<?php

namespace JET_ABAF\Components\Blocks_Views;

use \JET_ABAF\Render\Calendar as Calendar_Renderer;
use \JET_SM\Gutenberg\Controls_Manager;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

class Manager {

	/**
	 * Style manager controls instance.
	 *
	 * @var Controls_Manager|null
	 */
	private $controls_manager = null;

	public function __construct() {

		if ( class_exists( 'JET_SM\Gutenberg\Controls_Manager' ) ) {
			$this->controls_manager = new Controls_Manager( 'jet-booking/calendar' );

			$this->add_style_manager_options();
		}

		// Register booking related block categories.
		add_filter( 'block_categories_all', [ $this, 'register_categories' ], 10, 2 );
		// Disable specific blocks in the editor.
		add_filter( 'allowed_block_types_all', [ $this, 'maybe_disable_blocks' ], 10, 2 );

		// Register booking blocks.
		add_action( 'init', [ $this, 'register_blocks' ] );
		// Enqueue scripts and styles for editor blocks.
		add_action( 'enqueue_block_assets', [ $this, 'enqueue_editor_assets' ] );

	}

	/**
	 * Register categories.
	 *
	 * Register booking related block categories.
	 *
	 * @since 3.2.0
	 *
	 * @param array[]                  $block_categories Array of categories for block types.
	 * @param \WP_Block_Editor_Context $editor_context   The current block editor context.
	 *
	 * @return array
	 */
	public function register_categories( $block_categories, $editor_context ) {

		$block_categories[] = [
			'slug'  => 'jet-booking',
			'title' => __( 'JetBooking', 'jet-booking' ),
			'icon'  => null,
		];

		return $block_categories;

	}

	/**
	 * Conditionally disable specific blocks in the editor.
	 *
	 * This function checks if the current post type is 'jet-form-builder' and if so,
	 * removes specific blocks from the list of allowed blocks.
	 *
	 * @since 3.6.5
	 *
	 * @param array|bool               $allowed_blocks The list of allowed block types or true if all block types are
	 *                                                 allowed.
	 * @param \WP_Block_Editor_Context $editor_context The current block editor context.
	 *
	 * @return array|bool Modified list of allowed block types or the original input if conditions are not met.
	 */
	public function maybe_disable_blocks( $allowed_blocks, $editor_context ) {

		if ( isset( $editor_context->post ) && 'jet-form-builder' === $editor_context->post->post_type ) {
			$blocks = \WP_Block_Type_Registry::get_instance()->get_all_registered();

			unset( $blocks['jet-booking/calendar'] );

			return array_keys( $blocks );
		}

		return $allowed_blocks;

	}

	/**
	 * Register blocks.
	 *
	 * Register all booking related block.
	 *
	 * @since 3.2.0
	 */
	public function register_blocks() {
		register_block_type(
			JET_ABAF_PATH . 'assets/js/admin/blocks-view/build/blocks/calendar',
			[ 'render_callback' => [ $this, 'render_callback' ] ]
		);
	}

	/**
	 * Render callback.
	 *
	 * Render booking availability calendar block.
	 *
	 * @since 3.2.0
	 *
	 * @param array $attributes List of block attributes.
	 *
	 * @return false|string
	 */
	public function render_callback( $attributes = [] ) {

		$style = [
			'--jet-abaf-calendar-color: ' . $attributes['calendarTextColor'],
			'--jet-abaf-calendar-bg-color: ' . $attributes['calendarBackgroundColor'],
			'--jet-abaf-days-color: ' . $attributes['daysTextColor'],
			'--jet-abaf-days-bg-color: ' . $attributes['daysBackgroundColor'],
			'--jet-abaf-current-day-color:' . $attributes['currentDayTextColor'],
			'--jet-abaf-current-day-bg-color: ' . $attributes['currentDayBackgroundColor'],
			'--jet-abaf-selected-edges-color: ' . $attributes['selectedEdgesTextColor'],
			'--jet-abaf-selected-edges-bg-color: ' . $attributes['selectedEdgesBackgroundColor'],
			'--jet-abaf-selected-trace-color: ' . $attributes['selectedTraceTextColor'],
			'--jet-abaf-selected-trace-bg-color: ' . $attributes['selectedTraceBackgroundColor']
		];

		ob_start();

		echo sprintf( '<div class="wp-block-jet-booking-calendar" data-is-block="jet-booking/calendar" style="%s">', implode( ';', $style ) );

		$renderer = new Calendar_Renderer( $attributes );
		$renderer->render();

		echo "</div>";

		$output = ob_get_contents();
		ob_end_clean();

		return $output;

	}

