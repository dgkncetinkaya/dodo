<?php
/**
 * DODO Change Intent Classifier
 * 
 * Classifies WHAT TYPE of change happened, not just HOW MUCH.
 * Transforms AI from "rewriter" to "editor".
 * 
 * Detects:
 * - Grammar fixes
 * - Punctuation fixes
 * - Readability improvements
 * - Semantic reframing (FORBIDDEN in LOW mode)
 * - Tone shifts (FORBIDDEN in LOW mode)
 * - Metaphor changes (FORBIDDEN in LOW mode)
 * - Sentence reconstruction (LIMITED in LOW mode)
 * - Structural changes
 * - Content expansion
 * 
 * Philosophy:
 * EDITOR ≠ WRITER
 * A writer changes expression style.
 * An editor preserves expression style unless clarity is broken.
 * 
 * @package DODO_AI_SEO
 * @since 1.7.0
 */

// Doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

class DODO_Change_Intent_Classifier {
    
    /**
     * Turkish semantic reframing patterns
     * 
     * These are stylistic rewrites, not edits.
     */
    private const SEMANTIC_REFRAMING_PATTERNS = array(
        // Common Turkish stylistic pairs
        array('sahneye çık', 'ön plana çık'),
        array('ön plana çık', 'sahneye çık'),
        array('kalbi', 'can damarı'),
        array('can damarı', 'kalbi'),
        array('hayati rol oyna', 'hayati öneme sahip'),
        array('hayati öneme sahip', 'hayati rol oyna'),
        array('önemli rol oyna', 'önem taşı'),
        array('önem taşı', 'önemli rol oyna'),
        array('büyük önem', 'hayati önem'),
        array('hayati önem', 'büyük önem'),
        array('temel oluştur', 'temel taşı'),
        array('temel taşı', 'temel oluştur'),
        array('kritik öneme sahip', 'hayati öneme sahip'),
        array('hayati öneme sahip', 'kritik öneme sahip'),
        array('merkezi rol', 'önemli rol'),
        array('önemli rol', 'merkezi rol'),
        array('kilit rol', 'önemli rol'),
        array('önemli rol', 'kilit rol'),
    );
    
    /**
     * Metaphor replacement patterns
     */
    private const METAPHOR_PATTERNS = array(
        array('kalp', 'can damarı'),
        array('can damarı', 'kalp'),
        array('omurga', 'temel'),
        array('temel', 'omurga'),
        array('iskelet', 'yapı'),
        array('yapı', 'iskelet'),
        array('motor', 'itici güç'),
        array('itici güç', 'motor'),
    );
    
    /**
     * Tone shift indicators
     */
    private const TONE_SHIFT_INDICATORS = array(
        // Formal → Informal
        array('gerçekleştirmek', 'yapmak'),
        array('kullanmak', 'kullanmak'),
        array('sağlamak', 'vermek'),
        // Informal → Formal
        array('yapmak', 'gerçekleştirmek'),
        array('vermek', 'sağlamak'),
        // Dramatic → Neutral
        array('devrim niteliğinde', 'önemli'),
        array('çığır açan', 'yenilikçi'),
        // Neutral → Dramatic
        array('önemli', 'devrim niteliğinde'),
        array('yenilikçi', 'çığır açan'),
    );
    
    /**
     * Classify change intent
     * 
     * @param string $original Original content
     * @param string $improved Improved content
     * @return array Classification results
     */
    public function classify($original, $improved) {
        // Normalize for analysis
        $original_normalized = $this->normalize_for_analysis($original);
        $improved_normalized = $this->normalize_for_analysis($improved);
        
        // Initialize counters
        $intent = array(
            'grammar_fix' => 0,
            'punctuation_fix' => 0,
            'readability_fix' => 0,
            'semantic_reframing' => 0,
            'tone_shift' => 0,
            'metaphor_change' => 0,
            'sentence_reconstruction' => 0,
            'structural_change' => 0,
            'expansion' => 0,
            'typo_fix' => 0,
            'duplicate_cleanup' => 0,
            'transition_improvement' => 0,
        );
        
        // Detect grammar fixes
        $intent['grammar_fix'] = $this->detect_grammar_fixes($original_normalized, $improved_normalized);
        
        // Detect punctuation fixes
        $intent['punctuation_fix'] = $this->detect_punctuation_fixes($original, $improved);
        
        // Detect readability improvements
        $intent['readability_fix'] = $this->detect_readability_improvements($original_normalized, $improved_normalized);
        
        // Detect semantic reframing (CRITICAL for LOW mode)
        $intent['semantic_reframing'] = $this->detect_semantic_reframing($original_normalized, $improved_normalized);
        
        // Detect tone shifts
        $intent['tone_shift'] = $this->detect_tone_shifts($original_normalized, $improved_normalized);
        
        // Detect metaphor changes
        $intent['metaphor_change'] = $this->detect_metaphor_changes($original_normalized, $improved_normalized);
        
        // Detect sentence reconstruction
        $intent['sentence_reconstruction'] = $this->detect_sentence_reconstruction($original_normalized, $improved_normalized);
        
        // Detect structural changes
        $intent['structural_change'] = $this->detect_structural_changes($original, $improved);
        
        // Detect expansion
        $intent['expansion'] = $this->detect_expansion($original_normalized, $improved_normalized);
        
        // Detect typo fixes
        $intent['typo_fix'] = $this->detect_typo_fixes($original_normalized, $improved_normalized);
        
        // Detect duplicate cleanup
        $intent['duplicate_cleanup'] = $this->detect_duplicate_cleanup($original_normalized, $improved_normalized);
        
        // Detect transition improvements
        $intent['transition_improvement'] = $this->detect_transition_improvements($original_normalized, $improved_normalized);
        
        // Calculate overall classification
        $classification = $this->calculate_overall_classification($intent);
        
        // Debug logging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[DODO CHANGE INTENT] Classification: ' . json_encode($intent));
            error_log('[DODO CHANGE INTENT] Overall: ' . $classification);
        }
        
