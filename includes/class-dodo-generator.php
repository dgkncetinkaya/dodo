<?php
/**
 * Generator Sınıfı
 * 
 * Blog oluşturma sürecini orkestra eder
 *
 * @package DODO_AI_SEO
 * @since 1.0.0
 */

// Doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

// Duplicate class guard
if (class_exists('DODO_Generator')) {
    return;
}

class DODO_Generator {
    
    /**
     * OpenAI instance
     */
    private $openai;
    
    /**
     * Internal Links instance
     */
    private $internal_links;
    
    /**
     * Prompt Builder instance
     */
    private $prompt_builder;
    
    /**
     * Post Creator instance
     */
    private $post_creator;
    
    /**
     * Rank Math instance
     */
    private $rankmath;
    
    /**
     * Settings instance
     */
    private $settings;
    
    /**
     * Answer Block Engine instance
     */
    private $answer_block_engine;
    
    /**
     * Generation Strategy instance
     */
    private $strategy_builder;
    
    /**
     * Current generation strategy
     */
    private $strategy;
    
    /**
     * Brain Core instance (v2.3.0 - Phase 2)
     */
    private $brain;
    
    /**
     * Brain Strategy Mapper instance (v2.3.0 - Phase 2)
     */
    private $brain_mapper;
    
    /**
     * Brain analysis result
     */
    private $brain_analysis;
    
    /**
     * Constructor
     */
    public function __construct() {
        try {
            // Check required classes
            $required_classes = array(
                'DODO_OpenAI',
                'DODO_Internal_Links',
                'DODO_Prompt_Builder',
                'DODO_Post_Creator',
                'DODO_RankMath',
                'DODO_Settings',
                'DODO_Answer_Block_Engine',
                'DODO_Generation_Strategy'
            );
            
            $missing_classes = array();
            foreach ($required_classes as $class_name) {
                if (!class_exists($class_name)) {
                    $missing_classes[] = $class_name;
                    error_log("[DODO Generator] Missing required class: {$class_name}");
                }
            }
            
            if (!empty($missing_classes)) {
                throw new Exception('Missing required classes: ' . implode(', ', $missing_classes));
            }
            
            // Initialize dependencies
            $this->openai = new DODO_OpenAI();
            $this->internal_links = new DODO_Internal_Links();
            $this->prompt_builder = new DODO_Prompt_Builder();
            $this->post_creator = new DODO_Post_Creator();
            $this->rankmath = new DODO_RankMath();
            $this->settings = new DODO_Settings();
            $this->answer_block_engine = new DODO_Answer_Block_Engine();
            $this->strategy_builder = new DODO_Generation_Strategy();
            
            error_log('[DODO Generator] All dependencies initialized successfully');
            
            // Initialize Brain Core (v2.3.0 - Phase 2)
            $this->init_brain_core();
            
        } catch (Exception $e) {
            error_log('[DODO Generator] Constructor failed: ' . $e->getMessage());
            throw $e; // Re-throw to be caught by AJAX handler
        }
    }
    
    /**
     * Initialize Brain Core with fallback
     */
    private function init_brain_core() {
        try {
            require_once plugin_dir_path(__FILE__) . 'brain/class-dodo-brain-core.php';
            require_once plugin_dir_path(__FILE__) . 'class-dodo-brain-strategy-mapper.php';
            
            $this->brain = new Dodo_Brain_Core();
            $this->brain_mapper = new DODO_Brain_Strategy_Mapper();
            
            error_log('[DODO Generator] Brain Core initialized successfully');
        } catch (Exception $e) {
            error_log('[DODO Generator] Brain Core initialization failed: ' . $e->getMessage());
            $this->brain = null;
            $this->brain_mapper = null;
        }
    }
    
    /**
     * Blog yazısı oluştur (ana metod)
     * 
     * @param array $params Kullanıcı parametreleri
     * @return array|WP_Error Sonuç veya hata
     */
    public function generate_blog($params) {
        try {
            // 1. Parametreleri validate et
            $validated_params = $this->validate_params($params);
            if (is_wp_error($validated_params)) {
                return $validated_params;
            }
            
            // 2. BRAIN CORE ANALYSIS (v2.3.0 - Phase 2)
            $this->brain_analysis = $this->run_brain_analysis($validated_params);
            
            // 3. CHECK CANNIBALIZATION RISK
            $cannibalization_warning = $this->check_cannibalization_risk($this->brain_analysis);
            if ($cannibalization_warning && !($validated_params['override_cannibalization'] ?? false)) {
                // Return warning to user (will be handled by UI)
                return new WP_Error(
                    'cannibalization_risk',
                    $cannibalization_warning['message'],
                    $cannibalization_warning
                );
            }
            
            // 4. BRAIN → STRATEGY MAPPING
            if ($this->brain_analysis && $this->brain_mapper) {
                $validated_params = $this->brain_mapper->map_to_strategy($this->brain_analysis, $validated_params);
                error_log('[DODO Generator] Brain-enhanced parameters applied');
            }
            
            // 5. BUILD STRATEGY OBJECT - Advanced settings + Brain recommendations
            $this->strategy = $this->strategy_builder->build_strategy($validated_params);
            error_log('DODO: Strategy object built successfully (Brain-enhanced: ' . ($validated_params['brain_enhanced'] ?? false ? 'YES' : 'NO') . ')');
            
            // 6. İç linkleri bul
            $internal_links = $this->internal_links->find_relevant_links($validated_params);
            $internal_links_text = $this->internal_links->format_links_for_ai($internal_links);
            
            // 7. Multi-step generation ile içerik oluştur - BRAIN-ENHANCED STRATEGY İLE
            error_log('DODO: Multi-step generation başlıyor (Brain + Strategy-driven)...');
            $generated_content = $this->generate_content_multi_step($validated_params, $internal_links_text);
            
            if (is_wp_error($generated_content)) {
                return $generated_content;
            }
            
            // 5. Kelime sayısını validate et - STRATEGY'den min_words kullan
            $word_count_validation = $this->validate_word_count_with_strategy($generated_content);
            
            if (is_wp_error($word_count_validation)) {
                // Log ve hata döndür - POST OLUŞTURMA
                error_log('DODO: Kelime sayısı yetersiz - ' . $word_count_validation->get_error_message());
                return $word_count_validation;
            }
            
            // 4.5. AI Answer Blocks ekle (Sprint 5 - Phase 2)
            // FORM OVERRIDE: params'dan gelen ayarlar settings'den üstün
            $block_settings = $this->answer_block_engine->get_block_settings();
            
            // Override with form params if provided
            if (isset($validated_params['answer_blocks_enabled'])) {
                $block_settings['enabled'] = $validated_params['answer_blocks_enabled'];
                error_log('DODO: Answer Blocks enabled overridden by form: ' . ($block_settings['enabled'] ? 'YES' : 'NO'));
            }
            if (isset($validated_params['block_short_answer'])) {
                $block_settings['short_answer'] = $validated_params['block_short_answer'];
                error_log('DODO: Short Answer overridden by form: ' . ($block_settings['short_answer'] ? 'YES' : 'NO'));
            }
            if (isset($validated_params['block_faq'])) {
                $block_settings['faq'] = $validated_params['block_faq'];
                error_log('DODO: FAQ Block overridden by form: ' . ($block_settings['faq'] ? 'YES' : 'NO'));
            }
            
            if ($block_settings['enabled']) {
                error_log('DODO: AI Answer Blocks oluşturuluyor...');
                $generated_content = $this->add_answer_blocks($generated_content, $validated_params, $block_settings);
            } else {
                error_log('DODO: AI Answer Blocks disabled (form or settings)');
            }
            
            // 5. İçeriği hazırla
            $post_data = $this->prepare_post_data($generated_content, $validated_params);
            $seo_data = $this->prepare_seo_data($generated_content, $validated_params);
            
            // Focus keyword'ü logla
            error_log("DODO: Generator - Focus keyword: {$validated_params['focus_keyword']}");
            error_log("DODO: Generator - SEO data focus keyword: {$seo_data['focus_keyword']}");
            
            // 6. Post'u oluştur
            $post_id = $this->post_creator->create_post($post_data, $seo_data);
            
            if (is_wp_error($post_id)) {
                return $post_id;
            }
            
            // 7. Generate AI Strategy Report (v2.3.0 - Phase 2)
            $strategy_report = $this->generate_strategy_report($validated_params);
            
            // 8. Start Impact Tracking (Phase 5)
            $this->start_impact_tracking($post_id, $validated_params, $this->strategy);
            
            // 9. Record Learning Event
            $this->record_learning_event($post_id, $validated_params, $this->strategy, $generated_content);
            
            // 10. Başarı sonucu döndür
            return array(
                'success' => true,
                'post_id' => $post_id,
                'post_url' => get_permalink($post_id),
                'edit_url' => get_edit_post_link($post_id, 'raw'),
                'stats' => $this->post_creator->get_post_stats($post_id),
                'internal_links_count' => $this->internal_links->get_total_link_count($internal_links),
                'brain_enhanced' => $this->brain_analysis !== null,
                'learning_applied' => $this->strategy['learning_applied'] ?? false,
                'strategy_report' => $strategy_report,
            );
            
        } catch (Exception $e) {
            return new WP_Error('generation_failed', $e->getMessage());
        }
    }
    
