=== Post Upsert Webhooks ===
Contributors: kuba-serafinowski
Tags: webhooks, api, integration, posts, automation, rest-api
Requires at least: 5.0
Tested up to: 6.7.1
Requires PHP: 7.0
Stable tag: 0.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Send reliable webhooks for post creation, updates, and status changes with configurable endpoints, retry logic, and idempotency support.

== Description ==

WP Post Upsert Webhooks enables WordPress sites to send reliable webhook notifications when posts are created, updated, or their status changes. Perfect for integrating WordPress with external services, headless CMS setups, or custom automation workflows.

**Key Features:**

* Configure multiple webhook endpoints with different settings
* Unique IDs for each webhook configuration
* Support for POST and GET HTTP methods
* Flexible post type and status filtering
* Advanced retry mechanisms with constant or exponential backoff
* Configurable jitter for exponential backoff
* Idempotency support with customizable fields
* Bearer token authentication support
* Detailed post data including meta fields
* Event-based triggers (create, update, status change)
* Duplicate suppression with configurable fields
* Comprehensive logging with webhook tracing
* Automatic cleanup of webhook metadata
* Configurable log retention with automatic cleanup

**Log Retention Features:**

* Per-webhook log retention settings
* Configurable retention period (in days)
* Maximum number of logs to keep per webhook
* Automatic daily cleanup of old logs
* Always preserves the most recent log entry
* Option to disable cleanup and retain all logs

**Use Cases:**

* Sync content with external systems
* Trigger automated workflows
* Update caches or static sites
* Integrate with third-party services
* Build decoupled WordPress architectures

**Technical Details:**

The plugin sends comprehensive post data including:

* Post ID, title, content, and excerpt
* Author information (ID, name, email)
* Categories and tags
* Custom fields (post meta)
* Status transitions
* Permalinks
* Post type
* Creation and modification dates (GMT)

Each webhook request includes:
* Unique webhook configuration ID
* Configurable idempotency keys based on selected fields
* Event type (post.created, post.updated, post.status_changed)
* Webhook-specific headers
* Timestamp in ISO 8601 format
* Custom webhook name for identification

**Retry Configuration:**

The plugin supports two retry modes:
1. Constant Delay:
   * Fixed time between retries
   * Configurable delay value
   * Multiple time units (milliseconds, seconds, minutes, hours, days)

2. Exponential Backoff:
   * Configurable base and multiplier
   * Jitter support (0-50%) to prevent thundering herd
   * Maximum retry attempts limit

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/wp-post-upsert-webhooks` directory, or install the plugin through the WordPress plugins screen
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > Post Webhooks to configure your webhook endpoints
4. For each webhook, configure:
   * Endpoint URL
   * HTTP method (POST/GET)
   * Post types to monitor
   * Post statuses to track
   * Bearer token (if required)
   * Retry settings (constant or exponential backoff)
   * Idempotency fields
   * Duplicate suppression settings
   * Log retention settings
5. Each webhook configuration is automatically assigned a unique ID for tracking
6. Use the Logs section to monitor webhook executions and troubleshoot any issues
7. Configure retry settings based on your needs:
   * Choose between constant delay or exponential backoff
   * Set appropriate delay values and retry limits
   * Add jitter for exponential backoff to prevent concurrent retries

== Frequently Asked Questions ==

= What data is included in the webhook payload? =

The webhook payload includes comprehensive post data including title, content, excerpt, author details, categories, tags, meta fields, and status information. See the technical details section for a complete list.

= How does the retry mechanism work? =

The plugin offers two retry modes:

1. Constant Delay: Retries occur at fixed intervals (e.g., every 5 minutes)
2. Exponential Backoff: Each retry increases the delay using a configurable base and multiplier, with optional jitter to prevent concurrent retries

You can configure:
* Maximum retry attempts (up to 10)
* Delay values and units (milliseconds to days)
* Jitter percentage for exponential backoff (0-50%)
* Base delay and multiplier for exponential calculations

Failed webhook attempts are automatically retried based on your configuration, with detailed logging of each attempt.

= How does log retention work? =

Each webhook has its own log retention settings that control:
* How long logs are kept (in days)
* Maximum number of logs to retain
* Option to disable cleanup and keep all logs

The plugin automatically runs a daily cleanup task that:
* Removes logs older than the specified retention period
* Keeps only the specified maximum number of most recent logs
* Always preserves at least one most recent log entry per webhook
* Respects per-webhook settings

= How does idempotency work? =

The plugin generates unique idempotency keys based on your selected fields and the webhook configuration ID:
* Title
* Content
* Status
* Slug
* Categories
* Tags
* Author
* Event type

Each webhook configuration has its own unique ID, ensuring that identical content changes trigger separate webhooks for different configurations. You can choose which fields contribute to the idempotency key, allowing fine-grained control over duplicate detection.

= How can I track webhook executions? =

The plugin provides a comprehensive logging system that includes:
* Webhook configuration ID
* Execution status (success/failure)
* Response data
* Retry attempts and timing
* Error messages
* Request payload

You can filter logs by webhook configuration and status to track specific webhook executions.

= Can I send webhooks for specific post types only? =

Yes, you can configure each webhook endpoint to monitor specific post types and statuses. This allows you to have different webhooks for different content types.

= What triggers a webhook? =

Webhooks are triggered by:
1. Post creation
2. Post updates
3. Status changes

Each webhook can be configured to monitor specific:
* Post types (e.g., posts, pages, custom post types)
* Post statuses (e.g., publish, draft, private)
* Status transitions

== Changelog ==

= X.X.X =
* Added configurable log retention settings per webhook
* Added automatic daily cleanup of old logs
* Added option to disable log cleanup per webhook
* Added minimum retention of one most recent log entry per webhook
* Added server-side validation of log retention settings

= 0.2.0 =
* Added unique IDs for webhook configurations to improve tracking and management
* Fixed duplicate suppression logic to properly handle different webhook configurations
* Added webhook IDs to logs for better traceability
* Improved retry mechanism with proper exponential backoff calculation
* Added cleanup of webhook metadata when configurations are deleted
* Fixed webhook filtering in logs
* Added display of webhook IDs in settings UI
* Improved error handling and logging

= 0.1.1 =
* Fix some minor lingering rename issues

= 0.1.0 =
* Initial release
* Support for multiple webhook endpoints
* Configurable retry logic
* Idempotency support
* Bearer token authentication
* POST and GET HTTP methods
* Comprehensive post data payload

== Upgrade Notice ==

= X.X.X =
This version adds log retention settings and automatic cleanup to help manage webhook logs. No breaking changes.

= 0.2.0 =
This version improves webhook reliability with unique IDs, better duplicate handling, and improved retry logic. No breaking changes.

= 0.1.0 =
Initial release of WP Post Upsert Webhooks
