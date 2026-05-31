<?php
/**
 * Business Priority Engine
 * 
 * Calculates business value separate from SEO score
 * Phase 4 - Business Intelligence
 * 
 * @package DODO_AI_SEO
 * @since 3.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Business_Priority_Engine {
    
    /**
     * Calculate business priority score (0-100)
     * Different from SEO score - focuses on business impact
     * 
     * @param array $opportunity Opportunity data
     * @return array Business priority analysis
     */
    public function calculate_business_priority($opportunity) {
        $score = 0;
        $factors = array();
        
        // 1. Revenue Potential (0-30 points)
        $revenue_score = $this->calculate_revenue_factor($opportunity);
        $score += $revenue_score;
        if ($revenue_score > 0) {
            $factors[] = array(
                'factor' => 'Revenue Potential',
                'score' => $revenue_score,
                'weight' => 30,
            );
        }
        
        // 2. Conversion Probability (0-25 points)
        $conversion_score = $this->calculate_conversion_factor($opportunity);
        $score += $conversion_score;
        if ($conversion_score > 0) {
            $factors[] = array(
                'factor' => 'Conversion Probability',
                'score' => $conversion_score,
                'weight' => 25,
            );
        }
        
        // 3. Traffic Potential (0-20 points)
        $traffic_score = $this->calculate_traffic_factor($opportunity);
        $score += $traffic_score;
        if ($traffic_score > 0) {
            $factors[] = array(
                'factor' => 'Traffic Potential',
                'score' => $traffic_score,
                'weight' => 20,
            );
        }
        
        // 4. Effort vs Impact (0-15 points)
        $effort_score = $this->calculate_effort_factor($opportunity);
        $score += $effort_score;
        if ($effort_score > 0) {
            $factors[] = array(
                'factor' => 'Effort vs Impact',
                'score' => $effort_score,
                'weight' => 15,
            );
        }
        
        // 5. Strategic Value (0-10 points)
        $strategic_score = $this->calculate_strategic_factor($opportunity);
        $score += $strategic_score;
        if ($strategic_score > 0) {
            $factors[] = array(
                'factor' => 'Strategic Value',
                'score' => $strategic_score,
                'weight' => 10,
            );
        }
        
        // Normalize to 0-100
        $score = min(100, $score);
        
        // Determine priority level
        $priority_level = 'low';
        if ($score >= 75) {
            $priority_level = 'critical';
        } elseif ($score >= 60) {
            $priority_level = 'high';
        } elseif ($score >= 40) {
            $priority_level = 'medium';
        }
        
        // Calculate ROI estimate
        $roi_estimate = $this->estimate_roi($opportunity, $score);
        
        return array(
            'business_priority_score' => $score,
            'priority_level' => $priority_level,
            'factors' => $factors,
            'roi_estimate' => $roi_estimate,
            'recommended_action' => $this->get_priority_action($priority_level),
        );
    }
    
    /**
     * Calculate revenue factor (0-30)
     */
    private function calculate_revenue_factor($opp) {
        $score = 0;
        
        // Check revenue score from Revenue Engine
        $revenue_score = $opp['revenue_score'] ?? 0;
        
        if ($revenue_score >= 70) {
            $score = 30; // High revenue keyword
        } elseif ($revenue_score >= 40) {
            $score = 20; // Medium revenue
        } elseif ($revenue_score >= 20) {
            $score = 10; // Low revenue
        }
        
        // Boost for money keywords
        if (!empty($opp['money_keyword'])) {
            $score += 5;
        }
        
        // Boost for B2B
        if (!empty($opp['b2b_potential']) && $opp['b2b_potential'] === 'high') {
            $score += 5;
        }
        
        return min(30, $score);
    }
    
    /**
     * Calculate conversion factor (0-25)
     */
    private function calculate_conversion_factor($opp) {
        $score = 0;
        
        // Check conversion probability
        $conversion_prob = $opp['conversion_probability'] ?? 0;
        
        if ($conversion_prob >= 70) {
            $score = 25;
        } elseif ($conversion_prob >= 50) {
            $score = 18;
        } elseif ($conversion_prob >= 30) {
            $score = 12;
        } elseif ($conversion_prob >= 10) {
            $score = 6;
        }
        
        // Boost for buyer intent
        $buyer_intent = $opp['buyer_intent'] ?? 'low';
        if ($buyer_intent === 'high') {
            $score += 5;
        }
        
        return min(25, $score);
    }
    
    /**
     * Calculate traffic factor (0-20)
     */
    private function calculate_traffic_factor($opp) {
        $score = 0;
        
        $impressions = $opp['impressions'] ?? 0;
        $position = $opp['position'] ?? $opp['current_position'] ?? 999;
        
        // High impressions = high traffic potential
        if ($impressions >= 1000) {
            $score += 10;
        } elseif ($impressions >= 500) {
            $score += 7;
        } elseif ($impressions >= 100) {
            $score += 4;
        }
        
        // Good position = easier to capture traffic
        if ($position <= 5) {
            $score += 10;
        } elseif ($position <= 10) {
            $score += 7;
        } elseif ($position <= 20) {
            $score += 4;
        }
        
        return min(20, $score);
    }
    
    /**
     * Calculate effort factor (0-15)
     */
    private function calculate_effort_factor($opp) {
        $score = 0;
        
        $effort = $opp['effort'] ?? 'medium';
        $impact = $opp['impact'] ?? $opp['estimated_impact'] ?? 'medium';
        
        // Low effort + high impact = best ROI
        if ($effort === 'low' && $impact === 'high') {
            $score = 15; // Quick win!
        } elseif ($effort === 'low' && $impact === 'medium') {
            $score = 12;
        } elseif ($effort === 'medium' && $impact === 'high') {
            $score = 10;
        } elseif ($effort === 'low' && $impact === 'low') {
            $score = 8;
        } elseif ($effort === 'medium' && $impact === 'medium') {
            $score = 6;
        } elseif ($effort === 'high' && $impact === 'high') {
            $score = 5;
        } else {
            $score = 2; // High effort, low impact
        }
        
        return $score;
    }
    
    /**
     * Calculate strategic factor (0-10)
     */
    private function calculate_strategic_factor($opp) {
        $score = 0;
        
        // Topical authority building
        $topical_strength = $opp['topical_strength'] ?? 0;
        if ($topical_strength < 30) {
            $score += 5; // Building new authority area
        }
        
        // Cannibalization fix = strategic
        $source = $opp['source'] ?? '';
        if ($source === 'gsc_cannibalization') {
            $score += 5; // Fixing internal competition
        }
        
        // Decay recovery = urgent strategic
        if ($source === 'gsc_decay') {
            $score += 3; // Protecting existing assets
        }
        
        return min(10, $score);
    }
    
    /**
     * Estimate ROI
     */
    private function estimate_roi($opp, $business_score) {
        $impressions = $opp['impressions'] ?? 0;
        $conversion_prob = $opp['conversion_probability'] ?? 0;
        $effort = $opp['effort'] ?? 'medium';
        
        // Estimate potential conversions
        $estimated_ctr = 0.05; // 5% CTR target
        $potential_clicks = $impressions * $estimated_ctr;
        $potential_conversions = $potential_clicks * ($conversion_prob / 100);
        
        // Effort cost (arbitrary units)
        $effort_cost = array(
            'low' => 1,
            'medium' => 3,
            'high' => 5,
        );
        
        $cost = $effort_cost[$effort] ?? 3;
        
        // ROI = (Conversions / Cost) * Business Score
        $roi = $cost > 0 ? ($potential_conversions / $cost) * ($business_score / 100) : 0;
        
        if ($roi >= 5) {
            return 'excellent';
        } elseif ($roi >= 2) {
            return 'good';
        } elseif ($roi >= 1) {
            return 'fair';
        } else {
            return 'poor';
        }
    }
    
    /**
     * Get priority action
     */
    private function get_priority_action($priority_level) {
        $actions = array(
            'critical' => 'HEMEN BAŞLA - Yüksek business value, acil aksiyon gerekli',
            'high' => 'ÖNCELİKLİ - Bu hafta içinde ele al',
            'medium' => 'PLANLAMA - Önümüzdeki 2 hafta içinde',
            'low' => 'BACKLOG - Zaman kalırsa değerlendir',
        );
        
        return $actions[$priority_level] ?? 'Değerlendir';
    }
    
    /**
     * Rank opportunities by business priority
     */
    public function rank_by_business_priority($opportunities) {
        foreach ($opportunities as &$opp) {
            $priority_analysis = $this->calculate_business_priority($opp);
            
            $opp['business_priority_score'] = $priority_analysis['business_priority_score'];
            $opp['priority_level'] = $priority_analysis['priority_level'];
            $opp['roi_estimate'] = $priority_analysis['roi_estimate'];
            $opp['priority_factors'] = $priority_analysis['factors'];
            $opp['priority_action'] = $priority_analysis['recommended_action'];
        }
        
        // Sort by business priority (not SEO score!)
        usort($opportunities, function($a, $b) {
            $a_score = $a['business_priority_score'] ?? 0;
            $b_score = $b['business_priority_score'] ?? 0;
            return $b_score <=> $a_score;
        });
        
        return $opportunities;
    }
    
    /**
     * Get quick wins (low effort + high business value)
     */
    public function get_quick_wins($opportunities) {
        $quick_wins = array();
        
        foreach ($opportunities as $opp) {
            $effort = $opp['effort'] ?? 'medium';
            $business_score = $opp['business_priority_score'] ?? 0;
            
            // Low effort + high business value = quick win
            if ($effort === 'low' && $business_score >= 60) {
                $quick_wins[] = $opp;
            }
        }
        
        // Sort by business score
        usort($quick_wins, function($a, $b) {
            return ($b['business_priority_score'] ?? 0) <=> ($a['business_priority_score'] ?? 0);
        });
        
        return array_slice($quick_wins, 0, 10);
    }
    
    /**
     * Get strategic priorities (building authority)
     */
    public function get_strategic_priorities($opportunities) {
        $strategic = array();
        
        foreach ($opportunities as $opp) {
            $topical_strength = $opp['topical_strength'] ?? 100;
            $business_score = $opp['business_priority_score'] ?? 0;
            
            // Low topical strength + decent business value = strategic
            if ($topical_strength < 40 && $business_score >= 40) {
                $strategic[] = $opp;
            }
        }
        
        // Sort by business score
        usort($strategic, function($a, $b) {
            return ($b['business_priority_score'] ?? 0) <=> ($a['business_priority_score'] ?? 0);
        });
        
        return array_slice($strategic, 0, 10);
    }
    
    /**
     * Get revenue priorities (money keywords)
     */
    public function get_revenue_priorities($opportunities) {
        $revenue = array();
        
        foreach ($opportunities as $opp) {
            $revenue_score = $opp['revenue_score'] ?? 0;
            
            if ($revenue_score >= 60) {
                $revenue[] = $opp;
            }
        }
        
        // Sort by revenue score
        usort($revenue, function($a, $b) {
            return ($b['revenue_score'] ?? 0) <=> ($a['revenue_score'] ?? 0);
        });
        
        return array_slice($revenue, 0, 10);
    }
}
