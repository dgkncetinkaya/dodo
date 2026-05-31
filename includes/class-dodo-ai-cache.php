<?php
/**
 * AI Cache Layer
 * 
 * OpenAI API maliyetini düşürmek için prompt-based cache sistemi
 * 
 * @package DODO_AI_SEO
 * @since 1.2.0
 */

// Doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

class DODO_AI_Cache {
    
    /**
     * Cache prefix
     */
    private const CACHE_PREFIX = 'dodo_ai_cache_';
    
    /**
     * Stats option key
     */
    private const STATS_KEY = 'dodo_ai_cache_stats';
    
    /**
     * Default TTL values (seconds)
     */
    private const DEFAULT_TTL = array(
        'keyword_generation' => 30 * DAY_IN_SECONDS,  // 30 gün
        'faq' => 30 * DAY_IN_SECONDS,                 // 30 gün
        'meta_description' => 30 * DAY_IN_SECONDS,    // 30 gün
        'audit' => 7 * DAY_IN_SECONDS,                // 7 gün
        'outline' => 30 * DAY_IN_SECONDS,             // 30 gün
        'introduction' => 30 * DAY_IN_SECONDS,        // 30 gün
        'main_sections' => 30 * DAY_IN_SECONDS,       // 30 gün
        'conclusion' => 30 * DAY_IN_SECONDS,          // 30 gün
        'seo_meta' => 30 * DAY_IN_SECONDS,            // 30 gün
        'default' => 7 * DAY_IN_SECONDS,              // 7 gün (fallback)
    );
    
    /**
     * Generate cache key from parameters
     * 
     * @param string $model Model adı (gpt-4o, gpt-4-turbo, etc.)
     * @param string $system_prompt System prompt
     * @param string $user_prompt User prompt
     * @param float $temperature Temperature değeri
     * @param int $max_tokens Max tokens
     * @param string $feature_name Feature adı (keyword_generation, faq, etc.)
     * @return string Cache key
     */
    public function generate_cache_key($model, $system_prompt, $user_prompt, $temperature = 0.7, $max_tokens = 16000, $feature_name = 'default') {
        // Cache key bileşenleri
        $components = array(
            'model' => $model,
            'system' => $system_prompt,
            'user' => $user_prompt,
            'temp' => $temperature,
            'max_tokens' => $max_tokens,
            'feature' => $feature_name,
        );
        
        // Hash oluştur
        $hash = md5(json_encode($components));
        
        // Prefix + feature + hash
        $cache_key = self::CACHE_PREFIX . $feature_name . '_' . $hash;
        
        // WordPress transient key max 172 karakter
        if (strlen($cache_key) > 172) {
            $cache_key = self::CACHE_PREFIX . substr($hash, 0, 140);
        }
        
        return $cache_key;
    }
    
    /**
     * Get cached AI response
     * 
     * @param string $cache_key Cache key
     * @param bool $force_refresh Force refresh (bypass cache)
     * @return array|false Cache data veya false
     */
    public function get_cached_response($cache_key, $force_refresh = false) {
        // Force refresh ise cache'i atla
        if ($force_refresh) {
            $this->log_cache_event('force_refresh', $cache_key);
            return false;
        }
        
        // Transient'tan al
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            // Cache hit
            $this->increment_stat('cache_hits');
            $this->log_cache_event('cache_hit', $cache_key, array(
                'cached_at' => $cached['cached_at'] ?? 'unknown',
                'feature' => $cached['feature'] ?? 'unknown',
            ));
            
            return $cached;
        }
        
        // Cache miss
        $this->increment_stat('cache_misses');
        $this->log_cache_event('cache_miss', $cache_key);
        
