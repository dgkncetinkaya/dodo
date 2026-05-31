<?php
/**
 * System Health Check
 * 
 * @package DODO_AI_SEO
 * @since 2.0.0 (Sprint 4 - Task 10)
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Health_Check {
    
    /**
     * Run all health checks
     */
    public static function check_all() {
        return array(
            'openai_api' => self::check_openai_api(),
            'database' => self::check_database(),
            'cron' => self::check_cron(),
            'memory' => self::check_memory(),
            'php' => self::check_php(),
            'extensions' => self::check_extensions(),
            'permissions' => self::check_permissions(),
            'cache' => self::check_cache(),
            'queue' => self::check_queue(),
            'conflicts' => self::check_conflicts(),
        );
    }
    
    /**
     * Check OpenAI API connection
     */
    private static function check_openai_api() {
        $settings = get_option('dodo_ai_seo_settings', array());
        
        if (empty($settings['openai_api_key'])) {
            return array(
                'status' => 'critical',
                'message' => 'OpenAI API anahtarı tanımlanmamış',
            );
        }
        
        // Test API connection
        $openai = new DODO_OpenAI();
        $test = $openai->test_connection();
        
        if (is_wp_error($test)) {
            return array(
                'status' => 'critical',
                'message' => 'OpenAI API bağlantısı başarısız: ' . $test->get_error_message(),
            );
        }
        
        return array(
            'status' => 'healthy',
            'message' => 'OpenAI API bağlantısı çalışıyor',
        );
    }
    
    /**
     * Check database tables
     */
    private static function check_database() {
        require_once DODO_PLUGIN_DIR . 'includes/class-dodo-database.php';
        $health = DODO_Database::get_health();
        
        if (!$health['tables']['all_exist']) {
            return array(
                'status' => 'warning',
                'message' => count($health['tables']['missing']) . ' tablo eksik',
                'details' => $health['tables']['missing'],
            );
        }
        
        if ($health['needs_migration']) {
            return array(
                'status' => 'warning',
                'message' => 'Veritabanı migration gerekli',
            );
        }
        
        return array(
            'status' => 'healthy',
            'message' => 'Tüm tablolar mevcut (' . $health['size_mb'] . ' MB)',
        );
    }
    
    /**
     * Check WP Cron
     */
    private static function check_cron() {
        if (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON) {
            return array(
                'status' => 'warning',
                'message' => 'WP Cron devre dışı - Zamanlanmış işler çalışmayabilir',
            );
        }
        
        $crons = _get_cron_array();
        $dodo_crons = 0;
        
        foreach ($crons as $timestamp => $cron) {
            foreach ($cron as $hook => $details) {
                if (strpos($hook, 'dodo_') === 0) {
                    $dodo_crons++;
                }
            }
        }
        
        return array(
            'status' => 'healthy',
            'message' => $dodo_crons . ' zamanlanmış iş aktif',
        );
    }
    
    /**
     * Check memory
     */
    private static function check_memory() {
        $limit = ini_get('memory_limit');
        $limit_bytes = wp_convert_hr_to_bytes($limit);
        $limit_mb = $limit_bytes / 1024 / 1024;
        
        if ($limit_mb < 128) {
            return array(
                'status' => 'warning',
                'message' => 'PHP memory limit düşük: ' . $limit . ' (Önerilen: 128M+)',
            );
        }
        
        return array(
            'status' => 'healthy',
            'message' => 'PHP memory limit: ' . $limit,
        );
    }
    
    /**
     * Check PHP version
     */
    private static function check_php() {
        $version = PHP_VERSION;
        
        if (version_compare($version, '8.0', '<')) {
            return array(
                'status' => 'critical',
                'message' => 'PHP ' . $version . ' desteklenmiyor (Gerekli: 8.0+)',
            );
        }
        
        if (version_compare($version, '8.3', '<')) {
            return array(
                'status' => 'warning',
                'message' => 'PHP ' . $version . ' (Önerilen: 8.3+)',
            );
        }
        
        return array(
            'status' => 'healthy',
            'message' => 'PHP ' . $version,
        );
    }
    
    /**
     * Check required extensions
     */
    private static function check_extensions() {
        $required = array('curl', 'json', 'mbstring');
        $missing = array();
        
        foreach ($required as $ext) {
            if (!extension_loaded($ext)) {
                $missing[] = $ext;
            }
        }
        
        if (!empty($missing)) {
            return array(
                'status' => 'critical',
                'message' => 'Eksik PHP extension: ' . implode(', ', $missing),
            );
        }
        
        return array(
            'status' => 'healthy',
            'message' => 'Tüm gerekli extension\'lar yüklü',
        );
    }
    
    /**
     * Check file permissions
     */
    private static function check_permissions() {
        $upload_dir = wp_upload_dir();
        
        if (!is_writable($upload_dir['basedir'])) {
            return array(
                'status' => 'critical',
                'message' => 'Upload dizini yazılabilir değil',
            );
        }
        
        return array(
            'status' => 'healthy',
            'message' => 'Dosya izinleri uygun',
        );
    }
    
    /**
     * Check cache status
     */
    private static function check_cache() {
        $cache = new DODO_AI_Cache();
        $stats = $cache->get_stats();
        
        return array(
            'status' => 'healthy',
            'message' => $stats['total_entries'] . ' cache entry, ' . $stats['hit_rate'] . '% hit rate',
        );
    }
    
    /**
     * Check job queue
     */
    private static function check_queue() {
        require_once DODO_PLUGIN_DIR . 'includes/class-dodo-job-queue.php';
        $stats = DODO_Job_Queue::get_stats();
        
        $failed = intval($stats['failed'] ?? 0);
        
        if ($failed > 10) {
            return array(
                'status' => 'warning',
                'message' => $failed . ' başarısız iş var',
            );
        }
        
        return array(
            'status' => 'healthy',
            'message' => $stats['pending'] . ' bekleyen, ' . $stats['completed'] . ' tamamlanmış iş',
        );
    }
    
    /**
     * Check plugin conflicts
     */
    private static function check_conflicts() {
        $conflicts = array();
        
        // Check for known conflicting plugins
        $known_conflicts = array(
            'wp-rocket/wp-rocket.php' => 'WP Rocket - Cache ayarlarını kontrol edin',
            'autoptimize/autoptimize.php' => 'Autoptimize - JS minification sorun çıkarabilir',
        );
        
        foreach ($known_conflicts as $plugin => $message) {
            if (is_plugin_active($plugin)) {
                $conflicts[] = $message;
            }
        }
        
        if (!empty($conflicts)) {
            return array(
                'status' => 'warning',
                'message' => 'Potansiyel çakışma: ' . implode(', ', $conflicts),
            );
        }
        
        return array(
            'status' => 'healthy',
            'message' => 'Bilinen çakışma yok',
        );
    }
    
    /**
     * Get overall health status
     */
    public static function get_overall_status() {
        $checks = self::check_all();
        
        $critical = 0;
        $warnings = 0;
        
        foreach ($checks as $check) {
            if ($check['status'] === 'critical') {
                $critical++;
            } elseif ($check['status'] === 'warning') {
                $warnings++;
            }
        }
        
        if ($critical > 0) {
            return 'critical';
        } elseif ($warnings > 0) {
            return 'warning';
        }
        
        return 'healthy';
    }
    
    /**
     * Run all checks and return comprehensive status (Sprint 4 - Enhanced)
     */
    public function run_all_checks() {
        $checks = self::check_all();
        
        // Calculate overall status
        $critical_count = 0;
        $warning_count = 0;
        
        foreach ($checks as $check) {
            if ($check['status'] === 'critical') {
                $critical_count++;
            } elseif ($check['status'] === 'warning') {
                $warning_count++;
            }
        }
        
        $overall_status = 'healthy';
        if ($critical_count > 0) {
            $overall_status = 'critical';
        } elseif ($warning_count > 0) {
            $overall_status = 'warning';
        }
        
        return array(
            'overall_status' => $overall_status,
            'critical_count' => $critical_count,
            'warning_count' => $warning_count,
            'api_connection' => $checks['openai_api']['status'],
            'database' => $checks['database']['status'],
            'cron' => $checks['cron']['status'],
            'memory' => $checks['memory']['status'],
            'php_version' => $checks['php']['status'],
            'extensions' => $checks['extensions']['status'],
            'checks' => $checks
        );
    }
    
    /**
     * Auto-fix issues (Sprint 4 - Task 4)
     */
    public static function auto_fix($issue_type) {
        switch ($issue_type) {
            case 'missing_tables':
                return self::fix_missing_tables();
            case 'failed_queue':
                return self::fix_failed_queue();
            case 'cache_corrupted':
                return self::fix_cache();
            case 'cron_stuck':
                return self::fix_cron();
            default:
                return array('success' => false, 'message' => 'Unknown issue type');
        }
    }
    
    /**
     * Fix missing tables
     */
    private static function fix_missing_tables() {
        require_once DODO_PLUGIN_DIR . 'includes/class-dodo-database.php';
        $result = DODO_Database::migrate();
        
        if ($result) {
            return array('success' => true, 'message' => 'Tables recreated successfully');
        }
        
        return array('success' => false, 'message' => 'Failed to recreate tables');
    }
    
    /**
     * Fix failed queue jobs
     */
    private static function fix_failed_queue() {
        $queue = new DODO_Job_Queue();
        $count = $queue->cleanup_old_jobs(0);
        
        return array('success' => true, 'message' => sprintf('%d failed jobs cleaned', $count));
    }
    
    /**
     * Fix cache issues
     */
    private static function fix_cache() {
        global $wpdb;
        
        // Clear all DODO transients
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_dodo_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_dodo_%'");
        
        wp_cache_flush();
        
        return array('success' => true, 'message' => 'Cache cleared successfully');
    }
    
    /**
     * Fix cron issues
     */
    private static function fix_cron() {
        // Reschedule main cron jobs
        if (!wp_next_scheduled('dodo_process_job_queue')) {
            wp_schedule_event(time(), 'hourly', 'dodo_process_job_queue');
        }
        
        if (!wp_next_scheduled('dodo_cleanup_old_jobs')) {
            wp_schedule_event(time(), 'daily', 'dodo_cleanup_old_jobs');
        }
        
        return array('success' => true, 'message' => 'Cron jobs rescheduled');
    }
    
    /**
     * Get system metrics (Sprint 4 - Task 4)
     */
    public static function get_system_metrics() {
        global $wpdb;
        
        // API latency
        $start = microtime(true);
        $openai = new DODO_OpenAI();
        $test = $openai->test_connection();
        $api_latency = round((microtime(true) - $start) * 1000); // ms
        
        // DB performance
        $start = microtime(true);
        $wpdb->get_results("SELECT ID FROM {$wpdb->posts} LIMIT 1");
        $db_latency = round((microtime(true) - $start) * 1000); // ms
        
        // Queue health
        $queue = new DODO_Job_Queue();
        $stats = $queue->get_queue_stats();
        
        // Memory usage
        $memory_limit = ini_get('memory_limit');
        $memory_usage = memory_get_usage(true);
        $memory_peak = memory_get_peak_usage(true);
        
        // Token usage (today)
        $usage_logger = new DODO_Usage_Logger();
        $today_usage = $usage_logger->get_usage_stats('today');
        
        return array(
            'api_latency_ms' => $api_latency,
            'db_latency_ms' => $db_latency,
            'queue_pending' => $stats['pending'],
            'queue_failed' => $stats['failed'],
            'memory_limit' => $memory_limit,
            'memory_usage_mb' => round($memory_usage / 1024 / 1024, 2),
            'memory_peak_mb' => round($memory_peak / 1024 / 1024, 2),
            'tokens_today' => $today_usage['total_tokens'] ?? 0,
            'cost_today' => $today_usage['total_cost'] ?? 0,
        );
    }
}
