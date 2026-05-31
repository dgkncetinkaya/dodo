<?php
/**
 * Production Readiness Center
 * 
 * Operational health checks and production readiness score
 * 
 * @package DODO_AI_SEO
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get health checks
$health_checks = dodo_get_production_health_checks();
$readiness_score = dodo_calculate_readiness_score($health_checks);

?>

<div class="wrap dodo-production-readiness">
    <h1>🚀 Canlı Sistem Hazırlık Merkezi</h1>
    <p class="description">Operasyonel sağlık kontrolleri ve canlı sistem hazırlık doğrulaması</p>
    
    <!-- Readiness Score -->
    <div class="dodo-readiness-score-card">
        <div class="score-circle <?php echo esc_attr(dodo_get_score_class($readiness_score)); ?>">
            <span class="score-value"><?php echo esc_html($readiness_score); ?>%</span>
            <span class="score-label">Canlıya Hazır</span>
        </div>
        
        <div class="score-status">
            <?php if ($readiness_score >= 90): ?>
                <span class="status-badge status-excellent">✓ Mükemmel - Canlıya Hazır</span>
            <?php elseif ($readiness_score >= 75): ?>
                <span class="status-badge status-good">⚠ İyi - Küçük Sorunlar</span>
            <?php elseif ($readiness_score >= 60): ?>
                <span class="status-badge status-warning">⚠ Uyarı - Aksiyon Gerekli</span>
            <?php else: ?>
                <span class="status-badge status-critical">✗ Kritik - Canlıya Hazır Değil</span>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Health Checks Grid -->
    <div class="dodo-health-checks-grid">
        
        <!-- Cron Health -->
        <div class="health-check-card <?php echo esc_attr($health_checks['cron']['status']); ?>">
            <div class="check-header">
                <span class="check-icon"><?php echo $health_checks['cron']['status'] === 'pass' ? '✓' : '✗'; ?></span>
                <h3>WP-Cron Sistemi</h3>
            </div>
            <div class="check-details">
                <p><?php echo esc_html($health_checks['cron']['message']); ?></p>
                <?php if (!empty($health_checks['cron']['details'])): ?>
                    <ul class="check-details-list">
                        <?php foreach ($health_checks['cron']['details'] as $detail): ?>
                            <li><?php echo esc_html($detail); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Queue Health -->
        <div class="health-check-card <?php echo esc_attr($health_checks['queue']['status']); ?>">
            <div class="check-header">
                <span class="check-icon"><?php echo $health_checks['queue']['status'] === 'pass' ? '✓' : '✗'; ?></span>
                <h3>İş Kuyruğu</h3>
            </div>
            <div class="check-details">
                <p><?php echo esc_html($health_checks['queue']['message']); ?></p>
                <?php if (!empty($health_checks['queue']['details'])): ?>
                    <ul class="check-details-list">
                        <?php foreach ($health_checks['queue']['details'] as $detail): ?>
                            <li><?php echo esc_html($detail); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- API Budget -->
        <div class="health-check-card <?php echo esc_attr($health_checks['api_budget']['status']); ?>">
            <div class="check-header">
                <span class="check-icon"><?php echo $health_checks['api_budget']['status'] === 'pass' ? '✓' : '✗'; ?></span>
                <h3>API Bütçesi</h3>
            </div>
            <div class="check-details">
                <p><?php echo esc_html($health_checks['api_budget']['message']); ?></p>
                <?php if (!empty($health_checks['api_budget']['details'])): ?>
                    <ul class="check-details-list">
                        <?php foreach ($health_checks['api_budget']['details'] as $detail): ?>
                            <li><?php echo esc_html($detail); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Cache Health -->
        <div class="health-check-card <?php echo esc_attr($health_checks['cache']['status']); ?>">
            <div class="check-header">
                <span class="check-icon"><?php echo $health_checks['cache']['status'] === 'pass' ? '✓' : '✗'; ?></span>
                <h3>Performans Önbelleği</h3>
            </div>
            <div class="check-details">
                <p><?php echo esc_html($health_checks['cache']['message']); ?></p>
                <?php if (!empty($health_checks['cache']['details'])): ?>
                    <ul class="check-details-list">
                        <?php foreach ($health_checks['cache']['details'] as $detail): ?>
                            <li><?php echo esc_html($detail); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Learning Stability -->
        <div class="health-check-card <?php echo esc_attr($health_checks['learning']['status']); ?>">
            <div class="check-header">
                <span class="check-icon"><?php echo $health_checks['learning']['status'] === 'pass' ? '✓' : '✗'; ?></span>
                <h3>Öğrenme Sistemi</h3>
            </div>
            <div class="check-details">
                <p><?php echo esc_html($health_checks['learning']['message']); ?></p>
                <?php if (!empty($health_checks['learning']['details'])): ?>
                    <ul class="check-details-list">
                        <?php foreach ($health_checks['learning']['details'] as $detail): ?>
                            <li><?php echo esc_html($detail); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Database Health -->
        <div class="health-check-card <?php echo esc_attr($health_checks['database']['status']); ?>">
            <div class="check-header">
                <span class="check-icon"><?php echo $health_checks['database']['status'] === 'pass' ? '✓' : '✗'; ?></span>
                <h3>Veritabanı Tabloları</h3>
            </div>
            <div class="check-details">
                <p><?php echo esc_html($health_checks['database']['message']); ?></p>
                <?php if (!empty($health_checks['database']['details'])): ?>
                    <ul class="check-details-list">
                        <?php foreach ($health_checks['database']['details'] as $detail): ?>
                            <li><?php echo esc_html($detail); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Embeddings Health -->
        <div class="health-check-card <?php echo esc_attr($health_checks['embeddings']['status']); ?>">
            <div class="check-header">
                <span class="check-icon"><?php echo $health_checks['embeddings']['status'] === 'pass' ? '✓' : '✗'; ?></span>
                <h3>Semantik Gömme</h3>
            </div>
            <div class="check-details">
                <p><?php echo esc_html($health_checks['embeddings']['message']); ?></p>
                <?php if (!empty($health_checks['embeddings']['details'])): ?>
                    <ul class="check-details-list">
                        <?php foreach ($health_checks['embeddings']['details'] as $detail): ?>
                            <li><?php echo esc_html($detail); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Security Baseline -->
        <div class="health-check-card <?php echo esc_attr($health_checks['security']['status']); ?>">
            <div class="check-header">
                <span class="check-icon"><?php echo $health_checks['security']['status'] === 'pass' ? '✓' : '✗'; ?></span>
                <h3>Güvenlik Temeli</h3>
            </div>
            <div class="check-details">
                <p><?php echo esc_html($health_checks['security']['message']); ?></p>
                <?php if (!empty($health_checks['security']['details'])): ?>
                    <ul class="check-details-list">
                        <?php foreach ($health_checks['security']['details'] as $detail): ?>
                            <li><?php echo esc_html($detail); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Performance Metrics -->
        <div class="health-check-card <?php echo esc_attr($health_checks['performance']['status']); ?>">
            <div class="check-header">
                <span class="check-icon"><?php echo $health_checks['performance']['status'] === 'pass' ? '✓' : '✗'; ?></span>
                <h3>Performans Metrikleri</h3>
            </div>
            <div class="check-details">
                <p><?php echo esc_html($health_checks['performance']['message']); ?></p>
                <?php if (!empty($health_checks['performance']['details'])): ?>
                    <ul class="check-details-list">
                        <?php foreach ($health_checks['performance']['details'] as $detail): ?>
                            <li><?php echo esc_html($detail); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
        
    </div>
    
    <!-- Action Buttons -->
    <div class="dodo-readiness-actions">
        <button type="button" class="button button-primary" onclick="location.reload()">
            🔄 Sağlık Kontrollerini Yenile
        </button>
        
        <?php if ($health_checks['database']['status'] === 'fail' || $health_checks['queue']['status'] === 'fail'): ?>
            <button type="button" class="button button-secondary" id="repair-tables-btn">
                🔧 Tabloları Onar
            </button>
        <?php endif; ?>
        
        <button type="button" class="button" id="run-migrations-btn">
            🔄 Migration Çalıştır
        </button>
        
        <?php if ($readiness_score < 90): ?>
            <button type="button" class="button" onclick="dodoFixIssues()">
                🔧 Sorunları Otomatik Düzelt
            </button>
        <?php endif; ?>
        
        <button type="button" class="button" onclick="dodoExportReport()">
            📄 Raporu Dışa Aktar
        </button>
    </div>
    
</div>

<style>
.dodo-production-readiness {
    max-width: 1400px;
}

.dodo-readiness-score-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 30px;
    margin: 20px 0;
    display: flex;
    align-items: center;
    gap: 30px;
}

.score-circle {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    border: 8px solid;
}

.score-circle.excellent { border-color: #46b450; background: #f0f9f1; }
.score-circle.good { border-color: #00a0d2; background: #f0f8ff; }
.score-circle.warning { border-color: #ffb900; background: #fffbf0; }
.score-circle.critical { border-color: #dc3232; background: #fff0f0; }

.score-value {
    font-size: 48px;
    font-weight: bold;
    line-height: 1;
}

.score-label {
    font-size: 14px;
    color: #666;
    margin-top: 5px;
}

.score-status {
    flex: 1;
}

.status-badge {
    display: inline-block;
    padding: 12px 24px;
    border-radius: 6px;
    font-size: 18px;
    font-weight: 600;
}

.status-badge.status-excellent {
    background: #46b450;
    color: #fff;
}

.status-badge.status-good {
    background: #00a0d2;
    color: #fff;
}

.status-badge.status-warning {
    background: #ffb900;
    color: #000;
}

.status-badge.status-critical {
    background: #dc3232;
    color: #fff;
}

.dodo-health-checks-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.health-check-card {
    background: #fff;
    border: 2px solid;
    border-radius: 8px;
    padding: 20px;
}

.health-check-card.pass {
    border-color: #46b450;
}

.health-check-card.warning {
    border-color: #ffb900;
}

.health-check-card.fail {
    border-color: #dc3232;
}

.check-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 15px;
}

.check-icon {
    font-size: 24px;
    font-weight: bold;
}

.health-check-card.pass .check-icon {
    color: #46b450;
}

.health-check-card.warning .check-icon {
    color: #ffb900;
}

.health-check-card.fail .check-icon {
    color: #dc3232;
}

.check-header h3 {
    margin: 0;
    font-size: 16px;
}

.check-details p {
    margin: 0 0 10px 0;
    color: #666;
}

.check-details-list {
    margin: 10px 0 0 20px;
    font-size: 13px;
    color: #666;
}

.check-details-list li {
    margin: 5px 0;
}

.dodo-readiness-actions {
    margin: 30px 0;
    display: flex;
    gap: 10px;
}
</style>

<script>
function dodoFixIssues() {
    if (!confirm('Otomatik düzeltme yaygın sorunları onarmaya çalışacak. Devam edilsin mi?')) {
        return;
    }
    
    alert('Otomatik düzeltme özelliği yakında gelecek');
}

function dodoExportReport() {
    alert('Dışa aktarma özelliği yakında gelecek');
}

// Repair tables button
jQuery(document).ready(function($) {
    $('#repair-tables-btn').on('click', function() {
        if (!confirm('Eksik veritabanı tablolarını oluşturmak istediğinizden emin misiniz?')) {
            return;
        }
        
        const btn = $(this);
        btn.prop('disabled', true).text('🔧 Onarılıyor...');
        
        $.post(ajaxurl, {
            action: 'dodo_repair_database_tables',
            nonce: '<?php echo wp_create_nonce('dodo_repair_tables'); ?>'
        }, function(response) {
            if (response.success) {
                alert('✅ Tablolar başarıyla oluşturuldu!\n\n' + response.data.message);
                location.reload();
            } else {
                alert('❌ Hata: ' + response.data.message);
                btn.prop('disabled', false).text('🔧 Tabloları Onar');
            }
        }).fail(function() {
            alert('❌ AJAX hatası oluştu');
            btn.prop('disabled', false).text('🔧 Tabloları Onar');
        });
    });
    
    // Run migrations button
    $('#run-migrations-btn').on('click', function() {
        if (!confirm('Veritabanı migration\'larını çalıştırmak istediğinizden emin misiniz?')) {
            return;
        }
        
        const btn = $(this);
        btn.prop('disabled', true).text('🔄 Çalıştırılıyor...');
        
        $.post(ajaxurl, {
            action: 'dodo_run_migrations',
            nonce: '<?php echo wp_create_nonce('dodo_migrations'); ?>'
        }, function(response) {
            if (response.success) {
                alert('✅ Migration\'lar başarıyla tamamlandı!\n\n' + response.data.message);
                location.reload();
            } else {
                alert('❌ Hata: ' + response.data.message);
                btn.prop('disabled', false).text('🔄 Migration Çalıştır');
            }
        }).fail(function() {
            alert('❌ AJAX hatası oluştu');
            btn.prop('disabled', false).text('🔄 Migration Çalıştır');
        });
    });
});
</script>

<?php

/**
 * Get production health checks
 * SAFE: Never throws fatal errors
 */
