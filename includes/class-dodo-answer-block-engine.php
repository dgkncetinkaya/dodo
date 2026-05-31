<?php
/**
 * AI Answer Block Engine
 * 
 * Generates LLM-friendly content blocks for better AI retrieval
 * 
 * @package DODO_AI_SEO
 * @since 3.0.0 (Sprint 5 - Phase 2)
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Answer_Block_Engine {
    
    /**
     * OpenAI instance
     */
    private $openai;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->openai = new DODO_OpenAI();
    }
    
    /**
     * Generate all answer blocks for content
     * 
     * @param string $title Post title
     * @param string $focus_keyword Focus keyword
     * @param string $content Main content
     * @param array $options Block options
     * @return array Generated blocks
     */
    public function generate_blocks($title, $focus_keyword, $content, $options = array()) {
        $blocks = array();
        
        $default_options = array(
            'short_answer' => true,
            'featured_snippet' => true,
            'direct_answer' => true,
            'comparison' => false,
            'ai_summary' => true,
            'faq' => true,
        );
        
        $options = wp_parse_args($options, $default_options);
        
        // Check cache first
        $cache_key = 'dodo_answer_blocks_' . md5($title . $focus_keyword . serialize($options));
        $cached_blocks = get_transient($cache_key);
        
        if ($cached_blocks !== false) {
            return $cached_blocks;
        }
        
        // Check API call limit
        $max_calls = $this->get_max_api_calls();
        $enabled_count = count(array_filter($options));
        
        if ($enabled_count > $max_calls) {
            return new WP_Error('api_limit_exceeded', sprintf(
                __('API call limit exceeded. Maximum %d blocks allowed, %d requested.', 'dodo-ai-seo'),
                $max_calls,
                $enabled_count
            ));
        }
        
        // Generate enabled blocks
        if ($options['short_answer']) {
            $blocks['short_answer'] = $this->generate_short_answer($title, $focus_keyword, $content);
        }
        
        if ($options['featured_snippet']) {
            $blocks['featured_snippet'] = $this->generate_featured_snippet($title, $focus_keyword, $content);
        }
        
        if ($options['direct_answer']) {
            $blocks['direct_answer'] = $this->generate_direct_answer($title, $focus_keyword, $content);
        }
        
        if ($options['comparison']) {
            $blocks['comparison'] = $this->generate_comparison($title, $focus_keyword, $content);
        }
        
        if ($options['ai_summary']) {
            $blocks['ai_summary'] = $this->generate_ai_summary($title, $focus_keyword, $content);
        }
        
        if ($options['faq']) {
            $blocks['faq'] = $this->generate_faq($title, $focus_keyword, $content);
        }
        
        // Cache for 24 hours
        set_transient($cache_key, $blocks, 24 * HOUR_IN_SECONDS);
        
        return $blocks;
    }
    
    /**
     * Generate short answer block (2-3 sentences)
     */
    public function generate_short_answer($title, $focus_keyword, $content) {
        $prompt = "Based on this article title and content, write a SHORT ANSWER (2-3 sentences, 40-60 words) that directly answers the main question.

Title: {$title}
Focus Keyword: {$focus_keyword}

Content preview: " . substr(strip_tags($content), 0, 500) . "

Requirements:
- 2-3 sentences only
- 40-60 words
- Direct and concise
- Standalone (can be quoted without context)
- No fluff or filler
- Answer the main question immediately

Short Answer:";

        $response = $this->openai->generate_text($prompt, array(
            'max_tokens' => 150,
            'temperature' => 0.3,
        ));
        
        if (is_wp_error($response)) {
            return '';
        }
        
        return trim($response);
    }
    
    /**
     * Generate featured snippet block (40-60 words)
     */
    public function generate_featured_snippet($title, $focus_keyword, $content) {
        $prompt = "Based on this article, write a FEATURED SNIPPET optimized paragraph.

Title: {$title}
Focus Keyword: {$focus_keyword}

Content preview: " . substr(strip_tags($content), 0, 500) . "

Requirements:
- 40-60 words
- Definition or explanation format
- Include the focus keyword naturally
- Structured and clear
- Google Featured Snippet optimized
- Can stand alone

Featured Snippet:";

        $response = $this->openai->generate_text($prompt, array(
            'max_tokens' => 150,
            'temperature' => 0.3,
        ));
        
        if (is_wp_error($response)) {
            return '';
        }
        
        return trim($response);
    }
    
    /**
     * Generate direct answer paragraph (Question + Answer format)
     */
    public function generate_direct_answer($title, $focus_keyword, $content) {
        $prompt = "Based on this article, create a DIRECT ANSWER paragraph in Question + Answer format.

Title: {$title}
Focus Keyword: {$focus_keyword}

Content preview: " . substr(strip_tags($content), 0, 500) . "

Requirements:
- Start with the question (bold)
- Follow with clear answer
- 60-100 words total
- No fluff
- Conversational but professional
- LLM-friendly format

Format:
**Question:** [question]
[Answer paragraph]

Direct Answer:";

        $response = $this->openai->generate_text($prompt, array(
            'max_tokens' => 200,
            'temperature' => 0.4,
        ));
        
        if (is_wp_error($response)) {
            return '';
        }
        
        return trim($response);
    }
    
    /**
     * Generate comparison section
     */
    public function generate_comparison($title, $focus_keyword, $content) {
        $prompt = "Based on this article, create a COMPARISON section if applicable.

Title: {$title}
Focus Keyword: {$focus_keyword}

Content preview: " . substr(strip_tags($content), 0, 500) . "

Requirements:
- Only if comparison is relevant
- Side-by-side format
- Clear differentiators
- Bullet points or table format
- 100-150 words
- Structured for LLM extraction

If comparison is not relevant, return: NOT_APPLICABLE

Comparison:";

        $response = $this->openai->generate_text($prompt, array(
            'max_tokens' => 300,
            'temperature' => 0.4,
        ));
        
        if (is_wp_error($response) || strpos($response, 'NOT_APPLICABLE') !== false) {
            return '';
        }
        
        return trim($response);
    }
    
    /**
     * Generate AI summary section (key points)
     */
    public function generate_ai_summary($title, $focus_keyword, $content) {
        $prompt = "Based on this article, create an AI SUMMARY with key points.

Title: {$title}
Focus Keyword: {$focus_keyword}

Content preview: " . substr(strip_tags($content), 0, 800) . "

Requirements:
- 4-6 key points
- Bullet list format
- Each point: 10-15 words
- Scannable and clear
- LLM extraction friendly
- Cover main topics

Format:
• [Key point 1]
• [Key point 2]
• [Key point 3]
...

AI Summary:";

        $response = $this->openai->generate_text($prompt, array(
            'max_tokens' => 250,
            'temperature' => 0.3,
        ));
        
        if (is_wp_error($response)) {
            return '';
        }
        
        return trim($response);
    }
    
    /**
     * Generate FAQ section
     */
    public function generate_faq($title, $focus_keyword, $content) {
        $prompt = "Based on this article, create a FAQ section with related questions and brief answers.

Title: {$title}
Focus Keyword: {$focus_keyword}

Content preview: " . substr(strip_tags($content), 0, 800) . "

Requirements:
- 3-5 related questions
- Brief answers (20-40 words each)
- Natural questions users would ask
- Conversational tone
- Schema markup ready
- LLM-friendly format

Format:
**Q: [Question 1]**
A: [Answer 1]

**Q: [Question 2]**
A: [Answer 2]

FAQ:";

        $response = $this->openai->generate_text($prompt, array(
            'max_tokens' => 400,
            'temperature' => 0.5,
        ));
        
        if (is_wp_error($response)) {
            return '';
        }
        
        return trim($response);
    }
    
    /**
     * Insert blocks into content
     * 
     * @param string $content Original content
     * @param array $blocks Generated blocks
     * @param array $placement Placement options
     * @return string Content with blocks
     */
    public function insert_blocks_into_content($content, $blocks, $placement = array()) {
        $default_placement = array(
            'short_answer' => 'start', // start, end, none
            'featured_snippet' => 'start',
            'direct_answer' => 'after_intro',
            'comparison' => 'middle',
            'ai_summary' => 'end',
            'faq' => 'end',
        );
        
        $placement = wp_parse_args($placement, $default_placement);
        
        // Split content into paragraphs
        $paragraphs = explode("\n\n", $content);
        $total_paragraphs = count($paragraphs);
        
        // Prepare blocks with HTML wrapper
        $formatted_blocks = array();
        
        foreach ($blocks as $type => $block_content) {
            if (empty($block_content)) {
                continue;
            }
            
            $formatted_blocks[$type] = $this->format_block($type, $block_content);
        }
        
        // Insert blocks based on placement
        $result = array();
        
        foreach ($paragraphs as $index => $paragraph) {
            // Start position
            if ($index === 0) {
                if (isset($formatted_blocks['short_answer']) && $placement['short_answer'] === 'start') {
                    $result[] = $formatted_blocks['short_answer'];
                }
                if (isset($formatted_blocks['featured_snippet']) && $placement['featured_snippet'] === 'start') {
                    $result[] = $formatted_blocks['featured_snippet'];
                }
            }
            
            // After intro (after first paragraph)
            if ($index === 1) {
                if (isset($formatted_blocks['direct_answer']) && $placement['direct_answer'] === 'after_intro') {
                    $result[] = $formatted_blocks['direct_answer'];
                }
            }
            
            // Middle position
            if ($index === floor($total_paragraphs / 2)) {
                if (isset($formatted_blocks['comparison']) && $placement['comparison'] === 'middle') {
                    $result[] = $formatted_blocks['comparison'];
                }
            }
            
            $result[] = $paragraph;
            
            // End position
            if ($index === $total_paragraphs - 1) {
                if (isset($formatted_blocks['ai_summary']) && $placement['ai_summary'] === 'end') {
                    $result[] = $formatted_blocks['ai_summary'];
                }
                if (isset($formatted_blocks['faq']) && $placement['faq'] === 'end') {
                    $result[] = $formatted_blocks['faq'];
                }
            }
        }
        
        return implode("\n\n", $result);
    }
    
    /**
     * Format block with HTML wrapper
     */
    private function format_block($type, $content) {
        $labels = array(
            'short_answer' => 'Kısa Cevap',
            'featured_snippet' => 'Öne Çıkan Snippet',
            'direct_answer' => 'Doğrudan Cevap',
            'comparison' => 'Karşılaştırma',
            'ai_summary' => 'Özet',
            'faq' => 'Sık Sorulan Sorular',
        );
        
        $label = $labels[$type] ?? ucfirst($type);
        
        // Add HTML wrapper with class for styling
        $html = '<div class="dodo-answer-block dodo-answer-block-' . esc_attr($type) . '">';
        $html .= '<div class="dodo-answer-block-label">' . esc_html($label) . '</div>';
        $html .= '<div class="dodo-answer-block-content">' . wpautop($content) . '</div>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Get block settings from options
     */
    public function get_block_settings() {
        $settings = get_option('dodo_ai_seo_settings', array());
        
        return array(
            'enabled' => isset($settings['answer_blocks_enabled']) ? $settings['answer_blocks_enabled'] : false, // DEFAULT OFF for cost safety
            'short_answer' => isset($settings['block_short_answer']) ? $settings['block_short_answer'] : true,
            'featured_snippet' => isset($settings['block_featured_snippet']) ? $settings['block_featured_snippet'] : false, // Reduced default
            'direct_answer' => isset($settings['block_direct_answer']) ? $settings['block_direct_answer'] : false, // Reduced default
            'comparison' => isset($settings['block_comparison']) ? $settings['block_comparison'] : false,
            'ai_summary' => isset($settings['block_ai_summary']) ? $settings['block_ai_summary'] : false, // Reduced default
            'faq' => isset($settings['block_faq']) ? $settings['block_faq'] : false, // Reduced default
        );
    }
    
    /**
     * Save block settings
     */
    public function save_block_settings($new_settings) {
        $settings = get_option('dodo_ai_seo_settings', array());
        
        $settings['answer_blocks_enabled'] = isset($new_settings['enabled']) ? $new_settings['enabled'] : true;
        $settings['block_short_answer'] = isset($new_settings['short_answer']) ? $new_settings['short_answer'] : true;
        $settings['block_featured_snippet'] = isset($new_settings['featured_snippet']) ? $new_settings['featured_snippet'] : true;
        $settings['block_direct_answer'] = isset($new_settings['direct_answer']) ? $new_settings['direct_answer'] : true;
        $settings['block_comparison'] = isset($new_settings['comparison']) ? $new_settings['comparison'] : false;
        $settings['block_ai_summary'] = isset($new_settings['ai_summary']) ? $new_settings['ai_summary'] : true;
        $settings['block_faq'] = isset($new_settings['faq']) ? $new_settings['faq'] : true;
        $settings['answer_blocks_max_calls'] = isset($new_settings['max_calls']) ? intval($new_settings['max_calls']) : 3;
        
        update_option('dodo_ai_seo_settings', $settings);
    }
    
    /**
     * Get max API calls limit
     */
    private function get_max_api_calls() {
        $settings = get_option('dodo_ai_seo_settings', array());
        return isset($settings['answer_blocks_max_calls']) ? intval($settings['answer_blocks_max_calls']) : 3;
    }
    
    /**
     * Estimate cost for block generation
     * 
     * @param array $options Block options
     * @return array Cost estimation
     */
    public function estimate_cost($options = array()) {
        $enabled_blocks = array_filter($options);
        $block_count = count($enabled_blocks);
        
        // Average tokens per block type
        $token_estimates = array(
            'short_answer' => 150,
            'featured_snippet' => 150,
            'direct_answer' => 200,
            'comparison' => 300,
            'ai_summary' => 250,
            'faq' => 400,
        );
        
        $total_tokens = 0;
        foreach ($enabled_blocks as $block_type => $enabled) {
            if ($enabled && isset($token_estimates[$block_type])) {
                $total_tokens += $token_estimates[$block_type];
            }
        }
        
        // GPT-4o pricing (approximate)
        $cost_per_1k_tokens = 0.005; // $0.005 per 1K tokens
        $estimated_cost = ($total_tokens / 1000) * $cost_per_1k_tokens;
        
        return array(
            'block_count' => $block_count,
            'estimated_tokens' => $total_tokens,
            'estimated_cost' => $estimated_cost,
            'cost_formatted' => '$' . number_format($estimated_cost, 4),
        );
    }
    
    /**
     * Clear answer blocks cache
     */
    public function clear_cache($post_id = null) {
        global $wpdb;
        
        if ($post_id) {
            // Clear specific post cache
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                    '_transient_dodo_answer_blocks_%'
                )
            );
        } else {
            // Clear all answer blocks cache
            $wpdb->query(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_dodo_answer_blocks_%'"
            );
        }
    }
}
