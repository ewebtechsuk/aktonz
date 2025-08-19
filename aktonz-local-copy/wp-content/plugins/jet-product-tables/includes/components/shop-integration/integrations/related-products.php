<?php
namespace Jet_WC_Product_Table\Components\Shop_Integration\Integrations;

use Jet_WC_Product_Table\Plugin;
use Jet_WC_Product_Table\Table;

/**
 * An abstract class that defines the essential structure and functionality of a column type in the product table.
 * All specific column types must extend this class and implement its abstract methods.
 */
class Related_Products extends Base_Integration {

	/**
	 * Retrieves the unique identifier of the column.
	 * Must be implemented by the subclass to return a string that uniquely identifies the column type.
	 *
	 * @return string The unique identifier of the column.
	 */
	public function get_id() {
		return 'related_products';
	}

	/**
	 * Retrieves the display name of the column.
	 * Must be implemented by the subclass to return the name that will be shown in the UI for the column.
	 *
	 * @return string The display name of the column.
	 */
	public function get_name() {
		return __( 'Related Products', 'jet-wc-product-table' );
	}

	/**
	 * Description of current integration type for settings page
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'Add related products table insted of related products list on a single product page.', 'jet-wc-product-table' );
	}

	/**
	 * Check if integration page is currently displaying
	 *
	 * @return boolean [description]
	 */
	public function is_integration_page_now() {
		return function_exists( 'is_product' ) && is_product();
	}

	/**
	 * Get type of query for current integration
	 *
	 * @return string
	 */
	public function get_query_type() {
		return 'related_products';
	}

	/**
	 * Apply current integration
	 *
	 * @return void
	 */
	public function apply() {
		remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );
		add_action( 'woocommerce_after_single_product_summary', [ $this, 'render_integration_table' ], 20 );
	}
}
