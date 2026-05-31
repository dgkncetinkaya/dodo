<?php
/**
 * Score Calculator Class
 * 
 * Calculates content health scores across 5 dimensions:
 * - SEO Score (25% weight)
 * - Quality Score (30% weight)
 * - Readability Score (20% weight)
 * - Semantic Score (15% weight)
 * - AI Risk Score (10% weight)
 *
 * @package DODO_AI_SEO
 * @since 1.2.0
 */

// Doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

class DODO_Score_Calculator {
    
    /**
     * Score weights for health score calculation
     */
    const WEIGHTS = [
        'seo'         => 0.25,  // 25%
        'quality'     => 0.30,  // 30%
        'readability' => 0.20,  // 20%
        'semantic'    => 0.15,  // 15%
        'ai_risk'     => 0.10   // 10%
    ];
    
    /**
     * AI signature phrases to detect
     */
    const AI_PHRASES = [
        'it\'s important to note that',
        'in today\'s digital landscape',
        'delve into',
        'tapestry of',
        'realm of',
        'it\'s worth noting',
        'in conclusion',
        'moreover',
        'furthermore',
        'additionally',
        'however',
        'nevertheless',
        'consequently'
    ];
    
    /**
     * Constructor
     */
    public function __construct() {
        // No initialization needed
    }
    
    /**
     * Calculate overall health score
     * 
     * @param array $intelligence Intelligence data with dimension scores
     * @return int Health score (0-100)
     */
    public function calculate_health_score($intelligence) {
        $health_score = (
            ($intelligence['seo_score'] * self::WEIGHTS['seo']) +
            ($intelligence['quality_score'] * self::WEIGHTS['quality']) +
            ($intelligence['readability_score'] * self::WEIGHTS['readability']) +
            ($intelligence['semantic_score'] * self::WEIGHTS['semantic']) +
            ((100 - $intelligence['ai_risk_score']) * self::WEIGHTS['ai_risk'])
        );
        
        return round($health_score);
    }
    
    /**
     * Calculate SEO score (0-100)
     * 
     * Factors:
     * - Focus keyword presence (15 points)
     * - SEO title exists (10 points)
     * - Meta description exists (10 points)
     * - SEO-friendly slug (10 points)
     * - Keyword in title (15 points)
     * - Keyword in meta description (15 points)
     * - Keyword in first paragraph (15 points)
     * - Meta description length (10 points)
     * 
     * @param string $content Post content
     * @param string $keyword Focus keyword
     * @param array $metadata Post metadata (title, description, slug)
     * @return int SEO score (0-100)
     */
    public function calculate_seo_score($content, $keyword, $metadata) {
        $score = 0;
        
        // Focus keyword exists (15 points)
        if (!empty($keyword)) {
            $score += 15;
        }
        
        // SEO title exists (10 points)
        if (!empty($metadata['seo_title'])) {
            $score += 10;
        }
        
        // Meta description exists (10 points)
        if (!empty($metadata['meta_description'])) {
            $score += 10;
        }
        
        // Slug is SEO-friendly (10 points)
        if ($this->is_seo_friendly_slug($metadata['slug'])) {
            $score += 10;
        }
        
        // Keyword in title (15 points)
        if (!empty($keyword) && !empty($metadata['seo_title'])) {
            if ($this->keyword_in_text($keyword, $metadata['seo_title'])) {
                $score += 15;
            }
        }
        
        // Keyword in meta description (15 points)
        if (!empty($keyword) && !empty($metadata['meta_description'])) {
            if ($this->keyword_in_text($keyword, $metadata['meta_description'])) {
                $score += 15;
            }
        }
        
        // Keyword in first paragraph (15 points)
        if (!empty($keyword) && !empty($content)) {
            if ($this->keyword_in_first_paragraph($keyword, $content)) {
                $score += 15;
            }
        }
        
        // Meta description length (10 points)
        if (!empty($metadata['meta_description'])) {
            $desc_length = mb_strlen($metadata['meta_description']);
            if ($desc_length >= 120 && $desc_length <= 160) {
                $score += 10;
            } elseif ($desc_length >= 100 && $desc_length < 120) {
                $score += 7;
            } elseif ($desc_length >= 80 && $desc_length < 100) {
                $score += 5;
            }
        }
        
        return min($score, 100);
    }
    
