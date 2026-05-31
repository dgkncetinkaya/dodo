<?php
/**
 * SERP Intelligence Engine
 * 
 * Analyzes SERP features and patterns to guide content strategy
 * Phase 3 - SEO Strategist Intelligence
 * 
 * @package DODO_AI_SEO
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_SERP_Intelligence_Engine {
    
    /**
     * Detect SERP features from query patterns
     * 
     * @param string $query Query string
     * @param array $metrics GSC metrics
     * @return array SERP analysis
     */
    public function analyze_serp_features($query, $metrics = array()) {
        $query_lower = strtolower($query);
        $features = array();
        $recommendations = array();
        
        // 1. Featured Snippet Detection
        if ($this->likely_has_featured_snippet($query_lower)) {
            $features[] = 'featured_snippet';
            $recommendations[] = array(
                'feature' => 'featured_snippet',
                'action' => 'Kısa, net cevap paragrafı ekle (40-60 kelime)',
                'priority' => 'high',
            );
        }
        
        // 2. FAQ Detection
        if ($this->likely_has_faq($query_lower)) {
            $features[] = 'faq';
            $recommendations[] = array(
                'feature' => 'faq',
                'action' => 'FAQ schema markup ekle, soru-cevap formatı kullan',
                'priority' => 'high',
            );
        }
        
        // 3. Video Dominance
        if ($this->likely_video_dominant($query_lower)) {
            $features[] = 'video';
            $recommendations[] = array(
                'feature' => 'video',
                'action' => 'Video embed et veya video transcript ekle',
                'priority' => 'medium',
            );
        }
        
        // 4. Image Pack
        if ($this->likely_has_image_pack($query_lower)) {
            $features[] = 'image_pack';
            $recommendations[] = array(
                'feature' => 'image_pack',
                'action' => 'Yüksek kalite görseller ekle, alt text optimize et',
                'priority' => 'medium',
            );
        }
        
        // 5. Local Pack
        if ($this->likely_has_local_pack($query_lower)) {
            $features[] = 'local_pack';
            $recommendations[] = array(
                'feature' => 'local_pack',
                'action' => 'Lokasyon bilgisi ekle, Google My Business optimize et',
                'priority' => 'high',
            );
        }
        
        // 6. Shopping Results
        if ($this->likely_has_shopping($query_lower)) {
            $features[] = 'shopping';
            $recommendations[] = array(
                'feature' => 'shopping',
                'action' => 'Product schema ekle, fiyat bilgisi göster',
                'priority' => 'high',
            );
        }
        
        // 7. People Also Ask
        if ($this->likely_has_paa($query_lower)) {
            $features[] = 'people_also_ask';
            $recommendations[] = array(
                'feature' => 'people_also_ask',
                'action' => 'İlgili soruları içeriğe ekle, detaylı cevaplar ver',
                'priority' => 'medium',
            );
        }
        
        // 8. Knowledge Panel
        if ($this->likely_has_knowledge_panel($query_lower)) {
            $features[] = 'knowledge_panel';
            $recommendations[] = array(
                'feature' => 'knowledge_panel',
                'action' => 'Entity markup ekle, Wikipedia-style bilgi ver',
                'priority' => 'low',
            );
        }
        
        // Calculate SERP complexity
        $complexity = count($features);
        $difficulty = 'easy';
        
        if ($complexity >= 4) {
            $difficulty = 'hard';
        } elseif ($complexity >= 2) {
            $difficulty = 'medium';
        }
        
        return array(
            'query' => $query,
            'detected_features' => $features,
            'feature_count' => $complexity,
            'difficulty' => $difficulty,
            'recommendations' => $recommendations,
            'content_type' => $this->suggest_content_type($query_lower, $features),
        );
    }
    
    /**
     * Check if query likely has featured snippet
     */
    private function likely_has_featured_snippet($query) {
        $snippet_patterns = array(
            'nedir', 'what is', 'ne demek', 'meaning',
            'nasıl', 'how to', 'how do',
            'neden', 'why', 'when', 'ne zaman',
        );
        
        foreach ($snippet_patterns as $pattern) {
            if (strpos($query, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if query likely has FAQ
     */
    private function likely_has_faq($query) {
        $faq_patterns = array(
            'sık sorulan', 'faq', 'sorular',
            'frequently asked', 'questions',
        );
        
        foreach ($faq_patterns as $pattern) {
            if (strpos($query, $pattern) !== false) {
                return true;
            }
        }
        
        // Questions often trigger FAQ
        if (preg_match('/\?/', $query)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if query likely video dominant
     */
    private function likely_video_dominant($query) {
        $video_patterns = array(
            'video', 'izle', 'watch', 'tutorial',
            'nasıl yapılır', 'how to make',
        );
        
        foreach ($video_patterns as $pattern) {
            if (strpos($query, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if query likely has image pack
     */
    private function likely_has_image_pack($query) {
        $image_patterns = array(
            'resim', 'image', 'photo', 'fotoğraf',
            'görsel', 'picture', 'galeri', 'gallery',
        );
        
        foreach ($image_patterns as $pattern) {
            if (strpos($query, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if query likely has local pack
     */
    private function likely_has_local_pack($query) {
        $local_patterns = array(
            'yakın', 'near me', 'nearby', 'civar',
            'nerede', 'where', 'adres', 'address',
            'açık', 'open', 'saatler', 'hours',
        );
        
        foreach ($local_patterns as $pattern) {
            if (strpos($query, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if query likely has shopping results
     */
    private function likely_has_shopping($query) {
        $shopping_patterns = array(
            'satın al', 'buy', 'fiyat', 'price',
            'ucuz', 'cheap', 'indirim', 'discount',
            'ürün', 'product',
        );
        
        foreach ($shopping_patterns as $pattern) {
            if (strpos($query, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if query likely has People Also Ask
     */
    private function likely_has_paa($query) {
        // Most informational queries have PAA
        $info_patterns = array(
            'nedir', 'what', 'nasıl', 'how',
            'neden', 'why', 'ne zaman', 'when',
        );
        
        foreach ($info_patterns as $pattern) {
            if (strpos($query, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if query likely has knowledge panel
     */
    private function likely_has_knowledge_panel($query) {
        // Entity queries (brands, people, places)
        // Simple heuristic: capitalized words
        $words = explode(' ', $query);
        $capitalized = 0;
        
        foreach ($words as $word) {
            if (ucfirst($word) === $word && strlen($word) > 2) {
                $capitalized++;
            }
        }
        
        return $capitalized >= 1;
    }
    
    /**
     * Suggest content type based on query and SERP features
     */
    private function suggest_content_type($query, $features) {
        // Video dominant
        if (in_array('video', $features)) {
            return 'video_tutorial';
        }
        
        // Shopping
        if (in_array('shopping', $features)) {
            return 'product_page';
        }
        
        // Local
        if (in_array('local_pack', $features)) {
            return 'local_business_page';
        }
        
        // FAQ heavy
        if (in_array('faq', $features) || in_array('people_also_ask', $features)) {
            return 'faq_article';
        }
        
        // How-to
        if (strpos($query, 'nasıl') !== false || strpos($query, 'how') !== false) {
            return 'how_to_guide';
        }
        
        // What is
        if (strpos($query, 'nedir') !== false || strpos($query, 'what is') !== false) {
            return 'definition_article';
        }
        
        // Best/comparison
        if (strpos($query, 'en iyi') !== false || strpos($query, 'best') !== false) {
            return 'listicle';
        }
        
        // Default
        return 'informational_article';
    }
    
    /**
     * Analyze SERP intent from position and CTR
     */
    public function analyze_serp_intent($query, $position, $ctr) {
        $intent = array(
            'query' => $query,
            'position' => $position,
            'ctr' => $ctr,
            'expected_ctr' => $this->get_expected_ctr($position),
            'ctr_performance' => 'normal',
            'intent_signals' => array(),
        );
        
        $expected_ctr = $intent['expected_ctr'];
        
        // CTR performance
        if ($ctr > $expected_ctr * 1.5) {
            $intent['ctr_performance'] = 'excellent';
            $intent['intent_signals'][] = 'Strong user interest - title/description very compelling';
        } elseif ($ctr > $expected_ctr * 1.2) {
            $intent['ctr_performance'] = 'good';
            $intent['intent_signals'][] = 'Above average interest';
        } elseif ($ctr < $expected_ctr * 0.5) {
            $intent['ctr_performance'] = 'poor';
            $intent['intent_signals'][] = 'Low interest - title/description needs improvement';
        } elseif ($ctr < $expected_ctr * 0.8) {
            $intent['ctr_performance'] = 'below_average';
            $intent['intent_signals'][] = 'Below average interest';
        }
        
        return $intent;
    }
    
    /**
     * Get expected CTR by position
     */
    private function get_expected_ctr($position) {
        // Industry average CTR by position
        $ctr_by_position = array(
            1 => 0.28,
            2 => 0.15,
            3 => 0.11,
            4 => 0.08,
            5 => 0.07,
            6 => 0.06,
            7 => 0.05,
            8 => 0.04,
            9 => 0.03,
            10 => 0.03,
        );
        
        $pos = round($position);
        
        if (isset($ctr_by_position[$pos])) {
            return $ctr_by_position[$pos];
        }
        
        // Beyond position 10
        if ($pos <= 20) {
            return 0.02;
        }
        
        return 0.01;
    }
}
