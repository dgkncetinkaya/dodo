<?php
/**
 * Yeni Blog Oluştur Sayfası
 *
 * @package DODO_AI_SEO
 * @since 1.0.0
 */

// Doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

$settings = new DODO_Settings();
$tone_options = $settings->get_tone_options();
$length_options = $settings->get_length_options();
$content_type_options = $settings->get_content_type_options();
$status_options = $settings->get_status_options();

// Kategorileri al
$categories = get_categories(array(
    'hide_empty' => false,
    'orderby' => 'name',
    'order' => 'ASC',
));

// URL parametrelerinden değerleri al (Keyword Opportunities'den geliyorsa)
$prefill_keyword = isset($_GET['keyword']) ? sanitize_text_field($_GET['keyword']) : '';
$prefill_content_type = isset($_GET['content_type']) ? sanitize_text_field($_GET['content_type']) : '';
$opportunity_id = isset($_GET['opportunity_id']) ? absint($_GET['opportunity_id']) : 0;
?>

<div class="wrap dodo-admin-wrap dodo-new-blog-page">
    <!-- Premium Page Header -->
    <div class="dodo-page-header">
        <div class="dodo-page-header-content">
            <h1 class="dodo-page-title">
                <svg class="dodo-page-title-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                <?php echo esc_html__('Yeni Blog Oluştur', 'dodo-ai-seo'); ?>
            </h1>
            <p class="dodo-page-description">
                <?php echo esc_html__('AI destekli blog yazısı oluşturma platformu. Sadece anahtar kelime girin, gerisini AI halleder.', 'dodo-ai-seo'); ?>
            </p>
        </div>
    </div>
    
    <!-- Premium 2-Column Layout -->
    <div class="dodo-admin-container">
        <div class="blog-create-layout">
            <div class="blog-form-card">
                <div class="dodo-card">
                <form id="dodo-blog-form" method="post">
                    <?php wp_nonce_field('dodo_create_blog', 'dodo_blog_nonce'); ?>
                    
                    <?php if ($opportunity_id > 0) : ?>
                        <input type="hidden" name="opportunity_id" value="<?php echo esc_attr($opportunity_id); ?>">
                        <div class="dodo-result dodo-result info" style="margin-bottom: var(--dodo-space-5);">
                            <svg class="dodo-result-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                            <div>
                                <strong><?php echo esc_html__('Anahtar Kelime Fırsatı', 'dodo-ai-seo'); ?></strong>
                                <p style="margin: var(--dodo-space-1) 0 0 0;"><?php echo esc_html__('Bu blog yazısı, AI tarafından önerilen bir keyword fırsatından oluşturuluyor.', 'dodo-ai-seo'); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Odak Anahtar Kelime -->
                    <div class="dodo-form-group">
                        <label for="focus_keyword" class="dodo-label required">
                            <?php echo esc_html__('Odak Anahtar Kelime', 'dodo-ai-seo'); ?>
                        </label>
                        <input 
                            type="text" 
                            id="focus_keyword" 
                            name="focus_keyword" 
                            class="dodo-input" 
                            placeholder="<?php echo esc_attr__('Örn: wordpress seo eklentisi', 'dodo-ai-seo'); ?>"
                            value="<?php echo esc_attr($prefill_keyword); ?>"
                            required
                        >
                        <p class="dodo-help-text">
                            <?php echo esc_html__('Blog yazısının odaklanacağı ana anahtar kelime. AI gerisini otomatik halleder.', 'dodo-ai-seo'); ?>
                        </p>
                    </div>
                    
                    <!-- Gelişmiş Ayarlar Toggle -->
                    <button type="button" class="dodo-btn-ghost" id="dodo-advanced-toggle" style="margin-top: var(--dodo-space-5); width: 100%;" onclick="
                        var section = document.getElementById('dodo-advanced-section');
                        var toggle = this;
                        if (section.style.display === 'none' || section.style.display === '') {
                            section.style.display = 'block';
                            toggle.classList.add('active');
                        } else {
                            section.style.display = 'none';
                            toggle.classList.remove('active');
                        }
                    ">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>
                        <?php echo esc_html__('Gelişmiş Ayarlar', 'dodo-ai-seo'); ?>
                    </button>
                    
                    <!-- Gelişmiş Ayarlar Bölümü (Varsayılan Kapalı) -->
                    <div class="dodo-advanced-section" id="dodo-advanced-section" style="display: none;">
                        
                        <!-- Yazı Konusu (Opsiyonel) -->
                        <div class="dodo-form-group">
                            <label for="topic" class="dodo-label">
                                <?php echo esc_html__('Yazı Konusu / Kısa Açıklama', 'dodo-ai-seo'); ?>
                                <span class="dodo-optional-badge"><?php echo esc_html__('Opsiyonel', 'dodo-ai-seo'); ?></span>
                            </label>
                            <textarea 
                                id="topic" 
                                name="topic" 
                                class="dodo-textarea" 
                                rows="3"
                                placeholder="<?php echo esc_attr__('Boş bırakırsanız AI otomatik olarak en uygun makale açısını belirleyecektir...', 'dodo-ai-seo'); ?>"
                            ></textarea>
                            <p class="dodo-help-text">
                                <?php echo esc_html__('Boş bırakın → AI otomatik yön belirler. Yazarsanız → AI öncelikli olarak sizin açıklamanızı kullanır.', 'dodo-ai-seo'); ?>
                            </p>
                        </div>
                        
                        <div class="dodo-form-row">
                            <!-- Yazı Uzunluğu -->
                            <div class="dodo-form-group dodo-col-half">
                                <label for="length" class="dodo-label">
                                    <?php echo esc_html__('Yazı Uzunluğu', 'dodo-ai-seo'); ?>
                                </label>
                                <select id="length" name="length" class="dodo-select">
                                    <?php foreach ($length_options as $value => $label) : ?>
                                        <option value="<?php echo esc_attr($value); ?>" 
                                            <?php selected($settings->get_setting('default_length'), $value); ?>>
                                            <?php echo esc_html($label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Yazı Tonu -->
                            <div class="dodo-form-group dodo-col-half">
                                <label for="tone" class="dodo-label">
                                    <?php echo esc_html__('Yazı Tonu', 'dodo-ai-seo'); ?>
                                </label>
                                <select id="tone" name="tone" class="dodo-select">
                                    <?php foreach ($tone_options as $value => $label) : ?>
                                        <option value="<?php echo esc_attr($value); ?>" 
                                            <?php selected($settings->get_setting('default_tone'), $value); ?>>
                                            <?php echo esc_html($label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <!-- İçerik Tipi -->
                        <div class="dodo-form-group">
                            <label for="content_type" class="dodo-label">
                                <?php echo esc_html__('İçerik Tipi', 'dodo-ai-seo'); ?>
                            </label>
                            <select id="content_type" name="content_type" class="dodo-select">
                                <?php foreach ($content_type_options as $value => $label) : ?>
                                    <option value="<?php echo esc_attr($value); ?>" 
                                        <?php selected(!empty($prefill_content_type) ? $prefill_content_type : 'blog', $value); ?>>
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="dodo-form-row">
                            <!-- Kategori -->
                            <div class="dodo-form-group dodo-col-half">
                                <label for="category_id" class="dodo-label">
                                    <?php echo esc_html__('Kategori', 'dodo-ai-seo'); ?>
                                </label>
                                <select id="category_id" name="category_id" class="dodo-select">
                                    <option value="0"><?php echo esc_html__('Kategori Seçin', 'dodo-ai-seo'); ?></option>
                                    <?php foreach ($categories as $category) : ?>
                                        <option value="<?php echo esc_attr($category->term_id); ?>">
                                            <?php echo esc_html($category->name); ?> (<?php echo esc_html($category->count); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Yayın Modu -->
                            <div class="dodo-form-group dodo-col-half">
                                <label for="publish_mode" class="dodo-label">
                                    <?php echo esc_html__('Yayın Modu', 'dodo-ai-seo'); ?>
                                </label>
                                <select id="publish_mode" name="publish_mode" class="dodo-select">
                                    <option value="default"><?php echo esc_html__('Varsayılan ayarı kullan', 'dodo-ai-seo'); ?></option>
                                    <option value="draft"><?php echo esc_html__('Taslak olarak oluştur', 'dodo-ai-seo'); ?></option>
                                    <option value="publish"><?php echo esc_html__('Hemen yayınla', 'dodo-ai-seo'); ?></option>
                                    <option value="scheduled"><?php echo esc_html__('Zamanlanmış yayınla', 'dodo-ai-seo'); ?></option>
                                </select>
                                <p class="dodo-help-text">
                                    <?php echo esc_html__('Varsayılan: Settings sayfasındaki ayarı kullanır', 'dodo-ai-seo'); ?>
                                </p>
                            </div>
                        </div>
                        
                        <!-- Zamanlanmış Yayın Ayarları (Conditional) -->
                        <div id="dodo-scheduled-options" style="display: none; background: var(--dodo-info-bg); padding: var(--dodo-space-4); border-radius: var(--dodo-radius-lg); border: 1px solid var(--dodo-info-border); margin-top: var(--dodo-space-4);">
                            <h4 style="margin-top: 0; color: var(--dodo-info); display: flex; align-items: center; gap: var(--dodo-space-2);">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                <?php echo esc_html__('Zamanlanmış Yayın Ayarları', 'dodo-ai-seo'); ?>
                            </h4>
                            
                            <div class="dodo-form-row">
                                <div class="dodo-form-group dodo-col-half">
                                    <label for="scheduled_date" class="dodo-label">
                                        <?php echo esc_html__('Yayın Tarihi', 'dodo-ai-seo'); ?>
                                        <span class="dodo-optional-badge"><?php echo esc_html__('Opsiyonel', 'dodo-ai-seo'); ?></span>
                                    </label>
                                    <input 
                                        type="date" 
                                        id="scheduled_date" 
                                        name="scheduled_date" 
                                        class="dodo-input"
                                        min="<?php echo date('Y-m-d'); ?>"
                                    >
                                    <p class="dodo-help-text">
                                        <?php echo esc_html__('Boş bırakırsanız otomatik hesaplanır', 'dodo-ai-seo'); ?>
                                    </p>
                                </div>
                                
                                <div class="dodo-form-group dodo-col-half">
                                    <label for="scheduled_time" class="dodo-label">
                                        <?php echo esc_html__('Yayın Saati', 'dodo-ai-seo'); ?>
                                        <span class="dodo-optional-badge"><?php echo esc_html__('Opsiyonel', 'dodo-ai-seo'); ?></span>
                                    </label>
                                    <input 
                                        type="time" 
                                        id="scheduled_time" 
                                        name="scheduled_time" 
                                        class="dodo-input"
                                        value="<?php echo sprintf('%02d:%02d', $settings->get_setting('publish_start_hour', 10), $settings->get_setting('publish_start_minute', 0)); ?>"
                                    >
                                    <p class="dodo-help-text">
                                        <?php echo esc_html__('Boş bırakırsanız varsayılan saat kullanılır', 'dodo-ai-seo'); ?>
                                    </p>
                                </div>
                            </div>
                            
                            <div style="background: var(--dodo-bg-card); padding: var(--dodo-space-3); border-radius: var(--dodo-radius-md); border-left: 3px solid var(--dodo-info);">
                                <p style="margin: 0; font-size: var(--dodo-text-sm); color: var(--dodo-text-secondary);">
                                    <strong><?php echo esc_html__('İpucu:', 'dodo-ai-seo'); ?></strong> 
                                    <?php echo esc_html__('Tarih ve saati boş bırakırsanız, Publishing Scheduler ayarlarına göre otomatik olarak bir sonraki uygun slot hesaplanır.', 'dodo-ai-seo'); ?>
                                </p>
                            </div>
                        </div>
                        
                        <!-- Advanced Generation Controls (Sprint 3 - Task 6) -->
                        <div class="dodo-generation-controls" style="margin-top: var(--dodo-space-6); padding-top: var(--dodo-space-6); border-top: 1px solid var(--dodo-border-default);">
                            <h3 style="margin-top: 0; margin-bottom: var(--dodo-space-4); font-size: var(--dodo-text-lg); font-weight: var(--dodo-font-semibold); color: var(--dodo-text-primary); display: flex; align-items: center; gap: var(--dodo-space-2);">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 4V2"/><path d="M15 16v-2"/><path d="M8 9h2"/><path d="M20 9h2"/><path d="M17.8 11.8 19 13"/><path d="M15 9h0"/><path d="M17.8 6.2 19 5"/><path d="m3 21 9-9"/><path d="M12.2 6.2 11 5"/></svg>
                                <?php echo esc_html__('AI Üretim Kontrolleri', 'dodo-ai-seo'); ?>
                            </h3>
                            
                            <div class="dodo-form-row">
                                <!-- Intent Selector -->
                                <div class="dodo-form-group dodo-col-half">
                                    <label for="content_intent" class="dodo-label">
                                        <?php echo esc_html__('İçerik Amacı', 'dodo-ai-seo'); ?>
                                    </label>
                                    <select id="content_intent" name="content_intent" class="dodo-select">
                                        <option value="informational"><?php echo esc_html__('Bilgilendirici', 'dodo-ai-seo'); ?></option>
                                        <option value="educational"><?php echo esc_html__('Eğitici', 'dodo-ai-seo'); ?></option>
                                        <option value="commercial"><?php echo esc_html__('Ticari', 'dodo-ai-seo'); ?></option>
                                        <option value="transactional"><?php echo esc_html__('Satış Odaklı', 'dodo-ai-seo'); ?></option>
                                    </select>
                                </div>
                                
                                <!-- Expertise Depth -->
                                <div class="dodo-form-group dodo-col-half">
                                    <label for="expertise_depth" class="dodo-label">
                                        <?php echo esc_html__('Uzmanlık Derinliği', 'dodo-ai-seo'); ?>
                                    </label>
                                    <select id="expertise_depth" name="expertise_depth" class="dodo-select">
                                        <option value="beginner"><?php echo esc_html__('Başlangıç', 'dodo-ai-seo'); ?></option>
                                        <option value="intermediate" selected><?php echo esc_html__('Orta', 'dodo-ai-seo'); ?></option>
                                        <option value="advanced"><?php echo esc_html__('İleri', 'dodo-ai-seo'); ?></option>
                                        <option value="expert"><?php echo esc_html__('Uzman', 'dodo-ai-seo'); ?></option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="dodo-form-row">
                                <!-- GEO Optimization Level -->
                                <div class="dodo-form-group dodo-col-half">
                                    <label for="geo_optimization" class="dodo-label">
                                        <?php echo esc_html__('GEO Optimizasyonu', 'dodo-ai-seo'); ?>
                                    </label>
                                    <select id="geo_optimization" name="geo_optimization" class="dodo-select">
                                        <option value="none"><?php echo esc_html__('Yok', 'dodo-ai-seo'); ?></option>
                                        <option value="light"><?php echo esc_html__('Hafif', 'dodo-ai-seo'); ?></option>
                                        <option value="moderate" selected><?php echo esc_html__('Orta', 'dodo-ai-seo'); ?></option>
                                        <option value="aggressive"><?php echo esc_html__('Agresif', 'dodo-ai-seo'); ?></option>
                                    </select>
                                    <p class="dodo-help-text">
                                        <?php echo esc_html__('ChatGPT/Perplexity görünürlük optimizasyonu', 'dodo-ai-seo'); ?>
                                    </p>
                                </div>
                                
                                <!-- AI Naturalness Level -->
                                <div class="dodo-form-group dodo-col-half">
                                    <label for="ai_naturalness" class="dodo-label">
                                        <?php echo esc_html__('AI Doğallığı', 'dodo-ai-seo'); ?>
                                    </label>
                                    <select id="ai_naturalness" name="ai_naturalness" class="dodo-select">
                                        <option value="very_human"><?php echo esc_html__('Çok İnsan Gibi', 'dodo-ai-seo'); ?></option>
                                        <option value="human" selected><?php echo esc_html__('İnsan Gibi', 'dodo-ai-seo'); ?></option>
                                        <option value="balanced"><?php echo esc_html__('Dengeli', 'dodo-ai-seo'); ?></option>
                                        <option value="ai_friendly"><?php echo esc_html__('AI Dostu', 'dodo-ai-seo'); ?></option>
                                    </select>
                                    <p class="dodo-help-text">
                                        <?php echo esc_html__('AI tespit riski seviyesi', 'dodo-ai-seo'); ?>
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Sprint 5: Answer Blocks & Humanization -->
                            <div class="dodo-form-row">
                                <!-- Answer Blocks -->
                                <div class="dodo-form-group dodo-col-half">
                                    <label class="dodo-checkbox-label">
                                        <input type="checkbox" id="answer_blocks_enabled" name="answer_blocks_enabled" value="1">
                                        <?php echo esc_html__('Cevap Blokları Aktif', 'dodo-ai-seo'); ?>
                                    </label>
                                    <p class="dodo-help-text">
                                        ⚠️ <?php echo esc_html__('Her blok 1 API çağrısı yapar (maliyet artar)', 'dodo-ai-seo'); ?>
                                    </p>
                                    <div id="answer-blocks-options" style="display:none; margin-top: 10px;">
                                        <label class="dodo-checkbox-label" style="font-size: 13px;">
                                            <input type="checkbox" name="block_short_answer" value="1" checked>
                                            <?php echo esc_html__('Kısa Cevap', 'dodo-ai-seo'); ?>
                                        </label>
                                        <label class="dodo-checkbox-label" style="font-size: 13px;">
                                            <input type="checkbox" name="block_faq" value="1">
                                            <?php echo esc_html__('SSS', 'dodo-ai-seo'); ?>
                                        </label>
                                    </div>
                                </div>
                                
                                <!-- Humanization -->
                                <div class="dodo-form-group dodo-col-half">
                                    <label for="humanization_preset" class="dodo-label">
                                        <?php echo esc_html__('İnsanlaştırma Ayarı', 'dodo-ai-seo'); ?>
                                    </label>
                                    <select id="humanization_preset" name="humanization_preset" class="dodo-select">
                                        <option value="none"><?php echo esc_html__('Yok', 'dodo-ai-seo'); ?></option>
                                        <option value="subtle"><?php echo esc_html__('Hafif', 'dodo-ai-seo'); ?></option>
                                        <option value="moderate" selected><?php echo esc_html__('Orta', 'dodo-ai-seo'); ?></option>
                                        <option value="strong"><?php echo esc_html__('Güçlü', 'dodo-ai-seo'); ?></option>
                                    </select>
                                    <p class="dodo-help-text">
                                        <?php echo esc_html__('İçeriği daha doğal hale getirir', 'dodo-ai-seo'); ?>
                                    </p>
                                </div>
                            </div>
                            
                            <div class="dodo-form-row">
                                <!-- Semantic Aggressiveness -->
                                <div class="dodo-form-group dodo-col-half">
                                    <label for="semantic_aggressiveness" class="dodo-label">
                                        <?php echo esc_html__('Semantik Yoğunluk', 'dodo-ai-seo'); ?>
                                    </label>
                                    <select id="semantic_aggressiveness" name="semantic_aggressiveness" class="dodo-select">
                                        <option value="conservative"><?php echo esc_html__('Muhafazakar', 'dodo-ai-seo'); ?></option>
                                        <option value="moderate" selected><?php echo esc_html__('Orta', 'dodo-ai-seo'); ?></option>
                                        <option value="aggressive"><?php echo esc_html__('Agresif', 'dodo-ai-seo'); ?></option>
                                    </select>
                                    <p class="dodo-help-text">
                                        <?php echo esc_html__('LSI anahtar kelime ve varlık kullanım yoğunluğu', 'dodo-ai-seo'); ?>
                                    </p>
                                </div>
                                
                                <!-- Readability Target -->
                                <div class="dodo-form-group dodo-col-half">
                                    <label for="readability_target" class="dodo-label">
                                        <?php echo esc_html__('Okunabilirlik Hedefi', 'dodo-ai-seo'); ?>
                                    </label>
                                    <select id="readability_target" name="readability_target" class="dodo-select">
                                        <option value="very_easy"><?php echo esc_html__('Çok Kolay', 'dodo-ai-seo'); ?></option>
                                        <option value="easy" selected><?php echo esc_html__('Kolay', 'dodo-ai-seo'); ?></option>
                                        <option value="moderate"><?php echo esc_html__('Orta', 'dodo-ai-seo'); ?></option>
                                        <option value="advanced"><?php echo esc_html__('İleri', 'dodo-ai-seo'); ?></option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Article Strategy Presets -->
                            <div class="dodo-form-group">
                                <label class="dodo-label">
                                    <?php echo esc_html__('Makale Strateji Şablonları', 'dodo-ai-seo'); ?>
                                </label>
                                <div class="dodo-preset-buttons" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: var(--dodo-space-3);">
                                    <button type="button" class="dodo-preset-btn" data-preset="seo-safe">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                        <span><?php echo esc_html__('SEO Güvenli', 'dodo-ai-seo'); ?></span>
                                    </button>
                                    <button type="button" class="dodo-preset-btn" data-preset="geo-optimized">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                                        <span><?php echo esc_html__('GEO Optimize', 'dodo-ai-seo'); ?></span>
                                    </button>
                                    <button type="button" class="dodo-preset-btn" data-preset="human-like">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                        <span><?php echo esc_html__('İnsan Gibi', 'dodo-ai-seo'); ?></span>
                                    </button>
                                    <button type="button" class="dodo-preset-btn" data-preset="authority-builder">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"/></svg>
                                        <span><?php echo esc_html__('Otorite Artırıcı', 'dodo-ai-seo'); ?></span>
                                    </button>
                                    <button type="button" class="dodo-preset-btn" data-preset="conversion-focused">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                                        <span><?php echo esc_html__('Dönüşüm Odaklı', 'dodo-ai-seo'); ?></span>
                                    </button>
                                </div>
                                <p class="dodo-help-text">
                                    <?php echo esc_html__('Hızlı şablonlar yukarıdaki ayarları otomatik doldurur', 'dodo-ai-seo'); ?>
                                </p>
                            </div>
                        </div>
                        
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="dodo-form-actions">
                        <button type="submit" class="dodo-btn-primary dodo-btn-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 4V2"/><path d="M15 16v-2"/><path d="M8 9h2"/><path d="M20 9h2"/><path d="M17.8 11.8 19 13"/><path d="M15 9h0"/><path d="M17.8 6.2 19 5"/><path d="m3 21 9-9"/><path d="M12.2 6.2 11 5"/></svg>
                            <?php echo esc_html__('Blog Yazısı Oluştur', 'dodo-ai-seo'); ?>
                        </button>
                    </div>
                    
                    <script>
                    jQuery(document).ready(function($) {
                        // Publish mode değiştiğinde scheduled options göster/gizle
                        $('#publish_mode').on('change', function() {
                            if ($(this).val() === 'scheduled') {
                                $('#dodo-scheduled-options').slideDown();
                            } else {
                                $('#dodo-scheduled-options').slideUp();
                            }
                        });
                        
                        // Answer Blocks toggle (Sprint 5)
                        $('#answer_blocks_enabled').on('change', function() {
                            if ($(this).is(':checked')) {
                                $('#answer-blocks-options').slideDown();
                            } else {
                                $('#answer-blocks-options').slideUp();
                            }
                        });
                        
                        // Preset button logic (Sprint 3 - Task 6)
                        $('.dodo-preset-btn').on('click', function() {
                            const preset = $(this).data('preset');
                            
                            // Remove active class from all
                            $('.dodo-preset-btn').removeClass('active');
                            $(this).addClass('active');
                            
                            // Apply preset values
                            const presets = {
                                'seo-safe': {
                                    content_intent: 'informational',
                                    expertise_depth: 'intermediate',
                                    geo_optimization: 'light',
                                    ai_naturalness: 'very_human',
                                    semantic_aggressiveness: 'conservative',
                                    readability_target: 'easy'
                                },
                                'geo-optimized': {
                                    content_intent: 'informational',
                                    expertise_depth: 'intermediate',
                                    geo_optimization: 'aggressive',
                                    ai_naturalness: 'ai_friendly',
                                    semantic_aggressiveness: 'aggressive',
                                    readability_target: 'easy'
                                },
                                'human-like': {
                                    content_intent: 'educational',
                                    expertise_depth: 'beginner',
                                    geo_optimization: 'none',
                                    ai_naturalness: 'very_human',
                                    semantic_aggressiveness: 'conservative',
                                    readability_target: 'very_easy'
                                },
                                'authority-builder': {
                                    content_intent: 'educational',
                                    expertise_depth: 'expert',
                                    geo_optimization: 'moderate',
                                    ai_naturalness: 'balanced',
                                    semantic_aggressiveness: 'aggressive',
                                    readability_target: 'advanced'
                                },
                                'conversion-focused': {
                                    content_intent: 'transactional',
                                    expertise_depth: 'intermediate',
                                    geo_optimization: 'light',
                                    ai_naturalness: 'human',
                                    semantic_aggressiveness: 'moderate',
                                    readability_target: 'easy'
                                }
                            };
                            
                            if (presets[preset]) {
                                const values = presets[preset];
                                $('#content_intent').val(values.content_intent);
                                $('#expertise_depth').val(values.expertise_depth);
                                $('#geo_optimization').val(values.geo_optimization);
                                $('#ai_naturalness').val(values.ai_naturalness);
                                $('#semantic_aggressiveness').val(values.semantic_aggressiveness);
                                $('#readability_target').val(values.readability_target);
                            }
                        });
                    });
                    </script>
                    
                    <!-- Loading State -->
                    <div id="dodo-loading" class="dodo-loading" style="display: none;">
                        <div class="dodo-spinner"></div>
                        <p class="dodo-loading-text">
                            <?php echo esc_html__('AI içerik üretiyor (Multi-Step Generation)...', 'dodo-ai-seo'); ?>
                        </p>
                        <p class="dodo-loading-subtext">
                            <?php echo esc_html__('Bu işlem 2-4 dakika sürebilir. Lütfen sayfayı kapatmayın.', 'dodo-ai-seo'); ?>
                        </p>
                        <div class="dodo-progress-steps" style="margin-top: 15px; font-size: 12px; color: #666;">
                            <div>⏳ Konu analizi & yön belirleme...</div>
                            <div>⏳ Outline oluşturuluyor...</div>
                            <div>⏳ Giriş bölümü yazılıyor...</div>
                            <div>⏳ Ana bölümler oluşturuluyor...</div>
                            <div>⏳ FAQ hazırlanıyor...</div>
                            <div>⏳ Sonuç yazılıyor...</div>
                            <div>⏳ SEO meta verileri oluşturuluyor...</div>
                        </div>
                    </div>
                    
                    <!-- Result Message -->
                    <div id="dodo-result" class="dodo-result" style="display: none;"></div>
                </form>
            </div><!-- .dodo-card -->
        </div><!-- .blog-form-card -->
        
        <!-- Premium Sidebar -->
        <aside class="blog-info-sidebar">
            <div class="dodo-sidebar-card">
                <h3 class="dodo-sidebar-card-title"><?php echo esc_html__('Hızlı Başlangıç', 'dodo-ai-seo'); ?></h3>
                <ul class="dodo-sidebar-list">
                    <li><?php echo esc_html__('Sadece odak anahtar kelime girin', 'dodo-ai-seo'); ?></li>
                    <li><?php echo esc_html__('AI otomatik makale açısı belirler', 'dodo-ai-seo'); ?></li>
                    <li><?php echo esc_html__('Gelişmiş ayarlardan özelleştirin', 'dodo-ai-seo'); ?></li>
                    <li><?php echo esc_html__('İçerik üretimi 2-4 dakika sürer', 'dodo-ai-seo'); ?></li>
                </ul>
            </div>
            
            <div class="dodo-sidebar-card">
                <h3 class="dodo-sidebar-card-title"><?php echo esc_html__('Üretilecek İçerik', 'dodo-ai-seo'); ?></h3>
                <ul class="dodo-sidebar-list">
                    <li>SEO uyumlu başlık ve meta</li>
                    <li>H2/H3 başlık yapısı</li>
                    <li>Profil bazlı kelime hedefi</li>
                    <li>SSS bölümü</li>
                    <li>Akıllı iç linkler</li>
                    <li>Semantik SEO kelimeleri</li>
                </ul>
            </div>
            
            <?php if (class_exists('RankMath')) : ?>
            <div class="dodo-sidebar-card dodo-success-card">
                <p>
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    <?php echo esc_html__('Rank Math aktif', 'dodo-ai-seo'); ?>
                </p>
            </div>
            <?php endif; ?>
            
            <?php if (class_exists('WooCommerce')) : ?>
            <div class="dodo-sidebar-card dodo-success-card">
                <p>
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    <?php echo esc_html__('WooCommerce aktif', 'dodo-ai-seo'); ?>
                </p>
            </div>
            <?php endif; ?>
        </aside><!-- .blog-info-sidebar -->
        </div><!-- .blog-create-layout -->
    </div><!-- .dodo-admin-container -->
</div><!-- .wrap -->
