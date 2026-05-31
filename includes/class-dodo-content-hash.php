<?php
/**
 * Content Hash Manager
 * 
 * İçerik değişmediyse audit tekrar AI çağrısı yapmasın
 * 
 * @package DODO_AI_SEO
 * @since 1.2.0
 */

// Doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

class DODO_Content_Hash {
    
    /**
     * Meta key for content hash
     */
    private const HASH_META_KEY = '_dodo_content_hash';
    
    /**
     * Meta key for audit result
     */
    private const AUDIT_META_KEY = '_dodo_audit_result';
    
    /**
     * Meta key for audit score
     */
    private const SCORE_META_KEY = '_dodo_audit_score';
    
    /**
     * Generate content hash
     * 
     * @param string $content Post content
     * @return string MD5 hash
     */
    public function generate_hash($content) {
        // HTML taglarını temizle
        $clean_content = strip_tags($content);
        
        // Whitespace normalize et
        $clean_content = preg_replace('/\s+/', ' ', $clean_content);
        
        // Trim
        $clean_content = trim($clean_content);
        
        // MD5 hash
        return md5($clean_content);
    }
    
    /**
     * Get saved content hash
     * 
     * @param int $post_id Post ID
     * @return string|false Hash veya false
     */
    public function get_saved_hash($post_id) {
        return get_post_meta($post_id, self::HASH_META_KEY, true);
    }
    
    /**
     * Save content hash
     * 
     * @param int $post_id Post ID
     * @param string $hash Content hash
     * @return bool Success
     */
    public function save_hash($post_id, $hash) {
        return update_post_meta($post_id, self::HASH_META_KEY, $hash);
    }
    
    /**
     * Check if content changed
     * 
     * @param int $post_id Post ID
     * @param string $current_content Current post content
     * @return bool True if changed, false if same
     */
    public function has_content_changed($post_id, $current_content) {
        $saved_hash = $this->get_saved_hash($post_id);
        
        if (empty($saved_hash)) {
            // Hash yoksa değişmiş kabul et
            return true;
        }
        
        $current_hash = $this->generate_hash($current_content);
        
        return $saved_hash !== $current_hash;
    }
    
    /**
     * Get cached audit result
     * 
     * @param int $post_id Post ID
     * @return array|false Audit result veya false
     */
    public function get_cached_audit($post_id) {
        $audit_result = get_post_meta($post_id, self::AUDIT_META_KEY, true);
        
        if (empty($audit_result)) {
            return false;
        }
        
        // JSON decode
        if (is_string($audit_result)) {
            $audit_result = json_decode($audit_result, true);
        }
        
        return $audit_result;
    }
    
    /**
     * Save audit result
     * 
     * @param int $post_id Post ID
     * @param array $audit_result Audit result
     * @param string $content_hash Content hash
     * @return bool Success
     */
    public function save_audit_result($post_id, $audit_result, $content_hash) {
        // Audit result'u JSON olarak kaydet
        $json_result = json_encode($audit_result);
        
        // Meta'ya kaydet
        $result1 = update_post_meta($post_id, self::AUDIT_META_KEY, $json_result);
        $result2 = update_post_meta($post_id, self::HASH_META_KEY, $content_hash);
        
        // Overall score'u ayrı kaydet (query için)
        if (isset($audit_result['overall_score'])) {
            update_post_meta($post_id, self::SCORE_META_KEY, $audit_result['overall_score']);
        }
        
        $this->log_event('audit_cached', $post_id, array(
            'hash' => $content_hash,
            'overall_score' => $audit_result['overall_score'] ?? 0,
        ));
        
        return $result1 && $result2;
    }
    
    /**
     * Should use cached audit?
     * 
     * @param int $post_id Post ID
     * @param string $current_content Current post content
     * @return bool True if should use cache
     */
    public function should_use_cached_audit($post_id, $current_content) {
        // İçerik değişti mi kontrol et
        if ($this->has_content_changed($post_id, $current_content)) {
            $this->log_event('content_changed', $post_id);
            return false;
        }
        
        // Cached audit var mı kontrol et
        $cached_audit = $this->get_cached_audit($post_id);
        
        if (empty($cached_audit)) {
            $this->log_event('no_cached_audit', $post_id);
            return false;
        }
        
        $this->log_event('using_cached_audit', $post_id);
        return true;
    }
    
    /**
     * Clear cached audit
     * 
     * @param int $post_id Post ID
     * @return bool Success
     */
    public function clear_cached_audit($post_id) {
        delete_post_meta($post_id, self::HASH_META_KEY);
        delete_post_meta($post_id, self::AUDIT_META_KEY);
        delete_post_meta($post_id, self::SCORE_META_KEY);
        
        $this->log_event('audit_cache_cleared', $post_id);
        
        return true;
    }
    
    /**
     * Clear all cached audits
     * 
     * @return int Number of cleared audits
     */
    public function clear_all_cached_audits() {
        global $wpdb;
        
        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->postmeta} 
                WHERE meta_key IN (%s, %s, %s)",
                self::HASH_META_KEY,
                self::AUDIT_META_KEY,
                self::SCORE_META_KEY
            )
        );
        
        $this->log_event('all_audit_cache_cleared', 0, array('deleted' => $deleted));
        
        return $deleted;
    }
    
    /**
     * Get cache statistics
     * 
     * @return array Stats
     */
    public function get_cache_stats() {
        global $wpdb;
        
        // Cached audit sayısı
        $cached_count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} 
                WHERE meta_key = %s",
                self::HASH_META_KEY
            )
        );
        
        // Toplam post sayısı
        $total_posts = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} 
            WHERE post_type = 'post' AND post_status = 'publish'"
        );
        
        return array(
            'cached_audits' => (int) $cached_count,
            'total_posts' => (int) $total_posts,
            'cache_coverage' => $total_posts > 0 ? round(($cached_count / $total_posts) * 100, 2) : 0,
        );
    }
    
    /**
     * Log event (debug)
     * 
     * @param string $event_type Event type
     * @param int $post_id Post ID
     * @param array $data Additional data
     */
    private function log_event($event_type, $post_id, $data = array()) {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }
        
        $log_message = sprintf(
            '[DODO CONTENT HASH] %s - Post ID: %d',
            strtoupper($event_type),
            $post_id
        );
        
        if (!empty($data)) {
            $log_message .= ' - Data: ' . json_encode($data);
        }
        
        error_log($log_message);
    }
}
