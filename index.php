<?php
/*
Plugin Name: MF Base
Plugin URI: http://github.com/frostkom/mf_base
Description: 
Version: 2.0.2
Author: Martin Fors
Author URI: http://frostkom.se
*/

add_action('init', 'include_base', 1);

function include_base()
{
	define('DEFAULT_DATE', "1982-08-04 23:15:00");
	define('IS_ADMIN', current_user_can('update_core'));
	define('IS_EDITOR', current_user_can('edit_pages'));
	define('IS_AUTHOR', current_user_can('upload_files'));

	include_once("include/classes.php");
	include_once("include/functions.php");

	if(is_admin())
	{
		$my_settings_page = new MySettingsPage();
	}
}

add_action('init', 'init_base');

if(is_admin())
{
	register_activation_hook(__FILE__, 'activate_base');
	register_deactivation_hook(__FILE__, 'deactivate_base');

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

add_action('cron_base', 'run_cron_base');

load_plugin_textdomain('lang_base', false, dirname(plugin_basename(__FILE__)).'/lang/');

function activate_base()
{
	if(!wp_next_scheduled('cron_base'))
	{
		//Can be set later in settings when needed
		$setting_base_cron = get_option('setting_base_cron', 'hourly');

		do_log("Set cron for MF Base ".$setting_base_cron);

		wp_schedule_event(time(), $setting_base_cron, 'cron_base');
	}
}

function deactivate_base()
{
	wp_clear_scheduled_hook('cron_base');
}