<?php

class mf_cron
{
	function __construct()
	{
		$this->schedules = wp_get_schedules();

		$this->date_start = date("Y-m-d H:i:s");
	}

	function has_expired($data = array())
	{
		if(!isset($data['margin'])){		$data['margin'] = 1;}

		$setting_base_cron = get_option('setting_base_cron');

		$cron_interval_seconds = $this->schedules[$setting_base_cron]['interval'];

		$date_now = date("Y-m-d H:i:s");
		$date_difference = time_between_dates(array('start' => $this->date_start, 'end' => $date_now, 'type' => "ceil", 'return' => "seconds"));

		return $date_difference >= ($cron_interval_seconds * $data['margin']);
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

			if($pagenow == 'plugins.php' && $data['show_notice'] == true)
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
	require_once(ABSPATH.'wp-admin/includes/template.php');
	require_once(ABSPATH.'wp-admin/includes/class-wp-list-table.php');
}

class mf_list_table extends WP_List_Table
{
	var $arr_settings = array();
	var $post_type = "";
	var $orderby_default = "post_title";
	var $orderby_default_order = "asc";

	var $views = array();
	var $columns = array();
	var $sortable_columns = array();
	var $data = "";
	var $num_rows = 0;
	var $query_join = "";
	var $query_where = "";
	var $search = "";
	var $orderby = "";
	var $order = "";
	var $page = "";

	function __construct()
	{
		global $wpdb;

		parent::__construct(array(
			'singular' => '', //singular name of the listed records
			'plural' => '', //plural name of the listed records
			'ajax' => false //does this table support ajax?
		));

		$this->page = check_var('page', 'char');
		$this->search = check_var('s', 'char', true);

		$this->arr_settings = array(
			'per_page' => $this->get_items_per_page('edit_page_per_page', 20),
			'query_from' => $wpdb->posts,
			'query_select_id' => "ID",
			'query_all_id' => "all",
			'query_trash_id' => array('trash', 'ignore'),
			'has_autocomplete' => false,
		);

		$this->set_default();

		if($this->post_type != '')
		{
			$this->_args['singular'] = $this->post_type;
		}

		/**
		 * Optional. You can handle your bulk actions however you see fit. In this
		 * case, we'll handle them within our package just to keep things clean.
		 */
		$this->process_bulk_action();

		$this->orderby = check_var('orderby', 'char', true, $this->orderby_default);
		$this->order = check_var('order', 'char', true, $this->orderby_default_order);
	}

	function set_default(){}
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
			$empty_trash_days = defined('EMPTY_TRASH_DAYS') ? EMPTY_TRASH_DAYS : 30;

			$result = $wpdb->get_results("SELECT ".$this->arr_settings['query_select_id']." FROM ".$this->arr_settings['query_from']." WHERE ".$db_field." = '1' AND ".$db_field."Date < DATE_SUB(NOW(), INTERVAL ".$empty_trash_days." DAY)");

