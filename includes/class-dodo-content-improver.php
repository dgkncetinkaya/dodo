<?php
/**
 * DODO Content Improver Core Engine
 * 
 * Entire article rewrite yapmadan belirli section'ları geliştirmek için core engine.
 * 
 * @package DODO_AI_SEO
 * @since 1.3.0
 */

// Doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

class DODO_Content_Improver {
    
    /**
     * OpenAI instance
     */
    private $openai;
    
    /**
     * Supported improve types
     */
    private const IMPROVE_TYPES = array(
        'intro_improve',
        'faq_improve',
        'section_expand',
        'semantic_improve',
        'readability_improve',
        'cta_improve',
    );
    
    /**
     * Token limits per improve type
     */
    private const TOKEN_LIMITS = array(
        'intro_improve' => 600,
        'faq_improve' => 1200,
        'section_expand' => 1500,
        'semantic_improve' => 1000,
        'readability_improve' => 800,
        'cta_improve' => 400,
    );
    
    /**
     * Rewrite necessity levels
     */
    private const REWRITE_NECESSITY_LEVELS = array(
        'none',
        'low',
        'medium',
        'high',
    );
    
    /**
     * Analysis token limit
     */
    private const ANALYSIS_TOKEN_LIMIT = 500;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->openai = new DODO_OpenAI();
    }
    
    /**
     * Analyze improvement intent (STAGE 1: ANALYSIS)
     * 
     * Analyzes section content and determines improvement strategy.
     * This is the AI Editor Intelligence Layer.
     * 
     * @param string $section_content Section content to analyze
     * @param string $improve_type Type of improvement
     * @param string $focus_keyword Focus keyword
     * @param array $additional_context Additional context (optional)
     * @return array|WP_Error Analysis results or error
     */
    private function analyze_improvement_intent($section_content, $improve_type, $focus_keyword, $additional_context = array()) {
        // Build analysis prompt
        $system_prompt = "You are an AI content quality analyst. Analyze content and provide improvement strategy in JSON format.";
        
        $user_prompt = "Analyze this content section and provide improvement strategy.\n\n";
        $user_prompt .= "**Section Type:** {$improve_type}\n";
        $user_prompt .= "**Focus Keyword:** {$focus_keyword}\n\n";
        $user_prompt .= "**Section Content:**\n```\n{$section_content}\n```\n\n";
        $user_prompt .= "**Analysis Required:**\n";
        $user_prompt .= "1. Current quality score (0-100)\n";
        $user_prompt .= "2. Main problem (brief description)\n";
        $user_prompt .= "3. Improvement goal (what to achieve)\n";
        $user_prompt .= "4. Rewrite necessity (none/low/medium/high)\n";
        $user_prompt .= "5. What to keep (array of elements)\n";
        $user_prompt .= "6. What to change (array of elements)\n";
        $user_prompt .= "7. What NOT to change (array of elements)\n\n";
        $user_prompt .= "**Output Format:** JSON only, no explanations.\n\n";
        $user_prompt .= "**Example Output:**\n";
        $user_prompt .= "{\n";
        $user_prompt .= '  "current_quality_score": 68,' . "\n";
        $user_prompt .= '  "main_problem": "Weak opening hook",' . "\n";
        $user_prompt .= '  "improvement_goal": "Strengthen engagement",' . "\n";
        $user_prompt .= '  "rewrite_necessity": "low",' . "\n";
        $user_prompt .= '  "what_to_keep": ["structure", "facts", "links"],' . "\n";
        $user_prompt .= '  "what_to_change": ["opening sentence", "transitions"],' . "\n";
        $user_prompt .= '  "what_not_to_change": ["headings", "HTML structure"]' . "\n";
        $user_prompt .= "}";
        
        // Call OpenAI for analysis
        $response = $this->openai->generate_content(
            $system_prompt,
            $user_prompt,
            'improvement_intent_analysis'
        );
        
        // Error handling
        if (is_wp_error($response)) {
            // Fallback to default strategy if analysis fails
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("[DODO EDITOR] Analysis failed, using default strategy: " . $response->get_error_message());
            }
            
            return $this->get_default_strategy($improve_type);
        }
        
        // Parse JSON response
        $analysis = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($analysis)) {
            // Fallback to default strategy if JSON parsing fails
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("[DODO EDITOR] JSON parsing failed, using default strategy");
            }
            
            return $this->get_default_strategy($improve_type);
        }
        
        // Validate and normalize analysis
        $normalized = array(
            'section_type' => $improve_type,
            'current_quality_score' => isset($analysis['current_quality_score']) ? intval($analysis['current_quality_score']) : 50,
            'main_problem' => isset($analysis['main_problem']) ? sanitize_text_field($analysis['main_problem']) : 'Content needs improvement',
            'improvement_goal' => isset($analysis['improvement_goal']) ? sanitize_text_field($analysis['improvement_goal']) : 'Improve quality',
            'preserve_priority' => 'high', // Always high for editor behavior
            'rewrite_necessity' => $this->normalize_rewrite_necessity($analysis['rewrite_necessity'] ?? 'low'),
            'recommended_strategy' => $this->determine_strategy($analysis['rewrite_necessity'] ?? 'low'),
            'what_to_keep' => isset($analysis['what_to_keep']) && is_array($analysis['what_to_keep']) ? $analysis['what_to_keep'] : array('structure'),
            'what_to_change' => isset($analysis['what_to_change']) && is_array($analysis['what_to_change']) ? $analysis['what_to_change'] : array('content quality'),
            'what_not_to_change' => isset($analysis['what_not_to_change']) && is_array($analysis['what_not_to_change']) ? $analysis['what_not_to_change'] : array('headings', 'links'),
        );
        
        // Debug log
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[DODO EDITOR] Analysis complete - Quality: {$normalized['current_quality_score']}, Rewrite: {$normalized['rewrite_necessity']}");
        }
        
        return $normalized;
    }
    
    /**
     * Get default strategy (fallback)
     * 
     * @param string $improve_type
     * @return array
     */
    private function get_default_strategy($improve_type) {
        return array(
            'section_type' => $improve_type,
            'current_quality_score' => 50,
            'main_problem' => 'Content needs improvement',
            'improvement_goal' => 'Improve content quality',
            'preserve_priority' => 'high',
            'rewrite_necessity' => 'low', // Default to minimal changes
            'recommended_strategy' => 'minimal_edit',
            'what_to_keep' => array('structure', 'facts', 'links', 'headings'),
            'what_to_change' => array('content quality', 'clarity'),
            'what_not_to_change' => array('HTML structure', 'existing links', 'headings'),
        );
    }
    
    /**
     * Normalize rewrite necessity
     * 
     * @param string $necessity
     * @return string
     */
    private function normalize_rewrite_necessity($necessity) {
        $necessity = strtolower(trim($necessity));
        
        if (in_array($necessity, self::REWRITE_NECESSITY_LEVELS)) {
            return $necessity;
        }
        
        // Default to low (minimal changes)
        return 'low';
    }
    
    /**
     * Determine strategy based on rewrite necessity
     * 
     * @param string $rewrite_necessity
     * @return string
     */
    private function determine_strategy($rewrite_necessity) {
        switch ($rewrite_necessity) {
            case 'none':
                return 'no_change';
            case 'low':
                return 'minimal_edit';
            case 'medium':
                return 'partial_rewrite';
            case 'high':
                return 'full_rewrite';
            default:
                return 'minimal_edit';
        }
    }
    
    /**
     * Improve section
     * 
     * Main entry point for content improvement with AI Editor Intelligence.
     * Returns both improved content and metadata about the improvement.
     * 
     * @param string $section_content Section content to improve
     * @param string $improve_type Type of improvement
     * @param string $focus_keyword Focus keyword
     * @param array $additional_context Additional context (optional)
     * @return array|WP_Error Array with 'content' and 'metadata', or error
     */
    public function improve_section($section_content, $improve_type, $focus_keyword, $additional_context = array()) {
        // Validate improve type
        if (!in_array($improve_type, self::IMPROVE_TYPES)) {
            return new WP_Error(
                'invalid_improve_type',
                sprintf(__('Invalid improve type: %s', 'dodo-ai-seo'), $improve_type)
            );
        }
        
        // Validate section content
        if (empty($section_content)) {
            return new WP_Error(
                'empty_section',
                __('Section content is empty', 'dodo-ai-seo')
            );
        }
        
        // Store original content for rollback
        $original_content = $section_content;
        
        // Special handling for FAQ improve
        if ($improve_type === 'faq_improve' && !$this->detect_faq_section($section_content)) {
            return new WP_Error(
                'not_faq_section',
                __('Bu yazıda gerçek FAQ/SSS bölümü bulunamadı. İsterseniz FAQ Oluştur aksiyonunu kullanabilirsiniz.', 'dodo-ai-seo'),
                array('suggestion' => 'faq_create')
            );
        }
        
        // STAGE 1: ANALYSIS - Analyze improvement intent
        $strategy = $this->analyze_improvement_intent($section_content, $improve_type, $focus_keyword, $additional_context);
        
        if (is_wp_error($strategy)) {
            // Use default strategy if analysis fails
            $strategy = $this->get_default_strategy($improve_type);
        }
        
        // Check if no change needed
        if ($strategy['rewrite_necessity'] === 'none') {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("[DODO EDITOR] No change needed - Quality score: {$strategy['current_quality_score']}");
            }
            
            return array(
                'content' => $original_content,
                'metadata' => array(
                    'strategy' => 'no_change',
                    'rewrite_necessity' => 'none',
                    'main_problem' => 'Content quality is already good',
                    'what_changed' => array(),
                    'what_preserved' => array('everything'),
                ),
            );
        }
        
        // STAGE 2: IMPROVEMENT - Improve with strategy
        $improved_content = $this->improve_with_strategy(
            $section_content,
            $improve_type,
            $focus_keyword,
            $strategy,
            $additional_context
        );
        
        // Error handling
        if (is_wp_error($improved_content)) {
            error_log("[DODO EDITOR] Improvement failed: " . $improved_content->get_error_message());
            
            return array(
                'content' => $original_content,
                'metadata' => array(
                    'strategy' => 'failed',
                    'rewrite_necessity' => $strategy['rewrite_necessity'],
                    'main_problem' => $strategy['main_problem'],
                    'error' => $improved_content->get_error_message(),
                ),
            );
        }
        
        // STAGE 3: VALIDATION - Validate improvement
        $validation = $this->validate_improvement(
            $original_content,
            $improved_content,
            $improve_type,
            $strategy
        );
        
        if (!$validation['valid']) {
            // Validation failed - SAFE ROLLBACK
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("[DODO EDITOR] Validation failed: " . implode(', ', $validation['errors']));
                error_log("[DODO EDITOR] AI rewrite rejected - returning original content");
            }
            
            // Build validation failure message
            $error_message = __('AI rewrite rejected by validation system.', 'dodo-ai-seo');
            if (!empty($validation['errors'])) {
                $error_message .= ' ' . __('Reasons:', 'dodo-ai-seo') . ' ' . implode(', ', $validation['errors']);
            }
            
            return array(
                'content' => $original_content, // CRITICAL: Return original, not improved
                'validation_failed' => true, // NEW: Flag for frontend
                'apply_allowed' => false, // NEW: Disable apply button
                'metadata' => array(
                    'strategy' => 'validation_failed',
                    'rewrite_necessity' => $strategy['rewrite_necessity'],
                    'main_problem' => $strategy['main_problem'],
                    'validation_errors' => $validation['errors'],
                    'validation_warnings' => $validation['warnings'],
                    'error_message' => $error_message,
                    // No diff data - validation failed
                    'diff_html' => null,
                    'sentence_diff_html' => null,
                ),
            );
        }
        
        // Calculate sentence statistics for metadata
        $sentence_stats = $this->analyze_sentence_changes($original_content, $improved_content);
        
        // Calculate semantic analysis for metadata
        $semantic_analysis = $this->detect_semantic_rewrites($original_content, $improved_content);
        
        // Calculate token-level analysis for metadata
        $token_analysis = $this->analyze_token_level_changes($original_content, $improved_content);
        
        // Calculate change intent analysis for metadata (NEW - Task 9)
        $intent_classifier = new DODO_Change_Intent_Classifier();
        $intent_analysis = $intent_classifier->classify($original_content, $improved_content);
        
        // Generate content diff for visualization
        $diff_data = $this->generate_content_diff($original_content, $improved_content);
        
        // Success - return improved content with metadata
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[DODO EDITOR] Section improved successfully - Type: {$improve_type}, Strategy: {$strategy['recommended_strategy']}");
            error_log("[DODO EDITOR] Sentence stats - Preserved: {$sentence_stats['preserved']}/{$sentence_stats['total_original']}, Changed: {$sentence_stats['changed']}");
            error_log("[DODO EDITOR] Semantic preservation: " . round($semantic_analysis['semantic_preservation_score'] * 100) . "%");
            error_log("[DODO EDITOR] Token stats - Changed: {$token_analysis['changed_tokens']}/{$token_analysis['total_original_tokens']} (" . round($token_analysis['token_change_ratio'] * 100) . "%), Surgical score: {$token_analysis['surgical_edit_score']}%");
            error_log("[DODO EDITOR] Diff stats - Added: {$diff_data['added_words_count']}, Removed: {$diff_data['removed_words_count']}, Modified: {$diff_data['modified_words_count']}, Change: {$diff_data['diff_change_percentage']}%");
        }
        
        return array(
            'content' => $improved_content,
            'validation_failed' => false, // NEW: Validation passed
            'apply_allowed' => true, // NEW: Apply button enabled
            'metadata' => array(
                'strategy' => $strategy['recommended_strategy'],
                'rewrite_necessity' => $strategy['rewrite_necessity'],
                'main_problem' => $strategy['main_problem'],
                'improvement_goal' => $strategy['improvement_goal'],
                'what_changed' => $strategy['what_to_change'],
                'what_preserved' => $strategy['what_to_keep'],
                'validation_warnings' => $validation['warnings'],
                'changed_sentences_count' => $sentence_stats['changed'],
                'preserved_sentences_count' => $sentence_stats['preserved'],
                'total_sentences_count' => $sentence_stats['total_original'],
                'semantic_preservation_score' => round($semantic_analysis['semantic_preservation_score'] * 100),
                'synonym_replacement_count' => $semantic_analysis['synonym_replacement_count'],
                'stylistic_changes_detected' => $semantic_analysis['stylistic_changes_detected'],
                'token_change_ratio' => round($token_analysis['token_change_ratio'] * 100),
                'surgical_edit_score' => $token_analysis['surgical_edit_score'],
                'reconstructed_sentences_count' => $token_analysis['reconstructed_sentences_count'],
                // Change Intent Analysis (NEW - Task 9)
                'change_intent' => $intent_analysis['intent'],
                'change_classification' => $intent_analysis['classification'],
                'is_editorial_safe' => $intent_analysis['is_editorial_safe'],
                'grammar_fixes' => $intent_analysis['intent']['grammar_fix'],
                'punctuation_fixes' => $intent_analysis['intent']['punctuation_fix'],
                'readability_fixes' => $intent_analysis['intent']['readability_fix'],
                'semantic_reframings' => $intent_analysis['intent']['semantic_reframing'],
                'tone_shifts' => $intent_analysis['intent']['tone_shift'],
                'metaphor_changes' => $intent_analysis['intent']['metaphor_change'],
                'sentence_reconstructions' => $intent_analysis['intent']['sentence_reconstruction'],
                // Diff visualization data (NEW)
                'diff_html' => $diff_data['diff_html'],
                'sentence_diff_html' => $diff_data['sentence_diff_html'],
                'added_words_count' => $diff_data['added_words_count'],
                'removed_words_count' => $diff_data['removed_words_count'],
                'modified_words_count' => $diff_data['modified_words_count'],
                'unchanged_words_count' => $diff_data['unchanged_words_count'],
                'diff_change_percentage' => $diff_data['diff_change_percentage'],
            ),
        );
    }
    
    /**
     * Improve with strategy (STAGE 2: IMPROVEMENT)
     * 
     * Improves content using analysis strategy.
     * 
     * @param string $section_content Section content to improve
     * @param string $improve_type Type of improvement
     * @param string $focus_keyword Focus keyword
     * @param array $strategy Analysis strategy
     * @param array $additional_context Additional context (optional)
     * @return string|WP_Error Improved content or error
     */
    private function improve_with_strategy($section_content, $improve_type, $focus_keyword, $strategy, $additional_context = array()) {
        // Build strategy-aware prompts
        $system_prompt = $this->build_system_prompt_with_strategy($improve_type, $strategy);
        $user_prompt = $this->build_user_prompt_with_strategy($section_content, $improve_type, $focus_keyword, $strategy, $additional_context);
        
        // Get token limit
        $max_tokens = self::TOKEN_LIMITS[$improve_type];
        
        // Call OpenAI with feature name
        $response = $this->openai->generate_content($system_prompt, $user_prompt, $improve_type);
        
        // Error handling
        if (is_wp_error($response)) {
            return $response;
        }
        
        // Clean response
        $improved_content = $this->clean_ai_response($response);
        
        if (empty($improved_content)) {
            return new WP_Error('empty_response', __('AI returned empty response', 'dodo-ai-seo'));
        }
        
        return $improved_content;
    }
    
    /**
     * Build system prompt with strategy
     * 
     * @param string $improve_type
     * @param array $strategy
     * @return string
     */
    private function build_system_prompt_with_strategy($improve_type, $strategy) {
        $base_prompt = "You are a professional content editor. ";
        
        // Add strategy context
        $base_prompt .= "Your editing approach: {$strategy['recommended_strategy']}. ";
        $base_prompt .= "Rewrite necessity: {$strategy['rewrite_necessity']}. ";
        
        switch ($improve_type) {
            case 'intro_improve':
                $base_prompt .= "Improve the introduction section to be more engaging and SEO-friendly. ";
                break;
            
            case 'faq_improve':
                $base_prompt .= "Improve the FAQ section with better answers and semantic depth. ";
                break;
            
            case 'section_expand':
                $base_prompt .= "Expand the section with more detailed information and examples. ";
                break;
            
            case 'semantic_improve':
                $base_prompt .= "Improve semantic depth by adding related concepts and LSI keywords naturally. ";
                break;
            
            case 'readability_improve':
                $base_prompt .= "Improve readability with better sentence structure and clarity. ";
                break;
            
            case 'cta_improve':
                $base_prompt .= "Improve the call-to-action to be more compelling and natural. ";
                break;
        }
        
        // Add preservation emphasis
        $base_prompt .= "CRITICAL: Make ONLY necessary changes. Preserve existing structure, links, and headings.";
        
        return $base_prompt;
    }
    
    /**
     * Build user prompt with strategy
     * 
     * @param string $section_content
     * @param string $improve_type
     * @param string $focus_keyword
     * @param array $strategy
     * @param array $additional_context
     * @return string
     */
    private function build_user_prompt_with_strategy($section_content, $improve_type, $focus_keyword, $strategy, $additional_context = array()) {
        $prompt = "**Improvement Type:** {$improve_type}\n";
        $prompt .= "**Focus Keyword:** {$focus_keyword}\n\n";
        
        // Add analysis results
        $prompt .= "**ANALYSIS RESULTS:**\n";
        $prompt .= "- Main Problem: {$strategy['main_problem']}\n";
        $prompt .= "- Rewrite Necessity: {$strategy['rewrite_necessity']}\n";
        $prompt .= "- Strategy: {$strategy['recommended_strategy']}\n\n";
        
        // Add preservation rules
        $prompt .= "**PRESERVATION RULES:**\n";
        $prompt .= "- MUST KEEP: " . implode(', ', $strategy['what_to_keep']) . "\n";
        $prompt .= "- CAN CHANGE: " . implode(', ', $strategy['what_to_change']) . "\n";
        $prompt .= "- NEVER CHANGE: " . implode(', ', $strategy['what_not_to_change']) . "\n\n";
        
        // Add critical instructions based on rewrite necessity
        $prompt .= "**CRITICAL INSTRUCTIONS:**\n";
        
        if ($strategy['rewrite_necessity'] === 'low') {
            $prompt .= "⚠️ MINIMAL EDIT MODE - STRICT RULES:\n";
            $prompt .= "- Preserve 70-90% of original content\n";
            $prompt .= "- Keep original sentences whenever possible\n";
            $prompt .= "- Modify ONLY weak or unclear parts\n";
            $prompt .= "- Do NOT rewrite for stylistic variation only\n";
            $prompt .= "- Do NOT use synonyms unless clarity improves\n";
            $prompt .= "- Do NOT change paragraph structure\n";
            $prompt .= "- Do NOT add unnecessary introductory sentences\n";
            $prompt .= "- Do NOT change content length significantly\n";
            $prompt .= "- Keep paragraph flow nearly identical\n";
            $prompt .= "- Think like an EDITOR, not a WRITER\n";
            $prompt .= "- Make surgical edits, not rewrites\n\n";
            $prompt .= "⚠️ FORBIDDEN ACTIONS IN LOW MODE:\n";
            $prompt .= "- Do NOT rewrite sentences just to sound different\n";
            $prompt .= "- Do NOT replace words with synonyms unless clarity improves\n";
            $prompt .= "- Do NOT change 'kalbi sayılabilecek' to 'can damarı' (unnecessary synonym)\n";
            $prompt .= "- Do NOT change 'hayati rol oynar' to 'hayati öneme sahiptir' (stylistic only)\n";
            $prompt .= "- Do NOT change tone or personality of the text\n";
            $prompt .= "- Do NOT add dramatic adjectives\n";
            $prompt .= "- Preserve the author's wording whenever possible\n\n";
            $prompt .= "✅ ONLY ALLOWED CHANGES IN LOW MODE:\n";
            $prompt .= "- Grammar fixes\n";
            $prompt .= "- Clarity improvements (only if unclear)\n";
            $prompt .= "- Typo corrections\n";
            $prompt .= "- Weak hook strengthening (if genuinely weak)\n";
            $prompt .= "- Readability fixes (only if genuinely hard to read)\n\n";
            $prompt .= "🎯 ACT LIKE A HUMAN EDITOR:\n";
            $prompt .= "Make surgical corrections, NOT AI rewrites.\n";
            $prompt .= "If a sentence is clear, leave it alone.\n";
            $prompt .= "If a word works, don't replace it.\n\n";
            $prompt .= "🔬 TOKEN-LEVEL SURGICAL EDITING:\n";
            $prompt .= "PRESERVE THE ORIGINAL SENTENCE STRUCTURE.\n";
            $prompt .= "Do NOT reconstruct sentences unless absolutely necessary.\n";
            $prompt .= "Prefer changing a few words instead of rewriting the sentence.\n";
            $prompt .= "Act like a human proofreader making tiny edits.\n\n";
            $prompt .= "❌ BAD EXAMPLE:\n";
            $prompt .= "Original: 'hayati rol oynar'\n";
            $prompt .= "Bad: 'hayati öneme sahiptir' (sentence reconstruction)\n\n";
            $prompt .= "✅ GOOD EXAMPLE:\n";
            $prompt .= "Original: 'hayati rol oynar'\n";
            $prompt .= "Good: 'önemli rol oynar' (minimal token change)\n\n";
            $prompt .= "⭐ BEST EXAMPLE:\n";
            $prompt .= "Original: 'hayati rol oynar'\n";
            $prompt .= "Best: leave unchanged if already clear\n\n";
        } elseif ($strategy['rewrite_necessity'] === 'medium') {
            $prompt .= "MODERATE EDIT MODE:\n";
            $prompt .= "- Preserve 40-60% of original content\n";
            $prompt .= "- Make MODERATE changes\n";
            $prompt .= "- Preserve main structure and key points\n";
            $prompt .= "- Improve clarity and flow\n";
            $prompt .= "- Keep most factual sentences intact\n";
            $prompt .= "- Rewrite weak sections more freely\n\n";
        } elseif ($strategy['rewrite_necessity'] === 'high') {
            $prompt .= "FULL REWRITE MODE:\n";
            $prompt .= "- Significant rewrite allowed\n";
            $prompt .= "- Preserve core facts and links\n";
            $prompt .= "- Improve overall quality substantially\n";
            $prompt .= "- Restructure if needed\n";
            $prompt .= "- Focus on quality over preservation\n\n";
        }
        
        $prompt .= "- Keep existing links, headings, and HTML structure\n";
        $prompt .= "- Never turn normal section into FAQ unless it's already FAQ\n";
        $prompt .= "- Use focus keyword naturally\n\n";
        
        // Add additional context
        if (!empty($additional_context['tone'])) {
            $prompt .= "**Tone:** {$additional_context['tone']}\n";
        }
        
        if (!empty($additional_context['target_audience'])) {
            $prompt .= "**Target Audience:** {$additional_context['target_audience']}\n";
        }
        
        // Add type-specific instructions
        $prompt .= "\n" . $this->get_type_specific_instructions($improve_type, $strategy['rewrite_necessity']) . "\n";
        
        // Add output format enforcement
        $prompt .= "**OUTPUT FORMAT:**\n";
        $prompt .= "- Return ONLY clean HTML\n";
        $prompt .= "- NO markdown wrappers (```html or ```markdown)\n";
        $prompt .= "- NO explanations\n";
        $prompt .= "- NO code blocks\n";
        $prompt .= "- Just the improved content\n\n";
        
        $prompt .= "**ORIGINAL CONTENT:**\n";
        $prompt .= $section_content . "\n\n";
        
        $prompt .= "**IMPROVED CONTENT:**\n";
        
        return $prompt;
    }
    
    /**
     * Get type-specific instructions
     * 
     * @param string $improve_type
     * @param string $rewrite_necessity
     * @return string
     */
    private function get_type_specific_instructions($improve_type, $rewrite_necessity = 'low') {
        $is_minimal = ($rewrite_necessity === 'low' || $rewrite_necessity === 'none');
        
        switch ($improve_type) {
            case 'intro_improve':
                if ($is_minimal) {
                    return "**INTRO IMPROVEMENT (MINIMAL):**\n" .
                           "- Strengthen opening hook ONLY if weak\n" .
                           "- Keep existing sentences if they work\n" .
                           "- Don't rewrite entire intro\n" .
                           "- Keep it concise (2-3 paragraphs)";
                } else {
                    return "**INTRO IMPROVEMENT:**\n" .
                           "- Strengthen opening hook\n" .
                           "- Keep it concise (2-3 paragraphs)\n" .
                           "- Improve engagement";
                }
            
            case 'faq_improve':
                if ($is_minimal) {
                    return "**FAQ IMPROVEMENT (MINIMAL):**\n" .
                           "- Keep Q&A format exactly\n" .
                           "- Improve answer quality ONLY if unclear\n" .
                           "- Keep answers concise (2-3 sentences)\n" .
                           "- Don't rewrite clear answers";
                } else {
                    return "**FAQ IMPROVEMENT:**\n" .
                           "- Keep Q&A format\n" .
                           "- Improve answer quality\n" .
                           "- Keep answers concise (2-3 sentences)";
                }
            
            case 'section_expand':
                return "**SECTION EXPANSION:**\n" .
                       "- Add more details and examples\n" .
                       "- Don't change existing content unnecessarily\n" .
                       "- Keep heading structure\n" .
                       "- Expand, don't rewrite";
            
            case 'semantic_improve':
                if ($is_minimal) {
                    return "**SEMANTIC IMPROVEMENT (MINIMAL):**\n" .
                           "- Add related concepts naturally\n" .
                           "- NO keyword stuffing\n" .
                           "- Keep length similar\n" .
                           "- Don't rewrite existing sentences\n" .
                           "- Insert concepts, don't replace";
                } else {
                    return "**SEMANTIC IMPROVEMENT:**\n" .
                           "- Add related concepts naturally\n" .
                           "- No keyword stuffing\n" .
                           "- Keep length similar";
                }
            
            case 'readability_improve':
                if ($is_minimal) {
                    return "**READABILITY IMPROVEMENT (MINIMAL):**\n" .
                           "- Simplify ONLY complex sentences\n" .
                           "- Use active voice where passive is unclear\n" .
                           "- Keep technical meaning intact\n" .
                           "- Don't rewrite clear sentences\n" .
                           "- Fix, don't rewrite";
                } else {
                    return "**READABILITY IMPROVEMENT:**\n" .
                           "- Simplify complex sentences\n" .
                           "- Use active voice\n" .
                           "- Keep technical meaning intact";
                }
            
            case 'cta_improve':
                if ($is_minimal) {
                    return "**CTA IMPROVEMENT (MINIMAL):**\n" .
                           "- Strengthen call-to-action ONLY if weak\n" .
                           "- Keep it natural and concise\n" .
                           "- Max 1-2 sentences\n" .
                           "- Don't rewrite if already good";
                } else {
                    return "**CTA IMPROVEMENT:**\n" .
                           "- Make it more compelling\n" .
                           "- Keep it natural and concise\n" .
                           "- Max 1-2 sentences";
                }
            
            default:
                return "";
        }
    }
    
    /**
     * Clean AI response
     * 
     * Removes markdown wrappers and cleans output.
     * 
     * @param string $response
     * @return string
     */
    private function clean_ai_response($response) {
        $response = trim($response);
        
        // Remove markdown code block wrappers
        $response = preg_replace('/^```(html|markdown)?\s*/i', '', $response);
        $response = preg_replace('/\s*```$/i', '', $response);
        
        // Trim again
        $response = trim($response);
        
        return $response;
    }
    
    /**
     * Validate improvement (STAGE 3: VALIDATION)
     * 
     * Validates improved content against strategy and rules.
     * 
     * @param string $original_content Original content
     * @param string $improved_content Improved content
     * @param string $improve_type Type of improvement
     * @param array $strategy Analysis strategy
     * @return array Validation result with 'valid', 'errors', 'warnings'
     */
    private function validate_improvement($original_content, $improved_content, $improve_type, $strategy) {
        $errors = array();
        $warnings = array();
        
        // 1. Empty response check
        if (empty($improved_content) || strlen($improved_content) < 20) {
            $errors[] = 'Improved content is empty or too short';
        }
        
        // 2. Markdown wrapper check
        if (preg_match('/^```(html|markdown)/i', $improved_content) || preg_match('/```$/i', $improved_content)) {
            $errors[] = 'Content contains markdown wrapper';
        }
        
        // 3. Length validation based on rewrite necessity
        $original_length = strlen($original_content);
        $improved_length = strlen($improved_content);
        $length_ratio = $improved_length / $original_length;
        
        if ($strategy['rewrite_necessity'] === 'none' && $length_ratio < 0.9) {
            $errors[] = 'Content changed too much for rewrite_necessity=none';
        } elseif ($strategy['rewrite_necessity'] === 'low' && $length_ratio < 0.5) {
            $errors[] = 'Content too short for rewrite_necessity=low (must be >= 50% of original)';
        } elseif ($strategy['rewrite_necessity'] === 'medium' && $length_ratio < 0.3) {
            $warnings[] = 'Content significantly shorter than original';
        }
        
        // 4. Similarity check for LOW rewrite necessity
        if ($strategy['rewrite_necessity'] === 'low') {
            $similarity = $this->calculate_content_similarity($original_content, $improved_content);
            
            if ($similarity < 0.5) {
                $errors[] = 'Content changed too much for LOW rewrite (similarity: ' . round($similarity * 100) . '%, required: >= 50%)';
            } elseif ($similarity < 0.7) {
                $warnings[] = 'Content similarity lower than expected for LOW rewrite (' . round($similarity * 100) . '%)';
            }
            
            // Sentence-level change detection
            $sentence_stats = $this->analyze_sentence_changes($original_content, $improved_content);
            
            if ($sentence_stats['change_ratio'] > 0.5) {
                $errors[] = 'Too many sentences changed for LOW rewrite (' . round($sentence_stats['change_ratio'] * 100) . '% changed, max 50%)';
            }
            
            // Semantic rewrite detection
            $semantic_analysis = $this->detect_semantic_rewrites($original_content, $improved_content);
            
            // Check semantic preservation score
            if ($semantic_analysis['semantic_preservation_score'] < 0.6) {
                $errors[] = 'Excessive semantic rewrites detected (preservation: ' . round($semantic_analysis['semantic_preservation_score'] * 100) . '%, required: >= 60%)';
            }
            
            // Check synonym replacements
            if ($semantic_analysis['synonym_replacement_count'] > 10) {
                $errors[] = 'Too many synonym replacements (' . $semantic_analysis['synonym_replacement_count'] . ' detected, max 10 for LOW rewrite)';
            }
            
            // Check stylistic changes
            if ($semantic_analysis['stylistic_changes_detected'] > 3) {
                $errors[] = 'Too many stylistic changes (' . $semantic_analysis['stylistic_changes_detected'] . ' detected, max 3 for LOW rewrite)';
            }
            
            // Check forbidden patterns
            if (!empty($semantic_analysis['forbidden_patterns'])) {
                foreach ($semantic_analysis['forbidden_patterns'] as $pattern) {
                    $warnings[] = $pattern;
                }
            }
            
            // Token-level validation (NEW)
            $token_analysis = $this->analyze_token_level_changes($original_content, $improved_content);
            
            // Check token change ratio
            if ($token_analysis['token_change_ratio'] > 0.2) {
                $errors[] = 'Too many tokens changed for LOW rewrite (' . round($token_analysis['token_change_ratio'] * 100) . '% changed, max 20%)';
            }
            
            // Check surgical edit score
            if ($token_analysis['surgical_edit_score'] < 80) {
                $errors[] = 'Surgical edit score too low (' . $token_analysis['surgical_edit_score'] . '%, required: >= 80%)';
            }
            
            // Check sentence reconstruction
            if ($token_analysis['reconstruction_detected']) {
                $errors[] = 'Unnecessary sentence reconstruction detected (' . $token_analysis['reconstructed_sentences_count'] . ' sentences rebuilt)';
            }
            
            // CHANGE INTENT CLASSIFICATION (NEW - Task 9)
            $intent_classifier = new DODO_Change_Intent_Classifier();
            $intent_analysis = $intent_classifier->classify($original_content, $improved_content);
            
            // Check if changes are editorial safe
            if (!$intent_analysis['is_editorial_safe']) {
                $forbidden_changes = $intent_analysis['forbidden_changes'];
                
                if (!empty($forbidden_changes)) {
                    $errors[] = 'LOW edit mode only allows surgical editorial corrections. AI attempted stylistic or semantic rewriting: ' . implode(', ', $forbidden_changes);
                }
            }
            
            // Log change intent analysis
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[DODO VALIDATION] Change Intent Analysis: ' . json_encode($intent_analysis['intent']));
                error_log('[DODO VALIDATION] Classification: ' . $intent_analysis['classification']);
                error_log('[DODO VALIDATION] Editorial Safe: ' . ($intent_analysis['is_editorial_safe'] ? 'YES' : 'NO'));
            }
        }
        
        // 5. FAQ validation
        if ($improve_type === 'faq_improve') {
            if (!$this->detect_faq_section($original_content)) {
                $errors[] = 'Original content is not a FAQ section';
            } elseif (!$this->has_qa_format($improved_content)) {
                $errors[] = 'Improved content lost Q&A format';
            }
        }
        
        // 6. CTA length validation
        if ($improve_type === 'cta_improve') {
            $word_count = str_word_count($improved_content);
            if ($word_count > 100) {
                $warnings[] = 'CTA is too long (should be concise)';
            }
        }
        
        // 7. Link preservation check (basic)
        $original_links = preg_match_all('/<a\s+[^>]*href=["\']([^"\']+)["\'][^>]*>/i', $original_content);
        $improved_links = preg_match_all('/<a\s+[^>]*href=["\']([^"\']+)["\'][^>]*>/i', $improved_content);
        
        if ($original_links > 0 && $improved_links < $original_links) {
            $warnings[] = 'Some links may have been removed';
        }
        
        // 8. Heading preservation check (basic)
        $original_headings = preg_match_all('/<h[2-6][^>]*>/i', $original_content);
        $improved_headings = preg_match_all('/<h[2-6][^>]*>/i', $improved_content);
        
        if ($original_headings > 0 && $improved_headings < $original_headings) {
            $warnings[] = 'Some headings may have been removed';
        }
        
        // Determine validity
        $valid = empty($errors);
        
        return array(
            'valid' => $valid,
            'errors' => $errors,
            'warnings' => $warnings,
        );
    }
    
    /**
     * Calculate content similarity
     * 
     * Uses word-level similarity to detect excessive rewrites.
     * 
     * @param string $original Original content
     * @param string $improved Improved content
     * @return float Similarity score (0-1)
     */
    private function calculate_content_similarity($original, $improved) {
        // Strip HTML tags
        $original_text = wp_strip_all_tags($original);
        $improved_text = wp_strip_all_tags($improved);
        
        // Convert to lowercase and split into words
        $original_words = preg_split('/\s+/', strtolower($original_text), -1, PREG_SPLIT_NO_EMPTY);
        $improved_words = preg_split('/\s+/', strtolower($improved_text), -1, PREG_SPLIT_NO_EMPTY);
        
        if (empty($original_words) || empty($improved_words)) {
            return 0;
        }
        
        // Calculate word overlap (Jaccard similarity)
        $original_set = array_unique($original_words);
        $improved_set = array_unique($improved_words);
        
        $intersection = array_intersect($original_set, $improved_set);
        $union = array_unique(array_merge($original_set, $improved_set));
        
        $similarity = count($intersection) / count($union);
        
        return $similarity;
    }
    
    /**
     * Analyze sentence changes
     * 
     * Detects how many sentences were changed.
     * 
     * @param string $original Original content
     * @param string $improved Improved content
     * @return array Stats with changed/preserved/total counts
     */
    private function analyze_sentence_changes($original, $improved) {
        // Strip HTML tags
        $original_text = wp_strip_all_tags($original);
        $improved_text = wp_strip_all_tags($improved);
        
        // Split into sentences (basic)
        $original_sentences = preg_split('/[.!?]+/', $original_text, -1, PREG_SPLIT_NO_EMPTY);
        $improved_sentences = preg_split('/[.!?]+/', $improved_text, -1, PREG_SPLIT_NO_EMPTY);
        
        $original_sentences = array_map('trim', $original_sentences);
        $improved_sentences = array_map('trim', $improved_sentences);
        
        $original_sentences = array_filter($original_sentences);
        $improved_sentences = array_filter($improved_sentences);
        
        $total_original = count($original_sentences);
        $total_improved = count($improved_sentences);
        
        if ($total_original === 0) {
            return array(
                'total_original' => 0,
                'total_improved' => $total_improved,
                'preserved' => 0,
                'changed' => $total_improved,
                'change_ratio' => 1.0,
            );
        }
        
        // Count preserved sentences (exact match or very similar)
        $preserved = 0;
        foreach ($original_sentences as $orig_sentence) {
            foreach ($improved_sentences as $imp_sentence) {
                $similarity = similar_text(strtolower($orig_sentence), strtolower($imp_sentence), $percent);
                if ($percent > 80) {
                    $preserved++;
                    break;
                }
            }
        }
        
        $changed = $total_original - $preserved;
        $change_ratio = $changed / $total_original;
        
        return array(
            'total_original' => $total_original,
            'total_improved' => $total_improved,
            'preserved' => $preserved,
            'changed' => $changed,
            'change_ratio' => $change_ratio,
        );
    }
    
    /**
     * Detect semantic rewrites (stylistic changes without quality improvement)
     * 
     * Detects unnecessary synonym replacements and stylistic mutations.
     * 
     * @param string $original Original content
     * @param string $improved Improved content
     * @return array Detection results with scores and counts
     */
    private function detect_semantic_rewrites($original, $improved) {
        // Strip HTML tags
        $original_text = wp_strip_all_tags($original);
        $improved_text = wp_strip_all_tags($improved);
        
        // Convert to lowercase for comparison
        $original_lower = strtolower($original_text);
        $improved_lower = strtolower($improved_text);
        
        // Split into words
        $original_words = preg_split('/\s+/', $original_lower, -1, PREG_SPLIT_NO_EMPTY);
        $improved_words = preg_split('/\s+/', $improved_lower, -1, PREG_SPLIT_NO_EMPTY);
        
        // Calculate word preservation ratio
        $original_word_set = array_unique($original_words);
        $improved_word_set = array_unique($improved_words);
        
        $preserved_words = array_intersect($original_word_set, $improved_word_set);
        $word_preservation_ratio = count($preserved_words) / count($original_word_set);
        
        // Detect synonym replacements (words removed from original)
        $removed_words = array_diff($original_word_set, $improved_word_set);
        $added_words = array_diff($improved_word_set, $original_word_set);
        
        $synonym_replacement_count = min(count($removed_words), count($added_words));
        
        // Detect stylistic mutations (sentence structure changes)
        $original_sentences = preg_split('/[.!?]+/', $original_text, -1, PREG_SPLIT_NO_EMPTY);
        $improved_sentences = preg_split('/[.!?]+/', $improved_text, -1, PREG_SPLIT_NO_EMPTY);
        
        $stylistic_changes = 0;
        foreach ($original_sentences as $i => $orig_sentence) {
            if (!isset($improved_sentences[$i])) {
                continue;
            }
            
            $orig_sentence = trim($orig_sentence);
            $imp_sentence = trim($improved_sentences[$i]);
            
            // Check if sentence structure changed significantly
            $similarity = similar_text(strtolower($orig_sentence), strtolower($imp_sentence), $percent);
            
            // If similarity is between 40-80%, it's likely a stylistic rewrite
            // (not completely different, but not preserved either)
            if ($percent >= 40 && $percent < 80) {
                $stylistic_changes++;
            }
        }
        
        // Calculate semantic preservation score (0-1)
        // Higher score = better preservation
        $semantic_preservation_score = ($word_preservation_ratio * 0.6) + 
                                      ((1 - ($synonym_replacement_count / max(count($original_word_set), 1))) * 0.4);
        
        // Detect forbidden patterns
        $forbidden_patterns_detected = $this->detect_forbidden_rewrite_patterns($original_text, $improved_text);
        
        return array(
            'semantic_preservation_score' => $semantic_preservation_score,
            'word_preservation_ratio' => $word_preservation_ratio,
            'synonym_replacement_count' => $synonym_replacement_count,
            'stylistic_changes_detected' => $stylistic_changes,
            'forbidden_patterns' => $forbidden_patterns_detected,
        );
    }
    
    /**
     * Detect forbidden rewrite patterns
     * 
     * Detects specific patterns that indicate unnecessary rewrites.
     * 
     * @param string $original Original text
     * @param string $improved Improved text
     * @return array List of detected forbidden patterns
     */
    private function detect_forbidden_rewrite_patterns($original, $improved) {
        $patterns = array();
        
        // Common Turkish stylistic mutations to detect
        $stylistic_pairs = array(
            array('kalbi sayılabilecek', 'can damarı'),
            array('hayati rol oynar', 'hayati öneme sahiptir'),
            array('önemli bir yere sahip', 'önemli bir konumda'),
            array('büyük önem taşır', 'büyük öneme sahiptir'),
            array('kritik rol oynar', 'kritik öneme sahiptir'),
            array('temel unsur', 'temel öğe'),
            array('önemli faktör', 'önemli etken'),
            array('yaygın olarak kullanılır', 'sıklıkla kullanılır'),
            array('geniş bir yelpaze', 'geniş bir spektrum'),
            array('çok sayıda', 'birçok'),
        );
        
        $original_lower = strtolower($original);
        $improved_lower = strtolower($improved);
        
        foreach ($stylistic_pairs as $pair) {
            $original_phrase = $pair[0];
            $replacement_phrase = $pair[1];
            
            // Check if original phrase was replaced with synonym
            if (strpos($original_lower, $original_phrase) !== false && 
                strpos($improved_lower, $replacement_phrase) !== false) {
                $patterns[] = "Unnecessary synonym: '{$original_phrase}' → '{$replacement_phrase}'";
            }
        }
        
        // Detect excessive adjective additions
        $original_adjective_count = preg_match_all('/\b(çok|oldukça|son derece|fevkalade|gayet|hayli)\b/iu', $original);
        $improved_adjective_count = preg_match_all('/\b(çok|oldukça|son derece|fevkalade|gayet|hayli)\b/iu', $improved);
        
        if ($improved_adjective_count > $original_adjective_count * 1.5) {
            $patterns[] = 'Excessive adjective additions detected';
        }
        
        // Detect tone changes (formal vs informal)
        $original_formal_count = preg_match_all('/\b(olmaktadır|bulunmaktadır|edilmektedir|yapılmaktadır)\b/iu', $original);
        $improved_formal_count = preg_match_all('/\b(olmaktadır|bulunmaktadır|edilmektedir|yapılmaktadır)\b/iu', $improved);
        
        if (abs($improved_formal_count - $original_formal_count) > 3) {
            $patterns[] = 'Tone change detected (formal/informal shift)';
        }
        
        return $patterns;
    }
    
    /**
     * Analyze token-level changes (surgical editing detection)
     * 
     * Detects how many individual words/tokens were changed.
     * Helps identify unnecessary sentence reconstruction.
     * 
     * @param string $original Original content
     * @param string $improved Improved content
     * @return array Token-level analysis with surgical edit score
     */
    private function analyze_token_level_changes($original, $improved) {
        // Strip HTML tags
        $original_text = wp_strip_all_tags($original);
        $improved_text = wp_strip_all_tags($improved);
        
        // Tokenize (split into words)
        $original_tokens = preg_split('/\s+/', strtolower($original_text), -1, PREG_SPLIT_NO_EMPTY);
        $improved_tokens = preg_split('/\s+/', strtolower($improved_text), -1, PREG_SPLIT_NO_EMPTY);
        
        $total_original_tokens = count($original_tokens);
        $total_improved_tokens = count($improved_tokens);
        
        if ($total_original_tokens === 0) {
            return array(
                'total_original_tokens' => 0,
                'total_improved_tokens' => $total_improved_tokens,
                'changed_tokens' => $total_improved_tokens,
                'token_change_ratio' => 1.0,
                'surgical_edit_score' => 0,
            );
        }
        
        // Calculate token overlap (how many tokens are preserved)
        $original_token_set = array_count_values($original_tokens);
        $improved_token_set = array_count_values($improved_tokens);
        
        $preserved_tokens = 0;
        foreach ($original_token_set as $token => $count) {
            if (isset($improved_token_set[$token])) {
                $preserved_tokens += min($count, $improved_token_set[$token]);
            }
        }
        
        $changed_tokens = $total_original_tokens - $preserved_tokens;
        $token_change_ratio = $changed_tokens / $total_original_tokens;
        
        // Calculate surgical edit score (0-100)
        // Higher score = more surgical (fewer changes)
        $surgical_edit_score = (1 - $token_change_ratio) * 100;
        
        // Detect sentence reconstruction
        $reconstruction_analysis = $this->detect_sentence_reconstruction($original_text, $improved_text);
        
        return array(
            'total_original_tokens' => $total_original_tokens,
            'total_improved_tokens' => $total_improved_tokens,
            'preserved_tokens' => $preserved_tokens,
            'changed_tokens' => $changed_tokens,
            'token_change_ratio' => $token_change_ratio,
            'surgical_edit_score' => round($surgical_edit_score),
            'reconstructed_sentences_count' => $reconstruction_analysis['reconstructed_count'],
            'reconstruction_detected' => $reconstruction_analysis['reconstruction_detected'],
        );
    }
    
    /**
     * Detect sentence reconstruction
     * 
     * Detects if sentences were unnecessarily rebuilt with same meaning.
     * 
     * @param string $original Original text
     * @param string $improved Improved text
     * @return array Reconstruction analysis
     */
    private function detect_sentence_reconstruction($original, $improved) {
        // Split into sentences
        $original_sentences = preg_split('/[.!?]+/', $original, -1, PREG_SPLIT_NO_EMPTY);
        $improved_sentences = preg_split('/[.!?]+/', $improved, -1, PREG_SPLIT_NO_EMPTY);
        
        $original_sentences = array_map('trim', array_filter($original_sentences));
        $improved_sentences = array_map('trim', array_filter($improved_sentences));
        
        $reconstructed_count = 0;
        $reconstruction_detected = false;
        
        foreach ($original_sentences as $i => $orig_sentence) {
            if (!isset($improved_sentences[$i])) {
                continue;
            }
            
            $imp_sentence = $improved_sentences[$i];
            
            // Calculate word-level similarity
            $orig_words = preg_split('/\s+/', strtolower($orig_sentence), -1, PREG_SPLIT_NO_EMPTY);
            $imp_words = preg_split('/\s+/', strtolower($imp_sentence), -1, PREG_SPLIT_NO_EMPTY);
            
            // Calculate word order preservation
            $word_order_preserved = $this->calculate_word_order_preservation($orig_words, $imp_words);
            
            // If word order changed significantly but words are similar,
            // it's likely a reconstruction
            $word_overlap = count(array_intersect($orig_words, $imp_words)) / max(count($orig_words), 1);
            
            // Reconstruction detected if:
            // - High word overlap (> 60%) but low order preservation (< 50%)
            // - Indicates same words rearranged unnecessarily
            if ($word_overlap > 0.6 && $word_order_preserved < 0.5) {
                $reconstructed_count++;
                $reconstruction_detected = true;
            }
        }
        
        return array(
            'reconstructed_count' => $reconstructed_count,
            'reconstruction_detected' => $reconstruction_detected,
        );
    }
    
    /**
     * Calculate word order preservation
     * 
     * Measures how much the word order was preserved.
     * 
     * @param array $original_words Original words
     * @param array $improved_words Improved words
     * @return float Preservation score (0-1)
     */
    private function calculate_word_order_preservation($original_words, $improved_words) {
        if (empty($original_words) || empty($improved_words)) {
            return 0;
        }
        
        // Find common words
        $common_words = array_intersect($original_words, $improved_words);
        
        if (empty($common_words)) {
            return 0;
        }
        
        // Check if common words appear in same order
        $original_positions = array();
        $improved_positions = array();
        
        foreach ($common_words as $word) {
            $orig_pos = array_search($word, $original_words);
            $imp_pos = array_search($word, $improved_words);
            
            if ($orig_pos !== false && $imp_pos !== false) {
                $original_positions[] = $orig_pos;
                $improved_positions[] = $imp_pos;
            }
        }
        
        // Calculate order preservation
        // If positions are in same relative order, score is high
        $order_preserved = 0;
        for ($i = 0; $i < count($original_positions) - 1; $i++) {
            if (($original_positions[$i] < $original_positions[$i + 1]) === 
                ($improved_positions[$i] < $improved_positions[$i + 1])) {
                $order_preserved++;
            }
        }
        
        $total_comparisons = max(count($original_positions) - 1, 1);
        return $order_preserved / $total_comparisons;
    }
    
    /**
     * Detect FAQ section
     * 
     * Detects if section is a genuine FAQ section.
     * 
     * @param string $section_content Section content
     * @return bool True if FAQ, false otherwise
     */
    private function detect_faq_section($section_content) {
        // Check for FAQ headings
        $faq_heading_patterns = array(
            '/Sıkça\s+Sorulan\s+Sorular/i',
            '/SSS/i',
            '/\bFAQ\b/i',
            '/Frequently\s+Asked\s+Questions/i',
        );
        
        $has_faq_heading = false;
        foreach ($faq_heading_patterns as $pattern) {
            if (preg_match($pattern, $section_content)) {
                $has_faq_heading = true;
                break;
            }
        }
        
        // Check for Q&A format
        $has_qa_format = $this->has_qa_format($section_content);
        
        // Must have both FAQ heading AND Q&A format
        return $has_faq_heading && $has_qa_format;
    }
    
    /**
     * Check if content has Q&A format
     * 
     * @param string $content Content to check
     * @return bool True if has Q&A format
     */
    private function has_qa_format($content) {
        // Check for questions (headings ending with ?)
        $question_patterns = array(
            '/###?\s+.+\?/m', // Markdown question
            '/<h[2-6][^>]*>.+\?<\/h[2-6]>/i', // HTML question
        );
        
        $question_count = 0;
        foreach ($question_patterns as $pattern) {
            $question_count += preg_match_all($pattern, $content);
        }
        
        // Must have at least 2 questions for FAQ format
        return $question_count >= 2;
    }
    
    /**
     * Check if content is broken (DEPRECATED - kept for backward compatibility)
     * 
     * @param string $content
     * @return bool
     */
    private function is_content_broken($content) {
        // This method is deprecated but kept for backward compatibility
        // Validation is now handled by validate_improvement()
        
        // Check for unclosed markdown code blocks
        $backtick_count = substr_count($content, '```');
        if ($backtick_count % 2 !== 0) {
            return true;
        }
        
        // Check for suspiciously short content
        if (strlen($content) < 50) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Generate content diff (word-level and sentence-level)
     * 
     * Generates visual diff between original and improved content.
     * Returns HTML with inline diff markup for premium UX.
     * 
     * @param string $original Original content
     * @param string $improved Improved content
     * @return array Diff data with HTML and statistics
     */
    public function generate_content_diff($original, $improved) {
        // Use new Smart Diff Engine (Task 8)
        $smart_diff = new DODO_Smart_Diff();
        return $smart_diff->generate($original, $improved);
    }
    
    /**
     * Calculate diff operations (simple word-level diff)
     * 
     * @param array $original_words
     * @param array $improved_words
     * @return array Operations array
     */
    private function calculate_diff_operations($original_words, $improved_words) {
        $operations = array();
        
        // Simple diff algorithm (not perfect but fast)
        $orig_index = 0;
        $imp_index = 0;
        
        while ($orig_index < count($original_words) || $imp_index < count($improved_words)) {
            // Both have words
            if ($orig_index < count($original_words) && $imp_index < count($improved_words)) {
                $orig_word = $original_words[$orig_index];
                $imp_word = $improved_words[$imp_index];
                
                // Skip whitespace
                if (trim($orig_word) === '' && trim($imp_word) === '') {
                    $operations[] = array('type' => 'unchanged', 'text' => $orig_word);
                    $orig_index++;
                    $imp_index++;
                    continue;
                }
                
                if (trim($orig_word) === '') {
                    $operations[] = array('type' => 'unchanged', 'text' => $orig_word);
                    $orig_index++;
                    continue;
                }
                
                if (trim($imp_word) === '') {
                    $operations[] = array('type' => 'unchanged', 'text' => $imp_word);
                    $imp_index++;
                    continue;
                }
                
                // Words match
                if (strtolower(trim($orig_word)) === strtolower(trim($imp_word))) {
                    $operations[] = array('type' => 'unchanged', 'text' => $orig_word);
                    $orig_index++;
                    $imp_index++;
                } else {
                    // Look ahead to see if word appears later
                    $found_in_improved = false;
                    for ($i = $imp_index + 1; $i < min($imp_index + 5, count($improved_words)); $i++) {
                        if (strtolower(trim($original_words[$orig_index])) === strtolower(trim($improved_words[$i]))) {
                            // Word was added before this
                            $operations[] = array('type' => 'added', 'text' => $imp_word);
                            $imp_index++;
                            $found_in_improved = true;
                            break;
                        }
                    }
                    
                    if (!$found_in_improved) {
                        // Check if original word appears later in improved
                        $found_in_original = false;
                        for ($i = $orig_index + 1; $i < min($orig_index + 5, count($original_words)); $i++) {
                            if (strtolower(trim($original_words[$i])) === strtolower(trim($improved_words[$imp_index]))) {
                                // Word was removed
                                $operations[] = array('type' => 'removed', 'text' => $orig_word);
                                $orig_index++;
                                $found_in_original = true;
                                break;
                            }
                        }
                        
                        if (!$found_in_original) {
                            // Words are different - modified
                            $operations[] = array('type' => 'removed', 'text' => $orig_word);
                            $operations[] = array('type' => 'added', 'text' => $imp_word);
                            $orig_index++;
                            $imp_index++;
                        }
                    }
                }
            } elseif ($orig_index < count($original_words)) {
                // Only original has words left - removed
                $operations[] = array('type' => 'removed', 'text' => $original_words[$orig_index]);
                $orig_index++;
            } else {
                // Only improved has words left - added
                $operations[] = array('type' => 'added', 'text' => $improved_words[$imp_index]);
                $imp_index++;
            }
        }
        
        return $operations;
    }
    
    /**
     * Render inline diff HTML
     * 
     * @param array $operations
     * @return string HTML
     */
    private function render_inline_diff($operations) {
        $html = '<div class="dodo-diff-content">';
        
        foreach ($operations as $op) {
            $text = esc_html($op['text']);
            
            switch ($op['type']) {
                case 'added':
                    $html .= '<ins class="dodo-diff-added">' . $text . '</ins>';
                    break;
                case 'removed':
                    $html .= '<del class="dodo-diff-removed">' . $text . '</del>';
                    break;
                case 'unchanged':
                    $html .= '<span class="dodo-diff-unchanged">' . $text . '</span>';
                    break;
            }
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Calculate diff statistics
     * 
     * @param array $operations
     * @return array Statistics
     */
    private function calculate_diff_statistics($operations) {
        $added = 0;
        $removed = 0;
        $unchanged = 0;
        
        foreach ($operations as $op) {
            // Skip whitespace
            if (trim($op['text']) === '') {
                continue;
            }
            
            switch ($op['type']) {
                case 'added':
                    $added++;
                    break;
                case 'removed':
                    $removed++;
                    break;
                case 'unchanged':
                    $unchanged++;
                    break;
            }
        }
        
        $total = $added + $removed + $unchanged;
        $modified = min($added, $removed); // Pairs of add/remove = modifications
        $pure_added = $added - $modified;
        $pure_removed = $removed - $modified;
        
        $change_percentage = $total > 0 ? round((($added + $removed) / $total) * 100) : 0;
        
        return array(
            'added' => $pure_added,
            'removed' => $pure_removed,
            'modified' => $modified,
            'unchanged' => $unchanged,
            'total' => $total,
            'change_percentage' => $change_percentage,
        );
    }
    
    /**
     * Generate sentence-level diff
     * 
     * @param string $original
     * @param string $improved
     * @return array Sentence diff data
     */
    private function generate_sentence_diff($original, $improved) {
        // Split into sentences
        $original_sentences = preg_split('/[.!?]+/', $original, -1, PREG_SPLIT_NO_EMPTY);
        $improved_sentences = preg_split('/[.!?]+/', $improved, -1, PREG_SPLIT_NO_EMPTY);
        
        $original_sentences = array_map('trim', $original_sentences);
        $improved_sentences = array_map('trim', $improved_sentences);
        
        $html = '<div class="dodo-sentence-diff">';
        
        $max_sentences = max(count($original_sentences), count($improved_sentences));
        
        for ($i = 0; $i < $max_sentences; $i++) {
            $orig_sentence = isset($original_sentences[$i]) ? $original_sentences[$i] : '';
            $imp_sentence = isset($improved_sentences[$i]) ? $improved_sentences[$i] : '';
            
            if ($orig_sentence === '' && $imp_sentence !== '') {
                // Added sentence
                $html .= '<div class="dodo-sentence-added">';
                $html .= '<span class="dodo-sentence-label">+ Added:</span> ';
                $html .= esc_html($imp_sentence) . '.';
                $html .= '</div>';
            } elseif ($orig_sentence !== '' && $imp_sentence === '') {
                // Removed sentence
                $html .= '<div class="dodo-sentence-removed">';
                $html .= '<span class="dodo-sentence-label">- Removed:</span> ';
                $html .= esc_html($orig_sentence) . '.';
                $html .= '</div>';
            } elseif ($orig_sentence !== '' && $imp_sentence !== '') {
                // Compare sentences
                $similarity = similar_text(strtolower($orig_sentence), strtolower($imp_sentence), $percent);
                
                if ($percent > 90) {
                    // Mostly unchanged
                    $html .= '<div class="dodo-sentence-unchanged">';
                    $html .= esc_html($imp_sentence) . '.';
                    $html .= '</div>';
                } elseif ($percent > 50) {
                    // Modified
                    $html .= '<div class="dodo-sentence-modified">';
                    $html .= '<span class="dodo-sentence-label">~ Modified:</span> ';
                    $html .= esc_html($imp_sentence) . '.';
                    $html .= '</div>';
                } else {
                    // Heavily changed
                    $html .= '<div class="dodo-sentence-removed">';
                    $html .= '<span class="dodo-sentence-label">- Old:</span> ';
                    $html .= esc_html($orig_sentence) . '.';
                    $html .= '</div>';
                    $html .= '<div class="dodo-sentence-added">';
                    $html .= '<span class="dodo-sentence-label">+ New:</span> ';
                    $html .= esc_html($imp_sentence) . '.';
                    $html .= '</div>';
                }
            }
        }
        
        $html .= '</div>';
        
        return array(
            'html' => $html,
        );
    }
    
    /**
     * Get supported improve types
     * 
     * @return array
     */
    public function get_improve_types() {
        return self::IMPROVE_TYPES;
    }
    
    /**
     * Get token limit for improve type
     * 
     * @param string $improve_type
     * @return int
     */
    public function get_token_limit($improve_type) {
        return self::TOKEN_LIMITS[$improve_type] ?? 1000;
    }
    
    /**
     * Test improver with sample FAQ
     * 
     * @return array Test results
     */
    public function test_improver() {
        $test_results = array();
        
        // Sample FAQ content
        $sample_faq = "## Sıkça Sorulan Sorular\n\n";
        $sample_faq .= "### Serigrafi nedir?\n\n";
        $sample_faq .= "Serigrafi bir baskı tekniğidir.\n\n";
        $sample_faq .= "### Serigrafi nasıl yapılır?\n\n";
        $sample_faq .= "Önce şablon hazırlanır, sonra baskı yapılır.\n\n";
        
        $focus_keyword = "serigrafi";
        
        // Test FAQ improve
        $test_results['original'] = $sample_faq;
        $test_results['improve_type'] = 'faq_improve';
        $test_results['focus_keyword'] = $focus_keyword;
        
        $improved = $this->improve_section($sample_faq, 'faq_improve', $focus_keyword);
        
        if (is_wp_error($improved)) {
            $test_results['status'] = 'error';
            $test_results['error'] = $improved->get_error_message();
            $test_results['improved'] = null;
        } else {
            $test_results['status'] = 'success';
            $test_results['improved'] = $improved;
            $test_results['original_length'] = strlen($sample_faq);
            $test_results['improved_length'] = strlen($improved);
            $test_results['length_diff'] = strlen($improved) - strlen($sample_faq);
        }
        
        return $test_results;
    }
    
    /**
     * Batch improve multiple sections
     * 
     * @param array $sections Array of sections to improve
     * @param string $improve_type
     * @param string $focus_keyword
     * @param array $additional_context
     * @return array Results
     */
    public function batch_improve($sections, $improve_type, $focus_keyword, $additional_context = array()) {
        $results = array();
        
        foreach ($sections as $index => $section_content) {
            $improved = $this->improve_section($section_content, $improve_type, $focus_keyword, $additional_context);
            
            $results[$index] = array(
                'original' => $section_content,
                'improved' => $improved,
                'success' => !is_wp_error($improved),
                'error' => is_wp_error($improved) ? $improved->get_error_message() : null,
            );
            
            // Rate limiting
            if ($index < count($sections) - 1) {
                sleep(1);
            }
        }
        
        return $results;
    }
}
