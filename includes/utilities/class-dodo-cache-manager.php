<?php
/**
 * Cache Manager - Centralized caching strategy
 * 
 * PHASE 7 - Architecture Cleanup
 * Standardizes all caching operations
 * 
 * @package DODO_AI_SEO
 * @since 4.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (class_exists('DODO_Cache_Manager')) {
    return;
}

class DODO_Cache_Manager {
    
    /**
     * Cache prefix
     */
    const PREFIX = 'dodo_';
    
    /**
     * Default TTL values (in seconds)
     */
    const TTL_SHORT = 300;        // 5 minutes
    const TTL_MEDIUM = 3600;      // 1 hour
    const TTL_LONG = 21600;       // 6 hours
    const TTL_DAY = 86400;        // 24 hours
    const TTL_WEEK = 604800;      // 7 days
    
    /**
     * Get cached value
     * 
     * @param string $key Cache key
     * @param string $group Cache group (optional)
     * @return mixed|false Cached value or false
     */
    public static function get($key, $group = '') {
        $cache_key = self::build_key($key, $group);
        return get_transient($cache_key);
    }
    
    /**
     * Set cached value
     * 
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int $ttl Time to live in seconds
     * @param string $group Cache group (optional)
     * @return bool Success
     */
    public static function set($key, $value, $ttl = self::TTL_MEDIUM, $group = '') {
        $cache_key = self::build_key($key, $group);
        return set_transient($cache_key, $value, $ttl);
    }
    
    /**
     * Delete cached value
     * 
     * @param string $key Cache key
     * @param string $group Cache group (optional)
     * @return bool Success
     */
    public static function delete($key, $group = '') {
        $cache_key = self::build_key($key, $group);
        return delete_transient($cache_key);
    }
    
    /**
     * Clear all cache in a group
     * 
     * @param string $group Cache group
     * @return int Number of deleted items
     */
    public static function clear_group($group) {
        global $wpdb;
        
        $pattern = self::PREFIX . $group . '_%';
        
        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} 
                WHERE option_name LIKE %s 
                OR option_name LIKE %s",
                '_transient_' . $pattern,
                '_transient_timeout_' . $pattern
            )
        );
        
        return $deleted;
    }
    
    /**
     * Clear all DODO cache
     * 
     * @return int Number of deleted items
     */
    public static function clear_all() {
        global $wpdb;
        
        $deleted = $wpdb->query(
            "DELETE FROM {$wpdb->options} 
            WHERE option_name LIKE '_transient_dodo_%' 
            OR option_name LIKE '_transient_timeout_dodo_%'"
        );
        
        return $deleted;
    }
    
    /**
     * Get or set with callback
     * 
     * @param string $key Cache key
     * @param callable $callback Function to generate value if not cached
     * @param int $ttl Time to live
     * @param string $group Cache group
     * @return mixed Cached or generated value
     */
    public static function remember($key, $callback, $ttl = self::TTL_MEDIUM, $group = '') {
        $value = self::get($key, $group);
        
        if ($value !== false) {
            return $value;
        }
        
        $value = call_user_func($callback);
        self::set($key, $value, $ttl, $group);
        
        return $value;
    }
    
    /**
     * Build cache key
     * 
     * @param string $key Base key
     * @param string $group Group
     * @return string Full cache key
     */
    private static function build_key($key, $group = '') {
        if (!empty($group)) {
            return self::PREFIX . $group . '_' . $key;
        }
        return self::PREFIX . $key;
    }
    
    /**
     * Get cache statistics
     * 
     * @return array Statistics
     */
    public static function get_stats() {
        global $wpdb;
        
        $total = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->options} 
            WHERE option_name LIKE '_transient_dodo_%'"
        );
        
        $size = $wpdb->get_var(
            "SELECT SUM(LENGTH(option_value)) FROM {$wpdb->options} 
            WHERE option_name LIKE '_transient_dodo_%'"
        );
        
        return array(
            'total_items' => (int) $total,
            'total_size_bytes' => (int) $size,
            'total_size_mb' => round($size / 1024 / 1024, 2),
        );
    }
    
    /**
     * Clean expired cache
     * 
     * @return int Number of deleted items
     */
    public static function clean_expired() {
        global $wpdb;
        
        // Get expired transient timeouts
        $expired = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT option_name FROM {$wpdb->options} 
                WHERE option_name LIKE %s 
                AND option_value < %d",
                '_transient_timeout_dodo_%',
                time()
            )
        );
        
        $deleted = 0;
        
        foreach ($expired as $timeout_key) {
            // Extract transient name
            $transient_key = str_replace('_transient_timeout_', '_transient_', $timeout_key);
            
            // Delete both timeout and value
            $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name = %s", $timeout_key));
            $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name = %s", $transient_key));
            
            $deleted++;
        }
        
        return $deleted;
    }
    
    /**
     * Set cache lock (for stampede protection)
     * 
     * @param string $key Lock key
     * @param int $ttl Lock duration
     * @return bool Success
     */
    public static function lock($key, $ttl = 10) {
        $lock_key = 'lock_' . $key;
        return self::set($lock_key, time(), $ttl);
    }
    
    /**
     * Check if cache is locked
     * 
     * @param string $key Lock key
     * @return bool Is locked
     */
    public static function is_locked($key) {
        $lock_key = 'lock_' . $key;
        return self::get($lock_key) !== false;
    }
    
    /**
     * Release cache lock
     * 
     * @param string $key Lock key
     * @return bool Success
     */
    public static function unlock($key) {
        $lock_key = 'lock_' . $key;
        return self::delete($lock_key);
    }
}
