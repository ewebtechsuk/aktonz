<?php
/**
 * Core plugin functionality.
 *
 * @package AdminNoticesManager
 * @since   1.0.0
 */

namespace AdminNoticesManager\Core;

use AdminNoticesManager\Notices;
use AdminNoticesManager\Pointers;
use AdminNoticesManager\Settings;
use AdminNoticesManager\Select2_WPWS;

/**
 * Default setup routine
 *
 * @return void
 */
function setup() {
	$n = function ( $anm_function ) {
		return __NAMESPACE__ . "\\$anm_function";
	};

	\add_action( 'init', $n( 'i18n' ) );
	\add_action( 'init', $n( 'init' ) );
	\add_action( 'admin_enqueue_scripts', $n( 'admin_scripts' ) );
	\add_action( 'admin_enqueue_scripts', $n( 'admin_styles' ) );

	\add_action( 'wp_ajax_anm_purge_notices', $n( 'purge_notices' ) );

	\do_action( 'admin_notices_manager_loaded' );

	\add_action( 'admin_init', $n( 'on_plugin_update' ), 10 );
}

/**
 * Handle plugin updated and notices.
 *
 * @return void
 *
 * @since 1.0.0
 */
function on_plugin_update() {
	$stored_version    = get_site_option( 'anm_active_version', false );
	$existing_settings = get_site_option( 'anm_settings', false );

	// Has existing settings.
	if ( $existing_settings && ! empty( $existing_settings ) ) {
		if ( ! empty( $stored_version ) && version_compare( $stored_version, ADMIN_NOTICES_MANAGER_VERSION, '<' ) ) {
			update_site_option( 'anm_active_version', ADMIN_NOTICES_MANAGER_VERSION );
			\do_action( 'admin_notices_manager_updated' );
		} elseif ( empty( $stored_version ) ) {
			update_site_option( 'anm_active_version', ADMIN_NOTICES_MANAGER_VERSION );
		}
	}

	if ( ! $stored_version ) {
		update_site_option( 'anm_active_version', ADMIN_NOTICES_MANAGER_VERSION );
	}
}

/**
 * Registers the default textdomain.
 *
 * @return void
 */
function i18n() {
	$locale = apply_filters( 'plugin_locale', get_locale(), 'admin-notices-manager' );
	load_textdomain( 'admin-notices-manager', WP_LANG_DIR . '/admin-notices-manager/admin-notices-manager-' . $locale . '.mo' );
	load_plugin_textdomain( 'admin-notices-manager', false, plugin_basename( ADMIN_NOTICES_MANAGER_PATH ) . '/languages/' );
}

/**
 * Initializes the plugin and fires an action other plugins can hook into.
 *
 * @return void
 */
function init() {

	if ( is_admin() ) {

		if ( class_exists( Select2_WPWS::class ) ) {
			Select2_WPWS::init( ADMIN_NOTICES_MANAGER_URL . 'includes/classes/vendor/Select2' );
		}

		// Check if the notices can be hidden for the currently logged-in user.
		$notice_hiding_allowed = Settings::notice_hiding_allowed_for_current_user();

		Notices::init( $notice_hiding_allowed );
		Pointers::init();

		Settings::init();
	}

	do_action( 'admin_notices_manager_init' );
}

/**
 * Activate the plugin
 *
 * @return void
 */
function activate() {
	// First load the init scripts in case any rewrite functionality is being loaded.
	init();
	update_option( 'anm-plugin-installed-by-user-id', get_current_user_id(), false );
	flush_rewrite_rules();
}

/**
 * Deactivate the plugin
 *
 * Uninstall routines should be in uninstall.php
 *
 * @return void
 */
function deactivate() {
}


/**
 * The list of knows contexts for enqueuing scripts/styles.
 *
 * @return array
 */
function get_enqueue_contexts() {
	return array( 'admin' );
}

/**
 * Generate an URL to a script, taking into account whether SCRIPT_DEBUG is enabled.
 *
 * @param string $script  Script file name (no .js extension).
 * @param string $context Context for the script ('admin', 'frontend', or 'shared').
 *
 * @return string|WP_Error URL
 */
function script_url( $script, $context ) {

	if ( ! in_array( $context, get_enqueue_contexts(), true ) ) {
		return new \WP_Error( 'invalid_enqueue_context', 'Invalid $context specified in AdminNoticesManager script loader.' );
	}

	return ADMIN_NOTICES_MANAGER_URL . 'assets/dist/js/' . $script . '.js';
}

/**
 * Generate an URL to a stylesheet, taking into account whether SCRIPT_DEBUG is enabled.
 *
 * @param string $stylesheet Stylesheet file name (no .css extension).
 * @param string $context    Context for the script ('admin', 'frontend', or 'shared').
 *
 * @return string URL
 */
