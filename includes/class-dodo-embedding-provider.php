<?php
/**
 * Embedding Provider
 * 
 * Abstraction layer for text embeddings
 * Supports: OpenAI, local models, fallback to TF-IDF
 *
 * @package DODO_AI_SEO
 * @since 2.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Embedding_Provider {
    
    /**
     * Provider types
     */
    const PROVIDER_OPENAI = 'openai';
    const PROVIDER_LOCAL = 'local';
    const PROVIDER_TFIDF = 'tfidf';
    
    /**
     * Current provider
     */
    private $provider;
    
    /**
     * OpenAI API key
     */
    private $api_key;
    
    /**
     * Cache
     */
    private $cache;
    
    /**
     * Telemetry instance (v2.4.0)
     */
    private $telemetry;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Get settings
        $settings = get_option('dodo_ai_seo_settings', []);
        $this->api_key = $settings['openai_api_key'] ?? '';
        
        // Determine provider
        $this->provider = $this->determine_provider();
        
        // Initialize cache
        require_once plugin_dir_path(__FILE__) . 'class-dodo-performance-cache.php';
        $this->cache = new DODO_Performance_Cache();
        
        // Initialize telemetry (v2.4.0)
        if (class_exists('DODO_Telemetry')) {
            $this->telemetry = new DODO_Telemetry();
        }
        
        error_log("[DODO Embedding] Provider: {$this->provider}");
    }
    
    /**
     * Determine best available provider
     */
    private function determine_provider() {
        // Check OpenAI
        if (!empty($this->api_key)) {
            return self::PROVIDER_OPENAI;
        }
        
        // Check local model (future: sentence-transformers via Python)
        // if (function_exists('python_exec')) {
        //     return self::PROVIDER_LOCAL;
        // }
        
        // Fallback to TF-IDF
        return self::PROVIDER_TFIDF;
    }
    
    /**
     * Generate embedding for text
     *
     * @param string $text Text to embed
     * @return array|null Embedding vector or null
     */
    public function generate_embedding($text) {
        // Check cache
        $cache_key = md5($text);
        $cached = $this->cache->get(DODO_Performance_Cache::CACHE_EMBEDDING, $cache_key);
        
        if ($cached !== null) {
            return $cached;
        }
        
        // Generate based on provider
        $embedding = null;
        
        switch ($this->provider) {
            case self::PROVIDER_OPENAI:
                $embedding = $this->generate_openai_embedding($text);
                break;
                
            case self::PROVIDER_LOCAL:
                $embedding = $this->generate_local_embedding($text);
                break;
                
            case self::PROVIDER_TFIDF:
                $embedding = $this->generate_tfidf_embedding($text);
                break;
        }
        
        // Cache result
        if ($embedding !== null) {
            $this->cache->set(
                DODO_Performance_Cache::CACHE_EMBEDDING,
                $cache_key,
                $embedding,
                DODO_Performance_Cache::TTL_EMBEDDING
            );
        }
        
        return $embedding;
    }
    
    /**
     * Generate OpenAI embedding
     */
    private function generate_openai_embedding($text) {
        if (empty($this->api_key)) {
            return null;
        }
        
        $start_time = microtime(true);
        
        // Rate limiting check (Phase 5.5)
        if (class_exists('DODO_Rate_Limiter')) {
            $rate_limiter = new DODO_Rate_Limiter();
            $rate_check = $rate_limiter->check_limit('embedding');
            
            if (!$rate_check['allowed']) {
                error_log('[DODO Embedding] Rate limit exceeded: ' . $rate_check['message']);
                return null; // Return null to allow graceful degradation
            }
        }
        
        // Truncate text if too long (OpenAI limit: 8191 tokens ≈ 32k chars)
        $text = mb_substr($text, 0, 30000, 'UTF-8');
        
        $response = wp_remote_post('https://api.openai.com/v1/embeddings', [
            'timeout' => 30,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode([
                'input' => $text,
                'model' => 'text-embedding-3-small', // 1536 dimensions, cheaper
            ]),
        ]);
        
        if (is_wp_error($response)) {
            error_log('[DODO Embedding] OpenAI error: ' . $response->get_error_message());
            return null;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!isset($body['data'][0]['embedding'])) {
            error_log('[DODO Embedding] OpenAI invalid response: ' . print_r($body, true));
            
            // Telemetry tracking for error (v2.4.0)
            if (isset($this->telemetry)) {
                $this->telemetry->track_ai_request([
                    'operation' => 'embedding_generation',
                    'model' => 'text-embedding-3-small',
                    'tokens' => 0,
                    'duration_ms' => round((microtime(true) - $start_time) * 1000, 2),
                    'success' => false,
                    'cache_hit' => false,
                ]);
            }
            
            return null;
        }
        
        $embedding = $body['data'][0]['embedding'];
        $execution_time = round((microtime(true) - $start_time) * 1000, 2);
        
        // Telemetry tracking for success (v2.4.0)
        if (isset($this->telemetry)) {
            $tokens = $body['usage']['total_tokens'] ?? 0;
            $this->telemetry->track_ai_request([
                'operation' => 'embedding_generation',
                'model' => 'text-embedding-3-small',
                'tokens' => $tokens,
                'duration_ms' => $execution_time,
                'success' => true,
                'cache_hit' => false,
            ]);
        }
        
        error_log(sprintf(
            '[DODO Embedding] OpenAI embedding generated: %d dimensions (%sms)',
            count($embedding),
            $execution_time
        ));
        
        return $embedding;
    }
    
    /**
     * Generate local embedding (future implementation)
     */
    private function generate_local_embedding($text) {
        // Future: Use sentence-transformers via Python subprocess
        // Example: all-MiniLM-L6-v2 (384 dimensions, fast)
        
        error_log('[DODO Embedding] Local embedding not implemented yet');
        return null;
    }
    
    /**
     * Generate TF-IDF embedding (fallback)
     */
    private function generate_tfidf_embedding($text) {
        // Use semantic engine's TF-IDF
        require_once plugin_dir_path(__FILE__) . 'class-dodo-semantic-engine.php';
        $semantic = new DODO_Semantic_Engine();
        
        // Get TF-IDF vector (simplified)
        $tokens = $this->tokenize($text);
        $tf = $this->calculate_tf($tokens);
        
        // Convert to fixed-size vector (100 dimensions)
        $embedding = array_fill(0, 100, 0);
        $i = 0;
        
        foreach ($tf as $term => $value) {
            if ($i >= 100) break;
            $embedding[$i] = $value;
            $i++;
        }
        
        return $embedding;
    }
    
    /**
     * Calculate cosine similarity between embeddings
     *
     * @param array $embedding1 First embedding
     * @param array $embedding2 Second embedding
     * @return float Similarity (0-1)
     */
    public function cosine_similarity($embedding1, $embedding2) {
        if (empty($embedding1) || empty($embedding2)) {
            return 0;
        }
        
        if (count($embedding1) !== count($embedding2)) {
            error_log('[DODO Embedding] Dimension mismatch: ' . count($embedding1) . ' vs ' . count($embedding2));
            return 0;
        }
        
        $dot_product = 0;
        $magnitude1 = 0;
        $magnitude2 = 0;
        
        for ($i = 0; $i < count($embedding1); $i++) {
            $dot_product += $embedding1[$i] * $embedding2[$i];
            $magnitude1 += $embedding1[$i] * $embedding1[$i];
            $magnitude2 += $embedding2[$i] * $embedding2[$i];
        }
        
        $magnitude1 = sqrt($magnitude1);
        $magnitude2 = sqrt($magnitude2);
        
        if ($magnitude1 == 0 || $magnitude2 == 0) {
            return 0;
        }
        
        return $dot_product / ($magnitude1 * $magnitude2);
    }
    
    /**
     * Get provider info
     *
     * @return array Provider information
     */
    public function get_provider_info() {
        return [
            'provider' => $this->provider,
            'has_api_key' => !empty($this->api_key),
            'capabilities' => $this->get_capabilities(),
        ];
    }
    
    /**
     * Get provider capabilities
     */
    private function get_capabilities() {
        switch ($this->provider) {
            case self::PROVIDER_OPENAI:
                return [
                    'dimensions' => 1536,
                    'quality' => 'high',
                    'speed' => 'medium',
                    'cost' => 'low',
                ];
                
            case self::PROVIDER_LOCAL:
                return [
                    'dimensions' => 384,
                    'quality' => 'medium',
                    'speed' => 'fast',
                    'cost' => 'free',
                ];
                
            case self::PROVIDER_TFIDF:
                return [
                    'dimensions' => 100,
                    'quality' => 'basic',
                    'speed' => 'very_fast',
                    'cost' => 'free',
                ];
                
            default:
                return [];
        }
    }
    
    /**
     * Tokenize text (helper)
     */
    private function tokenize($text) {
        $text = mb_strtolower($text, 'UTF-8');
        $text = strip_tags($text);
        preg_match_all('/\p{L}+/u', $text, $matches);
        return $matches[0];
    }
    
    /**
     * Calculate term frequency (helper)
     */
    private function calculate_tf($tokens) {
        $tf = [];
        $total = count($tokens);
        
        foreach ($tokens as $token) {
            if (!isset($tf[$token])) {
                $tf[$token] = 0;
            }
            $tf[$token]++;
        }
        
        foreach ($tf as $term => $count) {
            $tf[$term] = $count / $total;
        }
        
        return $tf;
    }
}
