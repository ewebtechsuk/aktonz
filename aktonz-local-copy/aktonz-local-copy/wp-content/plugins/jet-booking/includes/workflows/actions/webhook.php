<?php

namespace JET_ABAF\Workflows\Actions;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Webhook extends Base {

	/**
	 * Get action ID.
	 *
	 * Returns the unique identifier for this action.
	 *
	 * @since 3.7.0
	 *
	 * @return string Action ID.
	 */
	public function get_id() {
		return 'webhook';
	}

	/**
	 * Get action name.
	 *
	 * Returns the human-readable name for this action.
	 *
	 * @since 3.7.0
	 *
	 * @return string Action name.
	 */
	public function get_name() {
		return __( 'Call a Webhook', 'jet-booking' );
	}

	/**
	 * Performs the action.
	 *
	 * @since 3.7.0
	 *
	 * @return void
	 */
	public function do_action() {

		$webhook_url = $this->get_settings( 'webhook_url' );
		$args        = apply_filters( 'jet-booking/workflows/webhook/args', [ 'body' => $this->booking ], $this );

		if ( $webhook_url ) {
			$response = wp_remote_post( $webhook_url, $args );

			do_action( 'jet-booking/workflows/webhook/after-response', $response, $args, $this );
		}

	}

	/**
	 * Registers action controls for the workflow.
	 *
	 * This method is responsible for adding controls to the webhook action.
	 *
	 * @since 3.7.0
	 *
	 * @return void
	 */
	public function register_action_controls() {
		include JET_ABAF_PATH . 'includes/workflows/templates/actions/webhook-controls.php';
	}

}