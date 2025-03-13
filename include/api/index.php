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
$arr_input = explode("/", $type);

switch($arr_input[0])
{
	case 'my_ip':
		$json_output['success'] = true;
		$json_output['ip'] = get_current_visitor_ip();
	break;

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

			update_option('option_sync_sites', $option_sync_sites, false);
		}

		$json_output = apply_filters('api_sync', $json_output, array('remote_site_url' => $remote_site_url));
	break;
}

echo json_encode($json_output);