<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://line.industries
 * @package    Box_Office_WP
 * @subpackage Box_Office_WP/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Box_Office_WP
 * @subpackage Box_Office_WP/public
 * @author     Line Industries <support@lineindustries.com>
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Box_Office_WP_Public
{
	private $plugin_name;
	private $version;

	/**
	 * Initialize the class and set its properties.
	 */
	public function __construct($plugin_name, $version)
	{
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 */
	public function enqueue_styles()
	{
		wp_enqueue_style($this->plugin_name, plugins_url('css/box-office-wp-public.css', __FILE__), array(), $this->version, 'all');
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 */
	public function enqueue_scripts()
	{
		wp_enqueue_script($this->plugin_name, plugins_url('js/box-office-wp-public.js', __FILE__), array('jquery'), $this->version, array('strategy' => 'async', 'in_footer' => true));
	}

	/**
	* If current page is an event details page, change the page title to the event title.
	*/
	public function alter_title($title) : string
	{
		if($title == Box_Office_WP_Settings::load_settings()->event_details_page_name)
		{
			$event_name = $this->get_event_name_for_current_page();

			if($event_name != '')
				$title = $event_name;
		}

		return $title;
	}

	/**
	* If current page is an event details page, change the page title in the HTML head.
	*/
	public function box_office_wp_pre_get_document_title($title) : string
	{
		$event_name = $this->get_event_name_for_current_page();

		if($event_name != '')
			$title = "$event_name - " . get_bloginfo('name');

		return $title;
	}

	/**
	* If current page is an event details page, write a meta description to the HTML head.
	*/
	public function box_office_wp_wp_head()
	{
		$event_name = $this->get_event_name_for_current_page();

		if($event_name != '')
			echo "<meta name='description' content='" . esc_attr($event_name) . "'>\n";
	}

	/**
	 * Handler for the template_redirect action.
	 * 
	 * Redirects calls to Event Details page which are missing eventslug in query string.
	 */
	public function box_office_wp_template_redirect()
	{
		global $post;

		if(!is_null($post))
		{
			$settings = Box_Office_WP_Settings::load_settings();
			$spektrix_api = new Box_Office_WP_Spektrix_Api();

			$page_slug = $post->post_name;
	
			if($page_slug == $settings->event_details_page_slug)
			{
				$event_details = $spektrix_api->get_event_details_by_event_slug(get_query_var(Box_Office_WP_Constants::$event_slug_query_string_key));

				if(is_null($event_details))
				{
					// We're viewing Event Details page on public front-end of site, but there's no event slug in the 
					// query string. Redirect to event list page.
	
					exit(esc_url(wp_safe_redirect(home_url($settings->event_list_page_slug))));
				}
				else
				{
					// We have a valid event slug, and we've used it to fetch the event details. Add the eventID to the
					// query string vars so it can be used by Spektrix iframe shortcode handlers.

					set_query_var(Box_Office_WP_Constants::$event_id_query_string_key, $event_details->eventID);
				}
			}
		}
	}

	/**
	 * Handler for the init action.
	 * 
	 * Set up a rewrite rule for the event details pages. We won't actually have individual pages for events - instead 
	 * we'll be generating friendly URLs based on the event name, then using this rewrite rule to redirect these 
	 * requests to the event details page.
	 */
	public function box_office_wp_init()
	{
		self::add_whats_on_rewrite_rule();
	}

	/**
	 * Handler for the query_vars filter.
	 * 
	 * Make our custom query string vars available via core WordPress functions
	 */
	public function box_office_wp_init_query_vars($vars)
	{
		$vars[] = Box_Office_WP_Constants::$event_slug_query_string_key;
		$vars[] = Box_Office_WP_Constants::$event_id_query_string_key;
		$vars[] = Box_Office_WP_Constants::$instance_id_query_string_key;
	
		return $vars;
	}

	/**
	 * See "box_office_wp_init" for details.
	 * This function is public/static so it can be called from the plugin activation code.
	 */
	public static function add_whats_on_rewrite_rule()
	{
		$settings = Box_Office_WP_Settings::load_settings();

		$event_details_page_id = 0;
		$event_details_page = get_page_by_path($settings->event_details_page_slug);

		if(!is_null($event_details_page))
			$event_details_page_id = $event_details_page->ID;
		else
			Box_Office_WP::log("Error setting up what's on rewrite rule - couldn't find ID of event details page.");

		add_rewrite_rule($settings->whats_on_section . '/([a-z0-9-]+)[/]?$', 'index.php?page_id=' . $event_details_page_id . '&' . Box_Office_WP_Constants::$event_slug_query_string_key . '=$matches[1]', 'top');
	}

	private function get_event_name_for_current_page() : string
	{
		$event_name = '';

		global $post;

		if(!$this->current_page_is_event_details())
			return $event_name;

		$page_slug = $post->post_name;

		if($page_slug == Box_Office_WP_Settings::load_settings()->event_details_page_slug)
		{
			// We're viewing the event details page. See if we have an event slug in the query string, and if we do, use 
			// it to look up the event name.

			$event_slug = get_query_var(Box_Office_WP_Constants::$event_slug_query_string_key);

			if($event_slug != '')
			{
				$spektrix_api = new Box_Office_WP_Spektrix_Api();

				$event_details = $spektrix_api->get_event_details_by_event_slug($event_slug);

				if(!is_null($event_details))
					$event_name = $event_details->name;
			}
		}

		return $event_name;
	}

	private function current_page_is_event_details() : bool
	{
		global $post;

		return (!is_null($post) && $post != false && $post->post_name == Box_Office_WP_Settings::load_settings()->event_details_page_slug);
	}
}
