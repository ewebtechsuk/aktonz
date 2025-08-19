<?php
namespace JET_ABAF\Dashboard\Post_Meta;

use JET_ABAF\Plugin;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
class Price_Meta extends Base_Vue_Meta_Box {

	public function __construct() {

		$this->cpt_slug = Plugin::instance()->settings->get( 'apartment_post_type' );
		$this->meta_key = 'jet_abaf_price';

		if ( wp_doing_ajax() ) {
			add_action( 'wp_ajax_' . $this->meta_key, [ $this, 'save_post_meta' ] );
		}

		parent::__construct( $this->cpt_slug );

	}

	public function get_default_meta() {

		$post_ID          = get_the_ID();
		$_apartment_price = get_post_meta( $post_ID, '_apartment_price', true );

		return [
			'_apartment_price' => $_apartment_price,
			'_pricing_rates'   => [],
			'_seasonal_prices' => [],
			'_weekend_prices'  => $this->get_default_weekend_prices( $_apartment_price ),
		];

	}

	/**
	 * Return default value.
	 *
	 * @return [array] [description]
	 */
	public function get_meta_assets() {
		return [
			'confirm_message'        => esc_html__( 'Are you sure?', 'jet-booking' ),
			'price_label'            => esc_html__( 'Price:', 'jet-booking' ),
			'season_label'           => esc_html__( 'Season:', 'jet-booking' ),
			'weekdays_label'         => [
				'sun' => esc_html__( 'Sun', 'jet-booking' ),
				'mon' => esc_html__( 'Mon', 'jet-booking' ),
				'tue' => esc_html__( 'Tue', 'jet-booking' ),
				'wed' => esc_html__( 'Wed', 'jet-booking' ),
				'thu' => esc_html__( 'Thu', 'jet-booking' ),
				'fri' => esc_html__( 'Fri', 'jet-booking' ),
				'sat' => esc_html__( 'Sat', 'jet-booking' ),
			],
			'period_repeats_seasons' => [
				[
					'label' => esc_html__( 'Not repeat', 'jet-booking' ),
					'value' => 'not_repeat',
				],
				[
					'label' => esc_html__( 'Week', 'jet-booking' ),
					'value' => 'week',
				],
				[
					'label' => esc_html__( 'Month', 'jet-booking' ),
					'value' => 'month',
				],
				[
					'label' => esc_html__( 'Year', 'jet-booking' ),
					'value' => 'year',
				],
			],
			'default_weekend_prices' => $this->get_default_weekend_prices(),
		];
	}

	/**
	 * Return default weekend price.
	 *
	 * @return [array] [description]
	 */
	public function get_default_weekend_prices( $_apartment_price = 0 ) {
		return [
			'sun' => [
				'price'  => $_apartment_price,
				'active' => false,
			],
			'mon' => [
				'price'  => $_apartment_price,
				'active' => false,
			],
			'tue' => [
				'price'  => $_apartment_price,
				'active' => false,
			],
			'wed' => [
				'price'  => $_apartment_price,
				'active' => false,
			],
			'thu' => [
				'price'  => $_apartment_price,
				'active' => false,
			],
			'fri' => [
				'price'  => $_apartment_price,
				'active' => false,
			],
			'sat' => [
				'price'  => $_apartment_price,
				'active' => false,
			],
		];
	}

	public function parse_settings( $settings ) {

		foreach ( $settings as $key => $value ) {
			switch ( $key ) {
				case '_apartment_price':
					$value = empty( $value ) ? 0 : $value + 0;
					break;
				case '_pricing_rates':
					$value = is_array( $value ) ? $value : [];
					break;
				case '_weekend_prices':
					foreach ( $value as $weekend_key => $weekend_value ) {
						$weekend_value['active'] = filter_var( $weekend_value['active'], FILTER_VALIDATE_BOOLEAN );
						$weekend_value['price']  = empty( $weekend_value['price'] ) ? 0 : $weekend_value['price'] + 0;

						$value[ $weekend_key ] = $weekend_value;
					}
					break;
				case '_seasonal_prices':
					foreach ( $value as $seasonal_key => $seasonal_value ) {
						$seasonal_value = $this->parse_settings( $seasonal_value );

						$seasonal_value['_pricing_rates'] = $seasonal_value['_pricing_rates'] ?? [];
						$seasonal_value['enable_config']  = filter_var( $seasonal_value['enable_config'], FILTER_VALIDATE_BOOLEAN );

						$value[ $seasonal_key ] = $seasonal_value;
					}
					break;

			}

			$settings[ $key ] = $value;
		}

		return $settings;

	}

	/**
	 * Function for backward compatibility.
	 */
	protected function backward_save_post_meta( $meta ) {

		if ( isset( $meta['_apartment_price'] ) ) {
			update_post_meta( $meta['ID'], '_apartment_price', $meta['_apartment_price'] );
		}

		if ( isset( $meta['_pricing_rates'] ) ) {
			update_post_meta( $meta['ID'], '_pricing_rates', $meta['_pricing_rates'] );
		}

	}

	public function assets() {

		if ( ! $this->is_cpt_page() ) {
			return;
		}

		parent::assets();

		wp_enqueue_script(
			'jet-abaf-meta-price',
			$this->plugin_url . 'assets/js/admin/meta-price.js',
			[ 'jet-abaf-meta-extras', 'vuejs-datepicker' ],
			$this->plugin_version,
			true
		);

		wp_localize_script( 'jet-abaf-meta-price', 'jetAbafAssets', $this->get_meta_assets() );

	}

	public function add_meta_box() {

		if ( ! $this->is_cpt_page() ) {
			return;
		}

		add_meta_box(
			$this->meta_key,
			esc_html__( 'Pricing Settings', 'jet-booking' ),
			[ $this, 'meta_box_callback' ],
			[ $this->cpt_slug ],
			'normal',
			'high'
		);

	}

	/**
	 * Require metabox html.
	 */
	public function meta_box_callback() {
		require_once( $this->plugin_path . 'templates/admin/post-meta/price-meta.php' );
	}
}
