#!/usr/bin/env bash
# codex.sh - Unified setup & maintenance helper for local "Codex" WordPress environment
# Usage:
#   ./scripts/codex.sh setup              # One-time (or idempotent) environment provisioning
#   ./scripts/codex.sh help               # List all commands
#   ./scripts/codex.sh <command> [...]    # Maintenance / wp-cli helpers (after setup)
#
# This combines functionality from setup_codex_env.sh and maintain_codex_env.sh.

set -euo pipefail

# Global toggles (can also be set via environment before invoking script)
DRY_RUN=${DRY_RUN:-0}
VERBOSE=${VERBOSE:-0}

# Basic option parsing (flags before command). Example: ./scripts/codex.sh -n -v setup
while [[ $# -gt 0 ]]; do
  case "$1" in
    -n|--dry-run) DRY_RUN=1; shift ;;
    -v|--verbose) VERBOSE=1; shift ;;
    --) shift; break ;;
    -h|--help) COMMAND=help; break ;;
    -*) echo "[codex] Unknown flag: $1" >&2; COMMAND=help; break ;;
    *) break ;;
  esac
done

if [[ ${VERBOSE} -eq 1 ]]; then set -x; fi

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

# Export variables from .env into environment (if present)
load_env(){
  if [ -f "$ENV_FILE" ]; then
    set -a
    # shellcheck disable=SC1090
    . "$ENV_FILE"
    set +a
  fi
  PROJECT_NAME=${PROJECT_NAME:-$DEFAULT_PROJECT}
  SITE_URL=${SITE_URL:-http://localhost:${SITE_HTTP_PORT:-8080}}
}

COMMAND=${COMMAND:-${1:-help}}; shift || true

# Create example and .env if missing
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

# Decide if the command needs Docker
requires_full_docker(){
  case "$COMMAND" in
    setup|up|down|recreate|hard-reset|backup-db|restore-db|update|update-core|update-plugins|update-themes|logs|shell|compose|db-size|optimize-db|health|prune) return 0 ;; # true
    *) return 1 ;; # false
  esac
}

docker_available(){ command -v docker >/dev/null 2>&1 && docker info >/dev/null 2>&1; }

if requires_full_docker && ! docker_available; then
  if [[ "$COMMAND" == "setup" ]]; then
    log "Docker unavailable; falling back to lite-setup (no containers)."
    create_env_templates
    COMMAND=lite-setup
  else
    err "Docker required for '$COMMAND' but not available. Try: lite-setup, net-test, doctor, status-json, prefetch-offline"
    exit 1
# Close both the inner and outer docker availability conditionals
  fi
fi
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
  if [ "${DRY_RUN}" = "1" ]; then
    log "[dry-run] Skipping docker compose up"
  else
    docker compose -p "$PROJECT_NAME" up -d --build
    docker compose -p "$PROJECT_NAME" ps
  fi
  if [ "${DRY_RUN}" = "1" ]; then
    log "[dry-run] Skipping ensure_wp_cli, WP install, HTTP wait, plugin auto-disable"
  else
    ensure_wp_cli
    initial_wp_install
    wait_for_http || true
    auto_disable_plugins
  fi
  summary_output
  log "Setup complete. Try: ./scripts/codex.sh status"
}

