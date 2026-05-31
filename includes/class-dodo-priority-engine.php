<?php
/**
 * Smart Improvement Priority Engine Class
 * 
 * Prioritizes recommendations based on multiple factors
 * SEO impact, UX impact, effort, urgency
 *
 * @package DODO_AI_SEO
 * @since 1.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Priority_Engine {
    
    /**
     * Prioritize recommendations
     * 
     * @param array $recommendations Recommendations list
     * @param array $intelligence Intelligence data
     * @return array Prioritized recommendations
     */
    public function prioritize_recommendations($recommendations, $intelligence) {
        if (empty($recommendations)) {
            return [];
        }
        
        // Calculate priority score for each recommendation
        foreach ($recommendations as &$rec) {
            $rec['priority_score'] = $this->calculate_priority_score($rec, $intelligence);
            $rec['priority_factors'] = $this->get_priority_factors($rec, $intelligence);
        }
        
        // Sort by priority score (descending)
        usort($recommendations, function($a, $b) {
            return $b['priority_score'] - $a['priority_score'];
        });
        
        return $recommendations;
    }
    
    /**
     * Calculate priority score
     */
    private function calculate_priority_score($recommendation, $intelligence) {
        $score = 0;
        
        // SEO Impact (0-30 points)
        $seo_impact = $this->calculate_seo_impact($recommendation, $intelligence);
        $score += $seo_impact * 30;
        
        // UX Impact (0-25 points)
        $ux_impact = $this->calculate_ux_impact($recommendation, $intelligence);
        $score += $ux_impact * 25;
        
        // Readability Impact (0-20 points)
        $readability_impact = $this->calculate_readability_impact($recommendation, $intelligence);
        $score += $readability_impact * 20;
        
        // AI Risk Reduction (0-15 points)
        $ai_risk_reduction = $this->calculate_ai_risk_reduction($recommendation, $intelligence);
        $score += $ai_risk_reduction * 15;
        
        // Effort (inverse - easier = higher priority) (0-10 points)
        $effort_score = $this->calculate_effort_score($recommendation);
        $score += $effort_score * 10;
        
        return round($score);
    }
    
    /**
     * Calculate SEO impact
     */
    private function calculate_seo_impact($rec, $intelligence) {
        // SEO category recommendations have higher SEO impact
        if ($rec['category'] === 'seo') {
            // If SEO score is already low, SEO improvements are critical
            if ($intelligence['seo_score'] < 60) {
                return 1.0; // Maximum impact
            } elseif ($intelligence['seo_score'] < 75) {
                return 0.8;
            } else {
                return 0.5;
            }
        }
        
        // Content improvements also affect SEO
        if ($rec['category'] === 'content') {
            return 0.4;
        }
        
        return 0.2;
    }
    
    /**
     * Calculate UX impact
     */
    private function calculate_ux_impact($rec, $intelligence) {
        // Readability and content quality affect UX
        if ($rec['category'] === 'readability') {
            if ($intelligence['readability_score'] < 60) {
                return 1.0;
            } elseif ($intelligence['readability_score'] < 75) {
                return 0.7;
            } else {
                return 0.4;
            }
        }
        
        if ($rec['category'] === 'content') {
            if ($intelligence['quality_score'] < 60) {
                return 0.9;
            } elseif ($intelligence['quality_score'] < 75) {
                return 0.6;
            } else {
                return 0.3;
            }
        }
        
        return 0.2;
    }
    
    /**
     * Calculate readability impact
     */
    private function calculate_readability_impact($rec, $intelligence) {
        if ($rec['category'] === 'readability') {
            return 1.0;
        }
        
        // AI risk improvements can also improve readability
        if ($rec['category'] === 'ai-risk') {
            return 0.5;
        }
        
        return 0.1;
    }
    
    /**
     * Calculate AI risk reduction
     */
    private function calculate_ai_risk_reduction($rec, $intelligence) {
        if ($rec['category'] === 'ai-risk') {
            if (isset($intelligence['ai_detection']) && $intelligence['ai_detection']['risk_level'] === 'high') {
                return 1.0;
            } elseif (isset($intelligence['ai_detection']) && $intelligence['ai_detection']['risk_level'] === 'medium') {
                return 0.7;
            } else {
                return 0.3;
            }
        }
        
        return 0.0;
    }
    
    /**
     * Calculate effort score (inverse)
     */
    private function calculate_effort_score($rec) {
        $effort_scores = [
            'low' => 1.0,
            'medium' => 0.6,
            'high' => 0.3,
        ];
        
        return $effort_scores[$rec['effort']] ?? 0.5;
    }
    
    /**
     * Get priority factors (for explanation)
     */
    private function get_priority_factors($rec, $intelligence) {
        $factors = [];
        
        // Critical type
        if ($rec['type'] === 'critical') {
            $factors[] = __('Kritik öncelik', 'dodo-ai-seo');
        }
        
        // Low score in related category
        if ($rec['category'] === 'seo' && $intelligence['seo_score'] < 60) {
            $factors[] = __('SEO skoru düşük', 'dodo-ai-seo');
        }
        
        if ($rec['category'] === 'readability' && $intelligence['readability_score'] < 60) {
            $factors[] = __('Okunabilirlik düşük', 'dodo-ai-seo');
        }
        
        if ($rec['category'] === 'content' && $intelligence['quality_score'] < 60) {
            $factors[] = __('Kalite skoru düşük', 'dodo-ai-seo');
        }
        
        // High impact
        if ($rec['impact'] >= 8) {
            $factors[] = __('Yüksek etki', 'dodo-ai-seo');
        }
        
        // Low effort
        if ($rec['effort'] === 'low') {
            $factors[] = __('Kolay uygulama', 'dodo-ai-seo');
        }
        
        return $factors;
    }
}
