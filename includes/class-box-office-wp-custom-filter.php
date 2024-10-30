<?php
/**
 * Custom Filter class
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Box_Office_WP_Custom_Filter
{
	public string $spektrix_attribute_name;
	public string $display_name;
	public string $control_id;

	function __construct(string $spektrix_attribute_name, string $display_name)
	{
		$this->spektrix_attribute_name = $spektrix_attribute_name;
		$this->display_name = $display_name;
		$this->control_id = Box_Office_WP_Constants::$custom_event_filter_control_id_prefix . $spektrix_attribute_name;
	}
}