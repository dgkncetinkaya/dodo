<?php
/**
 * Prompt Builder Sınıfı
 * 
 * OpenAI için prompt oluşturur
 *
 * @package DODO_AI_SEO
 * @since 1.0.0
 */

// Doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

class DODO_Prompt_Builder {
    
    /**
     * Aktif prompt blokları (debug için)
     */
    private $active_blocks = array();
    
    /**
     * Sistem promptunu oluştur
     * 
     * @param string $length
     * @param string $tone
     * @param string $content_type
     * @param array $advanced_controls Advanced generation controls (Sprint 3)
     * @return string
     */
    public function build_system_prompt($length = 'medium', $tone = 'technical', $content_type = 'blog', $advanced_controls = array()) {
        $this->active_blocks = array(); // Reset
        
        $length_profile = $this->get_length_profile($length);
        
        // Parse advanced controls
        $content_intent = isset($advanced_controls['content_intent']) ? $advanced_controls['content_intent'] : 'informational';
        $expertise_depth = isset($advanced_controls['expertise_depth']) ? $advanced_controls['expertise_depth'] : 'intermediate';
        $geo_optimization = isset($advanced_controls['geo_optimization']) ? $advanced_controls['geo_optimization'] : 'moderate';
        $ai_naturalness = isset($advanced_controls['ai_naturalness']) ? $advanced_controls['ai_naturalness'] : 'human';
        $semantic_aggressiveness = isset($advanced_controls['semantic_aggressiveness']) ? $advanced_controls['semantic_aggressiveness'] : 'moderate';
        $readability_target = isset($advanced_controls['readability_target']) ? $advanced_controls['readability_target'] : 'easy';
        
        // Debug log
        error_log("=== DODO PROMPT BUILDER START ===");
        error_log("DODO: Length: {$length}, Tone: {$tone}, Content Type: {$content_type}");
        error_log("DODO: Advanced Controls - Intent: {$content_intent}, Depth: {$expertise_depth}, GEO: {$geo_optimization}");
        
        $prompt = "Sen profesyonel bir SEO içerik yazarısın. Türkçe blog yazıları yazıyorsun.\n\n";
        
        // Görev tanımı
        $prompt .= "## Görevin:\n";
        $prompt .= "Verilen anahtar kelime ve konuya göre SEO uyumlu, uzun ve detaylı blog yazısı oluşturmak.\n\n";
        
        // Advanced Controls Rules (Sprint 3 - Task 6)
        $prompt .= $this->get_advanced_controls_rules($content_intent, $expertise_depth, $geo_optimization, $ai_naturalness, $semantic_aggressiveness, $readability_target);
        $this->active_blocks[] = 'advanced_controls';
        
        // Modüler blokları ekle
        $prompt .= $this->get_content_length_rules($length_profile);
        $this->active_blocks[] = 'content_length_rules';
        
        $prompt .= $this->get_tone_rules($tone);
        $this->active_blocks[] = 'tone_rules';
        
        $prompt .= $this->get_seo_rules($length_profile);
        $this->active_blocks[] = 'seo_rules';
        
        $prompt .= $this->get_intro_rules($length_profile);
        $this->active_blocks[] = 'intro_rules';
        
        $prompt .= $this->get_content_type_rules($content_type, $length_profile);
        $this->active_blocks[] = 'content_type_rules';
        
        $prompt .= $this->get_faq_rules($length_profile);
        $this->active_blocks[] = 'faq_rules';
        
        $prompt .= $this->get_cta_rules();
        $this->active_blocks[] = 'cta_rules';
        
        $prompt .= $this->get_humanization_rules($tone);
        $this->active_blocks[] = 'humanization_rules';
        
        $prompt .= $this->get_quality_block_rules($content_type, $length);
        $this->active_blocks[] = 'quality_block_rules';
        
        // Format ve çıktı kuralları
        $prompt .= "## Format:\n";
        $prompt .= "- Markdown formatında yaz\n";
        $prompt .= "- H2 için ## kullan\n";
        $prompt .= "- H3 için ### kullan\n";
        $prompt .= "- Listeler için - veya 1. kullan\n";
        $prompt .= "- Kalın yazı için **metin** kullan\n\n";
        
        $prompt .= "## Çıktı Formatı:\n";
        $prompt .= "Yanıtını şu JSON formatında ver:\n";
        $prompt .= "```json\n";
        $prompt .= "{\n";
        $prompt .= '  "seo_title": "50-60 karakter arası SEO başlığı",'."\n";
        $prompt .= '  "meta_description": "150-160 karakter arası meta açıklama",'."\n";
        $prompt .= '  "slug": "seo-uyumlu-url-slug",'."\n";
        $prompt .= '  "excerpt": "150-200 kelimelik özet",'."\n";
        $prompt .= '  "content": "Tam blog içeriği (markdown formatında)",'."\n";
        $prompt .= '  "focus_keyword": "odak anahtar kelime",'."\n";
        $prompt .= '  "faq": ['."\n";
        $prompt .= '    {"question": "Soru 1?", "answer": "Cevap 1"},'."\n";
        $prompt .= '    {"question": "Soru 2?", "answer": "Cevap 2"}'."\n";
        $prompt .= '  ],'."\n";
        $prompt .= '  "tags": ["tag1", "tag2", "tag3"]'."\n";
        $prompt .= "}\n";
        $prompt .= "```\n\n";
        
        $prompt .= "**ÖNEMLİ:** Sadece JSON çıktısı ver, başka açıklama ekleme.";
        
        // Debug log
        $prompt_length = strlen($prompt);
        $estimated_tokens = (int) ceil($prompt_length / 4);
        error_log("DODO: Active blocks: " . implode(', ', $this->active_blocks));
        error_log("DODO: Final prompt length: {$prompt_length} chars (~{$estimated_tokens} tokens)");
        error_log("=== DODO PROMPT BUILDER END ===");
        
        return $prompt;
    }
    
    /**
     * İçerik uzunluğu kuralları
     * 
     * @param array $length_profile
     * @return string
     */
    private function get_content_length_rules($length_profile) {
        $rules = "## Yazı Kuralları:\n";
        $rules .= "- HEDEF KELIME SAYISI: {$length_profile['target_words']}\n";
        $rules .= "- Minimum {$length_profile['min_words']} kelime uzunluğunda yaz\n";
        $rules .= "- Türkçe dilbilgisi kurallarına uy\n";
        $rules .= "- Her bölüm en az {$length_profile['min_section_words']} kelime olmalı\n\n";
        
        return $rules;
    }
    