function style_url( $stylesheet, $context ) {

	if ( ! in_array( $context, get_enqueue_contexts(), true ) ) {
		return new \WP_Error( 'invalid_enqueue_context', 'Invalid $context specified in AdminNoticesManager stylesheet loader.' );
	}

	return ADMIN_NOTICES_MANAGER_URL . 'assets/dist/css/' . $stylesheet . '.css';
}

/**
 * Enqueue scripts for admin.
 *
 * @return void
 */
function admin_scripts() {

	wp_enqueue_script(
		'admin_notices_manager_settings',
		script_url( 'settings', 'admin' ),
		array(),
		ADMIN_NOTICES_MANAGER_VERSION,
		true
	);

	if ( ! Settings::notice_hiding_allowed_for_current_user() ) {
		return;
	}

	add_thickbox();

	wp_enqueue_script(
		'admin_notices_manager_notices',
		script_url( 'notices', 'admin' ),
		array( 'thickbox' ),
		ADMIN_NOTICES_MANAGER_VERSION,
		true
	);

	$system_messages = array(
		// Pages and posts.
		esc_html__( 'Post draft updated.' ),
		esc_html__( 'Post updated.' ),
		esc_html__( 'Page draft updated.' ),
		esc_html__( 'Page updated.' ),
		esc_html__( '1 post not updated, somebody is editing it.' ),
		esc_html__( '1 page not updated, somebody is editing it.' ),

		// Comments.
		esc_html__( 'Invalid comment ID.' ),
		esc_html__( 'Sorry, you are not allowed to edit comments on this post.' ),
		esc_html__( 'This comment is already approved.' ),
		esc_html__( 'This comment is already in the Trash.' ),
		esc_html__( 'This comment is already marked as spam.' ),

		// Users.
		esc_html__( 'New user created.' ),
		esc_html__( 'User updated.' ),
		esc_html__( 'User deleted.' ),
		esc_html__( 'Changed roles.' ),
		esc_html__( 'The current user&#8217;s role must have user editing capabilities.' ),
		esc_html__( 'Other user roles have been changed.' ),
		esc_html__( 'You can&#8217;t delete the current user.' ),
		esc_html__( 'Other users have been deleted.' ),
		esc_html__( 'User removed from this site.' ),
		esc_html__( "You can't remove the current user." ),
		esc_html__( 'Other users have been removed.' ),
		esc_html__( 'Please enter a nickname.' ),
		esc_html__( 'Please enter an email address.' ),

		// Themes.
		esc_html__( 'The active theme is broken. Reverting to the default theme.' ),
		esc_html__( 'Settings saved and theme activated.' ),
		esc_html__( 'New theme activated.' ),
		esc_html__( 'Theme deleted.' ),
		esc_html__( 'You cannot delete a theme while it has an active child theme.' ),
		esc_html__( 'Theme resumed.' ),
		esc_html__( 'Theme could not be resumed because it triggered a <strong>fatal error</strong>.' ),
		esc_html__( 'Theme will be auto-updated.' ),
		esc_html__( 'Theme will no longer be auto-updated.' ),

		// Plugins.
		esc_html__( 'Plugin activated.' ),
		esc_html__( 'Plugin deactivated.' ),
		esc_html__( 'Plugin downgraded successfully.' ),
		esc_html__( 'Plugin updated successfully.' ),

		// Settings.
		esc_html__( 'Settings saved.' ),
		esc_html__( 'Permalink structure updated.' ),
		// translators: file name.
		esc_html__( 'You should update your %s file now.' ),
		// translators: file name.
		esc_html__( 'Permalink structure updated. Remove write access on %s file now!' ),
		esc_html__( 'Privacy Policy page updated successfully.' ),
		esc_html__( 'The currently selected Privacy Policy page does not exist. Please create or select a new page.' ),
		// translators: file name.
		esc_html__( 'The currently selected Privacy Policy page is in the Trash. Please create or select a new Privacy Policy page or <a href="%s">restore the current page</a>.' ),

		// Multisite.
		esc_html__( 'Sites removed from spam.' ),
		esc_html__( 'Sites marked as spam.' ),
		esc_html__( 'Sites deleted.' ),
		esc_html__( 'Site deleted.' ),
		esc_html__( 'Sorry, you are not allowed to delete that site.' ),
		esc_html__( 'Site archived.' ),
		esc_html__( 'Site unarchived.' ),
		esc_html__( 'Site activated.' ),
		esc_html__( 'Site deactivated.' ),
		esc_html__( 'Site removed from spam.' ),
		esc_html__( 'Site marked as spam.' ),

		// Personal data export.
		esc_html__( 'Unable to initiate confirmation request.' ),
		esc_html__( 'Unable to initiate user privacy confirmation request.' ),
		esc_html__( 'Unable to add this request. A valid email address or username must be supplied.' ),
		esc_html__( 'Invalid user privacy action.' ),
		esc_html__( 'Confirmation request sent again successfully.' ),
		esc_html__( 'Confirmation request initiated successfully.' ),
	);

	$plural_system_messages = array(
		// Posts and pages.
		array( '%s post permanently deleted.', '%s posts permanently deleted.' ),
		array( '%s post moved to the Trash.', '%s posts moved to the Trash.' ),
		array( '%s post restored from the Trash.', '%s posts restored from the Trash.' ),
		array( '%s page permanently deleted.', '%s pages permanently deleted.' ),
		array( '%s page moved to the Trash.', '%s pages moved to the Trash.' ),
		array( '%s page restored from the Trash.', '%s pages restored from the Trash.' ),
		array( '%s post updated.', '%s posts updated.' ),
		array( '%s post not updated, somebody is editing it.', '%s posts not updated, somebody is editing them.' ),
		array( '%s page updated.', '%s pages updated.' ),
		array( '%s page not updated, somebody is editing it.', '%s pages not updated, somebody is editing them.' ),

		// Comments.
		array( '%s comment approved.', '%s comments approved.' ),
		array( '%s comment marked as spam.', '%s comments marked as spam.' ),
		array( '%s comment restored from the spam.', '%s comments restored from the spam.' ),
		array( '%s comment moved to the Trash.', '%s comments moved to the Trash.' ),
		array( '%s comment restored from the Trash.', '%s comments restored from the Trash.' ),
		array( '%s comment permanently deleted.', '%s comments permanently deleted.' ),

		// Users.
		array( '%s user deleted.', '%s users deleted.' ),

		// Personal data export.
		array( '%d confirmation request failed to resend.', '%d confirmation requests failed to resend.' ),
		array( '%d confirmation request re-sent successfully.', '%d confirmation requests re-sent successfully.' ),
		array( '%d request marked as complete.', '%d requests marked as complete.' ),
		array( '%d request failed to delete.', '%d requests failed to delete.' ),
		array( '%d request deleted successfully.', '%d requests deleted successfully.' ),
	);

	foreach ( $plural_system_messages as $message ) {
		array_push( $system_messages, _n( $message[0], $message[1], 0 ) ); // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralSingle, WordPress.WP.I18n.NonSingularStringLiteralPlural
		array_push( $system_messages, _n( $message[0], $message[1], 1 ) ); // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralSingle, WordPress.WP.I18n.NonSingularStringLiteralPlural
		array_push( $system_messages, _n( $message[0], $message[1], 2 ) ); // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralSingle, WordPress.WP.I18n.NonSingularStringLiteralPlural
		array_push( $system_messages, _n( $message[0], $message[1], 5 ) ); // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralSingle, WordPress.WP.I18n.NonSingularStringLiteralPlural
	}

	\wp_localize_script(
		'admin_notices_manager_notices',
		'anm_i18n',
		array(
			'title'              => esc_html__( 'Admin notices', 'admin-notices-manager' ),
			'title_empty'        => esc_html__( 'No admin notices', 'admin-notices-manager' ),
			'date_time_preamble' => esc_html__( 'First logged:', 'admin-notices-manager' ) . ' ',
			'hide_notice_text'   => esc_attr__( 'Hide notice forever', 'admin-notices-manager' ),
			'hide_notice'        => esc_attr__( 'Hide this notice', 'admin-notices-manager' ),
			'display_notice'     => esc_attr__( 'Display notice as normal', 'admin-notices-manager' ),
			'system_messages'    => $system_messages,
			'settings'           => Settings::get_settings(),
			'ajaxurl'            => admin_url( 'admin-ajax.php' ),
			'nonce'              => wp_create_nonce( 'anm-ajax-nonce' ),
		)
	);

	wp_localize_script(
		'admin_notices_manager_settings',
		'anm_settings',
		array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
		)
	);
}

/**
 * Enqueue styles for admin.
 *
 * @return void
 */
function admin_styles() {

	wp_enqueue_style(
		'admin_notices_manager_admin',
		style_url( 'admin-style', 'admin' ),
		array(),
		ADMIN_NOTICES_MANAGER_VERSION
	);
}

/**
 * Simple function to clear hidden notices.
 *
 * @return void
 */
function purge_notices() {
	if ( ! isset( $_REQUEST['nonce'] ) || ! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_REQUEST['nonce'] ) ), 'anm_purge_notices_nonce' ) ) {
		exit;
	}
	
	\update_option( 'anm-hidden-notices', array() );
	\wp_send_json_success();
}
