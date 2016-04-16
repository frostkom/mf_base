<?php

function admin_init_base()
{
	new recommend_plugin(array('path' => "github-updater/github-updater.php", 'name' => "GitHub Updater", 'url' => "//github.com/afragen/github-updater"));
}

function mf_uninstall_plugin($data)
{
	global $wpdb;

	if(!isset($data['uploads'])){			$data['uploads'] = "";}
	if(!isset($data['options'])){			$data['options'] = array();}
	if(!isset($data['tables'])){			$data['tables'] = array();}

	if($data['uploads'] != '')
	{
		list($upload_path, $upload_url) = get_uploads_folder($data['uploads']);

		get_file_info(array('path' => $this->upload_path, 'callback' => "delete_files", 'time_limit' => 0));

		rmdir($upload_path);
	}

	foreach($data['options'] as $option)
	{
		delete_option($option);
		delete_site_option($option);
	}

	foreach($data['tables'] as $table)
	{
		$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix.$table);
	}
}

function get_setting_key($function_name)
{
	return str_replace("_callback", "", $function_name);
}

function is_domain_valid($email, $record = 'MX')
{
	list($user, $domain) = explode('@', $email);

	return checkdnsrr($domain, $record);
}

function get_option_or_default($key, $default = '')
{
	$option = get_option($key);

	if($option == '' && $default != '')
	{
		$option = $default;
	}

	return $option;
}

function get_post_meta_file_src($data)
{
	if(!isset($data['is_image'])){		$data['is_image'] = true;}
	if(!isset($data['image_size'])){	$data['image_size'] = "full";} //thumbnail, medium, large, or full
	if(!isset($data['single'])){		$data['single'] = true;}

	$file_ids = get_post_meta($data['post_id'], $data['meta_key'], $data['single']);

	if($data['single'])
	{
		if($data['is_image'] == true)
		{
			$file_url = wp_get_attachment_image_src($file_ids, $data['image_size']);
			$file_url = $file_url[0];
		}

		else
		{
			$file_url = wp_get_attachment_url($file_ids);
		}
	}

	else
	{
		$file_url = array();

		foreach($file_ids as $file_id)
		{
			if($data['is_image'] == true)
			{
				$file_url_temp = wp_get_attachment_image_src($file_id, $data['image_size']);
				$file_url[] = $file_url_temp[0];
			}

			else
			{
				$file_url[] = wp_get_attachment_url($file_id);
			}
		}
	}

	return $file_url;
}

function is_between($data)
{
	$out = false;

	$value_min = $data['value'][0];
	$value_max = isset($data['value'][1]) ? $data['value'][1] : "";
	$compare_min = $data['compare'][0];
	$compare_max = $data['compare'][1];

	if($value_min >= $compare_min && $value_min <= $compare_max)
	{
		$out = true;
	}

	else if($value_max != '' && $value_max >= $compare_min && $value_max <= $compare_max)
	{
		$out = true;
	}

	if(isset($data['value'][1]))
	{
		$value_max = $data['value'][1];

		if($compare_min >= $value_min && $compare_min <= $value_max)
		{
			$out = true;
		}

		else if($compare_max >= $value_min && $compare_max <= $value_max)
		{
			$out = true;
		}
	}

	return $out;
}

function time_between_dates($data) //$start, $end, $type = "round", $divide = 86400
{
	if(!isset($data['type'])){		$data['type'] = "round";}
	if(!isset($data['return'])){	$data['return'] = "days";}

	$arr_return_types = array(
		'days' => 60 * 60 * 24,
		'hours' => 60 * 60,
		'minutes' => 60,
		'seconds' => 1,
	);

	$out = strtotime($data['end']) - strtotime($data['start']);

	if(isset($arr_return_types[$data['return']]))
	{
		$out /= $arr_return_types[$data['return']];
	}

	switch($data['type'])
	{
		case 'ceil':	$out = ceil($out);		break;
		case 'round':	$out = round($out);		break;
		case 'floor':	$out = floor($out);		break;
	}

	return $out;
}

function delete_files($data)
{
	if(!isset($data['time_limit'])){	$data['time_limit'] = 60 * 60 * 24 * 2;} //2 days

	$time_now = time();
	$time_file = filemtime($data['file']);

	if($data['time_limit'] == 0 || ($time_now - $time_file >= $data['time_limit']))
	{
		unlink($data['file']);
	}
}

function format_date($in)
{
	$out = "";

	if($in > DEFAULT_DATE)
	{
		$date_now = date("Y-m-d");
		$one_day_ago = date("Y-m-d", strtotime("-1 day"));
		$one_week_ago = date("Y-m-d", strtotime("-6 day"));

		$date_short = date("Y-m-d", strtotime($in));

		$date_year = date("Y", strtotime($in));
		$date_month = date("m", strtotime($in));
		$date_day = date("d", strtotime($in));
		$date_hour = date("G", strtotime($in));
		$date_minute = date("i", strtotime($in));

		if($one_week_ago > $date_short)
		{
			$out = $date_short;
		}

		else
		{
			if($one_day_ago > $date_short)
			{
				$firstday_ts = mktime(0, 0, 0, $date_month, $date_day, $date_year);
				$date_weekday = day_name(date("w", $firstday_ts));
				//$date_weekday = day_name(date("w", $date_short));

				$out .= $date_weekday."&nbsp;";
			}

			else if($one_day_ago == $date_short)
			{
				$out .= __("Yesterday", 'lang_base')."&nbsp;";
			}

			if($date_year != date("Y") && $in < $one_week_ago)
			{
				$out .= $date_year;
			}

			else if($date_short > $date_now)
			{
				$out .= $date_short;
			}

			else
			{
				$out .= $date_hour.":".$date_minute;
			}
		}
	}

	return $out;
}

function get_uploads_folder($subfolder = "")
{
	$upload_dir = wp_upload_dir();

	$upload_path = $upload_dir['basedir']."/".($subfolder != '' ? $subfolder."/" : "");
	$upload_url = $upload_dir['baseurl']."/".($subfolder != '' ? $subfolder."/" : "");

	return array($upload_path, $upload_url);
}

