<?php

namespace Jet_WC_Product_Table\Modules\Bricks_Views;

use Jet_WC_Product_Table\Traits\Table_Render_By_Attributes;
use Jet_WC_Product_Table\Helpers\Woo_Glossary;
use Jet_WC_Product_Table\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Product_Table_Element extends \Bricks\Element {

	use Table_Render_By_Attributes;

	/**
	 * Element category in Bricks Editor
	 *
	 * @var string
	 */
	public $category = 'general';

	/**
	 * Element name
	 *
	 * @var string
	 */
	public $name = 'jet-wc-product-table';

	/**
	 * Element icon
	 *
	 * @var string
	 */
	public $icon = 'fas fa-table';

	/**
	 * CSS selector for the element
	 *
	 * @var string
	 */
	public $css_selector = '.jet-wc-product-table-wrapper';

	/**
	 * Element scripts
	 *
	 * @var array
	 */
	public $scripts = [];

	/**
	 * Returns the name of the element
	 */
	public function get_label() {
		return esc_html__( 'Product Table', 'jet-wc-product-table' );
	}

	/**
	 * Define control groups
	 */
	public function set_control_groups() {
		$this->control_groups['preset'] = [
			'title' => esc_html__( 'Preset', 'jet-wc-product-table' ),
			'tab'   => 'content',
		];

		$this->control_groups['query'] = [
			'title' => esc_html__( 'Query', 'jet-wc-product-table' ),
			'tab'   => 'content',
		];

		$this->control_groups['columns'] = [
			'title' => esc_html__( 'Columns', 'jet-wc-product-table' ),
			'tab'   => 'content',
		];

		$this->control_groups['settings'] = [
			'title' => esc_html__( 'Settings', 'jet-wc-product-table' ),
			'tab'   => 'content',
		];

		$this->control_groups['filters'] = [
			'title' => esc_html__( 'Filters', 'jet-wc-product-table' ),
			'tab'   => 'content',
		];
	}

	/**
	 * Define controls for the element
	 */
	public function set_controls() {
		$this->controls['preset_heading'] = [
			'tab'   => 'content',
			'group' => 'preset',
			'label' => esc_html__( 'Presets', 'jet-wc-product-table' ),
			'type'  => 'heading',
		];

		// Preset control
		$this->controls['preset'] = [
			'tab'         => 'content',
			'group'       => 'preset',
			'label'       => esc_html__( 'Use Preset', 'jet-wc-product-table' ),
			'type'        => 'select',
			'options'     => array_merge(
				[ '' => esc_html__( 'Select preset...', 'jet-wc-product-table' ) ],
				Woo_Glossary::instance()->get_presets_options()
			),
			'default'     => '',
			'description' => esc_html__( 'This preset will be applied for Columns, Settings, and Filters. If preset is selected, columns will be inherited from the preset.', 'jet-wc-product-table' ),
		];

		$this->controls['query_type_divider'] = [
			'type'  => 'divider',
			'tab'   => 'content',
			'group' => 'query',
		];

		$this->controls['query_type'] = [
			'tab'      => 'content',
			'group'    => 'query',
			'label'    => esc_html__( 'Query Type', 'jet-wc-product-table' ),
			'type'     => 'select',
			'default'  => 'products',
			'options'  => [
				'products'   => esc_html__( 'Products', 'jet-wc-product-table' ),
				'variations' => esc_html__( 'Product Variations', 'jet-wc-product-table' ),
			],
		];

		// General Query Heading
		$this->controls['general_heading'] = [
			'tab'       => 'content',
			'group'     => 'query',
			'label'     => esc_html__( 'General', 'jet-wc-product-table' ),
			'type'      => 'heading',
			'required' => [ 'query_type', '=', 'products' ],
		];

		$this->controls['product_status'] = [
			'tab'     => 'content',
			'group'   => 'query',
			'label'   => esc_html__( 'Product Status', 'jet-wc-product-table' ),
			'type'    => 'select',
			'options' => get_post_statuses(),
			'default' => 'publish',
			'description' => sprintf(
				esc_html__( 'One of %1$s, %2$s, %3$s, %4$s, or a custom status.', 'jet-wc-product-table' ),
				esc_html__( 'draft', 'jet-wc-product-table' ),
				esc_html__( 'pending', 'jet-wc-product-table' ),
				esc_html__( 'private', 'jet-wc-product-table' ),
				esc_html__( 'publish', 'jet-wc-product-table' )
			),
			'required' => [ 'query_type', '=', 'products' ],
		];

		$this->controls['product_limit'] = [
			'tab'         => 'content',
			'group'       => 'query',
			'label'       => esc_html__( 'Products Limit', 'jet-wc-product-table' ),
			'type'        => 'number',
			'default'     => 10,
			'min'         => 1,
			'step'        => 1,
			'help'        => esc_html__( 'Maximum number of results to retrieve', 'jet-wc-product-table' ),
			'description' => esc_html__( 'Maximum number of results to retrieve', 'jet-wc-product-table' ),
			'required' => [ 'query_type', '=', 'products' ],
		];

		$this->controls['product_page'] = [
			'tab'         => 'content',
			'group'       => 'query',
			'label'       => esc_html__( 'Page', 'jet-wc-product-table' ),
			'type'        => 'number',
			'min'         => 1,
			'help'        => esc_html__( 'Page of results to retrieve', 'jet-wc-product-table' ),
			'description' => esc_html__( 'Page of results to retrieve', 'jet-wc-product-table' ),
			'required' => [ 'query_type', '=', 'products' ],
		];

		$this->controls['product_offset'] = [
			'tab'         => 'content',
			'group'       => 'query',
			'label'       => esc_html__( 'Offset', 'jet-wc-product-table' ),
			'type'        => 'number',
			'min'         => 0,
			'help'        => esc_html__( 'Amount to offset product results. May affect pagination.', 'jet-wc-product-table' ),
			'description' => esc_html__( 'Amount to offset product results. Note: offset overrides the paged parameter and can break pagination and load more.', 'jet-wc-product-table' ),
			'required' => [ 'query_type', '=', 'products' ],
		];

		$this->controls['product_include'] = [
			'tab'         => 'content',
			'group'       => 'query',
			'label'       => esc_html__( 'Include Products', 'jet-wc-product-table' ),
			'type'        => 'text',
			'default'     => '',
			'help'        => esc_html__( 'Comma-separated list of product IDs to include', 'jet-wc-product-table' ),
			'description' => esc_html__( 'Comma-separated list of product IDs to include', 'jet-wc-product-table' ),
			'required' => [ 'query_type', '=', 'products' ],
		];

		$this->controls['product_exclude'] = [
			'tab'         => 'content',
			'group'       => 'query',
			'label'       => esc_html__( 'Exclude Products', 'jet-wc-product-table' ),
			'type'        => 'text',
			'default'     => '',
			'help'        => esc_html__( 'Comma-separated list of product IDs to exclude', 'jet-wc-product-table' ),
			'description' => esc_html__( 'Comma-separated list of product IDs to exclude', 'jet-wc-product-table' ),
			'required' => [ 'query_type', '=', 'products' ],
		];

		$this->controls['product_type'] = [
			'tab'         => 'content',
			'group'       => 'query',
			'label'       => esc_html__( 'Product Type', 'jet-wc-product-table' ),
			'type'        => 'select',
			'options'     => wc_get_product_types(),
			'default'     => '',
			'help'        => esc_html__( 'Filter products by type', 'jet-wc-product-table' ),
			'description' => esc_html__( 'Filter products by type', 'jet-wc-product-table' ),
			'required' => [ 'query_type', '=', 'products' ],
		];

		$this->controls['product_orderby'] = [
			'tab'         => 'content',
			'group'       => 'query',
			'label'       => esc_html__( 'Order By', 'jet-wc-product-table' ),
			'type'        => 'select',
			'options'     => Woo_Glossary::instance()->get_order_by_options(),
			'default'     => 'date',
			'help'        => esc_html__( 'Field to sort by', 'jet-wc-product-table' ),
			'description' => esc_html__( 'Field to sort by', 'jet-wc-product-table' ),
			'required' => [ 'query_type', '=', 'products' ],
		];

		$this->controls['product_order'] = [
			'tab'         => 'content',
			'group'       => 'query',
			'label'       => esc_html__( 'Order', 'jet-wc-product-table' ),
			'type'        => 'select',
			'options'     => Woo_Glossary::instance()->get_order_options(),
			'default'     => 'DESC',
			'help'        => esc_html__( 'Sort order: ASC or DESC', 'jet-wc-product-table' ),
			'description' => esc_html__( 'Sort order: ASC or DESC', 'jet-wc-product-table' ),
			'required' => [ 'query_type', '=', 'products' ],
		];

		// Product Section Controls
		$this->controls['product_divider_before'] = [
			'type'      => 'divider',
			'tab'       => 'content',
			'group'     => 'query',
			'required' => [ 'query_type', '=', 'products' ],
		];

		$this->controls['product_heading'] = [
			'type'  => 'heading',
			'tab'   => 'content',
			'group' => 'query',
			'label' => esc_html__( 'Product', 'jet-wc-product-table' ),
			'required' => [ 'query_type', '=', 'products' ],
		];

		$this->controls['product_divider_after'] = [
			'type'      => 'divider',
			'tab'       => 'content',
			'group'     => 'query',
			'required' => [ 'query_type', '=', 'products' ],
		];

		$this->controls['product_sku'] = [
			'tab'       => 'content',
			'group'     => 'query',
			'label'     => esc_html__( 'SKU', 'jet-wc-product-table' ),
			'type'      => 'text',
			'default'   => '',
			'help'        => esc_html__( 'Get product with given SKU', 'jet-wc-product-table' ),
			'description' => esc_html__( 'Get product with given SKU', 'jet-wc-product-table' ),
			'required' => [ 'query_type', '=', 'products' ],
		];

		$this->controls['product_name'] = [
			'tab'       => 'content',
			'group'     => 'query',
			'label'     => esc_html__( 'Name', 'jet-wc-product-table' ),
			'type'      => 'text',
			'default'   => '',
			'help'        => esc_html__( 'Get product with given name', 'jet-wc-product-table' ),
			'description' => esc_html__( 'Get product with given name', 'jet-wc-product-table' ),
			'required' => [ 'query_type', '=', 'products' ],
		];

		$this->controls['product_tag'] = [
			'tab'       => 'content',
			'group'     => 'query',
			'label'     => esc_html__( 'Tag', 'jet-wc-product-table' ),
			'type'      => 'text',
			'default'   => '',
			'help'        => esc_html__( 'Comma-separated list of tags', 'jet-wc-product-table' ),
			'description' => esc_html__( 'Comma-separated list of tags', 'jet-wc-product-table' ),
			'required' => [ 'query_type', '=', 'products' ],
		];

		$this->controls['product_tag_id'] = [
			'tab'       => 'content',
			'group'     => 'query',
			'label'     => esc_html__( 'Tag ID', 'jet-wc-product-table' ),
			'type'      => 'text',
			'default'   => '',
			'help'        => esc_html__( 'Comma-separated list of tag IDs', 'jet-wc-product-table' ),
			'description' => esc_html__( 'Comma-separated list of tag IDs', 'jet-wc-product-table' ),
			'required' => [ 'query_type', '=', 'products' ],
		];

		$this->controls['product_category'] = [
			'tab'       => 'content',
			'group'     => 'query',
			'label'     => esc_html__( 'Category', 'jet-wc-product-table' ),
			'type'      => 'text',
			'default'   => '',
			'help'        => esc_html__( 'Comma-separated list of categories', 'jet-wc-product-table' ),
			'description' => esc_html__( 'Comma-separated list of categories', 'jet-wc-product-table' ),
			'required' => [ 'query_type', '=', 'products' ],
		];

		$this->controls['product_category_id'] = [
			'tab'       => 'content',
			'group'     => 'query',
			'label'     => esc_html__( 'Category ID', 'jet-wc-product-table' ),
			'type'      => 'text',
			'default'   => '',
			'help'        => esc_html__( 'Comma-separated list of category IDs', 'jet-wc-product-table' ),
			'description' => esc_html__( 'Comma-separated list of category IDs', 'jet-wc-product-table' ),
			'required' => [ 'query_type', '=', 'products' ],
		];

		$this->controls['product_weight'] = [
			'tab'       => 'content',
			'group'     => 'query',
			'label'     => esc_html__( 'Weight', 'jet-wc-product-table' ),
			'type'      => 'text',
			'default'   => '',
			'help'        => esc_html__( 'Product weight', 'jet-wc-product-table' ),
			'description' => esc_html__( 'Product weight', 'jet-wc-product-table' ),
			'required' => [ 'query_type', '=', 'products' ],
		];

		$this->controls['product_width'] = [
			'tab'       => 'content',
			'group'     => 'query',
			'label'     => esc_html__( 'Width', 'jet-wc-product-table' ),
			'type'      => 'text',
			'default'   => '',
			'help'        => esc_html__( 'Product width', 'jet-wc-product-table' ),
			'description' => esc_html__( 'Product width', 'jet-wc-product-table' ),
			'required' => [ 'query_type', '=', 'products' ],
		];

		$this->controls['product_length'] = [
			'tab'       => 'content',
			'group'     => 'query',
			'label'     => esc_html__( 'Length', 'jet-wc-product-table' ),
			'type'      => 'text',
			'default'   => '',
			'description' => esc_html__( 'Product length', 'jet-wc-product-table' ),
			'required' => [ 'query_type', '=', 'products' ],
		];

		$this->controls['product_height'] = [
			'tab'       => 'content',
			'group'     => 'query',
			'label'     => esc_html__( 'Height', 'jet-wc-product-table' ),
			'type'      => 'text',
			'default'   => '',
			'description' => esc_html__( 'Product height', 'jet-wc-product-table' ),
			'required' => [ 'query_type', '=', 'products' ],
		];

		$this->controls['product_price'] = [
			'tab'       => 'content',
			'group'     => 'query',
			'label'     => esc_html__( 'Price', 'jet-wc-product-table' ),
			'type'      => 'text',
			'default'   => '',
			'description' => esc_html__( 'Product price', 'jet-wc-product-table' ),
			'required' => [ 'query_type', '=', 'products' ],
		];

		$this->controls['product_regular_price'] = [
			'tab'       => 'content',
			'group'     => 'query',
			'label'     => esc_html__( 'Regular Price', 'jet-wc-product-table' ),
			'type'      => 'text',
			'default'   => '',
			'description' => esc_html__( 'Regular price', 'jet-wc-product-table' ),
			'required' => [ 'query_type', '=', 'products' ],
		];

		$this->controls['product_sale_price'] = [
			'tab'       => 'content',
			'group'     => 'query',
			'label'     => esc_html__( 'Sale Price', 'jet-wc-product-table' ),
			'type'      => 'text',
			'default'   => '',
			'description' => esc_html__( 'Sale price', 'jet-wc-product-table' ),
			'required' => [ 'query_type', '=', 'products' ],
		];

		$this->controls['product_total_sales'] = [
			'tab'       => 'content',
			'group'     => 'query',
			'label'     => esc_html__( 'Total Sales', 'jet-wc-product-table' ),
			'type'      => 'text',
			'default'   => '',
			'description' => esc_html__( 'Total sales', 'jet-wc-product-table' ),
			'required' => [ 'query_type', '=', 'products' ],
		];

		$this->controls['product_virtual'] = [
			'tab'       => 'content',
			'group'     => 'query',
			'label'     => esc_html__( 'Virtual', 'jet-wc-product-table' ),
			'type'      => 'select',
			'default'   => '',
			'options'     => [
				''    => __( 'Select an option...', 'jet-wc-product-table' ),
				'yes' => __( 'Yes', 'jet-wc-product-table' ),
				'no'  => __( 'No', 'jet-wc-product-table' ),
			],
			'description' => esc_html__( 'Is the product virtual?', 'jet-wc-product-table' ),
			'required' => [ 'query_type', '=', 'products' ],
		];

		$this->controls['product_featured'] = [
			'tab'       => 'content',
			'group'     => 'query',
			'label'     => esc_html__( 'Featured', 'jet-wc-product-table' ),
			'type'      => 'select',
			'default'   => '',
			'options'     => [
				''    => __( 'Select an option...', 'jet-wc-product-table' ),
				'yes' => __( 'Yes', 'jet-wc-product-table' ),
				'no'  => __( 'No', 'jet-wc-product-table' ),
			],
			'description' => esc_html__( 'Is the product featured?', 'jet-wc-product-table' ),
			'required' => [ 'query_type', '=', 'products' ],
		];

		$this->controls['product_downloadable'] = [
			'tab'       => 'content',
			'group'     => 'query',
			'label'     => esc_html__( 'Downloadable', 'jet-wc-product-table' ),
			'type'      => 'select',
			'default'   => '',
			'options'     => [
				''    => __( 'Select an option...', 'jet-wc-product-table' ),
				'yes' => __( 'Yes', 'jet-wc-product-table' ),
				'no'  => __( 'No', 'jet-wc-product-table' ),
			],
			'description' => esc_html__( 'Is the product downloadable?', 'jet-wc-product-table' ),
			'required' => [ 'query_type', '=', 'products' ],
		];

		$this->controls['product_manage_stock'] = [
			'tab'       => 'content',
			'group'     => 'query',
			'label'     => esc_html__( 'Manage Stock', 'jet-wc-product-table' ),
			'type'      => 'select',
			'default'   => '',
			'options'     => [
				''    => __( 'Select an option...', 'jet-wc-product-table' ),
				'yes' => __( 'Yes', 'jet-wc-product-table' ),
				'no'  => __( 'No', 'jet-wc-product-table' ),
			],
			'description' => esc_html__( 'Is stock managed?', 'jet-wc-product-table' ),
			'required' => [ 'query_type', '=', 'products' ],
		];

		$this->controls['product_sold_individually'] = [
			'tab'       => 'content',
			'group'     => 'query',
			'label'     => esc_html__( 'Sold Individually', 'jet-wc-product-table' ),
			'type'      => 'select',
			'default'   => '',
			'options'     => [
				''    => __( 'Select an option...', 'jet-wc-product-table' ),
				'yes' => __( 'Yes', 'jet-wc-product-table' ),
				'no'  => __( 'No', 'jet-wc-product-table' ),
			],
			'description' => esc_html__( 'Is the product sold individually', 'jet-wc-product-table' ),
			'required' => [ 'query_type', '=', 'products' ],
		];

		$this->controls['product_reviews_allowed'] = [
			'tab'       => 'content',
			'group'     => 'query',
			'label'     => esc_html__( 'Reviews Allowed', 'jet-wc-product-table' ),
			'type'      => 'select',
			'default'   => '',
			'options'     => [
				''    => __( 'Select an option...', 'jet-wc-product-table' ),
				'yes' => __( 'Yes', 'jet-wc-product-table' ),
				'no'  => __( 'No', 'jet-wc-product-table' ),
			],
			'description' => esc_html__( 'Choose to show products where reviews are allowed or not.', 'jet-wc-product-table' ),
			'required' => [ 'query_type', '=', 'products' ],
		];

		$this->controls['product_backorder'] = [
			'tab'       => 'content',
			'group'     => 'query',
			'label'     => esc_html__( 'Backorder', 'jet-wc-product-table' ),
			'type'      => 'select',
			'default'   => '',
			'options'   => array_merge(
				[ '' => esc_html__( 'Select a backorder...', 'jet-wc-product-table' ) ],
				Woo_Glossary::instance()->get_backorder_options()
			),
			'description' => esc_html__( 'Backorder status', 'jet-wc-product-table' ),
			'required' => [ 'query_type', '=', 'products' ],
		];

		$this->controls['product_visibility'] = [
			'tab'       => 'content',
			'group'     => 'query',
			'label'     => esc_html__( 'Visibility', 'jet-wc-product-table' ),
			'type'      => 'select',
			'default'   => '',
			'options'   => array_merge(
				[ '' => esc_html__( 'Select visibility...', 'jet-wc-product-table' ) ],
				Woo_Glossary::instance()->get_visibility_options()
			),
			'description' => esc_html__( 'Product visibility', 'jet-wc-product-table' ),
			'required' => [ 'query_type', '=', 'products' ],
		];

		$this->controls['product_stock_quantity'] = [
			'tab'       => 'content',
			'group'     => 'query',
			'label'     => esc_html__( 'Stock Quantity', 'jet-wc-product-table' ),
			'type'      => 'number',
			'default'   => '',
			'description' => esc_html__( 'Specify the stock quantity for the product', 'jet-wc-product-table' ),
			'required' => [ 'query_type', '=', 'products' ],
		];

		$this->controls['product_stock_status'] = [
			'tab'       => 'content',
			'group'     => 'query',
			'label'     => esc_html__( 'Stock Status', 'jet-wc-product-table' ),
			'type'      => 'select',
			'default'   => '',
			'options'   => array_merge(
				[ '' => esc_html__( 'Select stock status...', 'jet-wc-product-table' ) ],
				Woo_Glossary::instance()->get_stock_status_options()
			),
			'description' => esc_html__( 'Stock status', 'jet-wc-product-table' ),
			'required' => [ 'query_type', '=', 'products' ],
		];

		$this->controls['product_shipping_class'] = [
			'tab'       => 'content',
			'group'     => 'query',
			'label'     => esc_html__( 'Shipping Class', 'jet-wc-product-table' ),
			'type'      => 'text',
			'default'   => '',
			'description' => esc_html__( 'Specify the shipping class slugs (comma-separated)', 'jet-wc-product-table' ),
			'required' => [ 'query_type', '=', 'products' ],
		];

		$this->controls['product_tax_status'] = [
			'tab'       => 'content',
			'group'     => 'query',
			'label'     => esc_html__( 'Tax Status', 'jet-wc-product-table' ),
			'type'      => 'select',
			'default'   => '',
			'options'   => array_merge(
				[ '' => esc_html__( 'Select tax status...', 'jet-wc-product-table' ) ],
				Woo_Glossary::instance()->get_tax_status_options()
			),
			'description' => esc_html__( 'Tax status', 'jet-wc-product-table' ),
			'required' => [ 'query_type', '=', 'products' ],
		];

		$this->controls['product_tax_class'] = [
			'tab'       => 'content',
			'group'     => 'query',
			'label'     => esc_html__( 'Tax Class', 'jet-wc-product-table' ),
			'type'      => 'text',
			'default'   => '',
			'description' => esc_html__( 'Specify the tax class slug for the product', 'jet-wc-product-table' ),
			'required' => [ 'query_type', '=', 'products' ],
		];

		$this->controls['product_average_rating'] = [
			'tab'       => 'content',
			'group'     => 'query',
			'label'     => esc_html__( 'Average Rating', 'jet-wc-product-table' ),
			'type'      => 'text',
			'default'   => '',
			'description' => esc_html__( 'Specify the average rating for the product', 'jet-wc-product-table' ),
			'required' => [ 'query_type', '=', 'products' ],
		];

		$this->controls['product_download_expiry'] = [
			'tab'       => 'content',
			'group'     => 'query',
			'label'     => esc_html__( 'Download Expiry', 'jet-wc-product-table' ),
			'type'      => 'number',
			'default'   => '',
			'description' => esc_html__( 'Set download expiry for the product (in days)', 'jet-wc-product-table' ),
			'required' => [ 'query_type', '=', 'products' ],
		];

		$this->controls['product_download_limit'] = [
			'tab'       => 'content',
			'group'     => 'query',
			'label'     => esc_html__( 'Download Limit', 'jet-wc-product-table' ),
			'type'      => 'number',
			'default'   => '',
			'description' => esc_html__( 'Set the download limit for the product', 'jet-wc-product-table' ),
			'required' => [ 'query_type', '=', 'products' ],
		];

		$this->controls['product_review_count'] = [
			'tab'       => 'content',
			'group'     => 'query',
			'label'     => esc_html__( 'Review Count', 'jet-wc-product-table' ),
			'type'      => 'number',
			'default'   => '',
			'description' => esc_html__( 'Specify the review count for the product', 'jet-wc-product-table' ),
			'required' => [ 'query_type', '=', 'products' ],
		];

		// Date Section Controls
		$this->controls['date_divider_before'] = [
			'type' => 'divider',
			'tab'  => 'content',
			'group' => 'query',
			'required' => [ 'query_type', '=', 'products' ],
		];

		$this->controls['date_heading'] = [
			'type'  => 'heading',
			'tab'   => 'content',
			'group' => 'query',
			'label' => esc_html__( 'Date', 'jet-wc-product-table' ),
			'required' => [ 'query_type', '=', 'products' ],
		];

		$description_text = esc_html__( 'Accepted values format for date options: YYYY-MM-DD, >=YYYY-MM-DD, >YYYY-MM-DD, <=YYYY-MM-DD, <YYYY-MM-DD', 'jet-wc-product-table' );

		$this->controls['product_date_created'] = [
			'tab'         => 'content',
			'group'       => 'query',
			'label'       => esc_html__( 'Date Created', 'jet-wc-product-table' ),
			'type'        => 'text',
			'default'     => '',
			'description' => $description_text,
			'required' => [ 'query_type', '=', 'products' ],
		];

		$this->controls['product_date_modified'] = [
			'tab'         => 'content',
			'group'       => 'query',
			'label'       => esc_html__( 'Date Modified', 'jet-wc-product-table' ),
			'type'        => 'text',
			'default'     => '',
			'description' => $description_text,
			'required' => [ 'query_type', '=', 'products' ],
		];

		$this->controls['product_date_on_sale_from'] = [
			'tab'         => 'content',
			'group'       => 'query',
			'label'       => esc_html__( 'Date On Sale From', 'jet-wc-product-table' ),
			'type'        => 'text',
			'default'     => '',
			'description' => $description_text,
			'required' => [ 'query_type', '=', 'products' ],
		];

		$this->controls['product_date_on_sale_to'] = [
			'tab'         => 'content',
			'group'       => 'query',
			'label'       => esc_html__( 'Date On Sale To', 'jet-wc-product-table' ),
			'type'        => 'text',
			'default'     => '',
			'description' => $description_text,
			'required' => [ 'query_type', '=', 'products' ],
		];

		$this->controls['date_divider_after'] = [
			'type' => 'divider',
			'tab'  => 'content',
			'group' => 'query',
			'required' => [ 'query_type', '=', 'products' ],
		];

		// Product Variation Specific Controls
		$this->controls['variations_heading'] = [
			'type'  => 'heading',
			'tab'   => 'content',
			'group' => 'query',
			'label' => esc_html__( 'Products Variation', 'jet-wc-product-table' ),
			'required' => [ 'query_type', '=', 'variations' ],
		];

		$this->controls['variation_type'] = [
			'tab'      => 'content',
			'group'    => 'query',
			'label'    => esc_html__( 'Get variations from', 'jet-wc-product-table' ),
			'type'     => 'select',
			'default'  => 'current_product',
			'options'  => [
				'current_product'       => esc_html__( 'Current product', 'jet-wc-product-table' ),
				'specific_product_ids'  => esc_html__( 'Specific product ID(s)', 'jet-wc-product-table' ),
				'specific_product_skus' => esc_html__( 'Specific product SKU(s)', 'jet-wc-product-table' ),
			],
			'required' => [ 'query_type', '=', 'variations' ],
		];

		$this->controls['product_ids'] = [
			'tab'         => 'content',
			'group'       => 'query',
			'label'       => esc_html__( 'Product ID(s)', 'jet-wc-product-table' ),
			'type'        => 'text',
			'placeholder' => esc_html__( 'Enter product ID(s), comma-separated', 'jet-wc-product-table' ),
			'required'    => [
				[ 'query_type', '=', 'variations' ],
				[ 'variation_type', '=', 'specific_product_ids' ],
			],
		];

		$this->controls['product_skus'] = [
			'tab'         => 'content',
			'group'       => 'query',
			'label'       => esc_html__( 'Product SKU(s)', 'jet-wc-product-table' ),
			'type'        => 'text',
			'placeholder' => esc_html__( 'Enter product SKU(s), comma-separated', 'jet-wc-product-table' ),
			'required'    => [
				[ 'query_type', '=', 'variations' ],
				[ 'variation_type', '=', 'specific_product_skus' ],
			],
		];

		// Columns group
		$this->controls['inherit_global_columns'] = [
			'tab'         => 'content',
			'group'       => 'columns',
			'label'       => esc_html__( 'Inherit global columns', 'jet-wc-product-table' ),
			'type'        => 'checkbox',
			'default'     => true,
			'description' => esc_html__( 'If enabled, the table will inherit global columns.', 'jet-wc-product-table' ),
		];

		$static_columns_default = [
			[
				'column_type' => 'Select a column type...',
				'label'       => '',
			],
			[
				'column_type' => 'product-image',
				'label'       => esc_html__( 'Product Image', 'jet-wc-product-table' ),
			],
			[
				'column_type' => 'product-name',
				'label'       => esc_html__( 'Product Name', 'jet-wc-product-table' ),
			],
			[
				'column_type' => 'product-price',
				'label'       => esc_html__( 'Product Price', 'jet-wc-product-table' ),
			],
		];

		$columns_repeater = [
			'column_type' => [
				'label'   => esc_html__( 'Column Type', 'jet-wc-product-table' ),
				'type'    => 'select',
				'options' => array_merge(
					[ '' => esc_html__( 'Select a column type...', 'jet-wc-product-table' ) ],
					Woo_Glossary::instance()->get_columns_options()
				),
				'default' => '',
			],
			'label' => [
				'label'       => esc_html__( 'Label', 'jet-wc-product-table' ),
				'type'        => 'text',
				'default'     => esc_html__( 'Column Label', 'jet-wc-product-table' ),
				'description' => esc_html__( 'Set the column label displayed in the table header.', 'jet-wc-product-table' ),
			],
		];

		$formatted_column_controls = $this->get_formatted_column_controls();
		foreach ( $formatted_column_controls as $control ) {
			$columns_repeater[ $control['id'] ] = $control;
		}

		$this->controls['columns_manager'] = [
			'tab'        => 'content',
			'group'      => 'columns',
			'label'      => esc_html__( 'Columns Manager', 'jet-wc-product-table' ),
			'type'       => 'repeater',
			'fields'     => $columns_repeater,
			'titleField' => '{{{ label }}}',
			'default'    => $static_columns_default,
			'required'   => [ 'inherit_global_columns', '!=', true ],
			'newItem'    => [
				'column_type' => '',
				'label'       => '',
			],
		];

		// Settings control group
		$this->controls['settings_heading'] = [
			'tab'   => 'content',
			'group' => 'settings',
			'label' => esc_html__( 'Settings', 'jet-wc-product-table' ),
			'type'  => 'heading',
		];

		$this->controls['direction'] = [
			'tab'         => 'content',
			'group'       => 'settings',
			'label'       => esc_html__( 'Direction', 'jet-wc-product-table' ),
			'type'        => 'select',
			'options'     => [
				'horizontal' => esc_html__( 'Horizontal', 'jet-wc-product-table' ),
				'vertical'   => esc_html__( 'Vertical', 'jet-wc-product-table' ),
			],
			'default'     => 'horizontal',
			'description' => esc_html__( 'Set the orientation of the table.', 'jet-wc-product-table' ),
		];

		$this->controls['show_header'] = [
			'tab'         => 'content',
			'group'       => 'settings',
			'label'       => esc_html__( 'Show Header', 'jet-wc-product-table' ),
			'type'        => 'checkbox',
			'default'     => true,
			'description' => esc_html__( 'Toggle table header visibility.', 'jet-wc-product-table' ),
		];

		$this->controls['sticky_header'] = [
			'tab'          => 'content',
			'group'        => 'settings',
			'label'        => esc_html__( 'Sticky Header', 'jet-wc-product-table' ),
			'type'        => 'checkbox',
			'default'     => false,
			'description'  => esc_html__( 'Keep the table header visible while scrolling.', 'jet-wc-product-table' ),
		];

		$this->controls['lazy_load'] = [
			'tab'          => 'content',
			'group'        => 'settings',
			'label'        => esc_html__( 'Lazy Load', 'jet-wc-product-table' ),
			'type'        => 'checkbox',
			'default'     => false,
			'description'  => esc_html__( 'Load table rows dynamically as you scroll.', 'jet-wc-product-table' ),
		];

		$this->controls['show_footer'] = [
			'tab'          => 'content',
			'group'        => 'settings',
			'label'        => esc_html__( 'Show Footer', 'jet-wc-product-table' ),
			'type'        => 'checkbox',
			'default'     => false,
			'description'  => esc_html__( 'Toggle the table footer visibility.', 'jet-wc-product-table' ),
		];

		$this->controls['mobile_layout'] = [
			'tab'         => 'content',
			'group'       => 'settings',
			'label'       => esc_html__( 'Mobile Layout', 'jet-wc-product-table' ),
			'type'        => 'select',
			'options'     => [
				'moveable'  => esc_html__( 'Moveable', 'jet-wc-product-table' ),
				'transform' => esc_html__( 'Transform', 'jet-wc-product-table' ),
				'collapsed' => esc_html__( 'Collapsed', 'jet-wc-product-table' ),
			],
			'default'     => 'moveable',
			'description' => esc_html__( 'Set the table layout for mobile devices.', 'jet-wc-product-table' ),
		];

		$this->controls['pager'] = [
			'tab'          => 'content',
			'group'        => 'settings',
			'label'        => esc_html__( 'Enable Pager', 'jet-wc-product-table' ),
			'type'        => 'checkbox',
			'default'     => false,
			'description'  => esc_html__( 'Enable pagination for the table.', 'jet-wc-product-table' ),
		];

		$this->controls['pager_position'] = [
			'tab'         => 'content',
			'group'       => 'settings',
			'label'       => esc_html__( 'Pager Position', 'jet-wc-product-table' ),
			'type'        => 'select',
			'options'     => [
				'before' => esc_html__( 'Before Table', 'jet-wc-product-table' ),
				'after'  => esc_html__( 'After Table', 'jet-wc-product-table' ),
				'both'   => esc_html__( 'Before & After Table', 'jet-wc-product-table' ),
			],
			'default'     => 'after',
			'description' => esc_html__( 'Set the position of the pager.', 'jet-wc-product-table' ),
			'required' => [ 'pager' ],
		];

		$this->controls['load_more'] = [
			'tab'          => 'content',
			'group'        => 'settings',
			'label'        => esc_html__( 'Enable Load More', 'jet-wc-product-table' ),
			'type'        => 'checkbox',
			'default'     => false,
			'description'  => esc_html__( 'Enable a "Load More" button to load additional rows.', 'jet-wc-product-table' ),
		];

		$this->controls['load_more_label'] = [
			'tab'         => 'content',
			'group'       => 'settings',
			'label'       => esc_html__( 'Load More Label', 'jet-wc-product-table' ),
			'type'        => 'text',
			'default'     => esc_html__( 'Load More', 'jet-wc-product-table' ),
			'description' => esc_html__( 'Text for the "Load More" button.', 'jet-wc-product-table' ),
			'required' => [ 'load_more' ],
		];

		// Filters group
		$this->controls['inherit_global_filters'] = [
			'tab'         => 'content',
			'group'       => 'filters',
			'label'       => esc_html__( 'Inherit global filters', 'jet-wc-product-table' ),
			'type'        => 'checkbox',
			'default'     => true,
			'description' => esc_html__( 'If enabled, the table will inherit global filters.', 'jet-wc-product-table' ),
		];

		$static_filter_default = [
			[
				'filter_type' => 'tax_query',
				'label'       => esc_html__( 'Product Categories', 'jet-wc-product-table' ),
				'query_var'   => 'product_cat',
				'placeholder' => esc_html__( 'Select Category...', 'jet-wc-product-table' ),
			],
		];

		$filters_repeater = [
			'filter_type' => [
				'label'   => esc_html__( 'Filter Type', 'jet-wc-product-table' ),
				'type'    => 'select',
				'options' => array_merge(
					[ '' => esc_html__( 'Select a filter type...', 'jet-wc-product-table' ) ],
					Woo_Glossary::instance()->get_filters_options()
				),
				'default' => '',
			],
		];

		$formatted_filter_controls = $this->get_formatted_filter_controls();

		foreach ( $formatted_filter_controls as $control ) {
			$filters_repeater[ $control['id'] ] = $control;
		}

		$this->controls['filters_manager'] = [
			'tab'        => 'content',
			'group'      => 'filters',
			'label'       => esc_html__( 'Filters Manager', 'jet-wc-product-table' ),
			'type'       => 'repeater',
			'fields'     => $filters_repeater,
			'titleField' => '{{{ filter_type }}}',
			'default'    => $static_filter_default,
			'required'   => [ 'inherit_global_filters', '!=', true ],
		];
	}

	/**
	 * Render the element's HTML
	 */
	public function render() {
		$settings = $this->settings;

		$attributes = [
			'preset'          => $this->prepare_preset( $settings ),
			'query'           => $this->prepare_query( $settings ),
			'settings'        => $this->prepare_settings( $settings ),
			'columns'         => $this->prepare_columns( $settings ),
			'filters'         => $this->prepare_filters( $settings ),
			'filters_enabled' => $this->is_filters_enabled( $settings ),
		];

		echo $this->render_callback( $attributes ); // phpcs:ignore
	}

	/**
	 * Prepare preset ID from settings.
	 *
	 * @param  array $settings Element settings.
	 *
	 * @return int|null Preset ID or null.
	 */
	protected function prepare_preset( $settings ) {
		return ! empty( $settings['preset'] ) ? absint( str_replace( 'id_', '', $settings['preset'] ) ) : null;
	}

	/**
	 * Prepare the query arguments.
	 *
	 * @param  array $settings Element settings.
	 *
	 * @return array Query arguments.
	 */
	protected function prepare_query( $settings ) {
		$query_type        = $settings['query_type'] ?? 'products';
		$include_array     = ! empty( $settings['product_include'] ) ? array_map( 'intval', explode( ',', $settings['product_include'] ) ) : [];
		$exclude_array     = ! empty( $settings['product_exclude'] ) ? array_map( 'intval', explode( ',', $settings['product_exclude'] ) ) : [];
		$tag_id_array      = ! empty( $settings['product_tag_id'] ) ? array_map( 'intval', explode( ',', $settings['product_tag_id'] ) ) : [];
		$category_array    = ! empty( $settings['product_category'] ) ? explode( ',', $settings['product_category'] ) : [];
		$category_id_array = ! empty( $settings['product_category_id'] ) ? array_map( 'intval', explode( ',', $settings['product_category_id'] ) ) : [];

		if ( 'products' === $query_type ) {
			unset( $settings['product_ids'], $settings['product_skus'] );
		}

		if ( ! empty( $settings['product_ids'] ) ) {
			$product_ids   = array_map( 'intval', explode( ',', $settings['product_ids'] ) );
			$include_array = array_merge( $include_array, $product_ids );
		}

		if ( ! empty( $settings['product_skus'] ) ) {
			$sku_ids = [];
			$skus    = array_map( 'trim', explode( ',', $settings['product_skus'] ) );
			foreach ( $skus as $sku ) {
				$product_id = wc_get_product_id_by_sku( $sku );
				if ( $product_id ) {
					$sku_ids[] = $product_id;
				}
			}
			$include_array = array_merge( $include_array, $sku_ids );
		}

		$query_args = [
			'include' => $include_array,
			'exclude' => $exclude_array,
		];
		if ( 'products' === $query_type ) {
			$query_args_products = [
				'status'            => ! empty( $settings['product_status'] ) ? $settings['product_status'] : '',
				'limit'             => ! empty( $settings['product_limit'] ) ? $settings['product_limit'] : '',
				'page'              => ! empty( $settings['product_page'] ) ? $settings['product_page'] : 1,
				'offset'            => ! empty( $settings['product_offset'] ) ? $settings['product_offset'] : 0,
				'type'              => ! empty( $settings['product_type'] ) ? $settings['product_type'] : '',
				'orderby'           => ! empty( $settings['product_orderby'] ) ? $settings['product_orderby'] : '',
				'order'             => ! empty( $settings['product_order'] ) ? $settings['product_order'] : '',
				'sku'               => ! empty( $settings['product_sku'] ) ? $settings['product_sku'] : '',
				'name'              => ! empty( $settings['product_name'] ) ? $settings['product_name'] : '',
				'tag'               => ! empty( $settings['product_tag'] ) ? $settings['product_tag'] : '',
				'category'          => ! empty( $settings['product_category'] ) ? $settings['product_category'] : '',
				'weight'            => ! empty( $settings['product_weight'] ) ? $settings['product_weight'] : '',
				'width'             => ! empty( $settings['product_width'] ) ? $settings['product_width'] : '',
				'length'            => ! empty( $settings['product_length'] ) ? $settings['product_length'] : '',
				'height'            => ! empty( $settings['product_height'] ) ? $settings['product_height'] : '',
				'price'             => ! empty( $settings['product_price'] ) ? $settings['product_price'] : '',
				'regular_price'     => ! empty( $settings['product_regular_price'] ) ? $settings['product_regular_price'] : '',
				'sale_price'        => ! empty( $settings['product_sale_price'] ) ? $settings['product_sale_price'] : '',
				'total_sales'       => ! empty( $settings['product_total_sales'] ) ? $settings['product_total_sales'] : '',
				'virtual'           => ! empty( $settings['product_virtual'] ) ? $settings['product_virtual'] : '',
				'featured'          => ! empty( $settings['product_featured'] ) ? $settings['product_featured'] : '',
				'downloadable'      => ! empty( $settings['product_downloadable'] ) ? $settings['product_downloadable'] : '',
				'manage_stock'      => ! empty( $settings['product_manage_stock'] ) ? $settings['product_manage_stock'] : '',
				'sold_individually' => ! empty( $settings['product_sold_individually'] ) ? $settings['product_sold_individually'] : '',
				'reviews_allowed'   => ! empty( $settings['product_reviews_allowed'] ) ? $settings['product_reviews_allowed'] : '',
				'backorders'        => ! empty( $settings['product_backorder'] ) ? $settings['product_backorder'] : '',
				'visibility'        => ! empty( $settings['product_visibility'] ) ? $settings['product_visibility'] : '',
				'stock_quantity'    => ! empty( $settings['product_stock_quantity'] ) ? $settings['product_stock_quantity'] : '',
				'stock_status'      => ! empty( $settings['product_stock_status'] ) ? $settings['product_stock_status'] : '',
				'shipping_class'    => ! empty( $settings['product_shipping_class'] ) ? $settings['product_shipping_class'] : '',
				'tax_status'        => ! empty( $settings['product_tax_status'] ) ? $settings['product_tax_status'] : '',
				'tax_class'         => ! empty( $settings['product_tax_class'] ) ? $settings['product_tax_class'] : '',
				'average_rating'    => ! empty( $settings['product_average_rating'] ) ? $settings['product_average_rating'] : '',
				'download_expiry'   => ! empty( $settings['product_download_expiry'] ) ? $settings['product_download_expiry'] : '',
				'download_limit'    => ! empty( $settings['product_download_limit'] ) ? $settings['product_download_limit'] : '',
				'review_count'      => ! empty( $settings['product_review_count'] ) ? $settings['product_review_count'] : '',
				'date_created'      => ! empty( $settings['product_date_created'] ) ? $settings['product_date_created'] : '',
				'date_modified'     => ! empty( $settings['product_date_modified'] ) ? $settings['product_date_modified'] : '',
				'date_on_sale_from' => ! empty( $settings['product_date_on_sale_from'] ) ? $settings['product_date_on_sale_from'] : '',
				'date_on_sale_to'   => ! empty( $settings['product_date_on_sale_to'] ) ? $settings['product_date_on_sale_to'] : '',
			];

			$query_args = array_merge( $query_args, $query_args_products );
		}

		if ( 'variations' === $query_type ) {
			$query_args['query_type']     = 'variations';
			$query_args['variation_type'] = $settings['variation_type'] ?? 'current_product';
			$query_args['product_ids']    = $settings['product_ids'] ?? '';
			$query_args['product_skus']   = $settings['product_skus'] ?? '';

			if ( 'current_product' === $query_args['variation_type'] ) {
				global $post;

				if ( $post && 'product' === $post->post_type ) {
					$product = wc_get_product( $post->ID );

					if ( $product && $product->is_type( 'variable' ) ) {
						$query_args['parent_product_id'] = $product->get_id();
					}
				}
			}
		}

		if ( ! empty( $tag_id_array ) ) {
			$query_args['tax_query'][] = [
				'taxonomy' => 'product_tag',
				'field'    => 'term_id',
				'terms'    => $tag_id_array,
			];
		}

		if ( ! empty( $category_array ) || ! empty( $category_id_array ) ) {
			$query_args['tax_query'][] = [
				'taxonomy' => 'product_cat',
				'field'    => ! empty( $category_id_array ) ? 'term_id' : 'name',
				'terms'    => ! empty( $category_id_array ) ? $category_id_array : $category_array,
			];
		}

		return $query_args;
	}

	/**
	 * Prepare settings for rendering the table
	 *
	 * @param array $settings Element settings.
	 *
	 * @return array Prepared settings.
	 */
	protected function prepare_settings( $settings ) {
		$prepared = [
			'direction'       => $settings['direction'] ?? 'horizontal',
			'show_header'     => ! empty( $settings['show_header'] ),
			'sticky_header'   => ! empty( $settings['sticky_header'] ),
			'lazy_load'       => ! empty( $settings['lazy_load'] ),
			'show_footer'     => ! empty( $settings['show_footer'] ),
			'mobile_layout'   => $settings['mobile_layout'] ?? 'moveable',
			'pager'           => ! empty( $settings['pager'] ),
			'pager_position'  => $settings['pager_position'] ?? 'after',
			'load_more'       => ! empty( $settings['load_more'] ),
			'load_more_label' => $settings['load_more_label'] ?? esc_html__( 'Load More', 'jet-wc-product-table' ),
		];

		ksort( $prepared );

		return $prepared;
	}

	/**
	 * Prepare columns data for the table.
	 *
	 * @param  array $settings Element settings.
	 * @return array Processed columns data.
	 */
	protected function prepare_columns( $settings ) {
		if ( ! empty( $settings['inherit_global_columns'] ) ) {
			return Plugin::instance()->settings->get( 'columns', [] );
		}

		if ( empty( $settings['columns_manager'] ) || ! is_array( $settings['columns_manager'] ) ) {
			return [];
		}

		$columns          = $settings['columns_manager'];
		$prepared_columns = [];

		foreach ( $columns as $column_data ) {
			$column_id  = $column_data['column_type'];
			$column_obj = Plugin::instance()->columns_controller->get_column( $column_id );

			if ( $column_obj ) {
				foreach ( $column_data as $key => $value ) {
					if ( strpos( $key, "{$column_id}_" ) === 0 ) {
						$new_key                 = str_replace( "{$column_id}_", '', $key );
						$column_data[ $new_key ] = $value;
						unset( $column_data[ $key ] );
					}
				}

				if ( isset( $column_data['linked'] ) ) {
					$column_data['linked'] = filter_var( $column_data['linked'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );
				} else {
					$column_data['linked'] = false;
				}

				$sanitized_data       = $column_obj->sanitize_column_data( $column_data );
				$sanitized_data['id'] = $column_id;

				$prepared_columns[] = $sanitized_data;
			}
		}

		return $prepared_columns;
	}

	/**
	 * Retrieve formatted column-specific controls for Bricks.
	 *
	 * @return array An array of formatted controls for each column type.
	 */
	protected function get_formatted_column_controls() {
		$columns_js = Plugin::instance()->columns_controller->get_columns_for_js();
		$controls   = [];

		foreach ( $columns_js as $column_data ) {
			$column_id       = $column_data['value'];
			$column_instance = Plugin::instance()->columns_controller->get_column( $column_id );

			if ( ! $column_instance ) {
				continue;
			}

			$column_settings = $column_instance->get_merged_additional_settings();

			foreach ( $column_settings as $setting_id => $setting_data ) {
				if ( empty( $setting_data['type'] ) ) {
					continue;
				}

				$control_id = "{$column_id}_{$setting_id}";

				$controls[ $control_id ] = [
					'label'       => $setting_data['label'] ?? '',
					'type'        => ( 'toggle' === $setting_data['type'] ? 'checkbox' : $setting_data['type'] ),
					'default'     => $setting_data['default'] ?? '',
					'description' => $setting_data['description'] ?? '',
					'id'          => $control_id,
					'required'    => [
						'column_type',
						'=',
						$column_id,
					],
				];

				if ( isset( $setting_data['options'] ) && is_array( $setting_data['options'] ) ) {
					$formatted_options = [];
					foreach ( $setting_data['options'] as $option ) {
						if ( isset( $option['value'], $option['label'] ) ) {
							$formatted_options[ $option['value'] ] = $option['label'];
						}
					}
					$controls[ $control_id ]['options'] = $formatted_options;
				}
			}
		}

		return $controls;
	}

	/**
	 * Prepare filters data for the table.
	 *
	 * @param  array $settings Element settings.
	 * @return array Filters data.
	 */
	protected function prepare_filters( $settings ) {
		if ( ! empty( $settings['inherit_global_filters'] ) ) {
			// Get global filters
			$global_filters  = Plugin::instance()->settings->get( 'filters', [] );
			$filters_enabled = Plugin::instance()->settings->get( 'filters_enabled', false );

			if ( ! $filters_enabled ) {
				return [];
			}

			return $global_filters;
		}

		$filters = isset( $settings['filters_manager'] ) && is_array( $settings['filters_manager'] )
			? $settings['filters_manager']
			: [];

		$prepared_filters = [];

		foreach ( $filters as $filter_data ) {
			$filter_id  = $filter_data['filter_type'] ?? '';
			$filter_obj = Plugin::instance()->filters_controller->get_filter_type( $filter_id );

			if ( $filter_obj ) {
				$filter_query_attr_name   = 'query_var';
				$filter_query_custom_name = "{$filter_id}_{$filter_query_attr_name}";

				if ( ! empty( $filter_data[ $filter_query_custom_name ] ) ) {
					$filter_data[ $filter_query_attr_name ] = $filter_data[ $filter_query_custom_name ];
				}

				$filter_data['id'] = ! empty( $filter_id ) ? $filter_id : uniqid( 'filter_' );

				if ( empty( $filter_data['label'] ) ) {
					$filter_data['label'] = ucfirst( str_replace( '_', ' ', $filter_id ) );
				}

				if ( isset( $filter_data['show_search_button'] ) ) {
					$filter_data['show_search_button'] = filter_var( $filter_data['show_search_button'], FILTER_VALIDATE_BOOLEAN );

					if ( ! $filter_data['show_search_button'] ) {
						$filter_data['search_button_label'] = '';
					}
				} else {
					$filter_data['show_search_button']  = false;
					$filter_data['search_button_label'] = '';
				}

				$prepared_filters[] = $filter_obj->sanitize_data( $filter_data );
			}
		}

		return $prepared_filters;
	}

	/**
	 * Retrieve formatted filter-specific controls for Bricks.
	 *
	 * @return array An array of formatted controls for each filter, suitable for use in Bricks.
	 */
	protected function get_formatted_filter_controls() {
		$filters_js = Plugin::instance()->filters_controller->get_filter_types_for_js();
		$controls   = [];

		foreach ( $filters_js as $filter_data ) {
			$filter_id       = $filter_data['value'];
			$filter_instance = Plugin::instance()->filters_controller->get_filter_type( $filter_id );

			if ( ! $filter_instance ) {
				continue;
			}

			$filter_settings = $filter_instance->get_merged_additional_settings();
			foreach ( $filter_settings as $setting_id => $setting_data ) {
				$setting_name = 'query_var' === $setting_id ? "{$filter_id}_{$setting_id}" : $setting_id;

				if ( ! isset( $controls[ $setting_name ] ) ) {
					$control_label       = $setting_data['label'] ?? '';
					$control_type        = $setting_data['type'] ?? '';
					$control_default     = $setting_data['default'] ?? '';
					$control_description = $setting_data['description'] ?? '';

					if ( 'show_search_button' === $setting_id ) {
						$control_default = false;
					}

					if ( ! $control_type ) {
						continue;
					}

					switch ( $control_type ) {
						case 'toggle':
							$control_type = 'checkbox';
							break;
						case 'search':
							$control_type = 'text';
							break;
					}

					$controls[ $setting_name ] = [
						'label'       => $control_label,
						'type'        => $control_type,
						'default'     => $control_default,
						'description' => $control_description,
						'id'          => $setting_name,
						'required'    => [
							'filter_type',
							'=',
							$filter_id,
						],
					];

					if ( 'search_button_label' === $setting_id ) {
						$controls[ $setting_name ]['required'] = [ 'show_search_button' ];
					}

					if ( isset( $setting_data['options'] ) && is_array( $setting_data['options'] ) ) {
						$formatted_options = [];
						foreach ( $setting_data['options'] as $option ) {
							if ( isset( $option['value'], $option['label'] ) ) {
								$formatted_options[ $option['value'] ] = $option['label'];
							}
						}
						$controls[ $setting_name ]['options'] = $formatted_options;
					}
				}
			}
		}

		return $controls;
	}

	/**
	 * Determine if filters are enabled.
	 *
	 * @param  array $settings Element settings.
	 * @return bool True if filters are enabled, false otherwise.
	 */
	protected function is_filters_enabled( $settings ) {
		return ! empty( $settings['filters_manager'] ) || ! empty( $settings['inherit_global_filters'] );
	}
}
