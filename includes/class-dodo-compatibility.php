<?php
/**
 * WordPress Ecosystem Compatibility Checker
 * 
 * @package DODO_AI_SEO
 * @since 2.0.0 (Sprint 4 - Task 1)
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Compatibility {
    
    /**
     * Check all compatibility requirements
     * 
     * @return array Compatibility status
     */
    public static function check_all() {
        return array(
            'php' => self::check_php(),
            'wordpress' => self::check_wordpress(),
            'rank_math' => self::check_rank_math(),
            'woocommerce' => self::check_woocommerce(),
            'elementor' => self::check_elementor(),
            'gutenberg' => self::check_gutenberg(),
            'classic_editor' => self::check_classic_editor(),
            'litespeed_cache' => self::check_litespeed_cache(),
            'wp_cron' => self::check_wp_cron(),
            'multisite' => self::check_multisite(),
        );
    }
    
    /**
     * Check PHP compatibility
     */
    public static function check_php() {
        $version = PHP_VERSION;
        $required = '8.0';
        $recommended = '8.3';
        
        $status = version_compare($version, $required, '>=') ? 'compatible' : 'incompatible';
        $level = version_compare($version, $recommended, '>=') ? 'optimal' : 'warning';
        
        return array(
            'status' => $status,
            'level' => $level,
            'version' => $version,
            'required' => $required,
            'recommended' => $recommended,
            'message' => $status === 'compatible' 
                ? sprintf('PHP %s (Gerekli: %s+)', $version, $required)
                : sprintf('PHP %s desteklenmiyor. En az %s gerekli.', $version, $required),
        );
    }
    
    /**
     * Check WordPress compatibility
     */
    public static function check_wordpress() {
        global $wp_version;
        $required = '5.8';
        $recommended = '6.4';
        
        $status = version_compare($wp_version, $required, '>=') ? 'compatible' : 'incompatible';
        $level = version_compare($wp_version, $recommended, '>=') ? 'optimal' : 'warning';
        
        return array(
            'status' => $status,
            'level' => $level,
            'version' => $wp_version,
            'required' => $required,
            'recommended' => $recommended,
            'message' => sprintf('WordPress %s', $wp_version),
        );
    }
    
    /**
     * Check Rank Math SEO
     */
    public static function check_rank_math() {
        $active = class_exists('RankMath');
        
        return array(
            'status' => $active ? 'active' : 'inactive',
            'level' => $active ? 'optimal' : 'info',
            'required' => false,
            'message' => $active 
                ? 'Rank Math SEO aktif - SEO meta verileri otomatik doldurulacak'
                : 'Rank Math SEO yüklü değil - SEO özellikleri sınırlı olacak',
        );
    }
    
    /**
     * Check WooCommerce
     */
    public static function check_woocommerce() {
        $active = class_exists('WooCommerce');
        
        return array(
            'status' => $active ? 'active' : 'inactive',
            'level' => $active ? 'optimal' : 'info',
            'required' => false,
            'message' => $active 
                ? 'WooCommerce aktif - Ürün iç linkleri kullanılabilir'
                : 'WooCommerce yüklü değil - Ürün linkleri devre dışı',
        );
    }
    
    /**
     * Check Elementor
     */
    public static function check_elementor() {
        $active = did_action('elementor/loaded');
        
        return array(
            'status' => $active ? 'active' : 'inactive',
            'level' => 'info',
            'required' => false,
            'message' => $active 
                ? 'Elementor aktif - Sayfa builder uyumlu'
                : 'Elementor yüklü değil',
        );
    }
    
    /**
     * Check Gutenberg
     */
    public static function check_gutenberg() {
        $active = function_exists('register_block_type');
        
        return array(
            'status' => $active ? 'active' : 'inactive',
            'level' => $active ? 'optimal' : 'warning',
            'required' => false,
            'message' => $active 
                ? 'Gutenberg aktif - Block editor destekleniyor'
                : 'Gutenberg devre dışı',
        );
    }
    
    /**
     * Check Classic Editor
     */
    public static function check_classic_editor() {
        $active = class_exists('Classic_Editor');
        
        return array(
            'status' => $active ? 'active' : 'inactive',
            'level' => 'info',
            'required' => false,
            'message' => $active 
                ? 'Classic Editor aktif'
                : 'Classic Editor yüklü değil',
        );
    }
    
    /**
     * Check LiteSpeed Cache
     */
    public static function check_litespeed_cache() {
        $active = class_exists('LiteSpeed_Cache');
        
        return array(
            'status' => $active ? 'active' : 'inactive',
            'level' => 'info',
            'required' => false,
            'message' => $active 
                ? 'LiteSpeed Cache aktif - Cache uyumluluğu sağlandı'
                : 'LiteSpeed Cache yüklü değil',
        );
    }
    
    /**
     * Check WP Cron
     */
    public static function check_wp_cron() {
        $disabled = defined('DISABLE_WP_CRON') && DISABLE_WP_CRON;
        
        return array(
            'status' => $disabled ? 'disabled' : 'enabled',
            'level' => $disabled ? 'warning' : 'optimal',
            'required' => true,
            'message' => $disabled 
                ? 'WP Cron devre dışı - Zamanlanmış işler çalışmayabilir'
                : 'WP Cron aktif - Zamanlanmış işler çalışıyor',
        );
    }
    
    /**
     * Check Multisite
     */
    public static function check_multisite() {
        $is_multisite = is_multisite();
        
        return array(
            'status' => $is_multisite ? 'multisite' : 'single',
            'level' => $is_multisite ? 'warning' : 'optimal',
            'required' => false,
            'message' => $is_multisite 
                ? 'Multisite ortamı - Bazı özellikler sınırlı olabilir'
                : 'Tek site ortamı',
        );
    }
    
    /**
     * Get compatibility summary
     */
    public static function get_summary() {
        $checks = self::check_all();
        
        $critical = 0;
        $warnings = 0;
        $optimal = 0;
        
        foreach ($checks as $check) {
            if ($check['level'] === 'incompatible' || ($check['required'] && $check['status'] === 'disabled')) {
                $critical++;
            } elseif ($check['level'] === 'warning') {
                $warnings++;
            } elseif ($check['level'] === 'optimal') {
                $optimal++;
            }
        }
        
        return array(
            'critical' => $critical,
            'warnings' => $warnings,
            'optimal' => $optimal,
            'total' => count($checks),
            'status' => $critical > 0 ? 'critical' : ($warnings > 0 ? 'warning' : 'healthy'),
        );
    }
}
