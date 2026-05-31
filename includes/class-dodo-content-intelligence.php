<?php
/**
 * Content Intelligence Engine
 * 
 * Site-wide content intelligence: topic mapping, cannibalization detection,
 * internal link intelligence, and topical authority tracking
 * 
 * @package DODO_AI_SEO
 * @since 3.0.0 (Sprint 5 - Phase 3)
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Content_Intelligence {
    
    /**
     * OpenAI instance
     */
    private $openai;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->openai = new DODO_OpenAI();
    }
    
    /**
     * Analyze site-wide content intelligence
     * 
     * @return array Complete intelligence analysis
     */
    public function analyze_site() {
        $result = array(
            'topic_map' => $this->build_topic_map(),
            'cannibalization' => $this->detect_cannibalization(),
            'internal_links' => $this->analyze_internal_links(),
            'topical_authority' => $this->track_topical_authority(),
            'analyzed_at' => current_time('mysql'),
        );
        
        // Save to database
        $this->save_analysis($result);
        
        return $result;
    }
    
    /**
     * Build topic map
     * 
     * @return array Topic map with pillars, support topics, clusters
     */
    public function build_topic_map() {
        // Get all published posts
        $posts = get_posts(array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
        ));
        
        if (empty($posts)) {
            return array(
                'pillar_topics' => array(),
                'support_topics' => array(),
                'semantic_clusters' => array(),
                'missing_topics' => array(),
            );
        }
        
        // Extract topics from posts
        $topics = array();
        foreach ($posts as $post) {
            $focus_keyword = get_post_meta($post->ID, 'rank_math_focus_keyword', true);
            $categories = wp_get_post_categories($post->ID, array('fields' => 'names'));
            
            if (!empty($focus_keyword)) {
                $topics[] = array(
                    'post_id' => $post->ID,
                    'title' => $post->post_title,
                    'focus_keyword' => $focus_keyword,
                    'categories' => $categories,
                    'word_count' => str_word_count(strip_tags($post->post_content)),
                    'date' => $post->post_date,
                );
            }
        }
        
        // Identify pillar topics (high post count, high authority)
        $pillar_topics = $this->identify_pillar_topics($topics);
        
        // Identify support topics
        $support_topics = $this->identify_support_topics($topics, $pillar_topics);
        
        // Build semantic clusters
        $semantic_clusters = $this->build_semantic_clusters($topics);
        
        // Identify missing topics
        $missing_topics = $this->identify_missing_topics($pillar_topics, $support_topics);
        
        return array(
            'pillar_topics' => $pillar_topics,
            'support_topics' => $support_topics,
            'semantic_clusters' => $semantic_clusters,
            'missing_topics' => $missing_topics,
            'total_posts' => count($posts),
            'total_topics' => count($topics),
        );
    }
    
    /**
     * Identify pillar topics
     */
    private function identify_pillar_topics($topics) {
        // Group by category
        $category_groups = array();
        
        foreach ($topics as $topic) {
            foreach ($topic['categories'] as $category) {
                if (!isset($category_groups[$category])) {
                    $category_groups[$category] = array();
                }
                $category_groups[$category][] = $topic;
            }
        }
        
        $pillar_topics = array();
        
        foreach ($category_groups as $category => $category_topics) {
            $post_count = count($category_topics);
            
            // Pillar criteria: 3+ posts
            if ($post_count >= 3) {
                $total_words = array_sum(array_column($category_topics, 'word_count'));
                $avg_words = $total_words / $post_count;
                
                // Calculate authority score
                $authority_score = min(100, ($post_count * 10) + ($avg_words / 50));
                
                // Determine cluster strength
                if ($post_count >= 10) {
                    $cluster_strength = 'strong';
                } elseif ($post_count >= 5) {
                    $cluster_strength = 'medium';
                } else {
                    $cluster_strength = 'weak';
                }
                
                $pillar_topics[] = array(
                    'topic' => $category,
                    'post_count' => $post_count,
                    'authority_score' => round($authority_score),
                    'cluster_strength' => $cluster_strength,
                    'avg_word_count' => round($avg_words),
                    'posts' => array_column($category_topics, 'post_id'),
                );
            }
        }
        
        // Sort by authority score
        usort($pillar_topics, function($a, $b) {
            return $b['authority_score'] - $a['authority_score'];
        });
        
        return $pillar_topics;
    }
    
    /**
     * Identify support topics
     */
    private function identify_support_topics($topics, $pillar_topics) {
        $pillar_categories = array_column($pillar_topics, 'topic');
        $support_topics = array();
        
        foreach ($topics as $topic) {
            // Find topics not in pillar categories
            $non_pillar_categories = array_diff($topic['categories'], $pillar_categories);
            
            foreach ($non_pillar_categories as $category) {
                if (!isset($support_topics[$category])) {
                    $support_topics[$category] = array(
                        'topic' => $category,
                        'post_count' => 0,
                        'posts' => array(),
                    );
                }
                
                $support_topics[$category]['post_count']++;
                $support_topics[$category]['posts'][] = $topic['post_id'];
            }
        }
        
        return array_values($support_topics);
    }
    
    /**
     * Build semantic clusters
     */
    private function build_semantic_clusters($topics) {
        // Group by keyword similarity
        $clusters = array();
        
        foreach ($topics as $topic) {
            $keyword = strtolower($topic['focus_keyword']);
            $words = explode(' ', $keyword);
            $main_word = $words[0]; // Use first word as cluster key
            
            if (!isset($clusters[$main_word])) {
                $clusters[$main_word] = array(
                    'cluster_name' => ucfirst($main_word),
                    'post_count' => 0,
                    'keywords' => array(),
                    'posts' => array(),
                );
            }
            
            $clusters[$main_word]['post_count']++;
            $clusters[$main_word]['keywords'][] = $keyword;
            $clusters[$main_word]['posts'][] = $topic['post_id'];
        }
        
        // Filter clusters with 2+ posts
        $clusters = array_filter($clusters, function($cluster) {
            return $cluster['post_count'] >= 2;
        });
        
        // Sort by post count
        usort($clusters, function($a, $b) {
            return $b['post_count'] - $a['post_count'];
        });
        
        return array_values($clusters);
    }
    
    /**
     * Identify missing topics
     */
    private function identify_missing_topics($pillar_topics, $support_topics) {
        $missing = array();
        
        // Analyze pillar topics for gaps
        foreach ($pillar_topics as $pillar) {
            if ($pillar['cluster_strength'] === 'weak') {
                $missing[] = array(
                    'type' => 'expand_pillar',
                    'topic' => $pillar['topic'],
                    'current_posts' => $pillar['post_count'],
                    'recommended_posts' => 5,
                    'priority' => 'high',
                    'reason' => 'Pillar topic needs more content for authority',
                );
            }
        }
        
        // Suggest related topics
        foreach ($pillar_topics as $pillar) {
            if ($pillar['post_count'] >= 5) {
                $missing[] = array(
                    'type' => 'related_topic',
                    'topic' => $pillar['topic'] . ' - Advanced',
                    'parent_topic' => $pillar['topic'],
                    'priority' => 'medium',
                    'reason' => 'Strong pillar can support advanced subtopics',
                );
            }
        }
        
        return $missing;
    }
    
    /**
     * Detect content cannibalization
     * 
     * @return array Cannibalization issues
     */
    public function detect_cannibalization() {
        $posts = get_posts(array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => -1,
        ));
        
        $issues = array();
        $checked_pairs = array();
        
        foreach ($posts as $post1) {
            $keyword1 = get_post_meta($post1->ID, 'rank_math_focus_keyword', true);
            
            if (empty($keyword1)) {
                continue;
            }
            
            foreach ($posts as $post2) {
                if ($post1->ID === $post2->ID) {
                    continue;
                }
                
                // Skip if pair already checked
                $pair_key = min($post1->ID, $post2->ID) . '-' . max($post1->ID, $post2->ID);
                if (isset($checked_pairs[$pair_key])) {
                    continue;
                }
                $checked_pairs[$pair_key] = true;
                
                $keyword2 = get_post_meta($post2->ID, 'rank_math_focus_keyword', true);
                
                if (empty($keyword2)) {
                    continue;
                }
                
                // Calculate keyword similarity
                $similarity = $this->calculate_keyword_similarity($keyword1, $keyword2);
                
                if ($similarity >= 70) {
                    $issue_type = 'duplicate_intent';
                    $recommendation = 'Consolidate or differentiate';
                } elseif ($similarity >= 50) {
                    $issue_type = 'overlap';
                    $recommendation = 'Review and differentiate';
                } else {
                    continue;
                }
                
                $issues[] = array(
                    'post_1' => array(
                        'id' => $post1->ID,
                        'title' => $post1->post_title,
                        'keyword' => $keyword1,
                    ),
                    'post_2' => array(
                        'id' => $post2->ID,
                        'title' => $post2->post_title,
                        'keyword' => $keyword2,
                    ),
                    'overlap_score' => $similarity,
                    'issue_type' => $issue_type,
                    'recommendation' => $recommendation,
                );
            }
        }
        
        // Sort by overlap score
        usort($issues, function($a, $b) {
            return $b['overlap_score'] - $a['overlap_score'];
        });
        
        return array(
            'total_issues' => count($issues),
            'high_priority' => count(array_filter($issues, function($i) { return $i['overlap_score'] >= 70; })),
            'medium_priority' => count(array_filter($issues, function($i) { return $i['overlap_score'] >= 50 && $i['overlap_score'] < 70; })),
            'issues' => array_slice($issues, 0, 20), // Limit to top 20
        );
    }
    
    /**
     * Calculate keyword similarity
     */
    private function calculate_keyword_similarity($keyword1, $keyword2) {
        $words1 = explode(' ', strtolower($keyword1));
        $words2 = explode(' ', strtolower($keyword2));
        
        $common = array_intersect($words1, $words2);
        $total = array_unique(array_merge($words1, $words2));
        
        if (empty($total)) {
            return 0;
        }
        
        return round((count($common) / count($total)) * 100);
    }
    
    /**
     * Analyze internal links
     * 
     * @return array Internal link analysis
     */
    public function analyze_internal_links() {
        $posts = get_posts(array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => -1,
        ));
        
        $link_data = array();
        $orphan_posts = array();
        
        foreach ($posts as $post) {
            $content = $post->post_content;
            
            // Count internal links
            preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>/i', $content, $matches);
            $links = $matches[1];
            
            $internal_link_count = 0;
            foreach ($links as $link) {
                if (strpos($link, home_url()) !== false) {
                    $internal_link_count++;
                }
            }
            
            $link_data[$post->ID] = array(
                'post_id' => $post->ID,
                'title' => $post->post_title,
                'internal_links' => $internal_link_count,
                'total_links' => count($links),
            );
            
            // Detect orphan posts (no internal links)
            if ($internal_link_count === 0) {
                $orphan_posts[] = array(
                    'post_id' => $post->ID,
                    'title' => $post->post_title,
                    'url' => get_permalink($post->ID),
                );
            }
        }
        
        // Calculate average
        $avg_internal_links = count($link_data) > 0 
            ? round(array_sum(array_column($link_data, 'internal_links')) / count($link_data), 1)
            : 0;
        
        return array(
            'total_posts' => count($posts),
            'avg_internal_links' => $avg_internal_links,
            'orphan_posts' => $orphan_posts,
            'orphan_count' => count($orphan_posts),
            'link_health' => count($orphan_posts) === 0 ? 'excellent' : (count($orphan_posts) < 5 ? 'good' : 'needs_improvement'),
        );
    }
    
    /**
     * Track topical authority
     * 
     * @return array Topical authority metrics
     */
    public function track_topical_authority() {
        $topic_map = $this->build_topic_map();
        $authority_scores = array();
        
        foreach ($topic_map['pillar_topics'] as $pillar) {
            $completeness = $this->calculate_cluster_completeness($pillar);
            
            $authority_scores[] = array(
                'topic' => $pillar['topic'],
                'authority_score' => $pillar['authority_score'],
                'cluster_completeness' => $completeness,
                'post_count' => $pillar['post_count'],
                'cluster_strength' => $pillar['cluster_strength'],
                'semantic_coverage' => $this->calculate_semantic_coverage($pillar),
            );
        }
        
        // Calculate overall authority
        $overall_authority = count($authority_scores) > 0
            ? round(array_sum(array_column($authority_scores, 'authority_score')) / count($authority_scores))
            : 0;
        
        return array(
            'overall_authority' => $overall_authority,
            'pillar_count' => count($authority_scores),
            'topics' => $authority_scores,
            'status' => $overall_authority >= 75 ? 'strong' : ($overall_authority >= 50 ? 'developing' : 'weak'),
        );
    }
    
    /**
     * Calculate cluster completeness
     */
    private function calculate_cluster_completeness($pillar) {
        $post_count = $pillar['post_count'];
        
        // Ideal cluster: 10+ posts
        $completeness = min(100, ($post_count / 10) * 100);
        
        return round($completeness);
    }
    
    /**
     * Calculate semantic coverage
     */
    private function calculate_semantic_coverage($pillar) {
        // Simplified: based on post count and diversity
        $post_count = $pillar['post_count'];
        
        if ($post_count >= 10) {
            return 90;
        } elseif ($post_count >= 5) {
            return 70;
        } elseif ($post_count >= 3) {
            return 50;
        } else {
            return 30;
        }
    }
    
    /**
     * Get link suggestions for a post
     * 
     * @param int $post_id Post ID
     * @return array Link suggestions
     */
    public function get_link_suggestions($post_id) {
        $post = get_post($post_id);
        
        if (!$post) {
            return array();
        }
        
        $focus_keyword = get_post_meta($post_id, 'rank_math_focus_keyword', true);
        $categories = wp_get_post_categories($post_id);
        
        // Find related posts
        $related_posts = get_posts(array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => 10,
            'post__not_in' => array($post_id),
            'category__in' => $categories,
            'orderby' => 'date',
            'order' => 'DESC',
        ));
        
        $suggestions = array();
        
        foreach ($related_posts as $related) {
            $related_keyword = get_post_meta($related->ID, 'rank_math_focus_keyword', true);
            
            // Calculate relevance
            $relevance = $this->calculate_keyword_similarity($focus_keyword, $related_keyword);
            
            if ($relevance >= 30) {
                $suggestions[] = array(
                    'post_id' => $related->ID,
                    'title' => $related->post_title,
                    'url' => get_permalink($related->ID),
                    'relevance' => $relevance,
                    'anchor_suggestion' => $related_keyword,
                );
            }
        }
        
        // Sort by relevance
        usort($suggestions, function($a, $b) {
            return $b['relevance'] - $a['relevance'];
        });
        
        return array_slice($suggestions, 0, 5);
    }
    
    /**
     * Save analysis to database
     */
    private function save_analysis($result) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dodo_content_intelligence';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") !== $table_name) {
            return false;
        }
        
        // Insert analysis
        $wpdb->insert(
            $table_name,
            array(
                'analysis_type' => 'site_wide',
                'analysis_data' => json_encode($result),
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ),
            array('%s', '%s', '%s', '%s')
        );
        
        return $wpdb->insert_id;
    }
    
    /**
     * Get latest analysis
     */
    public function get_latest_analysis() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dodo_content_intelligence';
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE analysis_type = %s ORDER BY created_at DESC LIMIT 1",
            'site_wide'
        ), ARRAY_A);
        
        if ($result) {
            $result['analysis_data'] = json_decode($result['analysis_data'], true);
        }
        
        return $result;
    }
    
    /**
     * Create database table
     */
    public static function create_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dodo_content_intelligence';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            analysis_type varchar(50) NOT NULL,
            analysis_data longtext NOT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY analysis_type (analysis_type),
            KEY updated_at (updated_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
