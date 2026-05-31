<?php
/**
 * Settings Sınıfı
 * 
 * Eklenti ayarlarını yönetir
 *
 * @package DODO_AI_SEO
 * @since 1.0.0
 */

// Doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

class DODO_Settings {
    
    /**
     * Ayarlar option key
     */
    private $option_name = 'dodo_ai_seo_settings';
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    /**
     * Ayarları kaydet
     */
    public function register_settings() {
        register_setting(
            'dodo_ai_seo_settings_group',
            $this->option_name,
            array($this, 'sanitize_settings')
        );
    }
    
    /**
     * Ayarları sanitize et
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        // OpenAI API Key
        if (isset($input['openai_api_key'])) {
            $sanitized['openai_api_key'] = sanitize_text_field($input['openai_api_key']);
        } else {
            $sanitized['openai_api_key'] = '';
        }
        
        // Varsayılan ton
        if (isset($input['default_tone'])) {
            $allowed_tones = array('technical', 'informative', 'commercial', 'friendly', 'corporate', 'expert');
            $sanitized['default_tone'] = in_array($input['default_tone'], $allowed_tones) 
                ? $input['default_tone'] 
                : 'technical';
        } else {
            $sanitized['default_tone'] = 'technical';
        }
        
        // Varsayılan uzunluk
        if (isset($input['default_length'])) {
            $allowed_lengths = array('short', 'medium', 'long');
            $sanitized['default_length'] = in_array($input['default_length'], $allowed_lengths) 
                ? $input['default_length'] 
                : 'medium';
        } else {
            $sanitized['default_length'] = 'medium';
        }
        
        // Varsayılan yayın durumu
        if (isset($input['default_status'])) {
            $allowed_statuses = array('draft', 'publish');
            $sanitized['default_status'] = in_array($input['default_status'], $allowed_statuses) 
                ? $input['default_status'] 
                : 'draft';
        } else {
            $sanitized['default_status'] = 'draft';
        }
        
        // Minimum iç link sayısı
        if (isset($input['min_internal_links'])) {
            $sanitized['min_internal_links'] = absint($input['min_internal_links']);
        } else {
            $sanitized['min_internal_links'] = 5;
        }
        
        // Maximum iç link sayısı
        if (isset($input['max_internal_links'])) {
            $sanitized['max_internal_links'] = absint($input['max_internal_links']);
        } else {
            $sanitized['max_internal_links'] = 10;
        }
        
        // OpenAI model
        if (isset($input['openai_model'])) {
            $sanitized['openai_model'] = sanitize_text_field($input['openai_model']);
        } else {
            $sanitized['openai_model'] = 'gpt-4o';
        }
        
        // OpenAI temperature
        if (isset($input['openai_temperature'])) {
            $sanitized['openai_temperature'] = floatval($input['openai_temperature']);
        } else {
            $sanitized['openai_temperature'] = 0.7;
        }
        
        // Scheduled Publishing Settings
        if (isset($input['default_publish_mode'])) {
            $allowed_modes = array('draft', 'publish', 'scheduled');
            $sanitized['default_publish_mode'] = in_array($input['default_publish_mode'], $allowed_modes) 
                ? $input['default_publish_mode'] 
                : 'draft';
            
            error_log('[DODO SANITIZE] default_publish_mode INPUT: ' . $input['default_publish_mode']);
            error_log('[DODO SANITIZE] default_publish_mode SANITIZED: ' . $sanitized['default_publish_mode']);
        } else {
            $sanitized['default_publish_mode'] = 'draft';
        }
        
        if (isset($input['daily_publish_limit'])) {
            $sanitized['daily_publish_limit'] = absint($input['daily_publish_limit']);
            if ($sanitized['daily_publish_limit'] < 1) $sanitized['daily_publish_limit'] = 1;
            if ($sanitized['daily_publish_limit'] > 20) $sanitized['daily_publish_limit'] = 20;
        } else {
            $sanitized['daily_publish_limit'] = 1;
        }
        
        if (isset($input['publish_start_hour'])) {
            $sanitized['publish_start_hour'] = absint($input['publish_start_hour']);
            if ($sanitized['publish_start_hour'] > 23) $sanitized['publish_start_hour'] = 10;
        } else {
            $sanitized['publish_start_hour'] = 10;
        }
        
        if (isset($input['publish_start_minute'])) {
            $sanitized['publish_start_minute'] = absint($input['publish_start_minute']);
            if ($sanitized['publish_start_minute'] > 59) $sanitized['publish_start_minute'] = 0;
        } else {
            $sanitized['publish_start_minute'] = 0;
        }
        
        if (isset($input['publish_on_weekends'])) {
            $sanitized['publish_on_weekends'] = (bool) $input['publish_on_weekends'];
        } else {
            $sanitized['publish_on_weekends'] = false; // Checkbox unchecked
        }
        
        if (isset($input['publish_time_interval'])) {
            $sanitized['publish_time_interval'] = absint($input['publish_time_interval']);
            if ($sanitized['publish_time_interval'] < 1) $sanitized['publish_time_interval'] = 4;
        } else {
            $sanitized['publish_time_interval'] = 4;
        }
        
        // Retry System Settings
        if (isset($input['retry_enabled'])) {
            $sanitized['retry_enabled'] = (bool) $input['retry_enabled'];
        } else {
            $sanitized['retry_enabled'] = false; // Checkbox unchecked
        }
        
        if (isset($input['max_retry_count'])) {
            $sanitized['max_retry_count'] = absint($input['max_retry_count']);
            if ($sanitized['max_retry_count'] < 1) $sanitized['max_retry_count'] = 3;
            if ($sanitized['max_retry_count'] > 10) $sanitized['max_retry_count'] = 10;
        } else {
            $sanitized['max_retry_count'] = 3;
        }
        
        // Sprint 5: Answer Blocks Settings
        $sanitized['answer_blocks_enabled'] = isset($input['answer_blocks_enabled']) ? (bool) $input['answer_blocks_enabled'] : false;
        $sanitized['block_short_answer'] = isset($input['block_short_answer']) ? (bool) $input['block_short_answer'] : true;
        $sanitized['block_featured_snippet'] = isset($input['block_featured_snippet']) ? (bool) $input['block_featured_snippet'] : false;
        $sanitized['block_direct_answer'] = isset($input['block_direct_answer']) ? (bool) $input['block_direct_answer'] : false;
        $sanitized['block_comparison'] = isset($input['block_comparison']) ? (bool) $input['block_comparison'] : false;
        $sanitized['block_ai_summary'] = isset($input['block_ai_summary']) ? (bool) $input['block_ai_summary'] : false;
        $sanitized['block_faq'] = isset($input['block_faq']) ? (bool) $input['block_faq'] : false;
        $sanitized['answer_blocks_max_calls'] = isset($input['answer_blocks_max_calls']) ? absint($input['answer_blocks_max_calls']) : 3;
        
        // Sprint 5: GEO Settings
        $sanitized['geo_analysis_mode'] = isset($input['geo_analysis_mode']) ? sanitize_text_field($input['geo_analysis_mode']) : 'basic';
        $sanitized['geo_ai_visibility_threshold'] = isset($input['geo_ai_visibility_threshold']) ? absint($input['geo_ai_visibility_threshold']) : 70;
        
        // Sprint 5: Humanization Settings
        $allowed_presets = array('subtle', 'moderate', 'strong', 'conversational', 'professional');
        $sanitized['humanization_preset'] = isset($input['humanization_preset']) && in_array($input['humanization_preset'], $allowed_presets) 
            ? $input['humanization_preset'] 
            : 'subtle';
        $sanitized['humanization_aggressiveness'] = isset($input['humanization_aggressiveness']) ? absint($input['humanization_aggressiveness']) : 5;
        
        // Sprint 5: Publishing Pipeline Settings
        $sanitized['publishing_pipeline_enabled'] = isset($input['publishing_pipeline_enabled']) ? (bool) $input['publishing_pipeline_enabled'] : true;
        $sanitized['publishing_review_required'] = isset($input['publishing_review_required']) ? (bool) $input['publishing_review_required'] : true;
        $sanitized['publishing_auto_publish'] = isset($input['publishing_auto_publish']) ? (bool) $input['publishing_auto_publish'] : false;
        
        return $sanitized;
    }
    
    /**
     * Ayarları al
     */
    public function get_settings() {
        $defaults = array(
            'openai_api_key' => '',
            'openai_model' => 'gpt-4o',
            'openai_temperature' => 0.7,
            'default_tone' => 'technical',
            'default_length' => 'medium',
            'default_status' => 'draft',
            'min_internal_links' => 5,
            'max_internal_links' => 10,
            // Scheduled Publishing Settings
            'default_publish_mode' => 'draft',
            'daily_publish_limit' => 1,
            'publish_start_hour' => 10,
            'publish_start_minute' => 0,
            'publish_on_weekends' => true,
            'publish_time_interval' => 4, // hours between posts on same day
            // Retry System Settings
            'retry_enabled' => true,
            'max_retry_count' => 3,
        );
        
        $settings = get_option($this->option_name, $defaults);
        return wp_parse_args($settings, $defaults);
    }
    
