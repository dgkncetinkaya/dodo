<?php
/**
 * Anahtar Kelime Fırsatları Sayfası
 *
 * @package DODO_AI_SEO
 * @since 1.0.0
 */

// Doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

$opportunities_manager = new DODO_Keyword_Opportunities();
$stats = $opportunities_manager->get_stats();
$opportunities = $opportunities_manager->get_opportunities(array(
    'status' => isset($_GET['status_filter']) ? sanitize_text_field($_GET['status_filter']) : 'all',
    'limit' => 50,
));

// Intent labels
$intent_labels = array(
    'bilgilendirici' => '📚 Bilgilendirici',
    'ticari' => '💼 Ticari',
    'satın_alma' => '🛒 Satın Alma',
    'karşılaştırma' => '⚖️ Karşılaştırma',
);

// Content type labels
$content_type_labels = array(
    'blog' => 'Blog Yazısı',
    'howto' => 'Nasıl Yapılır?',
    'comparison' => 'Karşılaştırma',
    'listicle' => 'Liste İçeriği',
    'guide' => 'Rehber',
    'category' => 'Kategori',
    'faq' => 'SSS',
);

// Status labels
$status_labels = array(
    'new' => '🆕 Yeni',
    'queued' => '⏳ Kuyrukta',
    'processing' => '⚙️ İşleniyor',
    'ignored' => '🚫 Yok Sayıldı',
    'created' => '✅ Oluşturuldu',
    'failed' => '❌ Hata',
    'permanently_failed' => '🔴 Kalıcı Hata',
);
?>

