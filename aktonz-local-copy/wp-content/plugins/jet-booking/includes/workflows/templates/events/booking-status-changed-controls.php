<cx-vui-select
	v-if="'booking-status-changed' === item.event"
	label="<?php _e( 'Status', 'jet-booking' ); ?>"
	description="<?php _e( 'Trigger event if bookings status changed to selected status.', 'jet-booking' ); ?>"
	:wrapper-css="[ 'equalwidth' ]"
	size="fullwidth"
	:options-list="<?php echo htmlspecialchars( json_encode( $statuses ) ); ?>"
	:value="item.status"
	@input="updateItem( $event, 'status' )"
></cx-vui-select>