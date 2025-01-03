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

class WP_Post_Upsert_Webhook {
    private static $instance = null;
    private $options;
    private $option_name = 'wp_post_upsert_webhook_settings';

    private static $default_config = array(
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
            'enabled' => true,
            'max_retries' => 3,
            'delays' => array(30, 300, 3600)
        )
    );

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('transition_post_status', array($this, 'handle_post_event'), 10, 3);
        $this->options = get_option($this->option_name);
    }

    public function add_settings_page() {
        add_options_page(
            'Post Webhook Settings',
            'Post Webhook',
            'manage_options',
            'wp-post-upsert-webhook',
            array($this, 'render_settings_page')
        );
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields($this->option_name);
                do_settings_sections('wp-post-upsert-webhook');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function register_settings() {
        register_setting($this->option_name, $this->option_name, array($this, 'sanitize_settings'));

        add_settings_section(
            'webhook_endpoints_section',
            'Webhook Endpoints',
            array($this, 'webhook_endpoints_section_callback'),
            'wp-post-upsert-webhook'
        );

        // We'll handle the dynamic fields in the section callback
    }

    public function webhook_endpoints_section_callback() {
        $webhooks = isset($this->options['webhooks']) ? $this->options['webhooks'] : array();

        // Add a default empty webhook if none exist
        if (empty($webhooks)) {
            $webhooks = array($this->get_default_webhook_config());
        }

        echo '<div id="webhook-endpoints">';
        foreach ($webhooks as $index => $webhook) {
            $this->render_webhook_config($index, $webhook);
        }
        echo '</div>';

        echo '<div class="webhook-actions" style="margin-top: 15px;">';
        echo '<button type="button" class="button" onclick="addWebhookEndpoint()">Add Another Webhook</button>';
        echo '</div>';

        $this->render_webhook_template();
    }

    private function get_default_webhook_config() {
        return self::$default_config;
    }

    private function render_webhook_config($index, $webhook) {
        // Ensure all required fields have default values
        $webhook = wp_parse_args($webhook, self::$default_config);

        $base_name = $this->option_name . '[webhooks][' . $index . ']';
        ?>
        <div class="webhook-endpoint" style="background: #fff; padding: 15px; margin: 10px 0; border: 1px solid #ccc;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                <h3 style="margin: 0;">Webhook Configuration</h3>
                <button type="button" class="button button-link-delete" onclick="removeWebhookEndpoint(this)">Remove</button>
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px;">
                    <input type="checkbox"
                           name="<?php echo $base_name; ?>[enabled]"
                           value="1"
                           <?php checked(!empty($webhook['enabled'])); ?>>
                    Enable this webhook
                </label>
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px;">Name:</label>
                <input type="text"
                       name="<?php echo $base_name; ?>[name]"
                       value="<?php echo esc_attr($webhook['name']); ?>"
                       class="regular-text"
                       placeholder="Webhook name for identification">
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px;">URL:</label>
                <input type="url"
                       name="<?php echo $base_name; ?>[url]"
                       value="<?php echo esc_url($webhook['url']); ?>"
                       class="regular-text">
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px;">HTTP Method:</label>
                <select name="<?php echo $base_name; ?>[http_method]">
                    <option value="POST" <?php selected($webhook['http_method'], 'POST'); ?>>POST</option>
                    <option value="GET" <?php selected($webhook['http_method'], 'GET'); ?>>GET</option>
                </select>
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px;">Bearer Token:</label>
                <div style="display: flex; gap: 10px;">
                    <input type="password"
                           name="<?php echo $base_name; ?>[bearer_token]"
                           value="<?php echo esc_attr($webhook['bearer_token']); ?>"
                           class="regular-text">
                    <button type="button" class="button" onclick="toggleTokenVisibility(this)">Show</button>
                </div>
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px;">
                    <input type="checkbox"
                           name="<?php echo $base_name; ?>[suppress_duplicates]"
                           value="1"
                           <?php checked(!empty($webhook['suppress_duplicates'])); ?>>
                    Suppress duplicate updates
                </label>
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px;">Post Types:</label>
                <?php
                $post_types = get_post_types(array('public' => true), 'objects');
                foreach ($post_types as $post_type) {
                    $checked = in_array($post_type->name, $webhook['post_types']) ? 'checked' : '';
                    echo '<label style="display: block; margin-bottom: 5px;">';
                    echo '<input type="checkbox" name="' . $base_name . '[post_types][]" value="' . esc_attr($post_type->name) . '" ' . $checked . '>';
                    echo ' ' . esc_html($post_type->labels->singular_name);
                    echo '</label>';
                }
                ?>
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px;">Post Statuses:</label>
                <?php
                $statuses = array('publish', 'pending', 'draft', 'private');
                foreach ($statuses as $status) {
                    $checked = in_array($status, $webhook['post_statuses']) ? 'checked' : '';
                    echo '<label style="display: block; margin-bottom: 5px;">';
                    echo '<input type="checkbox" name="' . $base_name . '[post_statuses][]" value="' . $status . '" ' . $checked . '>';
                    echo ' ' . ucfirst($status);
                    echo '</label>';
                }
                ?>
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px;">Idempotency Fields:</label>
                <?php
                $fields = array(
                    'title' => 'Title',
                    'content' => 'Content',
                    'excerpt' => 'Excerpt',
                    'status' => 'Status',
                    'slug' => 'Slug',
                    'event_type' => 'Event Type',
                    'categories' => 'Categories',
                    'tags' => 'Tags',
                    'author' => 'Author'
                );
                foreach ($fields as $field => $label) {
                    $checked = in_array($field, $webhook['idempotency_fields']) ? 'checked' : '';
                    echo '<label style="display: block; margin-bottom: 5px;">';
                    echo '<input type="checkbox" name="' . $base_name . '[idempotency_fields][]" value="' . $field . '" ' . $checked . '>';
                    echo ' ' . $label;
                    echo '</label>';
                }
                ?>
            </div>

            <div class="retry-settings" style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px;">
                    <input type="checkbox"
                           name="<?php echo $base_name; ?>[retry_settings][enabled]"
                           value="1"
                           <?php checked(!empty($webhook['retry_settings']['enabled'])); ?>>
                    Enable automatic retries
                </label>

                <div style="margin-top: 10px;">
                    <label style="display: block; margin-bottom: 5px;">Maximum retry attempts:</label>
                    <input type="number"
                           name="<?php echo $base_name; ?>[retry_settings][max_retries]"
                           value="<?php echo esc_attr($webhook['retry_settings']['max_retries']); ?>"
                           min="1"
                           max="10"
                           class="small-text">
                </div>

                <div style="margin-top: 10px;">
                    <label style="display: block; margin-bottom: 5px;">Retry delays (seconds):</label>
                    <div style="display: flex; gap: 10px;">
                        <?php foreach ($webhook['retry_settings']['delays'] as $index => $delay) : ?>
                            <div>
                                <label>Attempt <?php echo $index + 1; ?>:</label>
                                <input type="number"
                                       name="<?php echo $base_name; ?>[retry_settings][delays][]"
                                       value="<?php echo esc_attr($delay); ?>"
                                       min="1"
                                       class="small-text">
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_webhook_template() {
        ?>
        <template id="webhook-template">
            <?php $this->render_webhook_config('{{INDEX}}', $this->get_default_webhook_config()); ?>
        </template>

        <script>
        function addWebhookEndpoint() {
            const container = document.getElementById('webhook-endpoints');
            const template = document.getElementById('webhook-template');
            const index = container.children.length;
            const newEndpoint = template.content.cloneNode(true);

            // Replace placeholder index
            newEndpoint.querySelectorAll('[name*="{{INDEX}}"]').forEach(el => {
                el.name = el.name.replace('{{INDEX}}', index);
            });

            container.appendChild(newEndpoint);
        }

        function removeWebhookEndpoint(button) {
            const endpoint = button.closest('.webhook-endpoint');
            if (document.querySelectorAll('.webhook-endpoint').length > 1) {
                endpoint.remove();
            } else {
                alert('At least one webhook configuration must remain.');
            }
        }

        function toggleTokenVisibility(button) {
            const input = button.previousElementSibling;
            if (input.type === 'password') {
                input.type = 'text';
                button.textContent = 'Hide';
            } else {
                input.type = 'password';
                button.textContent = 'Show';
            }
        }
        </script>
        <?php
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
                    'enabled' => !empty($webhook['retry_settings']['enabled']),
                    'max_retries' => min(10, max(1, intval($webhook['retry_settings']['max_retries']))),
                    'delays' => isset($webhook['retry_settings']['delays'])
                        ? array_map('intval', array_slice($webhook['retry_settings']['delays'], 0, 3))
                        : self::$default_config['retry_settings']['delays']
                )
            );
        }

        return $sanitized;
    }

    public function handle_post_event($new_status, $old_status, $post) {
        // Skip if it's a revision
        if (wp_is_post_revision($post->ID)) {
            return;
        }

        // Skip if it's an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        $webhooks = isset($this->options['webhooks']) ? $this->options['webhooks'] : array();

        foreach ($webhooks as $webhook) {
            // Skip disabled webhooks
            if (empty($webhook['enabled']) || empty($webhook['url'])) {
                continue;
            }

            // Skip if post type is not monitored
            if (!in_array($post->post_type, $webhook['post_types'])) {
                continue;
            }

            // Get monitored statuses for this webhook
            $monitored_statuses = isset($webhook['post_statuses']) ? $webhook['post_statuses'] : array();

            // Determine the type of event
            $is_status_change = ($old_status !== $new_status);
            $is_entering_monitored = in_array($new_status, $monitored_statuses);
            $is_leaving_monitored = in_array($old_status, $monitored_statuses);
            $is_monitored_update = in_array($new_status, $monitored_statuses) && !$is_status_change;

            // Skip if none of our conditions are met
            if (!$is_entering_monitored && !$is_leaving_monitored && !$is_monitored_update) {
                continue;
            }

            // Set the event type
            $event_type = $is_status_change ? 'post.status_changed' : ($old_status === 'new' ? 'post.created' : 'post.updated');

            // Check if we should suppress this webhook
            if (!empty($webhook['suppress_duplicates']) && $this->should_suppress_webhook($post, $event_type, $webhook)) {
                continue;
            }

            // Prepare webhook data
            $data = $this->prepare_webhook_data($post, $event_type, $webhook);

            // Set appropriate event type and transition info
            if ($is_status_change) {
                $data['status_transition'] = array(
                    'from' => $old_status,
                    'to' => $new_status
                );
            }

            $this->send_webhook($data, $webhook);
        }
    }

    private function should_suppress_webhook($post, $event_type, $webhook) {
        // Get the current idempotency key
        $current_key = $this->get_stable_idempotency_key($post, $event_type, $webhook);

        // Get the last successful webhook key
        $last_key = get_post_meta($post->ID, '_last_successful_webhook_key_' . md5($webhook['url']), true);

        // If the keys match, suppress the webhook
        return $current_key === $last_key;
    }

    private function get_content_hash($post, $webhook) {
        $fields = isset($webhook['idempotency_fields']) ? $webhook['idempotency_fields'] : self::$default_config['idempotency_fields'];
        $hash_parts = array();

        foreach ($fields as $field) {
            switch ($field) {
                case 'title':
                    $hash_parts[] = $post->post_title;
                    break;
                case 'content':
                    $hash_parts[] = $post->post_content;
                    break;
                case 'excerpt':
                    $hash_parts[] = $post->post_excerpt;
                    break;
                case 'status':
                    $hash_parts[] = $post->post_status;
                    break;
                case 'slug':
                    $hash_parts[] = $post->post_name;
                    break;
                case 'categories':
                    $hash_parts[] = implode(',', wp_get_post_categories($post->ID, array('fields' => 'names')));
                    break;
                case 'tags':
                    $hash_parts[] = implode(',', wp_get_post_tags($post->ID, array('fields' => 'names')));
                    break;
                case 'author':
                    $hash_parts[] = $post->post_author;
                    break;
            }
        }

        return md5(implode(':', $hash_parts));
    }

    private function get_stable_idempotency_key($post, $event_type, $webhook) {
        $fields = isset($webhook['idempotency_fields']) ? $webhook['idempotency_fields'] : self::$default_config['idempotency_fields'];

        $key_parts = array(
            $post->ID,
            $this->get_content_hash($post, $webhook),
            md5($webhook['url']) // Include webhook URL in the key to make it unique per endpoint
        );

        // Add event_type to key if selected
        if (in_array('event_type', $fields)) {
            $key_parts[] = $event_type;
        }

        return md5(implode(':', $key_parts));
    }

    private function prepare_webhook_data($post, $event_type, $webhook) {
        $author = get_user_by('id', $post->post_author);
        $categories = wp_get_post_categories($post->ID, array('fields' => 'names'));
        $tags = wp_get_post_tags($post->ID, array('fields' => 'names'));

        return array(
            'event_type' => $event_type,
            'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
            'idempotency_key' => $this->get_stable_idempotency_key($post, $event_type, $webhook),
            'webhook_name' => $webhook['name'],
            'post' => array(
                'id' => $post->ID,
                'title' => $post->post_title,
                'content' => $post->post_content,
                'excerpt' => $post->post_excerpt,
                'status' => $post->post_status,
                'slug' => $post->post_name,
                'permalink' => get_permalink($post->ID),
                'date_created' => $post->post_date_gmt,
                'date_modified' => $post->post_modified_gmt,
                'author' => array(
                    'id' => $author->ID,
                    'name' => $author->display_name,
                    'email' => $author->user_email
                ),
                'categories' => $categories,
                'tags' => $tags,
                'meta' => get_post_meta($post->ID)
            )
        );
    }

    private function send_webhook($data, $webhook) {
        $headers = array(
            'Content-Type' => 'application/json',
            'X-WordPress-Webhook' => 'post-upsert',
            'Idempotency-Key' => $data['idempotency_key']
        );

        // Add Bearer token if configured
        if (!empty($webhook['bearer_token'])) {
            $headers['Authorization'] = 'Bearer ' . $webhook['bearer_token'];
        }

        $args = array(
            'headers' => $headers,
            'timeout' => 15
        );

        // Get retry settings
        $retry_settings = $webhook['retry_settings'];

        // Try to get existing retry count
        $retry_meta_key = '_webhook_retry_' . md5($webhook['url']) . '_' . $data['idempotency_key'];
        $retry_count = (int)get_post_meta($data['post']['id'], $retry_meta_key, true);

        // Check if we should stop retrying
        if ($retry_count >= $retry_settings['max_retries']) {
            error_log(sprintf('Maximum retry attempts reached for webhook %s: %s', $webhook['name'], $data['idempotency_key']));
            delete_post_meta($data['post']['id'], $retry_meta_key);
            return;
        }

        if ($webhook['http_method'] === 'POST') {
            $args['body'] = wp_json_encode($data);
            $response = wp_remote_post($webhook['url'], $args);
        } else {
            // For GET requests, add data as query parameters
            $url = add_query_arg(array('data' => base64_encode(wp_json_encode($data))), $webhook['url']);
            $response = wp_remote_get($url, $args);
        }

        if (is_wp_error($response)) {
            error_log(sprintf('Webhook %s sending failed: %s', $webhook['name'], $response->get_error_message()));
            if (!empty($retry_settings['enabled'])) {
                $this->schedule_retry($data, $webhook, $retry_count);
            }
            return;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code < 200 || $response_code >= 300) {
            error_log(sprintf('Webhook %s failed with HTTP %d: %s', $webhook['name'], $response_code, wp_remote_retrieve_body($response)));
            if (!empty($retry_settings['enabled'])) {
                $this->schedule_retry($data, $webhook, $retry_count);
            }
            return;
        }

        // Success - clean up retry metadata and store the successful webhook key
        delete_post_meta($data['post']['id'], $retry_meta_key);
        update_post_meta($data['post']['id'], '_last_successful_webhook_key_' . md5($webhook['url']), $data['idempotency_key']);
    }

    private function schedule_retry($data, $webhook, $retry_count) {
        $retry_meta_key = '_webhook_retry_' . md5($webhook['url']) . '_' . $data['idempotency_key'];
        $next_retry = $retry_count + 1;

        if ($next_retry <= count($webhook['retry_settings']['delays'])) {
            update_post_meta($data['post']['id'], $retry_meta_key, $next_retry);

            wp_schedule_single_event(
                time() + $webhook['retry_settings']['delays'][$retry_count],
                'wp_post_upsert_webhook_retry',
                array($data, $webhook)
            );
        }
    }

    public function retry_webhook($data, $webhook) {
        $this->send_webhook($data, $webhook);
    }

    public static function init_retry_hook() {
        add_action('wp_post_upsert_webhook_retry', array(self::get_instance(), 'retry_webhook'), 10, 2);
    }
}

// Initialize the plugin and retry hooks
add_action('plugins_loaded', array('WP_Post_Upsert_Webhook', 'get_instance'));
add_action('plugins_loaded', array('WP_Post_Upsert_Webhook', 'init_retry_hook'));
