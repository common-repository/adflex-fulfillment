<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div id="setting" class="tab-pane active">
    <div class="plugin-content">
        <?php include_once plugin_dir_path( __FILE__ ) . 'tab-header.php'; ?>
        <div class="setting-form mt-4">
            <form method="post" action="options.php">
                <?php settings_fields( 'pod-options' ); ?>
                <div class="form-group">
                    <label>API Key:</label>
                    <input type="text" name="pod_api_key" class="mfp-input" readonly
                    data-value="<?php echo get_option('pod_api_key'); ?>"
                    value="<?php echo get_option('pod_api_key'); ?>" />
                </div>
                <div class="setting-btn-group">
                    <button type="button" id="cancel" class="btn btn-light"> Cancel </button>
                    <button type="button" id="edit" class="btn btn-primary"> Edit </button>
                    <button type="submit" id="submit" class="btn btn-success"> Save </button>
                </div>
            </form>
        </div>
    </div>
</div>
