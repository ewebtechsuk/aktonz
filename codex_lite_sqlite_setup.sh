#!/usr/bin/env sh
# Wrapper to call actual script in scripts/
# Allows platform setup command: sh codex_lite_sqlite_setup.sh
set -eu
if [ -f scripts/codex_lite_sqlite_setup.sh ]; then
  exec sh scripts/codex_lite_sqlite_setup.sh "$@"
else
  echo "[wrapper][error] scripts/codex_lite_sqlite_setup.sh missing" >&2
  exit 1
fi
