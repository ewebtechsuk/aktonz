#!/usr/bin/env bash
# Manually dispatch deploy workflow with reason & optional inputs.
# Requires GH_PAT (classic PAT with repo + workflow scopes) exported.
set -euo pipefail
if [ -z "${GH_PAT:-}" ]; then echo "[dispatch][fatal] GH_PAT not set" >&2; exit 1; fi
REPO="ewebtechsuk/aktonz"
WF="deploy.yml"
REASON=${1:-"manual dispatch"}
JSON=$(jq -nc --arg r "$REASON" '{ref:"main",inputs:{reason:$r}}')
CODE=$(curl -s -o /tmp/resp.$$ -w '%{http_code}' -X POST \
  -H "Authorization: Bearer $GH_PAT" -H "Accept: application/vnd.github+json" \
  https://api.github.com/repos/$REPO/actions/workflows/$WF/dispatches \
  -d "$JSON") || true
if [ "$CODE" = 204 ]; then
  echo "[dispatch][ok] Workflow accepted (reason=$REASON)"
else
  echo "[dispatch][error] HTTP $CODE"; cat /tmp/resp.$$; rm -f /tmp/resp.$$; exit 1
fi
rm -f /tmp/resp.$$ || true
cat <<'EOF'
Next:
  1. (Optional) watch for heartbeat after some seconds:
       PRODUCTION_URL=https://aktonz.com ./scripts/monitor_heartbeat.sh --timeout 900 --interval 15
  2. If heartbeat fails to appear, add temporary debug steps or verify secrets.
EOF
