<?php
/*
Plugin Name: MF Base
Plugin URI: https://github.com/frostkom/mf_base
Description: 
Version: 8.3.12
Licence: GPLv2 or later
Author: Martin Fors
Author URI: http://frostkom.se
Text Domain: lang_base
Domain Path: /lang

GitHub Plugin URI: frostkom/mf_base
*/

include_once("include/classes.php");
include_once("include/functions.php");

if(is_admin())
{
	new settings_page();
}

$obj_base = new mf_base();

add_action('init', 'init_base', 0);
add_filter('cron_schedules', 'schedules_base');
add_action('cron_base', array($obj_base, 'run_cron_start'), 0);
add_action('cron_base', array($obj_base, 'run_cron_end'), 11);

if(is_admin())
{
	register_activation_hook(__FILE__, 'activate_base');
	register_deactivation_hook(__FILE__, 'deactivate_base');
	register_uninstall_hook(__FILE__, 'uninstall_base');

	add_action('admin_init', 'settings_base', 0);
	add_action('admin_init', array($obj_base, 'admin_init'), 0);

	add_filter('plugin_action_links', 'plugin_actions_base', 10, 2);
	add_filter('network_admin_plugin_action_links', 'plugin_actions_base', 10, 2);

	add_filter('media_buttons_context', 'add_shortcode_button_base');
	add_action('admin_footer', 'add_shortcode_display_base', 0);
	//add_filter('tiny_mce_before_init', 'extend_tiny_base');

	add_action('rwmb_meta_boxes', array($obj_base, 'meta_boxes'));
	add_action('rwmb_enqueue_scripts', 'meta_boxes_script_base');

	add_action('wp_ajax_check_notifications', 'check_notifications');

	//add_action('edit_form_after_title', 'after_title_base');
}

else
{
	add_action('login_init', array($obj_base, 'login_init'), 0);
	add_action('wp_head', array($obj_base, 'wp_head'), 0);
}

add_action('phpmailer_init', 'phpmailer_init_base');
add_shortcode('mf_file', array($obj_base, 'file_shortcode'));

load_plugin_textdomain('lang_base', false, dirname(plugin_basename(__FILE__)).'/lang/');

function activate_base()
{
	set_cron('cron_base', 'setting_base_cron');

	mf_uninstall_plugin(array(
		'options' => array('setting_base_info', 'setting_base_recommend', 'setting_base_external_links'),
	));
}

function deactivate_base()
{
	unset_cron('cron_base');
}

function uninstall_base()
{
	mf_uninstall_plugin(array(
		'options' => array('setting_base_auto_core_update', 'setting_base_auto_core_email', 'setting_base_info', 'setting_base_cron', 'option_cron_started', 'option_cron_run', 'setting_base_recommend'),
	));
}