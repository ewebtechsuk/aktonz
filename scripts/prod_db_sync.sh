#!/usr/bin/env bash
# prod_db_sync.sh - Pull production MySQL DB via SSH + mysqldump and import locally
# Usage:
#   ./scripts/prod_db_sync.sh [--dry-run] [--no-import] [--no-replace]
# Requires env (export or .env) defining:
#   PROD_SSH_HOST   (e.g. aktonz.com)
#   PROD_SSH_USER   (ssh user)
#   PROD_WP_PATH    (absolute path to WP root on remote, where wp-config.php lives)
# Optional:
#   PROD_SSH_PORT   (default 22)
#   PROD_DB_NAME / PROD_DB_USER / PROD_DB_PASSWORD (else parsed from remote wp-config.php)
#   PROD_DB_HOST    (defaults parsed; used for mysqldump host)
#   PRODUCTION_URL  (e.g. https://aktonz.com) for search-replace
#   LOCAL_URL       (defaults to http://localhost:8080)
#
# Local import path assumes docker (codex.sh setup). If only lite SQLite present,
# script can skip import unless you run with a MySQL-backed environment.
set -euo pipefail

DRY_RUN=0
DO_IMPORT=1
DO_REPLACE=1
for a in "$@"; do
  case "$a" in
    --dry-run) DRY_RUN=1 ;;
    --no-import) DO_IMPORT=0 ;;
    --no-replace) DO_REPLACE=0 ;;
    -h|--help)
      sed -n '1,60p' "$0"; exit 0 ;;
  esac
done

log(){ printf '[sync-db] %s\n' "$*"; }
err(){ printf '[sync-db][error] %s\n' "$*" >&2; }
fail(){ err "$*"; exit 1; }
need(){ command -v "$1" >/dev/null 2>&1 || fail "Need $1"; }

need ssh; need awk; need sed
command -v gzip >/dev/null 2>&1 || fail "Need gzip"

# Load local .env if exists for SITE_URL etc
if [ -f .env ]; then
  # shellcheck disable=SC1091
  . ./.env || true
fi

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

# Remote parse wp-config for creds if not provided
read_remote_cfg(){
  "${SSH_BASE[@]}" "grep -E '^define\\(\\'DB_(NAME|USER|PASSWORD|HOST)\\'' $PROD_WP_PATH/wp-config.php" 2>/dev/null || true
}

CFG_LINES=$(read_remote_cfg)

if [ -z "${PROD_DB_NAME:-}" ]; then
  PROD_DB_NAME=$(echo "$CFG_LINES" | awk -F"'" '/DB_NAME/{print $4; exit}')
fi
if [ -z "${PROD_DB_USER:-}" ]; then
  PROD_DB_USER=$(echo "$CFG_LINES" | awk -F"'" '/DB_USER/{print $4; exit}')
fi
if [ -z "${PROD_DB_PASSWORD:-}" ]; then
  PROD_DB_PASSWORD=$(echo "$CFG_LINES" | awk -F"'" '/DB_PASSWORD/{print $4; exit}')
fi
if [ -z "${PROD_DB_HOST:-}" ]; then
  PROD_DB_HOST=$(echo "$CFG_LINES" | awk -F"'" '/DB_HOST/{print $4; exit}')
fi

for v in PROD_DB_NAME PROD_DB_USER PROD_DB_PASSWORD PROD_DB_HOST; do
  eval val=\"\$$v\"; [ -n "$val" ] || fail "Could not determine $v (set env or ensure wp-config parse works)"

done

TS=$(date +%Y%m%d-%H%M%S)
OUT_DIR=backups
mkdir -p "$OUT_DIR"
REMOTE_TMP="/tmp/prod-${PROD_DB_NAME}-${TS}.sql"
REMOTE_GZ="${REMOTE_TMP}.gz"
LOCAL_DUMP="$OUT_DIR/prod-${PROD_DB_NAME}-${TS}.sql.gz"

log "Remote host: $PROD_SSH_HOST (port $PROD_SSH_PORT)"
log "Remote DB: $PROD_DB_NAME@$PROD_DB_HOST user=$PROD_DB_USER"
log "Production URL: $PRODUCTION_URL -> Local URL: $LOCAL_URL"

if [ $DRY_RUN -eq 1 ]; then
  log "[dry-run] Would run remote mysqldump & scp to $LOCAL_DUMP";
else
  log "Running remote mysqldump (compressed)"
  "${SSH_BASE[@]}" "mysqldump -u'$PROD_DB_USER' -p'$PROD_DB_PASSWORD' -h '$PROD_DB_HOST' --single-transaction --quick --no-tablespaces --routines --events '$PROD_DB_NAME' > $REMOTE_TMP && gzip -f $REMOTE_TMP" || fail "Remote dump failed"
  log "Copying dump"
  "${SCP_BASE[@]}" "$PROD_SSH_USER@$PROD_SSH_HOST:$REMOTE_GZ" "$LOCAL_DUMP" || fail "SCP failed"
  log "Cleaning remote temp"
  "${SSH_BASE[@]}" "rm -f $REMOTE_GZ" || true
  log "Dump saved -> $LOCAL_DUMP"
fi

if [ $DO_IMPORT -eq 1 ] && [ $DRY_RUN -eq 0 ]; then
  if grep -q 'WORDPRESS_DB_HOST' docker-compose.yml 2>/dev/null || docker compose ps 2>/dev/null | grep -q wordpress; then
    log "Backing up current local DB before import"
    if ./scripts/codex.sh backup-db "backups/pre-sync-${TS}.sql.gz" >/dev/null 2>&1; then :; else log "(warn) pre-backup failed"; fi
    log "Importing dump into local docker DB"
    ./scripts/codex.sh restore-db "$LOCAL_DUMP" || fail "Import failed"
  else
    err "Docker environment not detected; skipping import (dump retained)"
    DO_REPLACE=0
  fi
fi

if [ $DO_REPLACE -eq 1 ] && [ $DRY_RUN -eq 0 ]; then
  log "URL search-replace: $PRODUCTION_URL -> $LOCAL_URL"
  ./scripts/codex.sh wp search-replace "$PRODUCTION_URL" "$LOCAL_URL" --skip-columns=guid --all-tables || err "search-replace issues"
  log "Updating siteurl/home explicitly"
  ./scripts/codex.sh wp option update siteurl "$LOCAL_URL" || true
  ./scripts/codex.sh wp option update home "$LOCAL_URL" || true
fi

log "Done."
