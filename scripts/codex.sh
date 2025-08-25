#!/usr/bin/env bash
# codex.sh - Unified setup & maintenance helper for local "Codex" WordPress environment
# Usage:
#   ./scripts/codex.sh setup              # One-time (or idempotent) environment provisioning
#   ./scripts/codex.sh help               # List all commands
#   ./scripts/codex.sh <command> [...]    # Maintenance / wp-cli helpers (after setup)
#
# This combines functionality from setup_codex_env.sh and maintain_codex_env.sh.

set -euo pipefail

SELF_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)
PROJECT_ROOT=$(cd "$SELF_DIR/.." && pwd)
cd "$PROJECT_ROOT"

ENV_FILE=.env
EXAMPLE_ENV=.env.example
DEFAULT_PROJECT=aktonz

log(){ printf '[codex] %s\n' "$*"; }
err(){ printf '[codex][error] %s\n' "$*" >&2; }
fail(){ err "$*"; exit 1; }
need(){ command -v "$1" >/dev/null 2>&1 || fail "Required command '$1' not found"; }

COMMAND=${1:-help}; shift || true

# Ensure docker for all commands except pure help generation if not needed.
if [[ "$COMMAND" != "help" && "$COMMAND" != "print-env-template" ]]; then
  need docker
  if ! docker info >/dev/null 2>&1; then fail "Docker daemon not reachable"; fi
fi

create_env_templates(){
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
GENERATE_SALTS=1
ENABLE_XDEBUG=0
AUTO_DISABLE_PLUGINS=litespeed-cache
PHPMYADMIN_PORT=8081
EOF
    log "Created $EXAMPLE_ENV"
  fi
  if [ ! -f "$ENV_FILE" ]; then
    cp "$EXAMPLE_ENV" "$ENV_FILE"
    log "Created $ENV_FILE (edit values then re-run setup)"
  fi
}

load_env(){
  set -a; [ -f "$ENV_FILE" ] && . "$ENV_FILE"; set +a
  PROJECT_NAME=${PROJECT_NAME:-$DEFAULT_PROJECT}
  COMPOSE_PROJECT="-p $PROJECT_NAME"
}

compose(){ docker compose $COMPOSE_PROJECT "$@"; }
# Always allow root inside official wordpress container & suppress warning
wp(){ compose exec -T wordpress wp --allow-root "$@"; }

ensure_compose_file(){
  # Regenerate if:
  #  - Missing
  #  - Previously created with literal \n sequences (bad YAML)
  #  - Uses deprecated WORDPRESS_CONFIG_EXTRA injection (we now prefer WORDPRESS_TABLE_PREFIX)
  if [ ! -f docker-compose.yml ] || grep -q '\\nservices:' docker-compose.yml || grep -q 'WORDPRESS_CONFIG_EXTRA' docker-compose.yml; then
    cat > docker-compose.yml <<EOF
version: "3.9"
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
      WORDPRESS_TABLE_PREFIX: ${TABLE_PREFIX}
      WP_CLI_ALLOW_ROOT: 1
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
EOF
    log "(Re)generated docker-compose.yml"
  fi
}

ensure_php_configs(){
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
  if [ "${ENABLE_XDEBUG:-0}" != "1" ]; then
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
}

maybe_generate_salts(){
  if [ "${GENERATE_SALTS:-0}" = "1" ]; then
    local SALT_FILE=wp-config-local.php
    if ! grep -q 'AUTH_KEY' "$SALT_FILE" 2>/dev/null; then
      log "Generating WordPress salts in $SALT_FILE"
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
}

initial_wp_install(){
  if ! wp core is-installed >/dev/null 2>&1; then
    log "Running initial wp core install"
    if ! wp core install \
      --url="${SITE_URL}" \
      --title="${PROJECT_NAME^} Local" \
      --admin_user=admin \
      --admin_password=admin \
      --admin_email=admin@example.test; then
        err "Install failed, retrying once..."; sleep 5;
        wp core install --url="${SITE_URL}" --title="${PROJECT_NAME^} Local" --admin_user=admin --admin_password=admin --admin_email=admin@example.test || err "Second install attempt failed"
    fi
  else
    log "WP already installed"
  fi
}

