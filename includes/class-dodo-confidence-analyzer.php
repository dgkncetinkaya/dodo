<?php
/**
 * Confidence Analyzer Class
 * 
 * Analyzes AI confidence in content analysis and recommendations
 * Assesses risk levels and identifies risk factors
 *
 * @package DODO_AI_SEO
 * @since 1.2.0
 */

// Doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

class DODO_Confidence_Analyzer {
    
    /**
     * Confidence factor weights
     */
    const WEIGHTS = [
        'content_clarity'       => 0.30,  // 30%
        'analysis_certainty'    => 0.25,  // 25%
        'improvement_feasibility' => 0.25,  // 25%
        'context_completeness'  => 0.20   // 20%
    ];
    
    /**
     * Constructor
     */
    public function __construct() {
        // No initialization needed
    }
    
    /**
     * Calculate overall confidence score
     * 
     * @param array $intelligence Intelligence data with scores
     * @param string $content Post content
     * @param array $metadata Post metadata
     * @return int Confidence score (0-100)
     */
    public function calculate_confidence($intelligence, $content, $metadata) {
        $confidence = 0;
        
        // Content clarity (30 points)
        $clarity_score = $this->calculate_content_clarity($content, $metadata);
        $confidence += $clarity_score * self::WEIGHTS['content_clarity'];
        
        // Analysis certainty (25 points)
        $certainty_score = $this->calculate_analysis_certainty($intelligence);
        $confidence += $certainty_score * self::WEIGHTS['analysis_certainty'];
        
        // Improvement feasibility (25 points)
        $feasibility_score = $this->calculate_improvement_feasibility($intelligence, $content);
        $confidence += $feasibility_score * self::WEIGHTS['improvement_feasibility'];
        
        // Context completeness (20 points)
        $completeness_score = $this->calculate_context_completeness($content, $metadata);
        $confidence += $completeness_score * self::WEIGHTS['context_completeness'];
        
        return round($confidence);
    }
    
    /**
     * Assess risk level based on confidence and intelligence
     * 
     * @param int $confidence Confidence score (0-100)
     * @param array $intelligence Intelligence data
     * @return string Risk level (low|medium|high)
     */
    public function assess_risk_level($confidence, $intelligence) {
        // High confidence + no critical issues = Low risk
        if ($confidence >= 75 && !$this->has_critical_issues($intelligence)) {
            return 'low';
        }
        
        // Low confidence OR critical issues = High risk
        if ($confidence < 50 || $this->has_critical_issues($intelligence)) {
            return 'high';
        }
        
        // Everything else = Medium risk
        return 'medium';
    }
    
