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
