<?php
/**
 * Advanced Risk Engine Class
 * 
 * Comprehensive risk assessment system
 * Integrates with confidence and AI detection
 *
 * @package DODO_AI_SEO
 * @since 1.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Risk_Engine {
    
    /**
     * Risk severity levels
     */
    const SEVERITY_LOW = 'low';
    const SEVERITY_MEDIUM = 'medium';
    const SEVERITY_HIGH = 'high';
    const SEVERITY_CRITICAL = 'critical';
    
    /**
     * Assess all risks
     * 
     * @param array $intelligence Full intelligence data
     * @return array Risk assessment
     */
    public function assess_risks($intelligence) {
        $risks = [];
        
        // Semantic drift risk
        if (isset($intelligence['semantic_analysis'])) {
            $semantic_risks = $this->assess_semantic_risks($intelligence['semantic_analysis']);
            $risks = array_merge($risks, $semantic_risks);
        }
        
        // Tone inconsistency risk
        if (isset($intelligence['style_analysis'])) {
            $tone_risks = $this->assess_tone_risks($intelligence['style_analysis']);
            $risks = array_merge($risks, $tone_risks);
        }
        
        // AI repetition risk
        if (isset($intelligence['ai_detection'])) {
            $ai_risks = $this->assess_ai_risks($intelligence['ai_detection']);
            $risks = array_merge($risks, $ai_risks);
        }
        
        // Readability collapse risk
        $readability_risks = $this->assess_readability_risks($intelligence);
        $risks = array_merge($risks, $readability_risks);
        
        // Over-optimization risk
        $optimization_risks = $this->assess_optimization_risks($intelligence);
        $risks = array_merge($risks, $optimization_risks);
        
        // Calculate overall risk score
        $overall_risk = $this->calculate_overall_risk($risks);
        
        return [
            'risks' => $risks,
            'overall_risk_score' => $overall_risk,
            'overall_severity' => $this->get_severity_from_score($overall_risk),
            'risk_count' => count($risks),
            'critical_count' => $this->count_by_severity($risks, self::SEVERITY_CRITICAL),
            'high_count' => $this->count_by_severity($risks, self::SEVERITY_HIGH),
        ];
    }
    
    /**
     * Assess semantic risks
     */
    private function assess_semantic_risks($semantic) {
        $risks = [];
        
        // Semantic drift
        if ($semantic['topic_drift_score'] > 50) {
            $risks[] = [
                'type' => 'semantic_drift',
                'severity' => $semantic['topic_drift_score'] > 70 ? self::SEVERITY_HIGH : self::SEVERITY_MEDIUM,
                'score' => $semantic['topic_drift_score'],
                'message' => __('İçerik odak noktasından sapıyor', 'dodo-ai-seo'),
                'icon' => '🎯',
            ];
        }
        
        // Keyword stuffing
        if ($semantic['keyword_stuffing_score'] > 50) {
            $risks[] = [
                'type' => 'keyword_stuffing',
                'severity' => $semantic['keyword_stuffing_score'] > 70 ? self::SEVERITY_CRITICAL : self::SEVERITY_HIGH,
                'score' => $semantic['keyword_stuffing_score'],
                'message' => __('Anahtar kelime yoğunluğu çok yüksek', 'dodo-ai-seo'),
                'icon' => '⚠️',
            ];
        }
        
        // Low coherence
        if ($semantic['coherence_score'] < 50) {
            $risks[] = [
                'type' => 'low_coherence',
                'severity' => $semantic['coherence_score'] < 30 ? self::SEVERITY_HIGH : self::SEVERITY_MEDIUM,
                'score' => 100 - $semantic['coherence_score'],
                'message' => __('Paragraflar arası bağlantı zayıf', 'dodo-ai-seo'),
                'icon' => '🔗',
            ];
        }
        
        return $risks;
    }
    
    /**
     * Assess tone risks
     */
    private function assess_tone_risks($style) {
        $risks = [];
        
        // Low tone confidence
        if ($style['tone_confidence'] < 50) {
            $risks[] = [
                'type' => 'tone_inconsistency',
                'severity' => $style['tone_confidence'] < 30 ? self::SEVERITY_HIGH : self::SEVERITY_MEDIUM,
                'score' => 100 - $style['tone_confidence'],
                'message' => __('Yazım tonu tutarsız', 'dodo-ai-seo'),
                'icon' => '🎭',
            ];
        }
        
        return $risks;
    }
    
    /**
     * Assess AI risks
     */
    private function assess_ai_risks($ai_detection) {
        $risks = [];
        
        // AI repetition
        if ($ai_detection['repetitive_structures'] > 50) {
            $risks[] = [
                'type' => 'ai_repetition',
                'severity' => $ai_detection['repetitive_structures'] > 70 ? self::SEVERITY_HIGH : self::SEVERITY_MEDIUM,
                'score' => $ai_detection['repetitive_structures'],
                'message' => __('Tekrarlayan cümle yapıları tespit edildi', 'dodo-ai-seo'),
                'icon' => '🔄',
            ];
        }
        
        // Robotic phrasing
        if ($ai_detection['robotic_flow'] > 50) {
            $risks[] = [
                'type' => 'robotic_phrasing',
                'severity' => $ai_detection['robotic_flow'] > 70 ? self::SEVERITY_HIGH : self::SEVERITY_MEDIUM,
                'score' => $ai_detection['robotic_flow'],
                'message' => __('Robotik yazım akışı', 'dodo-ai-seo'),
                'icon' => '🤖',
            ];
        }
        
        // Generic flow
        if ($ai_detection['generic_transitions'] > 50) {
            $risks[] = [
                'type' => 'generic_flow',
                'severity' => $ai_detection['generic_transitions'] > 70 ? self::SEVERITY_MEDIUM : self::SEVERITY_LOW,
                'score' => $ai_detection['generic_transitions'],
                'message' => __('Jenerik geçiş kalıpları kullanılıyor', 'dodo-ai-seo'),
                'icon' => '➡️',
            ];
        }
        
        return $risks;
    }
    
    /**
     * Assess readability risks
     */
    private function assess_readability_risks($intelligence) {
        $risks = [];
        
        // Readability collapse
        if ($intelligence['readability_score'] < 40) {
            $risks[] = [
                'type' => 'readability_collapse',
                'severity' => self::SEVERITY_CRITICAL,
                'score' => 100 - $intelligence['readability_score'],
                'message' => __('Okunabilirlik kritik seviyede düşük', 'dodo-ai-seo'),
                'icon' => '📖',
            ];
        } elseif ($intelligence['readability_score'] < 60) {
            $risks[] = [
                'type' => 'readability_collapse',
                'severity' => self::SEVERITY_HIGH,
                'score' => 100 - $intelligence['readability_score'],
                'message' => __('Okunabilirlik düşük', 'dodo-ai-seo'),
                'icon' => '📖',
            ];
        }
        
        return $risks;
    }
    
    /**
     * Assess optimization risks
     */
    private function assess_optimization_risks($intelligence) {
        $risks = [];
        
        // Over-optimization (too perfect scores = suspicious)
        $perfect_scores = 0;
        if ($intelligence['seo_score'] > 95) $perfect_scores++;
        if ($intelligence['quality_score'] > 95) $perfect_scores++;
        if ($intelligence['readability_score'] > 95) $perfect_scores++;
        
        if ($perfect_scores >= 2 && isset($intelligence['ai_detection']) && $intelligence['ai_detection']['risk_level'] !== 'low') {
            $risks[] = [
                'type' => 'over_optimization',
                'severity' => self::SEVERITY_MEDIUM,
                'score' => 50,
                'message' => __('Aşırı optimizasyon riski', 'dodo-ai-seo'),
                'icon' => '⚡',
            ];
        }
        
        return $risks;
    }
    
    /**
     * Calculate overall risk score
     */
    private function calculate_overall_risk($risks) {
        if (empty($risks)) {
            return 0;
        }
        
        $total_score = 0;
        $weight_sum = 0;
        
        foreach ($risks as $risk) {
            $weight = $this->get_severity_weight($risk['severity']);
            $total_score += $risk['score'] * $weight;
            $weight_sum += $weight;
        }
        
        return $weight_sum > 0 ? round($total_score / $weight_sum) : 0;
    }
    
    /**
     * Get severity weight
     */
    private function get_severity_weight($severity) {
        $weights = [
            self::SEVERITY_LOW => 1,
            self::SEVERITY_MEDIUM => 2,
            self::SEVERITY_HIGH => 3,
            self::SEVERITY_CRITICAL => 4,
        ];
        
        return $weights[$severity] ?? 1;
    }
    
    /**
     * Get severity from score
     */
    private function get_severity_from_score($score) {
        if ($score >= 80) {
            return self::SEVERITY_CRITICAL;
        } elseif ($score >= 60) {
            return self::SEVERITY_HIGH;
        } elseif ($score >= 40) {
            return self::SEVERITY_MEDIUM;
        } else {
            return self::SEVERITY_LOW;
        }
    }
    
    /**
     * Count risks by severity
     */
    private function count_by_severity($risks, $severity) {
        $count = 0;
        foreach ($risks as $risk) {
            if ($risk['severity'] === $severity) {
                $count++;
            }
        }
        return $count;
    }
}