    /**
     * Identify risk factors
     * 
     * @param string $content Post content
     * @param array $intelligence Intelligence data
     * @return array Array of risk factors
     */
    public function identify_risk_factors($content, $intelligence) {
        $risk_factors = [];
        
        // Insufficient content
        $word_count = str_word_count(strip_tags($content));
        if ($word_count < 300) {
            $risk_factors[] = [
                'type' => 'insufficient_content',
                'severity' => 'high',
                'message' => __('İçerik çok kısa (< 300 kelime)', 'dodo-ai-seo'),
                'impact' => __('Analiz güvenilirliği düşük', 'dodo-ai-seo')
            ];
        }
        
        // High AI risk
        if (isset($intelligence['ai_risk_score']) && $intelligence['ai_risk_score'] > 60) {
            $risk_factors[] = [
                'type' => 'high_ai_risk',
                'severity' => 'high',
                'message' => __('Yüksek AI tespit riski', 'dodo-ai-seo'),
                'impact' => __('İçerik AI tarafından üretilmiş görünebilir', 'dodo-ai-seo')
            ];
        }
        
        // Low semantic quality
        if (isset($intelligence['semantic_score']) && $intelligence['semantic_score'] < 50) {
            $risk_factors[] = [
                'type' => 'low_semantic_quality',
                'severity' => 'medium',
                'message' => __('Düşük semantik kalite', 'dodo-ai-seo'),
                'impact' => __('İçerik anlamsal tutarlılık eksikliği', 'dodo-ai-seo')
            ];
        }
        
        // Poor readability
        if (isset($intelligence['readability_score']) && $intelligence['readability_score'] < 60) {
            $risk_factors[] = [
                'type' => 'poor_readability',
                'severity' => 'medium',
                'message' => __('Zayıf okunabilirlik', 'dodo-ai-seo'),
                'impact' => __('Kullanıcı deneyimi olumsuz etkilenebilir', 'dodo-ai-seo')
            ];
        }
        
        // Low SEO score
        if (isset($intelligence['seo_score']) && $intelligence['seo_score'] < 50) {
            $risk_factors[] = [
                'type' => 'low_seo',
                'severity' => 'medium',
                'message' => __('Düşük SEO skoru', 'dodo-ai-seo'),
                'impact' => __('Arama motorlarında düşük sıralama riski', 'dodo-ai-seo')
            ];
        }
        
        // No clear structure
        $h2_count = substr_count($content, '<h2');
        if ($h2_count < 2) {
            $risk_factors[] = [
                'type' => 'unclear_structure',
                'severity' => 'low',
                'message' => __('Belirsiz içerik yapısı', 'dodo-ai-seo'),
                'impact' => __('İyileştirme önerileri daha az kesin olabilir', 'dodo-ai-seo')
            ];
        }
        
        return $risk_factors;
    }
    
    /**
     * Explain confidence score
     * 
     * @param int $confidence Confidence score
     * @param array $factors Confidence factors
     * @return array Explanation data
     */
    public function explain_confidence($confidence, $factors) {
        // Determine confidence level
        if ($confidence >= 90) {
            $level = 'very_high';
            $label = __('Çok Yüksek', 'dodo-ai-seo');
            $description = __('AI analizine tam güvenebilirsiniz', 'dodo-ai-seo');
        } elseif ($confidence >= 75) {
            $level = 'high';
            $label = __('Yüksek', 'dodo-ai-seo');
            $description = __('AI önerileri güvenilir', 'dodo-ai-seo');
        } elseif ($confidence >= 60) {
            $level = 'medium';
            $label = __('Orta', 'dodo-ai-seo');
            $description = __('AI önerilerini gözden geçirin', 'dodo-ai-seo');
        } elseif ($confidence >= 40) {
            $level = 'low';
            $label = __('Düşük', 'dodo-ai-seo');
            $description = __('Manuel inceleme gerekli', 'dodo-ai-seo');
        } else {
            $level = 'very_low';
            $label = __('Çok Düşük', 'dodo-ai-seo');
            $description = __('AI belirsiz, dikkatli ilerleyin', 'dodo-ai-seo');
        }
        
        return [
            'confidence' => $confidence,
            'level' => $level,
            'label' => $label,
            'description' => $description,
            'factors' => $factors
        ];
    }
    
    // ==========================================
    // PRIVATE CALCULATION METHODS
    // ==========================================
    
    /**
     * Calculate content clarity score
     * 
     * @param string $content Post content
     * @param array $metadata Post metadata
     * @return int Clarity score (0-100)
     */
    private function calculate_content_clarity($content, $metadata) {
        $score = 0;
        
        // Focus keyword defined (10 points)
        if (!empty($metadata['focus_keyword'])) {
            $score += 10;
        }
        
        // Clear structure (10 points)
        if ($this->has_clear_structure($content)) {
            $score += 10;
        }
        
        // Clear sections (10 points)
        if ($this->has_clear_sections($content)) {
            $score += 10;
        }
        
        return $score;
    }
    
