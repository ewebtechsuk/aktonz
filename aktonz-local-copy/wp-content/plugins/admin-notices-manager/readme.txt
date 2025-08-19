=== Admin Notices Manager ===
Contributors: Melapress
Plugin URI: https://melapress.com/wordpress-admin-notices/
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl.html
Tags: admin notices, hide admin notices, manage admin notices, notices, dashboard notices
Requires at least: 5.0
Tested up to: 6.7.2
Stable tag: 1.6.0
Requires PHP: 7.2.0

Better manage admin notices & never miss important developer messages!

== Description ==

<strong>Better manage admin notices & never miss an important message!</strong><br />

WordPress core, themes and plugins developers use admin notices to send you important messages about your website and their software that you use. However, more often than not a cry wolf situation is created due to the overwhelming number of notices and the nature of these messages.

Use the Admin Notices Manager plugin to better manage your WordPress website admin notices - read them at your own convenience and not miss any important WordPress core and developer messages.

##The problem

Pretty much anyone who uses WordPress knows about admin notices. Unfortunately though, they have a negative connotation of them. Very often users are overwhelmed by the number of admin notices that pop up on their WordPress dashboard. Sometimes there are so many that the WordPress dashboard is below the scroll.

This has resulted in users ignoring the admin notices and not reading important messages and information from WordPress core, plugins and also themes developers.

##The Solution

The Admin Notices Manager plugin solves this problem by moving the admin notices out of the main dashboard view to a central place, so you are not disrupted. 

This allows you to keep on working and read the notices at your convenience at a later time, without missing any important WordPress core, plugins and themes messages.

##How it works

The Admin Notices Manager plugin is very easy to use; simply install and activate it on your WordPress website and it will automatically capture all the notifications.

The plugin solves this problem by moving the admin notices out of the main dashboard view to a central place, so you are not disrupted. 

The plugin notifies you of new notices by highlighting the number of new notices in the WordPress toolbar next to your username.

##Benefits & features

* Easily customize how & where the admin notices are displayed
* Customize what action should be taken for every different admin notices type
* Capture any type of admin notices, including ones with custom types
* Easily manage admin notices for a clutter-free admin area
* WordPress system admin notices are shown in the WordPress dashboard


##FREE Plugin Support
Support for Admin Notices Manager is available for free via:

