#!/usr/bin/env bash
# setup_local_mysql.sh
# Purpose: Provision a lightweight local MariaDB/MySQL instance (Ubuntu apt-based) for the Codex manual WordPress setup (Option A).
# Idempotent: Safe to re-run; only recreates DB/user if missing. Does NOT destroy existing data unless --reset specified.
# Usage:
#   bash scripts/setup_local_mysql.sh \
#     --db-name wpdb --db-user wpuser --db-pass 'wpsecret' [--root-pass 'rootpw'] [--reset]
# Notes:
#   * Designed for ephemeral CI/dev containers without Docker.
#   * Uses unix_socket auth for root if root password not supplied.
#   * If server already running, skips install/start.
#   * Leaves bind-address default (accessible only locally by default in CI images).
set -euo pipefail

DB_NAME=""; DB_USER=""; DB_PASS=""; ROOT_PASS=""; RESET=0

while [ $# -gt 0 ]; do
  case "$1" in
    --db-name) DB_NAME="$2"; shift 2;;
    --db-user) DB_USER="$2"; shift 2;;
    --db-pass) DB_PASS="$2"; shift 2;;
    --root-pass) ROOT_PASS="$2"; shift 2;;
    --reset) RESET=1; shift;;
    -h|--help) sed -n '1,120p' "$0"; exit 0;;
    *) echo "[mysql-setup][error] Unknown arg: $1" >&2; exit 1;;
  esac
done

log(){ printf '[mysql-setup] %s\n' "$*"; }
err(){ printf '[mysql-setup][error] %s\n' "$*" >&2; }
fail(){ err "$*"; exit 1; }
need(){ command -v "$1" >/dev/null 2>&1 || fail "Need dependency: $1"; }

[ -n "$DB_NAME" ] || DB_NAME="${DB_NAME_ENV:-wpdb}"
[ -n "$DB_USER" ] || DB_USER="${DB_USER_ENV:-wpuser}"
[ -n "$DB_PASS" ] || DB_PASS="${DB_PASSWORD:-wpsecret}"

need bash
need sed

# Install server if absent
if ! command -v mysql >/dev/null 2>&1; then
  log "Installing MariaDB server"
  export DEBIAN_FRONTEND=noninteractive
  apt-get update -y >/dev/null 2>&1 || apt-get update -y
  apt-get install -y mariadb-server mariadb-client >/dev/null 2>&1 || apt-get install -y mariadb-server mariadb-client
else
  log "MariaDB/MySQL client already present"
fi

# Start service if not running
if ! pgrep -x mysqld >/dev/null 2>&1; then
  log "Starting MariaDB server"
  (service mariadb start || service mysql start || mysqld_safe >/dev/null 2>&1 &)
  # Wait for socket
  ATT=0; until mysqladmin ping >/dev/null 2>&1 || [ $ATT -ge 30 ]; do sleep 1; ATT=$((ATT+1)); done
  mysqladmin ping >/dev/null 2>&1 && log "Server responsive" || fail "Server did not start"
else
  log "MariaDB already running"
fi

# Root auth method detection
ROOT_LOGIN=(mysql -uroot)
if [ -n "$ROOT_PASS" ]; then
  ROOT_LOGIN=(mysql -uroot -p"$ROOT_PASS")
fi
# Detect socket auth denial and switch to sudo mysql
if ! "${ROOT_LOGIN[@]}" -e 'SELECT 1' >/dev/null 2>&1; then
  if command -v sudo >/dev/null 2>&1 && sudo -n true 2>/dev/null; then
    if sudo mysql -uroot -e 'SELECT 1' >/dev/null 2>&1; then
      ROOT_LOGIN=(sudo mysql -uroot)
      log "Using sudo mysql (unix_socket auth)"
    fi
  fi
fi

# Optionally set root password if provided & not already set (best-effort)
if [ -n "$ROOT_PASS" ]; then
  if ! mysql -uroot -p"$ROOT_PASS" -e 'SELECT 1' >/dev/null 2>&1; then
    log "Setting root password"
    mysql -uroot <<SQL
ALTER USER 'root'@'localhost' IDENTIFIED BY '${ROOT_PASS}';
FLUSH PRIVILEGES;
SQL
  fi
fi

# Drop DB/user if reset requested
if [ $RESET -eq 1 ]; then
  log "--reset specified: dropping DB/user if exist"
  "${ROOT_LOGIN[@]}" <<SQL
DROP DATABASE IF EXISTS \`$DB_NAME\`;
DROP USER IF EXISTS '$DB_USER'@'localhost';
FLUSH PRIVILEGES;
SQL
fi

# Create DB & user (idempotent)
log "Ensuring database and user exist"
"${ROOT_LOGIN[@]}" <<SQL
CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'localhost';
FLUSH PRIVILEGES;
SQL

log "Summary: DB=$DB_NAME User=$DB_USER Password=[hidden] Host=localhost"
log "Ready for: bash scripts/codex_manual_setup.sh --url <URL> --title <Title> --admin-user <u> --admin-pass <p> --admin-email <e> --db-name $DB_NAME --db-user $DB_USER --db-pass '$DB_PASS' --db-host localhost"
