<?php

namespace JET_ABAF;

class Export {

	/**
	 * Action
	 *
	 * Name of export action.
	 *
	 * @since  3.2.0
	 * @access protected
	 *
	 * @var string
	 */
	protected $action = 'jet_bookings_export';

	/**
	 * Domain.
	 *
	 * Current domain name holder.
	 *
	 * @since  3.2.0
	 * @access protected
	 *
	 * @var null
	 */
	protected $domain = null;

	public function __construct() {
		add_action( 'admin_action_jet_bookings_export', array( $this, 'do_export' ) );
	}

	/**
	 * Do export.
	 *
	 * Export bookings list for selected parameters.
	 *
	 * @since  3.2.0
	 * @access public
	 *
	 * @return void
	 */
	public function do_export() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'You don`t have access to this URL', __( 'Error', 'jet-booking' ) );
		}

		if ( empty( $_REQUEST['nonce'] ) || ! wp_verify_nonce( $_REQUEST['nonce'], $this->action ) ) {
			wp_die( 'Link is expired. Please reload booking page and try again.', __( 'Error', 'jet-booking' ) );
		}

		$type     = ! empty( $_GET['type'] ) ? $_GET['type'] : 'all';
		$format   = ! empty( $_GET['format'] ) ? $_GET['format'] : 'csv';
		$bookings = [];

		switch ( $type ) {
			case 'all':
				$bookings = jet_abaf_get_bookings( [ 'return' => 'arrays' ] );
				break;

			case 'filtered':
				$prepared_params = jet_abaf()->db->prepare_params( [
					'filters'  => ! empty( $_GET['filters'] ) ? wp_unslash( $_GET['filters'] ) : [],
					'sort'     => ! empty( $_GET['sort'] ) ? wp_unslash( $_GET['sort'] ) : [],
					'mode'     => ! empty( $_GET['mode'] ) ? $_GET['mode'] : 'all',
				] );

				$bookings = jet_abaf_get_bookings( wp_parse_args( $prepared_params, [ 'return' => 'arrays' ] ) );

				break;
		}

		switch ( $format ) {
			case 'csv':
				$this->to_csv( $bookings );
				break;

			case 'ical':
				$this->to_ical( $bookings );
				break;
		}

		wp_die( 'Incorrect request data', __( 'Error', 'jet-booking' ) );

	}

	/**
	 * To CSV.
	 *
	 * Export bookings list file in csv format.
	 *
	 * @since  3.2.0
	 * @access public
	 *
	 * @param array $items List of booking items.
	 *
	 * @return void
	 */
	public function to_csv( $items = [] ) {

		$this->download_headers( 'bookings.csv', 'text/csv' );

		$output        = fopen( 'php://output', 'w' );
		$headers_added = false;
		$return        = ! empty( $_GET['return'] ) ? $_GET['return'] : 'id';
		$date_format   = ! empty( $_GET['date_format'] ) ? $_GET['date_format'] : 'Y-m-d';

		foreach ( $items as $item ) {
			if ( 'title' === $return ) {
				$item['apartment_id'] = ! empty( $item['apartment_id'] ) ? get_the_title( $item['apartment_id'] ) : $item['apartment_id'];
			}

			$tz = new \DateTimeZone( 'GMT+0' );

			$item['check_in_date']  = wp_date( $date_format, $item['check_in_date'], $tz );
			$item['check_out_date'] = wp_date( $date_format, $item['check_out_date'], $tz );

			if ( ! empty( $item['columns'] ) ) {
				foreach ( $item['columns'] as $key => $value ) {
					$item[ $key ] = $value;
				}
			}

			unset( $item['attributes'] );
			unset( $item['columns'] );
			unset( $item['guests'] );

			$item = apply_filters( 'jet-booking/export/csv-item-data', $item, $this );

			if ( ! $headers_added ) {
				$headers_added = true;
				$headers       = array_keys( $item );

				fputcsv( $output, $headers );
			}

			fputcsv( $output, $item );
		}

		fclose( $output );

		die();

	}

	/**
	 * To iCal.
	 *
	 * Export bookings list file in iCal format.
	 *
	 * @since  3.2.0
	 * @access public
	 *
	 * @param array $items List of booking items.
	 *
	 * @return void
	 */
	public function to_ical( $items = [] ) {

		$this->download_headers( 'bookings.ics', 'text/calendar' );

		if ( ! defined( '_ZAPCAL' ) ) {
			require_once JET_ABAF_PATH . 'includes/vendor/icalendar/zapcallib.php';
		}

		$datestamp = \ZCiCal::fromUnixDateTime() . 'Z';
		$calendar  = new \ZCiCal();

		foreach ( $items as $item ) {
			$this->add_calendar_item( $item, $calendar, $datestamp );
		}

		echo $calendar->export();

		die();

	}

	/**
	 * Add calendar item.
	 *
	 * Add booking item to existing calendar.
	 *
	 * @since  3.2.0
	 * @access public
	 *
	 * @param array   $item      Booking data list.
	 * @param \ZCiCal $calendar  iCal object instance.
	 * @param string  $datestamp Formatted iCal date/time string.
	 *
	 * @return void
	 */
	public function add_calendar_item( $item, $calendar, $datestamp ) {

		$hash_string = $item['check_in_date'] . $item['check_out_date'] . $item['ID'];
		$uid         = md5( $hash_string ) . '@' . $this->get_domain();
		$event       = new \ZCiCalNode( 'VEVENT', $calendar->curnode );
		$tz          = new \DateTimeZone( 'GMT+0' );

		if ( ! jet_abaf()->settings->is_per_nights_booking( $item['apartment_id'] ) ) {
			$item['check_out_date'] += DAY_IN_SECONDS;
		}

		$check_in_date  = wp_date( 'Y-m-d', $item['check_in_date'], $tz );
		$check_out_date = wp_date( 'Y-m-d', $item['check_out_date'], $tz );

		$data = apply_filters( 'jet-booking/export/ical-item-data', [
			'uid'         => [
				'node'  => 'UID',
				'value' => $uid,
			],
			'dtstart'     => [
				'node'  => 'DTSTART;VALUE=DATE-TIME',
				'value' => \ZCiCal::fromSqlDateTime( $check_in_date ),
			],
			'dtend'       => [
				'node'  => 'DTEND;VALUE=DATE-TIME',
				'value' => \ZCiCal::fromSqlDateTime( $check_out_date ),
			],
			'dtstamp'     => [
				'node'  => 'DTSTAMP',
				'value' => $datestamp,
			],
			'summary'     => [
				'node'  => 'SUMMARY',
				'value' => get_the_title( $item['apartment_id'] ),
			],
			'description' => [
				'node'  => 'DESCRIPTION',
				'value' => get_the_excerpt( $item['apartment_id'] ),
			],
		], $item, $calendar );

		foreach ( $data as $row ) {
			$event->addNode( new \ZCiCalDataNode( $row['node'] . ':' . $row['value'] ) );
		}

	}

	/**
	 * Download headers.
	 *
	 * Set the export headers before download.
	 *
	 * @since  3.2.0
	 * @access public
	 *
	 * @param null   $filename Download file name.
	 * @param string $type     Download content type.
	 *
	 * @return void
	 */
	public function download_headers( $filename = null, $type = 'application/json' ) {

		if ( false === strpos( ini_get( 'disable_functions' ), 'set_time_limit' ) ) {
			set_time_limit( 0 );
		}

		@session_write_close();

		if ( function_exists( 'apache_setenv' ) ) {
			$variable = 'no-gzip';
			$value    = 1;
			@apache_setenv( $variable, $value );
		}

		@ini_set( 'zlib.output_compression', 'Off' );

		nocache_headers();

		header( "Robots: none" );
		header( "Content-Type: " . $type );
		header( "Content-Description: File Transfer" );
		header( "Content-Disposition: attachment; filename=\"" . $filename . "\";" );
		header( "Content-Transfer-Encoding: binary" );

	}

	/**
	 * Get domain.
	 *
	 * Returns current domain.
	 *
	 * @since  3.2.0
	 * @access public
	 *
	 * @return string|string[]|void
	 */
	public function get_domain() {

		if ( ! $this->domain ) {
			$this->domain = str_replace( [ 'http://', 'https://' ], '', home_url() );
		}

		return $this->domain;

	}

	/**
	 * Get nonce.
	 *
	 * Returns security key for export action.
	 *
	 * @since  3.2.0
	 * @access public
	 *
	 * @return false|string
	 */
	public function get_nonce() {
		return wp_create_nonce( $this->action );
	}

	/**
	 * Base url.
	 *
	 * Returns base link url for export action.
	 *
	 * @since  3.2.0
	 * @access public
	 *
	 * @return string
	 */
	public function base_url() {
		return add_query_arg( [ 'action' => $this->action ], admin_url( 'admin.php' ) );
	}

}