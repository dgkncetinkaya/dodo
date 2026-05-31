<?php
/**
 * Revenue Intelligence Engine
 * 
 * Calculates real commercial value of keywords
 * Sprint D - Revenue & Topical Domination Engine
 * 
 * @package DODO_AI_SEO
 * @since 3.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Revenue_Engine {
    
    /**
     * High revenue transactional patterns
     */
    private $transactional_patterns = array(
        // Purchase intent
        '/satın\s+al/i',
        '/buy/i',
        '/purchase/i',
        '/sipariş/i',
        '/order/i',
        '/hemen\s+al/i',
        '/buy\s+now/i',
        
        // Pricing
        '/fiyat/i',
        '/price/i',
        '/kaç\s+tl/i',
        '/kaç\s+lira/i',
        '/how\s+much/i',
        '/cost/i',
        
        // Supplier/wholesale
        '/tedarikçi/i',
        '/supplier/i',
        '/wholesale/i',
        '/toptan/i',
        '/distribütör/i',
        '/distributor/i',
        
        // Quote/offer
        '/teklif\s+al/i',
        '/get\s+quote/i',
        '/fiyat\s+teklifi/i',
        '/price\s+quote/i',
    );
    
    /**
     * Commercial intent patterns
     */
    private $commercial_patterns = array(
        '/en\s+iyi/i',
        '/best/i',
        '/top\s+\d+/i',
        '/karşılaştırma/i',
        '/comparison/i',
        '/vs/i',
        '/tavsiye/i',
        '/recommend/i',
        '/review/i',
        '/yorum/i',
    );
    
    /**
     * B2B value patterns
     */
    private $b2b_patterns = array(
        '/endüstriyel/i',
        '/industrial/i',
        '/profesyonel/i',
        '/professional/i',
        '/ticari/i',
        '/commercial/i',
        '/toptan/i',
        '/wholesale/i',
        '/üretim/i',
        '/production/i',
        '/fabrika/i',
        '/factory/i',
        '/makine/i',
        '/machine/i',
        '/ekipman/i',
        '/equipment/i',
    );
    
    /**
     * Product relation patterns
     */
    private $product_patterns = array(
        '/makinesi/i',
        '/machine/i',
        '/cihaz/i',
        '/device/i',
        '/ekipman/i',
        '/equipment/i',
        '/ürün/i',
        '/product/i',
        '/model/i',
        '/marka/i',
        '/brand/i',
    );
    
    /**
     * Calculate revenue score (0-100)
     * 
     * @param string $keyword
     * @param array $context Optional context (GSC data, intent, etc.)
     * @return array Revenue analysis
     */
    public function calculate_revenue_score($keyword, $context = array()) {
        $score = 0;
        $signals = array();
        
        $keyword_lower = mb_strtolower($keyword, 'UTF-8');
        
        // 1. Transactional intent (0-40 points)
        $transactional_score = $this->detect_buyer_intent($keyword_lower);
        $score += $transactional_score;
        if ($transactional_score > 0) {
            $signals[] = 'Transactional intent detected';
        }
        
        // 2. Commercial intent (0-20 points)
        $commercial_matches = 0;
        foreach ($this->commercial_patterns as $pattern) {
            if (preg_match($pattern, $keyword_lower)) {
                $commercial_matches++;
            }
        }
        $commercial_score = min(20, $commercial_matches * 10);
        $score += $commercial_score;
        if ($commercial_score > 0) {
            $signals[] = 'Commercial intent detected';
        }
        
        // 3. B2B potential (0-20 points)
        $b2b_score = $this->detect_b2b_value($keyword_lower);
        $score += $b2b_score;
        if ($b2b_score > 0) {
            $signals[] = 'B2B potential detected';
        }
        
        // 4. Product relation (0-10 points)
        $product_score = $this->detect_product_relation($keyword_lower);
        $score += $product_score;
        if ($product_score > 0) {
            $signals[] = 'Product-related keyword';
        }
        
        // 5. GSC data boost (0-10 points)
        if (!empty($context['impressions']) && $context['impressions'] > 500) {
            $score += 10;
            $signals[] = 'High search volume';
        } elseif (!empty($context['impressions']) && $context['impressions'] > 100) {
            $score += 5;
        }
        
        // Ensure 0-100
        $score = max(0, min(100, $score));
        
        // Determine revenue level
        if ($score >= 70) {
            $revenue_level = 'high';
        } elseif ($score >= 40) {
            $revenue_level = 'medium';
        } else {
            $revenue_level = 'low';
        }
        
        // Estimate conversion probability
        $conversion_probability = $this->estimate_conversion_probability($score, $keyword_lower);
        
        // Estimate revenue impact
        $revenue_impact = $this->estimate_revenue_impact($score, $context);
        
        return array(
            'revenue_score' => $score,
            'revenue_level' => $revenue_level,
            'buyer_intent' => $transactional_score > 20 ? 'high' : ($transactional_score > 10 ? 'medium' : 'low'),
            'b2b_potential' => $b2b_score > 10 ? 'high' : ($b2b_score > 5 ? 'medium' : 'low'),
            'conversion_probability' => $conversion_probability,
            'revenue_impact' => $revenue_impact,
            'signals' => $signals,
            'money_keyword' => $score >= 60,
        );
    }
    
    /**
     * Detect buyer intent (0-40 points)
     */
    private function detect_buyer_intent($keyword) {
        $score = 0;
        
        foreach ($this->transactional_patterns as $pattern) {
            if (preg_match($pattern, $keyword)) {
                $score += 20;
                break;
            }
        }
        
        // Specific product + action
        if (preg_match('/\w+\s+(satın\s+al|buy|sipariş)/i', $keyword)) {
            $score += 20;
        }
        
        return min(40, $score);
    }
    
    /**
     * Detect B2B value (0-20 points)
     */
    private function detect_b2b_value($keyword) {
        $score = 0;
        $matches = 0;
        
        foreach ($this->b2b_patterns as $pattern) {
            if (preg_match($pattern, $keyword)) {
                $matches++;
            }
        }
        
        $score = min(20, $matches * 10);
        
        return $score;
    }
    
    /**
     * Detect product relation (0-10 points)
     */
    private function detect_product_relation($keyword) {
        $score = 0;
        
        foreach ($this->product_patterns as $pattern) {
            if (preg_match($pattern, $keyword)) {
                $score += 10;
                break;
            }
        }
        
        return $score;
    }
    
    /**
     * Estimate conversion probability (0-100%)
     */
    private function estimate_conversion_probability($revenue_score, $keyword) {
        // Base probability from revenue score
        $probability = $revenue_score * 0.5; // Max 50% from score
        
        // Boost for specific high-intent keywords
        if (preg_match('/(satın\s+al|buy|sipariş|teklif\s+al)/i', $keyword)) {
            $probability += 30;
        }
        
        // Boost for product + price
        if (preg_match('/\w+\s+fiyat/i', $keyword)) {
            $probability += 20;
        }
        
        return min(100, round($probability));
    }
    
    /**
     * Estimate revenue impact
     */
    private function estimate_revenue_impact($revenue_score, $context) {
        $impressions = $context['impressions'] ?? 0;
        $position = $context['position'] ?? 0;
        
        // Estimate potential clicks
        $estimated_ctr = 0;
        if ($position > 0 && $position <= 3) {
            $estimated_ctr = 20;
        } elseif ($position <= 10) {
            $estimated_ctr = 5;
        } else {
            $estimated_ctr = 1;
        }
        
        $potential_clicks = $impressions * ($estimated_ctr / 100);
        
        // Estimate conversion rate based on revenue score
        $conversion_rate = ($revenue_score / 100) * 0.05; // Max 5% conversion
        
        $potential_conversions = $potential_clicks * $conversion_rate;
        
        if ($potential_conversions >= 10) {
            return 'high';
        } elseif ($potential_conversions >= 3) {
            return 'medium';
        } else {
            return 'low';
        }
    }
    
    /**
     * Enrich opportunities with revenue intelligence
     */
    public function enrich_opportunities($opportunities) {
        foreach ($opportunities as &$opp) {
            $keyword = $opp['keyword'] ?? '';
            
            if (empty($keyword)) {
                continue;
            }
            
            $context = array(
                'impressions' => $opp['impressions'] ?? 0,
                'position' => $opp['position'] ?? 0,
                'ctr' => $opp['ctr'] ?? 0,
            );
            
            try {
                $revenue_analysis = $this->calculate_revenue_score($keyword, $context);
                
                $opp['revenue_score'] = $revenue_analysis['revenue_score'];
                $opp['revenue_level'] = $revenue_analysis['revenue_level'];
                $opp['buyer_intent'] = $revenue_analysis['buyer_intent'];
                $opp['conversion_probability'] = $revenue_analysis['conversion_probability'];
                $opp['revenue_impact'] = $revenue_analysis['revenue_impact'];
                $opp['money_keyword'] = $revenue_analysis['money_keyword'];
                
            } catch (Throwable $e) {
                error_log('[DODO][Revenue Engine] Error analyzing keyword: ' . $e->getMessage());
            }
        }
        
        return $opportunities;
    }
    
    /**
     * Get revenue statistics
     */
    public function get_revenue_statistics($opportunities) {
        $stats = array(
            'high_revenue' => 0,
            'medium_revenue' => 0,
            'low_revenue' => 0,
            'money_keywords' => 0,
            'high_conversion' => 0,
        );
        
        foreach ($opportunities as $opp) {
            $revenue_level = $opp['revenue_level'] ?? 'low';
            
            if ($revenue_level === 'high') {
                $stats['high_revenue']++;
            } elseif ($revenue_level === 'medium') {
                $stats['medium_revenue']++;
            } else {
                $stats['low_revenue']++;
            }
            
            if (!empty($opp['money_keyword'])) {
                $stats['money_keywords']++;
            }
            
            if (!empty($opp['conversion_probability']) && $opp['conversion_probability'] >= 60) {
                $stats['high_conversion']++;
            }
        }
        
        return $stats;
    }
}
