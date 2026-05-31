<?php
/**
 * AJAX Service - Centralized AJAX handler
 * 
 * PHASE 7 - Architecture Cleanup
 * Extracted from giant DODO_Admin class
 * 
 * @package DODO_AI_SEO
 * @since 4.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (class_exists('DODO_AJAX_Service')) {
    return;
}

class DODO_AJAX_Service {
    
    /**
     * Admin instance for delegation
     */
    private static $admin = null;
    
    /**
     * Get admin instance
     */
    private static function get_admin() {
        if (self::$admin === null) {
            self::$admin = new DODO_Admin();
        }
        return self::$admin;
    }
    
    /**
     * Register all AJAX actions
     * PHASE 7: Centralized AJAX registration (moved from DODO_Admin constructor)
     */
    public static function register_actions() {
        // Blog generation
        add_action('wp_ajax_dodo_generate_blog', array(__CLASS__, 'ajax_generate_blog'));
        add_action('wp_ajax_dodo_clean_rankmath_meta', array(__CLASS__, 'ajax_clean_rankmath_meta'));
        add_action('wp_ajax_dodo_hard_clean_post', array(__CLASS__, 'ajax_hard_clean_post'));
        
        // Keyword opportunities
        add_action('wp_ajax_dodo_generate_keyword_opportunities', array(__CLASS__, 'ajax_generate_keyword_opportunities'));
        add_action('wp_ajax_dodo_update_opportunity_status', array(__CLASS__, 'ajax_update_opportunity_status'));
        add_action('wp_ajax_dodo_delete_all_opportunities', array(__CLASS__, 'ajax_delete_all_opportunities'));
        
        // Queue management
        add_action('wp_ajax_dodo_process_queue_item', array(__CLASS__, 'ajax_process_queue_item'));
        add_action('wp_ajax_dodo_retry_queue_item', array(__CLASS__, 'ajax_retry_queue_item'));
        add_action('wp_ajax_dodo_get_queue_jobs', array(__CLASS__, 'ajax_get_queue_jobs'));
        add_action('wp_ajax_dodo_retry_queue_job', array(__CLASS__, 'ajax_retry_queue_job'));
        add_action('wp_ajax_dodo_cancel_queue_job', array(__CLASS__, 'ajax_cancel_queue_job'));
        add_action('wp_ajax_dodo_process_queue_manually', array(__CLASS__, 'ajax_process_queue_manually'));
        add_action('wp_ajax_dodo_get_queue_job_detail', array(__CLASS__, 'ajax_get_queue_job_detail'));
        add_action('wp_ajax_dodo_clear_completed_jobs', array(__CLASS__, 'ajax_clear_completed_jobs'));
        
        // Publishing
        add_action('wp_ajax_dodo_publish_now', array(__CLASS__, 'ajax_publish_now'));
        add_action('wp_ajax_dodo_convert_to_draft', array(__CLASS__, 'ajax_convert_to_draft'));
        add_action('wp_ajax_dodo_update_scheduled_post', array(__CLASS__, 'ajax_update_scheduled_post'));
        add_action('wp_ajax_dodo_fix_missed_schedules', array(__CLASS__, 'ajax_fix_missed_schedules'));
        
        // Content audit
        add_action('wp_ajax_dodo_run_content_audit', array(__CLASS__, 'ajax_run_content_audit'));
        
        // Content improver
        add_action('wp_ajax_dodo_analyze_content', array(__CLASS__, 'ajax_analyze_content'));
        add_action('wp_ajax_dodo_improve_content', array(__CLASS__, 'ajax_improve_content'));
        add_action('wp_ajax_dodo_apply_improvement', array(__CLASS__, 'ajax_apply_improvement'));
        
        // Revisions
        add_action('wp_ajax_dodo_get_revisions', array(__CLASS__, 'ajax_get_revisions'));
        add_action('wp_ajax_dodo_get_revision_compare', array(__CLASS__, 'ajax_get_revision_compare'));
        add_action('wp_ajax_dodo_restore_revision', array(__CLASS__, 'ajax_restore_revision'));
        add_action('wp_ajax_dodo_delete_revision', array(__CLASS__, 'ajax_delete_revision'));
        add_action('wp_ajax_dodo_rollback_revision', array(__CLASS__, 'ajax_rollback_revision'));
        add_action('wp_ajax_dodo_save_score_history', array(__CLASS__, 'ajax_save_score_history'));
        
        // Semantic links
        add_action('wp_ajax_dodo_get_link_suggestions', array(__CLASS__, 'ajax_get_link_suggestions'));
        add_action('wp_ajax_dodo_detect_orphan_pages', array(__CLASS__, 'ajax_detect_orphan_pages'));
        add_action('wp_ajax_dodo_analyze_authority_flow', array(__CLASS__, 'ajax_analyze_authority_flow'));
        
        // Clusters & analytics
        add_action('wp_ajax_dodo_analyze_clusters', array(__CLASS__, 'ajax_analyze_clusters'));
        add_action('wp_ajax_dodo_refresh_analytics', array(__CLASS__, 'ajax_refresh_analytics'));
        
        // Onboarding
        add_action('wp_ajax_dodo_test_api_key', array(__CLASS__, 'ajax_test_api_key'));
        add_action('wp_ajax_dodo_save_onboarding_settings', array(__CLASS__, 'ajax_save_onboarding_settings'));
        add_action('wp_ajax_dodo_get_system_health', array(__CLASS__, 'ajax_get_system_health'));
        add_action('wp_ajax_dodo_complete_onboarding', array(__CLASS__, 'ajax_complete_onboarding'));
        
        // Bulk operations
        add_action('wp_ajax_dodo_get_posts_for_bulk', array(__CLASS__, 'ajax_get_posts_for_bulk'));
        add_action('wp_ajax_dodo_process_bulk_batch', array(__CLASS__, 'ajax_process_bulk_batch'));
        
        // GEO engine
        add_action('wp_ajax_dodo_analyze_geo', array(__CLASS__, 'ajax_analyze_geo'));
        add_action('wp_ajax_dodo_get_geo_score', array(__CLASS__, 'ajax_get_geo_score'));
        add_action('wp_ajax_dodo_run_geo_analysis', array(__CLASS__, 'ajax_run_geo_analysis'));
        
        // Content intelligence
        add_action('wp_ajax_dodo_analyze_site_intelligence', array(__CLASS__, 'ajax_analyze_site_intelligence'));
        add_action('wp_ajax_dodo_detect_cannibalization', array(__CLASS__, 'ajax_detect_cannibalization'));
        
        // Humanization
        add_action('wp_ajax_dodo_analyze_humanization', array(__CLASS__, 'ajax_analyze_humanization'));
        add_action('wp_ajax_dodo_humanize_content', array(__CLASS__, 'ajax_humanize_content'));
        add_action('wp_ajax_dodo_get_humanization_score', array(__CLASS__, 'ajax_get_humanization_score'));
        add_action('wp_ajax_dodo_run_humanization_analysis', array(__CLASS__, 'ajax_run_humanization_analysis'));
        add_action('wp_ajax_dodo_apply_humanization', array(__CLASS__, 'ajax_apply_humanization'));
        
        // System health
        add_action('wp_ajax_dodo_run_migration', array(__CLASS__, 'ajax_run_migration'));
        add_action('wp_ajax_dodo_repair_tables', array(__CLASS__, 'ajax_repair_tables'));
        add_action('wp_ajax_dodo_repair_database_tables', array(__CLASS__, 'ajax_repair_database_tables'));
        add_action('wp_ajax_dodo_run_migrations', array(__CLASS__, 'ajax_run_migrations'));
        
        // Learning system
        add_action('wp_ajax_dodo_get_learning_insights', array(__CLASS__, 'ajax_get_learning_insights'));
        add_action('wp_ajax_dodo_reset_learning', array(__CLASS__, 'ajax_reset_learning'));
        add_action('wp_ajax_dodo_rollback_evolution', array(__CLASS__, 'ajax_rollback_evolution'));
        add_action('wp_ajax_dodo_set_learning_mode', array(__CLASS__, 'ajax_set_learning_mode'));
        add_action('wp_ajax_dodo_set_environment', array(__CLASS__, 'ajax_set_environment'));
        add_action('wp_ajax_dodo_set_confidence_threshold', array(__CLASS__, 'ajax_set_confidence_threshold'));
        add_action('wp_ajax_dodo_set_safety_settings', array(__CLASS__, 'ajax_set_safety_settings'));
        add_action('wp_ajax_dodo_emergency_freeze', array(__CLASS__, 'ajax_emergency_freeze'));
        add_action('wp_ajax_dodo_emergency_unfreeze', array(__CLASS__, 'ajax_emergency_unfreeze'));
    }
    
    /**
     * Magic method to delegate all ajax_* calls to admin instance
     */
    public static function __callStatic($name, $arguments) {
        $admin = self::get_admin();
        
        if (method_exists($admin, $name)) {
            return call_user_func_array(array($admin, $name), $arguments);
        }
        
        error_log("[DODO AJAX] Method not found: {$name}");
        wp_send_json_error(array('message' => 'AJAX method not found'));
    }
}
