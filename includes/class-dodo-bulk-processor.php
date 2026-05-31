<?php
/**
 * Bulk Processor Class
 * 
 * Handles bulk operations on multiple posts
 * 
 * @package DODO_AI_SEO
 * @since 2.3.0
 */

// Doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

class DODO_Bulk_Processor {
    
    /**
     * Queue Manager instance
     */
    private $queue;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->queue = new DODO_Queue_Manager();
    }
    
    /**
     * Bulk analyze content
     * 
     * @param array $post_ids Post IDs
     * @return array Result
     */
    public function bulk_analyze_content($post_ids) {
        if (empty($post_ids)) {
            return new WP_Error('no_posts', 'No posts selected');
        }
        
        $added = 0;
        $skipped = 0;
        $errors = array();
        
        foreach ($post_ids as $post_id) {
            $post_id = absint($post_id);
            
            if (!$post_id || get_post_status($post_id) === false) {
                $skipped++;
                $errors[] = "Post {$post_id} not found";
                continue;
            }
            
            $job_id = $this->queue->add_job(
                DODO_Queue_Manager::TYPE_CONTENT_ANALYSIS,
                $post_id,
                array('analysis_type' => 'full')
            );
            
            if ($job_id) {
                $added++;
            } else {
                $skipped++;
                $errors[] = "Failed to queue post {$post_id}";
            }
        }
        
        error_log("DODO Bulk: Content analysis - Added: {$added}, Skipped: {$skipped}");
        
        return array(
            'success' => true,
            'added' => $added,
            'skipped' => $skipped,
            'total' => count($post_ids),
            'errors' => $errors,
            'message' => sprintf(
                '%d içerik analiz kuyruğuna eklendi, %d atlandı',
                $added,
                $skipped
            ),
        );
    }
    
    /**
     * Bulk improve content
     * 
     * @param array $post_ids Post IDs
     * @param string $improvement_type Improvement type
     * @return array Result
     */
    public function bulk_improve_content($post_ids, $improvement_type = 'general') {
        if (empty($post_ids)) {
            return new WP_Error('no_posts', 'No posts selected');
        }
        
        $valid_types = array('general', 'seo', 'readability', 'geo', 'humanization');
        if (!in_array($improvement_type, $valid_types)) {
            return new WP_Error('invalid_type', 'Invalid improvement type');
        }
        
        $added = 0;
        $skipped = 0;
        $errors = array();
        
        foreach ($post_ids as $post_id) {
            $post_id = absint($post_id);
            
            if (!$post_id || get_post_status($post_id) === false) {
                $skipped++;
                $errors[] = "Post {$post_id} not found";
                continue;
            }
            
            $job_id = $this->queue->add_job(
                DODO_Queue_Manager::TYPE_CONTENT_IMPROVEMENT,
                $post_id,
                array(
                    'improvement_type' => $improvement_type,
                    'auto_apply' => false,
                )
            );
            
            if ($job_id) {
                $added++;
            } else {
                $skipped++;
                $errors[] = "Failed to queue post {$post_id}";
            }
        }
        
        error_log("DODO Bulk: Content improvement ({$improvement_type}) - Added: {$added}, Skipped: {$skipped}");
        
        return array(
            'success' => true,
            'added' => $added,
            'skipped' => $skipped,
            'total' => count($post_ids),
            'errors' => $errors,
            'message' => sprintf(
                '%d içerik geliştirme kuyruğuna eklendi (%s), %d atlandı',
                $added,
                $improvement_type,
                $skipped
            ),
        );
    }
    
    /**
     * Bulk generate meta
     * 
     * @param array $post_ids Post IDs
     * @return array Result
     */
    public function bulk_generate_meta($post_ids) {
        if (empty($post_ids)) {
            return new WP_Error('no_posts', 'No posts selected');
        }
        
        $added = 0;
        $skipped = 0;
        $errors = array();
        
        foreach ($post_ids as $post_id) {
            $post_id = absint($post_id);
            
            if (!$post_id || get_post_status($post_id) === false) {
                $skipped++;
                $errors[] = "Post {$post_id} not found";
                continue;
            }
            
            $job_id = $this->queue->add_job(
                DODO_Queue_Manager::TYPE_META_GENERATION,
                $post_id,
                array('regenerate' => true)
            );
            
            if ($job_id) {
                $added++;
            } else {
                $skipped++;
                $errors[] = "Failed to queue post {$post_id}";
            }
        }
        
        error_log("DODO Bulk: Meta generation - Added: {$added}, Skipped: {$skipped}");
        
        return array(
            'success' => true,
            'added' => $added,
            'skipped' => $skipped,
            'total' => count($post_ids),
            'errors' => $errors,
            'message' => sprintf(
                '%d içerik meta üretim kuyruğuna eklendi, %d atlandı',
                $added,
                $skipped
            ),
        );
    }
    
    /**
     * Bulk suggest internal links
     * 
     * @param array $post_ids Post IDs
     * @return array Result
     */
    public function bulk_suggest_internal_links($post_ids) {
        if (empty($post_ids)) {
            return new WP_Error('no_posts', 'No posts selected');
        }
        
        $added = 0;
        $skipped = 0;
        $errors = array();
        
        foreach ($post_ids as $post_id) {
            $post_id = absint($post_id);
            
            if (!$post_id || get_post_status($post_id) === false) {
                $skipped++;
                $errors[] = "Post {$post_id} not found";
                continue;
            }
            
            $job_id = $this->queue->add_job(
                DODO_Queue_Manager::TYPE_INTERNAL_LINKS,
                $post_id,
                array('min_links' => 5, 'max_links' => 10)
            );
            
            if ($job_id) {
                $added++;
            } else {
                $skipped++;
                $errors[] = "Failed to queue post {$post_id}";
            }
        }
        
        error_log("DODO Bulk: Internal links - Added: {$added}, Skipped: {$skipped}");
        
        return array(
            'success' => true,
            'added' => $added,
            'skipped' => $skipped,
            'total' => count($post_ids),
            'errors' => $errors,
            'message' => sprintf(
                '%d içerik iç link önerisi kuyruğuna eklendi, %d atlandı',
                $added,
                $skipped
            ),
        );
    }
    
    /**
     * Bulk GEO analysis
     * 
     * @param array $post_ids Post IDs
     * @return array Result
     */
    public function bulk_geo_analysis($post_ids) {
        if (empty($post_ids)) {
            return new WP_Error('no_posts', 'No posts selected');
        }
        
        $added = 0;
        $skipped = 0;
        $errors = array();
        
        foreach ($post_ids as $post_id) {
            $post_id = absint($post_id);
            
            if (!$post_id || get_post_status($post_id) === false) {
                $skipped++;
                $errors[] = "Post {$post_id} not found";
                continue;
            }
            
            $job_id = $this->queue->add_job(
                DODO_Queue_Manager::TYPE_GEO_ANALYSIS,
                $post_id,
                array('analysis_depth' => 'full')
            );
            
            if ($job_id) {
                $added++;
            } else {
                $skipped++;
                $errors[] = "Failed to queue post {$post_id}";
            }
        }
        
        error_log("DODO Bulk: GEO analysis - Added: {$added}, Skipped: {$skipped}");
        
        return array(
            'success' => true,
            'added' => $added,
            'skipped' => $skipped,
            'total' => count($post_ids),
            'errors' => $errors,
            'message' => sprintf(
                '%d içerik GEO analiz kuyruğuna eklendi, %d atlandı',
                $added,
                $skipped
            ),
        );
    }
    
    /**
     * Get posts for bulk operations
     * 
     * @param array $filters Filters
     * @return array Posts
     */
    public function get_posts_for_bulk($filters = array()) {
        $args = array(
            'post_type' => 'post',
            'post_status' => array('publish', 'draft'),
            'posts_per_page' => isset($filters['limit']) ? absint($filters['limit']) : 50,
            'offset' => isset($filters['offset']) ? absint($filters['offset']) : 0,
            'orderby' => 'date',
            'order' => 'DESC',
        );
        
        // Filter by status
        if (!empty($filters['status'])) {
            $args['post_status'] = sanitize_text_field($filters['status']);
        }
        
        // Filter by category
        if (!empty($filters['category'])) {
            $args['cat'] = absint($filters['category']);
        }
        
        // Filter by date range
        if (!empty($filters['date_from']) || !empty($filters['date_to'])) {
            $args['date_query'] = array();
            
            if (!empty($filters['date_from'])) {
                $args['date_query']['after'] = sanitize_text_field($filters['date_from']);
            }
            
            if (!empty($filters['date_to'])) {
                $args['date_query']['before'] = sanitize_text_field($filters['date_to']);
            }
        }
        
        // Search
        if (!empty($filters['search'])) {
            $args['s'] = sanitize_text_field($filters['search']);
        }
        
        $query = new WP_Query($args);
        
        $posts = array();
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                
                $post_id = get_the_ID();
                $posts[] = array(
                    'id' => $post_id,
                    'title' => get_the_title(),
                    'status' => get_post_status(),
                    'date' => get_the_date('Y-m-d H:i:s'),
                    'word_count' => str_word_count(strip_tags(get_the_content())),
                    'edit_url' => get_edit_post_link($post_id, 'raw'),
                );
            }
            wp_reset_postdata();
        }
        
        return array(
            'posts' => $posts,
            'total' => $query->found_posts,
            'pages' => $query->max_num_pages,
        );
    }
}
