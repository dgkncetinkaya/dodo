<?php
/**
 * DODO AI SEO Uninstall Script
 * 
 * Fired when the plugin is uninstalled.
 * Cleans up all plugin data from the database.
 * 
 * @package DODO_AI_SEO
 * @version 2.0.0
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// Delete all plugin options
delete_option('dodo_ai_seo_settings');
delete_option('dodo_ai_seo_version');
delete_option('dodo_ai_seo_db_version');

// Delete all transients
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_dodo_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_dodo_%'");

// Drop all plugin tables
$tables = array(
    $wpdb->prefix . 'dodo_keyword_opportunities',
    $wpdb->prefix . 'dodo_ai_revisions',
    $wpdb->prefix . 'dodo_content_audit',
    $wpdb->prefix . 'dodo_ai_usage_logs',
    $wpdb->prefix . 'dodo_score_history',
    $wpdb->prefix . 'dodo_analytics_history',
    $wpdb->prefix . 'dodo_job_queue',
);

foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS {$table}");
}

// Delete all post meta created by the plugin
$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE 'dodo_%'");

// Clear scheduled cron jobs
wp_clear_scheduled_hook('dodo_process_job_queue');
wp_clear_scheduled_hook('dodo_cleanup_old_jobs');
wp_clear_scheduled_hook('dodo_analytics_snapshot');

// Delete user meta
$wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'dodo_%'");

// Optional: Delete all posts created by the plugin (commented out for safety)
// Uncomment if you want to delete all AI-generated posts on uninstall
/*
$wpdb->query("DELETE FROM {$wpdb->posts} WHERE post_type = 'post' AND post_author IN (
    SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'dodo_ai_generated' AND meta_value = '1'
)");
*/

// Clear any cached data
wp_cache_flush();
