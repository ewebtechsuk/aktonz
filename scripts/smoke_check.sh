#!/usr/bin/env bash
# Enhanced smoke test (homepage + admin) with thresholds, hashing, size delta & artifact capture.
# Usage: smoke_check.sh <base_url>
# Config via env:
#   SMOKE_ALLOW_FAILURE (default false)
#   SMOKE_MAX_HOME_MS (e.g. 4000)
#   SMOKE_MAX_ADMIN_MS (e.g. 5000)
#   SMOKE_MAX_REDIRECTS (e.g. 4)
#   SMOKE_ARTIFACT_DIR (default smoke_artifacts)
#   SMOKE_MAX_SIZE_DELTA_PCT (integer percent, e.g. 60) triggers threshold fail if exceeded
#   PREV_HOMEPAGE_HASH / PREV_ADMIN_HASH / PREV_HOMEPAGE_SIZE / PREV_ADMIN_SIZE provided by workflow (optional)
# Robust mode: don't abort entire script on single check failure; we manage exit codes manually.
set -Euo pipefail
BASE_URL="${1:-${PRODUCTION_URL:-}}"
ALLOW_FAILURE="${SMOKE_ALLOW_FAILURE:-false}"
NONBLOCKING="${SMOKE_NONBLOCKING:-false}"
MAX_HOME_MS="${SMOKE_MAX_HOME_MS:-0}"  # 0 means disabled
MAX_ADMIN_MS="${SMOKE_MAX_ADMIN_MS:-0}"
MAX_REDIRECTS="${SMOKE_MAX_REDIRECTS:-0}"
MAX_SIZE_DELTA_PCT="${SMOKE_MAX_SIZE_DELTA_PCT:-0}"
ART_DIR="${SMOKE_ARTIFACT_DIR:-smoke_artifacts}"
if [ -z "$BASE_URL" ]; then
  echo "[smoke] ERROR: Base URL not provided (arg1 or PRODUCTION_URL env)." >&2
  exit 2
fi
BASE_URL="${BASE_URL%/}"
CURL_BIN="curl"
if ! command -v curl >/dev/null 2>&1; then
  echo "[smoke] ERROR: curl not installed" >&2
  exit 3
fi
mkdir -p "$ART_DIR" 2>/dev/null || true
META_JSON="$ART_DIR/metrics.json"
echo '{"checks":[]}' > "$META_JSON"

# Append JSON helper (very small, manual since jq not guaranteed)
append_json() {
  # args: label code ms redirects size url_eff fail reason
  local label="$1" code="$2" ms="$3" redirects="$4" size="$5" url_eff="$6" fail="$7" reason="$8"
  # naive insertion before final ]
  local entry
  entry=$(printf '{"label":"%s","code":%s,"time_ms":%s,"redirects":%s,"size":%s,"final_url":"%s","failed":%s,"reason":"%s"}' \
    "$label" "$code" "$ms" "$redirects" "$size" "$url_eff" "$fail" "$reason")
  # shell json append (safe enough for controlled values without quotes in reason)
  sed -i "s/\"checks\":\[/\"checks\":[$entry,/" "$META_JSON" || true
}

