<?php

/**
 * @link       https://line.industries
 * @package    Box_Office_WP
 * @subpackage Box_Office_WP/admin
 */

/**
 * @package    Box_Office_WP
 * @subpackage Box_Office_WP/admin
 * @author     Line Industries <support@lineindustries.com>
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class Box_Office_WP_Admin
{
	private $box_office_wp;
	private $version;

	/**
	 * Initialize the class and set its properties.
	 */
	public function __construct(string $box_office_wp, string $version)
	{
		$this->box_office_wp = $box_office_wp;
		$this->version = $version;

		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/box-office-wp-admin-display.php';
	}

	public function add_menu_page()
	{
		add_menu_page(
			'BoxOffice WP',
			'BoxOffice WP',
			'manage_options',
			'box-office-wp',
			array(get_called_class(), 'admin_page_html'),
			''
		);
	}

	public static function admin_page_html()
	{
		Box_Office_WP_Admin_Page::admin_page_html();
	}

	/**
	 * Register the stylesheets for the admin area.
	 */
	public function enqueue_styles()
	{
		wp_enqueue_style($this->box_office_wp, plugins_url('css/box-office-wp-admin.css', __FILE__), array(), $this->version, 'all');
	}

	/**
	 * Register the JavaScript for the admin area.
	 */
	public function enqueue_scripts()
	{
		wp_enqueue_script($this->box_office_wp, plugins_url('js/box-office-wp-admin.js', __FILE__), array('jquery'), $this->version, false);
	}

	/**
	 * Handle the "update settings" button click on admin settings page
	 */
	public static function handle_settings_page_update_settings_button_click()
	{
		if (!isset($_POST['box_office_wp_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['box_office_wp_nonce'])), 'box_office_wp_nonce'))
			return;

		$api_url_has_changed = isset($_POST['spektrix_url']) && Box_Office_WP_Settings::load_settings()->spektrix_url != sanitize_url(wp_unslash($_POST['spektrix_url']));

		$box_office_wp_settings = Box_Office_WP_Settings::update_settings_from_form_post();

		// Some of our configurable settings affect permalinks and rewrite rules, so refresh these.

		Box_Office_WP_Public::add_whats_on_rewrite_rule();
		flush_rewrite_rules(false);

		// Rebuilding the cached event list can be expensive/slow to re-populate, so we only do this if the Spektrix 
		// URL has changed.

		if ($api_url_has_changed)
			self::clear_cached_event_list();
	}

	/**
	 * Handle the "clear cache" button click on admin settings page
	 */
	public static function handle_settings_page_clear_cache_button_click()
	{
		self::clear_cached_event_list();
	}

	/**
	 * Handle the "reset settings" button click on admin settings page
	 */
	public static function handle_settings_page_reset_settings_button_click()
	{
		Box_Office_WP_Settings::delete_settings();
	}

	public static function clear_cached_event_list()
	{
		Box_Office_WP::log('Clearing and rebuilding cached event list.');

		$box_office_wp_api = new Box_Office_WP_Spektrix_Api();

		$box_office_wp_api->remove_event_list_from_cache();
		$box_office_wp_api->remove_custom_event_filters_from_cache();

		// Now that we've cleared the cached event list, fetch it again from the API.

		$event_list = $box_office_wp_api->get_event_list(Box_Office_WP_Constants::$default_event_list_limit);

		// We will have fetched a brand new event list - the associated order data would ordinarily get updated over 
		// the next X requests, but it would be better to fetch it all now, in one hit - that way the admin user take 
		// this hit rather than the next public visitor.

		$box_office_wp_api->update_offers_if_needed($event_list, Box_Office_WP_Constants::$default_event_list_limit);
	}

	public static function get_event_list_last_fetched_from_api()
	{
		$box_office_wp_api = new Box_Office_WP_Spektrix_Api();

		$event_list_last_fetched_from_api = $box_office_wp_api->get_event_list_last_fetched_from_api();

		if ($event_list_last_fetched_from_api === false)
			$event_list_last_fetched_from_api = 'Expired';

		return $event_list_last_fetched_from_api;
	}

	public static function get_cached_event_list_count()
	{
		$box_office_wp_api = new Box_Office_WP_Spektrix_Api();

		$event_list = $box_office_wp_api->get_event_list(Box_Office_WP_Constants::$default_event_list_limit);

		return count($event_list);
	}

	public static function get_events_with_stale_offer_data_count()
	{
		$box_office_wp_api = new Box_Office_WP_Spektrix_Api();

		$stale_offer_data_count = 0;

		$event_list = $box_office_wp_api->get_event_list(Box_Office_WP_Constants::$default_event_list_limit);

		foreach ($event_list as $event)
			if ($event->need_to_refresh_offers)
				$stale_offer_data_count++;

		return $stale_offer_data_count;
	}
}
