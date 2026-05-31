<?php
/**
 * Health Alerts
 * 
 * Admin notices for critical system issues
 * 
 * @package DODO_AI_SEO
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Health_Alerts {
    
    /**
     * Initialize alerts
     */
    public static function init() {
        add_action('admin_notices', [__CLASS__, 'show_alerts']);
    }
    
    /**
     * Show admin alerts
     */
    public static function show_alerts() {
        // Only show to admins
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $alerts = self::check_health();
        
        foreach ($alerts as $alert) {
            self::render_alert($alert);
        }
    }
    
    /**
     * Check system health
     * 
     * @return array Alerts
     */
    private static function check_health() {
        $alerts = [];
        
        // Check queue stuck
        if (class_exists('DODO_Queue_Manager')) {
            $queue = new DODO_Queue_Manager();
            $stuck_count = method_exists($queue, 'get_stuck_jobs_count') 
                ? $queue->get_stuck_jobs_count(60) 
                : 0;
            
            if ($stuck_count > 0) {
                $alerts[] = [
                    'type' => 'error',
                    'message' => sprintf(
                        __('%d iş kuyruğunda takılı kaldı. <a href="%s">Canlı Sistem Durumu</a> sayfasından kontrol edin.', 'dodo-ai-seo'),
                        $stuck_count,
                        admin_url('admin.php?page=dodo-production-readiness')
                    ),
                    'dismissible' => true,
                ];
            }
        }
        
        // Check API budget
        $today_usage = self::get_api_usage();
        if ($today_usage['total_cost'] > 50) {
            $alerts[] = [
                'type' => 'warning',
                'message' => sprintf(
                    __('API bütçesi yüksek: $%.2f bugün kullanıldı. Kullanımı kontrol edin.', 'dodo-ai-seo'),
                    $today_usage['total_cost']
                ),
                'dismissible' => true,
            ];
        }
        
        // Check learning unstable
        if (class_exists('DODO_Learning_Quality')) {
            $quality = new DODO_Learning_Quality();
            if (method_exists($quality, 'calculate_quality_score')) {
                $score_data = $quality->calculate_quality_score();
                $score = is_array($score_data) ? ($score_data['score'] ?? 0) : $score_data;
                $sample_count = is_array($score_data) ? ($score_data['sample_count'] ?? 0) : 0;
                
                if ($sample_count >= 5 && $score < 40) {
                    $alerts[] = [
                        'type' => 'warning',
                        'message' => sprintf(
                            __('Öğrenme kalitesi düşük (%d/100). <a href="%s">Öğrenme Kontrolü</a> sayfasından kontrol edin.', 'dodo-ai-seo'),
                            $score,
                            admin_url('admin.php?page=dodo-learning-control')
                        ),
                        'dismissible' => true,
                    ];
                }
            }
        }
        
        // Check cache issues
        if (class_exists('DODO_Performance_Cache')) {
            $cache = new DODO_Performance_Cache();
            if (method_exists($cache, 'get_stats')) {
                $stats = $cache->get_stats();
                $total_size = $stats['total_size_mb'] ?? 0;
                
                if ($total_size > 500) {
                    $alerts[] = [
                        'type' => 'warning',
                        'message' => sprintf(
                            __('Önbellek boyutu çok büyük: %.2f MB. Temizlik yapılmalı.', 'dodo-ai-seo'),
                            $total_size
                        ),
                        'dismissible' => true,
                    ];
                }
            }
        }
        
        // Check missing tables
        if (class_exists('DODO_Migrations')) {
            $schema = DODO_Migrations::verify_schema();
            if (!$schema['complete']) {
                $alerts[] = [
                    'type' => 'error',
                    'message' => sprintf(
                        __('%d veritabanı tablosu eksik. <a href="%s">Canlı Sistem Durumu</a> sayfasından onarın.', 'dodo-ai-seo'),
                        count($schema['missing']),
                        admin_url('admin.php?page=dodo-production-readiness')
                    ),
                    'dismissible' => false,
                ];
            }
        }
        
        return $alerts;
    }
    
    /**
     * Get API usage
     * 
     * @return array Usage data
     */
    private static function get_api_usage() {
        global $wpdb;
        $table = $wpdb->prefix . 'dodo_telemetry';
        
        $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
        
        if (!$exists) {
            return ['total_tokens' => 0, 'total_cost' => 0];
        }
        
        $result = $wpdb->get_row("
            SELECT 
                SUM(total_tokens) as total_tokens,
                SUM(estimated_cost) as total_cost
            FROM {$table}
            WHERE DATE(created_at) = CURDATE()
        ");
        
        return [
            'total_tokens' => $result->total_tokens ?? 0,
            'total_cost' => $result->total_cost ?? 0,
        ];
    }
    
    /**
     * Render alert
     * 
     * @param array $alert Alert data
     */
    private static function render_alert($alert) {
        $class = 'notice notice-' . $alert['type'];
        if ($alert['dismissible']) {
            $class .= ' is-dismissible';
        }
        
        printf(
            '<div class="%s"><p><strong>DODO AI SEO:</strong> %s</p></div>',
            esc_attr($class),
            $alert['message']
        );
    }
}

// Initialize
DODO_Health_Alerts::init();
