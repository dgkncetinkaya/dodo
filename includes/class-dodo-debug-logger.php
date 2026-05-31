<?php
/**
 * DODO AI SEO - Production Debug Logger
 * 
 * Comprehensive logging system for blog generation pipeline
 * 
 * @package DODO_AI_SEO
 * @since 2.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Debug_Logger {
    
    private $debug_id;
    private $stages = [];
    private $start_time;
    private $total_tokens = 0;
    
    /**
     * Initialize debug session
     */
    public function __construct() {
        $this->debug_id = uniqid('dodo_debug_', true);
        $this->start_time = microtime(true);
        
        error_log('[DODO DEBUG] Session started: ' . $this->debug_id);
    }
    
    /**
     * Start a pipeline stage
     */
    public function start_stage($stage_name) {
        $this->stages[$stage_name] = [
            'status' => 'running',
            'start_time' => microtime(true),
            'end_time' => null,
            'duration' => null,
            'tokens' => 0,
            'error' => null,
            'data' => []
        ];
        
        error_log("[DODO DEBUG] Stage started: {$stage_name}");
    }
    
    /**
     * End a pipeline stage with success
     */
    public function end_stage($stage_name, $data = []) {
        if (!isset($this->stages[$stage_name])) {
            error_log("[DODO DEBUG] Warning: Stage {$stage_name} not started");
            return;
        }
        
        $this->stages[$stage_name]['status'] = 'success';
        $this->stages[$stage_name]['end_time'] = microtime(true);
        $this->stages[$stage_name]['duration'] = $this->stages[$stage_name]['end_time'] - $this->stages[$stage_name]['start_time'];
        $this->stages[$stage_name]['data'] = $data;
        
        error_log(sprintf(
            "[DODO DEBUG] Stage completed: %s (%.2fs)",
            $stage_name,
            $this->stages[$stage_name]['duration']
        ));
    }
    
    /**
     * Mark stage as failed
     */
    public function fail_stage($stage_name, $error_message, $error_data = []) {
        if (!isset($this->stages[$stage_name])) {
            $this->start_stage($stage_name);
        }
        
        $this->stages[$stage_name]['status'] = 'failed';
        $this->stages[$stage_name]['end_time'] = microtime(true);
        $this->stages[$stage_name]['duration'] = $this->stages[$stage_name]['end_time'] - $this->stages[$stage_name]['start_time'];
        $this->stages[$stage_name]['error'] = $error_message;
        $this->stages[$stage_name]['data'] = $error_data;
        
        error_log("[DODO DEBUG] Stage FAILED: {$stage_name} - {$error_message}");
    }
    
    /**
     * Log token usage for a stage
     */
    public function log_tokens($stage_name, $tokens) {
        if (isset($this->stages[$stage_name])) {
            $this->stages[$stage_name]['tokens'] = $tokens;
            $this->total_tokens += $tokens;
        }
    }
    
    /**
     * Log API response
     */
    public function log_api_response($stage_name, $response) {
        if (isset($this->stages[$stage_name])) {
            $this->stages[$stage_name]['data']['api_response'] = [
                'status_code' => wp_remote_retrieve_response_code($response),
                'response_message' => wp_remote_retrieve_response_message($response),
                'body_length' => strlen(wp_remote_retrieve_body($response)),
                'is_error' => is_wp_error($response)
            ];
            
            if (is_wp_error($response)) {
                $this->stages[$stage_name]['data']['api_error'] = $response->get_error_message();
            }
        }
    }
    
    /**
     * Get debug summary
     */
    public function get_summary() {
        $total_duration = microtime(true) - $this->start_time;
        
        $failed_stages = array_filter($this->stages, function($stage) {
            return $stage['status'] === 'failed';
        });
        
        return [
            'debug_id' => $this->debug_id,
            'total_duration' => $total_duration,
            'total_tokens' => $this->total_tokens,
            'stages' => $this->stages,
            'failed_stages' => array_keys($failed_stages),
            'success' => empty($failed_stages),
            'timestamp' => current_time('mysql')
        ];
    }
    
    /**
     * Save debug log to database
     */
    public function save_to_database($post_id = null) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dodo_debug_logs';
        
        // Create table if not exists
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            debug_id varchar(100) NOT NULL,
            post_id bigint(20) DEFAULT NULL,
            summary longtext NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY debug_id (debug_id),
            KEY post_id (post_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Insert log
        $summary = $this->get_summary();
        
        $wpdb->insert(
            $table_name,
            [
                'debug_id' => $this->debug_id,
                'post_id' => $post_id,
                'summary' => json_encode($summary, JSON_PRETTY_PRINT),
                'created_at' => current_time('mysql')
            ],
            ['%s', '%d', '%s', '%s']
        );
        
        error_log("[DODO DEBUG] Log saved to database: {$this->debug_id}");
        
        return $this->debug_id;
    }
    
    /**
     * Get recent debug logs
     */
    public static function get_recent_logs($limit = 20) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'dodo_debug_logs';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name ORDER BY created_at DESC LIMIT %d",
            $limit
        ));
        
        return array_map(function($row) {
            $row->summary = json_decode($row->summary, true);
            return $row;
        }, $results);
    }
    
    /**
     * Get log by debug ID
     */
    public static function get_log($debug_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'dodo_debug_logs';
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE debug_id = %s",
            $debug_id
        ));
        
        if ($result) {
            $result->summary = json_decode($result->summary, true);
        }
        
        return $result;
    }
    
    /**
     * Clean old logs (keep last 100)
     */
    public static function clean_old_logs() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'dodo_debug_logs';
        
        $wpdb->query("
            DELETE FROM $table_name 
            WHERE id NOT IN (
                SELECT id FROM (
                    SELECT id FROM $table_name ORDER BY created_at DESC LIMIT 100
                ) AS keep_logs
            )
        ");
    }
}
