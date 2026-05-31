<?php
/**
 * Telemetry Service - Production monitoring and metrics
 * 
 * PHASE 7 - Production Telemetry
 * Real-time monitoring of production system health
 * 
 * @package DODO_AI_SEO
 * @since 4.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (class_exists('DODO_Telemetry')) {
    return;
}

class DODO_Telemetry {
    
    /**
     * Initialized flag
     */
    private static $initialized = false;
    
    /**
     * Initialize telemetry
     */
    public static function init() {
        if (self::$initialized) {
            return;
        }
        
        self::$initialized = true;
        
        // Telemetry is ready
        // All methods are static and called directly
    }
    
    /**
     * Track metric
     * 
     * @param string $metric_name Metric name
     * @param mixed $value Metric value
     * @param array $tags Optional tags
     */
    public static function track($metric_name, $value, $tags = array()) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'dodo_telemetry';
        
        // Check if table exists - fail silently if not
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table;
        
        if (!$table_exists) {
            // Log but don't break execution
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("[DODO Telemetry] Table {$table} does not exist - skipping tracking");
            }
            return false;
        }
        
        // Check if required columns exist
        $columns = $wpdb->get_col("DESCRIBE {$table}", 0);
        $required_columns = array('metric_name', 'metric_value', 'tags', 'created_at');
        $missing_columns = array_diff($required_columns, $columns);
        
        if (!empty($missing_columns)) {
            // Log but don't break execution
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("[DODO Telemetry] Missing columns in {$table}: " . implode(', ', $missing_columns));
            }
            return false;
        }
        
        // Safe insert with error suppression
        $data = array(
            'metric_name' => $metric_name,
            'metric_value' => is_numeric($value) ? $value : json_encode($value),
            'tags' => json_encode($tags),
            'created_at' => current_time('mysql'),
        );
        
        // Suppress errors to prevent breaking blog generation
        $wpdb->suppress_errors(true);
        $result = $wpdb->insert($table, $data);
        $wpdb->suppress_errors(false);
        
        if ($result === false && defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[DODO Telemetry] Insert failed: " . $wpdb->last_error);
        }
        
        return $result !== false;
    }
    
    /**
     * Track AI request (backward compatible)
     * 
     * @param array $data Request data
     */
    public static function track_ai_request($data) {
        // Extract data with fallbacks
        $operation = $data['operation'] ?? 'unknown';
        $model = $data['model'] ?? 'gpt-4o';
        $tokens = $data['tokens'] ?? 0;
        $duration_ms = $data['duration_ms'] ?? 0;
        $success = $data['success'] ?? true;
        $cache_hit = $data['cache_hit'] ?? false;
        
        // Track as multiple metrics for better analytics
        self::track('ai_request', 1, array(
            'operation' => $operation,
            'model' => $model,
            'success' => $success ? 'yes' : 'no',
            'cache_hit' => $cache_hit ? 'yes' : 'no',
        ));
        
        // Track tokens
        if ($tokens > 0) {
            self::track('ai_tokens', $tokens, array(
                'operation' => $operation,
                'model' => $model,
            ));
        }
        
        // Track latency
        if ($duration_ms > 0) {
            self::track_api_latency('openai', $duration_ms);
        }
        
        // Estimate and track cost
        if ($tokens > 0) {
            $cost = self::estimate_cost($tokens, $model);
            self::track_ai_cost($cost, $tokens, $model);
        }
    }
    
    /**
     * Estimate AI cost based on tokens and model
     * 
     * @param int $tokens Token count
     * @param string $model Model name
     * @return float Cost in USD
     */
    private static function estimate_cost($tokens, $model) {
        // Pricing per 1M tokens (as of 2024)
        $pricing = array(
            'gpt-4o' => 0.005,           // $5 per 1M tokens (average input/output)
            'gpt-4o-mini' => 0.0003,     // $0.30 per 1M tokens
            'gpt-4-turbo' => 0.01,       // $10 per 1M tokens
            'gpt-3.5-turbo' => 0.0015,   // $1.50 per 1M tokens
        );
        
        $rate = $pricing[$model] ?? $pricing['gpt-4o'];
        
        return ($tokens / 1000000) * $rate;
    }
    
    /**
     * Track AI cost
     * 
     * @param float $cost Cost in USD
     * @param int $tokens Token count
     * @param string $model Model name
     */
    public static function track_ai_cost($cost, $tokens, $model = 'gpt-4o') {
        self::track('ai_cost', $cost, array(
            'tokens' => $tokens,
            'model' => $model,
        ));
    }
    
    /**
     * Track API latency
     * 
     * @param string $api API name (openai, gsc, etc)
     * @param float $latency_ms Latency in milliseconds
     */
    public static function track_api_latency($api, $latency_ms) {
        self::track('api_latency', $latency_ms, array(
            'api' => $api,
        ));
    }
    
    /**
     * Track queue size
     * 
     * @param int $size Queue size
     * @param string $status Queue status
     */
    public static function track_queue_size($size, $status = 'pending') {
        self::track('queue_size', $size, array(
            'status' => $status,
        ));
    }
    
    /**
     * Track failed job
     * 
     * @param string $job_type Job type
     * @param string $error Error message
     */
    public static function track_failed_job($job_type, $error) {
        self::track('failed_job', 1, array(
            'job_type' => $job_type,
            'error' => substr($error, 0, 255),
        ));
    }
    
    /**
     * Track cron failure
     * 
     * @param string $cron_name Cron name
     * @param string $error Error message
     */
    public static function track_cron_failure($cron_name, $error) {
        self::track('cron_failure', 1, array(
            'cron_name' => $cron_name,
            'error' => substr($error, 0, 255),
        ));
    }
    
    /**
     * Track cache efficiency
     * 
     * @param int $hits Cache hits
     * @param int $misses Cache misses
     */
    public static function track_cache_efficiency($hits, $misses) {
        $total = $hits + $misses;
        $hit_rate = $total > 0 ? ($hits / $total) * 100 : 0;
        
        self::track('cache_hit_rate', $hit_rate, array(
            'hits' => $hits,
            'misses' => $misses,
        ));
    }
    
    /**
     * Track database growth
     * 
     * @param string $table_name Table name
     * @param int $size_mb Size in MB
     */
    public static function track_db_growth($table_name, $size_mb) {
        self::track('db_size', $size_mb, array(
            'table' => $table_name,
        ));
    }
    
    /**
     * Track memory peak
     * 
     * @param float $memory_mb Memory in MB
     */
    public static function track_memory_peak($memory_mb) {
        self::track('memory_peak', $memory_mb);
    }
    
    /**
     * Get real-time metrics
     * 
     * @return array Current metrics
     */
    public static function get_realtime_metrics() {
        return array(
            'ai_cost_today' => self::get_ai_cost_today(),
            'api_latency_avg' => self::get_api_latency_avg(),
            'queue_size' => self::get_queue_size(),
            'failed_jobs_today' => self::get_failed_jobs_today(),
            'cron_failures_today' => self::get_cron_failures_today(),
            'cache_hit_rate' => self::get_cache_hit_rate(),
            'db_size_total' => self::get_db_size_total(),
            'memory_peak' => self::get_memory_peak(),
        );
    }
    
    /**
     * Get AI cost today
     */
    private static function get_ai_cost_today() {
        global $wpdb;
        $table = $wpdb->prefix . 'dodo_telemetry';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) {
            return 0;
        }
        
        $result = $wpdb->get_var(
            "SELECT SUM(metric_value) 
            FROM {$table} 
            WHERE metric_name = 'ai_cost' 
            AND DATE(created_at) = CURDATE()"
        );
        
        return $result ? floatval($result) : 0;
    }
    
    /**
     * Get average API latency
     */
    private static function get_api_latency_avg() {
        global $wpdb;
        $table = $wpdb->prefix . 'dodo_telemetry';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) {
            return 0;
        }
        
        $result = $wpdb->get_var(
            "SELECT AVG(metric_value) 
            FROM {$table} 
            WHERE metric_name = 'api_latency' 
            AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)"
        );
        
        return $result ? floatval($result) : 0;
    }
    
    /**
     * Get current queue size
     */
    private static function get_queue_size() {
        global $wpdb;
        $table = $wpdb->prefix . 'dodo_queue_jobs';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) {
            return 0;
        }
        
        $result = $wpdb->get_var(
            "SELECT COUNT(*) 
            FROM {$table} 
            WHERE status = 'pending'"
        );
        
        return $result ? intval($result) : 0;
    }
    
    /**
     * Get failed jobs today
     */
    private static function get_failed_jobs_today() {
        global $wpdb;
        $table = $wpdb->prefix . 'dodo_telemetry';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) {
            return 0;
        }
        
        $result = $wpdb->get_var(
            "SELECT COUNT(*) 
            FROM {$table} 
            WHERE metric_name = 'failed_job' 
            AND DATE(created_at) = CURDATE()"
        );
        
        return $result ? intval($result) : 0;
    }
    
    /**
     * Get cron failures today
     */
    private static function get_cron_failures_today() {
        global $wpdb;
        $table = $wpdb->prefix . 'dodo_telemetry';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) {
            return 0;
        }
        
        $result = $wpdb->get_var(
            "SELECT COUNT(*) 
            FROM {$table} 
            WHERE metric_name = 'cron_failure' 
            AND DATE(created_at) = CURDATE()"
        );
        
        return $result ? intval($result) : 0;
    }
    
    /**
     * Get cache hit rate
     */
    private static function get_cache_hit_rate() {
        if (class_exists('DODO_Cache_Manager')) {
            $stats = DODO_Cache_Manager::get_stats();
            return $stats['hit_rate'] ?? 0;
        }
        
        return 0;
    }
    
    /**
     * Get total database size
     */
    private static function get_db_size_total() {
        global $wpdb;
        
        $tables = array(
            'dodo_queue_jobs',
            'dodo_telemetry',
            'dodo_learning_events',
            'dodo_keyword_opportunities',
            'dodo_ai_usage_logs',
        );
        
        $total_size = 0;
        
        foreach ($tables as $table) {
            $full_table = $wpdb->prefix . $table;
            
            if ($wpdb->get_var("SHOW TABLES LIKE '{$full_table}'") === $full_table) {
                $result = $wpdb->get_row(
                    "SELECT 
                        ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
                    FROM information_schema.TABLES 
                    WHERE table_schema = DATABASE() 
                    AND table_name = '{$full_table}'"
                );
                
                if ($result) {
                    $total_size += floatval($result->size_mb);
                }
            }
        }
        
        return $total_size;
    }
    
    /**
     * Get memory peak
     */
    private static function get_memory_peak() {
        return round(memory_get_peak_usage() / 1024 / 1024, 2);
    }
    
    /**
     * Get metric history
     * 
     * @param string $metric_name Metric name
     * @param int $hours Hours to look back
     * @return array Metric history
     */
    public static function get_metric_history($metric_name, $hours = 24) {
        global $wpdb;
        $table = $wpdb->prefix . 'dodo_telemetry';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) {
            return array();
        }
        
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT 
                    metric_value,
                    tags,
                    created_at
                FROM {$table} 
                WHERE metric_name = %s 
                AND created_at >= DATE_SUB(NOW(), INTERVAL %d HOUR)
                ORDER BY created_at ASC",
                $metric_name,
                $hours
            ),
            ARRAY_A
        );
        
        return $results ?: array();
    }
    
    /**
     * Get alerts
     * 
     * @return array Active alerts
     */
    public static function get_alerts() {
        $alerts = array();
        $metrics = self::get_realtime_metrics();
        
        // AI cost alert
        if ($metrics['ai_cost_today'] > 10) {
            $alerts[] = array(
                'level' => 'warning',
                'message' => sprintf('AI cost today: $%.2f (threshold: $10)', $metrics['ai_cost_today']),
                'metric' => 'ai_cost',
            );
        }
        
        // API latency alert
        if ($metrics['api_latency_avg'] > 5000) {
            $alerts[] = array(
                'level' => 'warning',
                'message' => sprintf('High API latency: %.0fms (threshold: 5000ms)', $metrics['api_latency_avg']),
                'metric' => 'api_latency',
            );
        }
        
        // Queue size alert
        if ($metrics['queue_size'] > 100) {
            $alerts[] = array(
                'level' => 'warning',
                'message' => sprintf('Large queue size: %d jobs (threshold: 100)', $metrics['queue_size']),
                'metric' => 'queue_size',
            );
        }
        
        // Failed jobs alert
        if ($metrics['failed_jobs_today'] > 10) {
            $alerts[] = array(
                'level' => 'error',
                'message' => sprintf('High failure rate: %d failed jobs today (threshold: 10)', $metrics['failed_jobs_today']),
                'metric' => 'failed_jobs',
            );
        }
        
        // Cron failures alert
        if ($metrics['cron_failures_today'] > 5) {
            $alerts[] = array(
                'level' => 'error',
                'message' => sprintf('Cron failures: %d today (threshold: 5)', $metrics['cron_failures_today']),
                'metric' => 'cron_failures',
            );
        }
        
        // Cache efficiency alert
        if ($metrics['cache_hit_rate'] < 50) {
            $alerts[] = array(
                'level' => 'warning',
                'message' => sprintf('Low cache hit rate: %.1f%% (threshold: 50%%)', $metrics['cache_hit_rate']),
                'metric' => 'cache_hit_rate',
            );
        }
        
        // Database size alert
        if ($metrics['db_size_total'] > 500) {
            $alerts[] = array(
                'level' => 'warning',
                'message' => sprintf('Large database: %.2f MB (threshold: 500 MB)', $metrics['db_size_total']),
                'metric' => 'db_size',
            );
        }
        
        // Memory peak alert
        $memory_limit = ini_get('memory_limit');
        $memory_limit_mb = intval($memory_limit);
        if ($metrics['memory_peak'] > ($memory_limit_mb * 0.8)) {
            $alerts[] = array(
                'level' => 'error',
                'message' => sprintf('High memory usage: %.2f MB / %s (80%% threshold)', $metrics['memory_peak'], $memory_limit),
                'metric' => 'memory_peak',
            );
        }
        
        return $alerts;
    }
    
    /**
     * Get dashboard data
     * 
     * @return array Dashboard data
     */
    public static function get_dashboard_data() {
        return array(
            'metrics' => self::get_realtime_metrics(),
            'alerts' => self::get_alerts(),
            'timestamp' => current_time('mysql'),
        );
    }
}
