#!/usr/bin/env bash
# Root-level wrapper delegating to scripts/codex_setup_auto.sh
set -euo pipefail
if [ -f scripts/codex_setup_auto.sh ]; then
  exec bash scripts/codex_setup_auto.sh "$@"
else
  echo "[codex-wrapper] scripts/codex_setup_auto.sh missing" >&2
  exit 1
fi
