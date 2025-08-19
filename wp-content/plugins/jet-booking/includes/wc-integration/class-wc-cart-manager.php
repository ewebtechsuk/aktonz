<?php

namespace JET_ABAF\WC_Integration;

use JET_ABAF\Cron\Manager as Cron_Manager;
use JET_ABAF\Price;

class WC_Cart_Manager {

	/**
	 * Schedule.
	 *
	 * Schedule event holder.
	 *
	 * @since  3.0.0
	 * @access private
	 *
	 * @var array|false|mixed
	 */
	private $schedule;

	public function __construct() {

		$this->schedule = Cron_Manager::instance()->get_schedules( 'jet-booking-clear-on-expire' );

		// Validate data before adding product to cart.
		add_filter( 'woocommerce_add_to_cart_validation', [ $this, 'validate_custom_fields_data' ], 10, 3 );

		// Add posted booking data to the cart item.
		add_filter( 'woocommerce_add_cart_item_data', [ $this, 'add_custom_cart_item_data' ], 10, 4 );

		// Adjust the price of the booking product based on booking properties
		add_filter( 'woocommerce_add_cart_item', [ $this, 'add_cart_item' ], 10, 2 );
		add_filter( 'woocommerce_get_cart_item_from_session', [ $this, 'get_cart_item_from_session' ], 10, 3 );

		// Handle removed and restored cart item.
		add_action( 'woocommerce_cart_item_removed', [ $this, 'cart_item_removed' ], 20 );
		add_action( 'woocommerce_cart_item_restored', [ $this, 'cart_item_restored' ], 20, 2 );

		// Remove expired cart items.
		add_action( 'woocommerce_cart_loaded_from_session', [ $this, 'remove_expired_cart_items' ] );

		// Add booking cart item info.
		add_filter( 'jet-booking/wc-integration/cart-info', [ $this, 'add_booking_cart_info' ], 10, 4 );

	}

	/**
	 * Validate custom fields data.
	 *
	 * Validate booking product custom dates input field value when a booking is added to the cart.
	 *
	 * @since  3.0.0
	 * @since  3.6.0 Added guests options validation.
	 * @since  3.7.0 Added user email field & time restriction validation.
	 *
	 * @param bool  $passed     Validation.
	 * @param int   $product_id Product ID.
	 * @param mixed $qty        Products quantity.
	 *
	 * @return bool
	 */
	public function validate_custom_fields_data( $passed, $product_id, $qty ) {

		$product = wc_get_product( $product_id );

		if ( ! jet_abaf()->wc->mode->is_booking_product( $product ) ) {
			return $passed;
		}

		if ( empty( $_POST['jet_abaf_field'] ) ) {
			wc_add_notice( __( 'Dates is a required field(s).', 'jet-booking' ), 'error' );

			return false;
		}

		if ( jet_abaf()->settings->get( 'timepicker' ) && ( empty( $_POST['check-in-time'] ) || empty( $_POST['check-out-time'] ) ) ) {
			wc_add_notice( __( 'Time is a required field(s).', 'jet-booking' ), 'error' );

			return false;
		}

		if ( ! jet_abaf()->settings->get( 'disable_email_field' ) ) {
			if ( empty( $_POST['jet_abaf_user_email'] ) ) {
				wc_add_notice( __( 'E-mail address is a required field.', 'jet-booking' ), 'error' );

				return false;
			} else if ( ! is_email( $_POST['jet_abaf_user_email'] ) ) {
				wc_add_notice( __( 'E-mail address is invalid.', 'jet-booking' ), 'error' );

				return false;
			}
		}

		if ( ! empty( $_POST['jet_abaf_guests'] ) ) {
			$message    = '';
			$min_guests = get_post_meta( $product->get_id(), '_jet_booking_min_guests', true ) ?: 1;
			$max_guests = get_post_meta( $product->get_id(), '_jet_booking_max_guests', true ) ?: 1;

			if ( $_POST['jet_abaf_guests'] < $min_guests ) {
				/* translators: minimum guests number */
				$message = sprintf( __( 'The minimum guests number per booking is %s.', 'jet-booking' ), $min_guests );
			} elseif ( $_POST['jet_abaf_guests'] > $max_guests ) {
				/* translators: maximum guests number */
				$message = sprintf( __( 'The maximum guests number per booking is %s.', 'jet-booking' ), $max_guests );
			}

			if ( ! empty( $message ) ) {
				wc_add_notice( $message, 'error' );

				return false;
			}
		}

		$dates   = jet_abaf()->wc->mode->get_transformed_dates( $_POST['jet_abaf_field'], $product_id );
		$booking = [
			'apartment_id'   => $product_id,
			'check_in_date'  => absint( $dates[0] ) + 1,
			'check_out_date' => $dates[0] === $dates[1] ? absint( $dates[1] ) + 12 * HOUR_IN_SECONDS : $dates[1],
		];

		if ( ! empty( $_POST['jet_abaf_units'] ) ) {
			$booked_units = jet_abaf()->db->get_booked_units( $booking );

			foreach ( $booked_units as $booked_unit ) {
				if ( absint( $_POST['jet_abaf_units'] ) === absint( $booked_unit['apartment_unit'] ) ) {
					wc_add_notice( __( 'Booking could not be added. Selected unit is no longer available.', 'jet-booking' ), 'error' );

					return false;
				}
			}

			$booking['apartment_unit'] = $_POST['jet_abaf_units'];
		} else {
			$booking['apartment_unit'] = jet_abaf()->db->get_available_unit( $booking );
		}

		$is_available       = jet_abaf()->db->booking_availability( $booking );
		$is_dates_available = jet_abaf()->db->is_booking_dates_available( $booking );

		if ( ! $is_available && ! $is_dates_available ) {
			wc_add_notice( __( 'Booking could not be added. Selected dates are no longer available.', 'jet-booking' ), 'error' );

			return false;
		}

		return $passed;

	}

