<?php
/**
 * Real Data Strategy Engine
 * 
 * Brain that uses REAL data instead of heuristics
 * Search Console data, semantic analysis, SERP intelligence
 * Generates actionable strategies based on actual performance
 *
 * @package DODO_AI_SEO
 * @since 2.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Real_Data_Strategy {
    
    /**
     * Strategy types
     */
    const STRATEGY_TITLE_REWRITE = 'title_rewrite';
    const STRATEGY_CONTENT_REFRESH = 'content_refresh';
    const STRATEGY_NEW_SUPPORTING = 'new_supporting_article';
    const STRATEGY_MERGE_CONTENT = 'merge_content';
    const STRATEGY_GEO_OPTIMIZE = 'geo_optimize';
    const STRATEGY_SNIPPET_TARGET = 'snippet_target';
    
    /**
     * Components
     */
    private $search_console;
    private $serp_intelligence;
    private $semantic_engine;
    private $topic_graph;
    private $geo_analyzer;
    private $cannibalization_engine;
    
    /**
     * Constructor
     */
    public function __construct() {
        require_once plugin_dir_path(__FILE__) . 'class-dodo-search-console.php';
        require_once plugin_dir_path(__FILE__) . 'class-dodo-serp-intelligence.php';
        require_once plugin_dir_path(__FILE__) . 'class-dodo-semantic-engine.php';
        require_once plugin_dir_path(__FILE__) . 'class-dodo-topic-graph.php';
        require_once plugin_dir_path(__FILE__) . 'class-dodo-ai-overview-engine.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'brain/class-dodo-cannibalization-engine.php';
        
        $this->search_console = new DODO_Search_Console();
        $this->serp_intelligence = new DODO_SERP_Intelligence();
        $this->semantic_engine = new DODO_Semantic_Engine();
        $this->topic_graph = new DODO_Topic_Graph();
        $this->geo_analyzer = new DODO_AI_Overview_Engine();
        $this->cannibalization_engine = new DODO_Cannibalization_Engine();
    }
    
    /**
     * Generate data-driven strategies
     *
     * @param array $options Strategy options
     * @return array Strategies
     */
    public function generate_strategies($options = []) {
        $start_time = microtime(true);
        
        error_log('[DODO Real Data Strategy] Generating data-driven strategies');
        
        $defaults = [
            'max_strategies' => 10,
            'include_gsc' => true,
            'include_serp' => true,
            'include_semantic' => true,
        ];
        $options = wp_parse_args($options, $defaults);
        
        $strategies = [];
        
        // 1. GSC-based strategies
        if ($options['include_gsc']) {
            $gsc_strategies = $this->generate_gsc_strategies();
            $strategies = array_merge($strategies, $gsc_strategies);
        }
        
        // 2. Semantic-based strategies
        if ($options['include_semantic']) {
            $semantic_strategies = $this->generate_semantic_strategies();
            $strategies = array_merge($strategies, $semantic_strategies);
        }
        
        // 3. SERP-based strategies
        if ($options['include_serp']) {
            $serp_strategies = $this->generate_serp_strategies();
            $strategies = array_merge($strategies, $serp_strategies);
        }
        
        // Sort by priority
        usort($strategies, function($a, $b) {
            return $b['priority_score'] - $a['priority_score'];
        });
        
        // Limit to max
        $strategies = array_slice($strategies, 0, $options['max_strategies']);
        
        $execution_time = round((microtime(true) - $start_time) * 1000, 2);
        
        error_log(sprintf(
            '[DODO Real Data Strategy] Generated %d strategies (%sms)',
            count($strategies),
            $execution_time
        ));
        
        return [
            'strategies' => $strategies,
            'total_count' => count($strategies),
            'metadata' => [
                'execution_time_ms' => $execution_time,
                'generated_at' => current_time('mysql'),
            ],
        ];
    }
    
    /**
     * Generate GSC-based strategies
     */
    private function generate_gsc_strategies() {
        $strategies = [];
        
        // Check if GSC is connected
        $status = $this->search_console->get_connection_status();
        if (!$status['connected']) {
            error_log('[DODO Real Data Strategy] GSC not connected - skipping GSC strategies');
            return $strategies;
        }
        
        // 1. Low CTR opportunities → Title rewrite
        $ctr_opportunities = $this->search_console->detect_ctr_opportunities();
        
        if (!isset($ctr_opportunities['error'])) {
            foreach (array_slice($ctr_opportunities, 0, 3) as $opportunity) {
                $strategies[] = [
                    'type' => self::STRATEGY_TITLE_REWRITE,
                    'priority_score' => 90,
                    'data_source' => 'google_search_console',
                    'target' => [
                        'query' => $opportunity['query'],
                        'current_ctr' => $opportunity['ctr'],
                        'impressions' => $opportunity['impressions'],
                    ],
                    'action' => 'Rewrite title and meta description',
                    'reasoning' => [
                        "High impressions ({$opportunity['impressions']}) but low CTR ({$opportunity['ctr']}%)",
                        "Potential to gain {$opportunity['potential_clicks']} additional clicks",
                        'Title optimization is highest ROI action',
                    ],
                    'expected_impact' => "Potential +{$opportunity['potential_clicks']} clicks/month",
                ];
            }
        }
        
        // 2. Ranking 6-15 → Content refresh
        $ranking_opportunities = $this->search_console->detect_ranking_opportunities();
        
        if (!isset($ranking_opportunities['error'])) {
            foreach (array_slice($ranking_opportunities, 0, 3) as $opportunity) {
                $strategies[] = [
                    'type' => self::STRATEGY_CONTENT_REFRESH,
                    'priority_score' => 85,
                    'data_source' => 'google_search_console',
                    'target' => [
                        'query' => $opportunity['query'],
                        'current_position' => $opportunity['position'],
                        'impressions' => $opportunity['impressions'],
                    ],
                    'action' => 'Refresh and expand content',
                    'reasoning' => [
                        "Currently ranking at position {$opportunity['position']}",
                        "High search volume ({$opportunity['impressions']} impressions)",
                        'Close to page 1 - content refresh can push to top 5',
                    ],
                    'expected_impact' => 'Move to page 1, increase CTR by 3-5x',
                ];
            }
        }
        
        // 3. Decaying pages → Urgent refresh
        $decaying = $this->search_console->detect_decaying_pages();
        
        if (!isset($decaying['error'])) {
            foreach (array_slice($decaying, 0, 2) as $decay) {
                $strategies[] = [
                    'type' => self::STRATEGY_CONTENT_REFRESH,
                    'priority_score' => 95, // Highest priority - losing traffic
                    'data_source' => 'google_search_console',
                    'target' => [
                        'page' => $decay['page'],
                        'click_decline' => $decay['change_pct'],
                    ],
                    'action' => 'Emergency content refresh',
                    'reasoning' => [
                        "Traffic declined by {$decay['change_pct']}%",
                        'Urgent action needed to stop decay',
                        'Update content, add fresh data, improve structure',
                    ],
                    'expected_impact' => 'Stop traffic decline, recover lost rankings',
                ];
            }
        }
        
        // 4. GSC cannibalization → Merge
        $cannibalization = $this->search_console->detect_gsc_cannibalization();
        
        if (!isset($cannibalization['error'])) {
            foreach (array_slice($cannibalization, 0, 2) as $cannibal) {
                $strategies[] = [
                    'type' => self::STRATEGY_MERGE_CONTENT,
                    'priority_score' => 75,
                    'data_source' => 'google_search_console',
                    'target' => [
                        'query' => $cannibal['query'],
                        'page_count' => $cannibal['page_count'],
                        'pages' => array_slice($cannibal['pages'], 0, 3),
                    ],
                    'action' => 'Consolidate competing pages',
                    'reasoning' => [
                        "{$cannibal['page_count']} pages competing for same query",
                        'Diluting ranking power',
                        'Merge into single authoritative page',
                    ],
                    'expected_impact' => 'Consolidate ranking signals, improve position',
                ];
            }
        }
        
        return $strategies;
    }
    
    /**
     * Generate semantic-based strategies
     */
    private function generate_semantic_strategies() {
        $strategies = [];
        
        // Build topic graph
        $this->topic_graph->build_graph(['post_limit' => 50]);
        
        // 1. Weak clusters → New supporting content
        $weak_clusters = $this->topic_graph->find_weak_clusters();
        
        foreach (array_slice($weak_clusters, 0, 2) as $cluster) {
            $post_titles = [];
            foreach ($cluster['posts'] as $post_id) {
                $post_titles[] = get_the_title($post_id);
            }
            
            $strategies[] = [
                'type' => self::STRATEGY_NEW_SUPPORTING,
                'priority_score' => 70,
                'data_source' => 'semantic_analysis',
                'target' => [
                    'cluster_posts' => $cluster['posts'],
                    'cluster_size' => $cluster['size'],
                ],
                'action' => 'Create supporting content',
                'reasoning' => [
                    "Weak cluster detected: " . implode(', ', array_slice($post_titles, 0, 2)),
                    'Only ' . $cluster['size'] . ' posts on this topic',
                    'Add 2-3 supporting articles to strengthen topical authority',
                ],
                'expected_impact' => 'Strengthen topical authority, improve cluster rankings',
            ];
        }
        
        // 2. Orphan posts → Link building
        $orphans = $this->topic_graph->detect_orphans();
        
        if (count($orphans) > 0) {
            $strategies[] = [
                'type' => 'internal_linking',
                'priority_score' => 65,
                'data_source' => 'semantic_analysis',
                'target' => [
                    'orphan_count' => count($orphans),
                    'orphan_posts' => array_slice($orphans, 0, 5),
                ],
                'action' => 'Add internal links to orphan posts',
                'reasoning' => [
                    count($orphans) . ' orphan posts detected',
                    'No semantic or link connections',
                    'Add contextual internal links from related content',
                ],
                'expected_impact' => 'Improve crawlability and authority flow',
            ];
        }
        
        // 3. High semantic overlap → Merge consideration
        $posts = get_posts([
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => 20,
        ]);
        
        foreach ($posts as $post) {
            $similar = $this->semantic_engine->find_similar_posts($post->ID, 5, 0.85);
            
            if (!empty($similar) && $similar[0]['similarity'] >= 0.90) {
                $strategies[] = [
                    'type' => self::STRATEGY_MERGE_CONTENT,
                    'priority_score' => 80,
                    'data_source' => 'semantic_analysis',
                    'target' => [
                        'post_id' => $post->ID,
                        'post_title' => $post->post_title,
                        'similar_post' => $similar[0],
                    ],
                    'action' => 'Merge highly similar content',
                    'reasoning' => [
                        "90%+ semantic similarity with '{$similar[0]['title']}'",
                        'Likely cannibalization',
                        'Merge into comprehensive guide',
                    ],
                    'expected_impact' => 'Eliminate cannibalization, consolidate authority',
                ];
                break; // Only suggest one merge at a time
            }
        }
        
        return $strategies;
    }
    
    /**
     * Generate SERP-based strategies
     */
    private function generate_serp_strategies() {
        $strategies = [];
        
        // Get recent posts to analyze
        $posts = get_posts([
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => 10,
            'orderby' => 'date',
        ]);
        
        foreach (array_slice($posts, 0, 3) as $post) {
            // Extract main keyword from title
            $keyword = $post->post_title;
            
            // Analyze SERP
            $serp_analysis = $this->serp_intelligence->analyze_serp($keyword);
            
            // Check for snippet opportunity
            foreach ($serp_analysis['features'] as $feature) {
                if ($feature['type'] === 'featured_snippet' && $feature['opportunity']) {
                    $strategies[] = [
                        'type' => self::STRATEGY_SNIPPET_TARGET,
                        'priority_score' => 85,
                        'data_source' => 'serp_analysis',
                        'target' => [
                            'post_id' => $post->ID,
                            'post_title' => $post->post_title,
                            'snippet_type' => $feature['snippet_type'],
                        ],
                        'action' => 'Optimize for featured snippet',
                        'reasoning' => [
                            "Featured snippet opportunity detected",
                            "Type: {$feature['snippet_type']}",
                            'Add structured answer in first paragraph',
                        ],
                        'expected_impact' => 'Capture position 0, increase CTR by 10-20%',
                    ];
                }
            }
            
            // Check GEO readiness
            $geo_analysis = $this->geo_analyzer->analyze_geo_readiness($post->post_content, $keyword);
            
            if ($geo_analysis['geo_readiness_score'] < 60) {
                $strategies[] = [
                    'type' => self::STRATEGY_GEO_OPTIMIZE,
                    'priority_score' => 75,
                    'data_source' => 'geo_analysis',
                    'target' => [
                        'post_id' => $post->ID,
                        'post_title' => $post->post_title,
                        'current_score' => $geo_analysis['geo_readiness_score'],
                    ],
                    'action' => 'Optimize for AI Overview',
                    'reasoning' => [
                        "GEO readiness score: {$geo_analysis['geo_readiness_score']}/100",
                        'Improve answer extraction and chunk retrievability',
                        'Add structured data and clear citations',
                    ],
                    'expected_impact' => 'Increase AI Overview citation probability',
                ];
            }
        }
        
        return $strategies;
    }
    
    /**
     * Generate strategy for specific keyword
     *
     * @param string $keyword Target keyword
     * @return array Strategy
     */
    public function generate_keyword_strategy($keyword) {
        $start_time = microtime(true);
        
        error_log("[DODO Real Data Strategy] Generating strategy for: {$keyword}");
        
        // 1. Check cannibalization
        $cannibalization = $this->cannibalization_engine->analyze($keyword);
        
        // 2. Analyze SERP
        $serp = $this->serp_intelligence->analyze_serp($keyword);
        
        // 3. Get GSC data if available
        $gsc_data = null;
        $status = $this->search_console->get_connection_status();
        
        if ($status['connected']) {
            $queries = $this->search_console->get_query_performance();
            
            if (!isset($queries['error'])) {
                foreach ($queries as $query_data) {
                    if (stripos($query_data['query'], $keyword) !== false) {
                        $gsc_data = $query_data;
                        break;
                    }
                }
            }
        }
        
        // Generate recommendation
        $recommendation = $this->synthesize_recommendation($keyword, $cannibalization, $serp, $gsc_data);
        
        $execution_time = round((microtime(true) - $start_time) * 1000, 2);
        
        return [
            'keyword' => $keyword,
            'recommendation' => $recommendation,
            'cannibalization_analysis' => $cannibalization,
            'serp_analysis' => $serp,
            'gsc_data' => $gsc_data,
            'metadata' => [
                'execution_time_ms' => $execution_time,
                'analyzed_at' => current_time('mysql'),
            ],
        ];
    }
    
    /**
     * Synthesize recommendation from multiple data sources
     */
    private function synthesize_recommendation($keyword, $cannibalization, $serp, $gsc_data) {
        $recommendation = [
            'action' => '',
            'priority' => 'medium',
            'reasoning' => [],
            'specific_steps' => [],
        ];
        
        // Cannibalization takes precedence
        if ($cannibalization['risk_score'] >= 80) {
            $recommendation['action'] = 'update_existing';
            $recommendation['priority'] = 'high';
            $recommendation['reasoning'][] = 'Critical cannibalization detected';
            $recommendation['reasoning'][] = "Update existing post: {$cannibalization['related_posts'][0]['title']}";
            $recommendation['specific_steps'][] = 'Merge new content into existing post';
            $recommendation['specific_steps'][] = 'Consolidate ranking signals';
        } elseif ($cannibalization['risk_score'] >= 60) {
            $recommendation['action'] = 'differentiate';
            $recommendation['priority'] = 'medium';
            $recommendation['reasoning'][] = 'High overlap detected';
            $recommendation['reasoning'][] = 'Create with distinct angle';
        } else {
            $recommendation['action'] = 'create_new';
            $recommendation['priority'] = 'low';
            $recommendation['reasoning'][] = 'Low cannibalization risk';
        }
        
        // Add SERP insights
        if ($serp['difficulty'] >= 70) {
            $recommendation['reasoning'][] = "High keyword difficulty ({$serp['difficulty']})";
            $recommendation['specific_steps'][] = 'Create comprehensive, data-rich content';
        }
        
        if (!empty($serp['features'])) {
            foreach ($serp['features'] as $feature) {
                if ($feature['type'] === 'featured_snippet') {
                    $recommendation['specific_steps'][] = 'Target featured snippet with structured answer';
                }
                if ($feature['type'] === 'people_also_ask') {
                    $recommendation['specific_steps'][] = 'Include FAQ section with PAA questions';
                }
            }
        }
        
        // Add GSC insights
        if ($gsc_data) {
            $recommendation['reasoning'][] = "Current position: {$gsc_data['position']}";
            $recommendation['reasoning'][] = "Current CTR: " . round($gsc_data['ctr'] * 100, 2) . "%";
            
            if ($gsc_data['position'] > 10) {
                $recommendation['specific_steps'][] = 'Major content refresh needed';
            } elseif ($gsc_data['ctr'] < 0.03) {
                $recommendation['specific_steps'][] = 'Optimize title and meta description';
            }
        }
        
        return $recommendation;
    }
}
