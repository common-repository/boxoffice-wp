<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * This file may be updated more in future version of the Boilerplate; however, this is the
 * general skeleton and outline for how the file should work.
 *
 * For more information, see the following discussion:
 * https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate/pull/123#issuecomment-28541913
 *
 * @link       https://line.industries
 * @package    Box_Office_WP
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

require_once plugin_dir_path(__FILE__) . 'includes/class-box-office-wp.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-box-office-wp-constants.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-box-office-wp-settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-box-office-wp-api.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-box-office-wp-custom-filter.php';

Box_Office_WP::log('Beginning uninstall');

Box_Office_WP::log('Clearing cached data');

$box_office_wp_api = new Box_Office_WP_Spektrix_Api();

$box_office_wp_api->remove_event_list_from_cache();
$box_office_wp_api->remove_custom_event_filters_from_cache();

Box_Office_WP::log('Deleting settings');

Box_Office_WP_Settings::delete_settings();

// Don't log or do anything else after deleting settings, or they may well get recreated!