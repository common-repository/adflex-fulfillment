<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if( ! class_exists( 'WP_List_Table' ) ) {
  require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class AFF_Synced_Order extends WP_List_Table {
    function get_columns(){
        $columns = array(
            'id'              => 'Order ID',
            'date_created'    => 'Time',
            'customer_name'   => 'Customer',
            'payment_status'  => 'Payment',
            'fulfill_status'  => 'Fulfillment',
            'tracking_status' => 'Tracking',
            'fulfill_cost'    => 'Fulfill cost',
            'shipping_cost'   => 'Shipping cost',
            'status'          => 'Status',
            'action'          => 'Action',
        );
        return $columns;
    }

    function column_default( $item, $column_name ) {
        switch( $column_name ) {
            case 'id':
            case 'date_created':
            case 'customer_name':
            case 'payment_status':
            case 'fulfill_status':
            case 'tracking_status':
            case 'fulfill_cost':
            case 'shipping_cost':
            case 'status':
                return $item[ $column_name ];
            default:
                return print_r( $item, true ); //Show the whole array for troubleshooting purposes
        }
    }

    function column_id( $item ) {
        return sprintf(
            '%s<br>No: %s', $item['id'], $item['order_number']
        );
    }

	function column_action( $item ) {
    	$attribute = [
    		'class'=>'button-submit-order',
		    'data-id' => $item['id']
	    ];
		if($item['status'] != 'Draft')  $attribute['disabled'] = true;

    	submit_button( 'Submit' , 'primary button-submit-order' ,'',true,$attribute);
	}

    function get_sortable_columns() {
        $sortable_columns = array(
            'id'              => array( 'id', false ),
            'date_created'    => array( 'date_created', false ),
            'customer_name'   => array( 'customer_name', false ),
            'payment_status'  => array( 'payment_status', false ),
            'fulfill_status'  => array( 'fulfill_status', false ),
            'tracking_status' => array( 'tracking_status', false ),
            'fulfill_cost'    => array( 'fulfill_cost', false ),
            'shipping_cost'   => array( 'shipping_cost', false ),
            'status'          => array( 'status', false )
        );
        return $sortable_columns;
    }

    function usort_reorder( $a, $b ) {
        // If no sort, default to title
        $orderby = ( ! empty( $_GET['orderby'] ) ) ? sanitize_text_field($_GET['orderby']) : 'date_created';
        // If no order, default to asc
        $order = ( ! empty($_GET['order'] ) ) ? sanitize_text_field($_GET['order']) : 'desc';
        // Determine sort order
        $result = strcmp( $a[$orderby], $b[$orderby] );
        // Send final sort direction to usort
        return ( $order === 'asc' ) ? $result : -$result;
    }

    function no_items() {
        _e( 'No orders found.' );
    }

    function get_request_data() {
        $data = array(
            'api_key' => get_option( 'pod_api_key' ),
        );

        $dates = array();

        if (isset($_GET['start_date'])) {
        	$start_date = sanitize_text_field($_GET['start_date']);
            $dates[] = date( 'Y-m-d H:i:s', strtotime( $start_date . ' 00:00:00' ) );
        }
        else {
            $dates[] = date('Y-m-d H:i:s', strtotime('today - 30 days'));
        }

        if (isset($_GET['end_date'])) {
        	$end_date = sanitize_text_field($_GET['end_date']);
            $dates[] = date( 'Y-m-d H:i:s', strtotime( $end_date. ' 23:59:59' ) );
        }
        else {
            $dates[] = date('Y-m-d H:i:s', strtotime('today + 1 days'));
        }

        $data['date'] = $dates;

        if ( isset( $_GET['payment_status'] ) ) {
            $data['payment'] = sanitize_text_field($_GET['payment_status']);
        }

        if ( isset( $_GET['fulfillment_status'] ) ) {
            $data['fulfill'] = sanitize_text_field($_GET['fulfillment_status']);
        }

        if ( isset( $_GET['tracking_status'] ) ) {
            $data['tracking'] = sanitize_text_field($_GET['tracking_status']);
        }

        if ( isset( $_GET['other_status'] ) ) {
            $data['other'] = sanitize_text_field($_GET['other_status']);
        }

        if ( isset( $_GET['search_type'] ) ) {
            $data['search_type'] = sanitize_text_field($_GET['search_type']);
            if (isset($_GET['search_key'])) {
                $data['s'] = sanitize_text_field($_GET['search_key']);
            }
        }
        return $data;
    }

    public function get_orders( $per_page ) {
        try{
	        $response_orders = array();
	        $orders = array();
	        $data = $this->get_request_data();
	        $data['page'] = $this->get_pagenum();
	        $data['size'] = $per_page;
	        $query_url = AFF_API_URL . 'order/list?' . http_build_query( $data );
	        $response = wp_remote_get( $query_url );
	        $response_body = wp_remote_retrieve_body( $response );

	        if ( is_wp_error( $response ) || ! json_decode( $response_body, true ) ||
	             ! array_key_exists( 'data',  json_decode( $response_body, true ) ) ) {
		        return array(
			        'orders' => [],
			        'number_total_orders' => 0
		        );
	        }

	        $result = json_decode( $response_body, true );
	        $orders_data = $result['data'];

	        foreach( $orders_data as $order_data ){
		        $order = array();
		        $order['id'] = $order_data['id'];
		        $order['order_number'] = $order_data['order_number'];
		        $order['date_created'] = date( 'd/m/Y H:i:s', strtotime( $order_data['created_at'] ) );
		        $order['customer_name'] = $order_data['customer_name'];
		        $order['payment_status'] = $order_data['payment_status_text'] ?
			        ucfirst( $order_data['payment_status_text'] ) :
			        '<b>...</b>';
		        $order['fulfill_status'] = $order_data['fulfill_status_text'] ?
			        ucfirst( $order_data['fulfill_status_text'] ) :
			        '<b>...</b>';
		        $order['tracking_status'] = $order_data['tracking_code'] ? wc_help_tip($order_data['tracking_code']): '<b>...</b>';
		        $order['fulfill_cost'] = aff_money_format( $order_data['fulfill_price'] );
		        $order['shipping_cost'] = aff_money_format( $order_data['shipping_cost'] );
		        $order['status'] = $this->convert_status( $order_data['status'] );
		        array_push( $orders, $order );
	        }

	        $number_total_orders = $result['meta']['total'];
	        $response_orders['orders'] = $orders;
	        $response_orders['number_total_orders'] = $number_total_orders;

	        return $response_orders;
        } catch ( \Exception $e ) {
	        aff_logger( $e );

	        return [];
        }
    }

    function prepare_items() {
        $per_page = 10;
        $response_orders = $this->get_orders( $per_page );
        $orders = $response_orders['orders'];

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        usort( $orders, array( &$this, 'usort_reorder' ) );
        $this->_column_headers = array( $columns, $hidden, $sortable );

        $total_items = $response_orders['number_total_orders'];

        $this->set_pagination_args( array(
            'total_items' => $total_items, //WE have to calculate the total number of items
            'per_page'    => $per_page     //WE have to determine how many items to show on a page
        ) );

        $this->items = $orders;
    }

    public function convert_status( $int_status ) {
        $status = [
            0  => 'Draft',
            1  => 'Address Verifying',
            2  => 'Address US Valid',
            3  => 'Address US Invalid',
            4  => 'Address US Buyer Confirmed',
            5  => 'Address Other',
            11 => 'Design Checking',
            12 => 'Design Error',
            13 => 'Design Completed',
            20 => 'Paid',
            21 => 'Refunded',
            22 => 'Unpaid',
            30 => 'Processing',
            31 => 'Fulfilled',
            32 => 'Delivered',
            33 => 'Processing',
            34 => 'Processing',
            40 => 'Tracking Completed',
            41 => 'Tracking Incomplete',
            42 => 'Tracking Missing',
            50 => 'Done',
            -1 => 'Cancel',
            -2 => 'Unresolved',
            -3 => 'Rejected',
        ];

        if ( array_key_exists( $int_status, $status ) ) {
            $string_status = $status[ $int_status ];
        } else {
            $string_status = '<b>...</b>';
        }

        return $string_status;
    }
}
