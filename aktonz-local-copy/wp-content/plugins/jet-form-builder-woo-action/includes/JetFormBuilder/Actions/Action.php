<?php

namespace Jet_FB_Woo\JetFormBuilder\Actions;

use Jet_FB_Woo\Plugin;
use Jet_Form_Builder\Actions\Types\Base;
use Jet_Form_Builder\Actions\Action_Handler;
use Jet_Form_Builder\Exceptions\Action_Exception;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Define Base_Type class
 */
class Action extends Base {

	public function get_id() {
		return 'redirect_to_woo_checkout';
	}

	public function get_name() {
		return __( 'Add to Cart & Redirect to Checkout', 'jet-form-builder-woo-action' );
	}

	public function dependence() {
		return class_exists( '\WooCommerce' );
	}

	/**
	 * @param array $request
	 * @param Action_Handler $handler
	 *
	 * @return void
	 * @throws Action_Exception
	 */
	public function do_action( array $request, Action_Handler $handler ) {
		$product_id = apply_filters(
			'jet-form-builder/action/redirect_to_woo_checkout/product-id',
			absint( $this->get_product_id() ),
			$this
		);

		if ( ! $product_id ) {
			throw new Action_Exception( 'Undefined product ID' );
		}

		if ( 'product' !== get_post_type( $product_id ) ) {
			throw new Action_Exception( 'The post is not a product' );
		}

		$cart_item_data = array(
			Plugin::instance()->wc->form_data_key       => $request,
			Plugin::instance()->wc->form_id_key         => jet_fb_action_handler()->form_id,
			Plugin::instance()->wc->action_settings_key => $this->settings,
		);

		$custom_price_field = $this->settings['wc_price'] ?? '';
		$custom_price       = $request[ $custom_price_field ] ?? false;

		if ( is_numeric( $custom_price ) && $custom_price > 0 ) {
			$cart_item_data[ Plugin::instance()->wc->price_key ] = floatval( $custom_price );
		}

		do_action(
			'jet-form-builder/action/redirect_to_woo_checkout/before-add',
			$this
		);

		try {
			WC()->cart->add_to_cart(
				...apply_filters(
					'jet-form-builder/action/redirect_to_woo_checkout/add-to-cart',
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
			throw new Action_Exception( esc_html( $exception->getMessage() ) );
		}

		$checkout_fields_map = array();

		foreach ( $this->settings as $key => $value ) {
			if ( false !== strpos( $key, 'wc_fields_map__' ) && ! empty( $value ) ) {
				$checkout_fields_map[ str_replace( 'wc_fields_map__', '', $key ) ] = $value;
			}
		}

		$checkout_fields_map = apply_filters(
			'jet-form-builder/action/redirect_to_woo_checkout/fields-map',
			$checkout_fields_map
		);

		if ( ! empty( $checkout_fields_map ) ) {
			$checkout_fields = array();

			foreach ( $checkout_fields_map as $checkout_field => $form_field ) {
				if ( array_key_exists( $form_field, $request ) ) {
					$checkout_fields[ $checkout_field ] = $request[ $form_field ];
				}
			}

			$checkout_fields = apply_filters(
				'jet-form-builder/action/redirect_to_woo_checkout/fields-map/values',
				$checkout_fields
			);

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
		$from = $this->settings['product_id_from'] ?? 'manual';

		switch ( $from ) {
			case 'manual':
				return $this->get_product_manual( 0, 'id' );
			case 'field':
				return $this->get_product_from_field( 0 );
		}

		return 0;
	}

	public function get_product_manual( $index, $key = '', $if_not_exist = false ) {
		$manual = $this->settings['product_manual'] ?? array();

		if ( ! isset( $manual[ $index ] ) ) {
			return $if_not_exist;
		}

		if ( ! $key ) {
			return $manual[ $index ];
		}

		return $manual[ $index ][ $key ] ?? $if_not_exist;
	}

	public function get_product_from_field( $if_not_exist = false ) {
		if ( ! jet_fb_context()->has_field( $this->settings['product_id_field'] ?? '' ) ) {
			return $if_not_exist;
		}
		return jet_fb_context()->get_value( $this->settings['product_id_field'] );
	}

	public function action_data() {
		$form_id = get_the_ID();
		$details = Plugin::instance()->wc->get_details_schema( $form_id );
		$details = empty( $details ) ? array() : $details;
		$nonce   = wp_create_nonce( Plugin::instance()->wc->meta_key );

		return array(
			'wc_fields'       => $this->get_checkout_fields(),
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

	public function get_checkout_fields(): array {
		$fields = array();

		foreach ( Plugin::instance()->wc->get_checkout_fields() as $field ) {
			$fields[] = array(
				'value' => $field,
				'label' => $field,
			);
		}

		return $fields;
	}

	public function self_script_name() {
		return 'JetActionWooCheckout';
	}

}
