<?php
/**
 * Entity & Semantic Enrichment Engine
 * 
 * @package DODO_AI_SEO
 * @since 2.0.0 (Sprint 3 - Task 8)
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Entity_Enrichment {
    
    public function analyze_entities($content) {
        $text = strip_tags($content);
        
        return array(
            'entities' => $this->extract_entities($text),
            'semantic_coverage' => $this->calculate_semantic_coverage($text),
            'topical_completeness' => $this->calculate_topical_completeness($text),
            'enrichment_suggestions' => $this->generate_enrichment_suggestions($text),
        );
    }
    
    private function extract_entities($text) {
        $entities = array();
        
        // Extract capitalized words (potential entities)
        preg_match_all('/\b[A-Z][a-z]+(?:\s+[A-Z][a-z]+)*\b/', $text, $matches);
        
        if (!empty($matches[0])) {
            $entity_freq = array_count_values($matches[0]);
            arsort($entity_freq);
            
            foreach (array_slice($entity_freq, 0, 10) as $entity => $count) {
                $entities[] = array(
                    'name' => $entity,
                    'frequency' => $count,
                    'type' => 'named_entity',
                );
            }
        }
        
        return $entities;
    }
    
    private function calculate_semantic_coverage($text) {
        $word_count = str_word_count($text);
        $unique_words = count(array_unique(str_word_count(strtolower($text), 1)));
        
        return min(100, ($unique_words / max(1, $word_count)) * 200);
    }
    
    private function calculate_topical_completeness($text) {
        $has_intro = stripos($text, 'introduction') !== false || stripos($text, 'overview') !== false;
        $has_conclusion = stripos($text, 'conclusion') !== false || stripos($text, 'summary') !== false;
        $has_examples = stripos($text, 'example') !== false || stripos($text, 'for instance') !== false;
        
        $score = 50;
        if ($has_intro) $score += 15;
        if ($has_conclusion) $score += 15;
        if ($has_examples) $score += 20;
        
        return $score;
    }
    
    private function generate_enrichment_suggestions($text) {
        $suggestions = array();
        
        if (str_word_count($text) < 1000) {
            $suggestions[] = 'İçeriği genişletin (1000+ kelime hedefleyin)';
        }
        
        if (substr_count($text, '?') < 3) {
            $suggestions[] = 'FAQ bölümü ekleyin';
        }
        
        return $suggestions;
    }
}
