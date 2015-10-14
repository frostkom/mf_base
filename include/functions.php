<?php

function mf_get_post_content($id)
{
	global $wpdb;

	return $wpdb->get_var($wpdb->prepare("SELECT post_content FROM ".$wpdb->posts." WHERE ID = '%d'", $id));
}

function disable_action_base($actions, $plugin_file, $plugin_data, $context)
{
	// Remove edit link for all
	/*if(array_key_exists('edit', $actions))
	{
		unset($actions['edit']);
	}*/

	if(array_key_exists('deactivate', $actions) && in_array($plugin_file, array('mf_base/index.php')))
	{
		unset($actions['deactivate']);
	}

	return $actions;
}


function add_action_base($links)
{
	$links[] = "<a href='".admin_url('options-general.php?page=settings_mf_base#settings_base')."'>".__("Settings", 'lang_base')."</a>";

	return $links;
}

function get_install_link_tags($require_url, $required_name)
{
	$a_start = "<a href='".($require_url != '' ? $require_url : get_site_url()."/wp-admin".(is_multisite() ? "/network" : "")."/plugin-install.php?tab=search&s=".$required_name)."'>";
	$a_end = "</a>";

	return array($a_start, $a_end);
}

function require_plugin($required_path, $required_name, $require_url = "")
{
	if(!is_plugin_active($required_path))
	{
		list($a_start, $a_end) = get_install_link_tags($require_url, $required_name);

		mf_trigger_error(sprintf(__("You need to install the plugin %s%s%s first", 'lang_base'), $a_start, $required_name, $a_end), E_USER_ERROR);
	}
}

class recommend_plugin
{
	function recommend_plugin($required_path, $required_name, $require_url = "")
	{
		global $pagenow;

		if($pagenow == 'plugins.php' && !is_plugin_active($required_path))
		{
			list($a_start, $a_end) = get_install_link_tags($require_url, $required_name);

			$this->message = sprintf(__("We highly recommend that you install %s%s%s aswell", 'lang_base'), $a_start, $required_name, $a_end);

			add_action('network_admin_notices', array($this, 'show_notice'));
			add_action('admin_notices', array($this, 'show_notice'));
		}
	}

	function show_notice()
	{
		global $notice_text;

		$notice_text = $this->message;

		echo get_notification();
	}
}

function mf_trigger_error($message, $errno)
{
	if(isset($_GET['action']) && $_GET['action'] == 'error_scrape')
	{
		echo $message;
	}

	else
	{
		trigger_error($message, $errno);
	}
}

function get_site_language($data)
{
	if(!isset($data['type'])){	$data['type'] = "";}
	if(!isset($data['uc'])){	$data['uc'] = true;}

	$arr_language = explode("_", $data['language']);

	if($data['type'] == "first")
	{
		$out = $arr_language[0];

		if($data['uc'] == true)
		{
			$out = strtoupper($out);
		}
	}

	else if($data['type'] == "last")
	{
		$out = $arr_language[1];

		if($data['uc'] == true)
		{
			$out = strtoupper($out);
		}
	}

	else
	{
		$out = $data['language'];
	}

	return $out;
}

function get_current_user_role($id = 0)
{
	if(!($id > 0))
	{
		$id = get_current_user_id();
	}

	$user_info = get_userdata($id);

	return $user_info->roles[0];
}

function settings_base()
{
	$options_page = "settings_mf_base";
	$options_area = "setting_base";

	if(current_user_can("update_core"))
	{
		add_settings_section(
			$options_area,
			__("Basic info", 'lang_base'),
			$options_area."_callback",
			$options_page
		);

		$arr_settings = array(
			"setting_base_info" => __("Versions", 'lang_base'),
			"setting_base_auto_core_email" => __("Update notification", 'lang_base'),
		);

		foreach($arr_settings as $handle => $text)
		{
			add_settings_field($handle, $text, $handle."_callback", $options_page, $options_area);

			register_setting($options_page, $handle);
		}
	}
}

function setting_base_callback()
{
	echo "<div id='settings_base'></div>";
}

function setting_base_info_callback()
{
	$php_version = explode("-", phpversion())[0];
	$mysql_version = explode("-", @mysql_get_server_info())[0];

	$php_required = "5.2.4";
	$mysql_required = "5.0";

	echo "<p><i class='fa ".($php_version > $php_required ? "fa-check green" : "fa-close red")."'></i> PHP: ".$php_version."</p>
	<p><i class='fa ".($mysql_version > $mysql_required ? "fa-check green" : "fa-close red")."'></i> MySQL: ".$mysql_version."</p>
	<p><a href='//wordpress.org/about/requirements/'>".__("Requirements", 'lang_base')."</a></p>";
}

function setting_base_auto_core_email_callback()
{
	$option = get_option('setting_base_auto_core_email');

	$arr_data = array();

	$arr_data[] = array('no', __("No", 'lang_base'));
	$arr_data[] = array('yes', __("Yes", 'lang_base'));

	echo show_select(array('data' => $arr_data, 'name' => 'setting_base_auto_core_email', 'compare' => $option))
	."<span class='description'>".__("Send e-mail to admin after auto core update", 'lang_base')."</span>";
}

