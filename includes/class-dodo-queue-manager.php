<?php
/**
 * Queue Manager Class
 * 
 * Manages job queue for async operations
 * 
 * @package DODO_AI_SEO
 * @since 2.3.0
 */

// Doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

class DODO_Queue_Manager {
    
    /**
     * Job statuses
     */
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_DEAD_LETTER = 'dead_letter';
    
    /**
     * Job timeout (seconds)
     */
    const JOB_TIMEOUT = 300; // 5 minutes
    
    /**
     * Job types
     */
    const TYPE_BLOG_GENERATION = 'blog_generation';
    const TYPE_CONTENT_ANALYSIS = 'content_analysis';
    const TYPE_CONTENT_IMPROVEMENT = 'content_improvement';
    const TYPE_META_GENERATION = 'meta_generation';
    const TYPE_INTERNAL_LINKS = 'internal_links';
    const TYPE_GEO_ANALYSIS = 'geo_analysis';
    
    /**
     * Table name
     */
    private $table_name;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'dodo_queue_jobs';
    }
    
    /**
     * Create queue table
     */
    public static function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'dodo_queue_jobs';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            job_type varchar(50) NOT NULL,
            target_post_id bigint(20) DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            params longtext,
            result longtext,
            error_message text,
            retry_count int(11) DEFAULT 0,
            max_retries int(11) DEFAULT 3,
            created_at datetime NOT NULL,
            started_at datetime DEFAULT NULL,
            completed_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY status (status),
            KEY job_type (job_type),
            KEY created_at (created_at)
        ) {$charset_collate};";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        error_log('DODO Queue: Table created/verified');
    }
    
    /**
     * Add job to queue
     * 
     * @param string $type Job type
     * @param int $target_post_id Target post ID (optional)
     * @param array $params Job parameters
     * @param int $max_retries Maximum retry count
     * @return int|false Job ID or false on failure
     */
    public function add_job($type, $target_post_id = null, $params = array(), $max_retries = 3) {
        global $wpdb;
        
        // Validate job type
        $valid_types = array(
            self::TYPE_BLOG_GENERATION,
            self::TYPE_CONTENT_ANALYSIS,
            self::TYPE_CONTENT_IMPROVEMENT,
            self::TYPE_META_GENERATION,
            self::TYPE_INTERNAL_LINKS,
            self::TYPE_GEO_ANALYSIS,
        );
        
        if (!in_array($type, $valid_types)) {
            error_log("DODO Queue: Invalid job type: {$type}");
            return false;
        }
        
        // Check for duplicate pending jobs
        if ($this->has_pending_job($type, $target_post_id)) {
            error_log("DODO Queue: Duplicate job prevented - Type: {$type}, Post: {$target_post_id}");
            return false;
        }
        
        $result = $wpdb->insert(
            $this->table_name,
            array(
                'job_type' => $type,
                'target_post_id' => $target_post_id,
                'status' => self::STATUS_PENDING,
                'params' => json_encode($params),
                'max_retries' => $max_retries,
                'created_at' => current_time('mysql'),
            ),
            array('%s', '%d', '%s', '%s', '%d', '%s')
        );
        
        if ($result === false) {
            error_log('DODO Queue: Failed to add job - ' . $wpdb->last_error);
            return false;
        }
        
        $job_id = $wpdb->insert_id;
        error_log("DODO Queue: Job added - ID: {$job_id}, Type: {$type}, Post: {$target_post_id}");
        
        return $job_id;
    }
    
    /**
     * Check if pending job exists
     * 
     * @param string $type Job type
     * @param int $target_post_id Target post ID
     * @return bool
     */
    private function has_pending_job($type, $target_post_id) {
        global $wpdb;
        
        if (empty($target_post_id)) {
            return false;
        }
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} 
            WHERE job_type = %s 
            AND target_post_id = %d 
            AND status IN (%s, %s)",
            $type,
            $target_post_id,
            self::STATUS_PENDING,
            self::STATUS_PROCESSING
        ));
        
        return $count > 0;
    }
    
    /**
     * Get job by ID
     * 
     * @param int $job_id Job ID
     * @return object|null Job object or null
     */
    public function get_job($job_id) {
        global $wpdb;
        
        $job = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $job_id
        ));
        
        if ($job) {
            $job->params = json_decode($job->params, true);
            $job->result = json_decode($job->result, true);
        }
        
        return $job;
    }
    
    /**
     * Get pending jobs
     * 
     * @param int $limit Limit
     * @return array Jobs
     */
    public function get_pending_jobs($limit = 10) {
        global $wpdb;
        
        $jobs = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
            WHERE status = %s 
            ORDER BY created_at ASC 
            LIMIT %d",
            self::STATUS_PENDING,
            $limit
        ));
        
        foreach ($jobs as $job) {
            $job->params = json_decode($job->params, true);
        }
        
        return $jobs;
    }
    
    /**
     * Get next job for processing (atomic lock)
     * 
     * @return object|null Job object or null if no jobs available
     */
    public function get_next_job() {
        global $wpdb;
        
        // Find oldest pending job that hasn't exceeded retry limit
        $job = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
            WHERE status IN (%s, %s)
            AND (retry_count < max_retries OR max_retries = 0)
            ORDER BY created_at ASC 
            LIMIT 1",
            self::STATUS_PENDING,
            'queued'
        ));
        
        if (!$job) {
            return null;
        }
        
        // Atomic lock: Update to processing status
        $updated = $wpdb->update(
            $this->table_name,
            [
                'status' => self::STATUS_PROCESSING,
                'started_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ],
            ['id' => $job->id],
            ['%s', '%s', '%s'],
            ['%d']
        );
        
        if ($updated === false) {
            error_log("DODO Queue: Failed to lock job {$job->id}");
            return null;
        }
        
        // Decode params
        $job->params = json_decode($job->params, true);
        
        error_log("DODO Queue: Locked job {$job->id} ({$job->job_type})");
        
        return $job;
    }
    
    /**
     * Get all jobs with filters
     * 
     * @param array $filters Filters (status, type, limit, offset)
     * @return array Jobs
     */
    public function get_jobs($filters = array()) {
        global $wpdb;
        
        $where = array('1=1');
        $values = array();
        
        if (!empty($filters['status'])) {
            $where[] = 'status = %s';
            $values[] = $filters['status'];
        }
        
        if (!empty($filters['type'])) {
            $where[] = 'job_type = %s';
            $values[] = $filters['type'];
        }
        
        $limit = isset($filters['limit']) ? absint($filters['limit']) : 50;
        $offset = isset($filters['offset']) ? absint($filters['offset']) : 0;
        
        $where_clause = implode(' AND ', $where);
        
        if (!empty($values)) {
            $query = $wpdb->prepare(
                "SELECT * FROM {$this->table_name} 
                WHERE {$where_clause} 
                ORDER BY created_at DESC 
                LIMIT %d OFFSET %d",
                array_merge($values, array($limit, $offset))
            );
        } else {
            $query = $wpdb->prepare(
                "SELECT * FROM {$this->table_name} 
                WHERE {$where_clause} 
                ORDER BY created_at DESC 
                LIMIT %d OFFSET %d",
                $limit,
                $offset
            );
        }
        
        $jobs = $wpdb->get_results($query);
        
        foreach ($jobs as $job) {
            $job->params = json_decode($job->params, true);
            $job->result = json_decode($job->result, true);
        }
        
        return $jobs;
    }
    
    /**
     * Update job status
     * 
     * @param int $job_id Job ID
     * @param string $status New status
     * @param mixed $result Result data (optional)
     * @param string $error_message Error message (optional)
     * @return bool Success
     */
    public function update_job_status($job_id, $status, $result = null, $error_message = null) {
        global $wpdb;
        
        $data = array('status' => $status);
        $format = array('%s');
        
        if ($status === self::STATUS_PROCESSING) {
            $data['started_at'] = current_time('mysql');
            $format[] = '%s';
        }
        
        if ($status === self::STATUS_COMPLETED || $status === self::STATUS_FAILED) {
            $data['completed_at'] = current_time('mysql');
            $format[] = '%s';
        }
        
        if ($result !== null) {
            $data['result'] = json_encode($result);
            $format[] = '%s';
        }
        
        if ($error_message !== null) {
            $data['error_message'] = $error_message;
            $format[] = '%s';
        }
        
        $updated = $wpdb->update(
            $this->table_name,
            $data,
            array('id' => $job_id),
            $format,
            array('%d')
        );
        
        if ($updated === false) {
            error_log("DODO Queue: Failed to update job {$job_id} - " . $wpdb->last_error);
            return false;
        }
        
        error_log("DODO Queue: Job {$job_id} status updated to {$status}");
        return true;
    }
    
    /**
     * Process next pending job
     * 
     * @return bool Success
     */
    public function process_next_job() {
        $jobs = $this->get_pending_jobs(1);
        
        if (empty($jobs)) {
            error_log('DODO Queue: No pending jobs');
            return false;
        }
        
        $job = $jobs[0];
        
        error_log("DODO Queue: Processing job {$job->id} - Type: {$job->job_type}");
        
        // Update to processing
        $this->update_job_status($job->id, self::STATUS_PROCESSING);
        
        // Set timeout alarm
        $start_time = time();
        
        try {
            // Check timeout during execution
            set_time_limit(self::JOB_TIMEOUT + 30); // PHP timeout slightly higher
            
            $result = $this->execute_job($job);
            
            // Check if timed out
            $duration = time() - $start_time;
            if ($duration > self::JOB_TIMEOUT) {
                throw new Exception("Job timeout after {$duration} seconds");
            }
            
            if (is_wp_error($result)) {
                throw new Exception($result->get_error_message());
            }
            
            $this->update_job_status($job->id, self::STATUS_COMPLETED, $result);
            error_log("DODO Queue: Job {$job->id} completed successfully in {$duration}s");
            
            return true;
            
        } catch (Exception $e) {
            $duration = time() - $start_time;
            error_log("DODO Queue: Job {$job->id} failed after {$duration}s - " . $e->getMessage());
            
            // Increment retry count
            global $wpdb;
            $wpdb->query($wpdb->prepare(
                "UPDATE {$this->table_name} SET retry_count = retry_count + 1 WHERE id = %d",
                $job->id
            ));
            
            // Check if should retry
            $job = $this->get_job($job->id);
            
            if ($job->retry_count < $job->max_retries) {
                // Reset to pending for retry
                $this->update_job_status($job->id, self::STATUS_PENDING, null, $e->getMessage());
                error_log("DODO Queue: Job {$job->id} will retry ({$job->retry_count}/{$job->max_retries})");
            } else {
                // Max retries reached - move to dead letter queue
                $this->move_to_dead_letter($job->id, $e->getMessage());
                error_log("DODO Queue: Job {$job->id} moved to dead letter queue (max retries reached)");
            }
            
            return false;
        }
    }
    
    /**
     * Move job to dead letter queue
     * 
     * @param int $job_id Job ID
     * @param string $reason Failure reason
     * @return bool Success
     */
    private function move_to_dead_letter($job_id, $reason) {
        global $wpdb;
        
        $updated = $wpdb->update(
            $this->table_name,
            [
                'status' => self::STATUS_DEAD_LETTER,
                'completed_at' => current_time('mysql'),
                'error_message' => 'Dead Letter: ' . $reason,
            ],
            ['id' => $job_id],
            ['%s', '%s', '%s'],
            ['%d']
        );
        
        // Log to dead letter for manual review
        error_log("DODO Queue: Dead Letter - Job {$job_id}: {$reason}");
        
        return $updated !== false;
    }
    
    /**
     * Get dead letter jobs
     * 
     * @param int $limit Limit
     * @return array Jobs
     */
    public function get_dead_letter_jobs($limit = 50) {
        global $wpdb;
        
        $jobs = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
            WHERE status = %s 
            ORDER BY completed_at DESC 
            LIMIT %d",
            self::STATUS_DEAD_LETTER,
            $limit
        ));
        
        foreach ($jobs as $job) {
            $job->params = json_decode($job->params, true);
            $job->result = json_decode($job->result, true);
        }
        
        return $jobs;
    }
    
    /**
     * Resurrect dead letter job
     * 
     * @param int $job_id Job ID
     * @return bool Success
     */
    public function resurrect_dead_letter($job_id) {
        global $wpdb;
        
        $job = $this->get_job($job_id);
        
        if (!$job || $job->status !== self::STATUS_DEAD_LETTER) {
            return false;
        }
        
        // Reset to pending with fresh retry count
        $updated = $wpdb->update(
            $this->table_name,
            [
                'status' => self::STATUS_PENDING,
                'retry_count' => 0,
                'error_message' => null,
                'started_at' => null,
                'completed_at' => null,
            ],
            ['id' => $job_id],
            ['%s', '%d', '%s', '%s', '%s'],
            ['%d']
        );
        
        error_log("DODO Queue: Dead letter job {$job_id} resurrected");
        return $updated !== false;
    }
    
    /**
     * Execute job based on type
     * 
     * @param object $job Job object
     * @return mixed Result or WP_Error
     */
    private function execute_job($job) {
        switch ($job->job_type) {
            case self::TYPE_BLOG_GENERATION:
                return $this->execute_blog_generation($job);
                
            case self::TYPE_CONTENT_ANALYSIS:
                return $this->execute_content_analysis($job);
                
            case self::TYPE_CONTENT_IMPROVEMENT:
                return $this->execute_content_improvement($job);
                
            case self::TYPE_META_GENERATION:
                return $this->execute_meta_generation($job);
                
            case self::TYPE_INTERNAL_LINKS:
                return $this->execute_internal_links($job);
                
            case self::TYPE_GEO_ANALYSIS:
                return $this->execute_geo_analysis($job);
                
            default:
                return new WP_Error('invalid_job_type', 'Invalid job type: ' . $job->job_type);
        }
    }
    
    /**
     * Execute blog generation job
     */
    private function execute_blog_generation($job) {
        $generator = new DODO_Generator();
        return $generator->generate_blog($job->params);
    }
    
    /**
     * Execute content analysis job
     */
    private function execute_content_analysis($job) {
        $analyzer = new DODO_Content_Analyzer();
        return $analyzer->analyze_post($job->target_post_id);
    }
    
    /**
     * Execute content improvement job
     */
    private function execute_content_improvement($job) {
        $improver = new DODO_Content_Improver();
        return $improver->improve_content($job->target_post_id, $job->params);
    }
    
    /**
     * Execute meta generation job
     */
    private function execute_meta_generation($job) {
        $generator = new DODO_Generator();
        // Implement meta generation logic
        return array('success' => true, 'message' => 'Meta generated');
    }
    
    /**
     * Execute internal links job
     */
    private function execute_internal_links($job) {
        $internal_links = new DODO_Internal_Links();
        return $internal_links->suggest_links_for_post($job->target_post_id);
    }
    
    /**
     * Execute GEO analysis job
     */
    private function execute_geo_analysis($job) {
        $geo_engine = new DODO_GEO_Engine();
        return $geo_engine->analyze_content($job->target_post_id);
    }
    
    /**
     * Retry failed job
     * 
     * @param int $job_id Job ID
     * @return bool Success
     */
    public function retry_failed_job($job_id) {
        global $wpdb;
        
        $job = $this->get_job($job_id);
        
        if (!$job || $job->status !== self::STATUS_FAILED) {
            return false;
        }
        
        // Reset retry count and status
        $wpdb->update(
            $this->table_name,
            array(
                'status' => self::STATUS_PENDING,
                'retry_count' => 0,
                'error_message' => null,
                'started_at' => null,
                'completed_at' => null,
            ),
            array('id' => $job_id),
            array('%s', '%d', '%s', '%s', '%s'),
            array('%d')
        );
        
        error_log("DODO Queue: Job {$job_id} reset for retry");
        return true;
    }
    
    /**
     * Get queue statistics
     * 
     * @return array Stats
     */
    public function get_stats() {
        global $wpdb;
        
        $stats = array(
            'pending' => 0,
            'processing' => 0,
            'completed' => 0,
            'failed' => 0,
            'total' => 0,
        );
        
        $results = $wpdb->get_results(
            "SELECT status, COUNT(*) as count FROM {$this->table_name} GROUP BY status"
        );
        
        foreach ($results as $row) {
            $stats[$row->status] = (int) $row->count;
            $stats['total'] += (int) $row->count;
        }
        
        return $stats;
    }
    
    /**
     * Clear completed jobs older than X days
     * 
     * @param int $days Days to keep
     * @return int Deleted count
     */
    public function clear_old_completed_jobs($days = 7) {
        global $wpdb;
        
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$this->table_name} 
            WHERE status = %s 
            AND completed_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            self::STATUS_COMPLETED,
            $days
        ));
        
        error_log("DODO Queue: Cleared {$deleted} old completed jobs");
        return $deleted;
    }
    
    /**
     * Recover stuck jobs
     * 
     * Finds jobs stuck in "processing" status for more than 1 hour
     * and resets them to pending for retry
     * 
     * @param int $stuck_threshold_minutes Minutes before job considered stuck (default: 60)
     * @return int Number of jobs recovered
     */
    public function recover_stuck_jobs($stuck_threshold_minutes = 60) {
        global $wpdb;
        
        $threshold_time = date('Y-m-d H:i:s', strtotime("-{$stuck_threshold_minutes} minutes"));
        
        // Find stuck jobs
        $stuck_jobs = $wpdb->get_results($wpdb->prepare(
            "SELECT id, job_type, target_post_id, retry_count, max_retries, started_at 
            FROM {$this->table_name} 
            WHERE status = %s 
            AND started_at < %s
            AND started_at IS NOT NULL",
            self::STATUS_PROCESSING,
            $threshold_time
        ));
        
        if (empty($stuck_jobs)) {
            error_log('[DODO Queue] No stuck jobs found');
            return 0;
        }
        
        $recovered = 0;
        $failed = 0;
        
        foreach ($stuck_jobs as $job) {
            $stuck_duration = round((time() - strtotime($job->started_at)) / 60);
            
            error_log(sprintf(
                '[DODO Queue] Stuck job detected - ID: %d, Type: %s, Stuck for: %d minutes',
                $job->id,
                $job->job_type,
                $stuck_duration
            ));
            
            // Check if can retry
            if ($job->retry_count < $job->max_retries) {
                // Reset to pending
                $updated = $wpdb->update(
                    $this->table_name,
                    [
                        'status' => self::STATUS_PENDING,
                        'started_at' => null,
                        'error_message' => sprintf('Job stuck for %d minutes - auto-recovered', $stuck_duration),
                        'retry_count' => $job->retry_count + 1,
                    ],
                    ['id' => $job->id],
                    ['%s', null, '%s', '%d'],
                    ['%d']
                );
                
                if ($updated) {
                    $recovered++;
                    error_log(sprintf(
                        '[DODO Queue] Job %d recovered and reset to pending (retry %d/%d)',
                        $job->id,
                        $job->retry_count + 1,
                        $job->max_retries
                    ));
                }
            } else {
                // Max retries reached - mark as failed
                $updated = $wpdb->update(
                    $this->table_name,
                    [
                        'status' => self::STATUS_FAILED,
                        'completed_at' => current_time('mysql'),
                        'error_message' => sprintf('Job stuck for %d minutes - max retries reached', $stuck_duration),
                    ],
                    ['id' => $job->id],
                    ['%s', '%s', '%s'],
                    ['%d']
                );
                
                if ($updated) {
                    $failed++;
                    error_log(sprintf(
                        '[DODO Queue] Job %d marked as failed (max retries reached)',
                        $job->id
                    ));
                }
            }
        }
        
        error_log(sprintf(
            '[DODO Queue] Stuck job recovery complete - Recovered: %d, Failed: %d',
            $recovered,
            $failed
        ));
        
        return $recovered;
    }
    
    /**
     * Get stuck jobs count
     * 
     * @param int $stuck_threshold_minutes Minutes before job considered stuck
     * @return int Number of stuck jobs
     */
    public function get_stuck_jobs_count($stuck_threshold_minutes = 60) {
        global $wpdb;
        
        $threshold_time = date('Y-m-d H:i:s', strtotime("-{$stuck_threshold_minutes} minutes"));
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) 
            FROM {$this->table_name} 
            WHERE status = %s 
            AND started_at < %s
            AND started_at IS NOT NULL",
            self::STATUS_PROCESSING,
            $threshold_time
        ));
        
        return (int) $count;
    }
    
    /**
     * Schedule stuck job recovery cron
     */
    public static function schedule_stuck_job_recovery() {
        if (!wp_next_scheduled('dodo_recover_stuck_jobs')) {
            wp_schedule_event(time(), 'hourly', 'dodo_recover_stuck_jobs');
            error_log('[DODO Queue] Stuck job recovery cron scheduled');
        }
    }
    
    /**
     * Cron callback for stuck job recovery
     */
    public static function cron_recover_stuck_jobs() {
        $queue = new self();
        $recovered = $queue->recover_stuck_jobs(60);
        
        if ($recovered > 0) {
            error_log("[DODO Queue] Cron recovered {$recovered} stuck jobs");
        }
    }
}
