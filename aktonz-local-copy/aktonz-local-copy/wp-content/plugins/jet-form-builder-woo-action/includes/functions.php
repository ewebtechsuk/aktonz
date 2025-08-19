<?php

use Jet_FB_Woo\Plugin;
use JFB\WooComm\Vendor\Auryn\Injector;
use JFB\WooComm\Vendor\Auryn\ConfigException;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die();
}

/**
 * @throws ConfigException
 */
function jet_fb_woo_comm_setup() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	/** @var Plugin $plugin */

	$injector = new Injector();
	$plugin   = new Plugin( $injector );
	$injector->share( $plugin );

	$plugin->setup();

	add_filter(
		'jet-fb/woo-comm/injector',
		function () use ( $injector ) {
			return $injector;
		}
	);

	do_action( 'jet-fb/woo-comm/setup', $injector );
}

function jet_fb_woo_comm_injector(): Injector {
	return apply_filters( 'jet-fb/woo-comm/injector', false );
}
