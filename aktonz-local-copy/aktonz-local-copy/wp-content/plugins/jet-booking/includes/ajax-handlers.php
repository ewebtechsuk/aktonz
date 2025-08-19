<?php
namespace JET_ABAF;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Ajax_Handlers {

	public function __construct() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			add_action( 'wp_ajax_jet_booking_get_timepicker_slots', [ $this, 'get_timepicker_slots' ] );
			add_action( 'wp_ajax_nopriv_jet_booking_get_timepicker_slots', [ $this, 'get_timepicker_slots' ] );
		}
	}

	/**
	 * Get timepicker slots.
	 *
	 * Retrieves available time slots for booking based on check-in and check-out dates.
	 *
	 * @since 3.7.0
	 *
	 * @return void
	 */
	public function get_timepicker_slots() {

		$post_id        = ! empty( $_POST['postID'] ) ? $_POST['postID'] : 0;
		$check_in_date  = ! empty( $_POST['checkInDate'] ) ? strtotime( $_POST['checkInDate'] ) : '';
		$check_out_date = ! empty( $_POST['checkOutDate'] ) ? strtotime( $_POST['checkOutDate'] ) : '';
		$booking        = ! empty( $_POST['bookingID'] ) && absint( $_POST['bookingID'] ) ? jet_abaf_get_booking( absint( $_POST['bookingID'] ) ) : 0;

		if ( $check_in_date >= $check_out_date ) {
			$check_out_date = $check_in_date + 12 * HOUR_IN_SECONDS;
		}

		$booking_check_in_time  = '';
		$booking_check_out_time = '';

		if ( $booking ) {
			$booking_check_in_time  = $booking->get_check_in_time();
			$booking_check_out_time = $booking->get_check_out_time();
		}

		$range_start = jet_abaf()->settings->get( 'timepicker_range_start' );
		$range_end   = jet_abaf()->settings->get( 'timepicker_range_end' );

		$check_in_time_slots  = jet_abaf()->tools->get_timepicker_slots( $range_start, $range_end, $booking_check_in_time );
		$check_out_time_slots = jet_abaf()->tools->get_timepicker_slots( $range_start, $range_end, $booking_check_out_time );

		if ( jet_abaf()->settings->get( 'timepicker_restrictions' ) ) {
			$buffer = jet_abaf()->settings->get( 'timepicker_buffer' );

			// Сase when the selected check-in date falls on an existing check-out date.
			$check_in_bookings = jet_abaf()->db->query( [
				'apartment_id'   => $post_id,
				'check_out_date' => $check_in_date,
			] );

			if ( ! empty( $check_in_bookings ) ) {
				$check_in_start_time = '';
				$check_out_time      = $check_in_bookings[0]['check_out_time'];

				foreach ( $check_in_bookings as $check_in_booking ) {
					if ( $check_out_time && $check_in_booking['check_out_time'] < $check_out_time ) {
						$check_out_time = $check_in_booking['check_out_time'];
					}
				}

				if ( $check_out_time ) {
					$check_in_start_time = $check_out_time + $buffer;
				}

				if ( $check_in_start_time ) {
					$check_in_time_slots = jet_abaf()->tools->get_timepicker_slots( $check_in_start_time, $range_end, $booking_check_in_time );
				}
			}

			// Сase when the selected check-out date falls on an existing check-in date.
			$check_out_bookings = jet_abaf()->db->query( [
				'apartment_id'  => $post_id,
				'check_in_date' => $check_out_date + 1,
			] );

			if ( ! empty( $check_out_bookings ) ) {
				$check_out_end_time = '';
				$check_in_time      = $check_out_bookings[0]['check_in_time'];

				foreach ( $check_out_bookings as $check_out_booking ) {
					if ( $check_in_time && $check_out_booking['check_in_time'] > $check_in_time ) {
						$check_in_time = $check_out_booking['check_in_time'];
					}
				}

				if ( $check_in_time ) {
					$check_out_end_time = $check_in_time - $buffer;
				}

				if ( $check_out_end_time ) {
					$check_out_time_slots = jet_abaf()->tools->get_timepicker_slots( $range_start, $check_out_end_time, $booking_check_out_time );
				}
			}
		}

		$response['check_in_time_slots']  = $check_in_time_slots;
		$response['check_out_time_slots'] = $check_out_time_slots;

		wp_send_json_success( $response );

	}

}
