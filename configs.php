<?php
function apiUrl(callable $getOption = null)
{
    if (!$getOption) {
        if (!function_exists('woothemes_queue_update')) {
            require_once('woo-includes/woo-functions.php');
        }
        $getOption = function ($key) {
            return get_option($key);
        };
    }
    if ($getOption('woocommerce_seedpay_settings')['environment'] == 'yes') {
        return $_ENV['seedpayTestModeApiUrl'] ?? 'https://staging.api.seedpay.com';
    }
    return $_ENV['seedpayApiUrl'] ?? 'https://api.seedpay.com';
}