    /**
     * Calculate content quality score (0-100)
     * 
     * Factors:
     * - Word count (25 points - graduated)
     * - H2 headings (20 points - graduated)
     * - H3 headings (15 points - graduated)
     * - Paragraph balance (15 points)
     * - FAQ section (10 points)
     * - CTA (10 points)
     * - Penalties for spam/imbalance (-10 max)
     * 
     * @param string $content Post content
     * @return int Quality score (0-100)
     */
    public function calculate_quality_score($content) {
        $score = 0;
        $penalties = 0;
        
        // Word count (25 points - graduated)
        $word_count = str_word_count(strip_tags($content));
        if ($word_count >= 2000) {
            $score += 25;
        } elseif ($word_count >= 1500) {
            $score += 20;
        } elseif ($word_count >= 1000) {
            $score += 15;
        } elseif ($word_count >= 500) {
            $score += 10;
        } else {
            $score += 5;
        }
        
        // H2 headings (20 points - graduated)
        $h2_count = substr_count($content, '<h2');
        if ($h2_count >= 5) {
            $score += 20;
        } elseif ($h2_count >= 3) {
            $score += 15;
        } elseif ($h2_count >= 1) {
            $score += 10;
        }
        
        // H3 headings (15 points - graduated)
        $h3_count = substr_count($content, '<h3');
        if ($h3_count >= 4) {
            $score += 15;
        } elseif ($h3_count >= 2) {
            $score += 10;
        } elseif ($h3_count >= 1) {
            $score += 5;
        }
        
        // Paragraph balance (15 points)
        $paragraphs = $this->extract_paragraphs($content);
        if (!empty($paragraphs)) {
            $avg_words = $this->average_paragraph_length($paragraphs);
            if ($avg_words >= 50 && $avg_words <= 150) {
                $score += 15;
            } elseif ($avg_words >= 40 && $avg_words < 50) {
                $score += 10;
            } elseif ($avg_words >= 30 && $avg_words < 40) {
                $score += 7;
            }
        }
        
        // FAQ section (10 points)
        if ($this->has_faq_section($content)) {
            $score += 10;
        }
        
        // CTA (10 points)
        if ($this->has_cta($content)) {
            $score += 10;
        }
        
        // Penalties
        if (!empty($paragraphs)) {
            // Short paragraph spam (too many < 30 words)
            $short_paragraphs = $this->count_short_paragraphs($paragraphs, 30);
            if ($short_paragraphs > count($paragraphs) * 0.5) {
                $penalties += 5;
            }
            
            // Overly long paragraphs (> 200 words)
            $long_paragraphs = $this->count_long_paragraphs($paragraphs, 200);
            if ($long_paragraphs > 0) {
                $penalties += 5;
            }
        }
        
        return max(0, min(100, $score - $penalties));
    }
    
