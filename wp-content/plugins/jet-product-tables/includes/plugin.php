<?php
namespace Jet_WC_Product_Table;

// Prevent direct access to the file.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Main plugin class.
 *
 * This class initializes the plugin, including its components like the
 * columns controller and the shortcode handler.
 */
class Plugin {

	/**
	 * Instance.
	 *
	 * Holds the plugin instance.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 *
	 * @var Plugin
	 */
	public static $instance = null;

	/**
	 * Columns Controller.
	 *
	 * This property stores the instance of the columns controller which
	 * manages all the column types for the plugin.
	 *
	 * @var \Jet_WC_Product_Table\Components\Columns\Controller
	 */
	public $columns_controller;

	/**
	 * Filters Controller.
	 *
	 * This property stores the instance of the filters controller
	 *
	 * @var \Jet_WC_Product_Table\Components\Filters\Controller
	 */
	public $filters_controller;

	/**
	 * Plugin settings instance
	 *
	 * @var \Jet_WC_Product_Table\Settings
	 */
	public $settings;

	/**
	 * Plugin presets instance
	 *
	 * @var \Jet_WC_Product_Table\Presets
	 */
	public $presets;

	/**
	 * Instance of integration manager.
	 * Responsible for tables integration into default shop layouts.
	 *
	 * @var \Jet_WC_Product_Table\Components\Shop_Integration\Controller
	 */
	public $integration_controller;

	/**
	 * Instance of assets manager.
	 *
	 * @var \Jet_WC_Product_Table\Assets
	 */
	public $assets;

	/**
	 * Instance of styles manager.
	 *
	 * @var \Jet_WC_Product_Table\Components\Style_Manager\Controller
	 */
	public $styles_manager;

	/**
	 * CX framework instance.
	 *
	 * Holds the CX framework instance.
	 *
	 * @since 1.1.0
	 * @access public
	 * @static
	 *
	 * @var Plugin
	 */
	public $framework;

	/**
	 * Instance.
	 *
	 * Ensures only one instance of the plugin class is loaded or can be loaded.
	 *
	 * @return Plugin An instance of the class.
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function instance() {

		if ( is_null( self::$instance ) ) {

			self::$instance = new self();

		}

		return self::$instance;
	}

	/**
	 * Register autoloader.
	 */
	private function register_autoloader() {
		require JET_WC_PT_PATH . 'includes/autoloader.php';
		Autoloader::run();
	}

	/**
	 * Constructor.
	 *
	 * The constructor is private to prevent creating multiple instances
	 * of the singleton.
	 */
	private function __construct() {

		$this->register_autoloader();

		// Load framework
		add_action( 'after_setup_theme', [ $this, 'framework_loader' ], -20 );

		// Jet Dashboard Init
		add_action( 'init', array( $this, 'jet_dashboard_init' ), -999 );

		add_action( 'after_setup_theme', [ $this, 'init_components' ] );
		add_action( 'after_setup_theme', [ $this, 'init_modules' ] );
	}

	/**
	 * Load framework modules
	 *
	 * @return void [type] [description]
	 */
	public function framework_loader() {
		require JET_WC_PT_PATH . 'framework/loader.php';

		$this->framework = new \Jet_WC_Product_Table_CX_Loader( [
			JET_WC_PT_PATH . 'framework/vue-ui/cherry-x-vue-ui.php',
			JET_WC_PT_PATH . 'framework/jet-dashboard/jet-dashboard.php',
		] );
	}

	/**
	 * Init the JetDashboard module
	 *
	 * @return void
	 */
	public function jet_dashboard_init() {

		if ( is_admin() ) {
			$jet_dashboard_module_data = $this->framework->get_included_module_data( 'jet-dashboard.php' );
			$jet_dashboard = \Jet_Dashboard\Dashboard::get_instance();
			$jet_dashboard->init( [
				'path'             => $jet_dashboard_module_data[ 'path' ], // phpcs:ignore
				'url'              => $jet_dashboard_module_data[ 'url' ], // phpcs:ignore
				'cx_ui_instance'   => [ $this, 'jet_dashboard_ui_instance_init' ],
				'plugin_data'      => [
					'slug'         => 'jet-product-tables',
					'file'          => 'jet-product-tables/jet-product-tables.php',
					'version'      => JET_WC_PT_VERSION,
					'plugin_links' => [
						[
							'label'  => esc_html__( 'Go to settings', 'jet-wc-product-table' ),
							'url'    => add_query_arg( [
								'page' => 'wc-settings',
								'tab'  => 'jet_wc_product_table' // phpcs:ignore
							], admin_url( 'admin.php' ) ),
							'target' => '_self',
						],
					],
				],
			] );
		}
	}

	/**
	 * [jet_dashboard_ui_instance_init description]
	 * @return [type] [description]
	 */
	public function jet_dashboard_ui_instance_init() {
		$cx_ui_module_data = $this->framework->get_included_module_data( 'cherry-x-vue-ui.php' );

		return new \CX_Vue_UI( $cx_ui_module_data );
	}

	/**
	 * Initializes plugin components.
	 *
	 * This method sets up the columns controller and initializes the shortcode
	 * handler with it, so that all plugin functionality is ready to use.
	 */
	public function init_components() {

		$this->columns_controller     = new Components\Columns\Controller();
		$this->filters_controller     = new Components\Filters\Controller();
		$this->integration_controller = new Components\Shop_Integration\Controller();
		$this->presets                = new Presets();
		$this->settings               = new Settings();
		$this->assets                 = new Assets();
		$this->styles_manager         = new Components\Style_Manager\Controller();

		new \Jet_WC_Product_Table\Components\Blocks\Block_Controller();
		new \Jet_WC_Product_Table\Components\Shortcodes\Controller();

		add_filter( 'plugin_action_links_' . JET_WC_PT_PLUGIN_BASE, function ( $actions ) {

			$actions[] = sprintf(
				'<a href="%1$s"><b>%2$s</b></a>',
				Plugin::instance()->settings->settings_page_url(),
				esc_html__( 'Product Tables Settings', 'jet-wc-product-table' )
			);

			$actions[] = sprintf(
				'<a href="https://crocoblock.com/knowledge-base/features/jetproducttables-dashboard-settings-overview/">%1$s</a>',
				esc_html__( 'Quick Start Guide', 'jet-wc-product-table' )
			);

			return $actions;
		} );
	}

	/**
	 * Initialize plugin modules
	 *
	 * @return void
	 */
	public function init_modules() {
		new Modules\Elementor_Views\Module();
		new Modules\Bricks_Views\Module();
	}
}

// Initialize the plugin instance.
Plugin::instance();
