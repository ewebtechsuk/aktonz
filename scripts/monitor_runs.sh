#!/usr/bin/env bash
# Monitor latest run of the Deploy to Hostinger workflow.
# Falls back to commit marker in page source if runs API returns empty.
# Usage: GH_PAT=token ./scripts/monitor_runs.sh [--interval 15] [--timeout 900]
set -euo pipefail
WF_ID=183728317
OWNER=ewebtechsuk
REPO=aktonz
PROD_URL=${PRODUCTION_URL:-https://aktonz.com}
INTERVAL=15
TIMEOUT=900
while [[ $# -gt 0 ]]; do
  case "$1" in
    --interval) INTERVAL="$2"; shift 2;;
    --timeout) TIMEOUT="$2"; shift 2;;
    *) echo "Unknown arg $1" >&2; exit 1;;
  esac
done
if [[ -z "${GH_PAT:-}" ]]; then echo "[monitor][fatal] GH_PAT not set" >&2; exit 1; fi
HEAD_SHA=$(git rev-parse HEAD)
SHORT=${HEAD_SHA:0:7}
API="https://api.github.com/repos/$OWNER/$REPO/actions/workflows/$WF_ID/runs?per_page=1"
START=$(date +%s); END=$((START+TIMEOUT)); ATTEMPT=0
printf '[monitor] tracking commit %s (%s) interval=%ss timeout=%ss\n' "$HEAD_SHA" "$SHORT" "$INTERVAL" "$TIMEOUT"
while :; do
  NOW=$(date +%s); (( NOW>END )) && echo '[monitor][timeout]' && exit 2
  ((ATTEMPT++))
  RESP=$(curl -s -H "Authorization: Bearer $GH_PAT" -H 'Accept: application/vnd.github+json' "$API" || true)
  if [[ -n "$RESP" && "$RESP" == *"workflow_runs"* ]]; then
    RUN_SHA=$(echo "$RESP" | sed -n 's/.*"head_sha"[[:space:]]*:[[:space:]]*"\([0-9a-f]\{40\}\)".*/\1/p' | head -n1)
    STATUS=$(echo "$RESP" | sed -n 's/.*"status"[[:space:]]*:[[:space:]]*"\([a-zA-Z_]*\)".*/\1/p' | head -n1)
    CONCLUSION=$(echo "$RESP" | sed -n 's/.*"conclusion"[[:space:]]*:[[:space:]]*"\([a-zA-Z_]*\)".*/\1/p' | head -n1)
    echo "[monitor][$ATTEMPT] api status=$STATUS conclusion=$CONCLUSION run_sha=${RUN_SHA:0:7}"
    if [[ "$RUN_SHA" == "$HEAD_SHA" ]]; then
      if [[ "$STATUS" == "completed" ]]; then
        echo "[monitor][success] run for target commit completed (conclusion=$CONCLUSION)"; break
      fi
    fi
  else
    echo "[monitor][$ATTEMPT][warn] runs API blank; using marker fallback"
  fi
  MARKER=$(curl -s "$PROD_URL/" | grep -o 'DEPLOY_COMMIT: [0-9a-f]\{7\}' | head -n1 || true)
  if [[ "$MARKER" == *"$SHORT"* ]]; then
    echo "[monitor][success] footer marker present for commit $SHORT"; exit 0
  fi
  sleep "$INTERVAL"
done
exit 0
