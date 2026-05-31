<?php
/**
 * GEO/AI Visibility Tracking Engine
 * 
 * Tracks AI/GEO visibility changes over time
 * Sprint E - Feedback Loop & Self-Learning SEO System
 * 
 * @package DODO_AI_SEO
 * @since 3.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_GEO_Tracker_Engine {
    
    /**
     * Track GEO visibility for content
     * 
     * @param int $post_id
     * @return array GEO tracking data
     */
    public function track_geo_visibility($post_id) {
        $tracking = array(
            'post_id' => $post_id,
            'current_score' => 0,
            'previous_score' => 0,
            'score_change' => 0,
            'trend' => 'unknown',
            'features' => array(),
            'history' => array(),
        );
        
        try {
            // Calculate current GEO score
            $current_score = $this->calculate_geo_score($post_id);
            
            // Get historical scores
            $history = $this->get_geo_history($post_id);
            
            // Calculate metrics
            $tracking['current_score'] = $current_score;
            $tracking['history'] = $history;
            
            if (!empty($history)) {
                $tracking['previous_score'] = $history[0]['score'] ?? 0;
                $tracking['score_change'] = $current_score - $tracking['previous_score'];
                $tracking['trend'] = $this->determine_geo_trend($history, $current_score);
            }
            
            // Analyze features
            $tracking['features'] = $this->analyze_geo_features($post_id);
            
            // Save snapshot
            $this->save_geo_snapshot($post_id, $current_score, $tracking['features']);
            
        } catch (Throwable $e) {
            error_log('[DODO][GEO Tracker] Error tracking visibility: ' . $e->getMessage());
        }
        
        return $tracking;
    }
    
    /**
     * Calculate GEO score for post
     * 
     * @param int $post_id
     * @return int Score (0-100)
     */
    private function calculate_geo_score($post_id) {
        $score = 0;
        
        try {
            $post = get_post($post_id);
            
            if (!$post) {
                return $score;
            }
            
            $content = $post->post_content;
            
            // FAQ presence (30 points)
            $faq_count = substr_count($content, 'wp:rank-math/faq-block');
            if ($faq_count > 0) {
                $score += min(30, $faq_count * 10);
            }
            
            // Structured headings (20 points)
            $h2_count = substr_count($content, '<h2');
            $h3_count = substr_count($content, '<h3');
            
            if ($h2_count >= 3 && $h3_count >= 5) {
                $score += 20;
            } elseif ($h2_count >= 2 && $h3_count >= 3) {
                $score += 15;
            } elseif ($h2_count >= 1) {
                $score += 10;
            }
            
            // List presence (15 points)
            $list_count = substr_count($content, '<ul') + substr_count($content, '<ol');
            if ($list_count >= 3) {
                $score += 15;
            } elseif ($list_count >= 2) {
                $score += 10;
            } elseif ($list_count >= 1) {
                $score += 5;
            }
            
            // Table presence (10 points)
            if (strpos($content, '<table') !== false) {
                $score += 10;
            }
            
            // Conversational quality (15 points)
            $conversational_score = $this->assess_conversational_quality($content);
            $score += $conversational_score;
            
            // Entity richness (10 points)
            $entity_score = $this->assess_entity_richness($content);
            $score += $entity_score;
            
        } catch (Throwable $e) {
            error_log('[DODO][GEO Tracker] Error calculating score: ' . $e->getMessage());
        }
        
        return min(100, $score);
    }
    
    /**
     * Assess conversational quality
     * 
     * @param string $content
     * @return int Score (0-15)
     */
    private function assess_conversational_quality($content) {
        $score = 0;
        
        // Question presence
        $question_count = substr_count($content, '?');
        if ($question_count >= 5) {
            $score += 5;
        } elseif ($question_count >= 3) {
            $score += 3;
        }
        
        // Direct address (you, your)
        $direct_address = preg_match_all('/(you|your|sizin|senin)/i', $content);
        if ($direct_address >= 10) {
            $score += 5;
        } elseif ($direct_address >= 5) {
            $score += 3;
        }
        
        // Natural language patterns
        $natural_patterns = array('örneğin', 'mesela', 'yani', 'başka bir deyişle', 'for example', 'in other words');
        $natural_count = 0;
        
        foreach ($natural_patterns as $pattern) {
            if (stripos($content, $pattern) !== false) {
                $natural_count++;
            }
        }
        
        if ($natural_count >= 3) {
            $score += 5;
        } elseif ($natural_count >= 2) {
            $score += 3;
        }
        
        return min(15, $score);
    }
    
    /**
     * Assess entity richness
     * 
     * @param string $content
     * @return int Score (0-10)
     */
    private function assess_entity_richness($content) {
        $score = 0;
        
        // Proper nouns (capitalized words)
        $proper_noun_count = preg_match_all('/\b[A-ZÇĞİÖŞÜ][a-zçğıöşü]+\b/', $content);
        
        if ($proper_noun_count >= 20) {
            $score += 5;
        } elseif ($proper_noun_count >= 10) {
            $score += 3;
        }
        
        // Numbers and data
        $number_count = preg_match_all('/\b\d+\b/', $content);
        
        if ($number_count >= 15) {
            $score += 5;
        } elseif ($number_count >= 8) {
            $score += 3;
        }
        
        return min(10, $score);
    }
    
    /**
     * Get GEO history
     * 
     * @param int $post_id
     * @return array History
     */
    private function get_geo_history($post_id) {
        $history = get_post_meta($post_id, '_dodo_geo_history', true);
        
        if (!is_array($history)) {
            $history = array();
        }
        
        // Sort by date descending
        usort($history, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
        
        // Keep last 90 days
        $history = array_slice($history, 0, 90);
        
        return $history;
    }
    
    /**
     * Save GEO snapshot
     * 
     * @param int $post_id
     * @param int $score
     * @param array $features
     */
    private function save_geo_snapshot($post_id, $score, $features) {
        $history = $this->get_geo_history($post_id);
        
        $snapshot = array(
            'date' => current_time('Y-m-d'),
            'score' => $score,
            'features' => $features,
            'timestamp' => current_time('timestamp'),
        );
        
        // Check if today already tracked
        $today_tracked = false;
        foreach ($history as $key => $item) {
            if ($item['date'] === $snapshot['date']) {
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
        
        update_post_meta($post_id, '_dodo_geo_history', $history);
        update_post_meta($post_id, '_dodo_geo_score', $score);
    }
    
    /**
     * Determine GEO trend
     * 
     * @param array $history
     * @param int $current_score
     * @return string Trend
     */
    private function determine_geo_trend($history, $current_score) {
        if (count($history) < 7) {
            return 'insufficient_data';
        }
        
        // Compare last 7 days vs previous 7 days
        $recent = array_slice($history, 0, 7);
        $previous = array_slice($history, 7, 7);
        
        if (empty($previous)) {
            return 'new';
        }
        
        $recent_avg = array_sum(array_column($recent, 'score')) / count($recent);
        $previous_avg = array_sum(array_column($previous, 'score')) / count($previous);
        
        $change = $recent_avg - $previous_avg;
        
        if ($change > 5) {
            return 'improving';
        } elseif ($change < -5) {
            return 'declining';
        } else {
            return 'stable';
        }
    }
    
    /**
     * Analyze GEO features
     * 
     * @param int $post_id
     * @return array Features
     */
    private function analyze_geo_features($post_id) {
        $features = array(
            'has_faq' => false,
            'faq_count' => 0,
            'has_structured_headings' => false,
            'heading_count' => 0,
            'has_lists' => false,
            'list_count' => 0,
            'has_tables' => false,
            'conversational_score' => 0,
            'entity_score' => 0,
        );
        
        try {
            $post = get_post($post_id);
            
            if (!$post) {
                return $features;
            }
            
            $content = $post->post_content;
            
            // FAQ
            $faq_count = substr_count($content, 'wp:rank-math/faq-block');
            $features['has_faq'] = $faq_count > 0;
            $features['faq_count'] = $faq_count;
            
            // Headings
            $h2_count = substr_count($content, '<h2');
            $h3_count = substr_count($content, '<h3');
            $features['heading_count'] = $h2_count + $h3_count;
            $features['has_structured_headings'] = $h2_count >= 2 && $h3_count >= 3;
            
            // Lists
            $list_count = substr_count($content, '<ul') + substr_count($content, '<ol');
            $features['has_lists'] = $list_count > 0;
            $features['list_count'] = $list_count;
            
            // Tables
            $features['has_tables'] = strpos($content, '<table') !== false;
            
            // Conversational
            $features['conversational_score'] = $this->assess_conversational_quality($content);
            
            // Entity
            $features['entity_score'] = $this->assess_entity_richness($content);
            
        } catch (Throwable $e) {
            error_log('[DODO][GEO Tracker] Error analyzing features: ' . $e->getMessage());
        }
        
        return $features;
    }
    
    /**
     * Compare GEO scores over time
     * 
     * @param int $post_id
     * @return array Comparison
     */
    public function compare_geo_scores($post_id) {
        $comparison = array(
            'available' => false,
            'current_score' => 0,
            'initial_score' => 0,
            'change' => 0,
            'trend' => 'unknown',
            'milestones' => array(),
        );
        
        try {
            $history = $this->get_geo_history($post_id);
            
            if (empty($history)) {
                return $comparison;
            }
            
            $comparison['available'] = true;
            $comparison['current_score'] = $history[0]['score'] ?? 0;
            $comparison['initial_score'] = end($history)['score'] ?? 0;
            $comparison['change'] = $comparison['current_score'] - $comparison['initial_score'];
            
            // Determine trend
            if ($comparison['change'] > 10) {
                $comparison['trend'] = 'significant_improvement';
            } elseif ($comparison['change'] > 5) {
                $comparison['trend'] = 'improving';
            } elseif ($comparison['change'] < -10) {
                $comparison['trend'] = 'significant_decline';
            } elseif ($comparison['change'] < -5) {
                $comparison['trend'] = 'declining';
            } else {
                $comparison['trend'] = 'stable';
            }
            
            // Detect milestones
            foreach ($history as $item) {
                $score = $item['score'];
                
                if ($score >= 80 && !isset($comparison['milestones']['excellent'])) {
                    $comparison['milestones']['excellent'] = $item['date'];
                }
                
                if ($score >= 60 && !isset($comparison['milestones']['good'])) {
                    $comparison['milestones']['good'] = $item['date'];
                }
            }
            
        } catch (Throwable $e) {
            error_log('[DODO][GEO Tracker] Error comparing scores: ' . $e->getMessage());
        }
        
        return $comparison;
    }
    
    /**
     * Detect AI answer growth
     * 
     * @param int $post_id
     * @return array Growth analysis
     */
    public function detect_ai_answer_growth($post_id) {
        $growth = array(
            'detected' => false,
            'growth_rate' => 0,
            'features_added' => array(),
            'recommendations' => array(),
        );
        
        try {
            $history = $this->get_geo_history($post_id);
            
            if (count($history) < 14) {
                return $growth;
            }
            
            // Compare last 7 days vs previous 7 days
            $recent = array_slice($history, 0, 7);
            $previous = array_slice($history, 7, 7);
            
            $recent_avg = array_sum(array_column($recent, 'score')) / count($recent);
            $previous_avg = array_sum(array_column($previous, 'score')) / count($previous);
            
            $growth['growth_rate'] = round((($recent_avg - $previous_avg) / $previous_avg) * 100, 1);
            
            if ($growth['growth_rate'] > 10) {
                $growth['detected'] = true;
                
                // Detect which features were added
                $recent_features = $recent[0]['features'] ?? array();
                $previous_features = $previous[0]['features'] ?? array();
                
                if (($recent_features['has_faq'] ?? false) && !($previous_features['has_faq'] ?? false)) {
                    $growth['features_added'][] = 'FAQ eklendi';
                }
                
                if (($recent_features['faq_count'] ?? 0) > ($previous_features['faq_count'] ?? 0)) {
                    $growth['features_added'][] = 'FAQ sayısı arttı';
                }
                
                if (($recent_features['conversational_score'] ?? 0) > ($previous_features['conversational_score'] ?? 0)) {
                    $growth['features_added'][] = 'Konuşma kalitesi iyileşti';
                }
            }
            
            // Generate recommendations
            $current_score = $recent[0]['score'] ?? 0;
            
            if ($current_score < 60) {
                $growth['recommendations'][] = 'FAQ blokları ekleyin';
                $growth['recommendations'][] = 'Yapılandırılmış başlıklar kullanın';
                $growth['recommendations'][] = 'Liste ve tablolar ekleyin';
            } elseif ($current_score < 80) {
                $growth['recommendations'][] = 'Daha fazla FAQ sorusu ekleyin';
                $growth['recommendations'][] = 'Konuşma dilini güçlendirin';
            }
            
        } catch (Throwable $e) {
            error_log('[DODO][GEO Tracker] Error detecting growth: ' . $e->getMessage());
        }
        
        return $growth;
    }
    
    /**
     * Get GEO statistics
     * 
     * @return array Statistics
     */
    public function get_statistics() {
        $stats = array(
            'total_tracked' => 0,
            'excellent' => 0, // >= 80
            'good' => 0, // 60-79
            'fair' => 0, // 40-59
            'poor' => 0, // < 40
            'avg_score' => 0,
            'improving_count' => 0,
            'declining_count' => 0,
        );
        
        try {
            global $wpdb;
            
            // Get all posts with GEO scores
            $results = $wpdb->get_results(
                "SELECT post_id, meta_value as score 
                FROM {$wpdb->postmeta} 
                WHERE meta_key = '_dodo_geo_score'"
            );
            
            $scores = array();
            
            foreach ($results as $row) {
                $score = intval($row->meta_value);
                $scores[] = $score;
                
                $stats['total_tracked']++;
                
                if ($score >= 80) {
                    $stats['excellent']++;
                } elseif ($score >= 60) {
                    $stats['good']++;
                } elseif ($score >= 40) {
                    $stats['fair']++;
                } else {
                    $stats['poor']++;
                }
                
                // Check trend
                $tracking = $this->track_geo_visibility($row->post_id);
                
                if ($tracking['trend'] === 'improving' || $tracking['trend'] === 'significant_improvement') {
                    $stats['improving_count']++;
                } elseif ($tracking['trend'] === 'declining' || $tracking['trend'] === 'significant_decline') {
                    $stats['declining_count']++;
                }
            }
            
            if (!empty($scores)) {
                $stats['avg_score'] = round(array_sum($scores) / count($scores));
            }
            
        } catch (Throwable $e) {
            error_log('[DODO][GEO Tracker] Error getting statistics: ' . $e->getMessage());
        }
        
        return $stats;
    }
    
    /**
     * Get posts by GEO score range
     * 
     * @param int $min_score
     * @param int $max_score
     * @return array Post IDs
     */
    public function get_posts_by_score_range($min_score = 0, $max_score = 100) {
        global $wpdb;
        
        $post_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT post_id 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_dodo_geo_score' 
            AND CAST(meta_value AS UNSIGNED) >= %d 
            AND CAST(meta_value AS UNSIGNED) <= %d
            ORDER BY CAST(meta_value AS UNSIGNED) DESC",
            $min_score,
            $max_score
        ));
        
        return array_map('intval', $post_ids);
    }
}
