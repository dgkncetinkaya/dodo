<?php
/**
 * GEO / AI Visibility Engine
 * 
 * Analyzes content potential for AI answer engines (ChatGPT, Gemini, Perplexity)
 * Sprint D - Revenue & Topical Domination Engine
 * 
 * @package DODO_AI_SEO
 * @since 3.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_GEO_Engine {
    
    /**
     * Calculate GEO score (0-100)
     * 
     * @param string $keyword
     * @param array $context
     * @return array GEO analysis
     */
    public function calculate_geo_score($keyword, $context = array()) {
        $score = 0;
        $signals = array();
        
        $keyword_lower = mb_strtolower($keyword, 'UTF-8');
        
        // 1. Question format (20 points)
        if ($this->is_question_format($keyword_lower)) {
            $score += 20;
            $signals[] = 'Question format detected';
        }
        
        // 2. Definition query (20 points)
        if ($this->is_definition_query($keyword_lower)) {
            $score += 20;
            $signals[] = 'Definition query';
        }
        
        // 3. Entity richness potential (20 points)
        $entity_score = $this->detect_entity_coverage($keyword_lower);
        $score += $entity_score;
        if ($entity_score > 0) {
            $signals[] = 'Entity-rich topic';
        }
        
        // 4. FAQ potential (20 points)
        if ($this->has_faq_potential($keyword_lower)) {
            $score += 20;
            $signals[] = 'FAQ potential';
        }
        
        // 5. Conversational answer quality (20 points)
        $conversational_score = $this->detect_answer_quality($keyword_lower);
        $score += $conversational_score;
        if ($conversational_score > 0) {
            $signals[] = 'Conversational answer potential';
        }
        
        $score = min(100, $score);
        
        // Determine GEO level
        if ($score >= 70) {
            $geo_level = 'high';
        } elseif ($score >= 40) {
            $geo_level = 'medium';
        } else {
            $geo_level = 'low';
        }
        
        // Detect AI answer potential
        $ai_answer_potential = $this->detect_ai_answer_potential($keyword_lower);
        
        // Detect missing FAQs
        $missing_faqs = $this->detect_missing_faqs($keyword_lower);
        
        return array(
            'geo_score' => $score,
            'geo_level' => $geo_level,
            'ai_answer_potential' => $ai_answer_potential,
            'ai_visibility_score' => $score,
            'signals' => $signals,
            'missing_faqs' => $missing_faqs,
            'structured_answer_potential' => $score >= 60,
        );
    }
    
    /**
     * Check if question format
     */
    private function is_question_format($keyword) {
        $question_patterns = array(
            '/^(nasıl|nedir|ne\s+demek|neden|ne\s+zaman|nerede|kim|hangi)/i',
            '/^(how|what|why|when|where|who|which)/i',
            '/\?$/',
        );
        
        foreach ($question_patterns as $pattern) {
            if (preg_match($pattern, $keyword)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if definition query
     */
    private function is_definition_query($keyword) {
        return preg_match('/(nedir|ne\s+demek|what\s+is|definition|tanım)/i', $keyword);
    }
    
    /**
     * Detect entity coverage (0-20 points)
     */
    private function detect_entity_coverage($keyword) {
        $score = 0;
        
        // Technical/specific terms = high entity potential
        if (preg_match('/\b(makine|cihaz|sistem|teknoloji|yöntem|süreç)/i', $keyword)) {
            $score += 10;
        }
        
        // Proper nouns
        if (preg_match('/\b[A-Z][a-z]+\b/', $keyword)) {
            $score += 5;
        }
        
        // Numbers/measurements
        if (preg_match('/\d+/', $keyword)) {
            $score += 5;
        }
        
        return min(20, $score);
    }
    
    /**
     * Check FAQ potential
     */
    private function has_faq_potential($keyword) {
        // Questions naturally have FAQ potential
        if ($this->is_question_format($keyword)) {
            return true;
        }
        
        // Topics that commonly have FAQs
        $faq_topics = array(
            'nasıl', 'nedir', 'fiyat', 'kullanım', 'avantaj', 'dezavantaj',
            'how', 'what', 'price', 'usage', 'advantage', 'disadvantage',
        );
        
        foreach ($faq_topics as $topic) {
            if (stripos($keyword, $topic) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Detect answer quality (0-20 points)
     */
    private function detect_answer_quality($keyword) {
        $score = 0;
        
        // Specific questions = better answer quality
        $word_count = count(explode(' ', $keyword));
        
        if ($word_count >= 4) {
            $score += 10; // Specific question
        } elseif ($word_count >= 2) {
            $score += 5;
        }
        
        // Actionable queries
        if (preg_match('/(nasıl|how\s+to|adım|step|yöntem|method)/i', $keyword)) {
            $score += 10;
        }
        
        return min(20, $score);
    }
    
    /**
     * Detect AI answer potential
     */
    private function detect_ai_answer_potential($keyword) {
        $potential = 'medium';
        
        // High potential: Questions, definitions, how-tos
        if ($this->is_question_format($keyword) || $this->is_definition_query($keyword)) {
            $potential = 'high';
        }
        
        // Low potential: Transactional, navigational
        if (preg_match('/(satın\s+al|buy|fiyat|price|sipariş|order)/i', $keyword)) {
            $potential = 'low';
        }
        
        return $potential;
    }
    
    /**
     * Detect missing FAQs
     */
    private function detect_missing_faqs($keyword) {
        $faqs = array();
        
        // Extract main topic
        $topic = preg_replace('/(nedir|nasıl|what|how)/i', '', $keyword);
        $topic = trim($topic);
        
        if (empty($topic)) {
            return $faqs;
        }
        
        // Generate common FAQ patterns
        $faq_patterns = array(
            $topic . ' nedir?',
            $topic . ' nasıl kullanılır?',
            $topic . ' avantajları nelerdir?',
            $topic . ' fiyatı ne kadar?',
            $topic . ' nereden alınır?',
        );
        
        return array_slice($faq_patterns, 0, 3);
    }
    
    /**
     * Detect semantic completeness
     */
    public function detect_semantic_completeness($keyword, $existing_content = array()) {
        $completeness_score = 0;
        
        // Check if existing content covers key aspects
        $key_aspects = array(
            'definition' => false,
            'how_to' => false,
            'benefits' => false,
            'examples' => false,
            'faq' => false,
        );
        
        foreach ($existing_content as $content) {
            $content_lower = mb_strtolower($content, 'UTF-8');
            
            if (stripos($content_lower, 'nedir') !== false || stripos($content_lower, 'tanım') !== false) {
                $key_aspects['definition'] = true;
            }
            
            if (stripos($content_lower, 'nasıl') !== false || stripos($content_lower, 'adım') !== false) {
                $key_aspects['how_to'] = true;
            }
            
            if (stripos($content_lower, 'avantaj') !== false || stripos($content_lower, 'fayda') !== false) {
                $key_aspects['benefits'] = true;
            }
            
            if (stripos($content_lower, 'örnek') !== false || stripos($content_lower, 'example') !== false) {
                $key_aspects['examples'] = true;
            }
            
            if (stripos($content_lower, 'sık sorulan') !== false || stripos($content_lower, 'faq') !== false) {
                $key_aspects['faq'] = true;
            }
        }
        
        // Calculate completeness
        $covered = array_filter($key_aspects);
        $completeness_score = (count($covered) / count($key_aspects)) * 100;
        
        return array(
            'completeness_score' => round($completeness_score),
            'covered_aspects' => array_keys($covered),
            'missing_aspects' => array_keys(array_filter($key_aspects, function($v) { return !$v; })),
        );
    }
    
    /**
     * Enrich opportunities with GEO intelligence
     */
    public function enrich_opportunities($opportunities) {
        foreach ($opportunities as &$opp) {
            $keyword = $opp['keyword'] ?? '';
            
            if (empty($keyword)) {
                continue;
            }
            
            try {
                $geo_analysis = $this->calculate_geo_score($keyword);
                
                $opp['geo_score'] = $geo_analysis['geo_score'];
                $opp['geo_level'] = $geo_analysis['geo_level'];
                $opp['ai_answer_potential'] = $geo_analysis['ai_answer_potential'];
                $opp['ai_visibility_score'] = $geo_analysis['ai_visibility_score'];
                $opp['structured_answer_potential'] = $geo_analysis['structured_answer_potential'];
                $opp['missing_faqs'] = $geo_analysis['missing_faqs'];
                
            } catch (Throwable $e) {
                error_log('[DODO][GEO Engine] Error analyzing keyword: ' . $e->getMessage());
            }
        }
        
        return $opportunities;
    }
}
