<?php
/**
 * Bulk Operations View
 * 
 * @package DODO_AI_SEO
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap dodo-admin-wrap">
    <div class="dodo-page-header">
        <h1><?php _e('Toplu İşlemler', 'dodo-ai-seo'); ?></h1>
        <p class="description"><?php _e('Birden fazla içerik üzerinde toplu analiz ve iyileştirme yapın', 'dodo-ai-seo'); ?></p>
    </div>

    <div class="dodo-bulk-grid">
        <!-- Bulk Analyze -->
        <div class="bulk-operation-card">
            <div class="card-icon">📊</div>
            <h3><?php _e('Toplu Analiz', 'dodo-ai-seo'); ?></h3>
            <p><?php _e('Seçili içeriklerin sağlık skorunu hesaplayın', 'dodo-ai-seo'); ?></p>
            
            <div class="operation-form">
                <div class="form-group">
                    <label><?php _e('İçerik Seçimi', 'dodo-ai-seo'); ?></label>
                    <select id="bulk-analyze-posts" class="dodo-select" multiple size="10">
                        <!-- Populated by JS -->
                    </select>
                    <p class="form-help"><?php _e('Ctrl/Cmd ile çoklu seçim yapabilirsiniz', 'dodo-ai-seo'); ?></p>
                </div>
                
                <button type="button" class="dodo-btn dodo-btn-primary" id="start-bulk-analyze">
                    <?php _e('Analizi Başlat', 'dodo-ai-seo'); ?>
                </button>
            </div>
        </div>

        <!-- Bulk Improve -->
        <div class="bulk-operation-card">
            <div class="card-icon">✨</div>
            <h3><?php _e('Toplu İyileştirme', 'dodo-ai-seo'); ?></h3>
            <p><?php _e('Seçili içerikleri AI ile iyileştirin', 'dodo-ai-seo'); ?></p>
            
            <div class="operation-form">
                <div class="form-group">
                    <label><?php _e('İçerik Seçimi', 'dodo-ai-seo'); ?></label>
                    <select id="bulk-improve-posts" class="dodo-select" multiple size="10">
                        <!-- Populated by JS -->
                    </select>
                </div>
                
                <div class="form-group">
                    <label><?php _e('İyileştirme Tipi', 'dodo-ai-seo'); ?></label>
                    <select id="improvement-type" class="dodo-select">
                        <option value="seo"><?php _e('SEO Optimizasyonu', 'dodo-ai-seo'); ?></option>
                        <option value="readability"><?php _e('Okunabilirlik', 'dodo-ai-seo'); ?></option>
                        <option value="quality"><?php _e('İçerik Kalitesi', 'dodo-ai-seo'); ?></option>
                        <option value="comprehensive"><?php _e('Kapsamlı İyileştirme', 'dodo-ai-seo'); ?></option>
                    </select>
                </div>
                
                <button type="button" class="dodo-btn dodo-btn-primary" id="start-bulk-improve">
                    <?php _e('İyileştirmeyi Başlat', 'dodo-ai-seo'); ?>
                </button>
            </div>
        </div>

        <!-- Bulk GEO Optimize -->
        <div class="bulk-operation-card">
            <div class="card-icon">🌍</div>
            <h3><?php _e('Toplu GEO Optimizasyonu', 'dodo-ai-seo'); ?></h3>
            <p><?php _e('İçerikleri hedef coğrafyaya göre optimize edin', 'dodo-ai-seo'); ?></p>
            
            <div class="operation-form">
                <div class="form-group">
                    <label><?php _e('İçerik Seçimi', 'dodo-ai-seo'); ?></label>
                    <select id="bulk-geo-posts" class="dodo-select" multiple size="10">
                        <!-- Populated by JS -->
                    </select>
                </div>
                
                <div class="form-group">
                    <label><?php _e('Hedef Ülke', 'dodo-ai-seo'); ?></label>
                    <select id="target-country" class="dodo-select">
                        <option value="TR"><?php _e('Türkiye', 'dodo-ai-seo'); ?></option>
                        <option value="US"><?php _e('Amerika', 'dodo-ai-seo'); ?></option>
                        <option value="GB"><?php _e('İngiltere', 'dodo-ai-seo'); ?></option>
                        <option value="DE"><?php _e('Almanya', 'dodo-ai-seo'); ?></option>
                    </select>
                </div>
                
                <button type="button" class="dodo-btn dodo-btn-primary" id="start-bulk-geo">
                    <?php _e('Optimizasyonu Başlat', 'dodo-ai-seo'); ?>
                </button>
            </div>
        </div>

        <!-- Bulk Internal Links -->
        <div class="bulk-operation-card">
            <div class="card-icon">🔗</div>
            <h3><?php _e('Toplu İç Link Analizi', 'dodo-ai-seo'); ?></h3>
            <p><?php _e('İçeriklere otomatik iç link önerileri oluşturun', 'dodo-ai-seo'); ?></p>
            
            <div class="operation-form">
                <div class="form-group">
                    <label><?php _e('İçerik Seçimi', 'dodo-ai-seo'); ?></label>
                    <select id="bulk-links-posts" class="dodo-select" multiple size="10">
                        <!-- Populated by JS -->
                    </select>
                </div>
                
                <button type="button" class="dodo-btn dodo-btn-primary" id="start-bulk-links">
                    <?php _e('Analizi Başlat', 'dodo-ai-seo'); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Progress Modal -->
    <div id="bulk-progress-modal" class="dodo-modal" style="display: none;">
        <div class="dodo-modal-overlay"></div>
        <div class="dodo-modal-content">
            <div class="dodo-modal-header">
                <h2><?php _e('Toplu İşlem İlerlemesi', 'dodo-ai-seo'); ?></h2>
            </div>
            <div class="dodo-modal-body">
                <div class="progress-info">
                    <div class="progress-stats">
                        <div class="stat-item">
                            <span class="stat-label"><?php _e('Toplam', 'dodo-ai-seo'); ?>:</span>
                            <span class="stat-value" id="total-items">0</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label"><?php _e('İşlenen', 'dodo-ai-seo'); ?>:</span>
                            <span class="stat-value" id="processed-items">0</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label"><?php _e('Başarısız', 'dodo-ai-seo'); ?>:</span>
                            <span class="stat-value" id="failed-items">0</span>
                        </div>
                    </div>
                    
                    <div class="progress-bar-wrapper">
                        <div class="progress-bar" id="bulk-progress-bar"></div>
                        <span class="progress-text" id="bulk-progress-text">0%</span>
                    </div>
                    
                    <div class="progress-estimate">
                        <span><?php _e('Tahmini Süre', 'dodo-ai-seo'); ?>:</span>
                        <strong id="estimated-time">--</strong>
                    </div>
                    
                    <div class="progress-cost">
                        <span><?php _e('Tahmini Maliyet', 'dodo-ai-seo'); ?>:</span>
                        <strong id="estimated-cost">$0.00</strong>
                    </div>
                </div>
                
                <div class="progress-log" id="progress-log">
                    <!-- Populated by JS -->
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="dodo-btn dodo-btn-secondary" id="cancel-bulk-operation">
                        <?php _e('İptal Et', 'dodo-ai-seo'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
