<?php
/**
 * Plugin Name:     WP Post Upsert Webhook
 * Plugin URI:      https://github.com/zizzfizzix/wp-post-upsert-webhook
 * Description:     Sends webhook notifications when posts are created or updated
 * Author:          Kuba Serafinowski
 * Author URI:      https://kuba.wtf
 * Text Domain:     wp-post-upsert-webhook
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Wp_Post_Upsert_Webhook
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WP_POST_UPSERT_WEBHOOK_VERSION', '0.1.0');
define('WP_POST_UPSERT_WEBHOOK_FILE', __FILE__);

// Load required files
require_once plugin_dir_path(__FILE__) . 'includes/class-webhook-settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-webhook-handler.php';

// Initialize the plugin
function wp_post_upsert_webhook_init() {
    $settings = new WP_Post_Upsert_Webhook_Settings();
    new WP_Post_Upsert_Webhook_Handler($settings);
}

add_action('plugins_loaded', 'wp_post_upsert_webhook_init');
