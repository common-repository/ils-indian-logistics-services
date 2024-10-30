<?php
/**
Plugin Name: ILS - Indian Logistics Services
Plugin URI: https://ilsportal.io/
Description: Process your orders in bulk and create hundreds of tracking numbers for your orders in single click, Notify your customers about shipments and sync the tracking details in your ecommerce platform automatically.
Author: Softpulse Infotech
Version: 1.0.2
Author URI: https://softpulseinfotech.com/
Text Domain: ils
License: GPLv3
*Requires PHP: 7.2
 **/
if(!defined('ABSPATH')){
	exit;
}
register_activation_hook(
	__FILE__,
	function () {
		$woocommerce_status = WPILS::WPILS_woocommerce_active_check(); // True if woocommerce is active.
		if ( false === $woocommerce_status ) {
			deactivate_plugins( basename( __FILE__ ) );
			wp_die( esc_html__( 'Oops! You tried installing the plugin without activating woocommerce. Please install and activate woocommerce and then try again .', 'ils-indian-logistics-services' ), '', array( 'back_link' => 1 ) );
		}else{
			WPILS::WPILS_shopiapp_active();
		}
	}
);
define( 'WPILS_DOMAIN', 'https://ilsportal.io/');
define( 'WPILS__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPILS_TRACKING_INFO_KEY', '_ils_tracking_details');
define( 'WPILS_APP_NAME', 'ILS');
define( 'WPILS_SUPPORT_EMAIL', 'support@shopiapps.in');
define( 'WPILS_REVIEW_LINK',WPILS_DOMAIN);
register_deactivation_hook( __FILE__, array( 'WPILS', 'WPILS_shopiapp_deactive' ) );
require_once( WPILS__PLUGIN_DIR . 'class.wpils.php' );
require_once( WPILS__PLUGIN_DIR . 'class.wpils-rest-api.php' );
add_action( 'rest_api_init', 'WPILS_REST_API::init' );
if ( is_admin() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
	require_once( WPILS__PLUGIN_DIR . 'class.wpils-admin.php' );
	add_action( 'init', array( 'WPILS_Admin', 'init' ) );
}
add_action( 'woocommerce_new_order', 'WPILS::WPILS_new_order',1,2);
add_action( 'woocommerce_update_order', 'WPILS::WPILS_order_update', 99, 3 );
add_action( 'woocommerce_after_order_itemmeta', array( 'WPILS_Admin', 'WPILS_tracking_for_line_items' ) , 10, 3);
?>