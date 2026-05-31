<?php
/**
 * Topical Authority Engine
 * 
 * Analyzes site-wide topical coverage, cluster strength, and authority gaps.
 * Detects orphan topics, weak clusters, and over-optimization.
 *
 * @package DODO_AI_SEO
 * @subpackage Brain
 * @since 2.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Prevent duplicate class declaration
if (class_exists('Dodo_Topical_Authority')) {
    return;
}

class Dodo_Topical_Authority {
    
    /**
     * Cluster strength thresholds
     */
    const CLUSTER_STRONG = 8;      // 8+ posts = strong cluster
    const CLUSTER_MODERATE = 4;    // 4-7 posts = moderate cluster
    const CLUSTER_WEAK = 2;        // 2-3 posts = weak cluster
    const CLUSTER_ORPHAN = 1;      // 1 post = orphan
    
    /**
     * Authority score thresholds
     */
    const AUTHORITY_EXCELLENT = 80;
    const AUTHORITY_GOOD = 60;
    const AUTHORITY_MODERATE = 40;
    const AUTHORITY_WEAK = 20;
    
    /**
     * Over-optimization thresholds
     */
    const OVEROPT_KEYWORD_DENSITY = 3.5;  // % keyword density threshold
    const OVEROPT_EXACT_MATCH_RATIO = 0.7; // 70% exact match = suspicious
    
    /**
     * Debug mode
     */
    private $debug = true;
    
    /**
     * Analyze topical authority for a keyword
     *
     * @param string $keyword Target keyword
     * @param array $options Analysis options
     * @return array Authority analysis with scores and recommendations
     */
    public function analyze($keyword, $options = []) {
        $start_time = microtime(true);
        
        // Validate input
        if (empty(trim($keyword))) {
            return [
                'error' => true,
                'message' => 'Konu adı boş olamaz.',
                'authority_score' => 0,
                'execution_time_ms' => 0
            ];
        }
        
        try {
            $defaults = [
                'include_drafts' => false,
                'lookback_days' => 365,
                'min_word_count' => 300,
            ];
            $options = wp_parse_args($options, $defaults);
            
            // Extract topic from keyword
            $topic = $this->extract_topic($keyword);
            
            // Get all posts in this topic cluster
            $cluster_posts = $this->get_cluster_posts($topic, $options);
            
            // Analyze cluster strength
            $cluster_analysis = $this->analyze_cluster_strength($cluster_posts, $topic);
            
            // Detect orphan topics
            $orphan_analysis = $this->detect_orphan_topics($topic, $cluster_posts);
            
            // Find authority gaps
            $gap_analysis = $this->find_authority_gaps($topic, $cluster_posts);
            
            // Check for over-optimization
            $overopt_analysis = $this->check_over_optimization($keyword, $cluster_posts);
            
            // Calculate overall authority score
            $authority_score = $this->calculate_authority_score(
                $cluster_analysis,
                $orphan_analysis,
                $gap_analysis,
                $overopt_analysis
            );
            
            // Generate recommendations
            $recommendations = $this->generate_recommendations(
                $authority_score,
                $cluster_analysis,
                $orphan_analysis,
                $gap_analysis,
                $overopt_analysis
            );
            
            $execution_time = round((microtime(true) - $start_time) * 1000, 2);
            
            if ($this->debug) {
                error_log(sprintf(
                    '[DODO Topical Authority] Keyword: %s | Topic: %s | Score: %d | Cluster Size: %d | Time: %sms',
                    $keyword,
                    $topic,
                    $authority_score,
                    count($cluster_posts),
                    $execution_time
                ));
            }
            
            return [
                'authority_score' => $authority_score,
                'topic' => $topic,
                'cluster_analysis' => $cluster_analysis,
                'orphan_analysis' => $orphan_analysis,
                'gap_analysis' => $gap_analysis,
                'overopt_analysis' => $overopt_analysis,
                'recommendations' => $recommendations,
                'reasoning' => $this->build_reasoning(
                    $authority_score,
                    $cluster_analysis,
                    $orphan_analysis,
                    $gap_analysis
                ),
                'execution_time_ms' => $execution_time,
            ];
        } catch (Throwable $e) {
            error_log('DODO Topical Authority: Analysis error: ' . $e->getMessage());
            return [
                'error' => true,
                'message' => 'Konu otoritesi analizi sırasında hata oluştu.',
                'authority_score' => 0,
                'execution_time_ms' => round((microtime(true) - $start_time) * 1000, 2)
            ];
        }
    }
    
    /**
     * Extract main topic from keyword
     */
    private function extract_topic($keyword) {
        // Remove common modifiers to get core topic
        $modifiers = [
            'nasıl', 'nedir', 'ne demek', 'örnekleri', 'fiyatları',
            'en iyi', 'ücretsiz', 'bedava', 'indir', 'satın al',
            'how to', 'what is', 'best', 'free', 'buy', 'price',
            '2024', '2025', '2026',
        ];
        
        $topic = strtolower($keyword);
        foreach ($modifiers as $modifier) {
            $topic = str_replace($modifier, '', $topic);
        }
        
        $topic = trim(preg_replace('/\s+/', ' ', $topic));
        
        // If too short after cleaning, use original
        if (strlen($topic) < 3) {
            $topic = strtolower($keyword);
        }
        
        return $topic;
    }
    
    /**
     * Get all posts in topic cluster
     */
    private function get_cluster_posts($topic, $options) {
        $args = [
            'post_type' => 'post',
            'post_status' => $options['include_drafts'] ? ['publish', 'draft'] : 'publish',
            'posts_per_page' => -1,
            'date_query' => [
                [
                    'after' => $options['lookback_days'] . ' days ago',
                ],
            ],
            's' => $topic, // Search in title and content
        ];
        
        $query = new WP_Query($args);
        $posts = [];
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                $content = get_post_field('post_content', $post_id);
                $word_count = str_word_count(strip_tags($content));
                
                if ($word_count >= $options['min_word_count']) {
                    $posts[] = [
                        'id' => $post_id,
                        'title' => get_the_title(),
                        'url' => get_permalink(),
                        'word_count' => $word_count,
                        'date' => get_the_date('Y-m-d'),
                        'content' => $content,
                    ];
                }
            }
            wp_reset_postdata();
        }
        
        return $posts;
    }
    
    /**
     * Analyze cluster strength
     */
    private function analyze_cluster_strength($cluster_posts, $topic) {
        $post_count = count($cluster_posts);
        
        if ($post_count >= self::CLUSTER_STRONG) {
            $strength = 'strong';
            $score = 90;
        } elseif ($post_count >= self::CLUSTER_MODERATE) {
            $strength = 'moderate';
            $score = 65;
        } elseif ($post_count >= self::CLUSTER_WEAK) {
            $strength = 'weak';
            $score = 40;
        } else {
            $strength = 'orphan';
            $score = 15;
        }
        
        // Calculate total word count
        $total_words = array_sum(array_column($cluster_posts, 'word_count'));
        
        // Calculate average word count
        $avg_words = $post_count > 0 ? round($total_words / $post_count) : 0;
        
        // Calculate content freshness
        $dates = array_column($cluster_posts, 'date');
        $freshness_score = $this->calculate_freshness_score($dates);
        
        return [
            'strength' => $strength,
            'score' => $score,
            'post_count' => $post_count,
            'total_words' => $total_words,
            'avg_words' => $avg_words,
            'freshness_score' => $freshness_score,
            'posts' => array_map(function($post) {
                return [
                    'id' => $post['id'],
                    'title' => $post['title'],
                    'word_count' => $post['word_count'],
                    'date' => $post['date'],
                ];
            }, $cluster_posts),
        ];
    }
    
    /**
     * Calculate content freshness score
     */
    private function calculate_freshness_score($dates) {
        if (empty($dates)) {
            return 0;
        }
        
        $now = time();
        $scores = [];
        
        foreach ($dates as $date) {
            $post_time = strtotime($date);
            $days_old = ($now - $post_time) / DAY_IN_SECONDS;
            
            // Fresher content = higher score
            if ($days_old <= 30) {
                $scores[] = 100;
            } elseif ($days_old <= 90) {
                $scores[] = 80;
            } elseif ($days_old <= 180) {
                $scores[] = 60;
            } elseif ($days_old <= 365) {
                $scores[] = 40;
            } else {
                $scores[] = 20;
            }
        }
        
        return round(array_sum($scores) / count($scores));
    }
    
    /**
     * Detect orphan topics
     */
    private function detect_orphan_topics($topic, $cluster_posts) {
        $is_orphan = count($cluster_posts) <= self::CLUSTER_ORPHAN;
        
        // Find related topics that could connect
        $related_topics = $this->find_related_topics($topic);
        
        return [
            'is_orphan' => $is_orphan,
            'related_topics' => $related_topics,
            'connection_opportunities' => count($related_topics),
            'risk_level' => $is_orphan ? 'high' : 'low',
        ];
    }
    
    /**
     * Find related topics
     */
    private function find_related_topics($topic) {
        // Simple related topic detection
        // In production, this could use taxonomy, tags, or semantic analysis
        $words = explode(' ', $topic);
        $related = [];
        
        foreach ($words as $word) {
            if (strlen($word) >= 4) {
                $args = [
                    'post_type' => 'post',
                    'post_status' => 'publish',
                    'posts_per_page' => 5,
                    's' => $word,
                ];
                
                $query = new WP_Query($args);
                if ($query->found_posts > 0) {
                    $related[] = $word;
                }
                wp_reset_postdata();
            }
        }
        
        return array_unique($related);
    }
    
    /**
     * Find authority gaps
     */
    private function find_authority_gaps($topic, $cluster_posts) {
        $gaps = [];
        
        // Check for missing subtopics
        $expected_subtopics = $this->get_expected_subtopics($topic);
        $covered_subtopics = $this->get_covered_subtopics($cluster_posts);
        
        $missing_subtopics = array_diff($expected_subtopics, $covered_subtopics);
        
        if (!empty($missing_subtopics)) {
            $gaps[] = [
                'type' => 'missing_subtopics',
                'severity' => 'high',
                'items' => $missing_subtopics,
            ];
        }
        
        // Check for shallow coverage (low word count)
        $shallow_posts = array_filter($cluster_posts, function($post) {
            return $post['word_count'] < 800;
        });
        
        if (!empty($shallow_posts)) {
            $gaps[] = [
                'type' => 'shallow_coverage',
                'severity' => 'medium',
                'count' => count($shallow_posts),
                'posts' => array_column($shallow_posts, 'title'),
            ];
        }
        
        // Check for outdated content
        $outdated_posts = array_filter($cluster_posts, function($post) {
            $days_old = (time() - strtotime($post['date'])) / DAY_IN_SECONDS;
            return $days_old > 365;
        });
        
        if (!empty($outdated_posts)) {
            $gaps[] = [
                'type' => 'outdated_content',
                'severity' => 'low',
                'count' => count($outdated_posts),
                'posts' => array_column($outdated_posts, 'title'),
            ];
        }
        
        return [
            'has_gaps' => !empty($gaps),
            'gap_count' => count($gaps),
            'gaps' => $gaps,
        ];
    }
    
    /**
     * Get expected subtopics for a topic
     */
    private function get_expected_subtopics($topic) {
        // Common subtopic patterns
        return [
            $topic . ' nedir',
            $topic . ' nasıl yapılır',
            $topic . ' örnekleri',
            $topic . ' avantajları',
            $topic . ' dezavantajları',
            'en iyi ' . $topic,
        ];
    }
    
    /**
     * Get covered subtopics from existing posts
     */
    private function get_covered_subtopics($cluster_posts) {
        $covered = [];
        
        foreach ($cluster_posts as $post) {
            $title = strtolower($post['title']);
            $covered[] = $title;
        }
        
        return $covered;
    }
    
    /**
     * Check for over-optimization
     */
    private function check_over_optimization($keyword, $cluster_posts) {
        $issues = [];
        
        // Validate keyword
        if (empty(trim($keyword))) {
            return [
                'is_over_optimized' => false,
                'issue_count' => 0,
                'issues' => [],
                'risk_level' => 'low',
            ];
        }
        
        foreach ($cluster_posts as $post) {
            $content = strtolower($post['content']);
            $keyword_lower = strtolower($keyword);
            
            // Skip if keyword is empty after processing
            if (empty($keyword_lower)) {
                continue;
            }
            
            // Count keyword occurrences
            $keyword_count = substr_count($content, $keyword_lower);
            $word_count = str_word_count($content);
            
            if ($word_count > 0) {
                $keyword_density = ($keyword_count / $word_count) * 100;
                
                if ($keyword_density > self::OVEROPT_KEYWORD_DENSITY) {
                    $issues[] = [
                        'post_id' => $post['id'],
                        'post_title' => $post['title'],
                        'issue' => 'high_keyword_density',
                        'density' => round($keyword_density, 2),
                        'threshold' => self::OVEROPT_KEYWORD_DENSITY,
                    ];
                }
            }
            
            // Check for exact match title spam
            $title_lower = strtolower($post['title']);
            if ($title_lower === $keyword_lower) {
                $issues[] = [
                    'post_id' => $post['id'],
                    'post_title' => $post['title'],
                    'issue' => 'exact_match_title',
                    'severity' => 'medium',
                ];
            }
        }
        
        return [
            'is_over_optimized' => !empty($issues),
            'issue_count' => count($issues),
            'issues' => $issues,
            'risk_level' => count($issues) > 3 ? 'high' : (count($issues) > 0 ? 'medium' : 'low'),
        ];
    }
    
    /**
     * Calculate overall authority score
     */
    private function calculate_authority_score($cluster, $orphan, $gap, $overopt) {
        $score = 0;
        
        // Cluster strength (40% weight)
        $score += $cluster['score'] * 0.4;
        
        // Freshness (20% weight)
        $score += $cluster['freshness_score'] * 0.2;
        
        // Orphan penalty (15% weight)
        if ($orphan['is_orphan']) {
            $score += 0; // No points for orphan
        } else {
            $score += 15;
        }
        
        // Gap penalty (15% weight)
        if ($gap['has_gaps']) {
            $gap_penalty = min($gap['gap_count'] * 5, 15);
            $score -= $gap_penalty;
        } else {
            $score += 15;
        }
        
        // Over-optimization penalty (10% weight)
        if ($overopt['is_over_optimized']) {
            $overopt_penalty = min($overopt['issue_count'] * 3, 10);
            $score -= $overopt_penalty;
        } else {
            $score += 10;
        }
        
        return max(0, min(100, round($score)));
    }
    
    /**
     * Generate recommendations
     */
    private function generate_recommendations($score, $cluster, $orphan, $gap, $overopt) {
        $recommendations = [];
        
        if ($score >= self::AUTHORITY_EXCELLENT) {
            $recommendations[] = [
                'type' => 'maintain',
                'priority' => 'low',
                'action' => 'Topical authority excellent. Maintain content freshness.',
            ];
        } elseif ($score >= self::AUTHORITY_GOOD) {
            $recommendations[] = [
                'type' => 'optimize',
                'priority' => 'medium',
                'action' => 'Good authority. Consider expanding cluster with related subtopics.',
            ];
        } else {
            $recommendations[] = [
                'type' => 'build',
                'priority' => 'high',
                'action' => 'Weak authority. Build cluster with supporting content.',
            ];
        }
        
        // Orphan recommendations
        if ($orphan['is_orphan']) {
            $recommendations[] = [
                'type' => 'connect',
                'priority' => 'high',
                'action' => sprintf(
                    'Orphan topic detected. Create %d supporting posts to build cluster.',
                    self::CLUSTER_MODERATE - $cluster['post_count']
                ),
            ];
        }
        
        // Gap recommendations
        if ($gap['has_gaps']) {
            foreach ($gap['gaps'] as $gap_item) {
                $recommendations[] = [
                    'type' => 'fill_gap',
                    'priority' => $gap_item['severity'],
                    'action' => $this->get_gap_action($gap_item),
                ];
            }
        }
        
        // Over-optimization recommendations
        if ($overopt['is_over_optimized']) {
            $recommendations[] = [
                'type' => 'reduce_optimization',
                'priority' => 'high',
                'action' => sprintf(
                    'Over-optimization detected in %d posts. Reduce keyword density and diversify content.',
                    $overopt['issue_count']
                ),
            ];
        }
        
        return $recommendations;
    }
    
    /**
     * Get action text for gap
     */
    private function get_gap_action($gap) {
        switch ($gap['type']) {
            case 'missing_subtopics':
                return 'Create content for missing subtopics: ' . implode(', ', array_slice($gap['items'], 0, 3));
            case 'shallow_coverage':
                return sprintf('Expand %d shallow posts (< 800 words) with deeper content.', $gap['count']);
            case 'outdated_content':
                return sprintf('Update %d outdated posts (> 1 year old) with fresh information.', $gap['count']);
            default:
                return 'Address content gap.';
        }
    }
    
    /**
     * Build reasoning array
     */
    private function build_reasoning($score, $cluster, $orphan, $gap) {
        $reasoning = [];
        
        $reasoning[] = sprintf(
            'Cluster strength: %s (%d posts)',
            $cluster['strength'],
            $cluster['post_count']
        );
        
        $reasoning[] = sprintf(
            'Content freshness: %d/100',
            $cluster['freshness_score']
        );
        
        if ($orphan['is_orphan']) {
            $reasoning[] = 'Warning: Orphan topic (needs supporting content)';
        }
        
        if ($gap['has_gaps']) {
            $reasoning[] = sprintf(
                '%d authority gaps detected',
                $gap['gap_count']
            );
        }
        
        if ($score >= self::AUTHORITY_EXCELLENT) {
            $reasoning[] = 'Excellent topical authority';
        } elseif ($score >= self::AUTHORITY_GOOD) {
            $reasoning[] = 'Good topical authority';
        } elseif ($score >= self::AUTHORITY_MODERATE) {
            $reasoning[] = 'Moderate topical authority';
        } else {
            $reasoning[] = 'Weak topical authority';
        }
        
        return $reasoning;
    }
}
