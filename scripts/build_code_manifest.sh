#!/usr/bin/env bash
# build_code_manifest.sh
# Generate deterministic hash manifest of tracked plugin & theme directories in the repo.
# Outputs three environment-friendly lines:
#   CODE_MANIFEST_HASH=<overall sha256>
#   PLUGIN_HASHES_JSON=<json array of {slug,hash,files} with shortened hash>
#   THEME_HASHES_JSON=<json array likewise>
# Designed to avoid requiring jq (pure bash + coreutils). If jq available, it will pretty-print to artifact file.
set -euo pipefail
ROOT_DIR=$(git rev-parse --show-toplevel 2>/dev/null || pwd)
cd "$ROOT_DIR"
BASE="wp-content"
PLUG_DIR="$BASE/plugins"
THEME_DIR="$BASE/themes"
TMP_MANIFEST=$(mktemp)
PLUGIN_JSON="[]"
THEME_JSON="[]"
calc_dir_hash() {
  local dir="$1"
  # List regular files (exclude .git keep) sorted, hash each then overall hash of the listing.
  if [ ! -d "$dir" ]; then echo ""; return 0; fi
  find "$dir" -type f -not -path '*/.git/*' -printf '%P\n' | LC_ALL=C sort | while IFS= read -r f; do
    sha=$(sha256sum "$dir/$f" | awk '{print $1}')
    printf '%s  %s\n' "$sha" "$f"
  done | sha256sum | awk '{print $1}'
}
collect_group() {
  local parent="$1"; local -n OUT_JSON_REF=$2; local type_label="$3"
  [ -d "$parent" ] || { OUT_JSON_REF='[]'; return 0; }
  local entries=()
  while IFS= read -r d; do
    [ -z "$d" ] && continue
    slug=$(basename "$d")
    # skip disabled copies and hidden dirs
    case "$slug" in
      *.disabled*|_off_*|.*) continue;;
    esac
    hash=$(calc_dir_hash "$d" || true)
    short=${hash:0:16}
    # Build minimal JSON object (escape slug just in case)
    esc_slug=$(printf '%s' "$slug" | sed 's/"/\\"/g')
    entries+=("{\"slug\":\"$esc_slug\",\"hash\":\"$short\"}")
  done < <(find "$parent" -mindepth 1 -maxdepth 1 -type d -printf '%p\n' | LC_ALL=C sort)
  if [ ${#entries[@]} -gt 0 ]; then
    local joined
    joined=$(IFS=,; echo "${entries[*]}")
    OUT_JSON_REF="[$joined]"
  else
    OUT_JSON_REF='[]'
  fi
}
collect_group "$PLUG_DIR" PLUGIN_JSON plugins
collect_group "$THEME_DIR" THEME_JSON themes
# Overall combined hash: stable JSON ordering + sha256
COMBINED=$(printf '{"plugins":%s,"themes":%s}' "$PLUGIN_JSON" "$THEME_JSON")
CODE_HASH=$(printf '%s' "$COMBINED" | sha256sum | awk '{print $1}')
# Export lines for GitHub Actions step parsing
printf 'CODE_MANIFEST_HASH=%s\n' "$CODE_HASH"
printf 'PLUGIN_HASHES_JSON=%s\n' "$PLUGIN_JSON"
printf 'THEME_HASHES_JSON=%s\n' "$THEME_JSON"
# Write artifact file (pretty if jq present)
ARTIFACT=code-manifest.json
if command -v jq >/dev/null 2>&1; then
  printf '%s' "$COMBINED" | jq '.' > "$ARTIFACT"
else
  printf '%s' "$COMBINED" > "$ARTIFACT"
fi
printf '[manifest] Generated code manifest: %s (overall hash %s)\n' "$ARTIFACT" "$CODE_HASH" >&2
