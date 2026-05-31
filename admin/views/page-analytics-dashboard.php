<?php
/**
 * Analytics Dashboard Page
 * 
 * @package DODO_AI_SEO
 * @since 2.0.0 (Sprint 3 - Task 5)
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!current_user_can('manage_options')) {
    wp_die(__('Bu sayfaya erişim yetkiniz yok.', 'dodo-ai-seo'));
}

// Get historical data
global $wpdb;
$table_name = $wpdb->prefix . 'dodo_analytics_history';

// Check if table exists
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;

// Get last 30 days of data
$thirty_days_ago = date('Y-m-d', strtotime('-30 days'));
$analytics_data = array();

if ($table_exists) {
    $analytics_data = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name WHERE recorded_at >= %s ORDER BY recorded_at ASC",
        $thirty_days_ago
    ), ARRAY_A);
}

// Get Sprint 5 metrics
$aggregator = new DODO_Analytics_Aggregator();
$sprint5_metrics = $aggregator->get_site_metrics();

// Calculate summary stats
$total_posts = wp_count_posts('post')->publish;
$total_pages = wp_count_posts('page')->publish;
$avg_health_score = 0;
$avg_seo_score = 0;
$avg_ai_risk = 0;

if (!empty($analytics_data)) {
    $health_scores = array_column($analytics_data, 'health_score');
    $seo_scores = array_column($analytics_data, 'seo_score');
    $ai_risks = array_column($analytics_data, 'ai_risk_score');
    
    $avg_health_score = !empty($health_scores) ? round(array_sum($health_scores) / count($health_scores)) : 0;
    $avg_seo_score = !empty($seo_scores) ? round(array_sum($seo_scores) / count($seo_scores)) : 0;
    $avg_ai_risk = !empty($ai_risks) ? round(array_sum($ai_risks) / count($ai_risks)) : 0;
}
?>

<div class="wrap dodo-admin-wrap">
    <div class="dodo-page-header">
        <div class="dodo-page-header-content">
            <h1 class="dodo-page-title">
                <svg class="dodo-page-title-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/></svg>
                <?php _e('Analitik Paneli', 'dodo-ai-seo'); ?>
            </h1>
            <p class="dodo-page-description">
                <?php _e('İçerik kalitesi ve performans trendlerini takip edin', 'dodo-ai-seo'); ?>
            </p>
        </div>
    </div>
    
    <div class="dodo-admin-container">
        <div class="dodo-analytics-container">
        <!-- Summary Cards -->
        <div class="dodo-analytics-summary">
            <div class="dodo-summary-card">
                <div class="dodo-summary-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
                </div>
                <div class="dodo-summary-content">
                    <div class="dodo-summary-label"><?php _e('Toplam İçerik', 'dodo-ai-seo'); ?></div>
                    <div class="dodo-summary-value"><?php echo $total_posts + $total_pages; ?></div>
                </div>
            </div>
            
            <div class="dodo-summary-card">
                <div class="dodo-summary-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                </div>
                <div class="dodo-summary-content">
                    <div class="dodo-summary-label"><?php _e('Ort. Sağlık Skoru', 'dodo-ai-seo'); ?></div>
                    <div class="dodo-summary-value"><?php echo $avg_health_score; ?></div>
                </div>
            </div>
            
            <div class="dodo-summary-card">
                <div class="dodo-summary-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                </div>
                <div class="dodo-summary-content">
                    <div class="dodo-summary-label"><?php _e('Ort. SEO Skoru', 'dodo-ai-seo'); ?></div>
                    <div class="dodo-summary-value"><?php echo $avg_seo_score; ?></div>
                </div>
            </div>
            
            <div class="dodo-summary-card">
                <div class="dodo-summary-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                </div>
                <div class="dodo-summary-content">
                    <div class="dodo-summary-label"><?php _e('Ort. AI Risk', 'dodo-ai-seo'); ?></div>
                    <div class="dodo-summary-value"><?php echo $avg_ai_risk; ?></div>
                </div>
            </div>
        </div>
        
        <!-- Sprint 5 Metrics Cards -->
        <div class="dodo-analytics-summary" style="margin-top: var(--dodo-space-6);">
            <div class="dodo-summary-card">
                <div class="dodo-summary-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                </div>
                <div class="dodo-summary-content">
                    <div class="dodo-summary-label"><?php _e('GEO Score', 'dodo-ai-seo'); ?></div>
                    <div class="dodo-summary-value"><?php echo $sprint5_metrics['geo_metrics']['avg_geo_score']; ?></div>
                    <div class="dodo-summary-sublabel"><?php echo $sprint5_metrics['geo_metrics']['total_analyzed']; ?> analiz</div>
                </div>
            </div>
            
            <div class="dodo-summary-card">
                <div class="dodo-summary-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                </div>
                <div class="dodo-summary-content">
                    <div class="dodo-summary-label"><?php _e('Answerability', 'dodo-ai-seo'); ?></div>
                    <div class="dodo-summary-value"><?php echo $sprint5_metrics['geo_metrics']['avg_answerability']; ?></div>
                </div>
            </div>
            
            <div class="dodo-summary-card">
                <div class="dodo-summary-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <div class="dodo-summary-content">
                    <div class="dodo-summary-label"><?php _e('Humanization', 'dodo-ai-seo'); ?></div>
                    <div class="dodo-summary-value"><?php echo $sprint5_metrics['humanization_metrics']['avg_humanization']; ?></div>
                    <div class="dodo-summary-sublabel"><?php echo $sprint5_metrics['humanization_metrics']['total_analyzed']; ?> analiz</div>
                </div>
            </div>
            
            <div class="dodo-summary-card">
                <div class="dodo-summary-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                <div class="dodo-summary-content">
                    <div class="dodo-summary-label"><?php _e('Pipeline', 'dodo-ai-seo'); ?></div>
                    <div class="dodo-summary-value"><?php echo $sprint5_metrics['publishing_metrics']['total_in_pipeline']; ?></div>
                    <div class="dodo-summary-sublabel"><?php echo $sprint5_metrics['publishing_metrics']['scheduled']; ?> zamanlanmış</div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div style="margin-bottom: var(--dodo-space-6);">
            <button type="button" id="dodo-refresh-analytics" class="dodo-btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2v6h-6"/><path d="M3 12a9 9 0 0 1 15-6.7L21 8"/><path d="M3 22v-6h6"/><path d="M21 12a9 9 0 0 1-15 6.7L3 16"/></svg>
                <?php _e('Analitiği Yenile', 'dodo-ai-seo'); ?>
            </button>
        </div>
        
        <!-- Charts -->
        <div class="dodo-analytics-charts">
            <div class="dodo-chart-card">
                <div class="dodo-chart-header">
                    <h3 class="dodo-chart-title"><?php _e('Sağlık Skoru Trendi', 'dodo-ai-seo'); ?></h3>
                </div>
                <div class="dodo-chart-body">
                    <canvas id="dodo-health-chart"></canvas>
                </div>
            </div>
            
            <div class="dodo-chart-card">
                <div class="dodo-chart-header">
                    <h3 class="dodo-chart-title"><?php _e('SEO & Semantik Trendi', 'dodo-ai-seo'); ?></h3>
                </div>
                <div class="dodo-chart-body">
                    <canvas id="dodo-seo-semantic-chart"></canvas>
                </div>
            </div>
            
            <div class="dodo-chart-card">
                <div class="dodo-chart-header">
                    <h3 class="dodo-chart-title"><?php _e('AI Risk & GEO Trendi', 'dodo-ai-seo'); ?></h3>
                </div>
                <div class="dodo-chart-body">
                    <canvas id="dodo-risk-geo-chart"></canvas>
                </div>
            </div>
            
            <!-- NEW: Sprint 5 Charts -->
            <div class="dodo-chart-card">
                <div class="dodo-chart-header">
                    <h3 class="dodo-chart-title"><?php _e('GEO & AI Visibility Trendi', 'dodo-ai-seo'); ?></h3>
                </div>
                <div class="dodo-chart-body">
                    <canvas id="dodo-geo-visibility-chart"></canvas>
                </div>
            </div>
            
            <div class="dodo-chart-card">
                <div class="dodo-chart-header">
                    <h3 class="dodo-chart-title"><?php _e('Humanization Trendi', 'dodo-ai-seo'); ?></h3>
                </div>
                <div class="dodo-chart-body">
                    <canvas id="dodo-humanization-chart"></canvas>
                </div>
            </div>
            
            <div class="dodo-chart-card">
                <div class="dodo-chart-header">
                    <h3 class="dodo-chart-title"><?php _e('Content Intelligence Özeti', 'dodo-ai-seo'); ?></h3>
                </div>
                <div class="dodo-chart-body">
                    <div class="dodo-intelligence-summary">
                        <div class="dodo-intelligence-metric">
                            <div class="dodo-intelligence-label">Pillar Topics</div>
                            <div class="dodo-intelligence-value"><?php echo $sprint5_metrics['intelligence_metrics']['pillar_count']; ?></div>
                        </div>
                        <div class="dodo-intelligence-metric">
                            <div class="dodo-intelligence-label">Cannibalization Issues</div>
                            <div class="dodo-intelligence-value"><?php echo $sprint5_metrics['intelligence_metrics']['cannibalization_issues']; ?></div>
                        </div>
                        <div class="dodo-intelligence-metric">
                            <div class="dodo-intelligence-label">Orphan Posts</div>
                            <div class="dodo-intelligence-value"><?php echo $sprint5_metrics['intelligence_metrics']['orphan_posts']; ?></div>
                        </div>
                        <div class="dodo-intelligence-metric">
                            <div class="dodo-intelligence-label">Overall Authority</div>
                            <div class="dodo-intelligence-value"><?php echo $sprint5_metrics['intelligence_metrics']['overall_authority']; ?>%</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if (empty($analytics_data)): ?>
        <div class="dodo-empty-state">
            <div class="dodo-empty-state-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/></svg>
            </div>
            <h3 class="dodo-empty-state-title"><?php _e('Henüz Analitik Verisi Yok', 'dodo-ai-seo'); ?></h3>
            <p class="dodo-empty-state-description">
                <?php _e('İlk analizi oluşturmak için "Analitiği Yenile" butonuna tıklayın.', 'dodo-ai-seo'); ?>
            </p>
        </div>
        <?php endif; ?>
    </div>
    </div>
</div>

<style>
.dodo-summary-sublabel {
    font-size: 11px;
    color: var(--dodo-text-muted);
    margin-top: 4px;
}

.dodo-intelligence-summary {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: var(--dodo-space-4);
    padding: var(--dodo-space-4);
}

.dodo-intelligence-metric {
    text-align: center;
    padding: var(--dodo-space-3);
    background: var(--dodo-bg-secondary);
    border-radius: var(--dodo-radius-md);
}

.dodo-intelligence-label {
    font-size: 12px;
    color: var(--dodo-text-muted);
    margin-bottom: var(--dodo-space-2);
}

.dodo-intelligence-value {
    font-size: 24px;
    font-weight: 700;
    color: var(--dodo-text-primary);
}
</style>

<script>
jQuery(document).ready(function($) {
    // Refresh Analytics Button
    $('#dodo-refresh-analytics').on('click', function() {
        const $btn = $(this);
        $btn.prop('disabled', true).html('<svg width="16" height="16" viewBox="0 0 24 24" fill="none" style="margin-right: 8px; animation: spin 1s linear infinite;"><path d="M1 4v6h6M23 20v-6h-6" stroke="currentColor" stroke-width="2"/><path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15" stroke="currentColor" stroke-width="2"/></svg>Analiz Ediliyor...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'dodo_refresh_analytics',
                nonce: '<?php echo wp_create_nonce('dodo_analytics_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Hata: ' + (response.data.message || 'Bilinmeyen hata'));
                    $btn.prop('disabled', false).html('<svg width="16" height="16" viewBox="0 0 24 24" fill="none" style="margin-right: 8px;"><path d="M1 4v6h6M23 20v-6h-6" stroke="currentColor" stroke-width="2"/><path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15" stroke="currentColor" stroke-width="2"/></svg>Analitiği Yenile');
                }
            },
            error: function() {
                alert('AJAX hatası oluştu');
                $btn.prop('disabled', false).html('<svg width="16" height="16" viewBox="0 0 24 24" fill="none" style="margin-right: 8px;"><path d="M1 4v6h6M23 20v-6h-6" stroke="currentColor" stroke-width="2"/><path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15" stroke="currentColor" stroke-width="2"/></svg>Analitiği Yenile');
            }
        });
    });
    
    <?php if (!empty($analytics_data)): ?>
    // Prepare data
    const analyticsData = <?php echo json_encode($analytics_data); ?>;
    const labels = analyticsData.map(d => d.recorded_at);
    const healthScores = analyticsData.map(d => parseFloat(d.health_score));
    const seoScores = analyticsData.map(d => parseFloat(d.seo_score));
    const semanticScores = analyticsData.map(d => parseFloat(d.semantic_score));
    const aiRisks = analyticsData.map(d => parseFloat(d.ai_risk_score));
    const geoScores = analyticsData.map(d => parseFloat(d.geo_score || 0));
    
    // Sprint 5 trend data
    const geoTrends = <?php echo json_encode($sprint5_metrics['trends']['geo']); ?>;
    const humanizationTrends = <?php echo json_encode($sprint5_metrics['trends']['humanization']); ?>;
    
    // Chart.js configuration
    const chartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'bottom'
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                max: 100
            }
        }
    };
    
    // Health Score Chart
    new Chart(document.getElementById('dodo-health-chart'), {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Sağlık Skoru',
                data: healthScores,
                borderColor: '#667eea',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: chartOptions
    });
    
    // SEO & Semantic Chart
    new Chart(document.getElementById('dodo-seo-semantic-chart'), {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'SEO Skoru',
                    data: seoScores,
                    borderColor: '#4facfe',
                    backgroundColor: 'rgba(79, 172, 254, 0.1)',
                    tension: 0.4
                },
                {
                    label: 'Semantic Skoru',
                    data: semanticScores,
                    borderColor: '#00f2fe',
                    backgroundColor: 'rgba(0, 242, 254, 0.1)',
                    tension: 0.4
                }
            ]
        },
        options: chartOptions
    });
    
    // Risk & GEO Chart
    new Chart(document.getElementById('dodo-risk-geo-chart'), {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'AI Risk',
                    data: aiRisks,
                    borderColor: '#f5576c',
                    backgroundColor: 'rgba(245, 87, 108, 0.1)',
                    tension: 0.4
                },
                {
                    label: 'GEO Skoru',
                    data: geoScores,
                    borderColor: '#fee140',
                    backgroundColor: 'rgba(254, 225, 64, 0.1)',
                    tension: 0.4
                }
            ]
        },
        options: chartOptions
    });
    
    // NEW: GEO & AI Visibility Chart
    if (geoTrends.length > 0) {
        new Chart(document.getElementById('dodo-geo-visibility-chart'), {
            type: 'line',
            data: {
                labels: geoTrends.map(d => d.date),
                datasets: [{
                    label: 'GEO Score',
                    data: geoTrends.map(d => parseFloat(d.avg_score)),
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: chartOptions
        });
    }
    
    // NEW: Humanization Chart
    if (humanizationTrends.length > 0) {
        new Chart(document.getElementById('dodo-humanization-chart'), {
            type: 'line',
            data: {
                labels: humanizationTrends.map(d => d.date),
                datasets: [{
                    label: 'Humanization Score',
                    data: humanizationTrends.map(d => parseFloat(d.avg_score)),
                    borderColor: '#4facfe',
                    backgroundColor: 'rgba(79, 172, 254, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: chartOptions
        });
    }
    <?php endif; ?>
});
</script>