    /**
     * Calculate analysis certainty score
     * 
     * @param array $intelligence Intelligence data
     * @return int Certainty score (0-100)
     */
    private function calculate_analysis_certainty($intelligence) {
        $score = 0;
        
        // Clear SEO state (10 points)
        if (isset($intelligence['seo_score'])) {
            if ($intelligence['seo_score'] > 70 || $intelligence['seo_score'] < 30) {
                $score += 10; // Clear state (very good or very bad)
            }
        }
        
        // Clear improvement opportunities (10 points)
        if ($this->has_clear_improvement_opportunities($intelligence)) {
            $score += 10;
        }
        
        // Strong patterns detected (5 points)
        if ($this->has_strong_patterns($intelligence)) {
            $score += 5;
        }
        
        return $score;
    }
    
    /**
     * Calculate improvement feasibility score
     * 
     * @param array $intelligence Intelligence data
     * @param string $content Post content
     * @return int Feasibility score (0-100)
     */
    private function calculate_improvement_feasibility($intelligence, $content) {
        $score = 0;
        
        // Clear improvement path (10 points)
        if ($this->has_clear_improvement_path($intelligence)) {
            $score += 10;
        }
        
        // Low semantic risk (10 points)
        if ($this->has_low_semantic_risk($content)) {
            $score += 10;
        }
        
        // Measurable impact (5 points)
        if ($this->has_measurable_impact($intelligence)) {
            $score += 5;
        }
        
        return $score;
    }
    
    /**
     * Calculate context completeness score
     * 
     * @param string $content Post content
     * @param array $metadata Post metadata
     * @return int Completeness score (0-100)
     */
    private function calculate_context_completeness($content, $metadata) {
        $score = 0;
        
        // Sufficient word count (10 points)
        $word_count = str_word_count(strip_tags($content));
        if ($word_count >= 500) {
            $score += 10;
        }
        
        // Complete sections (5 points)
        if ($this->has_complete_sections($content)) {
            $score += 5;
        }
        
        // Complete metadata (5 points)
        if (!empty($metadata['seo_title']) && !empty($metadata['meta_description'])) {
            $score += 5;
        }
        
        return $score;
    }
    
    // ==========================================
    // HELPER METHODS
    // ==========================================
    
