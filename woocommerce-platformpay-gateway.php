<?php
/*
Plugin Name: PlatformPay - WooCommerce Gateway
Plugin URI: https://www.platformpay.com/
Description: Extends WooCommerce by Adding the PlatformPay Gateway.
Version: 1.0
Author: Volkan Erdogan
*/

global $pfpay_db_version;
$pfpay_db_version = '1.0';

// Those three functions below are mostly for installing a barebones database
// It doesn't do much in this version, but will be useful if we implement a dashboard
function pfpay_install() {
	global $wpdb;
	global $pfpay_db_version;

	$table_name = $wpdb->prefix . 'pfpay_sales_data_test';
	
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		price numeric(8,2) DEFAULT '0.00' NOT NULL,
		UNIQUE KEY id (id)
	) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

	add_option( 'pfpay_db_version', $pfpay_db_version );
}

function pfpay_install_data() {
	global $wpdb;
	
	$welcome_name = '';
	$welcome_text = 'Congratulations, you just completed the installation!';
	
	$table_name = $wpdb->prefix . 'pfpay_sales_data_test';
	
	$wpdb->insert( 
		$table_name, 
		array( 
			'time' => current_time( 'mysql' ), 
			'name' => $welcome_name, 
			'text' => $welcome_text, 
		) 
	);
}

register_activation_hook( __FILE__, 'pfpay_install' );
register_activation_hook( __FILE__, 'pfpay_install_data' );

function pfpay_uninstall() {
	global $wpdb;

	$table_name = $wpdb->prefix . 'pfpay_sales_data_test';
	$sql = "DROP TABLE IF EXISTS $table_name";
	$wpdb->query( $sql );
	delete_option( "pfpay_db_version" );
}

register_deactivation_hook( __FILE__, 'pfpay_install' );

//----------------------------------------------------------------------------------------//

add_action( 'plugins_loaded', 'pfpay_platformpay_init', 0 );
function pfpay_platformpay_init() {
	// Control if woocommerce is installed
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) return;
	
	include_once( 'woocommerce-platformpay.php' );

	// Add our payment gateway
	add_filter( 'woocommerce_payment_gateways', 'pfpay_add_platformpay_gateway' );
	function pfpay_add_platformpay_gateway( $methods ) {
		$methods[] = 'PFPAY_PlatformPay';
		return $methods;
	}
}

// Add a link to settings page of the plugin
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'pfpay_platformpay_action_links' );
function pfpay_platformpay_action_links( $links ) {
	$plugin_links = array(
		'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=pfpay_platformpay' ) . '">' . __( 'Settings', 'pfpay-platformpay' ) . '</a>',
	);

	return array_merge( $plugin_links, $links );	
}

//----------------------------------------------------------------------------------------//


// Add the "csid" value to the form that is required by the third party server
/*
add_action('woocommerce_after_order_notes', 'my_custom_checkout_field');
function my_custom_checkout_field( $checkout ) {
	
	echo '<div id="my_custom_checkout_field" style="visibility: hidden;"><h3>'.__('My Field').'</h3>';
				
	woocommerce_form_field( 'csid', array( 
		'type' 			=> 'text',
		'class'         => array('my-field-class form-row-wide'),
		'label' 		=> __('csid'), 
		'placeholder' 	=> __('placeholder'),
		'value'			=> __('value'),
		), $checkout->get_value( 'csid' ));
	
	echo '</div>';
	?>
		<script type='text/javascript' src='https://online-safest.com/pub/csid.js'></script>
	<?php
	
}
*/

// Check if set, and if it's not, add an error.
/*
add_action('woocommerce_checkout_process', 'my_custom_checkout_field_process');
function my_custom_checkout_field_process() {
    if ( ! $_POST['csid'] )
        wc_add_notice( __( 'csid is empty.' ), 'error' );
}
*/

// Add it to posted values
/*
add_action( 'woocommerce_checkout_update_order_meta', 'my_custom_checkout_field_update_order_meta' );
function my_custom_checkout_field_update_order_meta( $order_id ) {
    if ( ! empty( $_POST['csid'] ) ) {
        update_post_meta( $order_id, 'csid', esc_attr( $_POST['csid'] ) );
    }
}
*/


//----------------------------------------------------------------------------------------//

