<?php 
class WPILS_REST_API {
	public static function init() {
		if ( ! function_exists( 'register_rest_route' ) ) {		
			echo "string";exit;
			return false;
		}
		register_rest_route( 'wpils/v1', '/auth/', array(
			array(
				'methods' => 'POST',
				'callback' => array( 'WPILS_REST_API', 'WPILS_get_settings' ),
				'permission_callback' => '__return_true'
			)
		));
		
	    register_rest_route('wpils/v1', '/single_order', array(
	    	array(
		        'methods' => 'POST',
		        'callback' => 'WPILS_REST_API::WPILS_get_single_order',
		        'permission_callback' => '__return_true',
		    )
	    ));
	    register_rest_route('wpils/v1', '/get_orders', array(
	    	array(
		        'methods' => 'POST',
		        'callback' => 'WPILS_REST_API::WPILS_get_multi_order',
		        'permission_callback' => '__return_true',
		    )
	    ));
	    register_rest_route('wpils/v1', '/create_tracking', array(
	    	array(
		        'methods' => 'POST',
		        'callback' => 'WPILS_REST_API::WPILS_add_tracking_info',
		        'permission_callback' => '__return_true',
		    )
	    ));
	    register_rest_route('wpils/v1', '/cancel_tracking', array(
	    	array(
		        'methods' => 'POST',
		        'callback' => array( 'WPILS_REST_API', 'WPILS_cancel_tracking' ),
		        'permission_callback' => '__return_true',
		    )
	    ));
	    register_rest_route('wpils/v1', '/update_tracking', array(
	    	array(
		        'methods' => 'POST',
		        'callback' => array( 'WPILS_REST_API', 'WPILS_update_tracking' ),
		        'permission_callback' => '__return_true',
		    )
	    ));
	}
	public static function WPILS_get_multi_order($request){
		$request = WPILS_REST_API::WPILS_verify_request($request);
	
		$json = [];
		$json['status'] = 'failed';
		if($request){
			$limit = (isset($request['limit']) ? (int)sanitize_text_field($request['limit']) : 50);
			$page = (isset($request['page']) ? (int)sanitize_text_field($request['page']) : 1);
			$numberOfDays = (isset($request['numberOfDays']) ? (int)sanitize_text_field($request['numberOfDays']) : 10);
			$days_in_seconds = ($numberOfDays * 86400);
			
			$args = array(
		        'paginate' => true,
		        'date_after' => '>' . ( time() - $days_in_seconds ),
		        'orderby' => 'date',
		        'posts_per_page' => $limit,
		        'paged' => $page,
		        'order' =>'DESC'
		    );
		    $orders = wc_get_orders($args);
		    $order_data = [];
		    if(!empty($orders)){
			    foreach ($orders->orders as $order) {
			    	array_push($order_data, $order->get_id());
			    }
			    $json['orders'] = $order_data;
				$json['status'] = 'success';
			}
		}
		return rest_ensure_response($json);
	}
	public static function WPILS_get_single_order($request){
		$request = WPILS_REST_API::WPILS_verify_request($request);
		
		$json = [];
		$json['status'] = 'failed';
		if($request){
			
			$order_id = sanitize_text_field($request['order_id']);
			$order = WPILS::WPILS_get_order_data($order_id);
			$order_data = $order->get_data();
			
			if(!empty($order_data)){
				$json['order'] = $order_data;
				$json['status'] = 'success';
			}				
		}
		return rest_ensure_response($json);
	}
	public static function WPILS_add_tracking_info($request){
		
		$json = [];
		$json['status'] = 'failed';
		$json['message'] = 'Invalid input.';
		$request = WPILS_REST_API::WPILS_verify_request($request);
		if($request){
			$order_id = $request['order_id'];
			$action = sanitize_text_field($request['action']);
			$trackingDetails = $request['trackingDetails'];
			if(is_array($trackingDetails)){
				$order_items = [];
				
				$order = wc_get_order($order_id);
				if(is_a($order,'WC_Order')){
		            $items = $order->get_items();
		            if(is_array($items) && !empty($items)){
		                foreach($items as $item_key=>$item){
		                    if(is_a($item,'WC_Order_Item_Product')){
		                        $item_data = $item->get_data();
		                        $item_id = isset($item_data['id']) ? $item_data['id'] : $item->get_id();
		                        
		                        $order_items['item_'.$item_id] = [
		                        									'quantity' => (isset($item_data['quantity'])) ? $item_data['quantity'] : $item->get_quantity(),
		                        									'item_id' => $item_id,
		                        									'name' => isset($item_data['name']) ? $item_data['name'] : $item->get_name()
		                        								];
		                    }
		                }
		            }
		        }
		        if(!empty($order_items)){
		        	$shipment_data = array();
		        	$shipped = get_post_meta($order_id,WPILS_TRACKING_INFO_KEY,true);
		        	if(is_array($shipped) && !empty($shipped)) 
		        		$shipment_data = $shipped;
					foreach ($trackingDetails as $key => $value) {
						$trackingInfo = (is_array($value['trackingInfo']) ? $value['trackingInfo'] : []);
						$lineItems = $value['lineItems'];
						$fulfill_id = sanitize_text_field($value['fulfill_id']);
						$trackingNumber = sanitize_text_field($trackingInfo['trackingNumber']);
				        $shippingProvider = sanitize_text_field($trackingInfo['shippingProvider']);
				        $trackingLink = sanitize_text_field($trackingInfo['trackingLink']);
				        if(!empty( $lineItems )){
				        	foreach ($lineItems as $key => $line) {
				        		$line_id = sanitize_text_field($line['line_items_id']);
		        				if(array_key_exists('item_'.$line_id, $order_items)){
		        					$order_item = $order_items['item_'.$line_id];
			                        $shipment_data[] = array(
			                        	'id' => $fulfill_id,
			                            'order_id'=> $order_id,
			                            'item_id' => $line_id,
			                            'name'    => $order_item['name'],
			                            'qty'     => $order_item['quantity'],
			                            'courier_company' => $shippingProvider,
			                            'tracking_number' => $trackingNumber,
			                            'tracking_url' => $trackingLink,
			                            'created_at' => gmdate('Y-m-d H:i:s'),
			                            'updated_at' => gmdate('Y-m-d H:i:s')
			                        );
			                        unset($order_items['item_'.$line_id]);
			                	}
				        	}
				        }
					}
					if(!empty($shipment_data)){
			        	update_post_meta($order_id,WPILS_TRACKING_INFO_KEY,$shipment_data);
			        	if(empty($order_items)) 
			        		$order->update_status('wc-completed', 'Order fulfilled by '.WPILS_APP_NAME);
					}
			        $json['status'] = 'success';
			        $json['message'] = 'Data updated successfully.';
			    }
			}
		}
		return rest_ensure_response($json);
	}
	public static function WPILS_verify_request($request){
		$request = json_decode($request->get_body(),true);
		$request = sanitize_post($request);
		if(isset($request['token'])){
			$token = sanitize_text_field($request['token']);
			$storeToken = WPILS::WPILS_getToken();
			if($storeToken == $token){
				return $request;
			}
		}
		return false;
	}
	public static function WPILS_update_tracking($request){
		$json = [];
		$json['status'] = 'failed';
		$json['message'] = 'Invalid input.';
		$request = WPILS_REST_API::WPILS_verify_request($request);
		if($request){
			$action = sanitize_text_field($request['action']);
			if($action == 'update_fulfill_order'){
				$order_id = sanitize_text_field($request['order_id']);
				$trackingDetails = $request['trackingInfo'];
				$fulfillmentId = sanitize_text_field($request['fulfillmentId']);
				
				$shipped = get_post_meta($order_id,WPILS_TRACKING_INFO_KEY,true);
				if(!empty($shipped) && !empty($trackingDetails)){
					foreach ($shipped as $key => $shipment) {
						if(in_array($shipment['id'], $fulfillmentId) ) {
							$shipped[$key]['courier_company'] = sanitize_text_field($trackingDetails['shippingProvider']);
							$shipped[$key]['tracking_number'] = sanitize_text_field($trackingDetails['trackingNumber']);
							$shipped[$key]['tracking_url'] = sanitize_url($trackingDetails['trackingLink']);
							$shipped[$key]['updated_at'] =  gmdate('Y-m-d H:i:s');
						}
					}						
				}
				
				update_post_meta($order_id,WPILS_TRACKING_INFO_KEY,$shipped);
				$json['status'] = 'success';
				$json['message'] = 'Data updated successfully.';
			}
		}
		return rest_ensure_response($json);
	}
	public static function WPILS_cancel_tracking($request){
		$json = [];
		$json['status'] = 'failed';
		$json['message'] = 'Invalid input.';
		$request = WPILS_REST_API::WPILS_verify_request($request);
		if($request){
			$action = sanitize_text_field($request['action']);
			if($action == 'cancel_fulfill_order'){
				$order_id = sanitize_text_field($request['order_id']);
				$fulfillmentIds = $request['fulfillmentId'];
				$shipped = get_post_meta($order_id,WPILS_TRACKING_INFO_KEY,true);
				if(!empty($shipped) && !empty($fulfillmentIds)){
					foreach ($fulfillmentIds as $key => $fulfill_id) {
						
						foreach ($shipped as $key => $shipment) {
							if($shipment['id'] == sanitize_text_field($fulfill_id)) unset($shipped[$key]);
						}
					}
				}
				$shipped = array_values($shipped);
				update_post_meta($order_id,WPILS_TRACKING_INFO_KEY,$shipped);
				$json['status'] = 'success';
				$json['message'] = 'Data updated successfully.';
			}
		}
		return rest_ensure_response($json);
	}
	public static function WPILS_get_settings($request) {
		$parameters = json_decode($request->get_body(),true);
		$site_url = site_url();
		$receive_url =  rtrim($parameters['store_url'],'/');
		$responce = array();
		if($site_url == $receive_url){
			$admin_email = get_option('admin_email');
			$responce['verify'] = true;
			$responce['admin_email']= $admin_email;
			$responce['site_url'] = $site_url;
			$responce['store_name']= get_bloginfo('name');
		}else{
			$responce['verify'] = false;
			$responce['message'] = "Domain verify fail";
		}
		return rest_ensure_response($responce);
	}
}
?>