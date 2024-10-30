<?php

/**
 * @link       https://line.industries
 * @package    Box_Office_WP
 * @subpackage Box_Office_WP/admin/partials
 */

 if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

 class Box_Office_WP_Admin_Page
 {
	public static function admin_page_html()
	{
		if(isset($_POST['box_office_wp_nonce']) && wp_verify_nonce(sanitize_text_field( wp_unslash($_POST['box_office_wp_nonce'])), 'box_office_wp_nonce')) {
			if(isset($_POST[Box_Office_WP_Settings::$update_settings_form_action]))
			{
				Box_Office_WP_Admin::handle_settings_page_update_settings_button_click();

				echo wp_kses_post('<div class="notice notice-success settings-error"><p>Settings were updated successfully</p></div>');
			}

			if(isset($_POST[Box_Office_WP_Settings::$clear_cache_form_action]))
			{
				Box_Office_WP_Admin::handle_settings_page_clear_cache_button_click();

				echo wp_kses_post('<div class="notice notice-success settings-error"><p>Cached event list successfully cleared</p></div>');
			}

			if(isset($_POST[Box_Office_WP_Settings::$reset_settings_form_action]))
			{
				Box_Office_WP_Admin::handle_settings_page_reset_settings_button_click();

				echo wp_kses_post('<div class="notice notice-success settings-error"><p>Settings were reset successfully</p></div>');
			}
		}
		elseif(isset($_POST['box_office_wp_nonce']) && !wp_verify_nonce(sanitize_text_field( wp_unslash($_POST['box_office_wp_nonce'])), 'box_office_wp_nonce')) {
			echo wp_kses_post('<div class="notice notice-error settings-error"><p>Error: security check failed</p></div>');
		}
		



		$box_office_wp_settings = Box_Office_WP_Settings::load_settings();
		$box_office_wp_api = new Box_Office_WP_Spektrix_Api();

		$page_and_url_settings_tab_name = 'pageandurlsettings';
		$spektrix_api_settings_tab_name = 'spektrixapisettings';
		$event_list_tab_name = 'eventlist';
		$diagnostics_tab_name = 'diagnostics';
		$license_key_tab_name = 'licensekey';
		$in_pro_mode = Box_Office_WP::in_pro_mode();

		//Get the active tab from the $_GET param

		$default_tab = $page_and_url_settings_tab_name;
		$tab = isset($_GET['tab']) ? wp_unslash(sanitize_key($_GET['tab'])) : $default_tab;

		?>

		<h1><?php echo esc_html(Box_Office_WP::get_plugin_display_name()); ?></h1>

		<div class="box-office-wp-admin">
			<div class="wrap box-office-wp-content">

				<nav class="nav-tab-wrapper">
					<a href="?page=box-office-wp&tab=<?php echo esc_attr($page_and_url_settings_tab_name) ?>" class="nav-tab <?php if($tab === $page_and_url_settings_tab_name):?>nav-tab-active<?php endif; ?>">Page and URL settings</a>
					<a href="?page=box-office-wp&tab=<?php echo esc_attr($spektrix_api_settings_tab_name) ?>" class="nav-tab <?php if($tab === $spektrix_api_settings_tab_name):?>nav-tab-active<?php endif; ?>">Spektrix API settings</a>
					<a href="?page=box-office-wp&tab=<?php echo esc_attr($event_list_tab_name) ?>" class="nav-tab <?php if($tab === $event_list_tab_name):?>nav-tab-active<?php endif; ?>">Event list</a>
					<a href="?page=box-office-wp&tab=<?php echo esc_attr($diagnostics_tab_name) ?>" class="nav-tab <?php if($tab === $diagnostics_tab_name):?>nav-tab-active<?php endif; ?>">Diagnostic info</a>
					<a href="?page=box-office-wp&tab=<?php echo esc_attr($license_key_tab_name) ?>" class="nav-tab <?php if($tab === $license_key_tab_name):?>nav-tab-active<?php endif; ?>">License key</a>
				</nav>

				<div class="tab-content">
					<?php
						switch($tab) :
							case $page_and_url_settings_tab_name: ?>

								<form action="" method="post">
									<?php wp_nonce_field('box_office_wp_nonce', 'box_office_wp_nonce'); ?>
									<table class="form-table">
										<tbody>
											<tr>
												<th scope="row">
													<label for="event_list_page_slug">Event list page slug</label>
												</th>
											<td>
												<input type="text" id="event_list_page_slug" name="event_list_page_slug" value="<?php echo esc_attr($box_office_wp_settings->event_list_page_slug); ?>" required class="regular-text">
											</td>
											</tr>
											<tr>
												<th scope="row">
													<label for="event_details_page_slug">Event details page slug</label>
												</th>
												<td>
													<input type="text" id="event_details_page_slug" name="event_details_page_slug" value="<?php echo esc_attr($box_office_wp_settings->event_details_page_slug); ?>" required class="regular-text">
												</td>
											</tr>
											<tr>
												<th scope="row">
													<label for="basket_page_slug">Basket page slug</label>
												</th>
												<td>
													<input type="text" id="basket_page_slug" name="basket_page_slug" value="<?php echo esc_attr($box_office_wp_settings->basket_page_slug); ?>" required class="regular-text">
												</td>
											</tr>
											<tr>
												<th scope="row">
													<label for="account_page_slug">Account page slug</label>
												</th>
												<td>
													<input type="text" id="account_page_slug" name="account_page_slug" value="<?php echo esc_attr($box_office_wp_settings->account_page_slug); ?>" required class="regular-text">
												</td>
											</tr>
											<tr>
												<th scope="row">
													<label for="checkout_page_slug">Checkout page slug</label>
												</th>
												<td>
													<input type="text" id="checkout_page_slug" name="checkout_page_slug" value="<?php echo esc_attr($box_office_wp_settings->checkout_page_slug); ?>" required class="regular-text">
												</td>
											</tr>
											<tr>
												<th scope="row">
													<label for="event_details_page_name">Event details page name</label>
												</th>
												<td>
													<input type="text" id="event_details_page_name" name="event_details_page_name" value="<?php echo esc_attr($box_office_wp_settings->event_details_page_name); ?>" required class="regular-text">
												</td>
											</tr>
											<tr>
												<th scope="row">
													<label for="whats_on_section">"What's on" section</label>
												</th>
												<td>
													<input type="text" id="whats_on_section" name="whats_on_section" value="<?php echo esc_attr($box_office_wp_settings->whats_on_section); ?>" required class="regular-text">
													<p>By default all events will have a URL that starts "/whats-on/" - you can change that section of the URL here.</p>
												</td>
											</tr>
										</tbody>
									</table>

									<p class="submit">
										<input type="submit" name="<?php echo esc_attr(Box_Office_WP_Settings::$update_settings_form_action); ?>" class="button button-primary" value="Save settings" >
									</p>
								</form>

								<?php
								break;

							case $spektrix_api_settings_tab_name: ?>

								<form action="" method="post">
									<?php wp_nonce_field('box_office_wp_nonce', 'box_office_wp_nonce'); ?>
									<table class="form-table">
										<tbody>
											<tr>
												<th scope="row">
													<label for="spektrix_url">Spektrix URL</label>
												</th>
												<td>
													<input type="text" id="spektrix_url" name="spektrix_url" value="<?php echo esc_attr($box_office_wp_settings->spektrix_url); ?>" required class="regular-text">
													<?php echo wp_kses_post($box_office_wp_api->spektrix_url_is_good($box_office_wp_settings->spektrix_url) ? '' : "<p class='notice notice-error'>Error: can't get event feed from this URL.</p>"); ?>
													<p>Change this to your Spektrix URL, e.g.:<br /><em>https://system.spektrix.com/your_organisation_name</em></p>
													<p>For testing purposes you can always use:<br /><em>https://feed.boxofficewp.com/examplefeed</em></p>
												</td>
											</tr>
											<tr>
												<th scope="row">
													<label for="cache_duration_minutes">API cache duration (minutes)</label>
												</th>
												<td>
													<input type="number" id="cache_duration_minutes" name="cache_duration_minutes" value="<?php echo esc_attr($box_office_wp_settings->cache_duration_seconds / 60); ?>" required class="regular-text">
													<p>Controls how long to cache the results to avoid excess API traffic.</p>
												</td>
											</tr>
										</tbody>
									</table>

									<p class="submit">
										<input type="submit" name="<?php echo esc_attr(Box_Office_WP_Settings::$update_settings_form_action); ?>" class="button button-primary" value="Save settings" >
									</p>
								</form>
			
								<?php
								break;

							case $event_list_tab_name: ?>

							<?php if($in_pro_mode) : ?>

								<form action="" method="post">
									<?php wp_nonce_field('box_office_wp_nonce', 'box_office_wp_nonce'); ?>
									<table class="form-table">
										<tbody>
											<tr>
												<th scope="row">
													<label for="date_format">Date format</label>
												</th>
											<td>
												<input type="text" id="date_format" name="date_format" value="<?php echo esc_attr($box_office_wp_settings->date_format); ?>" required class="regular-text">
												<p>e.g. 'Y-m-d', 'm/d/Y', 'd/m/Y', etc.</p>
											</td>
											</tr>
											<tr>
												<th scope="row">
													<label for="event_list_layout">Layout</label>
												</th>
												<td>
													<textarea id="event_list_layout" name="event_list_layout" rows="10" required class="regular-text"><?php echo esc_html($box_office_wp_settings->event_list_layout); ?></textarea>
													<p>
														Use this setting to control which items are displayed in the event list, in what order, and whether or not each item links to the event details page. 
														You can even include a very limited amount of HTML to help layout each item (though your markup must not include single or double quotes).
													</p>
													<p>Use an optional "link" attribute to specify that item should link to the event details page, e.g. "[description link]".</p>
													<p>Available tags:
														<ul>
															<li><?php echo esc_html(Box_Office_WP_Event_List_Logic::$name_placeholder) ?></li>
															<li><?php echo esc_html(Box_Office_WP_Event_List_Logic::$description_placeholder) ?></li>
															<li><?php echo esc_html(Box_Office_WP_Event_List_Logic::$html_description_placeholder) ?></li>
															<li><?php echo esc_html(Box_Office_WP_Event_List_Logic::$duration_placeholder) ?></li>
															<li><?php echo esc_html(Box_Office_WP_Event_List_Logic::$image_placeholder) ?></li>
															<li><?php echo esc_html(Box_Office_WP_Event_List_Logic::$is_on_sale_placeholder) ?></li>
															<li><?php echo esc_html(Box_Office_WP_Event_List_Logic::$instance_dates_placeholder) ?></li>
															<li><?php echo esc_html(Box_Office_WP_Event_List_Logic::$thumbnail_placeholder) ?></li>
															<li><?php echo esc_html(Box_Office_WP_Event_List_Logic::$first_instance_date_time_placeholder) ?></li>
															<li><?php echo esc_html(Box_Office_WP_Event_List_Logic::$last_instance_date_time_placeholder) ?></li>
															<li><?php echo esc_html(Box_Office_WP_Event_List_Logic::$more_details_placeholder) ?></li>
															<li><?php echo esc_html(Box_Office_WP_Event_List_Logic::$book_now_placeholder) ?></li>
														</ul>
													</p>
												</td>
											</tr>
											<tr>
												<th scope="row">
													<label for="custom_filters">Custom filters</label>
												</th>
												<td>
													<textarea id="custom_filter_list" name="custom_filter_list" rows="10" class="regular-text"><?php echo esc_html(Box_Office_WP_Settings::convert_custom_filters_array_to_string($box_office_wp_settings->custom_filter_list)); ?></textarea>
													<p>
														Use this setting to display custom filters on the event list page. Add one filter per line, Spektrix custom attribute name 
														followed by the display name, separated by a semi-colon:<br />
														<br />
														"&lt;Spektrix attribute name&gt;;&lt;filter display name&gt;".<br />
														<br />
														For example, "Type;Event type".
													</p>
												</td>
											</tr>
											<tr>
												<th scope="row">
													<label for='offer_filter_enabled'>Offer filter enabled</label>
												</th>
												<td>
													<label for='offer_filter_enabled_true'>True</label>
													<input type="radio" id="offer_filter_enabled_true" name="offer_filter_enabled" value="true" <?php echo esc_attr($box_office_wp_settings->offer_filter_enabled ? 'checked="checked"' : '') ?> class="radio">
													<label for='offer_filter_enabled_false'>False</label>
													<input type="radio" id="offer_filter_enabled_false" name="offer_filter_enabled" value="false" <?php echo esc_attr(!$box_office_wp_settings->offer_filter_enabled ? 'checked="checked"' : '') ?> class="radio">

													<p>Show/hide the "filter by offers" drop down list. Enabling this filter has a slight impact on performance, due to the additional API calls required.</p>
												</td>
											</tr>
											<tr>
												<th scope="row">
													<label for="event_name_ignore_string">Event name ignore string</label>
												</th>
											<td>
												<input type="text" id="event_name_ignore_string" name="event_name_ignore_string" value="<?php echo esc_attr($box_office_wp_settings->event_name_ignore_string); ?>" class="regular-text">
												<p>Events containing this string of text will be excluded from the event list.</p>
											</td>
											</tr>
										</tbody>
									</table>

									<p class="submit">
										<input type="submit" name="<?php echo esc_attr(Box_Office_WP_Settings::$update_settings_form_action); ?>" class="button button-primary" value="Save settings" >
									</p>
								</form>

								<?php  else :
									ob_start();
									?>
									<table class='form-table'>
										<tr><td>
										<h2>Upgrade to BoxOffice WP Pro</h2>
										<p>BoxOffice WP Pro offers full configuration of the Event List layout, custom filters, and more. Visit <a href='http://boxofficewp.com/'>boxofficewp.com</a> to learn more.</p>
										</td></tr>
									</table>
									<?php
									$html = ob_get_clean();
									echo wp_kses_post($html);

								endif;
								break;

							case $diagnostics_tab_name:	?>

								<form action="" method="post">
									<?php wp_nonce_field('box_office_wp_nonce', 'box_office_wp_nonce'); ?>
									<table class="form-table">
										<tbody>
											<tr>
												<th scope="row">
													Event list last fetched from API
												</th>
												<td>
													<?php echo esc_html(Box_Office_WP_Admin::get_event_list_last_fetched_from_api()) ?>
												</td>
											</tr>
											<tr>
												<th scope="row">
													Cache duration
												</th>
												<td>
													<?php echo esc_html($box_office_wp_settings->cache_duration_seconds) ?> seconds
												</td>
											</tr>
											<tr>
												<th scope="row">
													Events cached
												</th>
												<td>
													<?php echo esc_html(Box_Office_WP_Admin::get_cached_event_list_count()) ?>
												</td>
											</tr>
											<tr>
												<th scope="row">
													Events awaiting updated offer data
												</th>
												<td>
													<?php echo esc_html(Box_Office_WP_Admin::get_events_with_stale_offer_data_count()) ?>
												</td>
											</tr>
											<tr>
												<th scope="row">
													<label for='debug_log_enabled'>Debug log enabled</label>
												</th>
												<td>
													<label for='debug_log_enabled_true'>True</label>
													<input type="radio" id="debug_log_enabled_true" name="debug_log_enabled" value="true" <?php echo esc_attr($box_office_wp_settings->debug_log_enabled ? 'checked="checked"' : '') ?> class="radio">
													<label for='debug_log_enabled_false'>False</label>
													<input type="radio" id="debug_log_enabled_false" name="debug_log_enabled" value="false" <?php echo esc_attr(!$box_office_wp_settings->debug_log_enabled ? 'checked="checked"' : '') ?> class="radio">

													<p>Log messages are written to the default WordPress debug log, which must be enabled separately.</p>
												</td>
											</tr>
										</tbody>
									</table>

									<p class="submit">
										<input type="submit" name="<?php echo esc_attr(Box_Office_WP_Settings::$update_settings_form_action); ?>" class="button button-primary" value="Save settings" >
									</p>
									<p class="submit">
										<input type="submit" name="<?php echo esc_attr(Box_Office_WP_Settings::$clear_cache_form_action); ?>" class="button button-primary" value="Clear cached event list" >
									</p>
									<p class="submit">
										<input type="submit" name="<?php echo esc_attr(Box_Office_WP_Settings::$reset_settings_form_action); ?>" class="button button-primary" value="Reset all settings to default" >
									</p>
								</form>

								<?php
								break;
							case $license_key_tab_name: ?>

								<form action="" method="post">
									<?php wp_nonce_field('box_office_wp_nonce', 'box_office_wp_nonce'); ?>
									<table class="form-table">
										<tbody>
											<tr>
												<th scope="row">
													<label for="license_key">BoxOffice WP Pro license key</label>
												</th>
												<td>
													<input type="text" name="license_key" id="license_key" value="<?php echo esc_attr( $box_office_wp_settings->license_key ? $box_office_wp_settings->license_key : '') ?>">
												</td>
											</tr>
										</tbody>
									</table>

									<p class="submit">
										<input type="submit" name="<?php echo esc_attr(Box_Office_WP_Settings::$update_settings_form_action); ?>" class="button button-primary" value="Save settings" >
									</p>
								</form>

								<?php
								break;

							default:
								break;
						endswitch;
					?>
				</div>
			</div>

			<div class="box-office-wp-content">
				<div class="box-office-wp-banner">
					<div class="branding">
						<a href="https://line.industries" target="_blank">
							<img class="branding-img" src="<?php echo esc_url(plugin_dir_url(__FILE__) . 'img/boxofficewp-logo-type-dark-rgb.svg'); ?>" alt="Line Industries">
						</a>
					</div>
					<div class="banner-content">
						<h2><?php echo esc_html(Box_Office_WP::get_plugin_display_name()); ?></h2>
						<?php if(!Box_Office_WP::in_pro_mode()) echo wp_kses_post(Box_Office_WP_Constants::$upgrade_message_markup); ?>
						<p>
							Useful links:<br><br>

							<a href="https://boxofficewp.com/getting-started/">Getting started</a><br>
							<a href="https://boxofficewp.com/documentation/">Documentation</a><br>
							<a href="https://boxofficewp.com/contact-us/">Contact us</a><br>

							<?php if(Box_Office_WP::in_pro_mode()) : ?>
								<a href="https://supplykit.co/line-industries/sign-in">Your account</a><br>
							<?php endif; ?>

							<a href="https://boxofficewp.com/about-us/">About us</a><br><br>

							<?php echo esc_html(Box_Office_WP::get_plugin_display_name()); ?> is developed by LINE, a UK based digital agency with over 20 years experience working with organisations around the globe from start-ups to multinationals. Get in touch if you would like to know more about paid support and custom development.
						</p>
					</div>
				</div>
			</div>
		</div>

		<?php
	}
 }
?>
