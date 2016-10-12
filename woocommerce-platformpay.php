<?php
// Our payment gateway class
class PFPAY_PlatformPay extends WC_Payment_Gateway {
	public function get_title() {	
		return 'PlatformPay 
				<img src="https://upload.wikimedia.org/wikipedia/commons/thumb/0/0c/MasterCard_logo.png/640px-MasterCard_logo.png" alt="MasterCard logo" width="35" height="35"> 
				<img src="https://upload.wikimedia.org/wikipedia/commons/4/41/Visa_Logo.png" alt="Visa logo" width="35" height="35">';	
	}
	
	public function get_description() {	
		return '<img src="http://securitysoft.asia/smartphone/img/lock.png" alt="Padlock" width="30" height="30"> Pay securely using your credit card via PlatformPay.';	
	}
	
	// Set up our gateway's id, description, etc.
	function __construct() {

		$this->id = "pfpay_platformpay";

		// As seen on payment gateways page
		$this->method_title = __( "PlatformPay", 'pfpay-platformpay' );

		// As seen on payment options page
		$this->method_description = __( "PlatformPay Payment Gateway Plug-in for WooCommerce", 'pfpay-platformpay' );

		$this->title = $this->get_title();
		
		$this->description = $this->get_description();

		// To be able to add the default cc form
		$this->supports = array( 'default_credit_card_form' );

		// This basically defines our settings which are then loaded with init_settings()
		$this->init_form_fields();

		$this->init_settings();
		
		// Create variables from settings to use later
		foreach ( $this->settings as $setting_key => $value ) {
			$this->$setting_key = $value;
		}
		
		if ( is_admin() ) {
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		}		
	}

