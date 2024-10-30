<?php
/**
 * @package ILS
 */
class WPILS {
	private static $active_plugins;
	private static $current_user_details;
	private static $current_user_email_id;
	private static $current_user_meta;

	public static function init() {
		self::$active_plugins = (array) get_option( 'active_plugins', array() );
		if ( is_multisite() ) {
			self::$active_plugins = array_merge( self::$active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
		}
	}
	
	public static function WPILS_woocommerce_active_check() {
		if ( ! self::$active_plugins ) {
			self::init();
		}
		if(in_array( 'woocommerce/woocommerce.php', self::$active_plugins, true ) || array_key_exists( 'woocommerce/woocommerce.php', self::$active_plugins )){
			$blogusers = get_users('role=Administrator');
	        foreach ($blogusers as $user) {
	        	$user_data = array(
	        		'email' => $user->user_email,
	        		'name' => $user->user_login,
	        		'user_url' => $user->user_url,
	        		'display_name' => $user->display_name,
	        	);
	        }
	        self::WPILS_http_post( "wp/installChannel", $user_data );
		}
	}

	public static function WPILS_get_current_user_details() {
		if ( empty( self::$current_user_details ) ) {
			self::$current_user_details = wp_get_current_user();
		}
		return self::$current_user_details;
	}

	public static function WPILS_get_current_user_email_id() {
		if ( empty( self::$current_user_email_id ) ) {
			$current_user_details        = self::WPILS_get_current_user_details();
			self::$current_user_email_id = $current_user_details->__get( 'user_email' );
		}
		return self::$current_user_email_id;
	}

	public static function WPILS_get_current_user_meta() {
		if ( empty( self::$current_user_meta ) ) {
			self::$current_user_meta = get_user_meta( get_current_user_id() );
		}
		return self::$current_user_meta;
	}

	public static function WPILS_shopiapp_active(){
		global $wpdb;
		
	    $create_tbl = "CREATE TABLE `{$wpdb->prefix}ils_shopiapp_settings` (
	          `id` int(11) NOT NULL AUTO_INCREMENT,
	          `app_mode` enum('1','2','3') NOT NULL DEFAULT '1',
	          `app_token` text,	
	          PRIMARY KEY (id)
	        ) ENGINE=InnoDB DEFAULT CHARSET=latin1";

	    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $create_tbl );
	    /*$wpdb->query($create_tbl);*/
	    $wpdb->insert("{$wpdb->prefix}ils_shopiapp_settings",['app_mode' => '1']);
	}

	public static function WPILS_shopiapp_deactive(){
		global $wpdb;
		self::WPILS_http_post( "wp/uninstall", array( 'token' => self::WPILS_getToken(), 'store' => get_option( 'home' ) ) );
    	$wpdb->query("DROP table `{$wpdb->prefix}ils_shopiapp_settings`");
	}

	public static function WPILS_new_order($order_id,$order) {
	    
	    $order = wc_get_order($order_id);
	    /*Access order details*/
	    $order_data = $order->get_data();
	    $response =  json_encode($order_data);
	}

	public static function WPILS_order_update($order_id,$order) {
	    $order = self::WPILS_get_order_data($order_id);
	    $order_data = $order->get_data();
	    $request = [];
	    $request['data'] = $order_data;
	    $request['action'] = 'update';
	  	$response = self::WPILS_http_post( "wordpress/order/update", $request);
	}

	public static function WPILS_get_order_data($order_id) {
	    
	    $order = wc_get_order( $order_id );
	    $order_refunds  = $order->get_refunds();
	    $refund = array();
	    foreach ($order_refunds as $ref_id => $refunds) {
	        $refund[] = array(
				            'id'=>$refunds->get_id(),
				            'amount' => $refunds->get_amount(),
				            'reason'=>$refunds->get_reason(),
				            'refund_date'=> $refunds->get_date_created()
				        );
	    }
	    $items = array(); $total_weight = 0;
	    foreach ( $order->get_items() as $item_id => $item ) {
	        $product        = $item->get_product();
	        $regular_price = (float) $item->get_subtotal();
	        $sale_price = (float) $item->get_total();
	        $discount =  $regular_price - $sale_price;
	        
	        $total_weight += ($item->get_product()->get_weight() != '' ? $item->get_product()->get_weight() : 0);
	        $items [] = array(
	            'item_id'=>$item->get_id(),
	            'product_id'=> $item->get_product_id(),
	            'name'=>$item->get_name(),
	            'quantity'=>$item->get_quantity(),
	            'price'=> round($product->get_display_price(),2),
	            'subtotal'=>$item->get_subtotal(),
	            'total'=> $item->get_total(),
	            'tax'=> $item->get_subtotal_tax(),
	            'tax_status'=> $item->get_tax_status(),
	            'sku' => $item->get_product()->get_sku(),
	            'length'=> $item->get_product()->get_length(),
	            'width'=> $item->get_product()->get_width(),
	            'height'=> $item->get_product()->get_height(),
	            'weight'=> $item->get_product()->get_weight(),
	            'options' => $item->get_meta_data(),
	            'discount'=> $discount,
	            'refund_quantity' => abs($order->get_qty_refunded_for_item($item_id)),
	            'item_type'=> $item->get_type()
	        );
	    }
	    $response = array(
	        'user'=> $order->get_user(),
	        'id'=> $order->get_id(),
	        'order_data' => $order->get_data(),
	        'line_item' => $items,
	        'total_weight'=>$total_weight,
	        'coupon_codes' => $order->get_coupon_codes(),
	        'order_subtotal'=>$order->get_subtotal(),
	        'remaining_refund'=>$order->get_remaining_refund_amount(),
	        'total_refund_qty'=>$order->get_item_count_refunded(),
	        'shipping_refunded'=>$order->get_total_shipping_refunded(),
	        'tax_refunded'=>$order->get_total_tax_refunded(),
	        'total_refunded'=>$order->get_total_refunded(),
	        'date' => $order->get_date_created(),
	        'refunds' => $refund,
	        'total_with_tax'=>$order->get_prices_include_tax()
	    );
	    return rest_ensure_response($response);
	}

	public static function WPILS_http_post($endpoint, $request) {
		$host      = WPILS_DOMAIN;
		$http_host = $host.$endpoint;
		$token = self::WPILS_getToken();
		if($endpoint != 'wp/verify_token'){
			$request['shop'] = get_option( 'home' );
			$request['token'] = $token;
		} 
		$http_args = array(
			'body' => json_encode($request),
			'headers' => array(
				'Content-Type' => 'application/json;',
			),
			'httpversion' => '1.0',
			'sslverify'=> false,
			'timeout' => 60
		);
		$response = wp_remote_post( $http_host, $http_args );
		if ( is_wp_error( $response ) ) {
			return array( '', '' );
		}
		$body = wp_remote_retrieve_body($response);
		return array( 'headers' => $response['headers'],'body' => $response['body'] );
	}

	public static function WPILS_getToken() {
	    global $wpdb;

	    $setting_ary = $wpdb->get_results($wpdb->prepare(
	        "SELECT app_mode,app_token FROM `{$wpdb->prefix}ils_shopiapp_settings` WHERE app_mode = %d AND app_token != '' LIMIT 1",2
	    ), ARRAY_A);
	    $app_token = '';
	    if(!empty($setting_ary)){
	    	$app_mode = $setting_ary[0]['app_mode'];
	    	if($app_mode == '2') $app_token = $setting_ary[0]['app_token'];
	    }
	    return $app_token;
	}
}