    /**
     * Tek bir ayarı al
     */
    public function get_setting($key, $default = '') {
        $settings = $this->get_settings();
        return isset($settings[$key]) ? $settings[$key] : $default;
    }
    
    /**
     * Ayarları güncelle
     */
    public function update_settings($new_settings) {
        $current_settings = $this->get_settings();
        $updated_settings = array_merge($current_settings, $new_settings);
        
        // update_option false dönebilir çünkü değer değişmemiş olabilir
        // Bu hata değildir - sadece değer zaten aynıysa false döner
        update_option($this->option_name, $updated_settings, false);
        
        // Her zaman true dön - update_option false dönse bile bu başarıdır
        // Çünkü false = "değer değişmedi" demektir, hata değil
        return true;
    }
    
    /**
     * OpenAI API key'i al
     */
    public function get_api_key() {
        return $this->get_setting('openai_api_key');
    }
    
    /**
     * API key'in geçerli olup olmadığını kontrol et
     */
    public function is_api_key_valid() {
        $api_key = $this->get_api_key();
        return !empty($api_key) && strlen($api_key) > 20;
    }
    
    /**
     * Ton seçeneklerini al
     */
    public function get_tone_options() {
        return array(
            'technical' => __('Teknik / Profesyonel', 'dodo-ai-seo'),
            'informative' => __('Öğretici / Bilgilendirici', 'dodo-ai-seo'),
            'commercial' => __('Ticari / Dönüşüm Odaklı', 'dodo-ai-seo'),
            'friendly' => __('Samimi / İnsan Odaklı', 'dodo-ai-seo'),
            'corporate' => __('Kurumsal', 'dodo-ai-seo'),
            'expert' => __('Uzman Görüşlü', 'dodo-ai-seo'),
        );
    }
    
