<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * This file is responsible for rendering both:
 * 1. The list of existing webhooks
 * 2. The template for new webhooks (used by JavaScript)
 *
 * It defines the following variables for webhook-template.php:
 * - $webhook: The webhook configuration array
 * - $index: The webhook index (number or '{{INDEX}}' for template)
 * - $is_collapsed: Whether the webhook should be collapsed (optional)
 */
?>
<div class="webhook-grid" id="webhook-endpoints">
    <?php
    // Render existing webhooks
    foreach ($webhooks as $index => $webhook) {
        $is_collapsed = $index !== 0;
        include plugin_dir_path(__FILE__) . 'webhook-template.php';
    }
    ?>
</div>

<div class="webhook-actions">
    <button type="button" class="button" onclick="addWebhookEndpoint()">Add Webhook</button>
</div>

<template id="webhook-template">
    <?php
    // Render template for new webhooks
    $webhook = WP_Post_Upsert_Webhooks_Settings::$default_config;
    $index = '{{INDEX}}';
    $is_collapsed = false;
    include plugin_dir_path(__FILE__) . 'webhook-template.php';
    ?>
</template>
