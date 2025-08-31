#!/usr/bin/env bash
## codex_manual_setup.sh - Minimal manual WordPress ("codex") bootstrap for shared hosting / plain Linux.
## Safe to re-run (idempotent). Creates .env, fetches wp-cli, installs WordPress if missing, sets salts, optional plugins.
## Usage:
##   bash scripts/codex_manual_setup.sh \
##     --url https://example.com \
##     --title "Site Title" \
##     --admin-user admin --admin-pass 'StrongPass123!' --admin-email you@example.com \
##     --db-name wpdb --db-user wpuser --db-pass 'dbpass' [--db-host localhost] [--prefix wp_] [--force]
## Environment overrides (export or put in existing .env): DB_NAME, DB_USER, DB_PASSWORD, DB_HOST, TABLE_PREFIX, SITE_URL
set -euo pipefail

FORCE=0
URL=""; TITLE=""; ADMIN_USER=""; ADMIN_PASS=""; ADMIN_EMAIL="";
DB_NAME=""; DB_USER=""; DB_PASS=""; DB_HOST="localhost"; PREFIX="wp_";
PLUGINS_CORE=(akismet)
PLUGINS_OPTIONAL=(wordpress-seo)

while [ $# -gt 0 ]; do
  case "$1" in
    --url) URL="$2"; shift 2 ;;
    --title) TITLE="$2"; shift 2 ;;
    --admin-user) ADMIN_USER="$2"; shift 2 ;;
    --admin-pass) ADMIN_PASS="$2"; shift 2 ;;
    --admin-email) ADMIN_EMAIL="$2"; shift 2 ;;
    --db-name) DB_NAME="$2"; shift 2 ;;
    --db-user) DB_USER="$2"; shift 2 ;;
    --db-pass) DB_PASS="$2"; shift 2 ;;
    --db-host) DB_HOST="$2"; shift 2 ;;
    --prefix) PREFIX="$2"; shift 2 ;;
    --force) FORCE=1; shift ;;
    -h|--help) sed -n '1,120p' "$0"; exit 0 ;;
    *) echo "Unknown arg: $1" >&2; exit 1 ;;
  esac
done

log(){ printf '[codex] %s\n' "$*"; }
fail(){ log "ERROR: $*" >&2; exit 1; }
need(){ command -v "$1" >/dev/null 2>&1 || fail "Need $1"; }

# Try to load existing .env if present
[ -f .env ] && set -o allexport && . ./.env && set +o allexport || true

# Fill from env if not passed (database)
DB_NAME=${DB_NAME:-${DB_NAME_ENV:-${DB_NAME_DEFAULT:-wpdb}}}
DB_USER=${DB_USER:-${DB_USER_ENV:-wpuser}}
DB_PASS=${DB_PASS:-${DB_PASSWORD:-wpsecret}}
DB_HOST=${DB_HOST:-${WORDPRESS_DB_HOST:-${DB_HOST_ENV:-localhost}}}
DB_PORT_ENV=${DB_PORT:-}
if [ -n "$DB_PORT_ENV" ] && ! echo "$DB_HOST" | grep -q ':'; then
  # Append port if host lacks one
  DB_HOST="${DB_HOST}:${DB_PORT_ENV}"
fi
PREFIX=${PREFIX:-${TABLE_PREFIX:-wp_}}

# Admin + site fallbacks (multiple possible env names for convenience)
URL=${URL:-${CODEX_SITE_URL:-${WP_SITE_URL:-${SITE_URL:-}}}}
TITLE=${TITLE:-${CODEX_SITE_TITLE:-${WP_SITE_TITLE:-WordPress Site}}}
ADMIN_USER=${ADMIN_USER:-${CODEX_ADMIN_USER:-${WP_ADMIN_USER:-}}}
ADMIN_PASS=${ADMIN_PASS:-${CODEX_ADMIN_PASS:-${WP_ADMIN_PASS:-}}}
ADMIN_EMAIL=${ADMIN_EMAIL:-${CODEX_ADMIN_EMAIL:-${WP_ADMIN_EMAIL:-}}}

[ -n "$DB_NAME" ] || fail "DB name required"
[ -n "$DB_USER" ] || fail "DB user required"
[ -n "$DB_PASS" ] || fail "DB password required"
[ -n "$URL" ] || log "(info) --url not supplied; will skip installation unless already installed"

need php
need curl
need awk

# Root detection (Codex / CI containers often run as root); safely append --allow-root
IS_ROOT=0
if [ "$(id -u)" = "0" ]; then
  IS_ROOT=1
  log "Running as root; wp-cli will use --allow-root"
fi

# Acquire wp-cli if missing
if ! command -v wp >/dev/null 2>&1; then
  if [ ! -f wp-cli.phar ]; then
    log "Fetching wp-cli.phar"
    curl -fsSL https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar -o wp-cli.phar
  fi
  chmod +x wp-cli.phar
  WP="php wp-cli.phar"
