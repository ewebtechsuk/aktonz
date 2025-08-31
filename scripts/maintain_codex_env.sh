#!/usr/bin/env bash
# maintain_codex_env.sh
# Purpose: Perform common maintenance & troubleshooting tasks for the local "Codex" WordPress Docker environment.
# Works alongside setup_codex_env.sh (expects same .env / project name and docker-compose.yml).
# Usage: ./scripts/maintain_codex_env.sh <command> [args]
# Re-run with 'help' to list commands.

set -euo pipefail

PROJECT_ROOT=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
cd "$PROJECT_ROOT"

ENV_FILE=.env
[ -f "$ENV_FILE" ] || { echo "[maint] .env not found. Run setup_codex_env.sh first." >&2; exit 1; }

set -a; . "$ENV_FILE"; set +a
PROJECT_NAME=${PROJECT_NAME:-codex}
COMPOSE_PROJECT="-p $PROJECT_NAME"

log(){ printf '[maint] %s\n' "$*"; }
err(){ printf '[maint][error] %s\n' "$*" >&2; }
fail(){ err "$*"; exit 1; }
need(){ command -v "$1" >/dev/null 2>&1 || fail "Required command '$1' not found"; }

DOCKER_AVAILABLE=1
if ! command -v docker >/dev/null 2>&1; then
  DOCKER_AVAILABLE=0
  err "docker command not found – switching to LIMITED local wp-cli mode"
else
  if ! docker info >/dev/null 2>&1; then
    err "Docker daemon not reachable – limited local mode"
    DOCKER_AVAILABLE=0
  fi
fi

compose(){
  if [ $DOCKER_AVAILABLE -eq 1 ]; then
    docker compose $COMPOSE_PROJECT "$@"
  else
    err "compose command unavailable (no docker)"
    return 1
  fi
}

# wp(): transparently use docker exec when available; fallback to local wp-cli (wp or wp-cli.phar)
wp(){
  if [ $DOCKER_AVAILABLE -eq 1 ]; then
    compose exec -T wordpress wp "$@"
  else
    # Local fallback: ensure wp-cli present
    if command -v wp >/dev/null 2>&1; then
      wp "$@"
    else
      if [ -f wp-cli.phar ]; then
        php wp-cli.phar "$@" --allow-root
      else
        err "wp-cli not available locally; run: curl -fsSL https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar -o wp-cli.phar && chmod +x wp-cli.phar"
        return 1
      fi
    fi
  fi
}

usage(){ cat <<'EOF'
Commands:
  help                       Show this help
  status                     Show environment/container status + WP version
  logs [svc]                 Tail logs (default wordpress) (Ctrl+C to exit)
  shell [svc]                Shell into container (default wordpress)
  wp <args...>               Run arbitrary wp-cli command
  backup-db [out.sql.gz]     Dump DB to file (default: backups/DATE.sql.gz)
  restore-db <file.sql[.gz]> Restore DB from dump (drops all tables first)
  update                     Update core + plugins + themes
  update-core                Update WP core only
  update-plugins             Update all plugins
  update-themes              Update all themes
  list-plugins               List plugins (status, version, updates)
  enable-plugin <slug>       Activate plugin
  disable-plugin <slug>      Deactivate plugin
  rename-disable <slug>      Physically rename plugin dir -> .disabled
  rename-enable <slug>       Re-enable previously renamed plugin
  transients-clear           Delete all transients
  cache-flush                Flush rewrite rules + object cache (if any)
  search-replace <old> <new> Run safe search/replace in DB (dry-run add --dry-run)
  salts-regenerate           Regenerate local salts file (wp-config-local.php)
  db-size                    Show table sizes
  optimize-db                Optimize all tables
  health                     Basic site health checks (HTTP + db + core updates)
  recreate                   docker compose down --volumes && up -d (destructive DB!) (docker mode only)
  down                       Stop containers
  up                         Start containers
  prune                      Remove dangling docker images & volumes (global) (docker mode only)
  hard-reset                 Remove containers, volumes, generated salts, then fresh up (docker mode only)
  info                       Show key environment variables
EOF
}

