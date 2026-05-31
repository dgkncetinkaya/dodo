<?php
/**
 * System Health Engine
 * 
 * Comprehensive health monitoring and scoring for DODO AI SEO
 * Checks queue, cron, API, cache, database, security status
 *
 * @package DODO_AI_SEO
 * @since 2.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_System_Health {
    
    /**
     * Health score thresholds
     */
    const SCORE_EXCELLENT = 90;
    const SCORE_GOOD = 70;
    const SCORE_FAIR = 50;
    const SCORE_POOR = 30;
    
    /**
     * Component weights
     */
    private $weights = [
        'queue' => 0.25,
        'cron' => 0.15,
        'api' => 0.20,
        'cache' => 0.10,
        'database' => 0.15,
        'security' => 0.15,
    ];
    
    /**
     * Get overall system health
     *
     * @return array Health report
     */
    public function get_health_report() {
        $start_time = microtime(true);
        
        $components = [
            'queue' => $this->check_queue_health(),
            'cron' => $this->check_cron_health(),
            'api' => $this->check_api_health(),
            'cache' => $this->check_cache_health(),
            'database' => $this->check_database_health(),
            'security' => $this->check_security_health(),
        ];
        
        // Calculate overall score
        $overall_score = 0;
        foreach ($components as $component => $data) {
            $overall_score += $data['score'] * $this->weights[$component];
        }
        $overall_score = round($overall_score);
        
        // Determine status
        $status = $this->get_health_status($overall_score);
        
        // Collect issues
        $issues = [];
        foreach ($components as $component => $data) {
            if (!empty($data['issues'])) {
                $issues = array_merge($issues, $data['issues']);
            }
        }
        
        $execution_time = round((microtime(true) - $start_time) * 1000, 2);
        
        return [
            'overall_score' => $overall_score,
            'status' => $status,
            'components' => $components,
            'issues' => $issues,
            'recommendations' => $this->get_recommendations($components),
            'checked_at' => current_time('mysql'),
            'execution_time_ms' => $execution_time,
        ];
    }
    
    /**
     * Check queue health
     *
     * @return array Queue health data
     */
    private function check_queue_health() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dodo_queue_jobs';
        $score = 100;
        $issues = [];
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") !== $table_name) {
            return [
                'score' => 0,
                'status' => 'critical',
                'issues' => ['Queue table does not exist'],
                'metrics' => [],
            ];
        }
        
        // Get queue metrics
        $metrics = [
            'queued' => $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE status = 'queued'"),
            'processing' => $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE status = 'processing'"),
            'failed' => $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE status = 'failed'"),
            'stuck' => $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE status = 'processing' AND updated_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)"),
        ];
        
        // Check for issues
        if ($metrics['stuck'] > 0) {
            $score -= 30;
            $issues[] = sprintf('%d stuck jobs detected', $metrics['stuck']);
        }
        
        if ($metrics['failed'] > 10) {
            $score -= 20;
            $issues[] = sprintf('%d failed jobs in queue', $metrics['failed']);
        }
        
        if ($metrics['queued'] > 100) {
            $score -= 15;
            $issues[] = sprintf('Queue backlog: %d jobs', $metrics['queued']);
        }
        
        return [
            'score' => max(0, $score),
            'status' => $this->get_component_status($score),
            'issues' => $issues,
            'metrics' => $metrics,
        ];
    }
    
    /**
     * Check cron health
     *
     * @return array Cron health data
     */
    private function check_cron_health() {
        $score = 100;
        $issues = [];
        $metrics = [];
        
        // Check if WP-Cron is disabled
        if (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON) {
            $metrics['wp_cron_disabled'] = true;
            // Not necessarily bad if system cron is configured
        } else {
            $metrics['wp_cron_disabled'] = false;
        }
        
        // Check last cron run
        $last_run = get_option('dodo_cron_last_run', 0);
        $time_since_run = time() - $last_run;
        $metrics['last_run'] = $last_run;
        $metrics['time_since_run'] = $time_since_run;
        
        if ($time_since_run > 3600) { // 1 hour
            $score -= 40;
            $issues[] = sprintf('Cron not run for %d minutes', round($time_since_run / 60));
        } elseif ($time_since_run > 1800) { // 30 minutes
            $score -= 20;
            $issues[] = 'Cron delayed';
        }
        
        // Check scheduled events
        $scheduled = wp_get_schedules();
        $metrics['scheduled_events'] = count($scheduled);
        
        return [
            'score' => max(0, $score),
            'status' => $this->get_component_status($score),
            'issues' => $issues,
            'metrics' => $metrics,
        ];
    }
    
    /**
     * Check API health
     *
     * @return array API health data
     */
    private function check_api_health() {
        $score = 100;
        $issues = [];
        $metrics = [];
        
        // Check API key
        $api_key = get_option('dodo_ai_seo_settings')['openai_api_key'] ?? '';
        $metrics['api_key_configured'] = !empty($api_key);
        
        if (empty($api_key)) {
            $score = 0;
            $issues[] = 'OpenAI API key not configured';
            
            return [
                'score' => $score,
                'status' => 'critical',
                'issues' => $issues,
                'metrics' => $metrics,
            ];
        }
        
        // Check recent API errors
        global $wpdb;
        $error_table = $wpdb->prefix . 'dodo_error_logs';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '{$error_table}'") === $error_table) {
            $recent_errors = $wpdb->get_var(
                "SELECT COUNT(*) FROM {$error_table} 
                WHERE category = 'api' 
                AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)"
            );
            
            $metrics['recent_errors'] = $recent_errors;
            
            if ($recent_errors > 10) {
                $score -= 40;
                $issues[] = sprintf('%d API errors in last hour', $recent_errors);
            } elseif ($recent_errors > 5) {
                $score -= 20;
                $issues[] = 'Elevated API error rate';
            }
        }
        
        // Check API latency
        $telemetry_table = $wpdb->prefix . 'dodo_telemetry';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$telemetry_table}'") === $telemetry_table) {
            $avg_latency = $wpdb->get_var(
                "SELECT AVG(JSON_EXTRACT(metadata, '$.duration_ms'))
                FROM {$telemetry_table}
                WHERE metric_type = 'ai_request'
                AND recorded_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)"
            );
            
            $metrics['avg_latency_ms'] = round($avg_latency);
            
            if ($avg_latency > 10000) { // 10 seconds
                $score -= 30;
                $issues[] = 'High API latency';
            }
        }
        
        return [
            'score' => max(0, $score),
            'status' => $this->get_component_status($score),
            'issues' => $issues,
            'metrics' => $metrics,
        ];
    }
    
    /**
     * Check cache health
     *
     * @return array Cache health data
     */
    private function check_cache_health() {
        $score = 100;
        $issues = [];
        $metrics = [];
        
        // Check transient API
        $test_key = 'dodo_cache_test_' . time();
        $test_value = 'test';
        
        set_transient($test_key, $test_value, 60);
        $retrieved = get_transient($test_key);
        delete_transient($test_key);
        
        $metrics['transient_working'] = ($retrieved === $test_value);
        
        if (!$metrics['transient_working']) {
            $score -= 50;
            $issues[] = 'Transient cache not working';
        }
        
        // Check cache hit ratio
        global $wpdb;
        $telemetry_table = $wpdb->prefix . 'dodo_telemetry';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '{$telemetry_table}'") === $telemetry_table) {
            $total = $wpdb->get_var(
                "SELECT COUNT(*) FROM {$telemetry_table}
                WHERE metric_type = 'brain'
                AND recorded_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
            );
            
            $cache_hits = $wpdb->get_var(
                "SELECT COUNT(*) FROM {$telemetry_table}
                WHERE metric_type = 'brain'
                AND JSON_EXTRACT(metadata, '$.cache_hit') = true
                AND recorded_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
            );
            
            $hit_ratio = $total > 0 ? round(($cache_hits / $total) * 100, 2) : 0;
            $metrics['cache_hit_ratio'] = $hit_ratio;
            
            if ($hit_ratio < 20) {
                $score -= 20;
                $issues[] = 'Low cache hit ratio';
            }
        }
        
        return [
            'score' => max(0, $score),
            'status' => $this->get_component_status($score),
            'issues' => $issues,
            'metrics' => $metrics,
        ];
    }
    
    /**
     * Check database health
     *
     * @return array Database health data
     */
    private function check_database_health() {
        global $wpdb;
        
        $score = 100;
        $issues = [];
        $metrics = [];
        
        // Check connection
        if (!$wpdb->check_connection()) {
            return [
                'score' => 0,
                'status' => 'critical',
                'issues' => ['Database connection failed'],
                'metrics' => [],
            ];
        }
        
        $metrics['connection'] = 'ok';
        
        // Check query performance
        $start = microtime(true);
        $wpdb->get_var("SELECT 1");
        $query_time = (microtime(true) - $start) * 1000;
        
        $metrics['query_time_ms'] = round($query_time, 2);
        
        if ($query_time > 100) {
            $score -= 30;
            $issues[] = 'Slow database queries';
        }
        
        // Check table sizes
        $tables = [
            'dodo_queue_jobs',
            'dodo_telemetry',
            'dodo_error_logs',
            'dodo_security_logs',
        ];
        
        $total_size = 0;
        foreach ($tables as $table) {
            $full_table = $wpdb->prefix . $table;
            $size = $wpdb->get_var("SELECT COUNT(*) FROM {$full_table}");
            $total_size += $size;
        }
        
        $metrics['total_rows'] = $total_size;
        
        if ($total_size > 100000) {
            $score -= 15;
            $issues[] = 'Large table sizes - consider cleanup';
        }
        
        return [
            'score' => max(0, $score),
            'status' => $this->get_component_status($score),
            'issues' => $issues,
            'metrics' => $metrics,
        ];
    }
    
    /**
     * Check security health
     *
     * @return array Security health data
     */
    private function check_security_health() {
        $score = 100;
        $issues = [];
        $metrics = [];
        
        // Check for recent security events
        global $wpdb;
        $security_table = $wpdb->prefix . 'dodo_security_logs';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '{$security_table}'") === $security_table) {
            $recent_events = $wpdb->get_var(
                "SELECT COUNT(*) FROM {$security_table}
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
            );
            
            $metrics['security_events_24h'] = $recent_events;
            
            if ($recent_events > 50) {
                $score -= 30;
                $issues[] = 'High security event rate';
            }
            
            // Check for critical events
            $critical_events = $wpdb->get_var(
                "SELECT COUNT(*) FROM {$security_table}
                WHERE event_type IN ('prompt_injection', 'rate_limit_exceeded', 'unauthorized_access')
                AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
            );
            
            $metrics['critical_events_24h'] = $critical_events;
            
            if ($critical_events > 0) {
                $score -= 40;
                $issues[] = sprintf('%d critical security events', $critical_events);
            }
        }
        
        // Check SSL
        $metrics['ssl_enabled'] = is_ssl();
        if (!is_ssl()) {
            $score -= 20;
            $issues[] = 'SSL not enabled';
        }
        
        return [
            'score' => max(0, $score),
            'status' => $this->get_component_status($score),
            'issues' => $issues,
            'metrics' => $metrics,
        ];
    }
    
    /**
     * Get health status from score
     *
     * @param int $score Health score
     * @return string Status
     */
    private function get_health_status($score) {
        if ($score >= self::SCORE_EXCELLENT) {
            return 'excellent';
        } elseif ($score >= self::SCORE_GOOD) {
            return 'good';
        } elseif ($score >= self::SCORE_FAIR) {
            return 'fair';
        } elseif ($score >= self::SCORE_POOR) {
            return 'poor';
        } else {
            return 'critical';
        }
    }
    
    /**
     * Get component status from score
     *
     * @param int $score Component score
     * @return string Status
     */
    private function get_component_status($score) {
        if ($score >= 80) {
            return 'healthy';
        } elseif ($score >= 60) {
            return 'warning';
        } else {
            return 'critical';
        }
    }
    
    /**
     * Get recommendations based on health report
     *
     * @param array $components Component health data
     * @return array Recommendations
     */
    private function get_recommendations($components) {
        $recommendations = [];
        
        foreach ($components as $component => $data) {
            if ($data['score'] < 70 && !empty($data['issues'])) {
                $recommendations[] = [
                    'component' => $component,
                    'priority' => $data['score'] < 50 ? 'high' : 'medium',
                    'issues' => $data['issues'],
                    'action' => $this->get_component_action($component, $data),
                ];
            }
        }
        
        return $recommendations;
    }
    
    /**
     * Get recommended action for component
     *
     * @param string $component Component name
     * @param array $data Component data
     * @return string Recommended action
     */
    private function get_component_action($component, $data) {
        $actions = [
            'queue' => 'Clear stuck jobs and review queue settings',
            'cron' => 'Check WP-Cron configuration and server cron setup',
            'api' => 'Verify API key and check OpenAI status',
            'cache' => 'Clear cache and check transient storage',
            'database' => 'Optimize database tables and check connection',
            'security' => 'Review security logs and update security settings',
        ];
        
        return $actions[$component] ?? 'Review component settings';
    }
}
