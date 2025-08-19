<?php
/**
 * Uses Vue component
 */
namespace JET_ABAF\Dashboard\Post_Meta;

use JET_ABAF\Plugin;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Base_Vue_Meta_Box Class.
 *
 * @since 1.0.0
 */
class Base_Vue_Meta_Box {

	/**
	 * Post meta key
	 */
	protected $meta_key = null;

	/**
	 * CPT slug.
	 *
	 * Holds apartment custom post type slug.
	 *
	 * @since  2.6.0
	 * @access protected
	 *
	 * @var string
	 */
	protected $cpt_slug;

	/**
	 * current_screen_slug.
	 */
	private $current_screen_slug;

	/**
	 * $plugin_path.
	 */
	protected $plugin_path = JET_ABAF_PATH;

	/**
	 * $plugin_path.
	 */
	protected $plugin_url = JET_ABAF_URL;

	/**
	 * $plugin_path.
	 */
	protected $plugin_version = JET_ABAF_VERSION;

	public function __construct( $current_screen_slug = '' ) {

		$this->current_screen_slug = $current_screen_slug;

		if( ! $current_screen_slug ){
			return;
		}

		if( ! $this->meta_key ){
			$this->meta_key = 'jet_post_meta__' . $current_screen_slug;
		}

		add_action( 'add_meta_boxes', [ $this, 'add_meta_box' ], 11 );
		add_action( 'admin_enqueue_scripts', [ $this, 'vendor_assets' ], 7 );
		add_action( 'admin_enqueue_scripts', [ $this, 'localize_meta' ], 8 );
		add_action( 'admin_enqueue_scripts', [ $this, 'assets' ], 9 );
		add_action( 'admin_footer', [ $this, 'render_templates' ] );

	}

	/**
	 * Add meta box.
	 *
	 * Add a meta box to post.
	 *
	 * @since  2.2.5
	 * @access public
	 *
	 * @return void
	 */
	protected function add_meta_box() {}

	/**
	 * Default meta.
	 *
	 * Returns default meta values list.
	 *
	 * @since  2.2.5
	 * @access public
	 *
	 * @return array List on default values.
	 */
	protected function get_default_meta() {
		return [];
	}

	/**
	 * Return values from the database.
	 *
	 * @return [array] [description]
	 */
	protected function get_meta() {

		$default_meta = $this->get_default_meta();
		$post_ID      = get_the_ID();
		$meta         = get_post_meta( $post_ID, $this->meta_key, true );
		$meta         = is_array( $meta ) ? $meta : [];
		$meta         = array_replace_recursive( $default_meta, $meta );
		$meta         = $this->parse_settings( $meta );

		$meta['ID']     = $post_ID;
		$meta['action'] = $this->meta_key;
		$meta['nonce']  = wp_create_nonce( $this->meta_key );

		return $meta;

	}

	/**
	 * Is CPT page.
	 *
	 * Checks if the post page is booking instance custom post type.
	 *
	 * @return boolean
	 */
	protected function is_cpt_page() {
		return is_admin() && get_current_screen()->id === $this->current_screen_slug;
	}

	/**
	 * Parse settings.
	 *
	 * Parsed data before written to the database and after get from the database.
	 *
	 * @since  2.2.5
	 * @access public
	 *
	 * @return array List of parsed settings.
	 */
	protected function parse_settings( $settings ){
		return $settings;
	}

	/**
	 * Save post meta.
	 *
	 * Saves metadata to the database.
	 *
	 * @since  1.0.0
	 * @since  2.6.2 Added `nonce` security check.
	 * @access public
	 *
	 * @return void
	 */
	public function save_post_meta() {

		if ( empty( $_REQUEST['nonce'] ) || ! wp_verify_nonce( $_REQUEST['nonce'], $this->meta_key ) ) {
			wp_send_json_error( [
				'message' => __( 'Security check failed.', 'jet-booking' ),
			] );
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [
				'message' => __( 'Access denied. Not enough permissions', 'jet-booking' ),
			] );
		}

