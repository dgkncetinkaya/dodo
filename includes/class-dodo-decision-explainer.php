<?php
/**
 * Editorial Decision Explainer Class
 * 
 * Explains why AI made specific recommendations
 * Metric-based reasoning, not generic AI explanations
 *
 * @package DODO_AI_SEO
 * @since 1.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Decision_Explainer {
    
    /**
     * Generate explanations for recommendations
     * 
     * @param array $recommendations Recommendations list
     * @param array $intelligence Intelligence data
     * @return array Recommendations with explanations
     */
    public function add_explanations($recommendations, $intelligence) {
        foreach ($recommendations as &$rec) {
            $rec['explanation'] = $this->generate_explanation($rec, $intelligence);
        }
        
        return $recommendations;
    }
    
    /**
     * Generate explanation for a recommendation
     */
    private function generate_explanation($rec, $intelligence) {
        $explanation = '';
        
        // SEO recommendations
        if ($rec['category'] === 'seo') {
            $explanation = $this->explain_seo_recommendation($rec, $intelligence);
        }
        
        // Content recommendations
        elseif ($rec['category'] === 'content') {
            $explanation = $this->explain_content_recommendation($rec, $intelligence);
        }
        
        // Readability recommendations
        elseif ($rec['category'] === 'readability') {
            $explanation = $this->explain_readability_recommendation($rec, $intelligence);
        }
        
        // AI risk recommendations
        elseif ($rec['category'] === 'ai-risk') {
            $explanation = $this->explain_ai_risk_recommendation($rec, $intelligence);
        }
        
        return $explanation;
    }
    
    /**
     * Explain SEO recommendation
     */
    private function explain_seo_recommendation($rec, $intelligence) {
        $explanations = [];
        
        // Keyword in title
        if (strpos($rec['id'], 'keyword') !== false && strpos($rec['id'], 'title') !== false) {
            $explanations[] = sprintf(
                __('SEO skoru %d. Başlıkta odak kelime kullanımı arama motoru sıralamasını doğrudan etkiler.', 'dodo-ai-seo'),
                $intelligence['seo_score']
            );
        }
        
        // Meta description
        if (strpos($rec['id'], 'meta_description') !== false) {
            $explanations[] = sprintf(
                __('Meta açıklama eksik. Bu, tıklama oranını %%15-20 oranında düşürebilir.', 'dodo-ai-seo')
            );
        }
        
        // Keyword density
        if (strpos($rec['id'], 'keyword_density') !== false || strpos($rec['id'], 'keyword') !== false) {
            if (isset($intelligence['quick_stats']['keyword_density'])) {
                $density = $intelligence['quick_stats']['keyword_density'];
                $explanations[] = sprintf(
                    __('Mevcut keyword yoğunluğu %%%s. İdeal aralık %%1-2 arası.', 'dodo-ai-seo'),
                    number_format($density, 1)
                );
            }
        }
        
        return implode(' ', $explanations);
    }
    
    /**
     * Explain content recommendation
     */
    private function explain_content_recommendation($rec, $intelligence) {
        $explanations = [];
        
        // Word count
        if (strpos($rec['id'], 'word_count') !== false) {
            if (isset($intelligence['quick_stats']['word_count'])) {
                $word_count = $intelligence['quick_stats']['word_count'];
                $explanations[] = sprintf(
                    __('İçerik %d kelime. Kapsamlı içerikler (1000+ kelime) arama motorlarında daha iyi sıralanır.', 'dodo-ai-seo'),
                    $word_count
                );
            }
        }
        
        // Headings
        if (strpos($rec['id'], 'heading') !== false) {
            $explanations[] = sprintf(
                __('Kalite skoru %d. Başlıklar içeriği yapılandırır ve taranabilirliği artırır.', 'dodo-ai-seo'),
                $intelligence['quality_score']
            );
        }
        
        // FAQ
        if (strpos($rec['id'], 'faq') !== false) {
            $explanations[] = __('SSS bölümü featured snippet kazanma şansını artırır ve kullanıcı sorularını yanıtlar.', 'dodo-ai-seo');
        }
        
        // CTA
        if (strpos($rec['id'], 'cta') !== false) {
            $explanations[] = __('Harekete geçirici mesaj eksikliği dönüşüm oranını düşürür.', 'dodo-ai-seo');
        }
        
        return implode(' ', $explanations);
    }
    
    /**
     * Explain readability recommendation
     */
    private function explain_readability_recommendation($rec, $intelligence) {
        $explanations[] = sprintf(
            __('Okunabilirlik skoru %d.', 'dodo-ai-seo'),
            $intelligence['readability_score']
        );
        
        // Sentence length
        if (strpos($rec['id'], 'sentence') !== false) {
            $explanations[] = __('Uzun cümleler okuyucunun dikkatini dağıtır ve anlamayı zorlaştırır.', 'dodo-ai-seo');
        }
        
        // Overall readability
        if (strpos($rec['id'], 'readability') !== false) {
            if ($intelligence['readability_score'] < 60) {
                $explanations[] = __('Karmaşık cümleler, az geçiş kelimesi ve uzun paragraflar okunabilirliği düşürüyor.', 'dodo-ai-seo');
            }
        }
        
        return implode(' ', $explanations);
    }
    
    /**
     * Explain AI risk recommendation
     */
    private function explain_ai_risk_recommendation($rec, $intelligence) {
        $explanations = [];
        
        if (isset($intelligence['ai_detection'])) {
            $ai = $intelligence['ai_detection'];
            
            $explanations[] = sprintf(
                __('AI benzerlik skoru %d.', 'dodo-ai-seo'),
                $ai['ai_similarity_score']
            );
            
            // Repetitive structures
            if ($ai['repetitive_structures'] > 50) {
                $explanations[] = __('Tekrarlayan cümle yapıları robotik his veriyor.', 'dodo-ai-seo');
            }
            
            // Generic transitions
            if ($ai['generic_transitions'] > 50) {
                $explanations[] = __('Jenerik geçiş kalıpları AI tarafından üretilmiş izlenimi yaratıyor.', 'dodo-ai-seo');
            }
            
            // Robotic flow
            if ($ai['robotic_flow'] > 50) {
                $explanations[] = __('Mekanik akış ve tutarlı paragraf yapısı doğallığı azaltıyor.', 'dodo-ai-seo');
            }
            
            // Repetitive openings
            if ($ai['repetitive_openings'] > 50) {
                $explanations[] = __('Cümle başlangıçları çok benzer.', 'dodo-ai-seo');
            }
        }
        
        return implode(' ', $explanations);
    }
    
    /**
     * Generate score change explanation
     * 
     * @param string $metric Metric name
     * @param int $before Before score
     * @param int $after After score
     * @return string Explanation
     */
    public function explain_score_change($metric, $before, $after) {
        $change = $after - $before;
        $change_percent = $before > 0 ? round(($change / $before) * 100) : 0;
        
        $explanations = [];
        
        if ($change > 0) {
            $explanations[] = sprintf(
                __('%s skoru %d puanından %d puana yükseldi (%%%d artış).', 'dodo-ai-seo'),
                $this->get_metric_label($metric),
                $before,
                $after,
                abs($change_percent)
            );
            
            $explanations[] = $this->explain_improvement_reason($metric, $change);
        } else {
            $explanations[] = sprintf(
                __('%s skoru %d puanından %d puana düştü (%%%d azalma).', 'dodo-ai-seo'),
                $this->get_metric_label($metric),
                $before,
                $after,
                abs($change_percent)
            );
            
            $explanations[] = $this->explain_decline_reason($metric, abs($change));
        }
        
        return implode(' ', $explanations);
    }
    
    /**
     * Explain improvement reason
     */
    private function explain_improvement_reason($metric, $change) {
        $reasons = [
            'seo' => __('Keyword kullanımı ve meta veriler optimize edildi.', 'dodo-ai-seo'),
            'quality' => __('İçerik yapısı ve detay seviyesi geliştirildi.', 'dodo-ai-seo'),
            'readability' => __('Cümle uzunlukları ve paragraf yapısı iyileştirildi.', 'dodo-ai-seo'),
            'semantic' => __('Semantik tutarlılık ve bağlam güçlendirildi.', 'dodo-ai-seo'),
            'ai_risk' => __('AI kalıpları azaltıldı ve doğal akış sağlandı.', 'dodo-ai-seo'),
        ];
        
        return $reasons[$metric] ?? __('İyileştirmeler uygulandı.', 'dodo-ai-seo');
    }
    
    /**
     * Explain decline reason
     */
    private function explain_decline_reason($metric, $change) {
        $reasons = [
            'seo' => __('Keyword yoğunluğu veya meta veri kalitesi düştü.', 'dodo-ai-seo'),
            'quality' => __('İçerik yapısı veya detay seviyesi azaldı.', 'dodo-ai-seo'),
            'readability' => __('Cümle karmaşıklığı arttı.', 'dodo-ai-seo'),
            'semantic' => __('Semantik tutarlılık zayıfladı.', 'dodo-ai-seo'),
            'ai_risk' => __('AI benzeri kalıplar arttı.', 'dodo-ai-seo'),
        ];
        
        return $reasons[$metric] ?? __('Metrik değeri düştü.', 'dodo-ai-seo');
    }
    
    /**
     * Get metric label
     */
    private function get_metric_label($metric) {
        $labels = [
            'health' => __('Sağlık', 'dodo-ai-seo'),
            'seo' => __('SEO', 'dodo-ai-seo'),
            'quality' => __('Kalite', 'dodo-ai-seo'),
            'readability' => __('Okunabilirlik', 'dodo-ai-seo'),
            'semantic' => __('Semantik', 'dodo-ai-seo'),
            'ai_risk' => __('AI Risk', 'dodo-ai-seo'),
            'confidence' => __('Güven', 'dodo-ai-seo'),
        ];
        
        return $labels[$metric] ?? $metric;
    }
}
