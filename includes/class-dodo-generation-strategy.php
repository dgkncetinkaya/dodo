<?php
/**
 * Generation Strategy Class
 * 
 * Converts advanced settings into actionable generation parameters
 * 
 * @package DODO_AI_SEO
 * @since 2.3.0
 */

// Doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

class DODO_Generation_Strategy {
    
    /**
     * Build strategy object from user parameters
     * 
     * @param array $params User input parameters
     * @return array Strategy object
     */
    public function build_strategy($params) {
        // Normalize values from settings/frontend to strategy-compatible values
        $params['tone'] = $this->normalize_tone($params['tone'] ?? 'technical');
        $params['geo_optimization'] = $this->normalize_geo($params['geo_optimization'] ?? 'moderate');
        $params['ai_naturalness'] = $this->normalize_naturalness($params['ai_naturalness'] ?? 'human');
        $params['readability_target'] = $this->normalize_readability($params['readability_target'] ?? 'easy');
        
        $strategy = array(
            // Core parameters
            'focus_keyword' => $params['focus_keyword'] ?? '',
            'topic' => $params['topic'] ?? '',
            
            // Length strategy
            'length' => $params['length'] ?? 'medium',
            'target_words' => $this->calculate_target_words($params['length'] ?? 'medium'),
            'min_words' => $this->calculate_min_words($params['length'] ?? 'medium'),
            'section_count' => $this->calculate_section_count($params['length'] ?? 'medium'),
            'h2_count' => $this->calculate_h2_count($params['length'] ?? 'medium'),
            'h3_count' => $this->calculate_h3_count($params['length'] ?? 'medium'),
            'faq_count' => $this->calculate_faq_count($params['length'] ?? 'medium'),
            'intro_length' => $this->calculate_intro_length($params['length'] ?? 'medium'),
            'conclusion_length' => $this->calculate_conclusion_length($params['length'] ?? 'medium'),
            'section_word_min' => $this->calculate_section_word_min($params['length'] ?? 'medium'),
            'section_word_max' => $this->calculate_section_word_max($params['length'] ?? 'medium'),
            
            // Tone strategy
            'tone' => $params['tone'] ?? 'technical',
            'sentence_structure' => $this->get_sentence_structure($params['tone'] ?? 'technical'),
            'cta_density' => $this->get_cta_density($params['tone'] ?? 'technical'),
            'language_style' => $this->get_language_style($params['tone'] ?? 'technical'),
            
            // Content intent strategy
            'content_intent' => $params['content_intent'] ?? 'informational',
            'intent_focus' => $this->get_intent_focus($params['content_intent'] ?? 'informational'),
            'conversion_elements' => $this->get_conversion_elements($params['content_intent'] ?? 'informational'),
            
            // Expertise depth strategy
            'expertise_depth' => $params['expertise_depth'] ?? 'intermediate',
            'technical_level' => $this->get_technical_level($params['expertise_depth'] ?? 'intermediate'),
            'jargon_usage' => $this->get_jargon_usage($params['expertise_depth'] ?? 'intermediate'),
            'explanation_depth' => $this->get_explanation_depth($params['expertise_depth'] ?? 'intermediate'),
            
            // GEO optimization strategy
            'geo_optimization' => $params['geo_optimization'] ?? 'moderate',
            'answer_blocks_enabled' => $this->should_enable_answer_blocks($params['geo_optimization'] ?? 'moderate'),
            'featured_snippet_optimization' => $this->get_featured_snippet_level($params['geo_optimization'] ?? 'moderate'),
            'conversational_queries' => $this->get_conversational_query_count($params['geo_optimization'] ?? 'moderate'),
            'table_usage' => $this->should_use_tables($params['geo_optimization'] ?? 'moderate'),
            
            // AI naturalness strategy
            'ai_naturalness' => $params['ai_naturalness'] ?? 'human',
            'repetition_avoidance' => $this->get_repetition_avoidance($params['ai_naturalness'] ?? 'human'),
            'transition_quality' => $this->get_transition_quality($params['ai_naturalness'] ?? 'human'),
            'rhythm_variation' => $this->get_rhythm_variation($params['ai_naturalness'] ?? 'human'),
            'ai_pattern_removal' => $this->get_ai_pattern_removal($params['ai_naturalness'] ?? 'human'),
            
            // Semantic density strategy
            'semantic_aggressiveness' => $params['semantic_aggressiveness'] ?? 'moderate',
            'entity_count' => $this->calculate_entity_count($params['semantic_aggressiveness'] ?? 'moderate'),
            'lsi_keyword_density' => $this->get_lsi_density($params['semantic_aggressiveness'] ?? 'moderate'),
            'subtopic_coverage' => $this->get_subtopic_coverage($params['semantic_aggressiveness'] ?? 'moderate'),
            
            // Readability strategy
            'readability_target' => $params['readability_target'] ?? 'easy',
            'paragraph_length' => $this->get_paragraph_length($params['readability_target'] ?? 'easy'),
            'sentence_length' => $this->get_sentence_length($params['readability_target'] ?? 'easy'),
            'heading_frequency' => $this->get_heading_frequency($params['readability_target'] ?? 'easy'),
            'complexity_level' => $this->get_complexity_level($params['readability_target'] ?? 'easy'),
            
            // Publishing strategy
            'publish_mode' => $params['publish_mode'] ?? 'draft',
            'category_id' => $params['category_id'] ?? 0,
            'content_type' => $params['content_type'] ?? 'blog',
            
            // Metadata
            'created_at' => current_time('mysql'),
            'version' => '2.3.0',
        );
        
        // Log strategy for debugging
        $this->log_strategy($strategy);
        
        return $strategy;
    }
    
