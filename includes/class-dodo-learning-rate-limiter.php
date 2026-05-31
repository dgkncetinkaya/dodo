<?php
/**
 * Learning Rate Limiter
 * 
 * Prevents runaway learning and excessive adaptations
 * 
 * @package DODO_AI_SEO
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Learning_Rate_Limiter {
    
    /**
     * Rate limits
     */
    const MAX_DAILY_ADAPTATIONS = 5;
    const MAX_HOURLY_ADAPTATIONS = 2;
    const COOLDOWN_AFTER_ADAPTATION = 3600; // 1 hour
    const COOLDOWN_AFTER_ROLLBACK = 7200; // 2 hours
    
    /**
     * Adaptation caps
     */
    const MAX_PARAMETER_CHANGE = 30; // 30% max change
    const MAX_CUMULATIVE_CHANGE = 50; // 50% max cumulative change
    
    /**
     * Check if adaptation is allowed
     */
    public function is_adaptation_allowed($action_type, $context = []) {
        // Check daily limit
        if (!$this->check_daily_limit()) {
            return [
                'allowed' => false,
                'reason' => 'Daily adaptation limit reached',
                'limit' => self::MAX_DAILY_ADAPTATIONS,
            ];
        }
        
        // Check hourly limit
        if (!$this->check_hourly_limit()) {
            return [
                'allowed' => false,
                'reason' => 'Hourly adaptation limit reached',
                'limit' => self::MAX_HOURLY_ADAPTATIONS,
            ];
        }
        
        // Check cooldown
        $cooldown_check = $this->check_cooldown($action_type, $context);
        if (!$cooldown_check['allowed']) {
            return $cooldown_check;
        }
        
        // Check minimum samples
        if (!$this->check_minimum_samples($action_type, $context)) {
            return [
                'allowed' => false,
                'reason' => 'Insufficient samples for adaptation',
            ];
        }
        
        return [
            'allowed' => true,
            'reason' => 'All rate limit checks passed',
        ];
    }
    
    /**
     * Check daily limit
     */
    private function check_daily_limit() {
        $today = date('Y-m-d');
        $count = (int) get_transient("dodo_adaptations_count_{$today}");
        
        return $count < self::MAX_DAILY_ADAPTATIONS;
    }
    
    /**
     * Check hourly limit
     */
    private function check_hourly_limit() {
        $current_hour = date('Y-m-d-H');
        $count = (int) get_transient("dodo_adaptations_hour_{$current_hour}");
        
        return $count < self::MAX_HOURLY_ADAPTATIONS;
    }
    
    /**
     * Check cooldown
     */
    private function check_cooldown($action_type, $context) {
        $context_key = md5(json_encode($context));
        $cooldown_key = "dodo_cooldown_{$action_type}_{$context_key}";
        
        $last_adaptation = get_transient($cooldown_key);
        
        if ($last_adaptation) {
            $remaining = self::COOLDOWN_AFTER_ADAPTATION - (time() - $last_adaptation);
            
            return [
                'allowed' => false,
                'reason' => 'Cooldown period active',
                'remaining_seconds' => max(0, $remaining),
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
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$learning_table}
            WHERE action_type = %s
            AND context = %s",
            $action_type,
            $context_json
        ));
        
        return $count >= 5; // Minimum 5 samples
    }
    
    /**
     * Record adaptation
     */
    public function record_adaptation($action_type, $context = []) {
        // Increment daily counter
        $today = date('Y-m-d');
        $daily_key = "dodo_adaptations_count_{$today}";
        $count = (int) get_transient($daily_key);
        set_transient($daily_key, $count + 1, DAY_IN_SECONDS);
        
        // Increment hourly counter
        $current_hour = date('Y-m-d-H');
        $hourly_key = "dodo_adaptations_hour_{$current_hour}";
        $hour_count = (int) get_transient($hourly_key);
        set_transient($hourly_key, $hour_count + 1, HOUR_IN_SECONDS);
        
        // Set cooldown
        $context_key = md5(json_encode($context));
        $cooldown_key = "dodo_cooldown_{$action_type}_{$context_key}";
        set_transient($cooldown_key, time(), self::COOLDOWN_AFTER_ADAPTATION);
        
        error_log(sprintf(
            '[DODO Rate Limiter] Adaptation recorded - Daily: %d/%d, Hourly: %d/%d',
            $count + 1,
            self::MAX_DAILY_ADAPTATIONS,
            $hour_count + 1,
            self::MAX_HOURLY_ADAPTATIONS
        ));
    }
    
    /**
     * Record rollback
     */
    public function record_rollback($action_type, $context = []) {
        // Set extended cooldown after rollback
        $context_key = md5(json_encode($context));
        $cooldown_key = "dodo_cooldown_{$action_type}_{$context_key}";
        set_transient($cooldown_key, time(), self::COOLDOWN_AFTER_ROLLBACK);
        
        error_log('[DODO Rate Limiter] Rollback recorded - extended cooldown set');
    }
    
    /**
     * Validate parameter change
     */
    public function validate_parameter_change($param_name, $old_value, $new_value) {
        if (!is_numeric($old_value) || !is_numeric($new_value) || $old_value == 0) {
            return [
                'valid' => true,
                'reason' => 'Non-numeric or zero baseline',
            ];
        }
        
        $change_pct = abs(($new_value - $old_value) / $old_value) * 100;
        
        if ($change_pct > self::MAX_PARAMETER_CHANGE) {
            return [
                'valid' => false,
                'reason' => sprintf(
                    'Change %.1f%% exceeds maximum %.1f%%',
                    $change_pct,
                    self::MAX_PARAMETER_CHANGE
                ),
                'change_pct' => $change_pct,
            ];
        }
        
        return [
            'valid' => true,
            'reason' => 'Change within limits',
            'change_pct' => $change_pct,
        ];
    }
    
    /**
     * Check cumulative change
     */
    public function check_cumulative_change($param_name, $baseline_value, $current_value) {
        if (!is_numeric($baseline_value) || !is_numeric($current_value) || $baseline_value == 0) {
            return [
                'valid' => true,
                'reason' => 'Non-numeric or zero baseline',
            ];
        }
        
        $cumulative_change = abs(($current_value - $baseline_value) / $baseline_value) * 100;
        
        if ($cumulative_change > self::MAX_CUMULATIVE_CHANGE) {
            return [
                'valid' => false,
                'reason' => sprintf(
                    'Cumulative change %.1f%% exceeds maximum %.1f%%',
                    $cumulative_change,
                    self::MAX_CUMULATIVE_CHANGE
                ),
                'cumulative_change' => $cumulative_change,
            ];
        }
        
        return [
            'valid' => true,
            'reason' => 'Cumulative change within limits',
            'cumulative_change' => $cumulative_change,
        ];
    }
    
    /**
     * Get rate limit status
     */
    public function get_status() {
        $today = date('Y-m-d');
        $current_hour = date('Y-m-d-H');
        
        $daily_count = (int) get_transient("dodo_adaptations_count_{$today}");
        $hourly_count = (int) get_transient("dodo_adaptations_hour_{$current_hour}");
        
        return [
            'daily_adaptations' => $daily_count,
            'daily_limit' => self::MAX_DAILY_ADAPTATIONS,
            'daily_remaining' => max(0, self::MAX_DAILY_ADAPTATIONS - $daily_count),
            'hourly_adaptations' => $hourly_count,
            'hourly_limit' => self::MAX_HOURLY_ADAPTATIONS,
            'hourly_remaining' => max(0, self::MAX_HOURLY_ADAPTATIONS - $hourly_count),
            'max_parameter_change' => self::MAX_PARAMETER_CHANGE,
            'max_cumulative_change' => self::MAX_CUMULATIVE_CHANGE,
        ];
    }
    
    /**
     * Reset rate limits (admin only)
     */
    public function reset_limits() {
        global $wpdb;
        
        // Delete all adaptation counters
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
            WHERE option_name LIKE '_transient_dodo_adaptations_%'"
        );
        
        // Delete all cooldowns
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
            WHERE option_name LIKE '_transient_dodo_cooldown_%'"
        );
        
        error_log('[DODO Rate Limiter] All rate limits reset');
        
        return true;
    }
}
