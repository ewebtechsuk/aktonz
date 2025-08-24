#!/usr/bin/env bash
set -euo pipefail
ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT_DIR"

PLUGINS_DISABLED="wp-content/plugins.disabled"
PLUGINS_DIR="wp-content/plugins"
PLUGINS_OFF="wp-content/plugins.off"
LOG="wp-content/plugin-test-results.log"

echo "Plugin test run started at $(date)" > "$LOG"

if [ ! -d "$PLUGINS_DISABLED" ]; then
  echo "ERROR: $PLUGINS_DISABLED not found" | tee -a "$LOG"
  exit 2
fi

rm -rf "$PLUGINS_OFF"
mkdir -p "$PLUGINS_OFF"

# Move a copy of each disabled plugin into plugins.off so we can copy them back one-by-one
for p in "$PLUGINS_DISABLED"/*; do
  [ -e "$p" ] || continue
  base=$(basename "$p")
  cp -a "$p" "$PLUGINS_OFF/$base"
done

mkdir -p "$PLUGINS_DIR"

echo "Found plugins:" >> "$LOG"
for p in "$PLUGINS_OFF"/*; do
  [ -e "$p" ] || continue
  echo " - $(basename "$p")" >> "$LOG"
done

echo "--- STARTING PLUGIN LOOP ---" >> "$LOG"

for plugin in "$PLUGINS_OFF"/*; do
  [ -e "$plugin" ] || continue
  name=$(basename "$plugin")
  echo "\n=== TESTING: $name ===" | tee -a "$LOG"

  # wipe plugins dir and copy current plugin into place
  rm -rf "$PLUGINS_DIR"/* || true
  cp -a "$plugin" "$PLUGINS_DIR/$name"

  # clear php error log if present
  : > wp-content/php-error.log 2>/dev/null || true

  # run PHP (WP) under CLI with the local auto_prepend file; capture output
  php -d auto_prepend_file=wp-config-local.php -f index.php > /tmp/php.out 2>&1 || true
  rc=$?

  echo "EXIT_CODE:$rc" >> "$LOG"
  echo "--- php-error.log (tail 200) ---" >> "$LOG"
  tail -n 200 wp-content/php-error.log 2>/dev/null >> "$LOG" || true
  echo "--- /tmp/php.out (tail 200) ---" >> "$LOG"
  tail -n 200 /tmp/php.out 2>/dev/null >> "$LOG" || true
  echo "=== DONE: $name ===\n" >> "$LOG"
done

echo "ALL DONE $(date)" >> "$LOG"
echo "Results written to $LOG"
