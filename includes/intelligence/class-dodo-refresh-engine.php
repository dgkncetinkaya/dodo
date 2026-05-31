<?php
/**
 * Content Refresh Engine
 * 
 * Detects stale content that needs updating
 * Sprint E - Feedback Loop & Self-Learning SEO System
 * 
 * @package DODO_AI_SEO
 * @since 3.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Refresh_Engine {
    
    /**
     * Detect refresh candidates
     * 
     * @param int $limit
     * @return array Refresh candidates
     */
    public function detect_refresh_candidates($limit = 20) {
        $candidates = array();
        
        try {
            // Get published posts
            $posts = get_posts(array(
                'post_type' => 'post',
                'post_status' => 'publish',
                'posts_per_page' => 100,
                'orderby' => 'date',
                'order' => 'DESC',
            ));
            
            foreach ($posts as $post) {
                $refresh_score = $this->calculate_refresh_score($post->ID);
                
                if ($refresh_score['needs_refresh']) {
                    $candidates[] = array(
                        'post_id' => $post->ID,
                        'title' => $post->post_title,
                        'refresh_score' => $refresh_score['score'],
                        'reasons' => $refresh_score['reasons'],
                        'priority' => $refresh_score['priority'],
                        'estimated_impact' => $refresh_score['estimated_impact'],
                    );
                }
                
                if (count($candidates) >= $limit) {
                    break;
                }
            }
            
            // Sort by refresh score
            usort($candidates, function($a, $b) {
                return $b['refresh_score'] <=> $a['refresh_score'];
            });
            
        } catch (Throwable $e) {
            error_log('[DODO][Refresh Engine] Error detecting candidates: ' . $e->getMessage());
        }
        
        return $candidates;
    }
    
    /**
     * Calculate refresh score for post
     * 
     * @param int $post_id
     * @return array Refresh analysis
     */
    private function calculate_refresh_score($post_id) {
        $analysis = array(
            'score' => 0,
            'needs_refresh' => false,
            'reasons' => array(),
            'priority' => 'low',
            'estimated_impact' => 0,
        );
        
        try {
            // 1. Check declining impressions (30 points)
            $impressions_decline = $this->check_declining_impressions($post_id);
            if ($impressions_decline['declining']) {
                $analysis['score'] += 30;
                $analysis['reasons'][] = $impressions_decline['reason'];
            }
            
            // 2. Check declining CTR (25 points)
            $ctr_decline = $this->check_declining_ctr($post_id);
            if ($ctr_decline['declining']) {
                $analysis['score'] += 25;
                $analysis['reasons'][] = $ctr_decline['reason'];
            }
            
            // 3. Check content age (20 points)
            $age_check = $this->check_content_age($post_id);
            if ($age_check['outdated']) {
                $analysis['score'] += 20;
                $analysis['reasons'][] = $age_check['reason'];
            }
            
            // 4. Check semantic coverage (15 points)
            $semantic_check = $this->check_semantic_staleness($post_id);
            if ($semantic_check['stale']) {
                $analysis['score'] += 15;
                $analysis['reasons'][] = $semantic_check['reason'];
            }
            
            // 5. Check outdated entities (10 points)
            $entity_check = $this->check_outdated_entities($post_id);
            if ($entity_check['outdated']) {
                $analysis['score'] += 10;
                $analysis['reasons'][] = $entity_check['reason'];
            }
            
            // Determine if needs refresh
            $analysis['needs_refresh'] = $analysis['score'] >= 40;
            
            // Determine priority
            if ($analysis['score'] >= 70) {
                $analysis['priority'] = 'critical';
            } elseif ($analysis['score'] >= 55) {
                $analysis['priority'] = 'high';
            } elseif ($analysis['score'] >= 40) {
                $analysis['priority'] = 'medium';
            }
            
            // Estimate impact
            $analysis['estimated_impact'] = $this->estimate_refresh_impact($post_id, $analysis['score']);
            
        } catch (Throwable $e) {
            error_log('[DODO][Refresh Engine] Error calculating score: ' . $e->getMessage());
        }
        
        return $analysis;
    }
    
    /**
     * Check declining impressions
     * 
     * @param int $post_id
     * @return array Check result
     */
    private function check_declining_impressions($post_id) {
        $result = array(
            'declining' => false,
            'reason' => '',
        );
        
        try {
            $performance_score = get_post_meta($post_id, '_dodo_performance_score', true);
            $growth_rate = get_post_meta($post_id, '_dodo_growth_rate', true);
            
            if ($performance_score !== '' && $growth_rate !== '') {
                if ($performance_score < 40 && $growth_rate < 0) {
                    $result['declining'] = true;
                    $result['reason'] = 'Gösterim sayısı düşüyor (Performance: ' . $performance_score . ')';
                }
            }
            
        } catch (Throwable $e) {
            error_log('[DODO][Refresh Engine] Error checking impressions: ' . $e->getMessage());
        }
        
        return $result;
    }
    
    /**
     * Check declining CTR
     * 
     * @param int $post_id
     * @return array Check result
     */
    private function check_declining_ctr($post_id) {
        $result = array(
            'declining' => false,
            'reason' => '',
        );
        
        try {
            $current_snapshot = get_post_meta($post_id, '_dodo_current_snapshot', true);
            $publish_snapshot = get_post_meta($post_id, '_dodo_publish_snapshot', true);
            
            if (is_array($current_snapshot) && is_array($publish_snapshot)) {
                $current_ctr = $current_snapshot['ctr'] ?? 0;
                $publish_ctr = $publish_snapshot['ctr'] ?? 0;
                
                if ($publish_ctr > 0 && $current_ctr < $publish_ctr * 0.7) {
                    $result['declining'] = true;
                    $result['reason'] = 'CTR %30+ düştü (' . round($current_ctr, 2) . '% → ' . round($publish_ctr, 2) . '%)';
                }
            }
            
        } catch (Throwable $e) {
            error_log('[DODO][Refresh Engine] Error checking CTR: ' . $e->getMessage());
        }
        
        return $result;
    }
    
    /**
     * Check content age
     * 
     * @param int $post_id
     * @return array Check result
     */
    private function check_content_age($post_id) {
        $result = array(
            'outdated' => false,
            'reason' => '',
        );
        
        try {
            $post = get_post($post_id);
            
            if (!$post) {
                return $result;
            }
            
            $publish_date = strtotime($post->post_date);
            $modified_date = strtotime($post->post_modified);
            $current_date = current_time('timestamp');
            
            $days_since_publish = floor(($current_date - $publish_date) / DAY_IN_SECONDS);
            $days_since_modified = floor(($current_date - $modified_date) / DAY_IN_SECONDS);
            
            // Content older than 1 year and not updated in 6 months
            if ($days_since_publish > 365 && $days_since_modified > 180) {
                $result['outdated'] = true;
                $result['reason'] = 'İçerik 1 yıldan eski ve 6 aydır güncellenmemiş';
            }
            // Content older than 2 years
            elseif ($days_since_publish > 730) {
                $result['outdated'] = true;
                $result['reason'] = 'İçerik 2 yıldan eski';
            }
            
            // Check for year in title
            $title = $post->post_title;
            $current_year = date('Y');
            
            if (preg_match('/20\d{2}/', $title, $matches)) {
                $title_year = $matches[0];
                
                if ($title_year < $current_year - 1) {
                    $result['outdated'] = true;
                    $result['reason'] = 'Başlıkta eski yıl var (' . $title_year . ')';
                }
            }
            
        } catch (Throwable $e) {
            error_log('[DODO][Refresh Engine] Error checking age: ' . $e->getMessage());
        }
        
        return $result;
    }
    
    /**
     * Check semantic staleness
     * 
     * @param int $post_id
     * @return array Check result
     */
    private function check_semantic_staleness($post_id) {
        $result = array(
            'stale' => false,
            'reason' => '',
        );
        
        try {
            $post = get_post($post_id);
            
            if (!$post) {
                return $result;
            }
            
            $content = $post->post_content;
            $word_count = str_word_count(strip_tags($content));
            
            // Check content depth
            if ($word_count < 800) {
                $result['stale'] = true;
                $result['reason'] = 'İçerik çok kısa (' . $word_count . ' kelime)';
                return $result;
            }
            
            // Check FAQ presence
            $has_faq = strpos($content, 'wp:rank-math/faq-block') !== false;
            
            if (!$has_faq) {
                $result['stale'] = true;
                $result['reason'] = 'FAQ bloğu eksik';
                return $result;
            }
            
            // Check heading structure
            $h2_count = substr_count($content, '<h2');
            $h3_count = substr_count($content, '<h3');
            
            if ($h2_count < 3 || $h3_count < 5) {
                $result['stale'] = true;
                $result['reason'] = 'Başlık yapısı zayıf (H2: ' . $h2_count . ', H3: ' . $h3_count . ')';
                return $result;
            }
            
        } catch (Throwable $e) {
            error_log('[DODO][Refresh Engine] Error checking semantic: ' . $e->getMessage());
        }
        
        return $result;
    }
    
    /**
     * Check outdated entities
     * 
     * @param int $post_id
     * @return array Check result
     */
    private function check_outdated_entities($post_id) {
        $result = array(
            'outdated' => false,
            'reason' => '',
        );
        
        try {
            $post = get_post($post_id);
            
            if (!$post) {
                return $result;
            }
            
            $content = $post->post_content;
            $title = $post->post_title;
            
            // Check for old version numbers
            if (preg_match('/(version|v)\s*\d+\.\d+/i', $content . ' ' . $title)) {
                $result['outdated'] = true;
                $result['reason'] = 'Eski versiyon numaraları içeriyor';
                return $result;
            }
            
            // Check for old years in content
            $current_year = date('Y');
            $old_years = array($current_year - 2, $current_year - 3, $current_year - 4);
            
            foreach ($old_years as $year) {
                if (substr_count($content, (string)$year) > 2) {
                    $result['outdated'] = true;
                    $result['reason'] = 'İçerikte eski yıl referansları var (' . $year . ')';
                    return $result;
                }
            }
            
        } catch (Throwable $e) {
            error_log('[DODO][Refresh Engine] Error checking entities: ' . $e->getMessage());
        }
        
        return $result;
    }
    
    /**
     * Estimate refresh impact
     * 
     * @param int $post_id
     * @param int $refresh_score
     * @return int Impact score (0-100)
     */
    public function estimate_refresh_impact($post_id, $refresh_score) {
        $impact = 0;
        
        try {
            // Base impact from refresh score
            $impact = min(50, $refresh_score);
            
            // Check current traffic
            $current_snapshot = get_post_meta($post_id, '_dodo_current_snapshot', true);
            
            if (is_array($current_snapshot)) {
                $impressions = $current_snapshot['impressions'] ?? 0;
                
                // High traffic = high impact potential
                if ($impressions > 1000) {
                    $impact += 30;
                } elseif ($impressions > 500) {
                    $impact += 20;
                } elseif ($impressions > 100) {
                    $impact += 10;
                }
            }
            
            // Check ranking position
            $position = $current_snapshot['position'] ?? 0;
            
            // Position 5-15 = high impact potential
            if ($position >= 5 && $position <= 15) {
                $impact += 20;
            } elseif ($position >= 15 && $position <= 30) {
                $impact += 10;
            }
            
        } catch (Throwable $e) {
            error_log('[DODO][Refresh Engine] Error estimating impact: ' . $e->getMessage());
        }
        
        return min(100, $impact);
    }
    
    /**
     * Suggest refresh actions
     * 
     * @param int $post_id
     * @return array Suggested actions
     */
    public function suggest_refresh_actions($post_id) {
        $actions = array();
        
        try {
            $post = get_post($post_id);
            
            if (!$post) {
                return $actions;
            }
            
            $content = $post->post_content;
            $title = $post->post_title;
            
            // Title refresh
            $current_year = date('Y');
            if (!preg_match("/{$current_year}/", $title)) {
                $actions[] = array(
                    'type' => 'title_refresh',
                    'priority' => 'high',
                    'action' => 'Başlığa güncel yıl ekle (' . $current_year . ')',
                    'estimated_effort' => 'low',
                );
            }
            
            // FAQ addition
            $has_faq = strpos($content, 'wp:rank-math/faq-block') !== false;
            if (!$has_faq) {
                $actions[] = array(
                    'type' => 'add_faq',
                    'priority' => 'high',
                    'action' => 'FAQ bloğu ekle',
                    'estimated_effort' => 'medium',
                );
            }
            
            // Entity expansion
            $word_count = str_word_count(strip_tags($content));
            if ($word_count < 1500) {
                $actions[] = array(
                    'type' => 'expand_content',
                    'priority' => 'medium',
                    'action' => 'İçeriği genişlet (Mevcut: ' . $word_count . ' kelime)',
                    'estimated_effort' => 'high',
                );
            }
            
            // Semantic coverage
            $h2_count = substr_count($content, '<h2');
            if ($h2_count < 5) {
                $actions[] = array(
                    'type' => 'add_sections',
                    'priority' => 'medium',
                    'action' => 'Daha fazla bölüm ekle (Mevcut: ' . $h2_count . ' H2)',
                    'estimated_effort' => 'medium',
                );
            }
            
            // Internal links
            $internal_link_count = substr_count($content, get_site_url());
            if ($internal_link_count < 3) {
                $actions[] = array(
                    'type' => 'add_internal_links',
                    'priority' => 'low',
                    'action' => 'İç linkler ekle',
                    'estimated_effort' => 'low',
                );
            }
            
            // CTA strengthening
            $cta_words = array('öğren', 'keşfet', 'incele', 'başla');
            $has_cta = false;
            
            foreach ($cta_words as $word) {
                if (stripos($content, $word) !== false) {
                    $has_cta = true;
                    break;
                }
            }
            
            if (!$has_cta) {
                $actions[] = array(
                    'type' => 'add_cta',
                    'priority' => 'low',
                    'action' => 'CTA ekle veya güçlendir',
                    'estimated_effort' => 'low',
                );
            }
            
        } catch (Throwable $e) {
            error_log('[DODO][Refresh Engine] Error suggesting actions: ' . $e->getMessage());
        }
        
        return $actions;
    }
    
    /**
     * Mark post as needing refresh
     * 
     * @param int $post_id
     * @param array $analysis
     */
    public function mark_needs_refresh($post_id, $analysis) {
        update_post_meta($post_id, '_dodo_needs_refresh', true);
        update_post_meta($post_id, '_dodo_refresh_score', $analysis['score']);
        update_post_meta($post_id, '_dodo_refresh_priority', $analysis['priority']);
        update_post_meta($post_id, '_dodo_refresh_reasons', $analysis['reasons']);
        update_post_meta($post_id, '_dodo_refresh_detected_at', current_time('mysql'));
    }
    
    /**
     * Mark post as refreshed
     * 
     * @param int $post_id
     */
    public function mark_refreshed($post_id) {
        delete_post_meta($post_id, '_dodo_needs_refresh');
        delete_post_meta($post_id, '_dodo_refresh_score');
        delete_post_meta($post_id, '_dodo_refresh_priority');
        delete_post_meta($post_id, '_dodo_refresh_reasons');
        delete_post_meta($post_id, '_dodo_refresh_detected_at');
        
        update_post_meta($post_id, '_dodo_last_refreshed', current_time('mysql'));
    }
    
    /**
     * Get refresh statistics
     * 
     * @return array Statistics
     */
    public function get_statistics() {
        $stats = array(
            'total_needs_refresh' => 0,
            'critical' => 0,
            'high' => 0,
            'medium' => 0,
            'low' => 0,
            'avg_refresh_score' => 0,
            'total_refreshed' => 0,
        );
        
        try {
            global $wpdb;
            
            // Count posts needing refresh
            $needs_refresh = $wpdb->get_results(
                "SELECT post_id, meta_value as priority 
                FROM {$wpdb->postmeta} 
                WHERE meta_key = '_dodo_refresh_priority'"
            );
            
            $scores = array();
            
            foreach ($needs_refresh as $row) {
                $stats['total_needs_refresh']++;
                
                $priority = $row->meta_value;
                
                switch ($priority) {
                    case 'critical':
                        $stats['critical']++;
                        break;
                    case 'high':
                        $stats['high']++;
                        break;
                    case 'medium':
                        $stats['medium']++;
                        break;
                    case 'low':
                        $stats['low']++;
                        break;
                }
                
                $score = get_post_meta($row->post_id, '_dodo_refresh_score', true);
                if ($score) {
                    $scores[] = intval($score);
                }
            }
            
            // Average score
            if (!empty($scores)) {
                $stats['avg_refresh_score'] = round(array_sum($scores) / count($scores));
            }
            
            // Count refreshed posts
            $stats['total_refreshed'] = $wpdb->get_var(
                "SELECT COUNT(*) 
                FROM {$wpdb->postmeta} 
                WHERE meta_key = '_dodo_last_refreshed'"
            );
            
        } catch (Throwable $e) {
            error_log('[DODO][Refresh Engine] Error getting statistics: ' . $e->getMessage());
        }
        
        return $stats;
    }
    
    /**
     * Get posts by refresh priority
     * 
     * @param string $priority
     * @return array Post IDs
     */
    public function get_posts_by_priority($priority = 'all') {
        global $wpdb;
        
        if ($priority === 'all') {
            $post_ids = $wpdb->get_col(
                "SELECT post_id 
                FROM {$wpdb->postmeta} 
                WHERE meta_key = '_dodo_needs_refresh' 
                AND meta_value = '1'"
            );
        } else {
            $post_ids = $wpdb->get_col($wpdb->prepare(
                "SELECT post_id 
                FROM {$wpdb->postmeta} 
                WHERE meta_key = '_dodo_refresh_priority' 
                AND meta_value = %s",
                $priority
            ));
        }
        
        return array_map('intval', $post_ids);
    }
}
