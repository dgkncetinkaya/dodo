<?php
/**
 * DODO AI Revision Manager
 * 
 * Manages AI content improvement revision history with rollback capability.
 * 
 * @package DODO_AI_SEO
 * @since 1.6.0
 */

// Doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

class DODO_Revision_Manager {
    
    /**
     * Database table name
     */
    private $table_name;
    
    /**
     * Maximum revisions per post
     */
    private const MAX_REVISIONS_PER_POST = 20;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'dodo_ai_revisions';
    }
    
    /**
     * Create database table
     * 
     * Called on plugin activation.
     */
    public static function create_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dodo_ai_revisions';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            post_id bigint(20) UNSIGNED NOT NULL,
            improve_type varchar(50) NOT NULL,
            section_type varchar(50) DEFAULT NULL,
            original_content longtext NOT NULL,
            improved_content longtext NOT NULL,
            diff_html longtext DEFAULT NULL,
            metadata_json longtext DEFAULT NULL,
            rewrite_necessity varchar(20) DEFAULT NULL,
            ai_confidence_score int(3) DEFAULT NULL,
            user_id bigint(20) UNSIGNED NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY post_id (post_id),
            KEY improve_type (improve_type),
            KEY created_at (created_at),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Debug log
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[DODO REVISION] Table created: ' . $table_name);
        }
    }
    
    /**
     * Save revision
     * 
     * @param array $data Revision data
     * @return int|false Revision ID or false on failure
     */
    public function save_revision($data) {
        global $wpdb;
        
        // Ensure table exists
        if (!$this->table_exists()) {
            error_log('[DODO REVISION] Table does not exist, creating...');
            self::create_table();
            
            // Verify creation
            if (!$this->table_exists()) {
                error_log('[DODO REVISION] ERROR: Failed to create table');
                return false;
            }
        }
        
        // Validate required fields
        $required = array('post_id', 'improve_type', 'original_content', 'improved_content');
        foreach ($required as $field) {
            if (empty($data[$field])) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("[DODO REVISION] Missing required field: {$field}");
                }
                return false;
            }
        }
        
        // Calculate AI confidence score
        $ai_confidence_score = $this->calculate_ai_confidence_score($data);
        
        // Prepare data
        $insert_data = array(
            'post_id' => intval($data['post_id']),
            'improve_type' => sanitize_text_field($data['improve_type']),
            'section_type' => isset($data['section_type']) ? sanitize_text_field($data['section_type']) : null,
            'original_content' => wp_kses_post($data['original_content']),
            'improved_content' => wp_kses_post($data['improved_content']),
            'diff_html' => isset($data['diff_html']) ? wp_kses_post($data['diff_html']) : null,
            'metadata_json' => isset($data['metadata']) ? wp_json_encode($data['metadata']) : null,
            'rewrite_necessity' => isset($data['rewrite_necessity']) ? sanitize_text_field($data['rewrite_necessity']) : null,
            'ai_confidence_score' => $ai_confidence_score,
            'user_id' => get_current_user_id(),
            'created_at' => current_time('mysql'),
        );
        
        // Insert
        $inserted = $wpdb->insert(
            $this->table_name,
            $insert_data,
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s')
        );
        
        if ($inserted === false) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[DODO REVISION] Insert failed: ' . $wpdb->last_error);
            }
            return false;
        }
        
        $revision_id = $wpdb->insert_id;
        
        // Debug log
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[DODO REVISION] Saved revision #{$revision_id} for post #{$data['post_id']} - Type: {$data['improve_type']}, Confidence: {$ai_confidence_score}%");
        }
        
        // Cleanup old revisions
        $this->cleanup_old_revisions($data['post_id']);
        
        return $revision_id;
    }
    
    /**
     * Calculate AI confidence score
     * 
     * Factors:
     * - Validation success
     * - Semantic preservation
     * - Surgical edit score
     * - Rewrite necessity
     * - Diff size
     * - Forbidden pattern count
     * 
     * @param array $data Revision data
     * @return int Score 0-100
     */
    private function calculate_ai_confidence_score($data) {
        $score = 100;
        $metadata = isset($data['metadata']) ? $data['metadata'] : array();
        
        // Factor 1: Validation errors (critical)
        if (isset($metadata['validation_errors']) && !empty($metadata['validation_errors'])) {
            $score -= 30; // Major penalty for validation errors
        }
        
        // Factor 2: Validation warnings
        if (isset($metadata['validation_warnings']) && !empty($metadata['validation_warnings'])) {
            $warning_count = count($metadata['validation_warnings']);
            $score -= min($warning_count * 5, 20); // Max 20 points penalty
        }
        
        // Factor 3: Semantic preservation (if LOW rewrite)
        if (isset($data['rewrite_necessity']) && $data['rewrite_necessity'] === 'low') {
            $semantic_score = isset($metadata['semantic_preservation_score']) ? intval($metadata['semantic_preservation_score']) : 100;
            if ($semantic_score < 60) {
                $score -= 20; // Poor semantic preservation
            } elseif ($semantic_score < 80) {
                $score -= 10; // Moderate semantic preservation
            }
        }
        
        // Factor 4: Surgical edit score (if LOW rewrite)
        if (isset($data['rewrite_necessity']) && $data['rewrite_necessity'] === 'low') {
            $surgical_score = isset($metadata['surgical_edit_score']) ? intval($metadata['surgical_edit_score']) : 100;
            if ($surgical_score < 80) {
                $score -= 15; // Poor surgical editing
            } elseif ($surgical_score < 90) {
                $score -= 5; // Moderate surgical editing
            }
        }
        
        // Factor 5: Excessive changes for LOW rewrite
        if (isset($data['rewrite_necessity']) && $data['rewrite_necessity'] === 'low') {
            $diff_change = isset($metadata['diff_change_percentage']) ? intval($metadata['diff_change_percentage']) : 0;
            if ($diff_change > 30) {
                $score -= 20; // Too many changes for LOW mode
            } elseif ($diff_change > 20) {
                $score -= 10; // Moderate changes for LOW mode
            }
        }
        
        // Factor 6: Synonym replacements
        if (isset($metadata['synonym_replacement_count'])) {
            $synonym_count = intval($metadata['synonym_replacement_count']);
            if ($synonym_count > 15) {
                $score -= 10; // Too many synonyms
            } elseif ($synonym_count > 10) {
                $score -= 5; // Moderate synonyms
            }
        }
        
        // Factor 7: Sentence reconstruction
        if (isset($metadata['reconstructed_sentences_count'])) {
            $reconstructed = intval($metadata['reconstructed_sentences_count']);
            if ($reconstructed > 0) {
                $score -= min($reconstructed * 5, 15); // Max 15 points penalty
            }
        }
        
        // Ensure score is within bounds
        $score = max(0, min(100, $score));
        
        return $score;
    }
    
    /**
     * Get revisions for post
     * 
     * @param int $post_id Post ID
     * @param array $args Query arguments
     * @return array Revisions
     */
    public function get_revisions($post_id, $args = array()) {
        global $wpdb;
        
        // Ensure table exists
        if (!$this->table_exists()) {
            error_log('[DODO REVISION] Table does not exist in get_revisions, creating...');
            self::create_table();
            return array(); // Return empty on first call
        }
        
        $defaults = array(
            'limit' => 20,
            'offset' => 0,
            'order' => 'DESC',
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $sql = $wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
            WHERE post_id = %d 
            ORDER BY created_at {$args['order']} 
            LIMIT %d OFFSET %d",
            $post_id,
            $args['limit'],
            $args['offset']
        );
        
        $revisions = $wpdb->get_results($sql, ARRAY_A);
        
        // Decode metadata JSON
        foreach ($revisions as &$revision) {
            if (!empty($revision['metadata_json'])) {
                $revision['metadata'] = json_decode($revision['metadata_json'], true);
            }
        }
        
        return $revisions;
    }
    
    /**
     * Get revision by ID
     * 
     * @param int $revision_id Revision ID
     * @return array|null Revision data or null
     */
    public function get_revision($revision_id) {
        global $wpdb;
        
        $sql = $wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $revision_id
        );
        
        $revision = $wpdb->get_row($sql, ARRAY_A);
        
        if ($revision && !empty($revision['metadata_json'])) {
            $revision['metadata'] = json_decode($revision['metadata_json'], true);
        }
        
        return $revision;
    }
    
    /**
     * Restore revision (rollback)
     * 
     * @param int $revision_id Revision ID
     * @return bool|WP_Error Success or error
     */
    public function restore_revision($revision_id) {
        // Get revision
        $revision = $this->get_revision($revision_id);
        
        if (!$revision) {
            return new WP_Error('revision_not_found', __('Revision not found.', 'dodo-ai-seo'));
        }
        
        // Get post
        $post = get_post($revision['post_id']);
        
        if (!$post) {
            return new WP_Error('post_not_found', __('Post not found.', 'dodo-ai-seo'));
        }
        
        // Backup current content before rollback
        $current_content = $post->post_content;
        
        // Replace improved content with original content
        $restored_content = str_replace(
            $revision['improved_content'],
            $revision['original_content'],
            $current_content
        );
        
        // Update post
        $updated = wp_update_post(array(
            'ID' => $revision['post_id'],
            'post_content' => $restored_content,
        ), true);
        
        if (is_wp_error($updated)) {
            return $updated;
        }
        
        // Create rollback revision record
        $this->save_revision(array(
            'post_id' => $revision['post_id'],
            'improve_type' => 'rollback',
            'section_type' => $revision['section_type'],
            'original_content' => $revision['improved_content'], // Current (improved) becomes original
            'improved_content' => $revision['original_content'], // Original becomes improved
            'rewrite_necessity' => 'rollback',
            'metadata' => array(
                'rollback_from_revision_id' => $revision_id,
                'rollback_reason' => 'user_initiated',
            ),
        ));
        
        // Debug log
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[DODO REVISION] Restored revision #{$revision_id} for post #{$revision['post_id']}");
        }
        
        return true;
    }
    
    /**
     * Delete revision
     * 
     * @param int $revision_id Revision ID
     * @return bool Success
     */
    public function delete_revision($revision_id) {
        global $wpdb;
        
        $deleted = $wpdb->delete(
            $this->table_name,
            array('id' => $revision_id),
            array('%d')
        );
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[DODO REVISION] Deleted revision #{$revision_id}");
        }
        
        return $deleted !== false;
    }
    
    /**
     * Cleanup old revisions
     * 
     * Keeps only the most recent MAX_REVISIONS_PER_POST revisions per post.
     * 
     * @param int $post_id Post ID
     * @return int Number of deleted revisions
     */
    private function cleanup_old_revisions($post_id) {
        global $wpdb;
        
        // Get count
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE post_id = %d",
            $post_id
        ));
        
        if ($count <= self::MAX_REVISIONS_PER_POST) {
            return 0; // No cleanup needed
        }
        
        // Get IDs to delete (oldest revisions)
        $to_delete_count = $count - self::MAX_REVISIONS_PER_POST;
        
        $ids_to_delete = $wpdb->get_col($wpdb->prepare(
            "SELECT id FROM {$this->table_name} 
            WHERE post_id = %d 
            ORDER BY created_at ASC 
            LIMIT %d",
            $post_id,
            $to_delete_count
        ));
        
        if (empty($ids_to_delete)) {
            return 0;
        }
        
        // Delete
        $ids_placeholder = implode(',', array_fill(0, count($ids_to_delete), '%d'));
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$this->table_name} WHERE id IN ($ids_placeholder)",
            ...$ids_to_delete
        ));
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[DODO REVISION] Cleaned up {$deleted} old revisions for post #{$post_id}");
        }
        
        return $deleted;
    }
    
    /**
     * Get all revisions with filters
     * 
     * @param array $args Query arguments
     * @return array Revisions
     */
    public function get_all_revisions($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'post_id' => null,
            'improve_type' => null,
            'user_id' => null,
            'date_from' => null,
            'date_to' => null,
            'search' => null,
            'limit' => 20,
            'offset' => 0,
            'order' => 'DESC',
        );
        
        $args = wp_parse_args($args, $defaults);
        
        // Build WHERE clause
        $where = array('1=1');
        $where_values = array();
        
        if ($args['post_id']) {
            $where[] = 'post_id = %d';
            $where_values[] = $args['post_id'];
        }
        
        if ($args['improve_type']) {
            $where[] = 'improve_type = %s';
            $where_values[] = $args['improve_type'];
        }
        
        if ($args['user_id']) {
            $where[] = 'user_id = %d';
            $where_values[] = $args['user_id'];
        }
        
        if ($args['date_from']) {
            $where[] = 'created_at >= %s';
            $where_values[] = $args['date_from'];
        }
        
        if ($args['date_to']) {
            $where[] = 'created_at <= %s';
            $where_values[] = $args['date_to'];
        }
        
        $where_clause = implode(' AND ', $where);
        
        // Build query
        $sql = "SELECT * FROM {$this->table_name} 
                WHERE {$where_clause} 
                ORDER BY created_at {$args['order']} 
                LIMIT %d OFFSET %d";
        
        $where_values[] = $args['limit'];
        $where_values[] = $args['offset'];
        
        if (!empty($where_values)) {
            $sql = $wpdb->prepare($sql, ...$where_values);
        }
        
        $revisions = $wpdb->get_results($sql, ARRAY_A);
        
        // Decode metadata JSON
        foreach ($revisions as &$revision) {
            if (!empty($revision['metadata_json'])) {
                $revision['metadata'] = json_decode($revision['metadata_json'], true);
            }
        }
        
        return $revisions;
    }
    
    /**
     * Get revision count
     * 
     * @param array $args Query arguments
     * @return int Count
     */
    public function get_revision_count($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'post_id' => null,
            'improve_type' => null,
            'user_id' => null,
        );
        
        $args = wp_parse_args($args, $defaults);
        
        // Build WHERE clause
        $where = array('1=1');
        $where_values = array();
        
        if ($args['post_id']) {
            $where[] = 'post_id = %d';
            $where_values[] = $args['post_id'];
        }
        
        if ($args['improve_type']) {
            $where[] = 'improve_type = %s';
            $where_values[] = $args['improve_type'];
        }
        
        if ($args['user_id']) {
            $where[] = 'user_id = %d';
            $where_values[] = $args['user_id'];
        }
        
        $where_clause = implode(' AND ', $where);
        
        $sql = "SELECT COUNT(*) FROM {$this->table_name} WHERE {$where_clause}";
        
        if (!empty($where_values)) {
            $sql = $wpdb->prepare($sql, ...$where_values);
        }
        
        return (int) $wpdb->get_var($sql);
    }
    
    /**
     * Check if table exists
     * 
     * @return bool
     */
    private function table_exists() {
        global $wpdb;
        
        $table_name = $this->table_name;
        $query = $wpdb->prepare("SHOW TABLES LIKE %s", $table_name);
        
        return $wpdb->get_var($query) === $table_name;
    }
}

