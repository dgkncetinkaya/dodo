<?php
/**
 * Content Strategy Engine
 * 
 * Decides optimal content format and structure based on keyword analysis.
 * Recommends: blog post, landing page, comparison, glossary, pillar content, etc.
 *
 * @package DODO_AI_SEO
 * @subpackage Brain
 * @since 2.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Prevent duplicate class declaration
if (class_exists('Dodo_Content_Strategy')) {
    return;
}

class Dodo_Content_Strategy {
    
    /**
     * Content format types
     */
    const FORMAT_BLOG_POST = 'blog_post';
    const FORMAT_LANDING_PAGE = 'landing_page';
    const FORMAT_COMPARISON = 'comparison';
    const FORMAT_GLOSSARY = 'glossary';
    const FORMAT_PILLAR = 'pillar_content';
    const FORMAT_LISTICLE = 'listicle';
    const FORMAT_HOWTO = 'how_to_guide';
    const FORMAT_CASE_STUDY = 'case_study';
    const FORMAT_REVIEW = 'review';
    const FORMAT_FAQ = 'faq_page';
    
    /**
     * Confidence thresholds
     */
    const CONFIDENCE_HIGH = 80;
    const CONFIDENCE_MEDIUM = 60;
    const CONFIDENCE_LOW = 40;
    
    /**
     * Debug mode
     */
    private $debug = true;
    
    /**
     * Keyword Intelligence Engine
     */
    private $keyword_engine;
    
    /**
     * Intent Engine
     */
    private $intent_engine;
    
    /**
     * Constructor
     */
    public function __construct() {
        require_once plugin_dir_path(__FILE__) . 'class-dodo-keyword-intelligence.php';
        require_once plugin_dir_path(__FILE__) . 'class-dodo-intent-engine.php';
        
        $this->keyword_engine = new Dodo_Keyword_Intelligence();
        $this->intent_engine = new Dodo_Intent_Engine();
    }
    
    /**
     * Analyze and recommend content strategy
     *
     * @param string $keyword Target keyword
     * @param array $options Strategy options
     * @return array Strategy recommendation with reasoning
     */
    public function analyze($keyword, $options = []) {
        $start_time = microtime(true);
        
        $defaults = [
            'user_preference' => null, // User can override
            'site_context' => 'blog',  // blog, ecommerce, saas, etc.
        ];
        $options = wp_parse_args($options, $defaults);
        
        // Get keyword intelligence
        $keyword_analysis = $this->keyword_engine->analyze($keyword);
        
        // Get intent analysis
        $intent_analysis = $this->intent_engine->analyze($keyword);
        
        // Detect format signals from keyword
        $format_signals = $this->detect_format_signals($keyword);
        
        // Calculate format scores
        $format_scores = $this->calculate_format_scores(
            $keyword_analysis,
            $intent_analysis,
            $format_signals
        );
        
        // Get recommended format
        $recommended_format = $this->get_recommended_format($format_scores);
        
        // Generate structure recommendations
        $structure = $this->recommend_structure($recommended_format, $keyword_analysis, $intent_analysis);
        
        // Generate content elements
        $elements = $this->recommend_elements($recommended_format, $intent_analysis);
        
        // Calculate confidence
        $confidence = $this->calculate_confidence($format_scores, $recommended_format);
        
        $execution_time = round((microtime(true) - $start_time) * 1000, 2);
        
        if ($this->debug) {
            error_log(sprintf(
                '[DODO Content Strategy] Keyword: %s | Format: %s | Confidence: %d%% | Time: %sms',
                $keyword,
                $recommended_format,
                $confidence,
                $execution_time
            ));
        }
        
        return [
            'recommended_format' => $recommended_format,
            'confidence' => $confidence,
            'format_scores' => $format_scores,
            'structure' => $structure,
            'elements' => $elements,
            'reasoning' => $this->build_reasoning(
                $recommended_format,
                $keyword_analysis,
                $intent_analysis,
                $format_signals
            ),
            'execution_time_ms' => $execution_time,
        ];
    }
    
    /**
     * Detect format signals from keyword
     */
    private function detect_format_signals($keyword) {
        $keyword_lower = strtolower($keyword);
        $signals = [];
        
        // Comparison signals
        $comparison_patterns = [
            'vs', 'versus', 'karşı', 'karşılaştırma', 'fark', 'difference',
            'hangisi', 'which', 'better', 'daha iyi'
        ];
        foreach ($comparison_patterns as $pattern) {
            if (strpos($keyword_lower, $pattern) !== false) {
                $signals['comparison'] = true;
                break;
            }
        }
        
        // How-to signals
        $howto_patterns = [
            'nasıl', 'how to', 'how do', 'adım', 'step', 'guide',
            'rehber', 'tutorial', 'öğren'
        ];
        foreach ($howto_patterns as $pattern) {
            if (strpos($keyword_lower, $pattern) !== false) {
                $signals['how_to'] = true;
                break;
            }
        }
        
        // Listicle signals
        $listicle_patterns = [
            'en iyi', 'top', 'best', 'liste', 'list', 'örnekleri',
            'examples', 'türleri', 'types'
        ];
        foreach ($listicle_patterns as $pattern) {
            if (strpos($keyword_lower, $pattern) !== false) {
                $signals['listicle'] = true;
                break;
            }
        }
        
        // Definition/Glossary signals
        $glossary_patterns = [
            'nedir', 'ne demek', 'what is', 'definition', 'tanım',
            'anlamı', 'meaning'
        ];
        foreach ($glossary_patterns as $pattern) {
            if (strpos($keyword_lower, $pattern) !== false) {
                $signals['glossary'] = true;
                break;
            }
        }
        
        // Review signals
        $review_patterns = [
            'inceleme', 'review', 'değerlendirme', 'yorumlar',
            'deneyim', 'experience'
        ];
        foreach ($review_patterns as $pattern) {
            if (strpos($keyword_lower, $pattern) !== false) {
                $signals['review'] = true;
                break;
            }
        }
        
        // FAQ signals
        $faq_patterns = [
            'sss', 'faq', 'soru', 'cevap', 'questions', 'answers'
        ];
        foreach ($faq_patterns as $pattern) {
            if (strpos($keyword_lower, $pattern) !== false) {
                $signals['faq'] = true;
                break;
            }
        }
        
        // Landing page signals (commercial/transactional)
        $landing_patterns = [
            'satın al', 'buy', 'fiyat', 'price', 'teklif', 'offer',
            'hizmet', 'service', 'ürün', 'product'
        ];
        foreach ($landing_patterns as $pattern) {
            if (strpos($keyword_lower, $pattern) !== false) {
                $signals['landing_page'] = true;
                break;
            }
        }
        
        return $signals;
    }
    
    /**
     * Calculate scores for each format
     */
    private function calculate_format_scores($keyword_analysis, $intent_analysis, $format_signals) {
        $scores = [];
        
        // Blog Post (default/fallback)
        $scores[self::FORMAT_BLOG_POST] = 50; // Base score
        if ($intent_analysis['primary_intent'] === 'informational') {
            $scores[self::FORMAT_BLOG_POST] += 30;
        }
        
        // Landing Page
        $scores[self::FORMAT_LANDING_PAGE] = 20;
        if ($intent_analysis['primary_intent'] === 'transactional') {
            $scores[self::FORMAT_LANDING_PAGE] += 40;
        }
        if ($keyword_analysis['commercial_value'] === 'high') {
            $scores[self::FORMAT_LANDING_PAGE] += 20;
        }
        if (isset($format_signals['landing_page'])) {
            $scores[self::FORMAT_LANDING_PAGE] += 20;
        }
        
        // Comparison
        $scores[self::FORMAT_COMPARISON] = 10;
        if (isset($format_signals['comparison'])) {
            $scores[self::FORMAT_COMPARISON] += 60;
        }
        if ($intent_analysis['primary_intent'] === 'commercial') {
            $scores[self::FORMAT_COMPARISON] += 20;
        }
        
        // Glossary
        $scores[self::FORMAT_GLOSSARY] = 10;
        if (isset($format_signals['glossary'])) {
            $scores[self::FORMAT_GLOSSARY] += 70;
        }
        if ($intent_analysis['primary_intent'] === 'informational') {
            $scores[self::FORMAT_GLOSSARY] += 10;
        }
        
        // Pillar Content
        $scores[self::FORMAT_PILLAR] = 20;
        if ($keyword_analysis['semantic_richness'] >= 80) {
            $scores[self::FORMAT_PILLAR] += 30;
        }
        if (isset($keyword_analysis['topical_authority_contribution']) && $keyword_analysis['topical_authority_contribution'] >= 80) {
            $scores[self::FORMAT_PILLAR] += 30;
        }
        
        // Listicle
        $scores[self::FORMAT_LISTICLE] = 15;
        if (isset($format_signals['listicle'])) {
            $scores[self::FORMAT_LISTICLE] += 60;
        }
        if ($intent_analysis['primary_intent'] === 'informational') {
            $scores[self::FORMAT_LISTICLE] += 15;
        }
        
        // How-to Guide
        $scores[self::FORMAT_HOWTO] = 15;
        if (isset($format_signals['how_to'])) {
            $scores[self::FORMAT_HOWTO] += 70;
        }
        if ($keyword_analysis['geo_suitability'] >= 70) {
            $scores[self::FORMAT_HOWTO] += 10;
        }
        
        // Review
        $scores[self::FORMAT_REVIEW] = 10;
        if (isset($format_signals['review'])) {
            $scores[self::FORMAT_REVIEW] += 60;
        }
        if ($intent_analysis['primary_intent'] === 'commercial') {
            $scores[self::FORMAT_REVIEW] += 20;
        }
        
        // FAQ Page
        $scores[self::FORMAT_FAQ] = 10;
        if (isset($format_signals['faq'])) {
            $scores[self::FORMAT_FAQ] += 70;
        }
        if ($keyword_analysis['geo_suitability'] >= 80) {
            $scores[self::FORMAT_FAQ] += 15;
        }
        
        // Normalize scores to 0-100
        foreach ($scores as $format => $score) {
            $scores[$format] = min(100, max(0, $score));
        }
        
        // Sort by score
        arsort($scores);
        
        return $scores;
    }
    
    /**
     * Get recommended format
     */
    private function get_recommended_format($format_scores) {
        // Return highest scoring format
        reset($format_scores);
        return key($format_scores);
    }
    
    /**
     * Recommend content structure
     */
    private function recommend_structure($format, $keyword_analysis, $intent_analysis) {
        $structure = [];
        
        switch ($format) {
            case self::FORMAT_BLOG_POST:
                $structure = [
                    'sections' => [
                        'introduction' => 'Hook + problem statement',
                        'main_content' => '3-5 H2 sections with supporting details',
                        'examples' => 'Real-world examples or case studies',
                        'conclusion' => 'Summary + CTA',
                        'faq' => 'Optional FAQ section',
                    ],
                    'recommended_length' => 'medium', // 1500-2000 words
                    'tone' => $intent_analysis['recommended_tone'],
                ];
                break;
                
            case self::FORMAT_LANDING_PAGE:
                $structure = [
                    'sections' => [
                        'hero' => 'Strong headline + value proposition',
                        'benefits' => '3-5 key benefits',
                        'features' => 'Feature breakdown',
                        'social_proof' => 'Testimonials or case studies',
                        'cta' => 'Multiple CTAs throughout',
                        'faq' => 'Address objections',
                    ],
                    'recommended_length' => 'medium', // 1000-1500 words
                    'tone' => 'persuasive',
                ];
                break;
                
            case self::FORMAT_COMPARISON:
                $structure = [
                    'sections' => [
                        'introduction' => 'What is being compared and why',
                        'comparison_table' => 'Side-by-side feature comparison',
                        'detailed_analysis' => 'Deep dive into each option',
                        'pros_cons' => 'Pros and cons for each',
                        'recommendation' => 'Which is best for whom',
                        'conclusion' => 'Final verdict + CTA',
                    ],
                    'recommended_length' => 'long', // 2000-2500 words
                    'tone' => 'objective',
                ];
                break;
                
            case self::FORMAT_GLOSSARY:
                $structure = [
                    'sections' => [
                        'definition' => 'Clear, concise definition',
                        'explanation' => 'Detailed explanation',
                        'examples' => '2-3 practical examples',
                        'related_terms' => 'Related concepts',
                        'faq' => 'Common questions',
                    ],
                    'recommended_length' => 'short', // 800-1200 words
                    'tone' => 'educational',
                ];
                break;
                
            case self::FORMAT_PILLAR:
                $structure = [
                    'sections' => [
                        'introduction' => 'Comprehensive overview',
                        'table_of_contents' => 'Detailed TOC with jump links',
                        'main_sections' => '8-12 major H2 sections',
                        'subsections' => 'Deep H3 coverage',
                        'internal_links' => 'Links to cluster content',
                        'resources' => 'Additional resources',
                        'conclusion' => 'Summary + next steps',
                    ],
                    'recommended_length' => 'very_long', // 3500+ words
                    'tone' => 'authoritative',
                ];
                break;
                
            case self::FORMAT_LISTICLE:
                $structure = [
                    'sections' => [
                        'introduction' => 'Why this list matters',
                        'list_items' => '5-15 numbered items',
                        'item_details' => 'Each item with explanation',
                        'conclusion' => 'Summary + CTA',
                    ],
                    'recommended_length' => 'medium', // 1500-2000 words
                    'tone' => 'engaging',
                ];
                break;
                
            case self::FORMAT_HOWTO:
                $structure = [
                    'sections' => [
                        'introduction' => 'What you will learn',
                        'prerequisites' => 'What you need before starting',
                        'step_by_step' => 'Numbered steps with details',
                        'tips' => 'Pro tips and best practices',
                        'troubleshooting' => 'Common issues and solutions',
                        'conclusion' => 'Summary + next steps',
                    ],
                    'recommended_length' => 'long', // 2000-2500 words
                    'tone' => 'instructional',
                ];
                break;
                
            case self::FORMAT_REVIEW:
                $structure = [
                    'sections' => [
                        'introduction' => 'What is being reviewed',
                        'overview' => 'Quick summary and rating',
                        'features' => 'Detailed feature analysis',
                        'pros_cons' => 'Advantages and disadvantages',
                        'pricing' => 'Cost analysis',
                        'verdict' => 'Final recommendation',
                    ],
                    'recommended_length' => 'long', // 2000-2500 words
                    'tone' => 'balanced',
                ];
                break;
                
            case self::FORMAT_FAQ:
                $structure = [
                    'sections' => [
                        'introduction' => 'Brief overview',
                        'questions' => '10-20 Q&A pairs',
                        'categories' => 'Group by topic if many questions',
                        'conclusion' => 'Where to get more help',
                    ],
                    'recommended_length' => 'medium', // 1200-1800 words
                    'tone' => 'conversational',
                ];
                break;
                
            default:
                $structure = [
                    'sections' => ['introduction', 'main_content', 'conclusion'],
                    'recommended_length' => 'medium',
                    'tone' => 'neutral',
                ];
        }
        
        return $structure;
    }
    
    /**
     * Recommend content elements
     */
    private function recommend_elements($format, $intent_analysis) {
        $elements = [
            'images' => true,
            'videos' => false,
            'tables' => false,
            'charts' => false,
            'cta_buttons' => false,
            'testimonials' => false,
            'code_blocks' => false,
            'downloads' => false,
        ];
        
        // Format-specific elements
        switch ($format) {
            case self::FORMAT_LANDING_PAGE:
                $elements['videos'] = true;
                $elements['cta_buttons'] = true;
                $elements['testimonials'] = true;
                break;
                
            case self::FORMAT_COMPARISON:
                $elements['tables'] = true;
                $elements['charts'] = true;
                break;
                
            case self::FORMAT_HOWTO:
                $elements['videos'] = true;
                $elements['code_blocks'] = true;
                break;
                
            case self::FORMAT_REVIEW:
                $elements['tables'] = true;
                $elements['charts'] = true;
                break;
                
            case self::FORMAT_PILLAR:
                $elements['videos'] = true;
                $elements['tables'] = true;
                $elements['downloads'] = true;
                break;
        }
        
        // Intent-based elements
        if ($intent_analysis['primary_intent'] === 'transactional') {
            $elements['cta_buttons'] = true;
        }
        
        return $elements;
    }
    
    /**
     * Calculate confidence in recommendation
     */
    private function calculate_confidence($format_scores, $recommended_format) {
        $top_score = $format_scores[$recommended_format];
        
        // Get second highest score
        $scores_copy = $format_scores;
        unset($scores_copy[$recommended_format]);
        $second_score = !empty($scores_copy) ? max($scores_copy) : 0;
        
        // Calculate confidence based on score gap
        $score_gap = $top_score - $second_score;
        
        if ($score_gap >= 30) {
            return min(100, $top_score + 10); // High confidence
        } elseif ($score_gap >= 15) {
            return $top_score; // Medium confidence
        } else {
            return max(50, $top_score - 10); // Low confidence
        }
    }
    
    /**
     * Build reasoning array
     */
    private function build_reasoning($format, $keyword_analysis, $intent_analysis, $format_signals) {
        $reasoning = [];
        
        $reasoning[] = sprintf('Recommended format: %s', $this->format_label($format));
        
        $reasoning[] = sprintf('Primary intent: %s', $intent_analysis['primary_intent']);
        
        if (!empty($format_signals)) {
            $reasoning[] = 'Format signals detected: ' . implode(', ', array_keys($format_signals));
        }
        
        if ($keyword_analysis['commercial_value'] === 'high') {
            $reasoning[] = 'High commercial value detected';
        }
        
        if ($keyword_analysis['geo_suitability'] >= 70) {
            $reasoning[] = 'High GEO suitability - optimize for answer engines';
        }
        
        return $reasoning;
    }
    
    /**
     * Get human-readable format label
     */
    private function format_label($format) {
        $labels = [
            self::FORMAT_BLOG_POST => 'Blog Post',
            self::FORMAT_LANDING_PAGE => 'Landing Page',
            self::FORMAT_COMPARISON => 'Comparison Article',
            self::FORMAT_GLOSSARY => 'Glossary/Definition',
            self::FORMAT_PILLAR => 'Pillar Content',
            self::FORMAT_LISTICLE => 'Listicle',
            self::FORMAT_HOWTO => 'How-to Guide',
            self::FORMAT_CASE_STUDY => 'Case Study',
            self::FORMAT_REVIEW => 'Review',
            self::FORMAT_FAQ => 'FAQ Page',
        ];
        
        return isset($labels[$format]) ? $labels[$format] : $format;
    }
}
