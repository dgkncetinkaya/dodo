<?php
/**
 * Error Manager
 * 
 * Centralized error handling, categorization, and recovery suggestions
 * Enterprise-grade error management for DODO AI SEO
 *
 * @package DODO_AI_SEO
 * @since 2.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Error_Manager {
    
    /**
     * Error categories
     */
    const CATEGORY_API = 'api';
    const CATEGORY_QUEUE = 'queue';
    const CATEGORY_SECURITY = 'security';
    const CATEGORY_GENERATION = 'generation';
    const CATEGORY_BRAIN = 'brain';
    const CATEGORY_DATABASE = 'database';
    const CATEGORY_CACHE = 'cache';
    const CATEGORY_SYSTEM = 'system';
    
    /**
     * Error severity levels
     */
    const SEVERITY_DEBUG = 'debug';
    const SEVERITY_INFO = 'info';
    const SEVERITY_WARNING = 'warning';
    const SEVERITY_ERROR = 'error';
    const SEVERITY_CRITICAL = 'critical';
    
    /**
     * Create structured error
     *
     * @param string $code Error code
     * @param string $message Error message
     * @param string $category Error category
     * @param string $severity Error severity
     * @param array $context Additional context
     * @return WP_Error
     */
    public function create_error($code, $message, $category, $severity = self::SEVERITY_ERROR, $context = []) {
        $error_data = [
            'category' => $category,
            'severity' => $severity,
            'context' => $context,
            'timestamp' => current_time('mysql'),
            'recovery_suggestion' => $this->get_recovery_suggestion($code, $category),
        ];
        
        // Log error
        $this->log_error($code, $message, $category, $severity, $context);
        
        return new WP_Error($code, $message, $error_data);
    }
    
    /**
     * Log error with structured format
     *
     * @param string $code Error code
     * @param string $message Error message
     * @param string $category Error category
     * @param string $severity Error severity
     * @param array $context Additional context
     */
    public function log_error($code, $message, $category, $severity, $context = []) {
        $log_entry = [
            'timestamp' => current_time('mysql'),
            'code' => $code,
            'message' => $message,
            'category' => $category,
            'severity' => $severity,
            'context' => $context,
            'user_id' => get_current_user_id(),
            'request_uri' => isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '',
        ];
        
        // Format log message
        $log_message = sprintf(
            '[DODO Error] [%s] [%s] %s: %s',
            strtoupper($severity),
            strtoupper($category),
            $code,
            $message
        );
        
        if (!empty($context)) {
            $log_message .= ' | Context: ' . json_encode($context);
        }
        
        error_log($log_message);
        
        // Store in database for critical errors
        if (in_array($severity, [self::SEVERITY_ERROR, self::SEVERITY_CRITICAL])) {
            $this->store_error_log($log_entry);
        }
    }
    
    /**
     * Get recovery suggestion for error
     *
     * @param string $code Error code
     * @param string $category Error category
     * @return string Recovery suggestion
     */
    private function get_recovery_suggestion($code, $category) {
        $suggestions = [
            // API errors
            'openai_timeout' => 'Retry the request. If problem persists, check OpenAI status.',
            'openai_rate_limit' => 'Wait a few minutes before retrying.',
            'openai_quota_exceeded' => 'Check your OpenAI billing and increase quota.',
            'openai_invalid_key' => 'Verify your OpenAI API key in settings.',
            
            // Queue errors
            'queue_stuck' => 'Clear stuck jobs from queue dashboard.',
            'queue_full' => 'Wait for current jobs to complete or increase queue capacity.',
            'job_timeout' => 'Increase timeout limit or optimize generation parameters.',
            
            // Security errors
            'invalid_nonce' => 'Refresh the page and try again.',
            'insufficient_permissions' => 'Contact administrator for access.',
            'rate_limit_exceeded' => 'Wait before making more requests.',
            'prompt_injection_detected' => 'Remove suspicious characters from input.',
            
            // Generation errors
            'generation_failed' => 'Check partial save and retry with different parameters.',
            'outline_generation_failed' => 'Simplify topic or try different keyword.',
            'content_generation_failed' => 'Reduce content length or complexity.',
            
            // Brain errors
            'brain_analysis_failed' => 'Generation will continue without Brain enhancement.',
            'cannibalization_high_risk' => 'Consider updating existing content instead.',
            
            // Database errors
            'db_connection_failed' => 'Check database connection settings.',
            'db_query_failed' => 'Contact administrator if problem persists.',
            
            // Cache errors
            'cache_write_failed' => 'Check cache directory permissions.',
            'cache_corruption' => 'Clear cache and retry.',
        ];
        
        return $suggestions[$code] ?? 'Contact support if problem persists.';
    }
    
    /**
     * Get user-friendly error message
     *
     * @param WP_Error $error Error object
     * @return string User-friendly message
     */
    public function get_user_message($error) {
        if (!is_wp_error($error)) {
            return '';
        }
        
        $code = $error->get_error_code();
        $data = $error->get_error_data($code);
        
        // Check if we have a recovery suggestion
        if (isset($data['recovery_suggestion'])) {
            return $error->get_error_message() . ' ' . $data['recovery_suggestion'];
        }
        
        return $error->get_error_message();
    }
    
    /**
     * Get admin-safe error details
     *
     * @param WP_Error $error Error object
     * @return array Error details for admin
     */
    public function get_admin_details($error) {
        if (!is_wp_error($error)) {
            return [];
        }
        
        $code = $error->get_error_code();
        $data = $error->get_error_data($code);
        
        return [
            'code' => $code,
            'message' => $error->get_error_message(),
            'category' => $data['category'] ?? 'unknown',
            'severity' => $data['severity'] ?? self::SEVERITY_ERROR,
            'timestamp' => $data['timestamp'] ?? current_time('mysql'),
            'recovery_suggestion' => $data['recovery_suggestion'] ?? '',
            'context' => $data['context'] ?? [],
        ];
    }
    
    /**
     * Store error log in database
     *
     * @param array $log_entry Log entry data
     */
    private function store_error_log($log_entry) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dodo_error_logs';
        
        $wpdb->insert(
            $table_name,
            [
                'error_code' => $log_entry['code'],
                'error_message' => $log_entry['message'],
                'category' => $log_entry['category'],
                'severity' => $log_entry['severity'],
                'context' => json_encode($log_entry['context']),
                'user_id' => $log_entry['user_id'],
                'request_uri' => $log_entry['request_uri'],
                'created_at' => $log_entry['timestamp'],
            ],
            ['%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s']
        );
    }
    
    /**
     * Get recent errors
     *
     * @param int $limit Number of errors
     * @param string $category Filter by category
     * @return array Recent errors
     */
    public function get_recent_errors($limit = 50, $category = null) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dodo_error_logs';
        
        $where = '';
        if ($category) {
            $where = $wpdb->prepare(' WHERE category = %s', $category);
        }
        
        $errors = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name}{$where} 
            ORDER BY created_at DESC 
            LIMIT %d",
            $limit
        ), ARRAY_A);
        
        return $errors;
    }
    
    /**
     * Get error statistics
     *
     * @param int $days Days to look back
     * @return array Error statistics
     */
    public function get_error_stats($days = 7) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dodo_error_logs';
        
        $stats = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                category,
                severity,
                COUNT(*) as count
            FROM {$table_name}
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY category, severity
            ORDER BY count DESC",
            $days
        ), ARRAY_A);
        
        return $stats;
    }
    
    /**
     * Clean old error logs
     *
     * @param int $days Days to keep
     * @return int Deleted count
     */
    public function clean_old_logs($days = 30) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dodo_error_logs';
        
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$table_name} 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ));
        
        if ($deleted > 0) {
            error_log(sprintf(
                '[DODO Error Manager] Cleaned %d old error logs',
                $deleted
            ));
        }
        
        return $deleted;
    }
    
    /**
     * Create error logs table
     */
    public static function create_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dodo_error_logs';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            error_code varchar(100) NOT NULL,
            error_message text NOT NULL,
            category varchar(50) NOT NULL,
            severity varchar(20) NOT NULL,
            context text,
            user_id bigint(20) DEFAULT 0,
            request_uri varchar(255) DEFAULT '',
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY error_code (error_code),
            KEY category (category),
            KEY severity (severity),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        error_log('[DODO Error Manager] Error logs table created');
    }
}
