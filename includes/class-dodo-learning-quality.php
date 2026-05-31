<?php
/**
 * Learning Quality Score
 * 
 * Measures learning system quality and stability
 * 
 * @package DODO_AI_SEO
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Learning_Quality {
    
    /**
     * Calculate learning quality score (0-100)
     */
    public function calculate_quality_score() {
        $metrics = $this->get_quality_metrics();
        
        $score = 0;
        
        // 1. Acceptance rate (30 points)
        $acceptance_rate = $metrics['acceptance_rate'];
        $score += min(30, ($acceptance_rate / 100) * 30);
        
        // 2. Low rollback frequency (25 points)
        $rollback_rate = $metrics['rollback_rate'];
        $rollback_score = max(0, 25 - ($rollback_rate * 2.5));
        $score += $rollback_score;
        
        // 3. Low false adaptation rate (20 points)
        $false_rate = $metrics['false_adaptation_rate'];
        $false_score = max(0, 20 - ($false_rate * 2));
        $score += $false_score;
        
        // 4. Strategy stability (15 points)
        $stability = $metrics['strategy_stability'];
        $score += ($stability / 100) * 15;
        
        // 5. Noisy learning filter effectiveness (10 points)
        $filter_effectiveness = $metrics['filter_effectiveness'];
        $score += ($filter_effectiveness / 100) * 10;
        
        $final_score = round($score);
        
        error_log("[DODO Quality] Learning quality score: {$final_score}/100");
        
        return [
            'score' => $final_score,
            'grade' => $this->get_grade($final_score),
            'metrics' => $metrics,
            'breakdown' => [
                'acceptance' => round(($acceptance_rate / 100) * 30, 1),
                'rollback' => round($rollback_score, 1),
                'false_adaptation' => round($false_score, 1),
                'stability' => round(($stability / 100) * 15, 1),
                'filter' => round(($filter_effectiveness / 100) * 10, 1),
            ],
        ];
    }
    
    /**
     * Get quality metrics
     */
    public function get_quality_metrics() {
        global $wpdb;
        
        $feedback_table = $wpdb->prefix . 'dodo_feedback_events';
        $learning_table = $wpdb->prefix . 'dodo_learning_events';
        
        // 1. Acceptance rate
        $total_recommendations = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$feedback_table}"
        );
        
        $accepted = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$feedback_table} WHERE user_action = 'accepted'"
        );
        
        $acceptance_rate = $total_recommendations > 0 
            ? round(($accepted / $total_recommendations) * 100, 1) 
            : 0;
        
        // 2. Rollback rate
        $total_adaptations = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$learning_table} WHERE event_type = 'impact_result'"
        );
        
        $rollbacks = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$learning_table} WHERE event_type = 'evolution_rollback'"
        );
        
        $rollback_rate = $total_adaptations > 0 
            ? round(($rollbacks / $total_adaptations) * 100, 1) 
            : 0;
        
        // 3. False adaptation rate (adaptations with negative outcomes)
        $negative_outcomes = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$learning_table} 
            WHERE event_type = 'impact_result' 
            AND outcome_score < 0"
        );
        
        $false_adaptation_rate = $total_adaptations > 0 
            ? round(($negative_outcomes / $total_adaptations) * 100, 1) 
            : 0;
        
        // 4. Strategy stability (low variance in recent adaptations)
        $recent_scores = $wpdb->get_col(
            "SELECT outcome_score FROM {$learning_table} 
            WHERE event_type = 'impact_result' 
            ORDER BY recorded_at DESC 
            LIMIT 20"
        );
        
        $stability = $this->calculate_stability($recent_scores);
        
        // 5. Filter effectiveness (% of noisy data filtered)
        $total_events = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$learning_table}"
        );
        
        $filtered_events = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$learning_table} 
            WHERE event_type = 'validation_blocked'"
        );
        
        $filter_effectiveness = $total_events > 0 
            ? round(($filtered_events / $total_events) * 100, 1) 
            : 0;
        
        return [
            'acceptance_rate' => $acceptance_rate,
            'rollback_rate' => $rollback_rate,
            'false_adaptation_rate' => $false_adaptation_rate,
            'strategy_stability' => $stability,
            'filter_effectiveness' => $filter_effectiveness,
            'total_recommendations' => $total_recommendations,
            'accepted_recommendations' => $accepted,
            'total_adaptations' => $total_adaptations,
            'rollbacks' => $rollbacks,
            'negative_outcomes' => $negative_outcomes,
        ];
    }
    
    /**
     * Calculate stability score
     */
    private function calculate_stability($scores) {
        if (count($scores) < 3) {
            return 100; // Not enough data, assume stable
        }
        
        $mean = array_sum($scores) / count($scores);
        $variance = 0;
        
        foreach ($scores as $score) {
            $variance += pow($score - $mean, 2);
        }
        
        $variance = $variance / count($scores);
        $std_dev = sqrt($variance);
        
        // Lower std dev = higher stability
        // Normalize to 0-100 scale (std dev > 50 = 0 stability)
        $stability = max(0, 100 - ($std_dev * 2));
        
        return round($stability, 1);
    }
    
    /**
     * Get grade
     */
    private function get_grade($score) {
        if ($score >= 90) return 'A';
        if ($score >= 80) return 'B';
        if ($score >= 70) return 'C';
        if ($score >= 60) return 'D';
        return 'F';
    }
    
    /**
     * Get quality insights
     */
    public function get_insights() {
        $quality = $this->calculate_quality_score();
        $score = $quality['score'];
        $metrics = $quality['metrics'];
        
        $insights = [];
        
        // Acceptance rate insights
        if ($metrics['acceptance_rate'] < 50) {
            $insights[] = [
                'type' => 'warning',
                'message' => 'Low acceptance rate - recommendations may not be relevant',
                'metric' => 'acceptance_rate',
                'value' => $metrics['acceptance_rate'],
            ];
        } elseif ($metrics['acceptance_rate'] > 80) {
            $insights[] = [
                'type' => 'success',
                'message' => 'High acceptance rate - recommendations are well-received',
                'metric' => 'acceptance_rate',
                'value' => $metrics['acceptance_rate'],
            ];
        }
        
        // Rollback rate insights
        if ($metrics['rollback_rate'] > 20) {
            $insights[] = [
                'type' => 'error',
                'message' => 'High rollback rate - learning may be unstable',
                'metric' => 'rollback_rate',
                'value' => $metrics['rollback_rate'],
            ];
        } elseif ($metrics['rollback_rate'] < 5) {
            $insights[] = [
                'type' => 'success',
                'message' => 'Low rollback rate - learning is stable',
                'metric' => 'rollback_rate',
                'value' => $metrics['rollback_rate'],
            ];
        }
        
        // False adaptation insights
        if ($metrics['false_adaptation_rate'] > 15) {
            $insights[] = [
                'type' => 'error',
                'message' => 'High false adaptation rate - increase confidence threshold',
                'metric' => 'false_adaptation_rate',
                'value' => $metrics['false_adaptation_rate'],
            ];
        }
        
        // Stability insights
        if ($metrics['strategy_stability'] < 60) {
            $insights[] = [
                'type' => 'warning',
                'message' => 'Low strategy stability - outcomes are inconsistent',
                'metric' => 'strategy_stability',
                'value' => $metrics['strategy_stability'],
            ];
        }
        
        // Overall grade insight
        if ($score >= 80) {
            $insights[] = [
                'type' => 'success',
                'message' => 'Learning system is performing well',
                'metric' => 'overall',
                'value' => $score,
            ];
        } elseif ($score < 60) {
            $insights[] = [
                'type' => 'error',
                'message' => 'Learning system needs attention - consider adjusting settings',
                'metric' => 'overall',
                'value' => $score,
            ];
        }
        
        return $insights;
    }
    
    /**
     * Get quality trend
     */
    public function get_quality_trend($days = 30) {
        global $wpdb;
        
        $learning_table = $wpdb->prefix . 'dodo_learning_events';
        
        $trend = [];
        
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            
            $day_score = $wpdb->get_var($wpdb->prepare(
                "SELECT AVG(outcome_score) FROM {$learning_table} 
                WHERE event_type = 'impact_result' 
                AND DATE(recorded_at) = %s",
                $date
            ));
            
            $trend[] = [
                'date' => $date,
                'score' => $day_score ? round($day_score, 1) : null,
            ];
        }
        
        return $trend;
    }
}
