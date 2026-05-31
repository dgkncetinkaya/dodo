<?php
/**
 * Learning Center Dashboard
 * 
 * Shows learning insights, strategy evolution, and performance tracking
 * 
 * @package DODO_AI_SEO
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Initialize engines
$feedback_engine = new DODO_Feedback_Engine();
$strategy_evolution = new DODO_Strategy_Evolution();
$niche_intelligence = new DODO_Niche_Intelligence();
$explainer = new DODO_Learning_Explainer();
$validator = new DODO_Learning_Validator();

// Get insights
$learning_insights = $feedback_engine->get_learning_insights();
$evolution_insights = $strategy_evolution->get_evolution_insights();
$niche_insights = $niche_intelligence->get_niche_insights();
$validation_insights = $validator->get_validation_insights();

?>

<div class="wrap dodo-learning-center">
    <h1>
        <span class="dashicons dashicons-chart-line"></span>
        Öğrenme Merkezi
    </h1>
    
    <p class="description">
        Sistem öğrenme analizleri ve adaptif zeka performansı
    </p>
    
    <!-- Summary Cards -->
    <div class="dodo-stats-grid">
        <div class="dodo-stat-card">
            <div class="stat-icon">📊</div>
            <div class="stat-content">
                <div class="stat-value"><?php echo number_format($learning_insights['total_learning_events']); ?></div>
                <div class="stat-label">Öğrenme Olayları</div>
            </div>
        </div>
        
        <div class="dodo-stat-card">
            <div class="stat-icon">👤</div>
            <div class="stat-content">
                <div class="stat-value"><?php echo number_format($learning_insights['total_feedback_events']); ?></div>
                <div class="stat-label">Kullanıcı Geri Bildirimleri</div>
            </div>
        </div>
        
        <div class="dodo-stat-card">
            <div class="stat-icon">🎯</div>
            <div class="stat-content">
                <div class="stat-value"><?php echo count($learning_insights['best_performing_actions']); ?></div>
                <div class="stat-label">Öğrenilen Desenler</div>
            </div>
        </div>
        
        <div class="dodo-stat-card">
            <div class="stat-icon">⚠️</div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $validation_insights['blocked_learnings']; ?></div>
                <div class="stat-label">Engellenen Öğrenmeler</div>
            </div>
        </div>
    </div>
    
    <!-- Best Performing Actions -->
    <div class="dodo-panel">
        <h2>✅ En İyi Performans Gösteren Aksiyonlar</h2>
        
        <?php if (!empty($learning_insights['best_performing_actions'])): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Aksiyon Türü</th>
                        <th>Ort. Skor</th>
                        <th>Örnek Sayısı</th>
                        <th>Güven</th>
                        <th>Öneri</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($learning_insights['best_performing_actions'] as $action): ?>
                        <?php
                        $action_type = $action['action_type'];
                        $avg_score = round($action['avg_score'], 1);
                        $sample_count = $action['sample_count'];
                        
                        $confidence = 'Düşük';
                        if ($sample_count >= 20) $confidence = 'Yüksek';
                        elseif ($sample_count >= 10) $confidence = 'Orta';
                        
                        $score_class = $avg_score > 50 ? 'score-high' : ($avg_score > 20 ? 'score-medium' : 'score-low');
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html(ucwords(str_replace('_', ' ', $action_type))); ?></strong></td>
                            <td><span class="score-badge <?php echo $score_class; ?>">+<?php echo $avg_score; ?></span></td>
                            <td><?php echo $sample_count; ?> vaka</td>
                            <td><span class="confidence-badge confidence-<?php echo strtolower($confidence); ?>"><?php echo $confidence; ?></span></td>
                            <td>
                                <?php if ($avg_score > 50): ?>
                                    <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span> Kesinlikle Önerilir
                                <?php else: ?>
                                    <span class="dashicons dashicons-yes" style="color: #00a0d2;"></span> Önerilir
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="description">Henüz öğrenme verisi yok. Sistem optimizasyon sonuçlarından öğrenecek.</p>
        <?php endif; ?>
    </div>
    
    <!-- Worst Performing Actions -->
    <?php if (!empty($learning_insights['worst_performing_actions'])): ?>
    <div class="dodo-panel">
        <h2>⚠️ Kaçınılması Gereken Aksiyonlar</h2>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Aksiyon Türü</th>
                    <th>Ort. Skor</th>
                    <th>Örnek Sayısı</th>
                    <th>Uyarı</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($learning_insights['worst_performing_actions'] as $action): ?>
                    <?php if ($action['avg_score'] < 0): ?>
                        <tr>
                            <td><strong><?php echo esc_html(ucwords(str_replace('_', ' ', $action['action_type']))); ?></strong></td>
                            <td><span class="score-badge score-negative"><?php echo round($action['avg_score'], 1); ?></span></td>
                            <td><?php echo $action['sample_count']; ?> vaka</td>
                            <td>
                                <span class="dashicons dashicons-warning" style="color: #dc3232;"></span>
                                Negatif etki tespit edildi - kullanmaktan kaçının
                            </td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    
    <!-- Niche Intelligence -->
    <div class="dodo-panel">
        <h2>🎯 Niş Zekası</h2>
        
        <?php if (!empty($niche_insights['by_niche'])): ?>
            <div class="niche-grid">
                <?php foreach ($niche_insights['by_niche'] as $niche => $data): ?>
                    <div class="niche-card">
                        <h3><?php echo esc_html(ucwords(str_replace('_', ' ', $niche))); ?></h3>
                        <div class="niche-stats">
                            <div class="niche-stat">
                                <span class="label">Örnekler:</span>
                                <span class="value"><?php echo $data['sample_count']; ?></span>
                            </div>
                            <div class="niche-stat">
                                <span class="label">Güven:</span>
                                <span class="value"><?php echo $data['confidence']; ?>%</span>
                            </div>
                        </div>
                        
                        <?php if (!empty($data['best_actions'])): ?>
                            <div class="best-actions">
                                <strong>En İyi Aksiyonlar:</strong>
                                <ul>
                                    <?php foreach (array_slice($data['best_actions'], 0, 3, true) as $action => $score): ?>
                                        <li><?php echo esc_html(ucwords(str_replace('_', ' ', $action))); ?>: <strong>+<?php echo round($score, 1); ?></strong></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="description">Henüz niş-spesifik öğrenme verisi yok. Sistem farklı nişler için desenler öğrenecek.</p>
        <?php endif; ?>
    </div>
    
    <!-- Recommendation Quality -->
    <?php if (!empty($learning_insights['recommendation_quality'])): ?>
    <div class="dodo-panel">
        <h2>📈 Öneri Kalitesi</h2>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Öneri Türü</th>
                    <th>Kabul Oranı</th>
                    <th>Kabul Edilen</th>
                    <th>Reddedilen</th>
                    <th>Değiştirilen</th>
                    <th>Toplam</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($learning_insights['recommendation_quality'] as $type => $quality): ?>
                    <tr>
                        <td><strong><?php echo esc_html(ucwords(str_replace('_', ' ', $type))); ?></strong></td>
                        <td>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $quality['acceptance_rate']; ?>%;"></div>
                                <span class="progress-text"><?php echo $quality['acceptance_rate']; ?>%</span>
                            </div>
                        </td>
                        <td><?php echo $quality['accepted']; ?></td>
                        <td><?php echo $quality['rejected']; ?></td>
                        <td><?php echo $quality['modified']; ?></td>
                        <td><?php echo $quality['total']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    
    <!-- Validation Safety -->
    <div class="dodo-panel">
        <h2>🛡️ Öğrenme Güvenliği</h2>
        
        <div class="safety-stats">
            <div class="safety-stat">
                <span class="dashicons dashicons-shield-alt"></span>
                <div>
                    <strong><?php echo $validation_insights['total_validations']; ?></strong>
                    <span>Toplam Doğrulama</span>
                </div>
            </div>
            
            <div class="safety-stat">
                <span class="dashicons dashicons-dismiss"></span>
                <div>
                    <strong><?php echo $validation_insights['blocked_learnings']; ?></strong>
                    <span>Engellenen Öğrenmeler</span>
                </div>
            </div>
            
            <div class="safety-stat">
                <span class="dashicons dashicons-filter"></span>
                <div>
                    <strong><?php echo $validation_insights['filtered_outliers']; ?></strong>
                    <span>Filtrelenen Aykırı Değerler</span>
                </div>
            </div>
        </div>
        
        <p class="description">
            <span class="dashicons dashicons-info"></span>
            Öğrenme doğrulaması gürültülü veriden yanlış öğrenmeyi önler ve güvenliği sağlar.
        </p>
    </div>
</div>

<style>
.dodo-learning-center {
    max-width: 1400px;
}

.dodo-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.dodo-stat-card {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.stat-icon {
    font-size: 32px;
}

.stat-value {
    font-size: 32px;
    font-weight: bold;
    color: #2271b1;
}

.stat-label {
    color: #666;
    font-size: 14px;
}

.dodo-panel {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
}

.dodo-panel h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 2px solid #2271b1;
}

.score-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-weight: bold;
    font-size: 14px;
}

.score-high {
    background: #d4edda;
    color: #155724;
}

.score-medium {
    background: #d1ecf1;
    color: #0c5460;
}

.score-low {
    background: #fff3cd;
    color: #856404;
}

.score-negative {
    background: #f8d7da;
    color: #721c24;
}

.confidence-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 10px;
    font-size: 12px;
    font-weight: bold;
}

.confidence-high {
    background: #d4edda;
    color: #155724;
}

.confidence-medium {
    background: #d1ecf1;
    color: #0c5460;
}

.confidence-low {
    background: #fff3cd;
    color: #856404;
}

.niche-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.niche-card {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 15px;
    background: #f9f9f9;
}

.niche-card h3 {
    margin: 0 0 10px 0;
    color: #2271b1;
}

.niche-stats {
    display: flex;
    gap: 20px;
    margin: 10px 0;
}

.niche-stat {
    display: flex;
    flex-direction: column;
}

.niche-stat .label {
    font-size: 12px;
    color: #666;
}

.niche-stat .value {
    font-size: 18px;
    font-weight: bold;
    color: #2271b1;
}

.best-actions {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #ddd;
}

.best-actions ul {
    margin: 5px 0 0 0;
    padding-left: 20px;
}

.best-actions li {
    font-size: 13px;
    margin: 5px 0;
}

.progress-bar {
    position: relative;
    width: 100%;
    height: 24px;
    background: #f0f0f0;
    border-radius: 12px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #2271b1, #00a0d2);
    transition: width 0.3s ease;
}

.progress-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-weight: bold;
    font-size: 12px;
    color: #333;
}

.safety-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.safety-stat {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    background: #f9f9f9;
    border-radius: 8px;
    border: 1px solid #ddd;
}

.safety-stat .dashicons {
    font-size: 32px;
    color: #2271b1;
}

.safety-stat strong {
    display: block;
    font-size: 24px;
    color: #2271b1;
}

.safety-stat span {
    font-size: 13px;
    color: #666;
}
</style>
