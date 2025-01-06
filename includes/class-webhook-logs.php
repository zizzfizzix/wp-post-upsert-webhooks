<?php

if (!defined('ABSPATH')) {
    exit;
}

class WP_Post_Upsert_Webhooks_Logs {
    private $settings;

    public function __construct($settings) {
        $this->settings = $settings;
        add_action('wp_post_upsert_webhooks_cleanup_logs', array($this, 'cleanup_logs'));

        // Schedule daily cleanup if not already scheduled
        if (!wp_next_scheduled('wp_post_upsert_webhooks_cleanup_logs')) {
            wp_schedule_event(time(), 'daily', 'wp_post_upsert_webhooks_cleanup_logs');
        }
    }

    /**
     * Clean up old logs based on retention settings
     */
    public function cleanup_logs() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wp_post_upsert_webhooks_logs';
        $webhooks = $this->settings->get_webhooks();

        foreach ($webhooks as $webhook) {
            if (empty($webhook['id'])) {
                continue;
            }

            // Skip if retention is disabled
            if (empty($webhook['log_retention']['enabled'])) {
                continue;
            }

            $days = isset($webhook['log_retention']['days']) ? max(1, intval($webhook['log_retention']['days'])) : 90;
            $max_rows = isset($webhook['log_retention']['max_rows']) ? max(1, intval($webhook['log_retention']['max_rows'])) : 1000;

            // Always keep at least one row per webhook
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE webhook_id = %s",
                $webhook['id']
            ));

            if ($count <= 1) {
                continue;
            }

            // Delete old logs based on retention days
            $cutoff_date = gmdate('Y-m-d H:i:s', strtotime("-$days days"));
            $wpdb->query($wpdb->prepare(
                "DELETE FROM $table_name
                WHERE webhook_id = %s
                AND timestamp < %s
                AND id NOT IN (
                    SELECT id FROM (
                        SELECT id FROM $table_name
                        WHERE webhook_id = %s
                        ORDER BY timestamp DESC
                        LIMIT 1
                    ) t
                )",
                $webhook['id'],
                $cutoff_date,
                $webhook['id']
            ));

            // Delete excess rows while keeping the most recent ones
            $wpdb->query($wpdb->prepare(
                "DELETE FROM $table_name
                WHERE webhook_id = %s
                AND id NOT IN (
                    SELECT id FROM (
                        SELECT id FROM $table_name
                        WHERE webhook_id = %s
                        ORDER BY timestamp DESC
                        LIMIT %d
                    ) t
                )",
                $webhook['id'],
                $webhook['id'],
                $max_rows
            ));
        }
    }

    /**
     * Clean up the scheduled event on plugin deactivation
     */
    public static function deactivate() {
        wp_clear_scheduled_hook('wp_post_upsert_webhooks_cleanup_logs');
    }
}
