<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://line.industries
 * @package    Box_Office_WP
 * @subpackage Box_Office_WP/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @package    Box_Office_WP
 * @subpackage Box_Office_WP/includes
 * @author     Line Industries <support@lineindustries.com>
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Box_Office_WP
{
	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 */
	public function __construct()
	{
		if(defined('BOX_OFFICE_WP_VERSION'))
			$this->version = BOX_OFFICE_WP_VERSION;
		else
			$this->version = '1.4.0';

		$this->plugin_name = 'box-office-wp';

		$this->load_dependencies();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 */
	public function run()
	{
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 */
	public function get_plugin_name() : string
	{
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 */
	public function get_loader()
	{
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 */
	public function get_version()
	{
		return $this->version;
	}

	/**
	 * Writes message to WordPress error log, assuming plugin settings are configured to enable logging. Use the 
	 * force_logging parameter to log regardless of plugin settings.
	 */
	public static function log($message, bool $force_logging = false)
	{
		if($force_logging || Box_Office_WP_Settings::load_settings()->debug_log_enabled)
			error_log(print_r('Box_Office_WP - ' . $message, true));
	}

	public static function pretty_var_dump($object)
	{
		echo esc_html('<pre>' . var_export($object, true) . '</pre>');
	}

	public static function in_pro_mode() : bool
	{
		$pro_directory_path = dirname(__FILE__) . '/../' . Box_Office_WP_Constants::$pro_directory_name;

		return file_exists($pro_directory_path);
	}

	public static function get_plugin_display_name() : string
	{
		return self::in_pro_mode() ? Box_Office_WP_Constants::$pro_plugin_name : Box_Office_WP_Constants::$free_plugin_name;
	}

	/**
	 * Load the required dependencies for this plugin.
	 */
	private function load_dependencies()
	{
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-box-office-wp-constants.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-box-office-wp-custom-filter.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-box-office-wp-settings.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-box-office-wp-shortcodes.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-box-office-wp-event-list-logic.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-box-office-wp-api.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-box-office-wp-block-logic.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-box-office-wp-rest-api.php';

		if($this->in_pro_mode())
		{
			require_once plugin_dir_path(dirname(__FILE__)) . 'pro/includes/class-box-office-wp-pro-activator.php';
			require_once plugin_dir_path(dirname(__FILE__)) . 'pro/includes/class-box-office-wp-pro-block-logic.php';
			require_once plugin_dir_path(dirname(__FILE__)) . 'pro/includes/class-box-office-wp-pro-shortcodes.php';
			require_once plugin_dir_path(dirname(__FILE__)) . 'pro/includes/class-box-office-wp-pro-update.php';
		}

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-box-office-wp-loader.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-box-office-wp-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-box-office-wp-public.php';

		$this->loader = new Box_Office_WP_Loader();
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 */
	private function define_admin_hooks()
	{
		$plugin_admin = new Box_Office_WP_Admin($this->get_plugin_name(), $this->get_version());
		$block_logic = new Box_Office_WP_Block_Logic($this->get_plugin_name(), $this->get_version());
        $rest_api = new Box_Office_WP_Rest_Api($this->get_plugin_name(), $this->get_version());

		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
		$this->loader->add_action('admin_menu', $plugin_admin, 'add_menu_page');
		$this->loader->add_action('enqueue_block_editor_assets', $block_logic, 'enqueue_block_assets');
        $this->loader->add_action('rest_api_init', $rest_api, 'register_rest_routes');
		$this->loader->add_filter('block_categories_all', $block_logic, 'block_categories_all');
        if(is_admin() && $this->in_pro_mode()){
			new Box_Office_WP_Update($this->get_plugin_name(), $this->get_version());
        }	
    }

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 */
	private function define_public_hooks()
	{
		$plugin_public = new Box_Office_WP_Public($this->get_plugin_name(), $this->get_version());
		$plugin_shortcodes = new Box_Office_WP_Shortcodes();

		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
		$this->loader->add_filter('the_title', $plugin_public, 'alter_title');
		$this->loader->add_action('template_redirect', $plugin_public, 'box_office_wp_template_redirect');
		$this->loader->add_action('init', $plugin_public, 'box_office_wp_init');
		$this->loader->add_filter('query_vars', $plugin_public, 'box_office_wp_init_query_vars');
		$this->loader->add_action('wp_head', $plugin_public, 'box_office_wp_wp_head');
		$this->loader->add_filter('pre_get_document_title', $plugin_public, 'box_office_wp_pre_get_document_title');
		$this->loader->add_action('wp_enqueue_scripts', $plugin_shortcodes, 'enqueue_spektrix_resize_script');

		$box_office_wp_shortcodes = new Box_Office_WP_Shortcodes();

		$box_office_wp_shortcodes->add_shortcodes();
	}
}
