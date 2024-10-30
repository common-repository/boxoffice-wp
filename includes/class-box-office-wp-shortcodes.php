<?php
/**
 * Shortcodes class
 * 
 * All content replacement is handled by the shortcodes within this class. They can be used directly on the site, but 
 * are more likely to be used indirectly, via their associated Gutenberg Block wrappers.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Box_Office_WP_Shortcodes
{
	public static string $event_list_tag = 'box_office_wp_event_list';
	public static string $event_filter_tag = 'box_office_wp_event_filter';
	public static string $event_list_alpha_sort_tag = 'box_office_wp_event_list_alpha_sort';
	public static string $event_details_iframe_tag = 'box_office_wp_event_details_iframe';
	public static string $basket_iframe_tag = 'box_office_wp_basket_iframe';
	public static string $account_iframe_tag = 'box_office_wp_account_iframe';
	public static string $checkout_iframe_tag = 'box_office_wp_checkout_iframe';
	public static string $limit_attribute = 'limit';
	public static string $event_id_list_attribute = 'event_id_list';
	public static string $max_description_words_to_display_attribute = 'max_description_words_to_display';
	public static string $selected_attribute = ' selected="selected"';

	public static function create_drop_down_list_for_custom_filter($api, $custom_filter, $custom_filters_selected_values) : string
	{
		$markup = '';
		$control_id = $custom_filter->control_id;
		$display_name = $custom_filter->display_name;
		$please_select_value = Box_Office_WP_Constants::$please_select_value_prefix;
		$please_select_display = $please_select_value . strtolower($display_name) . '...';

		// If this is a form post-back, maintain state of custom filter drop downs.

		$selected_value = isset($_POST[Box_Office_WP_Settings::$filter_event_list_form_action]) && 
			isset($_POST['box_office_wp_event_filter_nonce']) && 
			wp_verify_nonce(sanitize_text_field( wp_unslash($_POST['box_office_wp_event_filter_nonce'])), 'box_office_wp_event_filter_nonce') 
			? $custom_filters_selected_values[$control_id] : $please_select_value;

		// Get the unique list of possible values for this custom attribute filter

		$values = $api->get_list_of_values_for_custom_attribute($custom_filter->spektrix_attribute_name);

		$markup .= "<div class='$control_id'>";
		$markup .= "	<label for='$control_id'>$display_name</label>";
		$markup .= "	<select name='$control_id' id='$control_id'>";

		$markup .= "		<option value='$please_select_value'>$please_select_display</option>";

		foreach($values as $value)
		{
			// We "clean" the value, i.e. remove any quotes, angle brackets. But we use the "unclean" 
			// version for display.

			$cleaned_value = $api->clean_custom_filter_value($value);
			$selected_text = $selected_value == $cleaned_value ? Box_Office_WP_Shortcodes::$selected_attribute : '';

			// If the value is boolean, display something more friendly

			$display_value = is_bool($value) ? $cleaned_value : $value;

			$markup .= "		<option value='$cleaned_value'$selected_text>$display_value</option>";
		}

		$markup .= '	</select>';
		$markup .= "</div>";

		return $markup;
	}

	/**
	 * Registers BoxOffice WP shortcodes
	 */
	public function add_shortcodes()
	{
		$this->add_event_list_shortcode();
		$this->add_event_filter_shortcode();
		$this->add_event_list_alpha_sort_shortcode();
		$this->add_event_details_iframe_shortcode();
		$this->add_basket_iframe_shortcode();
		$this->add_account_iframe_shortcode();
		$this->add_checkout_iframe_shortcode();

		if(Box_Office_WP::in_pro_mode())
		{
			$pro_shortcodes = new Box_Office_WP_Pro_Shortcodes();

			$pro_shortcodes->add_shortcodes();
		}
	}
	
	/**
	 * Registers and handles event list shortcode
	 */
	private function add_event_list_shortcode()
	{
		add_shortcode(self::$event_list_tag, 'box_office_wp_event_list_shortcode_handler');

		function box_office_wp_event_list_shortcode_handler($atts = [], $content = null, $tag = '')
		{
			$settings = Box_Office_WP_Settings::load_settings();

			// Normalize attribute keys, lowercase.

			$atts = array_change_key_case((array)$atts, CASE_LOWER);

			// Override default attributes with user attributes
			
			$event_list_atts = shortcode_atts(array(
													Box_Office_WP_Shortcodes::$limit_attribute => Box_Office_WP_Constants::$default_event_list_limit,
													Box_Office_WP_Shortcodes::$max_description_words_to_display_attribute => Box_Office_WP_Constants::$default_event_list_max_description_words_to_display,
													Box_Office_WP_Shortcodes::$event_id_list_attribute => ''
													),
												$atts, $tag);

			$limit = $event_list_atts[Box_Office_WP_Shortcodes::$limit_attribute];
			$max_description_words_to_display = $event_list_atts[Box_Office_WP_Shortcodes::$max_description_words_to_display_attribute];
			$event_id_list = Box_Office_WP_Spektrix_Api::convert_csv_to_int_array($event_list_atts[Box_Office_WP_Shortcodes::$event_id_list_attribute]);

			if(!is_int($max_description_words_to_display))
				$max_description_words_to_display = Box_Office_WP_Constants::$default_event_list_max_description_words_to_display;

			// Check if this is a form post-back from the event filter control, and if so, apply filters when 
			// retrieving list.

			$date_from_filter = null;
			$date_to_filter = null;
			$example_custom_filter = null;
			$offer_filter = null;
			$event_name_filter = null;
			$custom_filters_selected_values = [];
			$alpha_sort = Box_Office_WP_Shortcodes::get_alpha_sort_from_post_array();
			
			if(isset($_POST[$settings::$filter_event_list_form_action]) && 
				isset($_POST['box_office_wp_event_filter_nonce']) && 
				wp_verify_nonce(sanitize_text_field( wp_unslash($_POST['box_office_wp_event_filter_nonce'])), 'box_office_wp_event_filter_nonce')
			)
			{
				$date_from_filter = sanitize_text_field(isset($_POST[Box_Office_WP_Constants::$event_filter_from_date_control_id]) ? wp_unslash($_POST[Box_Office_WP_Constants::$event_filter_from_date_control_id]) : '');
				$date_to_filter = sanitize_text_field(isset($_POST[Box_Office_WP_Constants::$event_filter_to_date_control_id]) ? wp_unslash($_POST[Box_Office_WP_Constants::$event_filter_to_date_control_id]) : '');

				if(isset($_POST[Box_Office_WP_Constants::$event_filter_event_name_control_id]))
					$event_name_filter = sanitize_text_field(wp_unslash($_POST[Box_Office_WP_Constants::$event_filter_event_name_control_id]));

				if(isset($_POST[Box_Office_WP_Constants::$event_filter_offers_control_id]))
					$offer_filter = sanitize_text_field(wp_unslash($_POST[Box_Office_WP_Constants::$event_filter_offers_control_id]));

				foreach($settings->custom_filter_list as $custom_filter)
					$custom_filters_selected_values[$custom_filter->control_id] = sanitize_text_field(isset($_POST[$custom_filter->control_id]) ? wp_unslash($_POST[$custom_filter->control_id]) : '');
			}

			$box_office_wp_api = new Box_Office_WP_Spektrix_Api();

			return $box_office_wp_api->get_event_list_as_html($limit, $max_description_words_to_display, $event_id_list, $date_from_filter, $date_to_filter, $offer_filter, $custom_filters_selected_values, $event_name_filter, $alpha_sort);
		}
	}

	/**
	 * Registers and handles event filter shortcode
	 */
	private function add_event_filter_shortcode()
	{
		add_shortcode(self::$event_filter_tag, 'box_office_wp_event_filter_shortcode_handler');

		function box_office_wp_event_filter_shortcode_handler($atts = [], $content = null)
		{
			$settings = Box_Office_WP_Settings::load_settings();

			$form_action = Box_Office_WP_Settings::$filter_event_list_form_action;
			$event_list_page_relative_url = '/' . $settings->event_list_page_slug;
			$please_select_value = Box_Office_WP_Constants::$please_select_value_prefix;
			$event_name_filter_placeholder_text = Box_Office_WP_Constants::$event_name_filter_placeholder_text;
			$event_name_filter_minlength = Box_Office_WP_Constants::$event_name_filter_minlength;

			$api = new Box_Office_WP_Spektrix_Api();

			$event_filter_from_date_control_id = Box_Office_WP_Constants::$event_filter_from_date_control_id;
			$event_filter_to_date_control_id = Box_Office_WP_Constants::$event_filter_to_date_control_id;
			$event_filter_offers_control_id = Box_Office_WP_Constants::$event_filter_offers_control_id;
			$event_filter_event_name_control_id = Box_Office_WP_Constants::$event_filter_event_name_control_id;

			// Default the date filters to: "today to one year from today"

			$date_filter_from = gmdate(Box_Office_WP_Spektrix_Api::$event_list_date_filter_format);
			$date_filter_to = gmdate(Box_Office_WP_Spektrix_Api::$event_list_date_filter_format, strtotime(' + 1 year'));
			$selected_offer = '';
			$event_name = '';

			// If this is a form post-back, maintain state

			$custom_filters_selected_values = [];

			if(isset($_POST[Box_Office_WP_Settings::$filter_event_list_form_action]) && 
				isset($_POST['box_office_wp_event_filter_nonce']) && 
				wp_verify_nonce(sanitize_text_field( wp_unslash($_POST['box_office_wp_event_filter_nonce'])), 'box_office_wp_event_filter_nonce')
			)
			{
				$date_filter_from = sanitize_text_field(isset($_POST[$event_filter_from_date_control_id]) ? wp_unslash($_POST[$event_filter_from_date_control_id]) : '');
				$date_filter_to = sanitize_text_field(isset($_POST[$event_filter_to_date_control_id]) ? wp_unslash($_POST[$event_filter_to_date_control_id]) : '');
				
				if(isset($_POST[$event_filter_event_name_control_id]))
					$event_name = sanitize_text_field(wp_unslash($_POST[$event_filter_event_name_control_id]));

				if(isset($_POST[$event_filter_offers_control_id]))
					$selected_offer = sanitize_text_field(wp_unslash($_POST[$event_filter_offers_control_id]));

				foreach($settings->custom_filter_list as $custom_filter)
					$custom_filters_selected_values[$custom_filter->control_id] = sanitize_text_field(isset($_POST[$custom_filter->control_id]) ? wp_unslash($_POST[$custom_filter->control_id]) : '');
			}

			$markup = '';

			$markup .= "<div class='box-office-wp-event-filter'>";

				$markup .= "<form action='$event_list_page_relative_url' method='post'>";

					$markup .= wp_nonce_field('box_office_wp_event_filter_nonce', 'box_office_wp_event_filter_nonce');

					// Event name search

					$markup .= "<div class='$event_filter_event_name_control_id'>";
					$markup .= "	<label for='$event_filter_event_name_control_id'>Event name</label>";
					$markup .= "	<input name='$event_filter_event_name_control_id' id='$event_filter_event_name_control_id' type='text' placeholder='$event_name_filter_placeholder_text' minlength='$event_name_filter_minlength' value='$event_name'>";
					$markup .= "</div>";

					// Custom attribute filters

					foreach($settings->custom_filter_list as $custom_filter)
						$markup .= Box_Office_WP_Shortcodes::create_drop_down_list_for_custom_filter($api, $custom_filter, $custom_filters_selected_values);

					// Offers filter

					if($settings->offer_filter_enabled)
					{
						$please_select_display = $please_select_value . ' offer...';

						$markup .= "<div class='$event_filter_offers_control_id'>";
						$markup .= "	<label for='$event_filter_offers_control_id'>Offers</label>";
						$markup .= "	<select name='$event_filter_offers_control_id' id='$event_filter_offers_control_id'>";
				
						$markup .= "		<option value='$please_select_value'>$please_select_display</option>";
	
						foreach($api->get_unique_offers_list() as $offer_name)
						{
							// We "clean" the value, i.e. remove any quotes, angle brackets. But we use the "unclean" 
							// version for display.
				
							$cleaned_value = $api->clean_custom_filter_value($offer_name);
							$selected_text = $selected_offer == $cleaned_value ? Box_Office_WP_Shortcodes::$selected_attribute : '';
				
							$markup .= "		<option value='$cleaned_value'$selected_text>$offer_name</option>";
						}
	
						$markup .= '	</select>';
						$markup .= "</div>";			
					}

					// Date From filter

					$markup .= "<div class='$event_filter_from_date_control_id'>";
					$markup .= "	<label for='$event_filter_from_date_control_id'>Date from</label>";
					$markup .= "	<input type='date' id='$event_filter_from_date_control_id' name='$event_filter_from_date_control_id' value='$date_filter_from'>";
					$markup .= "</div>";

					// Date to filter

					$markup .= "<div class='$event_filter_to_date_control_id'>";
					$markup .= "	<label for='$event_filter_to_date_control_id'>Date to</label>";
					$markup .= "	<input type='date' id='$event_filter_to_date_control_id' name='$event_filter_to_date_control_id' value='$date_filter_to'>";
					$markup .= "</div>";

					$markup .= "<div class='box-office-wp-event-filter-submit'>";
					$markup .= "	<input type='submit' name='$form_action' class='button button-primary' value='Search' >";
					$markup .= "</div>";

				$markup .= "</form>";

			$markup .= "</div>";

			return $markup;
		}
	}

	/**
	 * Registers and handles event list alpha sort shortcode
	 */
	private function add_event_list_alpha_sort_shortcode()
	{
		add_shortcode(self::$event_list_alpha_sort_tag, 'box_office_wp_event_list_alpha_sort_shortcode_handler');

		function box_office_wp_event_list_alpha_sort_shortcode_handler($atts = [], $content = null)
		{
			$ascending_value = Box_Office_WP_Constants::$ascending;
			$descending_value = Box_Office_WP_Constants::$descending;
			$no_sort_value = Box_Office_WP_Constants::$no_sort;
			$alpha_sort_control_id = Box_Office_WP_Constants::$alpha_sort_control_id;
			$alpha_sort = '';

			// If this is a form post-back, maintain state

			if(isset($_POST[Box_Office_WP_Constants::$alpha_sort_control_id]) && 
				isset($_POST['box_office_wp_alpha_sort_nonce']) && 
				wp_verify_nonce(sanitize_text_field( wp_unslash($_POST['box_office_wp_alpha_sort_nonce'])), 'box_office_wp_alpha_sort_nonce')
			)
				$alpha_sort = Box_Office_WP_Shortcodes::get_alpha_sort_from_post_array();

			$atoz_selected_text = $alpha_sort == $ascending_value ? Box_Office_WP_Shortcodes::$selected_attribute : '';
			$ztoa_selected_text = $alpha_sort == $descending_value ? Box_Office_WP_Shortcodes::$selected_attribute : '';
			$date_selected_text = $alpha_sort == $no_sort_value ? Box_Office_WP_Shortcodes::$selected_attribute : '';

			// Default to date sort

			if($atoz_selected_text == '' && $ztoa_selected_text == '' && $date_selected_text == '')
				$date_selected_text = Box_Office_WP_Shortcodes::$selected_attribute;

			$markup = '';

			$markup .= "<div class='box-office-wp-alpha-sort'>";

			$markup .= "<form action='' method='post'>";

				$markup .= wp_nonce_field('box_office_wp_alpha_sort_nonce', 'box_office_wp_alpha_sort_nonce');

				$markup .= Box_Office_WP_Shortcodes::generate_hidden_fields_to_maintain_state();

				$markup .= "<select name='$alpha_sort_control_id' onchange='this.form.submit();'>";
				$markup .= "	<option value='$no_sort_value'$date_selected_text>Sort by: Date</option>";
				$markup .= "	<option value='$ascending_value'$atoz_selected_text>Sort by: A-Z</option>";
				$markup .= "	<option value='$descending_value'$ztoa_selected_text>Sort by: Z-A</option>";
				$markup .= "</select>";

				$markup .= "</form>";

			$markup .= "</div>";

			return $markup;
		}
	}

	/**
	 * Registers and handles event details iframe shortcode
	 */
	private function add_event_details_iframe_shortcode()
	{
		add_shortcode(self::$event_details_iframe_tag, 'box_office_wp_event_details_iframe_shortcode_handler');

		function box_office_wp_event_details_iframe_shortcode_handler($atts = [], $content = null)
		{
			if(Box_Office_WP_Shortcodes::instance_id_in_query_string())
				return '';

			$event_id = get_query_var(Box_Office_WP_Constants::$event_id_query_string_key);

			return Box_Office_WP_Shortcodes::build_spektrix_iframe_markup("/website/EventDetails.aspx?EventID=$event_id");
		}
	}

	/**
	 * Registers and handles basket shortcode
	 */
	private function add_basket_iframe_shortcode()
	{
		add_shortcode(self::$basket_iframe_tag, 'box_office_wp_basket_iframe_shortcode_handler');

		function box_office_wp_basket_iframe_shortcode_handler($atts = [], $content = null)
		{
			return Box_Office_WP_Shortcodes::build_spektrix_iframe_markup('/website/Basket2.aspx');
		}
	}

	/**
	 * Registers and handles account shortcode
	 */
	private function add_account_iframe_shortcode()
	{
		add_shortcode(self::$account_iframe_tag, 'box_office_wp_account_iframe_shortcode_handler');

		function box_office_wp_account_iframe_shortcode_handler($atts = [], $content = null)
		{
			return Box_Office_WP_Shortcodes::build_spektrix_iframe_markup('/website/Secure/MyAccount.aspx');
		}
	}

	/**
	 * Registers and handles checkout shortcode
	 */
	private function add_checkout_iframe_shortcode()
	{
		add_shortcode(self::$checkout_iframe_tag, 'box_office_wp_checkout_iframe_shortcode_handler');

		function box_office_wp_checkout_iframe_shortcode_handler($atts = [], $content = null)
		{
			return Box_Office_WP_Shortcodes::build_spektrix_iframe_markup("/website/Secure/Checkout.aspx");
		}
	}

	/**
	 * This function is used to enqueue the resize script for the Spektrix iframe
	 */
	public static function enqueue_spektrix_resize_script()
	{
		$settings = Box_Office_WP_Settings::load_settings();
		$box_office_wp_api = new Box_Office_WP_Spektrix_Api();
		$javascript_resize_url = $box_office_wp_api->combine_api_urls($settings->spektrix_url, '/website/scripts/integrate.js');

		wp_enqueue_script('box-office-wp-spektrix-resize', $javascript_resize_url, array(), '1.0.0', false);
	}

	/**
	 * This function is only public so it can be called statically
	 */
	public static function build_spektrix_iframe_markup(string $iframe_path) : string
	{
		$settings = Box_Office_WP_Settings::load_settings();
		$box_office_wp_api = new Box_Office_WP_Spektrix_Api();

		$path_includes_query_string_args = str_contains($iframe_path, '?');

		$iframe_url = $box_office_wp_api->combine_api_urls($settings->spektrix_url, $iframe_path);
		$resize_query_string = $path_includes_query_string_args ? '&resize=true' : '?resize=true';		

		$iframe_url .= $resize_query_string;

		$html = "<iframe name='SpektrixIFrame' id='SpektrixIFrame' title='SpektrixIFrame' src='$iframe_url' frameborder='0'></iframe>";

		return $html;
	}

	/**
	 * This function will return markup that creates a hidden input field for each Event Filter item in the current 
	 * $_POST array.
	 */
	public static function generate_hidden_fields_to_maintain_state() : string
	{
		$markup = '';

		if(isset($_POST['box_office_wp_event_filter_nonce']) && 
			wp_verify_nonce(sanitize_text_field( wp_unslash($_POST['box_office_wp_event_filter_nonce'])), 'box_office_wp_event_filter_nonce')
		)
		{
			$markup .= Box_Office_WP_Shortcodes::generate_hidden_field('box_office_wp_event_filter_nonce');
			$markup .= Box_Office_WP_Shortcodes::generate_hidden_field(Box_Office_WP_Settings::$filter_event_list_form_action);
			$markup .= Box_Office_WP_Shortcodes::generate_hidden_field(Box_Office_WP_Constants::$event_filter_event_name_control_id);
			$markup .= Box_Office_WP_Shortcodes::generate_hidden_field(Box_Office_WP_Constants::$event_filter_from_date_control_id);
			$markup .= Box_Office_WP_Shortcodes::generate_hidden_field(Box_Office_WP_Constants::$event_filter_to_date_control_id);
			$markup .= Box_Office_WP_Shortcodes::generate_hidden_field(Box_Office_WP_Constants::$event_filter_offers_control_id);
			$markup .= Box_Office_WP_Shortcodes::generate_hidden_field(Box_Office_WP_Constants::$alpha_sort_control_id);

			$settings = Box_Office_WP_Settings::load_settings();

			foreach($settings->custom_filter_list as $custom_filter)
				$markup .= Box_Office_WP_Shortcodes::generate_hidden_field($custom_filter->control_id);
		}

		return $markup;
	}

	public static function generate_hidden_field(string $key)
	{
		$markup = '';

		if(isset($_POST['box_office_wp_event_filter_nonce']) && 
		wp_verify_nonce(sanitize_text_field( wp_unslash($_POST['box_office_wp_event_filter_nonce'])), 'box_office_wp_event_filter_nonce')
		)
		{
			if(isset($_POST[$key]))
			{
				$escaped_key = sanitize_text_field($key);
				$escaped_value = sanitize_text_field(wp_unslash($_POST[$key]));
				$markup .= "<input type='hidden' name='$escaped_key' value='$escaped_value' />";
			}
	
		}

		return $markup;
	}

	public static function get_alpha_sort_from_post_array()
	{
		$alpha_sort = Box_Office_WP_Constants::$no_sort;

		if(isset($_POST[Box_Office_WP_Constants::$alpha_sort_control_id]) && 
			isset($_POST['box_office_wp_alpha_sort_nonce']) && 
			wp_verify_nonce(sanitize_text_field( wp_unslash($_POST['box_office_wp_alpha_sort_nonce'])), 'box_office_wp_alpha_sort_nonce')
		)
			$alpha_sort = sanitize_text_field(wp_unslash($_POST[Box_Office_WP_Constants::$alpha_sort_control_id]));

		return $alpha_sort;
	}

	/**
	 * This function is only public so it can be called statically
	 */
	public static function get_event_details_from_query_string_event_id()
	{
		$event_id = get_query_var(Box_Office_WP_Constants::$event_id_query_string_key);

		$box_office_wp_api = new Box_Office_WP_Spektrix_Api();

		return $box_office_wp_api->get_event_details_by_event_id($event_id);
	}

	public static function instance_id_in_query_string() : bool
	{
		$instance_id = get_query_var(Box_Office_WP_Constants::$instance_id_query_string_key);

		return $instance_id != '';
	}
}