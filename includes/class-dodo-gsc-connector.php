<?php
/**
 * Google Search Console Connector
 * 
 * Real GSC property connection and data validation
 * 
 * @package DODO_AI_SEO
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_GSC_Connector {
    
    private $client_id;
    private $client_secret;
    private $redirect_uri;
    private $access_token;
    private $refresh_token;
    
    public function __construct() {
        $this->client_id = get_option('dodo_gsc_client_id', '');
        $this->client_secret = get_option('dodo_gsc_client_secret', '');
        $this->redirect_uri = admin_url('admin.php?page=dodo-ai-seo-settings&gsc_callback=1');
        $this->access_token = get_option('dodo_gsc_access_token', '');
        $this->refresh_token = get_option('dodo_gsc_refresh_token', '');
    }
    
    /**
     * Get OAuth authorization URL
     */
    public function get_auth_url() {
        $params = [
            'client_id' => $this->client_id,
            'redirect_uri' => $this->redirect_uri,
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/webmasters.readonly',
            'access_type' => 'offline',
            'prompt' => 'consent',
        ];
        
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }
    
    /**
     * Exchange authorization code for tokens
     */
    public function exchange_code($code) {
        $response = wp_remote_post('https://oauth2.googleapis.com/token', [
            'body' => [
                'code' => $code,
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret,
                'redirect_uri' => $this->redirect_uri,
                'grant_type' => 'authorization_code',
            ],
        ]);
        
        if (is_wp_error($response)) {
            error_log('[DODO GSC] Token exchange error: ' . $response->get_error_message());
            return false;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['access_token'])) {
            update_option('dodo_gsc_access_token', $body['access_token']);
            $this->access_token = $body['access_token'];
        }
        
        if (isset($body['refresh_token'])) {
            update_option('dodo_gsc_refresh_token', $body['refresh_token']);
            $this->refresh_token = $body['refresh_token'];
        }
        
        error_log('[DODO GSC] Tokens saved successfully');
        
        return true;
    }
    
    /**
     * Refresh access token
     */
    private function refresh_access_token() {
        if (empty($this->refresh_token)) {
            return false;
        }
        
        $response = wp_remote_post('https://oauth2.googleapis.com/token', [
            'body' => [
                'refresh_token' => $this->refresh_token,
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret,
                'grant_type' => 'refresh_token',
            ],
        ]);
        
        if (is_wp_error($response)) {
            error_log('[DODO GSC] Token refresh error: ' . $response->get_error_message());
            return false;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['access_token'])) {
            update_option('dodo_gsc_access_token', $body['access_token']);
            $this->access_token = $body['access_token'];
            return true;
        }
        
        return false;
    }
    
    /**
     * Make API request
     * HARDENED: Phase 2 - Complete error handling + edge cases
     */
    private function api_request($endpoint, $method = 'GET', $body = null, $retry_count = 0) {
        $max_retries = 3;
        
        if (empty($this->access_token)) {
            if (class_exists('DODO_Error_Handler')) {
                DODO_Error_Handler::log_error_with_context('GSC', 'No access token available', array(
                    'endpoint' => $endpoint
                ));
            } else {
                error_log('[DODO][GSC] No access token available');
            }
            return new WP_Error('gsc_no_token', __('Google Search Console bağlantısı yok. Lütfen ayarlardan bağlantı kurun.', 'dodo-ai-seo'));
        }
        
        $args = [
            'method' => $method,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->access_token,
                'Content-Type' => 'application/json',
            ],
            'timeout' => 30,
        ];
        
        if ($body) {
            $args['body'] = json_encode($body);
        }
        
        $response = wp_remote_request($endpoint, $args);
        
        if (is_wp_error($response)) {
            if (class_exists('DODO_Error_Handler')) {
                DODO_Error_Handler::log_error_with_context('GSC', 'API request failed', array(
                    'endpoint' => $endpoint,
                    'error' => $response->get_error_message(),
                    'retry_count' => $retry_count
                ));
            } else {
                error_log('[DODO][GSC] API request error: ' . $response->get_error_message());
            }
            
            // Retry on network errors
            if ($retry_count < $max_retries) {
                sleep(pow(2, $retry_count)); // Exponential backoff
                return $this->api_request($endpoint, $method, $body, $retry_count + 1);
            }
            
            return new WP_Error('gsc_request_failed', __('Google Search Console API isteği başarısız: ', 'dodo-ai-seo') . $response->get_error_message());
        }
        
        $code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        // Handle different HTTP codes
        switch ($code) {
            case 401: // Unauthorized - token expired
                error_log('[DODO][GSC] Token expired (401), attempting refresh');
                if ($this->refresh_access_token()) {
                    // Retry with new token
                    $args['headers']['Authorization'] = 'Bearer ' . $this->access_token;
                    $response = wp_remote_request($endpoint, $args);
                    $response_body = wp_remote_retrieve_body($response);
                    return json_decode($response_body, true);
                } else {
                    return new WP_Error('gsc_token_expired', __('GSC token süresi doldu ve yenilenemedi. Lütfen yeniden bağlanın.', 'dodo-ai-seo'));
                }
                break;
                
            case 403: // Forbidden - permission denied
                error_log('[DODO][GSC] Permission denied (403)');
                return new WP_Error('gsc_permission_denied', __('GSC erişim izni yok. Property sahibi olduğunuzdan emin olun.', 'dodo-ai-seo'));
                break;
                
            case 429: // Rate limit exceeded
                error_log('[DODO][GSC] Rate limit exceeded (429)');
                if ($retry_count < $max_retries) {
                    $wait_time = pow(2, $retry_count + 2); // Longer wait for rate limits
                    error_log('[DODO][GSC] Waiting ' . $wait_time . ' seconds before retry');
                    sleep($wait_time);
                    return $this->api_request($endpoint, $method, $body, $retry_count + 1);
                }
                return new WP_Error('gsc_rate_limit', __('GSC API rate limit aşıldı. Lütfen daha sonra tekrar deneyin.', 'dodo-ai-seo'));
                break;
                
            case 500:
            case 502:
            case 503: // Server errors
                error_log('[DODO][GSC] Server error (' . $code . ')');
                if ($retry_count < $max_retries) {
                    sleep(pow(2, $retry_count));
                    return $this->api_request($endpoint, $method, $body, $retry_count + 1);
                }
                return new WP_Error('gsc_server_error', __('GSC sunucu hatası. Lütfen daha sonra tekrar deneyin.', 'dodo-ai-seo'));
                break;
                
            case 200: // Success
                return json_decode($response_body, true);
                break;
                
            default:
                error_log('[DODO][GSC] Unexpected response code: ' . $code);
                return json_decode($response_body, true);
        }
    }
    
    /**
     * Get site properties
     */
    public function get_properties() {
        $result = $this->api_request('https://www.googleapis.com/webmasters/v3/sites');
        
        if (!$result || !isset($result['siteEntry'])) {
            return [];
        }
        
        return $result['siteEntry'];
    }
    
    /**
     * Query search analytics
     * HARDENED: Phase 2 - Edge case handling
     */
    public function query_search_analytics($site_url, $start_date, $end_date, $dimensions = ['query'], $row_limit = 1000) {
        $endpoint = 'https://www.googleapis.com/webmasters/v3/sites/' . urlencode($site_url) . '/searchAnalytics/query';
        
        $body = [
            'startDate' => $start_date,
            'endDate' => $end_date,
            'dimensions' => $dimensions,
            'rowLimit' => min($row_limit, 25000), // GSC max limit
        ];
        
        $result = $this->api_request($endpoint, 'POST', $body);
        
        // Handle errors
        if (is_wp_error($result)) {
            error_log('[DODO GSC] Query failed: ' . $result->get_error_message());
            return [];
        }
        
        // Handle empty response
        if (!$result || !isset($result['rows'])) {
            error_log('[DODO GSC] Query returned no data - this is normal for new sites or low traffic');
            return [];
        }
        
        // Validate data structure
        $rows = $result['rows'];
        $valid_rows = [];
        
        foreach ($rows as $row) {
            // Skip invalid rows
            if (!isset($row['keys']) || !is_array($row['keys'])) {
                continue;
            }
            
            // Ensure numeric fields exist
            $row['impressions'] = isset($row['impressions']) ? (int) $row['impressions'] : 0;
            $row['clicks'] = isset($row['clicks']) ? (int) $row['clicks'] : 0;
            $row['ctr'] = isset($row['ctr']) ? (float) $row['ctr'] : 0;
            $row['position'] = isset($row['position']) ? (float) $row['position'] : 0;
            
            $valid_rows[] = $row;
        }
        
        error_log('[DODO GSC] Query successful: ' . count($valid_rows) . ' valid rows');
        
        return $valid_rows;
    }
    
    /**
     * Get low CTR opportunities
     */
    public function get_low_ctr_opportunities($site_url, $days = 30) {
        $end_date = date('Y-m-d');
        $start_date = date('Y-m-d', strtotime("-{$days} days"));
        
        $rows = $this->query_search_analytics($site_url, $start_date, $end_date, ['query', 'page']);
        
        $opportunities = [];
        
        foreach ($rows as $row) {
            $impressions = $row['impressions'] ?? 0;
            $clicks = $row['clicks'] ?? 0;
            $ctr = $row['ctr'] ?? 0;
            $position = $row['position'] ?? 0;
            
            // Low CTR opportunity: high impressions, low CTR, good position
            if ($impressions >= 100 && $ctr < 0.02 && $position <= 10) {
                $opportunities[] = [
                    'query' => $row['keys'][0] ?? '',
                    'page' => $row['keys'][1] ?? '',
                    'impressions' => $impressions,
                    'clicks' => $clicks,
                    'ctr' => round($ctr * 100, 2),
                    'position' => round($position, 1),
                    'opportunity_score' => $this->calculate_opportunity_score($impressions, $ctr, $position),
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
     * Detect content decay
     * HARDENED: Phase 2 - Intent drift + entity decay detection
     */
    public function detect_content_decay($site_url, $page_url, $days = 90) {
        $end_date = date('Y-m-d');
        $start_date = date('Y-m-d', strtotime("-{$days} days"));
        
        $rows = $this->query_search_analytics($site_url, $start_date, $end_date, ['date', 'page']);
        
        $page_data = [];
        
        foreach ($rows as $row) {
            if (isset($row['keys'][1]) && $row['keys'][1] === $page_url) {
                $date = $row['keys'][0];
                $page_data[$date] = [
                    'impressions' => $row['impressions'] ?? 0,
                    'clicks' => $row['clicks'] ?? 0,
                    'ctr' => $row['ctr'] ?? 0,
                    'position' => $row['position'] ?? 0,
                ];
            }
        }
        
        if (empty($page_data)) {
            return null;
        }
        
        // Calculate trend
        $dates = array_keys($page_data);
        sort($dates);
        
        // Compare first and last weeks
        $first_week = array_slice($dates, 0, min(7, count($dates)));
        $last_week = array_slice($dates, -min(7, count($dates)));
        
        $first_avg_impressions = $this->calculate_average($first_week, $page_data, 'impressions');
        $last_avg_impressions = $this->calculate_average($last_week, $page_data, 'impressions');
        
        $first_avg_position = $this->calculate_average($first_week, $page_data, 'position');
        $last_avg_position = $this->calculate_average($last_week, $page_data, 'position');
        
        $first_avg_ctr = $this->calculate_average($first_week, $page_data, 'ctr');
        $last_avg_ctr = $this->calculate_average($last_week, $page_data, 'ctr');
        
        // Calculate changes
        $impression_change = $first_avg_impressions > 0 
            ? (($last_avg_impressions - $first_avg_impressions) / $first_avg_impressions) * 100 
            : 0;
        
        $position_change = $last_avg_position - $first_avg_position;
        
        $ctr_change = $first_avg_ctr > 0
            ? (($last_avg_ctr - $first_avg_ctr) / $first_avg_ctr) * 100
            : 0;
        
        // Detect decay type
        $decay_type = 'none';
        $decay_reason = '';
        
        if ($impression_change < -20 && $position_change > 5) {
            $decay_type = 'ranking_drop';
            $decay_reason = 'Sıralama düştü - rakipler güçlenmiş veya algoritma değişikliği';
        } elseif ($impression_change < -30 && abs($position_change) < 3) {
            $decay_type = 'search_volume_drop';
            $decay_reason = 'Arama hacmi düştü - mevsimsel veya trend değişimi';
        } elseif ($ctr_change < -20 && abs($position_change) < 3) {
            $decay_type = 'ctr_drop';
            $decay_reason = 'CTR düştü - title/description çekiciliğini kaybetmiş';
        } elseif ($impression_change < -20) {
            $decay_type = 'general_decay';
            $decay_reason = 'Genel düşüş - içerik güncelliğini kaybetmiş';
        }
        
        // Check freshness
        $post_id = url_to_postid($page_url);
        $days_since_update = 999;
        
        if ($post_id) {
            $post = get_post($post_id);
            if ($post) {
                $modified_time = strtotime($post->post_modified);
                $days_since_update = floor((time() - $modified_time) / DAY_IN_SECONDS);
            }
        }
        
        // Freshness factor
        $freshness_issue = $days_since_update > 180; // 6 months
        
        if ($freshness_issue && $decay_type !== 'none') {
            $decay_reason .= ' + İçerik 6 aydan eski';
        }
        
        $is_decaying = $decay_type !== 'none';
        
        return [
            'page' => $page_url,
            'is_decaying' => $is_decaying,
            'decay_type' => $decay_type,
            'decay_reason' => $decay_reason,
            'impression_change_pct' => round($impression_change, 1),
            'position_change' => round($position_change, 1),
            'ctr_change_pct' => round($ctr_change, 1),
            'first_week_impressions' => round($first_avg_impressions),
            'last_week_impressions' => round($last_avg_impressions),
            'first_week_position' => round($first_avg_position, 1),
            'last_week_position' => round($last_avg_position, 1),
            'days_since_update' => $days_since_update,
            'freshness_issue' => $freshness_issue,
            'recommended_action' => $this->get_decay_action($decay_type, $freshness_issue),
        ];
    }
    
    /**
     * Get decay action recommendation
     * HARDENED: Phase 2 - Specific actionable recommendations
     * 
     * @param string $decay_type Type of decay
     * @param bool $freshness_issue Has freshness issue
     * @return string Action recommendation
     */
    private function get_decay_action($decay_type, $freshness_issue) {
        switch ($decay_type) {
            case 'ranking_drop':
                return 'Rakip analizi yap, içeriği derinleştir, E-A-T sinyallerini güçlendir, internal link ekle';
                
            case 'search_volume_drop':
                if ($freshness_issue) {
                    return 'İçeriği güncelle, yeni trendlere göre optimize et, tarih ekle';
                }
                return 'Mevsimsel düşüş olabilir, alternatif keywordler ekle, related topics genişlet';
                
            case 'ctr_drop':
                return 'Title ve meta description yenile, featured snippet için optimize et, FAQ ekle';
                
            case 'general_decay':
                if ($freshness_issue) {
                    return 'İçeriği tamamen yenile, yeni bilgiler ekle, eski bilgileri güncelle, tarih ekle';
                }
                return 'İçerik audit yap, eksik konuları ekle, semantic coverage artır';
                
            default:
                return 'İzlemeye devam et';
        }
    }
    
    /**
     * Detect keyword cannibalization
     * HARDENED: Phase 2 - Semantic overlap + intent detection
     */
    public function detect_cannibalization($site_url, $days = 30) {
        $end_date = date('Y-m-d');
        $start_date = date('Y-m-d', strtotime("-{$days} days"));
        
        $rows = $this->query_search_analytics($site_url, $start_date, $end_date, ['query', 'page']);
        
        // Group by query
        $query_pages = [];
        
        foreach ($rows as $row) {
            $query = $row['keys'][0] ?? '';
            $page = $row['keys'][1] ?? '';
            
            if (!isset($query_pages[$query])) {
                $query_pages[$query] = [];
            }
            
            $query_pages[$query][] = [
                'page' => $page,
                'impressions' => $row['impressions'] ?? 0,
                'clicks' => $row['clicks'] ?? 0,
                'position' => $row['position'] ?? 0,
                'ctr' => $row['ctr'] ?? 0,
            ];
        }
        
        // Find cannibalization
        $cannibalization = [];
        
        foreach ($query_pages as $query => $pages) {
            if (count($pages) < 2) {
                continue;
            }
            
            // Sort by impressions
            usort($pages, function($a, $b) {
                return $b['impressions'] <=> $a['impressions'];
            });
            
            $total_impressions = array_sum(array_column($pages, 'impressions'));
            
            // Only flag if significant traffic
            if ($total_impressions < 100) {
                continue;
            }
            
            // Calculate dominance - is there a clear winner?
            $top_page_impressions = $pages[0]['impressions'];
            $second_page_impressions = $pages[1]['impressions'] ?? 0;
            
            $dominance_ratio = $second_page_impressions > 0 
                ? $top_page_impressions / $second_page_impressions 
                : 999;
            
            // If one page dominates (>3x), not real cannibalization
            if ($dominance_ratio > 3) {
                continue;
            }
            
            // Calculate position spread
            $positions = array_column($pages, 'position');
            $position_spread = max($positions) - min($positions);
            
            // Calculate semantic overlap
            $semantic_score = $this->calculate_semantic_overlap($pages);
            
            // Determine severity
            $severity = 'low';
            if ($dominance_ratio < 1.5 && $position_spread < 5) {
                $severity = 'high'; // Very similar performance = high cannibalization
            } elseif ($dominance_ratio < 2) {
                $severity = 'medium';
            }
            
            // Detect intent type
            $intent = $this->detect_query_intent($query);
            
            $cannibalization[] = [
                'query' => $query,
                'competing_pages' => count($pages),
                'pages' => array_slice($pages, 0, 5),
                'total_impressions' => $total_impressions,
                'dominance_ratio' => round($dominance_ratio, 2),
                'position_spread' => round($position_spread, 1),
                'semantic_overlap' => $semantic_score,
                'severity' => $severity,
                'intent' => $intent,
                'primary_page' => $pages[0]['page'],
                'recommended_action' => $this->get_cannibalization_action_v2($pages, $severity, $intent),
            ];
        }
        
        // Sort by severity and impressions
        usort($cannibalization, function($a, $b) {
            $severity_order = ['high' => 3, 'medium' => 2, 'low' => 1];
            $a_score = ($severity_order[$a['severity']] * 1000) + $a['total_impressions'];
            $b_score = ($severity_order[$b['severity']] * 1000) + $b['total_impressions'];
            return $b_score <=> $a_score;
        });
        
        return array_slice($cannibalization, 0, 20);
    }
    
    /**
     * Calculate semantic overlap between pages
     * HARDENED: Phase 2 - Real semantic analysis
     * 
     * @param array $pages Page data
     * @return float Overlap score 0-1
     */
    private function calculate_semantic_overlap($pages) {
        if (count($pages) < 2) {
            return 0;
        }
        
        $page_contents = [];
        
        // Get content for each page
        foreach (array_slice($pages, 0, 3) as $page_data) {
            $page_url = $page_data['page'];
            $post_id = url_to_postid($page_url);
            
            if (!$post_id) {
                continue;
            }
            
            $post = get_post($post_id);
            if (!$post) {
                continue;
            }
            
            // Extract keywords from title and content
            $text = strtolower($post->post_title . ' ' . wp_strip_all_tags($post->post_content));
            $words = str_word_count($text, 1);
            
            // Filter meaningful words (>3 chars)
            $keywords = array_filter($words, function($word) {
                return strlen($word) > 3;
            });
            
            $page_contents[$page_url] = array_count_values($keywords);
        }
        
        if (count($page_contents) < 2) {
            return 0.5; // Unknown, assume medium overlap
        }
        
        // Calculate Jaccard similarity between first two pages
        $pages_array = array_values($page_contents);
        $page1_keywords = array_keys($pages_array[0]);
        $page2_keywords = array_keys($pages_array[1]);
        
        $intersection = count(array_intersect($page1_keywords, $page2_keywords));
        $union = count(array_unique(array_merge($page1_keywords, $page2_keywords)));
        
        $overlap = $union > 0 ? $intersection / $union : 0;
        
        return round($overlap, 2);
    }
    
    /**
     * Detect query intent
     * HARDENED: Phase 2 - Intent classification
     * 
     * @param string $query Query string
     * @return string Intent type
     */
    private function detect_query_intent($query) {
        $query_lower = strtolower($query);
        
        // Transactional intent
        $transactional_keywords = ['satın al', 'fiyat', 'ucuz', 'indirim', 'kampanya', 'buy', 'price', 'cheap', 'discount'];
        foreach ($transactional_keywords as $keyword) {
            if (strpos($query_lower, $keyword) !== false) {
                return 'transactional';
            }
        }
        
        // Navigational intent
        $navigational_keywords = ['giriş', 'login', 'kayıt', 'sign up', 'download', 'indir'];
        foreach ($navigational_keywords as $keyword) {
            if (strpos($query_lower, $keyword) !== false) {
                return 'navigational';
            }
        }
        
        // Informational intent (how-to, what is)
        $informational_keywords = ['nasıl', 'nedir', 'ne demek', 'how to', 'what is', 'why', 'neden'];
        foreach ($informational_keywords as $keyword) {
            if (strpos($query_lower, $keyword) !== false) {
                return 'informational';
            }
        }
        
        // Commercial investigation
        $commercial_keywords = ['en iyi', 'karşılaştırma', 'vs', 'best', 'review', 'comparison', 'top'];
        foreach ($commercial_keywords as $keyword) {
            if (strpos($query_lower, $keyword) !== false) {
                return 'commercial';
            }
        }
        
        return 'informational'; // Default
    }
    
    /**
     * Get cannibalization action (v2)
     * HARDENED: Phase 2 - Intent-aware recommendations
     * 
     * @param array $pages Competing pages
     * @param string $severity Severity level
     * @param string $intent Query intent
     * @return string Action recommendation
     */
    private function get_cannibalization_action_v2($pages, $severity, $intent) {
        if ($severity === 'high') {
            if ($intent === 'transactional' || $intent === 'commercial') {
                return 'Canonical tag kullan - ticari intent için tek güçlü sayfa olmalı';
            } else {
                return 'Sayfaları birleştir veya biri için 301 redirect yap';
            }
        } elseif ($severity === 'medium') {
            if ($intent === 'informational') {
                return 'Intent farklılaştır - biri "nasıl", diğeri "nedir" odaklı olabilir';
            } else {
                return 'Internal link yapısını düzenle - primary page\'e daha fazla link';
            }
        } else {
            return 'İzlemeye devam et - şimdilik kritik değil';
        }
    }
    
    /**
     * Calculate opportunity score
     */
    private function calculate_opportunity_score($impressions, $ctr, $position) {
        // Higher impressions = more opportunity
        // Lower CTR = more room for improvement
        // Better position = easier to improve
        
        $impression_score = min($impressions / 10, 100);
        $ctr_score = (1 - $ctr) * 100;
        $position_score = max(0, 100 - ($position * 10));
        
        return ($impression_score * 0.4) + ($ctr_score * 0.4) + ($position_score * 0.2);
    }
    
    /**
     * Calculate average
     */
    private function calculate_average($dates, $data, $key) {
        $values = [];
        
        foreach ($dates as $date) {
            if (isset($data[$date][$key])) {
                $values[] = $data[$date][$key];
            }
        }
        
        return !empty($values) ? array_sum($values) / count($values) : 0;
    }
    
    /**
     * Check if connected
     */
    public function is_connected() {
        return !empty($this->access_token) && !empty($this->refresh_token);
    }
    
    /**
     * Disconnect
     */
    public function disconnect() {
        delete_option('dodo_gsc_access_token');
        delete_option('dodo_gsc_refresh_token');
        
        error_log('[DODO GSC] Disconnected');
        
        return true;
    }
    
    /**
     * Test connection with real data
     */
    public function test_connection() {
        $properties = $this->get_properties();
        
        if (empty($properties)) {
            return [
                'success' => false,
                'message' => 'Could not fetch properties',
            ];
        }
        
        $site_url = $properties[0]['siteUrl'] ?? '';
        
        if (empty($site_url)) {
            return [
                'success' => false,
                'message' => 'No site URL found',
            ];
        }
        
        // Test query
        $end_date = date('Y-m-d');
        $start_date = date('Y-m-d', strtotime('-7 days'));
        
        $rows = $this->query_search_analytics($site_url, $start_date, $end_date, ['query'], 10);
        
        if (empty($rows)) {
            return [
                'success' => false,
                'message' => 'No search data available',
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Connection successful',
            'site_url' => $site_url,
            'sample_queries' => array_slice(array_column($rows, 'keys'), 0, 5),
            'total_queries' => count($rows),
        ];
    }
}

