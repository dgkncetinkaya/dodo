<?php
/**
 * SEO Impact Center
 * 
 * Real KPI dashboard showing learning impact
 * 
 * @package DODO_AI_SEO
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Initialize components
$tracker = new DODO_Impact_Tracker();
$quality = new DODO_Learning_Quality();
$rate_limiter = new DODO_Learning_Rate_Limiter();

// Get data
$quality_score = $quality->calculate_quality_score();
$rate_status = $rate_limiter->get_status();
$best_actions = $tracker->get_best_performing_actions(10);

?>

<div class="wrap dodo-impact-center">
    <h1>
        <span class="dashicons dashicons-chart-area"></span>
        SEO Etki Merkezi
    </h1>
    
    <p class="description">
        Gerçek zamanlı KPI'lar ve öğrenme sistemi performans metrikleri
    </p>
    
    <!-- Learning Quality Score -->
    <div class="quality-score-card">
        <div class="score-display">
            <div class="score-circle grade-<?php echo strtolower($quality_score['grade']); ?>">
                <div class="score-value"><?php echo $quality_score['score']; ?></div>
                <div class="score-label">Kalite Skoru</div>
            </div>
            <div class="grade-badge">Not: <?php echo $quality_score['grade']; ?></div>
        </div>
        
        <div class="score-breakdown">
            <h3>Skor Dağılımı</h3>
            <div class="breakdown-items">
                <div class="breakdown-item">
                    <span class="item-label">Kabul Oranı</span>
                    <span class="item-value"><?php echo $quality_score['breakdown']['acceptance']; ?>/30</span>
                    <div class="item-bar">
                        <div class="bar-fill" style="width: <?php echo ($quality_score['breakdown']['acceptance'] / 30) * 100; ?>%;"></div>
                    </div>
                </div>
                
                <div class="breakdown-item">
                    <span class="item-label">Düşük Geri Alma</span>
                    <span class="item-value"><?php echo $quality_score['breakdown']['rollback']; ?>/25</span>
                    <div class="item-bar">
                        <div class="bar-fill" style="width: <?php echo ($quality_score['breakdown']['rollback'] / 25) * 100; ?>%;"></div>
                    </div>
                </div>
                
                <div class="breakdown-item">
                    <span class="item-label">Düşük Hata Oranı</span>
                    <span class="item-value"><?php echo $quality_score['breakdown']['false_adaptation']; ?>/20</span>
                    <div class="item-bar">
                        <div class="bar-fill" style="width: <?php echo ($quality_score['breakdown']['false_adaptation'] / 20) * 100; ?>%;"></div>
                    </div>
                </div>
                
                <div class="breakdown-item">
                    <span class="item-label">Strateji Kararlılığı</span>
                    <span class="item-value"><?php echo $quality_score['breakdown']['stability']; ?>/15</span>
                    <div class="item-bar">
                        <div class="bar-fill" style="width: <?php echo ($quality_score['breakdown']['stability'] / 15) * 100; ?>%;"></div>
                    </div>
                </div>
                
                <div class="breakdown-item">
                    <span class="item-label">Filtre Etkinliği</span>
                    <span class="item-value"><?php echo $quality_score['breakdown']['filter']; ?>/10</span>
                    <div class="item-bar">
                        <div class="bar-fill" style="width: <?php echo ($quality_score['breakdown']['filter'] / 10) * 100; ?>%;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Rate Limiter Status -->
    <div class="rate-limiter-panel">
        <h2>Hız Sınırlayıcı Durumu</h2>
        
        <div class="rate-stats">
            <div class="rate-stat">
                <div class="stat-label">Günlük Adaptasyonlar</div>
                <div class="stat-value">
                    <?php echo $rate_status['daily_adaptations']; ?> / <?php echo $rate_status['daily_limit']; ?>
                </div>
                <div class="stat-bar">
                    <div class="bar-fill" style="width: <?php echo ($rate_status['daily_adaptations'] / $rate_status['daily_limit']) * 100; ?>%;"></div>
                </div>
                <div class="stat-remaining"><?php echo $rate_status['daily_remaining']; ?> kalan</div>
            </div>
            
            <div class="rate-stat">
                <div class="stat-label">Saatlik Adaptasyonlar</div>
                <div class="stat-value">
                    <?php echo $rate_status['hourly_adaptations']; ?> / <?php echo $rate_status['hourly_limit']; ?>
                </div>
                <div class="stat-bar">
                    <div class="bar-fill" style="width: <?php echo ($rate_status['hourly_adaptations'] / $rate_status['hourly_limit']) * 100; ?>%;"></div>
                </div>
                <div class="stat-remaining"><?php echo $rate_status['hourly_remaining']; ?> kalan</div>
            </div>
        </div>
        
        <div class="rate-limits">
            <p><strong>Maks. Parametre Değişimi:</strong> <?php echo $rate_status['max_parameter_change']; ?>%</p>
            <p><strong>Maks. Kümülatif Değişim:</strong> <?php echo $rate_status['max_cumulative_change']; ?>%</p>
        </div>
    </div>
    
    <!-- Best Performing Actions -->
    <div class="best-actions-panel">
        <h2>✅ En İyi Performans Gösteren Aksiyonlar</h2>
        
        <?php if (!empty($best_actions)): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Aksiyon Türü</th>
                        <th>Ort. Skor</th>
                        <th>Örnek Sayısı</th>
                        <th>Etki</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($best_actions as $action): ?>
                        <?php
                        $avg_score = round($action->avg_score, 1);
                        $score_class = $avg_score > 50 ? 'positive' : ($avg_score > 0 ? 'neutral' : 'negative');
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html(ucwords(str_replace('_', ' ', $action->action_type))); ?></strong></td>
                            <td><span class="score-badge score-<?php echo $score_class; ?>"><?php echo $avg_score > 0 ? '+' : ''; ?><?php echo $avg_score; ?></span></td>
                            <td><?php echo $action->action_count; ?> vaka</td>
                            <td>
                                <?php if ($avg_score > 50): ?>
                                    <span class="impact-badge impact-high">Yüksek Pozitif</span>
                                <?php elseif ($avg_score > 20): ?>
                                    <span class="impact-badge impact-medium">Orta Pozitif</span>
                                <?php elseif ($avg_score > 0): ?>
                                    <span class="impact-badge impact-low">Düşük Pozitif</span>
                                <?php else: ?>
                                    <span class="impact-badge impact-negative">Negatif</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="description">Henüz etki verisi yok. Sistem adaptasyonlardan sonra sonuçları takip edecek.</p>
        <?php endif; ?>
    </div>
    
    <!-- Quality Metrics -->
    <div class="quality-metrics-panel">
        <h2>📊 Kalite Metrikleri</h2>
        
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-icon">✓</div>
                <div class="metric-content">
                    <div class="metric-value"><?php echo $quality_score['metrics']['acceptance_rate']; ?>%</div>
                    <div class="metric-label">Kabul Oranı</div>
                </div>
            </div>
            
            <div class="metric-card">
                <div class="metric-icon">↩️</div>
                <div class="metric-content">
                    <div class="metric-value"><?php echo $quality_score['metrics']['rollback_rate']; ?>%</div>
                    <div class="metric-label">Geri Alma Oranı</div>
                </div>
            </div>
            
            <div class="metric-card">
                <div class="metric-icon">⚠️</div>
                <div class="metric-content">
                    <div class="metric-value"><?php echo $quality_score['metrics']['false_adaptation_rate']; ?>%</div>
                    <div class="metric-label">Yanlış Adaptasyon Oranı</div>
                </div>
            </div>
            
            <div class="metric-card">
                <div class="metric-icon">📈</div>
                <div class="metric-content">
                    <div class="metric-value"><?php echo $quality_score['metrics']['strategy_stability']; ?>%</div>
                    <div class="metric-label">Strateji Kararlılığı</div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.dodo-impact-center {
    max-width: 1400px;
}

.quality-score-card {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 30px;
    margin: 20px 0;
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 40px;
}

.score-display {
    text-align: center;
}

.score-circle {
    width: 200px;
    height: 200px;
    border-radius: 50%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    border: 8px solid #ddd;
}

.score-circle.grade-a {
    border-color: #46b450;
    background: #f0f9f1;
}

.score-circle.grade-b {
    border-color: #00a0d2;
    background: #f0f6fc;
}

.score-circle.grade-c {
    border-color: #ffb900;
    background: #fffbf0;
}

.score-circle.grade-d,
.score-circle.grade-f {
    border-color: #dc3232;
    background: #fef7f7;
}

.score-value {
    font-size: 64px;
    font-weight: bold;
    color: #2271b1;
}

.score-label {
    font-size: 14px;
    color: #666;
    text-transform: uppercase;
}

.grade-badge {
    font-size: 24px;
    font-weight: bold;
    color: #2271b1;
}

.score-breakdown h3 {
    margin-top: 0;
}

.breakdown-items {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.breakdown-item {
    display: grid;
    grid-template-columns: 150px 60px 1fr;
    align-items: center;
    gap: 10px;
}

.item-label {
    font-size: 13px;
    color: #666;
}

.item-value {
    font-weight: bold;
    color: #2271b1;
}

.item-bar {
    height: 20px;
    background: #f0f0f0;
    border-radius: 10px;
    overflow: hidden;
}

.bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #2271b1, #00a0d2);
    transition: width 0.3s;
}

.rate-limiter-panel,
.best-actions-panel,
.quality-metrics-panel {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
}

.rate-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.rate-stat {
    padding: 15px;
    background: #f9f9f9;
    border-radius: 8px;
}

.stat-label {
    font-size: 12px;
    color: #666;
    text-transform: uppercase;
    margin-bottom: 5px;
}

.stat-value {
    font-size: 24px;
    font-weight: bold;
    color: #2271b1;
    margin-bottom: 10px;
}

.stat-bar {
    height: 10px;
    background: #f0f0f0;
    border-radius: 5px;
    overflow: hidden;
    margin-bottom: 5px;
}

.stat-remaining {
    font-size: 12px;
    color: #666;
}

.score-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-weight: bold;
    font-size: 14px;
}

.score-positive {
    background: #d4edda;
    color: #155724;
}

.score-neutral {
    background: #d1ecf1;
    color: #0c5460;
}

.score-negative {
    background: #f8d7da;
    color: #721c24;
}

.impact-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 10px;
    font-size: 12px;
    font-weight: bold;
}

.impact-high {
    background: #d4edda;
    color: #155724;
}

.impact-medium {
    background: #d1ecf1;
    color: #0c5460;
}

.impact-low {
    background: #fff3cd;
    color: #856404;
}

.impact-negative {
    background: #f8d7da;
    color: #721c24;
}

.metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.metric-card {
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.metric-icon {
    font-size: 32px;
}

.metric-value {
    font-size: 28px;
    font-weight: bold;
    color: #2271b1;
}

.metric-label {
    font-size: 12px;
    color: #666;
}
</style>
