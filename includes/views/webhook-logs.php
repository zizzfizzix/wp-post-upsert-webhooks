<?php
if (!defined('ABSPATH')) {
    exit;
}

// Get filters from URL
$webhook_filter = isset($_GET['webhook']) ? sanitize_text_field($_GET['webhook']) : '';
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 20;

// Build query
global $wpdb;
$table_name = $wpdb->prefix . 'wp_post_upsert_webhooks_logs';
$offset = ($page - 1) * $per_page;

$where = array('1=1');
$where_values = array();

if (!empty($webhook_filter)) {
    $where[] = 'webhook_name = %s';
    $where_values[] = $webhook_filter;
}

if (!empty($status_filter)) {
    $where[] = 'status = %s';
    $where_values[] = $status_filter;
}

$where_clause = implode(' AND ', $where);
$count_query = $wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE $where_clause", $where_values);
$total_items = $wpdb->get_var($count_query);
$total_pages = ceil($total_items / $per_page);

$logs_query = $wpdb->prepare(
    "SELECT * FROM $table_name WHERE $where_clause ORDER BY timestamp DESC LIMIT %d OFFSET %d",
    array_merge($where_values, array($per_page, $offset))
);
$logs = $wpdb->get_results($logs_query);

// Get unique webhook names and statuses for filters
$webhook_names = $wpdb->get_col("SELECT DISTINCT webhook_name FROM $table_name ORDER BY webhook_name");
$statuses = $wpdb->get_col("SELECT DISTINCT status FROM $table_name ORDER BY status");
?>

<div class="wrap">
    <h1>Webhook Logs</h1>

    <form method="get" class="webhook-logs-filters">
        <input type="hidden" name="page" value="wp-post-upsert-webhooks-logs">

        <select name="webhook">
            <option value="">All Webhooks</option>
            <?php foreach ($webhook_names as $name): ?>
                <option value="<?php echo esc_attr($name); ?>" <?php selected($webhook_filter, $name); ?>>
                    <?php echo esc_html($name); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="status">
            <option value="">All Statuses</option>
            <?php foreach ($statuses as $status): ?>
                <option value="<?php echo esc_attr($status); ?>" <?php selected($status_filter, $status); ?>>
                    <?php echo esc_html(ucfirst($status)); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="button">Filter</button>
    </form>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Timestamp</th>
                <th>Webhook</th>
                <th>Event Type</th>
                <th>Idempotency Key</th>
                <th>Post ID</th>
                <th>Status</th>
                <th>Response Code</th>
                <th>Retries</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($logs)): ?>
                <tr>
                    <td colspan="9">No logs found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?php echo esc_html(get_date_from_gmt($log->timestamp)); ?></td>
                        <td><?php echo esc_html($log->webhook_name); ?></td>
                        <td><?php echo esc_html($log->event_type); ?></td>
                        <td><?php echo esc_html($log->idempotency_key); ?></td>
                        <td>
                            <a href="<?php echo get_edit_post_link($log->post_id); ?>" target="_blank">
                                <?php echo esc_html($log->post_id); ?>
                            </a>
                        </td>
                        <td>
                            <span class="webhook-status-<?php echo esc_attr($log->status); ?>">
                                <?php echo esc_html(ucfirst($log->status)); ?>
                            </span>
                        </td>
                        <td><?php echo $log->response_code ? esc_html($log->response_code) : '—'; ?></td>
                        <td><?php echo esc_html($log->retry_count); ?></td>
                        <td>
                            <button type="button" class="button button-small show-webhook-details"
                                    data-log="<?php echo esc_attr(wp_json_encode($log)); ?>">
                                View Details
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if ($total_pages > 1): ?>
        <div class="tablenav">
            <div class="tablenav-pages">
                <?php
                echo paginate_links(array(
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'prev_text' => __('&laquo;'),
                    'next_text' => __('&raquo;'),
                    'total' => $total_pages,
                    'current' => $page
                ));
                ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Modal for webhook details -->
<div id="webhook-details-modal" class="webhook-modal" style="display: none;">
    <div class="webhook-modal-content">
        <span class="webhook-modal-close">&times;</span>
        <h2>Webhook Execution Details</h2>
        <div class="webhook-details-content">
            <div class="webhook-detail-group">
                <h3>Request</h3>
                <h4>URL</h4>
                <pre id="webhook-url"></pre>
                <h4>Headers</h4>
                <pre id="webhook-request-headers"></pre>
                <h4>Payload</h4>
                <pre id="webhook-payload"></pre>
            </div>
            <div class="webhook-detail-group">
                <h3>Response</h3>
                <h4>Status: <span id="webhook-response-status"></span></h4>
                <h4>Headers</h4>
                <pre id="webhook-response-headers"></pre>
                <h4>Body</h4>
                <pre id="webhook-response"></pre>
            </div>
        </div>
    </div>
</div>
