<?php
/**
 * Plugin Name: DODO AI SEO
 * Plugin URI: https://dodoai.com
 * Description: WordPress için AI destekli, Rank Math uyumlu SEO blog yazısı oluşturucu
 * Version: 2.3.0
 * Author: DODO AI
 * Author URI: https://dodoai.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: dodo-ai-seo
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 8.0
 */

// Doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

// Plugin sabitleri
define('DODO_VERSION', '2.3.0');
define('DODO_PLUGIN_FILE', __FILE__);
define('DODO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DODO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('DODO_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Ana eklenti sınıfını yükle
 */
require_once DODO_PLUGIN_DIR . 'includes/class-dodo-core.php';

/**
 * Rate Limiter sınıfını yükle (Production Stability)
 */
require_once DODO_PLUGIN_DIR . 'includes/class-dodo-rate-limiter.php';

/**
 * Health Alerts sınıfını yükle (Production Monitoring)
 */
require_once DODO_PLUGIN_DIR . 'includes/class-dodo-health-alerts.php';

/**
 * Debug Logger sınıfını yükle (Production Debug System)
 */
require_once DODO_PLUGIN_DIR . 'includes/class-dodo-debug-logger.php';

/**
 * Generation Strategy sınıfını yükle (Advanced Settings Pipeline)
 */
require_once DODO_PLUGIN_DIR . 'includes/class-dodo-generation-strategy.php';

/**
 * Queue Manager sınıfını yükle (Job Queue System)
 */
require_once DODO_PLUGIN_DIR . 'includes/class-dodo-queue-manager.php';

/**
 * Bulk Processor sınıfını yükle (Bulk Operations System)
 */
require_once DODO_PLUGIN_DIR . 'includes/class-dodo-bulk-processor.php';

/**
 * Brain Core sınıfını yükle (AI Reasoning Engine - v2.3.0)
 */
require_once DODO_PLUGIN_DIR . 'includes/brain/class-dodo-brain-core.php';

/**
 * Utility Classes (Phase 7 - Architecture Cleanup)
 */
require_once DODO_PLUGIN_DIR . 'includes/utilities/class-dodo-text-utils.php';
require_once DODO_PLUGIN_DIR . 'includes/utilities/class-dodo-score-utils.php';
require_once DODO_PLUGIN_DIR . 'includes/utilities/class-dodo-cache-manager.php';
require_once DODO_PLUGIN_DIR . 'includes/utilities/class-dodo-security-helper.php';
require_once DODO_PLUGIN_DIR . 'includes/utilities/class-dodo-debug-panel.php';
require_once DODO_PLUGIN_DIR . 'includes/utilities/class-dodo-ux-helper.php';
require_once DODO_PLUGIN_DIR . 'includes/utilities/class-dodo-stress-tester.php';
require_once DODO_PLUGIN_DIR . 'includes/utilities/class-dodo-final-audit.php';
require_once DODO_PLUGIN_DIR . 'includes/utilities/class-dodo-live-validator.php';

/**
 * Service Classes (Phase 7 - Architecture Cleanup)
 */
require_once DODO_PLUGIN_DIR . 'includes/services/class-dodo-ajax-service.php';
require_once DODO_PLUGIN_DIR . 'includes/services/class-dodo-ai-optimizer.php';
require_once DODO_PLUGIN_DIR . 'includes/services/class-dodo-db-optimizer.php';
require_once DODO_PLUGIN_DIR . 'includes/services/class-dodo-performance-monitor.php';
require_once DODO_PLUGIN_DIR . 'includes/services/class-dodo-telemetry.php';

/**
 * Database Migrator (Phase 7 - Production Database Management)
 */
require_once DODO_PLUGIN_DIR . 'includes/class-dodo-database-migrator.php';

// Initialize services
DODO_AJAX_Service::register_actions();

/**
 * GSC Intelligence sınıfını yükle (Sprint B - Real GSC Intelligence Engine)
 */
require_once DODO_PLUGIN_DIR . 'includes/class-dodo-gsc-intelligence.php';

/**
 * Query Quality Engine sınıfını yükle (Sprint C - SEO Intelligence Engine)
 */
require_once DODO_PLUGIN_DIR . 'includes/class-dodo-query-quality-engine.php';

/**
 * Commercial Intent Engine sınıfını yükle (Sprint C - SEO Intelligence Engine)
 */
require_once DODO_PLUGIN_DIR . 'includes/class-dodo-commercial-intent-engine.php';

/**
 * SEO Problem Analyzer sınıfını yükle (Sprint C - SEO Intelligence Engine)
 */
require_once DODO_PLUGIN_DIR . 'includes/class-dodo-seo-problem-analyzer.php';

/**
 * SERP Intelligence Engine sınıfını yükle (Sprint C - SEO Intelligence Engine)
 */
require_once DODO_PLUGIN_DIR . 'includes/class-dodo-serp-intelligence-engine.php';

/**
 * Phase 4 Intelligence Engines (Sprint D - Business + GEO + Topical Domination)
 */
$phase_4_engines = array(
    'class-dodo-revenue-engine.php',
    'class-dodo-geo-engine.php',
    'class-dodo-topical-map-engine.php',
    'class-dodo-business-priority-engine.php',
);

foreach ($phase_4_engines as $engine_file) {
    $engine_path = DODO_PLUGIN_DIR . 'includes/intelligence/' . $engine_file;
    if (file_exists($engine_path)) {
        require_once $engine_path;
    } else {
        error_log('[DODO][Phase 4] Engine not found: ' . $engine_file);
    }
}

/**
 * Learning Cron Manager sınıfını yükle (Final Completion - Sprint E Activation)
 */
require_once DODO_PLUGIN_DIR . 'includes/class-dodo-learning-cron.php';

/**
 * Sprint E Intelligence Engines (Final Completion Phase)
 */
// Load all Sprint E intelligence engines
$sprint_e_engines = array(
    'class-dodo-content-performance-engine.php',
    'class-dodo-post-publish-tracker.php',
    'class-dodo-rank-tracker-engine.php',
    'class-dodo-ctr-learning-engine.php',
    'class-dodo-geo-tracker-engine.php',
    'class-dodo-refresh-engine.php',
    'class-dodo-semantic-audit-engine.php',
    'class-dodo-conversion-engine.php',
    'class-dodo-self-learning-priority.php',
    'class-dodo-winner-pattern-detector.php',
    'class-dodo-insight-engine.php',
);

foreach ($sprint_e_engines as $engine_file) {
    $engine_path = DODO_PLUGIN_DIR . 'includes/intelligence/' . $engine_file;
    if (file_exists($engine_path)) {
        require_once $engine_path;
    }
}

/**
 * WP-Cron: Queue processor
 * HARDENED: Sprint A - Cron Safe Wrapper
 */
add_action('dodo_process_queue', 'dodo_cron_process_queue');
function dodo_cron_process_queue() {
    try {
        // Check class exists
        if (!class_exists('DODO_Queue_Manager')) {
            error_log('[DODO][CRON][Queue] DODO_Queue_Manager class not found');
            return;
        }
        
        $queue = new DODO_Queue_Manager();
        $queue->process_next_job();
        
    } catch (Throwable $e) {
        // Log error but don't break cron
        if (class_exists('DODO_Error_Handler')) {
            DODO_Error_Handler::log_error_with_context('CRON', 'Queue processor failed', array(
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ));
        } else {
            error_log('[DODO][CRON][Queue] Fatal error: ' . $e->getMessage());
        }
    }
}

/**
 * WP-Cron: Stuck job recovery
 * HARDENED: Sprint A - Cron Safe Wrapper
 */
add_action('dodo_recover_stuck_jobs', 'dodo_cron_recover_stuck_jobs');
function dodo_cron_recover_stuck_jobs() {
    try {
        // Check class exists
        if (!class_exists('DODO_Queue_Manager')) {
            error_log('[DODO][CRON][Recovery] DODO_Queue_Manager class not found');
            return;
        }
        
        DODO_Queue_Manager::cron_recover_stuck_jobs();
        
    } catch (Throwable $e) {
        // Log error but don't break cron
        if (class_exists('DODO_Error_Handler')) {
            DODO_Error_Handler::log_error_with_context('CRON', 'Stuck job recovery failed', array(
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ));
        } else {
            error_log('[DODO][CRON][Recovery] Fatal error: ' . $e->getMessage());
        }
    }
}

// Schedule cron if not scheduled
if (!wp_next_scheduled('dodo_process_queue')) {
    wp_schedule_event(time(), 'every_minute', 'dodo_process_queue');
}

// Schedule stuck job recovery
if (!wp_next_scheduled('dodo_recover_stuck_jobs')) {
    wp_schedule_event(time(), 'hourly', 'dodo_recover_stuck_jobs');
}

// Add custom cron interval
add_filter('cron_schedules', 'dodo_add_cron_interval');
function dodo_add_cron_interval($schedules) {
    $schedules['every_minute'] = array(
        'interval' => 60,
        'display' => __('Every Minute', 'dodo-ai-seo')
    );
    return $schedules;
}

/**
 * Eklentiyi başlat
 */
function dodo_ai_seo_init() {
    return DODO_Core::get_instance();
}

// Eklentiyi başlat
add_action('plugins_loaded', 'dodo_ai_seo_init');

/**
 * Aktivasyon hook
 */
register_activation_hook(__FILE__, 'dodo_ai_seo_activate');
function dodo_ai_seo_activate() {
    error_log('[DODO AI SEO] Activation started');
    
    // Minimum gereksinimler kontrolü
    if (version_compare(PHP_VERSION, '8.0', '<')) {
        error_log('[DODO AI SEO] PHP version check failed: ' . PHP_VERSION);
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            __('DODO AI SEO eklentisi PHP 8.0 veya üzeri gerektirir.', 'dodo-ai-seo'),
            __('Eklenti Aktivasyon Hatası', 'dodo-ai-seo'),
            array('back_link' => true)
        );
    }
    
    error_log('[DODO AI SEO] PHP version OK: ' . PHP_VERSION);
    
    // Varsayılan ayarları oluştur
    $default_settings = array(
        'openai_api_key' => '',
        'default_tone' => 'technical',
        'default_length' => 'medium',
        'default_status' => 'draft',
        'min_internal_links' => 5,
        'max_internal_links' => 10,
    );
    
    $settings_added = add_option('dodo_ai_seo_settings', $default_settings);
    error_log('[DODO AI SEO] Settings added: ' . ($settings_added ? 'YES' : 'NO (already exists)'));
    
    // Keyword Opportunities tablosunu oluştur
    error_log('[DODO AI SEO] Creating database table...');
    dodo_ai_seo_create_table();
    
    // Run migrations (Phase 5.5)
    error_log('[DODO AI SEO] Running database migrations...');
    
    // Run new comprehensive migrator (Phase 7) - PRIMARY MIGRATION SYSTEM
    if (class_exists('DODO_Database_Migrator')) {
        DODO_Database_Migrator::run();
    }
    
    // Legacy migrations (fallback)
    require_once DODO_PLUGIN_DIR . 'includes/class-dodo-migrations.php';
    DODO_Migrations::run_migrations();
    
    // Schedule cron jobs (Phase 5.5)
    error_log('[DODO AI SEO] Scheduling cron jobs...');
    require_once DODO_PLUGIN_DIR . 'includes/class-dodo-cron-manager.php';
    DODO_Cron_Manager::schedule_crons();
    
    // AI Revisions tablosunu oluştur (NEW - Task 6)
    error_log('[DODO AI SEO] Creating AI revisions table...');
    require_once DODO_PLUGIN_DIR . 'includes/class-dodo-revision-manager.php';
    DODO_Revision_Manager::create_table();
    
    // Content Audit tablosunu oluştur
    error_log('[DODO AI SEO] Creating content audit table...');
    require_once DODO_PLUGIN_DIR . 'includes/class-dodo-content-audit.php';
    $audit_manager = new DODO_Content_Audit();
    $audit_manager->create_table();
    
    // Usage Logger tablosunu oluştur
    error_log('[DODO AI SEO] Creating usage logger table...');
    require_once DODO_PLUGIN_DIR . 'includes/class-dodo-usage-logger.php';
    $usage_logger = new DODO_Usage_Logger();
    $usage_logger->create_table();
    
    // Queue Jobs tablosunu oluştur (v2.3.0)
    error_log('[DODO AI SEO] Creating queue jobs table...');
    DODO_Queue_Manager::create_table();
    
    // Score History tablosunu oluştur (Sprint 2B - Task 7)
    error_log('[DODO AI SEO] Creating score history table...');
    // Use existing function instead of missing class
    dodo_ai_seo_create_score_history_table();
    
    // Phase 4 Tables - Semantic Intelligence & Performance Cache
    error_log('[DODO AI SEO] Creating Phase 4 tables...');
    
    // Semantic Cache table
    require_once DODO_PLUGIN_DIR . 'includes/class-dodo-semantic-engine.php';
    DODO_Semantic_Engine::create_table();
    
    // Performance Cache table
    require_once DODO_PLUGIN_DIR . 'includes/class-dodo-performance-cache.php';
    DODO_Performance_Cache::create_table();
    DODO_Performance_Cache::schedule_cleanup();
    
    error_log('[DODO AI SEO] Phase 4 tables created successfully');
    
    // Phase 5 Tables - Production Intelligence + Feedback Learning (CONSOLIDATED)
    error_log('[DODO AI SEO] Creating Phase 5 learning tables...');
    
    // Impact Tracking table
    require_once DODO_PLUGIN_DIR . 'includes/class-dodo-impact-tracker.php';
    DODO_Impact_Tracker::create_table();
    
    // Learning Events + Feedback Events tables
    require_once DODO_PLUGIN_DIR . 'includes/class-dodo-feedback-engine.php';
    DODO_Feedback_Engine::create_tables();
    
    // Ranking History table
    require_once DODO_PLUGIN_DIR . 'includes/class-dodo-ranking-correlation.php';
    DODO_Ranking_Correlation::create_table();
    
    // AI Visibility table
    require_once DODO_PLUGIN_DIR . 'includes/class-dodo-ai-visibility.php';
    DODO_AI_Visibility::create_table();
    
    // User Feedback table
    require_once DODO_PLUGIN_DIR . 'includes/class-dodo-human-feedback.php';
    DODO_Human_Feedback::create_table();
    
    // Phase 5 Governance Tables - Strategy Snapshots
    require_once DODO_PLUGIN_DIR . 'includes/class-dodo-strategy-snapshots.php';
    DODO_Strategy_Snapshots::create_table();
    
    require_once DODO_PLUGIN_DIR . 'includes/class-dodo-learning-oversight.php';
    DODO_Learning_Oversight::create_table();
    
    error_log('[DODO AI SEO] Phase 5 tables created successfully');
    
    // Initialize Learning Loop (Production-Safe: OBSERVE mode)
    error_log('[DODO AI SEO] Initializing learning loop...');
    require_once DODO_PLUGIN_DIR . 'includes/class-dodo-learning-controller.php';
    $learning_controller = new DODO_Learning_Controller();
    
    // Set to OBSERVE mode by default (safe for production)
    if (!get_option('dodo_learning_mode')) {
        $learning_controller->set_mode('observe');
        error_log('[DODO AI SEO] Learning mode set to OBSERVE (production-safe)');
    }
    
    // Set production environment
    if (!get_option('dodo_learning_environment')) {
        $learning_controller->set_environment('production');
        error_log('[DODO AI SEO] Learning environment set to PRODUCTION');
    }
    
    // Enable safety features
    if (!get_option('dodo_learning_noisy_filter')) {
        $learning_controller->set_noisy_filter(true);
        error_log('[DODO AI SEO] Noisy filter ENABLED');
    }
    
    if (!get_option('dodo_learning_adaptive_strategy')) {
        $learning_controller->set_adaptive_strategy(true);
        error_log('[DODO AI SEO] Adaptive strategy ENABLED');
    }
    
    error_log('[DODO AI SEO] Learning loop initialized successfully');
    
    // Version kaydet
    add_option('dodo_ai_seo_version', DODO_VERSION);
    error_log('[DODO AI SEO] Version saved: ' . DODO_VERSION);
    
    error_log('[DODO AI SEO] Activation completed successfully');
}