function mf_enqueue_script($handle, $file = "", $translation = array())
{
	if(count($translation) > 0)
	{
		wp_register_script($handle, $file, array('jquery'), '1.0', true);
		wp_localize_script($handle, $handle, $translation);
		wp_enqueue_script($handle);
	}

	else if($file != '')
	{
		wp_enqueue_script($handle, $file, array('jquery'), '1.0', true);
	}

	else
	{
		wp_enqueue_script($handle);
	}
}

function get_all_roles($data = array())
{
	global $wp_roles;

	if(!isset($wp_roles))
	{
		$wp_roles = new WP_Roles();
	}

	$roles = $wp_roles->get_names();

	if(isset($data['allowed']))
	{
		if(!is_array($data['allowed']))
		{
			$data['allowed'] = array($data['allowed']);
		}

		foreach($roles as $key => $value)
		{
			if(!in_array($key, $data['allowed']))
			{
				unset($roles[$key]);
			}
		}
	}

	if(isset($data['denied']))
	{
		if(!is_array($data['denied']))
		{
			$data['denied'] = array($data['denied']);
		}

		foreach($roles as $key => $value)
		{
			if(in_array($key, $data['denied']))
			{
				unset($roles[$key]);
			}
		}
	}

	return $roles;
}

function get_role_first_capability($role)
{
	global $wp_roles;

	//echo var_export($wp_roles->roles[$role]['capabilities'], true);

	$capabilities = $wp_roles->roles[$role]['capabilities'];
	$cap_keys = array_keys($capabilities);

	return $cap_keys[0];
	//return $role;
}

//Sortera array
#########################
# array		array(array("firstname" => "Martin", "surname" => "Fors"))
# on		Ex. surname
# order		asc/desc
#########################
function array_sort($data)
{
	if(!isset($data['order'])){		$data['order'] = "asc";}

	$array = $data['array'];
	$on = $data['on'];
	$order = $data['order'];

	$new_array = array();
	$sortable_array = array();

	if(count($array) > 0)
	{
		foreach($array as $k => $v)
		{
			if(is_array($v))
			{
				foreach($v as $k2 => $v2)
				{
					if($k2 == $on)
					{
						$sortable_array[$k] = $v2;
					}
				}
			}

			else
			{
				$sortable_array[$k] = $v;
			}
		}

		switch($order)
		{
			case "asc":
				asort($sortable_array);
			break;

			case "desc":
				arsort($sortable_array);
			break;
		}

		//This changes the index...to keep index but change order use $array as output instead
		foreach($sortable_array as $k => $v)
		{
			$new_array[] = $array[$k];
		}
	}

	return $new_array;
}
#########################

#################
function validate_url($value, $link = true, $http = true)
{
	if($link == true)
	{
		$exkludera = array("&", " ", "amp;amp;", 'Å', 'å', 'Ä', 'ä', 'Ö', 'ö', 'é');
		$inkludera = array("&amp;", "%20", "amp;", '%C5', '%E5', '%C4', '%E4', '%D6', '%F6', '%E9');
		$value = str_replace($exkludera, $inkludera, $value);
	}

	if($http == true && $value != '' && substr($value, 0, 1) != "/")
	{
		$arr_prefix = array('http:', 'https:', 'ftp:', 'mms:');

		if(!preg_match('/('. implode('|', $arr_prefix) .')/', $value))
		{
			$value = "http://".$value;
		}
	}

	return $value;
}
#################

function get_url_content($url, $catch_head = false, $password = "", $post = "", $post_data = array())
{
	//Replace with this?
	/*$url = 'http://api.example.com/v1/users';

    $args = array(
		'headers' => array(
			'token' => 'example_token'
		),
    );

    $out = wp_remote_get($url, $args);*/

	$url = validate_url($url, false);

	$ch = curl_init();
	$timeout = 5;

	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; sv-SE; rv:1.9.2.3) Gecko/20100401 Firefox/3.6.10");

	if(ini_get('open_basedir') == '' && ini_get('safe_mode') == 'Off')
	{
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		//curl_setopt($ch, CURLOPT_MAXREDIRS, $mr);
	}

	if($password != '')
	{
		curl_setopt($ch, CURLOPT_USERPWD, $password);
	}

	if($post != '')
	{
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, "status=".$post);
	}

	else if(count($post_data) > 0)
	{
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
	}

	$content = curl_exec($ch);

	/*if($output === false)
	{
		insert_error("cURL Error: ".curl_error($ch));
	}*/

	if($catch_head == true)
	{
		$headers = curl_getinfo($ch);

		$return_value = array($content, $headers);
	}

	else
	{
		$return_value = $content;
	}

	curl_close($ch);

	return $return_value;
}

