<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div id="unsynced-orders" class="tab-pane active">
    <div class="plugin-content">
        <?php include_once plugin_dir_path( __FILE__ ) . 'tab-header.php'; ?>
        <div class="fulfillment-content mt-4 position-relative">
            <button id="btn-sync-mul" class="btn btn-primary">Sync multiple orders</button>
            <div id="unsynced-orders-table">
                <?php
                wp_nonce_field( 'handle_sync', 'pod_nonce' );
                $orders_table = new AFF_Unsynced_Order();
                $orders_table->prepare_items();
                $orders_table->display();
                ?>
            </div>
        </div>
    </div>
</div>
