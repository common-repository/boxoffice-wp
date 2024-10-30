<?php
/**
 * Block_Logic class
 * 
 * Boilerplate code for setting up and configuring custom Gutenberg Blocks.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Box_Office_WP_Block_Logic
{
	public static string $event_list_block_name = 'box-office-wp-event-list';
	public static string $event_filter_block_name = 'box-office-wp-event-filter';
	public static string $event_list_alpha_sort_block_name = 'box-office-wp-event-list-alpha-sort';
	public static string $account_iframe_block_name = 'box-office-wp-account-iframe';
	public static string $basket_iframe_block_name = 'box-office-wp-basket-iframe';
	public static string $checkout_iframe_block_name = 'box-office-wp-checkout-iframe';
	public static string $event_details_iframe_block_name = 'box-office-wp-event-details-iframe';

	public static function generate_block_markup($block_name, $pre_tag_wrapper = '', $attributes = '', $post_tag_wrapper = '')
	{
		// Block names use hyphens, whereas shortcodes use underscores. Apart from that, block names and shortcode tags
		// should match.

		$shortcode_tag = str_replace('-', '_', $block_name);

		$content = '';

		$content .= "<!-- wp:box-office-wp/$block_name -->";
		$content .= $pre_tag_wrapper . '[' . $shortcode_tag . $attributes . ']' . $post_tag_wrapper;
		$content .= "<!-- /wp:box-office-wp/$block_name -->";

		return $content;
	}

	/**
	 * Enqueue CSS and Javascript assets for various BoxOffice WP blocks - admin mode
	 */
	 public function enqueue_block_assets()
	 {
		$this->enqueue_block_assets_for_block(self::$event_list_block_name);
		$this->enqueue_block_assets_for_block(self::$event_filter_block_name);
		$this->enqueue_block_assets_for_block(self::$event_list_alpha_sort_block_name);
		$this->enqueue_block_assets_for_block(self::$account_iframe_block_name);
		$this->enqueue_block_assets_for_block(self::$basket_iframe_block_name);
		$this->enqueue_block_assets_for_block(self::$checkout_iframe_block_name);
		$this->enqueue_block_assets_for_block(self::$event_details_iframe_block_name);

		if(Box_Office_WP::in_pro_mode())
		{
			$pro_block_logic = new Box_Office_WP_Pro_Block_Logic();

			$pro_block_logic->enqueue_block_assets();
		}
	 }

	 public function block_categories_all($categories)
	 {
		$categories[] = array(
			'slug'  => Box_Office_WP_Constants::$block_custom_category_slug,
			'title' => Box_Office_WP_Constants::$block_custom_category_display_name
		);
	
		return $categories;
	 }

	 public function enqueue_block_assets_for_block(string $block_name, bool $is_pro_block = false)
	 {
		$this->enqueue_style($block_name, $is_pro_block);
		$this->enqueue_script($block_name, $is_pro_block);
	 }

	 private function enqueue_style(string $block_name, bool $is_pro_block)
	 {
		$css_path = $this->get_block_css_path($block_name, $is_pro_block);

		wp_enqueue_style($block_name, $css_path . "/$block_name.css", [ 'wp-edit-blocks' ], BOX_OFFICE_WP_VERSION);
	 }

	 private function enqueue_script(string $block_name, bool $is_pro_block)
	 {
		$js_path = $this->get_block_js_path($block_name, $is_pro_block);

		wp_enqueue_script($block_name, $js_path . "/$block_name.js", [ 'wp-blocks', 'wp-dom' ] , BOX_OFFICE_WP_VERSION, true);
	 }

	 private function get_block_js_path(string $block_name, bool $is_pro_block) : string
	 {
		return $this->get_block_root_path($block_name, $is_pro_block) . $block_name . '/js';
	 }

	 private function get_block_css_path(string $block_name, bool $is_pro_block) : string
	 {
		return $this->get_block_root_path($block_name, $is_pro_block) . $block_name . '/css';
	 }

	 private function get_block_root_path(string $block_name, bool $is_pro_block) : string
	 {
		$block_root_path = plugin_dir_url(dirname(__FILE__));

		if($is_pro_block)
			$block_root_path .= 'pro/blocks/';
		else
			$block_root_path .= 'blocks/';

		return $block_root_path;
	 }
 }