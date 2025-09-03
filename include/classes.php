<?php

class mf_base
{
	var $post_type = 'mf_base';
	var $meta_prefix;
	var $chmod_dir = 0755;
	var $chmod_file = 0644;
	var $memory_limit_base = 200;
	var $upload_max_filesize_base = 20;
	var $post_max_size_base = 20;
	var $memory_limit = "";
	var $upload_max_filesize = "";
	var $post_max_size = "";
	var $memory_limit_current = "";
	var $post_max_size_current = "";
	var $upload_max_filesize_current = "";
	var $data = [];
	var $templates = [];
	var $ftp_size = 0;
	var $ftp_size_folders = [];
	var $template_lost_connection = false;
	var $template_loading = false;
	var $server_type = "";
	var $phpmailer_temp = [];
	var $file_warning;
	var $arr_uploads_ignore_folder;
	var $file_name = "";
	var $arr_post_types = [];
	var $arr_public_posts = [];
	var $github_debug;

	function __construct()
	{
		if(!defined('BASE_OPTIONS_PAGE'))
		{
			define('BASE_OPTIONS_PAGE', "settings_mf_base");
		}

		$this->meta_prefix = $this->post_type.'_';

		$this->memory_limit = (MB_IN_BYTES * $this->memory_limit_base);
		$this->upload_max_filesize = (MB_IN_BYTES * $this->upload_max_filesize_base);
		$this->post_max_size = (MB_IN_BYTES * $this->post_max_size_base);

		$this->memory_limit_current = $this->return_bytes('memory_limit'); //Use WP_MEMORY_LIMIT/WP_MAX_MEMORY_LIMIT instead?
		$this->post_max_size_current = $this->return_bytes('post_max_size');
		$this->upload_max_filesize_current = $this->return_bytes('upload_max_filesize');

		if($this->memory_limit_current < $this->memory_limit)
		{
			ini_set('memory_limit', $this->memory_limit_base."M");
		}

		if($this->upload_max_filesize_current < $this->upload_max_filesize)
		{
			ini_set('upload_max_filesize', $this->upload_max_filesize_base."M");
		}

		if($this->post_max_size_current < $this->post_max_size)
		{
			ini_set('post_max_size', $this->post_max_size_base."M");
		}
	}

	function get_icons_for_select()
	{
		global $obj_font_icons;

		if(!isset($obj_font_icons))
		{
			$obj_font_icons = new mf_font_icons();
		}

		$arr_data = [];
		$arr_data[''] = "-- ".__("Choose Here", 'lang_base')." --";

		foreach($obj_font_icons->get_array(array('allow_optgroup' => false)) as $key => $value)
		{
			$arr_data[$key] = $value;
		}

		return $arr_data;
	}

	function filter_phpmailer_data()
	{
		global $phpmailer;

		$arr_exclude = array('Priority', 'Body', 'AltBody', 'MIMEBody', 'Password', 'boundary', 'Timeout', 'Debugoutput', 'Version', 'CharSet', 'ContentType', 'Encoding', 'WordWrap', 'MessageDate', 'SMTPAutoTLS', 'SMTPDebug', 'UseSendmailOptions', 'Mailer', 'Sendmail', 'Sender', 'DKIM_copyHeaderFields'); //, 'Hostname', 'Host', 'Port'

		$this->phpmailer_temp = [];

		foreach($phpmailer as $key => $value)
		{
			if(is_array($value))
			{
				foreach($value as $key2 => $value2)
				{
					if(!in_array($key2, $arr_exclude) && trim($value2) != '')
					{
						$this->phpmailer_temp[$key][$key2] = $value2;
					}

					else
					{
						$this->phpmailer_temp[$key][$key2] = shorten_text(array('string' => htmlspecialchars($value2), 'limit' => 4));
					}
				}
			}

			else
			{
				if(!in_array($key, $arr_exclude) && trim($value) != '')
				{
					$this->phpmailer_temp[$key] = $value;
				}

				/*else
				{
					$this->phpmailer_temp[$key] = shorten_text(array('string' => htmlspecialchars($value), 'limit' => 4));
				}*/
			}

			$to_temp = $phpmailer->getToAddresses();

			if(isset($to_temp[0][0]))
			{
				$this->phpmailer_temp['to'] = $to_temp[0][0];
			}

			else
			{
				$this->phpmailer_temp['to'] = "";

				if(count($to_temp) > 0)
				{
					do_log(__("I could not get recipient address", 'lang_base').": ".var_export($to_temp, true)." (".$phpmailer->Subject.")");
				}
			}
		}
	}

	function get_post_types_for_metabox($data = [])
	{
		if(!isset($data['public'])){		$data['public'] = true;}

		$arr_data = [];

		$arr_post_types_ignore = apply_filters('get_post_types_for_metabox', array('attachment'));

		foreach(get_post_types($data, 'objects') as $post_type)
		{
			if(!in_array($post_type->name, $arr_post_types_ignore))
			{
				$arr_data[] = $post_type->name;
			}
		}

		return $arr_data;
	}

	function set_html_content_type()
	{
		return 'text/html';
	}

	function HTMLToRGB($hex)
	{
		if($hex[0] == '#')
		{
			$hex = substr($hex, 1);
		}

		if(strlen($hex) == 3)
		{
			$hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
		}

		$r = hexdec($hex[0].$hex[1]);
		$g = hexdec($hex[2].$hex[3]);
		$b = hexdec($hex[4].$hex[5]);

		return $b + ($g << 0x8) + ($r << 0x10);
	}

	function RGBToHSL($RGB)
	{
		$r = 0xFF & ($RGB >> 0x10);
		$g = 0xFF & ($RGB >> 0x8);
		$b = 0xFF & $RGB;

		$r = ((float)$r) / 255.0;
		$g = ((float)$g) / 255.0;
		$b = ((float)$b) / 255.0;

		$maxC = max($r, $g, $b);
		$minC = min($r, $g, $b);

		$l = ($maxC + $minC) / 2.0;

		if($maxC == $minC)
		{
			$s = $h = 0;
		}

		else
		{
			if($l < .5)
			{
				$s = ($maxC - $minC) / ($maxC + $minC);
			}

			else
			{
				$s = ($maxC - $minC) / (2.0 - $maxC - $minC);
			}

			if($r == $maxC)
			{
				$h = ($g - $b) / ($maxC - $minC);
			}

			if($g == $maxC)
			{
				$h = 2.0 + ($b - $r) / ($maxC - $minC);
			}

			if($b == $maxC)
			{
				$h = 4.0 + ($r - $g) / ($maxC - $minC);
			}

			$h = $h / 6.0;
		}

		$h = (int)round(255.0 * $h);
		$s = (int)round(255.0 * $s);
		$l = (int)round(255.0 * $l);

		return (object) array('hue' => $h, 'saturation' => $s, 'lightness' => $l);
	}

	function get_text_color_from_background($color)
	{
		$rgb = $this->HTMLToRGB($color);
		$hsl = $this->RGBToHSL($rgb);

		return ($hsl->lightness > 200 ? "#333" : "#fff");
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

		$new_array = [];
		$sortable_array = [];

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

	function reschedule_base($option = '')
	{
		if($option == ''){	$option = get_option('setting_base_cron', 'every_ten_minutes');}

		$schedule = wp_get_schedule('cron_base');

		$is_run_now = (check_var('action') == 'run_cron_now');
		$is_run_now_v2 = (check_var('action') == 'run_cron_now_v2');

		if($schedule != $option || $is_run_now)
		{
			deactivate_base();
			activate_base();

			if($is_run_now)
			{
				mf_redirect($_SERVER['HTTP_REFERER']);
			}
		}

		else if($is_run_now_v2)
		{
			do_action('cron_base');

			mf_redirect($_SERVER['HTTP_REFERER']);
		}
	}

	function get_toggler_includes()
	{
		$plugin_include_url = plugin_dir_url(__FILE__);

		mf_enqueue_style('style_base_toggler', $plugin_include_url."style_toggler.css");
		mf_enqueue_script('script_storage', $plugin_include_url."jquery.Storage.js");
		mf_enqueue_script('script_base_toggler', $plugin_include_url."script_toggler.js");
	}

	function enqueue_block_editor_assets()
	{
		$plugin_include_url = plugin_dir_url(__FILE__);
		$plugin_version = get_plugin_version(__FILE__);

		wp_register_style('style_base_block_wp', $plugin_include_url."block/style_wp.css", [], $plugin_version);
	}

	function init()
	{
		if($_SERVER['REQUEST_URI'] === '/.well-known/security.txt' || $_SERVER['REQUEST_URI'] === '/security.txt')
		{
			header('Content-Type: text/plain');

			echo "Contact: mailto:security@martinfors.se\r\n"
			."Expires: ".gmdate('Y-m-d\TH:i:s\Z', time() + WEEK_IN_SECONDS);
			exit;
		}

		load_plugin_textdomain('lang_base', false, str_replace("/include", "", dirname(plugin_basename(__FILE__)))."/lang/");

		add_post_type_support('page', 'excerpt');

		define('DEFAULT_DATE', "1982-08-04 23:15:00");
		define('IS_HTTPS', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on'));

		$is_super_admin = $is_admin = $is_editor = $is_author = false;

		if(current_user_can('update_core'))
		{
			$is_super_admin = $is_admin = $is_editor = $is_author = true;

			define('ALLOW_UNFILTERED_UPLOADS', true);
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
		define('IS_ADMINISTRATOR', $is_admin);
		define('IS_EDITOR', $is_editor);
		define('IS_AUTHOR', $is_author);

		if(get_site_option('setting_base_use_timezone') == 'yes')
		{
			$timezone_string = get_option('timezone_string');
 
			if($timezone_string != '')
			{
				date_default_timezone_set($timezone_string);
			}
		}

		$this->reschedule_base();
	}

	function cron_schedules($schedules)
	{
		$schedules['every_two_minutes'] = array('interval' => MINUTE_IN_SECONDS * 2, 'display' => __("Every 2 Minutes", 'lang_base'));
		$schedules['every_ten_minutes'] = array('interval' => MINUTE_IN_SECONDS * 10, 'display' => __("Every 10 Minutes", 'lang_base'));

		$schedules['weekly'] = array('interval' => WEEK_IN_SECONDS, 'display' => __("Weekly", 'lang_base'));
		$schedules['monthly'] = array('interval' => MONTH_IN_SECONDS, 'display' => __("Monthly", 'lang_base'));

		return $schedules;
	}

	function run_cron_start()
	{
		$obj_cron = new mf_cron();
		$obj_cron->start(__CLASS__.'_parent');

		if($obj_cron->is_running == false)
		{
			/*if(isset($_GET['doing_wp_cron']))
			{
				echo "Running cron manually...";
			}*/

			update_option('option_cron_started', date("Y-m-d H:i:s"), false);
		}
	}

	function get_ftp_size($data)
	{
		if(!is_dir($data['file']))
		{
			$ftp_size = @filesize($data['file']);

			$this->ftp_size += $ftp_size;

			$file_name = basename($data['file']);
			$arr_folder_name = explode("/", str_replace(ABSPATH, "", $data['file']));

			$count_temp = count($arr_folder_name);

			for($i = 0; $i < $count_temp; $i++)
			{
				switch($i)
				{
					case 0:
						$folder_depth_0 = (isset($arr_folder_name[0]) && $arr_folder_name[0] != '' && $arr_folder_name[0] != $file_name ? $arr_folder_name[0] : "/");

						if(!isset($this->ftp_size_folders[$folder_depth_0]))
						{
							$this->ftp_size_folders[$folder_depth_0] = array(
								'size' => $ftp_size,
								'children' => [],
							);
						}

						else
						{
							$this->ftp_size_folders[$folder_depth_0]['size'] += $ftp_size;
						}
					break;

					case 1:
						$folder_depth_1 = (isset($arr_folder_name[1]) && $arr_folder_name[1] != '' && $arr_folder_name[1] != $file_name ? $arr_folder_name[1] : "/");

						if(!isset($this->ftp_size_folders[$folder_depth_0]['children'][$folder_depth_1]))
						{
							$this->ftp_size_folders[$folder_depth_0]['children'][$folder_depth_1] = array(
								'size' => $ftp_size,
								'children' => [],
							);
						}

						else
						{
							$this->ftp_size_folders[$folder_depth_0]['children'][$folder_depth_1]['size'] += $ftp_size;
						}
					break;

					case 2:
						$folder_depth_2 = (isset($arr_folder_name[2]) && $arr_folder_name[2] != '' && $arr_folder_name[2] != $file_name ? $arr_folder_name[2] : "/");

						if(!isset($this->ftp_size_folders[$folder_depth_0]['children'][$folder_depth_1]['children'][$folder_depth_2]))
						{
							$this->ftp_size_folders[$folder_depth_0]['children'][$folder_depth_1]['children'][$folder_depth_2] = array(
								'size' => $ftp_size,
								'children' => [],
							);
						}

						else
						{
							$this->ftp_size_folders[$folder_depth_0]['children'][$folder_depth_1]['children'][$folder_depth_2]['size'] += $ftp_size;
						}
					break;

					case 3:
						$folder_depth_3 = (isset($arr_folder_name[3]) && $arr_folder_name[3] != '' && $arr_folder_name[3] != $file_name ? $arr_folder_name[3] : "/");

						if(!isset($this->ftp_size_folders[$folder_depth_0]['children'][$folder_depth_1]['children'][$folder_depth_2]['children'][$folder_depth_3]))
						{
							$this->ftp_size_folders[$folder_depth_0]['children'][$folder_depth_1]['children'][$folder_depth_2]['children'][$folder_depth_3] = array(
								'size' => $ftp_size,
								'children' => [],
							);
						}

						else
						{
							$this->ftp_size_folders[$folder_depth_0]['children'][$folder_depth_1]['children'][$folder_depth_2]['children'][$folder_depth_3]['size'] += $ftp_size;
						}
					break;
				}
			}
		}
	}

	function reset_time_limited()
	{
		$option_base_time_limited = get_option_or_default('option_base_time_limited', []);

		$has_changed = false;

		foreach($option_base_time_limited as $key => $value)
		{
			if($value < date("Y-m-d H:i:s"))
			{
				delete_site_option($key);
				delete_option($key);

				unset($option_base_time_limited[$key]);

				$has_changed = true;
			}
		}

		if($has_changed == true)
		{
			update_option('option_base_time_limited', $option_base_time_limited, false);
		}
	}

	function do_optimize()
	{
		global $wpdb;

		$i = 0;

		// Old trash
		####################
		$result = $wpdb->get_results("SELECT ID FROM ".$wpdb->posts." WHERE post_status = 'trash' AND post_modified < DATE_SUB(NOW(), INTERVAL ".EMPTY_TRASH_DAYS." DAY)");

		foreach($result as $r)
		{
			wp_delete_post($r->ID);

			$i++;

			if($i % 20 == 0)
			{
				sleep(1);
				set_time_limit(60);
			}
		}
		####################

		// Old revisions and auto-drafts
		####################
		$result = $wpdb->get_results("SELECT ID FROM ".$wpdb->posts." WHERE post_status IN ('revision', 'auto-draft') AND post_modified < DATE_SUB(NOW(), INTERVAL 12 MONTH)");

		foreach($result as $r)
		{
			wp_delete_post($r->ID);

			$i++;

			if($i % 20 == 0)
			{
				sleep(1);
				set_time_limit(60);
			}
		}
		####################

		// Orphan postmeta
		####################
		$wpdb->query("DELETE ".$wpdb->postmeta." FROM ".$wpdb->postmeta." LEFT JOIN ".$wpdb->posts." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE ".$wpdb->posts.".ID IS NULL");
		####################

		// Duplicate postmeta
		####################
		$result = $wpdb->get_results("SELECT meta_id, COUNT(meta_id) AS count FROM ".$wpdb->postmeta." GROUP BY post_id, meta_key, meta_value HAVING count > 1");

		foreach($result as $r)
		{
			$intMetaID = $r->meta_id;

			$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->postmeta." WHERE meta_id = '%d'", $intMetaID));

			$i++;

			if($i % 20 == 0)
			{
				sleep(1);
				set_time_limit(60);
			}
		}
		####################

		// Empty postmeta
		####################
		$wpdb->query("DELETE FROM ".$wpdb->postmeta." WHERE meta_key LIKE 'mf_%' AND (meta_value = '' OR meta_value IS null)");
		####################

		// Duplicate usermeta
		####################
		$result = $wpdb->get_results("SELECT umeta_id, COUNT(umeta_id) AS count FROM ".$wpdb->usermeta." GROUP BY user_id, meta_key, meta_value HAVING count > 1");

		foreach($result as $r)
		{
			$intMetaID = $r->umeta_id;

			$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->usermeta." WHERE umeta_id = '%d'", $intMetaID));

			$i++;

			if($i % 20 == 0)
			{
				sleep(1);
				set_time_limit(60);
			}
		}
		####################

