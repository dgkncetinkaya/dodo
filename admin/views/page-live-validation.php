<?php
/**
 * Live Validation Dashboard
 * 
 * FINAL PRODUCTION TRANSITION
 * Real-time production system validation
 * 
 * @package DODO_AI_SEO
 * @since 4.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!current_user_can('manage_options')) {
    wp_die(__('Bu sayfaya erişim yetkiniz yok.', 'dodo-ai-seo'));
}

// Get live status
$live_status = DODO_Live_Validator::get_live_status();
$cron_health = $live_status['cron_health'];
$tracking = $live_status['tracking_status'];
$learning = $live_status['learning_status'];
$queue = $live_status['queue_health'];
$gsc = $live_status['gsc_health'];
$metrics = $live_status['production_metrics'];
$readiness = $live_status['live_readiness'];

// Get content lists
$growing = DODO_Live_Validator::get_growth_candidates();
$declining = DODO_Live_Validator::get_declining_content();
$refresh = DODO_Live_Validator::get_refresh_candidates();
?>

<div class="wrap dodo-live-validation">
    <h1>🚀 <?php _e('Live Production Validation', 'dodo-ai-seo'); ?></h1>
    <p class="description"><?php _e('Real-time production system health and learning status', 'dodo-ai-seo'); ?></p>
    
    <!-- Overall Readiness -->
    <div class="dodo-card dodo-readiness-card">
        <div class="readiness-score-circle score-<?php echo esc_attr($readiness['status']); ?>">
            <div class="score-value"><?php echo esc_html($readiness['percentage']); ?>%</div>
            <div class="score-label"><?php _e('Live Ready', 'dodo-ai-seo'); ?></div>
        </div>
        <div class="readiness-info">
            <h2><?php echo esc_html($readiness['recommendation']); ?></h2>
            <p><?php _e('Last checked:', 'dodo-ai-seo'); ?> <?php echo esc_html($live_status['timestamp']); ?></p>
        </div>
    </div>
    
    <!-- System Health Grid -->
    <div class="health-grid">
        
        <!-- Cron Health -->
        <div class="dodo-card health-card">
            <div class="card-header">
                <h3>⏰ <?php _e('Cron Health', 'dodo-ai-seo'); ?></h3>
                <span class="status-badge status-<?php echo esc_attr($cron_health['status']); ?>">
                    <?php echo esc_html($cron_health['health_percentage']); ?>%
                </span>
            </div>
            <div class="card-body">
                <div class="stat-row">
                    <span><?php _e('Active Crons:', 'dodo-ai-seo'); ?></span>
                    <strong><?php echo esc_html($cron_health['active_count']); ?> / <?php echo esc_html($cron_health['total_count']); ?></strong>
                </div>
                
                <div class="cron-list">
                    <?php foreach ($cron_health['crons'] as $name => $cron): ?>
                    <div class="cron-item">
                        <div class="cron-status <?php echo $cron['active'] ? 'active' : 'inactive'; ?>"></div>
                        <div class="cron-info">
                            <div class="cron-name"><?php echo esc_html($name); ?></div>
                            <div class="cron-next"><?php echo esc_html($cron['time_until']); ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Tracking Status -->
        <div class="dodo-card health-card">
            <div class="card-header">
                <h3>📊 <?php _e('Tracking Status', 'dodo-ai-seo'); ?></h3>
                <span class="status-badge status-<?php echo esc_attr($tracking['status']); ?>">
                    <?php echo esc_html(ucfirst($tracking['status'])); ?>
                </span>
            </div>
            <div class="card-body">
                <div class="stat-row">
                    <span><?php _e('Tracked Content:', 'dodo-ai-seo'); ?></span>
                    <strong><?php echo esc_html($tracking['tracked_content_count']); ?></strong>
                </div>
                <div class="stat-row">
                    <span><?php _e('Baseline Snapshots:', 'dodo-ai-seo'); ?></span>
                    <strong><?php echo esc_html($tracking['baseline_snapshots']); ?></strong>
                </div>
                <div class="stat-row">
                    <span><?php _e('Recent Activity:', 'dodo-ai-seo'); ?></span>
                    <strong><?php echo esc_html($tracking['recent_activity']); ?></strong>
                </div>
            </div>
        </div>
        
        <!-- Learning Status -->
        <div class="dodo-card health-card">
            <div class="card-header">
                <h3>🧠 <?php _e('Learning Status', 'dodo-ai-seo'); ?></h3>
                <span class="status-badge status-<?php echo esc_attr($learning['status']); ?>">
                    <?php echo esc_html(ucfirst($learning['learning_mode'])); ?>
                </span>
            </div>
            <div class="card-body">
                <div class="stat-row">
                    <span><?php _e('Total Events:', 'dodo-ai-seo'); ?></span>
                    <strong><?php echo esc_html($learning['total_events']); ?></strong>
                </div>
                <div class="stat-row">
                    <span><?php _e('Recent Events (7d):', 'dodo-ai-seo'); ?></span>
                    <strong><?php echo esc_html($learning['recent_events']); ?></strong>
                </div>
                <div class="stat-row">
                    <span><?php _e('Winner Detections:', 'dodo-ai-seo'); ?></span>
                    <strong><?php echo esc_html($learning['winner_detections']); ?></strong>
                </div>
            </div>
        </div>
        
        <!-- Queue Health -->
        <div class="dodo-card health-card">
            <div class="card-header">
                <h3>📦 <?php _e('Queue Health', 'dodo-ai-seo'); ?></h3>
                <span class="status-badge status-<?php echo esc_attr($queue['status']); ?>">
                    <?php echo esc_html(ucfirst($queue['status'])); ?>
                </span>
            </div>
            <div class="card-body">
                <div class="stat-row">
                    <span><?php _e('Queue Size:', 'dodo-ai-seo'); ?></span>
                    <strong><?php echo esc_html($queue['queue_size']); ?></strong>
                </div>
                <div class="stat-row">
                    <span><?php _e('Failed Jobs:', 'dodo-ai-seo'); ?></span>
                    <strong><?php echo esc_html($queue['failed']); ?></strong>
                </div>
                <div class="stat-row">
                    <span><?php _e('Stuck Jobs:', 'dodo-ai-seo'); ?></span>
                    <strong><?php echo esc_html($queue['stuck_jobs']); ?></strong>
                </div>
            </div>
        </div>
        
        <!-- GSC Health -->
        <div class="dodo-card health-card">
            <div class="card-header">
                <h3>🔍 <?php _e('GSC Health', 'dodo-ai-seo'); ?></h3>
                <span class="status-badge status-<?php echo esc_attr($gsc['status']); ?>">
                    <?php echo esc_html(ucfirst($gsc['status'])); ?>
                </span>
            </div>
            <div class="card-body">
                <div class="stat-row">
                    <span><?php _e('Configured:', 'dodo-ai-seo'); ?></span>
                    <strong><?php echo $gsc['configured'] ? '✓ Yes' : '✗ No'; ?></strong>
                </div>
                <div class="stat-row">
                    <span><?php _e('Last Fetch:', 'dodo-ai-seo'); ?></span>
                    <strong><?php echo esc_html($gsc['last_fetch']); ?></strong>
                </div>
                <div class="stat-row">
                    <span><?php _e('Hours Since:', 'dodo-ai-seo'); ?></span>
                    <strong><?php echo esc_html($gsc['hours_since_fetch']); ?>h</strong>
                </div>
            </div>
        </div>
        
        <!-- Production Metrics -->
        <div class="dodo-card health-card">
            <div class="card-header">
                <h3>📈 <?php _e('Production Metrics', 'dodo-ai-seo'); ?></h3>
            </div>
            <div class="card-body">
                <div class="stat-row">
                    <span><?php _e('AI Calls Today:', 'dodo-ai-seo'); ?></span>
                    <strong><?php echo esc_html($metrics['ai_calls_today']); ?></strong>
                </div>
                <div class="stat-row">
                    <span><?php _e('AI Cost Today:', 'dodo-ai-seo'); ?></span>
                    <strong>$<?php echo esc_html(number_format($metrics['ai_cost_today'], 4)); ?></strong>
                </div>
                <div class="stat-row">
                    <span><?php _e('Memory Usage:', 'dodo-ai-seo'); ?></span>
                    <strong><?php echo esc_html($metrics['memory_usage_mb']); ?> MB</strong>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Content Performance -->
    <div class="content-performance">
        <h2><?php _e('Content Performance', 'dodo-ai-seo'); ?></h2>
        
        <div class="performance-grid">
            <!-- Growing Content -->
            <div class="dodo-card">
                <div class="card-header">
                    <h3>📈 <?php _e('Top Growing Content', 'dodo-ai-seo'); ?></h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($growing)): ?>
                    <table class="content-table">
                        <?php foreach ($growing as $post): ?>
                        <tr>
                            <td><a href="<?php echo get_edit_post_link($post['ID']); ?>"><?php echo esc_html($post['post_title']); ?></a></td>
                            <td class="growth-positive">+<?php echo esc_html($post['growth_rate']); ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                    <?php else: ?>
                    <p class="no-data"><?php _e('No growth data yet. Keep tracking!', 'dodo-ai-seo'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Declining Content -->
            <div class="dodo-card">
                <div class="card-header">
                    <h3>📉 <?php _e('Declining Content', 'dodo-ai-seo'); ?></h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($declining)): ?>
                    <table class="content-table">
                        <?php foreach ($declining as $post): ?>
                        <tr>
                            <td><a href="<?php echo get_edit_post_link($post['ID']); ?>"><?php echo esc_html($post['post_title']); ?></a></td>
                            <td class="growth-negative"><?php echo esc_html($post['growth_rate']); ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                    <?php else: ?>
                    <p class="no-data"><?php _e('No declining content detected.', 'dodo-ai-seo'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Refresh Candidates -->
            <div class="dodo-card">
                <div class="card-header">
                    <h3>🔄 <?php _e('Refresh Candidates', 'dodo-ai-seo'); ?></h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($refresh)): ?>
                    <table class="content-table">
                        <?php foreach ($refresh as $post): ?>
                        <tr>
                            <td><a href="<?php echo get_edit_post_link($post['ID']); ?>"><?php echo esc_html($post['post_title']); ?></a></td>
                            <td><span class="refresh-badge">Refresh</span></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                    <?php else: ?>
                    <p class="no-data"><?php _e('No refresh candidates yet.', 'dodo-ai-seo'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.dodo-live-validation {
    background: #f5f5f5;
    padding: 20px;
    margin: 20px 0;
}

.dodo-readiness-card {
    display: flex;
    align-items: center;
    gap: 30px;
    padding: 30px;
    margin-bottom: 30px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
}

.readiness-score-circle {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    background: rgba(255,255,255,0.2);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    border: 4px solid rgba(255,255,255,0.3);
}

.score-value {
    font-size: 48px;
    font-weight: bold;
}

.score-label {
    font-size: 14px;
    opacity: 0.9;
}

.readiness-info h2 {
    margin: 0 0 10px 0;
    color: #fff;
}

.health-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.health-card .card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    border-bottom: 1px solid #eee;
}

.health-card .card-header h3 {
    margin: 0;
    font-size: 16px;
}

.status-badge {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.status-healthy, .status-active, .status-ready {
    background: #d4edda;
    color: #155724;
}

.status-degraded, .status-almost_ready {
    background: #fff3cd;
    color: #856404;
}

.status-inactive, .status-not_ready, .status-idle {
    background: #f8d7da;
    color: #721c24;
}

.stat-row {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #f0f0f0;
}

.cron-list {
    margin-top: 15px;
}

.cron-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 0;
    border-bottom: 1px solid #f0f0f0;
}

.cron-status {
    width: 10px;
    height: 10px;
    border-radius: 50%;
}

.cron-status.active {
    background: #28a745;
}

.cron-status.inactive {
    background: #dc3545;
}

.cron-info {
    flex: 1;
}

.cron-name {
    font-size: 13px;
    font-weight: 500;
}

.cron-next {
    font-size: 12px;
    color: #666;
}

.performance-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 20px;
}

.content-table {
    width: 100%;
}

.content-table tr {
    border-bottom: 1px solid #f0f0f0;
}

.content-table td {
    padding: 10px 0;
}

.growth-positive {
    color: #28a745;
    font-weight: 600;
}

.growth-negative {
    color: #dc3545;
    font-weight: 600;
}

.refresh-badge {
    background: #17a2b8;
    color: #fff;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
}

.no-data {
    text-align: center;
    color: #999;
    padding: 20px;
}
</style>
