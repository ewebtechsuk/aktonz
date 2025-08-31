#!/usr/bin/env bash
# codex_entrypoint.sh - Auto bootstrap WordPress using codex_manual_setup.sh inside a container.
# Intended usage: mount project into /var/www/html and set env vars (see below) or pass args.
# Environment vars consumed (optional):
#   CODEX_SITE_URL, CODEX_SITE_TITLE, CODEX_ADMIN_USER, CODEX_ADMIN_PASS, CODEX_ADMIN_EMAIL
#   DB_NAME (or WORDPRESS_DB_NAME), DB_USER (or WORDPRESS_DB_USER), DB_PASSWORD (or WORDPRESS_DB_PASSWORD), DB_HOST (or WORDPRESS_DB_HOST)
# Args passed to this wrapper are forwarded to codex_manual_setup.sh (and override env-derived defaults).
set -euo pipefail
cd "$(dirname "$0")/.."

CMD=(bash scripts/codex_manual_setup.sh)

# Map common official image env names to expected ones if not already set
export DB_NAME=${DB_NAME:-${WORDPRESS_DB_NAME:-wpdb}}
export DB_USER=${DB_USER:-${WORDPRESS_DB_USER:-wpuser}}
export DB_PASSWORD=${DB_PASSWORD:-${WORDPRESS_DB_PASSWORD:-wpsecret}}
export DB_HOST=${DB_HOST:-${WORDPRESS_DB_HOST:-localhost}}

# Provide a default site URL if running in docker-compose and not supplied
if [ -z "${CODEX_SITE_URL:-}" ]; then
  # Assume port 8080 host mapping (adjust if different)
  export CODEX_SITE_URL="http://localhost:8080"
fi

# If admin creds absent, generate a quick ephemeral set (logged once) to avoid install stall
if [ -z "${CODEX_ADMIN_USER:-}" ] || [ -z "${CODEX_ADMIN_PASS:-}" ] || [ -z "${CODEX_ADMIN_EMAIL:-}" ]; then
  export CODEX_ADMIN_USER=${CODEX_ADMIN_USER:-admin}
  export CODEX_ADMIN_PASS=${CODEX_ADMIN_PASS:-"admin$(date +%s | tail -c6)"}
  export CODEX_ADMIN_EMAIL=${CODEX_ADMIN_EMAIL:-admin@example.test}
  echo "[codex-entrypoint] Using ephemeral admin creds: $CODEX_ADMIN_USER / $CODEX_ADMIN_PASS ($CODEX_ADMIN_EMAIL)" >&2
fi

# Run setup
"${CMD[@]}" "$@"

# Keep container in foreground if invoked as main process and optional WP not started by apache (for FPM images)
exec "$@" >/dev/null 2>&1 || true
