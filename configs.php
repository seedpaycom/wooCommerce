<?php
function apiUrl(callable $getOptions = null)
{
    if (!$getOptions) {
        $getOptions = get_options;
    }

    if ($getOptions('woocommerce_seedpay_settings')['environment'] == 'yes') {
        return $_ENV['NODE_ENV'] == 'production' ? 'https://staging.api.seedpay.com' : 'http://localhost';
    }
    return 'https://api.seedpay.com';
}