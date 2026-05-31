<?php
/**
 * Automated Insight Engine
 * 
 * Generates human-like SEO insights automatically
 * Sprint E - Feedback Loop & Self-Learning SEO System
 * 
 * @package DODO_AI_SEO
 * @since 3.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Insight_Engine {
    
    /**
     * Generate all insights
     * 
     * @return array All insights
     */
    public function generate_all_insights() {
        $insights = array(
            'growth_insights' => array(),
            'content_insights' => array(),
            'ctr_insights' => array(),
            'ranking_insights' => array(),
            'geo_insights' => array(),
            'conversion_insights' => array(),
            'strategic_insights' => array(),
        );
        
        try {
            $insights['growth_insights'] = $this->generate_growth_insights();
            $insights['content_insights'] = $this->generate_content_insights();
            $insights['ctr_insights'] = $this->generate_ctr_insights();
            $insights['ranking_insights'] = $this->generate_ranking_insights();
            $insights['geo_insights'] = $this->generate_geo_insights();
            $insights['conversion_insights'] = $this->generate_conversion_insights();
            $insights['strategic_insights'] = $this->generate_strategic_insights();
            
            // Save insights
            $this->save_insights($insights);
            
        } catch (Throwable $e) {
            error_log('[DODO][Insight Engine] Error generating insights: ' . $e->getMessage());
        }
        
        return $insights;
    }
    
    /**
     * Generate growth insights
     * 
     * @return array Growth insights
     */
    public function generate_growth_insights() {
        $insights = array();
        
        try {
            global $wpdb;
            
            // Get performance data
            $performance_data = $wpdb->get_results(
                "SELECT post_id, meta_value as score 
                FROM {$wpdb->postmeta} 
                WHERE meta_key = '_dodo_performance_score'
                ORDER BY CAST(meta_value AS UNSIGNED) DESC
                LIMIT 100"
            );
            
            if (empty($performance_data)) {
                return $insights;
            }
            
            // Calculate statistics
            $scores = array_column($performance_data, 'score');
            $avg_score = round(array_sum($scores) / count($scores));
            
            $winning = 0;
            $growing = 0;
            $declining = 0;
            
            foreach ($performance_data as $row) {
                $score = intval($row->meta_value);
                
                if ($score >= 70) {
                    $winning++;
                } elseif ($score >= 50) {
                    $growing++;
                } elseif ($score < 40) {
                    $declining++;
                }
            }
            
            $total = count($performance_data);
            $winning_pct = round(($winning / $total) * 100);
            $declining_pct = round(($declining / $total) * 100);
            
            // Generate insights
            if ($winning_pct >= 30) {
                $insights[] = array(
                    'type' => 'positive',
                    'insight' => "İçeriklerinizin %{$winning_pct}'i yüksek performans gösteriyor. Başarılı stratejinizi sürdürün.",
                    'metric' => 'winning_content',
                    'value' => $winning_pct,
                );
            }
            
            if ($declining_pct >= 20) {
                $insights[] = array(
                    'type' => 'warning',
                    'insight' => "İçeriklerinizin %{$declining_pct}'i düşük performans gösteriyor. Content refresh stratejisi uygulanmalı.",
                    'metric' => 'declining_content',
                    'value' => $declining_pct,
                );
            }
            
            if ($avg_score >= 60) {
                $insights[] = array(
                    'type' => 'positive',
                    'insight' => "Ortalama performans skoru {$avg_score}/100. Genel içerik kaliteniz yüksek.",
                    'metric' => 'avg_performance',
                    'value' => $avg_score,
                );
            } elseif ($avg_score < 50) {
                $insights[] = array(
                    'type' => 'critical',
                    'insight' => "Ortalama performans skoru {$avg_score}/100. İçerik stratejisi gözden geçirilmeli.",
                    'metric' => 'avg_performance',
                    'value' => $avg_score,
                );
            }
            
            // Time-based insights
            $recent_30_days = $this->get_recent_performance(30);
            $previous_30_days = $this->get_recent_performance(60, 30);
            
            if (!empty($recent_30_days) && !empty($previous_30_days)) {
                $recent_avg = array_sum($recent_30_days) / count($recent_30_days);
                $previous_avg = array_sum($previous_30_days) / count($previous_30_days);
                
                $change = $recent_avg - $previous_avg;
                $change_pct = round(($change / $previous_avg) * 100);
                
                if ($change_pct > 10) {
                    $insights[] = array(
                        'type' => 'positive',
                        'insight' => "Son 30 günde performans %{$change_pct} arttı. Momentum devam ediyor.",
                        'metric' => 'performance_trend',
                        'value' => $change_pct,
                    );
                } elseif ($change_pct < -10) {
                    $insights[] = array(
                        'type' => 'warning',
                        'insight' => "Son 30 günde performans %{$change_pct} düştü. Acil aksiyon gerekli.",
                        'metric' => 'performance_trend',
                        'value' => $change_pct,
                    );
                }
            }
            
        } catch (Throwable $e) {
            error_log('[DODO][Insight Engine] Error generating growth insights: ' . $e->getMessage());
        }
        
        return $insights;
    }
    
    /**
     * Generate content insights
     * 
     * @return array Content insights
     */
    public function generate_content_insights() {
        $insights = array();
        
        try {
            // Get winner patterns
            if (class_exists('DODO_Winner_Pattern_Detector')) {
                $detector = new DODO_Winner_Pattern_Detector();
                $patterns = $detector->get_saved_patterns();
                
                if (!empty($patterns)) {
                    $content_patterns = $patterns['content_patterns'] ?? array();
                    $structural_patterns = $patterns['structural_patterns'] ?? array();
                    
                    // Word count insight
                    if (isset($content_patterns['avg_word_count'])) {
                        $avg_words = $content_patterns['avg_word_count'];
                        
                        $insights[] = array(
                            'type' => 'info',
                            'insight' => "Başarılı içerikler ortalama {$avg_words} kelime. Yeni içeriklerinizi bu uzunlukta hedefleyin.",
                            'metric' => 'optimal_word_count',
                            'value' => $avg_words,
                        );
                    }
                    
                    // FAQ insight
                    if (isset($structural_patterns['faq_presence'])) {
                        $faq_pct = $structural_patterns['faq_presence'];
                        
                        if ($faq_pct >= 70) {
                            $insights[] = array(
                                'type' => 'positive',
                                'insight' => "Başarılı içeriklerin %{$faq_pct}'inde FAQ var. FAQ kullanımı kritik başarı faktörü.",
                                'metric' => 'faq_importance',
                                'value' => $faq_pct,
                            );
                        }
                    }
                    
                    // Heading structure insight
                    if (isset($structural_patterns['avg_heading_count'])) {
                        $avg_headings = round($structural_patterns['avg_heading_count']);
                        
                        $insights[] = array(
                            'type' => 'info',
                            'insight' => "Başarılı içerikler ortalama {$avg_headings} başlık kullanıyor. Yapılandırılmış içerik önemli.",
                            'metric' => 'heading_structure',
                            'value' => $avg_headings,
                        );
                    }
                }
            }
            
        } catch (Throwable $e) {
            error_log('[DODO][Insight Engine] Error generating content insights: ' . $e->getMessage());
        }
        
        return $insights;
    }
    
    /**
     * Generate CTR insights
     * 
     * @return array CTR insights
     */
    public function generate_ctr_insights() {
        $insights = array();
        
        try {
            // Get CTR learning data
            if (class_exists('DODO_CTR_Learning_Engine')) {
                $ctr_engine = new DODO_CTR_Learning_Engine();
                $learned = $ctr_engine->learn_title_patterns();
                
                if (!empty($learned['best_patterns'])) {
                    $best = $learned['best_patterns'][0];
                    
                    $insights[] = array(
                        'type' => 'positive',
                        'insight' => "'{$best['pattern']}' pattern en yüksek CTR'yi veriyor (Ort: {$best['avg_ctr']}%). Bu yaklaşımı kullanın.",
                        'metric' => 'best_ctr_pattern',
                        'value' => $best['avg_ctr'],
                    );
                }
                
                if (!empty($learned['avoid_patterns'])) {
                    $worst = $learned['avoid_patterns'][0];
                    
                    $insights[] = array(
                        'type' => 'warning',
                        'insight' => "'{$worst['pattern']}' pattern düşük CTR veriyor (Ort: {$worst['avg_ctr']}%). Bu yaklaşımdan kaçının.",
                        'metric' => 'worst_ctr_pattern',
                        'value' => $worst['avg_ctr'],
                    );
                }
            }
            
        } catch (Throwable $e) {
            error_log('[DODO][Insight Engine] Error generating CTR insights: ' . $e->getMessage());
        }
        
        return $insights;
    }
    
    /**
     * Generate ranking insights
     * 
     * @return array Ranking insights
     */
    public function generate_ranking_insights() {
        $insights = array();
        
        try {
            // Get rank tracker stats
            if (class_exists('DODO_Rank_Tracker_Engine')) {
                $tracker = new DODO_Rank_Tracker_Engine();
                $stats = $tracker->get_statistics();
                
                if (!empty($stats)) {
                    $improving = $stats['improving'] ?? 0;
                    $declining = $stats['declining'] ?? 0;
                    $total = $stats['total_tracked'] ?? 0;
                    
                    if ($total > 0) {
                        $improving_pct = round(($improving / $total) * 100);
                        $declining_pct = round(($declining / $total) * 100);
                        
                        if ($improving_pct > $declining_pct) {
                            $insights[] = array(
                                'type' => 'positive',
                                'insight' => "Keyword'lerinizin %{$improving_pct}'i yükseliyor. Ranking momentum pozitif.",
                                'metric' => 'ranking_momentum',
                                'value' => $improving_pct,
                            );
                        } elseif ($declining_pct > $improving_pct) {
                            $insights[] = array(
                                'type' => 'warning',
                                'insight' => "Keyword'lerinizin %{$declining_pct}'i düşüyor. Rekabet artıyor, içerik güncelleme gerekli.",
                                'metric' => 'ranking_decline',
                                'value' => $declining_pct,
                            );
                        }
                    }
                    
                    $avg_velocity = $stats['avg_velocity'] ?? 0;
                    
                    if ($avg_velocity > 0.5) {
                        $insights[] = array(
                            'type' => 'positive',
                            'insight' => "Ortalama ranking velocity +{$avg_velocity} pozisyon/gün. Hızlı yükseliş devam ediyor.",
                            'metric' => 'rank_velocity',
                            'value' => $avg_velocity,
                        );
                    } elseif ($avg_velocity < -0.3) {
                        $insights[] = array(
                            'type' => 'critical',
                            'insight' => "Ortalama ranking velocity {$avg_velocity} pozisyon/gün. Acil müdahale gerekli.",
                            'metric' => 'rank_velocity',
                            'value' => $avg_velocity,
                        );
                    }
                }
            }
            
        } catch (Throwable $e) {
            error_log('[DODO][Insight Engine] Error generating ranking insights: ' . $e->getMessage());
        }
        
        return $insights;
    }
    
    /**
     * Generate GEO insights
     * 
     * @return array GEO insights
     */
    public function generate_geo_insights() {
        $insights = array();
        
        try {
            // Get GEO tracker stats
            if (class_exists('DODO_GEO_Tracker_Engine')) {
                $tracker = new DODO_GEO_Tracker_Engine();
                $stats = $tracker->get_statistics();
                
                if (!empty($stats)) {
                    $excellent = $stats['excellent'] ?? 0;
                    $poor = $stats['poor'] ?? 0;
                    $total = $stats['total_tracked'] ?? 0;
                    $avg_score = $stats['avg_score'] ?? 0;
                    
                    if ($total > 0) {
                        $excellent_pct = round(($excellent / $total) * 100);
                        $poor_pct = round(($poor / $total) * 100);
                        
                        if ($excellent_pct >= 40) {
                            $insights[] = array(
                                'type' => 'positive',
                                'insight' => "İçeriklerinizin %{$excellent_pct}'i mükemmel GEO skoru. AI visibility yüksek.",
                                'metric' => 'geo_excellence',
                                'value' => $excellent_pct,
                            );
                        }
                        
                        if ($poor_pct >= 30) {
                            $insights[] = array(
                                'type' => 'warning',
                                'insight' => "İçeriklerinizin %{$poor_pct}'i düşük GEO skoru. FAQ ve yapılandırma eksik.",
                                'metric' => 'geo_weakness',
                                'value' => $poor_pct,
                            );
                        }
                    }
                    
                    if ($avg_score >= 70) {
                        $insights[] = array(
                            'type' => 'positive',
                            'insight' => "Ortalama GEO skoru {$avg_score}/100. AI answer engine'lerde görünürlük yüksek.",
                            'metric' => 'avg_geo_score',
                            'value' => $avg_score,
                        );
                    } elseif ($avg_score < 50) {
                        $insights[] = array(
                            'type' => 'critical',
                            'insight' => "Ortalama GEO skoru {$avg_score}/100. AI visibility için optimizasyon şart.",
                            'metric' => 'avg_geo_score',
                            'value' => $avg_score,
                        );
                    }
                }
            }
            
        } catch (Throwable $e) {
            error_log('[DODO][Insight Engine] Error generating GEO insights: ' . $e->getMessage());
        }
        
        return $insights;
    }
    
    /**
     * Generate conversion insights
     * 
     * @return array Conversion insights
     */
    public function generate_conversion_insights() {
        $insights = array();
        
        try {
            // Get conversion stats
            if (class_exists('DODO_Conversion_Engine')) {
                $engine = new DODO_Conversion_Engine();
                $stats = $engine->get_statistics();
                
                if (!empty($stats)) {
                    $high_conversion = $stats['high_conversion'] ?? 0;
                    $low_conversion = $stats['low_conversion'] ?? 0;
                    $total = $stats['total_analyzed'] ?? 0;
                    $avg_cta = $stats['avg_cta_quality'] ?? 0;
                    
                    if ($total > 0) {
                        $high_pct = round(($high_conversion / $total) * 100);
                        $low_pct = round(($low_conversion / $total) * 100);
                        
                        if ($high_pct >= 30) {
                            $insights[] = array(
                                'type' => 'positive',
                                'insight' => "İçeriklerinizin %{$high_pct}'i yüksek conversion potansiyeli taşıyor.",
                                'metric' => 'high_conversion',
                                'value' => $high_pct,
                            );
                        }
                        
                        if ($low_pct >= 40) {
                            $insights[] = array(
                                'type' => 'warning',
                                'insight' => "İçeriklerinizin %{$low_pct}'i düşük conversion sinyali. CTA ve ticari yapı güçlendirilmeli.",
                                'metric' => 'low_conversion',
                                'value' => $low_pct,
                            );
                        }
                    }
                    
                    if ($avg_cta < 50) {
                        $insights[] = array(
                            'type' => 'warning',
                            'insight' => "Ortalama CTA kalitesi {$avg_cta}/100. Daha fazla aksiyon çağrısı ve buton ekleyin.",
                            'metric' => 'cta_quality',
                            'value' => $avg_cta,
                        );
                    }
                }
            }
            
        } catch (Throwable $e) {
            error_log('[DODO][Insight Engine] Error generating conversion insights: ' . $e->getMessage());
        }
        
        return $insights;
    }
    
    /**
     * Generate strategic insights
     * 
     * @return array Strategic insights
     */
    public function generate_strategic_insights() {
        $insights = array();
        
        try {
            // Get refresh candidates
            if (class_exists('DODO_Refresh_Engine')) {
                $refresh_engine = new DODO_Refresh_Engine();
                $stats = $refresh_engine->get_statistics();
                
                $critical = $stats['critical'] ?? 0;
                $high = $stats['high'] ?? 0;
                
                if ($critical > 0) {
                    $insights[] = array(
                        'type' => 'critical',
                        'insight' => "{$critical} içerik kritik refresh ihtiyacı gösteriyor. Hemen güncelleme yapın.",
                        'metric' => 'critical_refresh',
                        'value' => $critical,
                    );
                }
                
                if ($high >= 10) {
                    $insights[] = array(
                        'type' => 'warning',
                        'insight' => "{$high} içerik yüksek öncelikli refresh bekliyor. Haftalık refresh planı oluşturun.",
                        'metric' => 'high_priority_refresh',
                        'value' => $high,
                    );
                }
            }
            
            // Self-learning insights
            if (class_exists('DODO_Self_Learning_Priority')) {
                $learning = new DODO_Self_Learning_Priority();
                $stats = $learning->get_statistics();
                
                $adjustments = $stats['total_adjustments'] ?? 0;
                
                if ($adjustments > 0) {
                    $insights[] = array(
                        'type' => 'info',
                        'insight' => "Sistem {$adjustments} kez priority ağırlıklarını optimize etti. Self-learning aktif.",
                        'metric' => 'learning_active',
                        'value' => $adjustments,
                    );
                }
                
                if (!empty($stats['weight_changes'])) {
                    $biggest_change = null;
                    $biggest_value = 0;
                    
                    foreach ($stats['weight_changes'] as $key => $change) {
                        if (abs($change['change']) > $biggest_value) {
                            $biggest_value = abs($change['change']);
                            $biggest_change = array('key' => $key, 'change' => $change['change']);
                        }
                    }
                    
                    if ($biggest_change) {
                        $direction = $biggest_change['change'] > 0 ? 'artırıldı' : 'azaltıldı';
                        $insights[] = array(
                            'type' => 'info',
                            'insight' => "'{$biggest_change['key']}' ağırlığı en çok değişen metrik ({$direction}).",
                            'metric' => 'biggest_weight_change',
                            'value' => $biggest_change['change'],
                        );
                    }
                }
            }
            
        } catch (Throwable $e) {
            error_log('[DODO][Insight Engine] Error generating strategic insights: ' . $e->getMessage());
        }
        
        return $insights;
    }
    
    /**
     * Get recent performance data
     * 
     * @param int $days_back
     * @param int $offset
     * @return array Performance scores
     */
    private function get_recent_performance($days_back = 30, $offset = 0) {
        global $wpdb;
        
        $start_date = date('Y-m-d', strtotime("-{$days_back} days"));
        $end_date = $offset > 0 ? date('Y-m-d', strtotime("-{$offset} days")) : date('Y-m-d');
        
        $scores = $wpdb->get_col($wpdb->prepare(
            "SELECT pm.meta_value 
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE pm.meta_key = '_dodo_performance_score'
            AND p.post_date BETWEEN %s AND %s",
            $start_date,
            $end_date
        ));
        
        return array_map('intval', $scores);
    }
    
    /**
     * Save insights
     * 
     * @param array $insights
     */
    private function save_insights($insights) {
        update_option('dodo_automated_insights', $insights, false);
        update_option('dodo_insights_generated_at', current_time('mysql'), false);
    }
    
    /**
     * Get saved insights
     * 
     * @return array Insights
     */
    public function get_saved_insights() {
        return get_option('dodo_automated_insights', array());
    }
    
    /**
     * Get top insights (most important)
     * 
     * @param int $limit
     * @return array Top insights
     */
    public function get_top_insights($limit = 5) {
        $all_insights = $this->get_saved_insights();
        $flat_insights = array();
        
        // Flatten all insights
        foreach ($all_insights as $category => $insights) {
            foreach ($insights as $insight) {
                $insight['category'] = $category;
                $flat_insights[] = $insight;
            }
        }
        
        // Sort by type priority (critical > warning > positive > info)
        $type_priority = array('critical' => 4, 'warning' => 3, 'positive' => 2, 'info' => 1);
        
        usort($flat_insights, function($a, $b) use ($type_priority) {
            $a_priority = $type_priority[$a['type']] ?? 0;
            $b_priority = $type_priority[$b['type']] ?? 0;
            
            return $b_priority <=> $a_priority;
        });
        
        return array_slice($flat_insights, 0, $limit);
    }
}