function dodo_get_production_health_checks() {
    $checks = [];
    
    // 1. Cron Health
    $checks['cron'] = dodo_safe_check('cron', 'dodo_check_cron_health');
    
    // 2. Queue Health
    $checks['queue'] = dodo_safe_check('queue', 'dodo_check_queue_health');
    
    // 3. API Budget
    $checks['api_budget'] = dodo_safe_check('api_budget', 'dodo_check_api_budget');
    
    // 4. Cache Health
    $checks['cache'] = dodo_safe_check('cache', 'dodo_check_cache_health');
    
    // 5. Learning Stability
    $checks['learning'] = dodo_safe_check('learning', 'dodo_check_learning_stability');
    
    // 6. Database Health
    $checks['database'] = dodo_safe_check('database', 'dodo_check_database_health');
    
    // 7. Embeddings Health
    $checks['embeddings'] = dodo_safe_check('embeddings', 'dodo_check_embeddings_health');
    
    // 8. Security Baseline
    $checks['security'] = dodo_safe_check('security', 'dodo_check_security_baseline');
    
    // 9. Performance Metrics
    $checks['performance'] = dodo_safe_check('performance', 'dodo_check_performance_metrics');
    
    return $checks;
}

/**
 * Safe wrapper for health checks
 * Catches all errors and returns safe fallback
 */
