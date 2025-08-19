<?php
namespace Jet_WC_Product_Table\Components\Shop_Integration\Integrations;

use Jet_WC_Product_Table\Plugin;
use Jet_WC_Product_Table\Table;

class Cross_Sell_Products extends Base_Integration {

	public function get_id() {
		return 'cross_sell_products';
	}

	public function get_name() {
		return __( 'Cross-Sell Products', 'jet-wc-product-table' );
	}

	public function get_description() {
		return __( 'Replace the default cross-sell products on the cart page with a product table.', 'jet-wc-product-table' );
	}

	public function is_integration_page_now() {
		$is_cart_page = function_exists( 'is_cart' ) && is_cart();
		return $is_cart_page;
	}

	/**
	 * Get type of query for current integration
	 *
	 * @return string
	 */
	public function get_query_type() {
		return 'cross_sell_products';
	}

	public function apply() {
		remove_action( 'woocommerce_cart_collaterals', 'woocommerce_cross_sell_display', 10 );
		add_action( 'woocommerce_cart_collaterals', [ $this, 'render_integration_table' ], 21 );
		add_filter( 'render_block', [ $this, 'render_block' ], 20, 2 );
	}

	/**
	 * Filters the content of a single block.
	 *
	 * @param string $block_content The block content.
	 * @param array  $block         The full block, including name and attributes.
	 *
	 * @return string
	 */
	public function render_block( $block_content, $block ): string {

		// Bail early in the admin area.
		if ( is_admin() || wp_is_json_request() || wp_doing_ajax() ) {
			return $block_content;
		}

		// Check if the block is an Woocommerce block.
		if ( isset( $block['blockName'] ) && 'woocommerce/cart-cross-sells-block' === $block['blockName'] ) {
			Plugin::instance()->assets->set_inline_in_footer_trigger( true );
			return $this->get_integration_table();
		}

		return $block_content;
	}
}
