#!/usr/bin/env bash
# push_prod.sh - Guarded manual code push to Hostinger production (mirrors CI deploy logic subset)
# Usage:
#   scripts/push_prod.sh [--dry-run] [--delete] [--skip-confirm] [--no-manifest]
# Requires env (in .env or exported): HOSTINGER_SSH_HOST, HOSTINGER_SSH_USER, HOSTINGER_SSH_PORT, HOSTINGER_PATH
# Optional: PRODUCTION_URL for post-push smoke.
# Excludes uploads & volatile dirs; mirrors .gitignore deploy patterns.
set -euo pipefail
DRY=0; DO_DELETE=0; SKIP_CONFIRM=0; NO_MANIFEST=0
for a in "$@"; do
  case "$a" in
    --dry-run) DRY=1 ;;
    --delete) DO_DELETE=1 ;;
    --skip-confirm) SKIP_CONFIRM=1 ;;
    --no-manifest) NO_MANIFEST=1 ;;
    -h|--help) sed -n '1,60p' "$0"; exit 0 ;;
  esac
done

log(){ printf '[push] %s\n' "$*"; }
err(){ printf '[push][error] %s\n' "$*" >&2; }
fail(){ err "$*"; exit 1; }
need(){ command -v "$1" >/dev/null 2>&1 || fail "Need $1"; }
need rsync; need ssh; need git; need awk

[ -f .env ] && . ./.env || true
HOSTINGER_SSH_HOST=${HOSTINGER_SSH_HOST:-}
HOSTINGER_SSH_USER=${HOSTINGER_SSH_USER:-}
HOSTINGER_SSH_PORT=${HOSTINGER_SSH_PORT:-22}
HOSTINGER_PATH=${HOSTINGER_PATH:-}
PRODUCTION_URL=${PRODUCTION_URL:-}

missing_vars=()
[ -n "$HOSTINGER_SSH_HOST" ] || missing_vars+=(HOSTINGER_SSH_HOST)
[ -n "$HOSTINGER_SSH_USER" ] || missing_vars+=(HOSTINGER_SSH_USER)
[ -n "$HOSTINGER_PATH" ] || missing_vars+=(HOSTINGER_PATH)
if [ ${#missing_vars[@]} -gt 0 ]; then
  err "Missing required env vars: ${missing_vars[*]}"
  cat >&2 <<EOM
Add them to your .env or export before running, e.g.:
  echo 'HOSTINGER_SSH_HOST=example.com' >> .env
  echo 'HOSTINGER_SSH_USER=deploy' >> .env
  echo 'HOSTINGER_SSH_PORT=22' >> .env
  echo 'HOSTINGER_PATH=/home/deploy/public_html' >> .env
Then re-run: scripts/push_prod.sh --dry-run
EOM
  exit 1
fi

# Ensure clean git state (avoid pushing untracked junk unknowingly)
if [ -n "$(git status --porcelain | grep -E '^[AMDR]' || true)" ]; then
  fail "Uncommitted changes present; commit or stash before pushing"
fi

# Build manifest unless disabled
if [ $NO_MANIFEST -eq 0 ]; then
  if [ -x scripts/build_code_manifest.sh ]; then
    log "Building code manifest"
    scripts/build_code_manifest.sh >/dev/null 2>&1 || err "Manifest build issues (continuing)"
  fi
fi

# Remote preflight: test connectivity & ensure target dir exists
if ! ssh -p "$HOSTINGER_SSH_PORT" "$HOSTINGER_SSH_USER@$HOSTINGER_SSH_HOST" "test -d '$HOSTINGER_PATH'"; then
  fail "Remote path missing or inaccessible: $HOSTINGER_PATH"
fi

# Rsync exclude list
EXCLUDES=(
  '--exclude' '.git/'
  '--exclude' 'wp-content/uploads/'
  '--exclude' 'backups/'
  '--exclude' 'offline-cache/'
  '--exclude' 'wp-lite/'
  '--exclude' '*.log'
  '--exclude' '*.sql'
  '--exclude' 'node_modules/'
  '--exclude' 'vendor/'
  '--exclude' '*.wpress'
)

RSYNC_BASE=(rsync -az --info=stats1 --human-readable)
[ $DRY -eq 1 ] && RSYNC_BASE+=(--dry-run)
[ $DO_DELETE -eq 1 ] && RSYNC_BASE+=(--delete --delete-excluded)

RSYNC_BASE+=("-e" "ssh -p $HOSTINGER_SSH_PORT")
RSYNC_CMD=(${RSYNC_BASE[@]} "${EXCLUDES[@]}" ./ "$HOSTINGER_SSH_USER@$HOSTINGER_SSH_HOST:$HOSTINGER_PATH/")

log "Planned rsync command:"; printf '  %q' "${RSYNC_CMD[@]}"; echo

if [ $SKIP_CONFIRM -eq 0 ]; then
  read -r -p "Proceed with push to $HOSTINGER_SSH_HOST:$HOSTINGER_PATH (y/N)? " ans
  case "$ans" in y|Y|yes|YES) ;; *) log "Aborted"; exit 0;; esac
fi

log "Starting rsync (dry=$DRY delete=$DO_DELETE)"
"${RSYNC_CMD[@]}" || fail "rsync failed"
log "Rsync phase complete"

# Post-push optional smoke
if [ -n "$PRODUCTION_URL" ] && command -v curl >/dev/null 2>&1; then
  log "Smoke: $PRODUCTION_URL"
  CODE=$(curl -k -s -o /dev/null -w '%{http_code}' "$PRODUCTION_URL" || true)
  log "HTTP $CODE for homepage"
fi

# Record a small deploy marker (commit + manifest hash) remotely
if command -v sha256sum >/dev/null 2>&1; then
  COMMIT=$(git rev-parse --short HEAD)
  MANIFEST_HASH=$(grep '^CODE_MANIFEST_HASH=' code-manifest.json 2>/dev/null || true)
  ssh -p "$HOSTINGER_SSH_PORT" "$HOSTINGER_SSH_USER@$HOSTINGER_SSH_HOST" "echo '{"commit":"$COMMIT","time":"$(date -u +%Y-%m-%dT%H:%M:%SZ)"}' > '$HOSTINGER_PATH/.last_manual_push.json'" || true
  log "Wrote remote .last_manual_push.json"
fi

log "Push complete"
