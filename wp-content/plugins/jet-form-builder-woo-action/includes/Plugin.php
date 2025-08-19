<?php

namespace Jet_FB_Woo;

use Jet_FB_Woo\JetEngine\Notifications\Manager as JEManager;
use Jet_FB_Woo\JetFormBuilder\Actions\Manager as JFBManager;
use JFB\WooComm\Vendor\Auryn\Injector;
use JFB\WooComm\Vendor\JFBCore\LicenceProxy;

if ( ! defined( 'WPINC' ) ) {
	die();
}

/**
 * @property WcIntegration wc
 */
class Plugin {

	const SLUG = 'jet-form-builder-woo-action';

	private $injector;

	public function __construct( Injector $injector ) {
		$this->injector = $injector;
	}

	public function setup() {
		$this->injector->share( WcIntegration::class );
		$this->injector->make( WcIntegration::class );

		JFBManager::register();
		JEManager::register();
		LicenceProxy::register();
	}

	public function get_version() {
		return JET_FB_WOO_ACTION_VERSION;
	}

	/**
	 * @return Injector
	 */
	public function get_injector(): Injector {
		return $this->injector;
	}

	public static function instance(): Plugin {
		/** @noinspection PhpUnhandledExceptionInspection */
		return jet_fb_woo_comm_injector()->make( self::class );
	}

	public function __get( $name ) {
		switch ( $name ) {
			case 'wc':
				return $this->injector->make( WcIntegration::class );
			case 'slug':
				return self::SLUG;
		}
	}

}
