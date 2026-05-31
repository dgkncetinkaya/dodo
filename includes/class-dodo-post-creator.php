<?php
/**
 * Post Creator Sınıfı
 * 
 * WordPress post oluşturur ve yönetir
 *
 * @package DODO_AI_SEO
 * @since 1.0.0
 */

// Doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

class DODO_Post_Creator {
    
    /**
     * Rank Math instance
     */
    private $rankmath;
    
    /**
     * Geçici olarak kaldırılan hook'lar
     */
    private $removed_hooks = array();
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->rankmath = new DODO_RankMath();
    }
    
    /**
     * Rank Math hook'larını geçici olarak kaldır
     */
    private function temporarily_remove_rankmath_hooks() {
        global $wp_filter;
        
        // save_post, wp_insert_post, transition_post_status hook'larını yedekle
        $hooks_to_remove = array('save_post', 'wp_insert_post', 'transition_post_status', 'wp_after_insert_post');
        
        foreach ($hooks_to_remove as $hook) {
            if (isset($wp_filter[$hook])) {
                $this->removed_hooks[$hook] = $wp_filter[$hook];
                unset($wp_filter[$hook]);
            }
        }
    }
    
    /**
     * Rank Math hook'larını geri yükle
     */
    private function restore_rankmath_hooks() {
        global $wp_filter;
        
        foreach ($this->removed_hooks as $hook => $callbacks) {
            $wp_filter[$hook] = $callbacks;
        }
        
        $this->removed_hooks = array();
    }
    
    /**
     * Blog post'u oluştur
     * 
     * @param array $post_data Post verileri
     * @param array $seo_data SEO verileri
     * @return int|WP_Error Post ID veya hata
     */
    public function create_post($post_data, $seo_data) {
        // Zorunlu alanları kontrol et
        if (empty($post_data['title']) || empty($post_data['content'])) {
            return new WP_Error('missing_data', __('Başlık ve içerik zorunludur.', 'dodo-ai-seo'));
        }
        
        // İçeriği temizle - AI çıktısındaki kod bloklarını ve zararlı içeriği kaldır
        $clean_content = $this->clean_ai_content($post_data['content']);
        
        // Scheduled publishing desteği
        $publish_mode = isset($post_data['publish_mode']) ? $post_data['publish_mode'] : 'draft';
        $queue_position = isset($post_data['queue_position']) ? absint($post_data['queue_position']) : 0;
        $manual_date = isset($post_data['scheduled_date']) ? sanitize_text_field($post_data['scheduled_date']) : '';
        $manual_time = isset($post_data['scheduled_time']) ? sanitize_text_field($post_data['scheduled_time']) : '';
        
        error_log("[DODO POST CREATOR] Publish mode: {$publish_mode}, Queue position: {$queue_position}");
        if (!empty($manual_date) && !empty($manual_time)) {
            error_log("[DODO POST CREATOR] Manual schedule: {$manual_date} {$manual_time}");
        }
        
        // Yayın tarihini hesapla
        $scheduler = new DODO_Scheduled_Publisher();
        $schedule_data = $scheduler->calculate_publish_date($publish_mode, $queue_position, $manual_date, $manual_time);
        
        error_log("[DODO POST CREATOR] Calculated status: {$schedule_data['status']}, Date: {$schedule_data['post_date']}");
        
        // Post verilerini hazırla
        $post_args = array(
            'post_title' => sanitize_text_field($post_data['title']),
            'post_content' => wp_kses_post($clean_content),
            'post_status' => $schedule_data['status'],
            'post_type' => 'post',
            'post_author' => get_current_user_id(),
            'post_date' => $schedule_data['post_date'],
            'post_date_gmt' => $schedule_data['post_date_gmt'],
        );
        
        // Log scheduled info
        if ($schedule_data['status'] === 'future') {
            error_log("[DODO SCHEDULER] Creating scheduled post: {$post_data['title']}, Date: {$schedule_data['post_date']}");
        }
        
        // ADIM 1: Kategori ekle
        if (!empty($post_data['category_id'])) {
            $post_args['post_category'] = array(absint($post_data['category_id']));
        }
        
        // ADIM 2: Excerpt ekle
        if (!empty($post_data['excerpt'])) {
            $post_args['post_excerpt'] = sanitize_textarea_field($post_data['excerpt']);
        }
        
        // ADIM 3: Slug ekle
        if (!empty($post_data['slug'])) {
            $post_args['post_name'] = sanitize_title($post_data['slug']);
        }
        
        // ADIM 4: Tags ekle
        if (!empty($post_data['tags']) && is_array($post_data['tags'])) {
            $post_args['tags_input'] = $post_data['tags'];
        }
        
        // ULTRA-MINIMAL MOD: Rank Math'i tamamen devre dışı bırak
        // Post oluşturulurken Rank Math hiçbir şey yapmasın
        
        // Rank Math DISABLE constant'ı tanımla
        if (!defined('RANK_MATH_DISABLE')) {
            define('RANK_MATH_DISABLE', true);
        }
        
        // ÖNCE Post'u oluştur
        $post_id = wp_insert_post($post_args, true);
        
        if (is_wp_error($post_id)) {
            $this->log_error('post_creation_failed', array(
                'error' => $post_id->get_error_message(),
                'post_data' => $post_args,
            ));
            return $post_id;
        }
        
        // DODO tarafından oluşturuldu işareti ekle
        update_post_meta($post_id, '_dodo_generated', true);
        update_post_meta($post_id, '_dodo_generated_date', current_time('mysql'));
        
        // Scheduled publishing metadata kaydet
        // Schedule source belirle: manuel tarih girilmişse 'manual', yoksa 'auto'
        $schedule_source = 'manual';
        if (empty($manual_date) && empty($manual_time)) {
            $schedule_source = 'auto';
        }
        $scheduler->save_schedule_meta($post_id, $publish_mode, $schedule_data['post_date'], $schedule_source);
        
        // Rank Math'in MUTLAKA eklediği meta'ları sil
        // AMA SEO meta'larını (focus_keyword, title, description) SİLME!
        global $wpdb;
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->postmeta} 
            WHERE post_id = %d 
            AND meta_key LIKE 'rank_math%%'
            AND meta_key NOT IN ('rank_math_focus_keyword', 'rank_math_focus_keywords', 'rank_math_title', 'rank_math_description')",
            $post_id
        ));
        
        // Cache'i temizle - ÇOK ÖNEMLİ!
        wp_cache_delete($post_id, 'post_meta');
        clean_post_cache($post_id);
        
        // Rank Math async işlemleri için kısa bir bekleme
        // Rank Math, post kaydedildikten sonra AJAX ile meta ekliyor olabilir
        usleep(100000); // 0.1 saniye bekle
        
        // Bir kez daha temizle (ama SEO meta'larını KORUYORUZ!)
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->postmeta} 
            WHERE post_id = %d 
            AND meta_key LIKE 'rank_math%%'
            AND meta_key NOT IN ('rank_math_focus_keyword', 'rank_math_focus_keywords', 'rank_math_title', 'rank_math_description', 'rank_math_robots', 'rank_math_advanced_robots')",
            $post_id
        ));
        
        // Cache'i tekrar temizle
        wp_cache_delete($post_id, 'post_meta');
        clean_post_cache($post_id);
        
        // Log
        if ($deleted > 0) {
            $this->log_error('rankmath_meta_deleted', array(
                'post_id' => $post_id,
                'deleted_count' => $deleted,
            ));
        }
        
        // Post ID alındıktan SONRA SEO meta verilerini kaydet
        // ADIM 5: Rank Math meta ekle (sadece string olarak, güvenli mod)
        if (!empty($seo_data) && $post_id > 0) {
            $seo_result = $this->rankmath->save_seo_meta($post_id, $seo_data);
            if (!$seo_result) {
                $this->log_error('seo_meta_save_failed', array(
                    'post_id' => $post_id,
                    'seo_data' => $seo_data,
                ));
            }
        }
        
        // DODO meta verilerini kaydet
        $this->save_dodo_meta($post_id, $post_data);
        
        // SON BİR KEZ DAHA: Rank Math meta'larını temizle
        $this->final_cleanup_rankmath_meta($post_id);
        
        // Schema ve FAQ'yi DODO meta olarak kaydet (Rank Math meta'larına değil)
        if (!empty($post_data['faq']) || !empty($post_data['schema_type'])) {
            $schema_data = array(
                'type' => !empty($post_data['schema_type']) ? $post_data['schema_type'] : 'article',
                'faqs' => !empty($post_data['faq']) ? $post_data['faq'] : array(),
            );
            
            $this->rankmath->save_dodo_schema($post_id, $schema_data);
        }
        
        return $post_id;
    }
    
    /**
     * AI çıktısındaki zararlı içeriği temizle
     * 
     * @param string $content
     * @return string
     */
    private function clean_ai_content($content) {
        // ULTRA-MINIMAL MOD: Agresif temizlik
        
        // Tehlikeli tagları kaldır
        $dangerous_tags = array('script', 'style', 'iframe', 'form', 'input', 'textarea', 'button', 'select', 'option', 'html', 'head', 'body');
        
        foreach ($dangerous_tags as $tag) {
            $content = preg_replace('/<' . $tag . '[^>]*>.*?<\/' . $tag . '>/is', '', $content);
            $content = preg_replace('/<' . $tag . '[^>]*\/?>/is', '', $content);
        }
        
        // ```html veya ``` kod bloklarını temizle
        $content = preg_replace('/```html\s*/i', '', $content);
        $content = preg_replace('/```\s*$/m', '', $content);
        $content = preg_replace('/```/m', '', $content);
        
        // <html>, <head>, <body> taglarını temizle
        $content = preg_replace('/<\/?html[^>]*>/i', '', $content);
        $content = preg_replace('/<\/?head[^>]*>/i', '', $content);
        $content = preg_replace('/<\/?body[^>]*>/i', '', $content);
        
        // JSON-LD script taglerini temizle (Rank Math kendi ekler)
        $content = preg_replace('/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>.*?<\/script>/is', '', $content);
        
        // Tüm script taglerini temizle
        $content = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $content);
        
        // Boş satırları temizle
        $content = preg_replace('/\n\s*\n\s*\n/', "\n\n", $content);
        
        // ULTRA-MINIMAL MOD: Sadece güvenli tagları bırak
        $allowed_tags = '<h2><h3><p><ul><ol><li><strong><em><a><blockquote><table><thead><tbody><tr><th><td>';
        $content = strip_tags($content, $allowed_tags);
        
        return trim($content);
    }
    
    /**
     * Son temizlik: Rank Math meta'larını kaldır
     * SADECE bozuk/otomatik eklenen meta'ları temizle
     * SEO meta'larına (focus_keyword, title, description) DOKUNMA!
     */
    private function final_cleanup_rankmath_meta($post_id) {
        global $wpdb;
        
        // SADECE bozuk/otomatik eklenen meta'ları sil
        // SEO meta'larını KORUYORUZ!
        $meta_to_delete = array(
            'rank_math_schema_Article',
            'rank_math_schema_FAQPage',
            'rank_math_schema',
            'rank_math_rich_snippet',
            'rank_math_snippet',
            'rank_math_faq',
            'rank_math_howto',
            'rank_math_internal_links_processed',
            'rank_math_seo_score',
            'rank_math_contentai_score',
            'rank_math_analytic_object_id',
            'rank_math_pillar_content',
        );
        
        foreach ($meta_to_delete as $key) {
            delete_post_meta($post_id, $key);
        }
        
        // Schema/FAQ/HowTo geçen metaları sil (ama focus_keyword, title, description'ı KORUYORUZ)
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->postmeta} 
            WHERE post_id = %d 
            AND meta_key LIKE 'rank_math%%' 
            AND meta_key NOT IN ('rank_math_focus_keyword', 'rank_math_focus_keywords', 'rank_math_title', 'rank_math_description', 'rank_math_robots', 'rank_math_advanced_robots')
            AND (meta_key LIKE '%%schema%%' OR meta_key LIKE '%%faq%%' OR meta_key LIKE '%%howto%%')",
            $post_id
        ));
        
        // Cache'i temizle
        wp_cache_delete($post_id, 'post_meta');
        clean_post_cache($post_id);
        
        error_log("DODO: Final cleanup - SEO meta'lar KORUNDU, sadece bozuk meta'lar silindi");
    }
    
    /**
     * DODO meta verilerini kaydet
     * 
     * @param int $post_id
     * @param array $post_data
     */
    private function save_dodo_meta($post_id, $post_data) {
        // DODO tarafından oluşturuldu işareti
        update_post_meta($post_id, '_dodo_generated', true);
        update_post_meta($post_id, '_dodo_generated_date', current_time('mysql'));
        update_post_meta($post_id, '_dodo_version', DODO_VERSION);
        
        // Kullanılan parametreler
        if (!empty($post_data['focus_keyword'])) {
            update_post_meta($post_id, '_dodo_focus_keyword', sanitize_text_field($post_data['focus_keyword']));
        }
        
        if (!empty($post_data['tone'])) {
            update_post_meta($post_id, '_dodo_tone', sanitize_text_field($post_data['tone']));
        }
        
        if (!empty($post_data['content_type'])) {
            update_post_meta($post_id, '_dodo_content_type', sanitize_text_field($post_data['content_type']));
        }
        
        if (!empty($post_data['length'])) {
            update_post_meta($post_id, '_dodo_length', sanitize_text_field($post_data['length']));
        }
        
        // İç link sayısı
        if (!empty($post_data['internal_link_count'])) {
            update_post_meta($post_id, '_dodo_internal_links', absint($post_data['internal_link_count']));
        }
        
        // Kelime sayısı
        $word_count = str_word_count(strip_tags($post_data['content']));
        update_post_meta($post_id, '_dodo_word_count', $word_count);
    }
    
    /**
     * Post'u güncelle
     * 
     * @param int $post_id
     * @param array $post_data
     * @param array $seo_data
     * @return int|WP_Error
     */
    public function update_post($post_id, $post_data, $seo_data = array()) {
        // Post var mı kontrol et
        $post = get_post($post_id);
        if (!$post) {
            return new WP_Error('post_not_found', __('Post bulunamadı.', 'dodo-ai-seo'));
        }
        
        // Güncelleme verilerini hazırla
        $update_args = array(
            'ID' => $post_id,
        );
        
        if (!empty($post_data['title'])) {
            $update_args['post_title'] = sanitize_text_field($post_data['title']);
        }
        
        if (!empty($post_data['content'])) {
            $update_args['post_content'] = wp_kses_post($post_data['content']);
        }
        
        if (!empty($post_data['excerpt'])) {
            $update_args['post_excerpt'] = sanitize_textarea_field($post_data['excerpt']);
        }
        
        if (!empty($post_data['status'])) {
            $update_args['post_status'] = $post_data['status'];
        }
        
        // Post'u güncelle
        $result = wp_update_post($update_args, true);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        // SEO meta verilerini güncelle
        if (!empty($seo_data)) {
            $this->rankmath->save_seo_meta($post_id, $seo_data);
        }
        
        // Güncelleme tarihini kaydet
        update_post_meta($post_id, '_dodo_updated_date', current_time('mysql'));
        
        return $post_id;
    }
    
    /**
     * Post'u sil
     * 
     * @param int $post_id
     * @param bool $force_delete Kalıcı olarak sil
     * @return bool|WP_Error
     */
    public function delete_post($post_id, $force_delete = false) {
        $result = wp_delete_post($post_id, $force_delete);
        
        if (!$result) {
            return new WP_Error('delete_failed', __('Post silinemedi.', 'dodo-ai-seo'));
        }
        
        return true;
    }
    
    /**
     * DODO tarafından oluşturulan postları al
     * 
     * @param array $args
     * @return array
     */
    public function get_dodo_posts($args = array()) {
        $default_args = array(
            'post_type' => 'post',
            'post_status' => 'any',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_dodo_generated',
                    'value' => true,
                    'compare' => '=',
                ),
            ),
        );
        
        $query_args = wp_parse_args($args, $default_args);
        
        return get_posts($query_args);
    }
    
    /**
     * Post istatistiklerini al
     * 
     * @param int $post_id
     * @return array
     */
    public function get_post_stats($post_id) {
        $post = get_post($post_id);
        
        if (!$post) {
            return array();
        }
        
        $content = $post->post_content;
        
        return array(
            'word_count' => str_word_count(strip_tags($content)),
            'character_count' => strlen(strip_tags($content)),
            'paragraph_count' => substr_count($content, '</p>'),
            'heading_count' => substr_count($content, '<h2>') + substr_count($content, '<h3>'),
            'internal_links' => $this->count_internal_links($content),
            'external_links' => $this->count_external_links($content),
            'images' => substr_count($content, '<img'),
            'generated_by_dodo' => get_post_meta($post_id, '_dodo_generated', true),
            'generated_date' => get_post_meta($post_id, '_dodo_generated_date', true),
            'seo_score' => $this->rankmath->calculate_seo_score($post_id),
        );
    }
    
    /**
     * İç link sayısını say
     * 
     * @param string $content
     * @return int
     */
    private function count_internal_links($content) {
        $site_url = get_site_url();
        preg_match_all('/<a[^>]+href=["\'](' . preg_quote($site_url, '/') . '[^"\']*)["\'][^>]*>/i', $content, $matches);
        return count($matches[0]);
    }
    
    /**
     * Dış link sayısını say
     * 
     * @param string $content
     * @return int
     */
    private function count_external_links($content) {
        $site_url = get_site_url();
        preg_match_all('/<a[^>]+href=["\']([^"\']*)["\'][^>]*>/i', $content, $matches);
        
        $external_count = 0;
        foreach ($matches[1] as $url) {
            if (strpos($url, $site_url) === false && strpos($url, 'http') === 0) {
                $external_count++;
            }
        }
        
        return $external_count;
    }
    
    /**
     * Hata logla
     * 
     * @param string $error_type
     * @param array $error_data
     */
    private function log_error($error_type, $error_data) {
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'type' => 'post_creator_' . $error_type,
            'data' => $error_data,
        );
        
        // Son 20 hatayı sakla
        $error_logs = get_option('dodo_error_logs', array());
        array_unshift($error_logs, $log_entry);
        $error_logs = array_slice($error_logs, 0, 20);
        
        update_option('dodo_error_logs', $error_logs, false);
        
        // WordPress error log'a da yaz
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('DODO AI SEO - Post Creator Error [' . $error_type . ']: ' . print_r($error_data, true));
        }
    }
    
    /**
     * Markdown'ı HTML'e çevir
     * 
     * @param string $markdown
     * @return string
     */
    public function markdown_to_html($markdown) {
        // Basit markdown parser
        $html = $markdown;
        
        // H2 başlıklar
        $html = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $html);
        
        // H3 başlıklar
        $html = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $html);
        
        // Kalın yazı
        $html = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $html);
        
        // İtalik
        $html = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $html);
        
        // Linkler - Markdown attributes desteği ile
        // Syntax: [text](url){:target="_blank" rel="nofollow noopener"}
        // Syntax: [text](url){:target="_blank" rel="noopener"} <- DOFOLLOW
        $html = preg_replace_callback(
            '/\[([^\]]+)\]\(([^\)]+)\)(\{:[^\}]+\})?/',
            function($matches) {
                $text = $matches[1];
                $url = $matches[2];
                $attributes = isset($matches[3]) ? $matches[3] : '';
                
                // Site URL'ini al
                $site_url = get_site_url();
                
                // Dış link mi kontrol et
                $is_external = (strpos($url, 'http') === 0 && strpos($url, $site_url) === false);
                
                if ($is_external) {
                    // Default attributes
                    $target = '';
                    $rel = 'nofollow noopener'; // Default nofollow
                    
                    // Attributes varsa parse et
                    if (!empty($attributes)) {
                        // target attribute
                        if (preg_match('/target="([^"]+)"/', $attributes, $target_matches)) {
                            $target = ' target="' . esc_attr($target_matches[1]) . '"';
                        }
                        
                        // rel attribute - ÖNEMLİ: Bu rel değerini kullan
                        if (preg_match('/rel="([^"]+)"/', $attributes, $rel_matches)) {
                            $rel = $rel_matches[1];
                        }
                    }
                    
                    // Link oluştur
                    return '<a href="' . esc_url($url) . '"' . $target . ' rel="' . esc_attr($rel) . '">' . $text . '</a>';
                } else {
                    // İç link - normal
                    return '<a href="' . esc_url($url) . '">' . $text . '</a>';
                }
            },
            $html
        );
        
        // Liste öğeleri
        $html = preg_replace('/^- (.+)$/m', '<li>$1</li>', $html);
        $html = preg_replace('/(<li>.*<\/li>)/s', '<ul>$1</ul>', $html);
        
        // Paragraflar
        $html = wpautop($html);
        
        return $html;
    }
}
