<?php

namespace JET_ABAF\Rest_API\Endpoints;

defined( 'ABSPATH' ) || exit;

class Update_ICal_Template extends Base {

	public static $key = 'jet_booking_ical_template';

	/**
	 * Get name.
	 *
	 * Returns route name.
	 *
	 * @since  2.7.0
	 *
	 * @return string
	 */
	public function get_name() {
		return 'update-ical-template';
	}

	/**
	 * Callback.
	 *
	 * API callback.
	 *
	 * @since  2.7.0
	 *
	 * @param object $request Endpoint request object.
	 *
	 * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response
	 */
	public function callback( $request ) {

		$params   = $request->get_params();
		$template = ! empty( $params['template'] ) ? $params['template'] : [];

		update_option( self::$key, $template, false );

		return rest_ensure_response( [
			'success' => true,
		] );

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
		return 'POST';
	}

}