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
		$this->method_title       = __( 'SeedPay', 'woocommerce' );
		$this->method_description = __( 'Gateway for SeedPay', 'woocommerce' );

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
    }
	public function payment_fields() {
 
	
	if ( $this->description ) {
		
		if ( $this->testmode == 'yes' ) {
			$this->description .= ' TEST MODE ENABLED. In test mode, you will be using staging api credentials.';
			$this->description  = trim( $this->description );
		}
		
		echo wpautop( wp_kses_post( $this->description ) );
	}
 
	
	echo '<fieldset id="wc-' . esc_attr( $this->id ) . '-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">';
 
	
	do_action( 'woocommerce_credit_card_form_start', $this->id );
 
	
	echo '<div class="form-row form-row-wide"><label>PaySeed Phone Number <span class="required">*</span></label>
		<input id="seedpay_payment_phone" type="text" autocomplete="off">
		</div>
		
		<div class="clear"></div>';
 
	do_action( 'woocommerce_credit_card_form_end', $this->id );
 
	echo '<div class="clear"></div></fieldset>';
 
}
	
	public function request($method,$fields){
		
		$curl = curl_init();
		$fields = json_encode($fields);
		curl_setopt_array($curl, array(
		  CURLOPT_URL => "".$this->url."/transactions/".$method."",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 0,
		  CURLOPT_FOLLOWLOCATION => false,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "GET",
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
		
	}

	
    /**
     * Initialise Gateway Settings Form Fields
     */
    public function init_form_fields() {

    	$this->form_fields = array(
			'enabled' => array(
				'title'   => __( 'Enable/Disable', 'woocommerce' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable SeedPay', 'woocommerce' ),
				'default' => 'yes'
			),
			
			'title' => array(
				'title'       => __( 'Title', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
				'default'     => __( 'SeedPay', 'woocommerce' ),
				'desc_tip'    => true,
			),
			'description' => array(
				'title'       => __( 'Description', 'woocommerce' ),
				'type'        => 'textarea',
				'description' => __( 'Payment method description that the customer will see on your checkout.', 'woocommerce' ),
				'default'     => __( 'Please enter your PaySeed phone number and approve the payment once received.', 'woocommerce' ),
				'desc_tip'    => true,
			),
			'instructions' => array(
				'title'       => __( 'Instructions', 'woocommerce' ),
				'type'        => 'textarea',
				'description' => __( 'Instructions that will be added to the thank you page and emails.', 'woocommerce' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'environment' => array(
				'title'   => __( 'Test Mode', 'woocommerce' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Test Servers', 'woocommerce' ),
				'default' => 'yes'
			),
			'username' => array(
				'title'       => __( 'SeedPay Username', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Your SeedPay Username.', 'woocommerce' ),
				
				'desc_tip'    => true,
			),
			'password' => array(
				'title'       => __( 'SeedPay Password', 'woocommerce' ),
				'type'        => 'password',
				'description' => __( 'Your SeedPay Username.', 'woocommerce' ),
				
				'desc_tip'    => true,
			),
			'token' => array(
				'title'       => __( 'SeedPay Token', 'woocommerce' ),
				'type'        => 'password',
				'description' => __( 'Your SeedPay Token, leave this field empty to generate a new token.', 'woocommerce' ),
				
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

    /**
     * Process the payment and return the result
     *
     * @param int $order_id
     * @return array
     */
	public function process_payment( $order_id ) {

		$order = wc_get_order( $order_id );
		if($order->payment_token == 'accepted'){
		// Mark as on-hold (we're awaiting the cheque)
		$order->update_status( 'wc-awaiting-shipment' );
		
		add_post_meta($order_id, '_isl_waiting_total',true);
		
			$mailer = WC()->mailer();
            $email  = $mailer->emails['WC_ISL_Email_New_Order'];
            $email->trigger($order);
		
			unset($mailer);
			unset($email);
			$mailer = WC()->mailer();
		  	$email_customer  = $mailer->emails['WC_ISL_Customer_Email_New_Order'];
            $email_customer->trigger($order);
				
		
		
		// Reduce stock levels
		#$order->reduce_order_stock();

		// Remove cart
		WC()->cart->empty_cart();
		



		// Return thankyou redirect
		return array(
			'result' 	=> 'success',
			'redirect'	=> $this->get_return_url( $order )
		);
		}else{
			
		//error	
			
		}

	}
}

function add_WC_Gateway_SeedPay( $methods ) {
	$methods[] = 'WC_Gateway_SeedPay'; 
	return $methods;
}

add_filter( 'woocommerce_payment_gateways', 'add_WC_Gateway_SeedPay' );