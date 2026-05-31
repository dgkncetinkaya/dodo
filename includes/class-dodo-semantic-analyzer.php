<?php
/**
 * Semantic Content Analyzer Class
 * 
 * Advanced semantic intelligence for editorial understanding
 * Detects semantic consistency, topic drift, contextual coherence
 *
 * @package DODO_AI_SEO
 * @since 1.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Semantic_Analyzer {
    
    /**
     * Semantic warning types
     */
    const WARNING_SEMANTIC_REPETITION = 'semantic_repetition';
    const WARNING_TOPIC_DRIFT = 'topic_drift';
    const WARNING_CONTEXT_WEAK = 'context_weak';
    const WARNING_KEYWORD_STUFFING = 'keyword_stuffing';
    const WARNING_DUPLICATE_MEANING = 'duplicate_meaning';
    const WARNING_PARAGRAPH_DISCONNECT = 'paragraph_disconnect';
    
    /**
     * Analyze semantic quality
     * 
     * @param string $content Post content
     * @param string $focus_keyword Focus keyword
     * @return array Semantic analysis data
     */
    public function analyze_semantic_quality($content, $focus_keyword = '') {
        $text = strip_tags($content);
        $paragraphs = $this->extract_paragraphs($content);
        
        // Calculate semantic metrics
        $consistency_score = $this->calculate_semantic_consistency($paragraphs, $focus_keyword);
        $coherence_score = $this->calculate_contextual_coherence($paragraphs);
        $repetition_score = $this->detect_semantic_repetition($paragraphs);
        $topic_drift_score = $this->detect_topic_drift($paragraphs, $focus_keyword);
        $keyword_stuffing_score = $this->detect_keyword_stuffing($text, $focus_keyword);
        
        // Overall semantic health (0-100)
        $semantic_health = $this->calculate_semantic_health([
            'consistency' => $consistency_score,
            'coherence' => $coherence_score,
            'repetition' => $repetition_score,
            'topic_drift' => $topic_drift_score,
            'keyword_stuffing' => $keyword_stuffing_score,
        ]);
        
        // Detect warnings
        $warnings = $this->detect_semantic_warnings([
            'consistency' => $consistency_score,
            'coherence' => $coherence_score,
            'repetition' => $repetition_score,
            'topic_drift' => $topic_drift_score,
            'keyword_stuffing' => $keyword_stuffing_score,
        ], $paragraphs);
        
        return [
            'semantic_health' => $semantic_health,
            'consistency_score' => $consistency_score,
            'coherence_score' => $coherence_score,
            'repetition_score' => $repetition_score,
            'topic_drift_score' => $topic_drift_score,
            'keyword_stuffing_score' => $keyword_stuffing_score,
            'warnings' => $warnings,
            'paragraph_count' => count($paragraphs),
        ];
    }
    
    /**
     * Calculate semantic consistency
     * 
     * @param array $paragraphs Content paragraphs
     * @param string $focus_keyword Focus keyword
     * @return int Score 0-100
     */
    private function calculate_semantic_consistency($paragraphs, $focus_keyword) {
        if (empty($paragraphs) || count($paragraphs) < 2) {
            return 50; // Neutral for short content
        }
        
        $score = 100;
        
        // Check keyword distribution consistency
        if (!empty($focus_keyword)) {
            $keyword_distribution = [];
            foreach ($paragraphs as $para) {
                $keyword_distribution[] = substr_count(strtolower($para), strtolower($focus_keyword));
            }
            
            // Calculate variance
            $mean = array_sum($keyword_distribution) / count($keyword_distribution);
            if ($mean > 0) {
                $variance = 0;
                foreach ($keyword_distribution as $count) {
                    $variance += pow($count - $mean, 2);
                }
                $variance /= count($keyword_distribution);
                
                // High variance = inconsistent keyword usage
                if ($variance > 4) {
                    $score -= 20;
                } elseif ($variance > 2) {
                    $score -= 10;
                }
            }
        }
        
        // Check paragraph length consistency
        $lengths = array_map('str_word_count', $paragraphs);
        $avg_length = array_sum($lengths) / count($lengths);
        
        $length_variance = 0;
        foreach ($lengths as $length) {
            $length_variance += pow($length - $avg_length, 2);
        }
        $length_variance /= count($lengths);
        
        // Very inconsistent paragraph lengths
        if ($length_variance > 10000) {
            $score -= 15;
        } elseif ($length_variance > 5000) {
            $score -= 8;
        }
        
        return max(0, min(100, $score));
    }
    
    /**
     * Calculate contextual coherence
     * 
     * @param array $paragraphs Content paragraphs
     * @return int Score 0-100
     */
    private function calculate_contextual_coherence($paragraphs) {
        if (empty($paragraphs) || count($paragraphs) < 2) {
            return 70; // Neutral for short content
        }
        
        $score = 100;
        
        // Check for transition words between paragraphs
        $transition_words = [
            'ancak', 'fakat', 'lakin', 'ama', 'oysa',
            'ayrıca', 'bunun yanında', 'dahası', 'üstelik',
            'sonuç olarak', 'bu nedenle', 'dolayısıyla', 'böylece',
            'örneğin', 'mesela', 'şöyle ki',
            'öncelikle', 'ilk olarak', 'son olarak', 'sonunda',
            'diğer yandan', 'buna karşın', 'aksine',
        ];
        
        $transition_count = 0;
        foreach ($paragraphs as $para) {
            $para_lower = mb_strtolower($para);
            foreach ($transition_words as $word) {
                if (mb_strpos($para_lower, $word) !== false) {
                    $transition_count++;
                    break; // Count once per paragraph
                }
            }
        }
        
        $transition_ratio = $transition_count / count($paragraphs);
        
        // Too few transitions = weak coherence
        if ($transition_ratio < 0.2) {
            $score -= 25;
        } elseif ($transition_ratio < 0.4) {
            $score -= 15;
        }
        
        // Check for abrupt topic changes (paragraph similarity)
        $similarity_scores = [];
        for ($i = 0; $i < count($paragraphs) - 1; $i++) {
            $similarity = $this->calculate_paragraph_similarity($paragraphs[$i], $paragraphs[$i + 1]);
            $similarity_scores[] = $similarity;
        }
        
        if (!empty($similarity_scores)) {
            $avg_similarity = array_sum($similarity_scores) / count($similarity_scores);
            
            // Very low similarity = disconnected paragraphs
            if ($avg_similarity < 0.1) {
                $score -= 20;
            } elseif ($avg_similarity < 0.2) {
                $score -= 10;
            }
        }
        
        return max(0, min(100, $score));
    }
    
    /**
     * Detect semantic repetition
     * 
     * @param array $paragraphs Content paragraphs
     * @return int Score 0-100 (higher = more repetition)
     */
    private function detect_semantic_repetition($paragraphs) {
        if (empty($paragraphs) || count($paragraphs) < 2) {
            return 0; // No repetition in short content
        }
        
        $repetition_score = 0;
        
        // Check for repeated phrases (3+ words)
        $phrases = [];
        foreach ($paragraphs as $para) {
            $words = preg_split('/\s+/', strtolower(strip_tags($para)));
            for ($i = 0; $i < count($words) - 2; $i++) {
                $phrase = $words[$i] . ' ' . $words[$i + 1] . ' ' . $words[$i + 2];
                if (strlen($phrase) > 15) { // Skip short phrases
                    $phrases[] = $phrase;
                }
            }
        }
        
        // Count duplicates
        $phrase_counts = array_count_values($phrases);
        foreach ($phrase_counts as $count) {
            if ($count > 1) {
                $repetition_score += ($count - 1) * 10;
            }
        }
        
        // Check for similar sentence structures
        $sentence_patterns = [];
        foreach ($paragraphs as $para) {
            $sentences = preg_split('/[.!?]+/', $para);
            foreach ($sentences as $sentence) {
                $pattern = $this->extract_sentence_pattern($sentence);
                if (!empty($pattern)) {
                    $sentence_patterns[] = $pattern;
                }
            }
        }
        
        $pattern_counts = array_count_values($sentence_patterns);
        foreach ($pattern_counts as $count) {
            if ($count > 3) {
                $repetition_score += ($count - 3) * 5;
            }
        }
        
        return min(100, $repetition_score);
    }
    
    /**
     * Detect topic drift
     * 
     * @param array $paragraphs Content paragraphs
     * @param string $focus_keyword Focus keyword
     * @return int Score 0-100 (higher = more drift)
     */
    private function detect_topic_drift($paragraphs, $focus_keyword) {
        if (empty($paragraphs) || count($paragraphs) < 3) {
            return 0; // Can't detect drift in short content
        }
        
        $drift_score = 0;
        
        // Analyze keyword presence across sections
        if (!empty($focus_keyword)) {
            $section_size = max(1, floor(count($paragraphs) / 3));
            $sections = array_chunk($paragraphs, $section_size);
            
            $keyword_presence = [];
            foreach ($sections as $section) {
                $section_text = implode(' ', $section);
                $keyword_count = substr_count(strtolower($section_text), strtolower($focus_keyword));
                $keyword_presence[] = $keyword_count;
            }
            
            // Check if keyword disappears in middle/end sections
            if (count($keyword_presence) >= 3) {
                if ($keyword_presence[0] > 0 && $keyword_presence[1] == 0) {
                    $drift_score += 30; // Topic drift in middle
                }
                if ($keyword_presence[0] > 0 && $keyword_presence[2] == 0) {
                    $drift_score += 25; // Topic drift at end
                }
            }
        }
        
        // Check for vocabulary shift (different word usage in different sections)
        if (count($paragraphs) >= 6) {
            $first_half = array_slice($paragraphs, 0, floor(count($paragraphs) / 2));
            $second_half = array_slice($paragraphs, floor(count($paragraphs) / 2));
            
            $first_vocab = $this->extract_vocabulary(implode(' ', $first_half));
            $second_vocab = $this->extract_vocabulary(implode(' ', $second_half));
            
            $common_words = array_intersect($first_vocab, $second_vocab);
            $vocab_overlap = count($common_words) / max(count($first_vocab), count($second_vocab));
            
            // Low overlap = topic drift
            if ($vocab_overlap < 0.3) {
                $drift_score += 25;
            } elseif ($vocab_overlap < 0.5) {
                $drift_score += 15;
            }
        }
        
        return min(100, $drift_score);
    }
    
    /**
     * Detect keyword stuffing
     * 
     * @param string $text Content text
     * @param string $focus_keyword Focus keyword
     * @return int Score 0-100 (higher = more stuffing)
     */
    private function detect_keyword_stuffing($text, $focus_keyword) {
        if (empty($focus_keyword) || empty($text)) {
            return 0;
        }
        
        $stuffing_score = 0;
        $word_count = str_word_count($text);
        
        if ($word_count == 0) {
            return 0;
        }
        
        // Calculate keyword density
        $keyword_count = substr_count(strtolower($text), strtolower($focus_keyword));
        $density = ($keyword_count / $word_count) * 100;
        
        // Excessive density
        if ($density > 3.5) {
            $stuffing_score += 50;
        } elseif ($density > 2.5) {
            $stuffing_score += 30;
        } elseif ($density > 2.0) {
            $stuffing_score += 15;
        }
        
        // Check for keyword clustering (multiple keywords in same sentence)
        $sentences = preg_split('/[.!?]+/', $text);
        foreach ($sentences as $sentence) {
            $sentence_keyword_count = substr_count(strtolower($sentence), strtolower($focus_keyword));
            if ($sentence_keyword_count > 2) {
                $stuffing_score += 10;
            }
        }
        
        // Check for unnatural keyword variations
        $keyword_words = explode(' ', $focus_keyword);
        if (count($keyword_words) > 1) {
            $variation_count = 0;
            foreach ($keyword_words as $word) {
                if (strlen($word) > 3) {
                    $variation_count += substr_count(strtolower($text), strtolower($word));
                }
            }
            
            $variation_density = ($variation_count / $word_count) * 100;
            if ($variation_density > 5) {
                $stuffing_score += 20;
            }
        }
        
        return min(100, $stuffing_score);
    }
    
    /**
     * Calculate overall semantic health
     * 
     * @param array $metrics Semantic metrics
     * @return int Score 0-100
     */
    private function calculate_semantic_health($metrics) {
        $health = 100;
        
        // Consistency (weight: 25%)
        $health -= (100 - $metrics['consistency']) * 0.25;
        
        // Coherence (weight: 30%)
        $health -= (100 - $metrics['coherence']) * 0.30;
        
        // Repetition penalty (weight: 20%)
        $health -= $metrics['repetition'] * 0.20;
        
        // Topic drift penalty (weight: 15%)
        $health -= $metrics['topic_drift'] * 0.15;
        
        // Keyword stuffing penalty (weight: 10%)
        $health -= $metrics['keyword_stuffing'] * 0.10;
        
        return max(0, min(100, round($health)));
    }
    
    /**
     * Detect semantic warnings
     * 
     * @param array $metrics Semantic metrics
     * @param array $paragraphs Content paragraphs
     * @return array Warnings
     */
    private function detect_semantic_warnings($metrics, $paragraphs) {
        $warnings = [];
        
        // Semantic repetition
        if ($metrics['repetition'] > 40) {
            $warnings[] = [
                'type' => self::WARNING_SEMANTIC_REPETITION,
                'severity' => 'high',
                'message' => __('Semantic repetition detected - same ideas repeated in different words', 'dodo-ai-seo'),
            ];
        } elseif ($metrics['repetition'] > 25) {
            $warnings[] = [
                'type' => self::WARNING_SEMANTIC_REPETITION,
                'severity' => 'medium',
                'message' => __('Moderate semantic repetition detected', 'dodo-ai-seo'),
            ];
        }
        
        // Topic drift
        if ($metrics['topic_drift'] > 50) {
            $warnings[] = [
                'type' => self::WARNING_TOPIC_DRIFT,
                'severity' => 'high',
                'message' => __('Topic drift detected - content loses focus in later sections', 'dodo-ai-seo'),
            ];
        } elseif ($metrics['topic_drift'] > 30) {
            $warnings[] = [
                'type' => self::WARNING_TOPIC_DRIFT,
                'severity' => 'medium',
                'message' => __('Moderate topic drift detected', 'dodo-ai-seo'),
            ];
        }
        
        // Context consistency
        if ($metrics['coherence'] < 50) {
            $warnings[] = [
                'type' => self::WARNING_CONTEXT_WEAK,
                'severity' => 'high',
                'message' => __('Context consistency weak - paragraphs feel disconnected', 'dodo-ai-seo'),
            ];
        } elseif ($metrics['coherence'] < 70) {
            $warnings[] = [
                'type' => self::WARNING_CONTEXT_WEAK,
                'severity' => 'medium',
                'message' => __('Context consistency could be improved', 'dodo-ai-seo'),
            ];
        }
        
        // Keyword stuffing
        if ($metrics['keyword_stuffing'] > 50) {
            $warnings[] = [
                'type' => self::WARNING_KEYWORD_STUFFING,
                'severity' => 'high',
                'message' => __('Excessive keyword variation usage detected', 'dodo-ai-seo'),
            ];
        } elseif ($metrics['keyword_stuffing'] > 30) {
            $warnings[] = [
                'type' => self::WARNING_KEYWORD_STUFFING,
                'severity' => 'medium',
                'message' => __('Keyword density slightly high', 'dodo-ai-seo'),
            ];
        }
        
        // Paragraph disconnect
        if ($metrics['consistency'] < 60) {
            $warnings[] = [
                'type' => self::WARNING_PARAGRAPH_DISCONNECT,
                'severity' => 'medium',
                'message' => __('Paragraph relationships could be stronger', 'dodo-ai-seo'),
            ];
        }
        
        return $warnings;
    }
    
    // ==========================================
    // HELPER METHODS
    // ==========================================
    
    /**
     * Extract paragraphs from content
     */
    private function extract_paragraphs($content) {
        preg_match_all('/<p[^>]*>(.*?)<\/p>/is', $content, $matches);
        return array_filter($matches[1], function($p) {
            return !empty(trim(strip_tags($p)));
        });
    }
    
    /**
     * Calculate paragraph similarity (simple word overlap)
     */
    private function calculate_paragraph_similarity($para1, $para2) {
        $words1 = array_unique(preg_split('/\s+/', strtolower(strip_tags($para1))));
        $words2 = array_unique(preg_split('/\s+/', strtolower(strip_tags($para2))));
        
        $common = array_intersect($words1, $words2);
        $total = array_unique(array_merge($words1, $words2));
        
        return count($total) > 0 ? count($common) / count($total) : 0;
    }
    
    /**
     * Extract sentence pattern (simplified)
     */
    private function extract_sentence_pattern($sentence) {
        $sentence = trim(strip_tags($sentence));
        if (strlen($sentence) < 10) {
            return '';
        }
        
        // Extract first 3 words as pattern
        $words = preg_split('/\s+/', strtolower($sentence));
        if (count($words) < 3) {
            return '';
        }
        
        return $words[0] . ' ' . $words[1] . ' ' . $words[2];
    }
    
    /**
     * Extract vocabulary (unique meaningful words)
     */
    private function extract_vocabulary($text) {
        $text = strtolower(strip_tags($text));
        $words = preg_split('/\s+/', $text);
        
        // Filter stop words and short words
        $stop_words = ['bir', 've', 'veya', 'ile', 'için', 'bu', 'şu', 'o', 'da', 'de', 'ki'];
        $vocab = [];
        
        foreach ($words as $word) {
            if (strlen($word) > 3 && !in_array($word, $stop_words)) {
                $vocab[] = $word;
            }
        }
        
        return array_unique($vocab);
    }
}
