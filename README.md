# Aktonz WordPress Project

Repository containing the WordPress application code for the Aktonz site, along with deployment automation and local development helpers.

## Deployment (GitHub Actions)

A workflow in `.github/workflows/deploy.yml` handles syncing this repo to the Hostinger server via SSH + rsync, performing:
- Pre-audit (structure + logs)
- Optional plugin disabling (rename to `.disabled`)
- LiteSpeed cache neutralization (forces `WP_CACHE=false`, disables related drop-ins)
- Safe rsync with curated excludes (uploads, caches, remote-only dirs)
- Post-deploy health/log probe (optional)

### Deployment Phases (High-Level)
1. Guard & Audit: Validates required directories, inspects recent logs and captures `wp-config.php` metrics (line count, md5, required tokens).
2. Plugin Safety Net: Optionally disables user-specified plugins; force-disables `litespeed-cache` with timestamped rename; prunes stale disabled variants.
3. LiteSpeed Neutralization: Sanitizes drop-ins, enforces a single `WP_CACHE=false`, removes residual optimizer code, creates stub assets & (if needed) a benign stub plugin.
4. Aggressive Neutralization (post-fix): Ensures no real LiteSpeed optimizer remnants, replaces object-cache if necessary, purges disabled variants, resets OPcache.
5. Rsync Sync: Pushes code while excluding volatile/uploads & remote-only directories; optional `--delete` gated by `DELETE_REMOTE` secret.
6. Smoke Tests: Curls homepage & admin, measuring status, size, timing, redirects, content hash delta vs previous marker.
7. Marker & Validation: Writes enriched `.deploy-info.json` (commit, cfg metrics, hash & size telemetry, disabled plugin variants) then validates structure & hash.
8. Final Summary: Emits condensed line/hashes/perf summary for quick run scanning.

### Required Secrets
| Secret | Purpose |
|--------|---------|
| `HOSTINGER_SSH_KEY` | Private key for SSH auth |
| `HOSTINGER_SSH_HOST` | Remote host (FQDN/IP) |
| `HOSTINGER_SSH_USER` | SSH user |
| `HOSTINGER_SSH_PORT` | SSH port |
| `HOSTINGER_PATH` | Absolute path to WP root (where `wp-admin` lives) |
| `PRODUCTION_URL` | Base URL for smoke & health checks |

### Optional Secrets / Inputs
| Name | Kind | Purpose |
|------|------|---------|
| `DISABLE_PLUGINS` | Secret | CSV plugin slugs auto-disabled (renamed) |
| `DELETE_REMOTE` | Secret (`true`/`false`) | Enable rsync `--delete` (default off for safety) |
| `SMOKE_MAX_HOME_MS`, `SMOKE_MAX_ADMIN_MS` | Secret | Performance thresholds (ms) |
| `SMOKE_MAX_SIZE_DELTA_PCT` | Secret | Max allowed size growth percentage |
| `MIN_CONFIG_LINES` | Secret | Override minimum `wp-config.php` line threshold |
| `RETAIN_DISABLED_LITESPEED` | Secret | How many disabled litespeed variants to keep |
| `RUN_WP_CLI_CACHE_FLUSH` | Secret | If `true`, runs `wp cache flush` post-rsync |
| `BASELINE_WP_CONFIG_LINES`, `BASELINE_WP_CONFIG_MD5` | (Planned) Drift guard baselines |

### Key Manual Inputs (workflow_dispatch)
| Input | Usage |
|-------|-------|
| `audit` | `true` = run guard/audit steps only (no rsync) |
| `dry_run` | `true` = show rsync diff without modifying remote |
| `disable_plugins` | Comma list of plugin slugs to disable this run |
| `toggle_debug` | `enable`/`disable` inserts temporary WP_DEBUG window |
| `debug_minutes` | Lifetime of temporary debug window |
| `debug_logs` | `true` tails logs + fetches homepage body/head |
| `fail_on_fatal` | `true` fails run on critical error signatures |

### Operational Playbooks
| Scenario | Action |
|----------|--------|
| Quick structural check (no file changes) | Dispatch deploy with `audit=true` |
| Test deploy impact safely | Dispatch with `dry_run=true` first, review rsync plan |
| Temporarily enable WP_DEBUG logging | Dispatch with `toggle_debug=enable` & desired `debug_minutes` |
| Re-disable the temp debug snippet early | Dispatch with `toggle_debug=disable` |
| Investigate sudden homepage size/hash increase | Compare `.deploy-info.json` marker differences & `smoke_artifacts/` payload sizes |
| Force purge lingering LiteSpeed remnants | Rerun a normal deploy (aggressive neutralization step handles purge) |

