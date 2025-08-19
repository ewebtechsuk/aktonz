<div>
	<cx-vui-select
		label="<?php _e( 'Booking mode', 'jet-booking' ); ?>"
		description="<?php _e( 'Select the booking mode type. It changes the display and behavior of the booking interface to match the chosen mode.', 'jet-booking' ); ?>"
		:options-list="[
			{
				value: 'plain',
				label: '<?php _e( 'Plain', 'jet-booking' ); ?>',
			},
			{
				value: 'wc_based',
				label: '<?php _e( 'WooCommerce based', 'jet-booking' ); ?>',
			}
		]"
		:wrapper-css="[ 'equalwidth' ]"
		:size="'fullwidth'"
		:value="generalSettings.booking_mode"
		@input="updateSetting( $event, 'booking_mode' )"
	>
		<div v-if="'plain' === generalSettings.booking_mode" class="cx-vui-component__desc">
			<p><?php _e( 'Booking system focusing on the core functionality for managing bookings.', 'jet-booking' ); ?></p>
		</div>
		<div v-else class="cx-vui-component__desc">
			<?php if ( ! jet_abaf()->wc->has_woocommerce() ) {
				printf(
					__( '<p>Requires <a href="%s" target="_blank">WooCommerce</a> to be installed and activated.</p>', 'jet-booking' ),
					add_query_arg( [ 's' => 'woocommerce', 'tab' => 'search', 'type' => 'term' ], admin_url( 'plugin-install.php' ) )
				);
			} else {
				if ( ! jet_abaf()->tools->get_booking_posts() ) {
					printf(
						__( '<p>Create booking products to start using this functionality. <a href="%s" target="_blank">Create the first product</a>.</p>', 'jet-booking' ),
						add_query_arg( [ 'post_type' => 'product', 'jet_booking_product' => 1 ], admin_url( 'post-new.php' ) )
					);
				}
			} ?>

			<p><?php _e( 'Booking system allowing you to create and manage custom products type specifically designed for booking and seamlessly integrate it into online store.', 'jet-booking' ); ?></p>

			<div class="cx-vui-component__meta">
				<a class="jet-abaf-help-link" href="https://crocoblock.com/knowledge-base/jetbooking/how-to-use-booking-with-woocommerce/?utm_source=jetbooking&utm_medium=content&utm_campaign=need-help" target="_blank">
					<span class="dashicons dashicons-editor-help"></span>
					<?php _e( 'What is this and how it works?', 'jet-booking' ); ?>
				</a>
			</div>
		</div>
	</cx-vui-select>

	<cx-vui-select
		v-if="'plain' === generalSettings.booking_mode"
		label="<?php _e( 'Booking orders post type', 'jet-booking' ); ?>"
		description="<?php _e( 'Select the post type, which will record and store the booking orders. It could be called \'Orders\'. Once a new order is placed, the record will appear in the corresponding database table within the chosen post type.', 'jet-booking' ); ?>"
		:options-list="postTypes"
		:wrapper-css="[ 'equalwidth' ]"
		:size="'fullwidth'"
		:value="generalSettings.related_post_type"
		@input="updateSetting( $event, 'related_post_type' )"
	></cx-vui-select>

	<cx-vui-select
		v-if="'plain' === generalSettings.booking_mode"
		label="<?php _e( 'Booking instance post type', 'jet-booking' ); ?>"
		description="<?php _e( 'Select the post type containing the units to be booked (booking instances). Once selected, the related post IDs will be shown in the Bookings database table.', 'jet-booking' ); ?>"
		:options-list="postTypes"
		:wrapper-css="[ 'equalwidth' ]"
		:size="'fullwidth'"
		:value="generalSettings.apartment_post_type"
		@input="updateSetting( $event, 'apartment_post_type' )"
	></cx-vui-select>

	<?php if ( jet_abaf()->wc->has_woocommerce() ) : ?>
		<cx-vui-switcher
			v-if="'plain' === generalSettings.booking_mode"
			label="<?php _e( 'WooCommerce integration', 'jet-booking' ); ?>"
			description="<?php _e( 'Enable to connect the booking system to a WooCommerce checkout.', 'jet-booking' ); ?>"
			:wrapper-css="[ 'equalwidth' ]"
			:value="generalSettings.wc_integration"
			@input="updateSetting( $event, 'wc_integration' )"
		></cx-vui-switcher>

		<cx-vui-switcher
			v-if="'plain' === generalSettings.booking_mode && settings.wc_integration"
			label="<?php _e( 'Two-way WooCommerce orders sync', 'jet-appointments-booking' ); ?>"
			description="<?php _e( 'If you enable this option, WooCommerce order status will be updated once the booking status changes (by default, if you update a booking status, the related order will remain the same).', 'jet-booking' ); ?>"
			:wrapper-css="[ 'equalwidth' ]"
			:value="settings.wc_sync_orders"
			@input="updateSetting( $event, 'wc_sync_orders' )"
		></cx-vui-switcher>

		<cx-vui-select
			v-if="'plain' !== generalSettings.booking_mode"
			label="<?php _e( 'Booking hold time', 'jet-booking' ); ?>"
			description="<?php _e( 'Time during which the selected date range will be kept on hold after adding the booking instance to the cart.', 'jet-booking' ); ?>"
			:options-list="[
			{
				value: '300',
				label: '<?php _e( '5 min', 'jet-booking' ); ?>',
			},
			{
				value: '600',
				label: '<?php _e( '10 min', 'jet-booking' ); ?>',
			},
			{
				value: '900',
				label: '<?php _e( '15 min', 'jet-booking' ); ?>',
			},
			{
				value: '1200',
				label: '<?php _e( '20 min', 'jet-booking' ); ?>',
			},
			{
				value: '1500',
				label: '<?php _e( '25 min', 'jet-booking' ); ?>',
			},
			{
				value: '1800',
				label: '<?php _e( '30 min', 'jet-booking' ); ?>',
			}
		]"
			:wrapper-css="[ 'equalwidth' ]"
			:size="'fullwidth'"
			:value="generalSettings.booking_hold_time"
			@input="updateSetting( $event, 'booking_hold_time' )"
		></cx-vui-select>
	<?php else : ?>
		<cx-vui-component-wrapper
			v-if="'plain' === generalSettings.booking_mode"
			label="<?php _e( 'WooCommerce integration', 'jet-booking' ); ?>"
			description="<?php _e( 'Enable to connect the booking system with WooCommerce checkout.', 'jet-booking' ); ?>"
			:wrapper-css="[ 'equalwidth' ]"
		>
			<span>
				<?php printf(
					__( 'Please install and activate  <a href="%s" target="_blank">WooCommerce</a> to use this option.', 'jet-booking' ),
					admin_url() . 'plugin-install.php?s=woocommerce&tab=search&type=term'
				); ?>
			</span>
		</cx-vui-component-wrapper>
	<?php endif; ?>

	<cx-vui-select
		label="<?php _e( 'Filters storage type', 'jet-booking' ); ?>"
		description="<?php _e( 'Select the filter storage type for the searched date range.', 'jet-booking' ); ?>"
		:options-list="[
			{
				value: 'session',
				label: '<?php _e( 'Session', 'jet-booking' ); ?>',
			},
			{
				value: 'cookies',
				label: '<?php _e( 'Cookies', 'jet-booking' ); ?>',
			}
		]"
		:wrapper-css="[ 'equalwidth' ]"
		:size="'fullwidth'"
		:value="generalSettings.filters_store_type"
		@input="updateSetting( $event, 'filters_store_type' )"
	></cx-vui-select>
</div>