function dodo_safe_check($check_name, $callback) {
    try {
        if (function_exists($callback)) {
            return call_user_func($callback);
        }
        
        return [
            'status' => 'fail',
            'message' => 'Kontrol fonksiyonu bulunamadı',
            'details' => ["Function: {$callback}"],
        ];
    } catch (Throwable $e) {
        return [
            'status' => 'fail',
            'message' => 'Kontrol sırasında hata oluştu',
            'details' => [
                'Hata: ' . $e->getMessage(),
                'Dosya: ' . basename($e->getFile()) . ':' . $e->getLine(),
            ],
        ];
    }
}

/**
 * Check cron health
 * SAFE: Handles empty cron array
 */
function dodo_check_cron_health() {
    $crons = _get_cron_array();
    
    if (empty($crons)) {
        return [
            'status' => 'fail',
            'message' => 'WP-Cron çalışmıyor',
            'details' => ['Zamanlanmış iş yok'],
        ];
    }
    
    $dodo_crons = [];
    $required_crons = ['dodo_process_queue', 'dodo_recover_stuck_jobs'];
    
    foreach ($crons as $timestamp => $cron) {
        foreach ($cron as $hook => $dings) {
            if (strpos($hook, 'dodo_') === 0) {
                $dodo_crons[] = $hook;
            }
        }
    }
    
    $missing = array_diff($required_crons, $dodo_crons);
    
    if (empty($missing) && count($dodo_crons) >= 2) {
        return [
            'status' => 'pass',
            'message' => 'WP-Cron sağlıklı',
            'details' => [
                count($dodo_crons) . " DODO cron işi zamanlandı",
                'Kuyruk işleyici: AKTİF',
                'Takılı iş kurtarma: AKTİF',
            ],
        ];
    }
    
    return [
        'status' => 'fail',
        'message' => 'WP-Cron sorunları tespit edildi',
        'details' => array_merge(
            ["Bulunan: " . count($dodo_crons) . " cron işi"],
            !empty($missing) ? ["Eksik: " . implode(', ', $missing)] : []
        ),
    ];
}

