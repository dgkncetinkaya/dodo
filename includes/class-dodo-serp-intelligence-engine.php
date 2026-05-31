<?php
/**
 * SERP Intelligence Engine
 * 
 * Analyzes Google SERP structure and provides content strategy
 * Sprint C - SEO Intelligence Engine (Task 7)
 * 
 * @package DODO_AI_SEO
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_SERP_Intelligence_Engine {
    
    /**
     * Analyze SERP structure for a query
     * 
     * @param string $query
     * @param array $gsc_data Optional GSC data
     * @return array SERP analysis
     */
    public function analyze_serp_structure($query, $gsc_data = array()) {
        $query_lower = mb_strtolower($query, 'UTF-8');
        
        $serp_type = 'informational';
        $features = array();
        $recommendations = array();
        $content_strategy = array();
        
        // 1. Detect SERP type based on query patterns
        if ($this->is_video_heavy_query($query_lower)) {
            $serp_type = 'video-heavy';
            $features[] = 'Video results dominant';
            $recommendations[] = 'Consider video content or video embed';
            $content_strategy[] = 'Add video tutorial section';
        }
        
        if ($this->is_ecommerce_query($query_lower)) {
            $serp_type = 'ecommerce-heavy';
            $features[] = 'Shopping results dominant';
            $recommendations[] = 'Product-focused content needed';
            $content_strategy[] = 'Add product comparison table';
        }
        
        if ($this->is_forum_heavy_query($query_lower)) {
            $serp_type = 'forum-heavy';
            $features[] = 'Forum/discussion results';
            $recommendations[] = 'User-generated content style';
            $content_strategy[] = 'Add Q&A or discussion section';
        }
        
        // 2. Featured snippet opportunity
        if ($this->has_featured_snippet_opportunity($query_lower)) {
            $features[] = 'Featured snippet opportunity';
            $recommendations[] = 'Optimize for featured snippet';
            $content_strategy[] = 'Add concise answer paragraph (40-60 words)';
            $content_strategy[] = 'Use definition list or table format';
        }
        
        // 3. People Also Ask detection
        if ($this->has_paa_opportunity($query_lower)) {
            $features[] = 'People Also Ask present';
            $recommendations[] = 'Target PAA questions';
            $content_strategy[] = 'Add FAQ section with related questions';
        }
        
        // 4. Local pack detection
        if ($this->has_local_intent($query_lower)) {
            $features[] = 'Local pack likely';
            $recommendations[] = 'Add location-specific content';
            $content_strategy[] = 'Include city/region names';
        }
        
        // 5. Title pattern analysis
        $title_pattern = $this->analyze_title_patterns($query_lower);
        if ($title_pattern) {
            $recommendations[] = "Use title pattern: {$title_pattern}";
        }
        
        return array(
            'serp_type' => $serp_type,
            'features' => $features,
            'recommendations' => $recommendations,
            'content_strategy' => $content_strategy,
            'title_pattern' => $title_pattern,
        );
    }
    
    /**
     * Check if query is video-heavy
     */
    private function is_video_heavy_query($query) {
        $video_patterns = array(
            '/nasıl\s+yapılır/i',
            '/how\s+to/i',
            '/tutorial/i',
            '/video/i',
            '/izle/i',
            '/watch/i',
            '/adım\s+adım/i',
            '/step\s+by\s+step/i',
        );
        
        foreach ($video_patterns as $pattern) {
            if (preg_match($pattern, $query)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if query is ecommerce-heavy
     */
    private function is_ecommerce_query($query) {
        $ecommerce_patterns = array(
            '/fiyat/i',
            '/satın\s+al/i',
            '/buy/i',
            '/ucuz/i',
            '/cheap/i',
            '/indirim/i',
            '/discount/i',
            '/en\s+iyi/i',
            '/best/i',
        );
        
        foreach ($ecommerce_patterns as $pattern) {
            if (preg_match($pattern, $query)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if query is forum-heavy
     */
    private function is_forum_heavy_query($query) {
        $forum_patterns = array(
            '/forum/i',
            '/reddit/i',
            '/ekşi/i',
            '/yorum/i',
            '/review/i',
            '/deneyim/i',
            '/experience/i',
            '/tavsiye/i',
            '/recommend/i',
        );
        
        foreach ($forum_patterns as $pattern) {
            if (preg_match($pattern, $query)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check featured snippet opportunity
     */
    private function has_featured_snippet_opportunity($query) {
        $snippet_patterns = array(
            '/nedir/i',
            '/what\s+is/i',
            '/ne\s+demek/i',
            '/nasıl/i',
            '/how/i',
            '/neden/i',
            '/why/i',
            '/ne\s+zaman/i',
            '/when/i',
        );
        
        foreach ($snippet_patterns as $pattern) {
            if (preg_match($pattern, $query)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check People Also Ask opportunity
     */
    private function has_paa_opportunity($query) {
        // Questions typically trigger PAA
        return $this->has_featured_snippet_opportunity($query);
    }
    
    /**
     * Check local intent
     */
    private function has_local_intent($query) {
        $local_patterns = array(
            '/istanbul/i',
            '/ankara/i',
            '/izmir/i',
            '/yakın/i',
            '/near\s+me/i',
            '/nerede/i',
            '/where/i',
        );
        
        foreach ($local_patterns as $pattern) {
            if (preg_match($pattern, $query)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Analyze title patterns
     */
    private function analyze_title_patterns($query) {
        if (preg_match('/nedir/i', $query)) {
            return '[Topic] Nedir? 2024 Kapsamlı Rehber';
        }
        
        if (preg_match('/nasıl/i', $query)) {
            return '[Topic] Nasıl Yapılır? Adım Adım Rehber';
        }
        
        if (preg_match('/en\s+iyi/i', $query)) {
            return 'En İyi [X] [Topic] - 2024 Karşılaştırma';
        }
        
        if (preg_match('/fiyat/i', $query)) {
            return '[Topic] Fiyatları 2024 - Güncel Liste';
        }
        
        return null;
    }
    
    /**
     * Enrich opportunities with SERP intelligence
     */
    public function enrich_opportunities($opportunities) {
        foreach ($opportunities as &$opp) {
            $query = $opp['keyword'] ?? '';
            
            if (empty($query)) {
                continue;
            }
            
            $gsc_data = array(
                'position' => $opp['position'] ?? 0,
                'ctr' => $opp['ctr'] ?? 0,
            );
            
            $serp_analysis = $this->analyze_serp_structure($query, $gsc_data);
            
            $opp['serp_type'] = $serp_analysis['serp_type'];
            $opp['serp_features'] = $serp_analysis['features'];
            $opp['serp_recommendations'] = $serp_analysis['recommendations'];
            $opp['content_strategy'] = $serp_analysis['content_strategy'];
        }
        
        return $opportunities;
    }
}
