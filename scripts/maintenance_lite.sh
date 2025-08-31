#!/usr/bin/env sh
# maintenance_lite.sh - Health + optional update pass for lite (no-docker, SQLite) setup
# Usage: sh scripts/maintenance_lite.sh [--update] [--json]
# Exits non-zero if critical health checks fail (HTTP / core presence / db drop-in)
set -eu

DO_UPDATE=0
OUTPUT_JSON=0
DO_AUDIT=0
for a in "$@"; do
  case "$a" in
    --update) DO_UPDATE=1 ;;
  --json) OUTPUT_JSON=1 ;;
  --audit-plugins) DO_AUDIT=1 ;;
    -h|--help)
      cat <<'EOF'
Usage: sh scripts/maintenance_lite.sh [--update] [--json]
  --update   Run wp core/theme/plugin updates (if wp-cli + network)
  --json     Emit final JSON summary (machine readable)
Performs:
  * Ensures wp-lite exists and core files present
  * Validates SQLite drop-in (wp-content/db.php)
  * Starts PHP built-in server if not running
  * HTTP GET / (expect 200) with basic timing
  * status-json (if scripts/codex.sh present) else local version parse
  * Optional offline cache checksum verify (if checksums file present)
  * Optional updates (core/plugins/themes)
  * Emits summary + optional JSON
EOF
      exit 0;;
  esac
done

ROOT=$(cd "$(dirname "$0")/.." && pwd)
cd "$ROOT"

log(){ printf '[maint] %s\n' "$*"; }
err(){ printf '[maint][error] %s\n' "$*" >&2; }

need(){ command -v "$1" >/dev/null 2>&1 || { err "Need $1"; exit 2; }; }
need php