# Ensure wp-cli present inside wordpress container (some base images may exclude it)
ensure_wp_cli(){
  if ! docker compose -p "$PROJECT_NAME" exec -T wordpress bash -lc 'command -v wp' >/dev/null 2>&1; then
    log "wp-cli missing; installing"
    if docker compose -p "$PROJECT_NAME" exec -T wordpress bash -lc 'curl -fsSL https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar -o /usr/local/bin/wp && chmod +x /usr/local/bin/wp'; then
      :
    else
      err "Primary download failed; attempting offline-cache fallback"
      if [ -f offline-cache/wp-cli.phar ]; then
        docker cp offline-cache/wp-cli.phar "${PROJECT_NAME}_wp:/usr/local/bin/wp" && \
          docker compose -p "$PROJECT_NAME" exec -T wordpress chmod +x /usr/local/bin/wp || \
          err "Fallback copy of wp-cli failed"
      else
        err "No offline-cache/wp-cli.phar available for fallback"
      fi
    fi
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
Global flags (place before command):
  -n, --dry-run               Show actions without executing docker/wp changes
  -v, --verbose               Verbose / xtrace execution

Setup:
  setup                        Provision / update local environment
  print-env-template           Output example .env to stdout
  doctor                      Run environment diagnostics (Docker, ports, perms)
  net-test                    Network reachability (DNS + HTTPS) tests
  lite-setup                  Minimal no-docker WordPress (wp-lite/) using wp-cli (SQLite-friendly)
  prefetch-offline            Download/cached artifacts (core tarball, wp-cli, plugin/theme zips, docker images) for offline use
  lite-sqlite                 Add SQLite DB drop-in (db.php) to lite install (no MySQL needed)
  lite-install                Run full core install in lite mode using SQLite (after lite-sqlite)
  fix-wp-cli                  Reinstall or ensure wp-cli inside container
Common:
  help                         Show this help
  status                       Container status + site & WP version
  logs [svc]                   Tail logs (default wordpress)
  shell [svc]                  Shell into container (default wordpress)
  compose <args...>            Pass-through to docker compose (project scoped)
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
  status-json                  Emit environment + WP info as JSON (tooling)
  self-test                   Run net-test, doctor, status-json (aggregated report)
  verify-offline              Verify checksums of cached artifacts
  serve-lite                  Start PHP built-in server for wp-lite (if docker unavailable)
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
      log "[warn] Docker command not found (skipping container diagnostics)"
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
    # Network quick test (non-fatal)
    if command -v getent >/dev/null 2>&1 && getent hosts wordpress.org >/dev/null 2>&1; then
      log "DNS wordpress.org: OK"
    else
      err "DNS lookup failed for wordpress.org"
    fi
    if command -v curl >/dev/null 2>&1; then
      if curl -fsSL -o /dev/null https://wordpress.org; then
        log "HTTPS wordpress.org: OK"
      else
        err "HTTPS request to wordpress.org failed (possibly blocked)"
      fi
    fi
    log "Diagnostics complete"
    ;;
  net-test)
    load_env || true
    log "Network test start"
    if command -v getent >/dev/null 2>&1 && getent hosts wordpress.org >/dev/null 2>&1; then
      log "DNS wordpress.org: OK"
    else
      err "DNS wordpress.org: FAIL"
    fi
    if command -v curl >/dev/null 2>&1; then
      if curl -fsSL -o /dev/null https://wordpress.org; then
        log "HTTPS wordpress.org: OK"
      else
        err "HTTPS wordpress.org: FAIL"
      fi
    else
      err "curl not installed"
    fi
    log "Network test end"
    ;;
  fix-wp-cli) ensure_wp_cli ;;
  help|-h|--help) usage ;;
  status) compose ps; if compose exec -T wordpress wp core version >/dev/null 2>&1; then log "WP Version: $(wp core version)"; log "Site URL: $(wp option get siteurl)"; else log "WordPress not yet installed"; fi ;;
  status-json)
    MODE="lite"
    if command -v docker >/dev/null 2>&1 && docker ps --format '{{.Names}}' 2>/dev/null | grep -q "${PROJECT_NAME}_wp"; then
      MODE="docker"
    fi
    SITE="" CORE_VER="" PLUGIN_UPDATES=0
    if [ "$MODE" = "docker" ]; then
      SITE=$(wp option get siteurl 2>/dev/null || echo "")
      CORE_VER=$(wp core version 2>/dev/null || echo "")
      PLUGIN_UPDATES_RAW=$(wp plugin list --update=available --field=name 2>/dev/null | wc -l 2>/dev/null || echo 0)
      PLUGIN_UPDATES=$(echo "$PLUGIN_UPDATES_RAW" | tr -dc '0-9')
      [ -z "$PLUGIN_UPDATES" ] && PLUGIN_UPDATES=0
    fi
    if [ -z "$CORE_VER" ] && [ -f wp-lite/wp-includes/version.php ]; then
      # Extract only the assignment line (avoid grabbing docblock lines mentioning $wp_version)
  CORE_VER=$(awk -F"'" '/\$wp_version *=/ { for(i=1;i<=NF;i++){ if($i ~ /^[0-9]+(\.[0-9]+)*$/){ printf "%s", $i; exit } } }' wp-lite/wp-includes/version.php 2>/dev/null || true)
      SITE=${SITE:-"http://localhost:8080"}
    fi
  # Normalize core version (strip newlines/CR)
  # Remove any CR/LF characters explicitly (some shells preserve newline from awk output)
  CORE_VER=${CORE_VER//$'\n'/}
  CORE_VER=${CORE_VER//$'\r'/}
    printf '{"project":"%s","mode":"%s","site_url":"%s","core_version":"%s","plugin_updates":%s,"http_port":"%s"}\n' \
      "$PROJECT_NAME" "$MODE" "$SITE" "$CORE_VER" "$PLUGIN_UPDATES" "${SITE_HTTP_PORT:-8080}"
    ;;
  logs) svc=${1:-wordpress}; shift || true; compose logs -f "$svc" ;;
  shell) svc=${1:-wordpress}; shift || true; compose exec "$svc" bash || compose exec "$svc" sh ;;
  compose) compose "$@" ;;
  lite-setup)
    # Minimal non-docker setup for constrained environments
    if command -v docker >/dev/null 2>&1; then
      log "Docker available; prefer: ./scripts/codex.sh setup"
    fi
    if ! command -v php >/dev/null 2>&1; then
      fail "php binary required for lite-setup"
    fi
    if ! command -v curl >/dev/null 2>&1; then
      fail "curl required for lite-setup"
    fi
    mkdir -p wp-lite
    if [ ! -f wp-cli.phar ]; then
      log "Downloading wp-cli.phar"
      curl -fsSL https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar -o wp-cli.phar || fail "Failed to download wp-cli"
    fi
    if [ ! -f wp-lite/wp-load.php ]; then
      if php -m | grep -qi zip; then
        (cd wp-lite && php ../wp-cli.phar core download --skip-content) || fail "Core download failed (wp-cli method)"
      else
        log "PHP ZipArchive missing; using tarball fallback"
        TMPDIR=$(mktemp -d)
        WP_VERSION=${WP_VERSION:-latest}
        if [ "$WP_VERSION" = "latest" ]; then
          WP_TARBALL_URL="https://wordpress.org/latest.tar.gz"
        else
          WP_TARBALL_URL="https://wordpress.org/wordpress-${WP_VERSION}.tar.gz"
        fi
        curl -fsSL "$WP_TARBALL_URL" -o "$TMPDIR/wp.tgz" || fail "Failed to download tarball"
        tar -xzf "$TMPDIR/wp.tgz" -C "$TMPDIR" || fail "Failed to extract tarball"
        mv "$TMPDIR/wordpress"/* wp-lite/ || fail "Failed moving core files"
        rm -rf "$TMPDIR"
      fi
      log "Downloaded WordPress core into wp-lite/"
    else
      log "wp-lite already present"
    fi
    if [ ! -f wp-lite/wp-config.php ]; then
      cp wp-lite/wp-config-sample.php wp-lite/wp-config.php
      # Basic config tweak: set FS_METHOD direct for container-like env
      sed -i "/^define('DB_PASSWORD'/a define('FS_METHOD','direct');" wp-lite/wp-config.php || true
      log "Created wp-lite/wp-config.php (no DB configured)"
      cat <<CONF >> wp-lite/wp-config.php
// Lite setup placeholder (no DB). Consider adding SQLite plugin or editing DB constants.
CONF
    fi
    log "Lite setup complete. Serve with: php -S 127.0.0.1:8080 -t wp-lite";
    ;;
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
  prefetch-offline)
    load_env || true
    mkdir -p offline-cache/plugins offline-cache/themes
    WP_VERSION=${WP_VERSION:-latest}
    CORE_TGZ="offline-cache/wordpress-${WP_VERSION}.tar.gz"
    CHECKSUM_FILE="offline-cache/checksums.txt"

    ensure_checksum_file(){ [ -f "$CHECKSUM_FILE" ] || : > "$CHECKSUM_FILE"; }
    record_checksum(){ sha256sum "$1" 2>/dev/null | awk '{print $1"  "$2}' >> "$CHECKSUM_FILE"; }
    verify_checksum(){
      local f=$1; [ -f "$f" ] || return 1; local name=$(basename "$f");
      if grep -F "  $name" "$CHECKSUM_FILE" >/dev/null 2>&1; then
        local expected=$(grep -F "  $name" "$CHECKSUM_FILE" | tail -1 | awk '{print $1}')
        local actual=$(sha256sum "$f" 2>/dev/null | awk '{print $1}')
        if [ "$expected" != "$actual" ]; then
          err "Checksum mismatch for $name (expected $expected got $actual)"; return 2
        fi
      fi
      return 0
    }

    ensure_checksum_file
    NEED_CORE_DOWNLOAD=0
    if [ ! -f "$CORE_TGZ" ]; then
      NEED_CORE_DOWNLOAD=1
    else
      verify_checksum "$CORE_TGZ" || NEED_CORE_DOWNLOAD=1
    fi
    if [ $NEED_CORE_DOWNLOAD -eq 1 ]; then
      if [ "$WP_VERSION" = "latest" ]; then
        URL_CORE="https://wordpress.org/latest.tar.gz"
      else
        URL_CORE="https://wordpress.org/wordpress-${WP_VERSION}.tar.gz"
      fi
      log "Downloading core tarball -> $CORE_TGZ"
      curl -fsSL "$URL_CORE" -o "$CORE_TGZ" || fail "Failed core download"
      # Refresh checksum (remove old entry then add new)
      sed -i "/$(basename "$CORE_TGZ")$/d" "$CHECKSUM_FILE" 2>/dev/null || true
      record_checksum "$CORE_TGZ"
    else
      log "Core tarball cached: $CORE_TGZ"
    fi
    if [ ! -f offline-cache/wp-cli.phar ]; then
      log "Caching wp-cli.phar"
      curl -fsSL https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar -o offline-cache/wp-cli.phar || fail "Failed wp-cli download"
      sed -i "/wp-cli.phar$/d" "$CHECKSUM_FILE" 2>/dev/null || true
      record_checksum offline-cache/wp-cli.phar
    else
      log "wp-cli already cached"
      verify_checksum offline-cache/wp-cli.phar || { log "Re-downloading wp-cli.phar due to checksum mismatch"; curl -fsSL https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar -o offline-cache/wp-cli.phar || fail "Failed wp-cli download"; sed -i "/wp-cli.phar$/d" "$CHECKSUM_FILE" 2>/dev/null || true; record_checksum offline-cache/wp-cli.phar; }
    fi
    # Plugins
    if [ -n "${PREFETCH_PLUGINS:-}" ]; then
      IFS=',' read -r -a PLUGS <<<"$PREFETCH_PLUGINS"
      for p in "${PLUGS[@]}"; do
        p=$(echo "$p" | xargs)
        [ -z "$p" ] && continue
        DEST="offline-cache/plugins/${p}.zip"
        NEED_DL=0
        if [ ! -f "$DEST" ]; then
          NEED_DL=1
        else
          verify_checksum "$DEST" || NEED_DL=1
        fi
        if [ $NEED_DL -eq 0 ]; then
          log "Plugin cached: $p"
        else
          URL="https://downloads.wordpress.org/plugin/${p}.latest-stable.zip"
            log "Downloading plugin $p -> $DEST"
            curl -fsSL "$URL" -o "$DEST" || err "Failed plugin $p"
            [ -f "$DEST" ] && { sed -i "/$(basename "$DEST")$/d" "$CHECKSUM_FILE" 2>/dev/null || true; record_checksum "$DEST"; }
        fi
      done
    fi
    # Themes
    if [ -n "${PREFETCH_THEMES:-}" ]; then
      IFS=',' read -r -a THMS <<<"$PREFETCH_THEMES"
      for t in "${THMS[@]}"; do
        t=$(echo "$t" | xargs)
        [ -z "$t" ] && continue
        DEST="offline-cache/themes/${t}.zip"
        NEED_DL=0
        if [ ! -f "$DEST" ]; then
          NEED_DL=1
        else
          verify_checksum "$DEST" || NEED_DL=1
        fi
        if [ $NEED_DL -eq 0 ]; then
          log "Theme cached: $t"
        else
          URL="https://downloads.wordpress.org/theme/${t}.latest-stable.zip"
          log "Downloading theme $t -> $DEST"
          curl -fsSL "$URL" -o "$DEST" || err "Failed theme $t"
          [ -f "$DEST" ] && { sed -i "/$(basename "$DEST")$/d" "$CHECKSUM_FILE" 2>/dev/null || true; record_checksum "$DEST"; }
        fi
      done
    fi
    # Docker images
    if command -v docker >/dev/null 2>&1; then
      if docker info >/dev/null 2>&1; then
        log "Pulling docker images for cache"
        docker pull "wordpress:${WP_VERSION}" || true
        docker pull "${DB_IMAGE:-mariadb:10.6}" || true
        docker pull phpmyadmin:5 || true
        log "Docker images pulled (cached by daemon layer store)"
      else
        err "Docker daemon not reachable; skipping images"
      fi
    else
      err "Docker not installed; skipping image prefetch"
    fi
    if [ ! -f offline-cache/README.md ]; then
      cat > offline-cache/README.md <<OCACHE
# Offline Cache

Artifacts cached on $(date -u +%Y-%m-%dT%H:%M:%SZ):
- WordPress core tarball (${WP_VERSION})
- wp-cli.phar
- Plugin zips (from PREFETCH_PLUGINS): ${PREFETCH_PLUGINS:-none}
- Theme zips (from PREFETCH_THEMES): ${PREFETCH_THEMES:-none}
- Docker images (layer cached locally): wordpress:${WP_VERSION}, ${DB_IMAGE:-mariadb:10.6}, phpmyadmin:5

Use: disconnect network then run ./scripts/codex.sh setup (docker) or lite-setup (php) referencing cached artifacts (future enhancement could auto-detect these).
OCACHE
    fi
    log "Prefetch complete. Consider disabling agent internet now if desired."
    ;;
  verify-offline)
    CHECKSUM_FILE="offline-cache/checksums.txt"; [ -f "$CHECKSUM_FILE" ] || { err "No checksum file (run prefetch-offline)"; exit 1; }
    FAILS=0; while read -r line; do [ -z "$line" ] && continue; EXPECT=${line%% *}; FILEPATH=${line#*  }; [ -f "$FILEPATH" ] || { err "Missing $FILEPATH"; FAILS=$((FAILS+1)); continue; }; ACTUAL=$(sha256sum "$FILEPATH" | awk '{print $1}'); [ "$EXPECT" = "$ACTUAL" ] && log "OK: $(basename "$FILEPATH")" || { err "Mismatch: $(basename "$FILEPATH")"; FAILS=$((FAILS+1)); }; done < "$CHECKSUM_FILE"; [ $FAILS -eq 0 ] && log "All offline-cache artifacts verified" || { err "Checksum failures: $FAILS"; exit 2; }
    ;;
  lite-sqlite)
    if ! command -v php >/dev/null 2>&1; then fail "php required"; fi
    php -m | grep -Eqi '(pdo_sqlite|sqlite3)' || fail "PHP sqlite extension missing"
    mkdir -p wp-lite
    [ -f wp-lite/wp-load.php ] || { log "Running lite-setup first"; "$0" lite-setup; }
    ZIP="offline-cache/plugins/sqlite-database-integration.zip"; mkdir -p offline-cache/plugins
    [ -f "$ZIP" ] || { log "Downloading sqlite plugin"; curl -fsSL https://downloads.wordpress.org/plugin/sqlite-database-integration.latest-stable.zip -o "$ZIP" || fail "Download failed"; }
    PLUG_DIR="wp-lite/wp-content/plugins/sqlite-database-integration"
    if [ ! -d "$PLUG_DIR" ]; then
      if command -v unzip >/dev/null 2>&1; then tmp=$(mktemp -d); unzip -q "$ZIP" -d "$tmp" || fail unzip; mv "$tmp/sqlite-database-integration" "$PLUG_DIR" || fail move; rm -rf "$tmp"; else php -m | grep -qi zip || fail "Need unzip or ZipArchive"; php - <<'PHPEXT' "$ZIP" "$PLUG_DIR"
<?php $z=new ZipArchive; if($z->open($argv[1])!==true) exit(1); $base='sqlite-database-integration/'; for($i=0;$i<$z->numFiles;$i++){ $st=$z->statIndex($i); $n=$st['name']; if(strpos($n,$base)===0){ $rel=substr($n,strlen($base)); $p=$argv[2].'/'.$rel; if(substr($n,-1)=='/'){ if(!is_dir($p)) mkdir($p,0777,true);} else { if(!is_dir(dirname($p))) mkdir(dirname($p),0777,true); file_put_contents($p,$z->getFromIndex($i)); } }} ?>
PHPEXT
      fi
    fi
    DB_DROP=wp-lite/wp-content/db.php
    if [ ! -f "$DB_DROP" ]; then
      if [ -f "$PLUG_DIR/db.copy" ]; then
        sed "s|{SQLITE_IMPLEMENTATION_FOLDER_PATH}|wp-content/plugins/sqlite-database-integration|; s|{SQLITE_PLUGIN}|sqlite-database-integration/load.php|" "$PLUG_DIR/db.copy" > "$DB_DROP" || fail db.copy
      elif [ -f "$PLUG_DIR/wp-includes/sqlite/db.php" ]; then
        cp "$PLUG_DIR/wp-includes/sqlite/db.php" "$DB_DROP" || fail copy
      else
        fail "No db.copy or db.php in plugin"
      fi
      log "SQLite db.php installed"
    else
      log "SQLite db.php already present"
    fi
    grep -q "DB_NAME' *,'none'" wp-lite/wp-config.php 2>/dev/null && sed -i "s/DB_NAME' *,'none'/DB_NAME','wordpress'/" wp-lite/wp-config.php || true
    log "lite-sqlite complete. Next: ./scripts/codex.sh lite-install"
    ;;
  lite-install)
    if ! command -v php >/dev/null 2>&1; then fail "php required"; fi
    [ -f wp-lite/wp-load.php ] || fail "Run lite-setup first"
    [ -f wp-lite/wp-content/db.php ] || { log "db.php missing -> running lite-sqlite"; "$0" lite-sqlite; }
    [ -f wp-cli.phar ] || { log "Downloading wp-cli.phar"; curl -fsSL https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar -o wp-cli.phar || fail wp-cli; }
    SITE_URL=${SITE_URL:-http://localhost:8080}
    if php wp-cli.phar --path=wp-lite core is-installed --allow-root >/dev/null 2>&1; then log "Core already installed"; else log "Installing core (SQLite)"; php wp-cli.phar --path=wp-lite core install --url="$SITE_URL" --title="${PROJECT_NAME:-Lite Site}" --admin_user=admin --admin_password=admin --admin_email=admin@example.test --skip-email --allow-root || fail install; fi
    log "lite-install done: ${SITE_URL}/wp-admin/ (admin/admin)"
    ;;
esac