cmd=${1:-help}; shift || true

case "$cmd" in
  help|-h|--help) usage; exit 0 ;;
  status)
    if [ $DOCKER_AVAILABLE -eq 1 ]; then
      compose ps || true
      if compose exec -T wordpress wp core version >/dev/null 2>&1; then
        log "WP Version: $(wp core version)"
        log "Site URL: $(wp option get siteurl)"
      else
        log "WordPress not yet installed (docker)"
      fi
    else
      # Local status
      if [ -f wp-config.php ]; then
        if wp core version >/dev/null 2>&1; then
          log "WP Version: $(wp core version)"
          log "Site URL: $(wp option get siteurl || echo 'n/a')"
        else
          log "wp-cli cannot query core version (maybe not installed)"
        fi
      else
        log "No wp-config.php found (run setup script)"
      fi
    fi
    ;;
  logs)
    svc=${1:-wordpress}; shift || true
    compose logs -f "$svc"
    ;;
  shell)
    svc=${1:-wordpress}; shift || true
    compose exec "$svc" bash || compose exec "$svc" sh
    ;;
  wp)
    wp "$@" || true
    ;;
  backup-db)
    mkdir -p backups
    out=${1:-backups/$(date +%Y%m%d-%H%M%S).sql.gz}
    log "Dumping DB -> $out"
    compose exec -T db mysqldump -u"$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" | gzip -c > "$out"
    log "Done ($out)"
    ;;
  restore-db)
    file=${1:-}
    [ -f "$file" ] || fail "File not found: $file"
    log "Restoring $file"
    if [[ $file == *.gz ]]; then
      gunzip -c "$file" | compose exec -T db mysql -u"$DB_USER" -p"$DB_PASSWORD" "$DB_NAME"
    else
      compose exec -T db mysql -u"$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" < "$file"
    fi
    log "Restore complete"
    ;;
  update)
    wp core update || true
    wp plugin update --all || true
    wp theme update --all || true
    wp core update-db || true
    ;;
  update-core) wp core update || true; wp core update-db || true ;;
  update-plugins) wp plugin update --all || true ;;
  update-themes) wp theme update --all || true ;;
  list-plugins) wp plugin list --format=table ;;
  enable-plugin) slug=${1:?slug}; wp plugin activate "$slug" ;;
  disable-plugin) slug=${1:?slug}; wp plugin deactivate "$slug" || true ;;
  rename-disable)
    slug=${1:?slug}
    src=wp-content/plugins/$slug
    dst=${src}.disabled
    [ -d "$src" ] || fail "Plugin dir not found: $src"
    [ -d "$dst" ] && fail "Already disabled: $dst"
    mv "$src" "$dst" && log "Renamed $src -> $dst"
    ;;
  rename-enable)
    slug=${1:?slug}
    dst=wp-content/plugins/$slug
    src=${dst}.disabled
    [ -d "$src" ] || fail "Disabled dir not found: $src"
    mv "$src" "$dst" && log "Restored $dst" && wp plugin activate "$slug" || true
    ;;
  transients-clear)
    wp transient delete --all || true
    wp transient delete-expired || true
    ;;
  cache-flush)
    wp cache flush || true
    wp rewrite flush --hard || true
    ;;
  search-replace)
    old=${1:?old}; new=${2:?new}; shift 2
    wp search-replace "$old" "$new" "$@" --skip-columns=guid || true
    ;;
  salts-regenerate)
    file=wp-config-local.php
    if command -v curl >/dev/null 2>&1 && curl -fsSL https://api.wordpress.org/secret-key/1.1/salt/ > .salts.tmp; then :; else
      log "Salt API failed; generating locally"
      : > .salts.tmp
      for k in AUTH_KEY SECURE_AUTH_KEY LOGGED_IN_KEY NONCE_KEY AUTH_SALT SECURE_AUTH_SALT LOGGED_IN_SALT NONCE_SALT; do
        printf "define('%s','%s');\n" "$k" "$(openssl rand -base64 48 2>/dev/null || head -c 48 /dev/urandom | base64)" >> .salts.tmp
      done
    fi
    { echo '<?php'; echo '// Regenerated local salts'; cat .salts.tmp; echo "if (!defined('WP_CACHE')) define('WP_CACHE', false);"; } > "$file"
    rm -f .salts.tmp
    log "Salts written to $file"
    ;;
  db-size)
    compose exec -T db bash -c "mysql -u$DB_USER -p$DB_PASSWORD -e 'SELECT table_name AS \"Table\", ROUND((data_length+index_length)/1024/1024,2) AS SizeMB FROM information_schema.TABLES WHERE table_schema=\"$DB_NAME\" ORDER BY (data_length+index_length) DESC;'" || true
    ;;
  optimize-db)
    for t in $(wp db tables); do wp db query "OPTIMIZE TABLE $t" || true; done
    ;;
  health)
    log "HTTP check: $SITE_URL"
    if curl -fsS -o /dev/null -w '%{http_code}\n' "$SITE_URL" 2>/dev/null | grep -q '^2'; then log "HTTP OK"; else err "HTTP not OK"; fi
    if wp core is-installed >/dev/null 2>&1; then log "Core installed"; else err "Core NOT installed"; fi
    if wp core check-update | grep -q 'Latest'; then log "Core up-to-date"; else log "Core updates available"; fi
    log "Plugin updates: $(wp plugin list --update=available --field=name | wc -l | tr -d ' ')"
    ;;
  recreate)
    [ $DOCKER_AVAILABLE -eq 1 ] || fail "recreate only valid in docker mode"
    read -r -p "Destructive: drop containers & volumes. Continue? (y/N) " ans; [[ $ans == y* ]] || exit 0
    compose down -v
    compose up -d --build
    ;;
  down)
    [ $DOCKER_AVAILABLE -eq 1 ] || fail "down only valid in docker mode"
    compose down ;;
  up)
    if [ $DOCKER_AVAILABLE -eq 1 ]; then compose up -d; else fail "up only valid in docker mode"; fi ;;
  prune)
    [ $DOCKER_AVAILABLE -eq 1 ] || fail "prune only valid in docker mode"
    read -r -p "Prune dangling images & unused volumes (global)? (y/N) " ans; [[ $ans == y* ]] || exit 0
    docker image prune -f
    docker volume prune -f
    ;;
  hard-reset)
    [ $DOCKER_AVAILABLE -eq 1 ] || fail "hard-reset only valid in docker mode"
    read -r -p "Hard reset (down -v + remove salts + fresh up)? (y/N) " ans; [[ $ans == y* ]] || exit 0
    compose down -v || true
    rm -f wp-config-local.php
    compose up -d --build
    ;;
  info)
    log "Project: $PROJECT_NAME"
    if [ $DOCKER_AVAILABLE -eq 1 ]; then
      log "Mode: docker"
      log "Site URL: ${SITE_URL}"
      log "DB: ${DB_NAME}@db as ${DB_USER}"
      log "HTTP Port: ${SITE_HTTP_PORT}"
      log "phpMyAdmin: http://localhost:${PHPMYADMIN_PORT:-8081}"
    else
      log "Mode: local (no docker)"
      if [ -f wp-config.php ]; then
        wp core version 2>/dev/null && log "Core: $(wp core version)" || true
        log "Site URL: $(wp option get siteurl 2>/dev/null || echo 'unset')"
      else
        log "WordPress not initialized. Run codex_manual_setup.sh or codex_lite_sqlite_setup.sh"
      fi
    fi
    ;;
  *)
    err "Unknown command: $cmd"
    usage
    exit 1
    ;;
esac