    /**
     * Advanced Generation Controls Rules (Sprint 3 - Task 6)
     * 
     * @param string $content_intent
     * @param string $expertise_depth
     * @param string $geo_optimization
     * @param string $ai_naturalness
     * @param string $semantic_aggressiveness
     * @param string $readability_target
     * @return string
     */
    public function get_advanced_controls_rules($content_intent, $expertise_depth, $geo_optimization, $ai_naturalness, $semantic_aggressiveness, $readability_target) {
        $rules = "## Advanced Generation Controls:\n";
        
        // Content Intent
        $intent_rules = array(
            'informational' => "**Bilgilendirme Modu:**\n- Okuyucuyu eğit ve detaylı açıkla\n- Örneklerle destekle",
            'educational' => "**Eğitim Modu:**\n- Adım adım öğret\n- Her kavramı açıkla",
            'transactional' => "**Dönüşüm Modu:**\n- Ürün/hizmet avantajlarını vurgula\n- Harekete geçirici dil kullan",
            'navigational' => "**Yönlendirme Modu:**\n- Net bilgi ver\n- Özet formatında yaz",
            'commercial' => "**Ticari Araştırma Modu:**\n- Karşılaştır ve değerlendir\n- Satın alma kriterleri belirt",
        );
        $rules .= $intent_rules[$content_intent] ?? $intent_rules['informational'];
        $rules .= "\n\n";
        
        // Expertise Depth
        $depth_rules = array(
            'beginner' => "**Başlangıç Seviyesi:**\n- Basit dil kullan\n- Teknik terimleri açıkla",
            'intermediate' => "**Orta Seviye:**\n- Temel teknik terimleri kullan\n- Pratik uygulamaya odaklan",
            'advanced' => "**İleri Seviye:**\n- Teknik terminoloji kullan\n- Derinlemesine analiz yap",
            'expert' => "**Uzman Seviyesi:**\n- Profesyonel jargon kullan\n- Sektör spesifik bilgiler ekle",
        );
        $rules .= $depth_rules[$expertise_depth] ?? $depth_rules['intermediate'];
        $rules .= "\n\n";
        
        // GEO Optimization
        $geo_rules = array(
            'none' => "**GEO Yok:**\n- Standart SEO odaklı yaz",
            'light' => "**Hafif GEO:**\n- Bazı soru-cevap formatları ekle",
            'moderate' => "**Orta GEO:**\n- Soru başlıkları kullan\n- Direkt cevaplar ver\n- Yapılandırılmış içerik",
            'aggressive' => "**Agresif GEO (LLM Extraction Optimized):**\n\n**ZORUNLU - HER H2 ALTINDA:**\n- İlk paragraf 40-60 kelime, direkt cevap\n- Featured snippet formatı kullan\n- Soru-cevap yapısı\n\n**ZORUNLU FORMATLAR:**\n- Tanım kutuları: **[Terim]:** [Kısa açıklama]\n- Kısa listeler (3-5 madde)\n- Tablo formatları\n- Adım adım numaralı listeler\n\n**LLM İÇİN OPTİMİZE ET:**\n- Her paragraf bağımsız anlaşılabilir\n- Chunk-friendly yapı\n- Citation-ready cümleler\n- Entity yoğunluğu (marka, ürün, yer isimleri)",
        );
        $rules .= $geo_rules[$geo_optimization] ?? $geo_rules['moderate'];
        $rules .= "\n\n";
        
        // AI Naturalness
        $naturalness_rules = array(
            'very_human' => "**Çok İnsan:**\n- Kişisel anekdotlar ekle\n- Duygusal bağ kur\n- Konuşma dili kullan",
            'human' => "**İnsan (AI Kalıpları Yasak):**\n\n**YASAKLI KALIPLAR:**\n- 'Günümüzde...'\n- 'Önemli bir nokta...'\n- 'Dikkat edilmesi gereken...'\n- 'Sonuç olarak...'\n- 'Özetle...'\n- 'Ayrıca...'\n- 'Bununla birlikte...'\n- 'Bu yazıda...'\n- 'Bu makalede...'\n\n**ZORUNLU:**\n- Doğrudan anlatım\n- Aktif cümleler\n- Kısa ve net ifadeler\n- Gerçek deneyim dili",
            'balanced' => "**Dengeli:**\n- Profesyonel ama akıcı\n- Doğal geçişler",
            'ai_friendly' => "**AI Dostu:**\n- Yapılandırılmış format\n- Net ve düzenli",
        );
        $rules .= $naturalness_rules[$ai_naturalness] ?? $naturalness_rules['human'];
        $rules .= "\n\n";
        
        // Semantic Aggressiveness
        $semantic_rules = array(
            'conservative' => "**Muhafazakar Semantik:**\n- Odak keyword'e sadık kal",
            'moderate' => "**Orta Semantik:**\n- Dengeli semantik kapsam\n- İlgili terimleri ekle",
            'aggressive' => "**Agresif Semantik:**\n- Geniş semantik ağ\n- LSI keywords kullan",
        );
        $rules .= $semantic_rules[$semantic_aggressiveness] ?? $semantic_rules['moderate'];
        $rules .= "\n\n";
        
        // Readability Target
        $readability_rules = array(
            'very_easy' => "**Çok Kolay:**\n- Kısa cümleler\n- Basit kelimeler",
            'easy' => "**Kolay:**\n- Orta cümleler\n- Anlaşılır dil",
            'moderate' => "**Orta:**\n- Dengeli cümleler\n- Profesyonel dil",
            'advanced' => "**İleri:**\n- Uzun cümleler\n- Karmaşık yapılar",
        );
        $rules .= $readability_rules[$readability_target] ?? $readability_rules['easy'];
        $rules .= "\n\n";
        
        return $rules;
    }
    
    /**
     * Ton kuralları
     * 
     * @param string $tone
     * @return string
     */
    private function get_tone_rules($tone) {
        $tone_instructions = array(
            'technical' => "- Profesyonel ama samimi bir dil kullan\n- Sektör terminolojisi kullan\n- Uzman dili ama anlaşılır\n",
            'informative' => "- Öğretici ve açıklayıcı dil kullan\n- Adım adım anlatım\n- Detaylı açıklamalar\n",
            'commercial' => "- Satış odaklı ama bilgilendirici ol\n- Ürün/hizmet avantajlarını vurgula\n- İkna edici dil kullan\n",
            'friendly' => "- Samimi ve sıcak dil kullan\n- Konuşma tarzında yaz\n- Okuyucuyla bağ kur\n",
            'corporate' => "- Resmi ve profesyonel ton kullan\n- Kurumsal dil\n- Güvenilir ve otoriter\n",
            'expert' => "- Otoriter ve derinlemesine analiz\n- Veri destekli açıklamalar\n- Sektör lideri perspektifi\n",
        );
        
        $rules = "## Ton ve Dil:\n";
        $rules .= $tone_instructions[$tone] ?? $tone_instructions['technical'];
        $rules .= "- Üretici firma dili kullan (teorik değil, atölye/uygulama deneyimi)\n\n";
        
        return $rules;
    }
    
    /**
     * SEO kuralları
     * 
     * @param array $length_profile
     * @return string
     */
    private function get_seo_rules($length_profile) {
        $rules = "## SEO Kuralları:\n";
        $rules .= "- Odak anahtar kelimeyi doğal şekilde kullan (spam yapma)\n";
        $rules .= "- Semantik SEO kelimelerini ekle\n";
        $rules .= "- H2 ve H3 başlıkları düzenli kullan\n";
        $rules .= "- Giriş paragrafında odak anahtar kelime geçmeli\n";
        $rules .= "- İç linkleri doğal şekilde yerleştir\n\n";
        
        return $rules;
    }
    
    /**
     * Giriş kuralları
     * 
     * @param array $length_profile
     * @return string
     */
    private function get_intro_rules($length_profile) {
        $rules = "## Giriş Bölümü:\n";
        $rules .= "- {$length_profile['intro_words']} kelime uzunluğunda\n";
        $rules .= "- Konuya giriş yap\n";
        $rules .= "- Odak anahtar kelime doğal şekilde geçmeli\n";
        $rules .= "- Yumuşak CTA (örn: 'Bu yazıda detaylı inceleyeceğiz')\n\n";
        
        return $rules;
    }
    
    /**
     * İçerik tipi kuralları
     * 
     * @param string $content_type
     * @param array $length_profile
     * @return string
     */
    private function get_content_type_rules($content_type, $length_profile) {
        $rules = "## Ana İçerik Yapısı:\n";
        $rules .= "- {$length_profile['main_words']} kelime uzunluğunda\n";
        $rules .= "- {$length_profile['h2_count']} ana bölüm (H2)\n";
        $rules .= "- Her H2 altında {$length_profile['h3_per_h2']} alt başlık (H3)\n";
        $rules .= "- Orta bölümde ürün yönlendirme CTA'sı\n\n";
        
        // İçerik tipine özel talimatlar
        $type_specific = $this->get_content_type_instructions($content_type);
        $rules .= "## İçerik Tipi Talimatları:\n";
        $rules .= $type_specific . "\n\n";
        
        return $rules;
    }
    
    /**
     * SSS kuralları
     * 
     * @param array $length_profile
     * @return string
     */
    private function get_faq_rules($length_profile) {
        $rules = "## SSS Bölümü:\n";
        $rules .= "- {$length_profile['faq_words']} kelime uzunluğunda\n";
        $rules .= "- Başlık: '## Sıkça Sorulan Sorular'\n";
        $rules .= "- En az {$length_profile['faq_count']} soru-cevap\n";
        $rules .= "- Her cevap {$length_profile['faq_answer_words']} kelime\n";
        $rules .= "- Sorular H3 olarak formatla\n\n";
        
        return $rules;
    }
    
    /**
     * CTA kuralları
     * 
     * @return string
     */
    private function get_cta_rules() {
        $rules = "## CTA Kuralları:\n";
        $rules .= "- 3 farklı CTA kullan (giriş, orta, sonuç)\n";
        $rules .= "- CTA'lar doğal ve konuya uygun olmalı\n";
        $rules .= "- Yapay satış dili kullanma\n";
        $rules .= "- Örnek: 'Ürünlerimizi incelemek için tıklayın' yerine 'Detaylı ürün karşılaştırmamıza göz atabilirsiniz'\n\n";
        
        return $rules;
    }
    
    /**
     * İnsansılaştırma kuralları
     * 
     * @param string $tone
     * @return string
     */
    private function get_humanization_rules($tone) {
        $rules = "## İnsansılaştırma:\n";
        
        // Ton'a göre insansılaştırma seviyesi
        if ($tone === 'friendly' || $tone === 'informative') {
            $rules .= "- Kişisel deneyim ve örnekler ekle\n";
            $rules .= "- Konuşma dili kullan\n";
            $rules .= "- Okuyucuya doğrudan hitap et\n";
        } elseif ($tone === 'expert' || $tone === 'technical') {
            $rules .= "- Profesyonel ama erişilebilir dil\n";
            $rules .= "- Gerçek dünya örnekleri ver\n";
            $rules .= "- Uygulamalı bilgi paylaş\n";
        } else {
            $rules .= "- Doğal ve akıcı dil kullan\n";
            $rules .= "- Pratik örnekler ekle\n";
            $rules .= "- Okuyucu odaklı yaz\n";
        }
        
        $rules .= "\n";
        
        return $rules;
    }
    
