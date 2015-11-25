<?php

//core.trac.wordpress.org/browser/tags/4.3.1/src//wp-admin/includes/class-wp-list-table.php#L0
if(!class_exists('WP_List_Table'))
{
	require_once(ABSPATH.'wp-admin/includes/class-wp-list-table.php');
}

class mf_list_table extends WP_List_Table
{
	var $arr_settings = array();
	var $post_type = "";
	var $orderby_default = "post_title";

	var $views = array();
	var $per_page = 10;
	var $columns = array();
	var $sortable_columns = array();
	var $data = "";
	var $total_items = 0;
	var $query_join = "";
	var $query_where = "";
	var $search = "";
	var $orderby = "";
	var $order = "";
	var $is_admin = false;
	var $page = "";

	function __construct()
	{
		global $wpdb;

		//Set parent defaults
		parent::__construct(array(
			'singular' => '', //singular name of the listed records
			'plural' => '', //plural name of the listed records
			'ajax' => false //does this table support ajax?
		));

		$this->page = check_var('page', 'char');
		$this->search = check_var('s', 'char', true);

		$this->arr_settings = array(
			'query_from' => $wpdb->posts,
			'query_select_id' => "ID",
			'query_all_id' => "all",
			'query_trash_id' => "trash",
			'has_autocomplete' => false,
		);

		$this->set_default();

		if($this->arr_settings['has_autocomplete'] == true)
		{
			wp_enqueue_script('jquery-ui-autocomplete');
			mf_enqueue_script('script_base_table', plugins_url()."/mf_base/include/script_table.js", array('plugins_url' => plugins_url(), 'plugin_name' => $this->arr_settings['plugin_name']));
		}

		$this->orderby = check_var('orderby', 'char', true, $this->orderby_default);
		$this->order = check_var('order', 'char', true, 'asc');

		$this->is_admin = current_user_can("update_core");
		$this->is_editor = current_user_can("edit_pages");
	}

	function set_default(){}

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

