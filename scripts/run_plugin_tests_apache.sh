#!/usr/bin/env bash
set -euo pipefail
ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT_DIR"

PLUGINS_DISABLED="wp-content/plugins.disabled"
PLUGINS_DIR="wp-content/plugins"
PLUGINS_OFF="wp-content/plugins.off"
LOG="wp-content/plugin-test-apache.log"

echo "Plugin apache test run started at $(date)" > "$LOG"

if [ ! -d "$PLUGINS_DISABLED" ]; then
  echo "ERROR: $PLUGINS_DISABLED not found" | tee -a "$LOG"
  exit 2
fi

rm -rf "$PLUGINS_OFF"
mkdir -p "$PLUGINS_OFF"

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

echo "--- STARTING APACHE PLUGIN LOOP ---" >> "$LOG"

for plugin in "$PLUGINS_OFF"/*; do
  [ -e "$plugin" ] || continue
  name=$(basename "$plugin")
  echo "\n=== TESTING (apache): $name ===" | tee -a "$LOG"

  # wipe plugins dir and copy current plugin into place
  rm -rf "$PLUGINS_DIR"/* || true
  cp -a "$plugin" "$PLUGINS_DIR/$name"

  # clear php error log if present (ensure wp-content exists)
  mkdir -p wp-content
  : > wp-content/php-error.log 2>/dev/null || true

  echo "Running container for $name" >> "$LOG"

  # Run container which installs mysqli, starts apache, curls the site, then stops
  docker run --rm -v "$(pwd)":/var/www/html -w /var/www/html php:8.0-apache bash -lc '
    set -euo pipefail
    apt-get update -y >/dev/null 2>&1 || true
    apt-get install -y default-libmysqlclient-dev build-essential >/dev/null 2>&1 || true
    docker-php-ext-install mysqli >/dev/null 2>&1 || true
    # start apache in background
    apache2ctl start >/dev/null 2>&1 || true
    sleep 1
    # request home page
    http_code=$(curl -sS -o /tmp/body -w "%{http_code}" http://127.0.0.1/ || true)
    echo "HTTP_CODE:$http_code"
    echo "--- container apache error log tail ---"
    tail -n 200 /var/log/apache2/error.log 2>/dev/null || true
    echo "--- php error log tail (within container) ---"
    tail -n 200 wp-content/php-error.log 2>/dev/null || true
    # stop apache
    apache2ctl stop >/dev/null 2>&1 || true
  ' 2>&1 | sed "s/^/[$name] /" >> "$LOG" || true

  echo "=== DONE (apache): $name ===\n" >> "$LOG"
done

echo "ALL DONE (apache) $(date)" >> "$LOG"
echo "Apache results written to $LOG"
