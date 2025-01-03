<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="webhook-header" onclick="toggleWebhook(this)">
    <h3>
        <span class="webhook-status <?php echo !empty($webhook['enabled']) ? 'enabled' : 'disabled'; ?>"></span>
        <span class="webhook-title"><?php echo !empty($webhook['name']) ? esc_html($webhook['name']) : 'Unnamed Webhook'; ?></span>
    </h3>
    <div class="webhook-header-actions">
        <button type="button" class="button button-link-delete" onclick="event.stopPropagation(); removeWebhookEndpoint(this)">Remove</button>
        <span class="collapse-indicator<?php echo isset($is_collapsed) && $is_collapsed ? ' rotated' : ''; ?>">▼</span>
    </div>
</div>
