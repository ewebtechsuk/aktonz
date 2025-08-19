<?php

namespace JET_ABAF\Rest_API\Endpoints;

defined( 'ABSPATH' ) || exit;

class Bookings_List extends Base {

	/**
	 * Get name.
	 *
	 * Returns route name.
	 *
	 * @since  2.0.0
	 *
	 * @return string
	 */
	public function get_name() {
		return 'bookings-list';
	}

	/**
	 * Callback.
	 *
	 * API callback.
	 *
	 * @since  2.0.0
	 * @since  3.2.0 Refactored.
	 *
	 * @param object $request Endpoint request object.
	 *
	 * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response
	 */
	public function callback( $request ) {

		$params   = jet_abaf()->db->prepare_params( $request->get_params() );
		$bookings = jet_abaf_get_bookings( wp_parse_args( $params, [ 'return' => 'arrays' ] ) );

		unset( $params['limit'] );

		return rest_ensure_response( [
			'success' => true,
			'data'    => $this->format_data( $bookings ),
			'total'   => count( jet_abaf_get_bookings( $params ) ),
		] );

	}

	/**
	 * Format data.
	 *
	 * Transform data to human-readable format and add additional parameters to booked item.
	 *
	 * @since  2.0.0
	 * @since  2.5.4 Added timestamp dates.
	 * @since  3.0.0 Added status handling.
	 * @since  3.6.0 Added booking ID and additional columns handling.
	 *
	 * @param array $bookings List of all bookings.
	 *
	 * @return array
	 */
	public function format_data( $bookings = [] ) {

		$date_format = get_option( 'date_format', 'F j, Y' );
		$time_format = get_option( 'time_format', 'g:i a' );

		return array_map( function ( $booking ) use ( $date_format, $time_format ) {

			$booking['check_in_date_timestamp']  = $booking['check_in_date'];
			$booking['check_in_date']            = date_i18n( $date_format, $booking['check_in_date'] );
			$booking['check_in_time']            = ! empty( $booking['check_in_time'] ) ? date_i18n( $time_format, $booking['check_in_time'] ) : '';
			$booking['check_out_date_timestamp'] = $booking['check_out_date'];
			$booking['check_out_date']           = date_i18n( $date_format, $booking['check_out_date'] );
			$booking['check_out_time']           = ! empty( $booking['check_out_time'] ) ? date_i18n( $time_format, $booking['check_out_time'] ) : '';
			$booking['status']                   = ! empty( $booking['status'] ) ? $booking['status'] : 'pending';

			if ( isset( $booking['ID'] ) ) {
				$booking['booking_id'] = $booking['ID'];

				unset( $booking['ID'] );
			}

			if ( isset( $booking['columns'] ) ) {
				foreach ( $booking['columns'] as $key => $value ) {
					if ( ! isset( $booking[ $key ] ) ) {
						$booking[ $key ] = $value;
					}
				}

				unset( $booking['columns'] );
			}

			return $booking;

		}, $bookings );

	}

	/**
	 * Permission callback.
	 *
	 * Check user access to current end-point.
	 *
	 * @since  2.0.0
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
	 * @since  2.0.0
	 *
	 * @return string
	 */
	public function get_method() {
		return 'GET';
	}

	/**
	 * Get args.
	 *
	 * Returns arguments config.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	public function get_args() {
		return [
			'offset'   => [
				'default'  => 0,
				'required' => false,
			],
			'per_page' => [
				'default'  => 50,
				'required' => false,
			],
			'filters'  => [
				'default'  => [],
				'required' => false,
			],
			'mode'     => [
				'default'  => 'all',
				'required' => false,
			],
		];
	}

}
