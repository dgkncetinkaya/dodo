<?php
/**
 * Conversion Signal Engine
 * 
 * Estimates conversion potential and detects conversion signals
 * Sprint E - Feedback Loop & Self-Learning SEO System
 * 
 * @package DODO_AI_SEO
 * @since 3.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Conversion_Engine {
    
    /**
     * Estimate conversion signals for content
     * 
     * @param int $post_id
     * @return array Conversion analysis
     */
    public function estimate_conversion_signals($post_id) {
        $signals = array(
            'post_id' => $post_id,
            'conversion_score' => 0,
            'buyer_intent' => 'low',
            'cta_quality' => 0,
            'commercial_structure' => 0,
            'transactional_relevance' => 0,
            'money_page_support' => 0,
            'signals_detected' => array(),
            'gaps' => array(),
        );
        
        try {
            $post = get_post($post_id);
            
            if (!$post) {
                return $signals;
            }
            
            // 1. Analyze buyer intent
            $signals['buyer_intent'] = $this->analyze_buyer_intent($post);
            
            // 2. Assess CTA quality
            $signals['cta_quality'] = $this->assess_cta_quality($post);
            
            // 3. Evaluate commercial structure
            $signals['commercial_structure'] = $this->evaluate_commercial_structure($post);
            
            // 4. Check transactional relevance
            $signals['transactional_relevance'] = $this->check_transactional_relevance($post);
            
            // 5. Assess money page support
            $signals['money_page_support'] = $this->assess_money_page_support($post);
            
            // 6. Detect specific signals
            $signals['signals_detected'] = $this->detect_conversion_signals($post);
            
            // 7. Detect conversion gaps
            $signals['gaps'] = $this->detect_conversion_gaps($post, $signals);
            
            // 8. Calculate overall conversion score
            $signals['conversion_score'] = $this->calculate_conversion_score($signals);
            
            // Save results
            $this->save_conversion_signals($post_id, $signals);
            
        } catch (Throwable $e) {
            error_log('[DODO][Conversion Engine] Error estimating signals: ' . $e->getMessage());
        }
        
        return $signals;
    }
    
    /**
     * Analyze buyer intent
     * 
     * @param WP_Post $post
     * @return string Intent level: 'high', 'medium', 'low'
     */
    private function analyze_buyer_intent($post) {
        $intent = 'low';
        
        try {
            $title = mb_strtolower($post->post_title, 'UTF-8');
            $content = mb_strtolower($post->post_content, 'UTF-8');
            
            // High intent keywords
            $high_intent = array(
                'satın al', 'fiyat', 'indirim', 'kampanya', 'teklif',
                'buy', 'price', 'discount', 'deal', 'offer',
                'en iyi', 'karşılaştırma', 'vs', 'alternatif',
                'inceleme', 'review', 'tavsiye', 'öneri',
            );
            
            // Medium intent keywords
            $medium_intent = array(
                'nasıl', 'rehber', 'guide', 'öğren', 'learn',
                'kullanım', 'kullan', 'use', 'yöntem', 'method',
            );
            
            $high_count = 0;
            $medium_count = 0;
            
            foreach ($high_intent as $keyword) {
                if (strpos($title, $keyword) !== false) {
                    $high_count += 3; // Title has more weight
                }
                if (strpos($content, $keyword) !== false) {
                    $high_count++;
                }
            }
            
            foreach ($medium_intent as $keyword) {
                if (strpos($title, $keyword) !== false) {
                    $medium_count += 2;
                }
                if (strpos($content, $keyword) !== false) {
                    $medium_count++;
                }
            }
            
            if ($high_count >= 5) {
                $intent = 'high';
            } elseif ($high_count >= 2 || $medium_count >= 5) {
                $intent = 'medium';
            }
            
        } catch (Throwable $e) {
            error_log('[DODO][Conversion Engine] Error analyzing intent: ' . $e->getMessage());
        }
        
        return $intent;
    }
    
    /**
     * Assess CTA quality (0-100)
     * 
     * @param WP_Post $post
     * @return int CTA quality score
     */
    private function assess_cta_quality($post) {
        $quality = 0;
        
        try {
            $content = $post->post_content;
            
            // CTA keywords
            $cta_keywords = array(
                'başla', 'dene', 'ücretsiz', 'indir', 'kayıt ol',
                'start', 'try', 'free', 'download', 'sign up',
                'tıkla', 'click', 'öğren', 'learn', 'keşfet', 'discover',
            );
            
            $cta_count = 0;
            
            foreach ($cta_keywords as $keyword) {
                $cta_count += substr_count(mb_strtolower($content, 'UTF-8'), $keyword);
            }
            
            // CTA presence (40 points)
            if ($cta_count >= 5) {
                $quality += 40;
            } elseif ($cta_count >= 3) {
                $quality += 30;
            } elseif ($cta_count >= 1) {
                $quality += 20;
            }
            
            // Button presence (30 points)
            $button_count = substr_count($content, 'wp:button') + substr_count($content, '<button');
            
            if ($button_count >= 3) {
                $quality += 30;
            } elseif ($button_count >= 2) {
                $quality += 20;
            } elseif ($button_count >= 1) {
                $quality += 15;
            }
            
            // Link presence (20 points)
            $link_count = substr_count($content, '<a href');
            
            if ($link_count >= 10) {
                $quality += 20;
            } elseif ($link_count >= 5) {
                $quality += 15;
            } elseif ($link_count >= 3) {
                $quality += 10;
            }
            
            // Urgency words (10 points)
            $urgency_words = array('şimdi', 'bugün', 'hemen', 'sınırlı', 'son', 'now', 'today', 'limited');
            $urgency_count = 0;
            
            foreach ($urgency_words as $word) {
                if (stripos($content, $word) !== false) {
                    $urgency_count++;
                }
            }
            
            if ($urgency_count >= 3) {
                $quality += 10;
            } elseif ($urgency_count >= 1) {
                $quality += 5;
            }
            
        } catch (Throwable $e) {
            error_log('[DODO][Conversion Engine] Error assessing CTA: ' . $e->getMessage());
        }
        
        return min(100, $quality);
    }
    
    /**
     * Evaluate commercial structure (0-100)
     * 
     * @param WP_Post $post
     * @return int Commercial structure score
     */
    private function evaluate_commercial_structure($post) {
        $score = 0;
        
        try {
            $content = $post->post_content;
            
            // Pricing information (30 points)
            $has_price = preg_match('/(\$|€|₺|TL)\s*\d+|fiyat|price/i', $content);
            
            if ($has_price) {
                $score += 30;
            }
            
            // Comparison/table (25 points)
            $has_table = strpos($content, '<table') !== false;
            
            if ($has_table) {
                $score += 25;
            }
            
            // Pros/cons (20 points)
            $has_pros_cons = preg_match('/(avantaj|dezavantaj|artı|eksi|pros|cons)/i', $content);
            
            if ($has_pros_cons) {
                $score += 20;
            }
            
            // Features list (15 points)
            $has_features = preg_match('/(özellik|feature|specification)/i', $content);
            
            if ($has_features) {
                $score += 15;
            }
            
            // Rating/review (10 points)
            $has_rating = preg_match('/(puan|rating|yıldız|star|değerlendirme)/i', $content);
            
            if ($has_rating) {
                $score += 10;
            }
            
        } catch (Throwable $e) {
            error_log('[DODO][Conversion Engine] Error evaluating structure: ' . $e->getMessage());
        }
        
        return min(100, $score);
    }
    
    /**
     * Check transactional relevance (0-100)
     * 
     * @param WP_Post $post
     * @return int Transactional relevance score
     */
    private function check_transactional_relevance($post) {
        $score = 0;
        
        try {
            $title = mb_strtolower($post->post_title, 'UTF-8');
            $content = mb_strtolower($post->post_content, 'UTF-8');
            
            // Transactional keywords in title (40 points)
            $transactional_title = array(
                'satın al', 'buy', 'fiyat', 'price', 'indirim', 'discount',
                'en iyi', 'best', 'karşılaştırma', 'comparison', 'vs',
            );
            
            foreach ($transactional_title as $keyword) {
                if (strpos($title, $keyword) !== false) {
                    $score += 40;
                    break;
                }
            }
            
            // Product/service mentions (30 points)
            $product_mentions = preg_match_all('/(ürün|product|hizmet|service|araç|tool)/i', $content);
            
            if ($product_mentions >= 10) {
                $score += 30;
            } elseif ($product_mentions >= 5) {
                $score += 20;
            } elseif ($product_mentions >= 2) {
                $score += 10;
            }
            
            // Commercial intent words (20 points)
            $commercial_words = array('satın', 'al', 'buy', 'purchase', 'order', 'sipariş');
            $commercial_count = 0;
            
            foreach ($commercial_words as $word) {
                $commercial_count += substr_count($content, $word);
            }
            
            if ($commercial_count >= 5) {
                $score += 20;
            } elseif ($commercial_count >= 3) {
                $score += 15;
            } elseif ($commercial_count >= 1) {
                $score += 10;
            }
            
            // Affiliate links (10 points)
            $has_affiliate = preg_match('/(affiliate|ref=|aff=|track=)/i', $content);
            
            if ($has_affiliate) {
                $score += 10;
            }
            
        } catch (Throwable $e) {
            error_log('[DODO][Conversion Engine] Error checking relevance: ' . $e->getMessage());
        }
        
        return min(100, $score);
    }
    
    /**
     * Assess money page support (0-100)
     * 
     * @param WP_Post $post
     * @return int Money page support score
     */
    private function assess_money_page_support($post) {
        $score = 0;
        
        try {
            $content = $post->post_content;
            
            // Internal links to product/service pages (40 points)
            $site_url = get_site_url();
            $internal_links = substr_count($content, $site_url);
            
            if ($internal_links >= 5) {
                $score += 40;
            } elseif ($internal_links >= 3) {
                $score += 30;
            } elseif ($internal_links >= 1) {
                $score += 20;
            }
            
            // Links to money keywords (30 points)
            $money_keywords = array('satın', 'buy', 'fiyat', 'price', 'ürün', 'product');
            $money_link_count = 0;
            
            foreach ($money_keywords as $keyword) {
                $money_link_count += preg_match_all('/<a[^>]*>' . $keyword . '/i', $content);
            }
            
            if ($money_link_count >= 3) {
                $score += 30;
            } elseif ($money_link_count >= 2) {
                $score += 20;
            } elseif ($money_link_count >= 1) {
                $score += 15;
            }
            
            // Related products/services section (20 points)
            $has_related = preg_match('/(ilgili ürün|related product|benzer|similar)/i', $content);
            
            if ($has_related) {
                $score += 20;
            }
            
            // Next steps/action section (10 points)
            $has_next_steps = preg_match('/(sonraki adım|next step|nasıl başla|how to start)/i', $content);
            
            if ($has_next_steps) {
                $score += 10;
            }
            
        } catch (Throwable $e) {
            error_log('[DODO][Conversion Engine] Error assessing support: ' . $e->getMessage());
        }
        
        return min(100, $score);
    }
    
    /**
     * Detect specific conversion signals
     * 
     * @param WP_Post $post
     * @return array Detected signals
     */
    private function detect_conversion_signals($post) {
        $signals = array();
        
        try {
            $content = $post->post_content;
            $title = $post->post_title;
            
            // Price mention
            if (preg_match('/(\$|€|₺|TL)\s*\d+|fiyat|price/i', $content)) {
                $signals[] = array(
                    'signal' => 'Fiyat bilgisi var',
                    'strength' => 'high',
                );
            }
            
            // CTA buttons
            $button_count = substr_count($content, 'wp:button') + substr_count($content, '<button');
            if ($button_count > 0) {
                $signals[] = array(
                    'signal' => 'CTA butonları var (' . $button_count . ')',
                    'strength' => 'high',
                );
            }
            
            // Comparison table
            if (strpos($content, '<table') !== false) {
                $signals[] = array(
                    'signal' => 'Karşılaştırma tablosu var',
                    'strength' => 'medium',
                );
            }
            
            // Urgency
            if (preg_match('/(şimdi|bugün|hemen|sınırlı|now|today|limited)/i', $content)) {
                $signals[] = array(
                    'signal' => 'Aciliyet ifadeleri var',
                    'strength' => 'medium',
                );
            }
            
            // Social proof
            if (preg_match('/(kullanıcı|müşteri|customer|user|testimonial|yorum)/i', $content)) {
                $signals[] = array(
                    'signal' => 'Sosyal kanıt var',
                    'strength' => 'medium',
                );
            }
            
            // Guarantee
            if (preg_match('/(garanti|guarantee|iade|refund)/i', $content)) {
                $signals[] = array(
                    'signal' => 'Garanti/iade politikası var',
                    'strength' => 'low',
                );
            }
            
        } catch (Throwable $e) {
            error_log('[DODO][Conversion Engine] Error detecting signals: ' . $e->getMessage());
        }
        
        return $signals;
    }
    
    /**
     * Detect conversion gaps
     * 
     * @param WP_Post $post
     * @param array $signals
     * @return array Detected gaps
     */
    public function detect_conversion_gaps($post, $signals) {
        $gaps = array();
        
        try {
            // Low CTA quality
            if ($signals['cta_quality'] < 50) {
                $gaps[] = array(
                    'gap' => 'CTA kalitesi düşük',
                    'severity' => 'high',
                    'recommendation' => 'Daha fazla CTA butonu ve net aksiyon çağrısı ekleyin',
                );
            }
            
            // Low commercial structure
            if ($signals['commercial_structure'] < 40) {
                $gaps[] = array(
                    'gap' => 'Ticari yapı zayıf',
                    'severity' => 'high',
                    'recommendation' => 'Fiyat bilgisi, karşılaştırma tablosu ve özellik listesi ekleyin',
                );
            }
            
            // Low money page support
            if ($signals['money_page_support'] < 30) {
                $gaps[] = array(
                    'gap' => 'Para sayfası desteği eksik',
                    'severity' => 'medium',
                    'recommendation' => 'Ürün/hizmet sayfalarına iç linkler ekleyin',
                );
            }
            
            // No pricing
            $content = $post->post_content;
            if (!preg_match('/(\$|€|₺|TL)\s*\d+|fiyat|price/i', $content)) {
                $gaps[] = array(
                    'gap' => 'Fiyat bilgisi yok',
                    'severity' => 'medium',
                    'recommendation' => 'Fiyat aralığı veya maliyet bilgisi ekleyin',
                );
            }
            
            // No urgency
            if (!preg_match('/(şimdi|bugün|hemen|sınırlı|now|today|limited)/i', $content)) {
                $gaps[] = array(
                    'gap' => 'Aciliyet ifadesi yok',
                    'severity' => 'low',
                    'recommendation' => 'Zaman sınırlı teklifler veya aciliyet ifadeleri ekleyin',
                );
            }
            
        } catch (Throwable $e) {
            error_log('[DODO][Conversion Engine] Error detecting gaps: ' . $e->getMessage());
        }
        
        return $gaps;
    }
    
    /**
     * Calculate overall conversion score (0-100)
     * 
     * @param array $signals
     * @return int Conversion score
     */
    private function calculate_conversion_score($signals) {
        $score = 0;
        
        // Buyer intent (25 points)
        switch ($signals['buyer_intent']) {
            case 'high':
                $score += 25;
                break;
            case 'medium':
                $score += 15;
                break;
            case 'low':
                $score += 5;
                break;
        }
        
        // CTA quality (25 points)
        $score += ($signals['cta_quality'] / 100) * 25;
        
        // Commercial structure (20 points)
        $score += ($signals['commercial_structure'] / 100) * 20;
        
        // Transactional relevance (20 points)
        $score += ($signals['transactional_relevance'] / 100) * 20;
        
        // Money page support (10 points)
        $score += ($signals['money_page_support'] / 100) * 10;
        
        return min(100, round($score));
    }
    
    /**
     * Save conversion signals
     * 
     * @param int $post_id
     * @param array $signals
     */
    private function save_conversion_signals($post_id, $signals) {
        update_post_meta($post_id, '_dodo_conversion_score', $signals['conversion_score']);
        update_post_meta($post_id, '_dodo_buyer_intent', $signals['buyer_intent']);
        update_post_meta($post_id, '_dodo_cta_quality', $signals['cta_quality']);
        update_post_meta($post_id, '_dodo_conversion_signals', $signals);
        update_post_meta($post_id, '_dodo_conversion_analyzed_at', current_time('mysql'));
    }
    
    /**
     * Get conversion statistics
     * 
     * @return array Statistics
     */
    public function get_statistics() {
        $stats = array(
            'total_analyzed' => 0,
            'high_conversion' => 0, // >= 70
            'medium_conversion' => 0, // 40-69
            'low_conversion' => 0, // < 40
            'high_intent' => 0,
            'medium_intent' => 0,
            'low_intent' => 0,
            'avg_conversion_score' => 0,
            'avg_cta_quality' => 0,
        );
        
        try {
            global $wpdb;
            
            // Get all analyzed posts
            $results = $wpdb->get_results(
                "SELECT post_id, meta_value as score 
                FROM {$wpdb->postmeta} 
                WHERE meta_key = '_dodo_conversion_score'"
            );
            
            $scores = array();
            $cta_scores = array();
            
            foreach ($results as $row) {
                $score = intval($row->meta_value);
                $scores[] = $score;
                
                $stats['total_analyzed']++;
                
                if ($score >= 70) {
                    $stats['high_conversion']++;
                } elseif ($score >= 40) {
                    $stats['medium_conversion']++;
                } else {
                    $stats['low_conversion']++;
                }
                
                // Intent stats
                $intent = get_post_meta($row->post_id, '_dodo_buyer_intent', true);
                
                switch ($intent) {
                    case 'high':
                        $stats['high_intent']++;
                        break;
                    case 'medium':
                        $stats['medium_intent']++;
                        break;
                    case 'low':
                        $stats['low_intent']++;
                        break;
                }
                
                // CTA quality
                $cta = get_post_meta($row->post_id, '_dodo_cta_quality', true);
                if ($cta) {
                    $cta_scores[] = intval($cta);
                }
            }
            
            if (!empty($scores)) {
                $stats['avg_conversion_score'] = round(array_sum($scores) / count($scores));
            }
            
            if (!empty($cta_scores)) {
                $stats['avg_cta_quality'] = round(array_sum($cta_scores) / count($cta_scores));
            }
            
        } catch (Throwable $e) {
            error_log('[DODO][Conversion Engine] Error getting statistics: ' . $e->getMessage());
        }
        
        return $stats;
    }
    
    /**
     * Get posts by conversion score range
     * 
     * @param int $min_score
     * @param int $max_score
     * @return array Post IDs
     */
    public function get_posts_by_score_range($min_score = 0, $max_score = 100) {
        global $wpdb;
        
        $post_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT post_id 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_dodo_conversion_score' 
            AND CAST(meta_value AS UNSIGNED) >= %d 
            AND CAST(meta_value AS UNSIGNED) <= %d
            ORDER BY CAST(meta_value AS UNSIGNED) DESC",
            $min_score,
            $max_score
        ));
        
        return array_map('intval', $post_ids);
    }
}
