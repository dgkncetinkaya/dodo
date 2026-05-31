<?php
/**
 * Content Depth Analyzer Class
 * 
 * Analyzes content depth and quality
 * Detects surface-level vs deep content
 *
 * @package DODO_AI_SEO
 * @since 1.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Depth_Analyzer {
    
    /**
     * Depth levels
     */
    const DEPTH_DEEP = 'deep';
    const DEPTH_MEDIUM = 'medium';
    const DEPTH_SURFACE = 'surface';
    
    /**
     * Analyze content depth
     * 
     * @param string $content Post content
     * @param string $focus_keyword Focus keyword
     * @return array Depth analysis data
     */
    public function analyze_depth($content, $focus_keyword = '') {
        $text = strip_tags($content);
        
        // Calculate depth metrics
        $topical_depth = $this->calculate_topical_depth($content, $focus_keyword);
        $supporting_detail = $this->calculate_supporting_detail($content);
        $expertise_indicators = $this->detect_expertise_indicators($text);
        $practical_value = $this->calculate_practical_value($content);
        $information_density = $this->calculate_information_density($text);
        
        // Overall depth score (0-100)
        $depth_score = $this->calculate_depth_score([
            'topical_depth' => $topical_depth,
            'supporting_detail' => $supporting_detail,
            'expertise_indicators' => $expertise_indicators,
            'practical_value' => $practical_value,
            'information_density' => $information_density,
        ]);
        
        // Determine depth level
        $depth_level = $this->get_depth_level($depth_score);
        $depth_label = $this->get_depth_label($depth_level);
        
        return [
            'depth_score' => $depth_score,
            'depth_level' => $depth_level,
            'depth_label' => $depth_label,
            'topical_depth' => $topical_depth,
            'supporting_detail' => $supporting_detail,
            'expertise_indicators' => $expertise_indicators,
            'practical_value' => $practical_value,
            'information_density' => $information_density,
        ];
    }
    
    /**
     * Calculate topical depth
     * 
     * @param string $content Post content
     * @param string $focus_keyword Focus keyword
     * @return int Score 0-100
     */
    private function calculate_topical_depth($content, $focus_keyword) {
        $score = 0;
        
        // Check for subtopics (H2/H3 headings)
        $h2_count = substr_count($content, '<h2');
        $h3_count = substr_count($content, '<h3');
        
        $score += min(30, $h2_count * 5);
        $score += min(20, $h3_count * 3);
        
        // Check for topic variations
        if (!empty($focus_keyword)) {
            $keyword_words = explode(' ', $focus_keyword);
            $variation_count = 0;
            
            foreach ($keyword_words as $word) {
                if (strlen($word) > 3) {
                    // Look for related terms
                    $related_patterns = [
                        $word . 'lar',
                        $word . 'ler',
                        $word . 'lık',
                        $word . 'lik',
                    ];
                    
                    foreach ($related_patterns as $pattern) {
                        if (stripos($content, $pattern) !== false) {
                            $variation_count++;
                        }
                    }
                }
            }
            
            $score += min(25, $variation_count * 3);
        }
        
        // Check for comprehensive coverage (long content)
        $word_count = str_word_count(strip_tags($content));
        if ($word_count > 2000) {
            $score += 25;
        } elseif ($word_count > 1500) {
            $score += 20;
        } elseif ($word_count > 1000) {
            $score += 15;
        } elseif ($word_count > 500) {
            $score += 10;
        }
        
        return min(100, $score);
    }
    
    /**
     * Calculate supporting detail
     * 
     * @param string $content Post content
     * @return int Score 0-100
     */
    private function calculate_supporting_detail($content) {
        $score = 0;
        
        // Lists (bullet points, numbered lists)
        $list_count = substr_count($content, '<li');
        $score += min(25, $list_count * 2);
        
        // Examples
        $example_indicators = ['örnek', 'mesela', 'şöyle ki', 'gibi'];
        foreach ($example_indicators as $indicator) {
            $count = substr_count(mb_strtolower($content), $indicator);
            $score += min(15, $count * 3);
        }
        
        // Data/statistics (numbers in content)
        preg_match_all('/\b\d+[%.,]?\d*\b/', strip_tags($content), $matches);
        $number_count = count($matches[0]);
        $score += min(20, $number_count * 2);
        
        // Quotes/citations
        $quote_count = substr_count($content, '<blockquote');
        $score += min(15, $quote_count * 5);
        
        // Images/media
        $image_count = substr_count($content, '<img');
        $score += min(15, $image_count * 3);
        
        // Tables (structured data)
        $table_count = substr_count($content, '<table');
        $score += min(10, $table_count * 5);
        
        return min(100, $score);
    }
    
    /**
     * Detect expertise indicators
     * 
     * @param string $text Content text
     * @return int Score 0-100
     */
    private function detect_expertise_indicators($text) {
        $score = 0;
        $text_lower = mb_strtolower($text);
        
        // Technical terminology
        $technical_terms = [
            'araştırma', 'analiz', 'metodoloji', 'yaklaşım', 'strateji',
            'uygulama', 'implementasyon', 'optimizasyon', 'verimlilik',
        ];
        
        foreach ($technical_terms as $term) {
            $score += min(15, substr_count($text_lower, $term) * 3);
        }
        
        // Specific numbers/data (shows research)
        preg_match_all('/\b\d+[%.,]\d+\b/', $text, $matches);
        $precise_numbers = count($matches[0]);
        $score += min(20, $precise_numbers * 2);
        
        // Citations/references
        $citation_patterns = ['göre', 'araştırma', 'çalışma', 'rapor', 'kaynak'];
        foreach ($citation_patterns as $pattern) {
            $score += min(10, substr_count($text_lower, $pattern) * 2);
        }
        
        // Detailed explanations (long paragraphs)
        $paragraphs = preg_split('/<p[^>]*>/', $text);
        $detailed_para_count = 0;
        foreach ($paragraphs as $para) {
            if (str_word_count(strip_tags($para)) > 100) {
                $detailed_para_count++;
            }
        }
        $score += min(20, $detailed_para_count * 4);
        
        // Process descriptions (step-by-step)
        $process_indicators = ['adım', 'önce', 'sonra', 'ardından', 'ilk', 'son'];
        foreach ($process_indicators as $indicator) {
            $score += min(15, substr_count($text_lower, $indicator) * 2);
        }
        
        return min(100, $score);
    }
    
    /**
     * Calculate practical value
     * 
     * @param string $content Post content
     * @return int Score 0-100
     */
    private function calculate_practical_value($content) {
        $score = 0;
        $text_lower = mb_strtolower(strip_tags($content));
        
        // Actionable advice
        $actionable_words = [
            'yapın', 'kullanın', 'deneyin', 'uygulayın', 'başlayın',
            'oluşturun', 'geliştirin', 'optimize edin', 'kontrol edin',
        ];
        
        foreach ($actionable_words as $word) {
            $score += min(20, substr_count($text_lower, $word) * 3);
        }
        
        // How-to patterns
        $howto_patterns = ['nasıl', 'ne şekilde', 'hangi yöntem', 'yöntem'];
        foreach ($howto_patterns as $pattern) {
            $score += min(15, substr_count($text_lower, $pattern) * 4);
        }
        
        // Tips/tricks
        $tip_indicators = ['ipucu', 'öneri', 'tavsiye', 'dikkat', 'önemli'];
        foreach ($tip_indicators as $indicator) {
            $score += min(15, substr_count($text_lower, $indicator) * 3);
        }
        
        // Problem-solution patterns
        $problem_words = ['sorun', 'problem', 'zorluk', 'engel'];
        $solution_words = ['çözüm', 'yöntem', 'yaklaşım', 'strateji'];
        
        $problem_count = 0;
        $solution_count = 0;
        
        foreach ($problem_words as $word) {
            $problem_count += substr_count($text_lower, $word);
        }
        foreach ($solution_words as $word) {
            $solution_count += substr_count($text_lower, $word);
        }
        
        if ($problem_count > 0 && $solution_count > 0) {
            $score += min(20, ($problem_count + $solution_count) * 2);
        }
        
        // Tools/resources mentioned
        $resource_indicators = ['araç', 'kaynak', 'platform', 'uygulama', 'yazılım'];
        foreach ($resource_indicators as $indicator) {
            $score += min(15, substr_count($text_lower, $indicator) * 2);
        }
        
        return min(100, $score);
    }
    
    /**
     * Calculate information density
     * 
     * @param string $text Content text
     * @return int Score 0-100
     */
    private function calculate_information_density($text) {
        $word_count = str_word_count($text);
        
        if ($word_count == 0) {
            return 0;
        }
        
        $score = 0;
        
        // Unique word ratio (vocabulary richness)
        $words = preg_split('/\s+/', mb_strtolower($text));
        $unique_words = array_unique($words);
        $unique_ratio = count($unique_words) / count($words);
        
        $score += $unique_ratio * 30;
        
        // Average word length (longer words = more specific/technical)
        $total_length = 0;
        foreach ($words as $word) {
            $total_length += mb_strlen($word);
        }
        $avg_word_length = $total_length / count($words);
        
        if ($avg_word_length > 6) {
            $score += 25;
        } elseif ($avg_word_length > 5) {
            $score += 15;
        } elseif ($avg_word_length > 4) {
            $score += 10;
        }
        
        // Sentence complexity (varied sentence lengths)
        $sentences = preg_split('/[.!?]+/', $text);
        $sentence_lengths = [];
        foreach ($sentences as $sentence) {
            $sentence_lengths[] = str_word_count($sentence);
        }
        
        if (count($sentence_lengths) > 1) {
            $variance = $this->calculate_variance($sentence_lengths);
            if ($variance > 50 && $variance < 200) {
                $score += 20; // Good variety
            } elseif ($variance > 20) {
                $score += 10;
            }
        }
        
        // Information markers (specific, detailed content)
        $info_markers = ['özellikle', 'spesifik', 'detaylı', 'kapsamlı', 'ayrıntılı'];
        foreach ($info_markers as $marker) {
            $score += min(15, substr_count(mb_strtolower($text), $marker) * 3);
        }
        
        return min(100, $score);
    }
    
    /**
     * Calculate overall depth score
     * 
     * @param array $metrics Depth metrics
     * @return int Score 0-100
     */
    private function calculate_depth_score($metrics) {
        $score = 0;
        
        // Weighted combination
        $score += $metrics['topical_depth'] * 0.25;
        $score += $metrics['supporting_detail'] * 0.25;
        $score += $metrics['expertise_indicators'] * 0.20;
        $score += $metrics['practical_value'] * 0.15;
        $score += $metrics['information_density'] * 0.15;
        
        return round($score);
    }
    
    /**
     * Get depth level
     * 
     * @param int $score Depth score
     * @return string Depth level
     */
    private function get_depth_level($score) {
        if ($score >= 75) {
            return self::DEPTH_DEEP;
        } elseif ($score >= 50) {
            return self::DEPTH_MEDIUM;
        } else {
            return self::DEPTH_SURFACE;
        }
    }
    
    /**
     * Get depth label
     * 
     * @param string $level Depth level
     * @return string Depth label
     */
    private function get_depth_label($level) {
        $labels = [
            self::DEPTH_DEEP => __('Derin İçerik', 'dodo-ai-seo'),
            self::DEPTH_MEDIUM => __('Orta Derinlik', 'dodo-ai-seo'),
            self::DEPTH_SURFACE => __('Yüzeysel İçerik', 'dodo-ai-seo'),
        ];
        
        return $labels[$level] ?? $level;
    }
    
    /**
     * Calculate variance
     */
    private function calculate_variance($values) {
        $mean = array_sum($values) / count($values);
        $variance = 0;
        
        foreach ($values as $value) {
            $variance += pow($value - $mean, 2);
        }
        
        return $variance / count($values);
    }
}