        return false;
    }
    
    /**
     * Set cached AI response
     * 
     * @param string $cache_key Cache key
     * @param string $response AI response
     * @param string $feature_name Feature adı
     * @param array $metadata Ek metadata (opsiyonel)
     * @return bool Success
     */
    public function set_cached_response($cache_key, $response, $feature_name = 'default', $metadata = array()) {
        // TTL belirle
        $ttl = self::DEFAULT_TTL[$feature_name] ?? self::DEFAULT_TTL['default'];
        
        // Cache data
        $cache_data = array(
            'response' => $response,
            'feature' => $feature_name,
            'cached_at' => current_time('mysql'),
            'expires_at' => date('Y-m-d H:i:s', time() + $ttl),
            'metadata' => $metadata,
        );
        
        // Transient'a kaydet
        $result = set_transient($cache_key, $cache_data, $ttl);
        
        if ($result) {
            $this->increment_stat('cache_sets');
            $this->log_cache_event('cache_set', $cache_key, array(
                'feature' => $feature_name,
                'ttl' => $ttl,
                'expires_at' => $cache_data['expires_at'],
            ));
        }
        
        return $result;
    }
    
    /**
     * Delete cached response
     * 
     * @param string $cache_key Cache key
     * @return bool Success
     */
    public function delete_cached_response($cache_key) {
        $result = delete_transient($cache_key);
        
        if ($result) {
            $this->increment_stat('cache_deletes');
            $this->log_cache_event('cache_delete', $cache_key);
        }
        
        return $result;
    }
    
    /**
     * Get cache statistics
     * 
     * @return array Cache stats
     */
    public function get_cache_stats() {
        $stats = get_option(self::STATS_KEY, array(
            'cache_hits' => 0,
            'cache_misses' => 0,
            'cache_sets' => 0,
            'cache_deletes' => 0,
            'total_requests' => 0,
            'last_reset' => current_time('mysql'),
        ));
        
        // Cache hit rate hesapla
        $total_requests = $stats['cache_hits'] + $stats['cache_misses'];
        $stats['total_requests'] = $total_requests;
        
        if ($total_requests > 0) {
            $stats['hit_rate'] = round(($stats['cache_hits'] / $total_requests) * 100, 2);
        } else {
            $stats['hit_rate'] = 0;
        }
        
        // Saved requests (cache hit = saved API call)
        $stats['saved_requests'] = $stats['cache_hits'];
        
        return $stats;
    }
    
    /**
     * Reset cache statistics
     * 
     * @return bool Success
     */
    public function reset_cache_stats() {
        $stats = array(
            'cache_hits' => 0,
            'cache_misses' => 0,
            'cache_sets' => 0,
            'cache_deletes' => 0,
            'total_requests' => 0,
            'last_reset' => current_time('mysql'),
        );
        
        return update_option(self::STATS_KEY, $stats);
    }
    
    /**
     * Increment cache statistic
     * 
     * @param string $stat_key Stat key (cache_hits, cache_misses, etc.)
     */
    private function increment_stat($stat_key) {
        $stats = get_option(self::STATS_KEY, array(
            'cache_hits' => 0,
            'cache_misses' => 0,
            'cache_sets' => 0,
            'cache_deletes' => 0,
            'total_requests' => 0,
            'last_reset' => current_time('mysql'),
        ));
        
        if (isset($stats[$stat_key])) {
            $stats[$stat_key]++;
        } else {
            $stats[$stat_key] = 1;
        }
        
        update_option(self::STATS_KEY, $stats, false);
    }
    
    /**
     * Log cache event (debug)
     * 
     * @param string $event_type Event type
     * @param string $cache_key Cache key
     * @param array $data Additional data
     */
    private function log_cache_event($event_type, $cache_key, $data = array()) {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }
        
        $log_message = sprintf(
            '[DODO AI CACHE] %s - Key: %s',
            strtoupper($event_type),
            substr($cache_key, 0, 50) . '...'
        );
        
        if (!empty($data)) {
            $log_message .= ' - Data: ' . json_encode($data);
        }
        
        error_log($log_message);
    }
    
    /**
     * Clear all cache (admin action)
     * 
     * @return int Number of deleted cache entries
     */
    public function clear_all_cache() {
        global $wpdb;
        
        // WordPress transients tablosundan tüm DODO cache'leri sil
        $prefix = self::CACHE_PREFIX;
        
        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} 
                WHERE option_name LIKE %s 
                OR option_name LIKE %s",
                $wpdb->esc_like('_transient_' . $prefix) . '%',
                $wpdb->esc_like('_transient_timeout_' . $prefix) . '%'
            )
        );
        
        $this->log_cache_event('clear_all', 'all', array('deleted' => $deleted));
        
        return $deleted;
    }
    
    /**
     * Clear cache by feature
     * 
     * @param string $feature_name Feature adı
     * @return int Number of deleted cache entries
     */
    public function clear_cache_by_feature($feature_name) {
        global $wpdb;
        
        $prefix = self::CACHE_PREFIX . $feature_name;
        
        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} 
                WHERE option_name LIKE %s 
                OR option_name LIKE %s",
                $wpdb->esc_like('_transient_' . $prefix) . '%',
                $wpdb->esc_like('_transient_timeout_' . $prefix) . '%'
            )
        );
        
        $this->log_cache_event('clear_feature', $feature_name, array('deleted' => $deleted));
        
        return $deleted;
    }
    
    /**
     * Get cache size (approximate)
     * 
     * @return array Cache size info
     */
    public function get_cache_size() {
        global $wpdb;
        
        $prefix = self::CACHE_PREFIX;
        
        // Count cache entries
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->options} 
                WHERE option_name LIKE %s",
                $wpdb->esc_like('_transient_' . $prefix) . '%'
            )
        );
        
        // Estimate size (rough)
        $size_bytes = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(LENGTH(option_value)) FROM {$wpdb->options} 
                WHERE option_name LIKE %s",
                $wpdb->esc_like('_transient_' . $prefix) . '%'
            )
        );
        
        return array(
            'count' => (int) $count,
            'size_bytes' => (int) $size_bytes,
            'size_kb' => round($size_bytes / 1024, 2),
            'size_mb' => round($size_bytes / 1024 / 1024, 2),
        );
    }
    
    /**
     * Check if cache is enabled
     * 
     * @return bool
     */
    public function is_cache_enabled() {
        $settings = new DODO_Settings();
        return $settings->get_setting('ai_cache_enabled', true);
    }
    
    /**
     * Get TTL for feature
     * 
     * @param string $feature_name Feature adı
     * @return int TTL in seconds
     */
    public function get_ttl($feature_name) {
        return self::DEFAULT_TTL[$feature_name] ?? self::DEFAULT_TTL['default'];
    }
}
