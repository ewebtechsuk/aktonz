#!/usr/bin/env bash
# prod_full_sync.sh - One-step production sync: DB + uploads (and URL replace)
# Usage:
#   ./scripts/prod_full_sync.sh [--dry-run] [--uploads-only] [--db-only] [--no-replace] [--keep-archives] [--rsync]
# Flags:
#   --rsync          Use rsync incremental sync for uploads (faster repeat runs, requires rsync on both ends)
# Env vars (optional):
#   UPLOADS_RSYNC_EXCLUDES="cache,backup-*,*.tmp"  (comma or colon separated patterns relative to uploads dir)
# Env / .env variables required (same as prod_db_sync):
#   PROD_SSH_HOST, PROD_SSH_USER, PROD_WP_PATH
# Optional: PROD_SSH_PORT (22), PROD_DB_* (auto-parsed otherwise), PRODUCTION_URL, LOCAL_URL
# Requires: ssh, scp, mysqldump (remote), gzip, tar, awk, sed, docker (if importing into docker DB)
# Safe defaults: creates timestamped backups before destructive DB import; idempotent uploads extraction.
set -euo pipefail

DRY_RUN=0
DO_DB=1
DO_UPLOADS=1
DO_REPLACE=1
KEEP_ARCHIVES=0
RSYNC_MODE=0
for a in "$@"; do
  case "$a" in
    --dry-run) DRY_RUN=1 ;;
    --uploads-only) DO_DB=0 ;;
    --db-only) DO_UPLOADS=0 ;;
    --no-replace) DO_REPLACE=0 ;;
  --keep-archives) KEEP_ARCHIVES=1 ;;
  --rsync) RSYNC_MODE=1 ;;
    -h|--help)
      sed -n '1,60p' "$0"; exit 0 ;;
  esac
done

log(){ printf '[full-sync] %s\n' "$*"; }
err(){ printf '[full-sync][error] %s\n' "$*" >&2; }
fail(){ err "$*"; exit 1; }
need(){ command -v "$1" >/dev/null 2>&1 || fail "Need $1"; }

need ssh; need scp; need awk; need sed; need gzip; need tar
[ $RSYNC_MODE -eq 1 ] && need rsync || true

