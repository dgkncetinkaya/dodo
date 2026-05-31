<?php
/**
 * Cost Control Manager
 * 
 * Token budget management, usage tracking, and cost optimization
 * Prevents runaway AI costs with smart limits and alerts
 *
 * @package DODO_AI_SEO
 * @since 2.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Cost_Manager {
    
    /**
     * Default monthly token budget
     */
    const DEFAULT_MONTHLY_BUDGET = 1000000; // 1M tokens
    
    /**
     * Cost per 1K tokens (GPT-4 average)
     */
    const COST_PER_1K_INPUT = 0.03;
    const COST_PER_1K_OUTPUT = 0.06;
    
    /**
     * Warning thresholds
     */
    const WARNING_THRESHOLD = 0.8; // 80%
    const CRITICAL_THRESHOLD = 0.95; // 95%
    
    /**
     * Get current month usage
     *
     * @return array Usage statistics
     */
    public function get_monthly_usage() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dodo_telemetry';
        
        $usage = $wpdb->get_row(
            "SELECT 
                COUNT(*) as request_count,
                SUM(value) as total_tokens,
                SUM(JSON_EXTRACT(metadata, '$.duration_ms')) as total_duration_ms
            FROM {$table_name}
            WHERE metric_type = 'ai_request'
            AND MONTH(recorded_at) = MONTH(CURRENT_DATE())
            AND YEAR(recorded_at) = YEAR(CURRENT_DATE())",
            ARRAY_A
        );
        
        $budget = $this->get_monthly_budget();
        $usage['budget'] = $budget;
        $usage['remaining'] = $budget - ($usage['total_tokens'] ?? 0);
        $usage['usage_percentage'] = $budget > 0 
            ? round((($usage['total_tokens'] ?? 0) / $budget) * 100, 2) 
            : 0;
        
        // Calculate estimated cost
        $usage['estimated_cost'] = $this->calculate_cost($usage['total_tokens'] ?? 0);
        
        // Determine status
        $usage['status'] = $this->get_usage_status($usage['usage_percentage']);
        
        return $usage;
    }
    
    /**
     * Check if within budget
     *
     * @param int $estimated_tokens Estimated tokens for operation
     * @return bool|WP_Error Within budget or error
     */
    public function check_budget($estimated_tokens = 0) {
        $usage = $this->get_monthly_usage();
        
        // Check if budget exceeded
        if ($usage['remaining'] <= 0) {
            error_log('[DODO Cost] Monthly budget exceeded');
            
            return new WP_Error(
                'budget_exceeded',
                'Monthly token budget exceeded. Please increase budget or wait until next month.',
                ['usage' => $usage]
            );
        }
        
        // Check if operation would exceed budget
        if ($estimated_tokens > 0 && $estimated_tokens > $usage['remaining']) {
            error_log(sprintf(
                '[DODO Cost] Operation would exceed budget. Estimated: %d, Remaining: %d',
                $estimated_tokens,
                $usage['remaining']
            ));
            
            return new WP_Error(
                'operation_exceeds_budget',
                'This operation would exceed monthly budget.',
                ['estimated' => $estimated_tokens, 'remaining' => $usage['remaining']]
            );
        }
        
        // Check warning threshold
        if ($usage['usage_percentage'] >= self::WARNING_THRESHOLD * 100) {
            error_log(sprintf(
                '[DODO Cost] WARNING: Budget usage at %s%%',
                $usage['usage_percentage']
            ));
            
            // Send notification if critical
            if ($usage['usage_percentage'] >= self::CRITICAL_THRESHOLD * 100) {
                $this->send_budget_alert('critical', $usage);
            }
        }
        
        return true;
    }
    
    /**
     * Estimate tokens for operation
     *
     * @param string $operation Operation type
     * @param array $params Operation parameters
     * @return int Estimated tokens
     */
    public function estimate_tokens($operation, $params = []) {
        $estimates = [
            'outline' => 1000,
            'introduction' => 500,
            'section' => 800,
            'conclusion' => 500,
            'faq' => 600,
            'brain_analysis' => 2000,
        ];
        
        $base_estimate = $estimates[$operation] ?? 1000;
        
        // Adjust based on length
        if (isset($params['length'])) {
            $multipliers = [
                'short' => 0.6,
                'medium' => 1.0,
                'long' => 1.5,
                'very_long' => 2.0,
            ];
            
            $base_estimate *= $multipliers[$params['length']] ?? 1.0;
        }
        
        return (int) $base_estimate;
    }
    
    /**
     * Calculate cost from tokens
     *
     * @param int $tokens Token count
     * @param string $type Token type (input/output)
     * @return float Cost in USD
     */
    public function calculate_cost($tokens, $type = 'mixed') {
        if ($type === 'input') {
            return ($tokens / 1000) * self::COST_PER_1K_INPUT;
        } elseif ($type === 'output') {
            return ($tokens / 1000) * self::COST_PER_1K_OUTPUT;
        } else {
            // Mixed - assume 50/50 split
            $input_cost = ($tokens * 0.5 / 1000) * self::COST_PER_1K_INPUT;
            $output_cost = ($tokens * 0.5 / 1000) * self::COST_PER_1K_OUTPUT;
            return $input_cost + $output_cost;
        }
    }
    
    /**
     * Get monthly budget
     *
     * @return int Monthly token budget
     */
    public function get_monthly_budget() {
        return get_option('dodo_monthly_token_budget', self::DEFAULT_MONTHLY_BUDGET);
    }
    
    /**
     * Set monthly budget
     *
     * @param int $budget Token budget
     * @return bool Success
     */
    public function set_monthly_budget($budget) {
        $budget = absint($budget);
        
        if ($budget < 10000) {
            return false; // Minimum 10K tokens
        }
        
        update_option('dodo_monthly_token_budget', $budget);
        
        error_log(sprintf('[DODO Cost] Monthly budget set to %d tokens', $budget));
        
        return true;
    }
    
    /**
     * Get usage status
     *
     * @param float $percentage Usage percentage
     * @return string Status (safe/warning/critical/exceeded)
     */
    private function get_usage_status($percentage) {
        if ($percentage >= 100) {
            return 'exceeded';
        } elseif ($percentage >= self::CRITICAL_THRESHOLD * 100) {
            return 'critical';
        } elseif ($percentage >= self::WARNING_THRESHOLD * 100) {
            return 'warning';
        } else {
            return 'safe';
        }
    }
    
    /**
     * Send budget alert
     *
     * @param string $level Alert level
     * @param array $usage Usage data
     */
    private function send_budget_alert($level, $usage) {
        $last_alert = get_transient('dodo_budget_alert_sent');
        
        // Don't spam alerts - once per day
        if ($last_alert) {
            return;
        }
        
        $admin_email = get_option('admin_email');
        $subject = sprintf('[DODO AI SEO] Budget Alert: %s', ucfirst($level));
        
        $message = sprintf(
            "DODO AI SEO Budget Alert\n\n" .
            "Level: %s\n" .
            "Usage: %s%%\n" .
            "Tokens Used: %s\n" .
            "Budget: %s\n" .
            "Remaining: %s\n" .
            "Estimated Cost: $%s\n\n" .
            "Please review your usage or increase the monthly budget.",
            strtoupper($level),
            $usage['usage_percentage'],
            number_format($usage['total_tokens']),
            number_format($usage['budget']),
            number_format($usage['remaining']),
            number_format($usage['estimated_cost'], 2)
        );
        
        wp_mail($admin_email, $subject, $message);
        
        set_transient('dodo_budget_alert_sent', true, DAY_IN_SECONDS);
        
        error_log(sprintf('[DODO Cost] Budget alert sent: %s', $level));
    }
    
    /**
     * Check for duplicate generation
     *
     * @param string $keyword Keyword
     * @param int $hours Hours to check
     * @return bool|int False or existing post ID
     */
    public function check_duplicate_generation($keyword, $hours = 24) {
        global $wpdb;
        
        // Check recent posts with same keyword
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE pm.meta_key = '_yoast_wpseo_focuskw'
            AND pm.meta_value = %s
            AND p.post_date >= DATE_SUB(NOW(), INTERVAL %d HOUR)
            LIMIT 1",
            $keyword,
            $hours
        ));
        
        if ($existing) {
            error_log(sprintf(
                '[DODO Cost] Duplicate generation detected for keyword: %s (Post ID: %d)',
                $keyword,
                $existing
            ));
            
            return (int) $existing;
        }
        
        return false;
    }
    
    /**
     * Optimize prompt for cost
     *
     * @param string $prompt Original prompt
     * @param int $max_tokens Maximum tokens
     * @return string Optimized prompt
     */
    public function optimize_prompt($prompt, $max_tokens = 4000) {
        $estimated_tokens = $this->estimate_prompt_tokens($prompt);
        
        if ($estimated_tokens <= $max_tokens) {
            return $prompt;
        }
        
        // Truncate prompt intelligently
        $ratio = $max_tokens / $estimated_tokens;
        $target_length = strlen($prompt) * $ratio * 0.9; // 90% to be safe
        
        $optimized = substr($prompt, 0, (int) $target_length);
        
        error_log(sprintf(
            '[DODO Cost] Prompt optimized: %d -> %d estimated tokens',
            $estimated_tokens,
            $this->estimate_prompt_tokens($optimized)
        ));
        
        return $optimized;
    }
    
    /**
     * Estimate tokens in prompt (rough)
     *
     * @param string $text Text to estimate
     * @return int Estimated tokens
     */
    private function estimate_prompt_tokens($text) {
        // Rough estimate: 1 token ≈ 4 characters
        return (int) (strlen($text) / 4);
    }
    
    /**
     * Get cost report
     *
     * @param int $months Months to include
     * @return array Cost report
     */
    public function get_cost_report($months = 3) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dodo_telemetry';
        
        $report = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                DATE_FORMAT(recorded_at, '%%Y-%%m') as month,
                COUNT(*) as request_count,
                SUM(value) as total_tokens
            FROM {$table_name}
            WHERE metric_type = 'ai_request'
            AND recorded_at >= DATE_SUB(NOW(), INTERVAL %d MONTH)
            GROUP BY month
            ORDER BY month DESC",
            $months
        ), ARRAY_A);
        
        // Add cost calculations
        foreach ($report as &$row) {
            $row['estimated_cost'] = $this->calculate_cost($row['total_tokens']);
        }
        
        return $report;
    }
    
    /**
     * Get most expensive operations
     *
     * @param int $limit Number of operations
     * @return array Expensive operations
     */
    public function get_expensive_operations($limit = 10) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dodo_telemetry';
        
        $operations = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                metric_name as operation,
                COUNT(*) as count,
                SUM(value) as total_tokens,
                AVG(value) as avg_tokens
            FROM {$table_name}
            WHERE metric_type = 'ai_request'
            AND recorded_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY metric_name
            ORDER BY total_tokens DESC
            LIMIT %d",
            $limit
        ), ARRAY_A);
        
        // Add cost
        foreach ($operations as &$op) {
            $op['estimated_cost'] = $this->calculate_cost($op['total_tokens']);
        }
        
        return $operations;
    }
    
    /**
     * Enable emergency stop
     *
     * @param string $reason Reason for stop
     */
    public function enable_emergency_stop($reason = '') {
        update_option('dodo_emergency_stop', true);
        update_option('dodo_emergency_stop_reason', $reason);
        update_option('dodo_emergency_stop_time', current_time('mysql'));
        
        error_log(sprintf('[DODO Cost] EMERGENCY STOP ENABLED: %s', $reason));
        
        // Send alert
        $admin_email = get_option('admin_email');
        wp_mail(
            $admin_email,
            '[DODO AI SEO] Emergency Stop Activated',
            "Emergency stop has been activated.\n\nReason: {$reason}\n\nAll AI operations are paused."
        );
    }
    
    /**
     * Disable emergency stop
     */
    public function disable_emergency_stop() {
        delete_option('dodo_emergency_stop');
        delete_option('dodo_emergency_stop_reason');
        delete_option('dodo_emergency_stop_time');
        
        error_log('[DODO Cost] Emergency stop disabled');
    }
    
    /**
     * Check if emergency stop is active
     *
     * @return bool|WP_Error Active or error
     */
    public function check_emergency_stop() {
        if (get_option('dodo_emergency_stop')) {
            $reason = get_option('dodo_emergency_stop_reason', 'Unknown');
            
            return new WP_Error(
                'emergency_stop_active',
                'AI operations are paused due to emergency stop.',
                ['reason' => $reason]
            );
        }
        
        return false;
    }
}
