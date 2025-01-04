<?php

if (!defined('ABSPATH')) {
    exit;
}

class WP_Post_Upsert_Webhooks_Handler {
    private $settings;

    public function __construct($settings) {
        $this->settings = $settings;
        add_action('transition_post_status', array($this, 'handle_post_event'), 10, 3);
        add_action('wp_post_upsert_webhooks_retry', array($this, 'retry_webhook'), 10, 2);
    }

    public function handle_post_event($new_status, $old_status, $post) {
        if (wp_is_post_revision($post->ID) || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)) {
            return;
        }

        $webhooks = $this->settings->get_options();
        $webhooks = isset($webhooks['webhooks']) ? $webhooks['webhooks'] : array();

        foreach ($webhooks as $webhook) {
            if (empty($webhook['enabled']) || empty($webhook['url'])) {
                continue;
            }

            if (!in_array($post->post_type, $webhook['post_types'])) {
                continue;
            }

            $monitored_statuses = isset($webhook['post_statuses']) ? $webhook['post_statuses'] : array();
            $is_status_change = ($old_status !== $new_status);
            $is_entering_monitored = in_array($new_status, $monitored_statuses);
            $is_leaving_monitored = in_array($old_status, $monitored_statuses);
            $is_monitored_update = in_array($new_status, $monitored_statuses) && !$is_status_change;

            if (!$is_entering_monitored && !$is_leaving_monitored && !$is_monitored_update) {
                continue;
            }

            $event_type = $is_status_change ? 'post.status_changed' : ($old_status === 'new' ? 'post.created' : 'post.updated');

            if (!empty($webhook['suppress_duplicates']) && $this->should_suppress_webhook($post, $event_type, $webhook)) {
                continue;
            }

            $data = $this->prepare_webhook_data($post, $event_type, $webhook);

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
        $current_key = $this->get_stable_idempotency_key($post, $event_type, $webhook);
        $last_key = get_post_meta($post->ID, '_last_successful_webhook_key_' . md5($webhook['url']), true);
        return $current_key === $last_key;
    }

    private function get_content_hash($post, $webhook) {
        $fields = isset($webhook['idempotency_fields']) ? $webhook['idempotency_fields'] : array();
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
        $fields = isset($webhook['idempotency_fields']) ? $webhook['idempotency_fields'] : array();
        $key_parts = array(
            $post->ID,
            $this->get_content_hash($post, $webhook),
            md5($webhook['url'])
        );

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
				'type' => $post->post_type,
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

        if (!empty($webhook['bearer_token'])) {
            $headers['Authorization'] = 'Bearer ' . $webhook['bearer_token'];
        }

        $args = array(
            'headers' => $headers,
            'timeout' => 15
        );

        $retry_settings = $webhook['retry_settings'];
        $retry_meta_key = '_webhook_retry_' . md5($webhook['url']) . '_' . $data['idempotency_key'];
        $retry_count = (int)get_post_meta($data['post']['id'], $retry_meta_key, true);

        if ($retry_count >= $retry_settings['max_retries']) {
            error_log(sprintf('Maximum retry attempts reached for webhook %s: %s', $webhook['name'], $data['idempotency_key']));
            delete_post_meta($data['post']['id'], $retry_meta_key);
            return;
        }

        if ($webhook['http_method'] === 'POST') {
            $args['body'] = wp_json_encode($data);
            $response = wp_remote_post($webhook['url'], $args);
        } else {
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

        delete_post_meta($data['post']['id'], $retry_meta_key);
        update_post_meta($data['post']['id'], '_last_successful_webhook_key_' . md5($webhook['url']), $data['idempotency_key']);
    }

    private function schedule_retry($data, $webhook, $retry_count) {
        $retry_meta_key = '_webhook_retry_' . md5($webhook['url']) . '_' . $data['idempotency_key'];
        $next_retry = $retry_count + 1;
        $retry_settings = $webhook['retry_settings'];

        if ($next_retry <= $retry_settings['max_retries']) {
            update_post_meta($data['post']['id'], $retry_meta_key, $next_retry);

            $delay_seconds = 0;
            if ($retry_settings['mode'] === 'constant') {
                $value = $retry_settings['constant_delay']['value'];
                switch ($retry_settings['constant_delay']['unit']) {
                    case 'ms':
                        $delay_seconds = $value / 1000;
                        break;
                    case 'sec':
                        $delay_seconds = $value;
                        break;
                    case 'min':
                        $delay_seconds = $value * 60;
                        break;
                    case 'hour':
                        $delay_seconds = $value * 3600;
                        break;
                    case 'day':
                        $delay_seconds = $value * 86400;
                        break;
                }
            } elseif ($retry_settings['mode'] === 'exponential') {
                $delay = $retry_settings['exponential']['multiplier'] * pow($retry_settings['exponential']['base'], $retry_count);
                $jitter = ($delay * $retry_settings['exponential']['jitter']) / 100;
                $delay_seconds = $delay + (rand(-$jitter * 100, $jitter * 100) / 100);
            }

            wp_schedule_single_event(
                time() + max(1, round($delay_seconds)),
                'wp_post_upsert_webhooks_retry',
                array($data, $webhook)
            );
        }
    }

    public function retry_webhook($data, $webhook) {
        $this->send_webhook($data, $webhook);
    }
}
