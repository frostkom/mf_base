<?php

if(!defined('ABSPATH'))
{
	header("Content-Type: application/json");
	header("Cache-Control: no-cache, must-revalidate");

	$folder = str_replace("/wp-content/plugins/mf_base/include/api", "/", dirname(__FILE__));

	require_once($folder."wp-load.php");
}

//do_action('run_cache', array('suffix' => 'json'));

if(!isset($obj_base))
{
	$obj_base = new mf_base();
}

$json_output = array(
	'success' => false,
);

$type = check_var('type', 'char');
$arr_input = explode("/", $type);

$type_action = $arr_input[0];
$type_action_type = (isset($arr_input[1]) ? $arr_input[1] : '');
$type_class = (isset($arr_input[2]) ? $arr_input[2] : '');

switch($type_action)
{
	case 'get_base_info':
		if(is_user_logged_in())
		{
			ob_start();

			$obj_base->get_base_info();

			$json_output['success'] = true;
			$json_output['html'] = ob_get_clean();
			$json_output['timestamp'] = date("Y-m-d H:i:s");
		}

		else
		{
			$json_output['message'] = __("You have to be logged in to access this", 'lang_base');
		}
	break;

	case 'get_base_cron':
		if(is_user_logged_in())
		{
			ob_start();

			$obj_base->get_base_cron();

			$json_output['success'] = true;
			$json_output['html'] = ob_get_clean();
			$json_output['timestamp'] = date("Y-m-d H:i:s");
		}

		else
		{
			$json_output['message'] = __("You have to be logged in to access this", 'lang_base');
		}
	break;

	case 'my_ip':
		$json_output['success'] = true;
		$json_output['ip'] = get_current_visitor_ip();
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
				'ip' => get_current_visitor_ip(),
			);

			update_option('option_sync_sites', $option_sync_sites, 'no');
		}

		$json_output = apply_filters('api_sync', $json_output, array('remote_site_url' => $remote_site_url));
	break;
}

echo json_encode($json_output);