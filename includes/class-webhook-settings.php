<?php

if (!defined('ABSPATH')) {
    exit;
}

class WP_Post_Upsert_Webhooks_Settings {
    private $option_name = 'wp_post_upsert_webhooks_settings';
    private $options;

    public static $default_config = array(
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
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        $this->options = get_option($this->option_name);
    }

    public function enqueue_admin_assets($hook) {
        if ('settings_page_wp-post-upsert-webhook' !== $hook) {
            return;
        }

        wp_enqueue_style(
            'wp-post-upsert-webhook-admin',
            plugins_url('assets/css/admin.css', WP_POST_UPSERT_WEBHOOKS_FILE),
            array(),
            WP_POST_UPSERT_WEBHOOKS_VERSION
        );

        wp_enqueue_script(
            'wp-post-upsert-webhook-admin',
            plugins_url('assets/js/admin.js', WP_POST_UPSERT_WEBHOOKS_FILE),
            array('jquery'),
            WP_POST_UPSERT_WEBHOOKS_VERSION,
            true
        );
    }

    public function add_settings_page() {
        add_options_page(
            'Post Upsert Webhooks Settings',
            'Post Upsert Webhooks',
            'manage_options',
            'wp-post-upsert-webhook',
            array($this, 'render_settings_page')
        );
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
            'Webhook Endpoints',
            array($this, 'webhook_endpoints_section_callback'),
            'wp-post-upsert-webhook'
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
            $name_a = empty($a['name']) ? 'Unnamed Webhook' : $a['name'];
            $name_b = empty($b['name']) ? 'Unnamed Webhook' : $b['name'];
            return strcasecmp($name_a, $name_b);
        });

        require plugin_dir_path(WP_POST_UPSERT_WEBHOOKS_FILE) . 'includes/views/webhooks-section.php';
    }

    public function sanitize_settings($input) {
        if (!is_array($input) || !isset($input['webhooks'])) {
            return array('webhooks' => array(self::$default_config));
        }

        $sanitized = array();
        $sanitized['webhooks'] = array();

        foreach ($input['webhooks'] as $webhook) {
            $sanitized['webhooks'][] = array(
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

        return $sanitized;
    }
}
