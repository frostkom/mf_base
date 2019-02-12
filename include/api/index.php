<?php

if(!defined('ABSPATH'))
{
	header('Content-Type: application/json');

	$folder = str_replace("/wp-content/plugins/mf_base/include/api", "/", dirname(__FILE__));

	require_once($folder."wp-load.php");
}

$json_output = array();

$type = check_var('type', 'char');
$arr_input = explode("/", $type);

$type_action = $arr_input[0];
$type_action_type = isset($arr_input[1]) ? $arr_input[1] : '';
$type_class = isset($arr_input[2]) ? $arr_input[2] : '';

switch($type_action)
{
	case 'admin_base_profile':
		$json_output['admin_base_response'] = array(
			'template' => "admin_profile",
			'type' => "admin_profile_profile",
		);
	break;

	case 'my_ip':
		$json_output['ip'] = $_SERVER['REMOTE_ADDR'];

		$json_output['success'] = true;
	break;

	case 'import':
		if(get_current_user_id() > 0 && $type_action_type == "check")
		{
			$plugin_name = substr($type_class, 0, -7);

			include_once("../../".$plugin_name."/include/classes.php");
			include_once("../../".$plugin_name."/include/functions.php");

			$json_output['result'] = call_user_func(array($type_class, 'get_result'));

			$json_output['success'] = true;
		}
	break;
}

echo json_encode($json_output);