WP_PATH=wp-lite
PORT=${PORT:-8080}
SITE_URL=${SITE_URL:-http://localhost:${PORT}}
DROPIN=${WP_PATH}/wp-content/db.php
START_TIME=$(date +%s)

STATUS_OK=1
HTTP_CODE=""
HTTP_TIME=""
CORE_VERSION=""
PLUGIN_UPDATES=0
THEME_UPDATES=0
CORE_UPDATE=0
OFFLINE_VERIFY="skipped"

# Ensure server running (fire and forget) if directory exists.
start_server(){
  if ! pgrep -f "php -S 127.0.0.1:${PORT} -t ${WP_PATH}" >/dev/null 2>&1; then
    if [ -d "$WP_PATH" ]; then
      log "Starting PHP server :${PORT}";
      (php -S 127.0.0.1:${PORT} -t "$WP_PATH" >/tmp/wp-lite-${PORT}.log 2>&1 &)
      sleep 1
    fi
  fi
}

if [ ! -f "$WP_PATH/wp-load.php" ]; then
  err "Core missing (run codex_lite_sqlite_setup.sh first)"
  STATUS_OK=0
else
  [ -f "$DROPIN" ] || { err "SQLite drop-in missing: $DROPIN"; STATUS_OK=0; }
fi

start_server || true

# HTTP check
if command -v curl >/dev/null 2>&1; then
  HTTP_METRICS=$(curl -fsS -o /tmp/maint_home.html -w '%{http_code} %{time_total}' "$SITE_URL/" 2>/dev/null || true)
  HTTP_CODE=$(echo "$HTTP_METRICS" | awk '{print $1}')
  HTTP_TIME=$(echo "$HTTP_METRICS" | awk '{print $2}')
  if [ "$HTTP_CODE" != "200" ]; then
    err "Homepage HTTP code: ${HTTP_CODE:-none}"; STATUS_OK=0
  else
    log "Homepage 200 in ${HTTP_TIME}s"
  fi
else
  err "curl not available; skipping HTTP check"
fi

# Status / version
if [ -x scripts/codex.sh ]; then
  RAW_JSON=$(timeout 5 ./scripts/codex.sh status-json 2>/dev/null || true)
  CORE_VERSION=$(echo "$RAW_JSON" | sed -n "s/.*\"core_version\":\"\([0-9.]*\)\".*/\1/p")
else
  if [ -f "$WP_PATH/wp-includes/version.php" ]; then
    CORE_VERSION=$(awk -F"'" '/\$wp_version *=/ { for(i=1;i<=NF;i++){ if($i ~ /^[0-9]+(\.[0-9]+)*$/){ printf "%s", $i; exit } } }' "$WP_PATH/wp-includes/version.php" 2>/dev/null || true)
  fi
fi
[ -n "$CORE_VERSION" ] || { err "Could not determine core version"; STATUS_OK=0; }

# wp-cli presence (local phar)
if [ -f wp-cli.phar ]; then
  # Updates check
  if php wp-cli.phar --path="$WP_PATH" core is-installed --allow-root >/dev/null 2>&1; then
    # Core update available?
    if php wp-cli.phar --path="$WP_PATH" core check-update --allow-root 2>/dev/null | grep -q 'wordpress.org'; then CORE_UPDATE=1; fi
    PLUGIN_UPDATES=$(php wp-cli.phar --path="$WP_PATH" plugin list --update=available --field=name --allow-root 2>/dev/null | wc -l | tr -d ' ' || echo 0)
    THEME_UPDATES=$(php wp-cli.phar --path="$WP_PATH" theme list --update=available --field=name --allow-root 2>/dev/null | wc -l | tr -d ' ' || echo 0)
    if [ "$DO_UPDATE" -eq 1 ]; then
      log "Applying updates (core/plugins/themes)"
      php wp-cli.phar --path="$WP_PATH" core update --allow-root || true
      php wp-cli.phar --path="$WP_PATH" plugin update --all --allow-root || true
      php wp-cli.phar --path="$WP_PATH" theme update --all --allow-root || true
    fi
  else
    err "WordPress not installed (wp-cli path not ready)"; STATUS_OK=0
  fi
else
  log "wp-cli.phar missing; skipping update checks"
fi

# Offline cache verify
if [ -f offline-cache/checksums.txt ] && [ -x scripts/codex.sh ]; then
  if ./scripts/codex.sh verify-offline >/tmp/maint_offline.log 2>&1; then
    OFFLINE_VERIFY="ok"
  else
    OFFLINE_VERIFY="fail"; STATUS_OK=0; err "offline-cache verification failed"
  fi
fi

duration=$(( $(date +%s) - START_TIME ))

summary(){
  log "Summary:";
  log " Core: ${CORE_VERSION:-unknown}";
  log " HTTP: ${HTTP_CODE:-na} (${HTTP_TIME:-?}s)";
  log " Updates: core=${CORE_UPDATE} plugins=${PLUGIN_UPDATES} themes=${THEME_UPDATES}";
  log " Offline cache: ${OFFLINE_VERIFY}";
  log " Status OK: ${STATUS_OK}";
  log " Duration: ${duration}s";
  if [ $DO_AUDIT -eq 1 ]; then
    if [ -x scripts/plugin_audit_combined.sh ]; then
      if [ -d wp-content/plugins ]; then
        log "Running plugin filesystem audit (no DB required)"
        scripts/plugin_audit_combined.sh > .codex-plugin-audit.json || err "Plugin audit failed"
        AUDIT_TOTAL=$(grep -o '"total":[0-9]*' .codex-plugin-audit.json 2>/dev/null | head -n1 | awk -F: '{print $2}' || echo 0)
        log " Plugin audit total plugins: ${AUDIT_TOTAL}";
      else
        log " Plugin audit skipped (no wp-content/plugins)";
      fi
    else
      err "plugin_audit_combined.sh not executable; skip audit"
    fi
  fi
}

summary

if [ "$OUTPUT_JSON" -eq 1 ]; then
  EXTRA=""
  if [ $DO_AUDIT -eq 1 ] && [ -f .codex-plugin-audit.json ]; then
    EXTRA=",\"plugin_audit\":true"
  fi
  printf '{"core_version":"%s","http_code":"%s","http_time":"%s","core_update":%s,"plugin_updates":%s,"theme_updates":%s,"offline":"%s","ok":%s,"duration_s":%s%s}"\n' \
    "$CORE_VERSION" "$HTTP_CODE" "$HTTP_TIME" "$CORE_UPDATE" "$PLUGIN_UPDATES" "$THEME_UPDATES" "$OFFLINE_VERIFY" "$STATUS_OK" "$duration" "$EXTRA"
fi

exit $([ $STATUS_OK -eq 1 ] && echo 0 || echo 1)
