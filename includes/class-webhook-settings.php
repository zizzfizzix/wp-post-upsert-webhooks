<?php

if (!defined('ABSPATH')) {
    exit;
}

class WP_Post_Upsert_Webhooks_Settings {
    private $option_name = 'wp_post_upsert_webhooks_settings';
    private $options;

    public static $default_config = array(
        'id' => '',
        'enabled' => false,
        'name' => '',
        'url' => '',
        'http_method' => 'POST',
        'bearer_token' => '',
        'suppress_duplicates' => true,
        'post_types' => array('post'),
        'post_statuses' => array('publish'),
        'idempotency_fields' => array('title', 'content', 'status', 'slug'),
        'retry_settings' => array(
            'mode' => 'constant',
            'enabled' => true,
            'max_retries' => 3,
            'constant_delay' => array(
                'value' => 5,
                'unit' => 'min'
            ),
            'exponential' => array(
                'multiplier' => 2,
                'base' => 5,
                'jitter' => 5
            )
        )
    );

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        $this->options = get_option($this->option_name);
        $this->ensure_webhook_ids();
    }

    /**
     * Ensures all webhooks have unique IDs
     * This is called on construction and before saving
     */
    private function ensure_webhook_ids() {
        if (!isset($this->options['webhooks']) || !is_array($this->options['webhooks'])) {
            return;
        }

        $modified = false;
        foreach ($this->options['webhooks'] as &$webhook) {
            if (empty($webhook['id'])) {
                $webhook['id'] = wp_generate_uuid4();
                $modified = true;
            }
        }

        if ($modified) {
            update_option($this->option_name, $this->options);
        }
    }

    public function add_admin_menu() {
        // Add main menu
        add_menu_page(
            __('Post Upsert Webhooks', 'wp-post-upsert-webhooks'),
            __('Post Webhooks', 'wp-post-upsert-webhooks'),
            'manage_options',
            'wp-post-upsert-webhooks',
            array($this, 'render_settings_page'),
            'dashicons-rest-api'
        );

        // Add Settings submenu
        add_submenu_page(
            'wp-post-upsert-webhooks',
            __('Webhook Settings', 'wp-post-upsert-webhooks'),
            __('Settings', 'wp-post-upsert-webhooks'),
            'manage_options',
            'wp-post-upsert-webhooks',
            array($this, 'render_settings_page')
        );

        // Add Logs submenu
        add_submenu_page(
            'wp-post-upsert-webhooks',
            __('Webhook Logs', 'wp-post-upsert-webhooks'),
            __('Logs', 'wp-post-upsert-webhooks'),
            'manage_options',
            'wp-post-upsert-webhooks-logs',
            array($this, 'render_logs_page')
        );
    }

    public function render_logs_page() {
        require plugin_dir_path(WP_POST_UPSERT_WEBHOOKS_FILE) . 'includes/views/webhook-logs.php';
    }

    public function enqueue_admin_assets($hook) {
        $allowed_hooks = array(
            'toplevel_page_wp-post-upsert-webhooks',
            'post-webhooks_page_wp-post-upsert-webhooks-logs'
        );

        if (!in_array($hook, $allowed_hooks)) {
            return;
        }

        if ($hook === 'toplevel_page_wp-post-upsert-webhooks') {
            // Settings page assets
            wp_enqueue_style(
                'wp-post-upsert-webhooks-settings',
                plugins_url('assets/css/settings.css', WP_POST_UPSERT_WEBHOOKS_FILE),
                array(),
                WP_POST_UPSERT_WEBHOOKS_VERSION
            );

            wp_enqueue_script(
                'wp-post-upsert-webhooks-settings',
                plugins_url('assets/js/settings.js', WP_POST_UPSERT_WEBHOOKS_FILE),
                array(),
                WP_POST_UPSERT_WEBHOOKS_VERSION,
                true
            );
        } else {
            // Logs page assets
            wp_enqueue_style(
                'wp-post-upsert-webhooks-logs',
                plugins_url('assets/css/logs.css', WP_POST_UPSERT_WEBHOOKS_FILE),
                array(),
                WP_POST_UPSERT_WEBHOOKS_VERSION
            );

            wp_enqueue_script(
                'wp-post-upsert-webhooks-logs',
                plugins_url('assets/js/logs.js', WP_POST_UPSERT_WEBHOOKS_FILE),
                array(),
                WP_POST_UPSERT_WEBHOOKS_VERSION,
                true
            );
        }
    }

    public function get_options() {
        return $this->options;
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        require_once plugin_dir_path(WP_POST_UPSERT_WEBHOOKS_FILE) . 'includes/views/settings-page.php';
    }

    public function register_settings() {
        register_setting($this->option_name, $this->option_name, array($this, 'sanitize_settings'));

        add_settings_section(
            'webhook_endpoints_section',
            __('Webhook Endpoints', 'wp-post-upsert-webhooks'),
            array($this, 'webhook_endpoints_section_callback'),
            'wp-post-upsert-webhooks'
        );
    }

    public function webhook_endpoints_section_callback() {
        $webhooks = isset($this->options['webhooks']) ? $this->options['webhooks'] : array();

        if (empty($webhooks)) {
            $webhooks = array(self::$default_config);
        }

        usort($webhooks, function($a, $b) {
            if (!empty($a['enabled']) && empty($b['enabled'])) return -1;
            if (empty($a['enabled']) && !empty($b['enabled'])) return 1;
            $name_a = empty($a['name']) ? __('Unnamed Webhook', 'wp-post-upsert-webhooks') : $a['name'];
            $name_b = empty($b['name']) ? __('Unnamed Webhook', 'wp-post-upsert-webhooks') : $b['name'];
            return strcasecmp($name_a, $name_b);
        });

        require plugin_dir_path(WP_POST_UPSERT_WEBHOOKS_FILE) . 'includes/views/webhooks-section.php';
    }

    public function sanitize_settings($input) {
        $old_webhooks = isset($this->options['webhooks']) ? $this->options['webhooks'] : array();
        $old_webhook_ids = array();

        // Get all old webhook IDs
        foreach ($old_webhooks as $webhook) {
            if (!empty($webhook['id'])) {
                $old_webhook_ids[] = $webhook['id'];
            }
        }

        if (!is_array($input) || !isset($input['webhooks'])) {
            // All webhooks were deleted, clean up meta for all old webhooks
            if (!empty($old_webhook_ids)) {
                $this->cleanup_webhook_meta($old_webhook_ids);
            }
            return array('webhooks' => array(array_merge(
                self::$default_config,
                array('id' => wp_generate_uuid4())
            )));
        }

        $sanitized = array();
        $sanitized['webhooks'] = array();
        $new_webhook_ids = array();

        foreach ($input['webhooks'] as $index => $webhook) {
            // Preserve the ID if this webhook exists at the same position
            $webhook_id = isset($old_webhooks[$index]['id']) ? $old_webhooks[$index]['id'] : wp_generate_uuid4();
            $new_webhook_ids[] = $webhook_id;

            $sanitized['webhooks'][] = array(
                'id' => $webhook_id,
                'enabled' => !empty($webhook['enabled']),
                'name' => sanitize_text_field($webhook['name']),
                'url' => esc_url_raw($webhook['url']),
                'http_method' => in_array($webhook['http_method'], array('POST', 'GET')) ? $webhook['http_method'] : self::$default_config['http_method'],
                'bearer_token' => sanitize_text_field($webhook['bearer_token']),
                'suppress_duplicates' => !empty($webhook['suppress_duplicates']),
                'post_types' => isset($webhook['post_types']) ? array_map('sanitize_text_field', $webhook['post_types']) : self::$default_config['post_types'],
                'post_statuses' => isset($webhook['post_statuses']) ? array_map('sanitize_text_field', $webhook['post_statuses']) : self::$default_config['post_statuses'],
                'idempotency_fields' => isset($webhook['idempotency_fields']) ? array_map('sanitize_text_field', $webhook['idempotency_fields']) : self::$default_config['idempotency_fields'],
                'retry_settings' => array(
                    'mode' => in_array($webhook['retry_settings']['mode'], array('disabled', 'constant', 'exponential'))
                        ? $webhook['retry_settings']['mode']
                        : 'disabled',
                    'enabled' => isset($webhook['retry_settings']['mode']) && $webhook['retry_settings']['mode'] !== 'disabled',
                    'max_retries' => min(10, max(1, intval($webhook['retry_settings']['max_retries']))),
                    'constant_delay' => array(
                        'value' => max(0, intval($webhook['retry_settings']['constant_delay']['value'] ?? 5)),
                        'unit' => in_array($webhook['retry_settings']['constant_delay']['unit'], array('ms', 'sec', 'min', 'hour', 'day'))
                            ? $webhook['retry_settings']['constant_delay']['unit']
                            : 'sec'
                    ),
                    'exponential' => array(
                        'multiplier' => max(1, floatval($webhook['retry_settings']['exponential']['multiplier'] ?? 2)),
                        'base' => max(1, intval($webhook['retry_settings']['exponential']['base'] ?? 5)),
                        'jitter' => min(50, max(0, intval($webhook['retry_settings']['exponential']['jitter'] ?? 5)))
                    )
                )
            );
        }

        // Clean up meta for webhooks that were deleted
        $webhooks_to_cleanup = array_diff($old_webhook_ids, $new_webhook_ids);
        if (!empty($webhooks_to_cleanup)) {
            $this->cleanup_webhook_meta($webhooks_to_cleanup);
        }

        return $sanitized;
    }

    /**
     * Cleans up webhook-related post meta for given webhook IDs
     * @param array $webhook_ids Array of webhook IDs to clean up
     */
    private function cleanup_webhook_meta($webhook_ids) {
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

        // Get all post IDs with webhook meta, using index hint for performance
        $post_ids = $wpdb->get_col("
            SELECT DISTINCT post_id
            FROM {$wpdb->postmeta} USE INDEX (meta_key)
            WHERE {$meta_key_pattern}
        ");

        if (empty($post_ids)) {
            return;
        }

        // Delete the meta for each post
        foreach ($post_ids as $post_id) {
            foreach ($webhook_ids as $webhook_id) {
                delete_post_meta($post_id, '_last_webhook_key_' . $webhook_id);

                // Get and delete all retry meta keys for this webhook
                $retry_meta_keys = $wpdb->get_col($wpdb->prepare("
                    SELECT meta_key
                    FROM {$wpdb->postmeta} USE INDEX (meta_key)
                    WHERE post_id = %d
                    AND meta_key LIKE %s",
                    $post_id,
                    '_webhook_retry_' . $webhook_id . '_%'
                ));

                foreach ($retry_meta_keys as $retry_meta_key) {
                    delete_post_meta($post_id, $retry_meta_key);
                }
            }
        }
    }
}
