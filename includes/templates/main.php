<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap">
    <?php if( isset( $_GET['settings-updated'] ) ) { ?>
        <div id="message" class="updated">
            <p><strong><?php _e('Settings saved.') ?></strong></p>
        </div>
    <?php } ?>
    <ul class="nav nav-tabs">
        <?php foreach ($tabs as $tab_slug => $title) { ?>
            <a class="nav-link <?php echo $current_tab === $tab_slug ? esc_attr( 'active' ) : '' ?>"
                href="<?php echo admin_url( 'admin.php?page=fulfillment&tab=' . esc_attr( $tab_slug ) ); ?>">
                <?php echo esc_html( $title ) ?>
            </a>
        <?php } ?>
    </ul>

    <!-- Tab panes -->
    <div class="tab-content">
        <?php include_once plugin_dir_path( __FILE__ ) . $current_tab . '-tab.php'; ?>
    </div>
</div>
