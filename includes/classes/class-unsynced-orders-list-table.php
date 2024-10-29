<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if( ! class_exists( 'WP_List_Table' ) ) {
  require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class AFF_Unsynced_Order extends WP_List_Table {
    function get_columns(){
        $columns = array(
            'cb'            => '<input type="checkbox" />',
            'id'            => 'ID',
            'date_created'  => 'Time',
            'customer_name' => 'Customer',
            'item_count'    => 'Number of items',
            'status'        => 'Status',
            'action'        => 'Action'
        );
        return $columns;
    }

    function column_default( $item, $column_name ) {
        switch( $column_name ) {
            case 'id':
            case 'date_created':
            case 'customer_name':
            case 'item_count':
            case 'status':
                return $item[ $column_name ];
            default:
                return print_r( $item, true ); //Show the whole array for troubleshooting purposes
        }
    }

    function get_sortable_columns() {
        $sortable_columns = array(
            'id' => array( 'id', false ),
            'date_created' => array( 'date_created', false ),
            'customer_name' => array( 'customer_name', false ),
            'item_count' => array( 'item_count', false ),
            'status'    => array( 'status', false )
        );
        return $sortable_columns;
    }

    function usort_reorder( $a, $b ) {
        // If no sort, default to title
        $orderby = ( ! empty( $_GET['orderby'] ) ) ? sanitize_text_field($_GET['orderby']) : 'id';
        // If no order, default to asc
        $order = ( ! empty($_GET['order'] ) ) ? sanitize_text_field($_GET['order']) : 'desc';
        // Determine sort order
        $result = strcmp( $a[$orderby], $b[$orderby] );
        // Send final sort direction to usort
        return ( 'asc' === $order ) ? $result : -$result;
    }

    function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="order[]" value="%s" />',
            $item['id']
        );
    }

    function column_action($item) {
        return sprintf(
            '<button class="button-sync-order button button-primary" data-id="%s"">
                Sync
            </button>',
            $item['id']
        );
    }

    function no_items() {
        _e( 'No orders found.' );
    }

    function badge_status( $status ) {
        switch ( $status ) {
            case 'pending':
                return 'primary';
                break;
            case 'processing':
                return 'success';
                break;
            case 'on-hole':
                return 'warning';
                break;
            case 'completed':
                return 'info';
                break;
            case 'cancelled':
                return 'danger';
                break;
            case 'refunded':
                return 'secondary';
                break;
            case 'failed':
                return 'dark';
                break;
            default:
                return 'info';
        }
    }

    function get_orders_count() {
        $orders_data = wc_get_orders( array(
            'status'       => 'wc-processing',
            'meta_key'     => '_fulfillment_status',
            'meta_compare' => 'NOT EXISTS',
        ));

        return count( $orders_data );
    }

    function get_orders( $per_page, $offset ) {
        $orders = array();

        $orders_data = wc_get_orders( array(
            'limit'        => $per_page,
            'offset'       => $offset,
            'orderby'      => 'id',
            'order'        => 'DESC',
            'status'       => 'wc-processing',
            'meta_key'     => '_fulfillment_status',
            'meta_compare' => 'NOT EXISTS',
        ));

        foreach( $orders_data as $order_data ){
            $order = array();
            $order['id'] = $order_data->get_id();
            $order['total_amount'] = $order_data->get_total();
            $order['date_created'] = date( 'd/m/Y H:i:s', strtotime( $order_data->get_date_created() ) );
            $order['customer_name'] = $order_data->get_billing_first_name() . ' ' . $order_data->get_billing_last_name();
            $order['item_count'] = $order_data->get_item_count();
            $order['status'] = '<span class="badge badge-' . $this->badge_status( $order_data->get_status() ) . '">' .
            ucfirst( $order_data->get_status() ) . '</span>';
            array_push( $orders, $order );
        }

        return $orders;
    }

    function prepare_items() {
        $per_page = 10;
        $offset = max( 0, intval( $this->get_pagenum() - 1 ) * $per_page );
        $orders = $this->get_orders( $per_page, $offset );

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        usort( $orders, array( &$this, 'usort_reorder' ) );
        $this->_column_headers = array( $columns, $hidden, $sortable );

        $total_items = $this->get_orders_count();

        $this->set_pagination_args( array(
            'total_items' => $total_items,
            'per_page'    => $per_page
        ) );

        $this->items = $orders;
    }
}