    /**
     * Uzunluk seçeneklerini al
     */
    public function get_length_options() {
        return array(
            'short' => __('Hızlı İçerik (1200-1800 kelime)', 'dodo-ai-seo'),
            'medium' => __('SEO Blog (2500-3500 kelime)', 'dodo-ai-seo'),
            'long' => __('Otorite İçerik (4000-6000 kelime)', 'dodo-ai-seo'),
        );
    }
    
    /**
     * İçerik tipi seçeneklerini al
     */
    public function get_content_type_options() {
        return array(
            'blog' => __('Blog Yazısı', 'dodo-ai-seo'),
            'howto' => __('Nasıl Yapılır?', 'dodo-ai-seo'),
            'comparison' => __('Karşılaştırma İçeriği', 'dodo-ai-seo'),
            'listicle' => __('Liste İçeriği', 'dodo-ai-seo'),
            'guide' => __('Ürün Rehberi', 'dodo-ai-seo'),
            'category' => __('Kategori SEO Yazısı', 'dodo-ai-seo'),
            'faq' => __('SSS Odaklı İçerik', 'dodo-ai-seo'),
        );
    }
    
    /**
     * Yayın durumu seçeneklerini al
     */
    public function get_status_options() {
        return array(
            'draft' => __('Taslak', 'dodo-ai-seo'),
            'publish' => __('Yayınla', 'dodo-ai-seo'),
        );
    }
    
    /**
     * Yayın modu seçeneklerini al
     */
    public function get_publish_mode_options() {
        return array(
            'draft' => __('Taslak olarak bırak', 'dodo-ai-seo'),
            'publish' => __('Hemen yayınla', 'dodo-ai-seo'),
            'scheduled' => __('Zamanlanmış yayınla', 'dodo-ai-seo'),
        );
    }
    
    /**
     * CTA yoğunluğu seçeneklerini al
     */
    public function get_cta_intensity_options() {
        return array(
            'low' => __('Düşük - Minimum satış dili', 'dodo-ai-seo'),
            'medium' => __('Orta - Dengeli CTA yerleşimi', 'dodo-ai-seo'),
            'high' => __('Yüksek - Güçlü dönüşüm odaklı', 'dodo-ai-seo'),
        );
    }
    
    /**
     * İnsansılaştırma seviyesi seçeneklerini al
     */
    public function get_humanization_options() {
        return array(
            'standard' => __('Standart', 'dodo-ai-seo'),
            'natural' => __('Doğal - Konuşma dili, kişisel deneyim', 'dodo-ai-seo'),
            'storytelling' => __('Hikaye Anlatıcı - Anekdotlar, örnekler', 'dodo-ai-seo'),
        );
    }
}
