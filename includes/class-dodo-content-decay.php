<?php
/**
 * Content Decay Engine
 * 
 * Detects content decay and prioritizes refresh operations
 * Identifies traffic decay, CTR decay, ranking decay
 * 
 * @package DODO_AI_SEO
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Content_Decay {
    
    /**
     * Decay severity levels
     */
    const CRITICAL_DECAY = 'critical';
    const HIGH_DECAY = 'high';
    const MODERATE_DECAY = 'moderate';
    const LOW_DECAY = 'low';
    const NO_DECAY = 'none';
    
    /**
     * Decay thresholds
     */
    const CRITICAL_THRESHOLD = -40; // -40% change
    const HIGH_THRESHOLD = -25;
    const MODERATE_THRESHOLD = -15;
    const LOW_THRESHOLD = -5;
    
    /**
     * Time windows for decay analysis
     */
    const SHORT_WINDOW = 7;
    const MEDIUM_WINDOW = 30;
    const LONG_WINDOW = 90;
    
    /**
     * GSC integration
     */
    private $gsc;
    
    /**
     * Constructor
     */
    public function __construct() {
        if (class_exists('DODO_Search_Console')) {
            $this->gsc = new DODO_Search_Console();
        }
    }
    
    /**
     * Detect decay for post
     * 
     * @param int $post_id Post ID
     * @param int $window_days Days to analyze
     * @return array Decay analysis
     */
    public function detect_decay($post_id, $window_days = self::MEDIUM_WINDOW) {
        error_log("[DODO Decay] Analyzing post {$post_id}, window: {$window_days} days");
        
        $decay_analysis = [
            'post_id' => $post_id,
            'window_days' => $window_days,
            'decay_detected' => false,
            'severity' => self::NO_DECAY,
            'decay_score' => 0,
            'signals' => [],
            'recommendations' => [],
        ];
        
        // Traffic decay
        $traffic_decay = $this->analyze_traffic_decay($post_id, $window_days);
        if ($traffic_decay['decay_detected']) {
            $decay_analysis['signals'][] = $traffic_decay;
            $decay_analysis['decay_detected'] = true;
        }
        
        // CTR decay
        $ctr_decay = $this->analyze_ctr_decay($post_id, $window_days);
        if ($ctr_decay['decay_detected']) {
            $decay_analysis['signals'][] = $ctr_decay;
            $decay_analysis['decay_detected'] = true;
        }
        
        // Ranking decay
        $ranking_decay = $this->analyze_ranking_decay($post_id, $window_days);
        if ($ranking_decay['decay_detected']) {
            $decay_analysis['signals'][] = $ranking_decay;
            $decay_analysis['decay_detected'] = true;
        }
        
        // AI visibility decay
        $ai_decay = $this->analyze_ai_visibility_decay($post_id, $window_days);
        if ($ai_decay['decay_detected']) {
            $decay_analysis['signals'][] = $ai_decay;
            $decay_analysis['decay_detected'] = true;
        }
        
        // Content freshness
        $freshness = $this->analyze_content_freshness($post_id);
        if ($freshness['stale']) {
            $decay_analysis['signals'][] = $freshness;
            $decay_analysis['decay_detected'] = true;
        }
        
        // Calculate overall decay score
        $decay_analysis['decay_score'] = $this->calculate_decay_score($decay_analysis['signals']);
        $decay_analysis['severity'] = $this->get_decay_severity($decay_analysis['decay_score']);
        
        // Generate recommendations
        $decay_analysis['recommendations'] = $this->generate_decay_recommendations($decay_analysis);
        
        // Calculate refresh priority
        $decay_analysis['refresh_priority'] = $this->calculate_refresh_priority($decay_analysis);
        
        error_log(sprintf(
            '[DODO Decay] Analysis complete - Decay Score: %d, Severity: %s, Priority: %d',
            $decay_analysis['decay_score'],
            $decay_analysis['severity'],
            $decay_analysis['refresh_priority']
        ));
        
        return $decay_analysis;
    }
    
    /**
     * Analyze traffic decay
     */
    private function analyze_traffic_decay($post_id, $window_days) {
        $analysis = [
            'type' => 'traffic_decay',
            'decay_detected' => false,
            'change_pct' => 0,
            'reasoning' => '',
        ];
        
        if (!$this->gsc || !$this->gsc->is_available()['available']) {
            $analysis['reasoning'] = 'GSC not available';
            return $analysis;
        }
        
        $url = get_permalink($post_id);
        
        // Get current period data
        $current = $this->gsc->get_page_performance($url);
        
        if (isset($current['error'])) {
            $analysis['reasoning'] = 'No GSC data available';
            return $analysis;
        }
        
        // Compare with previous period (stored data or estimate)
        $previous_clicks = get_post_meta($post_id, '_dodo_previous_clicks', true);
        
        if ($previous_clicks) {
            $current_clicks = $current['total_clicks'] ?? 0;
            $change = $current_clicks - $previous_clicks;
            $change_pct = $previous_clicks > 0 
                ? round(($change / $previous_clicks) * 100, 1) 
                : 0;
            
            $analysis['change_pct'] = $change_pct;
            
            if ($change_pct <= self::LOW_THRESHOLD) {
                $analysis['decay_detected'] = true;
                $analysis['reasoning'] = sprintf(
                    'Traffic declined by %.1f%% (%d → %d clicks)',
                    abs($change_pct),
                    $previous_clicks,
                    $current_clicks
                );
            }
        }
        
        // Store current for next comparison
        update_post_meta($post_id, '_dodo_previous_clicks', $current['total_clicks'] ?? 0);
        
        return $analysis;
    }
    
    /**
     * Analyze CTR decay
     */
    private function analyze_ctr_decay($post_id, $window_days) {
        $analysis = [
            'type' => 'ctr_decay',
            'decay_detected' => false,
            'change_pct' => 0,
            'reasoning' => '',
        ];
        
        if (!$this->gsc || !$this->gsc->is_available()['available']) {
            return $analysis;
        }
        
        $url = get_permalink($post_id);
        $current = $this->gsc->get_page_performance($url);
        
        if (isset($current['error'])) {
            return $analysis;
        }
        
        $current_ctr = $current['total_impressions'] > 0 
            ? ($current['total_clicks'] / $current['total_impressions']) * 100 
            : 0;
        
        $previous_ctr = get_post_meta($post_id, '_dodo_previous_ctr', true);
        
        if ($previous_ctr) {
            $change = $current_ctr - $previous_ctr;
            $change_pct = $previous_ctr > 0 
                ? round(($change / $previous_ctr) * 100, 1) 
                : 0;
            
            $analysis['change_pct'] = $change_pct;
            
            if ($change_pct <= self::LOW_THRESHOLD) {
                $analysis['decay_detected'] = true;
                $analysis['reasoning'] = sprintf(
                    'CTR declined by %.1f%% (%.2f%% → %.2f%%)',
                    abs($change_pct),
                    $previous_ctr,
                    $current_ctr
                );
            }
        }
        
        update_post_meta($post_id, '_dodo_previous_ctr', $current_ctr);
        
        return $analysis;
    }
    
    /**
     * Analyze ranking decay
     */
    private function analyze_ranking_decay($post_id, $window_days) {
        $analysis = [
            'type' => 'ranking_decay',
            'decay_detected' => false,
            'avg_position_change' => 0,
            'reasoning' => '',
        ];
        
        // Get ranking history
        global $wpdb;
        $ranking_table = $wpdb->prefix . 'dodo_ranking_history';
        
        $rankings = $wpdb->get_results($wpdb->prepare(
            "SELECT rankings, recorded_at 
            FROM {$ranking_table}
            WHERE post_id = %d
            AND recorded_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            ORDER BY recorded_at ASC",
            $post_id,
            $window_days
        ), ARRAY_A);
        
        if (count($rankings) < 2) {
            $analysis['reasoning'] = 'Insufficient ranking data';
            return $analysis;
        }
        
        $first = json_decode($rankings[0]['rankings'], true);
        $last = json_decode($rankings[count($rankings) - 1]['rankings'], true);
        
        $position_changes = [];
        
        foreach ($last as $keyword => $last_pos) {
            if (isset($first[$keyword])) {
                $change = $last_pos - $first[$keyword]; // Positive = decline
                $position_changes[] = $change;
            }
        }
        
        if (!empty($position_changes)) {
            $avg_change = array_sum($position_changes) / count($position_changes);
            $analysis['avg_position_change'] = round($avg_change, 1);
            
            if ($avg_change > 2) { // Lost 2+ positions on average
                $analysis['decay_detected'] = true;
                $analysis['reasoning'] = sprintf(
                    'Average ranking declined by %.1f positions',
                    $avg_change
                );
            }
        }
        
        return $analysis;
    }
    
    /**
     * Analyze AI visibility decay
     */
    private function analyze_ai_visibility_decay($post_id, $window_days) {
        $analysis = [
            'type' => 'ai_visibility_decay',
            'decay_detected' => false,
            'change' => 0,
            'reasoning' => '',
        ];
        
        if (!class_exists('DODO_AI_Visibility')) {
            return $analysis;
        }
        
        $ai_visibility = new DODO_AI_Visibility();
        $trend = $ai_visibility->get_visibility_trend($post_id, $window_days);
        
        if ($trend['trend'] === 'declining') {
            $analysis['decay_detected'] = true;
            $analysis['change'] = $trend['change'];
            $analysis['reasoning'] = sprintf(
                'AI Overview visibility declined by %d points',
                abs($trend['change'])
            );
        }
        
        return $analysis;
    }
    
    /**
     * Analyze content freshness
     */
    private function analyze_content_freshness($post_id) {
        $analysis = [
            'type' => 'content_freshness',
            'stale' => false,
            'days_since_update' => 0,
            'reasoning' => '',
        ];
        
        $post = get_post($post_id);
        
        if (!$post) {
            return $analysis;
        }
        
        $last_modified = strtotime($post->post_modified);
        $days_since = round((time() - $last_modified) / 86400);
        
        $analysis['days_since_update'] = $days_since;
        
        // Content is stale if not updated in 180 days (6 months)
        if ($days_since > 180) {
            $analysis['stale'] = true;
            $analysis['reasoning'] = sprintf(
                'Content not updated in %d days - may contain outdated information',
                $days_since
            );
        }
        
        return $analysis;
    }
    
    /**
     * Calculate overall decay score
     */
    private function calculate_decay_score($signals) {
        if (empty($signals)) {
            return 0;
        }
        
        $score = 0;
        
        foreach ($signals as $signal) {
            switch ($signal['type']) {
                case 'traffic_decay':
                    $score += abs($signal['change_pct'] ?? 0) * 1.5; // Traffic is most important
                    break;
                    
                case 'ctr_decay':
                    $score += abs($signal['change_pct'] ?? 0) * 1.2;
                    break;
                    
                case 'ranking_decay':
                    $score += abs($signal['avg_position_change'] ?? 0) * 5;
                    break;
                    
                case 'ai_visibility_decay':
                    $score += abs($signal['change'] ?? 0) * 0.5;
                    break;
                    
                case 'content_freshness':
                    if ($signal['stale']) {
                        $score += min(30, ($signal['days_since_update'] - 180) / 10);
                    }
                    break;
            }
        }
        
        return round(min(100, $score));
    }
    
    /**
     * Get decay severity
     */
    private function get_decay_severity($decay_score) {
        if ($decay_score >= 60) {
            return self::CRITICAL_DECAY;
        } elseif ($decay_score >= 40) {
            return self::HIGH_DECAY;
        } elseif ($decay_score >= 20) {
            return self::MODERATE_DECAY;
        } elseif ($decay_score >= 10) {
            return self::LOW_DECAY;
        } else {
            return self::NO_DECAY;
        }
    }
    
    /**
     * Generate decay recommendations
     */
    private function generate_decay_recommendations($decay_analysis) {
        $recommendations = [];
        
        foreach ($decay_analysis['signals'] as $signal) {
            switch ($signal['type']) {
                case 'traffic_decay':
                    $recommendations[] = [
                        'priority' => 'high',
                        'action' => 'content_refresh',
                        'title' => 'Refresh Content to Recover Traffic',
                        'description' => 'Update statistics, add new information, improve examples',
                    ];
                    break;
                    
                case 'ctr_decay':
                    $recommendations[] = [
                        'priority' => 'high',
                        'action' => 'title_meta_rewrite',
                        'title' => 'Rewrite Title and Meta Description',
                        'description' => 'Improve click appeal with updated title and description',
                    ];
                    break;
                    
                case 'ranking_decay':
                    $recommendations[] = [
                        'priority' => 'high',
                        'action' => 'seo_optimization',
                        'title' => 'SEO Optimization Required',
                        'description' => 'Improve on-page SEO, add internal links, expand content',
                    ];
                    break;
                    
                case 'ai_visibility_decay':
                    $recommendations[] = [
                        'priority' => 'medium',
                        'action' => 'geo_optimization',
                        'title' => 'Optimize for AI Overview',
                        'description' => 'Improve answer format, add FAQ, enhance snippet suitability',
                    ];
                    break;
                    
                case 'content_freshness':
                    $recommendations[] = [
                        'priority' => 'medium',
                        'action' => 'content_update',
                        'title' => 'Update Stale Content',
                        'description' => 'Add current year, update statistics, refresh examples',
                    ];
                    break;
            }
        }
        
        return $recommendations;
    }
    
    /**
     * Calculate refresh priority (0-100)
     */
    private function calculate_refresh_priority($decay_analysis) {
        $priority = 0;
        
        // Base priority from decay score
        $priority += $decay_analysis['decay_score'] * 0.6;
        
        // Boost for critical severity
        if ($decay_analysis['severity'] === self::CRITICAL_DECAY) {
            $priority += 20;
        } elseif ($decay_analysis['severity'] === self::HIGH_DECAY) {
            $priority += 10;
        }
        
        // Boost for multiple decay signals
        $signal_count = count($decay_analysis['signals']);
        if ($signal_count >= 3) {
            $priority += 15;
        } elseif ($signal_count >= 2) {
            $priority += 10;
        }
        
        return round(min(100, $priority));
    }
    
    /**
     * Get decay insights across all posts
     * 
     * @return array Decay insights
     */
    public function get_decay_insights() {
        $insights = [
            'total_posts_analyzed' => 0,
            'posts_with_decay' => 0,
            'critical_decay_posts' => [],
            'high_priority_refreshes' => [],
            'decay_by_type' => [],
        ];
        
        // Analyze published posts
        $posts = get_posts([
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => 100,
            'orderby' => 'modified',
            'order' => 'ASC', // Oldest first
        ]);
        
        $insights['total_posts_analyzed'] = count($posts);
        
        $decay_counts = [
            'traffic_decay' => 0,
            'ctr_decay' => 0,
            'ranking_decay' => 0,
            'ai_visibility_decay' => 0,
            'content_freshness' => 0,
        ];
        
        foreach ($posts as $post) {
            $decay = $this->detect_decay($post->ID);
            
            if ($decay['decay_detected']) {
                $insights['posts_with_decay']++;
                
                // Count by type
                foreach ($decay['signals'] as $signal) {
                    $decay_counts[$signal['type']]++;
                }
                
                // Critical decay
                if ($decay['severity'] === self::CRITICAL_DECAY) {
                    $insights['critical_decay_posts'][] = [
                        'post_id' => $post->ID,
                        'post_title' => $post->post_title,
                        'decay_score' => $decay['decay_score'],
                        'refresh_priority' => $decay['refresh_priority'],
                    ];
                }
                
                // High priority refreshes
                if ($decay['refresh_priority'] >= 70) {
                    $insights['high_priority_refreshes'][] = [
                        'post_id' => $post->ID,
                        'post_title' => $post->post_title,
                        'refresh_priority' => $decay['refresh_priority'],
                        'recommendations' => $decay['recommendations'],
                    ];
                }
            }
        }
        
        $insights['decay_by_type'] = $decay_counts;
        
        // Sort by priority
        usort($insights['high_priority_refreshes'], function($a, $b) {
            return $b['refresh_priority'] <=> $a['refresh_priority'];
        });
        
        return $insights;
    }
}
