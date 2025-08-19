<?php
namespace JET_APB\DB;

use JET_APB\Plugin;
use JET_APB\Tools;

/**
 * Database manager class
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Define DB class
 */
class Excluded_Dates extends Base {

	public $dates_to_exclude = array();

	public function __construct()
	{
		parent::__construct();

		add_action( 'jet-apb/settings/after-ajax-save', array( $this, 'maybe_clear_excluded_dates' ), 10, 2 );
	}

	/**
	 * Return table slug
	 * 
	 * @return [type] [description]
	 */
	public function table_slug() {
		return 'appointments_excluded';
	}

	/**
	 * Returns columns schema
	 * @return [type] [description]
	 */
	public function schema() {
		return array(
			'ID'       => 'bigint(20) NOT NULL AUTO_INCREMENT',
			'service'  => 'text',
			'provider' => 'text',
			'date'     => 'bigint(20) NOT NULL',
		);
	}

	/**
	 * Create DB table for apartment units
	 *
	 * @return [type] [description]
	 */
	public function get_table_schema() {

		$charset_collate = $this->wpdb()->get_charset_collate();
		$table           = $this->table();
		$default_columns = $this->schema();
		$columns_schema  = '';

		foreach ( $default_columns as $column => $desc ) {
			$columns_schema .= $column . ' ' . $desc . ',';
		}

		return "CREATE TABLE $table (
			$columns_schema
			PRIMARY KEY (ID)
		) $charset_collate;";

	}

	/**
	 * Maybe remove date from excluded after changing working settings
	 *
	 * @param  [type] $post_meta [description]
	 * @param  [type] $settings  [description]
	 * @return [type]            [description]
	 */
	public static function maybe_clear_excluded_dates( $post_meta, $settings ) {

		global $wpdb;

		$id  = ! empty( $post_meta['ID'] ) ? $post_meta['ID'] : '';

		if ( $id ) {
			$old_schedule = $post_meta['custom_schedule'];
			$new_schedule = get_post_meta( $post_meta['ID'] , 'jet_apb_post_meta')[0]['custom_schedule'];
		} else {
			$old_schedule = $post_meta;
			$new_schedule = $settings->get_all() ;
		}

		$schedules_difference = Tools::array_diff_assoc_recursive( $new_schedule, $old_schedule );

		if ( ! empty( $schedules_difference ) && ( 'working_hours' == array_key_first( $schedules_difference ) || 'working_days' == array_key_first( $schedules_difference ) ) ) {

			$table = $wpdb->prefix . 'jet_appointments_excluded';
			$today = strtotime( date( 'd.m.Y' ) );
			$where = "date >= {$today}";
			
			if( ! empty( $id ) && is_numeric( $id ) ) {
				$where .= " AND ( service = '{$id}' OR provider = '{$id}' )";
			}

			$query = $wpdb->prepare("
				SELECT * 
				FROM %i
				WHERE $where
			", $table );

			$dates_to_check = $wpdb->get_results( $query );

			if( ! empty( $dates_to_check ) ) {
				foreach ( $dates_to_check as $date ) {
					Plugin::instance()->db->maybe_remove_excluded_app( ['service' => $date->service,
						'provider' => ! empty( $date->provider ) ? $date->provider : 0,
						'date' => $date->date,
					] );
				}
			}

		}

	}

	/**
	 * Maybe add appointment date to excluded
	 *
	 * @param  [type] $appointment [description]
	 * @return [type]              [description]
	 */
	public function maybe_exclude_appointment_date( $appointment, $exclude_other = true ) {

		if ( is_integer( $appointment ) ) {
			$appointment = Plugin::instance()->db->get_appointment_by( 'ID', $appointment );
		}

		if ( ! $appointment ) {
			return;
		}

		$service_id       = ! empty( $appointment['service'] ) ? $appointment['service'] : null;
		$provider_id      = ! empty( $appointment['provider'] ) ? $appointment['provider'] : null;
		$date             = ! empty( $appointment['date'] ) ? $appointment['date'] : null;
		$slot             = ! empty( $appointment['slot'] ) ? $appointment['slot'] : null;

		if ( ! $slot ) {
			return;
		}

		/**
		 * If status of current appointment shouldn't be excluded from calendar - 
		 * we don't need to do any more checks
		 */
		if ( ! in_array( $appointment['status'], Plugin::instance()->statuses->exclude_statuses() ) ) {
			return;
		}

		$manage_capacity = Plugin::instance()->settings->get( 'manage_capacity' );
		$all_slots       = Plugin::instance()->calendar->get_date_slots( $service_id, $provider_id, $date );
		$all_slots       = ! empty( $all_slots ) ? $all_slots : [];
		
		if ( $manage_capacity ) {
			
			$query_args = array(
				'date'     => $date,
				'status'   => Plugin::instance()->statuses->exclude_statuses(),
			);

			if ( $service_id ){
				$query_args['service'] = $service_id;
			}

			if ( $provider_id ) {
				$query_args['provider'] = $provider_id;
			}

			$capacity       = Plugin::instance()->db->appointments->query_with_capacity( $query_args );
			$total_capacity = Plugin::instance()->tools->get_service_count( $service_id );

			foreach ( $capacity as $capacity_slot ) {

				if ( intval( ( $capacity_slot['service'] ) ) === $appointment['service'] 
					&& intval( ( $capacity_slot['provider'] ) ) === $appointment['provider'] 
					&& intval( ( $capacity_slot['slot'] ) ) === $appointment['slot'] 
					&& true === $exclude_other ) {
						array_push( $this->dates_to_exclude, array( 'slot' => $capacity_slot['slot'], 'slot_count' => $capacity_slot['slot_count'] ) );
				}

			}

			if ( ! empty( $this->dates_to_exclude )  ) {
				foreach ( $this->dates_to_exclude as $excluded_value ) {
					if ( $excluded_value['slot_count'] >= $total_capacity ) {
						unset( $all_slots[ $excluded_value['slot'] ] );
					}
				}
			}

		} elseif ( ! empty( $all_slots ) && isset( $all_slots[ $slot ] ) ) {
			unset( $all_slots[ $slot ] );
		}

		if ( empty( $all_slots ) ) {
			$this->insert( array(
				'service'  => $service_id,
				'provider' => $provider_id,
				'date'     => $date,
			) );
		}

		$check_by = Plugin::instance()->settings->get( 'check_by' );

		if ( true === $exclude_other && 'global' === $check_by ) {
			$this->maybe_exclude_other_app( $appointment );
		}

	}

	public function maybe_exclude_other_app( $appointment ) {

		$providers_cpt = Plugin::instance()->settings->get( 'providers_cpt' );

		if ( ! empty( $providers_cpt ) ) {
			$services = array_flip( Plugin::instance()->tools->get_services_for_provider( $appointment['provider'] ) );
		} else {
			$services = Plugin::instance()->tools->get_posts( 'services', [
				'post_status'    => 'any',
				'posts_per_page' => -1
			] );
		}

		unset( $services[$appointment['service']] );

		if ( ! empty( $services ) ) {
			foreach ( $services as $service_id => $service_name ) {
				$appointment['service'] = $service_id;
				$this->maybe_exclude_appointment_date( $appointment, false );
			}
		}
		
	}

	public function maybe_remove_excluded_app( $appointment ) {

		if ( 0 != $appointment['provider'] ) {
			foreach ( Plugin::instance()->tools->get_services_for_provider( $appointment['provider'] ) as $service_id ) {
				if( ! Plugin::instance()->settings->providers_slot_duplicating() ) {
					foreach (  Plugin::instance()->tools->get_providers_for_service( $service_id ) as $provider_id ) {
						if ( Plugin::instance()->settings->check_date_availability( intval( $service_id ), intval( $provider_id->ID ), intval( $appointment['date'] ) ) ) {
							$this->delete( [
								'service'  => $service_id,
								'provider' => $provider_id->ID,
								'date'     => $appointment['date'],
							] );
						}
					}
				} else {
					if ( Plugin::instance()->settings->check_date_availability( intval( $service_id ), intval( $appointment['provider'] ), intval( $appointment['date'] ) ) ) {
						$this->delete( [
							'service'  => $service_id,
							'provider' => $appointment['provider'],
							'date'     => $appointment['date'],
						] );
					}
				}

			}
		} else {
			$services = Plugin::instance()->tools->get_posts( 'services', [
				'post_status'    => 'any',
				'posts_per_page' => -1
			] );
			foreach ( $services as $service_id => $service_value ) {
				if ( Plugin::instance()->settings->check_date_availability( intval( $service_id ), intval( $appointment['provider'] ), intval( $appointment['date'] ) ) ) {
					$this->delete( [
						'service'  => $service_id,
						'date'     => $appointment['date'],
					] );
				}
			}
		}

	}

}
