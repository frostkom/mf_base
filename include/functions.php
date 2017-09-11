<?php

function extend_tiny_base($init)
{
	$init['setup'] = "function(ed)
	{
		ed.onBeforeSetContent.add(function(ed, event)
		{
			if(event.content.indexOf('[mf_custom_list id=') !== -1)
			{
				event.content = event.content.replace(/\[mf_custom_list id\=(.*?)\]/g, function(match, shortcode_id)
				{
					return '<div class=\"mf_shortcode_placeholder\" data-mce-shortcode=\"mf_custom_list\" data-mce-id=\"' + shortcode_id + '\" data-mce-url=\"".admin_url("post.php?action=edit&post=")."' + shortcode_id + '\" data-mce-resize=\"false\" data-mce-placeholder=\"1\"><i class=\"fa fa-lg fa-envelope-o\"></i> ".__("Custom List", 'lang_base')."</div>';
				});
			}

			if(event.content.indexOf('[mf_form id=') !== -1)
			{
				event.content = event.content.replace(/\[mf_form id\=(.*?)\]/g, function(match, shortcode_id)
				{
					return '<div class=\"mf_shortcode_placeholder\" data-mce-shortcode=\"mf_form\" data-mce-id=\"' + shortcode_id + '\" data-mce-url=\"".admin_url("post.php?action=edit&post=")."' + shortcode_id + '\" data-mce-resize=\"false\" data-mce-placeholder=\"1\"><i class=\"fa fa-lg fa-envelope-o\"></i> ".__("Form", 'lang_base')."</div>';
				});
			}
		});

		jQuery(document).on('click', '.mf_shortcode_placeholder', function()
		{
			console.log('Clicked');
		});

		ed.onPostProcess.add(function(ed, event)
		{
			if(event.get)
			{
				event.content = event.content.replace(/<div class=\"mf_shortcode_placeholder\" data-mce-shortcode=\"(.*?)\" data-mce-id=\"(.*?)\".*?>.*?<\/div>/g, function(tag, shortcode, shortcode_id)
				{
					var match,
						string;

					if(tag.indexOf('data-mce-id=') !== -1)
					{
						string = '[' + shortcode + ' id=' + shortcode_id + ']';
					}

					return string || tag;
				});
			}
		});
	}";

	return $init;
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
		$out = str_replace(array("http://", "https://"), "", get_site_url());
	}

	if($data['trim'] != '')
	{
		$out = trim($out, $data['trim']);
	}

	return $out;
}

function get_plugin_version($file)
{
	if(function_exists('get_plugin_data'))
	{
		$plugin_dir = plugin_dir_path($file)."index.php";

		$plugin_dir = str_replace("include/", "", $plugin_dir);

		$arr_plugin_data = get_plugin_data($plugin_dir);

		return $arr_plugin_data['Version'];
	}

	else
	{
		return false;
	}
}

