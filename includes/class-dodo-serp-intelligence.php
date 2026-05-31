<?php
/**
 * SERP Intelligence Engine
 * 
 * Analyzes SERP features, competitor patterns, content structures
 * Featured snippets, PAA, title patterns, heading analysis
 *
 * @package DODO_AI_SEO
 * @since 2.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_SERP_Intelligence {
    
    /**
     * SERP feature types
     */
    const FEATURE_SNIPPET = 'featured_snippet';
    const FEATURE_PAA = 'people_also_ask';
    const FEATURE_LOCAL = 'local_pack';
    const FEATURE_VIDEO = 'video';
    const FEATURE_IMAGE = 'image_pack';
    const FEATURE_KNOWLEDGE = 'knowledge_panel';
    
    /**
     * Cache TTL
     */
    const SERP_CACHE_TTL = 86400; // 24 hours (SERP changes daily)
    
    /**
     * Analyze SERP for keyword
     *
     * @param string $keyword Target keyword
     * @param array $options Analysis options
     * @return array SERP analysis
     */
    public function analyze_serp($keyword, $options = []) {
        $start_time = microtime(true);
        
        error_log("[DODO SERP] Analyzing SERP for: {$keyword}");
        
        // Check cache
        $cache_key = 'dodo_serp_' . md5($keyword);
        $cached = get_transient($cache_key);
        
        if ($cached !== false && !isset($options['force_refresh'])) {
            error_log('[DODO SERP] Using cached data');
            return $cached;
        }
        
        // Fetch SERP data (mock for now - real implementation would use API)
        $serp_data = $this->fetch_serp_data($keyword);
        
        // Analyze features
        $features = $this->detect_serp_features($serp_data);
        
        // Analyze titles
        $title_patterns = $this->analyze_title_patterns($serp_data);
        
        // Analyze content structure
        $content_patterns = $this->analyze_content_patterns($serp_data);
        
        // Analyze competitors
        $competitor_analysis = $this->analyze_competitors($serp_data);
        
        // Calculate SERP volatility
        $volatility = $this->estimate_volatility($keyword, $serp_data);
        
        // Detect intent
        $intent = $this->detect_intent($keyword, $serp_data);
        
        // Calculate keyword difficulty
        $difficulty = $this->calculate_difficulty($serp_data, $features);
        
        $execution_time = round((microtime(true) - $start_time) * 1000, 2);
        
        $analysis = [
            'keyword' => $keyword,
            'features' => $features,
            'title_patterns' => $title_patterns,
            'content_patterns' => $content_patterns,
            'competitor_analysis' => $competitor_analysis,
            'volatility' => $volatility,
            'intent' => $intent,
            'difficulty' => $difficulty,
            'recommendations' => $this->generate_recommendations($features, $title_patterns, $intent),
            'metadata' => [
                'analyzed_at' => current_time('mysql'),
                'execution_time_ms' => $execution_time,
            ],
        ];
        
        // Cache result
        set_transient($cache_key, $analysis, self::SERP_CACHE_TTL);
        
        error_log(sprintf(
            '[DODO SERP] Analysis complete - Features: %d, Difficulty: %d, Intent: %s',
            count($features),
            $difficulty,
            $intent
        ));
        
        return $analysis;
    }
    
    /**
     * Fetch SERP data
     * 
     * Supports multiple SERP API providers:
     * - SerpAPI (serpapi.com)
     * - DataForSEO (dataforseo.com)
     * - ValueSERP (valueserp.com)
     * - Google Custom Search API
     * 
     * Configure API in WordPress admin: DODO AI SEO → Settings → SERP API
     */
    private function fetch_serp_data($keyword) {
        $api_provider = get_option('dodo_serp_api_provider', 'none');
        $api_key = get_option('dodo_serp_api_key', '');
        
        // If no API configured, return local analysis only
        if ($api_provider === 'none' || empty($api_key)) {
            error_log('[DODO SERP] No API configured - using local analysis');
            return $this->get_local_serp_analysis($keyword);
        }
        
        // Fetch from configured API
        switch ($api_provider) {
            case 'serpapi':
                return $this->fetch_from_serpapi($keyword, $api_key);
            
            case 'dataforseo':
                return $this->fetch_from_dataforseo($keyword, $api_key);
            
            case 'valueserp':
                return $this->fetch_from_valueserp($keyword, $api_key);
            
            default:
                error_log("[DODO SERP] Unknown API provider: {$api_provider}");
                return $this->get_local_serp_analysis($keyword);
        }
    }
    
    /**
     * Fetch from SerpAPI
     */
    private function fetch_from_serpapi($keyword, $api_key) {
        $url = add_query_arg([
            'q' => $keyword,
            'api_key' => $api_key,
            'engine' => 'google',
            'hl' => get_option('dodo_serp_language', 'tr'),
            'gl' => get_option('dodo_serp_country', 'tr'),
        ], 'https://serpapi.com/search');
        
        $response = wp_remote_get($url, ['timeout' => 15]);
        
        if (is_wp_error($response)) {
            error_log('[DODO SERP] SerpAPI error: ' . $response->get_error_message());
            return $this->get_local_serp_analysis($keyword);
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        return $this->normalize_serpapi_response($data);
    }
    
    /**
     * Fetch from DataForSEO
     */
    private function fetch_from_dataforseo($keyword, $api_key) {
        // DataForSEO uses login:password format
        $credentials = explode(':', $api_key);
        
        if (count($credentials) !== 2) {
            error_log('[DODO SERP] Invalid DataForSEO credentials format');
            return $this->get_local_serp_analysis($keyword);
        }
        
        $url = 'https://api.dataforseo.com/v3/serp/google/organic/live/advanced';
        
        $body = json_encode([[
            'keyword' => $keyword,
            'language_code' => get_option('dodo_serp_language', 'tr'),
            'location_code' => get_option('dodo_serp_location', 2792),
        ]]);
        
        $response = wp_remote_post($url, [
            'timeout' => 15,
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($api_key),
                'Content-Type' => 'application/json',
            ],
            'body' => $body,
        ]);
        
        if (is_wp_error($response)) {
            error_log('[DODO SERP] DataForSEO error: ' . $response->get_error_message());
            return $this->get_local_serp_analysis($keyword);
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        return $this->normalize_dataforseo_response($data);
    }
    
    /**
     * Fetch from ValueSERP
     */
    private function fetch_from_valueserp($keyword, $api_key) {
        $url = add_query_arg([
            'q' => $keyword,
            'api_key' => $api_key,
            'location' => get_option('dodo_serp_location', 'Turkey'),
            'google_domain' => get_option('dodo_serp_domain', 'google.com.tr'),
            'hl' => get_option('dodo_serp_language', 'tr'),
        ], 'https://api.valueserp.com/search');
        
        $response = wp_remote_get($url, ['timeout' => 15]);
        
        if (is_wp_error($response)) {
            error_log('[DODO SERP] ValueSERP error: ' . $response->get_error_message());
            return $this->get_local_serp_analysis($keyword);
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        return $this->normalize_valueserp_response($data);
    }
    
    /**
     * Normalize SerpAPI response
     */
    private function normalize_serpapi_response($data) {
        return [
            'organic_results' => $data['organic_results'] ?? [],
            'featured_snippet' => $data['answer_box'] ?? null,
            'people_also_ask' => $data['related_questions'] ?? [],
            'related_searches' => $data['related_searches'] ?? [],
        ];
    }
    
    /**
     * Normalize DataForSEO response
     */
    private function normalize_dataforseo_response($data) {
        $items = $data['tasks'][0]['result'][0]['items'] ?? [];
        
        $organic = [];
        $snippet = null;
        $paa = [];
        
        foreach ($items as $item) {
            if ($item['type'] === 'organic') {
                $organic[] = $item;
            } elseif ($item['type'] === 'featured_snippet') {
                $snippet = $item;
            } elseif ($item['type'] === 'people_also_ask') {
                $paa[] = $item;
            }
        }
        
        return [
            'organic_results' => $organic,
            'featured_snippet' => $snippet,
            'people_also_ask' => $paa,
            'related_searches' => $data['tasks'][0]['result'][0]['related_searches'] ?? [],
        ];
    }
    
    /**
     * Normalize ValueSERP response
     */
    private function normalize_valueserp_response($data) {
        return [
            'organic_results' => $data['organic_results'] ?? [],
            'featured_snippet' => $data['answer_box'] ?? null,
            'people_also_ask' => $data['related_questions'] ?? [],
            'related_searches' => $data['related_searches'] ?? [],
        ];
    }
    
    /**
     * Get local SERP analysis (no API)
     * 
     * Analyzes existing WordPress content and provides
     * pattern-based recommendations
     */
    private function get_local_serp_analysis($keyword) {
        $keyword_lower = mb_strtolower($keyword, 'UTF-8');
        
        // Search existing posts
        $posts = get_posts([
            'post_type' => 'post',
            'post_status' => 'publish',
            's' => $keyword,
            'posts_per_page' => 10,
        ]);
        
        $organic_results = [];
        
        foreach ($posts as $post) {
            $organic_results[] = [
                'title' => $post->post_title,
                'url' => get_permalink($post->ID),
                'description' => wp_trim_words(strip_tags($post->post_content), 30),
                'domain' => parse_url(get_site_url(), PHP_URL_HOST),
                'is_local' => true,
            ];
        }
        
        // Pattern-based snippet detection
        $snippet_opportunity = $this->detect_snippet_opportunity($keyword_lower);
        
        return [
            'organic_results' => $organic_results,
            'featured_snippet' => $snippet_opportunity,
            'people_also_ask' => [],
            'related_searches' => [],
            'is_local_analysis' => true,
        ];
    }
    
    /**
     * Detect snippet opportunity from keyword pattern
     */
    private function detect_snippet_opportunity($keyword_lower) {
        $snippet_patterns = [
            'definition' => '/^(.*)\s+(nedir|ne demek|tanım|what is|definition)/u',
            'how_to' => '/^(nasıl|how to|how do)/u',
            'list' => '/^(en iyi|best|top \d+)/u',
            'comparison' => '/(vs|versus|veya|fark|difference)/u',
        ];
        
        foreach ($snippet_patterns as $type => $pattern) {
            if (preg_match($pattern, $keyword_lower)) {
                return [
                    'exists' => false,
                    'type' => $type,
                    'opportunity' => true,
                    'recommendation' => $this->get_snippet_recommendation($type),
                ];
            }
        }
        
        return [
            'exists' => false,
            'opportunity' => false,
        ];
    }
    
    /**
     * Get snippet optimization recommendation
     */
    private function get_snippet_recommendation($type) {
        $recommendations = [
            'definition' => 'Start with clear definition in first paragraph (40-60 words)',
            'how_to' => 'Use numbered steps with clear action verbs',
            'list' => 'Create ordered/unordered list with 5-10 items',
            'comparison' => 'Use comparison table or side-by-side format',
        ];
        
        return $recommendations[$type] ?? 'Optimize for featured snippet';
    }
    
    /**
     * Detect SERP features
     */
    private function detect_serp_features($serp_data) {
        $features = [];
        
        // Featured snippet
        if (!empty($serp_data['featured_snippet']['exists'])) {
            $features[] = [
                'type' => self::FEATURE_SNIPPET,
                'present' => true,
                'snippet_type' => $serp_data['featured_snippet']['type'],
                'opportunity' => $serp_data['featured_snippet']['opportunity'],
            ];
        }
        
        // People Also Ask
        if (!empty($serp_data['people_also_ask'])) {
            $features[] = [
                'type' => self::FEATURE_PAA,
                'present' => true,
                'question_count' => count($serp_data['people_also_ask']),
                'questions' => $serp_data['people_also_ask'],
            ];
        }
        
        return $features;
    }
    
    /**
     * Analyze title patterns
     */
    private function analyze_title_patterns($serp_data) {
        $titles = array_column($serp_data['organic_results'], 'title');
        
        if (empty($titles)) {
            return [];
        }
        
        $patterns = [
            'avg_length' => 0,
            'common_words' => [],
            'number_usage' => 0,
            'question_format' => 0,
            'year_usage' => 0,
            'bracket_usage' => 0,
        ];
        
        $all_words = [];
        $total_length = 0;
        
        foreach ($titles as $title) {
            $total_length += mb_strlen($title, 'UTF-8');
            
            // Extract words
            preg_match_all('/\p{L}+/u', mb_strtolower($title, 'UTF-8'), $matches);
            $all_words = array_merge($all_words, $matches[0]);
            
            // Number usage
            if (preg_match('/\d+/', $title)) {
                $patterns['number_usage']++;
            }
            
            // Question format
            if (preg_match('/\?$/', $title)) {
                $patterns['question_format']++;
            }
            
            // Year usage
            if (preg_match('/20\d{2}/', $title)) {
                $patterns['year_usage']++;
            }
            
            // Brackets
            if (preg_match('/[\[\(]/', $title)) {
                $patterns['bracket_usage']++;
            }
        }
        
        $patterns['avg_length'] = round($total_length / count($titles));
        
        // Find common words (excluding stop words)
        $word_freq = array_count_values($all_words);
        arsort($word_freq);
        $patterns['common_words'] = array_slice(array_keys($word_freq), 0, 10);
        
        // Convert counts to percentages
        $total = count($titles);
        $patterns['number_usage_pct'] = round(($patterns['number_usage'] / $total) * 100);
        $patterns['question_format_pct'] = round(($patterns['question_format'] / $total) * 100);
        $patterns['year_usage_pct'] = round(($patterns['year_usage'] / $total) * 100);
        $patterns['bracket_usage_pct'] = round(($patterns['bracket_usage'] / $total) * 100);
        
        return $patterns;
    }
    
    /**
     * Analyze content patterns
     */
    private function analyze_content_patterns($serp_data) {
        $descriptions = array_column($serp_data['organic_results'], 'description');
        
        if (empty($descriptions)) {
            return [];
        }
        
        $patterns = [
            'avg_length' => 0,
            'list_format' => 0,
            'data_driven' => 0,
            'how_to_format' => 0,
        ];
        
        $total_length = 0;
        
        foreach ($descriptions as $desc) {
            $total_length += mb_strlen($desc, 'UTF-8');
            
            // List format (bullets, numbers)
            if (preg_match('/[•\-\*]|\d+\./', $desc)) {
                $patterns['list_format']++;
            }
            
            // Data-driven (numbers, percentages, stats)
            if (preg_match('/\d+%|\d+\s*(milyon|bin|yıl)/', $desc)) {
                $patterns['data_driven']++;
            }
            
            // How-to format
            if (preg_match('/(adım|step|nasıl)/ui', $desc)) {
                $patterns['how_to_format']++;
            }
        }
        
        $patterns['avg_length'] = round($total_length / count($descriptions));
        
        $total = count($descriptions);
        $patterns['list_format_pct'] = round(($patterns['list_format'] / $total) * 100);
        $patterns['data_driven_pct'] = round(($patterns['data_driven'] / $total) * 100);
        $patterns['how_to_format_pct'] = round(($patterns['how_to_format'] / $total) * 100);
        
        return $patterns;
    }
    
    /**
     * Analyze competitors
     */
    private function analyze_competitors($serp_data) {
        $domains = [];
        
        foreach ($serp_data['organic_results'] as $result) {
            $domain = parse_url($result['url'], PHP_URL_HOST);
            
            if (!isset($domains[$domain])) {
                $domains[$domain] = 0;
            }
            $domains[$domain]++;
        }
        
        arsort($domains);
        
        return [
            'unique_domains' => count($domains),
            'top_domains' => array_slice($domains, 0, 5, true),
            'domain_diversity' => count($domains) / max(1, count($serp_data['organic_results'])),
        ];
    }
    
    /**
     * Estimate SERP volatility
     */
    private function estimate_volatility($keyword, $serp_data) {
        // Check historical data if available
        $history_key = 'dodo_serp_history_' . md5($keyword);
        $history = get_option($history_key, []);
        
        if (empty($history)) {
            // First analysis - assume medium volatility
            return [
                'score' => 50,
                'level' => 'medium',
                'reason' => 'No historical data',
            ];
        }
        
        // Compare with last analysis
        $last = end($history);
        $current_domains = array_column($serp_data['organic_results'], 'domain');
        $last_domains = $last['domains'] ?? [];
        
        $changes = count(array_diff($current_domains, $last_domains));
        $volatility_score = ($changes / max(1, count($current_domains))) * 100;
        
        // Update history
        $history[] = [
            'date' => current_time('mysql'),
            'domains' => $current_domains,
        ];
        
        // Keep last 30 days
        if (count($history) > 30) {
            $history = array_slice($history, -30);
        }
        
        update_option($history_key, $history);
        
        $level = 'low';
        if ($volatility_score > 60) {
            $level = 'high';
        } elseif ($volatility_score > 30) {
            $level = 'medium';
        }
        
        return [
            'score' => round($volatility_score),
            'level' => $level,
            'changes' => $changes,
        ];
    }
    
    /**
     * Detect search intent
     */
    private function detect_intent($keyword, $serp_data) {
        $keyword_lower = mb_strtolower($keyword, 'UTF-8');
        
        $intent_signals = [
            'informational' => 0,
            'transactional' => 0,
            'commercial' => 0,
            'navigational' => 0,
        ];
        
        // Keyword patterns
        if (preg_match('/(nedir|ne demek|nasıl|neden|what|how|why)/u', $keyword_lower)) {
            $intent_signals['informational'] += 40;
        }
        
        if (preg_match('/(satın al|fiyat|buy|price|ucuz|indirim)/u', $keyword_lower)) {
            $intent_signals['transactional'] += 40;
        }
        
        if (preg_match('/(en iyi|best|vs|review|karşılaştır)/u', $keyword_lower)) {
            $intent_signals['commercial'] += 40;
        }
        
        // SERP features
        if (!empty($serp_data['featured_snippet']['exists'])) {
            $intent_signals['informational'] += 20;
        }
        
        if (!empty($serp_data['people_also_ask'])) {
            $intent_signals['informational'] += 15;
        }
        
        // Title patterns
        $titles = array_column($serp_data['organic_results'], 'title');
        $titles_text = implode(' ', $titles);
        
        if (stripos($titles_text, 'fiyat') !== false || stripos($titles_text, 'satın') !== false) {
            $intent_signals['transactional'] += 20;
        }
        
        if (stripos($titles_text, 'en iyi') !== false || stripos($titles_text, 'best') !== false) {
            $intent_signals['commercial'] += 20;
        }
        
        arsort($intent_signals);
        $primary_intent = key($intent_signals);
        
        return [
            'primary' => $primary_intent,
            'confidence' => $intent_signals[$primary_intent],
            'signals' => $intent_signals,
        ];
    }
    
    /**
     * Calculate keyword difficulty
     */
    private function calculate_difficulty($serp_data, $features) {
        $difficulty = 0;
        
        // Domain diversity (less diversity = harder)
        $unique_domains = count(array_unique(array_column($serp_data['organic_results'], 'domain')));
        if ($unique_domains <= 3) {
            $difficulty += 30;
        } elseif ($unique_domains <= 5) {
            $difficulty += 20;
        } else {
            $difficulty += 10;
        }
        
        // SERP features (more features = harder)
        $difficulty += count($features) * 10;
        
        // Featured snippet (harder to compete)
        foreach ($features as $feature) {
            if ($feature['type'] === self::FEATURE_SNIPPET) {
                $difficulty += 20;
            }
        }
        
        return min(100, $difficulty);
    }
    
    /**
     * Generate recommendations
     */
    private function generate_recommendations($features, $title_patterns, $intent) {
        $recommendations = [];
        
        // Title recommendations
        if ($title_patterns['number_usage_pct'] > 50) {
            $recommendations[] = [
                'type' => 'title',
                'action' => 'Include numbers in title',
                'reason' => "{$title_patterns['number_usage_pct']}% of top results use numbers",
            ];
        }
        
        if ($title_patterns['year_usage_pct'] > 30) {
            $recommendations[] = [
                'type' => 'title',
                'action' => 'Add current year to title',
                'reason' => 'Freshness signal important for this query',
            ];
        }
        
        // Content recommendations based on intent
        if ($intent['primary'] === 'informational') {
            $recommendations[] = [
                'type' => 'content',
                'action' => 'Create comprehensive guide',
                'reason' => 'Informational intent detected',
            ];
        } elseif ($intent['primary'] === 'commercial') {
            $recommendations[] = [
                'type' => 'content',
                'action' => 'Include comparison and pros/cons',
                'reason' => 'Commercial investigation intent',
            ];
        }
        
        // Feature-based recommendations
        foreach ($features as $feature) {
            if ($feature['type'] === self::FEATURE_SNIPPET && $feature['opportunity']) {
                $recommendations[] = [
                    'type' => 'snippet',
                    'action' => 'Optimize for featured snippet',
                    'reason' => "Snippet opportunity: {$feature['snippet_type']}",
                ];
            }
            
            if ($feature['type'] === self::FEATURE_PAA) {
                $recommendations[] = [
                    'type' => 'faq',
                    'action' => 'Add FAQ section',
                    'reason' => 'PAA box present - include these questions',
                ];
            }
        }
        
        return $recommendations;
    }
}
