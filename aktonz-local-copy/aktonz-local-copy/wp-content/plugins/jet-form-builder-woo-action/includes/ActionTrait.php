<?php


namespace Jet_FB_Woo;

trait ActionTrait {

	public function get_id() {
		return 'redirect_to_woo_checkout';
	}

	public function get_name() {
		return __( 'Add to Cart & Redirect to Checkout', 'jet-form-builder-woo-action' );
	}

	public function dependence() {
		return class_exists( '\WooCommerce' );
	}

	public function run_action() {
		if ( ! $this->getSettings( 'product_id_from', false ) ) {
			$this->error( 'failed', 'product_id_from: undefined' );
		}

		$product_id = $this->applyFilters( 'product-id', absint( $this->get_product_id() ), $this );

		if ( ! $product_id ) {
			$this->dynamicError( 'Undefined product ID' );
		}

		if ( 'product' !== get_post_type( $product_id ) ) {
			$this->dynamicError( 'The post is not a product' );
		}

		$cart_item_data = array(
			Plugin::instance()->wc->form_data_key       => $this->getRequest(),
			Plugin::instance()->wc->form_id_key         => $this->getFormId(),
			Plugin::instance()->wc->action_settings_key => $this->getSettings(),
		);

		$custom_price_field = $this->getSettings( 'wc_price' );
		$custom_price       = $this->getRequest( $custom_price_field );

		if ( $custom_price_field && is_numeric( $custom_price ) && $custom_price > 0 ) {
			$cart_item_data[ Plugin::instance()->wc->price_key ] = floatval( $custom_price );
		}

		do_action(
			'jet-form-builder/action/redirect_to_woo_checkout/before-add',
			$this
		);

		try {
			WC()->cart->add_to_cart(
				...$this->applyFilters(
					'add-to-cart',
					array(
						$product_id,
						1,
						0,
						array(),
						$cart_item_data,
					)
				)
			);
		} catch ( \Exception $exception ) {
			$this->dynamicError( $exception->getMessage() );
		}

		$checkout_fields_map = array();

		foreach ( $this->getSettings() as $key => $value ) {
			if ( false !== strpos( $key, 'wc_fields_map__' ) && ! empty( $value ) ) {
				$checkout_fields_map[ str_replace( 'wc_fields_map__', '', $key ) ] = $value;
			}
		}

		$checkout_fields_map = $this->applyFilters( 'fields-map', $checkout_fields_map );

		if ( ! empty( $checkout_fields_map ) ) {
			$checkout_fields = array();

			foreach ( $checkout_fields_map as $checkout_field => $form_field ) {
				if ( $this->issetRequest( $form_field ) ) {
					$checkout_fields[ $checkout_field ] = $this->getRequest( $form_field );
				}
			}

			$checkout_fields = $this->applyFilters( 'fields-map/values', $checkout_fields );

			if ( ! empty( $checkout_fields ) && WC()->session ) {
				WC()->session->set( Plugin::instance()->wc->checkout_fields_session, $checkout_fields );
			}
		}

		do_action(
			'jet-form-builder/action/redirect_to_woo_checkout/after-add',
			$this
		);
	}

	public function get_product_id() {
		switch ( $this->getSettings( 'product_id_from' ) ) {
			case 'manual':
				return $this->get_product_manual( 0, 'id' );
			case 'field':
				return $this->get_product_from_field( 0 );
		}

		return 0;
	}

	public function get_product_manual( $index, $key = '', $ifNotExist = false ) {
		$manual = $this->getSettings( 'product_manual', array() );

		if ( ! isset( $manual[ $index ] ) ) {
			return $ifNotExist;
		}

		if ( ! $key ) {
			return $manual[ $index ];
		}

		return $manual[ $index ][ $key ] ?? $ifNotExist;
	}

	public function get_product_from_field( $ifNotExist = false ) {
		if ( $this->issetRequest( $this->getSettings( 'product_id_field' ) ) ) {
			return $this->getRequest( $this->getSettings( 'product_id_field' ), $ifNotExist );
		}

		return $ifNotExist;
	}

	public function get_checkout_fields() {
		$fields = array();

		foreach ( Plugin::instance()->wc->get_checkout_fields() as $field ) {
			$fields[ $field ] = array(
				'label' => $field,
			);
		}

		return $fields;
	}

	public function action_data() {
		$form_id         = get_the_ID();
		$checkout_fields = $this->get_checkout_fields();
		$details         = Plugin::instance()->wc->get_details_schema( $form_id );
		$details         = empty( $details ) ? array() : $details;
		$nonce           = wp_create_nonce( Plugin::instance()->wc->meta_key );

		return array(
			'action'          => Plugin::instance()->wc->ajax_action,
			'wc_fields'       => $checkout_fields,
			'post_id'         => $form_id,
			'details'         => maybe_unserialize( maybe_unserialize( $details ) ),
			'nonce'           => $nonce,
			'product_id_from' => array(
				array(
					'value' => 'field',
					'label' => __( 'Form Field', 'jet-form-builder' ),
				),
				array(
					'value' => 'manual',
					'label' => __( 'Manual Input', 'jet-form-builder' ),
				),
			),
		);
	}

	public function editor_labels_help() {
		return array(
			'wc_price'         => esc_html__(
				'Select field to get total price from. If not selected price will be get from post meta value.',
				'jet-form-builder'
			),
			'wc_order_details' => esc_html__(
				'Set up info you want to add to the WooCommerce orders and e-mails',
				'jet-form-builder'
			),
			'wc_fields_map'    => __(
				'Connect WooCommerce checkout fields to appropriate form fields. 
				This allows you to pre-fill WooCommerce checkout fields after redirect to checkout.',
				'jet-form-builder'
			),
		);
	}

	/**
	 * @return array
	 */
	public function editor_labels() {
		return array(
			'product_manual'           => __( 'Input product ID', 'jet-form-builder' ),
			'product_id_from'          => __( 'Get product ID from', 'jet-form-builder' ),
			'product_id_field'         => __( 'Product ID field', 'jet-form-builder' ),
			'wc_price'                 => __( 'WooCommerce Price field', 'jet-form-builder' ),
			'wc_order_details'         => __( 'WooCommerce order details', 'jet-form-builder' ),
			'wc_fields_map'            => __( 'WooCommerce checkout fields map', 'jet-form-builder' ),
			'wc_details__type'         => __( 'Type', 'jet-form-builder-woo-action' ),
			'wc_details__label'        => __( 'Label', 'jet-form-builder-woo-action' ),
			'wc_details__date_format'  => __( 'Date format', 'jet-form-builder-woo-action' ),
			'wc_details__field'        => __( 'Select form field', 'jet-form-builder-woo-action' ),
			'wc_details__link_label'   => __( 'Link text', 'jet-form-builder-woo-action' ),
			'wc_heading_order_details' => __( 'Heading for Order Details', 'jet-form-builder-woo-action' ),
		);
	}

}
