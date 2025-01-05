<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Get all webhook IDs from options
$options = get_option('wp_post_upsert_webhooks_settings');
$webhook_ids = array();

if (!empty($options['webhooks'])) {
    foreach ($options['webhooks'] as $webhook) {
        if (!empty($webhook['id'])) {
            $webhook_ids[] = $webhook['id'];
        }
    }
}

// Clean up post meta if we have any webhook IDs
if (!empty($webhook_ids)) {
    global $wpdb;

    // Get all posts that might have webhook meta
    $meta_keys = array();
    foreach ($webhook_ids as $webhook_id) {
        $meta_keys[] = '_last_webhook_key_' . $webhook_id;
        $meta_keys[] = $wpdb->esc_like('_webhook_retry_' . $webhook_id . '_') . '%';
    }

    // Build the meta key pattern for the SQL query
    $meta_key_patterns = array();
    foreach ($meta_keys as $key) {
        $meta_key_patterns[] = $wpdb->prepare('meta_key LIKE %s', $key);
    }
    $meta_key_pattern = implode(' OR ', $meta_key_patterns);

    // Delete all webhook-related post meta directly
    $wpdb->query("
        DELETE FROM {$wpdb->postmeta}
        WHERE {$meta_key_pattern}
    ");
}

// Drop the logs table
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}wp_post_upsert_webhooks_logs");

// Delete all plugin options
delete_option('wp_post_upsert_webhooks_settings');
delete_option('wp_post_upsert_webhooks_db_version');
