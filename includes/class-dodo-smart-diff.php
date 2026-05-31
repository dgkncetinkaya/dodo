<?php
/**
 * DODO Smart Diff Engine
 * 
 * Professional-grade diff system that behaves like GitHub/VSCode/Google Docs.
 * 
 * Features:
 * - Sentence-first diffing (not raw token streams)
 * - Semantic sentence matching (85% similarity threshold)
 * - Turkish-aware tokenization
 * - Prevents broken suffix splitting
 * - Preserves punctuation alignment
 * - Ignores cosmetic whitespace
 * - Clean diff mode for minimal changes
 * - Visual corruption fallback
 * 
 * @package DODO_AI_SEO
 * @since 1.6.0
 */

// Doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

class DODO_Smart_Diff {
    
    /**
     * Similarity threshold for sentence matching
     */
    const SIMILARITY_THRESHOLD_HIGH = 85;
    const SIMILARITY_THRESHOLD_MEDIUM = 50;
    
    /**
     * Clean diff mode threshold (token change ratio)
     */
    const CLEAN_MODE_THRESHOLD = 10;
    
    /**
     * Generate smart content diff
     * 
     * @param string $original Original content
     * @param string $improved Improved content
     * @return array Diff data with HTML and statistics
     */
    public function generate($original, $improved) {
        // Strip HTML tags for text comparison
        $original_text = wp_strip_all_tags($original);
        $improved_text = wp_strip_all_tags($improved);
        
        // Normalize whitespace (ignore cosmetic changes)
        $original_text = $this->normalize_whitespace($original_text);
        $improved_text = $this->normalize_whitespace($improved_text);
        
        // STEP 1: Split into sentences (sentence-first approach)
        $original_sentences = $this->split_into_sentences($original_text);
        $improved_sentences = $this->split_into_sentences($improved_text);
        
        // STEP 2: Match sentences semantically
        $sentence_matches = $this->match_sentences_semantically($original_sentences, $improved_sentences);
        
        // STEP 3: Generate sentence-level diff
        $sentence_diff = $this->generate_sentence_diff($sentence_matches);
        
        // STEP 4: Generate inline diff (only for modified sentences)
        $inline_diff = $this->generate_inline_diff($sentence_matches);
        
        // STEP 5: Calculate statistics
        $stats = $this->calculate_statistics($sentence_matches);
        
        // STEP 6: Check if clean diff mode should be used
        $use_clean_mode = $stats['token_change_ratio'] < self::CLEAN_MODE_THRESHOLD;
        
        // STEP 7: Validate diff output (prevent visual corruption)
        $diff_valid = $this->validate_diff_output($inline_diff['html'], $original_text, $improved_text);
        
        if (!$diff_valid) {
            // Fallback to sentence-level diff only
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[DODO SMART DIFF] Visual corruption detected, falling back to sentence-level diff');
            }
            
            return array(
                'diff_html' => $sentence_diff['html'],
                'sentence_diff_html' => $sentence_diff['html'],
                'statistics' => $stats,
                'added_words_count' => $stats['added'],
                'removed_words_count' => $stats['removed'],
                'modified_words_count' => $stats['modified'],
                'unchanged_words_count' => $stats['unchanged'],
                'diff_change_percentage' => $stats['change_percentage'],
                'clean_mode' => false,
                'fallback_mode' => true,
            );
        }
        