// Add coupon field
/*
add_filter('woocommerce_credit_card_form_fields', 'custom_credit_card_fields_add_coupon', 10, 2);
function custom_credit_card_fields_add_coupon( $default_fields , $payment_id ) {
	$bank = array(
		'card-coupon' => '<p class="form-row form-row-last">
								<label for="' . esc_attr( $payment_id ) . '-coupon">' . __( 'Coupon', 'woocommerce' ) . ' </label>
								<input id="' . esc_attr( $payment_id ) . '-coupon" class="input-text wc-credit-card-form-coupon" type="text" autocomplete="off" placeholder="' . esc_attr__( '', 'woocommerce' ) . '" name="' . $payment_id . '-coupon" />
						  </p>'
	);
	
	$default_fields = array_merge( $default_fields, $bank );
	
	return $default_fields;
	
}

add_action('woocommerce_checkout_update_order_meta', 'my_custom_credit_card_field_update_order_meta');
function my_custom_credit_card_field_update_order_meta( $order_id ) {
	if ($_POST['coupon']) update_post_meta( $order_id, 'coupon', esc_attr($_POST['coupon']));
}
*/

//----------------------------------------------------------------------------------------//

// Add name on card field
add_filter('woocommerce_credit_card_form_fields', 'custom_credit_card_fields_add_name_on_card', 10, 2);
function custom_credit_card_fields_add_name_on_card( $default_fields , $payment_id ) {
	$bank2 = array(
		'card-name-on-card' => '<p class="form-row form-row-first">
								<label for="' . esc_attr( $payment_id ) . '-name-on-card">' . __( 'Name on card', 'woocommerce' ) . ' <span class="required">*</span></label>
								<input id="' . esc_attr( $payment_id ) . '-name-on-card" class="input-text wc-credit-card-form-name-on-card" type="text" autocomplete="off" placeholder="' . esc_attr__( '', 'woocommerce' ) . '" name="' . $payment_id . '-name-on-card" />
								</p>'
	);
	
	$default_fields = array_merge( $default_fields, $bank2 );
	
	return $default_fields;
	
}

add_action('woocommerce_checkout_update_order_meta', 'my_custom_credit_card_field_update_order_meta2');
function my_custom_credit_card_field_update_order_meta2( $order_id ) {
	if ($_POST['nameOnCard']) update_post_meta( $order_id, 'nameOnCard', esc_attr($_POST['nameOnCard']));
}

//----------------------------------------------------------------------------------------//

// Make phone number required
add_filter( 'woocommerce_billing_fields', 'wc_npr_filter_phone', 10, 1 );
function wc_npr_filter_phone( $address_fields ) {
	$address_fields['billing_phone']['required'] = true;
		return $address_fields;
}

//----------------------------------------------------------------------------------------//

// Remove company field from billing fields
add_filter( 'woocommerce_checkout_fields' , 'custom_override_checkout_fields_remove_company' );
function custom_override_checkout_fields_remove_company( $fields ) {
     unset($fields['billing']['billing_company']);

     return $fields;
}

//----------------------------------------------------------------------------------------//

// Add email address to shipping fields
add_filter( 'woocommerce_shipping_fields', 'wc_npr_filter_email', 10, 1 );
function wc_npr_filter_email( $address_fields ) {
	$address_fields['shipping_company']['label'] = "Email Address";
		return $address_fields;
}

//----------------------------------------------------------------------------------------//

