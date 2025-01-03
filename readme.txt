=== Post Upsert Webhooks ===
Contributors: kuba-serafinowski
Tags: webhooks, api, integration, posts, automation, rest-api
Requires at least: 5.0
Tested up to: 6.7.1
Requires PHP: 7.0
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Send reliable webhooks for post creation, updates, and status changes with configurable endpoints, retry logic, and idempotency support.

== Description ==

WP Post Upsert Webhooks enables WordPress sites to send reliable webhook notifications when posts are created, updated, or their status changes. Perfect for integrating WordPress with external services, headless CMS setups, or custom automation workflows.

**Key Features:**

* Configure multiple webhook endpoints with different settings
* Support for POST and GET HTTP methods
* Flexible post type and status filtering
* Robust retry mechanism with configurable delays
* Idempotency support to prevent duplicate notifications
* Bearer token authentication support
* Detailed post data including meta fields
* Event-based triggers (create, update, status change)

**Use Cases:**

* Sync content with external systems
* Trigger automated workflows
* Update caches or static sites
* Integrate with third-party services
* Build decoupled WordPress architectures

**Technical Details:**

The plugin sends comprehensive post data including:

* Post ID, title, content, and excerpt
* Author information
* Categories and tags
* Custom fields (post meta)
* Status transitions
* Permalinks
* Creation and modification dates

Each webhook request includes idempotency keys and webhook-specific headers for reliable delivery and duplicate prevention.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/wp-post-upsert-webhooks` directory, or install the plugin through the WordPress plugins screen
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > WP Post Upsert Webhooks to configure your webhook endpoints
4. For each webhook, configure:
   * Endpoint URL
   * HTTP method (POST/GET)
   * Post types to monitor
   * Post statuses to track
   * Bearer token (if required)
   * Retry settings
   * Idempotency fields

== Frequently Asked Questions ==

= What data is included in the webhook payload? =

The webhook payload includes comprehensive post data including title, content, excerpt, author details, categories, tags, meta fields, and status information. See the technical details section for a complete list.

= How does the retry mechanism work? =

If a webhook delivery fails, the plugin will automatically retry based on your configured settings. You can set the number of retry attempts and the delay between each attempt.

= How does idempotency work? =

The plugin generates unique idempotency keys based on your configured fields (content, title, status, etc.). This ensures that identical updates don't trigger multiple webhooks, preventing duplicate processing.

= Can I send webhooks for specific post types only? =

Yes, you can configure each webhook endpoint to monitor specific post types and statuses. This allows you to have different webhooks for different content types.

== Changelog ==

= 0.1.0 =
* Initial release
* Support for multiple webhook endpoints
* Configurable retry logic
* Idempotency support
* Bearer token authentication
* POST and GET HTTP methods
* Comprehensive post data payload

== Upgrade Notice ==

= 0.1.0 =
Initial release of WP Post Upsert Webhooks