    /**
     * Kalite blok kuralları
     * 
     * @param string $content_type
     * @param string $length
     * @return string
     */
    private function get_quality_block_rules($content_type, $length) {
        // Mevcut build_quality_blocks_prompt metodunu kullan
        return $this->build_quality_blocks_prompt($content_type, $length);
    }
    
    /**
     * İç link kuralları (her zaman aktif)
     * 
     * @return string
     */
    private function get_internal_link_rules() {
        $rules = "## İç Link Kuralları:\n";
        $rules .= "- Verilen linkleri SADECE BİR KEZ kullan\n";
        $rules .= "- Anchor text doğal olmalı\n";
        $rules .= "- Link yerleştirme örnekleri:\n";
        $rules .= "  ✓ 'WordPress SEO eklentileri hakkında daha fazla bilgi için [rehberimize](URL) göz atabilirsiniz'\n";
        $rules .= "  ✓ 'Bu konuda [detaylı incelememizde](URL) daha fazla bilgi bulabilirsiniz'\n";
        $rules .= "  ✗ 'Buraya tıklayın' (kötü anchor text)\n";
        $rules .= "  ✗ Aynı URL'yi birden fazla kez kullanma\n\n";
        
        return $rules;
    }
    
    /**
     * Aktif prompt bloklarını al (debug için)
     * 
     * @return array
     */
    public function get_active_blocks() {
        return $this->active_blocks;
    }
    
    /**
     * Uzunluk profilini al
     * 
     * @param string $length
     * @return array
     */
    public function get_length_profile($length) {
        $profiles = array(
            'short' => array(
                'min_words' => 1200,
                'target_words' => '1200-1800',
                'max_words' => 1800,
                'intro_words' => '150-250',
                'main_words' => '800-1200',
                'faq_words' => '200-300',
                'conclusion_words' => '100-200',
                'h2_count' => '3-5',
                'h3_per_h2' => '1-2',
                'faq_count' => 3,
                'faq_answer_words' => '40-60',
                'min_section_words' => 150,
                'section_words' => '300-500',
            ),
            'medium' => array(
                'min_words' => 2500,
                'target_words' => '2500-3500',
                'max_words' => 3500,
                'intro_words' => '300-400',
                'main_words' => '1800-2500',
                'faq_words' => '400-500',
                'conclusion_words' => '200-300',
                'h2_count' => '5-8',
                'h3_per_h2' => '2-3',
                'faq_count' => 6,
                'faq_answer_words' => '60-100',
                'min_section_words' => 250,
                'section_words' => '500-700',
            ),
            'long' => array(
                'min_words' => 4000,
                'target_words' => '4000-6000',
                'max_words' => 6000,
                'intro_words' => '400-600',
                'main_words' => '3000-4500',
                'faq_words' => '600-800',
                'conclusion_words' => '300-500',
                'h2_count' => '8-12',
                'h3_per_h2' => '2-4',
                'faq_count' => 10,
                'faq_answer_words' => '80-150',
                'min_section_words' => 300,
                'section_words' => '700-1000',
            ),
            'authority' => array(
                'min_words' => 4000,
                'target_words' => '4000-6000',
                'max_words' => 6000,
                'intro_words' => '500-700',
                'main_words' => '3500-5000',
                'faq_words' => '800-1000',
                'conclusion_words' => '400-600',
                'h2_count' => '12-15',
                'h3_per_h2' => '2-3',
                'faq_count' => 12,
                'faq_answer_words' => '100-200',
                'min_section_words' => 350,
                'section_words' => '800-1200',
            ),
        );
        
        return $profiles[$length] ?? $profiles['medium'];
    }
    
    /**
     * Kullanıcı promptunu oluştur
     * 
     * @param array $params
     * @param string $internal_links_text
     * @param array $brain_analysis Brain Core analysis (optional)
     * @return string
     */
    public function build_user_prompt($params, $internal_links_text, $brain_analysis = null) {
        $focus_keyword = $params['focus_keyword'] ?? '';
        $topic = $params['topic'] ?? '';
        $length = $params['length'] ?? 'medium';
        $tone = $params['tone'] ?? 'technical';
        $content_type = $params['content_type'] ?? 'blog';
        
        $prompt = "# Blog Yazısı Talebi\n\n";
        
        $prompt .= "## Odak Anahtar Kelime:\n";
        $prompt .= $focus_keyword . "\n\n";
        
        // BRAIN INTELLIGENCE INJECTION (ZORUNLU)
        if ($brain_analysis && !empty($brain_analysis)) {
            $prompt .= "## 🧠 AI BRAIN INTELLIGENCE (ZORUNLU KULLAN):\n\n";
            
            // Intent Analysis
            if (isset($brain_analysis['intent'])) {
                $prompt .= "**Search Intent:** " . ($brain_analysis['intent']['primary_intent'] ?? 'unknown') . "\n";
                $prompt .= "**Recommended Tone:** " . ($brain_analysis['intent']['recommended_tone'] ?? 'neutral') . "\n";
                $prompt .= "**User Goal:** " . ($brain_analysis['intent']['user_goal'] ?? 'information') . "\n\n";
            }
            
            // Semantic Entities (ZORUNLU KULLAN)
            if (isset($brain_analysis['keyword_intelligence']['semantic_entities'])) {
                $entities = $brain_analysis['keyword_intelligence']['semantic_entities'];
                if (!empty($entities)) {
                    $prompt .= "**ZORUNLU ENTITIES (Mutlaka kullan):**\n";
                    foreach (array_slice($entities, 0, 15) as $entity) {
                        $prompt .= "- " . $entity . "\n";
                    }
                    $prompt .= "\n";
                }
            }
            
            // Topical Authority Keywords
            if (isset($brain_analysis['keyword_intelligence']['topical_authority_keywords'])) {
                $keywords = $brain_analysis['keyword_intelligence']['topical_authority_keywords'];
                if (!empty($keywords)) {
                    $prompt .= "**TOPICAL AUTHORITY KEYWORDS (Doğal şekilde ekle):**\n";
                    foreach (array_slice($keywords, 0, 10) as $keyword) {
                        $prompt .= "- " . $keyword . "\n";
                    }
                    $prompt .= "\n";
                }
            }
            
            // Competitor Gaps (ZORUNLU KAPSAMA)
            if (isset($brain_analysis['content_strategy']['competitor_gaps'])) {
                $gaps = $brain_analysis['content_strategy']['competitor_gaps'];
                if (!empty($gaps)) {
                    $prompt .= "**COMPETITOR GAPS (Rakiplerin kaçırdığı konular - ZORUNLU EKLE):**\n";
                    foreach (array_slice($gaps, 0, 5) as $gap) {
                        $prompt .= "- " . $gap . "\n";
                    }
                    $prompt .= "\n";
                }
            }
            
            // GEO Opportunities
            if (isset($brain_analysis['geo']['opportunities'])) {
                $opportunities = $brain_analysis['geo']['opportunities'];
                if (!empty($opportunities)) {
                    $prompt .= "**GEO OPPORTUNITIES (LLM extraction için optimize et):**\n";
                    foreach (array_slice($opportunities, 0, 5) as $opp) {
                        $prompt .= "- " . $opp . "\n";
                    }
                    $prompt .= "\n";
                }
            }
            
            // Content Recommendations
            if (isset($brain_analysis['content_strategy']['recommended_sections'])) {
                $sections = $brain_analysis['content_strategy']['recommended_sections'];
                if (!empty($sections)) {
                    $prompt .= "**RECOMMENDED SECTIONS (Bu bölümleri mutlaka ekle):**\n";
                    foreach (array_slice($sections, 0, 8) as $section) {
                        $prompt .= "- " . $section . "\n";
                    }
                    $prompt .= "\n";
                }
            }
            
            $prompt .= "**ÖNEMLİ:** Yukarıdaki Brain Intelligence verilerini ZORUNLU kullan. Bu sadece öneri değil, direktiftir!\n\n";
        }
        
        // Topic opsiyonel - boşsa AI otomatik yön belirler
        if (!empty($topic)) {
            $prompt .= "## Konu / Makale Açısı:\n";
            $prompt .= $topic . "\n\n";
        } else {
            $prompt .= "## Konu / Makale Açısı:\n";
            $prompt .= "Kullanıcı konu belirtmedi. Odak anahtar kelimeye ve Brain Intelligence'a göre en uygun makale açısını, başlık stratejisini ve semantik kapsamı SEN belirle.\n";
            $prompt .= "En güncel, en faydalı ve SEO açısından en güçlü yaklaşımı seç.\n\n";
        }
        
        $prompt .= "## Yazı Uzunluğu:\n";
        $prompt .= $this->get_length_description($length) . "\n\n";
        
        $prompt .= "## Ton:\n";
        $prompt .= $this->get_tone_description($tone) . "\n\n";
        
        $prompt .= "## İçerik Tipi ve Yapı Talimatları:\n";
        $prompt .= $this->get_content_type_instructions($content_type) . "\n\n";
        
        // Kalite blokları
        $quality_blocks_prompt = $this->build_quality_blocks_prompt($content_type, $length);
        if (!empty($quality_blocks_prompt)) {
            $prompt .= $quality_blocks_prompt . "\n\n";
        }
        
        // İç linkler
        if (!empty($internal_links_text)) {
            $prompt .= $internal_links_text . "\n\n";
        }
        
        $prompt .= "---\n\n";
        $prompt .= "Yukarıdaki bilgilere, Brain Intelligence direktiflerine, içerik tipi talimatlarına ve kalite bloklarına göre SEO uyumlu, uzun ve detaylı bir yazı oluştur.\n";
        $prompt .= "Brain Intelligence'daki entities, keywords ve gaps'leri ZORUNLU kullan.\n";
        $prompt .= "Kalite bloklarını yazının uygun yerlerine doğal şekilde yerleştir.\n";
        $prompt .= "Yanıtını JSON formatında ver.";
        
        return $prompt;
    }
    