threshold_violation() {
  local label="$1" ms="$2" redirects="$3"
  local reasons=()
  if [ "$label" = HOME ] && [ "$MAX_HOME_MS" != 0 ] && [ "$ms" -gt "$MAX_HOME_MS" ]; then reasons+=("slow>${MAX_HOME_MS}ms"); fi
  if [ "$label" = ADMIN ] && [ "$MAX_ADMIN_MS" != 0 ] && [ "$ms" -gt "$MAX_ADMIN_MS" ]; then reasons+=("slow>${MAX_ADMIN_MS}ms"); fi
  if [ "$MAX_REDIRECTS" != 0 ] && [ "$redirects" -gt "$MAX_REDIRECTS" ]; then reasons+=("redirects>${MAX_REDIRECTS}"); fi
  if [ ${#reasons[@]} -gt 0 ]; then
    printf '%s' "${reasons[*]}"
    return 0
  fi
  return 1
}

run_check() {
  local path="$1"; shift
  local label="$1"; shift
  local url="$BASE_URL$path"
  local body_file
  body_file=$(mktemp)
  local metrics
  metrics=$($CURL_BIN -s -L -o "$body_file" -w '%{http_code} %{time_total} %{size_download} %{url_effective} %{num_redirects}' "$url" || echo "000 0 0 - 0")
  read -r code t_total size url_eff redirects <<<"$metrics"
  local ms
  ms=$(awk -v t="$t_total" 'BEGIN{printf "%.0f", t*1000}')
  echo "[$label] HTTP $code time=${ms}ms size=${size}B redirects=$redirects url=$url_eff"
  head -n 50 "$body_file" | sed "s/^/[$label][body]/"
  # Compute body hash & size (size already captured, but keep consistent)
  local body_hash
  if command -v sha256sum >/dev/null 2>&1; then
    body_hash=$(sha256sum "$body_file" | awk '{print $1}')
  else
    body_hash="hash_unavailable"
  fi
  local fail=false reason=""
  if [ "$code" -ge 500 ]; then fail=true; reason="http${code}"; fi
  # "critical error" banner detection (non-fatal if not present)
  if grep -qi 'critical error' "$body_file" 2>/dev/null; then fail=true; reason="${reason:+$reason,}critical"; fi
  # Size delta vs previous (if provided)
  local prev_size prev_hash delta_pct
  case "$label" in
    HOME) prev_size="${PREV_HOMEPAGE_SIZE:-}"; prev_hash="${PREV_HOMEPAGE_HASH:-}";;
    ADMIN) prev_size="${PREV_ADMIN_SIZE:-}"; prev_hash="${PREV_ADMIN_HASH:-}";;
  esac
  if [ -n "$prev_size" ] && echo "$prev_size" | grep -Eq '^[0-9]+$' && [ "$prev_size" -gt 0 ]; then
    delta_pct=$(( ( (size - prev_size) * 100 ) / prev_size ))
    # absolute percent
    if [ $delta_pct -lt 0 ]; then
      delta_pct=$(( -1 * delta_pct ))
    fi
    if [ "$MAX_SIZE_DELTA_PCT" != 0 ] && [ "$delta_pct" -gt "$MAX_SIZE_DELTA_PCT" ]; then
      fail=true
      reason="${reason:+$reason,}size_delta>${MAX_SIZE_DELTA_PCT}%"
    fi
    # Export delta pct env
    if [ -n "${GITHUB_ENV:-}" ]; then
      case "$label" in
        HOME) echo "HOMEPAGE_SIZE_DELTA_PCT=$delta_pct" >> "$GITHUB_ENV" ;;
        ADMIN) echo "ADMIN_SIZE_DELTA_PCT=$delta_pct" >> "$GITHUB_ENV" ;;
      esac
    fi
  fi
  if [ -n "$prev_hash" ] && [ "$prev_hash" != "$body_hash" ]; then
    reason="${reason:+$reason,}hash_changed"
  fi
  tv=""
  if tv=$(threshold_violation "$label" "$ms" "$redirects"); then
    fail=true
    reason="${reason:+$reason,}threshold:${tv}"
  fi
  # export metrics
  if [ -n "${GITHUB_ENV:-}" ]; then
    case "$label" in
    HOME)
        {
          echo "HOMEPAGE_STATUS=$code"
          echo "HOMEPAGE_TIME_MS=$ms"
          echo "HOMEPAGE_REDIRECTS=$redirects"
      echo "HOMEPAGE_HASH=$body_hash"
      echo "HOMEPAGE_SIZE=$size"
          if [[ $reason == *threshold* ]]; then echo "HOMEPAGE_THRESHOLD_FAIL=true"; fi
        } >> "$GITHUB_ENV"
        ;;
    ADMIN)
        {
          echo "ADMIN_STATUS=$code"
          echo "ADMIN_TIME_MS=$ms"
          echo "ADMIN_REDIRECTS=$redirects"
      echo "ADMIN_HASH=$body_hash"
      echo "ADMIN_SIZE=$size"
          if [[ $reason == *threshold* ]]; then echo "ADMIN_THRESHOLD_FAIL=true"; fi
        } >> "$GITHUB_ENV"
        ;;
    esac
  fi
  # copy bodies for artifact
  cp "$body_file" "$ART_DIR/${label,,}.html" 2>/dev/null || true
  append_json "$label" "$code" "$ms" "$redirects" "$size" "$url_eff" "$fail" "${reason:-none}" || true
  # Export reason string for summary usage
  if [ -n "${GITHUB_ENV:-}" ]; then
    case "$label" in
      HOME) echo "HOMEPAGE_REASON=${reason:-none}" >> "$GITHUB_ENV" ;;
      ADMIN) echo "ADMIN_REASON=${reason:-none}" >> "$GITHUB_ENV" ;;
    esac
  fi
  rm -f "$body_file" || true
  if [ "$fail" = true ]; then
    echo "[$label] FAIL reason=$reason" >&2
    if [ "$ALLOW_FAILURE" != "true" ]; then
      return 10
    else
      echo "[smoke] ALLOW_FAILURE=true => not failing pipeline" >&2
    fi
  else
    echo "[$label] PASS" >&2
  fi
  return 0
}

rc_home=0; rc_admin=0
run_check "/" HOME || rc_home=$?
run_check "/wp-admin/" ADMIN || rc_admin=$?

if [ -n "${GITHUB_ENV:-}" ]; then
  echo "HOMEPAGE_RC=$rc_home" >> "$GITHUB_ENV"
  echo "ADMIN_RC=$rc_admin" >> "$GITHUB_ENV"
fi
if [ $rc_home -ne 0 ] || [ $rc_admin -ne 0 ]; then
  echo "[smoke] Overall FAILED (home=$rc_home admin=$rc_admin)" >&2
  if [ -n "${GITHUB_ENV:-}" ]; then echo "SMOKE_OVERALL_STATUS=failed" >> "$GITHUB_ENV"; fi
  if [ "$NONBLOCKING" = "true" ]; then
    echo "[smoke] NONBLOCKING=true -> continuing despite failure" >&2
    exit 0
  fi
  if [ "$ALLOW_FAILURE" != "true" ]; then
    exit 1
  fi
else
  echo "[smoke] Overall PASS (allow_failure=$ALLOW_FAILURE)"
  if [ -n "${GITHUB_ENV:-}" ]; then echo "SMOKE_OVERALL_STATUS=passed" >> "$GITHUB_ENV"; fi
fi
