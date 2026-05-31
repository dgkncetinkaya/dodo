<?php
/**
 * Google Search Console Integration
 * 
 * OPTIONAL FEATURE - Requires google/apiclient library
 * 
 * Installation:
 * 1. Run: composer require google/apiclient:^2.0
 * 2. Configure OAuth credentials in WordPress admin
 * 3. Connect your GSC property
 * 
 * If not installed, DODO AI SEO will work without GSC features.
 * GSC provides real SERP data for opportunity detection.
 * 
 * OAuth connection, query intelligence, opportunity detection
 * Real SERP data for AI-driven SEO decisions
 *
 * @package DODO_AI_SEO
 * @since 2.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Search_Console {
    
    /**
     * Opportunity thresholds
     */
    const HIGH_IMPRESSION_THRESHOLD = 1000;
    const LOW_CTR_THRESHOLD = 0.02; // 2%
    const DECAY_THRESHOLD_DAYS = 30;
    const RANKING_OPPORTUNITY_MIN = 6;
    const RANKING_OPPORTUNITY_MAX = 15;
    
    /**
     * Cache TTL
     */
    const QUERY_CACHE_TTL = 43200; // 12 hours
    const PERFORMANCE_CACHE_TTL = 21600; // 6 hours
    
    /**
     * API client
     */
    private $client = null;
    private $service = null;
    
    /**
     * Site URL
     */
    private $site_url;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->site_url = get_site_url();
    }
    
    /**
     * Initialize Google API client
     * 
     * Checks for google/apiclient library and credentials
     *
     * @return bool Success status
     */
    public function init_client() {
        // Check if Google API client library is installed
        if (!class_exists('Google_Client')) {
            error_log('[DODO GSC] Google API client not installed. Run: composer require google/apiclient:^2.0');
            
            // Store installation status for admin notice
            update_option('dodo_gsc_library_missing', true);
            
            return false;
        }
        
        // Clear library missing flag
        delete_option('dodo_gsc_library_missing');
        
        // Check if credentials exist
        $credentials = get_option('dodo_gsc_credentials');
        
        if (!$credentials) {
            error_log('[DODO GSC] No credentials configured. Configure in WordPress admin.');
            return false;
        }
        
        try {
            $this->client = new Google_Client();
            $this->client->setApplicationName('DODO AI SEO');
            $this->client->setScopes(['https://www.googleapis.com/auth/webmasters.readonly']);
            $this->client->setAuthConfig($credentials);
            $this->client->setAccessType('offline');
            
            // Check for stored access token
            $token = get_option('dodo_gsc_access_token');
            
            if ($token) {
                $this->client->setAccessToken($token);
                
                // Refresh if expired
                if ($this->client->isAccessTokenExpired()) {
                    $this->refresh_token();
                }
            }
            
            $this->service = new Google_Service_SearchConsole($this->client);
            
            error_log('[DODO GSC] Client initialized successfully');
            return true;
            
        } catch (Exception $e) {
            error_log('[DODO GSC] Init error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if GSC is available
     * 
     * @return array Status with details
     */
    public function is_available() {
        $library_installed = class_exists('Google_Client');
        $credentials_exist = !empty(get_option('dodo_gsc_credentials'));
        $token_exists = !empty(get_option('dodo_gsc_access_token'));
        
        return [
            'available' => $library_installed && $credentials_exist && $token_exists,
            'library_installed' => $library_installed,
            'credentials_configured' => $credentials_exist,
            'connected' => $token_exists,
            'message' => $this->get_availability_message($library_installed, $credentials_exist, $token_exists),
        ];
    }
    
    /**
     * Get availability message
     */
    private function get_availability_message($library, $credentials, $token) {
        if (!$library) {
            return 'Google API client not installed. Run: composer require google/apiclient:^2.0';
        }
        
        if (!$credentials) {
            return 'OAuth credentials not configured. Add credentials in Settings.';
        }
        
        if (!$token) {
            return 'Not connected to Google Search Console. Click Connect button.';
        }
        
        return 'Google Search Console connected and ready.';
    }
    
    /**
     * Get authorization URL
     *
     * @return string Auth URL
     */
    public function get_auth_url() {
        if (!$this->client) {
            $this->init_client();
        }
        
        return $this->client ? $this->client->createAuthUrl() : '';
    }
    
    /**
     * Handle OAuth callback
     *
     * @param string $code Authorization code
     * @return bool Success status
     */
    public function handle_oauth_callback($code) {
        try {
            if (!$this->client) {
                $this->init_client();
            }
            
            $token = $this->client->fetchAccessTokenWithAuthCode($code);
            
            if (isset($token['error'])) {
                error_log('[DODO GSC] OAuth error: ' . $token['error']);
                return false;
            }
            
            update_option('dodo_gsc_access_token', $token);
            
            error_log('[DODO GSC] OAuth successful');
            return true;
            
        } catch (Exception $e) {
            error_log('[DODO GSC] OAuth callback error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Refresh access token
     */
    private function refresh_token() {
        try {
            $refresh_token = $this->client->getRefreshToken();
            $this->client->fetchAccessTokenWithRefreshToken($refresh_token);
            
            $new_token = $this->client->getAccessToken();
            update_option('dodo_gsc_access_token', $new_token);
            
            error_log('[DODO GSC] Token refreshed');
            
        } catch (Exception $e) {
            error_log('[DODO GSC] Token refresh error: ' . $e->getMessage());
        }
    }
    
    /**
     * Get query performance data
     *
     * @param array $options Query options
     * @return array Query data
     */
    public function get_query_performance($options = []) {
        $defaults = [
            'start_date' => date('Y-m-d', strtotime('-28 days')),
            'end_date' => date('Y-m-d', strtotime('-1 day')),
            'dimensions' => ['query'],
            'row_limit' => 1000,
        ];
        $options = wp_parse_args($options, $defaults);
        
        // Check cache
        $cache_key = 'dodo_gsc_queries_' . md5(serialize($options));
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            error_log('[DODO GSC] Query data from cache');
            return $cached;
        }
        
        if (!$this->service) {
            if (!$this->init_client()) {
                return ['error' => 'GSC not connected'];
            }
        }
        
        try {
            $request = new Google_Service_SearchConsole_SearchAnalyticsQueryRequest();
            $request->setStartDate($options['start_date']);
            $request->setEndDate($options['end_date']);
            $request->setDimensions($options['dimensions']);
            $request->setRowLimit($options['row_limit']);
            
            $response = $this->service->searchanalytics->query($this->site_url, $request);
            
            $queries = [];
            
            if ($response->getRows()) {
                foreach ($response->getRows() as $row) {
                    $queries[] = [
                        'query' => $row->getKeys()[0],
                        'clicks' => $row->getClicks(),
                        'impressions' => $row->getImpressions(),
                        'ctr' => $row->getCtr(),
                        'position' => $row->getPosition(),
                    ];
                }
            }
            
            // Cache result
            set_transient($cache_key, $queries, self::QUERY_CACHE_TTL);
            
            error_log(sprintf('[DODO GSC] Fetched %d queries', count($queries)));
            
            return $queries;
            
        } catch (Exception $e) {
            error_log('[DODO GSC] Query fetch error: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Get page performance data
     *
     * @param string $page_url Page URL
     * @return array Page performance
     */
    public function get_page_performance($page_url) {
        $cache_key = 'dodo_gsc_page_' . md5($page_url);
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        if (!$this->service) {
            if (!$this->init_client()) {
                return ['error' => 'GSC not connected'];
            }
        }
        
        try {
            $request = new Google_Service_SearchConsole_SearchAnalyticsQueryRequest();
            $request->setStartDate(date('Y-m-d', strtotime('-28 days')));
            $request->setEndDate(date('Y-m-d', strtotime('-1 day')));
            $request->setDimensions(['query']);
            
            // Filter by page
            $dimension_filter = new Google_Service_SearchConsole_ApiDimensionFilter();
            $dimension_filter->setDimension('page');
            $dimension_filter->setOperator('equals');
            $dimension_filter->setExpression($page_url);
            
            $filter_group = new Google_Service_SearchConsole_ApiDimensionFilterGroup();
            $filter_group->setFilters([$dimension_filter]);
            
            $request->setDimensionFilterGroups([$filter_group]);
            
            $response = $this->service->searchanalytics->query($this->site_url, $request);
            
            $queries = [];
            
            if ($response->getRows()) {
                foreach ($response->getRows() as $row) {
                    $queries[] = [
                        'query' => $row->getKeys()[0],
                        'clicks' => $row->getClicks(),
                        'impressions' => $row->getImpressions(),
                        'ctr' => $row->getCtr(),
                        'position' => $row->getPosition(),
                    ];
                }
            }
            
            $result = [
                'url' => $page_url,
                'queries' => $queries,
                'total_clicks' => array_sum(array_column($queries, 'clicks')),
                'total_impressions' => array_sum(array_column($queries, 'impressions')),
            ];
            
            set_transient($cache_key, $result, self::PERFORMANCE_CACHE_TTL);
            
            return $result;
            
        } catch (Exception $e) {
            error_log('[DODO GSC] Page performance error: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Detect CTR opportunities
     *
     * @return array High impression + low CTR queries
     */
    public function detect_ctr_opportunities() {
        $queries = $this->get_query_performance();
        
        if (isset($queries['error'])) {
            return $queries;
        }
        
        $opportunities = [];
        
        foreach ($queries as $query_data) {
            if ($query_data['impressions'] >= self::HIGH_IMPRESSION_THRESHOLD 
                && $query_data['ctr'] < self::LOW_CTR_THRESHOLD) {
                
                $opportunities[] = [
                    'query' => $query_data['query'],
                    'impressions' => $query_data['impressions'],
                    'clicks' => $query_data['clicks'],
                    'ctr' => round($query_data['ctr'] * 100, 2),
                    'position' => round($query_data['position'], 1),
                    'opportunity_type' => 'low_ctr',
                    'recommendation' => 'Optimize title and meta description',
                    'potential_clicks' => $this->estimate_potential_clicks($query_data),
                ];
            }
        }
        
        // Sort by potential
        usort($opportunities, function($a, $b) {
            return $b['potential_clicks'] - $a['potential_clicks'];
        });
        
        error_log(sprintf('[DODO GSC] Found %d CTR opportunities', count($opportunities)));
        
        return array_slice($opportunities, 0, 20);
    }
    
    /**
     * Detect ranking opportunities
     *
     * @return array Queries ranking 6-15
     */
    public function detect_ranking_opportunities() {
        $queries = $this->get_query_performance();
        
        if (isset($queries['error'])) {
            return $queries;
        }
        
        $opportunities = [];
        
        foreach ($queries as $query_data) {
            $position = $query_data['position'];
            
            if ($position >= self::RANKING_OPPORTUNITY_MIN 
                && $position <= self::RANKING_OPPORTUNITY_MAX
                && $query_data['impressions'] >= 100) {
                
                $opportunities[] = [
                    'query' => $query_data['query'],
                    'position' => round($position, 1),
                    'impressions' => $query_data['impressions'],
                    'clicks' => $query_data['clicks'],
                    'ctr' => round($query_data['ctr'] * 100, 2),
                    'opportunity_type' => 'ranking_boost',
                    'recommendation' => 'Content refresh and optimization',
                    'positions_to_gain' => ceil($position - 5),
                ];
            }
        }
        
        // Sort by impressions (high volume = high priority)
        usort($opportunities, function($a, $b) {
            return $b['impressions'] - $a['impressions'];
        });
        
        error_log(sprintf('[DODO GSC] Found %d ranking opportunities', count($opportunities)));
        
        return array_slice($opportunities, 0, 20);
    }
    
    /**
     * Detect decaying pages
     *
     * @return array Pages losing traffic
     */
    public function detect_decaying_pages() {
        // Compare current period vs previous period
        $current = $this->get_query_performance([
            'start_date' => date('Y-m-d', strtotime('-28 days')),
            'end_date' => date('Y-m-d', strtotime('-1 day')),
            'dimensions' => ['page'],
        ]);
        
        $previous = $this->get_query_performance([
            'start_date' => date('Y-m-d', strtotime('-56 days')),
            'end_date' => date('Y-m-d', strtotime('-29 days')),
            'dimensions' => ['page'],
        ]);
        
        if (isset($current['error']) || isset($previous['error'])) {
            return ['error' => 'Could not fetch comparison data'];
        }
        
        $decaying = [];
        
        // Index previous data
        $previous_indexed = [];
        foreach ($previous as $page_data) {
            $previous_indexed[$page_data['query']] = $page_data;
        }
        
        foreach ($current as $page_data) {
            $page_url = $page_data['query'];
            
            if (isset($previous_indexed[$page_url])) {
                $prev = $previous_indexed[$page_url];
                
                $click_change = $page_data['clicks'] - $prev['clicks'];
                $click_change_pct = $prev['clicks'] > 0 
                    ? (($click_change / $prev['clicks']) * 100) 
                    : 0;
                
                // Significant decline
                if ($click_change_pct < -20 && $prev['clicks'] >= 10) {
                    $decaying[] = [
                        'page' => $page_url,
                        'current_clicks' => $page_data['clicks'],
                        'previous_clicks' => $prev['clicks'],
                        'change' => $click_change,
                        'change_pct' => round($click_change_pct, 1),
                        'opportunity_type' => 'decay',
                        'recommendation' => 'Content refresh urgently needed',
                    ];
                }
            }
        }
        
        // Sort by decline severity
        usort($decaying, function($a, $b) {
            return $a['change_pct'] - $b['change_pct'];
        });
        
        error_log(sprintf('[DODO GSC] Found %d decaying pages', count($decaying)));
        
        return array_slice($decaying, 0, 20);
    }
    
    /**
     * Detect keyword cannibalization from GSC data
     *
     * @return array Queries with multiple URLs
     */
    public function detect_gsc_cannibalization() {
        $queries = $this->get_query_performance([
            'dimensions' => ['query', 'page'],
        ]);
        
        if (isset($queries['error'])) {
            return $queries;
        }
        
        // Group by query
        $query_pages = [];
        
        foreach ($queries as $row) {
            $query = $row['query'];
            $page = isset($row['page']) ? $row['page'] : '';
            
            if (!isset($query_pages[$query])) {
                $query_pages[$query] = [];
            }
            
            $query_pages[$query][] = [
                'page' => $page,
                'clicks' => $row['clicks'],
                'impressions' => $row['impressions'],
                'position' => $row['position'],
            ];
        }
        
        // Find queries with multiple pages
        $cannibalization = [];
        
        foreach ($query_pages as $query => $pages) {
            if (count($pages) >= 2) {
                // Sort by impressions
                usort($pages, function($a, $b) {
                    return $b['impressions'] - $a['impressions'];
                });
                
                $total_impressions = array_sum(array_column($pages, 'impressions'));
                
                if ($total_impressions >= 100) {
                    $cannibalization[] = [
                        'query' => $query,
                        'pages' => $pages,
                        'page_count' => count($pages),
                        'total_impressions' => $total_impressions,
                        'recommendation' => 'Consolidate or differentiate content',
                    ];
                }
            }
        }
        
        // Sort by impression volume
        usort($cannibalization, function($a, $b) {
            return $b['total_impressions'] - $a['total_impressions'];
        });
        
        error_log(sprintf('[DODO GSC] Found %d cannibalization cases', count($cannibalization)));
        
        return array_slice($cannibalization, 0, 20);
    }
    
    /**
     * Detect GEO signals in queries
     *
     * @return array GEO-friendly queries
     */
    public function detect_geo_signals() {
        $queries = $this->get_query_performance();
        
        if (isset($queries['error'])) {
            return $queries;
        }
        
        $geo_queries = [];
        
        foreach ($queries as $query_data) {
            $query = mb_strtolower($query_data['query'], 'UTF-8');
            $score = 0;
            $signals = [];
            
            // Conversational patterns
            if (preg_match('/^(nasıl|neden|ne zaman|kim|nerede|hangi)/u', $query)) {
                $score += 30;
                $signals[] = 'conversational_start';
            }
            
            // Question words
            if (preg_match('/(mı|mi|mu|mü)\s*$/u', $query)) {
                $score += 20;
                $signals[] = 'question_marker';
            }
            
            // Long-tail (5+ words)
            $word_count = count(preg_split('/\s+/u', $query));
            if ($word_count >= 5) {
                $score += 25;
                $signals[] = 'long_tail';
            }
            
            // Natural language
            if (preg_match('/(için|ile|gibi|kadar|daha)/u', $query)) {
                $score += 15;
                $signals[] = 'natural_language';
            }
            
            // Comparison
            if (preg_match('/(vs|veya|mi yoksa|fark)/u', $query)) {
                $score += 10;
                $signals[] = 'comparison';
            }
            
            if ($score >= 30) {
                $geo_queries[] = [
                    'query' => $query_data['query'],
                    'geo_score' => $score,
                    'signals' => $signals,
                    'impressions' => $query_data['impressions'],
                    'position' => round($query_data['position'], 1),
                    'recommendation' => 'Optimize for AI Overview',
                ];
            }
        }
        
        // Sort by GEO score
        usort($geo_queries, function($a, $b) {
            return $b['geo_score'] - $a['geo_score'];
        });
        
        error_log(sprintf('[DODO GSC] Found %d GEO signals', count($geo_queries)));
        
        return array_slice($geo_queries, 0, 20);
    }
    
    /**
     * Estimate potential clicks from CTR improvement
     */
    private function estimate_potential_clicks($query_data) {
        // Assume 5% CTR is achievable for most queries
        $target_ctr = 0.05;
        $current_clicks = $query_data['clicks'];
        $impressions = $query_data['impressions'];
        
        $potential_clicks = ($impressions * $target_ctr) - $current_clicks;
        
        return max(0, round($potential_clicks));
    }
    
    /**
     * Get connection status
     *
     * @return array Status info
     */
    public function get_connection_status() {
        $token = get_option('dodo_gsc_access_token');
        $credentials = get_option('dodo_gsc_credentials');
        
        return [
            'connected' => !empty($token),
            'has_credentials' => !empty($credentials),
            'site_url' => $this->site_url,
        ];
    }
    
    /**
     * Disconnect GSC
     */
    public function disconnect() {
        delete_option('dodo_gsc_access_token');
        error_log('[DODO GSC] Disconnected');
    }
}
