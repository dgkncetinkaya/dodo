<?php
/**
 * Rate Limiter
 * 
 * API rate limiting for OpenAI, embeddings, SERP, GSC
 * 
 * @package DODO_AI_SEO
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Rate_Limiter {
    
    /**
     * Rate limits (requests per minute)
     */
    const LIMIT_OPENAI_GLOBAL = 60;
    const LIMIT_OPENAI_USER = 10;
    const LIMIT_EMBEDDING_GLOBAL = 100;
    const LIMIT_EMBEDDING_USER = 20;
    const LIMIT_SERP_GLOBAL = 30;
    const LIMIT_SERP_USER = 5;
    const LIMIT_GSC_GLOBAL = 20;
    const LIMIT_GSC_USER = 3;
    
    /**
     * Check if rate limit exceeded
     * 
     * @param string $service Service name (openai, embedding, serp, gsc)
     * @param int $user_id User ID (0 for global)
     * @return bool True if allowed, false if rate limited
     */
    public static function check($service, $user_id = 0) {
        // Get limits
        $global_limit = self::get_global_limit($service);
        $user_limit = self::get_user_limit($service);
        
        // Check global limit
        $global_count = self::get_request_count($service, 0);
        if ($global_count >= $global_limit) {
            error_log("DODO Rate Limit: Global limit exceeded for {$service} ({$global_count}/{$global_limit})");
            return false;
        }
        
        // Check user limit
        if ($user_id > 0) {
            $user_count = self::get_request_count($service, $user_id);
            if ($user_count >= $user_limit) {
                error_log("DODO Rate Limit: User limit exceeded for {$service} user {$user_id} ({$user_count}/{$user_limit})");
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Check rate limit (backward compatible wrapper)
     * 
     * Returns detailed information about rate limit status
     * 
     * @param string $feature Feature name (maps to service)
     * @param int $cost Cost (not used, for compatibility)
     * @param array $context Context (user_id, etc.)
     * @return array Rate limit status
     */
    public static function check_limit($feature = 'openai', $cost = 1, $context = array()) {
        // Map feature to service
        $service = $feature;
        
        // Get user ID from context
        $user_id = isset($context['user_id']) ? (int) $context['user_id'] : get_current_user_id();
        
        // Check rate limit
        $allowed = self::check($service, $user_id);
        
        // Get remaining counts
        $remaining_data = self::get_remaining($service, $user_id);
        
        // Build response
        return array(
            'allowed' => $allowed,
            'remaining' => $allowed ? $remaining_data['global_remaining'] : 0,
            'reset_at' => date('Y-m-d H:i:s', time() + 60), // 1 minute from now
            'message' => $allowed 
                ? 'Rate limit OK' 
                : sprintf(
                    'API kullanım limiti aşıldı (%s). Lütfen bir dakika bekleyin.',
                    $service
                )
        );
    }
    
    /**
     * Record request
     * 
     * @param string $service Service name
     * @param int $user_id User ID
     */
    public static function record($service, $user_id = 0) {
        // Record global
        self::increment_count($service, 0);
        
        // Record user
        if ($user_id > 0) {
            self::increment_count($service, $user_id);
        }
    }
    
    /**
     * Get request count
     * 
     * @param string $service Service name
     * @param int $user_id User ID (0 for global)
     * @return int Request count
     */
    private static function get_request_count($service, $user_id) {
        $key = self::get_cache_key($service, $user_id);
        $count = get_transient($key);
        return $count ? (int) $count : 0;
    }
    
    /**
     * Increment count
     * 
     * @param string $service Service name
     * @param int $user_id User ID
     */
    private static function increment_count($service, $user_id) {
        $key = self::get_cache_key($service, $user_id);
        $count = self::get_request_count($service, $user_id);
        set_transient($key, $count + 1, 60); // 1 minute TTL
    }
    
    /**
     * Get cache key
     * 
     * @param string $service Service name
     * @param int $user_id User ID
     * @return string Cache key
     */
    private static function get_cache_key($service, $user_id) {
        $scope = $user_id > 0 ? "user_{$user_id}" : 'global';
        return "dodo_rate_limit_{$service}_{$scope}";
    }
    
    /**
     * Get global limit
     * 
     * @param string $service Service name
     * @return int Limit
     */
    private static function get_global_limit($service) {
        switch ($service) {
            case 'openai':
                return self::LIMIT_OPENAI_GLOBAL;
            case 'embedding':
                return self::LIMIT_EMBEDDING_GLOBAL;
            case 'serp':
                return self::LIMIT_SERP_GLOBAL;
            case 'gsc':
                return self::LIMIT_GSC_GLOBAL;
            default:
                return 60;
        }
    }
    
    /**
     * Get user limit
     * 
     * @param string $service Service name
     * @return int Limit
     */
    private static function get_user_limit($service) {
        switch ($service) {
            case 'openai':
                return self::LIMIT_OPENAI_USER;
            case 'embedding':
                return self::LIMIT_EMBEDDING_USER;
            case 'serp':
                return self::LIMIT_SERP_USER;
            case 'gsc':
                return self::LIMIT_GSC_USER;
            default:
                return 10;
        }
    }
    
    /**
     * Get remaining requests
     * 
     * @param string $service Service name
     * @param int $user_id User ID
     * @return array Remaining counts
     */
    public static function get_remaining($service, $user_id = 0) {
        $global_limit = self::get_global_limit($service);
        $user_limit = self::get_user_limit($service);
        
        $global_count = self::get_request_count($service, 0);
        $user_count = $user_id > 0 ? self::get_request_count($service, $user_id) : 0;
        
        return [
            'global_remaining' => max(0, $global_limit - $global_count),
            'global_limit' => $global_limit,
            'user_remaining' => max(0, $user_limit - $user_count),
            'user_limit' => $user_limit,
        ];
    }
    
    /**
     * Reset limits (for testing)
     * 
     * @param string $service Service name
     * @param int $user_id User ID (0 for all)
     */
    public static function reset($service, $user_id = 0) {
        if ($user_id > 0) {
            $key = self::get_cache_key($service, $user_id);
            delete_transient($key);
        } else {
            // Reset global
            $key = self::get_cache_key($service, 0);
            delete_transient($key);
        }
    }
}
