<?php
/**
 * WordPress configuration.
 * IMPORTANT: This file contains live secrets. Do NOT commit to Git.
 *
 * Hardening notes:
 * - DISALLOW_FILE_EDIT prevents inline editing.
 * - WP_DEBUG_DISPLAY is false so visitors never see PHP errors.
 * - Logs still go to wp-content/debug.log for troubleshooting.
 */

/* =========================================
 * 1. Database Settings (fill in rotated values)
 * ========================================= */
define( 'DB_NAME',     'u753768407_GS8iP' );
define( 'DB_USER',     'u753768407_pDpFM' );
define( 'DB_PASSWORD', 'm^3Va[W|>' );
define( 'DB_HOST',     '127.0.0.1' );      
define( 'DB_CHARSET',  'utf8' );
define( 'DB_COLLATE',  '' );

/* =========================================
 * 2. Authentication Unique Keys & Salts
 *    Generate fresh ones: https://api.wordpress.org/secret-key/1.1/salt/
 *    Replace ALL eight lines below completely.
 * ========================================= */

define('AUTH_KEY',         '[Tz3Gq1-o+[YcvtY0-?2vz/$LReQF6bbCtiJ}LdFYZx4)wH^4tI1bnei0IDZ@skC');
define('SECURE_AUTH_KEY',  'X%S}I:T8Zkts+U{87P|0$H5F4cxko_:gyD 1t~-d,97cDZhjK/:z6K|g|:u|{JN8');
define('LOGGED_IN_KEY',    ']#LI+qI#-*yK;+VimU_vJ$!1%6MD~L,Ip);,BF7x/DnND<=>@2~%++PwY-K,Twk^');
define('NONCE_KEY',        'urun|TQrIoCX[?8Pr^7kln?(krC;*BM%Ii9NyS)Yi*fW6YFD]He`C}W3U0*i=9A ');
define('AUTH_SALT',        'sGH5Ko;@C.m6#Zx=kO<t);-hV}1X_YxM@U.6A/DNQ&l@+KmtIsejA((L PJiZ*M?');
define('SECURE_AUTH_SALT', 'zfhTa|_1ftn6Jz6|Yxvc,r#Hl:S@hp(-R3`2>IC}@<Em4n3hU-H7L<Y`pN!+bB5<');
define('LOGGED_IN_SALT',   '-^uP[DE-A(1X~t8E_M]T-J&N_z|1$eD?={1-2m;2``o!+%VeSVPq<pm:-hLK-jl7');
define('NONCE_SALT',       '~268SdtS+<p-|_[;Aug`lTepjw8.&o_PWBB.]@cVYl:Jw8Mdii?s-O$d65R{M1x{');

/* Unique cache key salt (any random string) */
define( 'WP_CACHE_KEY_SALT', 'UNIQUE_CACHE_KEY_SALT_CHANGE_ME' );

/* =========================================
 * 3. Table Prefix
 * ========================================= */
$table_prefix = 'wp_';   // Change if you have custom prefix (only letters, numbers, underscore)

/* =========================================
 * 4. Basic Security Hardening
 * ========================================= */
define( 'DISALLOW_FILE_EDIT', true );  // Prevent theme/plugin editor
// define( 'DISALLOW_FILE_MODS', true ); // Uncomment to block updates/installs (only if you handle deploys externally)

/* =========================================
 * 5. Environment & Debug
 * Set WP_ENVIRONMENT_TYPE to: production | staging | development
 * (Core uses this for some behavior differences)
 * ========================================= */
if ( ! defined( 'WP_ENVIRONMENT_TYPE' ) ) {
    define( 'WP_ENVIRONMENT_TYPE', 'production' );
}

/*
 * Debug policy:
 *  - While you are diagnosing the “critical error”, keep WP_DEBUG true.
 *  - After fixing, you can set WP_DEBUG to false but keep logging.
 */
define( 'WP_DEBUG', true );          // Set to false after troubleshooting if desired
define( 'WP_DEBUG_LOG', true );      // Writes to wp-content/debug.log
define( 'WP_DEBUG_DISPLAY', false ); // Never show errors to visitors
@ini_set( 'log_errors', 1 );
@ini_set( 'display_errors', 0 );

/* Optional: show script/style debug in a staging environment */
// if ( WP_ENVIRONMENT_TYPE !== 'production' ) {
//     define( 'SCRIPT_DEBUG', true );
// }

/* =========================================
 * 6. Performance / Memory
 * ========================================= */
define( 'WP_MEMORY_LIMIT',     '512M' );  // Increase if truly needed and host allows
define( 'WP_MAX_MEMORY_LIMIT', '512M' );

/* =========================================
 * 7. Updates
 * 'minor' gives you security & maintenance releases automatically.
 * ========================================= */
define( 'WP_AUTO_UPDATE_CORE', 'minor' );

/* =========================================
 * 8. Filesystem Method
 * Leave 'direct' only if you understand the risk & perms are tight.
 * If you later adopt deployment automation, remove this.
 * ========================================= */
define( 'FS_METHOD', 'direct' );  // Remove or change when using controlled deploys

/* =========================================
 * 9. Caching Flag
 * Set by cache plugins; keep true if using one.
 * ========================================= */
define( 'WP_CACHE', true );

/* =========================================
 * 10. Absolute Path & Bootstrap
 * ========================================= */
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/' );
}
require_once ABSPATH . 'wp-settings.php';