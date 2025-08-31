#!/usr/bin/env bash
# Toggle WordPress safe mode (minimal plugins + default theme) without losing original state.
# Usage:
#   ./scripts/safe_mode.sh enable   # stores current active plugins + theme, then disables plugins
#   ./scripts/safe_mode.sh disable  # restores previous active plugins + theme
#   ./scripts/safe_mode.sh status   # shows stored state
# Requires: wp-cli present & WP root as current working directory (or set WP_PATH env).
set -euo pipefail
WP_PATH=${WP_PATH:-$(pwd)}
STATE_DIR="${WP_PATH}/wp-content/.safe_mode_state"
PLUGINS_FILE="$STATE_DIR/active_plugins.txt"
THEME_FILE="$STATE_DIR/theme.txt"

wp_cmd() { wp --path="$WP_PATH" "$@"; }

ensure_wp() { command -v wp >/dev/null 2>&1 || { echo "wp-cli not found" >&2; exit 1; }; }

enable_safe_mode() {
  mkdir -p "$STATE_DIR"
  if [[ -f "$PLUGINS_FILE" ]]; then
    echo "Safe mode already enabled (state exists)."; return 0;
  fi
  echo "Capturing current active plugins...";
  wp_cmd plugin list --status=active --field=name > "$PLUGINS_FILE" || true
  echo "Capturing current theme...";
  wp_cmd theme list --status=active --field=name | head -n1 > "$THEME_FILE" || true
  echo "Disabling all plugins...";
  while read -r p; do [[ -n "$p" ]] && wp_cmd plugin deactivate "$p" || true; done < "$PLUGINS_FILE"
  echo "Switching to default theme (twentytwentyfive preferred)...";
  wp_cmd theme activate twentytwentyfive 2>/dev/null || wp_cmd theme activate twentytwentythree 2>/dev/null || true
  echo "Safe mode enabled.";
}

disable_safe_mode() {
  if [[ ! -f "$PLUGINS_FILE" ]]; then
    echo "No stored state; cannot restore." >&2; exit 1; fi
  echo "Restoring theme...";
  if [[ -f "$THEME_FILE" ]]; then
    ORIG_THEME=$(cat "$THEME_FILE")
    [[ -n "$ORIG_THEME" ]] && wp_cmd theme activate "$ORIG_THEME" || true
  fi
  echo "Re-activating plugins...";
  while read -r p; do [[ -n "$p" ]] && wp_cmd plugin activate "$p" || true; done < "$PLUGINS_FILE"
  rm -rf "$STATE_DIR"
  echo "Safe mode disabled; original state restored.";
}

status_safe_mode() {
  if [[ -f "$PLUGINS_FILE" ]]; then
    echo "Safe mode: ENABLED";
    echo "Stored plugins:"; cat "$PLUGINS_FILE" || true
    echo "Stored theme:"; cat "$THEME_FILE" || true
  else
    echo "Safe mode: DISABLED";
  fi
}

main() {
  ensure_wp
  ACTION=${1:-}
  case "$ACTION" in
    enable) enable_safe_mode ;;
    disable) disable_safe_mode ;;
    status) status_safe_mode ;;
    *) echo "Usage: $0 {enable|disable|status}" >&2; exit 1 ;;
  esac
}

main "$@"

#!/usr/bin/env bash
# Purpose: Temporarily force WordPress into a "safe mode" with only a minimal allowlist of plugins enabled.
# Strategy:
# 1. Move (rename) every plugin directory not in allowlist to *_off_safemode to disable them.
# 2. Create a MU plugin that flags safe mode and logs its activation.
# 3. Provide a restore function (when run with --restore) that reverts *_off_safemode back.
# Idempotent: multiple runs with same mode won't damage state.

set -euo pipefail
shopt -s nullglob

ROOT=${WP_ROOT:-$(pwd)}
PLUGINS_DIR="$ROOT/wp-content/plugins"
MU_DIR="$ROOT/wp-content/mu-plugins"
ALLOWLIST_CSV=${ALLOWLIST:-"akismet,wordpress-seo,query-monitor"}
MODE="activate"
if [[ ${1:-} == "--restore" ]]; then
  MODE="restore"
fi

IFS=',' read -r -a ALLOW <<<"$ALLOWLIST_CSV"
normalize() { printf '%s' "$1" | tr 'A-Z' 'a-z'; }

is_allowed() {
  local t=$(normalize "$1")
  for a in "${ALLOW[@]}"; do
    [[ $(normalize "$a") == "$t" ]] && return 0
  done
  return 1
}

log() { printf '[safe-mode] %s\n' "$*"; }

do_activate() {
  mkdir -p "$MU_DIR"
  # Create MU plugin marker
  cat >"$MU_DIR/zzz-safe-mode.php" <<'PHP'
<?php
/*
 Plugin Name: CI Safe Mode Marker
 Description: Indicates site is in temporary CI safe mode.
*/
if (!defined('CI_SAFE_MODE')) define('CI_SAFE_MODE', true);
error_log('[safe-mode] mu-plugin active');
PHP
  for d in "$PLUGINS_DIR"/*; do
    [[ -d "$d" ]] || continue
    base=$(basename "$d")
    if [[ "$base" == *_off_safemode ]]; then
      continue
    fi
    if is_allowed "$base"; then
      log "allow $base"
      continue
    fi
    target="${d}_off_safemode"
    if [[ -e "$target" ]]; then
      log "already disabled $base"
    else
      log "disabling $base -> $(basename "$target")"
      mv "$d" "$target"
    fi
  done
  log "activation complete"
}

do_restore() {
  for d in "$PLUGINS_DIR"/*_off_safemode; do
    base=$(basename "$d")
    orig=${d%_off_safemode}
    if [[ -e "$orig" ]]; then
      log "original exists for $(basename "$orig") - skipping rename back"
    else
      log "restoring $(basename "$orig")"
      mv "$d" "$orig"
    fi
  done
  rm -f "$MU_DIR/zzz-safe-mode.php" || true
  log "restore complete"
}

case "$MODE" in
  activate) do_activate ;;
  restore) do_restore ;;
  *) echo "Usage: $0 [--restore]" >&2; exit 1 ;;
endsac
