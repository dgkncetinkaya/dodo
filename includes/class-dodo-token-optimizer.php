<?php
/**
 * AI Token & Cost Optimization
 * 
 * @package DODO_AI_SEO
 * @since 2.0.0 (Sprint 4 - Task 4)
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Token_Optimizer {
    
    // GPT-4 pricing (per 1K tokens)
    const GPT4_INPUT_COST = 0.03;
    const GPT4_OUTPUT_COST = 0.06;
    
    // GPT-3.5 pricing (per 1K tokens)
    const GPT35_INPUT_COST = 0.0015;
    const GPT35_OUTPUT_COST = 0.002;
    
    /**
     * Estimate token count
     */
    public static function estimate_tokens($text) {
        // Rough estimation: 1 token ≈ 4 characters
        return ceil(strlen($text) / 4);
    }
    
    /**
     * Estimate cost for request
     */
    public static function estimate_cost($input_text, $expected_output_tokens = 1000, $model = 'gpt-4') {
        $input_tokens = self::estimate_tokens($input_text);
        
        if ($model === 'gpt-4') {
            $input_cost = ($input_tokens / 1000) * self::GPT4_INPUT_COST;
            $output_cost = ($expected_output_tokens / 1000) * self::GPT4_OUTPUT_COST;
        } else {
            $input_cost = ($input_tokens / 1000) * self::GPT35_INPUT_COST;
            $output_cost = ($expected_output_tokens / 1000) * self::GPT35_OUTPUT_COST;
        }
        
        return array(
            'input_tokens' => $input_tokens,
            'output_tokens' => $expected_output_tokens,
            'total_tokens' => $input_tokens + $expected_output_tokens,
            'input_cost' => $input_cost,
            'output_cost' => $output_cost,
            'total_cost' => $input_cost + $output_cost,
            'model' => $model,
        );
    }
    
    /**
     * Compress prompt
     */
    public static function compress_prompt($prompt) {
        // Remove extra whitespace
        $compressed = preg_replace('/\s+/', ' ', $prompt);
        
        // Remove unnecessary punctuation
        $compressed = preg_replace('/[,;:]+\s/', ' ', $compressed);
        
        // Trim
        $compressed = trim($compressed);
        
        return $compressed;
    }
    
    /**
     * Check if should use cache
     */
    public static function should_use_cache($content_hash) {
        $cache = new DODO_AI_Cache();
        return $cache->get($content_hash) !== false;
    }
    
    /**
     * Select optimal model
     */
    public static function select_model($task_type, $content_length) {
        // Use GPT-3.5 for simple tasks
        $simple_tasks = array('outline', 'title', 'meta_description', 'excerpt');
        
        if (in_array($task_type, $simple_tasks)) {
            return 'gpt-3.5-turbo';
        }
        
        // Use GPT-3.5 for short content
        if ($content_length < 2000) {
            return 'gpt-3.5-turbo';
        }
        
        // Use GPT-4 for complex tasks
        return 'gpt-4';
    }
    
    /**
     * Chunk large content
     */
    public static function chunk_content($content, $max_tokens = 3000) {
        $tokens = self::estimate_tokens($content);
        
        if ($tokens <= $max_tokens) {
            return array($content);
        }
        
        // Split by paragraphs
        $paragraphs = explode("\n\n", $content);
        $chunks = array();
        $current_chunk = '';
        
        foreach ($paragraphs as $paragraph) {
            $test_chunk = $current_chunk . "\n\n" . $paragraph;
            
            if (self::estimate_tokens($test_chunk) > $max_tokens) {
                if (!empty($current_chunk)) {
                    $chunks[] = trim($current_chunk);
                }
                $current_chunk = $paragraph;
            } else {
                $current_chunk = $test_chunk;
            }
        }
        
        if (!empty($current_chunk)) {
            $chunks[] = trim($current_chunk);
        }
        
        return $chunks;
    }
    
    /**
     * Get usage stats
     */
    public static function get_usage_stats($period = 'today') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dodo_ai_usage_logs';
        
        switch ($period) {
            case 'today':
                $start_date = date('Y-m-d 00:00:00');
                break;
            case 'week':
                $start_date = date('Y-m-d 00:00:00', strtotime('-7 days'));
                break;
            case 'month':
                $start_date = date('Y-m-d 00:00:00', strtotime('-30 days'));
                break;
            default:
                $start_date = date('Y-m-d 00:00:00');
        }
        
        $stats = $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(*) as request_count,
                SUM(tokens_used) as total_tokens,
                SUM(cost) as total_cost,
                AVG(tokens_used) as avg_tokens,
                AVG(cost) as avg_cost
            FROM {$table_name}
            WHERE created_at >= %s
        ", $start_date), ARRAY_A);
        
        return $stats;
    }
    
    /**
     * Check if within budget
     */
    public static function check_budget($estimated_cost) {
        $settings = get_option('dodo_ai_seo_settings', array());
        $daily_limit = isset($settings['daily_cost_limit']) ? floatval($settings['daily_cost_limit']) : 10.0;
        
        $today_stats = self::get_usage_stats('today');
        $today_cost = floatval($today_stats['total_cost'] ?? 0);
        
        $remaining = $daily_limit - $today_cost;
        
        return array(
            'within_budget' => $estimated_cost <= $remaining,
            'daily_limit' => $daily_limit,
            'used_today' => $today_cost,
            'remaining' => $remaining,
            'estimated_cost' => $estimated_cost,
        );
    }
    
    /**
     * Get cache hit rate
     */
    public static function get_cache_stats() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dodo_ai_usage_logs';
        
        $stats = $wpdb->get_row("
            SELECT 
                COUNT(*) as total_requests,
                SUM(CASE WHEN cache_hit = 1 THEN 1 ELSE 0 END) as cache_hits,
                SUM(CASE WHEN cache_hit = 0 THEN 1 ELSE 0 END) as cache_misses
            FROM {$table_name}
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ", ARRAY_A);
        
        $total = intval($stats['total_requests'] ?? 0);
        $hits = intval($stats['cache_hits'] ?? 0);
        
        $hit_rate = $total > 0 ? round(($hits / $total) * 100, 2) : 0;
        
        return array(
            'total_requests' => $total,
            'cache_hits' => $hits,
            'cache_misses' => intval($stats['cache_misses'] ?? 0),
            'hit_rate' => $hit_rate,
        );
    }
    
    /**
     * Prevent duplicate requests
     */
    public static function is_duplicate_request($content_hash, $window_seconds = 60) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dodo_ai_usage_logs';
        $cutoff = date('Y-m-d H:i:s', time() - $window_seconds);
        
        $count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM {$table_name} 
            WHERE content_hash = %s 
            AND created_at >= %s
        ", $content_hash, $cutoff));
        
        return $count > 0;
    }
}
