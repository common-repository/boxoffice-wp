<?php

/**
 * API class.
 * 
 * Concerned with all logic relating to communication with the Spektrix API.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Box_Office_WP_Spektrix_Api
{
	public static $event_list_date_filter_format = 'Y-m-d';
	private $event_list_cache_key = 'box_office_wp_event_list';
	private $offer_list_cache_key = 'box_office_wp_offer_list';
	private $event_list_last_fetched_cache_key = 'box_office_wp_event_list_last_fetched';
	private $event_instance_list_cache_key_prefix = 'box_office_wp_event_instance_list_';
	private $custom_event_filter_cache_key_prefix = 'box_office_wp_custom_event_filter_';
	private $event_list_api_path = '/api/v3/events';
	private $offers_api_path = '/api/v3/offers/';
	private $instances_api_append = 'instances';
	private $utc_date_format_string = 'Y-m-d\TH:i:s\Z';
	private $spektrix_custom_attribute_prefix = 'attribute_';
	private $fetch_offers_batch_size = 1;
	private $one_day_as_seconds = 86400;

	public function remove_event_list_from_cache()
	{
		delete_transient($this->event_list_cache_key);
	}

	public function remove_custom_event_filters_from_cache()
	{
		foreach(Box_Office_WP_Settings::load_settings()->custom_filter_list as $custom_filter)
		{
			$cache_key = $this->build_custom_event_filter_cache_key($custom_filter->spektrix_attribute_name);

			delete_transient($cache_key);
		}
	}

	public function get_event_details_by_event_slug(string $event_slug)
	{
		foreach($this->get_event_list(Box_Office_WP_Constants::$default_event_list_limit) as $event)
			if($event->eventSlug == $event_slug)
				return $event;

		return null;
	}

	public function get_event_details_by_event_id(string $event_id)
	{
		foreach($this->get_event_list(Box_Office_WP_Constants::$default_event_list_limit) as $event)
			if($event->eventID == $event_id)
				return $event;

		return null;
	}

	/**
	 * Fetches the Spektrix Event list and converts it to an HTML string
	 */
	public function get_event_list_as_html(int $limit, int $max_description_words_to_display, array $event_id_list = null, ?string $date_from_filter = null, ?string $date_to_filter = null, ?string $offer_filter = null, array $custom_filters_selected_values = null, ?string $event_name_filter = null, ?string $alpha_sort = null) : string
	{
		return Box_Office_WP_Event_List_Logic::event_list_to_html($this->get_event_list($limit, $event_id_list, $date_from_filter, $date_to_filter, $offer_filter, $custom_filters_selected_values, $event_name_filter, $alpha_sort), $max_description_words_to_display);
	}

	public function get_event_list_last_fetched_from_api()
	{
		return get_transient($this->event_list_last_fetched_cache_key);
	}

	/**
	 * Returns a list of Spektrix Event objects. Will attempt to cache the API call to avoid repeated frequent requests.
	 * 
	 * If $event_id_list argument is provided, and not null/empty, all other filters will be ignored, and that exact 
	 * list of events will be returned, in the order specified.
	 * 
	 * Each Event object has the following properties:
	 * - description
	 * - htmlDescription
	 * - duration
	 * - imageUrl
	 * - isOnSale
	 * - name
	 * - eventSlug (this is a WordPress style slug that we generate from the name property)
	 * - instanceDates
	 * - thumbnailUrl
	 * - webEventId
	 * - id (this is the ID that comes from Spektrix, e.g. 175385ARRVCCJJJKTLLTVGPHBGLPGMLDK)
	 * - eventID (this is the numeric portion of the ID property, as used in iframes, e.g. 175385)
	 * - firstInstanceDateTime
	 * - firstInstanceDateTimeFormatted (this is the Spektrix supplied date, formatted as per plugin settings)
	 * - lastInstanceDateTime
	 * - lastInstanceDateTimeFormatted (this is the Spektrix supplied date, formatted as per plugin settings)
	 * - various custom attributes, prefixed "attribute_"
	 * - offers (this isn't part of the Spektrix event, it's populated by an additional API call)
	 */
	public function get_event_list(int $limit, array $event_id_list = null, ?string $date_from_filter = null, ?string $date_to_filter = null, ?string $offer_filter = null, array $custom_filters_selected_values = null, ?string $event_name_filter = null, ?string $alpha_sort = null)
	{
		// Try and fetch list from cache before resorting to API call

		$event_list = get_transient($this->event_list_cache_key);
		$event_list_last_fetched_from_api = $this->get_event_list_last_fetched_from_api();

		// If we don't have a cached event list, or it's stale, fetch a new one from the API.

		if(false === $event_list || $event_list_last_fetched_from_api === false)
			$event_list = $this->update_event_list_from_api();

		// Check the event list for any stale offer data, and update that if needed (we do this in small batches, since
		// it requires an API call per event).

		$event_list = $this->update_offers_if_needed($event_list, $this->fetch_offers_batch_size);
			
		// Apply filters before returning. We don't cache filtered lists, since there are so many permutations.
		
		// If a static "event id list" has been provided, we always return those events, regardless of other filters. 
		// If not, we filter based on dates, etc.
		
		$event_id_list_set = is_array($event_id_list) && count($event_id_list) > 0;

		if($event_id_list_set)
			$filtered_list = $this->filter_event_list_by_id_list($event_list, $event_id_list);
		else
			$filtered_list = $this->filter_event_list($event_list, $date_from_filter, $date_to_filter, $offer_filter, $custom_filters_selected_values, $event_name_filter);

		// Likewise, applying limiting after filters have been applied.

		$filtered_list = array_slice($filtered_list, 0, $limit);

		// Apply the alpha-sort, if specified. By default the list will already be sorted by date asc., but we'll do 
		// that again here to be extra sure.

		if($alpha_sort == Box_Office_WP_Constants::$ascending)
			usort($filtered_list, fn($a, $b) => strcmp($a->name, $b->name));
		else if($alpha_sort == Box_Office_WP_Constants::$descending)
			usort($filtered_list, fn($a, $b) => strcmp($b->name, $a->name));
		else
			usort($filtered_list, fn($a, $b) => strcmp($a->firstInstanceDateTime, $b->firstInstanceDateTime));

		return $filtered_list;
	}

	/**
	 * Check to see if we need to update the "offers" property for any events. This requires an extra API call per 
	 * event, so we only update a few at a time (and only if the offer filter is enabled in the plugin settings). It 
	 * won't be long before all event offers have been fetched and cached, at which point this function will be a very 
	 * quick/cheap loop that does nothing.
	 */
	public function update_offers_if_needed(array $event_list, int $batch_size) : array
	{
		if(Box_Office_WP_Settings::load_settings()->offer_filter_enabled)
		{
			$events_updated = 0;

			foreach($event_list as $event)
			{
				if($events_updated < $batch_size && $event->need_to_refresh_offers)
				{
					$offers_updated_success = false;
					$offers = $this->get_offers_for_event($event->name, $offers_updated_success);
	
					if($offers_updated_success)
					{
						$event->offers = $offers;
						$event->need_to_refresh_offers = false;
		
						$events_updated++;
	
						// Cache the updated list, and clear the cached offer list (which gets used for filtering).
	
						$this->cache_event_list($event_list);
						delete_transient($this->offer_list_cache_key);
					}
				}
			}
		}

		return $event_list;
	}

	/**
	 * Makes a call to the Spektrix API to fetch a list of Spektrix Event objects. If possible, updates list already 
	 * stored in cache, otherwise fetches and caches a new one.
	 */
	private function update_event_list_from_api()
	{
		$event_list = [];
		$cached_event_list = get_transient($this->event_list_cache_key);
		$raw_event_list_from_api = $this->get_raw_event_list_from_api();

		if(false === $cached_event_list)
		{
			Box_Office_WP::log('No event list currently cached, cache a new one from API.');

			$event_list = $this->cache_new_event_list_from_api($raw_event_list_from_api);
		}
		else
		{
			Box_Office_WP::log('Event list already cached. Update from API.');

			$event_list = $this->update_cached_event_list_from_api($cached_event_list, $raw_event_list_from_api);
		}

		return $event_list;
	}

	/**
	 * Hits the Spektrix API for an event list
	 */
	private function get_raw_event_list_from_api() : array
	{
		$settings = Box_Office_WP_Settings::load_settings();

		// Only fetch events from today onwards

		$today_query_string_filter = gmdate(self::$event_list_date_filter_format);

		$spektrix_event_list_api_url = $this->combine_api_urls($settings->spektrix_url, $this->event_list_api_path);
		$spektrix_event_list_api_url .= "?instanceStart_from=$today_query_string_filter";

		$response = wp_remote_get($spektrix_event_list_api_url);

		$event_list = [];

		if(!is_wp_error($response) && is_array($response))
		{
			$body = wp_remote_retrieve_body($response);

			$event_list = json_decode($body);

			if(is_null($event_list))
			{
				Box_Office_WP::log('Failed to get valid event list from: ' . $spektrix_event_list_api_url);

				$event_list = [];
			}
		}
		else
			Box_Office_WP::log('Failed to connect to: ' . $spektrix_event_list_api_url);

		// Filter out any events that are to be ignored due to "ignore event name string" setting

		$filtered_event_list = [];

		if(strlen($settings->event_name_ignore_string) > 0)
		{
			foreach($event_list as $event)
				if(!str_contains(strtolower($event->name), strtolower($settings->event_name_ignore_string)))
					$filtered_event_list[] = $event;
		}
		else
			$filtered_event_list = $event_list;

		return $filtered_event_list;
	}

	public function spektrix_url_is_good(string $spektrix_url) : bool
	{
		return count($this->get_raw_event_list_from_api()) > 0;
	}

	/**
	 * Enriches the supplied raw Spektrix event list, fetches all the associated offer data from the Spektrix API, then
	 * caches the new list.
	 */
	private function cache_new_event_list_from_api(array $raw_event_list_from_api) : array
	{
		// Calling update_cached_event_list_from_api() with an empty cached event list essentially caches a brand new 
		// list with no offer data.

		$raw_event_list_from_api = $this->update_cached_event_list_from_api([], $raw_event_list_from_api);

		return $raw_event_list_from_api;
	}

	/**
	 * Enriches the supplied raw Spektrix event list, uses existing cached offer data if possible, then caches the 
	 * updated list.
	 */
	private function update_cached_event_list_from_api(array $cached_event_list, array $raw_event_list_from_api) : array
	{
		foreach($raw_event_list_from_api as $event)
		{
			$this->enrich_raw_spektrix_event($event);

			// We've fetched a fresh event list from Spektrix, which won't contain any event offer data. Luckily we 
			// already have that cached, so we'll use the cached offer data for now, but mark it as "stale" so that it 
			// will get updated again soon.

			$event->offers = $this->get_event_offers($cached_event_list, $event->eventID);
			$event->need_to_refresh_offers = true;
		}

		$this->make_event_slugs_unique($raw_event_list_from_api);

		$this->cache_event_list($raw_event_list_from_api);

		return $raw_event_list_from_api;
	}

	/**
	 * Add some extra properties to the supplied Spektrix event that will be used elsewhere by the plugin.
	 */
	private function enrich_raw_spektrix_event($raw_spektrix_event)
	{
		// Calculate the eventID and eventSlug properties (these are generated here, they aren't fetched from Spektrix).
		// We use Spektrix/JSON camelCase naming for consistency.

		$raw_spektrix_event->eventID = $this->get_numeric_start_of_string($raw_spektrix_event->id);
		$raw_spektrix_event->eventSlug = strtolower(sanitize_title($raw_spektrix_event->name));

		// Also, for convenience, store a nicely formatted date string for first and last instance.

		$raw_spektrix_event->firstInstanceDateTimeFormatted = $this->get_formatted_date($raw_spektrix_event->firstInstanceDateTime);
		$raw_spektrix_event->lastInstanceDateTimeFormatted = $this->get_formatted_date($raw_spektrix_event->lastInstanceDateTime);

		// Fetching "offers" for an event involves an additional API call per event, which we will do later.

		$raw_spektrix_event->offers = [];
		$raw_spektrix_event->need_to_refresh_offers = true;
	}

	/**
	 * Pass in an updated event list, with enriched data. We will have generated an eventSlug, which we use for 
	 * friendly URLs. Ensure these slugs are unique (some events have duplicate names). If any duplicate eventSlugs are
	 * encountered, these will have a "_2" appended.
	 */
	private function make_event_slugs_unique(array $event_list)
	{
		$event_slugs_are_unique = false;
		$event_slug_append = '-2';

		while(!$event_slugs_are_unique)
		{
			$duplicate_found = false;

			foreach($event_list as $outerEvent)
			{
				$event_id = $outerEvent->eventID;
				$event_slug = $outerEvent->eventSlug;

				foreach($event_list as $innerEvent)
				{
					if($innerEvent->eventSlug == $event_slug && $innerEvent->eventID != $event_id)
					{
						$duplicate_found = true;

						Box_Office_WP::log('Changing eventSlug: ' . $innerEvent->eventSlug . ' (' . $innerEvent->firstInstanceDateTime . ') to: ' . $innerEvent->eventSlug . $event_slug_append);

						$innerEvent->eventSlug .= $event_slug_append;
					}
				}
			}

			$event_slugs_are_unique = !$duplicate_found;
		}
	}

	private function cache_event_list(array $event_list)
	{
		// Sort event list by first instance date prior to caching

		usort($event_list, fn($a, $b) => strcmp($a->firstInstanceDateTime, $b->firstInstanceDateTime));

		// We cache the event list for 24 hours, regardless of the cache duration specified in the plugin settings. 
		// Once that lower duration has expired, logic elsewhere will get triggered that will update the cached event 
		// list, and re-cache it.

		$cache_duration = $this->one_day_as_seconds;

		set_transient($this->event_list_cache_key, $event_list, $cache_duration);

		set_transient($this->event_list_last_fetched_cache_key, gmdate($this->utc_date_format_string), Box_Office_WP_Settings::load_settings()->cache_duration_seconds);
	}
	
	/**
	 * Queries the Spektrix API for a list of all offers relating the supplied event, from today onwards. This query 
	 * isn't cached, so use sparingly.
	 * Function will return an empty array in the case of failure to connect to API, so check the value of the $success 
	 * argument before trusting the returned array.
	 */
	private function get_offers_for_event(string $event_name, bool &$success) : array
	{
		$success = false;

		Box_Office_WP::log('Calling API for offers for event: ' . $event_name);

		$settings = Box_Office_WP_Settings::load_settings();

		// Only fetch offers from today onwards

		$today_query_string_filter = gmdate(self::$event_list_date_filter_format);

		$spektrix_offers_list_api_url = $this->combine_api_urls($settings->spektrix_url, $this->offers_api_path);
		$spektrix_offers_list_api_url .= "?instanceStart_from=$today_query_string_filter";
		$spektrix_offers_list_api_url .= "&eventName=$event_name";

		$offer_list = [];
		$unique_offer_names = [];

		$response = wp_remote_get($spektrix_offers_list_api_url);

		if(!is_wp_error($response) && is_array($response))
		{
			$body = wp_remote_retrieve_body($response);

			$offer_list = json_decode($body);

			if(!is_null($offer_list))
			{
				foreach($offer_list as $offer)
				{
					if(is_null($offer->endDate) || $offer->endDate > $today_query_string_filter)
					{
						// Use the offer's HTML description, if available, otherwise fallback to using offer name.

						$offer_name = strlen($offer->htmlDescription) > 0 ? $offer->htmlDescription : $offer->name;

						if(!in_array($offer_name, $unique_offer_names))
							$unique_offer_names[] = $offer_name;
					}
				}

				$success = true;
			}
			else
				Box_Office_WP::log('Failed to get valid offer list from: ' . $spektrix_offers_list_api_url);
		}
		else
			Box_Office_WP::log('Failed to connect to: ' . $spektrix_offers_list_api_url);

		return $unique_offer_names;
	}

	/**
	 * Returns a list of Instances for a given Spektrix Event. Will attempt to cache the API call to avoid repeated frequent requests.
	 * Pass in the Spektrix ID of the Event - this is the long ID string, containing both numbers and letters.
	 * 
	 * Each Instance object has the following properties:
	 * - isOnSale
	 * - planID
	 * - priceList
	 * 		- id
	 * - id
	 * - event
	 * 		- id
	 * 	- start
	 *  - startFormatted (this is the Spektrix supplied date, formatted as per plugin settings)
	 * 	- startUtc
	 *  - startUtcFormatted (this is the Spektrix supplied date, formatted as per plugin settings)
	 * 	- startSellingAtWeb
	 * 	- startSellingAtWebUtc
	 * 	- stopSellingAtWeb
	 * 	- stopSellingAtWebUtc
	 * 	- webInstanceId
	 * 	- cancelled
	 * 	- hasBestAvailableOverlay
	 * 	- various custom attributes, prefixed "attribute_"
	 */
	public function get_event_instance_list_from_api(string $id)
	{
		$settings = Box_Office_WP_Settings::load_settings();

		$spektrix_event_instances_api_url = $this->combine_api_urls($settings->spektrix_url, $this->event_list_api_path);
		$spektrix_event_instances_api_url = $this->combine_api_urls($spektrix_event_instances_api_url, "/$id/" . $this->instances_api_append);

		// Try and fetch list from cache before resorting to API call

		$cache_key = $this->build_event_instance_list_cache_key($id);

		$instance_list = get_transient($cache_key);

		if(false === $instance_list)
		{
			Box_Office_WP::log("No cached event instance list for event $id, fetching from API");

			$fetch_from_api_success = false;
			$instance_list = [];

			$response = wp_remote_get($spektrix_event_instances_api_url);
	
			if(!is_wp_error($response) && is_array($response))
			{
				$body = wp_remote_retrieve_body($response);
	
				if(!str_contains($body, 'Resource Not Found'))
				{
					$instance_list = json_decode($body);

					if(!is_null($instance_list))
					{
						// Before caching the instance list, set the formatted date properties.
	
						foreach($instance_list as $instance)
						{
							$instance->startFormatted = $this->get_formatted_date($instance->start);
							$instance->startUtcFormatted = $this->get_formatted_date($instance->startUtc);
						}

						$fetch_from_api_success = true;
	
						set_transient($cache_key, $instance_list, $settings->cache_duration_seconds);
					}
					else
						Box_Office_WP::log('Failed to get valid event instance list from: ' . $spektrix_event_instances_api_url);
				}
				else
					Box_Office_WP::log('Failed to get valid event instance list (resource not found) from: ' . $spektrix_event_instances_api_url);
			}
			else
				Box_Office_WP::log('Failed to connect to: ' . $spektrix_event_instances_api_url);
		}
		else
			$fetch_from_api_success = true;
		
		if(!$fetch_from_api_success)
			$instance_list = [];

		// Filter out instances which should not be sold online. We don't cache this filtered list, since it's time-
		// sensitive. Besides, the filtering will have a neglibible performance effect compared to the API call above.

		$filtered_instance_list = [];
		$now_utc = gmdate($this->utc_date_format_string);

		foreach($instance_list as $instance)
			if($instance->isOnSale && $now_utc > $instance->startSellingAtWebUtc && $now_utc < $instance->stopSellingAtWebUtc)
				$filtered_instance_list[] = $instance;

		return $filtered_instance_list;
	}

	/**
	 * Loops through the (hopefully cached) event list, building a unique list of offers, in alphabetical order. This 
	 * can then be used for filters, etc. The list will be cached in the interests of performance.
	 */
	public function get_unique_offers_list() : array
	{
		$unique_offers_list = get_transient($this->offer_list_cache_key);

		if(false === $unique_offers_list)
		{
			Box_Office_WP::log("No cached offer list, re-building.");

			$unique_offers_list = [];
	
			foreach($this->get_event_list(Box_Office_WP_Constants::$default_event_list_limit) as $event)
				foreach($event->offers as $offer)
					if(!in_array($offer, $unique_offers_list))
						$unique_offers_list[] = $offer;
	
			sort($unique_offers_list);

			set_transient($this->offer_list_cache_key, $unique_offers_list, Box_Office_WP_Settings::load_settings()->cache_duration_seconds);
		}

		return $unique_offers_list;
	}

	/**
	 * Assuming the supplied custom attribute is present in the event list, returns the distinct list of values stored
	 * for that attribute. The list will be cached in the interests of performance.
	 */
	public function get_list_of_values_for_custom_attribute($custom_attribute_name)
	{
		$cache_key = $this->build_custom_event_filter_cache_key($custom_attribute_name);

		$values = get_transient($cache_key);

		if(false === $values)
		{
			Box_Office_WP::log("No cached value list for custom attribute '$custom_attribute_name', re-building.");

			$values = [];
	
			$property_name = $this->spektrix_custom_attribute_prefix . $custom_attribute_name;
	
			foreach($this->get_event_list(Box_Office_WP_Constants::$default_event_list_limit) as $event)
			{
				if(property_exists($event, $property_name))
				{
					$event_value = $event->$property_name;

					if(!in_array($event_value, $values))
						$values[] = $event_value;
				}
				else
					Box_Office_WP::log('Could not find custom Spektrix attribute: ' . $property_name);
			}
	
			sort($values);

			set_transient($cache_key, $values, Box_Office_WP_Settings::load_settings()->cache_duration_seconds);
		}

		return $values;
	}

	/**
	 * Combines two paths, removing any trailing slash from the first path.
	 */
	public function combine_api_urls(string $url1, string $url2) : string
	{
		// Remove trailing slash from $url1. This will have come from the user.

		if(!empty($url1) && substr($url1, -1) == '/') $url1 = substr($url1, 0, -1);
		if(!empty($url1) && substr($url1, -1) == '\\') $url1 = substr($url1, 0, -1);

		return $url1 . $url2;
	}

	/**
	 * Spektrix IDs are made up of numeric portion followed by text, e.g. 175385ARRVCCJJJKTLLTVGPHBGLPGMLDK. When
	 * used in iframes, we only need the numeric portion.
	 * Note that this function will preserve zeros at the start of input strings, if present.
	 */
	public function get_numeric_start_of_string(string $input) : string
	{
		return preg_replace('/[^0-9]/', '', $input);
	}

	public function get_formatted_date($date) : string
	{
		$original_date = new DateTime($date);

		return $original_date->format(Box_Office_WP_Settings::load_settings()->date_format);
	}
	
	/**
	 * Cleans string of all non-alphanumeric characters. Plus convert bools to strings.
	 */
	public function clean_custom_filter_value($value) : string
	{
		if(is_bool($value))
			$cleaned_value = $value ? 'Yes' : 'No';
		else
			$cleaned_value = preg_replace("/[^A-Za-z0-9]/", '', $value);

		return $cleaned_value;
	}

	public static function convert_csv_to_int_array(string $input) : array
	{
		$array = explode(',', $input);

		$int_array = [];

		foreach($array as $item)
			if(is_numeric($item))
				$int_array[] = $item;

		return $int_array;
	}

	private function build_event_instance_list_cache_key(string $id) : string
	{
		return $this->event_instance_list_cache_key_prefix . $id;
	}

	private function build_custom_event_filter_cache_key(string $attribute_name) : string
	{
		return $this->custom_event_filter_cache_key_prefix . $attribute_name;
	}

	/**
	 * Date filters should be strings, in the form "Y-m-d"
	 */
	private function filter_event_list($event_list, ?string $date_from_filter, ?string $date_to_filter, ?string $offer_filter, $custom_filters_selected_values, ?string $event_name_filter)
	{
		$filtered_event_list = [];

		$date_from_filter_set = strlen($date_from_filter ?? '') > 0;
		$date_to_filter_set = strlen($date_to_filter ?? '') > 0;
		$offer_filter_set = strlen($offer_filter ?? '') > 0;
		$custom_filters_set = is_array($custom_filters_selected_values) && count($custom_filters_selected_values) > 0;
		$event_name_filter_set = strlen($event_name_filter ?? '') > 0;

		// Check at least one filter has been set, otherwise just return the unfiltered list.

		if($date_from_filter_set || $date_to_filter_set || $offer_filter_set || $custom_filters_set || $event_name_filter_set)
		{
			foreach($event_list as $event)
			{
				// Ignore event times when filtering, we only care about the date.

				$event_start_date = gmdate(self::$event_list_date_filter_format, strtotime($event->firstInstanceDateTime));
				$event_end_date = gmdate(self::$event_list_date_filter_format, strtotime($event->lastInstanceDateTime));

				$passes_filter = true;

				if($passes_filter && $date_from_filter_set && $event_end_date < $date_from_filter)
					$passes_filter = false;

				if($passes_filter && $date_to_filter_set && $event_start_date > $date_to_filter)
					$passes_filter = false;

				if($passes_filter && !str_contains(strtolower($event->name), strtolower($event_name_filter)))
					$passes_filter = false;

				if($passes_filter && $offer_filter_set && $offer_filter != Box_Office_WP_Constants::$please_select_value_prefix)
				{
					$event_has_offer = false;

					// Remember to test against the "cleaned" offer name
					
					foreach($event->offers as $event_offer)
						if($this->clean_custom_filter_value($event_offer) == $offer_filter)
							$event_has_offer = true;

					$passes_filter = $event_has_offer;
				}

				if($passes_filter && $custom_filters_set)
				{
					foreach($custom_filters_selected_values as $key => $value)
					{
						// If the filter value is "please select", then nothing has been selected, so this filter passes.

						if($passes_filter && $value != Box_Office_WP_Constants::$please_select_value_prefix)
						{
							// The $key will be the custom filter control ID, e.g. "box-office-wp-event-filter-custom-Type".
							// To find the associated event custom attribute property, we remove the control ID prefix (i.e.
							// "box-office-wp-event-filter-custom-"), and replace it with the attribute prefix (i.e. "attribute_").

							$attribute_property_name = str_replace(Box_Office_WP_Constants::$custom_event_filter_control_id_prefix, $this->spektrix_custom_attribute_prefix, $key);

							// When comparing the value against our event's custom attributes, remember that the passed
							// in $value will have been "cleaned" first, so test against the "cleaned" property.

							if(property_exists($event, $attribute_property_name))
								$passes_filter = $this->clean_custom_filter_value($event->$attribute_property_name) == $value;
						}
					}
				}

				if($passes_filter)
					$filtered_event_list[] = $event;
			}
		}
		else
			return $event_list;

		return $filtered_event_list;
	}

	private function filter_event_list_by_id_list($event_list, $event_id_list)
	{
		// Calling function should have already checked this, but doesn't hurt to check again.

		$event_id_list_set = is_array($event_id_list) && count($event_id_list) > 0;

		// If we haven't been passed an event ID list filter, return the unfiltered list.

		if(!$event_id_list_set)
			return $event_list;

		// OK, we have an event ID list. Loop through the unfiltered list, adding only those events specified by the 
		// filter list, keeping specified order.

		$filtered_event_list = [];

		// The nested for-loops below don't look great, but the event ID list shouldn't contain many items, and the 
		// full event list also won't be *that* long.

		foreach($event_id_list as $event_id)
			foreach($event_list as $event)
				if($event->eventID == $event_id)
					$filtered_event_list[] = $event;

		return $filtered_event_list;
	}

	/**
	 * Searches for the supplied event_id in the supplied event list. If found, returns the event's offers. Otherwise 
	 * returns an empty array.
	 */
	private function get_event_offers($event_list, int $event_id) : array
	{
		$offers = [];

		foreach($event_list as $event)
			if($event->eventID == $event_id)
				$offers = $event->offers;

		return $offers;
	}
}
