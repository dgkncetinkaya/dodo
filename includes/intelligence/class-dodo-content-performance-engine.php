<?php
/**
 * Content Performance Engine
 * 
 * Tracks real performance of published content
 * Sprint E - Feedback Loop & Self-Learning SEO System
 * 
 * @package DODO_AI_SEO
 * @since 3.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Content_Performance_Engine {
    
    /**
     * Track content performance
     * 
     * @param int $post_id
     * @param array $gsc_data GSC data for the post
     * @return array Performance metrics
     */
    public function track_content_performance($post_id, $gsc_data = array()) {
        $performance = array(
            'post_id' => $post_id,
            'performance_score' => 0,
            'growth_rate' => 0,
            'status' => 'unknown',
            'metrics' => array(),
        );
        
        try {
            // Get publish snapshot
            $publish_snapshot = $this->get_publish_snapshot($post_id);
            
            // Get current snapshot
            $current_snapshot = $this->create_current_snapshot($post_id, $gsc_data);
            
            // Compare before/after
            $comparison = $this->compare_before_after($publish_snapshot, $current_snapshot);
            
            // Calculate growth rate
            $growth_rate = $this->calculate_growth_rate($comparison);
            
            // Calculate performance score
            $performance_score = $this->calculate_performance_score($comparison, $growth_rate);
            
            // Determine status
            $status = $this->determine_performance_status($performance_score, $growth_rate);
            
            $performance = array(
                'post_id' => $post_id,
                'performance_score' => $performance_score,
                'growth_rate' => $growth_rate,
                'status' => $status,
                'metrics' => $comparison,
                'publish_snapshot' => $publish_snapshot,
                'current_snapshot' => $current_snapshot,
            );
            
            // Save to post meta
            $this->save_performance_data($post_id, $performance);
            
        } catch (Throwable $e) {
            error_log('[DODO][Performance] Error tracking content: ' . $e->getMessage());
        }
        
        return $performance;
    }
    
    /**
     * Get publish snapshot
     */
    private function get_publish_snapshot($post_id) {
        $snapshot = get_post_meta($post_id, '_dodo_publish_snapshot', true);
        
        if (empty($snapshot)) {
            // Create initial snapshot
            $snapshot = array(
                'date' => get_the_date('Y-m-d', $post_id),
                'impressions' => 0,
                'clicks' => 0,
                'ctr' => 0,
                'position' => 0,
                'indexed' => false,
            );
            
            update_post_meta($post_id, '_dodo_publish_snapshot', $snapshot);
        }
        
        return $snapshot;
    }
    
    /**
     * Create current snapshot
     */
    private function create_current_snapshot($post_id, $gsc_data) {
        $snapshot = array(
            'date' => current_time('Y-m-d'),
            'impressions' => $gsc_data['impressions'] ?? 0,
            'clicks' => $gsc_data['clicks'] ?? 0,
            'ctr' => $gsc_data['ctr'] ?? 0,
            'position' => $gsc_data['position'] ?? 0,
            'indexed' => !empty($gsc_data['impressions']),
        );
        
        return $snapshot;
    }
    
    /**
     * Compare before/after
     */
    private function compare_before_after($before, $after) {
        $comparison = array(
            'impressions_delta' => ($after['impressions'] ?? 0) - ($before['impressions'] ?? 0),
            'clicks_delta' => ($after['clicks'] ?? 0) - ($before['clicks'] ?? 0),
            'ctr_delta' => ($after['ctr'] ?? 0) - ($before['ctr'] ?? 0),
            'position_delta' => ($before['position'] ?? 0) - ($after['position'] ?? 0), // Lower is better
            'indexed_change' => ($after['indexed'] ?? false) && !($before['indexed'] ?? false),
        );
        
        return $comparison;
    }
    
    /**
     * Calculate growth rate (0-100)
     */
    private function calculate_growth_rate($comparison) {
        $growth = 0;
        
        // Impressions growth (40 points)
        if ($comparison['impressions_delta'] > 100) {
            $growth += 40;
        } elseif ($comparison['impressions_delta'] > 50) {
            $growth += 30;
        } elseif ($comparison['impressions_delta'] > 10) {
            $growth += 20;
        } elseif ($comparison['impressions_delta'] > 0) {
            $growth += 10;
        }
        
        // Clicks growth (30 points)
        if ($comparison['clicks_delta'] > 20) {
            $growth += 30;
        } elseif ($comparison['clicks_delta'] > 10) {
            $growth += 20;
        } elseif ($comparison['clicks_delta'] > 5) {
            $growth += 15;
        } elseif ($comparison['clicks_delta'] > 0) {
            $growth += 10;
        }
        
        // Position improvement (20 points)
        if ($comparison['position_delta'] > 5) {
            $growth += 20;
        } elseif ($comparison['position_delta'] > 2) {
            $growth += 15;
        } elseif ($comparison['position_delta'] > 0) {
            $growth += 10;
        }
        
        // CTR improvement (10 points)
        if ($comparison['ctr_delta'] > 2) {
            $growth += 10;
        } elseif ($comparison['ctr_delta'] > 1) {
            $growth += 7;
        } elseif ($comparison['ctr_delta'] > 0) {
            $growth += 5;
        }
        
        return min(100, max(0, $growth));
    }
    
    /**
     * Calculate performance score (0-100)
     */
    private function calculate_performance_score($comparison, $growth_rate) {
        $score = $growth_rate;
        
        // Bonus for indexed
        if ($comparison['indexed_change']) {
            $score += 10;
        }
        
        // Penalty for negative trends
        if ($comparison['impressions_delta'] < -50) {
            $score -= 20;
        }
        
        if ($comparison['clicks_delta'] < -10) {
            $score -= 15;
        }
        
        return min(100, max(0, $score));
    }
    
    /**
     * Determine performance status
     */
    private function determine_performance_status($performance_score, $growth_rate) {
        if ($performance_score >= 80) {
            return 'winning';
        } elseif ($performance_score >= 60) {
            return 'growing';
        } elseif ($performance_score >= 40) {
            return 'stable';
        } elseif ($performance_score >= 20) {
            return 'declining';
        } else {
            return 'failing';
        }
    }
    
    /**
     * Save performance data
     */
    private function save_performance_data($post_id, $performance) {
        update_post_meta($post_id, '_dodo_performance_score', $performance['performance_score']);
        update_post_meta($post_id, '_dodo_growth_rate', $performance['growth_rate']);
        update_post_meta($post_id, '_dodo_performance_status', $performance['status']);
        update_post_meta($post_id, '_dodo_current_snapshot', $performance['current_snapshot']);
        update_post_meta($post_id, '_dodo_last_tracked', current_time('mysql'));
    }
    
    /**
     * Detect winning content
     */
    public function detect_winning_content($limit = 10) {
        $args = array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'meta_key' => '_dodo_performance_score',
            'orderby' => 'meta_value_num',
            'order' => 'DESC',
            'meta_query' => array(
                array(
                    'key' => '_dodo_performance_score',
                    'value' => 70,
                    'compare' => '>=',
                    'type' => 'NUMERIC',
                ),
            ),
        );
        
        return get_posts($args);
    }
    
    /**
     * Detect failing content
     */
    public function detect_failing_content($limit = 10) {
        $args = array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'meta_key' => '_dodo_performance_score',
            'orderby' => 'meta_value_num',
            'order' => 'ASC',
            'meta_query' => array(
                array(
                    'key' => '_dodo_performance_score',
                    'value' => 40,
                    'compare' => '<',
                    'type' => 'NUMERIC',
                ),
            ),
        );
        
        return get_posts($args);
    }
    
    /**
     * Get performance statistics
     */
    public function get_statistics() {
        global $wpdb;
        
        $stats = array(
            'total_tracked' => 0,
            'winning' => 0,
            'growing' => 0,
            'stable' => 0,
            'declining' => 0,
            'failing' => 0,
            'avg_performance_score' => 0,
            'avg_growth_rate' => 0,
        );
        
        // Count by status
        $statuses = array('winning', 'growing', 'stable', 'declining', 'failing');
        
        foreach ($statuses as $status) {
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->postmeta} 
                WHERE meta_key = '_dodo_performance_status' 
                AND meta_value = %s",
                $status
            ));
            
            $stats[$status] = intval($count);
            $stats['total_tracked'] += intval($count);
        }
        
        // Average scores
        $avg_performance = $wpdb->get_var(
            "SELECT AVG(CAST(meta_value AS DECIMAL(10,2))) 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_dodo_performance_score'"
        );
        
        $avg_growth = $wpdb->get_var(
            "SELECT AVG(CAST(meta_value AS DECIMAL(10,2))) 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_dodo_growth_rate'"
        );
        
        $stats['avg_performance_score'] = round(floatval($avg_performance));
        $stats['avg_growth_rate'] = round(floatval($avg_growth));
        
        return $stats;
    }
}
