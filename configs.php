<?php
if (!defined('ABSPATH')) {
    exit;
}
class configs
{
    function apiUrl()
    {
        $gateway_settings = get_option('woocommerce_seedpay_settings');
        if ($gateway_settings['environment'] == 'yes')
            return 'http://localhost:8080';
        return $site_url = 'https://api.seedpay.com';
    }
}