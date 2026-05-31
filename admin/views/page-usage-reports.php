<?php
/**
 * Usage Reports Page
 * 
 * Feature bazlı token ve maliyet raporu
 */

// Doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

// Tarih filtresi
$period = isset($_GET['period']) ? sanitize_text_field($_GET['period']) : 'today';
$valid_periods = array('today', 'week', 'month', 'all');
if (!in_array($period, $valid_periods)) {
    $period = 'today';
}

// Test kayıtlarını hariç tut
$exclude_tests = isset($_GET['exclude_tests']) ? true : false;

// Usage Logger instance
$logger = new DODO_Usage_Logger();

// Stats al
$stats = $logger->get_usage_stats($period);

// Feature bazlı breakdown al
$feature_stats = $logger->get_usage_by_feature($period);

// Test kayıtlarını filtrele
if ($exclude_tests && !empty($feature_stats)) {
    $feature_stats = array_filter($feature_stats, function($stat) {
        return !in_array($stat['feature_name'], array('cache_test', 'api_key_test', 'debug_test', 'force_create_test'));
    });
}

// Model bazlı breakdown al
$model_stats = $logger->get_usage_by_model($period);

?>

<div class="wrap dodo-admin-wrap">
    <h1>📊 Usage Reports</h1>
    
    <!-- Tarih Filtresi -->
    <div style="background: #fff; padding: 15px; margin: 20px 0; border: 1px solid #ccc;">
        <form method="get" style="display: flex; align-items: center; gap: 15px;">
            <input type="hidden" name="page" value="dodo-usage-reports">
            
            <label style="font-weight: bold;">Tarih Aralığı:</label>
            
            <label>
                <input type="radio" name="period" value="today" <?php checked($period, 'today'); ?>>
                Bugün
            </label>
            
            <label>
                <input type="radio" name="period" value="week" <?php checked($period, 'week'); ?>>
                Son 7 Gün
            </label>
            
            <label>
                <input type="radio" name="period" value="month" <?php checked($period, 'month'); ?>>
                Son 30 Gün
            </label>
            
            <label>
                <input type="radio" name="period" value="all" <?php checked($period, 'all'); ?>>
                Tüm Zamanlar
            </label>
            
            <label style="margin-left: 20px;">
                <input type="checkbox" name="exclude_tests" value="1" <?php checked($exclude_tests); ?>>
                Test kayıtlarını hariç tut
            </label>
            
            <button type="submit" class="button button-primary">Filtrele</button>
        </form>
    </div>
    
    <!-- Genel İstatistikler -->
    <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccc;">
        <h2>📈 Genel İstatistikler</h2>
        
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-top: 20px;">
            <div style="padding: 15px; background: #f0f0f1; border-radius: 4px;">
                <div style="font-size: 12px; color: #666; margin-bottom: 5px;">Toplam İstek</div>
                <div style="font-size: 24px; font-weight: bold;"><?php echo number_format($stats['total_requests']); ?></div>
            </div>
            
            <div style="padding: 15px; background: #f0f0f1; border-radius: 4px;">
                <div style="font-size: 12px; color: #666; margin-bottom: 5px;">Toplam Token</div>
                <div style="font-size: 24px; font-weight: bold;"><?php echo number_format($stats['total_tokens']); ?></div>
            </div>
            
            <div style="padding: 15px; background: #f0f0f1; border-radius: 4px;">
                <div style="font-size: 12px; color: #666; margin-bottom: 5px;">Toplam Maliyet</div>
                <div style="font-size: 24px; font-weight: bold; color: #d63638;">$<?php echo number_format($stats['total_cost'], 4); ?></div>
            </div>
            
            <div style="padding: 15px; background: #f0f0f1; border-radius: 4px;">
                <div style="font-size: 12px; color: #666; margin-bottom: 5px;">Cache Hit Rate</div>
                <div style="font-size: 24px; font-weight: bold; color: #00a32a;"><?php echo number_format($stats['cache_hit_rate'], 1); ?>%</div>
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-top: 20px;">
            <div style="padding: 15px; background: #f0f0f1; border-radius: 4px;">
                <div style="font-size: 12px; color: #666; margin-bottom: 5px;">Cache Hits</div>
                <div style="font-size: 20px; font-weight: bold; color: #00a32a;"><?php echo number_format($stats['cache_hits']); ?></div>
            </div>
            
            <div style="padding: 15px; background: #f0f0f1; border-radius: 4px;">
                <div style="font-size: 12px; color: #666; margin-bottom: 5px;">Cache Misses</div>
                <div style="font-size: 20px; font-weight: bold; color: #d63638;"><?php echo number_format($stats['cache_misses']); ?></div>
            </div>
            
            <div style="padding: 15px; background: #f0f0f1; border-radius: 4px;">
                <div style="font-size: 12px; color: #666; margin-bottom: 5px;">Ortalama Süre</div>
                <div style="font-size: 20px; font-weight: bold;"><?php echo number_format($stats['avg_execution_time']); ?> ms</div>
            </div>
        </div>
    </div>
    
    <!-- Feature Bazlı Rapor -->
    <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccc;">
        <h2>🎯 Feature Bazlı Rapor</h2>
        
        <?php if (empty($feature_stats)): ?>
            <p style="color: #666; font-style: italic;">Bu dönem için kayıt bulunamadı.</p>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped" style="margin-top: 20px;">
                <thead>
                    <tr>
                        <th style="width: 20%;">Feature Name</th>
                        <th style="width: 10%; text-align: center;">Request Count</th>
                        <th style="width: 10%; text-align: center;">Cache Hits</th>
                        <th style="width: 10%; text-align: center;">Cache Misses</th>
                        <th style="width: 15%; text-align: right;">Total Tokens</th>
                        <th style="width: 15%; text-align: right;">Total Cost</th>
                        <th style="width: 10%; text-align: right;">Avg Time</th>
                        <th style="width: 10%; text-align: right;">Hit Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // Feature name'leri güzelleştir
                    $feature_labels = array(
                        'outline' => '📝 Outline',
                        'introduction' => '🎯 Introduction',
                        'main_sections' => '📄 Main Sections (Legacy)',
                        'faq' => '❓ FAQ',
                        'conclusion' => '🏁 Conclusion',
                        'seo_meta' => '🔍 SEO Meta',
                        'keyword_opportunities' => '💡 Keyword Opportunities',
                        'keyword_generation' => '🔑 Keyword Generation',
                        'audit' => '📊 Content Audit',
                        'cache_test' => '🧪 Cache Test',
                        'api_key_test' => '🔑 API Key Test',
                        // Content Improver features
                        'intro_improve' => '✨ Intro Improve',
                        'faq_improve' => '✨ FAQ Improve',
                        'section_expand' => '✨ Section Expand',
                        'semantic_improve' => '✨ Semantic Improve',
                        'readability_improve' => '✨ Readability Improve',
                        'cta_improve' => '✨ CTA Improve',
                    );
                    
                    // Main section pattern için özel label
                    if (preg_match('/^main_section_(\d+)$/', $feature_name, $matches)) {
                        $section_number = $matches[1];
                        $feature_labels[$feature_name] = "📄 Main Section {$section_number}";
                    }
                    
                    foreach ($feature_stats as $stat): 
                        $feature_name = $stat['feature_name'];
                        $feature_label = isset($feature_labels[$feature_name]) ? $feature_labels[$feature_name] : $feature_name;
                        $requests = (int) $stat['requests'];
                        $total_tokens = (int) $stat['total_tokens'];
                        $total_cost = (float) $stat['total_cost'];
                        $avg_time = (int) $stat['avg_time'];
                        
                        // Cache hit/miss hesapla (ayrı sorgu gerekli)
                        global $wpdb;
                        $table_name = $wpdb->prefix . 'dodo_ai_usage_logs';
                        
                        $date_filter = '';
                        switch ($period) {
                            case 'today':
                                $date_filter = "AND DATE(created_at) = CURDATE()";
                                break;
                            case 'week':
                                $date_filter = "AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                                break;
                            case 'month':
                                $date_filter = "AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                                break;
                        }
                        
                        $cache_stats = $wpdb->get_row($wpdb->prepare(
                            "SELECT 
                                SUM(cache_hit) as hits,
                                COUNT(*) - SUM(cache_hit) as misses
                            FROM {$table_name}
                            WHERE feature_name = %s
                            {$date_filter}",
                            $feature_name
                        ));
                        
                        $cache_hits = (int) ($cache_stats->hits ?? 0);
                        $cache_misses = (int) ($cache_stats->misses ?? 0);
                        $hit_rate = $requests > 0 ? round(($cache_hits / $requests) * 100, 1) : 0;
                        
                        // Test kayıtları için farklı renk
                        $is_test = in_array($feature_name, array('cache_test', 'api_key_test', 'debug_test', 'force_create_test'));
                        $row_style = $is_test ? 'background: #fff3cd;' : '';
                    ?>
                        <tr style="<?php echo $row_style; ?>">
                            <td><strong><?php echo esc_html($feature_label); ?></strong></td>
                            <td style="text-align: center;"><?php echo number_format($requests); ?></td>
                            <td style="text-align: center; color: #00a32a;"><?php echo number_format($cache_hits); ?></td>
                            <td style="text-align: center; color: #d63638;"><?php echo number_format($cache_misses); ?></td>
                            <td style="text-align: right;"><?php echo number_format($total_tokens); ?></td>
                            <td style="text-align: right; font-weight: bold;">$<?php echo number_format($total_cost, 6); ?></td>
                            <td style="text-align: right;"><?php echo number_format($avg_time); ?> ms</td>
                            <td style="text-align: right; color: <?php echo $hit_rate > 50 ? '#00a32a' : '#666'; ?>;">
                                <?php echo number_format($hit_rate, 1); ?>%
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="background: #f0f0f1; font-weight: bold;">
                        <td>TOPLAM</td>
                        <td style="text-align: center;"><?php echo number_format($stats['total_requests']); ?></td>
                        <td style="text-align: center; color: #00a32a;"><?php echo number_format($stats['cache_hits']); ?></td>
                        <td style="text-align: center; color: #d63638;"><?php echo number_format($stats['cache_misses']); ?></td>
                        <td style="text-align: right;"><?php echo number_format($stats['total_tokens']); ?></td>
                        <td style="text-align: right; color: #d63638;">$<?php echo number_format($stats['total_cost'], 6); ?></td>
                        <td style="text-align: right;"><?php echo number_format($stats['avg_execution_time']); ?> ms</td>
                        <td style="text-align: right;"><?php echo number_format($stats['cache_hit_rate'], 1); ?>%</td>
                    </tr>
                </tfoot>
            </table>
        <?php endif; ?>
    </div>
    
    <!-- Model Bazlı Rapor -->
    <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccc;">
        <h2>🤖 Model Bazlı Rapor</h2>
        
        <?php if (empty($model_stats)): ?>
            <p style="color: #666; font-style: italic;">Bu dönem için kayıt bulunamadı.</p>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped" style="margin-top: 20px;">
                <thead>
                    <tr>
                        <th style="width: 30%;">Model</th>
                        <th style="width: 20%; text-align: center;">Request Count</th>
                        <th style="width: 25%; text-align: right;">Total Tokens</th>
                        <th style="width: 25%; text-align: right;">Total Cost</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($model_stats as $stat): ?>
                        <tr>
                            <td><strong><?php echo esc_html($stat['model']); ?></strong></td>
                            <td style="text-align: center;"><?php echo number_format($stat['requests']); ?></td>
                            <td style="text-align: right;"><?php echo number_format($stat['total_tokens']); ?></td>
                            <td style="text-align: right; font-weight: bold;">$<?php echo number_format($stat['total_cost'], 6); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    
    <!-- Blog Generation Maliyet Tahmini -->
    <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccc;">
        <h2>💰 Blog Generation Maliyet Analizi</h2>
        
        <?php
        // Blog generation feature'larını filtrele
        $blog_features = array('outline', 'introduction', 'faq', 'conclusion', 'seo_meta');
        
        // Main section pattern'i de dahil et
        $blog_stats = array_filter($feature_stats, function($stat) use ($blog_features) {
            $feature_name = $stat['feature_name'];
            // Normal blog features veya main_section_X pattern
            return in_array($feature_name, $blog_features) || preg_match('/^main_section_\d+$/', $feature_name);
        });
        
        if (!empty($blog_stats)):
            $total_blog_cost = 0;
            $total_blog_tokens = 0;
            $total_blog_requests = 0;
            
            foreach ($blog_stats as $stat) {
                $total_blog_cost += (float) $stat['total_cost'];
                $total_blog_tokens += (int) $stat['total_tokens'];
                $total_blog_requests += (int) $stat['requests'];
            }
            
            // Ortalama blog maliyeti hesapla
            $complete_blogs = 0;
            if (count($blog_stats) >= 5) { // En az 5 feature (outline, intro, faq, conclusion, seo_meta)
                // Main section sayısını bul
                $main_section_count = 0;
                foreach ($blog_stats as $stat) {
                    if (preg_match('/^main_section_\d+$/', $stat['feature_name'])) {
                        $main_section_count++;
                    }
                }
                
                // Minimum request count'u bul (tüm feature'lar arasında)
                $min_requests = PHP_INT_MAX;
                foreach ($blog_stats as $stat) {
                    $min_requests = min($min_requests, (int) $stat['requests']);
                }
                
                // Main section'lar varsa, onların da minimum request'ini kontrol et
                if ($main_section_count > 0) {
                    $complete_blogs = $min_requests;
                } else {
                    $complete_blogs = $min_requests;
                }
            }
            
            $avg_blog_cost = $complete_blogs > 0 ? $total_blog_cost / $complete_blogs : 0;
            $avg_blog_tokens = $complete_blogs > 0 ? $total_blog_tokens / $complete_blogs : 0;
            
            // Main section sayısını hesapla
            $main_section_count = 0;
            foreach ($blog_stats as $stat) {
                if (preg_match('/^main_section_\d+$/', $stat['feature_name'])) {
                    $main_section_count++;
                }
            }
        ?>
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px;">
                <div style="padding: 15px; background: #f0f0f1; border-radius: 4px;">
                    <div style="font-size: 12px; color: #666; margin-bottom: 5px;">Toplam Blog İşlemi</div>
                    <div style="font-size: 24px; font-weight: bold;"><?php echo number_format($total_blog_requests); ?></div>
                </div>
                
                <div style="padding: 15px; background: #f0f0f1; border-radius: 4px;">
                    <div style="font-size: 12px; color: #666; margin-bottom: 5px;">Toplam Blog Token</div>
                    <div style="font-size: 24px; font-weight: bold;"><?php echo number_format($total_blog_tokens); ?></div>
                </div>
                
                <div style="padding: 15px; background: #f0f0f1; border-radius: 4px;">
                    <div style="font-size: 12px; color: #666; margin-bottom: 5px;">Toplam Blog Maliyeti</div>
                    <div style="font-size: 24px; font-weight: bold; color: #d63638;">$<?php echo number_format($total_blog_cost, 4); ?></div>
                </div>
                
                <div style="padding: 15px; background: #f0f0f1; border-radius: 4px;">
                    <div style="font-size: 12px; color: #666; margin-bottom: 5px;">Ortalama Blog Maliyeti</div>
                    <div style="font-size: 24px; font-weight: bold; color: #d63638;">$<?php echo number_format($avg_blog_cost, 4); ?></div>
                </div>
            </div>
            
            <?php if ($complete_blogs > 0): ?>
                <p style="margin-top: 15px; color: #666; font-size: 14px;">
                    <strong>Not:</strong> Bu dönemde yaklaşık <?php echo number_format($complete_blogs); ?> adet tam blog oluşturuldu.
                    Ortalama bir blog <?php echo number_format($avg_blog_tokens); ?> token kullanıyor ve $<?php echo number_format($avg_blog_cost, 4); ?> maliyeti var.
                    <?php if ($main_section_count > 0): ?>
                        <br>Main Sections: <?php echo $main_section_count; ?> ayrı section (section-by-section generation aktif).
                    <?php endif; ?>
                </p>
            <?php endif; ?>
        <?php else: ?>
            <p style="color: #666; font-style: italic;">Bu dönem için blog generation kaydı bulunamadı.</p>
        <?php endif; ?>
    </div>
    
</div>
