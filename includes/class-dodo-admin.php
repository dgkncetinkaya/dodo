<?php
/**
 * Admin Sınıfı
 * 
 * WordPress admin panelini yönetir
 *
 * @package DODO_AI_SEO
 * @since 1.0.0
 */

// Doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

class DODO_Admin {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('admin_footer', array($this, 'add_sidebar_footer'));
        add_action('admin_init', array($this, 'check_database_migration'));
        
        // PHASE 7 REFACTOR: AJAX actions moved to DODO_AJAX_Service
        // All ajax_* methods are now handled by the centralized AJAX service
        // See: includes/services/class-dodo-ajax-service.php
        
        // Bulk actions
        add_filter('bulk_actions-edit-post', array($this, 'add_bulk_action'));
        add_filter('handle_bulk_actions-edit-post', array($this, 'handle_bulk_action'), 10, 3);
        add_action('admin_notices', array($this, 'bulk_action_admin_notice'));
    }
    
    /**
     * Check and run database migrations if needed
     */
    public function check_database_migration() {
        if (!class_exists('DODO_Database_Migrator')) {
            return;
        }
        
        // Run migration if needed
        if (DODO_Database_Migrator::needs_migration()) {
            error_log('[DODO Admin] Database migration needed - running automatically');
            DODO_Database_Migrator::run();
        }
    }
    
    /**
     * Admin asset'leri yükle
     */
    public function enqueue_admin_assets($hook) {
        // Sadece DODO sayfalarında yükle
        if (strpos($hook, 'dodo-ai-seo') === false) {
            return;
        }
        
        // Global Admin CSS (NEW - Sprint 1A)
        wp_enqueue_style(
            'dodo-admin-global-css',
            DODO_PLUGIN_URL . 'assets/css/admin-global.css',
            array(),
            DODO_VERSION
        );
        
        // Sidebar CSS (NEW - Sprint 4.6 Phase 2)
        wp_enqueue_style(
            'dodo-admin-sidebar-css',
            DODO_PLUGIN_URL . 'assets/css/admin-sidebar.css',
            array('dodo-admin-global-css'),
            DODO_VERSION
        );
        
        // Content Improver page için özel assets
        if ($hook === 'dodo-ai-seo_page_dodo-ai-seo-content-improver') {
            // Content Improver CSS
            wp_enqueue_style(
                'dodo-content-improver-css',
                DODO_PLUGIN_URL . 'assets/css/content-improver.css',
                array(),
                DODO_VERSION
            );
            
            // Content Improver JS
            wp_enqueue_script(
                'dodo-content-improver-js',
                DODO_PLUGIN_URL . 'assets/js/content-improver.js',
                array('jquery'),
                DODO_VERSION,
                true
            );
            
            // Inline Editor JS (NEW - Sprint 1B-1)
            wp_enqueue_script(
                'dodo-inline-editor-js',
                DODO_PLUGIN_URL . 'assets/js/inline-editor.js',
                array('jquery', 'dodo-content-improver-js'),
                DODO_VERSION,
                true
            );
            
            // Loading Experience JS (NEW - Sprint 1B-2)
            wp_enqueue_script(
                'dodo-loading-experience-js',
                DODO_PLUGIN_URL . 'assets/js/loading-experience.js',
                array('jquery'),
                DODO_VERSION,
                true
            );
            
            // Keyboard Shortcuts JS (NEW - Sprint 1B-2)
            wp_enqueue_script(
                'dodo-keyboard-shortcuts-js',
                DODO_PLUGIN_URL . 'assets/js/keyboard-shortcuts.js',
                array('jquery'),
                DODO_VERSION,
                true
            );
            
            // Localize script for Content Improver
            wp_localize_script('dodo-content-improver-js', 'dodoImprover', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('dodo_improver_nonce'),
                'debug' => defined('WP_DEBUG') && WP_DEBUG, // NEW: Enable debug logging
                'strings' => array(
                    'noKeyword' => __('Lütfen önce bir odak anahtar kelime girin.', 'dodo-ai-seo'),
                    'noSections' => __('Bölüm tespit edilemedi.', 'dodo-ai-seo'),
                    'error' => __('Bir hata oluştu. Lütfen tekrar deneyin.', 'dodo-ai-seo'),
                    'success' => __('İçerik başarıyla geliştirildi!', 'dodo-ai-seo'),
                ),
            ));
        }
        
        // Content Clusters page için özel assets (Sprint 3 - Task 1)
        if ($hook === 'dodo-ai-seo_page_dodo-ai-seo-content-clusters') {
            wp_enqueue_style(
                'dodo-content-clusters-css',
                DODO_PLUGIN_URL . 'assets/css/content-clusters.css',
                array(),
                DODO_VERSION
            );
            
            wp_enqueue_script(
                'dodo-content-clusters-js',
                DODO_PLUGIN_URL . 'assets/js/content-clusters.js',
                array('jquery'),
                DODO_VERSION,
                true
            );
            
            wp_localize_script('dodo-content-clusters-js', 'dodoClusters', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('dodo_clusters_nonce'),
            ));
        }
        
        // Analytics Dashboard page için özel assets (Sprint 3 - Task 5)
        if ($hook === 'dodo-ai-seo_page_dodo-ai-seo-analytics') {
            wp_enqueue_style(
                'dodo-analytics-dashboard-css',
                DODO_PLUGIN_URL . 'assets/css/analytics-dashboard.css',
                array(),
                DODO_VERSION
            );
            
            // Chart.js for graphs
            wp_enqueue_script(
                'chartjs',
                'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
                array(),
                '4.4.0',
                true
            );
        }
        
        // Learning Center page için özel assets (Phase 5)
        if ($hook === 'dodo-ai-seo_page_dodo-ai-seo-learning-center') {
            wp_enqueue_script(
                'dodo-learning-center-js',
                DODO_PLUGIN_URL . 'assets/js/learning-center.js',
                array('jquery'),
                DODO_VERSION,
                true
            );
            
            wp_localize_script('dodo-learning-center-js', 'dodoLearning', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('dodo_learning_nonce'),
            ));
        }
        
        // Revision History page için özel assets (NEW - Task 6)
        if ($hook === 'dodo-ai-seo_page_dodo-ai-seo-revision-history') {
            // Revision History CSS
            wp_enqueue_style(
                'dodo-revision-history-css',
                DODO_PLUGIN_URL . 'assets/css/revision-history.css',
                array(),
                DODO_VERSION
            );
            
            // Revision History JS
            wp_enqueue_script(
                'dodo-revision-history-js',
                DODO_PLUGIN_URL . 'assets/js/revision-history.js',
                array('jquery'),
                DODO_VERSION,
                true
            );
            
            // Localize script for Revision History
            wp_localize_script('dodo-revision-history-js', 'dodoRevisions', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('dodo_revision_nonce'),
                'strings' => array(
                    'confirmRestore' => __('Bu revizyonu geri yüklemek istediğinizden emin misiniz? Bu işlem mevcut içeriği değiştirecektir.', 'dodo-ai-seo'),
                    'confirmDelete' => __('Bu revizyonu silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.', 'dodo-ai-seo'),
                    'restoreSuccess' => __('Revizyon başarıyla geri yüklendi!', 'dodo-ai-seo'),
                    'deleteSuccess' => __('Revizyon başarıyla silindi!', 'dodo-ai-seo'),
                    'error' => __('Bir hata oluştu. Lütfen tekrar deneyin.', 'dodo-ai-seo'),
                ),
            ));
        }
        
        // CSS
        wp_enqueue_style(
            'dodo-admin-css',
            DODO_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            DODO_VERSION
        );
        
        // JavaScript
        wp_enqueue_script(
            'dodo-admin-js',
            DODO_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            DODO_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('dodo-admin-js', 'dodoAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dodo_ajax_nonce'),
            'strings' => array(
                'error' => __('Bir hata oluştu. Lütfen tekrar deneyin.', 'dodo-ai-seo'),
                'success' => __('İşlem başarılı!', 'dodo-ai-seo'),
            ),
        ));
    }
    
    /**
     * Admin menüsünü ekle
     */
    public function add_admin_menu() {
        // Ana menü - SVG icon
        add_menu_page(
            __('DODO AI SEO', 'dodo-ai-seo'),
            __('DODO AI SEO', 'dodo-ai-seo'),
            'manage_options',
            'dodo-ai-seo',
            array($this, 'render_new_blog_page'),
            $this->get_menu_icon_svg('sparkles'),
            30
        );
        
        // 1. Yeni Blog Oluştur (ana sayfa ile aynı)
        add_submenu_page(
            'dodo-ai-seo',
            __('Yeni Blog Oluştur', 'dodo-ai-seo'),
            __('Yeni Blog Oluştur', 'dodo-ai-seo'),
            'manage_options',
            'dodo-ai-seo',
            array($this, 'render_new_blog_page')
        );
        
        // 2. Anahtar Kelime Fırsatları
        add_submenu_page(
            'dodo-ai-seo',
            __('Anahtar Kelime Fırsatları', 'dodo-ai-seo'),
            __('Anahtar Kelime Fırsatları', 'dodo-ai-seo'),
            'manage_options',
            'dodo-ai-seo-keyword-opportunities',
            array($this, 'render_keyword_opportunities_page')
        );
        
        // 3. Yayın Zamanlayıcı
        add_submenu_page(
            'dodo-ai-seo',
            __('Yayın Zamanlayıcı', 'dodo-ai-seo'),
            __('Yayın Zamanlayıcı', 'dodo-ai-seo'),
            'manage_options',
            'dodo-ai-seo-publishing-scheduler',
            array($this, 'render_publishing_scheduler_page')
        );
        
        // 4. Analitik
        add_submenu_page(
            'dodo-ai-seo',
            __('Analitik', 'dodo-ai-seo'),
            __('Analitik', 'dodo-ai-seo'),
            'manage_options',
            'dodo-ai-seo-analytics',
            array($this, 'render_analytics_dashboard_page')
        );
        
        // 5. İçerik Geliştirici
        add_submenu_page(
            'dodo-ai-seo',
            __('İçerik Geliştirici', 'dodo-ai-seo'),
            __('İçerik Geliştirici', 'dodo-ai-seo'),
            'manage_options',
            'dodo-ai-seo-content-improver',
            array($this, 'render_content_improver_page')
        );
        
        // 6. İçerik Kümeleri
        add_submenu_page(
            'dodo-ai-seo',
            __('İçerik Kümeleri', 'dodo-ai-seo'),
            __('İçerik Kümeleri', 'dodo-ai-seo'),
            'manage_options',
            'dodo-ai-seo-content-clusters',
            array($this, 'render_content_clusters_page')
        );
        
        // 7. Revizyon Geçmişi
        add_submenu_page(
            'dodo-ai-seo',
            __('Revizyon Geçmişi', 'dodo-ai-seo'),
            __('Revizyon Geçmişi', 'dodo-ai-seo'),
            'manage_options',
            'dodo-ai-seo-revision-history',
            array($this, 'render_revision_history_page')
        );
        
        // 8. İçerik Denetimi
        add_submenu_page(
            'dodo-ai-seo',
            __('İçerik Denetimi', 'dodo-ai-seo'),
            __('İçerik Denetimi', 'dodo-ai-seo'),
            'manage_options',
            'dodo-ai-seo-content-audit',
            array($this, 'render_content_audit_page')
        );
        
        // 9. Kullanım Raporları
        add_submenu_page(
            'dodo-ai-seo',
            __('Kullanım Raporları', 'dodo-ai-seo'),
            __('Kullanım Raporları', 'dodo-ai-seo'),
            'manage_options',
            'dodo-ai-seo-usage-reports',
            array($this, 'render_usage_reports_page')
        );
        
        // 10. Sistem Sağlığı
        add_submenu_page(
            'dodo-ai-seo',
            __('Sistem Sağlığı', 'dodo-ai-seo'),
            __('Sistem Sağlığı', 'dodo-ai-seo'),
            'manage_options',
            'dodo-ai-seo-system-health',
            array($this, 'render_system_health_page')
        );
        
        // 11. Ayarlar
        add_submenu_page(
            'dodo-ai-seo',
            __('Ayarlar', 'dodo-ai-seo'),
            __('Ayarlar', 'dodo-ai-seo'),
            'manage_options',
            'dodo-ai-seo-settings',
            array($this, 'render_settings_page')
        );
        
        // 12. Kurulum Sihirbazı
        add_submenu_page(
            'dodo-ai-seo',
            __('Kurulum Sihirbazı', 'dodo-ai-seo'),
            __('Kurulum Sihirbazı', 'dodo-ai-seo'),
            'manage_options',
            'dodo-ai-seo-onboarding',
            array($this, 'render_onboarding_page')
        );
        
        // 13. İş Kuyruğu
        add_submenu_page(
            'dodo-ai-seo',
            __('İş Kuyruğu', 'dodo-ai-seo'),
            __('İş Kuyruğu', 'dodo-ai-seo'),
            'manage_options',
            'dodo-ai-seo-queue',
            array($this, 'render_queue_dashboard_page')
        );
        
        // 14. Toplu İşlemler
        add_submenu_page(
            'dodo-ai-seo',
            __('Toplu İşlemler', 'dodo-ai-seo'),
            __('Toplu İşlemler', 'dodo-ai-seo'),
            'manage_options',
            'dodo-ai-seo-bulk-operations',
            array($this, 'render_bulk_operations_page')
        );
        
        // 15. Brain Test Lab (v2.3.0)
        add_submenu_page(
            'dodo-ai-seo',
            __('Beyin Test Merkezi', 'dodo-ai-seo'),
            __('Beyin Test Merkezi', 'dodo-ai-seo'),
            'manage_options',
            'dodo-ai-seo-brain-test-lab',
            array($this, 'render_brain_test_lab_page')
        );
        
        // 16. Learning Center (Phase 5)
        add_submenu_page(
            'dodo-ai-seo',
            __('Öğrenme Merkezi', 'dodo-ai-seo'),
            __('Öğrenme Merkezi', 'dodo-ai-seo'),
            'manage_options',
            'dodo-ai-seo-learning-center',
            array($this, 'render_learning_center_page')
        );
        
        // 17. Learning Control (Phase 5 - Governance)
        add_submenu_page(
            'dodo-ai-seo',
            __('Öğrenme Kontrolü', 'dodo-ai-seo'),
            __('Öğrenme Kontrolü', 'dodo-ai-seo'),
            'manage_options',
            'dodo-ai-seo-learning-control',
            array($this, 'render_learning_control_page')
        );
        
        // 18. SEO Impact Center (Phase 5 - KPIs)
        add_submenu_page(
            'dodo-ai-seo',
            __('SEO Etki Merkezi', 'dodo-ai-seo'),
            __('SEO Etki Merkezi', 'dodo-ai-seo'),
            'manage_options',
            'dodo-ai-seo-impact-center',
            array($this, 'render_impact_center_page')
        );
        
        // 19. Production Readiness (Phase 5 - Governance)
        add_submenu_page(
            'dodo-ai-seo',
            __('Canlı Sistem Durumu', 'dodo-ai-seo'),
            __('Canlı Sistem Durumu', 'dodo-ai-seo'),
            'manage_options',
            'dodo-ai-seo-production-readiness',
            array($this, 'render_production_readiness_page')
        );
        
        // 20. Debug Panel (Phase 7 - Internal Debug Tooling)
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_submenu_page(
                'dodo-ai-seo',
                __('Debug Panel', 'dodo-ai-seo'),
                __('🔍 Debug Panel', 'dodo-ai-seo'),
                'manage_options',
                'dodo-ai-seo-debug-panel',
                array($this, 'render_debug_panel_page')
            );
        }
        
        // 21. Live Validation (FINAL PRODUCTION TRANSITION)
        add_submenu_page(
            'dodo-ai-seo',
            __('Live Validation', 'dodo-ai-seo'),
            __('🚀 Live Validation', 'dodo-ai-seo'),
            'manage_options',
            'dodo-ai-seo-live-validation',
            array($this, 'render_live_validation_page')
        );
    }
    
    /**
     * Yeni blog oluşturma sayfasını render et
     */
    public function render_new_blog_page() {
        // Yetki kontrolü
        if (!current_user_can('manage_options')) {
            wp_die(__('Bu sayfaya erişim yetkiniz yok.', 'dodo-ai-seo'));
        }
        
        // Settings instance
        $settings = new DODO_Settings();
        
        // API key kontrolü
        if (!$settings->is_api_key_valid()) {
            $this->render_api_key_warning();
            return;
        }
        
        // Kategorileri al
        $categories = get_categories(array(
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC',
        ));
        
        // View dosyasını include et
        include DODO_PLUGIN_DIR . 'admin/views/page-new-blog.php';
    }
    
    /**
     * Ayarlar sayfasını render et
     */
    public function render_settings_page() {
        // Yetki kontrolü
        if (!current_user_can('manage_options')) {
            wp_die(__('Bu sayfaya erişim yetkiniz yok.', 'dodo-ai-seo'));
        }
        
        // GSC callback handling
        if (isset($_GET['gsc_callback']) && isset($_GET['code'])) {
            $gsc = new DODO_GSC_Connector();
            if ($gsc->exchange_code(sanitize_text_field($_GET['code']))) {
                add_settings_error(
                    'dodo_messages',
                    'dodo_message',
                    __('Google Search Console başarıyla bağlandı!', 'dodo-ai-seo'),
                    'success'
                );
            } else {
                add_settings_error(
                    'dodo_messages',
                    'dodo_message',
                    __('GSC bağlantısı başarısız oldu.', 'dodo-ai-seo'),
                    'error'
                );
            }
        }
        
        // GSC disconnect handling
        if (isset($_GET['gsc_disconnect'])) {
            $gsc = new DODO_GSC_Connector();
            if ($gsc->disconnect()) {
                add_settings_error(
                    'dodo_messages',
                    'dodo_message',
                    __('Google Search Console bağlantısı kesildi.', 'dodo-ai-seo'),
                    'success'
                );
            }
        }
        
        // Ayarları kaydet
        if (isset($_POST['dodo_save_settings'])) {
            // Nonce kontrolü
            if (!isset($_POST['dodo_settings_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['dodo_settings_nonce'])), 'dodo_settings_nonce_action')) {
                wp_die(__('Güvenlik kontrolü başarısız.', 'dodo-ai-seo'));
            }
            
            $this->save_settings();
        }
        
        // GSC ayarlarını kaydet
        if (isset($_POST['dodo_save_gsc_settings'])) {
            if (!isset($_POST['dodo_settings_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['dodo_settings_nonce'])), 'dodo_settings_nonce_action')) {
                wp_die(__('Güvenlik kontrolü başarısız.', 'dodo-ai-seo'));
            }
            
            if (isset($_POST['gsc_client_id'])) {
                update_option('dodo_gsc_client_id', sanitize_text_field($_POST['gsc_client_id']));
            }
            
            if (isset($_POST['gsc_client_secret'])) {
                update_option('dodo_gsc_client_secret', sanitize_text_field($_POST['gsc_client_secret']));
            }
            
            add_settings_error(
                'dodo_messages',
                'dodo_message',
                __('GSC ayarları kaydedildi. Şimdi "Google ile Bağlan" butonuna tıklayın.', 'dodo-ai-seo'),
                'success'
            );
        }
        
        // Settings instance
        $settings = new DODO_Settings();
        $current_settings = $settings->get_settings();
        
        // Debug log - what settings are being displayed
        error_log('[DODO SETTINGS] === LOADING SETTINGS PAGE ===');
        error_log('[DODO SETTINGS] Current default_publish_mode: ' . $current_settings['default_publish_mode']);
        error_log('[DODO SETTINGS] Current daily_publish_limit: ' . $current_settings['daily_publish_limit']);
        error_log('[DODO SETTINGS] Current publish_start_hour: ' . $current_settings['publish_start_hour']);
        error_log('[DODO SETTINGS] Current publish_start_minute: ' . $current_settings['publish_start_minute']);
        error_log('[DODO SETTINGS] Current publish_on_weekends: ' . ($current_settings['publish_on_weekends'] ? 'true' : 'false'));
        error_log('[DODO SETTINGS] Current publish_time_interval: ' . $current_settings['publish_time_interval']);
        
        // View dosyasını include et
        include DODO_PLUGIN_DIR . 'admin/views/page-settings.php';
    }
    
    /**
     * Onboarding sayfasını render et (NEW - Sprint 4)
     */
    public function render_onboarding_page() {
        // Yetki kontrolü
        if (!current_user_can('manage_options')) {
            wp_die(__('Bu sayfaya erişim yetkiniz yok.', 'dodo-ai-seo'));
        }
        
        // Onboarding CSS
        wp_enqueue_style(
            'dodo-onboarding-css',
            DODO_PLUGIN_URL . 'assets/css/onboarding.css',
            array(),
            DODO_VERSION
        );
        
        // Onboarding JS
        wp_enqueue_script(
            'dodo-onboarding-js',
            DODO_PLUGIN_URL . 'assets/js/onboarding.js',
            array('jquery'),
            DODO_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('dodo-onboarding-js', 'dodoOnboarding', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'adminUrl' => admin_url(),
            'nonce' => wp_create_nonce('dodo_onboarding_nonce'),
        ));
        
        // View dosyasını include et
        include DODO_PLUGIN_DIR . 'admin/views/page-onboarding.php';
    }
    
    /**
     * Queue Dashboard sayfasını render et (NEW - Sprint 4)
     */
    public function render_queue_dashboard_page() {
        // Yetki kontrolü
        if (!current_user_can('manage_options')) {
            wp_die(__('Bu sayfaya erişim yetkiniz yok.', 'dodo-ai-seo'));
        }
        
        // Queue Dashboard CSS
        wp_enqueue_style(
            'dodo-queue-dashboard-css',
            DODO_PLUGIN_URL . 'assets/css/queue-dashboard.css',
            array(),
            DODO_VERSION
        );
        
        // Queue Dashboard JS
        wp_enqueue_script(
            'dodo-queue-dashboard-js',
            DODO_PLUGIN_URL . 'assets/js/queue-dashboard.js',
            array('jquery'),
            DODO_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('dodo-queue-dashboard-js', 'dodoQueue', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dodo_queue_nonce'),
        ));
        
        // View dosyasını include et
        include DODO_PLUGIN_DIR . 'admin/views/page-queue-dashboard.php';
    }
    
    /**
     * Bulk Operations sayfasını render et (NEW - Sprint 4)
     */
    public function render_bulk_operations_page() {
        // Yetki kontrolü
        if (!current_user_can('manage_options')) {
            wp_die(__('Bu sayfaya erişim yetkiniz yok.', 'dodo-ai-seo'));
        }
        
        // Bulk Operations CSS
        wp_enqueue_style(
            'dodo-bulk-operations-css',
            DODO_PLUGIN_URL . 'assets/css/bulk-operations.css',
            array(),
            DODO_VERSION
        );
        
        // Bulk Operations JS
        wp_enqueue_script(
            'dodo-bulk-operations-js',
            DODO_PLUGIN_URL . 'assets/js/bulk-operations.js',
            array('jquery'),
            DODO_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('dodo-bulk-operations-js', 'dodoBulk', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dodo_bulk_nonce'),
        ));
        
        // View dosyasını include et
        include DODO_PLUGIN_DIR . 'admin/views/page-bulk-operations.php';
    }
    
    /**
     * Brain Test Lab sayfasını render et (NEW - v2.3.0)
     */
    public function render_brain_test_lab_page() {
        // Yetki kontrolü
        if (!current_user_can('manage_options')) {
            wp_die(__('Bu sayfaya erişim yetkiniz yok.', 'dodo-ai-seo'));
        }
        
        // View dosyasını include et
        include DODO_PLUGIN_DIR . 'admin/views/page-brain-test-lab.php';
    }
    
    /**
     * API key uyarısını göster
     */
    private function render_api_key_warning() {
        $settings_url = admin_url('admin.php?page=dodo-ai-seo-settings');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('DODO AI SEO', 'dodo-ai-seo'); ?></h1>
            <div class="notice notice-error">
                <p>
                    <strong><?php echo esc_html__('OpenAI API Anahtarı Gerekli', 'dodo-ai-seo'); ?></strong>
                </p>
                <p>
                    <?php 
                    echo sprintf(
                        esc_html__('Blog yazısı oluşturabilmek için önce %s sayfasından OpenAI API anahtarınızı girmeniz gerekiyor.', 'dodo-ai-seo'),
                        '<a href="' . esc_url($settings_url) . '">' . esc_html__('Ayarlar', 'dodo-ai-seo') . '</a>'
                    );
                    ?>
                </p>
                <p>
                    <a href="<?php echo esc_url($settings_url); ?>" class="button button-primary">
                        <?php echo esc_html__('Ayarlara Git', 'dodo-ai-seo'); ?>
                    </a>
                </p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Ayarları kaydet
     */
    private function save_settings() {
        $settings = new DODO_Settings();
        $current_settings = $settings->get_settings();
        
        // Raw values from POST
        $raw_values = array(
            'openai_model' => isset($_POST['openai_model']) ? sanitize_text_field($_POST['openai_model']) : 'gpt-4o',
            'openai_temperature' => isset($_POST['openai_temperature']) ? floatval($_POST['openai_temperature']) : 0.7,
            'default_tone' => isset($_POST['default_tone']) ? sanitize_text_field($_POST['default_tone']) : 'technical',
            'default_length' => isset($_POST['default_length']) ? sanitize_text_field($_POST['default_length']) : 'medium',
            'default_status' => isset($_POST['default_status']) ? sanitize_text_field($_POST['default_status']) : 'draft',
            'min_internal_links' => isset($_POST['min_internal_links']) ? absint($_POST['min_internal_links']) : 5,
            'max_internal_links' => isset($_POST['max_internal_links']) ? absint($_POST['max_internal_links']) : 10,
            // Scheduled Publishing Settings
            'default_publish_mode' => isset($_POST['default_publish_mode']) ? sanitize_text_field($_POST['default_publish_mode']) : 'draft',
            'daily_publish_limit' => isset($_POST['daily_publish_limit']) ? absint($_POST['daily_publish_limit']) : 1,
            'publish_start_hour' => isset($_POST['publish_start_hour']) ? absint($_POST['publish_start_hour']) : 10,
            'publish_start_minute' => isset($_POST['publish_start_minute']) ? absint($_POST['publish_start_minute']) : 0,
            'publish_on_weekends' => isset($_POST['publish_on_weekends']) ? true : false,
            'publish_time_interval' => isset($_POST['publish_time_interval']) ? absint($_POST['publish_time_interval']) : 4,
            // Sprint 5: Answer Blocks
            'answer_blocks_enabled' => isset($_POST['answer_blocks_enabled']) ? true : false,
            'block_short_answer' => isset($_POST['block_short_answer']) ? true : false,
            'block_featured_snippet' => isset($_POST['block_featured_snippet']) ? true : false,
            'block_direct_answer' => isset($_POST['block_direct_answer']) ? true : false,
            'block_comparison' => isset($_POST['block_comparison']) ? true : false,
            'block_ai_summary' => isset($_POST['block_ai_summary']) ? true : false,
            'block_faq' => isset($_POST['block_faq']) ? true : false,
            'answer_blocks_max_calls' => isset($_POST['answer_blocks_max_calls']) ? absint($_POST['answer_blocks_max_calls']) : 3,
            // Sprint 5: GEO
            'geo_analysis_mode' => isset($_POST['geo_analysis_mode']) ? sanitize_text_field($_POST['geo_analysis_mode']) : 'basic',
            'geo_ai_visibility_threshold' => isset($_POST['geo_ai_visibility_threshold']) ? absint($_POST['geo_ai_visibility_threshold']) : 70,
            // Sprint 5: Humanization
            'humanization_preset' => isset($_POST['humanization_preset']) ? sanitize_text_field($_POST['humanization_preset']) : 'subtle',
            'humanization_aggressiveness' => isset($_POST['humanization_aggressiveness']) ? absint($_POST['humanization_aggressiveness']) : 5,
            // Sprint 5: Publishing Pipeline
            'publishing_pipeline_enabled' => isset($_POST['publishing_pipeline_enabled']) ? true : false,
            'publishing_review_required' => isset($_POST['publishing_review_required']) ? true : false,
            'publishing_auto_publish' => isset($_POST['publishing_auto_publish']) ? true : false,
        );
        
        // Debug log - raw values
        error_log('[DODO SETTINGS] === RAW POST VALUES ===');
        error_log('[DODO SETTINGS] RAW default_publish_mode: ' . $raw_values['default_publish_mode']);
        
        // Sanitize through settings class
        $new_settings = $settings->sanitize_settings($raw_values);
        
        // Debug log - after sanitization
        error_log('[DODO SETTINGS] === AFTER SANITIZATION ===');
        error_log('[DODO SETTINGS] default_publish_mode: ' . $new_settings['default_publish_mode']);
        error_log('[DODO SETTINGS] daily_publish_limit: ' . $new_settings['daily_publish_limit']);
        error_log('[DODO SETTINGS] publish_start_hour: ' . $new_settings['publish_start_hour']);
        error_log('[DODO SETTINGS] publish_start_minute: ' . $new_settings['publish_start_minute']);
        error_log('[DODO SETTINGS] publish_on_weekends: ' . ($new_settings['publish_on_weekends'] ? 'true' : 'false'));
        error_log('[DODO SETTINGS] publish_time_interval: ' . $new_settings['publish_time_interval']);
        
        // API key - sadece değiştirilmişse güncelle
        if (isset($_POST['openai_api_key'])) {
            $submitted_key = sanitize_text_field($_POST['openai_api_key']);
            
            // Eğer maskeli değer değilse (yani yeni key girilmişse)
            if (!empty($submitted_key) && strpos($submitted_key, '*') === false) {
                // Yeni key girilmiş
                $new_settings['openai_api_key'] = $submitted_key;
            } else {
                // Maskeli değer veya boş - mevcut key'i koru
                $new_settings['openai_api_key'] = $current_settings['openai_api_key'];
            }
        } else {
            // API key gönderilmemiş - mevcut key'i koru
            $new_settings['openai_api_key'] = $current_settings['openai_api_key'];
        }
        
        if ($settings->update_settings($new_settings)) {
            error_log('[DODO SETTINGS] Settings saved successfully');
            
            // Verify what was actually saved
            $saved_settings = $settings->get_settings();
            error_log('[DODO SETTINGS] === VERIFICATION - WHAT WAS ACTUALLY SAVED ===');
            error_log('[DODO SETTINGS] Saved default_publish_mode: ' . $saved_settings['default_publish_mode']);
            error_log('[DODO SETTINGS] Saved daily_publish_limit: ' . $saved_settings['daily_publish_limit']);
            error_log('[DODO SETTINGS] Saved publish_start_hour: ' . $saved_settings['publish_start_hour']);
            error_log('[DODO SETTINGS] Saved publish_start_minute: ' . $saved_settings['publish_start_minute']);
            error_log('[DODO SETTINGS] Saved publish_on_weekends: ' . ($saved_settings['publish_on_weekends'] ? 'true' : 'false'));
            error_log('[DODO SETTINGS] Saved publish_time_interval: ' . $saved_settings['publish_time_interval']);
            
            add_settings_error(
                'dodo_messages',
                'dodo_message',
                __('Ayarlar başarıyla kaydedildi.', 'dodo-ai-seo'),
                'success'
            );
        } else {
            error_log('[DODO SETTINGS] Settings save failed');
            add_settings_error(
                'dodo_messages',
                'dodo_message',
                __('Ayarlar kaydedilirken bir hata oluştu.', 'dodo-ai-seo'),
                'error'
            );
        }
    }
    
    /**
     * AJAX: Blog oluştur
     * HARDENED: Final Completion Phase - AJAX Hardening
     */
    public function ajax_generate_blog() {
        try {
            // Defensive nonce check (no fatal on failure)
            if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'dodo_ajax_nonce')) {
                DODO_Error_Handler::log_error_with_context('AJAX', 'Nonce verification failed for ajax_generate_blog', array(
                    'user_id' => get_current_user_id(),
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ));
                wp_send_json_error(array(
                    'message' => __('Güvenlik doğrulaması başarısız. Lütfen sayfayı yenileyin.', 'dodo-ai-seo')
                ), 403);
                return;
            }
            
            // Capability check
            if (!current_user_can('manage_options')) {
                DODO_Error_Handler::log_error_with_context('AJAX', 'Permission denied for ajax_generate_blog', array(
                    'user_id' => get_current_user_id()
                ));
                wp_send_json_error(array(
                    'message' => __('Bu işlem için yetkiniz yok.', 'dodo-ai-seo')
                ), 403);
                return;
            }
        
            // Form verilerini al
            $focus_keyword = isset($_POST['focus_keyword']) ? sanitize_text_field(wp_unslash($_POST['focus_keyword'])) : '';
            $topic = isset($_POST['topic']) ? sanitize_textarea_field(wp_unslash($_POST['topic'])) : '';
            $length = isset($_POST['length']) ? sanitize_text_field(wp_unslash($_POST['length'])) : 'medium';
            $tone = isset($_POST['tone']) ? sanitize_text_field(wp_unslash($_POST['tone'])) : 'technical';
            $content_type = isset($_POST['content_type']) ? sanitize_text_field(wp_unslash($_POST['content_type'])) : 'blog';
            $category_id = isset($_POST['category_id']) ? absint($_POST['category_id']) : 0;
            $opportunity_id = isset($_POST['opportunity_id']) ? absint($_POST['opportunity_id']) : 0;
            
            // Publish mode parameters
            $publish_mode = isset($_POST['publish_mode']) ? sanitize_text_field(wp_unslash($_POST['publish_mode'])) : 'default';
            $scheduled_date = isset($_POST['scheduled_date']) ? sanitize_text_field(wp_unslash($_POST['scheduled_date'])) : '';
            $scheduled_time = isset($_POST['scheduled_time']) ? sanitize_text_field(wp_unslash($_POST['scheduled_time'])) : '';
            
            // Advanced Generation Controls (Sprint 3 - Task 6)
            // BOŞ STRING = Kullanıcı seçmedi, Brain override yapabilir
            // DOLU STRING = Kullanıcı seçti, kullanıcı tercihi üstün
            $content_intent = isset($_POST['content_intent']) ? sanitize_text_field(wp_unslash($_POST['content_intent'])) : '';
            $expertise_depth = isset($_POST['expertise_depth']) ? sanitize_text_field(wp_unslash($_POST['expertise_depth'])) : '';
            $geo_optimization = isset($_POST['geo_optimization']) ? sanitize_text_field(wp_unslash($_POST['geo_optimization'])) : '';
            $ai_naturalness = isset($_POST['ai_naturalness']) ? sanitize_text_field(wp_unslash($_POST['ai_naturalness'])) : '';
            $semantic_aggressiveness = isset($_POST['semantic_aggressiveness']) ? sanitize_text_field(wp_unslash($_POST['semantic_aggressiveness'])) : '';
            $readability_target = isset($_POST['readability_target']) ? sanitize_text_field(wp_unslash($_POST['readability_target'])) : '';
            
            // Detect user explicit controls
            $user_explicit_controls = !empty($content_intent) || !empty($expertise_depth) || !empty($geo_optimization) || 
                                      !empty($ai_naturalness) || !empty($semantic_aggressiveness) || !empty($readability_target);
            
            // Apply defaults only if user didn't select
            if (!$user_explicit_controls) {
                $content_intent = 'informational';
                $expertise_depth = 'intermediate';
                $geo_optimization = 'moderate';
                $ai_naturalness = 'human';
                $semantic_aggressiveness = 'moderate';
                $readability_target = 'easy';
                error_log("DODO AJAX: user_explicit_controls = FALSE - Brain can override");
            } else {
                error_log("DODO AJAX: user_explicit_controls = TRUE - User preferences locked");
            }
            
            // Answer Blocks Controls (Task 6.2 - Frontend to Backend)
            $answer_blocks_enabled = isset($_POST['answer_blocks_enabled']) ? (sanitize_text_field(wp_unslash($_POST['answer_blocks_enabled'])) === '1') : null;
            $block_short_answer = isset($_POST['block_short_answer']) ? (sanitize_text_field(wp_unslash($_POST['block_short_answer'])) === '1') : null;
            $block_faq = isset($_POST['block_faq']) ? (sanitize_text_field(wp_unslash($_POST['block_faq'])) === '1') : null;
            $humanization_preset = isset($_POST['humanization_preset']) ? sanitize_text_field(wp_unslash($_POST['humanization_preset'])) : '';
            
            // Log answer blocks settings
            if ($answer_blocks_enabled !== null) {
                error_log("DODO AJAX: Answer Blocks - Enabled: " . ($answer_blocks_enabled ? 'YES' : 'NO'));
                error_log("DODO AJAX: Answer Blocks - Short Answer: " . ($block_short_answer ? 'YES' : 'NO'));
                error_log("DODO AJAX: Answer Blocks - FAQ: " . ($block_faq ? 'YES' : 'NO'));
            }
            
            // Focus keyword'ü detaylı logla
            error_log("=== DODO AJAX HANDLER START ===");
            error_log("DODO AJAX: \$_POST['focus_keyword'] RAW: " . (isset($_POST['focus_keyword']) ? wp_unslash($_POST['focus_keyword']) : 'NOT SET'));
            error_log("DODO AJAX: \$focus_keyword AFTER SANITIZE: '{$focus_keyword}'");
            error_log("DODO AJAX: Focus keyword boş mu? " . (empty($focus_keyword) ? 'EVET - BOŞ!' : 'Hayır - Dolu'));
            error_log("DODO AJAX: Publish mode: {$publish_mode}");
            
            // Validasyon - sadece focus keyword zorunlu
            if (empty($focus_keyword)) {
                DODO_Error_Handler::log_error_with_context('AJAX', 'Missing focus keyword in ajax_generate_blog', array(
                    'user_id' => get_current_user_id()
                ));
                wp_send_json_error(array(
                    'message' => __('Odak anahtar kelime zorunludur.', 'dodo-ai-seo')
                ), 400);
                return;
            }
            
            // Topic opsiyonel - boş olabilir
            if (empty($topic)) {
                error_log("DODO AJAX: Topic boş, AI otomatik yön belirleyecek");
            }
            
            // Publish mode'u resolve et
            $settings = new DODO_Settings();
            $resolved_publish_mode = $publish_mode;
            
            if ($publish_mode === 'default') {
                // Varsayılan ayarı kullan
                $resolved_publish_mode = $settings->get_setting('default_publish_mode', 'draft');
                error_log("DODO AJAX: Using default publish mode: {$resolved_publish_mode}");
            }
            
            // Queue position hesapla (scheduled mode için)
            $queue_position = 0;
            if ($resolved_publish_mode === 'scheduled') {
                $scheduler = new DODO_Scheduled_Publisher();
                $queue_position = $scheduler->get_current_queue_position();
                error_log("DODO AJAX: Queue position: {$queue_position}");
            }
            
            // Focus keyword'ü logla
            error_log("DODO AJAX: Focus keyword generator'a gönderiliyor: '{$focus_keyword}'");
            
            // Generator'ı başlat (inner try-catch for generator-specific errors)
            if (!class_exists('DODO_Generator')) {
                error_log('DODO AJAX FATAL: DODO_Generator class not found!');
                wp_send_json_error(array(
                    'message' => __('Sistem hatası: Generator sınıfı yüklenemedi. Lütfen eklentiyi yeniden etkinleştirin.', 'dodo-ai-seo')
                ), 500);
                return;
            }
            
            try {
                $generator = new DODO_Generator();
            } catch (Exception $e) {
                error_log('DODO AJAX FATAL: Generator initialization failed: ' . $e->getMessage());
                wp_send_json_error(array(
                    'message' => __('Sistem hatası: Generator başlatılamadı. Eksik bağımlılıklar olabilir.', 'dodo-ai-seo'),
                    'debug' => defined('WP_DEBUG') && WP_DEBUG ? $e->getMessage() : null
                ), 500);
                return;
            }
            
            // Opportunity ID varsa GSC context al
            $gsc_context = array();
            if ($opportunity_id > 0) {
                global $wpdb;
                $opportunities_table = $wpdb->prefix . 'dodo_keyword_opportunities';
                $opportunity = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$opportunities_table} WHERE id = %d",
                    $opportunity_id
                ), ARRAY_A);
                
                if ($opportunity) {
                    $gsc_context = array(
                        'source' => $opportunity['source'] ?? 'ai_suggestion',
                        'impressions' => intval($opportunity['impressions'] ?? 0),
                        'clicks' => intval($opportunity['clicks'] ?? 0),
                        'ctr' => floatval($opportunity['ctr'] ?? 0),
                        'position' => floatval($opportunity['position'] ?? 0),
                        'impact' => $opportunity['impact'] ?? 'medium',
                        'effort' => $opportunity['effort'] ?? 'medium',
                        'recommended_action' => $opportunity['recommended_action'] ?? '',
                    );
                    
                    error_log('[DODO][Blog Generation] GSC context loaded: ' . json_encode($gsc_context));
                }
            }
            
            // Generator parametreleri
            $generator_params = array(
                'focus_keyword' => $focus_keyword,
                'topic' => $topic,
                'length' => $length,
                'tone' => $tone,
                'content_type' => $content_type,
                'category_id' => $category_id,
                'post_status' => 'draft', // Will be overridden by publish_mode
                'publish_mode' => $resolved_publish_mode,
                'queue_position' => $queue_position,
                'gsc_context' => $gsc_context, // GSC metrikleri
                // Advanced Generation Controls (Sprint 3 - Task 6)
                'content_intent' => $content_intent,
                'expertise_depth' => $expertise_depth,
                'geo_optimization' => $geo_optimization,
                'ai_naturalness' => $ai_naturalness,
                'semantic_aggressiveness' => $semantic_aggressiveness,
                'readability_target' => $readability_target,
                'user_explicit_controls' => $user_explicit_controls, // Brain override flag
                // Answer Blocks Controls (Task 6.2)
                'answer_blocks_enabled' => $answer_blocks_enabled,
                'block_short_answer' => $block_short_answer,
                'block_faq' => $block_faq,
                'humanization_preset' => $humanization_preset,
            );
            
            // Eğer manuel tarih/saat girilmişse ekle
            if ($resolved_publish_mode === 'scheduled' && !empty($scheduled_date) && !empty($scheduled_time)) {
                // Validate date format
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $scheduled_date) && preg_match('/^\d{2}:\d{2}$/', $scheduled_time)) {
                    $generator_params['scheduled_date'] = $scheduled_date;
                    $generator_params['scheduled_time'] = $scheduled_time;
                    error_log("DODO AJAX: Manual schedule: {$scheduled_date} {$scheduled_time}");
                } else {
                    error_log("DODO AJAX: Invalid date/time format, using auto calculation");
                }
            }
            
            // Blog oluştur
            $result = $generator->generate_blog($generator_params);
            
            // WP_Error kontrolü - kelime sayısı veya diğer hatalar
            if (is_wp_error($result)) {
                DODO_Error_Handler::log_error_with_context('AJAX', 'Blog generation failed', array(
                    'error_code' => $result->get_error_code(),
                    'error_message' => $result->get_error_message(),
                    'focus_keyword' => $focus_keyword,
                    'user_id' => get_current_user_id()
                ));
                
                // Determine HTTP status code based on error
                $http_code = 500;
                $error_code = $result->get_error_code();
                if (in_array($error_code, array('no_api_key', 'openai_invalid_key'), true)) {
                    $http_code = 400;
                } elseif (in_array($error_code, array('rate_limit_exceeded', 'openai_rate_limit', 'openai_quota_exceeded'), true)) {
                    $http_code = 429;
                }
                
                wp_send_json_error(array(
                    'message' => $result->get_error_message(),
                    'error_code' => $result->get_error_code(),
                    'error_data' => $result->get_error_data()
                ), $http_code);
                return;
            }
            
            // Post oluşturuldu - SEO verilerini logla
            if (isset($result['post_id'])) {
                $post_id = $result['post_id'];
                
                error_log("DODO AJAX: Post oluşturuldu ID: {$post_id}");
                error_log("DODO AJAX: Post SEO verileri kontrol ediliyor...");
                
                // Opportunity ID varsa durumu güncelle
                if ($opportunity_id > 0) {
                    $opportunities_manager = new DODO_Keyword_Opportunities();
                    $opportunities_manager->update_status($opportunity_id, 'created');
                    error_log("DODO AJAX: Opportunity #{$opportunity_id} durumu 'created' olarak güncellendi");
                }
                
                // SEO verilerini al ve logla
                $saved_focus = get_post_meta($post_id, 'rank_math_focus_keyword', true);
                $saved_title = get_post_meta($post_id, 'rank_math_title', true);
                $saved_desc = get_post_meta($post_id, 'rank_math_description', true);
                $post = get_post($post_id);
                
                error_log("DODO AJAX VERIFY:");
                error_log("  - Original focus_keyword: {$focus_keyword}");
                error_log("  - rank_math_focus_keyword: {$saved_focus}");
                error_log("  - rank_math_title: {$saved_title}");
                error_log("  - rank_math_description length: " . mb_strlen($saved_desc));
                error_log("  - rank_math_description: " . mb_substr($saved_desc, 0, 100) . "...");
                error_log("  - post_name (slug): {$post->post_name}");
                error_log("  - excerpt length: " . mb_strlen($post->post_excerpt));
                
                // Focus keyword eşleşmesi kontrolü
                if ($saved_focus !== $focus_keyword) {
                    error_log("DODO AJAX ERROR: Focus keyword eşleşmiyor!");
                    error_log("  - Beklenen: {$focus_keyword}");
                    error_log("  - Kaydedilen: {$saved_focus}");
                }
            }
            
            // Clean output buffer before sending JSON response
            // Prevent ob_end_flush zlib errors
            while (ob_get_level() > 0) {
                @ob_end_clean();
            }
            
            // Başarılı
            wp_send_json_success(array(
                'message' => __('Blog yazısı başarıyla oluşturuldu!', 'dodo-ai-seo'),
                'post_id' => $result['post_id'],
                'edit_url' => get_edit_post_link($result['post_id'], 'raw'),
                'view_url' => get_permalink($result['post_id']),
            ));
            
        } catch (Throwable $e) {
            // Clean output buffer before sending error response
            while (ob_get_level() > 0) {
                @ob_end_clean();
            }
            
            // Catch all exceptions and errors (PHP 7+)
            DODO_Error_Handler::safe_exception_response($e, 'ajax_generate_blog');
        }
    }
    
    /**
     * AJAX: Rank Math meta verilerini temizle ve düzelt
     * HARDENED: Final Completion Phase - AJAX Hardening
     */
    public function ajax_clean_rankmath_meta() {
        try {
            // Defensive nonce check
            if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'dodo_ajax_nonce')) {
                DODO_Error_Handler::safe_ajax_error(__('Güvenlik doğrulaması başarısız.', 'dodo-ai-seo'), 403);
                return;
            }
            
            // Capability check
            if (!current_user_can('manage_options')) {
                DODO_Error_Handler::safe_ajax_error(__('Bu işlem için yetkiniz yok.', 'dodo-ai-seo'), 403);
                return;
            }
        
        $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
        
        if (!$post_id) {
            wp_send_json_error(array(
                'message' => __('Geçersiz post ID.', 'dodo-ai-seo')
            ));
        }
        
        // DODO tarafından oluşturulmuş mu kontrol et
        $is_dodo = get_post_meta($post_id, '_dodo_generated', true);
        
        if (!$is_dodo) {
            wp_send_json_error(array(
                'message' => __('Bu yazı DODO AI tarafından oluşturulmamış.', 'dodo-ai-seo')
            ));
        }
        
        // Bozuk meta değerlerini temizle
        $this->clean_broken_rankmath_meta($post_id);
        
        // Temiz meta değerlerini yeniden kaydet
        $focus_keyword = get_post_meta($post_id, '_dodo_focus_keyword', true);
        $post = get_post($post_id);
        
        if ($focus_keyword && $post) {
            $seo_data = array(
                'focus_keyword' => $focus_keyword,
                'seo_title' => $post->post_title,
                'meta_description' => $post->post_excerpt ? $post->post_excerpt : wp_trim_words($post->post_content, 20),
            );
            
            $rankmath = new DODO_RankMath();
            $rankmath->save_seo_meta($post_id, $seo_data);
        }
        
        wp_send_json_success(array(
            'message' => __('Rank Math meta verileri temizlendi ve düzeltildi.', 'dodo-ai-seo')
        ));
        
        } catch (Throwable $e) {
            DODO_Error_Handler::safe_exception_response($e, 'ajax_clean_rankmath_meta');
        }
    }
    
    /**
     * Bozuk Rank Math meta verilerini temizle
     * 
     * @param int $post_id
     */
    private function clean_broken_rankmath_meta($post_id) {
        // Bozuk/hatalı meta değerlerini sil
        delete_post_meta($post_id, 'rank_math_seo_score');
        delete_post_meta($post_id, 'rank_math_contentai_score');
        delete_post_meta($post_id, 'rank_math_focus_keywords');
        delete_post_meta($post_id, 'rank_math_primary_focus_keyword');
        delete_post_meta($post_id, 'rank_math_internal_links_processed');
        delete_post_meta($post_id, 'rank_math_analytic_object_id');
        delete_post_meta($post_id, 'rank_math_pillar_content');
        delete_post_meta($post_id, 'rank_math_facebook_enable_image_overlay');
        delete_post_meta($post_id, 'rank_math_facebook_image_overlay');
        delete_post_meta($post_id, 'rank_math_twitter_enable_image_overlay');
        delete_post_meta($post_id, 'rank_math_twitter_image_overlay');
    }
    
    /**
     * Toplu işlem ekle
     */
    public function add_bulk_action($bulk_actions) {
        $bulk_actions['dodo_clean_rankmath'] = __('DODO: Rank Math Meta Temizle', 'dodo-ai-seo');
        $bulk_actions['dodo_clean_schema'] = __('DODO: Schema Temizle', 'dodo-ai-seo');
        return $bulk_actions;
    }
    
    /**
     * Toplu işlemi yönet
     */
    public function handle_bulk_action($redirect_to, $doaction, $post_ids) {
        if ($doaction === 'dodo_clean_rankmath') {
            $cleaned = 0;
            
            foreach ($post_ids as $post_id) {
                // Sadece DODO yazılarını temizle
                $is_dodo = get_post_meta($post_id, '_dodo_generated', true);
                
                if ($is_dodo) {
                    $this->clean_broken_rankmath_meta($post_id);
                    
                    // Temiz meta değerlerini yeniden kaydet
                    $focus_keyword = get_post_meta($post_id, '_dodo_focus_keyword', true);
                    $post = get_post($post_id);
                    
                    if ($focus_keyword && $post) {
                        $seo_data = array(
                            'focus_keyword' => $focus_keyword,
                            'seo_title' => $post->post_title,
                            'meta_description' => $post->post_excerpt ? $post->post_excerpt : wp_trim_words($post->post_content, 20),
                        );
                        
                        $rankmath = new DODO_RankMath();
                        $rankmath->save_seo_meta($post_id, $seo_data);
                    }
                    
                    $cleaned++;
                }
            }
            
            $redirect_to = add_query_arg('dodo_cleaned', $cleaned, $redirect_to);
            return $redirect_to;
        }
        
        if ($doaction === 'dodo_clean_schema') {
            $cleaned = 0;
            
            foreach ($post_ids as $post_id) {
                // Sadece DODO yazılarını temizle
                $is_dodo = get_post_meta($post_id, '_dodo_generated', true);
                
                if ($is_dodo) {
                    $this->clean_schema_meta($post_id);
                    $cleaned++;
                }
            }
            
            $redirect_to = add_query_arg('dodo_schema_cleaned', $cleaned, $redirect_to);
            return $redirect_to;
        }
        
        return $redirect_to;
    }
    
    /**
     * Schema meta'larını temizle
     */
    private function clean_schema_meta($post_id) {
        global $wpdb;
        
        // Rank Math schema metalarını sil
        $schema_keys = array(
            'rank_math_schema_Article',
            'rank_math_schema_FAQPage',
            'rank_math_schema',
            'rank_math_rich_snippet',
            'rank_math_snippet',
            'rank_math_faq',
            'rank_math_howto',
        );
        
        foreach ($schema_keys as $key) {
            delete_post_meta($post_id, $key);
        }
        
        // Schema/FAQ/HowTo geçen tüm Rank Math metalarını sil
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key LIKE 'rank_math%%' AND (meta_key LIKE '%%schema%%' OR meta_key LIKE '%%faq%%' OR meta_key LIKE '%%howto%%')",
            $post_id
        ));
        
        // Cache temizle
        wp_cache_delete($post_id, 'post_meta');
        clean_post_cache($post_id);
    }
    
    /**
     * Toplu işlem bildirimi
     */
    public function bulk_action_admin_notice() {
        if (!empty($_REQUEST['dodo_cleaned'])) {
            $cleaned = intval($_REQUEST['dodo_cleaned']);
            printf(
                '<div class="notice notice-success is-dismissible"><p>' .
                _n(
                    '%s yazının Rank Math meta verileri temizlendi.',
                    '%s yazının Rank Math meta verileri temizlendi.',
                    $cleaned,
                    'dodo-ai-seo'
                ) .
                '</p></div>',
                $cleaned
            );
        }
        
        if (!empty($_REQUEST['dodo_schema_cleaned'])) {
            $cleaned = intval($_REQUEST['dodo_schema_cleaned']);
            printf(
                '<div class="notice notice-success is-dismissible"><p>' .
                _n(
                    '%s yazının Schema meta verileri temizlendi.',
                    '%s yazının Schema meta verileri temizlendi.',
                    $cleaned,
                    'dodo-ai-seo'
                ) .
                '</p></div>',
                $cleaned
            );
        }
    }
    
    /**
     * Debug sayfasını render et
     */
    public function render_debug_page() {
        // Yetki kontrolü
        if (!current_user_can('manage_options')) {
            wp_die(__('Bu sayfaya erişim yetkiniz yok.', 'dodo-ai-seo'));
        }
        
        // Hard clean işlemi
        if (isset($_POST['dodo_hard_clean']) && isset($_POST['hard_clean_post_id'])) {
            check_admin_referer('dodo_debug_nonce');
            $post_id = absint($_POST['hard_clean_post_id']);
            $this->hard_clean_post($post_id);
            echo '<div class="notice notice-success"><p>Post #' . $post_id . ' tamamen temizlendi.</p></div>';
        }
        
        // Karşılaştırma
        $dodo_post_id = isset($_POST['dodo_post_id']) ? absint($_POST['dodo_post_id']) : 0;
        $manual_post_id = isset($_POST['manual_post_id']) ? absint($_POST['manual_post_id']) : 0;
        
        ?>
        <div class="wrap">
            <h1>🔧 DODO AI Debug</h1>
            
            <!-- Post Karşılaştırma -->
            <div class="card" style="max-width: 100%; margin-top: 20px;">
                <h2>Post Karşılaştırma</h2>
                <form method="post">
                    <?php wp_nonce_field('dodo_debug_nonce'); ?>
                    <table class="form-table">
                        <tr>
                            <th>DODO Post ID (Bozuk)</th>
                            <td><input type="number" name="dodo_post_id" value="<?php echo esc_attr($dodo_post_id); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th>Manuel Post ID (Sağlıklı)</th>
                            <td><input type="number" name="manual_post_id" value="<?php echo esc_attr($manual_post_id); ?>" class="regular-text"></td>
                        </tr>
                    </table>
                    <p><button type="submit" class="button button-primary">Karşılaştır</button></p>
                </form>
            </div>
            
            <?php if ($dodo_post_id && $manual_post_id): ?>
                <?php $this->render_post_comparison($dodo_post_id, $manual_post_id); ?>
            <?php endif; ?>
            
            <!-- Hard Clean -->
            <div class="card" style="max-width: 100%; margin-top: 20px;">
                <h2 style="color: #d63638;">⚠️ Hard Clean (Tehlikeli)</h2>
                <p>Bu işlem seçilen postun tüm Rank Math ve DODO meta verilerini siler, içeriği temizler.</p>
                <form method="post" onsubmit="return confirm('Bu işlem geri alınamaz! Devam etmek istediğinizden emin misiniz?');">
                    <?php wp_nonce_field('dodo_debug_nonce'); ?>
                    <table class="form-table">
                        <tr>
                            <th>Post ID</th>
                            <td><input type="number" name="hard_clean_post_id" class="regular-text" required></td>
                        </tr>
                    </table>
                    <p><button type="submit" name="dodo_hard_clean" class="button button-secondary">Hard Clean Yap</button></p>
                </form>
            </div>
            
            <!-- Otomatik Test -->
            <div class="card" style="max-width: 100%; margin-top: 20px;">
                <h2>🧪 Hızlı Rank Math SEO Test</h2>
                <p><strong>Bu test:</strong> Focus keyword ve external link'leri hızlıca test eder. 3000 kelime validasyonunu bypass eder.</p>
                <form method="post">
                    <?php wp_nonce_field('dodo_debug_nonce'); ?>
                    <table class="form-table">
                        <tr>
                            <th>Odak Anahtar Kelime</th>
                            <td><input type="text" name="test_focus_keyword" value="test keyword" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th>Konu</th>
                            <td><input type="text" name="test_topic" value="Test konusu hakkında kısa bir yazı" class="regular-text" required></td>
                        </tr>
                    </table>
                    <p>
                        <button type="submit" name="dodo_quick_seo_test" class="button button-primary">⚡ Hızlı SEO Test (30 saniye)</button>
                    </p>
                    <p class="description">
                        ✅ Focus keyword Rank Math'e yazılacak<br>
                        ✅ 2 external link eklenecek (1 dofollow, 1 nofollow)<br>
                        ✅ SEO title, meta description, slug oluşturulacak<br>
                        ⚠️ Kısa içerik (3000 kelime validasyonu bypass edilecek)
                    </p>
                </form>
                
                <?php
                // Hızlı SEO test
                if (isset($_POST['dodo_quick_seo_test'])) {
                    check_admin_referer('dodo_debug_nonce');
                    $this->run_quick_seo_test(
                        sanitize_text_field(wp_unslash($_POST['test_focus_keyword'])),
                        sanitize_text_field(wp_unslash($_POST['test_topic']))
                    );
                }
                ?>
            </div>
            
            <!-- Otomatik Test (Eski) -->
            <div class="card" style="max-width: 100%; margin-top: 20px;">
                <h2>🧪 Manuel Test (Ultra-Minimal Mod)</h2>
                <p>Ultra-minimal modda test yazısı oluştur ve sonuçları göster.</p>
                <form method="post">
                    <?php wp_nonce_field('dodo_debug_nonce'); ?>
                    <table class="form-table">
                        <tr>
                            <th>Test Başlığı</th>
                            <td><input type="text" name="test_title" value="DODO Test - Ultra Minimal Mod" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th>Test İçeriği</th>
                            <td>
                                <textarea name="test_content" rows="5" class="large-text">
<h2>Test Başlığı</h2>
<p>Bu ultra-minimal modda oluşturulmuş bir test yazısıdır.</p>
<ul>
<li>Sadece temel HTML tagları</li>
<li>Hiç meta veri yok</li>
<li>Rank Math paneli açılmalı</li>
</ul>
                                </textarea>
                            </td>
                        </tr>
                    </table>
                    <p>
                        <button type="submit" name="dodo_auto_test" class="button button-primary">Test Yazısı Oluştur</button>
                        <button type="submit" name="dodo_test_without_rankmath" class="button button-secondary" style="margin-left: 10px;">Rank Math Olmadan Test</button>
                    </p>
                </form>
                
                <?php
                // Otomatik test
                if (isset($_POST['dodo_auto_test'])) {
                    check_admin_referer('dodo_debug_nonce');
                    $this->run_auto_test(
                        sanitize_text_field(wp_unslash($_POST['test_title'])),
                        wp_kses_post(wp_unslash($_POST['test_content']))
                    );
                }
                
                // Rank Math olmadan test
                if (isset($_POST['dodo_test_without_rankmath'])) {
                    check_admin_referer('dodo_debug_nonce');
                    $this->run_test_without_rankmath(
                        sanitize_text_field(wp_unslash($_POST['test_title'])),
                        wp_kses_post(wp_unslash($_POST['test_content']))
                    );
                }
                ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Post karşılaştırmasını render et
     */
    private function render_post_comparison($dodo_id, $manual_id) {
        $dodo_post = get_post($dodo_id);
        $manual_post = get_post($manual_id);
        
        if (!$dodo_post || !$manual_post) {
            echo '<div class="notice notice-error"><p>Post bulunamadı!</p></div>';
            return;
        }
        
        ?>
        <div class="card" style="max-width: 100%; margin-top: 20px;">
            <h2>Karşılaştırma Sonuçları</h2>
            
            <h3>Temel Post Verileri</h3>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Alan</th>
                        <th>DODO Post #<?php echo $dodo_id; ?></th>
                        <th>Manuel Post #<?php echo $manual_id; ?></th>
                        <th>Durum</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $fields = array(
                        'post_title' => 'Başlık',
                        'post_status' => 'Durum',
                        'post_type' => 'Tip',
                        'post_author' => 'Yazar ID',
                    );
                    
                    foreach ($fields as $field => $label) {
                        $dodo_val = $dodo_post->$field;
                        $manual_val = $manual_post->$field;
                        $match = $dodo_val === $manual_val;
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html($label); ?></strong></td>
                            <td><?php echo esc_html($dodo_val); ?></td>
                            <td><?php echo esc_html($manual_val); ?></td>
                            <td><?php echo $match ? '✅' : '⚠️'; ?></td>
                        </tr>
                        <?php
                    }
                    ?>
                    <tr>
                        <td><strong>İçerik Uzunluğu</strong></td>
                        <td><?php echo strlen($dodo_post->post_content); ?> karakter</td>
                        <td><?php echo strlen($manual_post->post_content); ?> karakter</td>
                        <td>-</td>
                    </tr>
                    <tr>
                        <td><strong>Excerpt</strong></td>
                        <td><?php echo empty($dodo_post->post_excerpt) ? '❌ Boş' : '✅ Var (' . strlen($dodo_post->post_excerpt) . ')'; ?></td>
                        <td><?php echo empty($manual_post->post_excerpt) ? '❌ Boş' : '✅ Var (' . strlen($manual_post->post_excerpt) . ')'; ?></td>
                        <td>-</td>
                    </tr>
                </tbody>
            </table>
            
            <h3 style="margin-top: 30px;">Rank Math Meta Verileri</h3>
            <?php $this->render_meta_comparison($dodo_id, $manual_id, 'rank_math'); ?>
            
            <h3 style="margin-top: 30px;">DODO Meta Verileri</h3>
            <?php $this->render_meta_comparison($dodo_id, $manual_id, '_dodo'); ?>
            
            <h3 style="margin-top: 30px;">Tüm Meta Verileri</h3>
            <?php $this->render_all_meta($dodo_id, $manual_id); ?>
        </div>
        <?php
    }
    
    /**
     * Meta karşılaştırmasını render et
     */
    private function render_meta_comparison($dodo_id, $manual_id, $prefix) {
        global $wpdb;
        
        $dodo_meta = $wpdb->get_results($wpdb->prepare(
            "SELECT meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key LIKE %s",
            $dodo_id,
            $prefix . '%'
        ));
        
        $manual_meta = $wpdb->get_results($wpdb->prepare(
            "SELECT meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key LIKE %s",
            $manual_id,
            $prefix . '%'
        ));
        
        // Manuel meta'yı key-value array'e çevir
        $manual_meta_array = array();
        foreach ($manual_meta as $meta) {
            $manual_meta_array[$meta->meta_key] = $meta->meta_value;
        }
        
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Meta Key</th>
                    <th>DODO Value</th>
                    <th>Manuel Value</th>
                    <th>Tip</th>
                    <th>Durum</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dodo_meta as $meta): ?>
                    <?php
                    $dodo_value = $meta->meta_value;
                    $manual_value = isset($manual_meta_array[$meta->meta_key]) ? $manual_meta_array[$meta->meta_key] : null;
                    
                    // Tip kontrolü
                    $is_serialized = is_serialized($dodo_value);
                    $is_json = $this->is_json($dodo_value);
                    $is_array = is_array(maybe_unserialize($dodo_value));
                    
                    $type = 'string';
                    if ($is_serialized) $type = 'serialized';
                    if ($is_json) $type = 'JSON';
                    if ($is_array) $type = 'array';
                    
                    $warning = ($is_serialized || $is_json || $is_array) ? '🔴' : '✅';
                    $exists_in_manual = $manual_value !== null ? '✅' : '❌';
                    ?>
                    <tr style="<?php echo ($is_serialized || $is_json || $is_array) ? 'background: #ffebee;' : ''; ?>">
                        <td><code><?php echo esc_html($meta->meta_key); ?></code></td>
                        <td>
                            <details>
                                <summary><?php echo esc_html(substr($dodo_value, 0, 50)) . (strlen($dodo_value) > 50 ? '...' : ''); ?></summary>
                                <pre style="font-size: 11px; max-height: 200px; overflow: auto;"><?php echo esc_html($dodo_value); ?></pre>
                            </details>
                        </td>
                        <td>
                            <?php if ($manual_value !== null): ?>
                                <details>
                                    <summary><?php echo esc_html(substr($manual_value, 0, 50)) . (strlen($manual_value) > 50 ? '...' : ''); ?></summary>
                                    <pre style="font-size: 11px; max-height: 200px; overflow: auto;"><?php echo esc_html($manual_value); ?></pre>
                                </details>
                            <?php else: ?>
                                <em>Yok</em>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $warning . ' ' . esc_html($type); ?></td>
                        <td><?php echo $exists_in_manual; ?></td>
                    </tr>
                <?php endforeach; ?>
                
                <?php if (empty($dodo_meta)): ?>
                    <tr>
                        <td colspan="5"><em>Meta veri bulunamadı</em></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
    }
    
    /**
     * Tüm meta verileri göster
     */
    private function render_all_meta($dodo_id, $manual_id) {
        $dodo_all = get_post_meta($dodo_id);
        $manual_all = get_post_meta($manual_id);
        
        // DODO'da olup Manuel'de olmayan keyler
        $dodo_only = array_diff_key($dodo_all, $manual_all);
        
        if (!empty($dodo_only)) {
            ?>
            <div style="background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin-bottom: 20px;">
                <h4>⚠️ Sadece DODO'da Olan Meta Keyler (<?php echo count($dodo_only); ?>)</h4>
                <ul>
                    <?php foreach (array_keys($dodo_only) as $key): ?>
                        <li><code><?php echo esc_html($key); ?></code></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php
        }
        
        echo '<p><strong>DODO Toplam Meta:</strong> ' . count($dodo_all) . ' | <strong>Manuel Toplam Meta:</strong> ' . count($manual_all) . '</p>';
    }
    
    /**
     * JSON kontrolü
     */
    private function is_json($string) {
        if (!is_string($string)) return false;
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }
    
    /**
     * AJAX: Hard clean post
     * HARDENED: Final Completion Phase - AJAX Hardening
     */
    public function ajax_hard_clean_post() {
        try {
            // Defensive nonce check
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dodo_ajax_nonce')) {
                wp_send_json_error(array('message' => 'Güvenlik doğrulaması başarısız'));
                return;
            }
            
            // Capability check
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => 'Yetkiniz yok'));
                return;
            }
            
            // Validate post ID
            $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
            if (!$post_id) {
                wp_send_json_error(array('message' => 'Geçersiz post ID'));
                return;
            }
            
            // Execute hard clean
            $this->hard_clean_post($post_id);
            
            wp_send_json_success(array('message' => 'Post temizlendi'));
            
        } catch (Throwable $e) {
            error_log('[DODO AJAX ERROR] ajax_hard_clean_post: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => 'İşlem sırasında hata oluştu',
                'error' => WP_DEBUG ? $e->getMessage() : null
            ));
        }
    }
    
    /**
     * Postu tamamen temizle
     */
    private function hard_clean_post($post_id) {
        global $wpdb;
        
        // Tüm rank_math metaları sil
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key LIKE 'rank_math%%'",
            $post_id
        ));
        
        // Tüm _dodo metaları sil
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key LIKE '_dodo%%'",
            $post_id
        ));
        
        // Yoast metaları sil
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key LIKE '_yoast%%'",
            $post_id
        ));
        
        // Schema/JSON/FAQ metaları sil
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->postmeta} WHERE post_id = %d AND (meta_key LIKE '%%schema%%' OR meta_key LIKE '%%json%%' OR meta_key LIKE '%%faq%%' OR meta_key LIKE '%%howto%%')",
            $post_id
        ));
        
        // Post içeriğini temizle
        $post = get_post($post_id);
        if ($post) {
            $clean_content = $this->ultra_clean_content($post->post_content);
            
            wp_update_post(array(
                'ID' => $post_id,
                'post_content' => $clean_content,
                'post_excerpt' => '',
                'post_status' => 'draft',
            ));
        }
    }
    
    /**
     * İçeriği ultra temizle
     */
    private function ultra_clean_content($content) {
        // Tehlikeli tagları kaldır
        $dangerous_tags = array('script', 'style', 'iframe', 'form', 'input', 'textarea', 'button', 'select', 'option', 'html', 'head', 'body');
        
        foreach ($dangerous_tags as $tag) {
            $content = preg_replace('/<' . $tag . '[^>]*>.*?<\/' . $tag . '>/is', '', $content);
            $content = preg_replace('/<' . $tag . '[^>]*\/?>/is', '', $content);
        }
        
        // Kod bloklarını temizle
        $content = preg_replace('/```html\s*/i', '', $content);
        $content = preg_replace('/```\s*$/m', '', $content);
        $content = preg_replace('/```/m', '', $content);
        
        // İzin verilen taglar
        $allowed_tags = '<h2><h3><p><ul><ol><li><strong><em><a><blockquote><table><thead><tbody><tr><th><td>';
        $content = strip_tags($content, $allowed_tags);
        
        return trim($content);
    }
    
    /**
     * Hızlı SEO test - Focus keyword ve external link testi
     * 3000 kelime validasyonunu bypass eder
     */
    private function run_quick_seo_test($focus_keyword, $topic) {
        echo '<div style="background: #d1ecf1; padding: 20px; border-left: 4px solid #0c5460; margin-top: 20px;">';
        echo '<h3>⚡ Hızlı SEO Test Sonuçları</h3>';
        
        echo '<p><strong>Odak Anahtar Kelime:</strong> ' . esc_html($focus_keyword) . '</p>';
        echo '<p><strong>Konu:</strong> ' . esc_html($topic) . '</p>';
        
        // Kısa test içeriği oluştur (3000 kelime validasyonunu bypass etmek için)
        $test_content = "## " . $topic . "\n\n";
        $test_content .= $focus_keyword . " hakkında detaylı bilgi. Bu bir test yazısıdır.\n\n";
        $test_content .= "### Alt Başlık 1\n\n";
        $test_content .= "Lorem ipsum dolor sit amet, consectetur adipiscing elit. " . $focus_keyword . " kullanımı çok önemlidir. ";
        $test_content .= "Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. ";
        $test_content .= "Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris.\n\n";
        
        // External linkler ekle
        $test_content .= "### Dış Kaynaklar\n\n";
        $test_content .= $focus_keyword . " hakkında daha fazla bilgi için [Wikipedia'daki detaylı makaleye](https://tr.wikipedia.org/wiki/" . urlencode(str_replace(' ', '_', $focus_keyword)) . "){:target=\"_blank\" rel=\"noopener\"} göz atabilirsiniz.\n\n";
        $test_content .= "Daha detaylı teknik bilgi için [İngilizce Wikipedia makalesine](https://en.wikipedia.org/wiki/" . urlencode(str_replace(' ', '_', $focus_keyword)) . "){:target=\"_blank\" rel=\"nofollow noopener\"} bakabilirsiniz.\n\n";
        
        $test_content .= "### Sonuç\n\n";
        $test_content .= $focus_keyword . " konusunda önemli noktaları ele aldık. Bu test yazısı SEO meta verilerini kontrol etmek içindir.";
        
        // Post creator ile HTML'e çevir
        $post_creator = new DODO_Post_Creator();
        $html_content = $post_creator->markdown_to_html($test_content);
        
        // SEO meta verileri oluştur
        $seo_title = $focus_keyword . " - Test Yazısı";
        if (mb_strlen($seo_title) > 60) {
            $seo_title = mb_substr($seo_title, 0, 57) . '...';
        }
        
        $meta_description = $focus_keyword . " hakkında detaylı test yazısı. SEO meta verilerini kontrol etmek için oluşturuldu.";
        if (mb_strlen($meta_description) > 160) {
            $meta_description = mb_substr($meta_description, 0, 157) . '...';
        }
        
        $slug = sanitize_title($focus_keyword . '-test');
        
        // Post verilerini hazırla
        $post_data = array(
            'title' => $seo_title,
            'content' => $html_content,
            'excerpt' => $meta_description,
            'slug' => $slug,
            'status' => 'draft',
            'category_id' => 0,
            'focus_keyword' => $focus_keyword,
            'tone' => 'technical',
            'content_type' => 'blog',
            'length' => 'short',
            'tags' => array('test', 'seo', $focus_keyword),
        );
        
        $seo_data = array(
            'focus_keyword' => $focus_keyword,
            'seo_title' => $seo_title,
            'meta_description' => $meta_description,
            'slug' => $slug,
        );
        
        echo '<h4>📝 Oluşturulan Veriler</h4>';
        echo '<ul>';
        echo '<li><strong>SEO Title:</strong> ' . esc_html($seo_title) . ' (' . mb_strlen($seo_title) . ' karakter)</li>';
        echo '<li><strong>Meta Description:</strong> ' . esc_html($meta_description) . ' (' . mb_strlen($meta_description) . ' karakter)</li>';
        echo '<li><strong>Slug:</strong> ' . esc_html($slug) . '</li>';
        echo '<li><strong>Focus Keyword:</strong> ' . esc_html($focus_keyword) . '</li>';
        echo '<li><strong>External Links:</strong> 2 (1 dofollow, 1 nofollow)</li>';
        echo '</ul>';
        
        // Post oluştur
        echo '<p>⏳ Post oluşturuluyor...</p>';
        
        $post_id = $post_creator->create_post($post_data, $seo_data);
        
        if (is_wp_error($post_id)) {
            echo '<p style="color: red;">❌ <strong>Post oluşturulamadı:</strong> ' . $post_id->get_error_message() . '</p>';
            echo '</div>';
            return;
        }
        
        echo '<p style="color: green; font-size: 16px;">✅ <strong>Post başarıyla oluşturuldu!</strong></p>';
        echo '<p><strong>Post ID:</strong> ' . $post_id . '</p>';
        
        // Rank Math meta verilerini kontrol et
        echo '<h4>🔍 Rank Math Meta Kontrol</h4>';
        
        $saved_focus_keyword = get_post_meta($post_id, 'rank_math_focus_keyword', true);
        $saved_focus_keywords = get_post_meta($post_id, 'rank_math_focus_keywords', true);
        $saved_title = get_post_meta($post_id, 'rank_math_title', true);
        $saved_description = get_post_meta($post_id, 'rank_math_description', true);
        
        echo '<table class="wp-list-table widefat fixed striped" style="margin-top: 10px;">';
        echo '<thead><tr><th>Meta Key</th><th>Değer</th><th>Tip</th><th>Durum</th></tr></thead>';
        echo '<tbody>';
        
        // Focus keyword (string)
        $focus_ok = ($saved_focus_keyword === $focus_keyword);
        echo '<tr>';
        echo '<td><code>rank_math_focus_keyword</code></td>';
        echo '<td>' . esc_html($saved_focus_keyword) . '</td>';
        echo '<td>' . gettype($saved_focus_keyword) . '</td>';
        echo '<td>' . ($focus_ok ? '✅' : '❌') . '</td>';
        echo '</tr>';
        
        // Focus keywords (array)
        $focus_array_ok = (is_array($saved_focus_keywords) && in_array($focus_keyword, $saved_focus_keywords));
        echo '<tr>';
        echo '<td><code>rank_math_focus_keywords</code></td>';
        echo '<td>' . (is_array($saved_focus_keywords) ? 'Array(' . implode(', ', $saved_focus_keywords) . ')' : esc_html($saved_focus_keywords)) . '</td>';
        echo '<td>' . gettype($saved_focus_keywords) . '</td>';
        echo '<td>' . ($focus_array_ok ? '✅' : '❌') . '</td>';
        echo '</tr>';
        
        // SEO Title
        $title_ok = !empty($saved_title) && stripos($saved_title, $focus_keyword) !== false;
        echo '<tr>';
        echo '<td><code>rank_math_title</code></td>';
        echo '<td>' . esc_html($saved_title) . ' (' . mb_strlen($saved_title) . ' karakter)</td>';
        echo '<td>' . gettype($saved_title) . '</td>';
        echo '<td>' . ($title_ok ? '✅' : '❌') . '</td>';
        echo '</tr>';
        
        // Meta Description
        $desc_ok = !empty($saved_description) && stripos($saved_description, $focus_keyword) !== false;
        echo '<tr>';
        echo '<td><code>rank_math_description</code></td>';
        echo '<td>' . esc_html(mb_substr($saved_description, 0, 80)) . '... (' . mb_strlen($saved_description) . ' karakter)</td>';
        echo '<td>' . gettype($saved_description) . '</td>';
        echo '<td>' . ($desc_ok ? '✅' : '❌') . '</td>';
        echo '</tr>';
        
        echo '</tbody></table>';
        
        // External link kontrol
        echo '<h4>🔗 External Link Kontrol</h4>';
        $post = get_post($post_id);
        $content = $post->post_content;
        
        // Dofollow link kontrol (rel="noopener" ama rel="nofollow" YOK)
        preg_match_all('/<a[^>]+href="https?:\/\/[^"]*wikipedia[^"]*"[^>]*>/i', $content, $wiki_links);
        $dofollow_count = 0;
        $nofollow_count = 0;
        
        foreach ($wiki_links[0] as $link) {
            if (preg_match('/rel="([^"]+)"/', $link, $rel_match)) {
                $rel = $rel_match[1];
                if (strpos($rel, 'nofollow') === false) {
                    $dofollow_count++;
                    echo '<p style="color: green;">✅ <strong>DOFOLLOW link bulundu:</strong> <code>' . esc_html($rel) . '</code></p>';
                } else {
                    $nofollow_count++;
                    echo '<p style="color: orange;">⚠️ <strong>NOFOLLOW link bulundu:</strong> <code>' . esc_html($rel) . '</code></p>';
                }
            }
        }
        
        echo '<p><strong>Toplam external link:</strong> ' . count($wiki_links[0]) . '</p>';
        echo '<p><strong>Dofollow:</strong> ' . $dofollow_count . '</p>';
        echo '<p><strong>Nofollow:</strong> ' . $nofollow_count . '</p>';
        
        // Sonuç özeti
        echo '<hr>';
        echo '<h4>📊 Test Özeti</h4>';
        
        $all_ok = $focus_ok && $focus_array_ok && $title_ok && $desc_ok && ($dofollow_count >= 1);
        
        if ($all_ok) {
            echo '<p style="color: green; font-size: 18px; font-weight: bold;">✅ TÜM TESTLER BAŞARILI!</p>';
            echo '<ul style="color: green;">';
            echo '<li>✅ Focus keyword STRING olarak kaydedildi</li>';
            echo '<li>✅ Focus keywords ARRAY olarak kaydedildi</li>';
            echo '<li>✅ SEO title focus keyword içeriyor</li>';
            echo '<li>✅ Meta description focus keyword içeriyor</li>';
            echo '<li>✅ En az 1 dofollow external link var</li>';
            echo '</ul>';
        } else {
            echo '<p style="color: red; font-size: 18px; font-weight: bold;">❌ BAZI TESTLER BAŞARISIZ!</p>';
            echo '<ul>';
            echo '<li style="color: ' . ($focus_ok ? 'green' : 'red') . ';">' . ($focus_ok ? '✅' : '❌') . ' Focus keyword STRING</li>';
            echo '<li style="color: ' . ($focus_array_ok ? 'green' : 'red') . ';">' . ($focus_array_ok ? '✅' : '❌') . ' Focus keywords ARRAY</li>';
            echo '<li style="color: ' . ($title_ok ? 'green' : 'red') . ';">' . ($title_ok ? '✅' : '❌') . ' SEO title focus keyword içeriyor</li>';
            echo '<li style="color: ' . ($desc_ok ? 'green' : 'red') . ';">' . ($desc_ok ? '✅' : '❌') . ' Meta description focus keyword içeriyor</li>';
            echo '<li style="color: ' . ($dofollow_count >= 1 ? 'green' : 'red') . ';">' . ($dofollow_count >= 1 ? '✅' : '❌') . ' En az 1 dofollow external link</li>';
            echo '</ul>';
        }
        
        echo '<hr>';
        echo '<p><a href="' . get_edit_post_link($post_id) . '" target="_blank" class="button button-primary" style="font-size: 16px; padding: 10px 20px;">📝 Post\'u Düzenle ve Rank Math Panelini Kontrol Et</a></p>';
        echo '<p class="description">Rank Math panelini açın ve focus keyword alanının dolu olduğunu, external links bölümünde dofollow link olduğunu kontrol edin.</p>';
        
        echo '</div>';
    }
    
    /**
     * Otomatik test çalıştır
     */
    private function run_auto_test($title, $content) {
        echo '<div style="background: #e7f3ff; padding: 20px; border-left: 4px solid #2271b1; margin-top: 20px;">';
        echo '<h3>🧪 Test Sonuçları</h3>';
        
        // Test post oluştur
        $post_id = wp_insert_post(array(
            'post_title' => sanitize_text_field($title),
            'post_content' => wp_kses_post($content),
            'post_status' => 'draft',
            'post_type' => 'post',
            'post_author' => get_current_user_id(),
        ));
        
        if (is_wp_error($post_id)) {
            echo '<p style="color: red;">❌ Post oluşturulamadı: ' . $post_id->get_error_message() . '</p>';
            echo '</div>';
            return;
        }
        
        echo '<p>✅ <strong>Test post oluşturuldu!</strong></p>';
        echo '<p><strong>Post ID:</strong> ' . $post_id . '</p>';
        echo '<p><strong>Düzenle:</strong> <a href="' . get_edit_post_link($post_id) . '" target="_blank">Post\'u Aç</a></p>';
        
        // Meta verileri kontrol et
        $all_meta = get_post_meta($post_id);
        
        echo '<h4>📊 Oluşturulan Meta Veriler</h4>';
        
        if (empty($all_meta)) {
            echo '<p style="color: green;">✅ <strong>Hiç meta veri yok! (İdeal durum)</strong></p>';
        } else {
            echo '<p style="color: orange;">⚠️ Toplam ' . count($all_meta) . ' meta veri bulundu:</p>';
            echo '<ul>';
            foreach ($all_meta as $key => $values) {
                $value = $values[0];
                $type = gettype($value);
                
                if (is_serialized($value)) {
                    $type = 'serialized';
                    $color = 'red';
                } elseif ($this->is_json($value)) {
                    $type = 'JSON';
                    $color = 'red';
                } elseif (is_array($value)) {
                    $type = 'array';
                    $color = 'red';
                } else {
                    $color = 'green';
                }
                
                echo '<li style="color: ' . $color . ';"><code>' . esc_html($key) . '</code> (' . $type . ')</li>';
            }
            echo '</ul>';
        }
        
        // Rank Math meta kontrol
        $rank_math_meta = array();
        foreach ($all_meta as $key => $values) {
            if (strpos($key, 'rank_math') === 0) {
                $rank_math_meta[$key] = $values[0];
            }
        }
        
        echo '<h4>🔍 Rank Math Meta Kontrol</h4>';
        if (empty($rank_math_meta)) {
            echo '<p style="color: green;">✅ <strong>Hiç Rank Math meta yok! (Ultra-minimal mod çalışıyor)</strong></p>';
        } else {
            echo '<p style="color: red;">❌ ' . count($rank_math_meta) . ' Rank Math meta bulundu:</p>';
            echo '<ul>';
            foreach ($rank_math_meta as $key => $value) {
                echo '<li><code>' . esc_html($key) . '</code></li>';
            }
            echo '</ul>';
        }
        
        // İçerik kontrol
        $post = get_post($post_id);
        $has_script = (strpos($post->post_content, '<script') !== false);
        $has_style = (strpos($post->post_content, '<style') !== false);
        $has_iframe = (strpos($post->post_content, '<iframe') !== false);
        
        echo '<h4>🧹 İçerik Temizlik Kontrol</h4>';
        echo '<ul>';
        echo '<li style="color: ' . ($has_script ? 'red' : 'green') . ';">' . ($has_script ? '❌' : '✅') . ' Script tag</li>';
        echo '<li style="color: ' . ($has_style ? 'red' : 'green') . ';">' . ($has_style ? '❌' : '✅') . ' Style tag</li>';
        echo '<li style="color: ' . ($has_iframe ? 'red' : 'green') . ';">' . ($has_iframe ? '❌' : '✅') . ' Iframe tag</li>';
        echo '</ul>';
        
        // Sonuç
        echo '<hr>';
        echo '<h4>📝 Test Özeti</h4>';
        
        $is_clean = empty($rank_math_meta) && !$has_script && !$has_style && !$has_iframe;
        
        if ($is_clean) {
            echo '<p style="color: green; font-size: 16px; font-weight: bold;">✅ POST TAMAMEN TEMİZ!</p>';
            echo '<p>Şimdi bu postu düzenleyin ve Rank Math paneline tıklayın.</p>';
            echo '<p><strong>Beklenen:</strong> Rank Math paneli açılmalı, hata vermemeli.</p>';
            echo '<p><a href="' . get_edit_post_link($post_id) . '" target="_blank" class="button button-primary">Post\'u Düzenle ve Test Et</a></p>';
        } else {
            echo '<p style="color: red; font-size: 16px; font-weight: bold;">❌ POST TEMİZ DEĞİL!</p>';
            echo '<p>Ultra-minimal mod düzgün çalışmıyor. Kod kontrol edilmeli.</p>';
        }
        
        echo '</div>';
    }
    
    /**
     * Rank Math olmadan test
     */
    private function run_test_without_rankmath($title, $content) {
        echo '<div style="background: #fff3cd; padding: 20px; border-left: 4px solid #ffc107; margin-top: 20px;">';
        echo '<h3>⚠️ Rank Math Devre Dışı Test</h3>';
        
        // Rank Math'i tamamen devre dışı bırak
        if (!defined('RANK_MATH_DISABLE')) {
            define('RANK_MATH_DISABLE', true);
        }
        
        // Test post oluştur
        $post_id = wp_insert_post(array(
            'post_title' => sanitize_text_field($title) . ' (No RM)',
            'post_content' => wp_kses_post($content),
            'post_status' => 'draft',
            'post_type' => 'post',
            'post_author' => get_current_user_id(),
        ));
        
        if (is_wp_error($post_id)) {
            echo '<p style="color: red;">❌ Post oluşturulamadı: ' . $post_id->get_error_message() . '</p>';
            echo '</div>';
            return;
        }
        
        // Tüm Rank Math meta'larını sil
        global $wpdb;
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key LIKE 'rank_math%%'",
            $post_id
        ));
        
        echo '<p>✅ <strong>Test post oluşturuldu! (Rank Math devre dışı)</strong></p>';
        echo '<p><strong>Post ID:</strong> ' . $post_id . '</p>';
        echo '<p><strong>Silinen Rank Math meta:</strong> ' . $deleted . '</p>';
        echo '<p><a href="' . get_edit_post_link($post_id) . '" target="_blank" class="button button-primary">Post\'u Aç ve Test Et</a></p>';
        
        // Meta kontrol
        $all_meta = get_post_meta($post_id);
        $rank_math_meta = array();
        foreach ($all_meta as $key => $values) {
            if (strpos($key, 'rank_math') === 0) {
                $rank_math_meta[$key] = $values[0];
            }
        }
        
        if (empty($rank_math_meta)) {
            echo '<p style="color: green; font-size: 16px; font-weight: bold;">✅ HİÇ RANK MATH META YOK!</p>';
            echo '<p>Bu post\'ta Rank Math paneli açılmalı.</p>';
        } else {
            echo '<p style="color: red;">❌ Hala ' . count($rank_math_meta) . ' Rank Math meta var:</p>';
            echo '<ul>';
            foreach ($rank_math_meta as $key => $value) {
                echo '<li><code>' . esc_html($key) . '</code></li>';
            }
            echo '</ul>';
            echo '<p><strong>Sonuç:</strong> Rank Math bu meta\'ları post oluşturulduktan SONRA başka bir işlemde ekliyor.</p>';
        }
        
        echo '</div>';
    }
    
    /**
     * Anahtar Kelime Fırsatları sayfasını render et
     */
    public function render_keyword_opportunities_page() {
        // Yetki kontrolü
        if (!current_user_can('manage_options')) {
            wp_die(__('Bu sayfaya erişim yetkiniz yok.', 'dodo-ai-seo'));
        }
        
        // Settings instance
        $settings = new DODO_Settings();
        
        // API key kontrolü
        if (!$settings->is_api_key_valid()) {
            $this->render_api_key_warning();
            return;
        }
        
        // View dosyasını include et
        include DODO_PLUGIN_DIR . 'admin/views/page-keyword-opportunities.php';
    }
    
    /**
     * Publishing Scheduler sayfasını render et
     */
    public function render_publishing_scheduler_page() {
        // Yetki kontrolü
        if (!current_user_can('manage_options')) {
            wp_die(__('Bu sayfaya erişim yetkiniz yok.', 'dodo-ai-seo'));
        }
        
        // View dosyasını include et
        include DODO_PLUGIN_DIR . 'admin/views/page-publishing-scheduler.php';
    }
    
    /**
     * AJAX: Keyword fırsatları üret
     * HARDENED: Final Completion Phase - AJAX Hardening
     */
    public function ajax_generate_keyword_opportunities() {
        // İlk log - handler başladı
        error_log('[DODO KW OPP] === AJAX HANDLER STARTED ===');
        
        try {
            // Nonce kontrolü
            error_log('[DODO KW OPP] Step 1: Nonce check');
            if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'dodo_ajax_nonce')) {
                error_log('[DODO KW OPP] Nonce check FAILED');
                wp_send_json_error(array(
                    'message' => __('Güvenlik doğrulaması başarısız. Sayfayı yenileyin.', 'dodo-ai-seo')
                ), 403);
                return;
            }
            error_log('[DODO KW OPP] Nonce check OK');
            
            // Yetki kontrolü
            error_log('[DODO KW OPP] Step 2: Capability check');
            if (!current_user_can('manage_options')) {
                error_log('[DODO KW OPP] Capability check FAILED');
                wp_send_json_error(array(
                    'message' => __('Bu işlem için yetkiniz yok.', 'dodo-ai-seo')
                ), 403);
                return;
            }
            error_log('[DODO KW OPP] Capability check OK');
            
            // Settings class kontrolü
            error_log('[DODO KW OPP] Step 3: Loading DODO_Settings');
            if (!class_exists('DODO_Settings')) {
                error_log('[DODO KW OPP] DODO_Settings class NOT FOUND');
                wp_send_json_error(array(
                    'message' => __('Settings modülü yüklenemedi.', 'dodo-ai-seo')
                ), 500);
                return;
            }
            
            $settings = new DODO_Settings();
            error_log('[DODO KW OPP] DODO_Settings loaded OK');
            
            // OpenAI API key kontrolü
            error_log('[DODO KW OPP] Step 4: API key check');
            $api_key = $settings->get_api_key();
            
            if (empty($api_key)) {
                error_log('[DODO KW OPP] API key is EMPTY');
                wp_send_json_error(array(
                    'message' => __('OpenAI API anahtarı tanımlanmamış. Lütfen ayarlar sayfasından API anahtarınızı girin.', 'dodo-ai-seo')
                ), 400);
                return;
            }
            error_log('[DODO KW OPP] API key exists');
            
            // Class exists kontrolü
            error_log('[DODO KW OPP] Step 5: Loading DODO_Keyword_Opportunities');
            if (!class_exists('DODO_Keyword_Opportunities')) {
                error_log('[DODO KW OPP] DODO_Keyword_Opportunities class NOT FOUND');
                wp_send_json_error(array(
                    'message' => __('Keyword Opportunities modülü yüklenemedi.', 'dodo-ai-seo')
                ), 500);
                return;
            }
            
            error_log('[DODO KW OPP] Step 6: Instantiating DODO_Keyword_Opportunities');
            $opportunities_manager = new DODO_Keyword_Opportunities();
            error_log('[DODO KW OPP] DODO_Keyword_Opportunities instantiated OK');
            
            // Tabloyu oluştur (ilk çalıştırmada)
            error_log('[DODO KW OPP] Step 7: Creating table');
            $opportunities_manager->create_table();
            error_log('[DODO KW OPP] Table created/verified OK');
            
            // Site içeriğini tara
            error_log('[DODO KW OPP] Step 8: Analyzing site content');
            $analysis = $opportunities_manager->analyze_site_content();
            
            if (empty($analysis) || !is_array($analysis)) {
                error_log('[DODO KW OPP] Site analysis FAILED or EMPTY');
                wp_send_json_error(array(
                    'message' => __('Site içeriği analiz edilemedi.', 'dodo-ai-seo')
                ), 500);
                return;
            }
            error_log('[DODO KW OPP] Site analysis OK - ' . count($analysis) . ' items');
            
            // AI ile fırsatlar üret
            error_log('[DODO KW OPP] Step 9: Generating opportunities with GSC + AI');
            $opportunities_result = $opportunities_manager->generate_opportunities($analysis);
            
            if (is_wp_error($opportunities_result)) {
                error_log('[DODO KW OPP] Generation FAILED: ' . $opportunities_result->get_error_message());
                wp_send_json_error(array(
                    'message' => __('Fırsatlar üretilemedi: ', 'dodo-ai-seo') . $opportunities_result->get_error_message()
                ), 500);
                return;
            }
            
            // Handle both old format (array) and new format (array with metadata)
            if (isset($opportunities_result['opportunities'])) {
                $opportunities = $opportunities_result['opportunities'];
                $source_breakdown = $opportunities_result['source_breakdown'] ?? array();
            } else {
                $opportunities = $opportunities_result;
                $source_breakdown = array();
            }
            
            if (empty($opportunities) || !is_array($opportunities)) {
                error_log('[DODO KW OPP] No opportunities generated');
                wp_send_json_error(array(
                    'message' => __('Hiçbir fırsat üretilemedi. Lütfen tekrar deneyin.', 'dodo-ai-seo')
                ), 500);
                return;
            }
            error_log('[DODO KW OPP] Generated ' . count($opportunities) . ' opportunities');
            
            // Veritabanına kaydet
            error_log('[DODO KW OPP] Step 10: Saving to database');
            $save_result = $opportunities_manager->save_opportunities($opportunities_result, $analysis);
            
            // Handle both old format (int) and new format (array)
            $saved_count = is_array($save_result) ? $save_result['saved_count'] : $save_result;
            $duplicate_count = is_array($save_result) ? $save_result['duplicate_count'] : 0;
            $error_count = is_array($save_result) ? $save_result['error_count'] : 0;
            
            if ($saved_count === 0) {
                global $wpdb;
                $db_error = $wpdb->last_error;
                error_log('[DODO KW OPP] Database save FAILED. DB Error: ' . $db_error);
                
                // Eğer tüm fırsatlar duplicate ise farklı mesaj
                if ($duplicate_count > 0 && $error_count === 0) {
                    wp_send_json_error(array(
                        'message' => __('Tüm fırsatlar zaten mevcut (duplicate). Yeni fırsat bulunamadı.', 'dodo-ai-seo')
                    ), 400);
                } else {
                    wp_send_json_error(array(
                        'message' => __('Fırsatlar veritabanına kaydedilemedi.', 'dodo-ai-seo') . ($db_error ? ' DB Error: ' . $db_error : '')
                    ), 500);
                }
                return;
            }
            
            error_log('[DODO KW OPP] === SUCCESS: ' . $saved_count . ' opportunities saved ===');
            error_log('[DODO KW OPP] Source breakdown: ' . json_encode($source_breakdown));
            
            // Build success message with source breakdown
            $message_parts = array();
            $message_parts[] = sprintf(__('%d yeni fırsat oluşturuldu!', 'dodo-ai-seo'), $saved_count);
            
            if (!empty($source_breakdown)) {
                $source_labels = array(
                    'gsc_ctr' => 'GSC CTR',
                    'gsc_ranking' => 'GSC Sıralama',
                    'gsc_decay' => 'GSC Düşüş',
                    'gsc_cannibalization' => 'Kannibalizasyon',
                    'gsc_gap' => 'GSC Boşluk',
                    'ai_suggestion' => 'AI Öneri',
                );
                
                $breakdown_text = array();
                foreach ($source_breakdown as $source => $count) {
                    if ($count > 0) {
                        $label = $source_labels[$source] ?? $source;
                        $breakdown_text[] = "{$label}: {$count}";
                    }
                }
                
                if (!empty($breakdown_text)) {
                    $message_parts[] = '(' . implode(', ', $breakdown_text) . ')';
                }
            }
            
            if ($duplicate_count > 0) {
                $message_parts[] = sprintf(__('%d duplicate atlandı.', 'dodo-ai-seo'), $duplicate_count);
            }
            
            $final_message = implode(' ', $message_parts);
            
            wp_send_json_success(array(
                'message' => $final_message,
                'created_count' => $saved_count,
                'duplicate_count' => $duplicate_count,
                'error_count' => $error_count,
                'source_breakdown' => $source_breakdown,
            ));
            
        } catch (Throwable $e) {
            error_log('[DODO KW OPP] EXCEPTION: ' . $e->getMessage());
            error_log('[DODO KW OPP] Stack trace: ' . $e->getTraceAsString());
            
            wp_send_json_error(array(
                'message' => __('Beklenmeyen bir hata oluştu: ', 'dodo-ai-seo') . $e->getMessage()
            ), 500);
        }
    }
    
    /**
     * AJAX: Opportunity durumunu güncelle
            error_log('[DODO KW OPP] Error: ' . $e->getMessage());
            error_log('[DODO KW OPP] File: ' . $e->getFile() . ':' . $e->getLine());
            error_log('[DODO KW OPP] Stack trace: ' . $e->getTraceAsString());
            
            $debug_message = defined('WP_DEBUG') && WP_DEBUG ? $e->getMessage() : null;
            
            wp_send_json_error(array(
                'message' => __('Fırsatlar üretilirken beklenmeyen bir hata oluştu. Lütfen tekrar deneyin.', 'dodo-ai-seo'),
                'debug' => $debug_message,
                'error_file' => defined('WP_DEBUG') && WP_DEBUG ? basename($e->getFile()) : null,
                'error_line' => defined('WP_DEBUG') && WP_DEBUG ? $e->getLine() : null
            ), 500);
        }
    }
    
    /**
     * AJAX: Fırsat durumunu güncelle
     * HARDENED: Final Completion Phase - AJAX Hardening
     */
    public function ajax_update_opportunity_status() {
        try {
            // Defensive nonce check
            if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'dodo_ajax_nonce')) {
                DODO_Error_Handler::safe_ajax_error(__('Güvenlik doğrulaması başarısız.', 'dodo-ai-seo'), 403);
                return;
            }
            
            // Capability check
            if (!current_user_can('manage_options')) {
                DODO_Error_Handler::safe_ajax_error(__('Bu işlem için yetkiniz yok.', 'dodo-ai-seo'), 403);
                return;
            }
        
        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        
        // Publish mode parametreleri (opsiyonel)
        $publish_mode = isset($_POST['publish_mode']) ? sanitize_text_field($_POST['publish_mode']) : null;
        $scheduled_date = isset($_POST['scheduled_date']) ? sanitize_text_field($_POST['scheduled_date']) : null;
        $scheduled_time = isset($_POST['scheduled_time']) ? sanitize_text_field($_POST['scheduled_time']) : null;
        
        if (!$id || !$status) {
            wp_send_json_error(array(
                'message' => __('Geçersiz parametreler.', 'dodo-ai-seo')
            ));
        }
        
        // Publish mode validation
        if (!empty($publish_mode)) {
            $allowed_modes = array('default', 'draft', 'publish', 'scheduled');
            if (!in_array($publish_mode, $allowed_modes)) {
                wp_send_json_error(array(
                    'message' => __('Geçersiz publish mode.', 'dodo-ai-seo')
                ));
            }
        }
        
        // Date/time validation
        if (!empty($scheduled_date) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $scheduled_date)) {
            wp_send_json_error(array(
                'message' => __('Geçersiz tarih formatı.', 'dodo-ai-seo')
            ));
        }
        
        if (!empty($scheduled_time) && !preg_match('/^\d{2}:\d{2}$/', $scheduled_time)) {
            wp_send_json_error(array(
                'message' => __('Geçersiz saat formatı.', 'dodo-ai-seo')
            ));
        }
        
        // Boş string'leri null'a çevir
        if ($publish_mode === '') $publish_mode = null;
        if ($scheduled_date === '') $scheduled_date = null;
        if ($scheduled_time === '') $scheduled_time = null;
        
        error_log("[DODO QUEUE] Updating opportunity #{$id}: status={$status}, publish_mode={$publish_mode}, date={$scheduled_date}, time={$scheduled_time}");
        
        $opportunities_manager = new DODO_Keyword_Opportunities();
        $result = $opportunities_manager->update_status($id, $status, $publish_mode, $scheduled_date, $scheduled_time);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Durum güncellendi.', 'dodo-ai-seo')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Durum güncellenemedi.', 'dodo-ai-seo')
            ));
        }
        
        } catch (Throwable $e) {
            DODO_Error_Handler::safe_exception_response($e, 'ajax_update_opportunity_status');
        }
    }
    
    /**
     * AJAX: Tüm fırsatları sil
     * HARDENED: Final Completion Phase - AJAX Hardening
     */
    public function ajax_delete_all_opportunities() {
        try {
            // Defensive nonce check
            if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'dodo_ajax_nonce')) {
                DODO_Error_Handler::safe_ajax_error(__('Güvenlik doğrulaması başarısız.', 'dodo-ai-seo'), 403);
                return;
            }
            
            // Capability check
            if (!current_user_can('manage_options')) {
                DODO_Error_Handler::safe_ajax_error(__('Bu işlem için yetkiniz yok.', 'dodo-ai-seo'), 403);
                return;
            }
        
        $opportunities_manager = new DODO_Keyword_Opportunities();
        $result = $opportunities_manager->delete_all_opportunities();
        
        if ($result !== false) {
            wp_send_json_success(array(
                'message' => __('Tüm fırsatlar silindi.', 'dodo-ai-seo')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Fırsatlar silinemedi.', 'dodo-ai-seo')
            ));
        }
        
        } catch (Throwable $e) {
            DODO_Error_Handler::safe_exception_response($e, 'ajax_delete_all_opportunities');
        }
    }
    
    /**
     * AJAX: Process next queue item
     * HARDENED: Final Completion Phase - AJAX Hardening
     */
    public function ajax_process_queue_item() {
        try {
            // Defensive nonce check
            if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'dodo_ajax_nonce')) {
                DODO_Error_Handler::log_error_with_context('AJAX', 'Nonce verification failed for ajax_process_queue_item', array(
                    'user_id' => get_current_user_id()
                ));
                wp_send_json_error(array(
                    'message' => __('Güvenlik doğrulaması başarısız. Lütfen sayfayı yenileyin.', 'dodo-ai-seo')
                ), 403);
                return;
            }
            
            // Capability check
            if (!current_user_can('manage_options')) {
                DODO_Error_Handler::log_error_with_context('AJAX', 'Permission denied for ajax_process_queue_item', array(
                    'user_id' => get_current_user_id()
                ));
                wp_send_json_error(array(
                    'message' => __('Bu işlem için yetkiniz yok.', 'dodo-ai-seo')
                ), 403);
                return;
            }
            
            // Check class exists
            if (!class_exists('DODO_Keyword_Opportunities')) {
                DODO_Error_Handler::log_error_with_context('AJAX', 'DODO_Keyword_Opportunities class not found', array(
                    'user_id' => get_current_user_id()
                ));
                wp_send_json_error(array(
                    'message' => __('Queue modülü yüklenemedi.', 'dodo-ai-seo')
                ), 500);
                return;
            }
            
            $opportunities_manager = new DODO_Keyword_Opportunities();
            
            // Get next queued opportunity
            $opportunity = $opportunities_manager->get_next_queued_opportunity();
            
            if (!$opportunity) {
                // No queued items, check for retryable items
                $retryable = $opportunities_manager->get_retryable_items();
                
                if (!empty($retryable)) {
                    $opportunity = $retryable[0];
                    error_log("[DODO RETRY] Found retryable item #{$opportunity['id']}, retry_count: {$opportunity['retry_count']}");
                } else {
                    // Queue completed
                    wp_send_json_success(array('completed' => true));
                    return;
                }
            }
            
            $opp_id = $opportunity['id'];
            $keyword = $opportunity['keyword'];
            $retry_count = isset($opportunity['retry_count']) ? intval($opportunity['retry_count']) : 0;
            
            error_log("[DODO QUEUE] Processing opportunity #{$opp_id}: {$keyword} (retry: {$retry_count})");
            
            // Update status to processing
            $opportunities_manager->update_opportunity_result($opp_id, 'processing');
            
            // Get publish mode from opportunity item (if set), otherwise use global default
            $item_publish_mode = !empty($opportunity['publish_mode']) ? $opportunity['publish_mode'] : null;
            $item_scheduled_date = !empty($opportunity['scheduled_date']) ? $opportunity['scheduled_date'] : null;
            $item_scheduled_time = !empty($opportunity['scheduled_time']) ? $opportunity['scheduled_time'] : null;
            
            if ($item_publish_mode) {
                // Item has its own publish mode
                $publish_mode = $item_publish_mode;
                error_log("[DODO QUEUE] Using item's publish mode: {$publish_mode}");
                
                if ($item_scheduled_date && $item_scheduled_time) {
                    error_log("[DODO QUEUE] Using item's scheduled date/time: {$item_scheduled_date} {$item_scheduled_time}");
                }
            } else {
                // Use global default
                if (!class_exists('DODO_Settings')) {
                    DODO_Error_Handler::log_error_with_context('QUEUE', 'DODO_Settings class not found', array(
                        'opportunity_id' => $opp_id
                    ));
                    wp_send_json_error(array(
                        'message' => __('Settings modülü yüklenemedi.', 'dodo-ai-seo')
                    ), 500);
                    return;
                }
                
                $settings = new DODO_Settings();
                $publish_mode = $settings->get_setting('default_publish_mode', 'draft');
                error_log("[DODO QUEUE] Using global default publish mode: {$publish_mode}");
            }
            
            // Resolve "default" to actual mode
            if ($publish_mode === 'default') {
                $settings = new DODO_Settings();
                $publish_mode = $settings->get_setting('default_publish_mode', 'draft');
                error_log("[DODO QUEUE] Resolved 'default' to: {$publish_mode}");
            }
            
            // Calculate queue position for scheduling
            if (!class_exists('DODO_Scheduled_Publisher')) {
                DODO_Error_Handler::log_error_with_context('QUEUE', 'DODO_Scheduled_Publisher class not found', array(
                    'opportunity_id' => $opp_id
                ));
                wp_send_json_error(array(
                    'message' => __('Scheduler modülü yüklenemedi.', 'dodo-ai-seo')
                ), 500);
                return;
            }
            
            $scheduler = new DODO_Scheduled_Publisher();
            $queue_position = $scheduler->get_current_queue_position();
            
            error_log("[DODO QUEUE] Queue position: {$queue_position}");
            
            // Generate blog using existing generator
            if (!class_exists('DODO_Generator')) {
                DODO_Error_Handler::log_error_with_context('QUEUE', 'DODO_Generator class not found', array(
                    'opportunity_id' => $opp_id
                ));
                wp_send_json_error(array(
                    'message' => __('Generator modülü yüklenemedi.', 'dodo-ai-seo')
                ), 500);
                return;
            }
            
            $generator = new DODO_Generator();
            
            $generator_params = array(
                'focus_keyword' => $keyword,
                'topic' => $opportunity['suggested_title'],
                'length' => 'medium',
                'tone' => 'technical',
                'content_type' => $opportunity['content_type'],
                'category_id' => 0,
                'post_status' => 'draft', // Will be overridden by publish_mode
                'publish_mode' => $publish_mode,
                'queue_position' => $queue_position,
            );
            
            // Add scheduled date/time if available from item
            if ($item_scheduled_date && $item_scheduled_time) {
                $generator_params['scheduled_date'] = $item_scheduled_date;
                $generator_params['scheduled_time'] = $item_scheduled_time;
                error_log("[DODO QUEUE] Passing manual schedule to generator: {$item_scheduled_date} {$item_scheduled_time}");
            }
            
            $result = $generator->generate_blog($generator_params);
            
            if (is_wp_error($result)) {
                // Failed - categorize error and update retry info
                $error_msg = $result->get_error_message();
                $error_code = $result->get_error_code();
                $error_category = $this->categorize_error($error_code, $error_msg);
                
                DODO_Error_Handler::log_error_with_context('QUEUE', 'Queue item processing failed', array(
                    'opportunity_id' => $opp_id,
                    'keyword' => $keyword,
                    'error_code' => $error_code,
                    'error_message' => $error_msg,
                    'error_category' => $error_category,
                    'retry_count' => $retry_count
                ));
                
                error_log("[DODO QUEUE] Failed #{$opp_id}: {$error_msg} (category: {$error_category})");
                
                // Check for non-retryable errors (quota exceeded, invalid API key)
                $non_retryable_errors = ['openai_quota_exceeded', 'openai_invalid_key'];
                
                if (in_array($error_code, $non_retryable_errors)) {
                    // Mark as permanently failed immediately - no retry
                    $opportunities_manager->mark_permanently_failed($opp_id, $error_msg, $error_category);
                    error_log("[DODO QUEUE] Non-retryable error for #{$opp_id}: {$error_code}, marked as permanently_failed");
                    
                    wp_send_json_error(array(
                        'message' => $error_msg,
                        'keyword' => $keyword,
                        'error_category' => $error_category,
                        'non_retryable' => true,
                        'stop_queue' => true, // Signal to stop queue processing
                    ));
                    return;
                }
                
                // Check if max retries exceeded
                $settings = new DODO_Settings();
                $max_retry = $settings->get_setting('max_retry_count', 3);
                
                if ($retry_count >= $max_retry) {
                    // Mark as permanently failed
                    $opportunities_manager->mark_permanently_failed($opp_id, $error_msg, $error_category);
                    error_log("[DODO RETRY] Max retries exceeded for #{$opp_id}, marked as permanently_failed");
                } else {
                    // Update retry info for next attempt
                    $opportunities_manager->update_retry_info($opp_id, $error_msg, $error_category);
                    error_log("[DODO RETRY] Updated retry info for #{$opp_id}, will retry later");
                }
                
                wp_send_json_error(array(
                    'message' => $error_msg,
                    'keyword' => $keyword,
                    'retry_count' => $retry_count + 1,
                    'error_category' => $error_category,
                ));
            } else {
                // Success - clear retry info
                $post_id = $result['post_id'];
                $opportunities_manager->clear_retry_info_on_success($opp_id);
                $opportunities_manager->update_opportunity_result($opp_id, 'created', $post_id);
                
                // Get post status for logging
                $post = get_post($post_id);
                $post_status = $post ? $post->post_status : 'unknown';
                
                error_log("[DODO QUEUE] Success #{$opp_id}: Post #{$post_id} created (status: {$post_status})");
                
                if ($retry_count > 0) {
                    error_log("[DODO RETRY] Item #{$opp_id} succeeded after {$retry_count} retries");
                }
                
                wp_send_json_success(array(
                    'completed' => false,
                    'keyword' => $keyword,
                    'post_id' => $post_id,
                    'post_status' => $post_status,
                    'was_retry' => $retry_count > 0,
                ));
            }
            
        } catch (Throwable $e) {
            // Exception - categorize and update retry info
            $error_msg = $e->getMessage();
            $error_category = $this->categorize_error('exception', $error_msg);
            
            DODO_Error_Handler::safe_exception_response($e, 'ajax_process_queue_item');
            
            error_log("[DODO QUEUE] Exception #{$opp_id}: {$error_msg} (category: {$error_category})");
            
            // Check if max retries exceeded
            if (!class_exists('DODO_Settings')) {
                return;
            }
            
            $settings = new DODO_Settings();
            $max_retry = $settings->get_setting('max_retry_count', 3);
            
            if (isset($opp_id) && isset($opportunities_manager)) {
                if ($retry_count >= $max_retry) {
                    // Mark as permanently failed
                    $opportunities_manager->mark_permanently_failed($opp_id, $error_msg, $error_category);
                    error_log("[DODO RETRY] Max retries exceeded for #{$opp_id}, marked as permanently_failed");
                } else {
                    // Update retry info for next attempt
                    $opportunities_manager->update_retry_info($opp_id, $error_msg, $error_category);
                    error_log("[DODO RETRY] Updated retry info for #{$opp_id}, will retry later");
                }
            }
        }
    }
    
    /**
     * Categorize error for retry system
     * 
     * @param string $error_code
     * @param string $error_message
     * @return string
     */
    private function categorize_error($error_code, $error_message) {
        $error_lower = strtolower($error_message);
        
        // OpenAI rate limit
        if (strpos($error_lower, 'rate limit') !== false || strpos($error_lower, 'rate_limit') !== false) {
            return 'openai_rate_limit';
        }
        
        // Timeout
        if (strpos($error_lower, 'timeout') !== false || strpos($error_lower, 'timed out') !== false) {
            return 'timeout_error';
        }
        
        // API error
        if (strpos($error_lower, 'api') !== false || strpos($error_lower, 'openai') !== false) {
            return 'api_error';
        }
        
        // WordPress insert error
        if (strpos($error_lower, 'wp_insert') !== false || strpos($error_lower, 'post') !== false) {
            return 'wp_insert_error';
        }
        
        // Validation error
        if (strpos($error_lower, 'validation') !== false || strpos($error_lower, 'invalid') !== false) {
            return 'validation_error';
        }
        
        return 'unknown_error';
    }
    
    /**
     * AJAX: Retry failed queue item manually
     * HARDENED: Final Completion Phase - AJAX Hardening
     */
    public function ajax_retry_queue_item() {
        try {
            // Defensive nonce check
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dodo_ajax_nonce')) {
                wp_send_json_error(array('message' => 'Güvenlik doğrulaması başarısız'));
                return;
            }
            
            // Capability check
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => 'Yetkiniz yok'));
                return;
            }
            
            // Validate ID
            $id = isset($_POST['id']) ? absint($_POST['id']) : 0;
            if (!$id) {
                wp_send_json_error(array('message' => 'Geçersiz ID'));
                return;
            }
            
            // Execute retry
            $opportunities_manager = new DODO_Keyword_Opportunities();
            $result = $opportunities_manager->manual_retry($id);
            
            if ($result) {
                error_log("[DODO RETRY] Manual retry triggered for #{$id}");
                wp_send_json_success(array('message' => 'Item kuyruğa eklendi, tekrar denenecek'));
            } else {
                wp_send_json_error(array('message' => 'Retry işlemi başarısız'));
            }
            
        } catch (Throwable $e) {
            error_log('[DODO AJAX ERROR] ajax_retry_queue_item: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => 'İşlem sırasında hata oluştu',
                'error' => WP_DEBUG ? $e->getMessage() : null
            ));
        }
    }
    
    /**
     * AJAX: Publish Now
     * HARDENED: Final Completion Phase - AJAX Hardening
     */
    public function ajax_publish_now() {
        try {
            // Defensive nonce check
            if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'dodo_ajax_nonce')) {
                DODO_Error_Handler::safe_ajax_error(__('Güvenlik doğrulaması başarısız.', 'dodo-ai-seo'), 403);
                return;
            }
            
            // Capability check
            if (!current_user_can('manage_options')) {
                DODO_Error_Handler::safe_ajax_error(__('Bu işlem için yetkiniz yok.', 'dodo-ai-seo'), 403);
                return;
            }
        
        $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
        
        if (!$post_id) {
            wp_send_json_error(array('message' => 'Geçersiz post ID'));
        }
        
        // Post kontrolü
        $post = get_post($post_id);
        
        if (!$post || $post->post_status !== 'future') {
            wp_send_json_error(array('message' => 'Post bulunamadı veya zamanlanmış değil'));
        }
        
        // Post'u yayınla
        $updated = wp_update_post(array(
            'ID' => $post_id,
            'post_status' => 'publish',
            'post_date' => current_time('mysql'),
            'post_date_gmt' => current_time('mysql', 1),
        ));
        
        if (is_wp_error($updated)) {
            wp_send_json_error(array('message' => $updated->get_error_message()));
        }
        
        error_log("[DODO SCHEDULER] Post #{$post_id} published manually");
        
        wp_send_json_success(array(
            'message' => 'İçerik başarıyla yayınlandı',
            'post_id' => $post_id,
        ));
        
        } catch (Throwable $e) {
            DODO_Error_Handler::safe_exception_response($e, 'ajax_publish_now');
        }
    }
    
    /**
     * AJAX: Convert to Draft
     * HARDENED: Final Completion Phase - AJAX Hardening
     */
    public function ajax_convert_to_draft() {
        try {
            // Defensive nonce check
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dodo_ajax_nonce')) {
                wp_send_json_error(array('message' => 'Güvenlik doğrulaması başarısız'));
                return;
            }
            
            // Capability check
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => 'Yetkiniz yok'));
                return;
            }
            
            // Validate post ID
            $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
            if (!$post_id) {
                wp_send_json_error(array('message' => 'Geçersiz post ID'));
                return;
            }
            
            // Post kontrolü
            $post = get_post($post_id);
            if (!$post || $post->post_status !== 'future') {
                wp_send_json_error(array('message' => 'Post bulunamadı veya zamanlanmış değil'));
                return;
            }
            
            // Post'u taslağa çevir
            $updated = wp_update_post(array(
                'ID' => $post_id,
                'post_status' => 'draft',
            ));
            
            if (is_wp_error($updated)) {
                wp_send_json_error(array('message' => $updated->get_error_message()));
                return;
            }
            
            error_log("[DODO SCHEDULER] Post #{$post_id} converted to draft");
            
            wp_send_json_success(array(
                'message' => 'İçerik taslağa çevrildi',
                'post_id' => $post_id,
            ));
            
        } catch (Throwable $e) {
            error_log('[DODO AJAX ERROR] ajax_convert_to_draft: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => 'İşlem sırasında hata oluştu',
                'error' => WP_DEBUG ? $e->getMessage() : null
            ));
        }
    }
    
    /**
     * AJAX: Update Scheduled Post
     * HARDENED: Final Completion Phase - AJAX Hardening
     */
    public function ajax_update_scheduled_post() {
        try {
            // Defensive nonce check
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dodo_ajax_nonce')) {
                wp_send_json_error(array('message' => 'Güvenlik doğrulaması başarısız'));
                return;
            }
            
            // Capability check
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => 'Yetkiniz yok'));
                return;
            }
            
            // Validate post ID
            $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
            $new_date = isset($_POST['publish_date']) ? sanitize_text_field($_POST['publish_date']) : '';
            $new_time = isset($_POST['publish_time']) ? sanitize_text_field($_POST['publish_time']) : '';
            $new_status = isset($_POST['post_status']) ? sanitize_text_field($_POST['post_status']) : '';
            
            if (!$post_id) {
                wp_send_json_error(array('message' => 'Geçersiz post ID'));
                return;
            }
            
            // Post kontrolü
            $post = get_post($post_id);
            if (!$post) {
                wp_send_json_error(array('message' => 'Post bulunamadı'));
                return;
            }
            
            // Update data
            $update_data = array('ID' => $post_id);
            
            // Status update
            if (!empty($new_status)) {
                $allowed_statuses = array('draft', 'publish', 'future');
                if (in_array($new_status, $allowed_statuses)) {
                    $update_data['post_status'] = $new_status;
                }
            }
            
            // Date/Time update
            if (!empty($new_date) && !empty($new_time)) {
                // Validate date format (YYYY-MM-DD)
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $new_date)) {
                    // Validate time format (HH:MM)
                    if (preg_match('/^\d{2}:\d{2}$/', $new_time)) {
                        $datetime_string = $new_date . ' ' . $new_time . ':00';
                        
                        // Validate datetime
                        $timestamp = strtotime($datetime_string);
                        if ($timestamp !== false) {
                            $update_data['post_date'] = $datetime_string;
                            $update_data['post_date_gmt'] = gmdate('Y-m-d H:i:s', $timestamp);
                            
                            // If future date, set status to future
                            if ($timestamp > current_time('timestamp') && empty($new_status)) {
                                $update_data['post_status'] = 'future';
                            }
                            
                            // Update metadata
                            update_post_meta($post_id, '_dodo_scheduled_date', $datetime_string);
                        } else {
                            wp_send_json_error(array('message' => 'Geçersiz tarih/saat'));
                            return;
                        }
                    } else {
                        wp_send_json_error(array('message' => 'Geçersiz saat formatı (HH:MM)'));
                        return;
                    }
                } else {
                    wp_send_json_error(array('message' => 'Geçersiz tarih formatı (YYYY-MM-DD)'));
                    return;
                }
            }
            
            // Update post
            $updated = wp_update_post($update_data);
            
            if (is_wp_error($updated)) {
                wp_send_json_error(array('message' => $updated->get_error_message()));
                return;
            }
            
            error_log("[DODO SCHEDULER] Post #{$post_id} updated - Status: {$update_data['post_status']}, Date: " . (isset($update_data['post_date']) ? $update_data['post_date'] : 'unchanged'));
            
            wp_send_json_success(array(
                'message' => 'İçerik güncellendi',
                'post_id' => $post_id,
                'post_status' => get_post_status($post_id),
                'post_date' => get_the_date('Y-m-d H:i', $post_id),
            ));
            
        } catch (Throwable $e) {
            error_log('[DODO AJAX ERROR] ajax_update_scheduled_post: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => 'İşlem sırasında hata oluştu',
                'error' => WP_DEBUG ? $e->getMessage() : null
            ));
        }
    }
    
    /**
     * Content Improver sayfasını render et (NEW - Standalone admin page)
     */
    public function render_content_improver_page() {
        // Yetki kontrolü
        if (!current_user_can('manage_options')) {
            wp_die(__('Bu sayfaya erişim yetkiniz yok.', 'dodo-ai-seo'));
        }
        
        // View dosyasını include et
        include DODO_PLUGIN_DIR . 'admin/views/page-content-improver.php';
    }
    
    /**
     * Content Audit sayfasını render et
     */
    public function render_content_audit_page() {
        // Yetki kontrolü
        if (!current_user_can('manage_options')) {
            wp_die(__('Bu sayfaya erişim yetkiniz yok.', 'dodo-ai-seo'));
        }
        
        // View dosyasını include et
        include DODO_PLUGIN_DIR . 'admin/views/page-content-audit.php';
    }
    
    /**
     * Usage Reports sayfasını render et
     */
    public function render_usage_reports_page() {
        // Yetki kontrolü
        if (!current_user_can('manage_options')) {
            wp_die(__('Bu sayfaya erişim yetkiniz yok.', 'dodo-ai-seo'));
        }
        
        // View dosyasını include et
        include DODO_PLUGIN_DIR . 'admin/views/page-usage-reports.php';
    }
    
    /**
     * System Health sayfasını render et (Sprint 5 - Task 6)
     */
    public function render_system_health_page() {
        // Yetki kontrolü
        if (!current_user_can('manage_options')) {
            wp_die(__('Bu sayfaya erişim yetkiniz yok.', 'dodo-ai-seo'));
        }
        
        // View dosyasını include et
        include DODO_PLUGIN_DIR . 'admin/views/page-system-health.php';
    }
    
    /**
     * Cache Test sayfasını render et
     */
    public function render_cache_test_page() {
        // Yetki kontrolü
        if (!current_user_can('manage_options')) {
            wp_die(__('Bu sayfaya erişim yetkiniz yok.', 'dodo-ai-seo'));
        }
        
        // View dosyasını include et
        include DODO_PLUGIN_DIR . 'admin/views/page-cache-test.php';
    }
    
    /**
     * Usage Logger Debug sayfasını render et
     */
    public function render_logger_debug_page() {
        // Yetki kontrolü
        if (!current_user_can('manage_options')) {
            wp_die(__('Bu sayfaya erişim yetkiniz yok.', 'dodo-ai-seo'));
        }
        
        // View dosyasını include et
        include DODO_PLUGIN_DIR . 'admin/views/page-usage-logger-debug.php';
    }
    
    /**
     * Force Table Creation sayfasını render et
     */
    public function render_force_table_creation_page() {
        // Yetki kontrolü
        if (!current_user_can('manage_options')) {
            wp_die(__('Bu sayfaya erişim yetkiniz yok.', 'dodo-ai-seo'));
        }
        
        // View dosyasını include et
        include DODO_PLUGIN_DIR . 'admin/views/page-force-table-creation.php';
    }
    
    /**
     * Improver Test sayfasını render et
     */
    public function render_improver_test_page() {
        // Yetki kontrolü
        if (!current_user_can('manage_options')) {
            wp_die(__('Bu sayfaya erişim yetkiniz yok.', 'dodo-ai-seo'));
        }
        
        // View dosyasını include et
        include DODO_PLUGIN_DIR . 'admin/views/page-improver-test.php';
    }
    
    /**
     * AJAX: Content Audit çalıştır
     * HARDENED: Final Completion Phase
     */
    public function ajax_run_content_audit() {
        try {
            error_log("[DODO AUDIT] === AJAX REQUEST START ===");
            
            // Nonce kontrolü
            if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'dodo_ajax_nonce')) {
                wp_send_json_error(array('message' => __('Güvenlik doğrulaması başarısız.', 'dodo-ai-seo')), 403);
                return;
            }
            
            // Capability check
            if (!current_user_can('manage_options')) {
                error_log("[DODO AUDIT] Permission denied");
                wp_send_json_error(array('message' => __('Bu işlem için yetkiniz yok.', 'dodo-ai-seo')), 403);
                return;
            }
            error_log("[DODO AUDIT] Permission OK");
            
            // Post ID al
            $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
            
            if (!$post_id) {
                error_log("[DODO AUDIT] Invalid post ID");
                wp_send_json_error(array(
                    'message' => __('Geçersiz post ID.', 'dodo-ai-seo')
                ));
                return;
            }
            
            error_log("[DODO AUDIT] AJAX request received for post #{$post_id}");
            
            // Audit manager'ı başlat
            $audit_manager = new DODO_Content_Audit();
            error_log("[DODO AUDIT] Audit manager initialized");
            
            // Audit'i çalıştır
            $result = $audit_manager->audit_post($post_id);
            
            // WP_Error kontrolü
            if (is_wp_error($result)) {
                error_log("[DODO AUDIT] Audit failed: " . $result->get_error_message());
                
                // Detaylı hata mesajı
                global $wpdb;
                wp_send_json_error(array(
                    'message' => $result->get_error_message(),
                    'error_code' => $result->get_error_code(),
                    'db_error' => $wpdb->last_error,
                    'last_query' => $wpdb->last_query
                ));
                return;
            }
            
            // Başarılı
            error_log("[DODO AUDIT] Audit successful for post #{$post_id}");
            error_log("[DODO AUDIT] Result data: " . print_r($result, true));
            
            $response_data = array(
                'message' => __('Audit başarıyla tamamlandı!', 'dodo-ai-seo'),
                'post_id' => intval($result['post_id']),
                'seo_score' => intval($result['seo_score']),
                'content_score' => intval($result['content_score']),
                'link_score' => intval($result['link_score']),
                'ai_score' => intval($result['ai_score']),
                'overall_score' => intval($result['overall_score']),
                'audit_status' => sanitize_text_field($result['audit_status']),
                'word_count' => intval($result['word_count']),
                'internal_links' => intval($result['internal_links']),
                'product_links' => intval($result['product_links']),
                'external_links' => intval($result['external_links']),
            );
            
            error_log("[DODO AUDIT] Sending response: " . print_r($response_data, true));
            error_log("[DODO AUDIT] === AJAX REQUEST END ===");
            
            wp_send_json_success($response_data);
            
        } catch (Throwable $e) {
            error_log("[DODO AUDIT] Exception: " . $e->getMessage());
            error_log("[DODO AUDIT] Stack trace: " . $e->getTraceAsString());
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }
    
    /**
     * AJAX: Kaçırılan zamanlamaları düzelt
     * HARDENED: Final Completion Phase
     */
    public function ajax_fix_missed_schedules() {
        try {
            error_log("[DODO SCHEDULER] === AJAX FIX MISSED SCHEDULES START ===");
            
            // Nonce kontrolü
            if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'dodo_ajax_nonce')) {
                wp_send_json_error(array('message' => __('Güvenlik doğrulaması başarısız.', 'dodo-ai-seo')), 403);
                return;
            }
            error_log("[DODO SCHEDULER] Nonce verified");
            
            // Capability check
            if (!current_user_can('manage_options')) {
                error_log("[DODO SCHEDULER] Permission denied");
                wp_send_json_error(array('message' => __('Bu işlem için yetkiniz yok.', 'dodo-ai-seo')), 403);
                return;
            }
            error_log("[DODO SCHEDULER] Permission OK");
            
            // Missed Schedule Fixer'ı başlat
            $fixer = new DODO_Missed_Schedule_Fixer();
            error_log("[DODO SCHEDULER] Fixer initialized");
            
            // Düzeltme işlemini çalıştır
            $result = $fixer->check_and_fix_missed_schedules();
            
            error_log("[DODO SCHEDULER] Fix result: " . print_r($result, true));
            error_log("[DODO SCHEDULER] === AJAX FIX MISSED SCHEDULES END ===");
            
            wp_send_json_success(array(
                'message' => $result['message'],
                'fixed' => $result['fixed'],
                'failed' => isset($result['failed']) ? $result['failed'] : 0,
                'failed_posts' => isset($result['failed_posts']) ? $result['failed_posts'] : array(),
            ));
            
        } catch (Throwable $e) {
            error_log("[DODO SCHEDULER] Exception: " . $e->getMessage());
            error_log("[DODO SCHEDULER] Stack trace: " . $e->getTraceAsString());
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }
    
    /**
     * AJAX: Analyze content (NEW - Content Improver)
     * HARDENED: Final Completion Phase - AJAX Hardening
     */
    public function ajax_analyze_content() {
        try {
            // Defensive nonce check
            if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'dodo_improver_nonce')) {
                DODO_Error_Handler::log_error_with_context('AJAX', 'Nonce verification failed for ajax_analyze_content', array(
                    'user_id' => get_current_user_id()
                ));
                wp_send_json_error(array(
                    'message' => __('Güvenlik doğrulaması başarısız. Lütfen sayfayı yenileyin.', 'dodo-ai-seo')
                ), 403);
                return;
            }
            
            // Capability check
            if (!current_user_can('manage_options')) {
                DODO_Error_Handler::log_error_with_context('AJAX', 'Permission denied for ajax_analyze_content', array(
                    'user_id' => get_current_user_id()
                ));
                wp_send_json_error(array(
                    'message' => __('Bu işlemi gerçekleştirmek için yetkiniz yok.', 'dodo-ai-seo')
                ), 403);
                return;
            }
            
            // Get parameters
            $post_id = intval($_POST['post_id'] ?? 0);
            $focus_keyword = sanitize_text_field($_POST['focus_keyword'] ?? '');
            $include_intelligence = isset($_POST['include_intelligence']) ? (bool) $_POST['include_intelligence'] : true;
            
            // Validate
            if (empty($post_id)) {
                wp_send_json_error(array(
                    'message' => __('Geçersiz yazı ID.', 'dodo-ai-seo')
                ), 400);
                return;
            }
            
            // STAGE 1: CONTENT LOADING
            $this->send_pipeline_stage('content_loading');
            
            // Get post
            $post = get_post($post_id);
            if (!$post) {
                DODO_Error_Handler::log_error_with_context('AJAX', 'Post not found in ajax_analyze_content', array(
                    'post_id' => $post_id,
                    'user_id' => get_current_user_id()
                ));
                wp_send_json_error(array(
                    'message' => __('Yazı bulunamadı.', 'dodo-ai-seo')
                ), 404);
                return;
            }
            
            // Detect sections
            $sections = $this->detect_content_sections($post->post_content);
            
            // Calculate intelligence (Sprint 2 - Task 1)
            $intelligence = null;
            if ($include_intelligence) {
                $intelligence = $this->calculate_content_intelligence_with_stages($post, $focus_keyword);
            }
            
            // STAGE: COMPLETED
            $this->send_pipeline_stage('completed');
            
            // Success
            wp_send_json_success(array(
                'sections' => $sections,
                'post_title' => $post->post_title,
                'intelligence' => $intelligence,
                'pipeline_completed' => true,
            ));
            
        } catch (Throwable $e) {
            DODO_Error_Handler::safe_exception_response($e, 'ajax_analyze_content');
        }
    }
    
    /**
     * Calculate content intelligence with stage reporting
     */
    private function calculate_content_intelligence_with_stages($post, $focus_keyword) {
        // STAGE 2: HEALTH ANALYSIS
        $this->send_pipeline_stage('health_analysis');
        
        // Check cache first
        $cache_key = $this->get_intelligence_cache_key($post->ID, $post->post_modified, $focus_keyword);
        $cached = get_transient($cache_key);
        
        if ($cached !== false && !isset($_POST['force_refresh'])) {
            return $cached;
        }
        
        // Initialize score calculator
        $calculator = new DODO_Score_Calculator();
        
        // Prepare metadata
        $metadata = array(
            'seo_title' => get_post_meta($post->ID, 'rank_math_title', true) ?: $post->post_title,
            'meta_description' => get_post_meta($post->ID, 'rank_math_description', true),
            'slug' => $post->post_name,
            'focus_keyword' => $focus_keyword,
        );
        
        // Calculate dimension scores
        $seo_score = $calculator->calculate_seo_score($post->post_content, $focus_keyword, $metadata);
        $quality_score = $calculator->calculate_quality_score($post->post_content);
        $readability_score = $calculator->calculate_readability_score($post->post_content);
        
        // STAGE 3: SEMANTIC ANALYSIS
        $this->send_pipeline_stage('semantic_analysis');
        
        $semantic_score = $calculator->calculate_semantic_score($post->post_content, $focus_keyword);
        
        // STAGE 4: AI DETECTION
        $this->send_pipeline_stage('ai_detection');
        
        $ai_risk_score = $calculator->calculate_ai_risk_score($post->post_content);
        
        // Calculate overall health score
        $intelligence_data = array(
            'seo_score' => $seo_score,
            'quality_score' => $quality_score,
            'readability_score' => $readability_score,
            'semantic_score' => $semantic_score,
            'ai_risk_score' => $ai_risk_score,
        );
        
        $health_score = $calculator->calculate_health_score($intelligence_data);
        $intelligence_data['health_score'] = $health_score;
        
        // Get score class and label
        $score_class = $calculator->get_score_class($health_score);
        $score_label = $calculator->get_score_label($health_score);
        
        // Calculate confidence
        $confidence_analyzer = new DODO_Confidence_Analyzer();
        $confidence_score = $confidence_analyzer->calculate_confidence($intelligence_data, $post->post_content, $metadata);
        $risk_level = $confidence_analyzer->assess_risk_level($confidence_score, $intelligence_data);
        $risk_factors = $confidence_analyzer->identify_risk_factors($post->post_content, $intelligence_data);
        $confidence_label = $confidence_analyzer->get_confidence_label($confidence_score);
        $risk_label = $confidence_analyzer->get_risk_label($risk_level);
        
        // STAGE 5: RECOMMENDATION ENGINE
        $this->send_pipeline_stage('recommendation_engine');
        
        // Generate recommendations
        $recommendation_engine = new DODO_Recommendation_Engine();
        $intelligence_data['confidence_score'] = $confidence_score;
        $intelligence_data['risk_level'] = $risk_level;
        $recommendations = $recommendation_engine->generate_recommendations($intelligence_data, $post->post_content, $metadata);
        
        // Prioritize recommendations
        $priority_engine = new DODO_Priority_Engine();
        $recommendations = $priority_engine->prioritize_recommendations($recommendations, $intelligence_data);
        
        // Add explanations
        $explainer = new DODO_Decision_Explainer();
        $recommendations = $explainer->add_explanations($recommendations, $intelligence_data);
        
        // Semantic analysis
        $semantic_analyzer = new DODO_Semantic_Analyzer();
        $semantic_analysis = $semantic_analyzer->analyze_semantic_quality($post->post_content, $focus_keyword);
        
        // Editorial style detection
        $style_detector = new DODO_Style_Detector();
        $style_analysis = $style_detector->detect_style($post->post_content);
        
        // Content depth analysis
        $depth_analyzer = new DODO_Depth_Analyzer();
        $depth_analysis = $depth_analyzer->analyze_depth($post->post_content, $focus_keyword);
        
        // AI detection risk analysis
        $ai_detector = new DODO_AI_Detector();
        $ai_detection = $ai_detector->analyze_ai_risk($post->post_content);
        
        // GEO (Generative Engine Optimization) analysis (Sprint 3 - Task 2)
        $geo_analyzer = new DODO_GEO_Analyzer();
        $geo_analysis = $geo_analyzer->analyze_geo_score($post->post_content, $focus_keyword);
        
        // STAGE 6: RISK ANALYSIS
        $this->send_pipeline_stage('risk_analysis');
        
        // Prepare full intelligence
        $full_intelligence = array(
            'health_score' => $health_score,
            'seo_score' => $seo_score,
            'quality_score' => $quality_score,
            'readability_score' => $readability_score,
            'semantic_score' => $semantic_score,
            'ai_risk_score' => $ai_risk_score,
            'score_class' => $score_class,
            'score_label' => $score_label,
            'confidence_score' => $confidence_score,
            'confidence_label' => $confidence_label,
            'risk_level' => $risk_level,
            'risk_label' => $risk_label,
            'risk_factors' => $risk_factors,
            'semantic_analysis' => $semantic_analysis,
            'style_analysis' => $style_analysis,
            'depth_analysis' => $depth_analysis,
            'ai_detection' => $ai_detection,
            'geo_analysis' => $geo_analysis, // NEW: GEO analysis
        );
        
        // STAGE 7: SUMMARY GENERATION
        $this->send_pipeline_stage('summary_generation');
        
        // Generate quality summary
        $quality_summarizer = new DODO_Quality_Summarizer();
        $quality_summary = $quality_summarizer->generate_summary($full_intelligence);
        
        // Assess advanced risks
        $risk_engine = new DODO_Risk_Engine();
        $risk_assessment = $risk_engine->assess_risks($full_intelligence);
        
        // STAGE 8: INSIGHTS RENDER
        $this->send_pipeline_stage('insights_render');
        
        // Calculate quick stats
        $word_count = str_word_count(strip_tags($post->post_content));
        $reading_time = ceil($word_count / 200);
        
        // Entity Enrichment (Sprint 3 - Task 2)
        $entity_enrichment = new DODO_Entity_Enrichment();
        $entity_data = $entity_enrichment->analyze_entities($post->post_content);
        
        // Workflow Engine (Sprint 3 - Task 3)
        $workflow_engine = new DODO_Workflow_Engine();
        $workflow_status = $workflow_engine->get_post_workflow_status($post->ID);
        $workflow_readiness = $workflow_engine->calculate_publish_readiness($post->ID);
        
        // Return intelligence data
        return array(
            'health_score' => $health_score,
            'seo_score' => $seo_score,
            'quality_score' => $quality_score,
            'readability_score' => $readability_score,
            'semantic_score' => $semantic_score,
            'ai_risk_score' => $ai_risk_score,
            'score_class' => $score_class,
            'score_label' => $score_label,
            'confidence_score' => $confidence_score,
            'confidence_label' => $confidence_label,
            'risk_level' => $risk_level,
            'risk_label' => $risk_label,
            'risk_factors' => $risk_factors,
            'recommendations' => $recommendations,
            'semantic_analysis' => $semantic_analysis,
            'style_analysis' => $style_analysis,
            'depth_analysis' => $depth_analysis,
            'ai_detection' => $ai_detection,
            'geo_analysis' => $geo_analysis, // Sprint 3 - GEO analysis
            'entity_enrichment' => $entity_data, // Sprint 3 - Entity enrichment
            'workflow' => array(
                'status' => $workflow_status,
                'readiness_score' => $workflow_readiness['readiness_score'],
                'is_ready' => $workflow_readiness['is_ready'],
                'blocking_warnings' => $workflow_readiness['blocking_warnings'],
            ), // Sprint 3 - Workflow engine
            'quality_summary' => $quality_summary,
            'risk_assessment' => $risk_assessment,
            'quick_stats' => array(
                'word_count' => $word_count,
                'reading_time' => $reading_time,
                'keyword_density' => $this->calculate_keyword_density($post->post_content, $focus_keyword),
            ),
            'analyzed_at' => current_time('mysql'),
        );
        
        // Cache intelligence data (Sprint 2B - Task 9)
        // Cache for 1 hour
        set_transient($cache_key, $intelligence_data, HOUR_IN_SECONDS);
        
        return $intelligence_data;
    }
    
    /**
     * Get intelligence cache key (Sprint 2B - Task 9)
     */
    private function get_intelligence_cache_key($post_id, $post_modified, $focus_keyword) {
        $content_hash = md5($post_modified . $focus_keyword);
        return 'dodo_intelligence_' . $post_id . '_' . $content_hash;
    }
    
    /**
     * Invalidate intelligence cache (Sprint 2B - Task 9)
     */
    private function invalidate_intelligence_cache($post_id) {
        global $wpdb;
        
        // Delete all transients for this post
        $pattern = '_transient_dodo_intelligence_' . $post_id . '_%';
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            $pattern
        ));
    }
    
    /**
     * Calculate keyword density
     * 
     * @param string $content Post content
     * @param string $keyword Focus keyword
     * @return float Keyword density percentage
     */
    private function calculate_keyword_density($content, $keyword) {
        if (empty($keyword)) {
            return 0;
        }
        
        $text = strip_tags($content);
        $word_count = str_word_count($text);
        
        if ($word_count == 0) {
            return 0;
        }
        
        $keyword_count = substr_count(strtolower($text), strtolower($keyword));
        $density = ($keyword_count / $word_count) * 100;
        
        return round($density, 2);
    }
    
    /**
     * AJAX: Improve content (NEW - Content Improver with AI Editor Intelligence)
     * HARDENED: Final Completion Phase - AJAX Hardening
     */
    public function ajax_improve_content() {
        try {
            // Defensive nonce check
            if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'dodo_improver_nonce')) {
                DODO_Error_Handler::log_error_with_context('AJAX', 'Nonce verification failed for ajax_improve_content', array(
                    'user_id' => get_current_user_id()
                ));
                wp_send_json_error(array(
                    'message' => __('Güvenlik doğrulaması başarısız. Lütfen sayfayı yenileyin.', 'dodo-ai-seo')
                ), 403);
                return;
            }
            
            // Capability check
            if (!current_user_can('manage_options')) {
                DODO_Error_Handler::log_error_with_context('AJAX', 'Permission denied for ajax_improve_content', array(
                    'user_id' => get_current_user_id()
                ));
                wp_send_json_error(array(
                    'message' => __('Bu işlemi gerçekleştirmek için yetkiniz yok.', 'dodo-ai-seo')
                ), 403);
                return;
            }
            
            // Get parameters
            $post_id = intval($_POST['post_id'] ?? 0);
            $improve_type = sanitize_text_field($_POST['improve_type'] ?? '');
            $focus_keyword = sanitize_text_field($_POST['focus_keyword'] ?? '');
            $section_content = wp_kses_post($_POST['section_content'] ?? '');
            
            // Validate
            if (empty($improve_type) || empty($focus_keyword) || empty($section_content)) {
                wp_send_json_error(array(
                    'message' => __('Geçersiz parametreler.', 'dodo-ai-seo')
                ), 400);
                return;
            }
            
            // STAGE 1: ANALYZE
            $this->send_pipeline_stage('analyze');
            
            // STAGE 2: STRATEGY
            $this->send_pipeline_stage('strategy');
            
            // STAGE 3: IMPROVE
            $this->send_pipeline_stage('improve');
            
            // Improve section using core engine with AI Editor Intelligence
            if (!class_exists('DODO_Content_Improver')) {
                DODO_Error_Handler::log_error_with_context('AJAX', 'DODO_Content_Improver class not found', array(
                    'post_id' => $post_id,
                    'user_id' => get_current_user_id()
                ));
                wp_send_json_error(array(
                    'message' => __('Content Improver modülü yüklenemedi.', 'dodo-ai-seo')
                ), 500);
                return;
            }
            
            $improver = new DODO_Content_Improver();
            $result = $improver->improve_section(
                $section_content,
                $improve_type,
                $focus_keyword
            );
            
            if (is_wp_error($result)) {
                DODO_Error_Handler::log_error_with_context('AJAX', 'Content improvement failed', array(
                    'error_code' => $result->get_error_code(),
                    'error_message' => $result->get_error_message(),
                    'post_id' => $post_id,
                    'improve_type' => $improve_type,
                    'user_id' => get_current_user_id()
                ));
                
                // Determine HTTP status code
                $http_code = 500;
                $error_code = $result->get_error_code();
                if (in_array($error_code, array('no_api_key', 'openai_invalid_key'), true)) {
                    $http_code = 400;
                } elseif (in_array($error_code, array('rate_limit_exceeded', 'openai_rate_limit', 'openai_quota_exceeded'), true)) {
                    $http_code = 429;
                }
                
                wp_send_json_error(array(
                    'message' => $result->get_error_message(),
                    'error_code' => $result->get_error_code(),
                    'error_data' => $result->get_error_data()
                ), $http_code);
                return;
            }
            
            // STAGE 4: VALIDATE
            $this->send_pipeline_stage('validate');
            
            // Extract content and metadata
            $improved_content = $result['content'];
            $metadata = $result['metadata'];
            
            // Extract validation flags (NEW - Task 7)
            $validation_failed = isset($result['validation_failed']) ? $result['validation_failed'] : false;
            $apply_allowed = isset($result['apply_allowed']) ? $result['apply_allowed'] : true;
            
            // STAGE 5: DIFF
            $this->send_pipeline_stage('diff');
            
            // Success - return with metadata and validation flags
            wp_send_json_success(array(
                'original' => $section_content,
                'improved' => $improved_content,
                'improve_type' => $improve_type,
                'metadata' => $metadata,
                'validation_failed' => $validation_failed,
                'apply_allowed' => $apply_allowed,
                'pipeline_completed' => true,
            ));
            
        } catch (Throwable $e) {
            DODO_Error_Handler::safe_exception_response($e, 'ajax_improve_content');
        }
    }
    
    /**
     * Send pipeline stage event (Sprint 1B - Task 5)
     * 
     * Sends stage update to frontend via custom header
     */
    private function send_pipeline_stage($stage) {
        // Send stage via custom header
        header('X-DODO-Pipeline-Stage: ' . $stage);
        
        // Flush output to send immediately
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } else {
            flush();
        }
        
        // Debug log
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[DODO PIPELINE] Stage: ' . $stage);
        }
    }
    
    /**
     * AJAX: Apply improvement (NEW - Content Improver)
     * HARDENED: Final Completion Phase - AJAX Hardening
     */
    public function ajax_apply_improvement() {
        try {
            // Defensive nonce check
            if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'dodo_improver_nonce')) {
                DODO_Error_Handler::safe_ajax_error(__('Güvenlik doğrulaması başarısız.', 'dodo-ai-seo'), 403);
                return;
            }
            
            // Capability check
            if (!current_user_can('manage_options')) {
                DODO_Error_Handler::safe_ajax_error(__('Bu işlem için yetkiniz yok.', 'dodo-ai-seo'), 403);
                return;
            }
        
        // === STEP 1: GET PARAMETERS ===
        $post_id = intval($_POST['post_id'] ?? 0);
        $original_content = isset($_POST['original_content']) ? wp_kses_post(wp_unslash($_POST['original_content'])) : '';
        $improved_content = isset($_POST['improved_content']) ? wp_kses_post(wp_unslash($_POST['improved_content'])) : '';
        $improve_type = sanitize_text_field($_POST['improve_type'] ?? '');
        $metadata = isset($_POST['metadata']) ? json_decode(stripslashes($_POST['metadata']), true) : array();
        
        // NEW: Get offset metadata for offset-based replacement
        $section_id = sanitize_text_field($_POST['section_id'] ?? '');
        $start_offset = isset($_POST['start_offset']) ? intval($_POST['start_offset']) : -1;
        $end_offset = isset($_POST['end_offset']) ? intval($_POST['end_offset']) : -1;
        $original_hash = sanitize_text_field($_POST['original_hash'] ?? '');
        
        // Calculate content hashes for verification
        $original_content_hash = md5($original_content);
        $improved_hash = md5($improved_content);
        
        error_log('[DODO APPLY DEBUG] === APPLY IMPROVEMENT PIPELINE START ===');
        error_log('[DODO APPLY DEBUG] Step 1: Request received');
        error_log('[DODO APPLY DEBUG] - post_id: ' . $post_id);
        error_log('[DODO APPLY DEBUG] - improve_type: ' . $improve_type);
        error_log('[DODO APPLY DEBUG] - section_id: ' . $section_id);
        error_log('[DODO APPLY DEBUG] - start_offset: ' . $start_offset);
        error_log('[DODO APPLY DEBUG] - end_offset: ' . $end_offset);
        error_log('[DODO APPLY DEBUG] - original_hash: ' . $original_hash);
        error_log('[DODO APPLY DEBUG] - original_content length: ' . strlen($original_content));
        error_log('[DODO APPLY DEBUG] - improved_content length: ' . strlen($improved_content));
        error_log('[DODO APPLY DEBUG] - original_content_hash: ' . $original_content_hash);
        error_log('[DODO APPLY DEBUG] - improved_hash: ' . $improved_hash);
        error_log('[DODO APPLY DEBUG] - metadata keys: ' . implode(', ', array_keys($metadata)));
        
        // === STEP 2: VALIDATION ===
        error_log('[DODO APPLY DEBUG] Step 2: Parameter validation');
        
        $missing_params = array();
        
        if (empty($post_id)) {
            $missing_params[] = 'post_id';
        }
        if (empty($original_content)) {
            $missing_params[] = 'original_content';
        }
        if (empty($improved_content)) {
            $missing_params[] = 'improved_content';
        }
        if (empty($improve_type)) {
            $missing_params[] = 'improve_type';
        }
        
        if (!empty($missing_params)) {
            $error_message = sprintf(
                __('Geçersiz parametreler: %s eksik.', 'dodo-ai-seo'),
                implode(', ', $missing_params)
            );
            
            error_log('[DODO APPLY DEBUG] VALIDATION FAILED: ' . $error_message);
            
            wp_send_json_error(array(
                'message' => $error_message,
                'missing_params' => $missing_params,
            ));
        }
        
        error_log('[DODO APPLY DEBUG] - validation_status: PASSED');
        
        // === STEP 3: VALIDATION FAILED CHECK (CRITICAL) ===
        error_log('[DODO APPLY DEBUG] Step 3: Validation failed check');
        
        if (isset($metadata['validation_failed']) && $metadata['validation_failed'] === true) {
            error_log('[DODO APPLY DEBUG] BLOCKED: Validation failed content cannot be applied');
            error_log('[DODO APPLY DEBUG] - validation_failed: true');
            error_log('[DODO APPLY DEBUG] - error_message: ' . ($metadata['error_message'] ?? 'N/A'));
            
            wp_send_json_error(array(
                'message' => __('Doğrulama başarısız içerik uygulanamaz. Lütfen önce içeriği düzeltin.', 'dodo-ai-seo'),
                'validation_failed' => true,
                'error_details' => $metadata['error_message'] ?? null,
            ));
        }
        
        error_log('[DODO APPLY DEBUG] - validation_failed: false (OK to proceed)');
        
        // === STEP 4: GET POST ===
        error_log('[DODO APPLY DEBUG] Step 4: Get post from database');
        
        $post = get_post($post_id);
        if (!$post) {
            error_log('[DODO APPLY DEBUG] ERROR: Post not found');
            wp_send_json_error(array(
                'message' => __('Yazı bulunamadı.', 'dodo-ai-seo'),
            ));
        }
        
        $current_content = $post->post_content;
        $current_content_hash = md5($current_content);
        
        error_log('[DODO APPLY DEBUG] - post found: ' . $post->post_title);
        error_log('[DODO APPLY DEBUG] - current_content length: ' . strlen($current_content));
        error_log('[DODO APPLY DEBUG] - current_content_hash: ' . $current_content_hash);
        
        // === STEP 5: CONTENT REPLACEMENT (OFFSET-BASED PRIMARY, ROBUST FALLBACK) ===
        error_log('[DODO APPLY DEBUG] Step 5: Content replacement');
        
        $replacement_result = null;
        $replacement_strategy = 'unknown';
        
        // Check if offset is unavailable
        if ($start_offset < 0 || $end_offset < 0) {
            error_log('[DODO APPLY DEBUG] - offset unavailable (start: ' . $start_offset . ', end: ' . $end_offset . ')');
            
            wp_send_json_error(array(
                'message' => __('Bu bölüm için güvenli konum bilgisi bulunamadı. Lütfen içeriği tekrar analiz edin.', 'dodo-ai-seo'),
                'offset_unavailable' => true,
            ));
        }
        
        // PRIMARY STRATEGY: Offset-based replacement (if metadata available)
        if ($start_offset >= 0 && $end_offset > $start_offset && !empty($original_hash)) {
            error_log('[DODO APPLY DEBUG] - attempting offset-based replacement');
            error_log('[DODO APPLY DEBUG] - offset range: ' . $start_offset . ' to ' . $end_offset);
            
            // Extract substring by offset
            $substring_length = $end_offset - $start_offset;
            $current_substring = substr($current_content, $start_offset, $substring_length);
            $current_substring_hash = md5($current_substring);
            
            error_log('[DODO APPLY DEBUG] - current_substring length: ' . strlen($current_substring));
            error_log('[DODO APPLY DEBUG] - current_substring_hash: ' . $current_substring_hash);
            error_log('[DODO APPLY DEBUG] - expected_hash: ' . $original_hash);
            
            // Verify hash match
            if ($current_substring_hash === $original_hash) {
                // Hash matches - safe to replace by offset
                error_log('[DODO APPLY DEBUG] - offset_hash_match: YES');
                error_log('[DODO APPLY DEBUG] - using offset-based replacement (PRIMARY)');
                
                $new_content = substr_replace($current_content, $improved_content, $start_offset, $substring_length);
                $replacement_strategy = 'offset';
                
                $replacement_result = array(
                    'success' => true,
                    'content' => $new_content,
                    'strategy' => 'offset',
                    'confidence' => 100,
                    'message' => __('İçerik offset ile değiştirildi.', 'dodo-ai-seo'),
                );
                
                error_log('[DODO APPLY DEBUG] - replacement_strategy: offset');
                error_log('[DODO APPLY DEBUG] - confidence: 100%');
            } else {
                // Hash mismatch - content changed since analysis
                error_log('[DODO APPLY DEBUG] - offset_hash_match: NO');
                error_log('[DODO APPLY DEBUG] - WARNING: Content changed since analysis');
                
                wp_send_json_error(array(
                    'message' => __('Bu içerik analizden sonra değişmiş. Lütfen içeriği tekrar analiz edin.', 'dodo-ai-seo'),
                    'content_changed' => true,
                    'expected_hash' => $original_hash,
                    'current_hash' => $current_substring_hash,
                ));
            }
        } else {
            // FALLBACK STRATEGY: Robust string matching
            error_log('[DODO APPLY DEBUG] - offset metadata not available, using robust matching fallback');
            
            $replacement_result = $this->robust_content_replace($current_content, $original_content, $improved_content);
            $replacement_strategy = $replacement_result['strategy'];
        }
        
        // Check replacement result
        if (!$replacement_result['success']) {
            error_log('[DODO APPLY DEBUG] ERROR: Content replacement failed');
            error_log('[DODO APPLY DEBUG] - strategy_tried: ' . $replacement_result['strategy']);
            error_log('[DODO APPLY DEBUG] - reason: ' . $replacement_result['reason']);
            
            wp_send_json_error(array(
                'message' => $replacement_result['message'],
                'content_mismatch' => true,
                'strategy_tried' => $replacement_result['strategy'],
            ));
        }
        
        $new_content = $replacement_result['content'];
        $new_content_hash = md5($new_content);
        
        error_log('[DODO APPLY DEBUG] - replacement_strategy: ' . $replacement_strategy);
        error_log('[DODO APPLY DEBUG] - confidence: ' . $replacement_result['confidence'] . '%');
        error_log('[DODO APPLY DEBUG] - new_content length: ' . strlen($new_content));
        error_log('[DODO APPLY DEBUG] - new_content_hash: ' . $new_content_hash);
        
        // Verify content actually changed
        $content_changed = $new_content_hash !== $current_content_hash;
        error_log('[DODO APPLY DEBUG] - new_content length: ' . strlen($new_content));
        error_log('[DODO APPLY DEBUG] - new_content_hash: ' . $new_content_hash);
        error_log('[DODO APPLY DEBUG] - content_changed: ' . ($content_changed ? 'YES' : 'NO'));
        
        if (!$content_changed) {
            error_log('[DODO APPLY DEBUG] ERROR: Content did not change after replacement');
            
            wp_send_json_error(array(
                'message' => __('İçerik değişikliği uygulanamadı. Lütfen tekrar deneyin.', 'dodo-ai-seo'),
                'no_change_detected' => true,
            ));
        }
        
        // === STEP 6: BACKUP ORIGINAL CONTENT ===
        error_log('[DODO APPLY DEBUG] Step 6: Backup original content');
        
        $backup_key = '_dodo_content_backup_' . time();
        $backup_saved = update_post_meta($post_id, $backup_key, $current_content);
        error_log('[DODO APPLY DEBUG] - backup_key: ' . $backup_key);
        error_log('[DODO APPLY DEBUG] - backup_saved: ' . ($backup_saved ? 'YES' : 'NO'));
        
        // === STEP 7: UPDATE POST ===
        error_log('[DODO APPLY DEBUG] Step 7: Update post in database');
        
        $updated = wp_update_post(array(
            'ID' => $post_id,
            'post_content' => $new_content,
        ), true);
        
        if (is_wp_error($updated)) {
            error_log('[DODO APPLY DEBUG] ERROR: wp_update_post failed');
            error_log('[DODO APPLY DEBUG] - error: ' . $updated->get_error_message());
            
            wp_send_json_error(array(
                'message' => $updated->get_error_message(),
                'wp_update_post_failed' => true,
            ));
        }
        
        error_log('[DODO APPLY DEBUG] - wp_update_post result: ' . $updated);
        
        // === STEP 8: VERIFY SAVE (CRITICAL) ===
        error_log('[DODO APPLY DEBUG] Step 8: Verify database save');
        
        // Clear cache and re-fetch post
        clean_post_cache($post_id);
        $verified_post = get_post($post_id);
        
        if (!$verified_post) {
            error_log('[DODO APPLY DEBUG] ERROR: Post not found after save');
            
            wp_send_json_error(array(
                'message' => __('İçerik kaydedilemedi. Lütfen tekrar deneyin.', 'dodo-ai-seo'),
                'verification_failed' => true,
            ));
        }
        
        $saved_content = $verified_post->post_content;
        $saved_content_hash = md5($saved_content);
        
        error_log('[DODO APPLY DEBUG] - saved_content length: ' . strlen($saved_content));
        error_log('[DODO APPLY DEBUG] - saved_content_hash: ' . $saved_content_hash);
        error_log('[DODO APPLY DEBUG] - hash_match: ' . ($saved_content_hash === $new_content_hash ? 'YES' : 'NO'));
        
        // Verify hash matches
        if ($saved_content_hash !== $new_content_hash) {
            error_log('[DODO APPLY DEBUG] ERROR: Saved content hash mismatch');
            error_log('[DODO APPLY DEBUG] - expected_hash: ' . $new_content_hash);
            error_log('[DODO APPLY DEBUG] - actual_hash: ' . $saved_content_hash);
            
            wp_send_json_error(array(
                'message' => __('İçerik doğrulaması başarısız. Değişiklikler kaydedilmemiş olabilir.', 'dodo-ai-seo'),
                'hash_mismatch' => true,
                'expected_hash' => $new_content_hash,
                'actual_hash' => $saved_content_hash,
            ));
        }
        
        error_log('[DODO APPLY DEBUG] - verification_status: PASSED (content saved successfully)');
        
        // === STEP 9: SAVE REVISION ===
        error_log('[DODO APPLY DEBUG] Step 9: Save revision to history');
        
        $revision_manager = new DODO_Revision_Manager();
        $revision_id = $revision_manager->save_revision(array(
            'post_id' => $post_id,
            'improve_type' => $improve_type,
            'original_content' => $original_content,
            'improved_content' => $improved_content,
            'diff_html' => isset($metadata['diff_html']) ? $metadata['diff_html'] : null,
            'metadata' => $metadata,
            'rewrite_necessity' => isset($metadata['rewrite_necessity']) ? $metadata['rewrite_necessity'] : null,
        ));
        
        if (!$revision_id) {
            error_log('[DODO APPLY DEBUG] WARNING: Failed to save revision');
            error_log('[DODO APPLY DEBUG] - revision_id: NULL');
            // Don't fail the entire operation, but log the issue
        } else {
            error_log('[DODO APPLY DEBUG] - revision_id: ' . $revision_id);
            error_log('[DODO APPLY DEBUG] - revision_saved: YES');
        }
        
        // === STEP 10: SUCCESS RESPONSE ===
        error_log('[DODO APPLY DEBUG] Step 10: Send success response');
        error_log('[DODO APPLY DEBUG] === APPLY IMPROVEMENT PIPELINE COMPLETE ===');
        error_log('[DODO APPLY DEBUG] RESULT: SUCCESS');
        error_log('[DODO APPLY DEBUG] - content_changed: YES');
        error_log('[DODO APPLY DEBUG] - database_saved: YES');
        error_log('[DODO APPLY DEBUG] - verification_passed: YES');
        error_log('[DODO APPLY DEBUG] - revision_created: ' . ($revision_id ? 'YES' : 'NO'));
        
        // === PHASE 5: RECORD HUMAN FEEDBACK ===
        error_log('[DODO APPLY DEBUG] Phase 5: Recording human feedback');
        $this->record_human_feedback($post_id, $improve_type, 'accepted', $metadata);
        
        // Success
        wp_send_json_success(array(
            'message' => __('İçerik başarıyla geliştirildi!', 'dodo-ai-seo'),
            'revision_id' => $revision_id,
            'verified' => true,
            'content_hash' => $saved_content_hash,
            'match_strategy' => $replacement_result['strategy'],
            'confidence' => $replacement_result['confidence'],
        ));
        
        } catch (Throwable $e) {
            DODO_Error_Handler::safe_exception_response($e, 'ajax_apply_improvement');
        }
    }
    
    /**
     * Detect content sections (NEW - Content Improver helper)
     * 
     * @param string $content Post content
     * @return array Detected sections
     */
    private function detect_content_sections($content) {
        $sections = array();
        $debug_info = array();
        
        // Store raw original content for offset tracking
        $raw_original_content = $content;
        
        // Debug: Raw content length
        $debug_info['raw_length'] = strlen($content);
        
        // Check if Gutenberg content
        $is_gutenberg = strpos($content, '<!-- wp:') !== false;
        $debug_info['is_gutenberg'] = $is_gutenberg;
        
        // Parse content based on format
        if ($is_gutenberg) {
            $parsed_content = $this->parse_gutenberg_content($content);
            $debug_info['parsed_length'] = strlen($parsed_content);
        } else {
            $parsed_content = $content;
            $debug_info['parsed_length'] = strlen($content);
        }
        
        // Detect headings (multiple formats)
        $headings = $this->detect_headings($parsed_content);
        $debug_info['heading_count'] = count($headings);
        
        // Extract content for each heading
        for ($i = 0; $i < count($headings); $i++) {
            $next_heading = isset($headings[$i + 1]) ? $headings[$i + 1] : null;
            $headings[$i]['content'] = $this->extract_section_content($parsed_content, $headings[$i], $next_heading);
        }
        
        // Detect intro (content before first heading)
        $intro_content = $this->detect_intro_section($parsed_content, $headings);
        if (!empty($intro_content)) {
            // Find offset in raw content
            $intro_offset = $this->find_content_offset($raw_original_content, $intro_content);
            $word_count = str_word_count($intro_content);
            
            $sections[] = array(
                'section_id' => 'intro_' . md5($intro_content),
                'title' => __('Giriş', 'dodo-ai-seo'),
                'type' => 'intro',
                'content' => $intro_content,
                'start_offset' => $intro_offset['start'],
                'end_offset' => $intro_offset['end'],
                'original_hash' => md5($intro_content),
                'raw_original_content' => $intro_content,
                'word_count' => $word_count,
            );
        }
        
        // Detect FAQ section
        $faq_content = $this->detect_faq_section($parsed_content);
        if (!empty($faq_content)) {
            $faq_offset = $this->find_content_offset($raw_original_content, $faq_content);
            $word_count = str_word_count($faq_content);
            
            // FAQ size limit: 1500 words
            $faq_too_large = $word_count > 1500;
            
            $sections[] = array(
                'section_id' => 'faq_' . md5($faq_content),
                'title' => __('SSS Bölümü', 'dodo-ai-seo'),
                'type' => 'faq',
                'content' => $faq_content,
                'start_offset' => $faq_offset['start'],
                'end_offset' => $faq_offset['end'],
                'original_hash' => md5($faq_content),
                'raw_original_content' => $faq_content,
                'word_count' => $word_count,
                'too_large' => $faq_too_large,
            );
        }
        
        // Detect CTA (last paragraphs) - with raw content matching
        $cta_result = $this->detect_cta_section($parsed_content, $raw_original_content);
        if (!empty($cta_result['content'])) {
            $word_count = str_word_count($cta_result['content']);
            
            // Debug log
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[DODO CTA DEBUG] - cta_raw_match_found: ' . ($cta_result['start_offset'] >= 0 ? 'YES' : 'NO'));
                error_log('[DODO CTA DEBUG] - cta_start_offset: ' . $cta_result['start_offset']);
                error_log('[DODO CTA DEBUG] - cta_end_offset: ' . $cta_result['end_offset']);
                error_log('[DODO CTA DEBUG] - cta_hash: ' . $cta_result['hash']);
            }
            
            $sections[] = array(
                'section_id' => 'cta_' . $cta_result['hash'],
                'title' => __('Harekete Geçirici Mesaj', 'dodo-ai-seo'),
                'type' => 'cta',
                'content' => $cta_result['content'],
                'start_offset' => $cta_result['start_offset'],
                'end_offset' => $cta_result['end_offset'],
                'original_hash' => $cta_result['hash'],
                'raw_original_content' => $cta_result['raw_content'],
                'word_count' => $word_count,
                'offset_unavailable' => $cta_result['start_offset'] < 0,
            );
        }
        
        // Add heading sections WITH CONTENT
        $successful_extractions = 0;
        $empty_sections = 0;
        
        foreach ($headings as $heading) {
            // Skip FAQ headings (already detected)
            if (preg_match('/(FAQ|SSS|Sıkça Sorulan Sorular)/i', $heading['text'])) {
                continue;
            }
            
            // Only add sections with content
            if (!empty($heading['content']) && strlen($heading['content']) > 20) {
                $section_offset = $this->find_content_offset($raw_original_content, $heading['content']);
                $word_count = str_word_count($heading['content']);
                
                // Section size limit: 3000 words
                $section_too_large = $word_count > 3000;
                
                $sections[] = array(
                    'section_id' => 'section_' . md5($heading['text'] . $heading['content']),
                    'title' => $heading['text'],
                    'type' => 'section',
                    'content' => $heading['content'],
                    'start_offset' => $section_offset['start'],
                    'end_offset' => $section_offset['end'],
                    'original_hash' => md5($heading['content']),
                    'raw_original_content' => $heading['content'],
                    'word_count' => $word_count,
                    'too_large' => $section_too_large,
                );
                $successful_extractions++;
            } else {
                $empty_sections++;
                
                // Debug empty sections
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("DODO: Empty section detected - Title: {$heading['text']}, Content length: " . strlen($heading['content'] ?? ''));
                }
            }
        }
        
        $debug_info['successful_extractions'] = $successful_extractions;
        $debug_info['empty_sections'] = $empty_sections;
        
        // FALLBACK: If no sections detected, create minimum viable sections
        if (empty($sections)) {
            $sections = $this->create_fallback_sections($parsed_content);
            $debug_info['fallback_used'] = true;
        } else {
            $debug_info['fallback_used'] = false;
        }
        
        $debug_info['final_section_count'] = count($sections);
        
        // Calculate average content length
        $total_content_length = 0;
        foreach ($sections as $section) {
            $total_content_length += strlen($section['content'] ?? '');
        }
        $debug_info['avg_content_length'] = count($sections) > 0 ? round($total_content_length / count($sections)) : 0;
        
        // Debug log (only in WP_DEBUG mode)
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('DODO Content Analysis Debug: ' . print_r($debug_info, true));
        }
        
        return $sections;
    }
    
    /**
     * Find content offset in raw original content
     * 
     * @param string $raw_content Raw original content
     * @param string $section_content Section content to find
     * @return array Start and end offsets
     */
    private function find_content_offset($raw_content, $section_content) {
        // Try exact match first
        $start_offset = strpos($raw_content, $section_content);
        
        if ($start_offset !== false) {
            return array(
                'start' => $start_offset,
                'end' => $start_offset + strlen($section_content),
            );
        }
        
        // Fallback: normalized match
        $normalized_raw = $this->normalize_content($raw_content);
        $normalized_section = $this->normalize_content($section_content);
        
        $start_offset = strpos($normalized_raw, $normalized_section);
        
        if ($start_offset !== false) {
            return array(
                'start' => $start_offset,
                'end' => $start_offset + strlen($normalized_section),
            );
        }
        
        // Last resort: return -1 (offset not found)
        return array(
            'start' => -1,
            'end' => -1,
        );
    }
    
    /**
     * Normalize content for matching
     * 
     * @param string $content Content to normalize
     * @return string Normalized content
     */
    private function normalize_content($content) {
        // Unslash if needed
        $content = wp_unslash($content);
        
        // Decode HTML entities
        $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Normalize non-breaking spaces
        $content = str_replace(array('&nbsp;', "\xc2\xa0"), ' ', $content);
        
        // Normalize line endings
        $content = str_replace(array("\r\n", "\r"), "\n", $content);
        
        // Collapse multiple whitespace to single space
        $content = preg_replace('/\s+/u', ' ', $content);
        
        // Remove empty p tags
        $content = preg_replace('/<p>\s*<\/p>/', '', $content);
        
        // Trim
        return trim($content);
    }
    
    /**
     * Parse Gutenberg content to plain text
     * 
     * @param string $content Gutenberg content
     * @return string Parsed content
     */
    private function parse_gutenberg_content($content) {
        // Remove Gutenberg block comments
        $content = preg_replace('/<!-- \/wp:.*?-->/s', '', $content);
        $content = preg_replace('/<!-- wp:.*?-->/s', '', $content);
        
        // Extract heading blocks
        $content = preg_replace('/<h([2-6]).*?>(.*?)<\/h\1>/is', "\n## $2\n", $content);
        
        // Extract paragraph blocks
        $content = preg_replace('/<p.*?>(.*?)<\/p>/is', "$1\n\n", $content);
        
        // Remove remaining HTML tags
        $content = wp_strip_all_tags($content);
        
        // Clean up whitespace
        $content = preg_replace('/\n{3,}/', "\n\n", $content);
        $content = trim($content);
        
        return $content;
    }
    
    /**
     * Detect headings in content with position tracking
     * 
     * @param string $content Content
     * @return array Headings with positions
     */
    private function detect_headings($content) {
        $headings = array();
        
        // Detect markdown H2 (## Heading) with position
        preg_match_all('/^##\s+(.+?)$/m', $content, $markdown_h2, PREG_OFFSET_CAPTURE);
        if (!empty($markdown_h2[0])) {
            foreach ($markdown_h2[0] as $index => $match) {
                $headings[] = array(
                    'text' => trim($markdown_h2[1][$index][0]),
                    'level' => 2,
                    'format' => 'markdown',
                    'position' => $match[1],
                    'full_match' => $match[0],
                );
            }
        }
        
        // Detect HTML H2 (<h2>Heading</h2>) with position
        preg_match_all('/<h2.*?>(.*?)<\/h2>/is', $content, $html_h2, PREG_OFFSET_CAPTURE);
        if (!empty($html_h2[0])) {
            foreach ($html_h2[0] as $index => $match) {
                $headings[] = array(
                    'text' => trim(wp_strip_all_tags($html_h2[1][$index][0])),
                    'level' => 2,
                    'format' => 'html',
                    'position' => $match[1],
                    'full_match' => $match[0],
                );
            }
        }
        
        // Detect markdown H3 (### Heading) with position
        preg_match_all('/^###\s+(.+?)$/m', $content, $markdown_h3, PREG_OFFSET_CAPTURE);
        if (!empty($markdown_h3[0])) {
            foreach ($markdown_h3[0] as $index => $match) {
                $headings[] = array(
                    'text' => trim($markdown_h3[1][$index][0]),
                    'level' => 3,
                    'format' => 'markdown',
                    'position' => $match[1],
                    'full_match' => $match[0],
                );
            }
        }
        
        // Sort by position
        usort($headings, function($a, $b) {
            return $a['position'] - $b['position'];
        });
        
        return $headings;
    }
    
    /**
     * Extract section content between headings
     * 
     * @param string $content Full content
     * @param array $heading Current heading
     * @param array $next_heading Next heading (or null)
     * @return string Section content
     */
    private function extract_section_content($content, $heading, $next_heading = null) {
        $start_pos = $heading['position'] + strlen($heading['full_match']);
        
        if ($next_heading) {
            $end_pos = $next_heading['position'];
        } else {
            $end_pos = strlen($content);
        }
        
        $section_content = substr($content, $start_pos, $end_pos - $start_pos);
        $section_content = trim($section_content);
        
        // If content is empty or too short, try to get next 2-3 paragraphs
        if (strlen($section_content) < 50) {
            $paragraphs = array_filter(explode("\n\n", $section_content));
            if (count($paragraphs) < 2) {
                // Try to extract more content after heading
                $remaining_content = substr($content, $start_pos);
                $paragraphs = array_filter(explode("\n\n", $remaining_content));
                $section_content = implode("\n\n", array_slice($paragraphs, 0, 3));
            }
        }
        
        return $section_content;
    }
    
    /**
     * Detect intro section
     * 
     * @param string $content Content
     * @param array $headings Detected headings
     * @return string Intro content
     */
    private function detect_intro_section($content, $headings) {
        // If headings exist, get content before first heading
        if (!empty($headings)) {
            // Find first heading position
            $first_heading_text = $headings[0]['text'];
            $heading_patterns = array(
                '/^##\s+' . preg_quote($first_heading_text, '/') . '/m',
                '/<h2.*?>' . preg_quote($first_heading_text, '/') . '<\/h2>/is',
            );
            
            foreach ($heading_patterns as $pattern) {
                if (preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                    $intro = trim(substr($content, 0, $matches[0][1]));
                    if (strlen($intro) > 50) { // Minimum 50 chars
                        return $intro;
                    }
                }
            }
        }
        
        // Fallback: Get first 3 paragraphs
        $paragraphs = array_filter(explode("\n\n", $content));
        if (count($paragraphs) >= 3) {
            return implode("\n\n", array_slice($paragraphs, 0, 3));
        }
        
        // Last fallback: Get first 500 chars
        if (strlen($content) > 500) {
            return substr($content, 0, 500) . '...';
        }
        
        return '';
    }
    
    /**
     * Detect FAQ section
     * 
     * @param string $content Content
     * @return string FAQ content
     */
    private function detect_faq_section($content) {
        $faq_patterns = array(
            '/##\s+(Sıkça Sorulan Sorular|SSS|FAQ|Frequently Asked Questions)(.*?)(?=##|$)/is',
            '/##\s+.*?(FAQ|SSS).*?(.*?)(?=##|$)/is',
            '/<h2.*?>(.*?(?:FAQ|SSS|Sıkça Sorulan Sorular).*?)<\/h2>(.*?)(?=<h2|$)/is',
        );
        
        foreach ($faq_patterns as $pattern) {
            if (preg_match($pattern, $content, $matches)) {
                return trim($matches[0]);
            }
        }
        
        return '';
    }
    
    /**
     * Detect CTA section with raw content offset tracking
     * 
     * @param string $content Parsed content
     * @param string $raw_content Raw original content
     * @return array CTA result with content, offsets, and hash
     */
    private function detect_cta_section($content, $raw_content) {
        // Get last 2 paragraphs from parsed content
        $paragraphs = array_filter(explode("\n\n", $content));
        
        $cta_parsed = '';
        
        if (count($paragraphs) >= 2) {
            $last_paragraphs = array_slice($paragraphs, -2);
            $cta_parsed = implode("\n\n", $last_paragraphs);
            
            // Minimum 50 chars
            if (strlen($cta_parsed) < 50) {
                $cta_parsed = '';
            }
        }
        
        // Fallback: Last 200 chars
        if (empty($cta_parsed) && strlen($content) > 200) {
            $cta_parsed = substr($content, -200);
        }
        
        if (empty($cta_parsed)) {
            return array(
                'content' => '',
                'raw_content' => '',
                'start_offset' => -1,
                'end_offset' => -1,
                'hash' => '',
            );
        }
        
        // Try to find this CTA in raw content (search in last 3000 chars only)
        $search_zone_length = 3000;
        $raw_length = strlen($raw_content);
        $search_start = max(0, $raw_length - $search_zone_length);
        $search_zone = substr($raw_content, $search_start);
        
        // Try exact match first
        $cta_offset = $this->find_content_offset($search_zone, $cta_parsed);
        
        if ($cta_offset['start'] >= 0) {
            // Found in search zone - adjust offset to full content
            $actual_start = $search_start + $cta_offset['start'];
            $actual_end = $search_start + $cta_offset['end'];
            
            // Extract raw substring
            $raw_substring = substr($raw_content, $actual_start, $actual_end - $actual_start);
            
            return array(
                'content' => $cta_parsed,
                'raw_content' => $raw_substring,
                'start_offset' => $actual_start,
                'end_offset' => $actual_end,
                'hash' => md5($raw_substring),
            );
        }
        
        // Offset not found - return content but mark offset as unavailable
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[DODO CTA DEBUG] WARNING: CTA offset not found in raw content');
        }
        
        return array(
            'content' => $cta_parsed,
            'raw_content' => $cta_parsed,
            'start_offset' => -1,
            'end_offset' => -1,
            'hash' => md5($cta_parsed),
        );
    }
    
    /**
     * Create fallback sections (minimum viable)
     * 
     * @param string $content Content
     * @return array Fallback sections
     */
    private function create_fallback_sections($content) {
        $sections = array();
        
        // Split content into paragraphs
        $paragraphs = array_filter(explode("\n\n", $content));
        $total_paragraphs = count($paragraphs);
        
        if ($total_paragraphs === 0) {
            // No paragraphs, use entire content as body
            $sections[] = array(
                'title' => __('Content Body', 'dodo-ai-seo'),
                'type' => 'section',
                'content' => $content,
            );
            return $sections;
        }
        
        // Intro: First 30% of paragraphs (min 1, max 3)
        $intro_count = max(1, min(3, ceil($total_paragraphs * 0.3)));
        $intro_paragraphs = array_slice($paragraphs, 0, $intro_count);
        $sections[] = array(
            'title' => __('Introduction', 'dodo-ai-seo'),
            'type' => 'intro',
            'content' => implode("\n\n", $intro_paragraphs),
        );
        
        // Body: Middle 50% of paragraphs
        if ($total_paragraphs > 3) {
            $body_start = $intro_count;
            $body_count = max(1, floor($total_paragraphs * 0.5));
            $body_paragraphs = array_slice($paragraphs, $body_start, $body_count);
            
            $sections[] = array(
                'title' => __('Main Content', 'dodo-ai-seo'),
                'type' => 'section',
                'content' => implode("\n\n", $body_paragraphs),
            );
        }
        
        // CTA: Last 20% of paragraphs (min 1, max 2)
        $cta_count = max(1, min(2, ceil($total_paragraphs * 0.2)));
        $cta_paragraphs = array_slice($paragraphs, -$cta_count);
        $sections[] = array(
            'title' => __('Call to Action', 'dodo-ai-seo'),
            'type' => 'cta',
            'content' => implode("\n\n", $cta_paragraphs),
        );
        
        return $sections;
    }
    
    /**
     * Render Revision History page (NEW - Task 6)
     */
    public function render_revision_history_page() {
        require_once DODO_PLUGIN_DIR . 'admin/views/page-revision-history.php';
    }
    
    /**
     * AJAX: Get revisions (NEW - Task 6)
     * HARDENED: Final Completion Phase - AJAX Hardening
     */
    public function ajax_get_revisions() {
        try {
            // Defensive nonce check
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dodo_revision_nonce')) {
                wp_send_json_error(array('message' => 'Güvenlik doğrulaması başarısız'));
                return;
            }
            
            // Capability check
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array(
                    'message' => __('Bu işlemi gerçekleştirmek için yetkiniz yok.', 'dodo-ai-seo'),
                ));
                return;
            }
            
            // Get parameters
            $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : null;
            $improve_type = isset($_POST['improve_type']) ? sanitize_text_field($_POST['improve_type']) : null;
            $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
            $per_page = 20;
            
            // Get revisions
            $revision_manager = new DODO_Revision_Manager();
            
            $args = array(
                'post_id' => $post_id,
                'improve_type' => $improve_type,
                'limit' => $per_page,
                'offset' => ($page - 1) * $per_page,
                'order' => 'DESC',
            );
            
            $revisions = $revision_manager->get_all_revisions($args);
            $total = $revision_manager->get_revision_count(array(
                'post_id' => $post_id,
                'improve_type' => $improve_type,
            ));
            
            // Format revisions for display
            $formatted_revisions = array();
            foreach ($revisions as $revision) {
                $post = get_post($revision['post_id']);
                $user = get_userdata($revision['user_id']);
                
                $formatted_revisions[] = array(
                    'id' => $revision['id'],
                    'post_id' => $revision['post_id'],
                    'post_title' => $post ? $post->post_title : __('(Yazı bulunamadı)', 'dodo-ai-seo'),
                    'improve_type' => $revision['improve_type'],
                    'rewrite_necessity' => $revision['rewrite_necessity'],
                    'ai_confidence_score' => $revision['ai_confidence_score'],
                    'user_name' => $user ? $user->display_name : __('Bilinmeyen', 'dodo-ai-seo'),
                    'created_at' => $revision['created_at'],
                    'created_at_formatted' => date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($revision['created_at'])),
                );
            }
            
            wp_send_json_success(array(
                'revisions' => $formatted_revisions,
                'total' => $total,
                'page' => $page,
                'per_page' => $per_page,
                'total_pages' => ceil($total / $per_page),
            ));
            
        } catch (Throwable $e) {
            error_log('[DODO AJAX ERROR] ajax_get_revisions: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => 'İşlem sırasında hata oluştu',
                'error' => WP_DEBUG ? $e->getMessage() : null
            ));
        }
    }
    
    /**
     * AJAX: Get revision for compare (NEW - Task 6)
     * HARDENED: Final Completion Phase - AJAX Hardening
     */
    public function ajax_get_revision_compare() {
        try {
            // Defensive nonce check
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dodo_revision_nonce')) {
                wp_send_json_error(array('message' => 'Güvenlik doğrulaması başarısız'));
                return;
            }
            
            // Capability check
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array(
                    'message' => __('Bu işlemi gerçekleştirmek için yetkiniz yok.', 'dodo-ai-seo'),
                ));
                return;
            }
            
            // Get parameters
            $revision_id = intval($_POST['revision_id'] ?? 0);
            
            if (empty($revision_id)) {
                wp_send_json_error(array(
                    'message' => __('Geçersiz revizyon ID.', 'dodo-ai-seo'),
                ));
                return;
            }
            
            // Get revision
            $revision_manager = new DODO_Revision_Manager();
            $revision = $revision_manager->get_revision($revision_id);
            
            if (!$revision) {
                wp_send_json_error(array(
                    'message' => __('Revizyon bulunamadı.', 'dodo-ai-seo'),
                ));
                return;
            }
            
            // Get post info
            $post = get_post($revision['post_id']);
            $user = get_userdata($revision['user_id']);
            
            // Format response
            wp_send_json_success(array(
                'revision' => array(
                    'id' => $revision['id'],
                    'post_title' => $post ? $post->post_title : __('(Yazı bulunamadı)', 'dodo-ai-seo'),
                    'improve_type' => $revision['improve_type'],
                    'original_content' => $revision['original_content'],
                    'improved_content' => $revision['improved_content'],
                    'diff_html' => $revision['diff_html'],
                    'metadata' => $revision['metadata'],
                    'rewrite_necessity' => $revision['rewrite_necessity'],
                    'ai_confidence_score' => $revision['ai_confidence_score'],
                    'user_name' => $user ? $user->display_name : __('Bilinmeyen', 'dodo-ai-seo'),
                    'created_at_formatted' => date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($revision['created_at'])),
                ),
            ));
            
        } catch (Throwable $e) {
            error_log('[DODO AJAX ERROR] ajax_get_revision_compare: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => 'İşlem sırasında hata oluştu',
                'error' => WP_DEBUG ? $e->getMessage() : null
            ));
        }
    }
    
    /**
     * AJAX: Restore revision (NEW - Task 6)
     * HARDENED: Final Completion Phase - AJAX Hardening
     */
    public function ajax_restore_revision() {
        try {
            // Defensive nonce check
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dodo_revision_nonce')) {
                wp_send_json_error(array('message' => 'Güvenlik doğrulaması başarısız'));
                return;
            }
            
            // Capability check
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array(
                    'message' => __('Bu işlemi gerçekleştirmek için yetkiniz yok.', 'dodo-ai-seo'),
                ));
                return;
            }
            
            // Get parameters
            $revision_id = intval($_POST['revision_id'] ?? 0);
            
            if (empty($revision_id)) {
                wp_send_json_error(array(
                    'message' => __('Geçersiz revizyon ID.', 'dodo-ai-seo'),
                ));
                return;
            }
            
            // Restore revision
            $revision_manager = new DODO_Revision_Manager();
            $result = $revision_manager->restore_revision($revision_id);
            
            if (is_wp_error($result)) {
                wp_send_json_error(array(
                    'message' => $result->get_error_message(),
                ));
                return;
            }
            
            wp_send_json_success(array(
                'message' => __('Revizyon başarıyla geri yüklendi!', 'dodo-ai-seo'),
            ));
            
        } catch (Throwable $e) {
            error_log('[DODO AJAX ERROR] ajax_restore_revision: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => 'İşlem sırasında hata oluştu',
                'error' => WP_DEBUG ? $e->getMessage() : null
            ));
        }
    }
    
    /**
     * AJAX: Delete revision (NEW - Task 6)
     * HARDENED: Final Completion Phase - AJAX Hardening
     */
    public function ajax_delete_revision() {
        try {
            // Defensive nonce check
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dodo_revision_nonce')) {
                wp_send_json_error(array('message' => 'Güvenlik doğrulaması başarısız'));
                return;
            }
            
            // Capability check
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array(
                    'message' => __('Bu işlemi gerçekleştirmek için yetkiniz yok.', 'dodo-ai-seo'),
                ));
                return;
            }
            
            // Get parameters
            $revision_id = intval($_POST['revision_id'] ?? 0);
            
            if (empty($revision_id)) {
                wp_send_json_error(array(
                    'message' => __('Geçersiz revizyon ID.', 'dodo-ai-seo'),
                ));
                return;
            }
            
            // Delete revision
            $revision_manager = new DODO_Revision_Manager();
            $deleted = $revision_manager->delete_revision($revision_id);
            
            if (!$deleted) {
                wp_send_json_error(array(
                    'message' => __('Revizyon silinemedi.', 'dodo-ai-seo'),
                ));
                return;
            }
            
            wp_send_json_success(array(
                'message' => __('Revizyon başarıyla silindi!', 'dodo-ai-seo'),
            ));
            
        } catch (Throwable $e) {
            error_log('[DODO AJAX ERROR] ajax_delete_revision: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => 'İşlem sırasında hata oluştu',
                'error' => WP_DEBUG ? $e->getMessage() : null
            ));
        }
    }
    
    /**
     * Robust content replacement with multiple strategies
     * 
     * @param string $haystack Current post content
     * @param string $needle Original content to find
     * @param string $replacement Improved content
     * @return array Result with success, content, strategy, confidence
     */
    private function robust_content_replace($haystack, $needle, $replacement) {
        // Strategy 1: Exact match
        if (strpos($haystack, $needle) !== false) {
            $count = substr_count($haystack, $needle);
            
            if ($count > 1) {
                return array(
                    'success' => false,
                    'strategy' => 'exact',
                    'reason' => 'ambiguous_multiple_matches',
                    'message' => __('İçerik birden fazla yerde bulundu. Lütfen daha spesifik bir bölüm seçin.', 'dodo-ai-seo'),
                );
            }
            
            return array(
                'success' => true,
                'content' => str_replace($needle, $replacement, $haystack),
                'strategy' => 'exact',
                'confidence' => 100,
            );
        }
        
        // Strategy 2: Normalized match
        $normalized_result = $this->normalized_content_match($haystack, $needle, $replacement);
        if ($normalized_result['success']) {
            return $normalized_result;
        }
        
        // Strategy 3: Fuzzy match (90%+ similarity)
        $fuzzy_result = $this->fuzzy_content_match($haystack, $needle, $replacement);
        if ($fuzzy_result['success']) {
            return $fuzzy_result;
        }
        
        // All strategies failed
        return array(
            'success' => false,
            'strategy' => 'all_failed',
            'reason' => 'no_match_found',
            'message' => __('Orijinal içerik yazıda bulunamadı. İçerik değiştirilmiş olabilir. Lütfen sayfayı yenileyin.', 'dodo-ai-seo'),
        );
    }
    
    /**
     * Normalized content matching
     */
    private function normalized_content_match($haystack, $needle, $replacement) {
        // Use the normalize_content method
        $normalized_haystack = $this->normalize_content($haystack);
        $normalized_needle = $this->normalize_content($needle);
        
        if (strpos($normalized_haystack, $normalized_needle) !== false) {
            $count = substr_count($normalized_haystack, $normalized_needle);
            
            if ($count > 1) {
                return array(
                    'success' => false,
                    'strategy' => 'normalized',
                    'reason' => 'ambiguous_multiple_matches',
                    'message' => __('İçerik birden fazla yerde bulundu (normalize). Lütfen daha spesifik bir bölüm seçin.', 'dodo-ai-seo'),
                );
            }
            
            // Find original position in non-normalized content
            $pos = strpos($normalized_haystack, $normalized_needle);
            
            // Approximate position in original content
            $before_normalized = substr($normalized_haystack, 0, $pos);
            $before_original_length = strlen($normalize(substr($haystack, 0, strlen($before_normalized) + 100)));
            
            // Find actual substring in original content around that position
            $search_start = max(0, $before_original_length - 50);
            $search_length = strlen($needle) + 200;
            $search_area = substr($haystack, $search_start, $search_length);
            
            // Try to find best match in search area
            $best_match_pos = $this->find_best_substring_match($search_area, $needle);
            
            if ($best_match_pos !== false) {
                $actual_pos = $search_start + $best_match_pos;
                $actual_length = strlen($needle);
                
                // Replace at exact position
                $new_content = substr_replace($haystack, $replacement, $actual_pos, $actual_length);
                
                return array(
                    'success' => true,
                    'content' => $new_content,
                    'strategy' => 'normalized',
                    'confidence' => 95,
                );
            }
        }
        
        return array('success' => false);
    }
    
    /**
     * Fuzzy content matching (90%+ similarity)
     */
    private function fuzzy_content_match($haystack, $needle, $replacement) {
        $needle_length = strlen($needle);
        $haystack_length = strlen($haystack);
        
        // Don't try fuzzy on very large content
        if ($haystack_length > 50000 || $needle_length > 10000) {
            return array('success' => false);
        }
        
        $best_similarity = 0;
        $best_pos = false;
        $best_length = 0;
        
        // Sliding window approach
        $window_size = $needle_length;
        $step = max(10, intval($needle_length / 10));
        
        for ($i = 0; $i < $haystack_length - $window_size; $i += $step) {
            $window = substr($haystack, $i, $window_size);
            
            similar_text($needle, $window, $percent);
            
            if ($percent > $best_similarity) {
                $best_similarity = $percent;
                $best_pos = $i;
                $best_length = $window_size;
            }
            
            // Early exit if perfect match found
            if ($percent >= 99) {
                break;
            }
        }
        
        // Require 90%+ similarity
        if ($best_similarity >= 90 && $best_pos !== false) {
            // Check for ambiguous matches
            $matches_found = 0;
            for ($i = 0; $i < $haystack_length - $window_size; $i += $step) {
                $window = substr($haystack, $i, $window_size);
                similar_text($needle, $window, $percent);
                if ($percent >= 90) {
                    $matches_found++;
                }
            }
            
            if ($matches_found > 1) {
                return array(
                    'success' => false,
                    'strategy' => 'fuzzy',
                    'reason' => 'ambiguous_multiple_matches',
                    'message' => __('İçerik birden fazla yerde bulundu (fuzzy). Lütfen daha spesifik bir bölüm seçin.', 'dodo-ai-seo'),
                );
            }
            
            // Replace at best position
            $new_content = substr_replace($haystack, $replacement, $best_pos, $best_length);
            
            return array(
                'success' => true,
                'content' => $new_content,
                'strategy' => 'fuzzy',
                'confidence' => intval($best_similarity),
            );
        }
        
        return array('success' => false);
    }
    
    /**
     * Find best substring match position
     */
    private function find_best_substring_match($haystack, $needle) {
        $needle_length = strlen($needle);
        $haystack_length = strlen($haystack);
        
        if ($needle_length > $haystack_length) {
            return false;
        }
        
        $best_similarity = 0;
        $best_pos = false;
        
        for ($i = 0; $i <= $haystack_length - $needle_length; $i++) {
            $substring = substr($haystack, $i, $needle_length);
            similar_text($needle, $substring, $percent);
            
            if ($percent > $best_similarity) {
                $best_similarity = $percent;
                $best_pos = $i;
            }
            
            if ($percent >= 99) {
                return $i;
            }
        }
        
        return $best_similarity >= 85 ? $best_pos : false;
    }
    
    /**
     * AJAX: Save score history (Sprint 2B - Task 7)
     * HARDENED: Final Completion Phase - AJAX Hardening
     */
    public function ajax_save_score_history() {
        try {
            // Defensive nonce check
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dodo_improver_nonce')) {
                wp_send_json_error(array('message' => 'Güvenlik doğrulaması başarısız'));
                return;
            }
            
            // Capability check
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array(
                    'message' => __('Bu işlemi gerçekleştirmek için yetkiniz yok.', 'dodo-ai-seo'),
                ));
                return;
            }
            
            // Get parameters
            $post_id = intval($_POST['post_id'] ?? 0);
            $revision_id = intval($_POST['revision_id'] ?? 0);
            $before_scores = isset($_POST['before_scores']) ? json_decode(stripslashes($_POST['before_scores']), true) : array();
            $after_scores = isset($_POST['after_scores']) ? json_decode(stripslashes($_POST['after_scores']), true) : array();
            
            // Validate
            if (empty($post_id) || empty($before_scores) || empty($after_scores)) {
                wp_send_json_error(array(
                    'message' => __('Geçersiz parametreler.', 'dodo-ai-seo'),
                ));
                return;
            }
            
            // Insert into database
            global $wpdb;
            $table_name = $wpdb->prefix . 'dodo_score_history';
            
            $result = $wpdb->insert(
                $table_name,
                array(
                    'post_id' => $post_id,
                    'revision_id' => $revision_id,
                    'before_health_score' => floatval($before_scores['health_score'] ?? 0),
                    'after_health_score' => floatval($after_scores['health_score'] ?? 0),
                    'before_seo_score' => floatval($before_scores['seo_score'] ?? 0),
                    'after_seo_score' => floatval($after_scores['seo_score'] ?? 0),
                    'before_quality_score' => floatval($before_scores['quality_score'] ?? 0),
                    'after_quality_score' => floatval($after_scores['quality_score'] ?? 0),
                    'before_readability_score' => floatval($before_scores['readability_score'] ?? 0),
                    'after_readability_score' => floatval($after_scores['readability_score'] ?? 0),
                    'before_semantic_score' => floatval($before_scores['semantic_score'] ?? 0),
                    'after_semantic_score' => floatval($after_scores['semantic_score'] ?? 0),
                    'before_ai_risk_score' => floatval($before_scores['ai_risk_score'] ?? 0),
                    'after_ai_risk_score' => floatval($after_scores['ai_risk_score'] ?? 0),
                    'before_confidence_score' => floatval($before_scores['confidence_score'] ?? 0),
                    'after_confidence_score' => floatval($after_scores['confidence_score'] ?? 0),
                    'created_at' => current_time('mysql'),
                ),
                array(
                    '%d', '%d', '%f', '%f', '%f', '%f', '%f', '%f', '%f', '%f', '%f', '%f', '%f', '%f', '%f', '%f', '%s'
                )
            );
            
            if ($result === false) {
                error_log('[DODO SCORE HISTORY] Database insert failed: ' . $wpdb->last_error);
                wp_send_json_error(array(
                    'message' => __('Score history kaydedilemedi.', 'dodo-ai-seo'),
                    'db_error' => WP_DEBUG ? $wpdb->last_error : null
                ));
                return;
            }
            
            $history_id = $wpdb->insert_id;
            error_log('[DODO SCORE HISTORY] Saved successfully - ID: ' . $history_id);
            
            wp_send_json_success(array(
                'message' => __('Score history kaydedildi.', 'dodo-ai-seo'),
                'history_id' => $history_id,
            ));
            
        } catch (Throwable $e) {
            error_log('[DODO AJAX ERROR] ajax_save_score_history: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => 'İşlem sırasında hata oluştu',
                'error' => WP_DEBUG ? $e->getMessage() : null
            ));
        }
    }
    
    /**
     * AJAX: Rollback revision (Sprint 1B - Task 3 + Task 4: Score History Integration)
     * HARDENED: Final Completion Phase - AJAX Hardening
     */
    public function ajax_rollback_revision() {
        try {
            // Defensive nonce check
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dodo_improver_nonce')) {
                wp_send_json_error(array('message' => 'Güvenlik doğrulaması başarısız'));
                return;
            }
            
            // Capability check
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array(
                    'message' => __('Bu işlemi gerçekleştirmek için yetkiniz yok.', 'dodo-ai-seo'),
                ));
                return;
            }
            
            // Get parameters
            $revision_id = intval($_POST['revision_id'] ?? 0);
            
            // Validate
            if (empty($revision_id)) {
                wp_send_json_error(array(
                    'message' => __('Geçersiz revizyon ID.', 'dodo-ai-seo'),
                ));
                return;
            }
            
            // Get revision data
            global $wpdb;
            $table_name = $wpdb->prefix . 'dodo_revisions';
            
            $revision = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE id = %d",
                $revision_id
            ));
            
            if (!$revision) {
                wp_send_json_error(array(
                    'message' => __('Revizyon bulunamadı.', 'dodo-ai-seo'),
                ));
                return;
            }
            
            // Get post
            $post = get_post($revision->post_id);
            if (!$post) {
                wp_send_json_error(array(
                    'message' => __('Yazı bulunamadı.', 'dodo-ai-seo'),
                ));
                return;
            }
            
            // Get score history for this revision
            $score_history_table = $wpdb->prefix . 'dodo_score_history';
            $score_history = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$score_history_table} WHERE revision_id = %d ORDER BY created_at DESC LIMIT 1",
                $revision_id
            ));
            
            // Restore previous content
            $restore_result = wp_update_post(array(
                'ID' => $revision->post_id,
                'post_content' => $revision->content_before,
            ), true);
            
            if (is_wp_error($restore_result)) {
                error_log('[DODO ROLLBACK] Failed to restore content: ' . $restore_result->get_error_message());
                wp_send_json_error(array(
                    'message' => __('İçerik geri yüklenemedi.', 'dodo-ai-seo'),
                    'error' => WP_DEBUG ? $restore_result->get_error_message() : null
                ));
                return;
            }
            
            // Mark revision as rolled back
            $wpdb->update(
                $table_name,
                array('rolled_back' => 1),
                array('id' => $revision_id),
                array('%d'),
                array('%d')
            );
            
            // Prepare response with restored scores
            $restored_scores = null;
            if ($score_history) {
                $restored_scores = array(
                    'health_score' => floatval($score_history->before_health_score),
                    'seo_score' => floatval($score_history->before_seo_score),
                    'quality_score' => floatval($score_history->before_quality_score),
                    'readability_score' => floatval($score_history->before_readability_score),
                    'semantic_score' => floatval($score_history->before_semantic_score),
                    'ai_risk_score' => floatval($score_history->before_ai_risk_score),
                    'confidence_score' => floatval($score_history->before_confidence_score),
                );
            }
            
            error_log('[DODO ROLLBACK] Successfully rolled back revision #' . $revision_id);
            if ($restored_scores) {
                error_log('[DODO ROLLBACK] Score history restored to previous state');
            }
            
            wp_send_json_success(array(
                'message' => __('İyileştirme geri alındı.', 'dodo-ai-seo'),
                'post_id' => $revision->post_id,
                'restored_scores' => $restored_scores,
                'score_history_restored' => !empty($restored_scores),
            ));
            
        } catch (Throwable $e) {
            error_log('[DODO AJAX ERROR] ajax_rollback_revision: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => 'İşlem sırasında hata oluştu',
                'error' => WP_DEBUG ? $e->getMessage() : null
            ));
        }
    }
    
    /**
     * AJAX: Get semantic link suggestions (Sprint 3 - Task 4)
     * HARDENED: Final Completion Phase - AJAX Hardening
     */
    public function ajax_get_link_suggestions() {
        try {
            // Defensive nonce check
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dodo_improver_nonce')) {
                wp_send_json_error(array('message' => 'Güvenlik doğrulaması başarısız'));
                return;
            }
            
            // Capability check
            if (!current_user_can('edit_posts')) {
                wp_send_json_error(array('message' => 'Yetkiniz yok'));
                return;
            }
            
            // Validate parameters
            $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
            $focus_keyword = isset($_POST['focus_keyword']) ? sanitize_text_field(wp_unslash($_POST['focus_keyword'])) : '';
            
            if (!$post_id) {
                wp_send_json_error(array('message' => 'Post ID gerekli'));
                return;
            }
            
            $post = get_post($post_id);
            if (!$post) {
                wp_send_json_error(array('message' => 'Post bulunamadı'));
                return;
            }
            
            // Get link suggestions
            $link_engine = new DODO_Semantic_Link_Engine();
            $suggestions = $link_engine->get_link_suggestions($post_id, $post->post_content, $focus_keyword);
            
            // Get authority flow analysis
            $authority_flow = $link_engine->analyze_authority_flow($post_id);
            
            wp_send_json_success(array(
                'suggestions' => $suggestions,
                'authority_flow' => $authority_flow,
                'total_suggestions' => count($suggestions),
            ));
            
        } catch (Throwable $e) {
            error_log('[DODO AJAX ERROR] ajax_get_link_suggestions: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => 'İşlem sırasında hata oluştu',
                'error' => WP_DEBUG ? $e->getMessage() : null
            ));
        }
    }
    
    /**
     * AJAX: Detect orphan pages (Sprint 3 - Task 4)
     * HARDENED: Final Completion Phase - AJAX Hardening
     */
    public function ajax_detect_orphan_pages() {
        try {
            // Defensive nonce check
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dodo_improver_nonce')) {
                wp_send_json_error(array('message' => 'Güvenlik doğrulaması başarısız'));
                return;
            }
            
            // Capability check
            if (!current_user_can('edit_posts')) {
                wp_send_json_error(array('message' => 'Yetkiniz yok'));
                return;
            }
            
            $link_engine = new DODO_Semantic_Link_Engine();
            $orphans = $link_engine->detect_orphan_pages();
            
            wp_send_json_success(array(
                'orphans' => $orphans,
                'total_orphans' => count($orphans),
            ));
            
        } catch (Throwable $e) {
            error_log('[DODO AJAX ERROR] ajax_detect_orphan_pages: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => 'İşlem sırasında hata oluştu',
                'error' => WP_DEBUG ? $e->getMessage() : null
            ));
        }
    }
    
    /**
     * AJAX: Analyze authority flow (Sprint 3 - Task 4)
     * HARDENED: Final Completion Phase - AJAX Hardening
     */
    public function ajax_analyze_authority_flow() {
        try {
            // Defensive nonce check
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dodo_improver_nonce')) {
                wp_send_json_error(array('message' => 'Güvenlik doğrulaması başarısız'));
                return;
            }
            
            // Capability check
            if (!current_user_can('edit_posts')) {
                wp_send_json_error(array('message' => 'Yetkiniz yok'));
                return;
            }
            
            // Validate parameters
            $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
            
            if (!$post_id) {
                wp_send_json_error(array('message' => 'Post ID gerekli'));
                return;
            }
            
            $link_engine = new DODO_Semantic_Link_Engine();
            $flow_analysis = $link_engine->analyze_authority_flow($post_id);
            
            wp_send_json_success($flow_analysis);
            
        } catch (Throwable $e) {
            error_log('[DODO AJAX ERROR] ajax_analyze_authority_flow: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => 'İşlem sırasında hata oluştu',
                'error' => WP_DEBUG ? $e->getMessage() : null
            ));
        }
    }
    
    /**
     * Render Content Clusters page (Sprint 3 - Task 1)
     */
    public function render_content_clusters_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Bu sayfaya erişim yetkiniz yok.', 'dodo-ai-seo'));
        }
        
        include DODO_PLUGIN_DIR . 'admin/views/page-content-clusters.php';
    }
    
    /**
     * Render Analytics Dashboard page (Sprint 3 - Task 5)
     */
    public function render_analytics_dashboard_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Bu sayfaya erişim yetkiniz yok.', 'dodo-ai-seo'));
        }
        
        include DODO_PLUGIN_DIR . 'admin/views/page-analytics-dashboard.php';
    }
    
    /**
     * AJAX: Analyze Clusters (Sprint 3 - Task 1)
     * HARDENED: Final Completion Phase - AJAX Hardening
     */
    public function ajax_analyze_clusters() {
        try {
            // Defensive nonce check
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dodo_clusters_nonce')) {
                wp_send_json_error(array('message' => 'Güvenlik doğrulaması başarısız'));
                return;
            }
            
            // Capability check
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => 'Unauthorized'));
                return;
            }
            
            $cluster_engine = new DODO_Cluster_Engine();
            $analysis = $cluster_engine->analyze_site_clusters();
            
            wp_send_json_success($analysis);
            
        } catch (Throwable $e) {
            error_log('[DODO AJAX ERROR] ajax_analyze_clusters: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => 'İşlem sırasında hata oluştu',
                'error' => WP_DEBUG ? $e->getMessage() : null
            ));
        }
    }
    
    /**
     * AJAX: Refresh Analytics (Sprint 3 - Task 5)
     * HARDENED: Final Completion Phase - AJAX Hardening
     */
    public function ajax_refresh_analytics() {
        try {
            // Defensive nonce check
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dodo_analytics_nonce')) {
                wp_send_json_error(array('message' => 'Güvenlik doğrulaması başarısız'));
                return;
            }
            
            // Capability check
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => 'Yetkisiz erişim'));
                return;
            }
            
            global $wpdb;
            $table_name = $wpdb->prefix . 'dodo_analytics_history';
            $today = date('Y-m-d');
            
        // Get all published posts
        $posts = get_posts(array(
            'post_type' => array('post', 'page'),
            'post_status' => 'publish',
            'posts_per_page' => -1,
        ));
        
        $processed = 0;
        
        foreach ($posts as $post) {
            // Check if already recorded today
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table_name WHERE post_id = %d AND recorded_at = %s",
                $post->ID,
                $today
            ));
            
            if ($exists) {
                continue;
            }
            
            // Get focus keyword
            $focus_keyword = get_post_meta($post->ID, 'rank_math_focus_keyword', true);
            if (empty($focus_keyword)) {
                continue;
            }
            
            // Calculate real intelligence scores
            $score_calculator = new DODO_Score_Calculator();
            $health_score = $score_calculator->calculate_health_score($post->ID);
            
            // Get other scores from content analysis
            $content_analyzer = new DODO_Content_Analyzer();
            $analysis = $content_analyzer->analyze($post->post_content, $focus_keyword);
            
            $seo_score = isset($analysis['seo_score']) ? $analysis['seo_score'] : 75;
            $quality_score = isset($analysis['quality_score']) ? $analysis['quality_score'] : 75;
            $readability_score = isset($analysis['readability_score']) ? $analysis['readability_score'] : 70;
            
            // Semantic analysis
            $semantic_analyzer = new DODO_Semantic_Analyzer();
            $semantic_analysis = $semantic_analyzer->analyze($post->post_content, $focus_keyword);
            $semantic_score = $semantic_analysis['semantic_score'] ?? 75;
            
            // AI detection
            $ai_detector = new DODO_AI_Detector();
            $ai_detection = $ai_detector->analyze($post->post_content);
            $ai_risk_score = $ai_detection['ai_risk_score'] ?? 20;
            
            // GEO analysis
            $geo_analyzer = new DODO_GEO_Analyzer();
            $geo_analysis = $geo_analyzer->analyze($post->post_content, $focus_keyword);
            $geo_score = $geo_analysis['geo_score'] ?? 70;
            
            // Entity coverage
            $entity_enrichment = new DODO_Entity_Enrichment();
            $entity_data = $entity_enrichment->analyze_entities($post->post_content);
            $entity_coverage = $entity_data['semantic_coverage'] ?? 75;
            
            // Workflow status
            $workflow_engine = new DODO_Workflow_Engine();
            $workflow_status = $workflow_engine->get_post_workflow_status($post->ID);
            
            // Insert record
            $inserted = $wpdb->insert(
                $table_name,
                array(
                    'post_id' => $post->ID,
                    'health_score' => $health_score,
                    'seo_score' => $seo_score,
                    'quality_score' => $quality_score,
                    'readability_score' => $readability_score,
                    'semantic_score' => $semantic_score,
                    'ai_risk_score' => $ai_risk_score,
                    'geo_score' => $geo_score,
                    'entity_coverage' => $entity_coverage,
                    'workflow_status' => $workflow_status,
                    'recorded_at' => $today,
                ),
                array('%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%s')
            );
            
            if ($inserted) {
                $processed++;
            }
        }
        
        wp_send_json_success(array(
            'message' => sprintf('%d içerik analiz edildi', $processed),
            'processed' => $processed
        ));
        
        } catch (Throwable $e) {
            error_log('[DODO AJAX ERROR] ajax_refresh_analytics: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => 'İşlem sırasında hata oluştu',
                'error' => WP_DEBUG ? $e->getMessage() : null
            ));
        }
    }
    
    /**
     * Test API Key (AJAX - Sprint 4)
     * HARDENED: Final Completion Phase - AJAX Hardening
     */
    public function ajax_test_api_key() {
        try {
            // Defensive nonce check
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dodo_onboarding_nonce')) {
                wp_send_json_error(array('message' => 'Güvenlik doğrulaması başarısız'));
                return;
            }
            
            // Capability check
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => 'Insufficient permissions'));
                return;
            }
            
            // Validate API key
            $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
            
            if (empty($api_key)) {
                wp_send_json_error(array('message' => 'API key is required'));
                return;
            }
            
            // Test API key with OpenAI
            $openai = new DODO_OpenAI();
            $test_result = $openai->test_connection($api_key);
            
            if ($test_result['success']) {
                wp_send_json_success(array('message' => 'API key is valid'));
            } else {
                wp_send_json_error(array('message' => $test_result['message'] ?? 'API key is invalid'));
            }
            
        } catch (Throwable $e) {
            error_log('[DODO AJAX ERROR] ajax_test_api_key: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => 'İşlem sırasında hata oluştu',
                'error' => WP_DEBUG ? $e->getMessage() : null
            ));
        }
    }
    
    /**
     * Save Onboarding Settings (AJAX - Sprint 4)
     * HARDENED: Final Completion Phase - AJAX Hardening
     */
    public function ajax_save_onboarding_settings() {
        try {
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dodo_onboarding_nonce')) {
                wp_send_json_error(array('message' => 'Güvenlik doğrulaması başarısız'));
                return;
            }
            
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => 'Insufficient permissions'));
                return;
            }
            
            $new_settings = isset($_POST['settings']) ? $_POST['settings'] : array();
            
            if (empty($new_settings)) {
                wp_send_json_error(array('message' => 'No settings provided'));
                return;
            }
            
            $current_settings = get_option('dodo_ai_seo_settings', array());
            $updated_settings = array_merge($current_settings, $new_settings);
            update_option('dodo_ai_seo_settings', $updated_settings);
            
            wp_send_json_success(array('message' => 'Settings saved'));
            
        } catch (Throwable $e) {
            error_log('[DODO AJAX ERROR] ajax_save_onboarding_settings: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'İşlem sırasında hata oluştu', 'error' => WP_DEBUG ? $e->getMessage() : null));
        }
    }
    
    /**
     * Get System Health (AJAX - Sprint 4)
     * HARDENED: Final Completion Phase - AJAX Hardening
     */
    public function ajax_get_system_health() {
        try {
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dodo_onboarding_nonce')) {
                wp_send_json_error(array('message' => 'Güvenlik doğrulaması başarısız'));
                return;
            }
            
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => 'Insufficient permissions'));
                return;
            }
            
            $health_check = new DODO_Health_Check();
            $health = $health_check->run_all_checks();
            
            wp_send_json_success($health);
            
        } catch (Throwable $e) {
            error_log('[DODO AJAX ERROR] ajax_get_system_health: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'İşlem sırasında hata oluştu', 'error' => WP_DEBUG ? $e->getMessage() : null));
        }
    }
    
    /**
     * Complete Onboarding (AJAX - Sprint 4)
     * HARDENED: Final Completion Phase - AJAX Hardening
     */
    public function ajax_complete_onboarding() {
        try {
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dodo_onboarding_nonce')) {
                wp_send_json_error(array('message' => 'Güvenlik doğrulaması başarısız'));
                return;
            }
            
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => 'Insufficient permissions'));
                return;
            }
            
            update_option('dodo_onboarding_completed', true);
            
            wp_send_json_success(array('message' => 'Onboarding completed'));
            
        } catch (Throwable $e) {
            error_log('[DODO AJAX ERROR] ajax_complete_onboarding: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'İşlem sırasında hata oluştu', 'error' => WP_DEBUG ? $e->getMessage() : null));
        }
    }
    
    /**
     * Get Queue Jobs (AJAX - Sprint 4)
     * HARDENED: Final Completion Phase - AJAX Hardening
     */
    public function ajax_get_queue_jobs() {
        try {
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dodo_queue_nonce')) {
                wp_send_json_error(array('message' => 'Güvenlik doğrulaması başarısız'));
                return;
            }
            
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => 'Insufficient permissions'));
                return;
            }
            
            $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
            $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
            $limit = isset($_POST['limit']) ? absint($_POST['limit']) : 50;
            $offset = isset($_POST['offset']) ? absint($_POST['offset']) : 0;
            
            $queue = new DODO_Queue_Manager();
            
            $filters = array(
                'limit' => $limit,
                'offset' => $offset,
            );
            
            if (!empty($status) && $status !== 'all') {
                $filters['status'] = $status;
            }
            
            if (!empty($type) && $type !== 'all') {
                $filters['type'] = $type;
            }
            
            $jobs = $queue->get_jobs($filters);
            $stats = $queue->get_stats();
            
            wp_send_json_success(array(
                'jobs' => $jobs,
                'stats' => $stats
            ));
            
        } catch (Throwable $e) {
            error_log('[DODO AJAX ERROR] ajax_get_queue_jobs: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'İşlem sırasında hata oluştu', 'error' => WP_DEBUG ? $e->getMessage() : null));
        }
    }
    
    /**
     * Retry Queue Job (AJAX - Sprint 4)
     * HARDENED: Final Completion Phase - AJAX Hardening
     */
    public function ajax_retry_queue_job() {
        try {
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dodo_queue_nonce')) {
                wp_send_json_error(array('message' => 'Güvenlik doğrulaması başarısız'));
                return;
            }
            
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => 'Insufficient permissions'));
                return;
            }
            
            $job_id = isset($_POST['job_id']) ? intval($_POST['job_id']) : 0;
            
            if (!$job_id) {
                wp_send_json_error(array('message' => 'Job ID required'));
                return;
            }
            
            $queue = new DODO_Queue_Manager();
            $result = $queue->retry_failed_job($job_id);
            
            if ($result) {
                wp_send_json_success(array('message' => 'Job queued for retry'));
            } else {
                wp_send_json_error(array('message' => 'Failed to retry job'));
            }
            
        } catch (Throwable $e) {
            error_log('[DODO AJAX ERROR] ajax_retry_queue_job: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'İşlem sırasında hata oluştu', 'error' => WP_DEBUG ? $e->getMessage() : null));
        }
    }
    
    /**
     * Cancel Queue Job (AJAX - Sprint 4)
     * HARDENED: Final Completion Phase - AJAX Hardening
     */
    public function ajax_cancel_queue_job() {
        try {
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dodo_queue_nonce')) {
                wp_send_json_error(array('message' => 'Güvenlik doğrulaması başarısız'));
                return;
            }
            
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => 'Insufficient permissions'));
                return;
            }
            
            $job_id = isset($_POST['job_id']) ? intval($_POST['job_id']) : 0;
            
            if (!$job_id) {
                wp_send_json_error(array('message' => 'Job ID required'));
                return;
            }
            
            $queue = new DODO_Queue_Manager();
            $result = $queue->update_job_status($job_id, DODO_Queue_Manager::STATUS_FAILED, null, 'Cancelled by user');
            
            if ($result) {
                wp_send_json_success(array('message' => 'Job cancelled'));
            } else {
                wp_send_json_error(array('message' => 'Failed to cancel job'));
            }
            
        } catch (Throwable $e) {
            error_log('[DODO AJAX ERROR] ajax_cancel_queue_job: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'İşlem sırasında hata oluştu', 'error' => WP_DEBUG ? $e->getMessage() : null));
        }
    }
    
    /**
     * Process Queue Manually (AJAX - NEW v2.3.0)
     * HARDENED: Final Completion Phase - AJAX Hardening
     */
    public function ajax_process_queue_manually() {
        try {
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dodo_queue_nonce')) {
                wp_send_json_error(array('message' => 'Güvenlik doğrulaması başarısız'));
                return;
            }
            
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => 'Insufficient permissions'));
                return;
            }
            
            $queue = new DODO_Queue_Manager();
            $result = $queue->process_next_job();
            
            if ($result) {
                wp_send_json_success(array('message' => 'Job processed successfully'));
            } else {
                wp_send_json_error(array('message' => 'No pending jobs or processing failed'));
            }
            
        } catch (Throwable $e) {
            error_log('[DODO AJAX ERROR] ajax_process_queue_manually: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'İşlem sırasında hata oluştu', 'error' => WP_DEBUG ? $e->getMessage() : null));
        }
    }
    
    /**
     * Get Queue Job Detail (AJAX - Sprint 4)
     * HARDENED: Final Completion Phase - AJAX Hardening
     */
    public function ajax_get_queue_job_detail() {
        try {
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dodo_queue_nonce')) {
                wp_send_json_error(array('message' => 'Güvenlik doğrulaması başarısız'));
                return;
            }
            
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => 'Insufficient permissions'));
                return;
            }
            
            $job_id = isset($_POST['job_id']) ? intval($_POST['job_id']) : 0;
            
            if (!$job_id) {
                wp_send_json_error(array('message' => 'Job ID required'));
                return;
            }
            
            $queue = new DODO_Job_Queue();
            $job = $queue->get_job($job_id);
            
            if ($job) {
                wp_send_json_success(array('job' => $job));
            } else {
                wp_send_json_error(array('message' => 'Job not found'));
            }
            
        } catch (Throwable $e) {
            error_log('[DODO AJAX ERROR] ajax_get_queue_job_detail: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'İşlem sırasında hata oluştu', 'error' => WP_DEBUG ? $e->getMessage() : null));
        }
    }
    
    /**
     * Clear Completed Jobs (AJAX - Sprint 4)
     * HARDENED: Final Completion Phase - AJAX Hardening
     */
    public function ajax_clear_completed_jobs() {
        try {
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dodo_queue_nonce')) {
                wp_send_json_error(array('message' => 'Güvenlik doğrulaması başarısız'));
                return;
            }
            
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => 'Insufficient permissions'));
                return;
            }
            
            $queue = new DODO_Job_Queue();
            $count = $queue->cleanup_old_jobs(0);
            
            wp_send_json_success(array('message' => sprintf('%d job cleaned', $count)));
            
        } catch (Throwable $e) {
            error_log('[DODO AJAX ERROR] ajax_clear_completed_jobs: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'İşlem sırasında hata oluştu', 'error' => WP_DEBUG ? $e->getMessage() : null));
        }
    }
    
    /**
     * Get Posts for Bulk Operations (AJAX - Sprint 4)
     * UPDATED: Now uses DODO_Bulk_Processor (v2.3.0)
     * HARDENED: Final Completion Phase - AJAX Hardening
     */
    public function ajax_get_posts_for_bulk() {
        try {
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dodo_bulk_nonce')) {
                wp_send_json_error(array('message' => 'Güvenlik doğrulaması başarısız'));
                return;
            }
            
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => 'Insufficient permissions'));
                return;
            }
            
            $filters = array(
                'status' => isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'publish',
                'category' => isset($_POST['category']) ? absint($_POST['category']) : 0,
                'search' => isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '',
                'date_from' => isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '',
                'date_to' => isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '',
                'limit' => isset($_POST['limit']) ? absint($_POST['limit']) : 50,
                'offset' => isset($_POST['offset']) ? absint($_POST['offset']) : 0,
            );
            
            $bulk_processor = new DODO_Bulk_Processor();
            $result = $bulk_processor->get_posts_for_bulk($filters);
            
            wp_send_json_success($result);
            
        } catch (Throwable $e) {
            error_log('[DODO AJAX ERROR] ajax_get_posts_for_bulk: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'İşlem sırasında hata oluştu', 'error' => WP_DEBUG ? $e->getMessage() : null));
        }
    }
    
    /**
     * Process Bulk Batch (AJAX - Sprint 4)
     * UPDATED: Now uses DODO_Bulk_Processor (v2.3.0)
     * HARDENED: Final Completion Phase - AJAX Hardening
     */
    public function ajax_process_bulk_batch() {
        try {
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dodo_bulk_nonce')) {
                wp_send_json_error(array('message' => 'Güvenlik doğrulaması başarısız'));
                return;
            }
            
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => 'Insufficient permissions'));
                return;
            }
            
            $operation_type = isset($_POST['operation_type']) ? sanitize_text_field($_POST['operation_type']) : '';
            $post_ids = isset($_POST['post_ids']) ? array_map('intval', $_POST['post_ids']) : array();
            $improvement_type = isset($_POST['improvement_type']) ? sanitize_text_field($_POST['improvement_type']) : 'general';
            
            if (empty($post_ids)) {
                wp_send_json_error(array('message' => 'No posts selected'));
                return;
            }
            
            $bulk_processor = new DODO_Bulk_Processor();
            
            switch ($operation_type) {
                case 'analyze':
                    $result = $bulk_processor->bulk_analyze_content($post_ids);
                    break;
                    
                case 'improve':
                    $result = $bulk_processor->bulk_improve_content($post_ids, $improvement_type);
                    break;
                    
                case 'meta':
                    $result = $bulk_processor->bulk_generate_meta($post_ids);
                    break;
                    
                case 'links':
                    $result = $bulk_processor->bulk_suggest_internal_links($post_ids);
                    break;
                    
                case 'geo':
                    $result = $bulk_processor->bulk_geo_analysis($post_ids);
                    break;
                    
                default:
                    wp_send_json_error(array('message' => 'Invalid operation type'));
                    return;
            }
            
            if (is_wp_error($result)) {
                wp_send_json_error(array('message' => $result->get_error_message()));
            } else {
                wp_send_json_success($result);
            }
            
        } catch (Throwable $e) {
            error_log('[DODO AJAX ERROR] ajax_process_bulk_batch: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'İşlem sırasında hata oluştu', 'error' => WP_DEBUG ? $e->getMessage() : null));
        }
    }
    
    /**
     * Process Bulk GEO
     */
    private function process_bulk_geo($post, $extra_data) {
        $target_country = $extra_data['target_country'] ?? 'TR';
        
        $geo_analyzer = new DODO_GEO_Analyzer();
        $analysis = $geo_analyzer->analyze($post->post_content, '', $target_country);
        
        if ($analysis) {
            update_post_meta($post->ID, 'dodo_geo_score', $analysis['geo_score']);
            return array('success' => true);
        }
        
        return array('success' => false, 'error' => 'GEO analysis failed');
    }
    
    /**
     * Process Bulk Links
     */
    private function process_bulk_links($post) {
        $internal_links = new DODO_Internal_Links();
        $suggestions = $internal_links->get_link_suggestions($post->ID);
        
        if ($suggestions) {
            update_post_meta($post->ID, 'dodo_link_suggestions', $suggestions);
            return array('success' => true);
        }
        
        return array('success' => false, 'error' => 'Link analysis failed');
    }
    
    /**
     * Get SVG icon for menu (NEW - Sprint 4.6 Phase 2)
     * Lucide-inspired minimal icons
     */
    private function get_menu_icon_svg($icon_name) {
        $icons = array(
            'sparkles' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v18m9-9H3m15.364-6.364L5.636 18.364m12.728 0L5.636 5.636"/></svg>',
            'edit' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>',
            'key' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="7.5" cy="15.5" r="5.5"/><path d="m21 2-9.6 9.6"/><path d="m15.5 7.5 3 3L22 7l-3-3"/></svg>',
            'calendar' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
            'wand' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 4V2"/><path d="M15 16v-2"/><path d="M8 9h2"/><path d="M20 9h2"/><path d="M17.8 11.8 19 13"/><path d="M15 9h0"/><path d="M17.8 6.2 19 5"/><path d="m3 21 9-9"/><path d="M12.2 6.2 11 5"/></svg>',
            'network' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="2"/><path d="M16.24 7.76a6 6 0 0 1 0 8.49m-8.48-.01a6 6 0 0 1 0-8.49m11.31-2.82a10 10 0 0 1 0 14.14m-14.14 0a10 10 0 0 1 0-14.14"/></svg>',
            'chart' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/></svg>',
            'history' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/></svg>',
            'search' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>',
            'activity' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>',
            'database' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>',
            'settings' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>',
            'wizard' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m8 3 4 8 5-5 5 15H2L8 3z"/></svg>',
            'layers' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>',
            'zap' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>',
            'bug' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="8" height="14" x="8" y="6" rx="4"/><path d="m19 7-3 2"/><path d="m5 7 3 2"/><path d="m19 19-3-2"/><path d="m5 19 3-2"/><path d="M20 13h-4"/><path d="M4 13h4"/><path d="m10 4 1 2"/><path d="m14 4-1 2"/></svg>',
        );
        
        if (isset($icons[$icon_name])) {
            return 'data:image/svg+xml;base64,' . base64_encode($icons[$icon_name]);
        }
        
        // Fallback to default dashicon
        return 'dashicons-admin-generic';
    }
    
    /**
     * Add sidebar footer with user profile (NEW - Sprint 4.6 Phase 2)
     */
    public function add_sidebar_footer() {
        // Only on DODO pages
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'dodo') === false) {
            return;
        }
        
        $current_user = wp_get_current_user();
        $user_name = $current_user->display_name;
        $user_role = translate_user_role(wp_roles()->roles[$current_user->roles[0]]['name']);
        $user_initials = strtoupper(substr($user_name, 0, 1));
        ?>
        <div class="dodo-sidebar-footer">
            <div class="dodo-sidebar-user">
                <div class="dodo-sidebar-avatar"><?php echo esc_html($user_initials); ?></div>
                <div class="dodo-sidebar-user-info">
                    <div class="dodo-sidebar-user-name"><?php echo esc_html($user_name); ?></div>
                    <div class="dodo-sidebar-user-role"><?php echo esc_html($user_role); ?></div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * AJAX: Analyze GEO score (Sprint 5 - Phase 1)
     * HARDENED: Final Completion Phase - AJAX Hardening
     */
    public function ajax_analyze_geo() {
        try {
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dodo_improver_nonce')) {
                wp_send_json_error(array('message' => 'Güvenlik doğrulaması başarısız'));
                return;
            }
            
            if (!current_user_can('edit_posts')) {
                wp_send_json_error(array('message' => 'Yetkiniz yok'));
                return;
            }
            
            $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
            
            if (!$post_id) {
                wp_send_json_error(array('message' => 'Geçersiz yazı ID'));
                return;
            }
            
            $geo_engine = new DODO_GEO_Engine();
            $result = $geo_engine->analyze_content($post_id);
            
            if (is_wp_error($result)) {
                wp_send_json_error(array('message' => $result->get_error_message()));
                return;
            }
            
            wp_send_json_success($result);
            
        } catch (Throwable $e) {
            error_log('[DODO AJAX ERROR] ajax_analyze_geo: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'İşlem sırasında hata oluştu', 'error' => WP_DEBUG ? $e->getMessage() : null));
        }
    }
    
    /**
     * AJAX: Get saved GEO score (Sprint 5 - Phase 1)
     * HARDENED: Final Completion Phase - AJAX Hardening
     */
    public function ajax_get_geo_score() {
        try {
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dodo_improver_nonce')) {
                wp_send_json_error(array('message' => 'Güvenlik doğrulaması başarısız'));
                return;
            }
            
            if (!current_user_can('edit_posts')) {
                wp_send_json_error(array('message' => 'Yetkiniz yok'));
                return;
            }
            
            $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
            
            if (!$post_id) {
                wp_send_json_error(array('message' => 'Geçersiz yazı ID'));
                return;
            }
            
            $geo_engine = new DODO_GEO_Engine();
            $result = $geo_engine->get_analysis($post_id);
            
            if ($result) {
                wp_send_json_success($result);
            } else {
                wp_send_json_error(array('message' => 'GEO analizi bulunamadı'));
            }
            
        } catch (Throwable $e) {
            error_log('[DODO AJAX ERROR] ajax_get_geo_score: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'İşlem sırasında hata oluştu', 'error' => WP_DEBUG ? $e->getMessage() : null));
        }
    }
    
    /**
     * AJAX: Analyze site-wide intelligence (Sprint 5 - Phase 3)
     * HARDENED: Final Completion Phase - AJAX Hardening
     */
    public function ajax_analyze_site_intelligence() {
        try {
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dodo_clusters_nonce')) {
                wp_send_json_error(array('message' => 'Güvenlik doğrulaması başarısız'));
                return;
            }
            
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => 'Yetkiniz yok'));
                return;
            }
            
            $intelligence = new DODO_Content_Intelligence();
            $result = $intelligence->analyze_site();
            
            wp_send_json_success($result);
            
        } catch (Throwable $e) {
            error_log('[DODO AJAX ERROR] ajax_analyze_site_intelligence: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'İşlem sırasında hata oluştu', 'error' => WP_DEBUG ? $e->getMessage() : null));
        }
    }
    
    /**
     * AJAX: Get link suggestions v2 (Sprint 5 - Phase 3)
     * HARDENED: Final Completion Phase - AJAX Hardening
     */
    public function ajax_get_link_suggestions_v2() {
        try {
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dodo_improver_nonce')) {
                wp_send_json_error(array('message' => 'Güvenlik doğrulaması başarısız'));
                return;
            }
            
            if (!current_user_can('edit_posts')) {
                wp_send_json_error(array('message' => 'Yetkiniz yok'));
                return;
            }
            
            $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
            
            if (!$post_id) {
                wp_send_json_error(array('message' => 'Geçersiz yazı ID'));
                return;
            }
            
            $intelligence = new DODO_Content_Intelligence();
            $suggestions = $intelligence->get_link_suggestions($post_id);
            
            wp_send_json_success(array('suggestions' => $suggestions));
            
        } catch (Throwable $e) {
            error_log('[DODO AJAX ERROR] ajax_get_link_suggestions_v2: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'İşlem sırasında hata oluştu', 'error' => WP_DEBUG ? $e->getMessage() : null));
        }
    }
    
    /**
     * AJAX: Detect cannibalization (Sprint 5 - Phase 3)
     * HARDENED: Final Completion Phase - AJAX Hardening
     */
    public function ajax_detect_cannibalization() {
        try {
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dodo_clusters_nonce')) {
                wp_send_json_error(array('message' => 'Güvenlik doğrulaması başarısız'));
                return;
            }
            
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => 'Yetkiniz yok'));
                return;
            }
            
            $intelligence = new DODO_Content_Intelligence();
            $result = $intelligence->detect_cannibalization();
            
            wp_send_json_success($result);
            
        } catch (Throwable $e) {
            error_log('[DODO AJAX ERROR] ajax_detect_cannibalization: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'İşlem sırasında hata oluştu', 'error' => WP_DEBUG ? $e->getMessage() : null));
        }
    }
    
    /**
     * AJAX: Analyze humanization (Sprint 5 - Phase 4)
     * HARDENED: Final Completion Phase - AJAX Hardening
     */
    public function ajax_analyze_humanization() {
        try {
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dodo_improver_nonce')) {
                wp_send_json_error(array('message' => 'Güvenlik doğrulaması başarısız'));
                return;
            }
            
            if (!current_user_can('edit_posts')) {
                wp_send_json_error(array('message' => 'Yetkiniz yok'));
                return;
            }
            
            $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
            
            if (!$post_id) {
                wp_send_json_error(array('message' => 'Geçersiz yazı ID'));
                return;
            }
            
            $humanizer = new DODO_Humanizer();
            $result = $humanizer->analyze_content($post_id);
            
            if (is_wp_error($result)) {
                wp_send_json_error(array('message' => $result->get_error_message()));
                return;
            }
            
            wp_send_json_success($result);
            
        } catch (Throwable $e) {
            error_log('[DODO AJAX ERROR] ajax_analyze_humanization: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'İşlem sırasında hata oluştu', 'error' => WP_DEBUG ? $e->getMessage() : null));
        }
    }
    
    /**
     * AJAX: Humanize content (Sprint 5 - Phase 4)
     * HARDENED: Final Completion Phase - AJAX Hardening
     */
    public function ajax_humanize_content() {
        try {
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dodo_improver_nonce')) {
                wp_send_json_error(array('message' => 'Güvenlik doğrulaması başarısız'));
                return;
            }
            
            if (!current_user_can('edit_posts')) {
                wp_send_json_error(array('message' => 'Yetkiniz yok'));
                return;
            }
            
            $content = isset($_POST['content']) ? wp_kses_post($_POST['content']) : '';
            $preset = isset($_POST['preset']) ? sanitize_text_field($_POST['preset']) : 'human-like';
            
            if (empty($content)) {
                wp_send_json_error(array('message' => 'İçerik boş'));
                return;
            }
            
            $humanizer = new DODO_Humanizer();
            $humanized = $humanizer->humanize_content($content, $preset);
            
            wp_send_json_success(array('content' => $humanized));
            
        } catch (Throwable $e) {
            error_log('[DODO AJAX ERROR] ajax_humanize_content: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'İşlem sırasında hata oluştu', 'error' => WP_DEBUG ? $e->getMessage() : null));
        }
    }
    
    /**
     * AJAX: Get humanization score (Sprint 5 - Phase 4)
     * HARDENED: Final Completion Phase - AJAX Hardening
     */
    public function ajax_get_humanization_score() {
        try {
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dodo_improver_nonce')) {
                wp_send_json_error(array('message' => 'Güvenlik doğrulaması başarısız'));
                return;
            }
            
            if (!current_user_can('edit_posts')) {
                wp_send_json_error(array('message' => 'Yetkiniz yok'));
                return;
            }
            
            $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
            
            if (!$post_id) {
                wp_send_json_error(array('message' => 'Geçersiz yazı ID'));
                return;
            }
            
            $humanizer = new DODO_Humanizer();
            $result = $humanizer->get_analysis($post_id);
            
            if ($result) {
                wp_send_json_success($result);
            } else {
                wp_send_json_error(array('message' => 'Humanization analizi bulunamadı'));
            }
            
        } catch (Throwable $e) {
            error_log('[DODO AJAX ERROR] ajax_get_humanization_score: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'İşlem sırasında hata oluştu', 'error' => WP_DEBUG ? $e->getMessage() : null));
        }
    }
    
    /**
     * AJAX: Run database migration (Sprint 5 - Task 6)
     * HARDENED: Final Completion Phase - AJAX Hardening
     */
    public function ajax_run_migration() {
        try {
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dodo_health_nonce')) {
                wp_send_json_error(array('message' => 'Güvenlik doğrulaması başarısız'));
                return;
            }
            
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => 'Yetkiniz yok'));
                return;
            }
            
            $result = DODO_Database::migrate();
            
            if ($result) {
                wp_send_json_success(array('message' => 'Migration başarıyla tamamlandı'));
            } else {
                wp_send_json_error(array('message' => 'Migration başarısız'));
            }
            
        } catch (Throwable $e) {
            error_log('[DODO AJAX ERROR] ajax_run_migration: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'İşlem sırasında hata oluştu', 'error' => WP_DEBUG ? $e->getMessage() : null));
        }
    }
    
    /**
     * AJAX: Repair database tables (Sprint 5 - Task 6)
     * HARDENED: Final Completion Phase - AJAX Hardening
     */
    public function ajax_repair_tables() {
        try {
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dodo_health_nonce')) {
                wp_send_json_error(array('message' => 'Güvenlik doğrulaması başarısız'));
                return;
            }
            
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => 'Yetkiniz yok'));
                return;
            }
            
            $result = DODO_Database::auto_repair();
            
            if ($result['success']) {
                wp_send_json_success($result);
            } else {
                wp_send_json_error($result);
            }
            
        } catch (Throwable $e) {
            error_log('[DODO AJAX ERROR] ajax_repair_tables: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'İşlem sırasında hata oluştu', 'error' => WP_DEBUG ? $e->getMessage() : null));
        }
    }
    
    /**
     * AJAX: Run GEO analysis (Sprint 5 - Task 2)
     * HARDENED: Final Completion Phase - AJAX Hardening
     */
    public function ajax_run_geo_analysis() {
        try {
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dodo_improver_nonce')) {
                wp_send_json_error(array('message' => 'Güvenlik doğrulaması başarısız'));
                return;
            }
            
            if (!current_user_can('edit_posts')) {
                wp_send_json_error(array('message' => 'Yetkiniz yok'));
                return;
            }
            
            $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
            $focus_keyword = isset($_POST['focus_keyword']) ? sanitize_text_field($_POST['focus_keyword']) : '';
            
            if (!$post_id) {
                wp_send_json_error(array('message' => 'Geçersiz yazı ID'));
                return;
            }
            
            $geo_engine = new DODO_GEO_Engine();
            $result = $geo_engine->analyze_content($post_id, $focus_keyword);
            
            wp_send_json_success($result);
            
        } catch (Throwable $e) {
            error_log('[DODO AJAX ERROR] ajax_run_geo_analysis: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'İşlem sırasında hata oluştu', 'error' => WP_DEBUG ? $e->getMessage() : null));
        }
    }
    
    /**
     * AJAX: Run humanization analysis (Sprint 5 - Task 2)
     * HARDENED: Final Completion Phase - AJAX Hardening
     */
    public function ajax_run_humanization_analysis() {
        try {
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dodo_improver_nonce')) {
                wp_send_json_error(array('message' => 'Güvenlik doğrulaması başarısız'));
                return;
            }
            
            if (!current_user_can('edit_posts')) {
                wp_send_json_error(array('message' => 'Yetkiniz yok'));
                return;
            }
            
            $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
            
            if (!$post_id) {
                wp_send_json_error(array('message' => 'Geçersiz yazı ID'));
                return;
            }
            
            $humanizer = new DODO_Humanizer();
            $result = $humanizer->analyze_content($post_id);
            
            wp_send_json_success($result);
            
        } catch (Throwable $e) {
            error_log('[DODO AJAX ERROR] ajax_run_humanization_analysis: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'İşlem sırasında hata oluştu', 'error' => WP_DEBUG ? $e->getMessage() : null));
        }
    }
    
    /**
    /**
     * AJAX: Apply humanization (Sprint 5 - Phase 4)
     * HARDENED: Final Completion Phase - AJAX Hardening
     */
    public function ajax_apply_humanization() {
        try {
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dodo_improver_nonce')) {
                wp_send_json_error(array('message' => 'Güvenlik doğrulaması başarısız'));
                return;
            }
            
            if (!current_user_can('edit_posts')) {
                wp_send_json_error(array('message' => 'Yetkiniz yok'));
                return;
            }
            
            $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
            $preset = isset($_POST['preset']) ? sanitize_text_field($_POST['preset']) : 'moderate';
            
            if (!$post_id) {
                wp_send_json_error(array('message' => 'Geçersiz yazı ID'));
                return;
            }
            
            $post = get_post($post_id);
            if (!$post) {
                wp_send_json_error(array('message' => 'Yazı bulunamadı'));
                return;
            }
            
            $humanizer = new DODO_Humanizer();
            $humanized_content = $humanizer->humanize_content($post->post_content, $preset);
            
            // Update post
            wp_update_post(array(
                'ID' => $post_id,
                'post_content' => $humanized_content,
            ));
            
            wp_send_json_success(array('message' => 'İnsanileştirme uygulandı'));
            
        } catch (Throwable $e) {
            error_log('[DODO AJAX ERROR] ajax_apply_humanization: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'İşlem sırasında hata oluştu', 'error' => WP_DEBUG ? $e->getMessage() : null));
        }
    }
    
    /**
     * Learning Center sayfasını render et (Phase 5)
     */
    public function render_learning_center_page() {
        // Yetki kontrolü
        if (!current_user_can('manage_options')) {
            wp_die(__('Bu sayfaya erişim yetkiniz yok.', 'dodo-ai-seo'));
        }
        
        // View dosyasını include et
        include DODO_PLUGIN_DIR . 'admin/views/page-learning-center.php';
    }
    
    /**
     * Learning Control sayfasını render et (Phase 5 - Governance)
     */
    public function render_learning_control_page() {
        // Yetki kontrolü
        if (!current_user_can('manage_options')) {
            wp_die(__('Bu sayfaya erişim yetkiniz yok.', 'dodo-ai-seo'));
        }
        
        // View dosyasını include et
        include DODO_PLUGIN_DIR . 'admin/views/page-learning-control.php';
    }
    
    /**
     * SEO Impact Center sayfasını render et (Phase 5 - KPIs)
     */
    public function render_impact_center_page() {
        // Yetki kontrolü
        if (!current_user_can('manage_options')) {
            wp_die(__('Bu sayfaya erişim yetkiniz yok.', 'dodo-ai-seo'));
        }
        
        // View dosyasını include et
        include DODO_PLUGIN_DIR . 'admin/views/page-impact-center.php';
    }
    
    /**
     * Production Readiness sayfasını render et (Phase 5 - Governance)
     */
    public function render_production_readiness_page() {
        // Yetki kontrolü
        if (!current_user_can('manage_options')) {
            wp_die(__('Bu sayfaya erişim yetkiniz yok.', 'dodo-ai-seo'));
        }
        
        // View dosyasını include et
        include DODO_PLUGIN_DIR . 'admin/views/page-production-readiness.php';
    }
    
    /**
     * Debug Panel sayfasını render et (Phase 7 - Internal Debug Tooling)
     */
    public function render_debug_panel_page() {
        // Yetki kontrolü
        if (!current_user_can('manage_options')) {
            wp_die(__('Bu sayfaya erişim yetkiniz yok.', 'dodo-ai-seo'));
        }
        
        // View dosyasını include et
        include DODO_PLUGIN_DIR . 'admin/views/page-debug-panel.php';
    }
    
    /**
     * Live Validation sayfasını render et (FINAL PRODUCTION TRANSITION)
     */
    public function render_live_validation_page() {
        // Yetki kontrolü
        if (!current_user_can('manage_options')) {
            wp_die(__('Bu sayfaya erişim yetkiniz yok.', 'dodo-ai-seo'));
        }
        
        // View dosyasını include et
        include DODO_PLUGIN_DIR . 'admin/views/page-live-validation.php';
    }
    
    /**
     * AJAX: Get learning insights (Phase 5)
     * HARDENED: Final Completion Phase - AJAX Hardening
     */
    public function ajax_get_learning_insights() {
        try {
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dodo_learning_nonce')) {
                wp_send_json_error(array('message' => 'Güvenlik doğrulaması başarısız'));
                return;
            }
            
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => 'Yetkiniz yok'));
                return;
            }
            
            $feedback_engine = new DODO_Feedback_Engine();
            $strategy_evolution = new DODO_Strategy_Evolution();
            $niche_intelligence = new DODO_Niche_Intelligence();
            $validator = new DODO_Learning_Validator();
            
            $insights = array(
                'learning' => $feedback_engine->get_learning_insights(),
                'evolution' => $strategy_evolution->get_evolution_insights(),
                'niche' => $niche_intelligence->get_niche_insights(),
                'validation' => $validator->get_validation_insights(),
            );
            
            wp_send_json_success($insights);
            
        } catch (Throwable $e) {
            error_log('[DODO AJAX ERROR] ajax_get_learning_insights: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'İşlem sırasında hata oluştu', 'error' => WP_DEBUG ? $e->getMessage() : null));
        }
    }
    
    /**
     * AJAX: Reset learning (Phase 5)
     * HARDENED: Final Completion Phase - AJAX Hardening
     */
    public function ajax_reset_learning() {
        try {
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dodo_learning_nonce')) {
                wp_send_json_error(array('message' => 'Güvenlik doğrulaması başarısız'));
                return;
            }
            
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => 'Yetkiniz yok'));
                return;
            }
            
            $action_type = isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : null;
            
            $feedback_engine = new DODO_Feedback_Engine();
            $feedback_engine->reset_learning($action_type);
            
            $message = $action_type 
                ? sprintf('"%s" için öğrenme verileri sıfırlandı', $action_type)
                : 'Tüm öğrenme verileri sıfırlandı';
            
            wp_send_json_success(array('message' => $message));
            
        } catch (Throwable $e) {
            error_log('[DODO AJAX ERROR] ajax_reset_learning: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'İşlem sırasında hata oluştu', 'error' => WP_DEBUG ? $e->getMessage() : null));
        }
    }
    
    /**
     * AJAX: Rollback evolution (Phase 5)
     * HARDENED: Final Completion Phase - AJAX Hardening
     */
    public function ajax_rollback_evolution() {
        try {
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dodo_learning_nonce')) {
                wp_send_json_error(array('message' => 'Güvenlik doğrulaması başarısız'));
                return;
            }
            
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => 'Yetkiniz yok'));
                return;
            }
            
            $strategy_id = isset($_POST['strategy_id']) ? sanitize_text_field($_POST['strategy_id']) : '';
            
            if (empty($strategy_id)) {
                wp_send_json_error(array('message' => 'Geçersiz strateji ID'));
                return;
            }
            
            $strategy_evolution = new DODO_Strategy_Evolution();
            $success = $strategy_evolution->rollback_evolution($strategy_id);
            
            if ($success) {
                wp_send_json_success(array('message' => 'Strateji evrim geri alındı'));
            } else {
                wp_send_json_error(array('message' => 'Strateji bulunamadı'));
            }
            
        } catch (Throwable $e) {
            error_log('[DODO AJAX ERROR] ajax_rollback_evolution: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'İşlem sırasında hata oluştu', 'error' => WP_DEBUG ? $e->getMessage() : null));
        }
    }
    
    /**
     * Record human feedback for learning (Phase 5)
     * 
     * @param int $post_id Post ID
     * @param string $recommendation_type Recommendation type
     * @param string $action User action (accepted, rejected, modified, ignored)
     * @param array $details Additional details
     */
    private function record_human_feedback($post_id, $recommendation_type, $action, $details = []) {
        try {
            if (!class_exists('DODO_Human_Feedback')) {
                return;
            }
            
            $feedback = new DODO_Human_Feedback();
            $feedback->record_feedback($post_id, $recommendation_type, $action, $details);
            
            // Also learn from feedback
            if (class_exists('DODO_Feedback_Engine')) {
                $engine = new DODO_Feedback_Engine();
                $engine->learn_from_user_feedback($recommendation_type, $action, $details);
            }
            
            error_log("[DODO Learning] Human feedback recorded - Type: {$recommendation_type}, Action: {$action}");
            
        } catch (Exception $e) {
            error_log('[DODO Learning] Failed to record human feedback: ' . $e->getMessage());
        }
    }
    
    /**
     * AJAX: Set learning mode (Phase 5 - Governance)
     * HARDENED: Final Completion Phase - AJAX Hardening
     */
    public function ajax_set_learning_mode() {
        try {
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dodo_learning_control')) {
                wp_send_json_error(array('message' => 'Güvenlik doğrulaması başarısız'));
                return;
            }
            
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => 'Yetkiniz yok'));
                return;
            }
            
            $mode = isset($_POST['mode']) ? sanitize_text_field($_POST['mode']) : '';
            
            $controller = new DODO_Learning_Controller();
            $success = $controller->set_mode($mode);
            
            if ($success) {
                wp_send_json_success(array('message' => 'Learning mode updated'));
            } else {
                wp_send_json_error(array('message' => 'Invalid mode'));
            }
            
        } catch (Throwable $e) {
            error_log('[DODO AJAX ERROR] ajax_set_learning_mode: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'İşlem sırasında hata oluştu', 'error' => WP_DEBUG ? $e->getMessage() : null));
        }
    }
    
    /**
     * AJAX: Set environment (Phase 5 - Governance)
     * HARDENED: Final Completion Phase - AJAX Hardening
     */
    public function ajax_set_environment() {
        try {
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dodo_learning_control')) {
                wp_send_json_error(array('message' => 'Güvenlik doğrulaması başarısız'));
                return;
            }
            
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => 'Yetkiniz yok'));
                return;
            }
            
            $env = isset($_POST['environment']) ? sanitize_text_field($_POST['environment']) : '';
            
            $controller = new DODO_Learning_Controller();
            $success = $controller->set_environment($env);
            
            if ($success) {
                wp_send_json_success(array('message' => 'Environment updated'));
            } else {
                wp_send_json_error(array('message' => 'Invalid environment'));
            }
            
        } catch (Throwable $e) {
            error_log('[DODO AJAX ERROR] ajax_set_environment: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'İşlem sırasında hata oluştu', 'error' => WP_DEBUG ? $e->getMessage() : null));
        }
    }
    
    /**
     * AJAX: Set confidence threshold (Phase 5 - Governance)
     * HARDENED: Final Completion Phase - AJAX Hardening
     */
    public function ajax_set_confidence_threshold() {
        try {
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dodo_learning_control')) {
                wp_send_json_error(array('message' => 'Güvenlik doğrulaması başarısız'));
                return;
            }
            
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => 'Yetkiniz yok'));
                return;
            }
            
            $threshold = isset($_POST['threshold']) ? intval($_POST['threshold']) : 60;
            
            $controller = new DODO_Learning_Controller();
            $controller->set_confidence_threshold($threshold);
            
            wp_send_json_success(array('message' => 'Confidence threshold updated'));
            
        } catch (Throwable $e) {
            error_log('[DODO AJAX ERROR] ajax_set_confidence_threshold: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'İşlem sırasında hata oluştu', 'error' => WP_DEBUG ? $e->getMessage() : null));
        }
    }
    
    /**
     * AJAX: Set safety settings (Phase 5 - Governance)
     * HARDENED: Final Completion Phase - AJAX Hardening
     */
    public function ajax_set_safety_settings() {
        try {
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dodo_learning_control')) {
                wp_send_json_error(array('message' => 'Güvenlik doğrulaması başarısız'));
                return;
            }
            
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => 'Yetkiniz yok'));
                return;
            }
            
            $adaptive = isset($_POST['adaptive_strategy']) ? (bool) $_POST['adaptive_strategy'] : true;
            $noisy = isset($_POST['noisy_filter']) ? (bool) $_POST['noisy_filter'] : true;
            
            $controller = new DODO_Learning_Controller();
            $controller->set_adaptive_strategy($adaptive);
            $controller->set_noisy_filter($noisy);
            
            wp_send_json_success(array('message' => 'Safety settings updated'));
            
        } catch (Throwable $e) {
            error_log('[DODO AJAX ERROR] ajax_set_safety_settings: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'İşlem sırasında hata oluştu', 'error' => WP_DEBUG ? $e->getMessage() : null));
        }
    }
    
    /**
     * AJAX: Emergency freeze (Phase 5 - Governance)
     * HARDENED: Final Completion Phase - AJAX Hardening
     */
    public function ajax_emergency_freeze() {
        try {
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dodo_learning_control')) {
                wp_send_json_error(array('message' => 'Güvenlik doğrulaması başarısız'));
                return;
            }
            
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => 'Yetkiniz yok'));
                return;
            }
            
            $controller = new DODO_Learning_Controller();
            $controller->emergency_freeze();
            
            wp_send_json_success(array('message' => 'Emergency freeze activated'));
            
        } catch (Throwable $e) {
            error_log('[DODO AJAX ERROR] ajax_emergency_freeze: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'İşlem sırasında hata oluştu', 'error' => WP_DEBUG ? $e->getMessage() : null));
        }
    }
    
    /**
     * AJAX: Emergency unfreeze (Phase 5 - Governance)
     * HARDENED: Final Completion Phase - AJAX Hardening
     */
    public function ajax_emergency_unfreeze() {
        try {
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dodo_learning_control')) {
                wp_send_json_error(array('message' => 'Güvenlik doğrulaması başarısız'));
                return;
            }
            
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => 'Yetkiniz yok'));
                return;
            }
            
            $controller = new DODO_Learning_Controller();
            $controller->unfreeze();
            
            wp_send_json_success(array('message' => 'System unfrozen'));
            
        } catch (Throwable $e) {
            error_log('[DODO AJAX ERROR] ajax_emergency_unfreeze: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'İşlem sırasında hata oluştu', 'error' => WP_DEBUG ? $e->getMessage() : null));
        }
    }
    
    /**
     * AJAX: Repair database tables (Phase 5 - Production Readiness)
     * Creates missing database tables
     * HARDENED: Final Completion Phase - AJAX Hardening
     */
    public function ajax_repair_database_tables() {
        try {
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dodo_repair_tables')) {
                wp_send_json_error(array('message' => 'Güvenlik doğrulaması başarısız'));
                return;
            }
            
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => 'Yetkiniz yok'));
                return;
            }
            
            global $wpdb;
            $created = [];
            $errors = [];
        
        // List of tables to create with their SQL
        $tables = array(
            'dodo_queue_jobs' => "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}dodo_queue_jobs (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                job_type varchar(50) NOT NULL,
                payload longtext NOT NULL,
                status varchar(20) NOT NULL DEFAULT 'pending',
                priority int(11) NOT NULL DEFAULT 10,
                attempts int(11) NOT NULL DEFAULT 0,
                max_attempts int(11) NOT NULL DEFAULT 3,
                error_message text,
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                scheduled_at datetime DEFAULT NULL,
                PRIMARY KEY (id),
                KEY status (status),
                KEY scheduled_at (scheduled_at),
                KEY priority (priority)
            ) {$wpdb->get_charset_collate()};",
            
            'dodo_telemetry' => "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}dodo_telemetry (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                operation_type varchar(100) NOT NULL,
                model varchar(50) NOT NULL,
                prompt_tokens int(11) NOT NULL DEFAULT 0,
                completion_tokens int(11) NOT NULL DEFAULT 0,
                total_tokens int(11) NOT NULL DEFAULT 0,
                estimated_cost decimal(10,6) NOT NULL DEFAULT 0,
                cache_hit tinyint(1) NOT NULL DEFAULT 0,
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY operation_type (operation_type),
                KEY created_at (created_at),
                KEY cache_hit (cache_hit)
            ) {$wpdb->get_charset_collate()};",
            
            'dodo_strategy_snapshots' => "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}dodo_strategy_snapshots (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                snapshot_type varchar(50) NOT NULL,
                strategy_data longtext NOT NULL,
                confidence_score decimal(5,2) NOT NULL DEFAULT 0,
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY snapshot_type (snapshot_type),
                KEY created_at (created_at)
            ) {$wpdb->get_charset_collate()};",
            
            'dodo_learning_events' => "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}dodo_learning_events (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                event_type varchar(50) NOT NULL,
                event_data longtext NOT NULL,
                impact_score decimal(5,2) DEFAULT NULL,
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY event_type (event_type),
                KEY created_at (created_at)
            ) {$wpdb->get_charset_collate()};",
            
            'dodo_semantic_cache' => "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}dodo_semantic_cache (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                cache_key varchar(255) NOT NULL,
                embedding_data longtext NOT NULL,
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY cache_key (cache_key)
            ) {$wpdb->get_charset_collate()};"
        );
        
        // Create each table
        foreach ($tables as $table_name => $sql) {
            $result = $wpdb->query($sql);
            
            if ($result === false) {
                $errors[] = "Tablo oluşturulamadı: {$table_name} - " . $wpdb->last_error;
            } else {
                $created[] = $table_name;
            }
        }
        
        if (!empty($errors)) {
            wp_send_json_error(array(
                'message' => implode("\n", $errors),
                'created' => $created
            ));
            return;
        }
        
        wp_send_json_success(array(
            'message' => count($created) . ' tablo başarıyla oluşturuldu: ' . implode(', ', $created),
            'created' => $created
        ));
        
        } catch (Throwable $e) {
            error_log('[DODO AJAX ERROR] ajax_repair_database_tables: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'İşlem sırasında hata oluştu', 'error' => WP_DEBUG ? $e->getMessage() : null));
        }
    }
    
    /**
     * AJAX: Run migrations (Phase 5.5 - Production Infrastructure)
     * Runs all pending database migrations
     * HARDENED: Final Completion Phase - AJAX Hardening
     */
    public function ajax_run_migrations() {
        try {
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dodo_migrations')) {
                wp_send_json_error(array('message' => 'Güvenlik doğrulaması başarısız'));
                return;
            }
            
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => 'Yetkiniz yok'));
                return;
            }
            
            require_once DODO_PLUGIN_DIR . 'includes/class-dodo-migrations.php';
            
            $current_version = DODO_Migrations::get_current_version();
            $target_version = DODO_Migrations::SCHEMA_VERSION;
            
            if ($current_version >= $target_version) {
                wp_send_json_success(array(
                    'message' => "Veritabanı zaten güncel (v{$current_version})",
                    'current_version' => $current_version,
                    'target_version' => $target_version
                ));
                return;
            }
            
            $result = DODO_Migrations::run_migrations();
            
            if ($result) {
                $new_version = DODO_Migrations::get_current_version();
                wp_send_json_success(array(
                    'message' => "Migration başarılı: v{$current_version} → v{$new_version}",
                    'current_version' => $new_version,
                    'target_version' => $target_version
                ));
            } else {
                wp_send_json_error(array(
                    'message' => 'Migration sırasında hata oluştu',
                    'current_version' => DODO_Migrations::get_current_version()
                ));
            }
            
        } catch (Throwable $e) {
            error_log('[DODO AJAX ERROR] ajax_run_migrations: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'İşlem sırasında hata oluştu', 'error' => WP_DEBUG ? $e->getMessage() : null));
        }
    }
}

