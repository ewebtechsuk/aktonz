#!/usr/bin/env bash
# codex_setup_auto.sh - Fixed admin credential WordPress bootstrap (Option A)
# Feel free to override any of these by exporting the env var before running.
set -euo pipefail
export CODEX_SITE_URL=${CODEX_SITE_URL:-http://localhost:8080}
export CODEX_SITE_TITLE=${CODEX_SITE_TITLE:-"Aktonz Dev"}
export CODEX_ADMIN_USER=${CODEX_ADMIN_USER:-admin}
# WARNING: Change this password for any non-ephemeral environment.
export CODEX_ADMIN_PASS=${CODEX_ADMIN_PASS:-ChangeMe123!}
export CODEX_ADMIN_EMAIL=${CODEX_ADMIN_EMAIL:-info@aktonz.com}

# Optional: disable automatic SQLite fallback by setting CODEX_NO_SQLITE_FALLBACK=1

# Auto-generate secure admin password if default sentinel still present
if [ "${CODEX_ADMIN_PASS}" = "ChangeMe123!" ]; then
	if command -v openssl >/dev/null 2>&1; then
		GEN=$(openssl rand -hex 6)
	else
		GEN=$(date +%s%N | sha256sum | cut -c1-12)
	fi
	CODEX_ADMIN_PASS="Adm-${GEN}!"
	export CODEX_ADMIN_PASS
	echo "[codex-auto] Generated admin password (stored in .codex-admin.txt)" >&2
	printf 'admin_user=%s\nadmin_pass=%s\n' "$CODEX_ADMIN_USER" "$CODEX_ADMIN_PASS" > .codex-admin.txt
	chmod 600 .codex-admin.txt || true
fi

# Attempt MySQL connectivity with wait loop (host derived from env / .env)
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
MYSQL_WAIT_MAX=${CODEX_MYSQL_WAIT_MAX:-10} # attempts
MYSQL_WAIT_SLEEP=${CODEX_MYSQL_WAIT_SLEEP_BASE:-1}
if command -v php >/dev/null 2>&1; then
	for attempt in $(seq 1 $MYSQL_WAIT_MAX); do
		if php -r '[$h,$u,$p,$d,$P]=[$_SERVER["argv"][1],$_SERVER["argv"][2],$_SERVER["argv"][3],$_SERVER["argv"][4],(int)$_SERVER["argv"][5]]; $m=@mysqli_connect($h,$u,$p,$d,$P); if($m){echo "OK"; mysqli_close($m);} ' "$DB_HOST_ONLY" "$DB_CHECK_USER" "$DB_CHECK_PASS" "$DB_CHECK_NAME" "$DB_PORT_ONLY" 2>/dev/null | grep -q OK; then
			mysql_ok=1; break
		fi
		sleep_time=$(( MYSQL_WAIT_SLEEP * attempt ))
		echo "[codex-auto] MySQL not ready (attempt ${attempt}/${MYSQL_WAIT_MAX}); sleeping ${sleep_time}s" >&2
		sleep $sleep_time
	done
fi

MODE="mysql"
FALLBACK_USED=0
if [ $mysql_ok -ne 1 ] && [ "${CODEX_NO_SQLITE_FALLBACK:-0}" != "1" ]; then
	echo "[codex-auto] MySQL unreachable at $DB_CHECK_HOST (db=$DB_CHECK_NAME); falling back to SQLite lite setup" >&2
	MODE="sqlite"
	FALLBACK_USED=1
	export ADMIN_USER="$CODEX_ADMIN_USER"
	export ADMIN_PASS="$CODEX_ADMIN_PASS"
	export ADMIN_EMAIL="$CODEX_ADMIN_EMAIL"
	export SITE_URL="$CODEX_SITE_URL"
	START_TS=$(date +%s)
	bash scripts/codex_lite_sqlite_setup.sh || { echo "[codex-auto] SQLite fallback failed" >&2; exit 1; }
	END_TS=$(date +%s)
	CORE_VERSION=$(awk -F"'" '/\$wp_version *=/ { for(i=1;i<=NF;i++){ if($i ~ /^[0-9]+(\.[0-9]+)*$/){ print $i; exit } }}' wp-lite/wp-includes/version.php 2>/dev/null || echo "")
	printf '{"mode":"%s","db":"%s","url":"%s","fallback_used":%s,"core_version":"%s","duration_s":%s}\n' "$MODE" "sqlite" "$CODEX_SITE_URL" "$FALLBACK_USED" "$CORE_VERSION" "$((END_TS-START_TS))" > .codex-status.json
	touch .codex-ready
	exit 0
fi

# Delegate to the main manual setup script (idempotent)
bash scripts/codex_manual_setup.sh "$@"

# Produce status JSON & ready file for MySQL path
START_TS=${START_TS:-$(date +%s)}
CORE_VERSION=""
if [ -f wp-includes/version.php ]; then
	CORE_VERSION=$(awk -F"'" '/\$wp_version *=/ { for(i=1;i<=NF;i++){ if($i ~ /^[0-9]+(\.[0-9]+)*$/){ print $i; exit } }}' wp-includes/version.php 2>/dev/null || echo "")
fi
END_TS=$(date +%s)
printf '{"mode":"%s","db":"%s","url":"%s","fallback_used":%s,"core_version":"%s","duration_s":%s}\n' "$MODE" "mysql" "$CODEX_SITE_URL" "$FALLBACK_USED" "$CORE_VERSION" "$((END_TS-START_TS))" > .codex-status.json
touch .codex-ready
