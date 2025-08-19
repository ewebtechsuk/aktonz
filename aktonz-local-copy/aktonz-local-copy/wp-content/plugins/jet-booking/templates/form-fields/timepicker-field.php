<div class="jet-abaf-timepicker">
	<?php if ( $labeled ) : ?>
		<label class="<?php echo implode( ' ', $label_classes ); ?>" for="<?php echo esc_attr( $type ) . '-time'; ?>">
			<?php _e( 'Time', 'jet-booking' ); ?>
		</label>
	<?php endif; ?>

	<select id="<?php echo esc_attr( $type ) . '-time'; ?>" name="<?php echo esc_attr( $type ) . '-time'; ?>" class="<?php echo implode( ' ', $field_classes ); ?>">
		<?php echo jet_abaf()->tools->get_timepicker_slots(); ?>
	</select>
</div>