function get_notification()
{
	global $error_text, $notice_text, $done_text;

	$out = "";

	if(isset($error_text) && $error_text != '')
	{
		$out .= "<div class='error'>
			<p>".$error_text."</p>
		</div>";

		$error_text = "";
	}

	if(isset($notice_text) && $notice_text != '')
	{
		$out .= "<div class='update-nag'>".$notice_text."</div>";

		$notice_text = "";
	}

	if(isset($done_text) && $done_text != '')
	{
		$out .= "<div class='updated'>
			<p>".$done_text."</p>
		</div>";

		$done_text = "";
	}

	return $out;
}

function get_list_navigation($resultPagination)
{
	global $wpdb, $intLimitAmount, $strSearch;

	$out = "";

	$rowsPagination = $wpdb->num_rows;

	if($rowsPagination > $intLimitAmount || $strSearch != '')
	{
		$out .= "<form method='post' action='".preg_replace("/\&paged\=\d+/", "", $_SERVER['REQUEST_URI'])."'>
			<p class='search-box'>
				<input type='search' name='s' value='".$strSearch."'>
				<button type='submit' class='button'>".__("Search", 'lang_base')."</button>
			</p>
		</form>";
	}

	if($rowsPagination > 0)
	{
		$pagination_obj = new pagination();

		$out .= $pagination_obj->show(array('result' => $resultPagination));
	}

	return $out;
}

function add_columns($array)
{
	global $wpdb;

	foreach($array as $table => $arr_col)
	{
		foreach($arr_col as $column => $value)
		{
			$result = $wpdb->get_results("SHOW COLUMNS FROM ".$table." WHERE Field = '".$column."'");

			if($wpdb->num_rows == 0)
			{
				$value = str_replace("[table]", $table, $value);
				$value = str_replace("[column]", $column, $value);

				$wpdb->query($value);
			}
		}
	}
}

function update_columns($array)
{
	global $wpdb;

	foreach($array as $table => $arr_col)
	{
		foreach($arr_col as $column => $value)
		{
			$result = $wpdb->get_results("SHOW COLUMNS FROM ".$table." WHERE Field = '".$column."'");

			if($wpdb->num_rows > 0)
			{
				$value = str_replace("[table]", $table, $value);
				$value = str_replace("[column]", $column, $value);

				$wpdb->query($value);
			}
		}
	}
}

function run_queries($array)
{
	global $wpdb;

	foreach($array as $value)
	{
		$wpdb->query($value);
	}
}

function mf_redirect($location)
{
	if(headers_sent() == true)
	{
		echo "<form name='reload' action='".$location."' method='post'></form>
		<script>document.reload.submit();</script>";
	}

	else
	{
		header("Location: ".$location);
	}

	exit;
}

if(!function_exists('wp_date_format'))
{
	function wp_date_format($data)
	{
		global $wpdb;

		if(!isset($data['full_datetime'])){		$data['full_datetime'] = false;}

		$date_format = $wpdb->get_var("SELECT option_value FROM ".$wpdb->options." WHERE option_name = '".($data['full_datetime'] == true ? "links_updated_date_format" : "date_format")."'");

		return date($date_format, strtotime($data['date']));
	}
}