    /**
     * Uzunluk açıklamasını al
     * 
     * @param string $length
     * @return string
     */
    private function get_length_description($length) {
        $profile = $this->get_length_profile($length);
        $descriptions = array(
            'short' => "Kısa ({$profile['target_words']} kelime) - Hızlı okunabilir, özet bilgi. Maksimum {$profile['h2_count']} H2 bölüm. Kısa giriş ve özlü açıklamalar.",
            'medium' => "Orta ({$profile['target_words']} kelime) - Detaylı ve kapsamlı. {$profile['h2_count']} H2 bölüm, dengeli yapı, orta düzey semantik kapsama.",
            'long' => "Uzun ({$profile['target_words']} kelime) - Çok detaylı, otorite rehber. {$profile['h2_count']} H2 bölüm, genişletilmiş H2/H3 hiyerarşisi, tablolar, checklistler, örnekler, profesyonel ipuçları, sık yapılan hatalar, genişletilmiş SSS, kullanım senaryoları.",
        );
        
        return $descriptions[$length] ?? $descriptions['medium'];
    }
    
    /**
     * Ton açıklamasını al
     * 
     * @param string $tone
     * @return string
     */
    private function get_tone_description($tone) {
        $descriptions = array(
            'technical' => 'Teknik ve Profesyonel - Sektör terminolojisi, resmi ama samimi, uzman dili',
            'informative' => 'Öğretici ve Bilgilendirici - Eğitici, detaylı açıklamalar, adım adım anlatım',
            'commercial' => 'Ticari ve Dönüşüm Odaklı - Ürün/hizmet tanıtımı, ikna edici, satışa yönlendiren',
            'friendly' => 'Samimi ve İnsan Odaklı - Sıcak, konuşma dili, okuyucuyla bağ kuran',
            'corporate' => 'Kurumsal - Resmi, güvenilir, marka imajına uygun, profesyonel ton',
            'expert' => 'Uzman Görüşlü - Otoriter, derinlemesine analiz, veri destekli, sektör lideri perspektifi',
        );
        
        return $descriptions[$tone] ?? $descriptions['technical'];
    }
    
    /**
     * İçerik tipi talimatlarını al
     * 
     * @param string $content_type
     * @return string
     */
    private function get_content_type_instructions($content_type) {
        $instructions = array(
            'blog' => "İçerik Tipi: Standart SEO Blog Yazısı\n"
                . "- Klasik blog yazısı yapısı kullan\n"
                . "- Giriş, ana bölümler (H2/H3), SSS ve sonuç bölümleri olsun\n"
                . "- Doğal akışta bilgilendirici içerik yaz\n"
                . "- CTA'ları giriş, orta ve sonuç bölümlerine yerleştir",

            'howto' => "İçerik Tipi: Nasıl Yapılır? (How-To) Rehberi\n"
                . "- Adım adım anlatım yapısı kullan\n"
                . "- H2 başlıklarını süreç odaklı yaz (\"Adım 1: ...\", \"Adım 2: ...\" veya \"1. Aşama: ...\")\n"
                . "- Her adımı detaylı açıkla, nelere dikkat edilmeli belirt\n"
                . "- Başlangıçta gerekli malzeme/araç/ön koşul listesi ver\n"
                . "- Olası hatalar ve çözümleri için bir bölüm ekle\n"
                . "- Sonunda \"Sık Yapılan Hatalar\" veya \"İpuçları\" bölümü ekle",

            'comparison' => "İçerik Tipi: Karşılaştırma İçeriği\n"
                . "- Karşılaştırılan öğeleri net şekilde tanıt\n"
                . "- Markdown tablo formatında bir karşılaştırma tablosu oluştur (\"| Özellik | A | B |\" gibi)\n"
                . "- Her öğe için ayrı H2 bölümü yaz\n"
                . "- Avantajlar ve dezavantajlar bölümü ekle (her öğe için \"\u2713 Avantajlar\" / \"\u2717 Dezavantajlar\" listesi)\n"
                . "- \"Hangisini Seçmelisiniz?\" veya \"Sonuç ve Tavsiye\" bölümü ile bitir\n"
                . "- Objektif ve tarafsız bir dil kullan",

            'listicle' => "İçerik Tipi: Liste İçeriği (Listicle)\n"
                . "- H2 başlıkları numaralı yaz (\"1. ...\", \"2. ...\", \"3. ...\" gibi)\n"
                . "- Başlık formatı: \"En İyi 10 ...\", \"Top 15 ...\", \"... için 7 Kritik ...\" tarzı kullan\n"
                . "- Her liste öğesi için kısa bir özet ve detaylı açıklama yaz\n"
                . "- Görsel ayrım için bold anahtar kelimeler ve kısa paragraflar kullan\n"
                . "- Listede sıralama mantığı belirt (önem, fiyat, popülerlik vb.)\n"
                . "- Sonunda \"Bonus\" veya \"Ekstra Öneri\" bölümü ekleyebilirsin",

            'guide' => "İçerik Tipi: Ürün Rehberi\n"
                . "- Ürün/hizmet odaklı detaylı rehber yaz\n"
                . "- Kullanım senaryoları ve gerçek hayat örnekleri ver\n"
                . "- \"Kimler İçin Uygun?\", \"Nasıl Kullanılır?\", \"Ne Zaman Tercih Edilmeli?\" bölümleri ekle\n"
                . "- CTA yoğunluğunu biraz artır (her ana bölüm sonunda doğal CTA)\n"
                . "- Fiyat/değer analizi bölümü ekle\n"
                . "- \"Ürünü Seçerken Dikkat Edilmesi Gerekenler\" bölümü ekle",

            'category' => "İçerik Tipi: Kategori SEO Yazısı\n"
                . "- Daha geniş semantik kapsama alanı kullan\n"
                . "- Kategori genelini kapsayan üst düzey başlıklar yaz\n"
                . "- Alt kategorilere ve ilgili konulara referans ver\n"
                . "- Geniş anahtar kelime yelpazesi kullan (ana + uzun kuyruk + semantik)\n"
                . "- \"[Kategori] Nedir?\", \"[Kategori] Türleri\", \"[Kategori] Nasıl Seçilir?\" yapısını takip et\n"
                . "- Kategori içindeki ürün/hizmet çeşitlerini listele ve açıkla",

            'faq' => "İçerik Tipi: SSS (FAQ) Odaklı İçerik\n"
                . "- Yazının büyük bölümünü soru-cevap formatında oluştur\n"
                . "- Minimum 10-15 soru-cevap çifti yaz\n"
                . "- Soruları H2 veya H3 başlık olarak formatla\n"
                . "- Her cevap 80-150 kelime arasında olsun\n"
                . "- Soruları mantıksal sırayla grupla (temel, orta, ileri düzey)\n"
                . "- Kısa bir giriş paragrafı ile başla, SSS bölümüne geç\n"
                . "- Soruları gerçek kullanıcıların sorabileceği şekilde yaz",
        );
        
        return $instructions[$content_type] ?? $instructions['blog'];
    }
    
