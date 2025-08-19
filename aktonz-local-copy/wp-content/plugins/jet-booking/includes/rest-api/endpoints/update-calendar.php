<?php

namespace JET_ABAF\Rest_API\Endpoints;

defined( 'ABSPATH' ) || exit;

class Update_Calendar extends Base {

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
		return 'update-calendar';
	}

	/**
	 * Callback.
	 *
	 * Rest API callback.
	 *
	 * @since  2.7.0 Added reindex array values.
	 * @access public
	 *
	 * @param object $request Callback request instance.
	 *
	 * @return void|\WP_Error|\WP_HTTP_Response|\WP_REST_Response
	 */
	public function callback( $request ) {

		$params  = $request->get_params();
		$item    = ! empty( $params['item'] ) ? $params['item'] : [];
		$post_id = ! empty( $item['post_id'] ) ? absint( $item['post_id'] ) : false;
		$unit_id = ! empty( $item['unit_id'] ) ? absint( $item['unit_id'] ) : false;

		if ( ! $post_id ) {
			return rest_ensure_response( [
				'success' => false,
				'data'    => __( 'Post ID not found in the request.', 'jet-booking' ),
			] );
		}

		if ( isset( $item['import_url'] ) ) {
			$import_url = array_filter( $item['import_url'] );

			jet_abaf()->ical->update_import_urls( array_values( $import_url ), $post_id, $unit_id );
		}

		return rest_ensure_response( [ 'success' => true ] );

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