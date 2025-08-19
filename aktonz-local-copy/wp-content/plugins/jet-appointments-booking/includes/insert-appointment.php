<?php
namespace JET_APB;

use JET_APB\Resources\Appointment_Collection;
use JET_APB\Vendor\Actions_Core\Base_Handler_Exception;
use JET_APB\Time_Types;
use Jet_Form_Builder\Exceptions\Action_Exception;

/**
 * @method setRequest( $key, $value )
 * @method getSettings( $key = '', $ifNotExist = false )
 * @method hasGateway()
 * @method getRequest( $key = '', $ifNotExist = false )
 * @method issetRequest( $key )
 *
 * Trait Insert_Appointment
 * @package JET_APB
 */
trait Insert_Appointment {

	/**
	 * Check if appointment has count option and count > 1, 
	 * add these counts as separate appointments
	 * @return [type] [description]
	 */
	public function extract_appointments_by_count( $appointments ) {

		$prepared_appointments = [];

		foreach ( $appointments as $app ) {

			$app = ( array ) $app;
			$prepared_appointments[] = $app;
			
			// todo - ensure we keep in max allowed count
			if ( ! empty( $app['count'] ) && 1 < absint( $app['count'] ) ) {
				$count_to_add = absint( $app['count'] ) - 1;
				for ( $i=0; $i < $count_to_add; $i++ ) { 
					$prepared_appointments[] = $app;
				}
			}
		}

		return $prepared_appointments;
	}

	/**
	 * @return array
	 * @throws Base_Handler_Exception
	 */
	public function run_action() {
		
		$args               = $this->getSettings();
		$data               = $this->getRequest();
		$appointments_field = ! empty( $args['appointment_date_field'] ) ? $args['appointment_date_field'] : false;
		$appointments       = ! empty( $data[ $appointments_field ] ) ? json_decode( wp_specialchars_decode( stripcslashes( $data[ $appointments_field ] ), ENT_COMPAT ), true ) : false;

		$appointments = $this->extract_appointments_by_count( $appointments );

		$multi_booking      = Plugin::instance()->settings->get( 'multi_booking' );
		$min_slot_count     = Plugin::instance()->settings->get( 'min_slot_count' );
		$appointments_count = count( $appointments );

		if ( $multi_booking && $appointments_count < $min_slot_count ) {
			throw new Base_Handler_Exception( sprintf( Plugin::instance()->settings->get_custom_label( 'minSlotCount', __( 'Sorry. You have not selected enough slots, minimum quantity: %s', 'jet-appointments-booking' ) ), $min_slot_count ), 'error' );
		}

		if ( ! $this->appointment_available( $appointments ) ) {
			throw new Base_Handler_Exception( $this->error_message, 'error' );
		}

		$email_field = ! empty( $args['appointment_email_field'] ) ? $args['appointment_email_field'] : false;
		$email       = ! empty( $data[ $email_field ] ) ? sanitize_email( $data[ $email_field ] ) : false;

		if ( ! $appointments || ! $email || ! is_email( $email ) ) {
			throw new Base_Handler_Exception( 'failed', '', $email_field );
		}

		$name_field = ! empty( $args['appointment_name_field'] ) ? $args['appointment_name_field'] : false;
		$name       = '';

		if ( $name_field && '_use_current_user' === $name_field ) {
			$user_prop = apply_filters( 'jet-apb/form-action/user-name-prop', 'user_login' );
			$guest_name = apply_filters( 'jet-apb/form-action/user-name-guest', __( 'Guest', 'jet-appointments-booking' ) );
			$name = ( is_user_logged_in() && isset( wp_get_current_user()->$user_prop ) ) ? wp_get_current_user()->$user_prop : $guest_name;
		} elseif ( $name_field && '_use_current_user' !== $name_field ) {
			$name = ! empty( $data[ $name_field ] ) ? $data[ $name_field ] : '';
		}

		$group_ID            = $multi_booking && $appointments_count > 1 ? Plugin::instance()->db->appointments->get_max_int( 'group_ID' ) + 1 : null;
		$appointment_id_list = array();
		$parent_appointment  = false;

		if ( $appointments_count > 1 ) {
			usort(
				$appointments,
				function ( $item_1, $item_2 ) {
					
					if ( is_array( $item_1 ) ) {
						return ( $item_1['slot'] < $item_2['slot'] ) ? 1 : - 1;
					} else {
						return ( $item_1->slot < $item_2->slot ) ? 1 : - 1;
					}
					
				}
			);
		}

		$collection = new Appointment_Collection( $this );
		$collection->set_group_ID( $group_ID );
		$collection->set_user_email( $email );
		$collection->set_user_name( $name );

		foreach ( $appointments as $key => $appointment ) {
			
			$single = $collection->add()->set_from_request( $appointment );

			if ( ! empty( $appointment['timezone'] ) ) {
				$single->set_meta( [
					'user_local_time' => $appointment['friendlyTime'],
					'user_local_date' => $appointment['friendlyDate'],
					'user_timezone'   => $appointment['timezone'],
				] );
			}

			do_action_ref_array( 'jet-apb/form-action/insert-appointment', [
				&$single,
				$this
			] );

			$appointment_id = Plugin::instance()->db->add_appointment( $single->to_db_array() );
			$single->set_ID( $appointment_id );

			$appointment_id_list[] = $appointment_id;

			$appointments[ $key ] = $single->to_array();

			if ( ! $parent_appointment ) {
				$parent_appointment = $appointments[ $key ];
			}
		}

		/**
		 * Check if group id exist or enabled multi booking
		 * To trigger even if one slot is booked with multiplay booking
		 * https://github.com/Crocoblock/issues-tracker/issues/11472
		 */
		if ( $group_ID || $multi_booking ) {
			do_action( 'jet-apb/form-action/insert-appointments-group', array_values( $appointments ), $collection );
		}

		$this->setRequest( 'appointment_id', $parent_appointment['ID'] );
		$this->setRequest( 'appointment_id_list', $appointment_id_list );

		return $appointments;
	}

