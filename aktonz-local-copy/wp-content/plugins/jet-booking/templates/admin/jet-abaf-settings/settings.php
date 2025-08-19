<div class="jet-abaf-wrap">
	<h1 class="cs-vui-title">
		<?php _e( 'Booking Settings', 'jet-booking' ); ?>
	</h1>

	<div class="cx-vui-panel">
		<cx-vui-tabs layout="vertical" :value="initialTab">
			<cx-vui-tabs-panel
				name="general"
				label="<?php _e( 'General', 'jet-booking' ); ?>"
				key="general"
			>
				<keep-alive>
					<jet-abaf-settings-general
						:settings="settings"
						@force-update="onUpdateSettings( $event, true )"
					></jet-abaf-settings-general>
				</keep-alive>
			</cx-vui-tabs-panel>

			<cx-vui-tabs-panel
				name="labels"
				label="<?php _e( 'Labels', 'jet-booking' ); ?>"
				key="labels"
			>
				<keep-alive>
					<jet-abaf-settings-labels
						:settings="settings"
						@force-update="onUpdateSettings( $event, true )"
					></jet-abaf-settings-labels>
				</keep-alive>
			</cx-vui-tabs-panel>

			<?php if ( jet_abaf()->wc->has_woocommerce() && 'wc_based' === jet_abaf()->settings->get( 'booking_mode' ) ) : ?>
				<cx-vui-tabs-panel
					name="field-settings"
					label="<?php _e( 'Fields Settings', 'jet-booking' ); ?>"
					key="field-settings"
				>
					<keep-alive>
						<jet-abaf-settings-field-settings
							:settings="settings"
							@force-update="onUpdateSettings( $event, true )"
						></jet-abaf-settings-field-settings>
					</keep-alive>
				</cx-vui-tabs-panel>
			<?php endif; ?>

			<cx-vui-tabs-panel
				name="advanced"
				label="<?php _e( 'Advanced', 'jet-booking' ); ?>"
				key="advanced"
			>
				<keep-alive>
					<jet-abaf-settings-advanced
						:settings="settings"
						@force-update="onUpdateSettings( $event, true )"
					></jet-abaf-settings-advanced>
				</keep-alive>
			</cx-vui-tabs-panel>

			<cx-vui-tabs-panel
				name="configuration"
				label="<?php _e( 'Configuration', 'jet-booking' ); ?>"
				key="configuration"
			>
				<keep-alive>
					<jet-abaf-settings-configuration
						:settings="settings"
						@force-update="onUpdateSettings( $event, true )"
					></jet-abaf-settings-configuration>
				</keep-alive>
			</cx-vui-tabs-panel>

			<cx-vui-tabs-panel
				name="schedule"
				label="<?php _e( 'Schedule', 'jet-booking' ); ?>"
				key="schedule"
			>
				<keep-alive>
					<jet-abaf-settings-schedule
						:settings="settings"
						@force-update="onUpdateSettings( $event, true )"
					></jet-abaf-settings-schedule>
				</keep-alive>
			</cx-vui-tabs-panel>

			<cx-vui-tabs-panel
				name="workflows"
				label="<?php _e( 'Workflows', 'jet-booking' ); ?>"
				key="workflows"
			>
				<keep-alive>
					<jet-abaf-settings-workflows
						:settings="settings"
						@force-update="onUpdateSettings( $event, true )"
					></jet-abaf-settings-workflows>
				</keep-alive>
			</cx-vui-tabs-panel>

			<cx-vui-tabs-panel
				name="tools"
				label="<?php _e( 'Tools', 'jet-booking' ); ?>"
				key="tools"
			>
				<keep-alive>
					<jet-abaf-settings-tools
						:settings="settings"
						:dbTablesExists="dbTablesExists"
						@force-update="onUpdateSettings( $event, true )"
					></jet-abaf-settings-tools>
				</keep-alive>
			</cx-vui-tabs-panel>
		</cx-vui-tabs>
	</div>
</div>