<?php

namespace Jet_WC_Product_Table;

/**
 * Public assets manager
 */
class Assets {

	protected $assets_registered       = false;
	protected $inline_assets_in_footer = false;
	protected $footer_assets           = [];

	public function __construct() {
		add_action( 'wp_enqueue_scripts', [ $this, 'register_public_assets' ] );
		add_action( 'wp_footer', [ $this, 'print_inline_footer_assets' ], 0 );
	}

	/**
	 * Enqueue previously registered script by handle
	 *
	 * @param  string $handle Script name to enqueue.
	 * @return void
	 */
	public function enqueue_script( $handle ) {

		if ( ! $this->assets_registered ) {
			$this->register_public_assets();
		}

		wp_enqueue_script( $handle );
	}

	/**
	 * Set value of footer assets trigger.
	 * If true - inline assets will be enqueued in footer. If false - immediately.
	 * Affects only assets called after trigger set.
	 *
	 * @param boolean $inline_assets_in_footer Inline assets state - true/false.
	 */
	public function set_inline_in_footer_trigger( $inline_assets_in_footer = false ) {
		$this->inline_assets_in_footer = $inline_assets_in_footer;
	}

	/**
	 * Register public assests for the tables
	 *
	 * @return void
	 */
	public function register_public_assets() {

		wp_register_script(
			'jet-wc-product-snackbar',
			JET_WC_PT_URL . 'assets/js/public/snackbar-notices.js',
			[ 'jquery' ],
			JET_WC_PT_VERSION,
			true
		);

		wp_register_script(
			'jet-wc-product-actions',
			JET_WC_PT_URL . 'assets/js/public/product-actions.js',
			[ 'jet-wc-product-snackbar' ],
			JET_WC_PT_VERSION,
			true
		);

		wp_register_script(
			'jet-wc-product-filters',
			JET_WC_PT_URL . 'assets/js/public/filters.js',
			[ 'jet-wc-product-snackbar' ],
			JET_WC_PT_VERSION,
			true
		);

		$this->assets_registered = true;
	}

	/**
	 * Enqueue inline script
	 *
	 * @param  string $file File name without extension.
	 * @return void
	 */
	public function enqueue_inline_script( $file = '' ) {

		$file = sanitize_file_name( $file );
		$path = JET_WC_PT_PATH . 'assets/js/inline/' . $file . '.js';

		if ( ! is_readable( $path ) ) {
			return;
		}

		if ( $this->inline_assets_in_footer ) {
			$this->footer_assets[] = $path;
		} else {
			$this->print_inline_script( $path );
		}
	}

	/**
	 * Print inline script by path to file of this script.
	 *
	 * @param  string $path Path to the script file.
	 * @return void
	 */
	public function print_inline_script( $path ) {

		ob_start();
		include $path;
		$content = ob_get_clean();

		// phpcs:ignore
		printf( '<script>%s</script>', $content );
	}

	/**
	 * Print inline assets registered for printing in footer.
	 *
	 * @return void
	 */
	public function print_inline_footer_assets() {
		foreach ( $this->footer_assets as $file_path ) {
			$this->print_inline_script( $file_path );
		}
	}
}
