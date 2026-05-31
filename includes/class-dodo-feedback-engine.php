<?php
/**
 * Feedback Learning Engine
 * 
 * Learns from SEO outcomes and user actions
 * Adapts recommendations based on what actually works
 * 
 * @package DODO_AI_SEO
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Feedback_Engine {
    
    /**
     * Learning confidence thresholds
     */
    const MIN_SAMPLES = 5; // Minimum samples before learning
    const HIGH_CONFIDENCE = 80;
    const MEDIUM_CONFIDENCE = 60;
    const LOW_CONFIDENCE = 40;
    
    /**
     * Learning rate (how fast to adapt)
     */
    const LEARNING_RATE = 0.1; // 10% adaptation per iteration
    
    /**
     * Database tables
     */
    private $learning_table;
    private $feedback_table;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->learning_table = $wpdb->prefix . 'dodo_learning_events';
        $this->feedback_table = $wpdb->prefix . 'dodo_feedback_events';
    }
    
    /**
     * Learn from impact tracking results
     * 
     * @param array $impact_data Impact analysis from tracker
     * @param string $context Context (niche, intent, etc.)
     */
    public function learn_from_impact($impact_data, $context = []) {
        global $wpdb;
        
        // Only learn from high-confidence results
        if ($impact_data['confidence'] < self::LOW_CONFIDENCE) {
            error_log('[DODO Learning] Skipping low-confidence result');
            return false;
        }
        
        $action_type = $impact_data['action_type'];
        $overall_score = $impact_data['overall_score'];
        
        error_log(sprintf(
            '[DODO Learning] Learning from %s action - Score: %d, Confidence: %d',
            $action_type,
            $overall_score,
            $impact_data['confidence']
        ));
        
        // Record learning event
        $wpdb->insert(
            $this->learning_table,
            [
                'event_type' => 'impact_result',
                'action_type' => $action_type,
                'outcome_score' => $overall_score,
                'confidence' => $impact_data['confidence'],
                'context' => json_encode($context),
                'impact_details' => json_encode($impact_data),
                'recorded_at' => current_time('mysql'),
            ],
            ['%s', '%s', '%d', '%d', '%s', '%s', '%s']
        );
        
        // Update learned patterns
        $this->update_learned_patterns($action_type, $overall_score, $context);
        
        return true;
    }
    
    /**
     * Learn from user feedback
     * 
     * @param string $recommendation_type Recommendation type
     * @param string $action User action (accepted, rejected, modified, ignored)
     * @param array $details Feedback details
     */
    public function learn_from_user_feedback($recommendation_type, $action, $details = []) {
        global $wpdb;
        
        error_log(sprintf(
            '[DODO Learning] User feedback - Type: %s, Action: %s',
            $recommendation_type,
            $action
        ));
        
        // Record feedback event
        $wpdb->insert(
            $this->feedback_table,
            [
                'recommendation_type' => $recommendation_type,
                'user_action' => $action,
                'details' => json_encode($details),
                'user_id' => get_current_user_id(),
                'recorded_at' => current_time('mysql'),
            ],
            ['%s', '%s', '%s', '%d', '%s']
        );
        
        // Update recommendation quality scores
        $this->update_recommendation_quality($recommendation_type, $action);
        
        return true;
    }
    
    /**
     * Update learned patterns
     */
    private function update_learned_patterns($action_type, $outcome_score, $context) {
        // Get existing pattern
        $pattern = $this->get_learned_pattern($action_type, $context);
        
        if (!$pattern) {
            // Create new pattern
            $pattern = [
                'action_type' => $action_type,
                'context' => $context,
                'avg_score' => $outcome_score,
                'sample_count' => 1,
                'confidence' => self::LOW_CONFIDENCE,
                'last_updated' => current_time('mysql'),
            ];
        } else {
            // Update existing pattern with exponential moving average
            $old_avg = $pattern['avg_score'];
            $new_avg = $old_avg + (self::LEARNING_RATE * ($outcome_score - $old_avg));
            
            $pattern['avg_score'] = round($new_avg, 2);
            $pattern['sample_count']++;
            $pattern['last_updated'] = current_time('mysql');
            
            // Update confidence based on sample count
            $pattern['confidence'] = $this->calculate_pattern_confidence($pattern['sample_count']);
        }
        
        // Save pattern
        $this->save_learned_pattern($pattern);
        
        error_log(sprintf(
            '[DODO Learning] Pattern updated - %s: Avg Score: %.2f, Samples: %d, Confidence: %d',
            $action_type,
            $pattern['avg_score'],
            $pattern['sample_count'],
            $pattern['confidence']
        ));
    }
    
    /**
     * Calculate pattern confidence based on sample count
     */
    private function calculate_pattern_confidence($sample_count) {
        if ($sample_count >= 20) {
            return self::HIGH_CONFIDENCE;
        } elseif ($sample_count >= 10) {
            return self::MEDIUM_CONFIDENCE;
        } elseif ($sample_count >= self::MIN_SAMPLES) {
            return self::LOW_CONFIDENCE;
        }
        
        return 0;
    }
    
    /**
     * Get learned pattern
     */
    private function get_learned_pattern($action_type, $context) {
        $context_key = $this->generate_context_key($context);
        $option_key = "dodo_learned_pattern_{$action_type}_{$context_key}";
        
        return get_option($option_key, null);
    }
    
    /**
     * Save learned pattern
     */
    private function save_learned_pattern($pattern) {
        $context_key = $this->generate_context_key($pattern['context']);
        $option_key = "dodo_learned_pattern_{$pattern['action_type']}_{$context_key}";
        
        update_option($option_key, $pattern);
    }
    
    /**
     * Generate context key
     */
    private function generate_context_key($context) {
        if (empty($context)) {
            return 'default';
        }
        
        ksort($context);
        return md5(json_encode($context));
    }
    
    /**
     * Update recommendation quality
     */
    private function update_recommendation_quality($recommendation_type, $user_action) {
        $quality_key = "dodo_recommendation_quality_{$recommendation_type}";
        $quality = get_option($quality_key, [
            'accepted' => 0,
            'rejected' => 0,
            'modified' => 0,
            'ignored' => 0,
            'total' => 0,
        ]);
        
        // Increment counters
        if (isset($quality[$user_action])) {
            $quality[$user_action]++;
        }
        $quality['total']++;
        
        // Calculate acceptance rate
        $quality['acceptance_rate'] = $quality['total'] > 0 
            ? round(($quality['accepted'] / $quality['total']) * 100, 1) 
            : 0;
        
        update_option($quality_key, $quality);
        
        error_log(sprintf(
            '[DODO Learning] Recommendation quality updated - %s: Acceptance Rate: %.1f%%',
            $recommendation_type,
            $quality['acceptance_rate']
        ));
    }
    
    /**
     * Get recommendation for action type
     * 
     * Returns learned recommendation with confidence
     * 
     * @param string $action_type Action type
     * @param array $context Context
     * @return array|null Recommendation or null
     */
    public function get_recommendation($action_type, $context = []) {
        $pattern = $this->get_learned_pattern($action_type, $context);
        
        if (!$pattern || $pattern['sample_count'] < self::MIN_SAMPLES) {
            // Not enough data - return default
            return null;
        }
        
        return [
            'action_type' => $action_type,
            'expected_score' => $pattern['avg_score'],
            'confidence' => $pattern['confidence'],
            'sample_count' => $pattern['sample_count'],
            'reasoning' => $this->generate_reasoning($pattern),
        ];
    }
    
    /**
     * Generate reasoning for recommendation
     */
    private function generate_reasoning($pattern) {
        $action_labels = [
            'title_rewrite' => 'Title rewrites',
            'meta_rewrite' => 'Meta description rewrites',
            'content_refresh' => 'Content refreshes',
            'faq_update' => 'FAQ updates',
            'internal_links' => 'Internal link additions',
            'semantic_expansion' => 'Semantic expansions',
            'geo_optimization' => 'GEO optimizations',
            'humanization' => 'Humanization improvements',
        ];
        
        $action_label = $action_labels[$pattern['action_type']] ?? $pattern['action_type'];
        
        if ($pattern['avg_score'] > 50) {
            return sprintf(
                '%s have shown positive impact (avg score: %.1f) across %d cases',
                $action_label,
                $pattern['avg_score'],
                $pattern['sample_count']
            );
        } elseif ($pattern['avg_score'] < -20) {
            return sprintf(
                '%s have shown negative impact (avg score: %.1f) across %d cases - not recommended',
                $action_label,
                $pattern['avg_score'],
                $pattern['sample_count']
            );
        } else {
            return sprintf(
                '%s have shown neutral impact (avg score: %.1f) across %d cases',
                $action_label,
                $pattern['avg_score'],
                $pattern['sample_count']
            );
        }
    }
    
    /**
     * Get learning insights
     * 
     * @return array Learning insights
     */
    public function get_learning_insights() {
        global $wpdb;
        
        $insights = [
            'total_learning_events' => 0,
            'total_feedback_events' => 0,
            'best_performing_actions' => [],
            'worst_performing_actions' => [],
            'recommendation_quality' => [],
            'confidence_distribution' => [],
        ];
        
        // Total events
        $insights['total_learning_events'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->learning_table}"
        );
        
        $insights['total_feedback_events'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->feedback_table}"
        );
        
        // Best performing actions
        $insights['best_performing_actions'] = $wpdb->get_results(
            "SELECT 
                action_type,
                AVG(outcome_score) as avg_score,
                COUNT(*) as sample_count
            FROM {$this->learning_table}
            WHERE confidence >= " . self::MEDIUM_CONFIDENCE . "
            GROUP BY action_type
            HAVING sample_count >= " . self::MIN_SAMPLES . "
            ORDER BY avg_score DESC
            LIMIT 5",
            ARRAY_A
        );
        
        // Worst performing actions
        $insights['worst_performing_actions'] = $wpdb->get_results(
            "SELECT 
                action_type,
                AVG(outcome_score) as avg_score,
                COUNT(*) as sample_count
            FROM {$this->learning_table}
            WHERE confidence >= " . self::MEDIUM_CONFIDENCE . "
            GROUP BY action_type
            HAVING sample_count >= " . self::MIN_SAMPLES . "
            ORDER BY avg_score ASC
            LIMIT 5",
            ARRAY_A
        );
        
        // Recommendation quality
        $recommendation_types = [
            'title_rewrite',
            'meta_rewrite',
            'content_refresh',
            'faq_update',
            'internal_links',
        ];
        
        foreach ($recommendation_types as $type) {
            $quality = get_option("dodo_recommendation_quality_{$type}", null);
            if ($quality) {
                $insights['recommendation_quality'][$type] = $quality;
            }
        }
        
        return $insights;
    }
    
    /**
     * Reset learning (use with caution)
     */
    public function reset_learning($action_type = null) {
        global $wpdb;
        
        if ($action_type) {
            // Reset specific action type
            $wpdb->delete(
                $this->learning_table,
                ['action_type' => $action_type],
                ['%s']
            );
            
            // Delete learned patterns
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$wpdb->options} 
                WHERE option_name LIKE %s",
                'dodo_learned_pattern_' . $action_type . '_%'
            ));
            
            error_log("[DODO Learning] Reset learning for: {$action_type}");
        } else {
            // Reset all learning
            $wpdb->query("TRUNCATE TABLE {$this->learning_table}");
            $wpdb->query("TRUNCATE TABLE {$this->feedback_table}");
            
            // Delete all learned patterns
            $wpdb->query(
                "DELETE FROM {$wpdb->options} 
                WHERE option_name LIKE 'dodo_learned_pattern_%'"
            );
            
            error_log('[DODO Learning] Reset all learning data');
        }
        
        return true;
    }
    
    /**
     * Create database tables
     */
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // Learning events table
        $learning_table = $wpdb->prefix . 'dodo_learning_events';
        $sql1 = "CREATE TABLE IF NOT EXISTS {$learning_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            event_type varchar(50) NOT NULL,
            action_type varchar(50) NOT NULL,
            outcome_score int(11) NOT NULL,
            confidence int(11) NOT NULL,
            context longtext,
            impact_details longtext,
            recorded_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY event_type (event_type),
            KEY action_type (action_type),
            KEY recorded_at (recorded_at)
        ) {$charset_collate};";
        
        // Feedback events table
        $feedback_table = $wpdb->prefix . 'dodo_feedback_events';
        $sql2 = "CREATE TABLE IF NOT EXISTS {$feedback_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            recommendation_type varchar(50) NOT NULL,
            user_action varchar(20) NOT NULL,
            details longtext,
            user_id bigint(20) NOT NULL,
            recorded_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY recommendation_type (recommendation_type),
            KEY user_action (user_action),
            KEY user_id (user_id),
            KEY recorded_at (recorded_at)
        ) {$charset_collate};";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql1);
        dbDelta($sql2);
        
        error_log('[DODO Learning] Learning tables created');
    }
}
