<?php

namespace JET_ABAF\Dashboard;

use JET_ABAF\Plugin;

/**
 * Units manager class
 */
class Units_Manager {

	public $apartment_post_type;
	private $base_url = null;

	/**
	 * Constructor class
	 *
	 * @param [type] $apartment_post_type [description]
	 */
	public function __construct( $apartment_post_type ) {

		$this->apartment_post_type = $apartment_post_type;

		add_action( 'add_meta_boxes_' . $this->apartment_post_type, [ $this, 'register_meta_box' ] );

		add_action( 'wp_ajax_jet_abaf_get_units', [ $this, 'get_units' ] );
		add_action( 'wp_ajax_jet_abaf_insert_units', [ $this, 'insert_units' ] );
		add_action( 'wp_ajax_jet_abaf_update_unit', [ $this, 'update_unit' ] );
		add_action( 'wp_ajax_jet_abaf_delete_unit', [ $this, 'delete_unit' ] );

		add_action( 'admin_enqueue_scripts', [ $this, 'init_units_manager' ], 99 );

	}

	/**
	 * Register
	 * @return [type] [description]
	 */
	public function register_meta_box() {

		add_meta_box(
			'jet-abaf-units',
			esc_html__( 'Units manager', 'jet-booking' ),
			array( $this, 'render_meta_box' ),
			null,
			'normal',
			'high'
		);
	}

	public function render_meta_box() {
		echo '<div id="jet_abaf_apartment_units"></div>';
	}

	/**
	 * Get units.
	 *
	 * Returns available units list
	 *
	 * @since 1.0.0
	 * @since 2.6.2 Added security check.
	 */
	public function get_units() {

		if ( empty( $_REQUEST['nonce'] ) || ! wp_verify_nonce( $_REQUEST['nonce'], 'jet-abaf-' . $this->apartment_post_type . '-units' ) ) {
			wp_send_json_error( [
				'message' => __( 'Security check failed.', 'jet-booking' ),
			] );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}

		$apartment = ! empty( $_REQUEST['apartment'] ) ? absint( $_REQUEST['apartment'] ) : false;

		if ( ! $apartment ) {
			wp_send_json_error();
		}

		wp_send_json_success( [ 'units' => jet_abaf()->db->get_apartment_units( $apartment ) ] );

	}

