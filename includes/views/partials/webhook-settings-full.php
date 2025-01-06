<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="webhook-settings-full">
    <div class="webhook-setting">
        <label>
            <input type="checkbox"
                   name="<?php echo $this->option_name; ?>[webhooks][<?php echo $index; ?>][enabled]"
                   value="1"
                   <?php checked(!empty($webhook['enabled'])); ?>>
            <?php esc_html_e('Enable this webhook', 'wp-post-upsert-webhooks'); ?>
        </label>
    </div>

    <div class="webhook-setting">
        <label><?php esc_html_e('Name:', 'wp-post-upsert-webhooks'); ?></label>
        <input type="text"
               name="<?php echo $this->option_name; ?>[webhooks][<?php echo $index; ?>][name]"
               value="<?php echo esc_attr($webhook['name']); ?>"
               class="regular-text webhook-name-input"
               placeholder="<?php esc_attr_e('Webhook name for identification', 'wp-post-upsert-webhooks'); ?>"
               onchange="updateWebhookTitle(this)">
    </div>

    <div class="webhook-setting">
        <label><?php esc_html_e('URL:', 'wp-post-upsert-webhooks'); ?></label>
        <input type="url"
               name="<?php echo $this->option_name; ?>[webhooks][<?php echo $index; ?>][url]"
               value="<?php echo esc_url($webhook['url']); ?>"
               class="regular-text">
    </div>
</div>
