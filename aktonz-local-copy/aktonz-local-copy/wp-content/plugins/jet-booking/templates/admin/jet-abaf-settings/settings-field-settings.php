<div>
	<cx-vui-switcher
		label="<?php _e( 'Disable email field', 'jet-booking' ); ?>"
		description="<?php _e( 'Removes the email input field from the booking section on the single product page.', 'jet-booking' ); ?>"
		:wrapper-css="[ 'equalwidth' ]"
		:value="fieldsSettings.disable_email_field"
		@input="updateSetting( $event, 'disable_email_field' )"
	></cx-vui-switcher>

	<div class="cx-vui-subtitle">
		<?php _e( 'Date range picker field', 'jet-booking' ); ?>
	</div>

	<cx-vui-select
		label="<?php _e( 'Layout', 'jet-booking' ); ?>"
		description="<?php _e( 'Define the arrangement and organization of the field within interface.', 'jet-booking' ); ?>"
		:options-list="[
			{
				value: 'single',
				label: '<?php _e( 'Single field', 'jet-booking' ); ?>',
			},
			{
				value: 'separate',
				label: '<?php _e( 'Separate fields', 'jet-booking' ); ?>',
			}
		]"
		:wrapper-css="[ 'equalwidth' ]"
		:size="'fullwidth'"
		:value="fieldsSettings.field_layout"
		@input="updateSetting( $event, 'field_layout' )"
	></cx-vui-select>

	<cx-vui-select
		v-if="'separate' === fieldsSettings.field_layout"
		label="<?php _e( 'Position', 'jet-booking' ); ?>"
		description="<?php _e( 'Define the specific placement of the separate fields in interface.', 'jet-booking' ); ?>"
		:options-list="[
			{
				value: 'inline',
				label: '<?php _e( 'Inline', 'jet-booking' ); ?>',
			},
			{
				value: 'list',
				label: '<?php _e( 'List', 'jet-booking' ); ?>',
			}
		]"
		:wrapper-css="[ 'equalwidth' ]"
		:size="'fullwidth'"
		:value="fieldsSettings.field_position"
		@input="updateSetting( $event, 'field_position' )"
	></cx-vui-select>

	<cx-vui-input
		v-if="'single' === fieldsSettings.field_layout"
		label="<?php _e( 'Label', 'jet-booking' ); ?>"
		description="<?php _e( 'Label for single field layout.', 'jet-booking' ); ?>"
		:wrapper-css="[ 'equalwidth' ]"
		:size="'fullwidth'"
		:value="fieldsSettings.field_label"
		@on-input-change="updateSetting( $event.target.value, 'field_label' )"
	></cx-vui-input>

	<cx-vui-input
		v-if="'single' === fieldsSettings.field_layout"
		label="<?php _e( 'Placeholder', 'jet-booking' ); ?>"
		description="<?php _e( 'Placeholder for single field layout.', 'jet-booking' ); ?>"
		:wrapper-css="[ 'equalwidth' ]"
		:size="'fullwidth'"
		:value="fieldsSettings.field_placeholder"
		@on-input-change="updateSetting( $event.target.value, 'field_placeholder' )"
	></cx-vui-input>

	<cx-vui-input
		v-if="'separate' === fieldsSettings.field_layout"
		label="<?php _e( 'Check in field label', 'jet-booking' ); ?>"
		description="<?php _e( 'Label for check in field.', 'jet-booking' ); ?>"
		:wrapper-css="[ 'equalwidth' ]"
		:size="'fullwidth'"
		:value="fieldsSettings.check_in_field_label"
		@on-input-change="updateSetting( $event.target.value, 'check_in_field_label' )"
	></cx-vui-input>

	<cx-vui-input
		v-if="'separate' === fieldsSettings.field_layout"
		label="<?php _e( 'Check in field placeholder', 'jet-booking' ); ?>"
		description="<?php _e( 'Placeholder for check in field.', 'jet-booking' ); ?>"
		:wrapper-css="[ 'equalwidth' ]"
		:size="'fullwidth'"
		:value="fieldsSettings.check_in_field_placeholder"
		@on-input-change="updateSetting( $event.target.value, 'check_in_field_placeholder' )"
	></cx-vui-input>

	<cx-vui-input
		v-if="'separate' === fieldsSettings.field_layout"
		label="<?php _e( 'Check out field label', 'jet-booking' ); ?>"
		description="<?php _e( 'Label for check out field.', 'jet-booking' ); ?>"
		:wrapper-css="[ 'equalwidth' ]"
		:size="'fullwidth'"
		:value="fieldsSettings.check_out_field_label"
		@on-input-change="updateSetting( $event.target.value, 'check_out_field_label' )"
	></cx-vui-input>

	<cx-vui-input
		v-if="'separate' === fieldsSettings.field_layout"
		label="<?php _e( 'Check out field placeholder', 'jet-booking' ); ?>"
		description="<?php _e( 'Placeholder for check out field.', 'jet-booking' ); ?>"
		:wrapper-css="[ 'equalwidth' ]"
		:size="'fullwidth'"
		:value="fieldsSettings.check_out_field_placeholder"
		@on-input-change="updateSetting( $event.target.value, 'check_out_field_placeholder' )"
	></cx-vui-input>

	<cx-vui-input
		label="<?php _e( 'Description', 'jet-booking' ); ?>"
		description="<?php _e( 'Define explanation to describe and provide context for the field within interface.', 'jet-booking' ); ?>"
		:wrapper-css="[ 'equalwidth' ]"
		:size="'fullwidth'"
		:value="fieldsSettings.field_description"
		@on-input-change="updateSetting( $event.target.value, 'field_description' )"
	></cx-vui-input>

	<cx-vui-select
		label="<?php _e( 'Date format', 'jet-booking' ); ?>"
		description="<?php _e( 'Specific structure and representation used to express dates in a standardized way. Applies only to dates in the form of check-in/check-out fields.', 'jet-booking' ); ?>"
		:options-list="[
			{
				value: 'YYYY-MM-DD',
				label: '<?php _e( 'YYYY-MM-DD', 'jet-booking' ); ?>',
			},
			{
				value: 'MM-DD-YYYY',
				label: '<?php _e( 'MM-DD-YYYY', 'jet-booking' ); ?>',
			},
			{
				value: 'DD-MM-YYYY',
				label: '<?php _e( 'DD-MM-YYYY', 'jet-booking' ); ?>',
			}
		]"
		:wrapper-css="[ 'equalwidth' ]"
		:size="'fullwidth'"
		:value="fieldsSettings.field_date_format"
		@input="updateSetting( $event, 'field_date_format' )"
	></cx-vui-select>

	<cx-vui-select
		label="<?php _e( 'Separator', 'jet-booking' ); ?>"
		description="<?php _e( 'Pick a character that will be used to separate the day, month, and year components within a date.', 'jet-booking' ); ?>"
		:options-list="[
			{
				value: '-',
				label: '<?php _e( '-', 'jet-booking' ); ?>',
			},
			{
				value: '.',
				label: '<?php _e( '.', 'jet-booking' ); ?>',
			},
			{
				value: '/',
				label: '<?php _e( '/', 'jet-booking' ); ?>',
			},
			{
				value: 'space',
				label: '<?php _e( 'Space', 'jet-booking' ); ?>',
			}
		]"
		:wrapper-css="[ 'equalwidth' ]"
		:size="'fullwidth'"
		:value="fieldsSettings.field_separator"
		@input="updateSetting( $event, 'field_separator' )"
	></cx-vui-select>

	<cx-vui-select
		label="<?php _e( 'First day of the week', 'jet-booking' ); ?>"
		description="<?php _e( 'Set the start if the week day in the date picker.', 'jet-booking' ); ?>"
		:options-list="[
			{
				value: 'monday',
				label: '<?php _e( 'Monday', 'jet-booking' ); ?>',
			},
			{
				value: 'sunday',
				label: '<?php _e( 'Sunday', 'jet-booking' ); ?>',
			}
		]"
		:wrapper-css="[ 'equalwidth' ]"
		:size="'fullwidth'"
		:value="fieldsSettings.field_start_of_week"
		@input="updateSetting( $event, 'field_start_of_week' )"
	></cx-vui-select>
</div>