<?php

if(!defined('ABSPATH'))
{
	header("Content-Type: application/json");

	$folder = str_replace("/wp-content/plugins/mf_base/include/api", "/", dirname(__FILE__));

	require_once($folder."wp-load.php");
}

do_action('run_cache', array('suffix' => 'json'));

$json_output = array(
	'success' => false,
);

$type = check_var('type', 'char');
$arr_input = explode("/", $type);

$type_action = $arr_input[0];
$type_action_type = isset($arr_input[1]) ? $arr_input[1] : '';
$type_class = isset($arr_input[2]) ? $arr_input[2] : '';

switch($type_action)
{
	case 'my_ip':
		$json_output['ip'] = $_SERVER['REMOTE_ADDR'];

		$json_output['success'] = true;
	break;

	/*case 'import':
		if($type_action_type == "check" && is_user_logged_in())
		{
			$plugin_name = substr($type_class, 0, -7);

			include_once("../../".$plugin_name."/include/classes.php");
			include_once("../../".$plugin_name."/include/functions.php");

			$json_output['success'] = true;
			$json_output['result'] = call_user_func(array($type_class, 'get_result'));
		}
	break;*/

	case 'sync':
		$json_output['success'] = true;

		$remote_site_url = check_var('site_url');
		$remote_site_name = check_var('site_name');

		if($remote_site_url != '' && $remote_site_name != '')
		{
			$option_sync_sites = get_option('option_sync_sites', array());

			$option_sync_sites[$remote_site_url] = array(
				'name' => $remote_site_name,
				'datetime' => date("Y-m-d H:i:s"),
				'ip' => $_SERVER['REMOTE_ADDR'],
			);

			update_option('option_sync_sites', $option_sync_sites, 'no');
		}

		//do_log("Sync: ".var_export($_REQUEST, true)." -> ".var_export(get_option('option_sync_sites'), true));

		$json_output = apply_filters('api_sync', $json_output, array('remote_site_url' => $remote_site_url));
	break;
}

echo json_encode($json_output);