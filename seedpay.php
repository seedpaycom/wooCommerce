<?php
/**
 * Plugin Name: WooCommerce SeedPay Gateway
 * Plugin URI: http://seedpay.com
 * Description: Receive payments using Seedpay
 * Author: Seedpay
 * Author URI: http://seedpay.com/
 * Version: 1.0.0
 * WC tested up to: 3.3
 * WC requires at least: 2.6
 */
 
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}
	
	/**
	 * Required functions
	 */
	if ( ! function_exists( 'woothemes_queue_update' ) ) {
		require_once( 'woo-includes/woo-functions.php' );
	}
	
	
	 function seedpay_request($function,$fields,$method,$token= NULL){
		
		$curl = curl_init();
		$fields = json_encode($fields);
	
		$gateway_settings =get_option('woocommerce_seedpay_settings');
		if($gateway_settings['environment'] == 'yes'){
			
			$url =  'https://staging.api.seedpay.com';
		}else{
			$url =  'https://api.seedpay.com';
		}
		
		$headers = array();
		$headers[] = "Content-Type: application/json";
		if($token != NULL){
		$headers[] = "x-access-token: ".$token."";	
		}
		if($method == 'GET'){
		unset($fields);	
		}
		$data = array(
		  CURLOPT_URL => "".$url."/".$function."",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 0,
		  CURLOPT_FOLLOWLOCATION => false,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => $method,
		  CURLOPT_POSTFIELDS =>$fields,
		  CURLOPT_HTTPHEADER => $headers,
		);
		curl_setopt_array($curl, $data );
		#print_r($data);
		$response = curl_exec($curl);
		#print_r($response);
		$err = curl_error($curl);
	
		curl_close($curl);
		
		if ($err) {
		  return $err;
		} else {
		 return json_decode($response);
		}
			
		
		
		
	}

	function ajax_seedpay_submit_request(){
		$gateway_settings =get_option('woocommerce_seedpay_settings');
		
		$phone = wc_format_phone_number($_REQUEST['phone'] );
		$cart = WC()->cart;
	
		
		
		$message = array();
		$message['error'] = '';
		$message['post'] = $_REQUEST;
	
		if($phone != ''){
		
		$request = array(				
				'fromPhoneNumber'=>$phone,
				'amount' =>$cart->total,
				'uniqueTransactionId'=>$_COOKIE['seedpay_cart_id']							
			);
		$message['request'] = $request;
		
		$response=	seedpay_request('requestPayment',$request,'POST',$gateway_settings['token']);
		
		$message['response'] = $response;
			
		}else{
			
		$message['error'] = __('Please add a valid SeedPay phone number.', 'woocommerce-gateway-seedpay');	
		}
		if( $response->error!= ''){
		$message['error'] =  $response->error;	
		}
			
	echo json_encode($message);
	die();	
	}
	//ajax calls
	add_action( 'wp_ajax_ajax_seedpay_submit_request','ajax_seedpay_submit_request' );
	add_action( 'wp_ajax_nopriv_ajax_seedpay_submit_request','ajax_seedpay_submit_request' );
	
	
	
	
	
	function ajax_seedpay_check_request(){
		$gateway_settings =get_option('woocommerce_seedpay_settings');
		if($gateway_settings['environment'] == 'yes'){
			
			$site_url =  'https://staging.api.seedpay.com';
		}else{
			$site_url =  'https://api.seedpay.com';
		}
		
		
		$transaction_id = $_REQUEST['transaction_id'];
		$phone = wc_format_phone_number($_REQUEST['phone'] );
		$cart = WC()->cart;
	
		
		
		$message = array();
		$message['error'] = '';
		$message['post'] = $_REQUEST;
		
		if($phone != ''){
		
		$request = array(				
				'password' =>$gateway_settings['password'],
				'phoneNumber'=>$phone						
			);
		$message['request'] = $request;
		$getVars = htmlentities(urlencode(json_encode(array('uniqueTransactionId'=>$transaction_id))));
		$url = '/transactions/'.$getVars.'';
		$message['url'] = $site_url.$url;
		
		
		$response=	seedpay_request($url,array(),'GET',$gateway_settings['token']);
	#	print_r($response);
		if($response[0]->status == 'acceptedAndPaid'){
			set_transient( 'seedpay_order_status_'.$transaction_id.'',$response[0], 168 * HOUR_IN_SECONDS );	
			set_transient( 'seedpay_order_phone_'.$transaction_id.'',$phone, 168 * HOUR_IN_SECONDS );	
			
		}
		$message['response'] =$response;
		}else{
			
		$message['error'] = __('Please add a valid SeedPay phone number.', 'woocommerce-gateway-seedpay');	
		}
			
	echo json_encode($message);
	die();	
	}
	//ajax calls
	add_action( 'wp_ajax_ajax_seedpay_check_request','ajax_seedpay_check_request' );
	add_action( 'wp_ajax_nopriv_ajax_seedpay_check_request','ajax_seedpay_check_request' );
	
	
	function woocommerce_seedpay_init(){
		
		require_once( plugin_basename( 'includes/class-wc-gateway-seedpay.php' ) );
		load_plugin_textdomain( 'woocommerce-gateway-seedpay', false, trailingslashit( dirname( plugin_basename( __FILE__ ) ) ) );
		add_filter( 'woocommerce_payment_gateways', 'woocommerce_seedpay_add_gateway' );
		
		
		
	}
	add_action( 'plugins_loaded', 'woocommerce_seedpay_init', 0 );
	function woocommerce_seedpay_add_gateway( $methods ) {
		$methods[] = 'WC_Gateway_SeedPay';
		return $methods;
	}
	
	function woocommerce_seedpay_plugin_links( $links ) {
		$settings_url = add_query_arg(
			array(
				'page' => 'wc-settings',
				'tab' => 'checkout',
				'section' => 'wc_gateway_seedpay',
			),
			admin_url( 'admin.php' )
		);
	
		$plugin_links = array(
			'<a href="' . esc_url( $settings_url ) . '">' . __( 'Settings', 'woocommerce-gateway-seedpay' ) . '</a>'
		);
	
		return array_merge( $plugin_links, $links );
	}
	add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'woocommerce_seedpay_plugin_links' );
