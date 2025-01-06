<?php
if (!defined('ABSPATH')) {
    exit;
}

// Expects $webhook and $index to be defined by the including file
?>
<div class="webhook-endpoint">
    <?php include plugin_dir_path(__FILE__) . 'partials/webhook-header.php'; ?>
    <div class="webhook-content<?php echo isset($is_collapsed) && $is_collapsed ? ' collapsed' : ''; ?>">
		<div class="webhook-settings-full">
			<div class="webhook-setting">
				<label>
					<input type="checkbox"
						name="<?php echo $this->option_name; ?>[webhooks][<?php echo $index; ?>][enabled]"
						value="1"
						<?php checked(!empty($webhook['enabled'])); ?>>
					<?php esc_html_e('Enable this webhook', 'wp-post-upsert-webhooks'); ?>
				</label>
			</div>

			<div class="webhook-setting">
				<label><?php esc_html_e('Name:', 'wp-post-upsert-webhooks'); ?></label>
				<input type="text"
					name="<?php echo $this->option_name; ?>[webhooks][<?php echo $index; ?>][name]"
					value="<?php echo esc_attr($webhook['name']); ?>"
					class="regular-text webhook-name-input"
					placeholder="<?php esc_attr_e('Webhook name for identification', 'wp-post-upsert-webhooks'); ?>"
					onchange="updateWebhookTitle(this)">
			</div>

			<div class="webhook-setting">
				<label><?php esc_html_e('URL:', 'wp-post-upsert-webhooks'); ?></label>
				<input type="url"
					name="<?php echo $this->option_name; ?>[webhooks][<?php echo $index; ?>][url]"
					value="<?php echo esc_url($webhook['url']); ?>"
					class="regular-text">
			</div>
		</div>
        <div class="webhook-settings-grid">
			<div class="webhook-setting">
				<label><?php esc_html_e('HTTP Method:', 'wp-post-upsert-webhooks'); ?></label>
				<select name="<?php echo $this->option_name; ?>[webhooks][<?php echo $index; ?>][http_method]">
					<option value="POST" <?php selected($webhook['http_method'], 'POST'); ?>>POST</option>
					<option value="GET" <?php selected($webhook['http_method'], 'GET'); ?>>GET</option>
				</select>
			</div>

			<div class="webhook-setting">
				<label><?php esc_html_e('Authorization Bearer Token:', 'wp-post-upsert-webhooks'); ?></label>
				<div class="webhook-setting-group">
					<input type="password"
						name="<?php echo $this->option_name; ?>[webhooks][<?php echo $index; ?>][bearer_token]"
						value="<?php echo esc_attr($webhook['bearer_token']); ?>"
						class="regular-text">
					<button type="button" class="button" onclick="toggleTokenVisibility(this)"><?php esc_html_e('Show', 'wp-post-upsert-webhooks'); ?></button>
				</div>
			</div>

			<div class="webhook-setting">
				<label><?php esc_html_e('Listen to changes of these Post Types:', 'wp-post-upsert-webhooks'); ?></label>
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
				<label><?php esc_html_e('Listen to changes for Posts in these Statuses:', 'wp-post-upsert-webhooks'); ?></label>
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
						<?php echo esc_html(ucfirst(__($status, 'wp-post-upsert-webhooks'))); ?>
					</label>
				<?php endforeach; ?>
			</div>

			<div class="webhook-setting">
				<label>
					<input type="checkbox"
						name="<?php echo $this->option_name; ?>[webhooks][<?php echo $index; ?>][suppress_duplicates]"
						value="1"
						<?php checked(!empty($webhook['suppress_duplicates'])); ?>>
					<?php esc_html_e('Suppress duplicate webhook invocations based on the idempotency key. If unset webhook might be triggered on unimportant changes (see Idempotency Fields).', 'wp-post-upsert-webhooks'); ?>
				</label>
			</div>

			<div class="webhook-setting">
				<label><?php esc_html_e('Idempotency Fields (changes to these fields will trigger the webhook):', 'wp-post-upsert-webhooks'); ?></label>
				<?php
				$fields = array(
					'title' => __('Title', 'wp-post-upsert-webhooks'),
					'content' => __('Content', 'wp-post-upsert-webhooks'),
					'excerpt' => __('Excerpt', 'wp-post-upsert-webhooks'),
					'status' => __('Status', 'wp-post-upsert-webhooks'),
					'slug' => __('Slug', 'wp-post-upsert-webhooks'),
					'event_type' => __('Event Type', 'wp-post-upsert-webhooks'),
					'categories' => __('Categories', 'wp-post-upsert-webhooks'),
					'tags' => __('Tags', 'wp-post-upsert-webhooks'),
					'author' => __('Author', 'wp-post-upsert-webhooks')
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
				<label><?php esc_html_e('Retry:', 'wp-post-upsert-webhooks'); ?></label>
				<div class="retry-settings">
					<div class="retry-mode-selector">
						<label class="retry-mode-option">
							<input type="radio"
								name="<?php echo $this->option_name; ?>[webhooks][<?php echo $index; ?>][retry_settings][mode]"
								value="disabled"
								<?php checked($webhook['retry_settings']['mode'], 'disabled'); ?>>
							<?php esc_html_e('Disabled', 'wp-post-upsert-webhooks'); ?>
						</label>
						<label class="retry-mode-option">
							<input type="radio"
								name="<?php echo $this->option_name; ?>[webhooks][<?php echo $index; ?>][retry_settings][mode]"
								value="constant"
								<?php checked($webhook['retry_settings']['mode'], 'constant'); ?>>
							<?php esc_html_e('Constant', 'wp-post-upsert-webhooks'); ?>
						</label>
						<label class="retry-mode-option">
							<input type="radio"
								name="<?php echo $this->option_name; ?>[webhooks][<?php echo $index; ?>][retry_settings][mode]"
								value="exponential"
								<?php checked($webhook['retry_settings']['mode'], 'exponential'); ?>>
							<?php esc_html_e('Exponential', 'wp-post-upsert-webhooks'); ?>
						</label>
					</div>
					<div class="retry-settings-content">
						<div class="retry-settings-row">
							<div class="retry-setting">
								<label><?php esc_html_e('Max attempts:', 'wp-post-upsert-webhooks'); ?></label>
								<input type="number"
									name="<?php echo $this->option_name; ?>[webhooks][<?php echo $index; ?>][retry_settings][max_retries]"
									value="<?php echo esc_attr($webhook['retry_settings']['max_retries']); ?>"
									min="1"
									max="10"
									class="small-text max-retries-input">
							</div>

							<div class="retry-setting retry-exponential-settings retry-mode-content">
								<label><?php esc_html_e('Multiplier:', 'wp-post-upsert-webhooks'); ?></label>
								<input type="number"
									name="<?php echo $this->option_name; ?>[webhooks][<?php echo $index; ?>][retry_settings][exponential][multiplier]"
									value="<?php echo esc_attr($webhook['retry_settings']['exponential']['multiplier'] ?? 2); ?>"
									min="1"
									step="0.1"
									class="small-text">
							</div>

							<div class="retry-setting retry-exponential-settings retry-mode-content">
								<label><?php esc_html_e('Base (in seconds):', 'wp-post-upsert-webhooks'); ?></label>
								<input type="number"
									name="<?php echo $this->option_name; ?>[webhooks][<?php echo $index; ?>][retry_settings][exponential][base]"
									value="<?php echo esc_attr($webhook['retry_settings']['exponential']['base'] ?? 5); ?>"
									min="1"
									class="small-text">
							</div>
							<div class="retry-setting retry-constant-settings retry-mode-content">
								<label><?php esc_html_e('Delay:', 'wp-post-upsert-webhooks'); ?></label>
								<div class="time-input-group">
									<input type="number"
										name="<?php echo $this->option_name; ?>[webhooks][<?php echo $index; ?>][retry_settings][constant_delay][value]"
										value="<?php echo esc_attr($webhook['retry_settings']['constant_delay']['value'] ?? 5); ?>"
										min="0"
										class="small-text">
									<select name="<?php echo $this->option_name; ?>[webhooks][<?php echo $index; ?>][retry_settings][constant_delay][unit]"
										class="retry-delay-unit">
										<option value="ms" <?php selected($webhook['retry_settings']['constant_delay']['unit'] ?? 'sec', 'ms'); ?>><?php esc_html_e('Milliseconds', 'wp-post-upsert-webhooks'); ?></option>
										<option value="sec" <?php selected($webhook['retry_settings']['constant_delay']['unit'] ?? 'sec', 'sec'); ?>><?php esc_html_e('Seconds', 'wp-post-upsert-webhooks'); ?></option>
										<option value="min" <?php selected($webhook['retry_settings']['constant_delay']['unit'] ?? 'sec', 'min'); ?>><?php esc_html_e('Minutes', 'wp-post-upsert-webhooks'); ?></option>
										<option value="hour" <?php selected($webhook['retry_settings']['constant_delay']['unit'] ?? 'sec', 'hour'); ?>><?php esc_html_e('Hours', 'wp-post-upsert-webhooks'); ?></option>
										<option value="day" <?php selected($webhook['retry_settings']['constant_delay']['unit'] ?? 'sec', 'day'); ?>><?php esc_html_e('Days', 'wp-post-upsert-webhooks'); ?></option>
									</select>
								</div>
							</div>
						</div>

						<!-- Exponential delay settings -->
						<div class="retry-exponential-settings retry-mode-content">
							<div class="retry-setting">
								<div class="formula-hint"><?php esc_html_e('delay = multiplier * base ^ (number of attempt)', 'wp-post-upsert-webhooks'); ?></div>
							</div>

							<div class="retry-setting">
								<label><?php esc_html_e('Randomization factor (percentage):', 'wp-post-upsert-webhooks'); ?></label>
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
							<h4><?php esc_html_e('Retry attempts', 'wp-post-upsert-webhooks'); ?></h4>
							<div class="retry-preview-content"></div>
						</div>
					</div>
				</div>
			</div>

			<div class="webhook-setting">
				<h4><?php esc_html_e('Log Retention', 'wp-post-upsert-webhooks'); ?></h4>

				<div class="webhook-setting-group">
					<label>
						<input type="checkbox"
							name="<?php echo $this->option_name; ?>[webhooks][<?php echo $index; ?>][log_retention][enabled]"
							value="1"
							<?php checked(!empty($webhook['log_retention']['enabled'])); ?>>
						<?php
						/* translators: This is a label for a checkbox that enables automatic log cleanup */
						esc_html_e('Enable automatic log cleanup', 'wp-post-upsert-webhooks');
						?>
					</label>
				</div>

				<div class="webhook-setting-group">
					<label><?php esc_html_e('Maximum time to keep logs for:', 'wp-post-upsert-webhooks'); ?></label>
					<input type="number"
						name="<?php echo $this->option_name; ?>[webhooks][<?php echo $index; ?>][log_retention][days]"
						value="<?php echo esc_attr($webhook['log_retention']['days'] ?? 90); ?>"
						min="1"
						class="small-text">
					<?php
					/* translators: Label for the unit of time (days) in log retention settings */
					esc_html_e('days', 'wp-post-upsert-webhooks');
					?>
				</div>

				<div class="webhook-setting-group">
					<label><?php esc_html_e('Maximum number of log entries to keep:', 'wp-post-upsert-webhooks'); ?></label>
					<input type="number"
						name="<?php echo $this->option_name; ?>[webhooks][<?php echo $index; ?>][log_retention][max_rows]"
						value="<?php echo esc_attr($webhook['log_retention']['max_rows'] ?? 1000); ?>"
						min="1"
						class="small-text">
					<p class="description">
						<?php
						/* translators: Description for the minimum log retention policy */
						esc_html_e('One most recent log entry will always be retained regardless of these settings.', 'wp-post-upsert-webhooks');
						?>
					</p>
				</div>
			</div>
        </div>

		<template id="retry-delay-template">
			<div class="retry-delay-field">
				<label><?php esc_html_e('Retry #{{DELAY_INDEX}} Delay (seconds):', 'wp-post-upsert-webhooks'); ?></label>
				<input type="number"
					name="<?php echo $this->option_name; ?>[webhooks][{{INDEX}}][retry_settings][delays][]"
					value="30"
					min="1"
					class="small-text">
			</div>
		</template>
    </div>
</div>
