<?php
/**
 * UX Helper - User experience utilities
 * 
 * PHASE 7 - UX & Onboarding Refinement
 * Provides better user experience with helpful messages and states
 * 
 * @package DODO_AI_SEO
 * @since 4.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (class_exists('DODO_UX_Helper')) {
    return;
}

class DODO_UX_Helper {
    
    /**
     * Render empty state
     * 
     * @param string $title Empty state title
     * @param string $description Empty state description
     * @param string $action_text Action button text
     * @param string $action_url Action button URL
     * @param string $icon Icon emoji or HTML
     */
    public static function render_empty_state($title, $description, $action_text = '', $action_url = '', $icon = '📭') {
        ?>
        <div class="dodo-empty-state">
            <div class="empty-state-icon"><?php echo $icon; ?></div>
            <h3 class="empty-state-title"><?php echo esc_html($title); ?></h3>
            <p class="empty-state-description"><?php echo esc_html($description); ?></p>
            <?php if ($action_text && $action_url): ?>
                <a href="<?php echo esc_url($action_url); ?>" class="dodo-btn dodo-btn-primary">
                    <?php echo esc_html($action_text); ?>
                </a>
            <?php endif; ?>
        </div>
        
        <style>
        .dodo-empty-state {
            text-align: center;
            padding: 60px 20px;
            background: #f9f9f9;
            border-radius: 8px;
            margin: 20px 0;
        }
        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        .empty-state-title {
            font-size: 24px;
            color: #1e1e1e;
            margin-bottom: 10px;
        }
        .empty-state-description {
            font-size: 16px;
            color: #666;
            margin-bottom: 20px;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }
        </style>
        <?php
    }
    
    /**
     * Render tooltip
     * 
     * @param string $text Tooltip text
     * @param string $position Position: top, bottom, left, right
     */
    public static function render_tooltip($text, $position = 'top') {
        ?>
        <span class="dodo-tooltip" data-tooltip="<?php echo esc_attr($text); ?>" data-position="<?php echo esc_attr($position); ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                <line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
        </span>
        
        <style>
        .dodo-tooltip {
            position: relative;
            display: inline-block;
            cursor: help;
            color: #666;
            margin-left: 5px;
        }
        .dodo-tooltip:hover::after {
            content: attr(data-tooltip);
            position: absolute;
            background: #1e1e1e;
            color: #fff;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 13px;
            white-space: nowrap;
            z-index: 1000;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        .dodo-tooltip[data-position="top"]:hover::after {
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            margin-bottom: 5px;
        }
        .dodo-tooltip[data-position="bottom"]:hover::after {
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            margin-top: 5px;
        }
        .dodo-tooltip[data-position="left"]:hover::after {
            right: 100%;
            top: 50%;
            transform: translateY(-50%);
            margin-right: 5px;
        }
        .dodo-tooltip[data-position="right"]:hover::after {
            left: 100%;
            top: 50%;
            transform: translateY(-50%);
            margin-left: 5px;
        }
        </style>
        <?php
    }
    
    /**
     * Render error explanation
     * 
     * @param string $error_code Error code
     * @param string $error_message Error message
     * @return string Friendly error explanation
     */
    public static function explain_error($error_code, $error_message) {
        $explanations = array(
            'api_key_invalid' => array(
                'title' => __('API Anahtarı Geçersiz', 'dodo-ai-seo'),
                'explanation' => __('OpenAI API anahtarınız geçersiz veya süresi dolmuş. Lütfen ayarlardan yeni bir anahtar girin.', 'dodo-ai-seo'),
                'action' => __('Ayarlara Git', 'dodo-ai-seo'),
                'action_url' => admin_url('admin.php?page=dodo-ai-seo-settings'),
            ),
            'rate_limit' => array(
                'title' => __('API Limiti Aşıldı', 'dodo-ai-seo'),
                'explanation' => __('OpenAI API rate limit\'e ulaştınız. Lütfen birkaç dakika bekleyin veya planınızı yükseltin.', 'dodo-ai-seo'),
                'action' => __('OpenAI Dashboard', 'dodo-ai-seo'),
                'action_url' => 'https://platform.openai.com/account/limits',
            ),
            'insufficient_quota' => array(
                'title' => __('Yetersiz Kredi', 'dodo-ai-seo'),
                'explanation' => __('OpenAI hesabınızda yeterli kredi yok. Lütfen hesabınıza kredi ekleyin.', 'dodo-ai-seo'),
                'action' => __('Kredi Ekle', 'dodo-ai-seo'),
                'action_url' => 'https://platform.openai.com/account/billing',
            ),
            'network_error' => array(
                'title' => __('Bağlantı Hatası', 'dodo-ai-seo'),
                'explanation' => __('OpenAI API\'ye bağlanılamadı. İnternet bağlantınızı kontrol edin veya daha sonra tekrar deneyin.', 'dodo-ai-seo'),
                'action' => __('Tekrar Dene', 'dodo-ai-seo'),
                'action_url' => '',
            ),
            'timeout' => array(
                'title' => __('İstek Zaman Aşımı', 'dodo-ai-seo'),
                'explanation' => __('İstek çok uzun sürdü. Lütfen tekrar deneyin veya daha kısa içerik oluşturmayı deneyin.', 'dodo-ai-seo'),
                'action' => __('Tekrar Dene', 'dodo-ai-seo'),
                'action_url' => '',
            ),
            'content_filter' => array(
                'title' => __('İçerik Filtrelendi', 'dodo-ai-seo'),
                'explanation' => __('OpenAI içerik politikalarına uygun olmayan içerik tespit edildi. Lütfen farklı bir konu deneyin.', 'dodo-ai-seo'),
                'action' => '',
                'action_url' => '',
            ),
        );
        
        $explanation = $explanations[$error_code] ?? array(
            'title' => __('Bir Hata Oluştu', 'dodo-ai-seo'),
            'explanation' => $error_message,
            'action' => '',
            'action_url' => '',
        );
        
        ob_start();
        ?>
        <div class="dodo-error-explanation">
            <div class="error-icon">⚠️</div>
            <div class="error-content">
                <h4><?php echo esc_html($explanation['title']); ?></h4>
                <p><?php echo esc_html($explanation['explanation']); ?></p>
                <?php if ($explanation['action']): ?>
                    <a href="<?php echo esc_url($explanation['action_url']); ?>" class="dodo-btn dodo-btn-small" target="_blank">
                        <?php echo esc_html($explanation['action']); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <style>
        .dodo-error-explanation {
            display: flex;
            gap: 15px;
            padding: 20px;
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            border-radius: 4px;
            margin: 15px 0;
        }
        .error-icon {
            font-size: 32px;
            flex-shrink: 0;
        }
        .error-content h4 {
            margin: 0 0 8px 0;
            color: #856404;
        }
        .error-content p {
            margin: 0 0 12px 0;
            color: #856404;
        }
        .dodo-btn-small {
            padding: 6px 12px;
            font-size: 13px;
        }
        </style>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render loading state
     * 
     * @param string $message Loading message
     */
    public static function render_loading_state($message = '') {
        if (empty($message)) {
            $message = __('Yükleniyor...', 'dodo-ai-seo');
        }
        ?>
        <div class="dodo-loading-state">
            <div class="loading-spinner"></div>
            <p><?php echo esc_html($message); ?></p>
        </div>
        
        <style>
        .dodo-loading-state {
            text-align: center;
            padding: 40px 20px;
        }
        .loading-spinner {
            width: 40px;
            height: 40px;
            margin: 0 auto 15px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #2271b1;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        </style>
        <?php
    }
    
    /**
     * Render success message
     * 
     * @param string $message Success message
     * @param bool $dismissible Is dismissible
     */
    public static function render_success_message($message, $dismissible = true) {
        ?>
        <div class="dodo-success-message <?php echo $dismissible ? 'dismissible' : ''; ?>">
            <div class="success-icon">✓</div>
            <div class="success-content"><?php echo esc_html($message); ?></div>
            <?php if ($dismissible): ?>
                <button class="dismiss-btn" onclick="this.parentElement.remove()">×</button>
            <?php endif; ?>
        </div>
        
        <style>
        .dodo-success-message {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px 20px;
            background: #d4edda;
            border-left: 4px solid #28a745;
            border-radius: 4px;
            margin: 15px 0;
            position: relative;
        }
        .success-icon {
            width: 24px;
            height: 24px;
            background: #28a745;
            color: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            flex-shrink: 0;
        }
        .success-content {
            flex: 1;
            color: #155724;
        }
        .dismiss-btn {
            background: none;
            border: none;
            font-size: 24px;
            color: #155724;
            cursor: pointer;
            padding: 0;
            width: 24px;
            height: 24px;
            line-height: 1;
        }
        .dismiss-btn:hover {
            opacity: 0.7;
        }
        </style>
        <?php
    }
    
    /**
     * Render info box
     * 
     * @param string $title Info title
     * @param string $content Info content
     * @param string $type Type: info, tip, warning
     */
    public static function render_info_box($title, $content, $type = 'info') {
        $icons = array(
            'info' => 'ℹ️',
            'tip' => '💡',
            'warning' => '⚠️',
        );
        
        $colors = array(
            'info' => array('bg' => '#d1ecf1', 'border' => '#17a2b8', 'text' => '#0c5460'),
            'tip' => array('bg' => '#fff3cd', 'border' => '#ffc107', 'text' => '#856404'),
            'warning' => array('bg' => '#f8d7da', 'border' => '#dc3545', 'text' => '#721c24'),
        );
        
        $icon = $icons[$type] ?? $icons['info'];
        $color = $colors[$type] ?? $colors['info'];
        ?>
        <div class="dodo-info-box" style="background: <?php echo esc_attr($color['bg']); ?>; border-left-color: <?php echo esc_attr($color['border']); ?>; color: <?php echo esc_attr($color['text']); ?>;">
            <div class="info-icon"><?php echo $icon; ?></div>
            <div class="info-content">
                <h4><?php echo esc_html($title); ?></h4>
                <p><?php echo esc_html($content); ?></p>
            </div>
        </div>
        
        <style>
        .dodo-info-box {
            display: flex;
            gap: 15px;
            padding: 15px 20px;
            border-left: 4px solid;
            border-radius: 4px;
            margin: 15px 0;
        }
        .info-icon {
            font-size: 24px;
            flex-shrink: 0;
        }
        .info-content h4 {
            margin: 0 0 5px 0;
            font-size: 15px;
        }
        .info-content p {
            margin: 0;
            font-size: 14px;
        }
        </style>
        <?php
    }
    
    /**
     * Get contextual help for features
     * 
     * @param string $feature Feature name
     * @return array Help content
     */
    public static function get_contextual_help($feature) {
        $help = array(
            'keyword_opportunities' => array(
                'title' => __('Anahtar Kelime Fırsatları', 'dodo-ai-seo'),
                'description' => __('AI, Google Search Console verilerinizi analiz ederek yüksek potansiyelli anahtar kelimeleri bulur.', 'dodo-ai-seo'),
                'tips' => array(
                    __('Yüksek skorlu fırsatları önceliklendirin', 'dodo-ai-seo'),
                    __('Düşük rekabet, yüksek hacim kombinasyonları arayın', 'dodo-ai-seo'),
                    __('Mevcut içeriğinizle alakalı fırsatları seçin', 'dodo-ai-seo'),
                ),
            ),
            'content_improver' => array(
                'title' => __('İçerik İyileştirici', 'dodo-ai-seo'),
                'description' => __('Mevcut içeriğinizi AI ile analiz edip SEO ve okunabilirlik önerileri sunar.', 'dodo-ai-seo'),
                'tips' => array(
                    __('Önce analiz yapın, sonra önerileri inceleyin', 'dodo-ai-seo'),
                    __('Tüm önerileri uygulamak zorunda değilsiniz', 'dodo-ai-seo'),
                    __('Markanızın sesini koruyun', 'dodo-ai-seo'),
                ),
            ),
            'learning_mode' => array(
                'title' => __('Öğrenme Modu', 'dodo-ai-seo'),
                'description' => __('Sistem, içeriklerinizin performansını izleyerek stratejisini otomatik optimize eder.', 'dodo-ai-seo'),
                'tips' => array(
                    __('İlk 30 gün gözlem modunda çalışır', 'dodo-ai-seo'),
                    __('Yeterli veri toplandıktan sonra adaptasyon başlar', 'dodo-ai-seo'),
                    __('İstediğiniz zaman dondurabilir veya geri alabilirsiniz', 'dodo-ai-seo'),
                ),
            ),
        );
        
        return $help[$feature] ?? array();
    }
}
