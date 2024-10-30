<?php

/**
 * Fired during plugin activation
 *
 * @link       https://line.industries
 * @package    Box_Office_WP
 * @subpackage Box_Office_WP/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @package    Box_Office_WP
 * @subpackage Box_Office_WP/includes
 * @author     Line Industries <support@lineindustries.com>
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Box_Office_WP_Activator
{
	/**
	 * Fired during plugin activation
	 */
	public static function activate()
	{
		$settings_were_already_in_db = Box_Office_WP_Settings::settings_exist();

		$existing_settings = Box_Office_WP_Settings::load_settings();

		Box_Office_WP::log('Activate. Mode: ' . (Box_Office_WP::in_pro_mode() ? 'Pro' : 'Regular'));
		Box_Office_WP::log('Settings already in DB: ' . ($settings_were_already_in_db ? 'yes' : 'no'));

		// If settings didn't already exist in the database, this is likely the first ever activation of this plugin, 
		// so let's create the plugin pages. If settings already exist then it's likely the plugin has been 
		// deactivated/reactivated, so the pages should already exist.

		if(!$settings_were_already_in_db)
			self::create_plugin_pages($existing_settings);
		
		// Add the "what's on" rewrite rule and refresh permalinks.

		Box_Office_WP_Public::add_whats_on_rewrite_rule();
		flush_rewrite_rules(false);

		if(Box_Office_WP::in_pro_mode())
		{
			// Flush the cache for plugin updates, so that the update banner will be displayed if necessary.
			Box_Office_WP_Update::get_update_info(true);
		}

	}

	public static function create_plugin_pages(Box_Office_WP_Settings $settings)
	{
		Box_Office_WP::log('Creating plugin pages', true);

		// First things first, let's make sure all our page slugs are unique, i.e. avoid clashes with any existing 
		// pages.

		$settings->event_list_page_slug = self::get_unique_page_slug($settings->event_list_page_slug);
		$settings->event_details_page_slug = self::get_unique_page_slug($settings->event_details_page_slug);
		$settings->basket_page_slug = self::get_unique_page_slug($settings->basket_page_slug);
		$settings->account_page_slug = self::get_unique_page_slug($settings->account_page_slug);
		$settings->checkout_page_slug = self::get_unique_page_slug($settings->checkout_page_slug);

		Box_Office_WP_Settings::save_settings($settings);

		// Now create the pages, which will contain default content/blocks.

		self::create_page(Box_Office_WP_Constants::$default_event_list_page_title, $settings->event_list_page_slug, self::build_event_list_page_content());
		self::create_page(Box_Office_WP_Constants::$default_basket_page_title, $settings->basket_page_slug, self::build_basket_page_content());
		self::create_page(Box_Office_WP_Constants::$default_account_page_title, $settings->account_page_slug, self::build_account_page_content());
		self::create_page(Box_Office_WP_Constants::$default_checkout_page_title, $settings->checkout_page_slug, self::build_checkout_page_content());

		// We populate the event details page with different content depending on whether we're in Free or Pro mode

		$event_details_page_content = Box_Office_WP::in_pro_mode() ? self::build_pro_event_details_page_content() : self::build_free_event_details_page_content();

		self::create_page(Box_Office_WP_Constants::$default_event_details_page_title, $settings->event_details_page_slug, $event_details_page_content);
	}

	public static function build_event_list_page_content()
	{
		$limit_attribute_key = Box_Office_WP_Shortcodes::$limit_attribute;
		$limit_attribute_value = Box_Office_WP_Constants::$default_event_list_limit;

		$content = '';

		$content .= Box_Office_WP_Block_Logic::generate_block_markup(Box_Office_WP_Block_Logic::$event_filter_block_name);
		$content .= Box_Office_WP_Block_Logic::generate_block_markup(Box_Office_WP_Block_Logic::$event_list_alpha_sort_block_name);
		$content .= Box_Office_WP_Block_Logic::generate_block_markup(Box_Office_WP_Block_Logic::$event_list_block_name, '<div class="wp-block-box-office-wp-box-office-wp-event-list">', " $limit_attribute_key=\"$limit_attribute_value\"", '</div>');
		
		return $content;
	}

	public static function build_account_page_content()
	{
		return Box_Office_WP_Block_Logic::generate_block_markup(Box_Office_WP_Block_Logic::$account_iframe_block_name);
	}

	public static function build_basket_page_content()
	{
		return Box_Office_WP_Block_Logic::generate_block_markup(Box_Office_WP_Block_Logic::$basket_iframe_block_name);
	}

	public static function build_checkout_page_content()
	{
		return Box_Office_WP_Block_Logic::generate_block_markup(Box_Office_WP_Block_Logic::$checkout_iframe_block_name);
	}

	public static function build_free_event_details_page_content()
	{
		return Box_Office_WP_Block_Logic::generate_block_markup(Box_Office_WP_Block_Logic::$event_details_iframe_block_name);
	}

	public static function build_pro_event_details_page_content()
	{
		return Box_Office_WP_Pro_Activator::build_pro_event_details_page_content();
	}

	public static function create_page(string $title, string $name, string $content)
	{
		$new_page = array('post_title' => $title,
		'post_name' => $name,
		'post_content' => $content,
		'post_status' => 'publish',
		'post_type' => 'page'
		);

		$post_id = wp_insert_post($new_page);
	}

	/**
	 * Checks to see if a page already exists with the supplied page slug, and if it does, keeps adding "-2" until a 
	 * unique page slug is found. Returns the unique page slug (which might be the original passed in $page_slug_to_try
	 *  if it was already unique.).
	 */
	public static function get_unique_page_slug(string $page_slug_to_try)
	{
		$unique_page_slug = $page_slug_to_try;

		while(!is_null(get_page_by_path($unique_page_slug)))
		{
			Box_Office_WP::log('Existing page found: ' . $unique_page_slug . '. Adding ' . Box_Office_WP_Constants::$page_slug_avoid_clash_append);

			$unique_page_slug .= Box_Office_WP_Constants::$page_slug_avoid_clash_append;
		}

		return $unique_page_slug;
	}
}
