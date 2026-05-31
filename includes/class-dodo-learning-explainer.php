<?php
/**
 * Learning Explainer System
 * 
 * Provides clear explanations for all learning decisions
 * Makes AI learning transparent and understandable
 * 
 * @package DODO_AI_SEO
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Learning_Explainer {
    
    /**
     * Explain why recommendation was made
     * 
     * @param string $action_type Action type
     * @param array $learned_data Learned data
     * @return array Explanation
     */
    public function explain_recommendation($action_type, $learned_data) {
        $explanation = [
            'action' => $this->get_action_label($action_type),
            'decision' => '',
            'reasoning' => [],
            'evidence' => [],
            'confidence' => 0,
        ];
        
        $avg_score = $learned_data['avg_score'] ?? 0;
        $sample_count = $learned_data['sample_count'] ?? 0;
        $confidence = $learned_data['confidence'] ?? 0;
        
        // Decision
        if ($avg_score > 50) {
            $explanation['decision'] = 'Strongly Recommended';
        } elseif ($avg_score > 20) {
            $explanation['decision'] = 'Recommended';
        } elseif ($avg_score > -20) {
            $explanation['decision'] = 'Neutral';
        } else {
            $explanation['decision'] = 'Not Recommended';
        }
        
        // Reasoning
        $explanation['reasoning'][] = sprintf(
            'Based on %d real-world implementations',
            $sample_count
        );
        
        $explanation['reasoning'][] = sprintf(
            'Average impact score: %+.1f (scale: -100 to +100)',
            $avg_score
        );
        
        if ($avg_score > 50) {
            $explanation['reasoning'][] = 'This action consistently shows strong positive results';
        } elseif ($avg_score > 20) {
            $explanation['reasoning'][] = 'This action shows moderate positive impact';
        } elseif ($avg_score < -20) {
            $explanation['reasoning'][] = 'This action has shown negative impact - avoid using';
        } else {
            $explanation['reasoning'][] = 'Results are mixed - use with caution';
        }
        
        // Confidence explanation
        if ($confidence >= 80) {
            $explanation['reasoning'][] = 'High confidence - strong evidence from many cases';
        } elseif ($confidence >= 60) {
            $explanation['reasoning'][] = 'Medium confidence - moderate evidence available';
        } else {
            $explanation['reasoning'][] = 'Low confidence - limited data, more testing needed';
        }
        
        // Evidence
        if (isset($learned_data['recent_outcomes'])) {
            foreach (array_slice($learned_data['recent_outcomes'], 0, 3) as $outcome) {
                $explanation['evidence'][] = sprintf(
                    'Score: %+d, Date: %s',
                    $outcome['score'],
                    $outcome['date'] ?? 'recent'
                );
            }
        }
        
        $explanation['confidence'] = $confidence;
        
        return $explanation;
    }
    
    /**
     * Explain strategy evolution
     * 
     * @param array $changes Strategy changes
     * @param array $learning_data Learning data
     * @return array Explanation
     */
    public function explain_evolution($changes, $learning_data) {
        $explanation = [
            'summary' => '',
            'changes' => [],
            'reasoning' => [],
            'expected_impact' => '',
        ];
        
        if (empty($changes)) {
            $explanation['summary'] = 'No changes needed - current strategy is optimal';
            $explanation['reasoning'][] = 'Learning data shows current parameters perform well';
            return $explanation;
        }
        
        $explanation['summary'] = sprintf(
            'Strategy evolved with %d parameter changes',
            count($changes)
        );
        
        // Explain each change
        foreach ($changes as $change) {
            $param_explanation = $this->explain_parameter_change(
                $change['parameter'],
                $change['from'],
                $change['to'],
                $learning_data
            );
            
            $explanation['changes'][] = $param_explanation;
        }
        
        // Overall reasoning
        $sample_count = $learning_data['sample_count'] ?? 0;
        $explanation['reasoning'][] = sprintf(
            'Evolution based on %d successful outcomes',
            $sample_count
        );
        
        $avg_score = $learning_data['avg_score'] ?? 0;
        if ($avg_score > 50) {
            $explanation['reasoning'][] = 'Strong positive results justify these adaptations';
            $explanation['expected_impact'] = 'Significant improvement expected';
        } elseif ($avg_score > 20) {
            $explanation['reasoning'][] = 'Moderate positive results support these changes';
            $explanation['expected_impact'] = 'Moderate improvement expected';
        } else {
            $explanation['reasoning'][] = 'Experimental changes based on limited data';
            $explanation['expected_impact'] = 'Impact uncertain - monitoring required';
        }
        
        return $explanation;
    }
    
    /**
     * Explain parameter change
     */
    private function explain_parameter_change($parameter, $from, $to, $learning_data) {
        $change_explanations = [
            'faq_count' => function($from, $to, $data) {
                if ($to > $from) {
                    return sprintf(
                        'FAQ count increased from %d to %d - more FAQs showed %.1f%% better performance',
                        $from,
                        $to,
                        ($data['avg_score'] ?? 0)
                    );
                } else {
                    return sprintf(
                        'FAQ count reduced from %d to %d - fewer FAQs performed better',
                        $from,
                        $to
                    );
                }
            },
            
            'semantic_density' => function($from, $to, $data) {
                return sprintf(
                    'Semantic density changed from "%s" to "%s" - richer semantic coverage showed positive impact',
                    $from,
                    $to
                );
            },
            
            'target_word_count' => function($from, $to, $data) {
                if ($to > $from) {
                    return sprintf(
                        'Target word count increased from %d to %d - longer content ranked better',
                        $from,
                        $to
                    );
                } else {
                    return sprintf(
                        'Target word count reduced from %d to %d - concise content performed better',
                        $from,
                        $to
                    );
                }
            },
            
            'cta_density' => function($from, $to, $data) {
                if ($to > $from) {
                    return 'CTA density increased - more calls-to-action showed better conversion';
                } else {
                    return 'CTA density reduced - less aggressive approach performed better';
                }
            },
        ];
        
        if (isset($change_explanations[$parameter])) {
            return [
                'parameter' => $this->get_parameter_label($parameter),
                'from' => $from,
                'to' => $to,
                'explanation' => $change_explanations[$parameter]($from, $to, $learning_data),
            ];
        }
        
        return [
            'parameter' => $this->get_parameter_label($parameter),
            'from' => $from,
            'to' => $to,
            'explanation' => sprintf('Changed from %s to %s based on learned patterns', $from, $to),
        ];
    }
    
    /**
     * Explain niche-specific strategy
     * 
     * @param string $niche Niche
     * @param array $strategy Strategy
     * @param array $niche_data Niche learning data
     * @return array Explanation
     */
    public function explain_niche_strategy($niche, $strategy, $niche_data) {
        $explanation = [
            'niche' => $this->get_niche_label($niche),
            'summary' => '',
            'key_adaptations' => [],
            'reasoning' => [],
            'confidence' => 0,
        ];
        
        $sample_count = $niche_data['sample_count'] ?? 0;
        $confidence = $niche_data['confidence'] ?? 0;
        
        $explanation['summary'] = sprintf(
            'Strategy optimized for %s based on %d cases',
            $this->get_niche_label($niche),
            $sample_count
        );
        
        // Key adaptations
        $key_params = ['faq_count', 'semantic_density', 'cta_style', 'target_word_count'];
        
        foreach ($key_params as $param) {
            if (isset($strategy[$param])) {
                $explanation['key_adaptations'][] = [
                    'parameter' => $this->get_parameter_label($param),
                    'value' => $strategy[$param],
                    'reason' => $this->explain_niche_adaptation($param, $strategy[$param], $niche),
                ];
            }
        }
        
        // Reasoning
        if (isset($niche_data['best_performing_actions'])) {
            $best = array_slice($niche_data['best_performing_actions'], 0, 3, true);
            
            foreach ($best as $action => $score) {
                $explanation['reasoning'][] = sprintf(
                    '%s works well in %s (score: %.1f)',
                    $this->get_action_label($action),
                    $niche,
                    $score
                );
            }
        }
        
        if ($confidence >= 80) {
            $explanation['reasoning'][] = 'High confidence - strong niche-specific evidence';
        } elseif ($confidence >= 60) {
            $explanation['reasoning'][] = 'Medium confidence - moderate niche data available';
        } else {
            $explanation['reasoning'][] = 'Low confidence - limited niche-specific data';
        }
        
        $explanation['confidence'] = $confidence;
        
        return $explanation;
    }
    
    /**
     * Explain niche adaptation
     */
    private function explain_niche_adaptation($parameter, $value, $niche) {
        $niche_explanations = [
            'saas' => [
                'faq_count' => 'SaaS users need detailed technical FAQs',
                'semantic_density' => 'High semantic density for technical terms',
                'cta_style' => 'Trial-focused CTAs convert better',
            ],
            'ecommerce' => [
                'faq_count' => 'Product FAQs address buyer concerns',
                'semantic_density' => 'Medium density with product attributes',
                'cta_style' => 'Purchase-focused CTAs drive sales',
            ],
            'b2b' => [
                'faq_count' => 'Professional FAQs for decision makers',
                'semantic_density' => 'High density for industry terms',
                'cta_style' => 'Consultation-focused CTAs work best',
            ],
            'local_business' => [
                'faq_count' => 'Location and service FAQs are critical',
                'semantic_density' => 'Medium density with local terms',
                'cta_style' => 'Contact-focused CTAs drive calls',
            ],
        ];
        
        if (isset($niche_explanations[$niche][$parameter])) {
            return $niche_explanations[$niche][$parameter];
        }
        
        return sprintf('Optimized for %s niche', $niche);
    }
    
    /**
     * Explain impact result
     * 
     * @param array $impact_data Impact data
     * @return array Explanation
     */
    public function explain_impact($impact_data) {
        $explanation = [
            'summary' => '',
            'key_changes' => [],
            'reasoning' => [],
            'confidence' => 0,
        ];
        
        $overall_score = $impact_data['overall_score'] ?? 0;
        $confidence = $impact_data['confidence'] ?? 0;
        
        // Summary
        if ($overall_score > 50) {
            $explanation['summary'] = sprintf(
                'Strong positive impact detected (score: +%d)',
                $overall_score
            );
        } elseif ($overall_score > 20) {
            $explanation['summary'] = sprintf(
                'Moderate positive impact detected (score: +%d)',
                $overall_score
            );
        } elseif ($overall_score > -20) {
            $explanation['summary'] = sprintf(
                'Minimal impact detected (score: %+d)',
                $overall_score
            );
        } else {
            $explanation['summary'] = sprintf(
                'Negative impact detected (score: %d)',
                $overall_score
            );
        }
        
        // Key changes
        if (isset($impact_data['changes'])) {
            foreach ($impact_data['changes'] as $metric => $change_data) {
                if (isset($change_data['score']) && abs($change_data['score']) > 10) {
                    $explanation['key_changes'][] = [
                        'metric' => $this->get_metric_label($metric),
                        'change' => $change_data['score'],
                        'description' => $change_data['reasoning'] ?? '',
                    ];
                }
            }
        }
        
        // Reasoning
        if ($confidence >= 70) {
            $explanation['reasoning'][] = 'High confidence - multiple data sources confirm impact';
        } elseif ($confidence >= 50) {
            $explanation['reasoning'][] = 'Medium confidence - some data sources available';
        } else {
            $explanation['reasoning'][] = 'Low confidence - limited data for measurement';
        }
        
        if (!empty($impact_data['reasoning'])) {
            foreach ($impact_data['reasoning'] as $reason) {
                $explanation['reasoning'][] = $reason;
            }
        }
        
        $explanation['confidence'] = $confidence;
        
        return $explanation;
    }
    
    /**
     * Explain content decay
     * 
     * @param array $decay_data Decay data
     * @return array Explanation
     */
    public function explain_decay($decay_data) {
        $explanation = [
            'summary' => '',
            'signals' => [],
            'severity' => '',
            'action_needed' => '',
        ];
        
        $decay_score = $decay_data['decay_score'] ?? 0;
        $severity = $decay_data['severity'] ?? 'none';
        
        // Summary
        if ($decay_score >= 60) {
            $explanation['summary'] = 'Critical decay detected - content losing significant performance';
        } elseif ($decay_score >= 40) {
            $explanation['summary'] = 'High decay detected - content performance declining';
        } elseif ($decay_score >= 20) {
            $explanation['summary'] = 'Moderate decay detected - early warning signs';
        } else {
            $explanation['summary'] = 'Content is healthy - no significant decay';
        }
        
        // Signals
        if (isset($decay_data['signals'])) {
            foreach ($decay_data['signals'] as $signal) {
                $explanation['signals'][] = [
                    'type' => $this->get_signal_label($signal['type']),
                    'description' => $signal['reasoning'] ?? 'Decay signal detected',
                ];
            }
        }
        
        // Severity
        $explanation['severity'] = ucfirst($severity);
        
        // Action needed
        if ($decay_score >= 60) {
            $explanation['action_needed'] = 'Immediate content refresh required to recover performance';
        } elseif ($decay_score >= 40) {
            $explanation['action_needed'] = 'Content refresh recommended within 1-2 weeks';
        } elseif ($decay_score >= 20) {
            $explanation['action_needed'] = 'Monitor closely and plan refresh if trend continues';
        } else {
            $explanation['action_needed'] = 'No immediate action required - continue monitoring';
        }
        
        return $explanation;
    }
    
    /**
     * Get action label
     */
    private function get_action_label($action_type) {
        $labels = [
            'title_rewrite' => 'Title Rewrite',
            'meta_rewrite' => 'Meta Description',
            'content_refresh' => 'Content Refresh',
            'faq_update' => 'FAQ Update',
            'internal_links' => 'Internal Links',
            'semantic_expansion' => 'Semantic Expansion',
            'geo_optimization' => 'GEO Optimization',
            'humanization' => 'Humanization',
        ];
        
        return $labels[$action_type] ?? ucwords(str_replace('_', ' ', $action_type));
    }
    
    /**
     * Get parameter label
     */
    private function get_parameter_label($parameter) {
        $labels = [
            'faq_count' => 'FAQ Count',
            'semantic_density' => 'Semantic Density',
            'target_word_count' => 'Word Count',
            'cta_density' => 'CTA Density',
            'cta_style' => 'CTA Style',
        ];
        
        return $labels[$parameter] ?? ucwords(str_replace('_', ' ', $parameter));
    }
    
    /**
     * Get niche label
     */
    private function get_niche_label($niche) {
        $labels = [
            'saas' => 'SaaS',
            'ecommerce' => 'E-commerce',
            'b2b' => 'B2B',
            'local_business' => 'Local Business',
            'blog_media' => 'Blog/Media',
            'education' => 'Education',
            'healthcare' => 'Healthcare',
            'finance' => 'Finance',
        ];
        
        return $labels[$niche] ?? ucwords(str_replace('_', ' ', $niche));
    }
    
    /**
     * Get metric label
     */
    private function get_metric_label($metric) {
        $labels = [
            'gsc' => 'Google Search Console',
            'rankings' => 'Search Rankings',
            'ai_visibility' => 'AI Overview Visibility',
            'internal_links' => 'Internal Links',
        ];
        
        return $labels[$metric] ?? ucwords(str_replace('_', ' ', $metric));
    }
    
    /**
     * Get signal label
     */
    private function get_signal_label($signal_type) {
        $labels = [
            'traffic_decay' => 'Traffic Decline',
            'ctr_decay' => 'CTR Decline',
            'ranking_decay' => 'Ranking Drop',
            'ai_visibility_decay' => 'AI Visibility Loss',
            'content_freshness' => 'Outdated Content',
        ];
        
        return $labels[$signal_type] ?? ucwords(str_replace('_', ' ', $signal_type));
    }
}