auto_disable_plugins(){
  if [ -n "${AUTO_DISABLE_PLUGINS:-}" ]; then
    IFS=',' read -r -a PLUGS <<<"$AUTO_DISABLE_PLUGINS"
    for p in "${PLUGS[@]}"; do
      p=$(echo "$p" | xargs)
      [ -z "$p" ] && continue
      local SRC="wp-content/plugins/$p"
      local DST="${SRC}.disabled"
      if [ -d "$SRC" ] && [ ! -d "$DST" ]; then
        log "Disabling plugin $p (rename -> .disabled)"
        mv "$SRC" "$DST" || log "Rename failed for $p (permissions?)"
      fi
    done
  fi
}

wait_for_http(){
  log "Waiting for WordPress HTTP (max 60s)"
  local ATTEMPTS=0 STATUS
  while [ $ATTEMPTS -lt 30 ]; do
    STATUS=$(curl -s -o /dev/null -w '%{http_code}' "${SITE_URL}/" || true)
    if [[ "$STATUS" =~ ^2[0-9][0-9]$ ]]; then
      log "Site reachable (${STATUS}) at ${SITE_URL}"
      return 0
    elif [[ "$STATUS" == "500" ]]; then
      err "Received 500 early (attempt $ATTEMPTS). Will continue waiting in case of transient startup."
    fi
    sleep 2; ATTEMPTS=$((ATTEMPTS+1));
  done
  if [ -n "$STATUS" ]; then
    err "Site not healthy after wait (last status: $STATUS)"
  else
    err "Site not reachable after wait"
  fi
  return 1
}

perform_setup(){
  need awk; need sed; need tr; need openssl || true; need curl || true
  create_env_templates
  load_env
  ensure_compose_file
  ensure_php_configs
  maybe_generate_salts
  log "Bringing up containers (detached)"
  docker compose -p "$PROJECT_NAME" up -d --build
  docker compose -p "$PROJECT_NAME" ps
  ensure_wp_cli
  initial_wp_install
  wait_for_http || true
  auto_disable_plugins
  summary_output
  log "Setup complete. Try: ./scripts/codex.sh status"
}

# Ensure wp-cli present inside wordpress container (some base images may exclude it)
ensure_wp_cli(){
  if ! docker compose -p "$PROJECT_NAME" exec -T wordpress bash -lc 'command -v wp' >/dev/null 2>&1; then
    log "wp-cli missing; installing"
    docker compose -p "$PROJECT_NAME" exec -T wordpress bash -lc 'curl -fsSL https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar -o /usr/local/bin/wp && chmod +x /usr/local/bin/wp' || err "Failed to install wp-cli"
  else
    log "wp-cli present"
  fi
}

summary_output(){
  log "----- SUMMARY -----"
  log "Site URL: ${SITE_URL}"
  if wp core is-installed >/dev/null 2>&1; then
    log "Admin: ${SITE_URL}/wp-admin/ (user: admin / pass: admin)"
  else
    err "WordPress not installed (visit ${SITE_URL}/wp-admin/install.php)"
  fi
  log "phpMyAdmin: http://localhost:${PHPMYADMIN_PORT:-8081}"
  log "WP CLI: ./scripts/codex.sh wp <cmd>"
  [ -n "${AUTO_DISABLE_PLUGINS:-}" ] && log "Auto-disable targets: ${AUTO_DISABLE_PLUGINS}" || true
  log "DB backup: ./scripts/codex.sh backup-db"
  log "--------------------"
}

usage(){ cat <<'EOF'
Codex unified script
Setup:
  setup                        Provision / update local environment
  print-env-template           Output example .env to stdout
  doctor                      Run environment diagnostics (Docker, ports, perms)
  fix-wp-cli                  Reinstall or ensure wp-cli inside container
Common:
  help                         Show this help
  status                       Container status + site & WP version
  logs [svc]                   Tail logs (default wordpress)
  shell [svc]                  Shell into container (default wordpress)
  wp <args...>                 Run wp-cli command
Database:
  backup-db [out.sql.gz]       Dump DB (default backups/DATE.sql.gz)
  restore-db <file.sql[.gz]>   Restore DB dump
  db-size                      Show table sizes
  optimize-db                  Optimize all tables
  search-replace <old> <new>   Safe search/replace (adds --skip-columns=guid)
  transients-clear             Delete all transients
Caching / salts:
  cache-flush                  Flush object & rewrite cache
  salts-regenerate             Regenerate local salts file
Plugins:
  list-plugins                 List plugins
  enable-plugin <slug>         Activate plugin
  disable-plugin <slug>        Deactivate plugin
  rename-disable <slug>        Rename plugin dir -> .disabled
  rename-enable <slug>         Restore renamed plugin
Updates:
  update                       Core + plugins + themes + db
  update-core                  Core only
  update-plugins               Plugins only
  update-themes                Themes only
Lifecycle:
  up                           Start containers
  down                         Stop containers
  recreate                     Down -v & up (destructive)
  hard-reset                   Down -v + remove salts + fresh up
  prune                        Prune dangling docker images/volumes (global)
Info / Health:
  health                       Basic health checks
  info                         Show key environment vars
EOF
}

