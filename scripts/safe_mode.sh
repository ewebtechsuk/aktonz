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
