<?php
/**
 * Generation Memory System
 * 
 * @package DODO_AI_SEO
 * @since 2.0.0 (Sprint 3 - Task 10)
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Generation_Memory {
    
    public function learn_from_content($post_id) {
        $post = get_post($post_id);
        
        $patterns = array(
            'tone' => $this->detect_tone($post->post_content),
            'heading_structure' => $this->analyze_heading_structure($post->post_content),
            'cta_style' => $this->detect_cta_style($post->post_content),
            'intro_style' => $this->analyze_intro_style($post->post_content),
            'semantic_patterns' => $this->extract_semantic_patterns($post->post_content),
        );
        
        $this->store_patterns($patterns);
        
        return $patterns;
    }
    
    public function get_brand_patterns() {
        return get_option('dodo_brand_patterns', array(
            'tone' => 'professional',
            'heading_structure' => 'standard',
            'cta_style' => 'direct',
            'intro_style' => 'question',
            'semantic_patterns' => array(),
        ));
    }
    
    private function detect_tone($content) {
        $formal_words = array('therefore', 'however', 'furthermore', 'consequently');
        $casual_words = array('basically', 'actually', 'pretty', 'really');
        
        $text = strtolower(strip_tags($content));
        
        $formal_count = 0;
        foreach ($formal_words as $word) {
            $formal_count += substr_count($text, $word);
        }
        
        $casual_count = 0;
        foreach ($casual_words as $word) {
            $casual_count += substr_count($text, $word);
        }
        
        return $formal_count > $casual_count ? 'formal' : 'casual';
    }
    
    private function analyze_heading_structure($content) {
        $h2_count = substr_count($content, '<h2');
        $h3_count = substr_count($content, '<h3');
        
        if ($h2_count > 5 && $h3_count > 10) {
            return 'detailed';
        } elseif ($h2_count > 3) {
            return 'standard';
        }
        
        return 'simple';
    }
    
    private function detect_cta_style($content) {
        if (stripos($content, 'click here') !== false || stripos($content, 'learn more') !== false) {
            return 'direct';
        }
        
        if (stripos($content, 'discover') !== false || stripos($content, 'explore') !== false) {
            return 'exploratory';
        }
        
        return 'subtle';
    }
    
    private function analyze_intro_style($content) {
        $first_para = substr(strip_tags($content), 0, 200);
        
        if (strpos($first_para, '?') !== false) {
            return 'question';
        }
        
        if (stripos($first_para, 'imagine') !== false || stripos($first_para, 'picture') !== false) {
            return 'scenario';
        }
        
        return 'statement';
    }
    
    private function extract_semantic_patterns($content) {
        // Simplified - just track common phrases
        return array();
    }
    
    private function store_patterns($patterns) {
        $existing = $this->get_brand_patterns();
        $merged = array_merge($existing, $patterns);
        update_option('dodo_brand_patterns', $merged);
    }
}
