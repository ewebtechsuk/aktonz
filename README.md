# Aktonz WordPress Project

Repository containing the WordPress application code for the Aktonz site, along with deployment automation and local development helpers.

## Deployment (GitHub Actions)

A workflow in `.github/workflows/deploy.yml` handles syncing this repo to the Hostinger server via SSH + rsync, performing:
- Pre-audit (structure + logs)
- Optional plugin disabling (rename to `.disabled`)
- LiteSpeed cache neutralization (forces `WP_CACHE=false`, disables related drop-ins)
- Safe rsync with curated excludes (uploads, caches, remote-only dirs)
- Post-deploy health/log probe (optional)

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

