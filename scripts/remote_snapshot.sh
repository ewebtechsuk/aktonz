#!/usr/bin/env bash
set -euo pipefail

# Remote snapshot/audit helper.
# Collects key WordPress deployment state from Hostinger and writes:
#  - human-readable *.log sections
#  - machine-readable snapshot.json summary (includes plugin/theme hashes if enabled)
#  - plugin_hashes.json / theme_hashes.json (hash of concatenated file hashes per dir)
#  - optional small tarball with selected files for diffing
#
# Requirements (export / provide via GitHub Actions env/secrets):
#  HOSTINGER_SSH_HOST, HOSTINGER_SSH_USER, HOSTINGER_SSH_PORT, HOSTINGER_PATH, HOSTINGER_SSH_KEY (if using ssh-agent pre-step)
#
# Usage examples:
#  bash scripts/remote_snapshot.sh                # default run
#  SNAPSHOT_TARBALL=0 bash scripts/remote_snapshot.sh  # skip tarball
#  EXTRA_FIND="-maxdepth 3" bash scripts/remote_snapshot.sh
#
# Exit codes:
#  0 success (even if some optional files missing)
#  1 missing required env or SSH/base failure

REQ_VARS=(HOSTINGER_SSH_HOST HOSTINGER_SSH_USER HOSTINGER_SSH_PORT HOSTINGER_PATH)
MISS=()
for v in "${REQ_VARS[@]}"; do
  [ -n "${!v:-}" ] || MISS+=("$v")
