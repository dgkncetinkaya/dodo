<?php
/**
 * Topical Map Engine
 * 
 * Builds topical authority graph and detects content clusters
 * Sprint D - Revenue & Topical Domination Engine
 * 
 * @package DODO_AI_SEO
 * @since 3.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Topical_Map_Engine {
    
    /**
     * Build topical graph from site content
     * 
     * @return array Topical graph
     */
    public function build_topical_graph() {
        $graph = array(
            'pillars' => array(),
            'clusters' => array(),
            'orphans' => array(),
            'stats' => array(),
        );
        
        try {
            // Get all published posts
            $posts = get_posts(array(
                'post_type' => 'post',
                'post_status' => 'publish',
                'numberposts' => 500,
                'orderby' => 'date',
                'order' => 'DESC',
            ));
            
            // Extract topics from titles and content
            $topics = array();
            foreach ($posts as $post) {
                $keywords = $this->extract_main_keywords($post->post_title . ' ' . $post->post_content);
                
                foreach ($keywords as $keyword) {
                    if (!isset($topics[$keyword])) {
                        $topics[$keyword] = array(
                            'posts' => array(),
                            'count' => 0,
                        );
                    }
                    
                    $topics[$keyword]['posts'][] = $post->ID;
                    $topics[$keyword]['count']++;
                }
            }
            
            // Sort by count
            uasort($topics, function($a, $b) {
                return $b['count'] <=> $a['count'];
            });
            
            // Identify pillars (topics with 5+ posts)
            foreach ($topics as $topic => $data) {
                if ($data['count'] >= 5) {
                    $graph['pillars'][$topic] = $data;
                } elseif ($data['count'] >= 2) {
                    $graph['clusters'][$topic] = $data;
                } else {
                    $graph['orphans'][$topic] = $data;
                }
            }
            
            $graph['stats'] = array(
                'total_posts' => count($posts),
                'pillar_count' => count($graph['pillars']),
                'cluster_count' => count($graph['clusters']),
                'orphan_count' => count($graph['orphans']),
            );
            
        } catch (Throwable $e) {
            error_log('[DODO][Topical Map] Graph building error: ' . $e->getMessage());
        }
        
        return $graph;
    }
    
    /**
     * Extract main keywords from text
     */
    private function extract_main_keywords($text) {
        // Turkish stop words
        $stop_words = array(
            'bir', 'bu', 've', 'için', 'ile', 'mi', 'mı', 'mu', 'mü',
            'da', 'de', 'ta', 'te', 'ki', 'ne', 'nasıl', 'neden',
            'gibi', 'kadar', 'daha', 'en', 'çok', 'az', 'var', 'yok',
            'olan', 'olarak', 'ise', 'ancak', 'fakat', 'veya', 'ya',
            'the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at',
            'to', 'for', 'of', 'with', 'by', 'from', 'as', 'is', 'was',
        );
        
        $text = mb_strtolower($text, 'UTF-8');
        $text = strip_tags($text);
        
        // Extract words
        preg_match_all('/\b[\p{L}]{3,}\b/u', $text, $matches);
        $words = $matches[0];
        
        // Filter stop words
        $keywords = array();
        foreach ($words as $word) {
            if (!in_array($word, $stop_words)) {
                if (!isset($keywords[$word])) {
                    $keywords[$word] = 0;
                }
                $keywords[$word]++;
            }
        }
        
        // Sort by frequency
        arsort($keywords);
        
        // Return top 10
        return array_keys(array_slice($keywords, 0, 10, true));
    }
    
    /**
     * Detect content clusters for a keyword
     */
    public function detect_content_clusters($keyword) {
        $keyword_lower = mb_strtolower($keyword, 'UTF-8');
        $main_topic = $this->extract_parent_topic($keyword_lower);
        
        // Search for related content
        $related_posts = get_posts(array(
            'post_type' => 'post',
            'post_status' => 'publish',
            's' => $main_topic,
            'numberposts' => 20,
        ));
        
        $cluster = array(
            'parent_topic' => $main_topic,
            'related_posts' => array(),
            'cluster_strength' => 0,
        );
        
        foreach ($related_posts as $post) {
            $cluster['related_posts'][] = array(
                'id' => $post->ID,
                'title' => $post->post_title,
                'url' => get_permalink($post->ID),
            );
        }
        
        $cluster['cluster_strength'] = count($related_posts);
        
        return $cluster;
    }
    
    /**
     * Extract parent topic from keyword
     */
    private function extract_parent_topic($keyword) {
        // Remove modifiers
        $modifiers = array(
            'nasıl', 'nedir', 'ne demek', 'neden', 'ne zaman', 'nerede',
            'en iyi', 'fiyat', 'fiyatları', 'satın al', 'tavsiye',
            'karşılaştırma', 'vs', 'hakkında', 'için', 'ile',
            'how to', 'what is', 'why', 'when', 'where',
            'best', 'price', 'buy', 'compare', 'vs', 'about',
            '2024', '2025', '2026',
        );
        
        $parent = $keyword;
        
        foreach ($modifiers as $modifier) {
            $parent = preg_replace('/\b' . preg_quote($modifier, '/') . '\b/i', '', $parent);
        }
        
        $parent = preg_replace('/\s+/', ' ', trim($parent));
        
        // Get first 2-3 words as parent topic
        $words = explode(' ', $parent);
        $parent = implode(' ', array_slice($words, 0, min(3, count($words))));
        
        return $parent;
    }
    
    /**
     * Detect missing clusters
     */
    public function detect_missing_clusters($keyword, $topical_graph) {
        $parent_topic = $this->extract_parent_topic($keyword);
        
        $missing = array();
        
        // Check if parent topic has enough coverage
        $pillar_exists = isset($topical_graph['pillars'][$parent_topic]);
        $cluster_exists = isset($topical_graph['clusters'][$parent_topic]);
        
        if (!$pillar_exists && !$cluster_exists) {
            $missing[] = 'No existing content cluster for: ' . $parent_topic;
        }
        
        // Suggest supporting topics
        $supporting_topics = $this->suggest_supporting_topics($keyword);
        
        return array(
            'parent_topic' => $parent_topic,
            'pillar_exists' => $pillar_exists,
            'cluster_exists' => $cluster_exists,
            'missing_coverage' => $missing,
            'suggested_supporting_topics' => $supporting_topics,
        );
    }
    
    /**
     * Suggest supporting topics
     */
    private function suggest_supporting_topics($keyword) {
        $parent = $this->extract_parent_topic($keyword);
        
        // Common supporting topic patterns
        $patterns = array(
            $parent . ' nedir',
            $parent . ' nasıl yapılır',
            $parent . ' çeşitleri',
            $parent . ' avantajları',
            $parent . ' fiyatları',
            $parent . ' kullanım alanları',
            'en iyi ' . $parent,
        );
        
        return $patterns;
    }
    
    /**
     * Calculate topical strength (0-100)
     */
    public function calculate_topical_strength($keyword, $topical_graph) {
        $parent_topic = $this->extract_parent_topic($keyword);
        
        $score = 0;
        
        // Check pillar existence (40 points)
        if (isset($topical_graph['pillars'][$parent_topic])) {
            $post_count = $topical_graph['pillars'][$parent_topic]['count'];
            $score += min(40, $post_count * 5);
        }
        
        // Check cluster existence (30 points)
        if (isset($topical_graph['clusters'][$parent_topic])) {
            $post_count = $topical_graph['clusters'][$parent_topic]['count'];
            $score += min(30, $post_count * 10);
        }
        
        // Check related content (30 points)
        $related_count = $this->count_related_content($parent_topic);
        $score += min(30, $related_count * 3);
        
        return min(100, $score);
    }
    
    /**
     * Count related content
     */
    private function count_related_content($topic) {
        $posts = get_posts(array(
            'post_type' => 'post',
            'post_status' => 'publish',
            's' => $topic,
            'numberposts' => 50,
        ));
        
        return count($posts);
    }
    
    /**
     * Calculate topic depth
     */
    public function calculate_topic_depth($keyword) {
        $parent_topic = $this->extract_parent_topic($keyword);
        
        // Count subtopics
        $subtopics = get_posts(array(
            'post_type' => 'post',
            'post_status' => 'publish',
            's' => $parent_topic,
            'numberposts' => 100,
        ));
        
        $depth_score = 0;
        
        if (count($subtopics) >= 20) {
            $depth_score = 100;
        } elseif (count($subtopics) >= 10) {
            $depth_score = 70;
        } elseif (count($subtopics) >= 5) {
            $depth_score = 40;
        } elseif (count($subtopics) >= 2) {
            $depth_score = 20;
        }
        
        return $depth_score;
    }
    
    /**
     * Enrich opportunities with topical intelligence
     */
    public function enrich_opportunities($opportunities) {
        // Build topical graph once
        $topical_graph = $this->build_topical_graph();
        
        foreach ($opportunities as &$opp) {
            $keyword = $opp['keyword'] ?? '';
            
            if (empty($keyword)) {
                continue;
            }
            
            try {
                $parent_topic = $this->extract_parent_topic($keyword);
                $cluster_info = $this->detect_content_clusters($keyword);
                $missing_info = $this->detect_missing_clusters($keyword, $topical_graph);
                $topical_strength = $this->calculate_topical_strength($keyword, $topical_graph);
                $topic_depth = $this->calculate_topic_depth($keyword);
                
                $opp['parent_topic'] = $parent_topic;
                $opp['cluster_type'] = $cluster_info['cluster_strength'] >= 5 ? 'pillar' : ($cluster_info['cluster_strength'] >= 2 ? 'cluster' : 'orphan');
                $opp['topical_strength'] = $topical_strength;
                $opp['topic_depth'] = $topic_depth;
                $opp['authority_gap'] = 100 - $topical_strength;
                $opp['suggested_supporting_topics'] = $missing_info['suggested_supporting_topics'];
                
            } catch (Throwable $e) {
                error_log('[DODO][Topical Map] Error analyzing keyword: ' . $e->getMessage());
            }
        }
        
        return $opportunities;
    }
}