        return array(
            'intent' => $intent,
            'classification' => $classification,
            'is_editorial_safe' => $this->is_editorial_safe($intent),
            'forbidden_changes' => $this->get_forbidden_changes($intent),
        );
    }
    
    /**
     * Normalize text for analysis
     * 
     * @param string $text
     * @return string
     */
    private function normalize_for_analysis($text) {
        // Strip HTML
        $text = wp_strip_all_tags($text);
        
        // Lowercase
        $text = mb_strtolower($text, 'UTF-8');
        
        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        
        return trim($text);
    }
    
    /**
     * Detect grammar fixes
     * 
     * @param string $original
     * @param string $improved
     * @return int Count
     */
    private function detect_grammar_fixes($original, $improved) {
        $count = 0;
        
        // Common Turkish grammar patterns
        $grammar_patterns = array(
            // Subject-verb agreement
            array('/\b(makine|cihaz|sistem)\s+(çalışıyorlar|yapıyorlar)\b/', '\1 çalışıyor'),
            // Case suffix errors
            array('/\b(makine|cihaz)nin\b/', '\1nin'), // Possessive
            // Plural errors
            array('/\b(makine|cihaz)ler\s+var\b/', '\1ler var'),
        );
        
        foreach ($grammar_patterns as $pattern) {
            if (isset($pattern[0]) && isset($pattern[1])) {
                $orig_matches = preg_match_all($pattern[0], $original);
                $imp_matches = preg_match_all($pattern[0], $improved);
                
                if ($orig_matches > $imp_matches) {
                    $count += ($orig_matches - $imp_matches);
                }
            }
        }
        
        return $count;
    }
    
    /**
     * Detect punctuation fixes
     * 
     * @param string $original
     * @param string $improved
     * @return int Count
     */
    private function detect_punctuation_fixes($original, $improved) {
        $count = 0;
        
        // Count punctuation differences
        $orig_punct = preg_match_all('/[.,!?;:]/', $original);
        $imp_punct = preg_match_all('/[.,!?;:]/', $improved);
        
        // If punctuation count changed, it's likely a fix
        if ($orig_punct !== $imp_punct) {
            $count = abs($orig_punct - $imp_punct);
        }
        
        // Check for missing spaces after punctuation
        $orig_missing_spaces = preg_match_all('/[.,!?;:][^\s]/', $original);
        $imp_missing_spaces = preg_match_all('/[.,!?;:][^\s]/', $improved);
        
        if ($orig_missing_spaces > $imp_missing_spaces) {
            $count += ($orig_missing_spaces - $imp_missing_spaces);
        }
        
        return $count;
    }
    
    /**
     * Detect readability improvements
     * 
     * @param string $original
     * @param string $improved
     * @return int Count
     */
    private function detect_readability_improvements($original, $improved) {
        $count = 0;
        
        // Check sentence length reduction
        $orig_sentences = preg_split('/[.!?]+/', $original, -1, PREG_SPLIT_NO_EMPTY);
        $imp_sentences = preg_split('/[.!?]+/', $improved, -1, PREG_SPLIT_NO_EMPTY);
        
        $orig_avg_length = 0;
        $imp_avg_length = 0;
        
        if (count($orig_sentences) > 0) {
            $orig_avg_length = array_sum(array_map('strlen', $orig_sentences)) / count($orig_sentences);
        }
        
        if (count($imp_sentences) > 0) {
            $imp_avg_length = array_sum(array_map('strlen', $imp_sentences)) / count($imp_sentences);
        }
        
        // If average sentence length decreased significantly, it's a readability improvement
        if ($orig_avg_length > 0 && $imp_avg_length < $orig_avg_length * 0.8) {
            $count++;
        }
        
        return $count;
    }
    
    /**
     * Detect semantic reframing (CRITICAL)
     * 
     * This is the key detector for LOW mode validation.
     * 
     * @param string $original
     * @param string $improved
     * @return int Count
     */
    private function detect_semantic_reframing($original, $improved) {
        $count = 0;
        
        // Check against known semantic reframing patterns
        foreach (self::SEMANTIC_REFRAMING_PATTERNS as $pattern) {
            $from = $pattern[0];
            $to = $pattern[1];
            
            // Check if original has $from and improved has $to
            if (mb_strpos($original, $from) !== false && mb_strpos($improved, $to) !== false) {
                // Verify it's actually a replacement
                if (mb_strpos($improved, $from) === false) {
                    $count++;
                    
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log("[DODO CHANGE INTENT] Semantic reframing detected: '{$from}' → '{$to}'");
                    }
                }
            }
        }
        
        return $count;
    }
    
    /**
     * Detect tone shifts
     * 
     * @param string $original
     * @param string $improved
     * @return int Count
     */
    private function detect_tone_shifts($original, $improved) {
        $count = 0;
        
        // Check against tone shift indicators
        foreach (self::TONE_SHIFT_INDICATORS as $pattern) {
            $from = $pattern[0];
            $to = $pattern[1];
            
            if (mb_strpos($original, $from) !== false && mb_strpos($improved, $to) !== false) {
                if (mb_strpos($improved, $from) === false) {
                    $count++;
                    
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log("[DODO CHANGE INTENT] Tone shift detected: '{$from}' → '{$to}'");
                    }
                }
            }
        }
        
        return $count;
    }
    
    /**
     * Detect metaphor changes
     * 
     * @param string $original
     * @param string $improved
     * @return int Count
     */
    private function detect_metaphor_changes($original, $improved) {
        $count = 0;
        
        // Check against metaphor patterns
        foreach (self::METAPHOR_PATTERNS as $pattern) {
            $from = $pattern[0];
            $to = $pattern[1];
            
            if (mb_strpos($original, $from) !== false && mb_strpos($improved, $to) !== false) {
                if (mb_strpos($improved, $from) === false) {
                    $count++;
                    
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log("[DODO CHANGE INTENT] Metaphor change detected: '{$from}' → '{$to}'");
                    }
                }
            }
        }
        
        return $count;
    }
    
    /**
     * Detect sentence reconstruction
     * 
     * @param string $original
     * @param string $improved
     * @return int Count
     */
    private function detect_sentence_reconstruction($original, $improved) {
        $count = 0;
        
        // Split into sentences
        $orig_sentences = preg_split('/[.!?]+/', $original, -1, PREG_SPLIT_NO_EMPTY);
        $imp_sentences = preg_split('/[.!?]+/', $improved, -1, PREG_SPLIT_NO_EMPTY);
        
        // Compare each sentence
        $max_sentences = min(count($orig_sentences), count($imp_sentences));
        
        for ($i = 0; $i < $max_sentences; $i++) {
            $orig_sent = trim($orig_sentences[$i]);
            $imp_sent = trim($imp_sentences[$i]);
            
            // Calculate word order preservation
            $orig_words = preg_split('/\s+/', $orig_sent);
            $imp_words = preg_split('/\s+/', $imp_sent);
            
            // If word order changed significantly, it's reconstruction
            $common_words = array_intersect($orig_words, $imp_words);
            $preservation_ratio = count($common_words) / max(count($orig_words), 1);
            
            // If less than 70% of words are preserved in order, it's reconstruction
            if ($preservation_ratio < 0.7 && count($orig_words) > 3) {
                $count++;
            }
        }
        
        return $count;
    }
    
    /**
     * Detect structural changes
     * 
     * @param string $original
     * @param string $improved
     * @return int Count
     */
    private function detect_structural_changes($original, $improved) {
        $count = 0;
        
        // Count paragraphs
        $orig_paragraphs = preg_split('/\n\n+/', trim($original), -1, PREG_SPLIT_NO_EMPTY);
        $imp_paragraphs = preg_split('/\n\n+/', trim($improved), -1, PREG_SPLIT_NO_EMPTY);
        
        // If paragraph count changed, it's a structural change
        if (count($orig_paragraphs) !== count($imp_paragraphs)) {
            $count = abs(count($orig_paragraphs) - count($imp_paragraphs));
        }
        
        return $count;
    }
    
    /**
     * Detect expansion
     * 
     * @param string $original
     * @param string $improved
     * @return int Percentage
     */
    private function detect_expansion($original, $improved) {
        $orig_words = str_word_count($original, 0, 'ÇçĞğİıÖöŞşÜü');
        $imp_words = str_word_count($improved, 0, 'ÇçĞğİıÖöŞşÜü');
        
        if ($orig_words === 0) {
            return 0;
        }
        
        $expansion_ratio = (($imp_words - $orig_words) / $orig_words) * 100;
        
        return max(0, round($expansion_ratio));
    }
    
    /**
     * Detect typo fixes
     * 
     * @param string $original
     * @param string $improved
     * @return int Count
     */
    private function detect_typo_fixes($original, $improved) {
        // Simple heuristic: single character differences in words
        $orig_words = preg_split('/\s+/', $original);
        $imp_words = preg_split('/\s+/', $improved);
        
        $count = 0;
        $max_words = min(count($orig_words), count($imp_words));
        
        for ($i = 0; $i < $max_words; $i++) {
            $orig_word = $orig_words[$i];
            $imp_word = $imp_words[$i];
            
            // If words are very similar (1-2 char difference), likely a typo fix
            $lev_distance = levenshtein($orig_word, $imp_word);
            
            if ($lev_distance > 0 && $lev_distance <= 2 && strlen($orig_word) > 3) {
                $count++;
            }
        }
        
        return $count;
    }
    
    /**
     * Detect duplicate cleanup
     * 
     * @param string $original
     * @param string $improved
     * @return int Count
     */
    private function detect_duplicate_cleanup($original, $improved) {
        $count = 0;
        
        // Detect repeated words
        $orig_repeated = preg_match_all('/\b(\w+)\s+\1\b/i', $original);
        $imp_repeated = preg_match_all('/\b(\w+)\s+\1\b/i', $improved);
        
        if ($orig_repeated > $imp_repeated) {
            $count = $orig_repeated - $imp_repeated;
        }
        
        return $count;
    }
    
    /**
     * Detect transition improvements
     * 
     * @param string $original
     * @param string $improved
     * @return int Count
     */
    private function detect_transition_improvements($original, $improved) {
        $count = 0;
        
        // Common Turkish transition words
        $transitions = array('ancak', 'fakat', 'lakin', 'bununla birlikte', 'öte yandan', 'ayrıca', 'dahası', 'sonuç olarak');
        
        $orig_transitions = 0;
        $imp_transitions = 0;
        
        foreach ($transitions as $transition) {
            $orig_transitions += substr_count($original, $transition);
            $imp_transitions += substr_count($improved, $transition);
        }
        
        // If transitions increased, it's an improvement
        if ($imp_transitions > $orig_transitions) {
            $count = $imp_transitions - $orig_transitions;
        }
        
        return $count;
    }
    
    /**
     * Calculate overall classification
     * 
     * @param array $intent
     * @return string Classification
     */
    private function calculate_overall_classification($intent) {
        // Check for forbidden changes
        if ($intent['semantic_reframing'] > 0 || $intent['tone_shift'] > 0 || $intent['metaphor_change'] > 0) {
            return 'semantic_rewrite';
        }
        
        // Check for heavy reconstruction
        if ($intent['sentence_reconstruction'] > 2) {
            return 'moderate_rewrite';
        }
        
        // Check for editorial changes only
        $editorial_changes = $intent['grammar_fix'] + $intent['punctuation_fix'] + $intent['typo_fix'] + $intent['readability_fix'];
        
        if ($editorial_changes > 0 && $intent['semantic_reframing'] === 0 && $intent['tone_shift'] === 0) {
            return 'editorial_safe';
        }
        
        // Default
        return 'moderate_rewrite';
    }
    
    /**
     * Check if changes are editorial safe (allowed in LOW mode)
     * 
     * @param array $intent
     * @return bool
     */
    private function is_editorial_safe($intent) {
        // Forbidden changes in LOW mode
        if ($intent['semantic_reframing'] > 0) {
            return false;
        }
        
        if ($intent['tone_shift'] > 0) {
            return false;
        }
        
        if ($intent['metaphor_change'] > 0) {
            return false;
        }
        
        if ($intent['sentence_reconstruction'] > 1) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Get forbidden changes (for validation error messages)
     * 
     * @param array $intent
     * @return array
     */
    private function get_forbidden_changes($intent) {
        $forbidden = array();
        
        if ($intent['semantic_reframing'] > 0) {
            $forbidden[] = "Semantic reframing detected ({$intent['semantic_reframing']} instances)";
        }
        
        if ($intent['tone_shift'] > 0) {
            $forbidden[] = "Tone shift detected ({$intent['tone_shift']} instances)";
        }
        
        if ($intent['metaphor_change'] > 0) {
            $forbidden[] = "Metaphor change detected ({$intent['metaphor_change']} instances)";
        }
        
        if ($intent['sentence_reconstruction'] > 1) {
            $forbidden[] = "Excessive sentence reconstruction ({$intent['sentence_reconstruction']} sentences)";
        }
        
        return $forbidden;
    }
}
