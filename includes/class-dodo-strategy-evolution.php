<?php
/**
 * Strategy Evolution System
 * 
 * Adapts generation strategies based on learned outcomes
 * Evolves from static thresholds to adaptive intelligence
 * 
 * @package DODO_AI_SEO
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Strategy_Evolution {
    
    /**
     * Evolution rate (how fast strategies adapt)
     */
    const EVOLUTION_RATE = 0.05; // 5% per successful outcome
    const MAX_EVOLUTION = 0.3; // Max 30% deviation from baseline
    
    /**
     * Minimum samples before evolution
     */
    const MIN_SAMPLES_FOR_EVOLUTION = 10;
    
    /**
     * Feedback engine
     */
    private $feedback_engine;
    
    /**
     * Constructor
     */
    public function __construct() {
        require_once plugin_dir_path(__FILE__) . 'class-dodo-feedback-engine.php';
        $this->feedback_engine = new DODO_Feedback_Engine();
    }
    
    /**
     * Get evolved strategy parameters
     * 
     * Returns adapted parameters based on learned outcomes
     * 
     * @param array $base_params Base strategy parameters
     * @param array $context Context (intent, niche, etc.)
     * @return array Evolved parameters
     */
    public function get_evolved_strategy($base_params, $context = []) {
        error_log('[DODO Evolution] Getting evolved strategy for context: ' . json_encode($context));
        
        $evolved = $base_params;
        
        // Evolve FAQ strategy
        $evolved = $this->evolve_faq_strategy($evolved, $context);
        
        // Evolve semantic density
        $evolved = $this->evolve_semantic_density($evolved, $context);
        
        // Evolve CTA strategy
        $evolved = $this->evolve_cta_strategy($evolved, $context);
        
        // Evolve title strategy
        $evolved = $this->evolve_title_strategy($evolved, $context);
        
        // Evolve content length
        $evolved = $this->evolve_content_length($evolved, $context);
        
        // Add evolution metadata
        $evolved['evolution_applied'] = true;
        $evolved['evolution_context'] = $context;
        $evolved['evolution_timestamp'] = current_time('mysql');
        
        return $evolved;
    }
    
    /**
     * Evolve FAQ strategy
     */
    private function evolve_faq_strategy($params, $context) {
        $learned = $this->get_learned_pattern('faq_optimization', $context);
        
        if (!$learned || $learned['confidence'] < DODO_Feedback_Engine::MEDIUM_CONFIDENCE) {
            return $params; // Not enough data
        }
        
        // Learn optimal FAQ count
        if ($learned['avg_score'] > 50) {
            // FAQ optimization works well in this context
            $current_count = $params['faq_count'] ?? 5;
            
            // Increase FAQ count gradually
            $evolved_count = round($current_count * (1 + self::EVOLUTION_RATE));
            $evolved_count = min(10, max(3, $evolved_count)); // Cap between 3-10
            
            $params['faq_count'] = $evolved_count;
            $params['faq_enabled'] = true;
            
            error_log(sprintf(
                '[DODO Evolution] FAQ count evolved: %d → %d (score: %.1f)',
                $current_count,
                $evolved_count,
                $learned['avg_score']
            ));
        } elseif ($learned['avg_score'] < -20) {
            // FAQ optimization performs poorly
            $current_count = $params['faq_count'] ?? 5;
            $evolved_count = round($current_count * (1 - self::EVOLUTION_RATE));
            $evolved_count = max(3, $evolved_count);
            
            $params['faq_count'] = $evolved_count;
            
            error_log(sprintf(
                '[DODO Evolution] FAQ count reduced: %d → %d (poor performance)',
                $current_count,
                $evolved_count
            ));
        }
        
        return $params;
    }
    
    /**
     * Evolve semantic density
     */
    private function evolve_semantic_density($params, $context) {
        $learned = $this->get_learned_pattern('semantic_expansion', $context);
        
        if (!$learned || $learned['confidence'] < DODO_Feedback_Engine::MEDIUM_CONFIDENCE) {
            return $params;
        }
        
        $current_density = $params['semantic_density'] ?? 'medium';
        $density_map = ['low' => 1, 'medium' => 2, 'high' => 3, 'very_high' => 4];
        $reverse_map = [1 => 'low', 2 => 'medium', 3 => 'high', 4 => 'very_high'];
        
        $current_level = $density_map[$current_density] ?? 2;
        
        if ($learned['avg_score'] > 50) {
            // Semantic expansion works - increase density
            $evolved_level = min(4, $current_level + 1);
        } elseif ($learned['avg_score'] < -20) {
            // Semantic expansion hurts - decrease density
            $evolved_level = max(1, $current_level - 1);
        } else {
            $evolved_level = $current_level;
        }
        
        if ($evolved_level !== $current_level) {
            $params['semantic_density'] = $reverse_map[$evolved_level];
            
            error_log(sprintf(
                '[DODO Evolution] Semantic density evolved: %s → %s',
                $current_density,
                $params['semantic_density']
            ));
        }
        
        return $params;
    }
    
    /**
     * Evolve CTA strategy
     */
    private function evolve_cta_strategy($params, $context) {
        // Learn from transactional content performance
        if (isset($context['intent']) && $context['intent'] === 'transactional') {
            $learned = $this->get_learned_pattern('cta_optimization', $context);
            
            if ($learned && $learned['confidence'] >= DODO_Feedback_Engine::MEDIUM_CONFIDENCE) {
                $current_density = $params['cta_density'] ?? 'medium';
                
                if ($learned['avg_score'] > 50) {
                    // Aggressive CTA works for transactional
                    $params['cta_density'] = 'high';
                    $params['cta_style'] = 'aggressive';
                    
                    error_log('[DODO Evolution] CTA strategy evolved to aggressive');
                } elseif ($learned['avg_score'] < -20) {
                    // Tone down CTA
                    $params['cta_density'] = 'medium';
                    $params['cta_style'] = 'balanced';
                    
                    error_log('[DODO Evolution] CTA strategy evolved to balanced');
                }
            }
        }
        
        return $params;
    }
    
    /**
     * Evolve title strategy
     */
    private function evolve_title_strategy($params, $context) {
        $learned = $this->get_learned_pattern('title_rewrite', $context);
        
        if (!$learned || $learned['confidence'] < DODO_Feedback_Engine::HIGH_CONFIDENCE) {
            return $params;
        }
        
        // Learn optimal title patterns
        $title_patterns = $this->analyze_successful_title_patterns($context);
        
        if (!empty($title_patterns)) {
            $params['title_patterns'] = $title_patterns;
            $params['title_optimization_enabled'] = true;
            
            error_log(sprintf(
                '[DODO Evolution] Title patterns learned: %d patterns',
                count($title_patterns)
            ));
        }
        
        return $params;
    }
    
    /**
     * Evolve content length
     */
    private function evolve_content_length($params, $context) {
        $learned = $this->get_learned_pattern('content_refresh', $context);
        
        if (!$learned || $learned['confidence'] < DODO_Feedback_Engine::MEDIUM_CONFIDENCE) {
            return $params;
        }
        
        // Analyze optimal content length for this context
        $optimal_length = $this->calculate_optimal_length($context);
        
        if ($optimal_length) {
            $current_length = $params['target_word_count'] ?? 1500;
            
            // Gradual evolution towards optimal
            $evolved_length = round(
                $current_length + (($optimal_length - $current_length) * self::EVOLUTION_RATE)
            );
            
            // Cap evolution
            $max_change = $current_length * self::MAX_EVOLUTION;
            $evolved_length = max(
                $current_length - $max_change,
                min($current_length + $max_change, $evolved_length)
            );
            
            $params['target_word_count'] = $evolved_length;
            
            error_log(sprintf(
                '[DODO Evolution] Content length evolved: %d → %d words',
                $current_length,
                $evolved_length
            ));
        }
        
        return $params;
    }
    
    /**
     * Get learned pattern from feedback engine
     */
    private function get_learned_pattern($action_type, $context) {
        return $this->feedback_engine->get_recommendation($action_type, $context);
    }
    
    /**
     * Analyze successful title patterns
     */
    private function analyze_successful_title_patterns($context) {
        global $wpdb;
        
        // Get successful title rewrites
        $impact_table = $wpdb->prefix . 'dodo_impact_tracking';
        
        $successful_titles = $wpdb->get_results($wpdb->prepare(
            "SELECT action_details, impact_analysis 
            FROM {$impact_table}
            WHERE action_type = 'title_rewrite'
            AND status = 'completed'
            AND JSON_EXTRACT(impact_analysis, '$.overall_score') > 50
            AND JSON_EXTRACT(impact_analysis, '$.confidence') >= %d
            ORDER BY JSON_EXTRACT(impact_analysis, '$.overall_score') DESC
            LIMIT 20",
            DODO_Feedback_Engine::MEDIUM_CONFIDENCE
        ), ARRAY_A);
        
        if (empty($successful_titles)) {
            return [];
        }
        
        $patterns = [];
        
        foreach ($successful_titles as $row) {
            $details = json_decode($row['action_details'], true);
            
            if (isset($details['new_title'])) {
                $title = $details['new_title'];
                
                // Extract patterns
                $patterns[] = [
                    'has_number' => preg_match('/\d+/', $title) ? true : false,
                    'has_year' => preg_match('/20\d{2}/', $title) ? true : false,
                    'has_question' => preg_match('/\?$/', $title) ? true : false,
                    'has_brackets' => preg_match('/[\[\(]/', $title) ? true : false,
                    'length' => mb_strlen($title, 'UTF-8'),
                    'word_count' => str_word_count($title),
                ];
            }
        }
        
        // Aggregate patterns
        if (!empty($patterns)) {
            $aggregated = [
                'number_usage_rate' => $this->calculate_rate($patterns, 'has_number'),
                'year_usage_rate' => $this->calculate_rate($patterns, 'has_year'),
                'question_usage_rate' => $this->calculate_rate($patterns, 'has_question'),
                'bracket_usage_rate' => $this->calculate_rate($patterns, 'has_brackets'),
                'avg_length' => round(array_sum(array_column($patterns, 'length')) / count($patterns)),
                'avg_word_count' => round(array_sum(array_column($patterns, 'word_count')) / count($patterns)),
            ];
            
            return $aggregated;
        }
        
        return [];
    }
    
    /**
     * Calculate rate for boolean pattern
     */
    private function calculate_rate($patterns, $key) {
        $count = array_sum(array_map(function($p) use ($key) {
            return $p[$key] ? 1 : 0;
        }, $patterns));
        
        return round(($count / count($patterns)) * 100, 1);
    }
    
    /**
     * Calculate optimal content length
     */
    private function calculate_optimal_length($context) {
        global $wpdb;
        
        $impact_table = $wpdb->prefix . 'dodo_impact_tracking';
        
        // Get successful content refreshes
        $successful_content = $wpdb->get_results($wpdb->prepare(
            "SELECT action_details, impact_analysis 
            FROM {$impact_table}
            WHERE action_type = 'content_refresh'
            AND status = 'completed'
            AND JSON_EXTRACT(impact_analysis, '$.overall_score') > 50
            AND JSON_EXTRACT(impact_analysis, '$.confidence') >= %d
            LIMIT 20",
            DODO_Feedback_Engine::MEDIUM_CONFIDENCE
        ), ARRAY_A);
        
        if (empty($successful_content)) {
            return null;
        }
        
        $lengths = [];
        
        foreach ($successful_content as $row) {
            $details = json_decode($row['action_details'], true);
            
            if (isset($details['word_count'])) {
                $lengths[] = $details['word_count'];
            }
        }
        
        if (!empty($lengths)) {
            return round(array_sum($lengths) / count($lengths));
        }
        
        return null;
    }
    
    /**
     * Get evolution insights
     * 
     * @return array Evolution insights
     */
    public function get_evolution_insights() {
        $insights = [
            'evolved_strategies' => [],
            'evolution_history' => [],
            'performance_improvements' => [],
        ];
        
        // Get evolved strategies from options
        global $wpdb;
        
        $evolved_options = $wpdb->get_results(
            "SELECT option_name, option_value 
            FROM {$wpdb->options} 
            WHERE option_name LIKE 'dodo_evolved_strategy_%'",
            ARRAY_A
        );
        
        foreach ($evolved_options as $option) {
            $strategy = maybe_unserialize($option['option_value']);
            if ($strategy) {
                $insights['evolved_strategies'][] = $strategy;
            }
        }
        
        // Get evolution history
        $learning_table = $wpdb->prefix . 'dodo_learning_events';
        
        $insights['evolution_history'] = $wpdb->get_results(
            "SELECT 
                action_type,
                AVG(outcome_score) as avg_score,
                COUNT(*) as evolution_count,
                MAX(recorded_at) as last_evolution
            FROM {$learning_table}
            WHERE confidence >= " . DODO_Feedback_Engine::MEDIUM_CONFIDENCE . "
            GROUP BY action_type
            ORDER BY last_evolution DESC
            LIMIT 10",
            ARRAY_A
        );
        
        return $insights;
    }
    
    /**
     * Rollback evolution
     * 
     * Reverts to baseline strategy if evolution performs poorly
     * 
     * @param string $strategy_id Strategy ID
     * @return bool Success
     */
    public function rollback_evolution($strategy_id) {
        $option_key = "dodo_evolved_strategy_{$strategy_id}";
        
        $evolved = get_option($option_key);
        
        if (!$evolved) {
            return false;
        }
        
        // Store rollback event
        global $wpdb;
        $learning_table = $wpdb->prefix . 'dodo_learning_events';
        
        $wpdb->insert(
            $learning_table,
            [
                'event_type' => 'evolution_rollback',
                'action_type' => $evolved['action_type'] ?? 'unknown',
                'outcome_score' => -100, // Negative score for rollback
                'confidence' => 100,
                'context' => json_encode(['strategy_id' => $strategy_id]),
                'impact_details' => json_encode($evolved),
                'recorded_at' => current_time('mysql'),
            ],
            ['%s', '%s', '%d', '%d', '%s', '%s', '%s']
        );
        
        // Delete evolved strategy
        delete_option($option_key);
        
        error_log("[DODO Evolution] Rolled back strategy: {$strategy_id}");
        
        return true;
    }
    
    /**
     * Validate evolution safety
     * 
     * Checks if evolution is safe to apply
     * 
     * @param array $base_params Base parameters
     * @param array $evolved_params Evolved parameters
     * @return array Validation result
     */
    public function validate_evolution_safety($base_params, $evolved_params) {
        $validation = [
            'safe' => true,
            'warnings' => [],
            'changes' => [],
        ];
        
        // Check for excessive changes
        foreach ($evolved_params as $key => $evolved_value) {
            if (!isset($base_params[$key])) {
                continue;
            }
            
            $base_value = $base_params[$key];
            
            // Numeric comparison
            if (is_numeric($base_value) && is_numeric($evolved_value)) {
                $change_pct = $base_value > 0 
                    ? abs(($evolved_value - $base_value) / $base_value) * 100 
                    : 0;
                
                if ($change_pct > (self::MAX_EVOLUTION * 100)) {
                    $validation['safe'] = false;
                    $validation['warnings'][] = sprintf(
                        'Parameter %s changed by %.1f%% (max allowed: %.1f%%)',
                        $key,
                        $change_pct,
                        self::MAX_EVOLUTION * 100
                    );
                }
                
                if ($change_pct > 5) {
                    $validation['changes'][] = [
                        'parameter' => $key,
                        'from' => $base_value,
                        'to' => $evolved_value,
                        'change_pct' => round($change_pct, 1),
                    ];
                }
            }
        }
        
        return $validation;
    }
}
