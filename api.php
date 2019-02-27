<?php
if (!defined('ABSPATH')) {
    exit;
}
require_once __DIR__ . '/configs.php';
function submitRequest($resource, $body, $method)
{
    $request = curl_init();
    $headers = array();
    $headers[] = "Content-Type: application/json";
    $headers[] = "x-access-token: " . get_option('woocommerce_seedpay_settings')['token'] . "";
    $data = array(
        CURLOPT_URL => "" . apiUrl() . "/" . $resource . "",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers
    );
    if ($body) {
        $data[CURLOPT_POSTFIELDS] = json_encode($body);
    }
    curl_setopt_array($request, $data);
    $response = curl_exec($request);
    $err = curl_error($request);
    $statusCode = curl_getinfo($request, CURLINFO_HTTP_CODE);
    curl_close($request);
    return array(
        'response' => $response,
        'error' => $err,
        'statusCode' => $statusCode
    );
}

function submitRequestPayment($fromPhoneNumber, $amount, $uniqueTransactionId)
{
    if (!$fromPhoneNumber) {
        return array('error' => __('Please enter a valid 10 digit phone number.', 'woocommerce-gateway-seedpay'));
    }
    if (!$amount || $amount < 0.5) {
        return array('error' => __('Amount must be larger than $0.50', 'woocommerce-gateway-seedpay'));
    }
    $body = array(
        'fromPhoneNumber' => $fromPhoneNumber,
        'amount' => $amount,
        'uniqueTransactionId' => $uniqueTransactionId
    );
    return submitRequest('requestPayment', $body, 'POST');
}

function submitGetTransactionStatus($uniqueTransactionId)
{
    if (!$uniqueTransactionId) {
        return array('error' => __('Amount must be larger than $0.50', 'woocommerce-gateway-seedpay'));
    }

    $url = 'transactions/' . htmlentities(urlencode(json_encode(array(
        'uniqueTransactionId' => $uniqueTransactionId
    )))) . '';
    $response = submitRequest($url, null, 'GET');
    return $response;
}

