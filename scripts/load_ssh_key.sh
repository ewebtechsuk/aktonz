#!/usr/bin/env bash
# Auto-load SSH key for repo pushes & remote Hostinger ops.
# Usage: ./scripts/load_ssh_key.sh [--key ./deploy_aktonz_key] [--remote-test] [--hostinger-test]
# Adds key if not already loaded, ensures git remote uses SSH, and optionally tests SSH connectivity.
set -euo pipefail
KEY=./deploy_aktonz_key
DO_REMOTE_TEST=false
DO_HOSTINGER_TEST=false
DO_FINGERPRINT=false
DO_DEBUG_SSH=false
while [[ $# -gt 0 ]]; do
  case "$1" in
    --key) KEY="$2"; shift 2;;
    --remote-test) DO_REMOTE_TEST=true; shift;;
    --hostinger-test) DO_HOSTINGER_TEST=true; shift;;
    --fingerprint) DO_FINGERPRINT=true; shift;;
    --debug-ssh) DO_DEBUG_SSH=true; shift;;
    *) echo "[key][error] Unknown arg $1" >&2; exit 1;;
  esac
done
if [[ ! -f "$KEY" ]]; then
  echo "[key][fatal] Key file not found: $KEY" >&2; exit 1
fi
chmod 600 "$KEY" || true
# Start agent if needed
if [[ -z "${SSH_AUTH_SOCK:-}" ]] || ! ssh-add -l >/dev/null 2>&1; then
  echo "[key] Starting new ssh-agent"
  eval "$(ssh-agent -s)" >/dev/null
else
  echo "[key] Existing ssh-agent detected"
fi
# Check if key already loaded
if ssh-add -l 2>/dev/null | grep -q "$(ssh-keygen -lf "$KEY" | awk '{print $2}')"; then
  echo "[key] Already loaded"
else
  ssh-add "$KEY"
  echo "[key] Added key: $KEY"
fi
# Ensure git remote uses SSH
CURRENT_URL=$(git remote get-url origin 2>/dev/null || echo '')
if [[ "$CURRENT_URL" =~ ^https://github.com/ ]]; then
  SSH_URL="git@github.com:${CURRENT_URL#https://github.com/}"
  git remote set-url origin "$SSH_URL"
  echo "[key] Updated origin to SSH: $SSH_URL"
else
  echo "[key] Remote origin already SSH or unset: $CURRENT_URL"
fi
if $DO_REMOTE_TEST; then
  echo "[key] Testing GitHub SSH auth..."
  ssh -o BatchMode=yes -T git@github.com 2>&1 | sed 's/^/[github]/'
fi
if $DO_FINGERPRINT; then
  if [[ -f "$KEY.pub" ]]; then
    FP=$(ssh-keygen -l -f "$KEY.pub" | awk '{print $2}')
    TYPE=$(ssh-keygen -l -f "$KEY.pub" | awk '{print $4}')
    echo "[key] Public key fingerprint: $FP ($TYPE)"
  else
    echo "[key][warn] No .pub alongside private key; cannot show fingerprint"
  fi
fi
if $DO_DEBUG_SSH; then
  echo "[key] Running verbose SSH test forcing this key..."
  ssh -vvv -i "$KEY" -o IdentitiesOnly=yes -T git@github.com 2>&1 | sed 's/^/[debug]/' || true
fi
if $DO_HOSTINGER_TEST; then
  # Expect env vars HOSTINGER_SSH_HOST HOSTINGER_SSH_PORT HOSTINGER_SSH_USER
  if [[ -z "${HOSTINGER_SSH_HOST:-}" || -z "${HOSTINGER_SSH_USER:-}" || -z "${HOSTINGER_SSH_PORT:-}" ]]; then
    echo "[key][warn] HOSTINGER_SSH_HOST / USER / PORT not all set; skipping Hostinger test"
  else
    echo "[key] Testing Hostinger SSH...";
    ssh -o BatchMode=yes -p "$HOSTINGER_SSH_PORT" "$HOSTINGER_SSH_USER@$HOSTINGER_SSH_HOST" 'echo [remote] ok' 2>&1 | sed 's/^/[hostinger]/'
  fi
fi
# Show loaded keys summary
ssh-add -l | sed 's/^/[agent]/'
