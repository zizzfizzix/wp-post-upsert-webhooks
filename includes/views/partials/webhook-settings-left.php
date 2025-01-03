<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div>
    <div class="webhook-setting">
        <label>HTTP Method:</label>
        <select name="<?php echo $this->option_name; ?>[webhooks][<?php echo $index; ?>][http_method]">
            <option value="POST" <?php selected($webhook['http_method'], 'POST'); ?>>POST</option>
            <option value="GET" <?php selected($webhook['http_method'], 'GET'); ?>>GET</option>
        </select>
    </div>

    <div class="webhook-setting">
        <label>Authorization Bearer Token:</label>
        <div class="webhook-setting-group">
            <input type="password"
                   name="<?php echo $this->option_name; ?>[webhooks][<?php echo $index; ?>][bearer_token]"
                   value="<?php echo esc_attr($webhook['bearer_token']); ?>"
                   class="regular-text">
            <button type="button" class="button" onclick="toggleTokenVisibility(this)">Show</button>
        </div>
    </div>

    <div class="webhook-setting">
        <label>Listen to changes of these Post Types:</label>
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
        <label>Listen to changes for Posts in these Statuses:</label>
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
