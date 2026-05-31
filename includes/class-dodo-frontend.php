<?php
/**
 * Frontend Sınıfı
 * 
 * Frontend'de DODO AI özelliklerini yönetir
 *
 * @package DODO_AI_SEO
 * @since 1.0.0
 */

// Doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

class DODO_Frontend {
    
    /**
     * Constructor
     */
    public function __construct() {
        // JSON-LD schema'yı wp_head'e bas
        add_action('wp_head', array($this, 'output_schema_json_ld'), 99);
    }
    
    /**
     * Schema JSON-LD'yi frontend'e bas
     */
    public function output_schema_json_ld() {
        // Sadece tekil post sayfalarında
        if (!is_singular('post')) {
            return;
        }
        
        // Admin ekranında çalışmasın
        if (is_admin()) {
            return;
        }
        
        $post_id = get_the_ID();
        
        // DODO schema'yı al
        $schema_json = get_post_meta($post_id, '_dodo_ai_schema_json_ld', true);
        
        if (empty($schema_json)) {
            return;
        }
        
        // JSON'u validate et
        $schema_data = json_decode($schema_json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Geçersiz JSON, basma
            return;
        }
        
        // JSON-LD'yi bas
        echo "\n<!-- DODO AI Schema -->\n";
        echo '<script type="application/ld+json">' . "\n";
        echo $schema_json . "\n";
        echo '</script>' . "\n";
        echo "<!-- /DODO AI Schema -->\n\n";
    }
}
