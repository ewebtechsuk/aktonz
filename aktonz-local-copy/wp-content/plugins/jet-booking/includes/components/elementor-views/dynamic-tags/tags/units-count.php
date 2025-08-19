<?php

namespace JET_ABAF\Components\Elementor_Views\Dynamic_Tags\Tags;

use \Elementor\Core\DynamicTags\Tag;
use \Elementor\Modules\DynamicTags\Module as Parent_Module;
use \JET_ABAF\Components\Elementor_Views\Dynamic_Tags\Module as Child_Module;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Units_Count extends Tag {

	/**
	 * Get name.
	 *
	 * Retrieve the dynamic tag name.
	 *
	 * @since 2.2.5
	 *
	 * @return string The name.
	 */
	public function get_name() {
		return 'jet-units-count';
	}

	/**
	 * Get title.
	 *
	 * Retrieve the dynamic tag title.
	 *
	 * @since 2.2.5
	 *
	 * @return string The title.
	 */
	public function get_title() {
		return __( 'Units count', 'jet-booking' );
	}

	/**
	 * Get group.
	 *
	 * Retrieve the dynamic tag group.
	 *
	 * @since 2.2.5
	 *
	 * @return string The group.
	 */
	public function get_group() {
		return Child_Module::JET_GROUP;
	}

	/**
	 * Get categories.
	 *
	 * Retrieve the dynamic tag categories.
	 *
	 * @since 2.2.5
	 *
	 * @return array The categories.
	 */
	public function get_categories() {
		return [
			Parent_Module::TEXT_CATEGORY,
			Parent_Module::NUMBER_CATEGORY,
			Parent_Module::POST_META_CATEGORY,
		];
	}

	/**
	 * Register controls.
	 *
	 * Used to add new controls to any element type.
	 *
	 * @since 2.2.5
	 *
	 * @return void
	 */
	public function render() {

		$units       = jet_abaf()->db->get_apartment_units( get_the_ID() );
		$units_count = ! empty( $units ) ? count( $units ) : $this->get_settings( 'fallback' );

		printf( '<span data-post="%1$s" data-units-count="%2$s">%2$s</span>', get_the_ID(), $units_count );

	}

}
