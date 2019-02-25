<?php
if (!defined('ABSPATH')) {
    exit;
}
require_once __DIR__ . '/configs.php';
function submitRequest($resource, $body, $method)
{
    $request = curl_init();
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
        CURLOPT_POSTFIELDS => json_encode($body || ''),
        CURLOPT_HTTPHEADER => $headers
    );
    curl_setopt_array($request, $data);
    $response = curl_exec($request);
    $err = curl_error($request);

    if ($err) {
        curl_close($request);
        return $err;
    } else {
        $statusCode = curl_getinfo($request, CURLINFO_HTTP_CODE);
        curl_close($request);
        wp_send_json($response);
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
        'uniqueTransactionId' => get_transient('uniqueTransactionId')
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
//     $transaction_id = get_transient(uniqueTransactionId');
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
