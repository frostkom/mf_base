<?php

function make_link_confirm()
{
	$plugin_include_url = plugin_dir_url(__FILE__);

	mf_enqueue_script('script_base_confirm', $plugin_include_url."script_confirm.js");

	return " rel='confirm'";
}

function make_link_external()
{
	do_action('load_font_awesome');

	$plugin_include_url = plugin_dir_url(__FILE__);

	mf_enqueue_script('script_base_external', $plugin_include_url."script_external.js");

	return " target='_blank' rel='noopener noreferrer'";
}

function get_placeholder_email()
{
	$site_url_clean = get_site_url_clean(array('trim' => "/"));

	if(strpos($site_url_clean, "/"))
	{
		list($site_url_clean, $rest) = explode("/", $site_url_clean, 2);
	}

	return __("your-name", 'lang_base')."@".$site_url_clean;
}

function get_time_limit_description($hours_left)
{
	if($hours_left == 1)
	{
		return "<em>".__("One hour left until it is reset", 'lang_base')."</em>";
	}

	else
	{
		return "<em>".sprintf(__("%d hours left until it is reset", 'lang_base'), $hours_left)."</em>";
	}
}

function setting_time_limit($data)
{
	if(!isset($data['return'])){		$data['return'] = '';}
	if(!isset($data['time_limit'])){	$data['time_limit'] = 6;}

	$has_changed = false;
	$description = "";

	if($data['value'] == 'yes' || is_array($data['value']) && count($data['value']) > 0)
	{
		$option_base_time_limited = get_option_or_default('option_base_time_limited', []);

		if(isset($option_base_time_limited[$data['key']]))
		{
			$hours_left = time_between_dates(array('start' => date("Y-m-d H:i:s"), 'end' => $option_base_time_limited[$data['key']], 'type' => 'round', 'return' => 'hours'));

			if($hours_left > 0)
			{
				$description = get_time_limit_description($hours_left);
			}

			else
			{
				delete_site_option($data['key']);
				delete_option($data['key']);

				unset($option_base_time_limited[$data['key']]);

				$has_changed = true;

				if(is_array($data['value']))
				{
					$data['value'] = [];
				}

				else
				{
					$data['value'] = "";
				}
			}
		}

		else
		{
			$description = get_time_limit_description($data['time_limit']);

			$option_base_time_limited[$data['key']] = date("Y-m-d H:i:s", strtotime("+".$data['time_limit']." hour"));

			$has_changed = true;
		}
	}

	if($has_changed == true)
	{
		update_option('option_base_time_limited', $option_base_time_limited, false);
	}

	switch($data['return'])
	{
		default:
			return $description;
		break;

		case 'array':
			return array($data['value'], $description);
		break;
	}
}

function get_option_page_suffix($data)
{
	if(!isset($data['post_type'])){	$data['post_type'] = 'page';}
	if(!isset($data['title'])){		$data['title'] = '';}
	if(!isset($data['content'])){	$data['content'] = '';}

	do_action('load_font_awesome');

	if($data['value'] > 0)
	{
		$out = "<a href='".admin_url("post.php?post=".$data['value']."&action=edit")."'><i class='fa fa-wrench fa-lg'></i></a> <a href='".get_permalink($data['value'])."'><i class='fa fa-eye fa-lg'></i></a>";
	}

	else
	{
		$out = "<a href='".admin_url("post-new.php?post_type=".$data['post_type'].($data['title'] != '' ? "&post_title=".$data['title'] : "").($data['content'] != '' ? "&content=".$data['content'] : ""))."'><i class='fa fa-plus-circle fa-lg'></i></a>";
	}

	return $out;
}

function show_final_size($in, $suffix = true)
{
	$arr_suffix = array("B", "kB", "MB", "GB", "TB");

	$count_temp = count($arr_suffix);

	for($i = 0; ($in > KB_IN_BYTES || $i < 1) && $i < $count_temp; $i++) //Forces at least kB
	{
		$in /= KB_IN_BYTES;
	}

	$out = (strlen(round($in)) < 3 ? round($in, 1) : round($in)); //If less than 3 digits, show one decimal aswell

	if($suffix == true)
	{
		$out .= "&nbsp;".$arr_suffix[$i];
	}

	return $out;
}

function get_url_part($data)
{
	if(!isset($data['url']) || $data['url'] == ''){			$data['url'] = get_site_url();}

	$parsed_url = parse_url($data['url']);

	$scheme = (isset($parsed_url['scheme']) ? $parsed_url['scheme'].'://' : '');
	$host = (isset($parsed_url['host']) ? $parsed_url['host'] : '');
	$path = (isset($parsed_url['path']) ? $parsed_url['path'] : '');

	switch($data['type'])
	{
		case 'domain':
			$arr_host = explode(".", $host);
			$arr_host_length = count($arr_host);

			$sld = $tld = "";
			$sld_index = ($arr_host_length - 2);
			$tld_index = ($arr_host_length - 1);

			if(isset($arr_host[$sld_index]))
			{
				$sld = $arr_host[$sld_index];
			}

			else
			{
				do_log(__FUNCTION__.": No SLD (".$sld_index.") in ".$data['url']." -> ".var_export($parsed_url, true)." -> ".$host." -> ".var_export($arr_host, true));
			}

			if(isset($arr_host[$tld_index]))
			{
				$tld = $arr_host[$tld_index];
			}

			else
			{
				do_log(__FUNCTION__.": No TLD (".$tld_index.") in ".var_export($arr_host, true));
			}

			return $scheme.$sld.".".$tld;
		break;

		case 'subdomain':
			return $scheme.$host;
		break;

		case 'path':
			if(substr($path, -1) != "/")
			{
				$path .= "/";
			}

			return $path;
		break;

		default:
			do_log("Unknown Part Type: ".$data['type']);
		break;
	}
}

function remove_protocol($data)
{
	if(!is_array($data)){			$data = array('url' => $data);}

	if(!isset($data['clean'])){		$data['clean'] = false;}
	if(!isset($data['trim'])){		$data['trim'] = false;}

	$data['url'] = str_replace(array("http://", "https://"), ($data['clean'] == true ? "" : "//"), $data['url']);

	if($data['trim'] == true)
	{
		$data['url'] = trim($data['url'], "/");
	}

	return $data['url'];
}

function get_site_url_clean($data = [])
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

	$plugin_path = plugin_dir_path($file);

	$arr_plugin_path = explode("/", $plugin_path);

	$plugin_path = "";

	foreach($arr_plugin_path as $plugin_path_part)
	{
		if($plugin_path_part != '')
		{
			$plugin_path .= "/".$plugin_path_part;

			if(substr($plugin_path_part, 0, 3) == "mf_")
			{
				break;
			}
		}
	}

	$plugin_dir = $plugin_path."/index.php";

	$arr_plugin_data = get_plugin_data($plugin_dir);

	return $arr_plugin_data['Version'];
}

function get_toggler_container($data)
{
	if(!isset($data['container_tag'])){		$data['container_tag'] = 'div';}

	switch($data['type'])
	{
		case 'start':
			do_action('get_toggler_includes');

			if(!isset($data['label_tag'])){			$data['label_tag'] = 'label';}
			if(!isset($data['is_open'])){			$data['is_open'] = false;}
			if(!isset($data['is_toggleable'])){		$data['is_toggleable'] = true;}
			if(!isset($data['id_prefix'])){			$data['id_prefix'] = '';}
			if(!isset($data['id'])){				$data['id'] = '';}

			if($data['id'] == '' && $data['text'] != '')
			{
				$data['id'] = sanitize_title_with_dashes(sanitize_title($data['text']));
			}

			$data['id'] = $data['id_prefix'].$data['id'];

			$out = "<".$data['label_tag'].($data['id'] != '' ? " id='toggle_".$data['id']."'" : "")." class='toggler".($data['is_open'] ? " is_open" : "").($data['is_toggleable'] ? "" : " is_not_toggleable")."'>"
				."<span>".$data['text']."</span>";

				if($data['is_toggleable'] == true)
				{
					$out .= "<div class='toggle_icon'><div></div><div></div></div>";
				}

			$out .= "</".$data['label_tag'].">
			<".$data['container_tag']." class='toggle_container".($data['id'] != '' ? " toggle_".$data['id'] : "")."'>";

			return $out;
		break;

		case 'end':
			return "</".$data['container_tag'].">";
		break;
	}
}

function get_user_info($data = [])
{
	global $obj_base;

	if(!isset($data['id'])){	$data['id'] = get_current_user_id();}
	if(!isset($data['type'])){	$data['type'] = 'name';}

	if($data['id'] > 0)
	{
		$user_data = get_userdata($data['id']);

		if(isset($user_data->display_name))
		{
			switch($data['type'])
			{
				case 'name':
					return $user_data->display_name;
				break;

				case 'shortname':
				case 'short_name':
					$display_name = $user_data->display_name;

					$arr_name = explode(" ", $display_name);

					$short_name = "";

					foreach($arr_name as $name)
					{
						$name_first_letter = substr($name, 0, 1);

						if(htmlspecialchars($name_first_letter) == '')
						{
							$name_first_letter = substr($name, 0, 2);
						}

						$short_name .= $name_first_letter;
					}

					return "<span title='".$display_name."'>".$short_name."</span>";
				break;
			}
		}

		else
		{
			return '';

			do_log(sprintf("There was no display name for %s (%d)", var_export($user_data, true), $data['id']));
		}
	}

	else
	{
		return __("unknown", 'lang_base');
	}
}

function contains_html($string)
{
	$string_decoded = htmlspecialchars_decode($string);

	return ($string != strip_tags($string) || $string_decoded != strip_tags($string_decoded));
}

function send_email($data)
{
	global $error_text, $phpmailer, $obj_base;

	if(!isset($data['from'])){			$data['from'] = get_bloginfo('admin_email');}
	if(!isset($data['from_name'])){		$data['from_name'] = get_bloginfo('name');}
	if(!isset($data['headers'])){		$data['headers'] = "From: ".$data['from_name']." <".$data['from'].">\r\n";}
	if(!isset($data['attachment'])){	$data['attachment'] = [];}
	if(!isset($data['save_log_type'])){	$data['save_log_type'] = 'plugin';}

	if(!isset($data['save_log']))
	{
		$setting_email_log = get_site_option('setting_email_log');

		$data['save_log'] = (is_array($setting_email_log) && in_array('plugin', $setting_email_log));
	}

	if(!isset($obj_base))
	{
		$obj_base = new mf_base();
	}

	if($data['to'] == '')
	{
		$error_text = sprintf(__("The message had no recipient so %s could not be sent", 'lang_base'), $data['subject']);

		return false;
	}

	else if($data['content'] == '')
	{
		$error_text = sprintf(__("The message was empty so I could not send %s to %s", 'lang_base'), $data['subject'], $data['to']);

		return false;
	}

	else
	{
		if(contains_html($data['content']))
		{
			$arr_preferred_content_types = apply_filters('get_preferred_content_types', [], $data['from']);

			if(!is_array($arr_preferred_content_types) || count($arr_preferred_content_types) == 0 || in_array('html', $arr_preferred_content_types))
			{
				add_filter('wp_mail_content_type', array($obj_base, 'set_html_content_type'));

				$data['content'] = "<html>
					<head>
						<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
					</head>
					<body>"
						.$data['content']
					."</body>
				</html>";
			}

			else
			{
				$data['content'] = strip_tags($data['content']);
				$data['content'] = preg_replace("/[\r\n]+/", "\r\n", $data['content']);
			}
		}

		// Needed when '&' is sent through a textfield, which otherwise becomes &amp; on the receiving end
		$data['subject'] = html_entity_decode($data['subject']);
		$data['content'] = html_entity_decode($data['content']);

		$sent = wp_mail($data['to'], $data['subject'], $data['content'], $data['headers'], $data['attachment']);

		if($data['save_log'] == true || !$sent)
		{
			$data_temp = $data;
			unset($data_temp['content']);
			unset($data_temp['attachment']);
			unset($data_temp['save_log']);
			unset($data_temp['save_log_type']);

			$obj_base->filter_phpmailer_data();
		}

		if($sent)
		{
			if($data['save_log'] == true)
			{
				$obj_microtime = new mf_microtime();

				do_log(__("Message Sent", 'lang_base')." (".$data['save_log_type']."): ".$obj_microtime->now.", ".htmlspecialchars(var_export($data_temp, true))." -> ".var_export($obj_base->phpmailer_temp, true)." (".$_SERVER['REQUEST_URI'].")", 'notification');
			}

			if(isset($phpmailer->From))
			{
				do_action('sent_email', $phpmailer->From);
			}
		}

		else
		{
			do_log(__("Message NOT Sent", 'lang_base')." (".$data['save_log_type']."): ".var_export($data_temp, true).", ".var_export($obj_base->phpmailer_temp, true));

			do_action('sent_email_error', $phpmailer->From);
		}

		return $sent;
	}
}