			if($wpdb->num_rows > 0)
			{
				$error_text = sprintf(__("Use run_cron_delete() on %s"), $db_field);

				do_log($error_text);
			}
		}
	}

	function set_views($data)
	{
		global $wpdb;

		$this->empty_trash($data['db_field']);

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
				.$query_group
			;

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
	 * Recommended. This method is called when the parent class can't find a method
	 * specifically build for a given column. Generally, it's recommended to include
	 * one method for each column you want to render, keeping your package class
	 * neat and organized. For example, if the class needs to process a column
	 * named 'title', it would first see if a method named $this->column_title() 
	 * exists - if it does, that method will be used. If it doesn't, this one will
	 * be used. Generally, you should try to use custom column methods as much as 
	 * possible. 
	 * 
	 * Since we have defined a column_title() method later on, this method doesn't
	 * need to concern itself with any column with a name of 'title'. Instead, it
	 * needs to handle everything else.
	 * 
	 * For more detailed insight into how columns are handled, take a look at 
	 * WP_List_Table::single_row_columns()
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
		return "<input type='checkbox' name='".$this->_args['singular']."[]' value='".$item['ID']."'>";
	}

	/** ************************************************************************
	* REQUIRED! This method dictates the table's columns and titles. This should
	* return an array where the key is the column slug (and class) and the value 
	* is the column's title text. If you need a checkbox for bulk actions, refer
	* to the $columns array below.
	* 
	* The 'cb' column is treated differently than the rest. If including a checkbox
	* column in your table you must create a column_cb() method. If you don't need
	* bulk actions or checkboxes, simply leave the 'cb' entry out of your array.
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
			foreach($_GET[$this->post_type] as $id)
			{
				wp_trash_post($id);
			}
		}

		else
		{
			do_log("Bulk delete: ".var_export($_GET, true));
		}
	}

	function process_bulk_action()
	{
		if(isset($_GET['_wpnonce']) && !empty($_GET['_wpnonce']))
		{
			if('delete' === $this->current_action())
			{
				$this->bulk_delete();
			}
		}
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
		 * REQUIRED. Now we need to define our column headers. This includes a complete
		 * array of columns to be displayed (slugs & titles), a list of columns
		 * to keep hidden, and a list of columns that are sortable. Each of these
		 * can be defined in another method (as we've done here) before being
		 * used to build the value for our _column_headers property.
		 */
		//$columns = $this->get_columns();
		$hidden = array();
		//$sortable = $this->get_sortable_columns();

		/**
		 * REQUIRED. Finally, we build an array to be used by the class for column 
		 * headers. The $this->_column_headers property takes an array which contains
		 * 3 other arrays. One for all columns, one for hidden columns, and one
		 * for sortable columns.
		 */
		$this->_column_headers = array($this->columns, $hidden, $this->sortable_columns);

		$current_page = $this->get_pagenum();

		$this->items = $this->data = array_slice($this->data, (($current_page - 1) * $this->arr_settings['per_page']), $this->arr_settings['per_page']);

		$this->set_pagination_args(array(
			'total_items' => $this->num_rows,
			'per_page'	=> $this->arr_settings['per_page'],
			'total_pages' => ceil($this->num_rows / $this->arr_settings['per_page'])
		));
	}

	protected function get_table_classes()
	{
		return array('widefat', 'striped'); //, 'fixed', $this->_args['plural']
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

		/*if(!isset($data['select']))
		{
			$data['select'] = "";

			foreach($this->columns as $key => $value)
			{
				$data['select'] .= ($data['select'] != '' ? ", " : "").$key;
			}
		}*/

		if(!isset($data['select'])){	$data['select'] = "*";}
		if(!isset($data['join'])){		$data['join'] = "";}
		if(!isset($data['where'])){		$data['where'] = "";}
		if(!isset($data['limit'])){		$data['limit'] = "";}
		if(!isset($data['amount'])){	$data['amount'] = "";}
		if(!isset($data['group_by'])){	$data['group_by'] = $this->arr_settings['query_select_id'];}
		if(!isset($data['order_by'])){	$data['order_by'] = $this->orderby;}
		if(!isset($data['order'])){		$data['order'] = $this->order;}
		if(!isset($data['sort_data'])){	$data['sort_data'] = true;}

		$query = "SELECT ".$data['select']." FROM ".$this->arr_settings['query_from'].$this->query_join.$data['join'];

		if($this->query_where != '' || $data['where'] != '')
		{
			$query .= " WHERE ";

			if($this->query_where != '')
			{
				$query .= $this->query_where;
			}

			if($data['where'])
			{
				$query .= ($this->query_where != '' ? " AND " : "").$data['where'];
			}
		}

		if($data['group_by'] != '')
		{
			$query .= " GROUP BY ".$data['group_by'];
		}

		if($data['order_by'] != '')
		{
			$query .= " ORDER BY ".$data['order_by']." ".$data['order'];
		}

		if($data['amount'] != '')
		{
			$query .= " LIMIT ".$data['limit'].", ".$data['amount'];
		}

		if(isset($data['debug']) && $data['debug'] == true)
		{
			echo "<br>mf_list_table->select_data() query: ".$query."<br>";
		}

		$result = $wpdb->get_results($query);

		$this->num_rows = count($result);

		if(isset($data['debug']) && $data['debug'] == true)
		{
			//$this->debug_query = $query;

			echo __("Rows", 'lang_base').": ".$this->num_rows."<br>";
		}

		$this->data = json_decode(json_encode($result), true);

		if($this->num_rows > 0 && $data['sort_data'] == true)
		{
			if(isset($data['debug']) && $data['debug'] == true)
			{
				echo __("Sorting...", 'lang_base')."<br>";
			}

			$this->sort_data();

			$this->num_rows = count($this->data);

			if(isset($data['debug']) && $data['debug'] == true)
			{
				echo __("Rows", 'lang_base').": ".$this->num_rows."<br>";
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
		$this->show_search_form();

		$this->show_before_display();
		$this->display();
		$this->show_after_display();
	}

	function show_before_display()
	{
		echo "<form method='get'>
			<input type='hidden' name='page' value='".$_REQUEST['page']."'>";
	}

	function show_after_display()
	{
		echo "</form>";
	}
}

class pagination
{
	function __construct()
	{
		$this->range = 5;
		$this->per_page = 20;
		$this->count = 0;
	}

	function show($data)
	{
		global $intLimitStart;

		if(!is_array($data['result']) && $data['result'] > 0)
		{
			$rows = $data['result'];
		}

		else
		{
			$rows = $data['result'] != '' ? count($data['result']) : 0;
		}

		if($rows > $this->per_page)
		{
			$first = 1;
			$last = ceil($rows / $this->per_page);
			$this->current = floor($intLimitStart / $this->per_page) + 1;

			$start = $first < ($this->current - $this->range - 1) ? $this->current - $this->range : $first;
			$stop = $last > ($this->current + $this->range + 1) ? $this->current + $this->range : $last;

			$out = "<div class='tablenav'>
				<div class='tablenav-pages'>";

					if($this->current > $first)
					{
						$out .= $this->button(array('page' => ($this->current - 1), 'text' => "&laquo;&laquo;"));
					}

					if($start != $first)
					{
						$out .= $this->button(array('page' => $first))."<span>...</span>";
					}

					for($i = $start; $i <= $stop; $i++)
					{
						$out .= $this->button(array('page' => $i));
					}

					if($stop != $last)
					{
						$out .= "<span>...</span>".$this->button(array('page' => $last));
					}

					if($this->current < $last)
					{
						$out .= $this->button(array('page' => ($this->current + 1), 'text' => "&raquo;&raquo;"));
					}

				$out .= "</div>
			</div>";

			$this->count++;

			return $out;
		}
	}

	function button($data)
	{
		return "<a href='".preg_replace("/\&paged\=\d+/", "", $_SERVER['REQUEST_URI'])."&paged=".($data['page'] - 1)."'".($this->current == $data['page'] ? " class='disabled'" : "").">"
			.(isset($data['text']) ? $data['text'] : $data['page'])
		."</a>";
	}
}

class settings_page
{
	//private $options;

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

	public function create_admin_page()
	{
		wp_enqueue_style('style_base_settings', plugin_dir_url(__FILE__)."style_settings.css");
		mf_enqueue_script('script_base_settings', plugin_dir_url(__FILE__)."script_settings.js", array('default_tab' => "settings_base", 'settings_page' => true));

		echo "<div class='wrap'>
			<h2>".__("My Settings", 'lang_base')."</h2>
			<h3 id='nav-tab-wrapper' class='nav-tab-wrapper'></h3>
			<form method='post' action='options.php'>";

				settings_fields($this->options_page);
				do_settings_sections($this->options_page);
				submit_button(); 

			echo "</form>
		</div>";
	}
}

//
######################
class mf_encryption 
{
	function __construct($type)
	{
		$this->set_key($type);
		$this->iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND);
	}

	function set_key($type)
	{
		$this->key = substr("mf_crypt".$type, 0, 32);
	}

	function encrypt($text, $key = "")
	{
		if($key != '')
		{
			$this->set_key($key);
		}

		return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $this->key, $text, MCRYPT_MODE_ECB, $this->iv));
	}

	function decrypt($text, $key = "")
	{
		if($key != '')
		{
			$this->set_key($key);
		}

		return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $this->key, base64_decode($text), MCRYPT_MODE_ECB, $this->iv));
	}
}
######################

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
		$arr_icons = array('500px', 'adjust', 'adn', 'align-center', 'align-justify', 'align-left', 'align-right', 'amazon', 'ambulance', 'anchor', 'android', 'angellist', 'angle-double-down', 'angle-double-left', 'angle-double-right', 'angle-double-up', 'angle-down', 'angle-left', 'angle-right', 'angle-up', 'apple', 'archive', 'area-chart', 'arrow-circle-down', 'arrow-circle-left', 'arrow-circle-o-down', 'arrow-circle-o-left', 'arrow-circle-o-right', 'arrow-circle-o-up', 'arrow-circle-right', 'arrow-circle-up', 'arrow-down', 'arrow-left', 'arrow-right', 'arrow-up', 'arrows', 'arrows-alt', 'arrows-h', 'arrows-v', 'asterisk', 'at', 'automobile', 'backward', 'balance-scale', 'ban', 'bank', 'bar-chart', 'bar-chart-o', 'barcode', 'bars', 'battery-0', 'battery-1', 'battery-2', 'battery-3', 'battery-4', 'battery-empty', 'battery-full', 'battery-half', 'battery-quarter', 'battery-three-quarters', 'bed', 'beer', 'behance', 'behance-square', 'bell', 'bell-o', 'bell-slash', 'bell-slash-o', 'bicycle', 'binoculars', 'birthday-cake', 'bitbucket', 'bitbucket-square', 'bitcoin', 'black-tie', 'bluetooth', 'bluetooth-b', 'bold', 'bolt', 'bomb', 'book', 'bookmark', 'bookmark-o', 'briefcase', 'btc', 'bug', 'building', 'building-o', 'bullhorn', 'bullseye', 'bus', 'buysellads', 'cab', 'calculator', 'calendar', 'calendar-check-o', 'calendar-minus-o', 'calendar-o', 'calendar-plus-o', 'calendar-times-o', 'camera', 'camera-retro', 'car', 'caret-down', 'caret-left', 'caret-right', 'caret-square-o-down', 'caret-square-o-left', 'caret-square-o-right', 'caret-square-o-up', 'caret-up', 'cart-arrow-down', 'cart-plus', 'cc', 'cc-amex', 'cc-diners-club', 'cc-discover', 'cc-jcb', 'cc-mastercard', 'cc-paypal', 'cc-stripe', 'cc-visa', 'certificate', 'chain', 'chain-broken', 'check', 'check-circle', 'check-circle-o', 'check-square', 'check-square-o', 'chevron-circle-down', 'chevron-circle-left', 'chevron-circle-right', 'chevron-circle-up', 'chevron-down', 'chevron-left', 'chevron-right', 'chevron-up', 'child', 'chrome', 'circle', 'circle-o', 'circle-o-notch', 'circle-thin', 'clipboard', 'clock-o', 'clone', 'close', 'cloud', 'cloud-download', 'cloud-upload', 'cny', 'code', 'code-fork', 'codepen', 'codiepie', 'coffee', 'cog', 'cogs', 'columns', 'comment', 'comment-o', 'commenting', 'commenting-o', 'comments', 'comments-o', 'compass', 'compress', 'connectdevelop', 'contao', 'copy', 'copyright', 'creative-commons', 'credit-card', 'credit-card-alt', 'crop', 'crosshairs', 'css3', 'cube', 'cubes', 'cut', 'cutlery', 'dashboard', 'dashcube', 'database', 'dedent', 'delicious', 'desktop', 'deviantart', 'diamond', 'digg', 'dollar', 'dot-circle-o', 'download', 'dribbble', 'dropbox', 'drupal', 'edge', 'edit', 'eject', 'ellipsis-h', 'ellipsis-v', 'empire', 'envelope', 'envelope-o', 'envelope-square', 'eraser', 'eur', 'euro', 'exchange', 'exclamation', 'exclamation-circle', 'exclamation-triangle', 'expand', 'expeditedssl', 'external-link', 'external-link-square', 'eye', 'eye-slash', 'eyedropper', 'facebook', 'facebook-f', 'facebook-official', 'facebook-square', 'fast-backward', 'fast-forward', 'fax', 'feed', 'female', 'fighter-jet', 'file', 'file-archive-o', 'file-audio-o', 'file-code-o', 'file-excel-o', 'file-image-o', 'file-movie-o', 'file-o', 'file-pdf-o', 'file-photo-o', 'file-picture-o', 'file-powerpoint-o', 'file-sound-o', 'file-text', 'file-text-o', 'file-video-o', 'file-word-o', 'file-zip-o', 'files-o', 'film', 'filter', 'fire', 'fire-extinguisher', 'firefox', 'flag', 'flag-checkered', 'flag-o', 'flash', 'flask', 'flickr', 'floppy-o', 'folder', 'folder-o', 'folder-open', 'folder-open-o', 'font', 'fonticons', 'fort-awesome', 'forumbee', 'forward', 'foursquare', 'frown-o', 'futbol-o', 'fw', 'gamepad', 'gavel', 'gbp', 'ge', 'gear', 'gears', 'genderless', 'get-pocket', 'gg', 'gg-circle', 'gift', 'git', 'git-square', 'github', 'github-alt', 'github-square', 'gittip', 'glass', 'globe', 'google', 'google-plus', 'google-plus-square', 'google-wallet', 'graduation-cap', 'gratipay', 'group', 'h-square', 'hacker-news', 'hand-grab-o', 'hand-lizard-o', 'hand-o-down', 'hand-o-left', 'hand-o-right', 'hand-o-up', 'hand-paper-o', 'hand-peace-o', 'hand-pointer-o', 'hand-rock-o', 'hand-scissors-o', 'hand-spock-o', 'hand-stop-o', 'hashtag', 'hdd-o', 'header', 'headphones', 'heart', 'heart-o', 'heartbeat', 'history', 'home', 'hospital-o', 'hotel', 'hourglass', 'hourglass-1', 'hourglass-2', 'hourglass-3', 'hourglass-end', 'hourglass-half', 'hourglass-o', 'hourglass-start', 'houzz', 'hover', 'html5', 'i-cursor', 'ils', 'image', 'inbox', 'indent', 'industry', 'info', 'info-circle', 'inr', 'instagram', 'institution', 'internet-explorer', 'intersex', 'ioxhost', 'italic', 'joomla', 'jpy', 'jsfiddle', 'key', 'keyboard-o', 'krw', 'language', 'laptop', 'lastfm', 'lastfm-square', 'leaf', 'leanpub', 'legal', 'lemon-o', 'level-down', 'level-up', 'lg', 'li', 'life-bouy', 'life-buoy', 'life-ring', 'life-saver', 'lightbulb-o', 'line-chart', 'link', 'linkedin', 'linkedin-square', 'linux', 'list', 'list-alt', 'list-ol', 'list-ul', 'location-arrow', 'lock', 'long-arrow-down', 'long-arrow-left', 'long-arrow-right', 'long-arrow-up', 'magic', 'magnet', 'mail-forward', 'mail-reply', 'mail-reply-all', 'male', 'map', 'map-marker', 'map-o', 'map-pin', 'map-signs', 'mars', 'mars-double', 'mars-stroke', 'mars-stroke-h', 'mars-stroke-v', 'maxcdn', 'meanpath', 'medium', 'medkit', 'meh-o', 'mercury', 'microphone', 'microphone-slash', 'minus', 'minus-circle', 'minus-square', 'minus-square-o', 'mixcloud', 'mobile', 'mobile-phone', 'modx', 'money', 'moon-o', 'mortar-board', 'motorcycle', 'mouse-pointer', 'music', 'navicon', 'neuter', 'newspaper-o', 'object-group', 'object-ungroup', 'odnoklassniki', 'odnoklassniki-square', 'opencart', 'openid', 'opera', 'optin-monster', 'outdent', 'pagelines', 'paint-brush', 'paper-plane', 'paper-plane-o', 'paperclip', 'paragraph', 'paste', 'pause', 'pause-circle', 'pause-circle-o', 'paw', 'paypal', 'pencil', 'pencil-square', 'pencil-square-o', 'percent', 'phone', 'phone-square', 'photo', 'picture-o', 'pie-chart', 'pied-piper', 'pied-piper-alt', 'pinterest', 'pinterest-p', 'pinterest-square', 'plane', 'play', 'play-circle', 'play-circle-o', 'plug', 'plus', 'plus-circle', 'plus-square', 'plus-square-o', 'power-off', 'print', 'product-hunt', 'puzzle-piece', 'qq', 'qrcode', 'question', 'question-circle', 'quote-left', 'quote-right', 'ra', 'random', 'rebel', 'recycle', 'reddit', 'reddit-alien', 'reddit-square', 'refresh', 'registered', 'remove', 'renren', 'reorder', 'repeat', 'reply', 'reply-all', 'retweet', 'rmb', 'road', 'rocket', 'rotate-left', 'rotate-right', 'rouble', 'rss', 'rss-square', 'rub', 'ruble', 'rupee', 'safari', 'save', 'scissors', 'scribd', 'search', 'search-minus', 'search-plus', 'sellsy', 'send', 'send-o', 'server', 'share', 'share-alt', 'share-alt-square', 'share-square', 'share-square-o', 'shekel', 'sheqel', 'shield', 'ship', 'shirtsinbulk', 'shopping-bag', 'shopping-basket', 'shopping-cart', 'sign-in', 'sign-out', 'signal', 'simplybuilt', 'sitemap', 'skyatlas', 'skype', 'slack', 'sliders', 'slideshare', 'smile-o', 'soccer-ball-o', 'sort', 'sort-alpha-asc', 'sort-alpha-desc', 'sort-amount-asc', 'sort-amount-desc', 'sort-asc', 'sort-desc', 'sort-down', 'sort-numeric-asc', 'sort-numeric-desc', 'sort-up', 'soundcloud', 'space-shuttle', 'spin</code>', 'spinner', 'spoon', 'spotify', 'square', 'square-o', 'stack-exchange', 'stack-overflow', 'star', 'star-half', 'star-half-empty', 'star-half-full', 'star-half-o', 'star-o', 'steam', 'steam-square', 'step-backward', 'step-forward', 'stethoscope', 'sticky-note', 'sticky-note-o', 'stop', 'stop-circle', 'stop-circle-o', 'street-view', 'strikethrough', 'stumbleupon', 'stumbleupon-circle', 'subscript', 'subway', 'suitcase', 'sun-o', 'superscript', 'support', 'table', 'tablet', 'tachometer', 'tag', 'tags', 'tasks', 'taxi', 'television', 'tencent-weibo', 'terminal', 'text-height', 'text-width', 'th', 'th-large', 'th-list', 'thumb-tack', 'thumbs-down', 'thumbs-o-down', 'thumbs-o-up', 'thumbs-up', 'ticket', 'times', 'times-circle', 'times-circle-o', 'tint', 'toggle-down', 'toggle-left', 'toggle-off', 'toggle-on', 'toggle-right', 'toggle-up', 'trademark', 'train', 'transgender', 'transgender-alt', 'trash', 'trash-o', 'tree', 'trello', 'tripadvisor', 'trophy', 'truck', 'try', 'tty', 'tumblr', 'tumblr-square', 'turkish-lira', 'tv', 'twitch', 'twitter', 'twitter-square', 'ul', 'umbrella', 'underline', 'undo', 'university', 'unlink', 'unlock', 'unlock-alt', 'unsorted', 'upload', 'usb', 'usd', 'user', 'user-md', 'user-plus', 'user-secret', 'user-times', 'users', 'venus', 'venus-double', 'venus-mars', 'viacoin', 'video-camera', 'vimeo', 'vimeo-square', 'vine', 'vk', 'volume-down', 'volume-off', 'volume-up', 'warning', 'wechat', 'weibo', 'weixin', 'whatsapp', 'wheelchair', 'wifi', 'wikipedia-w', 'windows', 'won', 'wordpress', 'wrench', 'xing', 'xing-square', 'y-combinator', 'y-combinator-square', 'yahoo', 'yc', 'yc-square', 'yelp', 'yen', 'youtube', 'youtube-play', 'youtube-square');

		/*$transient_key = "fontawesome_transient_2";

		$str_icons = get_transient($transient_key);
		$arr_icons = explode("|", $str_icons);

		if($content == "")
		{
			$content = get_url_content("http://fortawesome.github.io/Font-Awesome/icons/");

			//$arr_icons = get_match_all("/icon\/(.*?)\"/s", $content, false);
			$arr_icons = get_match_all("/fa-(.*?)[ |\"]/s", $content, false);
			$arr_icons = array_unique($arr_icons[0]);
			$arr_icons = array_sort(array('array' => $arr_icons, 'on' => 1));

			$str_icons = implode("|", $arr_icons);

			set_transient($transient_key, $str_icons, WEEK_IN_SECONDS);
		}*/

		return $arr_icons;
	}

	function get_symbol_tag($symbol, $title = "")
	{
		$out = "";

		if($symbol != '')
		{
			if(substr($symbol, 0, 5) == "icon-")
			{
				wp_enqueue_style('style_icomoon', plugin_dir_url(__FILE__)."style_icomoon.css");

				$out = "<span class='".$symbol."'".($title != '' ? " title='".$title."'" : "")."></span>&nbsp;";
			}

			else
			{
				$out = "<i class='fa fa-".$symbol."'".($title != '' ? " title='".$title."'" : "")."></i>&nbsp;";
			}
		}

		return $out;
	}
}

