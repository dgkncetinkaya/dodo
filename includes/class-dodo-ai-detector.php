<?php
/**
 * AI Detection Risk Analyzer Class
 * 
 * Advanced AI-generated content detection
 * Detects robotic patterns and GPT-like phrasing
 *
 * @package DODO_AI_SEO
 * @since 1.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_AI_Detector {
    
    /**
     * Risk levels
     */
    const RISK_LOW = 'low';
    const RISK_MEDIUM = 'medium';
    const RISK_HIGH = 'high';
    
    /**
     * GPT-like phrases (Turkish)
     */
    const GPT_PHRASES = [
        'önemli olan', 'dikkat edilmesi gereken', 'unutulmamalıdır ki',
        'söylemek gerekir ki', 'belirtmek gerekir ki', 'vurgulamak gerekir ki',
        'sonuç olarak', 'özetle', 'kısacası', 'bu bağlamda',
        'bu nedenle', 'dolayısıyla', 'bu sebeple', 'bu yüzden',
        'ayrıca', 'bunun yanı sıra', 'dahası', 'üstelik',
    ];
    
    /**
     * Repetitive opening patterns
     */
    const OPENING_PATTERNS = [
        'eğer', 'ancak', 'fakat', 'lakin', 'ama',
        'ayrıca', 'bunun yanında', 'dahası',
        'sonuç olarak', 'bu nedenle', 'dolayısıyla',
    ];
    
    /**
     * Analyze AI detection risk
     * 
     * @param string $content Post content
     * @return array AI detection analysis
     */
    public function analyze_ai_risk($content) {
        $text = strip_tags($content);
        
        // Calculate AI risk factors
        $repetitive_structures = $this->detect_repetitive_structures($text);
        $generic_transitions = $this->detect_generic_transitions($text);
        $predictable_rhythm = $this->detect_predictable_rhythm($text);
        $gpt_phrasing = $this->detect_gpt_phrasing($text);
        $unnatural_synonyms = $this->detect_unnatural_synonyms($text);
        $robotic_flow = $this->detect_robotic_flow($text);
        $repetitive_openings = $this->detect_repetitive_openings($text);
        
        // Calculate overall AI similarity risk (0-100)
        $ai_similarity_score = $this->calculate_ai_similarity([
            'repetitive_structures' => $repetitive_structures,
            'generic_transitions' => $generic_transitions,
            'predictable_rhythm' => $predictable_rhythm,
            'gpt_phrasing' => $gpt_phrasing,
            'unnatural_synonyms' => $unnatural_synonyms,
            'robotic_flow' => $robotic_flow,
            'repetitive_openings' => $repetitive_openings,
        ]);
        
        // Determine risk level
        $risk_level = $this->get_risk_level($ai_similarity_score);
        $risk_label = $this->get_risk_label($risk_level);
        
        return [
            'ai_similarity_score' => $ai_similarity_score,
            'risk_level' => $risk_level,
            'risk_label' => $risk_label,
            'repetitive_structures' => $repetitive_structures,
            'generic_transitions' => $generic_transitions,
            'predictable_rhythm' => $predictable_rhythm,
            'gpt_phrasing' => $gpt_phrasing,
            'unnatural_synonyms' => $unnatural_synonyms,
            'robotic_flow' => $robotic_flow,
            'repetitive_openings' => $repetitive_openings,
        ];
    }
    
    /**
     * Detect repetitive sentence structures
     */
    private function detect_repetitive_structures($text) {
        $sentences = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        if (count($sentences) < 3) {
            return 0;
        }
        
        $score = 0;
        $patterns = [];
        
        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);
            if (empty($sentence)) continue;
            
            // Extract structure pattern (first 3 words)
            $words = preg_split('/\s+/', mb_strtolower($sentence));
            if (count($words) >= 3) {
                $pattern = $words[0] . ' ' . $words[1] . ' ' . $words[2];
                $patterns[] = $pattern;
            }
        }
        
        // Count pattern repetitions
        $pattern_counts = array_count_values($patterns);
        foreach ($pattern_counts as $count) {
            if ($count > 2) {
                $score += ($count - 2) * 15;
            }
        }
        
        return min(100, $score);
    }
    
    /**
     * Detect generic transitions
     */
    private function detect_generic_transitions($text) {
        $score = 0;
        $text_lower = mb_strtolower($text);
        
        foreach (self::GPT_PHRASES as $phrase) {
            $count = substr_count($text_lower, $phrase);
            $score += $count * 8;
        }
        
        return min(100, $score);
    }
    
    /**
     * Detect predictable rhythm
     */
    private function detect_predictable_rhythm($text) {
        $sentences = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        if (count($sentences) < 5) {
            return 0;
        }
        
        $lengths = [];
        foreach ($sentences as $sentence) {
            $lengths[] = str_word_count($sentence);
        }
        
        // Check for very consistent sentence lengths (robotic)
        $mean = array_sum($lengths) / count($lengths);
        $variance = 0;
        
        foreach ($lengths as $length) {
            $variance += pow($length - $mean, 2);
        }
        $variance /= count($lengths);
        
        // Low variance = predictable rhythm
        if ($variance < 10) {
            return 80;
        } elseif ($variance < 20) {
            return 50;
        } elseif ($variance < 30) {
            return 25;
        }
        
        return 0;
    }
    
    /**
     * Detect GPT-like phrasing
     */
    private function detect_gpt_phrasing($text) {
        $score = 0;
        $text_lower = mb_strtolower($text);
        
        // Common GPT patterns
        $gpt_patterns = [
            'önemli olan şudur ki',
            'dikkat edilmesi gereken nokta',
            'unutulmamalıdır ki',
            'söylemek gerekir ki',
            'belirtmek gerekir ki',
            'vurgulamak gerekir ki',
            'bu bağlamda',
            'bu çerçevede',
            'bu kapsamda',
        ];
        
        foreach ($gpt_patterns as $pattern) {
            if (stripos($text_lower, $pattern) !== false) {
                $score += 12;
            }
        }
        
        // Overly formal constructions
        $formal_patterns = [
            '/\b(gerçekleştirmek|sağlamak|oluşturmak)\b/u',
            '/\b(dolayısıyla|bu nedenle|sonuç olarak)\b/u',
        ];
        
        foreach ($formal_patterns as $pattern) {
            $matches = preg_match_all($pattern, $text_lower);
            $score += min(20, $matches * 4);
        }
        
        return min(100, $score);
    }
    
    /**
     * Detect unnatural synonym usage
     */
    private function detect_unnatural_synonyms($text) {
        $score = 0;
        $text_lower = mb_strtolower($text);
        
        // Synonym groups that AI overuses
        $synonym_groups = [
            ['önemli', 'kritik', 'hayati', 'esaslı', 'temel'],
            ['sağlamak', 'temin etmek', 'garanti etmek'],
            ['gerçekleştirmek', 'icra etmek', 'yerine getirmek'],
            ['kullanmak', 'istifade etmek', 'faydalanmak'],
        ];
        
        foreach ($synonym_groups as $group) {
            $group_count = 0;
            foreach ($group as $word) {
                if (stripos($text_lower, $word) !== false) {
                    $group_count++;
                }
            }
            
            // Using 3+ synonyms from same group = unnatural
            if ($group_count >= 3) {
                $score += 25;
            } elseif ($group_count >= 2) {
                $score += 10;
            }
        }
        
        return min(100, $score);
    }
    
    /**
     * Detect robotic flow
     */
    private function detect_robotic_flow($text) {
        $score = 0;
        $sentences = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        if (count($sentences) < 3) {
            return 0;
        }
        
        // Check for overly consistent paragraph structure
        $paragraphs = preg_split('/<p[^>]*>/', $text);
        $para_sentence_counts = [];
        
        foreach ($paragraphs as $para) {
            $para_sentences = preg_split('/[.!?]+/', strip_tags($para), -1, PREG_SPLIT_NO_EMPTY);
            $para_sentence_counts[] = count($para_sentences);
        }
        
        if (count($para_sentence_counts) > 2) {
            $unique_counts = array_unique($para_sentence_counts);
            
            // All paragraphs have same sentence count = robotic
            if (count($unique_counts) == 1) {
                $score += 40;
            } elseif (count($unique_counts) == 2) {
                $score += 20;
            }
        }
        
        // Check for mechanical transitions
        $mechanical_transitions = 0;
        foreach ($sentences as $i => $sentence) {
            if ($i == 0) continue;
            
            $sentence_lower = mb_strtolower(trim($sentence));
            foreach (self::GPT_PHRASES as $phrase) {
                if (mb_strpos($sentence_lower, $phrase) === 0) {
                    $mechanical_transitions++;
                    break;
                }
            }
        }
        
        $transition_ratio = $mechanical_transitions / count($sentences);
        if ($transition_ratio > 0.4) {
            $score += 40;
        } elseif ($transition_ratio > 0.25) {
            $score += 25;
        }
        
        return min(100, $score);
    }
    
    /**
     * Detect repetitive opening patterns
     */
    private function detect_repetitive_openings($text) {
        $sentences = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        if (count($sentences) < 5) {
            return 0;
        }
        
        $score = 0;
        $openings = [];
        
        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);
            if (empty($sentence)) continue;
            
            $words = preg_split('/\s+/', mb_strtolower($sentence));
            if (!empty($words)) {
                $openings[] = $words[0];
            }
        }
        
        // Count opening word repetitions
        $opening_counts = array_count_values($openings);
        foreach ($opening_counts as $word => $count) {
            if (in_array($word, self::OPENING_PATTERNS) && $count > 2) {
                $score += ($count - 2) * 15;
            }
        }
        
        return min(100, $score);
    }
    
    /**
     * Calculate overall AI similarity score
     */
    private function calculate_ai_similarity($factors) {
        $score = 0;
        
        // Weighted combination
        $score += $factors['repetitive_structures'] * 0.20;
        $score += $factors['generic_transitions'] * 0.15;
        $score += $factors['predictable_rhythm'] * 0.15;
        $score += $factors['gpt_phrasing'] * 0.20;
        $score += $factors['unnatural_synonyms'] * 0.10;
        $score += $factors['robotic_flow'] * 0.15;
        $score += $factors['repetitive_openings'] * 0.05;
        
        return round($score);
    }
    
    /**
     * Get risk level
     */
    private function get_risk_level($score) {
        if ($score >= 70) {
            return self::RISK_HIGH;
        } elseif ($score >= 40) {
            return self::RISK_MEDIUM;
        } else {
            return self::RISK_LOW;
        }
    }
    
    /**
     * Get risk label
     */
    private function get_risk_label($level) {
        $labels = [
            self::RISK_LOW => __('Düşük Risk', 'dodo-ai-seo'),
            self::RISK_MEDIUM => __('Orta Risk', 'dodo-ai-seo'),
            self::RISK_HIGH => __('Yüksek Risk', 'dodo-ai-seo'),
        ];
        
        return $labels[$level] ?? $level;
    }
}