function check_var($in, $type = '', $v2 = true, $default = '', $return_empty = false, $force_req_type = '')
{
	$out = $temp = "";

	if($v2 == true)
	{
		$type2 = substr($in, 0, 3);

		if(isset($_SESSION[$in]) && ($force_req_type == "" || $force_req_type == "session"))
		{
			$temp = $_SESSION[$in] != '' ? $_SESSION[$in] : "";
		}

		else if(isset($_POST[$in]) && substr($in, 0, 3) != "ses" && ($force_req_type == "" || $force_req_type == "post"))
		{
			$temp = $_POST[$in] != '' ? $_POST[$in] : "";
		}

		else if(isset($_GET[$in]) && substr($in, 0, 3) != "ses" && ($force_req_type == "" || $force_req_type == "get"))
		{
			$temp = $_GET[$in] != '' ? $_GET[$in] : "";
		}
	}

	else
	{
		$type2 = "";
		$temp = $in;
	}

	if($type == 'raw')
	{
		$out = $temp;
	}

	else if($type == 'telno' || $type2 == 'tel')
	{
		$temp = trim($temp);

		if($temp == '' || preg_match('/^([-+\d()\s]+)$/', $temp))
		{
			$out = str_replace(" ", "", $temp);
		}

		else
		{
			if($return_empty == false){$out = $temp;}
			//$arrErrorField[] = $in;
		}
	}

	else if($type == 'soc' || $type2 == 'soc')
	{
		$temp = trim($temp);
		$temp = str_replace(array("-", " "), "", $temp);

		if(strlen($temp) == 12)
		{
			$temp = substr($temp, 2);
		}

		if($temp == '' || strlen($temp) == 10 && preg_match('/^([-\d\s]+)$/', $temp))
		{
			$out = $temp;
		}

		else
		{
			if($return_empty == false){$out = $temp;}
			//$arrErrorField[] = $in;
		}
	}

	else if($type == 'soc2' || $type2 == 'soc2')
	{
		$temp = trim($temp);
		$temp = str_replace(array("-", " "), "", $temp);

		if($temp == '' || strlen($temp) == 12 && preg_match('/^([-\d\s]+)$/', $temp))
		{
			$out = $temp;
		}

		else
		{
			if($return_empty == false){$out = $temp;}
			//$arrErrorField[] = $in;
		}
	}

	else if($type == 'email' || $type2 == 'eml')
	{
		$temp = trim($temp);

		if($temp == '' || preg_match('/^[-A-Za-z\d_.]+[@][A-Za-z\d_-]+([.][A-Za-z\d_-]+)*[.][A-Za-z]{2,8}$/', $temp))
		{
			$out = $temp;
		}

		else
		{
			if($return_empty == false){$out = $temp;}
			//$arrErrorField[] = $in;
		}
	}

	else if($type == 'url' || $type2 == 'url')
	{
		$temp = trim($temp);

		if($temp == '' || preg_match('/([-a-zA-Z\d_]+\.)*[-a-zA-Z\d_]+\.[-a-zA-Z\d_]{2,6}/', $temp))
		{
			$out = $temp;
		}

		else
		{
			if($return_empty == false){$out = $temp;}
			//$arrErrorField[] = $in;
		}
	}

	else if($type == 'date' || $type == 'shortDate' || $type == 'shortDate2' || $type2 == 'dte')
	{
		if($type == 'shortDate')
		{
			if($temp == '' || (preg_match('/^\d{4}-\d{2}$/', $temp) && substr($temp, 0, 4) > 1970 && substr($temp, 0, 4) < 2038))
			{
				$out = $temp;
			}

			else
			{
				if($temp == "0000-00")
				{
					$out = "";
				}

				else
				{
					if($return_empty == false){$out = trim($temp);}
					//$arrErrorField[] = $in;
				}
			}
		}

		else if($type == 'shortDate2') //Används av formulär för Securitas
		{
			if($temp == '' || preg_match('/^\d{6}$/', $temp))
			{
				$out = $temp;
			}

			else
			{
				if($temp == "000000")
				{
					$out = "";
				}

				else
				{
					if($return_empty == false){$out = trim($temp);}
					//$arrErrorField[] = $in;
				}
			}
		}

		else
		{
			if($temp == '' || (preg_match('/^\d{4}-\d{2}-\d{2}$/', $temp) && substr($temp, 0, 4) > 1970 && substr($temp, 0, 4) < 2038))
			{
				$out = $temp;
			}

			else
			{
				if($temp == "0000-00-00")
				{
					$out = "";
				}

				else
				{
					if($return_empty == false){$out = trim($temp);}
					//$arrErrorField[] = $in;
				}
			}
		}
	}

	else if(is_array($temp) || $type == 'array' || $type2 == 'arr')
	{
		if(is_array($temp) || $temp == '')
		{
			$out = $temp; //Får aldrig köras addslashes() på detta
		}
	}

	else if($type == 'char' || $type2 == 'str')
	{
		$out = trim(addslashes($temp));

		$out_temp = htmlspecialchars($out);

		if($out != '' && $out_temp != $out)
		{
			$out = $out_temp;
		}
	}

	else if($type == 'float' || $type2 == 'dbl') //is_numeric()
	{
		if($temp == strval(floatval($temp)) || $temp == '')
		{
			$out = $temp;
		}

		else
		{
			if($return_empty == false){$out = trim(trim($temp), "&nbsp;");}
			//$arrErrorField[] = $in;
		}
	}

	else if($type == 'int' || $type2 == 'int' || $type == 'zip' || $type2 == 'zip')
	{
		$temp = str_replace(" ", "", $temp);

		if($temp == strval(intval($temp)) || $temp == '')
		{
			$out = $temp;
		}

		else
		{
			if($return_empty == false){$out = trim($temp);}
			//$arrErrorField[] = $in;
		}
	}

	if($out == '')
	{
		$out = $default;
	}

	return $out;
}

