<?php

namespace Jet_WC_Product_Table\Modules\Bricks_Views;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Main class for the Bricks Views module
 */
class Module {

	public function __construct() {
		add_action( 'init', [
			$this,
			'register_bricks_elements',
		], 11 );
	}

	/**
	 * Register all Bricks Builder elements
	 */
	public function register_bricks_elements() {
		if ( ! class_exists( 'Bricks\Elements' ) ) {
			return;
		}

		\Bricks\Elements::register_element( __DIR__ . '/product-table-element.php' );
	}
} // End of class
