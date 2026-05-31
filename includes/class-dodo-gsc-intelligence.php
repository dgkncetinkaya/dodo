<?php
/**
 * GSC Intelligence Engine
 * 
 * Real Google Search Console data analysis for keyword opportunities
 * Sprint B - Real GSC Intelligence Engine
 * 
 * @package DODO_AI_SEO
 * @since 3.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_GSC_Intelligence {
    
    private $gsc;
    private $cache_ttl = 21600; // 6 hours
    
    public function __construct() {
        $this->gsc = new DODO_GSC_Connector();
    }
    
    /**
     * Fetch queries from GSC with caching
     * HARDENED: Phase 2 - Low traffic + partial data handling
     * 
     * @param int $days Number of days to fetch
     * @return array|WP_Error
     */
    public function fetch_queries($days = 28) {
        // Check cache
        $cache_key = 'gsc_queries_' . $days . 'd';
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            error_log('[DODO][GSC Intelligence] Cache HIT: ' . $cache_key);
            return $cached;
        }
        
        error_log('[DODO][GSC Intelligence] Cache MISS: ' . $cache_key . ', fetching from API');
        
        // Get site URL
        $site_url = $this->get_site_url();
        if (is_wp_error($site_url)) {
            return $site_url;
        }
        
        // Fetch from GSC
        $end_date = date('Y-m-d');
        $start_date = date('Y-m-d', strtotime("-{$days} days"));
        
        $rows = $this->gsc->query_search_analytics($site_url, $start_date, $end_date, ['query'], 1000);
        
        // Handle empty data (normal for new/low traffic sites)
        if (empty($rows)) {
            error_log('[DODO][GSC Intelligence] No query data - site may be new or have low traffic');
            
            // Cache empty result for shorter time to retry sooner
            set_transient($cache_key, [], 3600); // 1 hour instead of 6
            
            return new WP_Error('gsc_no_data', __('GSC\'de henüz yeterli veri yok. Site yeni ise veya trafik düşükse bu normaldir.', 'dodo-ai-seo'));
        }
        
        // Filter out spam/invalid queries
        $filtered_rows = $this->filter_spam_queries($rows);
        
        // Log data quality
        $filtered_count = count($rows) - count($filtered_rows);
        if ($filtered_count > 0) {
            error_log('[DODO][GSC Intelligence] Filtered ' . $filtered_count . ' spam/invalid queries');
        }
        
        // Cache it
        set_transient($cache_key, $filtered_rows, $this->cache_ttl);
        
        error_log('[DODO][GSC Intelligence] Fetched ' . count($filtered_rows) . ' valid queries');
        
        return $filtered_rows;
    }
    
    /**
     * Filter spam and invalid queries
     * HARDENED: Phase 2 - Query quality filter
     * 
     * @param array $rows GSC rows
     * @return array Filtered rows
     */
    private function filter_spam_queries($rows) {
        $filtered = [];
        
        foreach ($rows as $row) {
            $query = $row['keys'][0] ?? '';
            
            // Skip empty queries
            if (empty($query)) {
                continue;
            }
            
            // Skip very short queries (likely spam)
            if (strlen($query) < 3) {
                continue;
            }
            
            // Skip queries with excessive special characters
            $special_char_count = preg_match_all('/[^a-zA-Z0-9\s\-_]/', $query);
            if ($special_char_count > strlen($query) / 2) {
                continue;
            }
            
            // Skip queries that are just numbers
            if (is_numeric($query)) {
                continue;
            }
            
            // Skip queries with very low impressions (noise)
            $impressions = $row['impressions'] ?? 0;
            if ($impressions < 2) {
                continue;
            }
            
            $filtered[] = $row;
        }
        
        return $filtered;
    }
    
    /**
     * Fetch pages from GSC with caching
     * 
     * @param int $days Number of days to fetch
     * @return array|WP_Error
     */
    public function fetch_pages($days = 28) {
        // Check cache
        $cache_key = 'gsc_pages_' . $days . 'd';
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            error_log('[DODO][GSC Intelligence] Cache HIT: ' . $cache_key);
            return $cached;
        }
        
        error_log('[DODO][GSC Intelligence] Cache MISS: ' . $cache_key . ', fetching from API');
        
        // Get site URL
        $site_url = $this->get_site_url();
        if (is_wp_error($site_url)) {
            return $site_url;
        }
        
        // Fetch from GSC
        $end_date = date('Y-m-d');
        $start_date = date('Y-m-d', strtotime("-{$days} days"));
        
        $rows = $this->gsc->query_search_analytics($site_url, $start_date, $end_date, ['page'], 1000);
        
        if (empty($rows)) {
            return new WP_Error('gsc_no_data', __('GSC\'de henüz yeterli veri yok.', 'dodo-ai-seo'));
        }
        
        // Cache it
        set_transient($cache_key, $rows, $this->cache_ttl);
        
        error_log('[DODO][GSC Intelligence] Fetched ' . count($rows) . ' pages');
        
        return $rows;
    }
    
    /**
     * Fetch page queries (queries for a specific page)
     * 
     * @param string $page_url Page URL
     * @param int $days Number of days
     * @return array|WP_Error
     */
    public function fetch_page_queries($page_url, $days = 28) {
        $cache_key = 'gsc_page_queries_' . md5($page_url);
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        $site_url = $this->get_site_url();
        if (is_wp_error($site_url)) {
            return $site_url;
        }
        
        $end_date = date('Y-m-d');
        $start_date = date('Y-m-d', strtotime("-{$days} days"));
        
        $rows = $this->gsc->query_search_analytics($site_url, $start_date, $end_date, ['query', 'page'], 1000);
        
        // Filter by page
        $page_queries = array_filter($rows, function($row) use ($page_url) {
            return isset($row['keys'][1]) && $row['keys'][1] === $page_url;
        });
        
        set_transient($cache_key, $page_queries, $this->cache_ttl);
        
        return $page_queries;
    }
    
    /**
     * Fetch query pages (pages for a specific query)
     * 
     * @param string $query Query
     * @param int $days Number of days
     * @return array|WP_Error
     */
    public function fetch_query_pages($query, $days = 28) {
        $cache_key = 'gsc_query_pages_' . md5($query);
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        $site_url = $this->get_site_url();
        if (is_wp_error($site_url)) {
            return $site_url;
        }
        
        $end_date = date('Y-m-d');
        $start_date = date('Y-m-d', strtotime("-{$days} days"));
        
        $rows = $this->gsc->query_search_analytics($site_url, $start_date, $end_date, ['query', 'page'], 1000);
        
        // Filter by query
        $query_pages = array_filter($rows, function($row) use ($query) {
            return isset($row['keys'][0]) && $row['keys'][0] === $query;
        });
        
        set_transient($cache_key, $query_pages, $this->cache_ttl);
        
        return $query_pages;
    }
    
    /**
     * Get available properties
     * 
     * @return array
     */
    public function get_available_properties() {
        return $this->gsc->get_properties();
    }
    
    /**
     * Detect best property (auto-detect site URL)
     * 
     * @return string|WP_Error
     */
    public function detect_best_property() {
        $properties = $this->get_available_properties();
        
        if (empty($properties)) {
            return new WP_Error('gsc_no_properties', __('GSC\'de hiç property bulunamadı.', 'dodo-ai-seo'));
        }
        
        $site_url = get_site_url();
        
        // Try exact match
        foreach ($properties as $property) {
            if ($property['siteUrl'] === $site_url || $property['siteUrl'] === $site_url . '/') {
                return $property['siteUrl'];
            }
        }
        
        // Try without trailing slash
        $site_url_no_slash = rtrim($site_url, '/');
        foreach ($properties as $property) {
            if (rtrim($property['siteUrl'], '/') === $site_url_no_slash) {
                return $property['siteUrl'];
            }
        }
        
        // Try domain property
        $domain = parse_url($site_url, PHP_URL_HOST);
        foreach ($properties as $property) {
            if (strpos($property['siteUrl'], 'sc-domain:') === 0) {
                $property_domain = str_replace('sc-domain:', '', $property['siteUrl']);
                if ($property_domain === $domain) {
                    return $property['siteUrl'];
                }
            }
        }
        
        // Return first property as fallback
        return $properties[0]['siteUrl'];
    }
    
    /**
     * Get site URL (cached)
     * 
     * @return string|WP_Error
     */
    private function get_site_url() {
        $cached = get_transient('dodo_gsc_site_url');
        
        if ($cached !== false) {
            return $cached;
        }
        
        $site_url = $this->detect_best_property();
        
        if (is_wp_error($site_url)) {
            return $site_url;
        }
        
        set_transient('dodo_gsc_site_url', $site_url, DAY_IN_SECONDS);
        
        return $site_url;
    }
    
    /**
     * Check if GSC is connected and has data
     * HARDENED: Phase 2 - Comprehensive validation
     * 
     * @return bool
     */
    public function is_available() {
        if (!$this->gsc->is_connected()) {
            error_log('[DODO][GSC Intelligence] Not available: Not connected');
            return false;
        }
        
        // Try to fetch recent data
        $queries = $this->fetch_queries(7);
        
        if (is_wp_error($queries)) {
            error_log('[DODO][GSC Intelligence] Not available: ' . $queries->get_error_message());
            return false;
        }
        
        if (empty($queries)) {
            error_log('[DODO][GSC Intelligence] Not available: No data');
            return false;
        }
        
        error_log('[DODO][GSC Intelligence] Available: ' . count($queries) . ' queries found');
        return true;
    }
    
    /**
     * Get GSC data health status
     * HARDENED: Phase 2 - Data quality validation
     * 
     * @return array Health status
     */
    public function get_data_health() {
        $health = array(
            'connected' => false,
            'has_data' => false,
            'data_quality' => 'unknown',
            'total_queries' => 0,
            'total_impressions' => 0,
            'avg_position' => 0,
            'issues' => array(),
            'recommendations' => array(),
        );
        
        // Check connection
        if (!$this->gsc->is_connected()) {
            $health['issues'][] = 'GSC bağlantısı yok';
            $health['recommendations'][] = 'Ayarlar sayfasından GSC bağlantısı kurun';
            return $health;
        }
        
        $health['connected'] = true;
        
        // Fetch data
        $queries = $this->fetch_queries(28);
        
        if (is_wp_error($queries)) {
            $health['issues'][] = $queries->get_error_message();
            $health['recommendations'][] = 'Site yeniyse 2-3 hafta bekleyin';
            return $health;
        }
        
        if (empty($queries)) {
            $health['issues'][] = 'GSC\'de veri yok';
            $health['recommendations'][] = 'Site yeni ise normal, 2-3 hafta bekleyin';
            return $health;
        }
        
        $health['has_data'] = true;
        $health['total_queries'] = count($queries);
        
        // Calculate metrics
        $total_impressions = 0;
        $total_position = 0;
        
        foreach ($queries as $row) {
            $total_impressions += $row['impressions'] ?? 0;
            $total_position += $row['position'] ?? 0;
        }
        
        $health['total_impressions'] = $total_impressions;
        $health['avg_position'] = count($queries) > 0 ? round($total_position / count($queries), 1) : 0;
        
        // Assess data quality
        if ($total_impressions < 100) {
            $health['data_quality'] = 'very_low';
            $health['issues'][] = 'Çok düşük trafik (< 100 impression)';
            $health['recommendations'][] = 'SEO çalışmalarına devam edin, veri biriktikçe analiz gelişecek';
        } elseif ($total_impressions < 1000) {
            $health['data_quality'] = 'low';
            $health['recommendations'][] = 'Trafik artıyor, daha fazla veri için içerik üretmeye devam edin';
        } elseif ($total_impressions < 10000) {
            $health['data_quality'] = 'medium';
            $health['recommendations'][] = 'İyi seviyede veri var, opportunity analizleri güvenilir';
        } else {
            $health['data_quality'] = 'high';
            $health['recommendations'][] = 'Mükemmel veri kalitesi, tüm analizler güvenilir';
        }
        
        return $health;
    }
    
    /**
     * Fetch GSC data with fallback
     * HARDENED: Phase 2 - Graceful degradation
     * 
     * @param int $days Number of days
     * @return array Always returns array, never WP_Error
     */
    public function fetch_gsc_data($days = 28) {
        $queries = $this->fetch_queries($days);
        
        if (is_wp_error($queries)) {
            error_log('[DODO][GSC Intelligence] fetch_gsc_data: Returning empty array due to error');
            return array();
        }
        
        return $queries;
    }
    
    /**
     * Clear all caches
     */
    public function clear_cache() {
        global $wpdb;
        
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_gsc_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_gsc_%'");
        
        error_log('[DODO][GSC Intelligence] Cache cleared');
    }
    
    /**
     * CTR Opportunity Engine
     * Find high impression + low CTR opportunities
     * 
     * @param int $days Number of days
     * @return array
     */
    public function get_ctr_opportunities($days = 28) {
        $site_url = $this->get_site_url();
        if (is_wp_error($site_url)) {
            return [];
        }
        
        $opportunities = $this->gsc->get_low_ctr_opportunities($site_url, $days);
        
        // Add source and recommendations
        foreach ($opportunities as &$opp) {
            $opp['source'] = 'gsc_ctr';
            $opp['recommended_action'] = $this->get_ctr_recommendation($opp);
            $opp['effort'] = 'low';
            $opp['estimated_impact'] = $this->estimate_ctr_impact($opp);
        }
        
        return $opportunities;
    }
    
    /**
     * Ranking Opportunity Engine
     * Find queries ranking 4-20 (close to first page)
     * 
     * @param int $days Number of days
     * @return array
     */
    public function get_ranking_opportunities($days = 28) {
        $queries = $this->fetch_queries($days);
        
        if (is_wp_error($queries)) {
            return [];
        }
        
        $opportunities = [];
        
        foreach ($queries as $row) {
            $position = $row['position'] ?? 0;
            $impressions = $row['impressions'] ?? 0;
            $query = $row['keys'][0] ?? '';
            
            // Position 4-20 with meaningful impressions
            if ($position >= 4 && $position <= 20 && $impressions >= 50) {
                $opportunities[] = [
                    'query' => $query,
                    'current_position' => round($position, 1),
                    'target_position' => min(3, floor($position / 2)),
                    'impressions' => $impressions,
                    'clicks' => $row['clicks'] ?? 0,
                    'ctr' => round(($row['ctr'] ?? 0) * 100, 2),
                    'source' => 'gsc_ranking',
                    'effort_score' => $this->calculate_ranking_effort($position),
                    'impact_score' => $this->calculate_ranking_impact($position, $impressions),
                    'recommendation' => $this->get_ranking_recommendation($position),
                    'opportunity_score' => $this->calculate_ranking_opportunity_score($position, $impressions),
                ];
            }
        }
        
        // Sort by opportunity score
        usort($opportunities, function($a, $b) {
            return $b['opportunity_score'] <=> $a['opportunity_score'];
        });
        
        return array_slice($opportunities, 0, 50);
    }
    
    /**
     * Content Decay Engine
     * Compare last 28 days with previous 28 days
     * 
     * @return array
     */
    public function get_content_decay_opportunities() {
        error_log('[DODO][GSC Intelligence] Decay engine: START');
        $start_time = microtime(true);
        $max_execution_time = 20; // 20 seconds max
        $max_pages = 50; // Limit pages to analyze
        
        $pages = $this->fetch_pages(56); // 56 days for comparison
        
        if (is_wp_error($pages)) {
            error_log('[DODO][GSC Intelligence] Decay engine: fetch_pages returned error');
            return [];
        }
        
        $site_url = $this->get_site_url();
        if (is_wp_error($site_url)) {
            error_log('[DODO][GSC Intelligence] Decay engine: site_url error');
            return [];
        }
        
        // Limit pages
        $pages = array_slice($pages, 0, $max_pages);
        error_log('[DODO][GSC Intelligence] Decay engine: Analyzing ' . count($pages) . ' pages');
        
        $opportunities = [];
        $analyzed_count = 0;
        
        foreach ($pages as $row) {
            // Timeout check
            if ((microtime(true) - $start_time) > $max_execution_time) {
                error_log('[DODO][GSC Intelligence] Decay engine: TIMEOUT after ' . $analyzed_count . ' pages');
                break;
            }
            
            $page = $row['keys'][0] ?? '';
            
            if (empty($page)) {
                continue;
            }
            
            try {
                $decay = $this->gsc->detect_content_decay($site_url, $page, 56);
                $analyzed_count++;
                
                if ($decay && $decay['is_decaying']) {
                    $opportunities[] = [
                        'page' => $page,
                        'query' => basename($page), // Use page as query for display
                        'previous_impressions' => $decay['first_week_impressions'],
                        'current_impressions' => $decay['last_week_impressions'],
                        'impression_change_pct' => $decay['impression_change_pct'],
                        'position_change' => $decay['position_change'],
                        'decay_score' => abs($decay['impression_change_pct']) + abs($decay['position_change'] * 5),
                        'source' => 'gsc_decay',
                        'likely_reason' => $this->get_decay_reason($decay),
                        'recommended_action' => $this->get_decay_recommendation($decay),
                        'effort' => 'medium',
                    ];
                }
            } catch (Exception $e) {
                error_log('[DODO][GSC Intelligence] Decay engine: Error analyzing page ' . $page . ': ' . $e->getMessage());
                continue;
            }
        }
        
        // Sort by decay score
        usort($opportunities, function($a, $b) {
            return $b['decay_score'] <=> $a['decay_score'];
        });
        
        $result = array_slice($opportunities, 0, 20);
        $elapsed = microtime(true) - $start_time;
        
        error_log('[DODO][GSC Intelligence] Decay engine: END - Found ' . count($result) . ' opportunities in ' . round($elapsed, 2) . 's');
        
        return $result;
    }
    
    /**
     * Cannibalization Engine
     * Find queries with multiple competing pages
     * 
     * @param int $days Number of days
     * @return array
     */
    public function get_cannibalization_opportunities($days = 28) {
        error_log('[DODO][GSC Intelligence] Cannibalization engine: START');
        $start_time = microtime(true);
        
        $site_url = $this->get_site_url();
        if (is_wp_error($site_url)) {
            error_log('[DODO][GSC Intelligence] Cannibalization engine: site_url error');
            return [];
        }
        
        try {
            $cannibalization = $this->gsc->detect_cannibalization($site_url, $days);
            
            if (empty($cannibalization)) {
                error_log('[DODO][GSC Intelligence] Cannibalization engine: No cannibalization found');
                return [];
            }
            
            // Add recommendations
            foreach ($cannibalization as &$item) {
                $item['source'] = 'gsc_cannibalization';
                $item['cannibalization_score'] = $this->calculate_cannibalization_score($item);
                $item['action'] = $this->get_cannibalization_action($item);
                $item['effort'] = 'high';
                
                // Add query field if missing
                if (!isset($item['query']) && isset($item['keyword'])) {
                    $item['query'] = $item['keyword'];
                }
            }
            
            $elapsed = microtime(true) - $start_time;
            error_log('[DODO][GSC Intelligence] Cannibalization engine: END - Found ' . count($cannibalization) . ' opportunities in ' . round($elapsed, 2) . 's');
            
            return $cannibalization;
            
        } catch (Exception $e) {
            error_log('[DODO][GSC Intelligence] Cannibalization engine: ERROR - ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Query Gap Engine
     * Find queries with impressions but weak content match
     * 
     * @param int $days Number of days
     * @return array
     */
    public function get_query_gap_opportunities($days = 28) {
        error_log('[DODO][GSC Intelligence] Query Gap engine: START');
        $start_time = microtime(true);
        $max_execution_time = 20; // 20 seconds max
        $max_queries = 200; // Limit queries to analyze
        
        $end_date = date('Y-m-d');
        $start_date = date('Y-m-d', strtotime("-{$days} days"));
        
        $site_url = $this->get_site_url();
        if (is_wp_error($site_url)) {
            error_log('[DODO][GSC Intelligence] Query Gap engine: site_url error');
            return [];
        }
        
        try {
            $rows = $this->gsc->query_search_analytics($site_url, $start_date, $end_date, ['query', 'page'], 1000);
            
            if (empty($rows)) {
                error_log('[DODO][GSC Intelligence] Query Gap engine: No data from GSC');
                return [];
            }
            
            // Limit rows
            $rows = array_slice($rows, 0, $max_queries);
            error_log('[DODO][GSC Intelligence] Query Gap engine: Analyzing ' . count($rows) . ' queries');
            
            $opportunities = [];
            $analyzed_count = 0;
            
            foreach ($rows as $row) {
                // Timeout check
                if ((microtime(true) - $start_time) > $max_execution_time) {
                    error_log('[DODO][GSC Intelligence] Query Gap engine: TIMEOUT after ' . $analyzed_count . ' queries');
                    break;
                }
                
                $query = $row['keys'][0] ?? '';
                $page = $row['keys'][1] ?? '';
                $impressions = $row['impressions'] ?? 0;
                $position = $row['position'] ?? 0;
                
                if ($impressions < 100 || $position < 10) {
                    continue;
                }
                
                // Check content match
                $match_score = $this->calculate_content_match($query, $page);
                $analyzed_count++;
                
                if ($match_score < 0.5) {
                    $opportunities[] = [
                        'query' => $query,
                        'impressions' => $impressions,
                        'current_best_page' => $page,
                        'current_position' => round($position, 1),
                        'content_match_score' => round($match_score, 2),
                        'gap_score' => (1 - $match_score) * ($impressions / 10),
                        'source' => 'gsc_gap',
                        'recommended_content_type' => $this->detect_content_type($query),
                        'effort' => 'medium',
                    ];
                }
            }
            
            // Sort by gap score
            usort($opportunities, function($a, $b) {
                return $b['gap_score'] <=> $a['gap_score'];
            });
            
            $result = array_slice($opportunities, 0, 30);
            $elapsed = microtime(true) - $start_time;
            
            error_log('[DODO][GSC Intelligence] Query Gap engine: END - Found ' . count($result) . ' opportunities in ' . round($elapsed, 2) . 's');
            
            return $result;
            
        } catch (Exception $e) {
            error_log('[DODO][GSC Intelligence] Query Gap engine: ERROR - ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Helper: Get CTR recommendation
     */
    private function get_ctr_recommendation($opp) {
        $ctr = $opp['ctr'];
        $position = $opp['position'];
        
        if ($position <= 3 && $ctr < 5) {
            return 'Title ve meta description yeniden yazılmalı - üst sıralarda CTR çok düşük';
        } elseif ($position <= 10 && $ctr < 2) {
            return 'Meta description optimize edilmeli, FAQ bloğu eklenebilir';
        } else {
            return 'Featured snippet için yapılandırılmış içerik eklenebilir';
        }
    }
    
    /**
     * Helper: Estimate CTR impact
     */
    private function estimate_ctr_impact($opp) {
        $impressions = $opp['impressions'];
        $current_ctr = $opp['ctr'] / 100;
        $target_ctr = min($current_ctr * 2, 0.15); // 2x or max 15%
        
        $additional_clicks = $impressions * ($target_ctr - $current_ctr);
        
        if ($additional_clicks > 100) {
            return 'high';
        } elseif ($additional_clicks > 50) {
            return 'medium';
        } else {
            return 'low';
        }
    }
    
    /**
     * Helper: Calculate ranking effort
     */
    private function calculate_ranking_effort($position) {
        if ($position <= 10) {
            return 60; // Easier
        } elseif ($position <= 15) {
            return 75;
        } else {
            return 90; // Harder
        }
    }
    
    /**
     * Helper: Calculate ranking impact
     */
    private function calculate_ranking_impact($position, $impressions) {
        $position_gain = 20 - $position;
        return min(100, ($position_gain * 5) + ($impressions / 10));
    }
    
    /**
     * Helper: Get ranking recommendation
     */
    private function get_ranking_recommendation($position) {
        if ($position <= 10) {
            return 'İçerik derinleştirilmeli, internal linkler güçlendirilmeli';
        } elseif ($position <= 15) {
            return 'Semantic keyword coverage artırılmalı, E-A-T sinyalleri güçlendirilmeli';
        } else {
            return 'Yeni içerik oluşturulmalı veya mevcut içerik tamamen yeniden yazılmalı';
        }
    }
    
    /**
     * Helper: Calculate ranking opportunity score
     */
    private function calculate_ranking_opportunity_score($position, $impressions) {
        $position_score = max(0, 100 - ($position * 5));
        $impression_score = min(100, $impressions / 5);
        
        return ($position_score * 0.6) + ($impression_score * 0.4);
    }
    
    /**
     * Helper: Get decay reason
     */
    private function get_decay_reason($decay) {
        if ($decay['position_change'] > 5) {
            return 'Sıralama düşüşü - rakipler güçlenmiş olabilir';
        } elseif ($decay['impression_change_pct'] < -30) {
            return 'Arama hacmi düşmüş veya içerik güncelliğini kaybetmiş';
        } else {
            return 'CTR düşüşü - title/description çekiciliğini kaybetmiş';
        }
    }
    
    /**
     * Helper: Get decay recommendation
     */
    private function get_decay_recommendation($decay) {
        if ($decay['position_change'] > 5) {
            return 'İçerik güncellenmeli, yeni bilgiler eklenmeli, rakip analizi yapılmalı';
        } else {
            return 'Title ve meta description yenilenmeli, içerik tazeliği artırılmalı';
        }
    }
    
    /**
     * Helper: Calculate cannibalization score
     */
    private function calculate_cannibalization_score($item) {
        $competing_pages = $item['competing_pages'];
        $total_impressions = $item['total_impressions'];
        
        return min(100, ($competing_pages * 20) + ($total_impressions / 20));
    }
    
    /**
     * Helper: Get cannibalization action
     */
    private function get_cannibalization_action($item) {
        $pages = $item['pages'];
        
        if (count($pages) == 2) {
            return 'merge'; // Merge two pages
        } elseif (count($pages) >= 3) {
            return 'canonical'; // Set canonical
        } else {
            return 'differentiate'; // Differentiate intent
        }
    }
    
    /**
     * Helper: Calculate content match score
     */
    private function calculate_content_match($query, $page_url) {
        // Get page content
        $post_id = url_to_postid($page_url);
        
        if (!$post_id) {
            return 0;
        }
        
        $post = get_post($post_id);
        
        if (!$post) {
            return 0;
        }
        
        $title = strtolower($post->post_title);
        $content = strtolower($post->post_content);
        $query_lower = strtolower($query);
        
        $score = 0;
        
        // Title match
        if (strpos($title, $query_lower) !== false) {
            $score += 0.5;
        }
        
        // Content match
        $query_words = explode(' ', $query_lower);
        $matches = 0;
        
        foreach ($query_words as $word) {
            if (strlen($word) > 3 && strpos($content, $word) !== false) {
                $matches++;
            }
        }
        
        if (count($query_words) > 0) {
            $score += ($matches / count($query_words)) * 0.5;
        }
        
        return min(1, $score);
    }
    
    /**
     * Helper: Detect content type from query
     */
    private function detect_content_type($query) {
        $query_lower = strtolower($query);
        
        if (strpos($query_lower, 'nasıl') !== false || strpos($query_lower, 'how') !== false) {
            return 'how-to guide';
        } elseif (strpos($query_lower, 'nedir') !== false || strpos($query_lower, 'what is') !== false) {
            return 'definition article';
        } elseif (strpos($query_lower, 'en iyi') !== false || strpos($query_lower, 'best') !== false) {
            return 'listicle / comparison';
        } elseif (strpos($query_lower, 'fiyat') !== false || strpos($query_lower, 'price') !== false) {
            return 'product/pricing page';
        } else {
            return 'informational article';
        }
    }
}