######################
function show_textfield($data)
{
	$arr_number_types = array('int', 'float');

	if(isset($data['type']) && in_array($data['type'], $arr_number_types))
	{
		$data['type'] = "number";
	}

	$arr_accepted_types = array('text', 'email', 'url', 'date', 'number', 'range');

	if(!isset($data['type']) || !in_array($data['type'], $arr_accepted_types)){	$data['type'] = "text";}
	if(!isset($data['text'])){			$data['text'] = "";}
	if(!isset($data['value'])){			$data['value'] = "";}
	if(!isset($data['maxlength'])){		$data['maxlength'] = "";}
	if(!isset($data['size'])){			$data['size'] = 0;}
	if(!isset($data['required'])){		$data['required'] = 0;}
	if(!isset($data['placeholder'])){	$data['placeholder'] = "";}
	if(!isset($data['pattern'])){		$data['pattern'] = "";}
	if(!isset($data['xtra'])){			$data['xtra'] = "";}
	if(!isset($data['xtra_class'])){	$data['xtra_class'] = "";}
	if(!isset($data['datalist'])){		$data['datalist'] = array();}

	$label = $after = $color = "";

	/*if($data['type'] == "range")
	{
		$after .= " (<span>".$data['value']."</span>)";
	}*/

	if($data['value'] == "0000-00-00"){$data['value'] = "";}

	if($data['required'] == 1)
	{
		//$after .= " *";
		$data['xtra'] .= " required";
	}

	if($data['size'] > 0)
	{
		$data['xtra'] .= " size='".$data['size']."'";
	}

	if($data['maxlength'] > 0)
	{
		$data['xtra'] .= " maxlength='".$data['maxlength']."'";
	}

	if($data['placeholder'] != '')
	{
		$data['xtra'] .= " placeholder='".$data['placeholder']."...'";
	}

	if($data['pattern'] != '')
	{
		$data['xtra'] .= " pattern='".$data['pattern']."'";
	}

	if($data['type'] == "email" || $data['type'] == "url")
	{
		$data['xtra'] .= " autocorrect='off' autocapitalize='off'";
	}

	else if($data['type'] == "number")
	{
		$data['xtra'] .= " step='any'";
	}

	/*if(count($arrErrorField) > 0 && preg_match('/('. implode('|', $arrErrorField) .')/', $data['name']))
	{
		$data['xtra_class'] .= " red_border";
	}*/

	if($data['text'] != '')
	{
		$label = "<label for='".$data['name']."'>".$data['text'].$after."</label>";
	}

	$count_temp = count($data['datalist']);

	if($count_temp > 0)
	{
		$data['xtra'] .= " list='".$data['name']."_list'";
	}

	$out = "<div class='form_textfield".($data['xtra_class'] != '' ? " ".$data['xtra_class'] : "")."'>"
		.$label
		."<input type='".$data['type']."' name='".$data['name']."' value='".$data['value']."'".$data['xtra'].">";

		if($count_temp > 0)
		{
			$out .= "<datalist id='".$data['name']."_list'>";

				for($i = 0; $i < $count_temp; $i++)
				{
					$out .= "<option value='".$data['datalist'][$i]."'>";
				}

			$out .= "</datalist>";
		}

	$out .= "</div>"; //stripslashes($data['value'])

	return $out;
}
#################

######################################
function show_textarea($data)
{
	if(!isset($data['text'])){			$data['text'] = "";}
	if(!isset($data['value'])){			$data['value'] = "";}
	if(!isset($data['xtra'])){			$data['xtra'] = "";}
	if(!isset($data['class'])){			$data['class'] = "";}
	if(!isset($data['placeholder'])){	$data['placeholder'] = "";}
	if(!isset($data['required'])){		$data['required'] = 0;}
	if(!isset($data['wysiwyg'])){		$data['wysiwyg'] = false;}

	if($data['required'] == 1){		$data['xtra'] .= " required";}

	if($data['placeholder'] != '')
	{
		$data['placeholder'] .= "...";

		$data['xtra'] .= " placeholder='".$data['placeholder']."'";
	}

	$out = "<div class='form_textarea".($data['class'] != '' ? " ".$data['class'] : "")."'>";

		if($data['text'] != '')
		{
			$out .= "<label for='".$data['name']."'>".$data['text']."</label>"; //.($data['required'] == 1 ? " *" : "")
		}

		if($data['wysiwyg'] == true)
		{
			$settings = array(
				//'media_buttons' => false,
				'textarea_rows' => 5
			);

			$out .= wp_editor(stripslashes($data['value']), $data['name'], $settings);
		}

		else
		{
			$out .= "<textarea name='".$data['name']."' id='".$data['name']."'".($data['xtra'] != '' ? " ".$data['xtra'] : "").">".stripslashes($data['value'])."</textarea>";
		}

	$out .= "</div>";

	return $out;
}
#################

