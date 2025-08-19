<?php
namespace Jet_WC_Product_Table\Components\Query\Query_Types;

/**
 * Handles queries for product variations, providing a structured way to access variation data.
 */
class Cross_Sell_Products_Query extends Variations_Query {

	/**
	 * Fetches and returns product variations based on defined query parameters.
	 *
	 * @return void
	 */
	public function fetch_query_result() {

		// Get visible cross sells then sort them at random.
		$cross_sells = array_filter( array_map( 'wc_get_product', WC()->cart->get_cross_sells() ), 'wc_products_array_filter_visible' );

		// Handle orderby and limit results.
		$orderby     = apply_filters( 'woocommerce_cross_sells_orderby', 'rand' );
		$order       = apply_filters( 'woocommerce_cross_sells_order', 'desc' );
		$cross_sells = wc_products_array_orderby( $cross_sells, $orderby, $order );
		$limit       = intval( apply_filters( 'woocommerce_cross_sells_total', 4 ) );
		$cross_sells = $limit > 0 ? array_slice( $cross_sells, 0, $limit ) : $cross_sells;

		$this->query_result = $cross_sells;
	}
}