		// Spam comments
		####################
		$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->comments." WHERE comment_approved = %s AND comment_date < DATE_SUB(NOW(), INTERVAL 12 MONTH)", 'spam'));
		####################

		// Duplicate comments
		####################
		$wpdb->get_results($wpdb->prepare("SELECT *, COUNT(meta_id) AS count FROM ".$wpdb->commentmeta." GROUP BY comment_id, meta_key, meta_value HAVING count > %d", 1));

		if($wpdb->num_rows > 0)
		{
			do_log("Remove duplicate comments: ".$wpdb->last_query);
		}
		####################

		// oEmbed caches
		####################
		$wpdb->get_results($wpdb->prepare("DELETE FROM ".$wpdb->postmeta." WHERE meta_key LIKE %s", "%_oembed_%"));
		####################

		// Optimize Tables
		####################
		$result = $wpdb->get_results("SHOW TABLE STATUS");

		foreach($result as $r)
		{
			$wpdb->query("OPTIMIZE TABLE ".$r->Name);

			$i++;

			if($i % 20 == 0)
			{
				sleep(1);
				set_time_limit(60);
			}
		}
		####################

		// Empty folders in uploads
		####################
		list($upload_path, $upload_url) = get_uploads_folder('', true, false);

		if($upload_path != '')
		{
			get_file_info(array('path' => $upload_path, 'folder_callback' => array($this, 'delete_empty_folder_callback')));
		}
		####################

		update_option('option_base_optimized', date("Y-m-d H:i:s"), false);

		return __("I have optimized the site for you", 'lang_base');
	}

	function set_noindex_on_page($option)
	{
		if(is_array($option))
		{
			if(count($option) > 0)
			{
				foreach($option as $option_value)
				{
					update_post_meta($option_value, $this->meta_prefix.'page_index', 'no');
				}
			}
		}

		else if($option > 0)
		{
			update_post_meta($option, $this->meta_prefix.'page_index', 'no');
		}
	}

	function cron_base()
	{
		global $wpdb;

		$obj_cron = new mf_cron();
		$obj_cron->start(__CLASS__);

		if($obj_cron->is_running == false)
		{
			replace_option(array('old' => 'setting_theme_optimize', 'new' => 'setting_base_optimize'));
			replace_option(array('old' => 'option_database_optimized', 'new' => 'option_base_optimized'));

			if(is_plugin_active("mf_theme_core/index.php"))
			{
				global $obj_theme_core;

				if(!isset($obj_theme_core))
				{
					$obj_theme_core = new mf_theme_core();
				}

				replace_post_meta(array('old' => $obj_theme_core->meta_prefix.'page_index', 'new' => $this->meta_prefix.'page_index'));
			}

			$result = $wpdb->get_results($wpdb->prepare("SELECT ID, meta_value FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE meta_key = %s AND meta_value != ''", $this->meta_prefix.'page_index'));

			foreach($result as $r)
			{
				if(in_array($r->meta_value, array('noindex', 'nofollow', 'none')))
				{
					update_post_meta($r->ID, $this->meta_prefix.'page_index', 'no');
				}
			}

			mf_uninstall_plugin(array(
				'options' => array('option_cron_run', 'setting_base_php_info', 'setting_base_empty_trash_days', 'setting_base_automatic_updates', 'setting_base_cron_debug', 'option_sync_sites', 'option_github_access_token', 'option_git_updater', 'option_github_updates'),
				'meta' => array($this->meta_prefix.'publish_date', $this->meta_prefix.'unpublish_date'),
			));

			// Optimize
			#########################
			if(get_option('option_base_optimized') < date("Y-m-d H:i:s", strtotime("-4 hour")))
			{
				$this->do_optimize();
			}
			#########################

			// Save disc size and large table sizes
			############################
			$this->ftp_size = 0;
			$this->ftp_size_folders = [];

			get_file_info(array('path' => ABSPATH, 'callback' => array($this, 'get_ftp_size')));

			update_site_option('option_base_ftp_size', $this->ftp_size);
			update_site_option('option_base_ftp_size_folders', $this->ftp_size_folders);

			$arr_db_info = $this->get_db_info(array('limit' => (MB_IN_BYTES * 10)));

			update_site_option('option_base_db_size', $arr_db_info['db_size']);
			update_site_option('option_base_large_tables', $arr_db_info['tables']);
			############################

			$this->reset_time_limited();

			// Delete old uploads
			#######################
			list($upload_path, $upload_url) = get_uploads_folder($this->post_type);

			get_file_info(array('path' => $upload_path, 'callback' => 'delete_files_callback', 'time_limit' => WEEK_IN_SECONDS));
			get_file_info(array('path' => $upload_path, 'folder_callback' => 'delete_empty_folder_callback'));
			#######################
		}

		$obj_cron->end();
	}

	function run_cron_end()
	{
		$obj_cron = new mf_cron();
		$obj_cron->end(__CLASS__."_parent");

		update_option('option_cron_ended', date("Y-m-d H:i:s"), false);
	}

	function has_page_template($data = [])
	{
		global $wpdb;

		if(isset($data['template']))
		{
			return $wpdb->get_var($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE post_type = %s AND meta_key = %s AND meta_value = %s LIMIT 0, 1", 'page', '_wp_page_template', $data['template']));
		}
	}

	function return_bytes($type)
	{
		$value = ini_get($type);

		$number = substr($value, 0, -1);
		$suffix = strtoupper(substr($value, -1));

		if($number > 0)
		{
			switch($suffix)
			{
				case 'G':
					$number *= GB_IN_BYTES;
				break;

				case 'M':
					$number *= MB_IN_BYTES;
				break;

				case 'K':
					$number *= KB_IN_BYTES;
				break;

				default:
					// ...then assume that it already is bytes
				break;
			}
		}

		else
		{
			do_log("The value was nothing in return_bytes() (".$type." -> ".$value.")", 'notification');
		}

		return $number;
	}

	function get_db_info($data = [])
	{
		global $wpdb;

		if(!isset($data['limit'])){		$data['limit'] = MB_IN_BYTES;}

		$out = array(
			'db_size' => 0,
			'tables' => [],
		);

		$result = $wpdb->get_results("SHOW TABLES", ARRAY_N);

		foreach($result as $r)
		{
			$table_id = $table_name = $r[0];

			$table_size = $wpdb->get_var($wpdb->prepare("SELECT (DATA_LENGTH + INDEX_LENGTH) FROM information_schema.TABLES WHERE table_schema = %s AND table_name = %s", DB_NAME, $table_id));

			$out['db_size'] += $table_size;

			if($table_size > $data['limit'])
			{
				$arr_content = [];

				if(preg_match('/_posts$/', $table_name))
				{
					$result_post_types = $wpdb->get_results($wpdb->prepare("SELECT post_type, COUNT(post_type) AS post_type_amount FROM ".$table_name." GROUP BY post_type ORDER BY post_type_amount DESC LIMIT 0, 3"));

					foreach($result_post_types as $r)
					{
						$arr_content[$r->post_type] = $r->post_type_amount;
					}
				}

				else if(preg_match('/_postmeta$/', $table_name))
				{
					$result_meta_keys = $wpdb->get_results($wpdb->prepare("SELECT meta_key, COUNT(meta_key) AS meta_key_amount FROM ".$table_name." GROUP BY meta_key ORDER BY meta_key_amount DESC LIMIT 0, 3"));

					foreach($result_meta_keys as $r)
					{
						$arr_content[$r->meta_key] = $r->meta_key_amount;
					}
				}

				$out['tables'][] = array(
					'name' => $table_name,
					'size' => $table_size,
					'content' => $arr_content,
				);
			}
		}

		$out['tables'] = $this->array_sort(array('array' => $out['tables'], 'on' => 'size', 'order' => 'desc'));

		return $out;
	}

	function get_schedules_for_select()
	{
		$arr_schedules = wp_get_schedules();

		$arr_data = [];

		foreach($arr_schedules as $key => $value)
		{
			$arr_data[$key] = $value['display'];
		}

		return $arr_data;
	}

	function get_server_type()
	{
		if($this->server_type == '')
		{
			if(stripos($_SERVER['SERVER_SOFTWARE'], "Apache") !== false || stripos($_SERVER['SERVER_SOFTWARE'], "LiteSpeed") !== false)
			{
				$this->server_type = 'apache';
			}

			else if(stripos($_SERVER['SERVER_SOFTWARE'], "Nginx") !== false)
			{
				$this->server_type = 'nginx';
			}

			else if(stripos($_SERVER['SERVER_SOFTWARE'], "Microsoft") !== false || stripos($_SERVER['SERVER_SOFTWARE'], "IIS") !== false)
			{
				$this->server_type = 'iis';
			}

			else
			{
				$this->server_type = '';

				do_log(__("Unknown Server", 'lang_base').": ".$_SERVER['SERVER_SOFTWARE']);
			}
		}

		return $this->server_type;
	}

	function get_current_visitor_ip($out)
	{
		if($out == "")
		{
			$out = (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : "");
		}

		if(isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] != '')
		{
			$ip_adresses = array_values(array_filter(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])));

			if(isset($ip_adresses[0]) && $ip_adresses[0] != '' && $ip_adresses[0] != $out)
			{
				$out = $ip_adresses[0];
			}
		}

		return $out;
	}

	function has_comments()
	{
		global $wpdb;

		if(does_table_exist($wpdb->comments))
		{
			$wpdb->get_results($wpdb->prepare("SELECT comment_ID FROM ".$wpdb->comments." WHERE comment_approved NOT IN('spam', 'trash', 'post-trashed') AND comment_type = %s LIMIT 0, 1", 'comment'));

			return ($wpdb->num_rows > 0);
		}

		return false;
	}

	function filter_meta_input($array, $post_id = 0)
	{
		foreach($array as $key => $value)
		{
			if($value == '')
			{
				if($post_id > 0)
				{
					delete_post_meta($post_id, $key);
				}

				unset($array[$key]);
			}
		}

		return $array;
	}

	function wp_before_admin_bar_render()
	{
		global $wp_admin_bar;

		$wp_admin_bar->remove_menu('wp-logo');
		//$wp_admin_bar->remove_menu('view');

		if(apply_filters('has_comments', true) == false)
		{
			$wp_admin_bar->remove_menu('comments');
		}

		$wp_admin_bar->remove_menu('new-content');
	}

	function settings_base()
	{
		$options_area_orig = $options_area = __FUNCTION__;

		add_settings_section($options_area, "",	array($this, $options_area."_callback"), BASE_OPTIONS_PAGE);

		$arr_settings = array(
			'setting_base_info' => __("Status", 'lang_base'),
			'setting_base_cron' => __("Scheduled to run", 'lang_base'),
			//'setting_base_cron_debug' => __("Debug Schedule", 'lang_base'),
		);

		switch($this->get_server_type())
		{
			case 'apache':
				$config_file = ".htaccess";
			break;

			case 'nginx':
				$config_file = "nginx.conf";
			break;

			case 'iis':
				$config_file = "web.config";
			break;

			default:
				$config_file = "";
			break;
		}

		if(IS_SUPER_ADMIN)
		{
			if($config_file != '')
			{
				$arr_settings['setting_base_update_htaccess'] = sprintf(__("Automatically Update %s", 'lang_base'), $config_file);
			}

			$arr_settings['setting_base_prefer_www'] = sprintf(__("Prefer %s in front domain", 'lang_base'), "www");
		}

		if(IS_SUPER_ADMIN)
		{
			list($date_diff, $ftp_date, $db_date) = $this->get_date_diff();
 
			if($date_diff > 10 || get_site_option('setting_base_use_timezone') == 'yes')
			{
				$arr_settings['setting_base_use_timezone'] = __("Use Timezone to adjust time", 'lang_base');
			}

			$arr_settings['setting_base_optimize'] = __("Optimize", 'lang_base');
			$arr_settings['setting_base_recommend'] = __("Recommendations", 'lang_base');
		}

		show_settings_fields(array('area' => $options_area, 'object' => $this, 'settings' => $arr_settings));
	}

	function settings_base_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);

		echo settings_header($setting_key, __("Common", 'lang_base'));
	}

		function get_date_diff()
		{
			global $wpdb;

			$db_date = strtotime($wpdb->get_var("SELECT LOCALTIME()"));
			$ftp_date = strtotime(date("Y-m-d H:i:s"));
			$date_diff = abs($db_date - $ftp_date);

			return array($date_diff, $ftp_date, $db_date);
		}

		function api_base_info()
		{
			global $wpdb;

			$json_output = array(
				'success' => false,
			);

			ob_start();

			$php_version = explode("-", phpversion());
			$php_version = $php_version[0];

			$mysql_version = $wpdb->db_version();

			$php_required = "7.4";
			$mysql_required = "5.0";

			$has_required_php_version = version_compare($php_version, $php_required, ">");
			$has_required_mysql_version = version_compare($mysql_version, $mysql_required, ">");

			$mysql_title = __("DB", 'lang_base').": ".DB_NAME.", ".__("Prefix", 'lang_base').": ".$wpdb->prefix;

			list($date_diff, $ftp_date, $db_date) = $this->get_date_diff();

			$total_space = (function_exists('disk_total_space') ? disk_total_space(ABSPATH) : 0);

			if($total_space > 0)
			{
				$free_space = disk_free_space(ABSPATH);

				$free_percent = ($free_space / $total_space) * 100;
			}

			echo "<div class='flex_flow'>
				<div>";

					if(!$has_required_php_version || $php_version != $mysql_version)
					{
						echo "<p>
							<i class='fa ".($has_required_php_version ? "fa-check green" : "fa-times red display_warning")."'></i> PHP: ".$php_version;

							if(!$has_required_php_version)
							{
								echo " <a href='//wordpress.org/about/requirements/'><i class='fa fa-info-circle blue'></i></a>";
							}

						echo "</p>";
					}

					echo "<p title='".$mysql_title."'>
						<i class='fa ".($has_required_mysql_version ? "fa-check green" : "fa-times red display_warning")."'></i> MySQL: ".$mysql_version; //." (".$wpdb->db_server_info().")"

						if(!$has_required_mysql_version)
						{
							echo " <a href='//wordpress.org/about/requirements/'><i class='fa fa-info-circle blue'></i></a>";
						}

					echo "</p>";

					switch(get_bloginfo('language'))
					{
						case 'sv-SE':
							$collation_name = $wpdb->get_var($wpdb->prepare("SELECT DEFAULT_COLLATION_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = %s LIMIT 1", DB_NAME));
							$arr_collation_name_recommended = array('utf8mb4_swedish_ci', 'utf8mb3_swedish_ci', 'latin1_swedish_ci');

							if(!in_array($collation_name, $arr_collation_name_recommended))
							{
								$arr_collation_name_parts = explode("_", $collation_name);

								$collation_name_recommended = implode(", ", $arr_collation_name_recommended);

								foreach($arr_collation_name_recommended as $key => $value)
								{
									if(substr($value, 0, strlen($arr_collation_name_parts[0])) == $arr_collation_name_parts[0])
									{
										$collation_name_recommended = $value;
									}
								}

								echo "<p><i class='fa fa-times red display_warning'></i> ".__("Language", 'lang_base').": ".$collation_name." -> ".$collation_name_recommended." (DB_CHARSET: ".DB_CHARSET.", DB_COLLATE: ".DB_COLLATE.")</p>";
							}
						break;
					}

					if($date_diff > 60)
					{
						echo "<p><i class='fa ".($date_diff < 60 ? "fa-check green" : "fa-times red display_warning")."'></i> ".__("Time Difference", 'lang_base').": ".format_date(date("Y-m-d H:i:s", $ftp_date))." (PHP), ".format_date(date("Y-m-d H:i:s", $db_date))." (MySQL)</p>";
					}

					else
					{
						echo "<p><i class='fa fa-check green'></i> ".__("Time on Server", 'lang_base').": ".format_date(date("Y-m-d H:i:s", $ftp_date))."</p>";
					}

					if(isset($free_percent))
					{
						$ul_content = "";

						if(IS_SUPER_ADMIN)
						{
							$option_base_ftp_size = get_site_option('option_base_ftp_size');
							$option_base_ftp_size_folders = get_site_option('option_base_ftp_size_folders');
							$option_base_db_size = get_site_option('option_base_db_size');

							if($option_base_ftp_size > 0)
							{
								$ul_content .= "<li>".__("Files", 'lang_base').": ".show_final_size($option_base_ftp_size)."</li>";
							}

							if(is_array($option_base_ftp_size_folders) && count($option_base_ftp_size_folders) > 0)
							{
								$size_limit = ($option_base_ftp_size * .02);

								foreach($option_base_ftp_size_folders as $key => $arr_value)
								{
									if(isset($arr_value['children']) && count($arr_value['children']) > 0)
									{
										$out_temp = "";

										foreach($arr_value['children'] as $sub_key => $arr_sub_value)
										{
											if(isset($arr_sub_value['children']) && count($arr_sub_value['children']) > 0)
											{
												$sub_out_temp = "";

												foreach($arr_sub_value['children'] as $sub_sub_key => $arr_sub_sub_value)
												{
													if(isset($arr_sub_sub_value['children']) && count($arr_sub_sub_value['children']) > 0)
													{
														$sub_sub_out_temp = "";

														foreach($arr_sub_sub_value['children'] as $sub_sub_sub_key => $arr_sub_sub_sub_value)
														{
															if(isset($arr_sub_sub_sub_value['children']) && count($arr_sub_sub_sub_value['children']) > 0)
															{
																// Can this happen???
															}

															else if($arr_sub_sub_sub_value['size'] > $size_limit)
															{
																$path_temp = $key."/".$sub_key."/".$sub_sub_key;

																if(is_multisite() && $path_temp == "wp-content/uploads/sites")
																{
																	$path_temp = __("Files", 'lang_base')." (".get_blog_option($sub_sub_sub_key, 'blogname').")";
																}

																else
																{
																	$path_temp .= "/".$sub_sub_sub_key;
																}

																$sub_sub_out_temp .= "<li>".$path_temp.": ".show_final_size($arr_sub_sub_sub_value['size'])."</li>";
															}
														}

														if($sub_sub_out_temp != '')
														{
															$sub_out_temp .= "<ul>".$sub_sub_out_temp."</ul>";
														}
													}

													else if($arr_sub_sub_value['size'] > $size_limit)
													{
														$sub_out_temp .= "<li>".$key."/".$sub_key."/".$sub_sub_key.": ".show_final_size($arr_sub_sub_value['size'])."</li>";
													}
												}

												if($sub_out_temp != '')
												{
													$out_temp .= "<ul>".$sub_out_temp."</ul>";
												}
											}

											else if($arr_sub_value['size'] > $size_limit)
											{
												$out_temp .= "<li>".$key."/".$sub_key.": ".show_final_size($arr_sub_value['size'])."</li>";
											}
										}

										if($out_temp != '')
										{
											$ul_content .= "<ul>".$out_temp."</ul>";
										}
									}

									else if($arr_value['size'] > $size_limit)
									{
										$ul_content .= "<li>".$key.": ".show_final_size($arr_value['size'])."</li>";
									}
								}
							}

							if($option_base_db_size > 0)
							{
								$ul_content .= "<li>".__("DB", 'lang_base').": ".show_final_size($option_base_db_size)."</li>";
							}
						}

						echo "<div class='display_parent'>
							<p>
								<i class='fa ".($free_percent > 10 ? "fa-check green" : "fa-times red display_warning")."'></i> "
								.__("Space Left", 'lang_base').": ".mf_format_number($free_percent, 0)."% (".show_final_size($free_space)." / ".show_final_size($total_space).")"
							."</p>";

							if($ul_content != '')
							{
								echo "<ul class='display_on_hover'>".$ul_content."</ul>";
							}

						echo "</div>";
					}

					else
					{
						echo "<p>
							<i class='fa fa-times red display_warning'></i> "
							.__("Space Left", 'lang_base').": ".__("Unknown", 'lang_base')
						."</p>";
					}

					if(IS_SUPER_ADMIN)
					{
						$upload_dir = str_replace("/wp-content/uploads", "/", wp_upload_dir()['basedir']);

						if(preg_match("/sites/", $upload_dir))
						{
							$upload_dir = str_replace("/sites/".$wpdb->blogid, "/", $upload_dir);
						}

						$upload_dir = trim($upload_dir, "/");
						$current_dir = realpath(str_replace("/wp-content/plugins/mf_base/include", "/", dirname(__FILE__)));
						$current_dir = trim($current_dir, "/");

						if($upload_dir == $current_dir)
						{
							$filesystem_method = get_filesystem_method();

							$uploads_icon = ($filesystem_method == 'direct' ? "fa-check green" : "fa-times red display_warning");

							echo "<div class='display_parent'>
								<p>
									<i class='fa ".$uploads_icon."'></i> ".__("Upload Directory", 'lang_base').": <i class='fas fa-bezier-curve green' title='".$upload_dir."'></i>"
								."</p>
								<ul class='display_on_hover no_style'>
									<li><i class='fa ".$uploads_icon."'></i> ".__("Method", 'lang_base').": ";

										switch($filesystem_method)
										{
											case 'direct':
												echo __("Direct", 'lang_base');
											break;

											case 'ssh':
												echo __("SSH", 'lang_base');
											break;

											case 'ftpext':
												echo __("FTP", 'lang_base');
											break;

											case 'ftpsockets':
												echo __("FTP Sockets", 'lang_base');
											break;
										}

									echo "</li>";

									$test_folder = ABSPATH;
									$test_file = ABSPATH.'index.php';

									if(!defined('FS_CHMOD_DIR'))
									{
										define('FS_CHMOD_DIR', (fileperms($test_folder) & 0777 | 0755));
									}

									if(!defined('FS_CHMOD_FILE'))
									{
										define('FS_CHMOD_FILE', (fileperms($test_file) & 0777 | 0644));
									}

									if(defined('FS_CHMOD_DIR'))
									{
										echo "<li>
											<i class='fa ".(FS_CHMOD_DIR >= $this->chmod_dir ? "fa-check green" : "fa-times red display_warning")."'></i> ".__("Directories", 'lang_base').": ".decoct(FS_CHMOD_DIR);

											if(FS_CHMOD_DIR < $this->chmod_dir)
											{
												echo " < ".decoct($this->chmod_dir);
											}

										echo "</li>";
									}

									if(defined('FS_CHMOD_FILE'))
									{
										echo "<li>
											<i class='fa ".(FS_CHMOD_FILE >= $this->chmod_file ? "fa-check green" : "fa-times red display_warning")."'></i> ".__("Files", 'lang_base').": ".decoct(FS_CHMOD_FILE);

											if(FS_CHMOD_FILE < $this->chmod_file)
											{
												echo " < ".decoct($this->chmod_file);
											}

										echo "</li>";
									}

								echo "</ul>
							</div>";
						}

						else
						{
							echo "<p>
								<i class='fa fa-times red display_warning'></i> "
								.__("Upload Directory", 'lang_base').": ".$upload_dir." != ".$current_dir
							."</p>";
						}
					}

					$option_base_large_tables = get_site_option_or_default('option_base_large_tables', []);
					$option_base_large_table_amount = count($option_base_large_tables);

					if($option_base_large_table_amount > 0)
					{
						echo "<div class='display_parent'>
							<p>
								<i class='fa ".($option_base_large_table_amount == 0 ? "fa-check green" : "fa-times red display_warning")."'></i> "
								.__("DB", 'lang_base').": "
								."<span>".sprintf(__("%d tables larger than %s", 'lang_base'), $option_base_large_table_amount, "10MB")."</span>"
							."</p>";

							if(IS_SUPER_ADMIN && count($option_base_large_tables) > 0)
							{
								echo "<ul class='display_on_hover'>";

									foreach($option_base_large_tables as $arr_table)
									{
										echo "<li>".$arr_table['name']." ("
											.show_final_size($arr_table['size'])
											.(IS_SUPER_ADMIN && isset($arr_table['content']) && count($arr_table['content']) > 0 ? ", ".str_replace("'", "", var_export($arr_table['content'], true)) : "")
										.")</li>";
									}

								echo "</ul>";
							}

						echo "</div>";
					}

					if(!defined('NONCE_SALT'))
					{
						echo "<p>
							<i class='fa ".(defined('NONCE_SALT') ? "fa-check green" : "fa-times red display_warning")."'></i> "
							.__("Configuration", 'lang_base').": "
							."<span>".sprintf(__("%s has no value. Add it to %s", 'lang_base'), "NONCE_SALT", 'wp-config.php')."</span>"
						."</p>";
					}

					echo "<p>
						<i class='fa ".(EMPTY_TRASH_DAYS <= 30 ? "fa-check green" : "fa-times red display_warning")."'></i> "
						.__("Trash", 'lang_base').": "
						."<span>".sprintf(__("%d days", 'lang_base'), EMPTY_TRASH_DAYS)."</span>"
					."</p>";

				echo "</div>
				<div>
					<p>
						<i class='fa ".($this->memory_limit_current >= $this->memory_limit ? "fa-check green" : "fa-times red display_warning")."'></i> "
						.__("Memory Limit", 'lang_base').": ".show_final_size($this->memory_limit_current);

							if($this->memory_limit_current < $this->memory_limit)
							{
								echo " < ".show_final_size($this->memory_limit)." <i class='fa fa-info-circle blue' title='memory_limit = ".show_final_size($this->memory_limit, false)."M'></i>";
							}

						echo "</p>";

					if($this->post_max_size_current != $this->memory_limit_current)
					{
						echo "<p>
							<i class='fa ".($this->post_max_size_current >= $this->post_max_size ? "fa-check green" : "fa-times red display_warning")."'></i> "
							.__("Post Limit", 'lang_base').": ".show_final_size($this->post_max_size_current);

							if($this->post_max_size_current < $this->post_max_size)
							{
								echo " < ".show_final_size($this->post_max_size)." <i class='fa fa-info-circle blue' title='post_max_size = ".show_final_size($this->post_max_size, false)."M'></i>";
							}

						echo "</p>";
					}

					if($this->upload_max_filesize_current != $this->memory_limit_current)
					{
						echo "<p>
							<i class='fa ".($this->upload_max_filesize_current >= $this->upload_max_filesize ? "fa-check green" : "fa-times red display_warning")."'></i> "
							.__("Upload Limit", 'lang_base').": ".show_final_size($this->upload_max_filesize_current);

							if($this->upload_max_filesize_current < $this->upload_max_filesize)
							{
								echo " < ".show_final_size($this->upload_max_filesize)." <i class='fa fa-info-circle blue' title='upload_max_filesize = ".show_final_size($this->upload_max_filesize, false)."M'></i>";
							}

						echo "</p>";
					}

					if(is_multisite())
					{
						$fileupload_maxk = (KB_IN_BYTES * $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM ".$wpdb->sitemeta." WHERE meta_key = %s LIMIT 0, 1", 'fileupload_maxk')));

						echo "<p>
							<i class='fa ".($fileupload_maxk >= $this->upload_max_filesize ? "fa-check green" : "fa-times red display_warning")."'></i> "
							.__("Upload Limit", 'lang_base')." (".__("Network", 'lang_base')."): ".show_final_size($fileupload_maxk);

							if($fileupload_maxk < $this->upload_max_filesize)
							{
								echo " < ".show_final_size($this->upload_max_filesize)." <a href='".network_admin_url("settings.php")."'><i class='fa fa-wrench'></i></a>";
							}

						echo "</p>";
					}

					if(function_exists('sys_getloadavg'))
					{
						$load = sys_getloadavg();
						$load_limit = 1;

						$arr_load_types = array(
							0 => 1,
							1 => 5,
							2 => 15,
						);

						foreach($arr_load_types as $key => $value)
						{
							if(isset($load[$key]) && $load[$key] >= $load_limit)
							{
								$load_is_within_limit = ($load[$key] < $load_limit);

								echo "<p><i class='fa ".($load_is_within_limit ? "fa-check green" : "fa-times red display_warning")."'></i> ".__("Load", 'lang_base')." &lt; ".$value." ".__("min", 'lang_base').": ".mf_format_number($load[$key])."</p>";

								if($load_is_within_limit == false)
								{
									break;
								}
							}
						}
					}

					// Autoload
					########################
					$arr_autoload_type = array(
						'yes' => array('byte' => 0, 'limit' => (MB_IN_BYTES / 2)),
						'on' => array('alias' => 'yes'),
						'auto' => array('byte' => 0, 'limit' => (MB_IN_BYTES * 5)),
						'no' => array('byte' => 0),
						'off' => array('alias' => 'no'),
					);

					foreach($arr_autoload_type as $key => $value)
					{
						$arr_autoload_type[(isset($value['alias']) ? $value['alias'] : $key)]['byte'] += $wpdb->get_var($wpdb->prepare("SELECT SUM(LENGTH(option_value)) FROM ".$wpdb->options." WHERE autoload = %s", $key));
					}

					$out_temp = "";

					foreach($arr_autoload_type as $key => $value)
					{
						if(isset($value['byte']) && $value['byte'] > 0)
						{
							$out_temp .= ($out_temp != '' ? ", " : "")."<span title='".show_final_size($value['byte'])."'".(isset($arr_autoload_type[$key]['limit']) && $value['byte'] > $arr_autoload_type[$key]['limit'] ? " class='color_red'" : "").">".$key."</span>";
						}
					}

					$autoload_is_within_limits = ($arr_autoload_type['yes']['byte'] < $arr_autoload_type['yes']['limit'] && $arr_autoload_type['auto']['byte'] < $arr_autoload_type['auto']['limit']);

					if($autoload_is_within_limits == false)
					{
						echo "<p>
							<i class='fa ".($autoload_is_within_limits ? "fa-check green" : "fa-times red display_warning")."'></i> "
							.__("Autoload", 'lang_base').": ".$out_temp
						."</p>";
					}
					########################

					$current_visitor_ip = apply_filters('get_current_visitor_ip', "");

					echo "<p>
						<i class='fa ".($current_visitor_ip != '' ? "fa-check green" : "fa-times red display_warning")."'></i> "
						.__("My IP", 'lang_base').": ".$current_visitor_ip." (UID: ".get_current_user_id().")"
					."</p>
				</div>
			</div>";

			$json_output['success'] = true;
			$json_output['html'] = ob_get_clean();

			header('Content-Type: application/json');
			echo json_encode($json_output);
			die();
		}

		function setting_base_info_callback()
		{
			echo "<div class='api_base_info'>".apply_filters('get_loading_animation', '')."</div>";
		}

		function api_base_cron()
		{
			global $wpdb;

			$json_output = array(
				'success' => false,
			);

			ob_start();

			$obj_cron = new mf_cron();

			$setting_key = get_setting_key(__FUNCTION__);
			$option = get_option($setting_key, 'every_ten_minutes');

			$this->reschedule_base($option);

			if(defined('DISABLE_WP_CRON') && DISABLE_WP_CRON == true)
			{
				echo "<a href='".get_site_url()."/wp-cron.php?doing_wp_cron'>".__("Run schedule manually", 'lang_base')."</a> ";
			}

			$cron_interval = ($obj_cron->get_interval() / 60);

			$last_run_threshold = date("Y-m-d H:i:s", strtotime("-".$cron_interval." minute"));

			$option_cron_started = get_option('option_cron_started');
			$option_cron_ended = get_option('option_cron_ended');

			echo "<div class='display_parent'>";

				if($option_cron_started > $option_cron_ended)
				{
					echo "<em>".sprintf(__("Last started %s but has not finished.", 'lang_base'), format_date($option_cron_started))."</em>";
				}

				else if($option_cron_ended != '')
				{
					if(get_next_cron(array('raw' => true)) < $last_run_threshold && $option_cron_ended < $last_run_threshold)
					{
						echo "<span>".__("Running schedule...", 'lang_base')."</span> ";

						do_action('cron_base');
					}

					else
					{
						$out_temp = format_date($option_cron_started);

						if(format_date($option_cron_ended) != $out_temp)
						{
							$out_temp .= ($out_temp != '' ? " - " : "").format_date($option_cron_ended);
						}

						echo "<em>".sprintf(__("Last run %s.", 'lang_base'), $out_temp)."</em>";
					}
				}

				else
				{
					echo "<em>".__("Has never been run.", 'lang_base')."</em>";
				}

				if((!defined('DISABLE_WP_CRON') || DISABLE_WP_CRON == false))
				{
					$next_cron = get_next_cron();

					if($next_cron != '')
					{
						echo " <span>".sprintf(__("Next scheduled %s.", 'lang_base'), $next_cron)."</span>";
					}
				}

				if(IS_SUPER_ADMIN)
				{
					$option_cron_progress = get_option('option_cron_progress');

					if(is_array($option_cron_progress) && count($option_cron_progress) > 0)
					{
						echo "<br>
						<ul class='display_on_hover'>";

							foreach($option_cron_progress as $key => $arr_value)
							{
								$li_class = "";

								if($key == 'mf_base_parent')
								{
									$li_class .= ($li_class != '' ? " " : "")."strong";
								}

								if(!isset($arr_value['start']))
								{
									do_log(__FUNCTION__.": No start time (".var_export($arr_value, true).")");
								}

								if($arr_value['end'] <= $arr_value['start'])
								{
									$li_class .= ($li_class != '' ? " " : "")."grey";
								}

								echo "<li".($li_class != "" ? " class='".$li_class."'" : "").">"
									.$key.": ";

									if($arr_value['end'] >= $arr_value['start'])
									{
										echo time_between_dates(array('start' => $arr_value['start'], 'end' => $arr_value['end']));
									}

									else
									{
										echo format_date($arr_value['start'])." -> &hellip;";
									}

								echo "</li>";
							}

						echo "</ul>";
					}
				}

			echo "</div>";

			$json_output['success'] = true;
			$json_output['html'] = ob_get_clean();

			header('Content-Type: application/json');
			echo json_encode($json_output);
			die();
		}

		function setting_base_cron_callback()
		{
			$setting_key = get_setting_key(__FUNCTION__);
			$option = get_option($setting_key, 'every_ten_minutes');

			$this->reschedule_base($option);

			if((!defined('DISABLE_WP_CRON') || DISABLE_WP_CRON == false))
			{
				echo show_select(array('data' => $this->get_schedules_for_select(), 'name' => 'setting_base_cron', 'value' => $option));
			}

			echo "<div class='api_base_cron'>".apply_filters('get_loading_animation', '')."</div>";
		}

		/*function setting_base_cron_debug_callback()
		{
			$setting_key = get_setting_key(__FUNCTION__);
			settings_save_site_wide($setting_key);
			$option = get_site_option_or_default($setting_key, get_option_or_default($setting_key, 'no'));

			list($option, $description) = setting_time_limit(array('key' => $setting_key, 'value' => $option, 'return' => 'array'));

			echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option, 'description' => $description));

			if($option == 'yes' && IS_SUPER_ADMIN)
			{
				$option_cron = get_option('cron');

				if(count($option_cron) > 0)
				{
					echo "<ul>";

						foreach($option_cron as $key => $arr_jobs)
						{
							foreach($arr_jobs as $key => $arr_job)
							{
								echo "<li>"
									.$key.": ";

									foreach($arr_job as $key => $arr_data)
									{
										foreach($arr_data as $key => $value)
										{
											switch($key)
											{
												case 'schedule':
												//case 'interval':
													echo $key." => ".$value;
												break;
											}
										}
									}

								echo "</li>";
							}
						}

					echo "</ul>";
				}
			}
		}*/

		function setting_base_update_htaccess_callback()
		{
			switch($this->get_server_type())
			{
				case 'apache':
					$setting_key = get_setting_key(__FUNCTION__);
					$option = get_option($setting_key, 'no');

					if(!is_multisite() || is_main_site())
					{
						$xtra = "";
						$description = "";

						if($option != 'yes')
						{
							$description = __("Make sure that you know what you are doing, and have full access to the server where the file is located, before activating this feature", 'lang_base');
						}

						echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option, 'xtra' => $xtra, 'description' => $description));
					}

					else
					{
						echo "<p><a href='".get_admin_url(get_main_site_id(), "options-general.php?page=".BASE_OPTIONS_PAGE)."'>".__("You can only change this setting on the main site", 'lang_base')."</a></p><br>";
					}

					$config = apply_filters('recommend_config', array('file' => ABSPATH.".htaccess", 'html' => '')); //get_home_path()

					if($config['html'] != '')
					{
						echo $config['html'];
					}
				break;

				case 'nginx':
					$config = apply_filters('recommend_config', array('html' => ''));

					if($config['html'] != '')
					{
						echo $config['html'];
					}
				break;

				default:
					$config = apply_filters('recommend_config', array('file' => ABSPATH.".htaccess", 'html' => '')); //get_home_path()

					if($config['html'] != '')
					{
						echo $config['html'];
					}
				break;
			}
		}

		function setting_base_prefer_www_callback()
		{
			$setting_key = get_setting_key(__FUNCTION__);
			settings_save_site_wide($setting_key);
			$option = get_site_option_or_default($setting_key, get_option_or_default($setting_key));

			echo show_select(array('data' => get_yes_no_for_select(array('add_choose_here' => true)), 'name' => $setting_key, 'value' => $option));
		}

		function setting_base_use_timezone_callback()
		{
			$setting_key = get_setting_key(__FUNCTION__);
			settings_save_site_wide($setting_key);
			$option = get_site_option_or_default($setting_key, 'no');
 
			echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option));
		}

		function setting_base_optimize_callback()
		{
			$option_base_optimized = get_option('option_base_optimized');

			if($option_base_optimized > DEFAULT_DATE)
			{
				$populate_next = format_date(date("Y-m-d H:i:s", strtotime($option_base_optimized." +4 hour")));

				$description = sprintf(__("The optimization was last run %s and will be run again %s", 'lang_base'), format_date($option_base_optimized), $populate_next);
			}

			else
			{
				$description = sprintf(__("The optimization has not been run yet but will be %s", 'lang_base'), get_next_cron());
			}

			echo "<div class='form_button'>"
				.show_button(array('type' => 'button', 'name' => 'btnBaseOptimize', 'text' => __("Optimize Now", 'lang_base'), 'class' => 'button-secondary'))
				."<p class='italic'>".$description."</p>"
			."</div>
			<div class='api_base_optimize'></div>";
		}

		function setting_base_recommend_callback()
		{
			$arr_recommendations = array(
				array("Enable Media Replace", 'enable-media-replace/enable-media-replace.php', __("to replace existing files by uploading a replacement", 'lang_base')),
				array("Modern Image Formats", 'webp-uploads/load.php', __("to convert images to modern formats when uploaded", 'lang_base')),
				//array("Postie", 'postie/postie.php', __("to create posts by sending an e-mail", 'lang_base')),
				//array("Post Notification by Email", 'notify-users-e-mail/notify-users-e-mail.php', __("to send notifications to users when new posts are published", 'lang_base')),
				//array("Quick Page/Post Redirect Plugin", 'quick-pagepost-redirect-plugin/page_post_redirect_plugin.php', __("to redirect pages to internal or external URLs", 'lang_base')),
				array("Search & Replace", 'search-and-replace/inpsyde-search-replace.php', __("to search & replace text in the database", 'lang_base')),
				array("Simple Page Ordering", 'simple-page-ordering/simple-page-ordering.php', __("to reorder posts with drag and drop", 'lang_base')),
				array("Sucuri", 'sucuri-scanner/sucuri.php', __("to add security measures and the possibility to scan for vulnerabilities", 'lang_base')),
				//array("TablePress", 'tablepress/tablepress.php', __("to add tables to posts", 'lang_base')),
				//array("Tuxedo Big File Uploads", 'tuxedo-big-file-uploads/tuxedo_big_file_uploads.php', __("to upload larger files than normally allowed", 'lang_base')),
				//array("Username Changer", 'username-changer/username-changer.php', __("to change usernames", 'lang_base')),
			);

			foreach($arr_recommendations as $value)
			{
				$name = $value[0];
				$path = $value[1];
				$text = (isset($value[2]) ? $value[2] : '');

				new recommend_plugin(array('path' => $path, 'name' => $name, 'text' => $text, 'show_notice' => false));
			}
		}

	function admin_init()
	{
		global $pagenow;

		$this->wp_head(array('type' => 'admin'));

		$plugin_include_url = plugin_dir_url(__FILE__);

		mf_enqueue_style('style_base_wp', $plugin_include_url."style_wp.css");
		wp_enqueue_script('jquery-ui-autocomplete');
		mf_enqueue_script('script_base_wp', $plugin_include_url."script_wp.js", array(
			//'plugins_url' => plugins_url(),
			'ajax_url' => admin_url('admin-ajax.php'),
			'toggle_all_data_text' => __("Toggle All Data", 'lang_base'),
		));

		if($pagenow == 'options-general.php' && check_var('page') == BASE_OPTIONS_PAGE)
		{
			mf_enqueue_style('style_base_settings', $plugin_include_url."style_settings.css");
			mf_enqueue_script('script_base_settings', $plugin_include_url."script_settings.js", array(
				'default_tab' => "settings_base",
				'settings_page' => true,
				'ajax_url' => admin_url('admin-ajax.php'),
				'user_id' => get_current_user_id(),
				'loading_animation' => apply_filters('get_loading_animation', ''),
			));
		}
	}

	function admin_menu()
	{
		if(apply_filters('has_comments', true) == false)
		{
			remove_menu_page("edit-comments.php");
		}
	}

	function pre_set_site_transient_update_plugins($transient = [])
	{
		if(empty($transient->checked))
		{
			return $transient;
		}

		foreach(get_plugins() as $key => $arr_value)
		{
			if(isset($arr_value['PluginURI']) && $arr_value['PluginURI'] != '' && strpos($arr_value['PluginURI'], "https://github.com") !== false)
			{
				$this->github_debug = "";

				$github_repo = trim(str_replace("https://github.com/", "", $arr_value['PluginURI']), "/");
				$github_api_url = "https://raw.githubusercontent.com/".$github_repo."/master/index.php";

				list($content, $headers) = get_url_content(array(
					'url' => $github_api_url,
					'catch_head' => true,
				));

				switch($headers['http_code'])
				{
					case 200:
						$latest_version = "";

						if(preg_match('/Version\s*:\s*([^\s]+)/i', $content, $matches))
						{
							$latest_version = $matches[1];
						}

						else
						{
							$this->github_debug .= ($this->github_debug != '' ? "," : "").__FUNCTION__.": version does not exist (".$github_api_url." -> ".htmlspecialchars($content).")";
						}

						if($latest_version != '')
						{
							if(version_compare($arr_value['Version'], $latest_version, '<'))
							{
								list($plugin_dir, $plugin_file) = explode("/", $key);

								$plugin_data = array(
									'slug' => $plugin_dir,
									'plugin' => $key,
									'new_version' => $latest_version,
									'url' => $arr_value['PluginURI'],
									'package' => "https://github.com/".$github_repo."/archive/refs/heads/master.zip",
									'icons' => array(
										'svg' => "https://raw.githubusercontent.com/".$github_repo."/master/assets/icon.svg",
									),
								);

								//do_log(__FUNCTION__." - Added: ".$key." v".$latest_version." to transient since it was newer than v".$arr_value['Version']." (".var_export($plugin_data, true).")");

								$transient->response[$key] = (object)$plugin_data;
							}
						}
					break;

					default:
						$this->github_debug .= ($this->github_debug != '' ? "," : "").__FUNCTION__." - Error fetching data: ".$content;
					break;
				}

				if($this->github_debug != '')
				{
					//do_log(__FUNCTION__.": ".$this->github_debug, 'publish', false);
				}
			}
		}

		//do_log(__FUNCTION__.": ".var_export($transient, true));

		return $transient;
	}

	function upgrader_process_complete($upgrader_object, $hook_extra)
	{
		$arr_branches = array('master', 'main');

		if(isset($hook_extra['action'], $hook_extra['type']) && $hook_extra['action'] === 'update' && $hook_extra['type'] === 'plugin' && !empty($hook_extra['plugins'])
		)
		{
			$plugins_dir = WP_PLUGIN_DIR;

			foreach($hook_extra['plugins'] as $plugin_file)
			{
				$plugin_folder = dirname($plugin_file);

				foreach($arr_branches as $branch)
				{
					$old_path = $plugins_dir."/".$plugin_folder."-".$branch;
					$new_path = $plugins_dir."/".$plugin_folder;

					if(is_dir($old_path) && !is_dir($new_path))
					{
						rename($old_path, $new_path);
						break;
					}
				}
			}
		}
	}

	function plugin_action_links($arr_actions, $plugin_file)
	{
		if(!IS_SUPER_ADMIN && is_array($arr_actions) && array_key_exists('deactivate', $arr_actions) && in_array($plugin_file, array('mf_base/index.php')))
		{
			unset($arr_actions['deactivate']);
		}

		return $arr_actions;
	}

	function get_loading_animation($html, $args = [])
	{
		if(!isset($args['class'])){		$args['class'] = "fa-2x";}
		if(!isset($args['style'])){		$args['style'] = "";}

		if($html == '')
		{
			$html = "<i class='fa fa-spinner fa-spin loading_animation".($args['class'] != '' ? " ".$args['class'] : "")."'".($args['style'] != '' ? " style='".$args['style']."'" : "")."></i>";
		}

		return $html;
	}

	function get_image_fallback()
	{
		$plugin_include_url = plugin_dir_url(__FILE__);

		return "<img src='".$plugin_include_url."images/blank.svg' class='image_fallback' alt='".__("Generic image as a placeholder", 'lang_base')."'>";
	}

	function get_page_from_block_code($arr_ids, $block_code)
	{
		global $wpdb;

		$result = $wpdb->get_results($wpdb->prepare("SELECT ID, post_type FROM ".$wpdb->posts." WHERE post_status = %s AND post_content LIKE %s", 'publish', "%".$block_code."%"));

		foreach($result as $r)
		{
			$post_id = $r->ID;
			$post_type = $r->post_type;

			if($post_type == 'wp_block')
			{
				$block_code = '<!-- wp:block {"ref":'.$post_id.'} /-->';
				$arr_ids = apply_filters('get_page_from_block_code', $arr_ids, $block_code);
			}

			else
			{
				$arr_ids[] = $post_id;
			}
		}

		return $arr_ids;
	}

	function get_styles_content($out, $type)
	{
		global $wpdb;

		$out = "";

		$theme_slug = get_stylesheet();

		$styles_content = $wpdb->get_var($wpdb->prepare("SELECT post_content FROM ".$wpdb->posts." WHERE post_type = %s AND post_name = %s AND post_status = %s", 'wp_global_styles', 'wp-global-styles-'.$theme_slug, 'publish'));

		if(is_string($styles_content))
		{
			$arr_json_styles = json_decode($styles_content, true);

			switch($type)
			{
				case 'max_width':
					if(isset($arr_json_styles['settings']['layout']['wideSize']))
					{
						$out = $arr_json_styles['settings']['layout']['wideSize'];
					}
				break;

				default:
					do_log(__FUNCTION__.": Type not targeted yet (".$type.")");
				break;
			}
		}

		return $out;

		/*array ( 
			'styles' => array ( 
				'elements' => array ( 
					'heading' => array ( 
						'typography' => array ( 
							'fontStyle' => 'normal', 
							'fontWeight' => '400', 
							'fontFamily' => 'var(--wp--preset--font-family--kalam)', 
						), 
						'color' => array ( 
							'text' => '#1e73be', 
						), 
					), 
					'button' => array ( 
						'color' => array ( 
							'text' => 'var(--wp--preset--color--base)', 
							'background' => '#1e73be', 
						), 
					), 
					'link' => array ( 
						'typography' => array ( 'textDecoration' => 'none', ),
						'color' => array ( 'text' => '#1e73be', ), 
					), 
				), 
				'typography' => array ( 
					'fontStyle' => 'normal', 
					'fontWeight' => '300', 
					'fontSize' => 'var(--wp--preset--font-size--medium)', 
				), 
				'blocks' => array ( 
					'core/button' => array ( 
						'border' => array ( 'radius' => '0.33em', ), 
						'spacing' => array ( 
							'padding' => array ( 
								'left' => '1em', 
								'right' => '1em', 
								'top' => '0.6em', 
								'bottom' => '0.6em', 
							), 
						), 
					), 
					'core/paragraph' => array ( 
						'spacing' => array ( 
							'padding' => array ( 'bottom' => 'var:preset|spacing|20', ), 
						), 
					), 
				), 
				'css' => [CSS], 
				'spacing' => array ( 
					'blockGap' => '0rem', 
				), 
			), 
			'settings' => array ( 
				'layout' => array ( 
					'contentSize' => '1200px', 
					'wideSize' => '1200px', 
				), 
				'typography' => array ( 
					'fontFamilies' => array ( 
						'theme' => array ( 
							0 => array ( 
								'name' => 'Manrope', 
								'slug' => 'manrope', 
								'fontFamily' => 'Manrope, sans-serif', 
								'fontFace' => array ( 
									0 => array ( 
										'src' => array ( 
											0 => 'file:./assets/fonts/manrope/Manrope-VariableFont_wght.woff2',
										),
										'fontWeight' => '200 800',
										'fontStyle' => 'normal',
										'fontFamily' => 'Manrope',
									),
								),
							),
						),
						'custom' => array ( 
							0 => array ( 
								'name' => 'Kalam',
								'slug' => 'kalam',
								'fontFamily' => 'Kalam,
								cursive', 'fontFace' => array (
									0 => array (
										'src' => [url],
										'fontWeight' => '300',
										'fontStyle' => 'normal',
										'fontFamily' => 'Kalam',
									),
								),
							),
						),
					),
				),
			),
			'isGlobalStylesUserThemeJSON' => true,
			'version' => 3,
		)*/
	}

	function get_layout_breakpoints()
	{
		$arr_out = [
			'tablet' => 1200,
			'suffix' => "px",
		];

		$max_width = apply_filters('get_styles_content', '', 'max_width');

		if($max_width != '')
		{
			preg_match('/^([0-9]*\.?[0-9]+)([a-zA-Z%]+)$/', $max_width, $matches);

			$arr_out['tablet'] = $matches[1];
			$arr_out['suffix'] = $matches[2];
		}

		$arr_out['mobile'] = ($arr_out['tablet'] * .775);

		return $arr_out;
	}

	function column_header($columns)
	{
		unset($columns['date']);

		if(apply_filters('has_comments', true) == false)
		{
			unset($columns['comments']);
		}

		if(check_var('post_status') != 'trash')
		{
			$columns['page_index'] = __("Index", 'lang_base');
		}

		return $columns;
	}

	function column_cell($column, $post_id)
	{
		global $wpdb, $post;

		switch($column)
		{
			case 'page_index':
				$index_type = apply_filters('filter_theme_core_seo_type', '');

				if($index_type == '' && $post->post_status != 'publish')
				{
					$index_type = 'not_published';
				}

				if($index_type == '')
				{
					$page_index = get_post_meta($post_id, $this->meta_prefix.$column, true);

					if(in_array($page_index, array('noindex', 'none', 'no')))
					{
						$index_type = 'not_indexed';
					}
				}

				if($index_type == '' && $this->is_post_password_protected($post_id))
				{
					$index_type = 'password_protected';
				}

				switch($index_type)
				{
					case 'password_protected':
						echo "<i class='fa fa-lock fa-lg grey' title='".__("The page is password protected", 'lang_base')."'></i>";
					break;

					case 'not_published':
						echo "<i class='fa fa-eye-slash fa-lg grey' title='".__("The page is not published", 'lang_base')."'></i>";
					break;

					case 'not_indexed':
						echo "<i class='fa fa-eye-slash fa-lg grey' title='".__("The page is not indexed", 'lang_base')."'></i>";
					break;

					default:
						$post_excerpt = get_the_excerpt();

						if($post_excerpt != '')
						{
							echo "<i class='fa fa-check fa-lg green' title='".__("The page is published and indexed", 'lang_base')."'></i>";
						}

						else
						{
							echo "<i class='fa fa-check fa-lg blue' title='".__("The page has no description", 'lang_base')."'></i>";
						}
					break;
				}
			break;
		}
	}

	function rwmb_meta_boxes($meta_boxes)
	{
		if(IS_ADMINISTRATOR)
		{
			$arr_post_types_for_metabox = $this->get_post_types_for_metabox();

			$meta_boxes[] = array(
				'id' => $this->meta_prefix.'settings',
				'title' => __("Settings", 'lang_base'),
				'post_types' => $arr_post_types_for_metabox,
				'context' => 'side',
				'priority' => 'low',
				'fields' => array(
					array(
						'name' => __("Index", 'lang_base'),
						'id' => $this->meta_prefix.'page_index',
						'type' => 'select',
						'options' => array(
							'' => __("Yes", 'lang_base'),
							'no' => __("No", 'lang_base'),
						), //array('' => "-- ".__("Choose Here", 'lang_base')." --", 'noindex' => __("Do not Index", 'lang_base'), 'nofollow' => __("Do not Follow Links", 'lang_base'), 'none' => __("Do not Index and do not follow links", 'lang_base'))
					),
				),
			);
		}

		return $meta_boxes;
	}

	function rwmb_enqueue_scripts()
	{
		$plugin_include_url = plugin_dir_url(__FILE__);

		mf_enqueue_script('script_base_meta', $plugin_include_url."script_meta.js");
	}

	function api_base_notifications()
	{
		$array = apply_filters('get_user_notifications', []);

		$json_output = array(
			'success' => true,
			'notifications' => $array,
		);

		header("Content-Type: application/json");
		echo json_encode($json_output);
		die();
	}

	function api_base_optimize()
	{
		global $done_text, $error_text;

		$json_output = array(
			'success' => false,
		);

		$done_text = $this->do_optimize();

		if($done_text != '')
		{
			$json_output['success'] = true;
		}

		$json_output['html'] = get_notification();

		header('Content-Type: application/json');
		echo json_encode($json_output);
		die();
	}

	function is_post_password_protected($post_id = 0)
	{
		$out = false;

		if(!is_user_logged_in())
		{
			if($out == false)
			{
				if($post_id > 0)
				{
					$out = post_password_required($post_id);
				}

				else
				{
					$out = post_password_required();
				}
			}

			if($out == false)
			{
				if($post_id == 0)
				{
					global $post;

					if(isset($post->ID))
					{
						$post_id = $post->ID;
					}
				}

				$out = apply_filters('filter_is_password_protected', $out, array('post_id' => $post_id, 'check_login' => true, 'type' => 'bool'));
			}
		}

		return $out;
	}

	function has_noindex($post_id)
	{
		$page_index = get_post_meta($post_id, $this->meta_prefix.'page_index', true);

		return in_array($page_index, array('noindex', 'none', 'no'));
	}

	function get_public_post_types($data = [])
	{
		if(!isset($data['allow_password_protected'])){	$data['allow_password_protected'] = false;}

		$this->arr_post_types = [];

		foreach(get_post_types(array('public' => true, 'exclude_from_search' => false), 'names') as $post_type)
		{
			if($post_type != 'attachment')
			{
				$data_temp = array(
					'post_type' => $post_type,
				);

				if($data['allow_password_protected'] == false)
				{
					$data_temp['where'] = "post_password = ''";
				}

				get_post_children($data_temp, $this->arr_post_types);
			}
		}
	}

	function get_public_posts($data = [])
	{
		if(!isset($data['allow_noindex'])){				$data['allow_noindex'] = false;}
		if(!isset($data['allow_password_protected'])){	$data['allow_password_protected'] = false;}

		$this->arr_public_posts = [];

		if(count($this->arr_post_types) == 0)
		{
			$this->get_public_post_types(array('allow_password_protected' => $data['allow_password_protected']));
		}

		foreach($this->arr_post_types as $post_id => $post_title)
		{
			if($data['allow_noindex'] == false && $this->has_noindex($post_id) || $data['allow_password_protected'] == false && $this->is_post_password_protected($post_id))
			{
				// Do nothing
			}

			else
			{
				$this->arr_public_posts[$post_id] = $post_title;
			}
		}
	}

	function rest_authentication_errors($result)
	{
		if(!is_user_logged_in())
		{
			return new WP_Error('rest_forbidden', __("You cannot access the REST API without logging in.", 'lang_base'), array('status' => 401));
		}

		return $result;
	}

	function wp_sitemaps_posts_query_args($args, $post_type)
	{
		if(!isset($args['post__not_in'])){	$args['post__not_in'] = [];}

		$this->get_public_posts(array('allow_noindex' => true, 'allow_password_protected' => true));

		foreach($this->arr_public_posts as $post_id => $post_title)
		{
			if($this->has_noindex($post_id) || $this->is_post_password_protected($post_id))
			{
				$args['post__not_in'][] = $post_id;
			}
		}

		return $args;
	}

	function wp_sitemaps_taxonomies($taxonomies)
	{
		unset($taxonomies['category']);

        return $taxonomies;
    }

	function robots_txt()
	{
		if(get_option('blog_public'))
		{
			$file = ABSPATH.'robots.txt';

			if(file_exists($file))
			{
				unlink($file);
			}

			echo "Sitemap: ".home_url('/wp-sitemap.xml');
		}
	}

	function login_init()
	{
		$this->wp_head(array('type' => 'login'));
	}

	function load_font_awesome($data)
	{
		if(!wp_style_is('font-awesome') && !wp_style_is('font-awesome-5'))
		{
			// Cannot modify header information - headers already sent by (output started at wp-content/plugins/mf_base/include/classes.php:1987) in wp-admin/includes/misc.php on line 1431
			/*$plugin_fonts_url = str_replace("/include/", "/", $data['plugin_include_url']);

			echo "<link rel='preload' as='font' type='font/woff2' href='".$plugin_fonts_url."fonts/fa-brands-400.woff2' crossorigin>
			<link rel='preload' as='font' type='font/woff2' href='".$plugin_fonts_url."fonts/fa-regular-400.woff2' crossorigin>
			<link rel='preload' as='font' type='font/woff2' href='".$plugin_fonts_url."fonts/fa-solid-900.woff2' crossorigin>";*/

			mf_enqueue_style('font-awesome-5', $data['plugin_include_url']."font-awesome-5.15.4.php"); //, $data['plugin_version']
		}
	}

	function wp_head($data = [])
	{
		if(!is_array($data)){			$data = [];}
		if(!isset($data['type'])){		$data['type'] = 'public';}

		$plugin_include_url = plugin_dir_url(__FILE__);

		mf_enqueue_style('style_base', $plugin_include_url."style.php");

		$data_temp = $data;
		$data_temp['plugin_include_url'] = $plugin_include_url;
		$this->load_font_awesome($data_temp);

		mf_enqueue_script('script_base', $plugin_include_url."script.js", array(
			'confirm_question' => __("Are you sure?", 'lang_base'),
			'characters_left_text' => __("characters left", 'lang_base'),
		));

		if($data['type'] == 'public')
		{
			global $post;

			if(wp_is_block_theme())
			{
				wp_enqueue_style('wp-block-buttons');
				wp_enqueue_style('wp-block-button');
			}

			if(isset($post) && $post->ID > 0)
			{
				$page_index = get_post_meta($post->ID, $this->meta_prefix.'page_index', true);

				switch($page_index)
				{
					case 'nofollow':
					case 'noindex':
					case 'none':
					case 'no':
						echo "<meta name='robots' content='noindex, nofollow'/>";
					break;
				}

				$post_excerpt = get_the_excerpt();

				if($post_excerpt != '')
				{
					echo "<meta name='description' content='".esc_attr($post_excerpt)."'/>";
				}
			}
		}
	}

	function block_title($title)
	{
		if($title != '' && strpos($title, "[name]") !== false && is_user_logged_in())
		{
			$user_data = get_userdata(get_current_user_id());

			$title = str_replace("[name]", $user_data->first_name, $title);
		}

		return $title;
	}

	function phpmailer_init($phpmailer)
	{
		if($phpmailer->FromName == "WordPress")
		{
			$phpmailer->From = get_bloginfo('admin_email');
			$phpmailer->FromName = get_bloginfo('name');
		}

		if($phpmailer->ContentType == 'text/html')
		{
			$phpmailer->IsHTML(true);

			$arr_preferred_content_types = apply_filters('get_preferred_content_types', [], $phpmailer->From);

			if(!is_array($arr_preferred_content_types) || count($arr_preferred_content_types) == 0 || in_array('plain', $arr_preferred_content_types))
			{
				$phpmailer->AltBody = strip_tags($phpmailer->Body);
			}
		}
	}

	function theme_page_templates($posts_templates)
	{
		/*if(count($this->templates) == 0)
		{
			$this->templates = apply_filters('get_page_templates', []);
		}*/

		$posts_templates = array_merge($posts_templates, $this->templates);

		return $posts_templates;
	}

	function wp_insert_post_data($data)
	{
		/*if(count($this->templates) == 0)
		{
			$this->templates = apply_filters('get_page_templates', []);
		}*/

		// Create the key used for the themes cache
		$cache_key = "page_templates-".md5(get_theme_root()."/".get_stylesheet());

		// Retrieve the cache list. If it doesn't exist, or it's empty prepare an array
		$templates = wp_get_theme()->get_page_templates();

		if(empty($templates))
		{
			$templates = [];
		}

		// New cache, therefore remove the old one
		wp_cache_delete($cache_key , 'themes');

		// Now add our template to the list of templates by merging our templates with the existing templates array from the cache.
		$templates = array_merge($templates, $this->templates);

		// Add the modified cache to allow WordPress to pick it up for listing available templates
		wp_cache_add($cache_key, $templates, 'themes', 1800);

		return $data;
	}

	function template_include($template)
	{
		global $post;

		if(!$post)
		{
			return $template;
		}

		/*if(count($this->templates) == 0)
		{
			$this->templates = apply_filters('get_page_templates', []);
		}*/

		$template_temp = get_post_meta($post->ID, '_wp_page_template', true);

		// Return default template if we don't have a custom one defined
		if(!isset($this->templates[$template_temp]))
		{
			return $template;
		}

		$file = WP_CONTENT_DIR.$template_temp;

		if(file_exists($file))
		{
			return $file;
		}

		else
		{
			do_log("The template ".$file." does not exist for the post to use (".var_export($post, true).")");

			echo $file;
		}

		return $template;
	}

	function recommend_config($data)
	{
		if(!isset($data['file'])){		$data['file'] = '';}

		$update_with = "";

		if(!is_multisite() || is_main_site())
		{
			$subfolder = get_url_part(array('type' => 'path'));

			$ignore_files = "xmlrpc\.php|license\.txt|readme\.html|wp\-config\.php|wp\-config\-sample\.php|debug\.log";

			switch($this->get_server_type())
			{
				default:
				case 'apache':
					/*$update_with = "<Files xmlrpc.php>\r\n"
					."	Require all denied\r\n"
					."</Files>";*/

					$update_with = "<FilesMatch \"^(".$ignore_files.")$\">\r\n" //|wp\-content/uploads/[\d]+/.*\.php
					."	Require all denied\r\n"
					."</FilesMatch>\r\n";

					/*$update_with .= "<IfModule mod_rewrite.c>\r\n"
					."	RewriteEngine On\r\n"
					."	RewriteCond %{REQUEST_URI} ^/?(xmlrpc\.php)$\r\n"
					."	RewriteRule .* /404/ [L,NC]\r\n"
					."</IfModule>\r\n";*/

					$update_with .= "ServerSignature Off\r\n"
					."DirectoryIndex index.php\r\n"
					."Options -Indexes\r\n"
					."\r\n"
					."Header set X-XSS-Protection \"1; mode=block\"\r\n"
					."Header set X-Content-Type-Options nosniff\r\n"
					."Header set X-Powered-By \"Me\"\r\n"
					."\r\n"
					."<IfModule mod_headers.c>\r\n"
					."	Header always set Referrer-Policy \"same-origin\"\r\n"
					."</IfModule>\r\n"
					."\r\n"
					."<IfModule mod_rewrite.c>\r\n"
					."	RewriteEngine On\r\n"
					."	RewriteBase /\r\n";

					$update_with .= "\r\n"
					."	RewriteCond %{REQUEST_METHOD} ^TRACE\r\n"
					."	RewriteRule .* - [F]\r\n";

					switch(get_site_option('setting_base_prefer_www'))
					{
						case 'yes':
							$update_with .= "\r\n"
							."	RewriteCond %{HTTPS} on\r\n"
							."	RewriteCond %{HTTP_HOST} !^www\.(.*)$ [NC]\r\n"
							."	RewriteRule ^(.*)$ https://www.%{HTTP_HOST}/$1 [R=301,L]\r\n";
						break;

						case 'no':
							$update_with .= "\r\n"
							."	RewriteCond %{HTTPS} on\r\n"
							."	RewriteCond %{HTTP_HOST} ^www\.(.*)$ [NC]\r\n"
							."	RewriteRule ^(.*)$ https://%1/$1 [R=301,L]\r\n";
						break;
					}

					$update_with .= "\r\n"
					."	RewriteRule ^my_ip$ ".$subfolder."wp-content/plugins/mf_base/include/api/?type=my_ip [L]\r\n"
					."\r\n";

					/*$update_with .= "	RewriteCond %{REQUEST_URI} ^/?(license\.txt|readme\.html|wp\-config\.php|wp\-config\-sample\.php|wp\-content/debug\.log|wp\-content/uploads/[\d]+/.*\.php)$\r\n"
					."	RewriteRule .* /404/ [L,NC]\r\n"
					."\r\n";*/

					// Disable execution of PHP files in wp-includes
					if(!is_multisite())
					{
						$update_with .= "	RewriteRule ^wp-admin/includes/ - [F,L]\r\n";
					}

					$update_with .= "	RewriteRule !^wp-includes/ - [S=3]\r\n"
					."	RewriteRule ^wp-includes/[^/]+\.php$ - [F,L]\r\n"
					."	RewriteRule ^wp-includes/js/tinymce/langs/.+\.php - [F,L]\r\n"
					."	RewriteRule ^wp-includes/theme-compat/ - [F,L]\r\n"
					."</IfModule>\r\n";

					switch(php_sapi_name())
					{
						case 'apache2handler':
						case 'litespeed':
							if($this->memory_limit_current <= $this->memory_limit)
							{
								$update_with .= "\r\nphp_value memory_limit ".$this->memory_limit_base."M";
							}

							if($this->upload_max_filesize_current <= $this->upload_max_filesize)
							{
								$update_with .= "\r\nphp_value upload_max_filesize ".$this->upload_max_filesize_base."M";
							}

							if($this->post_max_size_current <= $this->post_max_size)
							{
								$update_with .= "\r\nphp_value post_max_size ".$this->post_max_size_base."M";
							}
						break;

						case 'fpm-fcgi':
						case 'cgi-fcgi':
							// Do nothing. It will cause 500 Server Error
						break;

						default:
							do_log("The server is running '".php_sapi_name()."' and might allow upload_max_filesize etc. in .htaccess");
						break;
					}
				break;

				case 'nginx':
					$update_with .= "location ~* ^/(".$ignore_files.")$ {\r\n"
					."	deny all;\r\n"
					."}\r\n"
					."\r\n"
					."index \"index.php\";\r\n"
					."autoindex off;\r\n"
					."server_tokens off;\r\n"
					."\r\n"
					."location = /my_ip {\r\n"
					."	rewrite ^(.*)$ ".$subfolder."wp-content/plugins/mf_base/include/api/?type=my_ip break;\r\n"
					."}";
				break;
			}
		}

		$data['html'] .= $this->update_config(array(
			'plugin_name' => "MF Base",
			'file' => $data['file'],
			'update_with' => $update_with,
			'auto_update' => true,
		));

		return $data;
	}

	function get_block_search($post_id, $handle = '')
	{
		global $wpdb;

		if($handle == '') // Previous version was get_block_search($handle)
		{
			$handle = $post_id;
			$post_id = 0;
		}

		$query_where = $query_order = "";

		if($post_id > 0)
		{
			$query_where .= " AND ID = '".esc_sql($post_id)."'";
		}

		else
		{
			$query_order = " ORDER BY post_modified DESC";
		}

		$result = $wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE (post_type = %s".$query_where." OR post_type IN ('wp_template', 'wp_template_part')) AND post_status = %s".$query_order, 'page', 'publish'));

		foreach($result as $r)
		{
			if(has_block($handle, get_post($r->ID)))
			{
				$post_id = $r->ID;
				break;
			}
		}

		return $post_id;
	}

	/* Form */
	############################
	function init_form($data)
	{
		$this->data = $data;
	}

	function is_multiple()
	{
		return substr($this->data['name'], -2) == "[]";
	}

	function get_hidden_field()
	{
		$out = "";

		foreach($this->data['data'] as $key => $option)
		{
			if($key != '')
			{
				$out = input_hidden(array('name' => $this->data['name'], 'value' => $key));

				break;
			}
		}

		return $out;
	}

	function get_field_suffix()
	{
		if($this->data['suffix'] != '')
		{
			return "<span class='description'>".$this->data['suffix']."</span>";
		}
	}

	function get_field_description()
	{
		if($this->data['description'] != '')
		{
			return "<p class='description'>".$this->data['description']."</p>";
		}
	}
	############################

	/* .htaccess */
	############################
	function update_config($data)
	{
		global $done_text, $error_text;

		if(!isset($data['file'])){			$data['file'] = false;}
		if(!isset($data['auto_update'])){	$data['auto_update'] = false;}

		$data['update_with'] = trim($data['update_with']);

		$out = $content = "";

		if($data['file'] != '' && file_exists($data['file']))
		{
			$content = get_file_content(array('file' => $data['file']));
		}

		$new_md5 = ($data['update_with'] != '' ? md5($data['update_with']) : '');

		if(preg_match("/BEGIN ".$data['plugin_name']." \(".$new_md5."\)/is", $content)) // If there are multiple "BEGIN [plugin_name] (md5)" due to differences depending on subsite
		{
			$old_md5 = $new_md5;
		}

		else
		{
			$old_md5 = get_match("/BEGIN ".$data['plugin_name']." \((.*?)\)/is", $content, false);
		}

		if($new_md5 != $old_md5)
		{
			$old_content = get_match("/(\# BEGIN ".$data['plugin_name']."(.*)\# END ".$data['plugin_name'].")/is", $content, false);
			$new_content = "";

			if($data['update_with'] != '')
			{
				$new_content = "# BEGIN ".$data['plugin_name']." (".$new_md5.")\r\n".$data['update_with']."\r\n# END ".$data['plugin_name'];
			}

			if($old_content != '')
			{
				$content = str_replace($old_content, $new_content, $content);
			}

			else if($new_content != '')
			{
				$content = $new_content."\r\n\r\n".$content;
			}

			$success = false;

			if($data['file'] != '' && $data['auto_update'] == true && get_option('setting_base_update_htaccess', 'no') == 'yes' && (!is_multisite() || is_main_site()))
			{
				$file_temp = $data['file']."_temp";
				$content = trim($content);

				$success = file_put_contents($file_temp, $content);

				if($success > 0 && file_exists($file_temp) && $success == strlen($content))
				{
					if(copy($file_temp, $data['file']))
					{
						$done_text = sprintf(__("I successfully updated %s with %s", 'lang_base'), ".htaccess", $data['plugin_name']);
						//$done_text .= " (".ABSPATH.")";
						//$done_text .= " (".$new_md5." != ".$old_md5.")";
						//$done_text .= " (".$old_content." -> ".$new_content." -> ".$content.")";

						$out .= get_notification();
					}

					else
					{
						$error_text = sprintf(__("I could not update %s with %s from the temp file", 'lang_base'), $data['file'], $data['plugin_name']);

						$out .= get_notification();
					}
				}

				else
				{
					$error_text = sprintf(__("I could not successfully update %s with %s", 'lang_base'), $data['file'], $data['plugin_name']);
					//$error_text .= " (".ABSPATH.")";
					//$error_text .= " (".$success." != ".strlen($content).")";

					$out .= get_notification();
				}

				if(file_exists($file_temp))
				{
					unlink($file_temp);
				}
			}

			if($success == false && $data['update_with'] != '')
			{
				$new_content = "# BEGIN ".$data['plugin_name']." (".$new_md5.")\r\n"
					.htmlspecialchars($data['update_with'])."\r\n"
				."# END ".$data['plugin_name'];

				switch($this->get_server_type())
				{
					case 'apache':
						$config_file = ".htaccess";
					break;

					case 'nginx':
						$config_file = "nginx.conf";
					break;

					case 'iis':
						$config_file = "web.config";
					break;
				}

				$out .= "<div class='mf_form'>"
					."<h3 class='display_warning'>
						<i class='fa fa-exclamation-triangle yellow'></i> "
						.sprintf(__("Add this to the beginning of %s", 'lang_base'), $config_file)
					."</h3>"
					."<p class='input'>".nl2br($new_content)."</p>"
				."</div>";
			}
		}

		return $out;
	}
	############################

	function get_templates($arr_type = [])
	{
		$out = "";

		if(in_array('lost_connection', $arr_type) && $this->template_lost_connection == false)
		{
			$plugin_include_url = plugin_dir_url(__FILE__);

			mf_enqueue_style('style_base_overlay', $plugin_include_url."style_overlay.css");
			mf_enqueue_script('script_base_overlay', $plugin_include_url."script_overlay.js");

			$out .= "<div id='overlay_lost_connection' class='overlay_container hide'><div>".__("Lost Connection", 'lang_base')."</div></div>";

			$this->template_lost_connection = true;
		}

		if(in_array('loading', $arr_type) && $this->template_loading == false)
		{
			$plugin_include_url = plugin_dir_url(__FILE__);

			mf_enqueue_style('style_base_overlay', $plugin_include_url."style_overlay.css");
			mf_enqueue_script('script_base_overlay', $plugin_include_url."script_overlay.js");

			$out .= "<div id='overlay_loading' class='overlay_container hide'><div>".apply_filters('get_loading_animation', '')."</div></div>";

			$this->template_loading = true;
		}

		return $out;
	}
}

