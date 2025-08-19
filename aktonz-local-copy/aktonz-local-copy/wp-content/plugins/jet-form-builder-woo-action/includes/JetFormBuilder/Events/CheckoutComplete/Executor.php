<?php


namespace Jet_FB_Woo\JetFormBuilder\Events\CheckoutComplete;

use Jet_Form_Builder\Actions\Events\Base_Executor;

class Executor extends Base_Executor {

	public function is_supported(): bool {
		return true;
	}
}
