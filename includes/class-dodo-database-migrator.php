<?php
/**
 * Database Migrator - Comprehensive Schema Management
 * 
 * Handles all DODO AI SEO database tables and columns
 * Version-based migrations with safe column additions
 * 
 * @package DODO_AI_SEO
 * @since 4.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (class_exists('DODO_Database_Migrator')) {
    return;
}

class DODO_Database_Migrator {
    
    const DB_VERSION = 12; // Incremented for forced metadata repair
    const OPTION_KEY = 'dodo_db_version';
    
    /**
     * Ensure column exists in table
     */
    private static function ensure_column_exists($table, $column, $definition) {
        global $wpdb;
        
        // Check if column exists
        $columns = $wpdb->get_col("DESCRIBE {$table}", 0);
        
        if (!in_array($column, $columns)) {
            error_log("[DODO Migrator] Adding missing column {$column} to {$table}");
            $wpdb->query("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
            return true;
        }
        
        return false;
    }
    
    /**
     * Run migrations
     */
    public static function run() {
        $current_version = get_option(self::OPTION_KEY, 0);
        
        if ($current_version >= self::DB_VERSION) {
            return true; // Already up to date
        }
        
        error_log("[DODO Migrator] Starting migration from v{$current_version} to v" . self::DB_VERSION);
        
        // Run all table creations/updates
        self::create_all_tables();
        
        // Update version
        update_option(self::OPTION_KEY, self::DB_VERSION);
        
        error_log("[DODO Migrator] Migration complete - now at v" . self::DB_VERSION);
        
        return true;
    }
    
    /**
     * Create/update all tables
     */
    private static function create_all_tables() {
        global $wpdb;
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // 1. Feedback Events Table
        self::create_feedback_events_table($charset_collate);
        
        // 2. Learning Events Table
        self::create_learning_events_table($charset_collate);
        
        // 3. Performance Cache Table
        self::create_performance_cache_table($charset_collate);
        
        // 4. Queue Jobs Table
        self::create_queue_jobs_table($charset_collate);
        
        // 5. Telemetry Table
        self::create_telemetry_table($charset_collate);
        
        // 6. Impact Tracking Table
        self::create_impact_tracking_table($charset_collate);
        
        // 7. Strategy Snapshots Table
        self::create_strategy_snapshots_table($charset_collate);
        
        // 8. AI Visibility Table
        self::create_ai_visibility_table($charset_collate);
    }
    
    /**
     * Create feedback_events table
     */
    private static function create_feedback_events_table($charset_collate) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'dodo_feedback_events';
        
        $sql = "CREATE TABLE {$table_name} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_action VARCHAR(50) NOT NULL,
            post_id BIGINT UNSIGNED NULL,
            content_type VARCHAR(100) NULL,
            feedback TEXT NULL,
            metadata LONGTEXT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            KEY post_id (post_id),
            KEY user_action (user_action),
            KEY created_at (created_at)
        ) {$charset_collate};";
        
        dbDelta($sql);
        
        error_log("[DODO Migrator] Created/updated table: {$table_name}");
    }
    
    /**
     * Create learning_events table
     */
    private static function create_learning_events_table($charset_collate) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'dodo_learning_events';
        
        $sql = "CREATE TABLE {$table_name} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            event_type VARCHAR(100) NOT NULL,
            post_id BIGINT UNSIGNED NULL,
            strategy_params LONGTEXT NULL,
            outcome_score FLOAT DEFAULT 0,
            metadata LONGTEXT NULL,
            recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            KEY event_type (event_type),
            KEY post_id (post_id),
            KEY outcome_score (outcome_score),
            KEY recorded_at (recorded_at)
        ) {$charset_collate};";
        
        dbDelta($sql);
        
        error_log("[DODO Migrator] Created/updated table: {$table_name}");
    }
    
    /**
     * Create performance_cache table
     */
    private static function create_performance_cache_table($charset_collate) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'dodo_performance_cache';
        
        $sql = "CREATE TABLE {$table_name} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            cache_type VARCHAR(100) NOT NULL,
            cache_key VARCHAR(191) NOT NULL,
            cache_value LONGTEXT NULL,
            metadata LONGTEXT NULL,
            expires_at DATETIME NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY cache_key (cache_key),
            KEY cache_type (cache_type),
            KEY expires_at (expires_at)
        ) {$charset_collate};";
        
        dbDelta($sql);
        
        // CRITICAL: Force check and add metadata column if missing
        $columns = $wpdb->get_col("DESCRIBE {$table_name}", 0);
        
        if (!in_array('metadata', $columns)) {
            error_log("[DODO MIGRATION] metadata column missing in {$table_name}, adding now...");
            
            $result = $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN metadata LONGTEXT NULL AFTER cache_value");
            
            if ($result !== false) {
                error_log("[DODO MIGRATION] ✓ Added metadata column to dodo_performance_cache");
            } else {
                error_log("[DODO MIGRATION] ✗ Failed to add metadata column: " . $wpdb->last_error);
            }
        } else {
            error_log("[DODO MIGRATION] ✓ metadata column already exists in {$table_name}");
        }
        
        error_log("[DODO Migrator] Created/updated table: {$table_name}");
    }
    
    /**
     * Create queue_jobs table
     */
    private static function create_queue_jobs_table($charset_collate) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'dodo_queue_jobs';
        
        $sql = "CREATE TABLE {$table_name} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            job_type VARCHAR(50) NOT NULL,
            job_data LONGTEXT NULL,
            status VARCHAR(50) DEFAULT 'pending',
            retry_count INT DEFAULT 0,
            max_retries INT DEFAULT 3,
            priority INT DEFAULT 5,
            error_message TEXT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            started_at DATETIME NULL,
            completed_at DATETIME NULL,
            KEY status (status),
            KEY job_type (job_type),
            KEY priority (priority),
            KEY created_at (created_at)
        ) {$charset_collate};";
        
        dbDelta($sql);
        
        error_log("[DODO Migrator] Created/updated table: {$table_name}");
    }
    
    /**
     * Create telemetry table
     */
    private static function create_telemetry_table($charset_collate) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'dodo_telemetry';
        
        $sql = "CREATE TABLE {$table_name} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            metric_name VARCHAR(191) NOT NULL,
            metric_value TEXT NULL,
            tags LONGTEXT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            KEY metric_name (metric_name),
            KEY created_at (created_at)
        ) {$charset_collate};";
        
        dbDelta($sql);
        
        error_log("[DODO Migrator] Created/updated table: {$table_name}");
    }
    
    /**
     * Create impact_tracking table
     */
    private static function create_impact_tracking_table($charset_collate) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'dodo_impact_tracking';
        
        $sql = "CREATE TABLE {$table_name} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            post_id BIGINT UNSIGNED NOT NULL,
            strategy_params LONGTEXT NULL,
            baseline_metrics LONGTEXT NULL,
            current_metrics LONGTEXT NULL,
            impact_score FLOAT DEFAULT 0,
            tracked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            KEY post_id (post_id),
            KEY impact_score (impact_score),
            KEY tracked_at (tracked_at)
        ) {$charset_collate};";
        
        dbDelta($sql);
        
        error_log("[DODO Migrator] Created/updated table: {$table_name}");
    }
    
    /**
     * Create strategy_snapshots table
     */
    private static function create_strategy_snapshots_table($charset_collate) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'dodo_strategy_snapshots';
        
        $sql = "CREATE TABLE {$table_name} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            snapshot_id VARCHAR(36) NOT NULL,
            snapshot_type VARCHAR(50) NOT NULL,
            strategy_data LONGTEXT NULL,
            metadata LONGTEXT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY snapshot_id (snapshot_id),
            KEY snapshot_type (snapshot_type),
            KEY created_at (created_at)
        ) {$charset_collate};";
        
        dbDelta($sql);
        
        error_log("[DODO Migrator] Created/updated table: {$table_name}");
    }
    
    /**
     * Create ai_visibility table
     */
    private static function create_ai_visibility_table($charset_collate) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'dodo_ai_visibility';
        
        $sql = "CREATE TABLE {$table_name} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            post_id BIGINT UNSIGNED NOT NULL,
            visibility_score INT DEFAULT 0,
            metrics LONGTEXT NULL,
            analysis_details LONGTEXT NULL,
            analyzed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            KEY post_id (post_id),
            KEY visibility_score (visibility_score),
            KEY analyzed_at (analyzed_at)
        ) {$charset_collate};";
        
        dbDelta($sql);
        
        error_log("[DODO Migrator] Created/updated table: {$table_name}");
    }
    
    /**
     * Check table health
     */
    public static function check_health() {
        global $wpdb;
        
        $tables = array(
            'dodo_feedback_events',
            'dodo_learning_events',
            'dodo_performance_cache',
            'dodo_queue_jobs',
            'dodo_telemetry',
            'dodo_impact_tracking',
            'dodo_strategy_snapshots',
            'dodo_ai_visibility',
        );
        
        $status = array();
        
        foreach ($tables as $table) {
            $table_name = $wpdb->prefix . $table;
            $exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
            
            $status[$table] = array(
                'exists' => $exists,
                'name' => $table_name,
            );
            
            if ($exists) {
                // Check column count
                $columns = $wpdb->get_results("DESCRIBE {$table_name}");
                $status[$table]['columns'] = count($columns);
            }
        }
        
        return $status;
    }
    
    /**
     * Repair missing tables
     */
    public static function repair() {
        error_log("[DODO Migrator] Starting repair...");
        
        $result = self::run();
        
        if ($result) {
            error_log("[DODO Migrator] Repair completed successfully");
            return array(
                'success' => true,
                'message' => 'Database tables repaired successfully',
            );
        } else {
            error_log("[DODO Migrator] Repair failed");
            return array(
                'success' => false,
                'message' => 'Database repair failed',
            );
        }
    }
    
    /**
     * Get current version
     */
    public static function get_version() {
        return get_option(self::OPTION_KEY, 0);
    }
    
    /**
     * Check if migration needed
     */
    public static function needs_migration() {
        return self::get_version() < self::DB_VERSION;
    }
}
