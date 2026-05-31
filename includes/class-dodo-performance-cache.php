<?php
/**
 * Performance + Vector Cache Layer
 * 
 * High-performance caching for expensive operations
 * Embeddings, similarity calculations, SERP snapshots, GSC data
 *
 * @package DODO_AI_SEO
 * @since 2.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Performance_Cache {
    
    /**
     * Cache types
     */
    const CACHE_EMBEDDING = 'embedding';
    const CACHE_SIMILARITY = 'similarity';
    const CACHE_SERP = 'serp';
    const CACHE_GSC = 'gsc';
    const CACHE_SEMANTIC = 'semantic';
    const CACHE_GRAPH = 'graph';
    
    /**
     * TTL strategies (in seconds)
     */
    const TTL_EMBEDDING = 2592000; // 30 days - embeddings rarely change
    const TTL_SIMILARITY = 86400; // 1 day - similarity can be recalculated
    const TTL_SERP = 86400; // 1 day - SERP changes daily
    const TTL_GSC = 43200; // 12 hours - GSC data updates frequently
    const TTL_SEMANTIC = 86400; // 1 day
    const TTL_GRAPH = 3600; // 1 hour - graph is dynamic
    
    /**
     * Cache table
     */
    private $table_name;
    
    /**
     * Stats
     */
    private $hits = 0;
    private $misses = 0;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'dodo_performance_cache';
    }
    
    /**
     * Get cached value with stampede protection
     *
     * @param string $cache_type Cache type
     * @param string $key Cache key
     * @return mixed|null Cached value or null
     */
    public function get($cache_type, $key) {
        global $wpdb;
        
        $cache_key = $this->generate_cache_key($cache_type, $key);
        
        // Try transient first (faster)
        $transient_key = 'dodo_cache_' . md5($cache_key);
        $cached = get_transient($transient_key);
        
        if ($cached !== false) {
            $this->hits++;
            error_log("[DODO Cache] HIT: {$cache_type} - {$key}");
            return maybe_unserialize($cached);
        }
        
        // Stampede protection: Check if regeneration is in progress
        $lock_key = 'dodo_cache_lock_' . md5($cache_key);
        $lock = get_transient($lock_key);
        
        if ($lock) {
            // Another process is regenerating, wait briefly and try again
            usleep(100000); // 100ms
            $cached = get_transient($transient_key);
            if ($cached !== false) {
                $this->hits++;
                return maybe_unserialize($cached);
            }
        }
        
        // Try database
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT cache_value, expires_at FROM {$this->table_name} 
             WHERE cache_key = %s AND expires_at > NOW()",
            $cache_key
        ));
        
        if ($result) {
            $value = maybe_unserialize($result->cache_value);
            
            // Store in transient for faster access
            $ttl = strtotime($result->expires_at) - time();
            if ($ttl > 0) {
                set_transient($transient_key, $result->cache_value, $ttl);
            }
            
            $this->hits++;
            error_log("[DODO Cache] HIT (DB): {$cache_type} - {$key}");
            return $value;
        }
        
        $this->misses++;
        error_log("[DODO Cache] MISS: {$cache_type} - {$key}");
        return null;
    }
    
    /**
     * Set cached value with stampede protection
     *
     * @param string $cache_type Cache type
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int|null $ttl TTL in seconds (null = use default)
     * @return bool Success
     */
    public function set($cache_type, $key, $value, $ttl = null) {
        global $wpdb;
        
        if ($ttl === null) {
            $ttl = $this->get_default_ttl($cache_type);
        }
        
        $cache_key = $this->generate_cache_key($cache_type, $key);
        $serialized = maybe_serialize($value);
        
        // Set lock to prevent stampede
        $lock_key = 'dodo_cache_lock_' . md5($cache_key);
        set_transient($lock_key, time(), 10); // 10 second lock
        
        // Store in transient
        $transient_key = 'dodo_cache_' . md5($cache_key);
        set_transient($transient_key, $serialized, $ttl);
        
        // CRITICAL FIX: Check if metadata column exists before using it
        $columns = $wpdb->get_col("DESCRIBE {$this->table_name}", 0);
        $has_metadata = in_array('metadata', $columns);
        
        // Prepare data array
        $data = [
            'cache_key' => $cache_key,
            'cache_type' => $cache_type,
            'cache_value' => $serialized,
            'created_at' => current_time('mysql'),
            'expires_at' => date('Y-m-d H:i:s', time() + $ttl),
        ];
        
        $format = ['%s', '%s', '%s', '%s', '%s'];
        
        // Add metadata only if column exists
        if ($has_metadata) {
            $data['metadata'] = json_encode([
                'size' => strlen($serialized),
                'created_at' => current_time('mysql'),
            ]);
            $format[] = '%s';
        } else {
            error_log('[DODO Cache] WARNING: metadata column missing, skipping metadata');
        }
        
        // Store in database
        $result = $wpdb->replace(
            $this->table_name,
            $data,
            $format
        );
        
        // Release lock
        delete_transient($lock_key);
        
        if ($result) {
            error_log("[DODO Cache] SET: {$cache_type} - {$key} (TTL: {$ttl}s)");
        } else {
            error_log("[DODO Cache] FAILED: {$cache_type} - {$key} - Error: " . $wpdb->last_error);
        }
        
        return $result !== false;
    }
    
    /**
     * Delete cached value
     *
     * @param string $cache_type Cache type
     * @param string $key Cache key
     * @return bool Success
     */
    public function delete($cache_type, $key) {
        global $wpdb;
        
        $cache_key = $this->generate_cache_key($cache_type, $key);
        
        // Delete transient
        $transient_key = 'dodo_cache_' . md5($cache_key);
        delete_transient($transient_key);
        
        // Delete from database
        $result = $wpdb->delete(
            $this->table_name,
            ['cache_key' => $cache_key],
            ['%s']
        );
        
        error_log("[DODO Cache] DELETE: {$cache_type} - {$key}");
        
        return $result !== false;
    }
    
    /**
     * Clear cache by type
     *
     * @param string $cache_type Cache type
     * @return int Number of entries deleted
     */
    public function clear_by_type($cache_type) {
        global $wpdb;
        
        $result = $wpdb->delete(
            $this->table_name,
            ['cache_type' => $cache_type],
            ['%s']
        );
        
        error_log("[DODO Cache] CLEAR TYPE: {$cache_type} ({$result} entries)");
        
        return $result;
    }
    
    /**
     * Clear expired cache entries
     *
     * @return int Number of entries deleted
     */
    public function clear_expired() {
        global $wpdb;
        
        $result = $wpdb->query(
            "DELETE FROM {$this->table_name} WHERE expires_at < NOW()"
        );
        
        error_log("[DODO Cache] CLEAR EXPIRED: {$result} entries");
        
        return $result;
    }
    
    /**
     * Get cache statistics
     *
     * @return array Cache stats
     */
    public function get_stats() {
        global $wpdb;
        
        $stats = [
            'total_entries' => 0,
            'total_size_mb' => 0,
            'by_type' => [],
            'hit_rate' => 0,
            'hits' => $this->hits,
            'misses' => $this->misses,
        ];
        
        // Count entries by type
        $results = $wpdb->get_results(
            "SELECT cache_type, COUNT(*) as count, 
             SUM(LENGTH(cache_value)) as total_size
             FROM {$this->table_name}
             WHERE expires_at > NOW()
             GROUP BY cache_type"
        );
        
        foreach ($results as $row) {
            $stats['total_entries'] += $row->count;
            $stats['total_size_mb'] += $row->total_size;
            
            $stats['by_type'][$row->cache_type] = [
                'count' => $row->count,
                'size_mb' => round($row->total_size / 1024 / 1024, 2),
            ];
        }
        
        $stats['total_size_mb'] = round($stats['total_size_mb'] / 1024 / 1024, 2);
        
        // Calculate hit rate
        $total_requests = $this->hits + $this->misses;
        if ($total_requests > 0) {
            $stats['hit_rate'] = round(($this->hits / $total_requests) * 100, 2);
        }
        
        return $stats;
    }
    
    /**
     * Warm up cache for common operations
     *
     * @param array $options Warmup options
     * @return array Warmup results
     */
    public function warmup($options = []) {
        $start_time = microtime(true);
        
        error_log('[DODO Cache] Starting cache warmup');
        
        $defaults = [
            'post_limit' => 20,
            'include_semantic' => true,
            'include_graph' => true,
        ];
        $options = wp_parse_args($options, $defaults);
        
        $warmed = 0;
        
        // Warm up semantic similarities for recent posts
        if ($options['include_semantic']) {
            require_once plugin_dir_path(__FILE__) . 'class-dodo-semantic-engine.php';
            $semantic = new DODO_Semantic_Engine();
            
            $posts = get_posts([
                'post_type' => 'post',
                'post_status' => 'publish',
                'posts_per_page' => $options['post_limit'],
            ]);
            
            foreach ($posts as $post) {
                // Calculate similarities with other posts
                $similar = $semantic->find_similar_posts($post->ID, 5, 0.5);
                $warmed += count($similar);
            }
        }
        
        // Warm up topic graph
        if ($options['include_graph']) {
            require_once plugin_dir_path(__FILE__) . 'class-dodo-topic-graph.php';
            $graph = new DODO_Topic_Graph();
            $graph->build_graph(['post_limit' => $options['post_limit']]);
            $warmed++;
        }
        
        $execution_time = round((microtime(true) - $start_time) * 1000, 2);
        
        error_log("[DODO Cache] Warmup complete: {$warmed} entries ({$execution_time}ms)");
        
        return [
            'warmed_entries' => $warmed,
            'execution_time_ms' => $execution_time,
        ];
    }
    
    /**
     * Generate cache key
     */
    private function generate_cache_key($cache_type, $key) {
        return $cache_type . '_' . md5($key);
    }
    
    /**
     * Get default TTL for cache type
     */
    private function get_default_ttl($cache_type) {
        switch ($cache_type) {
            case self::CACHE_EMBEDDING:
                return self::TTL_EMBEDDING;
            case self::CACHE_SIMILARITY:
                return self::TTL_SIMILARITY;
            case self::CACHE_SERP:
                return self::TTL_SERP;
            case self::CACHE_GSC:
                return self::TTL_GSC;
            case self::CACHE_SEMANTIC:
                return self::TTL_SEMANTIC;
            case self::CACHE_GRAPH:
                return self::TTL_GRAPH;
            default:
                return 3600; // 1 hour default
        }
    }
    
    /**
     * Create cache table
     */
    public static function create_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dodo_performance_cache';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            cache_key varchar(128) NOT NULL,
            cache_type varchar(50) NOT NULL,
            cache_value longtext NOT NULL,
            metadata text,
            created_at datetime NOT NULL,
            expires_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY cache_key (cache_key),
            KEY cache_type (cache_type),
            KEY expires_at (expires_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        error_log('[DODO Cache] Performance cache table created');
    }
    
    /**
     * Schedule cleanup cron
     */
    public static function schedule_cleanup() {
        if (!wp_next_scheduled('dodo_cache_cleanup')) {
            wp_schedule_event(time(), 'daily', 'dodo_cache_cleanup');
            error_log('[DODO Cache] Cleanup cron scheduled');
        }
    }
    
    /**
     * Cleanup cron callback
     * HARDENED: Sprint A - Cron Safe Wrapper
     */
    public static function cleanup_cron() {
        try {
            $cache = new self();
            $deleted = $cache->clear_expired();
            error_log("[DODO][CRON][Cache] Cleanup: {$deleted} expired entries removed");
            
        } catch (Throwable $e) {
            // Log error but don't break cron
            if (class_exists('DODO_Error_Handler')) {
                DODO_Error_Handler::log_error_with_context('CRON', 'Cache cleanup failed', array(
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ));
            } else {
                error_log('[DODO][CRON][Cache] Fatal error: ' . $e->getMessage());
            }
        }
    }
}

// Register cron hook
add_action('dodo_cache_cleanup', ['DODO_Performance_Cache', 'cleanup_cron']);
