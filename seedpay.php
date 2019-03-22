<?php

/**
 * Plugin Name: WooCommerce Seedpay Gateway
 * Plugin URI: http://seedpay.com
 * Description: Receive payments using Seedpay
 * Author: Seedpay
 * Author URI: http://seedpay.com/
 * Version: 1.0.0
 * WC tested up to: 3.5.4
 * WC requires at least: 3.0
 * Text Domain: woocommerce-gateway-seedpay
 */
if (!defined('ABSPATH')) {
    exit;
}
if (!function_exists('woothemes_queue_update')) {
    require_once('woo-includes/woo-functions.php');
}

define('WC_SEEDPAY_PLUGIN_ASSETS', plugins_url('assets/', __FILE__));

require_once __DIR__ . '/configs.php';
require_once __DIR__ . '/api.php';
require_once __DIR__ . '/transactionId.php';

function requestPayment()
{
    wp_send_json(
        submitRequestPayment(
            wc_format_phone_number($_REQUEST['phoneNumber']),
            WC()->cart->total,
            getTransactionId()
        )
    );
    die();
}
add_action('wp_ajax_requestPayment', 'requestPayment');
add_action('wp_ajax_nopriv_requestPayment', 'requestPayment');

function checkTransactionStatus()
{
    wp_send_json(
        submitGetTransactionStatus(
            getTransactionId()
        )
    );
    die();
}
add_action('wp_ajax_checkTransactionStatus', 'checkTransactionStatus');
add_action('wp_ajax_nopriv_checkTransactionStatus', 'checkTransactionStatus');

function checkUserStatus()
{
    wp_send_json(
        submitGetUserStatus(
            wc_format_phone_number($_REQUEST['phoneNumber'])
        )
    );
    die();
}
add_action('wp_ajax_checkUserStatus', 'checkUserStatus');
add_action('wp_ajax_nopriv_checkUserStatus', 'checkUserStatus');

function generateNewTransactionId()
{
    wp_send_json('' . generateNewId());
    die();
}
add_action('wp_ajax_generateNewTransactionId', 'generateNewTransactionId');
add_action('wp_ajax_nopriv_generateNewTransactionId', 'generateNewTransactionId');

function woocommerce_seedpay_init()
{
    require_once(plugin_basename('includes/class-wc-gateway-seedpay.php'));
    add_filter('woocommerce_payment_gateways', 'woocommerce_seedpay_add_gateway');
    load_plugin_textdomain('woocommerce-gateway-seedpay', false, basename(dirname(__FILE__)) . '/languages/');
}
add_action('plugins_loaded', 'woocommerce_seedpay_init', 0);

function woocommerce_seedpay_add_gateway($methods)
{
    $methods[] = 'WC_Gateway_Seedpay';
    return $methods;
}

function woocommerce_seedpay_plugin_links($links)
{
    $settings_url = add_query_arg(array(
        'page' => 'wc-settings',
        'tab' => 'checkout',
        'section' => 'wc_gateway_seedpay'
    ), admin_url('admin.php'));
    $plugin_links = array(
        '<a href="' . esc_url($settings_url) . '">' . __('Settings', 'woocommerce-gateway-seedpay') . '</a>'
    );
    return array_merge($plugin_links, $links);
}

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'woocommerce_seedpay_plugin_links');

function seedpay_add_to_cart_validation($passed, $product_id, $quantity)
{
    $response = submitGetTransactionStatus(getTransactionId());
    if (
        $response && $response["response"]
        && property_exists($response["response"], 'transactions')
        && sizeof($response["response"]->transactions) > 0
        && property_exists($response["response"]->transactions[0], 'status')
        && $response["response"]->transactions[0]->status == 'acceptedAndPaid'
    ) {
        wc_add_notice(__('Payment already accepted.  Please proceed to check out.', 'woocommerce'), 'error');
        $passed = false;
    }
    return $passed;
};
add_filter('woocommerce_add_to_cart_validation', 'seedpay_add_to_cart_validation', 10, 3);
add_filter('woocommerce_update_cart_validation', 'seedpay_add_to_cart_validation', 10, 3);
