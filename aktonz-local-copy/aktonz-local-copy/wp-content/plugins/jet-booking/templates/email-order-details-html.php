<?php
/**
 * WooCommerce email order details HTML.
 *
 * This template can be overridden by copying it to yourtheme/jet-booking/email-order-details-html.php.
 */
?>
<h2> <?php _e( 'Booking Details', 'jet-booking' ); ?></h2>

<ul>
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