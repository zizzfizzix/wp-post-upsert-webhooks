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

    private function get_content_hash($post) {
        $fields = isset($this->options['idempotency_fields']) ? $this->options['idempotency_fields'] : array('title', 'content', 'status', 'event_type');
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

    private function get_stable_idempotency_key($post, $event_type) {
        $fields = isset($this->options['idempotency_fields']) ? $this->options['idempotency_fields'] : array('title', 'content', 'status', 'event_type');

        $key_parts = array(
            $post->ID,
            $this->get_content_hash($post)
        );

        // Add event_type to key if selected
        if (in_array('event_type', $fields)) {
            $key_parts[] = $event_type;
        }

        return md5(implode(':', $key_parts));
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

    public function register_settings() {
        register_setting($this->option_name, $this->option_name);

        add_settings_section(
            'webhook_settings_section',
            'Webhook Configuration',
            array($this, 'settings_section_callback'),
            'wp-post-upsert-webhook'
        );

        add_settings_field(
            'webhook_url',
            'Webhook URL',
            array($this, 'webhook_url_callback'),
            'wp-post-upsert-webhook',
            'webhook_settings_section'
        );

        add_settings_field(
            'http_method',
            'HTTP Method',
            array($this, 'http_method_callback'),
            'wp-post-upsert-webhook',
            'webhook_settings_section'
        );

        add_settings_field(
            'bearer_token',
            'Bearer Token',
            array($this, 'bearer_token_callback'),
            'wp-post-upsert-webhook',
            'webhook_settings_section'
        );

        add_settings_field(
            'suppress_duplicates',
            'Suppress Duplicate Updates',
            array($this, 'suppress_duplicates_callback'),
            'wp-post-upsert-webhook',
            'webhook_settings_section'
        );

        add_settings_field(
            'idempotency_fields',
            'Idempotency Key Fields',
            array($this, 'idempotency_fields_callback'),
            'wp-post-upsert-webhook',
            'webhook_settings_section'
        );

        add_settings_field(
            'post_statuses',
            'Post Statuses',
            array($this, 'post_statuses_callback'),
            'wp-post-upsert-webhook',
            'webhook_settings_section'
        );

        add_settings_field(
            'retry_settings',
            'Retry Settings',
            array($this, 'retry_settings_callback'),
            'wp-post-upsert-webhook',
            'webhook_settings_section'
        );
    }

    public function settings_section_callback() {
        echo '<p>Configure the webhook URL, authentication, and which post statuses should trigger notifications.</p>';
    }

    public function webhook_url_callback() {
        $value = isset($this->options['webhook_url']) ? esc_url($this->options['webhook_url']) : '';
        echo '<input type="url" id="webhook_url" name="' . $this->option_name . '[webhook_url]" value="' . $value . '" class="regular-text">';
    }

    public function http_method_callback() {
        $value = isset($this->options['http_method']) ? $this->options['http_method'] : 'POST';
        ?>
        <select name="<?php echo $this->option_name; ?>[http_method]" id="http_method">
            <option value="POST" <?php selected($value, 'POST'); ?>>POST</option>
            <option value="GET" <?php selected($value, 'GET'); ?>>GET</option>
        </select>
        <?php
    }

    public function bearer_token_callback() {
        $value = isset($this->options['bearer_token']) ? $this->options['bearer_token'] : '';
        ?>
        <div class="token-input-wrapper" style="display: flex; align-items: center; gap: 10px;">
            <input type="password"
                   id="bearer_token"
                   name="<?php echo $this->option_name; ?>[bearer_token]"
                   value="<?php echo esc_attr($value); ?>"
                   class="regular-text">
            <button type="button"
                    class="button"
                    onclick="toggleTokenVisibility()"
                    id="toggle_token_btn">Show</button>
        </div>
        <p class="description">Enter the Bearer token for webhook authentication. Leave empty if not required.</p>
        <script>
        function toggleTokenVisibility() {
            const tokenInput = document.getElementById('bearer_token');
            const toggleBtn = document.getElementById('toggle_token_btn');

            if (tokenInput.type === 'password') {
                tokenInput.type = 'text';
                toggleBtn.textContent = 'Hide';
            } else {
                tokenInput.type = 'password';
                toggleBtn.textContent = 'Show';
            }
        }
        </script>
        <?php
    }

    public function suppress_duplicates_callback() {
        $value = isset($this->options['suppress_duplicates']) ? $this->options['suppress_duplicates'] : false;
        echo '<label>';
        echo '<input type="checkbox" name="' . $this->option_name . '[suppress_duplicates]" value="1" ' . checked($value, true, false) . '>';
        echo ' Enable duplicate update suppression based on content';
        echo '</label>';
        echo '<p class="description">When enabled, webhooks will not be sent if the content hasn\'t changed since the last successful webhook.</p>';
    }

    private function should_suppress_webhook($post, $event_type) {
        // If suppression is not enabled, never suppress
        if (empty($this->options['suppress_duplicates'])) {
            return false;
        }

        // Get the current idempotency key
        $current_key = $this->get_stable_idempotency_key($post, $event_type);

        // Get the last successful webhook key
        $last_key = get_post_meta($post->ID, '_last_successful_webhook_key', true);

        // If the keys match, suppress the webhook
        return $current_key === $last_key;
    }

    public function post_statuses_callback() {
        $statuses = array('publish', 'pending', 'draft', 'private');
        $selected = isset($this->options['post_statuses']) ? $this->options['post_statuses'] : array();

        foreach ($statuses as $status) {
            $checked = in_array($status, $selected) ? 'checked' : '';
            echo '<label style="display: block; margin-bottom: 5px;">';
            echo '<input type="checkbox" name="' . $this->option_name . '[post_statuses][]" value="' . $status . '" ' . $checked . '>';
            echo ' ' . ucfirst($status);
            echo '</label>';
        }
    }

    public function idempotency_fields_callback() {
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

        $selected = isset($this->options['idempotency_fields']) ? $this->options['idempotency_fields'] : array('title', 'content', 'status', 'event_type');

        echo '<p class="description">Select which fields should be included when generating the idempotency key. Changes to selected fields will trigger new webhooks when suppression is enabled.</p>';
        echo '<div style="margin-top: 10px;">';
        foreach ($fields as $field => $label) {
            $checked = in_array($field, $selected) ? 'checked' : '';
            echo '<label style="display: block; margin-bottom: 5px;">';
            echo '<input type="checkbox" name="' . $this->option_name . '[idempotency_fields][]" value="' . $field . '" ' . $checked . '>';
            echo ' ' . $label;
            echo '</label>';
        }
        echo '</div>';
    }

    public function retry_settings_callback() {
        // Get current settings or set defaults
        $retry_settings = isset($this->options['retry_settings']) ? $this->options['retry_settings'] : array(
            'enabled' => true,
            'max_retries' => 3,
            'delays' => array(30, 300, 3600)
        );

        echo '<div class="retry-settings-wrapper">';

        // Enable/disable retries
        echo '<label style="display: block; margin-bottom: 10px;">';
        echo '<input type="checkbox" name="' . $this->option_name . '[retry_settings][enabled]" value="1" '
            . checked(!empty($retry_settings['enabled']), true, false) . '>';
        echo ' Enable automatic retries on failure';
        echo '</label>';

        // Maximum retries
        echo '<div style="margin-bottom: 10px;">';
        echo '<label style="display: block; margin-bottom: 5px;">Maximum retry attempts:</label>';
        echo '<input type="number" name="' . $this->option_name . '[retry_settings][max_retries]" value="'
            . esc_attr($retry_settings['max_retries']) . '" min="1" max="10" class="small-text">';
        echo '</div>';

        // Retry delays
        echo '<div class="retry-delays">';
        echo '<label style="display: block; margin-bottom: 5px;">Retry delays (in seconds):</label>';
        echo '<div style="display: flex; gap: 10px; align-items: center;">';

        $delays = isset($retry_settings['delays']) ? $retry_settings['delays'] : array(30, 300, 3600);
        foreach ($delays as $index => $delay) {
            echo '<div>';
            echo '<label>Attempt ' . ($index + 1) . ':</label>';
            echo '<input type="number" name="' . $this->option_name . '[retry_settings][delays][]" value="'
                . esc_attr($delay) . '" min="1" class="small-text">';
        }
        echo '</div>';
        echo '</div>';

        echo '<p class="description">Configure how many times to retry failed webhooks and the delay between attempts.</p>';
        echo '</div>';
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

    public function handle_post_event($new_status, $old_status, $post) {
        // Skip if it's a revision
        if (wp_is_post_revision($post->ID)) {
            return;
        }

        // Skip if it's an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Skip if webhook URL is not set
        if (empty($this->options['webhook_url'])) {
            return;
        }

        // Get monitored statuses
        $monitored_statuses = isset($this->options['post_statuses']) ? $this->options['post_statuses'] : array();

        // Determine the type of event
        $is_status_change = ($old_status !== $new_status);
        $is_entering_monitored = in_array($new_status, $monitored_statuses);
        $is_leaving_monitored = in_array($old_status, $monitored_statuses);
        $is_monitored_update = in_array($new_status, $monitored_statuses) && !$is_status_change;

        // Skip if none of our conditions are met
        if (!$is_entering_monitored && !$is_leaving_monitored && !$is_monitored_update) {
            return;
        }

        // Set the event type
        $event_type = $is_status_change ? 'post.status_changed' : ($old_status === 'new' ? 'post.created' : 'post.updated');

        // Check if we should suppress this webhook
        if ($this->should_suppress_webhook($post, $event_type)) {
            return;
        }

        // Prepare webhook data
        $data = $this->prepare_webhook_data($post, $event_type);

        // Set appropriate event type and transition info
        if ($is_status_change) {
            $data['status_transition'] = array(
                'from' => $old_status,
                'to' => $new_status
            );
        }

        $this->send_webhook($data);
    }

    private function prepare_webhook_data($post, $event_type) {
        $author = get_user_by('id', $post->post_author);
        $categories = wp_get_post_categories($post->ID, array('fields' => 'names'));
        $tags = wp_get_post_tags($post->ID, array('fields' => 'names'));

        return array(
            'event_type' => $event_type,
            'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
            'idempotency_key' => $this->get_stable_idempotency_key($post, $event_type),
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

    private function send_webhook($data) {
        $headers = array(
            'Content-Type' => 'application/json',
            'X-WordPress-Webhook' => 'post-upsert',
            'Idempotency-Key' => $data['idempotency_key']
        );

        // Add Bearer token if configured
        if (!empty($this->options['bearer_token'])) {
            $headers['Authorization'] = 'Bearer ' . $this->options['bearer_token'];
        }

        $http_method = isset($this->options['http_method']) ? $this->options['http_method'] : 'POST';

        $args = array(
            'headers' => $headers,
            'timeout' => 15
        );

        // Get retry settings
        $retry_settings = isset($this->options['retry_settings']) ? $this->options['retry_settings'] : array(
            'enabled' => true,
            'max_retries' => 3,
            'delays' => array(30, 300, 3600)
        );

        // Try to get existing retry count
        $retry_meta_key = '_webhook_retry_' . $data['idempotency_key'];
        $retry_count = (int)get_post_meta($data['post']['id'], $retry_meta_key, true);

        // Check if we should stop retrying
        if ($retry_count >= $retry_settings['max_retries']) {
            error_log('Maximum retry attempts reached for webhook: ' . $data['idempotency_key']);
            delete_post_meta($data['post']['id'], $retry_meta_key);
            return;
        }

        if ($http_method === 'POST') {
            $args['body'] = wp_json_encode($data);
            $response = wp_remote_post($this->options['webhook_url'], $args);
        } else {
            // For GET requests, add data as query parameters
            $url = add_query_arg(array('data' => base64_encode(wp_json_encode($data))), $this->options['webhook_url']);
            $response = wp_remote_get($url, $args);
        }

        if (is_wp_error($response)) {
            error_log('Webhook sending failed: ' . $response->get_error_message());
            if (!empty($retry_settings['enabled'])) {
                $this->schedule_retry($data, $retry_count, $retry_settings['delays']);
            }
            return;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code < 200 || $response_code >= 300) {
            error_log(sprintf('Webhook failed with HTTP %d: %s', $response_code, wp_remote_retrieve_body($response)));
            if (!empty($retry_settings['enabled'])) {
                $this->schedule_retry($data, $retry_count, $retry_settings['delays']);
            }
            return;
        }

        // Success - clean up retry metadata and store the successful webhook key
        delete_post_meta($data['post']['id'], $retry_meta_key);
        update_post_meta($data['post']['id'], '_last_successful_webhook_key', $data['idempotency_key']);
    }

    private function schedule_retry($data, $retry_count, $retry_delays) {
        $retry_meta_key = '_webhook_retry_' . $data['idempotency_key'];
        $next_retry = $retry_count + 1;

        if ($next_retry <= count($retry_delays)) {
            update_post_meta($data['post']['id'], $retry_meta_key, $next_retry);

            wp_schedule_single_event(
                time() + $retry_delays[$retry_count],
                'wp_post_upsert_webhook_retry',
                array($data)
            );
        }
    }

    public static function init_retry_hook() {
        add_action('wp_post_upsert_webhook_retry', array(self::get_instance(), 'retry_webhook'));
    }

    public function retry_webhook($data) {
        $this->send_webhook($data);
    }
}

// Initialize the plugin and retry hooks
add_action('plugins_loaded', array('WP_Post_Upsert_Webhook', 'get_instance'));
add_action('plugins_loaded', array('WP_Post_Upsert_Webhook', 'init_retry_hook'));
