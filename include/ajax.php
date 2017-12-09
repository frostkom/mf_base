<?php

if(!defined('ABSPATH'))
{
	header('Content-Type: application/json');

	$folder = str_replace("/wp-content/plugins/mf_base/include", "/", dirname(__FILE__));

	require_once($folder."wp-load.php");
}

$json_output = array();

$type = check_var('type', 'char');
$arr_input = explode("/", $type);

$type_action = $arr_input[0];
$type_action_type = $arr_input[1];
$type_class = $arr_input[2];

if(get_current_user_id() > 0)
{
	if($type_action == "import")
	{
		if($type_action_type == "check")
		{
			$plugin_name = substr($type_class, 0, -7);

			include_once("../../".$plugin_name."/include/classes.php");
			include_once("../../".$plugin_name."/include/functions.php");

			$json_output['result'] = call_user_func(array($type_class, 'get_result')); //, '__construct'

			//$obj_import->fetch_request();

			//$json_output['result'] = $obj_import->get_result();

			$json_output['success'] = true;
		}
	}
}

echo json_encode($json_output);