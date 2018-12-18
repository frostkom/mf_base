<?php

function get_option_page_suffix($data)
{
	if(!isset($data['title'])){		$data['title'] = '';}
	if(!isset($data['content'])){	$data['content'] = '';}

	if($data['value'] > 0)
	{
		$out = "<a href='".admin_url("post.php?post=".$data['value']."&action=edit")."'><i class='fa fa-wrench fa-lg'></i></a>";
	}

	else
	{
		$out = "<a href='".admin_url("post-new.php?post_type=page".($data['title'] != '' ? "&post_title=".$data['title'] : "").($data['content'] != '' ? "&content=".$data['content'] : ""))."'><i class='fa fa-plus-circle fa-lg'></i></a>";
	}

	return $out;
}

function override_capability($data)
{
	$capability = $data['default'];

	$option = get_option('setting_admin_menu_roles');

	if(is_array($option) && count($option) > 0)
	{
		foreach($option as $key => $value)
		{
			$arr_item = explode('|', $key);

			if(count($arr_item) == 2)
			{
				$item_parent = false;
				$item_url = $arr_item[0];
				$item_name = $arr_item[1];
			}

			else
			{
				$item_parent = $arr_item[0];
				$item_url = $arr_item[1];
				$item_name = $arr_item[2];
			}

			if($data['page'] == $item_url)
			{
				$capability = $value;

				break;
			}
		}
	}

	return $capability;
}

function get_or_set_table_filter($data)
{
	if(!isset($data['prefix'])){	$data['prefix'] = '';}
	if(!isset($data['save'])){		$data['save'] = false;}
	if(!isset($data['default'])){	$data['default'] = '';}

	$meta_value = '';

	$user_id = get_current_user_id();
	$meta_key = 'meta_table_filter_'.$data['prefix'].$data['key'];

	if(isset($_GET['filter_action']) || isset($_GET[$data['key']]))
	{
		$meta_value = check_var($data['key']);

		if($data['save'] == true)
		{
			if($meta_value != '')
			{
				update_user_meta(get_current_user_id(), $meta_key, $meta_value);
			}

			else
			{
				delete_user_meta(get_current_user_id(), $meta_key, $meta_value);
			}
		}
	}

	else
	{
		$meta_value = get_the_author_meta($meta_key, $user_id);
	}

	if($meta_value == '')
	{
		$meta_value = $data['default'];
	}

	return $meta_value;
}

function show_final_size($in)
{
	$arr_suffix = array("B", "kB", "MB", "GB", "TB");

	$count_temp = count($arr_suffix);

	for($i = 0; ($in > 1024 || $i < 1) && $i < $count_temp; $i++) //Forces at least kB
	{
		$in /= 1024;
	}

	$out = strlen(round($in)) < 3 ? round($in, 1) : round($in); //If less than 3 digits, show one decimal aswell

	return $out."&nbsp;".$arr_suffix[$i];
}

function show_flot_graph($data)
{
	global $flot_count;

	if(!isset($data['type'])){				$data['type'] = 'lines';}
	if(!isset($data['settings'])){			$data['settings'] = '';}
	if(!isset($data['height'])){			$data['height'] = '';}
	if(!isset($data['title'])){				$data['title'] = '';}

	if($data['settings'] == '')
	{
		$data['settings'] = ($data['settings'] != '' ? "," : "")."legend: {position: 'nw'},
		xaxis: {mode: 'time'},
		yaxis: {
			tickFormatter: function suffixFormatter(val, axis)
			{
				return parseInt(val).toLocaleString();
			}
		}";
	}

	switch($data['type'])
	{
		case 'lines':
			$data['settings'] .= ($data['settings'] != '' ? "," : "")."points: {show: true, radius: 0.5}";
		break;
	}

	if(!($flot_count > 0))
	{
		$flot_count = 0;
	}

	$out = "";

	if(is_array($data['data']) && count($data['data']) > 0)
	{
		$plugin_include_url = plugin_dir_url(__FILE__);
		$plugin_version = get_plugin_version(__FILE__);

		//Should be moved to admin_init
		mf_enqueue_style('style_flot', $plugin_include_url."style_flot.css", $plugin_version);
		mf_enqueue_script('jquery-flot', $plugin_include_url."jquery.flot.min.0.7.js", $plugin_version);
		mf_enqueue_script('script_flot', $plugin_include_url."script_flot.js", $plugin_version);

		$style_cont = "width: 95%;";

		if($data['height'] > 0)
		{
			$style_cont .= "height: ".$data['height']."px;";
		}

		$out .= "<div id='flot_".$flot_count."' class='flot_graph'".($style_cont != '' ? " style='".$style_cont."'" : "").($data['title'] != '' ? " title='".$data['title']."'" : "")."><i class='fa fa-spinner fa-spin'></i></div>
		<script>
			function plot_flot_".$flot_count."()
			{
				jQuery.plot(jQuery('#flot_".$flot_count."'),
				[";

					$i = 0;

					foreach($data['data'] as $type_key => $arr_type)
					{
						$out .= ($i > 0 ? "," : "")."{label:'".$arr_type['label']."', data:[";

							$j = 0;

							foreach($arr_type['data'] as $point_key => $arr_point)
							{
								$data['data'][$type_key][$point_key]['date'] = (strtotime($arr_point['date']." UTC") * 1000);

								$out .= ($j > 0 ? "," : "")."[".(strtotime($arr_point['date']." UTC") * 1000).",".$arr_point['value']."]";

								$j++;
							}

						$out .= "]";

						if(isset($arr_type['yaxis']))
						{
							$out .= ", yaxis: ".$arr_type['yaxis'];
						}

						$out .= "}";

						$i++;
					}

				$out .= "],
				{series: {".$data['type'].": {show: true}},"
				."grid: {hoverable: true}"
				.($data['settings'] != '' ? ",".$data['settings'] : "")."});
			}

			if(typeof arr_flot_functions === 'undefined')
			{
				var arr_flot_functions = [];
			}

			arr_flot_functions.push('plot_flot_".$flot_count."');
		</script>";


		$flot_count++;
	}

	return $out;
}

function get_pages_from_shortcode($shortcode)
{
	global $wpdb;

	$arr_ids = array();

	$result = $wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_status = 'publish' AND post_content LIKE %s", "%".addslashes($shortcode)."%")); //post_type != 'revision' AND

	foreach($result as $r)
	{
		$arr_ids[] = $r->ID;
	}

	return $arr_ids;
}

function get_site_url_clean($data = array())
{
	global $wpdb;

	if(!isset($data['trim'])){	$data['trim'] = "";}

	$out = "";

	if(is_multisite())
	{
		if(!isset($data['id'])){	$data['id'] = $wpdb->blogid;}

		$result = get_sites(array('ID' => $data['id']));

		foreach($result as $r)
		{
			$out = $r->domain.$r->path;
			break;
		}
	}

	else
	{
		$out = remove_protocol(array('url' => get_site_url(), 'clean' => true));
	}

	if($data['trim'] != '')
	{
		$out = trim($out, $data['trim']);
	}

	return $out;
}

function get_plugin_version($file)
{
	if(!function_exists('get_plugin_data'))
	{
		require_once(ABSPATH."wp-admin/includes/plugin.php");
	}

	$plugin_dir = plugin_dir_path($file)."index.php";
	$plugin_dir = str_replace("include/", "", $plugin_dir);

	$arr_plugin_data = get_plugin_data($plugin_dir);

	return $arr_plugin_data['Version'];
}

function get_theme_version()
{
	if(function_exists('wp_get_theme'))
	{
		$arr_theme_data = wp_get_theme();
		$theme_version = $arr_theme_data['Version'];
	}

	else
	{
		$theme_version = 0;
	}

	$theme_version = int2point(point2int($theme_version) + get_option_or_default('option_theme_version', 1));

	return $theme_version;
}

function get_toggler_container($data)
{
	if(!isset($data['open'])){						$data['open'] = false;}
	if(!isset($data['rel']) || $data['rel'] == ''){	$data['rel'] = mt_rand(0, 1000);}
	if(!isset($data['icon_first'])){				$data['icon_first'] = true;}
	if(!isset($data['icon'])){						$data['icon'] = "fa fa-caret-right";}
	if(!isset($data['icon_open'])){					$data['icon_open'] = "fa fa-caret-down";}

	switch($data['type'])
	{
		case 'start':
			$icon = "<i class='".$data['icon']." fa-lg toggle_icon_closed'></i>
			<i class='".$data['icon_open']." fa-lg toggle_icon_open'></i>";
			$text = "<span>".$data['text']."</span>";

			$out = "<label class='toggler".($data['open'] ? " open" : "")."' rel='".$data['rel']."'>";

				if($data['icon_first'] == true)
				{
					$out .= $icon.$text;
				}

				else
				{
					$out .= $text.$icon;
				}

			$out .= "</label>
			<div class='toggle_container".($data['open'] ? "" : " hide")."' rel='".$data['rel']."'>";

			return $out;
		break;

		case 'end':
			return "</div>";
		break;
	}
}

function get_user_info($data = array())
{
	if(!isset($data['id'])){	$data['id'] = get_current_user_id();}
	if(!isset($data['type'])){	$data['type'] = 'name';}

	$user_data = get_userdata($data['id']);

	switch($data['type'])
	{
		case 'name':
			if(isset($user_data->display_name))
			{
				return $user_data->display_name;
			}

			else
			{
				return '';

				do_log(sprintf(__("There was no display name for %s (%d)", 'lang_base'), var_export($user_data, true), $data['id']));
			}
		break;

		case 'shortname':
		case 'short_name':
			$display_name = $user_data->display_name;

			$arr_name = explode(" ", $display_name);

			$short_name = "";

			foreach($arr_name as $name)
			{
				$short_name .= substr($name, 0, 1);
			}

			return "<span title='".$display_name."'>".$short_name."</span>";
		break;
	}
}

if(!function_exists('get_post_title'))
{
	function get_post_title($post)
	{
		return get_the_title($post);
	}
}

function contains_html($string)
{
	$string_decoded = htmlspecialchars_decode($string);

	if($string != strip_tags($string) || $string_decoded != strip_tags($string_decoded))
	{
		return true;
	}

	else
	{
		return false;
	}
}

function set_html_content_type()
{
	return 'text/html';
}

function send_email($data)
{
	global $error_text, $phpmailer;

	if(!isset($data['headers'])){		$data['headers'] = "From: ".get_bloginfo('name')." <".get_bloginfo('admin_email').">\r\n";}
	if(!isset($data['attachment'])){	$data['attachment'] = array();}
	if(!isset($data['save_log'])){		$data['save_log'] = true;}

	if($data['to'] == '')
	{
		$error_text = sprintf(__("The message had no recipient so '%s' could not be sent", 'lang_base'), $data['subject']);

		return false;
	}

	else if($data['content'] == '')
	{
		$error_text = sprintf(__("The message was empty so I could not send '%s' to '%s'", 'lang_base'), $data['subject'], $data['to']);

		return false;
	}

	else
	{
		if(contains_html($data['content']))
		{
			add_filter('wp_mail_content_type', 'set_html_content_type');

			$data['content'] = "<html><head><meta http-equiv='Content-Type' content='text/html; charset=utf-8'></head><body>".$data['content']."</body></html>";
		}

		$sent = wp_mail($data['to'], $data['subject'], $data['content'], $data['headers'], $data['attachment']);

		if($data['save_log'] == true || !$sent)
		{
			$data_temp = $data;
			unset($data_temp['content']);

			$arr_exclude = array('Priority', 'Body', 'AltBody', 'MIMEBody', 'Password', 'boundary', 'Timeout', 'Debugoutput');

			$phpmailer_temp = array();

			foreach($phpmailer as $key => $value)
			{
				if(is_array($value))
				{
					foreach($value as $key2 => $value2)
					{
						if(!in_array($key2, $arr_exclude) && trim($value2) != '')
						{
							$phpmailer_temp[$key][$key2] = $value2;
						}

						else
						{
							$phpmailer_temp[$key][$key2] = shorten_text(array('string' => htmlspecialchars($value2), 'limit' => 4));
						}
					}
				}

				else
				{
					if(!in_array($key, $arr_exclude) && trim($value) != '')
					{
						$phpmailer_temp[$key] = $value;
					}

					/*else
					{
						$phpmailer_temp[$key] = shorten_text(array('string' => htmlspecialchars($value), 'limit' => 4));
					}*/
				}
			}
		}

		if($sent)
		{
			if($data['save_log'] == true)
			{
				do_log(sprintf(__("Message sent: %s", 'lang_base'), var_export($data_temp, true).", ".var_export($phpmailer_temp, true)), 'auto-draft');
			}

			if(isset($phpmailer->From))
			{
				do_action('sent_email', $phpmailer->From);
			}
		}

		else
		{
			do_log(sprintf(__("I could not send the email to %s", 'lang_base'), var_export($data_temp, true).", ".var_export($phpmailer_temp, true)));
		}

		return $sent;
	}
}

function shorten_text($data)
{
	if(!isset($data['string'])){	$data['string'] = $data['text'];}
	if(!isset($data['count'])){		$data['count'] = false;}

	$out = "";

	if(strlen($data['string']) > $data['limit'])
	{
		$out = trim(mb_substr($data['string'], 0, $data['limit']))."&hellip;";

		if($data['count'] == true)
		{
			$out .= " (".strlen($data['string']).")";
		}
	}

	else
	{
		$out = $data['string'];
	}

	return $out;
}

function replace_post_type($data)
{
	global $wpdb;

	$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->posts." SET post_type = %s WHERE post_type = %s", $data['new'], $data['old']));
}

function replace_option($data)
{
	$option_old = get_option($data['old']);

	if($option_old != '')
	{
		update_option($data['new'], $option_old);
		delete_option($data['old']);
	}
}

function replace_user_meta($data)
{
	if(!isset($data['single'])){	$data['single'] = true;}

	$users = get_users(array('fields' => array('ID')));

	foreach($users as $user)
	{
		$meta_old = get_user_meta($user->ID, $data['old'], $data['single']);

		if($meta_old != '')
		{
			update_user_meta($user->ID, $data['new'], $meta_old);
			delete_user_meta($user->ID, $data['old']);
		}
	}
}

function mf_uninstall_uploads($data, $force_main_uploads)
{
	if($data['uploads'] != '')
	{
		list($upload_path, $upload_url) = get_uploads_folder($data['uploads'], $force_main_uploads);

		if($upload_path != '')
		{
			do_log("Delete the folder ".$upload_path);

			/*get_file_info(array('path' => $upload_path, 'callback' => 'delete_files', 'time_limit' => 0));

			rmdir($upload_path);*/
		}
	}
}

function mf_uninstall_meta($data)
{
	if(count($data['meta']) > 0)
	{
		$users = get_users(array('fields' => array('ID')));

		foreach($users as $user)
		{
			foreach($data['meta'] as $meta_key)
			{
				delete_user_meta($user->ID, $meta_key);
			}
		}
	}
}

function mf_uninstall_post_types($data)
{
	global $wpdb;

	if(count($data['post_types']) > 0)
	{
		foreach($data['post_types'] as $post_type)
		{
			$i = 0;

			$result = $wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_type = %s AND post_status != 'trash'", $post_type));

			foreach($result as $r)
			{
				wp_trash_post($r->ID);

				$i++;

				if($i % 100 == 0)
				{
					sleep(0.1);
					set_time_limit(60);
				}
			}
		}
	}
}

function mf_uninstall_options($data)
{
	if(count($data['options']) > 0)
	{
		foreach($data['options'] as $option)
		{
			delete_option($option);
		}
	}
}

function does_table_exist($table)
{
	global $wpdb;

	$wpdb->get_results($wpdb->prepare("SHOW TABLES LIKE %s", $table));

	return ($wpdb->num_rows > 0);
}

function mf_uninstall_tables($data)
{
	global $wpdb;

	if(count($data['tables']) > 0)
	{
		foreach($data['tables'] as $table)
		{
			if(does_table_exist($wpdb->prefix.$table))
			{
				$wpdb->query("DELETE FROM ".$wpdb->prefix.$table." WHERE 1 = 1");
				$wpdb->query("TRUNCATE TABLE ".$wpdb->prefix.$table);
				$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix.$table);

				if(does_table_exist($wpdb->prefix.$table))
				{
					$wpdb->get_results("SELECT 1 FROM ".$wpdb->prefix.$table." LIMIT 0, 1");

					if($wpdb->num_rows > 0)
					{
						do_log(sprintf(__("I was not allowed to drop %s and it still has data"), $wpdb->prefix.$table));
					}

					/*else
					{
						do_log(sprintf(__("I was not allowed to drop %s but at least it is empty now"), $wpdb->prefix.$table));
					}*/
				}
			}
		}
	}
}

