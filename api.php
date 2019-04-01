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
    return submitRequest(
        'user/isRegistered/' . $phoneNumber . '',
        null,
        'GET'
    );
}
$GLOBALS['genericRequestPaymentError'] = 'Error while requesting payment.';
function getApiResponseObjectOrGenericErrorsFromRequestPaymentResponse($response)
{
    if (!$response || gettype($response) != gettype(array()) || !$response['statusCode'] || ($response['statusCode'] != 200 && $response['statusCode'] != 400) || !is_object($response['response']) || $response['error']) {
        return json_decode('{
            "errors": [' . $GLOBALS['genericRequestPaymentError'] . ']
        }');
    }
    return $response['response'];
}
