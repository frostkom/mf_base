<?php

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

					//Range and total amount
					#####
					/*if($this->count == 0)
					{
						$total_value = $rows;

						$start_value = $intLimitStart + 1;
						$end_value = $this->current < $last ? $start_value + $this->per_page - 1 : $total_value;

						$out .= "<span class='em'>(".$start_value." - ".$end_value." ".__("of", 'lang_base')." ".$total_value.")</span>";
					}*/
					#####

				$out .= "</div>
			</div>";

			$this->count++;

			return $out;
		}
	}

	function button($data)
	{
		/*return "<form method='post' action=''>"
			.$this->search
			."<button type='submit' name='intLimitStart' value='".(($data['page'] - 1) * $this->per_page)."'".($this->current == $data['page'] ? " class='current_page'" : "").">"
				.(isset($data['text']) ? $data['text'] : $data['page'])
			."</button>
		</form>";*/

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

		$result = $wpdb->get_results($wpdb->prepare("SELECT queryURL, queryPaymentProvider, queryPaymentHmac, queryPaymentMerchant, queryPaymentCurrency, queryAnswerURL FROM ".$wpdb->base_prefix."query WHERE queryID = '%d'", $this->query_id));

		foreach($result as $r)
		{
			$this->prefix = isset($r->queryURL) && $r->queryURL != '' ? $r->queryURL."_" : "field_";
			$this->provider = $r->queryPaymentProvider;
			$this->hmac = $r->queryPaymentHmac;
			$this->merchant = $r->queryPaymentMerchant;
			$this->currency = $r->queryPaymentCurrency;
			$this->answer_url = $r->queryAnswerURL;
		}
	}

	function process_passthru($data)
	{
		global $wpdb;

		$out = "";

		$base_callback_url = get_site_url().$_SERVER['REQUEST_URI'];

		$this->amount = $data['amount'];
		$this->orderid = $data['orderid'];

		$this->test = $data['test'];

		if($this->provider == 1)
		{
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

			$instance['acceptreturnurl'] = $base_callback_url."?accept";
			$instance['callbackurl'] = $base_callback_url."?callback";
			$instance['cancelreturnurl'] = $base_callback_url."?cancel";

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
		}

		else
		{
			if($this->currency == ''){	$this->currency = "USD";}

			$instance = array();

			$this->action = "https://pay.skrill.com";
			$this->language = "EN"; //get_bloginfo('language') [sv_SE, en_US etc]

			$this->sid = get_url_content($this->action."/?pay_to_email=".$this->merchant."&amount=".$this->amount."&currency=".$this->currency."&language=".$this->language."&prepare_only=1");

			$transaction_id = $this->prefix.$this->orderid;

			$out .= "<form name='form_payment' action='".$this->action."' method='post'>
				<input type='hidden' name='session_ID' value='".$this->sid."'>
				<input type='hidden' name='pay_to_email' value='".$this->merchant."'>
				<input type='hidden' name='recipient_description' value='".get_bloginfo('name')."'>
				<input type='hidden' name='transaction_id' value='".$transaction_id."'>
				<input type='hidden' name='return_url' value='".$base_callback_url."?accept'>
				<input type='hidden' name='cancel_url' value='".$base_callback_url."?cancel&transaction_id=".$transaction_id."'>
				<input type='hidden' name='status_url' value='".$base_callback_url."?callback'>
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
		}

		return $out;

		exit;
	}

	function process_callback()
	{
		global $wpdb;

		$out = "";

		$request_type = substr($_SERVER['REQUEST_URI'], 15);

		$is_accept = isset($_GET['accept']) || $request_type == "accept";
		$is_callback = isset($_GET['callback']) || $request_type == "callback";
		$is_cancel = isset($_GET['cancel']) || $request_type == "cancel";

		//Debug
		##################
		$folder = str_replace("plugins/mf_base/include", "", dirname(__FILE__));

		$file_suffix = "unknown";

		if($is_accept){			$file_suffix = "accept";}
		else if($is_callback){	$file_suffix = "callback";}
		else if($is_cancel){	$file_suffix = "cancel";}

		$file = date("YmdHis")."_".$file_suffix;
		$debug = "URI: ".$_SERVER['REQUEST_URI']."\n\n"
			."GET: ".var_export($_GET, true)."\n\n"
			."POST: ".var_export($_POST, true)."\n\n"
			."THIS: ".var_export($this, true)."\n\n";

		$success = set_file_content(array('file' => $folder."/uploads/".$file, 'mode' => 'a', 'content' => trim($debug)));
		##################

		$amount = check_var('amount', 'int');

		$out .= __("Processing...", 'lang_base');

		if($this->provider == 1)
		{
			$orderid = check_var('orderid', 'char');

			$hmac = $this->hmac;
			$instance['merchant'] = $this->merchant;

			if($is_accept)
			{
				if($orderid != '')
				{
					$wpdb->query("UPDATE ".$wpdb->base_prefix."query_answer SET answerText = '104: ".__("User has paid. Waiting for confirmation...", 'lang_base')."' WHERE answerID = '".$orderid."' AND query2TypeID = '0' AND answerText LIKE '10%'");

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
							$wpdb->query("UPDATE ".$wpdb->base_prefix."query_answer SET answerText = '105: ".__("User has paid & has been sent to confirmation page. Waiting for confirmation...", 'lang_base')."' WHERE answerID = '".$orderid."' AND query2TypeID = '0' AND answerText LIKE '10%'");

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
				}
			}

			else if($is_callback)
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

				$arr_confirm_type = explode("_", $orderid);

				$strConfirmType = $arr_confirm_type[0];
				$strConfirmTypeID = $arr_confirm_type[1];

				if($is_valid_mac)
				{
					$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."query_answer SET answerText = '116: ".__("Payment done", 'lang_base')." (".($amount / 100).")' WHERE answerID = '%d' AND query2TypeID = '0'", $orderid));

					header("Status: 200 OK");
				}
				
				else
				{
					header("Status: 400 Bad Request");
				}
			}

			else if($is_cancel)
			{
				//Is the ID really sent with the cancle request?
				$wpdb->query("UPDATE ".$wpdb->base_prefix."query_answer SET answerText = '103: ".__("User canceled", 'lang_base')."' WHERE answerID = '".$orderid."' AND query2TypeID = '0' AND answerText LIKE '10%'");

				$cancel_url = get_site_url();

				mf_redirect($cancel_url);
			}
		}

		else
		{
			$transaction_id = check_var('transaction_id', 'char');
			$intAnswerID = str_replace($this->prefix, "", $transaction_id);

			if($is_accept)
			{
				if($intAnswerID > 0)
				{
					$wpdb->query("UPDATE ".$wpdb->base_prefix."query_answer SET answerText = '104: ".__("User has paid. Waiting for confirmation...", 'lang_base')."' WHERE answerID = '".$intAnswerID."' AND query2TypeID = '0' AND answerText LIKE '10%'");

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
							$wpdb->query("UPDATE ".$wpdb->base_prefix."query_answer SET answerText = '105: ".__("User has paid & has been sent to confirmation page. Waiting for confirmation...", 'lang_base')."' WHERE answerID = '".$intAnswerID."' AND query2TypeID = '0' AND answerText LIKE '10%'");

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
				}
			}

			else if($is_callback)
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
					case -2:		$payment_status_text = "Failed";			break;
					case 2:			$payment_status_text = "Processed";			break;
					case 0:			$payment_status_text = "Pending";			break;
					case -1:		$payment_status_text = "Cancelled";			break;
				}

				if($is_valid_mac)
				{
					$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."query_answer SET answerText = '116 - ".$status.": ".$payment_status_text." (".$amount." ".$currency.")' WHERE answerID = '%d' AND query2TypeID = '0'", $intAnswerID));

					header("Status: 200 OK");
				}
				
				else
				{
					$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."query_answer SET answerText = '115 - ".$status.": ".$payment_status_text." (".__("But could not verify MD5 signature", 'lang_base').", ".$md5sig." != ".$md5calc.") (".$amount." ".$currency.")' WHERE answerID = '%d' AND query2TypeID = '0'", $intAnswerID));

					header("Status: 400 Bad Request");
				}
			}

			else if($is_cancel)
			{
				$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."query_answer SET answerText = '103: ".__("User canceled", 'lang_base')."' WHERE answerID = '%d' AND query2TypeID = '0'", $intAnswerID));

				$cancel_url = get_site_url();

				mf_redirect($cancel_url);
			}
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