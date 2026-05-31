<?php
/**
 * Content Quality Summarizer Class
 * 
 * Generates executive summary of content quality
 * Single-glance overview of strengths and weaknesses
 *
 * @package DODO_AI_SEO
 * @since 1.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Quality_Summarizer {
    
    /**
     * Generate quality summary
     * 
     * @param array $intelligence Full intelligence data
     * @return array Quality summary
     */
    public function generate_summary($intelligence) {
        $strengths = $this->identify_strengths($intelligence);
        $weaknesses = $this->identify_weaknesses($intelligence);
        $critical_warnings = $this->identify_critical_warnings($intelligence);
        $next_actions = $this->suggest_next_actions($intelligence);
        
        return [
            'strengths' => $strengths,
            'weaknesses' => $weaknesses,
            'critical_warnings' => $critical_warnings,
            'next_actions' => $next_actions,
            'overall_status' => $this->determine_overall_status($intelligence),
        ];
    }
    
    /**
     * Identify strengths
     */
    private function identify_strengths($intelligence) {
        $strengths = [];
        
        // SEO strength
        if ($intelligence['seo_score'] >= 75) {
            $strengths[] = [
                'icon' => '✅',
                'label' => __('SEO Optimizasyonu Güçlü', 'dodo-ai-seo'),
                'score' => $intelligence['seo_score'],
            ];
        }
        
        // Quality strength
        if ($intelligence['quality_score'] >= 75) {
            $strengths[] = [
                'icon' => '✅',
                'label' => __('İçerik Kalitesi Yüksek', 'dodo-ai-seo'),
                'score' => $intelligence['quality_score'],
            ];
        }
        
        // Readability strength
        if ($intelligence['readability_score'] >= 75) {
            $strengths[] = [
                'icon' => '✅',
                'label' => __('Okunabilirlik İyi', 'dodo-ai-seo'),
                'score' => $intelligence['readability_score'],
            ];
        }
        
        // Semantic strength
        if (isset($intelligence['semantic_analysis']) && $intelligence['semantic_analysis']['semantic_health'] >= 75) {
            $strengths[] = [
                'icon' => '✅',
                'label' => __('Semantik Yapı Güçlü', 'dodo-ai-seo'),
                'score' => $intelligence['semantic_analysis']['semantic_health'],
            ];
        }
        
        // Depth strength
        if (isset($intelligence['depth_analysis']) && $intelligence['depth_analysis']['depth_level'] === 'deep') {
            $strengths[] = [
                'icon' => '✅',
                'label' => __('Derin İçerik', 'dodo-ai-seo'),
                'score' => $intelligence['depth_analysis']['depth_score'],
            ];
        }
        
        // Low AI risk
        if (isset($intelligence['ai_detection']) && $intelligence['ai_detection']['risk_level'] === 'low') {
            $strengths[] = [
                'icon' => '✅',
                'label' => __('Doğal Yazım', 'dodo-ai-seo'),
                'score' => 100 - $intelligence['ai_detection']['ai_similarity_score'],
            ];
        }
        
        return $strengths;
    }
    
    /**
     * Identify weaknesses
     */
    private function identify_weaknesses($intelligence) {
        $weaknesses = [];
        
        // SEO weakness
        if ($intelligence['seo_score'] < 60) {
            $weaknesses[] = [
                'icon' => '⚠️',
                'label' => __('SEO Optimizasyonu Zayıf', 'dodo-ai-seo'),
                'score' => $intelligence['seo_score'],
                'severity' => 'high',
            ];
        } elseif ($intelligence['seo_score'] < 75) {
            $weaknesses[] = [
                'icon' => '⚠️',
                'label' => __('SEO Geliştirilebilir', 'dodo-ai-seo'),
                'score' => $intelligence['seo_score'],
                'severity' => 'medium',
            ];
        }
        
        // Quality weakness
        if ($intelligence['quality_score'] < 60) {
            $weaknesses[] = [
                'icon' => '⚠️',
                'label' => __('İçerik Kalitesi Düşük', 'dodo-ai-seo'),
                'score' => $intelligence['quality_score'],
                'severity' => 'high',
            ];
        } elseif ($intelligence['quality_score'] < 75) {
            $weaknesses[] = [
                'icon' => '⚠️',
                'label' => __('Kalite Geliştirilebilir', 'dodo-ai-seo'),
                'score' => $intelligence['quality_score'],
                'severity' => 'medium',
            ];
        }
        
        // Readability weakness
        if ($intelligence['readability_score'] < 60) {
            $weaknesses[] = [
                'icon' => '⚠️',
                'label' => __('Okunabilirlik Zayıf', 'dodo-ai-seo'),
                'score' => $intelligence['readability_score'],
                'severity' => 'high',
            ];
        } elseif ($intelligence['readability_score'] < 75) {
            $weaknesses[] = [
                'icon' => '⚠️',
                'label' => __('Okunabilirlik Optimize Edilebilir', 'dodo-ai-seo'),
                'score' => $intelligence['readability_score'],
                'severity' => 'medium',
            ];
        }
        
        // AI risk weakness
        if (isset($intelligence['ai_detection']) && $intelligence['ai_detection']['risk_level'] === 'high') {
            $weaknesses[] = [
                'icon' => '⚠️',
                'label' => __('AI Benzerliği Yüksek', 'dodo-ai-seo'),
                'score' => $intelligence['ai_detection']['ai_similarity_score'],
                'severity' => 'high',
            ];
        } elseif (isset($intelligence['ai_detection']) && $intelligence['ai_detection']['risk_level'] === 'medium') {
            $weaknesses[] = [
                'icon' => '⚠️',
                'label' => __('AI Benzerliği Orta', 'dodo-ai-seo'),
                'score' => $intelligence['ai_detection']['ai_similarity_score'],
                'severity' => 'medium',
            ];
        }
        
        // Depth weakness
        if (isset($intelligence['depth_analysis']) && $intelligence['depth_analysis']['depth_level'] === 'surface') {
            $weaknesses[] = [
                'icon' => '⚠️',
                'label' => __('Yüzeysel İçerik', 'dodo-ai-seo'),
                'score' => $intelligence['depth_analysis']['depth_score'],
                'severity' => 'medium',
            ];
        }
        
        return $weaknesses;
    }
    
    /**
     * Identify critical warnings
     */
    private function identify_critical_warnings($intelligence) {
        $warnings = [];
        
        // Critical health score
        if ($intelligence['health_score'] < 50) {
            $warnings[] = [
                'icon' => '🔴',
                'message' => __('İçerik sağlık skoru kritik seviyede', 'dodo-ai-seo'),
                'severity' => 'critical',
            ];
        }
        
        // High risk level
        if ($intelligence['risk_level'] === 'high') {
            $warnings[] = [
                'icon' => '🔴',
                'message' => __('Yüksek risk faktörleri tespit edildi', 'dodo-ai-seo'),
                'severity' => 'critical',
            ];
        }
        
        // Semantic warnings
        if (isset($intelligence['semantic_analysis']['warnings'])) {
            foreach ($intelligence['semantic_analysis']['warnings'] as $warning) {
                if ($warning['severity'] === 'high') {
                    $warnings[] = [
                        'icon' => '🔴',
                        'message' => $warning['message'],
                        'severity' => 'critical',
                    ];
                }
            }
        }
        
        // Very low confidence
        if ($intelligence['confidence_score'] < 40) {
            $warnings[] = [
                'icon' => '🔴',
                'message' => __('Analiz güven skoru çok düşük', 'dodo-ai-seo'),
                'severity' => 'critical',
            ];
        }
        
        return $warnings;
    }
    
    /**
     * Suggest next actions
     */
    private function suggest_next_actions($intelligence) {
        $actions = [];
        
        // Prioritize based on scores
        $priorities = [];
        
        if ($intelligence['seo_score'] < 70) {
            $priorities[] = [
                'score' => $intelligence['seo_score'],
                'action' => __('SEO optimizasyonunu iyileştir', 'dodo-ai-seo'),
                'icon' => '🔍',
            ];
        }
        
        if ($intelligence['quality_score'] < 70) {
            $priorities[] = [
                'score' => $intelligence['quality_score'],
                'action' => __('İçerik kalitesini artır', 'dodo-ai-seo'),
                'icon' => '📝',
            ];
        }
        
        if ($intelligence['readability_score'] < 70) {
            $priorities[] = [
                'score' => $intelligence['readability_score'],
                'action' => __('Okunabilirliği optimize et', 'dodo-ai-seo'),
                'icon' => '📖',
            ];
        }
        
        if (isset($intelligence['ai_detection']) && $intelligence['ai_detection']['risk_level'] !== 'low') {
            $priorities[] = [
                'score' => 100 - $intelligence['ai_detection']['ai_similarity_score'],
                'action' => __('AI kalıplarını azalt', 'dodo-ai-seo'),
                'icon' => '🤖',
            ];
        }
        
        // Sort by score (lowest first = highest priority)
        usort($priorities, function($a, $b) {
            return $a['score'] - $b['score'];
        });
        
        // Return top 3 actions
        return array_slice($priorities, 0, 3);
    }
    
    /**
     * Determine overall status
     */
    private function determine_overall_status($intelligence) {
        $health = $intelligence['health_score'];
        
        if ($health >= 90) {
            return [
                'label' => __('Mükemmel', 'dodo-ai-seo'),
                'icon' => '🎉',
                'class' => 'excellent',
            ];
        } elseif ($health >= 75) {
            return [
                'label' => __('İyi', 'dodo-ai-seo'),
                'icon' => '✅',
                'class' => 'good',
            ];
        } elseif ($health >= 60) {
            return [
                'label' => __('Orta', 'dodo-ai-seo'),
                'icon' => '⚠️',
                'class' => 'fair',
            ];
        } else {
            return [
                'label' => __('Zayıf', 'dodo-ai-seo'),
                'icon' => '🔴',
                'class' => 'poor',
            ];
        }
    }
}
