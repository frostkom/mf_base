<?php
/*
Plugin Name: MF Base
Plugin URI: http://github.com/frostkom/mf_base
Description: 
Version: 2.7.10
Author: Martin Fors
Author URI: http://frostkom.se
*/

include_once("include/classes.php");
include_once("include/functions.php");

add_filter('cron_schedules', 'schedules_base');

if(is_admin())
{
	new settings_page();
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

load_plugin_textdomain('lang_base', false, dirname(plugin_basename(__FILE__)).'/lang/');

function activate_base()
{
	set_cron('cron_base', 'setting_base_cron');
}

function deactivate_base()
{
	unset_cron('cron_base');
}