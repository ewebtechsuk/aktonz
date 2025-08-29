#!/usr/bin/env sh
# codex_lite_sqlite_setup.sh - Minimal WordPress + SQLite (no Docker)
# Idempotent: safe to re-run. Produces wp-lite/ and starts PHP built-in server.
set -eu
PORT=${PORT:-8080}
SITE_URL=${SITE_URL:-http://localhost:${PORT}}
TITLE=${TITLE:-Lite SQLite}
ADMIN_USER=${ADMIN_USER:-admin}
ADMIN_PASS=${ADMIN_PASS:-admin}
ADMIN_EMAIL=${ADMIN_EMAIL:-admin@example.test}
PLUG_SLUG=sqlite-database-integration
CACHE_DIR=offline-cache
PLUG_CACHE=${CACHE_DIR}/plugins
CORE_TGZ=${CACHE_DIR}/wordpress-latest.tar.gz
WP_PATH=wp-lite
DROPIN=${WP_PATH}/wp-content/db.php
PLUG_DIR=${WP_PATH}/wp-content/plugins/${PLUG_SLUG}
ZIP=${PLUG_CACHE}/${PLUG_SLUG}.zip
WP_CLI=wp-cli.phar
log(){ printf '[lite] %s\n' "$*"; }
err(){ printf '[lite][error] %s\n' "$*" >&2; }
need(){ command -v "$1" >/dev/null 2>&1 || { err "Need $1"; exit 1; }; }
need php; need curl
php -m 2>/dev/null | grep -Eqi '(pdo_sqlite|sqlite3)' || { err "Missing SQLite PHP extension"; exit 1; }
mkdir -p "$PLUG_CACHE" "$WP_PATH"

# Idempotent fast path: if already installed, just ensure server + emit status JSON
if [ -f "$WP_PATH/wp-load.php" ] && [ -f "$WP_CLI" ] && php "$WP_CLI" --path="$WP_PATH" core is-installed --allow-root >/dev/null 2>&1; then
  log "Already installed (fast path)"
  if ! pgrep -f "php -S 127.0.0.1:${PORT} -t ${WP_PATH}" >/dev/null 2>&1; then
    log "Starting PHP server :$PORT"
    (php -S 127.0.0.1:${PORT} -t "$WP_PATH" >/tmp/wp-lite-${PORT}.log 2>&1 &)
    sleep 1
  fi
  VER=""; [ -f "$WP_PATH/wp-includes/version.php" ] && VER=$(awk -F"'" '/\$wp_version *=/ { for(i=1;i<=NF;i++){ if($i ~ /^[0-9]+(\.[0-9]+)*$/){ printf "%s", $i; exit } } }' "$WP_PATH/wp-includes/version.php" 2>/dev/null || true)
  printf '{"mode":"lite","db":"sqlite","url":"%s","core_version":"%s","admin":"%s","user":"%s"}\n' "$SITE_URL" "$VER" "$SITE_URL/wp-admin/" "$ADMIN_USER"
  log "Admin: $SITE_URL/wp-admin/ ($ADMIN_USER/$ADMIN_PASS)"
  log "Done."
  exit 0
fi
# Core fetch
if [ ! -f "$WP_PATH/wp-load.php" ]; then
  [ -f "$CORE_TGZ" ] || { log "Download core"; mkdir -p "$CACHE_DIR"; curl -fsSL https://wordpress.org/latest.tar.gz -o "$CORE_TGZ"; }
  tmp=$(mktemp -d); tar -xzf "$CORE_TGZ" -C "$tmp"; cp -R "$tmp/wordpress/"* "$WP_PATH/"; rm -rf "$tmp"; log "Core extracted"
fi
# Config
if [ ! -f "$WP_PATH/wp-config.php" ] && [ -f "$WP_PATH/wp-config-sample.php" ]; then
  cp "$WP_PATH/wp-config-sample.php" "$WP_PATH/wp-config.php"
  sed -i "s/database_name_here/none/;s/username_here/none/;s/password_here/none/" "$WP_PATH/wp-config.php" || true
  printf "\ndefine('FS_METHOD','direct');\n" >> "$WP_PATH/wp-config.php"
fi
# Plugin fetch
[ -f "$ZIP" ] || { log "Download plugin"; curl -fsSL "https://downloads.wordpress.org/plugin/${PLUG_SLUG}.latest-stable.zip" -o "$ZIP"; }
# Plugin extract
if [ ! -d "$PLUG_DIR" ]; then
  if command -v unzip >/dev/null 2>&1; then tmp=$(mktemp -d); unzip -q "$ZIP" -d "$tmp"; mv "$tmp/$PLUG_SLUG" "$PLUG_DIR"; rm -rf "$tmp"; else
    php -m | grep -qi zip || { err "Need unzip or ZipArchive"; exit 1; }
    php - "$ZIP" "$PLUG_DIR" <<'PHPZ'
<?php $z=new ZipArchive; if($z->open($argv[1])!==true) exit(1); $base='sqlite-database-integration/'; for($i=0;$i<$z->numFiles;$i++){ $st=$z->statIndex($i); $n=$st['name']; if(strpos($n,$base)!==0) continue; $rel=substr($n,strlen($base)); if($rel==='') continue; $p=$argv[2].'/'.$rel; if(substr($n,-1)=='/'){ if(!is_dir($p)) mkdir($p,0777,true);} else { if(!is_dir(dirname($p))) mkdir(dirname($p),0777,true); file_put_contents($p,$z->getFromIndex($i)); }} ?>
PHPZ
  fi
fi
# Drop-in
if [ ! -f "$DROPIN" ]; then
  if [ -f "$PLUG_DIR/db.copy" ]; then
    sed "s|{SQLITE_IMPLEMENTATION_FOLDER_PATH}|wp-content/plugins/${PLUG_SLUG}|; s|{SQLITE_PLUGIN}|${PLUG_SLUG}/load.php|" "$PLUG_DIR/db.copy" > "$DROPIN"
  else
    cp "$PLUG_DIR/wp-includes/sqlite/db.php" "$DROPIN" 2>/dev/null || { err "No db.copy or fallback db.php"; exit 1; }
  fi
fi
# wp-cli
[ -f "$WP_CLI" ] || curl -fsSL https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar -o "$WP_CLI"
# Install
if ! php "$WP_CLI" --path="$WP_PATH" core is-installed --allow-root >/dev/null 2>&1; then
  php "$WP_CLI" --path="$WP_PATH" core install --url="$SITE_URL" --title="$TITLE" --admin_user="$ADMIN_USER" --admin_password="$ADMIN_PASS" --admin_email="$ADMIN_EMAIL" --skip-email --allow-root
fi
# Serve
if ! pgrep -f "php -S 127.0.0.1:${PORT} -t ${WP_PATH}" >/dev/null 2>&1; then
  log "Starting PHP server on :$PORT"
  (php -S 127.0.0.1:${PORT} -t "$WP_PATH" >/tmp/wp-lite-${PORT}.log 2>&1 &)
  sleep 1
fi
# Version
VER=""; [ -f "$WP_PATH/wp-includes/version.php" ] && VER=$(awk -F"'" '/\$wp_version *=/ { for(i=1;i<=NF;i++){ if($i ~ /^[0-9]+(\.[0-9]+)*$/){ printf "%s", $i; exit } } }' "$WP_PATH/wp-includes/version.php" 2>/dev/null || true)
# JSON status
printf '{"mode":"lite","db":"sqlite","url":"%s","core_version":"%s","admin":"%s","user":"%s"}\n' "$SITE_URL" "$VER" "$SITE_URL/wp-admin/" "$ADMIN_USER"
log "Admin: $SITE_URL/wp-admin/ ($ADMIN_USER/$ADMIN_PASS)"
log "Done."
