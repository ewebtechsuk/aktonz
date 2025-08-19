<?php
/**
 * wp-config.example.php
 * Template ONLY – do NOT put real credentials or salts here.
 * Keep this under version control; the real wp-config.php stays ignored.
 */

/* 1. Database (placeholders) */
define( 'DB_NAME',     'PLACEHOLDER_DB_NAME' );
define( 'DB_USER',     'PLACEHOLDER_DB_USER' );
define( 'DB_PASSWORD', 'PLACEHOLDER_DB_PASSWORD' );
define( 'DB_HOST',     '127.0.0.1' );
define( 'DB_CHARSET',  'utf8' );
define( 'DB_COLLATE',  '' );

/* Optional ENV injection (uncomment if you adopt .env or panel vars)
 * if ( getenv('WP_DB_NAME') )     define( 'DB_NAME', getenv('WP_DB_NAME') );
 * if ( getenv('WP_DB_USER') )     define( 'DB_USER', getenv('WP_DB_USER') );
 * if ( getenv('WP_DB_PASSWORD') ) define( 'DB_PASSWORD', getenv('WP_DB_PASSWORD') );
 * if ( getenv('WP_DB_HOST') )     define( 'DB_HOST', getenv('WP_DB_HOST') );
 */

/* 2. Keys & Salts – placeholders ONLY (generate real ones for live) */
define( 'AUTH_KEY',         'PLACEHOLDER_AUTH_KEY' );
define( 'SECURE_AUTH_KEY',  'PLACEHOLDER_SECURE_AUTH_KEY' );
define( 'LOGGED_IN_KEY',    'PLACEHOLDER_LOGGED_IN_KEY' );
define( 'NONCE_KEY',        'PLACEHOLDER_NONCE_KEY' );
define( 'AUTH_SALT',        'PLACEHOLDER_AUTH_SALT' );
define( 'SECURE_AUTH_SALT', 'PLACEHOLDER_SECURE_AUTH_SALT' );
define( 'LOGGED_IN_SALT',   'PLACEHOLDER_LOGGED_IN_SALT' );
define( 'NONCE_SALT',       'PLACEHOLDER_NONCE_SALT' );

define( 'WP_CACHE_KEY_SALT', 'PLACEHOLDER_UNIQUE_CACHE_SALT' );

/* 3. Table Prefix */
$table_prefix = 'wp_';

/* 4. Environment & Debug */
define( 'WP_ENVIRONMENT_TYPE', 'development' );
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', true );
@ini_set( 'log_errors', 1 );
@ini_set( 'display_errors', 1 );

/* 5. Security Hardening (example baseline) */
define( 'DISALLOW_FILE_EDIT', true );
// define( 'DISALLOW_FILE_MODS', true ); // Only if external deployment pipeline

/* 6. Memory (adjust for dev) */
define( 'WP_MEMORY_LIMIT',     '256M' );
define( 'WP_MAX_MEMORY_LIMIT', '512M' );

/* 7. Core Updates */
define( 'WP_AUTO_UPDATE_CORE', 'minor' );

/* 8. Filesystem method (leave commented by default) */
// define( 'FS_METHOD', 'direct' );

/* 9. Optional Site URLs (commented) */
// define( 'WP_HOME',    'http://project.local' );
// define( 'WP_SITEURL', 'http://project.local' );

/* 10. Local override include (ignored file) */
// if ( file_exists( __DIR__ . '/wp-config.local.php' ) ) {
//     include __DIR__ . '/wp-config.local.php';
// }

/* 11. Bootstrap */
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/' );
}
require_once ABSPATH . 'wp-settings.php';