<?php

class mf_base
{
	function __construct()
	{
		$this->meta_prefix = "mf_base_";
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

	function init()
	{
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
		define('IS_ADMIN', $is_admin);
		define('IS_EDITOR', $is_editor);
		define('IS_AUTHOR', $is_author);

		$timezone_string = get_option('timezone_string');

		if($timezone_string != '')
		{
			date_default_timezone_set($timezone_string);
		}

		$this->reschedule_base();
	}

	function cron_schedules($schedules)
	{
		//$schedules['every_ten_seconds'] = array('interval' => 10, 'display' => "Manually");
		$schedules['every_two_minutes'] = array('interval' => 60 * 2, 'display' => __("Every 2 Minutes", 'lang_base'));
		$schedules['every_ten_minutes'] = array('interval' => 60 * 10, 'display' => __("Every 10 Minutes", 'lang_base'));

		$schedules['weekly'] = array('interval' => 60 * 60 * 24 * 7, 'display' => __("Weekly", 'lang_base'));
		$schedules['monthly'] = array('interval' => 60 * 60 * 24 * 7 * 4, 'display' => __("Monthly", 'lang_base'));

		return $schedules;
	}

	function run_cron_start()
	{
		update_option('option_cron_started', date("Y-m-d H:i:s"), 'no');
	}

	function run_cron_end()
	{
		update_option('option_cron_run', date("Y-m-d H:i:s"), 'no');
		delete_option('option_cron_started');
	}

	function has_page_template($data = array())
	{
		global $wpdb;

		if(!isset($data['template'])){		$data['template'] = "/plugins/mf_base/include/templates/template_admin.php";}

		$post_id = $wpdb->get_var($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE post_type = %s AND meta_key = %s AND meta_value = %s LIMIT 0, 1", 'page', '_wp_page_template', $data['template']));

		return $post_id;
	}

	function wp_before_admin_bar_render()
	{
		global $wp_admin_bar;

		$post_id = $this->has_page_template();

		if($post_id)
		{
			$post_status = get_post_status($post_id);

			$color = $title = "";

			switch($post_status)
			{
				case 'publish':
					$color = "color_green";
				break;

				case 'draft':
					if(IS_ADMIN)
					{
						$color = "color_yellow";
						$title = __("Not Published", 'lang_base');
					}
				break;
			}

			if($color != '')
			{
				$wp_admin_bar->add_node(array(
					'id' => 'front-end',
					'title' => "<a href='".get_permalink($post_id)."' class='".$color."'".($title != '' ? " title='".$title."'" : '').">".get_post_title($post_id)."</a>",
				));
			}
		}
	}

	function settings_base()
	{
		define('BASE_OPTIONS_PAGE', "settings_mf_base");

		$options_area = __FUNCTION__;

		add_settings_section($options_area, "",	array($this, $options_area."_callback"), BASE_OPTIONS_PAGE);

		$arr_settings = array(
			'setting_base_info' => __("Status", 'lang_base'),
			'setting_base_cron' => __("Scheduled to run", 'lang_base'),
		);

		if($this->has_page_template() > 0)
		{
			$arr_settings['setting_base_front_end_admin'] = __("Front-End Admin", 'lang_base');
		}

		if(IS_SUPER_ADMIN)
		{
			$arr_settings['setting_base_recommend'] = __("Recommendations", 'lang_base');
		}

		show_settings_fields(array('area' => $options_area, 'object' => $this, 'settings' => $arr_settings));
	}

	function settings_base_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);

		echo settings_header($setting_key, __("Common", 'lang_base'));
	}

	function return_bytes($value)
	{
		$number = substr($value, 0, -1);
		$suffix = strtoupper(substr($value, -1));

		if($number > 0)
		{
			switch($suffix)
			{
				case 'G':
					$number *= pow(1024, 3);
				break;

				case 'M':
					$number *= pow(1024, 2);
				break;

				case 'K':
					$number *= 1024;
				break;

				default:
					do_log("There was no suffix in return_bytes() (".$value.")");
				break;
			}
		}

		else
		{
			do_log("The value was nothing in return_bytes() (".$value.")");
		}

		return $number;
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

		$has_required_php_version = version_compare($php_version, $php_required, ">");
		$has_required_mysql_version = version_compare($mysql_version, $mysql_required, ">");

		$db_date = strtotime($wpdb->get_var("SELECT LOCALTIME()"));
		$ftp_date = strtotime(date("Y-m-d H:i:s"));
		$date_diff = abs($db_date - $ftp_date);

		$memory_limit = $this->return_bytes(ini_get('memory_limit'));

		$total_space = @disk_total_space('/');
		$free_space = @disk_free_space('/');

		if($total_space > 0)
		{
			$free_percent = ($free_space / $total_space) * 100;
		}

		$load = sys_getloadavg();

		/*$memory_used = memory_get_usage();
		$memory_allocated = memory_get_usage(true);
		$memory_peak_used = memory_get_peak_usage();
		$memory_peak_allocated = memory_get_peak_usage(false);*/

		echo "<div class='flex_flow'>
			<div>
				<p><i class='".($has_required_php_version ? "fa fa-check green" : "fa fa-times red display_warning")."'></i> ".__("PHP", 'lang_base').": ".$php_version."</p>
				<p><i class='".($has_required_mysql_version ? "fa fa-check green" : "fa fa-times red display_warning")."'></i> ".__("MySQL", 'lang_base').": ".$mysql_version."</p>";

				if(!($has_required_php_version && $has_required_mysql_version))
				{
					echo "<p><a href='//wordpress.org/about/requirements/'>".__("Requirements", 'lang_base')."</a></p>";
				}

				if($date_diff > 60)
				{
					echo "<p><i class='".($date_diff < 60 ? "fa fa-check green" : "fa fa-times red display_warning")."'></i> Time Difference: ".format_date(date("Y-m-d H:i:s", $ftp_date))." (".__("PHP", 'lang_base')."), ".format_date(date("Y-m-d H:i:s", $db_date))." (".__("MySQL", 'lang_base').")</p>";
				}

				else
				{
					echo "<p><i class='fa fa-check green'></i> ".__("Time on Server", 'lang_base').": ".format_date(date("Y-m-d H:i:s", $ftp_date))."</p>";
				}

				if(isset($free_percent))
				{
					echo "<p>
						<i class='".($free_percent > 10 ? "fa fa-check green" : "fa fa-times red display_warning")."'></i> "
						.__("Disc Space", 'lang_base').": ".mf_format_number($free_percent, 0)."% (".show_final_size($free_space)." / ".show_final_size($total_space).")"
					."</p>";
				}

			echo "</div>
			<div>
				<p>
					<i class='".($memory_limit > 200 * pow(1024, 2) ? "fa fa-check green" : "fa fa-times red display_warning")."'></i> "
					.__("Memory Limit", 'lang_base').": ".show_final_size($memory_limit)
				."</p>
				<p><i class='".($load[0] < 1 ? "fa fa-check green" : "fa fa-times red")."'></i> ".__("Load", 'lang_base')." &lt; 1 ".__("min", 'lang_base').": ".mf_format_number($load[0])."</p>
				<p><i class='".($load[1] < 1 ? "fa fa-check green" : "fa fa-times red")."'></i> ".__("Load", 'lang_base')." &lt; 5 ".__("min", 'lang_base').": ".mf_format_number($load[1])."</p>
				<p><i class='".($load[2] < 1 ? "fa fa-check green" : "fa fa-times red")."'></i> ".__("Load", 'lang_base')." &lt; 15 ".__("min", 'lang_base').": ".mf_format_number($load[2])."</p>"
				//."<p><i class='".($memory_used < ($memory_total * .8) ? "fa fa-check green" : "fa fa-times red")."'></i> "."Memory".": ".mf_format_number(($memory_used / $memory_total) * 100)."% (".$memory_used." / ".$memory_total.")</p>"
			."</div>
		</div>";
	}

