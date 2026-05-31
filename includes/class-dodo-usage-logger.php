<?php
/**
 * AI Usage Logger
 * 
 * Tüm OpenAI API çağrılarını loglar ve token kullanımını takip eder
 * 
 * @package DODO_AI_SEO
 * @since 1.2.0
 */

// Doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

class DODO_Usage_Logger {
    
    /**
     * Database table name
     */
    private $table_name;
    
    /**
     * Pricing per 1M tokens (USD) - GPT-4o pricing
     */
    private const PRICING = array(
        'gpt-4o' => array(
            'input' => 2.50,   // $2.50 per 1M input tokens
            'output' => 10.00, // $10.00 per 1M output tokens
        ),
        'gpt-4o-mini' => array(
            'input' => 0.150,  // $0.15 per 1M input tokens
            'output' => 0.600, // $0.60 per 1M output tokens
        ),
        'gpt-4-turbo' => array(
            'input' => 10.00,  // $10.00 per 1M input tokens
            'output' => 30.00, // $30.00 per 1M output tokens
        ),
        'gpt-4' => array(
            'input' => 30.00,  // $30.00 per 1M input tokens
            'output' => 60.00, // $60.00 per 1M output tokens
        ),
    );
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'dodo_ai_usage_logs';
    }
    
    /**
     * Create database table
     */
    public function create_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$this->table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            feature_name varchar(50) NOT NULL,
            model varchar(50) NOT NULL,
            tokens_input int(11) NOT NULL DEFAULT 0,
            tokens_output int(11) NOT NULL DEFAULT 0,
            tokens_total int(11) NOT NULL DEFAULT 0,
            estimated_cost decimal(10,6) NOT NULL DEFAULT 0,
            execution_time int(11) NOT NULL DEFAULT 0,
            cache_hit tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY feature_name (feature_name),
            KEY model (model),
            KEY cache_hit (cache_hit),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        error_log('[DODO USAGE LOGGER] Table created/updated: ' . $this->table_name);
    }
    
    /**
     * Log AI usage
     * 
     * @param string $feature_name Feature adı (keyword_generation, faq, etc.)
     * @param string $model Model adı (gpt-4o, gpt-4-turbo, etc.)
     * @param int $tokens_input Input tokens
     * @param int $tokens_output Output tokens
     * @param int $execution_time Execution time (milliseconds)
     * @param bool $cache_hit Cache hit mi?
     * @return int|false Insert ID veya false
     */
    public function log_usage($feature_name, $model, $tokens_input, $tokens_output, $execution_time = 0, $cache_hit = false) {
        global $wpdb;
        
        // Total tokens
        $tokens_total = $tokens_input + $tokens_output;
        
        // Estimated cost hesapla
        $estimated_cost = $this->calculate_cost($model, $tokens_input, $tokens_output);
        
        // Debug log before insert (sadece WP_DEBUG modda)
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[DODO USAGE LOGGER] Insert - Feature: ' . $feature_name . ', Tokens: ' . $tokens_total);
        }
        
        // HARDENED: Sprint A - Table existence check
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'") === $this->table_name;
        if (!$table_exists) {
            error_log('[DODO][DATABASE] Usage logger table missing, attempting auto-repair');
            $this->create_table();
            // Recheck
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'") === $this->table_name;
            if (!$table_exists) {
                error_log('[DODO][DATABASE] Usage logger table creation failed');
                return false;
            }
        }
        
        // Insert
        $result = $wpdb->insert(
            $this->table_name,
            array(
                'feature_name' => $feature_name,
                'model' => $model,
                'tokens_input' => (int) $tokens_input,
                'tokens_output' => (int) $tokens_output,
                'tokens_total' => (int) $tokens_total,
                'estimated_cost' => (float) $estimated_cost,
                'execution_time' => (int) $execution_time,
                'cache_hit' => $cache_hit ? 1 : 0,
                'created_at' => current_time('mysql'),
            ),
            array('%s', '%s', '%d', '%d', '%d', '%f', '%d', '%d', '%s')
        );
        
        if ($result === false) {
            // HARDENED: Sprint A - Enhanced error logging
            if (class_exists('DODO_Error_Handler')) {
                DODO_Error_Handler::log_error_with_context('DATABASE', 'Usage logger insert failed', array(
                    'table' => $this->table_name,
                    'error' => $wpdb->last_error,
                    'feature' => $feature_name,
                    'model' => $model
                ));
            } else {
                error_log('[DODO][DATABASE] Usage logger insert failed: ' . $wpdb->last_error);
            }
            
            // Detaylı debug (sadece WP_DEBUG modda)
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[DODO USAGE LOGGER] WPDB Query: ' . $wpdb->last_query);
                error_log('[DODO USAGE LOGGER] Table exists: ' . ($wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'") === $this->table_name ? 'YES' : 'NO'));
            }
            return false;
        }
        
        $insert_id = $wpdb->insert_id;
        
        // Debug log
        $this->log_event('usage_logged', array(
            'id' => $insert_id,
            'feature' => $feature_name,
            'model' => $model,
            'tokens' => $tokens_total,
            'cost' => $estimated_cost,
            'cache_hit' => $cache_hit,
        ));
        
        return $insert_id;
    }
    
    /**
     * Calculate estimated cost
     * 
     * @param string $model Model adı
     * @param int $tokens_input Input tokens
     * @param int $tokens_output Output tokens
     * @return float Cost in USD
     */
    public function calculate_cost($model, $tokens_input, $tokens_output) {
        // Model pricing al
        $pricing = self::PRICING[$model] ?? self::PRICING['gpt-4o'];
        
        // Cost hesapla (per 1M tokens)
        $input_cost = ($tokens_input / 1000000) * $pricing['input'];
        $output_cost = ($tokens_output / 1000000) * $pricing['output'];
        
        return $input_cost + $output_cost;
    }
    
    /**
     * Get usage statistics
     * 
     * @param string $period Period (today, week, month, all)
     * @return array Stats
     */
    public function get_usage_stats($period = 'all') {
        global $wpdb;
        
        // Date filter
        $date_filter = $this->get_date_filter($period);
        
        // Query
        $stats = $wpdb->get_row(
            "SELECT 
                COUNT(*) as total_requests,
                SUM(tokens_input) as total_input_tokens,
                SUM(tokens_output) as total_output_tokens,
                SUM(tokens_total) as total_tokens,
                SUM(estimated_cost) as total_cost,
                AVG(execution_time) as avg_execution_time,
                SUM(cache_hit) as cache_hits,
                COUNT(*) - SUM(cache_hit) as cache_misses
            FROM {$this->table_name}
            {$date_filter}",
            ARRAY_A
        );
        
        // Null check
        if (empty($stats)) {
            return $this->get_empty_stats();
        }
        
        // Cache hit rate
        $total_requests = (int) $stats['total_requests'];
        $cache_hits = (int) $stats['cache_hits'];
        
        $stats['cache_hit_rate'] = $total_requests > 0 
            ? round(($cache_hits / $total_requests) * 100, 2) 
            : 0;
        
        // Format
        $stats['total_requests'] = (int) $stats['total_requests'];
        $stats['total_input_tokens'] = (int) $stats['total_input_tokens'];
        $stats['total_output_tokens'] = (int) $stats['total_output_tokens'];
        $stats['total_tokens'] = (int) $stats['total_tokens'];
        $stats['total_cost'] = (float) $stats['total_cost'];
        $stats['avg_execution_time'] = (int) $stats['avg_execution_time'];
        $stats['cache_hits'] = (int) $stats['cache_hits'];
        $stats['cache_misses'] = (int) $stats['cache_misses'];
        
        return $stats;
    }
    
    /**
     * Get usage by feature
     * 
     * @param string $period Period (today, week, month, all)
     * @return array Feature stats
     */
    public function get_usage_by_feature($period = 'all') {
        global $wpdb;
        
        $date_filter = $this->get_date_filter($period);
        
        $results = $wpdb->get_results(
            "SELECT 
                feature_name,
                COUNT(*) as requests,
                SUM(tokens_total) as total_tokens,
                SUM(estimated_cost) as total_cost,
                AVG(execution_time) as avg_time
            FROM {$this->table_name}
            {$date_filter}
            GROUP BY feature_name
            ORDER BY total_cost DESC",
            ARRAY_A
        );
        
        return $results ?: array();
    }
    
    /**
     * Get usage by model
     * 
     * @param string $period Period (today, week, month, all)
     * @return array Model stats
     */
    public function get_usage_by_model($period = 'all') {
        global $wpdb;
        
        $date_filter = $this->get_date_filter($period);
        
        $results = $wpdb->get_results(
            "SELECT 
                model,
                COUNT(*) as requests,
                SUM(tokens_total) as total_tokens,
                SUM(estimated_cost) as total_cost
            FROM {$this->table_name}
            {$date_filter}
            GROUP BY model
            ORDER BY total_cost DESC",
            ARRAY_A
        );
        
        return $results ?: array();
    }
    
    /**
     * Get recent logs
     * 
     * @param int $limit Limit
     * @return array Recent logs
     */
    public function get_recent_logs($limit = 20) {
        global $wpdb;
        
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} 
                ORDER BY created_at DESC 
                LIMIT %d",
                $limit
            ),
            ARRAY_A
        );
        
        return $results ?: array();
    }
    
    /**
     * Get date filter SQL
     * 
     * @param string $period Period
     * @return string SQL WHERE clause
     */
    private function get_date_filter($period) {
        switch ($period) {
            case 'today':
                return "WHERE DATE(created_at) = CURDATE()";
            
            case 'week':
                return "WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            
            case 'month':
                return "WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            
            case 'all':
            default:
                return "";
        }
    }
    
    /**
     * Get empty stats
     * 
     * @return array Empty stats
     */
    private function get_empty_stats() {
        return array(
            'total_requests' => 0,
            'total_input_tokens' => 0,
            'total_output_tokens' => 0,
            'total_tokens' => 0,
            'total_cost' => 0.0,
            'avg_execution_time' => 0,
            'cache_hits' => 0,
            'cache_misses' => 0,
            'cache_hit_rate' => 0,
        );
    }
    
    /**
     * Clear old logs
     * 
     * @param int $days Days to keep
     * @return int Number of deleted rows
     */
    public function clear_old_logs($days = 90) {
        global $wpdb;
        
        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$this->table_name} 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
                $days
            )
        );
        
        $this->log_event('old_logs_cleared', array('deleted' => $deleted, 'days' => $days));
        
        return $deleted;
    }
    
    /**
     * Clear all logs
     * 
     * @return int Number of deleted rows
     */
    public function clear_all_logs() {
        global $wpdb;
        
        $deleted = $wpdb->query("TRUNCATE TABLE {$this->table_name}");
        
        $this->log_event('all_logs_cleared', array('deleted' => $deleted));
        
        return $deleted;
    }
    
    /**
     * Get table size
     * 
     * @return array Table size info
     */
    public function get_table_size() {
        global $wpdb;
        
        $size = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT 
                    COUNT(*) as row_count,
                    ROUND(((data_length + index_length) / 1024 / 1024), 2) as size_mb
                FROM information_schema.TABLES 
                WHERE table_schema = %s 
                AND table_name = %s",
                DB_NAME,
                $this->table_name
            ),
            ARRAY_A
        );
        
        return $size ?: array('row_count' => 0, 'size_mb' => 0);
    }
    
    /**
     * Log event (debug)
     * 
     * @param string $event_type Event type
     * @param array $data Additional data
     */
    private function log_event($event_type, $data = array()) {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }
        
        $log_message = sprintf(
            '[DODO USAGE LOGGER] %s',
            strtoupper($event_type)
        );
        
        if (!empty($data)) {
            $log_message .= ' - Data: ' . json_encode($data);
        }
        
        error_log($log_message);
    }
}
