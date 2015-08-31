<?php
/*
Plugin Name: MF Base
Plugin URI: 
Version: 1.2.4
Author: Martin Fors
Author URI: www.frostkom.se
*/

add_action('init', 'init_base');
add_action('admin_init', 'settings_base');
add_filter('the_password_form', 'password_form_base');
add_filter('the_content', 'the_content_protected_base');

load_plugin_textdomain('lang_base', false, dirname(plugin_basename(__FILE__)).'/lang/');

define('DEFAULT_DATE', "1982-08-04 23:15:00");

function init_base()
{
	$timezone_string = get_option('timezone_string');

	if($timezone_string != '')
	{
		date_default_timezone_set($timezone_string);
	}

	wp_enqueue_style('style_base', plugins_url()."/mf_base/include/style.css");

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

$my_settings_page = new MySettingsPage();