function shorten_text($data)
{
	if(!isset($data['string'])){		$data['string'] = '';}
	if(!isset($data['count'])){			$data['count'] = false;}
	if(!isset($data['add_title'])){		$data['add_title'] = false;}

	$out = "";

	if(is_array($data['string']))
	{
		do_log(__FUNCTION__." - String is array: ".var_export($data, true)." (Backtrace: ".var_export(debug_backtrace(), true).")");
	}

	else if(strlen($data['string']) > $data['limit'])
	{
		if($data['add_title'])
		{
			$out .= "<span title='".$data['string']."'>";
		}

			$out .= trim(mb_substr($data['string'], 0, $data['limit']))."&hellip;";

			if($data['count'] == true)
			{
				$out .= " (".strlen($data['string']).")";
			}

		if($data['add_title'])
		{
			$out .= "</span>";
		}
	}

	else
	{
		$out .= $data['string'];
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
		update_option($data['new'], $option_old, false);

	}

	delete_option($data['old']);
}

function replace_post_meta($data)
{
	global $wpdb;

	$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->postmeta." SET meta_key = %s WHERE meta_key = %s", $data['new'], $data['old']));
}

function replace_user_meta($data)
{
	if(!isset($data['single'])){	$data['single'] = true;}

	$arr_users = get_users(array('fields' => array('ID')));

	foreach($arr_users as $user)
	{
		$user_id = (isset($user->ID) ? $user->ID : $user);

		$meta_old = get_user_meta($user_id, $data['old'], $data['single']);

		if($meta_old != '')
		{
			update_user_meta($user_id, $data['new'], $meta_old);
			delete_user_meta($user_id, $data['old']);
		}
	}
}

function mf_uninstall_uploads($data, $force_main_uploads)
{
	if($data['uploads'] != '')
	{
		list($upload_path, $upload_url) = get_uploads_folder($data['uploads'], $force_main_uploads, false);

		if($upload_path != '' && file_exists($upload_path))
		{
			get_file_info(array('path' => $upload_path, 'callback' => 'delete_files_callback', 'time_limit' => 0));
			get_file_info(array('path' => $upload_path, 'folder_callback' => 'delete_empty_folder_callback'));
		}
	}
}

function mf_uninstall_options($data)
{
	foreach($data['options'] as $option)
	{
		delete_site_option($option);
		delete_option($option);
	}
}

function mf_uninstall_user_meta($data)
{
	if(count($data['user_meta']) > 0)
	{
		$arr_users = get_users(array('fields' => array('ID')));

		foreach($arr_users as $user)
		{
			foreach($data['user_meta'] as $meta_key)
			{
				if(isset($user->ID))
				{
					delete_user_meta($user->ID, $meta_key);
				}
			}
		}
	}
}

function mf_uninstall_post_types($data)
{
	global $wpdb;

	foreach($data['post_types'] as $post_type)
	{
		$i = 0;

		$result = $wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_type = %s AND post_status != 'trash'", $post_type));

		foreach($result as $r)
		{
			wp_delete_post($r->ID);

			$i++;

			if($i % 100 == 0)
			{
				sleep(1);
				set_time_limit(60);
			}
		}
	}
}

function mf_uninstall_post_meta($data)
{
	global $wpdb;

	foreach($data['post_meta'] as $meta_key)
	{
		$i = 0;

		$result = $wpdb->get_results($wpdb->prepare("SELECT post_id, meta_key FROM ".$wpdb->postmeta." WHERE meta_key = %s", $meta_key));

		foreach($result as $r)
		{
			//delete_post_meta($r->post_id, $r->meta_key);
			do_log(__FUNCTION__.": Delete post_meta: ".$post_id." -> ".$meta_key);

			$i++;

			if($i % 100 == 0)
			{
				sleep(1);
				set_time_limit(60);
			}
		}
	}
}

function does_table_exist($table)
{
	global $wpdb;

	$wpdb->get_results($wpdb->prepare("SHOW TABLES LIKE %s", $table));

	return ($wpdb->num_rows > 0);
}

function does_column_exist($table, $column)
{
	global $wpdb;

	$wpdb->get_results($wpdb->prepare("SHOW COLUMNS FROM ".esc_sql($table)." WHERE Field = %s", $column));

	return ($wpdb->num_rows > 0);
}

function mf_uninstall_tables($data)
{
	global $wpdb;

	foreach($data['tables'] as $table)
	{
		$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix.$table);
	}
}

function mf_uninstall_plugin($data)
{
	if(!isset($data['uploads'])){			$data['uploads'] = "";}
	if(!isset($data['options'])){			$data['options'] = [];}
	if(!isset($data['user_meta'])){			$data['user_meta'] = [];}
	if(!isset($data['post_types'])){		$data['post_types'] = [];}
	if(!isset($data['post_meta'])){			$data['post_meta'] = [];}
	if(!isset($data['tables'])){			$data['tables'] = [];}

	if(isset($data['meta']))
	{
		do_log(__FUNCTION__.": 'meta' still exists in a plugin (".var_export($data, true).")");

		//$data['user_meta'] = $data['meta'];
	}

	if(is_multisite())
	{
		$result = get_sites();

		foreach($result as $r)
		{
			switch_to_blog($r->blog_id);

			mf_uninstall_uploads($data, false);
			mf_uninstall_options($data);
			mf_uninstall_post_types($data);
			mf_uninstall_post_meta($data);
			mf_uninstall_tables($data);

			restore_current_blog();
		}
	}

	else
	{
		mf_uninstall_options($data);
		mf_uninstall_post_types($data);
		mf_uninstall_post_meta($data);
		mf_uninstall_tables($data);
	}

	mf_uninstall_uploads($data, true);
	mf_uninstall_user_meta($data);
}

