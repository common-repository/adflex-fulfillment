<?php
/**
 * Plugin Name: Adflex Fulfillment
 * Plugin URI: https://fulfillment.adflex.vn
 * Description: Sync orders from your WooCommerce store to AdFlex Fulfillment system.
 * Version: 1.0.1
 * Author: Adflex Team
 * Author URI: https://adflex.vn
 **/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AFF_URL', plugins_url( '', __FILE__ ) );
define( 'AFF_API_URL', 'https://seller-fulfillment.adflex.vn/api/async/' );

if ( ! class_exists( 'AFF_Fulfillment' ) ) {
	// Load helper
	require_once( plugin_dir_path( __FILE__ ) . 'includes/helpers.php' );

	if ( ! class_exists( 'AFF_Unsynced_Order' ) ) {
		require_once( plugin_dir_path( __FILE__ ) . 'includes/classes/class-unsynced-orders-list-table.php' );
	}

	if ( ! class_exists( 'AFF_Synced_Order' ) ) {
		require_once( plugin_dir_path( __FILE__ ) . 'includes/classes/class-synced-orders-list-table.php' );
	}

	if ( ! class_exists( 'AFF_Ajax' ) ) {
		require_once( plugin_dir_path( __FILE__ ) . 'includes/classes/class-pod-ajax.php' );
	}

	if ( ! class_exists( 'AFF_Fulfill_Api' ) ) {
		require_once( plugin_dir_path( __FILE__ ) . 'includes/classes/class-pod-fulfill-api.php' );
	}


	class AFF_Fulfillment {
		protected $tabs = [];

		public function __construct() {
			add_action( 'plugins_loaded', array( $this, 'on_plugins_loaded' ) );
		}

		public function on_plugins_loaded() {
			if ( ! class_exists( 'WooCommerce' ) ) {
				add_action( 'admin_notices', function () {
					echo '<div class="error"><p><strong>' . sprintf( 'AdFlex Fulfillment requires the WooCommerce plugin to be installed and active. You can download %s here.', '<a href="https://wordpress.org/plugins/woocommerce/" target="_blank">WooCommerce</a>' ) . '</strong></p></div>';
				} );

				return;
			}

			$this->tabs = [
				'unsynced' => 'Unsynced Orders',
				'synced'   => 'Synced Orders',
				'setting'  => 'Setting',
			];

			$this->add_order_tracking_code_field();
			$this->setting_http_request();

			add_action( 'admin_menu', array( $this, 'create_menu' ) );
			add_action( 'init', array( $this, 'register_custom_order_status' ) );
			add_filter( 'wc_order_statuses', array( $this, 'wc_add_custom_order_status' ) );
			add_filter( 'https_ssl_verify', '__return_false' );
			add_filter( 'woocommerce_screen_ids', [ $this, 'set_wc_screen_ids' ] );

			if ( isset( $_GET['page'] ) && ( 'fulfillment' == sanitize_text_field($_GET['page']) ) ) {
				add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts_and_styles' ) );

				add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_tabs_script' ) );
			}
		}

		public function set_wc_screen_ids( $screen ) {
			$screen[] = 'toplevel_page_fulfillment';

			return $screen;
		}

		public function enqueue_scripts_and_styles() {
			wp_enqueue_style( 'aff-bootstrap', AFF_URL . '/assets/vendor/bootstrap/css/bootstrap.min.css' );
			wp_enqueue_style( 'aff-date-range-picker', AFF_URL . '/assets/vendor/daterangepicker/daterangepicker.css' );
			wp_enqueue_style( 'aff-main', AFF_URL . '/assets/css/plugin.css' );

			wp_enqueue_script( 'aff-bootstrap', AFF_URL . '/assets/vendor/bootstrap/js/bootstrap.bundle.min.js' );
			wp_enqueue_script( 'aff-moment-js', AFF_URL . '/assets/vendor/daterangepicker/moment.min.js' );
			wp_enqueue_script( 'aff-date-range-picker', AFF_URL . '/assets/vendor/daterangepicker/daterangepicker.js' );
			wp_enqueue_script( 'aff-sweetalert', AFF_URL . '/assets/vendor/sweetalert/sweetalert2.js' );
			wp_register_script( 'aff-main', AFF_URL . '/assets/js/script.js' );
			wp_localize_script( 'aff-main', 'aff_ajax', array( 'url' => admin_url( 'admin-ajax.php' ) ) );
			wp_enqueue_script( 'aff-main' );
		}

		public function enqueue_tabs_script() {
			$tab = isset( $_GET['tab'] ) ? sanitize_text_field($_GET['tab']) : 'unsynced';
			wp_register_script( $tab . '-script', AFF_URL . '/assets/js/' . $tab . '.js' );
			wp_localize_script( $tab . '-script', 'aff_ajax', array( 'url' => admin_url( 'admin-ajax.php' ) ) );
			wp_enqueue_script( $tab . '-script' );
		}

		public function setting_http_request() {
			add_filter( 'http_request_timeout', array( $this, 'custom_http_request_timeout' ) );
			add_filter( 'https_ssl_verify', '__return_false' );
		}

		public function custom_http_request_timeout() {
			return 15;
		}

		public function register_mysettings() {
			register_setting( 'pod-options', 'pod_api_key' );
		}

		public function register_custom_order_status() {
			register_post_status( 'wc-fulfillment', array(
				'label'                     => _x( 'Fulfillment', 'shop_order' ),
				'label_count'               => _n_noop( 'Fulfillment <span class="count">(%s)</span>', 'Fulfillment <span class="count">(%s)</span>' ),
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
			) );
		}

		public function wc_add_custom_order_status( $order_statuses ) {
			$order_statuses['wc-fulfillment'] = _x( 'Fulfillment', 'shop_order' );

			return $order_statuses;
		}

		public function create_menu() {
			add_menu_page(
				'Adflex Fulfillment',
				'Adflex Fulfillment',
				'manage_options',
				'fulfillment',
				array( $this, 'pod_tabs' ),
				AFF_URL . '/images/adflex-icon.png'
			);

			add_action( 'admin_init', array( $this, 'register_mysettings' ) );
		}

		public function pod_tabs() {
			$api_key       = get_option( 'pod_api_key' );
			$url           = AFF_API_URL . 'account/balance?api_key=' . $api_key;
			$response      = wp_remote_get( $url );
			$response_body = wp_remote_retrieve_body( $response );

			if ( $response_body && array_key_exists( 'data', json_decode( $response_body, true ) ) ) {
				setlocale( LC_MONETARY, 'en_US' );
				$balance_data    = json_decode( $response_body, true )['data'];
				$current_balance = aff_money_format( $balance_data['current'] );
				$paid            = aff_money_format( $balance_data['paid'] );
			}

			$orders_counts = $this->get_orders_counts();
			$synced_orders = $orders_counts['draft'];
			$draft_orders  = $orders_counts['process'];
			$tabs          = $this->tabs;
			$current_tab   = 'unsynced';

			if ( isset( $_GET['tab'] ) && isset( $this->tabs[ sanitize_text_field($_GET['tab']) ] ) ) {
				$current_tab = sanitize_text_field($_GET['tab']);
			}

			include_once plugin_dir_path( __FILE__ ) . 'includes/templates/main.php';
		}

		public function add_order_tracking_code_field() {
			add_action( 'add_meta_boxes', array( $this, 'tc_tracking_code' ) );
			add_action( 'save_post', array( $this, 'tc_save_meta_box_data' ) );
			add_action(
				'woocommerce_order_details_after_order_table',
				array( $this, 'tc_display_tracking_code_in_order_view' ),
				10,
				1
			);
		}

		public function tc_tracking_code() {
			add_meta_box(
				'tc-tracking-modal',
				'Tracking code',
				array( $this, 'tc_meta_box_callback' ),
				'shop_order',
				'side',
				'core'
			);
		}

		public function tc_meta_box_callback( $post ) {
			$value = get_post_meta( $post->ID, '_tracking_code', true );
			$text  = ! empty( $value ) ? esc_attr( $value ) : '';
			echo '<input type="text" name="tracking_box" id="tc_tracking_code" value="' . $text . '" />';
			echo '<input type="hidden" name="tracking_box_nonce" value="' . wp_create_nonce() . '">';
		}

		public function tc_save_meta_box_data( $post_id ) {
			if ( isset( $_POST['post_type'] ) && 'shop_order' != sanitize_text_field($_POST['post_type']) ) {
				return $post_id;
			}

			if ( ! isset( $_POST['tracking_box_nonce'] ) && ! isset( $_POST['tracking_box'] ) ) {
				return $post_id;
			}

			$nonce = sanitize_text_field($_POST['tracking_box_nonce']);

			if ( ! wp_verify_nonce( $nonce ) ) {
				return $post_id;
			}

			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return $post_id;
			}

			if ( ! current_user_can( 'edit_shop_order', $post_id ) && ! current_user_can( 'edit_shop_orders', $post_id ) ) {
				return $post_id;
			}

			update_post_meta( $post_id, '_tracking_code', sanitize_text_field( $_POST['tracking_box'] ) );
		}

		public function tc_display_tracking_code_in_order_view( $order ) {
			$tracking_box = get_post_meta( $order->get_id(), '_tracking_code', true );
			if ( ! empty( $tracking_box ) && is_account_page() ) {
				echo '<p>Tracking box: ' . $tracking_box . '</p>';
			}
		}

		function get_orders_counts() {
			$empty_counts = array(
				'draft'     => 0,
				'process'   => 0,
				'fulfilled' => 0,
				'canceled'  => 0
			);
			$data         = array(
				'api_key' => get_option( 'pod_api_key' )
			);
			$query_url    = AFF_API_URL . 'order/summary?' . http_build_query( $data );
			$response     = wp_remote_get( $query_url );

			if ( is_wp_error( $response ) ) {
				return $empty_counts;
			}

			$response_body = wp_remote_retrieve_body( $response );

			if ( $response_body &&
			     array_key_exists( 'data', json_decode( $response_body, true ) ) ) {
				return json_decode( $response_body, true )['data'];
			}

			return $empty_counts;
		}
	}
}

new AFF_Ajax();
new AFF_Fulfill_Api();
new AFF_Fulfillment();
