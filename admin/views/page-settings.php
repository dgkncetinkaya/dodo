<?php
/**
 * Ayarlar Sayfası
 *
 * @package DODO_AI_SEO
 * @since 1.0.0
 */

// Doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap dodo-admin-wrap">
    <!-- Premium Page Header -->
    <div class="dodo-page-header">
        <div class="dodo-page-header-content">
            <h1 class="dodo-page-title">
                <span class="dashicons dashicons-admin-settings"></span>
                <?php echo esc_html__('Ayarlar', 'dodo-ai-seo'); ?>
            </h1>
            <p class="dodo-page-description">
                <?php echo esc_html__('DODO AI SEO platformunun tüm ayarlarını buradan yönetin.', 'dodo-ai-seo'); ?>
            </p>
        </div>
    </div>
    
    <?php settings_errors('dodo_messages'); ?>
    
    <div class="dodo-admin-container">
        <div class="dodo-main-content">
            <form method="post" action="">
                <?php wp_nonce_field('dodo_settings_nonce_action', 'dodo_settings_nonce'); ?>
                
                <!-- OpenAI API Ayarları -->
                <div class="dodo-card">
                    <h2 class="dodo-card-title">
                        <?php echo esc_html__('OpenAI API Ayarları', 'dodo-ai-seo'); ?>
                    </h2>
                    
                    <div class="dodo-form-group">
                        <label for="openai_api_key" class="dodo-label required">
                            <?php echo esc_html__('OpenAI API Anahtarı', 'dodo-ai-seo'); ?>
                        </label>
                        <?php
                        $api_key = isset($current_settings['openai_api_key']) ? $current_settings['openai_api_key'] : '';
                        $masked_key = '';
                        
                        // API key varsa maskele
                        if (!empty($api_key)) {
                            $api_key = sanitize_text_field($api_key);
                            $key_length = strlen($api_key);
                            if ($key_length > 11) {
                                // İlk 7 ve son 4 karakteri göster, ortasını maskele
                                $masked_key = substr($api_key, 0, 7) . str_repeat('*', $key_length - 11) . substr($api_key, -4);
                            } else {
                                $masked_key = str_repeat('*', $key_length);
                            }
                        }
                        ?>
                        <input 
                            type="text" 
                            id="openai_api_key" 
                            name="openai_api_key" 
                            class="dodo-input" 
                            value="<?php echo esc_attr($masked_key); ?>"
                            placeholder="sk-..."
                        >
                        <p class="dodo-help-text">
                            <?php 
                            if (!empty($api_key)) {
                                echo '✅ ' . esc_html__('API anahtarı kayıtlı. Değiştirmek için yeni anahtarı girin.', 'dodo-ai-seo') . '<br>';
                            }
                            echo sprintf(
                                esc_html__('OpenAI API anahtarınızı %s adresinden alabilirsiniz.', 'dodo-ai-seo'),
                                '<a href="https://platform.openai.com/api-keys" target="_blank">platform.openai.com</a>'
                            );
                            ?>
                        </p>
                    </div>
                    
                    <div class="dodo-form-row">
                        <div class="dodo-form-group dodo-col-half">
                            <label for="openai_model" class="dodo-label">
                                <?php echo esc_html__('OpenAI Model', 'dodo-ai-seo'); ?>
                            </label>
                            <select id="openai_model" name="openai_model" class="dodo-select">
                                <option value="gpt-4o" <?php selected($current_settings['openai_model'], 'gpt-4o'); ?>>
                                    GPT-4o (Önerilen)
                                </option>
                                <option value="gpt-4o-mini" <?php selected($current_settings['openai_model'], 'gpt-4o-mini'); ?>>
                                    GPT-4o Mini (Ekonomik)
                                </option>
                                <option value="gpt-4-turbo" <?php selected($current_settings['openai_model'], 'gpt-4-turbo'); ?>>
                                    GPT-4 Turbo
                                </option>
                            </select>
                        </div>
                        
                        <div class="dodo-form-group dodo-col-half">
                            <label for="openai_temperature" class="dodo-label">
                                <?php echo esc_html__('Temperature (Yaratıcılık)', 'dodo-ai-seo'); ?>
                            </label>
                            <input 
                                type="number" 
                                id="openai_temperature" 
                                name="openai_temperature" 
                                class="dodo-input" 
                                value="<?php echo esc_attr($current_settings['openai_temperature']); ?>"
                                min="0"
                                max="2"
                                step="0.1"
                            >
                            <p class="dodo-help-text">
                                <?php echo esc_html__('0.7 önerilir (0-2 arası)', 'dodo-ai-seo'); ?>
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Varsayılan Ayarlar -->
                <div class="dodo-card">
                    <h2 class="dodo-card-title">
                        <?php echo esc_html__('Varsayılan Ayarlar', 'dodo-ai-seo'); ?>
                    </h2>
                    
                    <div class="dodo-form-row">
                        <div class="dodo-form-group dodo-col-half">
                            <label for="default_tone" class="dodo-label">
                                <?php echo esc_html__('Varsayılan Ton', 'dodo-ai-seo'); ?>
                            </label>
                            <select id="default_tone" name="default_tone" class="dodo-select">
                                <?php foreach ($settings->get_tone_options() as $value => $label) : ?>
                                    <option value="<?php echo esc_attr($value); ?>" 
                                        <?php selected($current_settings['default_tone'], $value); ?>>
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="dodo-form-group dodo-col-half">
                            <label for="default_length" class="dodo-label">
                                <?php echo esc_html__('Varsayılan Uzunluk', 'dodo-ai-seo'); ?>
                            </label>
                            <select id="default_length" name="default_length" class="dodo-select">
                                <?php foreach ($settings->get_length_options() as $value => $label) : ?>
                                    <option value="<?php echo esc_attr($value); ?>" 
                                        <?php selected($current_settings['default_length'], $value); ?>>
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="dodo-form-group">
                        <label for="default_status" class="dodo-label">
                            <?php echo esc_html__('Varsayılan Yayın Durumu', 'dodo-ai-seo'); ?>
                        </label>
                        <select id="default_status" name="default_status" class="dodo-select">
                            <?php foreach ($settings->get_status_options() as $value => $label) : ?>
                                <option value="<?php echo esc_attr($value); ?>" 
                                    <?php selected($current_settings['default_status'], $value); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="dodo-help-text">
                            <?php echo esc_html__('Güvenlik için "Taslak" önerilir', 'dodo-ai-seo'); ?>
                        </p>
                    </div>
                </div>
                
                <!-- İç Link Ayarları -->
                <div class="dodo-card">
                    <h2 class="dodo-card-title">
                        <?php echo esc_html__('İç Link Ayarları', 'dodo-ai-seo'); ?>
                    </h2>
                    
                    <div class="dodo-form-row">
                        <div class="dodo-form-group dodo-col-half">
                            <label for="min_internal_links" class="dodo-label">
                                <?php echo esc_html__('Minimum İç Link Sayısı', 'dodo-ai-seo'); ?>
                            </label>
                            <input 
                                type="number" 
                                id="min_internal_links" 
                                name="min_internal_links" 
                                class="dodo-input" 
                                value="<?php echo esc_attr($current_settings['min_internal_links']); ?>"
                                min="0"
                                max="20"
                            >
                        </div>
                        
                        <div class="dodo-form-group dodo-col-half">
                            <label for="max_internal_links" class="dodo-label">
                                <?php echo esc_html__('Maximum İç Link Sayısı', 'dodo-ai-seo'); ?>
                            </label>
                            <input 
                                type="number" 
                                id="max_internal_links" 
                                name="max_internal_links" 
                                class="dodo-input" 
                                value="<?php echo esc_attr($current_settings['max_internal_links']); ?>"
                                min="0"
                                max="20"
                            >
                        </div>
                    </div>
                    
                    <p class="dodo-help-text">
                        <?php echo esc_html__('AI, alakalı ürün ve blog yazılarına otomatik iç link ekleyecek', 'dodo-ai-seo'); ?>
                    </p>
                </div>
                
                <!-- Sprint 5: Answer Blocks Settings -->
                <div class="dodo-card">
                    <h2 class="dodo-card-title">
                        🎯 <?php echo esc_html__('Answer Blocks (Sprint 5)', 'dodo-ai-seo'); ?>
                    </h2>
                    
                    <div class="dodo-form-group">
                        <label class="dodo-checkbox-label">
                            <input 
                                type="checkbox" 
                                name="answer_blocks_enabled" 
                                value="1"
                                <?php checked(isset($current_settings['answer_blocks_enabled']) ? $current_settings['answer_blocks_enabled'] : false); ?>
                            >
                            <?php echo esc_html__('Answer Blocks Aktif', 'dodo-ai-seo'); ?>
                        </label>
                        <p class="dodo-help-text">
                            <?php echo esc_html__('LLM-friendly content blocks (short answer, FAQ, etc.)', 'dodo-ai-seo'); ?>
                        </p>
                    </div>
                    
                    <div class="dodo-form-group">
                        <label class="dodo-label"><?php echo esc_html__('Aktif Block Tipleri', 'dodo-ai-seo'); ?></label>
                        
                        <label class="dodo-checkbox-label">
                            <input type="checkbox" name="block_short_answer" value="1" 
                                <?php checked(isset($current_settings['block_short_answer']) ? $current_settings['block_short_answer'] : true); ?>>
                            <?php echo esc_html__('Short Answer (2-3 cümle)', 'dodo-ai-seo'); ?>
                        </label>
                        
                        <label class="dodo-checkbox-label">
                            <input type="checkbox" name="block_featured_snippet" value="1" 
                                <?php checked(isset($current_settings['block_featured_snippet']) ? $current_settings['block_featured_snippet'] : false); ?>>
                            <?php echo esc_html__('Featured Snippet', 'dodo-ai-seo'); ?>
                        </label>
                        
                        <label class="dodo-checkbox-label">
                            <input type="checkbox" name="block_direct_answer" value="1" 
                                <?php checked(isset($current_settings['block_direct_answer']) ? $current_settings['block_direct_answer'] : false); ?>>
                            <?php echo esc_html__('Direct Answer (Q&A format)', 'dodo-ai-seo'); ?>
                        </label>
                        
                        <label class="dodo-checkbox-label">
                            <input type="checkbox" name="block_comparison" value="1" 
                                <?php checked(isset($current_settings['block_comparison']) ? $current_settings['block_comparison'] : false); ?>>
                            <?php echo esc_html__('Comparison', 'dodo-ai-seo'); ?>
                        </label>
                        
                        <label class="dodo-checkbox-label">
                            <input type="checkbox" name="block_ai_summary" value="1" 
                                <?php checked(isset($current_settings['block_ai_summary']) ? $current_settings['block_ai_summary'] : false); ?>>
                            <?php echo esc_html__('AI Summary (key points)', 'dodo-ai-seo'); ?>
                        </label>
                        
                        <label class="dodo-checkbox-label">
                            <input type="checkbox" name="block_faq" value="1" 
                                <?php checked(isset($current_settings['block_faq']) ? $current_settings['block_faq'] : false); ?>>
                            <?php echo esc_html__('FAQ Section', 'dodo-ai-seo'); ?>
                        </label>
                    </div>
                    
                    <div class="dodo-form-group">
                        <label for="answer_blocks_max_calls" class="dodo-label">
                            <?php echo esc_html__('Max API Call Limit', 'dodo-ai-seo'); ?>
                        </label>
                        <input 
                            type="number" 
                            id="answer_blocks_max_calls" 
                            name="answer_blocks_max_calls" 
                            class="dodo-input" 
                            value="<?php echo esc_attr(isset($current_settings['answer_blocks_max_calls']) ? $current_settings['answer_blocks_max_calls'] : 3); ?>"
                            min="1"
                            max="6"
                        >
                        <p class="dodo-help-text">
                            ⚠️ <?php echo esc_html__('Her block 1 API call yapar. Maliyet kontrolü için limit belirleyin.', 'dodo-ai-seo'); ?>
                        </p>
                    </div>
                </div>
                
                <!-- Sprint 5: GEO Settings -->
                <div class="dodo-card">
                    <h2 class="dodo-card-title">
                        🌍 <?php echo esc_html__('GEO Optimization (Sprint 5)', 'dodo-ai-seo'); ?>
                    </h2>
                    
                    <div class="dodo-form-group">
                        <label for="geo_analysis_mode" class="dodo-label">
                            <?php echo esc_html__('Default Analysis Mode', 'dodo-ai-seo'); ?>
                        </label>
                        <select id="geo_analysis_mode" name="geo_analysis_mode" class="dodo-select">
                            <option value="basic" <?php selected(isset($current_settings['geo_analysis_mode']) ? $current_settings['geo_analysis_mode'] : 'basic', 'basic'); ?>>
                                <?php echo esc_html__('Basic (hızlı)', 'dodo-ai-seo'); ?>
                            </option>
                            <option value="comprehensive" <?php selected(isset($current_settings['geo_analysis_mode']) ? $current_settings['geo_analysis_mode'] : 'basic', 'comprehensive'); ?>>
                                <?php echo esc_html__('Comprehensive (detaylı)', 'dodo-ai-seo'); ?>
                            </option>
                        </select>
                    </div>
                    
                    <div class="dodo-form-group">
                        <label for="geo_ai_visibility_threshold" class="dodo-label">
                            <?php echo esc_html__('AI Visibility Threshold', 'dodo-ai-seo'); ?>
                        </label>
                        <input 
                            type="number" 
                            id="geo_ai_visibility_threshold" 
                            name="geo_ai_visibility_threshold" 
                            class="dodo-input" 
                            value="<?php echo esc_attr(isset($current_settings['geo_ai_visibility_threshold']) ? $current_settings['geo_ai_visibility_threshold'] : 70); ?>"
                            min="0"
                            max="100"
                        >
                        <p class="dodo-help-text">
                            <?php echo esc_html__('Bu skorun altındaki içerikler için uyarı göster', 'dodo-ai-seo'); ?>
                        </p>
                    </div>
                </div>
                
                <!-- Sprint 5: Humanization Settings -->
                <div class="dodo-card">
                    <h2 class="dodo-card-title">
                        👤 <?php echo esc_html__('Humanization (Sprint 5)', 'dodo-ai-seo'); ?>
                    </h2>
                    
                    <div class="dodo-form-group">
                        <label for="humanization_preset" class="dodo-label">
                            <?php echo esc_html__('Default Preset', 'dodo-ai-seo'); ?>
                        </label>
                        <select id="humanization_preset" name="humanization_preset" class="dodo-select">
                            <option value="subtle" <?php selected(isset($current_settings['humanization_preset']) ? $current_settings['humanization_preset'] : 'subtle', 'subtle'); ?>>
                                <?php echo esc_html__('Subtle (hafif)', 'dodo-ai-seo'); ?>
                            </option>
                            <option value="moderate" <?php selected(isset($current_settings['humanization_preset']) ? $current_settings['humanization_preset'] : 'subtle', 'moderate'); ?>>
                                <?php echo esc_html__('Moderate (orta)', 'dodo-ai-seo'); ?>
                            </option>
                            <option value="strong" <?php selected(isset($current_settings['humanization_preset']) ? $current_settings['humanization_preset'] : 'subtle', 'strong'); ?>>
                                <?php echo esc_html__('Strong (güçlü)', 'dodo-ai-seo'); ?>
                            </option>
                            <option value="conversational" <?php selected(isset($current_settings['humanization_preset']) ? $current_settings['humanization_preset'] : 'subtle', 'conversational'); ?>>
                                <?php echo esc_html__('Conversational (sohbet)', 'dodo-ai-seo'); ?>
                            </option>
                            <option value="professional" <?php selected(isset($current_settings['humanization_preset']) ? $current_settings['humanization_preset'] : 'subtle', 'professional'); ?>>
                                <?php echo esc_html__('Professional (profesyonel)', 'dodo-ai-seo'); ?>
                            </option>
                        </select>
                    </div>
                    
                    <div class="dodo-form-group">
                        <label for="humanization_aggressiveness" class="dodo-label">
                            <?php echo esc_html__('Aggressiveness Level', 'dodo-ai-seo'); ?>
                        </label>
                        <input 
                            type="range" 
                            id="humanization_aggressiveness" 
                            name="humanization_aggressiveness" 
                            min="1" 
                            max="10" 
                            value="<?php echo esc_attr(isset($current_settings['humanization_aggressiveness']) ? $current_settings['humanization_aggressiveness'] : 5); ?>"
                            oninput="this.nextElementSibling.textContent = this.value"
                        >
                        <output><?php echo isset($current_settings['humanization_aggressiveness']) ? $current_settings['humanization_aggressiveness'] : 5; ?></output>
                        <p class="dodo-help-text">
                            <?php echo esc_html__('1 = minimal değişiklik, 10 = maksimum insanileştirme', 'dodo-ai-seo'); ?>
                        </p>
                    </div>
                </div>
                
                <!-- Sprint 5: Publishing Pipeline Settings -->
                <div class="dodo-card">
                    <h2 class="dodo-card-title">
                        📅 <?php echo esc_html__('Publishing Pipeline (Sprint 5)', 'dodo-ai-seo'); ?>
                    </h2>
                    
                    <div class="dodo-form-group">
                        <label class="dodo-checkbox-label">
                            <input 
                                type="checkbox" 
                                name="publishing_pipeline_enabled" 
                                value="1"
                                <?php checked(isset($current_settings['publishing_pipeline_enabled']) ? $current_settings['publishing_pipeline_enabled'] : true); ?>
                            >
                            <?php echo esc_html__('Publishing Pipeline Aktif', 'dodo-ai-seo'); ?>
                        </label>
                    </div>
                    
                    <div class="dodo-form-group">
                        <label class="dodo-checkbox-label">
                            <input 
                                type="checkbox" 
                                name="publishing_review_required" 
                                value="1"
                                <?php checked(isset($current_settings['publishing_review_required']) ? $current_settings['publishing_review_required'] : true); ?>
                            >
                            <?php echo esc_html__('Review Required (yayından önce onay gerekli)', 'dodo-ai-seo'); ?>
                        </label>
                    </div>
                    
                    <div class="dodo-form-group">
                        <label class="dodo-checkbox-label">
                            <input 
                                type="checkbox" 
                                name="publishing_auto_publish" 
                                value="1"
                                <?php checked(isset($current_settings['publishing_auto_publish']) ? $current_settings['publishing_auto_publish'] : false); ?>
                            >
                            <?php echo esc_html__('Auto Publish (onaylanan içerikleri otomatik yayınla)', 'dodo-ai-seo'); ?>
                        </label>
                        <p class="dodo-help-text">
                            ⚠️ <?php echo esc_html__('Dikkat: Otomatik yayın riskli olabilir', 'dodo-ai-seo'); ?>
                        </p>
                    </div>
                </div>
                
                <!-- Google Search Console Integration -->
                <div class="dodo-card">
                    <h2 class="dodo-card-title">
                        <?php echo esc_html__('Google Search Console Entegrasyonu', 'dodo-ai-seo'); ?>
                    </h2>
                    
                    <?php
                    $gsc = new DODO_GSC_Connector();
                    $is_connected = $gsc->is_connected();
                    ?>
                    
                    <?php if ($is_connected): ?>
                        <div class="dodo-alert dodo-alert-success">
                            <strong>✓ <?php echo esc_html__('GSC Bağlı', 'dodo-ai-seo'); ?></strong>
                            <p><?php echo esc_html__('Google Search Console başarıyla bağlandı. Gerçek SEO verileri toplanıyor.', 'dodo-ai-seo'); ?></p>
                        </div>
                        
                        <div class="dodo-form-actions">
                            <a href="<?php echo esc_url(admin_url('admin.php?page=dodo-ai-seo-settings&gsc_disconnect=1')); ?>" 
                               class="button button-secondary"
                               onclick="return confirm('<?php echo esc_js(__('GSC bağlantısını kesmek istediğinizden emin misiniz?', 'dodo-ai-seo')); ?>')">
                                <?php echo esc_html__('Bağlantıyı Kes', 'dodo-ai-seo'); ?>
                            </a>
                        </div>
                    <?php else: ?>
                        <p><?php echo esc_html__('Google Search Console\'u bağlayarak gerçek SEO verilerini toplayabilir, içerik decay tespiti yapabilir ve keyword cannibalization analizi çalıştırabilirsiniz.', 'dodo-ai-seo'); ?></p>
                        
                        <div class="dodo-form-group">
                            <label for="gsc_client_id" class="dodo-label">
                                <?php echo esc_html__('Google OAuth Client ID', 'dodo-ai-seo'); ?>
                            </label>
                            <input 
                                type="text" 
                                id="gsc_client_id" 
                                name="gsc_client_id" 
                                class="dodo-input" 
                                value="<?php echo esc_attr(get_option('dodo_gsc_client_id', '')); ?>"
                                placeholder="xxxxx.apps.googleusercontent.com"
                            >
                        </div>
                        
                        <div class="dodo-form-group">
                            <label for="gsc_client_secret" class="dodo-label">
                                <?php echo esc_html__('Google OAuth Client Secret', 'dodo-ai-seo'); ?>
                            </label>
                            <input 
                                type="text" 
                                id="gsc_client_secret" 
                                name="gsc_client_secret" 
                                class="dodo-input" 
                                value="<?php echo esc_attr(get_option('dodo_gsc_client_secret', '')); ?>"
                                placeholder="GOCSPX-xxxxx"
                            >
                            <p class="dodo-help-text">
                                <?php echo sprintf(
                                    esc_html__('OAuth credentials almak için %s adresinden yeni proje oluşturun.', 'dodo-ai-seo'),
                                    '<a href="https://console.cloud.google.com/apis/credentials" target="_blank">Google Cloud Console</a>'
                                ); ?>
                            </p>
                        </div>
                        
                        <div class="dodo-form-actions">
                            <button type="submit" name="dodo_save_gsc_settings" class="button button-secondary">
                                <?php echo esc_html__('GSC Ayarlarını Kaydet', 'dodo-ai-seo'); ?>
                            </button>
                            
                            <?php if (!empty(get_option('dodo_gsc_client_id')) && !empty(get_option('dodo_gsc_client_secret'))): ?>
                                <a href="<?php echo esc_url($gsc->get_auth_url()); ?>" class="button button-primary">
                                    <?php echo esc_html__('Google ile Bağlan', 'dodo-ai-seo'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Submit Button -->
                <div class="dodo-form-actions">
                    <button type="submit" name="dodo_save_settings" class="button button-primary button-large">
                        <span class="dashicons dashicons-saved"></span>
                        <?php echo esc_html__('Ayarları Kaydet', 'dodo-ai-seo'); ?>
                    </button>
                </div>
            </form>
            
            <!-- Publishing Scheduler Link -->
            <div class="dodo-card" style="background: #f0f6fc; border-color: #c3e0f7; margin-top: 20px;">
                <h3 style="margin-top: 0; color: #2271b1;">
                    📅 <?php echo esc_html__('Zamanlanmış Yayınlama', 'dodo-ai-seo'); ?>
                </h3>
                <p style="margin-bottom: 15px;">
                    <?php echo esc_html__('Zamanlanmış yayınlama ayarlarını ve planlanan içerikleri yönetmek için Publishing Scheduler sayfasını kullanın.', 'dodo-ai-seo'); ?>
                </p>
                <a href="<?php echo esc_url(admin_url('admin.php?page=dodo-ai-seo-publishing-scheduler')); ?>" class="button button-primary">
                    <span class="dashicons dashicons-calendar-alt" style="margin-top: 4px;"></span>
                    <?php echo esc_html__('Publishing Scheduler\'a Git', 'dodo-ai-seo'); ?>
                </a>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="dodo-sidebar">
            <div class="dodo-card dodo-info-card">
                <h3><?php echo esc_html__('ℹ️ Bilgi', 'dodo-ai-seo'); ?></h3>
                <p><?php echo esc_html__('DODO AI SEO v' . DODO_VERSION, 'dodo-ai-seo'); ?></p>
                <hr>
                <p><strong><?php echo esc_html__('Sistem Durumu:', 'dodo-ai-seo'); ?></strong></p>
                <ul class="dodo-system-status">
                    <li>
                        <span class="dashicons dashicons-<?php echo version_compare(PHP_VERSION, '8.0', '>=') ? 'yes' : 'no'; ?>"></span>
                        PHP <?php echo PHP_VERSION; ?>
                    </li>
                    <li>
                        <span class="dashicons dashicons-<?php echo class_exists('RankMath') ? 'yes' : 'no'; ?>"></span>
                        Rank Math <?php echo class_exists('RankMath') ? '✓' : '✗'; ?>
                    </li>
                    <li>
                        <span class="dashicons dashicons-<?php echo class_exists('WooCommerce') ? 'yes' : 'no'; ?>"></span>
                        WooCommerce <?php echo class_exists('WooCommerce') ? '✓' : '✗'; ?>
                    </li>
                    <li>
                        <span class="dashicons dashicons-<?php echo $settings->is_api_key_valid() ? 'yes' : 'no'; ?>"></span>
                        OpenAI API <?php echo $settings->is_api_key_valid() ? '✓' : '✗'; ?>
                    </li>
                </ul>
            </div>
            
            <div class="dodo-card dodo-info-card">
                <h3><?php echo esc_html__('📚 Dokümantasyon', 'dodo-ai-seo'); ?></h3>
                <ul>
                    <li><a href="https://platform.openai.com/docs" target="_blank">OpenAI API Docs</a></li>
                    <li><a href="https://rankmath.com/kb/" target="_blank">Rank Math Docs</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>