	/**
	 * Add custom cart item data.
	 *
	 * Add posted custom booking data to cart item.
	 *
	 * @since  3.0.0
	 * @since  3.3.0 Fixed One-day bookings.
	 * @since  3.6.0 Added attributes & guests handling.
	 * @since  3.7.0 Added user email & check in/out time handling.
	 *
	 * @param array $cart_item_data Cart items list..
	 * @param int   $product_id     ID of the added product.
	 * @param int   $variation_id   ID of the added product variation.
	 * @param int   $quantity       Quantity of items.
	 *
	 * @return array
	 */
	public function add_custom_cart_item_data( $cart_item_data, $product_id, $variation_id, $quantity ) {

		$product = wc_get_product( $product_id );

		if ( jet_abaf()->wc->mode->is_booking_product( $product ) ) {
			$dates      = jet_abaf()->wc->mode->get_transformed_dates( $_POST['jet_abaf_field'], $product_id );
			$user_email = $_POST['jet_abaf_user_email'] ?? '';

			if ( empty( $user_email ) && is_user_logged_in() ) {
				$user       = wp_get_current_user();
				$user_email = $user->user_email ?? '';
			}

			$args = [
				'apartment_id'   => $product_id,
				'apartment_unit' => $_POST['jet_abaf_units'] ?? '',
				'status'         => 'on-hold',
				'check_in_date'  => $dates[0],
				'check_out_date' => $dates[1],
				'user_email'     => $user_email
			];

			if ( jet_abaf()->settings->get( 'timepicker' ) ) {
				$args['check_in_time']  = $_POST['check-in-time'];
				$args['check_out_time'] = $_POST['check-out-time'];
			}

			if ( is_user_logged_in() ) {
				$args['user_id'] = get_current_user_id();
			}

			$booking_id = jet_abaf()->db->insert_booking( $args );

			if ( $booking_id ) {
				$args       = jet_abaf()->db->inserted_booking;
				$attributes = array_filter( $product->get_attributes(), 'wc_attributes_array_filter_visible' );

				foreach ( $attributes as $attribute ) {
					if ( $attribute->is_taxonomy() && ! empty( $_POST[ 'jet_abaf_product_' . $attribute->get_name() ] ) ) {
						$args[ jet_abaf()->wc->mode->additional_services_key ][ $attribute->get_name() ] = $_POST[ 'jet_abaf_product_' . $attribute->get_name() ];
					}
				}

				$has_guests = get_post_meta( $product->get_id(), '_jet_booking_has_guests', true );

				if ( filter_var( $has_guests, FILTER_VALIDATE_BOOLEAN ) && ! empty( $_POST['jet_abaf_guests'] ) ) {
					$args['__guests'] = $_POST['jet_abaf_guests'];
				}

				$cart_item_data[ jet_abaf()->wc->data_key ] = apply_filters( 'jet-booking/wc-integration/cart-item-data', $args, $cart_item_data, $product, $variation_id, $quantity );

				$this->schedule->schedule_single_event( [ $booking_id ] );

				do_action( 'jet-booking/wc-integration/booking-inserted', $booking_id );
			}
		}

		return $cart_item_data;

	}