    /**
     * Calculate readability score (0-100)
     * 
     * Factors:
     * - Sentence length (deduct if too long/short)
     * - Sentence variation (deduct if monotonous)
     * - Paragraph consistency (deduct if too uniform)
     * - Transition words (deduct if too few/many)
     * - Passive voice (deduct if excessive)
     * - Complex words (deduct if too many)
     * - Flesch Reading Ease (deduct if too difficult/simple)
     * 
     * @param string $content Post content
     * @return int Readability score (0-100)
     */
    public function calculate_readability_score($content) {
        $score = 100; // Start at 100, deduct for issues
        
        $text = strip_tags($content);
        
        // Sentence length analysis
        $sentences = $this->extract_sentences($text);
        if (!empty($sentences)) {
            $avg_sentence_length = $this->average_sentence_length($sentences);
            
            // Ideal: 15-20 words per sentence
            if ($avg_sentence_length > 25) {
                $score -= 15; // Too long
            } elseif ($avg_sentence_length < 10) {
                $score -= 10; // Too short
            }
            
            // Sentence length variation
            $variation = $this->sentence_length_variation($sentences);
            if ($variation < 0.3) {
                $score -= 10; // Too monotonous
            }
        }
        
        // Paragraph length consistency
        $paragraphs = $this->extract_paragraphs($content);
        if (!empty($paragraphs)) {
            $para_variation = $this->paragraph_length_variation($paragraphs);
            if ($para_variation < 0.2) {
                $score -= 10; // Too uniform
            }
        }
        
        // Transition words
        $transition_ratio = $this->calculate_transition_ratio($text);
        if ($transition_ratio < 0.2) {
            $score -= 10; // Too few transitions
        } elseif ($transition_ratio > 0.5) {
            $score -= 15; // Too many transitions (AI-like)
        }
        
        // Passive voice ratio
        $passive_ratio = $this->calculate_passive_voice_ratio($text);
        if ($passive_ratio > 0.3) {
            $score -= 10; // Too much passive voice
        }
        
        // Complex words
        $complex_ratio = $this->calculate_complex_word_ratio($text);
        if ($complex_ratio > 0.2) {
            $score -= 10; // Too many complex words
        }
        
        // Flesch Reading Ease
        $flesch_score = $this->calculate_flesch_reading_ease($text);
        if ($flesch_score < 50) {
            $score -= 10; // Too difficult
        } elseif ($flesch_score > 80) {
            $score -= 5; // Too simple
        }
        
        return max(0, min(100, $score));
    }
    
    /**
     * Calculate semantic quality score (0-100)
     * 
     * This is a placeholder that will be enhanced with OpenAI integration
     * For now, uses rule-based analysis
     * 
     * @param string $content Post content
     * @param string $keyword Focus keyword
     * @return int Semantic score (0-100)
     */
    public function calculate_semantic_score($content, $keyword) {
        $score = 50; // Base score
        
        $text = strip_tags($content);
        $word_count = str_word_count($text);
        
        // Keyword density (ideal: 1-2%)
        if (!empty($keyword)) {
            $keyword_count = substr_count(strtolower($text), strtolower($keyword));
            $density = ($word_count > 0) ? ($keyword_count / $word_count) * 100 : 0;
            
            if ($density >= 1 && $density <= 2) {
                $score += 20; // Ideal density
            } elseif ($density >= 0.5 && $density < 1) {
                $score += 15; // Good density
            } elseif ($density >= 2 && $density <= 3) {
                $score += 10; // Acceptable
            } elseif ($density > 3) {
                $score -= 10; // Keyword stuffing
            }
        }
        
        // Content depth (based on word count and structure)
        if ($word_count >= 2000) {
            $score += 15;
        } elseif ($word_count >= 1500) {
            $score += 10;
        } elseif ($word_count >= 1000) {
            $score += 5;
        }
        
        // Heading structure (indicates organization)
        $h2_count = substr_count($content, '<h2');
        $h3_count = substr_count($content, '<h3');
        if ($h2_count >= 3 && $h3_count >= 2) {
            $score += 15; // Well-structured
        } elseif ($h2_count >= 2) {
            $score += 10;
        }
        
        return max(0, min(100, $score));
    }
    
    /**
     * Calculate AI risk score (0-100)
     * Higher score = higher risk of AI detection
     * 
     * Factors:
     * - Repetitive patterns (0-25 points)
     * - AI signature phrases (0-30 points)
     * - Unnatural transitions (0-20 points)
     * - Perfect structure (0-15 points)
     * - Personality absence (0-10 points)
     * 
     * @param string $content Post content
     * @return int AI risk score (0-100)
     */
    public function calculate_ai_risk_score($content) {
        $risk_score = 0;
        
        $text = strip_tags($content);
        $text_lower = strtolower($text);
        
        // Repetitive sentence structures (0-25 points)
        $repetition_score = $this->detect_sentence_repetition($text);
        $risk_score += min(25, $repetition_score);
        
        // AI signature phrases (0-30 points)
        $phrase_count = 0;
        foreach (self::AI_PHRASES as $phrase) {
            $phrase_count += substr_count($text_lower, $phrase);
        }
        $risk_score += min(30, $phrase_count * 5);
        
        // Unnatural transitions (0-20 points)
        $transition_score = $this->detect_unnatural_transitions($text);
        $risk_score += min(20, $transition_score);
        
        // Overly perfect structure (0-15 points)
        $structure_score = $this->detect_perfect_structure($content);
        $risk_score += min(15, $structure_score);
        
        // Lack of personality (0-10 points)
        $personality_score = $this->detect_personality_absence($text);
        $risk_score += min(10, $personality_score);
        
        return min(100, $risk_score);
    }
    
