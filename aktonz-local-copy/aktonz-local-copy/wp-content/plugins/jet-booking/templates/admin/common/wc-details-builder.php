<div :class="{ 'jet-abaf-popup': true, 'jet-abaf-popup--active': isActive }">
	<div class="jet-abaf-popup__overlay" @click="isActive = ! isActive"></div>

	<div class="jet-abaf-popup__body">
		<div class="jet-abaf-popup__header">
			<h3>
				<?php _e( 'Set up WooCommerce order details', 'jet-booking' ); ?>
			</h3>
		</div>

		<div class="jet-abaf-popup__content">
			<div class="jet-abaf-wc-details">
				<div
					class="jet-abaf-wc-details__item"
					v-for="( item, index ) in details"
					:key="'details-item-' + index"
				>
					<div class="jet-abaf-wc-details-nav">
						<span class="dashicons dashicons-arrow-up-alt2" @click="moveItem( index, index - 1 )"></span>
						<span class="dashicons dashicons-arrow-down-alt2" @click="moveItem( index, index + 1 )"></span>
					</div>

					<div class="jet-abaf-wc-details__col col-type">
						<label :for="'type_' + index">
							<?php _e( 'Type', 'jet-booking' ); ?>
						</label>
						<select v-model="details[ index ].type" :id="'type_' + index">
							<option value="">
								<?php _e( 'Select type...', 'jet-booking' ); ?>
							</option>
							<option value="booked-inst">
								<?php _e( 'Booked instance name', 'jet-booking' ); ?>
							</option>
							<option value="check-in">
								<?php _e( 'Check in', 'jet-booking' ); ?>
							</option>
							<option value="check-out">
								<?php _e( 'Check out', 'jet-booking' ); ?>
							</option>
							<option value="unit">
								<?php _e( 'Booking unit', 'jet-booking' ); ?>
							</option>
							<option value="field">
								<?php _e( 'Form field', 'jet-booking' ); ?>
							</option>
							<option value="add_to_calendar">
								<?php _e( 'Add to Google calendar link', 'jet-booking' ); ?>
							</option>
						</select>
					</div>

					<div class="jet-abaf-wc-details__col col-label">
						<label :for="'label_' + index">
							<?php _e( 'Label', 'jet-booking' ); ?>
						</label>

						<input type="text" v-model="details[ index ].label" :id="'label_' + index">
					</div>
					<div
						v-if="'check-in' === details[ index ].type || 'check-out' === details[ index ].type"
						class="jet-abaf-wc-details__col col-format"
					>
						<label :for="'format_' + index">
							<?php _e( 'Date format', 'jet-booking' ); ?>
						</label>

						<input type="text" v-model="details[ index ].format" :id="'format_' + index">

						<div class="jet-abaf-wc-details__desc">
							<?php
							printf(
								'<a href="https://codex.wordpress.org/Formatting_Date_and_Time" target="_blank">%s</a>',
								__( 'Formatting docs', 'jet-booking' )
							);
							?>
						</div>
					</div>
					<div v-else-if="'field' === details[ index ].type" class="jet-abaf-wc-details__col col-format">
						<label :for="'field_' + index">
							<?php _e( 'Select form field', 'jet-booking' ); ?>
						</label>

						<select v-model="details[ index ].field" :id="'field_' + index">
							<option value="">
								<?php _e( 'Select field...', 'jet-booking' ); ?>
							</option>
							<option :value="field" v-for="field in fieldsList" :key="'details-field-' + field">
								{{ field }}
							</option>
						</select>
					</div>
					<div
						v-else-if="'add_to_calendar' === details[ index ].type"
						class="jet-abaf-wc-details__col col-placeholder"
					>
						<label :for="'link_label_' + index">
							<?php _e( 'Link text', 'jet-booking' ); ?>
						</label>

						<input type="text" v-model="details[ index ].link_label" :id="'format_' + index">
					</div>

					<div v-else class="jet-abaf-wc-details__col col-placeholder"></div>

					<div class="jet-abaf-wc-details__col col-delete">
						<span class="dashicons dashicons-trash" @click="deleteItem( index )"></span>
					</div>
				</div>
			</div>

			<a
				href="#"
				class="jet-abaf-add-rate"
				@click.prevent="newItem"
			>
				<?php _e( '+ Add new item', 'jet-booking' ); ?>
			</a>
		</div>

		<div class="jet-abaf-popup-actions">
			<button
				class="button button-primary"
				type="button"
				aria-expanded="true"
				@click="save"
			>
				<span v-if="!saving">
					<?php _e( 'Save', 'jet-booking' ); ?>
				</span>
				<span v-else>
					<?php _e( 'Saving...', 'jet-booking' ); ?>
				</span>
			</button>

			<button
				class="button-link"
				type="button"
				aria-expanded="true"
				@click="isActive = false"
			>
				<?php _e( 'Cancel', 'jet-booking' ); ?>
			</button>
		</div>
	</div>
</div>