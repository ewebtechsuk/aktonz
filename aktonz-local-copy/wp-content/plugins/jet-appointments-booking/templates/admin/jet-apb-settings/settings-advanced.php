<div>
	<cx-vui-select
		label="<?php _e( 'Availability check by', 'jet-appointments-booking' ); ?>"
		description="<?php _e( 'Select type of slots availability check - through all services or independent by each service', 'jet-appointments-booking' ); ?>"
		:options-list="[
			{
				value: 'global',
				label: '<?php _e( 'Through all services', 'jet-appointments-boooking' ); ?>',
			},
			{
				value: 'service',
				label: '<?php _e( 'By each service', 'jet-appointments-boooking' ); ?>',
			}
		]"
		:wrapper-css="[ 'equalwidth' ]"
		:size="'fullwidth'"
		:value="settings.check_by"
		@input="updateSetting( $event, 'check_by' )"
	></cx-vui-select>
	<cx-vui-select
		label="<?php _e( 'How to process \'on-hold\' appointments', 'jet-appointments-booking' ); ?>"
		description="<?php _e( 'Select the way how \'on-hold\' appointments slots will be handled in the calendar. \'on-hold\' appointments used when you integrate appointments with some payment system from JetFormBuilder or WooCommerce', 'jet-appointments-booking' ); ?>"
		:options-list="[
			{
				value: 'invalid',
				label: '<?php _e( 'Keep `on-hold` slots available', 'jet-appointments-boooking' ); ?>',
			},
			{
				value: 'in_progress',
				label: '<?php _e( 'Exclude `on-hold` slots from calendar', 'jet-appointments-boooking' ); ?>',
			}
		]"
		:wrapper-css="[ 'equalwidth' ]"
		:size="'fullwidth'"
		:value="settings.process_on_hold"
		@input="updateSetting( $event, 'process_on_hold' )"
	></cx-vui-select>
	<cx-vui-switcher
		label="<?php _e( 'Automatically switch appointments status', 'jet-appointments-booking' ); ?>"
		description="<?php _e( 'Check this to automatically change status for \'pending\' or \'on hold\' appointments to \'failed\' after selected period of time. This is may be useful if you want automatically make available not confirmed slots.', 'jet-appointments-booking' ); ?>"
		:wrapper-css="[ 'equalwidth' ]"
		:value="settings.switch_status"
		@input="updateSetting( $event, 'switch_status' )"
	></cx-vui-switcher>
	<cx-vui-select
		label="<?php _e( 'Switch interval', 'jet-appointments-booking' ); ?>"
		description="<?php _e( 'Select switching appointments time interval', 'jet-appointments-booking' ); ?>"
		v-if="settings.switch_status"
		:options-list="getGlobalConfig( 'switch_intervals', [] )"
		:wrapper-css="[ 'equalwidth' ]"
		:size="'fullwidth'"
		:value="settings.switch_status_period"
		@input="updateSetting( $event, 'switch_status_period' )"
	></cx-vui-select>
	<cx-vui-f-select
		label="<?php _e( 'Switch from', 'jet-appointments-booking' ); ?>"
		description="<?php _e( 'Find appointments with this status', 'jet-appointments-booking' ); ?>"
		v-if="settings.switch_status"
		:options-list="[
			{
				value: 'on-hold',
				label: '<?php echo \Jet_APB\Plugin::instance()->statuses->get_status_label( 'on-hold' ); ?>',
			},
			{
				value: 'pending',
				label: '<?php echo \Jet_APB\Plugin::instance()->statuses->get_status_label( 'pending' ); ?>',
			},
			{
				value: 'processing',
				label: '<?php echo \Jet_APB\Plugin::instance()->statuses->get_status_label( 'processing' ); ?>',
			},	
		]"
		:wrapper-css="[ 'equalwidth' ]"
		:size="'fullwidth'"
		:multiple="true"
		:value="settings.switch_status_from"
		@input="updateSetting( $event, 'switch_status_from' )"
	></cx-vui-f-select>
	<cx-vui-select
		label="<?php _e( 'Switch to', 'jet-appointments-booking' ); ?>"
		description="<?php _e( 'Switch status to this', 'jet-appointments-booking' ); ?>"
		v-if="settings.switch_status"
		:options-list="[
			{
				value: 'failed',
				label: '<?php echo \Jet_APB\Plugin::instance()->statuses->get_status_label( 'failed' ); ?>',
			},
			{
				value: 'cancelled',
				label: '<?php echo \Jet_APB\Plugin::instance()->statuses->get_status_label( 'cancelled' ); ?>',
			},	
		]"
		:wrapper-css="[ 'equalwidth' ]"
		:size="'fullwidth'"
		:value="settings.switch_status_to"
		@input="updateSetting( $event, 'switch_status_to' )"
	></cx-vui-select>
	
	<cx-vui-switcher
		label="<?php _e( 'Generate Confirmation URLs', 'jet-appointments-booking' ); ?>"
		description="<?php _e( 'Generate for each appointments unique URLs to confirm or decline appointment. URLs are stored in the Appointment meta data and can be used inside emails or webhooks.', 'jet-appointments-booking' ); ?>"
		:wrapper-css="[ 'equalwidth' ]"
		:value="settings.allow_action_links"
		@input="updateSetting( $event, 'allow_action_links' )"
	></cx-vui-switcher>
	
	<cx-vui-select
		label="<?php _e( 'Generate Same Confirmation URLs for appointments in group', 'jet-appointments-booking' ); ?>"
		description="<?php _e( 'For all appointments in the group, the confirmation and cancellation links will be the same.', 'jet-appointments-booking' ); ?>"
		v-if="( settings.manage_capacity || settings.multi_booking ) && settings.allow_action_links"
		:options-list="[
			{
				value: false,
				label: '<?php _e( 'Unique link for each appointment', 'jet-appointments-booking' ) ?>',
			},
			{
				value: true,
				label: '<?php _e( 'One link for group', 'jet-appointments-booking' ) ?>',
			},	
		]"
		:wrapper-css="[ 'equalwidth' ]"
		:size="'fullwidth'"
		:value="settings.same_group_token"
		@input="updateSetting( $event, 'same_group_token' )"
	></cx-vui-select>

	<cx-vui-select
		label="<?php _e( 'Confirm Page Shows', 'jet-appointments-booking' ); ?>"
		description="<?php _e( 'How to present information on the Confirmation page - with plain text message or custom template', 'jet-appointments-booking' ); ?>"
		v-if="settings.allow_action_links"
		:options-list="[
			{
				value: 'text_message',
				label: '<?php _e( 'Text Message', 'jet-appointments-booking' ) ?>',
			},
			{
				value: 'custom_template',
				label: '<?php _e( 'Custom Template', 'jet-appointments-booking' ) ?>',
			},
		]"
		:wrapper-css="[ 'equalwidth' ]"
		:size="'fullwidth'"
		:value="settings.confirm_action_template_type"
		@input="updateSetting( $event, 'confirm_action_template_type' )"
	></cx-vui-select>
	<cx-vui-textarea
		label="<?php esc_html_e( 'Confirmed Message', 'jet-appointments-booking' ); ?>"
		description="<?php esc_html_e( 'Message to show on appointment confirmation', 'jet-appointments-booking' ); ?>"
		:wrapper-css="[ 'equalwidth' ]"
		:size="'fullwidth'"
		:value="settings.confirm_action_message"
		v-if="true === settings.allow_action_links && 'text_message' === settings.confirm_action_template_type"
		@on-input-change="updateSetting( $event.target.value, 'confirm_action_message' )"
	></cx-vui-textarea>
	<cx-vui-select
		label="<?php _e( 'Confirm Page Template', 'jet-appointments-booking' ); ?>"
		description="<?php _e( 'Select template to use as confiramtion page. This template will replace whole page content.', 'jet-appointments-booking' ); ?>"
		v-if="true === settings.allow_action_links && 'custom_template' === settings.confirm_action_template_type"
		:options-list="allowedTemplates"
		:wrapper-css="[ 'equalwidth' ]"
		size="fullwidth"
		:value="settings.confirm_action_template"
		@input="updateSetting( $event, 'confirm_action_template' )"
	></cx-vui-select>

	<cx-vui-select
		label="<?php _e( 'Cancellation Page Shows', 'jet-appointments-booking' ); ?>"
		description="<?php _e( 'How to present information on the Cancellation page - with plain text message or custom template', 'jet-appointments-booking' ); ?>"
		v-if="settings.allow_action_links"
		:options-list="[
			{
				value: 'text_message',
				label: '<?php _e( 'Text Message', 'jet-appointments-booking' ) ?>',
			},
			{
				value: 'custom_template',
				label: '<?php _e( 'Custom Template', 'jet-appointments-booking' ) ?>',
			},
		]"
		:wrapper-css="[ 'equalwidth' ]"
		size="fullwidth"
		:value="settings.cancel_action_template_type"
		@input="updateSetting( $event, 'cancel_action_template_type' )"
	></cx-vui-select>
	<cx-vui-textarea
		label="<?php esc_html_e( 'Cancelled Message', 'jet-appointments-booking' ); ?>"
		description="<?php esc_html_e( 'Message to show on appointment cancel', 'jet-appointments-booking' ); ?>"
		:wrapper-css="[ 'equalwidth' ]"
		size="fullwidth"
		:value="settings.cancel_action_message"
		v-if="true === settings.allow_action_links && 'text_message' === settings.cancel_action_template_type"
		@on-input-change="updateSetting( $event.target.value, 'cancel_action_message' )"
	></cx-vui-textarea>
	<cx-vui-select
		label="<?php _e( 'Cancellation Page Template', 'jet-appointments-booking' ); ?>"
		description="<?php _e( 'Select template to use as cancellation page. This template will replace whole page content.', 'jet-appointments-booking' ); ?>"
		v-if="true === settings.allow_action_links && 'custom_template' === settings.cancel_action_template_type"
		:options-list="allowedTemplates"
		:wrapper-css="[ 'equalwidth' ]"
		size="fullwidth"
		:value="settings.cancel_action_template"
		@input="updateSetting( $event, 'cancel_action_template' )"
	></cx-vui-select>

	<cx-vui-switcher
		label="<?php _e( 'Hide Set Up Wizard', 'jet-appointments-booking' ); ?>"
		description="<?php _e( 'Check this to hide Set Up page to avoid unnecessary plugin resets', 'jet-appointments-booking' ); ?>"
		:wrapper-css="[ 'equalwidth' ]"
		:value="settings.hide_setup"
		@input="updateSetting( $event, 'hide_setup' )"
	></cx-vui-switcher>
</div>