function is_domain_valid($email, $record = 'MX')
{
	if(strpos($email, "@") === false)
	{
		do_log(__FUNCTION__." - No domain: ".$email." (".$record.")", 'notification');

		return false;
	}

	else
	{
		list($user, $domain) = explode("@", $email);

		if($domain != '')
		{
			return checkdnsrr($domain, $record);
		}

		else
		{
			do_log(__FUNCTION__." - Empty domain: ".$email." (".$record.")", 'notification');

			return false;
		}
	}
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

/*function get_the_author_meta_or_default($key, $user_id, $default = '')
{
	$option = get_the_author_meta($key, $user_id);

	if(($option == '' || $option === 0) && $default != '')
	{
		$option = $default;
	}

	return $option;
}*/

function render_image_tag($data)
{
	$out = "";

	if(!isset($data['id'])){	$data['id'] = 0;}
	if(!isset($data['src'])){	$data['src'] = '';}
	if(!isset($data['size'])){	$data['size'] = 'full';}

	if(!($data['id'] > 0) && $data['src'] != '')
	{
		global $wpdb;

		$attachment_id = $wpdb->get_var($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE guid LIKE %s", $data['src']));

		if($attachment_id > 0)
		{
			$data['id'] = $attachment_id;
		}
	}

	if($data['id'] > 0)
	{
		$image_tag = wp_get_attachment_image($data['id'], $data['size']);
		$image_tag = preg_replace('/(width|height)="\d*"\s?/', '', $image_tag);
		$out .= $image_tag;
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

			if(isset($file_url[0]))
			{
				$file_url = $file_url[0];
			}

			/*else
			{
				do_log("No image found with get_post_meta_file_src(): ".var_export($data, true)." -> ".var_export($file_url, true));
			}*/
		}

		else
		{
			$file_url = wp_get_attachment_url($file_ids);
		}
	}

	else
	{
		$file_url = [];

		if(is_array($file_ids))
		{
			foreach($file_ids as $file_id)
			{
				if($data['is_image'] == true)
				{
					$file_url_temp = wp_get_attachment_image_src($file_id, $data['image_size']);
					$file_url[$file_id] = $file_url_temp[0];
				}

				else
				{
					$file_url[$file_id] = wp_get_attachment_url($file_id);
				}
			}
		}
	}

	return $file_url;
}

function time_between_dates($data)
{
	if(!isset($data['return'])){	$data['return'] = '';}

	$out = "";

	$startDate = new DateTime($data['start']);
	$endDate = new DateTime($data['end']);
	$interval = $startDate->diff($endDate);

	switch($data['return'])
	{
		/*case 'years': break;
		case 'months': break;
		case 'weeks': break;
		case 'days': break;*/

		case 'hours':
			$out = $interval->h + ($interval->days * 24);
		break;

		case 'minutes':
			$out = $interval->i + ($interval->h * 60) + ($interval->days * 24);
		break;

		case 'seconds':
			$out = $interval->s + ($interval->i * 60) + ($interval->h * 60) + ($interval->days * 24);
		break;

		default:
			if($interval->days > 0)
			{
				$out .= ($out != '' ? ", " : "").$interval->days."&nbsp;".($interval->days > 1 ? __("days", 'lang_base') : __("day", 'lang_base'));
			}

			if($interval->h > 0)
			{
				$out .= ($out != '' ? ", " : "").$interval->h."&nbsp;".($interval->h > 1 ? __("hours", 'lang_base') : __("hour", 'lang_base'));
			}

			if($interval->i > 0)
			{
				$out .= ($out != '' ? ", " : "").$interval->i."&nbsp;".($interval->i > 1 ? __("minutes", 'lang_base') : __("minute", 'lang_base'));
			}

			if($out == "" || $interval->s > 0)
			{
				$out .= ($out != '' ? ", " : "").$interval->s."&nbsp;".($interval->s != 1 ? __("seconds", 'lang_base') : __("second", 'lang_base'));
			}
		break;
	}

	return $out;
}

function delete_files($data)
{
	delete_files_callback($data);
}

function delete_files_callback($data)
{
	if(!isset($data['time_limit'])){	$data['time_limit'] = (DAY_IN_SECONDS * 2);}

	if(file_exists($data['file']))
	{
		if($data['time_limit'] == 0 || (time() - filemtime($data['file']) >= $data['time_limit']))
		{
			// Make sure that a file in a /YYYY/MM/ folder is never deleted here
			if(preg_match("/\/[0-9]{4}\/[0-9]{2}\//", $data['file']) == false)
			{
				unlink($data['file']);
			}
		}
	}
}

function delete_empty_folder_callback($data)
{
	$folder = $data['path']."/".$data['child'];

	if(file_exists($folder) && is_array(scandir($folder)) && count(scandir($folder)) == 2)
	{
		if(is_link($folder))
		{
			unlink($folder);
		}

		else if(is_dir($folder))
		{
			@rmdir($folder);
		}
	}
}

function format_date($in)
{
	global $obj_base;

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

function get_uploads_folder($subfolder = '', $force_main_uploads = true, $force_create = true)
{
	global $obj_base, $error_text;

	$subfolder = trim($subfolder, "/");

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

	if(substr($upload_url, 0, 5) == 'http:' && substr(get_site_url(), 0, 6) == 'https:')
	{
		$upload_url = str_replace("http:", "https:", $upload_url);
	}

	if($subfolder != '')
	{
		if(is_dir($upload_path) || is_file($upload_path))
		{
			$dir_exists = true;
		}

		else
		{
			if($force_create == true && @mkdir($upload_path, 0755, true))
			{
				$dir_exists = true;
			}

			else
			{
				$dir_exists = false;
			}
		}

		if($dir_exists == false)
		{
			$error_text = sprintf(__("Could not create %s in %s. Please add the correct rights for the script to create a new subfolder.", 'lang_base'), $subfolder, "uploads");

			$upload_path = $upload_url = "";
		}
	}

	return array($upload_path, $upload_url);
}

function insert_attachment($data)
{
	global $wpdb, $obj_base, $done_text, $error_text;

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

		if($intFileID > 0)
		{
			// Remove http://domain.com/wp-content/uploads/sites/8/
			##############################
			list($upload_path, $upload_url) = get_uploads_folder();

			if(is_multisite() && $wpdb->blogid > 1)
			{
				$upload_url .= "sites/".$wpdb->blogid."/";
			}

			$file_url = str_replace($upload_url, "", $file_url);

			update_post_meta($intFileID, '_wp_attached_file', $file_url);
			##############################

			// Create thumbnails
			##############################
			$imagenew = get_post($intFileID);
			$fullsizepath = get_attached_file($imagenew->ID);
			$attach_data = wp_generate_attachment_metadata($intFileID, $fullsizepath);
			wp_update_attachment_metadata($intFileID, $attach_data);
			##############################
		}

		else
		{
			$error_text = __("Well, we tried to save the file but something went wrong internally in Wordpress", 'lang_base').": ".$temp_file;

			do_log("insert_attachment() Error: ".var_export($attachment, true)." (".$file_url.")");
		}
	}

	return $intFileID;
}

function do_log($data, $action = 'publish', $increment = true)
{
	global $obj_log;

	if(!class_exists('mf_log') && file_exists(ABSPATH.'wp-content/mf_log/include/classes.php'))
	{
		require_once(ABSPATH.'wp-content/mf_log/include/classes.php');
	}

	if(class_exists('mf_log'))
	{
		if(!isset($obj_log))
		{
			$obj_log = new mf_log();
		}

		$obj_log->create($data, $action, $increment);
	}

	else if($action == 'publish')
	{
		error_log(str_replace("\n", "", $data));
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
	if(!isset($data['child_tables'])){	$data['child_tables'] = [];}

	$empty_trash_days = (defined('EMPTY_TRASH_DAYS') ? EMPTY_TRASH_DAYS : 30);

	$data['field_prefix'] = esc_sql($data['field_prefix']);
	$data['table'] = esc_sql($data['table']);

	$result = $wpdb->get_results("SELECT ".$data['field_prefix']."ID AS ID FROM ".$data['table_prefix'].$data['table']." WHERE ".$data['field_prefix']."Deleted = '1' AND (".$data['field_prefix']."DeletedDate IS null OR ".$data['field_prefix']."DeletedDate < DATE_SUB(NOW(), INTERVAL ".$empty_trash_days." DAY)) LIMIT 0, 1000");

	foreach($result as $r)
	{
		$intID = $r->ID;

		$rows = 0;

		$debug = "Checking ".$data['table_prefix'].$data['table']." #".$intID;

		foreach($data['child_tables'] as $child_table => $child_table_type)
		{
			if($child_table_type['action'] == "trash" && does_table_exist($data['table_prefix'].$child_table))
			{
				$wpdb->get_results($wpdb->prepare("SELECT ".$data['field_prefix']."ID FROM ".$data['table_prefix'].$child_table." WHERE ".$data['field_prefix']."ID = '%d' LIMIT 0, 1", $intID));

				if($wpdb->num_rows > 0)
				{
					$wpdb->query($wpdb->prepare("UPDATE ".$data['table_prefix'].$child_table." SET ".$child_table_type['field_prefix']."Deleted = '1', ".$child_table_type['field_prefix']."DeletedDate = NOW() WHERE ".$data['field_prefix']."ID = '%d' AND ".$child_table_type['field_prefix']."Deleted = '0'", $intID));

					$rows += $wpdb->rows_affected;

					$debug .= ", Trashed ".$wpdb->rows_affected." from ".$data['table_prefix'].$child_table;
				}

				else
				{
					$debug .= ", No rows to delete in ".$data['table_prefix'].$child_table;
				}
			}

			else
			{
				$debug .= ", No action (".$child_table_type['action'].") or table ".$data['table_prefix'].$child_table;
			}
		}

		if($rows == 0)
		{
			foreach($data['child_tables'] as $child_table => $child_table_type)
			{
				if($child_table_type['action'] == "delete")
				{
					$wpdb->query($wpdb->prepare("DELETE FROM ".$data['table_prefix'].$child_table." WHERE ".$data['field_prefix']."ID = '%d'", $intID));

					$debug .= ", Deleted ".$wpdb->rows_affected." from ".$data['table_prefix'].$child_table;
				}
			}

			$wpdb->query($wpdb->prepare("DELETE FROM ".$data['table_prefix'].$data['table']." WHERE ".$data['field_prefix']."ID = '%d'", $intID));

			$debug .= ", Deleted ".$wpdb->rows_affected." from ".$data['table_prefix'].$data['table'];
		}
	}
}

function get_file_icon($data)
{
	if(!is_array($data))
	{
		$data = array(
			'file' => $data,
		);
	}

	if(!isset($data['size'])){		$data['size'] = "fa-lg";}

	do_action('load_font_awesome');

	$suffix = get_file_suffix($data['file']);

	switch($suffix)
	{
		case 'pdf':
			$class = "fa-file-pdf";
		break;

		case 'mp3':
		case 'ogg':
			$class = "fa-file-audio";
		break;

		case 'xls':
		case 'xlsx':
			$class = "fa-file-excel";
		break;

		case 'css':
			$class = "fa-file-code";
		break;

		case 'avif':
		case 'gif':
		case 'jpg':
		case 'jpeg':
		case 'png':
		case 'tif':
			$class = "fa-file-image";
		break;

		case 'ppt':
		case 'pptx':
			$class = "fa-file-powerpoint";
		break;

		case 'wmv':
		case 'avi':
		case 'mpg':
			$class = "fa-file-video";
		break;

		case 'doc':
		case 'docx':
			$class = "fa-file-word";
		break;

		case 'zip':
		case 'tar':
			$class = "fa-file-archive";
		break;

		case 'txt':
			$class = "fa-file-alt";
		break;

		default:
			$class = "fa-file";
		break;
	}

	return "<i class='fa ".$class." ".$data['size']."'></i>";
}

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

function filter_style_var($value)
{
	if(is_array($value))
	{
		do_log(__FUNCTION__." - Not a string: ".var_export($value, true));
	}

	else if(strpos($value, 'var:') !== false)
	{
		$value = str_replace("var:", "var(--wp--", $value);
		$value = str_replace("|", "--", $value);
		$value .= ")";
	}

	return $value;
}

function parse_block_attributes($data = [])
{
	if(!isset($data['class'])){			$data['class'] = "";}
	if(!isset($data['attributes'])){	$data['attributes'] = [];}
	if(!isset($data['style'])){			$data['style'] = "";}

	$out = "";

	if(isset($data['attributes']['align']))
	{
		$data['class'] .= ($data['class'] != '' ? " " : "")."align".$data['attributes']['align'];
	}

	if(isset($data['attributes']['className']) && $data['attributes']['className'] != '')
	{
		$data['class'] .= ($data['class'] != '' ? " " : "").$data['attributes']['className'];
	}

	if($data['class'] != '')
	{
		$out .= " class='".$data['class']."'";
	}

	if(isset($data['attributes']['style']) && is_array($data['attributes']['style']))
	{
		foreach($data['attributes']['style'] as $key_parent => $arr_value_parent)
		{
			switch($key_parent)
			{
				case 'border':
					foreach($arr_value_parent as $key_child => $value)
					{
						switch($key_child)
						{
							case 'radius':
								$data['style'] .= "border-radius: ".$value."; overflow: hidden;";
							break;

							default:
								do_log(__FUNCTION__.": The key child '".$key_parent."->".$key_child."' with value '".var_export($arr_value_parent, true)."' has to be taken care of");
							break;
						}
					}
				break;

				case 'color':
					foreach($arr_value_parent as $key_child => $value)
					{
						switch($key_child)
						{
							case 'background':
								$data['style'] .= "background-color: ".$value.";";
							break;

							case 'text':
								$data['style'] .= "color: ".$value.";";
							break;

							default:
								do_log(__FUNCTION__.": The key child '".$key_parent."->".$key_child."' with value '".var_export($arr_value_parent, true)."' has to be taken care of");
							break;
						}
					}
				break;

				case 'elements':
				case 'spacing':
					foreach($arr_value_parent as $key_child => $arr_value_child)
					{
						switch($key_child)
						{
							case 'link':
								foreach($arr_value_child as $key_grandchild => $arr_value_grandchild)
								{
									switch($key_grandchild)
									{
										case 'color':
											foreach($arr_value_grandchild as $key_grandgrandchild => $arr_value_grandgrandchild)
											{
												switch($key_grandgrandchild)
												{
													case 'text':
														$data['style'] .= "color: ".filter_style_var($arr_value_grandgrandchild).";";
													break;

													default:
														do_log(__FUNCTION__.": The key grandgrandchild '".$key_grandgrandchild."' with value '".var_export($arr_value_grandchild, true)."' has to be taken care of");
													break;
												}
											}
										break;

										default:
											do_log(__FUNCTION__.": The key grandchild '".$key_grandchild."' with value '".var_export($arr_value_child, true)."' has to be taken care of");
										break;
									}
								}
							break;

							case 'margin':
							case 'padding':
								foreach($arr_value_child as $key_grandchild => $value)
								{
									switch($key_grandchild)
									{
										case 'top':
										case 'right':
										case 'bottom':
										case 'left':
											$data['style'] .= $key_child."-".$key_grandchild.": ".filter_style_var($value).";";
										break;

										default:
											do_log(__FUNCTION__.": The key grandchild '".$key_grandchild."' with value '".var_export($arr_value_child, true)."' has to be taken care of");
										break;
									}
								}
							break;

							default:
								do_log(__FUNCTION__.": The key child '".$key_parent."->".$key_child."' with value '".var_export($arr_value_parent, true)."' has to be taken care of");
							break;
						}
					}
				break;

				case 'typography':
					foreach($arr_value_parent as $key_child => $value)
					{
						switch($key_child)
						{
							case 'fontSize':
								$data['style'] .= "font-size: ".$value.";";
							break;

							default:
								do_log(__FUNCTION__.": The key child '".$key_parent."->".$key_child."' with value '".var_export($arr_value_parent, true)."' has to be taken care of");
							break;
						}
					}
				break;

				default:
					do_log(__FUNCTION__.": The key parent '".$key_parent."' with value '".var_export($arr_value_parent, true)."' has to be taken care of");
				break;
			}
		}
	}

	if($data['style'] != '')
	{
		$out .= " style='".$data['style']."'";
	}

	return $out;
}

function get_form_button_classes($out = "")
{
	if(is_admin())
	{
		// Do nothing
	}

	else if(wp_is_block_theme())
	{
		$out .= ($out != '' ? " " : "")."wp-block-button";
	}

	else
	{
		$out .= ($out != '' ? " " : "")."form_button";
	}

	if(strpos($out, "flex_flow") !== false)
	{
		$plugin_include_url = plugin_dir_url(__FILE__);

		mf_enqueue_style('style_base_flex_flow', $plugin_include_url."style_flex_flow.php");
	}

	if($out != '')
	{
		return " class='".$out."'";
	}

	else
	{
		return "";
	}
}

function get_media_library($data)
{
	global $obj_base, $is_media_library_init;

	if(!isset($data['type'])){			$data['type'] = 'file';}
	if(!isset($data['multiple'])){		$data['multiple'] = false;}
	if(!isset($data['label'])){			$data['label'] = '';}
	if(!isset($data['name'])){			$data['name'] = '';}
	if(!isset($data['return_to'])){		$data['return_to'] = '';}
	if(!isset($data['return_type'])){	$data['return_type'] = '';}
	if(!isset($data['value'])){			$data['value'] = '';}
	if(!isset($data['description'])){	$data['description'] = '';}

	do_action('load_font_awesome');

	$add_file_text = __("Add File", 'lang_base');
	$change_file_text = __("Change File", 'lang_base');
	$insert_file_text = __("Insert File", 'lang_base');
	$insert_text = __("Insert", 'lang_base');

	if(!isset($is_media_library_init))
	{
		$plugin_include_url = plugin_dir_url(__FILE__);

		wp_enqueue_media();
		mf_enqueue_style('style_media_library', $plugin_include_url."style_media_library.css");
		mf_enqueue_script('script_media_library', $plugin_include_url."script_media_library.js", array(
			'add_file_text' => $add_file_text,
			'change_file_text' => $change_file_text,
			'insert_file_text' => $insert_file_text,
			'insert_text' => $insert_text,
		));

		$is_media_library_init = true;
	}

	$out = "<div class='mf_media_library' data-type='".$data['type']."' data-multiple='".$data['multiple']."' data-return_to='".$data['return_to']."' data-return_type='".$data['return_type']."'>
		<div>";

			if($data['label'] != '')
			{
				$out .= "<label>".$data['label']."</label>";
			}

			$out .= "<div".apply_filters('get_flex_flow', "", ['class' => ['tight']]).">";

				if($data['name'] != '')
				{
					$out .= "<div class='media_container'>"
						."<img src='".$data['value']."'>"
						."<span><i class='fa fa-file fa-5x' title='".$data['value']."'></i></span>"
						."<a href='#' title='".__("Delete", 'lang_base')."'><i class='fa fa-trash red fa-lg'></i></a>"
						.input_hidden(array('name' => $data['name'], 'value' => $data['value'], 'allow_empty' => true))
					."</div>";
				}

				$out .= "<div".get_form_button_classes("is-style-outline").">"
					.show_button(array('type' => 'button', 'text' => $add_file_text, 'class' => "button"))
				."</div>
			</div>
		</div>";

		if($data['description'] != '')
		{
			$out .= "<p class='description'>".$data['description']."</p>";
		}

	$out .= "</div>";

	return $out;
}

function get_media_button($data = [])
{
	global $obj_base, $is_media_button_init;

	$out = "";

	if(!isset($data['name'])){				$data['name'] = "mf_media_urls";}
	if(!isset($data['label'])){				$data['label'] = "";}
	if(!isset($data['text'])){				$data['text'] = __("Add Attachment", 'lang_base');}
	if(!isset($data['value'])){				$data['value'] = "";}
	if(!isset($data['show_add_button'])){	$data['show_add_button'] = IS_AUTHOR;}
	if(!isset($data['multiple'])){			$data['multiple'] = true;}
	if(!isset($data['max_file_uploads'])){	$data['max_file_uploads'] = 0;}
	if(!isset($data['description'])){		$data['description'] = '';}

	if($data['show_add_button'] == true || $data['value'] != '')
	{
		if($data['multiple'] == false)
		{
			$data['max_file_uploads'] = 1;
		}

		$out .= "<div class='mf_media_button' data-max_file_uploads='".$data['max_file_uploads']."'>";

			if($data['label'] != '')
			{
				$out .= "<label>".$data['label']."</label>";
			}

			if($data['show_add_button'] == true)
			{
				if(!isset($is_media_button_init))
				{
					do_action('load_font_awesome');

					$plugin_include_url = plugin_dir_url(__FILE__);

					wp_enqueue_media();
					mf_enqueue_style('style_media_button', $plugin_include_url."style_media_button.css");
					mf_enqueue_script('script_media_button', $plugin_include_url."script_media_button.js", array(
						'no_attachment_link' => __("The Media Library did not return a link to the file you added. Please try again and make sure that Link To is set to Media File", 'lang_base'),
						'unknown_title' => __("Unknown title", 'lang_base'),
						'confirm_question' => __("Are you sure?", 'lang_base'),
					));

					$is_media_button_init = true;
				}

				$out .= "<div".get_form_button_classes("is-style-outline").">
					<div class='button insert-media wp-block-button__link'>".$data['text']."</div>
					<span></span>
				</div>";
			}

			$out .= "<div class='mf_media_raw'></div>
			<table".apply_filters('get_table_attr', "", ['class' => ["mf_media_list", "hide"]])."><tbody></tbody></table>"
			//."<textarea name='".$data['name']."' class='mf_media_urls'>".$data['value']."</textarea>"
			.input_hidden(array('name' => $data['name'], 'value' => $data['value'], 'allow_empty' => true, 'xtra' => "class='mf_media_urls'"));

			if($data['description'] != '')
			{
				$out .= "<p class='description'>".$data['description']."</p>";
			}

		$out .= "</div>";
	}

	return $out;
}

function get_attachment_to_send($string)
{
	global $wpdb, $obj_base, $error_text;

	$arr_ids = $arr_files = [];

	if($string != '')
	{
		$arr_attachments = explode(",", $string);

		foreach($arr_attachments as $attachment)
		{
			@list($file_name, $file_url, $file_id) = explode("|", $attachment);

			if(!($file_id > 0) && $file_url != '')
			{
				$file_id_temp = get_attachment_id_by_url($file_url);

				if($file_id_temp > 0)
				{
					$file_id = $file_id_temp;
				}

				/*else
				{
					do_log("I could not find the file from the URL ".$wpdb->last_query);
				}*/
			}

			if(!($file_id > 0) && $file_name != '')
			{
				$file_id_temp = $wpdb->get_var($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_type = %s AND (post_title = %s OR post_name = %s)", 'attachment', $file_name, $file_name));

				if($file_id_temp > 0)
				{
					$file_id = $file_id_temp;
				}

				/*else
				{
					do_log("I could not find the file from the name ".$wpdb->last_query);
				}*/
			}

			if($file_id > 0)
			{
				$arr_ids[] = $file_id;
			}

			if($file_url != '')
			{
				//$file_url = WP_CONTENT_DIR.str_replace(site_url()."/wp-content", "", $file_url);
				$file_url = str_replace(WP_CONTENT_URL, WP_CONTENT_DIR, $file_url);

				if(file_exists($file_url))
				{
					$arr_files[] = $file_url;
				}
			}
		}

		if(count($arr_ids) == 0 && count($arr_files) == 0)
		{
			$error_text = sprintf(__("The file (%s) could not be found in the DB", 'lang_base'), $string);
		}
	}

	return array($arr_files, $arr_ids);
}

function get_attachment_data_by_id($id)
{
	global $wpdb;

	$result = $wpdb->get_results($wpdb->prepare("SELECT post_title, guid FROM ".$wpdb->posts." WHERE post_type = %s AND ID = '%d' LIMIT 0, 1", 'attachment', $id));

	if($wpdb->num_rows > 0)
	{
		$r = $result[0];

		return array($r->post_title, $r->guid);
	}
}

if(!function_exists('get_attachment_id_by_url'))
{
	function get_attachment_id_by_url($url)
	{
		global $wpdb;

		@list($rest, $parsed_url) = explode(parse_url(WP_CONTENT_URL, PHP_URL_PATH), $url);

		if($parsed_url == '')
		{
			do_log("get_attachment_id_by_url Error: ".parse_url(WP_CONTENT_URL, PHP_URL_PATH)." could not be exploded by ".$url);
		}

		$parsed_url = preg_replace("/\-\d+x\d+\./", ".", $parsed_url);

		if($parsed_url != '')
		{
			return $wpdb->get_var($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_type = %s AND guid RLIKE %s", 'attachment', $parsed_url));
		}
	}
}

function mf_format_number($in, $dec = 2)
{
	if(is_string($in))
	{
		$in = (float)$in;
	}

	if($in == null)
	{
		$in = 0;
	}

	return number_format($in, 0, '.', '') == $in ? number_format($in, 0, '.', ' ') : number_format($in, $dec, '.', ' ');
}

function get_current_user_role($id = 0) // Change into function get_user_role()???
{
	global $obj_base;

	if(!($id > 0))
	{
		$id = get_current_user_id();
	}

	$user_data = get_userdata($id);

	return (isset($user_data->roles[0]) ? $user_data->roles[0] : "(".__("unknown", 'lang_base').")");
}

function get_next_cron($data = [])
{
	global $obj_base;

	if(!isset($data['raw'])){		$data['raw'] = false;}

	$out = "";

	$date_next_schedule = date("Y-m-d H:i:s", wp_next_scheduled('cron_base'));

	$mins = time_between_dates(array('start' => date("Y-m-d H:i:s"), 'end' => $date_next_schedule, 'type' => 'round', 'return' => 'minutes'));

	if($data['raw'] == true)
	{
		$out = $date_next_schedule;
	}

	else
	{
		if($mins > 0 && $mins < 60)
		{
			$out = sprintf(($mins == 1 ? __("in %d minute", 'lang_base') : __("in %d minutes", 'lang_base')), $mins);
		}

		else if($mins == 0)
		{
			$out = __("at any moment", 'lang_base');
		}

		else
		{
			$out = format_date($date_next_schedule);
		}

		if(($mins < -1 || $mins > 1) && IS_SUPER_ADMIN)
		{
			$option_cron_started = get_option('option_cron_started');
			$option_cron_ended = get_option('option_cron_ended');

			if($option_cron_ended < date("Y-m-d H:i:s") && $option_cron_ended >= $option_cron_started)
			{
				$out .= "&nbsp;(<a href='".admin_url("options-general.php?page=".BASE_OPTIONS_PAGE."&action=run_cron_now#settings_base")."'>".__("Run Now", 'lang_base')."</a>";

					if($mins < -1)
					{
						$out .= ", <a href='".admin_url("options-general.php?page=".BASE_OPTIONS_PAGE."&action=run_cron_now_v2#settings_base")."'>v2</a>";
					}

				$out .= ")";
			}
		}
	}

	return $out;
}

function show_settings_fields($data)
{
	if(!isset($data['area'])){		$data['area'] = '';}
	if(!isset($data['object'])){	$data['object'] = '';}
	if(!isset($data['settings'])){	$data['settings'] = [];}
	if(!isset($data['args'])){		$data['args'] = [];}
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

		register_setting(BASE_OPTIONS_PAGE, $handle, array(
			'type' => 'string',
			'sanitize_callback' => $data['callback'],
		));
	}
}

function get_setting_key($function_name, $args = [])
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

function get_source_version($file)
{
	$version = '';

	if($version == '' && strpos($file, WP_CONTENT_URL) !== false)
	{
		$file_dir = str_replace(WP_CONTENT_URL, WP_CONTENT_DIR, $file);

		if(file_exists($file_dir))
		{
			$version = filemtime($file_dir);
		}
	}

	if($version == '')
	{
		$version = date("YmdHis");
	}

	if($version != '' && strpos($file, $version))
	{
		$version = null;
	}

	return $version;
}

function mf_enqueue_style($handle, $file = "", $dep = [])
{
	global $wp_styles;

	if(isset($wp_styles->registered[$handle]))
	{
		$existing_src = $wp_styles->registered[$handle]->src;

		if($existing_src !== $file)
		{
			do_log(__FUNCTION__.": The handle ".$handle." has already been enqueued with another file (".$existing_src." !== ".$file.")");
		}
	}

	else
	{
		$version = get_source_version($file);

		wp_enqueue_style($handle, $file, $dep, $version);
	}
}

function mf_enqueue_script($handle, $file = "", $translation = [])
{
	global $wp_scripts;

	if($file != '')
	{
		$version = get_source_version($file);

		if(isset($wp_scripts->registered[$handle]))
		{
			$existing_src = $wp_scripts->registered[$handle]->src;

			if($existing_src !== $file)
			{
				do_log(__FUNCTION__.": The handle ".$handle." has already been enqueued with another file (".$existing_src." !== ".$file.")");
			}
		}

		else if(is_array($translation) && count($translation) > 0)
		{
			wp_register_script($handle, $file, array('jquery'), $version, true);

			if(!wp_script_is($handle, 'done'))
			{
				wp_localize_script($handle, $handle, $translation);
			}

			wp_enqueue_script($handle);
		}

		else
		{
			wp_enqueue_script($handle, $file, array('jquery'), $version, true);
		}
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

	$roles = [];

	foreach($option as $key => $value)
	{
		$roles[$key] = $value['name'];
	}

	return $roles;
}

function get_all_roles($data = [])
{
	global $wpdb, $wp_roles;

	if(!isset($data['orig'])){	$data['orig'] = false;}

	if($data['orig'] == true)
	{
		$roles = roles_option_to_array(get_option($wpdb->prefix.'user_roles_orig'));
	}

	else
	{
		if(!function_exists('is_plugin_active') || function_exists('is_plugin_active') && is_plugin_active("mf_users/index.php"))
		{
			if(class_exists('mf_users'))
			{
				$obj_users = new mf_users();
				$obj_users->hide_roles();
			}
		}

		$roles = $wp_roles->get_names();
	}

	if(count($roles) == 0)
	{
		do_log("I could not find any roles for this site...? (Use 'User Role Editor' to update all roles)");
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

function get_yes_no_for_select($data = [])
{
	if(!isset($data['add_choose_here'])){	$data['add_choose_here'] = (isset($data['choose_here_text']));}
	if(!isset($data['choose_here_text'])){	$data['choose_here_text'] = __("Choose Here", 'lang_base');}
	if(!isset($data['return_integer'])){	$data['return_integer'] = false;}

	$arr_data = [];

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

function get_roles_for_select($data = [])
{
	global $obj_base;

	if(!isset($data['array'])){				$data['array'] = [];}
	if(!isset($data['add_choose_here'])){	$data['add_choose_here'] = false;}
	if(!isset($data['choose_here_text'])){	$data['choose_here_text'] = __("Choose Here", 'lang_base');}
	if(!isset($data['strict_key'])){		$data['strict_key'] = false;}
	if(!isset($data['use_capability'])){	$data['use_capability'] = true;}
	if(!isset($data['exclude'])){			$data['exclude'] = [];}

	if($data['add_choose_here'] == true)
	{
		$data['array'][''] = "-- ".$data['choose_here_text']." --";
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

		if(!isset($data['array'][$key]) && $key != '' && !in_array($key, $data['exclude']))
		{
			$data['array'][$key] = $value;
		}
	}

	return $data['array'];
}

function get_users_for_select($data = [])
{
	global $obj_base;

	if(!isset($data['add_choose_here'])){	$data['add_choose_here'] = true;}
	if(!isset($data['choose_here_text'])){	$data['choose_here_text'] = __("Choose Here", 'lang_base');}
	if(!isset($data['include'])){			$data['include'] = [];}
	if(!isset($data['exclude_inactive'])){	$data['exclude_inactive'] = true;}
	if(!isset($data['callback'])){			$data['callback'] = '';}

	$data_temp = array(
		'orderby' => 'display_name',
		'order' => 'ASC',
		'fields' => array('ID', 'display_name', 'user_email'),
	);

	if(is_array($data['include']) && count($data['include']) > 0)
	{
		$data_temp['role__in'] = $data['include'];
	}

	$arr_users = get_users($data_temp);

	$arr_data = [];

	if($data['add_choose_here'] == true)
	{
		$arr_data[''] = "-- ".$data['choose_here_text']." --";
	}

	foreach($arr_users as $user)
	{
		$user_data = get_userdata($user->ID);

		//$user = apply_filters('filter_user_for_select', $user, $user_data);

		if($data['exclude_inactive'] == false || (isset($user_data->roles[0]) && $user_data->roles[0] != '')) // && !isset($user->exclude_from_list)
		{
			if($data['callback'] != '' && is_callable($data['callback']))
			{
				$arr_data = call_user_func($data['callback'], $data, $user, $arr_data);
			}

			else
			{
				if($user_data->display_name != '')
				{
					$arr_data[$user->ID] = $user_data->display_name;
				}

				else if($user_data->first_name != '' && $user_data->last_name != '')
				{
					$arr_data[$user->ID] = $user_data->first_name." ".$user_data->last_name;
				}
			}
		}
	}

	return $arr_data;
}

function get_post_types_for_select($data = [])
{
	global $obj_base;

	if(!isset($data['include'])){		$data['include'] = array('ids', 'types', 'special');}
	if(!isset($data['post_status'])){	$data['post_status'] = 'publish';}
	if(!isset($data['add_is'])){		$data['add_is'] = true;}

	$opt_groups = is_array($data['include']) && count($data['include']) > 1;

	$arr_data = [];

	if(in_array('ids', $data['include']))
	{
		$arr_pages = [];
		get_post_children(array('post_status' => $data['post_status']), $arr_pages);

		if(count($arr_pages) > 0)
		{
			$page_for_posts = get_option('page_for_posts');

			if($opt_groups == true)
			{
				$arr_data['opt_start_pages'] = __("Pages", 'lang_base');
			}

				foreach($arr_pages as $post_id => $post_title)
				{
					$key_prefix = "";

					if($post_id == $page_for_posts)
					{
						$key_prefix = "disabled_";
					}

					if($data['add_is'] == true)
					{
						$arr_data[$key_prefix.'is_page('.$post_id.')'] = $post_title;
					}

					else
					{
						$arr_data[$key_prefix.$post_id] = $post_title;
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

						$arr_tax = get_object_taxonomies($post_type->name, 'objects');

						foreach($arr_tax as $taxonomy)
						{
							if($taxonomy->public == 1)
							{
								$arr_data['is_tax("'.$taxonomy->name.'")'] = " - ".$taxonomy->label;
							}
						}
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
		$arr_data['is_404()'] = "404";
		//$arr_data['is_archive()'] = "Archive";

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

		$show_on_front = get_option('show_on_front');
		$page_on_front = get_option('page_on_front');
		$page_for_posts = get_option('page_for_posts');

		$front_page_title = __("Front Page", 'lang_base');
		$home_title = __("Home", 'lang_base');

		switch($show_on_front)
		{
			case 'post':
				if($page_for_posts > 0)
				{
					$front_page_title = $home_title = get_the_title($page_for_posts);
				}
			break;

			case 'page':
				if($page_on_front > 0)
				{
					$front_page_title = get_the_title($page_on_front);
				}

				if($page_for_posts > 0)
				{
					$home_title = get_the_title($page_for_posts);
				}
			break;
		}

		$arr_data['is_front_page()'] = $front_page_title;
		$arr_data['is_home()'] = $home_title;
		//$arr_data['is_page()'] = "Page";
		$arr_data['is_search()'] = __("Search", 'lang_base');
		//$arr_data['is_single()'] = "Single";
		//$arr_data['is_sticky()'] = "Sticky";
	}

	return $arr_data;
}

function get_categories_for_select($data = [])
{
	global $obj_base;

	if(!isset($data['add_choose_here'])){	$data['add_choose_here'] = false;}
	if(!isset($data['hide_empty'])){		$data['hide_empty'] = true;}
	if(!isset($data['hierarchical'])){		$data['hierarchical'] = true;}

	$arr_data = [];

	if($data['add_choose_here'] == true)
	{
		$arr_data[''] = "-- ".__("Choose Here", 'lang_base')." --";
	}

	$arr_categories = get_categories(array(
		'hierarchical' => $data['hierarchical'],
		'hide_empty' => $data['hide_empty'],
	));

	foreach($arr_categories as $category)
	{
		$arr_data[$category->cat_ID] = ($data['hierarchical'] && $category->parent > 0 ? "&nbsp;&nbsp;&nbsp;" : "").$category->name;
	}

	return $arr_data;
}

/*if(!function_exists('array_sort'))
{
	function array_sort($data)
	{
		global $obj_base;

		if(!isset($obj_base))
		{
			$obj_base = new mf_base();
		}

		return $obj_base->array_sort($data);
	}
}*/

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

function get_url_content($data = [])
{
	if(!isset($data['timeout'])){					$data['timeout'] = 10;}
	if(!isset($data['follow_redirect'])){			$data['follow_redirect'] = false;}
	if(!isset($data['catch_head'])){				$data['catch_head'] = false;}
	if(!isset($data['catch_cookie'])){				$data['catch_cookie'] = false;}
	if(!isset($data['debug'])){						$data['debug'] = false;}
	if(!isset($data['headers'])){					$data['headers'] = [];}
	if(!isset($data['request'])){					$data['request'] = 'get';}
	if(!isset($data['content_type'])){				$data['content_type'] = '';}
	if(!isset($data['password'])){					$data['password'] = '';}
	if(!isset($data['post_data'])){					$data['post_data'] = '';}
	if(!isset($data['ca_path'])){					$data['ca_path'] = '';}
	if(!isset($data['ssl_cert_path'])){				$data['ssl_cert_path'] = '';}
	if(!isset($data['ssl_key_path'])){				$data['ssl_key_path'] = '';}

	$data['url'] = validate_url($data['url'], false);

	$ch = curl_init($data['url']);

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLINFO_HEADER_OUT, true);

	if($data['catch_cookie'] == true)
	{
		curl_setopt($ch, CURLOPT_HEADER, true);
	}

	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, ($data['timeout'] / 2));
	curl_setopt($ch, CURLOPT_TIMEOUT, $data['timeout']);
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0 Safari/537.36");

	if($data['debug'] == true)
	{
		ob_start();
		$verbose_output = fopen('php://output', 'w');

		curl_setopt($curl, CURLOPT_VERBOSE, true);
		curl_setopt($curl, CURLOPT_STDERR, $verbose_output);
	}

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

	else
	{
		// This will prevent server from returning HTTP_CODE 0 due to certificate
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
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

	if($data['request'] == 'post' || $data['post_data'] != '')
	{
		curl_setopt($ch, CURLOPT_POST, true);
	}

	if($data['post_data'] != '')
	{
		if(is_array($data['post_data']))
		{
			$post_data_temp = "";

			foreach($data['post_data'] as $key => $value)
			{
				$post_data_temp .= ($post_data_temp != '' ? "&" : "").$key."=".$value;
			}

			$data['post_data'] = $post_data_temp;
		}

		curl_setopt($ch, CURLOPT_POSTFIELDS, $data['post_data']);

		$data['headers'][] = 'Content-Length: '.strlen($data['post_data']);
	}

	if(count($data['headers']) > 0)
	{
		curl_setopt($ch, CURLOPT_HTTPHEADER, $data['headers']);
	}

	if($data['debug'] == true)
	{
		$log_message = "cURL was run but not completed";

		do_log($log_message." (".var_export($data, true).")");
	}

	if($data['catch_head'] == true)
	{
		$headers_raw = [];

		curl_setopt($ch, CURLOPT_HEADERFUNCTION,
			function($curl, $header) use (&$headers_raw)
			{
				$len = strlen($header);
				$header = explode(':', $header, 2);

				if(count($header) < 2) // Ignore invalid headers
				{
					return $len;
				}

				if(isset($headers_raw[trim($header[0])])) // If already set this might be a secondary header with the same key and should therefor be added as an additional index
				{
					if(!is_array($headers_raw[trim($header[0])]))
					{
						$headers_raw[trim($header[0])] = array($headers_raw[trim($header[0])]);
					}

					$headers_raw[trim($header[0])][] = trim($header[1]);
				}

				else
				{
					$headers_raw[trim($header[0])] = trim($header[1]);
				}

				return $len;
			}
		);
	}

	$content = curl_exec($ch);

	if($data['catch_cookie'] == true)
	{
		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$header = substr($content, 0, $header_size);
		$content = substr($content, $header_size);

		preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $header, $matches);

		$arr_cookies = [];

		foreach($matches[1] as $item)
		{
			parse_str($item, $cookie);
			$arr_cookies = array_merge($arr_cookies, $cookie);
		}
	}

	if($data['debug'] == true)
	{
		do_log($log_message, 'trash');

		fclose($verbose_output);
	}

	if($data['catch_head'] == true && curl_errno($ch))
	{
		$headers_raw['curl_error'] = curl_error($ch);
	}

	$headers = curl_getinfo($ch);

	if($data['follow_redirect'] == true)
	{
		switch($headers['http_code'])
		{
			case 301:
			case 302:
				if(isset($headers['redirect_url']) && $headers['redirect_url'] != $data['url'])
				{
					$data['url'] = $headers['redirect_url'];
					$data['follow_redirect'] = false;
					$data['catch_head'] = true;
					$data['catch_cookie'] = true;

					list($content, $headers, $arr_cookies) = get_url_content($data);
				}
			break;
		}
	}

	if($data['catch_cookie'] == true)
	{
		if($data['debug'] == true)
		{
			$headers['debug'] = ob_get_clean();
		}

		$out = array($content, array_merge($headers, $headers_raw), $arr_cookies);
	}

	else if($data['catch_head'] == true)
	{
		if($data['debug'] == true)
		{
			$headers['debug'] = ob_get_clean();
		}

		$out = array($content, array_merge($headers, $headers_raw));
	}

	else
	{
		$out = $content;
	}

	curl_close($ch);

	return $out;
}

function get_notification($data = [])
{
	global $error_text, $notice_text, $done_text;

	$plugin_include_url = plugin_dir_url(__FILE__);

	mf_enqueue_style('style_base_notification', $plugin_include_url."style_notification.css");

	if(!isset($data['add_container'])){		$data['add_container'] = false;}

	$out = "";

	if(isset($error_text) && $error_text != '')
	{
		$out .= "<div class='error'>
			<p>".$error_text."</p>
		</div>";
	}

	else if(isset($notice_text) && $notice_text != '')
	{
		$out .= "<div class='update-nag'>
			<p>".$notice_text."</p>
		</div>";
	}

	else if(isset($done_text) && $done_text != '')
	{
		$out .= "<div class='updated'>
			<p>".$done_text."</p>
		</div>";
	}

	$error_text = $notice_text = $done_text = "";

	if($out != '' && $data['add_container'] == true)
	{
		$out = "<div class='notification'>".$out."</div>";
	}

	return $out;
}

function add_columns($array, $debug = false)
{
	global $wpdb;

	foreach($array as $table => $arr_col)
	{
		foreach($arr_col as $column => $value)
		{
			$has_column = does_column_exist($table, $column);

			if($debug == true)
			{
				do_log("add_columns - check: ".$wpdb->last_query);
			}

			if($has_column == false)
			{
				$value = str_replace("[table]", $table, $value);
				$value = str_replace("[column]", $column, $value);

				$wpdb->query($value);

				if($debug == true)
				{
					do_log("add_columns - add: ".$wpdb->last_query);
				}
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
			if(does_column_exist($table, $column))
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
			$arr_existing_indexes = [];

			$result = $wpdb->get_results("SHOW INDEX FROM ".esc_sql($table));

			foreach($result as $r)
			{
				$arr_existing_indexes[] = $r->Column_name;
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

function mf_redirect($location, $arr_vars = [], $method = 'post')
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

		if(isset($_SESSION[$in]) && $force_req_type == '' || $force_req_type == "session" || substr($in, 0, 3) == "ses")
		{
			if(isset($_SESSION[$in]) && $_SESSION[$in] != '')
			{
				$temp = $_SESSION[$in];
			}
		}

		else if(isset($_POST[$in]) && $force_req_type == '' || $force_req_type == "post")
		{
			if(isset($_POST[$in]) && $_POST[$in] != '')
			{
				$temp = $_POST[$in];
			}
		}

		else if(isset($_GET[$in]) && $force_req_type == '' || $force_req_type == "get")
		{
			if(isset($_GET[$in]) && $_GET[$in] != '')
			{
				$temp = $_GET[$in];
			}
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
		$date_format = '/^\d{4}-\d{2}-\d{2}$/';
		$is_date_format = preg_match($date_format, $temp);

		if($temp != '' && !$is_date_format)
		{
			// Add century in front of date if not formatted properly from the beginning
			if(preg_match('/^\d{2}-\d{2}-\d{2}$/', $temp) || preg_match('/^\d{6}$/', $temp))
			{
				$current_century = substr(date("Y"), 0, 2);
				$temp = (substr($out, 0, 2) > date("y") ? ($current_century - 1) : $current_century).$temp;
			}

			$temp = date("Y-m-d", strtotime($temp));

			$is_date_format = preg_match($date_format, $temp);
		}

		if($temp == '' || ($is_date_format && substr($temp, 0, 4) > 1970 && substr($temp, 0, 4) < 2038))
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
				if($return_empty == false)
				{
					$out = trim($temp);
				}
			}
		}
	}

	else if($type == 'time')
	{
		$is_time_format = preg_match('/^\d{2}:\d{2}$/', $temp);

		if($temp != '' && !$is_time_format)
		{
			if(strlen($temp) < 3)
			{
				$temp = zeroise($temp, 2).":00";
			}

			else
			{
				$temp = date("H:i", strtotime(date("Y-m-d")." ".$temp));
			}

			$is_time_format = preg_match('/^\d{2}:\d{2}$/', $temp);
		}

		if($temp == '' || $is_time_format)
		{
			$out = $temp;
		}

		else
		{
			if($temp == "00:00")
			{
				$out = "";
			}

			else
			{
				if($return_empty == false)
				{
					$out = trim($temp);
				}
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
			$out = [];
		}
	}

	else if($type == 'char' || $type2 == 'str' || in_array($type, array('address', 'city', 'country', 'name', 'zip')))
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
	if(!isset($data['type'])){				$data['type'] = '';}
	if(!isset($data['custom_tag'])){		$data['custom_tag'] = 'div';}
	if(!isset($data['xtra_class'])){		$data['xtra_class'] = "";}
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
	if(!isset($data['title'])){				$data['title'] = "";}
	if(!isset($data['xtra'])){				$data['xtra'] = "";}
	if(!isset($data['field_class'])){		$data['field_class'] = "mf_form_field";}
	if(!isset($data['datalist'])){			$data['datalist'] = [];}
	if(!isset($data['suffix'])){			$data['suffix'] = "";}
	if(!isset($data['description'])){		$data['description'] = "";}

	$data['value'] = str_replace("\\", "", $data['value']);

	switch($data['type'])
	{
		/*case 'month':
			$plugin_include_url = plugin_dir_url(__FILE__);

			mf_enqueue_style('jquery-ui-css', $plugin_include_url."jquery-ui.css");
			wp_enqueue_script('jquery-ui-datepicker');
			mf_enqueue_script('script_base_datepicker', $plugin_include_url."script_datepicker.js");

			$data['xtra_class'] .= ($data['xtra_class'] != '' ? " " : "")."mf_datepicker ".$data['type'];
			$data['type'] = "text";
		break;*/

		case 'email':
			$data['autocapitalize'] = $data['autocorrect'] = false;
			$data['xtra'] .= " inputmode='".$data['type']."'";

			if($data['placeholder'] == '')
			{
				$data['placeholder'] = get_placeholder_email();
			}
		break;

		case 'float':
			$data['type'] = 'number';
			$data['xtra'] .= " inputmode='decimal'";
			$data['xtra'] .= " step='any'";
		break;

		case 'int':
		case 'number':
			$data['type'] = 'number';
			$data['xtra'] .= " inputmode='numeric'";
			$data['xtra'] .= " step='any'";
		break;

		case 'tel':
		case 'telno':
			$plugin_include_url = plugin_dir_url(__FILE__);

			mf_enqueue_script('script_base_tel', $plugin_include_url."script_tel.js");

			$data['type'] = 'tel';
			$data['xtra'] .= " inputmode='numeric'";
		break;

		case 'search':
			$data['xtra'] .= " inputmode='".$data['type']."'";
		break;

		case 'url':
			$plugin_include_url = plugin_dir_url(__FILE__);

			mf_enqueue_script('script_base_url', $plugin_include_url."script_url.js");

			$data['autocapitalize'] = $data['autocorrect'] = false;
			$data['xtra'] .= " inputmode='".$data['type']."'";

			if($data['placeholder'] == '')
			{
				$data['placeholder'] = get_site_url();
			}
		break;

		case 'color':
		case 'date':
		case 'datetime-local':
		case 'range':
		case 'time':
		case 'text':
			// Do nothing
		break;

		default:
			$data['type'] = 'text';
		break;
	}

	if($data['value'] == "0000-00-00"){$data['value'] = "";}

	if(preg_match("/\[(.*)\]/", $data['id']))
	{
		$data['xtra'] .= " class='".preg_replace("/\[(.*)\]/", "", $data['id'])."'";
		$data['id'] = '';
	}

	if($data['suffix'] != '')
	{
		$data['xtra_class'] .= ($data['xtra_class'] != '' ? " " : "")."has_suffix";
	}

	$out = "";

	if($data['custom_tag'] != '')
	{
		$out .= "<".$data['custom_tag']." class='form_textfield".($data['xtra_class'] != '' ? " ".$data['xtra_class'] : "")."'>";
	}

		if($data['text'] != '')
		{
			$out .= "<label for='".$data['name']."'>".$data['text']."</label>";
		}

		$out .= "<input type='".$data['type']."'";

			if($data['id'] != '')
			{
				$out .= " id='".$data['id']."'";
			}

			if($data['name'] != '')
			{
				$out .= " name='".$data['name']."'";
			}

			$out .= " value=\"".stripslashes($data['value'])."\"";

			if($data['field_class'] != '')
			{
				$out .= " class='".$data['field_class']."'";
			}

			if($data['required'])
			{
				$plugin_include_url = plugin_dir_url(__FILE__);

				mf_enqueue_script('script_base_required', $plugin_include_url."script_required.js", array('confirm_question' => __("Are you sure?", 'lang_base')));

				$out .= " required";
			}

			if($data['autocorrect'] == false)
			{
				$out .= " autocorrect='off'";
			}

			if($data['autocapitalize'] == false)
			{
				$out .= " autocapitalize='off'";
			}

			if($data['readonly'] == true)
			{
				$out .= " readonly";
			}

			if($data['size'] > 0)
			{
				$out .= " size='".$data['size']."'";
			}

			if($data['maxlength'] > 0)
			{
				$plugin_include_url = plugin_dir_url(__FILE__);

				mf_enqueue_style('style_base_maxlength', $plugin_include_url."style_maxlength.css");
				mf_enqueue_script('script_base_maxlength', $plugin_include_url."script_maxlength.js", array('characters_left_text' => __("characters left", 'lang_base')));

				$out .= " maxlength='".$data['maxlength']."'";
			}

			if($data['placeholder'] != '')
			{
				$out .= " placeholder='".$data['placeholder']."&hellip;'";
			}

			if($data['title'] != '')
			{
				$out .= " title='".$data['title']."'";
			}

			$count_datalist = count($data['datalist']);

			if($count_datalist > 0)
			{
				$out .= " list='".$data['name']."_list'";
			}

			if($data['description'] != '')
			{
				$out .= " aria-describedby='".$data['name']."-description'";
			}

			if($data['xtra'] != '')
			{
				$out .= " ".trim($data['xtra']);
			}

		$out .= ">";

		if($data['suffix'] != '')
		{
			$out .= "<span class='description'>".$data['suffix']."</span>";
		}

		if($data['description'] != '')
		{
			$out .= "<p class='description' id='".$data['name']."-description'>".$data['description']."</p>";
		}

		if($count_datalist > 0)
		{
			$out .= "<datalist id='".$data['name']."_list'>";

				for($i = 0; $i < $count_datalist; $i++)
				{
					$out .= "<option value='".$data['datalist'][$i]."'>";
				}

			$out .= "</datalist>";
		}

	if($data['custom_tag'] != '')
	{
		$out .= "</".$data['custom_tag'].">";
	}

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
	if(!isset($data['required'])){		$data['required'] = false;}
	if(!isset($data['placeholder'])){	$data['placeholder'] = "";}
	if(!isset($data['xtra'])){			$data['xtra'] = "";}
	if(!isset($data['field_class'])){	$data['field_class'] = "mf_form_field";}
	if(!isset($data['suffix'])){		$data['suffix'] = "";}
	if(!isset($data['description'])){	$data['description'] = "";}

	if($data['suffix'] != '')
	{
		$data['xtra_class'] .= ($data['xtra_class'] != '' ? " " : "")."has_suffix";
	}

	$out .= "<div class='form_password".($data['xtra_class'] != '' ? " ".$data['xtra_class'] : "")."'>";

		if($data['text'] != '')
		{
			$out .= "<label for='".$data['name']."'>".$data['text']."</label>";
		}

		$out .= "<input type='password' name='".$data['name']."' value='".$data['value']."' id='".$data['name']."'"
			.$data['xtra'];

			if($data['maxlength'] > 0)
			{
				$plugin_include_url = plugin_dir_url(__FILE__);

				mf_enqueue_style('style_base_maxlength', $plugin_include_url."style_maxlength.css");
				mf_enqueue_script('script_base_maxlength', $plugin_include_url."script_maxlength.js", array('characters_left_text' => __("characters left", 'lang_base')));

				$out .= " maxlength='".$data['maxlength']."'";
			}

			if($data['required'])
			{
				$plugin_include_url = plugin_dir_url(__FILE__);

				mf_enqueue_script('script_base_required', $plugin_include_url."script_required.js", array('confirm_question' => __("Are you sure?", 'lang_base')));

				$out .= " required";
			}

			if($data['placeholder'] != '')
			{
				$out .= " placeholder='".$data['placeholder']."&hellip;'";
			}

			if($data['field_class'] != '')
			{
				$out .= " class='".$data['field_class']."'";
			}

		$out .= ">";

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
	if(!isset($data['class'])){			$data['class'] = "";}
	if(!isset($data['wysiwyg'])){		$data['wysiwyg'] = false;}
	if(!isset($data['name'])){			$data['name'] = "";}
	if(!isset($data['text'])){			$data['text'] = "";}
	if(!isset($data['value'])){			$data['value'] = "";}
	if(!isset($data['xtra'])){			$data['xtra'] = "";}
	if(!isset($data['field_class'])){	$data['field_class'] = "mf_form_field";}
	if(!isset($data['placeholder'])){	$data['placeholder'] = "";}
	if(!isset($data['required'])){		$data['required'] = false;}
	if(!isset($data['description'])){	$data['description'] = "";}

	$data['value'] = str_replace("\\", "", $data['value']);

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
			$out .= "<textarea name='".$data['name']."' id='".$data['name']."'"
				.$data['xtra'];

				if($data['required'])
				{
					$plugin_include_url = plugin_dir_url(__FILE__);

					mf_enqueue_script('script_base_required', $plugin_include_url."script_required.js", array('confirm_question' => __("Are you sure?", 'lang_base')));

					$out .= " required";
				}

				if($data['placeholder'] != '')
				{
					$out .= " placeholder='".$data['placeholder']."&hellip;'";
				}

				if($data['field_class'] != '')
				{
					$out .= " class='".$data['field_class']."'";
				}

			$out .= ">"
				.stripslashes($data['value'])
			."</textarea>";
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

				wp_editor(stripslashes($data['value']), $data['name'], $data);

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
	if(!isset($data['class'])){				$data['class'] = "";}
	if(!isset($data['data'])){				$data['data'] = [];}
	if(!isset($data['name'])){				$data['name'] = "";}
	if(!isset($data['text'])){				$data['text'] = "";}
	if(!isset($data['value'])){				$data['value'] = "";}
	if(!isset($data['xtra'])){				$data['xtra'] = "";}
	if(!isset($data['field_class'])){		$data['field_class'] = "mf_form_field";}
	if(!isset($data['required'])){			$data['required'] = false;}
	if(!isset($data['attributes'])){		$data['attributes'] = [];}
	if(!isset($data['suffix'])){			$data['suffix'] = "";}
	if(!isset($data['description'])){		$data['description'] = "";}
	if(!isset($data['allow_hidden_field'])){$data['allow_hidden_field'] = true;}

	if(!is_array($data['value']) && substr($data['value'], 0, 2) == "<%")
	{
		$data['xtra'] .= ($data['xtra'] != '' ? " " : "")."data-value='".$data['value']."'";
	}

	$obj_base = new mf_base();
	$obj_base->init_form($data);

	if(!isset($data['multiple'])){		$data['multiple'] = $obj_base->is_multiple();}

	if($data['multiple'] == true)
	{
		$data['allow_hidden_field'] = false;
	}

	$out = "";

	$count_temp = count($obj_base->data['data']);

	if($count_temp > 0)
	{
		$container_class = "form_select";

		if($data['multiple'])
		{
			$obj_base->data['xtra'] .= ($obj_base->data['xtra'] != '' ? " " : "")."multiple size='".get_select_size(array_merge($data, array('count' => $count_temp)))."'";

			$container_class .= " form_select_multiple";
		}

		if($count_temp == 1 && $data['allow_hidden_field'])
		{
			$out = $obj_base->get_hidden_field();
		}

		else
		{
			if($obj_base->data['required'])
			{
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

						mf_enqueue_script('script_base_conditions', $plugin_include_url."script_conditions.js");
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

				$out .= "<select";

					if($obj_base->data['name'] != '')
					{
						$out .= " id='".preg_replace("/\[(.*)\]/", "", $obj_base->data['name'])."' name='".$obj_base->data['name']."'";
					}

					if($obj_base->data['xtra'] != '')
					{
						if(strpos($obj_base->data['xtra'], "rel='submit_change'") !== false)
						{
							$plugin_include_url = plugin_dir_url(__FILE__);

							mf_enqueue_script('script_base_submit_change', $plugin_include_url."script_submit_change.js");
						}

						$out .= " ".$obj_base->data['xtra'];
					}

					if($obj_base->data['field_class'] != '')
					{
						$out .= " class='".$obj_base->data['field_class']."'";
					}

				$out .= ">";

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
							if(isset($option[0]) && isset($option[1]))
							{
								$data_text = $option[0];
								$data_desc = $option[1];
							}

							else
							{
								$data_text = $option['name'];
								$data_desc = '';
							}
						}

						else
						{
							$data_text = $option;
							$data_desc = '';

							$option = array(
								'name' => $option,
								'attributes' => [],
							);
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
							if($data['multiple'] && $data_value == ''){}

							else
							{
								$out .= "<option value='".($data_value === 0 ? '' : $data_value)."'";

									if(isset($option['attributes']) && count($option['attributes']) > 0)
									{
										foreach($option['attributes'] as $attr_key => $attr_value)
										{
											$out .= " ".$attr_key."='".$attr_value."'";
										}
									}

									if($is_disabled)
									{
										$out .= " disabled";
										$out .= " class='is_disabled'";
									}

									else if(
										is_array($obj_base->data['value'])
										&& (
											in_array($data_value, $obj_base->data['value'])
											|| in_array(str_replace('"', "&quot;", $data_value), $obj_base->data['value']) // Needed when widget_logic_data gets arr_values
										)
										|| $obj_base->data['value'] == $data_value
									)
									{
										$out .= " selected";
									}

									/*else
									{
										$value1 = implode(", ", $obj_base->data['value']);

										if(preg_match("/is_singular/", $value1) && preg_match("/is_singular/", $data_value))
										{
											do_log("Select or not to select: ".var_export($obj_base->data['value'], true)." --- ".$data_value);
										}
									}*/

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
	if(!isset($data['data'])){			$data['data'] = [];}
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

	if(!isset($data['multiple'])){		$data['multiple'] = $obj_base->is_multiple();}

	$out = "";

	$count_datalist = count($obj_base->data['data']);

	if($count_datalist > 0)
	{
		$plugin_include_url = plugin_dir_url(__FILE__);
		mf_enqueue_style('style_base_radio_multiple', $plugin_include_url."style_radio_multiple.css");

		if($data['multiple'])
		{
			$container_class = "form_checkbox_multiple";
		}

		else
		{
			$container_class = "form_radio_multiple";
		}

		if($count_datalist == 1 && $obj_base->data['required'] && $obj_base->data['text'] != '')
		{
			$out = $obj_base->get_hidden_field();
		}

		else
		{
			if($obj_base->data['required'])
			{
				$plugin_include_url = plugin_dir_url(__FILE__);

				mf_enqueue_script('script_base_required', $plugin_include_url."script_required.js", array('confirm_question' => __("Are you sure?", 'lang_base')));

				//$obj_base->data['xtra'] .= " required";
				$container_class .= " required";
			}

			if($obj_base->data['suffix'] != '')
			{
				$obj_base->data['class'] .= ($obj_base->data['class'] != '' ? " " : "")."has_suffix";
			}

			$out = "<div class='".$container_class.($obj_base->data['class'] != '' ? " ".$obj_base->data['class'] : "")."'>";

				if($obj_base->data['text'] != '')
				{
					$out .= "<label".($obj_base->data['required'] ? " required" : "").">".$obj_base->data['text']."</label>";
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
							if(isset($option[0]))
							{
								$data_text = $option[0];
								$data_desc = (isset($option[1]) ? $option[1] : '');
							}

							else
							{
								$data_text = $option['name'];
								$data_desc = (isset($option['desc']) ? $option['desc'] : '');
							}
						}

						else
						{
							$data_text = $option;
							$data_desc = '';

							$option = array(
								'name' => $option,
								//'attributes' => [],
							);
						}

						if(!isset($option['attributes']))
						{
							$option['attributes'] = [];
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
							if($data_value == '') //$data['multiple'] &&
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
									$compare = ((is_array($obj_base->data['value']) && in_array($data_value, $obj_base->data['value']) || $obj_base->data['value'] == $data_value) ? $data_value : 0);
								}

								if($data['multiple'])
								{
									$out .= show_checkbox(array('name' => $obj_base->data['name'], 'text' => $data_text, 'value' => $data_value, 'compare' => $compare, 'tag' => 'li', 'xtra' => $obj_base->data['xtra'].($is_disabled ? " class='is_disabled' disabled" : ""), 'description' => $data_desc));
								}

								else
								{
									$out .= show_radio_input(array('name' => $obj_base->data['name'], 'text' => $data_text, 'value' => $data_value, 'compare' => $compare, 'tag' => 'li', 'xtra' => $obj_base->data['xtra'].($is_disabled ? " class='is_disabled' disabled" : ""), 'description' => $data_desc));
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
		$plugin_include_url = plugin_dir_url(__FILE__);

		mf_enqueue_script('script_base_required', $plugin_include_url."script_required.js", array('confirm_question' => __("Are you sure?", 'lang_base')));

		$data['xtra'] .= " required";
	}

	if($data['switch'] == 1)
	{
		$plugin_include_url = plugin_dir_url(__FILE__);

		mf_enqueue_style('style_base_switch', $plugin_include_url."style_switch.css");

		$data['xtra_class'] .= ($data['xtra_class'] != '' ? " " : "")."form_switch";
		$data['text'] = "<span><i class='".$data['switch_icon_on']." checked'></i><i class='".$data['switch_icon_off']." unchecked'></i>".apply_filters('get_loading_animation', '')."</span>".$data['text'];
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

function get_form_accents($data)
{
	$out = "";

	if(strlen($data['value']) == 7)
	{
		$out .= show_textfield(array('type' => 'color', 'name' => $data['name'].'_old', 'value' => $data['value']));
	}

	$arr_colors = apply_filters('get_styles_content', '', 'colors');

	if(count($arr_colors) > 0)
	{
		$plugin_include_url = plugin_dir_url(__FILE__);

		mf_enqueue_style('style_base_accents', $plugin_include_url."style_accents.css");

		$out .= "<ul".apply_filters('get_flex_flow', "", ['class' => ['display_theme_colors', 'tight']]).">";

			foreach($arr_colors as $arr_color)
			{
				$out .= show_radio_input(array('name' => $data['name'], 'text' => "<span style='background: ".$arr_color['color']."' title='".$arr_color['name']."'></span>", 'value' => "var(--wp--preset--color--".$arr_color['slug'].")", 'compare' => $data['value'], 'tag' => 'li'));
			}

			$out .= "<li><a href='".admin_url("site-editor.php?p=/styles&section=/colors/palette")."'>".__("Edit", 'lang_navigation')."</a></li>";

		$out .= "</ul>";
	}

	return $out;
}

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
		$plugin_include_url = plugin_dir_url(__FILE__);

		mf_enqueue_script('script_base_required', $plugin_include_url."script_required.js", array('confirm_question' => __("Are you sure?", 'lang_base')));

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
function show_button($data)
{
	if(!isset($data['type'])){	$data['type'] = "submit";}
	if(!isset($data['name'])){	$data['name'] = "";}
	if(!isset($data['class'])){	$data['class'] = "";}
	if(!isset($data['xtra'])){	$data['xtra'] = "";}

	if(strpos($data['xtra'], "rel='confirm'") !== false)
	{
		$plugin_include_url = plugin_dir_url(__FILE__);

		mf_enqueue_script('script_base_confirm', $plugin_include_url."script_confirm.js");
	}

	return "<button type='".$data['type']."'"
		.($data['name'] != '' ? " name='".$data['name']."'" : "")
		." class='".($data['class'] != '' ? $data['class'] : "button-primary").(wp_is_block_theme() ? " wp-block-button__link" : "")."'"
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
		/*if(is_array($data['value']))
		{
			do_log("Error - input_hidden: ".var_export($data, true));
		}*/

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
			//$memory_usage = memory_get_usage();
			$file_size = filesize($data['file']);
			/*$memory_limit = (str_replace("M", "", ini_get('memory_limit')) * MB_IN_BYTES); // Presumes that the limit ends with M (MB)...

			if(($file_size + $memory_usage) > ($memory_limit * .9))
			{
				do_log("We are almost out of memory because the file ".$data['file']." (".show_final_size($file_size).") and used memory (".show_final_size($memory_usage).") is close to the memory limit (".show_final_size($memory_limit).")");
			}

			else
			{*/
				$content = fread($fh, $file_size);
			//}

			fclose($fh);
		}

		else
		{
			do_log("The file could not be opened (".$data['file'].", ".(file_exists($data['file']) ? "Exists" : "Not").")");
		}
	}

	return $content;
}

//
#######################
function get_match($regexp, $in, $all = true)
{
	if($in != null && $in != '')
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

	else
	{
		return "";
	}
}
#######################

//
#######################
function get_match_all($regexp, $in, $all = true)
{
	if($in != null && $in != '')
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

	else
	{
		return "";
	}
}
#######################

//
##################
/*function prepare_file_name($file_base)
{
	return sanitize_title_with_dashes(sanitize_title($file_base))."_".date("ymdHis")."_".wp_hash($file_base);
}*/

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

		$folder = dirname($data['file']);

		$log_message = __FUNCTION__.": I could not create the folder ".$folder;

		if(is_dir($folder))
		{
			do_log($log_message, 'trash');
		}

		else
		{
			if(@mkdir($folder, 0755, true))
			{
				do_log($log_message, 'trash');
			}

			else
			{
				do_log($log_message);
			}
		}

		if($fh = @fopen($data['file'], $data['mode']))
		{
			switch(get_file_suffix($data['file']))
			{
				case 'bz2':
					$data['content'] = bzcompress($data['content']);
				break;

				case 'gz':
					$data['content'] = gzencode($data['content']);
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
			do_log(sprintf("I am sorry but I did not have permission to access %s", $data['file']));
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

				if($data['folder_callback'] != '' && is_callable($data['folder_callback']))
				{
					$data_temp = $data;
					$data_temp['child'] = $child;

					call_user_func($data['folder_callback'], $data_temp);
				}
			}

			else if(is_callable($data['callback']))
			{
				$data_temp = $data;
				$data_temp['file'] = $file;

				call_user_func($data['callback'], $data_temp);
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
				//$arr_header[$i] = stripslashes(strip_tags($arr_header[$i]));

				if($shorten_text == true && strlen($arr_header[$i]) > 15 && strpos($arr_header[$i], "<") !== false)
				{
					$title = $arr_header[$i];
					$content = shorten_text(array('string' => $arr_header[$i], 'limit' => 12));
				}

				else
				{
					$title = "";
					$content = $arr_header[$i];
				}

				$out .= "<th".($title != '' ? " title='".$title."'" : "").($i == 0 ? " class='column-primary'" : "").">".$content."</th>";
			}

		$out .= "</tr>
	</thead>";

	return $out;
}
########################################

function get_post_children($data, &$arr_data = [])
{
	global $wpdb, $obj_base;

	if(!isset($data['add_choose_here'])){	$data['add_choose_here'] = isset($data['choose_here_text']);}
	if(!isset($data['choose_here_text'])){	$data['choose_here_text'] = __("Choose Here", 'lang_base');}
	if(!isset($data['output_array'])){		$data['output_array'] = true;}
	if(!isset($data['allow_depth'])){		$data['allow_depth'] = true;}
	if(!isset($data['depth'])){				$data['depth'] = 0;}

	if(!isset($data['post_type'])){			$data['post_type'] = 'page';}
	if(!isset($data['post_id'])){			$data['post_id'] = ($data['post_type'] == 'attachment' ? -1 : 0);}
	if(!isset($data['post_status'])){		$data['post_status'] = ($data['post_type'] == 'attachment' ? 'inherit' : 'publish');}
	if(!isset($data['include'])){			$data['include'] = [];}
	if(!isset($data['exclude'])){			$data['exclude'] = [];}

	if(!isset($data['join'])){				$data['join'] = '';}
	if(!isset($data['where'])){				$data['where'] = '';}

	//if(!isset($data['is_trusted'])){		$data['is_trusted'] = false;}
	if(!isset($data['meta'])){				$data['meta'] = [];}

	if(!isset($data['group_by'])){			$data['group_by'] = '';}
	if(!isset($data['order_by'])){			$data['order_by'] = 'menu_order';}
	if(!isset($data['order'])){				$data['order'] = 'ASC';}
	if(!isset($data['limit'])){				$data['limit'] = 0;}

	if(!isset($data['count'])){				$data['count'] = false;}
	if(!isset($data['debug'])){				$data['debug'] = false;}

	if(!isset($data['current_id'])){		$data['current_id'] = '';}

	$exclude_post_status = array('auto-draft', 'ignore', 'inherit', 'trash');

	if($data['add_choose_here'] == true)
	{
		$arr_data[''] = "-- ".$data['choose_here_text']." --";
	}

	$out = "";

	// We do not want these to be added to data[] since that will duplicate itself when requesting a deeper level of get_post_children() below
	$query_join = $data['join'];
	$query_where = $data['where'];

	if($data['post_id'] >= 0)
	{
		$query_where .= ($query_where != '' ? " AND " : "")."post_parent = '".esc_sql($data['post_id'])."'";
	}

	if($data['post_status'] != '')
	{
		$query_where .= ($query_where != '' ? " AND " : "")."post_status = '".esc_sql($data['post_status'])."'";
	}

	else
	{
		$query_where .= ($query_where != '' ? " AND " : "")."post_status NOT IN('".implode("','", $exclude_post_status)."')";
	}

	if(count($data['include']) > 0)
	{
		$query_where .= ($query_where != '' ? " AND " : "")."ID IN('".implode("','", $data['include'])."')";
	}

	if(count($data['exclude']) > 0)
	{
		$query_where .= ($query_where != '' ? " AND " : "")."ID NOT IN('".implode("','", $data['exclude'])."')";
	}

	if(count($data['meta']) > 0)
	{
		$arr_keys_used = [];

		foreach($data['meta'] as $key => $value)
		{
			if(!isset($arr_keys_used[$key]))
			{
				$query_join .= " INNER JOIN ".$wpdb->postmeta." AS table_".$key." ON ".$wpdb->posts.".ID = table_".$key.".post_id";

				$arr_keys_used[$key] = $key;
			}

			/*if($data['is_trusted'])
			{
				$query_where .= ($query_where != '' ? " AND " : "")."table_".$key.".meta_key = '".$key."' AND table_".$key.".meta_value = '".$value."'";
			}

			else
			{*/
				$query_join .= ($query_join != '' ? " AND " : "")."table_".$key.".meta_key = '".esc_sql($key)."' AND table_".$key.".meta_value = '".esc_sql($value)."'";
				//$query_where .= ($query_where != '' ? " AND " : "")."table_".$key.".meta_key = '".esc_sql($key)."' AND table_".$key.".meta_value = '".esc_sql($value)."'";
			//}
		}

		unset($arr_keys_used);
	}

	$result = $wpdb->get_results($wpdb->prepare("SELECT ID, post_title, post_status FROM ".$wpdb->posts.$query_join." WHERE post_type = %s".($query_where != '' ? " AND ".$query_where : "").($data['group_by'] != '' ? " GROUP BY ".$data['group_by'] : "")." ORDER BY ".$data['order_by']." ".$data['order'].($data['limit'] > 0 ? " LIMIT 0, ".$data['limit'] : ""), $data['post_type']));
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
			$post_title = "";

			if($data['post_type'] == 'attachment')
			{
				$post_title = get_post_meta($post_id, '_wp_attachment_image_alt', true);

				if($post_title == '')
				{
					$post_title = get_post_meta($post_id, '_wp_attached_file', true);
				}
			}

			if($post_title == '')
			{
				$post_title = $r->post_title;
			}

			if($post_title == '')
			{
				$post_title = "(".__("no title", 'lang_base').")";
			}

			if($data['post_status'] == '' && $r->post_status == 'draft')
			{
				$post_title .= " (".__("Draft", 'lang_base').")";
			}

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

function month_name($data, $ucfirst = 1)
{
	global $obj_base;

	if(!is_array($data))
	{
		$data = array(
			'number' => $data,
		);
	}

	if(!isset($data['ucfirst'])){	$data['ucfirst'] = $ucfirst;}
	if(!isset($data['short'])){		$data['short'] = false;}

	if($data['number'] < 1)
	{
		$data['number'] = 1;
	}

	if($data['short'])
	{
		$array = array(__("Jan", 'lang_base'), __("Feb", 'lang_base'), __("Mar", 'lang_base'), __("Apr", 'lang_base'), __("May", 'lang_base'), __("Jun", 'lang_base'), __("Jul", 'lang_base'), __("Aug", 'lang_base'), __("Sep", 'lang_base'), __("Oct", 'lang_base'), __("Nov", 'lang_base'), __("Dec", 'lang_base'));
	}

	else
	{
		$array = array(__("January", 'lang_base'), __("February", 'lang_base'), __("March", 'lang_base'), __("April", 'lang_base'), __("May", 'lang_base'), __("June", 'lang_base'), __("July", 'lang_base'), __("August", 'lang_base'), __("September", 'lang_base'), __("October", 'lang_base'), __("November", 'lang_base'), __("December", 'lang_base'));
	}

	$out = $array[$data['number'] - 1];

	if($data['ucfirst'] == 0)
	{
		$out = strtolower($out);
	}

	return $out;
}

function day_name($data, $ucfirst = 1)
{
	global $obj_base;

	if(!is_array($data))
	{
		$data = array(
			'number' => $data,
		);
	}

	if(!isset($data['ucfirst'])){	$data['ucfirst'] = $ucfirst;}
	if(!isset($data['short'])){		$data['short'] = false;}

	if($data['short'])
	{
		$array = array(__("Sun", 'lang_base'), __("Mon", 'lang_base'), __("Tue", 'lang_base'), __("Wed", 'lang_base'), __("Thu", 'lang_base'), __("Fri", 'lang_base'), __("Sat", 'lang_base'));
	}

	else
	{
		$array = array(__("Sunday", 'lang_base'), __("Monday", 'lang_base'), __("Tuesday", 'lang_base'), __("Wednesday", 'lang_base'), __("Thursday", 'lang_base'), __("Friday", 'lang_base'), __("Saturday", 'lang_base'));
	}

	$out = $array[$data['number']];

	if($data['ucfirst'] == 0)
	{
		$out = strtolower($out);
	}

	return $out;
}