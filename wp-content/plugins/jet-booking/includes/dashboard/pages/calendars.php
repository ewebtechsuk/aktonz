<?php

namespace JET_ABAF\Dashboard\Pages;

use JET_ABAF\Dashboard\Helpers\Page_Config;

class Calendars extends Base {

	/**
	 * Page slug.
	 *
	 * @return string
	 */
	public function slug() {
		return 'jet-abaf-calendars';
	}

	/**
	 * Page title.
	 *
	 * @return string
	 */
	public function title() {
		return __( 'Calendars', 'jet-booking' );
	}

	/**
	 * Render.
	 *
	 * Page render function.
	 *
	 * @since  2.7.0 Updated styles.
	 * @since  3.0.0 Refactored.
	 * @access public
	 *
	 * @return void
	 */
	public function render() {
		echo '<div id="jet-abaf-ical-page"></div>';
	}

	/**
	 * Return calendars page config object.
	 *
	 * @return Page_Config
	 */
	public function page_config() {
		return new Page_Config( $this->slug(), [
			'api' => jet_abaf()->rest_api->get_urls( false ),
		] );
	}

	/**
	 * Assets.
	 *
	 * Dashboard calendar page specific assets.
	 *
	 * @access public
	 *
	 * @return void
	 */
	public function assets() {
		parent::assets();
		$this->enqueue_script( $this->slug(), 'assets/js/admin/calendars.js' );
	}

	/**
	 * Set to true to hide page from admin menu.
	 *
	 * @return boolean
	 */
	public function is_hidden() {
		return ! jet_abaf()->settings->get( 'ical_synch' );
	}

	/**
	 * Page components templates.
	 *
	 * @return array
	 */
	public function vue_templates() {
		return [
			'calendars',
			'calendars-list',
		];
	}

}