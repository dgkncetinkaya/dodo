<?php
/**
 * Strategy Snapshot System
 * 
 * Captures, compares, and rolls back strategy snapshots
 * 
 * @package DODO_AI_SEO
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Strategy_Snapshots {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'dodo_strategy_snapshots';
    }
    
    /**
     * Create snapshot
     */
    public function create_snapshot($strategy, $label = '', $metadata = []) {
        global $wpdb;
        
        $snapshot_id = wp_generate_uuid4();
        
        $wpdb->insert(
            $this->table_name,
            [
                'snapshot_id' => $snapshot_id,
                'label' => $label,
                'strategy_data' => json_encode($strategy),
                'metadata' => json_encode($metadata),
                'created_at' => current_time('mysql'),
            ],
            ['%s', '%s', '%s', '%s', '%s']
        );
        
        error_log("[DODO Snapshots] Created snapshot: {$snapshot_id} - {$label}");
        
        return $snapshot_id;
    }
    
    /**
     * Get snapshot
     */
    public function get_snapshot($snapshot_id) {
        global $wpdb;
        
        $snapshot = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE snapshot_id = %s",
            $snapshot_id
        ));
        
        if (!$snapshot) {
            return null;
        }
        
        return [
            'snapshot_id' => $snapshot->snapshot_id,
            'label' => $snapshot->label,
            'strategy' => json_decode($snapshot->strategy_data, true),
            'metadata' => json_decode($snapshot->metadata, true),
            'created_at' => $snapshot->created_at,
        ];
    }
    
    /**
     * List snapshots
     */
    public function list_snapshots($limit = 50) {
        global $wpdb;
        
        $snapshots = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
            ORDER BY created_at DESC 
            LIMIT %d",
            $limit
        ));
        
        $result = [];
        
        foreach ($snapshots as $snapshot) {
            $result[] = [
                'snapshot_id' => $snapshot->snapshot_id,
                'label' => $snapshot->label,
                'strategy' => json_decode($snapshot->strategy_data, true),
                'metadata' => json_decode($snapshot->metadata, true),
                'created_at' => $snapshot->created_at,
            ];
        }
        
        return $result;
    }
    
    /**
     * Compare snapshots
     */
    public function compare_snapshots($snapshot_id_1, $snapshot_id_2) {
        $snapshot1 = $this->get_snapshot($snapshot_id_1);
        $snapshot2 = $this->get_snapshot($snapshot_id_2);
        
        if (!$snapshot1 || !$snapshot2) {
            return null;
        }
        
        $strategy1 = $snapshot1['strategy'];
        $strategy2 = $snapshot2['strategy'];
        
        $diff = [];
        
        // Compare all keys
        $all_keys = array_unique(array_merge(array_keys($strategy1), array_keys($strategy2)));
        
        foreach ($all_keys as $key) {
            $val1 = $strategy1[$key] ?? null;
            $val2 = $strategy2[$key] ?? null;
            
            if ($val1 !== $val2) {
                $diff[$key] = [
                    'before' => $val1,
                    'after' => $val2,
                    'changed' => true,
                ];
                
                // Calculate change percentage for numeric values
                if (is_numeric($val1) && is_numeric($val2) && $val1 > 0) {
                    $diff[$key]['change_pct'] = round((($val2 - $val1) / $val1) * 100, 1);
                }
            }
        }
        
        return [
            'snapshot1' => $snapshot1,
            'snapshot2' => $snapshot2,
            'diff' => $diff,
            'total_changes' => count($diff),
        ];
    }
    
    /**
     * Rollback to snapshot
     */
    public function rollback_to_snapshot($snapshot_id) {
        $snapshot = $this->get_snapshot($snapshot_id);
        
        if (!$snapshot) {
            return false;
        }
        
        $strategy = $snapshot['strategy'];
        
        // Create backup of current state before rollback
        $current_strategy = $this->get_current_strategy();
        $backup_id = $this->create_snapshot(
            $current_strategy,
            'Auto-backup before rollback',
            ['rollback_from' => $snapshot_id]
        );
        
        // Apply snapshot strategy
        $this->apply_strategy($strategy);
        
        error_log("[DODO Snapshots] Rolled back to snapshot: {$snapshot_id}");
        error_log("[DODO Snapshots] Backup created: {$backup_id}");
        
        return [
            'success' => true,
            'snapshot_id' => $snapshot_id,
            'backup_id' => $backup_id,
        ];
    }
    
    /**
     * Get current strategy
     */
    private function get_current_strategy() {
        // Get current strategy from options
        $strategy = [];
        
        $keys = [
            'faq_count',
            'target_words',
            'semantic_aggressiveness',
            'cta_density',
            'geo_optimization',
            'readability_target',
        ];
        
        foreach ($keys as $key) {
            $value = get_option("dodo_strategy_{$key}");
            if ($value !== false) {
                $strategy[$key] = $value;
            }
        }
        
        return $strategy;
    }
    
    /**
     * Apply strategy
     */
    private function apply_strategy($strategy) {
        foreach ($strategy as $key => $value) {
            update_option("dodo_strategy_{$key}", $value);
        }
    }
    
    /**
     * Get evolution timeline
     */
    public function get_evolution_timeline($limit = 20) {
        global $wpdb;
        
        $snapshots = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
            ORDER BY created_at ASC 
            LIMIT %d",
            $limit
        ));
        
        $timeline = [];
        $prev_snapshot = null;
        
        foreach ($snapshots as $snapshot) {
            $current = [
                'snapshot_id' => $snapshot->snapshot_id,
                'label' => $snapshot->label,
                'strategy' => json_decode($snapshot->strategy_data, true),
                'created_at' => $snapshot->created_at,
            ];
            
            if ($prev_snapshot) {
                $current['changes_from_previous'] = $this->calculate_changes(
                    $prev_snapshot['strategy'],
                    $current['strategy']
                );
            }
            
            $timeline[] = $current;
            $prev_snapshot = $current;
        }
        
        return $timeline;
    }
    
    /**
     * Calculate changes between strategies
     */
    private function calculate_changes($strategy1, $strategy2) {
        $changes = [];
        
        foreach ($strategy2 as $key => $val2) {
            $val1 = $strategy1[$key] ?? null;
            
            if ($val1 !== $val2) {
                $changes[$key] = [
                    'from' => $val1,
                    'to' => $val2,
                ];
                
                if (is_numeric($val1) && is_numeric($val2) && $val1 > 0) {
                    $changes[$key]['change_pct'] = round((($val2 - $val1) / $val1) * 100, 1);
                }
            }
        }
        
        return $changes;
    }
    
    /**
     * Delete old snapshots
     */
    public function cleanup_old_snapshots($days = 90) {
        global $wpdb;
        
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$this->table_name} 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ));
        
        error_log("[DODO Snapshots] Cleaned up {$deleted} old snapshots");
        
        return $deleted;
    }
    
    /**
     * Create table
     */
    public static function create_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dodo_strategy_snapshots';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            snapshot_id varchar(36) NOT NULL,
            label varchar(255) DEFAULT '',
            strategy_data longtext NOT NULL,
            metadata longtext,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY snapshot_id (snapshot_id),
            KEY created_at (created_at)
        ) {$charset_collate};";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        error_log('[DODO Snapshots] Strategy snapshots table created');
    }
}