/**
 * Schedule cron jobs (called on activation)
 */
function dodo_ai_seo_schedule_cron_jobs() {
    // Analytics snapshot - daily
    if (!wp_next_scheduled('dodo_daily_analytics_snapshot')) {
        wp_schedule_event(time(), 'daily', 'dodo_daily_analytics_snapshot');
        error_log('[DODO AI SEO] Scheduled: dodo_daily_analytics_snapshot');
    }
    
    // Job queue processor - hourly
    if (!wp_next_scheduled('dodo_process_job_queue')) {
        wp_schedule_event(time(), 'hourly', 'dodo_process_job_queue');
        error_log('[DODO AI SEO] Scheduled: dodo_process_job_queue');
    }
    
    // Publishing pipeline processor - hourly (Sprint 5 - Phase 5)
    if (!wp_next_scheduled('dodo_process_publishing_pipeline')) {
        wp_schedule_event(time(), 'hourly', 'dodo_process_publishing_pipeline');
        error_log('[DODO AI SEO] Scheduled: dodo_process_publishing_pipeline');
    }
}

/**
 * Veritabanı tablosunu oluştur (aktivasyon sırasında)
 * 
 * dbDelta otomatik olarak eksik kolonları ekler, mevcut verileri bozmaz
 */
function dodo_ai_seo_create_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'dodo_keyword_opportunities';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE {$table_name} (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        keyword varchar(255) NOT NULL,
        intent varchar(50) NOT NULL,
        suggested_title text NOT NULL,
        content_type varchar(50) NOT NULL,
        score int(11) NOT NULL DEFAULT 0,
        status varchar(20) NOT NULL DEFAULT 'new',
        reason text,
        similar_content_exists tinyint(1) DEFAULT 0,
        similar_content_ids text,
        publish_mode varchar(20) DEFAULT NULL,
        scheduled_date varchar(20) DEFAULT NULL,
        scheduled_time varchar(10) DEFAULT NULL,
        retry_count int(11) NOT NULL DEFAULT 0,
        last_retry_at datetime DEFAULT NULL,
        last_error text DEFAULT NULL,
        next_retry_at datetime DEFAULT NULL,
        created_at datetime NOT NULL,
        updated_at datetime NOT NULL,
        PRIMARY KEY  (id),
        KEY keyword (keyword),
        KEY status (status),
        KEY score (score),
        KEY next_retry_at (next_retry_at)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    // Tablo oluşturuldu mu kontrol et
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
    error_log('[DODO AI SEO] Table created/updated: ' . ($table_exists ? 'YES' : 'FAILED'));
    
    if (!$table_exists) {
        error_log('[DODO AI SEO] ERROR: Failed to create table ' . $table_name);
    } else {
        // Kolonların eklendiğini kontrol et
        $columns = $wpdb->get_col("DESCRIBE {$table_name}");
        error_log('[DODO AI SEO] Table columns: ' . implode(', ', $columns));
    }
}