class mf_cron
{
	var $schedules;
	var $type = "";
	var $date_start;
	var $upload_path;
	var $file = "";
	var $is_running = "";

	function __construct()
	{
		$this->schedules = wp_get_schedules();

		$this->date_start = date("Y-m-d H:i:s");

		list($this->upload_path, $upload_url) = get_uploads_folder(__CLASS__);
	}

	function start($type)
	{
		global $wpdb;

		$this->type = $type;

		/*if(get_site_option('setting_base_cron_debug') == 'yes')
		{
			do_log("Cron: ".$this->type." started ".$this->date_start);
		}*/

		$this->file = $this->upload_path.".is_running_".$wpdb->prefix.trim($this->type, "_");

		$this->set_is_running();

		if($this->is_running == false)
		{
			if($this->type == 'mf_base_parent')
			{
				$arr_progress = [];
			}

			else
			{
				$arr_progress = get_option('option_cron_progress');
			}

			$arr_progress[$this->type] = array('start' => date("Y-m-d H:i:s"), 'end' => "");

			update_option('option_cron_progress', $arr_progress, false);
		}

		$success = set_file_content(array('file' => $this->file, 'mode' => 'w', 'log' => false, 'content' => date("Y-m-d H:i:s")));

		if(!$success)
		{
			do_log(sprintf("I could not create the temporary file in %s, please make sure that I have access to create this file in order for schedules to work as intended", $this->upload_path));
		}
	}

