<?php

namespace JET_ABAF\DB;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Manager {

	/**
	 * Bookings db table instance holder.
	 *
	 * @var Tables\Bookings
	 */
	public $bookings;

	/**
	 * Bookings meta db table instance holder.
	 *
	 * @var Tables\Bookings_Meta
	 */
	public $bookings_meta;

	/**
	 * Units db table instance holder.
	 *
	 * @var Tables\Units
	 */
	public $units;

	/**
	 * Stores latest inserted booking item.
	 *
	 * @var array
	 */
	public $inserted_booking = false;

	/**
	 * Stores latest queried result to use it.
	 *
	 * @var null
	 */
	public $latest_result = null;

	public function __construct() {

		$this->bookings      = new Tables\Bookings();
		$this->bookings_meta = new Tables\Bookings_Meta();
		$this->units         = new Tables\Units();

		// Check available unit count for booking instance post type action.
		add_action( 'wp_ajax_jet_booking_check_available_units_count', [ $this, 'check_available_units_count' ] );
		add_action( 'wp_ajax_nopriv_jet_booking_check_available_units_count', [ $this, 'check_available_units_count' ] );

	}

	/**
	 * Install table.
	 *
	 * Try to recreate DB tables by request.
	 *
	 * @since 3.7.2 Added booking meta table.
	 *
	 * @return void
	 */
	public function install_table() {

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$this->bookings->install_table();
		$this->bookings_meta->install_table();
		$this->units->install_table();

	}

	/**
	 * Returns WPDB instance.
	 *
	 * @return \QM_DB|\wpdb
	 */
	public static function wpdb() {
		global $wpdb;

		return $wpdb;
	}

	/**
	 * Returns bookings table name.
	 *
	 * @since  3.3.0 Refactored.
	 *
	 * @return string
	 */
	public static function bookings_table() {
		return jet_abaf()->db->bookings->table();
	}

	/**
	 * Returns units table name.
	 *
	 * @since  3.3.0 Refactored.
	 *
	 * @return string
	 */
	public static function units_table() {
		return jet_abaf()->db->units->table();
	}

	/**
	 * Check if booking table already exists.
	 *
	 * @since 3.3.0 Refactored.
	 *
	 * @return boolean
	 */
	public function is_bookings_table_exists() {
		return $this->bookings->is_table_exists();
	}

	/**
	 * Check if units table already exists.
	 *
	 * @since 3.3.0 Refactored.
	 *
	 * @return boolean
	 */
	public function is_units_table_exists() {
		return $this->units->is_table_exists();
	}

	/**
	 * Check if all required DB tables are exists.
	 *
	 * @since 3.3.0 Refactored.
	 *
	 * @return boolean
	 */
	public function tables_exists() {
		return $this->bookings->is_table_exists() && $this->units->is_table_exists() && $this->bookings_meta->is_table_exists();
	}

	/**
	 * Get default fields.
	 *
	 * Returns default database fields list.
	 *
	 * @since  2.8.0 Added `order_id`, `import_id` fields.
	 * @since  3.3.0 Added `user_id` field.
	 * @since  3.7.0 Added `user_email`, `check_in_time` and `check_out_time` fields.
	 *
	 * @return string[]
	 */
	public function get_default_fields() {
		return [
			'booking_id',
			'status',
			'apartment_id',
			'apartment_unit',
			'check_in_date',
			'check_in_time',
			'check_out_date',
			'check_out_time',
			'user_id',
			'user_email',
			'order_id',
			'import_id',
		];
	}

	/**
	 * Returns additional DB fields.
	 *
	 * @since 3.3.0 Refactored.
	 *
	 * @return array
	 */
	public function get_additional_db_columns() {
		return apply_filters( 'jet-abaf/db/additional-db-columns', [] );
	}

	/**
	 * Create booking table.
	 *
	 * Create database table for tracked information.
	 *
	 * @since  2.8.0 Refactored
	 * @since  3.3.0 Refactored
	 */
	public function create_bookings_table( $delete_if_exists = false ) {
		$this->bookings->create_table( $delete_if_exists );
	}

