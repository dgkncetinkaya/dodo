<?php
/**
 * Cron Manager
 * 
 * Manages all DODO cron jobs
 * 
 * @package DODO_AI_SEO
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Cron_Manager {
    
    /**
     * Initialize cron jobs
     */
    public static function init() {
        // Register cron schedules
        add_filter('cron_schedules', [__CLASS__, 'add_cron_schedules']);
        
        // Register cron hooks
        add_action('dodo_process_queue', [__CLASS__, 'process_queue']);
        add_action('dodo_cleanup_cache', [__CLASS__, 'cleanup_cache']);
        add_action('dodo_cleanup_logs', [__CLASS__, 'cleanup_logs']);
        add_action('dodo_recover_stuck_jobs', [__CLASS__, 'recover_stuck_jobs']);
        
        // Schedule crons if not already scheduled
        self::schedule_crons();
    }
    
    /**
     * Add custom cron schedules
     */
    public static function add_cron_schedules($schedules) {
        // Every 5 minutes
        $schedules['dodo_five_minutes'] = [
            'interval' => 300,
            'display' => __('Her 5 Dakika', 'dodo-ai-seo')
        ];
        
        // Every 15 minutes
        $schedules['dodo_fifteen_minutes'] = [
            'interval' => 900,
            'display' => __('Her 15 Dakika', 'dodo-ai-seo')
        ];
        
        // Every hour
        $schedules['dodo_hourly'] = [
            'interval' => 3600,
            'display' => __('Saatte Bir', 'dodo-ai-seo')
        ];
        
        return $schedules;
    }
    
    /**
     * Schedule all cron jobs
     */
    public static function schedule_crons() {
        // Process queue every 5 minutes
        if (!wp_next_scheduled('dodo_process_queue')) {
            wp_schedule_event(time(), 'dodo_five_minutes', 'dodo_process_queue');
            error_log('DODO Cron: Scheduled dodo_process_queue');
        }
        
        // Cleanup cache every hour
        if (!wp_next_scheduled('dodo_cleanup_cache')) {
            wp_schedule_event(time(), 'dodo_hourly', 'dodo_cleanup_cache');
            error_log('DODO Cron: Scheduled dodo_cleanup_cache');
        }
        
        // Cleanup logs daily
        if (!wp_next_scheduled('dodo_cleanup_logs')) {
            wp_schedule_event(time(), 'daily', 'dodo_cleanup_logs');
            error_log('DODO Cron: Scheduled dodo_cleanup_logs');
        }
        
        // Recover stuck jobs every 15 minutes
        if (!wp_next_scheduled('dodo_recover_stuck_jobs')) {
            wp_schedule_event(time(), 'dodo_fifteen_minutes', 'dodo_recover_stuck_jobs');
            error_log('DODO Cron: Scheduled dodo_recover_stuck_jobs');
        }
    }
    
    /**
     * Unschedule all cron jobs
     */
    public static function unschedule_crons() {
        $crons = ['dodo_process_queue', 'dodo_cleanup_cache', 'dodo_cleanup_logs', 'dodo_recover_stuck_jobs'];
        
        foreach ($crons as $cron) {
            $timestamp = wp_next_scheduled($cron);
            if ($timestamp) {
                wp_unschedule_event($timestamp, $cron);
                error_log("DODO Cron: Unscheduled {$cron}");
            }
        }
    }
    
    /**
     * Process queue with overlap protection
     */
    public static function process_queue() {
        try {
            // Overlap protection
            $lock_key = 'dodo_cron_queue_lock';
            $lock_value = get_transient($lock_key);
            
            if ($lock_value) {
                error_log('DODO Cron: Queue processing already running, skipping');
                return;
            }
            
            // Set lock (5 minute timeout)
            set_transient($lock_key, time(), 300);
            
            if (!class_exists('DODO_Queue_Manager')) {
                delete_transient($lock_key);
                error_log('DODO Cron: Queue Manager class not found');
                return;
            }
            
            $queue = new DODO_Queue_Manager();
            
            // Check if get_next_job method exists
            if (!method_exists($queue, 'get_next_job')) {
                delete_transient($lock_key);
                error_log('DODO Cron: get_next_job method not found in Queue Manager');
                return;
            }
            
            $processed = 0;
            $max_jobs = 10; // Process max 10 jobs per run
            
            for ($i = 0; $i < $max_jobs; $i++) {
                try {
                    $job = $queue->get_next_job();
                    
                    if (!$job) {
                        if ($i === 0) {
                            error_log('DODO Cron: No pending jobs');
                        }
                        break; // No more jobs
                    }
                    
                    $queue->process_job($job->id);
                    $processed++;
                } catch (Exception $e) {
                    error_log("DODO Cron: Failed to process job: " . $e->getMessage());
                    continue; // Continue with next job
                }
            }
            
            if ($processed > 0) {
                error_log("DODO Cron: Processed {$processed} jobs");
            }
            
            // Release lock
            delete_transient($lock_key);
            
        } catch (Throwable $e) {
            // Release lock on error
            delete_transient('dodo_cron_queue_lock');
            error_log('DODO Cron: Fatal error in process_queue: ' . $e->getMessage());
        }
    }
    
    /**
     * Cleanup cache
     */
    public static function cleanup_cache() {
        if (!class_exists('DODO_Migrations')) {
            return;
        }
        
        DODO_Migrations::cleanup_old_data();
        error_log('DODO Cron: Cleaned up old data');
    }
    
    /**
     * Cleanup logs
     */
    public static function cleanup_logs() {
        global $wpdb;
        
        // Cleanup old security logs
        $deleted = $wpdb->query("
            DELETE FROM {$wpdb->prefix}dodo_security_logs
            WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        
        if ($deleted) {
            error_log("DODO Cron: Deleted {$deleted} old security logs");
        }
    }
    
    /**
     * Recover stuck jobs
     */
    public static function recover_stuck_jobs() {
        global $wpdb;
        $table = $wpdb->prefix . 'dodo_queue_jobs';
        
        // Check if table exists
        $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
        
        if (!$exists) {
            return;
        }
        
        // Reset stuck jobs (processing for more than 1 hour)
        $recovered = $wpdb->query("
            UPDATE {$table}
            SET status = 'pending',
                attempts = attempts + 1,
                updated_at = NOW()
            WHERE status = 'processing'
            AND updated_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)
            AND attempts < max_attempts
        ");
        
        if ($recovered) {
            error_log("DODO Cron: Recovered {$recovered} stuck jobs");
        }
        
        // Mark failed jobs that exceeded max attempts
        $failed = $wpdb->query("
            UPDATE {$table}
            SET status = 'failed',
                error_message = 'Exceeded maximum retry attempts',
                updated_at = NOW()
            WHERE status = 'processing'
            AND updated_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)
            AND attempts >= max_attempts
        ");
        
        if ($failed) {
            error_log("DODO Cron: Marked {$failed} jobs as failed");
        }
    }
    
    /**
     * Get cron status
     */
    public static function get_status() {
        $crons = ['dodo_process_queue', 'dodo_cleanup_cache', 'dodo_cleanup_logs', 'dodo_recover_stuck_jobs'];
        $status = [];
        
        foreach ($crons as $cron) {
            $timestamp = wp_next_scheduled($cron);
            $status[$cron] = [
                'scheduled' => (bool) $timestamp,
                'next_run' => $timestamp ? date('Y-m-d H:i:s', $timestamp) : null,
            ];
        }
        
        return $status;
    }
}

// Initialize cron manager
DODO_Cron_Manager::init();