else
  WP="wp"
fi

# Append --allow-root automatically if root
[ $IS_ROOT -eq 1 ] && WP="$WP --allow-root"

# Generate .env if absent
if [ ! -f .env ]; then
  log "Creating .env"
  cat > .env <<EOF
DB_NAME=$DB_NAME
DB_USER=$DB_USER
DB_PASSWORD=$DB_PASS
DB_HOST=$DB_HOST
TABLE_PREFIX=$PREFIX
SITE_URL=$URL
EOF
else
  log ".env exists (skipping create)"
fi

# Download core if missing
if [ ! -f wp-settings.php ]; then
  log "Downloading WordPress core"
  $WP core download --quiet
else
  log "Core present"
fi

# Create wp-config if missing or forced
if [ ! -f wp-config.php ] || [ $FORCE -eq 1 ]; then
  [ $FORCE -eq 1 ] && log "--force: regenerating wp-config.php"
  rm -f wp-config.php
  log "Generating wp-config.php"
  $WP config create \
    --dbname="$DB_NAME" --dbuser="$DB_USER" --dbpass="$DB_PASS" --dbhost="$DB_HOST" \
    --dbprefix="$PREFIX" --skip-check --quiet
  log "Injecting .env loader + salts"
  SALTS=$(curl -fsSL https://api.wordpress.org/secret-key/1.1/salt/ || true)
  awk 'NR==1{print "<?php"} {print}' wp-config.php > wp-config.tmp && mv wp-config.tmp wp-config.php
  printf '\n// Load .env if present\nif (file_exists(__DIR__.'"'/.env'"')) { foreach (parse_ini_file(__DIR__.'"'/.env'"') as $k=>$v){ if(!getenv($k)) putenv("$k=$v"); } }\n' >> wp-config.php
  printf '// Salts (regenerated)\n%s\n' "$SALTS" >> wp-config.php
fi

# Install if not installed and URL/admin provided
CORE_INSTALLED=1
if ! $WP core is-installed >/dev/null 2>&1; then
  CORE_INSTALLED=0
  if [ -n "$URL" ] && [ -n "$ADMIN_USER" ] && [ -n "$ADMIN_PASS" ] && [ -n "$ADMIN_EMAIL" ]; then
    log "Running core install (url=$URL user=$ADMIN_USER)"
    if $WP core install --url="$URL" --title="$TITLE" \
      --admin_user="$ADMIN_USER" --admin_password="$ADMIN_PASS" --admin_email="$ADMIN_EMAIL"; then
      CORE_INSTALLED=1
    else
      log "Install command failed" >&2
      # If DB host looks like localhost:PORT and fails, try common docker service host fallback
      if echo "$DB_HOST" | grep -q 'localhost:'; then
        ALT_HOST="db:3306"
        log "Retrying install with DB_HOST fallback $ALT_HOST"
        $WP config set DB_HOST "$ALT_HOST" --type=constant --quiet || true
        if $WP core install --url="$URL" --title="$TITLE" --admin_user="$ADMIN_USER" --admin_password="$ADMIN_PASS" --admin_email="$ADMIN_EMAIL"; then
          CORE_INSTALLED=1
        fi
      fi
    fi
  else
    log "Skipping install (missing URL or admin creds)"
  fi
else
  log "WordPress already installed"
fi

# Ensure search & home URLs match if URL provided AND core installed
if [ -n "$URL" ] && [ $CORE_INSTALLED -eq 1 ]; then
  CURR=$($WP option get siteurl 2>/dev/null || echo '')
  if [ "$CURR" != "$URL" ]; then
    log "Updating siteurl/home to $URL"
    $WP option update siteurl "$URL" >/dev/null || log "(warn) siteurl update failed"
    $WP option update home "$URL" >/dev/null || log "(warn) home update failed"
  fi
fi

# Plugins only if core installed
if [ $CORE_INSTALLED -eq 1 ]; then
  # Install essential plugins
  for p in "${PLUGINS_CORE[@]}"; do
    $WP plugin is-installed "$p" 2>/dev/null || { log "Installing plugin $p"; $WP plugin install "$p" --activate --quiet || true; }
  done
  # Optional plugins (ignore failures)
  for p in "${PLUGINS_OPTIONAL[@]}"; do
    $WP plugin is-installed "$p" 2>/dev/null || $WP plugin install "$p" --activate --quiet || true
  done
else
  log "Skipping plugin installs (core not installed)"
fi

if [ $CORE_INSTALLED -eq 1 ]; then
  log "Done. Site ready at: ${URL:-<unknown URL>}"
else
  log "Done (partial). Core not installed; re-run with URL + admin creds or set env vars CODEX_SITE_URL, CODEX_ADMIN_USER, CODEX_ADMIN_PASS, CODEX_ADMIN_EMAIL"
fi
log "Re-run with --force after adjusting .env to regenerate config if needed." 