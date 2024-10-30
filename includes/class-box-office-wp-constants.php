<?php
/**
 * Constants class
 * 
 * Shared constants used across the site. Can be called statically, without a class instance.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Box_Office_WP_Constants
{
	// Page titles

	public static string $default_event_list_page_title = 'What\'s On';
	public static string $default_event_details_page_title = 'Event Details';
	public static string $default_basket_page_title = 'Basket';
	public static string $default_account_page_title = 'My Account';
	public static string $default_checkout_page_title = 'Checkout';

	// Query string

	public static string $event_id_query_string_key = 'eventid';
	public static string $instance_id_query_string_key = 'instanceid';
	public static string $event_slug_query_string_key = 'eventslug';

	// Control IDs

	public static string $custom_event_filter_control_id_prefix = "box-office-wp-event-filter-custom-";
	public static string $event_filter_from_date_control_id = 'box-office-wp-event-filter-from-date';
	public static string $event_filter_to_date_control_id = 'box-office-wp-event-filter-to-date';
	public static string $event_filter_offers_control_id = 'box-office-wp-event-filter-offers';
	public static string $event_filter_event_name_control_id = 'box-office-wp-event-filter-event-name';
	public static string $alpha_sort_control_id = 'box-office-wp-alpha-sort';

	// Sorting

	public static string $ascending = 'asc';
	public static string $descending = 'desc';
	public static string $no_sort = 'no_sort';

	// Blocks

	public static string $block_custom_category_slug = 'box-office-wp-plugin';
	public static string $block_custom_category_display_name = 'BOXOFFICE WP PLUGIN';

	// Misc

	public static string $free_plugin_name = 'BoxOffice WP';
	public static string $pro_plugin_name = 'BoxOffice WP Pro';
	public static string $upgrade_message_markup = '<p>Upgrade to our Pro version<br><br>Everything in the free plugin plus:<br>More than 10 additional dedicated Gutenberg blocks<br>Granular control over the event list<br>Apply custom filters to the event list<br>Choose to display a “filter by offers” drop down<br>Design the layout of your event pages<br> <a href="https://boxofficewp.com">Upgrade now</a><br></p>';
	public static string $event_name_filter_placeholder_text = 'Search by event name...';
	public static string $please_select_value_prefix = 'Please select ';
	public static string $book_now_anchor_id = 'book-now';
	public static string $pro_directory_name = 'pro';
	public static string $page_slug_avoid_clash_append = '-2';
	public static int $event_name_filter_minlength = 3;
	public static int $default_event_list_limit = 999;
	public static int $default_event_list_max_description_words_to_display = 999;

}