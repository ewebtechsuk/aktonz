#!/usr/bin/env bash
set -euo pipefail
# Restore critical plugins renamed with _off_ prefix if live dir is absent.
# Usage: HOST=example.com USER=sshuser PORT=65002 PATH=/home/.../public_html ./scripts/restore_critical_plugins.sh

: "${HOST:?HOST required}" 
: "${USER:?USER required}" 
: "${PORT:?PORT required}" 
: "${PATH:?PATH required}" 

CRITICAL_SLUGS=(elementor elementor-pro jet-engine jet-elements jet-theme-core jet-menu jet-tabs jetformbuilder woocommerce wordpress-seo)

for slug in "${CRITICAL_SLUGS[@]}"; do
  off="${PATH%/}/wp-content/plugins/_off_${slug}"
  live="${PATH%/}/wp-content/plugins/${slug}"
  if ssh -p "$PORT" "$USER@$HOST" "[ -d '$off' ]"; then
    if ssh -p "$PORT" "$USER@$HOST" "[ -d '$live' ]"; then
      echo "[restore][skip] live exists for $slug"
    else
      echo "[restore] restoring $slug"
      ssh -p "$PORT" "$USER@$HOST" "mv '$off' '$live'" || echo "[restore][warn] failed to restore $slug"
    fi
  fi
done

echo "[restore] complete" 