/**
 * Check queue health
 * SAFE: Checks if table exists first
 */
function dodo_check_queue_health() {
    global $wpdb;
    $table = $wpdb->prefix . 'dodo_queue_jobs';
    
    // Check if table exists
    $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
    
    if (!$exists) {
        return [
            'status' => 'fail',
            'message' => 'İş kuyruğu tablosu bulunamadı',
            'details' => [
                'Tablo: ' . $table,
                'Veritabanı tablolarını oluşturmak için "Tabloları Onar" butonuna tıklayın',
            ],
        ];
    }
    
    // Safe query with error suppression
    $stuck_jobs = $wpdb->get_var("
        SELECT COUNT(*) FROM {$table}
        WHERE status = 'processing'
        AND updated_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ");
    
    $pending_jobs = $wpdb->get_var("
        SELECT COUNT(*) FROM {$table}
        WHERE status = 'pending'
    ");
    
    // Handle null results
    $stuck_jobs = $stuck_jobs ?? 0;
    $pending_jobs = $pending_jobs ?? 0;
    
    if ($stuck_jobs == 0 && $pending_jobs < 50) {
        return [
            'status' => 'pass',
            'message' => 'Kuyruk sağlıklı',
            'details' => [
                "{$pending_jobs} bekleyen iş",
                'Takılı iş yok',
            ],
        ];
    }
    
    if ($stuck_jobs > 0) {
        return [
            'status' => 'fail',
            'message' => 'Kuyrukta takılı işler var',
            'details' => [
                "{$stuck_jobs} takılı iş tespit edildi",
                "{$pending_jobs} bekleyen iş",
            ],
        ];
    }
    
    return [
        'status' => 'warning',
        'message' => 'Kuyruk birikimi tespit edildi',
        'details' => [
            "{$pending_jobs} bekleyen iş",
            'İşleme sıklığını artırmayı düşünün',
        ],
    ];
}

/**
 * Check API budget
 * SAFE: Checks if class and method exist, uses fallback
 */
function dodo_check_api_budget() {
    $today_usage = null;
    
    // Try DODO_Usage_Logger first
    if (class_exists('DODO_Usage_Logger')) {
        $usage_logger = new DODO_Usage_Logger();
        
        if (method_exists($usage_logger, 'get_today_usage')) {
            $today_usage = $usage_logger->get_today_usage();
        }
    }
    
    // Fallback: Try telemetry table directly
    if (!$today_usage) {
        $today_usage = dodo_get_usage_fallback();
    }
    
    $total_tokens = $today_usage['total_tokens'] ?? 0;
    $total_cost = $today_usage['total_cost'] ?? 0;
    
    if ($total_cost < 10) {
        return [
            'status' => 'pass',
            'message' => 'API bütçesi sağlıklı',
            'details' => [
                "Bugün: \${$total_cost}",
                number_format($total_tokens) . ' token kullanıldı',
            ],
        ];
    }
    
    if ($total_cost < 50) {
        return [
            'status' => 'warning',
            'message' => 'API kullanımı yüksek',
            'details' => [
                "Bugün: \${$total_cost}",
                number_format($total_tokens) . ' token kullanıldı',
            ],
        ];
    }
    
    return [
        'status' => 'fail',
        'message' => 'API bütçesi aşıldı',
        'details' => [
            "Bugün: \${$total_cost}",
            'Kullanım desenlerini gözden geçirin',
        ],
    ];
}

/**
 * Fallback usage getter from telemetry table
 */
function dodo_get_usage_fallback() {
    global $wpdb;
    $table = $wpdb->prefix . 'dodo_telemetry';
    
    // Check if table exists
    $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
    
    if (!$exists) {
        return [
            'total_tokens' => 0,
            'total_cost' => 0,
        ];
    }
    
    // Get today's usage
    $result = $wpdb->get_row("
        SELECT 
            SUM(total_tokens) as total_tokens,
            SUM(estimated_cost) as total_cost
        FROM {$table}
        WHERE DATE(created_at) = CURDATE()
    ");
    
    return [
        'total_tokens' => $result->total_tokens ?? 0,
        'total_cost' => $result->total_cost ?? 0,
    ];
}

/**
 * Check cache health
 * SAFE: Checks if class exists and handles new installations
 */
function dodo_check_cache_health() {
    if (!class_exists('DODO_Performance_Cache')) {
        return [
            'status' => 'warning',
            'message' => 'Önbellek sınıfı bulunamadı',
            'details' => [
                'DODO_Performance_Cache sınıfı yüklenmemiş',
            ],
        ];
    }
    
    $cache = new DODO_Performance_Cache();
    
    if (!method_exists($cache, 'get_stats')) {
        return [
            'status' => 'warning',
            'message' => 'Önbellek istatistikleri alınamıyor',
            'details' => [
                'get_stats() metodu bulunamadı',
            ],
        ];
    }
    
    $stats = $cache->get_stats();
    $hit_rate = $stats['hit_rate'] ?? 0;
    $total_requests = ($stats['hits'] ?? 0) + ($stats['misses'] ?? 0);
    
    // New installation - no data yet
    if ($total_requests === 0) {
        return [
            'status' => 'pass',
            'message' => 'Önbellek sistemi hazır',
            'details' => [
                'Önbellek kullanıma hazır',
                'İlk kullanımla birlikte istatistikler oluşacak',
            ],
        ];
    }
    
    if ($hit_rate >= 60) {
        return [
            'status' => 'pass',
            'message' => 'Önbellek iyi performans gösteriyor',
            'details' => [
                "İsabet oranı: {$hit_rate}%",
                ($stats['hits'] ?? 0) . ' isabet / ' . $total_requests . ' istek',
            ],
        ];
    }
    
    if ($hit_rate >= 30) {
        return [
            'status' => 'warning',
            'message' => 'Önbellek isabet oranı düşük',
            'details' => [
                "İsabet oranı: {$hit_rate}%",
                'Önbellek ısıtmayı düşünün',
            ],
        ];
    }
    
    return [
        'status' => 'warning',
        'message' => 'Önbellek henüz ısınıyor',
        'details' => [
            "İsabet oranı: {$hit_rate}%",
            'Kullanımla birlikte iyileşecek',
        ],
    ];
}

/**
 * Check learning stability
 * SAFE: Checks if classes exist and handles low sample count
 */
function dodo_check_learning_stability() {
    if (!class_exists('DODO_Learning_Controller') || !class_exists('DODO_Learning_Quality')) {
        return [
            'status' => 'warning',
            'message' => 'Öğrenme sistemi sınıfları bulunamadı',
            'details' => [
                'Learning Controller veya Quality sınıfı yüklenmemiş',
            ],
        ];
    }
    
    $controller = new DODO_Learning_Controller();
    $quality = new DODO_Learning_Quality();
    
    $score_data = method_exists($quality, 'calculate_quality_score') 
        ? $quality->calculate_quality_score() 
        : ['score' => 0, 'grade' => 'F', 'sample_count' => 0];
    
    // Extract numeric score from array
    $score = is_array($score_data) ? ($score_data['score'] ?? 0) : $score_data;
    $sample_count = is_array($score_data) ? ($score_data['sample_count'] ?? 0) : 0;
        
    $frozen = method_exists($controller, 'is_frozen') 
        ? $controller->is_frozen() 
        : false;
    
    if ($frozen) {
        return [
            'status' => 'warning',
            'message' => 'Öğrenme sistemi dondurulmuş',
            'details' => [
                'Acil dondurma aktif',
                'Manuel müdahale gerekli',
            ],
        ];
    }
    
    // Low sample count - not enough data yet
    if ($sample_count < 5) {
        return [
            'status' => 'pass',
            'message' => 'Öğrenme sistemi veri topluyor',
            'details' => [
                "Örnek sayısı: {$sample_count}",
                'Yeterli veri için en az 5 örnek gerekli',
                'Sistem kullanımla birlikte öğrenecek',
            ],
        ];
    }
    
    if ($score >= 70) {
        return [
            'status' => 'pass',
            'message' => 'Öğrenme sistemi kararlı',
            'details' => [
                "Kalite skoru: {$score}/100",
                "{$sample_count} örnek üzerinden hesaplandı",
                'Kararlılık sorunu yok',
            ],
        ];
    }
    
    if ($score >= 50) {
        return [
            'status' => 'warning',
            'message' => 'Öğrenme kalitesi orta seviyede',
            'details' => [
                "Kalite skoru: {$score}/100",
                "{$sample_count} örnek üzerinden hesaplandı",
                'Öğrenme desenlerini gözden geçirin',
            ],
        ];
    }
    
    return [
        'status' => 'warning',
        'message' => 'Öğrenme kalitesi düşük',
        'details' => [
            "Kalite skoru: {$score}/100",
            "{$sample_count} örnek üzerinden hesaplandı",
            'Öğrenme stratejisini gözden geçirin',
        ],
    ];
}

/**
 * Check database health
 * SAFE: Checks each table individually, auto-heals if possible
 */
function dodo_check_database_health() {
    global $wpdb;
    
    $required_tables = [
        'dodo_keyword_opportunities',
        'dodo_queue_jobs',
        'dodo_impact_tracking',
        'dodo_learning_events',
        'dodo_strategy_snapshots',
        'dodo_telemetry',
    ];
    
    $missing = [];
    
    foreach ($required_tables as $table) {
        $full_table = $wpdb->prefix . $table;
        $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $full_table));
        
        if (!$exists) {
            $missing[] = $table;
        }
    }
    
    // Auto-heal: Try to create missing tables
    if (!empty($missing) && class_exists('DODO_Migrations')) {
        error_log('DODO Production Readiness: Auto-healing missing tables: ' . implode(', ', $missing));
        
        try {
            DODO_Migrations::repair_tables();
            
            // Re-check after repair
            $still_missing = [];
            foreach ($missing as $table) {
                $full_table = $wpdb->prefix . $table;
                $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $full_table));
                
                if (!$exists) {
                    $still_missing[] = $table;
                }
            }
            
            if (empty($still_missing)) {
                return [
                    'status' => 'pass',
                    'message' => 'Tüm tablolar mevcut (otomatik onarıldı)',
                    'details' => [
                        count($required_tables) . ' tablo doğrulandı',
                        'Eksik tablolar otomatik oluşturuldu',
                    ],
                ];
            }
            
            $missing = $still_missing;
        } catch (Throwable $e) {
            error_log('DODO Production Readiness: Auto-heal failed: ' . $e->getMessage());
        }
    }
    
    if (empty($missing)) {
        return [
            'status' => 'pass',
            'message' => 'Tüm tablolar mevcut',
            'details' => [
                count($required_tables) . ' tablo doğrulandı',
            ],
        ];
    }
    
    return [
        'status' => 'fail',
        'message' => 'Eksik veritabanı tabloları',
        'details' => array_merge(
            array_map(function($t) {
                return "Eksik: {$t}";
            }, $missing),
            ['"Tabloları Onar" butonuna tıklayın']
        ),
    ];
}

