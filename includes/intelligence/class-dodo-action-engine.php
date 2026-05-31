<?php
/**
 * Action Engine
 * 
 * Generates specific, actionable SEO recommendations
 * Phase 3 - SEO Strategist Intelligence
 * 
 * @package DODO_AI_SEO
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Action_Engine {
    
    /**
     * Generate actionable recommendations for an opportunity
     * 
     * @param array $opportunity Opportunity data
     * @return array Actions
     */
    public function generate_actions($opportunity) {
        $query = $opportunity['query'] ?? '';
        $source = $opportunity['source'] ?? 'unknown';
        $position = $opportunity['position'] ?? $opportunity['current_position'] ?? 999;
        $ctr = $opportunity['ctr'] ?? 0;
        $impressions = $opportunity['impressions'] ?? 0;
        
        $actions = array();
        
        // Source-specific actions
        switch ($source) {
            case 'gsc_ctr':
                $actions = $this->get_ctr_actions($query, $position, $ctr, $impressions);
                break;
                
            case 'gsc_ranking':
                $actions = $this->get_ranking_actions($query, $position, $impressions);
                break;
                
            case 'gsc_decay':
                $actions = $this->get_decay_actions($opportunity);
                break;
                
            case 'gsc_cannibalization':
                $actions = $this->get_cannibalization_actions($opportunity);
                break;
                
            case 'gsc_gap':
                $actions = $this->get_gap_actions($opportunity);
                break;
                
            default:
                $actions = $this->get_generic_actions($query, $position);
        }
        
        // Add effort and impact estimates
        foreach ($actions as &$action) {
            if (!isset($action['effort'])) {
                $action['effort'] = $this->estimate_effort($action['type']);
            }
            if (!isset($action['impact'])) {
                $action['impact'] = $this->estimate_impact($action['type'], $impressions);
            }
        }
        
        return $actions;
    }
    
    /**
     * Get CTR improvement actions
     */
    private function get_ctr_actions($query, $position, $ctr, $impressions) {
        $actions = array();
        
        // Title optimization
        $actions[] = array(
            'type' => 'title_rewrite',
            'action' => 'Title\'ı yeniden yaz',
            'details' => 'Keyword başa al, sayı ekle, güçlü sıfat kullan (en iyi, ücretsiz, 2024)',
            'example' => "Örnek: \"{$query} - 2024 Güncel Rehber (Ücretsiz)\"",
            'priority' => 'high',
        );
        
        // Meta description
        $actions[] = array(
            'type' => 'meta_description',
            'action' => 'Meta description optimize et',
            'details' => 'CTA ekle, fayda vurgula, 150-160 karakter',
            'example' => "Örnek: \"{$query} hakkında bilmeniz gereken her şey. Adım adım rehber + örnekler. Hemen öğren!\"",
            'priority' => 'high',
        );
        
        // Featured snippet
        if ($position <= 5) {
            $actions[] = array(
                'type' => 'featured_snippet',
                'action' => 'Featured snippet için optimize et',
                'details' => 'İlk paragrafta kısa, net cevap ver (40-60 kelime)',
                'priority' => 'high',
            );
        }
        
        // FAQ
        $actions[] = array(
            'type' => 'faq_schema',
            'action' => 'FAQ schema markup ekle',
            'details' => 'İlgili 5-10 soru-cevap ekle, schema markup kullan',
            'priority' => 'medium',
        );
        
        return $actions;
    }
    
    /**
     * Get ranking improvement actions
     */
    private function get_ranking_actions($query, $position, $impressions) {
        $actions = array();
        
        if ($position <= 10) {
            // Close to first page
            $actions[] = array(
                'type' => 'content_depth',
                'action' => 'İçeriği derinleştir',
                'details' => 'En az 500 kelime ekle, alt başlıklar ekle, örnekler ver',
                'priority' => 'high',
            );
            
            $actions[] = array(
                'type' => 'internal_links',
                'action' => 'Internal link güçlendir',
                'details' => '3-5 ilgili içerikten bu sayfaya link ver',
                'priority' => 'high',
            );
            
        } elseif ($position <= 20) {
            // Second page
            $actions[] = array(
                'type' => 'content_rewrite',
                'action' => 'İçeriği yeniden yaz',
                'details' => 'Rakip analizi yap, eksik konuları ekle, yapıyı iyileştir',
                'priority' => 'high',
            );
            
            $actions[] = array(
                'type' => 'semantic_keywords',
                'action' => 'Semantic keyword coverage artır',
                'details' => 'LSI keywords ekle, related topics genişlet',
                'priority' => 'medium',
            );
            
        } else {
            // Beyond page 2
            $actions[] = array(
                'type' => 'new_content',
                'action' => 'Yeni içerik oluştur',
                'details' => 'Mevcut içerik yetersiz, sıfırdan kapsamlı içerik yaz',
                'priority' => 'high',
            );
        }
        
        // E-A-T signals
        $actions[] = array(
            'type' => 'eat_signals',
            'action' => 'E-A-T sinyallerini güçlendir',
            'details' => 'Yazar bilgisi ekle, kaynak göster, güncel tarih ekle',
            'priority' => 'medium',
        );
        
        return $actions;
    }
    
    /**
     * Get decay recovery actions
     */
    private function get_decay_actions($opportunity) {
        $decay_type = $opportunity['decay_type'] ?? 'general_decay';
        $actions = array();
        
        switch ($decay_type) {
            case 'ranking_drop':
                $actions[] = array(
                    'type' => 'competitor_analysis',
                    'action' => 'Rakip analizi yap',
                    'details' => 'Top 3 rakibi incele, eksik konuları tespit et',
                    'priority' => 'high',
                );
                $actions[] = array(
                    'type' => 'content_update',
                    'action' => 'İçeriği güncelle ve genişlet',
                    'details' => 'Yeni bilgiler ekle, eski bilgileri güncelle, 30%+ içerik ekle',
                    'priority' => 'high',
                );
                break;
                
            case 'ctr_drop':
                $actions[] = array(
                    'type' => 'title_refresh',
                    'action' => 'Title ve meta description yenile',
                    'details' => 'Güncel tarih ekle (2024), yeni angle dene',
                    'priority' => 'high',
                );
                break;
                
            case 'search_volume_drop':
                $actions[] = array(
                    'type' => 'keyword_expansion',
                    'action' => 'Alternatif keywordler ekle',
                    'details' => 'Related queries ekle, semantic coverage genişlet',
                    'priority' => 'medium',
                );
                break;
                
            default:
                $actions[] = array(
                    'type' => 'full_refresh',
                    'action' => 'İçeriği tamamen yenile',
                    'details' => 'Tarih güncelle, yeni bölümler ekle, görselleri yenile',
                    'priority' => 'high',
                );
        }
        
        return $actions;
    }
    
    /**
     * Get cannibalization fix actions
     */
    private function get_cannibalization_actions($opportunity) {
        $severity = $opportunity['severity'] ?? 'medium';
        $actions = array();
        
        if ($severity === 'high') {
            $actions[] = array(
                'type' => 'canonical_tag',
                'action' => 'Canonical tag kullan',
                'details' => 'Primary page belirle, diğer sayfalardan canonical ver',
                'priority' => 'high',
            );
            
            $actions[] = array(
                'type' => 'content_merge',
                'action' => 'Sayfaları birleştir',
                'details' => 'İki sayfayı tek güçlü sayfada birleştir, 301 redirect yap',
                'priority' => 'high',
            );
        } else {
            $actions[] = array(
                'type' => 'intent_differentiation',
                'action' => 'Intent farklılaştır',
                'details' => 'Her sayfayı farklı intent\'e odakla (nasıl vs nedir)',
                'priority' => 'medium',
            );
            
            $actions[] = array(
                'type' => 'internal_link_structure',
                'action' => 'Internal link yapısını düzenle',
                'details' => 'Primary page\'e daha fazla internal link ver',
                'priority' => 'medium',
            );
        }
        
        return $actions;
    }
    
    /**
     * Get content gap actions
     */
    private function get_gap_actions($opportunity) {
        $content_type = $opportunity['recommended_content_type'] ?? 'informational article';
        
        $actions = array();
        
        $actions[] = array(
            'type' => 'new_content_creation',
            'action' => 'Yeni içerik oluştur',
            'details' => "Önerilen format: {$content_type}",
            'priority' => 'high',
        );
        
        $actions[] = array(
            'type' => 'keyword_targeting',
            'action' => 'Keyword targeting optimize et',
            'details' => 'Title, H1, ilk paragraf ve URL\'de keyword kullan',
            'priority' => 'high',
        );
        
        return $actions;
    }
    
    /**
     * Get generic actions
     */
    private function get_generic_actions($query, $position) {
        return array(
            array(
                'type' => 'content_optimization',
                'action' => 'İçeriği optimize et',
                'details' => 'Keyword density kontrol et, semantic keywords ekle',
                'priority' => 'medium',
            ),
            array(
                'type' => 'technical_seo',
                'action' => 'Technical SEO kontrol et',
                'details' => 'Page speed, mobile-friendly, schema markup kontrol et',
                'priority' => 'low',
            ),
        );
    }
    
    /**
     * Estimate effort for action type
     */
    private function estimate_effort($action_type) {
        $effort_map = array(
            'title_rewrite' => 'low',
            'meta_description' => 'low',
            'faq_schema' => 'low',
            'featured_snippet' => 'low',
            'content_depth' => 'medium',
            'internal_links' => 'medium',
            'semantic_keywords' => 'medium',
            'eat_signals' => 'medium',
            'content_update' => 'medium',
            'title_refresh' => 'low',
            'keyword_expansion' => 'medium',
            'content_rewrite' => 'high',
            'new_content' => 'high',
            'competitor_analysis' => 'medium',
            'full_refresh' => 'high',
            'canonical_tag' => 'low',
            'content_merge' => 'high',
            'intent_differentiation' => 'high',
        );
        
        return $effort_map[$action_type] ?? 'medium';
    }
    
    /**
     * Estimate impact for action type
     */
    private function estimate_impact($action_type, $impressions) {
        $base_impact = array(
            'title_rewrite' => 60,
            'meta_description' => 50,
            'featured_snippet' => 80,
            'content_depth' => 70,
            'internal_links' => 60,
            'content_rewrite' => 85,
            'new_content' => 90,
            'canonical_tag' => 75,
            'content_merge' => 80,
        );
        
        $impact_score = $base_impact[$action_type] ?? 50;
        
        // Adjust by traffic potential
        if ($impressions > 1000) {
            $impact_score += 10;
        } elseif ($impressions < 100) {
            $impact_score -= 10;
        }
        
        $impact_score = max(0, min(100, $impact_score));
        
        if ($impact_score >= 75) {
            return 'high';
        } elseif ($impact_score >= 50) {
            return 'medium';
        } else {
            return 'low';
        }
    }
}
