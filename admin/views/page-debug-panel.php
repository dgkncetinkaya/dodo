<?php
/**
 * Debug Panel Page
 * 
 * PHASE 7 - Internal Debug Tooling
 * Comprehensive debugging interface for developers
 * 
 * @package DODO_AI_SEO
 * @since 4.0.0
 */

// Doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

// Yetki kontrolü
if (!current_user_can('manage_options')) {
    wp_die(__('Bu sayfaya erişim yetkiniz yok.', 'dodo-ai-seo'));
}

// Debug mode kontrolü
if (!defined('WP_DEBUG') || !WP_DEBUG) {
    ?>
    <div class="wrap">
        <h1><?php _e('Debug Panel', 'dodo-ai-seo'); ?></h1>
        <div class="notice notice-warning">
            <p><?php _e('Debug panel sadece WP_DEBUG aktifken çalışır. wp-config.php dosyanızda WP_DEBUG\'i true yapın.', 'dodo-ai-seo'); ?></p>
        </div>
    </div>
    <?php
    return;
}

// Get debug data
$system_diagnostics = DODO_Debug_Panel::get_system_diagnostics();
$api_usage = DODO_Debug_Panel::get_api_usage_stats('today');
$cache_stats = DODO_Debug_Panel::get_cache_stats();
$failed_jobs = DODO_Debug_Panel::get_failed_jobs();
$cron_history = array_slice(DODO_Debug_Panel::get_cron_history(), 0, 10);
$learning_events = array_slice(DODO_Debug_Panel::get_learning_evolution(20), 0, 10);

?>

