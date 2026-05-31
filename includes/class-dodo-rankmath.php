<?php
/**
 * Rank Math Sınıfı
 * 
 * Rank Math SEO meta verilerini yönetir
 *
 * @package DODO_AI_SEO
 * @since 1.0.0
 */

// Doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

class DODO_RankMath {
    
    /**
     * Rank Math meta verilerini kaydet
     * 
     * UYARI: Rank Math meta değerleri SADECE string olarak kaydedilmeli!
     * Array, object veya JSON kaydedilmemeli.
     * 
     * @param int $post_id
     * @param array $seo_data
     * @return bool
     */
    public function save_seo_meta($post_id, $seo_data) {
        // Rank Math yüklü mü kontrol et
        if (!class_exists('RankMath')) {
            $this->log_error('rankmath_not_active', array(
                'post_id' => $post_id,
                'message' => 'Rank Math eklentisi aktif değil',
            ));
            return false;
        }
        
        // Önce hatalı/bozuk meta değerlerini temizle
        $this->clean_broken_meta($post_id);
        
        $saved_fields = array();
        $focus_keyword = '';
        
        // Focus keyword'ü detaylı logla
        error_log("=== DODO RANKMATH save_seo_meta START ===");
        error_log("DODO RANKMATH: seo_data['focus_keyword']: " . (isset($seo_data['focus_keyword']) ? $seo_data['focus_keyword'] : 'NOT SET'));
        error_log("DODO RANKMATH: is_string: " . (isset($seo_data['focus_keyword']) && is_string($seo_data['focus_keyword']) ? 'YES' : 'NO'));
        error_log("DODO RANKMATH: empty: " . (empty($seo_data['focus_keyword']) ? 'YES - BOŞ!' : 'NO - Dolu'));
        
        // Focus Keyword - Rank Math'in beklediği formatta kaydet
        if (!empty($seo_data['focus_keyword']) && is_string($seo_data['focus_keyword'])) {
            $focus_keyword = sanitize_text_field($seo_data['focus_keyword']);
            
            error_log("DODO RANKMATH: focus_keyword AFTER SANITIZE: '{$focus_keyword}'");
            
            // Boş değilse kaydet
            if (!empty($focus_keyword)) {
                // rank_math_focus_keyword - STRING olarak
                update_post_meta($post_id, 'rank_math_focus_keyword', $focus_keyword);
                
                // CRITICAL FIX: Rank Math paneli için focus_keywords'ü ARRAY olarak kaydet
                // Rank Math JavaScript'i bunu array olarak bekliyor
                update_post_meta($post_id, 'rank_math_focus_keywords', array($focus_keyword));
                
                $saved_fields[] = 'focus_keyword';
                
                error_log("DODO RANKMATH: Focus keyword kaydedildi: '{$focus_keyword}'");
                
                // VERIFY: Kaydedildi mi kontrol et
                $saved_focus_keyword = get_post_meta($post_id, 'rank_math_focus_keyword', true);
                $saved_focus_keywords = get_post_meta($post_id, 'rank_math_focus_keywords', true);
                
                error_log("DODO RANKMATH VERIFY:");
                error_log("  - rank_math_focus_keyword: '{$saved_focus_keyword}'");
                error_log("  - rank_math_focus_keywords: " . print_r($saved_focus_keywords, true));
                
                if ($saved_focus_keyword !== $focus_keyword) {
                    error_log('DODO RANKMATH ERROR: rank_math_focus_keyword save FAILED!');
                    error_log("  - Expected: '{$focus_keyword}'");
                    error_log("  - Got: '{$saved_focus_keyword}'");
                } else {
                    error_log("DODO RANKMATH SUCCESS: Focus keyword verification OK!");
                }
                
                // rank_math_focus_keywords array mi kontrol et
                if (!is_array($saved_focus_keywords) || !in_array($focus_keyword, $saved_focus_keywords)) {
                    error_log('DODO RANKMATH WARNING: rank_math_focus_keywords array değil veya focus keyword içermiyor!');
                    error_log("  - Expected: array('{$focus_keyword}')");
                    error_log("  - Got: " . print_r($saved_focus_keywords, true));
                }
            } else {
                error_log('DODO RANKMATH ERROR: Focus keyword sanitize sonrası boş!');
            }
        } else {
            error_log('DODO RANKMATH ERROR: Focus keyword seo_data içinde yok veya string değil!');
            error_log('DODO RANKMATH: seo_data dump: ' . print_r($seo_data, true));
        }
        
        // SEO Title - Focus keyword içermeli
        if (!empty($seo_data['seo_title']) && is_string($seo_data['seo_title'])) {
            $seo_title = sanitize_text_field($seo_data['seo_title']);
            
            // Focus keyword SEO title'da var mı kontrol et
            if (!empty($focus_keyword) && stripos($seo_title, $focus_keyword) === false) {
                // Focus keyword yoksa başa ekle
                $seo_title = $focus_keyword . ' - ' . $seo_title;
                
                // Uzunluk kontrolü (max 60 karakter)
                if (mb_strlen($seo_title) > 60) {
                    // Çok uzunsa kısalt
                    $seo_title = mb_substr($seo_title, 0, 57) . '...';
                }
                
                error_log("DODO: SEO title focus keyword içermiyordu, eklendi: {$seo_title}");
            }
            
            // Uzunluk kontrolü (50-60 karakter ideal)
            $title_length = mb_strlen($seo_title);
            if ($title_length > 60) {
                $seo_title = mb_substr($seo_title, 0, 57) . '...';
                error_log("DODO: SEO title çok uzundu, kısaltıldı: {$seo_title}");
            }
            
            // Boş değilse kaydet
            if (!empty($seo_title)) {
                update_post_meta($post_id, 'rank_math_title', $seo_title);
                $saved_fields[] = 'seo_title';
                
                error_log("DODO: SEO title kaydedildi: {$seo_title}");
            }
        } elseif (!empty($focus_keyword)) {
            // SEO title yoksa focus keyword'den oluştur
            $post = get_post($post_id);
            $seo_title = $focus_keyword . ' - ' . $post->post_title;
            
            if (mb_strlen($seo_title) > 60) {
                $seo_title = mb_substr($seo_title, 0, 57) . '...';
            }
            
            update_post_meta($post_id, 'rank_math_title', $seo_title);
            $saved_fields[] = 'seo_title';
            
            error_log("DODO: SEO title yoktu, focus keyword'den oluşturuldu: {$seo_title}");
        }
        
        // Meta Description - Focus keyword içermeli
        if (!empty($seo_data['meta_description']) && is_string($seo_data['meta_description'])) {
            $meta_description = sanitize_textarea_field($seo_data['meta_description']);
            
            // Focus keyword meta description'da var mı kontrol et
            if (!empty($focus_keyword) && stripos($meta_description, $focus_keyword) === false) {
                // Focus keyword yoksa başa ekle
                $meta_description = $focus_keyword . ' hakkında detaylı bilgi. ' . $meta_description;
                
                // Uzunluk kontrolü (max 160 karakter)
                if (mb_strlen($meta_description) > 160) {
                    // Çok uzunsa kısalt
                    $meta_description = mb_substr($meta_description, 0, 157) . '...';
                }
                
                error_log("DODO: Meta description focus keyword içermiyordu, eklendi");
            }
            
            // Uzunluk kontrolü (150-160 karakter ideal)
            $desc_length = mb_strlen($meta_description);
            if ($desc_length > 160) {
                $meta_description = mb_substr($meta_description, 0, 157) . '...';
                error_log("DODO: Meta description çok uzundu, kısaltıldı");
            }
            
            // Boş değilse kaydet
            if (!empty($meta_description)) {
                update_post_meta($post_id, 'rank_math_description', $meta_description);
                $saved_fields[] = 'meta_description';
                
                error_log("DODO: Meta description kaydedildi: " . mb_substr($meta_description, 0, 50) . "...");
            }
        } elseif (!empty($focus_keyword)) {
            // Meta description yoksa focus keyword'den oluştur
            $post = get_post($post_id);
            $meta_description = $focus_keyword . ' hakkında detaylı bilgi ve rehber. ' . wp_trim_words(strip_tags($post->post_content), 15);
            
            if (mb_strlen($meta_description) > 160) {
                $meta_description = mb_substr($meta_description, 0, 157) . '...';
            }
            
            update_post_meta($post_id, 'rank_math_description', $meta_description);
            $saved_fields[] = 'meta_description';
            
            error_log("DODO: Meta description yoktu, focus keyword'den oluşturuldu");
        }
        
        // Robots Meta - Rank Math'in beklediği format
        update_post_meta($post_id, 'rank_math_robots', array('index', 'follow'));
        
        // Advanced Robots
        update_post_meta($post_id, 'rank_math_advanced_robots', array(
            'max-snippet:-1',
            'max-video-preview:-1',
            'max-image-preview:large',
        ));
        
        // Kaydedilen değerleri verify et
        if (!empty($focus_keyword)) {
            $saved_focus = get_post_meta($post_id, 'rank_math_focus_keyword', true);
            $saved_focus_array = get_post_meta($post_id, 'rank_math_focus_keywords', true);
            $saved_title = get_post_meta($post_id, 'rank_math_title', true);
            $saved_desc = get_post_meta($post_id, 'rank_math_description', true);
            
            error_log("DODO: Rank Math meta verify - Focus: {$saved_focus}, Focus Array: " . print_r($saved_focus_array, true) . ", Title: {$saved_title}, Desc: " . mb_substr($saved_desc, 0, 50));
        }
        
        return !empty($saved_fields);
    }
    