    /**
     * Calculate target words based on length
     */
    private function calculate_target_words($length) {
        $profiles = array(
            'short' => 800,
            'medium' => 1500,
            'long' => 2500,
            'very_long' => 3500,
            'comprehensive' => 4000,
            'authority' => 5000, // NEW: Authority content 4000-6000 words
        );
        
        return $profiles[$length] ?? 1500;
    }
    
    /**
     * Calculate minimum words based on length
     */
    private function calculate_min_words($length) {
        $profiles = array(
            'short' => 600,
            'medium' => 1200,
            'long' => 2000,
            'very_long' => 3000,
            'comprehensive' => 3500,
            'authority' => 4000, // NEW: Authority minimum 4000 words
        );
        
        return $profiles[$length] ?? 1200;
    }
    
    /**
     * Calculate section count based on length
     */
    private function calculate_section_count($length) {
        $profiles = array(
            'short' => 4,
            'medium' => 6,
            'long' => 8,
            'very_long' => 10,
            'comprehensive' => 12,
            'authority' => 15, // NEW: Authority 15 sections
        );
        
        return $profiles[$length] ?? 6;
    }
    
    /**
     * Calculate H2 count based on length
     */
    private function calculate_h2_count($length) {
        $profiles = array(
            'short' => 4,
            'medium' => 6,
            'long' => 8,
            'very_long' => 10,
            'comprehensive' => 12,
            'authority' => 15, // NEW: Authority 15 H2
        );
        
        return $profiles[$length] ?? 6;
    }
    
    /**
     * Calculate H3 count based on length
     */
    private function calculate_h3_count($length) {
        $profiles = array(
            'short' => 6,
            'medium' => 10,
            'long' => 15,
            'very_long' => 20,
            'comprehensive' => 25,
            'authority' => 30, // NEW: Authority 30 H3
        );
        
        return $profiles[$length] ?? 10;
    }
    
    /**
     * Calculate FAQ count based on length
     */
    private function calculate_faq_count($length) {
        $profiles = array(
            'short' => 3,
            'medium' => 5,
            'long' => 7,
            'very_long' => 9,
            'comprehensive' => 10,
            'authority' => 12, // NEW: Authority 12 FAQ
        );
        
        return $profiles[$length] ?? 5;
    }
    
    /**
     * Get sentence structure based on tone
     */
    private function get_sentence_structure($tone) {
        $structures = array(
            'technical' => 'complex_formal',
            'conversational' => 'simple_casual',
            'professional' => 'balanced_formal',
            'persuasive' => 'dynamic_engaging',
        );
        
        return $structures[$tone] ?? 'balanced_formal';
    }
    