<div class="wrap dodo-debug-wrap">
    <h1>🔍 <?php _e('DODO Debug Panel', 'dodo-ai-seo'); ?></h1>
    
    <div class="dodo-debug-tabs">
        <button class="debug-tab active" data-tab="system"><?php _e('System', 'dodo-ai-seo'); ?></button>
        <button class="debug-tab" data-tab="api"><?php _e('API Usage', 'dodo-ai-seo'); ?></button>
        <button class="debug-tab" data-tab="cache"><?php _e('Cache', 'dodo-ai-seo'); ?></button>
        <button class="debug-tab" data-tab="jobs"><?php _e('Failed Jobs', 'dodo-ai-seo'); ?></button>
        <button class="debug-tab" data-tab="cron"><?php _e('Cron History', 'dodo-ai-seo'); ?></button>
        <button class="debug-tab" data-tab="learning"><?php _e('Learning', 'dodo-ai-seo'); ?></button>
    </div>
    
    <!-- System Tab -->
    <div class="debug-tab-content active" id="tab-system">
        <div class="dodo-card">
            <div class="dodo-card-header">
                <h2>💻 <?php _e('System Diagnostics', 'dodo-ai-seo'); ?></h2>
            </div>
            <div class="dodo-card-body">
                <table class="debug-table">
                    <tr>
                        <th><?php _e('PHP Version', 'dodo-ai-seo'); ?></th>
                        <td><?php echo esc_html($system_diagnostics['php_version']); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('WordPress Version', 'dodo-ai-seo'); ?></th>
                        <td><?php echo esc_html($system_diagnostics['wp_version']); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Plugin Version', 'dodo-ai-seo'); ?></th>
                        <td><?php echo esc_html($system_diagnostics['plugin_version']); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Memory Limit', 'dodo-ai-seo'); ?></th>
                        <td><?php echo esc_html($system_diagnostics['memory_limit']); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Memory Usage', 'dodo-ai-seo'); ?></th>
                        <td><?php echo esc_html($system_diagnostics['memory_usage']); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Memory Peak', 'dodo-ai-seo'); ?></th>
                        <td><?php echo esc_html($system_diagnostics['memory_peak']); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Max Execution Time', 'dodo-ai-seo'); ?></th>
                        <td><?php echo esc_html($system_diagnostics['max_execution_time']); ?>s</td>
                    </tr>
                    <tr>
                        <th><?php _e('Theme', 'dodo-ai-seo'); ?></th>
                        <td><?php echo esc_html($system_diagnostics['theme']); ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    
    <!-- API Usage Tab -->
    <div class="debug-tab-content" id="tab-api">
        <div class="dodo-card">
            <div class="dodo-card-header">
                <h2>🤖 <?php _e('API Usage (Today)', 'dodo-ai-seo'); ?></h2>
            </div>
            <div class="dodo-card-body">
                <?php if (!empty($api_usage)): ?>
                <table class="debug-table">
                    <tr>
                        <th><?php _e('Total Calls', 'dodo-ai-seo'); ?></th>
                        <td><?php echo esc_html($api_usage['total_calls'] ?? 0); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Total Tokens', 'dodo-ai-seo'); ?></th>
                        <td><?php echo esc_html(number_format($api_usage['total_tokens'] ?? 0)); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Total Cost', 'dodo-ai-seo'); ?></th>
                        <td>$<?php echo esc_html(number_format($api_usage['total_cost'] ?? 0, 4)); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Avg Response Time', 'dodo-ai-seo'); ?></th>
                        <td><?php echo esc_html(number_format($api_usage['avg_response_time'] ?? 0, 2)); ?>ms</td>
                    </tr>
                    <tr>
                        <th><?php _e('Max Response Time', 'dodo-ai-seo'); ?></th>
                        <td><?php echo esc_html(number_format($api_usage['max_response_time'] ?? 0, 2)); ?>ms</td>
                    </tr>
                </table>
                <?php else: ?>
                <p><?php _e('No API usage data available.', 'dodo-ai-seo'); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Cache Tab -->
    <div class="debug-tab-content" id="tab-cache">
        <div class="dodo-card">
            <div class="dodo-card-header">
                <h2>💾 <?php _e('Cache Statistics', 'dodo-ai-seo'); ?></h2>
            </div>
            <div class="dodo-card-body">
                <?php if (!empty($cache_stats)): ?>
                <pre class="debug-json"><?php echo esc_html(json_encode($cache_stats, JSON_PRETTY_PRINT)); ?></pre>
                <?php else: ?>
                <p><?php _e('No cache statistics available.', 'dodo-ai-seo'); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Failed Jobs Tab -->
    <div class="debug-tab-content" id="tab-jobs">
        <div class="dodo-card">
            <div class="dodo-card-header">
                <h2>❌ <?php _e('Failed Jobs', 'dodo-ai-seo'); ?></h2>
            </div>
            <div class="dodo-card-body">
                <?php if (!empty($failed_jobs)): ?>
                <table class="debug-table">
                    <thead>
                        <tr>
                            <th><?php _e('ID', 'dodo-ai-seo'); ?></th>
                            <th><?php _e('Type', 'dodo-ai-seo'); ?></th>
                            <th><?php _e('Error', 'dodo-ai-seo'); ?></th>
                            <th><?php _e('Failed At', 'dodo-ai-seo'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($failed_jobs as $job): ?>
                        <tr>
                            <td><?php echo esc_html($job['id']); ?></td>
                            <td><?php echo esc_html($job['job_type']); ?></td>
                            <td><code><?php echo esc_html(substr($job['last_error'] ?? 'Unknown error', 0, 100)); ?></code></td>
                            <td><?php echo esc_html($job['updated_at']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p><?php _e('No failed jobs. Great!', 'dodo-ai-seo'); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Cron History Tab -->
    <div class="debug-tab-content" id="tab-cron">
        <div class="dodo-card">
            <div class="dodo-card-header">
                <h2>⏰ <?php _e('Cron History (Last 10)', 'dodo-ai-seo'); ?></h2>
            </div>
            <div class="dodo-card-body">
                <?php if (!empty($cron_history)): ?>
                <table class="debug-table">
                    <thead>
                        <tr>
                            <th><?php _e('Job', 'dodo-ai-seo'); ?></th>
                            <th><?php _e('Status', 'dodo-ai-seo'); ?></th>
                            <th><?php _e('Duration', 'dodo-ai-seo'); ?></th>
                            <th><?php _e('Started At', 'dodo-ai-seo'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cron_history as $log): ?>
                        <tr>
                            <td><?php echo esc_html($log['job_name']); ?></td>
                            <td><span class="status-badge status-<?php echo esc_attr($log['status']); ?>"><?php echo esc_html($log['status']); ?></span></td>
                            <td><?php echo esc_html($log['duration']); ?>s</td>
                            <td><?php echo esc_html($log['started_at']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p><?php _e('No cron history available.', 'dodo-ai-seo'); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Learning Tab -->
    <div class="debug-tab-content" id="tab-learning">
        <div class="dodo-card">
            <div class="dodo-card-header">
                <h2>🧠 <?php _e('Learning Evolution (Last 10)', 'dodo-ai-seo'); ?></h2>
            </div>
            <div class="dodo-card-body">
                <?php if (!empty($learning_events)): ?>
                <table class="debug-table">
                    <thead>
                        <tr>
                            <th><?php _e('Event', 'dodo-ai-seo'); ?></th>
                            <th><?php _e('Confidence', 'dodo-ai-seo'); ?></th>
                            <th><?php _e('Impact', 'dodo-ai-seo'); ?></th>
                            <th><?php _e('Created At', 'dodo-ai-seo'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($learning_events as $event): ?>
                        <tr>
                            <td><?php echo esc_html($event['event_type']); ?></td>
                            <td><?php echo esc_html($event['confidence'] ?? 'N/A'); ?></td>
                            <td><?php echo esc_html($event['impact'] ?? 'N/A'); ?></td>
                            <td><?php echo esc_html($event['created_at']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p><?php _e('No learning events available.', 'dodo-ai-seo'); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.dodo-debug-wrap {
    background: #f5f5f5;
    padding: 20px;
    margin: 20px 0;
}

.dodo-debug-tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    border-bottom: 2px solid #ddd;
}

.debug-tab {
    padding: 12px 24px;
    background: #fff;
    border: 1px solid #ddd;
    border-bottom: none;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.3s;
}

.debug-tab:hover {
    background: #f0f0f0;
}

.debug-tab.active {
    background: #2271b1;
    color: #fff;
    border-color: #2271b1;
}

.debug-tab-content {
    display: none;
}

.debug-tab-content.active {
    display: block;
}

.debug-table {
    width: 100%;
    border-collapse: collapse;
}

.debug-table th,
.debug-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

.debug-table th {
    background: #f9f9f9;
    font-weight: 600;
    width: 200px;
}

.debug-table code {
    background: #f5f5f5;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 12px;
}

.debug-json {
    background: #1e1e1e;
    color: #ce9178;
    padding: 20px;
    border-radius: 4px;
    overflow-x: auto;
    font-size: 13px;
    line-height: 1.6;
}

.status-badge {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.status-completed {
    background: #d4edda;
    color: #155724;
}

.status-failed {
    background: #f8d7da;
    color: #721c24;
}

.status-running {
    background: #d1ecf1;
    color: #0c5460;
}
</style>

<script>
jQuery(document).ready(function($) {
    $('.debug-tab').on('click', function() {
        const tab = $(this).data('tab');
        
        $('.debug-tab').removeClass('active');
        $(this).addClass('active');
        
        $('.debug-tab-content').removeClass('active');
        $('#tab-' + tab).addClass('active');
    });
});
</script>
