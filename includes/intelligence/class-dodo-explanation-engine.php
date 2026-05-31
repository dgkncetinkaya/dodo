<?php
/**
 * Explanation Engine
 * 
 * Generates human-like reasoning and explanations for SEO recommendations
 * Phase 3 - SEO Strategist Intelligence
 * 
 * @package DODO_AI_SEO
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Explanation_Engine {
    
    /**
     * Generate explanation for an opportunity
     * 
     * @param array $opportunity Opportunity data
     * @return string Human-readable explanation
     */
    public function explain_opportunity($opportunity) {
        $source = $opportunity['source'] ?? 'unknown';
        $query = $opportunity['query'] ?? '';
        
        switch ($source) {
            case 'gsc_ctr':
                return $this->explain_ctr_opportunity($opportunity);
                
            case 'gsc_ranking':
                return $this->explain_ranking_opportunity($opportunity);
                
            case 'gsc_decay':
                return $this->explain_decay_opportunity($opportunity);
                
            case 'gsc_cannibalization':
                return $this->explain_cannibalization_opportunity($opportunity);
                
            case 'gsc_gap':
                return $this->explain_gap_opportunity($opportunity);
                
            default:
                return $this->explain_generic_opportunity($opportunity);
        }
    }
    
    /**
     * Explain CTR opportunity
     */
    private function explain_ctr_opportunity($opp) {
        $query = $opp['query'];
        $position = $opp['position'] ?? 0;
        $ctr = $opp['ctr'] ?? 0;
        $impressions = $opp['impressions'] ?? 0;
        
        $explanation = "**Neden fırsat?**\n\n";
        
        $explanation .= "Bu keyword için Google'da **{$position}. sıradasınız** ve **{$impressions} gösterim** alıyorsunuz. ";
        $explanation .= "Ancak tıklama oranınız sadece **%{$ctr}** - bu pozisyon için beklenen oranın çok altında.\n\n";
        
        $explanation .= "**Ne anlama geliyor?**\n\n";
        $explanation .= "İnsanlar aramalarda içeriğinizi görüyor ama tıklamıyor. Bu genellikle:\n";
        $explanation .= "- Title'ınız yeterince çekici değil\n";
        $explanation .= "- Meta description rakiplerden daha zayıf\n";
        $explanation .= "- Featured snippet'i rakipler almış\n\n";
        
        $explanation .= "**Potansiyel kazanç:**\n\n";
        $potential_clicks = round($impressions * 0.05); // 5% CTR hedefi
        $explanation .= "CTR'yi %5'e çıkarırsanız, ayda **~{$potential_clicks} ek tıklama** kazanabilirsiniz. ";
        $explanation .= "Bu, düşük eforla yüksek kazanç demek! 🎯";
        
        return $explanation;
    }
    
    /**
     * Explain ranking opportunity
     */
    private function explain_ranking_opportunity($opp) {
        $query = $opp['query'];
        $position = $opp['current_position'] ?? $opp['position'] ?? 0;
        $target = $opp['target_position'] ?? 3;
        $impressions = $opp['impressions'] ?? 0;
        
        $explanation = "**Neden fırsat?**\n\n";
        
        if ($position <= 10) {
            $explanation .= "Bu keyword için **{$position}. sıradasınız** - ilk sayfada ama üst sıralarda değil. ";
            $explanation .= "Küçük iyileştirmelerle **top 3'e** çıkabilirsiniz.\n\n";
        } elseif ($position <= 20) {
            $explanation .= "Bu keyword için **{$position}. sıradasınız** - 2. sayfada. ";
            $explanation .= "Orta seviye çalışmayla **ilk sayfaya** çıkabilirsiniz.\n\n";
        } else {
            $explanation .= "Bu keyword için **{$position}. sıradasınız**. ";
            $explanation .= "Kapsamlı içerik çalışmasıyla **ilk sayfaya** çıkma potansiyeli var.\n\n";
        }
        
        $explanation .= "**Ne yapmalı?**\n\n";
        
        if ($position <= 10) {
            $explanation .= "İlk sayfadasınız, bu iyi! Şimdi yapmanız gerekenler:\n";
            $explanation .= "- İçeriği 30-50% derinleştirin\n";
            $explanation .= "- 3-5 internal link ekleyin\n";
            $explanation .= "- E-A-T sinyallerini güçlendirin\n\n";
        } else {
            $explanation .= "Daha büyük adımlar gerekiyor:\n";
            $explanation .= "- Rakip analizi yapın (top 3)\n";
            $explanation .= "- Eksik konuları tespit edin\n";
            $explanation .= "- İçeriği yeniden yapılandırın\n\n";
        }
        
        $explanation .= "**Potansiyel kazanç:**\n\n";
        $explanation .= "Top 3'e çıkarsanız, trafik **3-5x artabilir**. ";
        $explanation .= "Bu keyword'den ayda **{$impressions} gösterim** alıyorsunuz - potansiyel çok yüksek! 🚀";
        
        return $explanation;
    }
    
    /**
     * Explain decay opportunity
     */
    private function explain_decay_opportunity($opp) {
        $query = $opp['query'] ?? $opp['page'] ?? '';
        $decay_type = $opp['decay_type'] ?? 'general_decay';
        $impression_change = $opp['impression_change_pct'] ?? 0;
        $position_change = $opp['position_change'] ?? 0;
        
        $explanation = "**⚠️ Düşüş tespit edildi!**\n\n";
        
        $explanation .= "Bu içerik son dönemde performans kaybediyor:\n";
        $explanation .= "- Gösterimler: **{$impression_change}%** düşüş\n";
        $explanation .= "- Sıralama: **{$position_change}** pozisyon kayıp\n\n";
        
        $explanation .= "**Neden düşüyor?**\n\n";
        
        switch ($decay_type) {
            case 'ranking_drop':
                $explanation .= "Rakipler güçlenmiş veya algoritma değişikliği olmuş. ";
                $explanation .= "İçeriğiniz artık yeterince rekabetçi değil.\n\n";
                break;
                
            case 'search_volume_drop':
                $explanation .= "Arama hacmi düşmüş - mevsimsel olabilir veya trend değişmiş. ";
                $explanation .= "Alternatif keywordlere odaklanmalısınız.\n\n";
                break;
                
            case 'ctr_drop':
                $explanation .= "İnsanlar aramalarda görüyor ama tıklamıyor. ";
                $explanation .= "Title/description çekiciliğini kaybetmiş.\n\n";
                break;
                
            default:
                $explanation .= "İçerik güncelliğini kaybetmiş. ";
                $explanation .= "Yeni bilgiler eklenmeli, eski bilgiler güncellenmeli.\n\n";
        }
        
        $explanation .= "**Acil aksiyon:**\n\n";
        $explanation .= "Bu düşüşü durdurmak için hemen harekete geçin! ";
        $explanation .= "Her gün beklemek daha fazla trafik kaybı demek. ";
        $explanation .= "Önerilen aksiyonları öncelikle uygulayın. ⚡";
        
        return $explanation;
    }
    
    /**
     * Explain cannibalization opportunity
     */
    private function explain_cannibalization_opportunity($opp) {
        $query = $opp['query'];
        $competing_pages = $opp['competing_pages'] ?? 2;
        $severity = $opp['severity'] ?? 'medium';
        
        $explanation = "**⚠️ Kannibalizasyon tespit edildi!**\n\n";
        
        $explanation .= "**{$competing_pages} farklı sayfa** aynı keyword için yarışıyor: \"{$query}\"\n\n";
        
        $explanation .= "**Neden sorun?**\n\n";
        $explanation .= "Google hangi sayfanızı göstereceğini karıştırıyor. Sonuç:\n";
        $explanation .= "- Hiçbir sayfa yeterince güçlenemiyor\n";
        $explanation .= "- Link equity bölünüyor\n";
        $explanation .= "- Potansiyel trafik kaybediliyor\n\n";
        
        if ($severity === 'high') {
            $explanation .= "**Ciddiyet: YÜKSEK** 🔴\n\n";
            $explanation .= "Sayfalar çok benzer performans gösteriyor. Acil müdahale gerekli:\n";
            $explanation .= "- Sayfaları birleştirin VEYA\n";
            $explanation .= "- Canonical tag kullanın\n\n";
        } else {
            $explanation .= "**Ciddiyet: ORTA** 🟡\n\n";
            $explanation .= "Henüz kritik değil ama önlem alın:\n";
            $explanation .= "- Intent farklılaştırın\n";
            $explanation .= "- Internal link yapısını düzenleyin\n\n";
        }
        
        $explanation .= "**Potansiyel kazanç:**\n\n";
        $explanation .= "Kannibalizasyonu çözerseniz, tek güçlü sayfa **2-3x daha iyi** performans gösterebilir. ";
        $explanation .= "Dağılan gücü birleştirin! 💪";
        
        return $explanation;
    }
    
    /**
     * Explain gap opportunity
     */
    private function explain_gap_opportunity($opp) {
        $query = $opp['query'];
        $impressions = $opp['impressions'] ?? 0;
        $content_type = $opp['recommended_content_type'] ?? 'informational article';
        
        $explanation = "**💡 İçerik boşluğu tespit edildi!**\n\n";
        
        $explanation .= "Bu keyword için **{$impressions} gösterim** alıyorsunuz ama içeriğiniz yeterince güçlü değil.\n\n";
        
        $explanation .= "**Ne anlama geliyor?**\n\n";
        $explanation .= "Google sizi gösteriyor çünkü başka seçenek yok - ama kullanıcılar tatmin olmuyor. ";
        $explanation .= "Bu, **yeni içerik fırsatı** demek!\n\n";
        
        $explanation .= "**Ne yapmalı?**\n\n";
        $explanation .= "Önerilen içerik tipi: **{$content_type}**\n\n";
        $explanation .= "Bu keyword için özel olarak optimize edilmiş yeni içerik oluşturun:\n";
        $explanation .= "- Keyword'ü title, H1, URL'de kullanın\n";
        $explanation .= "- İlk paragrafta net cevap verin\n";
        $explanation .= "- Kapsamlı, derinlemesine içerik yazın\n\n";
        
        $explanation .= "**Potansiyel kazanç:**\n\n";
        $explanation .= "Doğru içerik tipiyle, bu keyword'den **5-10x daha fazla** trafik alabilirsiniz. ";
        $explanation .= "Boşluğu doldurun, trafiği kapmaya başlayın! 🎯";
        
        return $explanation;
    }
    
    /**
     * Explain generic opportunity
     */
    private function explain_generic_opportunity($opp) {
        $query = $opp['query'];
        
        $explanation = "**Fırsat tespit edildi!**\n\n";
        $explanation .= "\"{$query}\" için SEO potansiyeli var.\n\n";
        $explanation .= "Detaylı analiz için opportunity'yi inceleyin ve önerilen aksiyonları uygulayın.";
        
        return $explanation;
    }
    
    /**
     * Generate reasoning for why an action is recommended
     * 
     * @param string $action_type Action type
     * @param array $context Context data
     * @return string Reasoning
     */
    public function explain_action($action_type, $context = array()) {
        $reasonings = array(
            'title_rewrite' => "Title, SERP'te ilk görülen element. Çekici title = daha fazla tıklama. Keyword başta olmalı, sayı/tarih güven verir.",
            'meta_description' => "Meta description, kullanıcının tıklama kararını etkiler. CTA ve fayda vurgusu CTR'yi artırır.",
            'featured_snippet' => "Featured snippet, pozisyon 0'dır - en üstte görünür. Kısa, net cevap Google'ın sevdiği formattır.",
            'content_depth' => "Derin içerik = daha fazla keyword coverage = daha iyi sıralama. Google kapsamlı içerikleri sever.",
            'internal_links' => "Internal linkler, sayfanın önemini Google'a gösterir. Daha fazla link = daha fazla authority.",
            'content_rewrite' => "Mevcut içerik yetersiz. Rakipler daha iyi içerik sunuyor. Yeniden yazma şart.",
            'new_content' => "Bu keyword için optimize edilmiş içerik yok. Yeni içerik = yeni trafik fırsatı.",
            'canonical_tag' => "Canonical tag, Google'a hangi sayfanın primary olduğunu söyler. Kannibalizasyonu çözer.",
            'eat_signals' => "E-A-T (Expertise, Authority, Trust) Google'ın ranking faktörü. Yazar bilgisi, kaynak, tarih güven verir.",
        );
        
        return $reasonings[$action_type] ?? "Bu aksiyon, SEO performansını artıracaktır.";
    }
}