    /**
     * İçerik tipine göre kalite bloklarını al
     * 
     * Her blok: [position, label, instruction]
     * position: 'intro' | 'middle' | 'closing' — mantıksal yerleşim grubu
     * 
     * @param string $content_type
     * @return array
     */
    private function get_quality_blocks($content_type) {
        $blocks = array(
            'blog' => array(
                array('intro', 'Kısa Özet Kutusu', 'Yazının başında, konuyu 3-4 cümlede özetleyen kalın yazılı bir özet kutusu ekle. "📋 Kısa Özet:" başlığı ile başlasın.'),
                array('middle', 'Profesyonel İpucu', 'Ana bölümlerin arasına "💡 Profesyonel İpucu:" başlığıyla pratik, uygulanabilir bir ipucu kutusu ekle. Gerçek deneyime dayalı olsun.'),
                array('closing', 'SSS Bölümü', 'Sonuç öncesinde en az 5 soru-cevaplık SSS bölümü ekle. Sorular H3, cevaplar 50-80 kelime olsun.'),
            ),
            'howto' => array(
                array('intro', 'Adım Adım Özet Liste', 'Yazının başında tüm adımları numaralı kısa listede göster. "📋 Adımlar Özeti:" başlığı kullan. Her adım tek cümle olsun.'),
                array('middle', 'Dikkat Edilmesi Gerekenler', 'Sürecin ortasına "⚠️ Dikkat Edilmesi Gerekenler:" başlıklı bir uyarı kutusu ekle. 4-6 madde halinde kritik noktaları listele.'),
                array('closing', 'Sık Yapılan Hatalar', 'Sonuç öncesinde "❌ Sık Yapılan Hatalar ve Çözümleri:" bölümü ekle. En az 4 hata-çözüm çifti yaz.'),
            ),
            'comparison' => array(
                array('intro', 'Karşılaştırma Tablosu', 'Yazının başına Markdown tablo formatında detaylı bir karşılaştırma tablosu ekle. En az 6-8 özellik satırı olsun.'),
                array('middle', 'Artılar ve Eksiler', 'Her karşılaştırılan öğe için "✅ Artılar" ve "❌ Eksiler" alt bölümleri oluştur. Her listede en az 4 madde olsun.'),
                array('closing', 'Hangi Durumda Hangisi', '"🎯 Hangi Durumda Hangisini Seçmelisiniz?" bölümü ekle. Senaryolara göre öneriler yap (bütçe, kullanım amacı, deneyim düzeyi vb.).'),
            ),
            'listicle' => array(
                array('intro', 'Numaralı Liste Özeti', 'Yazının başında tüm liste öğelerini tek satırlık açıklamalarla numaralı listede göster. "📋 Hızlı Bakış:" başlığı kullan.'),
                array('middle', 'Mini Checklist', 'Listenin ortasına "✅ Seçim Checklist\'i:" başlıklı bir kontrol listesi ekle. 6-8 maddelik, evet/hayır formatında olsun.'),
                array('closing', 'Özet Tablo', 'Sonuç öncesinde tüm liste öğelerini karşılaştıran bir Markdown özet tablosu ekle.'),
            ),
            'guide' => array(
                array('intro', 'Kullanım Senaryoları', 'Yazının başına "🎯 Kullanım Senaryoları:" başlığıyla 4-6 gerçek hayat kullanım senaryosu yaz. Her biri 2-3 cümle olsun.'),
                array('middle', 'Seçim Kriterleri', '"📊 Seçim Kriterleri:" başlığıyla neye göre karar verilmesi gerektiğini maddeler halinde açıkla. Bütçe, ihtiyaç, teknik gereksinimler vb.'),
                array('closing', 'CTA Kutusu', '"🛒 Harekete Geçin:" başlığıyla güçlü bir CTA bölümü ekle. Ürünün/hizmetin temel avantajlarını 3 madde halinde özetle ve harekete geçiren bir mesaj yaz.'),
            ),
            'category' => array(
                array('intro', 'Kategori Açıklaması', 'Yazının başına kategoriyi tanımlayan kapsamlı bir açıklama yaz. Kategorinin kapsamı, alt dalları ve sektördeki yeri hakkında bilgi ver.'),
                array('middle', 'Alt Ürün Grupları', '"📦 Alt Ürün Grupları:" başlığıyla kategori içindeki alt grupları H3 başlıklarla listele. Her grup için kısa açıklama ve kullanım alanı belirt.'),
                array('closing', 'Satın Alma Rehberi', '"🛍️ Satın Alma Rehberi:" başlığıyla bu kategoriden ürün/hizmet alırken dikkat edilecek 6-8 kritik noktayı listele.'),
            ),
            'faq' => array(
                array('intro', 'Temel Sorular', 'Konuyla ilgili en temel 3-4 soruyla başla. Bunlar "Nedir?", "Ne işe yarar?", "Neden önemli?" gibi giriş düzeyi sorular olsun.'),
                array('middle', 'Detaylı Soru-Cevaplar', 'Minimum 10 soru-cevap çifti yaz. Her soruyu H3 başlık yap. Her cevapta önce 1-2 cümlelik kısa cevap, sonra detaylı açıklama (80-150 kelime) yaz.'),
                array('closing', 'İleri Düzey Sorular', 'Son bölümde uzman düzeyi 3-4 soru ekle. Bunlar karşılaştırma, ileri teknik veya gelecek öngörüsü içeren sorular olsun.'),
            ),
        );
        
        return $blocks[$content_type] ?? $blocks['blog'];
    }
    
    /**
     * Otorite İçerik (long) için ekstra kalite bloklarını al
     * 
     * @return array
     */
    private function get_extra_authority_blocks() {
        return array(
            array('middle', 'Veri Tablosu', 'İçeriğe konuyla ilgili bir Markdown veri/istatistik tablosu ekle. En az 5-6 satır ve 3-4 sütun olsun. Gerçekçi ve güncel veriler kullan.'),
            array('middle', 'Checklist Bölümü', '"✅ Kontrol Listesi:" başlığıyla 8-12 maddelik kapsamlı bir checklist ekle. Okuyucunun adım adım takip edebileceği format olsun.'),
            array('middle', 'Uzman Yorumu', '"🎙️ Uzman Görüşü:" başlığıyla sektör uzmanı perspektifinden bir yorum bölümü ekle. Derinlemesine analiz, trend değerlendirmesi ve gelecek öngörüsü içersin.'),
            array('closing', 'Sık Yapılan Hatalar', '"❌ Kaçınılması Gereken Hatalar:" başlığıyla en az 5 yaygın hata ve her biri için çözüm önerisi yaz.'),
            array('closing', 'Gelişmiş SSS', '"❓ İleri Düzey Sıkça Sorulan Sorular:" başlığıyla ek 5-8 uzman düzeyi soru-cevap ekle. Cevaplar detaylı ve karşılaştırmalı olsun.'),
        );
    }
    
    /**
     * Kalite bloklarını prompt metnine dönüştür
     * 
     * Blokları 3 pozisyon grubunda (intro/middle/closing) karıştırır.
     * Grup sırası korunur (intro → middle → closing) ama
     * her grup içindeki blokların sırası rastgele değişir.
     * 
     * @param string $content_type
     * @param string $length
     * @return string
     */
    private function build_quality_blocks_prompt($content_type, $length) {
        // Temel blokları al
        $blocks = $this->get_quality_blocks($content_type);
        
        // Otorite İçerik ise ekstra bloklar ekle
        if ($length === 'long') {
            $extra_blocks = $this->get_extra_authority_blocks();
            // Ekstra bloklardan 3 tanesini rastgele seç (her seferinde farklı kombinasyon)
            shuffle($extra_blocks);
            $selected_extras = array_slice($extra_blocks, 0, 3);
            $blocks = array_merge($blocks, $selected_extras);
        }
        
        // Blokları pozisyon gruplarına ayır
        $groups = array(
            'intro' => array(),
            'middle' => array(),
            'closing' => array(),
        );
        
        foreach ($blocks as $block) {
            $position = $block[0];
            if (isset($groups[$position])) {
                $groups[$position][] = $block;
            }
        }
        
        // Her grup içinde sıralamayı karıştır
        foreach ($groups as &$group) {
            shuffle($group);
        }
        unset($group);
        
        // Prompt metnini oluştur
        $prompt = "## İçerik Kalite Blokları:\n";
        $prompt .= "Aşağıdaki kalite bloklarını yazının ilgili bölümlerine doğal şekilde yerleştir.\n";
        $prompt .= "Bloklar birbirinden bağımsız, kendi başlıkları olan özel bölümlerdir.\n";
        $prompt .= "Sıralamayı içerik akışına göre ayarla, zorlama geçiş yapma.\n\n";
        
        $block_number = 1;
        $position_labels = array(
            'intro' => 'Yazının Başında',
            'middle' => 'Ana İçerik Arasında',
            'closing' => 'Sonuç Öncesinde',
        );
        
        foreach (array('intro', 'middle', 'closing') as $position) {
            if (empty($groups[$position])) {
                continue;
            }
            
            foreach ($groups[$position] as $block) {
                $label = $block[1];
                $instruction = $block[2];
                $pos_label = $position_labels[$position];
                
                $prompt .= "**Blok {$block_number} ({$pos_label}) - {$label}:**\n";
                $prompt .= $instruction . "\n\n";
                $block_number++;
            }
        }
        
        return $prompt;
    }
    
    /**
     * Prompt uzunluğunu hesapla
     * 
     * @param string $system_prompt
     * @param string $user_prompt
     * @return int Yaklaşık token sayısı
     */
    public function estimate_prompt_tokens($system_prompt, $user_prompt) {
        $total_text = $system_prompt . $user_prompt;
        // Basit tahmin: ~4 karakter = 1 token
        return (int) ceil(strlen($total_text) / 4);
    }
    
    /**
     * ========================================
     * STRATEGY-DRIVEN PROMPT METHODS (v2.3.0)
     * ========================================
     */
    
