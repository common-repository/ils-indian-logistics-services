<?php

class WPILS_Admin {
	private static $initiated = false;
	private static $wpdb_instance;
	
	public static function init() {
		if ( ! self::$initiated ) {
			self::init_hooks();
		}
		if(isset($_POST['_wpnonce']) && wp_verify_nonce(  sanitize_text_field( wp_unslash ($_POST['_wpnonce']))) == true){
			if ( isset( $_POST['action'] ) && sanitize_text_field($_POST['action']) == 'verify-key') {
				self::WPILS_save_key();
			}
		}
	}

	public static function init_hooks() {
		global $wpdb;
		self::$initiated = true;
		self::$wpdb_instance = $wpdb;
      
		add_action( 'admin_menu', array( 'WPILS_Admin', 'WPILS_admin_menu' ), 5 );
		add_filter( 'plugin_action_links_'.plugin_basename( plugin_dir_path( __FILE__ ) . 'ils.php'), array( 'WPILS_Admin', 'WPILS_admin_plugin_settings_link' ) );
	}

	public static function WPILS_admin_menu() {
		
		$hook = add_options_page( __('ILS Settings', 'ils-indian-logistics-services'), __('ILS Setting', 'ils-indian-logistics-services'), 'manage_options', 'ils-indian-logistics-services', array( 'WPILS_Admin', 'WPILS_settings_page' ) );
		
		/* Register style required in plugin */
		wp_register_style('wpils_style.css', plugin_dir_url(__FILE__) . 'views/assets/css/style.css');
    	wp_enqueue_style('wpils_style.css');
	}

	public static function WPILS_admin_plugin_settings_link( $links ) {
  		$plugin_links = array(
				'<a href="'.esc_url( self::WPILS_get_page_url() ).'">'.__('Settings', 'ils-indian-logistics-services').'</a>',
				'<a href="'.esc_url(WPILS_DOMAIN).'" target="_blank">' . __( 'Sign Up', 'ils-indian-logistics-services' ) . '</a>',
				'<a href="'.esc_url(WPILS_DOMAIN).'" target="_blank">' . __( 'Documentation', 'ils-indian-logistics-services' ) . '</a>',
			);
  		return array_merge( $plugin_links, $links );
	}

	public static function WPILS_get_page_url( $page = 'config' ) {
		$args = array( 'page' => 'ils-indian-logistics-services' );
		return add_query_arg( $args, menu_page_url( 'ils-indian-logistics-services', false ) );
	}

	public static function WPILS_settings_page() {
		$channel_status = self::WPILS_channel_status();
		if(empty($channel_status)){
			include(plugin_dir_path(__FILE__) . 'views/setup_account.php');		 	
		}else{
		 	include(plugin_dir_path(__FILE__) . 'views/dashboard.php');
		}
	}

	public static function WPILS_tracking_for_line_items($item_id, $item, $order) {
    	$order_id = $item->get_order_id();
	    if ($item instanceof WC_Order_Item_Product) {
	        $shipped = get_post_meta($order_id,'_ils_tracking_details',true);
	        $courier_company = $tracking_number = '';
	        if(is_array($shipped) && !empty($shipped)){
	            foreach($shipped as $ship_item_key=>$ship_item){
	                $courier_company = isset($ship_item['courier_company']) && $ship_item['courier_company'] != '' ? ucfirst($ship_item['courier_company']):'' ;
	                $tracking_number = isset($ship_item['tracking_number']) && $ship_item['tracking_number'] != '' ? ucfirst($ship_item['tracking_number']):'' ;
	                $tracking_url = isset($ship_item['tracking_url']) && $ship_item['tracking_url'] != '' ? ucfirst($ship_item['tracking_url']):'' ;
	                if($ship_item['item_id'] == $item_id){
	                   
	                    echo ('<p><strong>Courier:</strong> '. esc_attr($courier_company) . '</p>');
	                    echo ('<p><strong>Tracking Number:</strong> <a href="'.esc_attr($tracking_url).'" target="_blank">'. esc_attr($tracking_number) . '</a></p>');
	                }
	            }
	        }   
	    }
	}
	
	public static function WPILS_save_key( ) {
		if(wp_verify_nonce(  sanitize_text_field( wp_unslash ($_POST['_wpnonce']))) == true){
			if ( isset( $_POST['action'] ) && sanitize_text_field($_POST['action']) == 'verify-key') {
				$api_key =  sanitize_text_field($_POST['key']);
			
				if($api_key != ''){
					$response = self::WPILS_check_key_status( $api_key );
					if(!empty($response['body'] && $response['body'] !='')){
						$result = json_decode($response['body'], true);
						
						if ( $result['status'] == true ) {
							
							$wpdb = self::$wpdb_instance;
							
							$wpdb->insert("{$wpdb->prefix}ils_shopiapp_settings",['app_mode' => '1']);
						    $setting_ary = $wpdb->get_results("SELECT app_mode,app_token FROM `{$wpdb->prefix}ils_shopiapp_settings` LIMIT 1", ARRAY_A);
							$update = $wpdb->update("{$wpdb->prefix}ils_shopiapp_settings",["app_mode" => '2', "app_token" => $api_key],['app_mode' => '1']);
							
							if ($update !== false) {
							 	$url = menu_page_url( 'ils-indian-logistics-services', false );
						 	 	header('Location: '.$url);
							}				
						}elseif ( $result['status'] == false )
							set_transient('wpils_key_validation_result', $result['message'] , 1);
					}
					elseif ( in_array( 'failed', array( 'invalid', 'failed' ) ) )
						set_transient('wpils_key_validation_result', "Please try again with valid token", 1);
				}else{
					set_transient('wpils_key_validation_result', "Please Enter valid token", 1);
				}
			}else{
				set_transient('wpils_key_validation_result', "Invalid data.", 1);
			}
		}
	}	
	public static function WPILS_check_key_status( $key) {
		return WPILS::WPILS_http_post( "wp/verify_token", array( 'token' => $key, 'store' => get_option( 'home' ) ) );
	}
	public static function WPILS_build_query( $args ) {
		return _http_build_query( $args, '', '&' );
	}	
	public static function WPILS_channel_status() {
	    $wpdb = self::$wpdb_instance;
	    $setting_ary = $wpdb->get_results($wpdb->prepare(
	        "SELECT app_mode,app_token FROM `{$wpdb->prefix}ils_shopiapp_settings` WHERE app_mode = %d AND app_token != '' LIMIT 1",2
	    ), ARRAY_A);

	    return $setting_ary;
	}
}