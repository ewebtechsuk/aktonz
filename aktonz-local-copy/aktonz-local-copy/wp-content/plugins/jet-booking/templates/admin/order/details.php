<?php
/**
 * Admin order details.
 *
 * This template can be overridden by copying it to yourtheme/jet-booking/admin/order/details.php.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>

<hr style="clear:both;">

<h3><?php _e( 'Booking Details', 'jet-booking' ); ?></h3>

<p>
	<?php foreach ( $details as $item ) {
		if ( ! empty( $item['key'] ) ) {
			echo $item['key'] . ': ';
		}

		if ( ! empty( $item['is_html'] ) ) {
			echo $item['display'];
		} else {
			echo '<strong>' . $item['display'] . '</strong></br>';
		}
	} ?>
</p>