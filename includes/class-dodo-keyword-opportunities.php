<?php
/**
 * Keyword Opportunities Sınıfı
 * 
 * Site içeriğini analiz eder ve yeni anahtar kelime fırsatları önerir
 *
 * @package DODO_AI_SEO
 * @since 1.0.0
 */

// Doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

class DODO_Keyword_Opportunities {
    
    /**
     * Veritabanı tablo adı
     */
    private $table_name;
    
    /**
     * OpenAI instance
     */
    private $openai;
    
    /**
     * Maksimum öneri sayısı
     */
    private $max_opportunities = 50;
    
    /**
     * GSC Intelligence instance
     */
    private $gsc_intelligence;
    
    /**
     * Query Quality Engine instance
     */
    private $quality_engine;
    
    /**
     * Commercial Intent Engine instance
     */
    private $commercial_engine;
    
    /**
     * SERP Intelligence Engine instance
     */
    private $serp_engine;
    
    /**
     * SEO Problem Analyzer instance
     */
    private $problem_analyzer;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'dodo_keyword_opportunities';
        
        // OpenAI'yi lazy load yap - sadece gerektiğinde yükle
        // Aktivasyon sırasında OpenAI'ye ihtiyaç yok
        
        // GSC Intelligence'ı lazy load
        $this->gsc_intelligence = null;
        
        // Query Quality Engine'i lazy load
        $this->quality_engine = null;
        
        // Commercial Intent Engine'i lazy load
        $this->commercial_engine = null;
        
        // SERP Intelligence Engine'i lazy load
        $this->serp_engine = null;
        