// Add currency field
add_filter('woocommerce_credit_card_form_fields', 'custom_credit_card_fields_add_card_currency', 10, 2);
function custom_credit_card_fields_add_card_currency( $default_fields , $payment_id ) {
	$bank3 = array(
		'card_currency' => '<p class="form-row form-row-last">Your credit card currency: <span class="required">*</span> <br>
								<select name="card_currency" id="card-currency" style="height:35px; margin-top: 10px; width: 490px;">
									<option value="USD">US Dollar (USD)</option>
									<option value="ARS">Argentine Peso (ARS)</option>
									<option value="AUD">Australian Dollar (AUD)</option>
									<option value="BAM">Bosnia Convertible Mark (BAM)</option>
									<option value="BRL">Brazilian Real (BRL)</option>
									<option value="GBP">British Pound Sterling (GBP)</option>
									<option value="BGN">Bulgarian Lev (BGN)</option>
									<option value="CAD">Canadian Dollar (CAD)</option>
									<option value="CLP">Chilean Peso (CLP)</option>
									<option value="CNY">Chinese Yuan Renminbi (CNY)</option>
									<option value="COP">Colombian Peso (COP)</option>
									<option value="HRK">Croatian Kuna (HRK)</option>
									<option value="CZK">Czech Koruna (CZK)</option>    
									<option value="DKK">Danish Krone (DKK)</option>
									<option value="DOP">Dominican Peso (DOP)</option>
									<option value="EGP">Egyptian Pound (EGP)</option>
									<option value="EUR">EURO (EUR)</option>
									<option value="FJD">Fiji Dollar (FJD)</option>
									<option value="DEM">German Deutsche Mark (DEM)</option>
									<option value="HKD">Hong Kong Dollar (HKD)</option>
									<option value="HUF">Hungary Forint (HUF)</option>
									<option value="INR">Indian Rupee (INR)</option>
									<option value="IQD">Iraqi Dinar (IQD)</option>
									<option value="ILS">Israeli New Sheqel (ILS)</option>
									<option value="JMD">Jamaican Dollar (JMD)</option>
									<option value="JPY">Japanese Yen (JPY)</option>
									<option value="JOD">Jordanian Dinar (JOD)</option>
									<option value="KWD">Kuwaiti Dinar (KWD)</option>
									<option value="MXN">Mexican Peso (MXN)</option>
									<option value="MYR">Malaysian Ringgit (MYR)</option>
									<option value="MAD">Moroccan Dirham (MAD)</option>
									<option value="NZD">New Zealand Dollar (NZD)</option>
									<option value="NOK">Norwegian Krone (NOK)</option>
									<option value="PKR">Pakistan Rupee (PKR)</option>
									<option value="PEN">Peru Nuevo Sol (PEN)</option>
									<option value="PLN">Poland Zloty (PLN)</option>
									<option value="PHP">Philippine Peso (PHP)</option>
									<option value="RON">Romania Leu (RON)</option>
									<option value="RUB">Russian Ruble (RUB)</option>
									<option value="ZAR">South African Rand (ZAR)</option>
									<option value="SGD">Singapore Dollar (SGD)</option>
									<option value="SAR">Saudi Riyal (SAR)</option>
									<option value="KRW">South Korean Won (KRW)</option>
									<option value="CHF">Swiss Franc (CHF)</option>
									<option value="SEK">Swedish Krona (SEK)</option>
									<option value="THB">Thailand Baht (THB)</option>
									<option value="TWD">Taiwan New Dollar (TWD)</option>
									<option value="TRY">Turkish Lira (TRY)</option>
									<option value="AED">UAE Dirham (AED)</option>
									<option value="VND">Viet Nam Dong (VND)</option>
								</select>
							</p> 
							<p class="form-row form-row-first"></p>
							<p class="form-row form-row-last" align="right">
								<!--
								<a href="https://platformpay.com"><img src="' . plugin_dir_url( __FILE__ ) . 'images/PlatformPay.png" alt="PlatformPay logo" height="100" width="100" style=""></a>
								<img src="https://upload.wikimedia.org/wikipedia/commons/thumb/0/0c/MasterCard_logo.png/640px-MasterCard_logo.png" alt="MasterCard logo" height="50" width="50">
								<img src="https://upload.wikimedia.org/wikipedia/commons/4/41/Visa_Logo.png" alt="Visa logo" height="50" width="50">
								-->
								<a href="https://platformpay.com"><img src="' . plugin_dir_url( __FILE__ ) . 'images/pp-secureimage.png" alt="PlatformPay logo" height="550" width="150" style=""></a>
							</p>'
	);
	
	$default_fields = array_merge( $default_fields, $bank3 );
	
	return $default_fields;
	
}

add_action('woocommerce_checkout_update_order_meta', 'my_custom_credit_card_field_update_order_meta3');
function my_custom_credit_card_field_update_order_meta3( $order_id ) {
	if ($_POST['card_currency']) update_post_meta( $order_id, 'card_currency', esc_attr($_POST['card_currency']));
}

//----------------------------------------------------------------------------------------//

// Modify CVC display
add_filter( 'woocommerce_credit_card_form_fields' , 'custom_credit_card_fields_cis_cc' , 10, 2 );
function custom_credit_card_fields_cis_cc($cc_fields , $payment_id){
	$cc_fields['card-cvc-field'] = str_replace(
		'<input ', 
		'<input style="width: 65px;"', 
		$cc_fields['card-cvc-field']
	);
	
	$cc_fields['card-cvc-field'] = str_replace(
		'</p>', 
		'<span>Last 3 digits on the back of your card.</span> <span><img src="http://calmfamilies.com.au/spitfire/icons/payment/cvv.png" alt=""></img></span></p>', 
		$cc_fields['card-cvc-field']
	);
	
	
	//$cc_fields['card-number-field'] = str_replace(
	//	'<p class="form-row form-row-wide">',
	//	'<p class="form-row form-row-wide"><a href="https://platformpay.com"><img src="' . plugin_dir_url( __FILE__ ) . 'images/PlatformPay.png" alt="PlatformPay logo" height="100" width="100" style="margin-left: -8px;"></a></p><p class="form-row form-row-wide">',
	//	$cc_fields['card-number-field']
	//);
	
	return $cc_fields;
}

//----------------------------------------------------------------------------------------//