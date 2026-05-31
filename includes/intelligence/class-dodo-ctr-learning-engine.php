<?php
/**
 * CTR Optimization Learning Engine
 * 
 * Learns which title/meta patterns work best for CTR optimization
 * Sprint E - Feedback Loop & Self-Learning SEO System
 * 
 * @package DODO_AI_SEO
 * @since 3.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_CTR_Learning_Engine {
    
    /**
     * Analyze CTR patterns across content
     * 
     * @return array Pattern analysis
     */
    public function analyze_ctr_patterns() {
        $analysis = array(
            'high_ctr_patterns' => array(),
            'low_ctr_patterns' => array(),
            'title_patterns' => array(),
            'meta_patterns' => array(),
            'insights' => array(),
        );
        
        try {
            // Get all published posts with GSC data
            $posts = $this->get_posts_with_gsc_data();
            
            if (empty($posts)) {
                return $analysis;
            }
            
            // Analyze title patterns
            $analysis['title_patterns'] = $this->analyze_title_patterns($posts);
            
            // Analyze meta patterns
            $analysis['meta_patterns'] = $this->analyze_meta_patterns($posts);
            
            // Detect high CTR patterns
            $analysis['high_ctr_patterns'] = $this->detect_high_ctr_patterns($posts);
            
            // Detect low CTR patterns
            $analysis['low_ctr_patterns'] = $this->detect_low_ctr_patterns($posts);
            
            // Generate insights
            $analysis['insights'] = $this->generate_ctr_insights($analysis);
            
        } catch (Throwable $e) {
            error_log('[DODO][CTR Learning] Error analyzing patterns: ' . $e->getMessage());
        }
        
        return $analysis;
    }
    
    /**
     * Get posts with GSC data
     * 
     * @return array Posts with CTR data
     */
    private function get_posts_with_gsc_data() {
        $posts_data = array();
        
        try {
            // Get published posts
            $posts = get_posts(array(
                'post_type' => 'post',
                'post_status' => 'publish',
                'posts_per_page' => 100,
                'orderby' => 'date',
                'order' => 'DESC',
            ));
            
            if (!class_exists('DODO_GSC_Intelligence')) {
                return $posts_data;
            }
            
            $gsc = new DODO_GSC_Intelligence();
            
            if (!$gsc->is_available()) {
                return $posts_data;
            }
            
            // Get GSC data
            $raw_data = $gsc->fetch_gsc_data(28);
            
            if (empty($raw_data)) {
                return $posts_data;
            }
            
            // Match posts with GSC data
            foreach ($posts as $post) {
                $post_url = get_permalink($post->ID);
                $impressions = 0;
                $clicks = 0;
                $ctr = 0;
                $position = 0;
                
                // Find GSC data for this post
                foreach ($raw_data as $row) {
                    $url = $row['keys'][1] ?? '';
                    
                    if (strpos($url, $post_url) !== false) {
                        $impressions += $row['impressions'] ?? 0;
                        $clicks += $row['clicks'] ?? 0;
                    }
                }
                
                // Calculate CTR
                if ($impressions > 0) {
                    $ctr = ($clicks / $impressions) * 100;
                }
                
                // Only include posts with meaningful data
                if ($impressions >= 10) {
                    $posts_data[] = array(
                        'post_id' => $post->ID,
                        'title' => $post->post_title,
                        'meta_description' => get_post_meta($post->ID, 'rank_math_description', true),
                        'impressions' => $impressions,
                        'clicks' => $clicks,
                        'ctr' => round($ctr, 2),
                        'position' => $position,
                    );
                }
            }
            
        } catch (Throwable $e) {
            error_log('[DODO][CTR Learning] Error getting posts: ' . $e->getMessage());
        }
        
        return $posts_data;
    }
    
    /**
     * Analyze title patterns
     * 
     * @param array $posts
     * @return array Title pattern analysis
     */
    private function analyze_title_patterns($posts) {
        $patterns = array(
            'has_number' => array('count' => 0, 'avg_ctr' => 0, 'total_ctr' => 0),
            'has_year' => array('count' => 0, 'avg_ctr' => 0, 'total_ctr' => 0),
            'has_question' => array('count' => 0, 'avg_ctr' => 0, 'total_ctr' => 0),
            'has_how_to' => array('count' => 0, 'avg_ctr' => 0, 'total_ctr' => 0),
            'has_list' => array('count' => 0, 'avg_ctr' => 0, 'total_ctr' => 0),
            'has_brackets' => array('count' => 0, 'avg_ctr' => 0, 'total_ctr' => 0),
            'has_power_words' => array('count' => 0, 'avg_ctr' => 0, 'total_ctr' => 0),
            'short_title' => array('count' => 0, 'avg_ctr' => 0, 'total_ctr' => 0), // < 50 chars
            'medium_title' => array('count' => 0, 'avg_ctr' => 0, 'total_ctr' => 0), // 50-60 chars
            'long_title' => array('count' => 0, 'avg_ctr' => 0, 'total_ctr' => 0), // > 60 chars
        );
        
        $power_words = array('en iyi', 'ücretsiz', 'kolay', 'hızlı', 'garantili', 'kanıtlanmış', 'ultimate', 'complete', 'guide');
        
        foreach ($posts as $post) {
            $title = mb_strtolower($post['title'], 'UTF-8');
            $ctr = $post['ctr'];
            $title_length = mb_strlen($post['title'], 'UTF-8');
            
            // Has number
            if (preg_match('/\d+/', $title)) {
                $patterns['has_number']['count']++;
                $patterns['has_number']['total_ctr'] += $ctr;
            }
            
            // Has year
            if (preg_match('/20\d{2}/', $title)) {
                $patterns['has_year']['count']++;
                $patterns['has_year']['total_ctr'] += $ctr;
            }
            
            // Has question
            if (strpos($title, '?') !== false || preg_match('/(nasıl|neden|ne|kim|nerede|hangi)/u', $title)) {
                $patterns['has_question']['count']++;
                $patterns['has_question']['total_ctr'] += $ctr;
            }
            
            // Has how-to
            if (preg_match('/(nasıl|how to)/u', $title)) {
                $patterns['has_how_to']['count']++;
                $patterns['has_how_to']['total_ctr'] += $ctr;
            }
            
            // Has list indicators
            if (preg_match('/(\d+\s+(adım|yol|yöntem|tip|öneri|sebep|neden)|liste)/u', $title)) {
                $patterns['has_list']['count']++;
                $patterns['has_list']['total_ctr'] += $ctr;
            }
            
            // Has brackets
            if (preg_match('/[\[\(\{]/', $title)) {
                $patterns['has_brackets']['count']++;
                $patterns['has_brackets']['total_ctr'] += $ctr;
            }
            
            // Has power words
            foreach ($power_words as $word) {
                if (strpos($title, $word) !== false) {
                    $patterns['has_power_words']['count']++;
                    $patterns['has_power_words']['total_ctr'] += $ctr;
                    break;
                }
            }
            
            // Title length
            if ($title_length < 50) {
                $patterns['short_title']['count']++;
                $patterns['short_title']['total_ctr'] += $ctr;
            } elseif ($title_length <= 60) {
                $patterns['medium_title']['count']++;
                $patterns['medium_title']['total_ctr'] += $ctr;
            } else {
                $patterns['long_title']['count']++;
                $patterns['long_title']['total_ctr'] += $ctr;
            }
        }
        
        // Calculate averages
        foreach ($patterns as $key => $data) {
            if ($data['count'] > 0) {
                $patterns[$key]['avg_ctr'] = round($data['total_ctr'] / $data['count'], 2);
            }
        }
        
        // Sort by avg_ctr
        uasort($patterns, function($a, $b) {
            return $b['avg_ctr'] <=> $a['avg_ctr'];
        });
        
        return $patterns;
    }
    
    /**
     * Analyze meta description patterns
     * 
     * @param array $posts
     * @return array Meta pattern analysis
     */
    private function analyze_meta_patterns($posts) {
        $patterns = array(
            'has_cta' => array('count' => 0, 'avg_ctr' => 0, 'total_ctr' => 0),
            'has_emoji' => array('count' => 0, 'avg_ctr' => 0, 'total_ctr' => 0),
            'has_number' => array('count' => 0, 'avg_ctr' => 0, 'total_ctr' => 0),
            'has_question' => array('count' => 0, 'avg_ctr' => 0, 'total_ctr' => 0),
            'short_meta' => array('count' => 0, 'avg_ctr' => 0, 'total_ctr' => 0), // < 120 chars
            'optimal_meta' => array('count' => 0, 'avg_ctr' => 0, 'total_ctr' => 0), // 120-155 chars
            'long_meta' => array('count' => 0, 'avg_ctr' => 0, 'total_ctr' => 0), // > 155 chars
        );
        
        $cta_words = array('öğren', 'keşfet', 'incele', 'bul', 'gör', 'oku', 'tıkla', 'başla');
        
        foreach ($posts as $post) {
            $meta = mb_strtolower($post['meta_description'] ?? '', 'UTF-8');
            
            if (empty($meta)) {
                continue;
            }
            
            $ctr = $post['ctr'];
            $meta_length = mb_strlen($meta, 'UTF-8');
            
            // Has CTA
            foreach ($cta_words as $word) {
                if (strpos($meta, $word) !== false) {
                    $patterns['has_cta']['count']++;
                    $patterns['has_cta']['total_ctr'] += $ctr;
                    break;
                }
            }
            
            // Has emoji
            if (preg_match('/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}]/u', $meta)) {
                $patterns['has_emoji']['count']++;
                $patterns['has_emoji']['total_ctr'] += $ctr;
            }
            
            // Has number
            if (preg_match('/\d+/', $meta)) {
                $patterns['has_number']['count']++;
                $patterns['has_number']['total_ctr'] += $ctr;
            }
            
            // Has question
            if (strpos($meta, '?') !== false) {
                $patterns['has_question']['count']++;
                $patterns['has_question']['total_ctr'] += $ctr;
            }
            
            // Meta length
            if ($meta_length < 120) {
                $patterns['short_meta']['count']++;
                $patterns['short_meta']['total_ctr'] += $ctr;
            } elseif ($meta_length <= 155) {
                $patterns['optimal_meta']['count']++;
                $patterns['optimal_meta']['total_ctr'] += $ctr;
            } else {
                $patterns['long_meta']['count']++;
                $patterns['long_meta']['total_ctr'] += $ctr;
            }
        }
        
        // Calculate averages
        foreach ($patterns as $key => $data) {
            if ($data['count'] > 0) {
                $patterns[$key]['avg_ctr'] = round($data['total_ctr'] / $data['count'], 2);
            }
        }
        
        // Sort by avg_ctr
        uasort($patterns, function($a, $b) {
            return $b['avg_ctr'] <=> $a['avg_ctr'];
        });
        
        return $patterns;
    }
    
    /**
     * Detect high CTR patterns
     * 
     * @param array $posts
     * @return array High performing patterns
     */
    private function detect_high_ctr_patterns($posts) {
        $high_ctr_posts = array_filter($posts, function($post) {
            return $post['ctr'] >= 5; // CTR >= 5% is good
        });
        
        if (empty($high_ctr_posts)) {
            return array();
        }
        
        $patterns = array();
        
        // Analyze common traits
        $total_with_number = 0;
        $total_with_year = 0;
        $total_with_question = 0;
        $total_with_list = 0;
        
        foreach ($high_ctr_posts as $post) {
            $title = mb_strtolower($post['title'], 'UTF-8');
            
            if (preg_match('/\d+/', $title)) {
                $total_with_number++;
            }
            
            if (preg_match('/20\d{2}/', $title)) {
                $total_with_year++;
            }
            
            if (strpos($title, '?') !== false || preg_match('/(nasıl|neden|ne)/u', $title)) {
                $total_with_question++;
            }
            
            if (preg_match('/(\d+\s+(adım|yol|yöntem)|liste)/u', $title)) {
                $total_with_list++;
            }
        }
        
        $total_high_ctr = count($high_ctr_posts);
        
        // Calculate percentages
        if ($total_with_number / $total_high_ctr > 0.5) {
            $patterns[] = array(
                'pattern' => 'Başlıkta sayı kullanımı',
                'percentage' => round(($total_with_number / $total_high_ctr) * 100),
                'recommendation' => 'Başlıklarda sayı kullanmak CTR\'yi artırıyor',
            );
        }
        
        if ($total_with_year / $total_high_ctr > 0.4) {
            $patterns[] = array(
                'pattern' => 'Başlıkta yıl kullanımı',
                'percentage' => round(($total_with_year / $total_high_ctr) * 100),
                'recommendation' => 'Güncel yıl eklemek içeriği taze gösteriyor',
            );
        }
        
        if ($total_with_question / $total_high_ctr > 0.3) {
            $patterns[] = array(
                'pattern' => 'Soru formatı',
                'percentage' => round(($total_with_question / $total_high_ctr) * 100),
                'recommendation' => 'Soru formatındaki başlıklar merak uyandırıyor',
            );
        }
        
        if ($total_with_list / $total_high_ctr > 0.3) {
            $patterns[] = array(
                'pattern' => 'Liste formatı',
                'percentage' => round(($total_with_list / $total_high_ctr) * 100),
                'recommendation' => 'Liste formatı net beklenti oluşturuyor',
            );
        }
        
        return $patterns;
    }
    
    /**
     * Detect low CTR patterns
     * 
     * @param array $posts
     * @return array Low performing patterns
     */
    private function detect_low_ctr_patterns($posts) {
        $low_ctr_posts = array_filter($posts, function($post) {
            return $post['ctr'] < 2 && $post['impressions'] >= 50; // CTR < 2% with decent impressions
        });
        
        if (empty($low_ctr_posts)) {
            return array();
        }
        
        $patterns = array();
        
        // Analyze common issues
        $total_too_long = 0;
        $total_too_short = 0;
        $total_no_number = 0;
        $total_generic = 0;
        
        $generic_words = array('hakkında', 'bilgi', 'genel', 'detay');
        
        foreach ($low_ctr_posts as $post) {
            $title = $post['title'];
            $title_lower = mb_strtolower($title, 'UTF-8');
            $title_length = mb_strlen($title, 'UTF-8');
            
            if ($title_length > 70) {
                $total_too_long++;
            }
            
            if ($title_length < 30) {
                $total_too_short++;
            }
            
            if (!preg_match('/\d+/', $title)) {
                $total_no_number++;
            }
            
            foreach ($generic_words as $word) {
                if (strpos($title_lower, $word) !== false) {
                    $total_generic++;
                    break;
                }
            }
        }
        
        $total_low_ctr = count($low_ctr_posts);
        
        // Calculate percentages
        if ($total_too_long / $total_low_ctr > 0.4) {
            $patterns[] = array(
                'pattern' => 'Çok uzun başlık',
                'percentage' => round(($total_too_long / $total_low_ctr) * 100),
                'issue' => 'Başlıklar 70 karakterden uzun, SERP\'te kesiliyor',
            );
        }
        
        if ($total_too_short / $total_low_ctr > 0.3) {
            $patterns[] = array(
                'pattern' => 'Çok kısa başlık',
                'percentage' => round(($total_too_short / $total_low_ctr) * 100),
                'issue' => 'Başlıklar çok kısa, yeterli bilgi vermiyor',
            );
        }
        
        if ($total_no_number / $total_low_ctr > 0.6) {
            $patterns[] = array(
                'pattern' => 'Sayı eksikliği',
                'percentage' => round(($total_no_number / $total_low_ctr) * 100),
                'issue' => 'Başlıklarda sayı yok, spesifiklik eksik',
            );
        }
        
        if ($total_generic / $total_low_ctr > 0.4) {
            $patterns[] = array(
                'pattern' => 'Jenerik başlık',
                'percentage' => round(($total_generic / $total_low_ctr) * 100),
                'issue' => 'Başlıklar çok genel, değer önerisi belirsiz',
            );
        }
        
        return $patterns;
    }
    
    /**
     * Generate CTR insights
     * 
     * @param array $analysis
     * @return array Insights
     */
    private function generate_ctr_insights($analysis) {
        $insights = array();
        
        // Title pattern insights
        if (!empty($analysis['title_patterns'])) {
            $best_title_pattern = array_key_first($analysis['title_patterns']);
            $best_ctr = $analysis['title_patterns'][$best_title_pattern]['avg_ctr'];
            
            if ($best_ctr > 0) {
                $insights[] = array(
                    'type' => 'title_pattern',
                    'insight' => "En yüksek CTR: {$this->translate_pattern($best_title_pattern)} (Ort. CTR: {$best_ctr}%)",
                    'action' => 'Yeni içeriklerde bu pattern kullanılmalı',
                );
            }
        }
        
        // Meta pattern insights
        if (!empty($analysis['meta_patterns'])) {
            $best_meta_pattern = array_key_first($analysis['meta_patterns']);
            $best_meta_ctr = $analysis['meta_patterns'][$best_meta_pattern]['avg_ctr'];
            
            if ($best_meta_ctr > 0) {
                $insights[] = array(
                    'type' => 'meta_pattern',
                    'insight' => "En etkili meta: {$this->translate_pattern($best_meta_pattern)} (Ort. CTR: {$best_meta_ctr}%)",
                    'action' => 'Meta description\'larda bu yaklaşım tercih edilmeli',
                );
            }
        }
        
        // High CTR patterns
        if (!empty($analysis['high_ctr_patterns'])) {
            $top_pattern = $analysis['high_ctr_patterns'][0];
            $insights[] = array(
                'type' => 'success_pattern',
                'insight' => "Yüksek CTR içeriklerin %{$top_pattern['percentage']}'inde: {$top_pattern['pattern']}",
                'action' => $top_pattern['recommendation'],
            );
        }
        
        // Low CTR patterns
        if (!empty($analysis['low_ctr_patterns'])) {
            $top_issue = $analysis['low_ctr_patterns'][0];
            $insights[] = array(
                'type' => 'warning',
                'insight' => "Düşük CTR içeriklerin %{$top_issue['percentage']}'inde: {$top_issue['pattern']}",
                'action' => $top_issue['issue'],
            );
        }
        
        return $insights;
    }
    
    /**
     * Translate pattern key to Turkish
     * 
     * @param string $pattern
     * @return string
     */
    private function translate_pattern($pattern) {
        $translations = array(
            'has_number' => 'Sayı içeren başlıklar',
            'has_year' => 'Yıl içeren başlıklar',
            'has_question' => 'Soru formatı',
            'has_how_to' => 'Nasıl yapılır formatı',
            'has_list' => 'Liste formatı',
            'has_brackets' => 'Parantez/köşeli parantez',
            'has_power_words' => 'Güçlü kelimeler',
            'short_title' => 'Kısa başlık (<50 karakter)',
            'medium_title' => 'Orta başlık (50-60 karakter)',
            'long_title' => 'Uzun başlık (>60 karakter)',
            'has_cta' => 'CTA içeren meta',
            'has_emoji' => 'Emoji içeren meta',
            'optimal_meta' => 'Optimal uzunluk meta (120-155)',
            'short_meta' => 'Kısa meta (<120)',
            'long_meta' => 'Uzun meta (>155)',
        );
        
        return $translations[$pattern] ?? $pattern;
    }
    
    /**
     * Learn from winners - get best performing title patterns
     * 
     * @return array Learned patterns
     */
    public function learn_title_patterns() {
        $learned = array(
            'best_patterns' => array(),
            'avoid_patterns' => array(),
            'recommendations' => array(),
        );
        
        try {
            $analysis = $this->analyze_ctr_patterns();
            
            // Get top 3 title patterns
            $title_patterns = array_slice($analysis['title_patterns'], 0, 3, true);
            
            foreach ($title_patterns as $pattern => $data) {
                if ($data['avg_ctr'] >= 3) {
                    $learned['best_patterns'][] = array(
                        'pattern' => $this->translate_pattern($pattern),
                        'avg_ctr' => $data['avg_ctr'],
                        'sample_count' => $data['count'],
                    );
                }
            }
            
            // Get bottom 3 patterns to avoid
            $worst_patterns = array_slice(array_reverse($analysis['title_patterns'], true), 0, 3, true);
            
            foreach ($worst_patterns as $pattern => $data) {
                if ($data['avg_ctr'] < 2 && $data['count'] >= 3) {
                    $learned['avoid_patterns'][] = array(
                        'pattern' => $this->translate_pattern($pattern),
                        'avg_ctr' => $data['avg_ctr'],
                        'sample_count' => $data['count'],
                    );
                }
            }
            
            // Generate recommendations
            if (!empty($learned['best_patterns'])) {
                $best = $learned['best_patterns'][0];
                $learned['recommendations'][] = "Yeni başlıklarda '{$best['pattern']}' kullanın (Ort. CTR: {$best['avg_ctr']}%)";
            }
            
            if (!empty($learned['avoid_patterns'])) {
                $worst = $learned['avoid_patterns'][0];
                $learned['recommendations'][] = "'{$worst['pattern']}' pattern'inden kaçının (Ort. CTR: {$worst['avg_ctr']}%)";
            }
            
        } catch (Throwable $e) {
            error_log('[DODO][CTR Learning] Error learning patterns: ' . $e->getMessage());
        }
        
        return $learned;
    }
    
    /**
     * Get CTR optimization suggestions for a title
     * 
     * @param string $title
     * @return array Suggestions
     */
    public function suggest_title_improvements($title) {
        $suggestions = array();
        
        try {
            $title_lower = mb_strtolower($title, 'UTF-8');
            $title_length = mb_strlen($title, 'UTF-8');
            
            // Learn from existing patterns
            $learned = $this->learn_title_patterns();
            
            // Length check
            if ($title_length < 30) {
                $suggestions[] = array(
                    'type' => 'length',
                    'severity' => 'high',
                    'message' => 'Başlık çok kısa. 50-60 karakter arası ideal.',
                );
            } elseif ($title_length > 70) {
                $suggestions[] = array(
                    'type' => 'length',
                    'severity' => 'medium',
                    'message' => 'Başlık çok uzun. SERP\'te kesilecek.',
                );
            }
            
            // Number check
            if (!preg_match('/\d+/', $title)) {
                $suggestions[] = array(
                    'type' => 'number',
                    'severity' => 'medium',
                    'message' => 'Başlığa sayı eklemek CTR\'yi artırabilir.',
                );
            }
            
            // Year check
            $current_year = date('Y');
            if (!preg_match("/{$current_year}/", $title)) {
                $suggestions[] = array(
                    'type' => 'year',
                    'severity' => 'low',
                    'message' => "Başlığa '{$current_year}' eklemek içeriği güncel gösterir.",
                );
            }
            
            // Power words check
            $power_words = array('en iyi', 'ücretsiz', 'kolay', 'hızlı', 'ultimate', 'complete');
            $has_power_word = false;
            
            foreach ($power_words as $word) {
                if (strpos($title_lower, $word) !== false) {
                    $has_power_word = true;
                    break;
                }
            }
            
            if (!$has_power_word) {
                $suggestions[] = array(
                    'type' => 'power_words',
                    'severity' => 'low',
                    'message' => 'Güçlü kelimeler eklemek dikkat çekebilir.',
                );
            }
            
        } catch (Throwable $e) {
            error_log('[DODO][CTR Learning] Error suggesting improvements: ' . $e->getMessage());
        }
        
        return $suggestions;
    }
}
