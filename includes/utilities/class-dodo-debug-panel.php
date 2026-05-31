<?php
/**
 * Debug Panel - Internal debugging and diagnostics
 * 
 * PHASE 7 - Internal Debug Tooling
 * Provides comprehensive debugging information for developers
 * 
 * @package DODO_AI_SEO
 * @since 4.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (class_exists('DODO_Debug_Panel')) {
    return;
}

class DODO_Debug_Panel {
    
    /**
     * Check if debug mode is enabled
     */
    public static function is_debug_enabled() {
        return defined('WP_DEBUG') && WP_DEBUG && current_user_can('manage_options');
    }
    
    /**
     * Get AI reasoning trace for a post
     * 
     * @param int $post_id Post ID
     * @return array Reasoning trace
     */
    public static function get_ai_reasoning_trace($post_id) {
        if (!self::is_debug_enabled()) {
            return array();
        }
        
        $trace = array(
            'post_id' => $post_id,
            'generation_meta' => self::get_generation_metadata($post_id),
            'brain_decisions' => self::get_brain_decisions($post_id),
            'ai_calls' => self::get_ai_call_history($post_id),
            'prompt_history' => self::get_prompt_history($post_id),
        );
        
        return $trace;
    }
    
    /**
     * Get generation metadata
     */
    private static function get_generation_metadata($post_id) {
        return array(
            'focus_keyword' => get_post_meta($post_id, 'dodo_focus_keyword', true),
            'generation_strategy' => get_post_meta($post_id, 'dodo_generation_strategy', true),
            'content_intent' => get_post_meta($post_id, 'dodo_content_intent', true),
            'expertise_depth' => get_post_meta($post_id, 'dodo_expertise_depth', true),
            'geo_optimization' => get_post_meta($post_id, 'dodo_geo_optimization', true),
            'generation_time' => get_post_meta($post_id, 'dodo_generation_time', true),
            'token_usage' => get_post_meta($post_id, 'dodo_token_usage', true),
            'ai_cost' => get_post_meta($post_id, 'dodo_ai_cost', true),
        );
    }
    
    /**
     * Get brain decisions
     */
    private static function get_brain_decisions($post_id) {
        $brain_log = get_post_meta($post_id, 'dodo_brain_decisions', true);
        
        if (empty($brain_log)) {
            return array();
        }
        
        return is_array($brain_log) ? $brain_log : json_decode($brain_log, true);
    }
    
    /**
     * Get AI call history
     */
    private static function get_ai_call_history($post_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'dodo_ai_usage_logs';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) {
            return array();
        }
        
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} 
                WHERE post_id = %d 
                ORDER BY created_at DESC 
                LIMIT 50",
                $post_id
            ),
            ARRAY_A
        );
        
        return $results ?: array();
    }
    
    /**
     * Get prompt history
     */
    private static function get_prompt_history($post_id) {
        $prompts = get_post_meta($post_id, 'dodo_prompt_history', true);
        
        if (empty($prompts)) {
            return array();
        }
        
        return is_array($prompts) ? $prompts : json_decode($prompts, true);
    }
    
    /**
     * Get opportunity score breakdown
     * 
     * @param int $opportunity_id Opportunity ID
     * @return array Score breakdown
     */
    public static function get_opportunity_score_breakdown($opportunity_id) {
        if (!self::is_debug_enabled()) {
            return array();
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'dodo_keyword_opportunities';
        
        $opportunity = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $opportunity_id),
            ARRAY_A
        );
        
        if (!$opportunity) {
            return array();
        }
        
        return array(
            'opportunity_id' => $opportunity_id,
            'keyword' => $opportunity['keyword'],
            'total_score' => $opportunity['score'],
            'components' => array(
                'search_volume' => array(
                    'value' => $opportunity['search_volume'],
                    'score' => self::calculate_volume_score($opportunity['search_volume']),
                    'weight' => 30,
                ),
                'difficulty' => array(
                    'value' => $opportunity['difficulty'],
                    'score' => self::calculate_difficulty_score($opportunity['difficulty']),
                    'weight' => 25,
                ),
                'relevance' => array(
                    'value' => $opportunity['relevance_score'],
                    'score' => $opportunity['relevance_score'],
                    'weight' => 20,
                ),
                'commercial_intent' => array(
                    'value' => $opportunity['commercial_intent'],
                    'score' => self::calculate_intent_score($opportunity['commercial_intent']),
                    'weight' => 15,
                ),
                'trend' => array(
                    'value' => $opportunity['trend'],
                    'score' => self::calculate_trend_score($opportunity['trend']),
                    'weight' => 10,
                ),
            ),
            'metadata' => array(
                'created_at' => $opportunity['created_at'],
                'status' => $opportunity['status'],
                'priority' => $opportunity['priority'],
            ),
        );
    }
    
    /**
     * Calculate volume score
     */
    private static function calculate_volume_score($volume) {
        if ($volume >= 10000) return 30;
        if ($volume >= 5000) return 25;
        if ($volume >= 1000) return 20;
        if ($volume >= 500) return 15;
        if ($volume >= 100) return 10;
        return 5;
    }
    
    /**
     * Calculate difficulty score
     */
    private static function calculate_difficulty_score($difficulty) {
        return max(0, 25 - ($difficulty * 0.25));
    }
    
    /**
     * Calculate intent score
     */
    private static function calculate_intent_score($intent) {
        $intent_scores = array(
            'transactional' => 15,
            'commercial' => 12,
            'informational' => 8,
            'navigational' => 5,
        );
        
        return $intent_scores[$intent] ?? 8;
    }
    
    /**
     * Calculate trend score
     */
    private static function calculate_trend_score($trend) {
        if ($trend === 'rising') return 10;
        if ($trend === 'stable') return 7;
        if ($trend === 'declining') return 3;
        return 5;
    }
    
    /**
     * Get learning evolution history
     * 
     * @param int $limit Number of events to retrieve
     * @return array Learning events
     */
    public static function get_learning_evolution($limit = 50) {
        if (!self::is_debug_enabled()) {
            return array();
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'dodo_learning_events';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) {
            return array();
        }
        
        $events = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} 
                ORDER BY created_at DESC 
                LIMIT %d",
                $limit
            ),
            ARRAY_A
        );
        
        return $events ?: array();
    }
    
    /**
     * Get cron execution history
     * 
     * @return array Cron history
     */
    public static function get_cron_history() {
        if (!self::is_debug_enabled()) {
            return array();
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'dodo_cron_logs';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) {
            return array();
        }
        
        $logs = $wpdb->get_results(
            "SELECT * FROM {$table} 
            ORDER BY started_at DESC 
            LIMIT 100",
            ARRAY_A
        );
        
        return $logs ?: array();
    }
    
    /**
     * Get queue processing history
     * 
     * @return array Queue history
     */
    public static function get_queue_history() {
        if (!self::is_debug_enabled()) {
            return array();
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'dodo_queue_jobs';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) {
            return array();
        }
        
        $jobs = $wpdb->get_results(
            "SELECT * FROM {$table} 
            ORDER BY created_at DESC 
            LIMIT 100",
            ARRAY_A
        );
        
        return $jobs ?: array();
    }
    
    /**
     * Get API usage statistics
     * 
     * @param string $period Period: today, week, month, all
     * @return array API usage stats
     */
    public static function get_api_usage_stats($period = 'today') {
        if (!self::is_debug_enabled()) {
            return array();
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'dodo_ai_usage_logs';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) {
            return array();
        }
        
        $where = '';
        switch ($period) {
            case 'today':
                $where = "WHERE DATE(created_at) = CURDATE()";
                break;
            case 'week':
                $where = "WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case 'month':
                $where = "WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
        }
        
        $stats = $wpdb->get_row(
            "SELECT 
                COUNT(*) as total_calls,
                SUM(tokens_used) as total_tokens,
                SUM(cost) as total_cost,
                AVG(response_time) as avg_response_time,
                MAX(response_time) as max_response_time
            FROM {$table} 
            {$where}",
            ARRAY_A
        );
        
        return $stats ?: array();
    }
    
    /**
     * Get cache hit/miss statistics
     * 
     * @return array Cache stats
     */
    public static function get_cache_stats() {
        if (!self::is_debug_enabled()) {
            return array();
        }
        
        if (class_exists('DODO_Cache_Manager')) {
            return DODO_Cache_Manager::get_stats();
        }
        
        return array();
    }
    
    /**
     * Get failed jobs
     * 
     * @return array Failed jobs
     */
    public static function get_failed_jobs() {
        if (!self::is_debug_enabled()) {
            return array();
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'dodo_queue_jobs';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) {
            return array();
        }
        
        $failed = $wpdb->get_results(
            "SELECT * FROM {$table} 
            WHERE status = 'failed' 
            ORDER BY updated_at DESC 
            LIMIT 50",
            ARRAY_A
        );
        
        return $failed ?: array();
    }
    
    /**
     * Get system diagnostics
     * 
     * @return array System diagnostics
     */
    public static function get_system_diagnostics() {
        if (!self::is_debug_enabled()) {
            return array();
        }
        
        return array(
            'php_version' => PHP_VERSION,
            'wp_version' => get_bloginfo('version'),
            'plugin_version' => DODO_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'memory_usage' => round(memory_get_usage() / 1024 / 1024, 2) . ' MB',
            'memory_peak' => round(memory_get_peak_usage() / 1024 / 1024, 2) . ' MB',
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'loaded_extensions' => get_loaded_extensions(),
            'active_plugins' => get_option('active_plugins'),
            'theme' => wp_get_theme()->get('Name'),
        );
    }
    
    /**
     * Export debug data
     * 
     * @param int $post_id Optional post ID
     * @return array Complete debug export
     */
    public static function export_debug_data($post_id = null) {
        if (!self::is_debug_enabled()) {
            return array();
        }
        
        $export = array(
            'timestamp' => current_time('mysql'),
            'system' => self::get_system_diagnostics(),
            'api_usage' => self::get_api_usage_stats('month'),
            'cache_stats' => self::get_cache_stats(),
            'failed_jobs' => self::get_failed_jobs(),
            'cron_history' => self::get_cron_history(),
            'learning_evolution' => self::get_learning_evolution(20),
        );
        
        if ($post_id) {
            $export['post_trace'] = self::get_ai_reasoning_trace($post_id);
        }
        
        return $export;
    }
    
    /**
     * Render debug panel HTML
     * 
     * @param int $post_id Optional post ID
     */
    public static function render_debug_panel($post_id = null) {
        if (!self::is_debug_enabled()) {
            echo '<p>Debug mode is not enabled.</p>';
            return;
        }
        
        $data = self::export_debug_data($post_id);
        
        ?>
        <div class="dodo-debug-panel">
            <h2>🔍 DODO Debug Panel</h2>
            
            <div class="debug-section">
                <h3>System Diagnostics</h3>
                <pre><?php echo esc_html(json_encode($data['system'], JSON_PRETTY_PRINT)); ?></pre>
            </div>
            
            <div class="debug-section">
                <h3>API Usage (Last 30 Days)</h3>
                <pre><?php echo esc_html(json_encode($data['api_usage'], JSON_PRETTY_PRINT)); ?></pre>
            </div>
            
            <div class="debug-section">
                <h3>Cache Statistics</h3>
                <pre><?php echo esc_html(json_encode($data['cache_stats'], JSON_PRETTY_PRINT)); ?></pre>
            </div>
            
            <?php if ($post_id && !empty($data['post_trace'])): ?>
            <div class="debug-section">
                <h3>AI Reasoning Trace (Post #<?php echo esc_html($post_id); ?>)</h3>
                <pre><?php echo esc_html(json_encode($data['post_trace'], JSON_PRETTY_PRINT)); ?></pre>
            </div>
            <?php endif; ?>
            
            <div class="debug-section">
                <h3>Failed Jobs</h3>
                <pre><?php echo esc_html(json_encode($data['failed_jobs'], JSON_PRETTY_PRINT)); ?></pre>
            </div>
            
            <div class="debug-section">
                <h3>Learning Evolution (Last 20 Events)</h3>
                <pre><?php echo esc_html(json_encode($data['learning_evolution'], JSON_PRETTY_PRINT)); ?></pre>
            </div>
        </div>
        
        <style>
        .dodo-debug-panel {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
        }
        .dodo-debug-panel h2 {
            color: #4ec9b0;
            margin-top: 0;
        }
        .debug-section {
            margin: 20px 0;
            padding: 15px;
            background: #252526;
            border-radius: 4px;
            border-left: 3px solid #007acc;
        }
        .debug-section h3 {
            color: #dcdcaa;
            margin-top: 0;
        }
        .debug-section pre {
            background: #1e1e1e;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            color: #ce9178;
            font-size: 12px;
            line-height: 1.5;
        }
        </style>
        <?php
    }
}
