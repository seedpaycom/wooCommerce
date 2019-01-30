<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WC_Gateway_SeedPay extends WC_Payment_Gateway {

    /**
     * Constructor for the gateway.
     */
	public function __construct() {
		$this->id                 = 'seedpay';
		$this->icon               = apply_filters('woocommerce_cheque_icon', '');
		$this->has_fields         = true;
		$this->method_title       = __( 'SeedPay', 'woocommerce-gateway-seedpay' );
		$this->method_description = __( 'Gateway for SeedPay', 'woocommerce-gateway-seedpay' );

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();
		$this->testmode = $this->get_option( 'testmode' );
		// Define user set variables
		$this->title        = $this->get_option( 'title' );
		$this->description  = $this->get_option( 'description' );
		$this->instructions = $this->get_option( 'instructions', $this->description );
		$this->url = 'https://api.seedpay.com';
		
		if ( 'yes' === $this->testmode ) {
			$this->url = 'https://staging.api.seedpay.com';
			$this->add_testmode_admin_settings_notice();
		} 
		
		
		// Actions
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
    	add_action( 'woocommerce_thankyou_cheque', array( $this, 'thankyou_page' ) );

    	// Customer Emails
    	add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
    	add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
		
		//ajax calls
		add_action( 'wp_ajax_ajax_seedpay_submit_request', array($this,'ajax_seedpay_submit_request') );
		add_action( 'wp_ajax_nopriv_ajax_seedpay_submit_request',array($this,'ajax_seedpay_submit_request') );
		
	}
	public function payment_fields() {
 
	
	if ( $this->description ) {
		
		if ( $this->testmode == 'yes' ) {
			$this->description .= __('TEST MODE ENABLED. In test mode, you will be using staging api credentials.','woocommerce-gateway-seedpay');
			$this->description  = trim( $this->description );
		}
		
		echo wpautop( wp_kses_post( $this->description ) );
	}
 
	
	echo '<fieldset id="wc-' . esc_attr( $this->id ) . '-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">';
 
	
	do_action( 'woocommerce_credit_card_form_start', $this->id );
 
	
	echo '<div class="form-row form-row-wide"><label>'.__('PaySeed Phone Number','woocommerce-gateway-seedpay').' <span class="required">*</span></label>
		<input id="seedpay_payment_phone" name="seedpay_payment_phone" type="text" autocomplete="off"> <a href="#" class="seedpay-request-payment-submit seed-pay-button">'.__('Request Payment','woocommerce-gateway-seedpay').'</a>

		</div>
		
		<div class="clear"></div>';
 
	do_action( 'woocommerce_credit_card_form_end', $this->id );
 
	echo '<div class="clear"></div></fieldset>';
 
}	
	
	public function ajax_seedpay_submit_request(){
		
		$phone = wc_format_phone_number($_REQUEST['phone'] );
		$total = WC()->order->id;
		$order_id = $_REQUEST['order_id'];
		$order = new WC_Order( $order_id );
		$message = array();
		$message['error'] = '';
		$message['post'] = $_REQUEST;
		
		if($phone != ''){
		
		$request = array(				
				'fromPhoneNumber'=>$phone,
				'amount' =>$order->get_total(),
				'uniqueTransactionId'=>$order_id							
			);
		$message['request'] = $request;
		
			$this->request('requestPayment',$request,'POST');
			
		}else{
			
		$message['error'] = __('Please add a valid SeedPay phone number.', 'woocommerce-gateway-seedpay');	
		}
		
		echo json_encode($message);
	die();	
	}
	
	public function request($function,$fields,$method){
		
		$curl = curl_init();
		$fields = json_encode($fields);
		curl_setopt_array($curl, array(
		  CURLOPT_URL => "".$this->url."/".$function."",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 0,
		  CURLOPT_FOLLOWLOCATION => false,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => $method,
		  CURLOPT_POSTFIELDS =>$fields,
		  CURLOPT_HTTPHEADER => array(
			"Content-Type: application/json",
			"x-access-token: null"
		  ),
		));
		
		$response = curl_exec($curl);
		$err = curl_error($curl);
		
		curl_close($curl);
		
		if ($err) {
		  echo "cURL Error #:" . $err;
		} else {
		  echo $response;
		}
			
		
		
		
	}
	
	
	
	public function add_testmode_admin_settings_notice() {
		wc_add_notice( __('SeedPay is currently in Test Mode: ', 'woocommerce-gateway-seedpay'), 'error' );	
	}

	
    /**
     * Initialise Gateway Settings Form Fields
     */
    public function init_form_fields() {

    	$this->form_fields = array(
			'enabled' => array(
				'title'   => __( 'Enable/Disable', 'woocommerce-gateway-seedpay' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable SeedPay', 'woocommerce-gateway-seedpay' ),
				'default' => 'yes'
			),
			
			'title' => array(
				'title'       => __( 'Title', 'woocommerce-gateway-seedpay' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce-gateway-seedpay' ),
				'default'     => __( 'SeedPay', 'woocommerce-gateway-seedpay' ),
				'desc_tip'    => true,
			),
			'description' => array(
				'title'       => __( 'Description', 'woocommerce-gateway-seedpay' ),
				'type'        => 'textarea',
				'description' => __( 'Payment method description that the customer will see on your checkout.', 'woocommerce-gateway-seedpay' ),
				'default'     => __( 'Please enter your PaySeed phone number and approve the payment once received.', 'woocommerce-gateway-seedpay' ),
				'desc_tip'    => true,
			),
			'instructions' => array(
				'title'       => __( 'Instructions', 'woocommerce-gateway-seedpay' ),
				'type'        => 'textarea',
				'description' => __( 'Instructions that will be added to the thank you page and emails.', 'woocommerce-gateway-seedpay' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'environment' => array(
				'title'   => __( 'Test Mode', 'woocommerce-gateway-seedpay' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Test Servers', 'woocommerce-gateway-seedpay' ),
				'default' => 'yes'
			),
			'username' => array(
				'title'       => __( 'SeedPay Username', 'woocommerce-gateway-seedpay' ),
				'type'        => 'text',
				'description' => __( 'Your SeedPay Username.', 'woocommerce-gateway-seedpay' ),
				
				'desc_tip'    => true,
			),
			'password' => array(
				'title'       => __( 'SeedPay Password', 'woocommerce-gateway-seedpay' ),
				'type'        => 'password',
				'description' => __( 'Your SeedPay Username.', 'woocommerce-gateway-seedpay' ),
				
				'desc_tip'    => true,
			),
			'token' => array(
				'title'       => __( 'SeedPay Token', 'woocommerce-gateway-seedpay' ),
				'type'        => 'password',
				'description' => __( 'Your SeedPay Token, leave this field empty to generate a new token.', 'woocommerce-gateway-seedpay' ),
				
				'desc_tip'    => true,
			),
		);
    }

    /**
     * Output for the order received page.
     */
	public function thankyou_page() {
		if ( $this->instructions )
        	echo wpautop( wptexturize( $this->instructions ) );
	}

    /**
     * Add content to the WC emails.
     *
     * @access public
     * @param WC_Order $order
     * @param bool $sent_to_admin
     * @param bool $plain_text
     */
	public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
        if ( $this->instructions && ! $sent_to_admin && 'cheque' === $order->payment_method && $order->has_status( 'on-hold' ) ) {
			echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
		}
	}
	public function payment_scripts() {
 

	if ( ! is_cart() && ! is_checkout() && ! isset( $_GET['pay_for_order'] ) ) {
		return;
	} 
	
	if ( 'no' === $this->enabled ) {
		return;
	}
 
	
	wp_register_script( 'woocommerce_seedpay', plugins_url( 'assets/js/scripts.js','woocommerce-gateway-seedpay/seedpay.php' ), array( 'jquery' ) );
 	wp_localize_script( 'woocommerce_seedpay', 'seedpay_params', array('ajax_url' => admin_url( 'admin-ajax.php' )) ); 
	wp_enqueue_script( 'woocommerce_seedpay' );	
	wp_enqueue_style( 'woocommerce_seedpay_styles', plugins_url( 'assets/css/style.css','woocommerce-gateway-seedpay/seedpay.php' ) );
 
}
    /**
     * Process the payment and return the result
     *
     * @param int $order_id
     * @return array
     */
	public function process_payment( $order_id ) {

		$order = wc_get_order( $order_id );
		
	
		
		$phone = wc_format_phone_number($_REQUEST['seedpay_payment_phone'] );
		
		if($phone == ''){
		$error_message = __('Please add a valid SeedPay phone number.', 'woocommerce-gateway-seedpay');	
		wc_add_notice( __('Payment error: ', 'woocommerce-gateway-seedpay') . $error_message, 'error' );	
		}
		
		
		
		
		if($order->payment_token != 'accepted'){
		$error_message = __('You must first accept payment before continuing.', 'woocommerce-gateway-seedpay');
		wc_add_notice( __('Payment error: ', 'woocommerce-gateway-seedpay') . $error_message, 'error' );	
		}
		
		
		if($error_message == ''){		
		
			
		
		
		
		
		$order->update_status( 'wc-processing' );
		
			
		
		$order->reduce_order_stock();

		// Remove cart
		WC()->cart->empty_cart();
		



		// Return thankyou redirect
		return array(
			'result' 	=> 'success',
			'redirect'	=> $this->get_return_url( $order )
		);
			
			
			
			
		}

	}
}

function add_WC_Gateway_SeedPay( $methods ) {
	$methods[] = 'WC_Gateway_SeedPay'; 
	return $methods;
}

add_filter( 'woocommerce_payment_gateways', 'add_WC_Gateway_SeedPay' );