    /**
     * Bozuk/hatalı Rank Math meta değerlerini temizle
     * 
     * @param int $post_id
     */
    private function clean_broken_meta($post_id) {
        // SEO score ve Content AI score'u kesinlikle siliyoruz
        // Bunlar Rank Math tarafından otomatik hesaplanmalı
        delete_post_meta($post_id, 'rank_math_seo_score');
        delete_post_meta($post_id, 'rank_math_contentai_score');
        
        // NOT: rank_math_focus_keywords'ü SİLMİYORUZ
        // Çünkü Rank Math panelinde görünmesi için gerekli
        
        // Diğer potansiyel bozuk meta değerleri
        delete_post_meta($post_id, 'rank_math_primary_focus_keyword');
    }
    
    /**
     * Bozuk meta değerlerini temizle (public - admin tarafından kullanılır)
     * 
     * @param int $post_id
     */
    public function clean_post_meta($post_id) {
        $this->clean_broken_meta($post_id);
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
            'type' => 'rankmath_' . $error_type,
            'data' => $error_data,
        );
        
        // Son 20 hatayı sakla
        $error_logs = get_option('dodo_error_logs', array());
        array_unshift($error_logs, $log_entry);
        $error_logs = array_slice($error_logs, 0, 20);
        
        update_option('dodo_error_logs', $error_logs, false);
        
