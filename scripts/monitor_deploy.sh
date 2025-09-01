#!/usr/bin/env bash
# monitor_deploy.sh
# Poll the deploy workflow (deploy.yml) for a given commit and stream logs until completion.
# Requires: gh CLI authenticated (GH_TOKEN/GITHUB_TOKEN or gh auth login) with actions:read.
# Usage: bash scripts/monitor_deploy.sh [commit_sha]
set -euo pipefail

SHA=${1:-$(git rev-parse HEAD)}
WF_NAME="deploy.yml"
INTERVAL=15

echo "[monitor] Target commit: $SHA"

need_gh() { command -v gh >/dev/null 2>&1 || { echo "[monitor][fatal] gh CLI not installed" >&2; exit 1; }; }
need_gh

echo "[monitor] Waiting for workflow run (workflow=$WF_NAME)..."
RUN_ID=""
ATTEMPTS=0
while [ -z "$RUN_ID" ]; do
  ATTEMPTS=$((ATTEMPTS+1))
  # Fetch JSON list, filter by headSha match
  RAW=$(gh run list --workflow "$WF_NAME" --limit 20 --json databaseId,headSha,status,conclusion 2>/dev/null || true)
  if [ -n "$RAW" ]; then
    RUN_ID=$(printf '%s' "$RAW" | jq -r --arg SHA "$SHA" '.[] | select(.headSha==$SHA) | .databaseId' | head -n1)
  fi
  if [ -n "$RUN_ID" ]; then
    echo "[monitor] Found run id=$RUN_ID (after $ATTEMPTS attempt(s))"; break; fi
  sleep 5
done

echo "[monitor] Streaming status... (poll every ${INTERVAL}s)"
LAST_STATUS=""
while :; do
  INFO=$(gh run view "$RUN_ID" --json status,conclusion,createdAt,updatedAt,headSha,displayTitle || true)
  STATUS=$(printf '%s' "$INFO" | jq -r '.status')
  CONCLUSION=$(printf '%s' "$INFO" | jq -r '.conclusion')
  if [ "$STATUS" != "$LAST_STATUS" ]; then
    echo "[monitor] Status change: $LAST_STATUS -> $STATUS (conclusion=$CONCLUSION)";
    LAST_STATUS=$STATUS
  fi
  if [ "$STATUS" = "completed" ]; then
    echo "[monitor] Final conclusion: $CONCLUSION";
    echo "[monitor] Fetching logs...";
    gh run view "$RUN_ID" --log || true
    exit 0
  fi
  sleep "$INTERVAL"
done
