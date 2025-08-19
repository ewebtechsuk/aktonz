<?php

namespace JET_ABAF\Dashboard\Post_Meta;

use JET_ABAF\Plugin;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Configuration_Meta extends Base_Vue_Meta_Box {

	public function __construct() {

		$this->cpt_slug = Plugin::instance()->settings->get( 'apartment_post_type' );
		$this->meta_key = 'jet_abaf_configuration';

		if ( wp_doing_ajax() ) {
			add_action( 'wp_ajax_' . $this->meta_key, [ $this, 'save_post_meta' ] );
		}

		parent::__construct( $this->cpt_slug );

	}

	public function add_meta_box() {

		if ( ! $this->is_cpt_page() ) {
			return;
		}

		add_meta_box(
			$this->meta_key,
			__( 'Date Picker Config', 'jet-booking' ),
			[ $this, 'meta_box_callback' ],
			[ $this->cpt_slug ],
			'normal',
			'high'
		);

	}

	/**
	 * Get default meta.
	 *
	 * @since  2.6.3 Added `one_day_bookings`, `weekly_bookings`, `week_offset` meta parameters.
	 * @access public
	 *
	 * @return array[]
	 */
	public function get_default_meta() {
		return [
			'config' => [
				'enable_config'         => false,
				'booking_period'        => 'per_nights',
				'allow_checkout_only'   => false,
				'one_day_bookings'      => false,
				'weekly_bookings'       => false,
				'week_offset'           => '',
				'start_day_offset'      => '',
				'min_days'              => '',
				'max_days'              => '',
				'end_date_type'         => 'none',
				'end_date_range_number' => '1',
				'end_date_range_unit'   => 'year',
			],
		];
	}

	/**
	 * Parse settings.
	 *
	 * @since  2.6.3 Added `one_day_bookings`, `weekly_bookings`, `week_offset` meta parameters parsing.
	 * @access public
	 *
	 * @param array $settings List of settings.
	 *
	 * @return array
	 */
	public function parse_settings( $settings ) {

		foreach ( $settings['config'] as $key => $value ) {
			switch ( $key ) {
				case 'enable_config':
				case 'allow_checkout_only':
				case 'one_day_bookings':
				case 'weekly_bookings':
					$settings['config'][ $key ] = filter_var( $value, FILTER_VALIDATE_BOOLEAN );
					break;

				default:
					$settings['config'][ $key ] = $value;
					break;
			}
		}

		return $settings;

	}

	public function assets() {

		if ( ! $this->is_cpt_page() ) {
			return;
		}

		parent::assets();

		wp_enqueue_script(
			'jet-abaf-meta-configuration',
			$this->plugin_url . 'assets/js/admin/meta-configuration.js',
			[ 'jet-abaf-meta-extras' ],
			$this->plugin_version,
			true
		);

	}

	protected function get_vue_templates() {
		return [
			[
				'dir'  => 'jet-abaf-settings',
				'file' => 'settings-common-config',
			],
		];
	}

	/**
	 * Meta box callback.
	 *
	 * Includes meta box template.
	 *
	 * @since  2.6.0
	 * @access public
	 */
	public function meta_box_callback() {
		require_once( $this->plugin_path . 'templates/admin/post-meta/configuration-meta.php' );
	}

}
