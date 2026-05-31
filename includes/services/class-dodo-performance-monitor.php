<?php
/**
 * Performance Monitor - Performance profiling and monitoring
 * 
 * PHASE 7 - Performance Profiling + Debug Tooling + Telemetry
 * Tracks performance metrics, bottlenecks, and system health
 * 
 * @package DODO_AI_SEO
 * @since 4.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (class_exists('DODO_Performance_Monitor')) {
    return;
}

class DODO_Performance_Monitor {
    
    /**
     * Performance markers
     */
    private static $markers = array();
    
    /**
     * Initialized flag
     */
    private static $initialized = false;
    
    /**
     * Initialize performance monitor
     */
    public static function init() {
        if (self::$initialized) {
            return;
        }
        
        self::$initialized = true;
        
        // Performance monitor is ready
        // No hooks needed - all methods are static and called directly
    }
    
    /**
     * Start performance marker
     * 
     * @param string $name Marker name
     */
    public static function start($name) {
        self::$markers[$name] = array(
            'start' => microtime(true),
            'memory_start' => memory_get_usage(),
        );
    }
    
    /**
     * End performance marker
     * 
     * @param string $name Marker name
     * @return array Performance data
     */
    public static function end($name) {
        if (!isset(self::$markers[$name])) {
            return array('error' => 'Marker not found');
        }
        
        $marker = self::$markers[$name];
        
        $data = array(
            'name' => $name,
            'duration_ms' => round((microtime(true) - $marker['start']) * 1000, 2),
            'memory_used_mb' => round((memory_get_usage() - $marker['memory_start']) / 1024 / 1024, 2),
            'memory_peak_mb' => round(memory_get_peak_usage() / 1024 / 1024, 2),
        );
        
        // Log if slow
        if ($data['duration_ms'] > 1000) {
            error_log("[DODO Performance] SLOW: {$name} took {$data['duration_ms']}ms");
        }
        
        unset(self::$markers[$name]);
        
        return $data;
    }
    
    /**
     * Get all performance metrics
     */
    public static function get_metrics() {
        return array(
            'page_load_time' => self::get_page_load_time(),
            'memory_usage' => self::get_memory_usage(),
            'database_queries' => self::get_db_query_count(),
            'cache_stats' => self::get_cache_stats(),
            'api_latency' => self::get_api_latency(),
        );
    }
    
    /**
     * Get page load time
     */
    private static function get_page_load_time() {
        if (defined('DODO_START_TIME')) {
            return round((microtime(true) - DODO_START_TIME) * 1000, 2);
        }
        return 0;
    }
    
    /**
     * Get memory usage
     */
    private static function get_memory_usage() {
        return array(
            'current_mb' => round(memory_get_usage() / 1024 / 1024, 2),
            'peak_mb' => round(memory_get_peak_usage() / 1024 / 1024, 2),
            'limit_mb' => ini_get('memory_limit'),
        );
    }
    
    /**
     * Get database query count
     */
    private static function get_db_query_count() {
        global $wpdb;
        return array(
            'total_queries' => $wpdb->num_queries,
            'query_time' => round($wpdb->timer_stop(), 4),
        );
    }
    
    /**
     * Get cache statistics
     */
    private static function get_cache_stats() {
        if (class_exists('DODO_Cache_Manager')) {
            return DODO_Cache_Manager::get_stats();
        }
        return array();
    }
    
    /**
     * Get API latency
     */
    private static function get_api_latency() {
        // Test OpenAI latency
        $start = microtime(true);
        
        if (class_exists('DODO_OpenAI')) {
            $openai = new DODO_OpenAI();
            $test = $openai->test_connection();
            $latency = round((microtime(true) - $start) * 1000, 2);
            
            return array(
                'openai_ms' => $latency,
                'status' => is_wp_error($test) ? 'error' : 'ok',
            );
        }
        
        return array('openai_ms' => 0, 'status' => 'unavailable');
    }
    
    /**
     * Identify bottlenecks
     */
    public static function identify_bottlenecks() {
        $bottlenecks = array();
        
        // Check slow admin pages
        if (is_admin()) {
            $load_time = self::get_page_load_time();
            if ($load_time > 3000) {
                $bottlenecks[] = array(
                    'type' => 'slow_page_load',
                    'value' => $load_time,
                    'threshold' => 3000,
                    'recommendation' => 'Page load > 3s - check database queries and API calls',
                );
            }
        }
        
        // Check memory usage
        $memory = self::get_memory_usage();
        $limit_mb = (int) $memory['limit_mb'];
        if ($memory['peak_mb'] > ($limit_mb * 0.8)) {
            $bottlenecks[] = array(
                'type' => 'high_memory',
                'value' => $memory['peak_mb'],
                'threshold' => $limit_mb * 0.8,
                'recommendation' => 'Memory usage > 80% of limit - optimize data structures',
            );
        }
        
        // Check database queries
        $db = self::get_db_query_count();
        if ($db['total_queries'] > 100) {
            $bottlenecks[] = array(
                'type' => 'excessive_queries',
                'value' => $db['total_queries'],
                'threshold' => 100,
                'recommendation' => 'Too many DB queries - implement caching',
            );
        }
        
        return $bottlenecks;
    }
    
    /**
     * Get debug trace
     */
    public static function get_debug_trace() {
        return array(
            'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10),
            'included_files' => count(get_included_files()),
            'declared_classes' => count(get_declared_classes()),
        );
    }
    
    /**
     * Log performance event
     */
    public static function log_event($event_name, $data = array()) {
        if (!WP_DEBUG) {
            return;
        }
        
        $log_data = array_merge(array(
            'event' => $event_name,
            'timestamp' => current_time('mysql'),
            'memory_mb' => round(memory_get_usage() / 1024 / 1024, 2),
        ), $data);
        
        error_log('[DODO Performance] ' . json_encode($log_data));
    }
}
