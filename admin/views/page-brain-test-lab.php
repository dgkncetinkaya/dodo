<?php
/**
 * Brain Test Lab - Admin Test Panel
 * 
 * Test interface for Brain Core engines
 *
 * @package DODO_AI_SEO
 * @since 2.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Initialize Brain Core
$brain = new Dodo_Brain_Core();

// Handle test request
$test_results = null;
$test_type = '';
$test_input = '';

if (isset($_POST['dodo_brain_test']) && check_admin_referer('dodo_brain_test', 'dodo_brain_test_nonce')) {
    $test_type = sanitize_text_field($_POST['test_type']);
    $test_input = sanitize_text_field($_POST['test_input']);
    
    try {
        // Validate input based on test type
        if ($test_type === 'engine_status') {
            // Engine status doesn't need input
            $test_results = $brain->get_engine_status();
        } elseif ($test_type === 'post_analysis') {
            // Post analysis needs numeric ID
            $post_id = intval($test_input);
            if ($post_id <= 0) {
                $test_results = [
                    'error' => true,
                    'message' => 'Yazı analizi için geçerli bir Yazı ID girin.'
                ];
            } else {
                $test_results = $brain->analyze_post($post_id);
            }
        } else {
            // Other tests need non-empty input
            if (empty(trim($test_input))) {
                $test_results = [
                    'error' => true,
                    'message' => 'Bu test için girdi gereklidir. Lütfen anahtar kelime veya konu girin.'
                ];
            } else {
                switch ($test_type) {
                    case 'keyword_full':
                        $test_results = $brain->analyze_keyword($test_input);
                        break;
                        
                    case 'keyword_quick':
                        $test_results = $brain->quick_check($test_input);
                        break;
                        
                    case 'cluster_analysis':
                        $test_results = $brain->analyze_cluster($test_input);
                        break;
                        
                    default:
                        $test_results = [
                            'error' => true,
                            'message' => 'Geçersiz test türü.'
                        ];
                }
            }
        }
    } catch (Throwable $e) {
        $test_results = [
            'error' => true,
            'message' => 'Test sırasında hata oluştu: ' . esc_html($e->getMessage())
        ];
        error_log('DODO Brain Test Lab Error: ' . $e->getMessage());
    }
}

// Handle cache clear
if (isset($_POST['dodo_clear_cache']) && check_admin_referer('dodo_clear_cache', 'dodo_clear_cache_nonce')) {
    $brain->clear_cache();
    echo '<div class="notice notice-success"><p>Brain Core cache cleared successfully.</p></div>';
}
?>

<div class="wrap">
    <h1>Beyin Test Merkezi</h1>
    <p class="description">DODO AI SEO Beyin Çekirdeği motorlarını test edin ve hata ayıklayın</p>
    
    <div class="dodo-brain-test-container" style="display: flex; gap: 20px; margin-top: 20px;">
        
        <!-- Left Panel: Test Controls -->
        <div class="dodo-brain-test-controls" style="flex: 0 0 350px; background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
            <h2>Test Kontrolleri</h2>
            
            <form method="post" action="">
                <?php wp_nonce_field('dodo_brain_test', 'dodo_brain_test_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="test_type">Test Türü</label>
                        </th>
                        <td>
                            <select name="test_type" id="test_type" class="regular-text" required>
                                <option value="">Test Türü Seç</option>
                                <option value="keyword_full" <?php selected($test_type, 'keyword_full'); ?>>Tam Anahtar Kelime Analizi</option>
                                <option value="keyword_quick" <?php selected($test_type, 'keyword_quick'); ?>>Hızlı Anahtar Kelime Kontrolü</option>
                                <option value="post_analysis" <?php selected($test_type, 'post_analysis'); ?>>Yazı Analizi</option>
                                <option value="cluster_analysis" <?php selected($test_type, 'cluster_analysis'); ?>>Küme Analizi</option>
                                <option value="engine_status" <?php selected($test_type, 'engine_status'); ?>>Motor Durumu</option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="test_input">Girdi</label>
                        </th>
                        <td>
                            <input type="text" name="test_input" id="test_input" class="regular-text" 
                                   value="<?php echo esc_attr($test_input); ?>" 
                                   placeholder="Anahtar kelime veya Yazı ID">
                            <p class="description">
                                Anahtar kelime testleri için kelime girin<br>
                                Yazı analizi için Yazı ID girin<br>
                                Küme analizi için konu girin<br>
                                Motor durumu için boş bırakın
                            </p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" name="dodo_brain_test" class="button button-primary button-large">
                        Testi Çalıştır
                    </button>
                </p>
            </form>
            
            <hr style="margin: 30px 0;">
            
            <h3>Önbellek Yönetimi</h3>
            <form method="post" action="">
                <?php wp_nonce_field('dodo_clear_cache', 'dodo_clear_cache_nonce'); ?>
                <p>
                    <button type="submit" name="dodo_clear_cache" class="button button-secondary">
                        Beyin Önbelleğini Temizle
                    </button>
                </p>
            </form>
            
            <hr style="margin: 30px 0;">
            
            <h3>Hızlı Örnekler</h3>
            <ul style="list-style: disc; margin-left: 20px;">
                <li><strong>Tam Analiz:</strong> "wordpress seo eklentisi"</li>
                <li><strong>Hızlı Kontrol:</strong> "en iyi seo araçları"</li>
                <li><strong>Yazı Analizi:</strong> Yazı ID girin</li>
                <li><strong>Küme:</strong> "seo"</li>
            </ul>
        </div>
        
        <!-- Right Panel: Test Results -->
        <div class="dodo-brain-test-results" style="flex: 1; background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
            <h2>Test Sonuçları</h2>
            
            <?php if ($test_results): ?>
                
                <?php if (isset($test_results['error']) && $test_results['error']): ?>
                    <!-- Error Display -->
                    <div class="dodo-brain-error" style="background: #fef7f1; border-left: 4px solid #d63638; padding: 20px; border-radius: 4px; margin-bottom: 20px;">
                        <h3 style="margin: 0 0 10px 0; color: #d63638;">⚠️ Test Hatası</h3>
                        <p style="margin: 0; font-size: 14px; color: #50575e;">
                            <?php echo esc_html($test_results['message']); ?>
                        </p>
                    </div>
                <?php else: ?>
                
                <div class="dodo-brain-results-header" style="background: #f0f0f1; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
                    <h3 style="margin: 0 0 10px 0;">
                        <?php 
                        switch ($test_type) {
                            case 'keyword_full':
                                echo 'Tam Anahtar Kelime Analizi';
                                break;
                            case 'keyword_quick':
                                echo 'Hızlı Anahtar Kelime Kontrolü';
                                break;
                            case 'post_analysis':
                                echo 'Yazı Analizi';
                                break;
                            case 'cluster_analysis':
                                echo 'Küme Analizi';
                                break;
                            case 'engine_status':
                                echo 'Motor Durumu';
                                break;
                        }
                        ?>
                    </h3>
                    <p style="margin: 0; color: #646970;">
                        <strong>Girdi:</strong> <?php echo esc_html($test_input); ?><br>
                        <?php if (isset($test_results['total_execution_time_ms'])): ?>
                            <strong>Çalışma Süresi:</strong> <?php echo esc_html($test_results['total_execution_time_ms']); ?>ms
                        <?php elseif (isset($test_results['execution_time_ms'])): ?>
                            <strong>Çalışma Süresi:</strong> <?php echo esc_html($test_results['execution_time_ms']); ?>ms
                        <?php endif; ?>
                    </p>
                </div>
                
                <?php if ($test_type === 'keyword_full' && isset($test_results['overall_score'])): ?>
                    
                    <!-- Overall Score -->
                    <div class="dodo-brain-score-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                        <h2 style="margin: 0 0 10px 0; color: white;">Genel Skor</h2>
                        <div style="font-size: 48px; font-weight: bold;"><?php echo esc_html($test_results['overall_score']); ?>/100</div>
                        <p style="margin: 10px 0 0 0; opacity: 0.9;">
                            Öncelik: <strong><?php echo esc_html(strtoupper($test_results['recommendations']['priority'])); ?></strong>
                        </p>
                    </div>
                    
                    <!-- Recommendations -->
                    <div class="dodo-brain-recommendations" style="background: #f0f6fc; border-left: 4px solid #0073aa; padding: 15px; margin-bottom: 20px;">
                        <h3 style="margin-top: 0;">Öneriler</h3>
                        <p><strong>Aksiyon:</strong> <?php echo esc_html($test_results['recommendations']['action']); ?></p>
                        <p><strong>Format:</strong> <?php echo esc_html($test_results['recommendations']['format']); ?></p>
                        <p><strong>Uzunluk:</strong> <?php echo esc_html($test_results['recommendations']['length']); ?></p>
                        <p><strong>Ton:</strong> <?php echo esc_html($test_results['recommendations']['tone']); ?></p>
                        
                        <?php if (!empty($test_results['recommendations']['warnings'])): ?>
                            <h4>Uyarılar:</h4>
                            <ul>
                                <?php foreach ($test_results['recommendations']['warnings'] as $warning): ?>
                                    <li><?php echo esc_html($warning); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        
                        <?php if (!empty($test_results['recommendations']['opportunities'])): ?>
                            <h4>Fırsatlar:</h4>
                            <ul>
                                <?php foreach ($test_results['recommendations']['opportunities'] as $opportunity): ?>
                                    <li><?php echo esc_html($opportunity); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Engine Scores -->
                    <div class="dodo-brain-engine-scores" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
                        
                        <div class="score-card" style="background: #fff; border: 1px solid #ddd; padding: 15px; border-radius: 4px;">
                            <h4 style="margin: 0 0 10px 0; color: #646970;">Anahtar Kelime Fırsatı</h4>
                            <div style="font-size: 32px; font-weight: bold; color: #2271b1;">
                                <?php echo esc_html($test_results['keyword_intelligence']['opportunity_score']); ?>
                            </div>
                            <p style="margin: 5px 0 0 0; font-size: 12px; color: #646970;">
                                Rekabet: <?php echo esc_html($test_results['keyword_intelligence']['competition']); ?>
                            </p>
                        </div>
                        
                        <div class="score-card" style="background: #fff; border: 1px solid #ddd; padding: 15px; border-radius: 4px;">
                            <h4 style="margin: 0 0 10px 0; color: #646970;">Niyet Güveni</h4>
                            <div style="font-size: 32px; font-weight: bold; color: #2271b1;">
                                <?php echo esc_html($test_results['intent']['confidence']); ?>
                            </div>
                            <p style="margin: 5px 0 0 0; font-size: 12px; color: #646970;">
                                <?php echo esc_html($test_results['intent']['primary_intent']); ?>
                            </p>
                        </div>
                        
                        <div class="score-card" style="background: #fff; border: 1px solid #ddd; padding: 15px; border-radius: 4px;">
                            <h4 style="margin: 0 0 10px 0; color: #646970;">Kannibalizasyon Riski</h4>
                            <div style="font-size: 32px; font-weight: bold; color: <?php echo $test_results['cannibalization']['risk_score'] >= 70 ? '#d63638' : '#2271b1'; ?>;">
                                <?php echo esc_html($test_results['cannibalization']['risk_score']); ?>
                            </div>
                            <p style="margin: 5px 0 0 0; font-size: 12px; color: #646970;">
                                <?php echo esc_html($test_results['cannibalization']['recommendation']); ?>
                            </p>
                        </div>
                        
                        <div class="score-card" style="background: #fff; border: 1px solid #ddd; padding: 15px; border-radius: 4px;">
                            <h4 style="margin: 0 0 10px 0; color: #646970;">GEO Skoru</h4>
                            <div style="font-size: 32px; font-weight: bold; color: #2271b1;">
                                <?php echo esc_html($test_results['geo']['overall_score']); ?>
                            </div>
                            <p style="margin: 5px 0 0 0; font-size: 12px; color: #646970;">
                                Cevap Motoru Hazır
                            </p>
                        </div>
                        
                        <div class="score-card" style="background: #fff; border: 1px solid #ddd; padding: 15px; border-radius: 4px;">
                            <h4 style="margin: 0 0 10px 0; color: #646970;">Konu Otoritesi</h4>
                            <div style="font-size: 32px; font-weight: bold; color: #2271b1;">
                                <?php echo esc_html($test_results['topical_authority']['authority_score']); ?>
                            </div>
                            <p style="margin: 5px 0 0 0; font-size: 12px; color: #646970;">
                                <?php echo esc_html($test_results['topical_authority']['cluster_analysis']['strength']); ?> küme
                            </p>
                        </div>
                        
                    </div>
                    
                <?php endif; ?>
                
                <!-- JSON Debug Panel -->
                <details style="margin-top: 20px;">
                    <summary style="cursor: pointer; font-weight: bold; padding: 10px; background: #f0f0f1; border-radius: 4px;">
                        Tam JSON Çıktısı (Genişletmek için tıklayın)
                    </summary>
                    <pre style="background: #23282d; color: #f0f0f1; padding: 20px; border-radius: 4px; overflow-x: auto; margin-top: 10px; font-size: 12px; line-height: 1.5;"><?php echo esc_html(json_encode($test_results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
                </details>
                
                <?php endif; ?>
                
            <?php else: ?>
                
                <div style="text-align: center; padding: 60px 20px; color: #646970;">
                    <p style="font-size: 48px; margin: 0;">🧪</p>
                    <p style="font-size: 18px; margin: 10px 0 0 0;">Bir test türü seçin ve analizi çalıştırın</p>
                </div>
                
            <?php endif; ?>
            
        </div>
        
    </div>
</div>

<style>
.dodo-brain-test-container {
    max-width: 100%;
}

@media (max-width: 1200px) {
    .dodo-brain-test-container {
        flex-direction: column;
    }
    
    .dodo-brain-test-controls {
        flex: 1 !important;
    }
}
</style>
