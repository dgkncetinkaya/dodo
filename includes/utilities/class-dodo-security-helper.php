<?php
/**
 * Security Helper - Centralized security utilities
 * 
 * PHASE 7 - Security Hardening Final Pass
 * Provides reusable security functions for the entire plugin
 * 
 * @package DODO_AI_SEO
 * @since 4.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (class_exists('DODO_Security_Helper')) {
    return;
}

class DODO_Security_Helper {
    
    /**
     * Verify AJAX request security
     * 
     * @param string $nonce_action Nonce action name
     * @param string $capability Required capability (default: manage_options)
     * @return bool|WP_Error True if valid, WP_Error on failure
     */
    public static function verify_ajax_request($nonce_action = 'dodo_ajax_nonce', $capability = 'manage_options') {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), $nonce_action)) {
            return new WP_Error('invalid_nonce', __('Güvenlik doğrulaması başarısız.', 'dodo-ai-seo'));
        }
        
        // Check capability
        if (!current_user_can($capability)) {
            return new WP_Error('insufficient_permissions', __('Bu işlem için yetkiniz yok.', 'dodo-ai-seo'));
        }
        
        return true;
    }
    
    /**
     * Sanitize POST data array
     * 
     * @param array $keys Keys to sanitize
     * @param string $type Sanitization type: text, textarea, email, url, int, float, bool, array
     * @return array Sanitized data
     */
    public static function sanitize_post_data($keys, $type = 'text') {
        $sanitized = array();
        
        foreach ($keys as $key) {
            if (!isset($_POST[$key])) {
                $sanitized[$key] = null;
                continue;
            }
            
            $value = wp_unslash($_POST[$key]);
            
            switch ($type) {
                case 'textarea':
                    $sanitized[$key] = sanitize_textarea_field($value);
                    break;
                    
                case 'email':
                    $sanitized[$key] = sanitize_email($value);
                    break;
                    
                case 'url':
                    $sanitized[$key] = esc_url_raw($value);
                    break;
                    
                case 'int':
                    $sanitized[$key] = absint($value);
                    break;
                    
                case 'float':
                    $sanitized[$key] = floatval($value);
                    break;
                    
                case 'bool':
                    $sanitized[$key] = (bool) $value;
                    break;
                    
                case 'array':
                    $sanitized[$key] = is_array($value) ? array_map('sanitize_text_field', $value) : array();
                    break;
                    
                case 'text':
                default:
                    $sanitized[$key] = sanitize_text_field($value);
                    break;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Escape output for HTML
     * 
     * @param mixed $data Data to escape
     * @param string $context Context: html, attr, textarea, url
     * @return mixed Escaped data
     */
    public static function escape_output($data, $context = 'html') {
        if (is_array($data)) {
            return array_map(function($item) use ($context) {
                return self::escape_output($item, $context);
            }, $data);
        }
        
        switch ($context) {
            case 'attr':
                return esc_attr($data);
                
            case 'textarea':
                return esc_textarea($data);
                
            case 'url':
                return esc_url($data);
                
            case 'js':
                return esc_js($data);
                
            case 'html':
            default:
                return esc_html($data);
        }
    }
    
    /**
     * Safe SQL table name
     * 
     * @param string $table_suffix Table suffix (without prefix)
     * @return string Full table name
     */
    public static function get_table_name($table_suffix) {
        global $wpdb;
        
        // Only allow alphanumeric and underscore
        $table_suffix = preg_replace('/[^a-zA-Z0-9_]/', '', $table_suffix);
        
        return $wpdb->prefix . 'dodo_' . $table_suffix;
    }
    
    /**
     * Rate limit check for AJAX actions
     * 
     * @param string $action Action name
     * @param int $max_requests Max requests per minute
     * @return bool|WP_Error True if allowed, WP_Error if rate limited
     */
    public static function check_rate_limit($action, $max_requests = 60) {
        $user_id = get_current_user_id();
        $transient_key = 'dodo_rate_limit_' . $action . '_' . $user_id;
        
        $requests = get_transient($transient_key);
        
        if ($requests === false) {
            // First request
            set_transient($transient_key, 1, 60);
            return true;
        }
        
        if ($requests >= $max_requests) {
            return new WP_Error('rate_limited', __('Çok fazla istek. Lütfen bir dakika bekleyin.', 'dodo-ai-seo'));
        }
        
        // Increment counter
        set_transient($transient_key, $requests + 1, 60);
        
        return true;
    }
    
    /**
     * Validate and sanitize file upload
     * 
     * @param array $file $_FILES array element
     * @param array $allowed_types Allowed MIME types
     * @param int $max_size Max file size in bytes
     * @return array|WP_Error Sanitized file data or error
     */
    public static function validate_file_upload($file, $allowed_types = array(), $max_size = 5242880) {
        // Check if file was uploaded
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return new WP_Error('invalid_upload', __('Geçersiz dosya yüklemesi.', 'dodo-ai-seo'));
        }
        
        // Check file size
        if ($file['size'] > $max_size) {
            return new WP_Error('file_too_large', sprintf(__('Dosya çok büyük. Maksimum: %s', 'dodo-ai-seo'), size_format($max_size)));
        }
        
        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!empty($allowed_types) && !in_array($mime_type, $allowed_types, true)) {
            return new WP_Error('invalid_file_type', __('Geçersiz dosya tipi.', 'dodo-ai-seo'));
        }
        
        return array(
            'name' => sanitize_file_name($file['name']),
            'type' => $mime_type,
            'tmp_name' => $file['tmp_name'],
            'size' => $file['size'],
        );
    }
    
    /**
     * Sanitize SQL LIKE pattern
     * 
     * @param string $pattern LIKE pattern
     * @return string Sanitized pattern
     */
    public static function sanitize_like_pattern($pattern) {
        global $wpdb;
        return $wpdb->esc_like($pattern);
    }
    
    /**
     * Validate cron request
     * 
     * @return bool|WP_Error True if valid cron, WP_Error otherwise
     */
    public static function verify_cron_request() {
        // Check if it's a cron request
        if (!defined('DOING_CRON') || !DOING_CRON) {
            return new WP_Error('invalid_cron', __('Bu işlem sadece cron ile çalıştırılabilir.', 'dodo-ai-seo'));
        }
        
        return true;
    }
    
    /**
     * Log security event
     * 
     * @param string $event_type Event type
     * @param array $data Event data
     */
    public static function log_security_event($event_type, $data = array()) {
        if (!WP_DEBUG) {
            return;
        }
        
        $log_data = array_merge(array(
            'event' => $event_type,
            'timestamp' => current_time('mysql'),
            'user_id' => get_current_user_id(),
            'ip' => self::get_client_ip(),
        ), $data);
        
        error_log('[DODO Security] ' . json_encode($log_data));
    }
    
    /**
     * Get client IP address
     * 
     * @return string IP address
     */
    private static function get_client_ip() {
        $ip = '';
        
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = sanitize_text_field(wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR']));
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']));
        }
        
        return $ip;
    }
    
    /**
     * Validate JSON input
     * 
     * @param string $json JSON string
     * @param int $max_depth Maximum depth
     * @return array|WP_Error Decoded array or error
     */
    public static function validate_json_input($json, $max_depth = 10) {
        $data = json_decode($json, true, $max_depth);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('invalid_json', __('Geçersiz JSON formatı.', 'dodo-ai-seo'));
        }
        
        return $data;
    }
    
    /**
     * Prevent SQL injection in ORDER BY clause
     * 
     * @param string $orderby Order by field
     * @param array $allowed_fields Allowed field names
     * @param string $default Default field
     * @return string Safe orderby value
     */
    public static function sanitize_orderby($orderby, $allowed_fields, $default = 'id') {
        if (!in_array($orderby, $allowed_fields, true)) {
            return $default;
        }
        
        return $orderby;
    }
    
    /**
     * Prevent SQL injection in ORDER direction
     * 
     * @param string $order Order direction
     * @return string Safe order value (ASC or DESC)
     */
    public static function sanitize_order($order) {
        $order = strtoupper($order);
        
        if (!in_array($order, array('ASC', 'DESC'), true)) {
            return 'DESC';
        }
        
        return $order;
    }
}
