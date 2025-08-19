<?php
/**
 * Class adding plugin's pointers.
 *
 * @package AdminNoticesManager
 */

declare(strict_types=1);

namespace AdminNoticesManager;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\AdminNoticesManager\Pointers' ) ) {
	/**
	 * Responsible for showing the pointers.
	 *
	 * @since 1.6.0
	 */
	class Pointers {

		public const POINTER_ADMIN_MENU_NAME          = 'anm-admin-notifications-menu';
		public const POINTER_ADMIN_MENU_SETTINGS_NAME = 'anm-admin-settings-menu';

		/**
		 * Inits the class and sets the hooks
		 *
		 * @return void
		 *
		 * @since 1.6.0
		 */
		public static function init() {

			if ( \is_admin() ) {

				// Check that current user should see the pointers.
				$eligible_user_id = intval( \get_option( 'anm-plugin-installed-by-user-id', 1 ) );
				if ( 0 === $eligible_user_id ) {
					$eligible_user_id = 1;
				}

				$current_user_id = \get_current_user_id();
				if ( 0 === $current_user_id || $current_user_id !== $eligible_user_id ) {
					return;
				}

				if ( $eligible_user_id && ( ! self::is_dismissed( self::POINTER_ADMIN_MENU_NAME ) || ! self::is_dismissed( self::POINTER_ADMIN_MENU_SETTINGS_NAME ) ) ) {
					\add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );
				}
			}
		}

		/**
		 * Adds the necessary scripts to the queue
		 *
		 * @return void
		 *
		 * @since 1.6.0
		 */
		public static function admin_enqueue_scripts() {
			// Using Pointers.
			\wp_enqueue_style( 'wp-pointer' );
			\wp_enqueue_script( 'wp-pointer' );

			\wp_enqueue_script(
				'admin_notices_manager_pointer',
				ADMIN_NOTICES_MANAGER_URL . 'assets/dist/js/pointer.js',
				array( 'wp-pointer' ),
				ADMIN_NOTICES_MANAGER_VERSION,
				true
			);

			\wp_localize_script(
				'admin_notices_manager_pointer',
				'anm_pointer_i18n',
				array(
					'is_dismissed'          => self::is_dismissed( self::POINTER_ADMIN_MENU_NAME ),
					'settings_is_dismissed' => self::is_dismissed( self::POINTER_ADMIN_MENU_SETTINGS_NAME ),
					'menu_name'             => self::POINTER_ADMIN_MENU_NAME,
					'settings_menu_name'    => self::POINTER_ADMIN_MENU_SETTINGS_NAME,
					'content_title'         => esc_html__( 'Admin Notices Manager', 'admin-notices-manager' ),
					'content_text'          => esc_html__( 'From now onward, all the admin notices will be displayed here.', 'admin-notices-manager' ),
					'first_element_id'      => 'wp-admin-bar-anm_notification_count',
					'second_element_id'     => 'menu-settings',
					'second_content_title'  => esc_html__( 'Configure the Admin Notices Manager', 'admin-notices-manager' ),
					'second_content_text'   => esc_html__( 'Configure how the plugin handles different types of admin notices from the Settings > Admin Notices menu item.', 'admin-notices-manager' ),
				)
			);
		}

		/**
		 * Checks if the user already dismissed the message
		 *
		 * @param string $pointer - Name of the pointer to check.
		 *
		 * @return boolean
		 *
		 * @since 1.6.0
		 */
		public static function is_dismissed( string $pointer ): bool {

			$dismissed = array_filter( explode( ',', (string) \get_user_meta( \get_current_user_id(), 'dismissed_wp_pointers', true ) ) );

			return \in_array( $pointer, (array) $dismissed, true );
		}
	}
}
