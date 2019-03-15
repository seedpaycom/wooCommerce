<?php
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
        'response' => json_decode($response),
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
    return submitRequest(
        'requestPayment',
        $body,
        'POST'
    );
}

function submitGetTransactionStatus($uniqueTransactionId)
{
    if (!$uniqueTransactionId) {
        return array('error' => __('Amount must be larger than $0.50', 'woocommerce-gateway-seedpay'));
    }
    $url = 'transactions/' . htmlentities(urlencode(json_encode(array(
        'uniqueTransactionId' => $uniqueTransactionId
    )))) . '';
    return submitRequest(
        $url,
        null,
        'GET'
    );
}
function submitGetUserStatus($phoneNumber)
{
    if (!$phoneNumber) {
        return array('error' => __('Please enter a valid 10 digit phone number.', 'woocommerce-gateway-seedpay'));
    }
    return submitRequest(
        'user/isRegistered/' . $phoneNumber . '',
        null,
        'GET'
    );
}
$GLOBALS['genericRequestPaymentError'] = 'Error while requesting payment.';
function getStatusOrErrorFromRequestPaymentResponse($response)
{
    if (!$response || gettype($response) != gettype(array()) || !$response['statusCode'] || ($response['statusCode'] != 200 && $response['statusCode'] != 400) || !json_decode($response['response']) || $response['error']) {
        return array(
            'errors' => array($GLOBALS['genericRequestPaymentError'])
        );
    }
    return json_decode($response['response']);
}
