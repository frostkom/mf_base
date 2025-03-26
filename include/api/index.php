<?php

if(!defined('ABSPATH'))
{
	header("Content-Type: application/json");
	header("Cache-Control: no-cache, must-revalidate");

	$folder = str_replace("/wp-content/plugins/mf_base/include/api", "/", dirname(__FILE__));

	require_once($folder."wp-load.php");
}

if(!isset($obj_base))
{
	$obj_base = new mf_base();
}

$json_output = array(
	'success' => false,
);

$type = check_var('type', 'char');

switch($type[0])
{
	case 'my_ip':
		$json_output['success'] = true;
		$json_output['ip'] = apply_filters('get_current_visitor_ip', $_SERVER['REMOTE_ADDR']);
	break;
}

echo json_encode($json_output);