* [forums](https://wordpress.org/support/plugin/admin-notices-manager/)

* [email](https://melapress.com/support/)

##Maintained & Supported by Melapress

Melapress builds high-quality niche WordPress security & management plugins. It's suite of plugins consists of:

* [WP 2FA](https://melapress.com/wordpress-2fa/)
* [CAPTCHA 4WP](https://melapress.com/wordpress-captcha/)
* [Melapress Login Security](https://melapress.com/wordpress-login-security/)
* [WP Activity Log](https://melapress.com/wordpress-activity-log/)

Visit the [Melapress website](https://melapress.com/) for more information about the company and the plugins it develops.

##Related Links and Documentation

* [What are WordPress admin notices & how do they work?](hhttps://melapress.com/how-wordpress-admin-notices-work/)
* [Why WordPress admin notices matter & how to manage them effectively](https://melapress.com/manage-wordpress-admin-notices-effectively/)
* [Admin Notices Manager plugin page](https://melapress.com/wordpress-admin-notices/)

== Installation ==

=== From within WordPress ===

1. Visit 'Plugins > Add New'
1. Search for 'Admin Notices Manager'
1. Install & activate the plugin from your Plugins page.

=== Manually ===

1. Download the plugin from the [WordPress plugins repository](https://wordpress.org/plugins/admin-notices-manager/)
1. Unzip the zip file and upload the `admin-notices-manager` folder to the `/wp-content/plugins/` directory
1. Activate the Admin Notices Manager plugin through the 'Plugins' menu in WordPress

== Screenshots ==

1. The plugin is very easy to use. Once installed it will automatically capture all notifications.
2. When there are new admin notices, the plugin will highlight it by showing the number of notifications in the WordPress toolbar.
3. Admin notices are shown in a retractable sidebar, from where you can mark them as read or permanently hide them.
4. Fully configurable plugin: configure how the plugin should handle the different types of admin notices.
5. Optionally, admin notices can also be shown in a pop-up window, from where you can mark them as read or permanently hide them.

== Changelog ==

= 1.6.0 (2025-02-24) =

* **New Functionality**
	* Added an option to allow specific notifications to appear in the dashboard as usual.

* **Enhancements & improvements**
	* Improved plugin handling of translations, ensuring proper translation of strings within JavaScript code.
	* Enhanced the way notifications are hidden, significantly reducing the "visual flash" effect when a notification disappears.

* **Bug Fixes**
	* Fixed an issue where site notifications were not properly hidden on Simply History plugin pages.
	* Resolved a conflict with the Admin Site Enhancements plugin that caused its main page to appear blank when Admin Notice Manager was active.
	* Fixed a compatibility issue with Gravity Forms, where some Gravity Forms plugin pages appeared blank when our plugin was active.
	* Addressed a visual issue where our plugin incorrectly hid the H5P plugin’s consent notice.
	* Fixed a problem where some notices were missing the "Hide Forever" button.
	* Resolved a bug causing the "Reset list of hidden notices" function to malfunction.
	* Fixed a bug in which "Success level notices" were not being excluded from being hidden according to the plugin settings.
	* Improved the way we count the hidden notifications. Plugin will now report correct number each time.

= 1.5.0 (2024-06-05) =

* **Improvements**
	* Updated some URLs + links to point to the Melapress website.
	* Updated the license file.
	* Added more sanitization and improved code structure in general.
	* Added "Settings" link in the plugin listing on the plugins' page.
	
* **Bug fixes**
	* Fixed: WordPress system notifcations were captured as third party notifications, thus hidden even when they should have not been.
	* Security fix for missing authorization which would allow authenticated subscribers to retrieve list of user email addresses.

= 1.4.0 (2023-02-15) =

* **New features & functionality**
	* Hidden notices can now be purged from within the plugin settings.
	
* **Improvements**
	* Various UI tweaks.
	* Improved support for 3rd-party plugins.
	
* **Bug fixes**
	* Fixed: a conflict with the Loco Translate caused by use of 'notice' css classes.

= 1.3.0 (2022-03-08) =

Release notes: [Admin Notices Manager 1.3: Better support for custom admin notices](https://www.wpwhitesecurity.com/anm-1-3-0/)

* **New features & functionality**
	* Capture and manage custom admin notices with a specific CSS Selector.
	* Specify from which users to hide the admin notices.
	
* **Improvements**
	* Improved PHP8 compatibility.
	* Applied the WordPress Coding Standards to all plugin code.
	* Tidied up the translation domain in the plugin's code.
	* Improved the function that deletes the plugin data upon uninstall.
	
* **Bug fixes**
	* Fixed: a conflict with the WP Mail SMTP plugin breaks the plugin's settings pages.
	* Fixed: fatal error reported in plugin when use on some specific themes.

= 1.2.0 (2021-08-10) =

Release notes: [Admin Notices Manager 1.2: more information about admin notices & other improvements](https://www.wpwhitesecurity.com/anm-1-2-0/)

* **New features**
	* Option to permanently hide specific admin notices.
	* Plugin reports the date and time of when it detects an admin notice.
	* Admin notices are categorized by admin notice level.

* **Improvements**
	* Admin notices are now displayed in a sidebar instead of a popup window. 
	* Plugin data in the database is completely removed upon uninstall.
	* Improved admin notices readability.
	* Removed duplicate code.

* **Bug fix**
	* Plugin settings needed to be saved before the plugin could capture admin notices.   

= 1.1.0 (2021-01-11) =

Release notes: [Admin Notices Manager 1.1: choose which admin notices you want to see & which not](https://www.wpwhitesecurity.com/anm-1-1-0/)

* **New features**
	* New settings to configure which types of admin notices should the plugin ignore, capture and display in central list, or hide completely.

* **Improvements**
	* Plugin automatically detects and allows system messages to appear as per normal (for example; User profile updated). It is also possible to configure the plugin to capture these messages.  

* **Known issue**
	* The tabs in the WooCommerce Membership plugin UI disappear due to a conflict. We have not yet found a solution for this.   

= 1.0 =

Release notes: [Admin Notices Manager: announcing the new plugin](https://www.wpwhitesecurity.com/admin-notices-manager-announcing-the-new-plugin/)

* First release
