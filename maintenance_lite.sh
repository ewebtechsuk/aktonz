#!/usr/bin/env sh
# Wrapper to allow calling `sh maintenance_lite.sh` from repo root.
# Delegates to scripts/maintenance_lite.sh preserving all arguments.
set -eu
SCRIPT_DIR=$(cd "$(dirname "$0")" && pwd)
TARGET="$SCRIPT_DIR/scripts/maintenance_lite.sh"
if [ ! -f "$TARGET" ]; then
  echo "[maint-wrapper][error] Missing target script: $TARGET" >&2
  exit 127
fi
exec sh "$TARGET" "$@"