    /**
     * Get CTA density based on tone
     */
    private function get_cta_density($tone) {
        $densities = array(
            'technical' => 'low',
            'conversational' => 'medium',
            'professional' => 'medium',
            'persuasive' => 'high',
        );
        
        return $densities[$tone] ?? 'medium';
    }
    
    /**
     * Get language style based on tone
     */
    private function get_language_style($tone) {
        $styles = array(
            'technical' => 'expert_analytical',
            'conversational' => 'friendly_accessible',
            'professional' => 'authoritative_clear',
            'persuasive' => 'compelling_action_oriented',
        );
        
        return $styles[$tone] ?? 'authoritative_clear';
    }
    
    /**
     * Get intent focus based on content intent
     */
    private function get_intent_focus($intent) {
        $focuses = array(
            'informational' => 'education_guidance',
            'commercial' => 'comparison_evaluation',
            'transactional' => 'conversion_action',
        );
        
        return $focuses[$intent] ?? 'education_guidance';
    }
    
    /**
     * Get conversion elements based on content intent
     */
    private function get_conversion_elements($intent) {
        $elements = array(
            'informational' => array('resource_links', 'related_topics'),
            'commercial' => array('comparison_tables', 'pros_cons', 'buying_guides'),
            'transactional' => array('cta_buttons', 'product_highlights', 'urgency_elements'),
        );
        
        return $elements[$intent] ?? array('resource_links');
    }
    
    /**
     * Get technical level based on expertise depth
     */
    private function get_technical_level($depth) {
        $levels = array(
            'beginner' => 'basic_concepts',
            'intermediate' => 'practical_application',
            'advanced' => 'deep_technical',
            'expert' => 'cutting_edge_research',
        );
        
        return $levels[$depth] ?? 'practical_application';
    }
    
    /**
     * Get jargon usage based on expertise depth
     */
    private function get_jargon_usage($depth) {
        $usage = array(
            'beginner' => 'minimal_explained',
            'intermediate' => 'moderate_contextual',
            'advanced' => 'frequent_assumed',
            'expert' => 'extensive_specialized',
        );
        
        return $usage[$depth] ?? 'moderate_contextual';
    }
    
    /**
     * Get explanation depth based on expertise depth
     */
    private function get_explanation_depth($depth) {
        $depths = array(
            'beginner' => 'detailed_step_by_step',
            'intermediate' => 'balanced_practical',
            'advanced' => 'concise_technical',
            'expert' => 'minimal_assumed_knowledge',
        );
        
        return $depths[$depth] ?? 'balanced_practical';
    }
    
    /**
     * Should enable answer blocks based on GEO level
     */
    private function should_enable_answer_blocks($geo_level) {
        $enabled = array(
            'minimal' => false,
            'moderate' => true,
            'aggressive' => true,
        );
        
        return $enabled[$geo_level] ?? true;
    }
    
    /**
     * Get featured snippet optimization level
     */
    private function get_featured_snippet_level($geo_level) {
        $levels = array(
            'minimal' => 'basic',
            'moderate' => 'optimized',
            'aggressive' => 'maximum',
        );
        
        return $levels[$geo_level] ?? 'optimized';
    }
    
    /**
     * Get conversational query count
     */
    private function get_conversational_query_count($geo_level) {
        $counts = array(
            'minimal' => 2,
            'moderate' => 4,
            'aggressive' => 6,
        );
        
        return $counts[$geo_level] ?? 4;
    }
    
    /**
     * Should use tables based on GEO level
     */
    private function should_use_tables($geo_level) {
        $use_tables = array(
            'minimal' => false,
            'moderate' => true,
            'aggressive' => true,
        );
        
        return $use_tables[$geo_level] ?? true;
    }
    
    /**
     * Get repetition avoidance level
     */
    private function get_repetition_avoidance($naturalness) {
        $levels = array(
            'seo_safe' => 'low',
            'balanced' => 'medium',
            'human' => 'high',
        );
        
        return $levels[$naturalness] ?? 'medium';
    }
    
    /**
     * Get transition quality level
     */
    private function get_transition_quality($naturalness) {
        $levels = array(
            'seo_safe' => 'basic',
            'balanced' => 'smooth',
            'human' => 'natural_flowing',
        );
        
        return $levels[$naturalness] ?? 'smooth';
    }
    
