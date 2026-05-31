<?php
/**
 * OpenAI API Sınıfı
 * 
 * OpenAI API ile iletişimi yönetir
 *
 * @package DODO_AI_SEO
 * @since 1.0.0
 */

// Doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

class DODO_OpenAI {
    
    /**
     * API endpoint
     */
    private $api_url = 'https://api.openai.com/v1/chat/completions';
    
    /**
     * API key
     */
    private $api_key;
    
    /**
     * Settings instance
     */
    private $settings;
    
    /**
     * AI Cache instance
     */
    private $cache;
    
    /**
     * Usage Logger instance
     */
    private $usage_logger;
    
    /**
     * Telemetry instance (v2.4.0)
     */
    private $telemetry;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->settings = new DODO_Settings();
        $this->api_key = $this->settings->get_api_key();
        $this->cache = new DODO_AI_Cache();
        $this->usage_logger = new DODO_Usage_Logger();
        
        // Initialize telemetry (v2.4.0)
        if (class_exists('DODO_Telemetry')) {
            $this->telemetry = new DODO_Telemetry();
        }
        
        // Verify logger initialization (sadece debug modda)
        if (defined('WP_DEBUG') && WP_DEBUG) {
            if (!is_object($this->usage_logger)) {
                error_log('[DODO AI] CRITICAL: Usage Logger failed to initialize!');
            }
        }
    }
    
    /**
     * Generate text (backward compatible wrapper for Answer Blocks)
     * 
     * @param string $prompt User prompt
     * @param array $options Options (max_tokens, temperature, etc.)
     * @return string|WP_Error Generated text or error
     */
    public function generate_text($prompt, $options = array()) {
        // Extract options
        $max_tokens = $options['max_tokens'] ?? 500;
        $temperature = $options['temperature'] ?? 0.7;
        $feature_name = $options['feature'] ?? 'answer_block';
        
        // Build simple system prompt
        $system_prompt = "You are a helpful AI assistant. Generate concise, accurate content.";
        
        // Call generate_content with proper parameters
        return $this->generate_content($system_prompt, $prompt, $feature_name, false);
    }
    
    /**
     * İçerik üret (cache-aware)
     * 
     * @param string $system_prompt Sistem promptu
     * @param string $user_prompt Kullanıcı promptu
     * @param string $feature_name Feature adı (keyword_generation, faq, etc.)
     * @param bool $force_refresh Force refresh (bypass cache)
     * @return string|WP_Error Üretilen içerik veya hata
     */
    public function generate_content($system_prompt, $user_prompt, $feature_name = 'default', $force_refresh = false) {
        // API key kontrolü
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', __('OpenAI API anahtarı tanımlanmamış.', 'dodo-ai-seo'));
        }
        
        // Model ve temperature ayarlarını al
        $model = $this->settings->get_setting('openai_model', 'gpt-4o');
        $temperature = $this->settings->get_setting('openai_temperature', 0.7);
        $max_tokens = 16000;
        
        // Cache enabled mi kontrol et
        $cache_enabled = $this->cache->is_cache_enabled();
        
        // Cache key oluştur
        $cache_key = $this->cache->generate_cache_key(
            $model,
            $system_prompt,
            $user_prompt,
            $temperature,
            $max_tokens,
            $feature_name
        );
        
        // Cache'den dene (cache enabled ve force refresh değilse)
        if ($cache_enabled && !$force_refresh) {
            $cached = $this->cache->get_cached_response($cache_key);
            
            if ($cached !== false) {
                // Cache hit - usage log
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("[DODO AI] Cache HIT - Feature: {$feature_name}");
                }
                
                // Cache HIT: tokens = 0 (gerçek API çağrısı yok, maliyet yok)
                $log_result = $this->usage_logger->log_usage(
                    $feature_name,
                    $model,
                    0, // tokens_input = 0 (cache hit, API çağrısı yok)
                    0, // tokens_output = 0 (cache hit, API çağrısı yok)
                    0, // execution_time (cache hit, instant)
                    true // cache_hit
                );
                
                // Telemetry tracking for cache hit (v2.4.0)
                if (isset($this->telemetry)) {
                    try {
                        $this->telemetry->track_ai_request([
                            'operation' => $feature_name,
                            'model' => $model,
                            'tokens' => 0,
                            'duration_ms' => 0,
                            'success' => true,
                            'cache_hit' => true,
                        ]);
                    } catch (Throwable $e) {
                        // Telemetry failure should not break content generation
                        error_log('[DODO OpenAI] Telemetry tracking failed (cache hit): ' . $e->getMessage());
                    }
                }
                
                // Log failure (sadece debug modda)
                if (!$log_result && defined('WP_DEBUG') && WP_DEBUG) {
                    global $wpdb;
                    error_log("[DODO AI] Cache HIT log failed - WPDB Error: " . $wpdb->last_error);
                }
                
                return $cached['response'];
            }
        }
        
        // Cache miss - API çağrısı yap
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[DODO AI] Cache MISS - Feature: {$feature_name}");
        }
        
        $start_time = microtime(true);
        
        // Request body
        $body = array(
            'model' => $model,
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => $system_prompt
                ),
                array(
                    'role' => 'user',
                    'content' => $user_prompt
                )
            ),
            'temperature' => floatval($temperature),
            'max_completion_tokens' => $max_tokens,
        );
        
        // Request headers
        $headers = array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->api_key,
        );
        
        // Rate limiting check (Phase 5.5)
        if (class_exists('DODO_Rate_Limiter') && method_exists('DODO_Rate_Limiter', 'check_limit')) {
            try {
                $rate_check = DODO_Rate_Limiter::check_limit('openai', 1, array('user_id' => get_current_user_id()));
                
                if (is_array($rate_check) && isset($rate_check['allowed']) && !$rate_check['allowed']) {
                    error_log('[DODO OpenAI] Rate limit exceeded: ' . ($rate_check['message'] ?? 'Unknown'));
                    return new WP_Error(
                        'rate_limit_exceeded',
                        $rate_check['message'] ?? __('API kullanım limiti aşıldı. Lütfen bir dakika bekleyin.', 'dodo-ai-seo')
                    );
                }
            } catch (Throwable $e) {
                error_log('[DODO OpenAI] Rate limiter error (continuing without limit): ' . $e->getMessage());
                // Continue without rate limiting if error occurs
            }
        } else {
            error_log('[DODO OpenAI] Rate limiter unavailable, continuing without rate limit check');
        }
        
        // API isteği gönder
        $response = wp_remote_post($this->api_url, array(
            'headers' => $headers,
            'body' => json_encode($body),
            'timeout' => 120, // 2 dakika timeout
            'sslverify' => true,
        ));
        
        $execution_time = (int) ((microtime(true) - $start_time) * 1000); // milliseconds
        
        // Hata kontrolü
        if (is_wp_error($response)) {
            DODO_Error_Handler::log_error_with_context('OpenAI', 'API request failed', array(
                'error' => $response->get_error_message(),
                'feature' => $feature_name,
                'model' => $model
            ));
            
            return new WP_Error(
                'api_request_failed',
                sprintf(__('API isteği başarısız: %s', 'dodo-ai-seo'), $response->get_error_message())
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        // Response decode
        $data = json_decode($response_body, true);
        
        // HTTP hata kodları
        if ($response_code !== 200) {
            $error_message = isset($data['error']['message']) 
                ? $data['error']['message'] 
                : __('Bilinmeyen API hatası', 'dodo-ai-seo');
            
            $error_type = isset($data['error']['type']) ? $data['error']['type'] : 'unknown';
            $error_code = isset($data['error']['code']) ? $data['error']['code'] : 'unknown';
            
            // Hata logla
            $this->log_error('api_error', array(
                'response_code' => $response_code,
                'error_type' => $error_type,
                'error_code' => $error_code,
                'error_message' => $error_message,
                'full_response' => $data,
            ));
            
            // Enhanced error logging with context
            DODO_Error_Handler::log_error_with_context('OpenAI', 'API error response', array(
                'http_code' => $response_code,
                'error_type' => $error_type,
                'error_code' => $error_code,
                'feature' => $feature_name,
                'model' => $model
            ));
            
            // Telemetry tracking for API error (v2.4.0)
            if (isset($this->telemetry)) {
                try {
                    $this->telemetry->track_ai_request([
                        'operation' => $feature_name,
                        'model' => $model,
                        'tokens' => 0,
                        'duration_ms' => $execution_time,
                        'success' => false,
                        'cache_hit' => false,
                        'error_code' => $response_code,
                        'error_type' => $error_type,
                    ]);
                } catch (Throwable $e) {
                    // Telemetry failure should not break error handling
                    error_log('[DODO OpenAI] Telemetry tracking failed (API error): ' . $e->getMessage());
                }
            }
            
            // Specific error handling by HTTP status code
            if ($response_code === 401) {
                // Invalid API key
                return new WP_Error(
                    'openai_invalid_key',
                    __('OpenAI API anahtarı geçersiz. Lütfen ayarlardan kontrol edin.', 'dodo-ai-seo')
                );
            } elseif ($response_code === 403) {
                // Permission denied / Forbidden
                return new WP_Error(
                    'openai_permission_denied',
                    __('OpenAI API erişim izni reddedildi. Lütfen API anahtarı yetkilerini kontrol edin.', 'dodo-ai-seo')
                );
            } elseif ($response_code === 429) {
                // Rate limit veya quota aşımı
                if (strpos($error_message, 'insufficient_quota') !== false || strpos($error_code, 'insufficient_quota') !== false) {
                    return new WP_Error(
                        'openai_quota_exceeded',
                        __('OpenAI API kotanız dolmuş. Lütfen OpenAI hesabınızı kontrol edin ve kredi ekleyin.', 'dodo-ai-seo')
                    );
                } else {
                    return new WP_Error(
                        'openai_rate_limit',
                        __('OpenAI API rate limit aşıldı. Lütfen birkaç dakika bekleyip tekrar deneyin.', 'dodo-ai-seo')
                    );
                }
            } elseif ($response_code === 500) {
                // Internal server error
                return new WP_Error(
                    'openai_server_error',
                    __('OpenAI sunucu hatası. Lütfen birkaç dakika bekleyip tekrar deneyin.', 'dodo-ai-seo')
                );
            } elseif ($response_code === 503) {
                // Service unavailable
                return new WP_Error(
                    'openai_service_unavailable',
                    __('OpenAI servisi şu anda kullanılamıyor. Lütfen daha sonra tekrar deneyin.', 'dodo-ai-seo')
                );
            }
            
            // Generic error for other codes
            return new WP_Error(
                'api_error',
                sprintf(__('OpenAI API Hatası (%d): %s', 'dodo-ai-seo'), $response_code, $error_message)
            );
        }
        
        // İçeriği çıkar
        if (!isset($data['choices'][0]['message']['content'])) {
            return new WP_Error(
                'invalid_response',
                __('API yanıtı geçersiz format içeriyor.', 'dodo-ai-seo')
            );
        }
        
        $content = trim($data['choices'][0]['message']['content']);
        
        // Token usage al
        $tokens_input = $data['usage']['prompt_tokens'] ?? 0;
        $tokens_output = $data['usage']['completion_tokens'] ?? 0;
        
        // Record rate limiter (Phase 5.5)
        if (class_exists('DODO_Rate_Limiter') && method_exists('DODO_Rate_Limiter', 'record')) {
            try {
                DODO_Rate_Limiter::record('openai', get_current_user_id());
            } catch (Throwable $e) {
                error_log('[DODO OpenAI] Rate limiter record error: ' . $e->getMessage());
            }
        }
        
        // Usage log
        $log_result = $this->usage_logger->log_usage(
            $feature_name,
            $model,
            $tokens_input,
            $tokens_output,
            $execution_time,
            false // cache_hit = false (API çağrısı yapıldı)
        );
        
        // Telemetry tracking (v2.4.0)
        if (isset($this->telemetry)) {
            try {
                $this->telemetry->track_ai_request([
                    'operation' => $feature_name,
                    'model' => $model,
                    'tokens' => $tokens_input + $tokens_output,
                    'duration_ms' => $execution_time,
                    'success' => true,
                    'cache_hit' => false,
                ]);
            } catch (Throwable $e) {
                // Telemetry failure should not break content generation
                error_log('[DODO OpenAI] Telemetry tracking failed: ' . $e->getMessage());
            }
        }
        
        // Log failure (sadece debug modda)
        if (!$log_result && defined('WP_DEBUG') && WP_DEBUG) {
            global $wpdb;
            error_log("[DODO AI] API log failed - WPDB Error: " . $wpdb->last_error);
        }
        
        // Cache'e kaydet (cache enabled ise)
        if ($cache_enabled) {
            $this->cache->set_cached_response(
                $cache_key,
                $content,
                $feature_name,
                array(
                    'tokens_input' => $tokens_input,
                    'tokens_output' => $tokens_output,
                    'model' => $model,
                )
            );
        }
        
        // Legacy log (backward compatibility)
        $this->log_usage($data);
        
        return $content;
    }
    
    /**
     * Token kullanımını logla
     * 
     * @param array $response_data API yanıt verisi
     */
    private function log_usage($response_data) {
        if (!isset($response_data['usage'])) {
            return;
        }
        
        $usage = $response_data['usage'];
        
        // WordPress transient olarak sakla (debug için)
        $log_data = array(
            'timestamp' => current_time('mysql'),
            'prompt_tokens' => $usage['prompt_tokens'] ?? 0,
            'completion_tokens' => $usage['completion_tokens'] ?? 0,
            'total_tokens' => $usage['total_tokens'] ?? 0,
        );
        
        // Son 10 kullanımı sakla
        $logs = get_transient('dodo_openai_usage_logs') ?: array();
        array_unshift($logs, $log_data);
        $logs = array_slice($logs, 0, 10);
        
        set_transient('dodo_openai_usage_logs', $logs, DAY_IN_SECONDS);
    }
    
    /**
     * API key'i test et
     * 
     * @return bool|WP_Error
     */
    public function test_api_key() {
        $response = $this->generate_content(
            'You are a helpful assistant.',
            'Say "OK" if you can read this.',
            'api_key_test'
        );
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        return true;
    }
    
    /**
     * Token sayısını tahmin et (yaklaşık)
     * 
     * @param string $text
     * @return int
     */
    public function estimate_tokens($text) {
        // Basit tahmin: ~4 karakter = 1 token
        return (int) ceil(strlen($text) / 4);
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
     * Test API Connection (Sprint 4)
     * 
     * @param string $api_key API key to test (optional, uses stored key if not provided)
     * @return array Result with success status and message
     */
    public function test_connection($api_key = null) {
        $test_key = $api_key ?? $this->api_key;
        
        if (empty($test_key)) {
            return array(
                'success' => false,
                'message' => 'API key is required'
            );
        }
        
        // Simple test request
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $test_key,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'model' => 'gpt-3.5-turbo',
                'messages' => array(
                    array(
                        'role' => 'user',
                        'content' => 'test'
                    )
                ),
                'max_tokens' => 5
            )),
            'timeout' => 30,
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code === 200) {
            return array(
                'success' => true,
                'message' => 'API connection successful'
            );
        } elseif ($status_code === 401) {
            return array(
                'success' => false,
                'message' => 'Invalid API key'
            );
        } elseif ($status_code === 429) {
            return array(
                'success' => false,
                'message' => 'Rate limit exceeded'
            );
        } else {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            $error_message = $data['error']['message'] ?? 'Unknown error';
            
            return array(
                'success' => false,
                'message' => $error_message
            );
        }
    }
}
