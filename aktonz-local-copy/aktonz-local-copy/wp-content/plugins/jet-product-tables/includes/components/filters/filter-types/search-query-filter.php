<?php

namespace Jet_WC_Product_Table\Components\Filters\Filter_Types;

use Jet_WC_Product_Table\Plugin;

class Search_Query_Filter extends Base_Filter {

	public function get_id() {
		return 'search_query';
	}

	public function get_name() {
		return __( 'Search Filter', 'jet-wc-product-table' );
	}

	public function set_request( $query_var, $value, $query, $filters = null ) {
		if ( ! $value ) {
			return;
		}

		// General search query
		$query->set_query_prop( 's', $value );

		if ( is_null( $filters ) ) {
			$filters = Plugin::instance()->settings->get( 'filters' );
		}

		foreach ( $filters as $filter ) {
			$filter_type = $filter['id'] ?? false;
			if ( 'search_query' !== $filter_type ) {
				continue;
			}

			$search_by_sku           = filter_var( $filter['search_by_sku'] ?? false, FILTER_VALIDATE_BOOLEAN );
			$search_by_custom_fields = filter_var( $filter['search_by_custom_fields'] ?? false, FILTER_VALIDATE_BOOLEAN );
			$custom_fields           = ! empty( $filter['custom_fields'] ) ? explode( ',', $filter['custom_fields'] ) : [];

			/**
			 * Custom SKU Search using posts_clauses
			 */
			if ( $search_by_sku || $search_by_custom_fields ) {
				add_filter( 'posts_clauses', function ( $args ) use ( $value, $search_by_sku, $search_by_custom_fields, $custom_fields ) {
					global $wpdb;
					$where = $args['where'];

					// Generate a unique cache key based on search value, SKU flag, and custom fields
					$cache_key_components = [
						'value'         => $value,
						'search_by_sku' => $search_by_sku ? 'sku' : '',
						'custom_fields' => $custom_fields ? implode( ',', $custom_fields ) : '',
					];
					$cache_key            = 'jet_wc_search_' . md5( implode( '_', $cache_key_components ) );
					$search_ids           = wp_cache_get( $cache_key, 'product_search' );

					if ( false === $search_ids ) {
						$escaped_value = esc_sql( $wpdb->esc_like( $value ) );

						// Build the meta_query conditions for SKU and custom fields
						$meta_query_conditions = [];
						if ( $search_by_sku ) {
							$meta_query_conditions[] = $wpdb->prepare( "(meta_key = '_sku' AND meta_value LIKE %s)", '%' . $escaped_value . '%' );
						}

						if ( $search_by_custom_fields && ! empty( $custom_fields ) ) {
							$custom_fields_placeholders = implode( ', ', array_fill( 0, count( $custom_fields ), '%s' ) );
							$meta_query_conditions[]    = $wpdb->prepare(
								"(meta_key IN ($custom_fields_placeholders) AND meta_value LIKE %s)", // phpcs:ignore
								...array_merge( $custom_fields, [ '%' . $escaped_value . '%' ] )
							);
						}

						// Combine the conditions with OR logic
						$meta_query_sql = implode( ' OR ', $meta_query_conditions );

						// Execute the combined query
						$search_ids = $wpdb->get_col( // phpcs:ignore
							"SELECT DISTINCT post_id 
                 					FROM {$wpdb->postmeta} 
                 					WHERE $meta_query_sql" // phpcs:ignore
						);

						// Cache the results
						wp_cache_set( $cache_key, $search_ids, 'product_search', 60 );
					}

					// Extend the WHERE clause if there are matching post IDs
					if ( ! empty( $search_ids ) ) {
						$where .= ' OR ' . $wpdb->posts . '.ID IN (' . implode( ',', array_map( 'intval', $search_ids ) ) . ')';
					}

					$args['where'] = $where;

					return $args;
				} );
			}

			break;
		}

		/**
		 * Case-Insensitive Search for Titles, Content, and Excerpts
		 */
		add_filter( 'posts_search', function ( $search, $query ) use ( $value ) {
			global $wpdb;

			if ( is_admin() || ! $query->is_search() || ! $query->is_main_query() ) {
				return $search;
			}

			$search_term = esc_sql( $wpdb->esc_like( $value ) );

			if ( ! empty( $search ) ) {
				$search .= ' AND (';
			} else {
				$search .= ' AND ( 1=1 ';
			}

			$search .= $wpdb->prepare(
				' LOWER(' . $wpdb->posts . '.post_title) LIKE LOWER(%s)
			OR LOWER(' . $wpdb->posts . '.post_content) LIKE LOWER(%s)
			OR LOWER(' . $wpdb->posts . '.post_excerpt) LIKE LOWER(%s) ',
				'%' . $search_term . '%',
				'%' . $search_term . '%',
				'%' . $search_term . '%'
			);

			$search .= ' )';

			return $search;
		}, 999, 2 );
	}