    /**
     * Build outline prompt with strategy
     * 
     * @param array $strategy Strategy object
     * @param string $focus_keyword
     * @param string $topic
     * @return string
     */
    public function build_outline_prompt_with_strategy($strategy, $focus_keyword, $topic = '') {
        $prompt = "Sen profesyonel bir SEO içerik stratejistisin.\n\n";
        
        $prompt .= "## Görev:\n";
        $prompt .= "'{$focus_keyword}' anahtar kelimesi için detaylı blog yazısı outline'ı oluştur.\n\n";
        
        if (!empty($topic)) {
            $prompt .= "## Konu Yönlendirmesi:\n{$topic}\n\n";
        }
        
        // STRATEGY PARAMETERS
        $prompt .= "## Strateji Parametreleri:\n";
        $prompt .= "- Hedef Kelime: {$strategy['target_words']} kelime\n";
        $prompt .= "- H2 Sayısı: {$strategy['h2_count']} ana bölüm\n";
        $prompt .= "- H3 Sayısı: {$strategy['h3_count']} alt bölüm\n";
        $prompt .= "- İçerik Amacı: {$strategy['intent_focus']}\n";
        $prompt .= "- Teknik Seviye: {$strategy['technical_level']}\n";
        $prompt .= "- Jargon Kullanımı: {$strategy['jargon_usage']}\n";
        $prompt .= "- Alt Konu Kapsamı: {$strategy['subtopic_coverage']}\n\n";
        
        // INTENT-SPECIFIC RULES
        $prompt .= "## İçerik Amacı Kuralları:\n";
        if ($strategy['content_intent'] === 'informational') {
            $prompt .= "- Eğitici ve rehber odaklı outline oluştur\n";
            $prompt .= "- 'Nasıl yapılır', 'Nedir', 'Neden' gibi sorulara cevap ver\n";
            $prompt .= "- Adım adım açıklamalar için bölümler ekle\n";
        } elseif ($strategy['content_intent'] === 'commercial') {
            $prompt .= "- Karşılaştırma ve değerlendirme odaklı outline oluştur\n";
            $prompt .= "- 'En iyi', 'Karşılaştırma', 'Avantajlar/Dezavantajlar' bölümleri ekle\n";
            $prompt .= "- Satın alma kararına yardımcı olacak bilgiler ver\n";
        } elseif ($strategy['content_intent'] === 'transactional') {
            $prompt .= "- Dönüşüm ve aksiyon odaklı outline oluştur\n";
            $prompt .= "- Ürün/hizmet özellikleri ve faydaları vurgula\n";
            $prompt .= "- CTA için uygun bölümler planla\n";
        }
        $prompt .= "\n";
        
        // EXPERTISE-SPECIFIC RULES
        $prompt .= "## Uzmanlık Seviyesi Kuralları:\n";
        if ($strategy['expertise_depth'] === 'beginner') {
            $prompt .= "- Temel kavramlardan başla\n";
            $prompt .= "- Her terimi açıkla\n";
            $prompt .= "- Basit örnekler kullan\n";
        } elseif ($strategy['expertise_depth'] === 'intermediate') {
            $prompt .= "- Pratik uygulamalara odaklan\n";
            $prompt .= "- Temel bilgiyi varsay, detaya gir\n";
            $prompt .= "- Gerçek dünya örnekleri ver\n";
        } elseif ($strategy['expertise_depth'] === 'advanced') {
            $prompt .= "- Teknik detaylara gir\n";
            $prompt .= "- İleri seviye kavramları ele al\n";
            $prompt .= "- Karmaşık senaryoları incele\n";
        } elseif ($strategy['expertise_depth'] === 'expert') {
            $prompt .= "- Cutting-edge araştırmalara değin\n";
            $prompt .= "- Uzman terminolojisi kullan\n";
            $prompt .= "- Derinlemesine teknik analiz yap\n";
        }
        $prompt .= "\n";
        
        // OUTPUT FORMAT
        $prompt .= "## Çıktı Formatı:\n";
        $prompt .= "JSON formatında ver:\n";
        $prompt .= "```json\n";
        $prompt .= "{\n";
        $prompt .= '  "h1": "Ana başlık",'."\n";
        $prompt .= '  "sections": ['."\n";
        $prompt .= '    {'."\n";
        $prompt .= '      "h2": "Bölüm başlığı",'."\n";
        $prompt .= '      "h3_list": ["Alt başlık 1", "Alt başlık 2"],'."\n";
        $prompt .= '      "key_points": ["Ana nokta 1", "Ana nokta 2"]'."\n";
        $prompt .= '    }'."\n";
        $prompt .= '  ]'."\n";
        $prompt .= "}\n";
        $prompt .= "```\n\n";
        
        $prompt .= "**ÖNEMLİ:** Sadece JSON çıktısı ver, başka açıklama ekleme.";
        
        error_log("[STRATEGY] Outline prompt built - H2: {$strategy['h2_count']}, H3: {$strategy['h3_count']}, Intent: {$strategy['content_intent']}");
        
        return $prompt;
    }
    
    /**
     * Normalize section title - handle array or string safely
     * 
     * @param mixed $section Section data (string or array)
     * @param int $index Section index for fallback
     * @return string Normalized section title
     */
    private function normalize_section_title($section, $index = 0) {
        // If already string, return trimmed
        if (is_string($section)) {
            return trim(sanitize_text_field($section));
        }
        
        // If array, try to extract title from various possible keys
        if (is_array($section)) {
            $possible_keys = array('title', 'h2', 'heading', 'heading_title', 'section_title', 'name', 'text');
            
            foreach ($possible_keys as $key) {
                if (!empty($section[$key]) && is_string($section[$key])) {
                    return trim(sanitize_text_field($section[$key]));
                }
            }
            
            // If array has numeric keys, try first element
            if (isset($section[0]) && is_string($section[0])) {
                return trim(sanitize_text_field($section[0]));
            }
        }
        
        // Fallback: generate generic section title
        return 'Bölüm ' . ((int)$index + 1);
    }
    
    /**
     * Normalize array elements - ensure all elements are strings
     * 
     * @param array $array Array to normalize
     * @return array Array with all string elements
     */
    private function normalize_array_elements($array) {
        if (!is_array($array)) {
            return array();
        }
        
        $normalized = array();
        
        foreach ($array as $element) {
            if (is_string($element)) {
                $normalized[] = trim($element);
            } elseif (is_array($element)) {
                // If element is array, try to extract string value
                if (isset($element['text'])) {
                    $normalized[] = trim($element['text']);
                } elseif (isset($element['title'])) {
                    $normalized[] = trim($element['title']);
                } elseif (isset($element[0]) && is_string($element[0])) {
                    $normalized[] = trim($element[0]);
                } else {
                    // Convert array to JSON string as last resort
                    $normalized[] = json_encode($element);
                }
            } else {
                // Convert other types to string
                $normalized[] = (string)$element;
            }
        }
        
        return $normalized;
    }
    
    /**
     * Build section prompt with strategy
     * 
     * @param array $strategy Strategy object
     * @param array $section_data Section information
     * @param string $internal_links Internal links text
     * @return string
     */
    public function build_section_prompt_with_strategy($strategy, $section_data, $internal_links = '') {
        // Safe extraction of h2 using normalize function
        $section_index = $section_data['index'] ?? 0;
        
        // Debug: Log section_data to see what we're getting
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[DODO Prompt Builder] Section data received: ' . json_encode($section_data));
        }
        
        $h2 = $this->normalize_section_title($section_data['h2'] ?? '', $section_index);
        
        // Ensure h2 is never empty
        if (empty($h2)) {
            $h2 = 'Bölüm ' . ($section_index + 1);
            error_log("[DODO Prompt Builder] H2 was empty, using fallback: {$h2}");
        }
        
        // Normalize h3_list and key_points arrays
        $h3_list = $this->normalize_array_elements($section_data['h3_list'] ?? array());
        $key_points = $this->normalize_array_elements($section_data['key_points'] ?? array());
        
        $prompt = "Sen profesyonel bir SEO içerik yazarısın.\n\n";
        
        $prompt .= "## Görev:\n";
        $prompt .= "Aşağıdaki bölümü yaz:\n";
        $prompt .= "**H2:** {$h2}\n";
        if (!empty($h3_list)) {
            $prompt .= "**Alt Başlıklar:** " . implode(', ', $h3_list) . "\n";
        }
        if (!empty($key_points)) {
            $prompt .= "**Ana Noktalar:** " . implode(', ', $key_points) . "\n";
        }
        $prompt .= "\n";
        
        // STRATEGY PARAMETERS
        $prompt .= "## Yazım Stratejisi:\n";
        $prompt .= "- Paragraf Uzunluğu: {$strategy['paragraph_length']}\n";
        $prompt .= "- Cümle Yapısı: {$strategy['sentence_structure']}\n";
        $prompt .= "- Cümle Uzunluğu: {$strategy['sentence_length']}\n";
        $prompt .= "- CTA Yoğunluğu: {$strategy['cta_density']}\n";
        $prompt .= "- Dil Stili: {$strategy['language_style']}\n";
        $prompt .= "- Tekrar Önleme: {$strategy['repetition_avoidance']}\n";
        $prompt .= "- Geçiş Kalitesi: {$strategy['transition_quality']}\n";
        $prompt .= "- Ritim Çeşitliliği: {$strategy['rhythm_variation']}\n\n";
        
