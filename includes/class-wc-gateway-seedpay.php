<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class WC_Gateway_SeedPay extends WC_Payment_Gateway
{
    
    /**
     * Constructor for the gateway.
     */
    public function __construct()
    {
        if (!isset($_COOKIE['seedpay_cart_id'])) {
            
            $transaction_id = seedpay_generate_new_cart_id();
            
        } else {
            $transaction_id = $_COOKIE['seedpay_cart_id'];
        }
        
        $this->id                 = 'seedpay';
        $this->icon               = apply_filters('woocommerce_cheque_icon', '');
        $this->has_fields         = true;
        $this->method_title       = __('Seedpay', 'woocommerce-gateway-seedpay');
        $this->method_description = __('Gateway for Seedpay', 'woocommerce-gateway-seedpay');
        
        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();
        $this->testmode     = $this->get_option('environment');
        // Define user set variables
        $this->title        = $this->get_option('title');
        $this->description  = $this->get_option('description');
        $this->instructions = $this->get_option('instructions', $this->description);
        $this->url          = 'https://api.seedpay.com';
        
        
        $this->username = $this->get_option('username');
        $this->password = $this->get_option('password');
        $this->token    = $this->get_option('token');
        if (($this->username != '' && $this->password != '') && $this->token == '') {
            
            $this->login();
            
        }
        if ('yes' === $this->testmode) {
            $this->url = 'https://staging.api.seedpay.com';
            
        }
        
        
        // Actions
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(
            $this,
            'process_admin_options'
        ));
        add_action('woocommerce_thankyou_cheque', array(
            $this,
            'thankyou_page'
        ));
        
        // Customer Emails
        add_action('woocommerce_email_before_order_table', array(
            $this,
            'email_instructions'
        ), 10, 3);
        add_action('wp_enqueue_scripts', array(
            $this,
            'payment_scripts'
        ));
        
        
        
    }
    public function login()
    {
        
        $fields   = array(
            'username' => $this->username,
            'password' => $this->password
        );
        $response = seedpay_request('login', $fields, 'POST');
        
        if ($response->token != '') {
            $gateway_settings          = get_option('woocommerce_seedpay_settings');
            $gateway_settings['token'] = $response->token;
            update_option('woocommerce_seedpay_settings', $gateway_settings);
        }
        
        
    }
    public function payment_fields()
    {
        
        $transaction_id = $_COOKIE['seedpay_cart_id'];
        
        if ($this->description) {
            
            if ($this->testmode == 'yes') {
                $this->description .= '<p style="color:red;font-weight:bold">' . __('TEST MODE ENABLED. In test mode, you will be using staging api credentials.', 'woocommerce-gateway-seedpay') . '</p>';
                $this->description = trim($this->description);
            }
            
            echo wpautop(wp_kses_post($this->description));
        }
        
        
        echo '
    <div class="seedpay-messages"></div>
    <fieldset id="wc-' . esc_attr($this->id) . '-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">';
        
        
        do_action('woocommerce_credit_card_form_start', $this->id);
        $check_payment = get_transient('seedpay_order_status_' . $transaction_id . '');
        $phone         = get_transient('seedpay_order_phone_' . $transaction_id . '');
        
        
        
        if ($check_payment != "") {
            
            echo '<input type="hidden" class="seedpay_recheck_payment" value="1" data-id="' . $transaction_id . '" data-pn="' . $phone . '">';
            
        }
        
        
        echo '<div class="form-row form-row-wide seedpay-number-form" >
    
    
    <label>' . __('Phone Number', 'woocommerce-gateway-seedpay') . ' <span class="required">*</span></label>
        <input id="seedpay_payment_phone" name="seedpay_payment_phone" type="text" autocomplete="off" value="' . $phone . '"> <a href="#" class="seedpay-request-payment-submit seed-pay-button">' . __('Request Payment', 'woocommerce-gateway-seedpay') . '</a>

    </div>
        
        <div class="seedpay-number-form-pending" style="display:none">
            <p class="seedpay-message-success"> <img src="' .WC_SEEDPAY_PLUGIN_ASSETS. 'images/loading.gif" style="border:0px;float:none;"> Please accept the payment on your phone</p>
             <a href="#" class="seedpay-cancel-payment-submit seed-pay-button">' . __('Cancel Request', 'woocommerce-gateway-seedpay') . '</a>
            
        </div>
        
        <div class="seedpay-number-form-success" style="display:none">
            <input type="hidden" name="seedpay_payment_cancel" class="seedpay_payment_cancel" value="0">
            <input type="hidden" name="seedpay_payment_success" class="seedpay_payment_success" value="">
            <input type="hidden" name="seedpay_payment_cart_hash" class="seedpay_payment_cart_hash" value="' . $transaction_id . '">
            <p class="seedpay-message-success">Payment has been confirmed, please continue with your order</p>
        </div>
        
        
        <div class="clear"></div>';
        
        do_action('woocommerce_credit_card_form_end', $this->id);
        
        echo '<div class="clear"></div></fieldset>';
        
    }
    
    
    
    
    
    
    
    public function add_testmode_admin_settings_notice()
    {
        wc_add_notice(__('Seedpay is currently in Test Mode: ', 'woocommerce-gateway-seedpay'), 'error');
    }
    
    
    /**
     * Initialise Gateway Settings Form Fields
     */
    public function init_form_fields()
    {
        
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'woocommerce-gateway-seedpay'),
                'type' => 'checkbox',
                'label' => __('Enable Seedpay', 'woocommerce-gateway-seedpay'),
                'default' => 'yes'
            ),
            
            'title' => array(
                'title' => __('Title', 'woocommerce-gateway-seedpay'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'woocommerce-gateway-seedpay'),
                'default' => __('Seedpay', 'woocommerce-gateway-seedpay'),
                'desc_tip' => true
            ),
            'description' => array(
                'title' => __('Description', 'woocommerce-gateway-seedpay'),
                'type' => 'textarea',
                'description' => __('Payment method description that the customer will see on your checkout.', 'woocommerce-gateway-seedpay'),
                'default' => __('Please enter your phone number and approve the payment once received.', 'woocommerce-gateway-seedpay'),
                'desc_tip' => true
            ),
            'instructions' => array(
                'title' => __('Instructions', 'woocommerce-gateway-seedpay'),
                'type' => 'textarea',
                'description' => __('Instructions that will be added to the thank you page and emails.', 'woocommerce-gateway-seedpay'),
                'default' => '',
                'desc_tip' => true
            ),
            'environment' => array(
                'title' => __('Test Mode', 'woocommerce-gateway-seedpay'),
                'type' => 'checkbox',
                'label' => __('Enable Test Servers', 'woocommerce-gateway-seedpay'),
                'default' => 'yes'
            ),
            'username' => array(
                'title' => __('Seedpay Username', 'woocommerce-gateway-seedpay'),
                'type' => 'text',
                'description' => __('Your Seedpay Username.', 'woocommerce-gateway-seedpay'),
                
                'desc_tip' => true
            ),
            'password' => array(
                'title' => __('Seedpay Password', 'woocommerce-gateway-seedpay'),
                'type' => 'password',
                'description' => __('Your Seedpay Username.', 'woocommerce-gateway-seedpay'),
                
                'desc_tip' => true
            ),
            'password' => array(
                'title' => __('Seedpay Token', 'woocommerce-gateway-seedpay'),
                'type' => 'text',
                'description' => __('Your Seedpay Token, leave this field empty to generate a new token.', 'woocommerce-gateway-seedpay'),
                
                'desc_tip' => true
            )
        );
    }
    
    /**
     * Output for the order received page.
     */
    public function thankyou_page()
    {
        if ($this->instructions)
            echo wpautop(wptexturize($this->instructions));
    }
    
    /**
     * Add content to the WC emails.
     *
     * @access public
     * @param WC_Order $order
     * @param bool $sent_to_admin
     * @param bool $plain_text
     */
    public function email_instructions($order, $sent_to_admin, $plain_text = false)
    {
        if ($this->instructions && !$sent_to_admin && 'cheque' === $order->payment_method && $order->has_status('on-hold')) {
            echo wpautop(wptexturize($this->instructions)) . PHP_EOL;
        }
    }
    public function payment_scripts()
    {
        
        
        if (!is_cart() && !is_checkout() && !isset($_GET['pay_for_order'])) {
            return;
        }
        
        if ('no' === $this->enabled) {
            return;
        }
        
        
        wp_register_script('woocommerce_seedpay',WC_SEEDPAY_PLUGIN_ASSETS.'js/scripts.min.js', array(
            'jquery'
        ));
        wp_localize_script('woocommerce_seedpay', 'seedpay_params', array(
            'ajax_url' => admin_url('admin-ajax.php')
        ));
        wp_enqueue_script('woocommerce_seedpay');
        wp_enqueue_style('woocommerce_seedpay_styles',WC_SEEDPAY_PLUGIN_ASSETS.'css/style.min.css');
        
    }
    /**
     * Process the payment and return the result
     *
     * @param int $order_id
     * @return array
     */
    public function process_payment($order_id)
    {
        
        $order = wc_get_order($order_id);
        
        
        
        $phone = wc_format_phone_number($_REQUEST['seedpay_payment_phone']);
        
        if ($phone == '') {
            $error_message = __('Please add a valid Seedpay phone number.', 'woocommerce-gateway-seedpay');
            wc_add_notice(__('Payment error: ', 'woocommerce-gateway-seedpay') . $error_message, 'error');
        }
        if ($_REQUEST['seedpay_payment_success'] != 'acceptedAndPaid') {
            $error_message = __('You must first accept payment before continuing.', 'woocommerce-gateway-seedpay');
            wc_add_notice(__('Payment error: ', 'woocommerce-gateway-seedpay') . $error_message, 'error');
        } else {
            #one last check to see if payment is good
            $getVars  = htmlentities(urlencode(json_encode(array(
                'uniqueTransactionId' => $_REQUEST['seedpay_payment_cart_hash']
            ))));
            $posturl  = '/transactions/' . $getVars . '';
            $response = seedpay_request($posturl, array(), 'GET', $this->token);
            
            if ($response[0]->status == 'acceptedAndPaid') {
                
                
                
                
                if ($error_message == '') {
                    
                    
                    
                    
                    
                    $order->payment_complete();
                    
                    $order->update_status('wc-processing');
                    
                    
                    $order->add_order_note(__('Seedpay Payment Completed: #' . $response[0]->_id . '', 'woocommerce-gateway-seedpay'));
                    $order->add_order_note(__('Seedpay Payment Phone: ' . $_REQUEST['seedpay_payment_phone'] . '', 'woocommerce-gateway-seedpay'));
                    $order->update_meta_data('_seedpay_payment', $response[0]);
                    $order->update_meta_data('_seedpay_payment_phone', $_REQUEST['seedpay_payment_phone']);
                    $order->reduce_order_stock();
                    setcookie('seedpay_cart_id', '', time() - (15 * 60), COOKIEPATH, COOKIE_DOMAIN);
                    // Remove cart
                    WC()->cart->empty_cart();
                    
                    
                    
                    
                    // Return thankyou redirect
                    return array(
                        'result' => 'success',
                        'redirect' => $this->get_return_url($order)
                    );
                    
                } else {
                    $error_message = __('You must first accept payment before continuing.', 'woocommerce-gateway-seedpay');
                    wc_add_notice(__('Payment error: ', 'woocommerce-gateway-seedpay') . $error_message, 'error');
                    
                }
            }
            
        }
        
    }
}

function add_WC_Gateway_SeedPay($methods)
{
    $methods[] = 'WC_Gateway_SeedPay';
    return $methods;
}

add_filter('woocommerce_payment_gateways', 'add_WC_Gateway_SeedPay');