class mf_import
{
	function __construct()
	{
		$this->table = $this->post_type = $this->actions = "";
		$this->columns = $this->unique_columns = $this->validate_columns = $this->result = array();

		$this->row_separator = "
";
		$this->is_run = false;
		$this->unique_check = "OR";

		$this->rows_updated = $this->rows_up_to_date = $this->rows_inserted = $this->rows_not_inserted = $this->rows_deleted = $this->rows_not_deleted = $this->rows_not_exists = 0;

		$this->has_excel_support = is_plugin_active("mf_phpexcel/index.php");

		$this->get_defaults();
		$this->fetch_request();
	}

	function get_defaults(){}
	function get_external_value(&$strRowField, &$value){}
	function if_more_than_one($id){}
	function inserted_new($id){}
	function updated_new($id){}

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

	function fetch_request()
	{
		$this->data = array();
		$this->file_location = '';

		$this->action = check_var('strTableAction');
		$this->skip_header = check_var('intImportSkipHeader', '', true, '0');
		$this->text = isset($_POST['strImportText']) ? trim($_POST['strImportText']) : "";

		if($this->text != '')
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

		else if($this->has_excel_support)
		{
			$this->file_location = isset($_FILES['strImportFile']) ? $_FILES['strImportFile']['tmp_name'] : "";

			if($this->file_location != '')
			{
				$file_suffix = get_file_suffix($this->file_location);

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
				$objReader->setReadDataOnly(TRUE);
				$objPHPExcel = $objReader->load($this->file_location);

				$objWorksheet = $objPHPExcel->getActiveSheet();

				$i = 0;

				foreach($objWorksheet->getRowIterator() as $row)
				{
					$cellIterator = $row->getCellIterator();
					$cellIterator->setIterateOnlyExistingCells(FALSE); // This loops through all cells, even if a cell value is not set. By default, only cells that have a value set will be iterated.

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

		$this->is_run = isset($_POST['btnImportRun']) && wp_verify_nonce($_POST['_wpnonce'], 'import_run') && $this->action != '' && count($this->data) > 0;
	}

	function update_options($id)
	{
		foreach($this->query_option as $key => $value)
		{
			update_post_meta($id, $key, $value);
		}
	}

	function do_import()
	{
		global $wpdb;

		$out = "";

		$count_temp_rows = count($this->data);

		if($count_temp_rows > 0)
		{
			$out .= "<table class='widefat striped'>
				<tbody>";

					$i_start = $this->skip_header ? 1 : 0;

					for($i = $i_start; $i < $count_temp_rows; $i++)
					{
						$query_search = $query_xtra = "";
						$this->query_option = array();

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

								$this->get_external_value($strRowField, $value);

								if(in_array($strRowField, $this->unique_columns))
								{
									$query_search .= ($query_search != '' ? " ".$this->unique_check." " : "").$strRowField." = '".$value."'";
								}

								if($value != '')
								{
									$query_xtra .= ($query_xtra != '' ? ", " : "").$strRowField." = '".$value."'";
								}
							}
						}

						if($query_xtra != '' && $query_search != '')
						{
							$table_name = $wpdb->base_prefix.$this->table;

							if($this->table == "posts")
							{
								$table_id = "ID";
								$table_created = "post_date";
								$table_user = "post_author";

								$query_search .= " AND post_type = '".$this->post_type."'";
								$query_xtra .= ($query_xtra != '' ? ", " : "")."post_type = '".$this->post_type."'";
							}

							else
							{
								$table_id = $this->table."ID";
								$table_created = $this->table."Created";
								$table_user = "userID";
							}

							$query_select = "SELECT ".$table_id." AS ID FROM ".$table_name." WHERE ".$query_search." ORDER BY ".$table_created." ASC LIMIT 0, 5";

							$result = $wpdb->get_results($query_select);
							$rows = $wpdb->num_rows;

							if($this->action == "import")
							{
								if($rows > 0)
								{
									$k = 0;

									foreach($result as $r)
									{
										if($k == 0)
										{
											$id = $r->ID;

											if($this->table == "posts")
											{
												$query_update = "UPDATE ".$table_name." SET post_status = 'publish', ".$query_xtra." WHERE ".$table_id." = '".$id."'";

												$wpdb->query($query_update);

												/*$post_data = array(
													'ID' => $id,
													'post_type' => $this->post_type,
													'post_status' => 'publish',
													//$query_xtra?
												);

												wp_update_post($post_data);*/
											}

											else
											{
												$query_update = "UPDATE ".$table_name." SET ".$this->table."Deleted = '0', ".$this->table."DeletedDate = '', ".$this->table."DeletedID = '', ".$query_xtra." WHERE ".$table_id." = '".$id."'"; //$query_search

												$wpdb->query($query_update);
											}

											$this->updated_new($id);

											if($wpdb->rows_affected > 0)
											{
												$this->update_options($id);

												$this->rows_updated++;

												$this->result[] = array(
													'action' => 'fa-check green',
													'value' => $query_update,
												);
											}

											else
											{
												$this->rows_up_to_date++;

												$this->result[] = array(
													'action' => 'fa-cloud',
													'value' => $query_update,
												);
											}
										}

										else
										{
											$this->if_more_than_one($r->ID);

											$this->result[] = array(
												'action' => 'fa-copy',
												'value' => $query_select,
											);
										}

										$k++;
									}

									/*if($rows > 1)
									{
										$this->if_more_than_one($result);
									}*/
								}

								else
								{
									$query_insert = "INSERT INTO ".$table_name." SET ".$query_xtra.", ".$table_created." = NOW(), ".$table_user." = '".get_current_user_id()."'";

									/*if($this->table == "posts")
									{
										$post_data = array(
											'post_type' => $this->post_type,
											'post_status' => 'publish',
											//$query_xtra?
										);

										wp_insert_post($post_data);
									}*/

									if($query_insert != '')
									{
										$wpdb->query($query_insert);

										if($wpdb->rows_affected > 0)
										{
											$id = $wpdb->insert_id;

											$this->update_options($id);

											$this->inserted_new($id);

											$this->rows_inserted++;

											$this->result[] = array(
												'action' => 'fa-plus',
												'value' => $query_insert,
											);
										}

										else
										{
											$this->rows_not_inserted++;

											$this->result[] = array(
												'action' => 'fa-chain-broken',
												'value' => $query_insert,
											);
										}
									}

									else
									{
										do_log("wpdb->prepare made this query empty: INSERT INTO ".$table_name." SET ".$query_xtra.", ".$table_created." = NOW(), ".$table_user." = '%d'");
									}
								}
							}

							else if($this->action == "delete")
							{
								if($rows > 0)
								{
									if($this->table == "posts")
									{
										$id = $wpdb->get_var("SELECT ".$table_id." FROM ".$table_name." WHERE ".$query_search);

										wp_trash_post($id);
									}

									else
									{
										$query_delete = $wpdb->prepare("UPDATE ".$table_name." SET ".$this->table."Deleted = '1', ".$this->table."DeletedDate = NOW(), ".$this->table."DeletedID = '%d' WHERE ".$query_search, get_current_user_id());

										$wpdb->query($query_delete);
									}

									if($wpdb->rows_affected > 0)
									{
										$this->rows_deleted++;

										$this->result[] = array(
											'action' => 'fa-close',
											'value' => $query_delete,
										);
									}

									else
									{
										$this->rows_not_deleted++;

										$this->result[] = array(
											'action' => 'fa-chain-broken',
											'value' => $query_delete,
										);
									}
								}

								else
								{
									$this->rows_not_exists++;

									$this->result[] = array(
										'action' => 'fa-question',
										'value' => $query_select,
									);
								}
							}

							else
							{
								$this->result[] = array(
									'action' => 'fa-question',
								);
							}
						}

						else
						{
							$this->result[] = array(
								'action' => 'fa-heartbeat',
								'value' => var_export($arr_values, true),
							);
						}

						if($i % 100 == 0)
						{
							sleep(0.1);
							set_time_limit(60);
						}
					}

					if($this->action == "import")
					{
						if($this->rows_updated > 0)
						{
							$out .= "<tr><td><i class='fa fa-lg fa-check green'></i></td><td>".$this->rows_updated."</td><td>".__("Updated", 'lang_base')."</td></tr>";
						}

						if($this->rows_up_to_date > 0)
						{
							$out .= "<tr><td><i class='fa fa-lg fa-cloud'></i></td><td>".$this->rows_up_to_date."</td><td>".__("Already up to date", 'lang_base')."</td></tr>";
						}

						if($this->rows_inserted > 0)
						{
							$out .= "<tr><td><i class='fa fa-lg fa-plus green'></i></td><td>".$this->rows_inserted."</td><td>".__("Inserted", 'lang_base')."</td></tr>";
						}

						if($this->rows_not_inserted > 0)
						{
							$out .= "<tr><td><i class='fa fa-lg fa-chain-broken red'></i></td><td>".$this->rows_not_inserted."</td><td>".__("Not inserted", 'lang_base')."</td></tr>";
						}

						if($this->rows_deleted > 0)
						{
							$out .= "<tr><td><i class='fa fa-lg fa-close red'></i></td><td>".$this->rows_deleted."</td><td>".__("Deleted", 'lang_base')."</td></tr>";
						}
					}

					else if($this->action == "delete")
					{
						if($this->rows_deleted > 0)
						{
							$out .= "<tr><td><i class='fa fa-lg fa-close red'></i></td><td>".$this->rows_deleted."</td><td>".__("Deleted", 'lang_base')."</td></tr>";
						}

						if($this->rows_not_deleted > 0)
						{
							$out .= "<tr><td><i class='fa fa-lg fa-chain-broken red'></i></td><td>".$this->rows_not_deleted."</td><td>".__("Not deleted", 'lang_base')."</td></tr>";
						}

						if($this->rows_not_exists > 0)
						{
							$out .= "<tr><td><i class='fa fa-lg fa-question'></i></td><td>".$this->rows_not_exists."</td><td>".__("Did not exist", 'lang_base')."</td></tr>";
						}
					}

					if(count($this->result) > 0 && IS_ADMIN)
					{
						$out .= "<tr><td colspan='3'></td></tr>";

						foreach($this->result as $row)
						{
							$action = $row['action'];
							$value = isset($row['value']) ? $row['value'] : "";

							$out .= "<tr>
								<td><i class='fa fa-lg ".$action."'></i></td>
								<td colspan='2'>".$value."</td>
							</tr>";
						}
					}

				$out .= "</tbody>
			</table>
			<br>";
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
		global $wpdb;

		$out = "";

		$out .= "<form action='#' method='post' class='mf_form mf_settings' enctype='multipart/form-data' id='mf_import' rel='import/check/".get_class($this)."'>"
			."<div id='poststuff' class='postbox'>
				<h3 class='hndle'>".__("Check", 'lang_base')."</h3>
				<div class='inside'>";

					if(count($this->actions) > 1)
					{
						$arr_data = array();

						$arr_data[''] = "-- ".__("Choose here", 'lang_base')." --";
						$arr_data['delete'] = __("Delete", 'lang_base');
						$arr_data['import'] = __("Import", 'lang_base');

						$out .= show_select(array('data' => $arr_data, 'name' => 'strTableAction', 'text' => __("Action", 'lang_base'), 'value' => $this->action));
					}

					else
					{
						$out .= input_hidden(array('name' => "strTableAction", 'value' => $this->actions[0]));
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
					.show_button(array('name' => "btnImportCheck", 'text' => __("Check", 'lang_base')))
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
		global $wpdb;

		$out = "";

		$count_temp_rows = count($this->data);

		if($this->action != '' && $count_temp_rows > 0)
		{
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

						$arr_data = array();
						$arr_data[''] = "-- ".__("Choose here", 'lang_base')." --";

						foreach($this->columns as $key => $value)
						{
							$arr_data[$key] = $value;
						}

						$out .= show_select(array('data' => $arr_data, 'name' => 'strRowCheck'.$i, 'value' => $strRowField, 'text' => __("Column", 'lang_base')." ".($i + 1)." <span>(".$import_text.")</span>"));
					}

					$out .= "&nbsp;"
					.show_button(array('name' => "btnImportRun", 'text' => __("Run", 'lang_base')))
					.wp_nonce_field('import_run', '_wpnonce', true, false)
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
									$out .= "<".$cell_tag.">".$arr_values[$j]."</".$cell_tag.">";
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

class mf_export
{
	function __construct()
	{
		$this->has_excel_support = is_plugin_active("mf_phpexcel/index.php");
		$this->dir_exists = true;

		$this->upload_path = $this->upload_url = $this->plugin = $this->type_name = $this->name = "";
		$this->types = $this->data = array();

		$this->actions = array(
			'' => "-- ".__("Choose here", 'lang_base')." --",
			'csv' => "CSV",
		);

		if($this->has_excel_support)
		{
			$this->actions['xls'] = "XLS";
		}

		$this->get_defaults();
		$this->create_dir();
		$this->fetch_request();
		echo $this->save_data();
	}

	function get_defaults(){}

	function create_dir()
	{
		global $error_text;

		list($this->upload_path, $this->upload_url) = get_uploads_folder($this->plugin);

		if(!is_dir($this->upload_path))
		{
			if(!mkdir($this->upload_path, 0755, true))
			{
				$this->dir_exists = false;
			}
		}

		if($this->dir_exists == false)
		{
			$error_text = sprintf(__("Could not create the folder %s. Please add the correct rights for the script to create a new subfolder", 'lang_base'), $this->upload_path);
		}
	}

	function fetch_request()
	{
		$this->type = check_var('intExportType');
		$this->action = check_var('strExportAction');
	}

	function get_export_data(){}

	function save_data()
	{
		global $wpdb, $error_text, $done_text;

		$out = "";

		if(isset($_REQUEST['btnExportRun']) && wp_verify_nonce($_REQUEST['_wpnonce'], 'export_run'))
		{
			if($this->action != '')
			{
				$this->get_export_data();

				if(count($this->data) > 0)
				{
					$file = sanitize_title_with_dashes(sanitize_title($this->name))."_".date("YmdHis").".".$this->action;

					if($this->action == 'csv')
					{
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

								$out_temp .= ($i > 0 ? $field_separator : "").$row_value;
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
					}

					else if($this->action == 'xls')
					{
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
									$objPHPExcel->setActiveSheetIndex(0)->setCellValue($cell, $col_value);
								}
							}
						}

						$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5'); //XLSX: Excel2007
						$objWriter->save($this->upload_path.$file);

						$done_text = __("Download the exported file here", 'lang_base').": <a href='".$this->upload_url.$file."'>".$file."</a>";
					}
				}

				else
				{
					$error_text = __("There was nothing to export", 'lang_base');
				}

				get_file_info(array('path' => $this->upload_path, 'callback' => "delete_files"));
			}

			else
			{
				$error_text = __("You have to choose a file type to export to", 'lang_base');
			}
		}
	}

	function get_form()
	{
		global $wpdb, $error_text;

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
						$out .= show_select(array('data' => $this->actions, 'name' => 'strExportAction', 'text' => __("File type", 'lang_base'), 'value' => $this->action));
					}

					$out .= show_button(array('name' => "btnExportRun", 'text' => __("Run", 'lang_base')))
					.wp_nonce_field('export_run', '_wpnonce', true, false)
				."</div>
			</div>
		</form>";

		return $out;
	}
}