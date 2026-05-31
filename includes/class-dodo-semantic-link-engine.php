<?php
/**
 * Semantic Internal Link Engine V2
 * 
 * Advanced internal linking system with semantic similarity,
 * contextual relevance, and topical authority analysis.
 * 
 * @package DODO_AI_SEO
 * @since 2.0.0 (Sprint 3 - Task 4)
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Semantic_Link_Engine {
    
    /**
     * Get semantic link suggestions for content
     * 
     * @param int $post_id Current post ID
     * @param string $content Post content
     * @param string $focus_keyword Focus keyword
     * @return array Link suggestions with scores and explanations
     */
    public function get_link_suggestions($post_id, $content, $focus_keyword = '') {
        // Get all published posts except current
        $posts = $this->get_linkable_posts($post_id);
        
        if (empty($posts)) {
            return array();
        }
        
        // Extract semantic features from current content
        $current_features = $this->extract_semantic_features($content, $focus_keyword);
        
        // Calculate similarity scores for each post
        $suggestions = array();
        
        foreach ($posts as $post) {
            $target_features = $this->extract_semantic_features($post->post_content, get_post_meta($post->ID, '_rank_math_focus_keyword', true));
            
            // Calculate semantic similarity
            $semantic_score = $this->calculate_semantic_similarity($current_features, $target_features);
            
            // Calculate contextual relevance
            $contextual_score = $this->calculate_contextual_relevance($current_features, $target_features);
            
            // Calculate topical relation
            $topical_score = $this->calculate_topical_relation($current_features, $target_features);
            
            // Calculate authority flow potential
            $authority_score = $this->calculate_authority_potential($post->ID);
            
            // Combined confidence score
            $confidence = ($semantic_score * 0.35) + ($contextual_score * 0.30) + ($topical_score * 0.25) + ($authority_score * 0.10);
            
            // Only suggest if confidence > 40%
            if ($confidence < 40) {
                continue;
            }
            
            // Generate anchor text suggestions
            $anchor_suggestions = $this->generate_anchor_suggestions($post, $target_features, $current_features);
            
            // Generate explanation
            $explanation = $this->generate_link_explanation($semantic_score, $contextual_score, $topical_score, $target_features);
            
            $suggestions[] = array(
                'post_id' => $post->ID,
                'post_title' => $post->post_title,
                'post_url' => get_permalink($post->ID),
                'confidence_score' => round($confidence, 1),
                'semantic_score' => round($semantic_score, 1),
                'contextual_score' => round($contextual_score, 1),
                'topical_score' => round($topical_score, 1),
                'authority_score' => round($authority_score, 1),
                'anchor_suggestions' => $anchor_suggestions,
                'explanation' => $explanation,
                'topical_relation' => $this->get_topical_relation_label($topical_score),
                'anchor_quality' => $this->get_anchor_quality_label($anchor_suggestions),
                'link_type' => $this->determine_link_type($semantic_score, $contextual_score),
            );
        }
        
        // Sort by confidence score
        usort($suggestions, function($a, $b) {
            return $b['confidence_score'] <=> $a['confidence_score'];
        });
        
        // Return top 10 suggestions
        return array_slice($suggestions, 0, 10);
    }
    
    /**
     * Get linkable posts (published, not current)
     */
    private function get_linkable_posts($exclude_id) {
        $args = array(
            'post_type' => array('post', 'page'),
            'post_status' => 'publish',
            'posts_per_page' => 100,
            'post__not_in' => array($exclude_id),
            'orderby' => 'date',
            'order' => 'DESC',
        );
        
        $query = new WP_Query($args);
        return $query->posts;
    }
    
    /**
     * Extract semantic features from content
     */
    private function extract_semantic_features($content, $focus_keyword = '') {
        $text = strip_tags($content);
        $text = strtolower($text);
        
        // Extract keywords (simple TF-IDF approximation)
        $words = str_word_count($text, 1);
        $word_freq = array_count_values($words);
        
        // Remove common stop words
        $stop_words = array('the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'from', 'as', 'is', 'was', 'are', 'were', 'been', 'be', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could', 'should', 'may', 'might', 'can', 'this', 'that', 'these', 'those', 'i', 'you', 'he', 'she', 'it', 'we', 'they', 'what', 'which', 'who', 'when', 'where', 'why', 'how');
        
        foreach ($stop_words as $stop) {
            unset($word_freq[$stop]);
        }
        
        // Get top keywords
        arsort($word_freq);
        $keywords = array_slice(array_keys($word_freq), 0, 20);
        
        // Extract entities (simple pattern matching)
        $entities = $this->extract_entities($text);
        
        // Extract topics (based on keyword clustering)
        $topics = $this->extract_topics($keywords);
        
        return array(
            'keywords' => $keywords,
            'word_freq' => $word_freq,
            'entities' => $entities,
            'topics' => $topics,
            'focus_keyword' => strtolower($focus_keyword),
            'word_count' => count($words),
        );
    }
    
    /**
     * Extract entities (simple pattern matching)
     */
    private function extract_entities($text) {
        $entities = array();
        
        // Extract capitalized words (potential entities)
        preg_match_all('/\b[A-Z][a-z]+(?:\s+[A-Z][a-z]+)*\b/', $text, $matches);
        
        if (!empty($matches[0])) {
            $entities = array_unique($matches[0]);
        }
        
        return $entities;
    }
    
    /**
     * Extract topics (simple clustering)
     */
    private function extract_topics($keywords) {
        // Group related keywords into topics
        // This is a simplified version - production would use NLP
        $topics = array();
        
        // Technology related
        $tech_words = array('technology', 'software', 'digital', 'online', 'web', 'app', 'code', 'data', 'ai', 'machine', 'learning', 'algorithm');
        $tech_count = count(array_intersect($keywords, $tech_words));
        if ($tech_count > 0) {
            $topics[] = 'technology';
        }
        
        // Business related
        $business_words = array('business', 'marketing', 'sales', 'strategy', 'growth', 'revenue', 'customer', 'market', 'brand', 'company');
        $business_count = count(array_intersect($keywords, $business_words));
        if ($business_count > 0) {
            $topics[] = 'business';
        }
        
        // Content related
        $content_words = array('content', 'writing', 'blog', 'article', 'post', 'seo', 'keyword', 'search', 'optimization');
        $content_count = count(array_intersect($keywords, $content_words));
        if ($content_count > 0) {
            $topics[] = 'content';
        }
        
        return $topics;
    }
    
    /**
     * Calculate semantic similarity (Jaccard similarity)
     */
    private function calculate_semantic_similarity($features1, $features2) {
        $keywords1 = $features1['keywords'];
        $keywords2 = $features2['keywords'];
        
        $intersection = count(array_intersect($keywords1, $keywords2));
        $union = count(array_unique(array_merge($keywords1, $keywords2)));
        
        if ($union == 0) {
            return 0;
        }
        
        $jaccard = ($intersection / $union) * 100;
        
        // Boost if focus keywords match
        if (!empty($features1['focus_keyword']) && !empty($features2['focus_keyword'])) {
            if ($features1['focus_keyword'] === $features2['focus_keyword']) {
                $jaccard = min(100, $jaccard * 1.3);
            }
        }
        
        return $jaccard;
    }
    
    /**
     * Calculate contextual relevance
     */
    private function calculate_contextual_relevance($features1, $features2) {
        $score = 0;
        
        // Topic overlap
        $topic_overlap = count(array_intersect($features1['topics'], $features2['topics']));
        $score += ($topic_overlap * 25);
        
        // Entity overlap
        $entity_overlap = count(array_intersect($features1['entities'], $features2['entities']));
        $score += min(30, $entity_overlap * 10);
        
        // Keyword density similarity
        $density_diff = abs($features1['word_count'] - $features2['word_count']);
        if ($density_diff < 500) {
            $score += 20;
        } elseif ($density_diff < 1000) {
            $score += 10;
        }
        
        return min(100, $score);
    }
    
    /**
     * Calculate topical relation
     */
    private function calculate_topical_relation($features1, $features2) {
        $topics1 = $features1['topics'];
        $topics2 = $features2['topics'];
        
        if (empty($topics1) || empty($topics2)) {
            return 50; // Neutral
        }
        
        $overlap = count(array_intersect($topics1, $topics2));
        $total = count(array_unique(array_merge($topics1, $topics2)));
        
        return ($overlap / $total) * 100;
    }
    
    /**
     * Calculate authority potential
     */
    private function calculate_authority_potential($post_id) {
        // Check if post has high engagement (comments, views)
        $comment_count = wp_count_comments($post_id)->approved;
        
        // Check post age (older posts have more authority)
        $post_date = get_post_time('U', false, $post_id);
        $age_days = (time() - $post_date) / DAY_IN_SECONDS;
        
        $score = 50; // Base score
        
        // Comment boost
        if ($comment_count > 10) {
            $score += 20;
        } elseif ($comment_count > 5) {
            $score += 10;
        }
        
        // Age boost
        if ($age_days > 180) {
            $score += 20;
        } elseif ($age_days > 90) {
            $score += 10;
        }
        
        return min(100, $score);
    }
    
    /**
     * Generate anchor text suggestions
     */
    private function generate_anchor_suggestions($post, $target_features, $current_features) {
        $suggestions = array();
        
        // Post title (natural)
        $suggestions[] = array(
            'text' => $post->post_title,
            'type' => 'natural',
            'quality' => 'high',
        );
        
        // Focus keyword (if available)
        if (!empty($target_features['focus_keyword'])) {
            $suggestions[] = array(
                'text' => $target_features['focus_keyword'],
                'type' => 'keyword',
                'quality' => 'medium',
            );
        }
        
        // Top keyword from target
        if (!empty($target_features['keywords'][0])) {
            $suggestions[] = array(
                'text' => $target_features['keywords'][0],
                'type' => 'semantic',
                'quality' => 'medium',
            );
        }
        
        return $suggestions;
    }
    
    /**
     * Generate link explanation
     */
    private function generate_link_explanation($semantic_score, $contextual_score, $topical_score, $target_features) {
        $reasons = array();
        
        if ($semantic_score > 60) {
            $reasons[] = 'Yüksek semantik benzerlik';
        }
        
        if ($contextual_score > 60) {
            $reasons[] = 'Güçlü bağlamsal ilişki';
        }
        
        if ($topical_score > 70) {
            $reasons[] = 'Aynı konu kümesinde';
        }
        
        if (!empty($target_features['focus_keyword'])) {
            $reasons[] = 'İlgili anahtar kelime: ' . $target_features['focus_keyword'];
        }
        
        if (empty($reasons)) {
            $reasons[] = 'İçerik ilişkisi tespit edildi';
        }
        
        return implode(' • ', $reasons);
    }
    
    /**
     * Get topical relation label
     */
    private function get_topical_relation_label($score) {
        if ($score >= 80) return 'Çok Güçlü';
        if ($score >= 60) return 'Güçlü';
        if ($score >= 40) return 'Orta';
        return 'Zayıf';
    }
    
    /**
     * Get anchor quality label
     */
    private function get_anchor_quality_label($anchors) {
        if (empty($anchors)) return 'Düşük';
        
        $high_quality = 0;
        foreach ($anchors as $anchor) {
            if ($anchor['quality'] === 'high') {
                $high_quality++;
            }
        }
        
        if ($high_quality >= 2) return 'Yüksek';
        if ($high_quality >= 1) return 'Orta';
        return 'Düşük';
    }
    
    /**
     * Determine link type
     */
    private function determine_link_type($semantic_score, $contextual_score) {
        if ($semantic_score > 70 && $contextual_score > 70) {
            return 'pillar'; // Strong topical connection
        }
        
        if ($semantic_score > 60) {
            return 'supporting'; // Supporting content
        }
        
        return 'related'; // Related content
    }
    
    /**
     * Detect orphan pages (pages with no internal links)
     */
    public function detect_orphan_pages() {
        global $wpdb;
        
        $orphans = array();
        
        // Get all published posts
        $posts = get_posts(array(
            'post_type' => array('post', 'page'),
            'post_status' => 'publish',
            'posts_per_page' => -1,
        ));
        
        foreach ($posts as $post) {
            // Check if post is linked from any other post
            $link_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts} 
                WHERE post_status = 'publish' 
                AND post_content LIKE %s 
                AND ID != %d",
                '%' . $wpdb->esc_like(get_permalink($post->ID)) . '%',
                $post->ID
            ));
            
            if ($link_count == 0) {
                $orphans[] = array(
                    'post_id' => $post->ID,
                    'post_title' => $post->post_title,
                    'post_url' => get_permalink($post->ID),
                    'post_date' => $post->post_date,
                    'severity' => 'high',
                );
            }
        }
        
        return $orphans;
    }
    
    /**
     * Analyze authority flow
     */
    public function analyze_authority_flow($post_id) {
        global $wpdb;
        
        // Count outbound internal links
        $post = get_post($post_id);
        $content = $post->post_content;
        
        preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>/i', $content, $matches);
        
        $internal_links = 0;
        $external_links = 0;
        
        foreach ($matches[1] as $url) {
            if (strpos($url, home_url()) !== false) {
                $internal_links++;
            } else {
                $external_links++;
            }
        }
        
        // Count inbound internal links
        $inbound_links = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} 
            WHERE post_status = 'publish' 
            AND post_content LIKE %s 
            AND ID != %d",
            '%' . $wpdb->esc_like(get_permalink($post_id)) . '%',
            $post_id
        ));
        
        // Calculate authority flow score
        $flow_score = 50; // Base
        
        if ($internal_links > 5) {
            $flow_score += 20;
        } elseif ($internal_links > 2) {
            $flow_score += 10;
        }
        
        if ($inbound_links > 5) {
            $flow_score += 20;
        } elseif ($inbound_links > 2) {
            $flow_score += 10;
        }
        
        // Penalty for too many external links
        if ($external_links > $internal_links * 2) {
            $flow_score -= 10;
        }
        
        return array(
            'flow_score' => min(100, max(0, $flow_score)),
            'internal_links' => $internal_links,
            'external_links' => $external_links,
            'inbound_links' => $inbound_links,
            'flow_label' => $this->get_flow_label($flow_score),
        );
    }
    
    /**
     * Get flow label
     */
    private function get_flow_label($score) {
        if ($score >= 80) return 'Mükemmel';
        if ($score >= 60) return 'İyi';
        if ($score >= 40) return 'Orta';
        return 'Zayıf';
    }
}
