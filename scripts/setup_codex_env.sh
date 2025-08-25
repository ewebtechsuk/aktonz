#!/usr/bin/env bash
# setup_codex_env.sh
# Purpose: Provision a reproducible local WordPress ("Codex") development environment for this repository.
# Assumptions: Docker + Docker Compose v2 installed. Repository already contains a full WP codebase.
# Safe to re-run (idempotent for core steps).

set -euo pipefail

PROJECT_NAME=${PROJECT_NAME:-aktonz}
COMPOSE_FILE=${COMPOSE_FILE:-docker-compose.yml}
ENV_FILE=.env
EXAMPLE_ENV=.env.example

log() { printf '\n[setup] %s\n' "$*"; }
fail() { printf '\n[setup][error] %s\n' "$*" >&2; exit 1; }

need() { command -v "$1" >/dev/null 2>&1 || fail "Required command '$1' not found"; }

need docker; need awk; need sed; need tr; need openssl || true

if ! docker info >/dev/null 2>&1; then
  fail "Docker daemon not reachable. Start Docker Desktop or service."
fi

# Create example env if absent
if [ ! -f "$EXAMPLE_ENV" ]; then
  cat > "$EXAMPLE_ENV" <<'EOF'
# Copy to .env and adjust as needed
PROJECT_NAME=aktonz
WP_VERSION=latest
DB_IMAGE=mariadb:10.6
DB_NAME=wpdb
DB_USER=wpuser
DB_PASSWORD=wpsecret
DB_ROOT_PASSWORD=rootpw
DB_PORT=3307
SITE_HOST=localhost
SITE_HTTP_PORT=8080
SITE_URL=http://localhost:8080
TABLE_PREFIX=wp_
# Optional: set to 1 to generate and inject salts on first run
GENERATE_SALTS=1
# Enable Xdebug (slow) - set to 1 to mount a basic xdebug.ini
ENABLE_XDEBUG=0
# Comma-separated plugin slugs to auto-disable (rename) after first up
AUTO_DISABLE_PLUGINS=litespeed-cache
EOF
  log "Created $EXAMPLE_ENV"
fi

# Create .env if absent
if [ ! -f "$ENV_FILE" ]; then
  cp "$EXAMPLE_ENV" "$ENV_FILE"
  log "Created $ENV_FILE (edit values then rerun if desired)"
fi

# Shell export vars from .env
set -a; # auto-export
# shellcheck disable=SC2046
[ -f "$ENV_FILE" ] && . "$ENV_FILE"
set +a

DOCKER_COMPOSE_YML_CONTENT='version: "3.9"
services:
  db:
    image: ${DB_IMAGE}
    container_name: ${PROJECT_NAME}_db
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: ${DB_NAME}
      MYSQL_USER: ${DB_USER}
      MYSQL_PASSWORD: ${DB_PASSWORD}
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD}
    ports:
      - "${DB_PORT}:3306"
    volumes:
      - db_data:/var/lib/mysql
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      interval: 10s
      timeout: 5s
      retries: 6
  wordpress:
    image: wordpress:${WP_VERSION}
    container_name: ${PROJECT_NAME}_wp
    depends_on:
      db:
        condition: service_healthy
    restart: unless-stopped
    environment:
      WORDPRESS_DB_HOST: db:3306
      WORDPRESS_DB_NAME: ${DB_NAME}
      WORDPRESS_DB_USER: ${DB_USER}
      WORDPRESS_DB_PASSWORD: ${DB_PASSWORD}
      WORDPRESS_CONFIG_EXTRA: |
        define('TABLE_PREFIX', '${TABLE_PREFIX}');
    ports:
      - "${SITE_HTTP_PORT}:80"
    volumes:
      - ./:/var/www/html
      - ./docker/php/uploads.ini:/usr/local/etc/php/conf.d/uploads.ini:ro
      - ./docker/php/xdebug.ini:/usr/local/etc/php/conf.d/xdebug.ini:ro
    extra_hosts:
      - "${SITE_HOST}:127.0.0.1"
  phpmyadmin:
    image: phpmyadmin:5
    container_name: ${PROJECT_NAME}_pma
    restart: unless-stopped
    environment:
      PMA_HOST: db
    ports:
      - "${PHPMYADMIN_PORT:-8081}:80"
    depends_on:
      db:
        condition: service_healthy
volumes:
  db_data:
'

if [ ! -f docker-compose.yml ]; then
  printf '%s' "$DOCKER_COMPOSE_YML_CONTENT" > docker-compose.yml
  log "Created docker-compose.yml"
fi

