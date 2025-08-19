<?php

namespace JET_ABAF\Dashboard;

#[\AllowDynamicProperties]
class Booking_Meta {

	public function __construct() {

		$this->apartment_post_type = jet_abaf()->settings->get( 'apartment_post_type' );

		if ( $this->apartment_post_type ) {
			add_action( 'add_meta_boxes_' . $this->apartment_post_type, [ $this, 'register_meta_box' ] );
			add_action( 'admin_enqueue_scripts', [ $this, 'init_upcoming_bookings' ], 99 );

			new Units_Manager( $this->apartment_post_type );
		}

	}

	/**
	 * Register meta box.
	 *
	 * Register booking instance post type meta boxes.
	 *
	 * @since 2.0.0
	 * @since 3.2.0 Refactored.
	 */
	public function register_meta_box() {
		add_meta_box(
			'jet-abaf',
			__( 'Upcoming Bookings', 'jet-booking' ),
			[ $this, 'render_meta_box' ],
			null,
			'normal',
			'high'
		);
	}

	/**
	 * Render meta box.
	 *
	 * Render booking instance post type meta boxes.
	 *
	 * @since  2.0.0
	 * @since  2.5.5 Added additional `$post_id` handling.
	 * @since  3.2.0 Refactored. Move template body to separate file.
	 */
	public function render_meta_box() {
		echo '<div id="jet_abaf_upcoming_bookings"></div>';
	}

	/**
	 * Initialize upcoming bookings.
	 *
	 * @since 3.2.0
	 */
	public function init_upcoming_bookings( $hook ) {

		if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ] ) ) {
			return;
		}

		if ( $this->apartment_post_type !== get_post_type() ) {
			return;
		}

		$ui_data = jet_abaf()->framework->get_included_module_data( 'cherry-x-vue-ui.php' );
		$ui      = new \CX_Vue_UI( $ui_data );

		$ui->enqueue_assets();

		wp_enqueue_script(
			'jet-abaf-upcoming-bookings',
			JET_ABAF_URL . 'assets/js/admin/upcoming-bookings.js',
			[ 'cx-vue-ui', 'wp-api-fetch' ],
			JET_ABAF_VERSION,
			true
		);

		global $post;

		$post_id  = jet_abaf()->db->get_initial_booking_item_id( $post->ID );
		$bookings = jet_abaf()->db->get_future_bookings( $post_id );

		$bookings = array_map( function ( $booking ) {
			$booking['check_in_date']  = date_i18n( get_option( 'date_format' ), $booking['check_in_date'] );
			$booking['check_out_date'] = date_i18n( get_option( 'date_format' ), $booking['check_out_date'] );

			return $booking;
		}, $bookings );

		wp_localize_script( 'jet-abaf-upcoming-bookings', 'JetABAFUpcomingBookingsData', [
			'api'           => jet_abaf()->rest_api->get_urls( false ),
			'bookings'      => $bookings,
			'bookings_link' => add_query_arg( [ 'page' => 'jet-abaf-bookings', ], admin_url( 'admin.php' ) ),
			'edit_link'     => add_query_arg( [ 'post' => '%id%', 'action' => 'edit', ], admin_url( 'post.php' ) ),
		] );

		add_action( 'admin_footer', [ $this, 'upcoming_bookings_template' ] );

	}

	/**
	 * Load upcoming bookings template.
	 *
	 * @since 3.2.0
	 */
	public function upcoming_bookings_template() {
		ob_start();
		include JET_ABAF_PATH . 'templates/admin/post-meta/upcoming-bookings.php';
		printf( '<script type="text/x-template" id="jet-abaf-upcoming-bookings">%s</script>', ob_get_clean() );
	}

}