    // ==========================================
    // HELPER METHODS
    // ==========================================
    
    /**
     * Check if slug is SEO-friendly
     * 
     * @param string $slug Post slug
     * @return bool True if SEO-friendly
     */
    private function is_seo_friendly_slug($slug) {
        if (empty($slug)) {
            return false;
        }
        
        // Check length (ideal: 3-5 words, 30-60 characters)
        $length = strlen($slug);
        if ($length < 10 || $length > 80) {
            return false;
        }
        
        // Check for numbers only or very short
        if (is_numeric($slug) || $length < 5) {
            return false;
        }
        
        // Check for proper format (lowercase, hyphens)
        if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if keyword exists in text (case-insensitive)
     * 
     * @param string $keyword Keyword to search
     * @param string $text Text to search in
     * @return bool True if keyword found
     */
    private function keyword_in_text($keyword, $text) {
        if (empty($keyword) || empty($text)) {
            return false;
        }
        
        return stripos($text, $keyword) !== false;
    }
    
    /**
     * Check if keyword is in first paragraph
     * 
     * @param string $keyword Keyword to search
     * @param string $content Post content
     * @return bool True if keyword in first paragraph
     */
    private function keyword_in_first_paragraph($keyword, $content) {
        if (empty($keyword) || empty($content)) {
            return false;
        }
        
        // Extract first paragraph
        $paragraphs = $this->extract_paragraphs($content);
        if (empty($paragraphs)) {
            return false;
        }
        
        $first_paragraph = strip_tags($paragraphs[0]);
        return stripos($first_paragraph, $keyword) !== false;
    }
    
    /**
     * Extract paragraphs from content
     * 
     * @param string $content HTML content
     * @return array Array of paragraph texts
     */
    private function extract_paragraphs($content) {
        // Match <p> tags
        preg_match_all('/<p[^>]*>(.*?)<\/p>/is', $content, $matches);
        
        if (empty($matches[1])) {
            return [];
        }
        
        // Filter out empty paragraphs
        $paragraphs = array_filter($matches[1], function($p) {
            $text = strip_tags($p);
            return !empty(trim($text));
        });
        
        return array_values($paragraphs);
    }
    
    /**
     * Calculate average paragraph length in words
     * 
     * @param array $paragraphs Array of paragraph texts
     * @return float Average word count
     */
    private function average_paragraph_length($paragraphs) {
        if (empty($paragraphs)) {
            return 0;
        }
        
        $total_words = 0;
        foreach ($paragraphs as $paragraph) {
            $text = strip_tags($paragraph);
            $total_words += str_word_count($text);
        }
        
        return $total_words / count($paragraphs);
    }
    
    /**
     * Count short paragraphs
     * 
     * @param array $paragraphs Array of paragraph texts
     * @param int $threshold Word count threshold
     * @return int Count of short paragraphs
     */
    private function count_short_paragraphs($paragraphs, $threshold) {
        $count = 0;
        foreach ($paragraphs as $paragraph) {
            $text = strip_tags($paragraph);
            if (str_word_count($text) < $threshold) {
                $count++;
            }
        }
        return $count;
    }
    
    /**
     * Count long paragraphs
     * 
     * @param array $paragraphs Array of paragraph texts
     * @param int $threshold Word count threshold
     * @return int Count of long paragraphs
     */
    private function count_long_paragraphs($paragraphs, $threshold) {
        $count = 0;
        foreach ($paragraphs as $paragraph) {
            $text = strip_tags($paragraph);
            if (str_word_count($text) > $threshold) {
                $count++;
            }
        }
        return $count;
    }
    
    /**
     * Check if content has FAQ section
     * 
     * @param string $content Post content
     * @return bool True if FAQ section found
     */
    private function has_faq_section($content) {
        $content_lower = strtolower($content);
        
        // Check for FAQ indicators
        $faq_indicators = [
            'faq',
            'sık sorulan sorular',
            'frequently asked questions',
            'soru cevap',
            'sorular',
            'questions'
        ];
        
        foreach ($faq_indicators as $indicator) {
            if (strpos($content_lower, $indicator) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if content has CTA (Call to Action)
     * 
     * @param string $content Post content
     * @return bool True if CTA found
     */
    private function has_cta($content) {
        $content_lower = strtolower($content);
        
        // Check for CTA indicators
        $cta_indicators = [
            'hemen',
            'şimdi',
            'tıkla',
            'başla',
            'kayıt ol',
            'ücretsiz',
            'dene',
            'iletişim',
            'satın al',
            'click here',
            'get started',
            'sign up',
            'contact us',
            'buy now',
            'learn more'
        ];
        
        $cta_count = 0;
        foreach ($cta_indicators as $indicator) {
            if (strpos($content_lower, $indicator) !== false) {
                $cta_count++;
            }
        }
        
        // Need at least 2 CTA indicators
        return $cta_count >= 2;
    }
    
    /**
     * Extract sentences from text
     * 
     * @param string $text Plain text
     * @return array Array of sentences
     */
    private function extract_sentences($text) {
        // Split by sentence endings
        $sentences = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        // Filter out very short sentences (< 3 words)
        $sentences = array_filter($sentences, function($s) {
            return str_word_count(trim($s)) >= 3;
        });
        
        return array_values($sentences);
    }
    
    /**
     * Calculate average sentence length in words
     * 
     * @param array $sentences Array of sentences
     * @return float Average word count
     */
    private function average_sentence_length($sentences) {
        if (empty($sentences)) {
            return 0;
        }
        
        $total_words = 0;
        foreach ($sentences as $sentence) {
            $total_words += str_word_count($sentence);
        }
        
        return $total_words / count($sentences);
    }
    
    /**
     * Calculate sentence length variation (coefficient of variation)
     * 
     * @param array $sentences Array of sentences
     * @return float Variation coefficient (0-1)
     */
    private function sentence_length_variation($sentences) {
        if (count($sentences) < 2) {
            return 0;
        }
        
        $lengths = array_map(function($s) {
            return str_word_count($s);
        }, $sentences);
        
        $mean = array_sum($lengths) / count($lengths);
        
        if ($mean == 0) {
            return 0;
        }
        
        $variance = 0;
        foreach ($lengths as $length) {
            $variance += pow($length - $mean, 2);
        }
        $variance /= count($lengths);
        
        $std_dev = sqrt($variance);
        
        return $std_dev / $mean; // Coefficient of variation
    }
    
    /**
     * Calculate paragraph length variation
     * 
     * @param array $paragraphs Array of paragraphs
     * @return float Variation coefficient (0-1)
     */
    private function paragraph_length_variation($paragraphs) {
        if (count($paragraphs) < 2) {
            return 0;
        }
        
        $lengths = array_map(function($p) {
            return str_word_count(strip_tags($p));
        }, $paragraphs);
        
        $mean = array_sum($lengths) / count($lengths);
        
        if ($mean == 0) {
            return 0;
        }
        
        $variance = 0;
        foreach ($lengths as $length) {
            $variance += pow($length - $mean, 2);
        }
        $variance /= count($lengths);
        
        $std_dev = sqrt($variance);
        
        return $std_dev / $mean;
    }
    
    /**
     * Calculate transition word ratio
     * 
     * @param string $text Plain text
     * @return float Ratio of sentences with transitions (0-1)
     */
    private function calculate_transition_ratio($text) {
        $sentences = $this->extract_sentences($text);
        if (empty($sentences)) {
            return 0;
        }
        
        $transition_words = [
            'ancak', 'fakat', 'ama', 'lakin', 'oysa',
            'çünkü', 'zira', 'nitekim',
            'dolayısıyla', 'bu nedenle', 'bu yüzden', 'sonuç olarak',
            'örneğin', 'mesela', 'şöyle ki',
            'ayrıca', 'bunun yanında', 'dahası', 'üstelik',
            'however', 'but', 'although', 'though',
            'because', 'since', 'as',
            'therefore', 'thus', 'consequently',
            'for example', 'for instance',
            'moreover', 'furthermore', 'additionally'
        ];
        
        $transition_count = 0;
        foreach ($sentences as $sentence) {
            $sentence_lower = strtolower($sentence);
            foreach ($transition_words as $word) {
                if (strpos($sentence_lower, $word) !== false) {
                    $transition_count++;
                    break; // Count each sentence only once
                }
            }
        }
        
        return $transition_count / count($sentences);
    }
    
    /**
     * Calculate passive voice ratio (simplified detection)
     * 
     * @param string $text Plain text
     * @return float Ratio of passive voice (0-1)
     */
    private function calculate_passive_voice_ratio($text) {
        $sentences = $this->extract_sentences($text);
        if (empty($sentences)) {
            return 0;
        }
        
        // Passive voice indicators (Turkish and English)
        $passive_indicators = [
            'edildi', 'edilmiş', 'ediliyor', 'edilecek',
            'olundu', 'olunmuş', 'oluyor', 'olunacak',
            'yapıldı', 'yapılmış', 'yapılıyor', 'yapılacak',
            'was', 'were', 'been', 'being',
            'is being', 'are being', 'was being', 'were being'
        ];
        
        $passive_count = 0;
        foreach ($sentences as $sentence) {
            $sentence_lower = strtolower($sentence);
            foreach ($passive_indicators as $indicator) {
                if (strpos($sentence_lower, $indicator) !== false) {
                    $passive_count++;
                    break;
                }
            }
        }
        
        return $passive_count / count($sentences);
    }
    
    /**
     * Calculate complex word ratio
     * Complex words: 3+ syllables (simplified: 7+ characters)
     * 
     * @param string $text Plain text
     * @return float Ratio of complex words (0-1)
     */
    private function calculate_complex_word_ratio($text) {
        $words = str_word_count($text, 1);
        if (empty($words)) {
            return 0;
        }
        
        $complex_count = 0;
        foreach ($words as $word) {
            // Simplified: words with 7+ characters are considered complex
            if (mb_strlen($word) >= 7) {
                $complex_count++;
            }
        }
        
        return $complex_count / count($words);
    }
    
    /**
     * Calculate Flesch Reading Ease score
     * 
     * @param string $text Plain text
     * @return float Flesch score (0-100, higher = easier)
     */
    private function calculate_flesch_reading_ease($text) {
        $sentences = $this->extract_sentences($text);
        $words = str_word_count($text, 1);
        
        if (empty($sentences) || empty($words)) {
            return 50; // Default middle score
        }
        
        $total_sentences = count($sentences);
        $total_words = count($words);
        
        // Estimate syllables (simplified: characters / 3)
        $total_syllables = 0;
        foreach ($words as $word) {
            $total_syllables += max(1, round(mb_strlen($word) / 3));
        }
        
        // Flesch Reading Ease formula
        $score = 206.835 - 1.015 * ($total_words / $total_sentences) - 84.6 * ($total_syllables / $total_words);
        
        return max(0, min(100, $score));
    }
    
    /**
     * Detect sentence repetition patterns
     * 
     * @param string $text Plain text
     * @return int Repetition score (0-25)
     */
    private function detect_sentence_repetition($text) {
        $sentences = $this->extract_sentences($text);
        if (count($sentences) < 3) {
            return 0;
        }
        
        $score = 0;
        
        // Check for repeated sentence starters
        $starters = [];
        foreach ($sentences as $sentence) {
            $words = explode(' ', trim($sentence));
            if (count($words) >= 2) {
                $starter = strtolower($words[0] . ' ' . $words[1]);
                if (!isset($starters[$starter])) {
                    $starters[$starter] = 0;
                }
                $starters[$starter]++;
            }
        }
        
        // Count repetitions
        foreach ($starters as $count) {
            if ($count >= 3) {
                $score += 5; // Repeated starter
            }
        }
        
        return min(25, $score);
    }
    
    /**
     * Detect unnatural transitions
     * 
     * @param string $text Plain text
     * @return int Transition score (0-20)
     */
    private function detect_unnatural_transitions($text) {
        $sentences = $this->extract_sentences($text);
        if (empty($sentences)) {
            return 0;
        }
        
        // Generic AI transition phrases
        $ai_transitions = [
            'moreover',
            'furthermore',
            'additionally',
            'consequently',
            'nevertheless',
            'nonetheless'
        ];
        
        $transition_count = 0;
        foreach ($sentences as $sentence) {
            $sentence_lower = strtolower($sentence);
            foreach ($ai_transitions as $transition) {
                if (strpos($sentence_lower, $transition) !== false) {
                    $transition_count++;
                    break;
                }
            }
        }
        
        // Score based on frequency
        $ratio = $transition_count / count($sentences);
        if ($ratio > 0.3) {
            return 20; // Very high usage
        } elseif ($ratio > 0.2) {
            return 15;
        } elseif ($ratio > 0.1) {
            return 10;
        }
        
        return 0;
    }
    
    /**
     * Detect overly perfect structure
     * 
     * @param string $content HTML content
     * @return int Structure score (0-15)
     */
    private function detect_perfect_structure($content) {
        $score = 0;
        
        // Check paragraph uniformity
        $paragraphs = $this->extract_paragraphs($content);
        if (!empty($paragraphs)) {
            $variation = $this->paragraph_length_variation($paragraphs);
            if ($variation < 0.15) {
                $score += 10; // Too uniform
            }
        }
        
        // Check heading hierarchy perfection
        $h2_count = substr_count($content, '<h2');
        $h3_count = substr_count($content, '<h3');
        
        // Perfect ratio (each H2 has exactly 2-3 H3s)
        if ($h2_count > 0 && $h3_count > 0) {
            $ratio = $h3_count / $h2_count;
            if ($ratio >= 2 && $ratio <= 3) {
                $score += 5; // Too perfect
            }
        }
        
        return min(15, $score);
    }
    
    /**
     * Detect personality absence
     * 
     * @param string $text Plain text
     * @return int Personality score (0-10)
     */
    private function detect_personality_absence($text) {
        $score = 10; // Start assuming no personality
        
        $text_lower = strtolower($text);
        
        // Check for personal pronouns
        $personal_pronouns = ['ben', 'biz', 'benim', 'bizim', 'i ', 'we ', 'my ', 'our '];
        foreach ($personal_pronouns as $pronoun) {
            if (strpos($text_lower, $pronoun) !== false) {
                $score -= 3;
                break;
            }
        }
        
        // Check for questions (engagement)
        if (substr_count($text, '?') >= 2) {
            $score -= 2;
        }
        
        // Check for exclamations (emotion)
        if (substr_count($text, '!') >= 1) {
            $score -= 2;
        }
        
        // Check for quotes (examples, references)
        if (substr_count($text, '"') >= 2 || substr_count($text, '"') >= 2) {
            $score -= 2;
        }
        
        // Check for informal language
        $informal_words = ['gerçekten', 'harika', 'muhteşem', 'kesinlikle', 'tabii', 'elbette'];
        foreach ($informal_words as $word) {
            if (strpos($text_lower, $word) !== false) {
                $score -= 1;
                break;
            }
        }
        
        return max(0, $score);
    }
    
    /**
     * Get score class based on score value
     * 
     * @param int $score Score value (0-100)
     * @return string Score class (excellent|good|fair|poor)
     */
    public function get_score_class($score) {
        if ($score >= 90) {
            return 'excellent';
        } elseif ($score >= 75) {
            return 'good';
        } elseif ($score >= 60) {
            return 'fair';
        } else {
            return 'poor';
        }
    }
    
    /**
     * Get score label based on score value
     * 
     * @param int $score Score value (0-100)
     * @return string Score label
     */
    public function get_score_label($score) {
        $class = $this->get_score_class($score);
        
        $labels = [
            'excellent' => __('Mükemmel', 'dodo-ai-seo'),
            'good'      => __('İyi', 'dodo-ai-seo'),
            'fair'      => __('Orta', 'dodo-ai-seo'),
            'poor'      => __('Zayıf', 'dodo-ai-seo')
        ];
        
        return $labels[$class];
    }
}