	/**
	 * Create units table.
	 *
	 * Create database table for tracked information.
	 *
	 * @since  3.3.0 Refactored
	 */
	public function create_units_table( $delete_if_exists = false ) {
		$this->units->create_table( $delete_if_exists );
	}

	/**
	 * Get initial apartment id.
	 *
	 * Returns initial booking apartment ID.
	 *
	 * @since  2.5.5
	 *
	 * @param int|string $id Apartment post type ID.
	 *
	 * @return mixed|void
	 */
	public function get_initial_booking_item_id( $id ) {
		return apply_filters( 'jet-abaf/db/initial-apartment-id', $id );
	}

	/**
	 * Returns all available units for apartment.
	 *
	 * @param int|string $apartment_id Booking instance post type ID.
	 *
	 * @return mixed
	 */
	public function get_apartment_units( $apartment_id ) {
		return $this->query( [ 'apartment_id' => $apartment_id ], $this->units->table() );
	}

	/**
	 * Get booked units.
	 *
	 * Return list of apartment booked units for passed dates.
	 *
	 * @since  2.5.2
	 *
	 * @param array $booking Bookings parameters.
	 *
	 * @return array
	 */
	public function get_booked_units( $booking ) {

		$table        = $this->bookings->table();
		$apartment_id = $booking['apartment_id'];
		$from         = $booking['check_in_date'];
		$to           = $booking['check_out_date'];

		return self::wpdb()->get_results( "
			SELECT *
			FROM `{$table}`
			WHERE `apartment_id` = $apartment_id
			AND (
				( `check_in_date` >= $from AND `check_in_date` <= $to )
				OR ( `check_out_date` > $from AND `check_out_date` <= $to )
				OR ( `check_in_date` < $from AND `check_out_date` >= $to )
			)
		", ARRAY_A );

	}

	/**
	 * Get available unit.
	 *
	 * Returns available unit for passed dates.
	 *
	 * @since  1.0.0
	 * @since  2.5.2 Move some logic to `get_booked_units()`.
	 *
	 * @param array $booking Bookings parameters.
	 *
	 * @return mixed|null
	 */
	public function get_available_unit( $booking ) {

		$all_units = $this->get_apartment_units( $booking['apartment_id'] );

		if ( empty( $all_units ) ) {
			return null;
		}

		$booked_units = $this->get_booked_units( $booking );

		if ( empty( $booked_units ) ) {
			return $all_units[0]['unit_id'];
		}

		$skip_statuses   = jet_abaf()->statuses->invalid_statuses();
		$skip_statuses[] = jet_abaf()->statuses->temporary_status();

		foreach ( $all_units as $unit ) {
			$found = false;

			foreach ( $booked_units as $booked_unit ) {
				if ( ! isset( $booked_unit['status'] ) || ! in_array( $booked_unit['status'], $skip_statuses ) ) {
					if ( absint( $unit['unit_id'] ) === absint( $booked_unit['apartment_unit'] ) ) {
						$found = true;
					}
				}
			}

			if ( ! $found ) {
				return $unit['unit_id'];
			}
		}

		return null;

	}

	/**
	 * Get booked items.
	 *
	 * Returns list of booked items.
	 *
	 * @since  2.7.1
	 *
	 * @param array $booking Bookings data.
	 *
	 * @return array|object|\stdClass[]|null
	 */
	public function get_booked_items( $booking ) {

		$table          = $this->bookings->table();
		$apartment_id   = $booking['apartment_id'];
		$apartment_unit = $booking['apartment_unit'] ?? '';
		$from           = $booking['check_in_date'];
		$to             = $booking['check_out_date'];

		// Increase $from to 1 to avoid overlapping check-in and check-out dates.
		$from ++;

		$query = "
			SELECT *
			FROM $table
			WHERE (
				`check_in_date` BETWEEN $from AND $to
				OR `check_out_date` BETWEEN $from AND $to
				OR ( `check_in_date` <= $from AND `check_out_date` >= $to )
			) AND `apartment_id` = $apartment_id
		";

		if ( ! empty( $apartment_unit ) ) {
			$query .= " AND `apartment_unit` = $apartment_unit";
		}

		$query .= ";";

		$booked          = self::wpdb()->get_results( $query, ARRAY_A );
		$skip_statuses   = jet_abaf()->statuses->invalid_statuses();
		$skip_statuses[] = jet_abaf()->statuses->temporary_status();

		foreach ( $booked as $index => $booking ) {
			if ( ! empty( $booking['status'] ) && in_array( $booking['status'], $skip_statuses ) ) {
				unset( $booked[ $index ] );
			}
		}

		return $booked;

	}

	/**
	 * Is booking dates available.
	 *
	 * Check if current booking dates is available.
	 *
	 * @since  2.7.1 Refactored.
	 *
	 * @param array         $booking    Booking data.
	 * @param number|string $booking_id Booking ID.
	 *
	 * @return boolean
	 */
	public function is_booking_dates_available( $booking = [], $booking_id = 0 ) {

		$booked = $this->get_booked_items( $booking );

		if ( empty( $booked ) ) {
			return true;
		}

		foreach ( $booked as $index => $booking ) {
			if ( absint( $booking['booking_id'] ) === absint( $booking_id ) ) {
				unset( $booked[ $index ] );
			}
		}

		if ( empty( $booked ) ) {
			return true;
		}

		return false;

	}

	/**
	 * Insert booking.
	 *
	 * @since  2.1.0
	 * @since  2.5.5 Added additional `apartment_id` handling.
	 * @since  3.0.0 Fixed numeric column name handling.
	 * @since  3.7.0 Refactored.
	 *
	 * @param array $booking List of parameters.
	 *
	 * @return false|int
	 */
	public function insert_booking( $booking = [] ) {

		$booking['apartment_id'] = $this->get_initial_booking_item_id( $booking['apartment_id'] );

		if ( $booking['check_in_date'] >= $booking['check_out_date'] ) {
			$booking['check_out_date'] = $booking['check_in_date'] + 12 * HOUR_IN_SECONDS;
		}

		$booking['check_in_date'] ++;

		if ( empty( $booking['apartment_unit'] ) ) {
			$booking['apartment_unit'] = $this->get_available_unit( $booking );
		}

		if ( isset( $booking['__guests'] ) ) {
			unset( $booking['__guests'] );
		}

		if ( isset( $booking['attributes'] ) ) {
			unset( $booking['attributes'] );
		}

		if ( ! $this->is_booking_dates_available( $booking ) ) {
			return false;
		}

		$booking_id = $this->bookings->insert( $booking );

		$booking['booking_id'] = $booking_id;

		$this->inserted_booking = $booking;

		// Trigger hook after booking inserted.
		do_action( 'jet-booking/db/booking-inserted', $booking );

		return $booking_id;

	}

	/**
	 * Update booking.
	 *
	 * Update booking information in database.
	 *
	 * @since  2.7.0 Added `'jet-booking/db/booking-updated'` hook.
	 *
	 * @param string|int $booking_id Booking ID.
	 * @param array      $data       Booking item data.
	 *
	 * @return void
	 */
	public function update_booking( $booking_id = 0, $data = [] ) {
		$this->bookings->update( $data, [ 'booking_id' => $booking_id ] );
		do_action( 'jet-booking/db/booking-updated', $booking_id );
	}

	/**
	 * Delete booking.
	 *
	 * Delete booking by passed parameters.
	 *
	 * @since  3.0.0 Added `'jet-booking/db/before-booking-delete'` hook.
	 * @since  3.3.0 Delete related meta.
	 *
	 * @param array $where Delete parameters.
	 *
	 * @return void
	 */
	public function delete_booking( $where = [] ) {

		do_action( 'jet-booking/db/before-booking-delete', $where );

		$bookings = $this->query( $where, $this->bookings->table() );

		if ( ! empty( $bookings ) ) {
			$this->bookings_meta->delete( [ 'booking_id' => $bookings[0]['booking_id'] ] );
		}

		$this->bookings->delete( $where );

	}

	/**
	 * Insert table columns.
	 *
	 * Insert new columns into existing bookings table
	 *
	 * @since  3.0.0 Added backticks for numeric columns name handling.
	 * @since  3.3.0 Added column description handling.
	 * @since  3.7.0 Refactored.
	 *
	 * @param array $columns List of columns to insert.
	 *
	 * @return void
	 */
	public function insert_table_columns( $columns = [] ) {

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( empty( $columns ) ) {
			return;
		}

		$table          = $this->bookings->table();
		$columns_schema = '';

		foreach ( $columns as $column => $desc ) {
			$desc           = $desc ?: 'text';
			$columns_schema .= "ADD `$column` $desc, ";
		}

		$columns_schema = rtrim( $columns_schema, ', ' );
		$query          = "ALTER TABLE `$table` $columns_schema;";

		self::wpdb()->query( $query );

	}

	/**
	 * Update database with new columns.
	 *
	 * @param array $new_columns List of column names.
	 *
	 * @return false|void
	 */
	public function update_columns_diff( $new_columns = [] ) {

		$table   = $this->bookings->table();
		$columns = self::wpdb()->get_results( "SHOW COLUMNS FROM $table", ARRAY_A );

		if ( empty( $columns ) ) {
			return false;
		}

		$default_columns  = $this->get_default_fields();
		$existing_columns = [];

		foreach ( $columns as $column ) {
			if ( ! in_array( $column['Field'], $default_columns ) ) {
				$existing_columns[] = $column['Field'];
			}
		}

		if ( empty( $new_columns ) && empty( $existing_columns ) ) {
			return;
		}

		$to_delete = array_diff( $existing_columns, $new_columns );
		$to_add    = array_diff( $new_columns, $existing_columns );

		if ( ! empty( $to_delete ) ) {
			$this->delete_table_columns( $to_delete );
		}

		if ( ! empty( $to_add ) ) {
			$columns_to_add = [];

			foreach ( $to_add as $column ) {
				$columns_to_add[ $column ] = 'text';
			}

			$this->insert_table_columns( $columns_to_add );
		}

	}

	/**
	 * Delete table columns.
	 *
	 * Delete columns into existing bookings table
	 *
	 * @since  3.0.0 Added backticks for numeric columns name handling.
	 *
	 * @param array $columns List of columns to delete.
	 *
	 * @return void
	 */
	public function delete_table_columns( $columns ) {

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$columns_schema = '';

		foreach ( $columns as $column ) {
			$columns_schema .= "DROP COLUMN `$column`, ";
		}

		$columns_schema = rtrim( $columns_schema, ', ' );
		$table          = $this->bookings->table();
		$sql            = "ALTER TABLE $table $columns_schema;";

		self::wpdb()->query( $sql );

	}

	/**
	 * Check if booking DB column is exists.
	 *
	 * @param string $column Column name.
	 *
	 * @return bool|int|\mysqli_result
	 */
	public function column_exists( $column ) {
		$table = $this->bookings->table();

		return self::wpdb()->query( "SHOW COLUMNS FROM `$table` LIKE '$column'" );
	}

	/**
	 * Check if booking already exists.
	 *
	 * @since 3.3.0 Fixed default parameter value.
	 *
	 * @param string $by_field Column name.
	 * @param null   $value    Column value.
	 *
	 * @return bool
	 */
	public function booking_exists( $by_field = 'booking_id', $value = null ) {
		$count = $this->count( [ $by_field => $value ] );

		return ! empty( $count );
	}

	/**
	 * Get future bookings.
	 *
	 * Returns future bookings for apartment ID (or all future bookings if apartment ID is not passed).
	 *
	 * @since 3.3.0 Refactored.
	 *
	 * @param int|string $apartment_id Booking instance post type ID.
	 *
	 * @return array|object
	 */
	public function get_future_bookings( $apartment_id = null ) {

		$args = [
			'date_query' => [
				[
					'column'   => 'check_out_date',
					'operator' => '>=',
					'value'    => 'today'
				]
			],
			'return'     => 'arrays'
		];

		if ( $apartment_id ) {
			$args['apartment_id'] = $apartment_id;
		}

		return jet_abaf_get_bookings( $args );

	}

	/**
	 * Returns booking details by passed field and value.
	 *
	 * @since 3.3.0 Refactored.
	 *
	 * @param string $field Database column name.
	 * @param null   $value Database column value.
	 *
	 * @return false|mixed|\stdClass
	 */
	public function get_booking_by( $field = 'booking_id', $value = null ) {

		$bookings = $this->query( [ $field => $value ], $this->bookings->table() );

		if ( empty( $bookings ) ) {
			return false;
		}

		return reset( $bookings );

	}

	/**
	 * Get booked apartments.
	 *
	 * Get already booked apartments for passed dates.
	 *
	 * @since  1.0.0
	 * @since  2.5.2 Added compatibility with checkout only option.
	 *
	 * @param string $from Booking start date.
	 * @param string $to   Booking end date.
	 *
	 * @return array
	 */
	public function get_booked_apartments( $from, $to ) {

		$table       = $this->bookings->table();
		$units_table = $this->units->table();

		// Increase $from to 1 to avoid overlapping check-in and check-out dates.
		$from ++;

		$skip_statuses   = jet_abaf()->statuses->invalid_statuses();
		$skip_statuses[] = jet_abaf()->statuses->temporary_status();

		$skip_statuses = implode( ', ', array_map( function ( $item ) {
			return '"' . trim( $item ) . '"';
		}, $skip_statuses ) );

		$booked = self::wpdb()->get_results( "
			SELECT apartment_id AS `apartment_id`, count( * ) AS `units`, check_in_date AS `check_in_date`
			FROM $table
			WHERE ( `check_in_date` BETWEEN $from AND $to
			OR `check_out_date` BETWEEN $from AND $to
			OR ( `check_in_date` <= $from AND `check_out_date` >= $to ) )
			AND `status` NOT IN ( $skip_statuses )
			GROUP BY apartment_id;
		", ARRAY_A );

		if ( empty( $booked ) ) {
			return [];
		}

		$available = self::wpdb()->get_results( "
			SELECT apartment_id AS `apartment_id`, count( * ) AS `units`
			FROM $units_table
			GROUP BY apartment_id;
		", ARRAY_A );

		if ( ! empty( $available ) ) {
			$tmp = [];

			foreach ( $available as $row ) {
				$tmp[ $row['apartment_id'] ] = $row['units'];
			}

			$available = $tmp;
		} else {
			$available = [];
		}

		$result = [];

		foreach ( $booked as $apartment ) {
			$ap_id = $apartment['apartment_id'];

			if ( jet_abaf()->settings->checkout_only_allowed( $ap_id ) ) {
				if ( date( 'Y-m-d', $to ) === date( 'Y-m-d', $apartment['check_in_date'] ) ) {
					$available[ $ap_id ] = ! empty( $available[ $ap_id ] ) ? $available[ $ap_id ] : 1;
					$apartment['units']  = 0;
				}
			}

			if ( empty( $available[ $ap_id ] ) ) {
				$result[] = $ap_id;
			} else {
				$booked          = absint( $apartment['units'] );
				$available_units = absint( $available[ $ap_id ] );

				if ( $booked >= $available_units ) {
					$result[] = $ap_id;
				}
			}
		}

		return $result;

	}

	/**
	 * Booking availability.
	 *
	 * Check if is booking instance available for new bookings.
	 *
	 * @since  2.5.0
	 * @since  2.7.1 Refactored.
	 *
	 * @param array         $booking    Booking data.
	 * @param number|string $booking_id Booking ID.
	 *
	 * @return bool
	 */
	public function booking_availability( $booking = [], $booking_id = 0 ) {

		$booked = $this->get_booked_items( $booking );

		if ( empty( $booked ) ) {
			return true;
		}

		$this->latest_result = $booked;

		$units_table    = $this->units->table();
		$apartment_id   = $booking['apartment_id'];
		$apartment_unit = $booking['apartment_unit'] ?? '';
		$count          = 0;
		$booked_units   = [];

		foreach ( $booked as $item ) {
			if ( absint( $item['booking_id'] ) === absint( $booking_id ) || in_array( absint( $item['apartment_unit'] ), $booked_units ) ) {
				continue;
			}

			if ( absint( $item['apartment_unit'] ) === absint( $apartment_unit ) ) {
				return false;
			}

			$booked_units[] = absint( $item['apartment_unit'] );

			$count ++;
		}

		$available = self::wpdb()->get_results( "
			SELECT apartment_id AS `apartment_id`, count( * ) AS `units`
			FROM $units_table
			WHERE `apartment_id` = $apartment_id
			GROUP BY apartment_id;
		", ARRAY_A );

		if ( empty( $available ) && 0 < $count ) {
			return false;
		}

		if ( empty( $available ) && 0 === $count ) {
			return true;
		}

		if ( $count >= absint( $available[0]['units'] ) ) {
			return false;
		}

		return true;

	}

	/**
	 * Update unit.
	 *
	 * @param int|string $unit_id Booking unit ID.
	 * @param array      $data    Data to update
	 */
	public function update_unit( $unit_id, $data ) {
		$this->units->update( $data, [ 'unit_id' => $unit_id ] );
	}

	/**
	 * Delete unit by passed parameters.
	 *
	 * @param array $where Delete parameters.
	 */
	public function delete_unit( $where = [] ) {
		$this->units->delete( $where );
	}

	/**
	 * Returns all available units for apartment.
	 *
	 * @param int|string $apartment_id Booking instance post type ID.
	 * @param int|string $unit_id      Booking instance post type unit ID.
	 *
	 * @return array|object|\stdClass[]|null
	 */
	public function get_apartment_unit( $apartment_id, $unit_id ) {
		return $this->query( [ 'apartment_id' => $apartment_id, 'unit_id' => $unit_id, ], $this->units->table() );
	}

	/**
	 * Get available units.
	 *
	 * Returns the list of available units for passed/selected dates.
	 *
	 * @since  2.5.2
	 *
	 * @param array $booking Booking parameters list.
	 *
	 * @return array|object|\stdClass[]|null
	 */
	public function get_available_units( $booking ) {

		$all_units = $this->get_apartment_units( $booking['apartment_id'] );

		if ( empty( $all_units ) ) {
			return null;
		}

		$booked_units = $this->get_booked_units( $booking );

		if ( empty( $booked_units ) ) {
			return $all_units;
		}

		$skip_statuses   = jet_abaf()->statuses->invalid_statuses();
		$skip_statuses[] = jet_abaf()->statuses->temporary_status();

		foreach ( $all_units as $key => $unit ) {
			foreach ( $booked_units as $booked_unit ) {
				if ( ! isset( $booked_unit['status'] ) || ! in_array( $booked_unit['status'], $skip_statuses ) ) {
					if ( absint( $unit['unit_id'] ) === absint( $booked_unit['apartment_unit'] ) ) {
						unset( $all_units[ $key ] );
					}
				}
			}
		}

		return $all_units;

	}

	/**
	 * Check available units count.
	 *
	 * Check available units count for passed/selected dates.
	 *
	 * @since  2.5.2
	 * @since  3.2.1 Refactored.
	 */
	public function check_available_units_count() {

		$booking   = $_POST['booking'] ?? [];
		$all_units = $this->get_apartment_units( $booking['apartment_id'] );

		if ( empty( $all_units ) || empty( $booking['check_in_date'] ) || empty( $booking['check_out_date'] ) ) {
			wp_send_json_error();
		}

		if ( $booking['check_in_date'] === $booking['check_out_date'] ) {
			$booking['check_out_date'] += 12 * HOUR_IN_SECONDS;
		}

		if ( jet_abaf()->settings->is_per_nights_booking( $booking['apartment_id'] ) ) {
			$booking['check_in_date'] ++;
		}

		$booking['check_out_date'] ++;

		$booked_units = $this->get_booked_units( $booking );

		if ( empty( $booked_units ) ) {
			wp_send_json_success( [ 'count' => count( $all_units ) ] );
		} elseif ( count( $booked_units ) >= count( $all_units ) && jet_abaf()->settings->checkout_only_allowed( $booking['apartment_id'] ) ) {
			$booking['check_out_date'] --;
		}

		wp_send_json_success( [ 'count' => count( $this->get_available_units( $booking ) ) ] );

	}

	/**
	 * Prepare params.
	 *
	 * Return prepared list of parameters to use in query.
	 *
	 * @since  3.2.0
	 * @access public
	 *
	 * @param $params
	 *
	 * @return array
	 */
	public function prepare_params( $params ) {

		$mode = ! empty( $params['mode'] ) ? $params['mode'] : 'all';
		$view = ! empty( $params['view'] ) ? $params['view'] : 'list';
		$args = ! empty( $params['filters'] ) ? json_decode( $params['filters'], true ) : [];
		$args = ! empty( $args ) && is_array( $args ) ? array_filter( $args ) : [];
		$sort = ! empty( $params['sort'] ) ? json_decode( $params['sort'], true ) : [];

		$args['limit']     = ! empty( $params['per_page'] ) ? absint( $params['per_page'] ) : 0;
		$args['offset']    = ! empty( $params['offset'] ) ? absint( $params['offset'] ) : 0;
		$args['sorting'][] = ! empty( $sort ) && is_array( $sort ) ? array_filter( $sort ) : [
			'orderby' => 'booking_id',
			'order'   => 'DESC',
		];

		switch ( $mode ) {
			case 'upcoming':
				$args['date_query'][] = [
					'column'   => 'check_in_date',
					'operator' => '>=',
					'value'    => 'today'
				];

				break;

			case 'past':
				$args['date_query'][] = [
					'column'   => 'check_in_date',
					'operator' => '<',
					'value'    => 'today'
				];

				break;
		}

		if ( 'list' === $view ) {
			if ( ! empty( $args['check_in_date'] ) && ! empty( $args['check_out_date'] ) ) {
				$args['date_query'][] = [
					'column'   => 'check_in_date',
					'operator' => '>=',
					'value'    => $args['check_in_date']
				];
				$args['date_query'][] = [
					'column'   => 'check_out_date',
					'operator' => '<=',
					'value'    => strtotime( $args['check_out_date'] ) + 12 * HOUR_IN_SECONDS
				];

				unset( $args['check_in_date'] );
				unset( $args['check_out_date'] );
			}

			if ( ! empty( $args['check_in_date'] ) ) {
				$args['date_query'][] = [
					'column'   => 'check_in_date',
					'operator' => '=',
					'value'    => $args['check_in_date']
				];

				unset( $args['check_in_date'] );
			}

			if ( ! empty( $args['check_out_date'] ) ) {
				$args['date_query']['relation'] = 'OR';

				$args['date_query'][] = [
					'column'   => 'check_out_date',
					'operator' => '=',
					'value'    => $args['check_out_date']
				];
				$args['date_query'][] = [
					'column'   => 'check_out_date',
					'operator' => '=',
					'value'    => strtotime( $args['check_out_date'] ) + 12 * HOUR_IN_SECONDS
				];

				unset( $args['check_out_date'] );
			}
		}

		if ( ! empty( $args['date'] ) ) {
			$args['meta_query']['relation'] = 'OR';
			$args['meta_query'][]           = [
				'column'   => $args['date'],
				'operator' => 'BETWEEN',
				'value'    => [
					'check_in_date',
					'check_out_date',
				],
			];
			$args['meta_query'][]           = [
				'column'   => $args['date'] + 1,
				'operator' => 'BETWEEN',
				'value'    => [
					'check_in_date',
					'check_out_date',
				],
			];
		}

		return apply_filters( 'jet-booking/db/query/prepared-params', $args, $params );

	}

	/**
	 * Add nested query arguments
	 *
	 * @param string  $key    Column name.
	 * @param mixed   $value  Compared value.
	 * @param boolean $format Data format.
	 *
	 * @return string
	 */
	public function get_sub_query( $key = '', $value = '', $format = false ) {

		if ( ! $format ) {
			if ( false !== strpos( $key, '!' ) ) {
				$format = '`%1$s` != \'%2$s\'';
				$key    = ltrim( $key, '!' );
			} else {
				$format = '`%1$s` = \'%2$s\'';
			}
		}

		$query = '';
		$glue  = '';

		foreach ( $value as $child ) {
			$query .= $glue;
			$query .= sprintf( $format, esc_sql( $key ), esc_sql( $child ) );
			$glue  = ' OR ';
		}

		return $query;

	}

	/**
	 * Add where args.
	 *
	 * Add where arguments to query.
	 *
	 * @since  2.8.0 Added new arguments handling for `>=` & `<=`.
	 *
	 * @param array  $args Query arguments.
	 * @param string $rel  Query relation.
	 *
	 * @return string
	 */
	public function add_where_args( $args = [], $rel = 'AND' ) {

		$query = '';

		if ( ! empty( $args ) ) {
			$query .= ' WHERE ';
			$glue  = '';

			foreach ( $args as $key => $value ) {
				$format = '`%1$s` = \'%2$s\'';
				$query  .= $glue;

				if ( false !== strpos( $key, '!' ) ) {
					$key    = ltrim( $key, '!' );
					$format = '`%1$s` != \'%2$s\'';
				} elseif ( false !== strpos( $key, '>=' ) ) {
					$key    = rtrim( $key, '>=' );
					$format = '`%1$s` >= %2$d';
				} elseif ( false !== strpos( $key, '>' ) ) {
					$key    = rtrim( $key, '>' );
					$format = '`%1$s` > %2$d';
				} elseif ( false !== strpos( $key, '<=' ) ) {
					$key    = rtrim( $key, '<=' );
					$format = '`%1$s` <= %2$d';
				} elseif ( false !== strpos( $key, '<' ) ) {
					$key    = rtrim( $key, '<' );
					$format = '`%1$s` < %2$d';
				}

				if ( is_array( $value ) ) {
					$query .= sprintf( '( %s )', $this->get_sub_query( $key, $value, $format ) );
				} else {
					$query .= sprintf( $format, esc_sql( $key ), esc_sql( $value ) );
				}

				$glue = ' ' . $rel . ' ';
			}
		}

		return $query;

	}

	/**
	 * Add order arguments to query.
	 *
	 * @param array $order Query order arguments.
	 *
	 * @return string
	 */
	public function add_order_args( $order = [] ) {

		$query = '';

		if ( ! empty( $order['orderby'] ) ) {
			$orderby = $order['orderby'];
			$order   = ! empty( $order['order'] ) ? $order['order'] : 'desc';
			$order   = strtoupper( $order );
			$query   .= " ORDER BY $orderby $order";
		}

		return $query;

	}

	/**
	 * Return count of queried items.
	 *
	 * @param array  $args List of query arguments.
	 * @param string $rel  Query relation.
	 *
	 * @return string|null
	 */
	public function count( $args = [], $rel = 'AND' ) {

		$table = $this->bookings->table();
		$query = "SELECT count(*) FROM $table";

		if ( ! $rel ) {
			$rel = 'AND';
		}

		if ( isset( $args['after'] ) ) {
			$after = $args['after'];
			unset( $args['after'] );
			$args['ID>'] = $after;
		}

		if ( isset( $args['before'] ) ) {
			$before = $args['before'];
			unset( $args['before'] );
			$args['ID<'] = $before;
		}

		$query .= $this->add_where_args( $args, $rel );

		return self::wpdb()->get_var( $query );

	}

	/**
	 * Query.
	 *
	 * Query data from db table.
	 *
	 * @since  2.0.0
	 * @since  3.0.0 Check for bookings table existence.
	 *
	 * @param array  $args   List of query arguments.
	 * @param null   $table  Queried table name.
	 * @param int    $limit  Result limit number.
	 * @param int    $offset Result offset number.
	 * @param array  $order  List of query order options.
	 * @param string $rel    Arguments relation.
	 *
	 * @return array|object|\stdClass[]|null
	 */
	public function query( $args = [], $table = null, $limit = 0, $offset = 0, $order = [], $rel = 'AND' ) {

		if ( ! $this->tables_exists() ) {
			return [];
		}

		if ( ! $table ) {
			$table = $this->bookings->table();
		}

		$query = "SELECT * FROM $table";

		if ( ! $rel ) {
			$rel = 'AND';
		}

		if ( isset( $args['after'] ) ) {
			$after = $args['after'];
			unset( $args['after'] );
			$args['ID>'] = $after;
		}

		if ( isset( $args['before'] ) ) {
			$before = $args['before'];
			unset( $args['before'] );
			$args['ID<'] = $before;
		}

		$query .= $this->add_where_args( $args, $rel );
		$query .= $this->add_order_args( $order );

		if ( intval( $limit ) > 0 ) {
			$limit  = absint( $limit );
			$offset = absint( $offset );
			$query  .= " LIMIT $offset, $limit";
		}

		return self::wpdb()->get_results( $query, ARRAY_A );

	}

}
