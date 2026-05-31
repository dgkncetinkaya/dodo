<?php
/**
 * System Health Check Page
 * 
 * @package DODO_AI_SEO
 * @since 3.0.0 (Sprint 5 - Task 6)
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!current_user_can('manage_options')) {
    wp_die(__('Bu sayfaya erişim yetkiniz yok.', 'dodo-ai-seo'));
}

// Get health data
$db_health = DODO_Database::get_health();
$cron_jobs = array(
    'dodo_daily_analytics_snapshot' => wp_next_scheduled('dodo_daily_analytics_snapshot'),
    'dodo_process_job_queue' => wp_next_scheduled('dodo_process_job_queue'),
    'dodo_process_publishing_pipeline' => wp_next_scheduled('dodo_process_publishing_pipeline'),
);

// Check Sprint 5 classes
$sprint5_classes = array(
    'DODO_GEO_Engine' => class_exists('DODO_GEO_Engine'),
    'DODO_Answer_Block_Engine' => class_exists('DODO_Answer_Block_Engine'),
    'DODO_Content_Intelligence' => class_exists('DODO_Content_Intelligence'),
    'DODO_Humanizer' => class_exists('DODO_Humanizer'),
    'DODO_Publishing_Pipeline' => class_exists('DODO_Publishing_Pipeline'),
    'DODO_Analytics_Aggregator' => class_exists('DODO_Analytics_Aggregator'),
);

$all_classes_loaded = !in_array(false, $sprint5_classes);
?>

<div class="wrap dodo-admin-wrap">
    <div class="dodo-page-header">
        <div class="dodo-page-header-content">
            <h1 class="dodo-page-title">
                <svg class="dodo-page-title-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
                <?php _e('System Health', 'dodo-ai-seo'); ?>
            </h1>
            <p class="dodo-page-description">
                <?php _e('Database, cron jobs ve Sprint 5 entegrasyonlarını kontrol edin', 'dodo-ai-seo'); ?>
            </p>
        </div>
    </div>
    
    <div class="dodo-admin-container">
        <!-- Overall Status -->
        <div class="dodo-card dodo-health-status-card">
            <div class="dodo-health-status-icon <?php echo ($db_health['status'] === 'healthy' && $all_classes_loaded) ? 'healthy' : 'warning'; ?>">
                <?php if ($db_health['status'] === 'healthy' && $all_classes_loaded): ?>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                <?php else: ?>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                        <line x1="12" y1="9" x2="12" y2="13"/>
                        <line x1="12" y1="17" x2="12.01" y2="17"/>
                    </svg>
                <?php endif; ?>
            </div>
            <div class="dodo-health-status-content">
                <h2 class="dodo-health-status-title">
                    <?php echo ($db_health['status'] === 'healthy' && $all_classes_loaded) ? __('Sistem Sağlıklı', 'dodo-ai-seo') : __('Dikkat Gerekli', 'dodo-ai-seo'); ?>
                </h2>
                <p class="dodo-health-status-description">
                    <?php 
                    if ($db_health['status'] === 'healthy' && $all_classes_loaded) {
                        _e('Tüm sistemler çalışıyor', 'dodo-ai-seo');
                    } else {
                        _e('Bazı bileşenler dikkat gerektiriyor', 'dodo-ai-seo');
                    }
                    ?>
                </p>
            </div>
        </div>
        
        <!-- Database Health -->
        <div class="dodo-card">
            <div class="dodo-card-header">
                <h2 class="dodo-card-title">
                    🗄️ <?php _e('Database Health', 'dodo-ai-seo'); ?>
                </h2>
                <span class="dodo-health-badge <?php echo $db_health['status']; ?>">
                    <?php echo $db_health['status'] === 'healthy' ? __('Sağlıklı', 'dodo-ai-seo') : __('Uyarı', 'dodo-ai-seo'); ?>
                </span>
            </div>
            <div class="dodo-card-body">
                <div class="dodo-health-metrics">
                    <div class="dodo-health-metric">
                        <div class="dodo-health-metric-label"><?php _e('Schema Version', 'dodo-ai-seo'); ?></div>
                        <div class="dodo-health-metric-value">
                            <?php echo $db_health['schema_version']; ?> / <?php echo $db_health['latest_version']; ?>
                        </div>
                    </div>
                    
                    <div class="dodo-health-metric">
                        <div class="dodo-health-metric-label"><?php _e('Tables', 'dodo-ai-seo'); ?></div>
                        <div class="dodo-health-metric-value">
                            <?php echo $db_health['tables']['existing']; ?> / <?php echo $db_health['tables']['total']; ?>
                        </div>
                    </div>
                    
                    <div class="dodo-health-metric">
                        <div class="dodo-health-metric-label"><?php _e('Database Size', 'dodo-ai-seo'); ?></div>
                        <div class="dodo-health-metric-value">
                            <?php echo $db_health['size_mb']; ?> MB
                        </div>
                    </div>
                    
                    <div class="dodo-health-metric">
                        <div class="dodo-health-metric-label"><?php _e('Migration Status', 'dodo-ai-seo'); ?></div>
                        <div class="dodo-health-metric-value">
                            <?php echo $db_health['needs_migration'] ? __('Gerekli', 'dodo-ai-seo') : __('Güncel', 'dodo-ai-seo'); ?>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($db_health['tables']['missing'])): ?>
                <div class="dodo-health-warning">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                        <line x1="12" y1="9" x2="12" y2="13"/>
                        <line x1="12" y1="17" x2="12.01" y2="17"/>
                    </svg>
                    <div>
                        <strong><?php _e('Eksik Tablolar:', 'dodo-ai-seo'); ?></strong>
                        <ul>
                            <?php foreach ($db_health['tables']['missing'] as $table): ?>
                                <li><?php echo esc_html($table); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="dodo-health-actions">
                    <?php if ($db_health['needs_migration']): ?>
                    <button type="button" id="run-migration" class="dodo-btn-primary">
                        <?php _e('Run Migration', 'dodo-ai-seo'); ?>
                    </button>
                    <?php endif; ?>
                    
                    <?php if (!$db_health['tables']['all_exist']): ?>
                    <button type="button" id="repair-tables" class="dodo-btn-secondary">
                        <?php _e('Repair Tables', 'dodo-ai-seo'); ?>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Cron Jobs -->
        <div class="dodo-card">
            <div class="dodo-card-header">
                <h2 class="dodo-card-title">
                    ⏰ <?php _e('Cron Jobs', 'dodo-ai-seo'); ?>
                </h2>
            </div>
            <div class="dodo-card-body">
                <div class="dodo-cron-jobs-list">
                    <?php foreach ($cron_jobs as $job_name => $next_run): ?>
                    <div class="dodo-cron-job-item">
                        <div class="dodo-cron-job-status <?php echo $next_run ? 'active' : 'inactive'; ?>">
                            <?php if ($next_run): ?>
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="20 6 9 17 4 12"/>
                                </svg>
                            <?php else: ?>
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="18" y1="6" x2="6" y2="18"/>
                                    <line x1="6" y1="6" x2="18" y2="18"/>
                                </svg>
                            <?php endif; ?>
                        </div>
                        <div class="dodo-cron-job-info">
                            <div class="dodo-cron-job-name"><?php echo esc_html($job_name); ?></div>
                            <div class="dodo-cron-job-next">
                                <?php 
                                if ($next_run) {
                                    $time_until = human_time_diff(time(), $next_run);
                                    printf(__('Sonraki çalışma: %s', 'dodo-ai-seo'), $time_until);
                                } else {
                                    _e('Zamanlanmamış', 'dodo-ai-seo');
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Production Validation -->
        <div class="dodo-card">
            <div class="dodo-card-header">
                <h2 class="dodo-card-title">
                    🧪 <?php _e('Production Validation', 'dodo-ai-seo'); ?>
                </h2>
            </div>
            <div class="dodo-card-body">
                <p><?php _e('Run comprehensive production validation tests to verify system readiness.', 'dodo-ai-seo'); ?></p>
                
                <div class="dodo-health-actions" style="margin-top: 20px;">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=dodo-ai-seo-production-readiness')); ?>" class="dodo-btn-primary">
                        🚀 <?php _e('Production Readiness Center', 'dodo-ai-seo'); ?>
                    </a>
                    
                    <button type="button" onclick="alert('Run: php <?php echo DODO_PLUGIN_DIR; ?>tests/run-all-tests.php\n\nOr check tests/README-TESTS.txt for instructions')" class="dodo-btn-secondary">
                        📋 <?php _e('Test Instructions', 'dodo-ai-seo'); ?>
                    </button>
                </div>
                
                <div style="margin-top: 15px; padding: 15px; background: #f0f8ff; border-left: 4px solid #0073aa; border-radius: 4px;">
                    <strong><?php _e('Test Suite Includes:', 'dodo-ai-seo'); ?></strong>
                    <ul style="margin: 10px 0 0 20px;">
                        <li><?php _e('Learning ON vs OFF validation', 'dodo-ai-seo'); ?></li>
                        <li><?php _e('Real SEO impact tracking', 'dodo-ai-seo'); ?></li>
                        <li><?php _e('Stress test (100+ jobs)', 'dodo-ai-seo'); ?></li>
                        <li><?php _e('Long run stability', 'dodo-ai-seo'); ?></li>
                        <li><?php _e('Learning governance', 'dodo-ai-seo'); ?></li>
                        <li><?php _e('Cache, GEO, security validation', 'dodo-ai-seo'); ?></li>
                        <li><?php _e('Final regression test', 'dodo-ai-seo'); ?></li>
                    </ul>
                </div>
            </div>
        </div>
            </div>
        </div>
        
        <!-- Sprint 5 Classes -->
        <div class="dodo-card">
            <div class="dodo-card-header">
                <h2 class="dodo-card-title">
                    🚀 <?php _e('Sprint 5 Components', 'dodo-ai-seo'); ?>
                </h2>
                <span class="dodo-health-badge <?php echo $all_classes_loaded ? 'healthy' : 'warning'; ?>">
                    <?php echo $all_classes_loaded ? __('Yüklü', 'dodo-ai-seo') : __('Eksik', 'dodo-ai-seo'); ?>
                </span>
            </div>
            <div class="dodo-card-body">
                <div class="dodo-components-list">
                    <?php foreach ($sprint5_classes as $class_name => $loaded): ?>
                    <div class="dodo-component-item">
                        <div class="dodo-component-status <?php echo $loaded ? 'loaded' : 'missing'; ?>">
                            <?php if ($loaded): ?>
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="20 6 9 17 4 12"/>
                                </svg>
                            <?php else: ?>
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="18" y1="6" x2="6" y2="18"/>
                                    <line x1="6" y1="6" x2="18" y2="18"/>
                                </svg>
                            <?php endif; ?>
                        </div>
                        <div class="dodo-component-name"><?php echo esc_html($class_name); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.dodo-health-status-card {
    display: flex;
    align-items: center;
    gap: var(--dodo-space-6);
    padding: var(--dodo-space-6);
    margin-bottom: var(--dodo-space-6);
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.dodo-health-status-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.dodo-health-status-icon.healthy {
    background: rgba(16, 185, 129, 0.2);
}

.dodo-health-status-icon.warning {
    background: rgba(245, 158, 11, 0.2);
}

.dodo-health-status-icon svg {
    width: 40px;
    height: 40px;
}

.dodo-health-status-title {
    font-size: 24px;
    font-weight: 700;
    margin: 0 0 var(--dodo-space-2) 0;
    color: white;
}

.dodo-health-status-description {
    margin: 0;
    opacity: 0.9;
}

.dodo-health-badge {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.dodo-health-badge.healthy {
    background: rgba(16, 185, 129, 0.2);
    color: #10b981;
}

.dodo-health-badge.warning {
    background: rgba(245, 158, 11, 0.2);
    color: #f59e0b;
}

.dodo-health-metrics {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--dodo-space-4);
    margin-bottom: var(--dodo-space-6);
}

.dodo-health-metric {
    padding: var(--dodo-space-4);
    background: var(--dodo-bg-secondary);
    border-radius: var(--dodo-radius-md);
    text-align: center;
}

.dodo-health-metric-label {
    font-size: 12px;
    color: var(--dodo-text-muted);
    margin-bottom: var(--dodo-space-2);
}

.dodo-health-metric-value {
    font-size: 24px;
    font-weight: 700;
    color: var(--dodo-text-primary);
}

.dodo-health-warning {
    display: flex;
    gap: var(--dodo-space-3);
    padding: var(--dodo-space-4);
    background: #fef3c7;
    border-left: 4px solid #f59e0b;
    border-radius: var(--dodo-radius-md);
    margin-bottom: var(--dodo-space-4);
}

.dodo-health-warning ul {
    margin: var(--dodo-space-2) 0 0 var(--dodo-space-4);
}

.dodo-health-actions {
    display: flex;
    gap: var(--dodo-space-3);
}

.dodo-cron-jobs-list,
.dodo-components-list {
    display: flex;
    flex-direction: column;
    gap: var(--dodo-space-3);
}

.dodo-cron-job-item,
.dodo-component-item {
    display: flex;
    align-items: center;
    gap: var(--dodo-space-3);
    padding: var(--dodo-space-3);
    background: var(--dodo-bg-secondary);
    border-radius: var(--dodo-radius-md);
}

.dodo-cron-job-status,
.dodo-component-status {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.dodo-cron-job-status.active,
.dodo-component-status.loaded {
    background: rgba(16, 185, 129, 0.1);
    color: #10b981;
}

.dodo-cron-job-status.inactive,
.dodo-component-status.missing {
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
}

.dodo-cron-job-name,
.dodo-component-name {
    font-weight: 600;
    color: var(--dodo-text-primary);
}

.dodo-cron-job-next {
    font-size: 13px;
    color: var(--dodo-text-muted);
}
</style>

<script>
jQuery(document).ready(function($) {
    $('#run-migration').on('click', function() {
        if (!confirm('Database migration çalıştırılacak. Devam edilsin mi?')) {
            return;
        }
        
        const $btn = $(this);
        $btn.prop('disabled', true).text('Migration çalışıyor...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'dodo_run_migration',
                nonce: '<?php echo wp_create_nonce('dodo_health_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('✅ Migration başarıyla tamamlandı!');
                    location.reload();
                } else {
                    alert('Hata: ' + (response.data.message || 'Bilinmeyen hata'));
                    $btn.prop('disabled', false).text('Run Migration');
                }
            },
            error: function() {
                alert('AJAX hatası oluştu');
                $btn.prop('disabled', false).text('Run Migration');
            }
        });
    });
    
    $('#repair-tables').on('click', function() {
        if (!confirm('Eksik tablolar oluşturulacak. Devam edilsin mi?')) {
            return;
        }
        
        const $btn = $(this);
        $btn.prop('disabled', true).text('Repair ediliyor...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'dodo_repair_tables',
                nonce: '<?php echo wp_create_nonce('dodo_health_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('✅ Tablolar başarıyla oluşturuldu!');
                    location.reload();
                } else {
                    alert('Hata: ' + (response.data.message || 'Bilinmeyen hata'));
                    $btn.prop('disabled', false).text('Repair Tables');
                }
            },
            error: function() {
                alert('AJAX hatası oluştu');
                $btn.prop('disabled', false).text('Repair Tables');
            }
        });
    });
});
</script>