    /**
     * Multi-step generation: İçeriği adım adım oluştur
     * 
     * @param array $params
     * @param string $internal_links_text
     * @return array|WP_Error
     */
    private function generate_content_multi_step($params, $internal_links_text) {
        // === PHASE 5: LEARNED STRATEGY INTEGRATION ===
        error_log("DODO: === PHASE 5 LEARNING INTEGRATION START ===");
        
        // 1. Get base strategy (already built)
        $base_strategy = $this->strategy;
        error_log("DODO: Base strategy loaded");
        
        // 2. Get learned strategy from Phase 5
        $learned_strategy = $this->get_learned_strategy($params);
        
        // 3. Merge strategies with priority: User > Safety > Brain > Learned > Default
        $this->strategy = $this->merge_strategies($base_strategy, $learned_strategy, $params);
        
        // 4. Log final merged strategy
        error_log("DODO: === FINAL MERGED STRATEGY (User > Safety > Brain > Learned > Default) ===");
        error_log("DODO: Strategy Target Words: {$this->strategy['target_words']}");
        error_log("DODO: Strategy Min Words: {$this->strategy['min_words']}");
        error_log("DODO: Strategy H2 Count: {$this->strategy['h2_count']}");
        error_log("DODO: Strategy H3 Count: {$this->strategy['h3_count']}");
        error_log("DODO: Strategy FAQ Count: {$this->strategy['faq_count']}");
        error_log("DODO: Strategy CTA Density: {$this->strategy['cta_density']}");
        error_log("DODO: Strategy GEO Optimization: {$this->strategy['geo_optimization']}");
        error_log("DODO: Strategy Semantic: {$this->strategy['semantic_aggressiveness']}");
        error_log("DODO: Strategy Readability: {$this->strategy['readability_target']}");
        error_log("DODO: Strategy Tone: {$this->strategy['tone']}");
        error_log("DODO: Strategy Intent: {$this->strategy['content_intent']}");
        
        // STEP 1: Outline oluştur - STRATEGY İLE
        error_log('DODO: Step 1 - Outline oluşturuluyor (strategy-driven)...');
        $outline = $this->generate_outline($params);
        
        if (is_wp_error($outline)) {
            return $outline;
        }
        
        // STEP 2: Giriş bölümü oluştur - STRATEGY İLE
        error_log('DODO: Step 2 - Giriş bölümü oluşturuluyor (strategy-driven)...');
        $intro = $this->generate_introduction($params, $outline);
        
        if (is_wp_error($intro)) {
            return $intro;
        }
        
        // STEP 3: Ana bölümleri oluştur - STRATEGY İLE
        error_log('DODO: Step 3 - Ana bölümler oluşturuluyor (strategy-driven)...');
        $main_sections = $this->generate_main_sections($params, $outline, $internal_links_text);
        
        if (is_wp_error($main_sections)) {
            return $main_sections;
        }
        
        // STEP 4: FAQ bölümü oluştur - STRATEGY İLE
        error_log('DODO: Step 4 - FAQ bölümü oluşturuluyor (strategy-driven)...');
        error_log("DODO: FAQ Count from strategy: {$this->strategy['faq_count']}");
        $faq = $this->generate_faq($params);
        
        if (is_wp_error($faq)) {
            return $faq;
        }
        
        // STEP 5: Sonuç bölümü oluştur - STRATEGY İLE
        error_log('DODO: Step 5 - Sonuç bölümü oluşturuluyor (strategy-driven)...');
        $conclusion = $this->generate_conclusion($params, $outline);
        
        if (is_wp_error($conclusion)) {
            return $conclusion;
        }
        
        // STEP 6: SEO meta verileri oluştur - STRATEGY İLE
        error_log('DODO: Step 6 - SEO meta verileri oluşturuluyor (strategy-driven)...');
        $seo_meta = $this->generate_seo_meta($params);
        
        // SEO meta başarısız olsa bile devam et - fallback kullan
        if (is_wp_error($seo_meta)) {
            error_log('DODO WARNING: SEO meta generation failed: ' . $seo_meta->get_error_message());
            error_log('DODO: Using fallback SEO meta...');
            
            // Fallback SEO meta oluştur
            $seo_meta = $this->generate_fallback_seo_meta($params, $intro);
            error_log('DODO: Fallback SEO meta created successfully');
        }
        
        // STEP 7: Tüm içeriği birleştir
        $full_content = $intro . "\n\n" . $main_sections . "\n\n" . $faq['content'] . "\n\n" . $conclusion;
        
        // Kelime sayısını logla
        $word_count = count(preg_split('/\s+/u', trim(strip_tags($full_content)), -1, PREG_SPLIT_NO_EMPTY));
        error_log('DODO: Multi-step generation tamamlandı. Toplam kelime: ' . $word_count);
        
        return array(
            'seo_title' => $seo_meta['seo_title'],
            'meta_description' => $seo_meta['meta_description'],
            'slug' => $seo_meta['slug'],
            'excerpt' => $seo_meta['excerpt'],
            'content' => $full_content,
            'focus_keyword' => $params['focus_keyword'],
            'faq' => $faq['faq_array'],
            'tags' => $seo_meta['tags'],
            'seo_meta_fallback' => isset($seo_meta['is_fallback']) ? true : false,
        );
    }
    
    /**
     * Parametreleri validate et
     * 
     * @param array $params
     * @return array|WP_Error
     */
    private function validate_params($params) {
        $errors = array();
        
        // Focus keyword'ü logla
        error_log("=== DODO GENERATOR validate_params START ===");
        error_log("DODO GENERATOR: params['focus_keyword'] RAW: " . (isset($params['focus_keyword']) ? $params['focus_keyword'] : 'NOT SET'));
        
        // Input sanitization
        $params['focus_keyword'] = isset($params['focus_keyword']) ? sanitize_text_field($params['focus_keyword']) : '';
        $params['topic'] = isset($params['topic']) ? sanitize_textarea_field($params['topic']) : '';
        $params['length'] = isset($params['length']) ? sanitize_text_field($params['length']) : '';
        $params['tone'] = isset($params['tone']) ? sanitize_text_field($params['tone']) : '';
        $params['content_type'] = isset($params['content_type']) ? sanitize_text_field($params['content_type']) : 'blog';
        $params['post_status'] = isset($params['post_status']) ? sanitize_text_field($params['post_status']) : 'draft';
        $params['category_id'] = isset($params['category_id']) ? absint($params['category_id']) : 0;
        $params['publish_mode'] = isset($params['publish_mode']) ? sanitize_text_field($params['publish_mode']) : 'draft';
        $params['queue_position'] = isset($params['queue_position']) ? absint($params['queue_position']) : 0;
        
        // Advanced Generation Controls (Sprint 3 - Task 6)
        $params['content_intent'] = isset($params['content_intent']) ? sanitize_text_field($params['content_intent']) : 'informational';
        $params['expertise_depth'] = isset($params['expertise_depth']) ? sanitize_text_field($params['expertise_depth']) : 'intermediate';
        $params['geo_optimization'] = isset($params['geo_optimization']) ? sanitize_text_field($params['geo_optimization']) : 'moderate';
        $params['ai_naturalness'] = isset($params['ai_naturalness']) ? sanitize_text_field($params['ai_naturalness']) : 'human';
        $params['semantic_aggressiveness'] = isset($params['semantic_aggressiveness']) ? sanitize_text_field($params['semantic_aggressiveness']) : 'moderate';
        $params['readability_target'] = isset($params['readability_target']) ? sanitize_text_field($params['readability_target']) : 'easy';
        
        error_log("DODO GENERATOR: params['focus_keyword'] AFTER SANITIZE: '{$params['focus_keyword']}'");
        error_log("DODO GENERATOR: Focus keyword boş mu? " . (empty($params['focus_keyword']) ? 'EVET - BOŞ!' : 'Hayır - Dolu'));
        
        // Odak anahtar kelime - ZORUNLU
        if (empty($params['focus_keyword'])) {
            $errors[] = __('Odak anahtar kelime zorunludur.', 'dodo-ai-seo');
            error_log("DODO GENERATOR ERROR: Focus keyword boş!");
        }
        
        // Topic - OPSİYONEL (boş olabilir, AI otomatik yön belirler)
        if (empty($params['topic'])) {
            error_log("DODO GENERATOR: Topic boş, AI otomatik yön belirleyecek");
        }
        
        // Uzunluk limiti (prompt injection koruması)
        if (!empty($params['topic']) && strlen($params['topic']) > 1000) {
            $errors[] = __('Yazı konusu çok uzun (maksimum 1000 karakter).', 'dodo-ai-seo');
        }
        
        if (strlen($params['focus_keyword']) > 200) {
            $errors[] = __('Odak anahtar kelime çok uzun (maksimum 200 karakter).', 'dodo-ai-seo');
        }
        
        // Hata varsa döndür
        if (!empty($errors)) {
            return new WP_Error('validation_failed', implode(' ', $errors));
        }
        
        // Varsayılan değerleri ekle
        $defaults = array(
            'length' => $this->settings->get_setting('default_length', 'medium'),
            'tone' => $this->settings->get_setting('default_tone', 'technical'),
            'content_type' => 'blog',
            'post_status' => 'draft', // GÜVENLİK: Her zaman draft, kullanıcı özellikle seçmedikçe
            'category_id' => 0,
            'publish_mode' => $this->settings->get_setting('default_publish_mode', 'draft'),
            'queue_position' => 0,
            // Advanced Generation Controls defaults
            'content_intent' => 'informational',
            'expertise_depth' => 'intermediate',
            'geo_optimization' => 'moderate',
            'ai_naturalness' => 'human',
            'semantic_aggressiveness' => 'moderate',
            'readability_target' => 'easy',
        );
        
        $validated = wp_parse_args($params, $defaults);
        
        // Post status güvenlik kontrolü - publish ancak açıkça seçilmişse
        if (isset($params['post_status']) && $params['post_status'] === 'publish') {
            // Kullanıcı özellikle publish seçmiş, izin ver
            $validated['post_status'] = 'publish';
        } else {
            // Diğer tüm durumlarda draft
            $validated['post_status'] = 'draft';
        }
        
        // Allowed values kontrolü
        $allowed_lengths = array('short', 'medium', 'long');
        if (!in_array($validated['length'], $allowed_lengths)) {
            $validated['length'] = 'medium';
        }
        
        $allowed_tones = array('technical', 'informative', 'commercial', 'friendly', 'corporate', 'expert');
        if (!in_array($validated['tone'], $allowed_tones)) {
            $validated['tone'] = 'technical';
        }
        
        $allowed_content_types = array('blog', 'howto', 'comparison', 'listicle', 'guide', 'category', 'faq');
        if (!in_array($validated['content_type'], $allowed_content_types)) {
            $validated['content_type'] = 'blog';
        }
        
        $allowed_statuses = array('draft', 'publish');
        if (!in_array($validated['post_status'], $allowed_statuses)) {
            $validated['post_status'] = 'draft';
        }
        
        return $validated;
    }
    
