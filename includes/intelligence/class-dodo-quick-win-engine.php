<?php
/**
 * Quick Win Engine
 * 
 * Detects quick win opportunities (low effort, high impact)
 * Sprint D - Revenue & Topical Domination Engine
 * 
 * @package DODO_AI_SEO
 * @since 3.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Quick_Win_Engine {
    
    /**
     * Detect quick wins
     * 
     * @param array $opportunities
     * @return array Opportunities with quick_win flag
     */
    public function detect_quick_wins($opportunities) {
        foreach ($opportunities as &$opp) {
            $is_quick_win = false;
            $quick_win_reasons = array();
            
            // Criteria 1: Low CTR + High Impressions
            $impressions = $opp['impressions'] ?? 0;
            $ctr = $opp['ctr'] ?? 0;
            $position = $opp['position'] ?? 0;
            
            if ($impressions > 500 && $ctr < 3 && $position > 0 && $position <= 10) {
                $is_quick_win = true;
                $quick_win_reasons[] = 'Yüksek gösterim + düşük CTR = title/meta fix';
            }
            
            // Criteria 2: Position 4-12 (first page, improvable)
            if ($position >= 4 && $position <= 12) {
                $is_quick_win = true;
                $quick_win_reasons[] = 'İlk sayfada, küçük optimizasyon ile üst sıralara çıkabilir';
            }
            
            // Criteria 3: Low effort
            $effort = $opp['effort'] ?? 'medium';
            if ($effort === 'low') {
                $is_quick_win = true;
                $quick_win_reasons[] = 'Düşük efor gerekiyor';
            }
            
            // Criteria 4: High revenue potential
            $revenue_score = $opp['revenue_score'] ?? 0;
            if ($revenue_score >= 70 && $effort !== 'high') {
                $is_quick_win = true;
                $quick_win_reasons[] = 'Yüksek revenue potansiyeli';
            }
            
            // Criteria 5: Problem with clear solution
            $problem_severity = $opp['problem_severity'] ?? '';
            if (in_array($problem_severity, array('high', 'critical')) && !empty($opp['quick_wins'])) {
                $is_quick_win = true;
                $quick_win_reasons[] = 'Net çözümü olan problem';
            }
            
            $opp['is_quick_win'] = $is_quick_win;
            $opp['quick_win_reasons'] = $quick_win_reasons;
        }
        
        return $opportunities;
    }
    
    /**
     * Get quick win statistics
     */
    public function get_statistics($opportunities) {
        $stats = array(
            'total_quick_wins' => 0,
            'ctr_opportunities' => 0,
            'position_opportunities' => 0,
            'low_effort_high_revenue' => 0,
        );
        
        foreach ($opportunities as $opp) {
            if (!empty($opp['is_quick_win'])) {
                $stats['total_quick_wins']++;
                
                $reasons = $opp['quick_win_reasons'] ?? array();
                foreach ($reasons as $reason) {
                    if (stripos($reason, 'CTR') !== false) {
                        $stats['ctr_opportunities']++;
                    }
                    if (stripos($reason, 'sayfada') !== false) {
                        $stats['position_opportunities']++;
                    }
                    if (stripos($reason, 'revenue') !== false) {
                        $stats['low_effort_high_revenue']++;
                    }
                }
            }
        }
        
        return $stats;
    }
}
