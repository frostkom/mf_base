<?php
/*
Plugin Name: MF Base
Plugin URI: http://github.com/frostkom/mf_base
Version: 1.6.5
Author: Martin Fors
Author URI: http://frostkom.se
*/

add_action('init', 'init_base');

if(is_admin())
{
	add_action('admin_init', 'settings_base', 0);
	add_filter('plugin_action_links', 'disable_action_base', 10, 4);
	add_filter('network_admin_plugin_action_links', 'disable_action_base', 10, 4);
	add_filter('plugin_action_links_'.plugin_basename(__FILE__), 'add_action_base');
	add_filter('network_admin_plugin_action_links_'.plugin_basename(__FILE__), 'add_action_base');
	add_filter('upload_mimes', 'upload_mimes_base');
}

else
{
	add_filter('the_password_form', 'password_form_base');
	add_filter('the_content', 'the_content_protected_base');

	add_action('wp_footer', 'footer_base');
}

load_plugin_textdomain('lang_base', false, dirname(plugin_basename(__FILE__)).'/lang/');

define('DEFAULT_DATE', "1982-08-04 23:15:00");

function init_base()
{
	$timezone_string = get_option('timezone_string');

	if($timezone_string != '')
	{
		date_default_timezone_set($timezone_string);
	}

	$setting_base_auto_core_update = get_option('setting_base_auto_core_update');
	$setting_base_auto_core_email = get_option('setting_base_auto_core_email');

	if($setting_base_auto_core_update != '')
	{
		if($setting_base_auto_core_update == "all"){		$setting_base_auto_core_update = true;}
		else if($setting_base_auto_core_update == "none"){	$setting_base_auto_core_update = false;}

		define('WP_AUTO_UPDATE_CORE', $setting_base_auto_core_update);
	}

	if($setting_base_auto_core_email != "yes")
	{
		apply_filters('auto_core_update_send_email', '__return_false');
		//apply_filters('auto_core_update_send_email', false, $type, $core_update, $result);
	}

	wp_enqueue_style('font-awesome', plugins_url()."/mf_base/include/font-awesome.min.css");
	wp_enqueue_style('style_base', plugins_url()."/mf_base/include/style.css");

	// Add datepicker
	wp_enqueue_style('jquery-ui-css', '//ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css');
	wp_enqueue_script('jquery-ui-datepicker');

	if(is_user_logged_in() && current_user_can("update_core"))
	{
		global $wpdb;

		if(!defined('DIEONDBERROR'))
		{
			define('DIEONDBERROR', true);
		}

		$wpdb->show_errors();
	}
}

include("include/classes.php");
include("include/functions.php");

if(is_admin())
{
	$my_settings_page = new MySettingsPage();
}