    /**
     * Get rhythm variation level
     */
    private function get_rhythm_variation($naturalness) {
        $levels = array(
            'seo_safe' => 'consistent',
            'balanced' => 'varied',
            'human' => 'dynamic',
        );
        
        return $levels[$naturalness] ?? 'varied';
    }
    
    /**
     * Get AI pattern removal level
     */
    private function get_ai_pattern_removal($naturalness) {
        $levels = array(
            'seo_safe' => 'minimal',
            'balanced' => 'moderate',
            'human' => 'aggressive',
        );
        
        return $levels[$naturalness] ?? 'moderate';
    }
    
    /**
     * Calculate entity count based on semantic aggressiveness
     */
    private function calculate_entity_count($aggressiveness) {
        $counts = array(
            'conservative' => 8,
            'moderate' => 15,
            'aggressive' => 25,
        );
        
        return $counts[$aggressiveness] ?? 15;
    }
    
    /**
     * Get LSI keyword density
     */
    private function get_lsi_density($aggressiveness) {
        $densities = array(
            'conservative' => 'low',
            'moderate' => 'balanced',
            'aggressive' => 'high',
        );
        
        return $densities[$aggressiveness] ?? 'balanced';
    }
    
    /**
     * Get subtopic coverage level
     */
    private function get_subtopic_coverage($aggressiveness) {
        $coverage = array(
            'conservative' => 'focused',
            'moderate' => 'comprehensive',
            'aggressive' => 'exhaustive',
        );
        
        return $coverage[$aggressiveness] ?? 'comprehensive';
    }
    
    /**
     * Get paragraph length based on readability
     */
    private function get_paragraph_length($readability) {
        $lengths = array(
            'easy' => 'short',
            'moderate' => 'medium',
            'advanced' => 'long',
        );
        
        return $lengths[$readability] ?? 'medium';
    }
    
    /**
     * Get sentence length based on readability
     */
    private function get_sentence_length($readability) {
        $lengths = array(
            'easy' => 'short',
            'moderate' => 'mixed',
            'advanced' => 'complex',
        );
        
        return $lengths[$readability] ?? 'mixed';
    }
    
    /**
     * Get heading frequency based on readability
     */
    private function get_heading_frequency($readability) {
        $frequencies = array(
            'easy' => 'frequent',
            'moderate' => 'balanced',
            'advanced' => 'sparse',
        );
        
        return $frequencies[$readability] ?? 'balanced';
    }
    
    /**
     * Get complexity level based on readability
     */
    private function get_complexity_level($readability) {
        $levels = array(
            'easy' => 'simple_accessible',
            'moderate' => 'balanced_clear',
            'advanced' => 'sophisticated_dense',
        );
        
        return $levels[$readability] ?? 'balanced_clear';
    }
    
    /**
     * Calculate intro length based on overall length
     */
    private function calculate_intro_length($length) {
        $lengths = array(
            'short' => 150,
            'medium' => 200,
            'long' => 250,
            'very_long' => 300,
            'comprehensive' => 350,
        );
        
        return $lengths[$length] ?? 200;
    }
    
    /**
     * Calculate conclusion length based on overall length
     */
    private function calculate_conclusion_length($length) {
        $lengths = array(
            'short' => 150,
            'medium' => 180,
            'long' => 200,
            'very_long' => 250,
            'comprehensive' => 300,
        );
        
        return $lengths[$length] ?? 180;
    }
    
    /**
     * Calculate section word minimum
     */
    private function calculate_section_word_min($length) {
        $mins = array(
            'short' => 150,
            'medium' => 200,
            'long' => 250,
            'very_long' => 300,
            'comprehensive' => 350,
        );
        
        return $mins[$length] ?? 200;
    }
    
    /**
     * Calculate section word maximum
     */
    private function calculate_section_word_max($length) {
        $maxs = array(
            'short' => 250,
            'medium' => 300,
            'long' => 350,
            'very_long' => 400,
            'comprehensive' => 450,
        );
        
        return $maxs[$length] ?? 300;
    }
    
