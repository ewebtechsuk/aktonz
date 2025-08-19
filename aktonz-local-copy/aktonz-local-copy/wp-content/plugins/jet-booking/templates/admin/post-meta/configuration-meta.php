<div id='jet-abaf-configuration-meta-box'>
	<cx-vui-switcher
		label="<?php _e( 'Date Picker Configuration', 'jet-booking' ); ?>"
		description="<?php _e( 'You can enable and setup datepicker configuration for apartment.', 'jet-booking' ); ?>"
		:wrapper-css="[ 'equalwidth' ]"
		:return-true="true"
		:return-false="false"
		v-model="settings.config.enable_config"
		@input="updateSetting( $event, 'enable_config' )"
	></cx-vui-switcher>

	<jet-abaf-configuration
		v-if="settings.config.enable_config"
		class="cx-vui-panel"
	></jet-abaf-configuration>
</div>