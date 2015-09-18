<?php
/*
Plugin Name: MF Base
Plugin URI: www.github.com/frostkom/mf_base
Version: 1.4.6
Author: Martin Fors
Author URI: www.frostkom.se
*/

add_action('init', 'init_base');
add_action('admin_init', 'settings_base');
add_filter('the_password_form', 'password_form_base');
add_filter('the_content', 'the_content_protected_base');
add_filter('plugin_action_links', 'disable_action_base', 10, 4);
add_filter('network_admin_plugin_action_links', 'disable_action_base', 10, 4);
add_filter('plugin_action_links_'.plugin_basename(__FILE__), 'add_action_base');
add_filter('network_admin_plugin_action_links_'.plugin_basename(__FILE__), 'add_action_base');

function disable_action_base($actions, $plugin_file, $plugin_data, $context)
{
	// Remove edit link for all
	/*if(array_key_exists('edit', $actions))
	{
		unset($actions['edit']);
	}*/

	if(array_key_exists('deactivate', $actions) && in_array($plugin_file, array('mf_base/index.php', 'mf_form/index.php')))
	{
		unset($actions['deactivate']);
	}

	return $actions;
}


function add_action_base($links)
{
	$links[] = "<a href='".admin_url('options-general.php?page=settings_mf_base')."'>".__("Settings", 'lang_base')."</a>";

	return $links;
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

	$setting_base_auto_core_email = get_option('setting_base_auto_core_email');

	if($setting_base_auto_core_email != "yes")
	{
		apply_filters('auto_core_update_send_email', '__return_false');
		//apply_filters('auto_core_update_send_email', false, $type, $core_update, $result);
	}

	wp_enqueue_style('font-awesome', plugins_url()."/mf_base/include/font-awesome.min.css");
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