	function setting_base_cron_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option($setting_key, 'every_ten_minutes');

		$this->reschedule_base($option);

		if(!defined('DISABLE_WP_CRON') || DISABLE_WP_CRON == false)
		{
			$arr_schedules = wp_get_schedules();

			$arr_data = array();

			foreach($arr_schedules as $key => $value)
			{
				$arr_data[$key] = $value['display'];
			}

			$next_cron = get_next_cron();

			if($next_cron != '')
			{
				$select_suffix = sprintf(__("Next scheduled %s", 'lang_base'), $next_cron);
			}

			else
			{
				$select_suffix = "";
			}

			echo show_select(array('data' => $arr_data, 'name' => 'setting_base_cron', 'value' => $option, 'suffix' => $select_suffix));
		}

		if(defined('DISABLE_WP_CRON') && DISABLE_WP_CRON == true || get_next_cron(true) < date("Y-m-d H:i:s", strtotime("-2 minute")))
		{
			$cron_url = get_site_url()."/wp-cron.php?doing_wp_cron";

			echo "<a href='".$cron_url."'>".__("Run schedule manually", 'lang_base')."</a> ";
		}

		$option_cron_started = get_option('option_cron_started');
		$option_cron_run = get_option('option_cron_run');

		if($option_cron_run != '' && $option_cron_run > $option_cron_started)
		{
			echo "<em>".sprintf(__("Last run %s", 'lang_base'), format_date($option_cron_run))."</em>";
		}

		else if($option_cron_started > $option_cron_run)
		{
			echo "<em>".sprintf(__("Last started %s but has not finished", 'lang_base'), format_date($option_cron_started))."</em>";
		}

