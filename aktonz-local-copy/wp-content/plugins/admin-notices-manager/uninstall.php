<?php
/**
 * Uninstall script.
 *
 * @package admin-notices-manager
 */

// If uninstall.php is not called by WordPress, die.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

global $wpdb;
if ( function_exists( 'is_multisite' ) && is_multisite() ) {
	$wpdb->query( // @codingStandardsIgnoreLine
		$wpdb->prepare(
			"
            DELETE FROM $wpdb->sitemeta
            WHERE meta_key LIKE %s
            ",
			array(
				'anm%',
			)
		)
	);
} else {
	$wpdb->query( // @codingStandardsIgnoreLine
		$wpdb->prepare(
			"
            DELETE FROM $wpdb->options
            WHERE option_name LIKE %s
            ",
			array(
				'anm%',
			)
		)
	);
}