done
if [ ${#MISS[@]} -gt 0 ]; then
  echo "[snapshot][fatal] Missing required env: ${MISS[*]}" >&2
  exit 1
fi

SSH_HOST="$HOSTINGER_SSH_HOST"
SSH_USER="$HOSTINGER_SSH_USER"
SSH_PORT="$HOSTINGER_SSH_PORT"
BASE_PATH="${HOSTINGER_PATH%/}"
STAMP=$(date -u +%Y%m%dT%H%M%SZ)
OUT_DIR="snapshot_$STAMP"
mkdir -p "$OUT_DIR" || true

ssh_cmd() { ssh -o BatchMode=yes -p "$SSH_PORT" "$SSH_USER@$SSH_HOST" "$@"; }

echo "[snapshot] Remote connectivity test..."
if ! ssh_cmd "echo ok" >/dev/null 2>&1; then
  echo "[snapshot][fatal] SSH connectivity failed" >&2
  exit 1
fi

log() { printf '%s\n' "$*" | tee -a "$OUT_DIR/snapshot.log"; }
section() { log "\n===== $* ====="; }

section "TOP-LEVEL LISTING"
ssh_cmd "ls -lA --time-style=long-iso '$BASE_PATH'" 2>/dev/null | tee -a "$OUT_DIR/snapshot.log" || true

section "DISK USAGE (selected)"
ssh_cmd "du -sh '$BASE_PATH'/wp-{content,includes,admin} 2>/dev/null" | tee -a "$OUT_DIR/snapshot.log" || true

section "WP-CONFIG METRICS"
CFG="$BASE_PATH/wp-config.php"
if ssh_cmd "test -f '$CFG'"; then
  LINES=$(ssh_cmd "wc -l < '$CFG'")
  SIZE=$(ssh_cmd "stat -c %s '$CFG'")
  MD5=$(ssh_cmd "md5sum '$CFG' | awk '{print $1}'")
  MTIME=$(ssh_cmd "stat -c %y '$CFG'")
  log "lines=$LINES size=$SIZE md5=$MD5 mtime='$MTIME'"
  ssh_cmd "head -n 3 '$CFG'; tail -n 3 '$CFG'" > "$OUT_DIR/wp-config.headtail.txt" 2>/dev/null || true
else
  log "missing=true"
  LINES=0 SIZE=0 MD5=missing MTIME=""
fi

section "PLUGINS LIST"
PLUG_DIR="$BASE_PATH/wp-content/plugins"
if ssh_cmd "test -d '$PLUG_DIR'"; then
  ssh_cmd "ls -1A '$PLUG_DIR'" | tee "$OUT_DIR/plugins.list" | sed 's/^/[plugins]/' >> "$OUT_DIR/snapshot.log" || true
else
  log "plugins_dir_missing"
fi

section "DISABLED VARIANTS"
DISABLED_JSON="[]"
if ssh_cmd "test -d '$PLUG_DIR'"; then
  VARS=$(ssh_cmd "ls -1A '$PLUG_DIR' 2>/dev/null" | grep -E '(\.disabled(\.[0-9]+)?$|^_off_)' || true)
  if [ -n "$VARS" ]; then
    printf '%s\n' "$VARS" | sed 's/^/[disabled]/' >> "$OUT_DIR/snapshot.log"
    # build JSON array
    ITEMS=""
    while IFS= read -r x; do [ -z "$x" ] && continue; ITEMS+="\"$x\","; done <<<"$VARS"
    DISABLED_JSON="[${ITEMS%,}]"
  fi
fi

section "LITESPEED RESIDUAL SCAN"
ssh_cmd "grep -RIl -m1 -E 'LiteSpeed|litespeed' '$BASE_PATH/wp-config.php' '$BASE_PATH/wp-content/object-cache.php' '$BASE_PATH/wp-content/advanced-cache.php' '$BASE_PATH/wp-content/mu-plugins' 2>/dev/null | head -n 40" | sed 's/^/[ls]/' | tee -a "$OUT_DIR/snapshot.log" || true

section "OBJECT/ADVANCED CACHE DROP-INS"
for f in object-cache.php advanced-cache.php; do
  FP="$BASE_PATH/wp-content/$f"
  if ssh_cmd "test -f '$FP'"; then
    S=$(ssh_cmd "stat -c %s '$FP'")
    H=$(ssh_cmd "md5sum '$FP' | awk '{print $1}'")
    log "$f size=$S md5=$H"
  else
    log "$f missing"
  fi
done

section "DEPLOY MARKER (.deploy-info.json)"
MARKER="$BASE_PATH/.deploy-info.json"
if ssh_cmd "test -s '$MARKER'"; then
  HEAD=$(ssh_cmd "head -c 400 '$MARKER'")
  printf '%s\n' "$HEAD" | sed 's/^/[marker]/' | tee -a "$OUT_DIR/snapshot.log"
  ssh_cmd "cat '$MARKER'" > "$OUT_DIR/deploy-info.json" || true
else
  log "marker_missing"
fi

section "THEMES (top-level)"
THEME_DIR="$BASE_PATH/wp-content/themes"
if ssh_cmd "test -d '$THEME_DIR'"; then
  ssh_cmd "ls -1A '$THEME_DIR'" | sed 's/^/[themes]/' | tee -a "$OUT_DIR/snapshot.log" || true
else
  log "themes_dir_missing"
fi

# Optional hashing of plugin and theme directories
HASH_CONTENT=${HASH_CONTENT:-1}
if [ "$HASH_CONTENT" = 1 ]; then
  section "PLUGIN HASHES"
  if ssh_cmd "test -d '$PLUG_DIR'"; then
    PHASH_RAW=$(ssh_cmd "bash -c 'set -e; for d in \"$PLUG_DIR\"/*; do [ -d \"$d\" ] || continue; name=\"$(basename \"$d\")\"; H=$( (find \"$d\" -type f -not -path \"*/.git/*\" -print0 2>/dev/null | sort -z | xargs -0 sha256sum 2>/dev/null || true) | sha256sum | awk \"{print \\\$1}\"); [ -n \"$H\" ] || H=EMPTY; echo \"$name:$H\"; done'")
    printf '%s\n' "$PHASH_RAW" | sed 's/^/[phash]/' | tee -a "$OUT_DIR/snapshot.log" >/dev/null
    { echo '{'; first=1; while IFS= read -r line; do [ -z "$line" ] && continue; n=${line%%:*}; h=${line#*:}; if [ $first -eq 0 ]; then echo ','; fi; printf '"%s":"%s"' "$n" "$h"; first=0; done <<<"$PHASH_RAW"; echo '}'; } > "$OUT_DIR/plugin_hashes.json"
  else
    log "plugin_hashes_skipped (no dir)"
    echo '{}' > "$OUT_DIR/plugin_hashes.json"
  fi
  section "THEME HASHES"
  if ssh_cmd "test -d '$THEME_DIR'"; then
    THASH_RAW=$(ssh_cmd "bash -c 'set -e; for d in \"$THEME_DIR\"/*; do [ -d \"$d\" ] || continue; name=\"$(basename \"$d\")\"; H=$( (find \"$d\" -type f -not -path \"*/.git/*\" -print0 2>/dev/null | sort -z | xargs -0 sha256sum 2>/dev/null || true) | sha256sum | awk \"{print \\\$1}\"); [ -n \"$H\" ] || H=EMPTY; echo \"$name:$H\"; done'")
    printf '%s\n' "$THASH_RAW" | sed 's/^/[thash]/' | tee -a "$OUT_DIR/snapshot.log" >/dev/null
    { echo '{'; first=1; while IFS= read -r line; do [ -z "$line" ] && continue; n=${line%%:*}; h=${line#*:}; if [ $first -eq 0 ]; then echo ','; fi; printf '"%s":"%s"' "$n" "$h"; first=0; done <<<"$THASH_RAW"; echo '}'; } > "$OUT_DIR/theme_hashes.json"
  else
    log "theme_hashes_skipped (no dir)"
    echo '{}' > "$OUT_DIR/theme_hashes.json"
  fi
else
  log "HASH_CONTENT=0 (skipping plugin/theme hashing)"
  echo '{}' > "$OUT_DIR/plugin_hashes.json"
  echo '{}' > "$OUT_DIR/theme_hashes.json"
fi

# Build summary JSON
SUMMARY_JSON="$OUT_DIR/snapshot.json"
{
  printf '{'
  printf '"timestamp":"%s"' "$STAMP"
  printf ',"base_path":"%s"' "$BASE_PATH"
  printf ',"wp_config":{"lines":%s,"size":%s,"md5":"%s","mtime":"%s"}' "$LINES" "$SIZE" "$MD5" "$MTIME"
  printf ',"disabled_plugins":%s' "$DISABLED_JSON"
  printf ',"plugin_count":%s' $( [ -f "$OUT_DIR/plugins.list" ] && wc -l < "$OUT_DIR/plugins.list" || echo 0 )
  printf ',"marker_present":%s' $( [ -f "$OUT_DIR/deploy-info.json" ] && echo true || echo false )
  printf ',"object_cache_present":%s' $( ssh_cmd "test -f '$BASE_PATH/wp-content/object-cache.php' && echo true || echo false" )
  printf ',"advanced_cache_present":%s' $( ssh_cmd "test -f '$BASE_PATH/wp-content/advanced-cache.php' && echo true || echo false" )
  printf ',"plugin_hashes":%s' "$(cat "$OUT_DIR/plugin_hashes.json")"
  printf ',"theme_hashes":%s' "$(cat "$OUT_DIR/theme_hashes.json")"
  printf '}'
} > "$SUMMARY_JSON"

SNAPSHOT_TARBALL=${SNAPSHOT_TARBALL:-1}
if [ "$SNAPSHOT_TARBALL" = 1 ]; then
  TAR_TARGET="${OUT_DIR}.tar.gz"
  tar -czf "$TAR_TARGET" -C "$OUT_DIR" .
  echo "[snapshot] Created tarball $TAR_TARGET ($(du -h "$TAR_TARGET" | cut -f1))"
fi

echo "[snapshot] Complete. Outputs in $OUT_DIR/"
exit 0
