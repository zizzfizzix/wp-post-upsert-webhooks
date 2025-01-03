<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div>
    <div class="webhook-setting">
        <label>
            <input type="checkbox"
                   name="<?php echo $this->option_name; ?>[webhooks][<?php echo $index; ?>][suppress_duplicates]"
                   value="1"
                   <?php checked(!empty($webhook['suppress_duplicates'])); ?>>
            Suppress duplicate webhook invocations based on the idempotency key. If unset webhook might be triggered on unimportant changes (see Idempotency Fields).
        </label>
    </div>

    <div class="webhook-setting">
        <label>Idempotency Fields (changes to these fields will trigger the webhook):</label>
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
            Enable automatic retries (in case of unsuccessful request)
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
