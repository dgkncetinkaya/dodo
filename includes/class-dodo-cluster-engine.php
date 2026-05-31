<?php
/**
 * Topic Cluster Intelligence Engine
 * 
 * Detects pillar content, supporting articles, semantic clusters,
 * topical authority, and content cannibalization.
 * 
 * @package DODO_AI_SEO
 * @since 2.0.0 (Sprint 3 - Task 5)
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Cluster_Engine {
    
    /**
     * Analyze content clusters for the entire site
     * 
     * @return array Cluster analysis with pillar pages, supporting content, gaps
     */
    public function analyze_site_clusters() {
        // Get all published posts
        $posts = $this->get_all_posts();
        
        if (empty($posts)) {
            return array(
                'clusters' => array(),
                'pillar_pages' => array(),
                'orphan_content' => array(),
                'cannibalization_risks' => array(),
            );
        }
        
        // Extract topics from all posts
        $post_topics = array();
        foreach ($posts as $post) {
            $post_topics[$post->ID] = $this->extract_topics($post->post_content, $post->post_title);
        }
        
        // Build clusters
        $clusters = $this->build_clusters($posts, $post_topics);
        
        // Detect pillar pages
        $pillar_pages = $this->detect_pillar_pages($posts, $clusters);
        
        // Find orphan content
        $orphan_content = $this->find_orphan_content($posts, $clusters);
        
        // Detect cannibalization
        $cannibalization = $this->detect_cannibalization($posts, $post_topics);
        
        return array(
            'clusters' => $clusters,
            'pillar_pages' => $pillar_pages,
            'orphan_content' => $orphan_content,
            'cannibalization_risks' => $cannibalization,
            'total_clusters' => count($clusters),
            'analyzed_at' => current_time('mysql'),
        );
    }
    
    /**
     * Get cluster analysis for specific post
     */
    public function get_post_cluster_info($post_id) {
        $post = get_post($post_id);
        if (!$post) {
            return null;
        }
        
        // Get post topics
        $topics = $this->extract_topics($post->post_content, $post->post_title);
        
        // Find related posts in same cluster
        $related_posts = $this->find_cluster_members($post_id, $topics);
        
        // Calculate topical authority
        $authority_score = $this->calculate_topical_authority($post_id, $topics);
        
        // Check if pillar candidate
        $is_pillar = $this->is_pillar_candidate($post);
        
        // Detect gaps
        $gaps = $this->detect_cluster_gaps($topics, $related_posts);
        
        return array(
            'post_id' => $post_id,
            'topics' => $topics,
            'related_posts' => $related_posts,
            'authority_score' => $authority_score,
            'is_pillar' => $is_pillar,
            'cluster_size' => count($related_posts),
            'gaps' => $gaps,
        );
    }
    
    /**
     * Get all published posts
     */
    private function get_all_posts() {
        return get_posts(array(
            'post_type' => array('post', 'page'),
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
        ));
    }
    
    /**
     * Extract topics from content
     */
    private function extract_topics($content, $title = '') {
        $text = strip_tags($content . ' ' . $title);
        $text = strtolower($text);
        
        // Extract keywords
        $words = str_word_count($text, 1);
        $word_freq = array_count_values($words);
        
        // Remove stop words
        $stop_words = array('the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'from', 'as', 'is', 'was', 'are', 'were', 'been', 'be', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could', 'should', 'may', 'might', 'can', 'this', 'that', 'these', 'those');
        
        foreach ($stop_words as $stop) {
            unset($word_freq[$stop]);
        }
        
        // Get top topics
        arsort($word_freq);
        $topics = array_slice(array_keys($word_freq), 0, 10);
        
        // Categorize into topic groups
        return $this->categorize_topics($topics);
    }
    
    /**
     * Categorize topics into groups
     */
    private function categorize_topics($keywords) {
        $categories = array();
        
        // Technology
        $tech_words = array('technology', 'software', 'digital', 'online', 'web', 'app', 'code', 'data', 'ai', 'machine', 'learning', 'algorithm', 'api', 'cloud', 'database');
        if (count(array_intersect($keywords, $tech_words)) > 0) {
            $categories[] = 'technology';
        }
        
        // Business
        $business_words = array('business', 'marketing', 'sales', 'strategy', 'growth', 'revenue', 'customer', 'market', 'brand', 'company', 'startup', 'entrepreneur');
        if (count(array_intersect($keywords, $business_words)) > 0) {
            $categories[] = 'business';
        }
        
        // Content/SEO
        $content_words = array('content', 'writing', 'blog', 'article', 'post', 'seo', 'keyword', 'search', 'optimization', 'ranking');
        if (count(array_intersect($keywords, $content_words)) > 0) {
            $categories[] = 'content-seo';
        }
        
        // Design
        $design_words = array('design', 'ui', 'ux', 'interface', 'user', 'experience', 'visual', 'graphic', 'layout');
        if (count(array_intersect($keywords, $design_words)) > 0) {
            $categories[] = 'design';
        }
        
        // Development
        $dev_words = array('development', 'programming', 'coding', 'developer', 'frontend', 'backend', 'fullstack', 'javascript', 'python', 'php');
        if (count(array_intersect($keywords, $dev_words)) > 0) {
            $categories[] = 'development';
        }
        
        return empty($categories) ? array('general') : $categories;
    }
    
    /**
     * Build clusters from posts
     */
    private function build_clusters($posts, $post_topics) {
        $clusters = array();
        
        foreach ($posts as $post) {
            $topics = $post_topics[$post->ID];
            
            foreach ($topics as $topic) {
                if (!isset($clusters[$topic])) {
                    $clusters[$topic] = array(
                        'topic' => $topic,
                        'posts' => array(),
                        'total_words' => 0,
                        'authority_score' => 0,
                    );
                }
                
                $word_count = str_word_count(strip_tags($post->post_content));
                
                $clusters[$topic]['posts'][] = array(
                    'post_id' => $post->ID,
                    'post_title' => $post->post_title,
                    'post_url' => get_permalink($post->ID),
                    'word_count' => $word_count,
                    'post_date' => $post->post_date,
                );
                
                $clusters[$topic]['total_words'] += $word_count;
            }
        }
        
        // Calculate authority scores
        foreach ($clusters as $topic => $cluster) {
            $clusters[$topic]['authority_score'] = $this->calculate_cluster_authority($cluster);
            $clusters[$topic]['post_count'] = count($cluster['posts']);
        }
        
        // Sort by authority
        uasort($clusters, function($a, $b) {
            return $b['authority_score'] <=> $a['authority_score'];
        });
        
        return $clusters;
    }
    
    /**
     * Calculate cluster authority
     */
    private function calculate_cluster_authority($cluster) {
        $score = 0;
        
        // Post count factor
        $post_count = count($cluster['posts']);
        $score += min(50, $post_count * 5);
        
        // Total content depth
        $total_words = $cluster['total_words'];
        if ($total_words > 10000) {
            $score += 30;
        } elseif ($total_words > 5000) {
            $score += 20;
        } elseif ($total_words > 2000) {
            $score += 10;
        }
        
        // Recency factor
        $recent_posts = 0;
        $six_months_ago = strtotime('-6 months');
        
        foreach ($cluster['posts'] as $post) {
            if (strtotime($post['post_date']) > $six_months_ago) {
                $recent_posts++;
            }
        }
        
        $score += min(20, $recent_posts * 5);
        
        return min(100, $score);
    }
    
    /**
     * Detect pillar pages
     */
    private function detect_pillar_pages($posts, $clusters) {
        $pillars = array();
        
        foreach ($posts as $post) {
            $word_count = str_word_count(strip_tags($post->post_content));
            $is_pillar = false;
            $pillar_score = 0;
            $reasons = array();
            
            // Long-form content (2000+ words)
            if ($word_count >= 2000) {
                $pillar_score += 30;
                $reasons[] = 'Uzun form içerik (' . $word_count . ' kelime)';
                $is_pillar = true;
            }
            
            // Has many internal links
            $internal_link_count = substr_count($post->post_content, home_url());
            if ($internal_link_count >= 5) {
                $pillar_score += 20;
                $reasons[] = $internal_link_count . ' iç link';
            }
            
            // Has headings structure
            $h2_count = substr_count($post->post_content, '<h2');
            if ($h2_count >= 5) {
                $pillar_score += 15;
                $reasons[] = 'Güçlü başlık yapısı';
            }
            
            // Comprehensive (has FAQ, lists, etc)
            if (strpos($post->post_content, '<ul>') !== false || strpos($post->post_content, '<ol>') !== false) {
                $pillar_score += 10;
                $reasons[] = 'Liste içeriği';
            }
            
            // Old and established
            $age_days = (time() - strtotime($post->post_date)) / DAY_IN_SECONDS;
            if ($age_days > 180) {
                $pillar_score += 15;
                $reasons[] = 'Yerleşik içerik';
            }
            
            // High engagement (comments)
            $comment_count = wp_count_comments($post->ID)->approved;
            if ($comment_count > 10) {
                $pillar_score += 10;
                $reasons[] = $comment_count . ' yorum';
            }
            
            if ($is_pillar && $pillar_score >= 50) {
                $pillars[] = array(
                    'post_id' => $post->ID,
                    'post_title' => $post->post_title,
                    'post_url' => get_permalink($post->ID),
                    'pillar_score' => $pillar_score,
                    'word_count' => $word_count,
                    'reasons' => $reasons,
                );
            }
        }
        
        // Sort by score
        usort($pillars, function($a, $b) {
            return $b['pillar_score'] <=> $a['pillar_score'];
        });
        
        return $pillars;
    }
    
    /**
     * Find orphan content (not in any strong cluster)
     */
    private function find_orphan_content($posts, $clusters) {
        $orphans = array();
        
        foreach ($posts as $post) {
            $in_cluster = false;
            
            foreach ($clusters as $cluster) {
                foreach ($cluster['posts'] as $cluster_post) {
                    if ($cluster_post['post_id'] === $post->ID) {
                        // Check if cluster is strong enough
                        if (count($cluster['posts']) >= 3) {
                            $in_cluster = true;
                            break 2;
                        }
                    }
                }
            }
            
            if (!$in_cluster) {
                $orphans[] = array(
                    'post_id' => $post->ID,
                    'post_title' => $post->post_title,
                    'post_url' => get_permalink($post->ID),
                    'severity' => 'medium',
                );
            }
        }
        
        return $orphans;
    }
    
    /**
     * Detect content cannibalization
     */
    private function detect_cannibalization($posts, $post_topics) {
        $cannibalization = array();
        
        // Group posts by similar topics
        $topic_groups = array();
        foreach ($posts as $post) {
            $topics = $post_topics[$post->ID];
            $topic_key = implode('_', $topics);
            
            if (!isset($topic_groups[$topic_key])) {
                $topic_groups[$topic_key] = array();
            }
            
            $topic_groups[$topic_key][] = $post;
        }
        
        // Find groups with multiple posts (potential cannibalization)
        foreach ($topic_groups as $topic_key => $group_posts) {
            if (count($group_posts) >= 2) {
                // Check if they target same keywords
                $focus_keywords = array();
                foreach ($group_posts as $post) {
                    $keyword = get_post_meta($post->ID, 'rank_math_focus_keyword', true);
                    if (!empty($keyword)) {
                        $focus_keywords[] = strtolower($keyword);
                    }
                }
                
                // If same keywords, it's cannibalization
                if (count($focus_keywords) !== count(array_unique($focus_keywords))) {
                    $cannibalization[] = array(
                        'topic' => str_replace('_', ', ', $topic_key),
                        'posts' => array_map(function($post) {
                            return array(
                                'post_id' => $post->ID,
                                'post_title' => $post->post_title,
                                'post_url' => get_permalink($post->ID),
                                'focus_keyword' => get_post_meta($post->ID, 'rank_math_focus_keyword', true),
                            );
                        }, $group_posts),
                        'severity' => 'high',
                        'post_count' => count($group_posts),
                    );
                }
            }
        }
        
        return $cannibalization;
    }
    
    /**
     * Find cluster members for a post
     */
    private function find_cluster_members($post_id, $topics) {
        $members = array();
        
        $posts = $this->get_all_posts();
        
        foreach ($posts as $post) {
            if ($post->ID === $post_id) {
                continue;
            }
            
            $post_topics = $this->extract_topics($post->post_content, $post->post_title);
            
            // Calculate topic overlap
            $overlap = count(array_intersect($topics, $post_topics));
            
            if ($overlap > 0) {
                $members[] = array(
                    'post_id' => $post->ID,
                    'post_title' => $post->post_title,
                    'post_url' => get_permalink($post->ID),
                    'topic_overlap' => $overlap,
                    'relevance_score' => ($overlap / count($topics)) * 100,
                );
            }
        }
        
        // Sort by relevance
        usort($members, function($a, $b) {
            return $b['relevance_score'] <=> $a['relevance_score'];
        });
        
        return array_slice($members, 0, 10);
    }
    
    /**
     * Calculate topical authority for post
     */
    private function calculate_topical_authority($post_id, $topics) {
        $score = 50; // Base
        
        // Content depth
        $post = get_post($post_id);
        $word_count = str_word_count(strip_tags($post->post_content));
        
        if ($word_count > 2000) {
            $score += 20;
        } elseif ($word_count > 1000) {
            $score += 10;
        }
        
        // Internal links
        $internal_links = substr_count($post->post_content, home_url());
        $score += min(15, $internal_links * 3);
        
        // External authority (comments, age)
        $comment_count = wp_count_comments($post_id)->approved;
        $score += min(10, $comment_count * 2);
        
        $age_days = (time() - strtotime($post->post_date)) / DAY_IN_SECONDS;
        if ($age_days > 180) {
            $score += 5;
        }
        
        return min(100, $score);
    }
    
    /**
     * Check if post is pillar candidate
     */
    private function is_pillar_candidate($post) {
        $word_count = str_word_count(strip_tags($post->post_content));
        $internal_links = substr_count($post->post_content, home_url());
        
        return $word_count >= 2000 && $internal_links >= 5;
    }
    
    /**
     * Detect cluster gaps
     */
    private function detect_cluster_gaps($topics, $related_posts) {
        $gaps = array();
        
        // If cluster is small, suggest expansion
        if (count($related_posts) < 3) {
            $gaps[] = array(
                'type' => 'small_cluster',
                'message' => 'Küme çok küçük. Daha fazla destekleyici içerik oluşturun.',
                'severity' => 'medium',
            );
        }
        
        // If no pillar content
        $has_pillar = false;
        foreach ($related_posts as $post) {
            $post_obj = get_post($post['post_id']);
            if ($this->is_pillar_candidate($post_obj)) {
                $has_pillar = true;
                break;
            }
        }
        
        if (!$has_pillar) {
            $gaps[] = array(
                'type' => 'no_pillar',
                'message' => 'Pillar içerik eksik. Kapsamlı bir rehber oluşturun.',
                'severity' => 'high',
            );
        }
        
        return $gaps;
    }
}