    /**
     * Run Brain Core analysis (v2.3.0 - Phase 2)
     * 
     * @param array $params Validated parameters
     * @return array|null Brain analysis or null if Brain unavailable
     */
    private function run_brain_analysis($params) {
        if (!$this->brain) {
            error_log('[DODO Generator] Brain Core not available, skipping analysis');
            return null;
        }
        
        try {
            $keyword = $params['focus_keyword'];
            
            error_log("[DODO Generator] Running Brain analysis for keyword: {$keyword}");
            
            $analysis = $this->brain->analyze_keyword($keyword);
            
            error_log(sprintf(
                '[DODO Generator] Brain analysis complete | Overall Score: %d | Intent: %s | GEO: %d',
                $analysis['overall_score'] ?? 0,
                $analysis['intent']['primary_intent'] ?? 'unknown',
                $analysis['geo']['overall_score'] ?? 0
            ));
            
            return $analysis;
            
        } catch (Exception $e) {
            error_log('[DODO Generator] Brain analysis failed: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Check cannibalization risk (v2.3.0 - Phase 2)
     * 
     * @param array|null $brain_analysis Brain analysis result
     * @return array|null Warning data or null if no risk
     */
    private function check_cannibalization_risk($brain_analysis) {
        if (!$brain_analysis || !$this->brain_mapper) {
            return null;
        }
        
        $warning = $this->brain_mapper->get_cannibalization_warning($brain_analysis);
        
        if ($warning) {
            error_log(sprintf(
                '[DODO Generator] Cannibalization risk detected | Level: %s | Score: %d',
                $warning['risk_level'],
                $warning['risk_score']
            ));
        }
        
        return $warning;
    }
    
    /**
     * AI yanıtını parse et
     * 
     * @param string $response
     * @return array|WP_Error
     */
    private function parse_ai_response($response) {
        // Ham yanıtı kaydet (fallback için)
        $raw_response = $response;
        
        // JSON bloğunu bul
        if (preg_match('/```json\s*(\{.*?\})\s*```/s', $response, $matches)) {
            $json_string = $matches[1];
        } elseif (preg_match('/\{.*"seo_title".*\}/s', $response, $matches)) {
            // JSON bloğu işareti olmadan direkt JSON
            $json_string = $matches[0];
        } else {
            // JSON bloğu yoksa tüm yanıtı dene
            $json_string = $response;
        }
        
        // JSON decode
        $data = json_decode($json_string, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            // JSON parse hatası - ham içeriği kaydet
            $this->log_error('json_parse_error', array(
                'error' => json_last_error_msg(),
                'raw_response' => $raw_response,
                'attempted_json' => $json_string,
            ));
            
            // Fallback: Ham içerikten manuel parse dene
            $fallback_data = $this->fallback_parse($raw_response);
            
            if ($fallback_data) {
                return $fallback_data;
            }
            
            return new WP_Error(
                'json_parse_error',
                sprintf(__('AI yanıtı parse edilemedi: %s. Ham içerik kaydedildi.', 'dodo-ai-seo'), json_last_error_msg())
            );
        }
        
        // Zorunlu alanları kontrol et
        $required_fields = array('seo_title', 'meta_description', 'slug', 'content', 'focus_keyword');
        $missing_fields = array();
        
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                $missing_fields[] = $field;
            }
        }
        
        if (!empty($missing_fields)) {
            $this->log_error('missing_fields', array(
                'missing' => $missing_fields,
                'received_data' => $data,
            ));
            
            return new WP_Error(
                'missing_field',
                sprintf(__('AI yanıtında şu alanlar eksik: %s', 'dodo-ai-seo'), implode(', ', $missing_fields))
            );
        }
        
        // FAQ ve tags opsiyonel ama varsa validate et
        if (isset($data['faq']) && !is_array($data['faq'])) {
            $data['faq'] = array();
        }
        
        if (isset($data['tags']) && !is_array($data['tags'])) {
            $data['tags'] = array();
        }
        
        return $data;
    }
    
    /**
     * Kelime sayısını validate et
     * 
     * Kelime sayısını validate et.
     * Length profiline göre dinamik minimum kullanır.
     * 
     * @param array $parsed_content
     * @param array $params
     * @return true|WP_Error
     */
    /**
     * Kelime sayısını validate et (Strategy-driven)
     * 
     * @param array $parsed_content
     * @return true|WP_Error
     */
    private function validate_word_count_with_strategy($parsed_content) {
        // İçeriği al
        $content = isset($parsed_content['content']) ? $parsed_content['content'] : '';
        
        if (empty($content)) {
            return new WP_Error(
                'empty_content',
                __('İçerik boş. Lütfen tekrar deneyin.', 'dodo-ai-seo')
            );
        }
        
        // HTML taglarını temizle
        $clean_content = strip_tags($content);
        
        // Türkçe kelime sayısını say (Unicode desteği ile)
        $words = preg_split('/\s+/u', trim($clean_content), -1, PREG_SPLIT_NO_EMPTY);
        $word_count = count($words);
        
        // STRATEGY'den minimum kelime sayısını al
        $minimum_words = $this->strategy['min_words'];
        $target_words = $this->strategy['target_words'];
        
        error_log(sprintf(
            'DODO: STRATEGY-DRIVEN Kelime sayısı kontrolü - Üretilen: %d, Minimum: %d, Hedef: %d',
            $word_count, $minimum_words, $target_words
        ));
        
        if ($word_count < $minimum_words) {
            error_log(sprintf(
                'DODO: Kelime sayısı yetersiz - Üretilen: %d, Minimum: %d, Hedef: %d',
                $word_count, $minimum_words, $target_words
            ));
            
            return new WP_Error(
                'word_count_too_low',
                sprintf(
                    __('Oluşturulan içerik çok kısa (%d kelime). Minimum %d kelime gerekli. Lütfen tekrar deneyin.', 'dodo-ai-seo'),
                    $word_count,
                    $minimum_words
                )
            );
        }
        
        error_log("DODO: Kelime sayısı kontrolü BAŞARILI - {$word_count} kelime");
        
        return true;
    }
    
    /**
     * Kelime sayısını validate et (DEPRECATED - Eski method)
     * 
     * @param array $parsed_content
     * @param array $params
     * @return true|WP_Error
     */
    private function validate_word_count($parsed_content, $params) {
        // İçeriği al
        $content = isset($parsed_content['content']) ? $parsed_content['content'] : '';
        
        if (empty($content)) {
            return new WP_Error(
                'empty_content',
                __('İçerik boş. Lütfen tekrar deneyin.', 'dodo-ai-seo')
            );
        }
        
        // HTML taglarını temizle
        $clean_content = strip_tags($content);
        
        // Türkçe kelime sayısını say (Unicode desteği ile)
        $words = preg_split('/\s+/u', trim($clean_content), -1, PREG_SPLIT_NO_EMPTY);
        $word_count = count($words);
        
        // Length profiline göre dinamik minimum
        $length = $params['length'] ?? 'medium';
        $pb = new DODO_Prompt_Builder();
        $lp = $pb->get_length_profile($length);
        $minimum_words = $lp['min_words'];
        
        error_log(sprintf(
            'DODO: Kelime sayısı kontrolü - Üretilen: %d, Minimum: %d, Hedef: %s, Length: %s',
            $word_count, $minimum_words, $lp['target_words'], $length
        ));
        
        if ($word_count < $minimum_words) {
            // Log kaydet
            $this->log_error('word_count_below_minimum', array(
                'actual_word_count' => $word_count,
                'minimum_required' => $minimum_words,
                'target_range' => $lp['target_words'],
                'length_setting' => $length,
                'keyword' => isset($params['focus_keyword']) ? $params['focus_keyword'] : '',
                'generation_timestamp' => current_time('mysql'),
            ));
            
            error_log(sprintf(
                'DODO: Kelime sayısı yetersiz - Üretilen: %d, Minimum: %d (%s), Keyword: %s',
                $word_count, $minimum_words, $length,
                isset($params['focus_keyword']) ? $params['focus_keyword'] : 'N/A'
            ));
            
            return new WP_Error(
                'word_count_too_low',
                sprintf(
                    __('İçerik %d kelimenin altında üretildi (%s profili). Üretilen: %d kelime. Lütfen tekrar deneyin.', 'dodo-ai-seo'),
                    $minimum_words, $length, $word_count
                )
            );
        }
        
        // Başarılı - log kaydet
        $this->log_error('word_count_validation_success', array(
            'actual_word_count' => $word_count,
            'minimum_required' => $minimum_words,
            'target_range' => $lp['target_words'],
            'length_setting' => $length,
            'keyword' => isset($params['focus_keyword']) ? $params['focus_keyword'] : '',
            'generation_timestamp' => current_time('mysql'),
        ));
        
        error_log(sprintf(
            'DODO: Kelime sayısı validasyonu başarılı - Üretilen: %d, Hedef: %s (%s), Keyword: %s',
            $word_count, $lp['target_words'], $length,
            isset($params['focus_keyword']) ? $params['focus_keyword'] : 'N/A'
        ));
        
        return true;
    }
    
    /**
     * Fallback parse - JSON başarısız olursa manuel parse dene
     * 
     * @param string $response
     * @return array|false
     */
    private function fallback_parse($response) {
        // Basit regex ile alanları çıkarmaya çalış
        $data = array();
        
        // SEO Title
        if (preg_match('/"seo_title"\s*:\s*"([^"]+)"/i', $response, $matches)) {
            $data['seo_title'] = $matches[1];
        }
        
        // Meta Description
        if (preg_match('/"meta_description"\s*:\s*"([^"]+)"/i', $response, $matches)) {
            $data['meta_description'] = $matches[1];
        }
        
        // Slug
        if (preg_match('/"slug"\s*:\s*"([^"]+)"/i', $response, $matches)) {
            $data['slug'] = $matches[1];
        }
        
        // Focus Keyword
        if (preg_match('/"focus_keyword"\s*:\s*"([^"]+)"/i', $response, $matches)) {
            $data['focus_keyword'] = $matches[1];
        }
        
        // Content (daha karmaşık, tüm içeriği al)
        if (preg_match('/"content"\s*:\s*"(.*?)"\s*[,}]/s', $response, $matches)) {
            $data['content'] = $matches[1];
        } elseif (preg_match('/##\s+.+/s', $response)) {
            // Markdown içerik varsa direkt kullan
            $data['content'] = $response;
        }
        
        // Excerpt
        if (preg_match('/"excerpt"\s*:\s*"([^"]+)"/i', $response, $matches)) {
            $data['excerpt'] = $matches[1];
        }
        
        // Tüm zorunlu alanlar var mı?
        $required = array('seo_title', 'meta_description', 'slug', 'content', 'focus_keyword');
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return false; // Fallback başarısız
            }
        }
        
        $this->log_error('fallback_parse_success', array(
            'message' => 'JSON parse başarısız oldu ama fallback parse çalıştı',
            'extracted_fields' => array_keys($data),
        ));
        
