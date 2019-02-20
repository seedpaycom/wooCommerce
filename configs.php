<?php
function apiUrl(callable $getOptions = null)
{
    if (!$getOptions) {
        $getOptions = get_options;
    }

    if ($getOptions('woocommerce_seedpay_settings')['environment'] == 'yes') {
        return $_ENV['seedpayTestModeApiUrl'] ?? 'https://staging.api.seedpay.com';
    }
    return $_ENV['seedpayApiUrl'] ?? 'https://api.seedpay.com';
}