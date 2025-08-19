<?php

namespace JET_ABAF\Rest_API\Endpoints;

defined( 'ABSPATH' ) || exit;

class Add_Booking extends Base {

	/**
	 * Get name.
	 *
	 * Returns route name.
	 *
	 * @since  2.5.0
	 *
	 * @return string
	 */
	public function get_name() {
		return 'add-booking';
	}

	/**
	 * Callback.
	 *
	 * API callback.
	 *
	 * @since  2.5.0
	 *
	 * @param object $request Endpoint request object.
	 *
	 * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response
	 */
	public function callback( $request ) {

		$params = $request->get_params();
		$item   = $params['item'] ?? [];

		if ( empty( $item ) ) {
			return rest_ensure_response( [
				'success' => false,
				'data'    => __( 'Booking could not be added.', 'jet-booking' ),
			] );
		}

		if ( empty( $item['check_in_date'] ) || empty( $item['check_out_date'] ) ) {
			return rest_ensure_response( [
				'success' => false,
				'data'    => __( 'Booking date is empty.', 'jet-booking' ),
			] );
		}

		$item['check_in_date']  = strtotime( $item['check_in_date'] );
		$item['check_out_date'] = strtotime( $item['check_out_date'] );

		if ( $item['check_in_date'] >= $item['check_out_date'] ) {
			$item['check_out_date'] = $item['check_in_date'] + 12 * HOUR_IN_SECONDS;
		}

		if ( empty( $item['apartment_unit'] ) ) {
			$item['apartment_unit'] = jet_abaf()->db->get_available_unit( $item );
		}

		$is_available       = jet_abaf()->db->booking_availability( $item );
		$is_dates_available = jet_abaf()->db->is_booking_dates_available( $item );
		$is_days_available  = jet_abaf()->tools->is_booking_period_available( $item );

		if ( ! $is_available && ! $is_dates_available || ! $is_days_available ) {
			ob_start();

			echo __( 'Selected dates are not available.', 'jet-booking' ) . '<br>';

			if ( jet_abaf()->db->latest_result ) {
				echo __( 'Overlapping bookings: ', 'jet-booking' );

				$result = [];

				foreach ( jet_abaf()->db->latest_result as $ob ) {
					if ( ! empty( $ob['order_id'] ) ) {
						$result[] = sprintf( '<a href="%s" target="_blank">#%d</a>', get_edit_post_link( $ob['order_id'] ), $ob['order_id'] );
					} else {
						$result[] = '#' . $ob['booking_id'];
					}
				}

				echo implode( ', ', $result ) . '.';
			}

			return rest_ensure_response( [
				'success'              => false,
				'overlapping_bookings' => true,
				'html'                 => ob_get_clean(),
				'data'                 => __( 'Can`t add this item.', 'jet-booking' ),
			] );
		}

		$booking_id = jet_abaf()->db->insert_booking( $item );

		if ( $booking_id && ! empty( $params['relatedOrder'] ) ) {
			$this->set_related_order_data( $params['relatedOrder'], $booking_id, $item );
		}

		return rest_ensure_response( [ 'success' => true ] );

	}

	/**
	 * Set related order data.
	 *
	 * @since  3.0.0
	 * @since  3.6.0 Added `$booking` parameter.
	 *
	 * @param array      $order_data Initial order data list.
	 * @param string|int $booking_id Created booking ID.
	 * @param array      $booking    Booking data list.
	 *
	 * @return void
	 */
	public function set_related_order_data( $order_data, $booking_id, $booking ) {
		if ( 'plain' === jet_abaf()->settings->get( 'booking_mode' ) && ! jet_abaf()->settings->wc_integration_enabled() ) {
			$post_type        = jet_abaf()->settings->get( 'related_post_type' );
			$post_type_object = get_post_type_object( $post_type );

			$args = [
				'post_type'   => $post_type,
				'post_status' => ! empty( $order_data['orderStatus'] ) ? $order_data['orderStatus'] : 'draft',
			];

			if ( post_type_supports( $post_type, 'excerpt' ) ) {
				$args['post_excerpt'] = sprintf( __( 'This is %s post.', 'jet-booking' ), $post_type_object->labels->singular_name );
			}

			$post_id = wp_insert_post( $args );

			if ( ! $post_id || is_wp_error( $post_id ) ) {
				return;
			}

			wp_update_post( [
				'ID'         => $post_id,
				'post_title' => $post_type_object->labels->singular_name . ' #' . $post_id,
				'post_name'  => $post_type_object->labels->singular_name . '-' . $post_id,
			] );

			jet_abaf()->db->update_booking( $booking_id, [ 'order_id' => $post_id ] );
		} else {
			do_action( 'jet-booking/rest-api/add-booking/set-related-order-data', $order_data, $booking_id, $booking );
		}
	}

	/**
	 * Permission callback.
	 *
	 * Check user access to current end-point.
	 *
	 * @since  2.5.0
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
	 * @since  2.5.0
	 *
	 * @return string
	 */
	public function get_method() {
		return 'POST';
	}

}