function insert_attachment($data)
{
	global $wpdb, $done_text, $error_text;

	$intFileID = false;

	if(strlen($data['content']) > 0 && $data['name'] != '')
	{
		$file_name = basename($data['name']);
		$file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
		$file_name = sanitize_title_with_dashes(sanitize_title($file_name)).".".$file_extension;

		$upload_dir = wp_upload_dir();

		$temp_file = $upload_dir['path']."/".$file_name;
		$file_url = $upload_dir['url']."/".$file_name;

		$intFileID = $wpdb->get_var($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_type = 'attachment' AND guid = %s", validate_url($file_url)));

		if(!($intFileID > 0))
		{
			set_file_content(array('file' => $temp_file, 'mode' => 'w', 'content' => $data['content']));

			$attachment = array(
				'post_mime_type' => $data['mime'],
				'post_title' => preg_replace('/\.[^.]+$/', '', basename($data['name'])),
				'post_content' => '',
				'post_status' => 'inherit',
				'guid' => $file_url,
			);

			$intFileID = wp_insert_attachment($attachment, $file_url);

			if(!($intFileID > 0))
			{
				$error_text = __("Well, we tried to save the file but something went wrong internally in Wordpress", 'lang_base').": ".$temp_file;
			}
		}

		else
		{
			//$error_text = __("The file already exists", 'lang_base').": ".$file_url;
		}
	}

	return $intFileID;
}

function do_log($data, $action = "insert")
{
	if(!class_exists('mf_log') && file_exists(ABSPATH.'wp-content/mf_log/include/classes.php'))
	{
		require_once(ABSPATH.'wp-content/mf_log/include/classes.php');
	}

	if(class_exists('mf_log'))
	{
		$obj_log = new mf_log();
		$obj_log->create($data, $action);
	}

	else if(IS_ADMIN)
	{
		echo $data."<br>";
	}
}

function schedules_base($schedules)
{
	$schedules['every_ten_seconds'] = array('interval' => 10, 'display' => __("Manually", 'lang_base'));
	$schedules['every_two_minutes'] = array('interval' => 60 * 2, 'display' => __("Every 2 Minutes", 'lang_base'));
	$schedules['every_ten_minutes'] = array('interval' => 60 * 10, 'display' => __("Every 10 Minutes", 'lang_base'));

	$schedules['weekly'] = array('interval' => 60 * 60 * 24 * 7, 'display' => __("Weekly", 'lang_base'));
	$schedules['monthly'] = array('interval' => 60 * 60 * 24 * 7 * 4, 'display' => __("Monthly", 'lang_base'));

	return $schedules;
}

function set_cron($hook, $option_key, $option_default = 'hourly')
{
	if(!wp_next_scheduled($hook))
	{
		$recurrance = get_option($option_key, $option_default);

		wp_schedule_event(time(), $recurrance, $hook);
	}
}

function unset_cron($hook)
{
	wp_clear_scheduled_hook($hook);
}

function delete_base($data)
{
	global $wpdb;

	if(!isset($data['child_tables'])){	$data['child_tables'] = array();}

	$empty_trash_days = defined('EMPTY_TRASH_DAYS') ? EMPTY_TRASH_DAYS : 30;

	$data['field_prefix'] = esc_sql($data['field_prefix']);
	$data['table'] = esc_sql($data['table']);

	$result = $wpdb->get_results("SELECT ".$data['field_prefix']."ID AS ID FROM ".$wpdb->base_prefix.$data['table']." WHERE ".$data['field_prefix']."Deleted = '1' AND ".$data['field_prefix']."DeletedDate < DATE_SUB(NOW(), INTERVAL ".$empty_trash_days." DAY)");

	foreach($result as $r)
	{
		$intID = $r->ID;

		$rows = 0;

		foreach($data['child_tables'] as $child_table => $child_table_type)
		{
			if($child_table_type['action'] == "trash")
			{
				$wpdb->get_results($wpdb->prepare("SELECT ".$data['field_prefix']."ID FROM ".$wpdb->base_prefix.$child_table." WHERE ".$data['field_prefix']."ID = '%d' LIMIT 0, 1", $intID));
				$rows_temp = $wpdb->num_rows;

				if($rows_temp > 0)
				{
					$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix.$child_table." SET ".$child_table_type['field_prefix']."Deleted = '1', ".$child_table_type['field_prefix']."DeletedDate = NOW() WHERE ".$data['field_prefix']."ID = '%d' AND ".$child_table_type['field_prefix']."Deleted = '0'", $intID));

					$rows += $rows_temp;
				}
			}
		}

		if($rows == 0)
		{
			foreach($data['child_tables'] as $child_table => $child_table_type)
			{
				if($child_table_type['action'] == "delete")
				{
					$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix.$child_table." WHERE ".$data['field_prefix']."ID = '%d'", $intID));
				}
			}

			$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix.$data['table']." WHERE ".$data['field_prefix']."ID = '%d'", $intID));
		}

		if($rows > 0)
		{
			do_log("Trashed ".$rows." posts in ".$child_table);
		}
	}
}

function init_base()
{
	define('DEFAULT_DATE', "1982-08-04 23:15:00");
	//define('IS_SUPER_ADMIN', current_user_can('update_core'));
	define('IS_ADMIN', current_user_can('manage_options'));
	define('IS_EDITOR', current_user_can('edit_pages'));
	define('IS_AUTHOR', current_user_can('upload_files'));

	$timezone_string = get_option('timezone_string');

	if($timezone_string != '')
	{
		date_default_timezone_set($timezone_string);
	}

	$setting_base_auto_core_update = get_option('setting_base_auto_core_update');
	$setting_base_auto_core_email = get_option('setting_base_auto_core_email');

	if($setting_base_auto_core_update != '')
	{
		if($setting_base_auto_core_update == "all"){		$setting_base_auto_core_update = true;}
		else if($setting_base_auto_core_update == "none"){	$setting_base_auto_core_update = false;}

		define('WP_AUTO_UPDATE_CORE', $setting_base_auto_core_update);
	}

	if($setting_base_auto_core_email != "yes")
	{
		apply_filters('auto_core_update_send_email', '__return_false');
		//apply_filters('auto_core_update_send_email', false, $type, $core_update, $result);
	}

	wp_enqueue_style('font-awesome', plugin_dir_url(__FILE__)."font-awesome.min.css");
	wp_enqueue_style('style_base', plugin_dir_url(__FILE__)."style.css");

	// Add datepicker
	/*wp_enqueue_style('jquery-ui-css', '//ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css');
	wp_enqueue_script('jquery-ui-datepicker');*/

	mf_enqueue_script('script_base', plugin_dir_url(__FILE__)."script.js", array('confirm_question' => __("Are you sure?", 'lang_base')));

	if(is_user_logged_in() && IS_ADMIN)
	{
		global $wpdb;

		if(!defined('DIEONDBERROR'))
		{
			define('DIEONDBERROR', true);
		}

		$wpdb->show_errors();
	}
}

