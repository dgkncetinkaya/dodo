<?php
/**
 * Commercial Intent Engine
 * 
 * Analyzes commercial value and intent of keywords
 * Sprint C - SEO Intelligence Engine
 * 
 * @package DODO_AI_SEO
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Commercial_Intent_Engine {
    
    /**
     * High commercial intent patterns
     */
    private $high_commercial_patterns = array(
        // Purchase intent
        '/fiyat/i',
        '/price/i',
        '/satın\s+al/i',
        '/buy/i',
        '/purchase/i',
        '/sipariş/i',
        '/order/i',
        
        // Comparison
        '/en\s+iyi/i',
        '/best/i',
        '/top\s+\d+/i',
        '/vs/i',
        '/karşılaştırma/i',
        '/comparison/i',
        '/compare/i',
        
        // Recommendation
        '/tavsiye/i',
        '/öner/i',
        '/recommend/i',
        '/suggestion/i',
        
        // Where to buy
        '/nereden\s+alınır/i',
        '/nereden\s+alabilirim/i',
        '/where\s+to\s+buy/i',
        '/nerede\s+satılır/i',
        
        // Supplier/wholesale
        '/tedarikçi/i',
        '/supplier/i',
        '/wholesale/i',
        '/toptan/i',
        '/distribütör/i',
        '/distributor/i',
        
        // Cost/budget
        '/kaç\s+tl/i',
        '/kaç\s+lira/i',
        '/ne\s+kadar/i',
        '/how\s+much/i',
        '/cost/i',
        '/bütçe/i',
        '/budget/i',
        
        // Review/rating
        '/yorum/i',
        '/review/i',
        '/değerlendirme/i',
        '/rating/i',
        '/puan/i',
        
        // Discount/deal
        '/indirim/i',
        '/discount/i',
        '/kampanya/i',
        '/deal/i',
        '/ucuz/i',
        '/cheap/i',
        '/uygun\s+fiyat/i',
        '/affordable/i',
    );
    
    /**
     * Medium commercial intent patterns
     */
    private $medium_commercial_patterns = array(
        '/nasıl\s+seçilir/i',
        '/how\s+to\s+choose/i',
        '/özellikleri/i',
        '/features/i',
        '/avantajları/i',
        '/advantages/i',
        '/faydaları/i',
        '/benefits/i',
        '/kullanım/i',
        '/usage/i',
        '/uygulama/i',
        '/application/i',
        '/çeşitleri/i',
        '/types/i',
        '/modelleri/i',
        '/models/i',
    );
    
    /**
     * Low commercial intent patterns (informational)
     */
    private $low_commercial_patterns = array(
        '/nedir/i',
        '/what\s+is/i',
        '/ne\s+demek/i',
        '/tanım/i',
        '/definition/i',
        '/tarihçe/i',
        '/history/i',
        '/kim\s+buldu/i',
        '/who\s+invented/i',
        '/ne\s+zaman/i',
        '/when/i',
        '/nerede\s+kullanılır/i',
        '/where\s+used/i',
    );
    
    /**
     * Transactional keywords
     */
    private $transactional_keywords = array(
        'satın al', 'buy', 'sipariş', 'order', 'sepete ekle', 'add to cart',
        'hemen al', 'buy now', 'teklif al', 'get quote', 'fiyat teklifi',
    );
    
    /**
     * Navigational keywords
     */
    private $navigational_keywords = array(
        'giriş', 'login', 'kayıt', 'register', 'hesap', 'account',
        'iletişim', 'contact', 'hakkımızda', 'about us',
    );
    
    /**
     * Calculate commercial intent score (0-100)
     * 
     * @param string $query
     * @return array ['score' => int, 'intent_type' => string, 'intent_level' => string, 'signals' => array]
     */
    public function calculate_commercial_score($query) {
        $score = 0;
        $signals = array();
        $query_lower = mb_strtolower($query, 'UTF-8');
        
        // 1. Check high commercial patterns (+30 points each, max 60)
        $high_matches = 0;
        foreach ($this->high_commercial_patterns as $pattern) {
            if (preg_match($pattern, $query_lower)) {
                $high_matches++;
                $signals[] = 'High commercial keyword detected';
            }
        }
        $score += min(60, $high_matches * 30);
        
        // 2. Check medium commercial patterns (+15 points each, max 30)
        $medium_matches = 0;
        foreach ($this->medium_commercial_patterns as $pattern) {
            if (preg_match($pattern, $query_lower)) {
                $medium_matches++;
                $signals[] = 'Medium commercial keyword detected';
            }
        }
        $score += min(30, $medium_matches * 15);
        
        // 3. Check low commercial patterns (informational) (-20 points)
        foreach ($this->low_commercial_patterns as $pattern) {
            if (preg_match($pattern, $query_lower)) {
                $score -= 20;
                $signals[] = 'Informational keyword detected';
                break;
            }
        }
        
        // 4. Product/brand modifiers (+10 points)
        if (preg_match('/marka/i', $query_lower) || preg_match('/brand/i', $query_lower)) {
            $score += 10;
            $signals[] = 'Brand keyword';
        }
        
        // 5. Specific product terms (+15 points)
        if (preg_match('/\d+\s*(ml|gr|kg|lt|adet|piece)/i', $query_lower)) {
            $score += 15;
            $signals[] = 'Specific product specification';
        }
        
        // 6. Year in query (+5 points - indicates freshness need)
        if (preg_match('/202\d/i', $query_lower)) {
            $score += 5;
            $signals[] = 'Year mentioned (current info needed)';
        }
        
        // Ensure score is between 0-100
        $score = max(0, min(100, $score));
        
        // Determine intent type
        $intent_type = $this->determine_intent_type($query_lower);
        
        // Determine intent level
        if ($score >= 70) {
            $intent_level = 'high';
        } elseif ($score >= 40) {
            $intent_level = 'medium';
        } else {
            $intent_level = 'low';
        }
        
        return array(
            'score' => $score,
            'intent_type' => $intent_type,
            'intent_level' => $intent_level,
            'signals' => $signals,
        );
    }
    
    /**
     * Determine intent type
     * 
     * @param string $query_lower
     * @return string informational|commercial|transactional|navigational
     */
    private function determine_intent_type($query_lower) {
        // Check transactional
        foreach ($this->transactional_keywords as $keyword) {
            if (stripos($query_lower, $keyword) !== false) {
                return 'transactional';
            }
        }
        
        // Check navigational
        foreach ($this->navigational_keywords as $keyword) {
            if (stripos($query_lower, $keyword) !== false) {
                return 'navigational';
            }
        }
        
        // Check commercial
        foreach ($this->high_commercial_patterns as $pattern) {
            if (preg_match($pattern, $query_lower)) {
                return 'commercial';
            }
        }
        
        // Default to informational
        return 'informational';
    }
    
    /**
     * Get intent badge
     * 
     * @param string $intent_type
     * @return string
     */
    public function get_intent_badge($intent_type) {
        $badges = array(
            'transactional' => '🛒 Satın Alma',
            'commercial' => '💰 Ticari',
            'navigational' => '🧭 Navigasyon',
            'informational' => '📚 Bilgilendirici',
        );
        
        return $badges[$intent_type] ?? '📚 Bilgilendirici';
    }
    
    /**
     * Enrich opportunities with commercial intent
     * 
     * @param array $opportunities
     * @return array Enriched opportunities
     */
    public function enrich_opportunities($opportunities) {
        foreach ($opportunities as &$opp) {
            $keyword = $opp['keyword'] ?? '';
            
            if (empty($keyword)) {
                continue;
            }
            
            // Calculate commercial intent
            $intent_result = $this->calculate_commercial_score($keyword);
            
            // Add commercial data
            $opp['commercial_score'] = $intent_result['score'];
            $opp['intent_type'] = $intent_result['intent_type'];
            $opp['commercial_level'] = $intent_result['intent_level'];
            $opp['commercial_signals'] = $intent_result['signals'];
            
            // Boost score for high commercial intent
            if ($intent_result['score'] >= 70) {
                $opp['score'] = min(100, $opp['score'] + 10);
            }
        }
        
        return $opportunities;
    }
    
    /**
     * Get commercial intent statistics
     * 
     * @param array $opportunities
     * @return array Statistics
     */
    public function get_intent_statistics($opportunities) {
        $stats = array(
            'transactional' => 0,
            'commercial' => 0,
            'navigational' => 0,
            'informational' => 0,
            'high_value' => 0,
            'medium_value' => 0,
            'low_value' => 0,
        );
        
        foreach ($opportunities as $opp) {
            $intent_type = $opp['intent_type'] ?? 'informational';
            $commercial_level = $opp['commercial_level'] ?? 'low';
            
            if (isset($stats[$intent_type])) {
                $stats[$intent_type]++;
            }
            
            if ($commercial_level === 'high') {
                $stats['high_value']++;
            } elseif ($commercial_level === 'medium') {
                $stats['medium_value']++;
            } else {
                $stats['low_value']++;
            }
        }
        
        return $stats;
    }
    
    /**
     * Filter by commercial intent
     * 
     * @param array $opportunities
     * @param string $min_level low|medium|high
     * @return array Filtered opportunities
     */
    public function filter_by_commercial_intent($opportunities, $min_level = 'low') {
        $level_map = array(
            'low' => 0,
            'medium' => 40,
            'high' => 70,
        );
        
        $min_score = $level_map[$min_level] ?? 0;
        
        return array_filter($opportunities, function($opp) use ($min_score) {
            $commercial_score = $opp['commercial_score'] ?? 0;
            return $commercial_score >= $min_score;
        });
    }
}