	/**
	 * Insert units.
	 *
	 * Insert new units of apartment.
	 *
	 * @since 1.0.0
	 * @since 2.6.2 Added security check.
	 *
	 * @return void
	 */
	public function insert_units() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}

		$request = file_get_contents( 'php://input' );
		$request = json_decode( $request, true );

		if ( empty( $request ) ) {
			wp_send_json_error();
		}

		$this->request_security_check( $request );

		$apartment = ! empty( $request['apartment'] ) ? absint( $request['apartment'] ) : false;
		$number    = ! empty( $request['number'] ) ? absint( $request['number'] ) : 1;
		$title     = ! empty( $request['title'] ) ? esc_attr( $request['title'] ) : false;

		if ( ! $apartment ) {
			wp_send_json_error();
		}

		if ( ! $title ) {
			$title = get_the_title( $apartment );
		}

		$result = jet_abaf()->db->get_apartment_units( $apartment );

		$current_count = count( $result );

		for ( $i = 1; $i <= $number; $i ++ ) {
			$num = $current_count + $i;

			jet_abaf()->db::wpdb()->insert( jet_abaf()->db->units->table(), [
				'apartment_id' => $apartment,
				'unit_title'   => $title . ' ' . $num,
			] );
		}

		$result = jet_abaf()->db->get_apartment_units( $apartment );

		wp_send_json_success( [ 'units' => $result ] );

	}

	/**
	 * Update unit.
	 *
	 * Update unit of apartment.
	 *
	 * @since 1.0.0
	 * @since 2.6.2 Added security check.
	 */
	public function update_unit() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}

		$request = file_get_contents( 'php://input' );
		$request = json_decode( $request, true );

		$this->request_security_check( $request );

		$apartment = ! empty( $request['apartment'] ) ? absint( $request['apartment'] ) : false;
		$unit      = ! empty( $request['unit'] ) ? $request['unit'] : false;

		if ( ! $apartment || ! $unit ) {
			wp_send_json_error();
		}

		$unit_id = $unit['unit_id'] ?? false;

		if ( ! $unit_id ) {
			wp_send_json_error();
		}

		jet_abaf()->db->units->update( $unit, [ 'unit_id' => $unit_id ] );

		wp_send_json_success( [ 'units' => jet_abaf()->db->get_apartment_units( $apartment ) ] );

	}

	/**
	 * Delete unit.
	 *
	 * Delete unit of apartment.
	 *
	 * @since 1.0.0
	 * @since 2.6.2 Added security check.
	 */
	public function delete_unit() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}

		$request = file_get_contents( 'php://input' );
		$request = json_decode( $request, true );

		$this->request_security_check( $request );

		$apartment = ! empty( $request['apartment'] ) ? absint( $request['apartment'] ) : false;
		$unit      = ! empty( $request['unit'] ) ? absint( $request['unit'] ) : false;

		if ( ! $apartment ) {
			wp_send_json_error();
		}

		jet_abaf()->db->units->delete( [ 'apartment_id' => $apartment, 'unit_id' => $unit ] );

		wp_send_json_success( [ 'units' => jet_abaf()->db->get_apartment_units( $apartment ) ] );

	}

	/**
	 * Initialize units manager
	 *
	 * @return [type] [description]
	 */
	public function init_units_manager( $hook ) {

		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ) ) ) {
			return;
		}

		if ( $this->apartment_post_type !== get_post_type() ) {
			return;
		}

		$ui_data = jet_abaf()->framework->get_included_module_data( 'cherry-x-vue-ui.php' );
		$ui      = new \CX_Vue_UI( $ui_data );

		$ui->enqueue_assets();

		wp_enqueue_script(
			'jet-abaf-units-manager',
			JET_ABAF_URL . 'assets/js/admin/units-manager.js',
			array( 'cx-vue-ui', 'wp-api-fetch' ),
			JET_ABAF_VERSION,
			true
		);

		global $post;

		$nonce       = wp_create_nonce( 'jet-abaf-' . $this->apartment_post_type . '-units' );
		$post_id     = jet_abaf()->db->get_initial_booking_item_id( $post->ID );
		$action_args = [
			'apartment' => $post_id,
			'nonce'     => $nonce,
		];

		wp_localize_script( 'jet-abaf-units-manager', 'JetABAFUnitsData', array(
			'apartment'    => $post_id,
			'insert_units' => $this->get_action_url( 'insert_units' ),
			'get_units'    => $this->get_action_url( 'get_units', $action_args ),
			'delete_unit'  => $this->get_action_url( 'delete_unit' ),
			'update_unit'  => $this->get_action_url( 'update_unit' ),
			'nonce'        => $nonce,
		) );

		add_action( 'admin_footer', array( $this, 'unit_manager_template' ) );

	}

	/**
	 * Returns action URL
	 *
	 * @param null  $action
	 * @param array $args
	 *
	 * @return string [type] [description]
	 */
	public function get_action_url( $action = null, $args = array() ) {

		if ( ! $this->base_url ) {
			$this->base_url = admin_url( 'admin-ajax.php' );
		}

		return add_query_arg( array_merge( array( 'action' => 'jet_abaf_' . $action ), $args ), $this->base_url );

	}

	/**
	 * Request security check.
	 *
	 * @since  2.6.2
	 * @access public
	 *
	 * @param array $request List of request data.
	 *
	 * @return void
	 */
	public function request_security_check( $request ) {
		if ( empty( $request['nonce'] ) || ! wp_verify_nonce( $request['nonce'], 'jet-abaf-' . $this->apartment_post_type . '-units' ) ) {
			wp_send_json_error( [
				'message' => __( 'Security check failed.', 'jet-booking' ),
			] );
		}
	}

	/**
	 * LOad units manager template
	 *
	 * @return void [type] [description]
	 */
	public function unit_manager_template() {
		ob_start();
		include JET_ABAF_PATH . 'templates/units-manager.php';
		$content = ob_get_clean();
		printf( '<script type="text/x-template" id="jet-abaf-units-manager">%s</script>', $content );
	}

}