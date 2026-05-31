<?php
/**
 * DODO AI SEO Uninstall Script
 * 
 * Fired when the plugin is uninstalled.
 * Cleans up all plugin data from the database.
 * 
 * @package DODO_AI_SEO
 * @version 3.0.0
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// Check if user wants to keep data (option can be set in settings)
$keep_data = get_option('dodo_keep_data_on_uninstall', false);

if ($keep_data) {
    // User wants to keep data - only remove options
    delete_option('dodo_ai_seo_settings');
    delete_option('dodo_ai_seo_version');
    delete_option('dodo_db_version');
    delete_option('dodo_keep_data_on_uninstall');
    
    // Clear scheduled cron jobs
    wp_clear_scheduled_hook('dodo_process_job_queue');
    wp_clear_scheduled_hook('dodo_cleanup_old_jobs');
    wp_clear_scheduled_hook('dodo_analytics_snapshot');
    wp_clear_scheduled_hook('dodo_process_publishing_pipeline');
    wp_clear_scheduled_hook('dodo_learning_cron');
    
    exit;
}

// Full cleanup - delete all data
delete_option('dodo_ai_seo_settings');
delete_option('dodo_ai_seo_version');
delete_option('dodo_db_version');
delete_option('dodo_keep_data_on_uninstall');
delete_option('dodo_onboarding_completed');
delete_option('dodo_learning_mode');
delete_option('dodo_environment');

// Delete all transients
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_dodo_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_dodo_%'");

// Drop all plugin tables (COMPREHENSIVE LIST)
$tables = array(
    // Core tables
    $wpdb->prefix . 'dodo_keyword_opportunities',
    $wpdb->prefix . 'dodo_ai_revisions',
    $wpdb->prefix . 'dodo_content_audit',
    $wpdb->prefix . 'dodo_ai_usage_logs',
    $wpdb->prefix . 'dodo_score_history',
    $wpdb->prefix . 'dodo_analytics_history',
    $wpdb->prefix . 'dodo_job_queue',
    
    // Phase 5+ tables
    $wpdb->prefix . 'dodo_performance_cache',
    $wpdb->prefix . 'dodo_telemetry',
    $wpdb->prefix . 'dodo_feedback_events',
    $wpdb->prefix . 'dodo_learning_events',
    $wpdb->prefix . 'dodo_queue_jobs',
    $wpdb->prefix . 'dodo_impact_tracking',
    $wpdb->prefix . 'dodo_strategy_snapshots',
    $wpdb->prefix . 'dodo_ai_visibility',
    $wpdb->prefix . 'dodo_ranking_correlation',
    
    // Legacy tables (if any)
    $wpdb->prefix . 'dodo_generations',
    $wpdb->prefix . 'dodo_cache',
);

foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS {$table}");
}

// Delete all post meta created by the plugin
$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE 'dodo_%'");
$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_dodo_%'");

// Clear scheduled cron jobs
wp_clear_scheduled_hook('dodo_process_job_queue');
wp_clear_scheduled_hook('dodo_cleanup_old_jobs');
wp_clear_scheduled_hook('dodo_analytics_snapshot');
wp_clear_scheduled_hook('dodo_process_publishing_pipeline');
wp_clear_scheduled_hook('dodo_learning_cron');
wp_clear_scheduled_hook('dodo_continuous_optimization');

// Delete user meta
$wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'dodo_%'");
$wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE '_dodo_%'");

// Optional: Delete all posts created by the plugin (commented out for safety)
// Uncomment if you want to delete all AI-generated posts on uninstall
/*
$wpdb->query("DELETE FROM {$wpdb->posts} WHERE post_type = 'post' AND ID IN (
    SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_dodo_ai_generated' AND meta_value = '1'
)");
*/

// Clear any cached data
wp_cache_flush();

