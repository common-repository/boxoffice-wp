<?php

/**
 * @link              https://line.industries
 * @package           Box-Office-WP
 *
 * @wordpress-plugin
 * Plugin Name:       BoxOffice WP
 * Plugin URI:        https://boxofficewp.com
 * Description:       Easily display data from Spektrix within your WordPress site.
 * Version:           1.4.0
 * Author:            Line Industries
 * Author URI:        https://line.industries
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       box-office-wp
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if(!defined('WPINC'))
	die;

/**
 * Current plugin version.
 */
define( 'BOX_OFFICE_WP_VERSION', '1.4.0' );

/**
 * Plugin file path.
 */
define( 'BOX_OFFICE_WP_FILE', __FILE__ );

/**
 * Plugin file name.
 */
define( 'BOX_OFFICE_WP_FILE_NAME', basename(__FILE__) );

/**
 * Plugin folder name.
 */
define( 'BOX_OFFICE_WP_FOLDER_NAME', basename(dirname(__FILE__)) );

/**
 * The code that runs during plugin activation.
 */
function box_office_wp_activate()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-box-office-wp-activator.php';
	Box_Office_WP_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function box_office_wp_deactivate()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-box-office-wp-deactivator.php';
	Box_Office_WP_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'box_office_wp_activate');
register_deactivation_hook(__FILE__, 'box_office_wp_deactivate');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-box-office-wp.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 */
function box_office_wp_run()
{
	$plugin = new Box_Office_WP();
	$plugin->run();
}
box_office_wp_run();
