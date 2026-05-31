<?php
/**
 * Query Quality Filter Engine
 * 
 * Filters low-quality, spam, and irrelevant queries
 * Sprint C - SEO Intelligence Engine
 * 
 * @package DODO_AI_SEO
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Query_Quality_Engine {
    
    /**
     * Spam/low-quality patterns
     */
    private $spam_patterns = array(
        // Phone numbers
        '/\d{10,}/',
        '/\d{3}[-.\s]?\d{3}[-.\s]?\d{4}/',
        
        // Customer service
        '/müşteri\s+hizmetleri/i',
        '/customer\s+service/i',
        '/destek\s+hattı/i',
        '/iletişim\s+numarası/i',
        
        // Login/account
        '/giriş\s+yap/i',
        '/login/i',
        '/sign\s+in/i',
        '/üye\s+ol/i',
        '/kayıt\s+ol/i',
        
        // File downloads
        '/\.pdf$/i',
        '/\.doc$/i',
        '/\.xls$/i',
        '/torrent/i',
        '/indir$/i',
        '/download$/i',
        '/ücretsiz\s+indir/i',
        '/free\s+download/i',
        
        // Piracy/crack
        '/crack/i',
        '/serial/i',
        '/keygen/i',
        '/full\s+indir/i',
        '/bedava\s+indir/i',
        
        // Job/salary
        '/iş\s+ilanı/i',
        '/iş\s+başvuru/i',
        '/maaş/i',
        '/salary/i',
        '/kariyer/i',
        '/cv\s+gönder/i',
        
        // Marketplace
        '/sahibinden/i',
        '/letgo/i',
        '/ikinci\s+el/i',
        '/second\s+hand/i',
        '/satılık/i',
        '/kiralık/i',
        
        // Adult/spam
        '/porno/i',
        '/sex/i',
        '/xxx/i',
        '/casino/i',
        '/viagra/i',
        '/cialis/i',
    );
    
    /**
     * Low commercial value patterns
     */
    private $low_value_patterns = array(
        '/kim\s+buldu/i',
        '/tarihçe/i',
        '/history\s+of/i',
        '/ne\s+zaman\s+kuruldu/i',
        '/when\s+was.*founded/i',
    );
    
    /**
     * Typo/garbage patterns
     */
    private $garbage_patterns = array(
        // Random characters
        '/[a-z]{15,}/i', // Very long single word
        '/\d{5,}/', // Long numbers
        '/(.)\1{4,}/', // Repeated characters (aaaaa)
        
        // Mixed language garbage
        '/[a-z]{3,}\d+[a-z]+/i', // word123word
    );
    
    /**
     * Calculate query quality score (0-100)
     * 
     * @param string $query
     * @return array ['score' => int, 'reasons' => array, 'quality' => string]
     */
    public function calculate_quality_score($query) {
        $score = 100;
        $reasons = array();
        
        $query_lower = mb_strtolower($query, 'UTF-8');
        $query_length = mb_strlen($query, 'UTF-8');
        
        // 1. Check spam patterns (-50 points)
        foreach ($this->spam_patterns as $pattern) {
            if (preg_match($pattern, $query_lower)) {
                $score -= 50;
                $reasons[] = 'Spam/low-quality pattern detected';
                break;
            }
        }
        
        // 2. Check garbage patterns (-40 points)
        foreach ($this->garbage_patterns as $pattern) {
            if (preg_match($pattern, $query)) {
                $score -= 40;
                $reasons[] = 'Garbage/typo pattern detected';
                break;
            }
        }
        
        // 3. Check low value patterns (-20 points)
        foreach ($this->low_value_patterns as $pattern) {
            if (preg_match($pattern, $query_lower)) {
                $score -= 20;
                $reasons[] = 'Low commercial value';
                break;
            }
        }
        
        // 4. Length check
        if ($query_length < 3) {
            $score -= 30;
            $reasons[] = 'Query too short';
        } elseif ($query_length > 100) {
            $score -= 20;
            $reasons[] = 'Query too long';
        }
        
        // 5. Single word check (lower quality)
        $word_count = count(explode(' ', $query));
        if ($word_count === 1 && $query_length < 5) {
            $score -= 15;
            $reasons[] = 'Single short word';
        }
        
        // 6. Domain name check
        if (preg_match('/^[a-z0-9-]+\.(com|net|org|tr|io)$/i', $query)) {
            $score -= 40;
            $reasons[] = 'Domain name query';
        }
        
        // 7. Only numbers
        if (preg_match('/^\d+$/', $query)) {
            $score -= 35;
            $reasons[] = 'Only numbers';
        }
        
        // 8. Irrelevant country queries (for Turkish site)
        $irrelevant_countries = array('india', 'pakistan', 'bangladesh', 'nigeria', 'kenya');
        foreach ($irrelevant_countries as $country) {
            if (stripos($query_lower, $country) !== false) {
                $score -= 25;
                $reasons[] = 'Irrelevant country query';
                break;
            }
        }
        
        // 9. English garbage (for Turkish site)
        // If query is mostly English but not meaningful
        if (preg_match('/^[a-z\s]+$/i', $query) && !$this->is_meaningful_english($query)) {
            $score -= 20;
            $reasons[] = 'Irrelevant English query';
        }
        
        // Ensure score is between 0-100
        $score = max(0, min(100, $score));
        
        // Determine quality level
        if ($score >= 70) {
            $quality = 'high';
        } elseif ($score >= 40) {
            $quality = 'medium';
        } else {
            $quality = 'low';
        }
        
        return array(
            'score' => $score,
            'quality' => $quality,
            'reasons' => $reasons,
            'should_filter' => $score < 40,
        );
    }
    
    /**
     * Check if English query is meaningful
     * 
     * @param string $query
     * @return bool
     */
    private function is_meaningful_english($query) {
        // Common meaningful English keywords for industrial/technical content
        $meaningful_keywords = array(
            'screen', 'print', 'printing', 'ink', 'mesh', 'squeegee',
            'coating', 'textile', 'fabric', 'industrial', 'machine',
            'equipment', 'process', 'technique', 'method', 'guide',
            'how', 'what', 'why', 'best', 'top', 'compare', 'vs',
        );
        
        $query_lower = strtolower($query);
        
        foreach ($meaningful_keywords as $keyword) {
            if (strpos($query_lower, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Filter opportunities by quality
     * 
     * @param array $opportunities
     * @return array Filtered opportunities with quality scores
     */
    public function filter_opportunities($opportunities) {
        $filtered = array();
        $filtered_count = 0;
        
        foreach ($opportunities as $opp) {
            $keyword = $opp['keyword'] ?? '';
            
            if (empty($keyword)) {
                continue;
            }
            
            // Calculate quality
            $quality_result = $this->calculate_quality_score($keyword);
            
            // Add quality data to opportunity
            $opp['quality_score'] = $quality_result['score'];
            $opp['query_quality'] = $quality_result['quality'];
            $opp['quality_reasons'] = $quality_result['reasons'];
            
            // Filter if quality too low
            if ($quality_result['should_filter']) {
                $filtered_count++;
                error_log("[DODO][Query Quality] Filtered: '{$keyword}' (score: {$quality_result['score']})");
                continue;
            }
            
            $filtered[] = $opp;
        }
        
        error_log("[DODO][Query Quality] Filtered {$filtered_count} low-quality queries");
        
        return $filtered;
    }
    
    /**
     * Batch quality check
     * 
     * @param array $keywords Array of keywords
     * @return array Quality scores
     */
    public function batch_quality_check($keywords) {
        $results = array();
        
        foreach ($keywords as $keyword) {
            $results[$keyword] = $this->calculate_quality_score($keyword);
        }
        
        return $results;
    }
}
