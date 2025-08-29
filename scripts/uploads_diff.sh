#!/usr/bin/env bash
# uploads_diff.sh - Detect changes (added/removed/changed) in local uploads directory since last baseline.
# Usage:
#   scripts/uploads_diff.sh [--hash] [--json] [--refresh-only] [--baseline <file>] [--no-save]
#   scripts/uploads_diff.sh --remote [--hash] [--json]   # compare local vs remote (no baseline update)
#
# Baseline manifests are stored under backups/ as:
#   uploads-manifest-<timestamp>.txt  (TSV: path<TAB>size<TAB>mtime_epoch[<TAB>sha1])
# A convenience symlink/file `backups/uploads-manifest-latest.txt` points to the most recent baseline.
#
# Options:
#   --hash          Include sha1 hash (slower, but better change detection)
#   --json          Emit JSON summary line at end
#   --refresh-only  Generate new baseline without diffing (unless --remote)
#   --baseline F    Use explicit previous baseline file instead of latest
#   --no-save       Do not write a new baseline (just diff)
#   --remote        Build remote manifest via SSH (requires PROD_* env vars) and diff vs local current
#
# Env (remote mode): PROD_SSH_HOST, PROD_SSH_USER, PROD_SSH_PORT (22), PROD_WP_PATH
set -euo pipefail

HASH=0
JSON=0
REFRESH_ONLY=0
NO_SAVE=0
REMOTE=0
BASELINE_SPEC=""
for a in "$@"; do
  case "$a" in
    --hash) HASH=1 ;;
    --json) JSON=1 ;;
    --refresh-only) REFRESH_ONLY=1 ;;
    --no-save) NO_SAVE=1 ;;
    --remote) REMOTE=1 ;;
    --baseline) shift; BASELINE_SPEC="$1" ;;
    -h|--help) sed -n '1,80p' "$0"; exit 0 ;;
  esac
  shift || true
  [ "${a}" = "--baseline" ] && shift || true
done

log(){ printf '[uploads-diff] %s\n' "$*"; }
err(){ printf '[uploads-diff][error] %s\n' "$*" >&2; }
fail(){ err "$*"; exit 1; }
need(){ command -v "$1" >/dev/null 2>&1 || fail "Need $1"; }
need find; need awk; need sed; need sort; need grep
[ $HASH -eq 1 ] && need sha1sum || true

[ -f .env ] && . ./.env || true

# Detect local uploads directory
LOCAL_WP_ROOT=.
if [ -d wp-lite/wp-includes ]; then
  LOCAL_WP_ROOT=wp-lite
fi
UPLOADS_DIR="$LOCAL_WP_ROOT/wp-content/uploads"
[ -d "$UPLOADS_DIR" ] || fail "Uploads directory not found: $UPLOADS_DIR"

BACKUPS_DIR=backups; mkdir -p "$BACKUPS_DIR"
TS=$(date +%Y%m%d-%H%M%S)
NEW_MANIFEST="$BACKUPS_DIR/uploads-manifest-$TS.txt"
LATEST_LINK="$BACKUPS_DIR/uploads-manifest-latest.txt"

