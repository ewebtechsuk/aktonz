#!/usr/bin/env bash
# Quick diagnostics for GH_PAT token (classic PAT recommended: scopes repo, workflow)
set -euo pipefail
if [ -z "${GH_PAT:-}" ]; then
  echo "[token][fatal] GH_PAT not set in environment" >&2; exit 1
fi
base_api="https://api.github.com"
repo_slug="ewebtechsuk/aktonz"
header_auth=( -H "Authorization: Bearer $GH_PAT" )
common_headers=( -H "Accept: application/vnd.github+json" )
function hit(){
  local path="$1"; shift || true
  echo "[token][check] GET $path" >&2
  local code
  code=$(curl -s -o /tmp/resp.$$ -w '%{http_code}' "${base_api}$path" "${header_auth[@]}" "${common_headers[@]}") || true
  if [ "$code" = "200" ]; then
    echo "[token][ok] $path (200)" >&2
  else
    echo "[token][warn] $path -> HTTP $code" >&2
  fi
  if grep -qi 'bad credentials' /tmp/resp.$$; then
    echo "[token][error] Bad credentials detected" >&2; rm -f /tmp/resp.$$; exit 2
  fi
  rm -f /tmp/resp.$$ || true
}
# Basic resource checks
hit /user                    # requires user scope (classic PAT has it implicitly)
hit /repos/$repo_slug        # repo metadata
hit /repos/$repo_slug/actions/workflows # list workflows
# POST permission dry-run: attempt workflow dispatch with dry-run (will 404 if path wrong, 204 success, 401 unauthorized)
BODY='{"ref":"main","inputs":{"reason":"token test"}}'
endpoint="/repos/$repo_slug/actions/workflows/deploy.yml/dispatches"
code=$(curl -s -o /dev/null -w '%{http_code}' -X POST "${base_api}$endpoint" "${header_auth[@]}" "${common_headers[@]}" -d "$BODY") || true
case "$code" in
  204) echo "[token][ok] workflow dispatch authorized (204)";;
  401) echo "[token][error] 401 Unauthorized (bad credentials or missing scopes)"; exit 3;;
  404) echo "[token][warn] 404 Not Found (workflow file name mismatch OR token lacks repo access)";;
  422) echo "[token][warn] 422 Unprocessable (ref missing?)";;
  *) echo "[token][info] POST dispatch returned $code";;
cesac
