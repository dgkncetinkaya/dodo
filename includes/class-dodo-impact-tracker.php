<?php
/**
 * SEO Impact Tracking Engine
 * 
 * Tracks before/after metrics for every optimization
 * Measures real SEO impact of AI recommendations
 * 
 * @package DODO_AI_SEO
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Impact_Tracker {
    
    /**
     * Action types
     */
    const ACTION_TITLE_REWRITE = 'title_rewrite';
    const ACTION_META_REWRITE = 'meta_rewrite';
    const ACTION_CONTENT_REFRESH = 'content_refresh';
    const ACTION_FAQ_UPDATE = 'faq_update';
    const ACTION_INTERNAL_LINKS = 'internal_links';
    const ACTION_SEMANTIC_EXPANSION = 'semantic_expansion';
    const ACTION_GEO_OPTIMIZATION = 'geo_optimization';
    const ACTION_HUMANIZATION = 'humanization';
    
    /**
     * Measurement window (days)
     */
    const MEASUREMENT_WINDOW = 30;
    const SHORT_WINDOW = 7;
    const LONG_WINDOW = 90;
    
    /**
     * Database table
     */
    private $table_name;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'dodo_impact_tracking';
    }
    
    /**
     * Start tracking an optimization action
     * 
     * Captures baseline metrics before optimization
     * 
     * @param int $post_id Post ID
     * @param string $action_type Action type
     * @param array $action_details Action details
     * @return int Tracking ID
     */
    public function start_tracking($post_id, $action_type, $action_details = []) {
        global $wpdb;
        
        error_log("[DODO Impact] Starting tracking for post {$post_id}, action: {$action_type}");
        
        // Capture baseline metrics
        $baseline = $this->capture_baseline_metrics($post_id);
        
        // Insert tracking record
        $wpdb->insert(
            $this->table_name,
            [
                'post_id' => $post_id,
                'action_type' => $action_type,
                'action_details' => json_encode($action_details),
                'baseline_metrics' => json_encode($baseline),
                'started_at' => current_time('mysql'),
                'status' => 'tracking',
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s']
        );
        
        $tracking_id = $wpdb->insert_id;
        
        error_log("[DODO Impact] Tracking started - ID: {$tracking_id}");
        
        return $tracking_id;
    }
    
    /**
     * Capture baseline metrics
     * 
     * @param int $post_id Post ID
     * @return array Baseline metrics
     */
    private function capture_baseline_metrics($post_id) {
        $metrics = [
            'timestamp' => current_time('mysql'),
            'post_id' => $post_id,
        ];
        
        // Get GSC data if available
        if (class_exists('DODO_Search_Console')) {
            $gsc = new DODO_Search_Console();
            $status = $gsc->is_available();
            
            if ($status['available']) {
                $url = get_permalink($post_id);
                $gsc_data = $gsc->get_page_performance($url);
                
                if (!isset($gsc_data['error'])) {
                    $metrics['gsc'] = [
                        'total_clicks' => $gsc_data['total_clicks'] ?? 0,
                        'total_impressions' => $gsc_data['total_impressions'] ?? 0,
                        'avg_ctr' => $gsc_data['total_impressions'] > 0 
                            ? ($gsc_data['total_clicks'] / $gsc_data['total_impressions']) * 100 
                            : 0,
                        'queries' => array_slice($gsc_data['queries'] ?? [], 0, 10),
                    ];
                }
            }
        }
        
        // Get current rankings (if tracking plugin available)
        $metrics['rankings'] = $this->get_current_rankings($post_id);
        
        // Get AI Overview visibility
        $metrics['ai_visibility'] = $this->get_ai_visibility_score($post_id);
        
        // Get internal link metrics
        $metrics['internal_links'] = $this->get_internal_link_metrics($post_id);
        
        // Get content metrics
        $post = get_post($post_id);
        if ($post) {
            $metrics['content'] = [
                'word_count' => str_word_count(strip_tags($post->post_content)),
                'title_length' => strlen($post->post_title),
                'has_faq' => $this->has_faq_schema($post_id),
            ];
        }
        
        return $metrics;
    }
    
    /**
     * Complete tracking and calculate impact
     * 
     * @param int $tracking_id Tracking ID
     * @return array Impact analysis
     */
    public function complete_tracking($tracking_id) {
        global $wpdb;
        
        // Get tracking record
        $tracking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $tracking_id
        ));
        
        if (!$tracking) {
            error_log("[DODO Impact] Tracking ID {$tracking_id} not found");
            return ['error' => 'Tracking not found'];
        }
        
        error_log("[DODO Impact] Completing tracking {$tracking_id} for post {$tracking->post_id}");
        
        // Capture after metrics
        $after_metrics = $this->capture_baseline_metrics($tracking->post_id);
        
        // Calculate impact
        $baseline = json_decode($tracking->baseline_metrics, true);
        $impact = $this->calculate_impact($baseline, $after_metrics, $tracking->action_type);
        
        // Update tracking record
        $wpdb->update(
            $this->table_name,
            [
                'after_metrics' => json_encode($after_metrics),
                'impact_analysis' => json_encode($impact),
                'completed_at' => current_time('mysql'),
                'status' => 'completed',
            ],
            ['id' => $tracking_id],
            ['%s', '%s', '%s', '%s'],
            ['%d']
        );
        
        error_log(sprintf(
            "[DODO Impact] Tracking completed - Impact Score: %d",
            $impact['overall_score']
        ));
        
        return $impact;
    }
    
    /**
     * Calculate impact between baseline and after metrics
     * 
     * @param array $baseline Baseline metrics
     * @param array $after After metrics
     * @param string $action_type Action type
     * @return array Impact analysis
     */
    private function calculate_impact($baseline, $after, $action_type) {
        $impact = [
            'action_type' => $action_type,
            'measured_at' => current_time('mysql'),
            'changes' => [],
            'scores' => [],
            'overall_score' => 0,
            'confidence' => 0,
            'reasoning' => [],
        ];
        
        // GSC Impact
        if (isset($baseline['gsc']) && isset($after['gsc'])) {
            $gsc_impact = $this->calculate_gsc_impact($baseline['gsc'], $after['gsc']);
            $impact['changes']['gsc'] = $gsc_impact;
            $impact['scores']['gsc'] = $gsc_impact['score'];
            $impact['reasoning'][] = $gsc_impact['reasoning'];
        }
        
        // Ranking Impact
        if (isset($baseline['rankings']) && isset($after['rankings'])) {
            $ranking_impact = $this->calculate_ranking_impact($baseline['rankings'], $after['rankings']);
            $impact['changes']['rankings'] = $ranking_impact;
            $impact['scores']['rankings'] = $ranking_impact['score'];
            $impact['reasoning'][] = $ranking_impact['reasoning'];
        }
        
        // AI Visibility Impact
        if (isset($baseline['ai_visibility']) && isset($after['ai_visibility'])) {
            $ai_impact = $this->calculate_ai_visibility_impact($baseline['ai_visibility'], $after['ai_visibility']);
            $impact['changes']['ai_visibility'] = $ai_impact;
            $impact['scores']['ai_visibility'] = $ai_impact['score'];
            $impact['reasoning'][] = $ai_impact['reasoning'];
        }
        
        // Internal Link Impact
        if (isset($baseline['internal_links']) && isset($after['internal_links'])) {
            $link_impact = $this->calculate_link_impact($baseline['internal_links'], $after['internal_links']);
            $impact['changes']['internal_links'] = $link_impact;
            $impact['scores']['internal_links'] = $link_impact['score'];
            $impact['reasoning'][] = $link_impact['reasoning'];
        }
        
        // Calculate overall score (weighted average)
        $weights = [
            'gsc' => 0.4,
            'rankings' => 0.3,
            'ai_visibility' => 0.2,
            'internal_links' => 0.1,
        ];
        
        $total_weight = 0;
        $weighted_sum = 0;
        
        foreach ($impact['scores'] as $metric => $score) {
            $weight = $weights[$metric] ?? 0;
            $weighted_sum += $score * $weight;
            $total_weight += $weight;
        }
        
        $impact['overall_score'] = $total_weight > 0 ? round($weighted_sum / $total_weight) : 0;
        
        // Calculate confidence based on data availability
        $impact['confidence'] = $this->calculate_confidence($baseline, $after);
        
        return $impact;
    }
    
    /**
     * Calculate GSC impact
     */
    private function calculate_gsc_impact($baseline, $after) {
        $impact = [
            'clicks_change' => 0,
            'clicks_change_pct' => 0,
            'impressions_change' => 0,
            'impressions_change_pct' => 0,
            'ctr_change' => 0,
            'ctr_change_pct' => 0,
            'score' => 0,
            'reasoning' => '',
        ];
        
        // Clicks
        $clicks_before = $baseline['total_clicks'] ?? 0;
        $clicks_after = $after['total_clicks'] ?? 0;
        $impact['clicks_change'] = $clicks_after - $clicks_before;
        $impact['clicks_change_pct'] = $clicks_before > 0 
            ? round((($clicks_after - $clicks_before) / $clicks_before) * 100, 1) 
            : 0;
        
        // Impressions
        $impressions_before = $baseline['total_impressions'] ?? 0;
        $impressions_after = $after['total_impressions'] ?? 0;
        $impact['impressions_change'] = $impressions_after - $impressions_before;
        $impact['impressions_change_pct'] = $impressions_before > 0 
            ? round((($impressions_after - $impressions_before) / $impressions_before) * 100, 1) 
            : 0;
        
        // CTR
        $ctr_before = $baseline['avg_ctr'] ?? 0;
        $ctr_after = $after['avg_ctr'] ?? 0;
        $impact['ctr_change'] = round($ctr_after - $ctr_before, 2);
        $impact['ctr_change_pct'] = $ctr_before > 0 
            ? round((($ctr_after - $ctr_before) / $ctr_before) * 100, 1) 
            : 0;
        
        // Calculate score (-100 to +100)
        $score = 0;
        
        // CTR is most important
        if ($impact['ctr_change_pct'] > 0) {
            $score += min(50, $impact['ctr_change_pct'] * 2);
        } else {
            $score += max(-50, $impact['ctr_change_pct'] * 2);
        }
        
        // Clicks
        if ($impact['clicks_change_pct'] > 0) {
            $score += min(30, $impact['clicks_change_pct']);
        } else {
            $score += max(-30, $impact['clicks_change_pct']);
        }
        
        // Impressions (less weight)
        if ($impact['impressions_change_pct'] > 0) {
            $score += min(20, $impact['impressions_change_pct'] * 0.5);
        } else {
            $score += max(-20, $impact['impressions_change_pct'] * 0.5);
        }
        
        $impact['score'] = round($score);
        
        // Reasoning
        if ($impact['ctr_change_pct'] > 10) {
            $impact['reasoning'] = sprintf('CTR increased by %s%% - strong positive impact', $impact['ctr_change_pct']);
        } elseif ($impact['ctr_change_pct'] < -10) {
            $impact['reasoning'] = sprintf('CTR decreased by %s%% - negative impact', abs($impact['ctr_change_pct']));
        } elseif ($impact['clicks_change'] > 0) {
            $impact['reasoning'] = sprintf('Clicks increased by %d - positive impact', $impact['clicks_change']);
        } else {
            $impact['reasoning'] = 'Minimal GSC impact detected';
        }
        
        return $impact;
    }
    
    /**
     * Calculate ranking impact
     */
    private function calculate_ranking_impact($baseline, $after) {
        $impact = [
            'position_changes' => [],
            'avg_position_change' => 0,
            'score' => 0,
            'reasoning' => '',
        ];
        
        // Compare rankings
        foreach ($after as $keyword => $after_pos) {
            $before_pos = $baseline[$keyword] ?? null;
            
            if ($before_pos !== null) {
                $change = $before_pos - $after_pos; // Positive = improvement
                $impact['position_changes'][$keyword] = $change;
            }
        }
        
        if (!empty($impact['position_changes'])) {
            $impact['avg_position_change'] = round(
                array_sum($impact['position_changes']) / count($impact['position_changes']),
                1
            );
            
            // Score: +10 per position gained (capped at ±100)
            $impact['score'] = round(min(100, max(-100, $impact['avg_position_change'] * 10)));
            
            if ($impact['avg_position_change'] > 0) {
                $impact['reasoning'] = sprintf(
                    'Average ranking improved by %.1f positions',
                    $impact['avg_position_change']
                );
            } else {
                $impact['reasoning'] = sprintf(
                    'Average ranking declined by %.1f positions',
                    abs($impact['avg_position_change'])
                );
            }
        } else {
            $impact['reasoning'] = 'No ranking data available for comparison';
        }
        
        return $impact;
    }
    
    /**
     * Calculate AI visibility impact
     */
    private function calculate_ai_visibility_impact($baseline, $after) {
        $change = $after - $baseline;
        $change_pct = $baseline > 0 ? round(($change / $baseline) * 100, 1) : 0;
        
        return [
            'score_before' => $baseline,
            'score_after' => $after,
            'change' => $change,
            'change_pct' => $change_pct,
            'score' => round($change * 2), // -100 to +100
            'reasoning' => $change > 0 
                ? "AI Overview visibility improved by {$change} points"
                : "AI Overview visibility declined by " . abs($change) . " points",
        ];
    }
    
    /**
     * Calculate internal link impact
     */
    private function calculate_link_impact($baseline, $after) {
        $inbound_change = ($after['inbound_count'] ?? 0) - ($baseline['inbound_count'] ?? 0);
        $outbound_change = ($after['outbound_count'] ?? 0) - ($baseline['outbound_count'] ?? 0);
        
        $score = ($inbound_change * 5) + ($outbound_change * 2);
        
        return [
            'inbound_change' => $inbound_change,
            'outbound_change' => $outbound_change,
            'score' => round(min(100, max(-100, $score))),
            'reasoning' => $inbound_change > 0 
                ? "Gained {$inbound_change} inbound links"
                : "Internal link structure unchanged",
        ];
    }
    
    /**
     * Calculate confidence score
     */
    private function calculate_confidence($baseline, $after) {
        $confidence = 0;
        $max_confidence = 100;
        
        // GSC data available
        if (isset($baseline['gsc']) && isset($after['gsc'])) {
            $confidence += 40;
        }
        
        // Ranking data available
        if (isset($baseline['rankings']) && isset($after['rankings']) && !empty($after['rankings'])) {
            $confidence += 30;
        }
        
        // AI visibility data
        if (isset($baseline['ai_visibility']) && isset($after['ai_visibility'])) {
            $confidence += 20;
        }
        
        // Internal links data
        if (isset($baseline['internal_links']) && isset($after['internal_links'])) {
            $confidence += 10;
        }
        
        return min($max_confidence, $confidence);
    }
    
    /**
     * Get current rankings
     * 
     * Attempts to integrate with popular rank tracking plugins.
     * Returns empty array if no rank tracking plugin is available.
     * 
     * @param int $post_id Post ID
     * @return array Rankings data
     */
    private function get_current_rankings($post_id) {
        $rankings = [];
        
        // Try to integrate with Rank Math (if available)
        if (class_exists('RankMath')) {
            $focus_keyword = get_post_meta($post_id, 'rank_math_focus_keyword', true);
            if ($focus_keyword) {
                // Rank Math doesn't store position data by default
                // This is a placeholder for future integration
                $rankings[$focus_keyword] = 0;
            }
        }
        
        // Try to integrate with Yoast SEO (if available)
        if (class_exists('WPSEO_Options')) {
            $focus_keyword = get_post_meta($post_id, '_yoast_wpseo_focuskw', true);
            if ($focus_keyword) {
                // Yoast doesn't store position data by default
                // This is a placeholder for future integration
                $rankings[$focus_keyword] = 0;
            }
        }
        
        // Future: Add integration with dedicated rank tracking plugins
        // - SEMrush
        // - Ahrefs
        // - SERPWatcher
        
        return $rankings;
    }
    
    /**
     * Get AI visibility score
     */
    private function get_ai_visibility_score($post_id) {
        if (class_exists('DODO_AI_Overview_Engine')) {
            $geo_engine = new DODO_AI_Overview_Engine();
            $post = get_post($post_id);
            
            if ($post) {
                $analysis = $geo_engine->analyze_content($post->post_content);
                return $analysis['overall_score'] ?? 0;
            }
        }
        
        return 0;
    }
    
    /**
     * Get internal link metrics
     */
    private function get_internal_link_metrics($post_id) {
        $post = get_post($post_id);
        
        if (!$post) {
            return ['inbound_count' => 0, 'outbound_count' => 0];
        }
        
        // Count outbound links
        preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>/i', $post->post_content, $matches);
        $outbound_count = 0;
        
        foreach ($matches[1] as $url) {
            if (strpos($url, get_site_url()) !== false) {
                $outbound_count++;
            }
        }
        
        // Count inbound links (search other posts linking to this)
        global $wpdb;
        $permalink = get_permalink($post_id);
        
        $inbound_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} 
            WHERE post_status = 'publish' 
            AND post_content LIKE %s 
            AND ID != %d",
            '%' . $wpdb->esc_like($permalink) . '%',
            $post_id
        ));
        
        return [
            'inbound_count' => (int) $inbound_count,
            'outbound_count' => $outbound_count,
        ];
    }
    
    /**
     * Check if post has FAQ schema
     */
    private function has_faq_schema($post_id) {
        $faq_meta = get_post_meta($post_id, 'rank_math_faq', true);
        return !empty($faq_meta);
    }
    
    /**
     * Get impact history for post
     */
    public function get_post_impact_history($post_id, $limit = 10) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
            WHERE post_id = %d 
            AND status = 'completed'
            ORDER BY completed_at DESC 
            LIMIT %d",
            $post_id,
            $limit
        ));
    }
    
    /**
     * Get best performing actions
     */
    public function get_best_performing_actions($limit = 20) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT 
                action_type,
                AVG(JSON_EXTRACT(impact_analysis, '$.overall_score')) as avg_score,
                COUNT(*) as action_count
            FROM {$this->table_name}
            WHERE status = 'completed'
            AND JSON_EXTRACT(impact_analysis, '$.confidence') >= 50
            GROUP BY action_type
            ORDER BY avg_score DESC
            LIMIT %d",
            $limit
        ));
    }
    
    /**
     * Create database table
     */
    public static function create_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dodo_impact_tracking';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            action_type varchar(50) NOT NULL,
            action_details longtext,
            baseline_metrics longtext,
            after_metrics longtext,
            impact_analysis longtext,
            started_at datetime NOT NULL,
            completed_at datetime DEFAULT NULL,
            status varchar(20) DEFAULT 'tracking',
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY action_type (action_type),
            KEY status (status),
            KEY completed_at (completed_at)
        ) {$charset_collate};";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        error_log('[DODO Impact] Impact tracking table created');
    }
}
