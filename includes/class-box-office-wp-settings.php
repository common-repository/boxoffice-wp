<?php

/**
 * Settings class
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class Box_Office_WP_Settings
{
	public static string $update_settings_form_action = 'box_office_wp_update_settings';
	public static string $clear_cache_form_action = 'box_office_wp_clear_cache';
	public static string $reset_settings_form_action = 'box_office_wp_reset_settings';
	public static string $filter_event_list_form_action = 'box_office_wp_filter_event_list';
	public static string $alpha_sort_atoz_form_action = 'box_office_wp_alpha_sort_atoz';
	public static string $alpha_sort_ztoa_form_action = 'box_office_wp_alpha_sort_ztoa';
	public static string $alpha_sort_none_form_action = 'box_office_wp_alpha_sort_none';
	public static string $custom_filter_separator = ';';

	private static string $settings_key = 'box_office_wp_settings';
	private static string $default_api_url = 'https://feed.boxofficewp.com/examplefeed';
	private static string $default_event_list_page_slug = 'whats-on';
	private static string $default_event_details_page_slug = 'event-details';
	private static string $default_basket_page_slug = 'basket';
	private static string $default_account_page_slug = 'account';
	private static string $default_checkout_page_slug = 'checkout';
	private static string $default_event_details_page_name = 'Event Details';
	private static string $default_whats_on_section = 'whats-on';
	private static string $default_event_name_ignore_string = '';
	private static string $default_time_format = ', H:i';
	private static string $default_event_list_layout = '[name link]
[description]
[image link]
[instance_dates]
[book_now]
[more_details]';
	private static int $default_cache_duration_seconds = 3600;
	private static int $minimum_cache_duration_seconds = 60;
	private static bool $default_offer_filter_enabled = false;

	private static string $default_license_key = '';

	// Instance properties of the settings class

	public string $spektrix_url;
	public string $event_list_page_slug;
	public string $event_details_page_slug;
	public string $basket_page_slug;
	public string $account_page_slug;
	public string $checkout_page_slug;
	public string $event_details_page_name;
	public string $whats_on_section;
	public string $event_list_layout;
	public string $date_format;
	public string $event_name_ignore_string;
	public int $cache_duration_seconds;
	public array $custom_filter_list;
	public bool $debug_log_enabled;
	public bool $offer_filter_enabled;
	public bool $include_offer_filter;
	public string $license_key;

	function __construct(string $spektrix_url, string $event_list_page_slug, string $event_details_page_slug, string $basket_page_slug, string $account_page_slug, string $checkout_page_slug, string $event_details_page_name, string $whats_on_section, string $event_list_layout, string $date_format, string $event_name_ignore_string, int $cache_duration_seconds, array $custom_filter_list, bool $debug_log_enabled, bool $offer_filter_enabled, string $license_key)
	{
		$this->spektrix_url = $spektrix_url;
		$this->event_list_page_slug = $event_list_page_slug;
		$this->event_details_page_slug = $event_details_page_slug;
		$this->basket_page_slug = $basket_page_slug;
		$this->account_page_slug = $account_page_slug;
		$this->checkout_page_slug = $checkout_page_slug;
		$this->event_details_page_name = $event_details_page_name;
		$this->whats_on_section = $whats_on_section;
		$this->event_list_layout = $event_list_layout;
		$this->date_format = $date_format;
		$this->event_name_ignore_string = $event_name_ignore_string;
		$this->cache_duration_seconds = $cache_duration_seconds;
		$this->custom_filter_list = $custom_filter_list;
		$this->debug_log_enabled = $debug_log_enabled;
		$this->offer_filter_enabled = $offer_filter_enabled;
		$this->license_key = $license_key;
	}

	/**
	 * Updates the settings based on form POST values. Returns updated settings.
	 */
	public static function update_settings_from_form_post(): Box_Office_WP_Settings
	{

		if (isset($_POST['box_office_wp_nonce']) || wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['box_office_wp_nonce'])), 'box_office_wp_nonce'))

			$box_office_wp_settings = self::load_settings();

		if (isset($_POST[self::$update_settings_form_action])) {
			if (isset($_POST['spektrix_url']))
				$box_office_wp_settings->spektrix_url = sanitize_url(wp_unslash($_POST['spektrix_url']));

			if (isset($_POST['event_list_page_slug']))
				$box_office_wp_settings->event_list_page_slug = sanitize_text_field(wp_unslash($_POST['event_list_page_slug']));

			if (isset($_POST['event_details_page_slug']))
				$box_office_wp_settings->event_details_page_slug = sanitize_text_field(wp_unslash($_POST['event_details_page_slug']));

			if (isset($_POST['basket_page_slug']))
				$box_office_wp_settings->basket_page_slug = sanitize_text_field(wp_unslash($_POST['basket_page_slug']));

			if (isset($_POST['checkout_page_slug']))
				$box_office_wp_settings->checkout_page_slug = sanitize_text_field(wp_unslash($_POST['checkout_page_slug']));

			if (isset($_POST['account_page_slug']))
				$box_office_wp_settings->account_page_slug = sanitize_text_field(wp_unslash($_POST['account_page_slug']));

			if (isset($_POST['event_details_page_name']))
				$box_office_wp_settings->event_details_page_name = sanitize_text_field(wp_unslash($_POST['event_details_page_name']));

			if (isset($_POST['whats_on_section']))
				$box_office_wp_settings->whats_on_section = sanitize_text_field(wp_unslash($_POST['whats_on_section']));

			if (isset($_POST['event_list_layout']))
				$box_office_wp_settings->event_list_layout = sanitize_text_field(wp_unslash($_POST['event_list_layout']));

			if (isset($_POST['date_format']))
				$box_office_wp_settings->date_format = sanitize_text_field(wp_unslash($_POST['date_format']));

			if (isset($_POST['event_name_ignore_string']))
				$box_office_wp_settings->event_name_ignore_string = sanitize_text_field(wp_unslash($_POST['event_name_ignore_string']));

			if (isset($_POST['cache_duration_minutes']))
				$box_office_wp_settings->cache_duration_seconds = sanitize_text_field(wp_unslash($_POST['cache_duration_minutes']) * 60);

			if (isset($_POST['custom_filter_list']))
				$box_office_wp_settings->custom_filter_list = self::convert_raw_custom_filters_string_to_filter_array(sanitize_text_field(wp_unslash($_POST['custom_filter_list'])));

			if (isset($_POST['debug_log_enabled']))
				$box_office_wp_settings->debug_log_enabled = sanitize_text_field(wp_unslash($_POST['debug_log_enabled']) == 'true');

			if (isset($_POST['offer_filter_enabled']))
				$box_office_wp_settings->offer_filter_enabled = sanitize_text_field(wp_unslash($_POST['offer_filter_enabled']) == 'true');
			if (isset($_POST['license_key']))
				$box_office_wp_settings->license_key = sanitize_text_field(wp_unslash($_POST['license_key']));

			// We enforce a minimum cache duration, regardless of what the user has supplied.

			if ($box_office_wp_settings->cache_duration_seconds < self::$minimum_cache_duration_seconds)
				$box_office_wp_settings->cache_duration_seconds = self::$minimum_cache_duration_seconds;

			self::save_settings($box_office_wp_settings);
		}

		return $box_office_wp_settings;
	}

	/**
	 * The custom filters setting is managed as a string, in the form:
	 * "<Spektrix attribute name>;<filter display name>". One filter per line. For example, "Type;Event type".
	 * This function converts such a string to an array of filter objects.
	 */
	public static function convert_raw_custom_filters_string_to_filter_array(string $raw_filter_string): array
	{
		$custom_filter_list = [];

		if (strlen($raw_filter_string) > 0) {
			$lines = preg_split("/\r\n|\n|\r/", $raw_filter_string);

			foreach ($lines as $line) {
				if (strlen($line) > 0) {
					$filter = explode(self::$custom_filter_separator, $line, 2);

					$custom_filter_list[] = new Box_Office_WP_Custom_Filter($filter[0], $filter[1]);
				}
			}
		}

		return $custom_filter_list;
	}

	/**
	 * Converts an array of custom filters back into a string to allow it to be edited on the settings page
	 */
	public static function convert_custom_filters_array_to_string(?array $custom_filter_list): string
	{
		$custom_filters_as_string = '';

		if (!is_null($custom_filter_list) && is_array($custom_filter_list)) {
			foreach ($custom_filter_list as $custom_filter) {
				$custom_filters_as_string .= $custom_filter->spektrix_attribute_name . self::$custom_filter_separator . $custom_filter->display_name;
				$custom_filters_as_string .= "\r\n";
			}
		}

		return $custom_filters_as_string;
	}

	/**
	 * Use this to check if we have plugin settings in the DB
	 */
	public static function settings_exist(): bool
	{
		return get_option(self::$settings_key) != false;
	}

	/**
	 * Attempt to load settings from the DB. If unable, return default settings.
	 */
	public static function load_settings(): Box_Office_WP_Settings
	{
		// IMPORTANT!!! Must never make a call to Box_Office_WP::log() from within this function - there's a call 
		// within Box_Office_WP::log() to load_settings(), so that would result in an infinite loop. For this reason, 
		// be careful not to call ANY functions that might be dependent upon this Settings class.

		$box_office_wp_settings = get_option(self::$settings_key);

		// Default to this WordPress instance's date format

		$default_date_format = get_option('date_format') . self::$default_time_format;

		// Default writing to the log file as "true" for localhost, "false" otherwise.

		$debug_log_enabled = str_contains(strtolower(get_site_url()), 'localhost');

		$default_custom_filter = [];

		// If we don't already have settings saved in the DB, initialise default settings, and save to DB.

		if ($box_office_wp_settings === false) {
			$box_office_wp_settings = new Box_Office_WP_Settings(
				self::$default_api_url,
				self::$default_event_list_page_slug,
				self::$default_event_details_page_slug,
				self::$default_basket_page_slug,
				self::$default_account_page_slug,
				self::$default_checkout_page_slug,
				self::$default_event_details_page_name,
				self::$default_whats_on_section,
				self::$default_event_list_layout,
				$default_date_format,
				self::$default_event_name_ignore_string,
				self::$default_cache_duration_seconds,
				$default_custom_filter,
				$debug_log_enabled,
				self::$default_offer_filter_enabled,
				self::$default_license_key
			);

			self::save_settings($box_office_wp_settings);
		}

		// There's no need to do the following in v1 of this plugin, but leaving here for references - this is how to 
		// handle new properties being added to Settings class as part of a plugin upgrade.

		if (!isset($box_office_wp_settings->license_key)) {
			$box_office_wp_settings->license_key = self::$default_license_key;

			self::save_settings($box_office_wp_settings);
		}

		return $box_office_wp_settings;
	}

	/**
	 * Save settings in DB
	 */
	public static function save_settings($box_office_wp_settings): void
	{
		update_option(self::$settings_key, $box_office_wp_settings);
	}

	/**
	 * Delete settings from DB (a new set of default settings will subsequently get created and saved)
	 */
	public static function delete_settings(): void
	{
		Box_Office_WP::log('Deleting settings from DB');

		delete_option(self::$settings_key);
	}
}
