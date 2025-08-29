#!/usr/bin/env sh
# lite_quickstart.sh - Minimal no-docker WordPress (SQLite) bootstrap
# Usage: sh scripts/lite_quickstart.sh
set -eu
PORT=${PORT:-8080}
SITE_URL=${SITE_URL:-http://localhost:${PORT}}
PLUG_SLUG=sqlite-database-integration
CACHE=offline-cache
mkdir -p "$CACHE/plugins" wp-lite
# Fetch wp core (tarball) if not present
if [ ! -f wp-lite/wp-load.php ]; then
  echo "[lite] Downloading core" >&2
  curl -fsSL https://wordpress.org/latest.tar.gz -o /tmp/wp.tgz
  tar -xzf /tmp/wp.tgz -C /tmp
  cp -R /tmp/wordpress/* wp-lite/
fi
# Basic config
if [ ! -f wp-lite/wp-config.php ]; then
  cp wp-lite/wp-config-sample.php wp-lite/wp-config.php
  # Placeholder DB constants (unused with SQLite drop-in) & direct FS
  sed -i "s/database_name_here/none/;s/username_here/none/;s/password_here/none/" wp-lite/wp-config.php 2>/dev/null || true
  echo "define('FS_METHOD','direct');" >> wp-lite/wp-config.php
fi
# Get SQLite plugin zip (latest stable)
ZIP="$CACHE/plugins/${PLUG_SLUG}.zip"
if [ ! -f "$ZIP" ]; then
  echo "[lite] Fetching $PLUG_SLUG" >&2
  curl -fsSL "https://downloads.wordpress.org/plugin/${PLUG_SLUG}.latest-stable.zip" -o "$ZIP"
fi
PLUG_DIR=wp-lite/wp-content/plugins/${PLUG_SLUG}
if [ ! -d "$PLUG_DIR" ]; then
  echo "[lite] Extract plugin" >&2
  TMP=$(mktemp -d)
  unzip -q "$ZIP" -d "$TMP" || { echo "[lite][error] unzip failed" >&2; exit 1; }
  mv "$TMP/${PLUG_SLUG}" "$PLUG_DIR" || true
  rm -rf "$TMP"
fi
# Build db.php drop-in from db.copy if not already
DROP=wp-lite/wp-content/db.php
if [ ! -f "$DROP" ]; then
  if [ -f "$PLUG_DIR/db.copy" ]; then
    sed "s|{SQLITE_IMPLEMENTATION_FOLDER_PATH}|wp-content/plugins/${PLUG_SLUG}|; s|{SQLITE_PLUGIN}|${PLUG_SLUG}/load.php|" "$PLUG_DIR/db.copy" > "$DROP" || { echo "[lite][error] build db.php" >&2; exit 1; }
  else
    # fallback copy
    cp "$PLUG_DIR/wp-includes/sqlite/db.php" "$DROP" 2>/dev/null || { echo "[lite][error] missing db.copy & fallback" >&2; exit 1; }
  fi
fi
# Fetch wp-cli locally
if [ ! -f wp-cli.phar ]; then
  curl -fsSL https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar -o wp-cli.phar
fi
# Install if needed
if ! php wp-cli.phar --path=wp-lite core is-installed --allow-root >/dev/null 2>&1; then
  php wp-cli.phar --path=wp-lite core install \
    --url="$SITE_URL" \
    --title="Lite SQLite" \
    --admin_user=admin \
    --admin_password=admin \
    --admin_email=admin@example.test \
    --skip-email --allow-root
fi
# Start server (background) if not already
if ! pgrep -f "php -S 127.0.0.1:${PORT} -t wp-lite" >/dev/null 2>&1; then
  (php -S 127.0.0.1:${PORT} -t wp-lite >/tmp/lite-php.log 2>&1 &)
  sleep 1
fi
# Output JSON status
VER=""
[ -f wp-lite/wp-includes/version.php ] && VER=$(awk -F"'" '/\$wp_version *=/ { for(i=1;i<=NF;i++){ if($i ~ /^[0-9]+(\.[0-9]+)*$/){ printf "%s", $i; exit } } }' wp-lite/wp-includes/version.php 2>/dev/null || true)
printf '{"mode":"lite","db":"sqlite","url":"%s","core_version":"%s","admin":"%s"}\n' "$SITE_URL" "$VER" "$SITE_URL/wp-admin/"
echo "[lite] Admin: $SITE_URL/wp-admin/ (admin/admin)"
