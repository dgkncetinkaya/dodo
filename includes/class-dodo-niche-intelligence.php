<?php
/**
 * Dynamic Niche Strategy System
 * 
 * Learns niche-specific patterns and adapts strategies per niche
 * Different niches (SaaS, E-commerce, B2B, etc.) require different approaches
 * 
 * @package DODO_AI_SEO
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Niche_Intelligence {
    
    /**
     * Supported niches
     */
    const NICHE_SAAS = 'saas';
    const NICHE_ECOMMERCE = 'ecommerce';
    const NICHE_B2B = 'b2b';
    const NICHE_LOCAL_BUSINESS = 'local_business';
    const NICHE_BLOG_MEDIA = 'blog_media';
    const NICHE_EDUCATION = 'education';
    const NICHE_HEALTHCARE = 'healthcare';
    const NICHE_FINANCE = 'finance';
    const NICHE_MANUFACTURING = 'manufacturing';
    const NICHE_REAL_ESTATE = 'real_estate';
    
    /**
     * Minimum samples for niche learning
     */
    const MIN_SAMPLES = 10;
    
    /**
     * Feedback engine
     */
    private $feedback_engine;
    
    /**
     * Impact tracker
     */
    private $impact_tracker;
    
    /**
     * Constructor
     */
    public function __construct() {
        if (class_exists('DODO_Feedback_Engine')) {
            require_once plugin_dir_path(__FILE__) . 'class-dodo-feedback-engine.php';
            $this->feedback_engine = new DODO_Feedback_Engine();
        }
        
        if (class_exists('DODO_Impact_Tracker')) {
            require_once plugin_dir_path(__FILE__) . 'class-dodo-impact-tracker.php';
            $this->impact_tracker = new DODO_Impact_Tracker();
        }
    }
    
    /**
     * Detect niche for post
     * 
     * @param int $post_id Post ID
     * @return string Detected niche
     */
    public function detect_niche($post_id) {
        $post = get_post($post_id);
        
        if (!$post) {
            return 'unknown';
        }
        
        // Check manual niche setting
        $manual_niche = get_post_meta($post_id, '_dodo_niche', true);
        if ($manual_niche) {
            return $manual_niche;
        }
        
        // Auto-detect from content
        $content = $post->post_content . ' ' . $post->post_title;
        $content_lower = strtolower($content);
        
        $niche_signals = [
            self::NICHE_SAAS => [
                'keywords' => ['software', 'saas', 'platform', 'api', 'integration', 'cloud', 'subscription', 'dashboard'],
                'score' => 0,
            ],
            self::NICHE_ECOMMERCE => [
                'keywords' => ['buy', 'shop', 'product', 'price', 'cart', 'checkout', 'shipping', 'store', 'sale'],
                'score' => 0,
            ],
            self::NICHE_B2B => [
                'keywords' => ['enterprise', 'business', 'solution', 'roi', 'efficiency', 'productivity', 'workflow'],
                'score' => 0,
            ],
            self::NICHE_LOCAL_BUSINESS => [
                'keywords' => ['near me', 'location', 'hours', 'address', 'local', 'service area', 'appointment'],
                'score' => 0,
            ],
            self::NICHE_BLOG_MEDIA => [
                'keywords' => ['article', 'blog', 'news', 'story', 'guide', 'tutorial', 'tips', 'how to'],
                'score' => 0,
            ],
            self::NICHE_EDUCATION => [
                'keywords' => ['course', 'learn', 'training', 'education', 'student', 'teacher', 'lesson', 'certification'],
                'score' => 0,
            ],
            self::NICHE_HEALTHCARE => [
                'keywords' => ['health', 'medical', 'doctor', 'patient', 'treatment', 'diagnosis', 'symptoms', 'care'],
                'score' => 0,
            ],
            self::NICHE_FINANCE => [
                'keywords' => ['finance', 'investment', 'banking', 'loan', 'credit', 'insurance', 'mortgage', 'tax'],
                'score' => 0,
            ],
            self::NICHE_MANUFACTURING => [
                'keywords' => ['manufacturing', 'production', 'factory', 'industrial', 'machinery', 'equipment', 'supply chain'],
                'score' => 0,
            ],
            self::NICHE_REAL_ESTATE => [
                'keywords' => ['property', 'real estate', 'house', 'apartment', 'rent', 'lease', 'broker', 'listing'],
                'score' => 0,
            ],
        ];
        
        // Score each niche
        foreach ($niche_signals as $niche => $data) {
            foreach ($data['keywords'] as $keyword) {
                if (strpos($content_lower, $keyword) !== false) {
                    $niche_signals[$niche]['score']++;
                }
            }
        }
        
        // Get highest scoring niche
        $detected_niche = 'general';
        $max_score = 0;
        
        foreach ($niche_signals as $niche => $data) {
            if ($data['score'] > $max_score) {
                $max_score = $data['score'];
                $detected_niche = $niche;
            }
        }
        
        // Require minimum score
        if ($max_score < 2) {
            $detected_niche = 'general';
        }
        
        // Cache detection
        update_post_meta($post_id, '_dodo_detected_niche', $detected_niche);
        
        error_log("[DODO Niche] Detected niche for post {$post_id}: {$detected_niche} (score: {$max_score})");
        
        return $detected_niche;
    }
    
    /**
     * Get niche-specific strategy
     * 
     * Returns adapted strategy parameters for niche
     * 
     * @param string $niche Niche identifier
     * @param array $base_strategy Base strategy
     * @return array Niche-adapted strategy
     */
    public function get_niche_strategy($niche, $base_strategy = []) {
        error_log("[DODO Niche] Getting strategy for niche: {$niche}");
        
        // Get learned niche patterns
        $learned = $this->get_learned_niche_patterns($niche);
        
        // Start with base strategy
        $strategy = $base_strategy;
        
        // Apply niche-specific defaults
        $strategy = $this->apply_niche_defaults($niche, $strategy);
        
        // Apply learned adaptations
        if ($learned && $learned['sample_count'] >= self::MIN_SAMPLES) {
            $strategy = $this->apply_learned_adaptations($niche, $strategy, $learned);
        }
        
        // Add niche metadata
        $strategy['niche'] = $niche;
        $strategy['niche_confidence'] = $learned['confidence'] ?? 0;
        $strategy['niche_samples'] = $learned['sample_count'] ?? 0;
        
        return $strategy;
    }
    
    /**
     * Apply niche-specific defaults
     */
    private function apply_niche_defaults($niche, $strategy) {
        switch ($niche) {
            case self::NICHE_SAAS:
                $strategy['faq_count'] = 6;
                $strategy['faq_style'] = 'technical';
                $strategy['semantic_density'] = 'high';
                $strategy['cta_density'] = 'high';
                $strategy['cta_style'] = 'trial_focused';
                $strategy['title_pattern'] = 'benefit_driven';
                $strategy['target_word_count'] = 2000;
                break;
                
            case self::NICHE_ECOMMERCE:
                $strategy['faq_count'] = 8;
                $strategy['faq_style'] = 'product_focused';
                $strategy['semantic_density'] = 'medium';
                $strategy['cta_density'] = 'very_high';
                $strategy['cta_style'] = 'purchase_focused';
                $strategy['title_pattern'] = 'price_benefit';
                $strategy['target_word_count'] = 1200;
                break;
                
            case self::NICHE_B2B:
                $strategy['faq_count'] = 5;
                $strategy['faq_style'] = 'professional';
                $strategy['semantic_density'] = 'high';
                $strategy['cta_density'] = 'medium';
                $strategy['cta_style'] = 'consultation_focused';
                $strategy['title_pattern'] = 'roi_driven';
                $strategy['target_word_count'] = 2500;
                break;
                
            case self::NICHE_LOCAL_BUSINESS:
                $strategy['faq_count'] = 7;
                $strategy['faq_style'] = 'location_focused';
                $strategy['semantic_density'] = 'medium';
                $strategy['cta_density'] = 'high';
                $strategy['cta_style'] = 'contact_focused';
                $strategy['title_pattern'] = 'location_service';
                $strategy['target_word_count'] = 1000;
                $strategy['geo_optimization'] = 'aggressive';
                break;
                
            case self::NICHE_BLOG_MEDIA:
                $strategy['faq_count'] = 4;
                $strategy['faq_style'] = 'informational';
                $strategy['semantic_density'] = 'medium';
                $strategy['cta_density'] = 'low';
                $strategy['cta_style'] = 'engagement_focused';
                $strategy['title_pattern'] = 'curiosity_driven';
                $strategy['target_word_count'] = 1500;
                break;
                
            case self::NICHE_EDUCATION:
                $strategy['faq_count'] = 6;
                $strategy['faq_style'] = 'educational';
                $strategy['semantic_density'] = 'high';
                $strategy['cta_density'] = 'medium';
                $strategy['cta_style'] = 'enrollment_focused';
                $strategy['title_pattern'] = 'learning_outcome';
                $strategy['target_word_count'] = 2000;
                break;
                
            case self::NICHE_HEALTHCARE:
                $strategy['faq_count'] = 8;
                $strategy['faq_style'] = 'medical';
                $strategy['semantic_density'] = 'very_high';
                $strategy['cta_density'] = 'low';
                $strategy['cta_style'] = 'appointment_focused';
                $strategy['title_pattern'] = 'symptom_solution';
                $strategy['target_word_count'] = 1800;
                $strategy['trust_signals'] = 'high';
                break;
                
            case self::NICHE_FINANCE:
                $strategy['faq_count'] = 7;
                $strategy['faq_style'] = 'financial';
                $strategy['semantic_density'] = 'high';
                $strategy['cta_density'] = 'medium';
                $strategy['cta_style'] = 'consultation_focused';
                $strategy['title_pattern'] = 'benefit_security';
                $strategy['target_word_count'] = 2200;
                $strategy['trust_signals'] = 'very_high';
                break;
                
            case self::NICHE_MANUFACTURING:
                $strategy['faq_count'] = 5;
                $strategy['faq_style'] = 'technical';
                $strategy['semantic_density'] = 'high';
                $strategy['cta_density'] = 'medium';
                $strategy['cta_style'] = 'quote_focused';
                $strategy['title_pattern'] = 'specification_driven';
                $strategy['target_word_count'] = 1800;
                break;
                
            case self::NICHE_REAL_ESTATE:
                $strategy['faq_count'] = 6;
                $strategy['faq_style'] = 'property_focused';
                $strategy['semantic_density'] = 'medium';
                $strategy['cta_density'] = 'high';
                $strategy['cta_style'] = 'viewing_focused';
                $strategy['title_pattern'] = 'location_feature';
                $strategy['target_word_count'] = 1200;
                $strategy['geo_optimization'] = 'high';
                break;
                
            default:
                // General niche - use base strategy
                break;
        }
        
        return $strategy;
    }
    
    /**
     * Apply learned adaptations
     */
    private function apply_learned_adaptations($niche, $strategy, $learned) {
        // Apply learned FAQ patterns
        if (isset($learned['optimal_faq_count'])) {
            $strategy['faq_count'] = $learned['optimal_faq_count'];
        }
        
        // Apply learned semantic density
        if (isset($learned['optimal_semantic_density'])) {
            $strategy['semantic_density'] = $learned['optimal_semantic_density'];
        }
        
        // Apply learned CTA strategy
        if (isset($learned['optimal_cta_density'])) {
            $strategy['cta_density'] = $learned['optimal_cta_density'];
        }
        
        // Apply learned content length
        if (isset($learned['optimal_word_count'])) {
            $strategy['target_word_count'] = $learned['optimal_word_count'];
        }
        
        // Apply learned title patterns
        if (isset($learned['successful_title_patterns'])) {
            $strategy['title_patterns'] = $learned['successful_title_patterns'];
        }
        
        error_log(sprintf(
            '[DODO Niche] Applied learned adaptations for %s (samples: %d, confidence: %d)',
            $niche,
            $learned['sample_count'],
            $learned['confidence']
        ));
        
        return $strategy;
    }
    
    /**
     * Learn from niche-specific outcomes
     * 
     * @param string $niche Niche
     * @param array $impact_data Impact data
     */
    public function learn_from_niche_outcome($niche, $impact_data) {
        if (!$this->feedback_engine) {
            return false;
        }
        
        error_log("[DODO Niche] Learning from outcome for niche: {$niche}");
        
        // Learn with niche context
        $context = ['niche' => $niche];
        $this->feedback_engine->learn_from_impact($impact_data, $context);
        
        // Update niche-specific patterns
        $this->update_niche_patterns($niche, $impact_data);
        
        return true;
    }
    
    /**
     * Update niche-specific patterns
     */
    private function update_niche_patterns($niche, $impact_data) {
        $patterns = $this->get_learned_niche_patterns($niche);
        
        if (!$patterns) {
            $patterns = [
                'niche' => $niche,
                'sample_count' => 0,
                'confidence' => 0,
                'outcomes' => [],
            ];
        }
        
        // Record outcome
        $patterns['outcomes'][] = [
            'action_type' => $impact_data['action_type'],
            'score' => $impact_data['overall_score'],
            'timestamp' => current_time('mysql'),
        ];
        
        $patterns['sample_count']++;
        
        // Calculate confidence
        $patterns['confidence'] = $this->calculate_niche_confidence($patterns['sample_count']);
        
        // Analyze patterns
        if ($patterns['sample_count'] >= self::MIN_SAMPLES) {
            $patterns = $this->analyze_niche_patterns($patterns);
        }
        
        // Save patterns
        $this->save_niche_patterns($niche, $patterns);
    }
    
    /**
     * Analyze niche patterns
     */
    private function analyze_niche_patterns($patterns) {
        $outcomes = $patterns['outcomes'];
        
        if (empty($outcomes)) {
            return $patterns;
        }
        
        // Analyze by action type
        $by_action = [];
        
        foreach ($outcomes as $outcome) {
            $action = $outcome['action_type'];
            
            if (!isset($by_action[$action])) {
                $by_action[$action] = [
                    'scores' => [],
                    'count' => 0,
                ];
            }
            
            $by_action[$action]['scores'][] = $outcome['score'];
            $by_action[$action]['count']++;
        }
        
        // Find best performing actions
        $best_actions = [];
        
        foreach ($by_action as $action => $data) {
            if ($data['count'] >= 3) {
                $avg_score = array_sum($data['scores']) / $data['count'];
                $best_actions[$action] = round($avg_score, 1);
            }
        }
        
        arsort($best_actions);
        $patterns['best_performing_actions'] = $best_actions;
        
        // Analyze optimal parameters (would need more detailed tracking)
        // For now, use heuristics based on successful outcomes
        
        $successful_outcomes = array_filter($outcomes, function($o) {
            return $o['score'] > 50;
        });
        
        if (count($successful_outcomes) >= 5) {
            // These would be learned from actual data
            // For now, mark that learning is available
            $patterns['has_learned_parameters'] = true;
        }
        
        return $patterns;
    }
    
    /**
     * Get learned niche patterns
     */
    private function get_learned_niche_patterns($niche) {
        $option_key = "dodo_niche_patterns_{$niche}";
        return get_option($option_key, null);
    }
    
    /**
     * Save niche patterns
     */
    private function save_niche_patterns($niche, $patterns) {
        $option_key = "dodo_niche_patterns_{$niche}";
        update_option($option_key, $patterns);
    }
    
    /**
     * Calculate niche confidence
     */
    private function calculate_niche_confidence($sample_count) {
        if ($sample_count >= 50) {
            return 90;
        } elseif ($sample_count >= 30) {
            return 75;
        } elseif ($sample_count >= 20) {
            return 60;
        } elseif ($sample_count >= self::MIN_SAMPLES) {
            return 40;
        }
        
        return 20;
    }
    
    /**
     * Get niche insights
     * 
     * @return array Niche insights
     */
    public function get_niche_insights() {
        $niches = [
            self::NICHE_SAAS,
            self::NICHE_ECOMMERCE,
            self::NICHE_B2B,
            self::NICHE_LOCAL_BUSINESS,
            self::NICHE_BLOG_MEDIA,
            self::NICHE_EDUCATION,
            self::NICHE_HEALTHCARE,
            self::NICHE_FINANCE,
            self::NICHE_MANUFACTURING,
            self::NICHE_REAL_ESTATE,
        ];
        
        $insights = [
            'by_niche' => [],
            'most_learned' => [],
            'needs_more_data' => [],
        ];
        
        foreach ($niches as $niche) {
            $patterns = $this->get_learned_niche_patterns($niche);
            
            if ($patterns) {
                $insights['by_niche'][$niche] = [
                    'sample_count' => $patterns['sample_count'],
                    'confidence' => $patterns['confidence'],
                    'best_actions' => $patterns['best_performing_actions'] ?? [],
                ];
                
                if ($patterns['sample_count'] >= 30) {
                    $insights['most_learned'][] = $niche;
                } elseif ($patterns['sample_count'] < self::MIN_SAMPLES) {
                    $insights['needs_more_data'][] = $niche;
                }
            } else {
                $insights['needs_more_data'][] = $niche;
            }
        }
        
        return $insights;
    }
    
    /**
     * Compare niche performance
     * 
     * @param string $niche1 First niche
     * @param string $niche2 Second niche
     * @return array Comparison
     */
    public function compare_niches($niche1, $niche2) {
        $patterns1 = $this->get_learned_niche_patterns($niche1);
        $patterns2 = $this->get_learned_niche_patterns($niche2);
        
        if (!$patterns1 || !$patterns2) {
            return ['error' => 'Insufficient data for comparison'];
        }
        
        $comparison = [
            'niche1' => $niche1,
            'niche2' => $niche2,
            'sample_counts' => [
                $niche1 => $patterns1['sample_count'],
                $niche2 => $patterns2['sample_count'],
            ],
            'best_actions' => [
                $niche1 => $patterns1['best_performing_actions'] ?? [],
                $niche2 => $patterns2['best_performing_actions'] ?? [],
            ],
            'differences' => [],
        ];
        
        // Find action differences
        $all_actions = array_unique(array_merge(
            array_keys($patterns1['best_performing_actions'] ?? []),
            array_keys($patterns2['best_performing_actions'] ?? [])
        ));
        
        foreach ($all_actions as $action) {
            $score1 = $patterns1['best_performing_actions'][$action] ?? 0;
            $score2 = $patterns2['best_performing_actions'][$action] ?? 0;
            
            if (abs($score1 - $score2) > 20) {
                $comparison['differences'][] = [
                    'action' => $action,
                    'scores' => [$niche1 => $score1, $niche2 => $score2],
                    'difference' => abs($score1 - $score2),
                ];
            }
        }
        
        return $comparison;
    }
    
    /**
     * Get niche recommendations
     * 
     * @param string $niche Niche
     * @return array Recommendations
     */
    public function get_niche_recommendations($niche) {
        $patterns = $this->get_learned_niche_patterns($niche);
        
        if (!$patterns || $patterns['sample_count'] < self::MIN_SAMPLES) {
            return [
                'status' => 'insufficient_data',
                'message' => "Need at least " . self::MIN_SAMPLES . " samples to provide niche-specific recommendations",
                'current_samples' => $patterns['sample_count'] ?? 0,
            ];
        }
        
        $recommendations = [
            'status' => 'available',
            'confidence' => $patterns['confidence'],
            'recommendations' => [],
        ];
        
        // Recommend best performing actions
        if (isset($patterns['best_performing_actions'])) {
            foreach ($patterns['best_performing_actions'] as $action => $score) {
                if ($score > 50) {
                    $recommendations['recommendations'][] = [
                        'action' => $action,
                        'priority' => 'high',
                        'expected_score' => $score,
                        'reasoning' => sprintf(
                            '%s performs well in %s niche (avg score: %.1f)',
                            $action,
                            $niche,
                            $score
                        ),
                    ];
                }
            }
        }
        
        return $recommendations;
    }
}
