<?php
/**
 * Learning System Cron Manager
 * 
 * Activates and manages all learning system cron jobs
 * Final Completion Phase - Sprint E Activation
 * 
 * @package DODO_AI_SEO
 * @since 3.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Learning_Cron {
    
    /**
     * Constructor - Register hooks
     */
    public function __construct() {
        // Get plugin file constant safely
        $plugin_file = defined('DODO_PLUGIN_FILE') ? DODO_PLUGIN_FILE : null;
        
        // Activation/Deactivation hooks (only if constant exists)
        if ($plugin_file) {
            register_activation_hook($plugin_file, array($this, 'activate_crons'));
            register_deactivation_hook($plugin_file, array($this, 'deactivate_crons'));
        } else {
            error_log('[DODO][Learning Cron] WARNING: DODO_PLUGIN_FILE constant not defined, activation hooks skipped');
        }
        
        // Register cron actions
        add_action('dodo_daily_tracking', array($this, 'run_daily_tracking'));
        add_action('dodo_weekly_learning', array($this, 'run_weekly_learning'));
        add_action('dodo_pattern_analysis', array($this, 'run_pattern_analysis'));
        add_action('dodo_refresh_scan', array($this, 'run_refresh_scan'));
        add_action('dodo_rank_tracking', array($this, 'run_rank_tracking'));
        add_action('dodo_geo_tracking', array($this, 'run_geo_tracking'));
        
        // Admin action to manually trigger
        add_action('admin_init', array($this, 'maybe_activate_crons'));
    }
    
    /**
     * Activate all learning crons
     */
    public function activate_crons() {
        error_log('[DODO][Learning Cron] === ACTIVATING ALL CRONS ===');
        
        // Daily tracking - every day at 2 AM
        if (!wp_next_scheduled('dodo_daily_tracking')) {
            $timestamp = strtotime('tomorrow 2:00 AM');
            wp_schedule_event($timestamp, 'daily', 'dodo_daily_tracking');
            error_log('[DODO][Learning Cron] Scheduled: dodo_daily_tracking');
        }
        
        // Weekly learning - every Monday at 3 AM
        if (!wp_next_scheduled('dodo_weekly_learning')) {
            $timestamp = strtotime('next Monday 3:00 AM');
            wp_schedule_event($timestamp, 'weekly', 'dodo_weekly_learning');
            error_log('[DODO][Learning Cron] Scheduled: dodo_weekly_learning');
        }
        
        // Pattern analysis - every Sunday at 4 AM
        if (!wp_next_scheduled('dodo_pattern_analysis')) {
            $timestamp = strtotime('next Sunday 4:00 AM');
            wp_schedule_event($timestamp, 'weekly', 'dodo_pattern_analysis');
            error_log('[DODO][Learning Cron] Scheduled: dodo_pattern_analysis');
        }
        
        // Refresh scan - every day at 5 AM
        if (!wp_next_scheduled('dodo_refresh_scan')) {
            $timestamp = strtotime('tomorrow 5:00 AM');
            wp_schedule_event($timestamp, 'daily', 'dodo_refresh_scan');
            error_log('[DODO][Learning Cron] Scheduled: dodo_refresh_scan');
        }
        
        // Rank tracking - every 12 hours
        if (!wp_next_scheduled('dodo_rank_tracking')) {
            wp_schedule_event(time(), 'twicedaily', 'dodo_rank_tracking');
            error_log('[DODO][Learning Cron] Scheduled: dodo_rank_tracking');
        }
        
        // GEO tracking - every day at 6 AM
        if (!wp_next_scheduled('dodo_geo_tracking')) {
            $timestamp = strtotime('tomorrow 6:00 AM');
            wp_schedule_event($timestamp, 'daily', 'dodo_geo_tracking');
            error_log('[DODO][Learning Cron] Scheduled: dodo_geo_tracking');
        }
        
        // Mark as activated
        update_option('dodo_learning_crons_activated', true);
        update_option('dodo_learning_crons_activated_at', current_time('mysql'));
        
        error_log('[DODO][Learning Cron] === ALL CRONS ACTIVATED ===');
    }
    
    /**
     * Deactivate all learning crons
     */
    public function deactivate_crons() {
        error_log('[DODO][Learning Cron] === DEACTIVATING ALL CRONS ===');
        
        wp_clear_scheduled_hook('dodo_daily_tracking');
        wp_clear_scheduled_hook('dodo_weekly_learning');
        wp_clear_scheduled_hook('dodo_pattern_analysis');
        wp_clear_scheduled_hook('dodo_refresh_scan');
        wp_clear_scheduled_hook('dodo_rank_tracking');
        wp_clear_scheduled_hook('dodo_geo_tracking');
        
        delete_option('dodo_learning_crons_activated');
        
        error_log('[DODO][Learning Cron] === ALL CRONS DEACTIVATED ===');
    }
    
    /**
     * Maybe activate crons if not already activated
     */
    public function maybe_activate_crons() {
        if (!get_option('dodo_learning_crons_activated')) {
            $this->activate_crons();
        }
    }
    
    /**
     * Run daily tracking
     */
    public function run_daily_tracking() {
        error_log('[DODO][Learning Cron] === DAILY TRACKING START ===');
        
        try {
            // Post-publish tracking
            if (class_exists('DODO_Post_Publish_Tracker')) {
                $tracker = new DODO_Post_Publish_Tracker();
                $tracker->check_and_track_posts();
            }
            
            // Content performance tracking
            if (class_exists('DODO_Content_Performance_Engine')) {
                $performance = new DODO_Content_Performance_Engine();
                
                // Track all published posts
                $posts = get_posts(array(
                    'post_type' => 'post',
                    'post_status' => 'publish',
                    'posts_per_page' => 50,
                    'orderby' => 'modified',
                    'order' => 'DESC',
                ));
                
                foreach ($posts as $post) {
                    $performance->track_content_performance($post->ID, array());
                }
            }
            
            error_log('[DODO][Learning Cron] === DAILY TRACKING END ===');
            
        } catch (Throwable $e) {
            error_log('[DODO][Learning Cron] Daily tracking error: ' . $e->getMessage());
        }
    }
    
    /**
     * Run weekly learning
     */
    public function run_weekly_learning() {
        error_log('[DODO][Learning Cron] === WEEKLY LEARNING START ===');
        
        try {
            // Self-learning priority
            if (class_exists('DODO_Self_Learning_Priority')) {
                $learning = new DODO_Self_Learning_Priority();
                $learning->run_automatic_learning();
            }
            
            // CTR learning
            if (class_exists('DODO_CTR_Learning_Engine')) {
                $ctr_learning = new DODO_CTR_Learning_Engine();
                $ctr_learning->analyze_ctr_patterns();
            }
            
            error_log('[DODO][Learning Cron] === WEEKLY LEARNING END ===');
            
        } catch (Throwable $e) {
            error_log('[DODO][Learning Cron] Weekly learning error: ' . $e->getMessage());
        }
    }
    
    /**
     * Run pattern analysis
     */
    public function run_pattern_analysis() {
        error_log('[DODO][Learning Cron] === PATTERN ANALYSIS START ===');
        
        try {
            // Winner pattern detection
            if (class_exists('DODO_Winner_Pattern_Detector')) {
                $detector = new DODO_Winner_Pattern_Detector();
                $detector->analyze_winning_patterns();
            }
            
            // Generate insights
            if (class_exists('DODO_Insight_Engine')) {
                $insights = new DODO_Insight_Engine();
                $insights->generate_all_insights();
            }
            
            error_log('[DODO][Learning Cron] === PATTERN ANALYSIS END ===');
            
        } catch (Throwable $e) {
            error_log('[DODO][Learning Cron] Pattern analysis error: ' . $e->getMessage());
        }
    }
    
    /**
     * Run refresh scan
     */
    public function run_refresh_scan() {
        error_log('[DODO][Learning Cron] === REFRESH SCAN START ===');
        
        try {
            // Refresh engine
            if (class_exists('DODO_Refresh_Engine')) {
                $refresh = new DODO_Refresh_Engine();
                $candidates = $refresh->detect_refresh_candidates(20);
                
                // Mark candidates
                foreach ($candidates as $candidate) {
                    $refresh->mark_needs_refresh($candidate['post_id'], array(
                        'score' => $candidate['refresh_score'],
                        'priority' => $candidate['priority'],
                        'reasons' => $candidate['reasons'],
                    ));
                }
            }
            
            // Semantic audit
            if (class_exists('DODO_Semantic_Audit_Engine')) {
                $audit = new DODO_Semantic_Audit_Engine();
                
                // Audit recent posts
                $posts = get_posts(array(
                    'post_type' => 'post',
                    'post_status' => 'publish',
                    'posts_per_page' => 20,
                    'orderby' => 'modified',
                    'order' => 'DESC',
                ));
                
                foreach ($posts as $post) {
                    $audit->audit_semantic_coverage($post->ID);
                }
            }
            
            error_log('[DODO][Learning Cron] === REFRESH SCAN END ===');
            
        } catch (Throwable $e) {
            error_log('[DODO][Learning Cron] Refresh scan error: ' . $e->getMessage());
        }
    }
    
    /**
     * Run rank tracking
     */
    public function run_rank_tracking() {
        error_log('[DODO][Learning Cron] === RANK TRACKING START ===');
        
        try {
            if (class_exists('DODO_Rank_Tracker_Engine')) {
                $tracker = new DODO_Rank_Tracker_Engine();
                
                // Get statistics (this will track keywords)
                $stats = $tracker->get_statistics();
                
                error_log('[DODO][Learning Cron] Rank tracking: ' . json_encode($stats));
            }
            
            error_log('[DODO][Learning Cron] === RANK TRACKING END ===');
            
        } catch (Throwable $e) {
            error_log('[DODO][Learning Cron] Rank tracking error: ' . $e->getMessage());
        }
    }
    
    /**
     * Run GEO tracking
     */
    public function run_geo_tracking() {
        error_log('[DODO][Learning Cron] === GEO TRACKING START ===');
        
        try {
            if (class_exists('DODO_GEO_Tracker_Engine')) {
                $tracker = new DODO_GEO_Tracker_Engine();
                
                // Track recent posts
                $posts = get_posts(array(
                    'post_type' => 'post',
                    'post_status' => 'publish',
                    'posts_per_page' => 30,
                    'orderby' => 'modified',
                    'order' => 'DESC',
                ));
                
                foreach ($posts as $post) {
                    $tracker->track_geo_visibility($post->ID);
                }
            }
            
            error_log('[DODO][Learning Cron] === GEO TRACKING END ===');
            
        } catch (Throwable $e) {
            error_log('[DODO][Learning Cron] GEO tracking error: ' . $e->getMessage());
        }
    }
    
    /**
     * Get cron status
     */
    public function get_cron_status() {
        $status = array(
            'activated' => get_option('dodo_learning_crons_activated', false),
            'activated_at' => get_option('dodo_learning_crons_activated_at', ''),
            'crons' => array(),
        );
        
        $cron_hooks = array(
            'dodo_daily_tracking',
            'dodo_weekly_learning',
            'dodo_pattern_analysis',
            'dodo_refresh_scan',
            'dodo_rank_tracking',
            'dodo_geo_tracking',
        );
        
        foreach ($cron_hooks as $hook) {
            $next_run = wp_next_scheduled($hook);
            $status['crons'][$hook] = array(
                'scheduled' => $next_run !== false,
                'next_run' => $next_run ? date('Y-m-d H:i:s', $next_run) : 'Not scheduled',
            );
        }
        
        return $status;
    }
    
    /**
     * Manual trigger for testing
     */
    public function manual_trigger($hook) {
        error_log('[DODO][Learning Cron] Manual trigger: ' . $hook);
        
        switch ($hook) {
            case 'daily_tracking':
                $this->run_daily_tracking();
                break;
            case 'weekly_learning':
                $this->run_weekly_learning();
                break;
            case 'pattern_analysis':
                $this->run_pattern_analysis();
                break;
            case 'refresh_scan':
                $this->run_refresh_scan();
                break;
            case 'rank_tracking':
                $this->run_rank_tracking();
                break;
            case 'geo_tracking':
                $this->run_geo_tracking();
                break;
        }
    }
}

// Initialize
new DODO_Learning_Cron();
