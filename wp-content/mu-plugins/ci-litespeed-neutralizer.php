<?php
/**
 * Plugin Name: CI LiteSpeed Neutralizer
 * Description: Disables LiteSpeed Cache/Optimizer code paths during CI troubleshooting to prevent md5_file() fatals.
 * Author: CI Automation
 * Version: 1.0.0
 * MU Plugin: Yes
 */

// If a real troubleshooting disable flag exists, set it very early.
if ( ! defined( 'LITESPEED_DISABLE_ALL' ) ) {
	define( 'LITESPEED_DISABLE_ALL', true );
}
// Common constant LiteSpeed checks to decide whether to bootstrap advanced cache.
if ( ! defined( 'LSCACHE_ADV_CACHE' ) ) {
	define( 'LSCACHE_ADV_CACHE', false );
}
// Extra defensive flags (harmless if unused by target plugin versions).
if ( ! defined( 'LITESPEED_DISABLE_CRAWLER' ) ) {
	define( 'LITESPEED_DISABLE_CRAWLER', true );
}
if ( ! defined( 'LITESPEED_DISABLE_OBJECT' ) ) {
	define( 'LITESPEED_DISABLE_OBJECT', true );
}

// Remove optimizer shutdown callbacks if they get registered before we return (LiteSpeed normally attaches late).
add_action( 'plugins_loaded', function () {
	global $wp_filter;
	if ( empty( $wp_filter['shutdown'] ) ) {
		return;
	}
	foreach ( $wp_filter['shutdown'] as $priority => $callbacks ) {
		if ( ! is_array( $callbacks ) ) {
			continue;
		}
		foreach ( $callbacks as $id => $data ) {
			$fn = $data['function'] ?? null;
			if ( is_array( $fn ) && is_object( $fn[0] ) ) {
				$class = get_class( $fn[0] );
				if ( stripos( $class, 'LiteSpeed' ) !== false ) {
					remove_action( 'shutdown', $fn, $priority );
				}
			}
		}
	}
}, 1 ); // Run very early so later additions (if any) might still be blocked next load.

// Provide stubs expected by other themes/plugins if they naively call LiteSpeed helpers.
if ( ! function_exists( 'litespeed_optm' ) ) {
	function litespeed_optm() {
		return false; // Indicate optimization subsystem inactive.
	}
}

// Marker so diagnostics can confirm neutralizer loaded.
if ( ! defined( 'CI_LITESPEED_NEUTRALIZER_ACTIVE' ) ) {
	define( 'CI_LITESPEED_NEUTRALIZER_ACTIVE', true );
}

// 1. Remove LiteSpeed from active plugin option values before plugins are loaded.
function ci_ls_strip_active_plugins( $value ) {
	if ( is_array( $value ) ) {
		$value = array_values( array_filter( $value, function ( $slug ) {
			return ( stripos( $slug, 'litespeed-cache' ) === false );
		} ) );
	}
	return $value;
}
add_filter( 'option_active_plugins', 'ci_ls_strip_active_plugins', 1 );
add_filter( 'site_option_active_sitewide_plugins', function ( $value ) {
	if ( is_array( $value ) ) {
		foreach ( array_keys( $value ) as $plugin_file ) {
			if ( stripos( $plugin_file, 'litespeed-cache' ) !== false ) {
				unset( $value[ $plugin_file ] );
			}
		}
	}
	return $value;
}, 1 );

// 2. Provide minimal class/interface stubs (global namespace) if LiteSpeed classes are referenced later.
if ( ! class_exists( 'LiteSpeed\\Base', false ) ) {
	class LiteSpeed_Base {}
}
if ( ! class_exists( 'LiteSpeed\\Optimizer', false ) ) {
	class LiteSpeed_Optimizer {
		public function __call( $name, $args ) { return false; }
		public static function __callStatic( $name, $args ) { return false; }
	}
}
// 3. Aggressively remove any LiteSpeed actions/filters that slipped through (runs on init priority 0)
add_action( 'init', function () {
	global $wp_filter;
	$targets = array( 'init', 'template_redirect', 'shutdown', 'plugins_loaded' );
	foreach ( $targets as $hook ) {
		if ( empty( $wp_filter[ $hook ] ) ) { continue; }
		foreach ( $wp_filter[ $hook ] as $priority => $callbacks ) {
			if ( ! is_array( $callbacks ) ) { continue; }
			foreach ( $callbacks as $id => $data ) {
				$fn = $data['function'] ?? null;
				if ( is_array( $fn ) && is_object( $fn[0] ) ) {
					$cls = get_class( $fn[0] );
					if ( stripos( $cls, 'LiteSpeed' ) !== false ) {
						remove_action( $hook, $fn, $priority );
					}
				} elseif ( is_string( $fn ) && stripos( $fn, 'litespeed' ) !== false ) {
					remove_action( $hook, $fn, $priority );
				}
			}
		}
	}
}, 0 );

// 4. Ensure asset directories exist to satisfy md5_file probes (defensive; cheap to create).
add_action( 'muplugins_loaded', function () {
	$content_dir = defined( 'WP_CONTENT_DIR' ) ? WP_CONTENT_DIR : dirname( __FILE__, 2 );
	$ls_dir      = $content_dir . '/litespeed';
	@wp_mkdir_p( $ls_dir . '/css' );
	@wp_mkdir_p( $ls_dir . '/js' );
}, 1 );
