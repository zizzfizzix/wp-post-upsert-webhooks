<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="webhook-header" onclick="toggleWebhook(this)">
    <h3>
        <span class="webhook-status <?php echo !empty($webhook['enabled']) ? 'enabled' : 'disabled'; ?>"></span>
        <span class="webhook-title"><?php echo !empty($webhook['name']) ? esc_html($webhook['name']) : esc_html__('Unnamed Webhook', 'wp-post-upsert-webhooks'); ?></span>
        <?php if (!empty($webhook['id'])): ?>
            <span class="webhook-id" title="<?php esc_attr_e('Webhook ID', 'wp-post-upsert-webhooks'); ?>">(<?php echo esc_html($webhook['id']); ?>)</span>
        <?php endif; ?>
    </h3>
    <div class="webhook-header-actions">
        <button type="button" class="button button-link-delete" onclick="event.stopPropagation(); removeWebhookEndpoint(this)"><?php esc_html_e('Remove', 'wp-post-upsert-webhooks'); ?></button>
        <span class="collapse-indicator<?php echo isset($is_collapsed) && $is_collapsed ? ' rotated' : ''; ?>">▼</span>
    </div>
</div>
