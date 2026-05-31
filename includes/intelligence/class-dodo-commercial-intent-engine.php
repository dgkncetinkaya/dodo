<?php
/**
 * Commercial Intent Engine
 * 
 * Detects commercial value and buyer intent in queries
 * Phase 3 - SEO Strategist Intelligence
 * 
 * @package DODO_AI_SEO
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Commercial_Intent_Engine {
    
    /**
     * Commercial intent keywords (Turkish + English)
     */
    private $commercial_keywords = array(
        // Transactional
        'satın al' => 90,
        'buy' => 90,
        'fiyat' => 85,
        'price' => 85,
        'ucuz' => 80,
        'cheap' => 80,
        'indirim' => 80,
        'discount' => 80,
        'kampanya' => 75,
        'deal' => 75,
        'sipariş' => 85,
        'order' => 85,
        
        // Commercial investigation
        'en iyi' => 70,
        'best' => 70,
        'karşılaştırma' => 65,
        'comparison' => 65,
        'vs' => 60,
        'versus' => 60,
        'review' => 65,
        'inceleme' => 65,
        'top' => 60,
        'öneri' => 60,
        'recommendation' => 60,
        
        // Product-specific
        'ürün' => 50,
        'product' => 50,
        'marka' => 45,
        'brand' => 45,
        'model' => 50,
        'özellik' => 40,
        'feature' => 40,
        'spec' => 45,
        'teknik' => 40,
    );
    
    /**
     * Informational keywords (lower commercial value)
     */
    private $informational_keywords = array(
        'nedir' => -20,
        'what is' => -20,
        'nasıl' => -15,
        'how to' => -15,
        'neden' => -10,
        'why' => -10,
        'ne demek' => -20,
        'meaning' => -20,
    );
    
    /**
     * Detect commercial intent
     * 
     * @param string $query Query string
     * @param array $metrics GSC metrics
     * @return array Intent analysis
     */
    public function detect_intent($query, $metrics = array()) {
        $query_lower = strtolower($query);
        $score = 0;
        $signals = array();
        
        // 1. Check commercial keywords
        foreach ($this->commercial_keywords as $keyword => $weight) {
            if (strpos($query_lower, $keyword) !== false) {
                $score += $weight;
                $signals[] = array(
                    'type' => 'commercial_keyword',
                    'keyword' => $keyword,
                    'weight' => $weight,
                );
            }
        }
        
        // 2. Check informational keywords (reduce score)
        foreach ($this->informational_keywords as $keyword => $weight) {
            if (strpos($query_lower, $keyword) !== false) {
                $score += $weight; // Negative weight
                $signals[] = array(
                    'type' => 'informational_keyword',
                    'keyword' => $keyword,
                    'weight' => $weight,
                );
            }
        }
        
        // 3. Check for brand names (increases commercial intent)
        if ($this->contains_brand_name($query_lower)) {
            $score += 30;
            $signals[] = array(
                'type' => 'brand_mention',
                'weight' => 30,
            );
        }
        
        // 4. Check for numbers (often product models or prices)
        if (preg_match('/\d+/', $query)) {
            $score += 15;
            $signals[] = array(
                'type' => 'contains_numbers',
                'weight' => 15,
            );
        }
        
        // 5. Metrics-based signals
        if (!empty($metrics)) {
            $ctr = $metrics['ctr'] ?? 0;
            $position = $metrics['position'] ?? 999;
            
            // High CTR in commercial positions = strong intent
            if ($position <= 3 && $ctr > 0.05) {
                $score += 20;
                $signals[] = array(
                    'type' => 'high_ctr_top_position',
                    'weight' => 20,
                );
            }
        }
        
        // Normalize score to 0-100
        $score = max(0, min(100, $score));
        
        // Determine intent type
        $intent_type = 'informational';
        $commercial_value = 'low';
        
        if ($score >= 70) {
            $intent_type = 'transactional';
            $commercial_value = 'high';
        } elseif ($score >= 50) {
            $intent_type = 'commercial';
            $commercial_value = 'medium';
        } elseif ($score >= 30) {
            $intent_type = 'mixed';
            $commercial_value = 'low';
        }
        
        return array(
            'query' => $query,
            'commercial_score' => $score,
            'intent_type' => $intent_type,
            'commercial_value' => $commercial_value,
            'signals' => $signals,
            'estimated_revenue_potential' => $this->estimate_revenue_potential($score, $metrics),
        );
    }
    
    /**
     * Check if query contains brand name
     * 
     * @param string $query_lower Lowercase query
     * @return bool
     */
    private function contains_brand_name($query_lower) {
        // Common brand indicators
        $brand_patterns = array(
            '/\b(apple|samsung|google|microsoft|amazon|nike|adidas)\b/',
            '/\b[A-Z][a-z]+\s+(pro|plus|max|ultra|premium)\b/i',
        );
        
        foreach ($brand_patterns as $pattern) {
            if (preg_match($pattern, $query_lower)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Estimate revenue potential
     * 
     * @param int $commercial_score Commercial score
     * @param array $metrics GSC metrics
     * @return string Revenue potential level
     */
    private function estimate_revenue_potential($commercial_score, $metrics) {
        $impressions = $metrics['impressions'] ?? 0;
        $clicks = $metrics['clicks'] ?? 0;
        
        // Calculate potential value
        $potential_score = ($commercial_score / 100) * ($impressions / 10) * (1 + ($clicks / max(1, $impressions)));
        
        if ($potential_score > 50) {
            return 'very_high';
        } elseif ($potential_score > 20) {
            return 'high';
        } elseif ($potential_score > 10) {
            return 'medium';
        } else {
            return 'low';
        }
    }
    
    /**
     * Rank opportunities by commercial value
     * 
     * @param array $opportunities Array of opportunities
     * @return array Ranked opportunities
     */
    public function rank_by_commercial_value($opportunities) {
        foreach ($opportunities as &$opp) {
            $query = $opp['query'] ?? '';
            $metrics = $opp;
            
            $intent = $this->detect_intent($query, $metrics);
            $opp['commercial_intent'] = $intent;
            $opp['commercial_score'] = $intent['commercial_score'];
        }
        
        // Sort by commercial score
        usort($opportunities, function($a, $b) {
            return ($b['commercial_score'] ?? 0) <=> ($a['commercial_score'] ?? 0);
        });
        
        return $opportunities;
    }
}
