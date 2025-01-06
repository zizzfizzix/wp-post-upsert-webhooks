<?php
/**
 * Plugin Name:     Post Upsert Webhooks
 * Plugin URI:      https://github.com/zizzfizzix/wp-post-upsert-webhooks
 * Description:     Sends webhook notifications when posts are created or updated
 * Author:          Kuba Serafinowski
 * Author URI:      https://kuba.wtf
 * Text Domain:     wp-post-upsert-webhooks
 * Domain Path:     /languages
 * Version:         0.2.0
 *
 * @package         Wp_Post_Upsert_Webhooks
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WP_POST_UPSERT_WEBHOOKS_VERSION', '0.2.0');
define('WP_POST_UPSERT_WEBHOOKS_FILE', __FILE__);

// Load plugin textdomain
function wp_post_upsert_webhooks_load_textdomain() {
    load_plugin_textdomain(
        'wp-post-upsert-webhooks',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
}
add_action('init', 'wp_post_upsert_webhooks_load_textdomain');

// Create webhook logs table on plugin activation
function wp_post_upsert_webhooks_install() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wp_post_upsert_webhooks_logs';
    $charset_collate = $wpdb->get_charset_collate();
    $db_version = WP_POST_UPSERT_WEBHOOKS_VERSION;

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        webhook_id char(36) NOT NULL,
        webhook_name varchar(255) NOT NULL,
        webhook_url varchar(2083) NOT NULL,
        post_id bigint(20) NOT NULL,
        event_type varchar(50) NOT NULL,
        status varchar(20) NOT NULL,
        retry_count int(11) NOT NULL DEFAULT 0,
        payload longtext NOT NULL,
        response_code int(11),
        response_body longtext,
        request_headers text,
        response_headers text,
        timestamp datetime NOT NULL,
        idempotency_key varchar(32) NOT NULL,
        PRIMARY KEY  (id),
        KEY webhook_id (webhook_id),
        KEY post_id (post_id),
        KEY status (status),
        KEY timestamp (timestamp)
    ) $charset_collate;";

    dbDelta($sql);

    // Update version
    update_option('wp_post_upsert_webhooks_db_version', $db_version);
}

// Register activation hook
register_activation_hook(__FILE__, 'wp_post_upsert_webhooks_install');

// Check if we need to update
function wp_post_upsert_webhooks_update_check() {
    $current_version = get_option('wp_post_upsert_webhooks_db_version', '0.0.0');
    if (version_compare($current_version, WP_POST_UPSERT_WEBHOOKS_VERSION, '<')) {
        wp_post_upsert_webhooks_install();
    }
}
add_action('plugins_loaded', 'wp_post_upsert_webhooks_update_check');

// Load required files
require_once plugin_dir_path(__FILE__) . 'includes/class-webhook-settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-webhook-handler.php';

// Initialize the plugin
function wp_post_upsert_webhooks_init() {
    $settings = new WP_Post_Upsert_Webhooks_Settings();
    new WP_Post_Upsert_Webhooks_Handler($settings);
}

add_action('plugins_loaded', 'wp_post_upsert_webhooks_init');
