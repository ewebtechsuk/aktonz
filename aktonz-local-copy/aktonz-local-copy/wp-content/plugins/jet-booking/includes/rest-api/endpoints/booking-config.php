<?php

namespace JET_ABAF\Rest_API\Endpoints;

defined( 'ABSPATH' ) || exit;

class Booking_Config extends Base {

	/**
	 * Get name.
	 *
	 * Returns route name.
	 *
	 * @since  2.2.5
	 *
	 * @return string
	 */
	public function get_name() {
		return 'booking-config';
	}

	/**
	 * Callback.
	 *
	 * API callback.
	 *
	 * @since  2.2.5
	 * @since  3.2.0 Refactored.
	 * @since  3.6.0 Added attributes & guests handling.
	 *
	 * @param object $request Endpoint request object.
	 *
	 * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response
	 * @throws \Exception
	 */
	public function callback( $request ) {

		$params = $request->get_params();
		$item   = ! empty( $params['item'] ) ? $params['item'] : [];

		if ( empty( $item ) ) {
			return rest_ensure_response( [
				'success' => false,
				'data'    => __( 'No data to check booking config.', 'jet-booking' ),
			] );
		}

		if ( empty( $item['apartment_id'] ) ) {
			return rest_ensure_response( [
				'success' => false,
				'data'    => __( 'Incorrect item data.', 'jet-booking' ),
			] );
		}

		$localized_data = jet_abaf()->assets->get_localized_data( $item['apartment_id'] );
		$response       = [
			'success'       => true,
			'start_of_week' => get_option( 'start_of_week' ) ? 'monday' : 'sunday',
			'units'         => jet_abaf()->db->get_apartment_units( $item['apartment_id'] ),
		];

		if ( 'wc_based' === jet_abaf()->settings->get( 'booking_mode' ) ) {
			$has_guests = get_post_meta( $item['apartment_id'], '_jet_booking_has_guests', true );

			if ( filter_var( $has_guests, FILTER_VALIDATE_BOOLEAN ) ) {
				$response['guests_settings'] = [
					'min' => get_post_meta( $item['apartment_id'], '_jet_booking_min_guests', true ) ?: 1,
					'max' => get_post_meta( $item['apartment_id'], '_jet_booking_max_guests', true ) ?: 1,
				];
			} else {
				$response['guests_settings'] = [];
			}

			$response['attributes_list'] = jet_abaf()->wc->mode->attributes->get_attributes( $item['apartment_id'] );
		}

		return rest_ensure_response( wp_parse_args( $localized_data, $response ) );

	}

	/**
	 * Permission callback.
	 *
	 * Check user access to current end-point.
	 *
	 * @since  2.2.5
	 *
	 * @param object $request Endpoint request object.
	 *
	 * @return bool
	 */
	public function permission_callback( $request ) {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Get method.
	 *
	 * Returns endpoint request method - GET/POST/PUT/DELETE.
	 *
	 * @since  2.2.5
	 *
	 * @return string
	 */
	public function get_method() {
		return 'POST';
	}

}