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
