<?php
/**
 * Link Intelligence Engine
 * 
 * Analyzes internal linking opportunities and provides smart recommendations.
 * Identifies orphan content, suggests anchor text, and optimizes link distribution.
 *
 * @package DODO_AI_SEO
 * @subpackage Brain
 * @since 2.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Prevent duplicate class declaration
if (class_exists('Dodo_Link_Intelligence')) {
    return;
}

class Dodo_Link_Intelligence {
    
    /**
     * Link opportunity thresholds
     */
    const OPPORTUNITY_EXCELLENT = 80;
    const OPPORTUNITY_GOOD = 60;
    const OPPORTUNITY_MODERATE = 40;
    
    /**
     * Link density thresholds
     */
    const LINK_DENSITY_OPTIMAL_MIN = 1.0;  // 1% of words
    const LINK_DENSITY_OPTIMAL_MAX = 3.0;  // 3% of words
    const LINK_DENSITY_EXCESSIVE = 5.0;    // 5%+ = spam risk
    
    /**
     * Semantic similarity threshold
     */
    const SEMANTIC_SIMILARITY_HIGH = 0.7;
    const SEMANTIC_SIMILARITY_MEDIUM = 0.4;
    
    /**
     * Debug mode
     */
    private $debug = true;
    
    /**
     * Analyze link opportunities for a post
     *
     * @param int $post_id Target post ID
     * @param array $options Analysis options
     * @return array Link intelligence with recommendations
     */
    public function analyze($post_id, $options = []) {
        $start_time = microtime(true);
        
        $defaults = [
            'max_recommendations' => 10,
            'min_relevance_score' => 50,
            'include_external' => false,
        ];
        $options = wp_parse_args($options, $defaults);
        
        $post = get_post($post_id);
        if (!$post) {
            return ['error' => 'Post not found'];
        }
        
        // Analyze current link status
        $current_links = $this->analyze_current_links($post);
        
        // Find linking opportunities
        $opportunities = $this->find_link_opportunities($post, $options);
        
        // Detect orphan status
        $orphan_status = $this->check_orphan_status($post_id);
        
        // Analyze link distribution
        $distribution = $this->analyze_link_distribution($post);
        
        // Generate anchor text suggestions
        $anchor_suggestions = $this->generate_anchor_suggestions($opportunities);
        
        // Calculate overall link health score
        $health_score = $this->calculate_link_health_score(
            $current_links,
            $orphan_status,
            $distribution
        );
        
        $execution_time = round((microtime(true) - $start_time) * 1000, 2);
        
        if ($this->debug) {
            error_log(sprintf(
                '[DODO Link Intelligence] Post: %d | Health: %d | Opportunities: %d | Orphan: %s | Time: %sms',
                $post_id,
                $health_score,
                count($opportunities),
                $orphan_status['is_orphan'] ? 'YES' : 'NO',
                $execution_time
            ));
        }
        
        return [
            'health_score' => $health_score,
            'current_links' => $current_links,
            'opportunities' => $opportunities,
            'orphan_status' => $orphan_status,
            'distribution' => $distribution,
            'anchor_suggestions' => $anchor_suggestions,
            'recommendations' => $this->generate_recommendations(
                $health_score,
                $current_links,
                $opportunities,
                $orphan_status
            ),
            'execution_time_ms' => $execution_time,
        ];
    }
    
    /**
     * Analyze current internal links in post
     */
    private function analyze_current_links($post) {
        $content = $post->post_content;
        $word_count = str_word_count(strip_tags($content));
        
        // Extract internal links
        preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/i', $content, $matches);
        
        $internal_links = [];
        $site_url = get_site_url();
        
        for ($i = 0; $i < count($matches[0]); $i++) {
            $url = $matches[1][$i];
            $anchor = strip_tags($matches[2][$i]);
            
            // Check if internal link
            if (strpos($url, $site_url) !== false || strpos($url, '/') === 0) {
                $internal_links[] = [
                    'url' => $url,
                    'anchor' => $anchor,
                    'type' => $this->classify_anchor_type($anchor),
                ];
            }
        }
        
        $link_count = count($internal_links);
        $link_density = $word_count > 0 ? ($link_count / $word_count) * 100 : 0;
        
        // Classify link density
        if ($link_density >= self::LINK_DENSITY_EXCESSIVE) {
            $density_status = 'excessive';
        } elseif ($link_density >= self::LINK_DENSITY_OPTIMAL_MIN && $link_density <= self::LINK_DENSITY_OPTIMAL_MAX) {
            $density_status = 'optimal';
        } elseif ($link_density > 0) {
            $density_status = 'low';
        } else {
            $density_status = 'none';
        }
        
        return [
            'count' => $link_count,
            'density' => round($link_density, 2),
            'density_status' => $density_status,
            'links' => $internal_links,
            'word_count' => $word_count,
        ];
    }
    
    /**
     * Classify anchor text type
     */
    private function classify_anchor_type($anchor) {
        $anchor_lower = strtolower(trim($anchor));
        
        // Generic anchors
        $generic_patterns = ['tıklayın', 'buraya', 'burada', 'devamı', 'click here', 'read more', 'here'];
        foreach ($generic_patterns as $pattern) {
            if (strpos($anchor_lower, $pattern) !== false) {
                return 'generic';
            }
        }
        
        // Branded anchors
        if (strlen($anchor) < 20 && preg_match('/^[A-Z]/', $anchor)) {
            return 'branded';
        }
        
        // Exact match (likely keyword-rich)
        if (strlen($anchor) > 20 && strlen($anchor) < 60) {
            return 'keyword_rich';
        }
        
        // Long descriptive
        if (strlen($anchor) > 60) {
            return 'descriptive';
        }
        
        return 'natural';
    }
    
    /**
     * Find link opportunities
     */
    private function find_link_opportunities($post, $options) {
        $opportunities = [];
        
        // Get post keywords and entities
        $post_keywords = $this->extract_keywords($post->post_content);
        $post_title_words = explode(' ', strtolower($post->post_title));
        
        // Find related posts
        $args = [
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => 50,
            'post__not_in' => [$post->ID],
            'orderby' => 'date',
            'order' => 'DESC',
        ];
        
        $related_query = new WP_Query($args);
        
        if ($related_query->have_posts()) {
            while ($related_query->have_posts()) {
                $related_query->the_post();
                $related_id = get_the_ID();
                $related_title = get_the_title();
                $related_content = get_post_field('post_content', $related_id);
                
                // Calculate relevance score
                $relevance = $this->calculate_relevance(
                    $post_keywords,
                    $post_title_words,
                    $related_title,
                    $related_content
                );
                
                if ($relevance >= $options['min_relevance_score']) {
                    // Suggest anchor text
                    $suggested_anchor = $this->suggest_anchor_text(
                        $related_title,
                        $post_keywords
                    );
                    
                    // Find best placement context
                    $placement_context = $this->find_placement_context(
                        $post->post_content,
                        $related_title,
                        $post_keywords
                    );
                    
                    $opportunities[] = [
                        'target_post_id' => $related_id,
                        'target_title' => $related_title,
                        'target_url' => get_permalink($related_id),
                        'relevance_score' => $relevance,
                        'suggested_anchor' => $suggested_anchor,
                        'placement_context' => $placement_context,
                        'reason' => $this->explain_opportunity($relevance, $post_keywords, $related_title),
                    ];
                }
            }
            wp_reset_postdata();
        }
        
        // Sort by relevance
        usort($opportunities, function($a, $b) {
            return $b['relevance_score'] - $a['relevance_score'];
        });
        
        // Limit results
        return array_slice($opportunities, 0, $options['max_recommendations']);
    }
    
    /**
     * Extract keywords from content
     */
    private function extract_keywords($content) {
        $text = strtolower(strip_tags($content));
        
        // Remove common stop words
        $stop_words = ['bir', 've', 'veya', 'ile', 'için', 'bu', 'şu', 'the', 'and', 'or', 'with', 'for'];
        $words = str_word_count($text, 1);
        
        $keywords = [];
        foreach ($words as $word) {
            if (strlen($word) >= 4 && !in_array($word, $stop_words)) {
                if (!isset($keywords[$word])) {
                    $keywords[$word] = 0;
                }
                $keywords[$word]++;
            }
        }
        
        // Sort by frequency
        arsort($keywords);
        
        // Return top 20 keywords
        return array_slice(array_keys($keywords), 0, 20);
    }
    
    /**
     * Calculate relevance between posts
     */
    private function calculate_relevance($source_keywords, $source_title_words, $target_title, $target_content) {
        $score = 0;
        $target_title_lower = strtolower($target_title);
        $target_content_lower = strtolower(strip_tags($target_content));
        
        // Title word overlap (40% weight)
        $title_overlap = 0;
        foreach ($source_title_words as $word) {
            if (strlen($word) >= 4 && strpos($target_title_lower, $word) !== false) {
                $title_overlap++;
            }
        }
        $score += min(($title_overlap / max(count($source_title_words), 1)) * 100, 40);
        
        // Keyword overlap (60% weight)
        $keyword_overlap = 0;
        foreach ($source_keywords as $keyword) {
            if (strpos($target_content_lower, $keyword) !== false) {
                $keyword_overlap++;
            }
        }
        $score += min(($keyword_overlap / max(count($source_keywords), 1)) * 100, 60);
        
        return round($score);
    }
    
    /**
     * Suggest anchor text
     */
    private function suggest_anchor_text($target_title, $source_keywords) {
        // Use title if short enough
        if (strlen($target_title) <= 60) {
            return $target_title;
        }
        
        // Try to create natural anchor from title + keyword
        $title_words = explode(' ', $target_title);
        $short_title = implode(' ', array_slice($title_words, 0, 5));
        
        return $short_title;
    }
    
    /**
     * Find best placement context in content
     */
    private function find_placement_context($content, $target_title, $keywords) {
        $sentences = preg_split('/[.!?]+/', strip_tags($content));
        $best_sentence = '';
        $best_score = 0;
        
        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);
            if (strlen($sentence) < 20) continue;
            
            $score = 0;
            $sentence_lower = strtolower($sentence);
            
            // Check keyword presence
            foreach ($keywords as $keyword) {
                if (strpos($sentence_lower, $keyword) !== false) {
                    $score++;
                }
            }
            
            if ($score > $best_score) {
                $best_score = $score;
                $best_sentence = $sentence;
            }
        }
        
        return $best_sentence ? substr($best_sentence, 0, 150) . '...' : '';
    }
    
    /**
     * Explain why this is a good opportunity
     */
    private function explain_opportunity($relevance, $keywords, $target_title) {
        if ($relevance >= 80) {
            return 'Highly relevant content with strong keyword overlap';
        } elseif ($relevance >= 60) {
            return 'Good topical relevance and semantic connection';
        } else {
            return 'Moderate relevance, consider for cluster building';
        }
    }
    
    /**
     * Check if post is orphan (no incoming links)
     */
    private function check_orphan_status($post_id) {
        global $wpdb;
        
        $post_url = get_permalink($post_id);
        $post_slug = basename($post_url);
        
        // Search for links to this post in other posts
        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} 
            WHERE post_status = 'publish' 
            AND post_type = 'post'
            AND ID != %d
            AND (post_content LIKE %s OR post_content LIKE %s)",
            $post_id,
            '%' . $wpdb->esc_like($post_url) . '%',
            '%' . $wpdb->esc_like($post_slug) . '%'
        );
        
        $incoming_links = $wpdb->get_var($query);
        
        return [
            'is_orphan' => $incoming_links == 0,
            'incoming_links' => (int) $incoming_links,
            'risk_level' => $incoming_links == 0 ? 'high' : ($incoming_links < 3 ? 'medium' : 'low'),
        ];
    }
    
    /**
     * Analyze link distribution across content
     */
    private function analyze_link_distribution($post) {
        $content = $post->post_content;
        
        // Split content into sections (by headings)
        $sections = preg_split('/<h[2-3][^>]*>.*?<\/h[2-3]>/i', $content);
        
        $distribution = [];
        foreach ($sections as $index => $section) {
            $link_count = substr_count($section, '<a ');
            $word_count = str_word_count(strip_tags($section));
            
            $distribution[] = [
                'section' => $index + 1,
                'links' => $link_count,
                'words' => $word_count,
                'density' => $word_count > 0 ? round(($link_count / $word_count) * 100, 2) : 0,
            ];
        }
        
        // Check if links are concentrated in one section
        $link_counts = array_column($distribution, 'links');
        $max_links = max($link_counts);
        $total_links = array_sum($link_counts);
        
        $is_balanced = $total_links > 0 ? ($max_links / $total_links) < 0.5 : true;
        
        return [
            'sections' => $distribution,
            'is_balanced' => $is_balanced,
            'balance_score' => $is_balanced ? 100 : 50,
        ];
    }
    
    /**
     * Generate anchor text suggestions for opportunities
     */
    private function generate_anchor_suggestions($opportunities) {
        $suggestions = [];
        
        foreach ($opportunities as $opp) {
            $suggestions[] = [
                'target_post_id' => $opp['target_post_id'],
                'anchors' => [
                    'natural' => $opp['suggested_anchor'],
                    'keyword_rich' => $opp['target_title'],
                    'generic' => 'Devamını oku',
                ],
                'recommended' => 'natural', // Always prefer natural anchors
            ];
        }
        
        return $suggestions;
    }
    
    /**
     * Calculate link health score
     */
    private function calculate_link_health_score($current_links, $orphan_status, $distribution) {
        $score = 0;
        
        // Link density (30% weight)
        if ($current_links['density_status'] === 'optimal') {
            $score += 30;
        } elseif ($current_links['density_status'] === 'low') {
            $score += 15;
        } elseif ($current_links['density_status'] === 'excessive') {
            $score += 5;
        }
        
        // Orphan status (40% weight)
        if (!$orphan_status['is_orphan']) {
            $score += 40;
        } elseif ($orphan_status['incoming_links'] > 0) {
            $score += 20;
        }
        
        // Distribution balance (30% weight)
        $score += $distribution['balance_score'] * 0.3;
        
        return round($score);
    }
    
    /**
     * Generate recommendations
     */
    private function generate_recommendations($health_score, $current_links, $opportunities, $orphan_status) {
        $recommendations = [];
        
        // Overall health
        if ($health_score >= 80) {
            $recommendations[] = [
                'type' => 'maintain',
                'priority' => 'low',
                'action' => 'Link health excellent. Maintain current strategy.',
            ];
        } elseif ($health_score >= 60) {
            $recommendations[] = [
                'type' => 'optimize',
                'priority' => 'medium',
                'action' => 'Good link health. Consider adding 2-3 more internal links.',
            ];
        } else {
            $recommendations[] = [
                'type' => 'improve',
                'priority' => 'high',
                'action' => 'Weak link health. Immediate action needed.',
            ];
        }
        
        // Density recommendations
        if ($current_links['density_status'] === 'none') {
            $recommendations[] = [
                'type' => 'add_links',
                'priority' => 'high',
                'action' => sprintf(
                    'No internal links found. Add %d-%d relevant links.',
                    max(1, floor($current_links['word_count'] * 0.01)),
                    max(2, floor($current_links['word_count'] * 0.02))
                ),
            ];
        } elseif ($current_links['density_status'] === 'low') {
            $recommendations[] = [
                'type' => 'add_links',
                'priority' => 'medium',
                'action' => 'Link density low. Add 2-3 more internal links.',
            ];
        } elseif ($current_links['density_status'] === 'excessive') {
            $recommendations[] = [
                'type' => 'reduce_links',
                'priority' => 'high',
                'action' => 'Link density excessive (spam risk). Remove low-value links.',
            ];
        }
        
        // Orphan recommendations
        if ($orphan_status['is_orphan']) {
            $recommendations[] = [
                'type' => 'fix_orphan',
                'priority' => 'high',
                'action' => 'Orphan content detected. Get links from 3+ related posts.',
            ];
        }
        
        // Opportunity recommendations
        if (!empty($opportunities)) {
            $top_opportunity = $opportunities[0];
            $recommendations[] = [
                'type' => 'link_opportunity',
                'priority' => 'medium',
                'action' => sprintf(
                    'Top opportunity: Link to "%s" (relevance: %d%%)',
                    $top_opportunity['target_title'],
                    $top_opportunity['relevance_score']
                ),
                'target_post_id' => $top_opportunity['target_post_id'],
            ];
        }
        
        return $recommendations;
    }
}
