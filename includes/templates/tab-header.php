<div class="header">
    <img src="<?php echo ( AFF_URL . '/images/adflex-logo.jpg' ); ?>" />
    <div class="right-header float-right text-right">
        <div class="paid-info">
            Current balance: <?php echo isset( $current_balance ) ? $current_balance : 0 ?>
            &nbsp;&nbsp;&nbsp;
            Paid: <?php echo isset( $paid ) ? $paid : 0 ?>
        </div>
        <div class="orders-info">
            <span class= "text-warning">
                Draft orders: <?php echo isset( $synced_orders ) ? $synced_orders : 0 ?>
            </span>
            &nbsp;&nbsp;&nbsp;
            <span class="text-success">
                Processing orders: <?php echo isset( $draft_orders ) ? $draft_orders : 0 ?>
            </span>
        </div>
    </div>
    <div class="clearfix"></div>
</div>
