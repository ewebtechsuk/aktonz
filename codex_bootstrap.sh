#!/usr/bin/env bash
set -euo pipefail
# codex_bootstrap.sh - Unified WordPress bootstrap (MySQL preferred, SQLite fallback)
# Env overrides (export before running if desired):
#  CODEX_SITE_URL, CODEX_SITE_TITLE, CODEX_ADMIN_USER, CODEX_ADMIN_PASS, CODEX_ADMIN_EMAIL
#  WORDPRESS_DB_HOST, DB_HOST, DB_NAME, DB_USER, DB_PASSWORD
#  CODEX_NO_SQLITE_FALLBACK=1  (to disable fallback)
#  PORT (for lite server)
export CODEX_SITE_URL=${CODEX_SITE_URL:-http://localhost:8080}
export CODEX_SITE_TITLE=${CODEX_SITE_TITLE:-"Aktonz Dev"}
export CODEX_ADMIN_USER=${CODEX_ADMIN_USER:-admin}
export CODEX_ADMIN_PASS=${CODEX_ADMIN_PASS:-ChangeMe123!}
export CODEX_ADMIN_EMAIL=${CODEX_ADMIN_EMAIL:-info@aktonz.com}
DB_NAME=${DB_NAME:-${WORDPRESS_DB_NAME:-wpdb}}
DB_USER=${DB_USER:-${WORDPRESS_DB_USER:-wpuser}}
DB_PASSWORD=${DB_PASSWORD:-${WORDPRESS_DB_PASSWORD:-wpsecret}}
DB_HOST_RAW=${WORDPRESS_DB_HOST:-${DB_HOST:-localhost}}
PORT=${PORT:-8080}

log(){ printf '[codex] %s\n' "$*"; }
warn(){ printf '[codex][warn] %s\n' "$*" >&2; }
fail(){ printf '[codex][error] %s\n' "$*" >&2; exit 1; }
need(){ command -v "$1" >/dev/null 2>&1 || fail "Need $1"; }

# dependencies (install if missing)
for bin in php curl awk unzip; do if ! command -v "$bin" >/dev/null 2>&1; then MISSING=1; fi; done || true
if [ "${MISSING:-0}" = 1 ]; then
  if command -v apt-get >/dev/null 2>&1; then
    echo "[codex] Installing missing packages (php php-sqlite3 curl unzip) via apt" >&2
    sudo apt-get update -y >/dev/null 2>&1 || sudo apt-get update -y
    sudo apt-get install -y php php-sqlite3 curl unzip >/dev/null 2>&1 || sudo apt-get install -y php php-sqlite3 curl unzip
  fi
fi
need php; need curl; need awk

# Auto strong admin password if default sentinel unchanged
if [ "$CODEX_ADMIN_PASS" = "ChangeMe123!" ]; then
  if command -v openssl >/dev/null 2>&1; then GEN=$(openssl rand -hex 8); else GEN=$(date +%s%N | sha256sum | cut -c1-16); fi
  CODEX_ADMIN_PASS="Adm-${GEN}!"
  export CODEX_ADMIN_PASS
  printf 'admin_user=%s\nadmin_pass=%s\n' "$CODEX_ADMIN_USER" "$CODEX_ADMIN_PASS" > .codex-admin.txt
  chmod 600 .codex-admin.txt || true
  log "Generated admin password (saved .codex-admin.txt)"
fi

# wp-cli (local phar if wp absent)
if ! command -v wp >/dev/null 2>&1; then
  if [ ! -f wp-cli.phar ]; then
    log "Downloading wp-cli.phar"
    curl -fsSL https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar -o wp-cli.phar
  fi
  chmod +x wp-cli.phar
  WP="php wp-cli.phar"
else
  WP="wp"
fi
[ "$(id -u)" = "0" ] && WP="$WP --allow-root"

# MySQL reachability probe (simple loop)
DB_HOST="$DB_HOST_RAW"; DB_PORT=3306
if echo "$DB_HOST_RAW" | grep -q ':'; then
  DB_HOST="${DB_HOST_RAW%%:*}"
  DB_PORT="${DB_HOST_RAW##*:}"
fi
mysql_ok=0
ATTEMPTS=${CODEX_MYSQL_WAIT_MAX:-6}
for a in $(seq 1 $ATTEMPTS); do
  if php -r '[$h,$u,$p,$d,$P]=array_slice($_SERVER["argv"],1);$m=@mysqli_connect($h,$u,$p,$d,$P);if($m){echo "OK";mysqli_close($m);}'; \
      "$DB_HOST" "$DB_USER" "$DB_PASSWORD" "$DB_NAME" "$DB_PORT" 2>/dev/null | grep -q OK; then
      mysql_ok=1; break
  fi
  sleep $((a))  # linear backoff
  log "MySQL not ready (attempt $a/$ATTEMPTS)"
done

if [ $mysql_ok -eq 1 ]; then
  log "MySQL reachable at ${DB_HOST}:${DB_PORT} (db=$DB_NAME)"
  # Create .env (layer source) if absent
  if [ ! -f .env ]; then
    cat > .env <<ENV
DB_NAME=$DB_NAME
DB_USER=$DB_USER
DB_PASSWORD=$DB_PASSWORD
DB_HOST=${DB_HOST_RAW}
TABLE_PREFIX=wp_
SITE_URL=$CODEX_SITE_URL
ENV
    log "Wrote .env"
  fi
  # Core download
  if [ ! -f wp-settings.php ]; then
    log "Downloading WordPress core"
    $WP core download --quiet
  fi
  # wp-config
  if [ ! -f wp-config.php ]; then
    log "Generating wp-config.php"
    $WP config create --dbname="$DB_NAME" --dbuser="$DB_USER" --dbpass="$DB_PASSWORD" --dbhost="$DB_HOST_RAW" --dbprefix=wp_ --skip-check --quiet
    SALTS=$(curl -fsSL https://api.wordpress.org/secret-key/1.1/salt/ || true)
    awk 'NR==1{print "<?php"} {print}' wp-config.php > wp-config.tmp && mv wp-config.tmp wp-config.php
    printf '\n// Load .env if present\nif (file_exists(__DIR__."/.env")) { foreach (parse_ini_file(__DIR__."/.env") as $k=>$v){ if(!getenv($k)) putenv("$k=$v"); } }\n' >> wp-config.php
    printf '// Salts\n%s\n' "$SALTS" >> wp-config.php
  fi
  # Install
  if ! $WP core is-installed >/dev/null 2>&1; then
    log "Installing core (URL=$CODEX_SITE_URL)"
    $WP core install --url="$CODEX_SITE_URL" --title="$CODEX_SITE_TITLE" \
       --admin_user="$CODEX_ADMIN_USER" --admin_password="$CODEX_ADMIN_PASS" --admin_email="$CODEX_ADMIN_EMAIL"
  else
    log "Core already installed"
  fi
  VER=$(awk -F"'" '/\$wp_version *=/ {for(i=1;i<=NF;i++){if($i~/^[0-9.]+$/){print $i;exit}}}' wp-includes/version.php 2>/dev/null || echo "")
  printf '{"mode":"%s","db":"%s","url":"%s","core_version":"%s","fallback_used":false}\n' "mysql" "mysql" "$CODEX_SITE_URL" "$VER" > .codex-status.json
  touch .codex-ready
  log "MySQL path complete. Admin: $CODEX_SITE_URL/wp-admin/ ($CODEX_ADMIN_USER / $CODEX_ADMIN_PASS)"
  exit 0
fi

if [ "${CODEX_NO_SQLITE_FALLBACK:-0}" = "1" ]; then
  fail "MySQL unreachable and SQLite fallback disabled (set CODEX_NO_SQLITE_FALLBACK=0 to allow)."
fi

log "MySQL unreachable – proceeding with SQLite lite fallback."
# --- SQLite lite path (wp-lite directory) ---
need unzip || php -m | grep -qi zip || fail "Need unzip or ZipArchive extension"
PLUG_SLUG=sqlite-database-integration
CACHE_DIR=offline-cache
PLUG_CACHE=${CACHE_DIR}/plugins
CORE_TGZ=${CACHE_DIR}/wordpress-latest.tar.gz
WP_LITE=wp-lite
DROPIN=${WP_LITE}/wp-content/db.php
mkdir -p "$PLUG_CACHE" "$WP_LITE"

if [ ! -f "$WP_LITE/wp-load.php" ]; then
  [ -f "$CORE_TGZ" ] || { log "Download core (lite)"; curl -fsSL https://wordpress.org/latest.tar.gz -o "$CORE_TGZ"; }
  tmp=$(mktemp -d); tar -xzf "$CORE_TGZ" -C "$tmp"
  cp -R "$tmp/wordpress/"* "$WP_LITE/"; rm -rf "$tmp"
fi
[ -f "$WP_LITE/wp-config.php" ] || cp "$WP_LITE/wp-config-sample.php" "$WP_LITE/wp-config.php" || true

ZIP=${PLUG_CACHE}/${PLUG_SLUG}.zip
[ -f "$ZIP" ] || curl -fsSL "https://downloads.wordpress.org/plugin/${PLUG_SLUG}.latest-stable.zip" -o "$ZIP"
if [ ! -d "$WP_LITE/wp-content/plugins/$PLUG_SLUG" ]; then
  tmp=$(mktemp -d); unzip -q "$ZIP" -d "$tmp"; mv "$tmp/$PLUG_SLUG" "$WP_LITE/wp-content/plugins/$PLUG_SLUG"; rm -rf "$tmp"
fi
if [ ! -f "$DROPIN" ]; then
  if [ -f "$WP_LITE/wp-content/plugins/$PLUG_SLUG/db.copy" ]; then
    sed "s|{SQLITE_IMPLEMENTATION_FOLDER_PATH}|wp-content/plugins/${PLUG_SLUG}|; s|{SQLITE_PLUGIN}|${PLUG_SLUG}/load.php|" \
      "$WP_LITE/wp-content/plugins/$PLUG_SLUG/db.copy" > "$DROPIN"
  else
    cp "$WP_LITE/wp-content/plugins/$PLUG_SLUG/wp-includes/sqlite/db.php" "$DROPIN" 2>/dev/null || fail "SQLite drop-in not found"
  fi
fi
[ -f wp-cli.phar ] || curl -fsSL https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar -o wp-cli.phar
chmod +x wp-cli.phar
WP_L="$([ "$(id -u)" = "0" ] && echo 'php wp-cli.phar --allow-root' || echo 'php wp-cli.phar')"

if ! $WP_L --path="$WP_LITE" core is-installed >/dev/null 2>&1; then
  $WP_L --path="$WP_LITE" core install --url="$CODEX_SITE_URL" --title="$CODEX_SITE_TITLE (Lite)" \
    --admin_user="$CODEX_ADMIN_USER" --admin_password="$CODEX_ADMIN_PASS" --admin_email="$CODEX_ADMIN_EMAIL" --skip-email
fi
if ! pgrep -f "php -S 127.0.0.1:${PORT} -t ${WP_LITE}" >/dev/null 2>&1; then
  log "Starting PHP built-in server :$PORT (lite)"
  (php -S 127.0.0.1:${PORT} -t "$WP_LITE" >/tmp/wp-lite-${PORT}.log 2>&1 &)
  sleep 1
fi
VER=$(awk -F"'" '/\$wp_version *=/ {for(i=1;i<=NF;i++){if($i~/^[0-9.]+$/){print $i;exit}}}' "$WP_LITE/wp-includes/version.php" 2>/dev/null || echo "")
printf '{"mode":"%s","db":"%s","url":"%s","core_version":"%s","fallback_used":true}\n' "lite" "sqlite" "$CODEX_SITE_URL" "$VER" > .codex-status.json
touch .codex-ready
log "Lite fallback complete. Admin: $CODEX_SITE_URL/wp-admin/ ($CODEX_ADMIN_USER / $CODEX_ADMIN_PASS)"
