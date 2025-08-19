<div class="jet-abaf-bookings-view">
	<cx-vui-button
		v-for="( view, index ) in views"
		:key="index"
		:button-style="viewButtonStyle( index )"
		size="mini"
		@click="updateView( index )"
	>
		<template slot="label">{{ view.label }}</template>
	</cx-vui-button>
</div>
