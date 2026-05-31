<?php
/**
 * Telemetry System
 * 
 * Observability and metrics collection for DODO AI SEO
 * Tracks AI usage, queue performance, Brain metrics, system health
 *
 * @package DODO_AI_SEO
 * @since 2.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Telemetry {
    
    /**
     * Metrics table
     */
    private $metrics_table;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->metrics_table = $wpdb->prefix . 'dodo_telemetry';
    }
    
    /**
     * Track AI request
     *
     * @param array $data Request data
     */
    public function track_ai_request($data) {
        $metrics = [
            'metric_type' => 'ai_request',
            'metric_name' => $data['operation'] ?? 'unknown',
            'value' => $data['tokens'] ?? 0,
            'metadata' => json_encode([
                'model' => $data['model'] ?? 'gpt-4',
                'duration_ms' => $data['duration_ms'] ?? 0,
                'success' => $data['success'] ?? true,
                'cache_hit' => $data['cache_hit'] ?? false,
            ]),
            'recorded_at' => current_time('mysql'),
        ];
        
        $this->store_metric($metrics);
    }
    
    /**
     * Track queue operation
     *
     * @param array $data Queue data
     */
    public function track_queue_operation($data) {
        $metrics = [
            'metric_type' => 'queue',
            'metric_name' => $data['operation'] ?? 'unknown',
            'value' => $data['duration_ms'] ?? 0,
            'metadata' => json_encode([
                'job_id' => $data['job_id'] ?? 0,
                'status' => $data['status'] ?? 'unknown',
                'retry_count' => $data['retry_count'] ?? 0,
            ]),
            'recorded_at' => current_time('mysql'),
        ];
        
        $this->store_metric($metrics);
    }
    
    /**
     * Track Brain Core operation
     *
     * @param array $data Brain data
     */
    public function track_brain_operation($data) {
        $metrics = [
            'metric_type' => 'brain',
            'metric_name' => $data['engine'] ?? 'unknown',
            'value' => $data['execution_time_ms'] ?? 0,
            'metadata' => json_encode([
                'keyword' => $data['keyword'] ?? '',
                'score' => $data['score'] ?? 0,
                'cache_hit' => $data['cache_hit'] ?? false,
            ]),
            'recorded_at' => current_time('mysql'),
        ];
        
        $this->store_metric($metrics);
    }
    
    /**
     * Track system metric
     *
     * @param string $metric_name Metric name
     * @param mixed $value Metric value
     * @param array $metadata Additional metadata
     */
    public function track_system_metric($metric_name, $value, $metadata = []) {
        $metrics = [
            'metric_type' => 'system',
            'metric_name' => $metric_name,
            'value' => is_numeric($value) ? $value : 0,
            'metadata' => json_encode($metadata),
            'recorded_at' => current_time('mysql'),
        ];
        
        $this->store_metric($metrics);
    }
    
    /**
     * Store metric in database
     *
     * @param array $metrics Metric data
     */
    private function store_metric($metrics) {
        global $wpdb;
        
        $wpdb->insert(
            $this->metrics_table,
            $metrics,
            ['%s', '%s', '%f', '%s', '%s']
        );
    }
    
    /**
     * Get AI usage statistics
     *
     * @param int $days Days to look back
     * @return array Statistics
     */
    public function get_ai_usage_stats($days = 30) {
        global $wpdb;
        
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as request_count,
                SUM(value) as total_tokens,
                AVG(value) as avg_tokens,
                SUM(JSON_EXTRACT(metadata, '$.duration_ms')) as total_duration_ms
            FROM {$this->metrics_table}
            WHERE metric_type = 'ai_request'
            AND recorded_at >= DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ), ARRAY_A);
        
        // Calculate cost estimate (rough)
        $cost_per_1k_tokens = 0.03; // GPT-4 average
        $stats['estimated_cost'] = ($stats['total_tokens'] / 1000) * $cost_per_1k_tokens;
        
        // Cache hit ratio
        $cache_hits = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) 
            FROM {$this->metrics_table}
            WHERE metric_type = 'ai_request'
            AND JSON_EXTRACT(metadata, '$.cache_hit') = true
            AND recorded_at >= DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ));
        
        $stats['cache_hit_ratio'] = $stats['request_count'] > 0 
            ? round(($cache_hits / $stats['request_count']) * 100, 2) 
            : 0;
        
        return $stats;
    }
    
    /**
     * Get queue statistics
     *
     * @param int $days Days to look back
     * @return array Statistics
     */
    public function get_queue_stats($days = 7) {
        global $wpdb;
        
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total_jobs,
                AVG(value) as avg_duration_ms,
                MAX(value) as max_duration_ms
            FROM {$this->metrics_table}
            WHERE metric_type = 'queue'
            AND recorded_at >= DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ), ARRAY_A);
        
        // Failed jobs
        $stats['failed_jobs'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) 
            FROM {$this->metrics_table}
            WHERE metric_type = 'queue'
            AND JSON_EXTRACT(metadata, '$.status') = 'failed'
            AND recorded_at >= DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ));
        
        // Retry rate
        $stats['retry_rate'] = $stats['total_jobs'] > 0 
            ? round(($stats['failed_jobs'] / $stats['total_jobs']) * 100, 2) 
            : 0;
        
        return $stats;
    }
    
    /**
     * Get Brain Core statistics
     *
     * @param int $days Days to look back
     * @return array Statistics
     */
    public function get_brain_stats($days = 30) {
        global $wpdb;
        
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as analysis_count,
                AVG(value) as avg_execution_ms,
                MAX(value) as max_execution_ms
            FROM {$this->metrics_table}
            WHERE metric_type = 'brain'
            AND recorded_at >= DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ), ARRAY_A);
        
        // Cache hit ratio
        $cache_hits = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) 
            FROM {$this->metrics_table}
            WHERE metric_type = 'brain'
            AND JSON_EXTRACT(metadata, '$.cache_hit') = true
            AND recorded_at >= DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ));
        
        $stats['cache_hit_ratio'] = $stats['analysis_count'] > 0 
            ? round(($cache_hits / $stats['analysis_count']) * 100, 2) 
            : 0;
        
        // Average score
        $stats['avg_score'] = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(JSON_EXTRACT(metadata, '$.score'))
            FROM {$this->metrics_table}
            WHERE metric_type = 'brain'
            AND recorded_at >= DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ));
        
        return $stats;
    }
    
    /**
     * Get system health metrics
     *
     * @return array Health metrics
     */
    public function get_system_health() {
        $health = [
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'memory_limit' => ini_get('memory_limit'),
        ];
        
        // Database health
        global $wpdb;
        $health['db_queries'] = $wpdb->num_queries;
        
        // Queue depth
        $health['queue_depth'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}dodo_queue_jobs 
            WHERE status IN ('queued', 'processing')"
        );
        
        // Cron health
        $health['cron_last_run'] = get_option('dodo_cron_last_run', 0);
        $health['cron_healthy'] = (time() - $health['cron_last_run']) < 3600; // Within 1 hour
        
        return $health;
    }
    
    /**
     * Get metrics for time period
     *
     * @param string $metric_type Metric type
     * @param int $hours Hours to look back
     * @return array Time series data
     */
    public function get_time_series($metric_type, $hours = 24) {
        global $wpdb;
        
        $data = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                DATE_FORMAT(recorded_at, '%%Y-%%m-%%d %%H:00:00') as hour,
                COUNT(*) as count,
                AVG(value) as avg_value,
                SUM(value) as sum_value
            FROM {$this->metrics_table}
            WHERE metric_type = %s
            AND recorded_at >= DATE_SUB(NOW(), INTERVAL %d HOUR)
            GROUP BY hour
            ORDER BY hour ASC",
            $metric_type,
            $hours
        ), ARRAY_A);
        
        return $data;
    }
    
    /**
     * Clean old metrics
     *
     * @param int $days Days to keep
     * @return int Deleted count
     */
    public function clean_old_metrics($days = 90) {
        global $wpdb;
        
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$this->metrics_table} 
            WHERE recorded_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ));
        
        if ($deleted > 0) {
            error_log(sprintf(
                '[DODO Telemetry] Cleaned %d old metrics',
                $deleted
            ));
        }
        
        return $deleted;
    }
    
    /**
     * Create telemetry table
     */
    public static function create_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dodo_telemetry';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            metric_type varchar(50) NOT NULL,
            metric_name varchar(100) NOT NULL,
            value decimal(15,2) NOT NULL DEFAULT 0,
            metadata text,
            recorded_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY metric_type (metric_type),
            KEY metric_name (metric_name),
            KEY recorded_at (recorded_at),
            KEY type_name (metric_type, metric_name)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        error_log('[DODO Telemetry] Telemetry table created');
    }
}
