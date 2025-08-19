<?php


namespace Jet_FB_Woo\JetFormBuilder\Actions;

use Jet_FB_Woo\Plugin;
use JFB\WooComm\Vendor\JFBCore\JetFormBuilder\ActionsManager;
use Jet_FB_Woo\JetFormBuilder\Events\CheckoutComplete;
use Jet_FB_Woo\JetFormBuilder\Events\OrderComplete;

class Manager extends ActionsManager {

	/**
	 * Supported only >= 3.4.0 JetFormBuilder
	 *
	 * @return bool
	 */
	public function can_init(): bool {
		return class_exists( '\JFB_Modules\Actions_V2\Module' );
	}

	public function register_controller( \Jet_Form_Builder\Actions\Manager $manager ) {
		$manager->register_action_type( new Action() );

		jet_fb_events()->rep_install_item_soft( new CheckoutComplete\Event() );
		jet_fb_events()->rep_install_item_soft( new OrderComplete\Event() );
	}

	/**
	 * @return void
	 */
	public function before_init_editor_assets() {
		$script_asset = require_once JET_FB_WOO_ACTION_PATH . 'assets/build/builder.editor.asset.php';

		wp_enqueue_script(
			Plugin::SLUG,
			JET_FB_WOO_ACTION_URL . 'assets/build/builder.editor.js',
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);
	}

	public function on_base_need_update() {
		$this->add_admin_notice(
			'warning',
			__(
				'<b>Warning</b>: <b>JetFormBuilder Woocommerce Cart & Checkout Action</b> needs <b>JetFormBuilder</b> update.',
				'jet-form-builder-woo-action'
			)
		);
	}

	public function on_base_need_install() {
	}
}