    /**
     * Check if content has critical issues
     * 
     * @param array $intelligence Intelligence data
     * @return bool True if critical issues found
     */
    private function has_critical_issues($intelligence) {
        // Very low health score
        if (isset($intelligence['health_score']) && $intelligence['health_score'] < 40) {
            return true;
        }
        
        // Very high AI risk
        if (isset($intelligence['ai_risk_score']) && $intelligence['ai_risk_score'] > 70) {
            return true;
        }
        
        // Very low SEO
        if (isset($intelligence['seo_score']) && $intelligence['seo_score'] < 30) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if content has clear structure
     * 
     * @param string $content Post content
     * @return bool True if clear structure
     */
    private function has_clear_structure($content) {
        // Check for headings
        $h2_count = substr_count($content, '<h2');
        $h3_count = substr_count($content, '<h3');
        
        // Need at least 2 H2 headings
        return ($h2_count >= 2);
    }
    
    /**
     * Check if content has clear sections
     * 
     * @param string $content Post content
     * @return bool True if clear sections
     */
    private function has_clear_sections($content) {
        // Check for paragraphs
        $p_count = substr_count($content, '<p');
        
        // Need at least 5 paragraphs
        return ($p_count >= 5);
    }
    
    /**
     * Check if has clear improvement opportunities
     * 
     * @param array $intelligence Intelligence data
     * @return bool True if clear opportunities
     */
    private function has_clear_improvement_opportunities($intelligence) {
        // Check if any score is in improvable range (50-80)
        $scores = [
            $intelligence['seo_score'] ?? 0,
            $intelligence['quality_score'] ?? 0,
            $intelligence['readability_score'] ?? 0,
            $intelligence['semantic_score'] ?? 0
        ];
        
        foreach ($scores as $score) {
            if ($score >= 50 && $score <= 80) {
                return true; // Clear room for improvement
            }
        }
        
        return false;
    }
    
    /**
     * Check if has strong patterns
     * 
     * @param array $intelligence Intelligence data
     * @return bool True if strong patterns
     */
    private function has_strong_patterns($intelligence) {
        // Check if scores are consistent (not all over the place)
        $scores = [
            $intelligence['seo_score'] ?? 0,
            $intelligence['quality_score'] ?? 0,
            $intelligence['readability_score'] ?? 0,
            $intelligence['semantic_score'] ?? 0
        ];
        
        $avg = array_sum($scores) / count($scores);
        $variance = 0;
        
        foreach ($scores as $score) {
            $variance += pow($score - $avg, 2);
        }
        $variance /= count($scores);
        
        // Low variance = consistent scores = strong patterns
        return ($variance < 400); // Standard deviation < 20
    }
    
    /**
     * Check if has clear improvement path
     * 
     * @param array $intelligence Intelligence data
     * @return bool True if clear path
     */
    private function has_clear_improvement_path($intelligence) {
        // If health score is not too low and not perfect
        $health = $intelligence['health_score'] ?? 0;
        return ($health >= 40 && $health < 90);
    }
    
    /**
     * Check if has low semantic risk
     * 
     * @param string $content Post content
     * @return bool True if low risk
     */
    private function has_low_semantic_risk($content) {
        // Check content length (longer = safer to modify)
        $word_count = str_word_count(strip_tags($content));
        return ($word_count >= 800);
    }
    
    /**
     * Check if has measurable impact
     * 
     * @param array $intelligence Intelligence data
     * @return bool True if measurable
     */
    private function has_measurable_impact($intelligence) {
        // If any score is significantly below 100
        $scores = [
            $intelligence['seo_score'] ?? 100,
            $intelligence['quality_score'] ?? 100,
            $intelligence['readability_score'] ?? 100,
            $intelligence['semantic_score'] ?? 100
        ];
        
        foreach ($scores as $score) {
            if ($score < 85) {
                return true; // Room for measurable improvement
            }
        }
        
        return false;
    }
    
    /**
     * Check if has complete sections
     * 
     * @param string $content Post content
     * @return bool True if complete
     */
    private function has_complete_sections($content) {
        // Check for introduction, body, conclusion indicators
        $has_intro = (strpos($content, '<p') !== false);
        $has_body = (substr_count($content, '<h2') >= 2);
        $has_conclusion = (substr_count($content, '<p') >= 5);
        
        return ($has_intro && $has_body && $has_conclusion);
    }
    
    /**
     * Get confidence level label
     * 
     * @param int $confidence Confidence score (0-100)
     * @return string Confidence level
     */
    public function get_confidence_level($confidence) {
        if ($confidence >= 90) {
            return 'very_high';
        } elseif ($confidence >= 75) {
            return 'high';
        } elseif ($confidence >= 60) {
            return 'medium';
        } elseif ($confidence >= 40) {
            return 'low';
        } else {
            return 'very_low';
        }
    }
    
    /**
     * Get confidence level label (localized)
     * 
     * @param int $confidence Confidence score (0-100)
     * @return string Localized label
     */
    public function get_confidence_label($confidence) {
        $level = $this->get_confidence_level($confidence);
        
        $labels = [
            'very_high' => __('Çok Yüksek', 'dodo-ai-seo'),
            'high'      => __('Yüksek', 'dodo-ai-seo'),
            'medium'    => __('Orta', 'dodo-ai-seo'),
            'low'       => __('Düşük', 'dodo-ai-seo'),
            'very_low'  => __('Çok Düşük', 'dodo-ai-seo')
        ];
        
        return $labels[$level];
    }
    
    /**
     * Get risk level label (localized)
     * 
     * @param string $risk_level Risk level (low|medium|high)
     * @return string Localized label
     */
    public function get_risk_label($risk_level) {
        $labels = [
            'low'    => __('Düşük Risk', 'dodo-ai-seo'),
            'medium' => __('Orta Risk', 'dodo-ai-seo'),
            'high'   => __('Yüksek Risk', 'dodo-ai-seo')
        ];
        
        return $labels[$risk_level] ?? $risk_level;
    }
}