	/**
	 * Add cart item.
	 *
	 * Adjust the price of the booking product based on booking properties.
	 *
	 * @since  3.0.0
	 * @since  3.6.0 Added attributes & guests cost calculation.
	 *
	 * @param array  $cart_item_data WooCommerce cart item data array.
	 * @param string $cart_item_key  WooCommerce cart item key.
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function add_cart_item( $cart_item_data, $cart_item_key ) {

		$product = $cart_item_data['data'];

		if ( jet_abaf()->wc->mode->is_booking_product( $product ) && ! empty( $cart_item_data[ jet_abaf()->wc->data_key ] ) ) {
			$booking_data  = $cart_item_data[ jet_abaf()->wc->data_key ];
			$price         = new Price( $booking_data['apartment_id'] );
			$booking_price = $price->get_booking_price( $booking_data );
			$guests        = 0;
			$has_guests    = get_post_meta( $product->get_id(), '_jet_booking_has_guests', true );

			if ( filter_var( $has_guests, FILTER_VALIDATE_BOOLEAN ) && ! empty( $booking_data['__guests'] ) ) {
				$guests            = $booking_data['__guests'];
				$guests_multiplier = get_post_meta( $product->get_id(), '_jet_booking_guests_multiplier', true );

				if ( filter_var( $guests_multiplier, FILTER_VALIDATE_BOOLEAN ) ) {
					$booking_price *= $guests;
				}
			}

			if ( ! empty( $booking_data[ jet_abaf()->wc->mode->additional_services_key ] ) ) {
				$interval      = jet_abaf()->tools->get_booking_period_interval( $booking_data['check_in_date'], $booking_data['check_out_date'], $booking_data['apartment_id'] );
				$booking_price += jet_abaf()->wc->mode->attributes->get_attributes_cost( $booking_data[ jet_abaf()->wc->mode->additional_services_key ], $interval, $booking_data['apartment_id'], $guests );
			}

			$product->set_price( $booking_price );
		}

		return $cart_item_data;

	}

	/**
	 * Get cart item from session.
	 *
	 * Get data from the session and add to the cart item's meta.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @param array  $cart_item_data WooCommerce cart item data array.
	 * @param array  $values         Cart values array.
	 * @param string $cart_item_key  WooCommerce cart item key.
	 *
	 * @return array|mixed
	 */
	public function get_cart_item_from_session( $cart_item_data, $values, $cart_item_key ) {

		if ( ! empty( $values[ jet_abaf()->wc->data_key ] ) ) {
			$cart_item_data[ jet_abaf()->wc->data_key ] = $values[ jet_abaf()->wc->data_key ];
			$cart_item_data                             = $this->add_cart_item( $cart_item_data, $cart_item_key );
		}

		return $cart_item_data;

	}

	/**
	 * Remove cart item.
	 *
	 * Delete and clear schedule for JetBooking product.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @param string $cart_item_key Removed cart item in identifier.
	 *
	 * @return void
	 */
	public function cart_item_removed( $cart_item_key ) {

		$cart_item = WC()->cart->removed_cart_contents[ $cart_item_key ];

		if ( ! empty( $cart_item[ jet_abaf()->wc->data_key ] ) ) {
			$booking_id = $cart_item[ jet_abaf()->wc->data_key ]['booking_id'];

			jet_abaf()->db->delete_booking( [ 'booking_id' => $booking_id ] );
			$this->schedule->unschedule_single_event( [ $booking_id ] );
		}

	}