function mf_uninstall_plugin($data)
{
	global $wpdb;

	if(!isset($data['uploads'])){			$data['uploads'] = "";}
	if(!isset($data['options'])){			$data['options'] = array();}
	if(!isset($data['meta'])){				$data['meta'] = array();}
	if(!isset($data['post_types'])){		$data['post_types'] = array();}
	if(!isset($data['tables'])){			$data['tables'] = array();}

	if(is_multisite())
	{
		$result = get_sites();

		foreach($result as $r)
		{
			switch_to_blog($r->blog_id);

			mf_uninstall_uploads($data, false);
			mf_uninstall_options($data);
			mf_uninstall_post_types($data);
			mf_uninstall_tables($data);

			restore_current_blog();
		}
	}

	else
	{
		mf_uninstall_options($data);
		mf_uninstall_post_types($data);
		mf_uninstall_tables($data);
	}

	mf_uninstall_meta($data);
	mf_uninstall_uploads($data, true);
}

function is_domain_valid($email, $record = 'MX')
{
	list($user, $domain) = explode('@', $email);

	return checkdnsrr($domain, $record);
}

function get_post_meta_or_default($post_id, $key = '', $single = false, $default = '')
{
	$post_meta = get_post_meta($post_id, $key, $single);

	if($post_meta == '' && $default != '')
	{
		$post_meta = $default;
	}

	return $post_meta;
}

