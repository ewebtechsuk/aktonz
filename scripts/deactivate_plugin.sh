#!/usr/bin/env bash
# Deactivate a WordPress plugin via WP-CLI or by renaming its folder.
# Usage: ./scripts/deactivate_plugin.sh [plugin-slug]
# Default plugin is 'elementor'.
set -euo pipefail
PLUGIN=${1:-elementor}
WP_PATH=${WP_PATH:-$(pwd)}
wp_cmd() { wp --path="$WP_PATH" "$@"; }
if command -v wp >/dev/null 2>&1; then
  if wp_cmd plugin is-active "$PLUGIN" >/dev/null 2>&1; then
    if wp_cmd plugin deactivate "$PLUGIN"; then
      echo "Deactivated $PLUGIN via WP-CLI."
      exit 0
    fi
  fi
fi
PLUGIN_DIR="${WP_PATH}/wp-content/plugins/${PLUGIN}"
if [ -d "$PLUGIN_DIR" ]; then
  mv "$PLUGIN_DIR" "${PLUGIN_DIR}-disabled"
  echo "Renamed $PLUGIN_DIR to ${PLUGIN_DIR}-disabled to disable the plugin."
else
  echo "Plugin directory $PLUGIN_DIR not found." >&2
  exit 1
fi
