#!/usr/bin/env bash
# plugin_conflict_audit.sh - Heuristic plugin conflict & bloat audit without DB (file-system level)
# Usage: bash scripts/plugin_conflict_audit.sh
set -euo pipefail
PLUG_DIR="wp-content/plugins"
[ -d "$PLUG_DIR" ] || { echo "No plugins dir" >&2; exit 1; }

json_escape(){ printf '%s' "$1" | sed 's/"/\\"/g'; }

# Collect basic metadata
plugins=()
while IFS= read -r -d '' p; do plugins+=("$p"); done < <(find "$PLUG_DIR" -maxdepth 1 -mindepth 1 -type d -printf '%f\0')

# Categorize
GROUP_CORE=(woocommerce wordpress-seo wordpress-seo-premium jet-engine elementor elementor-pro litespeed-cache) # core heavy hitters
GROUP_CROC=(jet-* crocoblock-* )

is_match(){ local name=$1 pat; shift; for pat in "$@"; do if [[ $name == $pat ]]; then return 0; fi; done; return 1; }

HEAVY=(); CROC=(); STRIPE=(); CACHE=(); SECURITY=(); MISC=();
for plug in "${plugins[@]}"; do
  case "$plug" in
    woo-*) STRIPE+=("$plug");; # simplistic pattern bucket (Stripe gateway or woo extensions)
  esac
  [[ $plug == *stripe* ]] && STRIPE+=("$plug") || true
  [[ $plug == litespeed-cache* ]] && CACHE+=("$plug") || true
  [[ $plug == hide-my-wp* ]] && SECURITY+=("$plug") || true
  if is_match "$plug" "${GROUP_CORE[@]}"; then HEAVY+=("$plug")
  elif [[ $plug == jet-* || $plug == crocoblock-* ]]; then CROC+=("$plug")
  else MISC+=("$plug")
  fi
done

# Estimate code size per plugin (PHP + JS) ignoring vendor
sizes_json="[]"
for plug in "${plugins[@]}"; do
  sz=$(find "$PLUG_DIR/$plug" -type f \( -name '*.php' -o -name '*.js' \) -not -path '*/vendor/*' -printf '.' 2>/dev/null | wc -c | awk '{print $1}')
  sizes_json=$(printf '%s\n{"name":"%s","approx_files":%s}' "$sizes_json" "$plug" "$sz")
  # We count number of files (approx by dot count) rather than bytes for speed.
done
sizes_json=$(echo "$sizes_json" | awk 'BEGIN{print "["} NR>1{if(NR>2)printf ","; printf $0} END{print "]"}')

# Output JSON summary
cat <<JSON
{
  "total_plugins": ${#plugins[@]},
  "heavy_core": ["${HEAVY[@]}"],
  "crocoblock_cluster": ["${CROC[@]}"],
  "stripe_related": ["${STRIPE[@]}"],
  "cache": ["${CACHE[@]}"],
  "security": ["${SECURITY[@]}"],
  "misc_count": ${#MISC[@]},
  "size_approx": $sizes_json,
  "advice": [
    "Reduce overlapping page builder / dynamic content plugins if not all required",
    "Audit Crocoblock Jet suite; disable unused modules to cut hooks and queries",
    "Ensure only one persistent cache solution (avoid stack conflicts)",
    "Keep Stripe gateway + WooCommerce updated; remove legacy REST API if unused",
    "Deactivate duplicate SEO plugins (Yoast free vs premium pair ok; avoid multiples)",
    "Remove orphan admin utilities not providing active value"
  ]
}
JSON