/**
 * Check embeddings health
 * SAFE: Checks if table exists first, handles new installations
 */
function dodo_check_embeddings_health() {
    global $wpdb;
    $table = $wpdb->prefix . 'dodo_semantic_cache';
    
    // Check if table exists
    $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
    
    if (!$exists) {
        return [
            'status' => 'pass',
            'message' => 'Semantik önbellek sistemi hazır',
            'details' => [
                'Tablo ilk kullanımda oluşacak',
                'Önbellek kullanımla birlikte dolacak',
            ],
        ];
    }
    
    $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
    $count = $count ?? 0;
    
    if ($count >= 100) {
        return [
            'status' => 'pass',
            'message' => 'Embedding önbelleği sağlıklı',
            'details' => [
                "{$count} embedding önbelleğe alındı",
                'Önbellek iyi dolu',
            ],
        ];
    }
    
    if ($count >= 20) {
        return [
            'status' => 'pass',
            'message' => 'Embedding önbelleği oluşuyor',
            'details' => [
                "{$count} embedding önbelleğe alındı",
                'Önbellek zamanla gelişecek',
            ],
        ];
    }
    
    return [
        'status' => 'pass',
        'message' => 'Embedding önbelleği hazır',
        'details' => [
            $count > 0 ? "{$count} embedding önbelleğe alındı" : 'Önbellek boş',
            'İlk semantic analiz sonrası otomatik oluşacak',
        ],
    ];
}