############################
function show_select($data)
{
	if(!isset($data['compare'])){		$data['compare'] = "";}
	if(!isset($data['xtra'])){			$data['xtra'] = "";}
	if(!isset($data['text'])){			$data['text'] = "";}
	if(!isset($data['maxsize'])){		$data['maxsize'] = 10;}
	if(!isset($data['required'])){		$data['required'] = 0;}
	if(!isset($data['class'])){			$data['class'] = "";}
	if(!isset($data['description'])){	$data['description'] = "";}

	if(isset($data['data']) && $data['data'] != '')
	{
		$label = "";

		$count_temp = count($data['data']);

		$is_multiple = preg_match('/(\[\])/', $data['name']);

		if($is_multiple)
		{
			$data['class'] .= ($data['class'] != '' ? " " : "")."top";
			$data['xtra'] .= " multiple='multiple' size='".($count_temp > $data['maxsize'] ? $data['maxsize'] : $count_temp)."'";

			$container_class = "form_select_multiple";
		}

		else
		{
			$container_class = "form_select";
		}

		if($data['text'] != '')
		{
			$label = "<label for='".$data['name']."'>".$data['text']."</label>";
		}

		if($count_temp == 1 && $data['required'] == 1 && $data['text'] != '')
		{
			$out = input_hidden(array('name' => $data['name'], 'value' => $data['data'][0][0]));
		}

		else
		{
			$out = "<div class='".$container_class.($data['class'] != '' ? " ".$data['class'] : "")."'>"
				.$label
				."<select id='".str_replace("[]", "", $data['name'])."' name='".$data['name']."'".$data['xtra'].">";

					for($i = 0; $i < $count_temp; $i++)
					{
						$data_value = $data['data'][$i][0];
						$data_text = $data['data'][$i][1];

						if($data_value."" == "opt_start")
						{
							$out .= "<optgroup label='".$data_text."'>";
						}

						else if($data_value."" == "opt_end")
						{
							$out .= "</optgroup>";
						}

						else
						{
							$out .= "<option value='".$data_value."'";

								if(is_array($data['compare']) && in_array($data_value, $data['compare']) || $data['compare'] == $data_value)
								{
									$out .= " selected";
								}

							$out .= ">".$data_text."</option>";
						}
					}

				$out .= "</select>";

				if($data['description'] != '')
				{
					$out .= "<p class='description'>".$data['description']."</p>";
				}

			$out .= "</div>";
		}

		return $out;
	}
}
############################

######################################
function show_checkbox($data)
{
	if(!isset($data['name'])){			$data['name'] = "";}
	if(!isset($data['text'])){			$data['text'] = "";}
	if(!isset($data['required'])){		$data['required'] = 0;}
	if(!isset($data['compare'])){		$data['compare'] = 0;}
	if(!isset($data['xtra'])){			$data['xtra'] = "";}
	if(!isset($data['xtra_class'])){	$data['xtra_class'] = "";}

	$checked = $data['value'] == $data['compare'] ? " checked" : "";

	if(substr($data['name'], -1) == "]")
	{
		$is_array = true;

		$this_id = substr($data['name'], 0, -2)."_".$data['value'];

		$data['xtra'] .= ($data['xtra'] != '' ? " " : "")."class='".substr($data['name'], 0, -2)."'";
	}

	else
	{
		$is_array = false;

		$this_id = $data['name'];
	}

	if($data['required'] == 1)
	{
		$data['xtra'] .= ($data['xtra'] != '' ? " " : "")."required";
	}

	$out = "<div class='form_checkbox".($data['xtra_class'] != '' ? " ".$data['xtra_class'] : "")."'>
		<input type='checkbox'";

			if($data['name'] != '')
			{
				$out .= " name='".$data['name']."' id='".$this_id."'";
			}

			$out .= " value='".$data['value']."'"
			.$checked
			.($data['xtra'] != '' ? " ".$data['xtra'] : "")
		.">";

		if($data['text'] != '')
		{
			$out .= "<label for='".$this_id."'>"
				.$data['text'];

				/*if($data['required'] == 1)
				{
					$out .= " *";
				}*/

			$out .= "</label>";
		}

	$out .= "</div>";

	return $out;
}
#################

################################
function show_radio_input($data)
{
	if(!isset($data['label'])){			$data['label'] = "";}
	if(!isset($data['text'])){			$data['text'] = "";}
	if(!isset($data['compare'])){		$data['compare'] = "";}
	if(!isset($data['xtra'])){			$data['xtra'] = "";}
	if(!isset($data['xtra_class'])){	$data['xtra_class'] = "";}

	$checked = "";

	if($data['compare'] != '' && $data['compare'] == $data['value'])
	{
		$checked = " checked";
	}

	$out = "<div class='form_radio".($data['xtra_class'] != '' ? " ".$data['xtra_class'] : "")."'>
		<input type='radio' id='".$data['name']."_".$data['value']."' name='".$data['name']."' value='".$data['value']."'".$checked.$data['xtra'].">";

		if($data['label'] != '')
		{
			$out .= "<label for='".$data['name']."_".$data['value']."'>".$data['label']."</label>";
		}

	$out .= "</div>";

	return $out;
}
#################

######################
function show_file_field($data)
{
	if(!isset($data['text'])){		$data['text'] = "";}
	if(!isset($data['class'])){		$data['class'] = "";}
	//if(!isset($data['size'])){		$data['size'] = 0;}
	if(!isset($data['multiple'])){	$data['multiple'] = false;}

	$label = "";

	if($data['text'] != '')
	{
		$label = "<label for='".$data['name']."'>".$data['text']."</label>";
	}

	return "<div class='form_file_input".($data['class'] != '' ? " ".$data['class'] : "")."'>"
		.$label
		."<input type='file'".($data['multiple'] == true ? " multiple='true'" : "")." name='".$data['name'].($data['multiple'] == true ? "[]" : "")."' value=''/>
	</div>";
}
######################

