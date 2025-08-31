#!/usr/bin/env bash
# package_site.sh - Create a deployable archive (zip or tar.gz) for manual / FTP upload
# Mirrors (and extends) rsync exclude rules so you don't ship large/volatile dirs.
# Features:
#   * Minimal mode (only WP core + themes + plugins + key root files)
#   * Fast tar mode using pigz if available
#   * Pre-flight size summary & estimated final size
#   * Progress output (no silent large temp file growth)
# Usage:
#   scripts/package_site.sh [--zip|--tar] [--fast] [--minimal] [--output DIR] [--name NAME]
# Defaults:
#   Format: zip, Output dir: dist/, Name: site-package-<timestamp>
set -euo pipefail
FMT=zip
OUT_DIR=dist
FAST=0
MINIMAL=0
NAME=""
while [ $# -gt 0 ]; do
  case "$1" in
  --zip) FMT=zip; shift ;;
  --tar) FMT=tar; shift ;;
  --fast) FAST=1; shift ;;
  --minimal) MINIMAL=1; shift ;;
    --output) OUT_DIR="$2"; shift 2 ;;
  --name) NAME="$2"; shift 2 ;;
    -h|--help) sed -n '1,60p' "$0"; exit 0 ;;
    *) echo "Unknown arg: $1" >&2; exit 1 ;;
  esac
done

mkdir -p "$OUT_DIR"
STAMP=$(date -u +%Y%m%d%H%M%S)
BASE=${NAME:-site-package-$STAMP}
ARCHIVE_ZIP="$OUT_DIR/$BASE.zip"
ARCHIVE_TAR="$OUT_DIR/$BASE.tar.gz"

EXCLUDES_COMMON=(
  .git
  backups
  offline-cache
  wp-lite
  node_modules
  vendor
  '*.log'
  '*.sql'
  '*.wpress'
  '.env'
  '.env.deploy'
  'deploy_aktonz_key'
  'deploy_aktonz_key.pub'
)

# In full mode we also exclude uploads (to keep archive small) – user can sync media separately.
EXCLUDES_FULL_EXTRA=(wp-content/uploads)

# Minimal mode: we explicitly include a whitelist instead of excluding.
WHITELIST_MINIMAL=(
  wp-admin
  wp-includes
  wp-content/themes
  wp-content/plugins
  index.php wp-config.php wp-settings.php wp-load.php wp-blog-header.php
  xmlrpc.php wp-cron.php wp-login.php
)

log(){ printf '[package] %s\n' "$*"; }
human(){ num=$1; awk -v n="$num" 'BEGIN{ split("B KB MB GB TB",u); s=1; while(n>=1024 && s<5){n/=1024; s++} printf "%.2f %s", n, u[s] }'; }

calc_size(){ du -s --bytes "$1" 2>/dev/null | awk '{print $1}'; }

log "Mode: ${FMT} minimal=$MINIMAL fast=$FAST"

# Pre-flight size summary
TOTAL_SIZE=0
if [ $MINIMAL -eq 0 ]; then
  # full mode size estimate (excluding common excludes & uploads)
  for d in wp-admin wp-includes wp-content/themes wp-content/plugins; do
    if [ -d "$d" ]; then sz=$(calc_size "$d"); TOTAL_SIZE=$((TOTAL_SIZE+sz)); log "Size $d: $(human $sz)"; fi
  done
else
  for d in "${WHITELIST_MINIMAL[@]}"; do
    # skip non-existent files gracefully
    if [ -e "$d" ]; then
      if [ -d "$d" ]; then sz=$(calc_size "$d"); TOTAL_SIZE=$((TOTAL_SIZE+sz)); log "Size $d: $(human $sz)"; else sz=$(stat -c '%s' "$d" 2>/dev/null || echo 0); TOTAL_SIZE=$((TOTAL_SIZE+sz)); fi
    fi
  done
fi
log "Estimated packaged size (pre-compress): $(human $TOTAL_SIZE)"

if [ $MINIMAL -eq 1 ]; then
  # Build a staging directory to package minimal contents
  STAGE=.package_stage_$STAMP
  rm -rf "$STAGE"; mkdir -p "$STAGE"
  log "Assembling minimal whitelist into $STAGE"
  for item in "${WHITELIST_MINIMAL[@]}"; do
    [ -e "$item" ] || continue
    if [ -d "$item" ]; then
      mkdir -p "$STAGE/$item"
      rsync -a --delete "$item/" "$STAGE/$item/" >/dev/null 2>&1 || true
    else
      mkdir -p "$STAGE"
      cp -p "$item" "$STAGE/" 2>/dev/null || true
    fi
  done
  PACKAGE_ROOT="$STAGE"
  EXCLUDES_EFFECTIVE=("${EXCLUDES_COMMON[@]}") # uploads already not copied unless whitelisted
else
  PACKAGE_ROOT="."
  EXCLUDES_EFFECTIVE=("${EXCLUDES_COMMON[@]}" "${EXCLUDES_FULL_EXTRA[@]}")
fi

if [ "$FMT" = zip ]; then
  command -v zip >/dev/null 2>&1 || { echo "Need 'zip' installed" >&2; exit 1; }
  ARGS=(-r "$ARCHIVE_ZIP" "$PACKAGE_ROOT")
  for e in "${EXCLUDES_EFFECTIVE[@]}"; do ARGS+=( -x "$PACKAGE_ROOT/$e" -x "$e" ); done
  log "Creating $ARCHIVE_ZIP"
  zip "${ARGS[@]}"
  log "Done -> $ARCHIVE_ZIP"
else
  command -v tar >/dev/null 2>&1 || { echo "Need 'tar' installed" >&2; exit 1; }
  COMPRESS_PROG=""
  if [ $FAST -eq 1 ] && command -v pigz >/dev/null 2>&1; then COMPRESS_PROG="--use-compress-program=pigz"; fi
  TAR_EXC=()
  for e in "${EXCLUDES_EFFECTIVE[@]}"; do TAR_EXC+=( --exclude="$e" ); done
  log "Creating $ARCHIVE_TAR (fast=$FAST)"
  if [ -n "$COMPRESS_PROG" ]; then
    tar ${TAR_EXC[@]} -cf - -C "$PACKAGE_ROOT" . | pigz -c > "$ARCHIVE_TAR"
  else
    tar ${TAR_EXC[@]} -czf "$ARCHIVE_TAR" -C "$PACKAGE_ROOT" .
  fi
  log "Done -> $ARCHIVE_TAR"
fi

if [ $MINIMAL -eq 1 ]; then
  rm -rf "$STAGE"
fi

log "Upload and extract in your public_html. Media (uploads) not included."
