#!/usr/bin/env bash
# codex_setup_auto.sh - Fixed admin credential WordPress bootstrap (Option A)
# Feel free to override any of these by exporting the env var before running.
set -euo pipefail
export CODEX_SITE_URL=${CODEX_SITE_URL:-http://localhost:8080}
export CODEX_SITE_TITLE=${CODEX_SITE_TITLE:-"Aktonz Dev"}
export CODEX_ADMIN_USER=${CODEX_ADMIN_USER:-admin}
# WARNING: Change this password for any non-ephemeral environment.
export CODEX_ADMIN_PASS=${CODEX_ADMIN_PASS:-Utembeeds875@!}
export CODEX_ADMIN_EMAIL=${CODEX_ADMIN_EMAIL:-info@aktonz.com}

# Optional: disable automatic SQLite fallback by setting CODEX_NO_SQLITE_FALLBACK=1

# Attempt quick MySQL connectivity check (host derived from env / .env)
DB_CHECK_HOST="${WORDPRESS_DB_HOST:-${DB_HOST:-localhost}}"
DB_CHECK_NAME="${DB_NAME:-${WORDPRESS_DB_NAME:-wpdb}}"
DB_CHECK_USER="${DB_USER:-${WORDPRESS_DB_USER:-wpuser}}"
DB_CHECK_PASS="${DB_PASSWORD:-${WORDPRESS_DB_PASSWORD:-wpsecret}}"

# Extract host / port for mysqli
DB_HOST_ONLY="$DB_CHECK_HOST"; DB_PORT_ONLY=3306
if echo "$DB_CHECK_HOST" | grep -q ':'; then
	DB_HOST_ONLY="${DB_CHECK_HOST%%:*}"
	DB_PORT_ONLY="${DB_CHECK_HOST##*:}"
fi

mysql_ok=0
if command -v php >/dev/null 2>&1; then
	if php -r '[$h,$u,$p,$d,$P]=[$_SERVER["argv"][1],$_SERVER["argv"][2],$_SERVER["argv"][3],$_SERVER["argv"][4],(int)$_SERVER["argv"][5]]; $m=@mysqli_connect($h,$u,$p,$d,$P); if($m){echo "OK"; mysqli_close($m);} ' "$DB_HOST_ONLY" "$DB_CHECK_USER" "$DB_CHECK_PASS" "$DB_CHECK_NAME" "$DB_PORT_ONLY" 2>/dev/null | grep -q OK; then
		mysql_ok=1
	fi
fi

if [ $mysql_ok -ne 1 ] && [ "${CODEX_NO_SQLITE_FALLBACK:-0}" != "1" ]; then
	echo "[codex-auto] MySQL unreachable at $DB_CHECK_HOST (db=$DB_CHECK_NAME); falling back to SQLite lite setup" >&2
	# Map admin creds for lite script
	export ADMIN_USER="$CODEX_ADMIN_USER"
	export ADMIN_PASS="$CODEX_ADMIN_PASS"
	export ADMIN_EMAIL="$CODEX_ADMIN_EMAIL"
	export SITE_URL="$CODEX_SITE_URL"
	bash scripts/codex_lite_sqlite_setup.sh || {
		echo "[codex-auto] SQLite fallback failed" >&2; exit 1; }
	exit 0
fi

# Delegate to the main manual setup script (idempotent)
bash scripts/codex_manual_setup.sh "$@"
