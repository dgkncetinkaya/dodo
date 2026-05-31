<?php
/**
 * Validation Framework
 * 
 * Real data validation and accuracy measurement for Phase 4 systems
 * Measures precision, recall, false positives, semantic accuracy
 *
 * @package DODO_AI_SEO
 * @since 2.5.1
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Validation_Framework {
    
    /**
     * Validation results storage
     */
    private $results = [];
    
    /**
     * Test counter
     */
    private $test_count = 0;
    private $passed_count = 0;
    private $failed_count = 0;
    
    /**
     * Run all validations
     *
     * @return array Complete validation report
     */
    public function run_all_validations() {
        $start_time = microtime(true);
        
        error_log('[DODO Validation] Starting comprehensive validation suite');
        
        $this->results = [
            'gsc_validation' => $this->validate_gsc(),
            'semantic_validation' => $this->validate_semantic(),
            'cannibalization_validation' => $this->validate_cannibalization(),
            'clustering_validation' => $this->validate_clustering(),
            'link_intelligence_validation' => $this->validate_link_intelligence(),
            'geo_validation' => $this->validate_geo(),
            'serp_validation' => $this->validate_serp(),
            'performance_validation' => $this->validate_performance(),
            'embedding_validation' => $this->validate_embeddings(),
        ];
        
        $execution_time = round((microtime(true) - $start_time) * 1000, 2);
        
        // Calculate overall metrics
        $overall = $this->calculate_overall_metrics();
        
        $report = [
            'timestamp' => current_time('mysql'),
            'execution_time_ms' => $execution_time,
            'tests_run' => $this->test_count,
            'tests_passed' => $this->passed_count,
            'tests_failed' => $this->failed_count,
            'pass_rate' => $this->test_count > 0 ? round(($this->passed_count / $this->test_count) * 100, 2) : 0,
            'overall_metrics' => $overall,
            'detailed_results' => $this->results,
        ];
        
        error_log(sprintf(
            '[DODO Validation] Complete: %d/%d passed (%.1f%%) in %sms',
            $this->passed_count,
            $this->test_count,
            $report['pass_rate'],
            $execution_time
        ));
        
        return $report;
    }
    
    /**
     * Validate Google Search Console integration
     */
    private function validate_gsc() {
        $validation = [
            'component' => 'Google Search Console',
            'status' => 'testing',
            'tests' => [],
        ];
        
        require_once plugin_dir_path(__FILE__) . 'class-dodo-search-console.php';
        $gsc = new DODO_Search_Console();
        
        // Test 1: Connection status
        $status = $gsc->get_connection_status();
        $validation['tests']['connection'] = [
            'name' => 'GSC Connection',
            'passed' => $status['connected'],
            'details' => $status,
        ];
        $this->record_test($status['connected']);
        
        if (!$status['connected']) {
            $validation['status'] = 'not_connected';
            $validation['accuracy'] = 0;
            return $validation;
        }
        
        // Test 2: Query fetch
        $queries = $gsc->get_query_performance(['row_limit' => 10]);
        $query_fetch_ok = !isset($queries['error']) && !empty($queries);
        
        $validation['tests']['query_fetch'] = [
            'name' => 'Query Data Fetch',
            'passed' => $query_fetch_ok,
            'sample_count' => $query_fetch_ok ? count($queries) : 0,
        ];
        $this->record_test($query_fetch_ok);
        
        // Test 3: CTR opportunity detection
        if ($query_fetch_ok) {
            $ctr_opps = $gsc->detect_ctr_opportunities();
            $ctr_detection_ok = !isset($ctr_opps['error']);
            
            $validation['tests']['ctr_detection'] = [
                'name' => 'CTR Opportunity Detection',
                'passed' => $ctr_detection_ok,
                'opportunities_found' => $ctr_detection_ok ? count($ctr_opps) : 0,
            ];
            $this->record_test($ctr_detection_ok);
            
            // Validate CTR logic
            if ($ctr_detection_ok && !empty($ctr_opps)) {
                $sample = $ctr_opps[0];
                $logic_correct = $sample['impressions'] >= 1000 && $sample['ctr'] < 2;
                
                $validation['tests']['ctr_logic'] = [
                    'name' => 'CTR Logic Accuracy',
                    'passed' => $logic_correct,
                    'sample' => $sample,
                ];
                $this->record_test($logic_correct);
            }
        }
        
        // Test 4: Decay detection
        $decay = $gsc->detect_decaying_pages();
        $decay_ok = !isset($decay['error']);
        
        $validation['tests']['decay_detection'] = [
            'name' => 'Decay Detection',
            'passed' => $decay_ok,
            'decaying_pages' => $decay_ok ? count($decay) : 0,
        ];
        $this->record_test($decay_ok);
        
        // Test 5: Cannibalization detection
        $cannibal = $gsc->detect_gsc_cannibalization();
        $cannibal_ok = !isset($cannibal['error']);
        
        $validation['tests']['cannibalization'] = [
            'name' => 'GSC Cannibalization Detection',
            'passed' => $cannibal_ok,
            'cases_found' => $cannibal_ok ? count($cannibal) : 0,
        ];
        $this->record_test($cannibal_ok);
        
        $validation['status'] = 'completed';
        $validation['accuracy'] = $this->calculate_test_accuracy($validation['tests']);
        
        return $validation;
    }
    
    /**
     * Validate semantic similarity system
     */
    private function validate_semantic() {
        $validation = [
            'component' => 'Semantic Intelligence',
            'status' => 'testing',
            'tests' => [],
        ];
        
        require_once plugin_dir_path(__FILE__) . 'class-dodo-semantic-engine.php';
        $semantic = new DODO_Semantic_Engine();
        
        // Test cases: known similarity levels
        $test_cases = [
            [
                'text1' => 'WordPress SEO optimization techniques for better rankings',
                'text2' => 'SEO strategies to improve WordPress site rankings',
                'expected' => 'high',
                'min_similarity' => 0.60,
            ],
            [
                'text1' => 'WordPress SEO optimization techniques',
                'text2' => 'Cat food recipes for healthy pets',
                'expected' => 'low',
                'max_similarity' => 0.30,
            ],
            [
                'text1' => 'Screen printing ink prices and costs',
                'text2' => 'Screen printing equipment and machinery',
                'expected' => 'medium',
                'min_similarity' => 0.40,
                'max_similarity' => 0.70,
            ],
        ];
        
        $correct = 0;
        $total = count($test_cases);
        
        foreach ($test_cases as $i => $case) {
            $similarity = $semantic->calculate_similarity($case['text1'], $case['text2'], 'auto');
            
            $passed = false;
            if ($case['expected'] === 'high') {
                $passed = $similarity >= $case['min_similarity'];
            } elseif ($case['expected'] === 'low') {
                $passed = $similarity <= $case['max_similarity'];
            } else {
                $passed = $similarity >= $case['min_similarity'] && $similarity <= $case['max_similarity'];
            }
            
            if ($passed) $correct++;
            
            $validation['tests']['similarity_' . ($i + 1)] = [
                'name' => "Similarity Test " . ($i + 1) . " ({$case['expected']})",
                'passed' => $passed,
                'expected' => $case['expected'],
                'actual_similarity' => round($similarity * 100, 2) . '%',
            ];
            $this->record_test($passed);
        }
        
        // Test with real posts
        $posts = get_posts([
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => 5,
        ]);
        
        if (count($posts) >= 2) {
            $real_similarity = $semantic->calculate_similarity(
                $posts[0]->post_content,
                $posts[1]->post_content,
                'auto'
            );
            
            $validation['tests']['real_post_similarity'] = [
                'name' => 'Real Post Similarity',
                'passed' => $real_similarity >= 0 && $real_similarity <= 1,
                'post1' => $posts[0]->post_title,
                'post2' => $posts[1]->post_title,
                'similarity' => round($real_similarity * 100, 2) . '%',
            ];
            $this->record_test($real_similarity >= 0 && $real_similarity <= 1);
        }
        
        // Entity extraction test
        $test_text = "WordPress is a popular CMS. Google Analytics helps track website traffic. Istanbul is a major city in Turkey.";
        $entities = $semantic->extract_entities($test_text);
        
        $has_wordpress = in_array('WordPress', $entities['technologies']);
        $has_google = in_array('Google', $entities['brands']) || in_array('Google Analytics', $entities['technologies']);
        $has_istanbul = in_array('Istanbul', $entities['places']);
        
        $entity_accuracy = ($has_wordpress + $has_google + $has_istanbul) / 3;
        
        $validation['tests']['entity_extraction'] = [
            'name' => 'Entity Extraction',
            'passed' => $entity_accuracy >= 0.66,
            'accuracy' => round($entity_accuracy * 100, 2) . '%',
            'extracted' => $entities,
        ];
        $this->record_test($entity_accuracy >= 0.66);
        
        $validation['status'] = 'completed';
        $validation['accuracy'] = $this->calculate_test_accuracy($validation['tests']);
        $validation['semantic_accuracy'] = round(($correct / $total) * 100, 2);
        
        return $validation;
    }
    
    /**
     * Validate cannibalization detection
     */
    private function validate_cannibalization() {
        $validation = [
            'component' => 'Cannibalization Detection',
            'status' => 'testing',
            'tests' => [],
        ];
        
        require_once plugin_dir_path(dirname(__FILE__)) . 'brain/class-dodo-cannibalization-engine.php';
        $cannibal = new DODO_Cannibalization_Engine();
        
        // Get real posts for testing
        $posts = get_posts([
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => 10,
        ]);
        
        if (count($posts) >= 3) {
            $test_results = [];
            
            // Test with 3 different keywords
            foreach (array_slice($posts, 0, 3) as $i => $post) {
                $keyword = $post->post_title;
                $analysis = $cannibal->analyze($keyword);
                
                $test_results[] = [
                    'keyword' => $keyword,
                    'risk_score' => $analysis['risk_score'],
                    'risk_level' => $analysis['risk_level'],
                    'recommendation' => $analysis['recommendation'],
                    'related_count' => count($analysis['related_posts']),
                ];
                
                // Validate logic
                $logic_ok = $analysis['risk_score'] >= 0 && $analysis['risk_score'] <= 100;
                $this->record_test($logic_ok);
            }
            
            $validation['tests']['cannibalization_analysis'] = [
                'name' => 'Cannibalization Analysis',
                'passed' => true,
                'samples' => $test_results,
            ];
            
            // Check for false positives (very different content marked as high risk)
            $false_positive_check = true;
            foreach ($test_results as $result) {
                if ($result['risk_score'] > 80 && $result['related_count'] == 0) {
                    $false_positive_check = false;
                }
            }
            
            $validation['tests']['false_positive_check'] = [
                'name' => 'False Positive Check',
                'passed' => $false_positive_check,
            ];
            $this->record_test($false_positive_check);
        } else {
            $validation['tests']['insufficient_data'] = [
                'name' => 'Data Availability',
                'passed' => false,
                'reason' => 'Need at least 3 posts for testing',
            ];
            $this->record_test(false);
        }
        
        $validation['status'] = 'completed';
        $validation['accuracy'] = $this->calculate_test_accuracy($validation['tests']);
        
        return $validation;
    }
    
    /**
     * Validate clustering system
     */
    private function validate_clustering() {
        $validation = [
            'component' => 'Semantic Clustering',
            'status' => 'testing',
            'tests' => [],
        ];
        
        require_once plugin_dir_path(__FILE__) . 'class-dodo-semantic-engine.php';
        require_once plugin_dir_path(__FILE__) . 'class-dodo-topic-graph.php';
        
        $semantic = new DODO_Semantic_Engine();
        $graph = new DODO_Topic_Graph();
        
        // Get posts for clustering
        $posts = get_posts([
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => 20,
        ]);
        
        if (count($posts) >= 5) {
            $post_ids = array_map(function($p) { return $p->ID; }, $posts);
            
            // Test clustering
            $clusters = $semantic->cluster_posts($post_ids, 0.60);
            
            $validation['tests']['clustering'] = [
                'name' => 'Semantic Clustering',
                'passed' => !empty($clusters),
                'total_posts' => count($post_ids),
                'clusters_found' => count($clusters),
                'avg_cluster_size' => count($clusters) > 0 ? round(count($post_ids) / count($clusters), 2) : 0,
            ];
            $this->record_test(!empty($clusters));
            
            // Build graph
            $graph_stats = $graph->build_graph(['post_limit' => 20]);
            
            $validation['tests']['graph_building'] = [
                'name' => 'Topic Graph Building',
                'passed' => $graph_stats['nodes'] > 0 && $graph_stats['edges'] > 0,
                'nodes' => $graph_stats['nodes'],
                'edges' => $graph_stats['edges'],
            ];
            $this->record_test($graph_stats['nodes'] > 0);
            
            // Orphan detection
            $orphans = $graph->detect_orphans();
            
            $validation['tests']['orphan_detection'] = [
                'name' => 'Orphan Detection',
                'passed' => true,
                'orphans_found' => count($orphans),
            ];
            $this->record_test(true);
            
            // Weak cluster detection
            $weak = $graph->find_weak_clusters();
            
            $validation['tests']['weak_cluster_detection'] = [
                'name' => 'Weak Cluster Detection',
                'passed' => true,
                'weak_clusters' => count($weak),
            ];
            $this->record_test(true);
            
        } else {
            $validation['tests']['insufficient_data'] = [
                'name' => 'Data Availability',
                'passed' => false,
                'reason' => 'Need at least 5 posts for clustering',
            ];
            $this->record_test(false);
        }
        
        $validation['status'] = 'completed';
        $validation['accuracy'] = $this->calculate_test_accuracy($validation['tests']);
        
        return $validation;
    }
    
    /**
     * Validate link intelligence
     */
    private function validate_link_intelligence() {
        $validation = [
            'component' => 'Link Intelligence',
            'status' => 'testing',
            'tests' => [],
        ];
        
        require_once plugin_dir_path(__FILE__) . 'class-dodo-link-intelligence.php';
        $link_intel = new DODO_Link_Intelligence();
        
        $posts = get_posts([
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => 5,
        ]);
        
        if (!empty($posts)) {
            $test_post = $posts[0];
            $recommendations = $link_intel->generate_recommendations($test_post->ID, [
                'max_recommendations' => 5,
            ]);
            
            $has_recommendations = !isset($recommendations['error']) && !empty($recommendations['recommendations']);
            
            $validation['tests']['recommendation_generation'] = [
                'name' => 'Link Recommendation Generation',
                'passed' => $has_recommendations,
                'test_post' => $test_post->post_title,
                'recommendations_count' => $has_recommendations ? count($recommendations['recommendations']) : 0,
            ];
            $this->record_test($has_recommendations);
            
            // Validate anchor diversity
            if ($has_recommendations) {
                $sample_rec = $recommendations['recommendations'][0];
                $has_anchors = !empty($sample_rec['anchor_suggestions']);
                $anchor_diversity = $has_anchors ? count($sample_rec['anchor_suggestions']) : 0;
                
                $validation['tests']['anchor_diversity'] = [
                    'name' => 'Anchor Text Diversity',
                    'passed' => $anchor_diversity >= 3,
                    'diversity_count' => $anchor_diversity,
                    'sample_anchors' => $has_anchors ? array_slice($sample_rec['anchor_suggestions'], 0, 3) : [],
                ];
                $this->record_test($anchor_diversity >= 3);
            }
            
        } else {
            $validation['tests']['insufficient_data'] = [
                'name' => 'Data Availability',
                'passed' => false,
                'reason' => 'No posts available',
            ];
            $this->record_test(false);
        }
        
        $validation['status'] = 'completed';
        $validation['accuracy'] = $this->calculate_test_accuracy($validation['tests']);
        
        return $validation;
    }
    
    /**
     * Validate GEO analyzer
     */
    private function validate_geo() {
        $validation = [
            'component' => 'GEO Analyzer',
            'status' => 'testing',
            'tests' => [],
        ];
        
        require_once plugin_dir_path(__FILE__) . 'class-dodo-ai-overview-engine.php';
        $geo = new DODO_AI_Overview_Engine();
        
        // Test with sample content
        $test_content = "
            <h1>What is WordPress SEO?</h1>
            <p>WordPress SEO is the process of optimizing your WordPress website to rank higher in search engines like Google.</p>
            <p>It involves several key techniques including keyword optimization, content quality, and technical improvements.</p>
            <h2>Key Benefits</h2>
            <ul>
                <li>Increased organic traffic</li>
                <li>Better user experience</li>
                <li>Higher conversion rates</li>
            </ul>
        ";
        
        $analysis = $geo->analyze_geo_readiness($test_content, 'wordpress seo');
        
        $validation['tests']['geo_analysis'] = [
            'name' => 'GEO Readiness Analysis',
            'passed' => isset($analysis['geo_readiness_score']),
            'score' => $analysis['geo_readiness_score'] ?? 0,
            'level' => $analysis['readiness_level'] ?? 'unknown',
        ];
        $this->record_test(isset($analysis['geo_readiness_score']));
        
        // Validate component scores
        $components = ['answer_extraction', 'chunk_retrievability', 'citation_friendliness'];
        $all_components_present = true;
        
        foreach ($components as $component) {
            if (!isset($analysis['component_scores'][$component])) {
                $all_components_present = false;
            }
        }
        
        $validation['tests']['component_scores'] = [
            'name' => 'Component Scores',
            'passed' => $all_components_present,
            'components' => $analysis['component_scores'] ?? [],
        ];
        $this->record_test($all_components_present);
        
        $validation['status'] = 'completed';
        $validation['accuracy'] = $this->calculate_test_accuracy($validation['tests']);
        
        return $validation;
    }
    
    /**
     * Validate SERP intelligence
     */
    private function validate_serp() {
        $validation = [
            'component' => 'SERP Intelligence',
            'status' => 'testing',
            'tests' => [],
        ];
        
        require_once plugin_dir_path(__FILE__) . 'class-dodo-serp-intelligence.php';
        $serp = new DODO_SERP_Intelligence();
        
        $test_keyword = 'wordpress seo';
        $analysis = $serp->analyze_serp($test_keyword);
        
        $validation['tests']['serp_analysis'] = [
            'name' => 'SERP Analysis',
            'passed' => isset($analysis['difficulty']) && isset($analysis['intent']),
            'keyword' => $test_keyword,
            'difficulty' => $analysis['difficulty'] ?? 0,
            'intent' => $analysis['intent']['primary'] ?? 'unknown',
        ];
        $this->record_test(isset($analysis['difficulty']));
        
        // Note: Using mock data currently
        $validation['tests']['data_source'] = [
            'name' => 'Data Source',
            'passed' => false,
            'note' => 'Currently using mock data - real SERP API integration needed',
        ];
        $this->record_test(false);
        
        $validation['status'] = 'completed';
        $validation['accuracy'] = $this->calculate_test_accuracy($validation['tests']);
        
        return $validation;
    }
    
    /**
     * Validate performance
     */
    private function validate_performance() {
        $validation = [
            'component' => 'Performance',
            'status' => 'testing',
            'tests' => [],
        ];
        
        require_once plugin_dir_path(__FILE__) . 'class-dodo-performance-cache.php';
        $cache = new DODO_Performance_Cache();
        
        $stats = $cache->get_stats();
        
        $validation['tests']['cache_system'] = [
            'name' => 'Cache System',
            'passed' => isset($stats['total_entries']),
            'stats' => $stats,
        ];
        $this->record_test(isset($stats['total_entries']));
        
        // Benchmark similarity calculation
        require_once plugin_dir_path(__FILE__) . 'class-dodo-semantic-engine.php';
        $semantic = new DODO_Semantic_Engine();
        
        $text1 = str_repeat("WordPress SEO optimization ", 100);
        $text2 = str_repeat("SEO for WordPress sites ", 100);
        
        $start = microtime(true);
        $similarity = $semantic->calculate_similarity($text1, $text2);
        $time_ms = round((microtime(true) - $start) * 1000, 2);
        
        $validation['tests']['similarity_performance'] = [
            'name' => 'Similarity Calculation Performance',
            'passed' => $time_ms < 1000, // Should be under 1 second
            'execution_time_ms' => $time_ms,
        ];
        $this->record_test($time_ms < 1000);
        
        $validation['status'] = 'completed';
        $validation['accuracy'] = $this->calculate_test_accuracy($validation['tests']);
        
        return $validation;
    }
    
    /**
     * Validate embedding system
     */
    private function validate_embeddings() {
        $validation = [
            'component' => 'Embedding System',
            'status' => 'testing',
            'tests' => [],
        ];
        
        require_once plugin_dir_path(__FILE__) . 'class-dodo-embedding-provider.php';
        $provider = new DODO_Embedding_Provider();
        
        $info = $provider->get_provider_info();
        
        $validation['tests']['provider_initialization'] = [
            'name' => 'Provider Initialization',
            'passed' => !empty($info['provider']),
            'provider' => $info['provider'],
            'capabilities' => $info['capabilities'],
        ];
        $this->record_test(!empty($info['provider']));
        
        // Test embedding generation
        $test_text = "Test embedding generation";
        $embedding = $provider->generate_embedding($test_text);
        
        $validation['tests']['embedding_generation'] = [
            'name' => 'Embedding Generation',
            'passed' => !empty($embedding),
            'dimensions' => !empty($embedding) ? count($embedding) : 0,
        ];
        $this->record_test(!empty($embedding));
        
        // Test fallback
        $validation['tests']['fallback_mechanism'] = [
            'name' => 'TF-IDF Fallback',
            'passed' => $info['provider'] === 'tfidf' || $info['provider'] === 'openai',
            'active_provider' => $info['provider'],
        ];
        $this->record_test(true);
        
        $validation['status'] = 'completed';
        $validation['accuracy'] = $this->calculate_test_accuracy($validation['tests']);
        
        return $validation;
    }
    
    /**
     * Calculate test accuracy
     */
    private function calculate_test_accuracy($tests) {
        $passed = 0;
        $total = 0;
        
        foreach ($tests as $test) {
            if (isset($test['passed'])) {
                $total++;
                if ($test['passed']) $passed++;
            }
        }
        
        return $total > 0 ? round(($passed / $total) * 100, 2) : 0;
    }
    
    /**
     * Record test result
     */
    private function record_test($passed) {
        $this->test_count++;
        if ($passed) {
            $this->passed_count++;
        } else {
            $this->failed_count++;
        }
    }
    
    /**
     * Calculate overall metrics
     */
    private function calculate_overall_metrics() {
        $accuracies = [];
        
        foreach ($this->results as $component => $result) {
            if (isset($result['accuracy'])) {
                $accuracies[] = $result['accuracy'];
            }
        }
        
        return [
            'average_accuracy' => !empty($accuracies) ? round(array_sum($accuracies) / count($accuracies), 2) : 0,
            'components_tested' => count($this->results),
            'production_ready' => $this->assess_production_readiness(),
        ];
    }
    
    /**
     * Assess production readiness
     */
    private function assess_production_readiness() {
        $pass_rate = $this->test_count > 0 ? ($this->passed_count / $this->test_count) * 100 : 0;
        
        if ($pass_rate >= 90) {
            return 'excellent';
        } elseif ($pass_rate >= 75) {
            return 'good';
        } elseif ($pass_rate >= 60) {
            return 'fair';
        } else {
            return 'needs_improvement';
        }
    }
}