function get_file_suffix($file)
{
	$arr_file_name = explode(".", $file);

	return $arr_file_name[count($arr_file_name) - 1];
}

function get_media_button($data = array())
{
	$out = "";

	if(!isset($data['name'])){				$data['name'] = "mf_media_urls";}
	if(!isset($data['text'])){				$data['text'] = __("Add Attachment", 'lang_base');}
	if(!isset($data['value'])){				$data['value'] = "";}
	if(!isset($data['show_add_button'])){	$data['show_add_button'] = true;}
	if(!isset($data['multiple'])){			$data['multiple'] = true;}

	if(IS_AUTHOR && $data['show_add_button'] == true || $data['value'] != '')
	{
		wp_enqueue_style('style_media_button', plugin_dir_url(__FILE__)."style_media_button.css");
		mf_enqueue_script('script_media_button', plugin_dir_url(__FILE__)."script_media_button.js", array(
			'multiple' => $data['multiple'],
			'no_attachment_link' => __("The Media Library did not return a link to the file you added. Please try again and make sure that 'Link To' is set to 'Media File'", 'lang_base'),
		));

		$out .= "<div class='mf_media_button'>";

			if(IS_AUTHOR && $data['show_add_button'] == true)
			{
				$out .= "<div class='wp-media-buttons'>
					<a href='#' class='button insert-media add_media'>
						<span class='wp-media-buttons-icon'></span> <span>".$data['text']."</span>
					</a>
				</div>";
			}

			$out .= "<div class='mf_media_raw'></div>
			<table class='mf_media_list widefat striped'></table>"
			.input_hidden(array('name' => $data['name'], 'value' => $data['value'], 'allow_empty' => true, 'xtra' => " class='mf_media_urls'"))
			//."<textarea name='".$data['name']."' class='mf_media_urls'>".$data['value']."</textarea>"
		."</div>";
	}

	return $out;
}

function get_file_button($data)
{
	wp_enqueue_style('thickbox');
	wp_enqueue_style('style_media_button', plugin_dir_url(__FILE__)."style_media_button.css");

	$add_file_text = __("Add Image", 'lang_base');
	$change_file_text = __("Change Image", 'lang_base');
	$insert_file_text = __("Insert Image", 'lang_base');

	wp_enqueue_script('media-upload');
	wp_enqueue_script('thickbox');
	mf_enqueue_script('script_media_button', plugin_dir_url(__FILE__)."script_media_button.js", array(
		'multiple' => false,
		'no_attachment_link' => __("The Media Library did not return a link to the file you added. Please try again and make sure that 'Link To' is set to 'Media File'", 'lang_base'),
		'adminurl' => get_admin_url(), 'add_file_text' => $add_file_text, 'change_file_text' => $change_file_text, 'insert_file_text' => $insert_file_text
	));

	return "<div class='mf_image_button'>
		<div".($data['option'] != '' ? "" : " class='hide'").">
			<img src='".$data['option']."'>
			<a href='#' rel='confirm'><i class='fa fa-lg fa-trash'></i></a>
		</div>
		<div>"
			.show_submit(array('text' => ($data['option'] != '' ? $change_file_text : $add_file_text), 'class' => "button"))
			.input_hidden(array('name' => $data['setting_key'], 'value' => $data['option']))
		."</div>
		<div class='mf_file_raw'></div>
	</div>";
}

function get_attachment_callback($in, $callback)
{
	$arr_files = get_attachment_to_send($in);

	if(count($arr_files) > 0)
	{
		foreach($arr_files as $file_url)
		{
			$file_id = get_attachment_id_by_url($file_url);

			if($file_id > 0)
			{
				if(is_callable($callback))
				{
					call_user_func($callback, $file_id);
				}
			}

			else
			{
				$error_text = __("The file couldn't be saved", 'lang_base')." (".$file_url.")";
			}
		}
	}
}

function get_attachment_to_send($string)
{
	$arr_files = array();

	if($string != '')
	{
		$arr_attachments = explode(",", $string);

		foreach($arr_attachments as $attachment)
		{
			list($file_name, $file_url) = explode("|", $attachment);

			if($file_url != '')
			{
				$file_url = WP_CONTENT_DIR.str_replace(site_url()."/wp-content", "", $file_url);

				if(file_exists($file_url))
				{
					$arr_files[] = $file_url;
				}
			}
		}
	}

	return $arr_files;
}

