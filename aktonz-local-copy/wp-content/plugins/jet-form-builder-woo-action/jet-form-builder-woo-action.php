<?php
/**
 * Plugin Name:         JetFormBuilder WooCommerce Cart & Checkout Action
 * Plugin URI:          https://jetformbuilder.com/addons/woocommerce-cart-checkout-action/
 * Description:         An addon that integrates forms and WooCommerce checkout.
 * Version:             1.0.8
 * Author:              Crocoblock
 * Author URI:          https://crocoblock.com/
 * Text Domain:         jet-form-builder-woo-action
 * License:             GPL-3.0+
 * License URI:         http://www.gnu.org/licenses/gpl-3.0.txt
 * Requires PHP:        7.0
 * Requires at least:   6.0
 * Domain Path:         /languages
 * Requires Plugins:    woocommerce
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die();
}

define( 'JET_FB_WOO_ACTION_VERSION', '1.0.8' );

define( 'JET_FB_WOO_ACTION__FILE__', __FILE__ );
define( 'JET_FB_WOO_ACTION_PLUGIN_BASE', plugin_basename( __FILE__ ) );
define( 'JET_FB_WOO_ACTION_PATH', plugin_dir_path( __FILE__ ) );
define( 'JET_FB_WOO_ACTION_URL', plugins_url( '/', __FILE__ ) );

require __DIR__ . '/includes/load.php';