		$meta = ! empty( $_REQUEST[ 'meta' ] ) ? $_REQUEST[ 'meta' ] : [];

		if ( empty( $meta ) || ! isset( $meta['ID'] ) ) {
			wp_send_json_error( [
				'message' => __( 'Empty data or post ID not found!', 'jet-booking' ),
			] );
		}

		$meta = $this->parse_settings( $meta );

		$this->backward_save_post_meta( $meta );

		$result = update_post_meta( $meta['ID'] , $this->meta_key , $meta );

		if( ! $result || is_wp_error( $result ) ){
			wp_send_json_error( [
				'message' => __( 'Failed to save data!', 'jet-booking' ),
			] );
		}

		wp_send_json_success( [
			'message' => __( 'Settings saved!', 'jet-booking' ),
		] );

	}

	/**
	 * Function for backward compatibility.
	 */
	protected function backward_save_post_meta( $meta ){}

	/**
	 * Include vue scripts.
	 */
	public function localize_meta() {

		if ( ! $this->is_cpt_page() ) {
			return;
		}

		$meta = $this->get_meta();

		if ( ! empty( $meta ) ) {
			wp_localize_script( 'cx-vue', $this->meta_key, $meta );
		}
	}

	/**
	 * Assets.
	 *
	 * Include scripts and styles.
	 *
	 * @since  2.2.5
	 * @access public
	 *
	 * @return void
	 */
	public function assets() {

		if ( ! $this->is_cpt_page() ) {
			return;
		}

		wp_enqueue_script(
			'jet-abaf-meta-extras',
			$this->plugin_url . 'assets/js/admin/meta-extras.js',
			[ 'cx-vue-ui', 'moment-js' ],
			$this->plugin_version,
			true
		);

		wp_enqueue_style(
			'jet-abaf-meta',
			$this->plugin_url . 'assets/css/admin/jet-abaf-admin-style.css',
			[],
			$this->plugin_version
		);

	}

	/**
	 * Include scripts and styles
	 */
	public function vendor_assets() {

		if ( ! $this->is_cpt_page() ) {
			return;
		}

		$ui_data = jet_abaf()->framework->get_included_module_data( 'cherry-x-vue-ui.php' );
		$ui      = new \CX_Vue_UI( $ui_data );

		$ui->enqueue_assets();


		wp_enqueue_script(
			'vuejs-datepicker',
			$this->plugin_url . 'assets/js/lib/vuejs-datepicker.min.js',
			[],
			$this->plugin_version,
			true
		);

		wp_enqueue_script(
			'moment-js',
			JET_ABAF_URL . 'assets/lib/moment/js/moment.js',
			array(),
			$this->plugin_version,
			true
		);

	}

	/**
	 * Vue templates.
	 *
	 * Return vue templates.
	 *
	 * @since  2.2.5
	 * @access public
	 *
	 * @return array List of vue templates.
	 */
	protected function get_vue_templates() {
		/*
		[
			[
				'dir'  => '',
				'file' => '',
			]
		]
		*/

		return [];
	}

	/**
	 * Render vue templates
	 *
	 * @return [type] [description]
	 */
	public function render_templates() {
		if ( ! $this->is_cpt_page() ) {
			return;
		}

		$templates = $this->get_vue_templates();

		if( empty( $templates ) ){
			return;
		}

		foreach ( $this->get_vue_templates() as $template ) {
			if ( is_array( $template ) ) {
				$this->render_template( $template['file'], $template['dir'] );
			} else {
				$this->render_template( $template );
			}
		}
	}

	/**
	 * Render vue template
	 *
	 * @return void
	 */
	private function render_template( $template, $path = null ) {

		if ( ! $path ) {
			$path = $this->meta_key;
		}

		$file = $this->plugin_path . 'templates/admin/' . $path . '/' . $template . '.php';

		if ( ! is_readable( $file ) ) {
			return;
		}

		ob_start();
		include $file;
		$content = ob_get_clean();

		printf( '<script type="text/x-template" id="jet-abaf-%1$s">%2$s</script>', $template, $content );

	}

}
