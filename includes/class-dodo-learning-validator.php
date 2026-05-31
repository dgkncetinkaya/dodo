<?php
/**
 * Learning Validation System
 * 
 * Validates learning safety and prevents false learning
 * Implements noisy data filtering, cooldown periods, and validation checks
 * 
 * @package DODO_AI_SEO
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Learning_Validator {
    
    /**
     * Validation thresholds
     */
    const MIN_SAMPLES_STRICT = 10;
    const MIN_SAMPLES_RELAXED = 5;
    const MIN_CONFIDENCE = 60;
    const MAX_VARIANCE = 30; // Max 30% variance in outcomes
    
    /**
     * Cooldown periods (seconds)
     */
    const COOLDOWN_AFTER_LEARNING = 3600; // 1 hour
    const COOLDOWN_AFTER_ROLLBACK = 7200; // 2 hours
    
    /**
     * Noise detection thresholds
     */
    const OUTLIER_THRESHOLD = 2.5; // Standard deviations
    const MIN_CONSISTENCY = 0.6; // 60% consistency required
    
    /**
     * Feedback engine
     */
    private $feedback_engine;
    
    /**
     * Constructor
     */
    public function __construct() {
        if (class_exists('DODO_Feedback_Engine')) {
            require_once plugin_dir_path(__FILE__) . 'class-dodo-feedback-engine.php';
            $this->feedback_engine = new DODO_Feedback_Engine();
        }
    }
    
    /**
     * Validate learning safety
     * 
     * @param string $action_type Action type
     * @param array $context Context
     * @param array $new_outcome New outcome to learn from
     * @return array Validation result
     */
    public function validate_learning($action_type, $context, $new_outcome) {
        error_log(sprintf(
            '[DODO Validator] Validating learning - Action: %s, Score: %d',
            $action_type,
            $new_outcome['score']
        ));
        
        $validation = [
            'safe' => true,
            'confidence' => 100,
            'warnings' => [],
            'blocks' => [],
            'reasoning' => [],
        ];
        
        // Check cooldown period
        $cooldown_check = $this->check_cooldown($action_type, $context);
        if (!$cooldown_check['allowed']) {
            $validation['safe'] = false;
            $validation['blocks'][] = 'cooldown_active';
            $validation['reasoning'][] = $cooldown_check['reason'];
            return $validation;
        }
        
        // Check minimum samples
        $sample_check = $this->check_minimum_samples($action_type, $context);
        if (!$sample_check['sufficient']) {
            $validation['safe'] = false;
            $validation['blocks'][] = 'insufficient_samples';
            $validation['reasoning'][] = $sample_check['reason'];
            return $validation;
        }
        
        // Check for noisy data
        $noise_check = $this->detect_noisy_data($action_type, $context, $new_outcome);
        if ($noise_check['is_noisy']) {
            $validation['safe'] = false;
            $validation['blocks'][] = 'noisy_data';
            $validation['reasoning'][] = $noise_check['reason'];
            return $validation;
        }
        
        // Check outcome consistency
        $consistency_check = $this->check_outcome_consistency($action_type, $context, $new_outcome);
        if (!$consistency_check['consistent']) {
            $validation['warnings'][] = 'low_consistency';
            $validation['confidence'] -= 20;
            $validation['reasoning'][] = $consistency_check['reason'];
        }
        
        // Check for extreme variance
        $variance_check = $this->check_variance($action_type, $context, $new_outcome);
        if ($variance_check['high_variance']) {
            $validation['warnings'][] = 'high_variance';
            $validation['confidence'] -= 15;
            $validation['reasoning'][] = $variance_check['reason'];
        }
        
        // Check confidence threshold
        if ($validation['confidence'] < self::MIN_CONFIDENCE) {
            $validation['safe'] = false;
            $validation['blocks'][] = 'low_confidence';
            $validation['reasoning'][] = sprintf(
                'Validation confidence (%d%%) below minimum threshold (%d%%)',
                $validation['confidence'],
                self::MIN_CONFIDENCE
            );
        }
        
        if ($validation['safe']) {
            $validation['reasoning'][] = 'All validation checks passed - safe to learn';
        }
        
        error_log(sprintf(
            '[DODO Validator] Validation result - Safe: %s, Confidence: %d%%',
            $validation['safe'] ? 'YES' : 'NO',
            $validation['confidence']
        ));
        
        return $validation;
    }
    
    /**
     * Check cooldown period
     */
    private function check_cooldown($action_type, $context) {
        $context_key = $this->generate_context_key($context);
        $cooldown_key = "dodo_learning_cooldown_{$action_type}_{$context_key}";
        
        $last_learning = get_option($cooldown_key, 0);
        $time_since = time() - $last_learning;
        
        if ($time_since < self::COOLDOWN_AFTER_LEARNING) {
            $remaining = self::COOLDOWN_AFTER_LEARNING - $time_since;
            return [
                'allowed' => false,
                'reason' => sprintf(
                    'Cooldown active - %d seconds remaining',
                    $remaining
                ),
            ];
        }
        
        return [
            'allowed' => true,
            'reason' => 'No cooldown active',
        ];
    }
    
    /**
     * Check minimum samples
     */
    private function check_minimum_samples($action_type, $context) {
        global $wpdb;
        
        $learning_table = $wpdb->prefix . 'dodo_learning_events';
        $context_json = json_encode($context);
        
        $sample_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$learning_table}
            WHERE action_type = %s
            AND context = %s",
            $action_type,
            $context_json
        ));
        
        if ($sample_count < self::MIN_SAMPLES_RELAXED) {
            return [
                'sufficient' => false,
                'reason' => sprintf(
                    'Only %d samples available (minimum: %d)',
                    $sample_count,
                    self::MIN_SAMPLES_RELAXED
                ),
            ];
        }
        
        return [
            'sufficient' => true,
            'reason' => sprintf('%d samples available', $sample_count),
        ];
    }
    
    /**
     * Detect noisy data
     */
    private function detect_noisy_data($action_type, $context, $new_outcome) {
        global $wpdb;
        
        $learning_table = $wpdb->prefix . 'dodo_learning_events';
        $context_json = json_encode($context);
        
        // Get recent outcomes
        $recent_outcomes = $wpdb->get_results($wpdb->prepare(
            "SELECT outcome_score FROM {$learning_table}
            WHERE action_type = %s
            AND context = %s
            ORDER BY recorded_at DESC
            LIMIT 20",
            $action_type,
            $context_json
        ), ARRAY_A);
        
        if (empty($recent_outcomes)) {
            return [
                'is_noisy' => false,
                'reason' => 'No historical data for comparison',
            ];
        }
        
        $scores = array_column($recent_outcomes, 'outcome_score');
        $scores[] = $new_outcome['score'];
        
        // Calculate statistics
        $mean = array_sum($scores) / count($scores);
        $variance = 0;
        
        foreach ($scores as $score) {
            $variance += pow($score - $mean, 2);
        }
        
        $variance = $variance / count($scores);
        $std_dev = sqrt($variance);
        
        // Check if new outcome is an outlier
        if ($std_dev > 0) {
            $z_score = abs(($new_outcome['score'] - $mean) / $std_dev);
            
            if ($z_score > self::OUTLIER_THRESHOLD) {
                return [
                    'is_noisy' => true,
                    'reason' => sprintf(
                        'Outcome is statistical outlier (z-score: %.2f, threshold: %.2f)',
                        $z_score,
                        self::OUTLIER_THRESHOLD
                    ),
                    'z_score' => $z_score,
                ];
            }
        }
        
        return [
            'is_noisy' => false,
            'reason' => 'Outcome within expected range',
        ];
    }
    
    /**
     * Check outcome consistency
     */
    private function check_outcome_consistency($action_type, $context, $new_outcome) {
        global $wpdb;
        
        $learning_table = $wpdb->prefix . 'dodo_learning_events';
        $context_json = json_encode($context);
        
        // Get recent outcomes
        $recent_outcomes = $wpdb->get_results($wpdb->prepare(
            "SELECT outcome_score FROM {$learning_table}
            WHERE action_type = %s
            AND context = %s
            ORDER BY recorded_at DESC
            LIMIT 10",
            $action_type,
            $context_json
        ), ARRAY_A);
        
        if (count($recent_outcomes) < 3) {
            return [
                'consistent' => true,
                'reason' => 'Insufficient data for consistency check',
            ];
        }
        
        $scores = array_column($recent_outcomes, 'outcome_score');
        
        // Check if outcomes are consistently positive or negative
        $positive_count = count(array_filter($scores, function($s) { return $s > 0; }));
        $negative_count = count(array_filter($scores, function($s) { return $s < 0; }));
        $total = count($scores);
        
        $consistency_rate = max($positive_count, $negative_count) / $total;
        
        if ($consistency_rate < self::MIN_CONSISTENCY) {
            return [
                'consistent' => false,
                'reason' => sprintf(
                    'Low consistency rate: %.1f%% (minimum: %.1f%%)',
                    $consistency_rate * 100,
                    self::MIN_CONSISTENCY * 100
                ),
                'consistency_rate' => $consistency_rate,
            ];
        }
        
        return [
            'consistent' => true,
            'reason' => sprintf('Consistency rate: %.1f%%', $consistency_rate * 100),
        ];
    }
    
    /**
     * Check variance
     */
    private function check_variance($action_type, $context, $new_outcome) {
        global $wpdb;
        
        $learning_table = $wpdb->prefix . 'dodo_learning_events';
        $context_json = json_encode($context);
        
        // Get recent outcomes
        $recent_outcomes = $wpdb->get_results($wpdb->prepare(
            "SELECT outcome_score FROM {$learning_table}
            WHERE action_type = %s
            AND context = %s
            ORDER BY recorded_at DESC
            LIMIT 10",
            $action_type,
            $context_json
        ), ARRAY_A);
        
        if (empty($recent_outcomes)) {
            return [
                'high_variance' => false,
                'reason' => 'No historical data for variance check',
            ];
        }
        
        $scores = array_column($recent_outcomes, 'outcome_score');
        $mean = array_sum($scores) / count($scores);
        
        // Calculate coefficient of variation
        $variance = 0;
        foreach ($scores as $score) {
            $variance += pow($score - $mean, 2);
        }
        $variance = $variance / count($scores);
        $std_dev = sqrt($variance);
        
        $cv = $mean != 0 ? ($std_dev / abs($mean)) * 100 : 0;
        
        if ($cv > self::MAX_VARIANCE) {
            return [
                'high_variance' => true,
                'reason' => sprintf(
                    'High variance detected: %.1f%% (max: %d%%)',
                    $cv,
                    self::MAX_VARIANCE
                ),
                'coefficient_of_variation' => $cv,
            ];
        }
        
        return [
            'high_variance' => false,
            'reason' => sprintf('Variance within acceptable range: %.1f%%', $cv),
        ];
    }
    
    /**
     * Record learning event
     */
    public function record_learning_event($action_type, $context, $outcome) {
        $context_key = $this->generate_context_key($context);
        $cooldown_key = "dodo_learning_cooldown_{$action_type}_{$context_key}";
        
        // Set cooldown
        update_option($cooldown_key, time());
        
        error_log(sprintf(
            '[DODO Validator] Learning event recorded - Cooldown set for %d seconds',
            self::COOLDOWN_AFTER_LEARNING
        ));
    }
    
    /**
     * Record rollback event
     */
    public function record_rollback_event($action_type, $context) {
        $context_key = $this->generate_context_key($context);
        $cooldown_key = "dodo_learning_cooldown_{$action_type}_{$context_key}";
        
        // Set longer cooldown after rollback
        update_option($cooldown_key, time());
        
        // Store rollback timestamp
        $rollback_key = "dodo_rollback_{$action_type}_{$context_key}";
        update_option($rollback_key, time());
        
        error_log(sprintf(
            '[DODO Validator] Rollback event recorded - Extended cooldown set for %d seconds',
            self::COOLDOWN_AFTER_ROLLBACK
        ));
    }
    
    /**
     * Validate evolution safety
     * 
     * @param array $base_params Base parameters
     * @param array $evolved_params Evolved parameters
     * @return array Validation result
     */
    public function validate_evolution($base_params, $evolved_params) {
        $validation = [
            'safe' => true,
            'warnings' => [],
            'changes' => [],
            'reasoning' => [],
        ];
        
        foreach ($evolved_params as $key => $evolved_value) {
            if (!isset($base_params[$key])) {
                continue;
            }
            
            $base_value = $base_params[$key];
            
            // Skip non-numeric values
            if (!is_numeric($base_value) || !is_numeric($evolved_value)) {
                continue;
            }
            
            // Calculate change percentage
            $change_pct = $base_value > 0 
                ? abs(($evolved_value - $base_value) / $base_value) * 100 
                : 0;
            
            if ($change_pct > 30) { // Max 30% change
                $validation['safe'] = false;
                $validation['warnings'][] = sprintf(
                    'Parameter %s changed by %.1f%% (max allowed: 30%%)',
                    $key,
                    $change_pct
                );
            }
            
            if ($change_pct > 5) {
                $validation['changes'][] = [
                    'parameter' => $key,
                    'from' => $base_value,
                    'to' => $evolved_value,
                    'change_pct' => round($change_pct, 1),
                ];
            }
        }
        
        if ($validation['safe']) {
            $validation['reasoning'][] = 'Evolution within safe parameters';
        } else {
            $validation['reasoning'][] = 'Evolution exceeds safety thresholds';
        }
        
        return $validation;
    }
    
    /**
     * Get validation insights
     * 
     * @return array Validation insights
     */
    public function get_validation_insights() {
        global $wpdb;
        
        $learning_table = $wpdb->prefix . 'dodo_learning_events';
        
        $insights = [
            'total_validations' => 0,
            'blocked_learnings' => 0,
            'noisy_data_filtered' => 0,
            'cooldown_blocks' => 0,
            'recent_blocks' => [],
        ];
        
        // Get validation statistics from learning events
        $total = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$learning_table}"
        );
        
        $insights['total_validations'] = (int) $total;
        
        // Get recent rollback events
        $rollback_events = $wpdb->get_results(
            "SELECT * FROM {$learning_table}
            WHERE event_type = 'evolution_rollback'
            ORDER BY recorded_at DESC
            LIMIT 10",
            ARRAY_A
        );
        
        $insights['recent_blocks'] = $rollback_events;
        $insights['blocked_learnings'] = count($rollback_events);
        
        return $insights;
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
     * Reset validation state
     * 
     * @param string $action_type Action type (optional)
     */
    public function reset_validation($action_type = null) {
        global $wpdb;
        
        if ($action_type) {
            // Reset specific action type
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$wpdb->options} 
                WHERE option_name LIKE %s",
                'dodo_learning_cooldown_' . $action_type . '_%'
            ));
            
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$wpdb->options} 
                WHERE option_name LIKE %s",
                'dodo_rollback_' . $action_type . '_%'
            ));
            
            error_log("[DODO Validator] Reset validation for: {$action_type}");
        } else {
            // Reset all validation state
            $wpdb->query(
                "DELETE FROM {$wpdb->options} 
                WHERE option_name LIKE 'dodo_learning_cooldown_%'"
            );
            
            $wpdb->query(
                "DELETE FROM {$wpdb->options} 
                WHERE option_name LIKE 'dodo_rollback_%'"
            );
            
            error_log('[DODO Validator] Reset all validation state');
        }
        
        return true;
    }
    
    /**
     * Validate strategy safety (Phase 5 - Generator Integration)
     * 
     * @param array $base_strategy Base strategy
     * @param array $learned_strategy Learned strategy
     * @return array Validation result
     */
    public function validate_strategy_safety($base_strategy, $learned_strategy) {
        error_log('[DODO Validator] Validating learned strategy safety');
        
        $validation = [
            'safe' => true,
            'warnings' => [],
            'changes' => [],
        ];
        
        // Check confidence
        if (isset($learned_strategy['confidence'])) {
            $confidence = $learned_strategy['confidence'];
            
            if ($confidence < self::MIN_CONFIDENCE) {
                $validation['safe'] = false;
                $validation['warnings'][] = sprintf(
                    'Learned strategy confidence (%d%%) below minimum (%d%%)',
                    $confidence,
                    self::MIN_CONFIDENCE
                );
                error_log("[DODO Validator] ❌ Low confidence: {$confidence}%");
                return $validation;
            }
            
            error_log("[DODO Validator] ✅ Confidence check passed: {$confidence}%");
        }
        
        // Check parameter changes
        $numeric_params = ['faq_count', 'target_word_count', 'h2_count', 'h3_count'];
        
        foreach ($numeric_params as $param) {
            if (!isset($learned_strategy[$param]) || !isset($base_strategy[$param])) {
                continue;
            }
            
            $base_value = $base_strategy[$param];
            $learned_value = $learned_strategy[$param];
            
            if ($base_value == 0) {
                continue;
            }
            
            $change_pct = abs(($learned_value - $base_value) / $base_value) * 100;
            
            if ($change_pct > 30) { // Max 30% deviation
                $validation['safe'] = false;
                $validation['warnings'][] = sprintf(
                    'Parameter %s changed by %.1f%% (max: 30%%) - %d → %d',
                    $param,
                    $change_pct,
                    $base_value,
                    $learned_value
                );
                error_log("[DODO Validator] ❌ Excessive change in {$param}: {$change_pct}%");
            } else if ($change_pct > 5) {
                $validation['changes'][] = [
                    'parameter' => $param,
                    'from' => $base_value,
                    'to' => $learned_value,
                    'change_pct' => round($change_pct, 1),
                ];
                error_log("[DODO Validator] ✅ Safe change in {$param}: {$change_pct}%");
            }
        }
        
        if ($validation['safe']) {
            error_log('[DODO Validator] ✅ Strategy safety validation PASSED');
        } else {
            error_log('[DODO Validator] ❌ Strategy safety validation FAILED');
        }
        
        return $validation;
    }
}
