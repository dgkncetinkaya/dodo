<?php
/**
 * Brain Strategy Mapper
 * 
 * Maps Brain Core analysis output to Generation Strategy parameters
 * Bridges AI reasoning with content generation
 *
 * @package DODO_AI_SEO
 * @since 2.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Brain_Strategy_Mapper {
    
    /**
     * Debug mode
     */
    private $debug = true;
    
    /**
     * Map Brain analysis to Strategy parameters
     *
     * @param array $brain_analysis Full Brain Core analysis
     * @param array $user_params User-provided parameters
     * @return array Enhanced strategy parameters
     */
    public function map_to_strategy($brain_analysis, $user_params = []) {
        $start_time = microtime(true);
        
        if ($this->debug) {
            error_log('[DODO Brain Mapper] Starting Brain → Strategy mapping');
        }
        
        // Start with user params as base
        $strategy_params = $user_params;
        
        // Detect if user explicitly set controls (not just defaults)
        $user_explicit_controls = $this->detect_explicit_controls($user_params);
        
        if ($this->debug) {
            error_log('[DODO Brain Mapper] User explicit controls: ' . ($user_explicit_controls ? 'YES' : 'NO'));
        }
        
        // Apply Brain recommendations with smart override logic
        $strategy_params = $this->apply_intent_mapping($brain_analysis, $strategy_params, $user_explicit_controls);
        $strategy_params = $this->apply_geo_mapping($brain_analysis, $strategy_params, $user_explicit_controls);
        $strategy_params = $this->apply_content_strategy_mapping($brain_analysis, $strategy_params, $user_explicit_controls);
        $strategy_params = $this->apply_tone_mapping($brain_analysis, $strategy_params, $user_explicit_controls);
        $strategy_params = $this->apply_semantic_mapping($brain_analysis, $strategy_params, $user_explicit_controls);
        $strategy_params = $this->apply_readability_mapping($brain_analysis, $strategy_params, $user_explicit_controls);
        
        // Add Brain metadata
        $strategy_params['brain_enhanced'] = true;
        $strategy_params['brain_overall_score'] = $brain_analysis['overall_score'] ?? 0;
        $strategy_params['brain_recommendations'] = $brain_analysis['recommendations'] ?? [];
        $strategy_params['user_explicit_controls'] = $user_explicit_controls;
        
        $execution_time = round((microtime(true) - $start_time) * 1000, 2);
        
        if ($this->debug) {
            error_log(sprintf(
                '[DODO Brain Mapper] Mapping complete | Overall Score: %d | Time: %sms',
                $strategy_params['brain_overall_score'],
                $execution_time
            ));
            
            $this->log_mapping_decisions($brain_analysis, $user_params, $strategy_params);
        }
        
        return $strategy_params;
    }
    
    /**
     * Detect if user explicitly set controls (not just defaults)
     * 
     * @param array $params User parameters
     * @return bool True if user made explicit choices
     */
    private function detect_explicit_controls($params) {
        // Default values that come from frontend when user doesn't change anything
        $defaults = array(
            'content_intent' => 'informational',
            'expertise_depth' => 'intermediate',
            'geo_optimization' => 'moderate',
            'ai_naturalness' => 'human',
            'semantic_aggressiveness' => 'moderate',
            'readability_target' => 'easy',
            'tone' => 'technical',
            'length' => 'medium',
        );
        
        // Check if ANY parameter differs from default
        foreach ($defaults as $key => $default_value) {
            if (isset($params[$key]) && $params[$key] !== $default_value) {
                return true; // User changed at least one control
            }
        }
        
        return false; // All values are defaults
    }
    
    /**
     * Apply intent-based mapping
     */
    private function apply_intent_mapping($brain_analysis, $params, $user_explicit = false) {
        if (!isset($brain_analysis['intent']) || !isset($brain_analysis['intent']['primary_intent'])) {
            return $params;
        }
        
        $intent = $brain_analysis['intent']['primary_intent'];
        
        // Only override if user didn't explicitly set content_intent
        if (!$user_explicit && (!isset($params['content_intent']) || $params['content_intent'] === 'informational')) {
            $params['content_intent'] = $this->map_intent_to_content_intent($intent);
            
            if ($this->debug) {
                error_log("[DODO Brain Mapper] Intent mapped: {$intent} → {$params['content_intent']}");
            }
        } else {
            if ($this->debug) {
                error_log("[DODO Brain Mapper] Intent override skipped (user explicit choice: {$params['content_intent']})");
            }
        }
        
        return $params;
    }
    
    /**
     * Apply GEO-based mapping
     */
    private function apply_geo_mapping($brain_analysis, $params, $user_explicit = false) {
        if (!isset($brain_analysis['geo']) || !isset($brain_analysis['geo']['overall_score'])) {
            return $params;
        }
        
        $geo_score = $brain_analysis['geo']['overall_score'];
        
        // GEO optimization level based on score
        if (!$user_explicit && (!isset($params['geo_optimization']) || $params['geo_optimization'] === 'moderate')) {
            if ($geo_score >= 80) {
                $params['geo_optimization'] = 'aggressive';
            } elseif ($geo_score >= 60) {
                $params['geo_optimization'] = 'moderate';
            } else {
                $params['geo_optimization'] = 'conservative';
            }
            
            if ($this->debug) {
                error_log("[DODO Brain Mapper] GEO mapped: score {$geo_score} → {$params['geo_optimization']}");
            }
        }
        
        return $params;
    }
    
    /**
     * Apply content strategy mapping
     */
    private function apply_content_strategy_mapping($brain_analysis, $params, $user_explicit = false) {
        if (!isset($brain_analysis['content_strategy'])) {
            return $params;
        }
        
        $strategy = $brain_analysis['content_strategy'];
        
        // Map recommended length
        if (!$user_explicit && (!isset($params['length']) || $params['length'] === 'medium')) {
            $params['length'] = $strategy['structure']['recommended_length'];
            
            if ($this->debug) {
                error_log("[DODO Brain Mapper] Length mapped: {$params['length']}");
            }
        }
        
        // Map content type
        if (!$user_explicit && (!isset($params['content_type']) || $params['content_type'] === 'blog')) {
            $params['content_type'] = $this->map_format_to_content_type($strategy['recommended_format']);
            
            if ($this->debug) {
                error_log("[DODO Brain Mapper] Content type mapped: {$strategy['recommended_format']} → {$params['content_type']}");
            }
        }
        
        return $params;
    }
    
    /**
     * Apply tone mapping
     */
    private function apply_tone_mapping($brain_analysis, $params, $user_explicit = false) {
        if (!isset($brain_analysis['intent'])) {
            return $params;
        }
        
        $recommended_tone = $brain_analysis['intent']['recommended_tone'] ?? 'neutral';
        
        // Only apply if user didn't set tone
        if (!$user_explicit && (!isset($params['tone']) || $params['tone'] === 'technical')) {
            $params['tone'] = $this->map_brain_tone_to_strategy_tone($recommended_tone);
            
            if ($this->debug) {
                error_log("[DODO Brain Mapper] Tone mapped: {$recommended_tone} → {$params['tone']}");
            }
        } else {
            if ($this->debug) {
                error_log("[DODO Brain Mapper] Tone override skipped (user choice: {$params['tone']})");
            }
        }
        
        return $params;
    }
    
    /**
     * Apply semantic density mapping
     */
    private function apply_semantic_mapping($brain_analysis, $params, $user_explicit = false) {
        if (!isset($brain_analysis['keyword_intelligence'])) {
            return $params;
        }
        
        $semantic_richness = $brain_analysis['keyword_intelligence']['semantic_richness'];
        
        // Only apply if user didn't set semantic aggressiveness
        if (!$user_explicit && (!isset($params['semantic_aggressiveness']) || $params['semantic_aggressiveness'] === 'moderate')) {
            if ($semantic_richness >= 80) {
                $params['semantic_aggressiveness'] = 'aggressive';
            } elseif ($semantic_richness >= 60) {
                $params['semantic_aggressiveness'] = 'moderate';
            } else {
                $params['semantic_aggressiveness'] = 'conservative';
            }
            
            if ($this->debug) {
                error_log("[DODO Brain Mapper] Semantic mapped: richness {$semantic_richness} → {$params['semantic_aggressiveness']}");
            }
        }
        
        return $params;
    }
    
    /**
     * Apply readability mapping
     */
    private function apply_readability_mapping($brain_analysis, $params, $user_explicit = false) {
        if (!isset($brain_analysis['intent']) || !isset($brain_analysis['intent']['primary_intent'])) {
            return $params;
        }
        
        $intent = $brain_analysis['intent']['primary_intent'];
        
        // Only apply if user didn't set readability
        if (!$user_explicit && (!isset($params['readability_target']) || $params['readability_target'] === 'easy')) {
            // Informational = easier to read, Technical = more complex
            if ($intent === 'informational') {
                $params['readability_target'] = 'easy';
            } elseif ($intent === 'transactional' || $intent === 'commercial') {
                $params['readability_target'] = 'moderate';
            } else {
                $params['readability_target'] = 'moderate';
            }
            
            if ($this->debug) {
                error_log("[DODO Brain Mapper] Readability mapped: intent {$intent} → {$params['readability_target']}");
            }
        }
        
        return $params;
    }
    
    /**
     * Map Brain intent to Strategy content_intent
     */
    private function map_intent_to_content_intent($brain_intent) {
        $mapping = [
            'informational' => 'informational',
            'transactional' => 'transactional',
            'commercial' => 'commercial',
            'navigational' => 'informational',
            'local' => 'commercial',
        ];
        
        return $mapping[$brain_intent] ?? 'informational';
    }
    
    /**
     * Map Brain format to Strategy content_type
     */
    private function map_format_to_content_type($brain_format) {
        $mapping = [
            'blog_post' => 'blog',
            'landing_page' => 'landing',
            'comparison' => 'comparison',
            'glossary' => 'glossary',
            'pillar_content' => 'pillar',
            'listicle' => 'listicle',
            'how_to_guide' => 'howto',
            'review' => 'review',
            'faq_page' => 'faq',
        ];
        
        return $mapping[$brain_format] ?? 'blog';
    }
    
    /**
     * Map Brain tone to Strategy tone
     */
    private function map_brain_tone_to_strategy_tone($brain_tone) {
        $mapping = [
            'technical' => 'technical',
            'conversational' => 'conversational',
            'persuasive' => 'persuasive',
            'educational' => 'technical',
            'objective' => 'technical',
            'engaging' => 'conversational',
            'instructional' => 'technical',
            'balanced' => 'conversational',
            'authoritative' => 'technical',
            'neutral' => 'conversational',
        ];
        
        return $mapping[$brain_tone] ?? 'conversational';
    }
    
    /**
     * Get cannibalization warning for UI
     */
    public function get_cannibalization_warning($brain_analysis) {
        if (!isset($brain_analysis['cannibalization'])) {
            return null;
        }
        
        $risk_score = $brain_analysis['cannibalization']['risk_score'];
        
        if ($risk_score < 50) {
            return null; // No warning needed
        }
        
        $recommendation = $brain_analysis['cannibalization']['recommendation'];
        $related_posts = $brain_analysis['cannibalization']['related_posts'];
        
        return [
            'risk_level' => $risk_score >= 70 ? 'high' : 'medium',
            'risk_score' => $risk_score,
            'recommendation' => $recommendation,
            'message' => $this->build_cannibalization_message($risk_score, $recommendation, $related_posts),
            'related_posts' => $related_posts,
            'allow_override' => true,
        ];
    }
    
    /**
     * Build cannibalization warning message
     */
    private function build_cannibalization_message($risk_score, $recommendation, $related_posts) {
        $post_count = count($related_posts);
        
        if ($risk_score >= 70) {
            return sprintf(
                'YÜKSEK RİSK: Bu keyword için %d benzer içerik mevcut. Öneri: %s',
                $post_count,
                $this->translate_recommendation($recommendation)
            );
        } else {
            return sprintf(
                'ORTA RİSK: Bu keyword için %d benzer içerik bulundu. Öneri: %s',
                $post_count,
                $this->translate_recommendation($recommendation)
            );
        }
    }
    
    /**
     * Translate recommendation to Turkish
     */
    private function translate_recommendation($recommendation) {
        $translations = [
            'create_new' => 'Yeni içerik oluştur',
            'update_existing' => 'Mevcut içeriği güncelle',
            'merge_existing' => 'Mevcut içerikleri birleştir',
            'create_differentiated' => 'Farklı açıdan yeni içerik oluştur',
        ];
        
        return $translations[$recommendation] ?? $recommendation;
    }
    
    /**
     * Get link intelligence suggestions
     */
    public function get_link_suggestions($brain_analysis, $post_id = null) {
        if (!$post_id || !isset($brain_analysis['link_intelligence'])) {
            return [];
        }
        
        $link_intel = $brain_analysis['link_intelligence'];
        
        return [
            'health_score' => $link_intel['health_score'],
            'opportunities' => $link_intel['opportunities'] ?? [],
            'orphan_warning' => $link_intel['orphan_status']['is_orphan'] ?? false,
            'recommendations' => $link_intel['recommendations'] ?? [],
        ];
    }
    
    /**
     * Generate AI Strategy Report
     */
    public function generate_strategy_report($brain_analysis, $user_params, $final_params) {
        $report = [
            'timestamp' => current_time('mysql'),
            'brain_score' => $brain_analysis['overall_score'] ?? 0,
            'decisions' => [],
        ];
        
        // Intent decision
        if (isset($brain_analysis['intent']) && isset($brain_analysis['intent']['primary_intent'])) {
            $report['decisions'][] = [
                'parameter' => 'Content Intent',
                'brain_recommendation' => $brain_analysis['intent']['primary_intent'] ?? 'unknown',
                'user_choice' => $user_params['content_intent'] ?? 'not set',
                'final_value' => $final_params['content_intent'] ?? 'informational',
                'reason' => $this->explain_intent_decision($brain_analysis['intent']),
            ];
        }
        
        // GEO decision
        if (isset($brain_analysis['geo']) && isset($brain_analysis['geo']['overall_score'])) {
            $report['decisions'][] = [
                'parameter' => 'GEO Optimization',
                'brain_recommendation' => $this->get_geo_recommendation($brain_analysis['geo']['overall_score']),
                'user_choice' => $user_params['geo_optimization'] ?? 'not set',
                'final_value' => $final_params['geo_optimization'] ?? 'moderate',
                'reason' => sprintf('GEO score: %d/100', $brain_analysis['geo']['overall_score']),
            ];
        }
        
        // Tone decision
        if (isset($brain_analysis['intent']['recommended_tone'])) {
            $report['decisions'][] = [
                'parameter' => 'Tone',
                'brain_recommendation' => $brain_analysis['intent']['recommended_tone'],
                'user_choice' => $user_params['tone'] ?? 'not set',
                'final_value' => $final_params['tone'] ?? 'technical',
                'reason' => 'Based on search intent and content type',
            ];
        }
        
        // Length decision
        if (isset($brain_analysis['content_strategy']['structure']['recommended_length'])) {
            $report['decisions'][] = [
                'parameter' => 'Content Length',
                'brain_recommendation' => $brain_analysis['content_strategy']['structure']['recommended_length'],
                'user_choice' => $user_params['length'] ?? 'not set',
                'final_value' => $final_params['length'] ?? 'medium',
                'reason' => sprintf('Format: %s', $brain_analysis['content_strategy']['recommended_format'] ?? 'blog_post'),
            ];
        }
        
        return $report;
    }
    
    /**
     * Explain intent decision
     */
    private function explain_intent_decision($intent_analysis) {
        $primary_intent = $intent_analysis['primary_intent'] ?? 'unknown';
        $confidence = $intent_analysis['confidence'] ?? 0;
        
        return sprintf(
            'Detected %s intent with %d%% confidence',
            $primary_intent,
            $confidence
        );
    }
    
    /**
     * Get GEO recommendation text
     */
    private function get_geo_recommendation($geo_score) {
        if ($geo_score >= 80) {
            return 'aggressive';
        } elseif ($geo_score >= 60) {
            return 'moderate';
        } else {
            return 'conservative';
        }
    }
    
    /**
     * Log mapping decisions for debugging
     */
    private function log_mapping_decisions($brain_analysis, $user_params, $final_params) {
        $decisions = [];
        
        // Compare user vs brain vs final for key parameters
        $key_params = ['content_intent', 'tone', 'length', 'geo_optimization', 'semantic_aggressiveness'];
        
        foreach ($key_params as $param) {
            $user_value = $user_params[$param] ?? 'not set';
            $final_value = $final_params[$param] ?? 'not set';
            
            if ($user_value !== 'not set' && $user_value !== $final_value) {
                $decisions[] = sprintf('%s: USER OVERRIDE (%s)', $param, $user_value);
            } elseif ($user_value === 'not set' && $final_value !== 'not set') {
                $decisions[] = sprintf('%s: BRAIN APPLIED (%s)', $param, $final_value);
            }
        }
        
        if (!empty($decisions)) {
            error_log('[DODO Brain Mapper] Decisions: ' . implode(' | ', $decisions));
        }
    }
}
