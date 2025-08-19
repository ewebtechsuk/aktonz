<?php

namespace JET_ABAF\WC_Integration\Modes;

use JET_ABAF\Price;
use JET_ABAF\Settings;
use JET_ABAF\WC_Integration\WC_Order_Details_Builder;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Plain {

	/**
	 * Is enabled.
	 *
	 * WooCommerce integration status holder.
	 *
	 * @since  3.0.0 Fixed typo.
	 * @access private
	 *
	 * @var bool
	 */
	private $is_enabled = false;

	/**
	 * Product ID.
	 *
	 * Holds product ID for plain integration.
	 *
	 * @access private
	 *
	 * @var int
	 */
	private $product_id = 0;

	/**
	 * Product key.
	 *
	 * Booking product key holder.
	 *
	 * @access public
	 *
	 * @var string
	 */
	public $product_key = '_is_jet_booking';

	/**
	 * Price key.
	 *
	 * Booking product price key holder.
	 *
	 * @access public
	 *
	 * @var string
	 */
	public $price_key = 'wc_booking_price';

	/**
	 * Price adjusted.
	 *
	 * Holds adjustments status.
	 *
	 * @access private
	 *
	 * @var bool
	 */
	private $price_adjusted = false;

	/**
	 * Details.
	 *
	 * WC order details builder object instance holder.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @var WC_Order_Details_Builder|null
	 */
	public $details = null;

	public function __construct() {

		add_action( 'jet-abaf/settings/before-write', [ $this, 'maybe_set_required_mode_settings' ] );

		$this->is_enabled = jet_abaf()->settings->wc_integration_enabled();
		$this->product_id = jet_abaf()->settings->get( 'wc_product_id' );

		if ( ! $this->get_status() || ! $this->get_product_id() ) {
			return;
		}

		// Set booking admin page config;
		add_filter( 'jet-abaf/dashboard/helpers/page-config/config', [ $this, 'set_booking_page_config' ] );

		// Form-related
		add_action( 'jet-abaf/form/notification/success', [ $this, 'process_wc_notification' ], 10, 2 );
		add_action( 'jet-abaf/jet-fb/action/success', [ $this, 'process_wc_notification' ], 10, 2 );

		// Cart related
		add_filter( 'woocommerce_get_cart_contents', [ $this, 'set_booking_price' ] );
		add_filter( 'woocommerce_cart_item_name', [ $this, 'set_booking_item_name' ], 10, 2 );
		add_filter( 'woocommerce_checkout_get_value', [ $this, 'maybe_set_checkout_defaults' ], 10, 2 );
		add_filter( 'woocommerce_cart_item_permalink', [ $this, 'woocommerce_cart_item_permalink' ], 10, 3 );
		add_filter( 'woocommerce_cart_item_thumbnail', [ $this, 'woocommerce_cart_item_thumbnail' ], 10, 3 );
		// Handle related cart item on booking item manipulations.
		add_action( 'woocommerce_cart_loaded_from_session', [ $this, 'maybe_remove_booking_cart_item' ] );

		// Order related
		add_action( 'woocommerce_before_checkout_process', [ $this, 'pre_process_order' ], 10, );
		add_filter( 'woocommerce_order_item_name', [ $this, 'set_booking_item_name' ], 10, 2 );
		add_action( 'woocommerce_thankyou', [ $this, 'order_details' ], 0 );
		add_action( 'woocommerce_view_order', [ $this, 'order_details' ], 0 );
		add_action( 'woocommerce_email_order_meta', [ $this, 'email_order_details' ], 0, 3 );
		add_action( 'woocommerce_admin_order_data_after_shipping_address', [ $this, 'admin_order_details' ] );
		add_action( 'jet-booking/rest-api/add-booking/set-related-order-data', [ $this, 'set_booking_related_order' ], 10, 3 );

		if ( jet_abaf()->settings->get( 'wc_sync_orders' ) ) {
			add_action( 'jet-booking/db/booking-updated', [ $this, 'update_order_on_status_update' ], 10 );
			// Prevent circular status updates.
			add_action( 'jet-booking/wc-integration/before-set-order-data', function () {
				remove_action( 'jet-booking/db/booking-updated', [ $this, 'update_order_on_status_update' ], 10 );
			} );
		}

		require_once JET_ABAF_PATH . 'includes/wc-integration/class-wc-order-details-builder.php';
		$this->details = new WC_Order_Details_Builder();

		add_action( 'wp_login', [ $this, 'reset_saved_cart_after_login' ], 20, 2 );

	}

	/**
	 * Maybe set required mode settings.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @param Settings $settings Plugin settings object instance.
	 *
	 * @return void
	 */
	public function maybe_set_required_mode_settings( $settings ) {

		if ( ! $settings->wc_integration_enabled() ) {
			return;
		}

		$product_id = $this->get_product_id_from_db() ? $this->get_product_id_from_db() : $settings->get( 'wc_product_id' );
		$product    = get_post( $product_id );

		if ( ! $product || $product->post_status !== 'publish' ) {
			$product_id = $this->create_booking_product();
		}

		$settings->update( 'wc_product_id', $product_id, false );

	}

	/**
	 * Get status.
	 *
	 * Return WooCommerce integration status.
	 *
	 * @since  3.0.0 Fixed typo.
	 * @access public
	 *
	 * @return bool
	 */
	public function get_status() {
		return $this->is_enabled;
	}

	/**
	 * Get product ID.
	 *
	 * Return WooCommerce plain integration product ID.
	 *
	 * @access public
	 *
	 * @return int|mixed
	 */
	public function get_product_id() {
		return $this->product_id;
	}

	/**
	 * Process wc notification.
	 *
	 * Process WooCommerce related notification part.
	 *
	 * @param array  $booking Booking arguments.
	 * @param object $action  Smart Notification Action Trait object instance.
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function process_wc_notification( $booking, $action ) {

		if ( filter_var( $action->getSettings( 'disable_wc_integration' ), FILTER_VALIDATE_BOOLEAN ) ) {
			return;
		}

		$cart_item_data = [
			jet_abaf()->wc->data_key      => $booking,
			jet_abaf()->wc->form_data_key => $action->getRequest(),
			jet_abaf()->wc->form_id_key   => $action->getFormId(),
		];

		$price_field = $action->getSettings( 'booking_wc_price' );
		$price       = ( $price_field && $action->issetRequest( $price_field ) ) ? floatval( $action->getRequest( $price_field ) ) : false;

		if ( $price ) {
			$cart_item_data[ $this->price_key ] = $price;
		}

		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $this->get_product_id(), 1, 0, [], $cart_item_data );

		$checkout_fields_map = [];

		foreach ( $action->getSettings() as $key => $value ) {
			if ( false !== strpos( $key, 'wc_fields_map__' ) && ! empty( $value ) ) {
				$checkout_fields_map[ str_replace( 'wc_fields_map__', '', $key ) ] = $value;
			}
		}

		if ( ! empty( $checkout_fields_map ) ) {
			$checkout_fields = [];

			foreach ( $checkout_fields_map as $checkout_field => $form_field ) {
				if ( $action->issetRequest( $form_field ) ) {
					$checkout_fields[ $checkout_field ] = $action->getRequest( $form_field );
				}
			}

			if ( ! empty( $checkout_fields ) ) {
				WC()->session->set( 'jet_booking_fields', $checkout_fields );
			}
		}

		$action->filterQueryArgs( function ( $query_args, $handler, $args ) use ( $action ) {

			$url = apply_filters( 'jet-engine/forms/handler/wp_redirect_url', wc_get_checkout_url() );

			if ( $action->isAjax() ) {
				$query_args['redirect'] = $url;

				return $query_args;
			} else {
				wp_redirect( $url );
				die();
			}

		} );

	}

	/**
	 * Set booking price.
	 *
	 * Set custom price per booking item.
	 *
	 * @since  2.8.0 Refactored.
	 * @since  3.0.0 New way of calculating fallback booking price.
	 * @access public
	 *
	 * @param array $cart_items List cart items.
	 *
	 * @return mixed
	 */
	public function set_booking_price( $cart_items ) {

		if ( $this->price_adjusted || empty( $cart_items ) ) {
			return $cart_items;
		}

		foreach ( $cart_items as $item ) {
			if ( ! empty( $item[ jet_abaf()->wc->data_key ] ) ) {
				if ( ! empty( $item[ $this->price_key ] ) ) {
					$booking_price = $item[ $this->price_key ];
				} else {
					$price         = new Price( $item[ jet_abaf()->wc->data_key ]['apartment_id'] );
					$booking_price = $price->get_booking_price( $item[ jet_abaf()->wc->data_key ] );
				}

				if ( $booking_price ) {
					$item['data']->set_price( floatval( $booking_price ) );
				}

				$this->price_adjusted = true;
			}
		}

		return $cart_items;

	}

	/**
	 * Pre-process order.
	 *
	 * Checks if booking already exists and throws an exception.
	 *
	 * @since  3.0.1
	 * @access public
	 *
	 * @return void
	 */
	public function pre_process_order() {
		try {
			foreach ( WC()->cart->get_cart_contents() as $item ) {
				if ( ! empty( $item[ jet_abaf()->wc->data_key ] ) ) {
					$is_available       = jet_abaf()->db->booking_availability( $item[ jet_abaf()->wc->data_key ] );
					$is_dates_available = jet_abaf()->db->is_booking_dates_available( $item[ jet_abaf()->wc->data_key ] );

					if ( ! $is_available && ! $is_dates_available ) {
						throw new \Exception( __( 'The dates you have selected are already booked. Please select different dates.', 'jet-booking' ) );
					}
				}
			}
		} catch ( \Exception $e ) {
			wc_add_notice( $e->getMessage(), 'error' );
		}
	}

	/**
	 * Set booking name.
	 *
	 * Set booking item name for checkout, thank you page and e-mail order details.
	 *
	 * @since 2.0.0
	 * @since 2.6.0 Refactor code. Added orders item name handling. Added link for items names.
	 * @since 3.0.0 Small improvements, naming.
	 *
	 * @param string       $product_name HTML for the product name.
	 * @param array|object $item         WooCommerce item data list or Instance.
	 *
	 * @return string;
	 */
	public function set_booking_item_name( $product_name, $item ) {

		$booking = [];

		if ( ! empty( $item[ jet_abaf()->wc->data_key ] ) ) {
			$booking = $item[ jet_abaf()->wc->data_key ];
		} elseif ( is_object( $item ) && $this->get_product_id() === $item->get_product_id() ) {
			$booking = $this->get_booking_by_order_id( $item->get_order_id() );
		}

		$apartment_id = ! empty( $booking['apartment_id'] ) ? absint( $booking['apartment_id'] ) : false;
		$apartment_id = apply_filters( 'jet-booking/wc-integration/apartment-id', $apartment_id );

		if ( ! $apartment_id ) {
			return $product_name;
		}

		return sprintf( '%s: <a href="%s">%s</a>', $this->get_apartment_label(), get_permalink( $apartment_id ), get_the_title( $apartment_id ) );

	}

	/**
	 * Maybe set checkout defaults.
	 *
	 * Set checkout default fields values for checkout forms.
	 *
	 * @since  2.8.0 Refactored.
	 * @access public
	 *
	 * @param mixed  $value Initial value of the input.
	 * @param string $input Name of the input we want to set data for. e.g. billing_country.
	 *
	 * @return mixed The default value.
	 */
	public function maybe_set_checkout_defaults( $value, $input ) {
		if ( function_exists( 'WC' ) && WC()->session ) {
			$fields = WC()->session->get( 'jet_booking_fields' );

			if ( ! empty( $fields ) && ! empty( $fields[ $input ] ) ) {
				return $fields[ $input ];
			} else {
				return $value;
			}
		} else {
			return $value;
		}
	}

	/**
	 * Replaces Booking product thumbnail with Apartment featured image in the product cart.
	 *
	 * @param bool   $permalink     Product permalink usage.
	 * @param array  $cart_item     The product in the cart.
	 * @param string $cart_item_key Key for the product in the cart.
	 *
	 * @return bool|string Product permalink usage.
	 */
	public function woocommerce_cart_item_permalink( $permalink, $cart_item, $cart_item_key ) {

		if ( ! empty( $cart_item[ jet_abaf()->wc->data_key ] ) ) {
			return '';
		}

		return $permalink;

	}

	/**
	 * Replaces Booking product thumbnail with Apartment featured image in the product cart.
	 *
	 * @param string $thumbnail     HTML img element.
	 * @param array  $cart_item     The product in the cart.
	 * @param string $cart_item_key Key for the product in the cart.
	 *
	 * @return string HTML img element or empty string on failure.
	 */
	public function woocommerce_cart_item_thumbnail( $thumbnail, $cart_item, $cart_item_key ) {

		if ( ! empty( $cart_item[ jet_abaf()->wc->data_key ] ) ) {
			$thumbnail = get_the_post_thumbnail( $cart_item[ jet_abaf()->wc->data_key ]['apartment_id'], 'woocommerce_thumbnail' );
		}

		return $thumbnail;

	}

	/**
	 * Remove booking cart item if the associated booking no longer exists.
	 *
	 * This function iterates through the cart items, checks for booking products,
	 * and removes them from the cart if the corresponding booking entry is not found in the database.
	 *
	 * @since 3.6.4
	 *
	 * @param \WC_Cart $cart WooCommerce cart instance.
	 *
	 * @return void
	 */
	public function maybe_remove_booking_cart_item( $cart ) {
		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
			if ( ! isset( $cart_item[ jet_abaf()->wc->data_key ] ) ) {
				continue;
			}

			$booking_data = $cart_item[ jet_abaf()->wc->data_key ];

			if ( $cart_item['data']->get_id() === intval( $this->get_product_id() ) && ! empty( $booking_data ) ) {
				$booking = jet_abaf_get_booking( $booking_data['booking_id'] );

				if ( ! $booking ) {
					unset( $cart->cart_contents[ $cart_item_key ] );

					$cart->calculate_totals();
				}
			}
		}
	}

	/**
	 * Order details.
	 *
	 * Show booking related order details on order page.
	 *
	 * @access public
	 *
	 * @param string|int $order_id WC order ID.
	 *
	 * @return void
	 */
	public function order_details( $order_id ) {
		$this->order_details_template( $order_id );
	}

	/**
	 * Email order details.
	 *
	 * Show booking related order details in order email.
	 *
	 * @access public
	 *
	 * @param \WC_Order $order         Order instance.
	 * @param bool      $sent_to_admin If should sent to admin.
	 * @param bool      $plain_text    If is plain text email.
	 *
	 * @return void
	 */
	public function email_order_details( $order, $sent_to_admin, $plain_text ) {
		$template = $plain_text ? 'email-order-details-plain' : 'email-order-details-html';
		$this->order_details_template( $order->get_id(), $template );
	}

	/**
	 * Admin order details.
	 *
	 * Returns booking order details template in WooCommerce order view.
	 *
	 * @since  2.4.4
	 * @access public
	 *
	 * @param object $order Order instance.
	 *
	 * @return void
	 */
	public function admin_order_details( $order ) {

		$details = $this->get_booking_order_details( $order->get_id() );

		if ( ! $details ) {
			return;
		}

		include JET_ABAF_PATH . 'templates/admin/order/details.php';

	}

	/**
	 * Set booking related order.
	 *
	 * Set WooCommerce related order for booking instance item.
	 *
	 * @since  3.0.0
	 * @since  3.6.0 Added `$booking` parameter.
	 * @since  3.7.1 Added tax handling.
	 *
	 * @param array      $order_data Related order data array.
	 * @param string|int $booking_id Created booking ID.
	 * @param array      $booking    Booking data list.
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function set_booking_related_order( $order_data, $booking_id, $booking ) {

		$order = wc_create_order();

		if ( is_wp_error( $order ) ) {
			return;
		}

		$product_id    = $this->get_product_id();
		$price         = new Price( $booking['apartment_id'] );
		$booking_price = $price->get_booking_price( $booking );

		$product = wc_get_product( $product_id );

		if ( wc_prices_include_tax() ) {
			$booking_price = wc_get_price_excluding_tax( $product, [ 'price' => $booking_price ] );
		}

		$order->add_product( $product, 1, [
			'subtotal' => $booking_price,
			'total'    => $booking_price,
		] );

		$order->set_billing_address( [
			'first_name' => $order_data['firstName'] ?? '',
			'last_name'  => $order_data['lastName'] ?? '',
			'email'      => $order_data['email'] ?? '',
			'phone'      => $order_data['phone'] ?? '',
		] );

		$order->set_status( $booking['status'] );
		$order->calculate_totals();
		$order->save();

		jet_abaf()->db->update_booking( $booking_id, [ 'order_id' => $order->get_id() ] );

	}

	/**
	 * Update order on status update.
	 *
	 * Update an order status on related booking update.
	 *
	 * @since  2.8.0
	 * @since  3.3.0 Refactored.
	 *
	 * @param string|int $booking_id Booking ID.
	 *
	 * @return void
	 */
	public function update_order_on_status_update( $booking_id ) {

		$booking = jet_abaf_get_booking( $booking_id );

		if ( ! $booking || empty( $booking->get_status() ) || empty( $booking->get_order_id() ) ) {
			return;
		}

		$order  = wc_get_order( $booking->get_order_id() );
		$status = $booking->get_status();

		if ( ! $order || $order->get_status() === $status ) {
			return;
		}

		remove_action( 'woocommerce_order_status_changed', [ jet_abaf()->wc, 'update_status_on_order_update' ], 10 );

		$order->update_status( $status, sprintf( __( 'Booking #%d update.', 'jet-booking' ), $booking_id ), true );

	}

	/**
	 * Get product ID from DB.
	 *
	 * Try to get previously created product ID from data base.
	 *
	 * @access public
	 *
	 * @return false|int
	 */
	public function get_product_id_from_db() {

		global $wpdb;

		$product_id = $wpdb->get_var( "SELECT `post_id` FROM $wpdb->postmeta WHERE `meta_key` = '$this->product_key' ORDER BY post_id DESC;" );

		if ( ! $product_id ) {
			return false;
		}

		if ( 'product' !== get_post_type( $product_id ) ) {
			return false;
		}

		return absint( $product_id );

	}

	/**
	 * Create booking product.
	 *
	 * @access public
	 *
	 * @return int
	 */
	public function create_booking_product() {

		$product = new \WC_Product_Simple( 0 );

		$product->set_name( $this->get_product_name() );
		$product->set_status( 'publish' );
		$product->set_price( 1 );
		$product->set_regular_price( 1 );
		$product->set_slug( sanitize_title( $this->get_product_name() ) );
		$product->save();

		$product_id = $product->get_id();

		if ( $product_id ) {
			update_post_meta( $product_id, $this->product_key, true );
		}

		return $product_id;

	}

	/**
	 * Get product name.
	 *
	 * Returns booking product name.
	 *
	 * @access public
	 *
	 * @return string
	 */
	public function get_product_name() {
		return apply_filters( 'jet-abaf/wc-integration/product-name', __( 'Booking', 'jet-booking' ) );
	}

	/**
	 * Get booking by order id.
	 *
	 * Returns booking detail by order id.
	 *
	 * @access public
	 *
	 * @param int|string $order_id WC Order ID.
	 *
	 * @return false|mixed|\stdClass
	 */
	public function get_booking_by_order_id( $order_id ) {

		$booking = jet_abaf()->db->get_booking_by( 'order_id', $order_id );

		if ( ! $booking || ! $booking['apartment_id'] ) {
			return false;
		}

		return $booking;

	}

	/**
	 * Get apartment label.
	 *
	 * Returns apartment custom post type label.
	 *
	 * @access public
	 *
	 * @return null
	 */
	public function get_apartment_label() {

		$cpt = jet_abaf()->settings->get( 'apartment_post_type' );

		if ( ! $cpt ) {
			return null;
		}

		$cpt_object = get_post_type_object( $cpt );

		if ( ! $cpt_object ) {
			return null;
		}

		return $cpt_object->labels->singular_name;

	}

	/**
	 * Order details template.
	 *
	 * @access public
	 *
	 * @param string|int $order_id WC order ID
	 * @param string     $template Template name.
	 *
	 * @return void
	 */
	public function order_details_template( $order_id, $template = 'order-details' ) {

		$details = $this->get_booking_order_details( $order_id );

		if ( ! $details ) {
			return;
		}

		include jet_abaf()->get_template( $template . '.php' );

	}

	/**
	 * Booking order details.
	 *
	 * Returns sanitized booking order details.
	 *
	 * @since  2.4.4
	 * @access public
	 *
	 * @param string|int $order_id WooCommerce order ID.
	 *
	 * @return mixed
	 */
	public function get_booking_order_details( $order_id ) {

		$booking = $this->get_booking_by_order_id( $order_id );

		if ( ! $booking ) {
			return false;
		}

		$details = apply_filters( 'jet-booking/wc-integration/pre-get-order-details', false, $order_id, $booking );

		if ( $details ) {
			return $details;
		}

		$booking_title = get_the_title( $booking['apartment_id'] );
		$details       = [
			[
				'key'     => '',
				'display' => $booking_title,
			]
		];

		$details = wp_parse_args( jet_abaf()->wc->get_formatted_info( $booking ), $details );

		return apply_filters( 'jet-booking/wc-integration/order-details', $details, $order_id, $booking );

	}

	/**
	 * Set booking admin page WC configuration.
	 *
	 * @param array $config Configuration list.
	 *
	 * @return array
	 */
	public function set_booking_page_config( $config ) {

		$config['wc_integration'] = $this->get_status();

		return $config;

	}

	/**
	 * Resets the saved cart for a user after login.
	 *
	 * This function checks if the user's saved cart contains a booking product.
	 * If it does, the saved cart is deleted to prevent conflicts with new bookings.
	 *
	 * @param string   $user_login The user's login name.
	 * @param \WP_User $user       The user object.
	 *
	 * @return void
	 */
	public function reset_saved_cart_after_login( $user_login, $user ) {

		if ( ! $user ) {
			return;
		}

		$saved_cart = get_user_meta( $user->ID, '_woocommerce_persistent_cart_' . get_current_blog_id(), true );

		if ( ! $saved_cart ) {
			return;
		}

		$reset_cart = false;

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			if ( $cart_item['data']->get_id() === intval( $this->get_product_id() ) ) {
				$reset_cart = true;
			}
		}

		if ( $reset_cart ) {
			delete_user_meta( $user->ID, '_woocommerce_persistent_cart_' . get_current_blog_id() );
		}

	}

	/**
	 * Get checkout fields.
	 *
	 * Returns checkout fields list.
	 *
	 * @access public
	 *
	 * @return array
	 */
	public function get_checkout_fields() {
		return apply_filters( 'jet-booking/wc-integration/checkout-fields', [
			'billing_first_name',
			'billing_last_name',
			'billing_email',
			'billing_phone',
			'billing_company',
			'billing_country',
			'billing_address_1',
			'billing_address_2',
			'billing_city',
			'billing_state',
			'billing_postcode',
			'shipping_first_name',
			'shipping_last_name',
			'shipping_company',
			'shipping_country',
			'shipping_address_1',
			'shipping_address_2',
			'shipping_city',
			'shipping_state',
			'shipping_postcode',
			'order_comments',
		] );
	}

}