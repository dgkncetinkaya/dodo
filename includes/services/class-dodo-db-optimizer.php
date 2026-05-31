<?php
/**
 * Database Optimizer - Database performance optimization
 * 
 * PHASE 7 - Database Optimization
 * Handles cleanup, indexing, and query optimization
 * 
 * @package DODO_AI_SEO
 * @since 4.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (class_exists('DODO_DB_Optimizer')) {
    return;
}

class DODO_DB_Optimizer {
    
    /**
     * Cleanup old data (retention policy)
     */
    public static function cleanup_old_data() {
        global $wpdb;
        
        $deleted = array();
        
        // 1. Cleanup old queue jobs (completed > 30 days)
        $queue_table = $wpdb->prefix . 'dodo_queue_jobs';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$queue_table}'") === $queue_table) {
            $count = $wpdb->query(
                "DELETE FROM {$queue_table} 
                WHERE status = 'completed' 
                AND completed_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"
            );
            $deleted['queue_jobs'] = $count;
        }
        
        // 2. Cleanup old telemetry (> 90 days)
        $telemetry_table = $wpdb->prefix . 'dodo_telemetry';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$telemetry_table}'") === $telemetry_table) {
            $count = $wpdb->query(
                "DELETE FROM {$telemetry_table} 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)"
            );
            $deleted['telemetry'] = $count;
        }
        
        // 3. Cleanup old snapshots (> 90 days)
        $snapshot_table = $wpdb->prefix . 'dodo_strategy_snapshots';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$snapshot_table}'") === $snapshot_table) {
            $count = $wpdb->query(
                "DELETE FROM {$snapshot_table} 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)"
            );
            $deleted['snapshots'] = $count;
        }
        
        // 4. Cleanup old semantic cache (> 7 days)
        $cache_table = $wpdb->prefix . 'dodo_semantic_cache';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$cache_table}'") === $cache_table) {
            $count = $wpdb->query(
                "DELETE FROM {$cache_table} 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)"
            );
            $deleted['semantic_cache'] = $count;
        }
        
        // 5. Cleanup old debug logs (> 7 days)
        $debug_table = $wpdb->prefix . 'dodo_debug_logs';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$debug_table}'") === $debug_table) {
            $count = $wpdb->query(
                "DELETE FROM {$debug_table} 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)"
            );
            $deleted['debug_logs'] = $count;
        }
        
        return $deleted;
    }
    
    /**
     * Optimize tables
     */
    public static function optimize_tables() {
        global $wpdb;
        
        $tables = array(
            'dodo_queue_jobs',
            'dodo_telemetry',
            'dodo_strategy_snapshots',
            'dodo_learning_events',
            'dodo_semantic_cache',
            'dodo_ai_usage_logs',
            'dodo_keyword_opportunities',
        );
        
        $optimized = array();
        
        foreach ($tables as $table) {
            $full_table = $wpdb->prefix . $table;
            
            if ($wpdb->get_var("SHOW TABLES LIKE '{$full_table}'") === $full_table) {
                $wpdb->query("OPTIMIZE TABLE {$full_table}");
                $optimized[] = $table;
            }
        }
        
        return $optimized;
    }
    
    /**
     * Get table sizes
     */
    public static function get_table_sizes() {
        global $wpdb;
        
        $tables = array(
            'dodo_queue_jobs',
            'dodo_telemetry',
            'dodo_strategy_snapshots',
            'dodo_learning_events',
            'dodo_semantic_cache',
            'dodo_ai_usage_logs',
            'dodo_keyword_opportunities',
            'dodo_ai_revisions',
            'dodo_content_audit',
        );
        
        $sizes = array();
        
        foreach ($tables as $table) {
            $full_table = $wpdb->prefix . $table;
            
            if ($wpdb->get_var("SHOW TABLES LIKE '{$full_table}'") === $full_table) {
                $result = $wpdb->get_row(
                    "SELECT 
                        COUNT(*) as row_count,
                        ROUND(((data_length + index_length) / 1024 / 1024), 2) as size_mb
                    FROM information_schema.TABLES 
                    WHERE table_schema = DATABASE() 
                    AND table_name = '{$full_table}'"
                );
                
                $sizes[$table] = array(
                    'rows' => (int) $result->row_count,
                    'size_mb' => (float) $result->size_mb,
                );
            }
        }
        
        return $sizes;
    }
    
    /**
     * Check for missing indexes
     */
    public static function check_missing_indexes() {
        global $wpdb;
        
        $recommendations = array();
        
        // Check queue_jobs for target_post_id index
        $queue_table = $wpdb->prefix . 'dodo_queue_jobs';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$queue_table}'") === $queue_table) {
            $indexes = $wpdb->get_results("SHOW INDEX FROM {$queue_table}");
            $has_target_index = false;
            
            foreach ($indexes as $index) {
                if ($index->Column_name === 'target_post_id') {
                    $has_target_index = true;
                    break;
                }
            }
            
            if (!$has_target_index) {
                $recommendations[] = array(
                    'table' => 'dodo_queue_jobs',
                    'column' => 'target_post_id',
                    'reason' => 'Frequently queried for job lookup by post',
                    'sql' => "ALTER TABLE {$queue_table} ADD INDEX target_post_id (target_post_id)",
                );
            }
        }
        
        // Check keyword_opportunities for source index
        $opp_table = $wpdb->prefix . 'dodo_keyword_opportunities';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$opp_table}'") === $opp_table) {
            $indexes = $wpdb->get_results("SHOW INDEX FROM {$opp_table}");
            $has_source_index = false;
            
            foreach ($indexes as $index) {
                if ($index->Column_name === 'source') {
                    $has_source_index = true;
                    break;
                }
            }
            
            if (!$has_source_index) {
                $recommendations[] = array(
                    'table' => 'dodo_keyword_opportunities',
                    'column' => 'source',
                    'reason' => 'Frequently filtered by source type',
                    'sql' => "ALTER TABLE {$opp_table} ADD INDEX source (source)",
                );
            }
        }
        
        return $recommendations;
    }
    
    /**
     * Apply missing indexes
     */
    public static function apply_missing_indexes() {
        $recommendations = self::check_missing_indexes();
        $applied = array();
        
        global $wpdb;
        
        foreach ($recommendations as $rec) {
            $result = $wpdb->query($rec['sql']);
            
            if ($result !== false) {
                $applied[] = $rec['table'] . '.' . $rec['column'];
            }
        }
        
        return $applied;
    }
    
    /**
     * Get slow queries (if query log is enabled)
     */
    public static function get_slow_queries() {
        global $wpdb;
        
        // This requires MySQL slow query log to be enabled
        // Return placeholder for now
        return array(
            'note' => 'Enable MySQL slow query log to track slow queries',
            'queries' => array(),
        );
    }
    
    /**
     * Get database statistics
     */
    public static function get_stats() {
        $sizes = self::get_table_sizes();
        
        $total_rows = 0;
        $total_size = 0;
        
        foreach ($sizes as $table => $data) {
            $total_rows += $data['rows'];
            $total_size += $data['size_mb'];
        }
        
        return array(
            'total_tables' => count($sizes),
            'total_rows' => $total_rows,
            'total_size_mb' => round($total_size, 2),
            'tables' => $sizes,
            'missing_indexes' => count(self::check_missing_indexes()),
        );
    }
    
    /**
     * Prune duplicate tracking rows
     */
    public static function prune_duplicates() {
        global $wpdb;
        
        $pruned = array();
        
        // Prune duplicate learning events (keep latest)
        $learning_table = $wpdb->prefix . 'dodo_learning_events';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$learning_table}'") === $learning_table) {
            $count = $wpdb->query("
                DELETE t1 FROM {$learning_table} t1
                INNER JOIN {$learning_table} t2 
                WHERE t1.id < t2.id 
                AND t1.event_type = t2.event_type 
                AND t1.created_at = t2.created_at
            ");
            $pruned['learning_events'] = $count;
        }
        
        return $pruned;
    }
}