        // WordPress error log'a da yaz
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('DODO AI SEO - Rank Math Error [' . $error_type . ']: ' . print_r($error_data, true));
        }
    }
    
    /**
     * Schema markup ekle - KAPALI
     * 
     * Rank Math panelini bozduğu için schema artık Rank Math meta'larına yazılmıyor.
     * Bunun yerine _dodo_ai_schema_json_ld olarak kaydediliyor.
     * 
     * @param int $post_id
     * @param string $schema_type
     * @return bool
     */
    public function add_schema_markup($post_id, $schema_type = 'article') {
        // KAPALI - Rank Math panelini bozuyor
        // Schema artık Rank Math meta'larına yazılmıyor
        return false;
        
        /*
        if (!class_exists('RankMath')) {
            return false;
        }
        
        // Article schema
        $schema = array(
            '@type' => 'Article',
            'headline' => get_the_title($post_id),
            'description' => get_the_excerpt($post_id),
            'datePublished' => get_the_date('c', $post_id),
            'dateModified' => get_the_modified_date('c', $post_id),
        );
        
        update_post_meta($post_id, 'rank_math_schema_Article', $schema);
        
        return true;
        */
    }
    
    /**
     * FAQ Schema ekle - KAPALI
     * 
     * Rank Math panelini bozduğu için FAQ artık Rank Math meta'larına yazılmıyor.
     * FAQ içeriği yazı içinde HTML olarak kalıyor.
     * JSON-LD frontend'de _dodo_ai_schema_json_ld'den basılıyor.
     * 
     * @param int $post_id
     * @param array $faqs Soru-cevap dizisi
     * @return bool
     */
    public function add_faq_schema($post_id, $faqs) {
        // KAPALI - Rank Math panelini bozuyor
        // FAQ artık Rank Math meta'larına yazılmıyor
        return false;
        
        /*
        if (!class_exists('RankMath') || empty($faqs)) {
            return false;
        }
        
        $faq_schema = array();
        
        foreach ($faqs as $faq) {
            if (!empty($faq['question']) && !empty($faq['answer'])) {
                $faq_schema[] = array(
                    '@type' => 'Question',
                    'name' => $faq['question'],
                    'acceptedAnswer' => array(
                        '@type' => 'Answer',
                        'text' => $faq['answer'],
                    ),
                );
            }
        }
        
        if (!empty($faq_schema)) {
            update_post_meta($post_id, 'rank_math_schema_FAQPage', array(
                '@type' => 'FAQPage',
                'mainEntity' => $faq_schema,
            ));
        }
        
        return true;
        */
    }
    
    /**
     * DODO Schema JSON-LD oluştur ve kaydet
     * 
     * Rank Math meta'larına değil, _dodo_ai_schema_json_ld olarak kaydeder.
     * 
     * @param int $post_id
     * @param array $schema_data
     * @return bool
     */
    public function save_dodo_schema($post_id, $schema_data) {
        if (empty($schema_data)) {
            return false;
        }
        
        // Schema JSON oluştur
        $json_ld = array(
            '@context' => 'https://schema.org',
        );
        
        // Article schema
        if (!empty($schema_data['type']) && $schema_data['type'] === 'article') {
            $post = get_post($post_id);
            
            $json_ld['@type'] = 'Article';
            $json_ld['headline'] = get_the_title($post_id);
            $json_ld['description'] = get_the_excerpt($post_id);
            $json_ld['datePublished'] = get_the_date('c', $post_id);
            $json_ld['dateModified'] = get_the_modified_date('c', $post_id);
            $json_ld['author'] = array(
                '@type' => 'Person',
                'name' => get_the_author_meta('display_name', $post->post_author),
            );
        }
        
        // FAQ schema
        if (!empty($schema_data['faqs']) && is_array($schema_data['faqs'])) {
            $faq_items = array();
            
            foreach ($schema_data['faqs'] as $faq) {
                if (!empty($faq['question']) && !empty($faq['answer'])) {
                    // HTML taglarını temizle - sadece düz metin
                    $clean_question = html_entity_decode(wp_strip_all_tags(trim($faq['question'])));
                    $clean_answer = html_entity_decode(wp_strip_all_tags(trim($faq['answer'])));
                    
                    if (!empty($clean_question) && !empty($clean_answer)) {
                        $faq_items[] = array(
                            '@type' => 'Question',
                            'name' => $clean_question,
                            'acceptedAnswer' => array(
                                '@type' => 'Answer',
                                'text' => $clean_answer,
                            ),
                        );
                    }
                }
            }
            
            if (!empty($faq_items)) {
                $json_ld['@type'] = 'FAQPage';
                $json_ld['mainEntity'] = $faq_items;
            }
        }
        
        // JSON'a çevir
        $json_string = wp_json_encode($json_ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        
        // Validate et
        $test_decode = json_decode($json_string, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->log_error('schema_json_invalid', array(
                'post_id' => $post_id,
                'error' => json_last_error_msg(),
            ));
            return false;
        }
        
        // String olarak kaydet
        update_post_meta($post_id, '_dodo_ai_schema_json_ld', $json_string);
        
        return true;
    }
    
    /**
     * SEO skorunu hesapla (basit versiyon)
     * 
     * @param int $post_id
     * @return int 0-100 arası skor
     */
    public function calculate_seo_score($post_id) {
        $score = 0;
        
        // Focus keyword var mı?
        $focus_keyword = get_post_meta($post_id, 'rank_math_focus_keyword', true);
        if (!empty($focus_keyword)) {
            $score += 20;
        }
        
        // SEO title var mı?
        $seo_title = get_post_meta($post_id, 'rank_math_title', true);
        if (!empty($seo_title) && strlen($seo_title) >= 50 && strlen($seo_title) <= 60) {
            $score += 20;
        }
        
        // Meta description var mı?
        $meta_desc = get_post_meta($post_id, 'rank_math_description', true);
        if (!empty($meta_desc) && strlen($meta_desc) >= 150 && strlen($meta_desc) <= 160) {
            $score += 20;
        }
        
        // İçerik uzunluğu
        $post = get_post($post_id);
        $word_count = str_word_count(strip_tags($post->post_content));
        if ($word_count >= 3000) {
            $score += 20;
        } elseif ($word_count >= 1500) {
            $score += 10;
        }
        
        // İç link var mı?
        $internal_links = $this->count_internal_links($post->post_content);
        if ($internal_links >= 5) {
            $score += 20;
        } elseif ($internal_links >= 3) {
            $score += 10;
        }
        
        return min($score, 100);
    }
    
    /**
     * İçerikteki iç link sayısını say
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
     * Rank Math ayarlarını al
     * 
     * @param int $post_id
     * @return array
     */
    public function get_seo_meta($post_id) {
        return array(
            'focus_keyword' => get_post_meta($post_id, 'rank_math_focus_keyword', true),
            'seo_title' => get_post_meta($post_id, 'rank_math_title', true),
            'meta_description' => get_post_meta($post_id, 'rank_math_description', true),
            'seo_score' => $this->calculate_seo_score($post_id),
        );
    }
    
    /**
     * Rank Math'in yüklü olup olmadığını kontrol et
     * 
     * @return bool
     */
    public function is_rankmath_active() {
        return class_exists('RankMath');
    }
}
