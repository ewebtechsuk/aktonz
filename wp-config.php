<?php
// Load environment variables from .env then .env.deploy (production overrides) if present
$env_primary = __DIR__.'/.env';
if (file_exists($env_primary)) {
    foreach (parse_ini_file($env_primary) as $key => $value) {
        if (getenv($key) === false) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}
$env_deploy = __DIR__.'/.env.deploy';
if (file_exists($env_deploy)) {
    foreach (parse_ini_file($env_deploy) as $key => $value) {
        // Always override with deploy values
        putenv("$key=$value");
        $_ENV[$key] = $value;
    }
}

// ** Database settings ** //
define('DB_NAME', getenv('DB_NAME') ?: 'wpdb');
define('DB_USER', getenv('DB_USER') ?: 'wpuser');
define('DB_PASSWORD', getenv('DB_PASSWORD') ?: 'wpsecret');
$host = getenv('DB_HOST');
if (!$host) {
    $port = getenv('DB_PORT');
    $host = $port ? 'localhost:'.$port : 'localhost';
}
define('DB_HOST', $host);
define('DB_CHARSET', 'utf8');
define('DB_COLLATE', '');

// ** Authentication unique keys and salts. ** //
// These should be set to unique phrases in production.
define('AUTH_KEY', getenv('AUTH_KEY') ?: 'put-your-unique-phrase-here');
define('SECURE_AUTH_KEY', getenv('SECURE_AUTH_KEY') ?: 'put-your-unique-phrase-here');
define('LOGGED_IN_KEY', getenv('LOGGED_IN_KEY') ?: 'put-your-unique-phrase-here');
define('NONCE_KEY', getenv('NONCE_KEY') ?: 'put-your-unique-phrase-here');
define('AUTH_SALT', getenv('AUTH_SALT') ?: 'put-your-unique-phrase-here');
define('SECURE_AUTH_SALT', getenv('SECURE_AUTH_SALT') ?: 'put-your-unique-phrase-here');
define('LOGGED_IN_SALT', getenv('LOGGED_IN_SALT') ?: 'put-your-unique-phrase-here');
define('NONCE_SALT', getenv('NONCE_SALT') ?: 'put-your-unique-phrase-here');

// WordPress database table prefix.
$table_prefix = getenv('TABLE_PREFIX') ?: 'wp_';

// For developers: WordPress debugging mode.
define('WP_DEBUG', false);
// Deployment workflow expects exactly one WP_CACHE define forcing false (for stability during audits)
if (!defined('WP_CACHE')) {
    define('WP_CACHE', false);
}

/* That's all, stop editing! Happy publishing. */

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

require_once ABSPATH . 'wp-settings.php';