        // PARAGRAPH LENGTH RULES
        $prompt .= "## Paragraf Kuralları:\n";
        if ($strategy['paragraph_length'] === 'short') {
            $prompt .= "- Her paragraf 2-3 cümle olsun\n";
            $prompt .= "- Kısa ve öz yaz\n";
            $prompt .= "- Sık sık yeni paragraf aç\n";
        } elseif ($strategy['paragraph_length'] === 'medium') {
            $prompt .= "- Her paragraf 4-5 cümle olsun\n";
            $prompt .= "- Dengeli uzunluk kullan\n";
            $prompt .= "- Mantıklı noktalarda paragraf değiştir\n";
        } else {
            $prompt .= "- Her paragraf 6-8 cümle olabilir\n";
            $prompt .= "- Detaylı açıklamalar yap\n";
            $prompt .= "- Derin analiz sun\n";
        }
        $prompt .= "\n";
        
        // SENTENCE STRUCTURE RULES
        $prompt .= "## Cümle Yapısı Kuralları:\n";
        if ($strategy['sentence_structure'] === 'simple_casual') {
            $prompt .= "- Basit cümleler kullan\n";
            $prompt .= "- Günlük dil tercih et\n";
            $prompt .= "- Karmaşık yapılardan kaçın\n";
        } elseif ($strategy['sentence_structure'] === 'balanced_formal') {
            $prompt .= "- Dengeli cümle yapıları kullan\n";
            $prompt .= "- Profesyonel ama anlaşılır yaz\n";
            $prompt .= "- Basit ve karmaşık cümleleri karıştır\n";
        } elseif ($strategy['sentence_structure'] === 'complex_formal') {
            $prompt .= "- Karmaşık cümle yapıları kullan\n";
            $prompt .= "- Teknik ve formal dil tercih et\n";
            $prompt .= "- Yan cümleler ve bağlaçlar kullan\n";
        } elseif ($strategy['sentence_structure'] === 'dynamic_engaging') {
            $prompt .= "- Dinamik ve çekici cümleler kullan\n";
            $prompt .= "- Çeşitli yapılar karıştır\n";
            $prompt .= "- Okuyucuyu meşgul et\n";
        }
        $prompt .= "\n";
        
        // CTA DENSITY RULES
        $prompt .= "## CTA Kuralları:\n";
        if ($strategy['cta_density'] === 'low') {
            $prompt .= "- Minimal CTA kullan (varsa 1 tane)\n";
            $prompt .= "- Bilgilendirmeye odaklan\n";
        } elseif ($strategy['cta_density'] === 'medium') {
            $prompt .= "- Dengeli CTA kullan (1-2 tane)\n";
            $prompt .= "- Doğal noktalarda ekle\n";
        } else {
            $prompt .= "- Aktif CTA kullan (2-3 tane)\n";
            $prompt .= "- Aksiyona yönlendir\n";
        }
        $prompt .= "\n";
        
        // NATURALNESS RULES
        $prompt .= "## Doğallık Kuralları:\n";
        if ($strategy['repetition_avoidance'] === 'high') {
            $prompt .= "- Aynı kelimeleri tekrar etme\n";
            $prompt .= "- Sinonim kullan\n";
            $prompt .= "- Çeşitli ifadeler tercih et\n";
        }
        if ($strategy['transition_quality'] === 'natural_flowing') {
            $prompt .= "- Doğal geçişler kullan\n";
            $prompt .= "- Cümleler akıcı bağlansın\n";
            $prompt .= "- Mantıksal akış sağla\n";
        }
        if ($strategy['rhythm_variation'] === 'dynamic') {
            $prompt .= "- Cümle uzunluklarını değiştir\n";
            $prompt .= "- Ritim çeşitliliği oluştur\n";
            $prompt .= "- Monotonluktan kaçın\n";
        }
        $prompt .= "\n";
        
        // INTERNAL LINKS
        if (!empty($internal_links)) {
            $prompt .= "## İç Linkler:\n";
            $prompt .= "Aşağıdaki iç linkleri doğal şekilde içeriğe yerleştir:\n";
            $prompt .= $internal_links . "\n\n";
        }
        
        // OUTPUT FORMAT
        $prompt .= "## Çıktı:\n";
        $prompt .= "Markdown formatında sadece bu bölümün içeriğini yaz.\n";
        $prompt .= "H2 ve H3 başlıklarını dahil et.\n";
        $prompt .= "**ÖNEMLİ:** Sadece içerik ver, JSON veya başka format kullanma.";
        
        error_log("[STRATEGY] Section prompt built - H2: {$h2}, Paragraph: {$strategy['paragraph_length']}, CTA: {$strategy['cta_density']}");
        
