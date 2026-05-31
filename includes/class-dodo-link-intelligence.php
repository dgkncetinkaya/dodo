<?php
/**
 * Real Internal Link Intelligence
 * 
 * Semantic link recommendations based on content similarity
 * Authority flow, orphan recovery, cluster strengthening
 *
 * @package DODO_AI_SEO
 * @since 2.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Link_Intelligence {
    
    /**
     * Link recommendation thresholds
     */
    const MIN_SEMANTIC_SIMILARITY = 0.50; // 50% similarity minimum
    const OPTIMAL_LINKS_PER_POST = 5;
    const MAX_LINKS_PER_POST = 10;
    const AUTHORITY_THRESHOLD = 60;
    
    /**
     * Semantic engine
     */
    private $semantic_engine;
    
    /**
     * Topic graph
     */
    private $topic_graph;
    
    /**
     * Constructor
     */
    public function __construct() {
        require_once plugin_dir_path(__FILE__) . 'class-dodo-semantic-engine.php';
        require_once plugin_dir_path(__FILE__) . 'class-dodo-topic-graph.php';
        
        $this->semantic_engine = new DODO_Semantic_Engine();
        $this->topic_graph = new DODO_Topic_Graph();
    }
    
    /**
     * Generate link recommendations for post
     *
     * @param int $post_id Post ID
     * @param array $options Recommendation options
     * @return array Link recommendations
     */
    public function generate_recommendations($post_id, $options = []) {
        $start_time = microtime(true);
        
        error_log("[DODO Link Intelligence] Generating recommendations for post {$post_id}");
        
        $defaults = [
            'max_recommendations' => self::OPTIMAL_LINKS_PER_POST,
            'min_similarity' => self::MIN_SEMANTIC_SIMILARITY,
            'include_orphans' => true,
            'boost_authority' => true,
        ];
        $options = wp_parse_args($options, $defaults);
        
        $post = get_post($post_id);
        if (!$post) {
            return ['error' => 'Post not found'];
        }
        
        $post_content = $post->post_content;
        $post_title = $post->post_title;
        
        // Find semantically similar posts
        $similar_posts = $this->semantic_engine->find_similar_posts(
            $post_id,
            20,
            $options['min_similarity']
        );
        
        // Get existing links
        $existing_links = $this->extract_existing_links($post_content);
        
        // Filter out already linked posts
        $similar_posts = array_filter($similar_posts, function($similar) use ($existing_links) {
            return !in_array($similar['post_id'], $existing_links);
        });
        
        // Build graph for authority analysis
        $this->topic_graph->build_graph(['post_limit' => 50]);
        
        // Score and rank recommendations
        $recommendations = [];
        
        foreach ($similar_posts as $similar) {
            $target_post = get_post($similar['post_id']);
            
            if (!$target_post) {
                continue;
            }
            
            // Calculate recommendation score
            $score = $this->calculate_recommendation_score(
                $post_id,
                $similar['post_id'],
                $similar['similarity'],
                $options
            );
            
            // Generate anchor text
            $anchor_suggestions = $this->generate_anchor_text(
                $post_content,
                $target_post->post_title,
                $target_post->post_content
            );
            
            // Find optimal placement
            $placement = $this->suggest_placement($post_content, $target_post->post_title);
            
            $recommendations[] = [
                'target_post_id' => $similar['post_id'],
                'target_title' => $target_post->post_title,
                'target_url' => get_permalink($similar['post_id']),
                'semantic_similarity' => round($similar['similarity'] * 100, 2),
                'recommendation_score' => $score,
                'anchor_suggestions' => $anchor_suggestions,
                'placement_suggestion' => $placement,
                'reason' => $this->explain_recommendation($score, $similar['similarity']),
            ];
        }
        
        // Sort by recommendation score
        usort($recommendations, function($a, $b) {
            return $b['recommendation_score'] - $a['recommendation_score'];
        });
        
        // Limit to max recommendations
        $recommendations = array_slice($recommendations, 0, $options['max_recommendations']);
        
        $execution_time = round((microtime(true) - $start_time) * 1000, 2);
        
        error_log(sprintf(
            '[DODO Link Intelligence] Generated %d recommendations (%sms)',
            count($recommendations),
            $execution_time
        ));
        
        return [
            'post_id' => $post_id,
            'post_title' => $post_title,
            'recommendations' => $recommendations,
            'existing_links_count' => count($existing_links),
            'metadata' => [
                'execution_time_ms' => $execution_time,
                'analyzed_at' => current_time('mysql'),
            ],
        ];
    }
    
    /**
     * Extract existing internal links from content
     */
    private function extract_existing_links($content) {
        $site_url = get_site_url();
        $linked_post_ids = [];
        
        preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>/i', $content, $matches);
        
        foreach ($matches[1] as $url) {
            // Check if internal link
            if (strpos($url, $site_url) !== false || strpos($url, '/') === 0) {
                $post_id = url_to_postid($url);
                if ($post_id > 0) {
                    $linked_post_ids[] = $post_id;
                }
            }
        }
        
        return array_unique($linked_post_ids);
    }
    
    /**
     * Calculate recommendation score
     */
    private function calculate_recommendation_score($source_post_id, $target_post_id, $similarity, $options) {
        $score = 0;
        
        // Base score from semantic similarity (0-50 points)
        $score += $similarity * 50;
        
        // Authority boost (0-25 points)
        if ($options['boost_authority']) {
            $authority = $this->topic_graph->calculate_authority($target_post_id);
            $score += ($authority / 100) * 25;
        }
        
        // Orphan recovery bonus (0-15 points)
        if ($options['include_orphans']) {
            $orphans = $this->topic_graph->detect_orphans();
            $is_orphan = false;
            
            foreach ($orphans as $orphan) {
                if ($orphan['post_id'] === $target_post_id) {
                    $is_orphan = true;
                    break;
                }
            }
            
            if ($is_orphan) {
                $score += 15;
            }
        }
        
        // Freshness bonus (0-10 points)
        $target_post = get_post($target_post_id);
        $days_old = (time() - strtotime($target_post->post_date)) / DAY_IN_SECONDS;
        
        if ($days_old < 30) {
            $score += 10;
        } elseif ($days_old < 90) {
            $score += 5;
        }
        
        return min(100, round($score));
    }
    
    /**
     * Generate anchor text suggestions
     */
    private function generate_anchor_text($source_content, $target_title, $target_content) {
        $anchors = [];
        
        // 1. Natural phrase from target title
        $title_words = explode(' ', $target_title);
        if (count($title_words) <= 5) {
            $anchors[] = [
                'text' => $target_title,
                'type' => 'exact_title',
                'geo_friendly' => true,
            ];
        }
        
        // 2. Partial match (first 3-4 words)
        if (count($title_words) > 3) {
            $partial = implode(' ', array_slice($title_words, 0, 4));
            $anchors[] = [
                'text' => $partial,
                'type' => 'partial_title',
                'geo_friendly' => true,
            ];
        }
        
        // 3. Extract key entities from target
        $entities = $this->semantic_engine->extract_entities($target_content);
        
        foreach ($entities['concepts'] as $concept) {
            if (strlen($concept) > 5 && stripos($source_content, $concept) !== false) {
                $anchors[] = [
                    'text' => $concept,
                    'type' => 'entity_match',
                    'geo_friendly' => true,
                ];
                
                if (count($anchors) >= 5) {
                    break;
                }
            }
        }
        
        // 4. Contextual phrase (find natural mention in source)
        $contextual = $this->find_contextual_anchor($source_content, $target_title);
        if ($contextual) {
            $anchors[] = [
                'text' => $contextual,
                'type' => 'contextual',
                'geo_friendly' => true,
            ];
        }
        
        // 5. Generic but natural (avoid "click here")
        $generic_natural = [
            'bu konuda daha fazla bilgi',
            'detaylı rehber',
            'kapsamlı açıklama',
            'ilgili makale',
        ];
        
        $anchors[] = [
            'text' => $generic_natural[array_rand($generic_natural)],
            'type' => 'generic_natural',
            'geo_friendly' => true,
        ];
        
        return array_slice($anchors, 0, 5);
    }
    
    /**
     * Find contextual anchor from source content
     */
    private function find_contextual_anchor($source_content, $target_title) {
        $text = strip_tags($source_content);
        $title_words = explode(' ', mb_strtolower($target_title, 'UTF-8'));
        
        // Find sentences containing title words
        $sentences = preg_split('/[.!?]+/', $text);
        
        foreach ($sentences as $sentence) {
            $sentence_lower = mb_strtolower($sentence, 'UTF-8');
            $matches = 0;
            
            foreach ($title_words as $word) {
                if (strlen($word) > 3 && stripos($sentence_lower, $word) !== false) {
                    $matches++;
                }
            }
            
            // If sentence contains multiple title words, extract phrase
            if ($matches >= 2) {
                $words = explode(' ', trim($sentence));
                
                // Extract 3-5 word phrase
                if (count($words) >= 3) {
                    $phrase = implode(' ', array_slice($words, 0, min(5, count($words))));
                    return $phrase;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Suggest optimal placement in content
     */
    private function suggest_placement($content, $target_title) {
        $text = strip_tags($content);
        $paragraphs = preg_split('/\n\s*\n/', $text);
        
        $title_words = explode(' ', mb_strtolower($target_title, 'UTF-8'));
        $best_paragraph = 0;
        $best_score = 0;
        
        foreach ($paragraphs as $index => $paragraph) {
            $para_lower = mb_strtolower($paragraph, 'UTF-8');
            $score = 0;
            
            // Score based on keyword presence
            foreach ($title_words as $word) {
                if (strlen($word) > 3 && stripos($para_lower, $word) !== false) {
                    $score++;
                }
            }
            
            // Prefer middle paragraphs (not intro, not conclusion)
            if ($index > 0 && $index < count($paragraphs) - 1) {
                $score += 2;
            }
            
            if ($score > $best_score) {
                $best_score = $score;
                $best_paragraph = $index;
            }
        }
        
        return [
            'paragraph_index' => $best_paragraph,
            'position' => $this->get_position_label($best_paragraph, count($paragraphs)),
            'confidence' => min(100, $best_score * 20),
        ];
    }
    
    /**
     * Get position label
     */
    private function get_position_label($index, $total) {
        $ratio = $index / max(1, $total);
        
        if ($ratio < 0.25) {
            return 'early';
        } elseif ($ratio < 0.5) {
            return 'early-middle';
        } elseif ($ratio < 0.75) {
            return 'late-middle';
        } else {
            return 'conclusion';
        }
    }
    
    /**
     * Explain recommendation
     */
    private function explain_recommendation($score, $similarity) {
        $reasons = [];
        
        if ($similarity >= 0.75) {
            $reasons[] = 'Highly related content';
        } elseif ($similarity >= 0.60) {
            $reasons[] = 'Related content';
        } else {
            $reasons[] = 'Somewhat related content';
        }
        
        if ($score >= 80) {
            $reasons[] = 'Strong recommendation';
        } elseif ($score >= 60) {
            $reasons[] = 'Good recommendation';
        } else {
            $reasons[] = 'Moderate recommendation';
        }
        
        return implode(' - ', $reasons);
    }
    
    /**
     * Detect orphan posts needing links
     *
     * @return array Orphan posts
     */
    public function detect_orphans() {
        $this->topic_graph->build_graph(['post_limit' => 100]);
        return $this->topic_graph->detect_orphans();
    }
    
    /**
     * Analyze link structure health
     *
     * @return array Link structure analysis
     */
    public function analyze_link_structure() {
        $posts = get_posts([
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => 100,
        ]);
        
        $stats = [
            'total_posts' => count($posts),
            'posts_with_links' => 0,
            'posts_without_links' => 0,
            'avg_links_per_post' => 0,
            'orphan_posts' => 0,
        ];
        
        $total_links = 0;
        
        foreach ($posts as $post) {
            $links = $this->extract_existing_links($post->post_content);
            $link_count = count($links);
            
            $total_links += $link_count;
            
            if ($link_count > 0) {
                $stats['posts_with_links']++;
            } else {
                $stats['posts_without_links']++;
            }
        }
        
        $stats['avg_links_per_post'] = $stats['total_posts'] > 0 
            ? round($total_links / $stats['total_posts'], 2) 
            : 0;
        
        $orphans = $this->detect_orphans();
        $stats['orphan_posts'] = count($orphans);
        
        return $stats;
    }
}
