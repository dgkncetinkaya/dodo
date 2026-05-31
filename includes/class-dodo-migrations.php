<?php
/**
 * Database Migrations Manager
 * 
 * Centralized migration system for all DODO tables
 * 
 * @package DODO_AI_SEO
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Migrations {
    
    /**
     * Current schema version
     */
    const SCHEMA_VERSION = 5;
    
    /**
     * Option key for schema version
     */
    const VERSION_OPTION = 'dodo_schema_version';
    
    /**
     * Run all pending migrations
     */
    public static function run_migrations() {
        $current_version = get_option(self::VERSION_OPTION, 0);
        
        if ($current_version >= self::SCHEMA_VERSION) {
            return true; // Already up to date
        }
        
        // Run migrations sequentially
        for ($version = $current_version + 1; $version <= self::SCHEMA_VERSION; $version++) {
            $method = "migrate_to_v{$version}";
            
            if (method_exists(__CLASS__, $method)) {
                $result = call_user_func([__CLASS__, $method]);
                
                if ($result === false) {
                    error_log("DODO Migration: Failed at version {$version}");
                    return false;
                }
                
                update_option(self::VERSION_OPTION, $version);
                error_log("DODO Migration: Migrated to version {$version}");
            }
        }
        
        return true;
    }
    
    /**
     * Get current schema version
     */
    public static function get_current_version() {
        return (int) get_option(self::VERSION_OPTION, 0);
    }
    
    /**
     * Verify all tables exist
     */
    public static function verify_schema() {
        global $wpdb;
        
        $required_tables = [
            'dodo_queue_jobs',
            'dodo_telemetry',
            'dodo_impact_tracking',
            'dodo_learning_events',
            'dodo_strategy_snapshots',
            'dodo_semantic_cache',
            'dodo_performance_cache',
            'dodo_approval_requests',
            'dodo_security_logs',
        ];
        
        $missing = [];
        
        foreach ($required_tables as $table) {
            $full_table = $wpdb->prefix . $table;
            $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $full_table));
            
            if (!$exists) {
                $missing[] = $table;
            }
        }
        
        return [
            'complete' => empty($missing),
            'missing' => $missing,
            'total' => count($required_tables),
            'existing' => count($required_tables) - count($missing),
        ];
    }
    
    /**
     * Repair all tables (safe rerun)
     */
    public static function repair_tables() {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $results = [];
        
        // Run all migrations
        $results['migrations'] = self::run_migrations();
        
        // Create all tables
        $results['queue'] = self::create_queue_table();
        $results['telemetry'] = self::create_telemetry_table();
        $results['impact'] = self::create_impact_tracking_table();
        $results['learning'] = self::create_learning_events_table();
        $results['snapshots'] = self::create_strategy_snapshots_table();
        $results['semantic'] = self::create_semantic_cache_table();
        $results['performance'] = self::create_performance_cache_table();
        $results['approval'] = self::create_approval_requests_table();
        $results['security'] = self::create_security_logs_table();
        
        return $results;
    }
    
    /**
     * Migration v1: Queue system
     */
    private static function migrate_to_v1() {
        return self::create_queue_table();
    }
    
    /**
     * Migration v2: Telemetry
     */
    private static function migrate_to_v2() {
        return self::create_telemetry_table();
    }
    
    /**
     * Migration v3: Learning system
     */
    private static function migrate_to_v3() {
        $result1 = self::create_impact_tracking_table();
        $result2 = self::create_learning_events_table();
        $result3 = self::create_strategy_snapshots_table();
        
        return $result1 && $result2 && $result3;
    }
    
    /**
     * Migration v4: Cache system
     */
    private static function migrate_to_v4() {
        $result1 = self::create_semantic_cache_table();
        $result2 = self::create_performance_cache_table();
        
        return $result1 && $result2;
    }
    
    /**
     * Migration v5: Governance
     */
    private static function migrate_to_v5() {
        $result1 = self::create_approval_requests_table();
        $result2 = self::create_security_logs_table();
        
        return $result1 && $result2;
    }
    
    /**
     * Create queue jobs table
     */
    public static function create_queue_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'dodo_queue_jobs';
        
        $sql = "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            job_type varchar(50) NOT NULL,
            payload longtext NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            priority int(11) NOT NULL DEFAULT 10,
            attempts int(11) NOT NULL DEFAULT 0,
            max_attempts int(11) NOT NULL DEFAULT 3,
            error_message text,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            scheduled_at datetime DEFAULT NULL,
            started_at datetime DEFAULT NULL,
            completed_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY status (status),
            KEY scheduled_at (scheduled_at),
            KEY priority (priority),
            KEY job_type (job_type)
        ) {$wpdb->get_charset_collate()};";
        
        dbDelta($sql);
        return true;
    }
    
    /**
     * Create telemetry table
     */
    public static function create_telemetry_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'dodo_telemetry';
        
        $sql = "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            operation_type varchar(100) NOT NULL,
            model varchar(50) NOT NULL,
            prompt_tokens int(11) NOT NULL DEFAULT 0,
            completion_tokens int(11) NOT NULL DEFAULT 0,
            total_tokens int(11) NOT NULL DEFAULT 0,
            estimated_cost decimal(10,6) NOT NULL DEFAULT 0,
            response_time int(11) NOT NULL DEFAULT 0,
            cache_hit tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY operation_type (operation_type),
            KEY created_at (created_at),
            KEY cache_hit (cache_hit),
            KEY model (model)
        ) {$wpdb->get_charset_collate()};";
        
        dbDelta($sql);
        return true;
    }
    
    /**
     * Create impact tracking table
     */
    public static function create_impact_tracking_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'dodo_impact_tracking';
        
        $sql = "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            post_id bigint(20) unsigned DEFAULT NULL,
            action_type varchar(50) NOT NULL,
            action_data longtext NOT NULL,
            before_score decimal(5,2) DEFAULT NULL,
            after_score decimal(5,2) DEFAULT NULL,
            ctr_before decimal(5,2) DEFAULT NULL,
            ctr_after decimal(5,2) DEFAULT NULL,
            ranking_before int(11) DEFAULT NULL,
            ranking_after int(11) DEFAULT NULL,
            geo_before decimal(5,2) DEFAULT NULL,
            geo_after decimal(5,2) DEFAULT NULL,
            impact_score decimal(5,2) DEFAULT NULL,
            before_metrics longtext,
            after_metrics longtext,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            measured_at datetime DEFAULT NULL,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY action_type (action_type),
            KEY created_at (created_at),
            KEY impact_score (impact_score)
        ) {$wpdb->get_charset_collate()};";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        error_log('DODO Migration: Impact tracking table created/updated');
        return true;
    }
    
    /**
     * Create learning events table
     */
    public static function create_learning_events_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'dodo_learning_events';
        
        $sql = "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            event_type varchar(50) NOT NULL,
            event_data longtext NOT NULL,
            confidence_score decimal(5,2) DEFAULT NULL,
            applied tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY event_type (event_type),
            KEY created_at (created_at),
            KEY applied (applied)
        ) {$wpdb->get_charset_collate()};";
        
        dbDelta($sql);
        return true;
    }
    
    /**
     * Create strategy snapshots table
     */
    public static function create_strategy_snapshots_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'dodo_strategy_snapshots';
        
        $sql = "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            snapshot_type varchar(50) NOT NULL,
            strategy_data longtext NOT NULL,
            confidence_score decimal(5,2) NOT NULL DEFAULT 0,
            approved tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY snapshot_type (snapshot_type),
            KEY created_at (created_at),
            KEY approved (approved)
        ) {$wpdb->get_charset_collate()};";
        
        dbDelta($sql);
        return true;
    }
    
    /**
     * Create semantic cache table
     */
    public static function create_semantic_cache_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'dodo_semantic_cache';
        
        $sql = "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            cache_key varchar(255) NOT NULL,
            embedding_data longtext NOT NULL,
            metadata longtext,
            hits int(11) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            expires_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY cache_key (cache_key),
            KEY expires_at (expires_at),
            KEY hits (hits)
        ) {$wpdb->get_charset_collate()};";
        
        dbDelta($sql);
        return true;
    }
    
    /**
     * Create performance cache table
     */
    public static function create_performance_cache_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'dodo_performance_cache';
        
        $sql = "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            cache_key varchar(255) NOT NULL,
            cache_value longtext NOT NULL,
            cache_group varchar(50) NOT NULL DEFAULT 'default',
            hits int(11) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            expires_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY cache_key_group (cache_key, cache_group),
            KEY expires_at (expires_at),
            KEY cache_group (cache_group)
        ) {$wpdb->get_charset_collate()};";
        
        dbDelta($sql);
        return true;
    }
    
    /**
     * Create approval requests table
     */
    public static function create_approval_requests_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'dodo_approval_requests';
        
        $sql = "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            request_type varchar(50) NOT NULL,
            request_data longtext NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            approved_by bigint(20) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            reviewed_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY request_type (request_type),
            KEY status (status),
            KEY created_at (created_at)
        ) {$wpdb->get_charset_collate()};";
        
        dbDelta($sql);
        return true;
    }
    
    /**
     * Create security logs table
     */
    public static function create_security_logs_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'dodo_security_logs';
        
        $sql = "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            event_type varchar(50) NOT NULL,
            severity varchar(20) NOT NULL DEFAULT 'info',
            message text NOT NULL,
            user_id bigint(20) DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY event_type (event_type),
            KEY severity (severity),
            KEY created_at (created_at),
            KEY user_id (user_id)
        ) {$wpdb->get_charset_collate()};";
        
        dbDelta($sql);
        return true;
    }
    
    /**
     * Cleanup old data
     */
    public static function cleanup_old_data() {
        global $wpdb;
        
        // Cleanup telemetry older than 90 days
        $wpdb->query("
            DELETE FROM {$wpdb->prefix}dodo_telemetry
            WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)
        ");
        
        // Cleanup security logs older than 30 days
        $wpdb->query("
            DELETE FROM {$wpdb->prefix}dodo_security_logs
            WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        
        // Cleanup expired cache
        $wpdb->query("
            DELETE FROM {$wpdb->prefix}dodo_semantic_cache
            WHERE expires_at IS NOT NULL AND expires_at < NOW()
        ");
        
        $wpdb->query("
            DELETE FROM {$wpdb->prefix}dodo_performance_cache
            WHERE expires_at IS NOT NULL AND expires_at < NOW()
        ");
        
        // Cleanup completed jobs older than 7 days
        $wpdb->query("
            DELETE FROM {$wpdb->prefix}dodo_queue_jobs
            WHERE status = 'completed' AND completed_at < DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");
        
        return true;
    }
}
