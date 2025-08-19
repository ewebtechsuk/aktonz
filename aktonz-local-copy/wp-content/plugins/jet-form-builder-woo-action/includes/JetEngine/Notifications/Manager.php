<?php


namespace Jet_FB_Woo\JetEngine\Notifications;

use Jet_FB_Woo\Plugin;
use JFB\WooComm\Vendor\JFBCore\JetEngine\NotificationsManager;

class Manager extends NotificationsManager {

	public function register_notification() {
		return array(
			new Notification(),
		);
	}

	/**
	 * Register notification assets
	 *
	 * @return void
	 */
	public function register_assets() {
		$script_asset = require_once JET_FB_WOO_ACTION_PATH . 'assets/build/engine.editor.asset.php';

		wp_enqueue_script(
			Plugin::SLUG,
			JET_FB_WOO_ACTION_URL . 'assets/build/builder.editor.js',
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);
	}

	public function plugin_version_compare() {
		return '2.8.3';
	}

	public function on_base_need_update() {
		$this->add_admin_notice(
			'warning',
			__(
				'<b>Warning</b>: <b>JetFormBuilder Woocommerce Cart & Checkout Action</b> needs <b>JetEngine</b> update.',
				'jet-form-builder-woo-action'
			)
		);
	}

	public function on_base_need_install() {
	}


}