# PHP config snippets
mkdir -p docker/php
if [ ! -f docker/php/uploads.ini ]; then
  cat > docker/php/uploads.ini <<'EOF'
file_uploads=On
memory_limit=256M
upload_max_filesize=64M
post_max_size=64M
max_execution_time=120
EOF
  log "Added docker/php/uploads.ini"
fi

if [ "${ENABLE_XDEBUG}" != "1" ]; then
  # Create empty file so volume mount succeeds
  : > docker/php/xdebug.ini
else
  cat > docker/php/xdebug.ini <<'EOF'
zend_extension=xdebug.so
xdebug.mode=debug,develop
xdebug.start_with_request=yes
xdebug.client_host=host.docker.internal
xdebug.client_port=9003
xdebug.log_level=0
EOF
  log "Enabled Xdebug (docker/php/xdebug.ini)"
fi

# Generate salts (inject into wp-config-local.php or create if absent)
if [ "${GENERATE_SALTS:-0}" = "1" ]; then
  SALT_FILE=wp-config-local.php
  if ! grep -q 'AUTH_KEY' "$SALT_FILE" 2>/dev/null; then
    log "Generating WordPress salts in $SALT_FILE"
    # Use WP API if curl; fallback to openssl random
    if command -v curl >/dev/null 2>&1; then
      curl -fsSL https://api.wordpress.org/secret-key/1.1/salt/ > .salts.tmp || true
    fi
    if [ ! -s .salts.tmp ]; then
      log "Salt API failed; using local generation"
      : > .salts.tmp
      for k in AUTH_KEY SECURE_AUTH_KEY LOGGED_IN_KEY NONCE_KEY AUTH_SALT SECURE_AUTH_SALT LOGGED_IN_SALT NONCE_SALT; do
        RAND=$(openssl rand -base64 48 2>/dev/null || head -c 48 /dev/urandom | base64)
        printf "define('%s','%s');\n" "$k" "$RAND" >> .salts.tmp
      done
    fi
    cat > "$SALT_FILE" <<'EOF'
<?php
// Local-only overrides (ignored in production deploy)
EOF
    cat .salts.tmp >> "$SALT_FILE"
    printf "\n// Force disable caching for local troubleshooting\nif (!defined('WP_CACHE')) { define('WP_CACHE', false); }\n" >> "$SALT_FILE"
    rm -f .salts.tmp
  else
    log "Salts already present; skipping generation"
  fi
fi

log "Bringing up containers (detached)"
docker compose -p "$PROJECT_NAME" up -d --build

docker compose -p "$PROJECT_NAME" ps

# Wait for WP to be reachable
log "Waiting for WordPress HTTP (max 60s)"
ATTEMPTS=0
until curl -fsS "${SITE_URL}/" >/dev/null 2>&1 || [ $ATTEMPTS -ge 30 ]; do
  sleep 2; ATTEMPTS=$((ATTEMPTS+1)); printf '.'
done
printf '\n'
if curl -fsS "${SITE_URL}/" >/dev/null 2>&1; then
  log "Site reachable at ${SITE_URL}"
else
  log "Site not yet reachable; continue manually if needed"
fi

# Install WP if not installed (using wp-cli in a temp container)
if ! docker compose -p "$PROJECT_NAME" exec -T wordpress wp core is-installed >/dev/null 2>&1; then
  log "Running initial wp core install"
  docker compose -p "$PROJECT_NAME" exec -T wordpress wp core install \
    --url="${SITE_URL}" \
    --title="${PROJECT_NAME^} Local" \
    --admin_user=admin \
    --admin_password=admin \
    --admin_email=admin@example.test || true
else
  log "WP already installed"
fi

# Auto-disable specified plugins (rename directory) if present
if [ -n "${AUTO_DISABLE_PLUGINS:-}" ]; then
  IFS=',' read -r -a PLUGS <<<"$AUTO_DISABLE_PLUGINS"
  for p in "${PLUGS[@]}"; do
    p=$(echo "$p" | xargs)
    [ -z "$p" ] && continue
    SRC="wp-content/plugins/$p"
    DST="${SRC}.disabled"
    if [ -d "$SRC" ] && [ ! -d "$DST" ]; then
      log "Disabling plugin $p (rename -> .disabled)"
      mv "$SRC" "$DST" || log "Rename failed for $p (permissions?)"
    fi
  done
fi

log "Done. Useful commands:\n  docker compose -p $PROJECT_NAME logs -f wordpress\n  docker compose -p $PROJECT_NAME exec wordpress wp plugin list\n  docker compose -p $PROJECT_NAME down"