	/**
	 * Cart item restored.
	 *
	 * Restore cart item as well as related booking, reschedule expired event.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @param string   $cart_item_key Removed cart item in identifier.
	 * @param \WC_Cart $cart          WooCommerce cart instance.
	 *
	 * @return void
	 */
	public function cart_item_restored( $cart_item_key, $cart ) {

		$cart_items = $cart->get_cart();
		$cart_item  = $cart_items[ $cart_item_key ];

		if ( jet_abaf()->wc->mode->is_booking_product( $cart_item['data'] ) && ! empty( $cart_item[ jet_abaf()->wc->data_key ] ) ) {
			$booking_id = jet_abaf()->db->insert_booking( $cart_item[ jet_abaf()->wc->data_key ] );

			if ( $booking_id ) {
				$this->schedule->schedule_single_event( [ $booking_id ] );

				do_action( 'jet-booking/wc-integration/booking-inserted', $booking_id );
			}
		}

	}

	/**
	 * Remove expired cart items.
	 *
	 * Check for invalid bookings and remove related cart items.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @param \WC_Cart $cart WooCommerce cart instance.
	 *
	 * @return void
	 */
	public function remove_expired_cart_items( $cart ) {

		$titles       = [];
		$titles_count = 0;

		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
			if ( jet_abaf()->wc->mode->is_booking_product( $cart_item['data'] ) && ! empty( $cart_item[ jet_abaf()->wc->data_key ] ) ) {
				$booking = jet_abaf_get_booking( $cart_item[ jet_abaf()->wc->data_key ]['booking_id'] );

				if ( ! $booking ) {
					unset( $cart->cart_contents[ $cart_item_key ] );

					$cart->calculate_totals();

					if ( $cart_item['product_id'] ) {
						$title = '<a href="' . get_permalink( $cart_item['product_id'] ) . '">' . get_the_title( $cart_item['product_id'] ) . '</a>';

						if ( ! in_array( $title, $titles, true ) ) {
							$titles[] = $title;
						}

						$titles_count ++;
					}
				}
			}
		}

		if ( $titles_count < 1 ) {
			return;
		}

		$formatted_titles = wc_format_list_of_items( $titles );
		$notice           = sprintf( __( 'A booking for %s has been removed from your cart due to inactivity.', 'jet-booking' ), $formatted_titles );

		if ( $titles_count > 1 ) {
			$notice = sprintf( __( 'Bookings for %s have been removed from your cart due to inactivity.', 'jet-booking' ), $formatted_titles );
		}

		wc_add_notice( $notice, 'notice' );

	}

	/**
	 * Add booking cart info.
	 *
	 * Extend cart info data with booking related information.
	 *
	 * @since 3.6.0
	 * @since 3.7.1 Added units info display.
	 *
	 * @param array      $result    List of cart info.
	 * @param array      $data      Booking data list.
	 * @param array      $form_data Submitted form data list.
	 * @param string|int $form_id   Submitted form id.
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function add_booking_cart_info( $result, $data, $form_data, $form_id ) {

		if ( ! empty( $data['apartment_unit'] ) ) {
			$booking = jet_abaf_get_booking( $data['booking_id'] );

			if ( $booking ) {
				array_unshift( $result, [
					'key'     => __( 'Unit', 'jet-booking' ),
					'display' => jet_abaf()->macros->macros_handler->do_macros( '%booking_unit_title%', $booking ),
				] );
			}
		}

		if ( ! empty( $data['__guests'] ) ) {
			$result[] = [
				'key'     => __( 'Guests', 'jet-booking' ),
				'display' => $data['__guests'],
			];
		}

		if ( ! empty( $data[ jet_abaf()->wc->mode->additional_services_key ] ) ) {
			$interval   = jet_abaf()->tools->get_booking_period_interval( $data['check_in_date'], $data['check_out_date'], $data['apartment_id'] );
			$attributes = jet_abaf()->wc->mode->attributes->get_attributes_for_display( $data[ jet_abaf()->wc->mode->additional_services_key ], $interval, $data['apartment_id'] );

			foreach ( $attributes as $attribute ) {
				$result[] = [
					'key'     => $attribute['label'],
					'display' => $attribute['value'],
				];
			}
		}

		return $result;

	}

}
