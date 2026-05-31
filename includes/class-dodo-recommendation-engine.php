<?php
/**
 * Recommendation Engine Class
 * 
 * Generates smart, prioritized recommendations for content improvement
 *
 * @package DODO_AI_SEO
 * @since 1.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Recommendation_Engine {
    
    /**
     * Generate recommendations based on intelligence data
     * 
     * @param array $intelligence Intelligence data
     * @param string $content Post content
     * @param array $metadata Post metadata
     * @return array Array of recommendations
     */
    public function generate_recommendations($intelligence, $content, $metadata) {
        $recommendations = [];
        
        // SEO recommendations
        $recommendations = array_merge($recommendations, $this->generate_seo_recommendations($intelligence, $content, $metadata));
        
        // Quality recommendations
        $recommendations = array_merge($recommendations, $this->generate_quality_recommendations($intelligence, $content));
        
        // Readability recommendations
        $recommendations = array_merge($recommendations, $this->generate_readability_recommendations($intelligence, $content));
        
        // AI risk recommendations
        $recommendations = array_merge($recommendations, $this->generate_ai_risk_recommendations($intelligence, $content));
        
        // Prioritize recommendations
        $recommendations = $this->prioritize_recommendations($recommendations);
        
        // Return top 10
        return array_slice($recommendations, 0, 10);
    }
    
    /**
     * Generate SEO recommendations
     */
    private function generate_seo_recommendations($intelligence, $content, $metadata) {
        $recommendations = [];
        $keyword = $metadata['focus_keyword'] ?? '';
        
        // Missing keyword in title
        if (!empty($keyword) && !empty($metadata['seo_title'])) {
            if (stripos($metadata['seo_title'], $keyword) === false) {
                $recommendations[] = [
                    'id' => 'add_keyword_to_title',
                    'type' => 'critical',
                    'category' => 'seo',
                    'title' => __('Başlığa odak kelime ekleyin', 'dodo-ai-seo'),
                    'description' => __('Arama motorları başlık etiketine büyük önem verir. Odak kelimenizi başlığa ekleyin.', 'dodo-ai-seo'),
                    'impact' => 8,
                    'effort' => 'low',
                    'actionable' => true,
                ];
            }
        }
        
        // Missing meta description
        if (empty($metadata['meta_description'])) {
            $recommendations[] = [
                'id' => 'add_meta_description',
                'type' => 'critical',
                'category' => 'seo',
                'title' => __('Meta açıklama ekleyin', 'dodo-ai-seo'),
                'description' => __('Meta açıklama arama sonuçlarında görünür ve tıklama oranını etkiler.', 'dodo-ai-seo'),
                'impact' => 7,
                'effort' => 'low',
                'actionable' => true,
            ];
        }
        
        // Keyword not in first paragraph
        if (!empty($keyword)) {
            $paragraphs = $this->extract_paragraphs($content);
            if (!empty($paragraphs)) {
                $first_para = strip_tags($paragraphs[0]);
                if (stripos($first_para, $keyword) === false) {
                    $recommendations[] = [
                        'id' => 'keyword_in_first_paragraph',
                        'type' => 'important',
                        'category' => 'seo',
                        'title' => __('İlk paragrafa odak kelime ekleyin', 'dodo-ai-seo'),
                        'description' => __('Odak kelimenizi içeriğin ilk paragrafında kullanın.', 'dodo-ai-seo'),
                        'impact' => 6,
                        'effort' => 'low',
                        'actionable' => true,
                    ];
                }
            }
        }
        
        // Low keyword density
        $word_count = str_word_count(strip_tags($content));
        if (!empty($keyword) && $word_count > 0) {
            $keyword_count = substr_count(strtolower(strip_tags($content)), strtolower($keyword));
            $density = ($keyword_count / $word_count) * 100;
            
            if ($density < 0.5) {
                $recommendations[] = [
                    'id' => 'increase_keyword_density',
                    'type' => 'suggested',
                    'category' => 'seo',
                    'title' => __('Anahtar kelime yoğunluğunu artırın', 'dodo-ai-seo'),
                    'description' => __('Odak kelimenizi içerikte daha fazla kullanın (hedef: %1-2).', 'dodo-ai-seo'),
                    'impact' => 4,
                    'effort' => 'medium',
                    'actionable' => false,
                ];
            }
        }
        
        return $recommendations;
    }
    
    /**
     * Generate quality recommendations
     */
    private function generate_quality_recommendations($intelligence, $content) {
        $recommendations = [];
        $word_count = str_word_count(strip_tags($content));
        
        // Content too short
        if ($word_count < 500) {
            $recommendations[] = [
                'id' => 'increase_word_count',
                'type' => 'critical',
                'category' => 'content',
                'title' => __('İçeriği genişletin', 'dodo-ai-seo'),
                'description' => sprintf(__('İçerik çok kısa (%d kelime). En az 500 kelime hedefleyin.', 'dodo-ai-seo'), $word_count),
                'impact' => 9,
                'effort' => 'high',
                'actionable' => false,
            ];
        }
        
        // Few headings
        $h2_count = substr_count($content, '<h2');
        if ($h2_count < 3) {
            $recommendations[] = [
                'id' => 'add_more_headings',
                'type' => 'important',
                'category' => 'content',
                'title' => __('Daha fazla başlık ekleyin', 'dodo-ai-seo'),
                'description' => __('İçeriğinizi H2 başlıklarıyla bölümlere ayırın (en az 3 adet).', 'dodo-ai-seo'),
                'impact' => 6,
                'effort' => 'medium',
                'actionable' => false,
            ];
        }
        
        // No FAQ
        if (!$this->has_faq($content)) {
            $recommendations[] = [
                'id' => 'add_faq_section',
                'type' => 'suggested',
                'category' => 'content',
                'title' => __('SSS bölümü ekleyin', 'dodo-ai-seo'),
                'description' => __('Sık sorulan sorular bölümü SEO ve kullanıcı deneyimi için faydalıdır.', 'dodo-ai-seo'),
                'impact' => 5,
                'effort' => 'medium',
                'actionable' => false,
            ];
        }
        
        // No CTA
        if (!$this->has_cta($content)) {
            $recommendations[] = [
                'id' => 'add_cta',
                'type' => 'suggested',
                'category' => 'content',
                'title' => __('Harekete geçirici mesaj ekleyin', 'dodo-ai-seo'),
                'description' => __('İçeriğinize CTA (Call-to-Action) ekleyerek dönüşüm oranını artırın.', 'dodo-ai-seo'),
                'impact' => 4,
                'effort' => 'low',
                'actionable' => false,
            ];
        }
        
        return $recommendations;
    }
    
    /**
     * Generate readability recommendations
     */
    private function generate_readability_recommendations($intelligence, $content) {
        $recommendations = [];
        
        // Poor readability score
        if (isset($intelligence['readability_score']) && $intelligence['readability_score'] < 60) {
            $recommendations[] = [
                'id' => 'improve_readability',
                'type' => 'important',
                'category' => 'readability',
                'title' => __('Okunabilirliği iyileştirin', 'dodo-ai-seo'),
                'description' => __('Cümleleri kısaltın, karmaşık kelimeleri azaltın, geçiş kelimelerini kullanın.', 'dodo-ai-seo'),
                'impact' => 7,
                'effort' => 'medium',
                'actionable' => false,
            ];
        }
        
        // Check sentence length
        $sentences = $this->extract_sentences(strip_tags($content));
        if (!empty($sentences)) {
            $total_words = 0;
            foreach ($sentences as $sentence) {
                $total_words += str_word_count($sentence);
            }
            $avg_length = $total_words / count($sentences);
            
            if ($avg_length > 25) {
                $recommendations[] = [
                    'id' => 'shorten_sentences',
                    'type' => 'suggested',
                    'category' => 'readability',
                    'title' => __('Cümleleri kısaltın', 'dodo-ai-seo'),
                    'description' => sprintf(__('Ortalama cümle uzunluğu %d kelime. 15-20 kelime hedefleyin.', 'dodo-ai-seo'), round($avg_length)),
                    'impact' => 5,
                    'effort' => 'medium',
                    'actionable' => false,
                ];
            }
        }
        
        return $recommendations;
    }
    
    /**
     * Generate AI risk recommendations
     */
    private function generate_ai_risk_recommendations($intelligence, $content) {
        $recommendations = [];
        
        // High AI risk
        if (isset($intelligence['ai_risk_score']) && $intelligence['ai_risk_score'] > 60) {
            $recommendations[] = [
                'id' => 'reduce_ai_patterns',
                'type' => 'critical',
                'category' => 'ai-risk',
                'title' => __('AI kalıplarını azaltın', 'dodo-ai-seo'),
                'description' => __('İçerik AI tarafından üretilmiş görünüyor. Kişisel dokunuşlar ekleyin, geçiş kelimelerini çeşitlendirin.', 'dodo-ai-seo'),
                'impact' => 8,
                'effort' => 'high',
                'actionable' => false,
            ];
        }
        
        return $recommendations;
    }
    
    /**
     * Prioritize recommendations
     */
    private function prioritize_recommendations($recommendations) {
        foreach ($recommendations as &$rec) {
            // Calculate priority score
            $impact = $rec['impact'];
            $effort_score = ['low' => 10, 'medium' => 5, 'high' => 2];
            $effort = $effort_score[$rec['effort']] ?? 5;
            
            $rec['priority'] = ($impact * 2) + $effort;
        }
        
        // Sort by priority (descending)
        usort($recommendations, function($a, $b) {
            return $b['priority'] - $a['priority'];
        });
        
        return $recommendations;
    }
    
    // Helper methods
    private function extract_paragraphs($content) {
        preg_match_all('/<p[^>]*>(.*?)<\/p>/is', $content, $matches);
        return array_filter($matches[1], function($p) {
            return !empty(trim(strip_tags($p)));
        });
    }
    
    private function extract_sentences($text) {
        return preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
    }
    
    private function has_faq($content) {
        $indicators = ['faq', 'sık sorulan', 'frequently asked'];
        foreach ($indicators as $indicator) {
            if (stripos($content, $indicator) !== false) {
                return true;
            }
        }
        return false;
    }
    
    private function has_cta($content) {
        $indicators = ['hemen', 'şimdi', 'tıkla', 'başla', 'kayıt ol'];
        $count = 0;
        foreach ($indicators as $indicator) {
            if (stripos(strtolower($content), $indicator) !== false) {
                $count++;
            }
        }
        return $count >= 2;
    }
}
