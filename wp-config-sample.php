<?php
/**
 * Sample WordPress configuration file
 *
 * Provide placeholders for database credentials and salts.
 */

// ** Database settings ** //
define( 'DB_NAME', 'DB_NAME_PLACEHOLDER' );
define( 'DB_USER', 'DB_USER_PLACEHOLDER' );
define( 'DB_PASSWORD', 'DB_PASSWORD_PLACEHOLDER' );
define( 'DB_HOST', 'DB_HOST_PLACEHOLDER' );
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );

// ** Authentication unique keys and salts. ** //
define( 'AUTH_KEY', 'AUTH_KEY_PLACEHOLDER' );
define( 'SECURE_AUTH_KEY', 'SECURE_AUTH_KEY_PLACEHOLDER' );
define( 'LOGGED_IN_KEY', 'LOGGED_IN_KEY_PLACEHOLDER' );
define( 'NONCE_KEY', 'NONCE_KEY_PLACEHOLDER' );
define( 'AUTH_SALT', 'AUTH_SALT_PLACEHOLDER' );
define( 'SECURE_AUTH_SALT', 'SECURE_AUTH_SALT_PLACEHOLDER' );
define( 'LOGGED_IN_SALT', 'LOGGED_IN_SALT_PLACEHOLDER' );
define( 'NONCE_SALT', 'NONCE_SALT_PLACEHOLDER' );

/**
 * WordPress database table prefix.
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 */
define( 'WP_DEBUG', false );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
        define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
