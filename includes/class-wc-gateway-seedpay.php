<?php
if (!defined('ABSPATH')) {
    exit;
}
require_once __DIR__ . '/../configs.php';
require_once __DIR__ . '/../api.php';
require_once __DIR__ . '/../transactionId.php';
class WC_Gateway_Seedpay extends WC_Payment_Gateway
{
    public function __construct()
    {
        $this->id = 'seedpay';
        $this->icon = apply_filters('woocommerce_cheque_icon', '');
        $this->has_fields = true;
        $this->method_title = __('Pay with Seedpay', 'woocommerce-gateway-seedpay');
        $this->init_form_fields();
        $this->init_settings();
        $this->title = $this->get_option('title');
        $this->instructions = $this->get_option('instructions');
        $this->token = $this->get_option('token');
        $error = '';
        if (get_option('_seedpay_login_error') == 1) {
            $error = '<p style="color:red;font-weight:bold">API Disconnected, please enter your API token.</p>';
        } else {
            $error = '<p style="color:green;font-weight:bold">API Connected</p>';
        }
        $this->method_description = __('Gateway for Seedpay', 'woocommerce-gateway-seedpay') . $error;
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(
            $this,
            'process_admin_options'
        ));
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
        $response = submitRequest('user', null, 'GET');
        if ($response['statusCode'] != 200) {
            update_option('_seedpay_login_error', 1);
        } else {
            update_option('_seedpay_login_error', 0);
        }
    }
    public function payment_fields()
    {
        if ($this->instructions) {
            if ($this->get_option('environment') == 'yes') {
                echo '<p style="color:red;font-weight:bold">' . __('TEST MODE!!!!!111!!1one!!', 'woocommerce-gateway-seedpay') . '</p>';
            }
            echo wpautop(wp_kses_post($this->instructions));
        }
        echo '
    <p class="seedpayErrorMessage"></p>
    <fieldset id="wc-' . esc_attr($this->id) . '-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">';
        do_action('woocommerce_credit_card_form_start', $this->id);
        echo '
        <div class="form-row form-row-wide seedpayPhoneNumberPrompt" >
            <label>' . __('Phone Number', 'woocommerce-gateway-seedpay') . ' 
                <span class="required">*</span>
            </label>
            <input id="seedpayPhoneNumber" name="seedpayPhoneNumber" type="tel" autocomplete="tel" value="">
        </div>
        <div class="seedpayRequestingPaymentIndicator" style="display:none">
            <p class="seedpay-message-success"> <img src="' . WC_SEEDPAY_PLUGIN_ASSETS . 'images/loading.gif" style="border:0px;float:none;"> Please follow the instructions on your phone to continue</p>
        </div>        
        <div class="seedpaySuccessMessage" style="display:none">
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
                'default' => 'Enter your 10 digit phone number to request a payment on your phone. If you do not have a Seedpay account, an invite link will be sent to your phone.',
                'desc_tip' => true
            ),
            'environment' => array(
                'title' => __('Test Mode', 'woocommerce-gateway-seedpay'),
                'type' => 'checkbox',
                'label' => __('Enable Test Servers', 'woocommerce-gateway-seedpay'),
                'default' => 'yes'
            ),
            'token' => array(
                'title' => __('Seedpay Token', 'woocommerce-gateway-seedpay'),
                'type' => 'password',
                'description' => __('Your Seedpay Token, leave this field empty to generate a new token.', 'woocommerce-gateway-seedpay'),
                'desc_tip' => true
            )
        );
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

    /**
     * Process the payment and return the result
     *
     * @param int $order_id
     * @return array
     */
    public function process_payment($order_id)
    {
        $phone = wc_format_phone_number($_REQUEST['seedpayPhoneNumber']);
        $submitRequestPaymentResponse = submitRequestPayment(
            $phone,
            WC()->cart->total,
            getTransactionId()
        );
        $responseOrGenericError = getApiResponseObjectOrGenericErrorsFromRequestPaymentResponse($submitRequestPaymentResponse);

        $status = null;
        $amount = null;
        if ($responseOrGenericError && property_exists($responseOrGenericError, 'transaction') && property_exists($responseOrGenericError->transaction, 'status')) {
            $status = $responseOrGenericError->transaction->status;
        }
        if ($responseOrGenericError && property_exists($responseOrGenericError, 'transaction') && property_exists($responseOrGenericError->transaction, 'amount')) {
            $amount = $responseOrGenericError->transaction->amount;
        }
        if (! ($status || $amount) && $responseOrGenericError && property_exists($responseOrGenericError, 'errors') && $responseOrGenericError->errors[0]) {
            wc_add_notice($responseOrGenericError->errors[0], 'error');
            return;
        }
        if ($status != 'acceptedAndPaid') {
            $error_message = __('Please follow the instructions on your phone to continue.', 'woocommerce-gateway-seedpay');
            wc_add_notice($error_message, 'notice');
            return;
        }
        if ($amount != WC()->cart->total) {
            $error_message = __('The shopping cart has changed after accepting the payment.  Please revert your changes and resubmit the order or contact helpdesk@seedpay.com', 'woocommerce-gateway-seedpay');
            wc_add_notice($error_message, 'error');
            return;
        }
        $order = wc_get_order($order_id);
        set_transient('seedpayOrderStatus' . getTransactionId() . '', $status);
        $order->payment_complete();
        $order->update_status('wc-processing');
        $order->add_order_note(__('Seedpay Payment Completed: #' . getTransactionId() . '', 'woocommerce-gateway-seedpay'));
        $order->add_order_note(__('Seedpay Payment Phone: ' . $phone . '', 'woocommerce-gateway-seedpay'));
        $order->update_meta_data('seedpayOrderResponse', $responseOrGenericError);
        $order->update_meta_data('seedpayOrderPhoneNumber', $phone);
        $order->reduce_order_stock();
        WC()->cart->empty_cart();
        generateNewId();
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
