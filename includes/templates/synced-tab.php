<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div id="synced-orders" class="tab-pane active">
    <div class="plugin-content">
        <?php include_once plugin_dir_path( __FILE__ ) . 'tab-header.php'; ?>
        <div class="fulfillment-content mt-4">
            <form method="get" action="<?php echo admin_url('admin.php') ?>">
                <input type="hidden" name="page" value="fulfillment" />
                <input type="hidden" name="tab" value="synced" />
                <div class="search-block row mt-4">
                    <div class="input-group col-md-4">
                        <input type="text" value="<?php echo $_GET['start_date'] ? esc_html($_GET['start_date']).' - '.esc_html($_GET['end_date']) : ''; ?>" class="form-control picker"/>
                        <input type="hidden" value="<?php echo esc_html($_GET['start_date']) ?>" class="start-date" name="start_date"/>
                        <input type="hidden" class="end-date" <?php echo esc_html($_GET['end_date']) ?> name="end_date"/>
                        <span class="calendar-icon">
                            <span class="dashicons dashicons-calendar-alt"></span>
                        </span>
                    </div>
                    <div class="input-group col-md-4 position-relative">
                    <span class="search-icon">
                        <span class="dashicons dashicons-search"></span>
                    </span>
                        <input type="text" name="search_key" class="form-control search-input"
                               placeholder="Search key" />
                    </div>
                    <div class="input-group col-md-2">
                        <select name="search_type" class="form-control">
                            <option value="">Search by</option>
                            <option value="order_id">Order ID</option>
                            <option value="customer">Customer</option>
                            <option value="design_sku">Design SKU</option>
                        </select>
                    </div>
                    <div class="input-group col-md-1">
			            <?php submit_button('Search','button primary','',false,['id'=>'btn']) ?>
                    </div>
                    <div class="input-group col-md-1">
			            <?php submit_button('Clear','button delete','',false,['id'=>'btn-clear-filters']) ?>
                    </div>
                </div>
                <div class="filters-block row mt-4">
                    <div class="form-group col-md-3">
                        <label>Payment status</label>
                        <div class="input-group">
                            <select name="payment_status" class="form-control">
                                <option value="">All</option>
                                <option value="20">Paid</option>
                                <option value="21">Refunded</option>
                                <option value="22">Unpaid</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group col-md-3">
                        <label>Fulfillment status</label>
                        <div class="input-group">
                            <select name="fulfillment_status" class="form-control">
                                <option value="">All</option>
                                <option value="30">Processing</option>
                                <option value="31">Fulfilled</option>
                                <option value="32">Delivered</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group col-md-3">
                        <label>Tracking status</label>
                        <div class="input-group">
                            <select name="tracking_status" class="form-control">
                                <option value="">All</option>
                                <option value="">Unavailable</option>
                                <option value="42">Available</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group col-md-3">
                        <label>Other status</label>
                        <div class="input-group">
                            <select name="other_status" class="form-control">
                                <option value="">All</option>
                                <option value="0">Draft</option>
                                <option value="-1">Cancelled</option>
                                <option value="50">Done</option>
                            </select>
                        </div>
                    </div>
                </div>
            </form>
            <div id="synced-orders-table">
                <?php
                wp_nonce_field( 'handle_submit', 'pod_nonce' );
                $orders_table = new AFF_Synced_Order();
                $orders_table->prepare_items();
                $orders_table->display();
                ?>
            </div>
        </div>
    </div>
</div>
