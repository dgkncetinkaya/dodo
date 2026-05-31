<?php
/**
 * Scheduled Publisher Sınıfı
 * 
 * Zamanlanmış yayınlama sistemini yönetir
 *
 * @package DODO_AI_SEO
 * @since 1.0.3
 */

// Doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

class DODO_Scheduled_Publisher {
    
    /**
     * Settings instance
     */
    private $settings;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->settings = new DODO_Settings();
    }
    
    /**
     * Bir sonraki yayın tarihini hesapla
     * 
     * @param string $publish_mode - draft, publish, scheduled
     * @param int $queue_position - Kuyruktaki sıra (0'dan başlar)
     * @param string $manual_date - Manuel tarih (Y-m-d format, opsiyonel)
     * @param string $manual_time - Manuel saat (H:i format, opsiyonel)
     * @return array - ['status' => 'draft|publish|future', 'post_date' => 'Y-m-d H:i:s']
     */
    public function calculate_publish_date($publish_mode = 'draft', $queue_position = 0, $manual_date = '', $manual_time = '') {
        // Draft mode - hemen taslak olarak kaydet
        if ($publish_mode === 'draft') {
            return array(
                'status' => 'draft',
                'post_date' => current_time('mysql'),
                'post_date_gmt' => current_time('mysql', 1),
            );
        }
        
        // Publish mode - hemen yayınla
        if ($publish_mode === 'publish') {
            return array(
                'status' => 'publish',
                'post_date' => current_time('mysql'),
                'post_date_gmt' => current_time('mysql', 1),
            );
        }
        
        // Scheduled mode - tarih hesapla
        if ($publish_mode === 'scheduled') {
            // Manuel tarih/saat girilmiş mi kontrol et
            if (!empty($manual_date) && !empty($manual_time)) {
                // Manuel tarih/saat kullan
                $scheduled_date = $this->parse_manual_datetime($manual_date, $manual_time);
                
                error_log("[DODO SCHEDULER] Using manual date/time: {$manual_date} {$manual_time}");
                error_log("[DODO SCHEDULER] Parsed to: {$scheduled_date['local']}");
            } else {
                // Otomatik hesapla
                $scheduled_date = $this->get_next_available_slot($queue_position);
                
                error_log("[DODO SCHEDULER] Auto-calculated date for queue position {$queue_position}: {$scheduled_date['local']}");
            }
            
            return array(
                'status' => 'future',
                'post_date' => $scheduled_date['local'],
                'post_date_gmt' => $scheduled_date['gmt'],
            );
        }
        
        // Fallback - draft
        return array(
            'status' => 'draft',
            'post_date' => current_time('mysql'),
            'post_date_gmt' => current_time('mysql', 1),
        );
    }
    
    /**
     * Manuel tarih/saati parse et ve WordPress formatına çevir
     * 
     * @param string $date - Y-m-d format
     * @param string $time - H:i format
     * @return array - ['local' => 'Y-m-d H:i:s', 'gmt' => 'Y-m-d H:i:s', 'timestamp' => int]
     */
    private function parse_manual_datetime($date, $time) {
        // Tarih ve saati birleştir
        $datetime_string = $date . ' ' . $time . ':00';
        
        // Timestamp'e çevir (local timezone)
        $timestamp = strtotime($datetime_string);
        
        // Geçmişte bir tarih mi kontrol et
        $current_time = current_time('timestamp');
        if ($timestamp <= $current_time) {
            error_log("[DODO SCHEDULER] WARNING: Manual date is in the past, using current time + 1 hour");
            $timestamp = $current_time + HOUR_IN_SECONDS;
        }
        
        // Local ve GMT formatları
        $local_date = date('Y-m-d H:i:s', $timestamp);
        $gmt_timestamp = $timestamp - (get_option('gmt_offset') * HOUR_IN_SECONDS);
        $gmt_date = gmdate('Y-m-d H:i:s', $gmt_timestamp);
        
        return array(
            'local' => $local_date,
            'gmt' => $gmt_date,
            'timestamp' => $timestamp,
        );
    }
    
    /**
     * Bir sonraki uygun yayın slotunu bul
     * 
     * @param int $queue_position
     * @return array - ['local' => 'Y-m-d H:i:s', 'gmt' => 'Y-m-d H:i:s']
     */
    private function get_next_available_slot($queue_position = 0) {
        $settings = $this->settings->get_settings();
        
        $daily_limit = absint($settings['daily_publish_limit']);
        $start_hour = absint($settings['publish_start_hour']);
        $start_minute = absint($settings['publish_start_minute']);
        $publish_on_weekends = (bool) $settings['publish_on_weekends'];
        $time_interval = absint($settings['publish_time_interval']); // hours
        
        // Bugünden başla
        $current_time = current_time('timestamp');
        $base_date = $current_time;
        
        // Hangi gün ve hangi slot?
        $day_number = floor($queue_position / $daily_limit);
        $slot_in_day = $queue_position % $daily_limit;
        
        // Gün sayısını ekle
        $target_date = strtotime("+{$day_number} days", $base_date);
        
        // Hafta sonu kontrolü
        if (!$publish_on_weekends) {
            $target_date = $this->skip_weekends($target_date, $day_number);
        }
        
        // Saat hesapla
        if ($daily_limit === 1) {
            // Günde 1 post - sadece başlangıç saati
            $target_hour = $start_hour;
            $target_minute = $start_minute;
        } else {
            // Günde birden fazla post - saatleri dağıt
            $target_hour = $start_hour + ($slot_in_day * $time_interval);
            $target_minute = $start_minute;
            
            // 24 saati aşarsa ertesi güne taşı
            if ($target_hour >= 24) {
                $days_to_add = floor($target_hour / 24);
                $target_hour = $target_hour % 24;
                $target_date = strtotime("+{$days_to_add} days", $target_date);
                
                // Tekrar hafta sonu kontrolü
                if (!$publish_on_weekends) {
                    $target_date = $this->skip_weekends($target_date, $days_to_add);
                }
            }
        }
        
        // Tarihi oluştur
        $scheduled_timestamp = mktime(
            $target_hour,
            $target_minute,
            0,
            date('n', $target_date),
            date('j', $target_date),
            date('Y', $target_date)
        );
        
        // Geçmişte bir tarih mi? (bugünden önceyse yarına taşı)
        if ($scheduled_timestamp <= $current_time) {
            $scheduled_timestamp = strtotime('+1 day', $scheduled_timestamp);
            
            // Hafta sonu kontrolü
            if (!$publish_on_weekends) {
                $scheduled_timestamp = $this->skip_weekends($scheduled_timestamp, 1);
            }
        }
        
        // Local ve GMT formatları
        $local_date = date('Y-m-d H:i:s', $scheduled_timestamp);
        $gmt_timestamp = $scheduled_timestamp - (get_option('gmt_offset') * HOUR_IN_SECONDS);
        $gmt_date = gmdate('Y-m-d H:i:s', $gmt_timestamp);
        
        error_log("[DODO SCHEDULER] Queue position: {$queue_position}, Scheduled: {$local_date}");
        
        return array(
            'local' => $local_date,
            'gmt' => $gmt_date,
            'timestamp' => $scheduled_timestamp,
        );
    }
    
    /**
     * Hafta sonlarını atla
     * 
     * @param int $timestamp
     * @param int $days_added
     * @return int
     */
    private function skip_weekends($timestamp, $days_added = 0) {
        $day_of_week = date('N', $timestamp); // 1 (Pazartesi) - 7 (Pazar)
        
        // Cumartesi (6) veya Pazar (7) ise Pazartesi'ye taşı
        if ($day_of_week == 6) {
            // Cumartesi -> Pazartesi (+2 gün)
            $timestamp = strtotime('+2 days', $timestamp);
        } elseif ($day_of_week == 7) {
            // Pazar -> Pazartesi (+1 gün)
            $timestamp = strtotime('+1 day', $timestamp);
        }
        
        return $timestamp;
    }
    
    /**
     * Kuyruktaki pozisyonu hesapla
     * 
     * @return int
     */
    public function get_current_queue_position() {
        global $wpdb;
        
        // Zamanlanmış (future) ve yayınlanmış (publish) postları say
        $count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} 
             WHERE post_type = 'post' 
             AND post_status IN ('future', 'publish')
             AND post_date > NOW()"
        );
        
        return absint($count);
    }
    
    /**
     * Post için scheduled metadata kaydet
     * 
     * @param int $post_id
     * @param string $publish_mode
     * @param string $scheduled_date
     * @param string $schedule_source - 'manual', 'queue', 'draft' (opsiyonel)
     */
    public function save_schedule_meta($post_id, $publish_mode, $scheduled_date = '', $schedule_source = 'manual') {
        update_post_meta($post_id, '_dodo_publish_mode', sanitize_text_field($publish_mode));
        
        if (!empty($scheduled_date)) {
            update_post_meta($post_id, '_dodo_scheduled_date', sanitize_text_field($scheduled_date));
        }
        
        // Schedule source kaydet (manuel, queue, draft)
        update_post_meta($post_id, '_dodo_schedule_source', sanitize_text_field($schedule_source));
        
        update_post_meta($post_id, '_dodo_queue_processed_at', current_time('mysql'));
    }
    
    /**
     * Zamanlanmış postları al
     * 
     * @param int $limit
     * @return array
     */
    public function get_scheduled_posts($limit = 10) {
        $args = array(
            'post_type' => 'post',
            'post_status' => 'future',
            'posts_per_page' => $limit,
            'orderby' => 'date',
            'order' => 'ASC',
            'meta_query' => array(
                array(
                    'key' => '_dodo_generated',
                    'value' => '1',
                    'compare' => '=',
                ),
            ),
        );
        
        return get_posts($args);
    }
    
    /**
     * Zamanlanmış post istatistikleri
     * 
     * @return array
     */
    public function get_schedule_stats() {
        global $wpdb;
        
        $stats = array(
            'scheduled' => 0,
            'published_today' => 0,
            'next_publish_date' => '',
        );
        
        // Zamanlanmış post sayısı
        $stats['scheduled'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} 
             WHERE post_type = 'post' 
             AND post_status = 'future'
             AND post_date > NOW()"
        );
        
        // Bugün yayınlanan post sayısı
        $today_start = date('Y-m-d 00:00:00');
        $today_end = date('Y-m-d 23:59:59');
        
        $stats['published_today'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} 
             WHERE post_type = 'post' 
             AND post_status = 'publish'
             AND post_date BETWEEN %s AND %s",
            $today_start,
            $today_end
        ));
        
        // Bir sonraki yayın tarihi
        $next_post = $wpdb->get_var(
            "SELECT post_date FROM {$wpdb->posts} 
             WHERE post_type = 'post' 
             AND post_status = 'future'
             AND post_date > NOW()
             ORDER BY post_date ASC
             LIMIT 1"
        );
        
        if ($next_post) {
            $stats['next_publish_date'] = date('d.m.Y H:i', strtotime($next_post));
        }
        
        return $stats;
    }
}
