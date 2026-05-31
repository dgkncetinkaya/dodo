<?php
/**
 * Ranking Correlation Engine
 * 
 * Correlates content changes with ranking movements
 * Identifies which optimizations actually impact rankings
 * 
 * @package DODO_AI_SEO
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Ranking_Correlation {
    
    /**
     * Correlation strength thresholds
     */
    const STRONG_CORRELATION = 0.7;
    const MODERATE_CORRELATION = 0.5;
    const WEAK_CORRELATION = 0.3;
    
    /**
     * Time windows for correlation analysis
     */
    const SHORT_WINDOW = 7; // 7 days
    const MEDIUM_WINDOW = 14; // 14 days
    const LONG_WINDOW = 30; // 30 days
    
    /**
     * Database table
     */
    private $ranking_table;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->ranking_table = $wpdb->prefix . 'dodo_ranking_history';
    }
    
    /**
     * Record ranking snapshot
     * 
     * @param int $post_id Post ID
     * @param array $rankings Keyword => position map
     * @param string $trigger What triggered this snapshot
     */
    public function record_ranking_snapshot($post_id, $rankings, $trigger = 'manual') {
        global $wpdb;
        
        if (empty($rankings)) {
            return false;
        }
        
        $wpdb->insert(
            $this->ranking_table,
            [
                'post_id' => $post_id,
                'rankings' => json_encode($rankings),
                'trigger' => $trigger,
                'recorded_at' => current_time('mysql'),
            ],
            ['%d', '%s', '%s', '%s']
        );
        
        error_log(sprintf(
            '[DODO Correlation] Ranking snapshot recorded - Post: %d, Keywords: %d, Trigger: %s',
            $post_id,
            count($rankings),
            $trigger
        ));
        
        return $wpdb->insert_id;
    }
    
    /**
     * Analyze correlation between action and ranking change
     * 
     * @param int $post_id Post ID
     * @param string $action_type Action type
     * @param int $days_window Days to analyze
     * @return array Correlation analysis
     */
    public function analyze_correlation($post_id, $action_type, $days_window = self::MEDIUM_WINDOW) {
        global $wpdb;
        
        error_log(sprintf(
            '[DODO Correlation] Analyzing correlation - Post: %d, Action: %s, Window: %d days',
            $post_id,
            $action_type,
            $days_window
        ));
        
        // Get ranking snapshots before and after action
        $before_snapshot = $this->get_snapshot_before_action($post_id, $action_type);
        $after_snapshot = $this->get_snapshot_after_action($post_id, $action_type, $days_window);
        
        if (!$before_snapshot || !$after_snapshot) {
            return [
                'correlation' => 0,
                'confidence' => 0,
                'reason' => 'Insufficient ranking data',
            ];
        }
        
        // Calculate ranking changes
        $ranking_changes = $this->calculate_ranking_changes(
            json_decode($before_snapshot->rankings, true),
            json_decode($after_snapshot->rankings, true)
        );
        
        // Get action impact score
        $impact_score = $this->get_action_impact_score($post_id, $action_type);
        
        // Calculate correlation
        $correlation = $this->calculate_correlation_coefficient(
            $ranking_changes,
            $impact_score
        );
        
        // Determine confidence
        $confidence = $this->calculate_correlation_confidence(
            $ranking_changes,
            $before_snapshot,
            $after_snapshot
        );
        
        $analysis = [
            'post_id' => $post_id,
            'action_type' => $action_type,
            'correlation' => round($correlation, 3),
            'correlation_strength' => $this->get_correlation_strength($correlation),
            'confidence' => $confidence,
            'ranking_changes' => $ranking_changes,
            'impact_score' => $impact_score,
            'time_window_days' => $days_window,
            'before_snapshot_date' => $before_snapshot->recorded_at,
            'after_snapshot_date' => $after_snapshot->recorded_at,
            'reasoning' => $this->generate_correlation_reasoning($correlation, $ranking_changes, $impact_score),
        ];
        
        // Store correlation result
        $this->store_correlation_result($analysis);
        
        error_log(sprintf(
            '[DODO Correlation] Analysis complete - Correlation: %.3f (%s), Confidence: %d%%',
            $correlation,
            $analysis['correlation_strength'],
            $confidence
        ));
        
        return $analysis;
    }
    
    /**
     * Get snapshot before action
     */
    private function get_snapshot_before_action($post_id, $action_type) {
        global $wpdb;
        
        // Get action timestamp from impact tracking
        $impact_table = $wpdb->prefix . 'dodo_impact_tracking';
        
        $action = $wpdb->get_row($wpdb->prepare(
            "SELECT started_at FROM {$impact_table}
            WHERE post_id = %d 
            AND action_type = %s
            ORDER BY started_at DESC
            LIMIT 1",
            $post_id,
            $action_type
        ));
        
        if (!$action) {
            return null;
        }
        
        // Get closest snapshot before action
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->ranking_table}
            WHERE post_id = %d
            AND recorded_at < %s
            ORDER BY recorded_at DESC
            LIMIT 1",
            $post_id,
            $action->started_at
        ));
    }
    
    /**
     * Get snapshot after action
     */
    private function get_snapshot_after_action($post_id, $action_type, $days_window) {
        global $wpdb;
        
        // Get action timestamp
        $impact_table = $wpdb->prefix . 'dodo_impact_tracking';
        
        $action = $wpdb->get_row($wpdb->prepare(
            "SELECT started_at FROM {$impact_table}
            WHERE post_id = %d 
            AND action_type = %s
            ORDER BY started_at DESC
            LIMIT 1",
            $post_id,
            $action_type
        ));
        
        if (!$action) {
            return null;
        }
        
        // Get snapshot within window after action
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->ranking_table}
            WHERE post_id = %d
            AND recorded_at > %s
            AND recorded_at <= DATE_ADD(%s, INTERVAL %d DAY)
            ORDER BY recorded_at DESC
            LIMIT 1",
            $post_id,
            $action->started_at,
            $action->started_at,
            $days_window
        ));
    }
    
    /**
     * Calculate ranking changes
     */
    private function calculate_ranking_changes($before, $after) {
        $changes = [
            'improved' => 0,
            'declined' => 0,
            'unchanged' => 0,
            'avg_change' => 0,
            'total_keywords' => 0,
            'details' => [],
        ];
        
        foreach ($after as $keyword => $after_pos) {
            if (!isset($before[$keyword])) {
                continue;
            }
            
            $before_pos = $before[$keyword];
            $change = $before_pos - $after_pos; // Positive = improvement
            
            $changes['details'][$keyword] = [
                'before' => $before_pos,
                'after' => $after_pos,
                'change' => $change,
            ];
            
            if ($change > 0) {
                $changes['improved']++;
            } elseif ($change < 0) {
                $changes['declined']++;
            } else {
                $changes['unchanged']++;
            }
            
            $changes['total_keywords']++;
        }
        
        if ($changes['total_keywords'] > 0) {
            $total_change = array_sum(array_column($changes['details'], 'change'));
            $changes['avg_change'] = round($total_change / $changes['total_keywords'], 2);
        }
        
        return $changes;
    }
    
    /**
     * Get action impact score
     */
    private function get_action_impact_score($post_id, $action_type) {
        global $wpdb;
        
        $impact_table = $wpdb->prefix . 'dodo_impact_tracking';
        
        $impact = $wpdb->get_var($wpdb->prepare(
            "SELECT JSON_EXTRACT(impact_analysis, '$.overall_score')
            FROM {$impact_table}
            WHERE post_id = %d
            AND action_type = %s
            AND status = 'completed'
            ORDER BY completed_at DESC
            LIMIT 1",
            $post_id,
            $action_type
        ));
        
        return $impact ? (int) $impact : 0;
    }
    
    /**
     * Calculate correlation coefficient
     * 
     * Simplified Pearson correlation between ranking improvement and impact score
     */
    private function calculate_correlation_coefficient($ranking_changes, $impact_score) {
        $avg_change = $ranking_changes['avg_change'];
        
        // Normalize both to -1 to 1 scale
        $normalized_ranking = $avg_change / 10; // Assume max change is 10 positions
        $normalized_ranking = max(-1, min(1, $normalized_ranking));
        
        $normalized_impact = $impact_score / 100; // Impact score is -100 to 100
        
        // Simple correlation: if both positive or both negative, correlation is positive
        if (($normalized_ranking > 0 && $normalized_impact > 0) ||
            ($normalized_ranking < 0 && $normalized_impact < 0)) {
            return abs($normalized_ranking * $normalized_impact);
        } else {
            return -abs($normalized_ranking * $normalized_impact);
        }
    }
    
    /**
     * Calculate correlation confidence
     */
    private function calculate_correlation_confidence($ranking_changes, $before_snapshot, $after_snapshot) {
        $confidence = 0;
        
        // More keywords = higher confidence
        $keyword_count = $ranking_changes['total_keywords'];
        if ($keyword_count >= 10) {
            $confidence += 40;
        } elseif ($keyword_count >= 5) {
            $confidence += 25;
        } elseif ($keyword_count >= 3) {
            $confidence += 15;
        }
        
        // Time window appropriateness
        $days_diff = (strtotime($after_snapshot->recorded_at) - strtotime($before_snapshot->recorded_at)) / 86400;
        if ($days_diff >= 7 && $days_diff <= 30) {
            $confidence += 30;
        } elseif ($days_diff >= 3 && $days_diff <= 60) {
            $confidence += 20;
        }
        
        // Consistency of changes (all improved or all declined = higher confidence)
        if ($keyword_count > 0) {
            $consistency = max(
                $ranking_changes['improved'] / $keyword_count,
                $ranking_changes['declined'] / $keyword_count
            );
            $confidence += round($consistency * 30);
        }
        
        return min(100, $confidence);
    }
    
    /**
     * Get correlation strength label
     */
    private function get_correlation_strength($correlation) {
        $abs_corr = abs($correlation);
        
        if ($abs_corr >= self::STRONG_CORRELATION) {
            return 'strong';
        } elseif ($abs_corr >= self::MODERATE_CORRELATION) {
            return 'moderate';
        } elseif ($abs_corr >= self::WEAK_CORRELATION) {
            return 'weak';
        } else {
            return 'negligible';
        }
    }
    
    /**
     * Generate correlation reasoning
     */
    private function generate_correlation_reasoning($correlation, $ranking_changes, $impact_score) {
        $reasoning = [];
        
        // Correlation interpretation
        if ($correlation > self::STRONG_CORRELATION) {
            $reasoning[] = sprintf(
                'Strong positive correlation (%.2f) - action likely caused ranking improvement',
                $correlation
            );
        } elseif ($correlation > self::MODERATE_CORRELATION) {
            $reasoning[] = sprintf(
                'Moderate positive correlation (%.2f) - action may have contributed to ranking improvement',
                $correlation
            );
        } elseif ($correlation < -self::STRONG_CORRELATION) {
            $reasoning[] = sprintf(
                'Strong negative correlation (%.2f) - action may have hurt rankings',
                $correlation
            );
        } else {
            $reasoning[] = sprintf(
                'Weak correlation (%.2f) - unclear if action affected rankings',
                $correlation
            );
        }
        
        // Ranking changes
        if ($ranking_changes['avg_change'] > 0) {
            $reasoning[] = sprintf(
                'Average ranking improved by %.1f positions (%d keywords improved, %d declined)',
                $ranking_changes['avg_change'],
                $ranking_changes['improved'],
                $ranking_changes['declined']
            );
        } elseif ($ranking_changes['avg_change'] < 0) {
            $reasoning[] = sprintf(
                'Average ranking declined by %.1f positions (%d keywords declined, %d improved)',
                abs($ranking_changes['avg_change']),
                $ranking_changes['declined'],
                $ranking_changes['improved']
            );
        } else {
            $reasoning[] = 'No significant ranking changes detected';
        }
        
        // Impact score
        if ($impact_score > 50) {
            $reasoning[] = sprintf('High impact score (%d) suggests positive SEO effect', $impact_score);
        } elseif ($impact_score < -20) {
            $reasoning[] = sprintf('Negative impact score (%d) suggests adverse effect', $impact_score);
        }
        
        return implode('. ', $reasoning);
    }
    
    /**
     * Store correlation result
     */
    private function store_correlation_result($analysis) {
        $option_key = sprintf(
            'dodo_correlation_%s_%d_%s',
            $analysis['action_type'],
            $analysis['post_id'],
            date('Ymd')
        );
        
        update_option($option_key, $analysis, false);
    }
    
    /**
     * Get correlation insights
     * 
     * @return array Correlation insights across all actions
     */
    public function get_correlation_insights() {
        global $wpdb;
        
        $insights = [
            'action_correlations' => [],
            'strongest_correlations' => [],
            'weakest_correlations' => [],
        ];
        
        // Get all correlation results
        $correlations = $wpdb->get_results(
            "SELECT option_name, option_value 
            FROM {$wpdb->options} 
            WHERE option_name LIKE 'dodo_correlation_%'
            ORDER BY option_name DESC
            LIMIT 100",
            ARRAY_A
        );
        
        $by_action = [];
        
        foreach ($correlations as $row) {
            $data = maybe_unserialize($row['option_value']);
            
            if (!$data || !isset($data['action_type'])) {
                continue;
            }
            
            $action_type = $data['action_type'];
            
            if (!isset($by_action[$action_type])) {
                $by_action[$action_type] = [
                    'correlations' => [],
                    'avg_correlation' => 0,
                    'sample_count' => 0,
                ];
            }
            
            $by_action[$action_type]['correlations'][] = $data['correlation'];
            $by_action[$action_type]['sample_count']++;
        }
        
        // Calculate averages
        foreach ($by_action as $action_type => $data) {
            $by_action[$action_type]['avg_correlation'] = round(
                array_sum($data['correlations']) / $data['sample_count'],
                3
            );
        }
        
        // Sort by correlation strength
        uasort($by_action, function($a, $b) {
            return abs($b['avg_correlation']) <=> abs($a['avg_correlation']);
        });
        
        $insights['action_correlations'] = $by_action;
        
        // Get strongest and weakest
        $insights['strongest_correlations'] = array_slice($by_action, 0, 5, true);
        $insights['weakest_correlations'] = array_slice(array_reverse($by_action, true), 0, 5, true);
        
        return $insights;
    }
    
    /**
     * Create database table
     */
    public static function create_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dodo_ranking_history';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            rankings longtext NOT NULL,
            trigger varchar(50) DEFAULT 'manual',
            recorded_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY recorded_at (recorded_at)
        ) {$charset_collate};";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        error_log('[DODO Correlation] Ranking history table created');
    }
}
