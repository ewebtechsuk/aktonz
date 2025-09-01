#!/usr/bin/env bash
# Poll production heartbeat JSON until commit matches local HEAD
# Usage: PRODUCTION_URL=https://aktonz.com ./scripts/monitor_heartbeat.sh [--timeout 600] [--interval 10]
set -euo pipefail
INTERVAL=10
TIMEOUT=600
while [[ $# -gt 0 ]]; do
  case "$1" in
    --interval) INTERVAL="$2"; shift 2;;
    --timeout) TIMEOUT="$2"; shift 2;;
    *) echo "Unknown arg: $1" >&2; exit 1;;
  esac
done
if [[ -z "${PRODUCTION_URL:-}" ]]; then
  echo "[heartbeat][fatal] PRODUCTION_URL not set" >&2; exit 1
fi
HEAD_SHA=$(git rev-parse HEAD)
SHORT=${HEAD_SHA:0:7}
START=$(date +%s)
END=$((START + TIMEOUT))
URL_PRIMARY="${PRODUCTION_URL%/}/deploy-commit.json"
URL_FALLBACK="${PRODUCTION_URL%/}/wp-content/uploads/deploy/heartbeat.json"
URL_DOT="${PRODUCTION_URL%/}/.deploy-commit.json"
printf '[heartbeat] Watching for commit %s (%s) up to %ss (interval=%ss)\n' "$HEAD_SHA" "$SHORT" "$TIMEOUT" "$INTERVAL"
ATTEMPT=0
while true; do
  NOW=$(date +%s)
  if (( NOW > END )); then
    echo "[heartbeat][timeout] Commit not observed within ${TIMEOUT}s"; exit 2
  fi
  ((ATTEMPT++))
  for U in "$URL_PRIMARY" "$URL_FALLBACK" "$URL_DOT"; do
    BODY=$(curl -fsSL --max-time 5 "$U" 2>/dev/null || true)
    if [[ -n "$BODY" ]]; then
      COMMIT=$(printf '%s' "$BODY" | sed -n 's/.*"commit"[[:space:]]*:[[:space:]]*"\([0-9a-f]\{40\}\)".*/\1/p' | head -n1)
      if [[ -n "$COMMIT" ]]; then
        printf '[heartbeat][attempt %d] %s commit=%s url=%s\n' "$ATTEMPT" "$(date -u +%H:%M:%S)" "$COMMIT" "$U"
        if [[ "$COMMIT" == "$HEAD_SHA" ]]; then
          echo "[heartbeat][success] Deployment commit observed on $U"; exit 0
        fi
      else
        printf '[heartbeat][attempt %d][warn] No commit field in JSON from %s\n' "$ATTEMPT" "$U"
      fi
    else
      printf '[heartbeat][attempt %d][miss] No content from %s\n' "$ATTEMPT" "$U"
    fi
  done
  sleep "$INTERVAL"
Done
