<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class AFF_Ajax
{
    public function __construct() {
        add_action( 'wp_ajax_handle_sync_order', array( $this, 'handle_sync_order' ) );
        add_action( 'wp_ajax_handle_sync_mul', array( $this, 'handle_sync_mul' ) );
        add_action( 'wp_ajax_handle_submit_order', array( $this, 'handle_submit_order' ) );
    }

    public function response($status, $message, $errors = array()) {
        $responseBody = array();
        $responseBody['message'] = $message;
        if ( ! empty( $errors )) {
            $responseBody['errors'] = $errors;
        }
        if ( 200 != $status ) {
            wp_send_json_error( $responseBody, $status );
        }
        else {
            echo json_encode( $responseBody );
        }
        wp_die();
    }

    public function handle_sync_order() {
        if ( ! isset( $_POST['pod_nonce'] )
            || ! wp_verify_nonce( $_POST['pod_nonce'], 'handle_sync' )
        ) {
            $this->response( 400, 'Sorry, your nonce did not verify' );
            return;
        } else {
            $order_id = sanitize_text_field($_POST['order_id']);

            $order = wc_get_order( $order_id );

            $api_key = get_option( 'pod_api_key' );

            if ( empty( $api_key ) ) {
                $this->response( 400, 'API key has not been set up' );
                return;
            }

            if ( empty( $order ) ) {
                $this->response( 400, 'Order does not exist' );
                return;
            }

            if ( ! empty( get_post_meta( $order_id, '_fulfillment_status' )[0] ) ) {
                $this->response( 400, 'Order has been synced' );
                return;
            }

            $errors = array();
            $orders_data = array();

            foreach( $order->get_items() as $item_id => $item ) {
                $sub_errors = array();
                $product_id = $item['product_id'];
                $product_variation_id = $item['variation_id'];

                if ( $product_variation_id ) {
                    $product = wc_get_product( $product_variation_id );
                    $design_sku = $product->get_sku();
                    if ( empty( $design_sku ) ) {
                        $product = wc_get_product( $product_id );
                        $design_sku = $product->get_sku();
                    }
                } else {
                    $product = wc_get_product( $product_id );
                    $design_sku = $product->get_sku();
                }

                if ( empty( $design_sku ) ) {
                    $sub_errors[] = 'product has not had design SKU';
                }

                $size = $product->get_attribute( 'pa_size' );

	            if ( empty( $sub_errors ) ) {
		            $line_order                 = $this->get_order_data( $api_key, $order, $item, $size, $design_sku );
		            $line_order['order_number'] .= '/' . ( $item_id + 1 );
		            $orders_data[]              = $line_order;
	            }
                else {
                    $errors[ $item->get_name() ] = $sub_errors;
                }
            }

            if ( empty( $errors ) ) {
                foreach ( $orders_data as $body ) {
                    $result = $this->call_sync_api( $body );
                    if ( ! empty( $result ) )
                        $errors[ $body['item_title'] ] = $result;
                }
            }
            else {
                $this->response( 400, 'Can not sync order', $errors);
            }

            if ( empty( $errors ) ) {
                add_post_meta( $order_id, '_fulfillment_status', 'synced' );
                $this->response( 200, 'Sync order sucessfully' );
            }
            else {
                $api_key = get_option( 'pod_api_key' );

                if ( empty( $api_key ) ) {
                    $this->response( 400, 'API key has not been set up' );
                    return;
                }

                $this->response( 400, 'Can not sync order', $errors);
            }
        }
    }

    public function handle_sync_mul() {
        if ( ! isset( $_POST['pod_nonce'] )
            || ! wp_verify_nonce( $_POST['pod_nonce'], 'handle_sync' )
        ) {
            $this->response( 200, 'Sorry, your nonce did not verify' );
            return;
        } else {
            $orders_id = aff_sanitize_arr($_POST['orders_id']);
            $message = array();
            $errors = array();
            $can_not_sync_message = 'Can not sync order';

            foreach ( $orders_id as $order_id ) {
                $order = wc_get_order( $order_id );

                $api_key = get_option( 'pod_api_key' );

                if ( empty( $api_key ) ) {
                    $message[$order_id] = $can_not_sync_message;
                    $errors[$order_id] = 'API key has not been set up';
                    break;
                }

                if ( empty( $order ) ) {
                    $message[$order_id] = $can_not_sync_message;
                    $errors[ $order_id] = 'Order does not exist';
                    break;
                }

                if ( !empty( get_post_meta( $order_id, '_fulfillment_status' )[0] ) ) {
                    $message[$order_id] = $can_not_sync_message;
                    $errors[$order_id] = 'Order has been synced';
                    break;
                }

                $sub_errors = array();
                $orders_data = array();

                foreach( $order->get_items() as $item_id => $item ) {
                    $product_id = $item['product_id'];
                    $product_variation_id = $item['variation_id'];

                    if ( $product_variation_id ) {
                        $product = wc_get_product( $product_variation_id );
                        $design_sku = $product->get_sku();
                        if ( empty( $design_sku ) ) {
                            $product = wc_get_product( $product_id );
                            $design_sku = $product->get_sku();
                        }
                    } else {
                        $product = wc_get_product( $product_id );
                        $design_sku = $product->get_sku();
                    }

                    if ( empty( $design_sku ) ) {
                        $sub_errors[ $item->get_name() ][] = 'product has not had design SKU';
                    }

                    $size = $product->get_attribute( 'pa_size' );

                    if ( empty( $sub_errors[ $item->get_name() ] ) ) {
                    	$line_order = $this->get_order_data( $api_key, $order, $item, $size, $design_sku );
                    	$line_order['order_number'] .='/'.$item_id;
                        $orders_data[] = $line_order;
                    }
                }

                if ( empty( $sub_errors ) ) {
                    foreach ( $orders_data as $body ) {
                        $result = $this->call_sync_api( $body );
                        if ( ! empty( $result ) ) {
                            $sub_errors[ $item->get_name() ] = $result;
                        }
                    }
                }
                else {
                    $message[ $order_id ] = $can_not_sync_message;
                    $errors[ $order_id ] = $sub_errors;
                }

                if ( empty( $sub_errors ) ) {
                    add_post_meta( $order_id, '_fulfillment_status', 'synced' );
                    $message[ $order_id ] = 'Sync sucessfully order';
                }
                else {
                    $message[ $order_id ] = $can_not_sync_message;
                    $errors[ $order_id ] = $sub_errors;
                }
            }

            if ( empty ( $errors ) ) {
                $this->response( 200, $message );
            }
            else {
                $this->response( 400, $message, $errors );
            }
        }
    }

    public function handle_submit_order() {
        if ( ! isset( $_POST['pod_nonce'] )
            || ! wp_verify_nonce( $_POST['pod_nonce'], 'handle_submit' )
        ) {
            $this->response( 400, 'Sorry, your nonce did not verify' );
        } else {
        	$order_id = sanitize_text_field($_POST['order_id']);
        	$api_key = get_option('pod_api_key');
	        $args = array(
		        'method'      => 'POST',
		        'timeout'     => 45,
		        'sslverify'   => false,
		        'headers'     => array(
			        'Content-Type'  => 'application/json',
		        ),
		        'body' => json_encode( [
			        'order_ids' => [ $order_id ],
			        'api_key'   => $api_key
		        ] )
	        );

	        $response = wp_remote_post( AFF_API_URL . 'order/submit', $args );

	        $status = wp_remote_retrieve_response_code( $response );
	        if($status == 200){
		        $this->response( 200, 'Submit đơn hàng thành công' );
	        }else{
		        $this->response( $status, wp_remote_retrieve_body( $response ) );
	        }
        }
    }

    public function get_order_data( $api_key,WC_Order $order, $item, $size, $design_sku ) {
        $order_data = array();
        $order_data['api_key'] = $api_key;
        $order_data['order_number'] = $order->get_id();
        $order_data['buyer_fullname'] = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        $order_data['buyer_address'] = $order->get_billing_address_1();
        $order_data['buyer_city'] = $order->get_billing_city();
        $order_data['buyer_state'] = $order->get_billing_state();
        $order_data['buyer_zip'] = $order->get_billing_postcode();
        $order_data['buyer_country'] = $order->get_billing_country();
        $order_data['buyer_phone_number'] = $order->get_billing_phone();
        $order_data['item_title'] = $item->get_name();
        $order_data['quantity'] = $item->get_quantity();
        if ( ! empty( $size ) ) {
            $order_data['size'] = $size;
        }
        $order_data['design_sku'] = $design_sku;
        return $order_data;
    }

    public function call_sync_api( $body ) {
        $args = array(
            'method'      => 'POST',
            'timeout'     => 45,
            'sslverify'   => false,
            'headers'     => array(
                'Content-Type'  => 'application/json',
            ),
            'body'        => json_encode($body),
        );

        $response = wp_remote_post( AFF_API_URL . 'order/store', $args );

        $status = wp_remote_retrieve_response_code( $response );

        if ( 200 == $status ) {
            return 0;
        }

        return json_decode( wp_remote_retrieve_body( $response ), true );
    }
}
