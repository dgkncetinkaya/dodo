<?php
/**
 * Production Publishing Pipeline
 * 
 * Enterprise-grade content publishing workflow with scheduling,
 * review queue, content memory, and multi-site support
 * 
 * @package DODO_AI_SEO
 * @since 3.0.0 (Sprint 5 - Phase 5)
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Publishing_Pipeline {
    
    /**
     * Add post to publishing pipeline
     * 
     * @param int $post_id Post ID
     * @param array $options Pipeline options
     * @return int|WP_Error Pipeline entry ID or error
     */
    public function add_to_pipeline($post_id, $options = array()) {
        global $wpdb;
        
        $defaults = array(
            'scheduled_at' => null,
            'review_status' => 'pending',
            'priority' => 10,
            'metadata' => array(),
        );
        
        $options = wp_parse_args($options, $defaults);
        
        // Validate post
        $post = get_post($post_id);
        if (!$post) {
            return new WP_Error('invalid_post', 'Geçersiz yazı ID');
        }
        
        // Auto-schedule if not provided
        if (empty($options['scheduled_at'])) {
            $options['scheduled_at'] = $this->calculate_next_publish_time();
        }
        
        $table_name = $wpdb->prefix . 'dodo_publishing_pipeline';
        
        // Check if already in pipeline
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table_name} WHERE post_id = %d AND status IN ('pending', 'scheduled')",
            $post_id
        ));
        
        if ($existing) {
            return new WP_Error('already_in_pipeline', 'Yazı zaten pipeline\'da');
        }
        
        // Insert into pipeline
        $inserted = $wpdb->insert(
            $table_name,
            array(
                'post_id' => $post_id,
                'status' => 'scheduled',
                'scheduled_at' => $options['scheduled_at'],
                'review_status' => $options['review_status'],
                'priority' => $options['priority'],
                'metadata' => json_encode($options['metadata']),
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ),
            array('%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s')
        );
        
        if (!$inserted) {
            return new WP_Error('insert_failed', 'Pipeline\'a eklenemedi');
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Calculate next publish time based on settings
     */
    private function calculate_next_publish_time() {
        $settings = get_option('dodo_ai_seo_settings', array());
        
        $start_hour = isset($settings['publish_start_hour']) ? intval($settings['publish_start_hour']) : 10;
        $start_minute = isset($settings['publish_start_minute']) ? intval($settings['publish_start_minute']) : 0;
        $interval_hours = isset($settings['publish_time_interval']) ? intval($settings['publish_time_interval']) : 4;
        $publish_on_weekends = isset($settings['publish_on_weekends']) ? $settings['publish_on_weekends'] : false;
        
        // Get last scheduled time
        global $wpdb;
        $table_name = $wpdb->prefix . 'dodo_publishing_pipeline';
        
        $last_scheduled = $wpdb->get_var(
            "SELECT MAX(scheduled_at) FROM {$table_name} WHERE status = 'scheduled'"
        );
        
        if ($last_scheduled) {
            $next_time = strtotime($last_scheduled) + ($interval_hours * 3600);
        } else {
            // Start from today at configured time
            $next_time = strtotime('today ' . $start_hour . ':' . $start_minute . ':00');
            
            // If time has passed, start tomorrow
            if ($next_time < time()) {
                $next_time = strtotime('tomorrow ' . $start_hour . ':' . $start_minute . ':00');
            }
        }
        
        // Skip weekends if configured
        if (!$publish_on_weekends) {
            $day_of_week = date('N', $next_time);
            
            // Saturday (6) or Sunday (7)
            if ($day_of_week >= 6) {
                // Move to Monday
                $days_to_add = (8 - $day_of_week);
                $next_time = strtotime('+' . $days_to_add . ' days', $next_time);
            }
        }
        
        return date('Y-m-d H:i:s', $next_time);
    }
    
    /**
     * Process scheduled posts
     */
    public function process_scheduled() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dodo_publishing_pipeline';
        $now = current_time('mysql');
        
        // Get posts ready to publish
        $ready_posts = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} 
            WHERE status = 'scheduled' 
            AND scheduled_at <= %s 
            AND review_status = 'approved'
            ORDER BY priority DESC, scheduled_at ASC 
            LIMIT 5",
            $now
        ), ARRAY_A);
        
        $published_count = 0;
        
        foreach ($ready_posts as $pipeline_entry) {
            $result = $this->publish_post($pipeline_entry['id']);
            
            if (!is_wp_error($result)) {
                $published_count++;
            }
        }
        
        return $published_count;
    }
    
    /**
     * Publish a post from pipeline
     */
    public function publish_post($pipeline_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dodo_publishing_pipeline';
        
        // Get pipeline entry
        $entry = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE id = %d",
            $pipeline_id
        ), ARRAY_A);
        
        if (!$entry) {
            return new WP_Error('not_found', 'Pipeline entry bulunamadı');
        }
        
        $post_id = $entry['post_id'];
        
        // Update post status to publish
        $updated = wp_update_post(array(
            'ID' => $post_id,
            'post_status' => 'publish',
            'post_date' => current_time('mysql'),
            'post_date_gmt' => current_time('mysql', 1),
        ), true);
        
        if (is_wp_error($updated)) {
            // Mark as failed
            $wpdb->update(
                $table_name,
                array(
                    'status' => 'failed',
                    'updated_at' => current_time('mysql'),
                ),
                array('id' => $pipeline_id),
                array('%s', '%s'),
                array('%d')
            );
            
            return $updated;
        }
        
        // Mark as published
        $wpdb->update(
            $table_name,
            array(
                'status' => 'published',
                'published_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ),
            array('id' => $pipeline_id),
            array('%s', '%s', '%s'),
            array('%d')
        );
        
        // Learn from published content
        $this->learn_from_content($post_id);
        
        return $post_id;
    }
    
    /**
     * Get review queue
     */
    public function get_review_queue($status = 'pending') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dodo_publishing_pipeline';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT p.*, po.post_title, po.post_status 
            FROM {$table_name} p
            LEFT JOIN {$wpdb->posts} po ON p.post_id = po.ID
            WHERE p.review_status = %s 
            AND p.status = 'scheduled'
            ORDER BY p.priority DESC, p.scheduled_at ASC",
            $status
        ), ARRAY_A);
        
        return $results;
    }
    
    /**
     * Approve post for publishing
     */
    public function approve_post($pipeline_id, $reviewer_id = null) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dodo_publishing_pipeline';
        
        if (!$reviewer_id) {
            $reviewer_id = get_current_user_id();
        }
        
        $updated = $wpdb->update(
            $table_name,
            array(
                'review_status' => 'approved',
                'reviewer_id' => $reviewer_id,
                'updated_at' => current_time('mysql'),
            ),
            array('id' => $pipeline_id),
            array('%s', '%d', '%s'),
            array('%d')
        );
        
        return $updated !== false;
    }
    
    /**
     * Reject post
     */
    public function reject_post($pipeline_id, $reason = '') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dodo_publishing_pipeline';
        
        $metadata = array('rejection_reason' => $reason);
        
        $updated = $wpdb->update(
            $table_name,
            array(
                'review_status' => 'rejected',
                'status' => 'cancelled',
                'metadata' => json_encode($metadata),
                'updated_at' => current_time('mysql'),
            ),
            array('id' => $pipeline_id),
            array('%s', '%s', '%s', '%s'),
            array('%d')
        );
        
        return $updated !== false;
    }
    
    /**
     * Learn from published content (Content Memory)
     */
    private function learn_from_content($post_id) {
        $post = get_post($post_id);
        
        if (!$post) {
            return;
        }
        
        $content = $post->post_content;
        
        // Extract patterns
        $memory = $this->get_content_memory();
        
        // Learn brand tone
        $tone = $this->detect_tone($content);
        if ($tone) {
            $memory['brand_tone'][] = $tone;
        }
        
        // Learn CTA patterns
        $ctas = $this->extract_ctas($content);
        if (!empty($ctas)) {
            $memory['cta_patterns'] = array_merge($memory['cta_patterns'], $ctas);
        }
        
        // Learn preferred entities
        $entities = $this->extract_entities($content);
        if (!empty($entities)) {
            $memory['preferred_entities'] = array_merge($memory['preferred_entities'], $entities);
        }
        
        // Save memory
        $this->save_content_memory($memory);
    }
    
    /**
     * Detect content tone
     */
    private function detect_tone($content) {
        $content_lower = strtolower(strip_tags($content));
        
        // Simple tone detection
        $formal_count = substr_count($content_lower, 'furthermore') + 
                       substr_count($content_lower, 'moreover') +
                       substr_count($content_lower, 'consequently');
        
        $casual_count = substr_count($content_lower, ' you ') +
                       substr_count($content_lower, ' your ') +
                       substr_count($content_lower, "let's");
        
        if ($casual_count > $formal_count) {
            return 'casual';
        } elseif ($formal_count > $casual_count) {
            return 'formal';
        } else {
            return 'neutral';
        }
    }
    
    /**
     * Extract CTAs
     */
    private function extract_ctas($content) {
        $ctas = array();
        
        // Common CTA patterns
        $patterns = array(
            '/\b(get started|sign up|learn more|download|try free|contact us|buy now)\b/i',
        );
        
        foreach ($patterns as $pattern) {
            preg_match_all($pattern, $content, $matches);
            if (!empty($matches[0])) {
                $ctas = array_merge($ctas, $matches[0]);
            }
        }
        
        return array_unique($ctas);
    }
    
    /**
     * Extract entities
     */
    private function extract_entities($content) {
        $entities = array();
        
        // Extract capitalized words (simple entity detection)
        preg_match_all('/\b[A-Z][a-z]+(?:\s+[A-Z][a-z]+)*\b/', strip_tags($content), $matches);
        
        if (!empty($matches[0])) {
            $entities = array_unique($matches[0]);
        }
        
        return array_slice($entities, 0, 20); // Limit to 20
    }
    
    /**
     * Get content memory
     */
    public function get_content_memory() {
        $memory = get_option('dodo_content_memory', array(
            'brand_tone' => array(),
            'cta_patterns' => array(),
            'forbidden_words' => array(),
            'preferred_entities' => array(),
            'internal_link_behavior' => array(),
        ));
        
        return $memory;
    }
    
    /**
     * Save content memory
     */
    private function save_content_memory($memory) {
        // Limit array sizes
        $memory['brand_tone'] = array_slice(array_unique($memory['brand_tone']), -50);
        $memory['cta_patterns'] = array_slice(array_unique($memory['cta_patterns']), -50);
        $memory['preferred_entities'] = array_slice(array_unique($memory['preferred_entities']), -100);
        
        update_option('dodo_content_memory', $memory);
    }
    
    /**
     * Add forbidden word
     */
    public function add_forbidden_word($word) {
        $memory = $this->get_content_memory();
        $memory['forbidden_words'][] = strtolower($word);
        $memory['forbidden_words'] = array_unique($memory['forbidden_words']);
        $this->save_content_memory($memory);
    }
    
    /**
     * Remove forbidden word
     */
    public function remove_forbidden_word($word) {
        $memory = $this->get_content_memory();
        $memory['forbidden_words'] = array_diff($memory['forbidden_words'], array(strtolower($word)));
        $this->save_content_memory($memory);
    }
    
    /**
     * Get pipeline stats
     */
    public function get_stats() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dodo_publishing_pipeline';
        
        $stats = $wpdb->get_row("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) as scheduled,
                SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                SUM(CASE WHEN review_status = 'pending' THEN 1 ELSE 0 END) as pending_review,
                SUM(CASE WHEN review_status = 'approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN review_status = 'rejected' THEN 1 ELSE 0 END) as rejected
            FROM {$table_name}
        ", ARRAY_A);
        
        return $stats;
    }
    
    /**
     * Get upcoming schedule
     */
    public function get_upcoming_schedule($limit = 10) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dodo_publishing_pipeline';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT p.*, po.post_title 
            FROM {$table_name} p
            LEFT JOIN {$wpdb->posts} po ON p.post_id = po.ID
            WHERE p.status = 'scheduled'
            ORDER BY p.scheduled_at ASC
            LIMIT %d",
            $limit
        ), ARRAY_A);
        
        return $results;
    }
    
    /**
     * Reschedule post
     */
    public function reschedule_post($pipeline_id, $new_time) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dodo_publishing_pipeline';
        
        $updated = $wpdb->update(
            $table_name,
            array(
                'scheduled_at' => $new_time,
                'updated_at' => current_time('mysql'),
            ),
            array('id' => $pipeline_id),
            array('%s', '%s'),
            array('%d')
        );
        
        return $updated !== false;
    }
    
    /**
     * Cancel scheduled post
     */
    public function cancel_post($pipeline_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dodo_publishing_pipeline';
        
        $updated = $wpdb->update(
            $table_name,
            array(
                'status' => 'cancelled',
                'updated_at' => current_time('mysql'),
            ),
            array('id' => $pipeline_id),
            array('%s', '%s'),
            array('%d')
        );
        
        return $updated !== false;
    }
    
    /**
     * Create database table
     */
    public static function create_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dodo_publishing_pipeline';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'scheduled',
            scheduled_at datetime NOT NULL,
            published_at datetime DEFAULT NULL,
            review_status varchar(20) DEFAULT 'pending',
            reviewer_id bigint(20) DEFAULT NULL,
            priority int(11) NOT NULL DEFAULT 10,
            metadata longtext,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY post_id (post_id),
            KEY status (status),
            KEY scheduled_at (scheduled_at),
            KEY review_status (review_status),
            KEY priority (priority)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