######################
function show_password_field($data)
{
	if(!isset($data['name'])){			$data['name'] = "";}
	if(!isset($data['text'])){			$data['text'] = "";}
	if(!isset($data['value'])){			$data['value'] = "";}
	if(!isset($data['max_length'])){	$data['max_length'] = "";}
	if(!isset($data['placeholder'])){	$data['placeholder'] = "";}
	if(!isset($data['xtra'])){			$data['xtra'] = "";}

	$data['max_length'] = $data['max_length'] != '' ? " maxlength='".$data['max_length']."'" : "";

	if($data['placeholder'] != '')
	{
		$data['placeholder'] .= "...";

		$data['xtra'] .= " placeholder='".$data['placeholder']."'";
	}

	$label = "";

	if($data['text'] != '')
	{
		$label .= "<label for='".$data['name']."'>".$data['text']."</label>";
	}

	return "<div class='form_password'>"
		.$label
		."<input type='password' name='".$data['name']."' value='".$data['value']."' id='".$data['name']."'".$data['max_length'].$data['xtra'].">
	</div>";
}
######################

#################
function show_submit($data)
{
	if(!isset($data['name'])){	$data['name'] = "";}
	if(!isset($data['xtra'])){	$data['xtra'] = "";}
	if(!isset($data['type'])){	$data['type'] = "submit";}
	if(!isset($data['class'])){	$data['class'] = "";}

	return "<button type='".$data['type']."'"
		.($data['name'] != '' ? " name='".$data['name']."'" : "")
		.($data['class'] != '' ? " class='".$data['class']."'" : " class='button button-primary button-large'")
		.$data['xtra']
	.">"
		.$data['text']
	."</button>";
}
#################

#####################
function input_hidden($data)
{
	if(!isset($data['name'])){			$data['name'] = "";}
	if(!isset($data['value'])){			$data['value'] = "";}
	if(!isset($data['allow_empty'])){	$data['allow_empty'] = false;}
	if(!isset($data['xtra'])){			$data['xtra'] = "";}

	if($data['value'] != '' || $data['value'] == 0 || $data['allow_empty'] == true)
	{
		return "<input type='hidden'".($data['name'] != '' ? " name='".$data['name']."'" : "")." value='".$data['value']."'".$data['xtra'].">";
	}
}
#####################

function get_file_content($data)
{
	$content = "";

	if(file_exists($data['file']) && filesize($data['file']) > 0)
	{
		if($fh = fopen(realpath($data['file']), 'r'))
		{
			$content = fread($fh, filesize($data['file']));
			fclose($fh);
		}

		else
		{
			insert_error(__("The file could not be opened", 'lang_base')." (".$data['file'].")");
		}
	}

	return $content;
}

function set_html_content_type()
{
	return 'text/html';
}

function hextostr($hex)
{
	$string = "";

	foreach(explode("\n", trim(chunk_split($hex, 2))) as $h)
	{
		$string .= chr(hexdec($h));
	}

	return $string;
}

function get_hmac_prepared_string($array)
{
	$string = "";

	ksort($array);

	foreach($array as $key => $value)
	{
		if($key != "MAC")
		{
			if(strlen($string) > 1)
			{
				$string .= "&";
			}

			$string .= $key."=".$value;
		}
	}

	return $string;
}

//
#######################
function get_match($regexp, $in, $all = true)
{
	preg_match($regexp, $in, $out);

	if(count($out) > 0)
	{
		if($all == true)
		{
			return $out;
		}

		else if(count($out) <= 1)
		{
			return $out[0];
		}

		else
		{
			return $out[1];
		}
	}
}
#######################

//
#######################
function get_match_all($regexp, $in, $all = true)
{
	preg_match_all($regexp, $in, $out);

	if(count($out) > 0)
	{
		if($all == true)
		{
			return $out[0];
		}

		else
		{
			$count_temp = count($out);

			for($i = 1; $i < $count_temp; $i++)
			{
				$out_new[] = $out[$i];
			}

			return $out_new;
		}
	}
}
#######################

//
##################
function set_file_content($data)
{
	$success = false;

	if(isset($data['realpath']) && $data['realpath'] == true)
	{
		$data['file'] = realpath($data['file']);
	}

	if($data['file'] != '')
	{
		if($fh = fopen($data['file'], $data['mode']))
		{
			if(fwrite($fh, $data['content']))
			{
				fclose($fh);

				$success = true;
			}
		}
	}

	return $success;
}
##################

function get_file_info($data)
{
	$dp = opendir($data['path']);

	while(($child = readdir($dp)) !== false)
	{
		if($child == '.' || $child == '..') continue;

		$file = str_replace("//", "/", $data['path'].'/'.$child);

		if(is_dir($file))
		{
			get_file_info(array('path' => $file, 'callback' => $data['callback']));
		}

		else
		{
			if(is_callable($data['callback']))
			{
				call_user_func($data['callback'], array('path' => $data['path'], 'file' => $file));
			}
		}
	}

	closedir($dp);
}

