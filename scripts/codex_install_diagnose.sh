#!/usr/bin/env bash
# codex_install_diagnose.sh
# Purpose: Automated retry + diagnostics for failing wp core install in Codex Option A local environment.
# Collects environment info, refreshes wp-cli, validates DB & PHP extensions, attempts install, outputs summary.
# Usage:
#   bash scripts/codex_install_diagnose.sh \
#     --url http://localhost:8080 --title "Aktonz Dev" \
#     --admin-user admin --admin-pass 'ChangeMe123!' --admin-email admin@example.test \
#     --db-name wpdb --db-user wpuser --db-pass wpsecret --db-host localhost
set -euo pipefail

LOG=codex_install_diag.log
: > "$LOG"
echo "[diag] Starting $(date -u)" | tee -a "$LOG"

ARGS=("$@")

want(){ printf '%s' " ${*} " | grep -q " --$1 " ; }

fetch_arg(){
  local key=$1; local val=""; local i=0;
  for ((i=0;i<${#ARGS[@]};i++)); do if [ "${ARGS[$i]}" = "--$key" ]; then val=${ARGS[$((i+1))]:-}; break; fi; done; echo "$val";
}

URL=$(fetch_arg url)
TITLE=$(fetch_arg title)
ADMIN_USER=$(fetch_arg admin-user)
ADMIN_PASS=$(fetch_arg admin-pass)
ADMIN_EMAIL=$(fetch_arg admin-email)
DB_NAME=$(fetch_arg db-name)
DB_USER=$(fetch_arg db-user)
DB_PASS=$(fetch_arg db-pass)
DB_HOST=$(fetch_arg db-host)

PHP_BIN=php
if ! php -m 2>/dev/null | grep -qi mysqli && command -v php7.4 >/dev/null 2>&1 && php7.4 -m | grep -qi mysqli; then
  PHP_BIN=php7.4
  echo "[diag] Using php7.4 (mysqli present)" | tee -a "$LOG"
fi

echo "[diag] PHP_BIN=$PHP_BIN" | tee -a "$LOG"
echo "[diag] PHP version: $($PHP_BIN -v | head -n1)" | tee -a "$LOG"
echo "[diag] Extensions: $($PHP_BIN -m | tr '\n' ' ' | sed 's/  */ /g')" | tee -a "$LOG"

echo "[diag] DB params host=$DB_HOST db=$DB_NAME user=$DB_USER" | tee -a "$LOG"

echo "[diag] Testing raw mysqli connect" | tee -a "$LOG"
$PHP_BIN -d detect_unicode=0 -r "\n$code=0; if(!function_exists('mysqli_connect')){echo 'NO_MYSQLI'; exit(2);} \n$start=microtime(true); \n@\$c = mysqli_init(); if(\$c && @mysqli_real_connect(\$c, '$DB_HOST', '$DB_USER', '$DB_PASS', '$DB_NAME')){ echo 'OK '.round((microtime(true)-$start)*1000).'ms'; } else { echo 'FAIL '.mysqli_connect_errno().' '.mysqli_connect_error(); $code=1;} if(\$c) @mysqli_close(\$c); exit($code);" 2>&1 | tee -a "$LOG" || true

echo "[diag] Refreshing wp-cli.phar" | tee -a "$LOG"
rm -f wp-cli.phar
curl -fsSL https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar -o wp-cli.phar
chmod +x wp-cli.phar
$PHP_BIN wp-cli.phar --info 2>&1 | tee -a "$LOG" || true

if [ ! -f wp-settings.php ]; then
  echo "[diag] Core not downloaded; downloading" | tee -a "$LOG"
  $PHP_BIN wp-cli.phar core download --force 2>&1 | tee -a "$LOG" || true
fi

echo "[diag] Attempting core is-installed check" | tee -a "$LOG"
$PHP_BIN wp-cli.phar core is-installed --allow-root 2>&1 | tee -a "$LOG" || true

if ! $PHP_BIN wp-cli.phar core is-installed --allow-root >/dev/null 2>&1; then
  echo "[diag] Running install" | tee -a "$LOG"
  $PHP_BIN wp-cli.phar core install \
    --url="$URL" --title="$TITLE" \
    --admin_user="$ADMIN_USER" --admin_password="$ADMIN_PASS" --admin_email="$ADMIN_EMAIL" \
    --skip-email --allow-root 2>&1 | tee -a "$LOG" || INSTALL_FAIL=1
else
  echo "[diag] Core already installed" | tee -a "$LOG"
fi

echo "[diag] Post-install core is-installed status:" | tee -a "$LOG"
$PHP_BIN wp-cli.phar core is-installed --allow-root 2>&1 | tee -a "$LOG" || true

echo "[diag] Listing active plugins (if possible)" | tee -a "$LOG"
$PHP_BIN wp-cli.phar plugin list --allow-root 2>&1 | tee -a "$LOG" || true

echo "[diag] wp-config first 60 lines" | tee -a "$LOG"
sed -n '1,60p' wp-config.php 2>/dev/null | tee -a "$LOG" || true

if [ "${INSTALL_FAIL:-0}" = 1 ]; then
  echo "[diag] RESULT: INSTALL FAILED" | tee -a "$LOG"
  exit 1
else
  echo "[diag] RESULT: OK" | tee -a "$LOG"
fi
