#!/usr/bin/env bash
# push_prod.sh - Guarded manual code push to Hostinger production (mirrors CI deploy logic subset)
# Usage:
#   scripts/push_prod.sh [--dry-run] [--delete] [--skip-confirm] [--no-manifest] [--identity keyfile]
#   scripts/push_prod.sh -i deploy_aktonz_key --dry-run
# Requires env (in .env / .env.deploy or exported): HOSTINGER_SSH_HOST, HOSTINGER_SSH_USER, HOSTINGER_SSH_PORT, HOSTINGER_PATH
# Optional env:
#   PRODUCTION_URL   -> run simple HTTP smoke after push
#   HOSTINGER_SSH_KEY -> default identity file (overridden by --identity)
# Behavior:
#   - Sources .env then .env.deploy if present (so production-only secrets go in .env.deploy)
#   - Builds code manifest unless --no-manifest
#   - Writes remote .last_manual_push.json with commit + manifest hash
# Excludes uploads & volatile dirs; mirrors .gitignore deploy patterns.
set -euo pipefail
DRY=0; DO_DELETE=0; SKIP_CONFIRM=0; NO_MANIFEST=0; IDENTITY_FILE=""

# Argument parsing (supports value options)
while [ $# -gt 0 ]; do
  case "$1" in
    --dry-run) DRY=1; shift ;;
    --delete) DO_DELETE=1; shift ;;
    --skip-confirm) SKIP_CONFIRM=1; shift ;;
    --no-manifest) NO_MANIFEST=1; shift ;;
    --identity|-i)
      [ $# -ge 2 ] || { echo "--identity requires a path" >&2; exit 1; }
      IDENTITY_FILE="$2"; shift 2 ;;
    -h|--help)
      sed -n '1,120p' "$0"; exit 0 ;;
    *) echo "Unknown argument: $1" >&2; exit 1 ;;
  esac
done

log(){ printf '[push] %s\n' "$*"; }
err(){ printf '[push][error] %s\n' "$*" >&2; }
fail(){ err "$*"; exit 1; }
need(){ command -v "$1" >/dev/null 2>&1 || fail "Need $1"; }
need rsync; need ssh; need git; need awk

[ -f .env ] && . ./.env || true
[ -f .env.deploy ] && . ./.env.deploy || true
HOSTINGER_SSH_HOST=${HOSTINGER_SSH_HOST:-}
HOSTINGER_SSH_USER=${HOSTINGER_SSH_USER:-}
HOSTINGER_SSH_PORT=${HOSTINGER_SSH_PORT:-22}
HOSTINGER_PATH=${HOSTINGER_PATH:-}
PRODUCTION_URL=${PRODUCTION_URL:-}
HOSTINGER_SSH_KEY=${HOSTINGER_SSH_KEY:-}

# Identity resolution precedence: --identity > HOSTINGER_SSH_KEY env > deploy_aktonz_key file (repo root)
if [ -z "$IDENTITY_FILE" ]; then
  if [ -n "$HOSTINGER_SSH_KEY" ]; then
    IDENTITY_FILE="$HOSTINGER_SSH_KEY"
  elif [ -f ./deploy_aktonz_key ]; then
    IDENTITY_FILE="./deploy_aktonz_key"
  fi
fi

if [ -n "$IDENTITY_FILE" ]; then
  if [ ! -f "$IDENTITY_FILE" ]; then
    fail "Identity file not found: $IDENTITY_FILE"
  fi
  # Fix perms if too open (ssh will warn)
  if [ -n "$(command -v stat || true)" ]; then
    perm=$(stat -c '%a' "$IDENTITY_FILE" 2>/dev/null || echo "")
    case "$perm" in
      6??|7??) chmod 600 "$IDENTITY_FILE" || true ;;
    esac
  fi
fi

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
SSH_BASE=(ssh -p "$HOSTINGER_SSH_PORT")
[ -n "$IDENTITY_FILE" ] && SSH_BASE+=( -i "$IDENTITY_FILE" )
SSH_CMD=("${SSH_BASE[@]}")

if ! "${SSH_CMD[@]}" "$HOSTINGER_SSH_USER@$HOSTINGER_SSH_HOST" "test -d '$HOSTINGER_PATH'"; then
  fail "Remote path missing or inaccessible: $HOSTINGER_PATH"
fi

# Rsync exclude list
EXCLUDES=(
  '--exclude' '.git/'
  '--exclude' 'wp-content/uploads/'
  '--exclude' 'backups/'
  '--exclude' 'offline-cache/'
  '--exclude' 'wp-lite/'
  '--exclude' '.env'
  '--exclude' '.env.deploy'
  '--exclude' '*.log'
  '--exclude' '*.sql'
  '--exclude' 'node_modules/'
  '--exclude' 'vendor/'
  '--exclude' '*.wpress'
)

RSYNC_BASE=(rsync -az --info=stats1 --human-readable)
[ $DRY -eq 1 ] && RSYNC_BASE+=(--dry-run)
[ $DO_DELETE -eq 1 ] && RSYNC_BASE+=(--delete --delete-excluded)

SSH_TRANSPORT=(ssh -p "$HOSTINGER_SSH_PORT")
[ -n "$IDENTITY_FILE" ] && SSH_TRANSPORT+=( -i "$IDENTITY_FILE" )
RSYNC_BASE+=("-e" "${SSH_TRANSPORT[*]}")
RSYNC_CMD=(${RSYNC_BASE[@]} "${EXCLUDES[@]}" ./ "$HOSTINGER_SSH_USER@$HOSTINGER_SSH_HOST:$HOSTINGER_PATH/")

log "Planned rsync command:"; printf '  %q' "${RSYNC_CMD[@]}"; echo

if [ $SKIP_CONFIRM -eq 0 ]; then
  read -r -p "Proceed with push to $HOSTINGER_SSH_HOST:$HOSTINGER_PATH (y/N)? " ans
  case "$ans" in y|Y|yes|YES) ;; *) log "Aborted"; exit 0;; esac
fi

log "Starting rsync (dry=$DRY delete=$DO_DELETE identity=${IDENTITY_FILE:-none})"
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
  MANIFEST_FILE="code-manifest.json"
  if [ -f "$MANIFEST_FILE" ]; then
    MANIFEST_HASH=$(sha256sum "$MANIFEST_FILE" | awk '{print $1}')
  else
    MANIFEST_HASH=""
  fi
  PUSH_JSON=$(printf '{"commit":"%s","time":"%s","manifest_sha256":"%s"}\n' "$COMMIT" "$(date -u +%Y-%m-%dT%H:%M:%SZ)" "$MANIFEST_HASH")
  "${SSH_CMD[@]}" "$HOSTINGER_SSH_USER@$HOSTINGER_SSH_HOST" "printf '%s' '$PUSH_JSON' > '$HOSTINGER_PATH/.last_manual_push.json'" || true
  log "Wrote remote .last_manual_push.json"
fi

log "Push complete"
