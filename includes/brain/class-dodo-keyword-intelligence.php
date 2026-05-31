<?php
/**
 * Keyword Intelligence Engine
 * 
 * Analyzes keywords for opportunity, competition, commercial value,
 * GEO suitability, and provides explainable scoring.
 * 
 * @package DODO_AI_SEO
 * @subpackage Brain_Core
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Prevent duplicate class declaration
if (class_exists('DODO_Keyword_Intelligence')) {
    return;
}

class DODO_Keyword_Intelligence {
    
    /**
     * Transactional indicators
     */
    private $transactional_terms = array(
        'satın al', 'fiyat', 'ucuz', 'indirim', 'kampanya', 'teklif',
        'sipariş', 'ödeme', 'kargo', 'ücretsiz', 'hizmet', 'paket',
        'buy', 'price', 'cheap', 'discount', 'deal', 'order', 'cost'
    );
    
    /**
     * Local intent indicators
     */
    private $local_terms = array(
        'istanbul', 'ankara', 'izmir', 'bursa', 'antalya', 'yakınımda',
        'near me', 'bölge', 'mahalle', 'ilçe', 'şehir', 'konum'
    );
    
    /**
     * Question patterns
     */
    private $question_patterns = array(
        'nasıl', 'neden', 'ne zaman', 'nerede', 'kim', 'hangi',
        'how', 'why', 'when', 'where', 'who', 'which', 'what'
    );
    
    /**
     * Comparison patterns
     */
    private $comparison_patterns = array(
        'vs', 'karşı', 'fark', 'hangisi', 'en iyi', 'alternatif',
        'versus', 'compare', 'difference', 'best', 'alternative'
    );
    
    /**
     * Analyze keyword and return intelligence report
     * 
     * @param string $keyword
     * @param array $context Optional context (existing content, site data)
     * @return array Intelligence report with scores and reasoning
     */
    public function analyze($keyword, $context = array()) {
        $start_time = microtime(true);
        
        // Validate input
        if (empty(trim($keyword))) {
            return [
                'error' => true,
                'message' => 'Anahtar kelime boş olamaz.',
                'opportunity_score' => 0,
                'execution_time_ms' => 0
            ];
        }
        
        try {
            // Sanitize and truncate keyword (max 200 chars)
            $keyword = trim($keyword);
            if (mb_strlen($keyword, 'UTF-8') > 200) {
                $keyword = mb_substr($keyword, 0, 200, 'UTF-8');
                error_log("BRAIN: Keyword truncated to 200 characters");
            }
            
            error_log("BRAIN: Keyword Intelligence analyzing: {$keyword}");
            
            // Normalize keyword
            $keyword_lower = mb_strtolower($keyword, 'UTF-8');
            $words = preg_split('/\s+/u', $keyword_lower);
            $word_count = count($words);
            
            // Run all analyses
            $opportunity_data = $this->calculate_opportunity_score($keyword_lower, $words, $word_count);
            $competition_data = $this->estimate_competition($keyword_lower, $words, $word_count);
            $commercial_data = $this->assess_commercial_value($keyword_lower, $words);
            $geo_data = $this->assess_geo_suitability($keyword_lower, $words);
            $intent_data = $this->estimate_search_intent($keyword_lower, $words);
            $semantic_data = $this->assess_semantic_richness($keyword_lower, $words);
            $trend_data = $this->estimate_trend_potential($keyword_lower, $words);
            
            // Calculate topical authority contribution
            $authority_contribution = $this->calculate_authority_contribution(
                $keyword_lower,
                $context
            );
            
            $execution_time = round((microtime(true) - $start_time) * 1000, 2);
            
            $report = array(
                'keyword' => $keyword,
                'opportunity_score' => $opportunity_data['score'],
                'competition' => $competition_data['level'],
                'competition_score' => $competition_data['score'],
                'commercial_value' => $commercial_data['value'],
                'commercial_score' => $commercial_data['score'],
                'geo_score' => $geo_data['score'],
                'geo_suitability' => $geo_data['suitability'],
                'intent' => $intent_data['primary_intent'],
                'intent_confidence' => $intent_data['confidence'],
                'semantic_richness' => $semantic_data['richness'],
                'semantic_score' => $semantic_data['score'],
                'trend_potential' => $trend_data['potential'],
                'trend_score' => $trend_data['score'],
                'authority_contribution' => $authority_contribution,
                'reasoning' => array(
                    'opportunity' => $opportunity_data['reasoning'],
                    'competition' => $competition_data['reasoning'],
                    'commercial' => $commercial_data['reasoning'],
                    'geo' => $geo_data['reasoning'],
                    'intent' => $intent_data['reasoning'],
                    'semantic' => $semantic_data['reasoning'],
                    'trend' => $trend_data['reasoning'],
                ),
                'metadata' => array(
                    'word_count' => $word_count,
                    'execution_time_ms' => $execution_time,
                    'analyzed_at' => current_time('mysql'),
                ),
            );
            
            error_log("BRAIN: Keyword Intelligence complete - Opportunity: {$report['opportunity_score']}, Competition: {$report['competition']}");
            
            return $report;
        } catch (Throwable $e) {
            error_log('DODO Keyword Intelligence: Analysis error: ' . $e->getMessage());
            return [
                'error' => true,
                'message' => 'Anahtar kelime analizi sırasında hata oluştu.',
                'opportunity_score' => 0,
                'execution_time_ms' => round((microtime(true) - $start_time) * 1000, 2)
            ];
        }
    }
    
    /**
     * Calculate opportunity score (0-100)
     */
    private function calculate_opportunity_score($keyword, $words, $word_count) {
        $score = 50; // Base score
        $reasoning = array();
        
        // Long-tail bonus (3-5 words = sweet spot)
        if ($word_count >= 3 && $word_count <= 5) {
            $score += 20;
            $reasoning[] = "Long-tail keyword (3-5 words) - easier to rank";
        } elseif ($word_count > 5) {
            $score += 10;
            $reasoning[] = "Very specific keyword (5+ words) - low competition";
        } elseif ($word_count == 2) {
            $score += 5;
            $reasoning[] = "Medium-tail keyword (2 words)";
        } else {
            $score -= 10;
            $reasoning[] = "Short keyword (1 word) - high competition";
        }
        
        // Modifier detection bonus
        $modifiers = array('en iyi', 'nasıl', 'neden', 'ücretsiz', 'kolay', 'hızlı', 'best', 'free', 'easy', 'fast');
        foreach ($modifiers as $modifier) {
            if (strpos($keyword, $modifier) !== false) {
                $score += 10;
                $reasoning[] = "Contains modifier '{$modifier}' - intent clarity";
                break;
            }
        }
        
        // Question pattern bonus
        foreach ($this->question_patterns as $pattern) {
            if (strpos($keyword, $pattern) !== false) {
                $score += 15;
                $reasoning[] = "Question-based keyword - high engagement potential";
                break;
            }
        }
        
        // Cap score
        $score = min(100, max(0, $score));
        
        return array(
            'score' => $score,
            'reasoning' => $reasoning,
        );
    }
    
    /**
     * Estimate competition level
     */
    private function estimate_competition($keyword, $words, $word_count) {
        $score = 50; // Base competition score (higher = more competitive)
        $reasoning = array();
        
        // Word count impact
        if ($word_count == 1) {
            $score += 40;
            $reasoning[] = "Single word - very high competition";
        } elseif ($word_count == 2) {
            $score += 20;
            $reasoning[] = "Two words - high competition";
        } elseif ($word_count >= 3 && $word_count <= 4) {
            $score += 0;
            $reasoning[] = "3-4 words - moderate competition";
        } else {
            $score -= 20;
            $reasoning[] = "5+ words - low competition (long-tail)";
        }
        
        // Generic terms increase competition
        $generic_terms = array('web', 'site', 'online', 'dijital', 'internet', 'seo', 'digital');
        foreach ($generic_terms as $term) {
            if (in_array($term, $words)) {
                $score += 10;
                $reasoning[] = "Contains generic term '{$term}' - increases competition";
                break;
            }
        }
        
        // Specific modifiers decrease competition
        $specific_modifiers = array('2024', '2025', 'yeni', 'güncel', 'new', 'latest');
        foreach ($specific_modifiers as $modifier) {
            if (strpos($keyword, $modifier) !== false) {
                $score -= 15;
                $reasoning[] = "Time-specific modifier - reduces competition";
                break;
            }
        }
        
        // Cap score
        $score = min(100, max(0, $score));
        
        // Determine level
        if ($score >= 70) {
            $level = 'very_high';
        } elseif ($score >= 50) {
            $level = 'high';
        } elseif ($score >= 30) {
            $level = 'medium';
        } else {
            $level = 'low';
        }
        
        return array(
            'score' => $score,
            'level' => $level,
            'reasoning' => $reasoning,
        );
    }
    
    /**
     * Assess commercial value
     */
    private function assess_commercial_value($keyword, $words) {
        $score = 0;
        $reasoning = array();
        $found_terms = array();
        
        // Check for transactional terms
        foreach ($this->transactional_terms as $term) {
            if (strpos($keyword, $term) !== false) {
                $score += 25;
                $found_terms[] = $term;
            }
        }
        
        if (count($found_terms) > 0) {
            $reasoning[] = "Transactional terms found: " . implode(', ', $found_terms);
        }
        
        // Service/product indicators
        $service_terms = array('hizmet', 'service', 'danışmanlık', 'consulting', 'ajans', 'agency');
        foreach ($service_terms as $term) {
            if (strpos($keyword, $term) !== false) {
                $score += 20;
                $reasoning[] = "Service-related keyword - commercial intent";
                break;
            }
        }
        
        // Brand/company indicators
        if (preg_match('/\b(ltd|şti|a\.ş|inc|llc|corp)\b/i', $keyword)) {
            $score += 15;
            $reasoning[] = "Company/brand keyword - commercial";
        }
        
        // Cap score
        $score = min(100, max(0, $score));
        
        // Determine value
        if ($score >= 70) {
            $value = 'very_high';
        } elseif ($score >= 50) {
            $value = 'high';
        } elseif ($score >= 30) {
            $value = 'medium';
        } elseif ($score > 0) {
            $value = 'low';
        } else {
            $value = 'none';
        }
        
        if (empty($reasoning)) {
            $reasoning[] = "No commercial indicators detected";
        }
        
        return array(
            'score' => $score,
            'value' => $value,
            'reasoning' => $reasoning,
        );
    }
    
    /**
     * Assess GEO suitability
     */
    private function assess_geo_suitability($keyword, $words) {
        $score = 0;
        $reasoning = array();
        $found_terms = array();
        
        // Check for local terms
        foreach ($this->local_terms as $term) {
            if (strpos($keyword, $term) !== false) {
                $score += 30;
                $found_terms[] = $term;
            }
        }
        
        if (count($found_terms) > 0) {
            $reasoning[] = "Local intent detected: " . implode(', ', $found_terms);
        }
        
        // Question patterns boost GEO
        foreach ($this->question_patterns as $pattern) {
            if (strpos($keyword, $pattern) !== false) {
                $score += 20;
                $reasoning[] = "Question format - suitable for AI Overview";
                break;
            }
        }
        
        // Comparison patterns boost GEO
        foreach ($this->comparison_patterns as $pattern) {
            if (strpos($keyword, $pattern) !== false) {
                $score += 15;
                $reasoning[] = "Comparison keyword - good for featured snippets";
                break;
            }
        }
        
        // Informational keywords are GEO-friendly
        $info_terms = array('nedir', 'ne demek', 'what is', 'definition', 'meaning', 'guide', 'rehber');
        foreach ($info_terms as $term) {
            if (strpos($keyword, $term) !== false) {
                $score += 25;
                $reasoning[] = "Informational keyword - high GEO potential";
                break;
            }
        }
        
        // Cap score
        $score = min(100, max(0, $score));
        
        // Determine suitability
        if ($score >= 70) {
            $suitability = 'excellent';
        } elseif ($score >= 50) {
            $suitability = 'good';
        } elseif ($score >= 30) {
            $suitability = 'moderate';
        } elseif ($score > 0) {
            $suitability = 'low';
        } else {
            $suitability = 'minimal';
        }
        
        if (empty($reasoning)) {
            $reasoning[] = "No strong GEO indicators - standard content approach";
        }
        
        return array(
            'score' => $score,
            'suitability' => $suitability,
            'reasoning' => $reasoning,
        );
    }
    
    /**
     * Estimate search intent
     */
    private function estimate_search_intent($keyword, $words) {
        $scores = array(
            'informational' => 0,
            'transactional' => 0,
            'commercial' => 0,
            'navigational' => 0,
            'local' => 0,
        );
        
        $reasoning = array();
        
        // Informational signals
        foreach ($this->question_patterns as $pattern) {
            if (strpos($keyword, $pattern) !== false) {
                $scores['informational'] += 30;
                $reasoning[] = "Question pattern detected - informational intent";
                break;
            }
        }
        
        $info_terms = array('nedir', 'ne demek', 'rehber', 'guide', 'tutorial', 'öğren', 'learn');
        foreach ($info_terms as $term) {
            if (strpos($keyword, $term) !== false) {
                $scores['informational'] += 25;
                $reasoning[] = "Educational term '{$term}' - informational";
                break;
            }
        }
        
        // Transactional signals
        foreach ($this->transactional_terms as $term) {
            if (strpos($keyword, $term) !== false) {
                $scores['transactional'] += 30;
                $reasoning[] = "Transactional term '{$term}' detected";
                break;
            }
        }
        
        // Commercial signals
        foreach ($this->comparison_patterns as $pattern) {
            if (strpos($keyword, $pattern) !== false) {
                $scores['commercial'] += 25;
                $reasoning[] = "Comparison pattern - commercial investigation";
                break;
            }
        }
        
        $commercial_terms = array('review', 'inceleme', 'değerlendirme', 'öneri', 'recommendation');
        foreach ($commercial_terms as $term) {
            if (strpos($keyword, $term) !== false) {
                $scores['commercial'] += 20;
                $reasoning[] = "Research term '{$term}' - commercial intent";
                break;
            }
        }
        
        // Local signals
        foreach ($this->local_terms as $term) {
            if (strpos($keyword, $term) !== false) {
                $scores['local'] += 35;
                $reasoning[] = "Local term '{$term}' - local intent";
                break;
            }
        }
        
        // Navigational signals
        if (preg_match('/\b(giriş|login|kayıt|register|hesap|account)\b/i', $keyword)) {
            $scores['navigational'] += 30;
            $reasoning[] = "Navigational term detected";
        }
        
        // Determine primary intent
        arsort($scores);
        $primary_intent = key($scores);
        $confidence = reset($scores);
        
        // Normalize confidence to 0-100
        $confidence = min(100, $confidence);
        
        if ($confidence < 20) {
            $reasoning[] = "Low confidence - mixed or unclear intent";
        }
        
        return array(
            'primary_intent' => $primary_intent,
            'confidence' => $confidence,
            'all_scores' => $scores,
            'reasoning' => $reasoning,
        );
    }
    
    /**
     * Assess semantic richness
     */
    private function assess_semantic_richness($keyword, $words) {
        $score = 0;
        $reasoning = array();
        
        // Word diversity
        $unique_words = count(array_unique($words));
        $diversity_ratio = $unique_words / count($words);
        
        if ($diversity_ratio >= 0.9) {
            $score += 30;
            $reasoning[] = "High word diversity - semantically rich";
        } elseif ($diversity_ratio >= 0.7) {
            $score += 20;
            $reasoning[] = "Good word diversity";
        }
        
        // Multi-concept detection
        if (count($words) >= 4) {
            $score += 20;
            $reasoning[] = "Multi-concept keyword - semantic depth";
        }
        
        // Modifier presence
        $modifiers = array('en', 'çok', 'best', 'top', 'most', 'yeni', 'new');
        foreach ($modifiers as $modifier) {
            if (in_array($modifier, $words)) {
                $score += 10;
                $reasoning[] = "Contains modifier - adds semantic context";
                break;
            }
        }
        
        // Technical terms
        $technical = array('seo', 'api', 'cms', 'crm', 'saas', 'b2b', 'b2c');
        foreach ($technical as $term) {
            if (in_array($term, $words)) {
                $score += 15;
                $reasoning[] = "Technical term present - niche semantic field";
                break;
            }
        }
        
        // Cap score
        $score = min(100, max(0, $score));
        
        // Determine richness
        if ($score >= 70) {
            $richness = 'very_rich';
        } elseif ($score >= 50) {
            $richness = 'rich';
        } elseif ($score >= 30) {
            $richness = 'moderate';
        } else {
            $richness = 'simple';
        }
        
        if (empty($reasoning)) {
            $reasoning[] = "Simple keyword structure - basic semantic field";
        }
        
        return array(
            'score' => $score,
            'richness' => $richness,
            'reasoning' => $reasoning,
        );
    }
    
    /**
     * Estimate trend potential
     */
    private function estimate_trend_potential($keyword, $words) {
        $score = 50; // Base score
        $reasoning = array();
        
        // Time-sensitive terms
        $time_terms = array('2024', '2025', 'yeni', 'new', 'latest', 'güncel', 'trend');
        foreach ($time_terms as $term) {
            if (strpos($keyword, $term) !== false) {
                $score += 20;
                $reasoning[] = "Time-sensitive term '{$term}' - trend potential";
                break;
            }
        }
        
        // Emerging tech terms
        $tech_terms = array('ai', 'yapay zeka', 'blockchain', 'metaverse', 'nft', 'web3');
        foreach ($tech_terms as $term) {
            if (strpos($keyword, $term) !== false) {
                $score += 25;
                $reasoning[] = "Emerging tech term - high trend potential";
                break;
            }
        }
        
        // Evergreen penalty (stable but less trendy)
        $evergreen = array('nedir', 'what is', 'definition', 'tanım');
        foreach ($evergreen as $term) {
            if (strpos($keyword, $term) !== false) {
                $score -= 10;
                $reasoning[] = "Evergreen keyword - stable but less trendy";
                break;
            }
        }
        
        // Cap score
        $score = min(100, max(0, $score));
        
        // Determine potential
        if ($score >= 70) {
            $potential = 'high';
        } elseif ($score >= 50) {
            $potential = 'moderate';
        } else {
            $potential = 'low';
        }
        
        if (empty($reasoning)) {
            $reasoning[] = "Standard keyword - moderate trend stability";
        }
        
        return array(
            'score' => $score,
            'potential' => $potential,
            'reasoning' => $reasoning,
        );
    }
    
    /**
     * Calculate topical authority contribution
     * 
     * Analyzes how this keyword fits into existing content clusters
     * and topical authority structure
     */
    private function calculate_authority_contribution($keyword, $context) {
        $score = 50; // Base score
        $reasoning = array();
        
        // If no context provided, return neutral score
        if (empty($context)) {
            return array(
                'score' => $score,
                'reasoning' => array('No context provided - neutral score'),
            );
        }
        
        $keyword_lower = mb_strtolower($keyword, 'UTF-8');
        $keyword_words = preg_split('/\s+/u', $keyword_lower);
        
        // Check if we have existing content data
        if (isset($context['existing_posts']) && !empty($context['existing_posts'])) {
            $related_count = 0;
            $total_posts = count($context['existing_posts']);
            
            foreach ($context['existing_posts'] as $post) {
                $post_title = mb_strtolower($post['title'], 'UTF-8');
                $post_content = mb_strtolower($post['content'], 'UTF-8');
                
                // Check keyword overlap
                $overlap = 0;
                foreach ($keyword_words as $word) {
                    if (mb_strlen($word) > 3) { // Skip short words
                        if (strpos($post_title, $word) !== false || strpos($post_content, $word) !== false) {
                            $overlap++;
                        }
                    }
                }
                
                if ($overlap >= 2) {
                    $related_count++;
                }
            }
            
            // Calculate cluster strength
            if ($related_count > 0) {
                $cluster_ratio = $related_count / $total_posts;
                
                if ($cluster_ratio >= 0.3) {
                    $score += 30;
                    $reasoning[] = "Strong cluster ({$related_count}/{$total_posts} related posts) - high authority potential";
                } elseif ($cluster_ratio >= 0.15) {
                    $score += 20;
                    $reasoning[] = "Moderate cluster ({$related_count}/{$total_posts} related posts) - good authority fit";
                } elseif ($cluster_ratio >= 0.05) {
                    $score += 10;
                    $reasoning[] = "Weak cluster ({$related_count}/{$total_posts} related posts) - expanding topic";
                } else {
                    $score -= 10;
                    $reasoning[] = "No cluster ({$related_count}/{$total_posts} related posts) - new topic area";
                }
            } else {
                $score -= 15;
                $reasoning[] = "Orphan keyword - no related content found";
            }
        }
        
        // Check if keyword matches site's main topics
        if (isset($context['main_topics']) && !empty($context['main_topics'])) {
            foreach ($context['main_topics'] as $topic) {
                $topic_lower = mb_strtolower($topic, 'UTF-8');
                
                foreach ($keyword_words as $word) {
                    if (mb_strlen($word) > 3 && strpos($topic_lower, $word) !== false) {
                        $score += 15;
                        $reasoning[] = "Matches main topic '{$topic}' - authority alignment";
                        break 2;
                    }
                }
            }
        }
        
        // Cap score
        $score = min(100, max(0, $score));
        
        if (empty($reasoning)) {
            $reasoning[] = "Neutral authority contribution - standard keyword";
        }
        
        return array(
            'score' => $score,
            'reasoning' => $reasoning,
        );
    }
}