        return $prompt;
    }
    
    /**
     * Build FAQ prompt with strategy (v2.3.0 - Brain-enhanced)
     * 
     * @param array $strategy Strategy object
     * @param array $params Generation parameters
     * @param array|null $brain_analysis Brain Core analysis (v2.3.0)
     * @return string
     */
    public function build_faq_prompt_with_strategy($strategy, $params, $brain_analysis = null) {
        $focus_keyword = $params['focus_keyword'] ?? '';
        
        $prompt = "Sen SEO uzmanı bir içerik yazarısın.\n\n";
        
        $prompt .= "## Görev:\n";
        $prompt .= "'{$focus_keyword}' konusu için FAQ bölümü oluştur.\n\n";
        
        // BRAIN CORE CONTEXT (v2.3.0 - Phase 2)
        if ($brain_analysis) {
            $prompt .= $this->build_brain_context_for_faq($brain_analysis);
        }
        
        // STRATEGY PARAMETERS
        $prompt .= "## Strateji:\n";
        $prompt .= "- FAQ Sayısı: {$strategy['faq_count']} soru-cevap\n";
        $prompt .= "- Konuşma Sorguları: {$strategy['conversational_queries']} adet\n";
        $prompt .= "- GEO Seviyesi: {$strategy['geo_optimization']}\n";
        $prompt .= "- Featured Snippet: {$strategy['featured_snippet_optimization']}\n\n";
        
        // GEO OPTIMIZATION RULES (Brain-enhanced)
        $prompt .= "## GEO Optimizasyon Kuralları:\n";
        if ($strategy['geo_optimization'] === 'aggressive') {
            $prompt .= "- Konuşma dilinde sorular oluştur\n";
            $prompt .= "- 'Nasıl', 'Neden', 'Ne zaman' gibi sorular ekle\n";
            $prompt .= "- Kısa ve net cevaplar ver (40-60 kelime)\n";
            $prompt .= "- Featured snippet için optimize et\n";
            $prompt .= "- AI answer engines için format kullan\n";
        } elseif ($strategy['geo_optimization'] === 'moderate') {
            $prompt .= "- Dengeli soru-cevap formatı kullan\n";
            $prompt .= "- Hem kısa hem detaylı cevaplar ver\n";
            $prompt .= "- Doğal dil kullan\n";
        } else {
            $prompt .= "- Standart FAQ formatı kullan\n";
            $prompt .= "- Detaylı cevaplar ver\n";
        }
        $prompt .= "\n";
        
        // OUTPUT FORMAT
        $prompt .= "## Çıktı Formatı:\n";
        $prompt .= "JSON formatında ver:\n";
        $prompt .= "```json\n";
        $prompt .= "{\n";
        $prompt .= '  "faq": ['."\n";
        $prompt .= '    {"question": "Soru 1?", "answer": "Cevap 1"},'."\n";
        $prompt .= '    {"question": "Soru 2?", "answer": "Cevap 2"}'."\n";
        $prompt .= '  ]'."\n";
        $prompt .= "}\n";
        $prompt .= "```\n\n";
        
        $prompt .= "**ÖNEMLİ:** Sadece JSON çıktısı ver, başka açıklama ekleme.";
        
        error_log("[STRATEGY] FAQ prompt built - Count: {$strategy['faq_count']}, GEO: {$strategy['geo_optimization']}");
        
        return $prompt;
    }
    
    /**
     * Build introduction prompt with strategy
     * 
     * @param string $focus_keyword
     * @param string $topic
     * @param array $strategy
     * @return string
     */
    public function build_introduction_prompt($focus_keyword, $topic, $strategy) {
        $prompt = "Sen profesyonel bir SEO içerik yazarısın.\n\n";
        
        $prompt .= "## Görev:\n";
        $prompt .= "'{$focus_keyword}' konusu için ilgi çekici bir giriş paragrafı yaz.\n\n";
        
        // STRATEGY PARAMETERS
        $prompt .= "## Strateji:\n";
        $prompt .= "- Hedef Kelime: {$strategy['intro_length']} kelime\n";
        $prompt .= "- Ton: {$strategy['tone']}\n";
        $prompt .= "- İçerik Amacı: {$strategy['content_intent']}\n";
        $prompt .= "- Uzmanlık Seviyesi: {$strategy['expertise_depth']}\n";
        $prompt .= "- Okunabilirlik: {$strategy['readability_target']}\n\n";
        
        // TONE-BASED APPROACH
        $prompt .= "## Ton Kuralları:\n";
        if ($strategy['tone'] === 'technical') {
            $prompt .= "- Profesyonel ve teknik dil kullan\n";
            $prompt .= "- Uzmanlık göster\n";
        } elseif ($strategy['tone'] === 'conversational') {
            $prompt .= "- Samimi ve konuşma dilinde yaz\n";
            $prompt .= "- Okuyucuyla bağ kur\n";
        } elseif ($strategy['tone'] === 'persuasive') {
            $prompt .= "- İkna edici dil kullan\n";
            $prompt .= "- Okuyucunun ilgisini çek\n";
        }
        $prompt .= "\n";
        
        error_log("[STRATEGY] Introduction prompt built - Length: {$strategy['intro_length']}, Tone: {$strategy['tone']}");
        
        return $prompt;
    }
    
    /**
     * Build conclusion prompt with strategy
     * 
     * @param string $focus_keyword
     * @param string $topic
     * @param array $strategy
     * @return string
     */
    public function build_conclusion_prompt($focus_keyword, $topic, $strategy) {
        $prompt = "Sen profesyonel bir SEO içerik yazarısın.\n\n";
        
        $prompt .= "## Görev:\n";
        $prompt .= "'{$focus_keyword}' konusu için güçlü bir sonuç bölümü yaz.\n\n";
        
        // STRATEGY PARAMETERS
        $prompt .= "## Strateji:\n";
        $prompt .= "- Hedef Kelime: {$strategy['conclusion_length']} kelime\n";
        $prompt .= "- CTA Yoğunluğu: {$strategy['cta_density']}\n";
        $prompt .= "- İçerik Amacı: {$strategy['content_intent']}\n";
        $prompt .= "- Ton: {$strategy['tone']}\n\n";
        
        // CTA DENSITY RULES
        $prompt .= "## CTA Kuralları:\n";
        if ($strategy['cta_density'] === 'high' || $strategy['content_intent'] === 'transactional') {
            $prompt .= "- Güçlü harekete geçirici mesaj ekle\n";
            $prompt .= "- Okuyucuyu aksiyona teşvik et\n";
            $prompt .= "- Net bir sonraki adım belirt\n";
        } elseif ($strategy['cta_density'] === 'medium' || $strategy['content_intent'] === 'commercial') {
            $prompt .= "- Yumuşak bir harekete geçirici mesaj ekle\n";
            $prompt .= "- Öneri niteliğinde ol\n";
        } else {
            $prompt .= "- Bilgilendirici bir kapanış yap\n";
            $prompt .= "- Özet ve değerlendirme yap\n";
        }
        $prompt .= "\n";
        
        error_log("[STRATEGY] Conclusion prompt built - Length: {$strategy['conclusion_length']}, CTA: {$strategy['cta_density']}");
        
        return $prompt;
    }
    
    /**
     * Build Brain Core context for outline (v2.3.0 - Phase 2)
     * 
     * @param array $brain_analysis Brain analysis result
     * @return string Brain context prompt section
     */
    private function build_brain_context_for_outline($brain_analysis) {
        $context = "## 🧠 AI Strateji Analizi:\n";
        
        // Intent analysis
        if (isset($brain_analysis['intent'])) {
            $intent = $brain_analysis['intent'];
            $context .= "- Tespit Edilen Amaç: {$intent['primary_intent']} ({$intent['confidence']}% güven)\n";
        }
        
        // Content strategy
        if (isset($brain_analysis['content_strategy'])) {
            $strategy = $brain_analysis['content_strategy'];
            $format = $strategy['recommended_format'];
            
            $context .= "- Önerilen Format: {$format}\n";
            
            // Format-specific outline instructions
            if ($format === 'comparison') {
                $context .= "- ÖZEL TALİMAT: Karşılaştırma yapısı kullan\n";
                $context .= "  * 'X vs Y' tarzı başlıklar\n";
                $context .= "  * 'Hangisi Daha İyi?' bölümü\n";
                $context .= "  * 'Avantajlar ve Dezavantajlar' karşılaştırması\n";
                $context .= "  * Karşılaştırma tablosu için bölüm\n";
            } elseif ($format === 'how_to_guide') {
                $context .= "- ÖZEL TALİMAT: Adım adım rehber yapısı kullan\n";
                $context .= "  * 'Nasıl Yapılır' odaklı başlıklar\n";
                $context .= "  * Numaralı adımlar\n";
                $context .= "  * 'Gerekli Malzemeler/Ön Koşullar' bölümü\n";
                $context .= "  * 'İpuçları ve Püf Noktaları' bölümü\n";
            } elseif ($format === 'listicle') {
                $context .= "- ÖZEL TALİMAT: Liste formatı kullan\n";
                $context .= "  * 'En İyi X' veya 'X Yöntem' tarzı başlıklar\n";
                $context .= "  * Numaralı liste öğeleri\n";
                $context .= "  * Her öğe için detaylı açıklama\n";
            } elseif ($format === 'glossary') {
                $context .= "- ÖZEL TALİMAT: Tanım ve açıklama yapısı kullan\n";
                $context .= "  * 'Nedir?' başlığı\n";
                $context .= "  * 'Detaylı Açıklama' bölümü\n";
                $context .= "  * 'Örnekler' bölümü\n";
                $context .= "  * 'İlgili Kavramlar' bölümü\n";
            }
        }
        
        // GEO optimization
        if (isset($brain_analysis['geo'])) {
            $geo_score = $brain_analysis['geo']['overall_score'];
            if ($geo_score >= 70) {
                $context .= "- GEO Optimizasyon: YÜKSEK ({$geo_score}/100)\n";
                $context .= "  * Konuşma dili sorularını başlık olarak kullan\n";
                $context .= "  * 'Ne', 'Nasıl', 'Neden', 'Hangi' ile başlayan başlıklar ekle\n";
                $context .= "  * Kısa, net cevap verilebilecek bölümler oluştur\n";
            }
        }
        
        // Topical authority gaps
        if (isset($brain_analysis['topical_authority']['gap_analysis'])) {
            $gaps = $brain_analysis['topical_authority']['gap_analysis'];
            if ($gaps['has_gaps']) {
                $context .= "- Tespit Edilen Eksiklikler:\n";
                foreach ($gaps['gaps'] as $gap) {
                    if ($gap['type'] === 'missing_subtopics' && !empty($gap['items'])) {
                        $context .= "  * Eksik alt konular: " . implode(', ', array_slice($gap['items'], 0, 3)) . "\n";
                    }
                }
            }
        }
        
        $context .= "\n";
        
        return $context;
    }
    
    /**
     * Build Brain Core context for FAQ (v2.3.0 - Phase 2)
     * 
     * @param array $brain_analysis Brain analysis result
     * @return string Brain context prompt section
     */
    private function build_brain_context_for_faq($brain_analysis) {
        $context = "## 🧠 AI Strateji Analizi (FAQ):\n";
        
        // GEO optimization for FAQ with safe extraction
        if (isset($brain_analysis['geo'])) {
            $geo_score = $brain_analysis['geo']['overall_score'] ?? $brain_analysis['geo']['score'] ?? 0;
            
            if ($geo_score >= 80) {
                $context .= "- GEO Optimizasyon: ÇOK YÜKSEK ({$geo_score}/100)\n";
                $context .= "- ÖZEL TALİMAT: Featured Snippet formatı kullan\n";
                $context .= "  * Sorular doğal konuşma dilinde olsun\n";
                $context .= "  * Cevaplar 40-60 kelime arası, kısa ve net\n";
                $context .= "  * İlk cümle direkt cevap versin\n";
                $context .= "  * AI answer engines için optimize et\n";
                $context .= "  * 'Kim', 'Ne', 'Nerede', 'Ne zaman', 'Nasıl', 'Neden' sorularını kullan\n";
            } elseif ($geo_score >= 60) {
                $context .= "- GEO Optimizasyon: ORTA ({$geo_score}/100)\n";
                $context .= "  * Sorular konuşma dilinde olsun\n";
                $context .= "  * Cevaplar net ve anlaşılır olsun\n";
            }
        }
        
        // Intent-based FAQ style with safe extraction
        if (isset($brain_analysis['intent'])) {
            $intent = $brain_analysis['intent']['primary_intent'] 
                   ?? $brain_analysis['intent']['intent'] 
                   ?? $brain_analysis['intent']['type']
                   ?? 'informational';
            
            if ($intent === 'transactional') {
                $context .= "- İçerik Amacı: Transactional\n";
                $context .= "  * Fiyat, satın alma, hizmet ile ilgili sorular ekle\n";
                $context .= "  * 'Nasıl satın alınır?', 'Fiyatı nedir?' gibi sorular\n";
            } elseif ($intent === 'informational') {
                $context .= "- İçerik Amacı: Informational\n";
                $context .= "  * Eğitici ve açıklayıcı sorular ekle\n";
                $context .= "  * 'Nedir?', 'Nasıl çalışır?', 'Neden önemli?' gibi sorular\n";
            }
        }
        
        $context .= "\n";
        
        return $context;
    }
}
