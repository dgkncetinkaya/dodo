<?php
/**
 * SEO Problem Analyzer Engine
 * 
 * Analyzes CTR, Decay, Cannibalization problems and provides actionable insights
 * Sprint C - SEO Intelligence Engine (Tasks 3-6)
 * 
 * @package DODO_AI_SEO
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_SEO_Problem_Analyzer {
    
    /**
     * Analyze CTR problem (Task 3)
     * 
     * @param array $opportunity GSC opportunity with CTR data
     * @return array Problem analysis
     */
    public function analyze_ctr_problem($opportunity) {
        $query = $opportunity['query'] ?? '';
        $ctr = $opportunity['ctr'] ?? 0;
        $position = $opportunity['position'] ?? 0;
        $impressions = $opportunity['impressions'] ?? 0;
        
        $problems = array();
        $quick_wins = array();
        $recommendations = array();
        
        // Expected CTR by position
        $expected_ctr = $this->get_expected_ctr($position);
        $ctr_gap = $expected_ctr - $ctr;
        
        // 1. Title analysis
        $title_issues = $this->analyze_title_issues($query);
        if (!empty($title_issues)) {
            $problems[] = 'Title issues detected';
            $quick_wins = array_merge($quick_wins, $title_issues['quick_wins']);
            $recommendations = array_merge($recommendations, $title_issues['recommendations']);
        }
        
        // 2. Meta description check
        if ($ctr < 2 && $position <= 10) {
            $problems[] = 'Low CTR despite good position - meta description likely weak';
            $quick_wins[] = 'Rewrite meta description with emotional trigger';
            $recommendations[] = 'Add call-to-action in meta description';
        }
        
        // 3. FAQ schema opportunity
        if (stripos($query, 'nasıl') !== false || stripos($query, 'nedir') !== false) {
            $problems[] = 'Question query without FAQ schema';
            $quick_wins[] = 'Add FAQ schema markup';
            $recommendations[] = 'Create FAQ section targeting this query';
        }
        
        // 4. Featured snippet opportunity
        if ($position >= 2 && $position <= 5) {
            $problems[] = 'Position #' . round($position) . ' - featured snippet opportunity';
            $quick_wins[] = 'Optimize for featured snippet';
            $recommendations[] = 'Add concise answer paragraph at top';
        }
        
        // Severity
        if ($ctr_gap > 10) {
            $severity = 'critical';
        } elseif ($ctr_gap > 5) {
            $severity = 'high';
        } elseif ($ctr_gap > 2) {
            $severity = 'medium';
        } else {
            $severity = 'low';
        }
        
        return array(
            'problem_type' => 'low_ctr',
            'severity' => $severity,
            'current_ctr' => $ctr,
            'expected_ctr' => $expected_ctr,
            'ctr_gap' => $ctr_gap,
            'problems' => $problems,
            'quick_wins' => $quick_wins,
            'recommendations' => $recommendations,
            'estimated_clicks_gain' => round($impressions * ($ctr_gap / 100)),
            'explanation' => $this->generate_ctr_explanation($query, $ctr, $expected_ctr, $position, $problems),
        );
    }
    
    /**
     * Generate human-like CTR explanation (Task 14)
     */
    private function generate_ctr_explanation($query, $ctr, $expected_ctr, $position, $problems) {
        $explanation = "Bu query'de CTR düşük çünkü ";
        
        if ($position <= 3) {
            $explanation .= "pozisyon çok iyi (#" . round($position) . ") ama tıklama oranı beklenenin altında. ";
        } else {
            $explanation .= "pozisyon #" . round($position) . " ve CTR %" . number_format($ctr, 1) . " (beklenen: %" . number_format($expected_ctr, 1) . "). ";
        }
        
        if (!empty($problems)) {
            $explanation .= "Sorunlar: " . implode(', ', array_slice($problems, 0, 2)) . ". ";
        }
        
        $explanation .= "Hızlı kazanç için title ve meta description optimize edilmeli.";
        
        return $explanation;
    }
    
    /**
     * Analyze title issues
     */
    private function analyze_title_issues($query) {
        $issues = array();
        $quick_wins = array();
        $recommendations = array();
        
        // Generic title check
        if (stripos($query, 'nedir') !== false) {
            $issues[] = 'Generic "nedir" query needs specific title';
            $quick_wins[] = 'Add year and specifics to title';
            $recommendations[] = 'Title format: "[Topic] Nedir? 2024 Kapsamlı Rehber"';
        }
        
        // No number in title
        if (!preg_match('/\d+/', $query)) {
            $quick_wins[] = 'Add number to title (e.g., "7 Kritik", "5 Önemli")';
        }
        
        // No power words
        $power_words = array('kritik', 'önemli', 'kapsamlı', 'detaylı', 'profesyonel', 'uzman');
        $has_power_word = false;
        foreach ($power_words as $word) {
            if (stripos($query, $word) !== false) {
                $has_power_word = true;
                break;
            }
        }
        
        if (!$has_power_word) {
            $quick_wins[] = 'Add power word to title';
        }
        
        return array(
            'issues' => $issues,
            'quick_wins' => $quick_wins,
            'recommendations' => $recommendations,
        );
    }
    
    /**
     * Get expected CTR by position
     */
    private function get_expected_ctr($position) {
        $ctr_map = array(
            1 => 28.5, 2 => 15.7, 3 => 11.0, 4 => 8.0, 5 => 7.2,
            6 => 5.1, 7 => 4.0, 8 => 3.2, 9 => 2.8, 10 => 2.5,
        );
        
        $pos = intval(round($position));
        
        if (isset($ctr_map[$pos])) {
            return $ctr_map[$pos];
        } elseif ($pos > 10 && $pos <= 20) {
            return 1.5;
        } else {
            return 0.5;
        }
    }
    
    /**
     * Analyze content decay (Task 4)
     * 
     * @param array $opportunity Decay opportunity
     * @return array Decay analysis
     */
    public function analyze_decay_problem($opportunity) {
        $page = $opportunity['page'] ?? '';
        $impression_change = $opportunity['impression_change_pct'] ?? 0;
        $position_change = $opportunity['position_change'] ?? 0;
        
        $decay_reasons = array();
        $recommendations = array();
        $priority = 'medium';
        
        // Determine decay reason
        if ($position_change > 5) {
            $decay_reasons[] = 'Sıralama düşüşü - rakipler güçlenmiş';
            $recommendations[] = 'Rakip analizi yap ve eksik konuları ekle';
            $recommendations[] = 'Internal link yapısını güçlendir';
            $priority = 'high';
        }
        
        if ($impression_change < -30) {
            $decay_reasons[] = 'Arama hacmi düşmüş veya içerik güncelliğini kaybetmiş';
            $recommendations[] = 'Güncel istatistikler ve tarihler ekle';
            $recommendations[] = 'Yeni section\'lar ekle';
        }
        
        if (abs($impression_change) < 20 && $position_change < 3) {
            $decay_reasons[] = 'CTR düşüşü - title/description çekiciliğini kaybetmiş';
            $recommendations[] = 'Title ve meta description yenile';
            $recommendations[] = 'Yeni FAQ ekle';
        }
        
        // Estimate lost clicks
        $previous_impressions = $opportunity['previous_impressions'] ?? 0;
        $current_impressions = $opportunity['current_impressions'] ?? 0;
        $lost_clicks_estimate = abs($previous_impressions - $current_impressions) * 0.05; // Assume 5% CTR
        
        return array(
            'problem_type' => 'content_decay',
            'decay_reasons' => $decay_reasons,
            'recommendations' => $recommendations,
            'priority_level' => $priority,
            'lost_clicks_estimate' => round($lost_clicks_estimate),
            'impression_change_pct' => $impression_change,
            'position_change' => $position_change,
            'explanation' => $this->generate_decay_explanation($decay_reasons, $impression_change, $position_change),
        );
    }
    
    /**
     * Generate human-like decay explanation (Task 14)
     */
    private function generate_decay_explanation($decay_reasons, $impression_change, $position_change) {
        $explanation = "İçerik performansı düşüyor. ";
        
        if (!empty($decay_reasons)) {
            $explanation .= $decay_reasons[0] . ". ";
        }
        
        if ($impression_change < -30) {
            $explanation .= "Gösterimler %" . abs(round($impression_change)) . " azalmış. ";
        }
        
        if ($position_change > 5) {
            $explanation .= "Sıralama " . round($position_change) . " pozisyon düşmüş. ";
        }
        
        $explanation .= "Acil güncelleme gerekiyor.";
        
        return $explanation;
    }
    
    /**
     * Analyze cannibalization (Task 5)
     * 
     * @param array $opportunity Cannibalization opportunity
     * @return array Cannibalization analysis
     */
    public function analyze_cannibalization($opportunity) {
        $query = $opportunity['query'] ?? $opportunity['keyword'] ?? '';
        $competing_urls = $opportunity['pages'] ?? $opportunity['competing_urls'] ?? array();
        $total_impressions = $opportunity['total_impressions'] ?? 0;
        
        $recommendations = array();
        $suggested_fix = '';
        $confidence = 'medium';
        
        $url_count = count($competing_urls);
        
        if ($url_count == 2) {
            $suggested_fix = 'merge';
            $recommendations[] = 'İki sayfayı birleştir - daha güçlü tek sayfa oluştur';
            $recommendations[] = 'Zayıf sayfadan güçlü sayfaya 301 redirect';
            $confidence = 'high';
        } elseif ($url_count >= 3) {
            $suggested_fix = 'canonical';
            $recommendations[] = 'Primary URL belirle ve canonical tag ekle';
            $recommendations[] = 'Diğer sayfaları farklı intent\'e yönlendir';
            $confidence = 'medium';
        }
        
        // Primary URL recommendation
        $primary_url = $this->recommend_primary_url($competing_urls);
        
        // Internal linking fix
        $recommendations[] = 'Internal link anchor text\'leri düzelt';
        $recommendations[] = 'Her sayfanın farklı intent\'i hedeflemesini sağla';
        
        return array(
            'problem_type' => 'cannibalization',
            'query' => $query,
            'competing_urls' => $competing_urls,
            'primary_url' => $primary_url,
            'suggested_fix' => $suggested_fix,
            'recommendations' => $recommendations,
            'confidence' => $confidence,
            'url_count' => $url_count,
            'explanation' => $this->generate_cannibalization_explanation($query, $url_count, $suggested_fix),
        );
    }
    
    /**
     * Generate human-like cannibalization explanation (Task 14)
     */
    private function generate_cannibalization_explanation($query, $url_count, $suggested_fix) {
        $explanation = "'{$query}' için {$url_count} farklı sayfa yarışıyor. ";
        
        $explanation .= "Bu kannibalizasyon Google'ın hangi sayfayı göstereceğini karıştırıyor ve tüm sayfaların sıralamasını düşürüyor. ";
        
        if ($suggested_fix === 'merge') {
            $explanation .= "Çözüm: Sayfaları birleştir ve daha güçlü tek sayfa oluştur.";
        } elseif ($suggested_fix === 'canonical') {
            $explanation .= "Çözüm: Primary URL belirle ve canonical tag ekle.";
        } else {
            $explanation .= "Çözüm: Her sayfanın farklı intent hedeflemesini sağla.";
        }
        
        return $explanation;
    }
    
    /**
     * Recommend primary URL
     */
    private function recommend_primary_url($urls) {
        if (empty($urls)) {
            return '';
        }
        
        // Simple heuristic: prefer shorter, cleaner URLs
        usort($urls, function($a, $b) {
            return strlen($a) - strlen($b);
        });
        
        return $urls[0];
    }
    
    /**
     * Analyze topical authority gap (Task 6)
     * 
     * @param string $main_topic
     * @param array $existing_content
     * @return array Authority gap analysis
     */
    public function analyze_topical_authority($main_topic, $existing_content = array()) {
        // This would ideally use semantic analysis
        // For now, basic keyword-based gap detection
        
        $authority_score = 0;
        $missing_subtopics = array();
        $coverage_gaps = array();
        
        // Calculate authority score based on content coverage
        $content_count = count($existing_content);
        
        if ($content_count >= 20) {
            $authority_score = 90;
        } elseif ($content_count >= 10) {
            $authority_score = 70;
        } elseif ($content_count >= 5) {
            $authority_score = 50;
        } else {
            $authority_score = 30;
        }
        
        // Identify gaps (simplified)
        $expected_subtopics = $this->get_expected_subtopics($main_topic);
        
        foreach ($expected_subtopics as $subtopic) {
            $found = false;
            foreach ($existing_content as $content) {
                if (stripos($content, $subtopic) !== false) {
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $missing_subtopics[] = $subtopic;
            }
        }
        
        return array(
            'main_topic' => $main_topic,
            'authority_score' => $authority_score,
            'content_count' => $content_count,
            'missing_subtopics' => $missing_subtopics,
            'coverage_gaps' => $coverage_gaps,
        );
    }
    
    /**
     * Get expected subtopics for a main topic
     */
    private function get_expected_subtopics($main_topic) {
        // This is simplified - in production, use semantic analysis or AI
        $common_subtopics = array(
            'nedir',
            'nasıl kullanılır',
            'çeşitleri',
            'avantajları',
            'dezavantajları',
            'fiyat',
            'satın alma rehberi',
            'karşılaştırma',
        );
        
        return $common_subtopics;
    }
}
