<?php
/**
 * Content Improver Admin Page
 * 
 * Standalone admin page for AI content improvement.
 * Replaces the deprecated meta box approach.
 * 
 * @package DODO_AI_SEO
 * @since 1.5.0
 */

// Doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

// Yetki kontrolü
if (!current_user_can('manage_options')) {
    wp_die(__('Bu sayfaya erişim yetkiniz yok.', 'dodo-ai-seo'));
}

// Get all posts and pages for selection
$posts_query = new WP_Query(array(
    'post_type' => array('post', 'page'),
    'post_status' => array('publish', 'draft', 'pending'),
    'posts_per_page' => -1,
    'orderby' => 'date',
    'order' => 'DESC',
));
?>

<div class="wrap dodo-admin-wrap">
    <!-- Skip to main content (accessibility) -->
    <a href="#dodo-main-content" class="dodo-skip-link">Skip to main content</a>
    
    <!-- Premium Page Header -->
    <div class="dodo-page-header">
        <div class="dodo-page-header-content">
            <h1 class="dodo-page-title">
                <svg class="dodo-page-title-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 4V2"/><path d="M15 16v-2"/><path d="M8 9h2"/><path d="M20 9h2"/><path d="M17.8 11.8 19 13"/><path d="M15 9h0"/><path d="M17.8 6.2 19 5"/><path d="m3 21 9-9"/><path d="M12.2 6.2 11 5"/></svg>
                <?php _e('İçerik Geliştirici', 'dodo-ai-seo'); ?>
            </h1>
            <p class="dodo-page-description">
                <?php _e('AI destekli içerik geliştirme platformu. Yazılarınızı analiz edin ve profesyonel iyileştirmeler yapın.', 'dodo-ai-seo'); ?>
            </p>
        </div>
    </div>
    
    <!-- Premium 2-Column Layout -->
    <div class="dodo-layout-container" id="dodo-main-content" role="main" style="display: grid; grid-template-columns: minmax(680px, 1fr) 380px; gap: 32px; max-width: 1600px; width: 100%; margin: 0 auto; align-items: start;">
        <!-- Main Content Area -->
        <div class="dodo-main-content" style="min-width: 0;">
            <!-- Post Selection Card -->
            <div class="dodo-premium-card dodo-post-selector" style="margin-bottom: 20px; padding: 32px; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
        <div class="dodo-card-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px;">
            <h2 class="dodo-card-title" style="margin: 0; font-size: 20px; font-weight: 700; color: #0f172a; letter-spacing: -0.02em;">
                <?php _e('İçerik Seçimi', 'dodo-ai-seo'); ?>
            </h2>
            <span class="dodo-step-badge" style="display: inline-flex; align-items: center; justify-content: center; height: 22px; padding: 0 10px; background: #f1f5f9; color: #475569; border-radius: 5px; font-size: 11px; font-weight: 600; letter-spacing: 0.02em;">ADIM 1</span>
        </div>
        <div class="dodo-card-body" style="padding: 0;">
        
        <div class="dodo-form-group" style="margin-bottom: 20px;">
            <label for="dodo-post-select" class="dodo-form-label" style="display: block; font-weight: 600; margin-bottom: 8px; font-size: 13px; color: #0f172a; letter-spacing: -0.01em;">
                <?php _e('Yazı veya Sayfa', 'dodo-ai-seo'); ?>
            </label>
            <select id="dodo-post-select" class="dodo-premium-select" aria-label="<?php _e('Yazı veya sayfa seçin', 'dodo-ai-seo'); ?>" aria-required="true" style="width: 100%; height: 40px; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; color: #0f172a; background: #fff; transition: all 0.15s; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;" onfocus="this.style.borderColor='#2563eb'; this.style.boxShadow='0 0 0 3px rgba(37,99,235,0.08)'; this.style.background='#fafbfc';" onblur="this.style.borderColor='#cbd5e1'; this.style.boxShadow='none'; this.style.background='#fff';">
                <option value=""><?php _e('-- Bir yazı veya sayfa seçin --', 'dodo-ai-seo'); ?></option>
                <?php if ($posts_query->have_posts()): ?>
                    <?php while ($posts_query->have_posts()): $posts_query->the_post(); ?>
                        <option 
                            value="<?php echo get_the_ID(); ?>"
                            data-type="<?php echo get_post_type(); ?>"
                            data-status="<?php echo get_post_status(); ?>"
                        >
                            <?php echo esc_html(get_the_title()); ?> 
                            (<?php echo get_post_type(); ?> - <?php echo get_post_status(); ?>)
                        </option>
                    <?php endwhile; ?>
                    <?php wp_reset_postdata(); ?>
                <?php endif; ?>
            </select>
            
            <p class="dodo-form-hint" style="margin: 6px 0 0 0; font-size: 12px; color: #64748b; line-height: 1.4;">
                <?php _e('Geliştirmek istediğiniz içeriği seçin', 'dodo-ai-seo'); ?>
            </p>
        </div>
        
        <div class="dodo-form-group" style="margin-bottom: 24px;">
            <label for="dodo-focus-keyword" class="dodo-form-label" style="display: block; font-weight: 600; margin-bottom: 8px; font-size: 13px; color: #0f172a; letter-spacing: -0.01em;">
                <?php _e('Odak Anahtar Kelime', 'dodo-ai-seo'); ?>
            </label>
            <input 
                type="text" 
                id="dodo-focus-keyword" 
                class="dodo-premium-input"
                placeholder="<?php _e('örn: dijital pazarlama', 'dodo-ai-seo'); ?>"
                aria-label="<?php _e('Odak anahtar kelime', 'dodo-ai-seo'); ?>"
                aria-describedby="dodo-keyword-hint"
                style="width: 100%; height: 40px; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; color: #0f172a; background: #fff; transition: all 0.15s; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;"
                onfocus="this.style.borderColor='#2563eb'; this.style.boxShadow='0 0 0 3px rgba(37,99,235,0.08)'; this.style.background='#fafbfc';"
                onblur="this.style.borderColor='#cbd5e1'; this.style.boxShadow='none'; this.style.background='#fff';"
            />
            <p class="dodo-form-hint" id="dodo-keyword-hint" style="margin: 6px 0 0 0; font-size: 12px; color: #64748b; line-height: 1.4;">
                <?php _e('AI iyileştirmeleri bu anahtar kelimeye göre optimize edilecek', 'dodo-ai-seo'); ?>
            </p>
        </div>
        
        <div class="dodo-card-actions">
            <button type="button" id="dodo-analyze-content" class="dodo-btn-primary dodo-btn-lg" disabled aria-label="<?php _e('İçeriği analiz et', 'dodo-ai-seo'); ?>" style="width: auto; min-width: 200px; height: 42px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; background: #2563eb; border: none; border-radius: 8px; color: #fff; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.15s; box-shadow: 0 1px 2px rgba(37,99,235,0.15); letter-spacing: -0.01em;" onmouseover="if(!this.disabled){this.style.background='#1d4ed8'; this.style.boxShadow='0 2px 8px rgba(37,99,235,0.25)';}" onmouseout="this.style.background='#2563eb'; this.style.boxShadow='0 1px 2px rgba(37,99,235,0.15)';">
                <svg class="dodo-btn-icon" width="16" height="16" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                    <path d="M9 17A8 8 0 1 0 9 1a8 8 0 0 0 0 16zM19 19l-4.35-4.35" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span><?php _e('İçeriği Analiz Et', 'dodo-ai-seo'); ?></span>
            </button>
        </div>
        </div>
    </div>

    
    <!-- Content Analysis Results -->
    <div class="dodo-premium-card dodo-analysis-results" style="display:none;" role="region" aria-label="<?php _e('Analiz sonuçları', 'dodo-ai-seo'); ?>">
        <div class="dodo-card-header">
            <h2 class="dodo-card-title"><?php _e('Tespit Edilen Bölümler', 'dodo-ai-seo'); ?></h2>
            <span class="dodo-step-badge">Adım 2</span>
        </div>
        <div class="dodo-card-body">
            <div id="dodo-detected-sections" role="list">
                <!-- Will be populated via AJAX -->
            </div>
        </div>
    </div>
    
    <!-- Sprint 5: GEO Analysis Panel -->
    <div class="dodo-premium-card dodo-geo-analysis-panel" id="dodo-geo-panel" style="display:none;">
        <div class="dodo-card-header">
            <h2 class="dodo-card-title">
                🌍 <?php _e('GEO Analysis', 'dodo-ai-seo'); ?>
            </h2>
            <span class="dodo-step-badge">Sprint 5</span>
        </div>
        <div class="dodo-card-body">
            <div class="dodo-geo-metrics-grid">
                <div class="dodo-geo-metric-card">
                    <div class="dodo-geo-metric-label">GEO Score</div>
                    <div class="dodo-geo-metric-value" id="dodo-geo-score">--</div>
                    <div class="dodo-geo-metric-bar">
                        <div class="dodo-geo-metric-fill" id="dodo-geo-score-bar"></div>
                    </div>
                </div>
                
                <div class="dodo-geo-metric-card">
                    <div class="dodo-geo-metric-label">Answerability</div>
                    <div class="dodo-geo-metric-value" id="dodo-geo-answerability">--</div>
                    <div class="dodo-geo-metric-bar">
                        <div class="dodo-geo-metric-fill" id="dodo-geo-answerability-bar"></div>
                    </div>
                </div>
                
                <div class="dodo-geo-metric-card">
                    <div class="dodo-geo-metric-label">Citation Potential</div>
                    <div class="dodo-geo-metric-value" id="dodo-geo-citation">--</div>
                    <div class="dodo-geo-metric-bar">
                        <div class="dodo-geo-metric-fill" id="dodo-geo-citation-bar"></div>
                    </div>
                </div>
                
                <div class="dodo-geo-metric-card">
                    <div class="dodo-geo-metric-label">Retrieval Friendliness</div>
                    <div class="dodo-geo-metric-value" id="dodo-geo-retrieval">--</div>
                    <div class="dodo-geo-metric-bar">
                        <div class="dodo-geo-metric-fill" id="dodo-geo-retrieval-bar"></div>
                    </div>
                </div>
                
                <div class="dodo-geo-metric-card">
                    <div class="dodo-geo-metric-label">Passage Extraction</div>
                    <div class="dodo-geo-metric-value" id="dodo-geo-passage">--</div>
                    <div class="dodo-geo-metric-bar">
                        <div class="dodo-geo-metric-fill" id="dodo-geo-passage-bar"></div>
                    </div>
                </div>
                
                <div class="dodo-geo-metric-card">
                    <div class="dodo-geo-metric-label">AI Overview Compatibility</div>
                    <div class="dodo-geo-metric-value" id="dodo-geo-ai-overview">--</div>
                    <div class="dodo-geo-metric-bar">
                        <div class="dodo-geo-metric-fill" id="dodo-geo-ai-overview-bar"></div>
                    </div>
                </div>
            </div>
            
            <div class="dodo-geo-recommendations" id="dodo-geo-recommendations">
                <!-- Will be populated via AJAX -->
            </div>
            
            <div class="dodo-card-actions">
                <button type="button" id="dodo-run-geo-analysis" class="dodo-btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                    </svg>
                    <?php _e('GEO Analizi Yap', 'dodo-ai-seo'); ?>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Sprint 5: Humanization Panel -->
    <div class="dodo-premium-card dodo-humanization-panel" id="dodo-humanization-panel" style="display:none;">
        <div class="dodo-card-header">
            <h2 class="dodo-card-title">
                👤 <?php _e('Humanization Analysis', 'dodo-ai-seo'); ?>
            </h2>
            <span class="dodo-step-badge">Sprint 5</span>
        </div>
        <div class="dodo-card-body">
            <div class="dodo-humanization-metrics-grid">
                <div class="dodo-humanization-metric-card">
                    <div class="dodo-humanization-metric-label">Humanization Score</div>
                    <div class="dodo-humanization-metric-value" id="dodo-humanization-score">--</div>
                    <div class="dodo-humanization-metric-bar">
                        <div class="dodo-humanization-metric-fill" id="dodo-humanization-score-bar"></div>
                    </div>
                </div>
                
                <div class="dodo-humanization-metric-card">
                    <div class="dodo-humanization-metric-label">Robotic Phrasing</div>
                    <div class="dodo-humanization-metric-value" id="dodo-humanization-robotic">--</div>
                    <div class="dodo-humanization-metric-bar dodo-metric-bar-inverse">
                        <div class="dodo-humanization-metric-fill" id="dodo-humanization-robotic-bar"></div>
                    </div>
                </div>
                
                <div class="dodo-humanization-metric-card">
                    <div class="dodo-humanization-metric-label">Sentence Rhythm</div>
                    <div class="dodo-humanization-metric-value" id="dodo-humanization-rhythm">--</div>
                    <div class="dodo-humanization-metric-bar">
                        <div class="dodo-humanization-metric-fill" id="dodo-humanization-rhythm-bar"></div>
                    </div>
                </div>
                
                <div class="dodo-humanization-metric-card">
                    <div class="dodo-humanization-metric-label">Burstiness</div>
                    <div class="dodo-humanization-metric-value" id="dodo-humanization-burstiness">--</div>
                    <div class="dodo-humanization-metric-bar">
                        <div class="dodo-humanization-metric-fill" id="dodo-humanization-burstiness-bar"></div>
                    </div>
                </div>
                
                <div class="dodo-humanization-metric-card">
                    <div class="dodo-humanization-metric-label">Transition Quality</div>
                    <div class="dodo-humanization-metric-value" id="dodo-humanization-transition">--</div>
                    <div class="dodo-humanization-metric-bar">
                        <div class="dodo-humanization-metric-fill" id="dodo-humanization-transition-bar"></div>
                    </div>
                </div>
            </div>
            
            <div class="dodo-humanization-suggestions" id="dodo-humanization-suggestions">
                <!-- Will be populated via AJAX -->
            </div>
            
            <div class="dodo-humanization-preset-selector">
                <label for="dodo-humanization-preset" class="dodo-form-label">
                    <?php _e('Humanization Preset', 'dodo-ai-seo'); ?>
                </label>
                <select id="dodo-humanization-preset" class="dodo-premium-select">
                    <option value="subtle"><?php _e('Subtle (hafif)', 'dodo-ai-seo'); ?></option>
                    <option value="moderate" selected><?php _e('Moderate (orta)', 'dodo-ai-seo'); ?></option>
                    <option value="strong"><?php _e('Strong (güçlü)', 'dodo-ai-seo'); ?></option>
                    <option value="conversational"><?php _e('Conversational (sohbet)', 'dodo-ai-seo'); ?></option>
                    <option value="professional"><?php _e('Professional (profesyonel)', 'dodo-ai-seo'); ?></option>
                </select>
            </div>
            
            <div class="dodo-card-actions">
                <button type="button" id="dodo-run-humanization-analysis" class="dodo-btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
                    </svg>
                    <?php _e('İnsanileştirme Analizi Yap', 'dodo-ai-seo'); ?>
                </button>
                
                <button type="button" id="dodo-apply-humanization" class="dodo-btn-secondary" style="display:none;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    <?php _e('Kontrollü İnsanileştir', 'dodo-ai-seo'); ?>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Empty State -->
    <div class="dodo-premium-card dodo-empty-state-container" id="dodo-empty-state" style="margin-bottom: 24px;">
        <div class="dodo-empty-state" style="text-align: center; padding: 60px 40px;">
            <div class="dodo-empty-state-icon" style="margin-bottom: 24px;">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.5" style="margin: 0 auto;">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/>
                    <line x1="16" y1="17" x2="8" y2="17"/>
                    <polyline points="10 9 9 9 8 9"/>
                </svg>
            </div>
            <h3 class="dodo-empty-state-title" style="margin: 0 0 12px 0; font-size: 24px; font-weight: 700; color: #0f172a;">
                <?php _e('İçerik Geliştirmeye Başlayın', 'dodo-ai-seo'); ?>
            </h3>
            <p class="dodo-empty-state-description" style="margin: 0 0 48px 0; font-size: 15px; color: #64748b; line-height: 1.6; max-width: 480px; margin-left: auto; margin-right: auto;">
                <?php _e('AI destekli içerik geliştirme platformu ile yazılarınızı profesyonel seviyeye taşıyın. Başlamak için yukarıdan bir içerik seçin.', 'dodo-ai-seo'); ?>
            </p>
            
            <!-- Onboarding Steps -->
            <div class="dodo-onboarding-hints" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 48px; text-align: left;">
                <div class="dodo-hint-card" style="padding: 24px; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border-radius: 12px; border: 1px solid #e2e8f0; transition: all 0.2s;" onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 24px rgba(0,0,0,0.08)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                    <div class="dodo-hint-number" style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; background: linear-gradient(135deg, #2271b1 0%, #135e96 100%); color: #fff; border-radius: 8px; font-size: 16px; font-weight: 700; margin-bottom: 16px;">1</div>
                    <h4 class="dodo-hint-title" style="margin: 0 0 8px 0; font-size: 16px; font-weight: 600; color: #0f172a;">
                        <?php _e('İçerik Seçin', 'dodo-ai-seo'); ?>
                    </h4>
                    <p class="dodo-hint-description" style="margin: 0; font-size: 14px; color: #64748b; line-height: 1.5;">
                        <?php _e('Geliştirmek istediğiniz yazı veya sayfayı seçin', 'dodo-ai-seo'); ?>
                    </p>
                </div>
                
                <div class="dodo-hint-card" style="padding: 24px; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border-radius: 12px; border: 1px solid #e2e8f0; transition: all 0.2s;" onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 24px rgba(0,0,0,0.08)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                    <div class="dodo-hint-number" style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; background: linear-gradient(135deg, #2271b1 0%, #135e96 100%); color: #fff; border-radius: 8px; font-size: 16px; font-weight: 700; margin-bottom: 16px;">2</div>
                    <h4 class="dodo-hint-title" style="margin: 0 0 8px 0; font-size: 16px; font-weight: 600; color: #0f172a;">
                        <?php _e('Anahtar Kelime Belirleyin', 'dodo-ai-seo'); ?>
                    </h4>
                    <p class="dodo-hint-description" style="margin: 0; font-size: 14px; color: #64748b; line-height: 1.5;">
                        <?php _e('SEO optimizasyonu için odak anahtar kelime girin', 'dodo-ai-seo'); ?>
                    </p>
                </div>
                
                <div class="dodo-hint-card" style="padding: 24px; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border-radius: 12px; border: 1px solid #e2e8f0; transition: all 0.2s;" onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 24px rgba(0,0,0,0.08)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                    <div class="dodo-hint-number" style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; background: linear-gradient(135deg, #2271b1 0%, #135e96 100%); color: #fff; border-radius: 8px; font-size: 16px; font-weight: 700; margin-bottom: 16px;">3</div>
                    <h4 class="dodo-hint-title" style="margin: 0 0 8px 0; font-size: 16px; font-weight: 600; color: #0f172a;">
                        <?php _e('AI İyileştirme', 'dodo-ai-seo'); ?>
                    </h4>
                    <p class="dodo-hint-description" style="margin: 0; font-size: 14px; color: #64748b; line-height: 1.5;">
                        <?php _e('Bölümleri analiz edin ve AI ile geliştirin', 'dodo-ai-seo'); ?>
                    </p>
                </div>
            </div>
            
            <!-- Feature Cards -->
            <div class="dodo-starter-examples" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; text-align: center;">
                <div class="dodo-example-card" style="padding: 20px; background: #fff; border: 1.5px solid #e2e8f0; border-radius: 10px; transition: all 0.2s;" onmouseover="this.style.borderColor='#2271b1'; this.style.background='#f8fafc';" onmouseout="this.style.borderColor='#e2e8f0'; this.style.background='#fff';">
                    <div class="dodo-example-icon" style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); border-radius: 10px; margin-bottom: 12px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2271b1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 4V2"/><path d="M15 16v-2"/><path d="M8 9h2"/><path d="M20 9h2"/><path d="M17.8 11.8 19 13"/><path d="M15 9h0"/><path d="M17.8 6.2 19 5"/><path d="m3 21 9-9"/><path d="M12.2 6.2 11 5"/></svg>
                    </div>
                    <h5 class="dodo-example-title" style="margin: 0 0 6px 0; font-size: 14px; font-weight: 600; color: #0f172a;">
                        <?php _e('Giriş Geliştirme', 'dodo-ai-seo'); ?>
                    </h5>
                    <p class="dodo-example-description" style="margin: 0; font-size: 12px; color: #64748b; line-height: 1.4;">
                        <?php _e('İlk paragrafınızı daha çekici hale getirin', 'dodo-ai-seo'); ?>
                    </p>
                </div>
                
                <div class="dodo-example-card" style="padding: 20px; background: #fff; border: 1.5px solid #e2e8f0; border-radius: 10px; transition: all 0.2s;" onmouseover="this.style.borderColor='#2271b1'; this.style.background='#f8fafc';" onmouseout="this.style.borderColor='#e2e8f0'; this.style.background='#fff';">
                    <div class="dodo-example-icon" style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); border-radius: 10px; margin-bottom: 12px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2271b1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                    </div>
                    <h5 class="dodo-example-title" style="margin: 0 0 6px 0; font-size: 14px; font-weight: 600; color: #0f172a;">
                        <?php _e('SEO Optimizasyonu', 'dodo-ai-seo'); ?>
                    </h5>
                    <p class="dodo-example-description" style="margin: 0; font-size: 12px; color: #64748b; line-height: 1.4;">
                        <?php _e('Anahtar kelime yoğunluğunu artırın', 'dodo-ai-seo'); ?>
                    </p>
                </div>
                
                <div class="dodo-example-card" style="padding: 20px; background: #fff; border: 1.5px solid #e2e8f0; border-radius: 10px; transition: all 0.2s;" onmouseover="this.style.borderColor='#2271b1'; this.style.background='#f8fafc';" onmouseout="this.style.borderColor='#e2e8f0'; this.style.background='#fff';">
                    <div class="dodo-example-icon" style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); border-radius: 10px; margin-bottom: 12px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2271b1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                    </div>
                    <h5 class="dodo-example-title" style="margin: 0 0 6px 0; font-size: 14px; font-weight: 600; color: #0f172a;">
                        <?php _e('Okunabilirlik', 'dodo-ai-seo'); ?>
                    </h5>
                    <p class="dodo-example-description" style="margin: 0; font-size: 12px; color: #64748b; line-height: 1.4;">
                        <?php _e('Cümleleri daha anlaşılır yapın', 'dodo-ai-seo'); ?>
                    </p>
                </div>
                
                <div class="dodo-example-card" style="padding: 20px; background: #fff; border: 1.5px solid #e2e8f0; border-radius: 10px; transition: all 0.2s;" onmouseover="this.style.borderColor='#2271b1'; this.style.background='#f8fafc';" onmouseout="this.style.borderColor='#e2e8f0'; this.style.background='#fff';">
                    <div class="dodo-example-icon" style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); border-radius: 10px; margin-bottom: 12px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2271b1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                    <h5 class="dodo-example-title" style="margin: 0 0 6px 0; font-size: 14px; font-weight: 600; color: #0f172a;">
                        <?php _e('CTA İyileştirme', 'dodo-ai-seo'); ?>
                    </h5>
                    <p class="dodo-example-description" style="margin: 0; font-size: 12px; color: #64748b; line-height: 1.4;">
                        <?php _e('Harekete geçirici mesajlar oluşturun', 'dodo-ai-seo'); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
        </div><!-- .dodo-main-content -->
        
        <!-- Premium Sidebar -->
        <aside class="dodo-sidebar" role="complementary" aria-label="<?php _e('İçerik istihbaratı ve öneriler', 'dodo-ai-seo'); ?>" style="position: sticky; top: 32px; width: 380px; justify-self: end;">
            <!-- Content Intelligence Panel (NEW - Sprint 2A) -->
            <div class="dodo-intelligence-panel" id="dodo-intelligence-panel" style="display:none;">
                <!-- Workflow Status Badge (Sprint 3 - Task 3) -->
                <div class="dodo-sidebar-card dodo-workflow-card" id="dodo-workflow-status-card">
                    <div class="dodo-sidebar-card-header">
                        <div class="dodo-sidebar-card-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <path d="M9 11l3 3L22 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h3 class="dodo-sidebar-card-title"><?php _e('Yayın Durumu', 'dodo-ai-seo'); ?></h3>
                    </div>
                    <div class="dodo-sidebar-card-body">
                        <div class="dodo-workflow-status-display">
                            <div class="dodo-workflow-badge-large" id="dodo-workflow-badge">
                                <span class="dodo-workflow-badge-label">Taslak</span>
                            </div>
                            <div class="dodo-workflow-readiness">
                                <div class="dodo-readiness-header">
                                    <span class="dodo-readiness-label"><?php _e('Yayın Hazırlığı', 'dodo-ai-seo'); ?></span>
                                    <span class="dodo-readiness-score" id="dodo-readiness-score">0%</span>
                                </div>
                                <div class="dodo-metric-bar">
                                    <div class="dodo-metric-fill" id="dodo-readiness-bar"></div>
                                </div>
                            </div>
                            <div class="dodo-workflow-warnings" id="dodo-workflow-warnings" style="display:none;">
                                <!-- Blocking warnings will be populated here -->
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="dodo-sidebar-card dodo-intelligence-card">
                    <div class="dodo-sidebar-card-header">
                        <div class="dodo-sidebar-card-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <path d="M12 2L2 7l10 5 10-5-10-5z" stroke="currentColor" stroke-width="2"/>
                                <path d="M2 17l10 5 10-5M2 12l10 5 10-5" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </div>
                        <h3 class="dodo-sidebar-card-title"><?php _e('İçerik İstihbaratı', 'dodo-ai-seo'); ?></h3>
                    </div>
                    
                    <!-- Health Score Circle -->
                    <div class="dodo-health-score-container">
                        <div class="dodo-circular-score" id="dodo-health-circle">
                            <svg class="dodo-circle-svg" viewBox="0 0 120 120">
                                <circle class="dodo-circle-bg" cx="60" cy="60" r="54" />
                                <circle class="dodo-circle-progress" cx="60" cy="60" r="54" id="dodo-health-progress" />
                            </svg>
                            <div class="dodo-circle-content">
                                <div class="dodo-circle-score" id="dodo-health-score">--</div>
                                <div class="dodo-circle-label" id="dodo-health-label">Sağlık</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Dimension Scores -->
                    <div class="dodo-dimension-scores">
                        <div class="dodo-metric-item">
                            <div class="dodo-metric-header">
                                <span class="dodo-metric-label">SEO</span>
                                <span class="dodo-metric-value" id="dodo-seo-score">--</span>
                            </div>
                            <div class="dodo-metric-bar">
                                <div class="dodo-metric-fill" id="dodo-seo-bar"></div>
                            </div>
                        </div>
                        
                        <div class="dodo-metric-item">
                            <div class="dodo-metric-header">
                                <span class="dodo-metric-label">Kalite</span>
                                <span class="dodo-metric-value" id="dodo-quality-score">--</span>
                            </div>
                            <div class="dodo-metric-bar">
                                <div class="dodo-metric-fill" id="dodo-quality-bar"></div>
                            </div>
                        </div>
                        
                        <div class="dodo-metric-item">
                            <div class="dodo-metric-header">
                                <span class="dodo-metric-label">Okunabilirlik</span>
                                <span class="dodo-metric-value" id="dodo-readability-score">--</span>
                            </div>
                            <div class="dodo-metric-bar">
                                <div class="dodo-metric-fill" id="dodo-readability-bar"></div>
                            </div>
                        </div>
                        
                        <div class="dodo-metric-item">
                            <div class="dodo-metric-header">
                                <span class="dodo-metric-label">Semantik</span>
                                <span class="dodo-metric-value" id="dodo-semantic-score">--</span>
                            </div>
                            <div class="dodo-metric-bar">
                                <div class="dodo-metric-fill" id="dodo-semantic-bar"></div>
                            </div>
                        </div>
                        
                        <div class="dodo-metric-item">
                            <div class="dodo-metric-header">
                                <span class="dodo-metric-label">AI Risk</span>
                                <span class="dodo-metric-value" id="dodo-ai-risk-score">--</span>
                            </div>
                            <div class="dodo-metric-bar dodo-metric-bar-inverse">
                                <div class="dodo-metric-fill" id="dodo-ai-risk-bar"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Confidence & Risk -->
                    <div class="dodo-confidence-container">
                        <div class="dodo-confidence-item">
                            <div class="dodo-confidence-label">Güven Skoru</div>
                            <div class="dodo-confidence-value" id="dodo-confidence-score">--</div>
                            <div class="dodo-confidence-badge" id="dodo-confidence-badge">--</div>
                        </div>
                        <div class="dodo-risk-item">
                            <div class="dodo-risk-label">Risk Seviyesi</div>
                            <div class="dodo-risk-badge" id="dodo-risk-badge">--</div>
                        </div>
                    </div>
                    
                    <!-- Quick Stats -->
                    <div class="dodo-quick-stats">
                        <div class="dodo-stat-item">
                            <svg class="dodo-stat-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                            <span class="dodo-stat-value" id="dodo-word-count">--</span>
                            <span class="dodo-stat-label">kelime</span>
                        </div>
                        <div class="dodo-stat-item">
                            <svg class="dodo-stat-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            <span class="dodo-stat-value" id="dodo-reading-time">--</span>
                            <span class="dodo-stat-label">dk okuma</span>
                        </div>
                        <div class="dodo-stat-item">
                            <svg class="dodo-stat-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            <span class="dodo-stat-value" id="dodo-keyword-density">--</span>
                            <span class="dodo-stat-label">% yoğunluk</span>
                        </div>
                    </div>
                </div>
                
                <!-- Recommendations Card -->
                <div class="dodo-sidebar-card dodo-recommendations-card">
                    <div class="dodo-sidebar-card-header">
                        <div class="dodo-sidebar-card-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <path d="M9 11l3 3L22 4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                <path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </div>
                        <h3 class="dodo-sidebar-card-title"><?php _e('Öneriler', 'dodo-ai-seo'); ?></h3>
                    </div>
                    
                    <div class="dodo-recommendations-list" id="dodo-recommendations-list">
                        <!-- Will be populated via JavaScript -->
                    </div>
                </div>
                
                <!-- Advanced Intelligence Card (Sprint 2B) -->
                <div class="dodo-sidebar-card dodo-advanced-intelligence-card">
                    <div class="dodo-sidebar-card-header">
                        <div class="dodo-sidebar-card-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                                <path d="M12 16v-4M12 8h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </div>
                        <h3 class="dodo-sidebar-card-title"><?php _e('Gelişmiş Analiz', 'dodo-ai-seo'); ?></h3>
                    </div>
                    
                    <!-- Semantic Health -->
                    <div class="dodo-advanced-metric">
                        <div class="dodo-advanced-metric-header">
                            <span class="dodo-advanced-metric-label">Semantik Sağlık</span>
                            <span class="dodo-advanced-metric-value" id="dodo-semantic-health">--</span>
                        </div>
                        <div class="dodo-advanced-metric-bar">
                            <div class="dodo-advanced-metric-fill" id="dodo-semantic-health-bar"></div>
                        </div>
                    </div>
                    
                    <!-- Editorial Tone -->
                    <div class="dodo-advanced-metric">
                        <div class="dodo-advanced-metric-header">
                            <span class="dodo-advanced-metric-label">Editoryal Ton</span>
                            <span class="dodo-advanced-metric-badge" id="dodo-editorial-tone">--</span>
                        </div>
                    </div>
                    
                    <!-- Content Depth -->
                    <div class="dodo-advanced-metric">
                        <div class="dodo-advanced-metric-header">
                            <span class="dodo-advanced-metric-label">İçerik Derinliği</span>
                            <span class="dodo-advanced-metric-badge" id="dodo-content-depth">--</span>
                        </div>
                        <div class="dodo-advanced-metric-bar">
                            <div class="dodo-advanced-metric-fill" id="dodo-content-depth-bar"></div>
                        </div>
                    </div>
                    
                    <!-- AI Similarity Risk (Sprint 2B - Task 4) -->
                    <div class="dodo-advanced-metric">
                        <div class="dodo-advanced-metric-header">
                            <span class="dodo-advanced-metric-label">AI Benzerlik Riski</span>
                            <span class="dodo-advanced-metric-badge" id="dodo-ai-similarity-risk">--</span>
                        </div>
                        <div class="dodo-advanced-metric-bar dodo-metric-bar-inverse">
                            <div class="dodo-advanced-metric-fill" id="dodo-ai-similarity-bar"></div>
                        </div>
                    </div>
                    
                    <!-- Semantic Warnings -->
                    <div class="dodo-semantic-warnings" id="dodo-semantic-warnings" style="display:none;">
                        <div class="dodo-warnings-header">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                            Semantik Uyarılar
                        </div>
                        <div class="dodo-warnings-list" id="dodo-warnings-list">
                            <!-- Will be populated via JavaScript -->
                        </div>
                    </div>
                    
                    <!-- Risk Assessment (Sprint 2B - Task 6) -->
                    <div class="dodo-risk-assessment" id="dodo-risk-assessment" style="display:none;">
                        <div class="dodo-risk-header">
                            <svg class="dodo-risk-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            <span class="dodo-risk-title">Risk Değerlendirmesi</span>
                            <span class="dodo-risk-count" id="dodo-risk-count">0</span>
                        </div>
                        <div class="dodo-risk-list" id="dodo-risk-list">
                            <!-- Will be populated via JavaScript -->
                        </div>
                    </div>
                </div>
                
                <!-- Quality Summary Card (Sprint 2B - Task 12) -->
                <div class="dodo-sidebar-card dodo-quality-summary-card">
                    <div class="dodo-sidebar-card-header">
                        <div class="dodo-sidebar-card-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </div>
                        <h3 class="dodo-sidebar-card-title"><?php _e('İçerik Özeti', 'dodo-ai-seo'); ?></h3>
                    </div>
                    
                    <!-- Overall Status -->
                    <div class="dodo-overall-status" id="dodo-overall-status">
                        <!-- Will be populated via JavaScript -->
                    </div>
                    
                    <!-- Strengths -->
                    <div class="dodo-summary-section">
                        <div class="dodo-summary-section-title">Güçlü Yönler</div>
                        <div class="dodo-summary-list" id="dodo-strengths-list">
                            <!-- Will be populated via JavaScript -->
                        </div>
                    </div>
                    
                    <!-- Weaknesses -->
                    <div class="dodo-summary-section">
                        <div class="dodo-summary-section-title">Zayıf Yönler</div>
                        <div class="dodo-summary-list" id="dodo-weaknesses-list">
                            <!-- Will be populated via JavaScript -->
                        </div>
                    </div>
                    
                    <!-- Critical Warnings -->
                    <div class="dodo-summary-section" id="dodo-critical-section" style="display:none;">
                        <div class="dodo-summary-section-title">Kritik Uyarılar</div>
                        <div class="dodo-summary-list" id="dodo-critical-list">
                            <!-- Will be populated via JavaScript -->
                        </div>
                    </div>
                    
                    <!-- Next Actions -->
                    <div class="dodo-summary-section">
                        <div class="dodo-summary-section-title">Önerilen Aksiyonlar</div>
                        <div class="dodo-summary-list" id="dodo-actions-list">
                            <!-- Will be populated via JavaScript -->
                        </div>
                    </div>
                </div>
                
                <!-- AI Visibility Panel (Sprint 3 - Task 3) -->
                <div class="dodo-sidebar-card dodo-ai-visibility-card">
                    <div class="dodo-sidebar-card-header">
                        <div class="dodo-sidebar-card-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z" fill="currentColor"/>
                                <circle cx="12" cy="12" r="3" fill="currentColor"/>
                            </svg>
                        </div>
                        <h3 class="dodo-sidebar-card-title"><?php _e('AI Görünürlüğü', 'dodo-ai-seo'); ?></h3>
                        <span class="dodo-badge dodo-badge-new">YENİ</span>
                    </div>
                    
                    <div class="dodo-ai-visibility-intro">
                        <p><?php _e('ChatGPT, Perplexity, Google AI Mode ve Gemini gibi AI arama motorlarında içeriğinizin görünürlüğünü optimize edin.', 'dodo-ai-seo'); ?></p>
                    </div>
                    
                    <!-- GEO Score Circle -->
                    <div class="dodo-geo-score-container">
                        <div class="dodo-circular-score dodo-geo-circle" id="dodo-geo-circle">
                            <svg class="dodo-circle-svg" viewBox="0 0 120 120">
                                <circle class="dodo-circle-bg" cx="60" cy="60" r="54" />
                                <circle class="dodo-circle-progress dodo-geo-progress" cx="60" cy="60" r="54" id="dodo-geo-progress" />
                            </svg>
                            <div class="dodo-circle-content">
                                <div class="dodo-circle-score" id="dodo-geo-score">--</div>
                                <div class="dodo-circle-label">GEO Skoru</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- GEO Sub-Scores -->
                    <div class="dodo-geo-subscores">
                        <div class="dodo-geo-metric">
                            <div class="dodo-geo-metric-header">
                                <svg class="dodo-geo-metric-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                <span class="dodo-geo-metric-label">AI Cevaplanabilirlik</span>
                                <span class="dodo-geo-metric-value" id="dodo-geo-answerability">--</span>
                            </div>
                            <div class="dodo-geo-metric-bar">
                                <div class="dodo-geo-metric-fill" id="dodo-geo-answerability-bar"></div>
                            </div>
                        </div>
                        
                        <div class="dodo-geo-metric">
                            <div class="dodo-geo-metric-header">
                                <svg class="dodo-geo-metric-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                                <span class="dodo-geo-metric-label">Alıntı Potansiyeli</span>
                                <span class="dodo-geo-metric-value" id="dodo-geo-citation">--</span>
                            </div>
                            <div class="dodo-geo-metric-bar">
                                <div class="dodo-geo-metric-fill" id="dodo-geo-citation-bar"></div>
                            </div>
                        </div>
                        
                        <div class="dodo-geo-metric">
                            <div class="dodo-geo-metric-header">
                                <svg class="dodo-geo-metric-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                                <span class="dodo-geo-metric-label">Chunk Kalitesi</span>
                                <span class="dodo-geo-metric-value" id="dodo-geo-chunk">--</span>
                            </div>
                            <div class="dodo-geo-metric-bar">
                                <div class="dodo-geo-metric-fill" id="dodo-geo-chunk-bar"></div>
                            </div>
                        </div>
                        
                        <div class="dodo-geo-metric">
                            <div class="dodo-geo-metric-header">
                                <svg class="dodo-geo-metric-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                <span class="dodo-geo-metric-label">Semantik Otorite</span>
                                <span class="dodo-geo-metric-value" id="dodo-geo-authority">--</span>
                            </div>
                            <div class="dodo-geo-metric-bar">
                                <div class="dodo-geo-metric-fill" id="dodo-geo-authority-bar"></div>
                            </div>
                        </div>
                        
                        <div class="dodo-geo-metric">
                            <div class="dodo-geo-metric-header">
                                <svg class="dodo-geo-metric-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                                <span class="dodo-geo-metric-label">Retrieval Dostu</span>
                                <span class="dodo-geo-metric-value" id="dodo-geo-retrieval">--</span>
                            </div>
                            <div class="dodo-geo-metric-bar">
                                <div class="dodo-geo-metric-fill" id="dodo-geo-retrieval-bar"></div>
                            </div>
                        </div>
                        
                        <div class="dodo-geo-metric">
                            <div class="dodo-geo-metric-header">
                                <svg class="dodo-geo-metric-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                                <span class="dodo-geo-metric-label">LLM Okunabilirlik</span>
                                <span class="dodo-geo-metric-value" id="dodo-geo-llm-readability">--</span>
                            </div>
                            <div class="dodo-geo-metric-bar">
                                <div class="dodo-geo-metric-fill" id="dodo-geo-llm-readability-bar"></div>
                            </div>
                        </div>
                        
                        <div class="dodo-geo-metric">
                            <div class="dodo-geo-metric-header">
                                <span class="dodo-geo-metric-icon">✂️</span>
                                <span class="dodo-geo-metric-label">Cevap Çıkarma</span>
                                <span class="dodo-geo-metric-value" id="dodo-geo-extraction">--</span>
                            </div>
                            <div class="dodo-geo-metric-bar">
                                <div class="dodo-geo-metric-fill" id="dodo-geo-extraction-bar"></div>
                            </div>
                        </div>
                        
                        <div class="dodo-geo-metric">
                            <div class="dodo-geo-metric-header">
                                <span class="dodo-geo-metric-icon">💭</span>
                                <span class="dodo-geo-metric-label">Konuşma Kapsamı</span>
                                <span class="dodo-geo-metric-value" id="dodo-geo-conversational">--</span>
                            </div>
                            <div class="dodo-geo-metric-bar">
                                <div class="dodo-geo-metric-fill" id="dodo-geo-conversational-bar"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- GEO Suggestions -->
                    <div class="dodo-geo-suggestions" id="dodo-geo-suggestions">
                        <div class="dodo-geo-suggestions-header">
                            <span class="dodo-geo-suggestions-icon">💡</span>
                            <span class="dodo-geo-suggestions-title">İyileştirme Önerileri</span>
                        </div>
                        <div class="dodo-geo-suggestions-list" id="dodo-geo-suggestions-list">
                            <!-- Will be populated via JavaScript -->
                        </div>
                    </div>
                    
                    <!-- AI Search Engines Info -->
                    <div class="dodo-geo-engines">
                        <div class="dodo-geo-engines-title">Optimize Edilen Platformlar</div>
                        <div class="dodo-geo-engines-list">
                            <span class="dodo-geo-engine-badge">ChatGPT</span>
                            <span class="dodo-geo-engine-badge">Perplexity</span>
                            <span class="dodo-geo-engine-badge">Google AI</span>
                            <span class="dodo-geo-engine-badge">Gemini</span>
                        </div>
                    </div>
                </div>
                
                <!-- Entity Enrichment Panel (Sprint 3 - Task 2) -->
                <div class="dodo-sidebar-card dodo-entity-enrichment-card">
                    <div class="dodo-sidebar-card-header">
                        <div class="dodo-sidebar-card-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <path d="M12 2L2 7l10 5 10-5-10-5z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M2 17l10 5 10-5M2 12l10 5 10-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h3 class="dodo-sidebar-card-title"><?php _e('Entity & Semantic Coverage', 'dodo-ai-seo'); ?></h3>
                    </div>
                    
                    <!-- Entity Stats -->
                    <div class="dodo-entity-stats">
                        <div class="dodo-entity-stat">
                            <span class="dodo-entity-stat-label">Semantic Coverage</span>
                            <div class="dodo-entity-stat-bar">
                                <div class="dodo-entity-stat-fill" id="dodo-semantic-coverage-bar"></div>
                            </div>
                            <span class="dodo-entity-stat-value" id="dodo-semantic-coverage">--</span>
                        </div>
                        <div class="dodo-entity-stat">
                            <span class="dodo-entity-stat-label">Topical Completeness</span>
                            <div class="dodo-entity-stat-bar">
                                <div class="dodo-entity-stat-fill" id="dodo-topical-completeness-bar"></div>
                            </div>
                            <span class="dodo-entity-stat-value" id="dodo-topical-completeness">--</span>
                        </div>
                    </div>
                    
                    <!-- Detected Entities -->
                    <div class="dodo-entities-list" id="dodo-entities-list">
                        <div class="dodo-entities-header">
                            <span class="dodo-entities-icon">🏷️</span>
                            <span class="dodo-entities-title">Detected Entities</span>
                        </div>
                        <div class="dodo-entities-items" id="dodo-entities-items">
                            <!-- Will be populated via JavaScript -->
                        </div>
                    </div>
                    
                    <!-- Enrichment Suggestions -->
                    <div class="dodo-enrichment-suggestions" id="dodo-enrichment-suggestions">
                        <div class="dodo-enrichment-header">
                            <span class="dodo-enrichment-icon">💡</span>
                            <span class="dodo-enrichment-title">Enrichment Suggestions</span>
                        </div>
                        <div class="dodo-enrichment-list" id="dodo-enrichment-list">
                            <!-- Will be populated via JavaScript -->
                        </div>
                    </div>
                </div>
                
                <!-- Smart Internal Links Panel (Sprint 3 - Task 4) -->
                <div class="dodo-sidebar-card dodo-smart-links-card">
                    <div class="dodo-sidebar-card-header">
                        <div class="dodo-sidebar-card-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </div>
                        <h3 class="dodo-sidebar-card-title"><?php _e('Akıllı İç Linkler', 'dodo-ai-seo'); ?></h3>
                        <button type="button" class="dodo-btn-icon" id="dodo-refresh-links" title="<?php _e('Linkleri Yenile', 'dodo-ai-seo'); ?>">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                                <path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </button>
                    </div>
                    
                    <!-- Authority Flow -->
                    <div class="dodo-authority-flow" id="dodo-authority-flow">
                        <div class="dodo-flow-metric">
                            <span class="dodo-flow-label">Otorite Akışı</span>
                            <span class="dodo-flow-value" id="dodo-flow-score">--</span>
                        </div>
                        <div class="dodo-flow-stats">
                            <div class="dodo-flow-stat">
                                <span class="dodo-flow-stat-icon">→</span>
                                <span class="dodo-flow-stat-value" id="dodo-internal-links">--</span>
                                <span class="dodo-flow-stat-label">İç Link</span>
                            </div>
                            <div class="dodo-flow-stat">
                                <span class="dodo-flow-stat-icon">←</span>
                                <span class="dodo-flow-stat-value" id="dodo-inbound-links">--</span>
                                <span class="dodo-flow-stat-label">Gelen Link</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Link Suggestions -->
                    <div class="dodo-link-suggestions" id="dodo-link-suggestions">
                        <div class="dodo-link-suggestions-header">
                            <span class="dodo-link-suggestions-title">Önerilen Linkler</span>
                            <span class="dodo-link-suggestions-count" id="dodo-link-count">0</span>
                        </div>
                        <div class="dodo-link-suggestions-list" id="dodo-link-suggestions-list">
                            <!-- Will be populated via JavaScript -->
                        </div>
                    </div>
                    
                    <!-- Orphan Warning -->
                    <div class="dodo-orphan-warning" id="dodo-orphan-warning" style="display:none;">
                        <svg class="dodo-orphan-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                        <div class="dodo-orphan-text">
                            <strong>Yetim Sayfa Uyarısı</strong>
                            <p>Bu içeriğe hiçbir iç link yok. SEO için riskli.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Start Card -->
            <div class="dodo-sidebar-card" id="dodo-quick-start-card" style="padding: 20px; margin-bottom: 16px; background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; box-shadow: 0 1px 2px rgba(0,0,0,0.03);">
                <div class="dodo-sidebar-card-header" style="display: flex; align-items: center; gap: 10px; margin-bottom: 16px;">
                    <div class="dodo-sidebar-card-icon" style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; background: #eff6ff; border-radius: 8px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                            <path d="M12 2L2 7l10 5 10-5-10-5z" stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M2 17l10 5 10-5M2 12l10 5 10-5" stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <h3 class="dodo-sidebar-card-title" style="margin: 0; font-size: 15px; font-weight: 700; color: #0f172a; letter-spacing: -0.01em;">
                        <?php _e('Hızlı Başlangıç', 'dodo-ai-seo'); ?>
                    </h3>
                </div>
                <ul class="dodo-sidebar-list" style="margin: 0; padding: 0; list-style: none;">
                    <li style="padding: 10px 0; border-bottom: 1px solid #f1f5f9; font-size: 13px; color: #475569; line-height: 1.4; display: flex; align-items: flex-start; gap: 8px;">
                        <span style="display: inline-flex; align-items: center; justify-content: center; min-width: 18px; height: 18px; background: #2563eb; color: #fff; border-radius: 4px; font-size: 10px; font-weight: 700; margin-top: 1px;">1</span>
                        <span><?php _e('Bir yazı veya sayfa seçin', 'dodo-ai-seo'); ?></span>
                    </li>
                    <li style="padding: 10px 0; border-bottom: 1px solid #f1f5f9; font-size: 13px; color: #475569; line-height: 1.4; display: flex; align-items: flex-start; gap: 8px;">
                        <span style="display: inline-flex; align-items: center; justify-content: center; min-width: 18px; height: 18px; background: #2563eb; color: #fff; border-radius: 4px; font-size: 10px; font-weight: 700; margin-top: 1px;">2</span>
                        <span><?php _e('Odak anahtar kelime belirleyin', 'dodo-ai-seo'); ?></span>
                    </li>
                    <li style="padding: 10px 0; border-bottom: 1px solid #f1f5f9; font-size: 13px; color: #475569; line-height: 1.4; display: flex; align-items: flex-start; gap: 8px;">
                        <span style="display: inline-flex; align-items: center; justify-content: center; min-width: 18px; height: 18px; background: #2563eb; color: #fff; border-radius: 4px; font-size: 10px; font-weight: 700; margin-top: 1px;">3</span>
                        <span><?php _e('İçeriği analiz edin', 'dodo-ai-seo'); ?></span>
                    </li>
                    <li style="padding: 10px 0; font-size: 13px; color: #475569; line-height: 1.4; display: flex; align-items: flex-start; gap: 8px;">
                        <span style="display: inline-flex; align-items: center; justify-content: center; min-width: 18px; height: 18px; background: #2563eb; color: #fff; border-radius: 4px; font-size: 10px; font-weight: 700; margin-top: 1px;">4</span>
                        <span><?php _e('Bölümleri geliştirin', 'dodo-ai-seo'); ?></span>
                    </li>
                </ul>
            </div>
            
            <!-- Tips Card -->
            <div class="dodo-sidebar-card dodo-sidebar-card-accent" style="padding: 18px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px;">
                <div class="dodo-sidebar-card-header" style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
                    <div class="dodo-sidebar-card-icon" style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; background: #eff6ff; border-radius: 8px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="10" stroke="#2563eb" stroke-width="2"/>
                            <path d="M12 16v-4M12 8h.01" stroke="#2563eb" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <h3 class="dodo-sidebar-card-title" style="margin: 0; font-size: 15px; font-weight: 700; color: #0f172a; letter-spacing: -0.01em;">
                        <?php _e('İpuçları', 'dodo-ai-seo'); ?>
                    </h3>
                </div>
                <p class="dodo-sidebar-text" style="margin: 0; font-size: 13px; color: #475569; line-height: 1.5;">
                    <?php _e('AI iyileştirmeleri, içeriğinizin SEO performansını artırır ve okunabilirliği geliştirir.', 'dodo-ai-seo'); ?>
                </p>
            </div>
        </aside>
        
    </div><!-- .dodo-layout-container -->
    
    <!-- Responsive CSS -->
    <style>
    @media (max-width: 1400px) {
        .dodo-layout-container {
            grid-template-columns: 1fr 360px !important;
            gap: 24px !important;
        }
    }
    
    @media (max-width: 1200px) {
        .dodo-layout-container {
            grid-template-columns: 1fr !important;
            gap: 20px !important;
            max-width: 800px !important;
        }
        
        .dodo-sidebar {
            width: 100% !important;
            position: static !important;
            justify-self: stretch !important;
        }
        
        .dodo-onboarding-hints {
            grid-template-columns: 1fr !important;
        }
        
        .dodo-starter-examples {
            grid-template-columns: repeat(2, 1fr) !important;
        }
    }
    
    @media (max-width: 768px) {
        .dodo-starter-examples {
            grid-template-columns: 1fr !important;
        }
    }
    </style>
    
    <!-- Preview Modal -->
    <div id="dodo-improver-preview-modal" class="dodo-modal" style="display:none;">
        <div class="dodo-modal-overlay"></div>
        <div class="dodo-modal-content">
            <div class="dodo-modal-header">
                <h3><?php _e('İyileştirme Önizlemesi', 'dodo-ai-seo'); ?></h3>
                <button type="button" class="dodo-modal-close">&times;</button>
            </div>
            
            <div class="dodo-modal-body">
                <!-- Validation Warning Panel (NEW - Task 7) -->
                <div class="dodo-validation-warning" style="display:none;">
                    <svg class="dodo-validation-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    <div class="dodo-validation-content">
                        <h4><?php _e('AI Yeniden Yazımı Doğrulama Sistemi Tarafından Reddedildi', 'dodo-ai-seo'); ?></h4>
                        <p><?php _e('AI çıktısı güvenlik doğrulamasından geçemedi. İçerik korunmuştur.', 'dodo-ai-seo'); ?></p>
                        <div class="dodo-validation-details"></div>
                    </div>
                </div>
                
                <!-- AI Edit Strategy Info (NEW) -->
                <div class="dodo-strategy-info" style="display:none;">
                    <h4><?php _e('AI Düzenleme Stratejisi', 'dodo-ai-seo'); ?></h4>
                    <div class="dodo-metadata-grid">
                        <!-- Will be populated via JavaScript -->
                    </div>
                </div>
                
                <!-- Diff Statistics (NEW) -->
                <div class="dodo-diff-stats" style="display:none;">
                    <h4><?php _e('Değişiklik Özeti', 'dodo-ai-seo'); ?></h4>
                    <div class="dodo-diff-stats-grid">
                        <!-- Will be populated via JavaScript -->
                    </div>
                </div>
                
                <!-- View Tabs (NEW) -->
                <div class="dodo-preview-tabs">
                    <button type="button" class="dodo-tab-btn active" data-tab="original">
                        <?php _e('Orijinal', 'dodo-ai-seo'); ?>
                    </button>
                    <button type="button" class="dodo-tab-btn" data-tab="diff">
                        <?php _e('Değişiklikler', 'dodo-ai-seo'); ?>
                    </button>
                    <button type="button" class="dodo-tab-btn" data-tab="improved">
                        <?php _e('Sonuç', 'dodo-ai-seo'); ?>
                    </button>
                </div>
                
                <!-- Tab Content -->
                <div class="dodo-preview-container">
                    <!-- Original Tab -->
                    <div class="dodo-tab-content active" data-tab-content="original">
                        <div class="dodo-preview-column">
                            <h4><?php _e('Orijinal İçerik', 'dodo-ai-seo'); ?></h4>
                            <div class="dodo-preview-original"></div>
                        </div>
                    </div>
                    
                    <!-- Diff Tab -->
                    <div class="dodo-tab-content" data-tab-content="diff">
                        <div class="dodo-preview-column dodo-preview-full">
                            <h4><?php _e('Görsel Fark (Değişiklikler Vurgulanmış)', 'dodo-ai-seo'); ?></h4>
                            <div class="dodo-preview-diff"></div>
                        </div>
                    </div>
                    
                    <!-- Improved Tab -->
                    <div class="dodo-tab-content" data-tab-content="improved">
                        <div class="dodo-preview-column">
                            <h4><?php _e('Geliştirilmiş İçerik', 'dodo-ai-seo'); ?></h4>
                            <div class="dodo-preview-improved"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="dodo-modal-footer">
                <button type="button" class="button dodo-modal-cancel">
                    <?php _e('İptal', 'dodo-ai-seo'); ?>
                </button>
                <button type="button" class="button button-primary dodo-modal-apply">
                    <span>✓</span> <?php _e('İyileştirmeyi Uygula', 'dodo-ai-seo'); ?>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Loading Overlay -->
    <div id="dodo-improver-loading" class="dodo-loading-overlay" style="display:none;">
        <div class="dodo-loading-content">
            <span class="spinner is-active"></span>
            <p><?php _e('AI içeriğinizi geliştiriyor...', 'dodo-ai-seo'); ?></p>
        </div>
    </div>
    
    </div>
    </div>
</div>

<script type="text/javascript">
// Localized data will be added via wp_localize_script
</script>
