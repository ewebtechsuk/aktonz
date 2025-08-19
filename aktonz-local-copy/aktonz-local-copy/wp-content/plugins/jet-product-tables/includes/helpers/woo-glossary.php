<?php

namespace Jet_WC_Product_Table\Helpers;

use Jet_WC_Product_Table\Plugin;
use Jet_WC_Product_Table\Components\Columns\Column_Types\Base_Column;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Woo_Glossary {
	/**
	 * Hold the class instance.
	 * @var Woo_Glossary|null
	 */
	private static $instance = null;

	/**
	 * Get the single instance of the class.
	 * @return Woo_Glossary
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Formats options array for different use cases.
	 * If $format is 'gutenberg', formats the options as an array of
	 * 'value' and 'label' pairs suitable for Gutenberg select controls.
	 *
	 * @param array  $options Array of options to format.
	 * @param string $format Optional format type, 'default' or 'gutenberg'.
	 *
	 * @return array Formatted options array.
	 */
	public function format_options( $options = [], $format = 'default' ) {
		$options = is_array( $options ) ? $options : [];

		if ( 'gutenberg' === $format ) {
			$format_options = [];
			foreach ( $options as $type => $label ) {
				$format_options[] = [
					'value' => $type,
					'label' => $label,
				];
			}
			$options = $format_options;
		}

		return $options;
	}

	/**
	 * Get the list of WooCommerce product types.
	 *
	 * @param string $format Format for options, 'default' or 'gutenberg'.
	 *
	 * @return array
	 */
	public function get_product_types( $format = 'default' ) {
		return $this->format_options( wc_get_product_types(), $format );
	}

	/**
	 * Get the list of post statuses.
	 *
	 * @param string $format Format for options, 'default' or 'gutenberg'.
	 *
	 * @return array
	 */
	public function get_post_statuses( $format = 'default' ) {
		return $this->format_options( get_post_statuses(), $format );
	}

	/**
	 * Get the order options for sorting (Date/ID/Name/Type/Random/Modified).
	 *
	 * @param string $format Format for options, 'default' or 'gutenberg'.
	 *
	 * @return array
	 */
	public function get_order_by_options( $format = 'default' ) {
		$order_by_options = [
			'date'     => __( 'Date', 'jet-wc-product-table' ),
			'ID'       => __( 'ID', 'jet-wc-product-table' ),
			'name'     => __( 'Name', 'jet-wc-product-table' ),
			'type'     => __( 'Type', 'jet-wc-product-table' ),
			'rand'     => __( 'Random', 'jet-wc-product-table' ),
			'modified' => __( 'Modified', 'jet-wc-product-table' ),
		];

		return $this->format_options( $order_by_options, $format );
	}

	/**
	 * Get the order options for sorting (ASC/DESC).
	 *
	 * @param string $format Format for options, 'default' or 'gutenberg'.
	 *
	 * @return array
	 */
	public function get_order_options( $format = 'default' ) {
		$order_options = [
			'ASC'  => __( 'ASC', 'jet-wc-product-table' ),
			'DESC' => __( 'DESC', 'jet-wc-product-table' ),
		];

		return $this->format_options( $order_options, $format );
	}

	/**
	 * Get the visibility options for WooCommerce products.
	 *
	 * @param string $format Format for options, 'default' or 'gutenberg'.
	 *
	 * @return array
	 */
	public function get_visibility_options( $format = 'default' ) {
		$visibility_options = [
			'visible' => __( 'Catalog & Search', 'jet-wc-product-table' ),
			'catalog' => __( 'Catalog', 'jet-wc-product-table' ),
			'search'  => __( 'Search', 'jet-wc-product-table' ),
			'hidden'  => __( 'Hidden', 'jet-wc-product-table' ),
		];

		return $this->format_options( $visibility_options, $format );
	}

	/**
	 * Get the backorder options for WooCommerce products.
	 *
	 * This method returns an array of backorder options available for products,
	 * such as allowing backorders, not allowing them, or notifying customers.
	 * The options can be formatted for either the default format or for Gutenberg usage.
	 *
	 * @param string $format Format for options, 'default' or 'gutenberg'.
	 *
	 * @return array
	 */
	public function get_backorder_options( $format = 'default' ) {
		$backorder_options = [
			'yes'    => __( 'Yes', 'jet-wc-product-table' ),
			'no'     => __( 'No', 'jet-wc-product-table' ),
			'notify' => __( 'Notify', 'jet-wc-product-table' ),
		];

		return $this->format_options( $backorder_options, $format );
	}

	/**
	 * Get the stock status options for WooCommerce products.
	 *
	 * This method returns an array of stock status options, indicating whether
	 * a product is in stock or out of stock. The returned options can be formatted
	 * for default usage or for Gutenberg.
	 *
	 * @param string $format Format for options, 'default' or 'gutenberg'.
	 *
	 * @return array
	 */
	public function get_stock_status_options( $format = 'default' ) {
		$stock_status_options = [
			'instock'    => __( 'In Stock', 'jet-wc-product-table' ),
			'outofstock' => __( 'Out of Stock', 'jet-wc-product-table' ),
		];

		return $this->format_options( $stock_status_options, $format );
	}

	/**
	 * Get the tax status options for WooCommerce products.
	 *
	 * This method returns an array of tax status options, indicating whether
	 * a product is taxable, subject to shipping tax only, or not taxed at all.
	 * The returned options can be formatted for default usage or for Gutenberg.
	 *
	 * @param string $format Format for options, 'default' or 'gutenberg'.
	 *
	 * @return array
	 */
	public function get_tax_status_options( $format = 'default' ) {
		$tax_status_options = [
			'taxable'  => __( 'Taxable', 'jet-wc-product-table' ),
			'shipping' => __( 'Shipping', 'jet-wc-product-table' ),
			'none'     => __( 'None', 'jet-wc-product-table' ),
		];

		return $this->format_options( $tax_status_options, $format );
	}

	/**
	 * Retrieve and format presets options.
	 *
	 * Returns presets formatted for general use or Gutenberg, based on the specified format.
	 * For 'gutenberg', it provides 'value' and 'label' pairs; otherwise, it prefixes preset IDs with 'id_'.
	 *
	 * @param  string $format Format type, 'default' or 'gutenberg'.
	 *
	 * @return array Formatted presets options.
	 */
	public static function get_presets_options( $format = 'default' ) {
		$presets = Plugin::instance()->presets->get_presets();

		if ( 'gutenberg' === $format ) {
			return Plugin::instance()->presets->get_presets_for_js();
		}

		$formatted_presets = [];
		foreach ( $presets as $preset ) {
			$formatted_presets[ 'id_' . $preset['ID'] ] = $preset['name'];
		}

		return $formatted_presets;
	}

	/**
	 * Retrieve available column options for use in the Elementor control.
	 *
	 * This method collects all registered columns and formats them as an associative array
	 * where each key is a column ID (value) and each value is the column label (name).
	 * These formatted options are suitable for populating dropdown selectors in Elementor.
	 *
	 * @return array Associative array of column options with column IDs as keys and labels as values.
	 */
	public function get_columns_options() {
		$columns = Plugin::instance()->columns_controller->get_columns_for_js();

		$formatted_columns = [];
		foreach ( $columns as $column ) {
			$formatted_columns[ $column['value'] ] = $column['label'];
		}

		return $formatted_columns;
	}

	/**
	 * Retrieve available filter options for use in the Elementor control.
	 *
	 * This method collects all registered filters and formats them as an associative array
	 * where each key is a filter ID (value) and each value is the filter label (name).
	 * These formatted options are suitable for populating dropdown selectors in Elementor.
	 *
	 * @return array Associative array of filter options with filter IDs as keys and labels as values.
	 */
	public function get_filters_options() {
		$filters = Plugin::instance()->filters_controller->get_filter_types_for_js();

		$formatted_filters = [];
		foreach ( $filters as $filter ) {
			$formatted_filters[ $filter['value'] ] = $filter['label'];
		}

		return $formatted_filters;
	}
}
