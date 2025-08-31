<?php
// Load environment variables from .env then .env.deploy (production overrides) if present.
// Hardened to avoid warnings when file exists but is unreadable (e.g., mode 600, different owner).
$env_primary = __DIR__.'/.env';
if (file_exists($env_primary)) {
    if (is_readable($env_primary)) {
        $parsed = @parse_ini_file($env_primary);
        if ($parsed !== false) {
            foreach ($parsed as $key => $value) {
                if (getenv($key) === false) {
                    putenv("$key=$value");
                    $_ENV[$key] = $value;
                }
            }
        } else {
            error_log('wp-config: failed to parse .env (parse_ini_file returned false)');
        }
    } else {
        error_log('wp-config: .env exists but is not readable by the web server user');
    }
}
$env_deploy = __DIR__.'/.env.deploy';
if (file_exists($env_deploy)) {
    if (is_readable($env_deploy)) {
        $parsedDeploy = @parse_ini_file($env_deploy);
        if ($parsedDeploy !== false) {
            foreach ($parsedDeploy as $key => $value) {
                // Always override with deploy values
                putenv("$key=$value");
                $_ENV[$key] = $value;
            }
        } else {
            error_log('wp-config: failed to parse .env.deploy (parse_ini_file returned false)');
        }
    } else {
        // Avoid noisy PHP warnings that were generating HTTP 500 responses
        error_log('wp-config: .env.deploy exists but is not readable by the web server user');
    }
}

// ** Database settings ** //
define('DB_NAME', getenv('DB_NAME') ?: 'wpdb');
define('DB_USER', getenv('DB_USER') ?: 'wpuser');
define('DB_PASSWORD', getenv('DB_PASSWORD') ?: 'wpsecret');
$host = getenv('DB_HOST');
if (!$host) {
    // Fallback to standard docker env var name used by official WP image
    $host = getenv('WORDPRESS_DB_HOST');
}
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

// For developers: WordPress debugging mode (gated by WP_ENABLE_DEBUG env or .env entry).
$__debugFlag = getenv('WP_ENABLE_DEBUG');
$__debugOn = $__debugFlag && in_array(strtolower($__debugFlag), ['1','true','yes','on'], true);
define('WP_DEBUG', $__debugOn);
define('WP_DEBUG_LOG', $__debugOn); // Log to wp-content/debug.log when enabled
define('WP_DEBUG_DISPLAY', false); // Keep responses clean; inspect debug.log instead
if ($__debugOn) { error_log('wp-config: WP_DEBUG enabled via WP_ENABLE_DEBUG'); }
// Deployment workflow expects exactly one WP_CACHE define forcing false (for stability during audits)
if (!defined('WP_CACHE')) {
    define('WP_CACHE', false);
}

/* That's all, stop editing! Happy publishing. */

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

require_once ABSPATH . 'wp-settings.php';