function get_attachment_id_by_url($url)
{
	global $wpdb;

	$out = "";

	list($rest, $parsed_url) = explode(parse_url(WP_CONTENT_URL, PHP_URL_PATH), $url);

	$parsed_url = preg_replace("/\-\d+x\d+\./", ".", $parsed_url);

	if($parsed_url != '')
	{
		$out = $wpdb->get_var($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_type = 'attachment' AND guid RLIKE %s", $parsed_url));
	}
	
	return $out;
}

function get_attachment_data_by_id($id)
{
	global $wpdb;

	$result = $wpdb->get_results($wpdb->prepare("SELECT post_title, guid FROM ".$wpdb->posts." WHERE post_type = 'attachment' AND ID = '%d'", $id));

	if($wpdb->num_rows > 0)
	{
		$r = $result[0];

		return array($r->post_title, $r->guid);
	}
}

function mf_format_number($in, $dec = 2)
{
	$out = number_format($in, 0, '.', '') == $in ? number_format($in, 0, '.', ' ') : number_format($in, $dec, '.', ' ');

	return $out;
}

function upload_mimes_base($existing_mimes = array())
{
	// add your extension to the array
	$existing_mimes['eot'] = "font/opentype";
	$existing_mimes['ico'] = "image/x-icon";
	$existing_mimes['svg'] = "image/svg+xmln";
	$existing_mimes['ttf'] = "font/truetype";
	$existing_mimes['woff'] = "application/font-woff";

	// removing existing file types
	//unset($existing_mimes['exe']);

	return $existing_mimes;
}

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

function get_install_link_tags($require_url, $required_name)
{
	$a_start = "<a href='".($require_url != '' ? $require_url : get_site_url()."/wp-admin".(is_multisite() ? "/network" : "")."/plugin-install.php?tab=search&s=".$required_name)."'>";
	$a_end = "</a>";

	return array($a_start, $a_end);
}

function require_plugin($required_path, $required_name, $require_url = "")
{
	if(function_exists('is_plugin_active') && !is_plugin_active($required_path))
	{
		list($a_start, $a_end) = get_install_link_tags($require_url, $required_name);

		mf_trigger_error(sprintf(__("You need to install the plugin %s%s%s first", 'lang_base'), $a_start, $required_name, $a_end), E_USER_ERROR);
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

	$user_data = get_userdata($id);

	return $user_data->roles[0];
}

function settings_base()
{
	wp_enqueue_style('style_base_wp', plugin_dir_url(__FILE__)."style_wp.css");

	wp_enqueue_script('jquery-ui-autocomplete');
	wp_enqueue_script('script_swipe', plugin_dir_url(__FILE__)."jquery.touchSwipe.min.js");
	mf_enqueue_script('script_base_wp', plugin_dir_url(__FILE__)."script_wp.js", array('plugins_url' => plugins_url()));

	define('BASE_OPTIONS_PAGE', "settings_mf_base");

	$options_area = __FUNCTION__;

	add_settings_section($options_area, "",	$options_area."_callback", BASE_OPTIONS_PAGE);

	$arr_settings = array(
		"setting_base_info" => __("Versions", 'lang_base'),
		"setting_base_auto_core_update" => __("Update core automatically", 'lang_base'),
	);

	if(get_option('setting_base_auto_core_update') != 'none')
	{
		$arr_settings["setting_base_auto_core_email"] = __("Update notification", 'lang_base');
	}

	$arr_settings["setting_base_cron"] = __("Scheduled to run", 'lang_base');
	$arr_settings["setting_base_recommend"] = __("Recommendations", 'lang_base');
	//$arr_settings["setting_all_options"] = __("All options", 'lang_base');

	foreach($arr_settings as $handle => $text)
	{
		add_settings_field($handle, $text, $handle."_callback", BASE_OPTIONS_PAGE, $options_area);

		register_setting(BASE_OPTIONS_PAGE, $handle);
	}
}

function settings_base_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);

	echo settings_header($setting_key, __("Common", 'lang_base'));
}

//main_version*10000 + minor_version *100 + sub_version. For example, 4.1.0 is returned as 40100
function int2point($in)
{
	$out = "";
	$in_orig = $in;

	$main_version = floor($in / 10000);

	$in -= $main_version * 10000;

	$minor_version = floor($in / 100);

	$in -= $minor_version * 100;

	$sub_version = $in;

	return $main_version.".".$minor_version.".".$sub_version; //." (".$in_orig.")"
}

function settings_header($id, $title)
{
	return "<div id='".$id."'><a href='#".$id."'><h3>".$title."</h3></a></div>"; //&nbsp;
}

function setting_base_info_callback()
{
	$php_version = explode("-", phpversion());
	$php_version = $php_version[0];
	$mysql_version = explode("-", @mysql_get_server_info());
	$mysql_version = $mysql_version[0];

	if($mysql_version == '')
	{
		$mysql_version = int2point(mysqli_get_client_version());
	}

	$php_required = "5.2.4";
	$mysql_required = "5.0";

	echo "<p><i class='fa ".($php_version > $php_required ? "fa-check green" : "fa-close red")."'></i> PHP: ".$php_version."</p>
	<p><i class='fa ".($mysql_version > $mysql_required ? "fa-check green" : "fa-close red")."'></i> MySQL: ".$mysql_version."</p>
	<p><a href='//wordpress.org/about/requirements/'>".__("Requirements", 'lang_base')."</a></p>";
}

function setting_base_recommend_callback()
{
	$arr_recommendations = array(
		array("Admin Branding", 'admin-branding/admin-branding.php', __("to brand the login and admin area", 'lang_base')),
		array("Admin Menu Tree Page View", 'admin-menu-tree-page-view/index.php'),
		array("Adminer", 'adminer/adminer.php', __("to get a graphical interface to the database", 'lang_base')),
		array("BackWPup", 'backwpup/backwpup.php', __("to backup all files and database to an external source", 'lang_base')),
		array("Black Studio TinyMCE Widget", 'black-studio-tinymce-widget/black-studio-tinymce-widget.php', __("to get a WYSIWYG widget editor", 'lang_base')),
		array("Email Log", 'email-log/email-log.php', __("to log all outgoing e-mails", 'lang_base')),
		array("Enable Media Replace", 'enable-media-replace/enable-media-replace.php', __("to be able to replace existing files by uploading a replacement", 'lang_base')),
		array("Google Authenticator", 'google-authenticator%2Fgoogle-authenticator.php', __("to use 2-step verification when logging in", 'lang_base')),
		array("JS & CSS Script Optimizer", 'js-css-script-optimizer/js-css-script-optimizer.php', __("to compress and combine JS and CSS files", 'lang_base')),
		//array("Media Library Categories", 'wp-media-library-categories%2Findex.php', __("to be able to categorize uploaded files", 'lang_base')),
		array("Quick Page/Post Redirect Plugin", 'quick-pagepost-redirect-plugin/page_post_redirect_plugin.php', __("to redirect pages to internal or external URLs", 'lang_base')),
		array("Simple Page Ordering", 'simple-page-ordering/simple-page-ordering.php', __("to reorder posts with drag & drop", 'lang_base')),
		array("TablePress", 'tablepress/tablepress.php', __("to be able to add tables to posts", 'lang_base')),
		array("User Role Editor", 'user-role-editor/user-role-editor.php', __("to be able to edit roles", 'lang_base')),
		array("User Switching", 'user-switching/user-switching.php', __("to be able to switch to another user without their credentials", 'lang_base')),
		array("WP Smush", 'wp-smushit/wp-smush.php', __("to losslessly compress all uploaded images", 'lang_base')),
		array("WP Super Cache", 'wp-super-cache/wp-cache.php', __("to increase the speed of the public site", 'lang_base')),
		array("WP-Mail-SMTP", 'wp-mail-smtp/wp_mail_smtp.php', __("to setup custom SMTP settings", 'lang_base')),
		array("Favicon by RealFaviconGenerator", 'favicon-by-realfavicongenerator/favicon-by-realfavicongenerator.php', __("to add all the favicons needed", 'lang_base')),
	);

	foreach($arr_recommendations as $value)
	{
		$name = $value[0];
		$path = $value[1];
		$text = isset($value[2]) ? $value[2] : "";

		new recommend_plugin(array('path' => $path, 'name' => $name, 'text' => $text, 'show_notice' => false));
	}
}