			if(get_current_user_id() == 1)
			{
				$result = $wpdb->get_results("SELECT ".$this->arr_settings['query_select_id']." FROM ".$this->arr_settings['query_from']." WHERE ".$db_field." = '1' AND ".$db_field."Date < DATE_SUB(NOW(), INTERVAL ".$empty_trash_days." DAY)");

				if($wpdb->num_rows > 0)
				{
					echo "Use obj->delete() on ".$db_field."<br>";
				}
			}
		}
	}

	function set_views($data)
	{
		global $wpdb;

		$this->empty_trash($data['db_field']);

		$db_value = check_var($data['db_field'], 'char', true, $this->arr_settings['query_all_id']);

		$query = "SELECT COUNT(".$this->arr_settings['query_select_id'].") FROM ".$this->arr_settings['query_from'].$this->query_join;

		$data['where_post_type'] = ($this->post_type != '' ? "post_type = '".$this->post_type."'" : "");

		if($data['where_post_type'] != '' || $this->query_where != '') // || $data['where'] != ''
		{
			$query .= " WHERE ";

			if($data['where_post_type'] != '')
			{
				$query .= $data['where_post_type'];
			}

			if($this->query_where != '')
			{
				$query .= ($data['where_post_type'] != '' ? " AND " : "").$this->query_where;
			}

			/*if($data['where'])
			{
				$query .= ($data['where_post_type'] != '' || $this->query_where != '' ? " AND " : "").$data['where'];
			}*/
		}

		foreach($data['types'] as $key => $value)
		{
			$query_this = $query.($data['where_post_type'] != '' || $this->query_where != '' ? " AND " : " WHERE ")
			.$this->get_views_trash_string($key, $data['db_field']);

			$amount = $wpdb->get_var($query_this);

			if($amount > 0)
			{
				$this->views[$key] = "<a href='admin.php?page=".$this->page."&".$data['db_field']."=".$key."'".($key == $db_value ? " class='current'" : "").">".$value." <span class='count'>(".$amount.")</span></a>"; //$this->post_type."/list/index.php
			}
		}

		$this->query_where .= ($this->query_where != '' ? " AND " : "").$this->get_views_trash_string($db_value, $data['db_field']);
	}

	function get_views_trash_string($value, $field)
	{
		return ($value == $this->arr_settings['query_all_id'] ? $field." != '".$this->arr_settings['query_trash_id']."'" : $field." = '".$value."'");
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

	/*function column_title($item)
	{
		//Build row actions
		$actions = array(
			'edit' => sprintf("<a href='?page=%s&action=%s&movie=%s'>".__("Edit", 'lang_base')."</a>", $_REQUEST['page'], 'edit', $item['ID']),
			'delete' => sprintf("<a href='?page=%s&action=%s&movie=%s'>".__("Delete", 'lang_base')."</a>", $_REQUEST['page'], 'delete', $item['ID']),
		);
		
		//Return the title contents
		return sprintf('%1$s <span style="color:silver">(id:%2$s)</span>%3$s',
			$item['title'],
			$item['ID'],
			$this->row_actions($actions)
		);
	}*/

	/** ************************************************************************
	 * REQUIRED if displaying checkboxes or using bulk actions! The 'cb' column
	 * is given special treatment when columns are processed. It ALWAYS needs to
	 * have it's own method.
	 * 
	 * @see WP_List_Table::::single_row_columns()
	 * @param array $item A singular item (one full row's worth of data)
	 * @return string Text to be placed inside the column <td> (movie title only)
	 **************************************************************************/
	function column_cb($item){
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			/*$1%s*/ $this->_args['singular'],  //Let's simply repurpose the table's singular label ("movie")
			/*$2%s*/ $item['ID']				//The value of the checkbox should be the record's id
		);
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
			$actions['delete'] = 'Delete';
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
	function process_bulk_action()
	{		
		//Detect when a bulk action is being triggered...
		if('delete' === $this->current_action())
		{
			wp_die('Items deleted (or they would be if we had items to delete)!');
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

		/**
		 * Optional. You can handle your bulk actions however you see fit. In this
		 * case, we'll handle them within our package just to keep things clean.
		 */
		$this->process_bulk_action();

		$current_page = $this->get_pagenum();

		$this->total_items = count($this->data);

		$this->data = array_slice($this->data, (($current_page - 1) * $this->per_page), $this->per_page);

		$this->items = $this->data;

		$this->set_pagination_args( array(
			'total_items' => $this->total_items,
			'per_page'	=> $this->per_page,
			'total_pages' => ceil($this->total_items / $this->per_page)
		) );
	}

	protected function get_table_classes()
	{
		return array('widefat', 'striped'); //, 'fixed', $this->_args['plural']
	}

	function show_search_form()
	{
		echo "<form method='post'>";

			$this->search_box(__("Search", 'lang_base'), 's');
		
		echo "</form>";
	}

	function select_data($data)
	{
		global $wpdb;

		/*if(!isset($data['from']))
		{
			$data['from'] = $wpdb->posts;
			$data['where_post_type'] = " post_type = '".$this->post_type."'";
		}*/

		$data['where_post_type'] = ($this->post_type != '' ? "post_type = '".$this->post_type."'" : "");

		if(!isset($data['join'])){		$data['join'] = "";}
		if(!isset($data['where'])){		$data['where'] = "";}
		if(!isset($data['group_by'])){	$data['group_by'] = $this->arr_settings['query_select_id'];}

		$query = "SELECT ".$data['select']." FROM ".$this->arr_settings['query_from'].$this->query_join.$data['join'];
		
		if($data['where_post_type'] != '' || $this->query_where != '' || $data['where'] != '')
		{
			$query .= " WHERE ";

			if($data['where_post_type'] != '')
			{
				$query .= $data['where_post_type'];
			}

			if($this->query_where != '')
			{
				$query .= ($data['where_post_type'] != '' ? " AND " : "").$this->query_where;
			}

			if($data['where'])
			{
				$query .= ($data['where_post_type'] != '' || $this->query_where != '' ? " AND " : "").$data['where'];
			}
		}

		if($data['group_by'] != '')
		{
			$query .= " GROUP BY ".$data['group_by'];
		}

		if($this->orderby != '')
		{
			$query .= " ORDER BY ".$this->orderby." ".$this->order;
		}

		$result = $wpdb->get_results($query);

		$this->data = json_decode(json_encode($result), true);
	}

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
	function pagination()
	{
		$this->range = 5;
		$this->per_page = 20;
		$this->count = 0;
	}

	function show($data)
	{
		global $intLimitStart; //$globals, 

		//$this->search = isset($globals['pag_data']) ? $globals['pag_data'] : "";

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

class MySettingsPage
{
	/**
	 * Holds the values to be used in the fields callbacks
	 */
	private $options;

	/**
	 * Start up
	 */
	public function __construct()
	{
		$this->options_page = "settings_mf_base";

		add_action('admin_menu', array($this, 'add_plugin_page'));
	}

	/**
	 * Add options page
	 */
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

	/**
	 * Options page callback
	 */
	public function create_admin_page()
	{
		// Set class property
		//$this->options = get_option('my_option_name');

		echo "<div class='wrap'>
			<h2>".__("My Settings", 'lang_base')."</h2>
			<form method='post' action='options.php'>";

				settings_fields($this->options_page);
				do_settings_sections($this->options_page);
				submit_button(); 

			echo "</form>
		</div>";
	}
}

class mf_form_payment
{
	function mf_form_payment($data = array())
	{
		global $wpdb;

		$this->query_id = $data['query_id'];
		$this->base_callback_url = get_site_url().$_SERVER['REQUEST_URI'];

		$result = $wpdb->get_results($wpdb->prepare("SELECT queryPaymentProvider, queryPaymentHmac, queryPaymentMerchant, queryPaymentPassword, queryPaymentCurrency, queryAnswerURL FROM ".$wpdb->base_prefix."query WHERE queryID = '%d'", $this->query_id));

		foreach($result as $r)
		{
			$this->provider = $r->queryPaymentProvider;
			$this->hmac = $r->queryPaymentHmac;
			$this->merchant = $r->queryPaymentMerchant;
			$this->password = $r->queryPaymentPassword;
			$this->currency = $r->queryPaymentCurrency;
			$this->answer_url = $r->queryAnswerURL;

			$obj_form = new mf_form($this->query_id);

			$this->prefix = $obj_form->get_post_name()."_";
		}
	}

	function PPHttpPost($methodName_, $nvpStr_, $PayPalApiUsername, $PayPalApiPassword, $PayPalApiSignature, $PayPalMode)
	{
		// Set up your API credentials, PayPal end point, and API version.
		$API_UserName = urlencode($PayPalApiUsername);
		$API_Password = urlencode($PayPalApiPassword);
		$API_Signature = urlencode($PayPalApiSignature);

		$paypalmode = ($PayPalMode == 'sandbox') ? '.sandbox' : '';

		$API_Endpoint = "https://api-3t".$paypalmode.".paypal.com/nvp";
		$version = urlencode('109.0');

		// Set the curl parameters.
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $API_Endpoint);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);

		// Turn off the server and peer verification (TrustManager Concept).
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);

		// Set the API operation, version, and API signature in the request.
		$nvpreq = "METHOD=$methodName_&VERSION=$version&PWD=$API_Password&USER=$API_UserName&SIGNATURE=$API_Signature$nvpStr_";

		// Set the request as a POST FIELD for curl.
		curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpreq);

		// Get response from the server.
		$httpResponse = curl_exec($ch);

		if(!$httpResponse) {
			exit("$methodName_ failed: ".curl_error($ch).'('.curl_errno($ch).')');
		}

		// Extract the response details.
		$httpResponseAr = explode("&", $httpResponse);

		$httpParsedResponseAr = array();
		foreach ($httpResponseAr as $i => $value) {
			$tmpAr = explode("=", $value);
			if(sizeof($tmpAr) > 1) {
				$httpParsedResponseAr[$tmpAr[0]] = $tmpAr[1];
			}
		}

		if((0 == sizeof($httpParsedResponseAr)) || !array_key_exists('ACK', $httpParsedResponseAr)) {
			exit("Invalid HTTP Response for POST request($nvpreq) to $API_Endpoint.");
		}

		return $httpParsedResponseAr;
	}

	function process_passthru($data)
	{
		global $wpdb;

		$out = "";

		$this->amount = $data['amount'];
		$this->orderid = $data['orderid'];

		$this->test = $data['test'];

		if($this->provider == 1)
		{
			$out .= $this->process_passthru_dibs();
		}

		else if($this->provider == 3)
		{
			$out .= $this->process_passthru_paypal();
		}

		else
		{
			$out .= $this->process_passthru_skrill();
		}

		return $out;

		exit;
	}

	function process_passthru_dibs()
	{
		global $wpdb;

		$out = "";

		if(!($this->currency > 0)){	$this->currency = 752;}

		$instance = array();

		$instance['amount'] = $this->amount * 100;
		$instance['orderid'] = $this->orderid;
		$instance['test'] = $this->test;

		$hmac = $this->hmac;
		$instance['merchant'] = $this->merchant;

		$instance['currency'] = $this->currency;
		$instance['paytype'] = "MC,VISA,MTRO,DIN,AMEX,DK,V-DK,ELEC"; //FFK,JCB
		$instance['language'] = get_bloginfo('language');

		$instance['acceptreturnurl'] = $this->base_callback_url."?accept";
		$instance['callbackurl'] = $this->base_callback_url."?callback";
		$instance['cancelreturnurl'] = $this->base_callback_url."?cancel";

		$instance['capturenow'] = 1;
		$dibs_action = "https://sat1.dibspayment.com/dibspaymentwindow/entrypoint";

		$out .= "<form name='form_payment' method='post' action='".$dibs_action."'>
			<input type='hidden' name='acceptreturnurl' value='".$instance['acceptreturnurl']."'>
			<input type='hidden' name='amount' value='".$instance['amount']."'>
			<input type='hidden' name='callbackurl' value='".$instance['callbackurl']."'>
			<input type='hidden' name='cancelreturnurl' value='".$instance['cancelreturnurl']."'>
			<input type='hidden' name='currency' value='".$instance['currency']."'>
			<input type='hidden' name='language' value='".$instance['language']."'>
			<input type='hidden' name='merchant' value='".$instance['merchant']."'>
			<input type='hidden' name='orderid' value='".$instance['orderid']."'>
			<input type='hidden' name='paytype' value='".$instance['paytype']."'>";

			if($instance['test'] == 1)
			{
				$out .= "<input type='hidden' name='test' value='".$instance['test']."'>";
			}

			else
			{
				unset($instance['test']);
			}

			if($instance['capturenow'] == 1)
			{
				$out .= "<input type='hidden' name='capturenow' value='".$instance['capturenow']."'>";
			}

			else
			{
				unset($instance['capturenow']);
			}

			//Calculate HMAC
			########
			$k = hextostr($hmac);

			$string = get_hmac_prepared_string($instance);

			$instance['mac'] = hash_hmac("sha256", $string, $k);

			$out .= "<input type='hidden' name='MAC' value='".$instance['mac']."' rel='".$string."'>";
			########

		$out .= "</form>";

		if(isset($instance['test']) && $instance['test'] == 1)
		{
			$out .= "<a href='http://tech.dibspayment.com/toolbox/test_information_cards'>".__("See DIBS test info", 'lang_base')."</a><br>
			<button type='button' onclick='document.form_payment.submit();'>".__("Send in test mode (No money will be charged)", 'lang_base')."</button>";
		}

		else
		{
			$out .= "<script>document.form_payment.submit();</script>";
		}

		$wpdb->query("UPDATE ".$wpdb->base_prefix."query_answer SET answerText = '102: ".__("Sent to payment", 'lang_base')."' WHERE answerID = '".$this->orderid."' AND query2TypeID = '0' AND answerText LIKE '10%'");

		return $out;
	}

	function save_token_with_answer_id()
	{
		global $wpdb;

		$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."query2answer SET answerToken = %s WHERE answerID = '%d'", urldecode($this->token), $this->orderid));
	}

	//https://developer.paypal.com/webapps/developer/docs/classic/express-checkout/integration-guide/ECCustomizing/
	function process_passthru_paypal()
	{
		global $wpdb;

		$out = "";

		$PayPalMode = $this->test == 1 ? 'sandbox' : 'live';

		$PayPalReturnURL = $this->base_callback_url."?accept";
		$PayPalCancelURL = $this->base_callback_url."?cancel";

		$this->language = get_site_language(array('language' => get_bloginfo('language'), 'type' => "last"));

		//Parameters for SetExpressCheckout, which will be sent to PayPal
		$padata = '&METHOD=SetExpressCheckout'.
			'&RETURNURL='.urlencode($PayPalReturnURL).
			'&CANCELURL='.urlencode($PayPalCancelURL).
			'&PAYMENTREQUEST_0_PAYMENTACTION='.urlencode("SALE").
			//'&L_PAYMENTREQUEST_0_AMT0='.urlencode($this->amount).
			'&NOSHIPPING=0'. //set 1 to hide buyer's shipping address, in-case products that does not require shipping
			//'&PAYMENTREQUEST_0_ITEMAMT='.urlencode($this->amount).
			'&PAYMENTREQUEST_0_AMT='.urlencode($this->amount).
			//'&L_PAYMENTREQUEST_0_QTY0=1'.
			'&PAYMENTREQUEST_0_CURRENCYCODE='.urlencode($this->currency).
			'&LOCALECODE='.$this->language; //PayPal pages to match the language on your website.
			//'&LOGOIMG='."http://". //site logo
			//'&CARTBORDERCOLOR=FFFFFF'. //border color of cart
			//'&ALLOWNOTE=1';

		//We need to execute the "SetExpressCheckOut" method to obtain paypal token
		$httpParsedResponseAr = $this->PPHttpPost('SetExpressCheckout', $padata, $this->merchant, $this->password, $this->hmac, $PayPalMode);

		//Respond according to message we receive from Paypal
		if("SUCCESS" == strtoupper($httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($httpParsedResponseAr["ACK"]))
		{
			$this->token = $httpParsedResponseAr["TOKEN"];

		 	$this->action = 'https://www'.$paypalmode.'.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token='.$this->token;

			$this->save_token_with_answer_id();

			mf_redirect($this->action);

			/*$out .= "<form name='form_payment' action='".$this->action."' method='get'></form>";

			if(isset($this->test) && $this->test == 1)
			{
				$out .= "<button type='button' onclick='document.form_payment.submit();'>".__("Send in test mode (No money will be charged)", 'lang_base')."</button>";
			}

			else
			{
				$out .= "<script>document.form_payment.submit();</script>";
			}*/
		}

		else
		{
			$out .= "<div class='error'>
				<p>Passthru: ".urldecode($httpParsedResponseAr["L_LONGMESSAGE0"])."</p>
				<p>".$padata."</p>
				<p>".var_export($httpParsedResponseAr, true)."</p>
			</div>";
		}

		return $out;
	}

	function process_passthru_skrill()
	{
		global $wpdb;

		$out = "";

		if($this->currency == ''){	$this->currency = "USD";}

		$instance = array();

		$this->action = "https://pay.skrill.com";
		$this->language = get_site_language(array('language' => get_bloginfo('language'), 'type' => "first")); //"EN"; //get_bloginfo('language') [sv_SE, en_US etc]

		$this->sid = get_url_content($this->action."/?pay_to_email=".$this->merchant."&amount=".$this->amount."&currency=".$this->currency."&language=".$this->language."&prepare_only=1");

		$transaction_id = $this->prefix.$this->orderid;

		$out .= "<form name='form_payment' action='".$this->action."' method='post'>
			<input type='hidden' name='session_ID' value='".$this->sid."'>
			<input type='hidden' name='pay_to_email' value='".$this->merchant."'>
			<input type='hidden' name='recipient_description' value='".get_bloginfo('name')."'>
			<input type='hidden' name='transaction_id' value='".$transaction_id."'>
			<input type='hidden' name='return_url' value='".$this->base_callback_url."?accept'>
			<input type='hidden' name='cancel_url' value='".$this->base_callback_url."?cancel&transaction_id=".$transaction_id."'>
			<input type='hidden' name='status_url' value='".$this->base_callback_url."?callback'>
			<input type='hidden' name='language' value='".$this->language."'>
			<input type='hidden' name='amount' value='".$this->amount."'>
			<input type='hidden' name='currency' value='".$this->currency."'>
		</form>";

		/*
			<input type='hidden' name='merchant_fields' value='customer_number, session_id'>
			<input type='hidden' name='customer_number' value='C1234'>

			<input type='hidden' name='pay_from_email' value='payer@skrill.com'>
			<input type='hidden' name='amount2_description' value='Product Price: '>
			<input type='hidden' name='amount2' value='29.90'>
			<input type='hidden' name='amount3_description' value='Handling Fees & Charges: '>
			<input type='hidden' name='amount3' value='3.10'>
			<input type='hidden' name='amount4_description' value='VAT (20%): '>
			<input type='hidden' name='amount4' value='6.60'>
			<input type='hidden' name='firstname' value='John'>
			<input type='hidden' name='lastname' value='Payer'>
			<input type='hidden' name='address' value='Payerstreet'>
			<input type='hidden' name='postal_code' value='EC45MQ'>
			<input type='hidden' name='city' value='Payertown'>
			<input type='hidden' name='country' value='GBR'>
			<input type='hidden' name='detail1_description' value='Product ID: '>
			<input type='hidden' name='detail1_text' value='4509334'>
			<input type='hidden' name='detail2_description' value='Description: '>
			<input type='hidden' name='detail2_text' value='Romeo and Juliet (W.
			Shakespeare) '>
			<input type='hidden' name='detail3_description' value='Special Conditions: '>
			<input type='hidden' name='detail3_text' value='5-6 days for delivery'>
			<input type='hidden' name='confirmation_note' value='Sample merchant wishes you
			pleasure reading your new book! '>
		*/

		if(isset($this->test) && $this->test == 1)
		{
			$out .= "<button type='button' onclick='document.form_payment.submit();'>".__("Send in test mode (No money will be charged)", 'lang_base')."</button>";
		}

		else
		{
			$out .= "<script>document.form_payment.submit();</script>";
		}

		return $out;
	}

	function confirm_cancel()
	{
		global $wpdb;

		$wpdb->query("UPDATE ".$wpdb->base_prefix."query_answer SET answerText = '103: ".__("User canceled", 'lang_base')."' WHERE answerID = '".$this->answer_id."' AND query2TypeID = '0' AND answerText LIKE '10%'");

		mf_redirect(get_site_url());
	}

	function confirm_accept()
	{
		global $wpdb;

		if($this->answer_id > 0)
		{
			$wpdb->query("UPDATE ".$wpdb->base_prefix."query_answer SET answerText = '104: ".__("User has paid. Waiting for confirmation...", 'lang_base')."' WHERE answerID = '".$this->answer_id."' AND query2TypeID = '0' AND answerText LIKE '10%'");

			if($this->answer_url != '' && preg_match("/_/", $this->answer_url))
			{
				list($blog_id, $intQueryAnswerURL) = explode("_", $this->answer_url);
			}

			else
			{
				$blog_id = 0;
				$intQueryAnswerURL = $this->answer_url;
			}

			if($intQueryAnswerURL > 0)
			{
				//Switch to temp site
				####################
				$wpdbobj = clone $wpdb;
				$wpdb->blogid = $blog_id;
				$wpdb->set_prefix($wpdb->base_prefix);
				####################

				if($intQueryAnswerURL != $wp_query->post->ID)
				{
					$wpdb->query("UPDATE ".$wpdb->base_prefix."query_answer SET answerText = '105: ".__("User has paid & has been sent to confirmation page. Waiting for confirmation...", 'lang_base')."' WHERE answerID = '".$this->answer_id."' AND query2TypeID = '0' AND answerText LIKE '10%'");

					$strQueryAnswerURL = get_permalink($intQueryAnswerURL);

					mf_redirect($strQueryAnswerURL);
				}

				/*else
				{
					header("Status: 400 Bad Request");
				}*/

				//Switch back to orig site
				###################
				$wpdb = clone $wpdbobj;
				###################
			}

			/*else
			{
				header("Status: 400 Bad Request");
			}*/
		}

		else
		{
			header("Status: 400 Bad Request");
		}
	}

	function confirm_paid($message)
	{
		global $wpdb;

		$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."query_answer SET answerText = '116: ".$message."' WHERE answerID = '%d' AND query2TypeID = '0'", $this->answer_id));

		header("Status: 200 OK");
	}

	function confirm_error($message)
	{
		global $wpdb;

		$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."query_answer SET answerText = '115: ".$message."' WHERE answerID = '%d' AND query2TypeID = '0'", $this->answer_id));

		header("Status: 400 Bad Request");
	}

	function process_callback()
	{
		global $wpdb;

		$out = "";

		$request_type = substr($_SERVER['REQUEST_URI'], 15);

		$this->is_accept = isset($_GET['accept']) || $request_type == "accept";
		$this->is_callback = isset($_GET['callback']) || $request_type == "callback";
		$this->is_cancel = isset($_GET['cancel']) || $request_type == "cancel";

		//Debug
		##################
		$folder = str_replace("plugins/mf_base/include", "", dirname(__FILE__));

		$file_suffix = "unknown";

		if($this->is_accept){			$file_suffix = "accept";}
		else if($this->is_callback){	$file_suffix = "callback";}
		else if($this->is_cancel){	$file_suffix = "cancel";}

		$file = date("YmdHis")."_".$file_suffix;
		$debug = "URI: ".$_SERVER['REQUEST_URI']."\n\n"
			."GET: ".var_export($_GET, true)."\n\n"
			."POST: ".var_export($_POST, true)."\n\n"
			."THIS: ".var_export($this, true)."\n\n";

		$success = set_file_content(array('file' => $folder."/uploads/".$file, 'mode' => 'a', 'content' => trim($debug)));
		##################

		$this->amount = check_var('amount', 'int');

		$out .= __("Processing...", 'lang_base');

		if($this->provider == 1)
		{
			$out .= $this->process_callback_dibs();
		}

		else if($this->provider == 3)
		{
			$out .= $this->process_callback_paypal();
		}

		else
		{
			$out .= $this->process_callback_skrill();
		}

		return $out;
	}

	function process_callback_dibs()
	{
		global $wpdb;

		$out = "";

		$this->answer_id = check_var('orderid', 'char');

		$hmac = $this->hmac;
		$instance['merchant'] = $this->merchant;

		if($this->is_accept)
		{
			$this->confirm_accept();

			/*if($this->answer_id > 0)
			{
				$wpdb->query("UPDATE ".$wpdb->base_prefix."query_answer SET answerText = '104: ".__("User has paid. Waiting for confirmation...", 'lang_base')."' WHERE answerID = '".$this->answer_id."' AND query2TypeID = '0' AND answerText LIKE '10%'");

				if($this->answer_url != '' && preg_match("/_/", $this->answer_url))
				{
					list($blog_id, $intQueryAnswerURL) = explode("_", $this->answer_url);
				}

				else
				{
					$blog_id = 0;
					$intQueryAnswerURL = $this->answer_url;
				}

				if($intQueryAnswerURL > 0)
				{
					//Switch to temp site
					####################
					$wpdbobj = clone $wpdb;
					$wpdb->blogid = $blog_id;
					$wpdb->set_prefix($wpdb->base_prefix);
					####################

					if($intQueryAnswerURL != $wp_query->post->ID)
					{
						$wpdb->query("UPDATE ".$wpdb->base_prefix."query_answer SET answerText = '105: ".__("User has paid & has been sent to confirmation page. Waiting for confirmation...", 'lang_base')."' WHERE answerID = '".$this->answer_id."' AND query2TypeID = '0' AND answerText LIKE '10%'");

						$strQueryAnswerURL = get_permalink($intQueryAnswerURL);

						mf_redirect($strQueryAnswerURL);
					}

					else
					{
						header("Status: 400 Bad Request");
					}

					//Switch back to orig site
					###################
					$wpdb = clone $wpdbobj;
					###################
				}

				else
				{
					header("Status: 400 Bad Request");
				}
			}

			else
			{
				header("Status: 400 Bad Request");
			}*/
		}

		else if($this->is_callback)
		{
			$k = hextostr($hmac);

			if(isset($_POST['mobilelib']) && $_POST['mobilelib'] == "android")
			{
				$arr_from_post = array('lang', 'orderid', 'merchantid');

				$post_selection = array();

				foreach($arr_from_post as $str_from_post)
				{
					$post_selection[$str_from_post] = $_POST[$str_from_post];
				}

				$string = get_hmac_prepared_string($post_selection);
			}

			else
			{
				$string = get_hmac_prepared_string($_POST);
			}

			$mac = hash_hmac("sha256", $string, $k);
			$is_valid_mac = isset($_POST['MAC']) && $_POST['MAC'] == $mac;

			$arr_confirm_type = explode("_", $this->answer_id);

			$strConfirmType = $arr_confirm_type[0];
			$strConfirmTypeID = $arr_confirm_type[1];

			if($is_valid_mac)
			{
				$this->confirm_paid(__("Payment done", 'lang_base')." (".($this->amount / 100).")");

				/*$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."query_answer SET answerText = '116: ".__("Payment done", 'lang_base')." (".($this->amount / 100).")' WHERE answerID = '%d' AND query2TypeID = '0'", $this->answer_id));

				header("Status: 200 OK");*/
			}

			else
			{
				$this->confirm_error(__("Payment done", 'lang_base')." (".__("But could not verify", 'lang_base').", ".$mac." != ".$_POST['MAC'].")");

				//header("Status: 400 Bad Request");
			}
		}

		else if($this->is_cancel)
		{
			//Is the ID really sent with the cancel request?
			$this->confirm_cancel();

			/*$wpdb->query("UPDATE ".$wpdb->base_prefix."query_answer SET answerText = '103: ".__("User canceled", 'lang_base')."' WHERE answerID = '".$this->answer_id."' AND query2TypeID = '0' AND answerText LIKE '10%'");

			mf_redirect(get_site_url());*/
		}

		return $out;
	}

	function get_info_from_token()
	{
		global $wpdb;

		$result = $wpdb->get_results($wpdb->prepare("SELECT answerID, queryPaymentAmount FROM ".$wpdb->base_prefix."query INNER JOIN ".$wpdb->base_prefix."query2answer USING (queryID) WHERE answerToken = %s", $this->token));
		$r = $result[0];
		$this->answer_id = $r->answerID;
		$intQueryPaymentAmount = $r->queryPaymentAmount;

		$this->amount = $wpdb->get_var($wpdb->prepare("SELECT answerText FROM ".$wpdb->base_prefix."query_answer WHERE answerID = '%d' AND query2TypeID = '%d'", $this->answer_id, $intQueryPaymentAmount));
	}

	function process_callback_paypal()
	{
		global $wpdb;

		$out = "";

		$this->token = check_var('token', 'char');
		$payer_id = check_var('PayerID', 'char');

		$this->get_info_from_token();

		if($this->is_cancel)
		{
			$this->confirm_cancel();

			/*$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."query_answer SET answerText = '103: ".__("User canceled", 'lang_base')."' WHERE answerID = '%d' AND query2TypeID = '0'", $this->answer_id));

			mf_redirect(get_site_url());*/
		}

		else if($this->is_accept)
		{
			$padata = '&TOKEN='.urlencode($this->token).
				'&PAYERID='.urlencode($payer_id).
				'&PAYMENTREQUEST_0_PAYMENTACTION='.urlencode("SALE").
				//'&L_PAYMENTREQUEST_0_AMT0='.urlencode($this->amount).
				//'&PAYMENTREQUEST_0_ITEMAMT='.urlencode($this->amount).
				'&PAYMENTREQUEST_0_AMT='.urlencode($this->amount).
				//'&L_PAYMENTREQUEST_0_QTY0=1'.
				'&PAYMENTREQUEST_0_CURRENCYCODE='.urlencode($this->currency);

			//We need to execute the "DoExpressCheckoutPayment" at this point to Receive payment from user.
			$httpParsedResponseAr = $this->PPHttpPost('DoExpressCheckoutPayment', $padata, $this->merchant, $this->password, $this->hmac, $PayPalMode);

			//Check if everything went ok..
			if("SUCCESS" == strtoupper($httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($httpParsedResponseAr["ACK"])) 
			{
				$this->confirm_accept();

				/*if('Completed' == $httpParsedResponseAr["PAYMENTINFO_0_PAYMENTSTATUS"])
				{
					$out .= "<div class='updated'><p>Payment Received! Your product will be sent to you very soon!</p></div>";
				}

				else if('Pending' == $httpParsedResponseAr["PAYMENTINFO_0_PAYMENTSTATUS"])
				{
					$out .= "<div class='error'>
						<p>Transaction Complete, but payment is still pending!</p>
						<p>You need to manually authorize this payment in your <a target='_new' href='//paypal.com'>Paypal Account</a></p>
					</div>";
				}*/

				// GetTransactionDetails requires a Transaction ID, and GetExpressCheckoutDetails requires Token returned by SetExpressCheckOut
				$padata = '&TOKEN='.urlencode($this->token);

				$httpParsedResponseAr = $this->PPHttpPost('GetExpressCheckoutDetails', $padata, $this->merchant, $this->password, $this->hmac, $PayPalMode);

				if("SUCCESS" == strtoupper($httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($httpParsedResponseAr["ACK"])) 
				{
					$this->confirm_paid(__("Payment done", 'lang_base')." (".$this->amount.")");
				}

				else
				{
					$this->confirm_error(__("Payment done", 'lang_base')." (".__("But could not verify", 'lang_base').", Success - ".$this->token.")");

					/*$out .= "<div class='error'>
						<p>GetTransactionDetails failed: ".urldecode($httpParsedResponseAr["L_LONGMESSAGE0"])."</p>
						<p>".var_export($httpParsedResponseAr, true)."</p>
					</div>";*/

				}
			}

			else
			{
				$this->confirm_error(__("Payment done", 'lang_base')." (".__("But could not verify", 'lang_base').", ".$this->token.")");

				/*$out .= "<div class='error'>
					<p>Callback: ".urldecode($httpParsedResponseAr["L_LONGMESSAGE0"])."</p>
					<p>".$padata."</p>
					<p>".var_export($httpParsedResponseAr, true)."</p>
				</div>";*/
			}
		}

		return $out;
	}

	function process_callback_skrill()
	{
		global $wpdb;

		$out = "";

		$transaction_id = check_var('transaction_id', 'char');
		$this->answer_id = str_replace($this->prefix, "", $transaction_id);

		if($this->is_accept)
		{
			$this->confirm_accept();

			/*if($this->answer_id > 0)
			{
				$wpdb->query("UPDATE ".$wpdb->base_prefix."query_answer SET answerText = '104: ".__("User has paid. Waiting for confirmation...", 'lang_base')."' WHERE answerID = '".$this->answer_id."' AND query2TypeID = '0' AND answerText LIKE '10%'");

				if($this->answer_url != '' && preg_match("/_/", $this->answer_url))
				{
					list($blog_id, $intQueryAnswerURL) = explode("_", $this->answer_url);
				}

				else
				{
					$blog_id = 0;
					$intQueryAnswerURL = $this->answer_url;
				}

				if($intQueryAnswerURL > 0)
				{
					//Switch to temp site
					####################
					$wpdbobj = clone $wpdb;
					$wpdb->blogid = $blog_id;
					$wpdb->set_prefix($wpdb->base_prefix);
					####################

					if($intQueryAnswerURL != $wp_query->post->ID)
					{
						$wpdb->query("UPDATE ".$wpdb->base_prefix."query_answer SET answerText = '105: ".__("User has paid & has been sent to confirmation page. Waiting for confirmation...", 'lang_base')."' WHERE answerID = '".$this->answer_id."' AND query2TypeID = '0' AND answerText LIKE '10%'");

						$strQueryAnswerURL = get_permalink($intQueryAnswerURL);

						mf_redirect($strQueryAnswerURL);
					}

					else
					{
						header("Status: 400 Bad Request");
					}

					//Switch back to orig site
					###################
					$wpdb = clone $wpdbobj;
					###################
				}

				else
				{
					header("Status: 400 Bad Request");
				}
			}

			else
			{
				header("Status: 400 Bad Request");
			}*/
		}

		else if($this->is_callback)
		{
			//pay_to_email, pay_from_email, amount

			$md5sig = check_var('md5sig', 'char');
			$currency = check_var('currency', 'char');

			$merchant_id = check_var('merchant_id', 'char');
			$mb_amount = check_var('mb_amount', 'char');
			$mb_currency = check_var('mb_currency', 'char');
			$status = check_var('status', 'char');

			$md5calc = strtoupper(md5($merchant_id.$transaction_id.strtoupper(md5($this->hmac)).$mb_amount.$mb_currency.$status));

			$is_valid_mac = $md5sig == $md5calc;

			$payment_status_text = "";

			switch($status)
			{
				case -2:		$payment_status_text = __("Failed", 'lang_base');			break;
				case 2:			$payment_status_text = __("Processed", 'lang_base');		break;
				case 0:			$payment_status_text = __("Pending", 'lang_base');			break;
				case -1:		$payment_status_text = __("Cancelled", 'lang_base');		break;
			}

			if($is_valid_mac)
			{
				$this->confirm_paid($status.": ".$payment_status_text." (".$this->amount." ".$currency.")");

				/*$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."query_answer SET answerText = '116: ".$status.": ".$payment_status_text." (".$this->amount." ".$currency.")' WHERE answerID = '%d' AND query2TypeID = '0'", $this->answer_id));

				header("Status: 200 OK");*/
			}

			else
			{
				$this->confirm_error($status.": ".$payment_status_text." (".__("But could not verify", 'lang_base').", ".$md5sig." != ".$md5calc.") (".$this->amount." ".$currency.")");

				/*$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."query_answer SET answerText = '115: ".$status.": ".$payment_status_text." (".__("But could not verify MD5 signature", 'lang_base').", ".$md5sig." != ".$md5calc.") (".$this->amount." ".$currency.")' WHERE answerID = '%d' AND query2TypeID = '0'", $this->answer_id));

				header("Status: 400 Bad Request");*/
			}
		}

		else if($this->is_cancel)
		{
			$this->confirm_cancel();

			/*$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."query_answer SET answerText = '103: ".__("User canceled", 'lang_base')."' WHERE answerID = '%d' AND query2TypeID = '0'", $this->answer_id));

			mf_redirect(get_site_url());*/
		}

		return $out;
	}
}

//
######################
class mf_encryption 
{
	function mf_encryption($type)
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
	function mf_font_icons($id = "")
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
				$arr_out[] = array($icon, $icon);
			}
		}

		else
		{
			//echo var_export($this->fonts, true);

			foreach($this->fonts as $key => $fonts)
			{
				if($data['allow_optgroup'] == true)
				{
					$arr_out[] = array('opt_start', __($key, 'lang_font_icons'));
				}

					foreach($fonts as $icon)
					{
						$arr_out[] = array($icon, $icon);
					}

				if($data['allow_optgroup'] == true)
				{
					$arr_out[] = array('opt_end', '');
				}
			}
		}

		return $arr_out;
	}

	function get_font_awesome_icon_list()
	{
		$transient_key = "fontawesome_transient";

		$content = get_transient($transient_key);

		if($content == "")
		{
			$content = get_url_content("http://fortawesome.github.io/Font-Awesome/icons/");

			set_transient($transient_key, $content, WEEK_IN_SECONDS);
		}

		//$arr_icons = get_match_all("/icon\/(.*?)\"/s", $content, false);
		$arr_icons = get_match_all("/fa-(.*?)[ |\"]/s", $content, false);
		$arr_icons = array_unique($arr_icons[0]);
		$arr_icons = array_sort(array('array' => $arr_icons, 'on' => 1));

		return $arr_icons;
	}

	function get_symbol_tag($symbol, $title = "")
	{
		$out = "";

		if($symbol != '')
		{
			if(substr($symbol, 0, 5) == "icon-")
			{
				wp_enqueue_style('style_icomoon', plugins_url()."/mf_base/include/style_icomoon.css");

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
		//mf_enqueue_script('script_base_wp', plugins_url()."/mf_base/include/script_import.js", array('plugins_url' => plugins_url()));

		$this->table = $this->actions = $this->columns = $this->unique_columns = "";

		$this->row_separator = "
";
		$this->is_run = false;

		$this->rows_updated = $this->rows_up_to_date = $this->rows_inserted = $this->rows_not_inserted = $this->rows_deleted = $this->rows_not_deleted = $this->rows_not_exists = 0;

		$this->has_excel_support = is_plugin_active("mf_phpexcel/index.php");

		$this->get_defaults();
		$this->fetch_request();
	}

	function get_defaults(){}

	function get_external_value(&$strRowField, &$value){}

	function if_more_than_one($result){}

	function inserted_new($id){}

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
		$this->text = isset($_POST['strImportText']) ? $_POST['strImportText'] : "";

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

					for($i = 0; $i < $count_temp_rows; $i++)
					{
						$query_search = $query_xtra = "";

						$arr_values = $this->data[$i];
						$count_temp_values = count($arr_values);

						for($j = 0; $j < $count_temp_values; $j++)
						{
							$value = $arr_values[$j];

							$strRowField = check_var('strRowCheck'.$j);

							if($strRowField != '')
							{
								$this->get_external_value($strRowField, $value);

								if(in_array($strRowField, $this->unique_columns))
								{
									$query_search .= ($query_search != '' ? " OR " : "").$strRowField." = '".$value."'";
								}

								$query_xtra .= ($query_xtra != '' ? ", " : "").$strRowField." = '".str_replace('"', '', $value)."'";
							}
						}

						if($query_search != '')
						{
							if($query_xtra == '')
							{
								//$status_icon = "fa-ban";
								//$text = __("The row was empty so nothing was inserted", 'lang_base');
							}

							else
							{
								$result = $wpdb->get_results("SELECT ".$this->table."ID FROM ".$wpdb->base_prefix.$this->table." WHERE ".$query_search." ORDER BY ".$this->table."Created ASC LIMIT 0, 5");
								$rows = $wpdb->num_rows;

								if($this->action == "import")
								{
									if($rows > 0)
									{
										$wpdb->query("UPDATE ".$wpdb->base_prefix.$this->table." SET ".$this->table."Deleted = '0', ".$this->table."DeletedDate = '', ".$this->table."DeletedID = '', ".$query_xtra." WHERE ".$query_search);

										if($wpdb->rows_affected > 0)
										{
											//$status_icon = "fa-check";
											//$text = __("The row already existed and was updated", 'lang_base').($rows > 1 ? " (".$rows.")" : "");

											$this->rows_updated++;
										}

										else
										{
											//$status_icon = "fa-cloud";
											//$text = __("The row was already up to date", 'lang_base').($rows > 1 ? " (".$rows.")" : "");

											$this->rows_up_to_date++;
										}

										if($rows > 1)
										{
											$this->if_more_than_one($result);
										}
									}

									else
									{
										$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix.$this->table." SET ".$query_xtra.", ".$this->table."Created = NOW(), userID = '%d'", get_current_user_id()));

										if($wpdb->rows_affected > 0)
										{
											$this->inserted_new($wpdb->insert_id);

											//$status_icon = "fa-check";
											//$text = __("The row was inserted", 'lang_base');

											$this->rows_inserted++;
										}

										else
										{
											//$status_icon = "fa-ban";
											//$text = __("The row should have been inserted but something went wrong", 'lang_base');

											$this->rows_not_inserted++;
										}
									}
								}

								else if($this->action == "delete")
								{
									if($rows > 0)
									{
										$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix.$this->table." SET ".$this->table."Deleted = '1', ".$this->table."DeletedDate = NOW(), ".$this->table."DeletedID = '%d' WHERE ".$query_search, get_current_user_id()));

										if($wpdb->rows_affected > 0)
										{
											//$status_icon = "fa-close";
											//$text = __("The row existed so it was deleted", 'lang_base');

											$this->rows_deleted++;
										}

										else
										{
											//$status_icon = "fa-ban";
											//$text = __("The row should have been deleted but something went wrong", 'lang_base');

											$this->rows_not_deleted++;
										}
									}

									else
									{

										//$status_icon = "fa-ban";
										//$text = __("The row did not exist", 'lang_base');

										$this->rows_not_exists++;
									}
								}
							}
						}

						if($i % 10 == 0)
						{
							sleep(0.1);
							set_time_limit(60);
						}
					}

					if($this->action == "import")
					{
						$out .= "<tr><td><i class='fa fa-lg fa-check'></i></td><td>".$this->rows_updated."</td><td>".__("Updated", 'lang_base')."</td></tr>
						<tr><td><i class='fa fa-lg fa-cloud'></i></td><td>".$this->rows_up_to_date."</td><td>".__("Already up to date", 'lang_base')."</td></tr>
						<tr><td><i class='fa fa-lg fa-check'></i></td><td>".$this->rows_inserted."</td><td>".__("Inserted", 'lang_base')."</td></tr>
						<tr><td><i class='fa fa-lg fa-ban'></i></td><td>".$this->rows_not_inserted."</td><td>".__("Not inserted", 'lang_base')."</td></tr>
						<tr><td><i class='fa fa-lg fa-close'></i></td><td>".$this->rows_deleted."</td><td>".__("Deleted", 'lang_base')."</td></tr>";
					}

					else if($this->action == "delete")
					{
						$out .= "<tr><td><i class='fa fa-lg fa-close'></i></td><td>".$this->rows_deleted."</td><td>".__("Deleted", 'lang_base')."</td></tr>
						<tr><td><i class='fa fa-lg fa-ban'></i></td><td>".$this->rows_not_deleted."</td><td>".__("Not deleted", 'lang_base')."</td></tr>
						<tr><td><i class='fa fa-lg fa-ban'></i></td><td>".$this->rows_not_exists."</td><td>".__("Did not exist", 'lang_base')."</td></tr>";
					}

				$out .= "</tbody>
			</table>";
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

		else
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

						$arr_data[] = array("", "-- ".__("Choose here", 'lang_base')." --");
						$arr_data[] = array("delete", __("Delete", 'lang_base'));
						$arr_data[] = array("import", __("Import", 'lang_base'));

						$out .= show_select(array('data' => $arr_data, 'name' => 'strTableAction', 'text' => __("Action", 'lang_base'), 'compare' => $this->action));
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

					$arr_data = array();

					$arr_data[] = array('1', __("Yes", 'lang_base'));
					$arr_data[] = array('0', __("No", 'lang_base'));

					$out .= show_select(array('data' => $arr_data, 'name' => 'intImportSkipHeader', 'compare' => $this->skip_header, 'text' => __("Skip first row", 'lang_base')))
					.show_submit(array('name' => "btnImportCheck", 'text' => __("Check", 'lang_base')))
				."</div>
			</div>
			<div id='import_result'>"
				.$this->get_result()
			."</div>
		</form>";

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
						$value = $arr_values[$i];

						$strRowField = check_var('strRowCheck'.$i);

						$arr_data = array();

						$arr_data[] = array("", "-- ".__("Choose here", 'lang_base')." --");

						$result = $wpdb->get_results("SHOW FIELDS FROM ".$wpdb->base_prefix.$this->table);

						foreach($result as $r)
						{
							$strTableField = $r->Field;

							if(array_key_exists($strTableField, $this->columns))
							{
								$arr_data[] = array($strTableField, $this->columns[$strTableField]);
							}
						}

						$out .= show_select(array('data' => $arr_data, 'name' => 'strRowCheck'.$i, 'compare' => $strRowField, 'text' => __("Column", 'lang_base')." ".($i + 1)." <span>(".$value.")</span>"));
					}

					$out .= "&nbsp;"
					.show_submit(array('name' => "btnImportRun", 'text' => __("Run", 'lang_base')))
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