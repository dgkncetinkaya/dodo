<?php
/**
 * AI Overview / GEO Analyzer
 * 
 * Analyzes content for Google AI Overview (GEO) readiness
 * Citation probability, answer extraction, chunk retrievability
 *
 * @package DODO_AI_SEO
 * @since 2.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_AI_Overview_Engine {
    
    /**
     * GEO readiness thresholds
     */
    const READINESS_EXCELLENT = 80;
    const READINESS_GOOD = 60;
    const READINESS_FAIR = 40;
    
    /**
     * Analyze content for GEO readiness
     *
     * @param string $content Content to analyze
     * @param string $keyword Target keyword
     * @return array GEO analysis
     */
    public function analyze_geo_readiness($content, $keyword = '') {
        $start_time = microtime(true);
        
        error_log('[DODO GEO] Analyzing AI Overview readiness');
        
        // Strip HTML
        $text = strip_tags($content);
        
        // Calculate individual scores
        $answer_extraction = $this->score_answer_extraction($text, $keyword);
        $chunk_retrievability = $this->score_chunk_retrievability($text);
        $citation_friendliness = $this->score_citation_friendliness($content);
        $conversational_retrieval = $this->score_conversational_retrieval($text, $keyword);
        $summarization_quality = $this->score_summarization_quality($text);
        $answer_density = $this->score_answer_density($text);
        
        // Calculate overall GEO readiness
        $geo_score = round((
            $answer_extraction['score'] * 0.25 +
            $chunk_retrievability['score'] * 0.20 +
            $citation_friendliness['score'] * 0.20 +
            $conversational_retrieval['score'] * 0.15 +
            $summarization_quality['score'] * 0.10 +
            $answer_density['score'] * 0.10
        ));
        
        // Calculate citation probability
        $citation_probability = $this->calculate_citation_probability($geo_score, $citation_friendliness);
        
        // Calculate snippet extraction score
        $snippet_score = $this->calculate_snippet_score($answer_extraction, $chunk_retrievability);
        
        $execution_time = round((microtime(true) - $start_time) * 1000, 2);
        
        $analysis = [
            'geo_readiness_score' => $geo_score,
            'readiness_level' => $this->get_readiness_level($geo_score),
            'citation_probability' => $citation_probability,
            'snippet_extraction_score' => $snippet_score,
            'component_scores' => [
                'answer_extraction' => $answer_extraction,
                'chunk_retrievability' => $chunk_retrievability,
                'citation_friendliness' => $citation_friendliness,
                'conversational_retrieval' => $conversational_retrieval,
                'summarization_quality' => $summarization_quality,
                'answer_density' => $answer_density,
            ],
            'recommendations' => $this->generate_geo_recommendations($geo_score, [
                'answer_extraction' => $answer_extraction,
                'chunk_retrievability' => $chunk_retrievability,
                'citation_friendliness' => $citation_friendliness,
            ]),
            'metadata' => [
                'analyzed_at' => current_time('mysql'),
                'execution_time_ms' => $execution_time,
                'content_length' => strlen($text),
            ],
        ];
        
        error_log(sprintf(
            '[DODO GEO] Analysis complete - Score: %d, Citation probability: %d%%',
            $geo_score,
            $citation_probability
        ));
        
        return $analysis;
    }
    
    /**
     * Score answer extraction suitability
     */
    private function score_answer_extraction($text, $keyword) {
        $score = 0;
        $signals = [];
        
        // Direct answer patterns
        $answer_patterns = [
            '/^.{0,50}(is|are|means?|refers? to|defined as)/i',
            '/^.{0,50}(nedir|demektir|anlamına gelir)/ui',
        ];
        
        foreach ($answer_patterns as $pattern) {
            if (preg_match($pattern, $text)) {
                $score += 25;
                $signals[] = 'direct_answer_pattern';
                break;
            }
        }
        
        // Clear sentence structure
        $sentences = preg_split('/[.!?]+/', $text);
        $clear_sentences = 0;
        
        foreach (array_slice($sentences, 0, 5) as $sentence) {
            $sentence = trim($sentence);
            $word_count = str_word_count($sentence);
            
            // Ideal sentence length: 15-25 words
            if ($word_count >= 15 && $word_count <= 25) {
                $clear_sentences++;
            }
        }
        
        if ($clear_sentences >= 3) {
            $score += 20;
            $signals[] = 'clear_sentence_structure';
        }
        
        // Keyword in first paragraph
        if (!empty($keyword)) {
            $first_para = substr($text, 0, 500);
            if (stripos($first_para, $keyword) !== false) {
                $score += 15;
                $signals[] = 'keyword_in_intro';
            }
        }
        
        // Concise introduction
        $first_sentence = trim($sentences[0] ?? '');
        $first_word_count = str_word_count($first_sentence);
        
        if ($first_word_count >= 10 && $first_word_count <= 30) {
            $score += 20;
            $signals[] = 'concise_intro';
        }
        
        // Definition-style content
        if (preg_match('/(definition|tanım|açıklama)/ui', substr($text, 0, 200))) {
            $score += 20;
            $signals[] = 'definition_style';
        }
        
        return [
            'score' => min(100, $score),
            'signals' => $signals,
            'reasoning' => $this->explain_score($score, 'answer extraction'),
        ];
    }
    
    /**
     * Score chunk retrievability
     */
    private function score_chunk_retrievability($text) {
        $score = 0;
        $signals = [];
        
        // Paragraph structure
        $paragraphs = preg_split('/\n\s*\n/', $text);
        $good_paragraphs = 0;
        
        foreach ($paragraphs as $para) {
            $word_count = str_word_count($para);
            
            // Ideal chunk: 50-150 words
            if ($word_count >= 50 && $word_count <= 150) {
                $good_paragraphs++;
            }
        }
        
        $para_ratio = count($paragraphs) > 0 ? $good_paragraphs / count($paragraphs) : 0;
        $score += round($para_ratio * 40);
        
        if ($para_ratio > 0.6) {
            $signals[] = 'good_paragraph_structure';
        }
        
        // Logical flow markers
        $flow_markers = ['first', 'second', 'next', 'finally', 'however', 'therefore', 
                         'önce', 'sonra', 'ardından', 'ancak', 'dolayısıyla'];
        
        $marker_count = 0;
        foreach ($flow_markers as $marker) {
            if (stripos($text, $marker) !== false) {
                $marker_count++;
            }
        }
        
        if ($marker_count >= 3) {
            $score += 20;
            $signals[] = 'logical_flow';
        }
        
        // Standalone chunks (can be understood independently)
        $standalone_count = 0;
        foreach (array_slice($paragraphs, 0, 5) as $para) {
            // Check if paragraph has subject and verb
            if (preg_match('/\b(is|are|was|were|has|have|can|will|dir|dır|tır|tir)\b/ui', $para)) {
                $standalone_count++;
            }
        }
        
        if ($standalone_count >= 3) {
            $score += 20;
            $signals[] = 'standalone_chunks';
        }
        
        // Clear topic sentences
        $topic_sentence_count = 0;
        foreach ($paragraphs as $para) {
            $first_sentence = preg_split('/[.!?]/', $para)[0] ?? '';
            if (str_word_count($first_sentence) >= 8) {
                $topic_sentence_count++;
            }
        }
        
        if ($topic_sentence_count >= count($paragraphs) * 0.7) {
            $score += 20;
            $signals[] = 'clear_topic_sentences';
        }
        
        return [
            'score' => min(100, $score),
            'signals' => $signals,
            'paragraph_count' => count($paragraphs),
            'good_paragraphs' => $good_paragraphs,
            'reasoning' => $this->explain_score($score, 'chunk retrievability'),
        ];
    }
    
    /**
     * Score citation friendliness
     */
    private function score_citation_friendliness($content) {
        $score = 0;
        $signals = [];
        
        // Structured data presence
        if (stripos($content, 'schema.org') !== false || stripos($content, 'application/ld+json') !== false) {
            $score += 25;
            $signals[] = 'structured_data';
        }
        
        // Clear authorship
        if (preg_match('/(author|yazar|by\s+\w+)/ui', $content)) {
            $score += 15;
            $signals[] = 'clear_authorship';
        }
        
        // Date information
        if (preg_match('/20\d{2}/', $content)) {
            $score += 10;
            $signals[] = 'date_present';
        }
        
        // Factual statements (numbers, data)
        $factual_count = preg_match_all('/\d+%|\d+\s*(percent|yüzde|milyon|bin)/', $content);
        if ($factual_count >= 3) {
            $score += 20;
            $signals[] = 'data_driven';
        }
        
        // Source citations
        if (preg_match_all('/(according to|kaynak|source|study|araştırma)/ui', $content) >= 2) {
            $score += 15;
            $signals[] = 'cites_sources';
        }
        
        // Clean HTML structure
        $heading_count = preg_match_all('/<h[1-6][^>]*>/i', $content);
        if ($heading_count >= 3) {
            $score += 15;
            $signals[] = 'clear_structure';
        }
        
        return [
            'score' => min(100, $score),
            'signals' => $signals,
            'reasoning' => $this->explain_score($score, 'citation friendliness'),
        ];
    }
    
    /**
     * Score conversational retrieval
     */
    private function score_conversational_retrieval($text, $keyword) {
        $score = 0;
        $signals = [];
        
        // Question-answer format
        $qa_count = preg_match_all('/\?/', $text);
        if ($qa_count >= 2) {
            $score += 25;
            $signals[] = 'qa_format';
        }
        
        // Natural language
        $natural_phrases = ['you can', 'you should', 'it is', 'this means', 
                           'yapabilirsiniz', 'yapmalısınız', 'demektir', 'anlamına gelir'];
        
        $natural_count = 0;
        foreach ($natural_phrases as $phrase) {
            if (stripos($text, $phrase) !== false) {
                $natural_count++;
            }
        }
        
        if ($natural_count >= 3) {
            $score += 20;
            $signals[] = 'natural_language';
        }
        
        // Conversational keywords
        if (!empty($keyword)) {
            $conversational_variants = [
                "what is {$keyword}",
                "how to {$keyword}",
                "{$keyword} nedir",
                "{$keyword} nasıl",
            ];
            
            foreach ($conversational_variants as $variant) {
                if (stripos($text, $variant) !== false) {
                    $score += 15;
                    $signals[] = 'conversational_keyword';
                    break;
                }
            }
        }
        
        // Direct address (you, your)
        $address_count = preg_match_all('/\b(you|your|siz|sizin)\b/ui', $text);
        if ($address_count >= 5) {
            $score += 20;
            $signals[] = 'direct_address';
        }
        
        // Step-by-step format
        if (preg_match('/(step \d+|adım \d+|\d+\.\s)/ui', $text)) {
            $score += 20;
            $signals[] = 'step_by_step';
        }
        
        return [
            'score' => min(100, $score),
            'signals' => $signals,
            'reasoning' => $this->explain_score($score, 'conversational retrieval'),
        ];
    }
    
    /**
     * Score summarization quality
     */
    private function score_summarization_quality($text) {
        $score = 0;
        $signals = [];
        
        // Clear introduction
        $first_para = substr($text, 0, 500);
        $first_word_count = str_word_count($first_para);
        
        if ($first_word_count >= 50 && $first_word_count <= 150) {
            $score += 30;
            $signals[] = 'clear_intro';
        }
        
        // Key points highlighted
        if (preg_match_all('/(important|key|main|önemli|ana|temel)/ui', $text) >= 3) {
            $score += 20;
            $signals[] = 'highlights_key_points';
        }
        
        // Conclusion present
        if (preg_match('/(conclusion|summary|sonuç|özet)/ui', $text)) {
            $score += 25;
            $signals[] = 'has_conclusion';
        }
        
        // Bullet points or lists
        if (preg_match('/<(ul|ol)[^>]*>/i', $text) || preg_match('/[•\-\*]\s+/u', $text)) {
            $score += 25;
            $signals[] = 'uses_lists';
        }
        
        return [
            'score' => min(100, $score),
            'signals' => $signals,
            'reasoning' => $this->explain_score($score, 'summarization quality'),
        ];
    }
    
    /**
     * Score answer density
     */
    private function score_answer_density($text) {
        $score = 0;
        $signals = [];
        
        $word_count = str_word_count($text);
        
        // Optimal length: 800-2000 words
        if ($word_count >= 800 && $word_count <= 2000) {
            $score += 30;
            $signals[] = 'optimal_length';
        } elseif ($word_count >= 500) {
            $score += 20;
            $signals[] = 'adequate_length';
        }
        
        // Information density (unique words ratio)
        $words = str_word_count(strtolower($text), 1);
        $unique_words = count(array_unique($words));
        $density = $word_count > 0 ? $unique_words / $word_count : 0;
        
        if ($density >= 0.5) {
            $score += 25;
            $signals[] = 'high_information_density';
        }
        
        // Factual content
        $number_count = preg_match_all('/\d+/', $text);
        if ($number_count >= 10) {
            $score += 20;
            $signals[] = 'data_rich';
        }
        
        // Specific terms (not vague)
        $vague_terms = ['thing', 'stuff', 'something', 'şey', 'bir şey'];
        $vague_count = 0;
        foreach ($vague_terms as $term) {
            $vague_count += substr_count(strtolower($text), $term);
        }
        
        if ($vague_count < 5) {
            $score += 25;
            $signals[] = 'specific_language';
        }
        
        return [
            'score' => min(100, $score),
            'signals' => $signals,
            'word_count' => $word_count,
            'reasoning' => $this->explain_score($score, 'answer density'),
        ];
    }
    
    /**
     * Calculate citation probability
     */
    private function calculate_citation_probability($geo_score, $citation_friendliness) {
        $base_probability = $geo_score * 0.6;
        $citation_bonus = $citation_friendliness['score'] * 0.4;
        
        return min(100, round($base_probability + $citation_bonus));
    }
    
    /**
     * Calculate snippet extraction score
     */
    private function calculate_snippet_score($answer_extraction, $chunk_retrievability) {
        return round(($answer_extraction['score'] * 0.6) + ($chunk_retrievability['score'] * 0.4));
    }
    
    /**
     * Get readiness level
     */
    private function get_readiness_level($score) {
        if ($score >= self::READINESS_EXCELLENT) {
            return 'excellent';
        } elseif ($score >= self::READINESS_GOOD) {
            return 'good';
        } elseif ($score >= self::READINESS_FAIR) {
            return 'fair';
        } else {
            return 'poor';
        }
    }
    
    /**
     * Explain score
     */
    private function explain_score($score, $component) {
        if ($score >= 80) {
            return "Excellent {$component} - highly optimized for AI Overview";
        } elseif ($score >= 60) {
            return "Good {$component} - suitable for AI Overview";
        } elseif ($score >= 40) {
            return "Fair {$component} - needs improvement";
        } else {
            return "Poor {$component} - significant optimization needed";
        }
    }
    
    /**
     * Generate GEO recommendations
     */
    private function generate_geo_recommendations($geo_score, $component_scores) {
        $recommendations = [];
        
        // Answer extraction recommendations
        if ($component_scores['answer_extraction']['score'] < 60) {
            $recommendations[] = [
                'priority' => 'high',
                'component' => 'answer_extraction',
                'action' => 'Add clear, direct answer in first paragraph',
                'reason' => 'AI Overview prefers immediate, concise answers',
            ];
        }
        
        // Chunk retrievability recommendations
        if ($component_scores['chunk_retrievability']['score'] < 60) {
            $recommendations[] = [
                'priority' => 'high',
                'component' => 'chunk_retrievability',
                'action' => 'Break content into 50-150 word paragraphs',
                'reason' => 'Smaller chunks are easier for AI to extract and cite',
            ];
        }
        
        // Citation friendliness recommendations
        if ($component_scores['citation_friendliness']['score'] < 60) {
            $recommendations[] = [
                'priority' => 'medium',
                'component' => 'citation_friendliness',
                'action' => 'Add structured data and clear authorship',
                'reason' => 'Increases likelihood of being cited as source',
            ];
        }
        
        // Overall recommendations
        if ($geo_score < 40) {
            $recommendations[] = [
                'priority' => 'critical',
                'component' => 'overall',
                'action' => 'Complete content restructure needed',
                'reason' => 'Current format not suitable for AI Overview',
            ];
        } elseif ($geo_score < 60) {
            $recommendations[] = [
                'priority' => 'medium',
                'component' => 'overall',
                'action' => 'Optimize content structure and clarity',
                'reason' => 'Good foundation but needs refinement',
            ];
        }
        
        return $recommendations;
    }
}
