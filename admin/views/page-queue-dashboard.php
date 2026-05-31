<?php
/**
 * Queue Dashboard View
 * 
 * @package DODO_AI_SEO
 * @since 2.0.0 (Sprint 4.5 - Redesigned)
 */

if (!defined('ABSPATH')) {
    exit;
}

$queue = new DODO_Job_Queue();
$stats = $queue->get_queue_stats();
?>

<div class="wrap dodo-admin-wrap">
    <!-- Page Header -->
    <div class="dodo-page-header">
        <div class="dodo-page-header-content">
            <h1 class="dodo-page-title"><?php _e('İş Kuyruğu', 'dodo-ai-seo'); ?></h1>
            <p class="dodo-page-description"><?php _e('Arka plan işlemlerini izleyin ve yönetin', 'dodo-ai-seo'); ?></p>
        </div>
    </div>

    <div class="dodo-admin-container">
        <!-- Stats Grid -->
        <div class="dodo-stats-grid">
            <div class="dodo-stat-card" data-stat="pending">
                <div class="dodo-stat-label"><?php _e('Bekleyen', 'dodo-ai-seo'); ?></div>
                <div class="dodo-stat-value"><?php echo esc_html($stats['pending']); ?></div>
            </div>
            
            <div class="dodo-stat-card" data-stat="processing">
                <div class="dodo-stat-label"><?php _e('İşleniyor', 'dodo-ai-seo'); ?></div>
                <div class="dodo-stat-value"><?php echo esc_html($stats['processing']); ?></div>
            </div>
            
            <div class="dodo-stat-card" data-stat="completed">
                <div class="dodo-stat-label"><?php _e('Tamamlandı', 'dodo-ai-seo'); ?></div>
                <div class="dodo-stat-value"><?php echo esc_html($stats['completed']); ?></div>
            </div>
            
            <div class="dodo-stat-card" data-stat="failed">
                <div class="dodo-stat-label"><?php _e('Başarısız', 'dodo-ai-seo'); ?></div>
                <div class="dodo-stat-value"><?php echo esc_html($stats['failed']); ?></div>
            </div>
        </div>
        
        <!-- Sprint 5: Publishing Pipeline Widget -->
        <?php
        $pipeline = new DODO_Publishing_Pipeline();
        $pipeline_stats = $pipeline->get_stats();
        $next_cron = wp_next_scheduled('dodo_process_publishing_pipeline');
        ?>
        <div class="dodo-card dodo-pipeline-widget">
            <div class="dodo-card-header">
                <h2 class="dodo-card-title">
                    📅 <?php _e('Publishing Pipeline', 'dodo-ai-seo'); ?>
                </h2>
                <span class="dodo-pipeline-status <?php echo $pipeline_stats['enabled'] ? 'active' : 'inactive'; ?>">
                    <?php echo $pipeline_stats['enabled'] ? __('Aktif', 'dodo-ai-seo') : __('Pasif', 'dodo-ai-seo'); ?>
                </span>
            </div>
            <div class="dodo-card-body">
                <div class="dodo-pipeline-stats-grid">
                    <div class="dodo-pipeline-stat">
                        <div class="dodo-pipeline-stat-label"><?php _e('Toplam Pipeline', 'dodo-ai-seo'); ?></div>
                        <div class="dodo-pipeline-stat-value"><?php echo $pipeline_stats['total']; ?></div>
                    </div>
                    
                    <div class="dodo-pipeline-stat">
                        <div class="dodo-pipeline-stat-label"><?php _e('Zamanlanmış', 'dodo-ai-seo'); ?></div>
                        <div class="dodo-pipeline-stat-value"><?php echo $pipeline_stats['scheduled']; ?></div>
                    </div>
                    
                    <div class="dodo-pipeline-stat">
                        <div class="dodo-pipeline-stat-label"><?php _e('Review Bekleyen', 'dodo-ai-seo'); ?></div>
                        <div class="dodo-pipeline-stat-value"><?php echo $pipeline_stats['pending_review']; ?></div>
                    </div>
                    
                    <div class="dodo-pipeline-stat">
                        <div class="dodo-pipeline-stat-label"><?php _e('Onaylanmış', 'dodo-ai-seo'); ?></div>
                        <div class="dodo-pipeline-stat-value"><?php echo $pipeline_stats['approved']; ?></div>
                    </div>
                    
                    <div class="dodo-pipeline-stat">
                        <div class="dodo-pipeline-stat-label"><?php _e('Yayınlandı', 'dodo-ai-seo'); ?></div>
                        <div class="dodo-pipeline-stat-value"><?php echo $pipeline_stats['published']; ?></div>
                    </div>
                    
                    <div class="dodo-pipeline-stat">
                        <div class="dodo-pipeline-stat-label"><?php _e('Başarısız', 'dodo-ai-seo'); ?></div>
                        <div class="dodo-pipeline-stat-value"><?php echo $pipeline_stats['failed']; ?></div>
                    </div>
                </div>
                
                <?php if ($next_cron): ?>
                <div class="dodo-pipeline-next-run">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                    </svg>
                    <?php 
                    $time_until = human_time_diff(time(), $next_cron);
                    printf(__('Sonraki çalışma: %s', 'dodo-ai-seo'), $time_until);
                    ?>
                </div>
                <?php endif; ?>
                
                <div class="dodo-pipeline-actions">
                    <a href="<?php echo admin_url('admin.php?page=dodo-ai-seo-publishing-scheduler'); ?>" class="dodo-btn-secondary dodo-btn-sm">
                        <?php _e('Pipeline Yönet', 'dodo-ai-seo'); ?>
                    </a>
                </div>
            </div>
        </div>

        <!-- Filters & Actions -->
        <div class="dodo-card">
            <div class="dodo-card-header">
                <h2 class="dodo-card-title"><?php _e('İşler', 'dodo-ai-seo'); ?></h2>
                <div class="dodo-btn-group">
                    <button type="button" id="refresh-queue" class="dodo-btn-secondary dodo-btn-sm">
                        <?php _e('Yenile', 'dodo-ai-seo'); ?>
                    </button>
                    <button type="button" id="clear-completed" class="dodo-btn-subtle dodo-btn-sm">
                        <?php _e('Tamamlananları Temizle', 'dodo-ai-seo'); ?>
                    </button>
                </div>
            </div>

            <div class="dodo-card-body">
                <div class="dodo-queue-filters">
                    <div class="dodo-form-group">
                        <label for="queue-status-filter" class="dodo-label"><?php _e('Durum', 'dodo-ai-seo'); ?></label>
                        <select id="queue-status-filter" class="dodo-select">
                            <option value="all"><?php _e('Tümü', 'dodo-ai-seo'); ?></option>
                            <option value="pending"><?php _e('Bekleyen', 'dodo-ai-seo'); ?></option>
                            <option value="processing"><?php _e('İşleniyor', 'dodo-ai-seo'); ?></option>
                            <option value="completed"><?php _e('Tamamlandı', 'dodo-ai-seo'); ?></option>
                            <option value="failed"><?php _e('Başarısız', 'dodo-ai-seo'); ?></option>
                        </select>
                    </div>
                    
                    <div class="dodo-form-group">
                        <label for="queue-type-filter" class="dodo-label"><?php _e('Tip', 'dodo-ai-seo'); ?></label>
                        <select id="queue-type-filter" class="dodo-select">
                            <option value="all"><?php _e('Tümü', 'dodo-ai-seo'); ?></option>
                            <option value="blog_generation"><?php _e('Blog Üretimi', 'dodo-ai-seo'); ?></option>
                            <option value="analytics_snapshot"><?php _e('Analytics', 'dodo-ai-seo'); ?></option>
                            <option value="cluster_analysis"><?php _e('Cluster Analizi', 'dodo-ai-seo'); ?></option>
                            <option value="content_audit"><?php _e('İçerik Denetimi', 'dodo-ai-seo'); ?></option>
                            <option value="bulk_improvement"><?php _e('Toplu İyileştirme', 'dodo-ai-seo'); ?></option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Queue Table -->
        <div class="dodo-card">
            <div class="dodo-queue-table-wrapper">
                <table class="dodo-queue-table" id="queue-table">
                    <thead>
                        <tr>
                            <th><?php _e('ID', 'dodo-ai-seo'); ?></th>
                            <th><?php _e('Tip', 'dodo-ai-seo'); ?></th>
                            <th><?php _e('Durum', 'dodo-ai-seo'); ?></th>
                            <th><?php _e('İlerleme', 'dodo-ai-seo'); ?></th>
                            <th class="col-priority"><?php _e('Öncelik', 'dodo-ai-seo'); ?></th>
                            <th class="col-retries"><?php _e('Deneme', 'dodo-ai-seo'); ?></th>
                            <th class="col-created"><?php _e('Oluşturulma', 'dodo-ai-seo'); ?></th>
                            <th><?php _e('İşlemler', 'dodo-ai-seo'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="queue-table-body">
                        <tr class="loading-row">
                            <td colspan="8">
                                <div class="loading-state">
                                    <div class="dodo-spinner"></div>
                                    <p><?php _e('Kuyruk yükleniyor...', 'dodo-ai-seo'); ?></p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Job Detail Modal -->
        <div id="job-detail-modal" class="dodo-modal" style="display: none;">
            <div class="dodo-modal-overlay"></div>
            <div class="dodo-modal-content">
                <div class="dodo-modal-header">
                    <h2><?php _e('İş Detayları', 'dodo-ai-seo'); ?></h2>
                    <button type="button" class="dodo-modal-close">&times;</button>
                </div>
                <div class="dodo-modal-body" id="job-detail-content">
                    <!-- Populated by JS -->
                </div>
            </div>
        </div>
    </div>
</div>
