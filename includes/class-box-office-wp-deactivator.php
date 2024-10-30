<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://line.industries
 * @package    Box_Office_WP
 * @subpackage Box_Office_WP/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @package    Box_Office_WP
 * @subpackage Box_Office_WP/includes
 * @author     Line Industries <support@lineindustries.com>
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Box_Office_WP_Deactivator {

	public static function deactivate()
	{
		Box_Office_WP::log('Deactivate');

		$box_office_wp_api = new Box_Office_WP_Spektrix_Api();

		$box_office_wp_api->remove_event_list_from_cache();
		$box_office_wp_api->remove_custom_event_filters_from_cache();
	}
}