[ -f .env ] && . ./.env || true
PROD_SSH_HOST=${PROD_SSH_HOST:-}
PROD_SSH_USER=${PROD_SSH_USER:-}
PROD_SSH_PORT=${PROD_SSH_PORT:-22}
PROD_WP_PATH=${PROD_WP_PATH:-}
PRODUCTION_URL=${PRODUCTION_URL:-https://aktonz.com}
LOCAL_URL=${LOCAL_URL:-${SITE_URL:-http://localhost:8080}}

[ -n "$PROD_SSH_HOST" ] || fail "PROD_SSH_HOST required"
[ -n "$PROD_SSH_USER" ] || fail "PROD_SSH_USER required"
[ -n "$PROD_WP_PATH" ] || fail "PROD_WP_PATH required"

SSH_BASE=(ssh -p "$PROD_SSH_PORT" "$PROD_SSH_USER@$PROD_SSH_HOST")
SCP_BASE=(scp -P "$PROD_SSH_PORT")

TS=$(date +%Y%m%d-%H%M%S)
BACKUPS_DIR=backups; mkdir -p "$BACKUPS_DIR"

# Detect local WP root (docker vs lite)
LOCAL_WP_ROOT="."
if [ -d wp-lite/wp-includes ]; then
  LOCAL_WP_ROOT=wp-lite
elif [ -d wp-includes ]; then
  LOCAL_WP_ROOT=.
fi
LOCAL_UPLOADS="$LOCAL_WP_ROOT/wp-content/uploads"

log "Remote host: $PROD_SSH_HOST (port $PROD_SSH_PORT)"
log "Production URL: $PRODUCTION_URL -> Local URL: $LOCAL_URL"
log "Local WP root: $LOCAL_WP_ROOT"
log "Uploads sync mode: $([ $RSYNC_MODE -eq 1 ] && echo rsync || echo archive)"

# 1. DB sync (delegates to prod_db_sync.sh for consistency)
if [ $DO_DB -eq 1 ]; then
  if [ $DRY_RUN -eq 1 ]; then
    log "[dry-run] Would invoke prod_db_sync.sh"
  else
    if [ ! -x scripts/prod_db_sync.sh ]; then
      fail "scripts/prod_db_sync.sh missing (generate first)"
    fi
    log "Running DB sync"
    # Pass through replace decision
    DB_ARGS=()
    [ $DO_REPLACE -eq 0 ] && DB_ARGS+=(--no-replace)
    ./scripts/prod_db_sync.sh "${DB_ARGS[@]}" || fail "DB sync failed"
  fi
else
  log "DB sync skipped (--uploads-only)"
fi

# 2. Uploads sync
if [ $DO_UPLOADS -eq 1 ]; then
  REMOTE_UPLOADS="$PROD_WP_PATH/wp-content/uploads"
  if [ $RSYNC_MODE -eq 1 ]; then
    # rsync incremental mode
    EX_PATTERNS=${UPLOADS_RSYNC_EXCLUDES:-}
    RSYNC_EXCLUDES=()
    if [ -n "$EX_PATTERNS" ]; then
      IFS=',:' read -r -a _raw_ex <<< "$EX_PATTERNS"
      for p in "${_raw_ex[@]}"; do
        p_trim=$(echo "$p" | sed 's/^ *//;s/ *$//')
        [ -n "$p_trim" ] && RSYNC_EXCLUDES+=(--exclude "$p_trim")
      done
    fi
    mkdir -p "$LOCAL_UPLOADS"
    if [ $DRY_RUN -eq 1 ]; then
      log "[dry-run] Would rsync remote uploads -> $LOCAL_UPLOADS"
    fi
    RSYNC_CMD=(rsync -az --info=stats1,progress2 --delete)
    [ $DRY_RUN -eq 1 ] && RSYNC_CMD+=(--dry-run)
    RSYNC_CMD+=("-e" "ssh -p $PROD_SSH_PORT")
    RSYNC_CMD+=("${RSYNC_EXCLUDES[@]}")
    RSYNC_CMD+=("$PROD_SSH_USER@$PROD_SSH_HOST:$REMOTE_UPLOADS/" "$LOCAL_UPLOADS/")
    log "Running: ${RSYNC_CMD[*]}"
    "${RSYNC_CMD[@]}" || fail "rsync uploads failed"
    log "Uploads rsync complete"
  else
    REMOTE_TGZ="/tmp/uploads-${TS}.tgz"
    LOCAL_TGZ="$BACKUPS_DIR/uploads-${TS}.tgz"
    if [ $DRY_RUN -eq 1 ]; then
      log "[dry-run] Would archive remote uploads: $REMOTE_UPLOADS -> $REMOTE_TGZ"
      log "[dry-run] Would scp to $LOCAL_TGZ and extract into $LOCAL_UPLOADS"
    else
      log "Archiving remote uploads (may take time)"
      "${SSH_BASE[@]}" "tar -czf $REMOTE_TGZ -C $(dirname "$REMOTE_UPLOADS") $(basename "$REMOTE_UPLOADS")" || fail "Remote tar failed"
      log "Copying archive"
      "${SCP_BASE[@]}" "$PROD_SSH_USER@$PROD_SSH_HOST:$REMOTE_TGZ" "$LOCAL_TGZ" || fail "SCP uploads failed"
      log "Cleaning remote tmp archive"
      "${SSH_BASE[@]}" "rm -f $REMOTE_TGZ" || true
      # Backup existing local uploads root (lightweight: just rename) before extraction
      if [ -d "$LOCAL_UPLOADS" ]; then
        mv "$LOCAL_UPLOADS" "${LOCAL_UPLOADS}.pre-${TS}" || true
      fi
      mkdir -p "$LOCAL_WP_ROOT/wp-content"
      log "Extracting uploads into $LOCAL_WP_ROOT/wp-content"
      tar -xzf "$LOCAL_TGZ" -C "$LOCAL_WP_ROOT/wp-content" || fail "Extract uploads failed"
      # Normalize path (extraction places 'uploads') ensure final path exists
      if [ ! -d "$LOCAL_UPLOADS" ]; then
        fail "Extraction missing uploads directory"
      fi
      [ $KEEP_ARCHIVES -eq 1 ] || rm -f "$LOCAL_TGZ" || true
      log "Uploads archive sync complete"
    fi
  fi
else
  log "Uploads sync skipped (--db-only)"
fi

# 3. URL replace if uploads-only (DB not imported this run) and requested; (rare)
if [ $DO_DB -eq 0 ] && [ $DO_REPLACE -eq 1 ] && [ $DRY_RUN -eq 0 ]; then
  if [ -x scripts/codex.sh ]; then
    log "Running URL search-replace (uploads-only scenario)"
    ./scripts/codex.sh wp search-replace "$PRODUCTION_URL" "$LOCAL_URL" --skip-columns=guid --all-tables || err "search-replace issues"
  fi
fi

log "Summary: DB=$DO_DB uploads=$DO_UPLOADS replace=$DO_REPLACE dry_run=$DRY_RUN rsync=$RSYNC_MODE"
log "Done."
