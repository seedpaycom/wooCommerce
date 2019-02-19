<?php
if (!defined('ABSPATH')) {
    exit;
}
require_once __DIR__ . '/configs.php';
function submitApiRequest($resource, $body, $method)
{
    $request = curl_init();
    $fields = json_encode($body);
    $gateway_settings = get_option('woocommerce_seedpay_settings');
    $url = apiUrl();
    $headers = array();
    $headers[] = "Content-Type: application/json";
    $token = $gateway_settings['token'];
    $headers[] = "x-access-token: " . $token . "";
    $data = array(
        CURLOPT_URL => "" . $url . "/" . $resource . "",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_POSTFIELDS => $fields,
        CURLOPT_HTTPHEADER => $headers
    );
    curl_setopt_array($request, $data);
    $response = curl_exec($request);
    $err = curl_error($request);
    curl_close($request);
    if ($err) {
        return $err;
    } else {
        $statusCode = curl_getinfo($request, CURLINFO_HTTP_CODE);
        return json_decode($response);
    }
}

function requestPayment($phone, $amount)
{
    $message = array();
    if ($phone == '') {
        $message['error'] = __('Please enter a valid 10 digit phone number', 'woocommerce-gateway-seedpay');
        return $message;
    }
    if ($amount < 0.5) {
        $message['error'] = __('Amount must be larger than $0.50', 'woocommerce-gateway-seedpay');
        return $message;
    }
    $body = array(
        'fromPhoneNumber' => $phone,
        'amount' => $amount,
        'uniqueTransactionId' => $_COOKIE['seedpay_cart_id']
    );
    $response = submitApiRequest('requestPayment', $body, 'POST');
    $message['response'] = $response;
    return $message;
}

// function ajax_seedpay_check_request()
// {
//     $gateway_settings = get_option('woocommerce_seedpay_settings');
//     if ($gateway_settings['environment'] == 'yes') {
//         $site_url = 'http://localhost:8080';
//     } else {
//         $site_url = 'https://api.seedpay.com';
//     }
//     $transaction_id = get_transient('seedpay_cart_id');
//     $phone = wc_format_phone_number($_REQUEST['phone']);
//     $cart = WC()->cart;
//     $message = array();
//     $message['error'] = '';
//     $message['post'] = $_REQUEST;
//     if ($phone != '') {
//         $request = array('phoneNumber' => $phone);
//         $message['request'] = $request;
//         $getVars = htmlentities(urlencode(json_encode(array('uniqueTransactionId' => $transaction_id))));
//         $url = 'transactions/' . $getVars . '';
//         $message['url'] = $site_url . $url;
//         $response = seedpay_request($url, array(), 'GET', $gateway_settings['token']);
//         if (sizeof($response) > 0) {
//             if ($response[0]->status == 'acceptedAndPaid') {
//                 set_transient('seedpay_order_status_' . $transaction_id . '', $response[0], 168 * HOUR_IN_SECONDS);
//                 set_transient('seedpay_order_statusname_' . $transaction_id . '', $response[0]->status, 168 * HOUR_IN_SECONDS);
//                 set_transient('seedpay_order_phone_' . $transaction_id . '', $phone, 168 * HOUR_IN_SECONDS);
//             }
//             if ($response[0]->status == 'errored') {
//                 $message['error'] = __('There was an error with this transaction.', 'woocommerce-gateway-seedpay');
//                 seedpay_generate_new_cart_id();
//             }
//             if ($response[0]->status == 'rejected') {
//                 $message['error'] = __('Payment was rejected.', 'woocommerce-gateway-seedpay');
//                 seedpay_generate_new_cart_id();
//             }
//         }
//         $message['response'] = $response;
//     } else {
//         $message['error'] = __('Please enter a valid 10 digit phone number', 'woocommerce-gateway-seedpay');
//     }
//     echo json_encode($message);
//     die();
// }

// add_action('wp_ajax_ajax_seedpay_check_request', 'ajax_seedpay_check_request');
// add_action('wp_ajax_nopriv_ajax_seedpay_check_request', 'ajax_seedpay_check_request');

// function ajax_checkUserStatus()
// {
//     $message = array();
//     $gateway_settings = get_option('woocommerce_seedpay_settings');
//     $phone = wc_format_phone_number($_REQUEST['phone']);
//     $url = 'user/isRegistered/' . $phone . '';
//     $message['url'] = $url;
//     $response = seedpay_request($url, array(), 'GET', $gateway_settings['token']);
//     $message['response'] = $response;
//     echo json_encode($message);
//     die();
// }

// add_action('wp_ajax_ajax_checkUserStatus', 'ajax_checkUserStatus');
// add_action('wp_ajax_nopriv_ajax_checkUserStatus', 'ajax_checkUserStatus');

// function seedpay_generate_new_cart_id()
// {
//     $transaction_id = wp_rand();
//     setcookie('seedpay_cart_id', '', time() - (15 * 60), COOKIEPATH, COOKIE_DOMAIN);
//     setcookie('seedpay_cart_id', $transactionId, time() + (60 * 20), COOKIEPATH, COOKIE_DOMAIN);
//     set_transient('seedpayTransactionId', $transactionId, 168 * HOUR_IN_SECONDS);
//     return $transactionId;
// }

// function woocommerce_seedpay_init()
// {
//     require_once(plugin_basename('includes/class-wc-gateway-seedpay.php'));
//     add_filter('woocommerce_payment_gateways', 'woocommerce_seedpay_add_gateway');
//     load_plugin_textdomain('woocommerce-gateway-seedpay', false, basename(dirname(__FILE__)) . '/languages/');
// }
// add_action('plugins_loaded', 'woocommerce_seedpay_init', 0);

// function woocommerce_seedpay_add_gateway($methods)
// {
//     $methods[] = 'WC_Gateway_Seedpay';
//     return $methods;
// }

// function woocommerce_seedpay_plugin_links($links)
// {
//     $settings_url = add_query_arg(array(
//         'page' => 'wc-settings',
//         'tab' => 'checkout',
//         'section' => 'wc_gateway_seedpay'
//     ), admin_url('admin.php'));
//     $plugin_links = array(
//         '<a href="' . esc_url($settings_url) . '">' . __('Settings', 'woocommerce-gateway-seedpay') . '</a>'
//     );
//     return array_merge($plugin_links, $links);
// }

// add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'woocommerce_seedpay_plugin_links');

// function seedpay_add_to_cart_validation($passed, $product_id, $quantity)
// {
//     $transient = get_transient('seedpay_order_statusname_' . $_COOKIE['seedpay_cart_id'] . '');
//     if ($transient == 'acceptedAndPaid') {
//         wc_add_notice(__('Payment already accepted you can no longer add any items to the cart', 'woocommerce'), 'error');
//         $passed = false;
//     }
//     return $passed;
// };

// add_filter('woocommerce_add_to_cart_validation', 'seedpay_add_to_cart_validation', 10, 3);
