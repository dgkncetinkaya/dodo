<?php
/**
 * Self-Learning Priority Engine
 * 
 * Learns from results and adjusts priority weights automatically
 * Sprint E - Feedback Loop & Self-Learning SEO System
 * 
 * @package DODO_AI_SEO
 * @since 3.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Self_Learning_Priority {
    
    /**
     * Default priority weights
     */
    private $default_weights = array(
        'ctr_opportunity' => 25,
        'ranking_opportunity' => 20,
        'decay_opportunity' => 30,
        'cannibalization' => 15,
        'query_gap' => 10,
        'commercial_intent' => 15,
        'geo_score' => 10,
        'revenue_score' => 20,
        'conversion_probability' => 15,
        'topical_authority' => 10,
    );
    
    /**
     * Learn from winners
     * 
     * @return array Learning results
     */
    public function learn_from_winners() {
        $learning = array(
            'winners_analyzed' => 0,
            'patterns_found' => array(),
            'weight_adjustments' => array(),
            'insights' => array(),
        );
        
        try {
            // Get winning content (performance_score >= 70)
            $winners = $this->get_winning_content();
            
            $learning['winners_analyzed'] = count($winners);
            
            if (empty($winners)) {
                return $learning;
            }
            
            // Analyze common patterns
            $patterns = $this->analyze_winner_patterns($winners);
            $learning['patterns_found'] = $patterns;
            
            // Calculate weight adjustments
            $adjustments = $this->calculate_weight_adjustments($patterns);
            $learning['weight_adjustments'] = $adjustments;
            
            // Apply adjustments
            $this->apply_weight_adjustments($adjustments);
            
            // Generate insights
            $learning['insights'] = $this->generate_learning_insights($patterns, $adjustments);
            
            // Save learning data
            $this->save_learning_data($learning);
            
        } catch (Throwable $e) {
            error_log('[DODO][Self Learning] Error learning from winners: ' . $e->getMessage());
        }
        
        return $learning;
    }
    
    /**
     * Learn from failures
     * 
     * @return array Learning results
     */
    public function learn_from_failures() {
        $learning = array(
            'failures_analyzed' => 0,
            'patterns_found' => array(),
            'weight_adjustments' => array(),
            'insights' => array(),
        );
        
        try {
            // Get failing content (performance_score < 40)
            $failures = $this->get_failing_content();
            
            $learning['failures_analyzed'] = count($failures);
            
            if (empty($failures)) {
                return $learning;
            }
            
            // Analyze common patterns
            $patterns = $this->analyze_failure_patterns($failures);
            $learning['patterns_found'] = $patterns;
            
            // Calculate weight adjustments (inverse)
            $adjustments = $this->calculate_failure_adjustments($patterns);
            $learning['weight_adjustments'] = $adjustments;
            
            // Apply adjustments
            $this->apply_weight_adjustments($adjustments);
            
            // Generate insights
            $learning['insights'] = $this->generate_failure_insights($patterns, $adjustments);
            
            // Save learning data
            $this->save_learning_data($learning);
            
        } catch (Throwable $e) {
            error_log('[DODO][Self Learning] Error learning from failures: ' . $e->getMessage());
        }
        
        return $learning;
    }
    
    /**
     * Get winning content
     * 
     * @return array Winners
     */
    private function get_winning_content() {
        global $wpdb;
        
        $post_ids = $wpdb->get_col(
            "SELECT post_id 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_dodo_performance_score' 
            AND CAST(meta_value AS UNSIGNED) >= 70
            ORDER BY CAST(meta_value AS UNSIGNED) DESC
            LIMIT 50"
        );
        
        $winners = array();
        
        foreach ($post_ids as $post_id) {
            $winners[] = array(
                'post_id' => $post_id,
                'performance_score' => get_post_meta($post_id, '_dodo_performance_score', true),
                'growth_rate' => get_post_meta($post_id, '_dodo_growth_rate', true),
                'source' => get_post_meta($post_id, '_dodo_opportunity_source', true),
                'priority_score' => get_post_meta($post_id, '_dodo_priority_score', true),
                'commercial_score' => get_post_meta($post_id, '_dodo_commercial_score', true),
                'geo_score' => get_post_meta($post_id, '_dodo_geo_score', true),
                'revenue_score' => get_post_meta($post_id, '_dodo_revenue_score', true),
                'conversion_score' => get_post_meta($post_id, '_dodo_conversion_score', true),
            );
        }
        
        return $winners;
    }
    
    /**
     * Get failing content
     * 
     * @return array Failures
     */
    private function get_failing_content() {
        global $wpdb;
        
        $post_ids = $wpdb->get_col(
            "SELECT post_id 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_dodo_performance_score' 
            AND CAST(meta_value AS UNSIGNED) < 40
            ORDER BY CAST(meta_value AS UNSIGNED) ASC
            LIMIT 50"
        );
        
        $failures = array();
        
        foreach ($post_ids as $post_id) {
            $failures[] = array(
                'post_id' => $post_id,
                'performance_score' => get_post_meta($post_id, '_dodo_performance_score', true),
                'growth_rate' => get_post_meta($post_id, '_dodo_growth_rate', true),
                'source' => get_post_meta($post_id, '_dodo_opportunity_source', true),
                'priority_score' => get_post_meta($post_id, '_dodo_priority_score', true),
                'commercial_score' => get_post_meta($post_id, '_dodo_commercial_score', true),
                'geo_score' => get_post_meta($post_id, '_dodo_geo_score', true),
                'revenue_score' => get_post_meta($post_id, '_dodo_revenue_score', true),
                'conversion_score' => get_post_meta($post_id, '_dodo_conversion_score', true),
            );
        }
        
        return $failures;
    }
    
    /**
     * Analyze winner patterns
     * 
     * @param array $winners
     * @return array Patterns
     */
    private function analyze_winner_patterns($winners) {
        $patterns = array(
            'source_distribution' => array(),
            'avg_scores' => array(),
            'common_traits' => array(),
        );
        
        // Source distribution
        $sources = array_column($winners, 'source');
        $source_counts = array_count_values(array_filter($sources));
        
        arsort($source_counts);
        $patterns['source_distribution'] = $source_counts;
        
        // Average scores
        $patterns['avg_scores'] = array(
            'commercial' => $this->calculate_average($winners, 'commercial_score'),
            'geo' => $this->calculate_average($winners, 'geo_score'),
            'revenue' => $this->calculate_average($winners, 'revenue_score'),
            'conversion' => $this->calculate_average($winners, 'conversion_score'),
        );
        
        // Common traits
        if ($patterns['avg_scores']['commercial'] > 60) {
            $patterns['common_traits'][] = 'Yüksek commercial intent';
        }
        
        if ($patterns['avg_scores']['geo'] > 70) {
            $patterns['common_traits'][] = 'Güçlü GEO optimizasyonu';
        }
        
        if ($patterns['avg_scores']['revenue'] > 65) {
            $patterns['common_traits'][] = 'Yüksek revenue potansiyeli';
        }
        
        if ($patterns['avg_scores']['conversion'] > 60) {
            $patterns['common_traits'][] = 'İyi conversion sinyalleri';
        }
        
        return $patterns;
    }
    
    /**
     * Analyze failure patterns
     * 
     * @param array $failures
     * @return array Patterns
     */
    private function analyze_failure_patterns($failures) {
        $patterns = array(
            'source_distribution' => array(),
            'avg_scores' => array(),
            'common_issues' => array(),
        );
        
        // Source distribution
        $sources = array_column($failures, 'source');
        $source_counts = array_count_values(array_filter($sources));
        
        arsort($source_counts);
        $patterns['source_distribution'] = $source_counts;
        
        // Average scores
        $patterns['avg_scores'] = array(
            'commercial' => $this->calculate_average($failures, 'commercial_score'),
            'geo' => $this->calculate_average($failures, 'geo_score'),
            'revenue' => $this->calculate_average($failures, 'revenue_score'),
            'conversion' => $this->calculate_average($failures, 'conversion_score'),
        );
        
        // Common issues
        if ($patterns['avg_scores']['commercial'] < 30) {
            $patterns['common_issues'][] = 'Düşük commercial intent';
        }
        
        if ($patterns['avg_scores']['geo'] < 40) {
            $patterns['common_issues'][] = 'Zayıf GEO optimizasyonu';
        }
        
        if ($patterns['avg_scores']['revenue'] < 35) {
            $patterns['common_issues'][] = 'Düşük revenue potansiyeli';
        }
        
        if ($patterns['avg_scores']['conversion'] < 30) {
            $patterns['common_issues'][] = 'Zayıf conversion sinyalleri';
        }
        
        return $patterns;
    }
    
    /**
     * Calculate average for a field
     * 
     * @param array $items
     * @param string $field
     * @return float Average
     */
    private function calculate_average($items, $field) {
        $values = array_filter(array_column($items, $field), function($val) {
            return $val !== '' && $val !== null;
        });
        
        if (empty($values)) {
            return 0;
        }
        
        return round(array_sum($values) / count($values), 1);
    }
    
    /**
     * Calculate weight adjustments from winner patterns
     * 
     * @param array $patterns
     * @return array Adjustments
     */
    private function calculate_weight_adjustments($patterns) {
        $adjustments = array();
        
        // Adjust based on source distribution
        if (!empty($patterns['source_distribution'])) {
            $top_source = array_key_first($patterns['source_distribution']);
            $top_count = $patterns['source_distribution'][$top_source];
            $total = array_sum($patterns['source_distribution']);
            
            // If one source dominates (>40%), increase its weight
            if ($top_count / $total > 0.4) {
                $adjustments[$top_source] = 5; // +5 points
            }
        }
        
        // Adjust based on average scores
        $avg_scores = $patterns['avg_scores'];
        
        if ($avg_scores['commercial'] > 60) {
            $adjustments['commercial_intent'] = 3;
        }
        
        if ($avg_scores['geo'] > 70) {
            $adjustments['geo_score'] = 3;
        }
        
        if ($avg_scores['revenue'] > 65) {
            $adjustments['revenue_score'] = 4;
        }
        
        if ($avg_scores['conversion'] > 60) {
            $adjustments['conversion_probability'] = 3;
        }
        
        return $adjustments;
    }
    
    /**
     * Calculate weight adjustments from failure patterns
     * 
     * @param array $patterns
     * @return array Adjustments (negative)
     */
    private function calculate_failure_adjustments($patterns) {
        $adjustments = array();
        
        // Adjust based on source distribution (decrease weight)
        if (!empty($patterns['source_distribution'])) {
            $top_source = array_key_first($patterns['source_distribution']);
            $top_count = $patterns['source_distribution'][$top_source];
            $total = array_sum($patterns['source_distribution']);
            
            // If one source dominates failures (>40%), decrease its weight
            if ($top_count / $total > 0.4) {
                $adjustments[$top_source] = -3; // -3 points
            }
        }
        
        // Adjust based on average scores (if very low, decrease weight)
        $avg_scores = $patterns['avg_scores'];
        
        if ($avg_scores['commercial'] < 30) {
            $adjustments['commercial_intent'] = -2;
        }
        
        if ($avg_scores['geo'] < 40) {
            $adjustments['geo_score'] = -2;
        }
        
        if ($avg_scores['revenue'] < 35) {
            $adjustments['revenue_score'] = -2;
        }
        
        if ($avg_scores['conversion'] < 30) {
            $adjustments['conversion_probability'] = -2;
        }
        
        return $adjustments;
    }
    
    /**
     * Apply weight adjustments
     * 
     * @param array $adjustments
     */
    public function apply_weight_adjustments($adjustments) {
        if (empty($adjustments)) {
            return;
        }
        
        // Get current weights
        $current_weights = $this->get_current_weights();
        
        // Apply adjustments
        foreach ($adjustments as $key => $adjustment) {
            if (isset($current_weights[$key])) {
                $new_weight = $current_weights[$key] + $adjustment;
                
                // Keep weights between 5 and 40
                $new_weight = max(5, min(40, $new_weight));
                
                $current_weights[$key] = $new_weight;
            }
        }
        
        // Save updated weights
        update_option('dodo_priority_weights', $current_weights, false);
        
        error_log('[DODO][Self Learning] Weights adjusted: ' . json_encode($adjustments));
    }
    
    /**
     * Get current weights
     * 
     * @return array Current weights
     */
    public function get_current_weights() {
        $weights = get_option('dodo_priority_weights', $this->default_weights);
        
        // Ensure all default keys exist
        foreach ($this->default_weights as $key => $value) {
            if (!isset($weights[$key])) {
                $weights[$key] = $value;
            }
        }
        
        return $weights;
    }
    
    /**
     * Reset weights to default
     */
    public function reset_weights() {
        update_option('dodo_priority_weights', $this->default_weights, false);
        error_log('[DODO][Self Learning] Weights reset to default');
    }
    
    /**
     * Generate learning insights
     * 
     * @param array $patterns
     * @param array $adjustments
     * @return array Insights
     */
    private function generate_learning_insights($patterns, $adjustments) {
        $insights = array();
        
        // Source insights
        if (!empty($patterns['source_distribution'])) {
            $top_source = array_key_first($patterns['source_distribution']);
            $insights[] = "En başarılı kaynak: {$top_source}";
        }
        
        // Score insights
        foreach ($patterns['avg_scores'] as $type => $score) {
            if ($score > 60) {
                $insights[] = "Kazanan içeriklerde yüksek {$type} skoru: {$score}";
            }
        }
        
        // Adjustment insights
        foreach ($adjustments as $key => $adjustment) {
            if ($adjustment > 0) {
                $insights[] = "{$key} ağırlığı +{$adjustment} artırıldı";
            }
        }
        
        return $insights;
    }
    
    /**
     * Generate failure insights
     * 
     * @param array $patterns
     * @param array $adjustments
     * @return array Insights
     */
    private function generate_failure_insights($patterns, $adjustments) {
        $insights = array();
        
        // Source insights
        if (!empty($patterns['source_distribution'])) {
            $top_source = array_key_first($patterns['source_distribution']);
            $insights[] = "En çok başarısız kaynak: {$top_source}";
        }
        
        // Score insights
        foreach ($patterns['avg_scores'] as $type => $score) {
            if ($score < 40) {
                $insights[] = "Başarısız içeriklerde düşük {$type} skoru: {$score}";
            }
        }
        
        // Adjustment insights
        foreach ($adjustments as $key => $adjustment) {
            if ($adjustment < 0) {
                $insights[] = "{$key} ağırlığı {$adjustment} azaltıldı";
            }
        }
        
        return $insights;
    }
    
    /**
     * Save learning data
     * 
     * @param array $learning
     */
    private function save_learning_data($learning) {
        $history = get_option('dodo_learning_history', array());
        
        $history[] = array(
            'date' => current_time('mysql'),
            'learning' => $learning,
        );
        
        // Keep last 30 learning sessions
        $history = array_slice($history, -30);
        
        update_option('dodo_learning_history', $history, false);
    }
    
    /**
     * Get learning history
     * 
     * @return array History
     */
    public function get_learning_history() {
        return get_option('dodo_learning_history', array());
    }
    
    /**
     * Get learning statistics
     * 
     * @return array Statistics
     */
    public function get_statistics() {
        $stats = array(
            'total_learning_sessions' => 0,
            'total_adjustments' => 0,
            'current_weights' => array(),
            'weight_changes' => array(),
        );
        
        try {
            $history = $this->get_learning_history();
            $stats['total_learning_sessions'] = count($history);
            
            // Count total adjustments
            foreach ($history as $session) {
                if (isset($session['learning']['weight_adjustments'])) {
                    $stats['total_adjustments'] += count($session['learning']['weight_adjustments']);
                }
            }
            
            // Current weights
            $stats['current_weights'] = $this->get_current_weights();
            
            // Calculate weight changes from default
            foreach ($this->default_weights as $key => $default_value) {
                $current_value = $stats['current_weights'][$key] ?? $default_value;
                $change = $current_value - $default_value;
                
                if ($change != 0) {
                    $stats['weight_changes'][$key] = array(
                        'default' => $default_value,
                        'current' => $current_value,
                        'change' => $change,
                    );
                }
            }
            
        } catch (Throwable $e) {
            error_log('[DODO][Self Learning] Error getting statistics: ' . $e->getMessage());
        }
        
        return $stats;
    }
    
    /**
     * Run automatic learning (called by cron)
     */
    public function run_automatic_learning() {
        error_log('[DODO][Self Learning] === AUTOMATIC LEARNING START ===');
        
        try {
            // Learn from winners
            $winner_learning = $this->learn_from_winners();
            error_log('[DODO][Self Learning] Winners analyzed: ' . $winner_learning['winners_analyzed']);
            
            // Learn from failures
            $failure_learning = $this->learn_from_failures();
            error_log('[DODO][Self Learning] Failures analyzed: ' . $failure_learning['failures_analyzed']);
            
            // Log insights
            $all_insights = array_merge(
                $winner_learning['insights'] ?? array(),
                $failure_learning['insights'] ?? array()
            );
            
            foreach ($all_insights as $insight) {
                error_log('[DODO][Self Learning] Insight: ' . $insight);
            }
            
            error_log('[DODO][Self Learning] === AUTOMATIC LEARNING END ===');
            
        } catch (Throwable $e) {
            error_log('[DODO][Self Learning] Error in automatic learning: ' . $e->getMessage());
        }
    }
}