        return $data;
    }
    
    /**
     * Post verilerini hazırla
     * 
     * @param array $parsed_content
     * @param array $params
     * @return array
     */
    private function prepare_post_data($parsed_content, $params) {
        // Markdown'ı HTML'e çevir
        $html_content = $this->post_creator->markdown_to_html($parsed_content['content']);
        
        // ÖNEMLI: Kullanıcının girdiği focus_keyword'ü kullan
        // AI'ın ürettiği focus_keyword değil!
        $focus_keyword = isset($params['focus_keyword']) ? $params['focus_keyword'] : '';
        
        // Fallback: AI'dan gelen focus_keyword
        if (empty($focus_keyword) && isset($parsed_content['focus_keyword'])) {
            $focus_keyword = $parsed_content['focus_keyword'];
        }
        
        // Slug'ı hazırla - focus keyword içermeli
        $slug = isset($parsed_content['slug']) ? $parsed_content['slug'] : '';
        
        // Slug focus keyword içermiyor mu kontrol et
        if (!empty($focus_keyword)) {
            $focus_slug = sanitize_title($focus_keyword);
            
            if (empty($slug) || stripos($slug, $focus_slug) === false) {
                // Slug yoksa veya focus keyword içermiyorsa, focus keyword'den oluştur
                $slug = $focus_slug;
                error_log("DODO: Slug focus keyword içermiyordu, düzeltildi: {$slug}");
            }
        }
        
        // Excerpt'i hazırla - focus keyword içermeli
        $excerpt = isset($parsed_content['excerpt']) ? $parsed_content['excerpt'] : '';
        
        if (!empty($focus_keyword) && !empty($excerpt) && stripos($excerpt, $focus_keyword) === false) {
            // Excerpt focus keyword içermiyorsa başa ekle
            $excerpt = $focus_keyword . ' hakkında: ' . $excerpt;
            
            // Uzunluk kontrolü
            if (mb_strlen($excerpt) > 200) {
                $excerpt = mb_substr($excerpt, 0, 197) . '...';
            }
        }
        
        return array(
            'title' => $parsed_content['seo_title'],
            'content' => $html_content,
            'excerpt' => $excerpt,
            'slug' => $slug,
            'status' => $params['post_status'],
            'category_id' => $params['category_id'],
            'focus_keyword' => $focus_keyword,
            'tone' => $params['tone'],
            'content_type' => $params['content_type'],
            'length' => $params['length'],
            'faq' => $parsed_content['faq'] ?? array(),
            'tags' => $parsed_content['tags'] ?? array(),
            'publish_mode' => $params['publish_mode'],
            'queue_position' => $params['queue_position'],
            'scheduled_date' => isset($params['scheduled_date']) ? $params['scheduled_date'] : '',
            'scheduled_time' => isset($params['scheduled_time']) ? $params['scheduled_time'] : '',
        );
    }
    
    /**
     * Hata logla
     * 
     * @param string $error_type
     * @param array $error_data
     */
    private function log_error($error_type, $error_data) {
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'type' => $error_type,
            'data' => $error_data,
        );
        
        // Son 20 hatayı sakla
        $error_logs = get_option('dodo_error_logs', array());
        array_unshift($error_logs, $log_entry);
        $error_logs = array_slice($error_logs, 0, 20);
        
        update_option('dodo_error_logs', $error_logs, false);
        
        // WordPress error log'a da yaz
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('DODO AI SEO Error [' . $error_type . ']: ' . print_r($error_data, true));
        }
    }
    
    /**
     * SEO verilerini hazırla
     * 
     * @param array $parsed_content
     * @param array $params
     * @return array
     */
    private function prepare_seo_data($parsed_content, $params) {
        // ÖNEMLI: Kullanıcının girdiği focus_keyword'ü kullan
        // AI'ın ürettiği focus_keyword değil!
        $focus_keyword = isset($params['focus_keyword']) ? $params['focus_keyword'] : '';
        
        // Fallback: AI'dan gelen focus_keyword
        if (empty($focus_keyword) && isset($parsed_content['focus_keyword'])) {
            $focus_keyword = $parsed_content['focus_keyword'];
        }
        
        return array(
            'focus_keyword' => $focus_keyword,
            'seo_title' => $parsed_content['seo_title'],
            'meta_description' => $parsed_content['meta_description'],
            'slug' => $parsed_content['slug'],
        );
    }
    
    /**
     * İçerik önizlemesi oluştur (gelecek özellik)
     * 
     * @param array $params
     * @return array|WP_Error
     */
    public function generate_preview($params) {
        // Şimdilik sadece iç linkleri göster
        $internal_links = $this->internal_links->find_relevant_links($params);
        
        return array(
            'internal_links' => $internal_links,
            'estimated_length' => $this->estimate_content_length($params['length']),
            'focus_keyword' => $params['focus_keyword'],
        );
    }
    
    /**
     * İçerik uzunluğunu tahmin et
     * 
     * @param string $length
     * @return string
     */
    private function estimate_content_length($length) {
        $pb = new DODO_Prompt_Builder();
        $lp = $pb->get_length_profile($length);
        return $lp['target_words'] . ' kelime';
    }
    
    /**
     * Toplu blog oluştur (gelecek özellik)
     * 
     * @param array $batch_params
     * @return array
     */
    public function generate_batch($batch_params) {
        $results = array();
        
        foreach ($batch_params as $params) {
            $result = $this->generate_blog($params);
            $results[] = $result;
            
            // Rate limiting için bekle
            sleep(2);
        }
        
        return $results;
    }
    
    /**
     * Mevcut yazıyı güncelle (gelecek özellik)
     * 
     * @param int $post_id
     * @param array $params
     * @return array|WP_Error
     */
    public function regenerate_post($post_id, $params = array()) {
        $post = get_post($post_id);
        
        if (!$post) {
            return new WP_Error('post_not_found', __('Post bulunamadı.', 'dodo-ai-seo'));
        }
        
        // Mevcut post verilerini kullan
        if (empty($params['focus_keyword'])) {
            $params['focus_keyword'] = get_post_meta($post_id, '_dodo_focus_keyword', true);
        }
        
        if (empty($params['topic'])) {
            $params['topic'] = $post->post_title;
        }
        
        // Yeni içerik oluştur
        $result = $this->generate_blog($params);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        // Eski post'u güncelle (yeni post oluşturmak yerine)
        // Bu özellik v2'de eklenecek
        
        return $result;
    }
    
    // ============================================
    // MULTI-STEP GENERATION METHODS
    // ============================================
    
    /**
     * Step 1: Outline (İçerik planı) oluştur
     * 
     * @param array $params
     * @return array|WP_Error
     */
    private function generate_outline($params) {
        // STRATEGY-DRIVEN OUTLINE GENERATION (Brain-enhanced v2.3.0)
        error_log("DODO: Outline generation - Using strategy object (Brain: " . ($this->brain_analysis ? 'YES' : 'NO') . ")");
        error_log("DODO: Strategy H2 count: {$this->strategy['h2_count']}");
        error_log("DODO: Strategy H3 count: {$this->strategy['h3_count']}");
        error_log("DODO: Strategy Target words: {$this->strategy['target_words']}");
        
        // USE STRATEGY OBJECT + BRAIN ANALYSIS
        $system_prompt = $this->prompt_builder->build_outline_prompt_with_strategy(
            $this->strategy,
            $params['focus_keyword'],
            $params['topic'] ?? '',
            $this->brain_analysis // v2.3.0 - Brain context for enhanced outlines
        );
        
        // Topic opsiyonel - boşsa focus keyword'den yön belirle
        if (!empty($params['topic'])) {
            $user_prompt = "Konu: {$params['topic']}\n";
        } else {
            $user_prompt = "Odak Anahtar Kelime: {$params['focus_keyword']}\n";
            $user_prompt .= "NOT: Kullanıcı konu belirtmedi. Bu anahtar kelimeye göre en uygun makale açısını ve konuyu SEN belirle.\n\n";
        }
        
        $user_prompt .= "Odak Anahtar Kelime: {$params['focus_keyword']}\n\n";
        
        // GSC Context ekle (Sprint B - Görev 11)
        if (!empty($params['gsc_context']) && !empty($params['gsc_context']['impressions'])) {
            $gsc = $params['gsc_context'];
            $user_prompt .= "**GOOGLE SEARCH CONSOLE VERİSİ:**\n";
            $user_prompt .= "Bu içerik GSC'de şu metriklere sahip:\n";
            $user_prompt .= "- Gösterim: " . number_format($gsc['impressions']) . "\n";
            $user_prompt .= "- Tıklama: " . number_format($gsc['clicks']) . "\n";
            $user_prompt .= "- CTR: " . number_format($gsc['ctr'], 2) . "%\n";
            $user_prompt .= "- Ortalama Pozisyon: " . number_format($gsc['position'], 1) . "\n";
            
            if (!empty($gsc['recommended_action'])) {
                $user_prompt .= "- Önerilen Aksiyon: {$gsc['recommended_action']}\n";
            }
            
            $user_prompt .= "\nBu verilere göre içeriği optimize et:\n";
            if ($gsc['position'] > 0 && $gsc['position'] <= 10) {
                $user_prompt .= "- İlk sayfada ama daha üst sıralara çıkabilir\n";
            } elseif ($gsc['position'] > 10 && $gsc['position'] <= 20) {
                $user_prompt .= "- İkinci sayfada, ilk sayfaya çıkma potansiyeli var\n";
            }
            
            if ($gsc['ctr'] < 2) {
                $user_prompt .= "- CTR çok düşük, başlık ve meta description çekici olmalı\n";
            }
            
            $user_prompt .= "\n";
        }
        
        // SERP Intelligence ekle (Sprint C - Görev 11)
        if (!empty($params['serp_analysis'])) {
            $serp = $params['serp_analysis'];
            $user_prompt .= "**SERP INTELLIGENCE:**\n";
            $user_prompt .= "SERP Tipi: {$serp['serp_type']}\n";
            
            if (!empty($serp['features'])) {
                $user_prompt .= "SERP Özellikleri:\n";
                foreach ($serp['features'] as $feature) {
                    $user_prompt .= "- {$feature}\n";
                }
            }
            
            if (!empty($serp['content_strategy'])) {
                $user_prompt .= "İçerik Stratejisi:\n";
                foreach ($serp['content_strategy'] as $strategy) {
                    $user_prompt .= "- {$strategy}\n";
                }
            }
            
            $user_prompt .= "\n";
        }
        
        // Problem Analysis ekle (Sprint C - Görev 11)
        if (!empty($params['problem_analysis'])) {
            $problem = $params['problem_analysis'];
            $user_prompt .= "**SEO PROBLEM ANALİZİ:**\n";
            
            if (!empty($problem['explanation'])) {
                $user_prompt .= "Problem: {$problem['explanation']}\n\n";
            }
            
            if (!empty($problem['quick_wins'])) {
                $user_prompt .= "Hızlı Kazançlar:\n";
                foreach ($problem['quick_wins'] as $win) {
                    $user_prompt .= "- {$win}\n";
                }
                $user_prompt .= "\n";
            }
            
            if (!empty($problem['recommendations'])) {
                $user_prompt .= "SEO Önerileri:\n";
                foreach (array_slice($problem['recommendations'], 0, 3) as $rec) {
                    $user_prompt .= "- {$rec}\n";
                }
                $user_prompt .= "\n";
            }
        }
        
        // Revenue Intelligence ekle (Sprint D - Görev 9)
        if (!empty($params['revenue_analysis'])) {
            $revenue = $params['revenue_analysis'];
            $user_prompt .= "**REVENUE INTELLIGENCE:**\n";
            $user_prompt .= "Revenue Score: {$revenue['revenue_score']}/100\n";
            $user_prompt .= "Buyer Intent: {$revenue['buyer_intent']}\n";
            $user_prompt .= "Conversion Probability: {$revenue['conversion_probability']}%\n";
            
            if (!empty($revenue['money_keyword'])) {
                $user_prompt .= "⚠️ Bu bir MONEY KEYWORD - ticari değer yüksek!\n";
                $user_prompt .= "İçerikte:\n";
                $user_prompt .= "- Ürün/hizmet önerileri ekle\n";
                $user_prompt .= "- Fiyat bilgileri ver\n";
                $user_prompt .= "- Call-to-action güçlü olsun\n";
            }
            
            $user_prompt .= "\n";
        }
        
        // GEO/AI Visibility ekle (Sprint D - Görev 9)
        if (!empty($params['geo_analysis'])) {
            $geo = $params['geo_analysis'];
            $user_prompt .= "**GEO/AI VISIBILITY:**\n";
            $user_prompt .= "GEO Score: {$geo['geo_score']}/100\n";
            $user_prompt .= "AI Answer Potential: {$geo['ai_answer_potential']}\n";
            
            if (!empty($geo['missing_faqs'])) {
                $user_prompt .= "Önerilen FAQ'ler:\n";
                foreach (array_slice($geo['missing_faqs'], 0, 3) as $faq) {
                    $user_prompt .= "- {$faq}\n";
                }
            }
            
            if ($geo['geo_score'] >= 70) {
                $user_prompt .= "⚠️ Yüksek AI visibility potansiyeli!\n";
                $user_prompt .= "İçerikte:\n";
                $user_prompt .= "- Net tanımlar ekle\n";
                $user_prompt .= "- FAQ section zorunlu\n";
                $user_prompt .= "- Structured data kullan\n";
                $user_prompt .= "- Conversational tone kullan\n";
            }
            
            $user_prompt .= "\n";
        }
        
        // Topical Authority ekle (Sprint D - Görev 9)
        if (!empty($params['topical_analysis'])) {
            $topical = $params['topical_analysis'];
            $user_prompt .= "**TOPICAL AUTHORITY:**\n";
            $user_prompt .= "Parent Topic: {$topical['parent_topic']}\n";
            $user_prompt .= "Topical Strength: {$topical['topical_strength']}/100\n";
            $user_prompt .= "Authority Gap: {$topical['authority_gap']}/100\n";
            
            if (!empty($topical['suggested_supporting_topics'])) {
                $user_prompt .= "Supporting Topics (internal link targets):\n";
                foreach (array_slice($topical['suggested_supporting_topics'], 0, 3) as $topic) {
                    $user_prompt .= "- {$topic}\n";
                }
            }
            
            $user_prompt .= "\n";
        }
        
        // Internal Link Opportunities ekle (Sprint D - Görev 9)
        if (!empty($params['internal_link_opportunities'])) {
            $links = $params['internal_link_opportunities'];
            $user_prompt .= "**INTERNAL LINK STRATEGY:**\n";
            
            if (!empty($links['suggested_internal_links'])) {
                $user_prompt .= "Link to these posts:\n";
                foreach (array_slice($links['suggested_internal_links'], 0, 3) as $link) {
                    $user_prompt .= "- {$link['title']} ({$link['url']})\n";
                }
            }
            
            if (!empty($links['suggested_anchors'])) {
                $user_prompt .= "Suggested anchors: " . implode(', ', array_slice($links['suggested_anchors'], 0, 3)) . "\n";
            }
            
            $user_prompt .= "\n";
        }
        
        $user_prompt .= "Bu konu için {$this->strategy['h2_count']} ana bölüm (H2) ve her bölüm altında ortalama " . round($this->strategy['h3_count'] / $this->strategy['h2_count']) . " alt başlık (H3) içeren bir plan oluştur.\n";
        $user_prompt .= "Hedef toplam kelime sayısı: {$this->strategy['target_words']}\n";
        $user_prompt .= "Yanıtını şu JSON formatında ver:\n";
        $user_prompt .= "```json\n";
        $user_prompt .= "{\n";
        $user_prompt .= '  "sections": ['."\n";
        $user_prompt .= '    {'."\n";
        $user_prompt .= '      "h2": "Ana Başlık 1",'."\n";
        $user_prompt .= '      "h3_list": ["Alt Başlık 1.1", "Alt Başlık 1.2"]'."\n";
        $user_prompt .= '    }'."\n";
        $user_prompt .= '  ]'."\n";
        $user_prompt .= "}\n";
        $user_prompt .= "```";
        
        $response = $this->openai->generate_content($system_prompt, $user_prompt, 'outline');
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        // JSON parse
        if (preg_match('/```json\s*(\{.*?\})\s*```/s', $response, $matches)) {
            $json_string = $matches[1];
        } else {
            $json_string = $response;
        }
        
        $outline = json_decode($json_string, true);
        
        if (json_last_error() !== JSON_ERROR_NONE || empty($outline['sections'])) {
            return new WP_Error('outline_parse_error', 'Outline oluşturulamadı.');
        }
        
        error_log("DODO: Outline generated with " . count($outline['sections']) . " sections");
        
        return $outline;
    }
    
    /**
     * Build system prompt for generation steps with advanced controls
     * 
     * @param array $params
     * @param string $base_prompt
     * @return string
     */
    private function build_step_system_prompt($params, $base_prompt) {
        $prompt = $base_prompt . "\n\n";
        
        // Add brand patterns from Generation Memory (Sprint 3 - Task 4)
        $memory = new DODO_Generation_Memory();
        $brand_patterns = $memory->get_brand_patterns();
        
        if (!empty($brand_patterns)) {
            $prompt .= "MARKA TUTARLILIĞI:\n";
            $prompt .= "- Ton: {$brand_patterns['tone']}\n";
            $prompt .= "- Başlık Yapısı: {$brand_patterns['heading_structure']}\n";
            $prompt .= "- CTA Stili: {$brand_patterns['cta_style']}\n";
            $prompt .= "- Giriş Stili: {$brand_patterns['intro_style']}\n";
            $prompt .= "Bu marka stilini koru.\n\n";
        }
        
        // Add advanced controls if present
        if (isset($params['content_intent']) || isset($params['expertise_depth'])) {
            $pb = new DODO_Prompt_Builder();
            $advanced_controls = array(
                'content_intent' => $params['content_intent'] ?? 'informational',
                'expertise_depth' => $params['expertise_depth'] ?? 'intermediate',
                'geo_optimization' => $params['geo_optimization'] ?? 'moderate',
                'ai_naturalness' => $params['ai_naturalness'] ?? 'human',
                'semantic_aggressiveness' => $params['semantic_aggressiveness'] ?? 'moderate',
                'readability_target' => $params['readability_target'] ?? 'easy',
            );
            
            $prompt .= $pb->get_advanced_controls_rules(
                $advanced_controls['content_intent'],
                $advanced_controls['expertise_depth'],
                $advanced_controls['geo_optimization'],
                $advanced_controls['ai_naturalness'],
                $advanced_controls['semantic_aggressiveness'],
                $advanced_controls['readability_target']
            );
        }
        
        return $prompt;
    }
    
    /**
     * Step 2: Giriş bölümü oluştur
     * 
     * @param array $params
     * @param array $outline
     * @return string|WP_Error
     */
    private function generate_introduction($params, $outline) {
        // STRATEGY-DRIVEN INTRODUCTION
        error_log("DODO: Introduction - Using strategy intro_length: {$this->strategy['intro_length']}");
        
        $system_prompt = $this->prompt_builder->build_introduction_prompt(
            $params['focus_keyword'],
            $params['topic'] ?? '',
            $this->strategy
        );
        
        // Topic opsiyonel - boşsa focus keyword'den yön belirle
        if (!empty($params['topic'])) {
            $user_prompt = "Konu: {$params['topic']}\n";
        } else {
            $user_prompt = "Odak Anahtar Kelime: {$params['focus_keyword']}\n";
            $user_prompt .= "NOT: Bu anahtar kelimeye göre en uygun makale açısını belirle ve ona göre giriş yaz.\n\n";
        }
        
        $user_prompt .= "Odak Anahtar Kelime: {$params['focus_keyword']}\n\n";
        $user_prompt .= "**ÇOK ÖNEMLİ - KELİME SAYISI KURALI:**\n";
        $user_prompt .= "- HEDEF: {$this->strategy['intro_length']} kelime\n";
        $user_prompt .= "- Bu kelime sayısına MUTLAKA ulaşmalısın\n";
        $user_prompt .= "- Kısa yazma, detaylı ve kapsamlı yaz\n\n";
        $user_prompt .= "Kurallar:\n";
        $user_prompt .= "- İlk paragrafta odak anahtar kelime geçmeli\n";
        $user_prompt .= "- Okuyucunun ilgisini çek\n";
        $user_prompt .= "- Yazıda neler anlatılacağını özetle\n";
        $user_prompt .= "- Markdown formatında yaz (başlık kullanma, sadece paragraflar)\n";
        $user_prompt .= "- Sadece içeriği ver, başka açıklama ekleme";
        
        $response = $this->openai->generate_content($system_prompt, $user_prompt, 'introduction');
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $word_count = str_word_count(strip_tags($response));
        error_log("DODO: Introduction generated - {$word_count} words (target: {$this->strategy['intro_length']})");
        
        return trim($response);
    }
    
    /**
     * Step 3: Ana bölümleri oluştur
     * 
     * @param array $params
     * @param array $outline
     * @param string $internal_links_text
     * @return string|WP_Error
     */
    private function generate_main_sections($params, $outline, $internal_links_text) {
        $all_sections = '';
        $sections = $outline['sections'];
        $total_sections = count($sections);
        
        // Section-by-section generation
        $generated_sections = array();
        $failed_sections = array();
        
        foreach ($sections as $index => $section) {
            $section_number = $index + 1;
            error_log("DODO: Ana bölüm {$section_number}/{$total_sections} oluşturuluyor: {$section['h2']}");
            
            // Feature name: main_section_1, main_section_2, etc.
            $feature_name = 'main_section_' . $section_number;
            
            // Prepare section_data for prompt builder
            // NORMALIZE ARRAYS - Prevent "Array to string conversion"
            $normalized_h3_list = array();
            if (isset($section['h3_list']) && is_array($section['h3_list'])) {
                foreach ($section['h3_list'] as $h3) {
                    $normalized_h3_list[] = is_array($h3) ? implode(' ', $h3) : (string)$h3;
                }
            }
            
            $normalized_key_points = array();
            if (isset($section['key_points']) && is_array($section['key_points'])) {
                foreach ($section['key_points'] as $point) {
                    $normalized_key_points[] = is_array($point) ? implode(' ', $point) : (string)$point;
                }
            }
            
            $normalized_entities = array();
            if (isset($section['entities']) && is_array($section['entities'])) {
                foreach ($section['entities'] as $entity) {
                    $normalized_entities[] = is_array($entity) ? implode(' ', $entity) : (string)$entity;
                }
            }
            
            // Fallback for missing H2
            $h2_title = isset($section['h2']) && !empty($section['h2']) 
                ? (is_array($section['h2']) ? implode(' ', $section['h2']) : (string)$section['h2'])
                : "Bölüm " . ($index + 1);
            
            $section_data = array(
                'h2' => $h2_title,
                'h3_list' => $normalized_h3_list,
                'key_points' => $normalized_key_points,
                'entities' => $normalized_entities,
                'index' => $index,
                'total_sections' => $total_sections,
                'params' => $params,
            );
            
            error_log("[STRATEGY] Section prompt built - H2: {$h2_title}");
            
            // USE STRATEGY OBJECT - Build section prompt with strategy
            $system_prompt = $this->prompt_builder->build_section_prompt_with_strategy(
                $this->strategy,
                $section_data,
                $internal_links_text
            );
            
            $user_prompt = "Ana Başlık (H2): {$h2_title}\n";
            $user_prompt .= "Alt Başlıklar (H3): " . implode(', ', $normalized_h3_list) . "\n";
            $user_prompt .= "Odak Anahtar Kelime: {$params['focus_keyword']}\n";
            
            // Topic opsiyonel
            if (!empty($params['topic'])) {
                $user_prompt .= "Genel Konu: {$params['topic']}\n\n";
            } else {
                $user_prompt .= "NOT: Odak anahtar kelimeye göre en uygun içeriği belirle.\n\n";
            }
            
            // İç linkleri sadece ilk 2 bölümde kullan
            if ($index < 2 && !empty($internal_links_text)) {
                $user_prompt .= $internal_links_text . "\n\n";
            }
            
            $user_prompt .= "**ÇOK ÖNEMLİ - KELİME SAYISI KURALI:**\n";
            $user_prompt .= "- HEDEF: {$this->strategy['target_words']} kelime (toplam)\n";
            $user_prompt .= "- MİNİMUM: {$this->strategy['min_words']} kelime (toplam)\n";
            $user_prompt .= "- Bu bölüm için: Detaylı ve kapsamlı yaz\n";
            $user_prompt .= "- Kısa yazma, her alt başlık altında yeterli açıklama yap\n\n";
            $user_prompt .= "Kurallar:\n";
            $user_prompt .= "- H2 başlığı ile başla: ## {$h2_title}\n";
            $user_prompt .= "- Her H3 alt başlığını kullan: ### [Alt Başlık]\n";
            $user_prompt .= "- Markdown formatında yaz\n";
            $user_prompt .= "- Liste, kalın yazı, vurgular kullan\n";
            $user_prompt .= "- Odak anahtar kelimeyi doğal şekilde kullan\n";
            
            // Orta bölümde CTA ekle - Strategy'den CTA density kullan
            if ($index === (int)($total_sections / 2) && $this->strategy['cta_density'] !== 'low') {
                $user_prompt .= "- Bu bölümün sonuna doğal bir CTA ekle (örn: 'Ürünlerimizi incelemek isterseniz...')\n";
            }
            
            $user_prompt .= "- Sadece içeriği ver, başka açıklama ekleme";
            
            // Section-by-section generation with individual cache
            $response = $this->openai->generate_content($system_prompt, $user_prompt, $feature_name);
            
            if (is_wp_error($response)) {
                // Section başarısız - kaydet ve devam et
                $failed_sections[] = array(
                    'section_number' => $section_number,
                    'h2' => $h2_title,
                    'error' => $response->get_error_message(),
                );
                error_log("DODO: Section {$section_number} başarısız: " . $response->get_error_message());
                
                // Placeholder ekle
                $generated_sections[$section_number] = "## {$h2_title}\n\n[Bu bölüm oluşturulamadı. Lütfen tekrar deneyin.]\n\n";
            } else {
                // Section başarılı
                $generated_sections[$section_number] = trim($response);
                error_log("DODO: Section {$section_number} başarılı");
            }
            
            // Rate limiting (OpenAI için)
            if ($index < $total_sections - 1) {
                sleep(1);
            }
        }
        
        // Final assembly - tüm section'ları birleştir
        foreach ($generated_sections as $section_number => $section_content) {
            $all_sections .= $section_content . "\n\n";
        }
        
        // Başarısız section'lar varsa logla
        if (!empty($failed_sections)) {
            error_log("DODO: " . count($failed_sections) . " section başarısız oldu");
            foreach ($failed_sections as $failed) {
                error_log("DODO: Failed section {$failed['section_number']}: {$failed['h2']} - {$failed['error']}");
            }
        }
        
        // Dış linkler ekle
        $all_sections = $this->add_external_links($all_sections, $params['focus_keyword']);
        
        return trim($all_sections);
    }
    
    /**
     * İçeriğe güvenilir dış linkler ekle
     * 
     * @param string $content
     * @param string $focus_keyword
     * @return string
     */
    private function add_external_links($content, $focus_keyword) {
        // Güvenilir kaynak önerileri
        $trusted_sources = array(
            'wikipedia' => 'https://tr.wikipedia.org/wiki/',
            'wikipedia_en' => 'https://en.wikipedia.org/wiki/',
        );
        
        // 1. Wikipedia link'i ekle (DOFOLLOW - Rank Math için)
        $wikipedia_keyword = str_replace(' ', '_', $focus_keyword);
        $wikipedia_url = $trusted_sources['wikipedia'] . urlencode($wikipedia_keyword);
        
        // İlk H2 veya H3'ten sonra Wikipedia linki ekle
        $pattern = '/(#{2,3}\s+[^\n]+\n\n[^\n]+)/';
        if (preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            $insert_position = $matches[0][1] + strlen($matches[0][0]);
            
            // DOFOLLOW link - rel="nofollow" YOK! Sadece rel="noopener"
            // Markdown syntax: [text](url){:target="_blank" rel="noopener"}
            $wiki_text = "\n\n" . $focus_keyword . " hakkında daha fazla bilgi için [Wikipedia'daki detaylı makaleye](" . $wikipedia_url . "){:target=\"_blank\" rel=\"noopener\"} göz atabilirsiniz.";
            
            $content = substr_replace($content, $wiki_text, $insert_position, 0);
            
            error_log("DODO: Dış link eklendi: Wikipedia (DOFOLLOW - rel='noopener')");
        }
        
        // 2. İkinci dış link ekle (NOFOLLOW olabilir)
        // Ortadaki bir H2/H3'ten sonra ekle
        $pattern_all = '/(#{2,3}\s+[^\n]+\n\n[^\n]+)/';
        if (preg_match_all($pattern_all, $content, $matches_all, PREG_OFFSET_CAPTURE)) {
            $total_matches = count($matches_all[0]);
            if ($total_matches > 2) {
                // Ortadaki bir bölüme ekle
                $middle_index = (int)($total_matches / 2);
                $insert_position = $matches_all[0][$middle_index][1] + strlen($matches_all[0][$middle_index][0]);
                
                // İngilizce Wikipedia'ya link (NOFOLLOW)
                // Markdown syntax: [text](url){:target="_blank" rel="nofollow noopener"}
                $wikipedia_en_keyword = str_replace(' ', '_', $focus_keyword);
                $wikipedia_en_url = $trusted_sources['wikipedia_en'] . urlencode($wikipedia_en_keyword);
                
                $wiki_en_text = "\n\nDaha detaylı teknik bilgi için [İngilizce Wikipedia makalesine](" . $wikipedia_en_url . "){:target=\"_blank\" rel=\"nofollow noopener\"} bakabilirsiniz.";
                
                $content = substr_replace($content, $wiki_en_text, $insert_position, 0);
                
                error_log("DODO: Dış link eklendi: Wikipedia EN (NOFOLLOW - rel='nofollow noopener')");
            }
        }
        
        return $content;
    }
    
    /**
     * Step 4: FAQ bölümü oluştur
     * 
     * @param array $params
     * @return array|WP_Error
     */
    private function generate_faq($params) {
        // USE STRATEGY OBJECT + BRAIN ANALYSIS (v2.3.0)
        $system_prompt = $this->prompt_builder->build_faq_prompt_with_strategy(
            $this->strategy,
            $params,
            $this->brain_analysis // v2.3.0 - Brain context for GEO-optimized FAQ
        );
        
        // Topic opsiyonel
        if (!empty($params['topic'])) {
            $user_prompt = "Konu: {$params['topic']}\n";
        } else {
            $user_prompt = "Odak Anahtar Kelime: {$params['focus_keyword']}\n";
            $user_prompt .= "NOT: Bu anahtar kelimeye göre en uygun SSS'leri belirle.\n\n";
        }
        
        $user_prompt .= "Odak Anahtar Kelime: {$params['focus_keyword']}\n\n";
        $faq_count = $this->strategy['faq_count'];
        $user_prompt .= "**ÇOK ÖNEMLİ - KELİME SAYISI KURALI:**\n";
        $user_prompt .= "- SORU SAYISI: Tam {$faq_count} adet soru-cevap oluştur\n";
        $user_prompt .= "- HER CEVAP: Detaylı ve kapsamlı olmalı\n";
        $user_prompt .= "- Kısa cevap yazma, açıklayıcı yaz\n\n";
        $user_prompt .= "Yanıtını şu JSON formatında ver:\n";
        $user_prompt .= "```json\n";
        $user_prompt .= "{\n";
        $user_prompt .= '  "faq": ['."\n";
        $user_prompt .= '    {"question": "Soru 1?", "answer": "Cevap 1"},'."\n";
        $user_prompt .= '    {"question": "Soru 2?", "answer": "Cevap 2"}'."\n";
        $user_prompt .= '  ]'."\n";
        $user_prompt .= "}\n";
        $user_prompt .= "```";
        
        $response = $this->openai->generate_content($system_prompt, $user_prompt, 'faq');
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        // JSON parse
        if (preg_match('/```json\s*(\{.*?\})\s*```/s', $response, $matches)) {
            $json_string = $matches[1];
        } else {
            $json_string = $response;
        }
        
        $faq_data = json_decode($json_string, true);
        
        if (json_last_error() !== JSON_ERROR_NONE || empty($faq_data['faq'])) {
            return new WP_Error('faq_parse_error', 'FAQ oluşturulamadı.');
        }
        
        // FAQ içeriğini markdown olarak oluştur
        $faq_content = "## Sıkça Sorulan Sorular\n\n";
        
        foreach ($faq_data['faq'] as $item) {
            $faq_content .= "### {$item['question']}\n\n";
            $faq_content .= "{$item['answer']}\n\n";
        }
        
        return array(
            'content' => trim($faq_content),
            'faq_array' => $faq_data['faq'],
        );
    }
    
    /**
     * Step 5: Sonuç bölümü oluştur
     * 
     * @param array $params
     * @param array $outline
     * @return string|WP_Error
     */
    private function generate_conclusion($params, $outline) {
        // STRATEGY-DRIVEN CONCLUSION
        error_log("DODO: Conclusion - Using strategy conclusion_length: {$this->strategy['conclusion_length']}");
        error_log("DODO: Conclusion - CTA density: {$this->strategy['cta_density']}");
        error_log("DODO: Conclusion - Content intent: {$this->strategy['content_intent']}");
        
        $system_prompt = $this->prompt_builder->build_conclusion_prompt(
            $params['focus_keyword'],
            $params['topic'] ?? '',
            $this->strategy
        );
        
        // Topic opsiyonel
        if (!empty($params['topic'])) {
            $user_prompt = "Konu: {$params['topic']}\n";
        } else {
            $user_prompt = "Odak Anahtar Kelime: {$params['focus_keyword']}\n";
            $user_prompt .= "NOT: Bu anahtar kelimeye göre uygun sonuç yaz.\n\n";
        }
        
        $user_prompt .= "Odak Anahtar Kelime: {$params['focus_keyword']}\n\n";
        $user_prompt .= "**ÇOK ÖNEMLİ - KELİME SAYISI KURALI:**\n";
        $user_prompt .= "- HEDEF: {$this->strategy['conclusion_length']} kelime\n";
        $user_prompt .= "- Bu kelime sayısına MUTLAKA ulaşmalısın\n";
        $user_prompt .= "- Kısa yazma, detaylı ve kapsamlı yaz\n\n";
        $user_prompt .= "Kurallar:\n";
        $user_prompt .= "- Yazıyı özetle\n";
        $user_prompt .= "- Ana noktaları vurgula\n";
        
        // CTA density'ye göre farklı yaklaşım
        if ($this->strategy['cta_density'] === 'high' || $this->strategy['content_intent'] === 'transactional') {
            $user_prompt .= "- Güçlü bir harekete geçirici mesaj (CTA) ekle\n";
            $user_prompt .= "- Okuyucuyu aksiyona teşvik et\n";
        } elseif ($this->strategy['cta_density'] === 'medium' || $this->strategy['content_intent'] === 'commercial') {
            $user_prompt .= "- Yumuşak bir harekete geçirici mesaj ekle\n";
        } else {
            $user_prompt .= "- Bilgilendirici bir kapanış yap\n";
        }
        
        $user_prompt .= "- Markdown formatında yaz (başlık kullanma, sadece paragraflar)\n";
        $user_prompt .= "- Sadece içeriği ver, başka açıklama ekleme";
        
        $response = $this->openai->generate_content($system_prompt, $user_prompt, 'conclusion');
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $word_count = str_word_count(strip_tags($response));
        error_log("DODO: Conclusion generated - {$word_count} words (target: {$this->strategy['conclusion_length']})");
        
        return trim($response);
    }
    
    /**
     * Step 6: SEO meta verileri oluştur
     * 
     * @param array $params
     * @return array|WP_Error
     */
    private function generate_seo_meta($params) {
        $system_prompt = "Sen bir SEO uzmanısın. Verilen konuya göre SEO meta verileri oluştur.";
        
        // Topic opsiyonel
        if (!empty($params['topic'])) {
            $user_prompt = "Konu: {$params['topic']}\n";
        } else {
            $user_prompt = "Odak Anahtar Kelime: {$params['focus_keyword']}\n";
            $user_prompt .= "NOT: Bu anahtar kelimeye göre en uygun SEO meta verilerini belirle.\n\n";
        }
        
        $user_prompt .= "Odak Anahtar Kelime: {$params['focus_keyword']}\n\n";
        $user_prompt .= "Bu konu için SEO meta verileri oluştur.\n\n";
        $user_prompt .= "KURALLAR:\n";
        $user_prompt .= "1. SEO Title:\n";
        $user_prompt .= "   - Maksimum 60 karakter\n";
        $user_prompt .= "   - Odak anahtar kelime MUTLAKA geçmeli\n";
        $user_prompt .= "   - Mümkünse sayı kullan (örn: '7 Kritik Kriter', '5 Önemli İpucu')\n";
        $user_prompt .= "   - Kısa, çarpıcı ve tıklanabilir olmalı\n";
        $user_prompt .= "   - Örnek: 'Karbür Matkap Uçları: 7 Kritik Seçim Kriteri'\n\n";
        $user_prompt .= "2. Meta Description:\n";
        $user_prompt .= "   - Maksimum 160 karakter\n";
        $user_prompt .= "   - Odak anahtar kelime MUTLAKA doğal şekilde geçmeli\n";
        $user_prompt .= "   - Tek cümle veya iki kısa cümle\n";
        $user_prompt .= "   - Okuyucuyu tıklamaya teşvik etmeli\n";
        $user_prompt .= "   - Uzun açıklama değil, kısa özet\n\n";
        $user_prompt .= "3. Slug:\n";
        $user_prompt .= "   - Maksimum 70 karakter\n";
        $user_prompt .= "   - Odak anahtar kelime içermeli\n";
        $user_prompt .= "   - Gereksiz kelimeleri çıkar (ve, veya, için, ile, gibi)\n";
        $user_prompt .= "   - Tire ile ayrılmış kelimeler\n";
        $user_prompt .= "   - Örnek: 'karbur-matkap-uclari-secim-kriterleri'\n\n";
        $user_prompt .= "4. Excerpt:\n";
        $user_prompt .= "   - Meta description ile aynı olabilir\n";
        $user_prompt .= "   - Maksimum 160 karakter\n\n";
        $user_prompt .= "Yanıtını şu JSON formatında ver:\n";
        $user_prompt .= "```json\n";
        $user_prompt .= "{\n";
        $user_prompt .= '  "seo_title": "Kısa başlık (max 60 karakter)",'."\n";
        $user_prompt .= '  "meta_description": "Kısa açıklama (max 160 karakter)",'."\n";
        $user_prompt .= '  "slug": "url-slug",'."\n";
        $user_prompt .= '  "excerpt": "Kısa özet (max 160 karakter)",'."\n";
        $user_prompt .= '  "tags": ["tag1", "tag2", "tag3", "tag4", "tag5"]'."\n";
        $user_prompt .= "}\n";
        $user_prompt .= "```";
        
        $response = $this->openai->generate_content($system_prompt, $user_prompt, 'seo_meta');
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        // JSON parse
        if (preg_match('/```json\s*(\{.*?\})\s*```/s', $response, $matches)) {
            $json_string = $matches[1];
        } else {
            $json_string = $response;
        }
        
        $seo_meta = json_decode($json_string, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('seo_meta_parse_error', 'SEO meta verileri oluşturulamadı.');
        }
        
        // Zorunlu alanları kontrol et
        $required = array('seo_title', 'meta_description', 'slug', 'excerpt', 'tags');
        foreach ($required as $field) {
            if (empty($seo_meta[$field])) {
                return new WP_Error('seo_meta_missing_field', "SEO meta verisi eksik: {$field}");
            }
        }
        
        // Uzunluk kontrolü ve düzeltme
        $focus_keyword = $params['focus_keyword'];
        
        // SEO Title - Max 60 karakter
        if (mb_strlen($seo_meta['seo_title']) > 60) {
            $seo_meta['seo_title'] = mb_substr($seo_meta['seo_title'], 0, 57) . '...';
        }
        
        // Focus keyword SEO title'da var mı?
        if (stripos($seo_meta['seo_title'], $focus_keyword) === false) {
            // Yoksa başa ekle
            $seo_meta['seo_title'] = $focus_keyword . ' - ' . $seo_meta['seo_title'];
            if (mb_strlen($seo_meta['seo_title']) > 60) {
                $seo_meta['seo_title'] = mb_substr($seo_meta['seo_title'], 0, 57) . '...';
            }
        }
        
        // Meta Description - Max 160 karakter
        if (mb_strlen($seo_meta['meta_description']) > 160) {
            // Uzunsa kısalt
            $seo_meta['meta_description'] = mb_substr($seo_meta['meta_description'], 0, 157) . '...';
        }
        
        // Focus keyword meta description'da var mı?
        if (stripos($seo_meta['meta_description'], $focus_keyword) === false) {
            // Yoksa başa ekle
            $seo_meta['meta_description'] = $focus_keyword . ' hakkında. ' . $seo_meta['meta_description'];
            if (mb_strlen($seo_meta['meta_description']) > 160) {
                $seo_meta['meta_description'] = mb_substr($seo_meta['meta_description'], 0, 157) . '...';
            }
        }
        
        // Slug - Max 70 karakter, focus keyword içermeli
        $slug = sanitize_title($seo_meta['slug']);
        if (mb_strlen($slug) > 70) {
            $slug = mb_substr($slug, 0, 70);
        }
        
        // Focus keyword slug'da var mı?
        $focus_slug = sanitize_title($focus_keyword);
        if (stripos($slug, $focus_slug) === false) {
            // Yoksa focus keyword'den oluştur
            $slug = $focus_slug;
        }
        $seo_meta['slug'] = $slug;
        
        // Excerpt - Max 160 karakter
        if (mb_strlen($seo_meta['excerpt']) > 160) {
            $seo_meta['excerpt'] = mb_substr($seo_meta['excerpt'], 0, 157) . '...';
        }
        
        // Logla
        error_log("DODO: SEO meta oluşturuldu:");
        error_log("  - SEO Title: {$seo_meta['seo_title']} (" . mb_strlen($seo_meta['seo_title']) . " karakter)");
        error_log("  - Meta Desc: {$seo_meta['meta_description']} (" . mb_strlen($seo_meta['meta_description']) . " karakter)");
        error_log("  - Slug: {$seo_meta['slug']} (" . mb_strlen($seo_meta['slug']) . " karakter)");
        error_log("  - Excerpt: " . mb_strlen($seo_meta['excerpt']) . " karakter");
        
        return $seo_meta;
    }
    
    /**
     * Fallback SEO meta generator
     * API fail olduğunda kullanılır
     * 
     * @param array $params
     * @param string $intro_content
     * @return array
     */
    private function generate_fallback_seo_meta($params, $intro_content = '') {
        $focus_keyword = $params['focus_keyword'];
        
        // Title: Focus keyword + site name
        $site_name = get_bloginfo('name');
        $seo_title = $focus_keyword;
        if ($site_name) {
            $seo_title .= ' | ' . $site_name;
        }
        // Max 60 karakter
        if (mb_strlen($seo_title) > 60) {
            $seo_title = mb_substr($seo_title, 0, 57) . '...';
        }
        
        // Meta Description: İlk 150 karakter veya focus keyword açıklaması
        $meta_description = '';
        if (!empty($intro_content)) {
            // HTML taglerini temizle
            $clean_intro = wp_strip_all_tags($intro_content);
            $meta_description = mb_substr($clean_intro, 0, 150);
        }
        
        // Boşsa focus keyword'den oluştur
        if (empty($meta_description)) {
            $meta_description = $focus_keyword . ' hakkında detaylı bilgi, öneriler ve ipuçları.';
        }
        
        // Max 160 karakter
        if (mb_strlen($meta_description) > 160) {
            $meta_description = mb_substr($meta_description, 0, 157) . '...';
        }
        
        // Slug: Focus keyword'den
        $slug = sanitize_title($focus_keyword);
        if (mb_strlen($slug) > 70) {
            $slug = mb_substr($slug, 0, 70);
        }
        
        // Excerpt: Meta description ile aynı
        $excerpt = $meta_description;
        
        // Tags: Focus keyword'den türet
        $tags = [];
        $keywords = explode(' ', $focus_keyword);
        foreach ($keywords as $keyword) {
            if (mb_strlen($keyword) > 3) { // 3 karakterden uzun kelimeler
                $tags[] = $keyword;
            }
        }
        // En fazla 5 tag
        $tags = array_slice($tags, 0, 5);
        
        error_log("DODO FALLBACK: SEO meta created:");
        error_log("  - Title: {$seo_title}");
        error_log("  - Description: {$meta_description}");
        error_log("  - Slug: {$slug}");
        error_log("  - Tags: " . implode(', ', $tags));
        
        return [
            'seo_title' => $seo_title,
            'meta_description' => $meta_description,
            'slug' => $slug,
            'excerpt' => $excerpt,
            'tags' => $tags,
            'is_fallback' => true
        ];
    }

    
    /**
     * Add AI Answer Blocks to content (Sprint 5 - Phase 2)
     * FAIL-SAFE: Never breaks blog generation
     * 
     * @param array $generated_content Generated content array
     * @param array $params Generation parameters
     * @return array Content with answer blocks (or original content if failed)
     */
    private function add_answer_blocks($generated_content, $params, $block_settings = null) {
        try {
            // Safe extraction with fallbacks
            $title = $generated_content['title'] 
                  ?? $generated_content['seo_title'] 
                  ?? $params['topic'] 
                  ?? $params['focus_keyword'] 
                  ?? 'Blog Post';
            
            $focus_keyword = $params['focus_keyword'] ?? '';
            $content = $generated_content['content'] ?? '';
            
            // Validate required data
            if (empty($content)) {
                error_log('[DODO Answer Blocks] Content is empty, skipping answer blocks');
                return $generated_content;
            }
            
            if (empty($focus_keyword)) {
                error_log('[DODO Answer Blocks] Focus keyword is empty, skipping answer blocks');
                return $generated_content;
            }
            
            // Check if OpenAI has required method
            if (!method_exists($this->answer_block_engine, 'generate_blocks')) {
                error_log('[DODO Answer Blocks] Answer block engine missing generate_blocks method');
                return $generated_content;
            }
            
            // Use provided block_settings or get from engine
            if ($block_settings === null) {
                $block_settings = $this->answer_block_engine->get_block_settings();
            }
            
            // Check if blocks are enabled
            if (!$block_settings['enabled']) {
                error_log('[DODO Answer Blocks] Answer blocks disabled');
                return $generated_content;
            }
            
            // Generate blocks
            error_log('[DODO Answer Blocks] Generating AI Answer Blocks...');
            $blocks = $this->answer_block_engine->generate_blocks(
                $title,
                $focus_keyword,
                $content,
                $block_settings
            );
            
            // Log generated blocks
            foreach ($blocks as $type => $block_content) {
                if (!empty($block_content)) {
                    $word_count = str_word_count(strip_tags($block_content));
                    error_log("[DODO Answer Blocks] - {$type}: {$word_count} words");
                }
            }
            
            // Insert blocks into content
            $content_with_blocks = $this->answer_block_engine->insert_blocks_into_content(
                $content,
                $blocks
            );
            
            // Update content
            $generated_content['content'] = $content_with_blocks;
            
            // Log success
            $total_blocks = count(array_filter($blocks));
            error_log("[DODO Answer Blocks] Successfully added: {$total_blocks} blocks");
            
            return $generated_content;
            
        } catch (Exception $e) {
            // FAIL-SAFE: Log error but don't fail blog generation
            error_log('[DODO Answer Blocks] FAILED: ' . $e->getMessage());
            error_log('[DODO Answer Blocks] Stack trace: ' . $e->getTraceAsString());
            error_log('[DODO Answer Blocks] Continuing without answer blocks...');
            
            // Return original content - blog generation continues
            return $generated_content;
        } catch (Throwable $e) {
            // Catch all errors (PHP 7+)
            error_log('[DODO Answer Blocks] FATAL ERROR: ' . $e->getMessage());
            error_log('[DODO Answer Blocks] Continuing without answer blocks...');
            
            // Return original content - blog generation continues
            return $generated_content;
        }
    }
    
    /**
     * Generate AI Strategy Report (v2.3.0 - Phase 2)
     * 
     * @param array $validated_params Final parameters used
     * @return array Strategy report
     */
    private function generate_strategy_report($validated_params) {
        $report = [
            'timestamp' => current_time('mysql'),
            'brain_enabled' => $this->brain !== null,
            'brain_used' => $this->brain_analysis !== null,
            'decisions' => [],
        ];
        
        // If Brain was used, generate detailed report
        if ($this->brain_analysis && $this->brain_mapper) {
            $user_params = []; // Original user params (before Brain enhancement)
            $report = $this->brain_mapper->generate_strategy_report(
                $this->brain_analysis,
                $user_params,
                $validated_params
            );
        } else {
            // Fallback report without Brain
            $report['decisions'][] = [
                'parameter' => 'Generation Mode',
                'value' => 'Standard (Brain Core not used)',
                'reason' => 'Brain Core unavailable or disabled',
            ];
        }
        
        // Add strategy summary
        $report['strategy_summary'] = [
            'content_intent' => $validated_params['content_intent'] ?? 'informational',
            'tone' => $validated_params['tone'] ?? 'technical',
            'length' => $validated_params['length'] ?? 'medium',
            'geo_optimization' => $validated_params['geo_optimization'] ?? 'moderate',
            'semantic_aggressiveness' => $validated_params['semantic_aggressiveness'] ?? 'moderate',
            'readability_target' => $validated_params['readability_target'] ?? 'easy',
        ];
        
        // Log report
        if ($this->brain_analysis) {
            error_log(sprintf(
                '[DODO Generator] Strategy Report | Brain Score: %d | Intent: %s | GEO: %s',
                $this->brain_analysis['overall_score'] ?? 0,
                $report['strategy_summary']['content_intent'],
                $report['strategy_summary']['geo_optimization']
            ));
        }
        
        return $report;
    }
    
    /**
     * Get learned strategy from Phase 5 learning system
     * 
     * @param array $params Generation parameters
     * @return array|null Learned strategy or null
     */
    private function get_learned_strategy($params) {
        try {
            // Check if learning classes exist
            if (!class_exists('DODO_Strategy_Evolution') || !class_exists('DODO_Learning_Controller')) {
                error_log('[DODO Learning] Required classes not found');
                return null;
            }
            
            // Check controller status
            $controller = new DODO_Learning_Controller();
            
            // If frozen, skip learning
            if ($controller->is_frozen()) {
                error_log('[DODO Learning] System frozen - skipping learning');
                return null;
            }
            
            // If not enabled, skip learning
            if (!$controller->is_enabled()) {
                error_log('[DODO Learning] Learning disabled - skipping');
                return null;
            }
            
            // If observe mode, track but don't apply
            if ($controller->is_observe_mode()) {
                error_log('[DODO Learning] Observe mode - tracking only');
                return null;
            }
            
            // Detect niche from keyword/topic
            $niche = $this->detect_niche($params);
            
            // Build context for learning
            $context = [
                'niche' => $niche,
                'intent' => $params['content_intent'] ?? 'informational',
                'tone' => $params['tone'] ?? 'technical',
            ];
            
            error_log('[DODO Learning] Getting learned strategy for context: ' . json_encode($context));
            
            // Get evolved strategy
            $evolution = new DODO_Strategy_Evolution();
            $base_params = [
                'faq_count' => $this->strategy['faq_count'],
                'semantic_density' => $this->strategy['semantic_aggressiveness'],
                'target_word_count' => $this->strategy['target_words'],
                'cta_density' => $this->strategy['cta_density'],
                'geo_optimization' => $this->strategy['geo_optimization'],
            ];
            
            $evolved = $evolution->get_evolved_strategy($base_params, $context);
            
            if ($evolved && isset($evolved['evolution_applied']) && $evolved['evolution_applied']) {
                // Validate with controller
                $validation = $controller->validate_application($evolved);
                
                if (!$validation['allowed']) {
                    error_log('[DODO Learning] Controller blocked application: ' . $validation['reason']);
                    return null;
                }
                
                // Create snapshot before applying
                $snapshots = new DODO_Strategy_Snapshots();
                $snapshots->create_snapshot(
                    $this->strategy,
                    'Before learning application',
                    ['context' => $context, 'evolved' => $evolved]
                );
                
                error_log('[DODO Learning] ✅ Learned strategy validated and approved');
                error_log('[DODO Learning] Evolved FAQ count: ' . ($evolved['faq_count'] ?? 'N/A'));
                error_log('[DODO Learning] Evolved semantic density: ' . ($evolved['semantic_density'] ?? 'N/A'));
                error_log('[DODO Learning] Evolved target words: ' . ($evolved['target_word_count'] ?? 'N/A'));
                
                return $evolved;
            }
            
            error_log('[DODO Learning] No evolution applied - using base strategy');
            return null;
            
        } catch (Exception $e) {
            error_log('[DODO Learning] Error getting learned strategy: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Merge strategies with priority: User > Safety > Brain > Learned > Default
     * 
     * @param array $base_strategy Base strategy (Brain-enhanced)
     * @param array|null $learned_strategy Learned strategy from Phase 5
     * @param array $params User parameters
     * @return array Merged strategy
     */
    private function merge_strategies($base_strategy, $learned_strategy, $params) {
        $merged = $base_strategy;
        
        // Track what was applied
        $applied_rules = [];
        $rejected_rules = [];
        
        if (!$learned_strategy || !isset($learned_strategy['evolution_applied'])) {
            error_log('[DODO Learning] No learned strategy to merge');
            return $merged;
        }
        
        // Validate learned strategy safety
        $validator = new DODO_Learning_Validator();
        $validation = $validator->validate_strategy_safety($base_strategy, $learned_strategy);
        
        if (!$validation['safe']) {
            error_log('[DODO Learning] ⚠️ Learned strategy rejected - safety validation failed');
            foreach ($validation['warnings'] as $warning) {
                error_log('[DODO Learning] Warning: ' . $warning);
                $rejected_rules[] = $warning;
            }
            return $merged;
        }
        
        error_log('[DODO Learning] ✅ Learned strategy passed safety validation');
        
        // Apply learned parameters with priority checks
        
        // 1. FAQ Count
        if (isset($learned_strategy['faq_count'])) {
            // User explicit choice overrides
            if (!isset($params['faq_count_override'])) {
                $old_value = $merged['faq_count'];
                $merged['faq_count'] = $learned_strategy['faq_count'];
                $applied_rules[] = "FAQ count: {$old_value} → {$merged['faq_count']} (learned)";
                error_log("[DODO Learning] Applied: FAQ count {$old_value} → {$merged['faq_count']}");
            } else {
                $rejected_rules[] = "FAQ count: User override active";
            }
        }
        
        // 2. Semantic Density
        if (isset($learned_strategy['semantic_density'])) {
            if (!isset($params['semantic_override'])) {
                $old_value = $merged['semantic_aggressiveness'];
                $merged['semantic_aggressiveness'] = $learned_strategy['semantic_density'];
                $applied_rules[] = "Semantic: {$old_value} → {$merged['semantic_aggressiveness']} (learned)";
                error_log("[DODO Learning] Applied: Semantic {$old_value} → {$merged['semantic_aggressiveness']}");
            } else {
                $rejected_rules[] = "Semantic density: User override active";
            }
        }
        
        // 3. Target Word Count
        if (isset($learned_strategy['target_word_count'])) {
            if (!isset($params['length_override'])) {
                $old_value = $merged['target_words'];
                $merged['target_words'] = $learned_strategy['target_word_count'];
                // Adjust min_words proportionally
                $merged['min_words'] = round($merged['target_words'] * 0.7);
                $applied_rules[] = "Word count: {$old_value} → {$merged['target_words']} (learned)";
                error_log("[DODO Learning] Applied: Word count {$old_value} → {$merged['target_words']}");
            } else {
                $rejected_rules[] = "Word count: User override active";
            }
        }
        
        // 4. CTA Density
        if (isset($learned_strategy['cta_density'])) {
            if (!isset($params['cta_override'])) {
                $old_value = $merged['cta_density'];
                $merged['cta_density'] = $learned_strategy['cta_density'];
                $applied_rules[] = "CTA density: {$old_value} → {$merged['cta_density']} (learned)";
                error_log("[DODO Learning] Applied: CTA {$old_value} → {$merged['cta_density']}");
            } else {
                $rejected_rules[] = "CTA density: User override active";
            }
        }
        
        // 5. Title Patterns (if learned)
        if (isset($learned_strategy['title_patterns'])) {
            $merged['title_patterns'] = $learned_strategy['title_patterns'];
            $applied_rules[] = "Title patterns: Learned patterns applied";
            error_log("[DODO Learning] Applied: Title patterns from learning");
        }
        
        // Log summary
        error_log('[DODO Learning] === STRATEGY MERGE SUMMARY ===');
        error_log('[DODO Learning] Applied rules: ' . count($applied_rules));
        foreach ($applied_rules as $rule) {
            error_log('[DODO Learning] ✅ ' . $rule);
        }
        
        if (!empty($rejected_rules)) {
            error_log('[DODO Learning] Rejected rules: ' . count($rejected_rules));
            foreach ($rejected_rules as $rule) {
                error_log('[DODO Learning] ❌ ' . $rule);
            }
        }
        
        // Store merge metadata
        $merged['learning_applied'] = true;
        $merged['applied_rules'] = $applied_rules;
        $merged['rejected_rules'] = $rejected_rules;
        $merged['learning_confidence'] = $learned_strategy['confidence'] ?? 0;
        
        return $merged;
    }
    
    /**
     * Detect niche from keyword/topic
     * 
     * @param array $params Generation parameters
     * @return string Detected niche
     */
    private function detect_niche($params) {
        $keyword = strtolower($params['focus_keyword'] ?? '');
        $topic = strtolower($params['topic'] ?? '');
        $text = $keyword . ' ' . $topic;
        
        // Simple keyword-based detection
        $niche_patterns = [
            'saas' => ['software', 'saas', 'cloud', 'platform', 'tool', 'app', 'api'],
            'ecommerce' => ['buy', 'shop', 'store', 'product', 'price', 'discount', 'sale'],
            'b2b' => ['business', 'enterprise', 'corporate', 'solution', 'service'],
            'blog' => ['guide', 'tutorial', 'how to', 'tips', 'best', 'top'],
            'news' => ['news', 'update', 'announcement', 'release', 'latest'],
            'local' => ['near me', 'local', 'city', 'location', 'address'],
        ];
        
        foreach ($niche_patterns as $niche => $patterns) {
            foreach ($patterns as $pattern) {
                if (strpos($text, $pattern) !== false) {
                    error_log("[DODO Learning] Detected niche: {$niche}");
                    return $niche;
                }
            }
        }
        
        error_log('[DODO Learning] Niche detection: default (blog)');
        return 'blog';
    }
    
    /**
     * Start impact tracking after content generation
     * 
     * @param int $post_id Created post ID
     * @param array $params Generation parameters
     * @param array $strategy Final strategy used
     */
    private function start_impact_tracking($post_id, $params, $strategy) {
        try {
            if (!class_exists('DODO_Impact_Tracker')) {
                return;
            }
            
            $tracker = new DODO_Impact_Tracker();
            
            $action_details = [
                'keyword' => $params['focus_keyword'],
                'strategy' => $strategy,
                'learning_applied' => $strategy['learning_applied'] ?? false,
                'brain_enhanced' => $params['brain_enhanced'] ?? false,
            ];
            
            $tracking_id = $tracker->start_tracking(
                $post_id,
                DODO_Impact_Tracker::ACTION_CONTENT_REFRESH,
                $action_details
            );
            
            // Store tracking ID in post meta
            update_post_meta($post_id, '_dodo_tracking_id', $tracking_id);
            
            error_log("[DODO Learning] Impact tracking started - ID: {$tracking_id}");
            
        } catch (Exception $e) {
            error_log('[DODO Learning] Impact tracking failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Record learning event after blog generation
     * 
     * @param int $post_id Created post ID
     * @param array $params Generation parameters
     * @param array $strategy Used strategy
     * @param array $generated_content Generated content
     */
    private function record_learning_event($post_id, $params, $strategy, $generated_content) {
        global $wpdb;
        
        try {
            $learning_table = $wpdb->prefix . 'dodo_learning_events';
            
            // Calculate word count
            $content = $generated_content['content'] ?? '';
            $word_count = count(preg_split('/\s+/u', trim(strip_tags($content)), -1, PREG_SPLIT_NO_EMPTY));
            
            // Prepare metadata
            $metadata = array(
                'focus_keyword' => $params['focus_keyword'] ?? '',
                'length' => $params['length'] ?? 'medium',
                'tone' => $params['tone'] ?? 'informative',
                'content_intent' => $params['content_intent'] ?? 'informational',
                'expertise_depth' => $params['expertise_depth'] ?? 'intermediate',
                'geo_optimization' => $params['geo_optimization'] ?? 'moderate',
                'ai_naturalness' => $params['ai_naturalness'] ?? 'human',
                'word_count' => $word_count,
                'brain_enhanced' => $this->brain_analysis !== null,
                'learning_applied' => $strategy['learning_applied'] ?? false,
            );
            
            // Insert learning event
            $result = $wpdb->insert(
                $learning_table,
                array(
                    'event_type' => 'blog_generation_completed',
                    'post_id' => $post_id,
                    'strategy_params' => json_encode($strategy),
                    'outcome_score' => 0, // Will be updated by impact tracker
                    'metadata' => json_encode($metadata),
                    'recorded_at' => current_time('mysql'),
                ),
                array('%s', '%d', '%s', '%f', '%s', '%s')
            );
            
            if ($result) {
                error_log(sprintf(
                    '[DODO Learning] Learning event recorded | Post ID: %d | Event: blog_generation_completed | Word Count: %d',
                    $post_id,
                    $word_count
                ));
            } else {
                error_log('[DODO Learning] Failed to record learning event: ' . $wpdb->last_error);
            }
            
        } catch (Exception $e) {
            error_log('[DODO Learning] Learning event recording failed: ' . $e->getMessage());
        }
    }
}

