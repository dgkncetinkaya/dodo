<?php
/**
 * GEO (Generative Engine Optimization) Analyzer
 * 
 * Analyzes content for AI search engine visibility:
 * - ChatGPT, Perplexity, Google AI Mode, Gemini
 * - Citation potential, answer extraction quality
 * - Semantic authority, retrieval friendliness
 * 
 * @package DODO_AI_SEO
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_GEO_Analyzer {
    
    /**
     * Analyze GEO score
     * 
     * @param string $content Post content
     * @param string $focus_keyword Focus keyword
     * @return array GEO analysis data
     */
    public function analyze_geo_score($content, $focus_keyword) {
        // Calculate sub-scores
        $ai_answerability = $this->calculate_ai_answerability($content, $focus_keyword);
        $citation_potential = $this->calculate_citation_potential($content);
        $chunk_quality = $this->calculate_chunk_quality($content);
        $semantic_authority = $this->calculate_semantic_authority($content, $focus_keyword);
        $retrieval_friendliness = $this->calculate_retrieval_friendliness($content);
        $llm_readability = $this->calculate_llm_readability($content);
        $answer_extraction = $this->calculate_answer_extraction_quality($content, $focus_keyword);
        $conversational_coverage = $this->calculate_conversational_coverage($content, $focus_keyword);
        
        // Calculate overall GEO score (weighted average)
        $geo_score = (
            $ai_answerability * 0.20 +
            $citation_potential * 0.15 +
            $chunk_quality * 0.15 +
            $semantic_authority * 0.15 +
            $retrieval_friendliness * 0.10 +
            $llm_readability * 0.10 +
            $answer_extraction * 0.10 +
            $conversational_coverage * 0.05
        );
        
        // Get score label and class
        $score_label = $this->get_geo_score_label($geo_score);
        $score_class = $this->get_geo_score_class($geo_score);
        
        // Generate improvement suggestions
        $suggestions = $this->generate_geo_suggestions($geo_score, array(
            'ai_answerability' => $ai_answerability,
            'citation_potential' => $citation_potential,
            'chunk_quality' => $chunk_quality,
            'semantic_authority' => $semantic_authority,
            'retrieval_friendliness' => $retrieval_friendliness,
            'llm_readability' => $llm_readability,
            'answer_extraction' => $answer_extraction,
            'conversational_coverage' => $conversational_coverage,
        ));
        
        return array(
            'geo_score' => round($geo_score, 1),
            'score_label' => $score_label,
            'score_class' => $score_class,
            'ai_answerability' => round($ai_answerability, 1),
            'citation_potential' => round($citation_potential, 1),
            'chunk_quality' => round($chunk_quality, 1),
            'semantic_authority' => round($semantic_authority, 1),
            'retrieval_friendliness' => round($retrieval_friendliness, 1),
            'llm_readability' => round($llm_readability, 1),
            'answer_extraction' => round($answer_extraction, 1),
            'conversational_coverage' => round($conversational_coverage, 1),
            'suggestions' => $suggestions,
        );
    }
    
    /**
     * Calculate AI answerability
     */
    private function calculate_ai_answerability($content, $focus_keyword) {
        $score = 50;
        
        // Check for direct answers
        if (preg_match('/^#{2,3}\s+(.+?)\?/m', $content)) {
            $score += 15; // Has question headings
        }
        
        // Check for definition patterns
        if (preg_match('/' . preg_quote($focus_keyword, '/') . '\s+(is|are|means|refers to)/i', $content)) {
            $score += 10; // Has definition
        }
        
        // Check for list structures
        if (preg_match_all('/^[-*]\s+/m', $content) >= 3) {
            $score += 10; // Has lists
        }
        
        // Check for step-by-step patterns
        if (preg_match_all('/^#{2,3}\s+(Step|Adım)\s+\d+/m', $content) >= 2) {
            $score += 15; // Has steps
        }
        
        return min(100, $score);
    }
    
    /**
     * Calculate citation potential
     */
    private function calculate_citation_potential($content) {
        $score = 40;
        
        // Check for statistics
        if (preg_match_all('/\d+%|\d+\s+(percent|yüzde)/i', $content) >= 2) {
            $score += 15;
        }
        
        // Check for authoritative statements
        if (preg_match_all('/(according to|research shows|studies indicate|araştırmalara göre)/i', $content) >= 1) {
            $score += 20;
        }
        
        // Check for data points
        if (preg_match_all('/\d{4}|\d+\s+(million|billion|milyon|milyar)/i', $content) >= 2) {
            $score += 15;
        }
        
        // Check for expert quotes
        if (preg_match('/"[^"]{20,}"/', $content)) {
            $score += 10;
        }
        
        return min(100, $score);
    }
    
    /**
     * Calculate chunk quality
     */
    private function calculate_chunk_quality($content) {
        $score = 50;
        
        // Analyze paragraph structure
        $paragraphs = preg_split('/\n\n+/', strip_tags($content));
        $avg_paragraph_length = 0;
        
        foreach ($paragraphs as $para) {
            $words = str_word_count($para);
            $avg_paragraph_length += $words;
        }
        
        if (count($paragraphs) > 0) {
            $avg_paragraph_length = $avg_paragraph_length / count($paragraphs);
            
            // Ideal: 50-150 words per paragraph
            if ($avg_paragraph_length >= 50 && $avg_paragraph_length <= 150) {
                $score += 25;
            } elseif ($avg_paragraph_length >= 30 && $avg_paragraph_length <= 200) {
                $score += 15;
            }
        }
        
        // Check for clear section breaks
        if (preg_match_all('/^#{2,3}\s+/m', $content) >= 3) {
            $score += 15;
        }
        
        // Check for scannable content
        if (preg_match_all('/\*\*[^*]+\*\*/', $content) >= 3) {
            $score += 10;
        }
        
        return min(100, $score);
    }
    
    /**
     * Calculate semantic authority
     */
    private function calculate_semantic_authority($content, $focus_keyword) {
        $score = 40;
        
        // Check keyword density (not too high, not too low)
        $word_count = str_word_count(strip_tags($content));
        $keyword_count = substr_count(strtolower($content), strtolower($focus_keyword));
        
        if ($word_count > 0) {
            $density = ($keyword_count / $word_count) * 100;
            
            if ($density >= 0.5 && $density <= 2.5) {
                $score += 20; // Optimal density
            } elseif ($density >= 0.3 && $density <= 3.5) {
                $score += 10;
            }
        }
        
        // Check for related terms
        $related_terms = $this->get_related_terms($focus_keyword);
        $related_found = 0;
        
        foreach ($related_terms as $term) {
            if (stripos($content, $term) !== false) {
                $related_found++;
            }
        }
        
        if ($related_found >= 3) {
            $score += 20;
        } elseif ($related_found >= 1) {
            $score += 10;
        }
        
        // Check for depth indicators
        if (preg_match_all('/(however|therefore|additionally|furthermore|moreover|ancak|dolayısıyla|ayrıca)/i', $content) >= 3) {
            $score += 20; // Has depth
        }
        
        return min(100, $score);
    }
    
    /**
     * Calculate retrieval friendliness
     */
    private function calculate_retrieval_friendliness($content) {
        $score = 50;
        
        // Check for clear structure
        if (preg_match('/^#{1,3}\s+/m', $content)) {
            $score += 15;
        }
        
        // Check for FAQ section
        if (preg_match('/#{2,3}\s+(FAQ|SSS|Sık Sorulan)/i', $content)) {
            $score += 20;
        }
        
        // Check for table of contents indicators
        if (preg_match('/(table of contents|içindekiler)/i', $content)) {
            $score += 10;
        }
        
        // Check for summary/conclusion
        if (preg_match('/#{2,3}\s+(Summary|Conclusion|Özet|Sonuç)/i', $content)) {
            $score += 5;
        }
        
        return min(100, $score);
    }
    
    /**
     * Calculate LLM readability
     */
    private function calculate_llm_readability($content) {
        $score = 50;
        
        $text = strip_tags($content);
        $sentences = preg_split('/[.!?]+/', $text);
        $total_words = 0;
        $sentence_count = 0;
        
        foreach ($sentences as $sentence) {
            $words = str_word_count(trim($sentence));
            if ($words > 0) {
                $total_words += $words;
                $sentence_count++;
            }
        }
        
        if ($sentence_count > 0) {
            $avg_sentence_length = $total_words / $sentence_count;
            
            // Ideal: 15-25 words per sentence for LLM
            if ($avg_sentence_length >= 15 && $avg_sentence_length <= 25) {
                $score += 30;
            } elseif ($avg_sentence_length >= 10 && $avg_sentence_length <= 30) {
                $score += 20;
            }
        }
        
        // Check for transition words
        if (preg_match_all('/(first|second|finally|additionally|however|therefore|önce|sonra|ancak|dolayısıyla)/i', $content) >= 3) {
            $score += 20;
        }
        
        return min(100, $score);
    }
    
    /**
     * Calculate answer extraction quality
     */
    private function calculate_answer_extraction_quality($content, $focus_keyword) {
        $score = 40;
        
        // Check for direct answer in first paragraph
        $paragraphs = preg_split('/\n\n+/', strip_tags($content));
        if (!empty($paragraphs[0])) {
            if (stripos($paragraphs[0], $focus_keyword) !== false) {
                $score += 20;
            }
        }
        
        // Check for featured snippet patterns
        if (preg_match('/^#{2,3}\s+What is/i', $content) || preg_match('/^#{2,3}\s+Nedir/i', $content)) {
            $score += 20;
        }
        
        // Check for numbered lists
        if (preg_match_all('/^\d+\.\s+/m', $content) >= 3) {
            $score += 10;
        }
        
        // Check for comparison tables
        if (preg_match('/\|[^|]+\|[^|]+\|/', $content)) {
            $score += 10;
        }
        
        return min(100, $score);
    }
    
    /**
     * Calculate conversational coverage
     */
    private function calculate_conversational_coverage($content, $focus_keyword) {
        $score = 50;
        
        // Check for question variations
        $question_patterns = array(
            'what', 'how', 'why', 'when', 'where', 'who',
            'nedir', 'nasıl', 'neden', 'ne zaman', 'nerede', 'kim'
        );
        
        $questions_found = 0;
        foreach ($question_patterns as $pattern) {
            if (preg_match('/' . preg_quote($pattern, '/') . '/i', $content)) {
                $questions_found++;
            }
        }
        
        if ($questions_found >= 4) {
            $score += 30;
        } elseif ($questions_found >= 2) {
            $score += 15;
        }
        
        // Check for conversational tone
        if (preg_match_all('/(you can|you should|you might|yapabilirsiniz|yapmalısınız)/i', $content) >= 2) {
            $score += 20;
        }
        
        return min(100, $score);
    }
    
    /**
     * Get related terms
     */
    private function get_related_terms($keyword) {
        // Simple related terms (can be expanded with NLP)
        $terms = array();
        
        // Add plural/singular variations
        $terms[] = $keyword . 'lar';
        $terms[] = $keyword . 'ler';
        
        return $terms;
    }
    
    /**
     * Get GEO score label
     */
    private function get_geo_score_label($score) {
        if ($score >= 80) return 'Excellent AI Visibility';
        if ($score >= 60) return 'Good AI Visibility';
        if ($score >= 40) return 'Fair AI Visibility';
        return 'Poor AI Visibility';
    }
    
    /**
     * Get GEO score class
     */
    private function get_geo_score_class($score) {
        if ($score >= 80) return 'excellent';
        if ($score >= 60) return 'good';
        if ($score >= 40) return 'fair';
        return 'poor';
    }
    
    /**
     * Generate GEO suggestions
     */
    private function generate_geo_suggestions($geo_score, $sub_scores) {
        $suggestions = array();
        
        if ($sub_scores['ai_answerability'] < 60) {
            $suggestions[] = array(
                'type' => 'ai_answerability',
                'priority' => 'high',
                'title' => 'Improve AI Answerability',
                'description' => 'Add direct answers, question headings, and step-by-step guides.',
            );
        }
        
        if ($sub_scores['citation_potential'] < 60) {
            $suggestions[] = array(
                'type' => 'citation_potential',
                'priority' => 'high',
                'title' => 'Boost Citation Potential',
                'description' => 'Include statistics, research references, and authoritative data points.',
            );
        }
        
        if ($sub_scores['chunk_quality'] < 60) {
            $suggestions[] = array(
                'type' => 'chunk_quality',
                'priority' => 'medium',
                'title' => 'Optimize Chunk Quality',
                'description' => 'Break content into 50-150 word paragraphs with clear section breaks.',
            );
        }
        
        if ($sub_scores['semantic_authority'] < 60) {
            $suggestions[] = array(
                'type' => 'semantic_authority',
                'priority' => 'high',
                'title' => 'Strengthen Semantic Authority',
                'description' => 'Add related terms, depth indicators, and topical coverage.',
            );
        }
        
        if ($sub_scores['answer_extraction'] < 60) {
            $suggestions[] = array(
                'type' => 'answer_extraction',
                'priority' => 'medium',
                'title' => 'Improve Answer Extraction',
                'description' => 'Add direct answers in first paragraph and featured snippet patterns.',
            );
        }
        
        return $suggestions;
    }
}
