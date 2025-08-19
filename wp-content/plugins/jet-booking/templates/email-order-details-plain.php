<?php
/**
 * WooCommerce email order details plain.
 *
 * This template can be overridden by copying it to yourtheme/jet-booking/email-order-details-plain.php.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

_e( 'Booking Details', 'jet-booking' );

foreach ( $details as $item ) {
	_e( '-', 'jet-booking' );

	if ( ! empty( $item['key'] ) ) {
		echo $item['key'] . ': ';
	}

	if ( ! empty( $item['is_html'] ) ) {
		echo $item['display_plain'];
	} else {
		echo $item['display'];
	}
}