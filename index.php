<?php
/*
Plugin Name: MF Base
Plugin URI: https://github.com/frostkom/mf_base
Description: 
Version: 8.11.29
Licence: GPLv2 or later
Author: Martin Fors
Author URI: https://frostkom.se
Text Domain: lang_base
Domain Path: /lang

GitHub Plugin URI: frostkom/mf_base
*/

include_once("include/classes.php");
include_once("include/functions.php");

$obj_base = new mf_base();

add_action('init', array($obj_base, 'init'), 0);
add_filter('cron_schedules', array($obj_base, 'cron_schedules'));
add_action('cron_base', 'activate_base', mt_rand(1, 10));
add_action('cron_base', array($obj_base, 'run_cron_start'), 0);
add_action('cron_base', array($obj_base, 'run_cron_end'), 11);

if(is_admin())
{
	new settings_page();

	register_activation_hook(__FILE__, 'activate_base');
	register_deactivation_hook(__FILE__, 'deactivate_base');
	register_uninstall_hook(__FILE__, 'uninstall_base');

	add_action('wp_before_admin_bar_render', array($obj_base, 'wp_before_admin_bar_render'));

	add_action('admin_init', array($obj_base, 'settings_base'), 0);
	add_action('admin_init', array($obj_base, 'admin_init'), 0);

	add_filter('plugin_action_links', array($obj_base, 'plugin_action_links'), 10, 2);
	add_filter('network_admin_plugin_action_links', array($obj_base, 'plugin_action_links'), 10, 2);

	add_filter('media_buttons_context', array($obj_base, 'media_buttons_context'));
	add_action('admin_footer', array($obj_base, 'admin_footer'), 0);

	add_action('rwmb_meta_boxes', array($obj_base, 'rwmb_meta_boxes'));
	add_action('rwmb_enqueue_scripts', array($obj_base, 'rwmb_enqueue_scripts'));

	add_action('wp_ajax_check_notifications', array($obj_base, 'check_notifications'));
}

else
{
	add_action('login_init', array($obj_base, 'login_init'), 0);
	add_action('wp_head', array($obj_base, 'wp_head'), 0);
}

add_action('phpmailer_init', array($obj_base, 'phpmailer_init'));
add_shortcode('mf_file', array($obj_base, 'shortcode_file'));

add_filter('init_base_admin', array($obj_base, 'init_base_admin'));
add_filter('init_base_admin', array($obj_base, 'init_base_admin_2'), 11);

add_filter('get_page_templates', array($obj_base, 'get_page_templates'));
//add_filter('page_attributes_dropdown_pages_args', array($obj_base, 'wp_insert_post_data')); // if(version_compare(floatval(get_bloginfo('version')), '4.7', '<'))
add_filter('theme_page_templates', array($obj_base, 'theme_page_templates'));
add_filter('wp_insert_post_data', array($obj_base, 'wp_insert_post_data'));
add_filter('template_include', array($obj_base, 'template_include'));

load_plugin_textdomain('lang_base', false, dirname(plugin_basename(__FILE__)).'/lang/');

function activate_base()
{
	if(is_admin())
	{
		set_cron('cron_base', 'setting_base_cron');
	}

	mf_uninstall_plugin(array(
		'options' => array('setting_base_info', 'setting_base_recommend', 'setting_base_external_links', 'setting_base_exclude_sources'),
	));
}

function deactivate_base()
{
	global $wpdb;

	unset_cron('cron_base');

	$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->usermeta." WHERE meta_key LIKE %s", "%meta_table_filter_%"));
}

function uninstall_base()
{
	mf_uninstall_plugin(array(
		'uploads' => 'mf_base',
		'options' => array('setting_base_auto_core_update', 'setting_base_auto_core_email', 'setting_base_info', 'setting_base_cron', 'setting_base_front_end_admin', 'option_cron_started', 'option_cron_run', 'setting_base_recommend'),
	));
}