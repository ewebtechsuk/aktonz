<div class="jet-abaf-bookings-timeline">
	<cx-vui-component-wrapper
		:wrapper-css="[ 'jet-abaf-gc-datepicker' ]"
		label="<?php esc_html_e('Period', 'jet-booking'); ?>"
	>
		<vuejs-datepicker
			input-class="cx-vui-input size-default"
			:format="dateFormat"
			minimum-view="month"
			v-model="selectedDate"
		></vuejs-datepicker>
	</cx-vui-component-wrapper>

	<v-gantt-chart
		:datas="itemsList"
		:startTime="startTime"
		:endTime="endTime"
		scale="1440"
		:hideYScrollBar="true"
		cellWidth="75"
		ref="gantt"
		@scrollLeft="adjustHeight"
	>
		<template v-slot:title>
			<?php esc_html_e( 'Instances', 'jet-booking' ); ?>
		</template>

		<template v-slot:timeline="{ day , getTimeScales }">
			{{ day.format( 'DD MMM' ) }}
		</template>

		<template v-slot:left="{data}">
			<div>{{ data.instance }}</div>
		</template>

		<template v-slot:block="{ data, item }" data-instance-id="test">
			<div class="jet-abaf-gantt-block-item" :class="statusClass( item.customData.status )" @click="callPopup( 'info', item.customData )">
				<template v-if="item.customData.apartment_unit">{{ getItemUnitLabel( item.customData.apartment_id, item.customData.apartment_unit ) }}</template>
			</div>
		</template>
	</v-gantt-chart>
</div>
