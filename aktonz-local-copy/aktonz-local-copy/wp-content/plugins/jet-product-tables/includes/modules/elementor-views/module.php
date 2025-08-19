<?php

namespace Jet_WC_Product_Table\Modules\Elementor_Views;

use Jet_WC_Product_Table\Plugin;
use Jet_WC_Product_Table\Table;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Elementor views module
 */
class Module {
	public function __construct() {
		add_action( 'elementor/widgets/widgets_registered', [
			$this,
			'register_product_table_widget',
		] );

		add_action( 'elementor/frontend/after_register_styles', [
			$this,
			'register_styles',
		] );

		add_action( 'elementor/editor/after_enqueue_styles', [
			$this,
			'register_admin_styles',
		] );

		add_action( 'elementor/preview/enqueue_styles', [
			$this,
			'register_preview_styles',
		] );
	}

	/**
	 * Registers the Product Table widget with Elementor.
	 */
	public function register_product_table_widget() {
		\Elementor\Plugin::instance()->widgets_manager->register_widget_type( new Product_Table_Widget() );
	}

	/**
	 * Registers styles for Elementor editor.
	 */
	public function register_admin_styles() {
		wp_register_style(
			'jet-product-tables-icons',
			JET_WC_PT_URL . 'assets/lib/jet-product-tables-icons/style.css',
			[],
			JET_WC_PT_VERSION
		);

		wp_enqueue_style( 'jet-product-tables-icons' );
	}

	public function register_preview_styles() {
		$table = new Table();
		$table->enqueue_assets();
	}

	/**
	 * Registers styles for Elementor widgets.
	 */
	public function register_styles() {
		wp_register_style(
			'jet-product-tables-table',
			JET_WC_PT_URL . 'assets/css/public/table.css',
			[],
			JET_WC_PT_VERSION
		);

		wp_enqueue_style( 'jet-product-tables-table' );
	}
}
