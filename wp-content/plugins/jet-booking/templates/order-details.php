<?php
/**
 * Order details.
 *
 * This template can be overridden by copying it to yourtheme/jet-booking/order-details.php.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>

<h2 class="woocommerce-order-details__title">
	<?php _e( 'Booking Details', 'jet-booking' ); ?>
</h2>

<ul class="woocommerce-order-overview woocommerce-thankyou-order-details order_details">
	<?php foreach ( $details as $item ) {
		echo '<li>';

		if ( ! empty( $item['key'] ) ) {
			echo $item['key'] . ': ';
		}

		if ( ! empty( $item['is_html'] ) ) {
			echo $item['display'];
		} else {
			echo '<strong>' . $item['display'] . '</strong>';
		}

		echo '</li>';
	} ?>
</ul>