<?php
/**
 * Event_List_Logic class
 * 
 * Logic relating to the configuration and display of the Event List shortcode, including presentation markup.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Box_Office_WP_Event_List_Logic
{
	public static string $with_link_attribute = 'link';
	public static string $name_placeholder = '[name]';
	public static string $description_placeholder = '[description]';
	public static string $html_description_placeholder = '[html_description]';
	public static string $duration_placeholder = '[duration]';
	public static string $image_placeholder = '[image]';
	public static string $is_on_sale_placeholder = '[is_on_sale]';
	public static string $instance_dates_placeholder = '[instance_dates]';
	public static string $thumbnail_placeholder = '[thumbnail]';
	public static string $first_instance_date_time_placeholder = '[first_instance_date_time]';
	public static string $last_instance_date_time_placeholder = '[last_instance_date_time]';
	public static string $more_details_placeholder = '[more_details]';
	public static string $book_now_placeholder = '[book_now]';

	/**
	 * Converts supplied array of Event objects to HTML string
	 */
	public static function event_list_to_html($event_list, int $max_description_words_to_display) : string
	{
		$settings = Box_Office_WP_Settings::load_settings();

		$list_has_items = is_array($event_list) && count($event_list) > 0;

		$list_html = '';

		if($list_has_items)
		{
			$list_html .= '<ul class="box-office-wp-event-list">';

			foreach($event_list as $event)
			{
				$event_details_page_url = "/$settings->whats_on_section/$event->eventSlug";

				$event->description = wp_trim_words($event->description, $max_description_words_to_display);

				$item_html = '';
				$item_html .= '<li class="box-office-wp-event">';

				// Default the item HTML to the layout template as defined in settings. This can be a combination of 
				// HTML and placeholder tags.

				$item_html .= $settings->event_list_layout;

				// Replace each of the placeholder tags

				$item_html = self::replace_name_placeholders($item_html, $event, $event_details_page_url);
				$item_html = self::replace_description_placeholders($item_html, $event, $event_details_page_url);
				$item_html = self::replace_html_description_placeholders($item_html, $event, $event_details_page_url);
				$item_html = self::replace_duration_placeholders($item_html, $event, $event_details_page_url);
				$item_html = self::replace_image_placeholders($item_html, $event, $event_details_page_url);
				$item_html = self::replace_is_on_sale_placeholders($item_html, $event, $event_details_page_url);
				$item_html = self::replace_instance_dates_placeholders($item_html, $event, $event_details_page_url);
				$item_html = self::replace_thumbnail_placeholders($item_html, $event, $event_details_page_url);
				$item_html = self::replace_first_instance_date_time_placeholders($item_html, $event, $event_details_page_url);
				$item_html = self::replace_last_instance_date_time_placeholders($item_html, $event, $event_details_page_url);
				$item_html = self::replace_more_details_placeholders($item_html, $event, $event_details_page_url);
				$item_html = self::replace_book_now_placeholders($item_html, $event, $event_details_page_url);

				$item_html .= '</li>';

				$list_html .= $item_html;
			}
			
			$list_html .= '</ul>';
		}
		else
			$list_html = "<div class='box-office-wp-no-results'>No results</div>";

		return $list_html;
	}

	public static function replace_name_placeholders(string $text, $event, string $event_details_url) : string
	{
		$replacement_markup = "<h1 class='box-office-wp-event-name'>$event->name</h1>";
		$replacement_with_link_markup = "<h1 class='box-office-wp-event-name'><a href='$event_details_url'>$event->name</a></h1>";

		return self::replace_placeholders($text, self::$name_placeholder, $replacement_markup, $replacement_with_link_markup);
	}

	public static function replace_description_placeholders(string $text, $event, string $event_details_url) : string
	{
		$replacement_markup = "<div class='box-office-wp-event-description'>$event->description</div>";
		$replacement_with_link_markup = "<div class='box-office-wp-event-description'><a href='$event_details_url'>$event->description</a></div>";

		return self::replace_placeholders($text, self::$description_placeholder, $replacement_markup, $replacement_with_link_markup);
	}

	public static function replace_html_description_placeholders(string $text, $event, string $event_details_url) : string
	{
		$replacement_markup = "<div class='box-office-wp-event-html-description'>$event->htmlDescription</div>";
		$replacement_with_link_markup = "<div class='box-office-wp-event-html-description'><a href='$event_details_url'>$event->htmlDescription</a></div>";
	
		return self::replace_placeholders($text, self::$html_description_placeholder, $replacement_markup, $replacement_with_link_markup);
	}
	
	public static function replace_duration_placeholders(string $text, $event, string $event_details_url) : string
	{
		$replacement_markup = "<div class='box-office-wp-event-duration'>$event->duration</div>";
		$replacement_with_link_markup = "<div class='box-office-wp-event-duration'><a href='$event_details_url'>$event->duration</a></div>";
	
		return self::replace_placeholders($text, self::$duration_placeholder, $replacement_markup, $replacement_with_link_markup);
	}
	
	public static function replace_image_placeholders(string $text, $event, string $event_details_url) : string
	{
		$replacement_markup = "<div class='box-office-wp-event-image'><img src='$event->imageUrl' alt='$event->name' loading='lazy' /></div>";
		$replacement_with_link_markup = "<div class='box-office-wp-event-image'><a href='$event_details_url'><img src='$event->imageUrl' alt='$event->name' loading='lazy' /></a></div>";
	
		return self::replace_placeholders($text, self::$image_placeholder, $replacement_markup, $replacement_with_link_markup);
	}
	
	public static function replace_is_on_sale_placeholders(string $text, $event, string $event_details_url) : string
	{
		$replacement_markup = "<div class='box-office-wp-event-is-on-sale'>$event->isOnSale</div>";
		$replacement_with_link_markup = "<div class='box-office-wp-event-is-on-sale'><a href='$event_details_url'>$event->isOnSale</a></div>";
	
		return self::replace_placeholders($text, self::$is_on_sale_placeholder, $replacement_markup, $replacement_with_link_markup);
	}
	
	public static function replace_instance_dates_placeholders(string $text, $event, string $event_details_url) : string
	{
		$replacement_markup = "<div class='box-office-wp-event-instance-dates'>$event->instanceDates</div>";
		$replacement_with_link_markup = "<div class='box-office-wp-event-instance-dates'><a href='$event_details_url'>$event->instanceDates</a></div>";
	
		return self::replace_placeholders($text, self::$instance_dates_placeholder, $replacement_markup, $replacement_with_link_markup);
	}
	
	public static function replace_thumbnail_placeholders(string $text, $event, string $event_details_url) : string
	{
		$replacement_markup = "<div class='box-office-wp-event-thumbnail'><img src='$event->thumbnailUrl' alt='$event->name' loading='lazy' /></div>";
		$replacement_with_link_markup = "<div class='box-office-wp-event-thumbnail'><a href='$event_details_url'><img src='$event->thumbnailUrl' alt='$event->name' loading='lazy' /></a></div>";
	
		return self::replace_placeholders($text, self::$thumbnail_placeholder, $replacement_markup, $replacement_with_link_markup);
	}
	
	public static function replace_first_instance_date_time_placeholders(string $text, $event, string $event_details_url) : string
	{
		$replacement_markup = "<div class='box-office-wp-event-first-instance-date-time'>$event->firstInstanceDateTimeFormatted</div>";
		$replacement_with_link_markup = "<div class='box-office-wp-event-first-instance-date-time'><a href='$event_details_url'>$event->firstInstanceDateTimeFormatted</a></div>";
	
		return self::replace_placeholders($text, self::$first_instance_date_time_placeholder, $replacement_markup, $replacement_with_link_markup);
	}
	
	public static function replace_last_instance_date_time_placeholders(string $text, $event, string $event_details_url) : string
	{
		$replacement_markup = "<div class='box-office-wp-event-last-instance-date-time'>$event->lastInstanceDateTimeFormatted</div>";
		$replacement_with_link_markup = "<div class='box-office-wp-event-last-instance-date-time'><a href='$event_details_url'>$event->lastInstanceDateTimeFormatted</a></div>";
	
		return self::replace_placeholders($text, self::$last_instance_date_time_placeholder, $replacement_markup, $replacement_with_link_markup);
	}
	
	public static function replace_more_details_placeholders(string $text, $event, string $event_details_url) : string
	{
		// This placeholder always links to the event details page, so use same markup for both.

		$replacement_markup = "<div class='box-office-wp-event-more-details'><a href='$event_details_url'>More details</a></div>";
		$replacement_with_link_markup = $replacement_markup;
	
		return self::replace_placeholders($text, self::$more_details_placeholder, $replacement_markup, $replacement_with_link_markup);
	}
	
	public static function replace_book_now_placeholders(string $text, $event, string $event_details_url) : string
	{
		// This placeholder always links to the event details page, so use same markup for both.

		$book_now_anchor_id = Box_Office_WP_Constants::$book_now_anchor_id;

		$replacement_markup = "<div class='box-office-wp-event-more-details'><a href='$event_details_url#$book_now_anchor_id'>Book now</a></div>";
		$replacement_with_link_markup = $replacement_markup;
	
		return self::replace_placeholders($text, self::$book_now_placeholder, $replacement_markup, $replacement_with_link_markup);
	}
	
	public static function replace_placeholders(string $text, string $placeholder, string $replacement_markup, string $replacement_with_link_markup) : string
	{
		$with_link_placeholder = self::build_with_link_placeholder($placeholder);

		$replaced_text = str_replace($placeholder, $replacement_markup, $text);
		$replaced_text = str_replace($with_link_placeholder, $replacement_with_link_markup, $replaced_text);

		return $replaced_text;
	}
	
	public static function build_with_link_placeholder(string $placeholder) : string
	{
		$with_link_attribute = self::$with_link_attribute;

		return str_replace(']', " $with_link_attribute]", $placeholder);
	}
}