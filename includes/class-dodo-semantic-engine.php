<?php
/**
 * Semantic Intelligence Engine
 * 
 * Core semantic understanding layer for DODO AI SEO
 * Handles embeddings, similarity, clustering, entity extraction
 * Provider-agnostic architecture for future vector DB integration
 *
 * @package DODO_AI_SEO
 * @since 2.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Semantic_Engine {
    
    /**
     * Similarity thresholds
     */
    const SIMILARITY_VERY_HIGH = 0.90;
    const SIMILARITY_HIGH = 0.75;
    const SIMILARITY_MEDIUM = 0.60;
    const SIMILARITY_LOW = 0.40;
    
    /**
     * Cache TTL
     */
    const EMBEDDING_CACHE_TTL = 2592000; // 30 days
    const SIMILARITY_CACHE_TTL = 86400; // 1 day
    
    /**
     * Embedding provider
     */
    private $embedding_provider = null;
    
    /**
     * Use embeddings flag
     */
    private $use_embeddings = true;
    
    /**
     * Cache table
     */
    private $cache_table;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->cache_table = $wpdb->prefix . 'dodo_semantic_cache';
        
        // Initialize embedding provider
        require_once plugin_dir_path(__FILE__) . 'class-dodo-embedding-provider.php';
        $this->embedding_provider = new DODO_Embedding_Provider();
        
        $provider_info = $this->embedding_provider->get_provider_info();
        error_log('[DODO Semantic] Using provider: ' . $provider_info['provider']);
    }
    
    /**
     * Calculate semantic similarity between two texts
     *
     * @param string $text1 First text
     * @param string $text2 Second text
     * @param string $method Similarity method
     * @return float Similarity score (0-1)
     */
    public function calculate_similarity($text1, $text2, $method = 'auto') {
        $start_time = microtime(true);
        
        // Check cache
        $cache_key = $this->get_similarity_cache_key($text1, $text2, $method);
        $cached = $this->get_cached_similarity($cache_key);
        
        if ($cached !== null) {
            return $cached;
        }
        
        // Auto-select method based on provider
        if ($method === 'auto') {
            $provider_info = $this->embedding_provider->get_provider_info();
            
            if ($provider_info['provider'] === 'openai') {
                $method = 'embedding';
            } else {
                $method = 'tfidf';
            }
        }
        
        // Calculate similarity based on method
        switch ($method) {
            case 'embedding':
                $similarity = $this->embedding_similarity($text1, $text2);
                break;
                
            case 'tfidf':
                $similarity = $this->tfidf_similarity($text1, $text2);
                break;
                
            case 'jaccard':
                $similarity = $this->jaccard_similarity($text1, $text2);
                break;
                
            case 'cosine':
                $similarity = $this->cosine_similarity($text1, $text2);
                break;
                
            default:
                $similarity = $this->tfidf_similarity($text1, $text2);
        }
        
        // Cache result
        $this->cache_similarity($cache_key, $similarity);
        
        $execution_time = round((microtime(true) - $start_time) * 1000, 2);
        
        error_log(sprintf(
            '[DODO Semantic] Similarity calculated: %.2f (method: %s, time: %sms)',
            $similarity,
            $method,
            $execution_time
        ));
        
        return $similarity;
    }
    
    /**
     * Embedding-based similarity (TRUE SEMANTIC)
     */
    private function embedding_similarity($text1, $text2) {
        // Generate embeddings
        $embedding1 = $this->embedding_provider->generate_embedding($text1);
        $embedding2 = $this->embedding_provider->generate_embedding($text2);
        
        if ($embedding1 === null || $embedding2 === null) {
            error_log('[DODO Semantic] Embedding generation failed, falling back to TF-IDF');
            return $this->tfidf_similarity($text1, $text2);
        }
        
        // Calculate cosine similarity
        return $this->embedding_provider->cosine_similarity($embedding1, $embedding2);
    }
    
    /**
     * TF-IDF based similarity
     */
    private function tfidf_similarity($text1, $text2) {
        $tokens1 = $this->tokenize($text1);
        $tokens2 = $this->tokenize($text2);
        
        // Calculate term frequencies
        $tf1 = $this->calculate_tf($tokens1);
        $tf2 = $this->calculate_tf($tokens2);
        
        // Get all unique terms
        $all_terms = array_unique(array_merge(array_keys($tf1), array_keys($tf2)));
        
        // Build vectors
        $vector1 = [];
        $vector2 = [];
        
        foreach ($all_terms as $term) {
            $vector1[] = $tf1[$term] ?? 0;
            $vector2[] = $tf2[$term] ?? 0;
        }
        
        // Calculate cosine similarity
        return $this->cosine_similarity_vectors($vector1, $vector2);
    }
    
    /**
     * Jaccard similarity
     */
    private function jaccard_similarity($text1, $text2) {
        $tokens1 = array_unique($this->tokenize($text1));
        $tokens2 = array_unique($this->tokenize($text2));
        
        $intersection = count(array_intersect($tokens1, $tokens2));
        $union = count(array_unique(array_merge($tokens1, $tokens2)));
        
        return $union > 0 ? $intersection / $union : 0;
    }
    
    /**
     * Cosine similarity (wrapper)
     */
    private function cosine_similarity($text1, $text2) {
        return $this->tfidf_similarity($text1, $text2);
    }
    
    /**
     * Cosine similarity between vectors
     */
    private function cosine_similarity_vectors($vector1, $vector2) {
        $dot_product = 0;
        $magnitude1 = 0;
        $magnitude2 = 0;
        
        for ($i = 0; $i < count($vector1); $i++) {
            $dot_product += $vector1[$i] * $vector2[$i];
            $magnitude1 += $vector1[$i] * $vector1[$i];
            $magnitude2 += $vector2[$i] * $vector2[$i];
        }
        
        $magnitude1 = sqrt($magnitude1);
        $magnitude2 = sqrt($magnitude2);
        
        if ($magnitude1 == 0 || $magnitude2 == 0) {
            return 0;
        }
        
        return $dot_product / ($magnitude1 * $magnitude2);
    }
    
    /**
     * Tokenize text
     */
    private function tokenize($text) {
        // Convert to lowercase
        $text = mb_strtolower($text, 'UTF-8');
        
        // Remove HTML
        $text = strip_tags($text);
        
        // Extract words
        preg_match_all('/\p{L}+/u', $text, $matches);
        $tokens = $matches[0];
        
        // Remove stop words
        $tokens = $this->remove_stop_words($tokens);
        
        // Stem words (basic)
        $tokens = array_map([$this, 'stem'], $tokens);
        
        return $tokens;
    }
    
    /**
     * Calculate term frequency
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
        
        // Normalize
        foreach ($tf as $term => $count) {
            $tf[$term] = $count / $total;
        }
        
        return $tf;
    }
    
    /**
     * Remove stop words
     */
    private function remove_stop_words($tokens) {
        $stop_words = [
            // Turkish
            'bir', 've', 'veya', 'ile', 'için', 'bu', 'şu', 'o', 'da', 'de',
            'mi', 'mı', 'mu', 'mü', 'ne', 'ki', 'gibi', 'daha', 'çok', 'en',
            // English
            'the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to',
            'for', 'of', 'with', 'by', 'from', 'as', 'is', 'was', 'are',
        ];
        
        return array_filter($tokens, function($token) use ($stop_words) {
            return !in_array($token, $stop_words) && strlen($token) > 2;
        });
    }
    
    /**
     * Basic stemming
     */
    private function stem($word) {
        // Very basic Turkish/English stemming
        $suffixes = ['lar', 'ler', 'lık', 'lik', 'luk', 'lük', 'ing', 'ed', 'es', 's'];
        
        foreach ($suffixes as $suffix) {
            if (substr($word, -strlen($suffix)) === $suffix) {
                return substr($word, 0, -strlen($suffix));
            }
        }
        
        return $word;
    }
    
    /**
     * Extract entities from text
     *
     * @param string $text Text to analyze
     * @return array Extracted entities
     */
    public function extract_entities($text) {
        $entities = [
            'brands' => [],
            'products' => [],
            'places' => [],
            'technologies' => [],
            'people' => [],
            'concepts' => [],
        ];
        
        // Capitalize words (potential entities)
        preg_match_all('/\b[A-ZÇĞİÖŞÜ][a-zçğıöşü]+(?:\s+[A-ZÇĞİÖŞÜ][a-zçğıöşü]+)*\b/u', $text, $matches);
        
        $capitalized = $matches[0];
        
        // Classify entities (basic heuristics)
        foreach ($capitalized as $entity) {
            $entity_lower = mb_strtolower($entity, 'UTF-8');
            
            // Technology patterns
            if (preg_match('/(wordpress|php|javascript|python|react|vue|angular|laravel)/i', $entity_lower)) {
                $entities['technologies'][] = $entity;
            }
            // Brand patterns
            elseif (preg_match('/(google|microsoft|apple|amazon|facebook|meta)/i', $entity_lower)) {
                $entities['brands'][] = $entity;
            }
            // Place patterns (basic)
            elseif (preg_match('/(istanbul|ankara|izmir|turkey|türkiye)/i', $entity_lower)) {
                $entities['places'][] = $entity;
            }
            // Default to concepts
            else {
                $entities['concepts'][] = $entity;
            }
        }
        
        // Remove duplicates
        foreach ($entities as $type => $list) {
            $entities[$type] = array_unique($list);
        }
        
        return $entities;
    }
    
    /**
     * Calculate entity overlap between two texts
     *
     * @param string $text1 First text
     * @param string $text2 Second text
     * @return float Overlap score (0-1)
     */
    public function calculate_entity_overlap($text1, $text2) {
        $entities1 = $this->extract_entities($text1);
        $entities2 = $this->extract_entities($text2);
        
        $all_entities1 = [];
        $all_entities2 = [];
        
        foreach ($entities1 as $type => $list) {
            $all_entities1 = array_merge($all_entities1, $list);
        }
        
        foreach ($entities2 as $type => $list) {
            $all_entities2 = array_merge($all_entities2, $list);
        }
        
        if (empty($all_entities1) || empty($all_entities2)) {
            return 0;
        }
        
        $intersection = count(array_intersect($all_entities1, $all_entities2));
        $union = count(array_unique(array_merge($all_entities1, $all_entities2)));
        
        return $union > 0 ? $intersection / $union : 0;
    }
    
    /**
     * Cluster posts by semantic similarity
     *
     * @param array $post_ids Post IDs to cluster
     * @param float $threshold Similarity threshold
     * @return array Clusters
     */
    public function cluster_posts($post_ids, $threshold = self::SIMILARITY_MEDIUM) {
        $clusters = [];
        $processed = [];
        
        foreach ($post_ids as $post_id) {
            if (in_array($post_id, $processed)) {
                continue;
            }
            
            $cluster = [$post_id];
            $post_content = get_post_field('post_content', $post_id);
            
            // Find similar posts
            foreach ($post_ids as $compare_id) {
                if ($compare_id === $post_id || in_array($compare_id, $processed)) {
                    continue;
                }
                
                $compare_content = get_post_field('post_content', $compare_id);
                $similarity = $this->calculate_similarity($post_content, $compare_content);
                
                if ($similarity >= $threshold) {
                    $cluster[] = $compare_id;
                    $processed[] = $compare_id;
                }
            }
            
            $clusters[] = $cluster;
            $processed[] = $post_id;
        }
        
        error_log(sprintf(
            '[DODO Semantic] Clustered %d posts into %d clusters (threshold: %.2f)',
            count($post_ids),
            count($clusters),
            $threshold
        ));
        
        return $clusters;
    }
    
    /**
     * Find semantically similar posts
     *
     * @param int $post_id Reference post ID
     * @param int $limit Number of similar posts
     * @param float $min_similarity Minimum similarity
     * @return array Similar posts with scores
     */
    public function find_similar_posts($post_id, $limit = 10, $min_similarity = self::SIMILARITY_LOW) {
        $post_content = get_post_field('post_content', $post_id);
        
        // Get all published posts
        $posts = get_posts([
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => 100,
            'post__not_in' => [$post_id],
        ]);
        
        $similar = [];
        
        foreach ($posts as $post) {
            $similarity = $this->calculate_similarity($post_content, $post->post_content);
            
            if ($similarity >= $min_similarity) {
                $similar[] = [
                    'post_id' => $post->ID,
                    'title' => $post->post_title,
                    'similarity' => $similarity,
                ];
            }
        }
        
        // Sort by similarity
        usort($similar, function($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });
        
        return array_slice($similar, 0, $limit);
    }
    
    /**
     * Get similarity cache key
     */
    private function get_similarity_cache_key($text1, $text2, $method) {
        return 'dodo_sim_' . md5($text1 . $text2 . $method);
    }
    
    /**
     * Get cached similarity
     */
    private function get_cached_similarity($cache_key) {
        $cached = get_transient($cache_key);
        return $cached !== false ? (float) $cached : null;
    }
    
    /**
     * Cache similarity result
     */
    private function cache_similarity($cache_key, $similarity) {
        set_transient($cache_key, $similarity, self::SIMILARITY_CACHE_TTL);
    }
    
    /**
     * Create semantic cache table
     */
    public static function create_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dodo_semantic_cache';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            cache_key varchar(64) NOT NULL,
            cache_type varchar(50) NOT NULL,
            cache_value longtext NOT NULL,
            metadata text,
            created_at datetime NOT NULL,
            expires_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY cache_key (cache_key),
            KEY cache_type (cache_type),
            KEY expires_at (expires_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        error_log('[DODO Semantic] Semantic cache table created');
    }
}