function setting_base_auto_core_update_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key, 'minor');

	$arr_data = array(
		'none' => __("None", 'lang_base'),
		'minor' => __("Minor", 'lang_base')." (".__("default", 'lang_base').")",
		'all' => __("All", 'lang_base'),
	);

	echo show_select(array('data' => $arr_data, 'name' => $setting_key, 'compare' => $option));
}

function setting_base_auto_core_email_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'compare' => $option))
	."<span class='description'>".__("Send e-mail to admin after auto core update", 'lang_base')."</span>";
}

function setting_base_cron_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key, 'every_ten_minutes');

	//Re-schedule if value has changed
	######################
	$schedule = wp_get_schedule('cron_base');

	if($schedule != $option)
	{
		deactivate_base();
		activate_base();
	}
	######################

	$next_scheduled = wp_next_scheduled('cron_base');

	$arr_schedules = wp_get_schedules();

	$arr_data = array();

	foreach($arr_schedules as $key => $value)
	{
		$arr_data[$key] = $value['display'];
	}

	$next_scheduled_text = sprintf(__("Next scheduled %s", 'lang_base'), date("Y-m-d H:i:s", $next_scheduled));

	if(defined('DISABLE_WP_CRON') && DISABLE_WP_CRON == true)
	{
		echo $next_scheduled_text.". <a href='".get_site_url()."/wp-cron.php?doing_cron'>".__("Run schedule manually", 'lang_base')."</a>";
	}

	else
	{
		if($option == "every_ten_seconds")
		{
			$next_scheduled_text .= ". ".sprintf(__("Make sure that %s is added to wp-config.php", 'lang_base'), "define('DISABLE_WP_CRON', true);")."</a>";
		}

		echo show_select(array('data' => $arr_data, 'name' => 'setting_base_cron', 'compare' => $option, 'description' => $next_scheduled_text));
	}
}

function setting_all_options_callback()
{
	echo "<a href='".admin_url("options.php")."'>".__("Edit", 'lang_base')."</a>";
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

	if(!isset($data['orig'])){	$data['orig'] = false;}

	if($data['orig'] == true)
	{
		$roles_temp = get_option('wp_user_roles_orig');

		if($roles_temp == '')
		{
			$roles_temp = get_option('wp_user_roles');
		}

		$roles = array();

		foreach($roles_temp as $key => $value)
		{
			$roles[$key] = $value['name'];
		}
	}

	else
	{
		if(is_plugin_active("mf_users/index.php") && function_exists('hide_roles'))
		{
			hide_roles();
		}

		$roles = $wp_roles->get_names();
	}

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

	if(isset($wp_roles->roles[$role]['capabilities']) && is_array($wp_roles->roles[$role]['capabilities']))
	{
		$capabilities = $wp_roles->roles[$role]['capabilities'];
		$cap_keys = array_keys($capabilities);

		return $cap_keys[0];
	}

	else
	{
		return false;
	}
}

function get_yes_no_for_select($data = array())
{
	if(!isset($data['add_choose_here'])){	$data['add_choose_here'] = false;}
	if(!isset($data['return_integer'])){	$data['return_integer'] = false;}

	$arr_data = array();
	
	if($data['add_choose_here'] == true)
	{
		$arr_data[''] = "-- ".__("Choose here", 'lang_base')." --";
	}

	if($data['return_integer'] == true)
	{
		$arr_data[1] = __("Yes", 'lang_base');
		$arr_data[0] = __("No", 'lang_base');
	}

	else
	{
		$arr_data['yes'] = __("Yes", 'lang_base');
		$arr_data['no'] = __("No", 'lang_base');
	}

	return $arr_data;
}

function get_roles_for_select($data = array())
{
	if(!isset($data['array'])){				$data['array'] = array();}
	if(!isset($data['add_choose_here'])){	$data['add_choose_here'] = false;}
	if(!isset($data['strict_key'])){		$data['strict_key'] = false;}

	if($data['add_choose_here'] == true)
	{
		$data['array'][''] = "-- ".__("Choose here", 'lang_base')." --";
	}

	$roles = get_all_roles();

	foreach($roles as $key => $value)
	{
		$key = get_role_first_capability($key);

		if(!isset($data['array'][$key]) && $key != '')
		{
			$data['array'][$key] = $value;
		}
	}

	return $data['array'];
}

function get_posts_for_select($data)
{
	global $wpdb;

	if(!isset($data['add_choose_here'])){	$data['add_choose_here'] = false;}
	if(!isset($data['optgroup'])){			$data['optgroup'] = true;}

	if(!isset($data['post_type'])){			$data['post_type'] = "";}
	if(!isset($data['post_status'])){		$data['post_status'] = "publish";}
	if(!isset($data['post_parent'])){		$data['post_parent'] = "";}
	if(!isset($data['order'])){				$data['order'] = "menu_order ASC";}

	$query_where = "";

	if($data['post_type'] != '')
	{
		$query_where .= " AND post_type = '".esc_sql($data['post_type'])."'";
	}

	else
	{
		$query_where .= " AND post_type NOT IN('nav_menu_item')";
	}

	$result = $wpdb->get_results($wpdb->prepare("SELECT ID, post_type, post_title FROM ".$wpdb->posts." WHERE post_status = %s AND post_parent = '%d'".$query_where." ORDER BY post_type ASC, ".esc_sql($data['order']), $data['post_status'], $data['post_parent']));

	$post_type_temp = $data['post_type'];
	$opt_start_open = false;

	$arr_data = array();

	if($data['add_choose_here'] == true)
	{
		$arr_data[]	= array("", "-- ".__("Choose here", 'lang_base')." --");
	}

	foreach($result as $r)
	{
		$post_id = $r->ID;
		$post_type = $r->post_type;
		$post_title = $r->post_title;

		if($post_type != $post_type_temp)
		{
			if($data['optgroup'] == true && $opt_start_open == true)
			{
				$arr_data["opt_end_".$post_type] = "";
			}

			$arr_data["opt_start_".$post_type] = "-- ".$post_type." --";
			$opt_start_open = true;
			
			$post_type_temp = $post_type;
		}

		$arr_data[$post_id] = $post_title;
	}

	return $arr_data;
}

