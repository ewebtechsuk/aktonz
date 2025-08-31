#!/usr/bin/env bash
# plugin_cleanup_plan.sh - Generate actions based on desired user decisions
# Decisions encoded:
# 1. Keep both Elementor (free+pro) and Jet Engine (accepted)
# 3. Remove WooCommerce Legacy REST API (woocommerce-legacy-rest-api)
# 4. Keep Yoast Premium (remove free wordpress-seo after confirming premium active)
# 5. Review large plugins (just listing top heavy by file count)
# 6. Provide detailed size + suggested removal list
set -euo pipefail
PLUG_DIR="wp-content/plugins"
[ -d "$PLUG_DIR" ] || { echo "No plugins dir" >&2; exit 1; }

# Gather sizes (file count heuristic)
mapfile -t entries < <(find "$PLUG_DIR" -maxdepth 1 -mindepth 1 -type d -printf '%f\n' | sort)

size_report=()
for p in "${entries[@]}"; do
  cnt=$(find "$PLUG_DIR/$p" -type f -name '*.php' -o -name '*.js' 2>/dev/null | wc -l | awk '{print $1}')
  size_report+=("$cnt:$p")
done

# Sort descending by count
IFS=$'\n' sorted=($(sort -t: -k1,1nr <<<"${size_report[*]}")); unset IFS

cat <<'HDR'
=== Plugin Cleanup Plan ===
Decisions Applied:
  * Keep: elementor, elementor-pro, jet-engine
  * Remove: woocommerce-legacy-rest-api (legacy API not needed unless specific integration)
  * Keep Premium SEO: wordpress-seo-premium; plan to remove free wordpress-seo after verifying migration
  * Evaluate Crocoblock jet-* modules; disable those not in active site features

Recommended Removals (if unused):
  - woocommerce-legacy-rest-api
  - wordpress-seo (after confirming premium license functioning)
  - crocoblock-activator (if only used for initial activation)
  - redundant jet-* modules (booking, appointments, product tables, etc. if not used)
  - litespeed-cache.disabled (either re-enable as only cache or delete)

Largest by file count (focus for performance review):
HDR

for line in "${sorted[@]:0:15}"; do
  echo "  $line"
done

cat <<'NEXT'

Next Steps (with wp-cli once DB works):
  wp plugin deactivate woocommerce-legacy-rest-api --allow-root
  wp plugin delete woocommerce-legacy-rest-api --allow-root
  wp plugin deactivate wordpress-seo --allow-root  # after confirming premium functioning
  wp plugin delete wordpress-seo --allow-root
  # Example selective deactivation:
  # wp plugin deactivate jet-booking jet-appointments-booking jet-product-tables --allow-root

Fallback (no DB yet): manually remove directories:
  rm -rf wp-content/plugins/woocommerce-legacy-rest-api
  rm -rf wp-content/plugins/wordpress-seo (ONLY after confirming premium)

Safety Tips:
  * Take a full backup before deletions
  * After removals, clear object & page caches
  * Re-test critical flows (checkout, forms, dynamic listings)

=== End Plan ===
NEXT