	// Build the administration fields for this gateway
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'		=> __( 'Enable / Disable', 'pfpay-platformpay' ),
				'label'		=> __( 'Enable this payment gateway', 'pfpay-platformpay' ),
				'type'		=> 'checkbox',
				'default'	=> 'no',
			),
			/*
			'title' => array(
				'title'		=> __( 'Title', 'pfpay-platformpay' ),
				'type'		=> 'text',
				'desc_tip'	=> __( 'Payment title the user will see during the checkout process.', 'pfpay-platformpay' ),
				'default'	=> __( 'PlatformPay', 'pfpay-platformpay' ),
			),
			'description' => array(
				'title'		=> __( 'Description', 'pfpay-platformpay' ),
				'type'		=> 'textarea',
				'desc_tip'	=> __( 'Payment description the user will see during the checkout process.', 'pfpay-platformpay' ),
				'default'	=> __( 'Pay securely using your credit card.', 'pfpay-platformpay' ),
				'css'		=> 'max-width:350px;'
			),
			*/
			'gateway_id' => array(
				'title'		=> __( 'PlatformPay gateway ID', 'pfpay-platformpay' ),
				'type'		=> 'text',
				'desc_tip'	=> __( 'This is the gateway ID provided by Platformpay.', 'pfpay-platformpay' ),
			),
			'auth_code' => array(
				'title'		=> __( 'Platformpay authorization code', 'pfpay-platformpay' ),
				'type'		=> 'password',
				'desc_tip'	=> __( 'This is the authorization code provided by PlatformPay.', 'pfpay-platformpay' ),
			),			
		);		
	}
	
	// Submit payment and handle response
	public function process_payment( $order_id ) {
		global $woocommerce;
		
		// Initiate the customer_order variable as the present order
		$order_facory = new WC_Order_Factory();
		$customer_order = $order_facory->get_order( $order_id );

		// Set the address of the third party server which we'll send the values
		$environment_url = ""; 

		
		// Process the name on card field. make sure it's not empty or has just one word in it				   
		$buyerNameOnCard = ( isset( $_POST['pfpay_platformpay-name-on-card'] ) ) ? trim( $_POST['pfpay_platformpay-name-on-card'] ) : '';

		if ( $buyerNameOnCard == '' ) {
			
			throw new Exception( __( "Name on Card is empty.", 'pfpay-platformpay' ) );
			
		} else {
			
			$arrNameOnCard = explode( ' ', $buyerNameOnCard );
			
			if ( count( $arrNameOnCard ) == 1 ) {
				
				throw new Exception( __( "Name on Card is wrong.", 'pfpay-platformpay' ) );
				
			} else {
				
				$lengthOfFirstWord = strlen( $arrNameOnCard[0] );
								
				$firstNameOnCard = $arrNameOnCard[0];
				
				$lastNameOnCard = substr( $buyerNameOnCard, $lengthOfFirstWord + 1 );
								
			}
		
		}
		
		
		///////////////
		
		// get the card currency and convert the order amount to the currency of the card if needed
		$cardCurrency = $_POST['card_currency'];
		// $orderCurrency = get_woocommerce_currency();
		$orderCurrency = $customer_order->get_order_currency();
		$orderAmount = trim( $customer_order->order_total );
		$orderAmountUsd = trim( $customer_order->order_total );
		
		/*
		if($orderCurrency != 'USD'){
			$from = $orderCurrency;
			$to = 'USD';
			$url = 'http://finance.yahoo.com/d/quotes.csv?f=l1d1t1&s='.$from.$to.'=X';
			$handle = fopen($url, 'r');
			 
			if ($handle) {
				$result = fgetcsv($handle);
				fclose($handle);
			}
			
			$orderAmountUsd = round($orderAmountUsd * $result[0]);
			
			// START: if Order amount is zero which means the api is not working...
			if($orderAmountUsd == 0){
				function currencyConverter($currency_from,$currency_to,$currency_input){
					$yql_base_url = "http://query.yahooapis.com/v1/public/yql";
					$yql_query = 'select * from yahoo.finance.xchange where pair in ("'.$currency_from.$currency_to.'")';
					$yql_query_url = $yql_base_url . "?q=" . urlencode($yql_query);
					$yql_query_url .= "&format=json&env=store%3A%2F%2Fdatatables.org%2Falltableswithkeys";
					$yql_session = file_get_contents($yql_query_url);
					$yql_json =  json_decode($yql_session,true);
					$currency_output = (float) $currency_input*$yql_json['query']['results']['rate']['Rate'];
					return $currency_output;
				}
				
				 $currency_input = $orderAmountUsd;
				 $currency_from = $orderCurrency;
				 $currency_to = 'USD';
				 $currency = currencyConverter($currency_from,$currency_to,$currency_input);
				
				 $orderAmountUsd = $currency;
			}
			// END: if Order amount is zero which means the api is not working...
		}
		*/
		
		///////////////

		$url = site_url();
		$parsed = parse_url( $url );
		$hostname = $parsed['host'];
		
		// Payload array contains the relevant values of the present transaction
		// These values will be sent both to the third party server, and to the PF server
		$payload = array(
		
			"merNo" 				=> "",
			"gatewayNo" 			=> "",
			"signkey" 				=> "",
						
			// "orderNo"			=> strval( trim( mt_rand( 1, 10000 ) ) ),
			"orderNo"				=> $order_id,
			"orderAmount"           => $orderAmountUsd,
			"orderCurrency" 		=> $orderCurrency,
			"paymentMethod" 		=> trim( "Credit Card" ),
			
			"cardNo"           		=> ( isset( $_POST['pfpay_platformpay-card-number'] ) ) ? trim( str_replace( array(' ', '-' ), '', $_POST['pfpay_platformpay-card-number'] ) ) : '',
			"cardSecurityCode"      => ( isset( $_POST['pfpay_platformpay-card-cvc'] ) ) ? trim( $_POST['pfpay_platformpay-card-cvc'] ) : '',
			"cardExpireYear" 		=> ( isset( $_POST['pfpay_platformpay-card-number'] ) ) ? trim( "20" . mb_substr( $_POST['pfpay_platformpay-card-expiry'], -2 ) ) : '', // substr yerine mb_substr kullanmak dogru mu?
			"cardExpireMonth"		=> trim(mb_substr( $_POST['pfpay_platformpay-card-expiry'], 0, 2 ) ),
			"issuingBank"			=> 'Bank of NY',
			
			"firstName"         	=> $firstNameOnCard,
			"lastName"          	=> $lastNameOnCard,
			"address"            	=> trim( $customer_order->billing_address_1 . $customer_order->billing_address_2 ),
			"city"              	=> trim( $customer_order->billing_city ),
			"state"              	=> trim( $customer_order->billing_state ),
			"zip"                	=> trim( $customer_order->billing_postcode ),
			"country"            	=> trim( $customer_order->billing_country ),
			"phone"              	=> trim( $customer_order->billing_phone ),
			"email"              	=> trim( $customer_order->billing_email ),
			
			"ship_to_first_name" 	=> trim( $customer_order->shipping_first_name ),
			"ship_to_last_name"  	=> trim( $customer_order->shipping_last_name ),
			"ship_to_company"    	=> trim( $customer_order->shipping_company ),
			"ship_to_address"    	=> trim( $customer_order->shipping_address_1 . $customer_order->shipping_address_2 ),
			"ship_to_city"       	=> trim( $customer_order->shipping_city ),
			"ship_to_country"    	=> trim( $customer_order->shipping_country ),
			"ship_to_state"      	=> trim( $customer_order->shipping_state ),
			"ship_to_zip"        	=> trim( $customer_order->shipping_postcode ),
			"ship_to_email"		 	=> trim( $customer_order->shipping_company ),
			
			"ip"        			=> trim( $_SERVER['REMOTE_ADDR'] ),
			"remark"    			=> trim( $customer_order->order_comments ),
			"interfaceInfo"			=> trim( "mystore" ),
			// "csid"				=> ( isset( $_POST['csid'] ) ) ? trim( $_POST['csid'] ) : '',
			"returnUrl"				=> $hostname,
			
		);
		
		
		// Stop the transaction if card number is empty
		if ($payload["cardNo"] == '') {
			
			throw new Exception( __( "Card number is empty.", 'pfpay-platformpay' ) );
			
		}
		
		// Control if the card is mastercard or visa
		// If it's visa, modify merNo, gatewayNo, and signkey variables
		// If it's neither mastercard nor visa, stop the transaction
		$firstCharOfCardNo = $payload["cardNo"][0];
		
		if ($firstCharOfCardNo == "4") {
			
			$payload["merNo"] = "";
			$payload["gatewayNo"] = "";
			$payload["signkey"] = "";
			
		} elseif ($firstCharOfCardNo == "5") {
			
			$temporary = 0;
			
		} else {
			
			throw new Exception( __( "Card is not MasterCard or Visa.", 'pfpay-platformpay' ) );
			
		}
		
		
		// Produce the signInfo that will be sent to the third party server
		$merged = ($payload["merNo"]).($payload["gatewayNo"]).($payload["orderNo"]).($payload["orderCurrency"]).($payload["orderAmount"]).($payload["firstName"]).($payload["lastName"]).($payload["cardNo"]).($payload["cardExpireYear"]).($payload["cardExpireMonth"]).($payload["cardSecurityCode"]).($payload["email"]).($payload["signkey"]);
		
		$signInfo = hash( "sha256", $merged );
		
		$payload["signInfo"] = $signInfo;
		
		
		// Send the payload array to the third party server, and get the response
		$curl = curl_init($environment_url);
		curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl,CURLOPT_HEADER, 0 );
		curl_setopt($curl,CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl,CURLOPT_POST,true); 
		curl_setopt($curl,CURLOPT_POSTFIELDS,http_build_query($payload));
		curl_setopt($curl,CURLOPT_REFERER,"");
		$xmlrs = curl_exec($curl);
		curl_close ($curl);
		
		$xmlob = simplexml_load_string(trim($xmlrs)) or wc_add_notice( 'xmlerror', 'error' );
		
		$merNo         = (string)$xmlob->merNo;
		$gatewayNo     = (string)$xmlob->gatewayNo;
		$tradeNo       = (string)$xmlob->tradeNo;
		$orderNo       = (string)$xmlob->orderNo;
		$orderAmount   = (string)$xmlob->orderAmount;
		$orderCurrency = (string)$xmlob->orderCurrency;
		$orderStatus   = (string)$xmlob->orderStatus;
		$orderInfo     = (string)$xmlob->orderInfo;
		$signInfo      = (string)$xmlob->signInfo;
		$riskInfo      = (string)$xmlob->riskInfo;
		
		$signInfocheck = hash("sha256", $merNo.$gatewayNo.$tradeNo.$orderNo.$orderCurrency.$orderAmount.$orderStatus.$orderInfo.$signkey);
		
		///////////////
		
		// Set the authentication info to authenticate by the platformpay server
		$authentication_info = array(
		
			"site_url" => $hostname,
			"gateway_id" => $this->settings['gateway_id'],
			"auth_code" => $this->settings['auth_code'],
			// "site_url" => "platformpay.com",
			// "gateway_id" => "",
			// "auth_code" => "",
			
		);
		
		$authentication_url = "";
		
		// Send the authentication info to the platformpay server and get the response
		$authentication_response = wp_remote_post( $authentication_url, array(
			'method'    	=> 'POST',
			'timeout' 		=> 45,
			'body'      	=> $authentication_info,
			'redirection' 	=> 5,
			'httpversion' 	=> '1.0',
			'headers' 		=> array(
			),
			'sslverify' => false
		) );		
		
		$authentication_response_body = wp_remote_retrieve_body( $authentication_response );
		
		// Stop the transaction if platformpay server doesn't send the go signal
		if ( strpos($authentication_response_body, '1') == FALSE ) {
			throw new Exception( __( "Authentication credentials are wrong or there is a problem with the PlatformPay server." . " " . $authentication_response_body  , 'pfpay-platformpay' ) );
		}
				
		///////////////
		
		
		///////////////
		
		// Add and modify a few values in the payload array to send to platformpay server again
		// This time, they will be sent to be recorded into the database
		$payload["orderStatus"] = $orderStatus;
		
		$payload["CardType"] = ( $firstCharOfCardNo == "5" ? "MasterCard" : "Visa" );
		
		$payload["DiffShipping_checkbox"] = $payload["ship_to_first_name"] == "" ? "0" : "1" ;
		
		$payload["firstName"] = trim( $customer_order->billing_first_name );
		
		$payload["lastName"] = trim( $customer_order->billing_last_name );
		
		$save_url = "";
		
		// Send and get response
		$save_response = wp_remote_post( $save_url, array(
			'method'    	=> 'POST',
			'timeout' 		=> 45,
			'body'      	=> $payload,
			'redirection' 	=> 5,
			'httpversion' 	=> '1.0',
			'headers' 		=> array(
			),
			'sslverify' => false
		) );
		
		$save_response_body = wp_remote_retrieve_body( $save_response );
		
		// Stop the transaction if platformpay server doesn't send the go signal
		if ( strpos($save_response_body, '1') == FALSE ) {
			throw new Exception( __( "Order can't be processed." . $save_response_body, 'pfpay-platformpay' ) );
		}		
		
		///////////////				
		
		// Check whether the payment is successful by looking at the orderStatus variable
		if ( $orderStatus == '1' )  {
			
			// If everything's ok, send a few more values to the platformpay server before finalizing the transaction
			$save_url = "";
			
			$cardType = "Visa";
			
			if ($firstCharOfCardNo == "5") {
				
				$cardType = "MasterCard";
				
			}
		
			$save_response = wp_remote_post( $save_url, array(
				'method'    	=> 'POST',
				'timeout' 		=> 45,
				'body'      	=> array("orderNo" => $payload["orderNo"], "CardType" => $cardType, "CardCurrency" => $cardCurrency),
				'redirection' 	=> 5,
				'httpversion' 	=> '1.0',
				'headers' 		=> array(
				),
				'sslverify' => false
			) );
			
			// Note that shows payment has been successful
			$customer_order->add_order_note( __( 'PlatformPay payment completed.', 'pfpay-platformpay' ) );
			
			$customer_order->reduce_order_stock();
												 
			$customer_order->payment_complete();

			$woocommerce->cart->empty_cart();

			return array(
				'result'   => 'success',
				'redirect' => $this->get_return_url( $customer_order ),
			);
			
		} else {
			wc_add_notice( 'Transaction was not succesful', 'error' );

			$customer_order->add_order_note( 'Error: '. 'Transaction was not succesful' );
			
			throw new Exception( __( $orderInfo , 'pfpay-platformpay' ) );
		}

	}

	public function do_ssl_check() {
		if( $this->enabled == "yes" ) {
			if( get_option( 'woocommerce_force_ssl_checkout' ) == "no" ) {
				echo "<div class=\"error\"><p>". sprintf( __( " SSL error " ), $this->method_title, admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ) ."</p></div>";	
			}
		}		
	}

}