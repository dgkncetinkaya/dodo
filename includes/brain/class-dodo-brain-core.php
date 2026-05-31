<?php
/**
 * Brain Core Orchestrator
 * 
 * Central intelligence coordinator for DODO AI SEO.
 * Manages all analysis engines and provides unified decision-making interface.
 *
 * @package DODO_AI_SEO
 * @subpackage Brain
 * @since 2.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Prevent duplicate class declaration
if (class_exists('Dodo_Brain_Core')) {
    return;
}

class Dodo_Brain_Core {
    
    /**
     * Engine instances
     */
    private $keyword_intelligence;
    private $intent_engine;
    private $cannibalization_engine;
    private $geo_engine;
    private $topical_authority;
    private $link_intelligence;
    private $content_strategy;
    
    /**
     * Cache manager
     */
    private $cache_enabled = true;
    private $cache_ttl = 3600; // 1 hour
    
    /**
     * Debug mode
     */
    private $debug = true;
    
    /**
     * Constructor - Initialize all engines
     */
    public function __construct() {
        $this->load_engines();
    }
    
    /**
     * Load all brain engines
     */
    private function load_engines() {
        $brain_path = plugin_dir_path(__FILE__);
        
        require_once $brain_path . 'class-dodo-keyword-intelligence.php';
        require_once $brain_path . 'class-dodo-intent-engine.php';
        require_once $brain_path . 'class-dodo-cannibalization-engine.php';
        require_once $brain_path . 'class-dodo-brain-geo-engine.php';
        require_once $brain_path . 'class-dodo-topical-authority.php';
        require_once $brain_path . 'class-dodo-link-intelligence.php';
        require_once $brain_path . 'class-dodo-content-strategy.php';
        
        $this->keyword_intelligence = new Dodo_Keyword_Intelligence();
        $this->intent_engine = new Dodo_Intent_Engine();
        $this->cannibalization_engine = new Dodo_Cannibalization_Engine();
        $this->geo_engine = new DODO_Brain_GEO_Engine();
        $this->topical_authority = new Dodo_Topical_Authority();
        $this->link_intelligence = new Dodo_Link_Intelligence();
        $this->content_strategy = new Dodo_Content_Strategy();
        
        if ($this->debug) {
            error_log('[DODO Brain Core] All engines loaded successfully');
        }
    }
    
    /**
     * Full keyword analysis - runs all relevant engines
     *
     * @param string $keyword Target keyword
     * @param array $options Analysis options
     * @return array Complete analysis from all engines
     */
    public function analyze_keyword($keyword, $options = []) {
        $start_time = microtime(true);
        
        // Check cache
        $cache_key = 'dodo_brain_keyword_' . md5($keyword . serialize($options));
        if ($this->cache_enabled) {
            $cached = get_transient($cache_key);
            if ($cached !== false) {
                if ($this->debug) {
                    error_log(sprintf('[DODO Brain Core] Cache HIT for keyword: %s', $keyword));
                }
                return $cached;
            }
        }
        
        if ($this->debug) {
            error_log(sprintf('[DODO Brain Core] Starting full analysis for: %s', $keyword));
        }
        
        // Run all engines
        $analysis = [
            'keyword' => $keyword,
            'timestamp' => current_time('mysql'),
            'keyword_intelligence' => $this->keyword_intelligence->analyze($keyword),
            'intent' => $this->intent_engine->analyze($keyword),
            'cannibalization' => $this->cannibalization_engine->analyze($keyword),
            'geo' => $this->geo_engine->analyze($keyword),
            'topical_authority' => $this->topical_authority->analyze($keyword),
            'content_strategy' => $this->content_strategy->analyze($keyword, $options),
        ];
        
        // Calculate overall recommendation score
        $analysis['overall_score'] = $this->calculate_overall_score($analysis);
        
        // Generate unified recommendations
        $analysis['recommendations'] = $this->generate_unified_recommendations($analysis);
        
        // Calculate total execution time
        $analysis['total_execution_time_ms'] = round((microtime(true) - $start_time) * 1000, 2);
        
        // Cache results
        if ($this->cache_enabled) {
            set_transient($cache_key, $analysis, $this->cache_ttl);
        }
        
        if ($this->debug) {
            error_log(sprintf(
                '[DODO Brain Core] Analysis complete | Score: %d | Time: %sms',
                $analysis['overall_score'],
                $analysis['total_execution_time_ms']
            ));
        }
        
        return $analysis;
    }
    
    /**
     * Quick keyword check - lightweight analysis
     *
     * @param string $keyword Target keyword
     * @return array Quick analysis results
     */
    public function quick_check($keyword) {
        $start_time = microtime(true);
        
        $analysis = [
            'keyword' => $keyword,
            'keyword_intelligence' => $this->keyword_intelligence->analyze($keyword),
            'intent' => $this->intent_engine->analyze($keyword),
            'cannibalization' => $this->cannibalization_engine->analyze($keyword),
        ];
        
        $analysis['recommendation'] = $this->get_quick_recommendation($analysis);
        $analysis['execution_time_ms'] = round((microtime(true) - $start_time) * 1000, 2);
        
        return $analysis;
    }
    
    /**
     * Analyze existing post for optimization opportunities
     *
     * @param int $post_id Post ID
     * @return array Post analysis with recommendations
     */
    public function analyze_post($post_id) {
        $start_time = microtime(true);
        
        $post = get_post($post_id);
        if (!$post) {
            return ['error' => 'Post not found'];
        }
        
        // Extract keyword from title or meta
        $keyword = $this->extract_keyword_from_post($post);
        
        if ($this->debug) {
            error_log(sprintf('[DODO Brain Core] Analyzing post %d | Keyword: %s', $post_id, $keyword));
        }
        
        $analysis = [
            'post_id' => $post_id,
            'post_title' => $post->post_title,
            'keyword' => $keyword,
            'timestamp' => current_time('mysql'),
            'link_intelligence' => $this->link_intelligence->analyze($post_id),
            'geo' => $this->geo_engine->analyze($keyword, ['content' => $post->post_content]),
        ];
        
        // Check cannibalization with this post
        $cannibalization = $this->cannibalization_engine->analyze($keyword);
        $analysis['cannibalization'] = $cannibalization;
        
        // Get topical authority for this keyword
        $analysis['topical_authority'] = $this->topical_authority->analyze($keyword);
        
        // Generate optimization recommendations
        $analysis['optimization_recommendations'] = $this->generate_post_optimization_recommendations($analysis);
        
        $analysis['execution_time_ms'] = round((microtime(true) - $start_time) * 1000, 2);
        
        if ($this->debug) {
            error_log(sprintf(
                '[DODO Brain Core] Post analysis complete | Link Health: %d | GEO Score: %d | Time: %sms',
                $analysis['link_intelligence']['health_score'],
                $analysis['geo']['overall_score'],
                $analysis['execution_time_ms']
            ));
        }
        
        return $analysis;
    }
    
    /**
     * Cluster analysis - analyze topic cluster strength
     *
     * @param string $topic Topic to analyze
     * @return array Cluster analysis
     */
    public function analyze_cluster($topic) {
        $start_time = microtime(true);
        
        // Validate input
        if (empty(trim($topic))) {
            return [
                'error' => true,
                'message' => 'Küme analizi için konu adı boş olamaz.',
                'topic' => '',
                'execution_time_ms' => 0
            ];
        }
        
        try {
            $analysis = [
                'topic' => $topic,
                'timestamp' => current_time('mysql'),
                'topical_authority' => $this->topical_authority->analyze($topic),
            ];
            
            $analysis['execution_time_ms'] = round((microtime(true) - $start_time) * 1000, 2);
            
            return $analysis;
        } catch (Throwable $e) {
            error_log('DODO Brain Core: Cluster analysis error: ' . $e->getMessage());
            return [
                'error' => true,
                'message' => 'Küme analizi sırasında hata oluştu.',
                'topic' => $topic,
                'execution_time_ms' => round((microtime(true) - $start_time) * 1000, 2)
            ];
        }
    }
    
    /**
     * Calculate overall opportunity score
     */
    private function calculate_overall_score($analysis) {
        $score = 0;
        $weights = [
            'keyword_opportunity' => 0.25,
            'intent_clarity' => 0.15,
            'cannibalization_risk' => 0.20,
            'geo_potential' => 0.20,
            'topical_authority' => 0.20,
        ];
        
        // Keyword opportunity (0-100)
        $score += $analysis['keyword_intelligence']['opportunity_score'] * $weights['keyword_opportunity'];
        
        // Intent clarity (0-100)
        $intent_confidence = $analysis['intent']['confidence'];
        $score += $intent_confidence * $weights['intent_clarity'];
        
        // Cannibalization risk (inverted - lower risk = higher score)
        $cannibalization_risk = $analysis['cannibalization']['risk_score'];
        $cannibalization_score = 100 - $cannibalization_risk;
        $score += $cannibalization_score * $weights['cannibalization_risk'];
        
        // GEO potential (0-100)
        $score += $analysis['geo']['overall_score'] * $weights['geo_potential'];
        
        // Topical authority (0-100)
        $score += $analysis['topical_authority']['authority_score'] * $weights['topical_authority'];
        
        return round($score);
    }
    
    /**
     * Generate unified recommendations from all engines
     */
    private function generate_unified_recommendations($analysis) {
        $recommendations = [
            'priority' => 'medium',
            'action' => 'create',
            'format' => $analysis['content_strategy']['recommended_format'],
            'length' => $analysis['content_strategy']['structure']['recommended_length'],
            'tone' => $analysis['content_strategy']['structure']['tone'],
            'warnings' => [],
            'opportunities' => [],
            'requirements' => [],
        ];
        
        $overall_score = $analysis['overall_score'];
        
        // Set priority based on overall score
        if ($overall_score >= 80) {
            $recommendations['priority'] = 'high';
            $recommendations['opportunities'][] = 'Excellent keyword opportunity - high potential ROI';
        } elseif ($overall_score >= 60) {
            $recommendations['priority'] = 'medium';
        } else {
            $recommendations['priority'] = 'low';
            $recommendations['warnings'][] = 'Low opportunity score - consider alternative keywords';
        }
        
        // Cannibalization warnings
        if ($analysis['cannibalization']['risk_score'] >= 70) {
            $recommendations['action'] = $analysis['cannibalization']['recommendation'];
            $recommendations['warnings'][] = sprintf(
                'High cannibalization risk (%d%%) - %s',
                $analysis['cannibalization']['risk_score'],
                $analysis['cannibalization']['recommendation']
            );
        }
        
        // GEO opportunities
        if ($analysis['geo']['overall_score'] >= 70) {
            $recommendations['opportunities'][] = 'High GEO potential - optimize for answer engines';
            $recommendations['requirements'][] = 'Use conversational language and answer blocks';
        }
        
        // Topical authority recommendations
        if ($analysis['topical_authority']['authority_score'] < 40) {
            $recommendations['warnings'][] = 'Weak topical authority - build supporting content first';
            $recommendations['requirements'][] = 'Create cluster content before targeting this keyword';
        }
        
        // Intent-based recommendations
        $intent = $analysis['intent']['primary_intent'];
        if ($intent === 'transactional') {
            $recommendations['requirements'][] = 'Include strong CTAs and conversion elements';
        } elseif ($intent === 'informational') {
            $recommendations['requirements'][] = 'Focus on educational content and examples';
        }
        
        // Competition warnings
        $competition = $analysis['keyword_intelligence']['competition'];
        if ($competition === 'high') {
            $recommendations['warnings'][] = 'High competition - requires exceptional content quality';
        }
        
        return $recommendations;
    }
    
    /**
     * Get quick recommendation
     */
    private function get_quick_recommendation($analysis) {
        $keyword_score = $analysis['keyword_intelligence']['opportunity_score'];
        $cannibalization_risk = $analysis['cannibalization']['risk_score'];
        
        if ($cannibalization_risk >= 70) {
            return [
                'action' => $analysis['cannibalization']['recommendation'],
                'reason' => 'High cannibalization risk detected',
                'confidence' => 'high',
            ];
        }
        
        if ($keyword_score >= 70) {
            return [
                'action' => 'create_new',
                'reason' => 'Excellent keyword opportunity',
                'confidence' => 'high',
            ];
        }
        
        if ($keyword_score >= 50) {
            return [
                'action' => 'create_new',
                'reason' => 'Good keyword opportunity',
                'confidence' => 'medium',
            ];
        }
        
        return [
            'action' => 'reconsider',
            'reason' => 'Low opportunity score - consider alternative keywords',
            'confidence' => 'medium',
        ];
    }
    
    /**
     * Generate post optimization recommendations
     */
    private function generate_post_optimization_recommendations($analysis) {
        $recommendations = [];
        
        // Link optimization
        $link_health = $analysis['link_intelligence']['health_score'];
        if ($link_health < 60) {
            $recommendations[] = [
                'type' => 'internal_linking',
                'priority' => 'high',
                'action' => 'Improve internal linking structure',
                'details' => $analysis['link_intelligence']['recommendations'],
            ];
        }
        
        // GEO optimization
        $geo_score = $analysis['geo']['overall_score'];
        if ($geo_score < 70) {
            $recommendations[] = [
                'type' => 'geo_optimization',
                'priority' => 'medium',
                'action' => 'Optimize for answer engines',
                'details' => $analysis['geo']['recommendations'],
            ];
        }
        
        // Cannibalization issues
        if ($analysis['cannibalization']['risk_score'] >= 50) {
            $recommendations[] = [
                'type' => 'cannibalization',
                'priority' => 'high',
                'action' => 'Address keyword cannibalization',
                'details' => [
                    'risk_score' => $analysis['cannibalization']['risk_score'],
                    'recommendation' => $analysis['cannibalization']['recommendation'],
                    'related_posts' => $analysis['cannibalization']['related_posts'],
                ],
            ];
        }
        
        // Topical authority
        $authority_score = $analysis['topical_authority']['authority_score'];
        if ($authority_score < 60) {
            $recommendations[] = [
                'type' => 'topical_authority',
                'priority' => 'medium',
                'action' => 'Build topical authority',
                'details' => $analysis['topical_authority']['recommendations'],
            ];
        }
        
        return $recommendations;
    }
    
    /**
     * Extract keyword from post
     */
    private function extract_keyword_from_post($post) {
        // Try to get from Yoast/RankMath meta
        $focus_keyword = get_post_meta($post->ID, '_yoast_wpseo_focuskw', true);
        if (!empty($focus_keyword)) {
            return $focus_keyword;
        }
        
        $focus_keyword = get_post_meta($post->ID, 'rank_math_focus_keyword', true);
        if (!empty($focus_keyword)) {
            return $focus_keyword;
        }
        
        // Fallback to title
        return $post->post_title;
    }
    
    /**
     * Clear cache for specific keyword
     */
    public function clear_cache($keyword = null) {
        if ($keyword) {
            $cache_key = 'dodo_brain_keyword_' . md5($keyword . serialize([]));
            delete_transient($cache_key);
            
            if ($this->debug) {
                error_log(sprintf('[DODO Brain Core] Cache cleared for keyword: %s', $keyword));
            }
        } else {
            // Clear all brain cache
            global $wpdb;
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_dodo_brain_%'");
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_dodo_brain_%'");
            
            if ($this->debug) {
                error_log('[DODO Brain Core] All cache cleared');
            }
        }
    }
    
    /**
     * Get engine status
     */
    public function get_engine_status() {
        return [
            'keyword_intelligence' => isset($this->keyword_intelligence),
            'intent_engine' => isset($this->intent_engine),
            'cannibalization_engine' => isset($this->cannibalization_engine),
            'geo_engine' => isset($this->geo_engine),
            'topical_authority' => isset($this->topical_authority),
            'link_intelligence' => isset($this->link_intelligence),
            'content_strategy' => isset($this->content_strategy),
            'cache_enabled' => $this->cache_enabled,
            'debug_mode' => $this->debug,
        ];
    }
    
    /**
     * Enable/disable cache
     */
    public function set_cache_enabled($enabled) {
        $this->cache_enabled = (bool) $enabled;
    }
    
    /**
     * Set cache TTL
     */
    public function set_cache_ttl($seconds) {
        $this->cache_ttl = (int) $seconds;
    }
    
    /**
     * Enable/disable debug mode
     */
    public function set_debug($enabled) {
        $this->debug = (bool) $enabled;
    }
}