	protected function render( $attrs = [] ) {

		$show_search_button  = isset( $attrs['show_search_button'] ) ? $attrs['show_search_button'] : true;
		$show_search_button  = filter_var( $show_search_button, FILTER_VALIDATE_BOOLEAN );
		$search_on_typing    = isset( $attrs['search_on_typing'] ) ? $attrs['search_on_typing'] : false;
		$search_on_typing    = filter_var( $search_on_typing, FILTER_VALIDATE_BOOLEAN );
		$search_button_label = isset( $attrs['search_button_label'] ) ? wp_kses_post( $attrs['search_button_label'] ) : esc_html__( 'Search', 'jet-wc-product-table' );
		$placeholder         = $attrs['search_input'] ?? __( 'Search products...', 'jet-wc-product-table' );
		$min_chars           = isset( $attrs['min_chars_to_start_search'] ) ? absint( $attrs['min_chars_to_start_search'] ) : 0;

		$search_button = '';

		if ( $show_search_button ) {

			$button_classes = apply_filters(  'jet-wc-product-table/components/filters/types/search/button-classes', [
				'jet-wc-product-filter-button',
				'jet-wc-search-button',
				'button',
			] );

			$search_button = sprintf(
				'<button type="button" class="%2$s">%1$s</button>',
				$search_button_label,
				implode( ' ', $button_classes )
			);
		}

		$this->add_attribute_to_stack( 'type', 'search' );
		$this->add_attribute_to_stack( 'name', $this->get_filter_el_name( $attrs ) );
		$this->add_attribute_to_stack( 'class', 'jet-wc-product-filter' );
		$this->add_attribute_to_stack( 'placeholder', $placeholder );
		$this->add_attribute_to_stack( 'data-ui', 'search' );
		$this->add_attribute_to_stack( 'data-search-on-typing', $search_on_typing ? 1 : 0 );
		$this->add_attribute_to_stack( 'data-min-chars-to-start-search', $min_chars );

		// phpcs:ignore
		printf( '<input %1$s/>%2$s', $this->get_attributes_string(), $search_button );
	}

	/**
	 * Get selecte filter value in human-readable format
	 *
	 * @param  string $query_var Query varisble. Not used in case of search.
	 * @param  string $value     User inputted value.
	 * @return string
	 */
	public function verbose_selection( $query_var, $value ) {

		if ( ! $value ) {
			return;
		}

		return sprintf( '%1$s: %2$s', __( 'Search', 'jet-wc-product-table' ), esc_html( $value ) );
	}

	/**
	 * Additional settings for the filter type.
	 *
	 * @return array The additional settings.
	 */
	public function additional_settings() {
		return [
			'search_input'              => [
				'label'       => __( 'Placeholder', 'jet-wc-product-table' ),
				'type'        => 'search',
				'default'     => __( 'Search products...', 'jet-wc-product-table' ),
				'description' => 'Set the placeholder text for the filter.',
			],
			'search_on_typing'          => [
				'label'   => __( 'Search on typing', 'jet-wc-product-table' ),
				'type'    => 'toggle',
				'default' => false,
			],
			'min_chars_to_start_search' => [
				'label'       => __( 'Min chars to start search', 'jet-wc-product-table' ),
				'type'        => 'text',
				'default'     => '3',
				'description' => 'Minimum characters required to start the search.',
			],
			'show_search_button'        => [
				'label'       => __( 'Show search button', 'jet-wc-product-table' ),
				'type'        => 'toggle',
				'default'     => true,
				'description' => 'Toggle to show or hide the search button.',
			],
			'search_button_label'       => [
				'label'       => __( 'Search button label', 'jet-wc-product-table' ),
				'type'        => 'text',
				'default'     => __( 'Search', 'jet-wc-product-table' ),
				'description' => 'Label for the search button.',
			],
			'search_by_sku'             => [
				'label'   => __( 'Search by SKU', 'jet-wc-product-table' ),
				'type'    => 'toggle',
				'default' => false,
			],
			'search_by_custom_fields'   => [
				'label'   => __( 'Search by custom fields', 'jet-wc-product-table' ),
				'type'    => 'toggle',
				'default' => false,
			],
			'custom_fields'             => [
				'label'       => __( 'Search by fields', 'jet-wc-product-table' ),
				'type'        => 'text',
				'default'     => '',
				'description' => __( 'A comma-separated list of custom fields to search by.', 'jet-wc-product-table' ),
			],
		];
	}
}
