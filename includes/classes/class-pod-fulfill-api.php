<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class AFF_Fulfill_Api
{
    protected $actions;
    public function __construct() {
        $this->actions = array(
            'update-order'         => 'update_order',
            'update-tracking-code' => 'update_tracking_code',
        );
        add_action( 'rest_api_init', array( $this, 'register_api_route' ) );
    }

    public function register_api_route() {
        $version = 1;
        $namespace = 'pod-fulfil-api/v' . $version;
        foreach ( $this->actions as $action => $function ) {
            register_rest_route( $namespace, '/' . $action, array(
            'methods' => 'POST',
            'callback' => array( $this, $function ),
            'permission_callback' => '__return_true',
        ) );
        }
    }

    public function validate_request( WP_REST_Request $request ) {
        $errors = array();
        if ( empty( $request['api_key'] ) ) {
            $errors['api_key'] = 'API key is required';
        }
        else {
            if ( get_option( 'pod_api_key' ) != $request['api_key'] ) {
                $errors['api_key'] = 'API key is invalid';
            }
        }

        if ( empty( $request['order_id'] ) ) {
            $errors['order_id'] = 'Order ID is required';
        }

        return $errors;
    }

    public function update_order( WP_REST_Request $request ) {
        $status_code = 200;
        $response_data = array();
        $order_id = sanitize_text_field($request['order_id']);
        $order_id = explode('/',$order_id);

        $errors = $this->validate_request( $request );

        if ( empty( $request['status'] ) ) {
            $errors['status'] = 'Status is required';
        }

        $order = new WC_Order( @$order_id[0] );

        if ( empty( $order ) ) {
            $errors['order'] = 'Order does not exist';
        }

        if ( empty( $errors ) ) {
        	$status = sanitize_text_field($request['status']);
            if ( $order->update_status( $status)) {
                $response_data['message'] = 'Update order status successfully';
            }
            else {
                $status_code = 400;
                $response_data['message'] = 'Update order status fail';
            }
        }
        else {
            $status_code = 400;
            $response_data['message'] = 'Update order status fail';
            $response_data['errors'] = $errors;
        }

        return new WP_REST_Response( $response_data, $status_code );
    }

    public function update_tracking_code( WP_REST_Request $request ) {
        $status_code = 200;
        $response_data = array();
	    $order_id = sanitize_text_field($request['order_id']);
	    $order_id = explode('/',$order_id);

        $errors = $this->validate_request( $request );

        if ( empty( $request['tracking_code'] ) ) {
            $errors['tracking_code'] = 'Status is required';
        }

        $order = new WC_Order( @$order_id[0] );

        if ( empty( $order ) ) {
            $errors['order'] = 'Order does not exist';
        }

        if ( empty( $errors ) ) {
            if ( update_post_meta( $order_id[0], '_tracking_code', sanitize_text_field($request['tracking_code']) )) {
                $response_data['message'] = 'Update tracking code successfully';
            }
            else {
                $status_code = 400;
                $response_data['message'] = 'Update tracking code fail';
            }
        }
        else {
            $status_code = 400;
            $response_data['message'] = 'Update tracking code fail';
            $response_data['errors'] = $errors;
        }

        return new WP_REST_Response( $response_data, $status_code );
    }
}
