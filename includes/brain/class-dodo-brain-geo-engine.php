<?php
/**
 * GEO Engine (Generative Engine Optimization)
 * 
 * Analyzes content readiness for:
 * - AI Overview (Google SGE)
 * - Conversational retrieval
 * - Answer blocks
 * - Citation readiness
 * - Featured snippets
 * 
 * @package DODO_AI_SEO
 * @subpackage Brain_Core
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Prevent duplicate class declaration
if (class_exists('DODO_Brain_GEO_Engine')) {
    return;
}

class DODO_Brain_GEO_Engine {
    
    /**
     * Analyze content for GEO readiness
     * 
     * @param string $content Content to analyze
     * @param string $keyword Target keyword
     * @param array $options Analysis options
     * @return array GEO readiness report
     */
    public function analyze($content, $keyword = '', $options = array()) {
        $start_time = microtime(true);
        
        // Validate input
        if (empty(trim($content))) {
            return [
                'error' => true,
                'message' => 'İçerik boş olamaz.',
                'overall_geo_score' => 0,
                'execution_time_ms' => 0
            ];
        }
        
        try {
            error_log("BRAIN: GEO Engine analyzing content (" . strlen($content) . " chars)");
            
            // Run all GEO analyses
            $conversational_score = $this->analyze_conversational_retrieval($content, $keyword);
            $ai_overview_score = $this->analyze_ai_overview_suitability($content, $keyword);
            $answer_block_score = $this->analyze_answer_block_readiness($content);
            $citation_score = $this->analyze_citation_readiness($content);
            $chunk_quality = $this->analyze_chunk_extraction($content);
            $entity_clarity = $this->analyze_entity_clarity($content, $keyword);
            $snippet_probability = $this->calculate_featured_snippet_probability($content, $keyword);
            
            // Calculate overall GEO score
            $overall_score = $this->calculate_overall_geo_score(array(
                'conversational' => $conversational_score['score'],
                'ai_overview' => $ai_overview_score['score'],
                'answer_block' => $answer_block_score['score'],
                'citation' => $citation_score['score'],
                'chunk_quality' => $chunk_quality['score'],
                'entity_clarity' => $entity_clarity['score'],
            ));
            
            $execution_time = round((microtime(true) - $start_time) * 1000, 2);
            
            $report = array(
                'overall_geo_score' => $overall_score,
                'geo_readiness' => $this->get_readiness_level($overall_score),
                'conversational_retrieval' => $conversational_score,
                'ai_overview_suitability' => $ai_overview_score,
                'answer_block_readiness' => $answer_block_score,
                'citation_readiness' => $citation_score,
                'chunk_extraction_quality' => $chunk_quality,
                'entity_clarity' => $entity_clarity,
                'featured_snippet_probability' => $snippet_probability,
                'recommendations' => $this->generate_recommendations($overall_score, array(
                    'conversational' => $conversational_score,
                    'ai_overview' => $ai_overview_score,
                    'answer_block' => $answer_block_score,
                    'citation' => $citation_score,
                )),
                'metadata' => array(
                    'content_length' => strlen($content),
                    'execution_time_ms' => $execution_time,
                    'analyzed_at' => current_time('mysql'),
                ),
            );
            
            error_log("BRAIN: GEO Engine complete - Overall Score: {$overall_score}, Readiness: {$report['geo_readiness']}");
            
            return $report;
        } catch (Throwable $e) {
            error_log('DODO GEO Engine: Analysis error: ' . $e->getMessage());
            return [
                'error' => true,
                'message' => 'GEO analizi sırasında hata oluştu.',
                'overall_geo_score' => 0,
                'execution_time_ms' => round((microtime(true) - $start_time) * 1000, 2)
            ];
        }
    }
    
    /**
     * Analyze conversational retrieval suitability
     */
    private function analyze_conversational_retrieval($content, $keyword) {
        $score = 0;
        $reasoning = array();
        
        // Question-answer format detection
        $qa_patterns = array('?', 'nasıl', 'neden', 'ne zaman', 'how', 'why', 'when', 'what');
        $qa_count = 0;
        foreach ($qa_patterns as $pattern) {
            $qa_count += substr_count(mb_strtolower($content, 'UTF-8'), $pattern);
        }
        
        if ($qa_count >= 5) {
            $score += 30;
            $reasoning[] = "High question-answer format presence ({$qa_count} instances)";
        } elseif ($qa_count >= 2) {
            $score += 15;
            $reasoning[] = "Moderate question-answer format";
        }
        
        // Direct answer patterns
        $answer_patterns = array('cevap:', 'answer:', 'sonuç:', 'result:', 'kısaca:', 'in short:');
        foreach ($answer_patterns as $pattern) {
            if (stripos($content, $pattern) !== false) {
                $score += 20;
                $reasoning[] = "Direct answer pattern detected";
                break;
            }
        }
        
        // Conversational tone indicators
        $conversational_terms = array('şimdi', 'öncelikle', 'örneğin', 'yani', 'first', 'for example', 'so');
        $conv_count = 0;
        foreach ($conversational_terms as $term) {
            if (stripos($content, $term) !== false) {
                $conv_count++;
            }
        }
        
        if ($conv_count >= 3) {
            $score += 20;
            $reasoning[] = "Conversational tone present";
        }
        
        // Sentence length (shorter = more conversational)
        $sentences = preg_split('/[.!?]+/', $content);
        $avg_sentence_length = 0;
        $sentence_count = 0;
        foreach ($sentences as $sentence) {
            $words = str_word_count(trim($sentence));
            if ($words > 0) {
                $avg_sentence_length += $words;
                $sentence_count++;
            }
        }
        
        if ($sentence_count > 0) {
            $avg_sentence_length = $avg_sentence_length / $sentence_count;
            if ($avg_sentence_length <= 15) {
                $score += 15;
                $reasoning[] = "Short sentences (avg {$avg_sentence_length} words) - conversational";
            } elseif ($avg_sentence_length <= 20) {
                $score += 10;
                $reasoning[] = "Moderate sentence length";
            }
        }
        
        // List usage (good for conversational retrieval)
        $list_count = substr_count($content, "\n- ") + substr_count($content, "\n* ") + substr_count($content, "\n1.");
        if ($list_count >= 3) {
            $score += 15;
            $reasoning[] = "Good list usage for structured answers";
        }
        
        $score = min(100, $score);
        
        return array(
            'score' => $score,
            'suitability' => $this->get_suitability_level($score),
            'reasoning' => $reasoning,
        );
    }
    
    /**
     * Analyze AI Overview suitability
     */
    private function analyze_ai_overview_suitability($content, $keyword) {
        $score = 0;
        $reasoning = array();
        
        // Clear structure (headings)
        $h2_count = substr_count($content, '## ');
        $h3_count = substr_count($content, '### ');
        
        if ($h2_count >= 4 && $h3_count >= 6) {
            $score += 25;
            $reasoning[] = "Well-structured content with clear headings";
        } elseif ($h2_count >= 2) {
            $score += 15;
            $reasoning[] = "Basic structure present";
        }
        
        // Factual statements (numbers, data)
        $number_count = preg_match_all('/\b\d+\b/', $content);
        if ($number_count >= 5) {
            $score += 20;
            $reasoning[] = "Data-rich content with factual information";
        }
        
        // Definition presence
        if (preg_match('/\b(nedir|ne demek|tanım|definition|means|is)\b/i', $content)) {
            $score += 15;
            $reasoning[] = "Contains definitions - good for AI Overview";
        }
        
        // Comparison elements
        if (preg_match('/\b(vs|karşı|fark|difference|compare)\b/i', $content)) {
            $score += 15;
            $reasoning[] = "Comparison content - AI Overview friendly";
        }
        
        // Step-by-step format
        $step_patterns = array('adım', 'step', '1.', '2.', '3.');
        $step_count = 0;
        foreach ($step_patterns as $pattern) {
            $step_count += substr_count(mb_strtolower($content, 'UTF-8'), $pattern);
        }
        
        if ($step_count >= 3) {
            $score += 15;
            $reasoning[] = "Step-by-step format detected";
        }
        
        // Keyword presence in first 200 chars
        if (!empty($keyword) && stripos(substr($content, 0, 200), $keyword) !== false) {
            $score += 10;
            $reasoning[] = "Keyword in introduction - clear topic signal";
        }
        
        $score = min(100, $score);
        
        return array(
            'score' => $score,
            'suitability' => $this->get_suitability_level($score),
            'reasoning' => $reasoning,
        );
    }
    
    /**
     * Analyze answer block readiness
     */
    private function analyze_answer_block_readiness($content) {
        $score = 0;
        $reasoning = array();
        
        // Concise paragraphs (ideal for answer blocks)
        $paragraphs = explode("\n\n", $content);
        $short_paragraphs = 0;
        foreach ($paragraphs as $para) {
            $word_count = str_word_count(strip_tags($para));
            if ($word_count >= 30 && $word_count <= 60) {
                $short_paragraphs++;
            }
        }
        
        if ($short_paragraphs >= 3) {
            $score += 30;
            $reasoning[] = "Multiple concise paragraphs (30-60 words) - ideal for answer blocks";
        }
        
        // Bold/emphasis usage
        $bold_count = substr_count($content, '**') / 2;
        if ($bold_count >= 3) {
            $score += 15;
            $reasoning[] = "Good use of emphasis for key points";
        }
        
        // List format
        $list_items = substr_count($content, "\n- ") + substr_count($content, "\n* ");
        if ($list_items >= 3) {
            $score += 25;
            $reasoning[] = "List format present - excellent for answer blocks";
        }
        
        // Clear topic sentences
        $topic_indicators = array('önemli', 'temel', 'ana', 'key', 'main', 'important');
        $topic_count = 0;
        foreach ($topic_indicators as $indicator) {
            if (stripos($content, $indicator) !== false) {
                $topic_count++;
            }
        }
        
        if ($topic_count >= 2) {
            $score += 15;
            $reasoning[] = "Clear topic indicators present";
        }
        
        // FAQ section
        if (stripos($content, 'sıkça sorulan') !== false || stripos($content, 'faq') !== false) {
            $score += 15;
            $reasoning[] = "FAQ section detected - perfect for answer blocks";
        }
        
        $score = min(100, $score);
        
        return array(
            'score' => $score,
            'readiness' => $this->get_readiness_level($score),
            'reasoning' => $reasoning,
        );
    }
    
    /**
     * Analyze citation readiness
     */
    private function analyze_citation_readiness($content) {
        $score = 50; // Base score
        $reasoning = array();
        
        // Authoritative language
        $authority_terms = array('araştırma', 'çalışma', 'uzman', 'research', 'study', 'expert', 'according to');
        $authority_count = 0;
        foreach ($authority_terms as $term) {
            if (stripos($content, $term) !== false) {
                $authority_count++;
            }
        }
        
        if ($authority_count >= 2) {
            $score += 20;
            $reasoning[] = "Authoritative language present - citation-worthy";
        }
        
        // Data/statistics
        $stat_patterns = array('%', 'oran', 'rate', 'istatistik', 'statistic');
        foreach ($stat_patterns as $pattern) {
            if (stripos($content, $pattern) !== false) {
                $score += 15;
                $reasoning[] = "Contains data/statistics - increases citation value";
                break;
            }
        }
        
        // Clear attribution
        if (preg_match('/\b(göre|according|based on|kaynak|source)\b/i', $content)) {
            $score += 15;
            $reasoning[] = "Attribution language present";
        }
        
        $score = min(100, $score);
        
        return array(
            'score' => $score,
            'readiness' => $this->get_readiness_level($score),
            'reasoning' => $reasoning,
        );
    }
    
    /**
     * Analyze chunk extraction quality
     */
    private function analyze_chunk_extraction($content) {
        $score = 0;
        $reasoning = array();
        
        // Paragraph independence (can each paragraph stand alone?)
        $paragraphs = explode("\n\n", $content);
        $independent_paragraphs = 0;
        
        foreach ($paragraphs as $para) {
            $word_count = str_word_count(strip_tags($para));
            // Good chunk: 40-150 words, starts with capital, ends with period
            if ($word_count >= 40 && $word_count <= 150) {
                $independent_paragraphs++;
            }
        }
        
        if ($independent_paragraphs >= 4) {
            $score += 40;
            $reasoning[] = "Multiple independent paragraphs - excellent chunk quality";
        } elseif ($independent_paragraphs >= 2) {
            $score += 25;
            $reasoning[] = "Some independent paragraphs present";
        }
        
        // Heading distribution
        $total_headings = substr_count($content, '## ') + substr_count($content, '### ');
        $content_length = str_word_count($content);
        
        if ($content_length > 0) {
            $headings_per_500_words = ($total_headings / $content_length) * 500;
            if ($headings_per_500_words >= 3 && $headings_per_500_words <= 6) {
                $score += 30;
                $reasoning[] = "Optimal heading distribution for chunking";
            }
        }
        
        // Transition words (helps AI understand context)
        $transitions = array('ancak', 'fakat', 'ayrıca', 'however', 'also', 'additionally', 'therefore');
        $transition_count = 0;
        foreach ($transitions as $trans) {
            if (stripos($content, $trans) !== false) {
                $transition_count++;
            }
        }
        
        if ($transition_count >= 3) {
            $score += 15;
            $reasoning[] = "Good use of transitions - context clarity";
        }
        
        // Semantic coherence (simplified - keyword repetition)
        $score += 15;
        $reasoning[] = "Base semantic coherence score";
        
        $score = min(100, $score);
        
        return array(
            'score' => $score,
            'quality' => $this->get_quality_level($score),
            'reasoning' => $reasoning,
        );
    }
    
    /**
     * Analyze entity clarity
     */
    private function analyze_entity_clarity($content, $keyword) {
        $score = 0;
        $reasoning = array();
        
        // Keyword/entity repetition (but not over-optimization)
        if (!empty($keyword)) {
            $keyword_count = substr_count(mb_strtolower($content, 'UTF-8'), mb_strtolower($keyword, 'UTF-8'));
            $word_count = str_word_count($content);
            
            if ($word_count > 0) {
                $keyword_density = ($keyword_count / $word_count) * 100;
                
                if ($keyword_density >= 0.5 && $keyword_density <= 2) {
                    $score += 30;
                    $reasoning[] = "Optimal keyword density ({$keyword_density}%) - clear entity";
                } elseif ($keyword_density > 2) {
                    $score += 10;
                    $reasoning[] = "High keyword density - may appear over-optimized";
                } else {
                    $score += 15;
                    $reasoning[] = "Low keyword density - entity could be clearer";
                }
            }
        }
        
        // Proper nouns (capitalized words - entities)
        $proper_noun_count = preg_match_all('/\b[A-ZÇĞİÖŞÜ][a-zçğıöşü]+\b/', $content);
        if ($proper_noun_count >= 5) {
            $score += 25;
            $reasoning[] = "Multiple entities detected - good entity clarity";
        }
        
        // Contextual clarity (definitions, explanations)
        if (preg_match('/\b(yani|örneğin|mesela|that is|for example|such as)\b/i', $content)) {
            $score += 20;
            $reasoning[] = "Contextual explanations present - entity clarity";
        }
        
        // Acronym definitions
        if (preg_match('/\b[A-Z]{2,}\b/', $content)) {
            $score += 15;
            $reasoning[] = "Acronyms present - technical entity clarity";
        }
        
        // Related terms (semantic field)
        $score += 10;
        $reasoning[] = "Base semantic field score";
        
        $score = min(100, $score);
        
        return array(
            'score' => $score,
            'clarity' => $this->get_clarity_level($score),
            'reasoning' => $reasoning,
        );
    }
    
    /**
     * Calculate featured snippet probability
     */
    private function calculate_featured_snippet_probability($content, $keyword) {
        $probability = 0;
        $factors = array();
        
        // Question in keyword
        $question_words = array('nasıl', 'neden', 'ne zaman', 'how', 'why', 'when', 'what');
        foreach ($question_words as $qword) {
            if (stripos($keyword, $qword) !== false) {
                $probability += 30;
                $factors[] = "Question-based keyword";
                break;
            }
        }
        
        // List format
        $list_count = substr_count($content, "\n- ") + substr_count($content, "\n1.");
        if ($list_count >= 3) {
            $probability += 25;
            $factors[] = "List format present";
        }
        
        // Table format
        if (strpos($content, '|') !== false) {
            $probability += 20;
            $factors[] = "Table format detected";
        }
        
        // Concise answer in first 200 words
        $first_200_words = implode(' ', array_slice(str_word_count(strip_tags($content), 1), 0, 200));
        if (!empty($keyword) && stripos($first_200_words, $keyword) !== false) {
            $probability += 15;
            $factors[] = "Keyword in first 200 words";
        }
        
        // Definition format
        if (preg_match('/\b(nedir|ne demek|tanım|definition|is|means)\b/i', $content)) {
            $probability += 10;
            $factors[] = "Definition format";
        }
        
        $probability = min(100, $probability);
        
        return array(
            'probability' => $probability,
            'likelihood' => $this->get_likelihood_level($probability),
            'factors' => $factors,
        );
    }
    
    /**
     * Calculate overall GEO score
     */
    private function calculate_overall_geo_score($scores) {
        // Weighted average
        $weights = array(
            'conversational' => 0.20,
            'ai_overview' => 0.25,
            'answer_block' => 0.20,
            'citation' => 0.10,
            'chunk_quality' => 0.15,
            'entity_clarity' => 0.10,
        );
        
        $total = 0;
        foreach ($scores as $key => $score) {
            $total += $score * $weights[$key];
        }
        
        return round($total);
    }
    
    /**
     * Generate recommendations
     */
    private function generate_recommendations($overall_score, $component_scores) {
        $recommendations = array();
        
        if ($overall_score < 60) {
            $recommendations[] = "Overall GEO score is low - focus on conversational format and structure";
        }
        
        if ($component_scores['conversational']['score'] < 50) {
            $recommendations[] = "Improve conversational tone - use shorter sentences and Q&A format";
        }
        
        if ($component_scores['ai_overview']['score'] < 50) {
            $recommendations[] = "Add more structure - use clear headings and factual data";
        }
        
        if ($component_scores['answer_block']['score'] < 50) {
            $recommendations[] = "Create concise paragraphs (30-60 words) and use lists";
        }
        
        if ($component_scores['citation']['score'] < 50) {
            $recommendations[] = "Add authoritative language and data/statistics";
        }
        
        if (empty($recommendations)) {
            $recommendations[] = "Content is well-optimized for GEO";
        }
        
        return $recommendations;
    }
    
    // Helper methods for level determination
    private function get_readiness_level($score) {
        if ($score >= 80) return 'excellent';
        if ($score >= 60) return 'good';
        if ($score >= 40) return 'moderate';
        if ($score >= 20) return 'low';
        return 'poor';
    }
    
    private function get_suitability_level($score) {
        return $this->get_readiness_level($score);
    }
    
    private function get_quality_level($score) {
        return $this->get_readiness_level($score);
    }
    
    private function get_clarity_level($score) {
        return $this->get_readiness_level($score);
    }
    
    private function get_likelihood_level($score) {
        if ($score >= 70) return 'high';
        if ($score >= 40) return 'medium';
        return 'low';
    }
}
