<?php
/**
 * Missed Schedule Fixer Sınıfı
 * 
 * WordPress "Zamanlama kaçırıldı" sorununu düzeltir
 *
 * @package DODO_AI_SEO
 * @since 1.1.1
 */

// Doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

class DODO_Missed_Schedule_Fixer {
    
    /**
     * Transient key
     */
    private $transient_key = 'dodo_missed_schedule_check';
    
    /**
     * Check interval (5 minutes)
     */
    private $check_interval = 300; // 5 dakika = 300 saniye
    
    /**
     * Constructor
     */
    public function __construct() {
        // Admin init hook ile hafif kontrol
        add_action('admin_init', array($this, 'maybe_check_missed_schedules'));
    }
    
    /**
     * Transient kontrolü ile 5 dakikada bir çalış
     */
    public function maybe_check_missed_schedules() {
        // Transient var mı kontrol et
        $last_check = get_transient($this->transient_key);
        
        if ($last_check !== false) {
            // Son 5 dakika içinde kontrol edilmiş, atla
            return;
        }
        
        // Transient set et (5 dakika)
        set_transient($this->transient_key, time(), $this->check_interval);
        
        // Missed schedule kontrolü yap
        $this->check_and_fix_missed_schedules();
    }
    
    /**
     * Missed schedule kontrolü ve düzeltme
     * 
     * @return array
     */
    public function check_and_fix_missed_schedules() {
        error_log("[DODO SCHEDULER] Missed schedule check started");
        
        global $wpdb;
        
        // Geçmiş tarihli future postları bul
        $current_time = current_time('mysql');
        
        $missed_posts = $wpdb->get_results($wpdb->prepare(
            "SELECT ID, post_title, post_date 
            FROM {$wpdb->posts} 
            WHERE post_status = 'future' 
            AND post_date < %s 
            AND post_type = 'post'
            ORDER BY post_date ASC
            LIMIT 50",
            $current_time
        ));
        
        if (empty($missed_posts)) {
            error_log("[DODO SCHEDULER] No missed schedules found");
            return array(
                'success' => true,
                'fixed' => 0,
                'message' => __('Kaçırılan zamanlama bulunamadı.', 'dodo-ai-seo')
            );
        }
        
        error_log("[DODO SCHEDULER] Found " . count($missed_posts) . " missed schedule(s)");
        
        $fixed_count = 0;
        $failed_posts = array();
        
        foreach ($missed_posts as $post) {
            error_log("[DODO SCHEDULER] Missed post found: ID #{$post->ID} - {$post->post_title} (scheduled: {$post->post_date})");
            
            // Post'u publish yap
            $result = wp_update_post(array(
                'ID' => $post->ID,
                'post_status' => 'publish',
                // post_date korunuyor (orijinal zamanlanan tarih)
            ), true);
            
            if (is_wp_error($result)) {
                error_log("[DODO SCHEDULER] Failed to publish post #{$post->ID}: " . $result->get_error_message());
                $failed_posts[] = array(
                    'id' => $post->ID,
                    'title' => $post->post_title,
                    'error' => $result->get_error_message()
                );
            } else {
                error_log("[DODO SCHEDULER] Published missed post: ID #{$post->ID} - {$post->post_title}");
                $fixed_count++;
            }
        }
        
        error_log("[DODO SCHEDULER] Missed schedule check completed - Fixed: {$fixed_count}, Failed: " . count($failed_posts));
        
        return array(
            'success' => true,
            'fixed' => $fixed_count,
            'failed' => count($failed_posts),
            'failed_posts' => $failed_posts,
            'message' => sprintf(
                __('%d kaçırılan zamanlama düzeltildi.', 'dodo-ai-seo'),
                $fixed_count
            )
        );
    }
    
    /**
     * Kaçırılan zamanlamaları say
     * 
     * @return int
     */
    public function count_missed_schedules() {
        global $wpdb;
        
        $current_time = current_time('mysql');
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) 
            FROM {$wpdb->posts} 
            WHERE post_status = 'future' 
            AND post_date < %s 
            AND post_type = 'post'",
            $current_time
        ));
        
        return absint($count);
    }
}
