<?php
/*
Plugin Name: MF Base
Plugin URI: https://github.com/frostkom/mf_base
Description:
Version: 1.2.7.3
Licence: GPLv2 or later
Author: Martin Fors
Author URI: https://martinfors.se
Text Domain: lang_base
Domain Path: /lang

GitHub Plugin URI: frostkom/mf_base
*/

if(!defined('DISALLOW_FILE_EDIT'))
{
	define('DISALLOW_FILE_EDIT', true);
}

include_once("include/classes.php");
include_once("include/functions.php");

$obj_base = new mf_base();

add_action('init', array($obj_base, 'init'), 0);
add_filter('cron_schedules', array($obj_base, 'cron_schedules'));
add_action('cron_base', 'activate_base', mt_rand(1, 10));
add_action('cron_base', array($obj_base, 'run_cron_start'), 0);
add_action('cron_base', array($obj_base, 'cron_base'), mt_rand(1, 10));
add_action('cron_base', array($obj_base, 'run_cron_end'), 11);

add_action('wp_before_admin_bar_render', array($obj_base, 'wp_before_admin_bar_render'));

if(is_admin())
{
	new settings_page();

	register_activation_hook(__FILE__, 'activate_base');
	register_deactivation_hook(__FILE__, 'deactivate_base');
	register_uninstall_hook(__FILE__, 'uninstall_base');

	add_action('admin_init', array($obj_base, 'settings_base'), 0);
	add_action('admin_init', array($obj_base, 'admin_init'), 0);
	add_action('admin_menu', array($obj_base, 'admin_menu'));

	add_filter('pre_set_site_transient_update_plugins', array($obj_base, 'pre_set_site_transient_update_plugins'), 10, 1);

	add_filter('plugin_action_links', array($obj_base, 'plugin_action_links'), 10, 2);
	add_filter('network_admin_plugin_action_links', array($obj_base, 'plugin_action_links'), 10, 2);

	add_filter('manage_page_posts_columns', array($obj_base, 'column_header'), 5);
	add_action('manage_page_posts_custom_column', array($obj_base, 'column_cell'), 5, 2);
	add_filter('manage_post_posts_columns', array($obj_base, 'column_header'), 5);
	add_action('manage_post_posts_custom_column', array($obj_base, 'column_cell'), 5, 2);

	add_action('rwmb_meta_boxes', array($obj_base, 'rwmb_meta_boxes'));
	add_action('rwmb_enqueue_scripts', array($obj_base, 'rwmb_enqueue_scripts'));

	add_action('wp_ajax_api_base_notifications', array($obj_base, 'api_base_notifications'));
}

else
{
	add_filter('wp_sitemaps_posts_query_args', array($obj_base, 'wp_sitemaps_posts_query_args'), 10, 2);
	add_filter('wp_sitemaps_taxonomies', array($obj_base, 'wp_sitemaps_taxonomies'));

	add_action('login_init', array($obj_base, 'login_init'), 0);
	add_action('wp_head', array($obj_base, 'wp_head'), 0);
}

add_filter('xmlrpc_enabled', '__return_false');
remove_action('wp_head', 'wp_generator'); // Remove WP versions

add_filter('get_current_visitor_ip', array($obj_base, 'get_current_visitor_ip'), 10);
add_filter('has_comments', array($obj_base, 'has_comments'), 10);
add_filter('filter_meta_input', array($obj_base, 'filter_meta_input'), 10);

add_filter('get_page_from_block_code', array($obj_base, 'get_page_from_block_code'), 10, 2);

add_action('wp_ajax_api_base_info', array($obj_base, 'api_base_info'));
add_action('wp_ajax_api_base_cron', array($obj_base, 'api_base_cron'));
add_action('wp_ajax_api_base_optimize', array($obj_base, 'api_base_optimize'));

/*$setting_base_automatic_updates = get_site_option_or_default('setting_base_automatic_updates', array());

if(!in_array('core', $setting_base_automatic_updates) && !has_filter('auto_update_core', '__return_false'))
{
	add_filter('auto_update_core', '__return_false');
}

if(!in_array('theme', $setting_base_automatic_updates) && !has_filter('auto_update_theme', '__return_false'))
{
	add_filter('auto_update_theme', '__return_false');
}

if(!in_array('plugin', $setting_base_automatic_updates) && !has_filter('auto_update_plugin', '__return_false'))
{
	add_filter('auto_update_plugin', '__return_false');
}*/

add_filter('auto_update_plugin', '__return_false');

add_action('phpmailer_init', array($obj_base, 'phpmailer_init'));

add_shortcode('mf_file', array($obj_base, 'shortcode_file'));

add_filter('theme_page_templates', array($obj_base, 'theme_page_templates'));
//add_filter('page_attributes_dropdown_pages_args', array($obj_base, 'wp_insert_post_data')); // if(version_compare(floatval(get_bloginfo('version')), '4.7', '<'))
add_filter('wp_insert_post_data', array($obj_base, 'wp_insert_post_data'));
add_filter('template_include', array($obj_base, 'template_include'));

add_filter('recommend_config', array($obj_base, 'recommend_config'));

add_filter('get_block_search', array($obj_base, 'get_block_search'), 10, 2);

function activate_base()
{
	global $obj_base;

	if(!isset($obj_base))
	{
		$obj_base = new mf_base();
	}

	if(is_admin())
	{
		set_cron('cron_base', 'setting_base_cron');
	}
}

function deactivate_base()
{
	global $wpdb;

	unset_cron('cron_base');

	$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->usermeta." WHERE meta_key LIKE %s", "%meta_table_filter_%"));
}

function uninstall_base()
{
	include_once("include/classes.php");

	$obj_base = new mf_base();

	mf_uninstall_plugin(array(
		'uploads' => $obj_base->post_type,
		'options' => array('setting_base_info', 'setting_base_cron', 'setting_base_update_htaccess', 'setting_base_prefer_www', 'setting_theme_enable_wp_api', 'setting_base_enable_wp_api', 'setting_base_automatic_updates', 'setting_base_template_site', 'setting_base_recommend', 'option_cron_started', 'option_cron_progress', 'option_cron_ended', 'option_base_ftp_size', 'option_base_ftp_size_folders', 'option_base_db_size', 'option_base_large_tables', 'setting_base_optimize', 'option_base_optimized', 'option_git_updater', 'setting_base_use_timezone', 'option_github_updates'),
		'meta' => array($obj_base->meta_prefix.'page_index'),
	));
}