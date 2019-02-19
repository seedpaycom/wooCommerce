<?php
if (!class_exists('WC_Dependencies')) {
    require_once 'class-wc-dependencies.php';
}
if (!function_exists('is_woocommerce_active')) {
    function is_woocommerce_active()
    {
        return WC_Dependencies::woocommerce_active_check();
    }
}