function get_site_option_or_default($key, $default = '')
{
	$option = get_site_option($key);

	if($option == '' && $default != '')
	{
		$option = $default;
	}

	return $option;
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

function render_image_tag($data)
{
	$out = "";

	if(!isset($data['id'])){	$data['id'] = 0;}
	if(!isset($data['src'])){	$data['src'] = '';}
	if(!isset($data['size'])){	$data['size'] = 'full';}

	if(!($data['id'] > 0) && $data['src'] != '')
	{
		global $wpdb;

		$attachment_id = $wpdb->get_var($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE guid = %s", $data['src']));

		if($attachment_id > 0)
		{
			$data['id'] = $attachment_id;
		}
	}

	if($data['id'] > 0)
	{
		$out .= wp_get_attachment_image($data['id'], $data['size']);
	}

	else if($data['src'] != '')
	{
		$out .= "<img src='".$data['src']."'>";
	}

	return $out;
}

function get_post_meta_file_src($data)
{
	if(!isset($data['is_image'])){		$data['is_image'] = true;}
	if(!isset($data['image_size'])){	$data['image_size'] = 'full';} //thumbnail, medium, large, or full
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

function time_between_dates($data)
{
	if(!isset($data['type'])){		$data['type'] = 'round';}
	if(!isset($data['return'])){	$data['return'] = 'days';}

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
		case 'ceil':			$out = ceil($out);		break;
		default: case 'round':	$out = round($out);		break;
		case 'floor':			$out = floor($out);		break;
	}

	return $out;
}

function delete_files($data)
{
	if(!isset($data['time_limit'])){	$data['time_limit'] = 60 * 60 * 24 * 2;} //2 days

	$time_now = time();
	$time_file = @filemtime($data['file']);

	if($data['time_limit'] == 0 || ($time_now - $time_file >= $data['time_limit']))
	{
		@unlink($data['file']);

		if(!preg_match("/(uploads\/(mf_form|mf_group)\/)/", $data['file']))
		{
			do_log("Removed File: ".$data['file']);
		}
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

function get_uploads_folder($subfolder = '', $force_main_uploads = true)
{
	global $error_text;

	$upload_dir = wp_upload_dir();

	if($force_main_uploads == true && is_multisite())
	{
		@list($rest, $sites_sub) = explode("/uploads/sites/", $upload_dir['basedir'], 2);

		if($sites_sub != '')
		{
			$upload_dir['basedir'] = str_replace("/sites/".$sites_sub, "", $upload_dir['basedir']);
			$upload_dir['baseurl'] = str_replace("/sites/".$sites_sub, "", $upload_dir['baseurl']);
		}
	}

	$upload_path = $upload_dir['basedir']."/".($subfolder != '' ? $subfolder."/" : "");
	$upload_url = $upload_dir['baseurl']."/".($subfolder != '' ? $subfolder."/" : "");

	if($subfolder != '')
	{
		$dir_exists = true;

		if(!is_dir($upload_path) && !is_file($upload_path))
		{
			if(!@mkdir($upload_path, 0755, true))
			{
				$dir_exists = false;
			}
		}

		if($dir_exists == false)
		{
			$error_text = sprintf(__("Could not create %s in uploads. Please add the correct rights for the script to create a new subfolder", 'lang_base'), $subfolder);

			$upload_path = $upload_url = "";
		}
	}

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
		$file_name = sanitize_title_with_dashes(sanitize_title(str_replace(".".$file_extension, "", $file_name)))."-".date("dHis").".".$file_extension;

		$upload_dir = wp_upload_dir();

		$temp_file = $upload_dir['path']."/".$file_name;
		$file_url = $upload_dir['url']."/".$file_name;

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

	return $intFileID;
}

function do_log($data, $action = 'publish')
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

	else if($action == 'publish')
	{
		error_log($data);
	}
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

	if(!isset($data['table_prefix'])){	$data['table_prefix'] = $wpdb->prefix;}
	if(!isset($data['child_tables'])){	$data['child_tables'] = array();}

	$empty_trash_days = defined('EMPTY_TRASH_DAYS') ? EMPTY_TRASH_DAYS : 30;

	$data['field_prefix'] = esc_sql($data['field_prefix']);
	$data['table'] = esc_sql($data['table']);

	$result = $wpdb->get_results("SELECT ".$data['field_prefix']."ID AS ID FROM ".$data['table_prefix'].$data['table']." WHERE ".$data['field_prefix']."Deleted = '1' AND ".$data['field_prefix']."DeletedDate < DATE_SUB(NOW(), INTERVAL ".$empty_trash_days." DAY)");

	foreach($result as $r)
	{
		$intID = $r->ID;

		$rows = 0;

		foreach($data['child_tables'] as $child_table => $child_table_type)
		{
			if($child_table_type['action'] == "trash" && does_table_exist($data['table_prefix'].$child_table))
			{
				$wpdb->get_results($wpdb->prepare("SELECT ".$data['field_prefix']."ID FROM ".$data['table_prefix'].$child_table." WHERE ".$data['field_prefix']."ID = '%d' LIMIT 0, 1", $intID));
				$rows_temp = $wpdb->num_rows;

				if($rows_temp > 0)
				{
					$wpdb->query($wpdb->prepare("UPDATE ".$data['table_prefix'].$child_table." SET ".$child_table_type['field_prefix']."Deleted = '1', ".$child_table_type['field_prefix']."DeletedDate = NOW() WHERE ".$data['field_prefix']."ID = '%d' AND ".$child_table_type['field_prefix']."Deleted = '0'", $intID));

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
					$wpdb->query($wpdb->prepare("DELETE FROM ".$data['table_prefix'].$child_table." WHERE ".$data['field_prefix']."ID = '%d'", $intID));
				}
			}

			$wpdb->query($wpdb->prepare("DELETE FROM ".$data['table_prefix'].$data['table']." WHERE ".$data['field_prefix']."ID = '%d'", $intID));
		}
	}
}

function get_file_icon($data) //$file
{
	if(!is_array($data))
	{
		$data = array(
			'file' => $data,
		);
	}

	if(!isset($data['size'])){		$data['size'] = "fa-lg";}

	$suffix = get_file_suffix($data['file']);

	switch($suffix)
	{
		default:														$class = "fa fa-file";					break;

		case 'pdf':														$class = "fa fa-file-pdf";				break;
		case 'mp3': case 'ogg':											$class = "fa fa-file-audio";			break;
		case 'xls': case 'xlsx':										$class = "fa fa-file-excel";			break;
		case 'css':														$class = "fa fa-file-code";				break;
		case 'jpg': case 'jpeg': case 'png': case 'gif': case 'tif':	$class = "fa fa-file-image";			break;
		case 'ppt': case 'pptx':										$class = "fa fa-file-powerpoint";		break;
		case 'wmv': case 'avi':	case 'mpg':								$class = "fa fa-file-video";			break;
		case 'doc': case 'docx':										$class = "fa fa-file-word";				break;
		case 'zip': case 'tar':											$class = "fa fa-file-archive";			break;
		case 'txt':														$class = "fa fa-file-alt";				break;
	}

	return "<i class='".$class." ".$data['size']."'></i>";
}

// Use wp_check_filetype('image.jpg') instead?
function get_file_suffix($file, $force_last = false)
{
	if($force_last == false && preg_match("/\?/", $file))
	{
		list($file, $rest) = explode("?", $file, 2);
	}

	$arr_file_name = explode(".", $file);

	$suffix = $arr_file_name[count($arr_file_name) - 1];

	return $suffix;
}

function get_media_library($data)
{
	if(!isset($data['type'])){			$data['type'] = false;}
	if(!isset($data['multiple'])){		$data['multiple'] = false;}
	if(!isset($data['label'])){			$data['label'] = '';}
	if(!isset($data['name'])){			$data['name'] = '';}
	if(!isset($data['return_to'])){		$data['return_to'] = '';}
	if(!isset($data['return_type'])){	$data['return_type'] = '';}
	if(!isset($data['value'])){			$data['value'] = '';}
	if(!isset($data['description'])){	$data['description'] = '';}

	$add_file_text = __("Add File", 'lang_base');
	$change_file_text = __("Change File", 'lang_base');
	$insert_file_text = __("Insert File", 'lang_base');
	$insert_text = __("Insert", 'lang_base');

	$plugin_include_url = plugin_dir_url(__FILE__);
	$plugin_version = get_plugin_version(__FILE__);

	mf_enqueue_style('style_media_library', $plugin_include_url."style_media_library.css", $plugin_version);

	wp_enqueue_media();
	mf_enqueue_script('script_media_library', $plugin_include_url."script_media_library.js", array(
		'add_file_text' => $add_file_text, 'change_file_text' => $change_file_text, 'insert_file_text' => $insert_file_text, 'insert_text' => $insert_text,
	), $plugin_version);

	$out = "<div class='mf_media_library' data-type='".$data['type']."' data-multiple='".$data['multiple']."' data-return_to='".$data['return_to']."' data-return_type='".$data['return_type']."'>
		<div>";

			if($data['label'] != '')
			{
				$out .= "<label>".$data['label']."</label>";
			}

			if($data['name'] != '')
			{
				$filetype = in_array(get_file_suffix($data['value']), array('gif', 'jpg', 'jpeg', 'png')) ? 'image' : 'file';

				$out .= "<div".($data['value'] != '' ? "" : " class='hide'").">
					<img src='".$data['value']."'".($filetype == 'image' ? "" : " class='hide'").">
					<span".($filetype == 'file' ? "" : " class='hide'")."><i class='fa fa-file fa-5x' title='".$data['value']."'></i></span>
					<a href='#' rel='confirm'><i class='fa fa-trash fa-lg red'></i></a>
				</div>";
			}

			$out .= show_button(array('type' => 'button', 'text' => ($data['value'] != '' ? $change_file_text : $add_file_text), 'class' => "button"));

			if($data['name'] != '')
			{
				$out .= input_hidden(array('name' => $data['name'], 'value' => $data['value']));
			}

		$out .= "</div>";

		if($data['description'] != '')
		{
			$out .= "<p class='description'>".$data['description']."</p>";
		}

	$out .= "</div>";

	return $out;
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
		$plugin_include_url = plugin_dir_url(__FILE__);
		$plugin_version = get_plugin_version(__FILE__);

		mf_enqueue_style('style_media_button', $plugin_include_url."style_media_button.css", $plugin_version);
		mf_enqueue_script('script_media_button', $plugin_include_url."script_media_button.js", array(
			'multiple' => $data['multiple'],
			'no_attachment_link' => __("The Media Library did not return a link to the file you added. Please try again and make sure that 'Link To' is set to 'Media File'", 'lang_base'),
			'unknown_title' => __("Unknown title", 'lang_base'),
		), $plugin_version);

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
			//."<textarea name='".$data['name']."' class='mf_media_urls'>".$data['value']."</textarea>"
			.input_hidden(array('name' => $data['name'], 'value' => $data['value'], 'allow_empty' => true, 'xtra' => "class='mf_media_urls'"))
		."</div>";
	}

	return $out;
}

function get_attachment_to_send($string)
{
	global $wpdb, $error_text;

	$arr_ids = $arr_files = array();

	if($string != '')
	{
		$arr_attachments = explode(",", $string);

		foreach($arr_attachments as $attachment)
		{
			@list($file_name, $file_url, $file_id) = explode("|", $attachment);

			if($file_id > 0)
			{
				$arr_ids[] = $file_id;
			}

			else if($file_name != '')
			{
				$id_temp = $wpdb->get_var($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_type = %s AND (post_title = %s OR post_name = %s)", 'attachment', $file_name, $file_name));

				if($id_temp > 0)
				{
					$arr_ids[] = $id_temp;
				}

				/*else
				{
					do_log(__("I could not get the ID from the filename", 'lang_base')." (".$wpdb->last_query.")");
				}*/
			}

			if($file_url != '')
			{
				$file_url = WP_CONTENT_DIR.str_replace(site_url()."/wp-content", "", $file_url);

				if(file_exists($file_url))
				{
					$arr_files[] = $file_url;
				}
			}
		}

		if(count($arr_ids) == 0 && count($arr_files) == 0)
		{
			$error_text = sprintf(__("The file '%s' could not be found in the DB", 'lang_base'), $string);
		}
	}

	return array($arr_files, $arr_ids);
}

function get_attachment_data_by_id($id)
{
	global $wpdb;

	$result = $wpdb->get_results($wpdb->prepare("SELECT post_title, guid FROM ".$wpdb->posts." WHERE post_type = 'attachment' AND ID = '%d' LIMIT 0, 1", $id));

	if($wpdb->num_rows > 0)
	{
		$r = $result[0];

		return array($r->post_title, $r->guid);
	}
}

function mf_format_number($in, $dec = 2)
{
	if(is_string($in))
	{
		$in = (float)$in;
	}

	$out = number_format($in, 0, '.', '') == $in ? number_format($in, 0, '.', ' ') : number_format($in, $dec, '.', ' ');

	return $out;
}

function mf_get_post_content($id, $field = 'post_content')
{
	global $wpdb;

	return $wpdb->get_var($wpdb->prepare("SELECT ".$field." FROM ".$wpdb->posts." WHERE ID = '%d'", $id));
}

function get_install_link_tags($require_url, $required_name)
{
	if($require_url == '')
	{
		if(is_multisite())
		{
			$require_url = network_admin_url("plugin-install.php?tab=search&type=term&s=".$required_name);
		}

		else
		{
			$require_url = admin_url("plugin-install.php?tab=search&s=".$required_name);
		}
	}

	return array("<a href='".$require_url."'>", "</a>");
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

function require_plugin($required_path, $required_name, $require_url = "")
{
	if(is_admin() && function_exists('is_plugin_active') && !is_plugin_active($required_path))
	{
		list($a_start, $a_end) = get_install_link_tags($require_url, $required_name);

		mf_trigger_error(sprintf(__("You need to install the plugin %s%s%s first", 'lang_base'), $a_start, $required_name, $a_end), E_USER_ERROR);
	}
}

function get_current_user_role($id = 0)
{
	if(!($id > 0))
	{
		$id = get_current_user_id();
	}

	$user_data = get_userdata($id);

	return isset($user_data->roles[0]) ? $user_data->roles[0] : "(".__("Unknown", 'lang_base').")";
}

//main_version * 10000 + minor_version * 100 + sub_version. For example, 4.1.0 is returned as 40100
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

function point2int($in)
{
	$str_version = 0;
	$multiplier = 1;

	if($in != '')
	{
		$arr_version = explode(".", $in);

		$count_temp = count($arr_version);

		while($count_temp < 3)
		{
			$arr_version[] = 0;

			$count_temp++;
		}

		for($i = 1; $i <= $count_temp; $i++)
		{
			$str_version += $arr_version[$count_temp - $i] * $multiplier;

			$multiplier *= 100;
		}
	}

	return $str_version;
}

function get_next_cron($raw = false)
{
	$date_next_schedule = date("Y-m-d H:i:s", wp_next_scheduled('cron_base'));

	$mins = time_between_dates(array('start' => date("Y-m-d H:i:s"), 'end' => $date_next_schedule, 'type' => 'round', 'return' => 'minutes'));

	if($raw == true)
	{
		return $date_next_schedule;
	}

	else
	{
		if($mins > 0 && $mins < 60)
		{
			return sprintf(($mins == 1 ? __("in %d minute", 'lang_base') : __("in %d minutes", 'lang_base')), $mins);
		}

		else if($mins == 0)
		{
			return __("at any moment", 'lang_base');
		}

		else
		{
			return format_date($date_next_schedule);
		}
	}
}

function show_settings_fields($data)
{
	if(!isset($data['area'])){		$data['area'] = '';}
	if(!isset($data['object'])){	$data['object'] = '';}
	if(!isset($data['settings'])){	$data['settings'] = array();}
	if(!isset($data['args'])){		$data['args'] = array();}
	if(!isset($data['callback'])){	$data['callback'] = '';}

	foreach($data['settings'] as $handle => $text)
	{
		if(preg_match("/\|/", $handle))
		{
			list($handle_parent, $handle_child) = explode("|", $handle);

			$handle = $handle_parent.($handle_child != '' ? "_".$handle_child : '');
			$handle_callback = $handle_parent."_callback";

			$data['args'] = array('child' => $handle_child);
		}

		else
		{
			$handle_callback = $handle."_callback";
		}

		if($data['object'] != '')
		{
			add_settings_field($handle, $text, array($data['object'], $handle_callback), BASE_OPTIONS_PAGE, $data['area'], $data['args']);
		}

		else
		{
			add_settings_field($handle, $text, $handle_callback, BASE_OPTIONS_PAGE, $data['area'], $data['args']);
		}

		register_setting(BASE_OPTIONS_PAGE, $handle, $data['callback']);
	}
}

function get_setting_key($function_name, $args = array())
{
	if(isset($args['child']) && $args['child'] != '')
	{
		$function_name .= "_".$args['child'];
	}

	return str_replace("_callback", "", $function_name);
}

function settings_save_site_wide($setting_key)
{
	if(IS_SUPER_ADMIN && is_multisite() && isset($_REQUEST['settings-updated']) && $_REQUEST['settings-updated'] == true)
	{
		$option = get_option($setting_key);

		update_site_option($setting_key, $option);

		$result = get_sites();

		foreach($result as $r)
		{
			delete_blog_option($r->blog_id, $setting_key);
		}
	}
}

function settings_header($id, $title)
{
	return "<div id='".$id."' class='hide'><a href='#".$id."'><h3>".$title."</h3></a></div>";
}

function remove_protocol($data)
{
	if(!is_array($data)){			$data = array('url' => $data);}

	if(!isset($data['clean'])){		$data['clean'] = false;}
	if(!isset($data['trim'])){		$data['trim'] = false;}

	if($data['clean'] == true)
	{
		$data['url'] = str_replace(array("http://", "https://"), "", $data['url']);
	}

	else
	{
		$data['url'] = str_replace(array("http:", "https:"), "", $data['url']);
	}

	if($data['trim'] == true)
	{
		$data['url'] = trim($data['url'], "/");
	}

	return $data['url'];
}

function mf_enqueue_style($handle, $file = "", $dep = array(), $version = false)
{
	if(!is_array($dep))
	{
		$version = $dep;
		$dep = array();
	}

	$file = remove_protocol(array('url' => $file));

	do_action('mf_enqueue_style', array('handle' => $handle, 'file' => $file, 'version' => $version));

	wp_enqueue_style($handle, $file, $dep, $version);
}

function mf_enqueue_script($handle, $file = "", $translation = array(), $version = false, $add2array = true)
{
	if(!is_array($translation))
	{
		$version = $translation;
		$translation = array();
	}

	$file = remove_protocol(array('url' => $file));

	if($add2array == true)
	{
		do_action('mf_enqueue_script', array('handle' => $handle, 'file' => $file, 'translation' => $translation, 'version' => $version));
	}

	if(count($translation) > 0)
	{
		wp_register_script($handle, $file, array('jquery'), $version, true);
		wp_localize_script($handle, $handle, $translation);
		wp_enqueue_script($handle);
	}

	else if($file != '')
	{
		wp_enqueue_script($handle, $file, array('jquery'), $version, true);
	}

	else
	{
		wp_enqueue_script($handle);
	}
}

function roles_option_to_array($option = '')
{
	global $wpdb;

	if($option == '' || is_array($option) && count($option) == 0)
	{
		$option = get_option($wpdb->prefix.'user_roles');
	}

	$roles = array();

	foreach($option as $key => $value)
	{
		$roles[$key] = $value['name'];
	}

	return $roles;
}

function get_all_roles($data = array())
{
	global $wpdb, $wp_roles;

	if(!isset($data['orig'])){	$data['orig'] = false;}

	if($data['orig'] == true)
	{
		$roles = roles_option_to_array(get_option($wpdb->prefix.'user_roles_orig'));
	}

	else
	{
		if(function_exists('is_plugin_active') && is_plugin_active('mf_users/index.php'))
		{
			$obj_users = new mf_users();
			$obj_users->hide_roles();
		}

		$roles = $wp_roles->get_names();
	}

	if(count($roles) == 0)
	{
		do_log(__("I could not find any roles for this site...?", 'lang_base'));
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
	if(!isset($data['choose_here_text'])){	$data['choose_here_text'] = __("Choose Here", 'lang_base');}
	if(!isset($data['return_integer'])){	$data['return_integer'] = false;}

	$arr_data = array();

	if($data['add_choose_here'] == true)
	{
		$arr_data[''] = "-- ".$data['choose_here_text']." --";
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
	if(!isset($data['use_capability'])){	$data['use_capability'] = true;}

	if($data['add_choose_here'] == true)
	{
		$data['array'][''] = "-- ".__("Choose Here", 'lang_base')." --";
	}

	if(is_multisite() && $data['use_capability'] == true)
	{
		$data['array']['update_core'] = __("Super Admin", 'lang_base');
	}

	$roles = get_all_roles();

	foreach($roles as $key => $value)
	{
		if($data['use_capability'] == true)
		{
			$key = get_role_first_capability($key);
		}

		if(!isset($data['array'][$key]) && $key != '')
		{
			$data['array'][$key] = $value;
		}
	}

	return $data['array'];
}

function get_users_for_select($data = array())
{
	if(!isset($data['add_choose_here'])){	$data['add_choose_here'] = true;}
	if(!isset($data['choose_here_text'])){	$data['choose_here_text'] = __("Choose Here", 'lang_base');}
	if(!isset($data['callback'])){			$data['callback'] = '';}

	$users = get_users(array(
		'orderby' => 'display_name',
		'order' => 'ASC',
		'fields' => array('ID', 'display_name', 'user_email'),
	));

	$arr_data = array();

	if($data['add_choose_here'] == true)
	{
		$arr_data[''] = "-- ".$data['choose_here_text']." --";
	}

	foreach($users as $user)
	{
		if($data['callback'] != '' && is_callable($data['callback']))
		{
			$arr_data = call_user_func($data['callback'], $data, $user, $arr_data);
		}

		else
		{
			$arr_data[$user->ID] = $user->display_name;
		}
	}

	return $arr_data;
}

function get_post_types_for_select($data = array())
{
	if(!isset($data['include'])){		$data['include'] = array('ids', 'types', 'special');}
	if(!isset($data['post_status'])){	$data['post_status'] = 'publish';}
	if(!isset($data['add_is'])){		$data['add_is'] = true;}

	$opt_groups = is_array($data['include']) && count($data['include']) > 1;

	$arr_data = array();

	if(in_array('ids', $data['include']))
	{
		$arr_pages = array();
		get_post_children(array('post_status' => $data['post_status']), $arr_pages);

		if(count($arr_pages) > 0)
		{
			if($opt_groups == true)
			{
				$arr_data['opt_start_pages'] = __("Pages", 'lang_base');
			}

				foreach($arr_pages as $post_id => $post_title)
				{
					if($data['add_is'] == true)
					{
						$arr_data['is_page('.$post_id.')'] = $post_title;
					}

					else
					{
						$arr_data[$post_id] = $post_title;
					}
				}

			if($opt_groups == true)
			{
				$arr_data['opt_end_pages'] = "";
			}
		}
	}

	if($opt_groups == true)
	{
		$arr_data['opt_start_post_types'] = __("Post Types", 'lang_base');
	}

		if(in_array('types', $data['include']))
		{
			foreach(get_post_types(array('exclude_from_search' => false), 'objects') as $post_type) //'public' => true, 'publicly_queryable' => true,
			{
				if(!in_array($post_type->name, array('attachment')))
				{
					if($data['add_is'] == true)
					{
						$arr_data['is_singular("'.$post_type->name.'")'] = $post_type->label;
					}

					else
					{
						$arr_data[$post_type->name] = $post_type->label;
					}
				}
			}
		}

	if($opt_groups == true)
	{
		$arr_data['opt_end_post_types'] = "";
	}

	if(in_array('special', $data['include']))
	{
		$arr_data['is_404()'] = __("404", 'lang_base');
		//$arr_data['is_archive()'] = __("Archive", 'lang_base');

		$arr_categories = get_categories(array('hierarchical' => 1, 'hide_empty' => 1));

		if(count($arr_categories) > 0)
		{
			$arr_data['is_category()'] = __("Category", 'lang_base');

			if(count($arr_categories) > 1)
			{
				if($opt_groups == true)
				{
					$arr_data['opt_start_categories'] = __("Categories", 'lang_base');
				}

					foreach($arr_categories as $category)
					{
						$arr_data['is_category('.$category->cat_ID.')'] = ($category->parent > 0 ? "&nbsp;&nbsp;&nbsp;" : "").$category->name;
					}

				if($opt_groups == true)
				{
					$arr_data['opt_end_categories'] = "";
				}
			}
		}

		//$arr_data['is_front_page()'] = __("Front Page", 'lang_base');
		$arr_data['is_home()'] = __("Home", 'lang_base');
		//$arr_data['is_page()'] = __("Page", 'lang_base');
		$arr_data['is_search()'] = __("Search", 'lang_base');
		//$arr_data['is_single()'] = __("Single", 'lang_base');
		//$arr_data['is_sticky()'] = __("Sticky", 'lang_base');
	}

	return $arr_data;
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
		$arr_include = get_post_types(array('public' => true, 'exclude_from_search' => false), 'names');

		if(count($arr_include) > 0)
		{
			$query_where .= " AND post_type IN('".implode("','", $arr_include)."')";
		}
	}

	$result = $wpdb->get_results($wpdb->prepare("SELECT ID, post_type, post_title FROM ".$wpdb->posts." WHERE post_status = %s AND post_parent = '%d'".$query_where." ORDER BY post_type ASC, ".esc_sql($data['order']), $data['post_status'], $data['post_parent']));

	$post_type_temp = $data['post_type'];
	$opt_start_open = false;

	$arr_data = array();

	if($data['add_choose_here'] == true)
	{
		$arr_data[''] = "-- ".__("Choose Here", 'lang_base')." --";
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
				$arr_data['opt_end_'.$post_type] = "";
			}

			$arr_data['opt_start_'.$post_type] = "-- ".$post_type." --";
			$opt_start_open = true;

			$post_type_temp = $post_type;
		}

		$arr_data[$post_id] = $post_title;
	}

	return $arr_data;
}

function get_categories_for_select($data = array())
{
	if(!isset($data['add_choose_here'])){	$data['add_choose_here'] = false;}
	if(!isset($data['hierarchical'])){		$data['hierarchical'] = true;}

	$arr_data = array();

	if($data['add_choose_here'] == true)
	{
		$arr_data[''] = "-- ".__("Choose Here", 'lang_base')." --";
	}

	$arr_categories = get_categories(array(
		'hierarchical' => $data['hierarchical'],
		'hide_empty' => 1,
	));

	foreach($arr_categories as $category)
	{
		$arr_data[$category->cat_ID] = ($data['hierarchical'] && $category->parent > 0 ? "&nbsp;&nbsp;&nbsp;" : "").$category->name;
	}

	return $arr_data;
}

function get_sidebars_for_select()
{
	$arr_data = array(
		'' => "-- ".__("Choose Here", 'lang_base')." --"
	);

	foreach($GLOBALS['wp_registered_sidebars'] as $sidebar)
	{
		$arr_data[$sidebar['id']] = $sidebar['name'];
	}

	return $arr_data;
}

//Sort array
#########################
# array			array(array("firstname" => "Martin", "surname" => "Fors"))
# on			Ex. surname
# order			asc/desc
# keep_index	true/false
#########################
function array_sort($data)
{
	if(!isset($data['on'])){			$data['on'] = 0;}
	if(!isset($data['order'])){			$data['order'] = "asc";}
	if(!isset($data['keep_index'])){	$data['keep_index'] = false;}

	$new_array = array();
	$sortable_array = array();

	if(count($data['array']) > 0)
	{
		foreach($data['array'] as $k => $v)
		{
			if(is_array($v))
			{
				foreach($v as $k2 => $v2)
				{
					if($k2 == $data['on'])
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

		switch($data['order'])
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
				$new_array[$k] = $data['array'][$k];
			}

			else
			{
				$new_array[] = $data['array'][$k];
			}
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

	if($http == true && $value != '')
	{
		if(substr($value, 0, 2) == "//")
		{
			$value = (IS_HTTPS ? "https:" : "http:").$value;
		}

		if(substr($value, 0, 1) != "/")
		{
			$arr_prefix = array('http:', 'https:', 'ftp:', 'mms:');

			if(!preg_match('/('. implode('|', $arr_prefix) .')/', $value))
			{
				$value = "http://".$value;
			}
		}
	}

	return $value;
}
#################

function get_url_content($data = array(), $catch_head = false, $password = '', $post = '', $post_data = '')
{
	/*if(!is_array($data))
	{
		do_log("get_url_content(): ".$data);

		$data = array(
			'url' => $data,
		);
	}*/

	if(!isset($data['follow_redirect'])){	$data['follow_redirect'] = false;}
	if(!isset($data['catch_head'])){		$data['catch_head'] = $catch_head;}
	if(!isset($data['headers'])){			$data['headers'] = array();}
	if(!isset($data['request'])){			$data['request'] = 'get';}
	if(!isset($data['content_type'])){		$data['content_type'] = '';}
	if(!isset($data['password'])){			$data['password'] = $password;}
	if(!isset($data['post_data'])){			$data['post_data'] = $post_data;}
	if(!isset($data['cert_path'])){			$data['cert_path'] = '';} // Deprecated
	if(!isset($data['ca_path'])){			$data['ca_path'] = $data['cert_path'];}
	if(!isset($data['ssl_cert_path'])){		$data['ssl_cert_path'] = '';}
	if(!isset($data['ssl_key_path'])){		$data['ssl_key_path'] = '';}

	$data['url'] = validate_url($data['url'], false);

	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, $data['url']);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLINFO_HEADER_OUT, true);

	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; sv-SE; rv:1.9.2.3) Gecko/20100401 Firefox/3.6.10");

	if(ini_get('open_basedir') == '' && ini_get('safe_mode') == 'Off')
	{
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	}

	if($data['ca_path'] != '')
	{
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($ch, CURLOPT_CAINFO, $data['ca_path']); // The name of a file holding one or more certificates to verify the peer with.
		curl_setopt($ch, CURLOPT_CAPATH, $data['ca_path']); // A directory that holds multiple CA certificates
	}

	if($data['ssl_cert_path'] != '')
	{
		curl_setopt($ch, CURLOPT_SSLCERT, $data['ssl_cert_path']);
		//curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM'); // "PEM" (default), "DER", "ENG"
		//curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $certPass);
	}

	if($data['ssl_key_path'] != '')
	{
		curl_setopt($ch, CURLOPT_SSLKEY, $data['ssl_key_path']);
		//curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM'); // "PEM" (default), "DER", "ENG"
		//curl_setopt($ch, CURLOPT_SSLKEYPASSWD, $keyPass);
	}

	if($data['password'] != '')
	{
		curl_setopt($ch, CURLOPT_USERPWD, $data['password']);
	}

	if($data['content_type'] != '')
	{
		$data['headers'][] = 'Accept: '.$data['content_type'];
		$data['headers'][] = 'Content-Type: '.$data['content_type'];
	}

	if(count($data['headers']) > 0)
	{
		curl_setopt($ch, CURLOPT_HTTPHEADER, $data['headers']);
	}

	if($data['request'] == 'post' || $data['post_data'] != '')
	{
		curl_setopt($ch, CURLOPT_POST, true);
	}

	if($data['post_data'] != '')
	{
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data['post_data']);
	}

	$content = curl_exec($ch);

	/*if(curl_errno($handle))
	{
		do_log(__("cURL Error", 'lang_base').": ".curl_error($ch));
	}*/

	if($data['catch_head'] == true)
	{
		$headers = curl_getinfo($ch);

		$out = array($content, $headers);

		if($data['follow_redirect'] == true)
		{
			switch($headers['http_code'])
			{
				case 301:
					if(isset($headers['redirect_url']) && $headers['redirect_url'] != $data['url'])
					{
						$data['url'] = $headers['redirect_url'];
						$data['follow_redirect'] = false;

						$out = get_url_content($data);
					}
				break;
			}
		}
	}

	else
	{
		$out = $content;
	}

	curl_close($ch);

	if(get_option('setting_log_curl_debug') == 'yes')
	{
		do_log("cURL: ".var_export($data, true)." -> ".var_export($out, true), 'auto-draft');
	}

	return $out;
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
	}

	else if(isset($notice_text) && $notice_text != '')
	{
		$out .= "<div class='update-nag'>".$notice_text."</div>";
	}

	else if(isset($done_text) && $done_text != '')
	{
		$out .= "<div class='updated'>
			<p>".$done_text."</p>
		</div>";
	}

	$error_text = $notice_text = $done_text = "";

	return $out;
}

function add_columns($array)
{
	global $wpdb;

	foreach($array as $table => $arr_col)
	{
		foreach($arr_col as $column => $value)
		{
			$wpdb->get_results("SHOW COLUMNS FROM ".esc_sql($table)." WHERE Field = '".esc_sql($column)."'");

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

function add_index($array)
{
	global $wpdb;

	foreach($array as $table => $arr_col)
	{
		foreach($arr_col as $column => $value)
		{
			$arr_existing_indexes = array();

			$result = $wpdb->get_results("SHOW INDEX FROM ".esc_sql($table));

			foreach($result as $r)
			{
				/*$strIndexTable = $r['Table'];
				$intIndexNonUnique = $r['Non_unique'];
				$strIndexKey = $r['Key_name'];
				$strIndexSeq = $r['Seq_in_index'];*/
				$strIndexColumn = $r->Column_name;
				/*$strIndexCollation = $r['Collation'];
				$strIndexCardinality = $r['Cardinality'];
				$strIndexSub = $r['Sub_part'];
				$strIndexPacked = $r['Packed'];
				$strIndexNull = $r['Null'];
				$strIndexType = $r['Index_type'];
				$strIndexComment = $r['Comment'];*/

				$arr_existing_indexes[] = $strIndexColumn;
			}

			if(!in_array($column, $arr_existing_indexes))
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

function mf_redirect($location, $arr_vars = array(), $method = 'post')
{
	$count_temp = count($arr_vars);

	if(!headers_sent() && $count_temp == 0)
	{
		header("Location: ".$location);
	}

	// Run this if header() does not work or headers_sent()
	echo "<form name='reload' action='".$location."' method='".$method."'>";

		if($count_temp > 0)
		{
			foreach($arr_vars as $key => $value)
			{
				echo input_hidden(array('name' => $key, 'value' => $value));
			}
		}

	echo "</form>
	<script>document.reload.submit();</script>";

	exit;
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

	//filter_var($temp, FILTER_VALIDATE_URL)
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

	else if($type == 'date' || $type2 == 'dte')
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
			}
		}
	}

	else if(is_array($temp) || $type == 'array' || $type2 == 'arr')
	{
		if(is_array($temp))
		{
			$out = $temp; // Never do addslashes() here
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

	//filter_var($temp, FILTER_SANITIZE_NUMBER_INT)
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
	if(!isset($data['custom_tag'])){		$data['custom_tag'] = 'div';}
	if(!isset($data['name'])){				$data['name'] = "";}
	if(!isset($data['id'])){				$data['id'] = $data['name'];}
	if(!isset($data['text'])){				$data['text'] = "";}
	if(!isset($data['value'])){				$data['value'] = "";}
	if(!isset($data['maxlength'])){			$data['maxlength'] = "";}
	if(!isset($data['size'])){				$data['size'] = 0;}
	if(!isset($data['required'])){			$data['required'] = false;}
	if(!isset($data['autocorrect'])){		$data['autocorrect'] = true;}
	if(!isset($data['autocapitalize'])){	$data['autocapitalize'] = true;}
	if(!isset($data['readonly'])){			$data['readonly'] = false;}
	if(!isset($data['placeholder'])){		$data['placeholder'] = "";}
	if(!isset($data['pattern'])){			$data['pattern'] = "";}
	if(!isset($data['title'])){				$data['title'] = "";}
	if(!isset($data['xtra'])){				$data['xtra'] = "";}
	if(!isset($data['xtra_class'])){		$data['xtra_class'] = "";}
	if(!isset($data['datalist'])){			$data['datalist'] = array();}
	if(!isset($data['suffix'])){			$data['suffix'] = "";}
	if(!isset($data['description'])){		$data['description'] = "";}

	/* Used by Form -> wp_form_check */
	if(isset($data['type']) && in_array($data['type'], array('int', 'float')))
	{
		$data['type'] = 'number';
	}

	$arr_accepted_types = array('text', 'email', 'url', 'date', 'month', 'time', 'number', 'range', 'color');

	if(!isset($data['type']) || !in_array($data['type'], $arr_accepted_types))
	{
		$data['type'] = 'text';
	}

	switch($data['type'])
	{
		case 'month':
		//case 'date':
			$plugin_include_url = plugin_dir_url(__FILE__);
			$plugin_version = get_plugin_version(__FILE__);

			mf_enqueue_style('jquery-ui-css', $plugin_include_url."jquery-ui.css", '1.8.2');
			wp_enqueue_script('jquery-ui-datepicker');
			mf_enqueue_script('script_base_datepicker', $plugin_include_url."script_datepicker.js", $plugin_version);

			$data['xtra_class'] .= ($data['xtra_class'] != '' ? " " : "")."mf_datepicker ".$data['type'];
			$data['type'] = "text";
		break;

		case 'email':
		case 'url':
			$data['autocorrect'] = false;
			$data['autocapitalize'] = false;
		break;

		case 'number':
			$data['xtra'] .= " step='any'";
		break;
	}

	if($data['value'] == "0000-00-00"){$data['value'] = "";}

	if(preg_match("/\[(.*)\]/", $data['id']))
	{
		$data['xtra'] .= " class='".preg_replace("/\[(.*)\]/", "", $data['id'])."'";
		$data['id'] = '';
	}

	if($data['required'])
	{
		$data['xtra'] .= " required";
	}

	if($data['autocorrect'] == false)
	{
		$data['xtra'] .= " autocorrect='off'";
	}

	if($data['autocapitalize'] == false)
	{
		$data['xtra'] .= " autocapitalize='off'";
	}

	if($data['readonly'] == true)
	{
		$data['xtra'] .= " readonly";
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
		$data['xtra'] .= " placeholder='".$data['placeholder']."&hellip;'";
	}

	if($data['pattern'] != '')
	{
		$data['xtra'] .= " pattern='".$data['pattern']."'";
	}

	if($data['title'] != '')
	{
		$data['xtra'] .= " title='".$data['title']."'";
	}

	$count_temp = count($data['datalist']);

	if($count_temp > 0)
	{
		$data['xtra'] .= " list='".$data['name']."_list'";
	}

	if($data['suffix'] != '')
	{
		$data['xtra_class'] .= ($data['xtra_class'] != '' ? " " : "")."has_suffix";
	}

	$out = "<".$data['custom_tag']." class='form_textfield".($data['xtra_class'] != '' ? " ".$data['xtra_class'] : "")."'>";

		if($data['text'] != '')
		{
			$out .= "<label for='".$data['name']."'>".$data['text']."</label>";
		}

		$out .= "<input type='".$data['type']."'".($data['name'] != '' ? " name='".$data['name']."'" : "").($data['id'] != '' ? " id='".$data['id']."'" : "")." value=\"".$data['value']."\"".($data['xtra'] != '' ? " ".trim($data['xtra']) : '').">";

		if($data['suffix'] != '')
		{
			$out .= "<span class='description'>".$data['suffix']."</span>";
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

	$out .= "</".$data['custom_tag'].">";

	return $out;
}
#################

######################
function show_password_field($data)
{
	$out = "";

	if(!isset($data['xtra_class'])){	$data['xtra_class'] = "";}
	if(!isset($data['name'])){			$data['name'] = "";}
	if(!isset($data['text'])){			$data['text'] = "";}
	if(!isset($data['value'])){			$data['value'] = "";}
	if(!isset($data['maxlength'])){		$data['maxlength'] = "";}
	if(!isset($data['placeholder'])){	$data['placeholder'] = "";}
	if(!isset($data['xtra'])){			$data['xtra'] = "";}
	if(!isset($data['suffix'])){		$data['suffix'] = "";}
	if(!isset($data['description'])){	$data['description'] = "";}

	if($data['maxlength'] != '')
	{
		$data['xtra'] .= " maxlength='".$data['maxlength']."'";
	}

	if($data['placeholder'] != '')
	{
		$data['xtra'] .= " placeholder='".$data['placeholder']."&hellip;'";
	}

	if($data['suffix'] != '')
	{
		$data['xtra_class'] .= ($data['xtra_class'] != '' ? " " : "")."has_suffix";
	}

	$out .= "<div class='form_password".($data['xtra_class'] != '' ? " ".$data['xtra_class'] : "")."'>";

		if($data['text'] != '')
		{
			$out .= "<label for='".$data['name']."'>".$data['text']."</label>";
		}

		$out .= "<input type='password' name='".$data['name']."' value='".$data['value']."' id='".$data['name']."'".$data['xtra'].">";

		if($data['suffix'] != '')
		{
			$out .= "<span class='description'>".$data['suffix']."</span>";
		}

		if($data['description'] != '')
		{
			$out .= "<p class='description'>".$data['description']."</p>";
		}

	$out .= "</div>";

	return $out;
}
######################

######################################
function show_textarea($data)
{
	if(!isset($data['name'])){			$data['name'] = "";}
	if(!isset($data['text'])){			$data['text'] = "";}
	if(!isset($data['value'])){			$data['value'] = "";}
	if(!isset($data['xtra'])){			$data['xtra'] = "";}
	if(!isset($data['class'])){			$data['class'] = "";}
	if(!isset($data['placeholder'])){	$data['placeholder'] = "";}
	if(!isset($data['required'])){		$data['required'] = false;}
	if(!isset($data['wysiwyg'])){		$data['wysiwyg'] = false;}
	if(!isset($data['description'])){	$data['description'] = "";}

	if($data['required'])
	{
		$data['xtra'] .= " required";
	}

	if($data['placeholder'] != '')
	{
		$data['xtra'] .= " placeholder='".$data['placeholder']."&hellip;'";
	}

	$out = "<div class='form_textarea".($data['class'] != '' ? " ".$data['class'] : "")."'>";

		if($data['text'] != '')
		{
			$out .= "<label for='".$data['name']."'>".$data['text']."</label>";
		}

		if($data['wysiwyg'] == true)
		{
			$out .= show_wp_editor(array('name' => $data['name'], 'value' => stripslashes($data['value']), 'editor_height' => 100));
		}

		else
		{
			$out .= "<textarea name='".$data['name']."' id='".$data['name']."'".$data['xtra'].">".stripslashes($data['value'])."</textarea>";
		}

		if($data['description'] != '')
		{
			$out .= "<p class='description'>".$data['description']."</p>";
		}

	$out .= "</div>";

	return $out;
}
#################

function show_wp_editor($data)
{
	if(!isset($data['name'])){			$data['name'] = "";}
	if(!isset($data['value'])){			$data['value'] = "";}
	if(!isset($data['class'])){			$data['class'] = "";}
	if(!isset($data['text'])){			$data['text'] = "";}
	if(!isset($data['xtra'])){			$data['xtra'] = "";}
	if(!isset($data['required'])){		$data['required'] = false;}
	if(!isset($data['description'])){	$data['description'] = "";}

	$data['value'] = str_replace("\\", "", $data['value']);

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

				wp_editor($data['value'], $data['name'], $data);

			$out .= ob_get_clean();

		if($data['xtra'] != '')
		{
			$out .= "</div>";
		}

		if($data['description'] != '')
		{
			$out .= "<p class='description'>".$data['description']."</p>";
		}

	if($data['class'] != '')
	{
		$out .= "</div>";
	}

	return $out;
}

############################
function get_select_size($data)
{
	if(!isset($data['minsize'])){		$data['minsize'] = 2;}
	if(!isset($data['maxsize'])){		$data['maxsize'] = 10;}

	if($data['count'] > $data['maxsize'])
	{
		$size = $data['maxsize'];
	}

	else if($data['count'] < $data['minsize'])
	{
		$size = $data['minsize'];
	}

	else
	{
		$size = $data['count'];
	}

	return $size;
}

function show_select($data)
{
	if(!isset($data['data'])){			$data['data'] = array();}
	if(!isset($data['name'])){			$data['name'] = "";}
	if(!isset($data['text'])){			$data['text'] = "";}
	if(!isset($data['compare'])){		$data['compare'] = "";} //To be deprecated in the future
	if(!isset($data['value'])){			$data['value'] = $data['compare'];}
	if(!isset($data['xtra'])){			$data['xtra'] = "";}
	if(!isset($data['required'])){		$data['required'] = false;}
	if(!isset($data['class'])){			$data['class'] = "";}
	if(!isset($data['attributes'])){	$data['attributes'] = array();}
	if(!isset($data['suffix'])){		$data['suffix'] = "";}
	if(!isset($data['description'])){	$data['description'] = "";}

	$obj_base = new mf_base();
	$obj_base->init_form($data);

	$out = "";

	$count_temp = count($obj_base->data['data']);

	if($count_temp > 0)
	{
		$container_class = "form_select";

		if($obj_base->is_multiple())
		{
			$obj_base->data['class'] .= ($obj_base->data['class'] != '' ? " " : "")."top";

			/* Do NOT make this default because it might have unknown consequences on some selects */
			/*if($obj_base->data['xtra'] == '')
			{
				$obj_base->data['xtra'] = "class='multiselect'";
			}*/

			$obj_base->data['xtra'] .= ($obj_base->data['xtra'] != '' ? " " : "")."multiple size='".get_select_size(array_merge($data, array('count' => $count_temp)))."'";

			$container_class .= " form_select_multiple";
		}

		if($count_temp == 1 && $obj_base->data['required'] && $obj_base->data['text'] != '')
		{
			$out = $obj_base->get_hidden_field();
		}

		else
		{
			if($obj_base->data['required'])
			{
				//$obj_base->data['xtra'] .= ($obj_base->data['xtra'] != '' ? " " : "")."required";
				$data['attributes']['required'] = '';
			}

			if(count($data['attributes']) > 0)
			{
				foreach($data['attributes'] as $key => $value)
				{
					if(is_array($value))
					{
						$value = wp_json_encode($value);
					}

					$obj_base->data['xtra'] .= ($obj_base->data['xtra'] != '' ? " " : "").$key.($value != '' ? " = '".$value."'" : '');

					if($key == 'condition_selector')
					{
						$plugin_include_url = plugin_dir_url(__FILE__);
						$plugin_version = get_plugin_version(__FILE__);

						mf_enqueue_script('script_base_conditions', $plugin_include_url."script_conditions.js", $plugin_version);
					}
				}
			}

			if($obj_base->data['suffix'] != '')
			{
				$obj_base->data['class'] .= ($obj_base->data['class'] != '' ? " " : "")."has_suffix";
			}

			$out = "<div class='".$container_class.($obj_base->data['class'] != '' ? " ".$obj_base->data['class'] : "")."'>";

				if($obj_base->data['text'] != '')
				{
					$out .= "<label for='".$obj_base->data['name']."'>".$obj_base->data['text']."</label>";
				}

				$out .= "<select".($obj_base->data['name'] != '' ? " id='".preg_replace("/\[(.*)\]/", "", $obj_base->data['name'])."' name='".$obj_base->data['name']."'" : "").($obj_base->data['xtra'] != '' ? " ".$obj_base->data['xtra'] : "").">";

					foreach($obj_base->data['data'] as $key => $option)
					{
						$is_disabled = false;

						if(substr($key, 0, 9) == "disabled_")
						{
							list($rest, $key) = explode("_", $key);

							$is_disabled = true;
						}

						$data_value = $key;

						if(is_array($option))
						{
							$data_text = $option[0];
							$data_desc = $option[1];
						}

						else
						{
							$data_text = $option;
							$data_desc = '';
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
							if($obj_base->is_multiple() && $data_value == ''){}

							else
							{
								//$out .= "<option value='".$data_value."'";
								$out .= "<option value='".($data_value === 0 ? '' : $data_value)."'";

									if($is_disabled)
									{
										$out .= " disabled";
									}

									else if(is_array($obj_base->data['value']) && in_array($data_value, $obj_base->data['value']) || $obj_base->data['value'] == $data_value)
									{
										$out .= " selected";
									}

								$out .= ">".$data_text."</option>";
							}
						}
					}

				$out .= "</select>"
				.$obj_base->get_field_suffix()
				.$obj_base->get_field_description()
			."</div>";
		}
	}

	return $out;
}
############################

############################
function show_form_alternatives($data)
{
	if(!isset($data['data'])){			$data['data'] = array();}
	if(!isset($data['name'])){			$data['name'] = '';}
	if(!isset($data['text'])){			$data['text'] = '';}
	if(!isset($data['value'])){			$data['value'] = '';}
	if(!isset($data['xtra'])){			$data['xtra'] = '';}
	if(!isset($data['required'])){		$data['required'] = false;}
	if(!isset($data['class'])){			$data['class'] = '';}
	if(!isset($data['suffix'])){		$data['suffix'] = '';}
	if(!isset($data['description'])){	$data['description'] = '';}

	$obj_base = new mf_base();
	$obj_base->init_form($data);

	$out = "";

	$count_temp = count($obj_base->data['data']);

	if($count_temp > 0)
	{
		if($obj_base->is_multiple())
		{
			$container_class = "form_checkbox_multiple";
		}

		else
		{
			$container_class = "form_radio_multiple";
		}

		if($count_temp == 1 && $obj_base->data['required'] && $obj_base->data['text'] != '')
		{
			$out = $obj_base->get_hidden_field();
		}

		else
		{
			if($obj_base->data['required'])
			{
				$obj_base->data['xtra'] .= " required";
			}

			if($obj_base->data['suffix'] != '')
			{
				$obj_base->data['class'] .= ($obj_base->data['class'] != '' ? " " : "")."has_suffix";
			}

			$out = "<div class='".$container_class.($obj_base->data['class'] != '' ? " ".$obj_base->data['class'] : "")."'>";

				if($obj_base->data['text'] != '')
				{
					$out .= "<label>".$obj_base->data['text']."</label>";
				}

				$out .= "<ul>";

					foreach($obj_base->data['data'] as $key => $option)
					{
						$is_disabled = false;

						if(substr($key, 0, 9) == "disabled_")
						{
							list($rest, $key) = explode("_", $key);

							$is_disabled = true;
						}

						$data_value = $key;

						if(is_array($option))
						{
							$data_text = $option[0];
							$data_desc = $option[1];
						}

						else
						{
							$data_text = $option;
							$data_desc = '';
						}

						if(substr($data_value, 0, 9) == "opt_start" && $data_value != $data_text)
						{
							$out .= "<li rel='".$data_value."'>".$data_text."</li>
							<ul>";
						}

						else if(substr($data_value, 0, 7) == "opt_end" && $data_value != $data_text)
						{
							$out .= "</ul>";
						}

						else
						{
							if($data_value == '') //$obj_base->is_multiple() &&
							{
								//Do nothing
							}

							else
							{
								if($is_disabled)
								{
									$compare = '';
								}

								else
								{
									$compare = (is_array($obj_base->data['value']) && in_array($data_value, $obj_base->data['value']) || $obj_base->data['value'] == $data_value) ? $data_value : -$data_value;
								}

								if($obj_base->is_multiple())
								{
									$out .= show_checkbox(array('name' => $obj_base->data['name'], 'text' => $data_text, 'value' => $data_value, 'compare' => $compare, 'tag' => 'li', 'xtra' => ($is_disabled ? " disabled" : ""), 'description' => $data_desc));
								}

								else
								{
									$out .= show_radio_input(array('name' => $obj_base->data['name'], 'text' => $data_text, 'value' => $data_value, 'compare' => $compare, 'tag' => 'li', 'xtra' => ($is_disabled ? " disabled" : ""), 'description' => $data_desc));
								}
							}
						}
					}

				$out .= "</ul>"
				.$obj_base->get_field_suffix()
				.$obj_base->get_field_description()
			."</div>";
		}
	}

	return $out;
}
############################

######################################
function show_checkbox($data)
{
	if(!isset($data['name'])){				$data['name'] = "";}
	if(!isset($data['value'])){				$data['value'] = "";}
	if(!isset($data['text'])){				$data['text'] = "";}
	if(!isset($data['required'])){			$data['required'] = false;}
	if(!isset($data['compare'])){			$data['compare'] = 0;}
	if(!isset($data['tag'])){				$data['tag'] = 'div';}
	if(!isset($data['xtra'])){				$data['xtra'] = "";}
	if(!isset($data['xtra_class'])){		$data['xtra_class'] = "";}
	if(!isset($data['suffix'])){			$data['suffix'] = "";}
	if(!isset($data['description'])){		$data['description'] = "";}
	if(!isset($data['switch'])){			$data['switch'] = 0;}

	if(!isset($data['switch_icon_on']) || $data['switch_icon_on'] == '')
	{
		$data['switch_icon_on'] = "far fa-check-square fa-lg";
	}

	if(!isset($data['switch_icon_off']) || $data['switch_icon_off'] == '')
	{
		$data['switch_icon_off'] = "far fa-square fa-lg";
	}

	$data['xtra'] .= ($data['value'] != '' && $data['value'] == $data['compare'] ? " checked" : "");

	if(substr($data['name'], -1) == "]")
	{
		$is_array = true;

		$new_class = preg_replace("/\[(.*?)\]/", "_$1", $data['name']);

		$new_class = trim($new_class, "_");

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

	if($data['switch'] == 1)
	{
		$data['xtra_class'] .= ($data['xtra_class'] != '' ? " " : "")."form_switch";
		$data['text'] = "<span><i class='".$data['switch_icon_on']." checked'></i><i class='".$data['switch_icon_off']." unchecked'></i><i class='fa fa-spinner fa-spin fa-lg loading'></i></span>".$data['text'];
	}

	if($data['suffix'] != '')
	{
		$data['xtra_class'] .= ($data['xtra_class'] != '' ? " " : "")."has_suffix";
	}

	$out = "<".$data['tag']." class='form_checkbox".($data['xtra_class'] != '' ? " ".$data['xtra_class'] : "")."'>
		<input type='checkbox'";

			if($data['name'] != '')
			{
				$out .= " name='".$data['name']."' id='".$this_id."'";
			}

		$out .= " value='".$data['value']."'".($data['xtra'] != '' ? " ".trim($data['xtra']) : "").">";

		if($data['text'] != '')
		{
			$out .= "<label".($this_id != '' ? " for='".$this_id."'" : "").">".$data['text']."</label>";
		}

		if($data['suffix'] != '')
		{
			$out .= "<span class='description'>".$data['suffix']."</span>";
		}

		if($data['description'] != '')
		{
			$out .= "<p class='description'>".$data['description']."</p>";
		}

	$out .= "</".$data['tag'].">";

	return $out;
}
#################

################################
function show_radio_input($data)
{
	if(!isset($data['text'])){			$data['text'] = "";}
	if(!isset($data['compare'])){		$data['compare'] = "";}
	if(!isset($data['tag'])){			$data['tag'] = 'div';}
	if(!isset($data['xtra'])){			$data['xtra'] = "";}
	if(!isset($data['xtra_class'])){	$data['xtra_class'] = "";}
	if(!isset($data['suffix'])){		$data['suffix'] = "";}
	if(!isset($data['description'])){	$data['description'] = "";}

	$checked = "";

	if($data['compare'] != '' && $data['compare'] == $data['value'])
	{
		$checked = " checked";
	}

	$out = "<".$data['tag']." class='form_radio".($data['xtra_class'] != '' ? " ".$data['xtra_class'] : "")."'>
		<input type='radio' name='".$data['name']."' value='".$data['value']."' id='".$data['name']."_".$data['value']."'".$checked.$data['xtra'].">";

		if($data['text'] != '')
		{
			$out .= "<label for='".$data['name']."_".$data['value']."'>".$data['text']."</label>";
		}

		if($data['suffix'] != '')
		{
			$out .= "<span class='description'>".$data['suffix']."</span>";
		}

		if($data['description'] != '')
		{
			$out .= "<p class='description'>".$data['description']."</p>";
		}

	$out .= "</".$data['tag'].">";

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

#################
function show_submit($data)
{
	return show_button($data);
}

function show_button($data)
{
	if(!isset($data['type'])){	$data['type'] = "submit";}
	if(!isset($data['name'])){	$data['name'] = "";}
	if(!isset($data['class'])){	$data['class'] = "";}
	if(!isset($data['xtra'])){	$data['xtra'] = "";}

	return "<button type='".$data['type']."'"
		.($data['name'] != '' ? " name='".$data['name']."'" : "")
		." class='".($data['class'] != '' ? $data['class'] : "button-primary")."'"
		.($data['xtra'] != '' ? " ".$data['xtra'] : "")
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
		if(is_array($data['value']))
		{
			do_log("Error - input_hidden: ".var_export($data, true));
		}

		return "<input type='hidden'".($data['name'] != '' ? " name='".$data['name']."'" : "")." value='".$data['value']."'".($data['xtra'] != '' ? " ".$data['xtra'] : "").">";
	}
}
#####################

function get_file_content($data)
{
	$content = "";

	if(file_exists($data['file']) && filesize($data['file']) > 0)
	{
		if($fh = @fopen(realpath($data['file']), 'r'))
		{
			$content = fread($fh, filesize($data['file']));
			fclose($fh);
		}

		else
		{
			do_log(__("The file could not be opened", 'lang_base')." (".$data['file'].")");
		}
	}

	return $content;
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
function prepare_file_name($file_name)
{
	return sanitize_title_with_dashes(sanitize_title($file_name))."_".date("ymdHi")."_".wp_hash($file_name);
}

function set_file_content($data)
{
	if(!isset($data['log'])){	$data['log'] = true;}

	$success = false;

	if(isset($data['realpath']) && $data['realpath'] == true)
	{
		$data['file'] = realpath($data['file']);
	}

	if($data['file'] != '')
	{
		switch(get_file_suffix($data['file']))
		{
			case 'bz2':
				if(!function_exists('bzcompress'))
				{
					$data['file'] = substr($data['file'], 0, -4);
				}
			break;

			case 'gz':
				if(!function_exists('gzencode'))
				{
					$data['file'] = substr($data['file'], 0, -3);
				}
			break;
		}

		if($fh = @fopen($data['file'], $data['mode']))
		{
			switch(get_file_suffix($data['file']))
			{
				case 'bz2':
					$data['content'] = bzcompress($data['content']); //, 9
				break;

				case 'gz':
					$data['content'] = gzencode($data['content']); //, 9
				break;
			}

			if(fwrite($fh, $data['content']))
			{
				fclose($fh);

				$success = true;
			}
		}

		else if($data['log'] == true)
		{
			do_log(sprintf(__("I am sorry but I did not have permission to access %s", 'lang_base'), $data['file']));
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
	if(!isset($data['allow_depth'])){		$data['allow_depth'] = true;}

	if($dp = @opendir($data['path']))
	{
		$count = 0;

		while(($child = readdir($dp)) !== false && ($data['limit'] == 0 || $count < $data['limit']))
		{
			if($child == '.' || $child == '..') continue;

			$file = str_replace("//", "/", $data['path'].'/'.$child);

			if(is_dir($file))
			{
				if($data['allow_depth'])
				{
					$data_temp = $data;
					$data_temp['path'] = $file;

					get_file_info($data_temp);
				}

				if($data['folder_callback'] != '')
				{
					if(is_callable($data['folder_callback']))
					{
						$data_temp = $data;
						$data_temp['child'] = $child;

						call_user_func($data['folder_callback'], $data_temp);
					}
				}
			}

			else
			{
				if(is_callable($data['callback']))
				{
					$data_temp = $data;
					$data_temp['file'] = $file;

					call_user_func($data['callback'], $data_temp);
				}
			}

			$count++;
		}

		closedir($dp);
	}
}

########################################
function show_table_header($arr_header, $shorten_text = true)
{
	$out = "<thead>
		<tr>";

			$count_temp = count($arr_header);

			for($i = 0; $i < $count_temp; $i++)
			{
				$arr_header[$i] = stripslashes(strip_tags($arr_header[$i]));

				if(strlen($arr_header[$i]) > 15 && $shorten_text == true)
				{
					$title = $arr_header[$i];
					$content = shorten_text(array('string' => $arr_header[$i], 'limit' => 12));
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

	if(!isset($data['add_choose_here'])){	$data['add_choose_here'] = false;}
	if(!isset($data['output_array'])){		$data['output_array'] = true;}
	if(!isset($data['allow_depth'])){		$data['allow_depth'] = true;}
	if(!isset($data['depth'])){				$data['depth'] = 0;}

	if(!isset($data['post_id'])){			$data['post_id'] = 0;}
	if(!isset($data['post_type'])){			$data['post_type'] = 'page';}
	if(!isset($data['post_status'])){		$data['post_status'] = 'publish';}
	if(!isset($data['include'])){			$data['include'] = array();}
	if(!isset($data['exclude'])){			$data['exclude'] = array();}
	if(!isset($data['join'])){				$data['join'] = '';}
	if(!isset($data['where'])){				$data['where'] = '';}
	if(!isset($data['order_by'])){			$data['order_by'] = 'menu_order';}
	if(!isset($data['limit'])){				$data['limit'] = 0;}
	if(!isset($data['count'])){				$data['count'] = false;}
	if(!isset($data['debug'])){				$data['debug'] = false;}

	if(!isset($data['current_id'])){		$data['current_id'] = '';}

	$exclude_post_status = array('auto-draft', 'ignore', 'inherit', 'trash');

	if($data['add_choose_here'] == true)
	{
		$arr_data[''] = "-- ".__("Choose Here", 'lang_base')." --";
	}

	$out = "";

	if($data['post_status'] != '')
	{
		$data['where'] .= ($data['where'] != '' ? " AND " : "")."post_status = '".$data['post_status']."'";
	}

	else
	{
		$data['where'] .= ($data['where'] != '' ? " AND " : "")."post_status NOT IN('".implode("','", $exclude_post_status)."')";
	}

	if(count($data['include']) > 0)
	{
		$data['where'] .= ($data['where'] != '' ? " AND " : "")."ID IN('".implode("','", $data['include'])."')";
	}

	if(count($data['exclude']) > 0)
	{
		$data['where'] .= ($data['where'] != '' ? " AND " : "")."ID NOT IN('".implode("','", $data['exclude'])."')";
	}

	$result = $wpdb->get_results($wpdb->prepare("SELECT ID, post_title FROM ".$wpdb->posts.$data['join']." WHERE post_type = %s AND post_parent = '%d'".($data['where'] != '' ? " AND ".$data['where'] : "")." ORDER BY ".$data['order_by']." ASC".($data['limit'] > 0 ? " LIMIT 0, ".$data['limit'] : ""), $data['post_type'], $data['post_id']));
	$rows = $wpdb->num_rows;

	if($data['debug'] == true)
	{
		do_log("get_post_children(): ".$wpdb->last_query);
	}

	if($data['count'] == true)
	{
		$out = $rows;
	}

	else if($rows > 0)
	{
		foreach($result as $r)
		{
			$post_id = $r->ID;
			$post_title = ($r->post_title != '' ? $r->post_title : "(".__("no title", 'lang_base').")");

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

			if($data['allow_depth'] == true)
			{
				$data_temp = $data;
				$data_temp['post_id'] = $post_id;
				$data_temp['depth']++;

				$out .= get_post_children($data_temp, $arr_data);
			}
		}
	}

	return $out;
}

function format_phone_no($string)
{
	return "tel:".preg_replace("/[^\d]/", "", $string);
}

function month_name($month_no, $ucfirst = 1)
{
	if($month_no < 1)
	{
		$month_no = 1;
	}

	$month_names = array(__("January", 'lang_base'), __("February", 'lang_base'), __("March", 'lang_base'), __("April", 'lang_base'), __("May", 'lang_base'), __("June", 'lang_base'), __("July", 'lang_base'), __("August", 'lang_base'), __("September", 'lang_base'), __("October", 'lang_base'), __("November", 'lang_base'), __("December", 'lang_base'));

	$out = $month_names[$month_no - 1];

	if($ucfirst == 0){$out = strtolower($out);}

	return $out;
}

function day_name($day_no, $ucfirst = 1)
{
	$day_names = array(__("Sunday", 'lang_base'), __("Monday", 'lang_base'), __("Tuesday", 'lang_base'), __("Wednesday", 'lang_base'), __("Thursday", 'lang_base'), __("Friday", 'lang_base'), __("Saturday", 'lang_base'));

	$out = $day_names[$day_no];

	if($ucfirst == 0){$out = strtolower($out);}

	return $out;
}