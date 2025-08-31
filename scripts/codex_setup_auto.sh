#!/usr/bin/env bash
# codex_setup_auto.sh - Fixed admin credential WordPress bootstrap (Option A)
# Feel free to override any of these by exporting the env var before running.
set -euo pipefail
export CODEX_SITE_URL=${CODEX_SITE_URL:-http://localhost:8080}
export CODEX_SITE_TITLE=${CODEX_SITE_TITLE:-"Aktonz Dev"}
export CODEX_ADMIN_USER=${CODEX_ADMIN_USER:-admin}
# WARNING: Change this password for any non-ephemeral environment.
export CODEX_ADMIN_PASS=${CODEX_ADMIN_PASS:-Utembeeds875@!}
export CODEX_ADMIN_EMAIL=${CODEX_ADMIN_EMAIL:-info@aktonz.com}

# Delegate to the main manual setup script (idempotent)
bash scripts/codex_manual_setup.sh "$@"
