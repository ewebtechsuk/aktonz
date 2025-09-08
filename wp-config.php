<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', getenv( 'DB_NAME' ) ?: 'wpdb' );

/** Database username */
define( 'DB_USER', getenv( 'DB_USER' ) ?: 'wpuser' );

/** Database password */
define( 'DB_PASSWORD', getenv( 'DB_PASSWORD' ) ?: 'wpsecret' );

/** Database hostname */
define( 'DB_HOST', getenv( 'DB_HOST' ) ?: 'localhost' );


/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',          'K8wB0BneWz?id_TFgF<!)tS(zT_yc0skML*NjQ4;JfzwX1]z{&%V;LcEEgTK  <U' );
define( 'SECURE_AUTH_KEY',   'xppdsynA:F)N8/3,W:ip|}n]Q6HyEv-w:&%yq#Pc4o?7RN7Z06x+BK:s4_HoafMs' );
define( 'LOGGED_IN_KEY',     '[,?xbLeUT:|rx#(=!M-15r#M:DgzU)%u@hkwt]Ed2L4oY!|M$/eU@F,.ivF;1{G=' );
define( 'NONCE_KEY',         '$d)@J-@(w_:tZ8O22N8+|?*!Pcx!2J#$?7IH((xRTG/[FmORg@,&5}X0Kv`PGP85' );
define( 'AUTH_SALT',         'a4SR/mB[L$0LLX4Wsk4>cFbZ0XzS<`t+[2v7.Z@O5gl`j] 5c6T,.mNF=c63;0Bx' );
define( 'SECURE_AUTH_SALT',  'uX<lj -M1kV= ITY34zyNOn]i_HC=:/1UmazU)ti8aH<Nh^+-4G^X7!f-`E5hSkZ' );
define( 'LOGGED_IN_SALT',    'EUJS[oiGC,G;<<26&y$|O:Vb.ujD5e?<Ap%s3h9lueU2E5=P$=:YrR<x`Ngj/Tk3' );
define( 'NONCE_SALT',        ',pfUBdv%)v-Jja+Jt_bZ*u52&4zp!:%bb43Ii::5GLu^;,2SgIR:xgDcE%_8fzM[' );
define( 'WP_CACHE_KEY_SALT', 'ZEXj;0C,rd)r(asG122&0sDGS/`r`+;b3eg$R`oCH)Z=!p+L2Pi2~,Q^l{})_S0I' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';


/* Add any custom values between this line and the "stop editing" line. */
if ( getenv( 'WP_HOME' ) ) {
    define( 'WP_HOME', getenv( 'WP_HOME' ) );
}
if ( getenv( 'WP_SITEURL' ) ) {
    define( 'WP_SITEURL', getenv( 'WP_SITEURL' ) );
}



/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
if ( ! defined( 'WP_DEBUG' ) ) {
    define( 'WP_DEBUG', true );
}
// Log debug messages to the default location in wp-content for easier troubleshooting.
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';

// Load .env if present
if (file_exists(__DIR__.'/.env')) {
    foreach (parse_ini_file(__DIR__.'/.env') as $k => $v) {
        if (!getenv($k)) {
            putenv("$k=$v");
        }
    }
}
// Managed by CI: force disable page/object cache while troubleshooting
// Managed by CI: enforce single WP_CACHE false (validation stage)
if ( ! defined( 'WP_CACHE' ) ) { define( 'WP_CACHE', false ); }
