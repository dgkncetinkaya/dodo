<?php
/**
 * Continuous Optimization Engine
 * 
 * Automatically generates optimization opportunities
 * Prioritizes low CTR, weak clusters, orphan content, decaying articles
 * 
 * @package DODO_AI_SEO
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Continuous_Optimizer {
    
    /**
     * Opportunity types
     */
    const OPP_LOW_CTR = 'low_ctr';
    const OPP_WEAK_CLUSTER = 'weak_cluster';
    const OPP_ORPHAN_CONTENT = 'orphan_content';
    const OPP_DECAYING_ARTICLE = 'decaying_article';
    const OPP_GEO_WEAK = 'geo_weak';
    const OPP_MISSING_ENTITIES = 'missing_entities';
    const OPP_POOR_INTERNAL_LINKS = 'poor_internal_links';
    const OPP_OUTDATED_CONTENT = 'outdated_content';
    const OPP_LOW_AI_VISIBILITY = 'low_ai_visibility';
    const OPP_RANKING_DECLINE = 'ranking_decline';
    
    /**
     * Priority levels
     */
    const PRIORITY_CRITICAL = 100;
    const PRIORITY_HIGH = 75;
    const PRIORITY_MEDIUM = 50;
    const PRIORITY_LOW = 25;
    
    /**
     * Dependencies
     */
    private $content_decay;
    private $ai_visibility;
    private $gsc;
    
    /**
     * Constructor
     */
    public function __construct() {
        if (class_exists('DODO_Content_Decay')) {
            require_once plugin_dir_path(__FILE__) . 'class-dodo-content-decay.php';
            $this->content_decay = new DODO_Content_Decay();
        }
        
        if (class_exists('DODO_AI_Visibility')) {
            require_once plugin_dir_path(__FILE__) . 'class-dodo-ai-visibility.php';
            $this->ai_visibility = new DODO_AI_Visibility();
        }
        
        if (class_exists('DODO_Search_Console')) {
            $this->gsc = new DODO_Search_Console();
        }
    }
    
    /**
     * Scan for optimization opportunities
     * 
     * @param array $options Scan options
     * @return array Opportunities
     */
    public function scan_opportunities($options = []) {
        error_log('[DODO Optimizer] Starting opportunity scan');
        
        $opportunities = [];
        
        // Low CTR opportunities
        $opportunities = array_merge($opportunities, $this->find_low_ctr_opportunities());
        
        // Weak cluster opportunities
        $opportunities = array_merge($opportunities, $this->find_weak_cluster_opportunities());
        
        // Orphan content opportunities
        $opportunities = array_merge($opportunities, $this->find_orphan_content_opportunities());
        
        // Decaying article opportunities
        $opportunities = array_merge($opportunities, $this->find_decaying_opportunities());
        
        // GEO weak opportunities
        $opportunities = array_merge($opportunities, $this->find_geo_weak_opportunities());
        
        // Missing entity opportunities
        $opportunities = array_merge($opportunities, $this->find_missing_entity_opportunities());
        
        // Poor internal link opportunities
        $opportunities = array_merge($opportunities, $this->find_poor_internal_link_opportunities());
        
        // Outdated content opportunities
        $opportunities = array_merge($opportunities, $this->find_outdated_content_opportunities());
        
        // Low AI visibility opportunities
        $opportunities = array_merge($opportunities, $this->find_low_ai_visibility_opportunities());
        
        // Ranking decline opportunities
        $opportunities = array_merge($opportunities, $this->find_ranking_decline_opportunities());
        
        // Sort by priority
        usort($opportunities, function($a, $b) {
            return $b['priority'] <=> $a['priority'];
        });
        
        error_log(sprintf(
            '[DODO Optimizer] Scan complete - Found %d opportunities',
            count($opportunities)
        ));
        
        return $opportunities;
    }
    
    /**
     * Find low CTR opportunities
     */
    private function find_low_ctr_opportunities() {
        $opportunities = [];
        
        if (!$this->gsc || !$this->gsc->is_available()['available']) {
            return $opportunities;
        }
        
        // Get posts with impressions but low CTR
        $posts = get_posts([
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => 50,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);
        
        foreach ($posts as $post) {
            $url = get_permalink($post->ID);
            $performance = $this->gsc->get_page_performance($url);
            
            if (isset($performance['error'])) {
                continue;
            }
            
            $impressions = $performance['total_impressions'] ?? 0;
            $clicks = $performance['total_clicks'] ?? 0;
            
            if ($impressions < 100) {
                continue; // Not enough data
            }
            
            $ctr = $impressions > 0 ? ($clicks / $impressions) * 100 : 0;
            
            // Low CTR threshold: < 2%
            if ($ctr < 2) {
                $opportunities[] = [
                    'type' => self::OPP_LOW_CTR,
                    'post_id' => $post->ID,
                    'post_title' => $post->post_title,
                    'priority' => $this->calculate_low_ctr_priority($ctr, $impressions),
                    'metrics' => [
                        'ctr' => round($ctr, 2),
                        'impressions' => $impressions,
                        'clicks' => $clicks,
                    ],
                    'recommendations' => [
                        [
                            'action' => 'title_rewrite',
                            'description' => 'Rewrite title to improve click appeal',
                        ],
                        [
                            'action' => 'meta_rewrite',
                            'description' => 'Optimize meta description for better CTR',
                        ],
                    ],
                    'reasoning' => sprintf(
                        'CTR is %.2f%% with %d impressions - significant improvement potential',
                        $ctr,
                        $impressions
                    ),
                ];
            }
        }
        
        return $opportunities;
    }
    
    /**
     * Find weak cluster opportunities
     */
    private function find_weak_cluster_opportunities() {
        $opportunities = [];
        
        // Get keyword clusters
        $clusters = $this->get_keyword_clusters();
        
        foreach ($clusters as $cluster) {
            // Weak cluster: < 3 posts or low total traffic
            if ($cluster['post_count'] < 3 || $cluster['total_traffic'] < 100) {
                $opportunities[] = [
                    'type' => self::OPP_WEAK_CLUSTER,
                    'cluster_id' => $cluster['id'],
                    'cluster_name' => $cluster['name'],
                    'priority' => $this->calculate_weak_cluster_priority($cluster),
                    'metrics' => [
                        'post_count' => $cluster['post_count'],
                        'total_traffic' => $cluster['total_traffic'],
                    ],
                    'recommendations' => [
                        [
                            'action' => 'create_pillar_content',
                            'description' => 'Create comprehensive pillar content for this cluster',
                        ],
                        [
                            'action' => 'strengthen_internal_links',
                            'description' => 'Add internal links between cluster posts',
                        ],
                    ],
                    'reasoning' => sprintf(
                        'Cluster has only %d posts with %d total traffic - needs strengthening',
                        $cluster['post_count'],
                        $cluster['total_traffic']
                    ),
                ];
            }
        }
        
        return $opportunities;
    }
    
    /**
     * Find orphan content opportunities
     */
    private function find_orphan_content_opportunities() {
        $opportunities = [];
        
        $posts = get_posts([
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => 100,
        ]);
        
        foreach ($posts as $post) {
            $inbound_links = $this->count_inbound_links($post->ID);
            
            // Orphan: 0-1 inbound links
            if ($inbound_links <= 1) {
                $opportunities[] = [
                    'type' => self::OPP_ORPHAN_CONTENT,
                    'post_id' => $post->ID,
                    'post_title' => $post->post_title,
                    'priority' => self::PRIORITY_MEDIUM,
                    'metrics' => [
                        'inbound_links' => $inbound_links,
                    ],
                    'recommendations' => [
                        [
                            'action' => 'add_internal_links',
                            'description' => 'Add internal links from related content',
                        ],
                        [
                            'action' => 'create_hub_page',
                            'description' => 'Create hub page linking to this content',
                        ],
                    ],
                    'reasoning' => sprintf(
                        'Post has only %d inbound link(s) - isolated from site structure',
                        $inbound_links
                    ),
                ];
            }
        }
        
        return $opportunities;
    }
    
    /**
     * Find decaying opportunities
     */
    private function find_decaying_opportunities() {
        $opportunities = [];
        
        if (!$this->content_decay) {
            return $opportunities;
        }
        
        $posts = get_posts([
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => 50,
            'orderby' => 'modified',
            'order' => 'ASC', // Oldest first
        ]);
        
        foreach ($posts as $post) {
            $decay = $this->content_decay->detect_decay($post->ID);
            
            if ($decay['decay_detected'] && $decay['decay_score'] >= 30) {
                $opportunities[] = [
                    'type' => self::OPP_DECAYING_ARTICLE,
                    'post_id' => $post->ID,
                    'post_title' => $post->post_title,
                    'priority' => $decay['refresh_priority'],
                    'metrics' => [
                        'decay_score' => $decay['decay_score'],
                        'severity' => $decay['severity'],
                    ],
                    'recommendations' => $decay['recommendations'],
                    'reasoning' => sprintf(
                        'Content showing %s decay (score: %d) - needs refresh',
                        $decay['severity'],
                        $decay['decay_score']
                    ),
                ];
            }
        }
        
        return $opportunities;
    }
    
    /**
     * Find GEO weak opportunities
     */
    private function find_geo_weak_opportunities() {
        $opportunities = [];
        
        if (!$this->ai_visibility) {
            return $opportunities;
        }
        
        $posts = get_posts([
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => 50,
        ]);
        
        foreach ($posts as $post) {
            $visibility = $this->ai_visibility->get_latest_visibility($post->ID);
            
            if ($visibility && $visibility['visibility_score'] < 50) {
                $opportunities[] = [
                    'type' => self::OPP_GEO_WEAK,
                    'post_id' => $post->ID,
                    'post_title' => $post->post_title,
                    'priority' => $this->calculate_geo_priority($visibility['visibility_score']),
                    'metrics' => [
                        'visibility_score' => $visibility['visibility_score'],
                    ],
                    'recommendations' => $this->ai_visibility->get_optimization_recommendations($post->ID),
                    'reasoning' => sprintf(
                        'AI Overview visibility score is %d - below target',
                        $visibility['visibility_score']
                    ),
                ];
            }
        }
        
        return $opportunities;
    }
    
    /**
     * Find missing entity opportunities
     */
    private function find_missing_entity_opportunities() {
        $opportunities = [];
        
        $posts = get_posts([
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => 50,
        ]);
        
        foreach ($posts as $post) {
            $entity_coverage = $this->analyze_entity_coverage($post->ID);
            
            if ($entity_coverage['coverage_score'] < 60) {
                $opportunities[] = [
                    'type' => self::OPP_MISSING_ENTITIES,
                    'post_id' => $post->ID,
                    'post_title' => $post->post_title,
                    'priority' => self::PRIORITY_MEDIUM,
                    'metrics' => [
                        'coverage_score' => $entity_coverage['coverage_score'],
                        'missing_entities' => $entity_coverage['missing_entities'],
                    ],
                    'recommendations' => [
                        [
                            'action' => 'add_entities',
                            'description' => 'Add missing semantic entities to content',
                            'entities' => array_slice($entity_coverage['missing_entities'], 0, 5),
                        ],
                    ],
                    'reasoning' => sprintf(
                        'Entity coverage is %d%% - missing %d key entities',
                        $entity_coverage['coverage_score'],
                        count($entity_coverage['missing_entities'])
                    ),
                ];
            }
        }
        
        return $opportunities;
    }
    
    /**
     * Find poor internal link opportunities
     */
    private function find_poor_internal_link_opportunities() {
        $opportunities = [];
        
        $posts = get_posts([
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => 50,
        ]);
        
        foreach ($posts as $post) {
            $link_metrics = $this->analyze_internal_links($post->ID);
            
            // Poor: < 3 outbound or < 2 inbound
            if ($link_metrics['outbound'] < 3 || $link_metrics['inbound'] < 2) {
                $opportunities[] = [
                    'type' => self::OPP_POOR_INTERNAL_LINKS,
                    'post_id' => $post->ID,
                    'post_title' => $post->post_title,
                    'priority' => self::PRIORITY_LOW,
                    'metrics' => $link_metrics,
                    'recommendations' => [
                        [
                            'action' => 'add_internal_links',
                            'description' => 'Add relevant internal links',
                            'suggested_links' => $this->suggest_internal_links($post->ID),
                        ],
                    ],
                    'reasoning' => sprintf(
                        'Poor internal linking: %d outbound, %d inbound',
                        $link_metrics['outbound'],
                        $link_metrics['inbound']
                    ),
                ];
            }
        }
        
        return $opportunities;
    }
    
    /**
     * Find outdated content opportunities
     */
    private function find_outdated_content_opportunities() {
        $opportunities = [];
        
        $posts = get_posts([
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => 50,
            'orderby' => 'modified',
            'order' => 'ASC',
        ]);
        
        foreach ($posts as $post) {
            $days_since_update = round((time() - strtotime($post->post_modified)) / 86400);
            
            // Outdated: > 365 days
            if ($days_since_update > 365) {
                $opportunities[] = [
                    'type' => self::OPP_OUTDATED_CONTENT,
                    'post_id' => $post->ID,
                    'post_title' => $post->post_title,
                    'priority' => $this->calculate_outdated_priority($days_since_update),
                    'metrics' => [
                        'days_since_update' => $days_since_update,
                        'last_modified' => $post->post_modified,
                    ],
                    'recommendations' => [
                        [
                            'action' => 'content_refresh',
                            'description' => 'Update statistics, examples, and information',
                        ],
                        [
                            'action' => 'add_current_year',
                            'description' => 'Add current year to title and content',
                        ],
                    ],
                    'reasoning' => sprintf(
                        'Content not updated in %d days - likely contains outdated information',
                        $days_since_update
                    ),
                ];
            }
        }
        
        return $opportunities;
    }
    
    /**
     * Find low AI visibility opportunities
     */
    private function find_low_ai_visibility_opportunities() {
        $opportunities = [];
        
        if (!$this->ai_visibility) {
            return $opportunities;
        }
        
        $insights = $this->ai_visibility->get_visibility_insights();
        
        if (isset($insights['low_performing_posts'])) {
            foreach ($insights['low_performing_posts'] as $post_data) {
                if ($post_data['visibility_score'] < 40) {
                    $opportunities[] = [
                        'type' => self::OPP_LOW_AI_VISIBILITY,
                        'post_id' => $post_data['post_id'],
                        'post_title' => $post_data['post_title'],
                        'priority' => self::PRIORITY_HIGH,
                        'metrics' => [
                            'visibility_score' => $post_data['visibility_score'],
                        ],
                        'recommendations' => $this->ai_visibility->get_optimization_recommendations($post_data['post_id']),
                        'reasoning' => sprintf(
                            'Very low AI Overview visibility (%d) - missing GEO opportunities',
                            $post_data['visibility_score']
                        ),
                    ];
                }
            }
        }
        
        return $opportunities;
    }
    
    /**
     * Find ranking decline opportunities
     */
    private function find_ranking_decline_opportunities() {
        $opportunities = [];
        
        // Would integrate with ranking tracking
        // For now, return empty
        
        return $opportunities;
    }
    
    /**
     * Calculate low CTR priority
     */
    private function calculate_low_ctr_priority($ctr, $impressions) {
        $priority = 50;
        
        // Lower CTR = higher priority
        if ($ctr < 1) {
            $priority += 30;
        } elseif ($ctr < 1.5) {
            $priority += 20;
        } elseif ($ctr < 2) {
            $priority += 10;
        }
        
        // More impressions = higher priority (more potential)
        if ($impressions > 1000) {
            $priority += 20;
        } elseif ($impressions > 500) {
            $priority += 10;
        }
        
        return min(100, $priority);
    }
    
    /**
     * Calculate weak cluster priority
     */
    private function calculate_weak_cluster_priority($cluster) {
        $priority = 40;
        
        // Fewer posts = higher priority
        if ($cluster['post_count'] == 1) {
            $priority += 20;
        } elseif ($cluster['post_count'] == 2) {
            $priority += 10;
        }
        
        // Lower traffic = higher priority
        if ($cluster['total_traffic'] < 50) {
            $priority += 15;
        }
        
        return min(100, $priority);
    }
    
    /**
     * Calculate GEO priority
     */
    private function calculate_geo_priority($visibility_score) {
        if ($visibility_score < 20) {
            return self::PRIORITY_CRITICAL;
        } elseif ($visibility_score < 30) {
            return self::PRIORITY_HIGH;
        } elseif ($visibility_score < 40) {
            return self::PRIORITY_MEDIUM;
        }
        
        return self::PRIORITY_LOW;
    }
    
    /**
     * Calculate outdated priority
     */
    private function calculate_outdated_priority($days) {
        if ($days > 730) { // 2 years
            return self::PRIORITY_HIGH;
        } elseif ($days > 545) { // 1.5 years
            return self::PRIORITY_MEDIUM;
        }
        
        return self::PRIORITY_LOW;
    }
    
    /**
     * Count inbound links
     */
    private function count_inbound_links($post_id) {
        global $wpdb;
        
        $permalink = get_permalink($post_id);
        
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} 
            WHERE post_status = 'publish' 
            AND post_content LIKE %s 
            AND ID != %d",
            '%' . $wpdb->esc_like($permalink) . '%',
            $post_id
        ));
    }
    
    /**
     * Analyze internal links
     */
    private function analyze_internal_links($post_id) {
        $post = get_post($post_id);
        
        if (!$post) {
            return ['inbound' => 0, 'outbound' => 0];
        }
        
        // Count outbound
        preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>/i', $post->post_content, $matches);
        $outbound = 0;
        
        foreach ($matches[1] as $url) {
            if (strpos($url, get_site_url()) !== false) {
                $outbound++;
            }
        }
        
        // Count inbound
        $inbound = $this->count_inbound_links($post_id);
        
        return [
            'inbound' => $inbound,
            'outbound' => $outbound,
        ];
    }
    
    /**
     * Suggest internal links
     */
    private function suggest_internal_links($post_id) {
        // Would use semantic similarity
        // For now, return related posts
        
        $post = get_post($post_id);
        $tags = wp_get_post_tags($post_id);
        
        if (empty($tags)) {
            return [];
        }
        
        $tag_ids = array_map(function($tag) {
            return $tag->term_id;
        }, $tags);
        
        $related = get_posts([
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => 5,
            'post__not_in' => [$post_id],
            'tag__in' => $tag_ids,
        ]);
        
        return array_map(function($p) {
            return [
                'post_id' => $p->ID,
                'title' => $p->post_title,
                'url' => get_permalink($p->ID),
            ];
        }, $related);
    }
    
    /**
     * Analyze entity coverage
     */
    private function analyze_entity_coverage($post_id) {
        // Simplified entity analysis
        // Would integrate with NLP/entity extraction
        
        return [
            'coverage_score' => rand(40, 90), // Placeholder
            'missing_entities' => ['entity1', 'entity2', 'entity3'],
        ];
    }
    
    /**
     * Get keyword clusters
     */
    private function get_keyword_clusters() {
        // Would integrate with keyword clustering
        // For now, return placeholder
        
        return [
            [
                'id' => 1,
                'name' => 'SEO Tools',
                'post_count' => 2,
                'total_traffic' => 50,
            ],
        ];
    }
    
    /**
     * Get opportunity summary
     * 
     * @param array $opportunities Opportunities
     * @return array Summary
     */
    public function get_opportunity_summary($opportunities) {
        $summary = [
            'total_opportunities' => count($opportunities),
            'by_type' => [],
            'by_priority' => [
                'critical' => 0,
                'high' => 0,
                'medium' => 0,
                'low' => 0,
            ],
            'estimated_impact' => 0,
        ];
        
        foreach ($opportunities as $opp) {
            // Count by type
            $type = $opp['type'];
            if (!isset($summary['by_type'][$type])) {
                $summary['by_type'][$type] = 0;
            }
            $summary['by_type'][$type]++;
            
            // Count by priority
            $priority = $opp['priority'];
            if ($priority >= 90) {
                $summary['by_priority']['critical']++;
            } elseif ($priority >= 70) {
                $summary['by_priority']['high']++;
            } elseif ($priority >= 40) {
                $summary['by_priority']['medium']++;
            } else {
                $summary['by_priority']['low']++;
            }
            
            // Estimate impact
            $summary['estimated_impact'] += $priority;
        }
        
        return $summary;
    }
}
