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

	<div class="webhook-setting">
		<label>Retry:</label>
		<div class="retry-settings">
			<div class="retry-mode-selector">
				<label class="retry-mode-option">
					<input type="radio"
						name="<?php echo $this->option_name; ?>[webhooks][<?php echo $index; ?>][retry_settings][mode]"
						value="disabled"
						<?php checked($webhook['retry_settings']['mode'], 'disabled'); ?>>
					Disabled
				</label>
				<label class="retry-mode-option">
					<input type="radio"
						name="<?php echo $this->option_name; ?>[webhooks][<?php echo $index; ?>][retry_settings][mode]"
						value="constant"
						<?php checked($webhook['retry_settings']['mode'], 'constant'); ?>>
					Constant
				</label>
				<label class="retry-mode-option">
					<input type="radio"
						name="<?php echo $this->option_name; ?>[webhooks][<?php echo $index; ?>][retry_settings][mode]"
						value="exponential"
						<?php checked($webhook['retry_settings']['mode'], 'exponential'); ?>>
					Exponential
				</label>
			</div>
			<div class="retry-settings-content">
				<div class="retry-settings-row">
					<div class="retry-setting">
						<label>Max attempts:</label>
						<input type="number"
							name="<?php echo $this->option_name; ?>[webhooks][<?php echo $index; ?>][retry_settings][max_retries]"
							value="<?php echo esc_attr($webhook['retry_settings']['max_retries']); ?>"
							min="1"
							max="10"
							class="small-text max-retries-input">
					</div>

					<div class="retry-setting retry-exponential-settings retry-mode-content">
						<label>Multiplier:</label>
						<input type="number"
							name="<?php echo $this->option_name; ?>[webhooks][<?php echo $index; ?>][retry_settings][exponential][multiplier]"
							value="<?php echo esc_attr($webhook['retry_settings']['exponential']['multiplier'] ?? 2); ?>"
							min="1"
							step="0.1"
							class="small-text">
					</div>

					<div class="retry-setting retry-exponential-settings retry-mode-content">
						<label>Base (in seconds):</label>
						<input type="number"
							name="<?php echo $this->option_name; ?>[webhooks][<?php echo $index; ?>][retry_settings][exponential][base]"
							value="<?php echo esc_attr($webhook['retry_settings']['exponential']['base'] ?? 5); ?>"
							min="1"
							class="small-text">
					</div>
					<div class="retry-setting retry-constant-settings retry-mode-content">
						<label>Delay:</label>
						<div class="time-input-group">
							<input type="number"
								name="<?php echo $this->option_name; ?>[webhooks][<?php echo $index; ?>][retry_settings][constant_delay][value]"
								value="<?php echo esc_attr($webhook['retry_settings']['constant_delay']['value'] ?? 5); ?>"
								min="0"
								class="small-text">
							<select name="<?php echo $this->option_name; ?>[webhooks][<?php echo $index; ?>][retry_settings][constant_delay][unit]"
								class="retry-delay-unit">
								<option value="ms" <?php selected($webhook['retry_settings']['constant_delay']['unit'] ?? 'sec', 'ms'); ?>>Milliseconds</option>
								<option value="sec" <?php selected($webhook['retry_settings']['constant_delay']['unit'] ?? 'sec', 'sec'); ?>>Seconds</option>
								<option value="min" <?php selected($webhook['retry_settings']['constant_delay']['unit'] ?? 'sec', 'min'); ?>>Minutes</option>
								<option value="hour" <?php selected($webhook['retry_settings']['constant_delay']['unit'] ?? 'sec', 'hour'); ?>>Hours</option>
								<option value="day" <?php selected($webhook['retry_settings']['constant_delay']['unit'] ?? 'sec', 'day'); ?>>Days</option>
							</select>
						</div>
					</div>
				</div>

				<!-- Exponential delay settings -->
				<div class="retry-exponential-settings retry-mode-content">
					<div class="retry-setting">
						<div class="formula-hint">delay = multiplier * base ^ (number of attempt)</div>
					</div>

					<div class="retry-setting">
						<label>Randomization factor (percentage):</label>
						<div class="range-input-group">
							<input type="range"
								name="<?php echo $this->option_name; ?>[webhooks][<?php echo $index; ?>][retry_settings][exponential][jitter]"
								value="<?php echo esc_attr($webhook['retry_settings']['exponential']['jitter'] ?? 5); ?>"
								min="0"
								max="50"
								class="retry-jitter-range">
							<input type="number"
								value="<?php echo esc_attr($webhook['retry_settings']['exponential']['jitter'] ?? 5); ?>"
								class="small-text retry-jitter-number"
								readonly>
						</div>
					</div>
				</div>

				<div class="retry-preview">
					<h4>Retry attempts</h4>
					<div class="retry-preview-content"></div>
				</div>
			</div>
		</div>
	</div>
</div>

<template id="retry-delay-template">
    <div class="retry-delay-field">
        <label>Retry #{{DELAY_INDEX}} Delay (seconds):</label>
        <input type="number"
               name="<?php echo $this->option_name; ?>[webhooks][{{INDEX}}][retry_settings][delays][]"
               value="30"
               min="1"
               class="small-text">
    </div>
</template>
