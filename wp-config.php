<?php
define( 'WP_CACHE', true );
 
 
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
define( 'DB_NAME', 'u753768407_GS8iP' );
/** Database username */
define( 'DB_USER', 'u753768407_pDpFM' );
/** Database password */
define( 'DB_PASSWORD', 'OcOKB34cTe' );
/** Database hostname */
define( 'DB_HOST', '127.0.0.1' );
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
define( 'AUTH_KEY',          '4sQRaaYz%x`8@!V{igdJTQn7%)uNHxs1O^vW<~_P6p@EfS3)oU@s(e@n<{&Bo7@P' );
define( 'SECURE_AUTH_KEY',   'RuYwJiX#]r@s!Hw<&S3BDJ=]Q1tbf<6#yX{$!x/HdR+t@,OVXX[r><_kIiI2#&0t' );
define( 'LOGGED_IN_KEY',     '#+_Xz v:9$Qq}mMy2BiF(EIe:ftm`ND8q]Sb;%*vioP@7{M5k5;7s[#1~HZ*WT`{' );
define( 'NONCE_KEY',         'J8sg:ih6t.|2O|@`IgJ7]WyvG:*oD8/<H:&pXBL+U^P1r]p;s?Zt%~!V_sK{17]>' );
define( 'AUTH_SALT',         'd$U2&P;rOVFY%L+/<O&a]a5xG^gQoPAx}o}Y+b,]xv9Yd3_P1q4N&Xm+oPz]tJ *' );
define( 'SECURE_AUTH_SALT',  'F-x@($Q&*%VQPD9^axLQB1p}XW4sAl%_&!pdeIkv*kR$tO/W7SJ.cQb|Z)b~@{Rm' );
define( 'LOGGED_IN_SALT',    'd=: os;(_zxa;<g~uFr6_@2WJf<Cg}UNC2`4<,|u}mH}_s,i[B~9,Ln)`9;bC;jM' );
define( 'NONCE_SALT',        'fO Ub~MP!.H[G<iy#jloUnf.jF0(PSG:+U%v%7v+F7nJYbb~0:e7;7E3Mll^whYR' );
define( 'WP_CACHE_KEY_SALT', 'E:BW=Su:I5#shw`x&[T#3;wprra1=z|]lG;3A,^8wyh|?F_[W{|A^:cDBXE!>Fi+' );
/**#@-*/
/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';
define('DISALLOW_FILE_EDIT',true);
/* Add any custom values between this line and the "stop editing" line. */
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
	define( 'WP_DEBUG', false );
}
define( 'FS_METHOD', 'direct' );
define( 'COOKIEHASH', '2a1c55a8328ded24d2b2819c5cac3f5a' );
define( 'WP_AUTO_UPDATE_CORE', false );
define( 'WP_DEBUG_LOG', false );
define( 'WP_DEBUG_DISPLAY', false );
/* That's all, stop editing! Happy publishing. */
/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}
/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';