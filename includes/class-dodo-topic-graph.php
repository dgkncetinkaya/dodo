<?php
/**
 * Topical Graph System
 * 
 * Graph-based content relationship analysis for DODO AI SEO
 * Nodes: posts, keywords, entities, clusters, categories
 * Edges: semantic relations, links, support, overlap
 *
 * @package DODO_AI_SEO
 * @since 2.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Topic_Graph {
    
    /**
     * Node types
     */
    const NODE_POST = 'post';
    const NODE_KEYWORD = 'keyword';
    const NODE_ENTITY = 'entity';
    const NODE_CLUSTER = 'cluster';
    const NODE_CATEGORY = 'category';
    
    /**
     * Edge types
     */
    const EDGE_SEMANTIC = 'semantically_related';
    const EDGE_LINK = 'links_to';
    const EDGE_SUPPORT = 'supports';
    const EDGE_OVERLAP = 'overlaps';
    const EDGE_BELONGS = 'belongs_to';
    
    /**
     * Semantic engine
     */
    private $semantic_engine;
    
    /**
     * Graph data (in-memory for now)
     */
    private $nodes = [];
    private $edges = [];
    
    /**
     * Constructor
     */
    public function __construct() {
        require_once plugin_dir_path(__FILE__) . 'class-dodo-semantic-engine.php';
        $this->semantic_engine = new DODO_Semantic_Engine();
    }
    
    /**
     * Build graph from site content
     *
     * @param array $options Build options
     * @return array Graph statistics
     */
    public function build_graph($options = []) {
        $start_time = microtime(true);
        
        $defaults = [
            'post_limit' => 100,
            'similarity_threshold' => 0.6,
            'include_categories' => true,
            'include_entities' => true,
        ];
        $options = wp_parse_args($options, $defaults);
        
        error_log('[DODO Graph] Building topical graph...');
        
        // Reset graph
        $this->nodes = [];
        $this->edges = [];
        
        // Add post nodes
        $this->add_post_nodes($options['post_limit']);
        
        // Add category nodes
        if ($options['include_categories']) {
            $this->add_category_nodes();
        }
        
        // Add entity nodes
        if ($options['include_entities']) {
            $this->add_entity_nodes();
        }
        
        // Build edges
        $this->build_semantic_edges($options['similarity_threshold']);
        $this->build_link_edges();
        $this->build_category_edges();
        
        $execution_time = round((microtime(true) - $start_time) * 1000, 2);
        
        $stats = [
            'nodes' => count($this->nodes),
            'edges' => count($this->edges),
            'execution_time_ms' => $execution_time,
        ];
        
        error_log(sprintf(
            '[DODO Graph] Graph built: %d nodes, %d edges (%sms)',
            $stats['nodes'],
            $stats['edges'],
            $execution_time
        ));
        
        return $stats;
    }
    
    /**
     * Add post nodes to graph
     */
    private function add_post_nodes($limit) {
        $posts = get_posts([
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
        ]);
        
        foreach ($posts as $post) {
            $this->add_node(self::NODE_POST, $post->ID, [
                'title' => $post->post_title,
                'content' => $post->post_content,
                'date' => $post->post_date,
                'word_count' => str_word_count(strip_tags($post->post_content)),
            ]);
        }
        
        error_log(sprintf('[DODO Graph] Added %d post nodes', count($posts)));
    }
    
    /**
     * Add category nodes
     */
    private function add_category_nodes() {
        $categories = get_categories(['hide_empty' => true]);
        
        foreach ($categories as $category) {
            $this->add_node(self::NODE_CATEGORY, $category->term_id, [
                'name' => $category->name,
                'slug' => $category->slug,
                'count' => $category->count,
            ]);
        }
        
        error_log(sprintf('[DODO Graph] Added %d category nodes', count($categories)));
    }
    
    /**
     * Add entity nodes from posts
     */
    private function add_entity_nodes() {
        $all_entities = [];
        
        foreach ($this->nodes as $node) {
            if ($node['type'] === self::NODE_POST) {
                $entities = $this->semantic_engine->extract_entities($node['data']['content']);
                
                foreach ($entities as $type => $entity_list) {
                    foreach ($entity_list as $entity) {
                        $entity_key = md5($entity);
                        
                        if (!isset($all_entities[$entity_key])) {
                            $all_entities[$entity_key] = [
                                'name' => $entity,
                                'type' => $type,
                                'posts' => [],
                            ];
                        }
                        
                        $all_entities[$entity_key]['posts'][] = $node['id'];
                    }
                }
            }
        }
        
        // Add entity nodes
        foreach ($all_entities as $key => $entity_data) {
            $this->add_node(self::NODE_ENTITY, $key, $entity_data);
        }
        
        error_log(sprintf('[DODO Graph] Added %d entity nodes', count($all_entities)));
    }
    
    /**
     * Build semantic relationship edges
     */
    private function build_semantic_edges($threshold) {
        $post_nodes = array_filter($this->nodes, function($node) {
            return $node['type'] === self::NODE_POST;
        });
        
        $edge_count = 0;
        
        foreach ($post_nodes as $node1) {
            foreach ($post_nodes as $node2) {
                if ($node1['id'] >= $node2['id']) {
                    continue; // Avoid duplicates and self-loops
                }
                
                $similarity = $this->semantic_engine->calculate_similarity(
                    $node1['data']['content'],
                    $node2['data']['content']
                );
                
                if ($similarity >= $threshold) {
                    $this->add_edge(
                        self::EDGE_SEMANTIC,
                        $node1['id'],
                        $node2['id'],
                        ['similarity' => $similarity]
                    );
                    $edge_count++;
                }
            }
        }
        
        error_log(sprintf('[DODO Graph] Added %d semantic edges', $edge_count));
    }
    
    /**
     * Build internal link edges
     */
    private function build_link_edges() {
        $edge_count = 0;
        
        foreach ($this->nodes as $node) {
            if ($node['type'] !== self::NODE_POST) {
                continue;
            }
            
            $content = $node['data']['content'];
            
            // Extract internal links
            preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>/i', $content, $matches);
            
            foreach ($matches[1] as $url) {
                $post_id = url_to_postid($url);
                
                if ($post_id > 0 && $this->node_exists(self::NODE_POST, $post_id)) {
                    $this->add_edge(
                        self::EDGE_LINK,
                        $node['id'],
                        $post_id,
                        ['url' => $url]
                    );
                    $edge_count++;
                }
            }
        }
        
        error_log(sprintf('[DODO Graph] Added %d link edges', $edge_count));
    }
    
    /**
     * Build category membership edges
     */
    private function build_category_edges() {
        $edge_count = 0;
        
        foreach ($this->nodes as $node) {
            if ($node['type'] !== self::NODE_POST) {
                continue;
            }
            
            $categories = wp_get_post_categories($node['id']);
            
            foreach ($categories as $cat_id) {
                if ($this->node_exists(self::NODE_CATEGORY, $cat_id)) {
                    $this->add_edge(
                        self::EDGE_BELONGS,
                        $node['id'],
                        $cat_id,
                        []
                    );
                    $edge_count++;
                }
            }
        }
        
        error_log(sprintf('[DODO Graph] Added %d category edges', $edge_count));
    }
    
    /**
     * Detect orphan nodes
     *
     * @return array Orphan nodes
     */
    public function detect_orphans() {
        $orphans = [];
        
        foreach ($this->nodes as $node) {
            if ($node['type'] !== self::NODE_POST) {
                continue;
            }
            
            // Check if node has any edges
            $has_edges = false;
            
            foreach ($this->edges as $edge) {
                if ($edge['from'] === $node['id'] || $edge['to'] === $node['id']) {
                    $has_edges = true;
                    break;
                }
            }
            
            if (!$has_edges) {
                $orphans[] = [
                    'post_id' => $node['id'],
                    'title' => $node['data']['title'],
                    'reason' => 'No semantic or link connections',
                ];
            }
        }
        
        error_log(sprintf('[DODO Graph] Detected %d orphan nodes', count($orphans)));
        
        return $orphans;
    }
    
    /**
     * Calculate authority flow
     *
     * @param int $post_id Post ID
     * @return float Authority score
     */
    public function calculate_authority($post_id) {
        if (!$this->node_exists(self::NODE_POST, $post_id)) {
            return 0;
        }
        
        $score = 0;
        
        // Incoming links
        $incoming = $this->get_incoming_edges($post_id, self::EDGE_LINK);
        $score += count($incoming) * 10;
        
        // Semantic connections
        $semantic = $this->get_edges_by_node($post_id, self::EDGE_SEMANTIC);
        $score += count($semantic) * 5;
        
        // Category membership
        $categories = $this->get_outgoing_edges($post_id, self::EDGE_BELONGS);
        $score += count($categories) * 3;
        
        // Entity richness
        $entities = $this->get_connected_nodes($post_id, self::NODE_ENTITY);
        $score += count($entities) * 2;
        
        return min(100, $score);
    }
    
    /**
     * Find weak clusters
     *
     * @return array Weak clusters
     */
    public function find_weak_clusters() {
        // Group posts by semantic similarity
        $post_nodes = array_filter($this->nodes, function($node) {
            return $node['type'] === self::NODE_POST;
        });
        
        $post_ids = array_column($post_nodes, 'id');
        $clusters = $this->semantic_engine->cluster_posts($post_ids, 0.6);
        
        $weak_clusters = [];
        
        foreach ($clusters as $cluster) {
            if (count($cluster) >= 2 && count($cluster) <= 3) {
                $weak_clusters[] = [
                    'posts' => $cluster,
                    'size' => count($cluster),
                    'reason' => 'Small cluster - needs supporting content',
                ];
            }
        }
        
        return $weak_clusters;
    }
    
    /**
     * Add node to graph
     */
    private function add_node($type, $id, $data) {
        $this->nodes[] = [
            'type' => $type,
            'id' => $id,
            'data' => $data,
        ];
    }
    
    /**
     * Add edge to graph
     */
    private function add_edge($type, $from, $to, $data) {
        $this->edges[] = [
            'type' => $type,
            'from' => $from,
            'to' => $to,
            'data' => $data,
        ];
    }
    
    /**
     * Check if node exists
     */
    private function node_exists($type, $id) {
        foreach ($this->nodes as $node) {
            if ($node['type'] === $type && $node['id'] === $id) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Get incoming edges for node
     */
    private function get_incoming_edges($node_id, $edge_type = null) {
        return array_filter($this->edges, function($edge) use ($node_id, $edge_type) {
            $match = $edge['to'] === $node_id;
            if ($edge_type) {
                $match = $match && $edge['type'] === $edge_type;
            }
            return $match;
        });
    }
    
    /**
     * Get outgoing edges for node
     */
    private function get_outgoing_edges($node_id, $edge_type = null) {
        return array_filter($this->edges, function($edge) use ($node_id, $edge_type) {
            $match = $edge['from'] === $node_id;
            if ($edge_type) {
                $match = $match && $edge['type'] === $edge_type;
            }
            return $match;
        });
    }
    
    /**
     * Get all edges for node
     */
    private function get_edges_by_node($node_id, $edge_type = null) {
        return array_filter($this->edges, function($edge) use ($node_id, $edge_type) {
            $match = $edge['from'] === $node_id || $edge['to'] === $node_id;
            if ($edge_type) {
                $match = $match && $edge['type'] === $edge_type;
            }
            return $match;
        });
    }
    
    /**
     * Get connected nodes
     */
    private function get_connected_nodes($node_id, $node_type = null) {
        $connected_ids = [];
        
        foreach ($this->edges as $edge) {
            if ($edge['from'] === $node_id) {
                $connected_ids[] = $edge['to'];
            } elseif ($edge['to'] === $node_id) {
                $connected_ids[] = $edge['from'];
            }
        }
        
        $connected_nodes = array_filter($this->nodes, function($node) use ($connected_ids, $node_type) {
            $match = in_array($node['id'], $connected_ids);
            if ($node_type) {
                $match = $match && $node['type'] === $node_type;
            }
            return $match;
        });
        
        return $connected_nodes;
    }
    
    /**
     * Export graph for visualization
     *
     * @return array Graph data
     */
    public function export_graph() {
        return [
            'nodes' => $this->nodes,
            'edges' => $this->edges,
        ];
    }
}
