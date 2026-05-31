<?php
/**
 * Competitor Gap Engine V2
 * 
 * Estimates competitor content structures and detects gaps
 * Sprint D - Revenue & Topical Domination Engine
 * 
 * @package DODO_AI_SEO
 * @since 3.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Competitor_Gap_Engine {
    
    /**
     * Detect competitor gap
     * 
     * @param string $keyword
     * @param array $context
     * @return array Gap analysis
     */
    public function detect_competitor_gap($keyword, $context = array()) {
        $gap_analysis = array(
            'missing_sections' => array(),
            'required_depth' => 'medium',
            'semantic_gap' => 0,
            'estimated_word_count' => 0,
            'content_structure' => array(),
        );
        
        try {
            $keyword_lower = mb_strtolower($keyword, 'UTF-8');
            
            // Estimate missing sections based on SERP intent
            $gap_analysis['missing_sections'] = $this->estimate_missing_sections($keyword_lower, $context);
            
            // Estimate required depth
            $gap_analysis['required_depth'] = $this->estimate_required_depth($keyword_lower, $context);
            
            // Calculate semantic gap
            $gap_analysis['semantic_gap'] = $this->estimate_semantic_gap($keyword_lower, $context);
            
            // Estimate word count needed
            $gap_analysis['estimated_word_count'] = $this->estimate_word_count($keyword_lower, $context);
            
            // Suggest content structure
            $gap_analysis['content_structure'] = $this->suggest_content_structure($keyword_lower, $context);
            
        } catch (Throwable $e) {
            error_log('[DODO][Competitor Gap] Error analyzing gap: ' . $e->getMessage());
        }
        
        return $gap_analysis;
    }
    
    /**
     * Estimate missing sections
     */
    private function estimate_missing_sections($keyword, $context) {
        $sections = array();
        
        // Based on keyword intent
        if (preg_match('/nedir|what\s+is/i', $keyword)) {
            $sections[] = 'Tanım ve Açıklama';
            $sections[] = 'Tarihçe';
            $sections[] = 'Kullanım Alanları';
        }
        
        if (preg_match('/nasıl|how\s+to/i', $keyword)) {
            $sections[] = 'Adım Adım Rehber';
            $sections[] = 'Gerekli Malzemeler';
            $sections[] = 'Püf Noktaları';
            $sections[] = 'Sık Yapılan Hatalar';
        }
        
        if (preg_match('/en\s+iyi|best/i', $keyword)) {
            $sections[] = 'Karşılaştırma Tablosu';
            $sections[] = 'Avantajlar ve Dezavantajlar';
            $sections[] = 'Fiyat Karşılaştırması';
            $sections[] = 'Kullanıcı Yorumları';
        }
        
        if (preg_match('/fiyat|price/i', $keyword)) {
            $sections[] = 'Fiyat Aralıkları';
            $sections[] = 'Fiyatı Etkileyen Faktörler';
            $sections[] = 'Nereden Alınır';
            $sections[] = 'Kampanyalar';
        }
        
        // SERP type based sections
        $serp_type = $context['serp_type'] ?? '';
        
        if ($serp_type === 'video-heavy') {
            $sections[] = 'Video Rehber';
            $sections[] = 'Görsel Örnekler';
        }
        
        if ($serp_type === 'ecommerce-heavy') {
            $sections[] = 'Ürün Özellikleri';
            $sections[] = 'Teknik Spesifikasyonlar';
            $sections[] = 'Satın Alma Rehberi';
        }
        
        // Always include
        $sections[] = 'Sık Sorulan Sorular (SSS)';
        $sections[] = 'Sonuç ve Öneriler';
        
        return array_unique($sections);
    }
    
    /**
     * Estimate required depth
     */
    private function estimate_required_depth($keyword, $context) {
        $depth = 'medium';
        
        // High depth indicators
        if (preg_match('/(profesyonel|endüstriyel|teknik|detaylı|kapsamlı)/i', $keyword)) {
            $depth = 'high';
        }
        
        // Position indicates depth needed
        $position = $context['position'] ?? 0;
        if ($position > 0 && $position <= 3) {
            $depth = 'high'; // Top positions need comprehensive content
        }
        
        // Impressions indicate competition
        $impressions = $context['impressions'] ?? 0;
        if ($impressions > 1000) {
            $depth = 'high'; // High volume = high competition
        }
        
        // Low depth indicators
        if (preg_match('/(basit|kolay|hızlı|kısa)/i', $keyword)) {
            $depth = 'low';
        }
        
        return $depth;
    }
    
    /**
     * Estimate semantic gap (0-100)
     */
    private function estimate_semantic_gap($keyword, $context) {
        $gap = 50; // Default medium gap
        
        // Check if we have existing content
        $existing_content = $this->find_existing_content($keyword);
        
        if (empty($existing_content)) {
            $gap = 100; // Complete gap - no content
        } else {
            // Analyze existing content depth
            $content_depth = $this->analyze_content_depth($existing_content);
            
            // Gap = 100 - depth
            $gap = 100 - $content_depth;
        }
        
        return min(100, max(0, $gap));
    }
    
    /**
     * Find existing content
     */
    private function find_existing_content($keyword) {
        $posts = get_posts(array(
            'post_type' => 'post',
            'post_status' => 'publish',
            's' => $keyword,
            'numberposts' => 5,
        ));
        
        return $posts;
    }
    
    /**
     * Analyze content depth (0-100)
     */
    private function analyze_content_depth($posts) {
        if (empty($posts)) {
            return 0;
        }
        
        $total_depth = 0;
        
        foreach ($posts as $post) {
            $depth = 0;
            
            // Word count
            $word_count = str_word_count(strip_tags($post->post_content));
            if ($word_count >= 2000) {
                $depth += 30;
            } elseif ($word_count >= 1000) {
                $depth += 20;
            } elseif ($word_count >= 500) {
                $depth += 10;
            }
            
            // Headings
            $h2_count = substr_count($post->post_content, '<h2');
            $h3_count = substr_count($post->post_content, '<h3');
            $depth += min(20, ($h2_count + $h3_count) * 2);
            
            // Lists
            $list_count = substr_count($post->post_content, '<ul') + substr_count($post->post_content, '<ol');
            $depth += min(15, $list_count * 5);
            
            // Images
            $image_count = substr_count($post->post_content, '<img');
            $depth += min(15, $image_count * 3);
            
            // Links
            $link_count = substr_count($post->post_content, '<a href');
            $depth += min(20, $link_count * 2);
            
            $total_depth += min(100, $depth);
        }
        
        return round($total_depth / count($posts));
    }
    
    /**
     * Estimate word count needed
     */
    private function estimate_word_count($keyword, $context) {
        $base_count = 1000;
        
        // Adjust by depth
        $depth = $this->estimate_required_depth($keyword, $context);
        
        if ($depth === 'high') {
            $base_count = 2500;
        } elseif ($depth === 'low') {
            $base_count = 600;
        }
        
        // Adjust by intent
        if (preg_match('/nasıl|how\s+to/i', $keyword)) {
            $base_count += 500; // How-to needs more detail
        }
        
        if (preg_match('/en\s+iyi|best|karşılaştırma|comparison/i', $keyword)) {
            $base_count += 800; // Comparisons need more content
        }
        
        // Adjust by competition
        $impressions = $context['impressions'] ?? 0;
        if ($impressions > 1000) {
            $base_count += 500; // High competition needs more
        }
        
        return $base_count;
    }
    
    /**
     * Suggest content structure
     */
    private function suggest_content_structure($keyword, $context) {
        $structure = array(
            'introduction' => array(
                'word_count' => 150,
                'elements' => array('Hook', 'Problem statement', 'What reader will learn'),
            ),
            'main_sections' => array(),
            'conclusion' => array(
                'word_count' => 100,
                'elements' => array('Summary', 'Call to action', 'Next steps'),
            ),
        );
        
        // Build main sections based on missing sections
        $missing_sections = $this->estimate_missing_sections($keyword, $context);
        
        foreach ($missing_sections as $section) {
            $structure['main_sections'][] = array(
                'title' => $section,
                'word_count' => 200,
                'elements' => array('Explanation', 'Examples', 'Tips'),
            );
        }
        
        return $structure;
    }
    
    /**
     * Enrich opportunities with competitor gap intelligence
     */
    public function enrich_opportunities($opportunities) {
        foreach ($opportunities as &$opp) {
            $keyword = $opp['keyword'] ?? '';
            
            if (empty($keyword)) {
                continue;
            }
            
            try {
                $context = array(
                    'serp_type' => $opp['serp_type'] ?? '',
                    'position' => $opp['position'] ?? 0,
                    'impressions' => $opp['impressions'] ?? 0,
                );
                
                $gap_analysis = $this->detect_competitor_gap($keyword, $context);
                
                $opp['missing_sections'] = $gap_analysis['missing_sections'];
                $opp['required_depth'] = $gap_analysis['required_depth'];
                $opp['semantic_gap'] = $gap_analysis['semantic_gap'];
                $opp['estimated_word_count'] = $gap_analysis['estimated_word_count'];
                $opp['content_structure'] = $gap_analysis['content_structure'];
                
            } catch (Throwable $e) {
                error_log('[DODO][Competitor Gap] Error enriching opportunity: ' . $e->getMessage());
            }
        }
        
        return $opportunities;
    }
}