/**
 * Deaktivasyon hook
 */
register_deactivation_hook(__FILE__, 'dodo_ai_seo_deactivate');
function dodo_ai_seo_deactivate() {
    error_log('[DODO AI SEO] Deactivation started');
    
    // Clear scheduled cron jobs (Phase 5.5)
    require_once DODO_PLUGIN_DIR . 'includes/class-dodo-cron-manager.php';
    DODO_Cron_Manager::unschedule_crons();
    
    // Legacy cron cleanup
    wp_clear_scheduled_hook('dodo_daily_analytics_snapshot');
    wp_clear_scheduled_hook('dodo_process_job_queue');
    
    error_log('[DODO AI SEO] Cron jobs cleared');
    error_log('[DODO AI SEO] Deactivation completed');
}

/**
 * Score History tablosunu oluştur (Sprint 2B - Task 7)
 */
function dodo_ai_seo_create_score_history_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'dodo_score_history';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE {$table_name} (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        post_id bigint(20) NOT NULL,
        revision_id bigint(20) DEFAULT NULL,
        health_score int(11) NOT NULL DEFAULT 0,
        seo_score int(11) NOT NULL DEFAULT 0,
        quality_score int(11) NOT NULL DEFAULT 0,
        readability_score int(11) NOT NULL DEFAULT 0,
        semantic_score int(11) NOT NULL DEFAULT 0,
        ai_risk_score int(11) NOT NULL DEFAULT 0,
        confidence_score int(11) NOT NULL DEFAULT 0,
        improvement_type varchar(50) DEFAULT NULL,
        focus_keyword varchar(255) DEFAULT NULL,
        created_at datetime NOT NULL,
        PRIMARY KEY  (id),
        KEY post_id (post_id),
        KEY revision_id (revision_id),
        KEY created_at (created_at)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    // Tablo oluşturuldu mu kontrol et
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
    error_log('[DODO AI SEO] Score history table created: ' . ($table_exists ? 'YES' : 'FAILED'));
}

/**
 * Analytics History tablosunu oluştur (Sprint 3 - Task 5)
 */
function dodo_ai_seo_create_analytics_history_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'dodo_analytics_history';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE {$table_name} (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        post_id bigint(20) NOT NULL,
        health_score int(11) NOT NULL DEFAULT 0,
        seo_score int(11) NOT NULL DEFAULT 0,
        quality_score int(11) NOT NULL DEFAULT 0,
        readability_score int(11) NOT NULL DEFAULT 0,
        semantic_score int(11) NOT NULL DEFAULT 0,
        ai_risk_score int(11) NOT NULL DEFAULT 0,
        geo_score int(11) NOT NULL DEFAULT 0,
        entity_coverage int(11) NOT NULL DEFAULT 0,
        workflow_status varchar(50) DEFAULT 'draft',
        recorded_at date NOT NULL,
        PRIMARY KEY  (id),
        KEY post_id (post_id),
        KEY recorded_at (recorded_at),
        UNIQUE KEY post_date (post_id, recorded_at)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    // Tablo oluşturuldu mu kontrol et
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
    error_log('[DODO AI SEO] Analytics history table created: ' . ($table_exists ? 'YES' : 'FAILED'));
}
