<?php
/**
 * Background Job Queue System
 * 
 * @package DODO_AI_SEO
 * @since 2.0.0 (Sprint 4 - Task 3)
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Job_Queue {
    
    const TABLE_NAME = 'dodo_job_queue';
    const MAX_RETRIES = 3;
    
    /**
     * Create queue table
     */
    public static function create_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            job_type varchar(50) NOT NULL,
            job_data longtext NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            priority int(11) NOT NULL DEFAULT 10,
            attempts int(11) NOT NULL DEFAULT 0,
            max_attempts int(11) NOT NULL DEFAULT 3,
            started_at datetime DEFAULT NULL,
            completed_at datetime DEFAULT NULL,
            failed_at datetime DEFAULT NULL,
            error_message text DEFAULT NULL,
            progress int(11) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY status (status),
            KEY job_type (job_type),
            KEY priority (priority),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Add job to queue
     */
    public static function add_job($job_type, $job_data, $priority = 10) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        
        $inserted = $wpdb->insert(
            $table_name,
            array(
                'job_type' => $job_type,
                'job_data' => json_encode($job_data),
                'status' => 'pending',
                'priority' => $priority,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ),
            array('%s', '%s', '%s', '%d', '%s', '%s')
        );
        
        if ($inserted) {
            return $wpdb->insert_id;
        }
        
        return false;
    }
    
    /**
     * Get next pending job
     */
    public static function get_next_job() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        
        // Get highest priority pending job
        $job = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} 
            WHERE status = 'pending' 
            AND attempts < max_attempts 
            ORDER BY priority DESC, created_at ASC 
            LIMIT 1"
        ), ARRAY_A);
        
        return $job;
    }
    
    /**
     * Start job
     */
    public static function start_job($job_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        
        return $wpdb->update(
            $table_name,
            array(
                'status' => 'processing',
                'started_at' => current_time('mysql'),
                'attempts' => $wpdb->prepare('attempts + 1'),
                'updated_at' => current_time('mysql'),
            ),
            array('id' => $job_id),
            array('%s', '%s', '%s', '%s'),
            array('%d')
        );
    }
    
    /**
     * Update job progress
     */
    public static function update_progress($job_id, $progress) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        
        return $wpdb->update(
            $table_name,
            array(
                'progress' => $progress,
                'updated_at' => current_time('mysql'),
            ),
            array('id' => $job_id),
            array('%d', '%s'),
            array('%d')
        );
    }
    
    /**
     * Complete job
     */
    public static function complete_job($job_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        
        return $wpdb->update(
            $table_name,
            array(
                'status' => 'completed',
                'progress' => 100,
                'completed_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ),
            array('id' => $job_id),
            array('%s', '%d', '%s', '%s'),
            array('%d')
        );
    }
    
    /**
     * Fail job
     */
    public static function fail_job($job_id, $error_message) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        
        return $wpdb->update(
            $table_name,
            array(
                'status' => 'failed',
                'failed_at' => current_time('mysql'),
                'error_message' => $error_message,
                'updated_at' => current_time('mysql'),
            ),
            array('id' => $job_id),
            array('%s', '%s', '%s', '%s'),
            array('%d')
        );
    }
    
    /**
     * Cancel job
     */
    public static function cancel_job($job_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        
        return $wpdb->update(
            $table_name,
            array(
                'status' => 'cancelled',
                'updated_at' => current_time('mysql'),
            ),
            array('id' => $job_id),
            array('%s', '%s'),
            array('%d')
        );
    }
    
    /**
     * Get job status
     */
    public static function get_job($job_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE id = %d",
            $job_id
        ), ARRAY_A);
    }
    
    /**
     * Get queue stats
     */
    public static function get_stats() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        
        $stats = $wpdb->get_row("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
            FROM {$table_name}
        ", ARRAY_A);
        
        return $stats;
    }
    
    /**
     * Process next job
     */
    public static function process_next() {
        $job = self::get_next_job();
        
        if (!$job) {
            return false;
        }
        
        self::start_job($job['id']);
        
        try {
            $job_data = json_decode($job['job_data'], true);
            
            switch ($job['job_type']) {
                case 'blog_generation':
                    self::process_blog_generation($job['id'], $job_data);
                    break;
                    
                case 'analytics_snapshot':
                    self::process_analytics_snapshot($job['id'], $job_data);
                    break;
                    
                case 'cluster_analysis':
                    self::process_cluster_analysis($job['id'], $job_data);
                    break;
                    
                case 'content_audit':
                    self::process_content_audit($job['id'], $job_data);
                    break;
                    
                case 'bulk_improvement':
                    self::process_bulk_improvement($job['id'], $job_data);
                    break;
                    
                default:
                    throw new Exception('Unknown job type: ' . $job['job_type']);
            }
            
            self::complete_job($job['id']);
            return true;
            
        } catch (Exception $e) {
            self::fail_job($job['id'], $e->getMessage());
            error_log('[DODO JOB] Job #' . $job['id'] . ' failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Process blog generation job
     */
    private static function process_blog_generation($job_id, $data) {
        $generator = new DODO_Generator();
        $result = $generator->generate_blog($data);
        
        if (is_wp_error($result)) {
            throw new Exception($result->get_error_message());
        }
    }
    
    /**
     * Process analytics snapshot job
     */
    private static function process_analytics_snapshot($job_id, $data) {
        $core = DODO_Core::get_instance();
        $core->record_analytics_snapshot();
    }
    
    /**
     * Process cluster analysis job
     */
    private static function process_cluster_analysis($job_id, $data) {
        $cluster_engine = new DODO_Cluster_Engine();
        $cluster_engine->analyze_site_clusters();
    }
    
    /**
     * Process content audit job
     */
    private static function process_content_audit($job_id, $data) {
        $audit = new DODO_Content_Audit();
        $audit->audit_all_content();
    }
    
    /**
     * Process bulk improvement job
     */
    private static function process_bulk_improvement($job_id, $data) {
        $post_ids = $data['post_ids'] ?? array();
        $total = count($post_ids);
        
        foreach ($post_ids as $index => $post_id) {
            // Process improvement
            $improver = new DODO_Content_Improver();
            $improver->improve_content($post_id);
            
            // Update progress
            $progress = round((($index + 1) / $total) * 100);
            self::update_progress($job_id, $progress);
        }
    }
    
    /**
     * Clean old completed jobs
     */
    public static function cleanup($days = 7) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        return $wpdb->query($wpdb->prepare(
            "DELETE FROM {$table_name} 
            WHERE status IN ('completed', 'cancelled') 
            AND updated_at < %s",
            $cutoff_date
        ));
    }
}
