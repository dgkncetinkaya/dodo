<?php
/**
 * Database Migration & Schema Management
 * 
 * @package DODO_AI_SEO
 * @since 2.0.0 (Sprint 4 - Task 2)
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Database {
    
    const SCHEMA_VERSION = 9; // Increment on schema changes
    const OPTION_KEY = 'dodo_db_schema_version';
    
    /**
     * Get current schema version
     */
    public static function get_schema_version() {
        return get_option(self::OPTION_KEY, 0);
    }
    
    /**
     * Update schema version
     */
    public static function update_schema_version($version) {
        update_option(self::OPTION_KEY, $version);
    }
    
    /**
     * Check if migration is needed
     */
    public static function needs_migration() {
        return self::get_schema_version() < self::SCHEMA_VERSION;
    }
    
    /**
     * Run migrations
     */
    public static function migrate() {
        $current_version = self::get_schema_version();
        
        error_log("[DODO DB] Starting migration from version {$current_version} to " . self::SCHEMA_VERSION);
        
        // Run migrations sequentially
        for ($version = $current_version + 1; $version <= self::SCHEMA_VERSION; $version++) {
            $method = "migrate_to_v{$version}";
            
            if (method_exists(__CLASS__, $method)) {
                error_log("[DODO DB] Running migration: {$method}");
                
                $result = self::$method();
                
                if ($result === false) {
                    error_log("[DODO DB] Migration {$method} failed");
                    return false;
                }
                
                self::update_schema_version($version);
                error_log("[DODO DB] Migration {$method} completed");
            }
        }
        
        error_log("[DODO DB] All migrations completed");
        return true;
    }
    
    /**
     * Check if all tables exist
     */
    public static function check_tables() {
        global $wpdb;
        
        $tables = array(
            'dodo_keyword_opportunities',
            'dodo_ai_revisions',
            'dodo_content_audit',
            'dodo_ai_usage_logs',
            'dodo_score_history',
            'dodo_analytics_history',
        );
        
        $missing = array();
        
        foreach ($tables as $table) {
            $table_name = $wpdb->prefix . $table;
            $exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
            
            if (!$exists) {
                $missing[] = $table;
            }
        }
        
        return array(
            'all_exist' => empty($missing),
            'missing' => $missing,
            'total' => count($tables),
            'existing' => count($tables) - count($missing),
        );
    }
    
    /**
     * Auto-repair missing tables
     */
    public static function auto_repair() {
        $check = self::check_tables();
        
        if ($check['all_exist']) {
            return array('success' => true, 'message' => 'Tüm tablolar mevcut');
        }
        
        error_log('[DODO DB] Auto-repair: Missing tables detected: ' . implode(', ', $check['missing']));
        
        // Re-run table creation functions
        if (in_array('dodo_keyword_opportunities', $check['missing'])) {
            dodo_ai_seo_create_table();
        }
        
        if (in_array('dodo_ai_revisions', $check['missing'])) {
            require_once DODO_PLUGIN_DIR . 'includes/class-dodo-revision-manager.php';
            DODO_Revision_Manager::create_table();
        }
        
        if (in_array('dodo_content_audit', $check['missing'])) {
            require_once DODO_PLUGIN_DIR . 'includes/class-dodo-content-audit.php';
            $audit = new DODO_Content_Audit();
            $audit->create_table();
        }
        
        if (in_array('dodo_ai_usage_logs', $check['missing'])) {
            require_once DODO_PLUGIN_DIR . 'includes/class-dodo-usage-logger.php';
            $logger = new DODO_Usage_Logger();
            $logger->create_table();
        }
        
        if (in_array('dodo_score_history', $check['missing'])) {
            dodo_ai_seo_create_score_history_table();
        }
        
        if (in_array('dodo_analytics_history', $check['missing'])) {
            dodo_ai_seo_create_analytics_history_table();
        }
        
        // Re-check
        $recheck = self::check_tables();
        
        if ($recheck['all_exist']) {
            error_log('[DODO DB] Auto-repair: All tables created successfully');
            return array('success' => true, 'message' => 'Eksik tablolar oluşturuldu');
        } else {
            error_log('[DODO DB] Auto-repair: Failed to create some tables: ' . implode(', ', $recheck['missing']));
            return array('success' => false, 'message' => 'Bazı tablolar oluşturulamadı', 'missing' => $recheck['missing']);
        }
    }
    
    /**
     * Migration: v1 - Initial schema
     */
    private static function migrate_to_v1() {
        // Initial tables already created in activation
        return true;
    }
    
    /**
     * Migration: v2 - Add retry columns to opportunities
     */
    private static function migrate_to_v2() {
        global $wpdb;
        $table = $wpdb->prefix . 'dodo_keyword_opportunities';
        
        // Check if columns exist
        $columns = $wpdb->get_col("DESCRIBE {$table}");
        
        if (!in_array('retry_count', $columns)) {
            $wpdb->query("ALTER TABLE {$table} ADD COLUMN retry_count int(11) NOT NULL DEFAULT 0");
        }
        
        if (!in_array('last_retry_at', $columns)) {
            $wpdb->query("ALTER TABLE {$table} ADD COLUMN last_retry_at datetime DEFAULT NULL");
        }
        
        if (!in_array('last_error', $columns)) {
            $wpdb->query("ALTER TABLE {$table} ADD COLUMN last_error text DEFAULT NULL");
        }
        
        if (!in_array('next_retry_at', $columns)) {
            $wpdb->query("ALTER TABLE {$table} ADD COLUMN next_retry_at datetime DEFAULT NULL");
            $wpdb->query("ALTER TABLE {$table} ADD KEY next_retry_at (next_retry_at)");
        }
        
        return true;
    }
    
    /**
     * Migration: v3 - Add analytics history table
     */
    private static function migrate_to_v3() {
        dodo_ai_seo_create_analytics_history_table();
        return true;
    }
    
    /**
     * Migration: v4 - Add workflow status column
     */
    private static function migrate_to_v4() {
        global $wpdb;
        $table = $wpdb->prefix . 'dodo_analytics_history';
        
        $columns = $wpdb->get_col("DESCRIBE {$table}");
        
        if (!in_array('workflow_status', $columns)) {
            $wpdb->query("ALTER TABLE {$table} ADD COLUMN workflow_status varchar(50) DEFAULT 'draft'");
        }
        
        return true;
    }
    
    /**
     * Migration: v5 - Add job queue composite index
     */
    private static function migrate_to_v5() {
        global $wpdb;
        $table = $wpdb->prefix . 'dodo_job_queue';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) {
            return true; // Skip if table doesn't exist
        }
        
        // Check if index already exists
        $indexes = $wpdb->get_results("SHOW INDEX FROM {$table} WHERE Key_name = 'status_priority'");
        
        if (empty($indexes)) {
            $wpdb->query("ALTER TABLE {$table} ADD INDEX status_priority (status, priority)");
        }
        
        return true;
    }
    
    /**
     * Migration: v6 - Add GEO scores table (Sprint 5)
     */
    private static function migrate_to_v6() {
        require_once DODO_PLUGIN_DIR . 'includes/class-dodo-geo-engine.php';
        DODO_GEO_Engine::create_table();
        return true;
    }
    
    /**
     * Migration: v7 - Add Content Intelligence table (Sprint 5)
     */
    private static function migrate_to_v7() {
        require_once DODO_PLUGIN_DIR . 'includes/class-dodo-content-intelligence.php';
        DODO_Content_Intelligence::create_table();
        return true;
    }
    
    /**
     * Migration: v8 - Add Humanization Analysis table (Sprint 5)
     */
    private static function migrate_to_v8() {
        require_once DODO_PLUGIN_DIR . 'includes/class-dodo-humanizer.php';
        DODO_Humanizer::create_table();
        return true;
    }
    
    /**
     * Migration: v9 - Add Publishing Pipeline table (Sprint 5)
     */
    private static function migrate_to_v9() {
        require_once DODO_PLUGIN_DIR . 'includes/class-dodo-publishing-pipeline.php';
        DODO_Publishing_Pipeline::create_table();
        return true;
    }
    
    /**
     * Get database health status
     */
    public static function get_health() {
        global $wpdb;
        
        $tables = self::check_tables();
        $schema_version = self::get_schema_version();
        $needs_migration = self::needs_migration();
        
        // Check database size
        $size_query = $wpdb->get_results("
            SELECT 
                SUM(data_length + index_length) as size 
            FROM information_schema.TABLES 
            WHERE table_schema = '{$wpdb->dbname}' 
            AND table_name LIKE '{$wpdb->prefix}dodo_%'
        ");
        
        $size_bytes = isset($size_query[0]->size) ? $size_query[0]->size : 0;
        $size_mb = round($size_bytes / 1024 / 1024, 2);
        
        return array(
            'tables' => $tables,
            'schema_version' => $schema_version,
            'latest_version' => self::SCHEMA_VERSION,
            'needs_migration' => $needs_migration,
            'size_mb' => $size_mb,
            'status' => $tables['all_exist'] && !$needs_migration ? 'healthy' : 'warning',
        );
    }
}