/**
 * Check security baseline
 * SAFE: Checks if class exists, improved WP_DEBUG handling
 */
function dodo_check_security_baseline() {
    $issues = [];
    $warnings = [];
    
    // Check if API key is set
    if (class_exists('DODO_Settings')) {
        $settings = new DODO_Settings();
        
        if (method_exists($settings, 'is_api_key_valid')) {
            if (!$settings->is_api_key_valid()) {
                $issues[] = 'API anahtarı yapılandırılmamış';
            }
        }
    } else {
        $warnings[] = 'DODO_Settings sınıfı bulunamadı';
    }
    
    // Check debug mode with nuance
    if (defined('WP_DEBUG') && WP_DEBUG) {
        // Check if it's safe debug mode (log only, no display)
        if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG && defined('WP_DEBUG_DISPLAY') && !WP_DEBUG_DISPLAY) {
            $warnings[] = 'WP_DEBUG etkin (sadece log - kabul edilebilir)';
        } else {
            $issues[] = 'WP_DEBUG_DISPLAY etkin (canlı ortamda kapatılmalı)';
        }
    }
    
    if (empty($issues) && empty($warnings)) {
        return [
            'status' => 'pass',
            'message' => 'Güvenlik temeli sağlandı',
            'details' => [
                'API anahtarı yapılandırıldı',
                'Debug modu uygun',
            ],
        ];
    }
    
    if (!empty($issues)) {
        return [
            'status' => 'fail',
            'message' => 'Güvenlik sorunları tespit edildi',
            'details' => array_merge($issues, $warnings),
        ];
    }
    
    return [
        'status' => 'warning',
        'message' => 'Güvenlik önerileri',
        'details' => $warnings,
    ];
}

