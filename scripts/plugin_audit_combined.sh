#!/usr/bin/env bash
# plugin_audit_combined.sh - Extended plugin audit (filesystem heuristic) producing rich JSON.
# Outputs JSON: { total, plugins:[{name,category,file_count,bytes,age_days}], recommendations:[...] }
# NOTE: With set -e enabled, bare conditional tests must not cause exit; wrap pattern matches in if blocks.
set -euo
PLUG_DIR="wp-content/plugins"
[ -d "$PLUG_DIR" ] || { echo '{}'; exit 0; }
now_ts=$(date +%s)

json_escape(){ printf '%s' "$1" | sed 's/"/\\"/g'; }

# Enumerate plugin directories
mapfile -t plugins < <(find "$PLUG_DIR" -maxdepth 1 -mindepth 1 -type d -printf '%f\n' | sort)

heavy_core=(woocommerce elementor elementor-pro jet-engine jetformbuilder wordpress-seo wordpress-seo-premium)
match(){ local x=$1; shift; for a in "$@"; do [ "$x" = "$a" ] && return 0; done; return 1; }

printf '{"total":%s,"plugins":[' "${#plugins[@]}"
first=1
for p in "${plugins[@]}"; do
  file_count=$(find "$PLUG_DIR/$p" -type f \( -name '*.php' -o -name '*.js' \) -not -path '*/vendor/*' 2>/dev/null | wc -l | awk '{print $1}')
  bytes=$(du -sk "$PLUG_DIR/$p" 2>/dev/null | awk '{print $1*1024}')
  mtime=$(find "$PLUG_DIR/$p" -type f -printf '%T@\n' 2>/dev/null | sort -nr | head -n1)
  [ -n "$mtime" ] || mtime=$now_ts
  age_days=$(( (now_ts - ${mtime%.*}) / 86400 ))
  cat_flag=other
  if match "$p" "${heavy_core[@]}"; then cat_flag=heavy; fi
  if [[ $p == jet-* || $p == crocoblock-* ]]; then cat_flag=jet; fi
  if [[ $p == *stripe* || $p == woo-stripe-payment* ]]; then cat_flag=stripe; fi
  if [[ $p == litespeed-cache* ]]; then cat_flag=cache; fi
  if [[ $p == hide-my-wp* ]]; then cat_flag=security; fi
  if [ $first -eq 0 ]; then printf ','; else first=0; fi
  printf '{"name":"%s","category":"%s","file_count":%s,"bytes":%s,"age_days":%s}' "$(json_escape "$p")" "$cat_flag" "$file_count" "$bytes" "$age_days"
done
printf '],"recommendations":['

rec=(
  "Remove legacy woocommerce-legacy-rest-api if unused"
  "Keep only Yoast Premium; remove free after confirming data migration"
  "Audit Jet (jet-*) modules; deactivate unused"
  "Ensure only one cache plugin enabled"
  "Review largest plugins for performance impact"
)
[ -d "$PLUG_DIR/wordpress-seo" ] && [ -d "$PLUG_DIR/wordpress-seo-premium" ] && rec+=("Both Yoast free + premium present; plan removal of free")
[ -d "$PLUG_DIR/litespeed-cache.disabled" ] && rec+=("Decide to enable or remove litespeed-cache.disabled directory")

first=1
for i in "${!rec[@]}"; do
  if [ $first -eq 0 ]; then printf ','; else first=0; fi
  printf '"%s"' "$(json_escape "${rec[$i]}")"
done
printf ']}'
exit 0
