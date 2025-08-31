#!/usr/bin/env sh
# maintenance_lite.sh - Health + optional update pass for lite (no-docker, SQLite) setup
# Usage: sh scripts/maintenance_lite.sh [--update] [--json]
# Exits non-zero if critical health checks fail (HTTP / core presence / db drop-in)
set -eu

DO_UPDATE=0
OUTPUT_JSON=0
DO_AUDIT=0
QUICK=0
ALLOW_PLUGINS=""
for a in "$@"; do
  case "$a" in
    --update) DO_UPDATE=1 ;;
  --json) OUTPUT_JSON=1 ;;
  --audit-plugins) DO_AUDIT=1 ;;
  --quick) QUICK=1 ;;
  --allow-plugins=*) ALLOW_PLUGINS="${a#--allow-plugins=}" ;;
    -h|--help)
      cat <<'EOF'
Usage: sh scripts/maintenance_lite.sh [--update] [--json]
  --update   Run wp core/theme/plugin updates (if wp-cli + network)
  --json     Emit final JSON summary (machine readable)
  --quick    Skip offline verify + plugin audit + updates enumeration for speed
  --audit-plugins  Run filesystem plugin audit (ignored if --quick)
  --allow-plugins=slug1,slug2  Allowlist for lite mode; other plugins renamed *.disabled
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
ORIG_PORT=$PORT
SITE_URL=${SITE_URL:-http://localhost:${PORT}}
DROPIN=${WP_PATH}/wp-content/db.php
START_TIME=$(date +%s)

STATUS_OK=1
HTTP_CODE=""
HTTP_TIME=""
REDIRECT_CHAIN=""
FINAL_PORT=""
CORE_VERSION=""
PLUGIN_UPDATES=0
THEME_UPDATES=0
CORE_UPDATE=0
OFFLINE_VERIFY="skipped"

# Ensure server running (fire and forget) if directory exists.
start_server(){
  if [ ! -d "$WP_PATH" ]; then return 0; fi
  if pgrep -f "php -S 127.0.0.1:${PORT} -t ${WP_PATH}" >/dev/null 2>&1; then return 0; fi
  # attempt original + up to 10 fallback ports
  for offset in 0 1 2 3 4 5 6 7 8 9 10; do
    CAND=$((ORIG_PORT+offset))
    # busy check
    if ss -ltn 2>/dev/null | grep -q ":${CAND} " ; then
      continue
    fi
    log "Starting PHP server :${CAND}";
    (php -S 127.0.0.1:${CAND} -t "$WP_PATH" >/tmp/wp-lite-${CAND}.log 2>&1 &)
    sleep 1
    if pgrep -f "php -S 127.0.0.1:${CAND} -t ${WP_PATH}" >/dev/null 2>&1; then
      PORT=$CAND
      SITE_URL="http://localhost:${PORT}"
      export PORT SITE_URL
      return 0
    fi
  done
  err "Could not start PHP server on any port starting at ${ORIG_PORT}"
}

probe_http(){
  # Manual redirect follow to capture chain (max 5 hops)
  local start_url="${SITE_URL}/"
  local current_url="$start_url"
  local hops=0
  local total_time=0
  local chain=""
  ACCEPT_CODES=${ACCEPT_CODES:-"200"}
  while [ $hops -lt 5 ]; do
    RESP=$(curl -fsS -o /tmp/maint_home_step.html -w '%{http_code} %{time_total}' "$current_url" 2>/dev/null || true)
    code=$(echo "$RESP" | awk '{print $1}')
    t=$(echo "$RESP" | awk '{print $2}')
    [ -n "$t" ] || t=0
    total_time=$(awk -v a="$total_time" -v b="$t" 'BEGIN{printf "%.6f", (a+b)}')
    [ -z "$chain" ] && chain="$code" || chain="$chain,$code"
    if [ "$code" = "301" ] || [ "$code" = "302" ]; then
      loc=$(grep -i '^Location:' /tmp/maint_home_step.html 2>/dev/null | tail -n1 | awk '{print $2}' | tr -d '\r')
      if [ -z "$loc" ]; then break; fi
      current_url="$loc"
      hops=$((hops+1))
      continue
    fi
    break
  done
  HTTP_CODE="$code"
  HTTP_TIME="$total_time"
  REDIRECT_CHAIN="$chain"
  # final port extract
  FINAL_PORT=$(printf "%s" "$current_url" | sed -n 's#.*://[^/:]*:\([0-9][0-9]*\).*#\1#p')
  [ -n "$FINAL_PORT" ] || FINAL_PORT="$PORT"
  # success if code in ACCEPT_CODES
  for c in $ACCEPT_CODES; do
    if [ "$HTTP_CODE" = "$c" ]; then
      log "Homepage ${HTTP_CODE} chain=${REDIRECT_CHAIN} in ${HTTP_TIME}s"
      return 0
    fi
  done
  return 1
}

if [ ! -f "$WP_PATH/wp-load.php" ]; then
  err "Core missing (run codex_lite_sqlite_setup.sh first)"
  STATUS_OK=0
else
  [ -f "$DROPIN" ] || { err "SQLite drop-in missing: $DROPIN"; STATUS_OK=0; }
fi

start_server || true

# Optional plugin allowlist (lite slimming)
if [ -n "$ALLOW_PLUGINS" ] && [ -d "$WP_PATH/wp-content/plugins" ]; then
  allow_csv="$ALLOW_PLUGINS,sqlite-database-integration" # ensure sqlite plugin always allowed
  for pdir in "$WP_PATH"/wp-content/plugins/*; do
    [ -d "$pdir" ] || continue
    base=$(basename "$pdir")
    skip=0
    # iterate over comma list manually
    rest="$allow_csv"
    while [ -n "$rest" ]; do
      one=${rest%%,*}
      [ "$rest" = "$one" ] && rest="" || rest=${rest#*,}
      [ "$base" = "$one" ] && { skip=1; break; }
    done
    if [ $skip -eq 0 ] && [ ! -d "${pdir}.disabled" ]; then
      mv "$pdir" "${pdir}.disabled" 2>/dev/null || true
      log "Disabled plugin (allowlist trim): $base"
    fi
  done
fi

# HTTP check
export MAINT_FORCE_SITEURL="$SITE_URL"
if command -v curl >/dev/null 2>&1; then
  if ! probe_http; then
    err "Homepage HTTP code: ${HTTP_CODE:-none} (after retries)"; STATUS_OK=0
    # tail server log if present
    [ -f /tmp/wp-lite-${PORT}.log ] && { log "Server log tail:"; tail -n 40 /tmp/wp-lite-${PORT}.log | sed 's/^/[maint][tail] /'; }
    # If server was (re)started on a different fallback port or just started late, give one more probe attempt
    if probe_http; then
      log "Homepage recovered on retry with code ${HTTP_CODE}"
      STATUS_OK=1
    fi
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
if [ $QUICK -eq 0 ] && [ -f offline-cache/checksums.txt ] && [ -x scripts/codex.sh ]; then
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
  if [ $DO_AUDIT -eq 1 ] && [ $QUICK -eq 0 ]; then
    AUDIT_OK=0
    rm -f .codex-plugin-audit.err 2>/dev/null || true
    if [ -x scripts/plugin_audit_combined.sh ]; then
      if [ -d wp-content/plugins ]; then
        log "Running plugin filesystem audit (no DB required)"
        # Reuse cached audit if fresh (<600s)
        if [ -f .codex-plugin-audit.json ]; then
          mt=$(($(date +%s) - $(stat -c %Y .codex-plugin-audit.json 2>/dev/null || echo 0)))
        else
          mt=9999
        fi
        if [ $mt -lt 600 ]; then
          log "Using cached plugin audit (${mt}s old)"
          AUDIT_OK=1
        elif bash scripts/plugin_audit_combined.sh > .codex-plugin-audit.json 2> .codex-plugin-audit.err; then
          # success path
          if [ ! -s .codex-plugin-audit.err ]; then rm -f .codex-plugin-audit.err || true; fi
          AUDIT_OK=1
        else
          err "Plugin audit failed (see .codex-plugin-audit.err)"
          STATUS_OK=0
        fi
        AUDIT_TOTAL=$(grep -o '"total":[0-9]*' .codex-plugin-audit.json 2>/dev/null | head -n1 | awk -F: '{print $2}' || echo 0)
        log " Plugin audit total plugins: ${AUDIT_TOTAL} (ok=${AUDIT_OK})";
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
    if grep -q '"total"' .codex-plugin-audit.json 2>/dev/null; then
      TOTAL=$(grep -o '"total":[0-9]*' .codex-plugin-audit.json | head -n1 | awk -F: '{print $2}')
      EXTRA="$EXTRA,\"plugin_total\":$TOTAL"
    fi
    if [ -f .codex-plugin-audit.err ]; then
      EXTRA="$EXTRA,\"plugin_audit_ok\":false"
    else
      EXTRA="$EXTRA,\"plugin_audit_ok\":true"
    fi
  fi
  [ -n "$REDIRECT_CHAIN" ] && EXTRA="$EXTRA,\"redirect_chain\":\"$REDIRECT_CHAIN\""
  [ -n "$FINAL_PORT" ] && EXTRA="$EXTRA,\"final_port\":\"$FINAL_PORT\""
  [ $QUICK -eq 1 ] && EXTRA="$EXTRA,\"quick\":true"
  printf '{"core_version":"%s","http_code":"%s","http_time":"%s","core_update":%s,"plugin_updates":%s,"theme_updates":%s,"offline":"%s","ok":%s,"duration_s":%s%s}"\n' \
    "$CORE_VERSION" "$HTTP_CODE" "$HTTP_TIME" "$CORE_UPDATE" "$PLUGIN_UPDATES" "$THEME_UPDATES" "$OFFLINE_VERIFY" "$STATUS_OK" "$duration" "$EXTRA"
fi

exit $([ $STATUS_OK -eq 1 ] && echo 0 || echo 1)