	function get_interval()
	{
		$setting_base_cron = get_option_or_default('setting_base_cron', 'hourly');

		return $this->schedules[$setting_base_cron]['interval'];
	}

	function set_is_running()
	{
		$this->is_running = file_exists($this->file);

		do_log(trim(sprintf("%s has been running since %s", $this->file, '')), 'trash');

		if($this->is_running)
		{
			$file_time = date("Y-m-d H:i:s", @filemtime($this->file));

			if($file_time > DEFAULT_DATE && $this->has_expired(array('start' => $file_time, 'margin' => 1.2)))
			{
				do_log(sprintf("%s has been running since %s", $this->file, $file_time));
			}
		}
	}

	function has_expired($data = [])
	{
		if(!isset($data['start'])){			$data['start'] = $this->date_start;}
		if(!isset($data['end'])){			$data['end'] = date("Y-m-d H:i:s");}
		if(!isset($data['margin'])){		$data['margin'] = 1;}

		$time_difference = time_between_dates(array('start' => $data['start'], 'end' => $data['end'], 'type' => 'ceil', 'return' => 'seconds'));

		return ($time_difference >= ($this->get_interval() * $data['margin']));
	}

	function end($type = "")
	{
		if($type != "")
		{
			global $wpdb;

			$this->type = $type;

			$this->file = $this->upload_path.".is_running_".$wpdb->prefix.trim($this->type, "_");
		}

		$arr_progress = get_option('option_cron_progress');

		$arr_progress[$this->type]['end'] = date("Y-m-d H:i:s");

		update_option('option_cron_progress', $arr_progress, false);

		/*if(get_site_option('setting_base_cron_debug') == 'yes')
		{
			$time_difference = time_between_dates(array('start' => $this->date_start, 'end' => date("Y-m-d H:i:s"), 'type' => 'ceil', 'return' => 'seconds'));

			do_log("Cron: ".$this->type." started", 'trash');

			if($time_difference > 1)
			{
				do_log("Cron: ".$this->type." ended after ".$time_difference."s");
			}
		}*/

		if(file_exists($this->file))
		{
			unlink($this->file);
		}
	}
}

