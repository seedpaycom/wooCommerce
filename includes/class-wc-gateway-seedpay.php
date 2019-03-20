<?php
if (!defined('ABSPATH')) {
    exit;
}
require_once __DIR__ . '/../configs.php';
require_once __DIR__ . '/../api.php';
class WC_Gateway_Seedpay extends WC_Payment_Gateway
{
    public function __construct()
    {
        if (get_transient('uniqueTransactionId') == null) {
            generateNewUniqueTransactionId();
        }
        $this->id = 'seedpay';
        $this->icon = apply_filters('woocommerce_cheque_icon', '');
        $this->has_fields = true;
        $this->method_title = __('Seedpay', 'woocommerce-gateway-seedpay');
        $this->init_form_fields();
        $this->init_settings();
        $this->testmode = $this->get_option('environment');
        $this->title = $this->get_option('title');
        $this->instructions = $this->get_option('instructions');
        $this->username = $this->get_option('username');
        $this->token = $this->get_option('token');
        $error = '';
        if (get_option('_seedpay_login_error') == 1) {
            $error = '<p style="color:red;font-weight:bold">API Disconnected, please enter your username and API token.</p>';
        } else {
            $error = '<p style="color:green;font-weight:bold">API Connected</p>';
        }
        $this->method_description = __('Gateway for Seedpay', 'woocommerce-gateway-seedpay') . $error;
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(
            $this,
            'process_admin_options'
        ));
        add_action('woocommerce_thankyou_cheque', array(
            $this,
            'thankyou_page'
        ));
        add_action('woocommerce_email_before_order_table', array(
            $this,
            'email_instructions'
        ), 10, 3);
        add_action('wp_enqueue_scripts', array(
            $this,
            'payment_scripts'
        ));
    }
    public function process_admin_options()
    {
        $this->login();
        parent::process_admin_options();
    }
    public function login()
    {
        $fields = array(
            'username' => $_REQUEST['woocommerce_seedpay_username'],
        );
        $response = seedpay_request('/user', $fields, 'GET', $_REQUEST['woocommerce_seedpay_token']);
        if ($response->errors) {
            update_option('_seedpay_login_error', 1);
        } else {
            update_option('_seedpay_login_error', 0);
        }
    }
    public function payment_fields()
    {
        $transaction_id = get_transient('uniqueTransactionId');
        if ($this->instructions) {
            if ($this->testmode == 'yes') {
                echo '<p style="color:red;font-weight:bold">' . __('TEST MODE!!!!!111!!1one!!', 'woocommerce-gateway-seedpay') . '</p>';
            }
            echo wpautop(wp_kses_post($this->instructions));
        }
        echo '
    <p class="seedpayErrorMessage"></p>
    <fieldset id="wc-' . esc_attr($this->id) . '-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">';
        do_action('woocommerce_credit_card_form_start', $this->id);
        $phone = get_transient('seedpay_order_phone_' . $transaction_id . '');
        echo '
        <div class="form-row form-row-wide seedpayPhoneNumberPrompt" >
            <label>' . __('Phone Number', 'woocommerce-gateway-seedpay') . ' 
                <span class="required">*</span>
            </label>
            <input id="seedpayPhoneNumber" name="seedpayPhoneNumber" type="tel" autocomplete="tel" value="' . $phone . '">
        </div>
        <div class="seedpayRequestingPaymentIndicator" style="display:none">
            <p class="seedpay-message-success"> <img src="' . WC_SEEDPAY_PLUGIN_ASSETS . 'images/loading.gif" style="border:0px;float:none;"> Please accept the payment on your phone</p>
            <a href="#" class="seedpayCancelButton seed-pay-button">' . __('Cancel Request', 'woocommerce-gateway-seedpay') . '</a>
        </div>        
        <div class="seedpaySuccessMessage" style="display:none">
            <input type="hidden" name="uniqueTransactionIdHiddenForm" class="uniqueTransactionIdHiddenForm" value="' . $transaction_id . '">
            <p class="seedpaySuccessMessage">Payment received.  You will now be directed to the confirmation page.</p> 
        </div>                
        <div class="clear"></div>
        ';
        do_action('woocommerce_credit_card_form_end', $this->id);
        echo '<div class="clear"></div>
    </fieldset>';
    }
    public function add_testmode_admin_settings_notice()
    {
        wc_add_notice(__('Seedpay is currently in Test Mode: ', 'woocommerce-gateway-seedpay'), 'error');
    }
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
            'instructions' => array(
                'title' => __('Instructions', 'woocommerce-gateway-seedpay'),
                'type' => 'textarea',
                'description' => __('Instructions which will be added on the checkout page.', 'woocommerce-gateway-seedpay'),
                'default' => 'Enter your 10 digit phone number to request a payment on your phone. If you do not have a Seedpay account, we will send an invite link to your phone.',
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
            'token' => array(
                'title' => __('Seedpay Token', 'woocommerce-gateway-seedpay'),
                'type' => 'password',
                'description' => __('Your Seedpay Token, leave this field empty to generate a new token.', 'woocommerce-gateway-seedpay'),
                'desc_tip' => true
            )
        );
    }
    public function thankyou_page()
    {
        if ($this->instructions) {
            $order = wc_get_order($order_id);
            echo json_encode($order);
            echo wpautop(wptexturize($order));
            echo wpautop(wptexturize($this->instructions));
        }
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
        if ($this->enabled === 'no') {
            return;
        }
        wp_register_script('woocommerce_seedpay', WC_SEEDPAY_PLUGIN_ASSETS . 'js/app.min.js', array(
            'jquery'
        ));
        wp_localize_script('woocommerce_seedpay', 'ajaxUrl', admin_url('admin-ajax.php'));
        wp_enqueue_script('woocommerce_seedpay');
        wp_enqueue_style('woocommerce_seedpay_styles', WC_SEEDPAY_PLUGIN_ASSETS . 'css/app.min.css');
    }

    public function checkTransactionStatus()
    {
        $transaction_id = get_transient('uniqueTransactionId');
        $phone = wc_format_phone_number($_REQUEST['phoneNumber']);
        $message = array();
        $message['error'] = '';
        $message['post'] = $_REQUEST;
        if ($phone == '') {
            $message['error'] = __('Please enter a valid 10 digit phone number', 'woocommerce-gateway-seedpay');
            echo json_encode($message);
            return;
        }
        $request = array('phoneNumber' => $phone);
        $message['request'] = $request;
        $getVars = htmlentities(urlencode(json_encode(array('uniqueTransactionId' => $transaction_id))));
        $response = submitRequest('transactions/' . $getVars . '', array(), 'GET');
        if (gettype($response) == 'array') {
            if ($response[0]->status == 'acceptedAndPaid') {
                set_transient('seedpay_order_status_' . $transaction_id . '', $response[0], 168 * HOUR_IN_SECONDS);
                set_transient('seedpay_order_statusname_' . $transaction_id . '', $response[0]->status, 168 * HOUR_IN_SECONDS);
                set_transient('seedpay_order_phone_' . $transaction_id . '', $phone, 168 * HOUR_IN_SECONDS);
            }
            if ($response[0]->status == 'errored') {
                $message['error'] = __('There was an error with this transaction.', 'woocommerce-gateway-seedpay');
                generateNewUniqueTransactionId();
            }
            if ($response[0]->status == 'rejected') {
                $message['error'] = __('Payment was rejected.', 'woocommerce-gateway-seedpay');
                generateNewUniqueTransactionId();
            }
        }
        $message['response'] = $response;
        echo json_encode($message);
    }

    /**
     * Process the payment and return the result
     *
     * @param int $order_id
     * @return array
     */
    public function process_payment($order_id)
    {
        $uniqueTransactionId = get_transient('uniqueTransactionId');
        $phone = wc_format_phone_number($_REQUEST['seedpayPhoneNumber']);
        if (!$uniqueTransactionId) {
            $uniqueTransactionId = generateNewUniqueTransactionId();
        }
        $submitRequestPaymentResponse = submitRequestPayment(
            $phone,
            WC()->cart->total,
            get_transient('uniqueTransactionId')
        );
        $responseOrGenericError = getApiResponseObjectOrGenericErrorsFromRequestPaymentResponse($submitRequestPaymentResponse);
        $status = $responseOrGenericError->transaction->status;
        if ($responseOrGenericError->errors && !$status) {
            wc_add_notice($responseOrGenericError['errors'][0], 'error');
            return;
        }
        if ($status != 'acceptedAndPaid') {
            $error_message = __('Please follow the instructions on your phone to continue.', 'woocommerce-gateway-seedpay');
            wc_add_notice($error_message, 'notice');
            return;
        }
        $order = wc_get_order($order_id);
        $order->payment_complete();
        $order->update_status('wc-processing');
        $order->add_order_note(__('Seedpay Payment Completed: #' . $uniqueTransactionId . '', 'woocommerce-gateway-seedpay'));
        $order->add_order_note(__('Seedpay Payment Phone: ' . $phone . '', 'woocommerce-gateway-seedpay'));
        $order->update_meta_data('seedpayOrderResponse', $responseOrGenericError);
        $order->update_meta_data('seedpayOrderPhoneNumber', $phone);
        $order->reduce_order_stock();
        set_transient('uniqueTransactionId', null);
        WC()->cart->empty_cart();
        return array(
            'result' => 'success',
            'redirect' => $this->get_return_url($order)
        );
    }
}
function add_WC_Gateway_Seedpay($methods)
{
    $methods[] = 'WC_Gateway_Seedpay';
    return $methods;
}
add_filter('woocommerce_payment_gateways', 'add_WC_Gateway_Seedpay');
