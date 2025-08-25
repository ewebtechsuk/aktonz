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

Spin up a reproducible local WP stack (MariaDB + WordPress + phpMyAdmin) with your working tree mounted.

### Quick Start

```bash
scripts/setup_codex_env.sh
```

Then visit: `http://localhost:8080` (default) and phpMyAdmin at `http://localhost:8081`.

### What the Script Does
1. Creates `.env.example` / `.env` if missing (tunable ports, DB creds, salts, plugin auto-disable list).
2. Generates `docker-compose.yml` and PHP config (`docker/php/uploads.ini`, optional `xdebug.ini`).
3. Optionally injects salts into `wp-config-local.php` when `GENERATE_SALTS=1`.
4. Launches containers (`docker compose up -d`).
5. Waits briefly for HTTP and runs `wp core install` if not yet installed.
6. Renames any plugins in `AUTO_DISABLE_PLUGINS` to `*.disabled` for safe local troubleshooting.

### Key .env Variables
| Variable | Purpose | Default |
|----------|---------|---------|
| `PROJECT_NAME` | Compose project prefix | aktonz |
| `WP_VERSION` | WordPress image tag | latest |
| `DB_IMAGE` | DB image | mariadb:10.6 |
| `DB_PORT` | Host DB port | 3307 |
| `SITE_HTTP_PORT` | Host HTTP port | 8080 |
| `SITE_URL` | Site URL used by installer | http://localhost:8080 |
| `ENABLE_XDEBUG` | 1 to enable Xdebug ini | 0 |
| `GENERATE_SALTS` | 1 to generate salts | 1 |
| `AUTO_DISABLE_PLUGINS` | CSV plugin slugs to disable | litespeed-cache |

### Common Commands
```bash
docker compose -p aktonz logs -f wordpress
docker compose -p aktonz exec wordpress wp plugin list
docker compose -p aktonz exec wordpress wp theme list
docker compose -p aktonz down           # stop
docker compose -p aktonz down -v        # destroy volumes (DB reset)
```

### Cleanup / Reset
To reset the database and reinstall:
```bash
docker compose -p aktonz down -v
scripts/setup_codex_env.sh
```

## Notes
- The production deploy workflow enforces a single `WP_CACHE` define to avoid cache plugin conflicts during troubleshooting.
- Local salts & overrides live in `wp-config-local.php` (excluded from deployment if configured in excludes).

## License
See `license.txt` (WordPress) plus any additional project specific licensing notices.

