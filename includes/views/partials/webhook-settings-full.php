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
            Enable this webhook
        </label>
    </div>

    <div class="webhook-setting">
        <label>Name:</label>
        <input type="text"
               name="<?php echo $this->option_name; ?>[webhooks][<?php echo $index; ?>][name]"
               value="<?php echo esc_attr($webhook['name']); ?>"
               class="regular-text webhook-name-input"
               placeholder="Webhook name for identification"
               onchange="updateWebhookTitle(this)">
    </div>

    <div class="webhook-setting">
        <label>URL:</label>
        <input type="url"
               name="<?php echo $this->option_name; ?>[webhooks][<?php echo $index; ?>][url]"
               value="<?php echo esc_url($webhook['url']); ?>"
               class="regular-text">
    </div>
</div>