    /**
     * Log strategy for debugging
     */
    private function log_strategy($strategy) {
        error_log('=== DODO GENERATION STRATEGY ===');
        error_log('Focus Keyword: ' . $strategy['focus_keyword']);
        error_log('Length: ' . $strategy['length'] . ' (Target: ' . $strategy['target_words'] . ' words, Min: ' . $strategy['min_words'] . ')');
        error_log('Sections: ' . $strategy['section_count'] . ' | H2: ' . $strategy['h2_count'] . ' | H3: ' . $strategy['h3_count'] . ' | FAQ: ' . $strategy['faq_count']);
        error_log('Tone: ' . $strategy['tone'] . ' (Structure: ' . $strategy['sentence_structure'] . ', CTA: ' . $strategy['cta_density'] . ')');
        error_log('Intent: ' . $strategy['content_intent'] . ' (Focus: ' . $strategy['intent_focus'] . ')');
        error_log('Expertise: ' . $strategy['expertise_depth'] . ' (Level: ' . $strategy['technical_level'] . ', Jargon: ' . $strategy['jargon_usage'] . ')');
        error_log('GEO: ' . $strategy['geo_optimization'] . ' (Answer Blocks: ' . ($strategy['answer_blocks_enabled'] ? 'Yes' : 'No') . ', Snippet: ' . $strategy['featured_snippet_optimization'] . ')');
        error_log('Naturalness: ' . $strategy['ai_naturalness'] . ' (Repetition: ' . $strategy['repetition_avoidance'] . ', Transitions: ' . $strategy['transition_quality'] . ')');
        error_log('Semantic: ' . $strategy['semantic_aggressiveness'] . ' (Entities: ' . $strategy['entity_count'] . ', LSI: ' . $strategy['lsi_keyword_density'] . ')');
        error_log('Readability: ' . $strategy['readability_target'] . ' (Paragraphs: ' . $strategy['paragraph_length'] . ', Sentences: ' . $strategy['sentence_length'] . ')');
        error_log('Publish Mode: ' . $strategy['publish_mode']);
        error_log('================================');
    }
    
    /**
     * Normalize tone value from settings to strategy
     * 
     * Settings values: technical, informative, commercial, friendly, corporate, expert
     * Strategy values: technical, conversational, professional, persuasive
     */
    private function normalize_tone($tone) {
        $mapping = array(
            'technical' => 'technical',
            'informative' => 'professional', // FIXED: informative artık professional
            'commercial' => 'persuasive',
            'friendly' => 'conversational',
            'corporate' => 'professional',
            'expert' => 'technical',
            // Strategy values (pass through)
            'conversational' => 'conversational',
            'professional' => 'professional',
            'persuasive' => 'persuasive',
        );
        
        return $mapping[$tone] ?? 'technical';
    }
    
    /**
     * Normalize GEO optimization value
     * 
     * Settings values: none, light, moderate, aggressive
     * Strategy values: minimal, moderate, aggressive
     */
    private function normalize_geo($geo) {
        $mapping = array(
            'none' => 'minimal',
            'light' => 'minimal',
            'moderate' => 'moderate',
            'aggressive' => 'aggressive',
            // Strategy values (pass through)
            'minimal' => 'minimal',
            'conservative' => 'minimal',
        );
        
        return $mapping[$geo] ?? 'moderate';
    }
    
    /**
     * Normalize AI naturalness value
     * 
     * Settings values: very_human, ai_friendly, balanced, human
     * Strategy values: seo_safe, balanced, human
     */
    private function normalize_naturalness($naturalness) {
        $mapping = array(
            'very_human' => 'human',
            'human' => 'human',
            'balanced' => 'balanced',
            'ai_friendly' => 'balanced',
            // Strategy values (pass through)
            'seo_safe' => 'seo_safe',
        );
        
        return $mapping[$naturalness] ?? 'human';
    }
    
    /**
     * Normalize readability value
     * 
     * Settings values: very_easy, easy, moderate, advanced
     * Strategy values: easy, moderate, advanced
     */
    private function normalize_readability($readability) {
        $mapping = array(
            'very_easy' => 'easy',
            'easy' => 'easy',
            'moderate' => 'moderate',
            'advanced' => 'advanced',
        );
        
        return $mapping[$readability] ?? 'easy';
    }
}
