<?php
/**
 * AI Overview Visibility Tracking
 * 
 * Tracks AI Overview (GEO) visibility trends
 * Measures citation probability and conversational retrieval success
 * 
 * @package DODO_AI_SEO
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_AI_Visibility {
    
    /**
     * Visibility score thresholds
     */
    const EXCELLENT_VISIBILITY = 80;
    const GOOD_VISIBILITY = 60;
    const MODERATE_VISIBILITY = 40;
    const POOR_VISIBILITY = 20;
    
    /**
     * Database table
     */
    private $visibility_table;
    
    /**
     * GEO Engine
     */
    private $geo_engine;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->visibility_table = $wpdb->prefix . 'dodo_ai_visibility';
        
        if (class_exists('DODO_GEO_Engine')) {
            $this->geo_engine = new DODO_GEO_Engine();
        }
    }
    
    /**
     * Track AI visibility for post
     * 
     * @param int $post_id Post ID
     * @param string $trigger What triggered this tracking
     * @return int Tracking ID
     */
    public function track_visibility($post_id, $trigger = 'manual') {
        global $wpdb;
        
        $post = get_post($post_id);
        
        if (!$post || !$this->geo_engine) {
            return false;
        }
        
        error_log("[DODO AI Visibility] Tracking post {$post_id}, trigger: {$trigger}");
        
        // Analyze content
        $analysis = $this->geo_engine->analyze_content($post->post_content);
        
        // Calculate visibility metrics
        $metrics = [
            'overall_score' => $analysis['overall_score'] ?? 0,
            'answer_extraction' => $analysis['answer_extraction_score'] ?? 0,
            'snippet_suitability' => $analysis['snippet_suitability_score'] ?? 0,
            'conversational_retrieval' => $analysis['conversational_retrieval_score'] ?? 0,
            'chunk_retrievability' => $analysis['chunk_retrievability_score'] ?? 0,
            'citation_probability' => $this->calculate_citation_probability($analysis),
            'visibility_level' => $this->get_visibility_level($analysis['overall_score'] ?? 0),
        ];
        
        // Store tracking record
        $wpdb->insert(
            $this->visibility_table,
            [
                'post_id' => $post_id,
                'visibility_score' => $metrics['overall_score'],
                'metrics' => json_encode($metrics),
                'analysis_details' => json_encode($analysis),
                'trigger' => $trigger,
                'recorded_at' => current_time('mysql'),
            ],
            ['%d', '%d', '%s', '%s', '%s', '%s']
        );
        
        $tracking_id = $wpdb->insert_id;
        
        error_log(sprintf(
            '[DODO AI Visibility] Tracked - Score: %d, Citation Probability: %.1f%%',
            $metrics['overall_score'],
            $metrics['citation_probability']
        ));
        
        return $tracking_id;
    }
    
    /**
     * Calculate citation probability
     * 
     * Estimates likelihood of being cited in AI Overview
     */
    private function calculate_citation_probability($analysis) {
        $probability = 0;
        
        // Base probability from overall score
        $overall_score = $analysis['overall_score'] ?? 0;
        $probability += ($overall_score / 100) * 40; // Max 40% from overall score
        
        // Answer extraction quality
        $answer_score = $analysis['answer_extraction_score'] ?? 0;
        if ($answer_score >= 80) {
            $probability += 25;
        } elseif ($answer_score >= 60) {
            $probability += 15;
        } elseif ($answer_score >= 40) {
            $probability += 5;
        }
        
        // Snippet suitability
        $snippet_score = $analysis['snippet_suitability_score'] ?? 0;
        if ($snippet_score >= 80) {
            $probability += 20;
        } elseif ($snippet_score >= 60) {
            $probability += 10;
        }
        
        // Conversational retrieval
        $conv_score = $analysis['conversational_retrieval_score'] ?? 0;
        if ($conv_score >= 80) {
            $probability += 15;
        } elseif ($conv_score >= 60) {
            $probability += 8;
        }
        
        return min(100, round($probability, 1));
    }
    
    /**
     * Get visibility level
     */
    private function get_visibility_level($score) {
        if ($score >= self::EXCELLENT_VISIBILITY) {
            return 'excellent';
        } elseif ($score >= self::GOOD_VISIBILITY) {
            return 'good';
        } elseif ($score >= self::MODERATE_VISIBILITY) {
            return 'moderate';
        } elseif ($score >= self::POOR_VISIBILITY) {
            return 'poor';
        } else {
            return 'very_poor';
        }
    }
    
    /**
     * Get visibility trend for post
     * 
     * @param int $post_id Post ID
     * @param int $days Days to analyze
     * @return array Trend analysis
     */
    public function get_visibility_trend($post_id, $days = 30) {
        global $wpdb;
        
        $records = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->visibility_table}
            WHERE post_id = %d
            AND recorded_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            ORDER BY recorded_at ASC",
            $post_id,
            $days
        ), ARRAY_A);
        
        if (empty($records)) {
            return [
                'trend' => 'no_data',
                'change' => 0,
                'current_score' => 0,
                'records' => [],
            ];
        }
        
        $first = reset($records);
        $last = end($records);
        
        $change = $last['visibility_score'] - $first['visibility_score'];
        $change_pct = $first['visibility_score'] > 0 
            ? round(($change / $first['visibility_score']) * 100, 1) 
            : 0;
        
        // Determine trend
        if ($change > 10) {
            $trend = 'improving';
        } elseif ($change < -10) {
            $trend = 'declining';
        } else {
            $trend = 'stable';
        }
        
        return [
            'trend' => $trend,
            'change' => $change,
            'change_pct' => $change_pct,
            'current_score' => $last['visibility_score'],
            'previous_score' => $first['visibility_score'],
            'record_count' => count($records),
            'records' => $records,
            'reasoning' => $this->generate_trend_reasoning($trend, $change, $records),
        ];
    }
    
    /**
     * Generate trend reasoning
     */
    private function generate_trend_reasoning($trend, $change, $records) {
        $reasoning = [];
        
        if ($trend === 'improving') {
            $reasoning[] = sprintf(
                'AI Overview visibility improved by %d points over %d measurements',
                $change,
                count($records)
            );
        } elseif ($trend === 'declining') {
            $reasoning[] = sprintf(
                'AI Overview visibility declined by %d points over %d measurements',
                abs($change),
                count($records)
            );
        } else {
            $reasoning[] = sprintf(
                'AI Overview visibility remained stable over %d measurements',
                count($records)
            );
        }
        
        // Analyze metrics
        $last = end($records);
        $metrics = json_decode($last['metrics'], true);
        
        if ($metrics) {
            if ($metrics['citation_probability'] >= 70) {
                $reasoning[] = sprintf(
                    'High citation probability (%.1f%%) - likely to appear in AI Overviews',
                    $metrics['citation_probability']
                );
            } elseif ($metrics['citation_probability'] < 30) {
                $reasoning[] = sprintf(
                    'Low citation probability (%.1f%%) - optimization needed',
                    $metrics['citation_probability']
                );
            }
        }
        
        return implode('. ', $reasoning);
    }
    
    /**
     * Get visibility insights across all posts
     * 
     * @return array Visibility insights
     */
    public function get_visibility_insights() {
        global $wpdb;
        
        $insights = [
            'total_tracked_posts' => 0,
            'avg_visibility_score' => 0,
            'visibility_distribution' => [],
            'top_performing_posts' => [],
            'low_performing_posts' => [],
            'trending_up' => [],
            'trending_down' => [],
        ];
        
        // Total tracked posts
        $insights['total_tracked_posts'] = $wpdb->get_var(
            "SELECT COUNT(DISTINCT post_id) FROM {$this->visibility_table}"
        );
        
        // Average visibility score (latest for each post)
        $latest_scores = $wpdb->get_results(
            "SELECT v1.post_id, v1.visibility_score
            FROM {$this->visibility_table} v1
            INNER JOIN (
                SELECT post_id, MAX(recorded_at) as max_date
                FROM {$this->visibility_table}
                GROUP BY post_id
            ) v2 ON v1.post_id = v2.post_id AND v1.recorded_at = v2.max_date",
            ARRAY_A
        );
        
        if (!empty($latest_scores)) {
            $insights['avg_visibility_score'] = round(
                array_sum(array_column($latest_scores, 'visibility_score')) / count($latest_scores)
            );
            
            // Distribution
            $distribution = [
                'excellent' => 0,
                'good' => 0,
                'moderate' => 0,
                'poor' => 0,
                'very_poor' => 0,
            ];
            
            foreach ($latest_scores as $score_data) {
                $score = $score_data['visibility_score'];
                $level = $this->get_visibility_level($score);
                $distribution[$level]++;
            }
            
            $insights['visibility_distribution'] = $distribution;
        }
        
        // Top performing posts
        $insights['top_performing_posts'] = $wpdb->get_results(
            "SELECT v.post_id, v.visibility_score, p.post_title
            FROM {$this->visibility_table} v
            INNER JOIN {$wpdb->posts} p ON v.post_id = p.ID
            WHERE v.id IN (
                SELECT MAX(id) FROM {$this->visibility_table}
                GROUP BY post_id
            )
            ORDER BY v.visibility_score DESC
            LIMIT 10",
            ARRAY_A
        );
        
        // Low performing posts
        $insights['low_performing_posts'] = $wpdb->get_results(
            "SELECT v.post_id, v.visibility_score, p.post_title
            FROM {$this->visibility_table} v
            INNER JOIN {$wpdb->posts} p ON v.post_id = p.ID
            WHERE v.id IN (
                SELECT MAX(id) FROM {$this->visibility_table}
                GROUP BY post_id
            )
            ORDER BY v.visibility_score ASC
            LIMIT 10",
            ARRAY_A
        );
        
        // Trending analysis
        $insights['trending_up'] = $this->get_trending_posts('up', 5);
        $insights['trending_down'] = $this->get_trending_posts('down', 5);
        
        return $insights;
    }
    
    /**
     * Get trending posts
     */
    private function get_trending_posts($direction = 'up', $limit = 5) {
        global $wpdb;
        
        // Get posts with at least 2 measurements in last 30 days
        $posts_with_trend = $wpdb->get_results(
            "SELECT post_id, COUNT(*) as measurement_count
            FROM {$this->visibility_table}
            WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY post_id
            HAVING measurement_count >= 2",
            ARRAY_A
        );
        
        $trending = [];
        
        foreach ($posts_with_trend as $post_data) {
            $post_id = $post_data['post_id'];
            $trend = $this->get_visibility_trend($post_id, 30);
            
            if ($direction === 'up' && $trend['trend'] === 'improving') {
                $trending[] = [
                    'post_id' => $post_id,
                    'post_title' => get_the_title($post_id),
                    'change' => $trend['change'],
                    'change_pct' => $trend['change_pct'],
                    'current_score' => $trend['current_score'],
                ];
            } elseif ($direction === 'down' && $trend['trend'] === 'declining') {
                $trending[] = [
                    'post_id' => $post_id,
                    'post_title' => get_the_title($post_id),
                    'change' => $trend['change'],
                    'change_pct' => $trend['change_pct'],
                    'current_score' => $trend['current_score'],
                ];
            }
        }
        
        // Sort by change magnitude
        usort($trending, function($a, $b) {
            return abs($b['change']) <=> abs($a['change']);
        });
        
        return array_slice($trending, 0, $limit);
    }
    
    /**
     * Get optimization recommendations
     * 
     * @param int $post_id Post ID
     * @return array Recommendations
     */
    public function get_optimization_recommendations($post_id) {
        global $wpdb;
        
        // Get latest visibility record
        $latest = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->visibility_table}
            WHERE post_id = %d
            ORDER BY recorded_at DESC
            LIMIT 1",
            $post_id
        ));
        
        if (!$latest) {
            return [];
        }
        
        $metrics = json_decode($latest->metrics, true);
        $analysis = json_decode($latest->analysis_details, true);
        
        $recommendations = [];
        
        // Answer extraction
        if ($metrics['answer_extraction'] < 60) {
            $recommendations[] = [
                'priority' => 'high',
                'type' => 'answer_extraction',
                'title' => 'Improve Answer Extraction',
                'description' => 'Add clear, concise answers in first paragraphs. Use definition format.',
                'expected_impact' => '+15-25 visibility points',
            ];
        }
        
        // Snippet suitability
        if ($metrics['snippet_suitability'] < 60) {
            $recommendations[] = [
                'priority' => 'high',
                'type' => 'snippet_optimization',
                'title' => 'Optimize for Featured Snippets',
                'description' => 'Use lists, tables, or step-by-step formats. Keep answers under 60 words.',
                'expected_impact' => '+10-20 visibility points',
            ];
        }
        
        // Conversational retrieval
        if ($metrics['conversational_retrieval'] < 60) {
            $recommendations[] = [
                'priority' => 'medium',
                'type' => 'conversational_format',
                'title' => 'Enhance Conversational Format',
                'description' => 'Use natural language, question-answer format, and clear headings.',
                'expected_impact' => '+10-15 visibility points',
            ];
        }
        
        // Chunk retrievability
        if ($metrics['chunk_retrievability'] < 60) {
            $recommendations[] = [
                'priority' => 'medium',
                'type' => 'content_structure',
                'title' => 'Improve Content Structure',
                'description' => 'Break content into clear sections. Use descriptive subheadings.',
                'expected_impact' => '+5-15 visibility points',
            ];
        }
        
        // Citation probability
        if ($metrics['citation_probability'] < 50) {
            $recommendations[] = [
                'priority' => 'high',
                'type' => 'citation_optimization',
                'title' => 'Increase Citation Probability',
                'description' => 'Add authoritative data, clear definitions, and factual statements.',
                'expected_impact' => '+20-30% citation probability',
            ];
        }
        
        return $recommendations;
    }
    
    /**
     * Compare visibility with competitors
     * 
     * @param int $post_id Post ID
     * @param array $competitor_urls Competitor URLs
     * @return array Comparison
     */
    public function compare_with_competitors($post_id, $competitor_urls = []) {
        // Get own visibility
        $own_visibility = $this->get_latest_visibility($post_id);
        
        if (!$own_visibility) {
            return ['error' => 'No visibility data for this post'];
        }
        
        $comparison = [
            'own_score' => $own_visibility['visibility_score'],
            'own_metrics' => json_decode($own_visibility['metrics'], true),
            'competitors' => [],
            'position' => null,
            'gap_to_leader' => null,
        ];
        
        // Analyze competitors (if URLs provided)
        // This would require fetching and analyzing competitor content
        // For now, return own data
        
        return $comparison;
    }
    
    /**
     * Get latest visibility record
     */
    private function get_latest_visibility($post_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->visibility_table}
            WHERE post_id = %d
            ORDER BY recorded_at DESC
            LIMIT 1",
            $post_id
        ), ARRAY_A);
    }
    
    /**
     * Create database table
     */
    public static function create_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dodo_ai_visibility';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            visibility_score int(11) NOT NULL,
            metrics longtext,
            analysis_details longtext,
            trigger varchar(50) DEFAULT 'manual',
            recorded_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY visibility_score (visibility_score),
            KEY recorded_at (recorded_at)
        ) {$charset_collate};";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        error_log('[DODO AI Visibility] Visibility tracking table created');
    }
}