produce_manifest_local(){
  local target_dir="$1" out_file="$2" include_hash="$3"
  if find "$target_dir" -type f -printf '%P\t%s\t%T@\n' >"$out_file.tmp" 2>/dev/null; then
    :
  else
    # Fallback (slower & less precise mtime) if -printf unsupported
    find "$target_dir" -type f | while read -r f; do
      sz=$(stat -c %s "$f" 2>/dev/null || stat -f %z "$f")
      mt=$(stat -c %Y "$f" 2>/dev/null || stat -f %m "$f")
      rel=${f#"$target_dir/"}
      printf '%s\t%s\t%s\n' "$rel" "$sz" "$mt" >>"$out_file.tmp"
    done
  fi
  if [ "$include_hash" = "1" ]; then
    awk -F '\t' 'BEGIN{OFS="\t"}{print $0, "PENDING_HASH"}' "$out_file.tmp" >"$out_file.hashprep"
    mv "$out_file.hashprep" "$out_file.tmp"
    # compute hashes row by row to limit memory
    while IFS=$'\t' read -r rel sz mt maybe; do
      f="$target_dir/$rel"; h=$(sha1sum "$f" | awk '{print $1}')
      printf '%s\t%s\t%s\t%s\n' "$rel" "$sz" "$mt" "$h" >>"$out_file"
    done <"$out_file.tmp"
    rm -f "$out_file.tmp"
  else
    mv "$out_file.tmp" "$out_file"
  fi
  sort -o "$out_file" "$out_file"
}

produce_manifest_remote(){
  local out_file="$1" include_hash="$2"
  need ssh
  PROD_SSH_PORT=${PROD_SSH_PORT:-22}
  [ -n "${PROD_SSH_HOST:-}" ] || fail "PROD_SSH_HOST required for --remote"
  [ -n "${PROD_SSH_USER:-}" ] || fail "PROD_SSH_USER required for --remote"
  [ -n "${PROD_WP_PATH:-}" ] || fail "PROD_WP_PATH required for --remote"
  local remote_uploads="$PROD_WP_PATH/wp-content/uploads"
  local base_cmd="find '$remote_uploads' -type f -printf '%P\\t%s\\t%T@\\n'"
  if [ $include_hash -eq 1 ]; then
    base_cmd="bash -c \"$base_cmd | while IFS=\\$'\\t' read -r p sz mt; do h=\\$(sha1sum '$remote_uploads'/\\"$p\\" | awk '{print \\\$1}'); echo -e \\\"$p\\t$sz\\t$mt\\t$h\\"; done\""
  fi
  ssh -p "$PROD_SSH_PORT" "$PROD_SSH_USER@$PROD_SSH_HOST" "$base_cmd" >"$out_file" || fail "Remote manifest failed"
  sort -o "$out_file" "$out_file"
}

# Load baseline if present
BASELINE_FILE=""
if [ -n "$BASELINE_SPEC" ]; then
  BASELINE_FILE="$BASELINE_SPEC"
elif [ -f "$LATEST_LINK" ]; then
  BASELINE_FILE="$LATEST_LINK"
fi

# Remote mode: get remote manifest and diff vs local current (not vs baseline)
if [ $REMOTE -eq 1 ]; then
  TMP_LOCAL=$(mktemp)
  TMP_REMOTE=$(mktemp)
  log "Generating local manifest (current)"; produce_manifest_local "$UPLOADS_DIR" "$TMP_LOCAL" $HASH
  log "Generating remote manifest"; produce_manifest_remote "$TMP_REMOTE" $HASH
  BASE="$TMP_REMOTE" NEW="$TMP_LOCAL" # Reuse diff logic (BASE=remote, NEW=local current)
else
  # Normal mode: produce new manifest and diff vs baseline
  log "Generating new manifest: $NEW_MANIFEST"; produce_manifest_local "$UPLOADS_DIR" "$NEW_MANIFEST" $HASH
  if [ $REFRESH_ONLY -eq 1 ]; then
    log "Refresh-only requested; skipping diff"
    if [ $NO_SAVE -eq 0 ]; then
      cp "$NEW_MANIFEST" "$LATEST_LINK"
      log "Baseline updated -> $LATEST_LINK"
    fi
    [ $JSON -eq 1 ] && printf '{"refresh_only":true,"files":%s}\n' "$(wc -l <"$NEW_MANIFEST")"
    exit 0
  fi
  if [ -z "$BASELINE_FILE" ]; then
    log "No existing baseline; establishing initial baseline"
    [ $NO_SAVE -eq 0 ] && cp "$NEW_MANIFEST" "$LATEST_LINK"
    added=$(wc -l <"$NEW_MANIFEST")
    log "Baseline created with $added files"
    [ $JSON -eq 1 ] && printf '{"initial":true,"added":%s}\n' "$added"
    exit 0
  fi
  BASE="$BASELINE_FILE"; NEW="$NEW_MANIFEST"
fi

# Determine field separator counts
base_fields=$(awk -F '\t' 'NR==1{print NF}' "$BASE")
new_fields=$(awk -F '\t' 'NR==1{print NF}' "$NEW")
EXPECT_FIELDS=3; [ $HASH -eq 1 ] && EXPECT_FIELDS=4
[ $base_fields -eq $EXPECT_FIELDS ] || log "Warning: baseline field count ($base_fields) != expected ($EXPECT_FIELDS)"
[ $new_fields -eq $EXPECT_FIELDS ] || log "Warning: new field count ($new_fields) != expected ($EXPECT_FIELDS)"

# Diff sets
added=$(comm -13 <(cut -f1 "$BASE" | sort) <(cut -f1 "$NEW" | sort)) || true
removed=$(comm -23 <(cut -f1 "$BASE" | sort) <(cut -f1 "$NEW" | sort)) || true

# Changed: intersection where size (or hash if available) differs
changed=$(join -t $'\t' -j1 <(sort -k1,1 "$BASE") <(sort -k1,1 "$NEW") | awk -F '\t' -v hc=$HASH '{
  if (hc==1){ if($2!=$6 || $4!=$8){print $1}} else { if($2!=$5){print $1} }
}' | sort) || true

count_added=$(printf '%s\n' "$added" | sed '/^$/d' | wc -l | awk '{print $1}')
count_removed=$(printf '%s\n' "$removed" | sed '/^$/d' | wc -l | awk '{print $1}')
count_changed=$(printf '%s\n' "$changed" | sed '/^$/d' | wc -l | awk '{print $1}')

log "Added: $count_added, Removed: $count_removed, Changed: $count_changed"

show_limited(){
  local label="$1" data="$2"; [ -z "$data" ] && return 0; printf '%s\n' "$data" | head -20 | sed "s/^/  /" | sed "1s/^/Top $label:\n/"; }
show_limited Added "$added"
show_limited Removed "$removed"
show_limited Changed "$changed"

if [ $REMOTE -eq 0 ] && [ $NO_SAVE -eq 0 ]; then
  cp "$NEW" "$LATEST_LINK"
  log "Updated latest baseline -> $LATEST_LINK"
fi

if [ $JSON -eq 1 ]; then
  # Escape lists into JSON arrays (limited to first 50 entries for brevity)
  list_to_json(){
    printf '%s' "$1" | sed '/^$/d' | head -50 | awk 'BEGIN{printf("["); first=1} {gsub(/"/, "\\\""); if(!first)printf(","); printf("\""$0"\""); first=0} END{printf("]")}'
  }
  ja=$(list_to_json "$added")
  jr=$(list_to_json "$removed")
  jc=$(list_to_json "$changed")
  printf '{"remote":%s,"hash":%s,"added":%s,"removed":%s,"changed":%s,"counts":{"added":%s,"removed":%s,"changed":%s}}\n' \
    "$REMOTE" "$HASH" "$ja" "$jr" "$jc" "$count_added" "$count_removed" "$count_changed"
fi

exit 0
