<?php

namespace JET_ABAF;

use JET_ABAF\Vendor\Actions_Core\Base_Handler_Exception;

/**
 * @method setRequest( $key, $value )
 * @method getSettings()
 * @method hasGateway()
 * @method getRequest( $key = '', $ifNotExist = false )
 * @method issetRequest( $key )
 * @method getFieldSettingsByName( $field_name, $setting_name, $if_not_exist = false )
 *
 * Trait Apartment_Booking_Trait
 *
 * @package JET_ABAF
 */
trait Apartment_Booking_Trait {

	/**
	 * Run booking action.
	 *
	 * @since  2.8.0 Refactored. Remove additional columns handling.
	 * @since  3.7.0 Added `user_email` handling.
	 *
	 * @return array|void
	 * @throws Base_Handler_Exception
	 */
	public function run_action() {

		if ( 'plain' !== jet_abaf()->settings->get( 'booking_mode' ) ) {
			return;
		}

		$args = $this->getSettings();

		if ( empty( $args ) ) {
			return;
		}

		if ( empty( $args['booking_apartment_field'] ) || empty( $args['booking_dates_field'] ) ) {
			return;
		}

		$dates_field = $args['booking_dates_field'];

		if ( $this->issetRequest( $dates_field . '__in' ) ) {
			$key_in  = $dates_field . '__in';
			$key_out = $dates_field . '__out';
			$date_in = $this->getRequest( $key_in );

			if ( ! empty( $this->getRequest( $key_out ) ) ) {
				$date_out = $this->getRequest( $key_out );
			} else {
				$date_out = $date_in;
			}

			$dates = [ $date_in, $date_out ];
		} else {
			$dates = $this->getRequest( $dates_field );
			$dates = explode( ' - ', $dates );

			if ( 1 === count( $dates ) ) {
				$dates[] = $dates[0];
			}
		}

		if ( empty( $dates ) || 2 !== count( $dates ) ) {
			throw new Base_Handler_Exception( 'failed' );
		}

		$fields_separator = $this->getFieldSettingsByName( $dates_field, 'cio_fields_separator', '-' );
		$fields_separator = 'space' === $fields_separator ? ' ' : $fields_separator;

		$date_format = '!' . $this->getFieldSettingsByName( $dates_field, 'cio_fields_format', 'Y-m-d' );
		$date_format = jet_abaf()->tools->date_format_js_to_php( $date_format );

		if ( ! empty( $dates[0] ) ) {
			$dates[0] = str_replace( $fields_separator, '-', $dates[0] );
		}

		if ( ! empty( $dates[1] ) ) {
			$dates[1] = str_replace( $fields_separator, '-', $dates[1] );
		}

		$this->setRequest( '_check_in_date', $dates[0] );
		$this->setRequest( '_check_out_date', $dates[1] );

		$in_object  = \DateTime::createFromFormat( $date_format, $dates[0] );
		$out_object = \DateTime::createFromFormat( $date_format, $dates[1] );

		if ( ! $in_object || ! $out_object ) {
			$in  = strtotime( $dates[0] );
			$out = strtotime( $dates[1] );
		} else {
			$in  = $in_object->getTimestamp();
			$out = $out_object->getTimestamp();
		}

		if ( ! $in || ! $out ) {
			throw new Base_Handler_Exception( 'failed', '', $dates, $in, $out, $fields_separator );
		}

		$user_email = ! empty( $args['booking_email_field'] ) ? $this->getRequest( $args['booking_email_field'] ) : '';

		if ( empty( $user_email ) && is_user_logged_in() ) {
			$user       = wp_get_current_user();
			$user_email = $user->user_email ?? '';
		}

		$booking = [
			'status'         => 'pending',
			'apartment_id'   => $this->getRequest( $args['booking_apartment_field'] ),
			'check_in_date'  => $in,
			'check_out_date' => $out,
			'user_email'     => $user_email,
			'order_id'       => $this->getRequest( 'inserted_post_id' ) ? $this->getRequest( 'inserted_post_id' ) : null,
		];

		if ( jet_abaf()->settings->get( 'timepicker' ) ) {
			if ( empty( $_REQUEST['check-in-time'] ) || empty( $_REQUEST['check-out-time'] ) ) {
				throw new Base_Handler_Exception( __( 'Time is required.', 'jet-booking' ), 'error' );
			}

			$booking['check_in_time']  = $_REQUEST['check-in-time'];
			$booking['check_out_time'] = $_REQUEST['check-out-time'];
		}

		jet_abaf()->settings->hook_db_columns();

		if ( jet_abaf()->db->get_additional_db_columns() ) {
			foreach ( jet_abaf()->db->get_additional_db_columns() as $column ) {
				$data_key = $args[ 'db_columns_map_' . $column ] ?? false;

				if ( $data_key && $this->getRequest( $data_key ) ) {
					$custom_data = $this->getRequest( $data_key );

					if ( is_array( $custom_data ) ) {
						$custom_data = implode( ', ', $custom_data );
					}

					$booking[ $column ] = $custom_data;
				}
			}
		}

		$disable_wc_integration = ! empty( $args['disable_wc_integration'] ) ? filter_var( $args['disable_wc_integration'], FILTER_VALIDATE_BOOLEAN ) : false;

		if ( jet_abaf()->wc->mode && jet_abaf()->wc->mode->get_status() && jet_abaf()->wc->mode->get_product_id() && ! $disable_wc_integration ) {
			$booking['status'] = jet_abaf()->statuses->temporary_status();
		}

		if ( $this->hasGateway() ) {
			$booking['status'] = jet_abaf()->statuses->temporary_status();
		}

		if ( is_user_logged_in() ) {
			$booking['user_id'] = get_current_user_id();
		}

		// Allow custom booking processing.
		$pre_processed = apply_filters( 'jet-booking/form-action/pre-process', false, $booking, $this );

		if ( $pre_processed ) {
			return $pre_processed;
		}

		$booking_id = jet_abaf()->db->insert_booking( $booking );

		if ( $booking_id ) {
			$booking = jet_abaf()->db->inserted_booking;

			do_action( 'jet-booking/form-action/booking-inserted', $booking_id );
		} else {
			throw new Base_Handler_Exception( esc_html__( 'Booking dates already taken', 'jet-booking' ), 'error' );
		}

		$this->setRequest( 'booking_id', $booking_id );

		return $booking;

	}

}