function get_toggler_container($data)
{
	if(!isset($data['open'])){						$data['open'] = false;}
	if(!isset($data['rel']) || $data['rel'] == ''){	$data['rel'] = mt_rand(0, 1000);}
	if(!isset($data['icon_first'])){				$data['icon_first'] = true;}
	if(!isset($data['icon'])){						$data['icon'] = "fa-caret-right";}

	switch($data['type'])
	{
		case 'start':
			$icon = "<i class='fa fa-lg ".($data['open'] ? "fa-caret-down" : $data['icon'])."'></i>";
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
			return $user_data->display_name;
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

function after_title_base()
{
	global $post, $wp_meta_boxes;

	do_meta_boxes(get_current_screen(), 'after_title', $post);

	unset($wp_meta_boxes[get_post_type($post)]['after_title']);
}

if(!function_exists('get_post_title'))
{
	function get_post_title($post)
	{
		return get_the_title($post);
	}
}

function phpmailer_init_base($phpmailer)
{
	if($phpmailer->ContentType == 'text/html')
	{
		$phpmailer->AltBody = strip_tags($phpmailer->Body);
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
	global $error_text;

	if(!isset($data['headers'])){		$data['headers'] = "From: ".get_bloginfo('name')." <".get_bloginfo('admin_email').">\r\n";}
	if(!isset($data['attachment'])){	$data['attachment'] = array();}

	if($data['to'] == '')
	{
		$error_text = sprintf(__("The message had no recepient so '%s' could not be sent", 'lang_base'), $data['subject']);

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

		return wp_mail($data['to'], $data['subject'], $data['content'], $data['headers'], $data['attachment']);
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

function check_notifications()
{
	$arr_notifications = apply_filters('get_user_notifications', array());

	$result = array(
		'success' => true,
		'notifications' => $arr_notifications,
	);

	echo json_encode($result);
	die();
}

function add_shortcode_button_base($button)
{
	global $pagenow;

	$out = "";

	if(in_array($pagenow, array('post.php', 'page.php', 'post-new.php', 'post-edit.php')))
	{
		$count_shortcode_button = 0;
		$count_shortcode_button = apply_filters('count_shortcode_button', $count_shortcode_button);

		if($count_shortcode_button > 0)
		{
			$out = "<a href='#TB_inline?width=640&inlineId=mf_shortcode_container' class='thickbox button'>
				<span class='dashicons dashicons-plus-alt' style='vertical-align: text-top;'></span> "
				.__("Add Content", 'lang_base')
			."</a>";
		}
	}

	return $button.$out;
}

function add_shortcode_display_base()
{
	global $pagenow;

	if(in_array($pagenow, array('post.php', 'page.php', 'post-new.php', 'post-edit.php')))
	{
		mf_enqueue_script('script_base_shortcode', plugin_dir_url(__FILE__)."script_shortcode.js", get_plugin_version(__FILE__));

		echo "<div id='mf_shortcode_container' class='hide'>
			<div class='mf_form mf_shortcode_wrapper'>"
				.apply_filters('get_shortcode_output', '')
				.show_button(array('text' => __("Insert", 'lang_base')))
				.show_button(array('text' => __("Cancel", 'lang_base'), 'class' => "button-secondary"))
			."</div>
		</div>";
	}
}

function meta_page_content()
{
	global $wpdb;

	$out = "";

	$post_id = filter_input(INPUT_GET, 'post', FILTER_SANITIZE_NUMBER_INT);

	if($post_id > 0)
	{
		list($post_id, $content_list) = apply_filters('get_shortcode_list', array($post_id, ''));

		if($content_list != '')
		{
			$out .= "<ul class='meta_list'>"
				.$content_list
			."</ul>";
		}
	}

	return $out;
}

function meta_boxes_base($meta_boxes)
{
	$meta_prefix = "mf_base_";

	$meta_boxes[] = array(
		'id' => $meta_prefix.'content',
		'title' => __("Added Content", 'lang_base'),
		'post_types' => array('page'),
		//'context' => 'side',
		'priority' => 'low',
		'fields' => array(
			array(
				'id' => $meta_prefix.'content',
				'type' => 'custom_html',
				'callback' => 'meta_page_content',
			),
		)
	);

	return $meta_boxes;
}

function meta_boxes_script_base()
{
	mf_enqueue_script('script_base_meta', plugin_dir_url(__FILE__)."script_meta.js", get_plugin_version(__FILE__));
}

function replace_option($data)
{
	if(get_option($data['old']) != '')
	{
		update_option($data['new'], get_option($data['old']));

		mf_uninstall_plugin(array(
			'options' => array($data['old']),
		));
	}
}

function mf_uninstall_plugin($data)
{
	global $wpdb;

	if(!isset($data['uploads'])){			$data['uploads'] = "";}
	if(!isset($data['options'])){			$data['options'] = array();}
	if(!isset($data['post_types'])){		$data['post_types'] = array();}
	if(!isset($data['tables'])){			$data['tables'] = array();}

	if($data['uploads'] != '')
	{
		list($upload_path, $upload_url) = get_uploads_folder($data['uploads']);

		if($upload_path != '')
		{
			get_file_info(array('path' => $upload_path, 'callback' => "delete_files", 'time_limit' => 0));

			rmdir($upload_path);
		}
	}

	foreach($data['options'] as $option)
	{
		delete_option($option);
		delete_site_option($option);
	}

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

	foreach($data['tables'] as $table)
	{
		$wpdb->get_results($wpdb->prepare("SHOW TABLES LIKE %s", $wpdb->prefix.$table));

		if($wpdb->num_rows > 0)
		{
			$wpdb->query("TRUNCATE TABLE ".$wpdb->prefix.$table);
			$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix.$table);

			$wpdb->get_results($wpdb->prepare("SHOW TABLES LIKE %s", $wpdb->prefix.$table));

			if($wpdb->num_rows > 0)
			{
				$result = $wpdb->get_results("SELECT 1 FROM ".$wpdb->prefix.$table." LIMIT 0, 1");

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

function get_setting_key($function_name)
{
	return str_replace("_callback", "", $function_name);
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
		$out .= wp_get_attachment_image($data['id'], 'full');
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
	$time_file = @filemtime($data['file']);

	if($data['time_limit'] == 0 || ($time_now - $time_file >= $data['time_limit']))
	{
		@unlink($data['file']);
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

function get_uploads_folder($subfolder = "", $force_main_uploads = false)
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

		if(!is_dir($upload_path))
		{
			if(!mkdir($upload_path, 0755, true))
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

		/*if($rows > 0)
		{
			do_log("Trashed ".$rows." posts in ".$child_table);
		}*/
	}
}

function init_base()
{
	define('DEFAULT_DATE', "1982-08-04 23:15:00");

	$is_super_admin = $is_admin = $is_editor = $is_author = false;

	if(current_user_can('update_core'))
	{
		$is_super_admin = $is_admin = $is_editor = $is_author = true;
	}

	else if(current_user_can('manage_options'))
	{
		$is_admin = $is_editor = $is_author = true;
	}

	else if(current_user_can('edit_pages'))
	{
		$is_editor = $is_author = true;
	}

	else if(current_user_can('upload_files'))
	{
		$is_author = true;
	}

	define('IS_SUPER_ADMIN', $is_super_admin);
	define('IS_ADMIN', $is_admin);
	define('IS_EDITOR', $is_editor);
	define('IS_AUTHOR', $is_author);

	$timezone_string = get_option('timezone_string');

	if($timezone_string != '')
	{
		date_default_timezone_set($timezone_string);
	}

	reschedule_base();

	$plugin_include_url = plugin_dir_url(__FILE__);
	$plugin_version = get_plugin_version(__FILE__);

	mf_enqueue_style('font-awesome', $plugin_include_url."font-awesome.php", $plugin_version);
	mf_enqueue_style('style_base', $plugin_include_url."style.css", $plugin_version);
	mf_enqueue_script('script_base', $plugin_include_url."script.js", array('confirm_question' => __("Are you sure?", 'lang_base'), 'external_links' => get_option('setting_base_external_links', 'yes')), $plugin_version);
}

function get_file_icon($file)
{
	$suffix = get_file_suffix($file);

	switch($suffix)
	{
		default:														$class = "fa-file-o";				break;

		case 'pdf':														$class = "fa-file-pdf-o";			break;
		case 'mp3': case 'ogg':											$class = "fa-file-audio-o";			break;
		case 'xls': case 'xlsx':										$class = "fa-file-excel-o";			break;
		case 'css':														$class = "fa-file-code-o";			break;
		case 'jpg': case 'jpeg': case 'png': case 'gif': case 'tif':	$class = "fa-file-image-o";			break;
		case 'ppt': case 'pptx':										$class = "fa-file-powerpoint-o";	break;
		case 'wmv': case 'avi':	case 'mpg':								$class = "fa-file-video-o";			break;
		case 'doc': case 'docx':										$class = "fa-file-word-o";			break;
		case 'zip': case 'tar':											$class = "fa-file-zip-o";			break;
		case 'txt':														$class = "fa-file-text-o";			break;
	}

	return "<i class='fa fa-lg ".$class."'></i>";
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
			.input_hidden(array('name' => $data['name'], 'value' => $data['value'], 'allow_empty' => true, 'xtra' => "class='mf_media_urls'"))
			//."<textarea name='".$data['name']."' class='mf_media_urls'>".$data['value']."</textarea>"
		."</div>";
	}

	return $out;
}

function get_file_button($data)
{
	$plugin_include_url = plugin_dir_url(__FILE__);
	$plugin_version = get_plugin_version(__FILE__);

	wp_enqueue_style('thickbox');
	mf_enqueue_style('style_media_button', $plugin_include_url."style_media_button.css", $plugin_version);

	$add_file_text = __("Add Image", 'lang_base');
	$change_file_text = __("Change Image", 'lang_base');
	$insert_file_text = __("Insert Image", 'lang_base');

	wp_enqueue_script('media-upload');
	wp_enqueue_script('thickbox');
	mf_enqueue_script('script_media_button', $plugin_include_url."script_media_button.js", array(
		'multiple' => false,
		'no_attachment_link' => __("The Media Library did not return a link to the file you added. Please try again and make sure that 'Link To' is set to 'Media File'", 'lang_base'),
		'unknown_title' => __("Unknown title", 'lang_base'),
		'adminurl' => get_admin_url(), 'add_file_text' => $add_file_text, 'change_file_text' => $change_file_text, 'insert_file_text' => $insert_file_text,
	), $plugin_version);

	return "<div class='mf_image_button'>
		<div".($data['value'] != '' ? "" : " class='hide'").">
			<img src='".$data['value']."'>
			<a href='#' rel='confirm'><i class='fa fa-lg fa-trash'></i></a>
		</div>
		<div>"
			.show_button(array('text' => ($data['value'] != '' ? $change_file_text : $add_file_text), 'class' => "button"))
			.input_hidden(array('name' => $data['name'], 'value' => $data['value']))
		."</div>
		<div class='mf_file_raw'></div>
	</div>";
}

function get_attachment_to_send($string)
{
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

	return array($arr_files, $arr_ids);
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

function mf_get_post_content($id, $field = 'post_content')
{
	global $wpdb;

	return $wpdb->get_var($wpdb->prepare("SELECT ".$field." FROM ".$wpdb->posts." WHERE ID = '%d'", $id));
}

function plugin_actions_base($actions, $plugin_file)
{
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
	if(function_exists('is_plugin_active') && !is_plugin_active($required_path))
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

function show_settings_fields($data)
{
	if(!isset($data['area'])){		$data['area'] = "";}
	if(!isset($data['settings'])){	$data['settings'] = array();}
	if(!isset($data['callback'])){	$data['callback'] = '';}

	foreach($data['settings'] as $handle => $text)
	{
		add_settings_field($handle, $text, $handle."_callback", BASE_OPTIONS_PAGE, $data['area']);

		register_setting(BASE_OPTIONS_PAGE, $handle, $data['callback']);
	}
}

function settings_header($id, $title)
{
	return "<div id='".$id."'><a href='#".$id."'><h3>".$title."</h3></a></div>";
}

function settings_base()
{
	global $wpdb;

	$plugin_include_url = plugin_dir_url(__FILE__);
	$plugin_version = get_plugin_version(__FILE__);

	//add_editor_style($plugin_include_url."font-awesome.php");
	//add_editor_style($plugin_include_url."style_editor.css");

	mf_enqueue_style('style_base_wp', $plugin_include_url."style_wp.css", $plugin_version);

	wp_enqueue_script('jquery-ui-autocomplete');
	mf_enqueue_script('script_base_wp', $plugin_include_url."script_wp.js", array('plugins_url' => plugins_url(), 'ajax_url' => admin_url('admin-ajax.php')), $plugin_version);

	define('BASE_OPTIONS_PAGE', "settings_mf_base");

	$options_area = __FUNCTION__;

	add_settings_section($options_area, "",	$options_area."_callback", BASE_OPTIONS_PAGE);

	$arr_settings = array(
		'setting_base_info' => __("Versions", 'lang_base'),
		'setting_base_cron' => __("Scheduled to run", 'lang_base'),
		'setting_base_external_links' => __("Open external links in new window", 'lang_base'),
		//'setting_all_options' => __("All options", 'lang_base'),
	);

	if(IS_SUPER_ADMIN)
	{
		$arr_settings['setting_base_recommend'] = __("Recommendations", 'lang_base');
	}

	show_settings_fields(array('area' => $options_area, 'settings' => $arr_settings));
}

function settings_base_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);

	echo settings_header($setting_key, __("Common", 'lang_base'));
}

function setting_base_info_callback()
{
	global $wpdb;

	$php_version = explode("-", phpversion());
	$php_version = $php_version[0];

	$mysql_version = '';

	if(function_exists('mysql_get_server_info'))
	{
		$mysql_version = explode("-", @mysql_get_server_info());
		$mysql_version = $mysql_version[0];
	}

	if($mysql_version == '')
	{
		$mysql_version = int2point(mysqli_get_client_version());
	}

	$php_required = "5.2.4";
	$mysql_required = "5.0";

	$has_required_php_version = point2int($php_version) > point2int($php_required);
	$has_required_mysql_version = point2int($mysql_version) > point2int($mysql_required);

	echo "<p><i class='fa ".($has_required_php_version ? "fa-check green" : "fa-close red")."'></i> ".__("PHP", 'lang_base').": ".$php_version."</p>
	<p><i class='fa ".($has_required_mysql_version ? "fa-check green" : "fa-close red")."'></i> ".__("MySQL", 'lang_base').": ".$mysql_version."</p>";

	if(!($has_required_php_version && $has_required_mysql_version))
	{
		echo "<p><a href='//wordpress.org/about/requirements/'>".__("Requirements", 'lang_base')."</a></p>";
	}

	$intDBDate = strtotime($wpdb->get_var("SELECT LOCALTIME()"));
	$intFileDate = strtotime(date("Y-m-d H:i:s"));
	$intDateDifference = abs($intDBDate - $intFileDate);

	if($intDateDifference > 60)
	{
		echo "<br>
		<p><i class='fa ".($intDateDifference > 60 ? "fa-close red" : "fa-check green")."'></i> Time Difference: ".format_date(date("Y-m-d H:i:s", $intFileDate))." (".__("PHP", 'lang_base')."), ".format_date(date("Y-m-d H:i:s", $intDBDate))." (".__("MySQL", 'lang_base').")</p>";
	}
}

function get_next_cron()
{
	return format_date(date("Y-m-d H:i:s", wp_next_scheduled('cron_base')));
}

function reschedule_base($option = '')
{
	if($option == ''){	$option = get_option('setting_base_cron', 'every_ten_minutes');}

	$schedule = wp_get_schedule('cron_base');

	if($schedule != $option)
	{
		deactivate_base();
		activate_base();
	}
}

function setting_base_cron_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key, 'every_ten_minutes');

	reschedule_base($option);

	$arr_schedules = wp_get_schedules();

	$arr_data = array();

	foreach($arr_schedules as $key => $value)
	{
		$arr_data[$key] = $value['display'];
	}

	if(defined('DISABLE_WP_CRON') && DISABLE_WP_CRON == true)
	{
		$cron_url = get_site_url()."/wp-cron.php?doing_wp_cron";

		echo "<a href='".$cron_url."'>".__("Run schedule manually", 'lang_base')."</a>";
	}

	else
	{
		$select_suffix = sprintf(__("Next scheduled %s", 'lang_base'), get_next_cron());

		if($option == "every_ten_seconds")
		{
			$select_suffix = sprintf(__("Make sure that %s is added to %s", 'lang_base'), "define('DISABLE_WP_CRON', true);", "wp-config.php");
		}

		echo show_select(array('data' => $arr_data, 'name' => 'setting_base_cron', 'value' => $option, 'suffix' => $select_suffix));
	}
}

function setting_base_external_links_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key, 'yes');

	echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option));
}

function setting_base_recommend_callback()
{
	$arr_recommendations = array(
		array("Admin Branding", 'admin-branding/admin-branding.php', __("to brand the login and admin area", 'lang_base')),
		//array("Admin Menu Tree Page View", 'admin-menu-tree-page-view/index.php'),
		array("Advanced Cron Manager", 'advanced-cron-manager/advanced-cron-manager.php', __("to debug Cron", 'lang_base')),
		array("ARI Adminer", 'ari-adminer/ari-adminer.php', __("to get a graphical interface to the database", 'lang_base')),
		array("BackWPup", 'backwpup/backwpup.php', __("to backup all files and database to an external source", 'lang_base')),
		array("Black Studio TinyMCE Widget", 'black-studio-tinymce-widget/black-studio-tinymce-widget.php', __("to get a WYSIWYG widget editor", 'lang_base')),
		array("E-mail Log", 'email-log/email-log.php', __("to log all outgoing e-mails", 'lang_base')),
		array("Enable Media Replace", 'enable-media-replace/enable-media-replace.php', __("to be able to replace existing files by uploading a replacement", 'lang_base')),
		array("Favicon by RealFaviconGenerator", 'favicon-by-realfavicongenerator/favicon-by-realfavicongenerator.php', __("to add all the favicons needed", 'lang_base')),
		array("Google XML Sitemaps", 'google-sitemap-generator/sitemap.php', __("to add a Sitemap XML to your site", 'lang_base')),
		array("P3 (Plugin Performance Profiler)", 'p3-profiler/p3-profiler.php', __("to scan for potential time thiefs on your site", 'lang_base')),
		array("Query Monitor", 'query-monitor/query-monitor.php', __("to monitor database queries, hooks, conditionals and more", 'lang_base')),
		array("Quick Page/Post Redirect Plugin", 'quick-pagepost-redirect-plugin/page_post_redirect_plugin.php', __("to redirect pages to internal or external URLs", 'lang_base')),
		array("Simple Page Ordering", 'simple-page-ordering/simple-page-ordering.php', __("to reorder posts with drag & drop", 'lang_base')),
		//array("Snitch", 'snitch/snitch.php', __("to monitor network traffic", 'lang_base')),
		array("TablePress", 'tablepress/tablepress.php', __("to be able to add tables to posts", 'lang_base')),
		//array("User Role Editor", 'user-role-editor/user-role-editor.php', __("to be able to edit roles", 'lang_base')),
		array("WP Video Lightbox", 'wp-video-lightbox/wp-video-lightbox.php', __("to be able to view video clips in modals", 'lang_base')),
	);

	if(!(is_plugin_active('tiny-compress-images/tiny-compress-images.php') || is_plugin_active('optimus/optimus.php') || is_plugin_active('wp-smushit/wp-smush.php')))
	{
		$arr_recommendations[] = array("Compress JPEG & PNG images", 'tiny-compress-images/tiny-compress-images.php', __("to losslessly compress all uploaded images (Max 500 for free / month)", 'lang_base'));
		$arr_recommendations[] = array("Optimus", 'optimus/optimus.php', __("to losslessly compress all uploaded images (Max 100kB/file for free)", 'lang_base'));
		$arr_recommendations[] = array("Smush Image Compression and Optimization", 'wp-smushit/wp-smush.php', __("to losslessly compress all uploaded images", 'lang_base'));
	}

	/*if(is_multisite())
	{
		$arr_recommendations[] = array("WP Super Cache", 'wp-super-cache/wp-cache.php', __("to increase the speed of the public site", 'lang_base'));
	}

	else
	{
		$arr_recommendations[] = array("WP Fastest Cache", 'wp-fastest-cache/wpFastestCache.php', __("to increase the speed of the public site", 'lang_base'));
	}*/

	foreach($arr_recommendations as $value)
	{
		$name = $value[0];
		$path = $value[1];
		$text = isset($value[2]) ? $value[2] : "";

		new recommend_plugin(array('path' => $path, 'name' => $name, 'text' => $text, 'show_notice' => false));
	}
}

/*function setting_all_options_callback()
{
	echo "<a href='".admin_url("options.php")."'>".__("Edit", 'lang_base')."</a>";
}*/

function mf_enqueue_style($handle, $file = "", $dep = array(), $version = false)
{
	if(!is_array($dep))
	{
		$version = $dep;
		$dep = array();
	}

	do_action('mf_enqueue_style', array('handle' => $handle, 'file' => $file, 'version' => $version));

	wp_enqueue_style($handle, $file, $dep, $version);
}

function mf_enqueue_script($handle, $file = "", $translation = array(), $version = false)
{
	if(!is_array($translation))
	{
		$version = $translation;
		$translation = array();
	}

	do_action('mf_enqueue_script', array('handle' => $handle, 'file' => $file, 'translation' => $translation, 'version' => $version));

	if(count($translation) > 0)
	{
		wp_register_script($handle, $file, array('jquery'), $version);
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
		if(function_exists('is_plugin_active') && is_plugin_active("mf_users/index.php") && function_exists('hide_roles'))
		{
			hide_roles();
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
	if(!isset($data['choose_here_text'])){	$data['choose_here_text'] = __("Choose here", 'lang_base');}
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
		$data['array'][''] = "-- ".__("Choose here", 'lang_base')." --";
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

function get_post_types_for_select($data = array())
{
	if(!isset($data['include'])){	$data['include'] = array('ids', 'types', 'special');}
	if(!isset($data['add_is'])){	$data['add_is'] = true;}

	$opt_groups = is_array($data['include']) && count($data['include']) > 1;

	$arr_data = array();

	if(in_array('ids', $data['include']))
	{
		$arr_pages = array();
		get_post_children(array(), $arr_pages);

		if(count($arr_pages) > 0)
		{
			if($opt_groups == true)
			{
				$arr_data["opt_start_pages"] = __("Pages", 'lang_base');
			}

				foreach($arr_pages as $post_id => $post_title)
				{
					if($data['add_is'] == true)
					{
						$arr_data["is_page(".$post_id.")"] = $post_title;
					}

					else
					{
						$arr_data[$post_id] = $post_title;
					}
				}

			if($opt_groups == true)
			{
				$arr_data["opt_end_pages"] = "";
			}
		}
	}

	if($opt_groups == true)
	{
		$arr_data["opt_start_post_types"] = __("Post Types", 'lang_base');
	}

		if(in_array('types', $data['include']))
		{
			foreach(get_post_types(array('public' => true), 'objects') as $post_type)
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
		$arr_data["opt_end_post_types"] = "";
	}

	if(in_array('special', $data['include']))
	{
		$arr_data["is_404()"] = __("404", 'lang_base');
		//$arr_data["is_archive()"] = __("Archive", 'lang_base');

		$arr_categories = get_categories(array(
			//'order' => 'ASC',
			//'orderby' => 'id',
			'hierarchical' => 0,
			'hide_empty' => 1,
		));

		if(count($arr_categories) > 0)
		{
			$arr_data["is_category()"] = __("Category", 'lang_base');

			if($opt_groups == true)
			{
				$arr_data["opt_start_categories"] = __("Categories", 'lang_base');
			}

				foreach($arr_categories as $category)
				{
					$arr_data['is_category('.$category->cat_ID.')'] = $category->name;
				}

			if($opt_groups == true)
			{
				$arr_data["opt_end_categories"] = "";
			}
		}

		//$arr_data["is_front_page()"] = __("Front Page", 'lang_base');
		$arr_data["is_home()"] = __("Home", 'lang_base');
		//$arr_data["is_page()"] = __("Page", 'lang_base');
		$arr_data["is_search()"] = __("Search", 'lang_base');
		//$arr_data["is_single()"] = __("Single", 'lang_base');
		//$arr_data["is_sticky()"] = __("Sticky", 'lang_base');
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
		$arr_exclude = array();

		foreach(get_post_types(array('public' => false), 'objects') as $post_type)
		{
			$arr_exclude[] = $post_type->name;
		}

		if(count($arr_exclude) > 0)
		{
			$query_where .= " AND post_type NOT IN('".implode("','", $arr_exclude)."')";
		}
	}

	$result = $wpdb->get_results($wpdb->prepare("SELECT ID, post_type, post_title FROM ".$wpdb->posts." WHERE post_status = %s AND post_parent = '%d'".$query_where." ORDER BY post_type ASC, ".esc_sql($data['order']), $data['post_status'], $data['post_parent']));

	$post_type_temp = $data['post_type'];
	$opt_start_open = false;

	$arr_data = array();

	if($data['add_choose_here'] == true)
	{
		$arr_data[''] = "-- ".__("Choose here", 'lang_base')." --";
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

function get_sidebars_for_select()
{
	$arr_data = array();
	$arr_data[''] = "-- ".__("Choose here", 'lang_base')." --";

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
			$value = "http:".$value;
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
			$out = $temp; //Får aldrig köras addslashes() på detta
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
	if(!isset($data['title'])){			$data['title'] = "";}
	if(!isset($data['xtra'])){			$data['xtra'] = "";}
	if(!isset($data['xtra_class'])){	$data['xtra_class'] = "";}
	if(!isset($data['datalist'])){		$data['datalist'] = array();}
	if(!isset($data['suffix'])){		$data['suffix'] = "";}
	if(!isset($data['description'])){	$data['description'] = "";}

	if($data['type'] == "date")
	{
		mf_enqueue_style('jquery-ui-css', '//ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css', '1.8.2');
		wp_enqueue_script('jquery-ui-datepicker');
		mf_enqueue_script('script_base_datepicker', plugin_dir_url(__FILE__)."script_datepicker.js", get_plugin_version(__FILE__));

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

	if($data['suffix'] != '')
	{
		$data['xtra_class'] .= ($data['xtra_class'] != '' ? " " : "")."has_suffix";
	}

	$out = "<div class='form_textfield".($data['xtra_class'] != '' ? " ".$data['xtra_class'] : "")."'>";

		if($data['text'] != '')
		{
			$out .= "<label for='".$data['name']."'>".$data['text']."</label>";
		}

		$out .= "<input type='".$data['type']."'".($data['name'] != '' ? " name='".$data['name']."'" : "")." value=\"".$data['value']."\"".$data['xtra'].">";

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

	$out .= "</div>";

	return $out;
}
#################

######################
function show_password_field($data)
{
	$out = "";

	if(!isset($data['name'])){			$data['name'] = "";}
	if(!isset($data['text'])){			$data['text'] = "";}
	if(!isset($data['value'])){			$data['value'] = "";}
	if(!isset($data['maxlength'])){		$data['maxlength'] = "";}
	if(!isset($data['placeholder'])){	$data['placeholder'] = "";}
	if(!isset($data['xtra'])){			$data['xtra'] = "";}

	if($data['maxlength'] != '')
	{
		$data['xtra'] .= " maxlength='".$data['maxlength']."'";
	}

	if($data['placeholder'] != '')
	{
		$data['xtra'] .= " placeholder='".$data['placeholder']."&hellip;'";
	}

	$out .= "<div class='form_password'>";

		if($data['text'] != '')
		{
			$out .= "<label for='".$data['name']."'>".$data['text']."</label>";
		}

		$out .= "<input type='password' name='".$data['name']."' value='".$data['value']."' id='".$data['name']."'".$data['xtra'].">
	</div>";

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
			$out .= show_wp_editor(array('name' => $data['name'], 'value' => stripslashes($data['value']), 'textarea_rows' => 5));
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

	return mf_editor($data['value'], $data['name'], $data);
}

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
	$out = "";

	if(!isset($data['data'])){			$data['data'] = array();}
	if(!isset($data['name'])){			$data['name'] = "";}
	if(!isset($data['text'])){			$data['text'] = "";}
	if(!isset($data['compare'])){		$data['compare'] = "";} //To be deprecated in the future
	if(!isset($data['value'])){			$data['value'] = $data['compare'];}
	if(!isset($data['xtra'])){			$data['xtra'] = "";}
	if(!isset($data['minsize'])){		$data['minsize'] = 2;}
	if(!isset($data['maxsize'])){		$data['maxsize'] = 10;}
	if(!isset($data['required'])){		$data['required'] = false;}
	if(!isset($data['class'])){			$data['class'] = "";}
	if(!isset($data['suffix'])){		$data['suffix'] = "";}
	if(!isset($data['description'])){	$data['description'] = "";}

	$count_temp = count($data['data']);

	if($count_temp > 0)
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
			$data['xtra'] .= ($data['xtra'] != '' ? " " : "")."multiple size='".$size."'";

			$container_class = "form_select form_select_multiple";
		}

		else
		{
			$container_class = "form_select";
		}

		if($data['required'])
		{
			$data['xtra'] .= ($data['xtra'] != '' ? " " : "")."required";
		}

		if($count_temp == 1 && $data['required'] && $data['text'] != '')
		{
			foreach($data['data'] as $key => $option)
			{
				if($key != '')
				{
					$out = input_hidden(array('name' => $data['name'], 'value' => $key));

					break;
				}
			}
		}

		else
		{
			if($data['suffix'] != '')
			{
				$data['class'] .= ($data['class'] != '' ? " " : "")."has_suffix";
			}

			$out = "<div class='".$container_class.($data['class'] != '' ? " ".$data['class'] : "")."'>";

				if($data['text'] != '')
				{
					$out .= "<label for='".$data['name']."'>".$data['text']."</label>";
				}

				$out .= "<select".($data['name'] != '' ? " id='".preg_replace("/\[(.*)\]/", "", $data['name'])."' name='".$data['name']."'" : "").($data['xtra'] != '' ? " ".$data['xtra'] : "").">";

					foreach($data['data'] as $key => $option)
					{
						$data_value = $key;
						$data_text = $option;

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

								if(is_array($data['value']) && in_array($data_value, $data['value']) || $data['value'] == $data_value)
								{
									$out .= " selected";
								}

							$out .= ">".$data_text."</option>";
						}
					}

				$out .= "</select>";

				if($data['suffix'] != '')
				{
					$out .= "<span class='description'>".$data['suffix']."</span>";
				}

				if($data['description'] != '')
				{
					$out .= "<p class='description'>".$data['description']."</p>";
				}

			$out .= "</div>";
		}
	}

	return $out;
}
############################

############################
function show_checkboxes($data)
{
	$out = "";

	if(!isset($data['data'])){			$data['data'] = array();}
	if(!isset($data['text'])){			$data['text'] = "";}
	if(!isset($data['value'])){			$data['value'] = "";}
	if(!isset($data['xtra'])){			$data['xtra'] = "";}
	if(!isset($data['required'])){		$data['required'] = false;}
	if(!isset($data['class'])){			$data['class'] = "";}
	if(!isset($data['suffix'])){		$data['suffix'] = "";}
	if(!isset($data['description'])){	$data['description'] = "";}

	$count_temp = count($data['data']);

	if($count_temp > 0)
	{
		$container_class = "form_checkbox_multiple";

		if($data['required'])
		{
			$data['xtra'] .= " required";
		}

		if($count_temp == 1 && $data['required'] && $data['text'] != '')
		{
			foreach($data['data'] as $key => $option)
			{
				if($key != '')
				{
					$out = input_hidden(array('name' => $data['name'], 'value' => $key));

					break;
				}
			}
		}

		else
		{
			if($data['suffix'] != '')
			{
				$data['class'] .= ($data['class'] != '' ? " " : "")."has_suffix";
			}

			$out = "<div class='".$container_class.($data['class'] != '' ? " ".$data['class'] : "")."'>";

				if($data['text'] != '')
				{
					$out .= "<label>".$data['text']."</label>";
				}

				$out .= "<ul>";

					foreach($data['data'] as $key => $option)
					{
						$data_value = $key;
						$data_text = $option;

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
							$compare = (is_array($data['value']) && in_array($data_value, $data['value']) || $data['value'] == $data_value) ? $data_value : -$data_value;

							$out .= show_checkbox(array('name' => $data['name'], 'text' => $data_text, 'value' => $data_value, 'compare' => $compare));
						}
					}

				$out .= "</ul>";

				if($data['suffix'] != '')
				{
					$out .= "<span class='description'>".$data['suffix']."</span>";
				}

				if($data['description'] != '')
				{
					$out .= "<p class='description'>".$data['description']."</p>";
				}

			$out .= "</div>";
		}
	}

	return $out;
}
############################

######################################
function show_checkbox($data)
{
	if(!isset($data['name'])){			$data['name'] = "";}
	if(!isset($data['value'])){			$data['value'] = "";}
	if(!isset($data['text'])){			$data['text'] = "";}
	if(!isset($data['required'])){		$data['required'] = false;}
	if(!isset($data['compare'])){		$data['compare'] = 0;}
	if(!isset($data['xtra'])){			$data['xtra'] = "";}
	if(!isset($data['xtra_class'])){	$data['xtra_class'] = "";}
	if(!isset($data['switch'])){		$data['switch'] = 0;}
	if(!isset($data['suffix'])){		$data['suffix'] = "";}
	if(!isset($data['description'])){	$data['description'] = "";}

	$data['xtra'] .= ($data['value'] != '' && $data['value'] == $data['compare'] ? " checked" : "");

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

	if($data['switch'] == 1)
	{
		$data['xtra_class'] .= ($data['xtra_class'] != '' ? " " : "")."form_switch";
		$data['text'] = "<span><i class='fa fa-lg fa-check-square-o green checked'></i><i class='fa fa-lg fa-square-o unchecked'></i><i class='fa fa-lg fa-spinner fa-spin loading'></i></span>".$data['text'];
	}

	if($data['suffix'] != '')
	{
		$data['xtra_class'] .= ($data['xtra_class'] != '' ? " " : "")."has_suffix";
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
			$out .= "<span class='description'>".$data['suffix']."</span>";
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

#################
function show_submit($data)
{
	return show_button($data);
}

function show_button($data)
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

	if(!isset($data['log'])){	$data['log'] = true;}

	if(isset($data['realpath']) && $data['realpath'] == true)
	{
		$data['file'] = realpath($data['file']);
	}

	if($data['file'] != '')
	{
		if($fh = @fopen($data['file'], $data['mode']))
		{
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
					//$content = substr($arr_header[$i], 0, 12)."&hellip;";
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

	if(!isset($data['post_id'])){			$data['post_id'] = 0;}
	if(!isset($data['post_type'])){			$data['post_type'] = "page";}
	if(!isset($data['post_status'])){		$data['post_status'] = "publish";}
	if(!isset($data['where'])){				$data['where'] = '';}
	if(!isset($data['order_by'])){			$data['order_by'] = "menu_order";}
	if(!isset($data['limit'])){				$data['limit'] = 0;}
	if(!isset($data['count'])){				$data['count'] = false;}

	if(!isset($data['current_id'])){		$data['current_id'] = "";}

	$exclude_post_status = array('auto-draft', 'ignore', 'inherit', 'trash');

	if($data['add_choose_here'] == true)
	{
		$arr_data[''] = "-- ".__("Choose here", 'lang_base')." --";
	}

	if(!isset($data['depth']))
	{
		$data['depth'] = 0;
	}

	else
	{
		$data['depth']++;
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

	$result = $wpdb->get_results($wpdb->prepare("SELECT ID, post_title FROM ".$wpdb->posts." WHERE post_type = %s AND post_parent = '%d'".($data['where'] != '' ? " AND ".$data['where'] : "")." ORDER BY ".$data['order_by']." ASC".($data['limit'] > 0 ? " LIMIT 0, ".$data['limit'] : ""), $data['post_type'], $data['post_id']));

	if($data['count'] == true)
	{
		$out = $wpdb->num_rows;
	}

	else if($wpdb->num_rows > 0)
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