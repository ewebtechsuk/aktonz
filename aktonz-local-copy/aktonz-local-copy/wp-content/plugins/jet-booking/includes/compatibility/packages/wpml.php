<?php

namespace JET_ABAF\Compatibility\Packages;

defined( 'ABSPATH' ) || exit;

class WPML {

	public function __construct() {

		add_filter( 'jet-abaf/db/initial-apartment-id', [ $this, 'set_initial_booking_item_id' ] );
		add_filter( 'jet-booking/wc-integration/apartment-id', [ $this, 'set_booking_item_id' ] );
		add_filter( 'jet-booking/tools/post-type-args', [ $this, 'set_additional_post_type_args' ] );
		add_filter( 'jet-booking/settings/on-save/value', [ $this, 'register_settings_input_for_string_translation' ], 10, 2 );
		add_filter( 'jet-booking/compatibility/translate-labels', [ $this, 'set_translation_for_settings_input' ] );
		add_filter( 'jet-engine/forms/handler/wp_redirect_url', [ $this, 'prepare_redirect_link' ] );

		add_action( 'jet-abaf/dashboard/bookings-page/before-page-config', [ $this, 'set_required_page_language' ] );

	}

	/**
	 * Register settings input for string translation.
	 *
	 * Registers settings input fields an individual text string for translation.
	 *
	 * @since  2.7.0
	 * @access public
	 *
	 * @param string $value   Input field value.
	 * @param string $setting Setting key name.
	 *
	 * @return mixed
	 */
	public function register_settings_input_for_string_translation( $value, $setting ) {

		$default_lang = apply_filters( 'wpml_default_language', NULL );
		$current_lang = apply_filters( 'wpml_current_language', NULL );

		$setting = str_replace( '_', '-', $setting );
		$setting = str_replace( 'labels-', '', $setting );

		if ( $current_lang === $default_lang && jet_abaf()->settings->get_labels( $setting ) ) {
			do_action( 'wpml_register_single_string', 'JetBooking Custom Calendar Labels', 'Custom Label - ' . $value, $value );
		}

		return $value;

	}

	/**
	 * Set translations for settings input.
	 *
	 * Handle translations for individual settings input text string.
	 *
	 * @since  2.7.0
	 * @access public
	 *
	 * @param array $labels List of labels.
	 *
	 * @return mixed
	 */
	public function set_translation_for_settings_input( $labels ) {

		$default_lang = apply_filters( 'wpml_default_language', NULL );
		$current_lang = apply_filters( 'wpml_current_language', NULL );

		if ( $current_lang === $default_lang ) {
			return $labels;
		}

		foreach ( $labels as $key => $label ) {
			if ( 'month-name' === $key ) {
				$label = implode( ', ', $label );
			}

			$label = apply_filters( 'wpml_translate_single_string', $label, 'JetBooking Custom Calendar Labels', 'Custom Label - ' . $label, $current_lang );

			if ( 'month-name' === $key ) {
				$label = jet_abaf()->settings->get_array_from_string( $label );
			}

			$labels[ $key ] = $label;
		}

		return $labels;

	}

	/**
	 * Set initial booking item id.
	 *
	 * Returns a booking item id for the default site language.
	 *
	 * @since  2.5.5
	 * @access public
	 *
	 * @param int|string $id Apartment post type ID.
	 *
	 * @return mixed|void
	 */
	public function set_initial_booking_item_id( $id ) {

		$default_lang = apply_filters( 'wpml_default_language', NULL );
		$post_type    = jet_abaf()->settings->get( 'apartment_post_type' );

		return apply_filters( 'wpml_object_id', $id, $post_type, FALSE, $default_lang );

	}

	/**
	 * Set booking item id.
	 *
	 * Returns a booking item id for current site language.
	 *
	 * @since  2.5.5
	 * @access public
	 *
	 * @param int|string $id Apartment post type ID.
	 *
	 * @return mixed|void
	 */
	public function set_booking_item_id( $id ) {

		$current_language = apply_filters( 'wpml_current_language', NULL );
		$post_type        = jet_abaf()->settings->get( 'apartment_post_type' );

		return apply_filters( 'wpml_object_id', $id, $post_type, FALSE, $current_language );

	}

	/**
	 * Set additional post type args.
	 *
	 * Returns booking post type arguments with additional option so we can see only default language posts.
	 *
	 * @since  2.5.5
	 * @access public
	 *
	 * @param array $args List of post arguments.
	 *
	 * @return mixed
	 */
	public function set_additional_post_type_args( $args ) {

		$args['suppress_filters'] = 0;

		return $args;

	}

	/**
	 * Set required page language.
	 *
	 * Switch language to default for proper posts display in bookings list.
	 *
	 * @since  2.5.5
	 * @access public
	 */
	public function set_required_page_language() {

		$default_lang = apply_filters( 'wpml_default_language', NULL );

		do_action( 'wpml_switch_language', $default_lang );

	}

	/**
	 * Prepare redirect link.
	 *
	 * @param string $url Redirect link.
	 *
	 * @return mixed|void
	 */
	public function prepare_redirect_link( $url ) {

		if ( isset( $_GET['lang'] ) ) {
			$lang = str_replace( '/', '', $_GET['lang'] );
			$url  = apply_filters( 'wpml_permalink', $url, $lang, true );
		}

		return $url;

	}

}

new WPML();