### Monitoring & Drift Detection (In Progress)
`remote-snapshot` workflow (added) will capture periodic hashes of plugins/themes + config metrics. Planned enhancements:
1. Store stable baseline config metrics in secrets (`BASELINE_WP_CONFIG_LINES`, `BASELINE_WP_CONFIG_MD5`).
2. (Implemented) Marker now includes `code_manifest_hash`, `plugin_hashes`, `theme_hashes` derived from `scripts/build_code_manifest.sh`.
3. Lightweight diff job comparing current vs previous snapshot; auto-create issue on unexpected drift (hash changes outside planned deploy window).

### Next Improvements (Roadmap)
1. Automate drift issue creation (GitHub Issue or webhook) using new manifest deltas.
2. Expose size/hash deltas in final summary line (env values already present) including manifest overall hash.
3. Add retention policy pruning for old `wp-config.php.ci.bak.*` beyond N (currently only partial pruning) and disabled plugin variants.
4. Optional flag to re-enable `WP_CACHE` once root cause resolved.
5. Nightly audit-only run to surface drift early without rsync mutation.

Trigger: push to `main` or manual dispatch. Secrets required:
- `HOSTINGER_SSH_KEY`, `HOSTINGER_SSH_HOST`, `HOSTINGER_SSH_USER`, `HOSTINGER_SSH_PORT`, `HOSTINGER_PATH`
- Optional: `DISABLE_PLUGINS`, `DELETE_REMOTE`, `PRODUCTION_URL`

## Local "Codex" Development Environment

Unified helper script: `scripts/codex.sh`

### Quick Start
```bash
# provision / update stack
scripts/codex.sh setup
```
Visit: http://localhost:8080 (default)
phpMyAdmin: http://localhost:8081

### Lite (No Docker) Fast Path (SQLite)
For constrained or Codespaces style environments without reliable Docker:
```bash
sh codex_lite_sqlite_setup.sh   # fast path; exits quickly if already installed
```
Outputs a JSON status line and ensures a PHP built-in server on port 8080. The script is idempotent (subsequent runs skip downloads).

To run lightweight health + optional updates afterwards:
```bash
sh scripts/maintenance_lite.sh --json
```

Important: Use only ONE setup command in automated platform config (either the Docker `scripts/codex.sh setup` or the lite `sh codex_lite_sqlite_setup.sh`) to avoid duplicate installs.

### Features (setup phase)
1. Generates `.env.example` / `.env` if absent.
2. Builds `docker-compose.yml` (WordPress + MariaDB + phpMyAdmin).
3. Adds PHP overrides (`docker/php/uploads.ini`, optional `xdebug.ini`).
4. (Optional) Generates salts into `wp-config-local.php`.
5. Boots containers & performs initial `wp core install` if needed.
6. Renames listed plugins in `AUTO_DISABLE_PLUGINS` to `*.disabled` (e.g. `litespeed-cache`).

### Key .env Variables
| Variable | Purpose | Default |
|----------|---------|---------|
| `PROJECT_NAME` | Compose project prefix | aktonz |
| `WP_VERSION` | WordPress image tag | latest |
| `DB_IMAGE` | DB image | mariadb:10.6 |
| `DB_NAME` | Database name | wpdb |
| `DB_USER` | DB user | wpuser |
| `DB_PASSWORD` | DB password | wpsecret |
| `DB_ROOT_PASSWORD` | DB root password | rootpw |
| `DB_PORT` | Host DB port | 3307 |
| `SITE_HTTP_PORT` | Host HTTP port | 8080 |
| `SITE_URL` | Site URL used by installer | http://localhost:8080 |
| `ENABLE_XDEBUG` | 1 to enable Xdebug ini | 0 |
| `GENERATE_SALTS` | 1 to generate salts | 1 |
| `AUTO_DISABLE_PLUGINS` | CSV plugin slugs to disable | litespeed-cache |
| `PHPMYADMIN_PORT` | phpMyAdmin port | 8081 |

### Common Commands
```bash
scripts/codex.sh help                 # list all commands
scripts/codex.sh status               # container + WP status
scripts/codex.sh logs                 # tail WP logs
scripts/codex.sh doctor               # diagnose docker/ports/perms
scripts/codex.sh wp plugin list       # arbitrary wp-cli
scripts/codex.sh backup-db            # gzip DB dump
scripts/codex.sh restore-db dump.sql.gz
scripts/codex.sh search-replace http://localhost:8080 https://alt.local
scripts/codex.sh update               # core + plugins + themes
scripts/codex.sh salts-regenerate
scripts/codex.sh recreate             # destructive: rebuild DB
```

### Hard Reset (Destructive)
```bash
scripts/codex.sh hard-reset
```

## Notes
- The production deploy workflow enforces a single `WP_CACHE` define to avoid cache plugin conflicts during troubleshooting.
- Local salts & overrides live in `wp-config-local.php` (excluded from deployment if configured in excludes).

## License
See `license.txt` (WordPress) plus any additional project specific licensing notices.


