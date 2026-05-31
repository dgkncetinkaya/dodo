<?php
/**
 * Intent Engine
 * 
 * Detects search intent and recommends optimal content strategy:
 * content type, CTA density, tone, structure, GEO suitability.
 * 
 * @package DODO_AI_SEO
 * @subpackage Brain_Core
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Prevent duplicate class declaration
if (class_exists('DODO_Intent_Engine')) {
    return;
}

class DODO_Intent_Engine {
    
    /**
     * Intent types and their characteristics
     */
    private $intent_profiles = array(
        'informational' => array(
            'content_types' => array('blog_post', 'guide', 'tutorial', 'glossary'),
            'cta_density' => 'low',
            'recommended_tone' => 'technical',
            'structure' => 'educational',
            'geo_suitability' => 'high',
        ),
        'transactional' => array(
            'content_types' => array('landing_page', 'product_page', 'service_page'),
            'cta_density' => 'high',
            'recommended_tone' => 'persuasive',
            'structure' => 'conversion_focused',
            'geo_suitability' => 'low',
        ),
        'commercial' => array(
            'content_types' => array('comparison_page', 'review', 'best_of_list'),
            'cta_density' => 'medium',
            'recommended_tone' => 'conversational',
            'structure' => 'comparison',
            'geo_suitability' => 'medium',
        ),
        'navigational' => array(
            'content_types' => array('category_page', 'brand_page'),
            'cta_density' => 'medium',
            'recommended_tone' => 'professional',
            'structure' => 'directory',
            'geo_suitability' => 'low',
        ),
        'local' => array(
            'content_types' => array('local_landing', 'location_page'),
            'cta_density' => 'high',
            'recommended_tone' => 'conversational',
            'structure' => 'local_focused',
            'geo_suitability' => 'very_high',
        ),
    );
    
    /**
     * Analyze intent and provide recommendations
     * 
     * @param string $keyword
     * @param array $keyword_intelligence Optional pre-analyzed keyword data
     * @return array Intent analysis with recommendations
     */
    public function analyze($keyword, $keyword_intelligence = null) {
        $start_time = microtime(true);
        
        // Validate input
        if (empty(trim($keyword))) {
            return [
                'error' => true,
                'message' => 'Anahtar kelime boş olamaz.',
                'detected_intent' => 'informational',
                'confidence' => 0,
                'execution_time_ms' => 0
            ];
        }
        
        try {
            error_log("BRAIN: Intent Engine analyzing: {$keyword}");
            
            // If keyword intelligence not provided, do basic intent detection
            if (!$keyword_intelligence) {
                $intent = $this->detect_basic_intent($keyword);
                $confidence = 60; // Lower confidence without full analysis
            } else {
                $intent = $keyword_intelligence['intent'];
                $confidence = $keyword_intelligence['intent_confidence'];
            }
            
            // Get profile for detected intent
            $profile = $this->intent_profiles[$intent] ?? $this->intent_profiles['informational'];
            
            // Generate recommendations
            $recommendations = $this->generate_recommendations($intent, $profile, $keyword, $keyword_intelligence);
            
            $execution_time = round((microtime(true) - $start_time) * 1000, 2);
            
            $report = array(
                'keyword' => $keyword,
                'detected_intent' => $intent,
                'confidence' => $confidence,
                'recommended_content_type' => $recommendations['content_type'],
                'recommended_cta_density' => $profile['cta_density'],
                'recommended_tone' => $profile['recommended_tone'],
                'recommended_structure' => $profile['structure'],
                'geo_suitability' => $profile['geo_suitability'],
                'all_recommendations' => $recommendations,
                'reasoning' => $recommendations['reasoning'],
                'metadata' => array(
                    'execution_time_ms' => $execution_time,
                    'analyzed_at' => current_time('mysql'),
                ),
            );
            
            error_log("BRAIN: Intent Engine complete - Intent: {$intent}, Content Type: {$recommendations['content_type']}");
            
            return $report;
        } catch (Throwable $e) {
            error_log('DODO Intent Engine: Analysis error: ' . $e->getMessage());
            return [
                'error' => true,
                'message' => 'Niyet analizi sırasında hata oluştu.',
                'detected_intent' => 'informational',
                'confidence' => 0,
                'execution_time_ms' => round((microtime(true) - $start_time) * 1000, 2)
            ];
        }
    }
    
    /**
     * Detect basic intent without full keyword intelligence
     */
    private function detect_basic_intent($keyword) {
        $keyword_lower = mb_strtolower($keyword, 'UTF-8');
        
        // Local intent
        $local_terms = array('istanbul', 'ankara', 'yakınımda', 'near me', 'bölge');
        foreach ($local_terms as $term) {
            if (strpos($keyword_lower, $term) !== false) {
                return 'local';
            }
        }
        
        // Transactional intent
        $trans_terms = array('satın al', 'fiyat', 'buy', 'price', 'order');
        foreach ($trans_terms as $term) {
            if (strpos($keyword_lower, $term) !== false) {
                return 'transactional';
            }
        }
        
        // Commercial intent
        $comm_terms = array('vs', 'karşı', 'en iyi', 'best', 'review', 'inceleme');
        foreach ($comm_terms as $term) {
            if (strpos($keyword_lower, $term) !== false) {
                return 'commercial';
            }
        }
        
        // Navigational intent
        if (preg_match('/\b(giriş|login|kayıt|register)\b/i', $keyword_lower)) {
            return 'navigational';
        }
        
        // Default to informational
        return 'informational';
    }
    
    /**
     * Generate detailed recommendations
     */
    private function generate_recommendations($intent, $profile, $keyword, $keyword_intelligence) {
        $reasoning = array();
        
        // Select best content type
        $content_type = $this->select_content_type($intent, $profile, $keyword, $reasoning);
        
        // Adjust recommendations based on keyword characteristics
        $adjusted_cta = $profile['cta_density'];
        $adjusted_tone = $profile['recommended_tone'];
        
        if ($keyword_intelligence) {
            // Adjust CTA based on commercial value
            if ($keyword_intelligence['commercial_value'] === 'very_high' && $adjusted_cta === 'low') {
                $adjusted_cta = 'medium';
                $reasoning[] = "CTA increased to medium due to high commercial value";
            }
            
            // Adjust tone based on GEO suitability
            if ($keyword_intelligence['geo_suitability'] === 'excellent' && $adjusted_tone === 'technical') {
                $adjusted_tone = 'conversational';
                $reasoning[] = "Tone adjusted to conversational for better GEO performance";
            }
        }
        
        // Structure recommendations
        $structure_details = $this->get_structure_details($profile['structure'], $reasoning);
        
        // GEO optimization recommendations
        $geo_recommendations = $this->get_geo_recommendations($profile['geo_suitability'], $intent, $reasoning);
        
        return array(
            'content_type' => $content_type,
            'cta_density' => $adjusted_cta,
            'tone' => $adjusted_tone,
            'structure' => $profile['structure'],
            'structure_details' => $structure_details,
            'geo_optimization' => $geo_recommendations,
            'reasoning' => $reasoning,
        );
    }
    
    /**
     * Select optimal content type
     */
    private function select_content_type($intent, $profile, $keyword, &$reasoning) {
        $keyword_lower = mb_strtolower($keyword, 'UTF-8');
        
        // Check for specific content type indicators
        if (strpos($keyword_lower, 'rehber') !== false || strpos($keyword_lower, 'guide') !== false) {
            $reasoning[] = "Keyword contains 'guide' - comprehensive guide recommended";
            return 'guide';
        }
        
        if (strpos($keyword_lower, 'vs') !== false || strpos($keyword_lower, 'karşı') !== false) {
            $reasoning[] = "Comparison keyword detected - comparison page recommended";
            return 'comparison_page';
        }
        
        if (strpos($keyword_lower, 'en iyi') !== false || strpos($keyword_lower, 'best') !== false) {
            $reasoning[] = "Best-of keyword - list/review format recommended";
            return 'best_of_list';
        }
        
        if (strpos($keyword_lower, 'nedir') !== false || strpos($keyword_lower, 'what is') !== false) {
            $reasoning[] = "Definition keyword - glossary/explanation format recommended";
            return 'glossary';
        }
        
        // Default to first content type in profile
        $default = $profile['content_types'][0];
        $reasoning[] = "Standard {$intent} intent - {$default} recommended";
        return $default;
    }
    
    /**
     * Get structure details
     */
    private function get_structure_details($structure_type, &$reasoning) {
        $structures = array(
            'educational' => array(
                'sections' => array('introduction', 'main_content', 'examples', 'faq', 'conclusion'),
                'h2_focus' => 'topic_breakdown',
                'list_usage' => 'high',
                'visual_aids' => 'recommended',
            ),
            'conversion_focused' => array(
                'sections' => array('hero', 'benefits', 'features', 'social_proof', 'cta', 'faq'),
                'h2_focus' => 'benefit_driven',
                'list_usage' => 'medium',
                'visual_aids' => 'essential',
            ),
            'comparison' => array(
                'sections' => array('introduction', 'comparison_table', 'detailed_analysis', 'verdict', 'faq'),
                'h2_focus' => 'feature_comparison',
                'list_usage' => 'very_high',
                'visual_aids' => 'tables_required',
            ),
            'local_focused' => array(
                'sections' => array('location_intro', 'services', 'coverage_area', 'contact', 'reviews'),
                'h2_focus' => 'location_specific',
                'list_usage' => 'medium',
                'visual_aids' => 'map_required',
            ),
            'directory' => array(
                'sections' => array('overview', 'categories', 'featured_items', 'navigation'),
                'h2_focus' => 'category_based',
                'list_usage' => 'very_high',
                'visual_aids' => 'optional',
            ),
        );
        
        $details = $structures[$structure_type] ?? $structures['educational'];
        $reasoning[] = "Structure: {$structure_type} with " . count($details['sections']) . " main sections";
        
        return $details;
    }
    
    /**
     * Get GEO optimization recommendations
     */
    private function get_geo_recommendations($suitability, $intent, &$reasoning) {
        $recommendations = array(
            'answer_blocks' => false,
            'featured_snippet_optimization' => 'none',
            'conversational_format' => false,
            'faq_priority' => 'low',
        );
        
        if ($suitability === 'very_high' || $suitability === 'high') {
            $recommendations['answer_blocks'] = true;
            $recommendations['featured_snippet_optimization'] = 'aggressive';
            $recommendations['conversational_format'] = true;
            $recommendations['faq_priority'] = 'high';
            $reasoning[] = "High GEO suitability - enable all GEO optimizations";
        } elseif ($suitability === 'medium') {
            $recommendations['answer_blocks'] = true;
            $recommendations['featured_snippet_optimization'] = 'moderate';
            $recommendations['faq_priority'] = 'medium';
            $reasoning[] = "Medium GEO suitability - selective GEO optimizations";
        } else {
            $reasoning[] = "Low GEO suitability - focus on traditional SEO";
        }
        
        return $recommendations;
    }
}
