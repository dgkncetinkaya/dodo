<?php
/**
 * Content Audit Sınıfı
 * 
 * Mevcut blog yazılarını SEO ve içerik kalitesi açısından puanlar
 *
 * @package DODO_AI_SEO
 * @since 1.1.0
 */

// Doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

class DODO_Content_Audit {
    
    /**
     * Veritabanı tablo adı
     */
    private $table_name;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'dodo_content_audits';
        
        // Admin init'te tablo kontrolü yap
        add_action('admin_init', array($this, 'verify_table'), 10);
    }
    
    /**
     * Tablo var mı kontrol et ve yoksa oluştur
     * Transient ile sınırla - her requestte çalışmasın
     */
    public function verify_table() {
        // Transient kontrolü - 1 saatte bir kontrol et
        $verified = get_transient('dodo_audit_table_verified');
        if ($verified) {
            return;
        }
        
        global $wpdb;
        
        // Tablo var mı kontrol et
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'");
        
        if (!$table_exists) {
            error_log('[DODO AUDIT] Table does not exist, creating...');
            $this->create_table();
        }
        
        // 1 saat için transient set et
        set_transient('dodo_audit_table_verified', true, HOUR_IN_SECONDS);
    }
    
    /**
     * Veritabanı tablosunu oluştur
     */
    public function create_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$this->table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            seo_score int(11) DEFAULT 0,
            content_score int(11) DEFAULT 0,
            link_score int(11) DEFAULT 0,
            ai_score int(11) DEFAULT 0,
            overall_score int(11) DEFAULT 0,
            audit_status varchar(20) DEFAULT 'pending',
            word_count int(11) DEFAULT 0,
            internal_links int(11) DEFAULT 0,
            product_links int(11) DEFAULT 0,
            external_links int(11) DEFAULT 0,
            audit_data longtext,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY unique_post_id (post_id),
            KEY audit_status (audit_status),
            KEY overall_score (overall_score)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        error_log('[DODO AUDIT] Content audits table created/updated');
        
        // Tablo oluşturuldu mu kontrol et
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'");
        if ($table_exists) {
            error_log('[DODO AUDIT] Table verification: SUCCESS');
        } else {
            error_log('[DODO AUDIT] Table verification: FAILED');
        }
    }
    
    /**
     * Blog yazılarını listele (son 20)
     * 
     * @return array
     */
    public function get_recent_posts() {
        $posts = get_posts(array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'numberposts' => 20,
            'orderby' => 'date',
            'order' => 'DESC',
        ));
        
        $posts_with_audit = array();
        
        foreach ($posts as $post) {
            $audit = $this->get_audit_by_post_id($post->ID);
            
            $posts_with_audit[] = array(
                'post' => $post,
                'audit' => $audit,
                'word_count' => $this->calculate_word_count($post->post_content),
            );
        }
        
        return $posts_with_audit;
    }
    
    /**
     * Post ID'ye göre audit kaydını al
     * 
     * @param int $post_id
     * @return array|null
     */
    public function get_audit_by_post_id($post_id) {
        global $wpdb;
        
        $audit = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE post_id = %d ORDER BY created_at DESC LIMIT 1",
            $post_id
        ), ARRAY_A);
        
        return $audit;
    }
    
    /**
     * Post'u audit et
     * 
     * @param int $post_id
     * @return array|WP_Error
     */
    public function audit_post($post_id) {
        error_log("[DODO AUDIT] Starting audit for post #{$post_id}");
        
        $post = get_post($post_id);
        
        if (!$post || $post->post_type !== 'post') {
            error_log("[DODO AUDIT] Post #{$post_id} not found or not a post");
            return new WP_Error('invalid_post', 'Geçersiz post ID');
        }
        
        // SEO skorunu hesapla
        $seo_result = $this->calculate_seo_score($post);
        
        // Content skorunu hesapla
        $content_result = $this->calculate_content_score($post);
        
        // Link skorunu hesapla
        $link_result = $this->calculate_link_score($post);
        
        // AI quality skorunu hesapla
        $ai_result = $this->calculate_ai_score($post);
        
        // Overall skoru hesapla (Weighted Formula)
        // SEO: 30%, Content: 35%, Links: 20%, AI: 15%
        $overall_score = round(
            ($seo_result['score'] * 0.30) +
            ($content_result['score'] * 0.35) +
            ($link_result['score'] * 0.20) +
            ($ai_result['score'] * 0.15)
        );
        
        // Audit status belirle
        $audit_status = $this->determine_audit_status($overall_score);
        
        // Audit data'yı hazırla
        $audit_data = array(
            'seo' => $seo_result,
            'content' => $content_result,
            'links' => $link_result,
            'ai' => $ai_result,
        );
        
        // Database'e kaydet
        $saved = $this->save_audit($post_id, array(
            'seo_score' => $seo_result['score'],
            'content_score' => $content_result['score'],
            'link_score' => $link_result['score'],
            'ai_score' => $ai_result['score'],
            'overall_score' => $overall_score,
            'audit_status' => $audit_status,
            'word_count' => $content_result['word_count'],
            'internal_links' => $link_result['internal_links'],
            'product_links' => $link_result['product_links'],
            'external_links' => $link_result['external_links'],
            'audit_data' => wp_json_encode($audit_data),
        ));
        
        if (!$saved) {
            error_log("[DODO AUDIT] Failed to save audit for post #{$post_id}");
            return new WP_Error('save_failed', 'Audit kaydedilemedi');
        }
        
        error_log("[DODO AUDIT] Audit completed for post #{$post_id} - Overall Score: {$overall_score}");
        
        return array(
            'post_id' => $post_id,
            'seo_score' => $seo_result['score'],
            'content_score' => $content_result['score'],
            'link_score' => $link_result['score'],
            'ai_score' => $ai_result['score'],
            'overall_score' => $overall_score,
            'audit_status' => $audit_status,
            'word_count' => $content_result['word_count'],
            'internal_links' => $link_result['internal_links'],
            'product_links' => $link_result['product_links'],
            'external_links' => $link_result['external_links'],
            'audit_data' => $audit_data,
        );
    }
    
    /**
     * SEO skorunu hesapla (Recalibrated)
     * 
     * @param WP_Post $post
     * @return array
     */
    private function calculate_seo_score($post) {
        $score = 0;
        $max_score = 100;
        $checks = array();
        
        // Focus keyword al
        $focus_keyword = get_post_meta($post->ID, 'rank_math_focus_keyword', true);
        $seo_title = get_post_meta($post->ID, 'rank_math_title', true);
        $seo_description = get_post_meta($post->ID, 'rank_math_description', true);
        $slug = $post->post_name;
        $content = $post->post_content;
        $title = $post->post_title;
        
        // 1. Focus keyword var mı (15 puan)
        if (!empty($focus_keyword)) {
            $score += 15;
            $checks['focus_keyword'] = array('status' => 'pass', 'message' => 'Focus keyword mevcut', 'points' => 15);
        } else {
            $checks['focus_keyword'] = array('status' => 'fail', 'message' => 'Focus keyword eksik', 'points' => 0);
        }
        
        // 2. SEO title var mı (10 puan)
        if (!empty($seo_title)) {
            $score += 10;
            $checks['seo_title'] = array('status' => 'pass', 'message' => 'SEO title mevcut', 'points' => 10);
        } else {
            $checks['seo_title'] = array('status' => 'fail', 'message' => 'SEO title eksik', 'points' => 0);
        }
        
        // 3. Meta description var mı (10 puan)
        if (!empty($seo_description)) {
            $score += 10;
            $checks['seo_description'] = array('status' => 'pass', 'message' => 'Meta description mevcut', 'points' => 10);
        } else {
            $checks['seo_description'] = array('status' => 'fail', 'message' => 'Meta description eksik', 'points' => 0);
        }
        
        // 4. Slug uygun mu (10 puan)
        if (!empty($slug) && strlen($slug) <= 50) {
            $score += 10;
            $checks['slug'] = array('status' => 'pass', 'message' => 'Slug uygun (' . strlen($slug) . ' karakter)', 'points' => 10);
        } else if (empty($slug)) {
            $checks['slug'] = array('status' => 'fail', 'message' => 'Slug boş', 'points' => 0);
        } else {
            $score += 5; // Kısmi puan
            $checks['slug'] = array('status' => 'warning', 'message' => 'Slug çok uzun (' . strlen($slug) . ' karakter)', 'points' => 5);
        }
        
        // 5. Focus keyword title içinde mi (15 puan)
        if (!empty($focus_keyword) && !empty($title)) {
            if (stripos($title, $focus_keyword) !== false) {
                $score += 15;
                $checks['keyword_in_title'] = array('status' => 'pass', 'message' => 'Focus keyword başlıkta var', 'points' => 15);
            } else {
                $checks['keyword_in_title'] = array('status' => 'fail', 'message' => 'Focus keyword başlıkta yok', 'points' => 0);
            }
        } else {
            $checks['keyword_in_title'] = array('status' => 'fail', 'message' => 'Kontrol edilemedi', 'points' => 0);
        }
        
        // 6. Focus keyword meta description içinde mi (15 puan)
        if (!empty($focus_keyword) && !empty($seo_description)) {
            if (stripos($seo_description, $focus_keyword) !== false) {
                $score += 15;
                $checks['keyword_in_description'] = array('status' => 'pass', 'message' => 'Focus keyword meta description\'da var', 'points' => 15);
            } else {
                $checks['keyword_in_description'] = array('status' => 'fail', 'message' => 'Focus keyword meta description\'da yok', 'points' => 0);
            }
        } else {
            $checks['keyword_in_description'] = array('status' => 'fail', 'message' => 'Kontrol edilemedi', 'points' => 0);
        }
        
        // 7. Focus keyword ilk paragrafta mı (15 puan)
        if (!empty($focus_keyword) && !empty($content)) {
            // İlk 300 karakteri al
            $first_paragraph = substr(strip_tags($content), 0, 300);
            if (stripos($first_paragraph, $focus_keyword) !== false) {
                $score += 15;
                $checks['keyword_in_first_paragraph'] = array('status' => 'pass', 'message' => 'Focus keyword ilk paragrafta var', 'points' => 15);
            } else {
                $checks['keyword_in_first_paragraph'] = array('status' => 'fail', 'message' => 'Focus keyword ilk paragrafta yok', 'points' => 0);
            }
        } else {
            $checks['keyword_in_first_paragraph'] = array('status' => 'fail', 'message' => 'Kontrol edilemedi', 'points' => 0);
        }
        
        // 8. Meta description uzunluğu (10 puan)
        if (!empty($seo_description)) {
            $desc_length = mb_strlen($seo_description);
            if ($desc_length >= 120 && $desc_length <= 160) {
                $score += 10;
                $checks['description_length'] = array('status' => 'pass', 'message' => "Meta description ideal uzunlukta ({$desc_length} karakter)", 'points' => 10);
            } else if ($desc_length > 0) {
                $score += 5; // Kısmi puan
                $checks['description_length'] = array('status' => 'warning', 'message' => "Meta description uzunluğu ideal değil ({$desc_length} karakter, ideal: 120-160)", 'points' => 5);
            } else {
                $checks['description_length'] = array('status' => 'fail', 'message' => 'Meta description boş', 'points' => 0);
            }
        } else {
            $checks['description_length'] = array('status' => 'fail', 'message' => 'Meta description yok', 'points' => 0);
        }
        
        return array(
            'score' => $score,
            'max_score' => $max_score,
            'checks' => $checks,
        );
    }
    
    /**
     * Content skorunu hesapla (Recalibrated)
     * 
     * @param WP_Post $post
     * @return array
     */
    private function calculate_content_score($post) {
        $score = 0;
        $max_score = 100;
        $checks = array();
        
        $content = $post->post_content;
        $text = strip_tags($content);
        
        // Kelime sayısı (25 puan) - Daha dengeli
        $word_count = $this->calculate_word_count($content);
        if ($word_count >= 2000) {
            $score += 25;
            $checks['word_count'] = array('status' => 'pass', 'message' => "{$word_count} kelime (mükemmel)", 'points' => 25);
        } else if ($word_count >= 1500) {
            $score += 20;
            $checks['word_count'] = array('status' => 'pass', 'message' => "{$word_count} kelime (çok iyi)", 'points' => 20);
        } else if ($word_count >= 1000) {
            $score += 15;
            $checks['word_count'] = array('status' => 'pass', 'message' => "{$word_count} kelime (iyi)", 'points' => 15);
        } else if ($word_count >= 500) {
            $score += 10;
            $checks['word_count'] = array('status' => 'warning', 'message' => "{$word_count} kelime (orta)", 'points' => 10);
        } else {
            $score += 5;
            $checks['word_count'] = array('status' => 'fail', 'message' => "{$word_count} kelime (çok az)", 'points' => 5);
        }
        
        // H2 başlıkları (20 puan)
        $h2_count = substr_count($content, '<h2');
        if ($h2_count >= 5) {
            $score += 20;
            $checks['h2_headings'] = array('status' => 'pass', 'message' => "{$h2_count} H2 başlık (mükemmel)", 'points' => 20);
        } else if ($h2_count >= 3) {
            $score += 15;
            $checks['h2_headings'] = array('status' => 'pass', 'message' => "{$h2_count} H2 başlık (iyi)", 'points' => 15);
        } else if ($h2_count >= 1) {
            $score += 8;
            $checks['h2_headings'] = array('status' => 'warning', 'message' => "{$h2_count} H2 başlık (az)", 'points' => 8);
        } else {
            $checks['h2_headings'] = array('status' => 'fail', 'message' => 'H2 başlık yok', 'points' => 0);
        }
        
        // H3 başlıkları (15 puan)
        $h3_count = substr_count($content, '<h3');
        if ($h3_count >= 4) {
            $score += 15;
            $checks['h3_headings'] = array('status' => 'pass', 'message' => "{$h3_count} H3 başlık (mükemmel)", 'points' => 15);
        } else if ($h3_count >= 2) {
            $score += 10;
            $checks['h3_headings'] = array('status' => 'pass', 'message' => "{$h3_count} H3 başlık (iyi)", 'points' => 10);
        } else if ($h3_count >= 1) {
            $score += 5;
            $checks['h3_headings'] = array('status' => 'warning', 'message' => "{$h3_count} H3 başlık", 'points' => 5);
        } else {
            $checks['h3_headings'] = array('status' => 'fail', 'message' => 'H3 başlık yok', 'points' => 0);
        }
        
        // Paragraf analizi (15 puan)
        $paragraphs = preg_split('/\n\n+/', $text);
        $paragraphs = array_filter($paragraphs, function($p) {
            return strlen(trim($p)) > 50;
        });
        
        $para_count = count($paragraphs);
        $short_paras = 0;
        $long_paras = 0;
        
        foreach ($paragraphs as $para) {
            $para_length = strlen(trim($para));
            if ($para_length < 100) {
                $short_paras++;
            } else if ($para_length > 1000) {
                $long_paras++;
            }
        }
        
        if ($para_count >= 5 && $short_paras < 2 && $long_paras < 2) {
            $score += 15;
            $checks['paragraph_balance'] = array('status' => 'pass', 'message' => "{$para_count} paragraf, dengeli yapı", 'points' => 15);
        } else if ($para_count >= 3) {
            $score += 10;
            $checks['paragraph_balance'] = array('status' => 'pass', 'message' => "{$para_count} paragraf", 'points' => 10);
        } else {
            $score += 5;
            $checks['paragraph_balance'] = array('status' => 'warning', 'message' => "{$para_count} paragraf (az)", 'points' => 5);
        }
        
        // FAQ kontrolü (10 puan)
        $has_faq = (stripos($content, 'sık sorulan') !== false || 
                    stripos($content, 'sıkça sorulan') !== false ||
                    stripos($content, 'faq') !== false);
        if ($has_faq) {
            $score += 10;
            $checks['faq'] = array('status' => 'pass', 'message' => 'FAQ bölümü var', 'points' => 10);
        } else {
            $checks['faq'] = array('status' => 'fail', 'message' => 'FAQ bölümü yok', 'points' => 0);
        }
        
        // CTA kontrolü (10 puan)
        $has_cta = (stripos($content, 'hemen') !== false || 
                    stripos($content, 'şimdi') !== false ||
                    stripos($content, 'tıklayın') !== false ||
                    stripos($content, 'satın al') !== false ||
                    stripos($content, 'incele') !== false);
        if ($has_cta) {
            $score += 10;
            $checks['cta'] = array('status' => 'pass', 'message' => 'CTA mevcut', 'points' => 10);
        } else {
            $checks['cta'] = array('status' => 'fail', 'message' => 'CTA yok', 'points' => 0);
        }
        
        // Kısa paragraf spamı kontrolü (-5 puan ceza)
        if ($short_paras > 3) {
            $score -= 5;
            $checks['short_paragraph_spam'] = array('status' => 'warning', 'message' => "{$short_paras} çok kısa paragraf var", 'points' => -5);
        }
        
        // Aşırı uzun paragraf kontrolü (-5 puan ceza)
        if ($long_paras > 1) {
            $score -= 5;
            $checks['long_paragraph'] = array('status' => 'warning', 'message' => "{$long_paras} çok uzun paragraf var", 'points' => -5);
        }
        
        // Skoru 0-100 arasında tut
        $score = max(0, min(100, $score));
        
        return array(
            'score' => $score,
            'max_score' => $max_score,
            'word_count' => $word_count,
            'checks' => $checks,
        );
    }
    
    /**
     * Link skorunu hesapla (Recalibrated)
     * 
     * @param WP_Post $post
     * @return array
     */
    private function calculate_link_score($post) {
        $score = 0;
        $max_score = 100;
        $checks = array();
        
        $content = $post->post_content;
        
        // Linkleri parse et
        preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>/i', $content, $matches);
        $all_links = $matches[1];
        
        $site_url = get_site_url();
        $internal_links = 0;
        $external_links = 0;
        $product_links = 0;
        
        foreach ($all_links as $link) {
            if (strpos($link, $site_url) !== false || strpos($link, '/') === 0) {
                $internal_links++;
                if (strpos($link, '/product/') !== false || strpos($link, '/urun/') !== false) {
                    $product_links++;
                }
            } else if (strpos($link, 'http') === 0) {
                $external_links++;
            }
        }
        
        // Internal link sayısı (50 puan) - Daha dengeli
        if ($internal_links >= 8) {
            $score += 50;
            $checks['internal_links'] = array('status' => 'pass', 'message' => "{$internal_links} internal link (mükemmel)", 'points' => 50);
        } else if ($internal_links >= 5) {
            $score += 40;
            $checks['internal_links'] = array('status' => 'pass', 'message' => "{$internal_links} internal link (çok iyi)", 'points' => 40);
        } else if ($internal_links >= 3) {
            $score += 30;
            $checks['internal_links'] = array('status' => 'pass', 'message' => "{$internal_links} internal link (iyi)", 'points' => 30);
        } else if ($internal_links >= 1) {
            $score += 15;
            $checks['internal_links'] = array('status' => 'warning', 'message' => "{$internal_links} internal link (az)", 'points' => 15);
        } else {
            $checks['internal_links'] = array('status' => 'fail', 'message' => 'Internal link yok', 'points' => 0);
        }
        
        // Product link sayısı (25 puan) - Bonus olarak
        if ($product_links >= 3) {
            $score += 25;
            $checks['product_links'] = array('status' => 'pass', 'message' => "{$product_links} ürün linki (mükemmel)", 'points' => 25);
        } else if ($product_links >= 2) {
            $score += 20;
            $checks['product_links'] = array('status' => 'pass', 'message' => "{$product_links} ürün linki (iyi)", 'points' => 20);
        } else if ($product_links >= 1) {
            $score += 10;
            $checks['product_links'] = array('status' => 'warning', 'message' => "{$product_links} ürün linki", 'points' => 10);
        } else {
            $checks['product_links'] = array('status' => 'fail', 'message' => 'Ürün linki yok', 'points' => 0);
        }
        
        // External link sayısı (25 puan) - Kaynak gösterme
        if ($external_links >= 3) {
            $score += 25;
            $checks['external_links'] = array('status' => 'pass', 'message' => "{$external_links} external link (mükemmel)", 'points' => 25);
        } else if ($external_links >= 2) {
            $score += 20;
            $checks['external_links'] = array('status' => 'pass', 'message' => "{$external_links} external link (iyi)", 'points' => 20);
        } else if ($external_links >= 1) {
            $score += 10;
            $checks['external_links'] = array('status' => 'warning', 'message' => "{$external_links} external link", 'points' => 10);
        } else {
            $checks['external_links'] = array('status' => 'fail', 'message' => 'External link yok', 'points' => 0);
        }
        
        return array(
            'score' => $score,
            'max_score' => $max_score,
            'internal_links' => $internal_links,
            'product_links' => $product_links,
            'external_links' => $external_links,
            'checks' => $checks,
        );
    }
    
    /**
     * AI quality skorunu hesapla (Recalibrated)
     * 
     * @param WP_Post $post
     * @return array
     */
    private function calculate_ai_score($post) {
        $score = 100; // Başlangıç skoru
        $max_score = 100;
        $checks = array();
        
        $content = $post->post_content;
        $text = strip_tags($content);
        
        // Paragrafları ayır
        $paragraphs = preg_split('/\n\n+/', $text);
        $paragraphs = array_filter($paragraphs, function($p) {
            return strlen(trim($p)) > 50;
        });
        
        // 1. Tekrar eden paragraf kontrolü (-20 puan ceza)
        $unique_paragraphs = array_unique($paragraphs);
        $duplicate_count = count($paragraphs) - count($unique_paragraphs);
        if ($duplicate_count > 0) {
            $score -= 20;
            $checks['duplicate_paragraphs'] = array('status' => 'fail', 'message' => "{$duplicate_count} tekrar eden paragraf var", 'points' => -20);
        } else {
            $checks['duplicate_paragraphs'] = array('status' => 'pass', 'message' => 'Tekrar eden paragraf yok', 'points' => 0);
        }
        
        // 2. Tekrar eden heading kontrolü (-15 puan ceza)
        preg_match_all('/<h[2-3][^>]*>(.*?)<\/h[2-3]>/i', $content, $heading_matches);
        $headings = $heading_matches[1];
        $unique_headings = array_unique($headings);
        $duplicate_headings = count($headings) - count($unique_headings);
        if ($duplicate_headings > 0) {
            $score -= 15;
            $checks['duplicate_headings'] = array('status' => 'fail', 'message' => "{$duplicate_headings} tekrar eden başlık var", 'points' => -15);
        } else {
            $checks['duplicate_headings'] = array('status' => 'pass', 'message' => 'Tekrar eden başlık yok', 'points' => 0);
        }
        
        // 3. Geçiş cümlesi spamı kontrolü (-20 puan ceza)
        $transition_patterns = array(
            'sonuç olarak', 'özetle', 'kısacası', 'bu nedenle', 'dolayısıyla',
            'bunun yanı sıra', 'ayrıca', 'diğer yandan', 'öte yandan', 'bununla birlikte'
        );
        $pattern_count = 0;
        foreach ($transition_patterns as $pattern) {
            $pattern_count += substr_count(mb_strtolower($text, 'UTF-8'), $pattern);
        }
        
        if ($pattern_count > 8) {
            $score -= 20;
            $checks['transition_spam'] = array('status' => 'fail', 'message' => "Geçiş cümlesi çok fazla ({$pattern_count} kez)", 'points' => -20);
        } else if ($pattern_count > 5) {
            $score -= 10;
            $checks['transition_spam'] = array('status' => 'warning', 'message' => "Geçiş cümlesi fazla ({$pattern_count} kez)", 'points' => -10);
        } else {
            $checks['transition_spam'] = array('status' => 'pass', 'message' => "Geçiş cümlesi normal ({$pattern_count} kez)", 'points' => 0);
        }
        
        // 4. Keyword stuffing kontrolü (-20 puan ceza)
        $focus_keyword = get_post_meta($post->ID, 'rank_math_focus_keyword', true);
        if (!empty($focus_keyword)) {
            $keyword_count = substr_count(mb_strtolower($text, 'UTF-8'), mb_strtolower($focus_keyword, 'UTF-8'));
            $word_count = str_word_count($text, 0, 'çğıöşüÇĞİÖŞÜ');
            $keyword_density = $word_count > 0 ? ($keyword_count / $word_count) * 100 : 0;
            
            if ($keyword_density > 3) {
                $score -= 20;
                $checks['keyword_stuffing'] = array('status' => 'fail', 'message' => sprintf("Keyword stuffing var (%.1f%% yoğunluk)", $keyword_density), 'points' => -20);
            } else if ($keyword_density > 2) {
                $score -= 10;
                $checks['keyword_stuffing'] = array('status' => 'warning', 'message' => sprintf("Keyword yoğunluğu yüksek (%.1f%%)", $keyword_density), 'points' => -10);
            } else {
                $checks['keyword_stuffing'] = array('status' => 'pass', 'message' => sprintf("Keyword yoğunluğu normal (%.1f%%)", $keyword_density), 'points' => 0);
            }
        }
        
        // 5. Tek tip paragraf yapısı kontrolü (-15 puan ceza)
        if (count($paragraphs) > 3) {
            $lengths = array_map('strlen', $paragraphs);
            $avg_length = array_sum($lengths) / count($lengths);
            $variance = 0;
            
            foreach ($lengths as $length) {
                $variance += pow($length - $avg_length, 2);
            }
            $variance = $variance / count($lengths);
            $std_dev = sqrt($variance);
            
            // Standart sapma çok düşükse tüm paragraflar aynı uzunlukta (yapay)
            if ($std_dev < $avg_length * 0.2) {
                $score -= 15;
                $checks['paragraph_uniformity'] = array('status' => 'fail', 'message' => 'Tüm paragraflar aynı uzunlukta (yapay)', 'points' => -15);
            } else if ($std_dev > $avg_length * 0.8) {
                $score -= 10;
                $checks['paragraph_uniformity'] = array('status' => 'warning', 'message' => 'Paragraf dengesi bozuk', 'points' => -10);
            } else {
                $checks['paragraph_uniformity'] = array('status' => 'pass', 'message' => 'Paragraf dengesi iyi', 'points' => 0);
            }
        }
        
        // 6. Yapay sonuç paragrafı kontrolü (-10 puan ceza)
        $last_paragraph = end($paragraphs);
        $conclusion_indicators = array('sonuç olarak', 'özetlemek gerekirse', 'sonuç', 'özetle');
        $has_forced_conclusion = false;
        foreach ($conclusion_indicators as $indicator) {
            if (stripos($last_paragraph, $indicator) === 0) {
                $has_forced_conclusion = true;
                break;
            }
        }
        
        if ($has_forced_conclusion && strlen($last_paragraph) < 200) {
            $score -= 10;
            $checks['forced_conclusion'] = array('status' => 'warning', 'message' => 'Yapay sonuç paragrafı tespit edildi', 'points' => -10);
        } else {
            $checks['forced_conclusion'] = array('status' => 'pass', 'message' => 'Doğal sonuç', 'points' => 0);
        }
        
        // Skoru 0-100 arasında tut
        $score = max(0, min(100, $score));
        
        return array(
            'score' => $score,
            'max_score' => $max_score,
            'checks' => $checks,
        );
    }
    
    /**
     * Kelime sayısını doğru hesapla
     * 
     * @param string $content
     * @return int
     */
    private function calculate_word_count($content) {
        // Shortcodes temizle
        $content = strip_shortcodes($content);
        
        // Script ve style taglerini temizle
        $content = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $content);
        $content = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $content);
        
        // HTML taglerini temizle
        $text = wp_strip_all_tags($content);
        
        // HTML entities decode et
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Fazla boşlukları normalize et
        $text = preg_replace('/\s+/u', ' ', trim($text));
        
        // Türkçe karakterlerle kelime sayısı hesapla
        $word_count = str_word_count($text, 0, 'çğıöşüÇĞİÖŞÜ');
        
        return $word_count;
    }
    
    /**
     * Audit status belirle (Recalibrated)
     * 
     * @param int $overall_score
     * @return string
     */
    private function determine_audit_status($overall_score) {
        if ($overall_score >= 90) {
            return 'excellent';
        } else if ($overall_score >= 75) {
            return 'good';
        } else if ($overall_score >= 60) {
            return 'warning';
        } else {
            return 'critical';
        }
    }
    
    /**
     * Audit'i kaydet veya güncelle
     * 
     * @param int $post_id
     * @param array $data
     * @return bool
     */
    private function save_audit($post_id, $data) {
        global $wpdb;
        
        error_log("[DODO AUDIT DB] === SAVE AUDIT START ===");
        error_log("[DODO AUDIT DB] Post ID: {$post_id}");
        error_log("[DODO AUDIT DB] Table name: {$this->table_name}");
        
        // Tablo var mı kontrol et
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'");
        error_log("[DODO AUDIT DB] Table exists: " . ($table_exists ? 'YES' : 'NO'));
        
        if (!$table_exists) {
            error_log("[DODO AUDIT DB] ERROR: Table does not exist! Creating table...");
            $this->create_table();
            
            // Tekrar kontrol et
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'");
            error_log("[DODO AUDIT DB] Table created: " . ($table_exists ? 'YES' : 'NO'));
            
            if (!$table_exists) {
                error_log("[DODO AUDIT DB] FATAL: Cannot create table!");
                return false;
            }
        }
        
        // Tablo yapısını kontrol et
        $columns = $wpdb->get_results("DESCRIBE {$this->table_name}");
        error_log("[DODO AUDIT DB] Table columns: " . count($columns));
        foreach ($columns as $column) {
            error_log("[DODO AUDIT DB] Column: {$column->Field} ({$column->Type})");
        }
        
        error_log("[DODO AUDIT DB] Scores - SEO: {$data['seo_score']}, Content: {$data['content_score']}, Link: {$data['link_score']}, AI: {$data['ai_score']}, Overall: {$data['overall_score']}");
        error_log("[DODO AUDIT DB] Word count: {$data['word_count']}, Status: {$data['audit_status']}");
        error_log("[DODO AUDIT DB] Data keys: " . implode(', ', array_keys($data)));
        
        // Mevcut audit var mı kontrol et
        $existing = $this->get_audit_by_post_id($post_id);
        error_log("[DODO AUDIT DB] Existing record: " . ($existing ? 'YES (ID: ' . $existing['id'] . ')' : 'NO'));
        
        $data['updated_at'] = current_time('mysql');
        
        if ($existing) {
            // Güncelle
            error_log("[DODO AUDIT DB] Updating existing audit record ID: {$existing['id']}");
            
            $result = $wpdb->update(
                $this->table_name,
                $data,
                array('post_id' => $post_id)
            );
            
            error_log("[DODO AUDIT DB] Update result: " . var_export($result, true));
            error_log("[DODO AUDIT DB] Last query: " . $wpdb->last_query);
            error_log("[DODO AUDIT DB] Last error: " . $wpdb->last_error);
            
            if ($result === false) {
                error_log("[DODO AUDIT DB] ERROR: DB update failed!");
                error_log("[DODO AUDIT DB] Data values: " . print_r($data, true));
                return false;
            }
            
            error_log("[DODO AUDIT DB] DB update successful (rows affected: {$result})");
        } else {
            // Yeni kayıt
            error_log("[DODO AUDIT DB] Creating new audit record");
            $data['post_id'] = $post_id;
            $data['created_at'] = current_time('mysql');
            
            error_log("[DODO AUDIT DB] Insert data keys: " . implode(', ', array_keys($data)));
            
            $result = $wpdb->insert(
                $this->table_name,
                $data
            );
            
            error_log("[DODO AUDIT DB] Insert result: " . var_export($result, true));
            error_log("[DODO AUDIT DB] Last query: " . $wpdb->last_query);
            error_log("[DODO AUDIT DB] Last error: " . $wpdb->last_error);
            
            if ($result === false) {
                error_log("[DODO AUDIT DB] ERROR: DB insert failed!");
                error_log("[DODO AUDIT DB] Data values: " . print_r($data, true));
                return false;
            }
            
            error_log("[DODO AUDIT DB] DB insert successful, ID: " . $wpdb->insert_id);
        }
        
        error_log("[DODO AUDIT DB] === SAVE AUDIT END ===");
        return true;
    }
}
