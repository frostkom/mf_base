<?php

if(!defined('ABSPATH'))
{
	header('Content-Type: application/json');

	$folder = str_replace("/wp-content/plugins/mf_base/include/api", "/", dirname(__FILE__));

	require_once($folder."wp-load.php");
}

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
	case 'admin':
		switch($type_action_type)
		{
			case 'profile':
				$user_id = get_current_user_id();

				$arr_fields = array();

				$arr_fields[] = array('type' => 'flex_start');
					$arr_fields[] = array('type' => 'text', 'name' => 'first_name', 'text' => __("First Name", 'lang_base'), 'required' => true);
					$arr_fields[] = array('type' => 'text', 'name' => 'last_name', 'text' => __("Last Name", 'lang_base'), 'required' => true);
				$arr_fields[] = array('type' => 'flex_end');
				$arr_fields[] = array('type' => 'email', 'name' => 'email', 'text' => __("E-mail", 'lang_base'), 'required' => true);

				switch($type_class)
				{
					case 'edit':
						foreach($arr_fields as $key => $value)
						{
							if(isset($value['name']))
							{
								$arr_fields[$key]['value'] = get_the_author_meta($value['name'], $user_id);
							}
						}

						$json_output['admin_response'] = array(
							'template' => str_replace("/", "_", $type),
							'container' => str_replace("/", "_", $type),
							'fields' => $arr_fields,
						);
					break;

					case 'save':
						$updated = false;

						foreach($arr_fields as $key => $value)
						{
							if(isset($value['name']))
							{
								$user_meta = check_var($value['name']);

								if($user_meta != '' || $value['required'] == false)
								{
									$meta_id = update_user_meta($user_id, $value['name'], $user_meta);

									if($meta_id > 0)
									{
										$updated = true;
									}
								}
							}
						}

						if($updated == true)
						{
							$json_output['success'] = true;
							$json_output['message'] = __("I have saved the information for you", 'lang_base');
						}

						else
						{
							$json_output['message'] = __("I could not update the information for you", 'lang_base');
						}
					break;
				}
			break;
		}
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

			$json_output['success'] = true;
			$json_output['result'] = call_user_func(array($type_class, 'get_result'));
		}
	break;
}

echo json_encode($json_output);