//Sortera array
#########################
# array		array(array("firstname" => "Martin", "surname" => "Fors"))
# on		Ex. surname
# order		asc/desc
#########################
function array_sort($data)
{
	if(!isset($data['order'])){			$data['order'] = "asc";}
	if(!isset($data['keep_index'])){	$data['keep_index'] = false;}

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

		foreach($sortable_array as $k => $v)
		{
			if($data['keep_index'] == true)
			{
				$new_array[$k] = $array[$k];
			}

			else
			{
				$new_array[] = $array[$k];
			}
		}
	}

	return $new_array;
}
#########################

/*function array_remove($data)
{
	if(!isset($data['on'])){		$data['on'] = 'key';}

	if(!is_array($data['remove']))
	{
		$data['remove'] = array($data['remove']);
	}

	foreach($data['array'] as $key => $value)
	{
		if($data['on'] == 'key')
		{
			foreach($data['remove'] as $remove)
			{
				if($remove == $key)
				{
					unset($data['array'][$key]);
				}
			}
		}

		else if($data['on'] == 'value')
		{
			foreach($data['remove'] as $remove)
			{
				if(is_array($value) && in_array($remove, $value) || $remove == $value)
				{
					unset($data['array'][$key]);
				}
			}
		}
	}

	return $data['array'];
}*/

