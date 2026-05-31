<?php
/**
 * Onboarding Wizard View
 * 
 * @package DODO_AI_SEO
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$current_step = isset($_GET['step']) ? intval($_GET['step']) : 1;
$settings = get_option('dodo_ai_seo_settings', array());
$onboarding_completed = get_option('dodo_onboarding_completed', false);
?>

<div class="dodo-onboarding-wrapper">
    <!-- Progress Bar -->
    <div class="dodo-onboarding-progress">
        <div class="dodo-progress-bar">
            <div class="dodo-progress-fill" style="width: <?php echo ($current_step / 7) * 100; ?>%"></div>
        </div>
        <div class="dodo-progress-steps">
            <?php for ($i = 1; $i <= 7; $i++): ?>
                <div class="dodo-progress-step <?php echo $i <= $current_step ? 'active' : ''; ?> <?php echo $i < $current_step ? 'completed' : ''; ?>">
                    <span class="step-number"><?php echo $i; ?></span>
                </div>
            <?php endfor; ?>
        </div>
    </div>

    <!-- Step Content -->
    <div class="dodo-onboarding-content">
        
        <?php if ($current_step === 1): ?>
            <!-- Step 1: Welcome -->
            <div class="dodo-onboarding-step" data-step="1">
                <h1><?php _e('DODO AI SEO\'ya Hoş Geldiniz', 'dodo-ai-seo'); ?></h1>
                <p class="step-description">
                    <?php _e('WordPress için AI destekli, production-ready SEO içerik platformu. Birkaç adımda sisteminizi yapılandıralım.', 'dodo-ai-seo'); ?>
                </p>
                
                <div class="feature-grid">
                    <div class="feature-card">
                        <h3><?php _e('AI İçerik Üretimi', 'dodo-ai-seo'); ?></h3>
                        <p><?php _e('GPT-4 ile SEO uyumlu içerik', 'dodo-ai-seo'); ?></p>
                    </div>
                    <div class="feature-card">
                        <h3><?php _e('İçerik Analizi', 'dodo-ai-seo'); ?></h3>
                        <p><?php _e('5 boyutlu sağlık skoru', 'dodo-ai-seo'); ?></p>
                    </div>
                    <div class="feature-card">
                        <h3><?php _e('Semantik Linkler', 'dodo-ai-seo'); ?></h3>
                        <p><?php _e('Akıllı iç link önerileri', 'dodo-ai-seo'); ?></p>
                    </div>
                    <div class="feature-card">
                        <h3><?php _e('Analytics', 'dodo-ai-seo'); ?></h3>
                        <p><?php _e('İçerik sağlığı takibi', 'dodo-ai-seo'); ?></p>
                    </div>
                </div>

                <div class="step-actions">
                    <a href="<?php echo admin_url('admin.php?page=dodo-ai-seo-onboarding&step=2'); ?>" class="dodo-btn dodo-btn-primary">
                        <?php _e('Başlayalım', 'dodo-ai-seo'); ?> →
                    </a>
                </div>
            </div>

        <?php elseif ($current_step === 2): ?>
            <!-- Step 2: API Key Setup -->
            <div class="dodo-onboarding-step" data-step="2">
                <h1><?php _e('OpenAI API Anahtarı', 'dodo-ai-seo'); ?></h1>
                <p class="step-description">
                    <?php _e('DODO AI SEO, OpenAI GPT-4 kullanır. API anahtarınızı girin.', 'dodo-ai-seo'); ?>
                </p>

                <form id="dodo-api-setup-form" class="onboarding-form">
                    <div class="form-group">
                        <label for="openai_api_key"><?php _e('OpenAI API Key', 'dodo-ai-seo'); ?></label>
                        <input 
                            type="password" 
                            id="openai_api_key" 
                            name="openai_api_key" 
                            value="<?php echo esc_attr($settings['openai_api_key'] ?? ''); ?>"
                            placeholder="sk-..."
                            class="dodo-input"
                        >
                        <p class="form-help">
                            <?php _e('API anahtarınızı', 'dodo-ai-seo'); ?> 
                            <a href="https://platform.openai.com/api-keys" target="_blank"><?php _e('OpenAI Dashboard', 'dodo-ai-seo'); ?></a>
                            <?php _e('üzerinden alabilirsiniz.', 'dodo-ai-seo'); ?>
                        </p>
                    </div>

                    <div class="api-test-result" id="api-test-result" style="display: none;"></div>

                    <div class="step-actions">
                        <a href="<?php echo admin_url('admin.php?page=dodo-ai-seo-onboarding&step=1'); ?>" class="dodo-btn dodo-btn-secondary">
                            ← <?php _e('Geri', 'dodo-ai-seo'); ?>
                        </a>
                        <button type="button" id="test-api-key" class="dodo-btn dodo-btn-outline">
                            <?php _e('API Anahtarını Test Et', 'dodo-ai-seo'); ?>
                        </button>
                        <button type="submit" class="dodo-btn dodo-btn-primary">
                            <?php _e('Kaydet ve Devam Et', 'dodo-ai-seo'); ?> →
                        </button>
                    </div>
                </form>
            </div>

        <?php elseif ($current_step === 3): ?>
            <!-- Step 3: System Test -->
            <div class="dodo-onboarding-step" data-step="3">
                <h1><?php _e('Sistem Kontrolü', 'dodo-ai-seo'); ?></h1>
                <p class="step-description">
                    <?php _e('Sisteminizin DODO AI SEO için hazır olup olmadığını kontrol ediyoruz.', 'dodo-ai-seo'); ?>
                </p>

                <div id="system-health-results" class="system-health-results">
                    <div class="loading-state">
                        <div class="spinner"></div>
                        <p><?php _e('Sistem kontrol ediliyor...', 'dodo-ai-seo'); ?></p>
                    </div>
                </div>

                <div class="step-actions">
                    <a href="<?php echo admin_url('admin.php?page=dodo-ai-seo-onboarding&step=2'); ?>" class="dodo-btn dodo-btn-secondary">
                        ← <?php _e('Geri', 'dodo-ai-seo'); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=dodo-ai-seo-onboarding&step=4'); ?>" class="dodo-btn dodo-btn-primary" id="continue-after-health">
                        <?php _e('Devam Et', 'dodo-ai-seo'); ?> →
                    </a>
                </div>
            </div>

        <?php elseif ($current_step === 4): ?>
            <!-- Step 4: Brand Settings -->
            <div class="dodo-onboarding-step" data-step="4">
                <h1><?php _e('Marka Ayarları', 'dodo-ai-seo'); ?></h1>
                <p class="step-description">
                    <?php _e('Markanızın tonunu ve içerik stratejisini belirleyin.', 'dodo-ai-seo'); ?>
                </p>

                <form id="dodo-brand-setup-form" class="onboarding-form">
                    <div class="form-group">
                        <label for="default_tone"><?php _e('Varsayılan Ton', 'dodo-ai-seo'); ?></label>
                        <select id="default_tone" name="default_tone" class="dodo-select">
                            <option value="technical" <?php selected($settings['default_tone'] ?? '', 'technical'); ?>><?php _e('Teknik / Profesyonel', 'dodo-ai-seo'); ?></option>
                            <option value="informative" <?php selected($settings['default_tone'] ?? '', 'informative'); ?>><?php _e('Öğretici / Bilgilendirici', 'dodo-ai-seo'); ?></option>
                            <option value="commercial" <?php selected($settings['default_tone'] ?? '', 'commercial'); ?>><?php _e('Ticari / Dönüşüm Odaklı', 'dodo-ai-seo'); ?></option>
                            <option value="friendly" <?php selected($settings['default_tone'] ?? '', 'friendly'); ?>><?php _e('Samimi / İnsan Odaklı', 'dodo-ai-seo'); ?></option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="default_length"><?php _e('Varsayılan İçerik Uzunluğu', 'dodo-ai-seo'); ?></label>
                        <select id="default_length" name="default_length" class="dodo-select">
                            <option value="short" <?php selected($settings['default_length'] ?? '', 'short'); ?>><?php _e('Hızlı İçerik (1200-1800 kelime)', 'dodo-ai-seo'); ?></option>
                            <option value="medium" <?php selected($settings['default_length'] ?? '', 'medium'); ?>><?php _e('SEO Blog (2500-3500 kelime)', 'dodo-ai-seo'); ?></option>
                            <option value="long" <?php selected($settings['default_length'] ?? '', 'long'); ?>><?php _e('Otorite İçerik (4000-6000 kelime)', 'dodo-ai-seo'); ?></option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="default_status"><?php _e('Varsayılan Yayın Durumu', 'dodo-ai-seo'); ?></label>
                        <select id="default_status" name="default_status" class="dodo-select">
                            <option value="draft" <?php selected($settings['default_status'] ?? '', 'draft'); ?>><?php _e('Taslak', 'dodo-ai-seo'); ?></option>
                            <option value="publish" <?php selected($settings['default_status'] ?? '', 'publish'); ?>><?php _e('Yayınla', 'dodo-ai-seo'); ?></option>
                        </select>
                    </div>

                    <div class="step-actions">
                        <a href="<?php echo admin_url('admin.php?page=dodo-ai-seo-onboarding&step=3'); ?>" class="dodo-btn dodo-btn-secondary">
                            ← <?php _e('Geri', 'dodo-ai-seo'); ?>
                        </a>
                        <button type="submit" class="dodo-btn dodo-btn-primary">
                            <?php _e('Kaydet ve Devam Et', 'dodo-ai-seo'); ?> →
                        </button>
                    </div>
                </form>
            </div>

        <?php elseif ($current_step === 5): ?>
            <!-- Step 5: GEO Settings -->
            <div class="dodo-onboarding-step" data-step="5">
                <h1><?php _e('GEO Ayarları', 'dodo-ai-seo'); ?></h1>
                <p class="step-description">
                    <?php _e('Hedef coğrafyanızı ve dil tercihlerinizi belirleyin.', 'dodo-ai-seo'); ?>
                </p>

                <form id="dodo-geo-setup-form" class="onboarding-form">
                    <div class="form-group">
                        <label for="target_country"><?php _e('Hedef Ülke', 'dodo-ai-seo'); ?></label>
                        <select id="target_country" name="target_country" class="dodo-select">
                            <option value="TR" <?php selected($settings['target_country'] ?? '', 'TR'); ?>><?php _e('Türkiye', 'dodo-ai-seo'); ?></option>
                            <option value="US" <?php selected($settings['target_country'] ?? '', 'US'); ?>><?php _e('Amerika Birleşik Devletleri', 'dodo-ai-seo'); ?></option>
                            <option value="GB" <?php selected($settings['target_country'] ?? '', 'GB'); ?>><?php _e('Birleşik Krallık', 'dodo-ai-seo'); ?></option>
                            <option value="DE" <?php selected($settings['target_country'] ?? '', 'DE'); ?>><?php _e('Almanya', 'dodo-ai-seo'); ?></option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="target_language"><?php _e('Hedef Dil', 'dodo-ai-seo'); ?></label>
                        <select id="target_language" name="target_language" class="dodo-select">
                            <option value="tr" <?php selected($settings['target_language'] ?? '', 'tr'); ?>><?php _e('Türkçe', 'dodo-ai-seo'); ?></option>
                            <option value="en" <?php selected($settings['target_language'] ?? '', 'en'); ?>><?php _e('İngilizce', 'dodo-ai-seo'); ?></option>
                            <option value="de" <?php selected($settings['target_language'] ?? '', 'de'); ?>><?php _e('Almanca', 'dodo-ai-seo'); ?></option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="enable_geo_scoring" value="1" <?php checked($settings['enable_geo_scoring'] ?? false, 1); ?>>
                            <?php _e('GEO Scoring\'i Etkinleştir', 'dodo-ai-seo'); ?>
                        </label>
                        <p class="form-help"><?php _e('İçerikleriniz hedef coğrafyaya göre optimize edilir.', 'dodo-ai-seo'); ?></p>
                    </div>

                    <div class="step-actions">
                        <a href="<?php echo admin_url('admin.php?page=dodo-ai-seo-onboarding&step=4'); ?>" class="dodo-btn dodo-btn-secondary">
                            ← <?php _e('Geri', 'dodo-ai-seo'); ?>
                        </a>
                        <button type="submit" class="dodo-btn dodo-btn-primary">
                            <?php _e('Kaydet ve Devam Et', 'dodo-ai-seo'); ?> →
                        </button>
                    </div>
                </form>
            </div>

        <?php elseif ($current_step === 6): ?>
            <!-- Step 6: Content Strategy -->
            <div class="dodo-onboarding-step" data-step="6">
                <h1><?php _e('İçerik Stratejisi', 'dodo-ai-seo'); ?></h1>
                <p class="step-description">
                    <?php _e('İçerik üretim ve optimizasyon tercihlerinizi belirleyin.', 'dodo-ai-seo'); ?>
                </p>

                <form id="dodo-strategy-setup-form" class="onboarding-form">
                    <div class="form-group">
                        <label for="min_internal_links"><?php _e('Minimum İç Link Sayısı', 'dodo-ai-seo'); ?></label>
                        <input type="number" id="min_internal_links" name="min_internal_links" value="<?php echo esc_attr($settings['min_internal_links'] ?? 5); ?>" min="0" max="20" class="dodo-input">
                    </div>

                    <div class="form-group">
                        <label for="max_internal_links"><?php _e('Maximum İç Link Sayısı', 'dodo-ai-seo'); ?></label>
                        <input type="number" id="max_internal_links" name="max_internal_links" value="<?php echo esc_attr($settings['max_internal_links'] ?? 10); ?>" min="0" max="50" class="dodo-input">
                    </div>

                    <div class="form-group">
                        <label for="ai_model_preference"><?php _e('AI Model Tercihi', 'dodo-ai-seo'); ?></label>
                        <select id="ai_model_preference" name="ai_model_preference" class="dodo-select">
                            <option value="auto" <?php selected($settings['ai_model_preference'] ?? '', 'auto'); ?>><?php _e('Otomatik (Maliyet Optimizasyonu)', 'dodo-ai-seo'); ?></option>
                            <option value="gpt-4" <?php selected($settings['ai_model_preference'] ?? '', 'gpt-4'); ?>><?php _e('GPT-4 (Yüksek Kalite)', 'dodo-ai-seo'); ?></option>
                            <option value="gpt-3.5" <?php selected($settings['ai_model_preference'] ?? '', 'gpt-3.5'); ?>><?php _e('GPT-3.5 (Hızlı & Ekonomik)', 'dodo-ai-seo'); ?></option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="daily_token_budget"><?php _e('Günlük Token Bütçesi', 'dodo-ai-seo'); ?></label>
                        <input type="number" id="daily_token_budget" name="daily_token_budget" value="<?php echo esc_attr($settings['daily_token_budget'] ?? 100000); ?>" min="10000" step="10000" class="dodo-input">
                        <p class="form-help"><?php _e('Günlük maksimum token kullanımı (0 = sınırsız)', 'dodo-ai-seo'); ?></p>
                    </div>

                    <div class="step-actions">
                        <a href="<?php echo admin_url('admin.php?page=dodo-ai-seo-onboarding&step=5'); ?>" class="dodo-btn dodo-btn-secondary">
                            ← <?php _e('Geri', 'dodo-ai-seo'); ?>
                        </a>
                        <button type="submit" class="dodo-btn dodo-btn-primary">
                            <?php _e('Kaydet ve Devam Et', 'dodo-ai-seo'); ?> →
                        </button>
                    </div>
                </form>
            </div>

        <?php elseif ($current_step === 7): ?>
            <!-- Step 7: Completion -->
            <div class="dodo-onboarding-step" data-step="7">
                <h1><?php _e('Kurulum Tamamlandı!', 'dodo-ai-seo'); ?></h1>
                <p class="step-description">
                    <?php _e('DODO AI SEO kullanıma hazır. Şimdi ilk içeriğinizi oluşturabilirsiniz.', 'dodo-ai-seo'); ?>
                </p>

                <div class="completion-checklist">
                    <div class="checklist-item completed">
                        <span class="check-icon">✓</span>
                        <span><?php _e('API anahtarı yapılandırıldı', 'dodo-ai-seo'); ?></span>
                    </div>
                    <div class="checklist-item completed">
                        <span class="check-icon">✓</span>
                        <span><?php _e('Sistem kontrolü tamamlandı', 'dodo-ai-seo'); ?></span>
                    </div>
                    <div class="checklist-item completed">
                        <span class="check-icon">✓</span>
                        <span><?php _e('Marka ayarları belirlendi', 'dodo-ai-seo'); ?></span>
                    </div>
                    <div class="checklist-item completed">
                        <span class="check-icon">✓</span>
                        <span><?php _e('GEO ayarları yapılandırıldı', 'dodo-ai-seo'); ?></span>
                    </div>
                    <div class="checklist-item completed">
                        <span class="check-icon">✓</span>
                        <span><?php _e('İçerik stratejisi belirlendi', 'dodo-ai-seo'); ?></span>
                    </div>
                </div>

                <div class="next-steps">
                    <h3><?php _e('Sonraki Adımlar', 'dodo-ai-seo'); ?></h3>
                    <div class="next-step-cards">
                        <a href="<?php echo admin_url('admin.php?page=dodo-ai-seo-new-blog'); ?>" class="next-step-card">
                            <h4><?php _e('İlk Blog Yazınızı Oluşturun', 'dodo-ai-seo'); ?></h4>
                            <p><?php _e('AI ile SEO uyumlu içerik üretin', 'dodo-ai-seo'); ?></p>
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=dodo-ai-seo-content-improver'); ?>" class="next-step-card">
                            <h4><?php _e('Mevcut İçeriği Analiz Edin', 'dodo-ai-seo'); ?></h4>
                            <p><?php _e('İçerik sağlığını ölçün ve iyileştirin', 'dodo-ai-seo'); ?></p>
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=dodo-ai-seo-keyword-opportunities'); ?>" class="next-step-card">
                            <h4><?php _e('Keyword Fırsatları Keşfedin', 'dodo-ai-seo'); ?></h4>
                            <p><?php _e('AI ile keyword analizi yapın', 'dodo-ai-seo'); ?></p>
                        </a>
                    </div>
                </div>

                <div class="step-actions">
                    <button type="button" id="complete-onboarding" class="dodo-btn dodo-btn-primary dodo-btn-large">
                        <?php _e('Dashboard\'a Git', 'dodo-ai-seo'); ?> →
                    </button>
                </div>
            </div>

        <?php endif; ?>

    </div>
</div>
