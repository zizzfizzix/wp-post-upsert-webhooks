<?php
if (!defined('ABSPATH')) {
    exit;
}

// Expects $webhook and $index to be defined by the including file
?>
<div class="webhook-endpoint">
    <?php include plugin_dir_path(__FILE__) . 'partials/webhook-header.php'; ?>
    <div class="webhook-content<?php echo isset($is_collapsed) && $is_collapsed ? ' collapsed' : ''; ?>">
        <div class="webhook-settings-grid">
            <?php
            include plugin_dir_path(__FILE__) . 'partials/webhook-settings-full.php';
            include plugin_dir_path(__FILE__) . 'partials/webhook-settings-left.php';
            include plugin_dir_path(__FILE__) . 'partials/webhook-settings-right.php';
            ?>
        </div>
    </div>
</div>
