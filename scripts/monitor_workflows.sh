#!/usr/bin/env bash
# Simple GitHub Actions workflow log monitor.
# Shows recent runs for selected workflows and tails live logs for in-progress runs.
# Requirements: gh CLI authenticated with repo read permissions.
# Usage: ./scripts/monitor_workflows.sh [interval_seconds] [workflow_glob ...]
# Example: ./scripts/monitor_workflows.sh 25 diagnostics deploy

set -euo pipefail
INTERVAL=${1:-25}
shift || true
if [ $# -gt 0 ]; then
  WF_TARGETS=("$@")
else
  # Default common workflow name stems (without .yml extension needed)
  WF_TARGETS=(diagnostics deploy remote-snapshot)
fi

which gh >/dev/null 2>&1 || { echo "[monitor] gh CLI not found" >&2; exit 1; }

show_runs() {
  for wf in "${WF_TARGETS[@]}"; do
    echo "================ WORKFLOW: $wf (recent runs) ================"
    # List last 5 runs for workflow (match on filename or name)
    gh run list --workflow "$wf" --limit 5 2>/dev/null || true
  done
}

show_active_logs() {
  for wf in "${WF_TARGETS[@]}"; do
    # Get in-progress run ids (status: queued, in_progress)
    mapfile -t ACTIVE < <(gh run list --workflow "$wf" --json databaseId,status -q '.[] | select(.status=="in_progress" or .status=="queued") | .databaseId' 2>/dev/null || true)
    for rid in "${ACTIVE[@]}"; do
      echo "------ Tail (last 40 lines) for active run $rid ($wf) ------"
      gh run view "$rid" --log 2>/dev/null | tail -n 40 || true
    done
  done
}

cycle=0
while true; do
  clear || true
  echo "[monitor] Cycle $cycle @ $(date -u +%Y-%m-%dT%H:%M:%SZ) interval=${INTERVAL}s targets=${WF_TARGETS[*]}"
  show_runs
  echo
  show_active_logs
  echo "[monitor] Sleeping ${INTERVAL}s (Ctrl+C to exit)"
  cycle=$((cycle+1))
  sleep "$INTERVAL"
done