<div class="wrap dodo-admin-wrap">
    <!-- Premium Page Header -->
    <div class="dodo-page-header">
        <div class="dodo-page-header-content">
            <h1 class="dodo-page-title">
                <span class="dashicons dashicons-lightbulb"></span>
                <?php echo esc_html__('Anahtar Kelime Fırsatları', 'dodo-ai-seo'); ?>
            </h1>
            <p class="dodo-page-description">
                <?php echo esc_html__('AI, site içeriğinizi analiz ederek hangi konularda blog yazmanız gerektiğini önerir.', 'dodo-ai-seo'); ?>
            </p>
        </div>
    </div>
    
    <!-- İstatistikler -->
    <div class="dodo-stats-grid">
        <div class="dodo-stat-card">
            <div><?php echo esc_html($stats['total']); ?></div>
            <div>Toplam Fırsat</div>
        </div>
        <div class="dodo-stat-card">
            <div style="color: var(--dodo-success);"><?php echo esc_html($stats['new']); ?></div>
            <div>Yeni</div>
        </div>
        <div class="dodo-stat-card">
            <div style="color: var(--dodo-warning);"><?php echo esc_html($stats['queued']); ?></div>
            <div>Kuyrukta</div>
        </div>
        <div class="dodo-stat-card">
            <div style="color: var(--dodo-primary);"><?php echo esc_html($stats['created']); ?></div>
            <div>Oluşturuldu</div>
        </div>
    </div>
    
    <!-- Aksiyonlar -->
    <div class="dodo-card">
        <?php 
        // GSC bağlantı durumunu kontrol et
        $gsc_intelligence = new DODO_GSC_Intelligence();
        $gsc_available = $gsc_intelligence->is_available();
        ?>
        
        <?php if (!$gsc_available && !empty($opportunities)) : ?>
            <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 12px 15px; margin-bottom: 20px; border-radius: 4px;">
                <p style="margin: 0; color: #856404; font-size: 13px;">
                    <strong>ℹ️ Bilgi:</strong> Google Search Console bağlı olmadığı için bu fırsatlar AI analizi ve site içeriği ile üretildi. 
                    Gerçek GSC verisi (gösterim, tıklama, CTR, pozisyon) için 
                    <a href="<?php echo admin_url('admin.php?page=dodo-ai-seo-settings#gsc-settings'); ?>" style="color: #856404; text-decoration: underline;">GSC'yi bağlayın</a>.
                </p>
            </div>
        <?php endif; ?>
        
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: var(--dodo-space-4);">
            <div style="display: flex; gap: var(--dodo-space-3); flex-wrap: wrap;">
                <button type="button" id="dodo-generate-opportunities" class="dodo-btn-primary">
                    <span class="dashicons dashicons-update" style="margin-top: 0; vertical-align: middle;"></span>
                    <?php echo esc_html__('Yeni Fırsatlar Üret', 'dodo-ai-seo'); ?>
                </button>
                
                <?php if ($stats['queued'] > 0) : ?>
                <button type="button" id="dodo-process-queue" class="button button-hero" style="background: var(--dodo-warning); border-color: var(--dodo-warning); color: #fff; height: 56px; padding: 0 var(--dodo-space-6); display: inline-flex; align-items: center; gap: 8px;">
                    <span class="dashicons dashicons-controls-play" style="margin-top: 0; vertical-align: middle;"></span>
                    <?php echo esc_html__('Kuyruğu Çalıştır', 'dodo-ai-seo'); ?>
                    <span class="dodo-queue-count">(<?php echo esc_html($stats['queued']); ?>)</span>
                </button>
                <?php endif; ?>
                
                <button type="button" id="dodo-delete-all-opportunities" class="button button-secondary" style="height: 56px; padding: 0 var(--dodo-space-6); display: inline-flex; align-items: center; gap: 8px;">
                    <span class="dashicons dashicons-trash" style="margin-top: 0; vertical-align: middle;"></span>
                    <?php echo esc_html__('Tümünü Sil', 'dodo-ai-seo'); ?>
                </button>
            </div>
            
            <div>
                <select id="dodo-status-filter" class="dodo-select" style="width: 200px; height: 48px;">
                    <option value="all" <?php selected(isset($_GET['status_filter']) ? $_GET['status_filter'] : 'all', 'all'); ?>>Tüm Durumlar</option>
                    <option value="new" <?php selected(isset($_GET['status_filter']) ? $_GET['status_filter'] : '', 'new'); ?>>🆕 Yeni</option>
                    <option value="queued" <?php selected(isset($_GET['status_filter']) ? $_GET['status_filter'] : '', 'queued'); ?>>⏳ Kuyrukta</option>
                    <option value="processing" <?php selected(isset($_GET['status_filter']) ? $_GET['status_filter'] : '', 'processing'); ?>>⚙️ İşleniyor</option>
                    <option value="ignored" <?php selected(isset($_GET['status_filter']) ? $_GET['status_filter'] : '', 'ignored'); ?>>🚫 Yok Sayıldı</option>
                    <option value="created" <?php selected(isset($_GET['status_filter']) ? $_GET['status_filter'] : '', 'created'); ?>>✅ Oluşturuldu</option>
                    <option value="failed" <?php selected(isset($_GET['status_filter']) ? $_GET['status_filter'] : '', 'failed'); ?>>❌ Hata</option>
                    <option value="permanently_failed" <?php selected(isset($_GET['status_filter']) ? $_GET['status_filter'] : '', 'permanently_failed'); ?>>🔴 Kalıcı Hata</option>
                </select>
            </div>
        </div>
    </div>
    
    <!-- Queue Processing Status -->
    <div id="dodo-queue-processing" class="dodo-card" style="display: none; background: var(--dodo-primary-light); border-left: 4px solid var(--dodo-primary);">
        <h3 style="margin-top: 0; display: flex; align-items: center; gap: var(--dodo-space-3); color: var(--dodo-primary);">
            <span class="dashicons dashicons-update dodo-spin"></span>
            Kuyruk İşleniyor...
        </h3>
        <div id="dodo-queue-progress" style="margin-bottom: var(--dodo-space-4);">
            <p><strong>İşlenen:</strong> <span id="dodo-queue-processed">0</span> / <span id="dodo-queue-total">0</span></p>
            <p><strong>Başarılı:</strong> <span id="dodo-queue-success">0</span></p>
            <p><strong>Hatalı:</strong> <span id="dodo-queue-failed">0</span></p>
            <p><strong>Şu an işlenen:</strong> <span id="dodo-queue-current">-</span></p>
        </div>
        <div class="dodo-progress-bar" style="background: var(--dodo-neutral-200); height: 20px; border-radius: var(--dodo-radius-lg); overflow: hidden;">
            <div id="dodo-queue-progress-bar" style="background: var(--dodo-primary); height: 100%; width: 0%; transition: width 0.3s;"></div>
        </div>
    </div>
    
    <!-- Loading State -->
    <div id="dodo-opportunities-loading" class="dodo-loading" style="display: none;">
        <div class="dodo-spinner"></div>
        <p class="dodo-loading-text">
            <?php echo esc_html__('Site içeriği taranıyor ve AI fırsatlar üretiyor...', 'dodo-ai-seo'); ?>
        </p>
        <p class="dodo-loading-subtext">
            <?php echo esc_html__('Bu işlem 30-60 saniye sürebilir. Lütfen sayfayı kapatmayın.', 'dodo-ai-seo'); ?>
        </p>
    </div>
    
    <!-- Result Message -->
    <div id="dodo-opportunities-result" class="dodo-result" style="display: none;"></div>
    
    <!-- Fırsatlar Tablosu -->
    <?php if (empty($opportunities)) : ?>
        <div class="dodo-card">
            <div style="text-align: center; padding: 40px 20px;">
                <span class="dashicons dashicons-lightbulb" style="font-size: 64px; color: #dcdcde;"></span>
                <h2 style="margin-top: 20px; color: #646970;">
                    <?php echo esc_html__('Henüz fırsat üretilmedi', 'dodo-ai-seo'); ?>
                </h2>
                <p style="color: #646970;">
                    <?php echo esc_html__('Yukarıdaki "Yeni Fırsatlar Üret" butonuna tıklayarak AI\'ın site içeriğinizi analiz etmesini ve yeni keyword fırsatları önermesini sağlayın.', 'dodo-ai-seo'); ?>
                </p>
                
                <?php 
                // GSC bağlantı durumunu kontrol et
                $gsc_intelligence = new DODO_GSC_Intelligence();
                $gsc_available = $gsc_intelligence->is_available();
                ?>
                
                <?php if (!$gsc_available) : ?>
                    <div style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px; padding: 15px; margin-top: 20px; text-align: left;">
                        <h4 style="margin-top: 0; color: #856404;">
                            ℹ️ Google Search Console Bağlı Değil
                        </h4>
                        <p style="color: #856404; margin-bottom: 10px;">
                            GSC bağlı olmadığı için fırsatlar sadece AI analizi ve site içeriği ile üretilecek. 
                            Gerçek GSC verisi (gösterim, tıklama, CTR, pozisyon) için GSC'yi bağlayın.
                        </p>
                        <a href="<?php echo admin_url('admin.php?page=dodo-ai-seo-settings#gsc-settings'); ?>" class="button button-primary">
                            Google Search Console'u Bağla
                        </a>
                    </div>
                <?php else : ?>
                    <div style="background: #d1f2eb; border: 1px solid #00a32a; border-radius: 8px; padding: 15px; margin-top: 20px; text-align: left;">
                        <h4 style="margin-top: 0; color: #00a32a;">
                            ✅ Google Search Console Bağlı
                        </h4>
                        <p style="color: #00a32a; margin: 0;">
                            Fırsatlar gerçek GSC verisi (gösterim, tıklama, CTR, pozisyon) ile üretilecek.
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php else : ?>
        <div class="dodo-card">
            <table class="wp-list-table widefat fixed striped" id="dodo-opportunities-table">
                <thead>
                    <tr>
                        <th style="width: 3%;"></th>
                        <th style="width: 17%;">Anahtar Kelime</th>
                        <th style="width: 8%;">Kaynak</th>
                        <th style="width: 6%;">Gösterim</th>
                        <th style="width: 5%;">Tıklama</th>
                        <th style="width: 5%;">CTR</th>
                        <th style="width: 5%;">Pozisyon</th>
                        <th style="width: 8%;">Intent</th>
                        <th style="width: 14%;">Önerilen Başlık</th>
                        <th style="width: 5%;">Skor</th>
                        <th style="width: 6%;">Etki</th>
                        <th style="width: 6%;">Efor</th>
                        <th style="width: 7%;">Durum</th>
                        <th style="width: 5%;">Aksiyon</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // Source labels
                    $source_labels = array(
                        'gsc_ctr' => array('label' => 'GSC CTR', 'color' => '#2271b1', 'icon' => '📊'),
                        'gsc_ranking' => array('label' => 'GSC Sıralama', 'color' => '#00a32a', 'icon' => '📈'),
                        'gsc_decay' => array('label' => 'GSC Düşüş', 'color' => '#d63638', 'icon' => '📉'),
                        'gsc_cannibalization' => array('label' => 'Kannibalizasyon', 'color' => '#f0b849', 'icon' => '⚠️'),
                        'gsc_gap' => array('label' => 'GSC Boşluk', 'color' => '#8c8f94', 'icon' => '🔍'),
                        'ai_suggestion' => array('label' => 'AI Öneri', 'color' => '#7c3aed', 'icon' => '🤖'),
                        'semantic_gap' => array('label' => 'Semantik Boşluk', 'color' => '#059669', 'icon' => '🧠'),
                    );
                    
                    foreach ($opportunities as $opp) : 
                        $source = $opp['source'] ?? 'ai_suggestion';
                        $source_info = $source_labels[$source] ?? $source_labels['ai_suggestion'];
                        
                        // Decode JSON fields for intelligence panel
                        $serp_features = !empty($opp['serp_features']) ? json_decode($opp['serp_features'], true) : array();
                        $quick_wins = !empty($opp['quick_wins']) ? json_decode($opp['quick_wins'], true) : array();
                        $seo_recommendations = !empty($opp['seo_recommendations']) ? json_decode($opp['seo_recommendations'], true) : array();
                        $problem_analysis = !empty($opp['problem_analysis']) ? json_decode($opp['problem_analysis'], true) : array();
                        
                        $has_intelligence = !empty($serp_features) || !empty($quick_wins) || !empty($seo_recommendations) || !empty($problem_analysis);
                    ?>
                        <tr data-id="<?php echo esc_attr($opp['id']); ?>" class="dodo-opp-row">
                            <td>
                                <?php if ($has_intelligence) : ?>
                                    <button type="button" class="button button-small dodo-expand-intelligence" data-id="<?php echo esc_attr($opp['id']); ?>" style="padding: 2px 6px; min-width: 0;">
                                        <span class="dashicons dashicons-arrow-down-alt2" style="font-size: 14px; width: 14px; height: 14px;"></span>
                                    </button>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo esc_html($opp['keyword']); ?></strong>
                                <?php if ($opp['similar_content_exists']) : ?>
                                    <br><span class="description" style="color: #f0b849;">⚠️ Benzer içerik var</span>
                                <?php endif; ?>
                                <?php if (!empty($opp['recommended_action'])) : ?>
                                    <br><span class="description" style="font-size: 11px;"><?php echo esc_html($opp['recommended_action']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="display: inline-block; background: <?php echo esc_attr($source_info['color']); ?>; color: #fff; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 600;">
                                    <?php echo esc_html($source_info['icon'] . ' ' . $source_info['label']); ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                $impressions = isset($opp['impressions']) ? intval($opp['impressions']) : 0;
                                if ($impressions > 0) {
                                    echo '<strong>' . esc_html(number_format($impressions)) . '</strong>';
                                } else {
                                    echo '<span style="color: #8c8f94;">-</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php 
                                $clicks = isset($opp['clicks']) ? intval($opp['clicks']) : 0;
                                if ($clicks > 0) {
                                    echo '<strong>' . esc_html(number_format($clicks)) . '</strong>';
                                } else {
                                    echo '<span style="color: #8c8f94;">-</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php 
                                $ctr = isset($opp['ctr']) ? floatval($opp['ctr']) : 0;
                                if ($ctr > 0) {
                                    $ctr_color = $ctr < 2 ? '#d63638' : ($ctr < 5 ? '#f0b849' : '#00a32a');
                                    echo '<span style="color: ' . esc_attr($ctr_color) . '; font-weight: 600;">' . esc_html(number_format($ctr, 2)) . '%</span>';
                                } else {
                                    echo '<span style="color: #8c8f94;">-</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php 
                                $position = isset($opp['position']) ? floatval($opp['position']) : 0;
                                if ($position > 0) {
                                    $pos_color = $position <= 3 ? '#00a32a' : ($position <= 10 ? '#f0b849' : '#8c8f94');
                                    echo '<span style="color: ' . esc_attr($pos_color) . '; font-weight: 600;">' . esc_html(number_format($position, 1)) . '</span>';
                                } else {
                                    echo '<span style="color: #8c8f94;">-</span>';
                                }
                                ?>
                            </td>
                            <td><?php echo esc_html($intent_labels[$opp['intent']] ?? $opp['intent']); ?></td>
                            <td style="font-size: 12px;"><?php echo esc_html($opp['suggested_title']); ?></td>
                            <td>
                                <span class="dodo-score-badge" style="display: inline-block; background: <?php echo $opp['score'] >= 70 ? '#00a32a' : ($opp['score'] >= 50 ? '#f0b849' : '#646970'); ?>; color: #fff; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">
                                    <?php echo esc_html($opp['score']); ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                $impact = $opp['impact'] ?? 'medium';
                                $impact_labels = array('low' => 'Düşük', 'medium' => 'Orta', 'high' => 'Yüksek');
                                $impact_colors = array('low' => '#8c8f94', 'medium' => '#f0b849', 'high' => '#00a32a');
                                ?>
                                <span style="color: <?php echo esc_attr($impact_colors[$impact] ?? '#8c8f94'); ?>; font-weight: 600; font-size: 11px;">
                                    <?php echo esc_html($impact_labels[$impact] ?? $impact); ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                $effort = $opp['effort'] ?? 'medium';
                                $effort_labels = array('low' => 'Düşük', 'medium' => 'Orta', 'high' => 'Yüksek');
                                $effort_colors = array('low' => '#00a32a', 'medium' => '#f0b849', 'high' => '#d63638');
                                ?>
                                <span style="color: <?php echo esc_attr($effort_colors[$effort] ?? '#8c8f94'); ?>; font-weight: 600; font-size: 11px;">
                                    <?php echo esc_html($effort_labels[$effort] ?? $effort); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html($status_labels[$opp['status']] ?? $opp['status']); ?></td>
                            <td>
                                <?php if ($opp['status'] === 'new' || $opp['status'] === 'queued') : ?>
                                    <button type="button" class="button button-small button-primary dodo-create-blog" data-id="<?php echo esc_attr($opp['id']); ?>" data-keyword="<?php echo esc_attr($opp['keyword']); ?>" data-content-type="<?php echo esc_attr($opp['content_type']); ?>">
                                        Blog Oluştur
                                    </button>
                                    <br>
                                    <button type="button" class="button button-small dodo-queue-opportunity" data-id="<?php echo esc_attr($opp['id']); ?>" style="margin-top: 5px;">
                                        Kuyruğa Ekle
                                    </button>
                                    <br>
                                    <button type="button" class="button button-small dodo-ignore-opportunity" data-id="<?php echo esc_attr($opp['id']); ?>" style="margin-top: 5px;">
                                        Yok Say
                                    </button>
                                <?php elseif ($opp['status'] === 'failed' || $opp['status'] === 'permanently_failed') : ?>
                                    <button type="button" class="button button-small button-primary dodo-retry-opportunity" data-id="<?php echo esc_attr($opp['id']); ?>">
                                        🔄 Tekrar Dene
                                    </button>
                                    <br>
                                    <button type="button" class="button button-small dodo-ignore-opportunity" data-id="<?php echo esc_attr($opp['id']); ?>" style="margin-top: 5px;">
                                        Yok Say
                                    </button>
                                <?php elseif ($opp['status'] === 'created') : ?>
                                    <span style="color: #00a32a;">✅ Oluşturuldu</span>
                                <?php else : ?>
                                    <button type="button" class="button button-small dodo-restore-opportunity" data-id="<?php echo esc_attr($opp['id']); ?>">
                                        Geri Al
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        
                        <!-- Intelligence Panel (Sprint C - Task 10) -->
                        <?php if ($has_intelligence) : ?>
                        <tr class="dodo-intelligence-panel" id="dodo-intelligence-<?php echo esc_attr($opp['id']); ?>" style="display: none;">
                            <td colspan="14" style="background: #f6f7f7; padding: 20px; border-left: 4px solid <?php echo esc_attr($source_info['color']); ?>;">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                    
                                    <!-- Problem Analysis -->
                                    <?php if (!empty($problem_analysis)) : ?>
                                    <div style="background: #fff; padding: 15px; border-radius: 8px; border: 1px solid #dcdcde;">
                                        <h4 style="margin-top: 0; color: #d63638; display: flex; align-items: center; gap: 8px;">
                                            <span class="dashicons dashicons-warning"></span>
                                            Problem Analizi
                                        </h4>
                                        
                                        <?php if (!empty($problem_analysis['explanation'])) : ?>
                                            <p style="background: #fff3cd; padding: 10px; border-radius: 4px; border-left: 3px solid #f0b849; margin: 10px 0; font-size: 13px;">
                                                <?php echo esc_html($problem_analysis['explanation']); ?>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($problem_analysis['severity'])) : ?>
                                            <p style="margin: 8px 0;">
                                                <strong>Önem Derecesi:</strong> 
                                                <span style="color: <?php echo $problem_analysis['severity'] === 'critical' ? '#d63638' : ($problem_analysis['severity'] === 'high' ? '#f0b849' : '#8c8f94'); ?>; font-weight: 600;">
                                                    <?php echo esc_html(ucfirst($problem_analysis['severity'])); ?>
                                                </span>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($problem_analysis['estimated_clicks_gain'])) : ?>
                                            <p style="margin: 8px 0;">
                                                <strong>Tahmini Tıklama Kazancı:</strong> 
                                                <span style="color: #00a32a; font-weight: 600;">
                                                    +<?php echo esc_html(number_format($problem_analysis['estimated_clicks_gain'])); ?> tıklama/ay
                                                </span>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <!-- Quick Wins -->
                                    <?php if (!empty($quick_wins)) : ?>
                                    <div style="background: #fff; padding: 15px; border-radius: 8px; border: 1px solid #dcdcde;">
                                        <h4 style="margin-top: 0; color: #00a32a; display: flex; align-items: center; gap: 8px;">
                                            <span class="dashicons dashicons-yes-alt"></span>
                                            Hızlı Kazançlar
                                        </h4>
                                        <ul style="margin: 10px 0; padding-left: 20px;">
                                            <?php foreach ($quick_wins as $win) : ?>
                                                <li style="margin: 6px 0; font-size: 13px;"><?php echo esc_html($win); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <!-- SEO Recommendations -->
                                    <?php if (!empty($seo_recommendations)) : ?>
                                    <div style="background: #fff; padding: 15px; border-radius: 8px; border: 1px solid #dcdcde;">
                                        <h4 style="margin-top: 0; color: #2271b1; display: flex; align-items: center; gap: 8px;">
                                            <span class="dashicons dashicons-lightbulb"></span>
                                            SEO Önerileri
                                        </h4>
                                        <ul style="margin: 10px 0; padding-left: 20px;">
                                            <?php foreach (array_slice($seo_recommendations, 0, 5) as $rec) : ?>
                                                <li style="margin: 6px 0; font-size: 13px;"><?php echo esc_html($rec); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <!-- SERP Features -->
                                    <?php if (!empty($serp_features)) : ?>
                                    <div style="background: #fff; padding: 15px; border-radius: 8px; border: 1px solid #dcdcde;">
                                        <h4 style="margin-top: 0; color: #7c3aed; display: flex; align-items: center; gap: 8px;">
                                            <span class="dashicons dashicons-search"></span>
                                            SERP Özellikleri
                                        </h4>
                                        
                                        <?php if (!empty($opp['serp_type'])) : ?>
                                            <p style="margin: 8px 0;">
                                                <strong>SERP Tipi:</strong> 
                                                <span style="background: #f0f6fc; padding: 3px 8px; border-radius: 4px; font-size: 12px;">
                                                    <?php echo esc_html($opp['serp_type']); ?>
                                                </span>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <ul style="margin: 10px 0; padding-left: 20px;">
                                            <?php foreach ($serp_features as $feature) : ?>
                                                <li style="margin: 6px 0; font-size: 13px;"><?php echo esc_html($feature); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                    <?php endif; ?>
                                    
                                </div>
                                
                                <!-- Expected Impact -->
                                <?php if (!empty($problem_analysis['estimated_clicks_gain']) || !empty($quick_wins)) : ?>
                                <div style="background: #e7f5ff; padding: 15px; border-radius: 8px; margin-top: 15px; border-left: 4px solid #2271b1;">
                                    <h4 style="margin-top: 0; color: #2271b1;">📊 Beklenen Etki</h4>
                                    <p style="margin: 0; font-size: 13px;">
                                        Bu fırsatı değerlendirdiğinizde:
                                        <?php if (!empty($problem_analysis['estimated_clicks_gain'])) : ?>
                                            <strong>+<?php echo esc_html(number_format($problem_analysis['estimated_clicks_gain'])); ?> tıklama/ay</strong> kazanç,
                                        <?php endif; ?>
                                        <?php if (!empty($quick_wins)) : ?>
                                            <strong><?php echo count($quick_wins); ?> hızlı kazanç</strong> fırsatı,
                                        <?php endif; ?>
                                        ve <strong><?php echo esc_html($opp['effort'] ?? 'orta'); ?> efor</strong> gerekiyor.
                                    </p>
                                </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Publish Mode Modal -->
<div id="dodo-publish-mode-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 100000; align-items: center; justify-content: center;">
    <div style="background: #fff; border-radius: 8px; padding: 30px; max-width: 500px; width: 90%; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
        <h2 style="margin-top: 0; margin-bottom: 20px;">
            📅 Yayın Modu Seçin
        </h2>
        
        <div style="margin-bottom: 20px;">
            <label style="display: block; font-weight: 600; margin-bottom: 8px;">
                Anahtar Kelime:
            </label>
            <div id="dodo-modal-keyword" style="padding: 10px; background: #f0f6fc; border-radius: 4px; font-weight: 600; color: #2271b1;">
                -
            </div>
        </div>
        
        <div style="margin-bottom: 20px;">
            <label for="dodo-modal-publish-mode" style="display: block; font-weight: 600; margin-bottom: 8px;">
                Yayın Modu:
            </label>
            <select id="dodo-modal-publish-mode" class="dodo-select" style="width: 100%;">
                <option value="default">Varsayılan ayarı kullan</option>
                <option value="draft">Taslak olarak oluştur</option>
                <option value="publish">Hemen yayınla</option>
                <option value="scheduled">Zamanlanmış yayınla</option>
            </select>
        </div>
        
        <!-- Zamanlanmış Yayın Ayarları (Conditional) -->
        <div id="dodo-modal-scheduled-options" style="display: none; background: #f0f6fc; padding: 15px; border-radius: 8px; border: 1px solid #c3e0f7; margin-bottom: 20px;">
            <h4 style="margin-top: 0; color: #2271b1; font-size: 14px;">
                📅 Zamanlanmış Yayın Ayarları
            </h4>
            
            <div style="margin-bottom: 15px;">
                <label for="dodo-modal-scheduled-date" style="display: block; font-weight: 600; margin-bottom: 8px; font-size: 13px;">
                    Yayın Tarihi <span style="color: #646970; font-weight: 400;">(Opsiyonel)</span>
                </label>
                <input 
                    type="date" 
                    id="dodo-modal-scheduled-date" 
                    class="dodo-input"
                    style="width: 100%;"
                    min="<?php echo date('Y-m-d'); ?>"
                >
                <p style="margin: 5px 0 0 0; font-size: 12px; color: #646970;">
                    Boş bırakırsanız otomatik hesaplanır
                </p>
            </div>
            
            <div>
                <label for="dodo-modal-scheduled-time" style="display: block; font-weight: 600; margin-bottom: 8px; font-size: 13px;">
                    Yayın Saati <span style="color: #646970; font-weight: 400;">(Opsiyonel)</span>
                </label>
                <input 
                    type="time" 
                    id="dodo-modal-scheduled-time" 
                    class="dodo-input"
                    style="width: 100%;"
                    value="10:00"
                >
                <p style="margin: 5px 0 0 0; font-size: 12px; color: #646970;">
                    Boş bırakırsanız varsayılan saat kullanılır
                </p>
            </div>
            
            <div style="background: #fff; padding: 10px; border-radius: 4px; border-left: 4px solid #2271b1; margin-top: 15px;">
                <p style="margin: 0; font-size: 12px;">
                    <strong>💡 İpucu:</strong> Tarih ve saati boş bırakırsanız, Publishing Scheduler ayarlarına göre otomatik olarak bir sonraki uygun slot hesaplanır.
                </p>
            </div>
        </div>
        
        <div style="display: flex; gap: 10px; justify-content: flex-end;">
            <button type="button" id="dodo-modal-cancel" class="button button-secondary">
                İptal
            </button>
            <button type="button" id="dodo-modal-confirm" class="button button-primary">
                Kuyruğa Ekle
            </button>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    
    // CSS animasyon ekle
    if (!$('#dodo-queue-styles').length) {
        $('<style id="dodo-queue-styles">' +
          '.dodo-spin { animation: dodo-spin 1s linear infinite; } ' +
          '@keyframes dodo-spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }' +
          '</style>').appendTo('head');
    }
    
    // Yeni fırsatlar üret
    $('#dodo-generate-opportunities').on('click', function() {
        if (!confirm('Site içeriği taranacak ve AI yeni keyword fırsatları üretecek. Bu işlem 30-60 saniye sürebilir. Devam etmek istiyor musunuz?')) {
            return;
        }
        
        const $btn = $(this);
        const $loading = $('#dodo-opportunities-loading');
        const $result = $('#dodo-opportunities-result');
        
        $btn.prop('disabled', true);
        $loading.fadeIn();
        $result.hide();
        
        $.ajax({
            url: dodoAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'dodo_generate_keyword_opportunities',
                nonce: dodoAjax.nonce
            },
            timeout: 120000, // 2 dakika
            success: function(response) {
                if (response.success) {
                    $result
                        .removeClass('error')
                        .addClass('success')
                        .html('<div class="dodo-result-title">✅ Başarılı!</div><p>' + response.data.message + '</p>')
                        .fadeIn();
                    
                    // Sayfayı yenile
                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                } else {
                    $result
                        .removeClass('success')
                        .addClass('error')
                        .html('<div class="dodo-result-title">❌ Hata!</div><p>' + response.data.message + '</p>')
                        .fadeIn();
                }
            },
            error: function(xhr, status, error) {
                $result
                    .removeClass('success')
                    .addClass('error')
                    .html('<div class="dodo-result-title">❌ Hata!</div><p>Bir hata oluştu. Lütfen tekrar deneyin.</p>')
                    .fadeIn();
            },
            complete: function() {
                $btn.prop('disabled', false);
                $loading.fadeOut();
            }
        });
    });
    
    // Tümünü sil
    $('#dodo-delete-all-opportunities').on('click', function() {
        if (!confirm('Tüm keyword fırsatları silinecek. Bu işlem geri alınamaz. Devam etmek istiyor musunuz?')) {
            return;
        }
        
        const $btn = $(this);
        
        $btn.prop('disabled', true);
        
        $.ajax({
            url: dodoAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'dodo_delete_all_opportunities',
                nonce: dodoAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    window.location.reload();
                } else {
                    alert('Hata: ' + response.data.message);
                    $btn.prop('disabled', false);
                }
            },
            error: function() {
                alert('Bir hata oluştu. Lütfen tekrar deneyin.');
                $btn.prop('disabled', false);
            }
        });
    });
    
    // Status filter
    $('#dodo-status-filter').on('change', function() {
        const status = $(this).val();
        window.location.href = '<?php echo admin_url('admin.php?page=dodo-ai-seo-keyword-opportunities'); ?>&status_filter=' + status;
    });
    
    // Blog oluştur
    $('.dodo-create-blog').on('click', function() {
        const $btn = $(this);
        const id = $btn.data('id');
        const keyword = $btn.data('keyword');
        const contentType = $btn.data('content-type');
        
        // Yeni Blog Oluştur sayfasına yönlendir ve keyword'ü URL'ye ekle
        window.location.href = '<?php echo admin_url('admin.php?page=dodo-ai-seo'); ?>&keyword=' + encodeURIComponent(keyword) + '&content_type=' + encodeURIComponent(contentType) + '&opportunity_id=' + id;
    });
    
    // Kuyruğa ekle - Modal aç
    let currentOpportunityId = null;
    
    $('.dodo-queue-opportunity').on('click', function() {
        currentOpportunityId = $(this).data('id');
        const keyword = $(this).closest('tr').find('td:first strong').text();
        
        // Modal'ı aç
        $('#dodo-modal-keyword').text(keyword);
        $('#dodo-modal-publish-mode').val('default');
        $('#dodo-modal-scheduled-date').val('');
        $('#dodo-modal-scheduled-time').val('10:00');
        $('#dodo-modal-scheduled-options').hide();
        $('#dodo-publish-mode-modal').css('display', 'flex').hide().fadeIn(200);
    });
    
    // Modal - Publish mode değiştiğinde
    $('#dodo-modal-publish-mode').on('change', function() {
        if ($(this).val() === 'scheduled') {
            $('#dodo-modal-scheduled-options').slideDown();
        } else {
            $('#dodo-modal-scheduled-options').slideUp();
        }
    });
    
    // Modal - İptal
    $('#dodo-modal-cancel').on('click', function() {
        $('#dodo-publish-mode-modal').fadeOut(200);
        currentOpportunityId = null;
    });
    
    // Modal - Kuyruğa ekle (confirm)
    $('#dodo-modal-confirm').on('click', function() {
        if (!currentOpportunityId) {
            return;
        }
        
        const publishMode = $('#dodo-modal-publish-mode').val();
        const scheduledDate = $('#dodo-modal-scheduled-date').val();
        const scheduledTime = $('#dodo-modal-scheduled-time').val();
        
        // Validation
        if (publishMode === 'scheduled' && scheduledDate && scheduledTime) {
            // Date/time format validation
            if (!/^\d{4}-\d{2}-\d{2}$/.test(scheduledDate)) {
                alert('Geçersiz tarih formatı. YYYY-MM-DD formatında olmalı.');
                return;
            }
            if (!/^\d{2}:\d{2}$/.test(scheduledTime)) {
                alert('Geçersiz saat formatı. HH:MM formatında olmalı.');
                return;
            }
        }
        
        // AJAX ile kuyruğa ekle
        updateOpportunityStatusWithPublishMode(
            currentOpportunityId, 
            'queued', 
            publishMode,
            scheduledDate,
            scheduledTime
        );
        
        // Modal'ı kapat
        $('#dodo-publish-mode-modal').fadeOut(200);
        currentOpportunityId = null;
    });
    
    // Modal dışına tıklanınca kapat
    $('#dodo-publish-mode-modal').on('click', function(e) {
        if (e.target === this) {
            $(this).fadeOut(200);
            currentOpportunityId = null;
        }
    });
    
    // Yok say
    $('.dodo-ignore-opportunity').on('click', function() {
        if (!confirm('Bu fırsatı yok saymak istediğinizden emin misiniz?')) {
            return;
        }
        updateOpportunityStatus($(this).data('id'), 'ignored');
    });
    
    // Geri al
    $('.dodo-restore-opportunity').on('click', function() {
        updateOpportunityStatus($(this).data('id'), 'new');
    });
    
    // Retry - Manuel tekrar dene
    $('.dodo-retry-opportunity').on('click', function() {
        if (!confirm('Bu item tekrar kuyruğa eklenecek ve işlenecek. Devam etmek istiyor musunuz?')) {
            return;
        }
        
        const $btn = $(this);
        const id = $btn.data('id');
        
        $btn.prop('disabled', true).text('İşleniyor...');
        
        $.ajax({
            url: dodoAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'dodo_retry_queue_item',
                nonce: dodoAjax.nonce,
                id: id
            },
            success: function(response) {
                if (response.success) {
                    alert('✅ ' + response.data.message);
                    window.location.reload();
                } else {
                    alert('❌ Hata: ' + response.data.message);
                    $btn.prop('disabled', false).text('🔄 Tekrar Dene');
                }
            },
            error: function() {
                alert('❌ Bir hata oluştu. Lütfen tekrar deneyin.');
                $btn.prop('disabled', false).text('🔄 Tekrar Dene');
            }
        });
    });
    
    // Status güncelleme fonksiyonu (publish mode ile)
    function updateOpportunityStatusWithPublishMode(id, status, publishMode, scheduledDate, scheduledTime) {
        $.ajax({
            url: dodoAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'dodo_update_opportunity_status',
                nonce: dodoAjax.nonce,
                id: id,
                status: status,
                publish_mode: publishMode || '',
                scheduled_date: scheduledDate || '',
                scheduled_time: scheduledTime || ''
            },
            success: function(response) {
                if (response.success) {
                    window.location.reload();
                } else {
                    alert('Hata: ' + response.data.message);
                }
            },
            error: function() {
                alert('Bir hata oluştu. Lütfen tekrar deneyin.');
            }
        });
    }
    
    // Status güncelleme fonksiyonu (eski - basit)
    function updateOpportunityStatus(id, status) {
        updateOpportunityStatusWithPublishMode(id, status, null, null, null);
    }
    
    // Kuyruğu çalıştır
    $('#dodo-process-queue').on('click', function() {
        if (!confirm('Kuyruktaki tüm fırsatlar sırayla blog yazısına dönüştürülecek. Bu işlem uzun sürebilir. Devam etmek istiyor musunuz?')) {
            return;
        }
        
        const $btn = $(this);
        const $processing = $('#dodo-queue-processing');
        const $result = $('#dodo-opportunities-result');
        
        $btn.prop('disabled', true);
        $processing.fadeIn();
        $result.hide();
        
        // İstatistikleri sıfırla
        let processed = 0;
        let success = 0;
        let failed = 0;
        const total = parseInt($('.dodo-queue-count').text().replace(/[()]/g, ''));
        
        $('#dodo-queue-total').text(total);
        $('#dodo-queue-processed').text(0);
        $('#dodo-queue-success').text(0);
        $('#dodo-queue-failed').text(0);
        
        // Sıradaki item'ı işle
        function processNextItem() {
            $.ajax({
                url: dodoAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'dodo_process_queue_item',
                    nonce: dodoAjax.nonce
                },
                timeout: 120000, // 2 dakika
                success: function(response) {
                    if (response.success) {
                        if (response.data.completed) {
                            // Kuyruk tamamlandı
                            $processing.fadeOut();
                            $result
                                .removeClass('error')
                                .addClass('success')
                                .html('<div class="dodo-result-title">✅ Kuyruk Tamamlandı!</div>' +
                                      '<p>Toplam: ' + processed + ' | Başarılı: ' + success + ' | Hatalı: ' + failed + '</p>')
                                .fadeIn();
                            
                            // Sayfayı yenile
                            setTimeout(function() {
                                window.location.reload();
                            }, 3000);
                        } else {
                            // Başarılı - sıradaki item'a geç
                            processed++;
                            success++;
                            
                            $('#dodo-queue-processed').text(processed);
                            $('#dodo-queue-success').text(success);
                            $('#dodo-queue-current').text(response.data.keyword || '-');
                            
                            const progress = (processed / total) * 100;
                            $('#dodo-queue-progress-bar').css('width', progress + '%');
                            
                            // Sıradaki item'ı işle
                            setTimeout(processNextItem, 1000); // 1 saniye bekle
                        }
                    } else {
                        // Hata durumu
                        processed++;
                        failed++;
                        
                        $('#dodo-queue-processed').text(processed);
                        $('#dodo-queue-failed').text(failed);
                        
                        const progress = (processed / total) * 100;
                        $('#dodo-queue-progress-bar').css('width', progress + '%');
                        
                        console.error('Queue item failed:', response.data.message);
                        
                        // STOP_QUEUE kontrolü - kritik hatalar için queue'yu durdur
                        if (response.data && response.data.stop_queue === true) {
                            // Queue'yu durdur
                            $processing.fadeOut();
                            
                            // Kullanıcıya kritik hata mesajı göster
                            const errorTitle = response.data.error_category === 'openai_quota_exceeded' 
                                ? '🚫 OpenAI API Kotası Doldu' 
                                : '🚫 Kritik Hata - Queue Durduruldu';
                            
                            const errorMessage = response.data.message || 'Kritik bir hata oluştu ve queue durduruldu.';
                            
                            $result
                                .removeClass('success')
                                .addClass('error')
                                .html('<div class="dodo-result-title">' + errorTitle + '</div>' +
                                      '<p style="font-size: 15px; line-height: 1.6;">' + errorMessage + '</p>' +
                                      '<p style="margin-top: 15px; padding: 12px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px;">' +
                                      '<strong>📊 İstatistikler:</strong><br>' +
                                      'Toplam İşlenen: ' + processed + ' | Başarılı: ' + success + ' | Hatalı: ' + failed +
                                      '</p>' +
                                      '<p style="margin-top: 15px; font-size: 13px; color: #646970;">' +
                                      'Queue otomatik olarak durduruldu. Sorunu çözdükten sonra kalan itemları tekrar çalıştırabilirsiniz.' +
                                      '</p>')
                                .fadeIn();
                            
                            // Butonu tekrar aktif et
                            $btn.prop('disabled', false);
                            
                            // Queue'yu DURDUR - processNextItem çağrılmayacak
                            console.error('QUEUE STOPPED due to critical error:', response.data);
                            return; // İşlemi sonlandır
                        }
                        
                        // Normal hata - devam et
                        setTimeout(processNextItem, 1000);
                    }
                },
                error: function(xhr, status, error) {
                    // AJAX hatası - ama devam et
                    processed++;
                    failed++;
                    
                    $('#dodo-queue-processed').text(processed);
                    $('#dodo-queue-failed').text(failed);
                    
                    const progress = (processed / total) * 100;
                    $('#dodo-queue-progress-bar').css('width', progress + '%');
                    
                    console.error('AJAX error:', error);
                    
                    // Sıradaki item'ı işle
                    setTimeout(processNextItem, 1000);
                }
            });
        }
        
        // İlk item'ı işle
        processNextItem();
    });
    
    // Intelligence Panel Toggle (Sprint C - Task 10)
    $(document).on('click', '.dodo-expand-intelligence', function() {
        const $btn = $(this);
        const oppId = $btn.data('id');
        const $panel = $('#dodo-intelligence-' + oppId);
        const $icon = $btn.find('.dashicons');
        
        if ($panel.is(':visible')) {
            // Collapse
            $panel.fadeOut(200);
            $icon.removeClass('dashicons-arrow-up-alt2').addClass('dashicons-arrow-down-alt2');
        } else {
            // Expand
            $panel.fadeIn(200);
            $icon.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-up-alt2');
        }
    });
});
</script>
