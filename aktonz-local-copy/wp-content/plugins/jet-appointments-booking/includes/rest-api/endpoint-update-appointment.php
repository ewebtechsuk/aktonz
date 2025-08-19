<?php
namespace JET_APB\Rest_API;

use JET_APB\Plugin;
use JET_APB\Time_Slots;

class Endpoint_Update_Appointment extends \Jet_Engine_Base_API_Endpoint {

	/**
	 * Returns route name
	 *
	 * @return string
	 */
	public function get_name() {
		return 'update-appointment';
	}

	/**
	 * API callback
	 *
	 * @return void
	 */
	public function callback( $request ) {

		$params      = $request->get_params();
		$item_id     = ! empty( $params['id'] ) ? absint( $params['id'] ) : 0;
		$item        = ! empty( $params['item'] ) ? $params['item'] : array();
		$not_allowed = array(
			'order_id',
			'user_id',
			'ID',
			'group_ID',
			'date_timestamp',
			'slot_timestamp',
			'slot_end_timestamp',
			'isGroupChief',
		);

		foreach ( $item as $key => $value ) {
			$value = in_array( $key, [ 'date', 'slot', 'slot_end' ] ) ? $item[ $key . '_timestamp' ] : $value ;
			$item[ $key ] = ! empty( $value ) ? esc_attr( $value ) : '';
		}

		if (  Plugin::instance()->settings->get( 'show_timezones' ) && ! empty( Plugin::instance()->db->appointments_meta->get_meta( $params['id'], 'user_timezone' ) ) ) {

			$app_local_zone = Plugin::instance()->db->appointments_meta->get_meta( $params['id'], 'user_timezone' );

			$args = [
				'user_local_time' => $this->get_local_date( $item['slot_timestamp'], $app_local_zone, Plugin::instance()->settings->get( 'slot_time_format' ) ) . "-" . $this->get_local_date( $item['slot_end_timestamp'], $app_local_zone, Plugin::instance()->settings->get( 'slot_time_format' ) ),
				'user_local_date' => $this->get_local_date( $item['slot_timestamp'], $app_local_zone, 'F d, Y' ),
			];
			
			foreach ( $args as $meta_key => $meta_value ) {
				Plugin::instance()->db->appointments_meta->update( [
					'meta_value' => $meta_value,
				], [
					'appointment_id' => $item_id,
					'meta_key'       => $meta_key,
				] );
			}

		}

		foreach ( $not_allowed as $key  ) {
			if ( isset( $item[ $key ] ) ) {
				unset( $item[ $key ] );
			}
		}

		if ( empty( $item ) ) {
			return rest_ensure_response( array(
				'success' => false,
				'data'    => __( 'No data to update', 'jet-appointments-booking' ),
			) );
		}

		$old_item = Plugin::instance()->db->get_appointment_by( 'ID', $item_id );

		$old_status = $old_item['status'];
		$new_status = ! empty( $item['status'] ) ? $item['status'] : $old_item['status'];

		if ( $new_status !== $old_status ) {

			if ( in_array( $new_status, Plugin::instance()->statuses->invalid_statuses() ) ) {
				Plugin::instance()->db->remove_appointment_date_from_excluded( $old_item );
			}

			if ( in_array( $old_status, Plugin::instance()->statuses->invalid_statuses() ) && in_array( $new_status, Plugin::instance()->statuses->exclude_statuses() ) ) {
				Plugin::instance()->db->maybe_exclude_appointment_date( $old_item );
			}
		}

		$item = wp_parse_args(
			$item,
			$old_item
		);

		if ( ! empty( $item['meta'] ) ) {

			foreach ( $item['meta'] as $meta_key => $meta_value ) {
				Plugin::instance()->db->appointments_meta->update( [
					'meta_value' => maybe_unserialize( $meta_value )
				], [
					'appointment_id' => $item_id,
					'meta_key'       => $meta_key,
				] );
			}

			unset( $item['meta'] );
			
		}

		Plugin::instance()->db->appointments->update( $item, array( 'ID' => $item_id ) );

		return rest_ensure_response( array(
			'success' => true,
		) );

	}

	/**
	 * Check user access to current end-popint
	 *
	 * @return bool
	 */
	public function permission_callback( $request ) {
		return Plugin::instance()->current_user_can( $this->get_name() );
	}

	/**
	 * Returns endpoint request method - GET/POST/PUT/DELTE
	 *
	 * @return string
	 */
	public function get_method() {
		return 'POST';
	}

	/**
	 * Get query param. Regex with query parameters
	 *
	 * @return string
	 */
	public function get_query_params() {
		return '(?P<id>[\d]+)';
	}

	public function get_local_date( $timestamp, $timezone, $format ) {

		$strdate                = date( 'Y-m-d H:i:s', $timestamp );
		$date_with_current_zone = date_create( $strdate, wp_timezone() );

		return wp_date( $format, $date_with_current_zone->format('U'), timezone_open( $timezone ) );

	}

}