# Load env for non-setup if present
if [[ "$COMMAND" != "setup" && "$COMMAND" != "print-env-template" && -f "$ENV_FILE" ]]; then
  load_env
fi

case "$COMMAND" in
  setup) perform_setup ;;
  print-env-template) create_env_templates; cat "$EXAMPLE_ENV" ;;
  doctor)
    load_env || true
    log "Running diagnostics"
    if command -v docker >/dev/null 2>&1; then
      if docker info >/dev/null 2>&1; then
        log "Docker daemon: OK"
      else
        err "Docker installed but daemon not reachable"
      fi
    else
      err "Docker command not found (install Docker Desktop / Engine)"
    fi
    # Port checks
    SITE_HTTP_PORT=${SITE_HTTP_PORT:-8080}
    DB_PORT=${DB_PORT:-3307}
    check_port(){
      local p=$1 label=$2
      if command -v ss >/dev/null 2>&1 && ss -ltn | awk '{print $4}' | grep -E "[:.]$p$" >/dev/null 2>&1; then
        err "Port $p ($label) appears in use"
      elif command -v lsof >/dev/null 2>&1 && lsof -iTCP -sTCP:LISTEN -Pn 2>/dev/null | grep -q ":$p"; then
        err "Port $p ($label) in use (lsof)"
      else
        log "Port $p ($label): free"
      fi
    }
    check_port "$SITE_HTTP_PORT" http
    check_port "$DB_PORT" db
    # File permissions
    for f in .env docker-compose.yml; do
      if [ -f "$f" ]; then
        if [ -w "$f" ]; then log "Writable: $f"; else err "Not writable: $f"; fi
      fi
    done
    # Containers
    if command -v docker >/dev/null 2>&1 && docker ps --format '{{.Names}}' | grep -q "${PROJECT_NAME}_wp"; then
      log "Container ${PROJECT_NAME}_wp present"
    else
      log "WordPress container not running yet"
    fi
    log "Diagnostics complete"
    ;;
  fix-wp-cli) ensure_wp_cli ;;
  help|-h|--help) usage ;;
  status) compose ps; if compose exec -T wordpress wp core version >/dev/null 2>&1; then log "WP Version: $(wp core version)"; log "Site URL: $(wp option get siteurl)"; else log "WordPress not yet installed"; fi ;;
  logs) svc=${1:-wordpress}; shift || true; compose logs -f "$svc" ;;
  shell) svc=${1:-wordpress}; shift || true; compose exec "$svc" bash || compose exec "$svc" sh ;;
  wp) wp "$@" || true ;;
  backup-db) mkdir -p backups; out=${1:-backups/$(date +%Y%m%d-%H%M%S).sql.gz}; log "Dumping DB -> $out"; compose exec -T db mysqldump -u"$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" | gzip -c > "$out"; log "Done ($out)" ;;
  restore-db) file=${1:-}; [ -f "$file" ] || fail "File not found: $file"; log "Restoring $file"; if [[ $file == *.gz ]]; then gunzip -c "$file" | compose exec -T db mysql -u"$DB_USER" -p"$DB_PASSWORD" "$DB_NAME"; else compose exec -T db mysql -u"$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" < "$file"; fi; log "Restore complete" ;;
  update) wp core update || true; wp plugin update --all || true; wp theme update --all || true; wp core update-db || true ;;
  update-core) wp core update || true; wp core update-db || true ;;
  update-plugins) wp plugin update --all || true ;;
  update-themes) wp theme update --all || true ;;
  list-plugins) wp plugin list --format=table ;;
  enable-plugin) slug=${1:?slug}; wp plugin activate "$slug" ;;
  disable-plugin) slug=${1:?slug}; wp plugin deactivate "$slug" || true ;;
  rename-disable) slug=${1:?slug}; src=wp-content/plugins/$slug; dst=${src}.disabled; [ -d "$src" ] || fail "Plugin dir not found: $src"; [ -d "$dst" ] && fail "Already disabled: $dst"; mv "$src" "$dst" && log "Renamed $src -> $dst" ;;
  rename-enable) slug=${1:?slug}; dst=wp-content/plugins/$slug; src=${dst}.disabled; [ -d "$src" ] || fail "Disabled dir not found: $src"; mv "$src" "$dst" && log "Restored $dst" && wp plugin activate "$slug" || true ;;
  transients-clear) wp transient delete --all || true; wp transient delete-expired || true ;;
  cache-flush) wp cache flush || true; wp rewrite flush --hard || true ;;
  search-replace) old=${1:?old}; new=${2:?new}; shift 2; wp search-replace "$old" "$new" "$@" --skip-columns=guid || true ;;
  salts-regenerate) file=wp-config-local.php; if command -v curl >/dev/null 2>&1 && curl -fsSL https://api.wordpress.org/secret-key/1.1/salt/ > .salts.tmp; then :; else log "Salt API failed; generating locally"; : > .salts.tmp; for k in AUTH_KEY SECURE_AUTH_KEY LOGGED_IN_KEY NONCE_KEY AUTH_SALT SECURE_AUTH_SALT LOGGED_IN_SALT NONCE_SALT; do printf "define('%s','%s');\n" "$k" "$(openssl rand -base64 48 2>/dev/null || head -c 48 /dev/urandom | base64)" >> .salts.tmp; done; fi; { echo '<?php'; echo '// Regenerated local salts'; cat .salts.tmp; echo "if (!defined('WP_CACHE')) define('WP_CACHE', false);"; } > "$file"; rm -f .salts.tmp; log "Salts written to $file" ;;
  db-size) compose exec -T db bash -c "mysql -u$DB_USER -p$DB_PASSWORD -e 'SELECT table_name AS \"Table\", ROUND((data_length+index_length)/1024/1024,2) AS SizeMB FROM information_schema.TABLES WHERE table_schema=\"$DB_NAME\" ORDER BY (data_length+index_length) DESC;'" || true ;;
  optimize-db) for t in $(wp db tables); do wp db query "OPTIMIZE TABLE $t" || true; done ;;
  health) log "HTTP check: $SITE_URL"; if curl -fsS -o /dev/null -w '%{http_code}\n' "$SITE_URL" 2>/dev/null | grep -q '^2'; then log "HTTP OK"; else err "HTTP not OK"; fi; if wp core is-installed >/dev/null 2>&1; then log "Core installed"; else err "Core NOT installed"; fi; if wp core check-update | grep -q 'Latest'; then log "Core up-to-date"; else log "Core updates available"; fi; log "Plugin updates: $(wp plugin list --update=available --field=name | wc -l | tr -d ' ')" ;;
  recreate) read -r -p "Destructive: drop containers & volumes. Continue? (y/N) " ans; [[ $ans == y* ]] || exit 0; compose down -v; compose up -d --build ;;
  down) compose down ;;
  up) compose up -d ;;
  prune) read -r -p "Prune dangling images & unused volumes (global)? (y/N) " ans; [[ $ans == y* ]] || exit 0; docker image prune -f; docker volume prune -f ;;
  hard-reset) read -r -p "Hard reset (down -v + remove salts + fresh up)? (y/N) " ans; [[ $ans == y* ]] || exit 0; compose down -v || true; rm -f wp-config-local.php; compose up -d --build ;;
  info) log "Project: $PROJECT_NAME"; log "Site URL: ${SITE_URL}"; log "DB: ${DB_NAME}@db as ${DB_USER}"; log "HTTP Port: ${SITE_HTTP_PORT}"; log "phpMyAdmin: http://localhost:${PHPMYADMIN_PORT:-8081}" ;;
  *) err "Unknown command: $COMMAND"; usage; exit 1 ;;
esac
