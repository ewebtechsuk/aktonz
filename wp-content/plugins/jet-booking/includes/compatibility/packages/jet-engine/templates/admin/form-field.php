<?php defined( 'ABSPATH' ) || exit; ?>

<div v-if="'check_in_out' === currentItem.settings.type" class="jet-form-editor__row">
	<div class="jet-form-editor__row-label">
		<?php _e( 'Layout:', 'jet-booking' ); ?>
	</div>
	<div class="jet-form-editor__row-control">
		<select type="text" v-model="currentItem.settings.cio_field_layout">
			<option value="single">
				<?php _e( 'Single field', 'jet-booking' ); ?>
			</option>
			<option value="separate">
				<?php _e( 'Separate fields for check in and check out dates.', 'jet-booking' ); ?>
			</option>
		</select>
	</div>
</div>

<div v-if="'check_in_out' === currentItem.settings.type" class="jet-form-editor__row">
	<div class="jet-form-editor__row-label">
		<?php _e( 'Fields position:', 'jet-booking' ); ?>
	</div>
	<div class="jet-form-editor__row-control">
		<select type="text" v-model="currentItem.settings.cio_fields_position">
			<option value="inline">
				<?php _e( 'Inline', 'jet-booking' ); ?>
			</option>
			<option value="list">
				<?php _e( 'List', 'jet-booking' ); ?>
			</option>
		</select>

		<div class="jet-form-editor__row-notice">
			<i>* - For separate fields layout</i>
		</div>
	</div>
</div>

<div v-if="'check_in_out' === currentItem.settings.type" class="jet-form-editor__row">
	<div class="jet-form-editor__row-label">
		<?php _e( 'Check In field label:', 'jet-booking' ); ?>
	</div>
	<div class="jet-form-editor__row-control">
		<input type="text" v-model="currentItem.settings.first_field_label">
		<div class="jet-form-editor__row-notice">
			<i>
				* - if you are using separate fields for check in and check out dates,<br> you need to left
				default "Label" empty and use this option for field label
			</i>
		</div>
	</div>
</div>

<div v-if="'check_in_out' === currentItem.settings.type" class="jet-form-editor__row">
	<div class="jet-form-editor__row-label">
		<?php _e( 'Placeholder:', 'jet-booking' ); ?>
	</div>
	<div class="jet-form-editor__row-control">
		<input type="text" v-model="currentItem.settings.first_field_placeholder">
	</div>
</div>

<div v-if="'check_in_out' === currentItem.settings.type" class="jet-form-editor__row">
	<div class="jet-form-editor__row-label">
		<?php _e( 'Check Out field label:', 'jet-booking' ); ?>
	</div>
	<div class="jet-form-editor__row-control">
		<input type="text" placeholder="For separate fields layout" v-model="currentItem.settings.second_field_label">
	</div>
</div>

<div v-if="'check_in_out' === currentItem.settings.type" class="jet-form-editor__row">
	<div class="jet-form-editor__row-label">
		<?php _e( 'Check Out field placeholder:', 'jet-booking' ); ?>
	</div>
	<div class="jet-form-editor__row-control">
		<input type="text" placeholder="For separate fields layout" v-model="currentItem.settings.second_field_placeholder">
	</div>
</div>

<div v-if="'check_in_out' === currentItem.settings.type" class="jet-form-editor__row">
	<div class="jet-form-editor__row-label">
		<?php _e( 'Date format:', 'jet-booking' ); ?>
	</div>
	<div class="jet-form-editor__row-control">
		<select type="text" v-model="currentItem.settings.cio_fields_format">
			<option value="YYYY-MM-DD">YYYY-MM-DD</option>
			<option value="MM-DD-YYYY">MM-DD-YYYY</option>
			<option value="DD-MM-YYYY">DD-MM-YYYY</option>
		</select>

		<div class="jet-form-editor__row-notice">
			<i>* - applies only for date in the form checkin/checkout fields</i>
			<br>
			<i>* - for `MM-DD-YYYY` date format use the `/` date separator</i>
		</div>
	</div>
</div>

<div v-if="'check_in_out' === currentItem.settings.type" class="jet-form-editor__row">
	<div class="jet-form-editor__row-label">
		<?php _e( 'Date field separator:', 'jet-booking' ); ?>
	</div>
	<div class="jet-form-editor__row-control">
		<select type="text" v-model="currentItem.settings.cio_fields_separator">
			<option value="-">-</option>
			<option value=".">.</option>
			<option value="/">/</option>
			<option value="space">Space</option>
		</select>
	</div>
</div>

<div v-if="'check_in_out' === currentItem.settings.type" class="jet-form-editor__row">
	<div class="jet-form-editor__row-label">
		<?php _e( 'First day of the week:', 'jet-booking' ); ?>
	</div>
	<div class="jet-form-editor__row-control">
		<select type="text" v-model="currentItem.settings.start_of_week">
			<option value="monday">
				<?php _e( 'Monday', 'jet-booking' ); ?>
			</option>
			<option value="sunday">
				<?php _e( 'Sunday', 'jet-booking' ); ?>
			</option>
		</select>
	</div>
</div>
