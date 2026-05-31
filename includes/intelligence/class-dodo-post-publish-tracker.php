<?php
/**
 * Post-Publish Tracking Engine
 * 
 * Automatically tracks content performance at 7/14/30/90 days after publish
 * Sprint E - Feedback Loop & Self-Learning SEO System
 * 
 * @package DODO_AI_SEO
 * @since 3.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Post_Publish_Tracker {
    
    /**
     * Tracking intervals (in days)
     */
    private $tracking_intervals = array(7, 14, 30, 90);
    
    /**
     * Constructor
     */
    public function __construct() {
        // Schedule tracking checks
        add_action('init', array($this, 'schedule_tracking_cron'));
        add_action('dodo_check_post_tracking', array($this, 'check_and_track_posts'));
    }
    
    /**
     * Schedule tracking cron job
     */
    public function schedule_tracking_cron() {
        if (!wp_next_scheduled('dodo_check_post_tracking')) {
            wp_schedule_event(time(), 'daily', 'dodo_check_post_tracking');
        }
    }
    
    /**
     * Check and track posts that need tracking
     */
    public function check_and_track_posts() {
        error_log('[DODO][Post Tracker] === DAILY TRACKING CHECK START ===');
        
        try {
            $posts_to_track = $this->get_posts_needing_tracking();
            
            error_log('[DODO][Post Tracker] Found ' . count($posts_to_track) . ' posts to track');
            
            foreach ($posts_to_track as $post_data) {
                $this->track_post($post_data['post_id'], $post_data['interval']);
            }
            
            error_log('[DODO][Post Tracker] === DAILY TRACKING CHECK END ===');
            
        } catch (Throwable $e) {
            error_log('[DODO][Post Tracker] Error in daily check: ' . $e->getMessage());
        }
    }
    
    /**
     * Get posts that need tracking
     * 
     * @return array Posts with their tracking intervals
     */
    private function get_posts_needing_tracking() {
        $posts_to_track = array();
        
        // Get all published posts
        $args = array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
        );
        
        $post_ids = get_posts($args);
        
        foreach ($post_ids as $post_id) {
            $publish_date = get_the_date('Y-m-d', $post_id);
            $days_since_publish = $this->get_days_since_publish($publish_date);
            
            // Check which interval this post needs
            foreach ($this->tracking_intervals as $interval) {
                if ($this->should_track_at_interval($post_id, $interval, $days_since_publish)) {
                    $posts_to_track[] = array(
                        'post_id' => $post_id,
                        'interval' => $interval,
                        'days_since_publish' => $days_since_publish,
                    );
                }
            }
        }
        
        return $posts_to_track;
    }
    
    /**
     * Check if post should be tracked at this interval
     * 
     * @param int $post_id
     * @param int $interval Days interval
     * @param int $days_since_publish
     * @return bool
     */
    private function should_track_at_interval($post_id, $interval, $days_since_publish) {
        // Post must be at least $interval days old
        if ($days_since_publish < $interval) {
            return false;
        }
        
        // Check if already tracked at this interval
        $tracked_intervals = get_post_meta($post_id, '_dodo_tracked_intervals', true);
        
        if (!is_array($tracked_intervals)) {
            $tracked_intervals = array();
        }
        
        // Not yet tracked at this interval
        if (!in_array($interval, $tracked_intervals)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Get days since publish
     * 
     * @param string $publish_date Y-m-d format
     * @return int Days
     */
    private function get_days_since_publish($publish_date) {
        $publish_timestamp = strtotime($publish_date);
        $current_timestamp = current_time('timestamp');
        
        $diff = $current_timestamp - $publish_timestamp;
        $days = floor($diff / DAY_IN_SECONDS);
        
        return $days;
    }
    
    /**
     * Track post at specific interval
     * 
     * @param int $post_id
     * @param int $interval Days interval
     */
    public function track_post($post_id, $interval) {
        error_log("[DODO][Post Tracker] Tracking post {$post_id} at {$interval} days");
        
        try {
            // Get GSC data for this post
            $gsc_data = $this->fetch_post_gsc_metrics($post_id);
            
            // Get performance engine
            if (!class_exists('DODO_Content_Performance_Engine')) {
                error_log('[DODO][Post Tracker] Performance engine not available');
                return;
            }
            
            $performance_engine = new DODO_Content_Performance_Engine();
            
            // Track performance
            $performance = $performance_engine->track_content_performance($post_id, $gsc_data);
            
            // Save interval tracking data
            $this->save_interval_tracking($post_id, $interval, $performance);
            
            // Mark interval as tracked
            $this->mark_interval_tracked($post_id, $interval);
            
            error_log("[DODO][Post Tracker] Post {$post_id} tracked successfully at {$interval} days");
            
        } catch (Throwable $e) {
            error_log("[DODO][Post Tracker] Error tracking post {$post_id}: " . $e->getMessage());
        }
    }
    
    /**
     * Fetch GSC metrics for post
     * 
     * @param int $post_id
     * @return array GSC data
     */
    private function fetch_post_gsc_metrics($post_id) {
        $gsc_data = array(
            'impressions' => 0,
            'clicks' => 0,
            'ctr' => 0,
            'position' => 0,
        );
        
        try {
            // Check if GSC Intelligence is available
            if (!class_exists('DODO_GSC_Intelligence')) {
                return $gsc_data;
            }
            
            $gsc = new DODO_GSC_Intelligence();
            
            if (!$gsc->is_available()) {
                return $gsc_data;
            }
            
            // Get post URL
            $post_url = get_permalink($post_id);
            
            // Fetch GSC data for this URL (last 28 days)
            $raw_data = $gsc->fetch_gsc_data(28);
            
            if (empty($raw_data)) {
                return $gsc_data;
            }
            
            // Filter data for this specific URL
            foreach ($raw_data as $row) {
                if (isset($row['keys'][1]) && strpos($row['keys'][1], $post_url) !== false) {
                    $gsc_data['impressions'] += $row['impressions'] ?? 0;
                    $gsc_data['clicks'] += $row['clicks'] ?? 0;
                }
            }
            
            // Calculate CTR and position
            if ($gsc_data['impressions'] > 0) {
                $gsc_data['ctr'] = ($gsc_data['clicks'] / $gsc_data['impressions']) * 100;
            }
            
            // Get average position for this URL
            $position_data = $this->get_average_position($post_url, $raw_data);
            $gsc_data['position'] = $position_data;
            
        } catch (Throwable $e) {
            error_log('[DODO][Post Tracker] Error fetching GSC metrics: ' . $e->getMessage());
        }
        
        return $gsc_data;
    }
    
    /**
     * Get average position for URL
     * 
     * @param string $url
     * @param array $raw_data
     * @return float
     */
    private function get_average_position($url, $raw_data) {
        $positions = array();
        
        foreach ($raw_data as $row) {
            if (isset($row['keys'][1]) && strpos($row['keys'][1], $url) !== false) {
                if (isset($row['position'])) {
                    $positions[] = $row['position'];
                }
            }
        }
        
        if (empty($positions)) {
            return 0;
        }
        
        return round(array_sum($positions) / count($positions), 1);
    }
    
    /**
     * Save interval tracking data
     * 
     * @param int $post_id
     * @param int $interval
     * @param array $performance
     */
    private function save_interval_tracking($post_id, $interval, $performance) {
        $tracking_history = get_post_meta($post_id, '_dodo_tracking_history', true);
        
        if (!is_array($tracking_history)) {
            $tracking_history = array();
        }
        
        $tracking_history[$interval] = array(
            'date' => current_time('mysql'),
            'performance_score' => $performance['performance_score'] ?? 0,
            'growth_rate' => $performance['growth_rate'] ?? 0,
            'status' => $performance['status'] ?? 'unknown',
            'metrics' => $performance['metrics'] ?? array(),
            'snapshot' => $performance['current_snapshot'] ?? array(),
        );
        
        update_post_meta($post_id, '_dodo_tracking_history', $tracking_history);
    }
    
    /**
     * Mark interval as tracked
     * 
     * @param int $post_id
     * @param int $interval
     */
    private function mark_interval_tracked($post_id, $interval) {
        $tracked_intervals = get_post_meta($post_id, '_dodo_tracked_intervals', true);
        
        if (!is_array($tracked_intervals)) {
            $tracked_intervals = array();
        }
        
        if (!in_array($interval, $tracked_intervals)) {
            $tracked_intervals[] = $interval;
            update_post_meta($post_id, '_dodo_tracked_intervals', $tracked_intervals);
        }
    }
    
    /**
     * Compare timeframes for a post
     * 
     * @param int $post_id
     * @return array Comparison data
     */
    public function compare_timeframes($post_id) {
        $tracking_history = get_post_meta($post_id, '_dodo_tracking_history', true);
        
        if (!is_array($tracking_history) || empty($tracking_history)) {
            return array(
                'available' => false,
                'message' => 'Henüz takip verisi yok',
            );
        }
        
        $comparison = array(
            'available' => true,
            'intervals' => array(),
            'trend' => 'unknown',
            'best_interval' => null,
            'worst_interval' => null,
        );
        
        $scores = array();
        
        foreach ($this->tracking_intervals as $interval) {
            if (isset($tracking_history[$interval])) {
                $data = $tracking_history[$interval];
                
                $comparison['intervals'][$interval] = array(
                    'days' => $interval,
                    'date' => $data['date'],
                    'performance_score' => $data['performance_score'],
                    'growth_rate' => $data['growth_rate'],
                    'status' => $data['status'],
                    'metrics' => $data['metrics'],
                );
                
                $scores[$interval] = $data['performance_score'];
            }
        }
        
        // Determine trend
        if (count($scores) >= 2) {
            $score_values = array_values($scores);
            $first_score = reset($score_values);
            $last_score = end($score_values);
            
            if ($last_score > $first_score + 10) {
                $comparison['trend'] = 'improving';
            } elseif ($last_score < $first_score - 10) {
                $comparison['trend'] = 'declining';
            } else {
                $comparison['trend'] = 'stable';
            }
        }
        
        // Best and worst intervals
        if (!empty($scores)) {
            arsort($scores);
            $comparison['best_interval'] = key($scores);
            
            asort($scores);
            $comparison['worst_interval'] = key($scores);
        }
        
        return $comparison;
    }
    
    /**
     * Get tracking status for post
     * 
     * @param int $post_id
     * @return array Status info
     */
    public function get_tracking_status($post_id) {
        $publish_date = get_the_date('Y-m-d', $post_id);
        $days_since_publish = $this->get_days_since_publish($publish_date);
        
        $tracked_intervals = get_post_meta($post_id, '_dodo_tracked_intervals', true);
        
        if (!is_array($tracked_intervals)) {
            $tracked_intervals = array();
        }
        
        $status = array(
            'post_id' => $post_id,
            'publish_date' => $publish_date,
            'days_since_publish' => $days_since_publish,
            'tracked_intervals' => $tracked_intervals,
            'pending_intervals' => array(),
            'next_tracking' => null,
        );
        
        // Find pending intervals
        foreach ($this->tracking_intervals as $interval) {
            if (!in_array($interval, $tracked_intervals)) {
                if ($days_since_publish >= $interval) {
                    $status['pending_intervals'][] = $interval;
                } elseif ($status['next_tracking'] === null) {
                    $status['next_tracking'] = array(
                        'interval' => $interval,
                        'days_remaining' => $interval - $days_since_publish,
                    );
                }
            }
        }
        
        return $status;
    }
    
    /**
     * Manually trigger tracking for a post
     * 
     * @param int $post_id
     * @param int $interval Optional specific interval
     * @return array Result
     */
    public function manual_track($post_id, $interval = null) {
        $result = array(
            'success' => false,
            'message' => '',
            'tracked_intervals' => array(),
        );
        
        try {
            if ($interval !== null) {
                // Track specific interval
                $this->track_post($post_id, $interval);
                $result['tracked_intervals'][] = $interval;
            } else {
                // Track all pending intervals
                $status = $this->get_tracking_status($post_id);
                
                foreach ($status['pending_intervals'] as $pending_interval) {
                    $this->track_post($post_id, $pending_interval);
                    $result['tracked_intervals'][] = $pending_interval;
                }
            }
            
            $result['success'] = true;
            $result['message'] = 'Takip başarıyla tamamlandı';
            
        } catch (Throwable $e) {
            $result['message'] = 'Hata: ' . $e->getMessage();
        }
        
        return $result;
    }
    
    /**
     * Get posts by tracking status
     * 
     * @param string $status 'complete', 'partial', 'none'
     * @return array Post IDs
     */
    public function get_posts_by_tracking_status($status = 'all') {
        $args = array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
        );
        
        $all_post_ids = get_posts($args);
        $filtered_posts = array();
        
        foreach ($all_post_ids as $post_id) {
            $tracked_intervals = get_post_meta($post_id, '_dodo_tracked_intervals', true);
            
            if (!is_array($tracked_intervals)) {
                $tracked_intervals = array();
            }
            
            $tracked_count = count($tracked_intervals);
            
            switch ($status) {
                case 'complete':
                    if ($tracked_count === count($this->tracking_intervals)) {
                        $filtered_posts[] = $post_id;
                    }
                    break;
                    
                case 'partial':
                    if ($tracked_count > 0 && $tracked_count < count($this->tracking_intervals)) {
                        $filtered_posts[] = $post_id;
                    }
                    break;
                    
                case 'none':
                    if ($tracked_count === 0) {
                        $filtered_posts[] = $post_id;
                    }
                    break;
                    
                default:
                    $filtered_posts[] = $post_id;
            }
        }
        
        return $filtered_posts;
    }
}
