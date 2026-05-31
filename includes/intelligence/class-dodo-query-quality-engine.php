<?php
/**
 * Query Quality Engine
 * 
 * Filters spam and low-quality queries with precision
 * Phase 3 - SEO Strategist Intelligence
 * 
 * @package DODO_AI_SEO
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Query_Quality_Engine {
    
    /**
     * Spam patterns to filter
     */
    private $spam_patterns = array(
        '/^[0-9]+$/',                    // Only numbers
        '/^[^a-zA-Z0-9\s]+$/',          // Only special chars
        '/(.)\1{4,}/',                   // Repeated chars (aaaaa)
        '/^.{1,2}$/',                    // Too short (1-2 chars)
        '/porn|xxx|sex|casino|viagra/i', // Adult/spam keywords
    );
    
    /**
     * Check if query is high quality
     * 
     * @param string $query Query string
     * @param array $metrics GSC metrics (impressions, clicks, etc)
     * @return array Quality assessment
     */
    public function assess_quality($query, $metrics = array()) {
        $score = 100;
        $issues = array();
        $flags = array();
        
        // 1. Spam pattern check
        foreach ($this->spam_patterns as $pattern) {
            if (preg_match($pattern, $query)) {
                $score -= 50;
                $issues[] = 'Spam pattern detected';
                $flags[] = 'spam';
                break;
            }
        }
        
        // 2. Length check
        $length = strlen($query);
        if ($length < 3) {
            $score -= 40;
            $issues[] = 'Too short';
            $flags[] = 'too_short';
        } elseif ($length > 100) {
            $score -= 20;
            $issues[] = 'Too long';
            $flags[] = 'too_long';
        }
        
        // 3. Word count check
        $word_count = str_word_count($query);
        if ($word_count < 2) {
            $score -= 15;
            $issues[] = 'Single word query';
            $flags[] = 'single_word';
        }
        
        // 4. Special character ratio
        $special_char_count = preg_match_all('/[^a-zA-Z0-9\s]/', $query);
        $special_ratio = $length > 0 ? $special_char_count / $length : 0;
        
        if ($special_ratio > 0.3) {
            $score -= 25;
            $issues[] = 'Too many special characters';
            $flags[] = 'special_chars';
        }
        
        // 5. Metrics-based quality (if available)
        if (!empty($metrics)) {
            $impressions = $metrics['impressions'] ?? 0;
            $clicks = $metrics['clicks'] ?? 0;
            $position = $metrics['position'] ?? 999;
            
            // Very low impressions = noise
            if ($impressions < 5) {
                $score -= 20;
                $issues[] = 'Very low impressions (noise)';
                $flags[] = 'low_impressions';
            }
            
            // No clicks with high impressions = irrelevant
            if ($impressions > 100 && $clicks === 0) {
                $score -= 15;
                $issues[] = 'No clicks despite impressions';
                $flags[] = 'no_clicks';
            }
            
            // Very poor position with low impressions = not relevant
            if ($position > 50 && $impressions < 20) {
                $score -= 10;
                $issues[] = 'Poor position with low traffic';
                $flags[] = 'poor_position';
            }
        }
        
        // 6. Language check (basic)
        $has_letters = preg_match('/[a-zA-ZğüşıöçĞÜŞİÖÇ]/', $query);
        if (!$has_letters) {
            $score -= 30;
            $issues[] = 'No letters detected';
            $flags[] = 'no_letters';
        }
        
        // Final score
        $score = max(0, $score);
        
        // Quality level
        $quality_level = 'high';
        if ($score < 30) {
            $quality_level = 'spam';
        } elseif ($score < 50) {
            $quality_level = 'low';
        } elseif ($score < 70) {
            $quality_level = 'medium';
        }
        
        return array(
            'query' => $query,
            'quality_score' => $score,
            'quality_level' => $quality_level,
            'is_spam' => $quality_level === 'spam',
            'is_acceptable' => $score >= 50,
            'issues' => $issues,
            'flags' => $flags,
        );
    }
    
    /**
     * Filter queries by quality
     * 
     * @param array $queries Array of queries with metrics
     * @param int $min_score Minimum quality score
     * @return array Filtered queries
     */
    public function filter_queries($queries, $min_score = 50) {
        $filtered = array();
        
        foreach ($queries as $query_data) {
            $query = is_array($query_data) ? ($query_data['query'] ?? $query_data['keys'][0] ?? '') : $query_data;
            
            if (empty($query)) {
                continue;
            }
            
            $metrics = is_array($query_data) ? $query_data : array();
            $assessment = $this->assess_quality($query, $metrics);
            
            if ($assessment['quality_score'] >= $min_score) {
                $filtered[] = array_merge($query_data, array(
                    'quality_assessment' => $assessment,
                ));
            }
        }
        
        return $filtered;
    }
    
    /**
     * Get quality statistics for a set of queries
     * 
     * @param array $queries Array of queries
     * @return array Statistics
     */
    public function get_quality_stats($queries) {
        $stats = array(
            'total' => count($queries),
            'high_quality' => 0,
            'medium_quality' => 0,
            'low_quality' => 0,
            'spam' => 0,
            'avg_score' => 0,
        );
        
        $total_score = 0;
        
        foreach ($queries as $query_data) {
            $query = is_array($query_data) ? ($query_data['query'] ?? $query_data['keys'][0] ?? '') : $query_data;
            $metrics = is_array($query_data) ? $query_data : array();
            
            $assessment = $this->assess_quality($query, $metrics);
            $total_score += $assessment['quality_score'];
            
            switch ($assessment['quality_level']) {
                case 'high':
                    $stats['high_quality']++;
                    break;
                case 'medium':
                    $stats['medium_quality']++;
                    break;
                case 'low':
                    $stats['low_quality']++;
                    break;
                case 'spam':
                    $stats['spam']++;
                    break;
            }
        }
        
        $stats['avg_score'] = $stats['total'] > 0 ? round($total_score / $stats['total'], 1) : 0;
        
        return $stats;
    }
}
