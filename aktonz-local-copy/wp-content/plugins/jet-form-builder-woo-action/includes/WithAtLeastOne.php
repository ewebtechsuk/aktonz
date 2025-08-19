<?php


namespace Jet_FB_Woo;

trait WithAtLeastOne {

	abstract public function on_plugin_init();

	public static function register() {
		$instance = new static();
		$instance->plugin_maybe_init();

		return $instance;
	}

	private function __construct() {
	}

	public function plugin_list() {
		return array(
			'jet_form_builder' => array( 'get_version', '1.2.1' ),
			'jet_engine'       => array( 'get_version', '2.8.3' ),
		);
	}

	final public function plugin_maybe_init() {
		foreach ( $this->plugin_list() as $function_name => list( $call_current, $compare_version ) ) {
			if ( function_exists( $function_name )
				 && version_compare( $function_name()->$call_current(), $compare_version, '>=' )
			) {
				$this->on_plugin_init();

				return;
			}
		}
	}

}
