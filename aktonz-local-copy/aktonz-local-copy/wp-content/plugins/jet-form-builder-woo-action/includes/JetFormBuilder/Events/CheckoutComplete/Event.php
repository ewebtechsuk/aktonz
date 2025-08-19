<?php

namespace Jet_FB_Woo\JetFormBuilder\Events\CheckoutComplete;

use Jet_Form_Builder\Actions\Events\Base_Event;

class Event extends Base_Event {

	public function get_id(): string {
		return 'WC.CHECKOUT.COMPLETE';
	}

	public function executors(): array {
		return array(
			new Executor(),
		);
	}
}
