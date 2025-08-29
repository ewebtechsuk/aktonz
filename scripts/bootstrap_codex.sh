#!/usr/bin/env bash
set -euo pipefail

echo "[bootstrap] Start"

CODEX_SCRIPT="scripts/codex.sh"
if [ ! -f "$CODEX_SCRIPT" ]; then
  echo "[bootstrap][error] $CODEX_SCRIPT missing" >&2; exit 1
fi

# Detect legacy hard docker guard (older version) and patch only if needed.
if grep -q 'Required command.*docker' "$CODEX_SCRIPT" && ! grep -q 'requires_full_docker()' "$CODEX_SCRIPT"; then
  echo "[bootstrap] Legacy docker guard detected; creating patched copy"
  cp "$CODEX_SCRIPT" "${CODEX_SCRIPT}.bak_legacy"

  # Strip the early guard block heuristically.
  awk '
    BEGIN{skip=0}
    /Required command.*docker/ { next }
    /if \[\[ "\$COMMAND" != "help"/ { skip=1; next }
    skip && /fi/ { skip=0; next }
    !skip { print }
  ' "$CODEX_SCRIPT" > "${CODEX_SCRIPT}.tmp1" || { echo "[bootstrap][error] awk strip failed" >&2; exit 1; }

  # Insert modern guard after DEFAULT_PROJECT line if missing.
  if ! grep -q 'requires_full_docker()' "${CODEX_SCRIPT}.tmp1"; then
    awk '
      { print }
      /DEFAULT_PROJECT=/ && !done {
        print "";
        print "# --- inserted docker fallback guard (bootstrap) ---";
        print "requires_full_docker(){";
        print "  case \"$COMMAND\" in";
        print "    setup|up|down|recreate|hard-reset|backup-db|restore-db|update|update-core|update-plugins|update-themes|logs|shell|compose|db-size|optimize-db|health|prune) return 0 ;;";
        print "    *) return 1 ;;";
        print "  esac";
        print "}";
        print "docker_available(){ command -v docker >/dev/null 2>&1 && docker info >/dev/null 2>&1; }";
        print "if requires_full_docker && ! docker_available; then";
        print "  if [[ \"$COMMAND\" == \"setup\" ]]; then";
        print "    log \"Docker unavailable; falling back to lite-setup (patched).\"";
        print "    create_env_templates 2>/dev/null || true";
        print "    COMMAND=lite-setup";
        print "  else";
        print "    err \"Docker required for $COMMAND (patched guard)\"; exit 1";
        print "  fi";
        print "fi";
        print "# --- end inserted guard ---";
        done=1;
      }
    ' "${CODEX_SCRIPT}.tmp1" > "${CODEX_SCRIPT}.tmp2"
    mv "${CODEX_SCRIPT}.tmp2" "$CODEX_SCRIPT"
  else
    mv "${CODEX_SCRIPT}.tmp1" "$CODEX_SCRIPT"
  fi
  rm -f "${CODEX_SCRIPT}.tmp1" 2>/dev/null || true
  chmod +x "$CODEX_SCRIPT"
  echo "[bootstrap] Patch applied"
fi

# Determine docker availability (daemon MUST answer).
HAVE_DOCKER=0
if command -v docker >/dev/null 2>&1 && docker info >/dev/null 2>&1; then
  HAVE_DOCKER=1
fi

lite_env_templates() {
  [ -f .env.example ] || cat > .env.example <<'EOF'
PROJECT_NAME=aktonz
WP_VERSION=latest
SITE_HTTP_PORT=8080
SITE_URL=http://localhost:8080
EOF
  [ -f .env ] || cp .env.example .env
}

lite_prefetch() {
  mkdir -p offline-cache
  if [ ! -f offline-cache/wp-cli.phar ]; then
    curl -fsSL https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar -o offline-cache/wp-cli.phar || true
  fi
  if [ ! -f offline-cache/wordpress-latest.tar.gz ]; then
    curl -fsSL https://wordpress.org/latest.tar.gz -o offline-cache/wordpress-latest.tar.gz || true
  fi
}

lite_setup() {
  lite_env_templates
  mkdir -p wp-lite
  if [ ! -f wp-lite/wp-load.php ]; then
    if command -v tar >/dev/null 2>&1 && [ -f offline-cache/wordpress-latest.tar.gz ]; then
      tmp=$(mktemp -d)
      if tar -xzf offline-cache/wordpress-latest.tar.gz -C "$tmp" 2>/dev/null; then
        [ -d "$tmp/wordpress" ] && cp -R "$tmp/wordpress/"* wp-lite/
      fi
      rm -rf "$tmp"
    fi
    if [ ! -f wp-lite/wp-load.php ]; then
      echo "[bootstrap] Direct download fallback (no cache or extraction failed)"
      curl -fsSL https://wordpress.org/latest.tar.gz -o /tmp/wp.tgz || true
      tar -xzf /tmp/wp.tgz -C /tmp 2>/dev/null || true
      [ -d /tmp/wordpress ] && cp -R /tmp/wordpress/* wp-lite/
    fi
  fi
  if [ ! -f wp-lite/wp-config.php ] && [ -f wp-lite/wp-config-sample.php ]; then
    cp wp-lite/wp-config-sample.php wp-lite/wp-config.php
    sed -i "s/database_name_here/none/;s/username_here/none/;s/password_here/none/;s/localhost/localhost/" wp-lite/wp-config.php || true
    echo "define('FS_METHOD','direct');" >> wp-lite/wp-config.php
  fi
}

lite_status_json() {
  local ver="" site="http://localhost:8080"
  if [ -f wp-lite/wp-includes/version.php ]; then
    ver=$(awk -F"'" '/\$wp_version *=/ { for(i=1;i<=NF;i++){ if($i ~ /^[0-9]+(\.[0-9]+)*$/){ printf "%s", $i; exit } } }' wp-lite/wp-includes/version.php 2>/dev/null || true)
  fi
  printf '{"project":"aktonz","mode":"lite","site_url":"%s","core_version":"%s","plugin_updates":0,"http_port":"8080"}\n' "$site" "$ver" > status.json
}

start_php_server() {
  if ! pgrep -f "php -S 127.0.0.1:8080 -t wp-lite" >/dev/null 2>&1; then
    php -S 127.0.0.1:8080 -t wp-lite > /tmp/php-lite-server.log 2>&1 &
    sleep 2
  fi
}

if [ $HAVE_DOCKER -eq 1 ]; then
  echo "[bootstrap] Docker available -> full path"
  bash scripts/codex.sh net-test || true
  bash scripts/codex.sh doctor || true
  bash scripts/codex.sh prefetch-offline || true
  bash scripts/codex.sh setup || true
  bash scripts/codex.sh status-json > status.json || true
  bash scripts/codex.sh status || true
else
  echo "[bootstrap] Docker NOT available -> lite path"
  lite_prefetch
  lite_setup
  lite_status_json
  start_php_server
  curl -fsS http://127.0.0.1:8080/ >/dev/null 2>&1 && echo "[bootstrap] Lite site responding" || echo "[bootstrap][warn] Lite site not responding yet"
fi

echo "[bootstrap] Done"
