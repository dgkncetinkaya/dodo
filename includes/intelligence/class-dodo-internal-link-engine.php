<?php
/**
 * Internal Link Opportunity Engine
 * 
 * Finds internal linking opportunities and detects orphan content
 * Sprint D - Revenue & Topical Domination Engine
 * 
 * @package DODO_AI_SEO
 * @since 3.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Internal_Link_Engine {
    
    /**
     * Find internal link opportunities for a keyword
     * 
     * @param string $keyword
     * @param array $context
     * @return array Link opportunities
     */
    public function find_internal_link_opportunities($keyword, $context = array()) {
        $opportunities = array();
        
        try {
            $keyword_lower = mb_strtolower($keyword, 'UTF-8');
            
            // Extract main topic
            $main_topic = $this->extract_main_topic($keyword_lower);
            
            // Find semantically related posts
            $related_posts = $this->find_related_posts($main_topic, $keyword_lower);
            
            // Suggest anchor texts
            $anchor_texts = $this->suggest_anchor_texts($keyword_lower);
            
            // Calculate link equity gap
            $link_equity_gap = $this->calculate_link_equity_gap($related_posts);
            
            $opportunities = array(
                'related_posts' => $related_posts,
                'suggested_anchors' => $anchor_texts,
                'link_equity_gap' => $link_equity_gap,
                'opportunity_count' => count($related_posts),
            );
            
        } catch (Throwable $e) {
            error_log('[DODO][Internal Link] Error finding opportunities: ' . $e->getMessage());
        }
        
        return $opportunities;
    }
    
    /**
     * Extract main topic from keyword
     */
    private function extract_main_topic($keyword) {
        // Remove modifiers
        $modifiers = array(
            'nasıl', 'nedir', 'ne demek', 'neden', 'ne zaman', 'nerede',
            'en iyi', 'fiyat', 'fiyatları', 'satın al', 'tavsiye',
            'how to', 'what is', 'why', 'when', 'where', 'best', 'price',
        );
        
        $topic = $keyword;
        foreach ($modifiers as $modifier) {
            $topic = preg_replace('/\b' . preg_quote($modifier, '/') . '\b/i', '', $topic);
        }
        
        return trim(preg_replace('/\s+/', ' ', $topic));
    }
    
    /**
     * Find related posts
     */
    private function find_related_posts($main_topic, $keyword) {
        $related = array();
        
        // Search by main topic
        $posts = get_posts(array(
            'post_type' => 'post',
            'post_status' => 'publish',
            's' => $main_topic,
            'numberposts' => 10,
        ));
        
        foreach ($posts as $post) {
            // Count existing internal links
            $content = $post->post_content;
            $internal_link_count = substr_count($content, '<a href');
            
            $related[] = array(
                'id' => $post->ID,
                'title' => $post->post_title,
                'url' => get_permalink($post->ID),
                'internal_links' => $internal_link_count,
                'relevance' => $this->calculate_relevance($post, $keyword),
            );
        }
        
        // Sort by relevance
        usort($related, function($a, $b) {
            return $b['relevance'] <=> $a['relevance'];
        });
        
        return array_slice($related, 0, 5);
    }
    
    /**
     * Calculate relevance score
     */
    private function calculate_relevance($post, $keyword) {
        $score = 0;
        
        $title = mb_strtolower($post->post_title, 'UTF-8');
        $content = mb_strtolower(strip_tags($post->post_content), 'UTF-8');
        $keyword_lower = mb_strtolower($keyword, 'UTF-8');
        
        // Title match
        if (stripos($title, $keyword_lower) !== false) {
            $score += 50;
        }
        
        // Content mentions
        $mentions = substr_count($content, $keyword_lower);
        $score += min(30, $mentions * 5);
        
        // Recency
        $post_age_days = (time() - strtotime($post->post_date)) / DAY_IN_SECONDS;
        if ($post_age_days < 30) {
            $score += 20;
        } elseif ($post_age_days < 90) {
            $score += 10;
        }
        
        return min(100, $score);
    }
    
    /**
     * Suggest anchor texts
     */
    private function suggest_anchor_texts($keyword) {
        $anchors = array();
        
        // Exact match
        $anchors[] = $keyword;
        
        // Variations
        $words = explode(' ', $keyword);
        
        if (count($words) > 1) {
            // Partial match
            $anchors[] = implode(' ', array_slice($words, 0, 2));
            
            // With modifier
            $anchors[] = $keyword . ' hakkında';
            $anchors[] = $keyword . ' rehberi';
        }
        
        // Branded
        $anchors[] = 'detaylı ' . $keyword . ' rehberi';
        
        return array_unique(array_slice($anchors, 0, 5));
    }
    
    /**
     * Calculate link equity gap (0-100)
     */
    private function calculate_link_equity_gap($related_posts) {
        if (empty($related_posts)) {
            return 100; // Maximum gap - no related content
        }
        
        $total_links = 0;
        foreach ($related_posts as $post) {
            $total_links += $post['internal_links'];
        }
        
        $avg_links = $total_links / count($related_posts);
        
        // Expected: 5-10 internal links per post
        $expected_links = 7;
        
        if ($avg_links >= $expected_links) {
            return 0; // No gap
        }
        
        $gap = (($expected_links - $avg_links) / $expected_links) * 100;
        
        return min(100, round($gap));
    }
    
    /**
     * Detect orphan content
     */
    public function detect_orphan_content() {
        $orphans = array();
        
        try {
            global $wpdb;
            
            // Find posts with no internal links pointing to them
            $query = "
                SELECT p.ID, p.post_title, p.post_date
                FROM {$wpdb->posts} p
                WHERE p.post_type = 'post'
                AND p.post_status = 'publish'
                AND p.ID NOT IN (
                    SELECT DISTINCT post_id
                    FROM {$wpdb->postmeta}
                    WHERE meta_key = '_internal_links_count'
                    AND meta_value > 0
                )
                ORDER BY p.post_date DESC
                LIMIT 20
            ";
            
            $results = $wpdb->get_results($query);
            
            foreach ($results as $post) {
                $orphans[] = array(
                    'id' => $post->ID,
                    'title' => $post->post_title,
                    'date' => $post->post_date,
                    'url' => get_permalink($post->ID),
                );
            }
            
        } catch (Throwable $e) {
            error_log('[DODO][Internal Link] Error detecting orphans: ' . $e->getMessage());
        }
        
        return $orphans;
    }
    
    /**
     * Enrich opportunities with internal link intelligence
     */
    public function enrich_opportunities($opportunities) {
        foreach ($opportunities as &$opp) {
            $keyword = $opp['keyword'] ?? '';
            
            if (empty($keyword)) {
                continue;
            }
            
            try {
                $link_opps = $this->find_internal_link_opportunities($keyword);
                
                $opp['suggested_internal_links'] = $link_opps['related_posts'];
                $opp['suggested_anchors'] = $link_opps['suggested_anchors'];
                $opp['link_equity_gap'] = $link_opps['link_equity_gap'];
                $opp['internal_link_opportunity_count'] = $link_opps['opportunity_count'];
                
            } catch (Throwable $e) {
                error_log('[DODO][Internal Link] Error enriching opportunity: ' . $e->getMessage());
            }
        }
        
        return $opportunities;
    }
}
