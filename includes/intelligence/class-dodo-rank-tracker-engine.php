<?php
/**
 * Rank Movement Engine
 * 
 * Tracks keyword ranking changes, velocity, and volatility
 * Sprint E - Feedback Loop & Self-Learning SEO System
 * 
 * @package DODO_AI_SEO
 * @since 3.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Rank_Tracker_Engine {
    
    /**
     * Track rank changes for a keyword
     * 
     * @param string $keyword
     * @param int $post_id Optional post ID
     * @return array Rank tracking data
     */
    public function track_rank_changes($keyword, $post_id = null) {
        $tracking = array(
            'keyword' => $keyword,
            'post_id' => $post_id,
            'current_position' => 0,
            'previous_position' => 0,
            'position_change' => 0,
            'rank_velocity' => 0,
            'volatility' => 0,
            'trend' => 'unknown',
            'history' => array(),
        );
        
        try {
            // Get current position from GSC
            $current_position = $this->get_current_position($keyword, $post_id);
            
            // Get historical positions
            $history = $this->get_rank_history($keyword, $post_id);
            
            // Calculate metrics
            $tracking['current_position'] = $current_position;
            $tracking['history'] = $history;
            
            if (!empty($history)) {
                $tracking['previous_position'] = $history[0]['position'] ?? 0;
                $tracking['position_change'] = $tracking['previous_position'] - $current_position;
                $tracking['rank_velocity'] = $this->calculate_rank_velocity($history, $current_position);
                $tracking['volatility'] = $this->calculate_volatility($history);
                $tracking['trend'] = $this->determine_trend($history, $current_position);
            }
            
            // Save current position to history
            $this->save_rank_snapshot($keyword, $post_id, $current_position);
            
        } catch (Throwable $e) {
            error_log('[DODO][Rank Tracker] Error tracking rank: ' . $e->getMessage());
        }
        
        return $tracking;
    }
    
    /**
     * Get current position from GSC
     * 
     * @param string $keyword
     * @param int $post_id
     * @return float Position
     */
    private function get_current_position($keyword, $post_id = null) {
        $position = 0;
        
        try {
            if (!class_exists('DODO_GSC_Intelligence')) {
                return $position;
            }
            
            $gsc = new DODO_GSC_Intelligence();
            
            if (!$gsc->is_available()) {
                return $position;
            }
            
            // Fetch GSC data
            $raw_data = $gsc->fetch_gsc_data(7); // Last 7 days
            
            if (empty($raw_data)) {
                return $position;
            }
            
            // Find keyword data
            $positions = array();
            
            foreach ($raw_data as $row) {
                $query = $row['keys'][0] ?? '';
                
                // Match keyword
                if (strtolower($query) === strtolower($keyword)) {
                    // If post_id specified, match URL too
                    if ($post_id !== null) {
                        $url = $row['keys'][1] ?? '';
                        $post_url = get_permalink($post_id);
                        
                        if (strpos($url, $post_url) !== false) {
                            $positions[] = $row['position'] ?? 0;
                        }
                    } else {
                        $positions[] = $row['position'] ?? 0;
                    }
                }
            }
            
            // Calculate average position
            if (!empty($positions)) {
                $position = round(array_sum($positions) / count($positions), 1);
            }
            
        } catch (Throwable $e) {
            error_log('[DODO][Rank Tracker] Error getting current position: ' . $e->getMessage());
        }
        
        return $position;
    }
    
    /**
     * Get rank history
     * 
     * @param string $keyword
     * @param int $post_id
     * @return array History data
     */
    private function get_rank_history($keyword, $post_id = null) {
        $meta_key = '_dodo_rank_history_' . md5($keyword . ($post_id ?? ''));
        
        if ($post_id !== null) {
            $history = get_post_meta($post_id, $meta_key, true);
        } else {
            $history = get_option($meta_key, array());
        }
        
        if (!is_array($history)) {
            $history = array();
        }
        
        // Sort by date descending
        usort($history, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
        
        // Keep last 90 days only
        $history = array_slice($history, 0, 90);
        
        return $history;
    }
    
    /**
     * Save rank snapshot
     * 
     * @param string $keyword
     * @param int $post_id
     * @param float $position
     */
    private function save_rank_snapshot($keyword, $post_id, $position) {
        $meta_key = '_dodo_rank_history_' . md5($keyword . ($post_id ?? ''));
        
        $history = $this->get_rank_history($keyword, $post_id);
        
        // Add new snapshot
        $snapshot = array(
            'date' => current_time('Y-m-d'),
            'position' => $position,
            'timestamp' => current_time('timestamp'),
        );
        
        // Check if today already tracked
        $today_tracked = false;
        foreach ($history as $key => $item) {
            if ($item['date'] === $snapshot['date']) {
                // Update today's position
                $history[$key] = $snapshot;
                $today_tracked = true;
                break;
            }
        }
        
        if (!$today_tracked) {
            array_unshift($history, $snapshot);
        }
        
        // Keep last 90 days
        $history = array_slice($history, 0, 90);
        
        // Save
        if ($post_id !== null) {
            update_post_meta($post_id, $meta_key, $history);
        } else {
            update_option($meta_key, $history, false);
        }
    }
    
    /**
     * Calculate rank velocity (positions per day)
     * 
     * @param array $history
     * @param float $current_position
     * @return float Velocity
     */
    private function calculate_rank_velocity($history, $current_position) {
        if (empty($history)) {
            return 0;
        }
        
        // Get position from 7 days ago
        $seven_days_ago = date('Y-m-d', strtotime('-7 days'));
        $old_position = null;
        
        foreach ($history as $item) {
            if ($item['date'] <= $seven_days_ago) {
                $old_position = $item['position'];
                break;
            }
        }
        
        if ($old_position === null) {
            // Use oldest available
            $old_position = end($history)['position'] ?? $current_position;
        }
        
        // Calculate velocity (positive = improving)
        $position_change = $old_position - $current_position;
        $days = 7;
        
        $velocity = $position_change / $days;
        
        return round($velocity, 2);
    }
    
    /**
     * Calculate volatility (standard deviation of positions)
     * 
     * @param array $history
     * @return float Volatility score (0-100)
     */
    private function calculate_volatility($history) {
        if (count($history) < 2) {
            return 0;
        }
        
        // Get last 30 days
        $recent_history = array_slice($history, 0, 30);
        
        $positions = array_column($recent_history, 'position');
        
        // Calculate standard deviation
        $mean = array_sum($positions) / count($positions);
        
        $variance = 0;
        foreach ($positions as $position) {
            $variance += pow($position - $mean, 2);
        }
        
        $variance = $variance / count($positions);
        $std_dev = sqrt($variance);
        
        // Convert to 0-100 scale (higher = more volatile)
        // Assume std_dev > 5 is very volatile
        $volatility = min(100, ($std_dev / 5) * 100);
        
        return round($volatility);
    }
    
    /**
     * Determine trend
     * 
     * @param array $history
     * @param float $current_position
     * @return string Trend: 'improving', 'declining', 'stable', 'volatile'
     */
    private function determine_trend($history, $current_position) {
        if (empty($history)) {
            return 'unknown';
        }
        
        // Get last 30 days
        $recent_history = array_slice($history, 0, 30);
        
        if (count($recent_history) < 7) {
            return 'insufficient_data';
        }
        
        // Calculate volatility
        $volatility = $this->calculate_volatility($recent_history);
        
        if ($volatility > 60) {
            return 'volatile';
        }
        
        // Compare first week vs last week
        $first_week = array_slice($recent_history, 0, 7);
        $last_week = array_slice($recent_history, -7);
        
        $first_avg = array_sum(array_column($first_week, 'position')) / count($first_week);
        $last_avg = array_sum(array_column($last_week, 'position')) / count($last_week);
        
        $change = $last_avg - $first_avg;
        
        if ($change < -2) {
            return 'improving'; // Position decreased = better
        } elseif ($change > 2) {
            return 'declining'; // Position increased = worse
        } else {
            return 'stable';
        }
    }
    
    /**
     * Detect rank growth opportunities
     * 
     * @param int $limit
     * @return array Keywords with growth potential
     */
    public function detect_rank_growth($limit = 20) {
        $opportunities = array();
        
        try {
            if (!class_exists('DODO_GSC_Intelligence')) {
                return $opportunities;
            }
            
            $gsc = new DODO_GSC_Intelligence();
            
            if (!$gsc->is_available()) {
                return $opportunities;
            }
            
            // Get ranking opportunities (position 5-20)
            $ranking_opps = $gsc->get_ranking_opportunities(28);
            
            foreach ($ranking_opps as $opp) {
                $keyword = $opp['query'] ?? '';
                
                if (empty($keyword)) {
                    continue;
                }
                
                // Track this keyword
                $tracking = $this->track_rank_changes($keyword);
                
                // Look for positive velocity
                if ($tracking['rank_velocity'] > 0.1) {
                    $opportunities[] = array(
                        'keyword' => $keyword,
                        'current_position' => $tracking['current_position'],
                        'velocity' => $tracking['rank_velocity'],
                        'trend' => $tracking['trend'],
                        'potential' => $this->calculate_growth_potential($tracking),
                    );
                }
                
                if (count($opportunities) >= $limit) {
                    break;
                }
            }
            
            // Sort by potential
            usort($opportunities, function($a, $b) {
                return $b['potential'] <=> $a['potential'];
            });
            
        } catch (Throwable $e) {
            error_log('[DODO][Rank Tracker] Error detecting growth: ' . $e->getMessage());
        }
        
        return $opportunities;
    }
    
    /**
     * Detect rank decay (declining rankings)
     * 
     * @param int $limit
     * @return array Keywords declining
     */
    public function detect_rank_decay($limit = 20) {
        $declining = array();
        
        try {
            if (!class_exists('DODO_GSC_Intelligence')) {
                return $declining;
            }
            
            $gsc = new DODO_GSC_Intelligence();
            
            if (!$gsc->is_available()) {
                return $declining;
            }
            
            // Get all tracked keywords from GSC
            $raw_data = $gsc->fetch_gsc_data(28);
            
            if (empty($raw_data)) {
                return $declining;
            }
            
            $keywords_checked = array();
            
            foreach ($raw_data as $row) {
                $keyword = $row['keys'][0] ?? '';
                
                if (empty($keyword) || in_array($keyword, $keywords_checked)) {
                    continue;
                }
                
                $keywords_checked[] = $keyword;
                
                // Track this keyword
                $tracking = $this->track_rank_changes($keyword);
                
                // Look for negative velocity
                if ($tracking['rank_velocity'] < -0.2) {
                    $declining[] = array(
                        'keyword' => $keyword,
                        'current_position' => $tracking['current_position'],
                        'velocity' => $tracking['rank_velocity'],
                        'trend' => $tracking['trend'],
                        'severity' => $this->calculate_decay_severity($tracking),
                    );
                }
                
                if (count($declining) >= $limit) {
                    break;
                }
            }
            
            // Sort by severity
            usort($declining, function($a, $b) {
                return $b['severity'] <=> $a['severity'];
            });
            
        } catch (Throwable $e) {
            error_log('[DODO][Rank Tracker] Error detecting decay: ' . $e->getMessage());
        }
        
        return $declining;
    }
    
    /**
     * Calculate growth potential (0-100)
     * 
     * @param array $tracking
     * @return int Potential score
     */
    private function calculate_growth_potential($tracking) {
        $potential = 0;
        
        // Positive velocity = good
        if ($tracking['rank_velocity'] > 0) {
            $potential += min(40, $tracking['rank_velocity'] * 20);
        }
        
        // Position 5-15 = high potential
        if ($tracking['current_position'] >= 5 && $tracking['current_position'] <= 15) {
            $potential += 30;
        } elseif ($tracking['current_position'] >= 15 && $tracking['current_position'] <= 30) {
            $potential += 20;
        }
        
        // Stable or improving trend = good
        if ($tracking['trend'] === 'improving') {
            $potential += 20;
        } elseif ($tracking['trend'] === 'stable') {
            $potential += 10;
        }
        
        // Low volatility = good
        if ($tracking['volatility'] < 30) {
            $potential += 10;
        }
        
        return min(100, $potential);
    }
    
    /**
     * Calculate decay severity (0-100)
     * 
     * @param array $tracking
     * @return int Severity score
     */
    private function calculate_decay_severity($tracking) {
        $severity = 0;
        
        // Negative velocity = bad
        if ($tracking['rank_velocity'] < 0) {
            $severity += min(40, abs($tracking['rank_velocity']) * 20);
        }
        
        // Was in top 10, now worse = critical
        if ($tracking['previous_position'] <= 10 && $tracking['current_position'] > 10) {
            $severity += 30;
        }
        
        // Declining trend = bad
        if ($tracking['trend'] === 'declining') {
            $severity += 20;
        }
        
        // High volatility = concerning
        if ($tracking['volatility'] > 60) {
            $severity += 10;
        }
        
        return min(100, $severity);
    }
    
    /**
     * Detect stagnant keywords (no movement)
     * 
     * @param int $limit
     * @return array Stagnant keywords
     */
    public function detect_stagnation($limit = 20) {
        $stagnant = array();
        
        try {
            if (!class_exists('DODO_GSC_Intelligence')) {
                return $stagnant;
            }
            
            $gsc = new DODO_GSC_Intelligence();
            
            if (!$gsc->is_available()) {
                return $stagnant;
            }
            
            // Get all tracked keywords
            $raw_data = $gsc->fetch_gsc_data(28);
            
            if (empty($raw_data)) {
                return $stagnant;
            }
            
            $keywords_checked = array();
            
            foreach ($raw_data as $row) {
                $keyword = $row['keys'][0] ?? '';
                
                if (empty($keyword) || in_array($keyword, $keywords_checked)) {
                    continue;
                }
                
                $keywords_checked[] = $keyword;
                
                // Track this keyword
                $tracking = $this->track_rank_changes($keyword);
                
                // Look for near-zero velocity and stable trend
                if (abs($tracking['rank_velocity']) < 0.1 && $tracking['trend'] === 'stable') {
                    // Only care about position 5-30 (opportunity zone)
                    if ($tracking['current_position'] >= 5 && $tracking['current_position'] <= 30) {
                        $stagnant[] = array(
                            'keyword' => $keyword,
                            'current_position' => $tracking['current_position'],
                            'velocity' => $tracking['rank_velocity'],
                            'days_stagnant' => $this->calculate_stagnant_days($tracking['history']),
                        );
                    }
                }
                
                if (count($stagnant) >= $limit) {
                    break;
                }
            }
            
            // Sort by position (better positions first)
            usort($stagnant, function($a, $b) {
                return $a['current_position'] <=> $b['current_position'];
            });
            
        } catch (Throwable $e) {
            error_log('[DODO][Rank Tracker] Error detecting stagnation: ' . $e->getMessage());
        }
        
        return $stagnant;
    }
    
    /**
     * Calculate how many days keyword has been stagnant
     * 
     * @param array $history
     * @return int Days
     */
    private function calculate_stagnant_days($history) {
        if (count($history) < 2) {
            return 0;
        }
        
        $current_position = $history[0]['position'] ?? 0;
        $days = 0;
        
        foreach ($history as $item) {
            $position = $item['position'] ?? 0;
            
            // If position changed by more than 2, not stagnant anymore
            if (abs($position - $current_position) > 2) {
                break;
            }
            
            $days++;
        }
        
        return $days;
    }
    
    /**
     * Get rank statistics
     * 
     * @return array Statistics
     */
    public function get_statistics() {
        $stats = array(
            'total_tracked' => 0,
            'improving' => 0,
            'declining' => 0,
            'stable' => 0,
            'volatile' => 0,
            'avg_velocity' => 0,
            'avg_volatility' => 0,
        );
        
        try {
            if (!class_exists('DODO_GSC_Intelligence')) {
                return $stats;
            }
            
            $gsc = new DODO_GSC_Intelligence();
            
            if (!$gsc->is_available()) {
                return $stats;
            }
            
            // Get sample of keywords
            $raw_data = $gsc->fetch_gsc_data(7);
            
            if (empty($raw_data)) {
                return $stats;
            }
            
            $keywords_checked = array();
            $velocities = array();
            $volatilities = array();
            
            foreach ($raw_data as $row) {
                $keyword = $row['keys'][0] ?? '';
                
                if (empty($keyword) || in_array($keyword, $keywords_checked)) {
                    continue;
                }
                
                $keywords_checked[] = $keyword;
                
                // Track keyword
                $tracking = $this->track_rank_changes($keyword);
                
                $stats['total_tracked']++;
                
                // Count by trend
                switch ($tracking['trend']) {
                    case 'improving':
                        $stats['improving']++;
                        break;
                    case 'declining':
                        $stats['declining']++;
                        break;
                    case 'stable':
                        $stats['stable']++;
                        break;
                    case 'volatile':
                        $stats['volatile']++;
                        break;
                }
                
                $velocities[] = $tracking['rank_velocity'];
                $volatilities[] = $tracking['volatility'];
                
                // Limit to 100 keywords for performance
                if ($stats['total_tracked'] >= 100) {
                    break;
                }
            }
            
            // Calculate averages
            if (!empty($velocities)) {
                $stats['avg_velocity'] = round(array_sum($velocities) / count($velocities), 2);
            }
            
            if (!empty($volatilities)) {
                $stats['avg_volatility'] = round(array_sum($volatilities) / count($volatilities));
            }
            
        } catch (Throwable $e) {
            error_log('[DODO][Rank Tracker] Error getting statistics: ' . $e->getMessage());
        }
        
        return $stats;
    }
}
