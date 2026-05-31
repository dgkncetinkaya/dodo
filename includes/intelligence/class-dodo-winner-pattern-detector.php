<?php
/**
 * Winner Pattern Detector
 * 
 * Analyzes successful content to detect common success traits
 * Sprint E - Feedback Loop & Self-Learning SEO System
 * 
 * @package DODO_AI_SEO
 * @since 3.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Winner_Pattern_Detector {
    
    /**
     * Analyze winning patterns
     * 
     * @return array Pattern analysis
     */
    public function analyze_winning_patterns() {
        $analysis = array(
            'total_winners' => 0,
            'content_patterns' => array(),
            'structural_patterns' => array(),
            'seo_patterns' => array(),
            'common_traits' => array(),
            'success_formula' => array(),
        );
        
        try {
            // Get winning content
            $winners = $this->get_winning_content();
            
            $analysis['total_winners'] = count($winners);
            
            if (empty($winners)) {
                return $analysis;
            }
            
            // Analyze content patterns
            $analysis['content_patterns'] = $this->analyze_content_patterns($winners);
            
            // Analyze structural patterns
            $analysis['structural_patterns'] = $this->analyze_structural_patterns($winners);
            
            // Analyze SEO patterns
            $analysis['seo_patterns'] = $this->analyze_seo_patterns($winners);
            
            // Detect common traits
            $analysis['common_traits'] = $this->detect_common_success_traits($winners);
            
            // Generate success formula
            $analysis['success_formula'] = $this->generate_success_formula($analysis);
            
            // Save analysis
            $this->save_pattern_analysis($analysis);
            
        } catch (Throwable $e) {
            error_log('[DODO][Winner Patterns] Error analyzing patterns: ' . $e->getMessage());
        }
        
        return $analysis;
    }
    
    /**
     * Get winning content
     * 
     * @return array Winners with full data
     */
    private function get_winning_content() {
        global $wpdb;
        
        $post_ids = $wpdb->get_col(
            "SELECT post_id 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_dodo_performance_score' 
            AND CAST(meta_value AS UNSIGNED) >= 70
            ORDER BY CAST(meta_value AS UNSIGNED) DESC
            LIMIT 50"
        );
        
        $winners = array();
        
        foreach ($post_ids as $post_id) {
            $post = get_post($post_id);
            
            if (!$post) {
                continue;
            }
            
            $winners[] = array(
                'post_id' => $post_id,
                'title' => $post->post_title,
                'content' => $post->post_content,
                'word_count' => str_word_count(strip_tags($post->post_content)),
                'performance_score' => get_post_meta($post_id, '_dodo_performance_score', true),
                'growth_rate' => get_post_meta($post_id, '_dodo_growth_rate', true),
                'geo_score' => get_post_meta($post_id, '_dodo_geo_score', true),
                'semantic_depth' => get_post_meta($post_id, '_dodo_semantic_depth', true),
                'conversion_score' => get_post_meta($post_id, '_dodo_conversion_score', true),
            );
        }
        
        return $winners;
    }
    
    /**
     * Analyze content patterns
     * 
     * @param array $winners
     * @return array Content patterns
     */
    private function analyze_content_patterns($winners) {
        $patterns = array(
            'avg_word_count' => 0,
            'word_count_range' => array('min' => 0, 'max' => 0),
            'optimal_length' => '',
            'common_length' => '',
        );
        
        $word_counts = array_column($winners, 'word_count');
        
        if (!empty($word_counts)) {
            $patterns['avg_word_count'] = round(array_sum($word_counts) / count($word_counts));
            $patterns['word_count_range']['min'] = min($word_counts);
            $patterns['word_count_range']['max'] = max($word_counts);
            
            // Determine optimal length
            if ($patterns['avg_word_count'] >= 2000) {
                $patterns['optimal_length'] = 'Çok uzun (2000+ kelime)';
            } elseif ($patterns['avg_word_count'] >= 1500) {
                $patterns['optimal_length'] = 'Uzun (1500-2000 kelime)';
            } elseif ($patterns['avg_word_count'] >= 1000) {
                $patterns['optimal_length'] = 'Orta (1000-1500 kelime)';
            } else {
                $patterns['optimal_length'] = 'Kısa (< 1000 kelime)';
            }
            
            // Most common length range
            $length_distribution = array(
                'short' => 0,    // < 1000
                'medium' => 0,   // 1000-1500
                'long' => 0,     // 1500-2000
                'very_long' => 0 // 2000+
            );
            
            foreach ($word_counts as $count) {
                if ($count < 1000) {
                    $length_distribution['short']++;
                } elseif ($count < 1500) {
                    $length_distribution['medium']++;
                } elseif ($count < 2000) {
                    $length_distribution['long']++;
                } else {
                    $length_distribution['very_long']++;
                }
            }
            
            arsort($length_distribution);
            $most_common = array_key_first($length_distribution);
            
            $length_labels = array(
                'short' => 'Kısa (< 1000)',
                'medium' => 'Orta (1000-1500)',
                'long' => 'Uzun (1500-2000)',
                'very_long' => 'Çok uzun (2000+)',
            );
            
            $patterns['common_length'] = $length_labels[$most_common];
        }
        
        return $patterns;
    }
    
    /**
     * Analyze structural patterns
     * 
     * @param array $winners
     * @return array Structural patterns
     */
    private function analyze_structural_patterns($winners) {
        $patterns = array(
            'avg_faq_count' => 0,
            'avg_heading_count' => 0,
            'avg_list_count' => 0,
            'avg_internal_links' => 0,
            'faq_presence' => 0,
            'table_presence' => 0,
        );
        
        $faq_counts = array();
        $heading_counts = array();
        $list_counts = array();
        $link_counts = array();
        $faq_present = 0;
        $table_present = 0;
        
        foreach ($winners as $winner) {
            $content = $winner['content'];
            
            // FAQ count
            $faq_count = substr_count($content, 'wp:rank-math/faq-block');
            $faq_counts[] = $faq_count;
            
            if ($faq_count > 0) {
                $faq_present++;
            }
            
            // Heading count
            $h2_count = substr_count($content, '<h2');
            $h3_count = substr_count($content, '<h3');
            $heading_counts[] = $h2_count + $h3_count;
            
            // List count
            $list_count = substr_count($content, '<ul') + substr_count($content, '<ol');
            $list_counts[] = $list_count;
            
            // Internal links
            $link_count = substr_count($content, get_site_url());
            $link_counts[] = $link_count;
            
            // Table presence
            if (strpos($content, '<table') !== false) {
                $table_present++;
            }
        }
        
        $total = count($winners);
        
        if ($total > 0) {
            $patterns['avg_faq_count'] = round(array_sum($faq_counts) / $total, 1);
            $patterns['avg_heading_count'] = round(array_sum($heading_counts) / $total, 1);
            $patterns['avg_list_count'] = round(array_sum($list_counts) / $total, 1);
            $patterns['avg_internal_links'] = round(array_sum($link_counts) / $total, 1);
            $patterns['faq_presence'] = round(($faq_present / $total) * 100);
            $patterns['table_presence'] = round(($table_present / $total) * 100);
        }
        
        return $patterns;
    }
    
    /**
     * Analyze SEO patterns
     * 
     * @param array $winners
     * @return array SEO patterns
     */
    private function analyze_seo_patterns($winners) {
        $patterns = array(
            'avg_geo_score' => 0,
            'avg_semantic_depth' => 0,
            'avg_conversion_score' => 0,
            'title_patterns' => array(),
        );
        
        $geo_scores = array_filter(array_column($winners, 'geo_score'));
        $semantic_depths = array_filter(array_column($winners, 'semantic_depth'));
        $conversion_scores = array_filter(array_column($winners, 'conversion_score'));
        
        if (!empty($geo_scores)) {
            $patterns['avg_geo_score'] = round(array_sum($geo_scores) / count($geo_scores));
        }
        
        if (!empty($semantic_depths)) {
            $patterns['avg_semantic_depth'] = round(array_sum($semantic_depths) / count($semantic_depths));
        }
        
        if (!empty($conversion_scores)) {
            $patterns['avg_conversion_score'] = round(array_sum($conversion_scores) / count($conversion_scores));
        }
        
        // Title patterns
        $title_patterns = array(
            'has_number' => 0,
            'has_year' => 0,
            'has_question' => 0,
            'has_how_to' => 0,
            'avg_length' => 0,
        );
        
        $title_lengths = array();
        
        foreach ($winners as $winner) {
            $title = mb_strtolower($winner['title'], 'UTF-8');
            $title_lengths[] = mb_strlen($winner['title'], 'UTF-8');
            
            if (preg_match('/\d+/', $title)) {
                $title_patterns['has_number']++;
            }
            
            if (preg_match('/20\d{2}/', $title)) {
                $title_patterns['has_year']++;
            }
            
            if (strpos($title, '?') !== false || preg_match('/(nasıl|neden|ne)/u', $title)) {
                $title_patterns['has_question']++;
            }
            
            if (preg_match('/(nasıl|how to)/u', $title)) {
                $title_patterns['has_how_to']++;
            }
        }
        
        $total = count($winners);
        
        if ($total > 0) {
            $title_patterns['has_number'] = round(($title_patterns['has_number'] / $total) * 100);
            $title_patterns['has_year'] = round(($title_patterns['has_year'] / $total) * 100);
            $title_patterns['has_question'] = round(($title_patterns['has_question'] / $total) * 100);
            $title_patterns['has_how_to'] = round(($title_patterns['has_how_to'] / $total) * 100);
            $title_patterns['avg_length'] = round(array_sum($title_lengths) / count($title_lengths));
        }
        
        $patterns['title_patterns'] = $title_patterns;
        
        return $patterns;
    }
    
    /**
     * Detect common success traits
     * 
     * @param array $winners
     * @return array Common traits
     */
    public function detect_common_success_traits($winners) {
        $traits = array();
        
        if (empty($winners)) {
            return $traits;
        }
        
        // Analyze content patterns
        $content_patterns = $this->analyze_content_patterns($winners);
        
        if ($content_patterns['avg_word_count'] >= 1500) {
            $traits[] = array(
                'trait' => 'Uzun içerik',
                'value' => $content_patterns['avg_word_count'] . ' kelime ortalama',
                'importance' => 'high',
            );
        }
        
        // Analyze structural patterns
        $structural_patterns = $this->analyze_structural_patterns($winners);
        
        if ($structural_patterns['faq_presence'] >= 70) {
            $traits[] = array(
                'trait' => 'FAQ kullanımı',
                'value' => '%' . $structural_patterns['faq_presence'] . ' içerikte var',
                'importance' => 'high',
            );
        }
        
        if ($structural_patterns['avg_heading_count'] >= 8) {
            $traits[] = array(
                'trait' => 'Zengin başlık yapısı',
                'value' => $structural_patterns['avg_heading_count'] . ' başlık ortalama',
                'importance' => 'medium',
            );
        }
        
        if ($structural_patterns['avg_internal_links'] >= 5) {
            $traits[] = array(
                'trait' => 'Güçlü iç linkleme',
                'value' => $structural_patterns['avg_internal_links'] . ' link ortalama',
                'importance' => 'medium',
            );
        }
        
        // Analyze SEO patterns
        $seo_patterns = $this->analyze_seo_patterns($winners);
        
        if ($seo_patterns['avg_geo_score'] >= 70) {
            $traits[] = array(
                'trait' => 'Yüksek GEO optimizasyonu',
                'value' => $seo_patterns['avg_geo_score'] . '/100',
                'importance' => 'high',
            );
        }
        
        if ($seo_patterns['avg_semantic_depth'] >= 70) {
            $traits[] = array(
                'trait' => 'Derin semantic coverage',
                'value' => $seo_patterns['avg_semantic_depth'] . '/100',
                'importance' => 'high',
            );
        }
        
        // Title patterns
        $title_patterns = $seo_patterns['title_patterns'];
        
        if ($title_patterns['has_number'] >= 60) {
            $traits[] = array(
                'trait' => 'Başlıkta sayı kullanımı',
                'value' => '%' . $title_patterns['has_number'] . ' içerikte var',
                'importance' => 'medium',
            );
        }
        
        if ($title_patterns['has_year'] >= 50) {
            $traits[] = array(
                'trait' => 'Başlıkta yıl kullanımı',
                'value' => '%' . $title_patterns['has_year'] . ' içerikte var',
                'importance' => 'medium',
            );
        }
        
        return $traits;
    }
    
    /**
     * Generate success formula
     * 
     * @param array $analysis
     * @return array Success formula
     */
    private function generate_success_formula($analysis) {
        $formula = array(
            'must_have' => array(),
            'should_have' => array(),
            'nice_to_have' => array(),
        );
        
        // Must have (high importance traits)
        foreach ($analysis['common_traits'] as $trait) {
            if ($trait['importance'] === 'high') {
                $formula['must_have'][] = $trait['trait'] . ': ' . $trait['value'];
            }
        }
        
        // Should have (medium importance)
        foreach ($analysis['common_traits'] as $trait) {
            if ($trait['importance'] === 'medium') {
                $formula['should_have'][] = $trait['trait'] . ': ' . $trait['value'];
            }
        }
        
        // Nice to have (low importance)
        foreach ($analysis['common_traits'] as $trait) {
            if ($trait['importance'] === 'low') {
                $formula['nice_to_have'][] = $trait['trait'] . ': ' . $trait['value'];
            }
        }
        
        // Add specific recommendations
        $content_patterns = $analysis['content_patterns'];
        $structural_patterns = $analysis['structural_patterns'];
        
        if ($content_patterns['avg_word_count'] >= 1500) {
            $formula['must_have'][] = 'Minimum ' . round($content_patterns['avg_word_count']) . ' kelime';
        }
        
        if ($structural_patterns['avg_faq_count'] >= 3) {
            $formula['must_have'][] = 'En az ' . round($structural_patterns['avg_faq_count']) . ' FAQ sorusu';
        }
        
        if ($structural_patterns['avg_heading_count'] >= 8) {
            $formula['should_have'][] = 'En az ' . round($structural_patterns['avg_heading_count']) . ' başlık';
        }
        
        return $formula;
    }
    
    /**
     * Save pattern analysis
     * 
     * @param array $analysis
     */
    private function save_pattern_analysis($analysis) {
        update_option('dodo_winner_patterns', $analysis, false);
        update_option('dodo_winner_patterns_updated', current_time('mysql'), false);
    }
    
    /**
     * Get saved pattern analysis
     * 
     * @return array Analysis
     */
    public function get_saved_patterns() {
        return get_option('dodo_winner_patterns', array());
    }
    
    /**
     * Get pattern statistics
     * 
     * @return array Statistics
     */
    public function get_statistics() {
        $stats = array(
            'last_analysis' => '',
            'total_winners_analyzed' => 0,
            'total_traits_found' => 0,
            'success_formula_items' => 0,
        );
        
        try {
            $stats['last_analysis'] = get_option('dodo_winner_patterns_updated', 'Henüz analiz yapılmadı');
            
            $patterns = $this->get_saved_patterns();
            
            if (!empty($patterns)) {
                $stats['total_winners_analyzed'] = $patterns['total_winners'] ?? 0;
                $stats['total_traits_found'] = count($patterns['common_traits'] ?? array());
                
                $formula = $patterns['success_formula'] ?? array();
                $stats['success_formula_items'] = count($formula['must_have'] ?? array()) +
                                                   count($formula['should_have'] ?? array()) +
                                                   count($formula['nice_to_have'] ?? array());
            }
            
        } catch (Throwable $e) {
            error_log('[DODO][Winner Patterns] Error getting statistics: ' . $e->getMessage());
        }
        
        return $stats;
    }
}
