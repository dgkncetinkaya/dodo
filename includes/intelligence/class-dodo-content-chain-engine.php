<?php
/**
 * Content Chain Engine
 * 
 * Generates content chains and supporting article recommendations
 * Sprint D - Revenue & Topical Domination Engine
 * 
 * @package DODO_AI_SEO
 * @since 3.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Content_Chain_Engine {
    
    /**
     * Generate content chain
     * 
     * @param string $keyword Main keyword
     * @param array $context
     * @return array Content chain
     */
    public function generate_content_chain($keyword, $context = array()) {
        $chain = array(
            'pillar_keyword' => $keyword,
            'supporting_articles' => array(),
            'semantic_path' => array(),
            'funnel_stage' => 'awareness',
        );
        
        try {
            $keyword_lower = mb_strtolower($keyword, 'UTF-8');
            
            // Detect supporting content
            $chain['supporting_articles'] = $this->detect_supporting_content($keyword_lower);
            
            // Build semantic path
            $chain['semantic_path'] = $this->build_semantic_path($keyword_lower);
            
            // Determine funnel stage
            $chain['funnel_stage'] = $this->determine_funnel_stage($keyword_lower);
            
            // Detect pillar relation
            $chain['pillar_relation'] = $this->detect_pillar_relation($keyword_lower);
            
        } catch (Throwable $e) {
            error_log('[DODO][Content Chain] Error generating chain: ' . $e->getMessage());
        }
        
        return $chain;
    }
    
    /**
     * Detect supporting content
     */
    private function detect_supporting_content($keyword) {
        $supporting = array();
        
        // Extract main topic
        $main_topic = $this->extract_main_topic($keyword);
        
        // Generate supporting article ideas
        $patterns = array(
            // Awareness stage
            $main_topic . ' nedir',
            $main_topic . ' hakkında bilmeniz gerekenler',
            $main_topic . ' tarihçesi',
            
            // Consideration stage
            $main_topic . ' nasıl çalışır',
            $main_topic . ' çeşitleri',
            $main_topic . ' avantajları ve dezavantajları',
            $main_topic . ' kullanım alanları',
            
            // Decision stage
            'en iyi ' . $main_topic,
            $main_topic . ' nasıl seçilir',
            $main_topic . ' satın alma rehberi',
            $main_topic . ' fiyatları',
            
            // Support/troubleshooting
            $main_topic . ' sorunları ve çözümleri',
            $main_topic . ' bakımı',
            $main_topic . ' püf noktaları',
        );
        
        foreach ($patterns as $pattern) {
            $supporting[] = array(
                'keyword' => $pattern,
                'relation' => $this->determine_relation_type($pattern, $keyword),
                'priority' => $this->calculate_support_priority($pattern, $keyword),
            );
        }
        
        // Sort by priority
        usort($supporting, function($a, $b) {
            return $b['priority'] <=> $a['priority'];
        });
        
        return array_slice($supporting, 0, 8);
    }
    
    /**
     * Extract main topic
     */
    private function extract_main_topic($keyword) {
        // Remove modifiers
        $modifiers = array(
            'nasıl', 'nedir', 'ne demek', 'neden', 'ne zaman', 'nerede',
            'en iyi', 'fiyat', 'fiyatları', 'satın al', 'tavsiye',
            'karşılaştırma', 'vs', 'hakkında', 'için', 'ile',
            'how to', 'what is', 'why', 'when', 'where',
            'best', 'price', 'buy', 'compare', 'vs', 'about',
        );
        
        $topic = $keyword;
        foreach ($modifiers as $modifier) {
            $topic = preg_replace('/\b' . preg_quote($modifier, '/') . '\b/i', '', $topic);
        }
        
        return trim(preg_replace('/\s+/', ' ', $topic));
    }
    
    /**
     * Determine relation type
     */
    private function determine_relation_type($supporting_keyword, $main_keyword) {
        if (stripos($supporting_keyword, 'nedir') !== false) {
            return 'definition';
        }
        
        if (stripos($supporting_keyword, 'nasıl') !== false) {
            return 'how-to';
        }
        
        if (stripos($supporting_keyword, 'en iyi') !== false || stripos($supporting_keyword, 'seçilir') !== false) {
            return 'comparison';
        }
        
        if (stripos($supporting_keyword, 'fiyat') !== false) {
            return 'pricing';
        }
        
        if (stripos($supporting_keyword, 'sorun') !== false || stripos($supporting_keyword, 'hata') !== false) {
            return 'troubleshooting';
        }
        
        return 'related';
    }
    
    /**
     * Calculate support priority
     */
    private function calculate_support_priority($supporting_keyword, $main_keyword) {
        $priority = 50;
        
        // High priority for foundational content
        if (stripos($supporting_keyword, 'nedir') !== false) {
            $priority += 30;
        }
        
        // High priority for how-to
        if (stripos($supporting_keyword, 'nasıl') !== false) {
            $priority += 25;
        }
        
        // Medium priority for comparison
        if (stripos($supporting_keyword, 'en iyi') !== false) {
            $priority += 20;
        }
        
        // Check if already exists
        $exists = $this->check_content_exists($supporting_keyword);
        if ($exists) {
            $priority -= 40; // Lower priority if exists
        }
        
        return min(100, max(0, $priority));
    }
    
    /**
     * Check if content exists
     */
    private function check_content_exists($keyword) {
        $posts = get_posts(array(
            'post_type' => 'post',
            'post_status' => 'publish',
            's' => $keyword,
            'numberposts' => 1,
        ));
        
        return !empty($posts);
    }
    
    /**
     * Build semantic path
     */
    private function build_semantic_path($keyword) {
        $path = array();
        
        $main_topic = $this->extract_main_topic($keyword);
        
        // Build path from general to specific
        $path[] = array(
            'level' => 'general',
            'keyword' => $main_topic,
            'description' => 'Ana konu',
        );
        
        $path[] = array(
            'level' => 'specific',
            'keyword' => $keyword,
            'description' => 'Spesifik sorgu',
        );
        
        // Add related concepts
        if (preg_match('/nasıl/i', $keyword)) {
            $path[] = array(
                'level' => 'action',
                'keyword' => $main_topic . ' uygulaması',
                'description' => 'Pratik uygulama',
            );
        }
        
        return $path;
    }
    
    /**
     * Determine funnel stage
     */
    private function determine_funnel_stage($keyword) {
        // Awareness stage
        if (preg_match('/(nedir|ne\s+demek|what\s+is|hakkında)/i', $keyword)) {
            return 'awareness';
        }
        
        // Consideration stage
        if (preg_match('/(nasıl|how|çeşitleri|types|avantaj|advantage)/i', $keyword)) {
            return 'consideration';
        }
        
        // Decision stage
        if (preg_match('/(satın\s+al|buy|fiyat|price|en\s+iyi|best|seçilir|choose)/i', $keyword)) {
            return 'decision';
        }
        
        // Retention stage
        if (preg_match('/(sorun|problem|hata|error|bakım|maintenance)/i', $keyword)) {
            return 'retention';
        }
        
        return 'consideration'; // Default
    }
    
    /**
     * Detect pillar relation
     */
    private function detect_pillar_relation($keyword) {
        $main_topic = $this->extract_main_topic($keyword);
        
        // Check if main topic has pillar content
        $pillar_posts = get_posts(array(
            'post_type' => 'post',
            'post_status' => 'publish',
            's' => $main_topic,
            'numberposts' => 10,
        ));
        
        if (count($pillar_posts) >= 5) {
            return 'has_pillar';
        } elseif (count($pillar_posts) >= 2) {
            return 'emerging_pillar';
        } else {
            return 'no_pillar';
        }
    }
    
    /**
     * Enrich opportunities with content chain intelligence
     */
    public function enrich_opportunities($opportunities) {
        foreach ($opportunities as &$opp) {
            $keyword = $opp['keyword'] ?? '';
            
            if (empty($keyword)) {
                continue;
            }
            
            try {
                $chain = $this->generate_content_chain($keyword);
                
                $opp['supporting_articles'] = $chain['supporting_articles'];
                $opp['semantic_path'] = $chain['semantic_path'];
                $opp['funnel_stage'] = $chain['funnel_stage'];
                $opp['pillar_relation'] = $chain['pillar_relation'];
                
            } catch (Throwable $e) {
                error_log('[DODO][Content Chain] Error enriching opportunity: ' . $e->getMessage());
            }
        }
        
        return $opportunities;
    }
}