	/**
	 * Enqueue editor assets.
	 *
	 * Register and enqueue assets for exclusive usage within the Site Editor.
	 *
	 * @since 3.2.0
	 */
	public function enqueue_editor_assets() {
		wp_register_style(
			'jquery-date-range-picker-styles',
			JET_ABAF_URL . 'assets/lib/jquery-date-range-picker/css/daterangepicker.css',
			[],
			JET_ABAF_VERSION
		);
	}

	/**
	 * Add style manager options.
	 *
	 * Add style manager style block options.
	 *
	 * @since 3.2.0
	 */
	public function add_style_manager_options() {

		$this->controls_manager->start_section(
			'style_controls',
			[
				'id'           => 'section_container_style',
				'title'        => __( 'Container', 'jet-booking' ),
				'initial_open' => true,
			]
		);

		$this->controls_manager->add_control( [
			'id'           => 'month_gap_color',
			'type'         => 'color-picker',
			'label'        => __( 'Gap Line Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .jet-booking-calendar .date-picker-wrapper .gap:before' => 'border-left-color: {{VALUE}}',
			],
		] );

		$this->controls_manager->add_control( [
			'id'           => 'container_bg_color',
			'type'         => 'color-picker',
			'label'        => __( 'Background Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .jet-booking-calendar .date-picker-wrapper' => 'background-color: {{VALUE}}',
			],
		] );

		$this->controls_manager->add_responsive_control( [
			'id'           => 'container_padding',
			'type'         => 'dimensions',
			'label'        => __( 'Padding', 'jet-booking' ),
			'units'        => [ 'px', 'em', '%' ],
			'css_selector' => [
				'{{WRAPPER}} .jet-booking-calendar .date-picker-wrapper' => 'padding: {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}} !important;',
			],
		] );

		$this->controls_manager->end_section();

		$this->controls_manager->start_section(
			'style_controls',
			[
				'id'    => 'section_calendar_month_style',
				'title' => __( 'Month', 'jet-booking' ),
			]
		);

		$this->controls_manager->add_control( [
			'id'           => 'calendar_month_bg_color',
			'type'         => 'color-picker',
			'label'        => __( 'Background Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .jet-booking-calendar .month1' => 'background-color: {{VALUE}}',
				'{{WRAPPER}} .jet-booking-calendar .month2' => 'background-color: {{VALUE}}',
			],
		] );

		$this->controls_manager->add_responsive_control( [
			'id'           => 'calendar_month_border',
			'type'         => 'border',
			'label'        => __( 'Border', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .jet-booking-calendar .date-picker-wrapper .month-wrapper .month1' => 'border-style: {{STYLE}}; border-width: {{WIDTH}}; border-radius: {{RADIUS}}; border-color: {{COLOR}}',
				'{{WRAPPER}} .jet-booking-calendar .date-picker-wrapper .month-wrapper .month2' => 'border-style: {{STYLE}}; border-width: {{WIDTH}}; border-radius: {{RADIUS}}; border-color: {{COLOR}}',
			]
		] );

		$this->controls_manager->add_responsive_control( [
			'id'           => 'calendar_month_padding',
			'type'         => 'dimensions',
			'label'        => __( 'Padding', 'jet-booking' ),
			'units'        => [ 'px', 'em', '%' ],
			'css_selector' => [
				'{{WRAPPER}} .jet-booking-calendar .month1' => 'padding: {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}} !important;',
				'{{WRAPPER}} .jet-booking-calendar .month2' => 'padding: {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}} !important;',
			],
		] );

		$this->controls_manager->add_control( [
			'id'      => 'month_names_heading',
			'type'    => 'text',
			'content' => __( 'Headings', 'jet-booking' ),
		] );

		$this->controls_manager->add_control( [
			'id'           => 'month_names_typography',
			'type'         => 'typography',
			'css_selector' => [
				'{{WRAPPER}} .jet-booking-calendar thead .caption .month-name .month-element' => 'font-family: {{FAMILY}}; font-weight: {{WEIGHT}}; text-transform: {{TRANSFORM}}; font-style: {{STYLE}}; text-decoration: {{DECORATION}}; line-height: {{LINEHEIGHT}}{{LH_UNIT}}; letter-spacing: {{LETTERSPACING}}{{LS_UNIT}}; font-size: {{SIZE}}{{S_UNIT}};',
			],
		] );

		$this->controls_manager->add_control( [
			'id'           => 'month_names_color',
			'type'         => 'color-picker',
			'label'        => __( 'Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .jet-booking-calendar thead .caption .month-name' => 'color: {{VALUE}}',
			],
		] );

		$this->controls_manager->add_control( [
			'id'           => 'month_names_bg_color',
			'type'         => 'color-picker',
			'label'        => __( 'Background Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .jet-booking-calendar thead .caption .month-name' => 'background-color: {{VALUE}}',
			],
		] );

		$this->controls_manager->add_responsive_control( [
			'id'           => 'month_names_border',
			'type'         => 'border',
			'label'        => __( 'Border', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .jet-booking-calendar thead .caption .month-name' => 'border-style: {{STYLE}}; border-width: {{WIDTH}}; border-radius: {{RADIUS}}; border-color: {{COLOR}}',
			]
		] );

		$this->controls_manager->add_responsive_control( [
			'id'           => 'month_names_padding',
			'type'         => 'dimensions',
			'label'        => __( 'Padding', 'jet-booking' ),
			'units'        => [ 'px', 'em', '%' ],
			'css_selector' => [
				'{{WRAPPER}} .jet-booking-calendar thead .caption .month-name' => 'padding: {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}} !important;',
			],
		] );

		$this->controls_manager->add_control( [
			'id'      => 'month_switcher_heading',
			'type'    => 'text',
			'content' => __( 'Switchers', 'jet-booking' ),
		] );

		$this->controls_manager->add_control( [
			'id'           => 'month_switcher_font_size',
			'type'         => 'range',
			'label'        => __( 'Font Size', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .jet-booking-calendar thead .caption .prev' => 'font-size: {{VALUE}}{{UNIT}};',
				'{{WRAPPER}} .jet-booking-calendar thead .caption .next' => 'font-size: {{VALUE}}{{UNIT}};',
			],
			'units'        => [
				[
					'value'     => 'px',
					'intervals' => [
						'step' => 1,
						'min'  => 30,
						'max'  => 200,
					]
				],
			],
		] );

		$this->controls_manager->add_control( [
			'id'           => 'month_switcher_box_size',
			'type'         => 'range',
			'label'        => __( 'Box Size', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .jet-booking-calendar .date-picker-wrapper thead .caption .prev' => 'width: {{VALUE}}{{UNIT}}; height: {{VALUE}}{{UNIT}}',
				'{{WRAPPER}} .jet-booking-calendar .date-picker-wrapper thead .caption .next' => 'width: {{VALUE}}{{UNIT}}; height: {{VALUE}}{{UNIT}}',
			],
			'units'        => [
				[
					'value'     => 'px',
					'intervals' => [
						'step' => 1,
						'min'  => 30,
						'max'  => 200,
					]
				],
			],
		] );

		$this->controls_manager->start_tabs(
			'style_controls',
			[
				'id'        => 'tabs_month_switcher_style',
				'separator' => 'after',
			]
		);

		$this->controls_manager->start_tab(
			'style_controls',
			[
				'id'    => 'month_switcher_normal',
				'title' => __( 'Normal', 'jet-booking' ),
			]
		);

		$this->controls_manager->add_control( [
			'id'           => 'month_switcher_color',
			'type'         => 'color-picker',
			'label'        => __( 'Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .jet-booking-calendar thead .caption .prev' => 'color: {{VALUE}}',
				'{{WRAPPER}} .jet-booking-calendar thead .caption .next' => 'color: {{VALUE}}',
			],
		] );

		$this->controls_manager->add_control( [
			'id'           => 'month_switcher_bg_color',
			'type'         => 'color-picker',
			'label'        => __( 'Background Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .jet-booking-calendar thead .caption .prev' => 'background-color: {{VALUE}}',
				'{{WRAPPER}} .jet-booking-calendar thead .caption .next' => 'background-color: {{VALUE}}',
			],
		] );

		$this->controls_manager->end_tab();

		$this->controls_manager->start_tab(
			'style_controls',
			[
				'id'    => 'month_switcher_hover',
				'title' => __( 'Hover', 'jet-booking' ),
			]
		);

		$this->controls_manager->add_control( [
			'id'           => 'month_switcher_color_hover',
			'type'         => 'color-picker',
			'label'        => __( 'Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .jet-booking-calendar .date-picker-wrapper thead .caption .prev:hover' => 'color: {{VALUE}}',
				'{{WRAPPER}} .jet-booking-calendar .date-picker-wrapper thead .caption .next:hover' => 'color: {{VALUE}}',
			],
		] );

		$this->controls_manager->add_control( [
			'id'           => 'month_switcher_bg_color_hover',
			'type'         => 'color-picker',
			'label'        => __( 'Background Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .jet-booking-calendar .date-picker-wrapper thead .caption .prev:hover' => 'background-color: {{VALUE}}',
				'{{WRAPPER}} .jet-booking-calendar .date-picker-wrapper thead .caption .next:hover' => 'background-color: {{VALUE}}',
			],
		] );

		$this->controls_manager->add_control( [
			'id'           => 'month_switcher_border_color_hover',
			'type'         => 'color-picker',
			'label'        => __( 'Border Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .jet-booking-calendar thead .caption .prev:hover' => 'border-color: {{VALUE}}',
				'{{WRAPPER}} .jet-booking-calendar thead .caption .next:hover' => 'border-color: {{VALUE}}',
			],
		] );

		$this->controls_manager->end_tab();

		$this->controls_manager->end_tabs();

		$this->controls_manager->add_responsive_control( [
			'id'           => 'month_switcher_border',
			'type'         => 'border',
			'label'        => __( 'Border', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .jet-booking-calendar thead .caption .prev' => 'border-style: {{STYLE}}; border-width: {{WIDTH}}; border-radius: {{RADIUS}}; border-color: {{COLOR}}',
				'{{WRAPPER}} .jet-booking-calendar thead .caption .next' => 'border-style: {{STYLE}}; border-width: {{WIDTH}}; border-radius: {{RADIUS}}; border-color: {{COLOR}}',
			]
		] );

		$this->controls_manager->end_section();

		$this->controls_manager->start_section(
			'style_controls',
			[
				'id'    => 'section_days_style',
				'title' => __( 'Days', 'jet-booking' ),
			]
		);

		$this->controls_manager->add_control( [
			'id'           => 'calendar_days_typography',
			'type'         => 'typography',
			'css_selector' => [
				'{{WRAPPER}} .jet-booking-calendar .date-picker-wrapper .month-wrapper tbody .day' => 'font-family: {{FAMILY}}; font-weight: {{WEIGHT}}; text-transform: {{TRANSFORM}}; font-style: {{STYLE}}; text-decoration: {{DECORATION}}; line-height: {{LINEHEIGHT}}{{LH_UNIT}}; letter-spacing: {{LETTERSPACING}}{{LS_UNIT}}; font-size: {{SIZE}}{{S_UNIT}};',
			],
		] );

		$this->controls_manager->add_control( [
			'id'           => 'calendar_days_color',
			'type'         => 'color-picker',
			'label'        => __( 'Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .jet-booking-calendar tbody .day.toMonth.valid' => 'color: {{VALUE}} !important;',
			],
		] );

		$this->controls_manager->add_control( [
			'id'           => 'calendar_days_bg_color',
			'type'         => 'color-picker',
			'label'        => __( 'Background Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .jet-booking-calendar tbody .day' => 'background-color: {{VALUE}} !important;',
			],
		] );

		$this->controls_manager->start_tabs(
			'style_controls',
			[
				'id'        => 'tabs_days_style',
				'separator' => 'after',
			]
		);

		$this->controls_manager->start_tab(
			'style_controls',
			[
				'id'    => 'calendar_days_inactive',
				'title' => __( 'Inactive', 'jet-booking' ),
			]
		);

		$this->controls_manager->add_control( [
			'id'           => 'calendar_days_inactive_color',
			'type'         => 'color-picker',
			'label'        => __( 'Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .jet-booking-calendar tbody div.day.invalid' => 'color: {{VALUE}} !important;',
			],
		] );

		$this->controls_manager->add_control( [
			'id'           => 'calendar_days_inactive_bg_color',
			'type'         => 'color-picker',
			'label'        => __( 'Background Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .jet-booking-calendar tbody div.day.invalid' => 'background-color: {{VALUE}} !important;',
			],
		] );

		$this->controls_manager->add_control( [
			'id'           => 'calendar_days_inactive_border_color',
			'type'         => 'color-picker',
			'label'        => __( 'Border Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .jet-booking-calendar tbody div.day.invalid' => 'border-color: {{VALUE}}',
			],
		] );

		$this->controls_manager->end_tab();

		$this->controls_manager->start_tab(
			'style_controls',
			[
				'id'    => 'calendar_days_today',
				'title' => __( 'Today', 'jet-booking' ),
			]
		);

		$this->controls_manager->add_control( [
			'id'           => 'calendar_days_today_color',
			'type'         => 'color-picker',
			'label'        => __( 'Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .jet-booking-calendar tbody .day.real-today:not(.invalid)' => 'color: {{VALUE}} !important;',
			],
		] );

		$this->controls_manager->add_control( [
			'id'           => 'calendar_days_today_bg_color',
			'type'         => 'color-picker',
			'label'        => __( 'Background Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .jet-booking-calendar tbody .day.real-today:not(.invalid)' => 'background-color: {{VALUE}} !important;',
			],
		] );

		$this->controls_manager->add_control( [
			'id'           => 'calendar_days_today_border_color',
			'type'         => 'color-picker',
			'label'        => __( 'Border Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .jet-booking-calendar tbody .day.real-today:not(.invalid)' => 'border-color: {{VALUE}}',
			],
		] );

		$this->controls_manager->end_tab();

		$this->controls_manager->start_tab(
			'style_controls',
			[
				'id'    => 'calendar_days_edges',
				'title' => __( 'Start/End', 'jet-booking' ),
			]
		);

		$this->controls_manager->add_control( [
			'id'           => 'calendar_days_edges_color',
			'type'         => 'color-picker',
			'label'        => __( 'Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .jet-booking-calendar tbody div.day.toMonth.checked.first-date-selected' => 'color: {{VALUE}} !important',
				'{{WRAPPER}} .jet-booking-calendar tbody div.day.toMonth.checked.last-date-selected'  => 'color: {{VALUE}} !important',
			],
		] );

		$this->controls_manager->add_control( [
			'id'           => 'calendar_days_edges_bg_color',
			'type'         => 'color-picker',
			'label'        => __( 'Background Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .jet-booking-calendar tbody div.day.toMonth.first-date-selected' => 'background-color: {{VALUE}} !important',
				'{{WRAPPER}} .jet-booking-calendar tbody div.day.toMonth.last-date-selected'  => 'background-color: {{VALUE}} !important',
			],
		] );

		$this->controls_manager->add_control( [
			'id'           => 'calendar_days_edges_border_color',
			'type'         => 'color-picker',
			'label'        => __( 'Border Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .jet-booking-calendar tbody div.day.first-date-selected' => 'border-color: {{VALUE}} !important',
				'{{WRAPPER}} .jet-booking-calendar tbody div.day.last-date-selected'  => 'border-color: {{VALUE}} !important',
			],
		] );

		$this->controls_manager->end_tab();

		$this->controls_manager->start_tab(
			'style_controls',
			[
				'id'    => 'calendar_days_trace',
				'title' => __( 'Trace', 'jet-booking' ),
			]
		);

		$this->controls_manager->add_control( [
			'id'           => 'calendar_days_trace_color',
			'type'         => 'color-picker',
			'label'        => __( 'Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .jet-booking-calendar tbody div.day.toMonth.checked'           => 'color: {{VALUE}} !important;',
				'{{WRAPPER}} .jet-booking-calendar tbody div.day.toMonth.hovering'          => 'color: {{VALUE}} !important;',
				'{{WRAPPER}} .jet-booking-calendar tbody div.day.has-tooltip:hover' => 'color: {{VALUE}} !important;',
			],
		] );

		$this->controls_manager->add_control( [
			'id'           => 'calendar_days_trace_bg_color',
			'type'         => 'color-picker',
			'label'        => __( 'Background Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .jet-booking-calendar tbody div.day.checked'           => 'background-color: {{VALUE}} !important;',
				'{{WRAPPER}} .jet-booking-calendar tbody div.day.hovering'          => 'background-color: {{VALUE}} !important;',
				'{{WRAPPER}} .jet-booking-calendar tbody div.day.has-tooltip:hover' => 'background-color: {{VALUE}} !important;',
			],
		] );

		$this->controls_manager->add_control( [
			'id'           => 'calendar_days_trace_border_color',
			'type'         => 'color-picker',
			'label'        => __( 'Border Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .jet-booking-calendar tbody div.day.checked'           => 'border-color: {{VALUE}}',
				'{{WRAPPER}} .jet-booking-calendar tbody div.day.hovering'          => 'border-color: {{VALUE}}',
				'{{WRAPPER}} .jet-booking-calendar tbody div.day.has-tooltip:hover' => 'border-color: {{VALUE}}',
			],
		] );

		$this->controls_manager->end_tab();

		$this->controls_manager->end_tabs();

		$this->controls_manager->add_responsive_control( [
			'id'           => 'calendar_days_border',
			'type'         => 'border',
			'label'        => __( 'Border', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .jet-booking-calendar tbody .day' => 'border-style: {{STYLE}}; border-width: {{WIDTH}}; border-radius: {{RADIUS}}; border-color: {{COLOR}}',
			]
		] );

		$this->controls_manager->add_responsive_control( [
			'id'           => 'calendar_days_padding',
			'type'         => 'dimensions',
			'label'        => __( 'Padding', 'jet-booking' ),
			'units'        => [ 'px', 'em', '%' ],
			'css_selector' => [
				'{{WRAPPER}} .jet-booking-calendar tbody .day' => 'padding: {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}} !important;',
			],
		] );

		$this->controls_manager->add_control( [
			'id'      => 'cell_heading',
			'type'    => 'text',
			'content' => __( 'Cell', 'jet-booking' ),
		] );

		$this->controls_manager->add_control( [
			'id'           => 'calendar_cell_bg_color',
			'type'         => 'color-picker',
			'label'        => __( 'Background Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .jet-booking-calendar .date-picker-wrapper .month-wrapper tbody td' => 'background-color: {{VALUE}}',
			],
		] );

		$this->controls_manager->add_responsive_control( [
			'id'           => 'calendar_cell_border',
			'type'         => 'border',
			'label'        => __( 'Border', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .jet-booking-calendar .date-picker-wrapper .month-wrapper tbody td' => 'border-style: {{STYLE}}; border-width: {{WIDTH}}; border-radius: {{RADIUS}}; border-color: {{COLOR}}',
			]
		] );

		$this->controls_manager->add_responsive_control( [
			'id'           => 'calendar_cell_padding',
			'type'         => 'dimensions',
			'label'        => __( 'Padding', 'jet-booking' ),
			'units'        => [ 'px', 'em', '%' ],
			'css_selector' => [
				'{{WRAPPER}} .jet-booking-calendar .date-picker-wrapper .month-wrapper tbody td' => 'padding: {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}};',
			],
		] );

		$this->controls_manager->add_control( [
			'id'      => 'week_days_heading',
			'type'    => 'text',
			'content' => __( 'Headings', 'jet-booking' ),
		] );

		$this->controls_manager->add_control( [
			'id'           => 'week_days_typography',
			'type'         => 'typography',
			'css_selector' => [
				'{{WRAPPER}} .jet-booking-calendar thead .week-name th' => 'font-family: {{FAMILY}}; font-weight: {{WEIGHT}}; text-transform: {{TRANSFORM}}; font-style: {{STYLE}}; text-decoration: {{DECORATION}}; line-height: {{LINEHEIGHT}}{{LH_UNIT}}; letter-spacing: {{LETTERSPACING}}{{LS_UNIT}}; font-size: {{SIZE}}{{S_UNIT}};',
			],
		] );

		$this->controls_manager->add_control( [
			'id'           => 'week_days_color',
			'type'         => 'color-picker',
			'label'        => __( 'Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .jet-booking-calendar thead .week-name' => 'color: {{VALUE}}',
			],
		] );

		$this->controls_manager->add_control( [
			'id'           => 'week_days_bg_color',
			'type'         => 'color-picker',
			'label'        => __( 'Background Color', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .jet-booking-calendar .date-picker-wrapper .month-wrapper thead .week-name' => 'background-color: {{VALUE}}',
			],
		] );

		$this->controls_manager->add_responsive_control( [
			'id'           => 'week_days_border',
			'type'         => 'border',
			'label'        => __( 'Border', 'jet-booking' ),
			'css_selector' => [
				'{{WRAPPER}} .jet-booking-calendar thead .week-name th' => 'border-style: {{STYLE}}; border-width: {{WIDTH}}; border-radius: {{RADIUS}}; border-color: {{COLOR}}',
			]
		] );

		$this->controls_manager->add_responsive_control( [
			'id'           => 'week_days_padding',
			'type'         => 'dimensions',
			'label'        => __( 'Padding', 'jet-booking' ),
			'units'        => [ 'px', 'em', '%' ],
			'css_selector' => [
				'{{WRAPPER}} .jet-booking-calendar thead .week-name th' => 'padding: {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}};',
			],
		] );

		$this->controls_manager->end_section();

	}

}