class recommend_plugin
{
	var $message = "";

	function get_install_link_tags($require_url, $required_name)
	{
		if($require_url == '')
		{
			$required_name = str_replace(array("& ", "&"), "", $required_name);

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

	function __construct($data)
	{
		global $pagenow;

		if(!isset($data['url'])){			$data['url'] = "";}
		if(!isset($data['show_notice'])){	$data['show_notice'] = true;}
		if(!isset($data['text'])){			$data['text'] = "";}

		if(!is_plugin_active($data['path']))
		{
			list($a_start, $a_end) = $this->get_install_link_tags($data['url'], $data['name']);

			if($pagenow == 'plugins.php' && $data['show_notice'] == true && $data['name'] != '')
			{
				$this->message = sprintf(__("We highly recommend that you install %s aswell", 'lang_base'), $a_start.$data['name'].$a_end).($data['text'] != '' ? " ".$data['text'] : "");

				add_action('network_admin_notices', array($this, 'show_notice'));
				add_action('admin_notices', array($this, 'show_notice'));
			}

			else if($pagenow == 'options-general.php' && $data['show_notice'] == false)
			{
				$this->message = $a_start.$data['name'].$a_end.($data['text'] != '' ? " <span class='description'>".$data['text']."</span>" : "");

				echo $this->show_info();
			}
		}
	}

	function show_notice()
	{
		global $notice_text;

		$notice_text = $this->message;

		echo get_notification();
	}

	function show_info()
	{
		return "<p>".$this->message."</p>";
	}
}

if(!function_exists('convert_to_screen'))
{
	require_once(ABSPATH.'wp-admin/includes/template.php');
}

// Needed when displaying tables in Front-End Admin
if(!class_exists('WP_Screen'))
{
	require_once(ABSPATH.'wp-admin/includes/screen.php');
	require_once(ABSPATH.'wp-admin/includes/class-wp-screen.php');
}

if(!class_exists('WP_List_Table'))
{
	$GLOBALS['hook_suffix'] = '';

	require_once(ABSPATH.'wp-admin/includes/admin.php');
}

class mf_list_table extends WP_List_Table
{
	var $arr_settings;
	var $post_type = "";
	var $table;
	var $orderby_default = "post_title";
	var $orderby_default_order = "ASC";

	var $views = [];
	var $columns = [];
	var $sortable_columns = [];
	var $data = "";
	var $data_full = "";
	var $num_rows = 0;
	var $query_join = "";
	var $query_where = "";
	var $search;
	var $search_key = 's';
	var $orderby;
	var $order;
	var $page;
	var $total_pages = "";
	var $debug = "";

	function __construct($data = [])
	{
		global $wpdb;

		parent::__construct(array(
			'singular' => '',
			'plural' => '',
			'ajax' => false,
		));

		if(!isset($data['per_page'])){			$data['per_page'] = $this->get_items_per_page('edit_page_per_page', 20);}
		if(!isset($data['query_from'])){		$data['query_from'] = $wpdb->posts;}
		if(!isset($data['query_select_id'])){	$data['query_select_id'] = "ID";}
		if(!isset($data['query_all_id'])){		$data['query_all_id'] = 'all';}
		if(!isset($data['query_trash_id'])){	$data['query_trash_id'] = array('trash', 'ignore');}
		if(!isset($data['display_search'])){	$data['display_search'] = true;}
		if(!isset($data['has_autocomplete'])){	$data['has_autocomplete'] = false;}
		if(!isset($data['remember_search'])){	$data['remember_search'] = false;}

		$this->arr_settings = $data;

		$this->page = check_var('page', 'char');

		$this->set_default();

		if($data['remember_search'] == true)
		{
			$this->search = get_or_set_table_filter(array('prefix' => ($this->post_type != '' ? $this->post_type : $this->table)."_", 'key' => $this->search_key, 'save' => true, 'default' => (isset($data['search']) ? $data['search'] : '')));
		}

		else
		{
			$this->search = check_var($this->search_key, 'char', true, (isset($data['search']) ? $data['search'] : ''));
		}

		// Has to be here too
		if($this->post_type != '')
		{
			$this->query_where .= ($this->query_where != '' ? " AND " : "")."post_type = '".$this->post_type."'";
		}

		$this->init_fetch();

		if($this->post_type != '')
		{
			$this->_args['singular'] = $this->post_type;
		}

		$this->process_bulk_action();

		$this->orderby = check_var('orderby', 'char', true, $this->orderby_default);
		$this->order = check_var('order', 'char', true, $this->orderby_default_order);
	}

	function set_default(){}
	function init_fetch(){}
	function sort_data(){}

	function set_columns($columns)
	{
		$this->columns = $columns;
	}

	function set_sortable_columns($columns)
	{
		foreach($columns as $column)
		{
			$this->sortable_columns[$column] = array($column, false);
		}
	}

	function empty_trash($db_field)
	{
		global $wpdb;

		if(substr($db_field, -7) == "Deleted")
		{
			$empty_trash_days = (defined('EMPTY_TRASH_DAYS') ? EMPTY_TRASH_DAYS : 30);

			$wpdb->get_results("SELECT ".$this->arr_settings['query_select_id']." FROM ".$this->arr_settings['query_from']." WHERE ".$db_field." = '1' AND ".$db_field."Date < DATE_SUB(NOW(), INTERVAL ".$empty_trash_days." DAY) LIMIT 0, 1");

			if($wpdb->num_rows > 0)
			{
				do_log(__FUNCTION__.": ".$wpdb->last_query);
			}
		}
	}

	function set_views($data)
	{
		global $wpdb;

		$this->empty_trash($data['db_field']);

		// Has to be here too
		if($this->post_type != '')
		{
			$this->query_where .= ($this->query_where != '' ? " AND " : "")."post_type = '".$this->post_type."'";
		}

		$db_value = check_var($data['db_field'], 'char', true, $this->arr_settings['query_all_id']);

		$query = "SELECT COUNT(*) FROM ".$this->arr_settings['query_from'].$this->query_join;

		if($this->query_where != '')
		{
			$query .= " WHERE ".$this->query_where;
		}

		$query_group = " GROUP BY ".$this->arr_settings['query_select_id'];

		foreach($data['types'] as $key => $value)
		{
			$query_this = $query
				.($this->query_where != '' ? " AND " : " WHERE ")
				.$this->get_views_trash_string($key, $data['db_field'])
				.$query_group;

			$wpdb->query($query_this);

			$amount = $wpdb->num_rows;

			if($amount > 0)
			{
				$url_xtra = "";

				if(isset($this->arr_settings['page_vars']) && count($this->arr_settings['page_vars']) > 0)
				{
					foreach($this->arr_settings['page_vars'] as $page_key => $page_value)
					{
						$url_xtra .= "&".$page_key."=".$page_value;
					}
				}

				if($this->search != '')
				{
					$url_xtra .= "&s=".$this->search;
				}

				$this->views[$key] = "<a href='".(is_admin() ? admin_url("admin.php?page=".$this->page)."&" : "?").$data['db_field']."=".$key.$url_xtra."'".($key == $db_value ? " class='current'" : "").">".$value." <span class='count'>(".$amount.")</span></a>";
			}
		}

		$this->query_where .= ($this->query_where != '' ? " AND " : "").$this->get_views_trash_string($db_value, $data['db_field']);
	}

	function get_views_trash_string($value, $field)
	{
		if($value == $this->arr_settings['query_all_id'])
		{
			if(is_array($this->arr_settings['query_trash_id']))
			{
				$out = "";

				foreach($this->arr_settings['query_trash_id'] as $query_trash_id)
				{
					$out .= ($out != '' ? " AND " : "").$field." != '".$query_trash_id."'";
				}
			}

			else
			{
				$out = $field." != '".$this->arr_settings['query_trash_id']."'";
			}
		}

		else
		{
			$out = $field." = '".$value."'";
		}

		return $out;
	}

	/** ************************************************************************
	 * Recommended. This method is called when the parent class can't find a method specifically build for a given column. Generally, it's recommended to include one method for each column you want to render, keeping your package class neat and organized. For example, if the class needs to process a column named 'title', it would first see if a method named $this->column_title() exists - if it does, that method will be used. If it doesn't, this one will be used. Generally, you should try to use custom column methods as much as possible.
	 *
	 * Since we have defined a column_title() method later on, this method doesn't need to concern itself with any column with a name of 'title'. Instead, it needs to handle everything else.
	 *
	 * For more detailed insight into how columns are handled, take a look at WP_List_Table::single_row_columns()
	 *
	 * @param array $item A singular item (one full row's worth of data)
	 * @param array $column_name The name/slug of the column to be processed
	 * @return string Text or HTML to be placed inside the column <td>
	 **************************************************************************/
	function column_default($item, $column_name)
	{
		$out = "";

		switch($column_name)
		{
			default:
				if(isset($item[$column_name]))
				{
					$out .= $item[$column_name];
				}

				else
				{
					$out .= $column_name." != ".var_export($item, true);
				}
			break;
		}

		return $out;
	}

	/** ************************************************************************
	 * REQUIRED if displaying checkboxes or using bulk actions! The 'cb' column
	 * is given special treatment when columns are processed. It ALWAYS needs to
	 * have it's own method.
	 *
	 * @see WP_List_Table::::single_row_columns()
	 * @param array $item A singular item (one full row's worth of data)
	 * @return string Text to be placed inside the column <td> (movie title only)
	 **************************************************************************/
	function column_cb($item)
	{
		return "<input type='checkbox' name='".$this->_args['singular']."[]' value='".$item[$this->arr_settings['query_select_id']]."'>";
	}

	/** ************************************************************************
	* REQUIRED! This method dictates the table's columns and titles. This should return an array where the key is the column slug (and class) and the value is the column's title text. If you need a checkbox for bulk actions, refer to the $columns array below.
	*
	* The 'cb' column is treated differently than the rest. If including a checkbox column in your table you must create a column_cb() method. If you don't need bulk actions or checkboxes, simply leave the 'cb' entry out of your array.
	*
	* @see WP_List_Table::::single_row_columns()
	* @return array An associative array containing column information: 'slugs'=>'Visible Titles'
	**************************************************************************/
	function get_columns()
	{
		return $this->columns;
	}

	function get_views()
	{
		return $this->views;
	}

	/** ************************************************************************
	 * Optional. If you need to include bulk actions in your list table, this is
	 * the place to define them. Bulk actions are an associative array in the format
	 * 'slug'=>'Visible Title'
	 *
	 * If this method returns an empty value, no bulk action will be rendered. If
	 * you specify any bulk actions, the bulk actions box will be rendered with
	 * the table automatically on display().
	 *
	 * Also note that list tables are not automatically wrapped in <form> elements,
	 * so you will need to create those manually in order for bulk actions to function.
	 *
	 * @return array An associative array containing all the bulk actions: 'slugs'=>'Visible Titles'
	 **************************************************************************/
	function get_bulk_actions()
	{
		$arr_actions = [];

		if(isset($this->columns['cb']))
		{
			$post_status = check_var('post_status');

			if($post_status == 'trash')
			{
				$arr_actions['restore'] = __("Restore", 'lang_base');
				$arr_actions['delete'] = __("Permanently Delete", 'lang_base');
			}

			else
			{
				$arr_actions['trash'] = __("Delete", 'lang_base');
			}
		}

		return $arr_actions;
	}

	/** ************************************************************************
	 * Optional. You can handle your bulk actions anywhere or anyhow you prefer.
	 * For this example package, we will handle it in the class to keep things
	 * clean and organized.
	 *
	 * @see $this->prepare_items()
	 **************************************************************************/
	function bulk_trash()
	{
		if(isset($_GET[$this->post_type]))
		{
			foreach($_GET[$this->post_type] as $post_id)
			{
				$post_id = check_var($post_id, 'int', false);

				wp_trash_post($post_id);
			}
		}
	}

	function bulk_restore()
	{
		if(isset($_GET[$this->post_type]))
		{
			foreach($_GET[$this->post_type] as $post_id)
			{
				$post_id = check_var($post_id, 'int', false);

				wp_untrash_post($post_id);
			}
		}
	}

	function bulk_delete()
	{
		if(isset($_GET[$this->post_type]))
		{
			foreach($_GET[$this->post_type] as $post_id)
			{
				$post_id = check_var($post_id, 'int', false);

				wp_delete_post($post_id);
			}
		}
	}

	function process_bulk_action()
	{
		if(isset($_GET['_wpnonce']) && !empty($_GET['_wpnonce']))
		{
			switch($this->current_action())
			{
				case 'trash':
					$this->bulk_trash();
				break;

				case 'restore':
					$this->bulk_restore();
				break;

				case 'delete':
					$this->bulk_delete();
				break;
			}
		}
	}

	protected function extra_tablenav($which)
	{
		global $wpdb;

		echo "<div class='alignleft actions'>";

			if('top' === $which && !is_singular())
			{
				ob_start();

				//$this->months_dropdown($this->screen->post_type);
				//$this->categories_dropdown($this->screen->post_type);

				/**
				 * Fires before the Filter button on the Posts and Pages list tables.
				 *
				 * The Filter button allows sorting by date and/or category on the
				 * Posts list table, and sorting by date on the Pages list table.
				 *
				 * @since 2.1.0
				 * @since 4.4.0 The `$post_type` parameter was added.
				 * @since 4.6.0 The `$which` parameter was added.
				 *
				 * @param string $post_type	The post type slug.
				 * @param string $which		The location of the extra table nav markup:
				 *							'top' or 'bottom' for WP_Posts_List_Table,
				 *							'bar' for WP_Media_List_Table.
				 */
				if(!is_multisite() || (isset($wpdb->sitemeta) && $wpdb->sitemeta != '')) // If for some reason another external DB is in use at this moment, don't bother doing this
				{
					do_action('restrict_manage_posts', ($this->arr_settings['query_from'] != '' ? $this->arr_settings['query_from'] : $this->post_type), $which);
				}

				$output = ob_get_clean();

				if(!empty($output))
				{
					echo $output;

					submit_button(__("Filter", 'lang_base'), '', 'filter_action', false, array('id' => 'post-query-submit'));
				}
			}

			if($this->has_items())
			{
				submit_button(__("Empty Trash"), 'apply', 'delete_all', false);
			}

		echo "</div>";

		/**
		 * Fires immediately following the closing "actions" div in the tablenav for the posts
		 * list table.
		 *
		 * @since 4.4.0
		 *
		 * @param string $which The location of the extra table nav markup: 'top' or 'bottom'.
		 */
		do_action('manage_posts_extra_tablenav', $which);
	}

	/** ************************************************************************
	 * REQUIRED! This is where you prepare your data for display. This method will
	 * usually be used to query the database, sort and filter the data, and generally
	 * get it ready to be displayed. At a minimum, we should set $this->items and
	 * $this->set_pagination_args(), although the following properties and methods
	 * are frequently interacted with here...
	 *
	 * @global WPDB $wpdb
	 * @uses $this->_column_headers
	 * @uses $this->items
	 * @uses $this->get_columns()
	 * @uses $this->get_sortable_columns()
	 * @uses $this->get_pagenum()
	 * @uses $this->set_pagination_args()
	 **************************************************************************/
	function prepare_items()
	{
		global $wpdb;

		$hidden = [];

		$this->_column_headers = array($this->columns, $hidden, $this->sortable_columns);

		$current_page = $this->get_pagenum();

		if(!is_array($this->data))
		{
			$this->data = [];
		}

		$this->items = $this->data = array_slice($this->data, (($current_page - 1) * $this->arr_settings['per_page']), $this->arr_settings['per_page']);

		$this->total_pages = ceil($this->num_rows / $this->arr_settings['per_page']);

		$this->set_pagination_args(array(
			'total_items' => $this->num_rows,
			'per_page'	=> $this->arr_settings['per_page'],
			'total_pages' => $this->total_pages
		));
	}

	protected function get_table_classes()
	{
		return array('widefat', 'striped');
	}

	function search_box($text, $input_id)
	{
		if($this->search != '' || $this->has_items() || isset($this->arr_settings['force_search']) && $this->arr_settings['force_search'])
		{
			$input_id = esc_attr($input_id."-search-input");

			echo "<div".get_form_button_classes("search-box alignright flex_flow tight").">"
				.show_textfield(array('type' => 'search', 'name' => $this->search_key, 'id' => $input_id, 'value' => $this->search))
				.show_button(array('text' => $text, 'class' => "button", 'xtra' => " id='search-submit'"));

				$arr_var_keys = array('orderby', 'order', 'post_status');

				foreach($arr_var_keys as $var_key)
				{
					if(!empty($_REQUEST[$var_key]))
					{
						echo input_hidden(array('name' => $var_key, 'value' => check_var($var_key)));
					}
				}

			echo "</div>";
		}
	}

	function show_search_form()
	{
		echo "<form method='get'".(is_admin() ? "" : " class='mf_form'").($this->arr_settings['has_autocomplete'] == true && isset($this->arr_settings['action']) ? " rel='".$this->arr_settings['action']."'" : "").">";

			$this->search_box(__("Search", 'lang_base'), $this->search_key);

			echo input_hidden(array('name' => 'page', 'value' => $this->page));

			if(isset($this->arr_settings['page_vars']) && count($this->arr_settings['page_vars']) > 0)
			{
				foreach($this->arr_settings['page_vars'] as $page_key => $page_value)
				{
					echo input_hidden(array('name' => $page_key, 'value' => $page_value));
				}
			}

		echo "</form>";
	}

	function filter_search_before_like($string)
	{
		if(strpos($string, "%") !== false)
		{
			// Do nothing
		}

		else if(strpos($string, "*") !== false)
		{
			$string = str_replace("*", "%", $string);
		}

		else
		{
			$string = "%".$string."%";
		}

		return $string;
	}

	function select_data($data = [])
	{
		global $wpdb;

		if(!isset($data['full_data'])){		$data['full_data'] = false;}
		if(!isset($data['sort_data'])){		$data['sort_data'] = false;}
		if(!isset($data['select'])){		$data['select'] = "*";}
		if(!isset($data['join'])){			$data['join'] = "";}
		if(!isset($data['where'])){			$data['where'] = "";}
		if(!isset($data['group_by'])){		$data['group_by'] = $this->arr_settings['query_select_id'];}
		if(!isset($data['order_by'])){		$data['order_by'] = $this->orderby;}
		if(!isset($data['order'])){			$data['order'] = $this->order;}
		if(!isset($data['limit'])){			$data['limit'] = 0;} //check_var('paged', 'int', true, '0') // This will mess up counter for all and pagination
		//if(!isset($data['amount'])){		$data['amount'] = ($data['sort_data'] == true ? 0 : $this->arr_settings['per_page']);} // This will mess up pagination
		if(!isset($data['amount'])){		$data['amount'] = 5000;}
		if(!isset($data['debug'])){			$data['debug'] = false;}
		if(!isset($data['debug_type'])){	$data['debug_type'] = 'echo';}

		$data = apply_filters('pre_select_data', $data, ($this->arr_settings['query_from'] != '' ? $this->arr_settings['query_from'] : $this->post_type));

		$query_from = $this->arr_settings['query_from'];
		$query_join = $this->query_join.$data['join'];
		$query_where = $query_group = $query_order = $query_limit = "";

		if($this->query_where != '' || $data['where'] != '')
		{
			$query_where .= " WHERE ";

			if($this->query_where != '')
			{
				$query_where .= $this->query_where;
			}

			if($data['where'])
			{
				$query_where .= ($this->query_where != '' ? " AND " : "").$data['where'];
			}
		}

		if($data['group_by'] != '')
		{
			$query_group .= " GROUP BY ".$data['group_by'];
		}

		if($data['order_by'] != '' && strpos($data['order_by'], " ") === false)
		{
			$arr_tables = array($query_from);

			if($query_join != '')
			{
				$arr_tables_temp = get_match_all('/ JOIN (.*?) /is', $query_join, false);

				if(isset($arr_tables_temp[0]))
				{
					foreach($arr_tables_temp[0] as $table_name)
					{
						$arr_tables[] = $table_name;
					}
				}
			}

			$column_exists = false;

			foreach($arr_tables as $table_name)
			{
				if(does_column_exist($table_name, $data['order_by']))
				{
					$column_exists = true;

					break;
				}
			}

			if($column_exists == false)
			{
				$data['order_by'] = "";
			}
		}

		if($data['order_by'] != '')
		{
			if(is_array($data['order']))
			{
				do_log(__FUNCTION__." - Error in 'order': ".var_export($this->arr_settings, true)." || ".$this->post_type." -> ".var_export($data, true)." (Backtrace: ".var_export(debug_backtrace(), true).")");

				$data['order'] = "ASC";
			}

			if(is_array($data['order_by']))
			{
				do_log(__FUNCTION__." - Error in 'order_by': ".var_export($this->arr_settings, true)." || ".$this->post_type." -> ".var_export($data, true)." (Backtrace: ".var_export(debug_backtrace(), true).")");
			}

			else
			{
				$query_order .= " ORDER BY ".$data['order_by']." ".$data['order'];
			}
		}

		if($data['amount'] > 0)
		{
			$query_limit .= " LIMIT ".$data['limit'].", ".$data['amount'];
		}

		$result = $wpdb->get_results("SELECT ".$data['select']." FROM ".$query_from.$query_join.$query_where.$query_group.$query_order.$query_limit);
		$this->num_rows = $wpdb->num_rows;

		if($data['debug'] == true)
		{
			switch($data['debug_type'])
			{
				case 'log':
					do_log(__CLASS__."->".__FUNCTION__.": ".$wpdb->last_query);
				break;

				case 'return':
					$this->debug .= $wpdb->last_query;
				break;

				default:
				case 'echo':
					echo "<p>".__CLASS__."->".__FUNCTION__.": (".$this->num_rows."x) ".$wpdb->last_query."</p>";
				break;
			}
		}

		$this->data = json_decode(json_encode($result), true);

		if($data['full_data'] == true)
		{
			$this->data_full = $this->data;
		}

		if($data['sort_data'] == true)
		{
			if($this->num_rows > 0)
			{
				if($data['debug'] == true)
				{
					echo __("Sorting", 'lang_base')."&hellip;<br>";
				}

				$this->sort_data();
				$this->num_rows = count($this->data);

				if($data['debug'] == true)
				{
					echo __("Rows", 'lang_base').": ".$this->num_rows."<br>";
				}
			}
		}
	}

	/*public function single_row($item)
	{
		echo "<tr".(isset($item['tr_class']) && $item['tr_class'] != '' ? " class='".$item['tr_class']."'" : "").">";

			$this->single_row_columns($item);

		echo "</tr>";
	}*/

	function do_display()
	{
		$this->prepare_items();

		$this->views();

		if($this->arr_settings['display_search'] == true)
		{
			$this->show_search_form();
		}

		$this->show_before_display();
		$this->display();
		$this->show_after_display();
	}

	function show_before_display()
	{
		echo "<form method='get'".get_form_button_classes().">
			<input type='hidden' name='page' value='".check_var('page')."'>";
	}

	function show_after_display()
	{
		echo "</form>";
	}
}

if(class_exists('RWMB_Field'))
{
	class RWMB_Clock_Field extends RWMB_Field
	{
		static public function html($meta, $field)
		{
			return sprintf(
				"<input type='text' name='%s' id='%s' value='%s' class='rwmb-text rwmb-clock' placeholder='18.00-03.00&hellip;'%s>", // pattern='[\d\s\:\.-]*'
				$field['field_name'],
				$field['id'],
				$meta,
				self::render_attributes($field['attributes'])
			);
		}
	}

	class RWMB_Page_Field extends RWMB_Field
	{
		static public function html($meta, $field)
		{
			$arr_data = [];
			get_post_children(array('add_choose_here' => true), $arr_data);

			return show_select(array('data' => $arr_data, 'name' => $field['field_name'], 'value' => $meta, 'class' => "rwmb-select-wrapper", 'xtra' => self::render_attributes($field['attributes'])));
		}
	}

	class RWMB_Phone_Field extends RWMB_Field
	{
		static public function html($meta, $field)
		{
			return sprintf(
				"<input type='tel' name='%s' id='%s' value='%s' class='rwmb-text rwmb-phone' pattern='[\d\s-]*' placeholder='".__("001-888-342-324", 'lang_base')."&hellip;'%s>",
				$field['field_name'],
				$field['id'],
				$meta,
				self::render_attributes($field['attributes'])
			);
		}
	}

	class RWMB_Select3_Field extends RWMB_Select_Field
	{
		public static function html($meta, $field)
		{
			$options = self::transform_options($field['options']);
			$attributes = self::call('get_attributes', $field, $meta);
			$attributes['data-selected'] = $meta;
			$walker = new RWMB_Walker_Select($field, $meta);

			$attributes['class'] .= " multiselect";

			do_action('init_multiselect');

			$output = sprintf("<select %s>", self::render_attributes($attributes));

				if(!$field['multiple'] && $field['placeholder'])
				{
					$output .= "<option value=''>".esc_html($field['placeholder'])."</option>";
				}

				$output .= $walker->walk($options, $field['flatten'] ? -1 : 0)
			."</select>"
			.self::get_select_all_html($field);

			return $output;
		}
	}
}

class settings_page
{
	public function __construct()
	{
		add_action('admin_menu', array($this, 'add_plugin_page'));
	}

	public function add_plugin_page()
	{
		add_options_page(
			__("My Settings", 'lang_base'),
			__("My Settings", 'lang_base'),
			'manage_options',
			BASE_OPTIONS_PAGE,
			array($this, 'create_admin_page')
		);
	}

	function do_settings_sections($page)
	{
		global $wp_settings_sections, $wp_settings_fields;

		if(!isset($wp_settings_sections[$page]))
		{
			return;
		}

		foreach((array)$wp_settings_sections[$page] as $section)
		{
			if($section['title'])
			{
				echo "<h2>".$section['title']."</h2>";
			}

			if($section['callback'])
			{
				call_user_func($section['callback'], $section);
			}

			if(!isset($wp_settings_fields) || !isset($wp_settings_fields[$page]) || !isset($wp_settings_fields[$page][$section['id']]))
			{
				continue;
			}

			echo "<table class='form-table hide'>";

				do_settings_fields($page, $section['id']);

			echo "</table>";
		}
	}

	public function create_admin_page()
	{
		echo "<div class='wrap'>
			<h2>".__("My Settings", 'lang_base')."</h2>
			<div class='settings-wrap loading'>
				<div class='settings-nav contextual-help-tabs'>
					<ul></ul>
				</div>
				<form method='post' action='options.php' class='settings-tabs mf_form'>";

					settings_fields(BASE_OPTIONS_PAGE);
					$this->do_settings_sections(BASE_OPTIONS_PAGE);
					submit_button();

				echo "</div>
			</div>
		</div>";
	}
}

class mf_microtime
{
	var $time_limit;
	var $now;
	var $time_orig;

	function __construct($data = [])
	{
		if(!isset($data['limit'])){	$data['limit'] = 0;}

		$this->time_limit = $data['limit'];

		$this->save_now();
		$this->time_orig = $this->now;
	}

	function save_now()
	{
		list($usec, $sec) = explode(" ", microtime());

		$this->now = (float) $usec + (float) $sec;
	}

	function check_time($limit)
	{
		$this->save_now();

		return (($this->now - $this->time_orig) > $limit);
	}

	/*function output($string, $type = "ms")
	{
		$time_old = $this->now;
		$this->save_now();

		$time_diff = $this->now - $time_old;
		$time_diff_orig = $this->now - $this->time_orig;

		if($type == "ms")
		{
			$time_diff *= 1000;
			$time_diff_orig *= 1000;
		}

		if($time_diff >= $this->time_limit)
		{
			return $string.": ".mf_format_number($time_diff, 4)." (".mf_format_number($time_diff_orig).")<br>";
		}
	}*/
}

class mf_font_icons
{
	var $id;
	var $fonts = [];

	function __construct($id = "")
	{
		$this->id = $id;

		if($this->id == "" || $this->id == 'icomoon')
		{
			$this->fonts['icomoon'] = array(
				"icon-accessible",
				"icon-bed",
				"icon-chair",
				"icon-clock",
				"icon-close",
				"icon-drink",
				"icon-exclusive",
				"icon-food",
				"icon-food-n-drink",
				"icon-home",
				"icon-info",
				"icon-music",
				"icon-parking",
				"icon-person",
				"icon-price",
				"icon-transportation",
			);
		}

		if($this->id == "" || $this->id == 'font_awesome')
		{
			$this->fonts['font_awesome'] = $this->get_font_awesome_icon_list();
		}
	}

	function get_array($data = [])
	{
		if(!isset($data['allow_optgroup'])){ $data['allow_optgroup'] = true;}

		$arr_out = [];

		if($this->id != "")
		{
			foreach($this->fonts[$this->id] as $icon)
			{
				$arr_out[$icon] = $icon;
			}
		}

		else
		{
			foreach($this->fonts as $key => $fonts)
			{
				if($data['allow_optgroup'] == true)
				{
					$arr_out["opt_start_".$key] = $key;
				}

					foreach($fonts as $icon)
					{
						$arr_out[$icon] = $icon;
					}

				if($data['allow_optgroup'] == true)
				{
					$arr_out["opt_end_".$key] = "";
				}
			}
		}

		return $arr_out;
	}

	function get_font_awesome_icon_list()
	{
		$arr_icons = array(
			'fas fa-arrow-right',
			'fas fa-award',
			'fas fa-briefcase',
			'fas fa-briefcase-medical',
			'fas fa-bullseye',
			'far fa-building',
			'fas fa-calculator',
			'fas fa-calendar-alt',
			'fas fa-chalkboard-teacher',
			'fas fa-chart-bar',
			'far fa-clock',
			'fas fa-clock',
			'fas fa-coins',
			'fas fa-download',
			'eye',
			'fa fa-exclamation-circle',
			'exclamation-triangle',
			'fas fa-external-link-alt',
			'fab fa-facebook',
			'fas fa-file-alt',
			'fas fa-graduation-cap',
			'fas fa-handshake',
			'fas fa-hospital-alt',
			'fab fa-instagram',
			'fas fa-key',
			'fa fa-link',
			'lock',
			'fas fa-map-marker-alt',
			'paper-plane',
			'fas fa-parking',
			'fas fa-play-circle',
			'fa fa-question',
			'fas fa-scroll',
			'fas fa-shopping-cart',
			'far fa-star',
			'fas fa-sun',
			'unlink',
			'fas fa-user',
			'fas fa-users',
			'fas fa-utensils',
			'fas fa-video',
			'fas fa-wheelchair',
		);

		return $arr_icons;
	}

	function get_symbol_tag($data)
	{
		if(!isset($data['title'])){		$data['title'] = '';}
		if(!isset($data['class'])){		$data['class'] = '';}

		$out = "";

		if($data['symbol'] != '')
		{
			if(substr($data['symbol'], 0, 5) == "icon-")
			{
				mf_enqueue_style('style_icomoon', plugin_dir_url(__FILE__)."style_icomoon.php");

				$out = "<span class='".$data['symbol'].($data['class'] != '' ? " ".$data['class'] : '')."'".($data['title'] != '' ? " title='".$data['title']."'" : "")."></span>";
			}

			else
			{
				if(substr($data['symbol'], 0, 2) != 'fa')
				{
					$data['symbol'] = "fa fa-".$data['symbol'];
				}

				$out = "<i class='".$data['symbol'].($data['class'] != '' ? " ".$data['class'] : '')."'".($data['title'] != '' ? " title='".$data['title']."'" : "")."></i>";
			}
		}

		return $out;
	}
}

class mf_export
{
	var $has_excel_support;
	var $dir_exists = true;
	var $plugin;
	var $name;
	var $arr_columns = [];
	var $order_by;
	var $do_export;
	var $type;
	var $format;
	var $data;
	var $upload_path;
	var $upload_url; 
	var $type_name = '';
	var $types = [];
	var $formats;
	var $file_name;

	function __construct($data = [])
	{
		$this->has_excel_support = is_plugin_active("mf_phpexcel/index.php");

		$this->plugin = (isset($data['plugin']) ? $data['plugin'] : 'mf_unknown');
		$this->name = (isset($data['name']) ? $data['name'] : '');

		$this->do_export = (isset($data['do_export']) ? $data['do_export'] : false);
		$this->type = (isset($data['type']) ? $data['type'] : '');
		$this->format = (isset($data['format']) ? $data['format'] : '');

		$this->data = (isset($data['data']) ? $data['data'] : []);

		$this->formats = array(
			'' => "-- ".__("Choose Here", 'lang_base')." --",
			'csv' => "CSV",
			'json' => "JSON",
		);

		if($this->has_excel_support)
		{
			$this->formats['xls'] = "XLS";
		}

		$this->get_defaults();

		list($this->upload_path, $this->upload_url) = get_uploads_folder($this->plugin);

		$this->fetch_request();

		echo $this->save_data();
	}

	function get_defaults(){}
	function fetch_request_xtra(){}
	function get_export_data(){}
	function get_form_xtra(){}

	function fetch_request()
	{
		$this->type = check_var('intExportType', 'char', true, $this->type);
		$this->format = check_var('strExportFormat', 'char', true, $this->format);

		$this->fetch_request_xtra();
	}

	function set_file_name()
	{
		if($this->file_name == '')
		{
			$this->file_name = prepare_file_name($this->name).".".$this->format;
		}
	}

	function compress_file()
	{
		if(class_exists('ZipArchive'))
		{
			$zip = new ZipArchive();

			$file_source = $this->upload_path.$this->file_name;
			$file_name = basename($file_source);
			$file_destination = $this->upload_path.$file_name.".zip";

			if(file_exists($file_destination))
			{
				unlink($file_destination);
			}

			if($zip->open($file_destination, ZIPARCHIVE::CREATE))
			{
				if(file_exists($file_source) && is_file($file_source))
				{
					$zip->addFile($file_source, $file_name);

					if($zip->close())
					{
						$this->file_name = $file_name.".zip";
						unlink($file_source);
					}
				}
			}
		}
	}

	function save_data()
	{
		global $error_text, $done_text;

		$out = "";

		if(isset($_REQUEST['btnExportRun']) && wp_verify_nonce($_REQUEST['_wpnonce_export_run'], 'export_run'))
		{
			$this->do_export = true;
		}

		if($this->do_export == true)
		{
			if($this->format != '')
			{
				$this->get_export_data();

				if(count($this->data) > 0)
				{
					$this->set_file_name();

					switch($this->format)
					{
						case 'csv':
							$field_separator = ",";
							$row_separator = "\n";

							$out_temp = "";

							foreach($this->data as $row)
							{
								$out_temp .= ($out_temp != '' ? $row_separator : "");

								$count_temp = count($row);

								for($i = 0; $i < $count_temp; $i++)
								{
									$row_value = preg_replace("/(\r\n|\r|\n|".$field_separator.")/", " ", $row[$i]);

									$out_temp .= ($i > 0 ? $field_separator : "").(is_array($row_value) ? "[".implode("|", $row_value)."]" : $row_value);
								}
							}

							$success = set_file_content(array('file' => $this->upload_path.$this->file_name, 'mode' => 'a', 'content' => trim($out_temp)));

							if($success == true)
							{
								$this->compress_file();

								$done_text = __("Download the exported file here", 'lang_base').": <a href='".$this->upload_url.$this->file_name."'>".$this->file_name."</a>";
							}

							else
							{
								$error_text = __("It was not possible to export", 'lang_base');
							}
						break;

						case 'json':
							$success = set_file_content(array('file' => $this->upload_path.$this->file_name, 'mode' => 'a', 'content' => json_encode($this->data)));

							if($success == true)
							{
								$this->compress_file();

								$done_text = __("Download the exported file here", 'lang_base').": <a href='".$this->upload_url.$this->file_name."'>".$this->file_name."</a>";
							}

							else
							{
								$error_text = __("It was not possible to export", 'lang_base');
							}
						break;

						case 'xls':
							$arr_alphabet = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');

							$objPHPExcel = new PHPExcel();

							foreach($this->data as $row_key => $row_value)
							{
								foreach($row_value as $col_key => $col_value)
								{
									$cell = "";

									$count_temp = count($arr_alphabet);

									while($col_key >= $count_temp)
									{
										$cell .= $arr_alphabet[floor($col_key / $count_temp) - 1];

										$col_key = $col_key % $count_temp;
									}

									$cell .= $arr_alphabet[$col_key].($row_key + 1);

									if($col_value != '')
									{
										$objPHPExcel->setActiveSheetIndex(0)->setCellValue($cell, (is_array($col_value) ? "[".implode("|", $col_value)."]" : stripslashes($col_value)));
									}
								}
							}

							$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5'); //XLSX: Excel2007
							$objWriter->save($this->upload_path.$this->file_name);

							$this->compress_file();

							$done_text = __("Download the exported file here", 'lang_base').": <a href='".$this->upload_url.$this->file_name."'>".$this->file_name."</a>";
						break;
					}
				}

				else if($error_text == '')
				{
					$error_text = __("There was nothing to export", 'lang_base');
				}
			}

			else
			{
				$error_text = __("You have to choose a file type to export to", 'lang_base');
			}
		}
	}

	function get_form()
	{
		global $error_text;

		$out = get_notification()
		."<form action='#' method='post' class='mf_form mf_settings'>"
			."<div id='poststuff' class='postbox'>
				<h3 class='hndle'>".__("Settings", 'lang_base')."</h3>
				<div class='inside'>";

					if(count($this->types) > 0)
					{
						$out .= show_select(array('data' => $this->types, 'name' => 'intExportType', 'text' => $this->type_name, 'value' => $this->type));
					}

					if(count($this->formats) > 0)
					{
						$out .= show_select(array('data' => $this->formats, 'name' => 'strExportFormat', 'text' => __("File type", 'lang_base'), 'value' => $this->format));
					}

					$out .= $this->get_form_xtra()
					.show_button(array('name' => 'btnExportRun', 'text' => __("Run", 'lang_base')))
					.wp_nonce_field('export_run', '_wpnonce_export_run', true, false)
				."</div>
			</div>
		</form>";

		return $out;
	}
}

class mf_import
{
	var $prefix;
	var $table = '';
	var $post_type = '';
	var $arr_actions = [];
	var $columns = [];
	var $unique_columns = [];
	var $validate_columns = [];
	var $row_separator = "
";
	var $is_run = false;
	var $unique_check = "OR";
	var $has_excel_support;
	var $save_result;
	var $result = [];
	var $rows_updated = 0;
	var $rows_up_to_date = 0;
	var $rows_inserted = 0;
	var $rows_not_inserted = 0;
	var $rows_deleted = 0;
	var $rows_not_deleted = 0;
	var $rows_exists = 0;
	var $rows_not_exists = 0;
	var $has_unchanged_data;
	var $query_base_where;
	var $table_id;
	var $query_set;
	var $query_where_first;
	var $query_where;
	var $query_option;
	var $current_column;
	var $columns_for_select;
	var $data;
	var $file_location;
	var $action;
	var $skip_header;
	var $text;
	var $text_filtered;
	var $value_separator;

	function __construct()
	{
		global $wpdb;

		$plugin_include_url = plugin_dir_url(__FILE__);

		mf_enqueue_style('style_import_wp', $plugin_include_url."style_import_wp.css");
		mf_enqueue_script('script_import_wp', $plugin_include_url."script_import_wp.js", array(
			'plugin_url' => $plugin_include_url,
		));

		$this->prefix = $wpdb->prefix;

		$this->has_excel_support = is_plugin_active("mf_phpexcel/index.php");

		$this->get_defaults();
		$this->fetch_request();
	}

	function get_defaults(){}
	function get_external_value(&$strRowField, &$value){}
	function if_more_than_one($id){}
	function inserted_new($id){}
	function updated_new($id){}
	function update_options_extend($id){}

	function filter_value($strRowField, $value)
	{
		return $value;
	}

	function get_used_separator($data)
	{
		$str_separator = "";
		$int_separator_count = 0;

		$arr_separator = array(",", ";", "	");

		$count_temp = count($arr_separator);

		for($i = 0; $i < $count_temp; $i++)
		{
			$int_separator_count_temp = substr_count($data['string'], $arr_separator[$i]);

			if($int_separator_count_temp > $int_separator_count)
			{
				$str_separator = $arr_separator[$i];
				$int_separator_count = $int_separator_count_temp;
			}
		}

		return $str_separator;
	}

	function is_json($string)
	{
		return is_array(json_decode($string, true)) ? true : false;
	}

	function fetch_request()
	{
		$this->data = [];
		$this->file_location = '';

		$this->action = check_var('strTableAction');
		$this->skip_header = check_var('intImportSkipHeader', '', true, '0');
		$this->save_result = check_var('intImportSaveResult', '', true, '0');
		$this->text = (isset($_POST['strImportText']) ? trim($_POST['strImportText']) : '');

		if($this->text != '')
		{
			if($this->is_json(stripslashes($this->text)))
			{
				$this->data = json_decode(stripslashes($this->text), true);
			}

			else
			{
				$this->text_filtered = $this->text;
				$this->value_separator = $this->get_used_separator(array('string' => $this->text));

				$this->text_filtered = preg_replace("/\".*?(\n).*?\"/s", "", $this->text_filtered);
				$this->text_filtered = str_replace('"', "", $this->text_filtered);

				$arr_rows = explode($this->row_separator, $this->text_filtered);
				$count_temp_rows = count($arr_rows);

				for($i = 0; $i < $count_temp_rows; $i++)
				{
					$row = trim($arr_rows[$i]);

					if($this->value_separator != '')
					{
						$arr_values = explode($this->value_separator, $row);
					}

					else
					{
						$arr_values = array($row);
					}

					$count_temp_values = count($arr_values);

					for($j = 0; $j < $count_temp_values; $j++)
					{
						$value = $arr_values[$j];
						$value = stripslashes($value);
						$value = trim($value, '"');
						$value = trim($value);
						$value = addslashes($value);

						$this->data[$i][$j] = $value;
					}
				}
			}
		}

		else if($this->has_excel_support)
		{
			$this->file_name = (isset($_FILES['strImportFile']) ? $_FILES['strImportFile']['name'] : '');
			$this->file_location = (isset($_FILES['strImportFile']) ? $_FILES['strImportFile']['tmp_name'] : '');

			if($this->file_name != '' && $this->file_location != '')
			{
				$file_suffix = get_file_suffix($this->file_name);

				switch($file_suffix)
				{
					case 'xlsx':
						$file_reader = "Excel2007";
					break;

					default:
					case 'xls':
						$file_reader = "Excel5";
					break;
				}

				$objReader = PHPExcel_IOFactory::createReader($file_reader);
				$objReader->setReadDataOnly(true);
				$objPHPExcel = $objReader->load($this->file_location);

				$objWorksheet = $objPHPExcel->getActiveSheet();

				$i = 0;

				foreach($objWorksheet->getRowIterator() as $row)
				{
					$cellIterator = $row->getCellIterator();
					$cellIterator->setIterateOnlyExistingCells(false); // This loops through all cells, even if a cell value is not set. By default, only cells that have a value set will be iterated.

					$j = 0;

					foreach($cellIterator as $cell)
					{
						$value = $cell->getValue();
						$value = trim($value);

						$this->data[$i][$j] = $value;

						$j++;
					}

					$i++;
				}
			}
		}

		$this->is_run = (isset($_POST['btnImportRun']) && wp_verify_nonce($_POST['_wpnonce_import_run'], 'import_run') && $this->action != '' && count($this->data) > 0);
	}

	function update_options($id)
	{
		switch($this->table)
		{
			case 'posts':
				foreach($this->query_option as $key => $value)
				{
					update_post_meta($id, $key, $value);
				}
			break;

			case 'users':
				foreach($this->query_option as $key => $value)
				{
					update_user_meta($id, $key, $value);
				}
			break;

			default:
				$this->update_options_extend($id);
			break;
		}
	}

	function do_import()
	{
		global $wpdb, $done_text, $error_text;

		$out = "";

		$count_temp_rows = count($this->data);

		if($count_temp_rows > 0)
		{
			$this->has_unchanged_data = wp_verify_nonce($_POST['_wpnonce_import_data'], 'import_data_'.md5(json_encode($this->data)));

			if($this->has_unchanged_data)
			{
				$this->query_base_where = "";

				switch($this->table)
				{
					case 'posts':
						$this->table_id = "ID";
						$table_created = "post_date";
						$table_user = "post_author";

						$this->query_base_where .= ($this->query_base_where != '' ? " AND " : "")."post_type = '".esc_sql($this->post_type)."'";
					break;

					case 'users':
						$this->table_id = "ID";
						$table_created = "user_registered";
						$table_user = '';
					break;

					default:
						if(preg_match("/\_/", $this->table))
						{
							list($rest, $table_field_prefix) = explode("_", $this->table);
						}

						else
						{
							$table_field_prefix = $this->table;
						}

						$this->table_id = $table_field_prefix."ID";
						$table_created = $table_field_prefix."Created";
						$table_user = "userID";
					break;
				}

				$i_start = $this->skip_header;

				for($i = $i_start; $i < $count_temp_rows; $i++)
				{
					$this->query_where = $query_where_fallback = $this->query_where_first = $this->query_set = "";
					$this->query_option = [];

					$arr_values = $this->data[$i];
					$count_temp_values = count($arr_values);

					for($j = 0; $j < $count_temp_values; $j++)
					{
						$this->current_column = $j;

						$value = $arr_values[$j];

						$strRowField = check_var('strRowCheck'.$j);

						if($strRowField != '')
						{
							$value = str_replace('"', '', $value);

							if(isset($this->validate_columns[$strRowField]))
							{
								$value = check_var($value, $this->validate_columns[$strRowField], false, '', true);
							}

							$value = $this->filter_value($strRowField, $value);

							$this->get_external_value($strRowField, $value);

							if($strRowField != '')
							{
								if(in_array($strRowField, $this->unique_columns))
								{
									$this->query_where .= ($this->query_where != '' ? " ".$this->unique_check." " : "").esc_sql($strRowField)." = '".esc_sql($value)."'";

									if($this->query_where_first == '')
									{
										$this->query_where_first .= esc_sql($strRowField)." = '".esc_sql($value)."'";
									}
								}

								$this->query_set .= ($this->query_set != '' ? ", " : "").esc_sql($strRowField)." = '".esc_sql($value)."'";
								$query_where_fallback .= ($query_where_fallback != '' ? " AND " : "").esc_sql($strRowField)." = '".esc_sql($value)."'";
							}
						}
					}

					if($this->query_where == '')
					{
						$this->query_where = $query_where_fallback;
					}

					if($this->query_set != '' && $this->query_where != '')
					{
						$query_select = "SELECT ".$this->table_id." AS ID FROM ".$this->prefix.$this->table." WHERE ".$this->query_base_where.($this->query_base_where != '' ? " AND " : "")."(".$this->query_where.") ORDER BY ".$table_created." ASC LIMIT 0, 2";

						$result = $wpdb->get_results($query_select);
						$rows = $wpdb->num_rows;

						if($rows > 1)
						{
							$query_select = "SELECT ".$this->table_id." AS ID FROM ".$this->prefix.$this->table." WHERE ".$this->query_base_where.($this->query_base_where != '' ? " AND " : "").$this->query_where_first." ORDER BY ".$table_created." ASC LIMIT 0, 5";

							$result = $wpdb->get_results($query_select);
							$rows = $wpdb->num_rows;
						}

						switch($this->action)
						{
							case 'import':
								if($rows > 0)
								{
									$k = 0;

									foreach($result as $r)
									{
										if($k == 0)
										{
											$id = $r->ID;

											$query_update = "UPDATE ".$this->prefix.$this->table." SET ";

											switch($this->table)
											{
												case 'posts':
													$query_update .= "post_status = 'publish', ";
												break;

												case 'users':
													// Add nothing
												break;

												default:
													$query_update .= $table_field_prefix."Deleted = '0', ".$table_field_prefix."DeletedDate = '', ".$table_field_prefix."DeletedID = '', ";
												break;
											}

											$query_update .= $this->query_set." WHERE ".$this->table_id." = '".$id."'";

											$wpdb->query($query_update);

											$rows_affected = $wpdb->rows_affected;

											$this->update_options($id);

											$rows_affected += $wpdb->rows_affected;

											if($rows_affected > 0)
											{
												$this->updated_new($id);

												if($this->save_result)
												{
													$this->result[] = array(
														'type' => 'updated',
														'action' => 'fa fa-check green',
														'id' => $id,
														'data' => $arr_values,
														'value' => $query_update,
													);
												}

												$this->rows_updated++;
											}

											else
											{
												if($this->save_result)
												{
													$this->result[] = array(
														'type' => 'up_to_date',
														'action' => 'fa fa-cloud blue',
														'id' => $id,
														'data' => $arr_values,
														'value' => $query_update,
													);
												}

												$this->rows_up_to_date++;
											}
										}

										else
										{
											$this->if_more_than_one($r->ID);

											if($this->save_result)
											{
												$this->result[] = array(
													'type' => 'duplicate',
													'action' => 'fa fa-copy',
													'id' => $r->ID,
													'data' => $arr_values,
													'value' => $query_select,
												);
											}
										}

										$k++;
									}
								}

								else
								{
									$query_insert = "INSERT INTO ".$this->prefix.$this->table." SET ".$this->query_set.", ".$table_created." = NOW()";

									if($table_user != '')
									{
										$query_insert .= ", ".$table_user." = '".get_current_user_id()."'";
									}

									$wpdb->query($query_insert);

									if($wpdb->rows_affected > 0)
									{
										$id = $wpdb->insert_id;

										$this->inserted_new($id);
										$this->update_options($id);

										if($this->save_result)
										{
											$this->result[] = array(
												'type' => 'inserted',
												'action' => 'fa fa-plus-circle',
												'id' => $id,
												'data' => $arr_values,
												'value' => $query_insert,
											);
										}

										$this->rows_inserted++;
									}

									else
									{
										if($this->save_result)
										{
											$this->result[] = array(
												'type' => 'not_inserted',
												'action' => 'fa fa-unlink',
												'id' => '',
												'data' => $arr_values,
												'value' => $query_insert,
											);
										}

										$this->rows_not_inserted++;
									}
								}
							break;

							case 'delete':
								if($rows > 0)
								{
									switch($this->table)
									{
										case 'posts':
											$id = $wpdb->get_var("SELECT ".$this->table_id." FROM ".$this->prefix.$this->table." WHERE ".$this->query_base_where.($this->query_base_where != '' ? " AND " : "").$this->query_where);

											wp_trash_post($id);
										break;

										case 'users':
											// Do nothing
										break;

										default:
											$query_delete = $wpdb->prepare("UPDATE ".$this->prefix.$this->table." SET ".$table_field_prefix."Deleted = '1', ".$table_field_prefix."DeletedDate = NOW(), ".$table_field_prefix."DeletedID = '%d' WHERE ".$this->query_base_where.($this->query_base_where != '' ? " AND " : "").$this->query_where, get_current_user_id());

											$wpdb->query($query_delete);
										break;
									}

									if($wpdb->rows_affected > 0)
									{
										if($this->save_result)
										{
											$this->result[] = array(
												'type' => 'deleted',
												'action' => 'fa fa-times',
												'id' => '',
												'data' => $arr_values,
												'value' => $query_delete,
											);
										}

										else
										{
											$this->rows_deleted++;
										}
									}

									else
									{
										if($this->save_result)
										{
											$this->result[] = array(
												'type' => 'not_deleted',
												'action' => 'fa fa-unlink',
												'id' => '',
												'data' => $arr_values,
												'value' => $query_delete,
											);
										}

										else
										{
											$this->rows_not_deleted++;
										}
									}
								}

								else
								{
									if($this->save_result)
									{
										$this->result[] = array(
											'type' => 'not_exists',
											'action' => 'fa fa-question',
											'id' => '',
											'data' => $arr_values,
											'value' => $query_select,
										);
									}

									else
									{
										$this->rows_not_exists++;
									}
								}
							break;

							case 'search':
								if($rows > 0)
								{
									foreach($result as $r)
									{
										$id = $r->ID;

										if($this->save_result)
										{
											$this->result[] = array(
												'type' => 'exists',
												'action' => 'fa fa-check green',
												'id' => $id,
												'data' => $arr_values,
											);
										}

										else
										{
											$this->rows_exists++;
										}
									}
								}

								else
								{
									if($this->save_result)
									{
										$this->result[] = array(
											'type' => 'not_exists',
											'action' => 'fa fa-times red',
											'id' => $id,
											'data' => $arr_values,
										);
									}

									else
									{
										$this->rows_not_exists++;
									}
								}
							break;

							default:
								if($this->save_result)
								{
									$this->result[] = array(
										'type' => '',
										'id' => '',
										'data' => $arr_values,
										'action' => 'fa fa-question',
									);
								}
							break;
						}
					}

					else if($this->save_result)
					{
						$this->result[] = array(
							'type' => '',
							'action' => 'fa fa-heartbeat',
							'data' => $arr_values,
							'value' => var_export($arr_values, true),
						);
					}

					else if(IS_SUPER_ADMIN)
					{
						$error_text = __("Set and Where were not set")." (".$this->query_set." && ".$this->query_where.")";
					}

					if($i % 100 == 0)
					{
						sleep(1);
						set_time_limit(60);
					}
				}

				if($this->save_result)
				{
					if(count($this->result) > 0)
					{
						$arr_export_data = $arr_ids_temp = [];

						foreach($this->result as $key => $row)
						{
							unset($this->result[$key]);

							$data_temp = array(
								$row['type'],
								$row['id'],
							);

							foreach($row['data'] as $value)
							{
								$data_temp[] = $value;
							}

							$data_temp[] = (isset($row['value']) ? $row['value'] : '');

							$arr_export_data[] = $data_temp;
							$arr_ids_temp[] = $row['id'];
						}

						switch($this->action)
						{
							case 'search':
								if(count($arr_ids_temp) > 0)
								{
									$query_select = "";

									foreach($this->columns as $key => $value)
									{
										$query_select .= ($query_select != '' ? ", " : "").$key;
									}

									$result = $wpdb->get_results("SELECT ".$query_select." FROM ".$this->prefix.$this->table." WHERE ".$this->query_base_where.($this->query_base_where != '' ? " AND " : "").$this->table_id." NOT IN('".implode("','", $arr_ids_temp)."')", ARRAY_A);

									foreach($result as $r)
									{
										$data_temp = array(
											'missing',
											$r[$this->table_id],
										);

										foreach($this->columns as $key => $value)
										{
											$data_temp[] = $r[$key];
										}

										$arr_export_data[] = $data_temp;
									}
								}
							break;
						}

						$obj_export = new mf_export(array('plugin' => 'mf_base', 'do_export' => true, 'name' => 'import_result', 'format' => (is_plugin_active("mf_phpexcel/index.php") ? 'xls' : 'csv'), 'data' => $arr_export_data));

						$out .= get_notification();
					}
				}

				else
				{
					$done_text = "";

					if($this->rows_updated > 0)
					{
						$done_text .= ($done_text != '' ? ", " : "").sprintf(__("%d updated", 'lang_base'), $this->rows_updated);
					}

					if($this->rows_up_to_date > 0)
					{
						$done_text .= ($done_text != '' ? ", " : "").sprintf(__("%d up to date", 'lang_base'), $this->rows_up_to_date);
					}

					if($this->rows_inserted > 0)
					{
						$done_text .= ($done_text != '' ? ", " : "").sprintf(__("%d inserted", 'lang_base'), $this->rows_inserted);
					}

					if($this->rows_not_inserted > 0)
					{
						$done_text .= ($done_text != '' ? ", " : "").sprintf(__("%d not inserted", 'lang_base'), $this->rows_not_inserted);

						if(IS_SUPER_ADMIN)
						{
							$done_text .= " (Ex. ".$table_field_prefix." -> ".$table_created." -> ".$query_insert.")";
						}
					}

					if($this->rows_deleted > 0)
					{
						$done_text .= ($done_text != '' ? ", " : "").sprintf(__("%d deleted", 'lang_base'), $this->rows_deleted);
					}

					if($this->rows_not_deleted > 0)
					{
						$done_text .= ($done_text != '' ? ", " : "").sprintf(__("%d not deleted", 'lang_base'), $this->rows_not_deleted);
					}

					if($this->rows_exists > 0)
					{
						$done_text .= ($done_text != '' ? ", " : "").sprintf(__("%d do exist", 'lang_base'), $this->rows_exists);
					}

					if($this->rows_not_exists > 0)
					{
						$done_text .= ($done_text != '' ? ", " : "").sprintf(__("%d do not exist", 'lang_base'), $this->rows_not_exists);
					}

					/*if($done_text == "")
					{
						$done_text .= ($done_text != '' ? ", " : "").__("Nothing was imported", 'lang_base');
						$done_text .= " (".$query_select.")";
					}*/

					$out .= get_notification();
				}
			}

			else
			{
				$error_text = __("The information that you are trying to import has been changed since checked. Please re-check and then run", 'lang_base');

				$out .= get_notification();
			}
		}

		return $out;
	}

	function do_display()
	{
		$out = "";

		if($this->is_run)
		{
			$out .= $this->do_import();
		}

		$out .= $this->get_form();

		return $out;
	}

	function get_form()
	{
		$out = "<form action='#' method='post' class='mf_form mf_settings' enctype='multipart/form-data' id='mf_import'>" // rel='import/check/".get_class($this)."'
			."<div id='poststuff' class='postbox'>
				<h3 class='hndle'>".__("Check", 'lang_base')."</h3>
				<div class='inside'>";

					$arr_data = array(
						'' => "-- ".__("Choose Here", 'lang_base')." --",
					);

					if(count($this->actions) == 0 || in_array('delete', $this->actions))
					{
						$arr_data['delete'] = __("Delete", 'lang_base');
					}

					if(count($this->actions) == 0 || in_array('import', $this->actions))
					{
						$arr_data['import'] = __("Import", 'lang_base');
					}

					if(count($this->actions) == 0 || in_array('search', $this->actions))
					{
						$arr_data['search'] = __("Search", 'lang_base');
					}

					$out .= show_select(array('data' => $arr_data, 'name' => 'strTableAction', 'text' => __("Action", 'lang_base'), 'value' => $this->action, 'required' => true));

					if($this->file_location == '')
					{
						$out .= show_textarea(array('name' => 'strImportText', 'text' => __("Text", 'lang_base'), 'value' => $this->text, 'size' => 'huge', 'placeholder' => __("Value 1	Value 2	Value 3", 'lang_base')));
					}

					if($this->has_excel_support && $this->text == '')
					{
						$out .= show_file_field(array('name' => 'strImportFile', 'text' => __("File", 'lang_base'), 'required' => ($this->file_location != '' ? true : false)));
					}

					$out .= show_select(array('data' => get_yes_no_for_select(array('return_integer' => true)), 'name' => 'intImportSkipHeader', 'value' => $this->skip_header, 'text' => __("Skip First Row", 'lang_base')))
					.show_select(array('data' => get_yes_no_for_select(array('return_integer' => true)), 'name' => 'intImportSaveResult', 'value' => $this->save_result, 'text' => __("Save Result", 'lang_base')))
					.show_button(array('name' => 'btnImportCheck', 'text' => __("Check", 'lang_base')))
				."</div>
			</div>";

			if(!$this->is_run || isset($this->has_unchanged_data) && $this->has_unchanged_data)
			{
				$out .= "<div id='import_result'>"
					.$this->get_result()
				."</div>";
			}

		$out .= "</form>";

		return $out;
	}

	function get_columns_for_select()
	{
		$this->columns_for_select = array(
			'' => "-- ".__("Choose Here", 'lang_base')." --"
		);

		foreach($this->columns as $key => $value)
		{
			$this->columns_for_select[$key] = $value;
		}
	}

	function get_result()
	{
		global $error_text;

		$out = "";

		$count_temp_rows = count($this->data);

		/*if($this->skip_header)
		{
			$count_temp_rows--;
		}*/

		if($this->action != '' && $count_temp_rows > 0)
		{
			$this->get_columns_for_select();

			$out .= "<div id='poststuff' class='postbox'>
				<h3 class='hndle'>".__("Run", 'lang_base')."</h3>
				<div class='inside'>"
					."<p>".__("Rows", 'lang_base').": ".($count_temp_rows + ($this->skip_header ? -1 : 0))."</p>";

					$arr_values = $this->data[0];
					$count_temp_values = count($arr_values);

					for($i = 0; $i < $count_temp_values; $i++)
					{
						$import_text = $arr_values[$i];

						if($error_text == '' && count(array_keys($arr_values, $import_text)) > 1)
						{
							$error_text = __("There are multiple columns with the same name. This might become a problem when importing data.", 'lang_base')." (".$import_text.")";
						}

						$strRowField = check_var('strRowCheck'.$i);

						if($strRowField == '')
						{
							if(isset($this->columns_for_select[$import_text]))
							{
								$strRowField = $this->columns_for_select[$import_text];
							}

							else
							{
								foreach($this->columns_for_select as $key => $value)
								{
									if($value == $import_text || strpos(strtolower($import_text), strtolower($value)) !== false)
									{
										$strRowField = $key;
									}
								}
							}
						}

						$out .= show_select(array('data' => $this->columns_for_select, 'name' => 'strRowCheck'.$i, 'value' => $strRowField, 'text' => __("Column", 'lang_base')." ".($i + 1)." <span>(".$import_text.")</span>"));
					}

					$out .= "&nbsp;"
					.get_notification()
					.show_button(array('name' => 'btnImportRun', 'text' => __("Run", 'lang_base')))
					.wp_nonce_field('import_run', '_wpnonce_import_run', true, false)
					.wp_nonce_field('import_data_'.md5(json_encode($this->data)), '_wpnonce_import_data', true, false)
				."</div>
			</div>
			<div id='poststuff' class='postbox'>
				<h3 class='hndle'>".__("Example", 'lang_base')."</h3>
				<div class='inside'>
					<table class='widefat striped'>";

						for($i = 0; $i < $count_temp_rows && $i < 5; $i++)
						{
							$out .= "<tr>";

								$cell_tag = ($i == 0 && $this->skip_header ? "th" : "td");

								$arr_values = $this->data[$i];
								$count_temp_values = count($arr_values);

								for($j = 0; $j < $count_temp_values; $j++)
								{
									$string = $arr_values[$j];

									if(strlen($string) > 20)
									{
										$string = shorten_text(array('string' => $string, 'limit' => 20));
									}

									$out .= "<".$cell_tag.">".$string."</".$cell_tag.">";
								}

							$out .= "</tr>";
						}

					$out .= "</table>
				</div>
			</div>";
		}

		return $out;
	}
}

class mf_encryption
{
	var $key;
	var $encrypt_method;
	var $iv;

	function __construct($type)
	{
		$this->set_key($type);

		if(function_exists('mcrypt_create_iv') && function_exists('mcrypt_get_iv_size'))
		{
			$this->iv = @mcrypt_create_iv(@mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND);
		}

		else
		{
			$this->encrypt_method = 'AES-256-CBC';

			if(!in_array($this->encrypt_method, openssl_get_cipher_methods()) && !in_array(strtolower($this->encrypt_method), openssl_get_cipher_methods()))
			{
				do_log(__CLASS__.": ".$this->encrypt_method." does not exist in ".var_export(openssl_get_cipher_methods(), true));
			}

			$this->iv = substr(hash('sha256', $this->key), 0, 16);
		}
	}

	function set_key($type)
	{
		if(function_exists('mcrypt_encrypt'))
		{
			$this->key = substr("mf_crypt".$type, 0, 32);
		}

		else
		{
			$this->key = hash('sha256', "mf_crypt".$type);
		}
	}

	function encrypt($text, $key = '')
	{
		if($key != '')
		{
			$this->set_key($key);
		}

		if(function_exists('mcrypt_encrypt'))
		{
			$text = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $this->key, $text, MCRYPT_MODE_ECB, $this->iv));
		}

		else
		{
			$text = base64_encode(openssl_encrypt($text, $this->encrypt_method, $this->key, 0, $this->iv));
		}

		return $text;
	}

	function decrypt($text, $key = '')
	{
		if($text != '' && strlen($text) >= 24)
		{
			if($key != '')
			{
				$this->set_key($key);
			}

			if(function_exists('mcrypt_encrypt'))
			{
				$text = trim(@mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $this->key, base64_decode($text), MCRYPT_MODE_ECB, $this->iv));
			}

			else
			{
				$text = openssl_decrypt(base64_decode($text), $this->encrypt_method, $this->key, 0, $this->iv);
			}
		}

		return $text;
	}
}