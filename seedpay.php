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

function ajax_seedpay_submit_request()
{
    $phone = wc_format_phone_number($_REQUEST['phone']);
    $cart = WC()->cart;
    if ($phone == '') {
        wp_send_json(
            array(
                'error' => __('Please enter a valid 10 digit phone number.', 'woocommerce-gateway-seedpay')
            )
        );
        return;
    }
    $request = array(
        'fromPhoneNumber' => $phone,
        'amount' => $cart->total,
        'uniqueTransactionId' => get_transient('uniqueTransactionId') ?? seedpay_generate_new_cart_id()
    );
    $response = submitRequest('requestPayment', $request, 'POST');
    if (gettype($response) == 'string') {
        seedpay_generate_new_cart_id();
        wp_send_json(
            array(
                'error' => $response
            )
        );
        return;
    }
    if ($response && $response->errors && $response->errors[0] != '') {
        seedpay_generate_new_cart_id();
        wp_send_json(
            array(
                'error' => $response->errors[0]
            )
        );
        return;
    }
}

add_action('wp_ajax_ajax_seedpay_submit_request', 'ajax_seedpay_submit_request');
add_action('wp_ajax_nopriv_ajax_seedpay_submit_request', 'ajax_seedpay_submit_request');

function ajax_seedpay_check_request()
{
    $transaction_id = get_transient('uniqueTransactionId');
    $phone = wc_format_phone_number($_REQUEST['phone']);
    if ($phone == '') {
        wp_send_json(
            array(
                'error' => __('Please enter a valid 10 digit phone number', 'woocommerce-gateway-seedpay')
            )
        );
        return;
    }
    $getVars = htmlentities(urlencode(json_encode(array('uniqueTransactionId' => $transaction_id))));
    $url = 'transactions/' . $getVars . '';
    $response = submitRequest($url, null, 'GET');
    if (gettype($response) == 'string') {
        seedpay_generate_new_cart_id();
        wp_send_json(
            array(
                'error' => $response
            )
        );
        return;
    }
    if (gettype($response) == "array") {
        if ($response[0]->status == 'acceptedAndPaid') {
            set_transient('seedpay_order_status_' . $transaction_id . '', $response[0], 168 * HOUR_IN_SECONDS);
            set_transient('seedpay_order_statusname_' . $transaction_id . '', $response[0]->status, 168 * HOUR_IN_SECONDS);
            set_transient('seedpay_order_phone_' . $transaction_id . '', $phone, 168 * HOUR_IN_SECONDS);
            return;
        }
        if ($response[0]->status == 'errored') {
            seedpay_generate_new_cart_id();
            wp_send_json(
                array(
                    'error' => __('There was an error with this transaction.', 'woocommerce-gateway-seedpay')
                )
            );
            return;
        }
        if ($response[0]->status == 'rejected') {
            seedpay_generate_new_cart_id();
            wp_send_json(
                array(
                    'error' => __('Payment was rejected.', 'woocommerce-gateway-seedpay')
                )
            );
            return;
        }
    }
}

add_action('wp_ajax_ajax_seedpay_check_request', 'ajax_seedpay_check_request');
add_action('wp_ajax_nopriv_ajax_seedpay_check_request', 'ajax_seedpay_check_request');

function ajax_checkUserStatus()
{
    $message = array();
    $gateway_settings = get_option('woocommerce_seedpay_settings');
    $phone = wc_format_phone_number($_REQUEST['phone']);
    $url = 'user/isRegistered/' . $phone . '';
    $message['url'] = $url;
    $response = seedpay_request($url, array(), 'GET', $gateway_settings['token']);
    $message['response'] = $response;
    wp_send_json($message);
    die();
}

add_action('wp_ajax_ajax_checkUserStatus', 'ajax_checkUserStatus');
add_action('wp_ajax_nopriv_ajax_checkUserStatus', 'ajax_checkUserStatus');

function seedpay_generate_new_cart_id()
{
    $transactionId = wp_rand();
    set_transient('uniqueTransactionId', $transactionId, 168 * HOUR_IN_SECONDS);
    return $transactionId;
}

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
    $transient = get_transient('seedpay_order_statusname_' . get_transient('uniqueTransactionId') . '');
    if ($transient == 'acceptedAndPaid') {
        wc_add_notice(__('Payment already accepted you can no longer add any items to the cart', 'woocommerce'), 'error');
        $passed = false;
    }
    return $passed;
};

add_filter('woocommerce_add_to_cart_validation', 'seedpay_add_to_cart_validation', 10, 3);
