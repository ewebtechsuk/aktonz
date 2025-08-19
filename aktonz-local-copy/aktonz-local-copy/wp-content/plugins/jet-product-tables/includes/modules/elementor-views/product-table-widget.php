<?php

namespace Jet_WC_Product_Table\Modules\Elementor_Views;

use Jet_WC_Product_Table\Plugin;
use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Jet_WC_Product_Table\Traits\Table_Render_By_Attributes;
use Elementor\Repeater;
use Jet_WC_Product_Table\Helpers\Woo_Glossary;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Product_Table_Widget extends Widget_Base {

	use Table_Render_By_Attributes;


	/**
	 * Get widget name.
	 */
	public function get_name() {
		return 'jet-wc-product-table';
	}

	/**
	 * Get widget title.
	 */
	public function get_title() {
		return __( 'Product Table', 'jet-wc-product-table' );
	}

	/**
	 * Get widget icon.
	 */
	public function get_icon() {
		return 'jet-product-table-main';
	}

	/**
	 * Enqueue widget styles.
	 */
	public function get_style_depends() {
		return [
			'jet-product-tables-table',
			'jet-product-tables-icons',
		];
	}

	/**
	 * Get widget categories.
	 */
	public function get_categories() {
		return [ 'general' ];
	}

	/**
	 * Register widget controls.
	 */
	protected function register_controls() {
		// Preset section
		$this->start_controls_section(
			'section_preset',
			[
				'label' => __( 'Preset', 'jet-wc-product-table' ),
			]
		);

		$this->add_control(
			'preset',
			[
				'label'       => __( 'Use Preset', 'jet-wc-product-table' ),
				'type'        => Controls_Manager::SELECT,
				'options'     => array_merge(
					[ '' => __( 'Select preset...', 'jet-wc-product-table' ) ],
					Woo_Glossary::instance()->get_presets_options()
				),
				'default'     => '',
				'description' => __( 'This preset will be applied for Columns, Settings, and Filters. If preset is selected, columns will be inherited from the preset.', 'jet-wc-product-table' ),
			]
		);

		$this->end_controls_section();

		// Query section
		$this->start_controls_section(
			'section_query',
			[
				'label' => __( 'Query', 'jet-wc-product-table' ),
			]
		);

		$this->add_control(
			'query_type',
			[
				'label'        => __( 'Query Type', 'jet-wc-product-table' ),
				'type'         => Controls_Manager::SELECT,
				'default'      => 'products',
				'options'      => [
					'products'   => __( 'Products', 'jet-wc-product-table' ),
					'variations' => __( 'Product Variations', 'jet-wc-product-table' ),
				],
			]
		);

		$this->add_control(
			'general_divider_before',
			[
				'type'      => \Elementor\Controls_Manager::DIVIDER,
				'condition' => [
					'query_type' => 'products',
				],
			]
		);

		$this->add_control(
			'general_heading',
			[
				'label'     => __( 'General', 'jet-wc-product-table' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'condition' => [
					'query_type' => 'products',
				],
			]
		);

		$this->add_control(
			'general_divider_after',
			[
				'type'      => \Elementor\Controls_Manager::DIVIDER,
				'condition' => [
					'query_type' => 'products',
				],
			]
		);

		$this->add_control(
			'product_status',
			[
				'label'     => __( 'Product Status', 'jet-wc-product-table' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'default'   => '',
				'options'   => array_merge(
					[ '' => __( 'Select a status...', 'jet-wc-product-table' ) ],
					Woo_Glossary::instance()->get_post_statuses()
				),
				'condition' => [
					'query_type' => 'products',
				],
			]
		);

		$this->add_control(
			'product_limit',
			[
				'label'     => __( 'Products Limit', 'jet-wc-product-table' ),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'default'   => 10,
				'min'       => 1,
				'step'      => 1,
				'condition' => [
					'query_type' => 'products',
				],
			]
		);

		$this->add_control(
			'product_page',
			[
				'label'       => __( 'Page', 'jet-wc-product-table' ),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'min'         => 1,
				'description' => __( 'Page of results to retrieve', 'jet-wc-product-table' ),
				'condition'   => [
					'query_type' => 'products',
				],
			]
		);

		$this->add_control(
			'product_offset',
			[
				'label'       => __( 'Offset', 'jet-wc-product-table' ),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'min'         => 0,
				'description' => __( 'Amount to offset product results. Note: offset overrides the paged parameter and can break pagination and load more.', 'jet-wc-product-table' ),
				'condition'   => [
					'query_type' => 'products',
				],
			]
		);

		$this->add_control(
			'product_include',
			[
				'label'       => __( 'Include Products', 'jet-wc-product-table' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => '',
				'description' => __( 'Comma-separated list of product IDs to include', 'jet-wc-product-table' ),
				'condition'   => [
					'query_type' => 'products',
				],
			]
		);

		$this->add_control(
			'product_exclude',
			[
				'label'       => __( 'Exclude Products', 'jet-wc-product-table' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => '',
				'description' => __( 'Comma-separated list of product IDs to exclude', 'jet-wc-product-table' ),
				'condition'   => [
					'query_type' => 'products',
				],
			]
		);

		$this->add_control(
			'product_type',
			[
				'label'       => __( 'Product Type', 'jet-wc-product-table' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'default'     => '',
				'options'     => array_merge(
					[ '' => __( 'Select a type...', 'jet-wc-product-table' ) ],
					Woo_Glossary::instance()->get_product_types()
				),
				'description' => __( 'Filter products by type', 'jet-wc-product-table' ),
				'condition'   => [
					'query_type' => 'products',
				],
			]
		);

		$this->add_control(
			'product_orderby',
			[
				'label'       => __( 'Order By', 'jet-wc-product-table' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'default'     => 'date',
				'options'     => array_merge(
					[ '' => __( 'Select an order by...', 'jet-wc-product-table' ) ],
					Woo_Glossary::instance()->get_order_by_options()
				),
				'description' => __( 'Field to sort by', 'jet-wc-product-table' ),
				'condition'   => [
					'query_type' => 'products',
				],
			]
		);

		$this->add_control(
			'product_order',
			[
				'label'       => __( 'Order', 'jet-wc-product-table' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'default'     => 'DESC',
				'options'     => array_merge(
					[ '' => __( 'Select an order...', 'jet-wc-product-table' ) ],
					Woo_Glossary::instance()->get_order_options()
				),
				'description' => __( 'Sort order: ASC or DESC', 'jet-wc-product-table' ),
				'condition'   => [
					'query_type' => 'products',
				],
			]
		);

		$this->add_control(
			'product_divider_before',
			[
				'type'      => \Elementor\Controls_Manager::DIVIDER,
				'condition' => [
					'query_type' => 'products',
				],
			]
		);

		$this->add_control(
			'product_heading',
			[
				'label'     => __( 'Product', 'jet-wc-product-table' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'condition' => [
					'query_type' => 'products',
				],
			]
		);

		$this->add_control(
			'product_divider_after',
			[
				'type'      => \Elementor\Controls_Manager::DIVIDER,
				'condition' => [
					'query_type' => 'products',
				],
			]
		);

		$this->add_control(
			'product_sku',
			[
				'label'       => __( 'SKU', 'jet-wc-product-table' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => '',
				'description' => __( 'Get product with given SKU', 'jet-wc-product-table' ),
				'condition'   => [
					'query_type' => 'products',
				],
			]
		);

		$this->add_control(
			'product_name',
			[
				'label'       => __( 'Name', 'jet-wc-product-table' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => '',
				'description' => __( 'Get product with given name', 'jet-wc-product-table' ),
				'condition'   => [
					'query_type' => 'products',
				],
			]
		);

		$this->add_control(
			'product_tag',
			[
				'label'       => __( 'Tag', 'jet-wc-product-table' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => '',
				'description' => __( 'Comma-separated list of tags', 'jet-wc-product-table' ),
				'condition'   => [
					'query_type' => 'products',
				],
			]
		);

		$this->add_control(
			'product_tag_id',
			[
				'label'       => __( 'Tag ID', 'jet-wc-product-table' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => '',
				'description' => __( 'Comma-separated list of tag IDs', 'jet-wc-product-table' ),
				'condition'   => [
					'query_type' => 'products',
				],
			]
		);

		$this->add_control(
			'product_category',
			[
				'label'       => __( 'Category', 'jet-wc-product-table' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => '',
				'description' => __( 'Comma-separated list of categories', 'jet-wc-product-table' ),
				'condition'   => [
					'query_type' => 'products',
				],
			]
		);

		$this->add_control(
			'product_category_id',
			[
				'label'       => __( 'Category ID', 'jet-wc-product-table' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => '',
				'description' => __( 'Comma-separated list of category IDs', 'jet-wc-product-table' ),
				'condition'   => [
					'query_type' => 'products',
				],
			]
		);

		$this->add_control(
			'product_weight',
			[
				'label'       => __( 'Weight', 'jet-wc-product-table' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => '',
				'description' => __( 'Product weight', 'jet-wc-product-table' ),
				'condition'   => [
					'query_type' => 'products',
				],
			]
		);

		$this->add_control(
			'product_width',
			[
				'label'       => __( 'Width', 'jet-wc-product-table' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => '',
				'description' => __( 'Product width', 'jet-wc-product-table' ),
				'condition'   => [
					'query_type' => 'products',
				],
			]
		);

		$this->add_control(
			'product_length',
			[
				'label'       => __( 'Length', 'jet-wc-product-table' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => '',
				'description' => __( 'Product length', 'jet-wc-product-table' ),
				'condition'   => [
					'query_type' => 'products',
				],
			]
		);

		$this->add_control(
			'product_height',
			[
				'label'       => __( 'Height', 'jet-wc-product-table' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => '',
				'description' => __( 'Product height', 'jet-wc-product-table' ),
				'condition'   => [
					'query_type' => 'products',
				],
			]
		);

		$this->add_control(
			'product_price',
			[
				'label'       => __( 'Price', 'jet-wc-product-table' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => '',
				'description' => __( 'Product price', 'jet-wc-product-table' ),
				'condition'   => [
					'query_type' => 'products',
				],
			]
		);

		$this->add_control(
			'product_regular_price',
			[
				'label'       => __( 'Regular Price', 'jet-wc-product-table' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => '',
				'description' => __( 'Regular price', 'jet-wc-product-table' ),
				'condition'   => [
					'query_type' => 'products',
				],
			]
		);

		$this->add_control(
			'product_sale_price',
			[
				'label'       => __( 'Sale Price', 'jet-wc-product-table' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => '',
				'description' => __( 'Sale price', 'jet-wc-product-table' ),
				'condition'   => [
					'query_type' => 'products',
				],
			]
		);

		$this->add_control(
			'product_total_sales',
			[
				'label'       => __( 'Total Sales', 'jet-wc-product-table' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => '',
				'description' => __( 'Total sales', 'jet-wc-product-table' ),
				'condition'   => [
					'query_type' => 'products',
				],
			]
		);

		$this->add_control(
			'product_virtual',
			[
				'label'       => __( 'Virtual', 'jet-wc-product-table' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'default'     => '',
				'options'     => [
					''    => __( 'Select an option...', 'jet-wc-product-table' ),
					'yes' => __( 'Yes', 'jet-wc-product-table' ),
					'no'  => __( 'No', 'jet-wc-product-table' ),
				],
				'description' => __( 'Is the product virtual?', 'jet-wc-product-table' ),
				'condition'   => [
					'query_type' => 'products',
				],
			]
		);

		$this->add_control(
			'product_featured',
			[
				'label'       => __( 'Featured', 'jet-wc-product-table' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'default'     => '',
				'options'     => [
					''    => __( 'Select an option...', 'jet-wc-product-table' ),
					'yes' => __( 'Yes', 'jet-wc-product-table' ),
					'no'  => __( 'No', 'jet-wc-product-table' ),
				],
				'description' => __( 'Is the product featured?', 'jet-wc-product-table' ),
				'condition'   => [
					'query_type' => 'products',
				],
			]
		);

		$this->add_control(
			'product_downloadable',
			[
				'label'       => __( 'Downloadable', 'jet-wc-product-table' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'default'     => '',
				'options'     => [
					''    => __( 'Select an option...', 'jet-wc-product-table' ),
					'yes' => __( 'Yes', 'jet-wc-product-table' ),
					'no'  => __( 'No', 'jet-wc-product-table' ),
				],
				'description' => __( 'Is the product downloadable?', 'jet-wc-product-table' ),
				'condition'   => [
					'query_type' => 'products',
				],
			]
		);

		$this->add_control(
			'product_manage_stock',
			[
				'label'       => __( 'Manage Stock', 'jet-wc-product-table' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'default'     => '',
				'options'     => [
					''    => __( 'Select an option...', 'jet-wc-product-table' ),
					'yes' => __( 'Yes', 'jet-wc-product-table' ),
					'no'  => __( 'No', 'jet-wc-product-table' ),
				],
				'description' => __( 'Is stock managed?', 'jet-wc-product-table' ),
				'condition'   => [
					'query_type' => 'products',
				],
			]
		);

		$this->add_control(
			'product_sold_individually',
			[
				'label'       => __( 'Sold Individually', 'jet-wc-product-table' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'default'     => '',
				'options'     => [
					''    => __( 'Select an option...', 'jet-wc-product-table' ),
					'yes' => __( 'Yes', 'jet-wc-product-table' ),
					'no'  => __( 'No', 'jet-wc-product-table' ),
				],
				'description' => __( 'Is the product sold individually?', 'jet-wc-product-table' ),
				'condition'   => [
					'query_type' => 'products',
				],
			]
		);

		$this->add_control(
			'product_reviews_allowed',
			[
				'label'       => __( 'Reviews Allowed', 'jet-wc-product-table' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'default'     => '',
				'options'     => [
					''    => __( 'Select an option...', 'jet-wc-product-table' ),
					'yes' => __( 'Yes', 'jet-wc-product-table' ),
					'no'  => __( 'No', 'jet-wc-product-table' ),
				],
				'description' => __( 'Choose to show products where reviews are allowed or not.', 'jet-wc-product-table' ),
				'condition'   => [
					'query_type' => 'products',
				],
			]
		);

		$this->add_control(
			'product_backorder',
			[
				'label'       => __( 'Backorder', 'jet-wc-product-table' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'default'     => '',
				'options'     => array_merge(
					[ '' => __( 'Select an backorder...', 'jet-wc-product-table' ) ],
					Woo_Glossary::instance()->get_backorder_options()
				),
				'description' => __( 'Backorder status', 'jet-wc-product-table' ),
				'condition'   => [
					'query_type' => 'products',
				],
			]
		);

		$this->add_control(
			'product_visibility',
			[
				'label'       => __( 'Visibility', 'jet-wc-product-table' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'default'     => '',
				'options'     => array_merge(
					[ '' => __( 'Select an visibility...', 'jet-wc-product-table' ) ],
					Woo_Glossary::instance()->get_visibility_options()
				),
				'description' => __( 'Product visibility', 'jet-wc-product-table' ),
				'condition'   => [
					'query_type' => 'products',
				],
			]
		);

		$this->add_control(
			'product_stock_quantity',
			[
				'label'       => __( 'Stock Quantity', 'jet-wc-product-table' ),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'default'     => '',
				'description' => __( 'Specify the stock quantity for the product', 'jet-wc-product-table' ),
				'condition'   => [
					'query_type' => 'products',
				],
			]
		);

		$this->add_control(
			'product_stock_status',
			[
				'label'       => __( 'Stock Status', 'jet-wc-product-table' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'default'     => '',
				'options'     => array_merge(
					[ '' => __( 'Select stock status...', 'jet-wc-product-table' ) ],
					Woo_Glossary::instance()->get_stock_status_options()
				),
				'description' => __( 'Stock Status', 'jet-wc-product-table' ),
				'condition'   => [
					'query_type' => 'products',
				],
			]
		);

		$this->add_control(
			'product_shipping_class',
			[
				'label'       => __( 'Shipping Class', 'jet-wc-product-table' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => '',
				'description' => __( 'Specify the shipping class slugs (comma-separated)', 'jet-wc-product-table' ),
				'condition'   => [
					'query_type' => 'products',
				],
			]
		);

		$this->add_control(
			'product_tax_status',
			[
				'label'       => __( 'Tax Status', 'jet-wc-product-table' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'default'     => '',
				'options'     => array_merge(
					[ '' => __( 'Select tax status...', 'jet-wc-product-table' ) ],
					Woo_Glossary::instance()->get_tax_status_options()
				),
				'description' => __( 'STax Status', 'jet-wc-product-table' ),
				'condition'   => [
					'query_type' => 'products',
				],
			]
		);

		$this->add_control(
			'product_tax_class',
			[
				'label'       => __( 'Tax Class', 'jet-wc-product-table' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => '',
				'description' => __( 'Specify the tax class slug for the product', 'jet-wc-product-table' ),
				'condition'   => [
					'query_type' => 'products',
				],
			]
		);

		$this->add_control(
			'product_average_rating',
			[
				'label'       => __( 'Average Rating', 'jet-wc-product-table' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => '',
				'description' => __( 'Specify the average rating for the product', 'jet-wc-product-table' ),
				'condition'   => [
					'query_type' => 'products',
				],
			]
		);

		$this->add_control(
			'product_download_expiry',
			[
				'label'       => __( 'Download Expiry', 'jet-wc-product-table' ),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'default'     => '',
				'description' => __( 'Set download expiry for the product (in days)', 'jet-wc-product-table' ),
				'condition'   => [
					'query_type' => 'products',
				],
			]
		);

		$this->add_control(
			'product_download_limit',
			[
				'label'       => __( 'Download Limit', 'jet-wc-product-table' ),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'default'     => '',
				'description' => __( 'Set the download limit for the product', 'jet-wc-product-table' ),
				'condition'   => [
					'query_type' => 'products',
				],
			]
		);

		$this->add_control(
			'product_review_count',
			[
				'label'       => __( 'Review Count', 'jet-wc-product-table' ),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'default'     => '',
				'description' => __( 'Specify the review count for the product', 'jet-wc-product-table' ),
				'condition'   => [
					'query_type' => 'products',
				],
			]
		);

		$this->add_control(
			'date_divider_before',
			[
				'type'      => \Elementor\Controls_Manager::DIVIDER,
				'condition' => [
					'query_type' => 'products',
				],
			]
		);

		$this->add_control(
			'date_heading',
			[
				'label'     => __( 'Date', 'jet-wc-product-table' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'condition' => [
					'query_type' => 'products',
				],
			]
		);

		$this->add_control(
			'date_divider_after',
			[
				'type'      => \Elementor\Controls_Manager::DIVIDER,
				'condition' => [
					'query_type' => 'products',
				],
			]
		);

		$description_text = __( 'Accepted values format for date options: YYYY-MM-DD, >=YYYY-MM-DD, >YYYY-MM-DD, <=YYYY-MM-DD, <YYYY-MM-DD', 'jet-wc-product-table' );

		$this->add_control(
			'product_date_created',
			[
				'label'       => __( 'Date Created', 'jet-wc-product-table' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => '',
				'description' => htmlspecialchars( $description_text ),
				'condition'   => [
					'query_type' => 'products',
				],
			]
		);

		$this->add_control(
			'product_date_modified',
			[
				'label'       => __( 'Date Modified', 'jet-wc-product-table' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => '',
				'description' => htmlspecialchars( $description_text ),
				'condition'   => [
					'query_type' => 'products',
				],
			]
		);

		$this->add_control(
			'product_date_on_sale_from',
			[
				'label'       => __( 'Date On Sale From', 'jet-wc-product-table' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => '',
				'description' => htmlspecialchars( $description_text ),
				'condition'   => [
					'query_type' => 'products',
				],
			]
		);

		$this->add_control(
			'product_date_on_sale_to',
			[
				'label'       => __( 'Date On Sale To', 'jet-wc-product-table' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => '',
				'description' => htmlspecialchars( $description_text ),
				'condition'   => [
					'query_type' => 'products',
				],
			]
		);

		$this->add_control(
			'variation_type',
			[
				'label'     => __( 'Get variations from', 'jet-wc-product-table' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'current_product',
				'options'   => [
					'current_product'       => __( 'Current product', 'jet-wc-product-table' ),
					'specific_product_ids'  => __( 'Specific product ID(s)', 'jet-wc-product-table' ),
					'specific_product_skus' => __( 'Specific product SKU(s)', 'jet-wc-product-table' ),
				],
				'condition' => [
					'query_type' => 'variations',
				],
			]
		);

		$this->add_control(
			'product_ids',
			[
				'label'       => __( 'Product ID(s)', 'jet-wc-product-table' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => __( 'Enter product ID(s)', 'jet-wc-product-table' ),
				'condition'   => [
					'query_type'     => 'variations',
					'variation_type' => 'specific_product_ids',
				],
			]
		);

		$this->add_control(
			'product_skus',
			[
				'label'       => __( 'Product SKU(s)', 'jet-wc-product-table' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => __( 'Enter product SKU(s)', 'jet-wc-product-table' ),
				'condition'   => [
					'query_type'     => 'variations',
					'variation_type' => 'specific_product_skus',
				],
			]
		);

		$this->end_controls_section();

		// Columns section
		$this->start_controls_section(
			'section_columns',
			[
				'label' => __( 'Columns', 'jet-wc-product-table' ),
			]
		);

		$this->add_control(
			'inherit_global_columns',
			[
				'label'        => __( 'Inherit global columns', 'jet-wc-product-table' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'jet-wc-product-table' ),
				'label_off'    => __( 'No', 'jet-wc-product-table' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$repeater_defaults = [
			[
				'column_type' => 'product-image',
				'label'       => __( 'Product Image', 'jet-wc-product-table' ),
			],
			[
				'column_type' => 'product-name',
				'label'       => __( 'Product Name', 'jet-wc-product-table' ),
			],
			[
				'column_type' => 'product-price',
				'label'       => __( 'Product Price', 'jet-wc-product-table' ),
			],
		];

		$repeater = new Repeater();

		$repeater->add_control(
			'column_type',
			[
				'label'   => __( 'Column Type', 'jet-wc-product-table' ),
				'type'    => Controls_Manager::SELECT,
				'options' => array_merge(
					[ '' => __( 'Select a column type...', 'jet-wc-product-table' ) ],
					Woo_Glossary::instance()->get_columns_options()
				),
				'default' => '',
			]
		);

		$repeater->add_control(
			'label',
			[
				'label'       => __( 'Label', 'jet-wc-product-table' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Column Label', 'jet-wc-product-table' ),
				'description' => __( 'Set the column label displayed in the table header.', 'jet-wc-product-table' ),
				'dynamic'     => [
					'active' => true,
				],
			]
		);

		$formatted_controls = $this->get_formatted_column_controls();

		foreach ( $formatted_controls as $control ) {
			$repeater->add_control(
				$control['id'],
				$control
			);
		}

		$this->add_control(
			'columns_manager',
			[
				'label'       => __( 'Columns Manager', 'jet-wc-product-table' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ label }}}',
				'default'     => $repeater_defaults,
				'condition'   => [
					'inherit_global_columns!' => 'yes',
				],
			]
		);

		$this->end_controls_section();

		// Settings section
		$this->start_controls_section(
			'section_settings',
			[
				'label' => __( 'Settings', 'jet-wc-product-table' ),
			]
		);

		$this->add_control(
			'direction',
			[
				'label'       => __( 'Direction', 'jet-wc-product-table' ),
				'type'        => Controls_Manager::SELECT,
				'options'     => [
					'horizontal' => __( 'Horizontal', 'jet-wc-product-table' ),
					'vertical'   => __( 'Vertical', 'jet-wc-product-table' ),
				],
				'default'     => 'horizontal',
				'description' => __( 'Set the orientation of the table.', 'jet-wc-product-table' ),
			]
		);

		$this->add_control(
			'show_header',
			[
				'label'        => __( 'Show Header', 'jet-wc-product-table' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Show', 'jet-wc-product-table' ),
				'label_off'    => __( 'Hide', 'jet-wc-product-table' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => __( 'Toggle the table header visibility.', 'jet-wc-product-table' ),
			]
		);

		$this->add_control(
			'sticky_header',
			[
				'label'        => __( 'Sticky Header', 'jet-wc-product-table' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Enable', 'jet-wc-product-table' ),
				'label_off'    => __( 'Disable', 'jet-wc-product-table' ),
				'return_value' => 'yes',
				'default'      => '',
				'description'  => __( 'Keep the table header visible while scrolling.', 'jet-wc-product-table' ),
				'condition'    => [
					'show_header' => 'yes',
				],
			]
		);

		$this->add_control(
			'lazy_load',
			[
				'label'        => __( 'Lazy Load', 'jet-wc-product-table' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Enable', 'jet-wc-product-table' ),
				'label_off'    => __( 'Disable', 'jet-wc-product-table' ),
				'return_value' => 'yes',
				'default'      => '',
				'description'  => __( 'Load table rows dynamically as you scroll.', 'jet-wc-product-table' ),
			]
		);

		$this->add_control(
			'show_footer',
			[
				'label'        => __( 'Show Footer', 'jet-wc-product-table' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Show', 'jet-wc-product-table' ),
				'label_off'    => __( 'Hide', 'jet-wc-product-table' ),
				'return_value' => 'yes',
				'default'      => '',
				'description'  => __( 'Toggle the table footer visibility.', 'jet-wc-product-table' ),
			]
		);

		$this->add_control(
			'mobile_layout',
			[
				'label'       => __( 'Mobile Layout', 'jet-wc-product-table' ),
				'type'        => Controls_Manager::SELECT,
				'options'     => [
					'moveable'  => __( 'Moveable', 'jet-wc-product-table' ),
					'transform' => __( 'Transform', 'jet-wc-product-table' ),
					'collapsed' => __( 'Collapsed', 'jet-wc-product-table' ),
				],
				'default'     => 'moveable',
				'description' => __( 'Set the table layout for mobile devices.', 'jet-wc-product-table' ),
			]
		);

		$this->add_control(
			'pager',
			[
				'label'        => __( 'Enable Pager', 'jet-wc-product-table' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Enable', 'jet-wc-product-table' ),
				'label_off'    => __( 'Disable', 'jet-wc-product-table' ),
				'return_value' => 'yes',
				'default'      => '',
				'description'  => __( 'Enable pagination for the table.', 'jet-wc-product-table' ),
			]
		);

		$this->add_control(
			'pager_position',
			[
				'label'       => __( 'Pager Position', 'jet-wc-product-table' ),
				'type'        => Controls_Manager::SELECT,
				'options'     => [
					'before' => __( 'Before Table', 'jet-wc-product-table' ),
					'after'  => __( 'After Table', 'jet-wc-product-table' ),
					'both'   => __( 'Before & After Table', 'jet-wc-product-table' ),
				],
				'default'     => 'after',
				'description' => __( 'Set the position of the pager.', 'jet-wc-product-table' ),
				'condition'   => [
					'pager' => 'yes',
				],
			]
		);

		$this->add_control(
			'load_more',
			[
				'label'        => __( 'Enable Load More', 'jet-wc-product-table' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Enable', 'jet-wc-product-table' ),
				'label_off'    => __( 'Disable', 'jet-wc-product-table' ),
				'return_value' => 'yes',
				'default'      => '',
				'description'  => __( 'Enable a "Load More" button to load additional rows.', 'jet-wc-product-table' ),
			]
		);

		$this->add_control(
			'load_more_label',
			[
				'label'       => __( 'Load More Label', 'jet-wc-product-table' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Load More', 'jet-wc-product-table' ),
				'description' => __( 'Text for the "Load More" button.', 'jet-wc-product-table' ),
				'condition'   => [
					'load_more' => 'yes',
				],
			]
		);

		$this->end_controls_section();

		// Filters section
		$this->start_controls_section(
			'section_filters',
			[
				'label' => __( 'Filters', 'jet-wc-product-table' ),
			]
		);

		$this->add_control(
			'inherit_global_filters',
			[
				'label'        => __( 'Inherit global filters', 'jet-wc-product-table' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'jet-wc-product-table' ),
				'label_off'    => __( 'No', 'jet-wc-product-table' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$static_filter_default = [
			[
				'filter_type' => 'tax_query',
				'label'       => __( 'Product Categories', 'jet-wc-product-table' ),
				'query_var'   => 'product_cat',
				'placeholder' => __( 'Select Category...', 'jet-wc-product-table' ),
			],
		];

		$filters_repeater = new Repeater();

		$filters_repeater->add_control(
			'filter_type',
			[
				'label'   => __( 'Filter Type', 'jet-wc-product-table' ),
				'type'    => Controls_Manager::SELECT,
				'options' => array_merge(
					[ '' => __( 'Select a filter type...', 'jet-wc-product-table' ) ],
					Woo_Glossary::instance()->get_filters_options()
				),
				'default' => '',
			]
		);

		$filters_repeater->add_control(
			'label',
			[
				'label'       => __( 'Label', 'jet-wc-product-table' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Filter Label', 'jet-wc-product-table' ),
				'description' => __( 'Set the label for the filter displayed in the table header.', 'jet-wc-product-table' ),
				'dynamic'     => [
					'active' => true,
				],
			]
		);

		$formatted_filter_controls = $this->get_formatted_filter_controls();

		foreach ( $formatted_filter_controls as $control ) {
			$filters_repeater->add_control(
				$control['id'],
				$control
			);
		}

		$this->add_control(
			'filters_manager',
			[
				'label'       => __( 'Filters Manager', 'jet-wc-product-table' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $filters_repeater->get_controls(),
				'title_field' => '{{{ label }}}',
				'default'     => $static_filter_default,
				'condition'   => [
					'inherit_global_filters!' => 'yes',
				],
			]
		);
		$this->end_controls_section();
	}

	/**
	 * Render the widget output on the frontend.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		$attributes = [
			'preset'          => $this->prepare_preset( $settings ),
			'columns'         => $this->prepare_columns( $settings ),
			'filters'         => $this->prepare_filters( $settings ),
			'query'           => $this->prepare_query( $settings ),
			'settings'        => $this->prepare_settings( $settings ),
			'filters_enabled' => $this->is_filters_enabled( $settings ),
		];

		echo $this->render_callback( $attributes ); // phpcs:ignore
	}

	/**
	 * Prepare preset ID from settings.
	 *
	 * @param array $settings Widget settings.
	 *
	 * @return int|null Preset ID or null.
	 */
	protected function prepare_preset( $settings ) {
		return ! empty( $settings['preset'] ) ? absint( str_replace( 'id_', '', $settings['preset'] ) ) : null;
	}

	/**
	 * Prepare columns data for the table.
	 *
	 * @param array $settings Widget settings.
	 *
	 * @return array Columns data.
	 */
	protected function prepare_columns( $settings ) {
		if ( 'yes' === ( $settings['inherit_global_columns'] ?? '' ) ) {
			return Plugin::instance()->settings->get( 'columns', [] );
		}

		$columns          = $settings['columns_manager'] ?? [];
		$prepared_columns = [];

		foreach ( $columns as $column_data ) {
			$column_id  = $column_data['column_type'];
			$column_obj = Plugin::instance()->columns_controller->get_column( $column_id );

			if ( $column_obj ) {
				$sanitized_data = $column_obj->sanitize_column_data( $column_data );
				$sanitized_data['id'] = $column_id;
				$prepared_columns[] = $sanitized_data;
			}
		}

		return $prepared_columns;
	}

	/**
	 * Retrieve formatted column-specific controls for Elementor.
	 *
	 * This method generates an array of control settings for each registered column,
	 * allowing each column type to have unique controls in the Elementor editor.
	 * Controls are added dynamically based on each column's additional settings,
	 * with conditions specified to show relevant controls for selected column types.
	 *
	 * @return array An array of formatted controls for each column, suitable for use in Elementor.
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
				if ( ! isset( $controls[ $setting_id ] ) ) {
					$control_label       = $setting_data['label'] ?? '';
					$control_type        = $setting_data['type'] ?? '';
					$control_default     = $setting_data['default'] ?? '';
					$control_description = $setting_data['description'] ?? '';

					if ( ! $control_type ) {
						continue;
					}

					// Change control types according to Elementor API
					switch ( $control_type ) {
						case 'toggle':
							$control_type    = \Elementor\Controls_Manager::SWITCHER;
							$control_default = $control_default ? 'yes' : 'no';
							break;
					}

					$controls[ $setting_id ] = [
						'label'       => $control_label,
						'type'        => $control_type,
						'default'     => $control_default,
						'description' => $control_description,
						'id'          => $setting_id,
						'condition'   => [
							'column_type' => [],
						],
					];
				}

				if ( ! in_array( $column_id, $controls[ $setting_id ]['condition']['column_type'], true ) ) {
					$controls[ $setting_id ]['condition']['column_type'][] = $column_id;
				}

				// Check if the setting has 'options' and format them
				if ( isset( $setting_data['options'] ) && is_array( $setting_data['options'] ) ) {
					$formatted_options = [];
					foreach ( $setting_data['options'] as $option ) {
						if ( isset( $option['value'], $option['label'] ) ) {
							$formatted_options[ $option['value'] ] = $option['label'];
						}
					}
					$controls[ $setting_id ]['options'] = $formatted_options;
				}
			}
		}

		return $controls;
	}

	/**
	 * Prepare filters data for the table.
	 *
	 * @param  array $settings Widget settings.
	 *
	 * @return array Filters data.
	 */
	protected function prepare_filters( $settings ) {
		$filters_enabled = Plugin::instance()->settings->get( 'filters_enabled', false );

		if ( 'yes' === ( $settings['inherit_global_filters'] ?? '' ) ) {
			return $filters_enabled ? Plugin::instance()->settings->get( 'filters', [] ) : [];
		}

		$filters          = $settings['filters_manager'] ?? [];
		$prepared_filters = [];

		foreach ( $filters as $filter_data ) {
			$filter_id  = $filter_data['filter_type'];
			$filter_obj = Plugin::instance()->filters_controller->get_filter_type( $filter_id );

			if ( $filter_obj ) {
				$filter_query_attr_name   = 'query_var';
				$filter_query_custom_name = "{$filter_id}_{$filter_query_attr_name}";

				if ( ! empty( $filter_data[ $filter_query_custom_name ] ) ) {
					$filter_data[ $filter_query_attr_name ] = $filter_data[ $filter_query_custom_name ];
				}

				if ( empty( $filter_data['label'] ) ) {
					$filter_data['label'] = ucfirst( str_replace( '_', ' ', $filter_id ) );
				}

				$prepared_filters[] = $filter_obj->sanitize_data( $filter_data );
			}
		}

		return $prepared_filters;
	}

	/**
	 * Retrieve formatted filter-specific controls for Elementor.
	 *
	 * This method generates an array of control settings for each registered filter type,
	 * allowing each filter type to have unique controls in the Elementor editor.
	 *
	 * @return array An array of formatted controls for each filter, suitable for use in Elementor.
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

					// Skip if the filter instance is invalid or not found.
					if ( ! $control_type ) {
						continue;
					}

					// Map custom control types to Elementor's standard control types.
					switch ( $control_type ) {
						case 'toggle':
							$control_type = \Elementor\Controls_Manager::SWITCHER;
							break;
						case 'search':
							$control_type = \Elementor\Controls_Manager::TEXT;
							break;
					}

					$controls[ $setting_name ] = [
						'label'       => $control_label,
						'type'        => $control_type,
						'default'     => $control_default,
						'description' => $control_description,
						'id'          => $setting_name,
						'condition'   => [
							'filter_type' => [],
						],
					];

					if ( 'search_button_label' === $setting_id ) {
						$controls[ $setting_name ]['condition']['show_search_button'] = 'yes';
					}

					// If the setting includes options, format them.
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

				if ( ! in_array( $filter_id, $controls[ $setting_name ]['condition']['filter_type'], true ) ) {
					$controls[ $setting_name ]['condition']['filter_type'][] = $filter_id;
				}
			}
		}

		return $controls;
	}

	/**
	 * Determine if filters are enabled.
	 *
	 * @param  array $settings Widget settings.
	 *
	 * @return bool True if filters are enabled, false otherwise.
	 */
	protected function is_filters_enabled( $settings ) {
		return ! empty( $settings['filters_manager'] ) || ( 'yes' === ( $settings['inherit_global_filters'] ?? '' ) );
	}

	/**
	 * Prepare query arguments for the table.
	 *
	 * @param  array $settings Widget settings.
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
			$query_args          = array_merge( $query_args, $query_args_products );
		}

		if ( 'variations' === $query_type ) {
			$query_args['query_type']     = 'variations';
			$query_args['variation_type'] = $settings['variation_type'] ?? 'current_product';
			$query_args['product_ids']    = $settings['product_ids'] ?? '';
			$query_args['product_skus']   = $settings['product_skus'] ?? '';
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
	 * Prepare settings for rendering the table.
	 *
	 * This method processes and sanitizes the widget's settings, ensuring they are ready for use
	 * in rendering the table. It includes options related to table layout, pagination,
	 * lazy-loading, and responsiveness. Default values are applied if specific settings are not defined.
	 *
	 * @param array $settings The raw widget settings provided by Elementor.
	 *
	 * @return array The prepared and sanitized settings.
	 */
	protected function prepare_settings( $settings ) {
		$is_edit_mode = \Elementor\Plugin::$instance->editor->is_edit_mode();

		$prepared = [
			'direction'       => $settings['direction'] ?? 'horizontal',
			'show_header'     => ( ! empty( $settings['show_header'] ) && 'yes' === $settings['show_header'] ),
			'sticky_header'   => ( ! empty( $settings['sticky_header'] ) && 'yes' === $settings['sticky_header'] ),
			'show_footer'     => ( ! empty( $settings['show_footer'] ) && 'yes' === $settings['show_footer'] ),
			'mobile_layout'   => $settings['mobile_layout'] ?? 'moveable',
			'lazy_load'       => ( ! empty( $settings['lazy_load'] ) && 'yes' === $settings['lazy_load'] && ! $is_edit_mode ),
			'pager'           => ( ! empty( $settings['pager'] ) && 'yes' === $settings['pager'] ),
			'pager_position'  => $settings['pager_position'] ?? 'after',
			'load_more'       => ( ! empty( $settings['load_more'] ) && 'yes' === $settings['load_more'] ),
			'load_more_label' => $settings['load_more_label'] ?? __( 'Load More', 'jet-wc-product-table' ),
		];

		ksort( $prepared );

		return $prepared;
	}
}