########################################
function show_table_header($arr_header)
{
	$out = "<thead>
		<tr>";

			$count_temp = count($arr_header);

			for($i = 0; $i < $count_temp; $i++)
			{
				$arr_header[$i] = stripslashes(strip_tags($arr_header[$i]));

				if(strlen($arr_header[$i]) > 15)
				{
					$title = $arr_header[$i];
					$content = substr($arr_header[$i], 0, 12)."...";
				}

				else
				{
					$title = "";
					$content = $arr_header[$i];
				}

				$out .= "<th".($title != '' ? " title='".$title."'" : "").">".$content."</th>";
			}

		$out .= "</tr>
	</thead>";

	return $out;
}
########################################

function get_post_children($data, &$arr_data = array())
{
	global $wpdb;

	if(!isset($data['current_id'])){	$data['current_id'] = "";}
	if(!isset($data['post_id'])){		$data['post_id'] = 0;}
	if(!isset($data['post_type'])){		$data['post_type'] = "page";}
	if(!isset($data['output_array'])){	$data['output_array'] = false;}

	if(!isset($data['depth']))
	{
		$data['depth'] = 0;
	}

	else
	{
		$data['depth']++;
	}

	$out = "";

	$result = $wpdb->get_results("SELECT ID, post_title FROM ".$wpdb->posts." WHERE post_type = '".$data['post_type']."' AND post_status = 'publish' AND post_parent = '".$data['post_id']."' ORDER BY menu_order ASC");

	if($wpdb->num_rows > 0)
	{
		foreach($result as $r)
		{
			$post_id = $r->ID;
			$post_title = $r->post_title;

			if($data['output_array'] == true)
			{
				for($i = 0; $i < $data['depth']; $i++)
				{
					$post_title = "&nbsp;&nbsp;&nbsp;".$post_title;
				}

				$arr_data[] = array($post_id, $post_title);
			}

			else
			{
				$out .= "<option value='".$post_id."'".($post_id == $data['current_id'] ? " selected" : "")." class='level-".$data['depth']."'>";

					for($i = 0; $i < $data['depth']; $i++)
					{
						$out .= "&nbsp;&nbsp;&nbsp;";
					}

					$out .= $post_title
				."</option>";
			}

			$data['post_id'] = $post_id;
			//$data['depth']++;

			$out .= get_post_children($data, $arr_data);
		}
	}

	return $out;
}

function format_phone_no($no)
{
	$out = "";

	/*if(is_user_logged_in() && function_exists('is_plugin_active') && is_plugin_active('mf_sms/index.php'))
	{
		$out = "/wp-admin/admin.php?page=mf_sms/list/index.php&strSmsTo=".$no;
	}

	else
	{*/
		$out = "tel:".str_replace(array(" ", "-", "/"), "", $no);
	//}

	return $out;
}

function get_font_awesome_icons_list()
{
	$transient_key = "fontawesome_transient";

	$content = get_transient($transient_key);

	if($content == "")
	{
		$content = get_url_content("http://fortawesome.github.io/Font-Awesome/icons/");

		set_transient($transient_key, $content, WEEK_IN_SECONDS);
	}

	$arr_icons = get_match_all("/icon\/(.*?)\"/s", $content, false);
	$arr_icons = array_unique($arr_icons[0]);
	$arr_icons = array_sort(array('array' => $arr_icons, 'on' => 1));

	return $arr_icons;
}

function password_form_base()
{
	return "<form action='".site_url('wp-login.php?action=postpass', 'login_post')."' method='post' class='mf_form'>
		<p>".__("To view this protected post, enter the password below", 'lang_base')."</p>"
		.show_password_field(array('name' => "post_password", 'placeholder' => __("Password", 'lang_base'), 'max_length' => 20))
		."<div class='form_button'>"
			.show_submit(array('text' => __("Submit")))
		."</div>
	</form>";
}

function the_content_protected_base($html)
{
    if(post_password_required())
	{
		$html = password_form_base();
	}

	return $html;
}

function month_name($month_no, $ucfirst = 1)
{
	if($month_no < 1)
	{
		$month_no = 1;
	}

	$month_names = array(__('January', 'lang_base'), __('February', 'lang_base'), __('March', 'lang_base'), __('April', 'lang_base'), __('May', 'lang_base'), __('June', 'lang_base'), __('July', 'lang_base'), __('August', 'lang_base'), __('September', 'lang_base'), __('October', 'lang_base'), __('November', 'lang_base'), __('December', 'lang_base'));

	$out = $month_names[$month_no - 1];

	if($ucfirst == 0){$out = strtolower($out);}

	return $out;
}

function day_name($day_no, $ucfirst = 1)
{
	$day_names = array(__('Sunday', 'lang_base'), __('Monday', 'lang_base'), __('Tuesday', 'lang_base'), __('Wednesday', 'lang_base'), __('Thursday', 'lang_base'), __('Friday', 'lang_base'), __('Saturday', 'lang_base'));

	$out = $day_names[$day_no];

	if($ucfirst == 0){$out = strtolower($out);}

	return $out;
}

function get_meta_image_url($post_id, $meta_key)
{
	$image_id = get_post_meta($post_id, $meta_key, true);

	$image_array = wp_get_attachment_image_src($image_id, 'full');

	return $image_array[0];
}