        // SEO Problem Analyzer'ı lazy load
        $this->problem_analyzer = null;
    }
    
    /**
     * SERP Intelligence Engine instance'ını al (lazy loading)
     */
    private function get_serp_engine() {
        if ($this->serp_engine === null) {
            if (class_exists('DODO_SERP_Intelligence_Engine')) {
                try {
                    $this->serp_engine = new DODO_SERP_Intelligence_Engine();
                } catch (Throwable $e) {
                    error_log('[DODO][Keyword Opportunities] SERP engine initialization failed: ' . $e->getMessage());
                    $this->serp_engine = false;
                }
            } else {
                $this->serp_engine = false;
            }
        }
        return $this->serp_engine;
    }
    
    /**
     * SEO Problem Analyzer instance'ını al (lazy loading)
     */
    private function get_problem_analyzer() {
        if ($this->problem_analyzer === null) {
            if (class_exists('DODO_SEO_Problem_Analyzer')) {
                try {
                    $this->problem_analyzer = new DODO_SEO_Problem_Analyzer();
                } catch (Throwable $e) {
                    error_log('[DODO][Keyword Opportunities] Problem analyzer initialization failed: ' . $e->getMessage());
                    $this->problem_analyzer = false;
                }
            } else {
                $this->problem_analyzer = false;
            }
        }
        return $this->problem_analyzer;
    }
    
    /**
     * Commercial Intent Engine instance'ını al (lazy loading)
     */
    private function get_commercial_engine() {
        if ($this->commercial_engine === null) {
            if (class_exists('DODO_Commercial_Intent_Engine')) {
                try {
                    $this->commercial_engine = new DODO_Commercial_Intent_Engine();
                } catch (Throwable $e) {
                    error_log('[DODO][Keyword Opportunities] Commercial engine initialization failed: ' . $e->getMessage());
                    $this->commercial_engine = false;
                }
            } else {
                $this->commercial_engine = false;
            }
        }
        return $this->commercial_engine;
    }
    
    /**
     * Query Quality Engine instance'ını al (lazy loading)
     */
    private function get_quality_engine() {
        if ($this->quality_engine === null) {
            if (class_exists('DODO_Query_Quality_Engine')) {
                try {
                    $this->quality_engine = new DODO_Query_Quality_Engine();
                } catch (Throwable $e) {
                    error_log('[DODO][Keyword Opportunities] Quality engine initialization failed: ' . $e->getMessage());
                    $this->quality_engine = false;
                }
            } else {
                $this->quality_engine = false;
            }
        }
        return $this->quality_engine;
    }
    
    /**
     * GSC Intelligence instance'ını al (lazy loading)
     */
    private function get_gsc_intelligence() {
        if ($this->gsc_intelligence === null) {
            // Check if class exists
            if (class_exists('DODO_GSC_Intelligence')) {
                try {
                    $this->gsc_intelligence = new DODO_GSC_Intelligence();
                } catch (Throwable $e) {
                    error_log('[DODO][Keyword Opportunities] GSC Intelligence initialization failed: ' . $e->getMessage());
                    // Return mock object with is_available() = false
                    $this->gsc_intelligence = new class {
                        public function is_available() { return false; }
                    };
                }
            } else {
                error_log('[DODO][Keyword Opportunities] DODO_GSC_Intelligence class not found');
                // Return mock object with is_available() = false
                $this->gsc_intelligence = new class {
                    public function is_available() { return false; }
                };
            }
        }
        return $this->gsc_intelligence;
    }
    
    /**
     * OpenAI instance'ını al (lazy loading)
     */
    private function get_openai() {
        if ($this->openai === null) {
            if (class_exists('DODO_OpenAI')) {
                try {
                    $this->openai = new DODO_OpenAI();
                } catch (Throwable $e) {
                    error_log('[DODO][Keyword Opportunities] OpenAI initialization failed: ' . $e->getMessage());
                    $this->openai = false;
                }
            } else {
                error_log('[DODO][Keyword Opportunities] DODO_OpenAI class not found');
                $this->openai = false;
            }
        }
        return $this->openai;
    }
    
    /**
     * Veritabanı tablosunu oluştur ve güncelle
     * 
     * dbDelta otomatik olarak eksik kolonları ekler, mevcut verileri bozmaz
     */
    public function create_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$this->table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            keyword varchar(255) NOT NULL,
            intent varchar(50) NOT NULL,
            suggested_title text NOT NULL,
            content_type varchar(50) NOT NULL,
            score int(11) NOT NULL DEFAULT 0,
            status varchar(20) NOT NULL DEFAULT 'new',
            reason text,
            similar_content_exists tinyint(1) DEFAULT 0,
            similar_content_ids text,
            publish_mode varchar(20) DEFAULT NULL,
            scheduled_date varchar(20) DEFAULT NULL,
            scheduled_time varchar(10) DEFAULT NULL,
            retry_count int(11) NOT NULL DEFAULT 0,
            last_retry_at datetime DEFAULT NULL,
            last_error text DEFAULT NULL,
            next_retry_at datetime DEFAULT NULL,
            source varchar(50) DEFAULT 'ai_suggestion',
            impressions int(11) DEFAULT 0,
            clicks int(11) DEFAULT 0,
            ctr decimal(5,2) DEFAULT 0.00,
            position decimal(5,1) DEFAULT 0.0,
            impact varchar(20) DEFAULT NULL,
            effort varchar(20) DEFAULT NULL,
            recommended_action text DEFAULT NULL,
            confidence int(11) DEFAULT 0,
            quality_score int(11) DEFAULT 100,
            query_quality varchar(20) DEFAULT 'high',
            commercial_score int(11) DEFAULT 0,
            intent_type varchar(20) DEFAULT 'informational',
            commercial_level varchar(20) DEFAULT 'low',
            priority_score int(11) DEFAULT 0,
            problem_severity varchar(20) DEFAULT NULL,
            serp_type varchar(50) DEFAULT NULL,
            serp_features text DEFAULT NULL,
            quick_wins text DEFAULT NULL,
            seo_recommendations text DEFAULT NULL,
            problem_analysis text DEFAULT NULL,
            revenue_score int(11) DEFAULT 0,
            geo_score int(11) DEFAULT 0,
            conversion_probability int(11) DEFAULT 0,
            authority_gap int(11) DEFAULT 0,
            topical_strength int(11) DEFAULT 0,
            parent_topic varchar(255) DEFAULT NULL,
            cluster_type varchar(50) DEFAULT NULL,
            supporting_articles text DEFAULT NULL,
            suggested_internal_links text DEFAULT NULL,
            funnel_stage varchar(50) DEFAULT NULL,
            buyer_intent varchar(20) DEFAULT NULL,
            money_keyword tinyint(1) DEFAULT 0,
            ai_visibility_score int(11) DEFAULT 0,
            business_priority int(11) DEFAULT 0,
            priority_level varchar(20) DEFAULT NULL,
            performance_score int(11) DEFAULT 0,
            growth_rate decimal(5,2) DEFAULT 0.00,
            rank_velocity decimal(5,2) DEFAULT 0.00,
            geo_growth int(11) DEFAULT 0,
            refresh_needed tinyint(1) DEFAULT 0,
            semantic_depth int(11) DEFAULT 0,
            conversion_signal int(11) DEFAULT 0,
            winner_pattern varchar(100) DEFAULT NULL,
            last_tracked datetime DEFAULT NULL,
            tracking_status varchar(20) DEFAULT NULL,
            publish_snapshot text DEFAULT NULL,
            current_snapshot text DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY keyword (keyword),
            KEY status (status),
            KEY score (score),
            KEY source (source),
            KEY next_retry_at (next_retry_at),
            KEY priority_score (priority_score),
            KEY intent_type (intent_type),
            KEY business_priority (business_priority),
            KEY money_keyword (money_keyword),
            KEY funnel_stage (funnel_stage),
            KEY performance_score (performance_score),
            KEY refresh_needed (refresh_needed),
            KEY tracking_status (tracking_status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        error_log('DODO: Keyword opportunities table created/updated with retry system columns');
    }
    
    /**
     * Site içeriğini tara ve analiz et
     * 
     * @return array Analiz sonuçları
     */
    public function analyze_site_content() {
        $analysis = array(
            'posts' => array(),
            'products' => array(),
            'categories' => array(),
            'tags' => array(),
            'focus_keywords' => array(),
            'topic_clusters' => array(),
            'stats' => array(
                'total_posts' => 0,
                'total_products' => 0,
                'total_categories' => 0,
                'total_tags' => 0,
                'total_focus_keywords' => 0,
            ),
        );
        
        error_log('DODO: Site içeriği taranıyor...');
        
        // 1. Blog yazılarını tara
        $posts = get_posts(array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'numberposts' => 200, // Performans için limit
            'orderby' => 'date',
            'order' => 'DESC',
        ));
        
        foreach ($posts as $post) {
            $analysis['posts'][] = array(
                'id' => $post->ID,
                'title' => $post->post_title,
                'content' => wp_trim_words($post->post_content, 100),
                'focus_keyword' => get_post_meta($post->ID, 'rank_math_focus_keyword', true),
            );
        }
        
        $analysis['stats']['total_posts'] = count($posts);
        error_log("DODO: {$analysis['stats']['total_posts']} blog yazısı tarandı");
        
        // 2. WooCommerce ürünlerini tara (varsa)
        if (class_exists('WooCommerce')) {
            $products = get_posts(array(
                'post_type' => 'product',
                'post_status' => 'publish',
                'numberposts' => 100,
            ));
            
            foreach ($products as $product) {
                $analysis['products'][] = array(
                    'id' => $product->ID,
                    'title' => $product->post_title,
                    'content' => wp_trim_words($product->post_content, 50),
                );
            }
            
            $analysis['stats']['total_products'] = count($products);
            error_log("DODO: {$analysis['stats']['total_products']} ürün tarandı");
        }
        
        // 3. Kategorileri tara
        $categories = get_categories(array(
            'hide_empty' => false,
            'number' => 50,
        ));
        
        foreach ($categories as $category) {
            $analysis['categories'][] = array(
                'id' => $category->term_id,
                'name' => $category->name,
                'slug' => $category->slug,
                'count' => $category->count,
                'description' => $category->description,
            );
        }
        
        $analysis['stats']['total_categories'] = count($categories);
        error_log("DODO: {$analysis['stats']['total_categories']} kategori tarandı");
        
        // 4. Etiketleri tara
        $tags = get_tags(array(
            'hide_empty' => false,
            'number' => 100,
        ));
        
        foreach ($tags as $tag) {
            $analysis['tags'][] = array(
                'id' => $tag->term_id,
                'name' => $tag->name,
                'slug' => $tag->slug,
                'count' => $tag->count,
            );
        }
        
        $analysis['stats']['total_tags'] = count($tags);
        error_log("DODO: {$analysis['stats']['total_tags']} etiket tarandı");
        
        // 5. Rank Math focus keyword'leri topla
        global $wpdb;
        $focus_keywords = $wpdb->get_results(
            "SELECT DISTINCT meta_value as keyword, COUNT(*) as count 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = 'rank_math_focus_keyword' 
            AND meta_value != '' 
            GROUP BY meta_value 
            ORDER BY count DESC 
            LIMIT 100"
        );
        
        foreach ($focus_keywords as $fk) {
            $analysis['focus_keywords'][] = array(
                'keyword' => $fk->keyword,
                'count' => $fk->count,
            );
        }
        
        $analysis['stats']['total_focus_keywords'] = count($focus_keywords);
        error_log("DODO: {$analysis['stats']['total_focus_keywords']} benzersiz focus keyword bulundu");
        
        // 6. Konu kümelerini çıkar (basit kelime frekans analizi)
        $analysis['topic_clusters'] = $this->extract_topic_clusters($analysis);
        
        return $analysis;
    }
    
    /**
     * Konu kümelerini çıkar
     * 
     * @param array $analysis
     * @return array
     */
    private function extract_topic_clusters($analysis) {
        $word_frequency = array();
        
        // Başlıklardan kelime frekansı çıkar
        foreach ($analysis['posts'] as $post) {
            $words = $this->extract_keywords_from_text($post['title']);
            foreach ($words as $word) {
                if (!isset($word_frequency[$word])) {
                    $word_frequency[$word] = 0;
                }
                $word_frequency[$word]++;
            }
        }
        
        // Kategorilerden kelime ekle
        foreach ($analysis['categories'] as $category) {
            $words = $this->extract_keywords_from_text($category['name']);
            foreach ($words as $word) {
                if (!isset($word_frequency[$word])) {
                    $word_frequency[$word] = 0;
                }
                $word_frequency[$word] += 3; // Kategorilere daha fazla ağırlık
            }
        }
        
        // Sırala ve en popüler 20'yi al
        arsort($word_frequency);
        $top_topics = array_slice($word_frequency, 0, 20, true);
        
        error_log("DODO: " . count($top_topics) . " konu kümesi çıkarıldı");
        
        return $top_topics;
    }
    
    /**
     * Metinden anahtar kelimeleri çıkar
     * 
     * @param string $text
     * @return array
     */
    private function extract_keywords_from_text($text) {
        // Türkçe stop words
        $stop_words = array(
            'bir', 'bu', 've', 'için', 'ile', 'mi', 'mı', 'mu', 'mü',
            'da', 'de', 'ta', 'te', 'ki', 'ne', 'nasıl', 'neden',
            'gibi', 'kadar', 'daha', 'en', 'çok', 'az', 'var', 'yok',
            'olan', 'olarak', 'ise', 'ancak', 'fakat', 'veya', 'ya',
        );
        
        // Küçük harfe çevir ve kelimelere ayır
        $text = mb_strtolower($text, 'UTF-8');
        $words = preg_split('/[\s\-_,\.;:!?()]+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        // Filtrele
        $keywords = array();
        foreach ($words as $word) {
            // En az 3 karakter
            if (mb_strlen($word, 'UTF-8') < 3) {
                continue;
            }
            
            // Stop word değilse ekle
            if (!in_array($word, $stop_words)) {
                $keywords[] = $word;
            }
        }
        
        return $keywords;
    }
    
    /**
     * AI ile yeni keyword önerileri üret (GSC + AI merge)
     * 
     * @param array $analysis Site analizi
     * @return array|WP_Error
     */
    public function generate_opportunities($analysis) {
        error_log('[DODO][Keyword Opportunities] === GENERATE OPPORTUNITIES START ===');
        
        $all_opportunities = array();
        $source_breakdown = array(
            'gsc_ctr' => 0,
            'gsc_ranking' => 0,
            'gsc_decay' => 0,
            'gsc_cannibalization' => 0,
            'gsc_gap' => 0,
            'ai_suggestion' => 0,
        );
        
        // 1. GSC Intelligence'dan fırsatları al
        $gsc_intelligence = $this->get_gsc_intelligence();
        
        if ($gsc_intelligence->is_available()) {
            error_log('[DODO][Keyword Opportunities] GSC is available, fetching GSC opportunities');
            
            // GSC CTR opportunities
            try {
                error_log('[DODO][Keyword Opportunities] === CTR ENGINE START ===');
                $ctr_opps = $gsc_intelligence->get_ctr_opportunities(28);
                if (!empty($ctr_opps)) {
                    error_log('[DODO][Keyword Opportunities] CTR: Found ' . count($ctr_opps) . ' opportunities');
                    $formatted_ctr = $this->format_gsc_opportunities($ctr_opps, 'gsc_ctr');
                    $all_opportunities = array_merge($all_opportunities, $formatted_ctr);
                    $source_breakdown['gsc_ctr'] = count($formatted_ctr);
                } else {
                    error_log('[DODO][Keyword Opportunities] CTR: No opportunities found');
                }
                error_log('[DODO][Keyword Opportunities] === CTR ENGINE END ===');
            } catch (Exception $e) {
                error_log('[DODO][Keyword Opportunities] CTR ENGINE ERROR: ' . $e->getMessage());
            }
            
            // GSC Ranking opportunities
            try {
                error_log('[DODO][Keyword Opportunities] === RANKING ENGINE START ===');
                $ranking_opps = $gsc_intelligence->get_ranking_opportunities(28);
                if (!empty($ranking_opps)) {
                    error_log('[DODO][Keyword Opportunities] RANKING: Found ' . count($ranking_opps) . ' opportunities');
                    $formatted_ranking = $this->format_gsc_opportunities($ranking_opps, 'gsc_ranking');
                    $all_opportunities = array_merge($all_opportunities, $formatted_ranking);
                    $source_breakdown['gsc_ranking'] = count($formatted_ranking);
                } else {
                    error_log('[DODO][Keyword Opportunities] RANKING: No opportunities found');
                }
                error_log('[DODO][Keyword Opportunities] === RANKING ENGINE END ===');
            } catch (Exception $e) {
                error_log('[DODO][Keyword Opportunities] RANKING ENGINE ERROR: ' . $e->getMessage());
            }
            
            // GSC Decay opportunities (with timeout protection)
            try {
                error_log('[DODO][Keyword Opportunities] === DECAY ENGINE START ===');
                $decay_start = microtime(true);
                $decay_opps = $gsc_intelligence->get_content_decay_opportunities();
                $decay_time = microtime(true) - $decay_start;
                
                if (!empty($decay_opps)) {
                    error_log('[DODO][Keyword Opportunities] DECAY: Found ' . count($decay_opps) . ' opportunities in ' . round($decay_time, 2) . 's');
                    $formatted_decay = $this->format_gsc_opportunities($decay_opps, 'gsc_decay');
                    $all_opportunities = array_merge($all_opportunities, $formatted_decay);
                    $source_breakdown['gsc_decay'] = count($formatted_decay);
                } else {
                    error_log('[DODO][Keyword Opportunities] DECAY: No opportunities found');
                }
                error_log('[DODO][Keyword Opportunities] === DECAY ENGINE END ===');
            } catch (Exception $e) {
                error_log('[DODO][Keyword Opportunities] DECAY ENGINE ERROR: ' . $e->getMessage());
            }
            
            // GSC Cannibalization opportunities (with timeout protection)
            try {
                error_log('[DODO][Keyword Opportunities] === CANNIBALIZATION ENGINE START ===');
                $cannibal_start = microtime(true);
                $cannibalization_opps = $gsc_intelligence->get_cannibalization_opportunities(28);
                $cannibal_time = microtime(true) - $cannibal_start;
                
                if (!empty($cannibalization_opps)) {
                    error_log('[DODO][Keyword Opportunities] CANNIBALIZATION: Found ' . count($cannibalization_opps) . ' opportunities in ' . round($cannibal_time, 2) . 's');
                    $formatted_cannibal = $this->format_gsc_opportunities($cannibalization_opps, 'gsc_cannibalization');
                    $all_opportunities = array_merge($all_opportunities, $formatted_cannibal);
                    $source_breakdown['gsc_cannibalization'] = count($formatted_cannibal);
                } else {
                    error_log('[DODO][Keyword Opportunities] CANNIBALIZATION: No opportunities found');
                }
                error_log('[DODO][Keyword Opportunities] === CANNIBALIZATION ENGINE END ===');
            } catch (Exception $e) {
                error_log('[DODO][Keyword Opportunities] CANNIBALIZATION ENGINE ERROR: ' . $e->getMessage());
            }
            
            // GSC Query Gap opportunities (with timeout protection)
            try {
                error_log('[DODO][Keyword Opportunities] === QUERY GAP ENGINE START ===');
                $gap_start = microtime(true);
                $gap_opps = $gsc_intelligence->get_query_gap_opportunities(28);
                $gap_time = microtime(true) - $gap_start;
                
                if (!empty($gap_opps)) {
                    error_log('[DODO][Keyword Opportunities] QUERY GAP: Found ' . count($gap_opps) . ' opportunities in ' . round($gap_time, 2) . 's');
                    $formatted_gap = $this->format_gsc_opportunities($gap_opps, 'gsc_gap');
                    $all_opportunities = array_merge($all_opportunities, $formatted_gap);
                    $source_breakdown['gsc_gap'] = count($formatted_gap);
                } else {
                    error_log('[DODO][Keyword Opportunities] QUERY GAP: No opportunities found');
                }
                error_log('[DODO][Keyword Opportunities] === QUERY GAP ENGINE END ===');
            } catch (Exception $e) {
                error_log('[DODO][Keyword Opportunities] QUERY GAP ENGINE ERROR: ' . $e->getMessage());
            }
            
            error_log('[DODO][Keyword Opportunities] Total GSC opportunities: ' . count($all_opportunities));
        } else {
            error_log('[DODO][Keyword Opportunities] GSC not available, will use AI fallback');
        }
        
        // 2. AI ile semantic gap ve ek öneriler üret
        try {
            error_log('[DODO][Keyword Opportunities] === AI FALLBACK START ===');
            $ai_start = microtime(true);
            
            $openai = $this->get_openai();
            
            if ($openai === false) {
                error_log('[DODO][Keyword Opportunities] OpenAI not available, skipping AI opportunities');
            } else {
                $ai_opportunities = $this->generate_ai_opportunities($analysis, count($all_opportunities));
                $ai_time = microtime(true) - $ai_start;
                
                if (!is_wp_error($ai_opportunities)) {
                    error_log('[DODO][Keyword Opportunities] AI: Found ' . count($ai_opportunities) . ' opportunities in ' . round($ai_time, 2) . 's');
                    $all_opportunities = array_merge($all_opportunities, $ai_opportunities);
                    $source_breakdown['ai_suggestion'] = count($ai_opportunities);
                } else {
                    error_log('[DODO][Keyword Opportunities] AI opportunities failed: ' . $ai_opportunities->get_error_message());
                }
            }
            
            error_log('[DODO][Keyword Opportunities] === AI FALLBACK END ===');
        } catch (Throwable $e) {
            error_log('[DODO][Keyword Opportunities] AI FALLBACK ERROR: ' . $e->getMessage());
        }
        
        // 3. Sırala ve limit uygula
        error_log('[DODO][Keyword Opportunities] === SORTING AND LIMITING ===');
        usort($all_opportunities, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        $all_opportunities = array_slice($all_opportunities, 0, $this->max_opportunities);
        
        // 4. Quality filter uygula (Sprint C - Görev 1)
        error_log('[DODO][Keyword Opportunities] === QUALITY FILTER START ===');
        $before_filter_count = count($all_opportunities);
        
        try {
            if (class_exists('DODO_Query_Quality_Engine')) {
                $quality_engine = $this->get_quality_engine();
                $all_opportunities = $quality_engine->filter_opportunities($all_opportunities);
            } else {
                error_log('[DODO][Keyword Opportunities] Quality engine not available, skipping filter');
            }
        } catch (Throwable $e) {
            error_log('[DODO][Keyword Opportunities] Quality filter error: ' . $e->getMessage());
        }
        
        $after_filter_count = count($all_opportunities);
        $filtered_count = $before_filter_count - $after_filter_count;
        error_log('[DODO][Keyword Opportunities] === QUALITY FILTER END ===');
        error_log("[DODO][Keyword Opportunities] Quality filter: {$filtered_count} low-quality queries removed");
        
        // 5. Commercial intent enrichment (Sprint C - Görev 2)
        error_log('[DODO][Keyword Opportunities] === COMMERCIAL INTENT START ===');
        $intent_stats = array();
        
        try {
            if (class_exists('DODO_Commercial_Intent_Engine')) {
                $commercial_engine = $this->get_commercial_engine();
                $all_opportunities = $commercial_engine->enrich_opportunities($all_opportunities);
                $intent_stats = $commercial_engine->get_intent_statistics($all_opportunities);
            } else {
                error_log('[DODO][Keyword Opportunities] Commercial intent engine not available, skipping');
            }
        } catch (Throwable $e) {
            error_log('[DODO][Keyword Opportunities] Commercial intent error: ' . $e->getMessage());
        }
        
        error_log('[DODO][Keyword Opportunities] === COMMERCIAL INTENT END ===');
        error_log('[DODO][Keyword Opportunities] Intent stats: ' . json_encode($intent_stats));
        
        // 6. SERP intelligence enrichment (Sprint C - Görev 7)
        error_log('[DODO][Keyword Opportunities] === SERP INTELLIGENCE START ===');
        
        try {
            if (class_exists('DODO_SERP_Intelligence_Engine')) {
                $serp_engine = $this->get_serp_engine();
                $all_opportunities = $serp_engine->enrich_opportunities($all_opportunities);
            } else {
                error_log('[DODO][Keyword Opportunities] SERP intelligence engine not available, skipping');
            }
        } catch (Throwable $e) {
            error_log('[DODO][Keyword Opportunities] SERP intelligence error: ' . $e->getMessage());
        }
        
        error_log('[DODO][Keyword Opportunities] === SERP INTELLIGENCE END ===');
        
        // 7. Problem analysis for GSC opportunities (Sprint C - Görev 3-6, 8)
        error_log('[DODO][Keyword Opportunities] === PROBLEM ANALYSIS START ===');
        
        try {
            if (class_exists('DODO_SEO_Problem_Analyzer')) {
                $problem_analyzer = $this->get_problem_analyzer();
                $all_opportunities = $this->analyze_problems($all_opportunities, $problem_analyzer);
            } else {
                error_log('[DODO][Keyword Opportunities] Problem analyzer not available, skipping');
            }
        } catch (Throwable $e) {
            error_log('[DODO][Keyword Opportunities] Problem analysis error: ' . $e->getMessage());
        }
        
        error_log('[DODO][Keyword Opportunities] === PROBLEM ANALYSIS END ===');
        
        // 8. Final priority scoring (Sprint C - Görev 9, 12)
        error_log('[DODO][Keyword Opportunities] === PRIORITY SCORING START ===');
        
        try {
            $all_opportunities = $this->calculate_final_priority($all_opportunities);
        } catch (Throwable $e) {
            error_log('[DODO][Keyword Opportunities] Priority scoring error: ' . $e->getMessage());
        }
        
        error_log('[DODO][Keyword Opportunities] === PRIORITY SCORING END ===');
        
        // 9. Revenue intelligence enrichment (Phase 4 - Sprint D)
        error_log('[DODO][Keyword Opportunities] === REVENUE INTELLIGENCE START ===');
        
        try {
            if (class_exists('DODO_Revenue_Engine')) {
                require_once DODO_PLUGIN_DIR . 'includes/intelligence/class-dodo-revenue-engine.php';
                $revenue_engine = new DODO_Revenue_Engine();
                $all_opportunities = $revenue_engine->enrich_opportunities($all_opportunities);
                $revenue_stats = $revenue_engine->get_revenue_statistics($all_opportunities);
                error_log('[DODO][Keyword Opportunities] Revenue stats: ' . json_encode($revenue_stats));
            } else {
                error_log('[DODO][Keyword Opportunities] Revenue engine not available, skipping');
            }
        } catch (Throwable $e) {
            error_log('[DODO][Keyword Opportunities] Revenue intelligence error: ' . $e->getMessage());
        }
        
        error_log('[DODO][Keyword Opportunities] === REVENUE INTELLIGENCE END ===');
        
        // 10. GEO/AI Visibility intelligence enrichment (Phase 4 - Sprint D)
        error_log('[DODO][Keyword Opportunities] === GEO INTELLIGENCE START ===');
        
        try {
            if (class_exists('DODO_GEO_Engine')) {
                require_once DODO_PLUGIN_DIR . 'includes/intelligence/class-dodo-geo-engine.php';
                $geo_engine = new DODO_GEO_Engine();
                $all_opportunities = $geo_engine->enrich_opportunities($all_opportunities);
            } else {
                error_log('[DODO][Keyword Opportunities] GEO intelligence engine not available, skipping');
            }
        } catch (Throwable $e) {
            error_log('[DODO][Keyword Opportunities] GEO intelligence error: ' . $e->getMessage());
        }
        
        error_log('[DODO][Keyword Opportunities] === GEO INTELLIGENCE END ===');
        
        // 11. Topical map intelligence enrichment (Phase 4 - Sprint D)
        error_log('[DODO][Keyword Opportunities] === TOPICAL INTELLIGENCE START ===');
        
        try {
            if (class_exists('DODO_Topical_Map_Engine')) {
                require_once DODO_PLUGIN_DIR . 'includes/intelligence/class-dodo-topical-map-engine.php';
                $topical_engine = new DODO_Topical_Map_Engine();
                $all_opportunities = $topical_engine->enrich_opportunities($all_opportunities);
            } else {
                error_log('[DODO][Keyword Opportunities] Topical map engine not available, skipping');
            }
        } catch (Throwable $e) {
            error_log('[DODO][Keyword Opportunities] Topical intelligence error: ' . $e->getMessage());
        }
        
        error_log('[DODO][Keyword Opportunities] === TOPICAL INTELLIGENCE END ===');
        
        // 12. Business priority scoring with real engines (Phase 4 - Sprint D)
        error_log('[DODO][Keyword Opportunities] === BUSINESS PRIORITY START ===');
        
        try {
            if (class_exists('DODO_Business_Priority_Engine')) {
                require_once DODO_PLUGIN_DIR . 'includes/intelligence/class-dodo-business-priority-engine.php';
                $business_engine = new DODO_Business_Priority_Engine();
                $all_opportunities = $business_engine->rank_by_business_priority($all_opportunities);
            } else {
                // Fallback to manual calculation
                $all_opportunities = $this->calculate_final_business_priority($all_opportunities);
            }
        } catch (Throwable $e) {
            error_log('[DODO][Keyword Opportunities] Business priority error: ' . $e->getMessage());
        }
        
        error_log('[DODO][Keyword Opportunities] === BUSINESS PRIORITY END ===');
        
        error_log('[DODO][Keyword Opportunities] Final opportunity count: ' . count($all_opportunities));
        error_log('[DODO][Keyword Opportunities] Source breakdown: ' . json_encode($source_breakdown));
        error_log('[DODO][Keyword Opportunities] === GENERATE OPPORTUNITIES END ===');
        
        // Return with metadata
        return array(
            'opportunities' => $all_opportunities,
            'source_breakdown' => $source_breakdown,
            'filtered_count' => $filtered_count,
        );
    }
    
    /**
     * GSC fırsatlarını standart formata çevir
     * 
     * @param array $gsc_opportunities
     * @param string $source
     * @return array
     */
    private function format_gsc_opportunities($gsc_opportunities, $source) {
        $formatted = array();
        
        foreach ($gsc_opportunities as $opp) {
            // Unified scoring hesapla
            $unified_score = $this->calculate_unified_score($opp, $source);
            
            $formatted_opp = array(
                'keyword' => $opp['query'] ?? $opp['page'] ?? '',
                'intent' => $this->detect_intent_from_query($opp['query'] ?? ''),
                'content_type' => $this->detect_content_type_from_source($source),
                'suggested_title' => $this->generate_title_from_gsc($opp, $source),
                'score' => $unified_score,
                'reason' => $opp['reason'] ?? $opp['recommendation'] ?? $opp['recommended_action'] ?? $opp['likely_reason'] ?? '',
                'source' => $source,
                'impressions' => $opp['impressions'] ?? 0,
                'clicks' => $opp['clicks'] ?? 0,
                'ctr' => $opp['ctr'] ?? 0,
                'position' => $opp['position'] ?? $opp['current_position'] ?? 0,
                'impact' => $this->calculate_impact_level($opp, $source),
                'effort' => $this->calculate_effort_level($opp, $source),
                'recommended_action' => $opp['recommended_action'] ?? $opp['recommendation'] ?? $opp['action'] ?? '',
                'confidence' => 90, // GSC data is high confidence
            );
            
            $formatted[] = $formatted_opp;
        }
        
        return $formatted;
    }
    
    /**
     * Unified scoring hesapla (0-100)
     * 
     * Skor bileşenleri:
     * - Impressions weight (0-30 puan)
     * - Position opportunity (0-25 puan)
     * - CTR weakness (0-20 puan)
     * - Business relevance (0-15 puan)
     * - Effort level (0-10 puan)
     * 
     * @param array $opp
     * @param string $source
     * @return int
     */
    private function calculate_unified_score($opp, $source) {
        $score = 0;
        
        // 1. Impressions weight (0-30 puan)
        $impressions = $opp['impressions'] ?? 0;
        if ($impressions > 0) {
            if ($impressions >= 1000) {
                $score += 30;
            } elseif ($impressions >= 500) {
                $score += 25;
            } elseif ($impressions >= 200) {
                $score += 20;
            } elseif ($impressions >= 100) {
                $score += 15;
            } else {
                $score += 10;
            }
        }
        
        // 2. Position opportunity (0-25 puan)
        $position = $opp['position'] ?? $opp['current_position'] ?? 0;
        if ($position > 0) {
            if ($position >= 4 && $position <= 10) {
                $score += 25; // İlk sayfada, optimize edilebilir
            } elseif ($position >= 11 && $position <= 20) {
                $score += 20; // İkinci sayfada, potansiyel var
            } elseif ($position <= 3) {
                $score += 15; // Zaten üstte ama CTR düşükse önemli
            } else {
                $score += 10; // Daha geride
            }
        }
        
        // 3. CTR weakness (0-20 puan)
        $ctr = $opp['ctr'] ?? 0;
        $position = $opp['position'] ?? 0;
        if ($ctr > 0 && $position > 0) {
            // Expected CTR'ye göre değerlendir
            $expected_ctr = $this->get_expected_ctr($position);
            $ctr_gap = $expected_ctr - $ctr;
            
            if ($ctr_gap > 5) {
                $score += 20; // Çok düşük CTR, büyük fırsat
            } elseif ($ctr_gap > 3) {
                $score += 15;
            } elseif ($ctr_gap > 1) {
                $score += 10;
            } else {
                $score += 5;
            }
        }
        
        // 4. Business relevance (source'a göre) (0-15 puan)
        switch ($source) {
            case 'gsc_ctr':
                $score += 15; // Yüksek gösterim + düşük CTR = hızlı kazanç
                break;
            case 'gsc_ranking':
                $score += 14; // İlk sayfaya yakın = orta efor, yüksek kazanç
                break;
            case 'gsc_decay':
                $score += 13; // Düşen içerik = acil müdahale
                break;
            case 'gsc_cannibalization':
                $score += 12; // Kannibalizasyon = optimize edilmeli
                break;
            case 'gsc_gap':
                $score += 11; // İçerik boşluğu = yeni içerik fırsatı
                break;
            default:
                $score += 10;
        }
        
        // 5. Effort level (düşük efor = yüksek skor) (0-10 puan)
        $effort = $opp['effort'] ?? $opp['effort_score'] ?? 'medium';
        if (is_numeric($effort)) {
            // Effort score varsa (0-100), tersine çevir
            $score += intval((100 - $effort) / 10);
        } else {
            // String effort
            switch ($effort) {
                case 'low':
                    $score += 10;
                    break;
                case 'medium':
                    $score += 6;
                    break;
                case 'high':
                    $score += 3;
                    break;
            }
        }
        
        // Skoru 0-100 arasında sınırla
        return min(100, max(0, $score));
    }
    
    /**
     * Expected CTR hesapla (pozisyona göre)
     * 
     * @param float $position
     * @return float
     */
    private function get_expected_ctr($position) {
        // Industry standard CTR by position
        $ctr_map = array(
            1 => 28.5,
            2 => 15.7,
            3 => 11.0,
            4 => 8.0,
            5 => 7.2,
            6 => 5.1,
            7 => 4.0,
            8 => 3.2,
            9 => 2.8,
            10 => 2.5,
        );
        
        $pos = intval(round($position));
        
        if (isset($ctr_map[$pos])) {
            return $ctr_map[$pos];
        } elseif ($pos > 10 && $pos <= 20) {
            return 1.5; // İkinci sayfa ortalama
        } else {
            return 0.5; // Daha geride
        }
    }
    
    /**
     * Impact level hesapla
     * 
     * @param array $opp
     * @param string $source
     * @return string
     */
    private function calculate_impact_level($opp, $source) {
        $impressions = $opp['impressions'] ?? 0;
        $position = $opp['position'] ?? $opp['current_position'] ?? 0;
        $ctr = $opp['ctr'] ?? 0;
        
        // Yüksek gösterim = yüksek impact potansiyeli
        if ($impressions >= 500) {
            return 'high';
        }
        
        // İyi pozisyon + düşük CTR = yüksek impact
        if ($position > 0 && $position <= 10 && $ctr < 5) {
            return 'high';
        }
        
        // Orta gösterim veya orta pozisyon
        if ($impressions >= 100 || ($position > 0 && $position <= 20)) {
            return 'medium';
        }
        
        return 'low';
    }
    
    /**
     * Effort level hesapla
     * 
     * @param array $opp
     * @param string $source
     * @return string
     */
    private function calculate_effort_level($opp, $source) {
        $position = $opp['position'] ?? $opp['current_position'] ?? 0;
        
        // Source'a göre base effort
        switch ($source) {
            case 'gsc_ctr':
                // Sadece title/meta description değişikliği
                return 'low';
                
            case 'gsc_ranking':
                // Pozisyona göre
                if ($position > 0 && $position <= 10) {
                    return 'low'; // İlk sayfada, küçük optimizasyon yeter
                } elseif ($position <= 20) {
                    return 'medium'; // İkinci sayfada, orta efor
                } else {
                    return 'high'; // Daha geride, büyük efor
                }
                
            case 'gsc_decay':
                // İçerik güncelleme
                return 'medium';
                
            case 'gsc_cannibalization':
                // Merge veya canonical - teknik iş
                return 'high';
                
            case 'gsc_gap':
                // Yeni içerik oluşturma
                return 'medium';
                
            default:
                return 'medium';
        }
    }
    
    /**
     * AI ile semantic gap ve ek öneriler üret
     * 
     * @param array $analysis
     * @param int $existing_count
     * @return array|WP_Error
     */
    private function generate_ai_opportunities($analysis, $existing_count) {
        // GSC'den yeterli fırsat geldiyse AI'yi sınırla
        $ai_limit = max(10, $this->max_opportunities - $existing_count);
        
        error_log('[DODO][Keyword Opportunities] Generating ' . $ai_limit . ' AI opportunities');
        
        // OpenAI check
        $openai = $this->get_openai();
        if ($openai === false) {
            return new WP_Error('openai_unavailable', 'OpenAI not available');
        }
        
        // AI için prompt hazırla
        $system_prompt = "Sen bir SEO uzmanısın. Verilen site içeriğini analiz edip yeni anahtar kelime fırsatları öneriyorsun.";
        
        $user_prompt = $this->build_opportunities_prompt($analysis, $ai_limit);
        
        // AI'dan öneri al
        try {
            $response = $openai->generate_content($system_prompt, $user_prompt, 'keyword_opportunities');
        } catch (Throwable $e) {
            error_log('[DODO][Keyword Opportunities] AI generation error: ' . $e->getMessage());
            return new WP_Error('ai_generation_failed', $e->getMessage());
        }
        
        if (is_wp_error($response)) {
            error_log('[DODO][Keyword Opportunities] AI failed: ' . $response->get_error_message());
            return $response;
        }
        
        // JSON parse
        $opportunities = $this->parse_opportunities_response($response);
        
        if (is_wp_error($opportunities)) {
            error_log('[DODO][Keyword Opportunities] AI parse failed');
            return $opportunities;
        }
        
        // AI opportunities'e source ekle
        foreach ($opportunities as &$opp) {
            $opp['source'] = 'ai_suggestion';
            $opp['confidence'] = 70; // AI confidence lower than GSC
            $opp['impressions'] = 0;
            $opp['clicks'] = 0;
            $opp['ctr'] = 0;
            $opp['position'] = 0;
            $opp['impact'] = $opp['impact'] ?? 'medium';
            $opp['effort'] = $opp['effort'] ?? 'medium';
            $opp['recommended_action'] = $opp['recommended_action'] ?? 'Yeni içerik oluştur';
        }
        
        return $opportunities;
    }
    
    /**
     * Apply problem analysis to opportunities (Sprint C - Task 8)
     * 
     * @param array $opportunities
     * @param DODO_SEO_Problem_Analyzer $problem_analyzer
     * @return array Enriched opportunities
     */
    private function analyze_problems($opportunities, $problem_analyzer) {
        if (!$problem_analyzer) {
            return $opportunities;
        }
        
        foreach ($opportunities as &$opp) {
            $source = $opp['source'] ?? '';
            
            try {
                // Apply analysis based on source
                if ($source === 'gsc_ctr') {
                    // CTR problem analysis
                    if (method_exists($problem_analyzer, 'analyze_ctr_problem')) {
                        $ctr_analysis = $problem_analyzer->analyze_ctr_problem($opp);
                        $opp['problem_analysis'] = $ctr_analysis;
                        $opp['quick_wins'] = $ctr_analysis['quick_wins'] ?? array();
                        $opp['seo_recommendations'] = $ctr_analysis['recommendations'] ?? array();
                        $opp['problem_severity'] = $ctr_analysis['severity'] ?? 'medium';
                    }
                    
                } elseif ($source === 'gsc_decay') {
                    // Decay problem analysis
                    if (method_exists($problem_analyzer, 'analyze_decay_problem')) {
                        $decay_analysis = $problem_analyzer->analyze_decay_problem($opp);
                        $opp['problem_analysis'] = $decay_analysis;
                        $opp['decay_reasons'] = $decay_analysis['decay_reasons'] ?? array();
                        $opp['seo_recommendations'] = $decay_analysis['recommendations'] ?? array();
                        $opp['problem_severity'] = $decay_analysis['priority_level'] ?? 'medium';
                    }
                    
                } elseif ($source === 'gsc_cannibalization') {
                    // Cannibalization problem analysis
                    if (method_exists($problem_analyzer, 'analyze_cannibalization')) {
                        $cannibal_analysis = $problem_analyzer->analyze_cannibalization($opp);
                        $opp['problem_analysis'] = $cannibal_analysis;
                        $opp['competing_urls'] = $cannibal_analysis['competing_urls'] ?? array();
                        $opp['primary_url'] = $cannibal_analysis['primary_url'] ?? '';
                        $opp['suggested_fix'] = $cannibal_analysis['suggested_fix'] ?? '';
                        $opp['seo_recommendations'] = $cannibal_analysis['recommendations'] ?? array();
                        $opp['problem_severity'] = 'high'; // Cannibalization is always high priority
                    }
                }
            } catch (Throwable $e) {
                error_log('[DODO][Keyword Opportunities] Problem analysis error for ' . ($opp['keyword'] ?? 'unknown') . ': ' . $e->getMessage());
            }
        }
        
        return $opportunities;
    }
    
    /**
     * Calculate final priority score (Sprint C - Task 9, 12)
     * 
     * New scoring components:
     * - Base score (from unified scoring)
     * - Commercial intent boost (+10 for high commercial)
     * - Money keyword boost (+15 for transactional)
     * - Quality penalty (-20 for medium quality)
     * - Problem severity boost (+10 for critical problems)
     * - Trend velocity (future enhancement)
     * 
     * @param array $opportunities
     * @return array Opportunities with final priority scores
     */
    private function calculate_final_priority($opportunities) {
        foreach ($opportunities as &$opp) {
            $base_score = $opp['score'] ?? 50;
            $final_score = $base_score;
            
            // 1. Commercial intent boost
            $commercial_score = $opp['commercial_score'] ?? 0;
            if ($commercial_score >= 70) {
                $final_score += 10;
            } elseif ($commercial_score >= 40) {
                $final_score += 5;
            }
            
            // 2. Money keyword boost (Task 12)
            $intent_type = $opp['intent_type'] ?? 'informational';
            if ($intent_type === 'transactional') {
                $final_score += 15; // Transactional = money keywords
            } elseif ($intent_type === 'commercial') {
                $final_score += 10;
            }
            
            // 3. Quality penalty
            $query_quality = $opp['query_quality'] ?? 'high';
            if ($query_quality === 'medium') {
                $final_score -= 10;
            } elseif ($query_quality === 'low') {
                $final_score -= 20;
            }
            
            // 4. Problem severity boost
            $problem_severity = $opp['problem_severity'] ?? '';
            if ($problem_severity === 'critical') {
                $final_score += 10;
            } elseif ($problem_severity === 'high') {
                $final_score += 7;
            } elseif ($problem_severity === 'medium') {
                $final_score += 3;
            }
            
            // 5. Effort adjustment (lower effort = higher priority)
            $effort = $opp['effort'] ?? 'medium';
            if ($effort === 'low') {
                $final_score += 5; // Quick wins get boost
            } elseif ($effort === 'high') {
                $final_score -= 5; // High effort gets penalty
            }
            
            // 6. Impact boost
            $impact = $opp['impact'] ?? 'medium';
            if ($impact === 'high') {
                $final_score += 8;
            } elseif ($impact === 'low') {
                $final_score -= 5;
            }
            
            // Ensure score is 0-100
            $final_score = max(0, min(100, $final_score));
            
            // Update score
            $opp['score'] = intval($final_score);
            $opp['priority_score'] = intval($final_score);
        }
        
        // Re-sort by final priority
        usort($opportunities, function($a, $b) {
            return $b['priority_score'] <=> $a['priority_score'];
        });
        
        return $opportunities;
    }
    
    /**
     * Calculate final business priority (Sprint D - Task 7)
     * 
     * Advanced scoring with revenue, GEO, topical authority
     * 
     * @param array $opportunities
     * @return array Opportunities with business priority scores
     */
    private function calculate_final_business_priority($opportunities) {
        foreach ($opportunities as &$opp) {
            $business_score = 0;
            
            // 1. Revenue score (0-25 points)
            $revenue_score = $opp['revenue_score'] ?? 0;
            $business_score += ($revenue_score / 100) * 25;
            
            // 2. GEO/AI visibility score (0-15 points)
            $geo_score = $opp['geo_score'] ?? 0;
            $business_score += ($geo_score / 100) * 15;
            
            // 3. Topical authority gap (0-15 points)
            // Lower gap = higher score (we have authority)
            $authority_gap = $opp['authority_gap'] ?? 50;
            $authority_score = 100 - $authority_gap;
            $business_score += ($authority_score / 100) * 15;
            
            // 4. Conversion probability (0-15 points)
            $conversion_prob = $opp['conversion_probability'] ?? 0;
            $business_score += ($conversion_prob / 100) * 15;
            
            // 5. Internal link potential (0-10 points)
            $link_equity_gap = $opp['link_equity_gap'] ?? 0;
            $business_score += ($link_equity_gap / 100) * 10;
            
            // 6. SEO score (base) (0-20 points)
            $seo_score = $opp['priority_score'] ?? $opp['score'] ?? 50;
            $business_score += ($seo_score / 100) * 20;
            
            // Ensure 0-100
            $business_score = max(0, min(100, round($business_score)));
            
            // Update business priority
            $opp['business_priority'] = intval($business_score);
            
            // Determine priority level
            if ($business_score >= 80) {
                $opp['priority_level'] = 'critical';
            } elseif ($business_score >= 60) {
                $opp['priority_level'] = 'high';
            } elseif ($business_score >= 40) {
                $opp['priority_level'] = 'medium';
            } else {
                $opp['priority_level'] = 'low';
            }
        }
        
        // Re-sort by business priority
        usort($opportunities, function($a, $b) {
            $a_priority = $a['business_priority'] ?? 0;
            $b_priority = $b['business_priority'] ?? 0;
            return $b_priority <=> $a_priority;
        });
        
        return $opportunities;
    }
    
    /**
     * Query'den intent tespit et
     */
    private function detect_intent_from_query($query) {
        $query_lower = mb_strtolower($query, 'UTF-8');
        
        if (strpos($query_lower, 'nasıl') !== false || strpos($query_lower, 'how') !== false) {
            return 'bilgilendirici';
        } elseif (strpos($query_lower, 'satın al') !== false || strpos($query_lower, 'buy') !== false || strpos($query_lower, 'fiyat') !== false) {
            return 'satın_alma';
        } elseif (strpos($query_lower, 'vs') !== false || strpos($query_lower, 'karşılaştırma') !== false || strpos($query_lower, 'en iyi') !== false) {
            return 'karşılaştırma';
        } else {
            return 'bilgilendirici';
        }
    }
    
    /**
     * Source'dan content type tespit et
     */
    private function detect_content_type_from_source($source) {
        switch ($source) {
            case 'gsc_ctr':
                return 'blog';
            case 'gsc_ranking':
                return 'howto';
            case 'gsc_decay':
                return 'blog';
            case 'gsc_cannibalization':
                return 'guide';
            case 'gsc_gap':
                return 'blog';
            default:
                return 'blog';
        }
    }
    
    /**
     * GSC verisinden title üret
     */
    private function generate_title_from_gsc($opp, $source) {
        $query = $opp['query'] ?? '';
        
        if (empty($query)) {
            return 'Yeni İçerik Fırsatı';
        }
        
        // Capitalize first letter of each word
        $title = mb_convert_case($query, MB_CASE_TITLE, 'UTF-8');
        
        // Add context based on source
        switch ($source) {
            case 'gsc_ctr':
                return $title . ' - Kapsamlı Rehber';
            case 'gsc_ranking':
                return $title . ' Hakkında Bilmeniz Gerekenler';
            case 'gsc_decay':
                return $title . ' - Güncel Bilgiler';
            case 'gsc_gap':
                return $title . ' - Detaylı İnceleme';
            default:
                return $title;
        }
    }
    
    /**
     * Opportunities prompt'unu oluştur
     * 
     * @param array $analysis
     * @param int $limit
     * @return string
     */
    private function build_opportunities_prompt($analysis, $limit = 50) {
        $prompt = "# Site İçerik Analizi\n\n";
        
        // İstatistikler
        $prompt .= "## İstatistikler:\n";
        $prompt .= "- Toplam blog yazısı: {$analysis['stats']['total_posts']}\n";
        $prompt .= "- Toplam kategori: {$analysis['stats']['total_categories']}\n";
        $prompt .= "- Toplam etiket: {$analysis['stats']['total_tags']}\n";
        if ($analysis['stats']['total_products'] > 0) {
            $prompt .= "- Toplam ürün: {$analysis['stats']['total_products']}\n";
        }
        $prompt .= "\n";
        
        // Mevcut focus keyword'ler
        $prompt .= "## Mevcut Focus Keyword'ler (Tekrar Önerme):\n";
        $existing_keywords = array_slice($analysis['focus_keywords'], 0, 30);
        foreach ($existing_keywords as $fk) {
            $prompt .= "- {$fk['keyword']} ({$fk['count']} yazı)\n";
        }
        $prompt .= "\n";
        
        // Kategoriler
        $prompt .= "## Kategoriler:\n";
        foreach (array_slice($analysis['categories'], 0, 15) as $cat) {
            $prompt .= "- {$cat['name']} ({$cat['count']} yazı)\n";
        }
        $prompt .= "\n";
        
        // Popüler konular
        $prompt .= "## Popüler Konular:\n";
        $top_topics = array_slice($analysis['topic_clusters'], 0, 15, true);
        foreach ($top_topics as $topic => $count) {
            $prompt .= "- {$topic} ({$count} kez geçiyor)\n";
        }
        $prompt .= "\n";
        
        // Talimatlar
        $prompt .= "## Görevin:\n";
        $prompt .= "Yukarıdaki site içeriğine göre {$limit} adet YENİ anahtar kelime fırsatı öner.\n\n";
        $prompt .= "**KURALLAR:**\n";
        $prompt .= "1. Mevcut focus keyword'leri TEKRAR ÖNERME\n";
        $prompt .= "2. Aynı veya çok benzer keyword'leri tekrar önerme\n";
        $prompt .= "3. Farklı intent'ler için benzer keyword önerilebilir:\n";
        $prompt .= "   - Örnek: 'karbür matkap ucu' varsa 'karbür matkap ucu nasıl seçilir' önerilebilir\n";
        $prompt .= "4. Long-tail keyword'lere odaklan\n";
        $prompt .= "5. Sitedeki mevcut konularla ilgili ama eksik kalan açıları bul\n";
        $prompt .= "6. Her öneri için:\n";
        $prompt .= "   - keyword: anahtar kelime\n";
        $prompt .= "   - intent: bilgilendirici / ticari / satın_alma / karşılaştırma\n";
        $prompt .= "   - content_type: blog / howto / comparison / listicle / guide / category / faq\n";
        $prompt .= "   - suggested_title: önerilen başlık (60 karakter max)\n";
        $prompt .= "   - score: fırsat skoru (1-100)\n";
        $prompt .= "   - reason: neden önerildi (kısa açıklama)\n";
        $prompt .= "   - impact: low / medium / high\n";
        $prompt .= "   - effort: low / medium / high\n";
        $prompt .= "   - recommended_action: önerilen aksiyon\n\n";
        $prompt .= "Yanıtını şu JSON formatında ver:\n";
        $prompt .= "```json\n";
        $prompt .= "{\n";
        $prompt .= '  "opportunities": [' . "\n";
        $prompt .= '    {' . "\n";
        $prompt .= '      "keyword": "örnek anahtar kelime",' . "\n";
        $prompt .= '      "intent": "bilgilendirici",' . "\n";
        $prompt .= '      "content_type": "howto",' . "\n";
        $prompt .= '      "suggested_title": "Örnek Başlık",' . "\n";
        $prompt .= '      "score": 85,' . "\n";
        $prompt .= '      "reason": "Sitede bu konu eksik",' . "\n";
        $prompt .= '      "impact": "high",' . "\n";
        $prompt .= '      "effort": "medium",' . "\n";
        $prompt .= '      "recommended_action": "Yeni içerik oluştur"' . "\n";
        $prompt .= '    }' . "\n";
        $prompt .= '  ]' . "\n";
        $prompt .= "}\n";
        $prompt .= "```\n\n";
        $prompt .= "**ÖNEMLİ:** Sadece JSON çıktısı ver, başka açıklama ekleme.";
        
        return $prompt;
    }
    
    /**
     * AI yanıtını parse et
     * 
     * @param string $response
     * @return array|WP_Error
     */
    private function parse_opportunities_response($response) {
        // JSON bloğunu bul
        if (preg_match('/```json\s*(\{.*?\})\s*```/s', $response, $matches)) {
            $json_string = $matches[1];
        } else {
            $json_string = $response;
        }
        
        $data = json_decode($json_string, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_parse_error', 'AI yanıtı parse edilemedi: ' . json_last_error_msg());
        }
        
        if (empty($data['opportunities']) || !is_array($data['opportunities'])) {
            return new WP_Error('invalid_response', 'AI yanıtında opportunities bulunamadı');
        }
        
        return $data['opportunities'];
    }
    
    /**
     * Fırsatları veritabanına kaydet
     * 
     * @param array $opportunities_data Opportunities + metadata
     * @param array $analysis
     * @return array Result with counts
     */
    public function save_opportunities($opportunities_data, $analysis) {
        global $wpdb;
        
        error_log('[DODO][Keyword Opportunities] === SAVE OPPORTUNITIES START ===');
        
        // Handle both old format (array) and new format (array with metadata)
        if (isset($opportunities_data['opportunities'])) {
            $opportunities = $opportunities_data['opportunities'];
            $source_breakdown = $opportunities_data['source_breakdown'] ?? array();
        } else {
            $opportunities = $opportunities_data;
            $source_breakdown = array();
        }
        
        error_log('[DODO][Keyword Opportunities] Saving ' . count($opportunities) . ' opportunities');
        
        $saved_count = 0;
        $duplicate_count = 0;
        $error_count = 0;
        
        foreach ($opportunities as $index => $opp) {
            // Zorunlu alanları kontrol et
            if (empty($opp['keyword']) || empty($opp['intent']) || empty($opp['suggested_title'])) {
                error_log("[DODO][Keyword Opportunities] Opportunity #{$index} skipped: missing required fields");
                $error_count++;
                continue;
            }
            
            // Duplicate kontrolü
            if ($this->is_duplicate_keyword($opp['keyword'], $analysis)) {
                $duplicate_count++;
                error_log("[DODO][Keyword Opportunities] Opportunity #{$index} skipped: duplicate keyword '{$opp['keyword']}'");
                continue;
            }
            
            // Benzer içerik var mı kontrol et
            $similar_check = $this->check_similar_content($opp['keyword']);
            
            // Veritabanına kaydet (yeni kolonlarla)
            $result = $wpdb->insert(
                $this->table_name,
                array(
                    'keyword' => sanitize_text_field($opp['keyword']),
                    'intent' => sanitize_text_field($opp['intent']),
                    'suggested_title' => sanitize_text_field($opp['suggested_title']),
                    'content_type' => sanitize_text_field($opp['content_type'] ?? 'blog'),
                    'score' => absint($opp['score'] ?? 50),
                    'status' => 'new',
                    'reason' => sanitize_textarea_field($opp['reason'] ?? ''),
                    'similar_content_exists' => $similar_check['exists'] ? 1 : 0,
                    'similar_content_ids' => !empty($similar_check['post_ids']) ? implode(',', $similar_check['post_ids']) : '',
                    'source' => sanitize_text_field($opp['source'] ?? 'ai_suggestion'),
                    'impressions' => absint($opp['impressions'] ?? 0),
                    'clicks' => absint($opp['clicks'] ?? 0),
                    'ctr' => floatval($opp['ctr'] ?? 0),
                    'position' => floatval($opp['position'] ?? 0),
                    'impact' => sanitize_text_field($opp['impact'] ?? 'medium'),
                    'effort' => sanitize_text_field($opp['effort'] ?? 'medium'),
                    'recommended_action' => sanitize_textarea_field($opp['recommended_action'] ?? ''),
                    'confidence' => absint($opp['confidence'] ?? 70),
                    'quality_score' => absint($opp['quality_score'] ?? 100),
                    'query_quality' => sanitize_text_field($opp['query_quality'] ?? 'high'),
                    'commercial_score' => absint($opp['commercial_score'] ?? 0),
                    'intent_type' => sanitize_text_field($opp['intent_type'] ?? 'informational'),
                    'commercial_level' => sanitize_text_field($opp['commercial_level'] ?? 'low'),
                    'priority_score' => absint($opp['priority_score'] ?? $opp['score'] ?? 50),
                    'problem_severity' => sanitize_text_field($opp['problem_severity'] ?? ''),
                    'serp_type' => sanitize_text_field($opp['serp_type'] ?? ''),
                    'serp_features' => !empty($opp['serp_features']) ? wp_json_encode($opp['serp_features']) : '',
                    'quick_wins' => !empty($opp['quick_wins']) ? wp_json_encode($opp['quick_wins']) : '',
                    'seo_recommendations' => !empty($opp['seo_recommendations']) ? wp_json_encode($opp['seo_recommendations']) : '',
                    'problem_analysis' => !empty($opp['problem_analysis']) ? wp_json_encode($opp['problem_analysis']) : '',
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql'),
                ),
                array('%s', '%s', '%s', '%s', '%d', '%s', '%s', '%d', '%s', '%s', '%d', '%d', '%f', '%f', '%s', '%s', '%s', '%d', '%d', '%s', '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
            );
            
            if ($result) {
                $saved_count++;
                if ($saved_count % 10 == 0) {
                    error_log("[DODO][Keyword Opportunities] Progress: {$saved_count} opportunities saved");
                }
            } else {
                $error_count++;
                error_log("[DODO][Keyword Opportunities] DB INSERT ERROR for opportunity #{$index}: " . $wpdb->last_error);
            }
        }
        
        error_log("[DODO][Keyword Opportunities] === SAVE OPPORTUNITIES END ===");
        error_log("[DODO][Keyword Opportunities] Saved: {$saved_count}, Duplicates: {$duplicate_count}, Errors: {$error_count}");
        
        return array(
            'saved_count' => $saved_count,
            'duplicate_count' => $duplicate_count,
            'error_count' => $error_count,
            'source_breakdown' => $source_breakdown,
        );
    }
    
    /**
     * Keyword duplicate mi kontrol et (Sprint C - Task 13: False Positive Reduction)
     * 
     * Improved duplicate detection with:
     * - Semantic similarity check
     * - Intent-based differentiation
     * - SERP overlap check
     * - Primary keyword extraction
     * 
     * @param string $keyword
     * @param array $analysis
     * @return bool
     */
    private function is_duplicate_keyword($keyword, $analysis) {
        $keyword_lower = mb_strtolower($keyword, 'UTF-8');
        
        // Extract primary keyword (remove modifiers)
        $primary_keyword = $this->extract_primary_keyword($keyword_lower);
        
        // Mevcut focus keyword'lerle karşılaştır
        foreach ($analysis['focus_keywords'] as $fk) {
            $existing_lower = mb_strtolower($fk['keyword'], 'UTF-8');
            $existing_primary = $this->extract_primary_keyword($existing_lower);
            
            // 1. Tam eşleşme - kesinlikle duplicate
            if ($keyword_lower === $existing_lower) {
                return true;
            }
            
            // 2. Primary keyword aynı mı?
            if ($primary_keyword === $existing_primary) {
                // Primary keyword aynı ama intent farklı olabilir
                $new_intent = $this->detect_keyword_intent($keyword_lower);
                $existing_intent = $this->detect_keyword_intent($existing_lower);
                
                // Intent farklıysa duplicate değil
                if ($new_intent !== $existing_intent) {
                    continue; // Not duplicate - different intent
                }
                
                // Intent de aynıysa duplicate
                return true;
            }
            
            // 3. Semantic similarity check (Levenshtein)
            $distance = levenshtein($keyword_lower, $existing_lower);
            $max_length = max(mb_strlen($keyword_lower), mb_strlen($existing_lower));
            
            if ($max_length > 0) {
                $similarity = 1 - ($distance / $max_length);
                
                // %95'ten fazla benzer - duplicate
                if ($similarity > 0.95) {
                    return true;
                }
            }
            
            // 4. Word overlap check (but not too aggressive)
            $overlap_ratio = $this->calculate_word_overlap($keyword_lower, $existing_lower);
            
            // %90'dan fazla kelime overlap + benzer uzunluk = duplicate
            if ($overlap_ratio > 0.9) {
                $length_ratio = min($max_length, mb_strlen($keyword_lower)) / max($max_length, mb_strlen($keyword_lower));
                if ($length_ratio > 0.8) {
                    return true;
                }
            }
        }
        
        // 5. Veritabanında var mı kontrol et
        global $wpdb;
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE keyword = %s",
            $keyword
        ));
        
        return $exists > 0;
    }
    
    /**
     * Extract primary keyword (remove modifiers)
     * 
     * @param string $keyword
     * @return string
     */
    private function extract_primary_keyword($keyword) {
        // Remove common modifiers
        $modifiers = array(
            'nasıl', 'nedir', 'ne demek', 'neden', 'ne zaman', 'nerede',
            'en iyi', 'fiyat', 'fiyatları', 'satın al', 'tavsiye',
            'karşılaştırma', 'vs', 'hakkında', 'için', 'ile',
            'how to', 'what is', 'why', 'when', 'where',
            'best', 'price', 'buy', 'compare', 'vs', 'about',
            '2024', '2025', '2026', // Years
        );
        
        $primary = $keyword;
        
        foreach ($modifiers as $modifier) {
            $primary = preg_replace('/\b' . preg_quote($modifier, '/') . '\b/i', '', $primary);
        }
        
        // Clean up extra spaces
        $primary = preg_replace('/\s+/', ' ', trim($primary));
        
        return $primary;
    }
    
    /**
     * Detect keyword intent
     * 
     * @param string $keyword
     * @return string informational|commercial|transactional|navigational
     */
    private function detect_keyword_intent($keyword) {
        // Transactional
        if (preg_match('/satın\s+al|buy|sipariş|order/i', $keyword)) {
            return 'transactional';
        }
        
        // Commercial
        if (preg_match('/fiyat|price|en\s+iyi|best|karşılaştırma|compare|vs|tavsiye/i', $keyword)) {
            return 'commercial';
        }
        
        // Navigational
        if (preg_match('/giriş|login|kayıt|register|iletişim|contact/i', $keyword)) {
            return 'navigational';
        }
        
        // Informational (default)
        return 'informational';
    }
    
    /**
     * Calculate word overlap ratio
     * 
     * @param string $keyword1
     * @param string $keyword2
     * @return float 0-1
     */
    private function calculate_word_overlap($keyword1, $keyword2) {
        $words1 = explode(' ', $keyword1);
        $words2 = explode(' ', $keyword2);
        
        $common_words = array_intersect($words1, $words2);
        $total_unique_words = count(array_unique(array_merge($words1, $words2)));
        
        if ($total_unique_words === 0) {
            return 0;
        }
        
        return count($common_words) / $total_unique_words;
    }
    
    /**
     * Benzer içerik var mı kontrol et
     * 
     * @param string $keyword
     * @return array
     */
    private function check_similar_content($keyword) {
        global $wpdb;
        
        // Keyword'ü kelimelere ayır
        $words = explode(' ', $keyword);
        $search_terms = array();
        
        foreach ($words as $word) {
            if (mb_strlen($word, 'UTF-8') >= 3) {
                $search_terms[] = $word;
            }
        }
        
        if (empty($search_terms)) {
            return array('exists' => false, 'post_ids' => array());
        }
        
        // Başlıklarda ara
        $like_conditions = array();
        foreach ($search_terms as $term) {
            $like_conditions[] = $wpdb->prepare("post_title LIKE %s", '%' . $wpdb->esc_like($term) . '%');
        }
        
        $where = implode(' OR ', $like_conditions);
        
        $query = "SELECT ID FROM {$wpdb->posts} 
                  WHERE post_type = 'post' 
                  AND post_status = 'publish' 
                  AND ({$where}) 
                  LIMIT 5";
        
        $post_ids = $wpdb->get_col($query);
        
        return array(
            'exists' => !empty($post_ids),
            'post_ids' => $post_ids,
        );
    }
    
    /**
     * Tüm fırsatları al
     * 
     * @param array $args
     * @return array
     */
    public function get_opportunities($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'status' => 'all',
            'orderby' => 'score',
            'order' => 'DESC',
            'limit' => 50,
            'offset' => 0,
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = '1=1';
        
        if ($args['status'] !== 'all') {
            $where .= $wpdb->prepare(' AND status = %s', $args['status']);
        }
        
        $orderby = in_array($args['orderby'], array('score', 'created_at', 'keyword')) ? $args['orderby'] : 'score';
        $order = $args['order'] === 'ASC' ? 'ASC' : 'DESC';
        
        $query = "SELECT * FROM {$this->table_name} 
                  WHERE {$where} 
                  ORDER BY {$orderby} {$order} 
                  LIMIT %d OFFSET %d";
        
        $results = $wpdb->get_results($wpdb->prepare($query, $args['limit'], $args['offset']), ARRAY_A);
        
        return $results;
    }
    
    /**
     * Fırsat durumunu güncelle
     * 
     * @param int $id
     * @param string $status
     * @param string $publish_mode - Opsiyonel publish mode
     * @param string $scheduled_date - Opsiyonel scheduled date
     * @param string $scheduled_time - Opsiyonel scheduled time
     * @return bool
     */
    public function update_status($id, $status, $publish_mode = null, $scheduled_date = null, $scheduled_time = null) {
        global $wpdb;
        
        $allowed_statuses = array('new', 'queued', 'processing', 'created', 'failed', 'ignored');
        
        if (!in_array($status, $allowed_statuses)) {
            return false;
        }
        
        $data = array(
            'status' => $status,
            'updated_at' => current_time('mysql'),
        );
        
        $format = array('%s', '%s');
        
        // Publish mode varsa ekle
        if ($publish_mode !== null) {
            $data['publish_mode'] = $publish_mode;
            $format[] = '%s';
        }
        
        // Scheduled date varsa ekle
        if ($scheduled_date !== null) {
            $data['scheduled_date'] = $scheduled_date;
            $format[] = '%s';
        }
        
        // Scheduled time varsa ekle
        if ($scheduled_time !== null) {
            $data['scheduled_time'] = $scheduled_time;
            $format[] = '%s';
        }
        
        $result = $wpdb->update(
            $this->table_name,
            $data,
            array('id' => $id),
            $format,
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Fırsatı sil
     * 
     * @param int $id
     * @return bool
     */
    public function delete_opportunity($id) {
        global $wpdb;
        
        $result = $wpdb->delete(
            $this->table_name,
            array('id' => $id),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Tüm fırsatları sil
     * 
     * @return bool
     */
    public function delete_all_opportunities() {
        global $wpdb;
        
        $result = $wpdb->query("TRUNCATE TABLE {$this->table_name}");
        
        return $result !== false;
    }
    
    /**
     * İstatistikleri al
     * 
     * @return array
     */
    public function get_stats() {
        global $wpdb;
        
        $stats = array(
            'total' => 0,
            'new' => 0,
            'queued' => 0,
            'processing' => 0,
            'ignored' => 0,
            'created' => 0,
            'failed' => 0,
        );
        
        $results = $wpdb->get_results(
            "SELECT status, COUNT(*) as count FROM {$this->table_name} GROUP BY status",
            ARRAY_A
        );
        
        foreach ($results as $row) {
            $stats[$row['status']] = (int) $row['count'];
            $stats['total'] += (int) $row['count'];
        }
        
        return $stats;
    }
    
    /**
     * Kuyruktaki bir sonraki fırsatı al
     * 
     * @return array|null
     */
    public function get_next_queued_opportunity() {
        global $wpdb;
        
        $opportunity = $wpdb->get_row(
            "SELECT * FROM {$this->table_name} 
             WHERE status = 'queued' 
             ORDER BY score DESC, created_at ASC 
             LIMIT 1",
            ARRAY_A
        );
        
        return $opportunity;
    }
    
    /**
     * Fırsat durumunu ve post ID'sini güncelle
     * 
     * @param int $id
     * @param string $status
     * @param int $post_id
     * @param string $error_message
     * @return bool
     */
    public function update_opportunity_result($id, $status, $post_id = 0, $error_message = '') {
        global $wpdb;
        
        $allowed_statuses = array('new', 'queued', 'processing', 'created', 'failed', 'ignored');
        
        if (!in_array($status, $allowed_statuses)) {
            return false;
        }
        
        $data = array(
            'status' => $status,
            'updated_at' => current_time('mysql'),
        );
        
        if ($post_id > 0) {
            $data['similar_content_ids'] = $post_id; // Post ID'yi buraya kaydediyoruz
        }
        
        if (!empty($error_message)) {
            $data['reason'] = sanitize_textarea_field($error_message);
        }
        
        $result = $wpdb->update(
            $this->table_name,
            $data,
            array('id' => $id),
            array_fill(0, count($data), '%s'),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Kuyruk istatistiklerini al
     * 
     * @return array
     */
    public function get_queue_stats() {
        global $wpdb;
        
        $stats = array(
            'queued' => 0,
            'processing' => 0,
            'created' => 0,
            'failed' => 0,
        );
        
        $results = $wpdb->get_results(
            "SELECT status, COUNT(*) as count 
             FROM {$this->table_name} 
             WHERE status IN ('queued', 'processing', 'created', 'failed') 
             GROUP BY status",
            ARRAY_A
        );
        
        foreach ($results as $row) {
            $stats[$row['status']] = (int) $row['count'];
        }
        
        return $stats;
    }
    
    /**
     * Retry edilebilir fırsatları al
     * 
     * @return array
     */
    public function get_retryable_items() {
        global $wpdb;
        
        $settings = new DODO_Settings();
        $max_retry = $settings->get_setting('max_retry_count', 3);
        $retry_enabled = $settings->get_setting('retry_enabled', true);
        
        if (!$retry_enabled) {
            return array();
        }
        
        $now = current_time('mysql');
        
        $query = $wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
             WHERE status = 'failed' 
             AND (retry_count < %d OR retry_count IS NULL)
             AND (next_retry_at IS NULL OR next_retry_at <= %s)
             ORDER BY score DESC, created_at ASC 
             LIMIT 10",
            $max_retry,
            $now
        );
        
        $results = $wpdb->get_results($query, ARRAY_A);
        
        error_log("[DODO RETRY] Found " . count($results) . " retryable items");
        
        return $results;
    }
    
    /**
     * Retry bilgilerini güncelle (başarısız olduğunda)
     * 
     * @param int $id
     * @param string $error_message
     * @param string $error_category
     * @return bool
     */
    public function update_retry_info($id, $error_message, $error_category = 'unknown_error') {
        global $wpdb;
        
        // Mevcut retry_count'u al
        $current = $wpdb->get_row($wpdb->prepare(
            "SELECT retry_count FROM {$this->table_name} WHERE id = %d",
            $id
        ));
        
        $retry_count = isset($current->retry_count) ? intval($current->retry_count) : 0;
        $retry_count++;
        
        // Next retry time hesapla (exponential backoff)
        $next_retry_at = $this->calculate_next_retry_time($retry_count);
        
        // Error message formatla
        $formatted_error = json_encode(array(
            'category' => $error_category,
            'message' => $error_message,
            'timestamp' => current_time('mysql'),
        ));
        
        $result = $wpdb->update(
            $this->table_name,
            array(
                'status' => 'failed',
                'retry_count' => $retry_count,
                'last_retry_at' => current_time('mysql'),
                'last_error' => $formatted_error,
                'next_retry_at' => $next_retry_at,
                'updated_at' => current_time('mysql'),
            ),
            array('id' => $id),
            array('%s', '%d', '%s', '%s', '%s', '%s'),
            array('%d')
        );
        
        error_log("[DODO RETRY] Updated retry info for #{$id}: retry_count={$retry_count}, next_retry={$next_retry_at}");
        
        return $result !== false;
    }
    
    /**
     * Bir sonraki retry zamanını hesapla (exponential backoff)
     * 
     * @param int $retry_count
     * @return string MySQL datetime
     */
    private function calculate_next_retry_time($retry_count) {
        // Exponential backoff: 5 min, 15 min, 60 min
        $delays = array(
            1 => 5,    // 5 minutes
            2 => 15,   // 15 minutes
            3 => 60,   // 1 hour
        );
        
        $delay_minutes = isset($delays[$retry_count]) ? $delays[$retry_count] : 60;
        
        $next_retry = strtotime("+{$delay_minutes} minutes");
        
        return date('Y-m-d H:i:s', $next_retry);
    }
    
    /**
     * Kalıcı başarısız olarak işaretle (max retry aşıldı)
     * 
     * @param int $id
     * @param string $error_message
     * @param string $error_category
     * @return bool
     */
    public function mark_permanently_failed($id, $error_message, $error_category = 'unknown_error') {
        global $wpdb;
        
        // Error message formatla
        $formatted_error = json_encode(array(
            'category' => $error_category,
            'message' => $error_message,
            'timestamp' => current_time('mysql'),
            'permanently_failed' => true,
        ));
        
        $result = $wpdb->update(
            $this->table_name,
            array(
                'status' => 'permanently_failed',
                'last_error' => $formatted_error,
                'updated_at' => current_time('mysql'),
            ),
            array('id' => $id),
            array('%s', '%s', '%s'),
            array('%d')
        );
        
        error_log("[DODO RETRY] Marked #{$id} as permanently_failed");
        
        return $result !== false;
    }
    
    /**
     * Manuel retry - item'ı tekrar kuyruğa ekle
     * 
     * @param int $id
     * @return bool
     */
    public function manual_retry($id) {
        global $wpdb;
        
        // Retry count'u koru, sadece status'u değiştir
        $result = $wpdb->update(
            $this->table_name,
            array(
                'status' => 'queued',
                'next_retry_at' => NULL,
                'updated_at' => current_time('mysql'),
            ),
            array('id' => $id),
            array('%s', '%s', '%s'),
            array('%d')
        );
        
        error_log("[DODO RETRY] Manual retry for #{$id}, status set to queued");
        
        return $result !== false;
    }
    
    /**
     * Başarılı olduğunda retry bilgilerini temizle
     * 
     * @param int $id
     * @return bool
     */
    public function clear_retry_info_on_success($id) {
        global $wpdb;
        
        $result = $wpdb->update(
            $this->table_name,
            array(
                'retry_count' => 0,
                'last_retry_at' => NULL,
                'last_error' => NULL,
                'next_retry_at' => NULL,
                'updated_at' => current_time('mysql'),
            ),
            array('id' => $id),
            array('%d', '%s', '%s', '%s', '%s'),
            array('%d')
        );
        
        error_log("[DODO RETRY] Cleared retry info for #{$id} after success");
        
        return $result !== false;
    }
}