	public function appointment_available( $appointments ) {
		$notification_log = true;

		foreach ( $appointments as $appointment ) {
			$appointment = $this->parse_field( $appointment );

			if ( ! Time_Types::is_allowed_time( $appointment ) ) {
				$this->error_message = Plugin::instance()->settings->get_custom_label( 'timeNotAllowedToBook', __( 'Selected time is not allowed to book', 'jet-appointments-booking' ) );
				$notification_log = false;
				break;
			}

			if ( ! Plugin::instance()->db->appointment_available( $appointment ) ) {
				$this->error_message = Plugin::instance()->settings->get_custom_label( 'timeAlreadyTaken', __( 'Appointment time already taken', 'jet-appointments-booking' ) );
				$notification_log = false;
				break;
			}
		}

		return $notification_log;
	}

	public function parse_field( $appointment ) {
		$db_columns = Plugin::instance()->settings->get( 'db_columns' );

		foreach ( $appointment as $field => $value ) {
			switch ( $field ) {
				case 'slotEnd':
					unset( $appointment[ $field ] );
					$field = 'slot_end';
					$value = intval( $value );
					break;

				case 'date':
				case 'slot':
				case 'provider':
				case 'service':
					$value = intval( $value );
					break;
				case 'count': 
					$value = intval( $value );
					break;
				default:
					$value = null;
					unset( $appointment[ $field ] );
					break;
			}

			if ( null !== $value ) {
				$appointment[ $field ] = $value;
			}
		}

		$appointment['type'] = Plugin::instance()->settings->get( 'booking_type' );

		if ( ! empty( $db_columns ) ) {
			$args = $this->getSettings();
			$data = $this->getRequest();

			foreach ( $db_columns as $column ) {
				if ( ! empty( $appointment[ $column ] ) ) {
					continue;
				}

				$custom_field           = 'appointment_custom_field_' . $column;
				$field_name             = ! empty( $args[ $custom_field ] ) ? $args[ $custom_field ] : false;
				$appointment[ $column ] = ! empty( $data[ $field_name ] ) ? esc_attr( $data[ $field_name ] ) : '';
			}
		}

		return $appointment;
	}


}