		else
		{
			echo "<em>".__("Has never been run", 'lang_base')."</em>";
		}
	}

	function get_front_end_views_for_select()
	{
		$arr_data = array();

		$arr_views = apply_filters('init_base_admin', array());

		foreach($arr_views as $key => $view)
		{
			$arr_data[$key] = $view['name'];
		}

		return $arr_data;
	}

	function setting_base_front_end_admin_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option($setting_key);

		echo show_select(array('data' => $this->get_front_end_views_for_select(), 'name' => $setting_key."[]", 'value' => $option));
	}

	function setting_base_recommend_callback()
	{
		get_file_info(array('path' => get_home_path(), 'callback' => array($this, 'check_htaccess'), 'allow_depth' => false));

		$arr_recommendations = array(
			array("Advanced Cron Manager", 'advanced-cron-manager/advanced-cron-manager.php', __("to debug Cron", 'lang_base')),
			array("ARI Adminer", 'ari-adminer/ari-adminer.php', __("to get a graphical interface to the database", 'lang_base')),
			array("BackWPup", 'backwpup/backwpup.php', __("to backup all files and database to an external source", 'lang_base')),
			array("Enable Media Replace", 'enable-media-replace/enable-media-replace.php', __("to be able to replace existing files by uploading a replacement", 'lang_base')),
			array("Favicon by RealFaviconGenerator", 'favicon-by-realfavicongenerator/favicon-by-realfavicongenerator.php', __("to add all the favicons needed", 'lang_base')),
			array("Plugin Dependencies", 'plugin-dependencies/plugin-dependencies.php', __("to display which plugin dependencies there are and prevent accidental deactivation of plugins that others depend on", 'lang_base')),
			array("Post Notification by Email", 'notify-users-e-mail/notify-users-e-mail.php', __("to send notifications to users when new posts are published", 'lang_base')),
			array("Quick Page/Post Redirect Plugin", 'quick-pagepost-redirect-plugin/page_post_redirect_plugin.php', __("to redirect pages to internal or external URLs", 'lang_base')),
			array("Simple Page Ordering", 'simple-page-ordering/simple-page-ordering.php', __("to reorder posts with drag & drop", 'lang_base')),
			array("TablePress", 'tablepress/tablepress.php', __("to be able to add tables to posts", 'lang_base')),
			array("Username Changer", 'username-changer/username-changer.php', __("to be able to change usernames", 'lang_base')),
			array("WP Video Lightbox", 'wp-video-lightbox/wp-video-lightbox.php', __("to be able to view video clips in modals", 'lang_base')),
		);

		if(!(is_plugin_active('tiny-compress-images/tiny-compress-images.php') || is_plugin_active('optimus/optimus.php') || is_plugin_active('wp-smushit/wp-smush.php')))
		{
			$arr_recommendations[] = array("Compress JPEG & PNG images", 'tiny-compress-images/tiny-compress-images.php', __("to losslessly compress all uploaded images (Max 500 for free / month)", 'lang_base'));
			$arr_recommendations[] = array("Optimus", 'optimus/optimus.php', __("to losslessly compress all uploaded images (Max 100kB/file for free)", 'lang_base'));
			$arr_recommendations[] = array("Smush Image Compression and Optimization", 'wp-smushit/wp-smush.php', __("to losslessly compress all uploaded images", 'lang_base'));
		}

		foreach($arr_recommendations as $value)
		{
			$name = $value[0];
			$path = $value[1];
			$text = isset($value[2]) ? $value[2] : "";

			new recommend_plugin(array('path' => $path, 'name' => $name, 'text' => $text, 'show_notice' => false));
		}
	}

	function admin_init()
	{
		global $pagenow;

		$this->wp_head();

		$plugin_include_url = plugin_dir_url(__FILE__);
		$plugin_version = get_plugin_version(__FILE__);

		//add_editor_style($plugin_include_url."font-awesome-5.7.2.php");
		//add_editor_style($plugin_include_url."style_editor.css");

		mf_enqueue_style('style_base_wp', $plugin_include_url."style_wp.css", $plugin_version);
		wp_enqueue_script('jquery-ui-autocomplete');
		mf_enqueue_script('script_base_wp', $plugin_include_url."script_wp.js", array('plugins_url' => plugins_url(), 'ajax_url' => admin_url('admin-ajax.php'), 'toggle_all_data_text' => __("Toggle All Data", 'lang_base')), $plugin_version);

		if($pagenow == 'options-general.php' && check_var('page') == 'settings_mf_base')
		{
			mf_enqueue_style('style_base_settings', $plugin_include_url."style_settings.css", $plugin_version);
			mf_enqueue_script('script_base_settings', $plugin_include_url."script_settings.js", array('default_tab' => "settings_base", 'settings_page' => true), $plugin_version);
		}

		if(in_array($pagenow, array('post.php', 'page.php', 'post-new.php', 'post-edit.php')))
		{
			mf_enqueue_script('script_base_shortcode', $plugin_include_url."script_shortcode.js", $plugin_version);
		}

		/*else if($pagenow == 'widgets.php')
		{
			mf_enqueue_script('script_base_meta', $plugin_include_url."script_meta.js", $plugin_version);
		}*/
	}

	function plugin_action_links($actions, $plugin_file)
	{
		if(array_key_exists('deactivate', $actions) && in_array($plugin_file, array('mf_base/index.php')))
		{
			unset($actions['deactivate']);
		}

		return $actions;
	}

	function media_buttons_context($button)
	{
		global $pagenow;

		$out = "";

		if(in_array($pagenow, array('post.php', 'page.php', 'post-new.php', 'post-edit.php')))
		{
			$count_shortcode_button = apply_filters('count_shortcode_button', 0);

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

	function admin_footer()
	{
		global $pagenow;

		if(in_array($pagenow, array('post.php', 'page.php', 'post-new.php', 'post-edit.php')))
		{
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

	function rwmb_meta_boxes($meta_boxes)
	{
		$meta_boxes[] = array(
			'id' => $this->meta_prefix.'content',
			'title' => __("Added Content", 'lang_base'),
			'post_types' => array('page'),
			//'context' => 'side',
			'priority' => 'low',
			'fields' => array(
				array(
					'id' => $this->meta_prefix.'content',
					'type' => 'custom_html',
					'callback' => array($this, 'meta_page_content'),
				),
			)
		);

		return $meta_boxes;
	}

	function rwmb_enqueue_scripts()
	{
		mf_enqueue_script('script_base_meta', plugin_dir_url(__FILE__)."script_meta.js", get_plugin_version(__FILE__));
	}

	function check_notifications()
	{
		$array = apply_filters('get_user_notifications', array());

		$result = array(
			'success' => true,
			'notifications' => $array,
		);

		header('Content-Type: application/json');
		echo json_encode($result);
		die();
	}

	function init_base_admin($arr_views)
	{
		$templates = "";
		$plugin_include_url = plugin_dir_url(__FILE__);
		$plugin_version = get_plugin_version(__FILE__);

		if(!is_admin())
		{
			mf_enqueue_style('style_base_admin', $plugin_include_url."style_admin.css", $plugin_version);

			mf_enqueue_script('underscore');
			mf_enqueue_script('backbone');
			mf_enqueue_script('script_base_plugins', $plugin_include_url."backbone/bb.plugins.js", $plugin_version);

			mf_enqueue_script('script_base_admin_router', $plugin_include_url."backbone/bb.admin.router.js", $plugin_version);
			mf_enqueue_script('script_base_admin_models', $plugin_include_url."backbone/bb.admin.models.js", array('plugin_url' => $plugin_include_url), $plugin_version);
			mf_enqueue_script('script_base_admin_views', $plugin_include_url."backbone/bb.admin.views.js", array(), $plugin_version);

			$templates .= "<script type='text/template' id='template_admin_profile_edit'>
				<form method='post' action='' class='mf_form' data-api-url='".$plugin_include_url."' data-action='admin/profile/save'>
					<% _.each(fields, function(field)
					{
						switch(field.type)
						{
							case 'date': %>"
								.show_textfield(array('type' => 'date', 'name' => "<%= field.name %>", 'text' => "<%= field.text %>", 'value' => "<%= field.value %>"))
							."<% break;

							case 'email': %>"
								.show_textfield(array('type' => 'email', 'name' => "<%= field.name %>", 'text' => "<%= field.text %>", 'value' => "<%= field.value %>"))
							."<% break;

							case 'flex_start': %>
								<div class='flex_flow'>
							<% break;

							case 'flex_end': %>
								</div>
							<% break;

							case 'media_image': %>
								<div>
									<label for='<%= field.name %>'><%= field.text %></label>"
									.get_media_library(array('name' => "<%= field.name %>", 'value' => "<%= field.value %>", 'type' => 'image'))
								."</div>
							<% break;

							case 'number': %>"
								.show_textfield(array('type' => 'number', 'name' => "<%= field.name %>", 'text' => "<%= field.text %>", 'value' => "<%= field.value %>"))
							."<% break;

							case 'password': %>
								<div class='form_button'>
									<label><%= field.text %></label>
									<a href='".admin_url("profile.php")."' class='button'>".__("Change Password", 'lang_base')."</a></div>
								</div>"
								//.show_password_field(array('name' => "<%= field.name %>", 'text' => "<%= field.text %>"))
							."<% break;

							case 'select': %>
								<div class='form_select type_<%= field.type %><%= field.class %>'>
									<label for='<%= field.name %>'><%= field.text %></label>
									<select id='<%= field.name %>' name='<%= field.name %><% if(field.multiple == true){ %>[]<% } %>'<% if(field.multiple == true){ %> multiple<% } %><%= field.attributes %>>
										<% _.each(field.options, function(option)
										{%>
											<% if(option.key.toString().substr(0, 9) == 'opt_start')
											{ %>
												<optgroup label='<%= option.value %>' rel='<%= option.key %>'>
											<% }

											else if(option.key.toString().substr(0, 7) == 'opt_end')
											{ %>
												</optgroup>
											<% }

											else
											{ %>
												<option value='<%= option.key %>'<% if(option.key == field.value || field.multiple == true && field.value.indexOf(option.key.toString()) !== -1){%> selected<%} %>><%= option.value %></option>
											<% } %>
										<% }); %>
									</select>
								</div>
							<% break;

							case 'text': %>"
								.show_textfield(array('name' => "<%= field.name %>", 'text' => "<%= field.text %>", 'value' => "<%= field.value %>"))
							."<% break;

							default: %>
								<strong><%= meta_field.type %></strong>: <%= meta_field.name %><br>
							<% break;
						}
					}); %>
					<div class='form_button'>"
						.show_button(array('text' => __("Update", 'lang_base')))
					."</div>
				</form>
			</script>";
		}

		$arr_views['profile'] = array(
			'name' => __("Profile", 'lang_base'),
			'icon' => "far fa-user-circle",
			'items' => array(
				array(
					'id' => 'edit',
					'name' => __("Edit Profile", 'lang_base'),
				),
			),
			'templates' => $templates,
			'api_url' => $plugin_include_url,
		);

		return $arr_views;
	}

	function init_base_admin_2($arr_views)
	{
		if(!is_admin())
		{
			$plugin_include_url = plugin_dir_url(__FILE__);
			$plugin_version = get_plugin_version(__FILE__);

			mf_enqueue_script('script_base_init', $plugin_include_url."backbone/bb.init.js", $plugin_version);

			$setting_base_front_end_admin = get_option('setting_base_front_end_admin');

			if(is_array($setting_base_front_end_admin) && count($setting_base_front_end_admin) > 0)
			{
				foreach($arr_views as $key => $view)
				{
					if(!in_array($key, $setting_base_front_end_admin))
					{
						unset($arr_views[$key]);
					}
				}
			}
		}

		return $arr_views;
	}

	function login_init()
	{
		$this->wp_head();
	}

	function wp_head()
	{
		$plugin_include_url = plugin_dir_url(__FILE__);
		$plugin_version = get_plugin_version(__FILE__);

		mf_enqueue_style('font-awesome', $plugin_include_url."font-awesome-5.7.2.php", $plugin_version);
		mf_enqueue_style('style_base', $plugin_include_url."style.css", $plugin_version);
		mf_enqueue_script('script_base', $plugin_include_url."script.js", array('confirm_question' => __("Are you sure?", 'lang_base'), 'read_more' => __("Read More", 'lang_base')), $plugin_version);
	}

	function phpmailer_init($phpmailer)
	{
		if($phpmailer->ContentType == 'text/html')
		{
			$phpmailer->AltBody = strip_tags($phpmailer->Body);
		}
	}

	function shortcode_file($atts)
	{
		extract(shortcode_atts(array(
			'id' => 0,
			'filetype' => '',
		), $atts));

		$out = "";

		switch($filetype)
		{
			case 'gif':
			case 'jpg':
			case 'jpeg':
			case 'png':
				$out .= render_image_tag(array('id' => $id));
			break;

			default:
				$file_name = basename(get_attached_file($id));
				$file_url = wp_get_attachment_url($id);

				$out .= "<a href='".$file_url."' rel='external'>".$file_name."</a>";
			break;
		}

		return $out;
	}

	function get_page_templates($templates)
	{
		$templates_path = str_replace(WP_CONTENT_DIR, "", plugin_dir_path(__FILE__))."templates/";

		$templates[$templates_path.'template_admin.php'] = __("Front-End Admin", 'lang_base');

		return $templates;
	}

	function theme_page_templates($posts_templates)
	{
		if(!isset($this->templates))
		{
			$this->templates = apply_filters('get_page_templates', array());
		}

		$posts_templates = array_merge($posts_templates, $this->templates);

		return $posts_templates;
	}

	function wp_insert_post_data($atts)
	{
		if(!isset($this->templates))
		{
			$this->templates = apply_filters('get_page_templates', array());
		}

		// Create the key used for the themes cache
		$cache_key = "page_templates-".md5(get_theme_root()."/".get_stylesheet());

		// Retrieve the cache list. If it doesn't exist, or it's empty prepare an array
		$templates = wp_get_theme()->get_page_templates();

		if(empty($templates))
		{
			$templates = array();
		}

		// New cache, therefore remove the old one
		wp_cache_delete($cache_key , 'themes');

		// Now add our template to the list of templates by merging our templates with the existing templates array from the cache.
		$templates = array_merge($templates, $this->templates);

		// Add the modified cache to allow WordPress to pick it up for listing available templates
		wp_cache_add($cache_key, $templates, 'themes', 1800);

		return $atts;
	}

	// Checks if the template is assigned to the page
	function template_include($template)
	{
		global $post;

		// Return template if post is empty
		if(!$post)
		{
			return $template;
		}

		if(!isset($this->templates))
		{
			$this->templates = apply_filters('get_page_templates', array());
		}

		$template_temp = get_post_meta($post->ID, '_wp_page_template', true);

		// Return default template if we don't have a custom one defined
		if(!isset($this->templates[$template_temp]))
		{
			return $template;
		}

		$file = WP_CONTENT_DIR.$template_temp; //plugin_dir_path(__FILE__)."templates/".

		// Just to be safe, we check if the file exist first
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
	function check_htaccess($data)
	{
		if(basename($data['file']) == ".htaccess")
		{
			$content = get_file_content(array('file' => $data['file']));

			/*$this->all_is_https = true;
			$this->recommend_htaccess = $this->recommend_htaccess_https = "";

			if(is_multisite())
			{
				$result = get_sites(array('deleted' => 0));

				foreach($result as $r)
				{
					$this->get_site_redirect($r->blog_id);
				}
			}

			else
			{
				$this->get_site_redirect();
			}*/

			$recommend_htaccess = "ServerSignature Off

			DirectoryIndex index.php
			Options -Indexes";

			/* Some hosts don't allow this */
			/*<FILES ~ '^.*\.([Hh][Tt][Aa])'>
				Order Allow,Deny
				Deny from all
			</FILES>

			<FILES wp-config.php>
				Order Allow,Deny
				Deny from all
			</FILES>*/

			/*if($this->recommend_htaccess != '')
			{
				$recommend_htaccess .= "\n
				RewriteEngine On".$this->recommend_htaccess;

				if($this->all_is_https == true)
				{
					$recommend_htaccess .= "\n
					RewriteCond %{HTTPS} !=on
					RewriteCond %{ENV:HTTPS} !=on
					RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]";
				}

				else if($this->recommend_htaccess_https != '')
				{
					$recommend_htaccess .= $this->recommend_htaccess_https;
				}
			}*/

			$recommend_htaccess .= "\n
			RewriteRule ^my_ip$ /wp-content/plugins/mf_base/include/my_ip/ [L]";

			$old_md5 = get_match("/BEGIN MF Base \((.*?)\)/is", $content, false);
			$new_md5 = md5($recommend_htaccess);

			if($new_md5 != $old_md5)
			{
				echo "<div class='mf_form'>"
					."<h3 class='display_warning'><i class='fa fa-exclamation-triangle yellow'></i> ".sprintf(__("Add this to the beginning of %s", 'lang_base'), ".htaccess")."</h3>"
					."<p class='input'>".nl2br("# BEGIN MF Base (".$new_md5.")\n".htmlspecialchars($recommend_htaccess)."\n# END MF Base")."</p>"
				."</div>";
			}
		}
	}

	function get_site_redirect($site_id = 0)
	{
		$site_url = ($site_id > 0 ? get_home_url($site_id) : get_home_url());
		$site_url_clean = remove_protocol(array('url' => $site_url, 'clean' => true));

		$is_https = (substr($site_url, 0, 5) == 'https');
		$has_www = "www." == substr($site_url_clean, 0, 4);
		$is_subdomain = substr_count($site_url_clean, '.') > ($has_www ? 2 : 1);
		$is_subfolder = substr_count($site_url_clean, '/') > 0;

		if(!is_multisite() || (!$is_subdomain && !$is_subfolder))
		{
			$site_url_clean_opposite = $has_www ? substr($site_url_clean, 4) : "www.".$site_url_clean;

			$this->recommend_htaccess .= "\n
			RewriteCond	%{HTTP_HOST}		^".$site_url_clean_opposite."$		[NC]
			RewriteRule	^(.*)$				".$site_url."/$1					[L,R=301]";

			if($is_https)
			{
				$this->recommend_htaccess_https .= "\n
				RewriteCond %{HTTP_HOST}	^".$site_url_clean."$				[NC]
				RewriteCond	%{HTTPS}		off
				RewriteRule	^(.*)$			".$site_url."/$1					[R=301,L]";
			}
		}

		if(!$is_https)
		{
			$this->all_is_https = false;
		}
	}
	############################

	function get_templates($arr_type = array())
	{
		$out = "";

		if(in_array('lost_connection', $arr_type))
		{
			$out .= "<div id='overlay_lost_connection'><span>".__("Lost Connection", 'lang_base')."</span></div>";
		}

		if(in_array('loading', $arr_type))
		{
			$out .= "<div id='overlay_loading'><span><i class='fa fa-spinner fa-spin fa-2x'></i></span></div>";
		}

		return $out;
	}
}

class mf_cron
{
	function __construct()
	{
		$this->schedules = wp_get_schedules();

		$this->date_start = date("Y-m-d H:i:s");
	}

	function start($type)
	{
		global $wpdb;

		list($upload_path, $upload_url) = get_uploads_folder();

		$this->file = $upload_path.".is_running_".$wpdb->prefix.trim($type, "_");

		$this->set_is_running();

		$success = set_file_content(array('file' => $this->file, 'mode' => 'w', 'content' => date("Y-m-d H:i:s")));

		if(!$success)
		{
			do_log(sprintf("I could not create %s, please make sure that I have access to create this file in order for schedules to work as intended", $this->file));
		}
	}

	function get_interval()
	{
		$setting_base_cron = get_option('setting_base_cron');

		return $this->schedules[$setting_base_cron]['interval'];
	}

	function set_is_running()
	{
		$this->is_running = file_exists($this->file);

		if($this->is_running)
		{
			$file_time = date("Y-m-d H:i:s", filemtime($this->file));

			if($file_time > DEFAULT_DATE && $this->has_expired(array('start' => $file_time, 'margin' => 1.2)))
			{
				do_log(sprintf("%s has been running since %s", $this->file, $file_time));
			}
		}
	}

	function has_expired($data = array())
	{
		if(!isset($data['start'])){			$data['start'] = $this->date_start;}
		if(!isset($data['end'])){			$data['end'] = date("Y-m-d H:i:s");}
		if(!isset($data['margin'])){		$data['margin'] = 1;}

		$time_difference = time_between_dates(array('start' => $data['start'], 'end' => $data['end'], 'type' => 'ceil', 'return' => 'seconds'));

		return $time_difference >= ($this->get_interval() * $data['margin']);
	}

	function end()
	{
		if(file_exists($this->file))
		{
			unlink($this->file);
		}
	}
}

class recommend_plugin
{
	function __construct($data)
	{
		global $pagenow;

		if(!isset($data['url'])){			$data['url'] = "";}
		if(!isset($data['show_notice'])){	$data['show_notice'] = true;}
		if(!isset($data['text'])){			$data['text'] = "";}

		if(!is_plugin_active($data['path']))
		{
			list($a_start, $a_end) = get_install_link_tags($data['url'], $data['name']);

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

if(!class_exists('WP_List_Table'))
{
	/*$hook_suffix = '';
	if ( isset( $page_hook ) ) {
		$hook_suffix = $page_hook;
	} elseif ( isset( $plugin_page ) ) {
		$hook_suffix = $plugin_page;
	} elseif ( isset( $pagenow ) ) {
		$hook_suffix = $pagenow;
	}*/

	$GLOBALS['hook_suffix'] = '';

	require_once(ABSPATH.'wp-admin/includes/admin.php');

	// Needed when displaying tables in Front-End Admin
	/*if(!class_exists('WP_Screen'))
	{
		require_once(ABSPATH.'wp-admin/includes/screen.php');
		require_once(ABSPATH.'wp-admin/includes/class-wp-screen.php');
	}

	require_once(ABSPATH.'wp-admin/includes/template.php');
	require_once(ABSPATH.'wp-admin/includes/class-wp-list-table.php');*/
}

class mf_list_table extends WP_List_Table
{
	var $arr_settings = array();
	var $post_type = "";
	var $orderby_default = "post_title";
	var $orderby_default_order = "ASC";

	var $views = array();
	var $columns = array();
	var $sortable_columns = array();
	var $data = "";
	var $data_full = "";
	var $num_rows = 0;
	var $query_join = "";
	var $query_where = "";
	var $search = "";
	var $orderby = "";
	var $order = "";
	var $page = "";
	var $total_pages = "";

	function __construct($data = array())
	{
		global $wpdb;

		parent::__construct(array(
			'singular' => '', //singular name of the listed records
			'plural' => '', //plural name of the listed records
			'ajax' => false //does this table support ajax?
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
		$this->search = check_var('s', 'char', true, (isset($data['search']) ? $data['search'] : ''));

		$this->set_default();

		if($data['remember_search'] == true)
		{
			$this->search = get_or_set_table_filter(array('prefix' => ($this->post_type != '' ? $this->post_type : $this->table)."_", 'key' => 's', 'save' => true));
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
			$empty_trash_days = (defined('EMPTY_TRASH_DAYS') ? EMPTY_TRASH_DAYS : 30) * 2;

			$wpdb->get_results("SELECT ".$this->arr_settings['query_select_id']." FROM ".$this->arr_settings['query_from']." WHERE ".$db_field." = '1' AND ".$db_field."Date < DATE_SUB(NOW(), INTERVAL ".$empty_trash_days." DAY) LIMIT 0, 1");

			if($wpdb->num_rows > 0)
			{
				$error_text = sprintf(__("Use %s on %s"), "delete_base()", $db_field);

				do_log($error_text);
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

		if(isset($this->query_group))
		{
			if($this->query_group != '')
			{
				$query_group = " GROUP BY ".$this->query_group;
			}
		}

		else
		{
			$query_group = " GROUP BY ".$this->arr_settings['query_select_id'];
		}

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

				$this->views[$key] = "<a href='admin.php?page=".$this->page."&".$data['db_field']."=".$key.$url_xtra."'".($key == $db_value ? " class='current'" : "").">".$value." <span class='count'>(".$amount.")</span></a>";
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
		$actions = array();

		if(isset($this->columns['cb']))
		{
			$actions['delete'] = __("Delete", 'lang_base');
		}

		return $actions;
	}

	/** ************************************************************************
	 * Optional. You can handle your bulk actions anywhere or anyhow you prefer.
	 * For this example package, we will handle it in the class to keep things
	 * clean and organized.
	 *
	 * @see $this->prepare_items()
	 **************************************************************************/
	function bulk_delete()
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

	function process_bulk_action()
	{
		if(isset($_GET['_wpnonce']) && !empty($_GET['_wpnonce']))
		{
			switch($this->current_action())
			{
				case 'delete':
					$this->bulk_delete();
				break;
			}
		}
	}

	protected function extra_tablenav( $which )
	{
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
				do_action('restrict_manage_posts', ($this->arr_settings['query_from'] != '' ? $this->arr_settings['query_from'] : $this->post_type), $which); //$this->screen->post_type

				$output = ob_get_clean();

				if(!empty($output))
				{
					echo $output;

					submit_button(__("Filter", 'lang_base'), '', 'filter_action', false, array('id' => 'post-query-submit'));
				}
			}

			if($this->is_trash && current_user_can(get_post_type_object($this->screen->post_type)->cap->edit_others_posts) && $this->has_items())
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
		global $wpdb; //This is used only if making any database queries

		/**
		 * REQUIRED. Now we need to define our column headers. This includes a complete array of columns to be displayed (slugs & titles), a list of columns to keep hidden, and a list of columns that are sortable. Each of these can be defined in another method (as we've done here) before being used to build the value for our _column_headers property.
		 */
		$hidden = array();

		/**
		 * REQUIRED. Finally, we build an array to be used by the class for column headers. The $this->_column_headers property takes an array which contains 3 other arrays. One for all columns, one for hidden columns, and one for sortable columns.
		 */
		$this->_column_headers = array($this->columns, $hidden, $this->sortable_columns);

		$current_page = $this->get_pagenum();

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
		return array('widefat', 'striped'); //, 'fixed', $this->_args['plural']
	}

	function search_box($text, $input_id)
	{
		if($this->search != '' || $this->has_items() || isset($this->arr_settings['force_search']) && $this->arr_settings['force_search'])
		{
			$input_id = esc_attr($input_id."-search-input");

			echo "<p class='search-box'>";

				//echo "<label class='screen-reader-text' for='".$input_id."'>".$text.":</label>";

				echo "<input type='search' id='".$input_id."' name='s' value='".$this->search."'>";

				submit_button($text, '', '', false, array('id' => 'search-submit'));

				$arr_var_keys = array('orderby', 'order', 'post_status'); //post_mime_type, detached

				foreach($arr_var_keys as $var_key)
				{
					if(!empty($_REQUEST[$var_key]))
					{
						echo input_hidden(array('name' => $var_key, 'value' => check_var($var_key)));
					}
				}

			echo "</p>";
		}
	}

	function show_search_form()
	{
		echo "<form method='get'".($this->arr_settings['has_autocomplete'] == true ? " rel='".$this->arr_settings['plugin_name']."'" : "").">";

			$this->search_box(__("Search", 'lang_base'), 's');

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

	function select_data($data = array())
	{
		global $wpdb;

		if(!isset($data['full_data'])){	$data['full_data'] = false;}
		if(!isset($data['sort_data'])){	$data['sort_data'] = false;}
		if(!isset($data['select'])){	$data['select'] = "*";}
		if(!isset($data['join'])){		$data['join'] = "";}
		if(!isset($data['where'])){		$data['where'] = "";}
		if(!isset($data['group_by'])){	$data['group_by'] = $this->arr_settings['query_select_id'];}
		if(!isset($data['order_by'])){	$data['order_by'] = $this->orderby;}
		if(!isset($data['order'])){		$data['order'] = $this->order;}
		if(!isset($data['limit'])){		$data['limit'] = 0;} //check_var('paged', 'int', true, '0') // This will mess up counter for all and pagination
		//if(!isset($data['amount'])){	$data['amount'] = ($data['sort_data'] == true ? 0 : $this->arr_settings['per_page']);} // This will mess up pagination
		if(!isset($data['amount'])){	$data['amount'] = 15000;}
		if(!isset($data['debug'])){		$data['debug'] = false;}

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

		if($data['order_by'] != '')
		{
			$query_order .= " ORDER BY ".$data['order_by']." ".$data['order'];
		}

		if($data['amount'] > 0)
		{
			$query_limit .= " LIMIT ".$data['limit'].", ".$data['amount'];
		}

		$query = "SELECT ".$data['select']." FROM ".$query_from.$query_join.$query_where.$query_group.$query_order.$query_limit;

		if($data['debug'] == true)
		{
			echo "<br>mf_list_table->select_data() query: ".$query."<br>";
		}

		$result = $wpdb->get_results($query);

		$this->num_rows = count($result);

		if($data['debug'] == true)
		{
			echo __("Rows", 'lang_base').": ".$this->num_rows."<br>";
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

		/*else if($this->num_rows == $data['amount'])
		{
			$wpdb->get_results("SELECT 1 FROM ".$query_from.$query_join.$query_where.$query_group);
			$this->num_rows = $wpdb->num_rows;
		}*/
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
		echo "<form method='get'>
			<input type='hidden' name='page' value='".check_var('page')."'>"; //$_REQUEST['page']
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
			$arr_data = array();
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
		$this->options_page = "settings_mf_base";

		add_action('admin_menu', array($this, 'add_plugin_page'));
	}

	public function add_plugin_page()
	{
		add_options_page(
			__("My Settings", 'lang_base'),
			__("My Settings", 'lang_base'),
			'manage_options',
			$this->options_page,
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

			if(!isset( $wp_settings_fields ) || !isset( $wp_settings_fields[$page] ) || !isset( $wp_settings_fields[$page][$section['id']] ) )
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

					settings_fields($this->options_page);
					$this->do_settings_sections($this->options_page);
					submit_button();

				echo "</div>
			</div>
		</div>";
	}
}

class mf_microtime
{
	function __construct($data = array())
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

		return ($this->now - $this->time_orig) > $limit;
	}

	function output($string, $type = "ms")
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
	}
}

class mf_font_icons
{
	function __construct($id = "")
	{
		$this->id = $id;
		$this->fonts = array();

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

	function get_array($data = array())
	{
		if(!isset($data['allow_optgroup'])){ $data['allow_optgroup'] = true;}

		$arr_out = array();

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
			'fas fa-briefcase-medical',
			'fas fa-chalkboard-teacher',
			'fas fa-clock',
			'eye',
			'exclamation-triangle',
			'fas fa-file-alt',
			'fas fa-graduation-cap',
			'fas fa-hospital-alt',
			'fas fa-key',
			'link',
			'lock',
			'paper-plane',
			'fas fa-parking',
			'fas fa-scroll',
			'fas fa-sun',
			'unlink',
			'fas fa-user',
			'fas fa-utensils',
			'fas fa-wheelchair',
		);

		return $arr_icons;
	}

	function get_symbol_tag($data, $title = "", $nbsp = true)
	{
		if(!is_array($data))
		{
			$data = array(
				'symbol' => $data,
			);
		}

		if(!isset($data['title'])){		$data['title'] = $title;}
		if(!isset($data['class'])){		$data['class'] = '';}
		//if(!isset($data['nbsp'])){		$data['nbsp'] = $nbsp;}

		$out = "";

		if($data['symbol'] != '')
		{
			if(substr($data['symbol'], 0, 5) == "icon-")
			{
				mf_enqueue_style('style_icomoon', plugin_dir_url(__FILE__)."style_icomoon.php", get_plugin_version(__FILE__));

				$out = "<span class='".$data['symbol'].($data['class'] != '' ? " ".$data['class'] : '')."'".($data['title'] != '' ? " title='".$data['title']."'" : "")."></span>"; //.($data['nbsp'] ? "&nbsp;" : '')
			}

			else
			{
				if(substr($data['symbol'], 0, 2) != 'fa')
				{
					$data['symbol'] = "fa fa-".$data['symbol'];
				}

				$out = "<i class='".$data['symbol'].($data['class'] != '' ? " ".$data['class'] : '')."'".($data['title'] != '' ? " title='".$data['title']."'" : "")."></i>"; //.($data['nbsp'] ? "&nbsp;" : '')
			}
		}

		return $out;
	}
}

class mf_export
{
	function __construct($data = array())
	{
		$this->has_excel_support = is_plugin_active('mf_phpexcel/index.php');
		$this->dir_exists = true;

		$this->plugin = isset($data['plugin']) ? $data['plugin'] : '';
		$this->name = isset($data['name']) ? $data['name'] : '';

		$this->do_export = isset($data['do_export']) ? $data['do_export'] : false;
		$this->type = isset($data['type']) ? $data['type'] : '';
		$this->action = isset($data['action']) ? $data['action'] : '';

		$this->data = isset($data['data']) ? $data['data'] : array();

		$this->upload_path = $this->upload_url = $this->type_name = '';
		$this->types = array();

		$this->actions = array(
			'' => "-- ".__("Choose Here", 'lang_base')." --",
			'csv' => "CSV",
			'json' => "JSON",
		);

		if($this->has_excel_support)
		{
			$this->actions['xls'] = "XLS";
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
		$this->action = check_var('strExportFormat', 'char', true, $this->action);

		$this->fetch_request_xtra();
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
			if($this->action != '')
			{
				$this->get_export_data();

				if(count($this->data) > 0)
				{
					$file = prepare_file_name($this->name).".".$this->action;

					switch($this->action)
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

							$success = set_file_content(array('file' => $this->upload_path.$file, 'mode' => 'a', 'content' => trim($out_temp)));

							if($success == true)
							{
								$done_text = __("Download the exported file here", 'lang_base').": <a href='".$this->upload_url.$file."'>".$file."</a>";
							}

							else
							{
								$error_text = __("It was not possible to export", 'lang_base');
							}
						break;

						case 'json':
							$success = set_file_content(array('file' => $this->upload_path.$file, 'mode' => 'a', 'content' => json_encode($this->data)));

							if($success == true)
							{
								$done_text = __("Download the exported file here", 'lang_base').": <a href='".$this->upload_url.$file."'>".$file."</a>";
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
							$objWriter->save($this->upload_path.$file);

							$done_text = __("Download the exported file here", 'lang_base').": <a href='".$this->upload_url.$file."'>".$file."</a>";
						break;
					}
				}

				else
				{
					$error_text = __("There was nothing to export", 'lang_base');
				}

				get_file_info(array('path' => $this->upload_path, 'callback' => 'delete_files'));
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

					if(count($this->actions) > 0)
					{
						$out .= show_select(array('data' => $this->actions, 'name' => 'strExportFormat', 'text' => __("File type", 'lang_base'), 'value' => $this->action));
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
	function __construct()
	{
		global $wpdb;

		$plugin_include_url = plugin_dir_url(__FILE__);
		$plugin_version = get_plugin_version(__FILE__);

		mf_enqueue_style('style_import_wp', $plugin_include_url."style_import_wp.css", $plugin_version);
		mf_enqueue_script('script_import_wp', $plugin_include_url."script_import_wp.js", $plugin_version);

		$this->prefix = $wpdb->prefix;
		$this->table = $this->post_type = $this->actions = "";
		$this->columns = $this->unique_columns = $this->validate_columns = $this->result = array();

		$this->row_separator = "
";
		$this->is_run = false;
		$this->unique_check = "OR";

		$this->rows_updated = $this->rows_up_to_date = $this->rows_inserted = $this->rows_not_inserted = $this->rows_deleted = $this->rows_not_deleted = $this->rows_not_exists = $this->rows_untouched = 0;

		$this->has_excel_support = is_plugin_active('mf_phpexcel/index.php');

		$this->get_defaults();
		$this->fetch_request();
	}

	function get_defaults(){}
	function get_external_value(&$strRowField, &$value){}
	function if_more_than_one($id){}
	function inserted_new($id){}
	function updated_new($id){}
	function update_options_extend($id){}

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
		$this->data = array();
		$this->file_location = '';

		$this->action = check_var('strTableAction');
		$this->skip_header = check_var('intImportSkipHeader', '', true, '0');
		$this->text = isset($_POST['strImportText']) ? trim($_POST['strImportText']) : "";

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
			$this->file_name = isset($_FILES['strImportFile']) ? $_FILES['strImportFile']['name'] : '';
			$this->file_location = isset($_FILES['strImportFile']) ? $_FILES['strImportFile']['tmp_name'] : '';

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

		$this->is_run = isset($_POST['btnImportRun']) && wp_verify_nonce($_POST['_wpnonce_import_run'], 'import_run') && $this->action != '' && count($this->data) > 0;
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

	function get_untouched()
	{
		global $wpdb;

		$arr_affected_id = array();

		foreach($this->result as $row)
		{
			if($row['id'] > 0)
			{
				$arr_affected_id[] = $row['id'];
			}
		}

		$result = $wpdb->get_results("SELECT *, ".$this->table_id." AS ID FROM ".$this->prefix.$this->table." WHERE ".$this->query_base_where.($this->query_base_where != '' ? " AND " : "").$this->table_id." NOT IN ('".implode("', '", $arr_affected_id)."')");

		foreach($result as $r)
		{
			$this->result[] = array(
				'type' => 'untouched',
				'action' => 'fa fa-check green',
				'id' => $r->ID,
				'data' => $r,
				'value' => "SELECT ".$this->table_id." AS ID FROM ".$this->prefix.$this->table." WHERE ".$this->query_base_where.($this->query_base_where != '' ? " AND " : "").$this->table_id." = '".$r->ID."'",
			);

			$this->rows_untouched++;
		}
	}

	function do_import()
	{
		global $wpdb;

		$out = "";

		$count_temp_rows = count($this->data);

		if($count_temp_rows > 0)
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

			$i_start = $this->skip_header ? 1 : 0;

			for($i = $i_start; $i < $count_temp_rows; $i++)
			{
				$this->query_where = $this->query_where_first = $this->query_set = "";
				$this->query_option = array();

				$arr_values = $this->data[$i];
				$count_temp_values = count($arr_values);

				/*if($count_temp_values == 1)
				{
					do_log("The row only had one column (".var_export($arr_values, true).")");
				}*/

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
						}
					}
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

											$this->rows_updated++;

											$this->result[] = array(
												'type' => 'updated',
												'action' => 'fa fa-check green',
												'id' => $id,
												'data' => $arr_values,
												'value' => $query_update,
											);
										}

										else
										{
											$this->rows_up_to_date++;

											$this->result[] = array(
												'type' => 'up_to_date',
												'action' => 'fa fa-cloud blue',
												'id' => $id,
												'data' => $arr_values,
												'value' => $query_update,
											);
										}
									}

									else
									{
										$this->if_more_than_one($r->ID);

										$this->result[] = array(
											'type' => 'duplicate',
											'action' => 'fa fa-copy',
											'id' => $r->ID,
											'data' => $arr_values,
											'value' => $query_select,
										);
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

									$this->rows_inserted++;

									$this->result[] = array(
										'type' => 'inserted',
										'action' => 'fa fa-plus-circle',
										'id' => $id,
										'data' => $arr_values,
										'value' => $query_insert,
									);
								}

								else
								{
									$this->rows_not_inserted++;

									$this->result[] = array(
										'type' => 'not_inserted',
										'action' => 'fa fa-unlink',
										'id' => '',
										'data' => $arr_values,
										'value' => $query_insert,
									);
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
									$this->rows_deleted++;

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
									$this->rows_not_deleted++;

									$this->result[] = array(
										'type' => 'not_deleted',
										'action' => 'fa fa-unlink',
										'id' => '',
										'data' => $arr_values,
										'value' => $query_delete,
									);
								}
							}

							else
							{
								$this->rows_not_exists++;

								$this->result[] = array(
									'type' => 'not_exists',
									'action' => 'fa fa-question',
									'id' => '',
									'data' => $arr_values,
									'value' => $query_select,
								);
							}
						break;

						default:
							$this->result[] = array(
								'type' => '',
								'id' => '',
								'data' => $arr_values,
								'action' => 'fa fa-question',
							);
						break;
					}
				}

				else
				{
					$this->result[] = array(
						'type' => '',
						'action' => 'fa fa-heartbeat',
						'data' => $arr_values,
						'value' => var_export($arr_values, true),
					);
				}

				if($i % 100 == 0)
				{
					sleep(0.1);
					set_time_limit(60);
				}
			}

			if(count($this->result) > 0)
			{
				$arr_export_data = array();

				foreach($this->result as $row)
				{
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
				}

				$obj_export = new mf_export(array('plugin' => 'mf_base', 'do_export' => true, 'name' => 'import_result', 'action' => (is_plugin_active('mf_phpexcel/index.php') ? 'xls' : 'csv'), 'data' => $arr_export_data));

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

		if(!$this->is_run || IS_ADMIN)
		{
			$out .= $this->get_form();
		}

		return $out;
	}

	function get_form()
	{
		$out = "<form action='#' method='post' class='mf_form mf_settings' enctype='multipart/form-data' id='mf_import' rel='import/check/".get_class($this)."'>"
			."<div id='poststuff' class='postbox'>
				<h3 class='hndle'>".__("Check", 'lang_base')."</h3>
				<div class='inside'>";

					if(count($this->actions) > 1)
					{
						$arr_data = array(
							'' => "-- ".__("Choose Here", 'lang_base')." --",
							'delete' => __("Delete", 'lang_base'),
							'import' => __("Import", 'lang_base'),
						);

						$out .= show_select(array('data' => $arr_data, 'name' => 'strTableAction', 'text' => __("Action", 'lang_base'), 'value' => $this->action));
					}

					else
					{
						$out .= input_hidden(array('name' => 'strTableAction', 'value' => $this->actions[0]));
					}

					if($this->file_location == '')
					{
						$out .= show_textarea(array('name' => 'strImportText', 'text' => __("Text", 'lang_base'), 'value' => $this->text, 'size' => 'huge', 'placeholder' => __("Value 1	Value 2	Value 3", 'lang_base')));
					}

					if($this->has_excel_support && $this->text == '')
					{
						$out .= show_file_field(array('name' => 'strImportFile', 'text' => __("File", 'lang_base'), 'required' => ($this->file_location != '' ? true : false)));
					}

					$out .= show_select(array('data' => get_yes_no_for_select(array('return_integer' => true)), 'name' => 'intImportSkipHeader', 'value' => $this->skip_header, 'text' => __("Skip first row", 'lang_base')))
					.show_button(array('name' => 'btnImportCheck', 'text' => __("Check", 'lang_base')))
				."</div>
			</div>";

			$out_temp = $this->get_result();

			if($out_temp != '')
			{
				$out .= "<div id='import_result'>"
					.$out_temp
				."</div>";
			}

		$out .= "</form>";

		return $out;
	}

	function get_result()
	{
		$out = "";

		$count_temp_rows = count($this->data);

		if($this->action != '' && $count_temp_rows > 0)
		{
			$arr_data = array(
				'' => "-- ".__("Choose Here", 'lang_base')." --"
			);

			foreach($this->columns as $key => $value)
			{
				$arr_data[$key] = $value;
			}

			$out .= "<div id='poststuff' class='postbox'>
				<h3 class='hndle'>".__("Run", 'lang_base')."</h3>
				<div class='inside'>
					<p>".__("Rows", 'lang_base').": ".$count_temp_rows."</p>";

					$arr_values = $this->data[0];
					$count_temp_values = count($arr_values);

					for($i = 0; $i < $count_temp_values; $i++)
					{
						$import_text = $arr_values[$i];

						$strRowField = check_var('strRowCheck'.$i);

						if($strRowField == '')
						{
							if(isset($arr_data[$import_text]))
							{
								$strRowField = $arr_data[$import_text];
							}

							else
							{
								foreach($arr_data as $key => $value)
								{
									if($value == $import_text)
									{
										$strRowField = $key;
									}
								}
							}
						}

						$out .= show_select(array('data' => $arr_data, 'name' => 'strRowCheck'.$i, 'value' => $strRowField, 'text' => __("Column", 'lang_base')." ".($i + 1)." <span>(".$import_text.")</span>"));
					}

					$out .= "&nbsp;"
					.show_button(array('name' => 'btnImportRun', 'text' => __("Run", 'lang_base')))
					.wp_nonce_field('import_run', '_wpnonce_import_run', true, false)
				."</div>
			</div>";

			$out .= "<div id='poststuff' class='postbox'>
				<h3 class='hndle'>".__("Example", 'lang_base')."</h3>
				<div class='inside'>
					<table class='widefat striped'>";

						for($i = 0; $i < $count_temp_rows && $i < 5; $i++)
						{
							$out .= "<tr>";

								$cell_tag = $i == 0 && $this->skip_header ? "th" : "td";

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