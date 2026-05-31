<?php
/**
 * Cannibalization Engine
 * 
 * Detects keyword and content overlap to prevent self-competition.
 * Recommends: create new, merge existing, or update existing.
 * 
 * @package DODO_AI_SEO
 * @subpackage Brain_Core
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Prevent duplicate class declaration
if (class_exists('DODO_Cannibalization_Engine')) {
    return;
}

class DODO_Cannibalization_Engine {
    
    /**
     * Similarity thresholds
     */
    const SLUG_SIMILARITY_THRESHOLD = 70;
    const KEYWORD_OVERLAP_THRESHOLD = 60;
    const SEMANTIC_SIMILARITY_THRESHOLD = 0.70; // 70% semantic similarity
    const ENTITY_OVERLAP_THRESHOLD = 0.60; // 60% entity overlap
    const INTENT_MATCH_WEIGHT = 25; // Intent match adds 25 points
    
    /**
     * Semantic engine
     */
    private $semantic_engine = null;
    
    /**
     * Analyze keyword for potential cannibalization
     * 
     * @param string $keyword Target keyword
     * @param string $proposed_slug Proposed slug
     * @param array $options Analysis options
     * @return array Cannibalization report with recommendation
     */
    public function analyze($keyword, $proposed_slug = '', $options = array()) {
        $start_time = microtime(true);
        
        // Validate input
        if (empty(trim($keyword))) {
            return [
                'error' => true,
                'message' => 'Anahtar kelime boş olamaz.',
                'risk_score' => 0,
                'execution_time_ms' => 0
            ];
        }
        
        try {
            error_log("BRAIN: Advanced Cannibalization Engine analyzing: {$keyword}");
            
            // Initialize semantic engine
            $this->init_semantic_engine();
            
            // Generate slug if not provided
            if (empty($proposed_slug)) {
                $proposed_slug = sanitize_title($keyword);
            }
            
            // Generate proposed content preview
            $proposed_content = $this->generate_content_preview($keyword);
            
            // Find related posts
            $related_posts = $this->find_related_posts($keyword, $proposed_slug);
            
            // Analyze overlaps (now with semantic intelligence)
            $overlap_analysis = $this->analyze_overlaps($keyword, $proposed_slug, $proposed_content, $related_posts);
            
            // Calculate cannibalization risk
            $risk_score = $this->calculate_risk_score($overlap_analysis);
            
            // Generate recommendation
            $recommendation = $this->generate_recommendation($risk_score, $overlap_analysis, $related_posts);
            
            $execution_time = round((microtime(true) - $start_time) * 1000, 2);
            
            $report = array(
                'keyword' => $keyword,
                'proposed_slug' => $proposed_slug,
                'risk_score' => $risk_score,
                'risk_level' => $this->get_risk_level($risk_score),
                'recommendation' => $recommendation['action'],
                'related_posts' => $related_posts,
                'overlap_analysis' => $overlap_analysis,
                'reasoning' => $recommendation['reasoning'],
                'metadata' => array(
                    'posts_analyzed' => count($related_posts),
                    'execution_time_ms' => $execution_time,
                    'analyzed_at' => current_time('mysql'),
                    'analysis_method' => 'semantic',
                ),
            );
            
            error_log("BRAIN: Advanced Cannibalization Engine complete - Risk: {$risk_score}, Recommendation: {$recommendation['action']}");
            
            return $report;
        } catch (Throwable $e) {
            error_log('DODO Cannibalization Engine: Analysis error: ' . $e->getMessage());
            return [
                'error' => true,
                'message' => 'Kannibalizasyon analizi sırasında hata oluştu.',
                'risk_score' => 0,
                'execution_time_ms' => round((microtime(true) - $start_time) * 1000, 2)
            ];
        }
    }
    
    /**
     * Initialize semantic engine
     */
    private function init_semantic_engine() {
        if ($this->semantic_engine === null) {
            require_once plugin_dir_path(dirname(__FILE__)) . 'class-dodo-semantic-engine.php';
            $this->semantic_engine = new DODO_Semantic_Engine();
        }
    }
    
    /**
     * Generate content preview for proposed article
     */
    private function generate_content_preview($keyword) {
        // Generate a preview of what the content would be about
        return "This article will cover {$keyword} in detail, explaining what it is, how it works, and why it matters.";
    }
    
    /**
     * Find related posts
     */
    private function find_related_posts($keyword, $proposed_slug) {
        global $wpdb;
        
        $keyword_lower = mb_strtolower($keyword, 'UTF-8');
        $keyword_words = preg_split('/\s+/u', $keyword_lower);
        
        // Build search query for title and content
        $search_terms = array();
        foreach ($keyword_words as $word) {
            if (strlen($word) > 3) { // Skip short words
                $search_terms[] = $wpdb->esc_like($word);
            }
        }
        
        if (empty($search_terms)) {
            return array();
        }
        
        // Search in post titles and slugs
        $like_clauses = array();
        foreach ($search_terms as $term) {
            $like_clauses[] = $wpdb->prepare("post_title LIKE %s OR post_name LIKE %s", "%{$term}%", "%{$term}%");
        }
        
        $where_clause = implode(' OR ', $like_clauses);
        
        $query = "
            SELECT ID, post_title, post_name, post_content, post_date
            FROM {$wpdb->posts}
            WHERE post_status = 'publish'
            AND post_type = 'post'
            AND ({$where_clause})
            ORDER BY post_date DESC
            LIMIT 20
        ";
        
        $posts = $wpdb->get_results($query);
        
        $related = array();
        foreach ($posts as $post) {
            $related[] = array(
                'id' => $post->ID,
                'title' => $post->post_title,
                'slug' => $post->post_name,
                'url' => get_permalink($post->ID),
                'date' => $post->post_date,
                'excerpt' => wp_trim_words(strip_tags($post->post_content), 30),
            );
        }
        
        return $related;
    }
    
    /**
     * Analyze overlaps (UPGRADED with semantic intelligence)
     */
    private function analyze_overlaps($keyword, $proposed_slug, $proposed_content, $related_posts) {
        $keyword_lower = mb_strtolower($keyword, 'UTF-8');
        $keyword_words = array_filter(preg_split('/\s+/u', $keyword_lower), function($word) {
            return strlen($word) > 3;
        });
        
        $overlaps = array();
        
        foreach ($related_posts as $post) {
            $post_title_lower = mb_strtolower($post['title'], 'UTF-8');
            $post_slug_lower = mb_strtolower($post['slug'], 'UTF-8');
            $post_content = get_post_field('post_content', $post['id']);
            
            // 1. Slug similarity (basic)
            $slug_similarity = $this->calculate_similarity($proposed_slug, $post_slug_lower);
            
            // 2. Keyword overlap (basic)
            $post_words = array_filter(preg_split('/\s+/u', $post_title_lower), function($word) {
                return strlen($word) > 3;
            });
            
            $common_words = array_intersect($keyword_words, $post_words);
            $keyword_overlap = empty($keyword_words) ? 0 : (count($common_words) / count($keyword_words)) * 100;
            
            // 3. SEMANTIC SIMILARITY (NEW - real semantic analysis)
            $semantic_similarity = 0;
            if ($this->semantic_engine) {
                $semantic_similarity = $this->semantic_engine->calculate_similarity(
                    $proposed_content . ' ' . $keyword,
                    $post_content
                );
            }
            
            // 4. ENTITY OVERLAP (NEW - entity-based comparison)
            $entity_overlap = 0;
            if ($this->semantic_engine) {
                $entity_overlap = $this->semantic_engine->calculate_entity_overlap(
                    $keyword,
                    $post_content
                );
            }
            
            // 5. Intent overlap (enhanced)
            $intent_overlap = $this->estimate_intent_overlap($keyword_lower, $post_title_lower);
            $intent_score = $this->intent_to_score($intent_overlap);
            
            // 6. TOPICAL DISTANCE (NEW - how close are the topics)
            $topical_distance = $this->calculate_topical_distance($keyword, $post_title_lower);
            
            // Calculate overall overlap with weighted components
            $overall_overlap = round((
                $slug_similarity * 0.15 +
                $keyword_overlap * 0.15 +
                ($semantic_similarity * 100) * 0.35 + // Most important
                ($entity_overlap * 100) * 0.20 +
                $intent_score * 0.10 +
                (100 - $topical_distance) * 0.05
            ), 2);
            
            $overlaps[] = array(
                'post_id' => $post['id'],
                'post_title' => $post['title'],
                'post_url' => get_permalink($post['id']),
                'slug_similarity' => round($slug_similarity, 2),
                'keyword_overlap' => round($keyword_overlap, 2),
                'semantic_similarity' => round($semantic_similarity * 100, 2), // NEW
                'entity_overlap' => round($entity_overlap * 100, 2), // NEW
                'intent_overlap' => $intent_overlap,
                'intent_score' => $intent_score,
                'topical_distance' => round($topical_distance, 2), // NEW
                'overall_overlap' => $overall_overlap,
                'analysis_method' => 'semantic_enhanced',
            );
        }
        
        // Sort by overall overlap
        usort($overlaps, function($a, $b) {
            return $b['overall_overlap'] - $a['overall_overlap'];
        });
        
        return $overlaps;
    }
    
    /**
     * Calculate topical distance
     */
    private function calculate_topical_distance($keyword1, $keyword2) {
        // Simple topical distance based on word overlap and semantic patterns
        $words1 = array_filter(preg_split('/\s+/u', mb_strtolower($keyword1, 'UTF-8')), function($w) {
            return strlen($w) > 3;
        });
        $words2 = array_filter(preg_split('/\s+/u', mb_strtolower($keyword2, 'UTF-8')), function($w) {
            return strlen($w) > 3;
        });
        
        if (empty($words1) || empty($words2)) {
            return 100; // Maximum distance
        }
        
        $common = count(array_intersect($words1, $words2));
        $total = count(array_unique(array_merge($words1, $words2)));
        
        $similarity = $total > 0 ? ($common / $total) * 100 : 0;
        
        return 100 - $similarity; // Convert to distance
    }
    
    /**
     * Convert intent overlap to score
     */
    private function intent_to_score($intent_overlap) {
        switch ($intent_overlap) {
            case 'high':
                return self::INTENT_MATCH_WEIGHT;
            case 'medium':
                return self::INTENT_MATCH_WEIGHT * 0.5;
            case 'low':
            default:
                return 0;
        }
    }
    
    /**
     * Calculate string similarity (Levenshtein-based)
     */
    private function calculate_similarity($str1, $str2) {
        $len1 = mb_strlen($str1);
        $len2 = mb_strlen($str2);
        
        if ($len1 == 0 || $len2 == 0) {
            return 0;
        }
        
        $distance = levenshtein(substr($str1, 0, 255), substr($str2, 0, 255));
        $max_len = max($len1, $len2);
        
        return (1 - ($distance / $max_len)) * 100;
    }
    
    /**
     * Calculate semantic overlap
     */
    private function calculate_semantic_overlap($keyword, $title, $excerpt) {
        $keyword_words = preg_split('/\s+/u', mb_strtolower($keyword, 'UTF-8'));
        $content = mb_strtolower($title . ' ' . $excerpt, 'UTF-8');
        
        $matches = 0;
        foreach ($keyword_words as $word) {
            if (strlen($word) > 3 && strpos($content, $word) !== false) {
                $matches++;
            }
        }
        
        return empty($keyword_words) ? 0 : ($matches / count($keyword_words)) * 100;
    }
    
    /**
     * Estimate intent overlap
     */
    private function estimate_intent_overlap($keyword, $title) {
        // Check for similar intent indicators
        $intent_indicators = array(
            'transactional' => array('satın al', 'fiyat', 'buy', 'price'),
            'informational' => array('nedir', 'nasıl', 'what is', 'how to'),
            'commercial' => array('en iyi', 'best', 'vs', 'review'),
        );
        
        $keyword_intent = null;
        $title_intent = null;
        
        foreach ($intent_indicators as $intent => $terms) {
            foreach ($terms as $term) {
                if (strpos($keyword, $term) !== false) {
                    $keyword_intent = $intent;
                }
                if (strpos($title, $term) !== false) {
                    $title_intent = $intent;
                }
            }
        }
        
        if ($keyword_intent && $title_intent && $keyword_intent === $title_intent) {
            return 'high';
        } elseif ($keyword_intent && $title_intent) {
            return 'medium';
        } else {
            return 'low';
        }
    }
    
    /**
     * Calculate risk score (UPGRADED with semantic weights)
     */
    private function calculate_risk_score($overlap_analysis) {
        if (empty($overlap_analysis)) {
            return 0;
        }
        
        // Get highest overlap
        $highest = $overlap_analysis[0];
        
        // Weight different factors (semantic similarity is now primary)
        $score = 0;
        $score += $highest['semantic_similarity'] * 0.40; // Semantic is most important
        $score += $highest['entity_overlap'] * 0.25; // Entity overlap is critical
        $score += $highest['slug_similarity'] * 0.15;
        $score += $highest['keyword_overlap'] * 0.15;
        $score += $highest['intent_score'] * 0.05;
        
        // Topical distance penalty (closer topics = higher risk)
        if (isset($highest['topical_distance'])) {
            $distance_penalty = (100 - $highest['topical_distance']) * 0.10;
            $score += $distance_penalty;
        }
        
        return min(100, round($score));
    }
    
    /**
     * Get risk level
     */
    private function get_risk_level($score) {
        if ($score >= 80) {
            return 'critical';
        } elseif ($score >= 60) {
            return 'high';
        } elseif ($score >= 40) {
            return 'medium';
        } elseif ($score >= 20) {
            return 'low';
        } else {
            return 'minimal';
        }
    }
    
    /**
     * Generate recommendation (UPGRADED with semantic insights)
     */
    private function generate_recommendation($risk_score, $overlap_analysis, $related_posts) {
        $reasoning = array();
        
        if ($risk_score >= 80) {
            // Critical overlap - merge or update
            $top_match = $overlap_analysis[0];
            $reasoning[] = "Critical semantic overlap detected with '{$top_match['post_title']}'";
            $reasoning[] = "Semantic similarity: {$top_match['semantic_similarity']}%";
            $reasoning[] = "Entity overlap: {$top_match['entity_overlap']}%";
            $reasoning[] = "These articles cover nearly identical topics";
            
            return array(
                'action' => 'update_existing',
                'target_post_id' => $top_match['post_id'],
                'target_post_url' => $top_match['post_url'],
                'reasoning' => $reasoning,
            );
        } elseif ($risk_score >= 60) {
            // High overlap - consider merging or differentiation
            $top_match = $overlap_analysis[0];
            $reasoning[] = "High semantic overlap with '{$top_match['post_title']}'";
            $reasoning[] = "Semantic similarity: {$top_match['semantic_similarity']}%";
            
            if ($top_match['entity_overlap'] > 60) {
                $reasoning[] = "Strong entity overlap ({$top_match['entity_overlap']}%) - topics are closely related";
                $reasoning[] = "Consider merging or creating distinct angle";
            } else {
                $reasoning[] = "Moderate entity overlap - differentiation possible";
                $reasoning[] = "Focus on unique aspects or different intent";
            }
            
            return array(
                'action' => 'merge_or_differentiate',
                'target_post_id' => $top_match['post_id'],
                'target_post_url' => $top_match['post_url'],
                'reasoning' => $reasoning,
            );
        } elseif ($risk_score >= 40) {
            // Medium overlap - create with caution
            $top_match = $overlap_analysis[0];
            $reasoning[] = "Medium semantic overlap detected";
            $reasoning[] = "Semantic similarity: {$top_match['semantic_similarity']}%";
            $reasoning[] = "Ensure content differentiation through:";
            $reasoning[] = "- Different angle or perspective";
            $reasoning[] = "- Unique entities and examples";
            $reasoning[] = "- Distinct search intent";
            
            return array(
                'action' => 'create_differentiated',
                'target_post_id' => null,
                'reasoning' => $reasoning,
            );
        } else {
            // Low/minimal overlap - safe to create
            $reasoning[] = "Low cannibalization risk (score: {$risk_score})";
            
            if (!empty($overlap_analysis)) {
                $top_match = $overlap_analysis[0];
                $reasoning[] = "Closest match: '{$top_match['post_title']}' ({$top_match['semantic_similarity']}% similar)";
                $reasoning[] = "Topics are sufficiently distinct";
            }
            
            $reasoning[] = "Safe to create new content";
            
            return array(
                'action' => 'create_new',
                'target_post_id' => null,
                'reasoning' => $reasoning,
            );
        }
    }
}
