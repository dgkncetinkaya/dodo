<?php
/**
 * AI Cost Optimizer - Reduces AI API costs
 * 
 * PHASE 7 - AI Cost Optimization
 * Implements batching, deduplication, and smart caching
 * 
 * @package DODO_AI_SEO
 * @since 4.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (class_exists('DODO_AI_Optimizer')) {
    return;
}

class DODO_AI_Optimizer {
    
    /**
     * Batch queue
     */
    private static $batch_queue = array();
    
    /**
     * Deduplication cache (in-memory for current request)
     */
    private static $dedup_cache = array();
    
    /**
     * Check if prompt is duplicate in current request
     * 
     * @param string $prompt Prompt to check
     * @return mixed|false Cached result or false
     */
    public static function check_duplicate($prompt) {
        $hash = md5($prompt);
        
        if (isset(self::$dedup_cache[$hash])) {
            error_log('[DODO AI Optimizer] Duplicate prompt detected - using in-memory cache');
            return self::$dedup_cache[$hash];
        }
        
        return false;
    }
    
    /**
     * Cache result for deduplication
     * 
     * @param string $prompt Prompt
     * @param mixed $result Result to cache
     */
    public static function cache_result($prompt, $result) {
        $hash = md5($prompt);
        self::$dedup_cache[$hash] = $result;
    }
    
    /**
     * Add to batch queue
     * 
     * @param string $prompt Prompt
     * @param string $feature Feature name
     * @param callable $callback Callback to execute with result
     */
    public static function add_to_batch($prompt, $feature, $callback) {
        self::$batch_queue[] = array(
            'prompt' => $prompt,
            'feature' => $feature,
            'callback' => $callback,
        );
    }
    
    /**
     * Process batch queue
     * 
     * @return int Number of processed items
     */
    public static function process_batch() {
        if (empty(self::$batch_queue)) {
            return 0;
        }
        
        $count = count(self::$batch_queue);
        error_log("[DODO AI Optimizer] Processing batch of {$count} items");
        
        // Group by feature
        $grouped = array();
        foreach (self::$batch_queue as $item) {
            $feature = $item['feature'];
            if (!isset($grouped[$feature])) {
                $grouped[$feature] = array();
            }
            $grouped[$feature][] = $item;
        }
        
        // Process each group
        foreach ($grouped as $feature => $items) {
            self::process_feature_batch($feature, $items);
        }
        
        // Clear queue
        self::$batch_queue = array();
        
        return $count;
    }
    
    /**
     * Process batch for specific feature
     * 
     * @param string $feature Feature name
     * @param array $items Batch items
     */
    private static function process_feature_batch($feature, $items) {
        // Combine prompts into single request
        $combined_prompt = '';
        foreach ($items as $index => $item) {
            $combined_prompt .= "Request {$index}: " . $item['prompt'] . "\n\n";
        }
        
        // Make single AI call
        if (class_exists('DODO_OpenAI')) {
            $openai = new DODO_OpenAI();
            $result = $openai->generate_content(
                'You are processing multiple requests. Respond to each numbered request separately.',
                $combined_prompt,
                $feature . '_batch'
            );
            
            // Parse and distribute results
            if (!is_wp_error($result)) {
                self::distribute_batch_results($items, $result);
            }
        }
    }
    
    /**
     * Distribute batch results to callbacks
     * 
     * @param array $items Batch items
     * @param string $result Combined result
     */
    private static function distribute_batch_results($items, $result) {
        // Simple distribution - split by request markers
        $parts = preg_split('/Request \d+:/', $result);
        array_shift($parts); // Remove empty first element
        
        foreach ($items as $index => $item) {
            $individual_result = isset($parts[$index]) ? trim($parts[$index]) : '';
            
            if (!empty($individual_result) && is_callable($item['callback'])) {
                call_user_func($item['callback'], $individual_result);
            }
        }
    }
    
    /**
     * Optimize prompt (reduce tokens)
     * 
     * @param string $prompt Original prompt
     * @return string Optimized prompt
     */
    public static function optimize_prompt($prompt) {
        // Remove excessive whitespace
        $prompt = preg_replace('/\s+/', ' ', $prompt);
        
        // Remove redundant phrases
        $redundant = array(
            'please ',
            'kindly ',
            'I would like you to ',
            'Can you ',
            'Could you ',
        );
        
        $prompt = str_ireplace($redundant, '', $prompt);
        
        return trim($prompt);
    }
    
    /**
     * Estimate token count (rough approximation)
     * 
     * @param string $text Text to estimate
     * @return int Estimated tokens
     */
    public static function estimate_tokens($text) {
        // Rough estimate: 1 token ≈ 4 characters
        return ceil(strlen($text) / 4);
    }
    
    /**
     * Check if request should use cache
     * 
     * @param string $feature Feature name
     * @return bool Should cache
     */
    public static function should_cache($feature) {
        // Features that should always cache
        $always_cache = array(
            'keyword_opportunities',
            'content_analysis',
            'seo_score',
            'semantic_similarity',
        );
        
        return in_array($feature, $always_cache);
    }
    
    /**
     * Get cache TTL for feature
     * 
     * @param string $feature Feature name
     * @return int TTL in seconds
     */
    public static function get_cache_ttl($feature) {
        $ttls = array(
            'keyword_opportunities' => DAY_IN_SECONDS,
            'content_analysis' => 6 * HOUR_IN_SECONDS,
            'seo_score' => 6 * HOUR_IN_SECONDS,
            'semantic_similarity' => WEEK_IN_SECONDS,
            'content_generation' => 0, // Don't cache
        );
        
        return $ttls[$feature] ?? HOUR_IN_SECONDS;
    }
    
    /**
     * Get cost statistics
     * 
     * @return array Cost stats
     */
    public static function get_cost_stats() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'dodo_ai_usage_logs';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) {
            return array(
                'total_requests' => 0,
                'cache_hits' => 0,
                'cache_hit_rate' => 0,
                'total_tokens' => 0,
                'estimated_cost' => 0,
                'saved_cost' => 0,
            );
        }
        
        $stats = $wpdb->get_row("
            SELECT 
                COUNT(*) as total_requests,
                SUM(CASE WHEN cache_hit = 1 THEN 1 ELSE 0 END) as cache_hits,
                SUM(tokens_input + tokens_output) as total_tokens
            FROM {$table}
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ", ARRAY_A);
        
        $total_requests = (int) $stats['total_requests'];
        $cache_hits = (int) $stats['cache_hits'];
        $total_tokens = (int) $stats['total_tokens'];
        
        // Estimate cost (GPT-4o: $2.50 per 1M input tokens, $10 per 1M output tokens)
        // Simplified: average $5 per 1M tokens
        $estimated_cost = ($total_tokens / 1000000) * 5;
        
        // Calculate saved cost from cache hits
        $avg_tokens_per_request = $total_requests > 0 ? $total_tokens / ($total_requests - $cache_hits) : 0;
        $saved_tokens = $cache_hits * $avg_tokens_per_request;
        $saved_cost = ($saved_tokens / 1000000) * 5;
        
        $cache_hit_rate = $total_requests > 0 ? ($cache_hits / $total_requests) * 100 : 0;
        
        return array(
            'total_requests' => $total_requests,
            'cache_hits' => $cache_hits,
            'cache_hit_rate' => round($cache_hit_rate, 2),
            'total_tokens' => $total_tokens,
            'estimated_cost' => round($estimated_cost, 2),
            'saved_cost' => round($saved_cost, 2),
        );
    }
}
