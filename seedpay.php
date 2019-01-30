<?php
/**
 * Plugin Name: WooCommerce SeedPay Gateway
 * Plugin URI: http://seedpay.com
 * Description: Receive payments using Seedpay
 * Author: Seedpay
 * Author URI: http://seedpay.com/
 * Version: 1.0.0
 * WC tested up to: 3.3
 * WC requires at least: 2.6
 */
 
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}
	
	/**
	 * Required functions
	 */
	if ( ! function_exists( 'woothemes_queue_update' ) ) {
		require_once( 'woo-includes/woo-functions.php' );
	}
	
	
	

	
	
	function woocommerce_seedpay_init(){
		
		require_once( plugin_basename( 'includes/class-wc-gateway-seedpay.php' ) );
		load_plugin_textdomain( 'woocommerce-gateway-seedpay', false, trailingslashit( dirname( plugin_basename( __FILE__ ) ) ) );
		add_filter( 'woocommerce_payment_gateways', 'woocommerce_seedpay_add_gateway' );
		
		
		
	}
	add_action( 'plugins_loaded', 'woocommerce_seedpay_init', 0 );
	function woocommerce_seedpay_add_gateway( $methods ) {
		$methods[] = 'WC_Gateway_SeedPay';
		return $methods;
	}
	
	function woocommerce_seedpay_plugin_links( $links ) {
		$settings_url = add_query_arg(
			array(
				'page' => 'wc-settings',
				'tab' => 'checkout',
				'section' => 'wc_gateway_seedpay',
			),
			admin_url( 'admin.php' )
		);
	
		$plugin_links = array(
			'<a href="' . esc_url( $settings_url ) . '">' . __( 'Settings', 'woocommerce-gateway-seedpay' ) . '</a>'
		);
	
		return array_merge( $plugin_links, $links );
	}
	add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'woocommerce_seedpay_plugin_links' );