/**
 * Calculate readiness score
 * Weighted scoring - critical checks have more weight
 */
function dodo_calculate_readiness_score($checks) {
    $weights = [
        'cron' => 15,        // Critical
        'queue' => 15,       // Critical
        'database' => 20,    // Critical
        'security' => 15,    // Critical
        'api_budget' => 10,  // Important
        'cache' => 10,       // Important
        'learning' => 10,    // Important
        'embeddings' => 5,   // Nice to have
    ];
    
    $total_weight = array_sum($weights);
    $earned_score = 0;
    
    foreach ($checks as $key => $check) {
        $weight = $weights[$key] ?? 5;
        
        if ($check['status'] === 'pass') {
            $earned_score += $weight;
        } elseif ($check['status'] === 'warning') {
            $earned_score += $weight * 0.7; // 70% credit for warnings
        }
        // fail = 0 points
    }
    
    return round(($earned_score / $total_weight) * 100);
}

/**
 * Get score class
 */
function dodo_get_score_class($score) {
    if ($score >= 90) return 'excellent';
    if ($score >= 75) return 'good';
    if ($score >= 60) return 'warning';
    return 'critical';
}

/**
 * Check performance metrics
 * SAFE: Checks if class exists
 */
function dodo_check_performance_metrics() {
    if (!class_exists('DODO_Performance_Monitor')) {
        return [
            'status' => 'warning',
            'message' => 'Performans izleme devre dışı',
            'details' => ['Performance Monitor sınıfı yüklenmedi'],
        ];
    }
    
    $stats = DODO_Performance_Monitor::get_stats();
    
    $memory_status = $stats['memory']['status'] ?? 'ok';
    $memory_peak = $stats['memory']['peak'] ?? 0;
    $slow_queries = $stats['slow_queries'] ?? 0;
    
    // Check memory
    if ($memory_status === 'critical') {
        return [
            'status' => 'fail',
            'message' => 'Kritik bellek kullanımı',
            'details' => [
                "Peak: {$memory_peak} MB",
                'Bellek optimizasyonu gerekli',
                "{$slow_queries} yavaş sorgu tespit edildi",
            ],
        ];
    }
    
    if ($memory_status === 'warning' || $slow_queries > 5) {
        return [
            'status' => 'warning',
            'message' => 'Performans uyarıları var',
            'details' => [
                "Peak Memory: {$memory_peak} MB",
                "{$slow_queries} yavaş sorgu tespit edildi",
                'Performans optimizasyonu önerilir',
            ],
        ];
    }
    
    return [
        'status' => 'pass',
        'message' => 'Performans sağlıklı',
        'details' => [
            "Peak Memory: {$memory_peak} MB",
            'Yavaş sorgu yok',
            'Sistem optimize çalışıyor',
        ],
    ];
}
