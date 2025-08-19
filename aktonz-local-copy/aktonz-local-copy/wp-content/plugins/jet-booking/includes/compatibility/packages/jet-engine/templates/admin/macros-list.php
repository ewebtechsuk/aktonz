<?php defined( 'ABSPATH' ) || exit; ?>

<br>
<div>
	<b>
		<?php esc_html_e( 'Booking macros:', 'jet-booking' ); ?>
	</b>

	<br>

	<i>%ADVANCED_PRICE::_check_in_out%</i>
	<?php esc_html_e( ' - The macro will return the advanced rate times the number of days booked.', 'jet-booking' ); ?>

	<br>

	<i>_check_in_out</i>
	<?php esc_html_e( ' - is the name of the field that returns the number of days booked.', 'jet-booking' ); ?>

	<br><br>

	<i>%META::_apartment_price%</i>
	<?php esc_html_e( ' - Macro returns price per 1 day / night', 'jet-booking' ); ?>
</div>