#################
function validate_url($value, $link = true, $http = true)
{
	if($link == true)
	{
		$exkludera = array("&", " ", "amp;amp;", '�', '�', '�', '�', '�', '�', '�');
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
				<input type='search' name='s' value='".$strSearch."'>"
				.show_submit(array('text' => __("Search", 'lang_base'), 'class' => "button"))
				//."<button type='submit' class='button'>".__("Search", 'lang_base')."</button>
			."</p>
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
			$result = $wpdb->get_results("SHOW COLUMNS FROM ".esc_sql($table)." WHERE Field = '".esc_sql($column)."'");

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
			$result = $wpdb->get_results("SHOW COLUMNS FROM ".esc_sql($table)." WHERE Field = '".esc_sql($column)."'");

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

function mf_redirect($location, $arr_vars = array())
{
	$count_temp = count($arr_vars);

	if(headers_sent() == true || $count_temp > 0)
	{
		echo "<form name='reload' action='".$location."' method='post'>";

			if($count_temp > 0)
			{
				foreach($arr_vars as $key => $value)
				{
					echo input_hidden(array('name' => $key, 'value' => $value));
				}
			}
		
		echo "</form>
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

		//return format_date($data['date']);
	}
}

function check_var($in, $type = 'char', $v2 = true, $default = '', $return_empty = false, $force_req_type = '')
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
		}
	}

	else if($type == 'date' || $type2 == 'dte') // || $type == 'shortDate' || $type == 'shortDate2'
	{
		/*if($type == 'shortDate')
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
				}
			}
		}

		else if($type == 'shortDate2')
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
				}
			}
		}

		else
		{*/
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
				}
			}
		//}
	}

	else if(is_array($temp) || $type == 'array' || $type2 == 'arr')
	{
		if(is_array($temp))
		{
			$out = $temp; //F�r aldrig k�ras addslashes() p� detta
		}

		else if($temp == '')
		{
			$out = array();
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

	$arr_accepted_types = array('text', 'email', 'url', 'date', 'number', 'range', 'color');

	if(!isset($data['type']) || !in_array($data['type'], $arr_accepted_types)){	$data['type'] = "text";}
	if(!isset($data['name'])){			$data['name'] = "";}
	if(!isset($data['text'])){			$data['text'] = "";}
	if(!isset($data['value'])){			$data['value'] = "";}
	if(!isset($data['maxlength'])){		$data['maxlength'] = "";}
	if(!isset($data['size'])){			$data['size'] = 0;}
	if(!isset($data['required'])){		$data['required'] = false;}
	if(!isset($data['placeholder'])){	$data['placeholder'] = "";}
	if(!isset($data['pattern'])){		$data['pattern'] = "";}
	if(!isset($data['xtra'])){			$data['xtra'] = "";}
	if(!isset($data['xtra_class'])){	$data['xtra_class'] = "";}
	if(!isset($data['datalist'])){		$data['datalist'] = array();}
	if(!isset($data['suffix'])){		$data['suffix'] = "";}
	if(!isset($data['description'])){	$data['description'] = "";}

	if($data['type'] == "date")
	{
		// Add datepicker
		wp_enqueue_style('jquery-ui-css', '//ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css');
		wp_enqueue_script('jquery-ui-datepicker');
		mf_enqueue_script('script_base_datepicker', plugin_dir_url(__FILE__)."script_datepicker.js");

		$data['type'] = "text";
		$data['xtra_class'] .= ($data['xtra_class'] != '' ? " " : "")."mf_datepicker";
	}

	if($data['value'] == "0000-00-00"){$data['value'] = "";}

	if($data['required'])
	{
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

	$count_temp = count($data['datalist']);

	if($count_temp > 0)
	{
		$data['xtra'] .= " list='".$data['name']."_list'";
	}

	$out = "<div class='form_textfield".($data['xtra_class'] != '' ? " ".$data['xtra_class'] : "")."'>";

		if($data['text'] != '')
		{
			$out .= "<label for='".$data['name']."'>".$data['text']."</label>";
		}
		
		$out .= "<input type='".$data['type']."'".($data['name'] != '' ? " name='".$data['name']."'" : "")." value='".$data['value']."'".$data['xtra'].">";

		if($data['suffix'] != '')
		{
			$out .= "&nbsp;<span class='description'>".$data['suffix']."</span>";
		}

		if($data['description'] != '')
		{
			$out .= "<p class='description'>".$data['description']."</p>";
		}

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
	if(!isset($data['required'])){		$data['required'] = false;}
	//if(!isset($data['wysiwyg'])){		$data['wysiwyg'] = false;}

	if($data['required'])
	{
		$data['xtra'] .= " required";
	}

	if($data['placeholder'] != '')
	{
		$data['placeholder'] .= "...";

		$data['xtra'] .= " placeholder='".$data['placeholder']."'";
	}

	$out = "<div class='form_textarea".($data['class'] != '' ? " ".$data['class'] : "")."'>";

		if($data['text'] != '')
		{
			$out .= "<label for='".$data['name']."'>".$data['text']."</label>";
		}

		/*if($data['wysiwyg'] == true)
		{
			$out .= mf_editor(stripslashes($data['value']), $data['name'], array('textarea_rows' => 5));
		}

		else
		{*/
			$out .= "<textarea name='".$data['name']."' id='".$data['name']."'".$data['xtra'].">".stripslashes($data['value'])."</textarea>";
		//}

	$out .= "</div>";

	return $out;
}
#################

function mf_editor($content, $editor_id, $data = array())
{
	if(!isset($data['class'])){			$data['class'] = "";}
	if(!isset($data['text'])){			$data['text'] = "";}
	if(!isset($data['xtra'])){			$data['xtra'] = "";}
	if(!isset($data['required'])){		$data['required'] = false;}

	$out = "";
	
	if($data['required'] && $data['text'] != '')
	{
		$data['xtra'] .= " class='required'";
	}

	if(isset($data['statusbar']))
	{
		$data['tinymce']['statusbar'] = $data['statusbar'];
		
		unset($data['statusbar']);
	}

	if(isset($data['mini_toolbar']) && $data['mini_toolbar'] == true)
	{
		$data['tinymce']['toolbar1'] = 'bold,italic,bullist,numlist,link,unlink';
		
		$data['class'] .= ($data['class'] != '' ? " " : "")."is_mini_toolbar";
	}

	if($data['class'] != '')
	{
		$out .= "<div class='mf_editor ".$data['class']."'>";
	}

		if($data['text'] != '')
		{
			$out .= "<label>".$data['text']."</label>";
		}

		if($data['xtra'] != '')
		{
			$out .= "<div".$data['xtra'].">";
		}

			//'toolbar1' => 'strikethrough,alignleft,aligncenter,alignright,wp_more,spellchecker,wp_fullscreen,wp_adv,blockquote,hr',
			//'toolbar2' => 'formatselect,underline,alignjustify,forecolor,pastetext,removeformat,charmap,outdent,indent,undo,redo,wp_help',
			//'block_formats' => 'Paragraph=p; Heading 3=h3; Heading 4=h4',
			//'quicktags' => array('buttons' => 'em,strong,link'), //false //Does not work
			//'editor_height' => '',
			//'wp_autoresize_on' => false,

			ob_start();

				wp_editor($content, $editor_id, $data);

			$out .= ob_get_clean();

		if($data['xtra'] != '')
		{
			$out .= "</div>";
		}

	if($data['class'] != '')
	{
		$out .= "</div>";
	}

	return $out;
}

############################
function show_select($data)
{
	if(!isset($data['data'])){			$data['data'] = array();}
	if(!isset($data['compare'])){		$data['compare'] = "";}
	if(!isset($data['xtra'])){			$data['xtra'] = "";}
	if(!isset($data['text'])){			$data['text'] = "";}
	if(!isset($data['minsize'])){		$data['minsize'] = 2;}
	if(!isset($data['maxsize'])){		$data['maxsize'] = 10;}
	if(!isset($data['required'])){		$data['required'] = false;}
	if(!isset($data['class'])){			$data['class'] = "";}
	if(!isset($data['suffix'])){		$data['suffix'] = "";}
	if(!isset($data['description'])){	$data['description'] = "";}

	$count_temp = count($data['data']);

	if($count_temp > 0) //isset($data['data']) && $data['data'] != ''
	{
		if(substr($data['name'], -2) == "[]")
		{
			if($count_temp > $data['maxsize'])
			{
				$size = $data['maxsize'];
			}

			else if($count_temp < $data['minsize'])
			{
				$size = $data['minsize'];
			}
			
			else
			{
				$size = $count_temp;
			}

			$data['class'] .= ($data['class'] != '' ? " " : "")."top";
			$data['xtra'] .= " multiple size='".$size."'";

			$container_class = "form_select_multiple";
		}

		else
		{
			$container_class = "form_select";
		}

		if($data['required'])
		{
			$data['xtra'] .= " required";
		}

		if($count_temp == 1 && $data['required'] && $data['text'] != '')
		{
			$out = input_hidden(array('name' => $data['name'], 'value' => $data['data'][0][0]));
		}

		else
		{
			$out = "<div class='".$container_class.($data['class'] != '' ? " ".$data['class'] : "")."'>";

				if($data['text'] != '')
				{
					$out .= "<label for='".$data['name']."'>".$data['text']."</label>";
				}

				$out .= "<select id='".preg_replace("/\[(.*)\]/", "", $data['name'])."' name='".$data['name']."'".$data['xtra'].">";

					//for($i = 0; $i < $count_temp; $i++)
					foreach($data['data'] as $key => $option)
					{
						//list($data_value, $data_text) = $data['data'][$i];

						if(is_array($option))
						{
							list($data_value, $data_text) = $option;

							do_log($data['name']." ".__("still uses the old way of inserting arrays in show_select()", 'lang_base'));
						}

						else
						{
							$data_value = $key;
							$data_text = $option;
						}

						if(substr($data_value, 0, 9) == "opt_start" && $data_value != $data_text)
						{
							$out .= "<optgroup label='".$data_text."' rel='".$data_value."'>";
						}

						else if(substr($data_value, 0, 7) == "opt_end" && $data_value != $data_text)
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

				if($data['suffix'] != '')
				{
					$out .= "&nbsp;<span class='description'>".$data['suffix']."</span>";
				}

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
	if(!isset($data['required'])){		$data['required'] = false;}
	if(!isset($data['compare'])){		$data['compare'] = 0;}
	if(!isset($data['xtra'])){			$data['xtra'] = "";}
	if(!isset($data['xtra_class'])){	$data['xtra_class'] = "";}
	if(!isset($data['switch'])){		$data['switch'] = 0;}
	if(!isset($data['suffix'])){		$data['suffix'] = "";}
	if(!isset($data['description'])){	$data['description'] = "";}

	$data['xtra'] .= $data['value'] == $data['compare'] ? " checked" : "";

	if(substr($data['name'], -1) == "]")
	{
		$is_array = true;

		$new_class = preg_replace("/\[.*?\]/", "", $data['name']);

		$this_id = $new_class."_".$data['value'];

		$data['xtra'] .= " class='".$new_class."'";
	}

	else
	{
		$is_array = false;

		$this_id = $data['name'];
	}

	if($data['required'])
	{
		$data['xtra'] .= " required";
	}

	if($data['switch'] == 1 && $data['text'] == '')
	{
		$data['xtra_class'] .= ($data['xtra_class'] != '' ? " " : "")."switch";
		$data['text'] = "<i class='fa fa-lg fa-check-square-o green checked'></i><i class='fa fa-lg fa-square-o unchecked'></i><i class='fa fa-lg fa-spin fa-spinner loading'></i>";
	}

	$out = "<div class='form_checkbox".($data['xtra_class'] != '' ? " ".$data['xtra_class'] : "")."'>
		<input type='checkbox'";

			if($data['name'] != '')
			{
				$out .= " name='".$data['name']."' id='".$this_id."'";
			}

		$out .= " value='".$data['value']."'".$data['xtra'].">";

		if($data['text'] != '')
		{
			$out .= "<label".($this_id != '' ? " for='".$this_id."'" : "").">".$data['text']."</label>";
		}

		if($data['suffix'] != '')
		{
			$out .= "&nbsp;<span class='description'>".$data['suffix']."</span>";
		}

		if($data['description'] != '')
		{
			$out .= "<p class='description'>".$data['description']."</p>";
		}

	$out .= "</div>";

	return $out;
}
#################

################################
function show_radio_input($data)
{
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

		if($data['text'] != '')
		{
			$out .= "<label for='".$data['name']."_".$data['value']."'>".$data['text']."</label>";
		}

	$out .= "</div>";

	return $out;
}
#################

######################
function show_file_field($data)
{
	$out = "";

	if(!isset($data['text'])){		$data['text'] = "";}
	if(!isset($data['class'])){		$data['class'] = "";}
	if(!isset($data['multiple'])){	$data['multiple'] = false;}
	if(!isset($data['required'])){	$data['required'] = false;}
	if(!isset($data['xtra'])){		$data['xtra'] = "";}

	if($data['required'])
	{
		$data['xtra'] .= " required";
	}

	$out .= "<div class='form_file_input".($data['class'] != '' ? " ".$data['class'] : "")."'>";

		if($data['text'] != '')
		{
			$out .= "<label for='".$data['name']."'>".$data['text']."</label>";
		}

		$out .= "<input type='file' name='".$data['name'].($data['multiple'] == true ? "[]" : "")."'".($data['multiple'] == true ? " multiple" : "").$data['xtra'].">
	</div>";

	return $out;
}
######################

######################
function show_password_field($data)
{
	$out = "";

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

	$out .= "<div class='form_password'>";

		if($data['text'] != '')
		{
			$out .= "<label for='".$data['name']."'>".$data['text']."</label>";
		}

		$out .= "<input type='password' name='".$data['name']."' value='".$data['value']."' id='".$data['name']."'".$data['max_length'].$data['xtra'].">
	</div>";

	return $out;
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
		.($data['class'] != '' ? " class='".$data['class']."'" : " class='button-primary'")
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
	if(!isset($data['callback'])){			$data['callback'] = "";}
	if(!isset($data['folder_callback'])){	$data['folder_callback'] = "";}
	if(!isset($data['limit'])){				$data['limit'] = 0;}

	if($dp = opendir($data['path']))
	{
		$count = 0;

		while(($child = readdir($dp)) !== false && ($data['limit'] == 0 || $count < $data['limit']))
		{
			if($child == '.' || $child == '..') continue;

			$file = str_replace("//", "/", $data['path'].'/'.$child);

			if(is_dir($file))
			{
				if($data['folder_callback'] != '')
				{
					if(is_callable($data['folder_callback']))
					{
						$data_temp = $data;
						$data_temp['child'] = $child;

						call_user_func($data['folder_callback'], $data_temp); //array('path' => $data['path'], 'child' => $child)
					}
				}

				else
				{
					$data_temp = $data;
					$data_temp['path'] = $file;

					get_file_info($data_temp); //array('path' => $file, 'callback' => $data['callback'])
				}
			}

			else
			{
				if(is_callable($data['callback']))
				{
					$data_temp = $data;
					$data_temp['file'] = $file;

					call_user_func($data['callback'], $data_temp); //array('path' => $data['path'], 'file' => $file)
				}
			}

			$count++;
		}

		closedir($dp);
	}
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

	$result = $wpdb->get_results($wpdb->prepare("SELECT ID, post_title FROM ".$wpdb->posts." WHERE post_type = %s AND post_status = 'publish' AND post_parent = '%d' ORDER BY menu_order ASC", $data['post_type'], $data['post_id']));

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

				$arr_data[$post_id] = $post_title;
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
	return "tel:".str_replace(array(" ", "-", "/"), "", $no);
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

function footer_base()
{
	/*if(get_option('setting_base_requests') == 1)
	{
		echo "<mf-debug>"
			.var_export($_REQUEST, true)
		."</mf-debug>";
	}*/

	/*if(get_option('setting_base_perfbar') == 1)
	{
		if(is_user_logged_in() && IS_ADMIN)
		{
			mf_enqueue_script('script_base_perfbar', plugin_dir_url(__FILE__)."perfbar_script.js");
		}
	}*/
}