        return array(
            'diff_html' => $inline_diff['html'],
            'sentence_diff_html' => $sentence_diff['html'],
            'statistics' => $stats,
            'added_words_count' => $stats['added'],
            'removed_words_count' => $stats['removed'],
            'modified_words_count' => $stats['modified'],
            'unchanged_words_count' => $stats['unchanged'],
            'diff_change_percentage' => $stats['change_percentage'],
            'clean_mode' => $use_clean_mode,
            'fallback_mode' => false,
        );
    }
    
    /**
     * Normalize whitespace (ignore cosmetic changes)
     * 
     * @param string $text
     * @return string
     */
    private function normalize_whitespace($text) {
        // Replace multiple spaces with single space
        $text = preg_replace('/[ \t]+/', ' ', $text);
        
        // Normalize line breaks
        $text = preg_replace('/\r\n|\r/', "\n", $text);
        
        // Remove excessive line breaks (more than 2)
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        
        // Trim each line
        $lines = explode("\n", $text);
        $lines = array_map('trim', $lines);
        $text = implode("\n", $lines);
        
        return trim($text);
    }
    
    /**
     * Split text into sentences (Turkish-aware)
     * 
     * @param string $text
     * @return array Sentences
     */
    private function split_into_sentences($text) {
        // Turkish-aware sentence splitting
        // Handle: . ! ? but not abbreviations like "Dr." "vs." etc.
        
        // First, protect common abbreviations
        $text = str_replace(
            array('Dr.', 'vs.', 'vb.', 'örn.', 'yak.', 'Prof.', 'Doç.'), 
            array('Dr__DOT__', 'vs__DOT__', 'vb__DOT__', 'örn__DOT__', 'yak__DOT__', 'Prof__DOT__', 'Doç__DOT__'), 
            $text
        );
        
        // Split on sentence boundaries
        $sentences = preg_split('/([.!?]+)\s+/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        
        // Reconstruct sentences with their punctuation
        $result = array();
        for ($i = 0; $i < count($sentences); $i += 2) {
            if (isset($sentences[$i]) && trim($sentences[$i]) !== '') {
                $sentence = trim($sentences[$i]);
                
                // Add punctuation if exists
                if (isset($sentences[$i + 1])) {
                    $sentence .= $sentences[$i + 1];
                }
                
                // Restore abbreviations
                $sentence = str_replace(
                    array('Dr__DOT__', 'vs__DOT__', 'vb__DOT__', 'örn__DOT__', 'yak__DOT__', 'Prof__DOT__', 'Doç__DOT__'),
                    array('Dr.', 'vs.', 'vb.', 'örn.', 'yak.', 'Prof.', 'Doç.'),
                    $sentence
                );
                
                $result[] = $sentence;
            }
        }
        
        return $result;
    }
    
    /**
     * Match sentences semantically (85% similarity threshold)
     * 
     * @param array $original_sentences
     * @param array $improved_sentences
     * @return array Match data
     */
    private function match_sentences_semantically($original_sentences, $improved_sentences) {
        $matches = array();
        $used_improved = array();
        
        foreach ($original_sentences as $orig_idx => $orig_sentence) {
            $best_match = null;
            $best_similarity = 0;
            $best_imp_idx = -1;
            
            // Find best matching sentence in improved
            foreach ($improved_sentences as $imp_idx => $imp_sentence) {
                // Skip if already matched
                if (isset($used_improved[$imp_idx])) {
                    continue;
                }
                
                // Calculate similarity
                $similarity = $this->calculate_sentence_similarity($orig_sentence, $imp_sentence);
                
                if ($similarity > $best_similarity) {
                    $best_similarity = $similarity;
                    $best_match = $imp_sentence;
                    $best_imp_idx = $imp_idx;
                }
            }
            
            // Determine match type based on similarity threshold
            if ($best_similarity >= self::SIMILARITY_THRESHOLD_HIGH) {
                // Modified (high similarity)
                $matches[] = array(
                    'type' => 'modified',
                    'original' => $orig_sentence,
                    'improved' => $best_match,
                    'similarity' => $best_similarity,
                    'orig_idx' => $orig_idx,
                    'imp_idx' => $best_imp_idx,
                );
                $used_improved[$best_imp_idx] = true;
            } elseif ($best_similarity >= self::SIMILARITY_THRESHOLD_MEDIUM) {
                // Heavily modified (medium similarity)
                $matches[] = array(
                    'type' => 'heavy_modified',
                    'original' => $orig_sentence,
                    'improved' => $best_match,
                    'similarity' => $best_similarity,
                    'orig_idx' => $orig_idx,
                    'imp_idx' => $best_imp_idx,
                );
                $used_improved[$best_imp_idx] = true;
            } else {
                // Removed (no good match)
                $matches[] = array(
                    'type' => 'removed',
                    'original' => $orig_sentence,
                    'improved' => null,
                    'similarity' => 0,
                    'orig_idx' => $orig_idx,
                    'imp_idx' => -1,
                );
            }
        }
        
        // Find added sentences (not matched)
        foreach ($improved_sentences as $imp_idx => $imp_sentence) {
            if (!isset($used_improved[$imp_idx])) {
                $matches[] = array(
                    'type' => 'added',
                    'original' => null,
                    'improved' => $imp_sentence,
                    'similarity' => 0,
                    'orig_idx' => -1,
                    'imp_idx' => $imp_idx,
                );
            }
        }
        
        // Sort by original index to maintain order
        usort($matches, function($a, $b) {
            if ($a['orig_idx'] === $b['orig_idx']) {
                return $a['imp_idx'] - $b['imp_idx'];
            }
            if ($a['orig_idx'] === -1) return 1;
            if ($b['orig_idx'] === -1) return -1;
            return $a['orig_idx'] - $b['orig_idx'];
        });
        
        return $matches;
    }
    
    /**
     * Calculate sentence similarity (Turkish-aware)
     * 
     * @param string $sentence1
     * @param string $sentence2
     * @return float Similarity percentage (0-100)
     */
    private function calculate_sentence_similarity($sentence1, $sentence2) {
        // Normalize for comparison
        $s1 = mb_strtolower(trim($sentence1), 'UTF-8');
        $s2 = mb_strtolower(trim($sentence2), 'UTF-8');
        
        // Remove punctuation for comparison
        $s1 = preg_replace('/[.,!?;:]+/', '', $s1);
        $s2 = preg_replace('/[.,!?;:]+/', '', $s2);
        
        // Use similar_text for Turkish text
        similar_text($s1, $s2, $percent);
        
        return $percent;
    }
    
    /**
     * Generate sentence-level diff
     * 
     * @param array $matches
     * @return array Sentence diff data
     */
    private function generate_sentence_diff($matches) {
        $html = '<div class="dodo-sentence-diff">';
        
        foreach ($matches as $match) {
            switch ($match['type']) {
                case 'modified':
                    // High similarity - show as modified
                    $html .= '<div class="dodo-sentence-modified">';
                    $html .= '<span class="dodo-sentence-label">~ Modified (' . round($match['similarity']) . '% similar):</span> ';
                    $html .= esc_html($match['improved']);
                    $html .= '</div>';
                    break;
                
                case 'heavy_modified':
                    // Medium similarity - show both
                    $html .= '<div class="dodo-sentence-removed">';
                    $html .= '<span class="dodo-sentence-label">- Old:</span> ';
                    $html .= esc_html($match['original']);
                    $html .= '</div>';
                    $html .= '<div class="dodo-sentence-added">';
                    $html .= '<span class="dodo-sentence-label">+ New:</span> ';
                    $html .= esc_html($match['improved']);
                    $html .= '</div>';
                    break;
                
                case 'removed':
                    $html .= '<div class="dodo-sentence-removed">';
                    $html .= '<span class="dodo-sentence-label">- Removed:</span> ';
                    $html .= esc_html($match['original']);
                    $html .= '</div>';
                    break;
                
                case 'added':
                    $html .= '<div class="dodo-sentence-added">';
                    $html .= '<span class="dodo-sentence-label">+ Added:</span> ';
                    $html .= esc_html($match['improved']);
                    $html .= '</div>';
                    break;
            }
        }
        
        $html .= '</div>';
        
        return array(
            'html' => $html,
        );
    }
    
    /**
     * Generate inline diff (only for modified sentences)
     * 
     * @param array $matches
     * @return array Inline diff data
     */
    private function generate_inline_diff($matches) {
        $html = '<div class="dodo-diff-content">';
        
        foreach ($matches as $match) {
            switch ($match['type']) {
                case 'modified':
                    // Run token-level diff only for modified sentences
                    $token_diff = $this->generate_token_diff($match['original'], $match['improved']);
                    $html .= '<p class="dodo-diff-paragraph">' . $token_diff . '</p>';
                    break;
                
                case 'heavy_modified':
                    // Show as removed + added (no token diff)
                    $html .= '<p class="dodo-diff-paragraph">';
                    $html .= '<del class="dodo-diff-removed">' . esc_html($match['original']) . '</del> ';
                    $html .= '<ins class="dodo-diff-added">' . esc_html($match['improved']) . '</ins>';
                    $html .= '</p>';
                    break;
                
                case 'removed':
                    $html .= '<p class="dodo-diff-paragraph">';
                    $html .= '<del class="dodo-diff-removed">' . esc_html($match['original']) . '</del>';
                    $html .= '</p>';
                    break;
                
                case 'added':
                    $html .= '<p class="dodo-diff-paragraph">';
                    $html .= '<ins class="dodo-diff-added">' . esc_html($match['improved']) . '</ins>';
                    $html .= '</p>';
                    break;
            }
        }
        
        $html .= '</div>';
        
        return array(
            'html' => $html,
        );
    }
    
    /**
     * Generate token-level diff (Turkish-aware)
     * 
     * Prevents broken suffix splitting like "oluştururken" → "oluşturur ve"
     * 
     * @param string $original
     * @param string $improved
     * @return string HTML
     */
    private function generate_token_diff($original, $improved) {
        // Turkish-aware tokenization
        $original_tokens = $this->tokenize_turkish($original);
        $improved_tokens = $this->tokenize_turkish($improved);
        
        // Calculate token diff using LCS
        $diff_ops = $this->calculate_token_diff_operations($original_tokens, $improved_tokens);
        
        // Render inline
        $html = '';
        foreach ($diff_ops as $op) {
            $text = esc_html($op['text']);
            
            switch ($op['type']) {
                case 'added':
                    $html .= '<ins class="dodo-diff-added">' . $text . '</ins>';
                    break;
                case 'removed':
                    $html .= '<del class="dodo-diff-removed">' . $text . '</del>';
                    break;
                case 'unchanged':
                    $html .= '<span class="dodo-diff-unchanged">' . $text . '</span>';
                    break;
            }
        }
        
        return $html;
    }
    
    /**
     * Tokenize Turkish text (suffix-aware)
     * 
     * Handles:
     * - Turkish suffixes (correctly)
     * - Apostrophes
     * - Compound words
     * - Quoted strings
     * - Punctuation alignment
     * 
     * @param string $text
     * @return array Tokens
     */
    private function tokenize_turkish($text) {
        $tokens = array();
        
        // Split by whitespace but preserve punctuation with words
        $words = preg_split('/(\s+)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        
        foreach ($words as $word) {
            // Preserve whitespace as-is
            if (trim($word) === '') {
                $tokens[] = $word;
                continue;
            }
            
            // Handle punctuation: keep it attached to the word
            // This prevents "word." from being split into "word" and "."
            $tokens[] = $word;
        }
        
        return $tokens;
    }
    
    /**
     * Calculate token diff operations (improved LCS algorithm)
     * 
     * @param array $original_tokens
     * @param array $improved_tokens
     * @return array Operations
     */
    private function calculate_token_diff_operations($original_tokens, $improved_tokens) {
        $operations = array();
        
        // Use dynamic programming LCS for better accuracy
        $lcs = $this->longest_common_subsequence($original_tokens, $improved_tokens);
        
        $orig_idx = 0;
        $imp_idx = 0;
        $lcs_idx = 0;
        
        while ($orig_idx < count($original_tokens) || $imp_idx < count($improved_tokens)) {
            // Check if we're at an LCS element
            if ($lcs_idx < count($lcs)) {
                $lcs_token = $lcs[$lcs_idx];
                
                // Find this token in original
                while ($orig_idx < count($original_tokens) && 
                       $this->normalize_token($original_tokens[$orig_idx]) !== $this->normalize_token($lcs_token)) {
                    $operations[] = array('type' => 'removed', 'text' => $original_tokens[$orig_idx]);
                    $orig_idx++;
                }
                
                // Find this token in improved
                while ($imp_idx < count($improved_tokens) && 
                       $this->normalize_token($improved_tokens[$imp_idx]) !== $this->normalize_token($lcs_token)) {
                    $operations[] = array('type' => 'added', 'text' => $improved_tokens[$imp_idx]);
                    $imp_idx++;
                }
                
                // Add unchanged token
                if ($orig_idx < count($original_tokens)) {
                    $operations[] = array('type' => 'unchanged', 'text' => $original_tokens[$orig_idx]);
                    $orig_idx++;
                    $imp_idx++;
                    $lcs_idx++;
                }
            } else {
                // No more LCS elements - rest are changes
                if ($orig_idx < count($original_tokens)) {
                    $operations[] = array('type' => 'removed', 'text' => $original_tokens[$orig_idx]);
                    $orig_idx++;
                } elseif ($imp_idx < count($improved_tokens)) {
                    $operations[] = array('type' => 'added', 'text' => $improved_tokens[$imp_idx]);
                    $imp_idx++;
                }
            }
        }
        
        return $operations;
    }
    
    /**
     * Longest Common Subsequence (LCS) algorithm
     * 
     * @param array $seq1
     * @param array $seq2
     * @return array LCS
     */
    private function longest_common_subsequence($seq1, $seq2) {
        $m = count($seq1);
        $n = count($seq2);
        
        // Build LCS table
        $lcs_table = array_fill(0, $m + 1, array_fill(0, $n + 1, 0));
        
        for ($i = 1; $i <= $m; $i++) {
            for ($j = 1; $j <= $n; $j++) {
                if ($this->normalize_token($seq1[$i - 1]) === $this->normalize_token($seq2[$j - 1])) {
                    $lcs_table[$i][$j] = $lcs_table[$i - 1][$j - 1] + 1;
                } else {
                    $lcs_table[$i][$j] = max($lcs_table[$i - 1][$j], $lcs_table[$i][$j - 1]);
                }
            }
        }
        
        // Backtrack to find LCS
        $lcs = array();
        $i = $m;
        $j = $n;
        
        while ($i > 0 && $j > 0) {
            if ($this->normalize_token($seq1[$i - 1]) === $this->normalize_token($seq2[$j - 1])) {
                array_unshift($lcs, $seq1[$i - 1]);
                $i--;
                $j--;
            } elseif ($lcs_table[$i - 1][$j] > $lcs_table[$i][$j - 1]) {
                $i--;
            } else {
                $j--;
            }
        }
        
        return $lcs;
    }
    
    /**
     * Normalize token for comparison
     * 
     * @param string $token
     * @return string
     */
    private function normalize_token($token) {
        // Lowercase and trim
        $normalized = mb_strtolower(trim($token), 'UTF-8');
        
        // Remove trailing punctuation for comparison (but keep apostrophes)
        $normalized = rtrim($normalized, '.,!?;:');
        
        return $normalized;
    }
    
    /**
     * Calculate statistics
     * 
     * @param array $matches
     * @return array Statistics
     */
    private function calculate_statistics($matches) {
        $added_sentences = 0;
        $removed_sentences = 0;
        $modified_sentences = 0;
        $unchanged_sentences = 0;
        
        $added_words = 0;
        $removed_words = 0;
        $modified_words = 0;
        $unchanged_words = 0;
        
        foreach ($matches as $match) {
            switch ($match['type']) {
                case 'modified':
                    $modified_sentences++;
                    // Count word changes
                    $orig_words = str_word_count($match['original'], 0, 'ÇçĞğİıÖöŞşÜü');
                    $imp_words = str_word_count($match['improved'], 0, 'ÇçĞğİıÖöŞşÜü');
                    $modified_words += abs($imp_words - $orig_words);
                    $unchanged_words += min($orig_words, $imp_words);
                    break;
                
                case 'heavy_modified':
                    $modified_sentences++;
                    $removed_words += str_word_count($match['original'], 0, 'ÇçĞğİıÖöŞşÜü');
                    $added_words += str_word_count($match['improved'], 0, 'ÇçĞğİıÖöŞşÜü');
                    break;
                
                case 'removed':
                    $removed_sentences++;
                    $removed_words += str_word_count($match['original'], 0, 'ÇçĞğİıÖöŞşÜü');
                    break;
                
                case 'added':
                    $added_sentences++;
                    $added_words += str_word_count($match['improved'], 0, 'ÇçĞğİıÖöŞşÜü');
                    break;
            }
        }
        
        $total_words = $added_words + $removed_words + $modified_words + $unchanged_words;
        $changed_words = $added_words + $removed_words + $modified_words;
        
        $change_percentage = $total_words > 0 ? round(($changed_words / $total_words) * 100) : 0;
        $token_change_ratio = $total_words > 0 ? round(($changed_words / $total_words) * 100, 1) : 0;
        
        return array(
            'added' => $added_words,
            'removed' => $removed_words,
            'modified' => $modified_words,
            'unchanged' => $unchanged_words,
            'total' => $total_words,
            'change_percentage' => $change_percentage,
            'token_change_ratio' => $token_change_ratio,
            'added_sentences' => $added_sentences,
            'removed_sentences' => $removed_sentences,
            'modified_sentences' => $modified_sentences,
            'unchanged_sentences' => $unchanged_sentences,
        );
    }
    
    /**
     * Validate diff output (prevent visual corruption)
     * 
     * @param string $diff_html
     * @param string $original_text
     * @param string $improved_text
     * @return bool Valid or not
     */
    private function validate_diff_output($diff_html, $original_text, $improved_text) {
        // Check 1: HTML should not be empty
        if (empty($diff_html) || strlen($diff_html) < 10) {
            return false;
        }
        
        // Check 2: Should contain diff markup
        if (strpos($diff_html, 'dodo-diff-') === false) {
            return false;
        }
        
        // Check 3: Length should be reasonable (not 10x longer than original)
        $max_length = max(strlen($original_text), strlen($improved_text)) * 10;
        if (strlen($diff_html) > $max_length) {
            return false;
        }
        
        // Check 4: Should not have excessive consecutive tags (sign of corruption)
        $consecutive_tags = preg_match_all('/<\/[^>]+><[^>]+>/i', $diff_html);
        if ($consecutive_tags > 100) {
            return false;
        }
        
        return true;
    }
}
