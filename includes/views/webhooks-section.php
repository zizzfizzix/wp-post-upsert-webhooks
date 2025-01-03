<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="webhook-grid" id="webhook-endpoints">
    <?php foreach ($webhooks as $index => $webhook) : ?>
        <div class="webhook-endpoint">
            <div class="webhook-header" onclick="toggleWebhook(this)">
                <h3>
                    <span class="webhook-status <?php echo !empty($webhook['enabled']) ? 'enabled' : 'disabled'; ?>"></span>
                    <span class="webhook-title"><?php echo !empty($webhook['name']) ? esc_html($webhook['name']) : 'Unnamed Webhook'; ?></span>
                </h3>
                <div class="webhook-header-actions">
                    <button type="button" class="button button-link-delete" onclick="event.stopPropagation(); removeWebhookEndpoint(this)">Remove</button>
                    <span class="collapse-indicator<?php echo $index !== 0 ? ' rotated' : ''; ?>">▼</span>
                </div>
            </div>
            <div class="webhook-content<?php echo $index !== 0 ? ' collapsed' : ''; ?>">
                <div class="webhook-settings-grid">
                    <!-- Full width settings -->
                    <div class="webhook-settings-full">
                        <div class="webhook-setting">
                            <label>
                                <input type="checkbox"
                                       name="<?php echo $this->option_name; ?>[webhooks][<?php echo $index; ?>][enabled]"
                                       value="1"
                                       <?php checked(!empty($webhook['enabled'])); ?>>
                                Enable this webhook
                            </label>
                        </div>

                        <div class="webhook-setting">
                            <label>Name:</label>
                            <input type="text"
                                   name="<?php echo $this->option_name; ?>[webhooks][<?php echo $index; ?>][name]"
                                   value="<?php echo esc_attr($webhook['name']); ?>"
                                   class="regular-text webhook-name-input"
                                   placeholder="Webhook name for identification"
                                   onchange="updateWebhookTitle(this)">
                        </div>

                        <div class="webhook-setting">
                            <label>URL:</label>
                            <input type="url"
                                   name="<?php echo $this->option_name; ?>[webhooks][<?php echo $index; ?>][url]"
                                   value="<?php echo esc_url($webhook['url']); ?>"
                                   class="regular-text">
                        </div>
                    </div>

                    <!-- Left column -->
                    <div>
                        <div class="webhook-setting">
                            <label>HTTP Method:</label>
                            <select name="<?php echo $this->option_name; ?>[webhooks][<?php echo $index; ?>][http_method]">
                                <option value="POST" <?php selected($webhook['http_method'], 'POST'); ?>>POST</option>
                                <option value="GET" <?php selected($webhook['http_method'], 'GET'); ?>>GET</option>
                            </select>
                        </div>

                        <div class="webhook-setting">
                            <label>Bearer Token:</label>
                            <div class="webhook-setting-group">
                                <input type="password"
                                       name="<?php echo $this->option_name; ?>[webhooks][<?php echo $index; ?>][bearer_token]"
                                       value="<?php echo esc_attr($webhook['bearer_token']); ?>"
                                       class="regular-text">
                                <button type="button" class="button" onclick="toggleTokenVisibility(this)">Show</button>
                            </div>
                        </div>

                        <div class="webhook-setting">
                            <label>Post Types:</label>
                            <?php
                            $post_types = get_post_types(array('public' => true), 'objects');
                            foreach ($post_types as $post_type) :
                                $checked = in_array($post_type->name, $webhook['post_types']) ? 'checked' : '';
                            ?>
                                <label>
                                    <input type="checkbox"
                                           name="<?php echo $this->option_name; ?>[webhooks][<?php echo $index; ?>][post_types][]"
                                           value="<?php echo esc_attr($post_type->name); ?>"
                                           <?php echo $checked; ?>>
                                    <?php echo esc_html($post_type->labels->singular_name); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>

                        <div class="webhook-setting">
                            <label>Post Statuses:</label>
                            <?php
                            $statuses = array('publish', 'pending', 'draft', 'private');
                            foreach ($statuses as $status) :
                                $checked = in_array($status, $webhook['post_statuses']) ? 'checked' : '';
                            ?>
                                <label>
                                    <input type="checkbox"
                                           name="<?php echo $this->option_name; ?>[webhooks][<?php echo $index; ?>][post_statuses][]"
                                           value="<?php echo $status; ?>"
                                           <?php echo $checked; ?>>
                                    <?php echo ucfirst($status); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Right column -->
                    <div>
                        <div class="webhook-setting">
                            <label>
                                <input type="checkbox"
                                       name="<?php echo $this->option_name; ?>[webhooks][<?php echo $index; ?>][suppress_duplicates]"
                                       value="1"
                                       <?php checked(!empty($webhook['suppress_duplicates'])); ?>>
                                Suppress duplicate updates
                            </label>
                        </div>

                        <div class="webhook-setting">
                            <label>Idempotency Fields:</label>
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
                            foreach ($fields as $field => $label) :
                                $checked = in_array($field, $webhook['idempotency_fields']) ? 'checked' : '';
                            ?>
                                <label>
                                    <input type="checkbox"
                                           name="<?php echo $this->option_name; ?>[webhooks][<?php echo $index; ?>][idempotency_fields][]"
                                           value="<?php echo $field; ?>"
                                           <?php echo $checked; ?>>
                                    <?php echo $label; ?>
                                </label>
                            <?php endforeach; ?>
                        </div>

                        <div class="retry-settings">
                            <label>
                                <input type="checkbox"
                                       name="<?php echo $this->option_name; ?>[webhooks][<?php echo $index; ?>][retry_settings][enabled]"
                                       value="1"
                                       <?php checked(!empty($webhook['retry_settings']['enabled'])); ?>>
                                Enable automatic retries
                            </label>

                            <div class="retry-setting">
                                <label>Maximum retry attempts:</label>
                                <input type="number"
                                       name="<?php echo $this->option_name; ?>[webhooks][<?php echo $index; ?>][retry_settings][max_retries]"
                                       value="<?php echo esc_attr($webhook['retry_settings']['max_retries']); ?>"
                                       min="1"
                                       max="10"
                                       class="small-text">
                            </div>

                            <div class="retry-setting">
                                <label>Retry delays (seconds):</label>
                                <div class="retry-delays">
                                    <?php foreach ($webhook['retry_settings']['delays'] as $delay_index => $delay) : ?>
                                        <div>
                                            <label>Attempt <?php echo $delay_index + 1; ?>:</label>
                                            <input type="number"
                                                   name="<?php echo $this->option_name; ?>[webhooks][<?php echo $index; ?>][retry_settings][delays][]"
                                                   value="<?php echo esc_attr($delay); ?>"
                                                   min="1"
                                                   class="small-text">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="webhook-actions">
    <button type="button" class="button" onclick="addWebhookEndpoint()">Add Another Webhook</button>
</div>

<template id="webhook-template">
    <?php
    $template_webhook = WP_Post_Upsert_Webhook_Settings::$default_config;
    $template_index = '{{INDEX}}';
    include plugin_dir_path(__FILE__) . 'webhook-template.php';
    ?>
</template>
