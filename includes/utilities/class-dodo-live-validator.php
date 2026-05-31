<?php
/**
 * Live Validator - Production transition validation
 * 
 * FINAL PRODUCTION TRANSITION
 * Validates system is ready for live operation
 * 
 * @package DODO_AI_SEO
 * @since 4.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (class_exists('DODO_Live_Validator')) {
    return;
}

class DODO_Live_Validator {
    
    /**
     * Get live system status
     * 
     * @return array Complete live status
     */
    public static function get_live_status() {
        return array(
            'timestamp' => current_time('mysql'),
            'cron_health' => self::check_cron_health(),
            'tracking_status' => self::check_tracking_status(),
            'learning_status' => self::check_learning_status(),
            'queue_health' => self::check_queue_health(),
            'gsc_health' => self::check_gsc_health(),
            'production_metrics' => self::get_production_metrics(),
            'live_readiness' => self::calculate_live_readiness(),
        );
    }
    
    /**
     * Check cron health
     */
    private static function check_cron_health() {
        $critical_crons = array(
            'dodo_daily_tracking' => 'daily',
            'dodo_weekly_learning' => 'weekly',
            'dodo_pattern_analysis' => 'weekly',
            'dodo_refresh_scan' => 'daily',
            'dodo_rank_tracking' => 'twicedaily',
            'dodo_geo_tracking' => 'daily',
        );
        
        $status = array();
        $active_count = 0;
        
        foreach ($critical_crons as $cron_name => $frequency) {
            $next_run = wp_next_scheduled($cron_name);
            $is_active = $next_run !== false;
            
            if ($is_active) {
                $active_count++;
            }
            
            $status[$cron_name] = array(
                'active' => $is_active,
                'frequency' => $frequency,
                'next_run' => $next_run ? date('Y-m-d H:i:s', $next_run) : 'Not scheduled',
                'time_until' => $next_run ? human_time_diff(time(), $next_run) : 'N/A',
            );
        }
        
        return array(
            'crons' => $status,
            'active_count' => $active_count,
            'total_count' => count($critical_crons),
            'health_percentage' => round(($active_count / count($critical_crons)) * 100, 1),
            'status' => $active_count === count($critical_crons) ? 'healthy' : 'degraded',
        );
    }
    
    /**
     * Check tracking status
     */
    private static function check_tracking_status() {
        global $wpdb;
        
        // Check tracked content
        $tracked_posts = $wpdb->get_var(
            "SELECT COUNT(DISTINCT post_id) 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = 'dodo_tracking_enabled' 
            AND meta_value = '1'"
        );
        
        // Check baseline snapshots
        $snapshot_table = $wpdb->prefix . 'dodo_strategy_snapshots';
        $snapshot_count = 0;
        
        if ($wpdb->get_var("SHOW TABLES LIKE '{$snapshot_table}'") === $snapshot_table) {
            $snapshot_count = $wpdb->get_var("SELECT COUNT(*) FROM {$snapshot_table}");
        }
        
        // Check recent tracking activity
        $recent_activity = $wpdb->get_var(
            "SELECT COUNT(*) 
            FROM {$wpdb->postmeta} 
            WHERE meta_key LIKE 'dodo_last_tracked%' 
            AND meta_value > DATE_SUB(NOW(), INTERVAL 7 DAY)"
        );
        
        return array(
            'tracked_content_count' => intval($tracked_posts),
            'baseline_snapshots' => intval($snapshot_count),
            'recent_activity' => intval($recent_activity),
            'tracking_active' => $tracked_posts > 0,
            'status' => $tracked_posts > 0 ? 'active' : 'inactive',
        );
    }
    
    /**
     * Check learning status
     */
    private static function check_learning_status() {
        global $wpdb;
        
        $learning_table = $wpdb->prefix . 'dodo_learning_events';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '{$learning_table}'") !== $learning_table) {
            return array(
                'status' => 'table_missing',
                'events_count' => 0,
                'recent_events' => 0,
                'learning_active' => false,
            );
        }
        
        // Total learning events
        $total_events = $wpdb->get_var("SELECT COUNT(*) FROM {$learning_table}");
        
        // Recent events (last 7 days)
        $recent_events = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$learning_table} 
            WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)"
        );
        
        // Winner detections
        $winner_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$learning_table} 
            WHERE event_type = 'winner_detected'"
        );
        
        // Learning mode
        $learning_mode = get_option('dodo_learning_mode', 'observe');
        
        return array(
            'total_events' => intval($total_events),
            'recent_events' => intval($recent_events),
            'winner_detections' => intval($winner_count),
            'learning_mode' => $learning_mode,
            'learning_active' => $recent_events > 0,
            'status' => $recent_events > 0 ? 'active' : 'idle',
        );
    }
    
    /**
     * Check queue health
     */
    private static function check_queue_health() {
        global $wpdb;
        
        $queue_table = $wpdb->prefix . 'dodo_queue_jobs';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '{$queue_table}'") !== $queue_table) {
            return array(
                'status' => 'table_missing',
                'queue_size' => 0,
                'stuck_jobs' => 0,
            );
        }
        
        // Queue size
        $pending = $wpdb->get_var("SELECT COUNT(*) FROM {$queue_table} WHERE status = 'pending'");
        $processing = $wpdb->get_var("SELECT COUNT(*) FROM {$queue_table} WHERE status = 'processing'");
        $failed = $wpdb->get_var("SELECT COUNT(*) FROM {$queue_table} WHERE status = 'failed'");
        $completed = $wpdb->get_var("SELECT COUNT(*) FROM {$queue_table} WHERE status = 'completed'");
        
        // Stuck jobs (processing > 1 hour)
        $stuck = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$queue_table} 
            WHERE status = 'processing' 
            AND updated_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)"
        );
        
        return array(
            'pending' => intval($pending),
            'processing' => intval($processing),
            'failed' => intval($failed),
            'completed' => intval($completed),
            'stuck_jobs' => intval($stuck),
            'queue_size' => intval($pending) + intval($processing),
            'status' => intval($stuck) > 0 ? 'degraded' : 'healthy',
        );
    }
    
    /**
     * Check GSC health
     */
    private static function check_gsc_health() {
        // Check if GSC is configured
        $gsc_client_id = get_option('dodo_gsc_client_id');
        $gsc_access_token = get_option('dodo_gsc_access_token');
        
        $configured = !empty($gsc_client_id) && !empty($gsc_access_token);
        
        // Check last fetch
        $last_fetch = get_option('dodo_gsc_last_fetch');
        $last_fetch_time = $last_fetch ? strtotime($last_fetch) : 0;
        $hours_since_fetch = $last_fetch_time > 0 ? round((time() - $last_fetch_time) / 3600, 1) : 999;
        
        return array(
            'configured' => $configured,
            'last_fetch' => $last_fetch ?: 'Never',
            'hours_since_fetch' => $hours_since_fetch,
            'status' => $configured && $hours_since_fetch < 48 ? 'healthy' : 'degraded',
        );
    }
    
    /**
     * Get production metrics
     */
    private static function get_production_metrics() {
        global $wpdb;
        
        // AI usage today
        $ai_table = $wpdb->prefix . 'dodo_ai_usage_logs';
        $ai_calls_today = 0;
        $ai_cost_today = 0;
        
        if ($wpdb->get_var("SHOW TABLES LIKE '{$ai_table}'") === $ai_table) {
            $ai_calls_today = $wpdb->get_var(
                "SELECT COUNT(*) FROM {$ai_table} 
                WHERE DATE(created_at) = CURDATE()"
            );
            
            $ai_cost_today = $wpdb->get_var(
                "SELECT SUM(cost) FROM {$ai_table} 
                WHERE DATE(created_at) = CURDATE()"
            );
        }
        
        // Memory usage
        $memory_usage = round(memory_get_usage() / 1024 / 1024, 2);
        $memory_peak = round(memory_get_peak_usage() / 1024 / 1024, 2);
        $memory_limit = ini_get('memory_limit');
        
        return array(
            'ai_calls_today' => intval($ai_calls_today),
            'ai_cost_today' => floatval($ai_cost_today),
            'memory_usage_mb' => $memory_usage,
            'memory_peak_mb' => $memory_peak,
            'memory_limit' => $memory_limit,
        );
    }
    
    /**
     * Calculate live readiness percentage
     */
    private static function calculate_live_readiness() {
        $status = self::get_live_status();
        
        $score = 0;
        $max_score = 100;
        
        // Cron health (30 points)
        $cron_health = $status['cron_health']['health_percentage'] ?? 0;
        $score += ($cron_health / 100) * 30;
        
        // Tracking active (20 points)
        if ($status['tracking_status']['tracking_active']) {
            $score += 20;
        }
        
        // Learning active (20 points)
        if ($status['learning_status']['learning_active']) {
            $score += 20;
        }
        
        // Queue healthy (15 points)
        if ($status['queue_health']['status'] === 'healthy') {
            $score += 15;
        }
        
        // GSC healthy (15 points)
        if ($status['gsc_health']['status'] === 'healthy') {
            $score += 15;
        }
        
        $percentage = round($score, 1);
        
        return array(
            'score' => $score,
            'max_score' => $max_score,
            'percentage' => $percentage,
            'status' => $percentage >= 80 ? 'ready' : ($percentage >= 60 ? 'almost_ready' : 'not_ready'),
            'recommendation' => self::get_readiness_recommendation($percentage),
        );
    }
    
    /**
     * Get readiness recommendation
     */
    private static function get_readiness_recommendation($percentage) {
        if ($percentage >= 90) {
            return 'System is live and healthy. Continue monitoring.';
        } elseif ($percentage >= 80) {
            return 'System is live-ready. Minor optimizations recommended.';
        } elseif ($percentage >= 60) {
            return 'System needs attention. Check cron and tracking status.';
        } else {
            return 'System not ready for live operation. Critical issues need resolution.';
        }
    }
    
    /**
     * Get growth candidates
     */
    public static function get_growth_candidates() {
        global $wpdb;
        
        // Posts with positive growth
        $growing_posts = $wpdb->get_results(
            "SELECT p.ID, p.post_title, pm.meta_value as growth_rate
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE pm.meta_key = 'dodo_growth_rate'
            AND CAST(pm.meta_value AS DECIMAL(10,2)) > 0
            ORDER BY CAST(pm.meta_value AS DECIMAL(10,2)) DESC
            LIMIT 10",
            ARRAY_A
        );
        
        return $growing_posts ?: array();
    }
    
    /**
     * Get declining content
     */
    public static function get_declining_content() {
        global $wpdb;
        
        // Posts with negative growth
        $declining_posts = $wpdb->get_results(
            "SELECT p.ID, p.post_title, pm.meta_value as growth_rate
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE pm.meta_key = 'dodo_growth_rate'
            AND CAST(pm.meta_value AS DECIMAL(10,2)) < 0
            ORDER BY CAST(pm.meta_value AS DECIMAL(10,2)) ASC
            LIMIT 10",
            ARRAY_A
        );
        
        return $declining_posts ?: array();
    }
    
    /**
     * Get refresh candidates
     */
    public static function get_refresh_candidates() {
        global $wpdb;
        
        // Posts marked for refresh
        $refresh_posts = $wpdb->get_results(
            "SELECT p.ID, p.post_title, pm.meta_value as refresh_score
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE pm.meta_key = 'dodo_refresh_candidate'
            AND pm.meta_value = '1'
            LIMIT 20",
            ARRAY_A
        );
        
        return $refresh_posts ?: array();
    }
}
