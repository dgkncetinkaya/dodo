<?php
/**
 * Score Utilities - Centralized scoring logic
 * 
 * PHASE 7 - Architecture Cleanup
 * Consolidates duplicate scoring calculations
 * 
 * @package DODO_AI_SEO
 * @since 4.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (class_exists('DODO_Score_Utils')) {
    return;
}

class DODO_Score_Utils {
    
    /**
     * Normalize score to 0-100 range
     * 
     * @param float $score Score to normalize
     * @param float $min Minimum value
     * @param float $max Maximum value
     * @return int Normalized score 0-100
     */
    public static function normalize($score, $min = 0, $max = 100) {
        if ($max === $min) {
            return 50;
        }
        
        $normalized = (($score - $min) / ($max - $min)) * 100;
        return max(0, min(100, round($normalized)));
    }
    
    /**
     * Get score level (critical/high/medium/low)
     * 
     * @param int $score Score 0-100
     * @return string Level
     */
    public static function get_level($score) {
        if ($score >= 80) {
            return 'critical';
        } elseif ($score >= 60) {
            return 'high';
        } elseif ($score >= 40) {
            return 'medium';
        } else {
            return 'low';
        }
    }
    
    /**
     * Get score grade (A/B/C/D/F)
     * 
     * @param int $score Score 0-100
     * @return string Grade
     */
    public static function get_grade($score) {
        if ($score >= 90) return 'A';
        if ($score >= 80) return 'B';
        if ($score >= 70) return 'C';
        if ($score >= 60) return 'D';
        return 'F';
    }
    
    /**
     * Calculate weighted average
     * 
     * @param array $scores Associative array of scores
     * @param array $weights Associative array of weights
     * @return int Weighted average
     */
    public static function weighted_average($scores, $weights) {
        $total_score = 0;
        $total_weight = 0;
        
        foreach ($scores as $key => $score) {
            $weight = $weights[$key] ?? 1;
            $total_score += $score * $weight;
            $total_weight += $weight;
        }
        
        return $total_weight > 0 ? round($total_score / $total_weight) : 0;
    }
    
    /**
     * Calculate confidence score
     * 
     * @param array $scores Array of scores
     * @return int Confidence 0-100
     */
    public static function calculate_confidence($scores) {
        if (empty($scores)) {
            return 0;
        }
        
        // Calculate standard deviation
        $mean = array_sum($scores) / count($scores);
        $variance = 0;
        
        foreach ($scores as $score) {
            $variance += pow($score - $mean, 2);
        }
        
        $std_dev = sqrt($variance / count($scores));
        
        // Lower std dev = higher confidence
        // Normalize: 0 std dev = 100 confidence, 50 std dev = 0 confidence
        $confidence = max(0, 100 - ($std_dev * 2));
        
        return round($confidence);
    }
    
    /**
     * Get score color class
     * 
     * @param int $score Score 0-100
     * @return string CSS class
     */
    public static function get_color_class($score) {
        if ($score >= 80) return 'success';
        if ($score >= 60) return 'warning';
        if ($score >= 40) return 'info';
        return 'danger';
    }
    
    /**
     * Get score label
     * 
     * @param int $score Score 0-100
     * @return string Label
     */
    public static function get_label($score) {
        if ($score >= 90) return 'Mükemmel';
        if ($score >= 80) return 'Çok İyi';
        if ($score >= 70) return 'İyi';
        if ($score >= 60) return 'Orta';
        if ($score >= 40) return 'Zayıf';
        return 'Çok Zayıf';
    }
    
    /**
     * Calculate growth rate
     * 
     * @param float $old_value Old value
     * @param float $new_value New value
     * @return float Growth rate percentage
     */
    public static function calculate_growth_rate($old_value, $new_value) {
        if ($old_value == 0) {
            return $new_value > 0 ? 100 : 0;
        }
        
        return round((($new_value - $old_value) / $old_value) * 100, 2);
    }
    
    /**
     * Calculate trend (improving/stable/declining)
     * 
     * @param array $scores Historical scores (oldest first)
     * @return string Trend
     */
    public static function calculate_trend($scores) {
        if (count($scores) < 2) {
            return 'unknown';
        }
        
        $first = reset($scores);
        $last = end($scores);
        
        $diff = $last - $first;
        
        if ($diff > 10) {
            return 'improving';
        } elseif ($diff < -10) {
            return 'declining';
        } else {
            return 'stable';
        }
    }
    
    /**
     * Combine multiple scores with different strategies
     * 
     * @param array $scores Array of scores
     * @param string $strategy 'average', 'max', 'min', 'median'
     * @return int Combined score
     */
    public static function combine($scores, $strategy = 'average') {
        if (empty($scores)) {
            return 0;
        }
        
        switch ($strategy) {
            case 'max':
                return max($scores);
                
            case 'min':
                return min($scores);
                
            case 'median':
                sort($scores);
                $count = count($scores);
                $middle = floor($count / 2);
                
                if ($count % 2 == 0) {
                    return round(($scores[$middle - 1] + $scores[$middle]) / 2);
                } else {
                    return $scores[$middle];
                }
                
            case 'average':
            default:
                return round(array_sum($scores) / count($scores));
        }
    }
}
