<?php
/**
 * Reliability Manager
 * 
 * Handles retry logic, partial saves, graceful failures, and recovery
 * Enterprise-grade reliability layer for DODO AI SEO
 *
 * @package DODO_AI_SEO
 * @since 2.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Reliability_Manager {
    
    /**
     * Retry configuration
     */
    const MAX_RETRIES = 3;
    const INITIAL_BACKOFF = 5; // seconds
    const MAX_BACKOFF = 300; // 5 minutes
    const BACKOFF_MULTIPLIER = 2;
    
    /**
     * Timeout configuration
     */
    const GENERATION_TIMEOUT = 300; // 5 minutes
    const HEARTBEAT_INTERVAL = 30; // seconds
    
    /**
     * Partial save table
     */
    private $partial_table;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->partial_table = $wpdb->prefix . 'dodo_partial_saves';
    }
    
    /**
     * Execute with retry logic
     *
     * @param callable $callback Function to execute
     * @param array $args Arguments for callback
     * @param string $operation_name Operation identifier
     * @return mixed Result or WP_Error
     */
    public function execute_with_retry($callback, $args = [], $operation_name = 'operation') {
        $attempt = 0;
        $last_error = null;
        
        while ($attempt < self::MAX_RETRIES) {
            $attempt++;
            
            try {
                error_log(sprintf(
                    '[DODO Reliability] Executing %s (attempt %d/%d)',
                    $operation_name,
                    $attempt,
                    self::MAX_RETRIES
                ));
                
                $result = call_user_func_array($callback, $args);
                
                if (!is_wp_error($result)) {
                    if ($attempt > 1) {
                        error_log(sprintf(
                            '[DODO Reliability] %s succeeded on attempt %d',
                            $operation_name,
                            $attempt
                        ));
                    }
                    return $result;
                }
                
                $last_error = $result;
                
            } catch (Exception $e) {
                $last_error = new WP_Error(
                    'execution_exception',
                    $e->getMessage()
                );
            }
            
            // Don't retry on final attempt
            if ($attempt >= self::MAX_RETRIES) {
                break;
            }
            
            // Calculate backoff
            $backoff = $this->calculate_backoff($attempt);
            
            error_log(sprintf(
                '[DODO Reliability] %s failed (attempt %d), retrying in %d seconds. Error: %s',
                $operation_name,
                $attempt,
                $backoff,
                is_wp_error($last_error) ? $last_error->get_error_message() : 'Unknown'
            ));
            
            sleep($backoff);
        }
        
        // All retries exhausted
        error_log(sprintf(
            '[DODO Reliability] %s failed after %d attempts',
            $operation_name,
            self::MAX_RETRIES
        ));
        
        return $last_error ?? new WP_Error(
            'max_retries_exceeded',
            sprintf('Operation failed after %d attempts', self::MAX_RETRIES)
        );
    }
    
    /**
     * Calculate exponential backoff
     */
    private function calculate_backoff($attempt) {
        $backoff = self::INITIAL_BACKOFF * pow(self::BACKOFF_MULTIPLIER, $attempt - 1);
        return min($backoff, self::MAX_BACKOFF);
    }
    
    /**
     * Save partial generation progress
     *
     * @param int $job_id Job ID
     * @param array $partial_data Partial generation data
     * @return bool Success
     */
    public function save_partial_progress($job_id, $partial_data) {
        global $wpdb;
        
        $data = [
            'job_id' => $job_id,
            'partial_data' => json_encode($partial_data),
            'saved_at' => current_time('mysql'),
        ];
        
        // Check if partial save exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->partial_table} WHERE job_id = %d",
            $job_id
        ));
        
        if ($existing) {
            // Update existing
            $result = $wpdb->update(
                $this->partial_table,
                $data,
                ['job_id' => $job_id],
                ['%d', '%s', '%s'],
                ['%d']
            );
        } else {
            // Insert new
            $result = $wpdb->insert(
                $this->partial_table,
                $data,
                ['%d', '%s', '%s']
            );
        }
        
        if ($result !== false) {
            error_log(sprintf(
                '[DODO Reliability] Partial progress saved for job %d',
                $job_id
            ));
            return true;
        }
        
        error_log(sprintf(
            '[DODO Reliability] Failed to save partial progress for job %d',
            $job_id
        ));
        return false;
    }
    
    /**
     * Recover partial generation
     *
     * @param int $job_id Job ID
     * @return array|null Partial data or null
     */
    public function recover_partial_progress($job_id) {
        global $wpdb;
        
        $partial_json = $wpdb->get_var($wpdb->prepare(
            "SELECT partial_data FROM {$this->partial_table} WHERE job_id = %d",
            $job_id
        ));
        
        if ($partial_json) {
            $partial_data = json_decode($partial_json, true);
            
            error_log(sprintf(
                '[DODO Reliability] Recovered partial progress for job %d',
                $job_id
            ));
            
            return $partial_data;
        }
        
        return null;
    }
    
    /**
     * Create partial draft from incomplete generation
     *
     * @param array $partial_data Partial generation data
     * @param array $params Original parameters
     * @return int|WP_Error Post ID or error
     */
    public function create_partial_draft($partial_data, $params) {
        $content = $this->build_partial_content($partial_data);
        
        $post_data = [
            'post_title' => $params['focus_keyword'] . ' (Partial Draft)',
            'post_content' => $content,
            'post_status' => 'draft',
            'post_type' => 'post',
            'meta_input' => [
                '_dodo_partial_recovery' => true,
                '_dodo_partial_data' => json_encode($partial_data),
                '_dodo_original_params' => json_encode($params),
            ],
        ];
        
        $post_id = wp_insert_post($post_data, true);
        
        if (!is_wp_error($post_id)) {
            error_log(sprintf(
                '[DODO Reliability] Partial draft created: Post ID %d',
                $post_id
            ));
        }
        
        return $post_id;
    }
    
    /**
     * Build content from partial data
     */
    private function build_partial_content($partial_data) {
        $content = "<!-- PARTIAL GENERATION RECOVERY -->\n\n";
        
        if (!empty($partial_data['outline'])) {
            $content .= "<!-- Outline completed -->\n";
        }
        
        if (!empty($partial_data['introduction'])) {
            $content .= $partial_data['introduction'] . "\n\n";
        }
        
        if (!empty($partial_data['sections'])) {
            foreach ($partial_data['sections'] as $section) {
                $content .= $section . "\n\n";
            }
        }
        
        if (!empty($partial_data['faq'])) {
            $content .= $partial_data['faq'] . "\n\n";
        }
        
        if (!empty($partial_data['conclusion'])) {
            $content .= $partial_data['conclusion'] . "\n\n";
        }
        
        $content .= "<!-- Generation incomplete - recovered partial content -->";
        
        return $content;
    }
    
    /**
     * Graceful fail handler
     *
     * @param WP_Error $error Error object
     * @param string $context Error context
     * @param array $recovery_data Data for recovery
     * @return array Graceful fail response
     */
    public function handle_graceful_fail($error, $context, $recovery_data = []) {
        $error_code = $error->get_error_code();
        $error_message = $error->get_error_message();
        
        error_log(sprintf(
            '[DODO Reliability] Graceful fail in %s: %s - %s',
            $context,
            $error_code,
            $error_message
        ));
        
        // Determine recovery strategy
        $strategy = $this->determine_recovery_strategy($error_code, $context);
        
        $response = [
            'success' => false,
            'error' => $error_message,
            'error_code' => $error_code,
            'context' => $context,
            'recovery_strategy' => $strategy,
            'user_message' => $this->get_user_friendly_message($error_code, $context),
            'can_retry' => $this->can_retry($error_code),
            'partial_save_available' => !empty($recovery_data),
        ];
        
        // Save recovery data if available
        if (!empty($recovery_data) && isset($recovery_data['job_id'])) {
            $this->save_partial_progress($recovery_data['job_id'], $recovery_data);
            $response['recovery_job_id'] = $recovery_data['job_id'];
        }
        
        return $response;
    }
    
    /**
     * Determine recovery strategy
     */
    private function determine_recovery_strategy($error_code, $context) {
        $strategies = [
            'openai_timeout' => 'retry_with_backoff',
            'openai_rate_limit' => 'retry_after_delay',
            'openai_quota_exceeded' => 'notify_admin',
            'generation_failed' => 'partial_save',
            'db_error' => 'retry_immediate',
            'network_error' => 'retry_with_backoff',
        ];
        
        return $strategies[$error_code] ?? 'log_and_notify';
    }
    
    /**
     * Get user-friendly error message
     */
    private function get_user_friendly_message($error_code, $context) {
        $messages = [
            'openai_timeout' => 'AI servisi yanıt vermedi. Lütfen tekrar deneyin.',
            'openai_rate_limit' => 'API limit aşıldı. Lütfen birkaç dakika bekleyin.',
            'openai_quota_exceeded' => 'Aylık kullanım kotası doldu. Lütfen yönetici ile iletişime geçin.',
            'generation_failed' => 'İçerik oluşturma başarısız. Kısmi içerik kaydedildi.',
            'db_error' => 'Veritabanı hatası. Lütfen tekrar deneyin.',
            'network_error' => 'Bağlantı hatası. Lütfen internet bağlantınızı kontrol edin.',
        ];
        
        return $messages[$error_code] ?? 'Bir hata oluştu. Lütfen tekrar deneyin.';
    }
    
    /**
     * Check if error is retryable
     */
    private function can_retry($error_code) {
        $retryable = [
            'openai_timeout',
            'openai_rate_limit',
            'db_error',
            'network_error',
        ];
        
        return in_array($error_code, $retryable);
    }
    
    /**
     * Check for timeout
     *
     * @param int $start_time Start timestamp
     * @return bool Is timed out
     */
    public function is_timed_out($start_time) {
        $elapsed = time() - $start_time;
        return $elapsed >= self::GENERATION_TIMEOUT;
    }
    
    /**
     * Update heartbeat
     *
     * @param int $job_id Job ID
     * @return bool Success
     */
    public function update_heartbeat($job_id) {
        return update_option(
            'dodo_heartbeat_' . $job_id,
            time(),
            false // Don't autoload
        );
    }
    
    /**
     * Check if job is stuck
     *
     * @param int $job_id Job ID
     * @return bool Is stuck
     */
    public function is_job_stuck($job_id) {
        $last_heartbeat = get_option('dodo_heartbeat_' . $job_id, 0);
        
        if ($last_heartbeat === 0) {
            return false; // No heartbeat yet
        }
        
        $elapsed = time() - $last_heartbeat;
        return $elapsed > (self::HEARTBEAT_INTERVAL * 3); // 3x heartbeat interval
    }
    
    /**
     * Create partial saves table
     */
    public static function create_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dodo_partial_saves';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            job_id bigint(20) NOT NULL,
            partial_data longtext NOT NULL,
            saved_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY job_id (job_id),
            KEY saved_at (saved_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        error_log('[DODO Reliability] Partial saves table created');
    }
    
    /**
     * Clean old partial saves
     *
     * @param int $days Days to keep
     * @return int Deleted count
     */
    public function clean_old_partial_saves($days = 7) {
        global $wpdb;
        
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$this->partial_table} 
            WHERE saved_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ));
        
        if ($deleted > 0) {
            error_log(sprintf(
                '[DODO Reliability] Cleaned %d old partial saves',
                $deleted
            ));
        }
        
        return $deleted;
    }
}
