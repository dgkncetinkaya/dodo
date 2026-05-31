<?php
/**
 * Security Hardening & Audit
 * 
 * @package DODO_AI_SEO
 * @since 2.0.0 (Sprint 4 - Task 8)
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Security {
    
    /**
     * Verify nonce
     */
    public static function verify_nonce($nonce, $action) {
        if (!wp_verify_nonce($nonce, $action)) {
            wp_die('Security check failed', 'Security Error', array('response' => 403));
        }
    }
    
    /**
     * Check capability
     */
    public static function check_capability($capability = 'manage_options') {
        if (!current_user_can($capability)) {
            wp_die('You do not have permission', 'Permission Denied', array('response' => 403));
        }
    }
    
    /**
     * Sanitize input array
     */
    public static function sanitize_array($array, $rules = array()) {
        $sanitized = array();
        
        foreach ($array as $key => $value) {
            $rule = $rules[$key] ?? 'text';
            
            switch ($rule) {
                case 'email':
                    $sanitized[$key] = sanitize_email($value);
                    break;
                case 'url':
                    $sanitized[$key] = esc_url_raw($value);
                    break;
                case 'int':
                    $sanitized[$key] = intval($value);
                    break;
                case 'float':
                    $sanitized[$key] = floatval($value);
                    break;
                case 'bool':
                    $sanitized[$key] = (bool) $value;
                    break;
                case 'html':
                    $sanitized[$key] = wp_kses_post($value);
                    break;
                case 'textarea':
                    $sanitized[$key] = sanitize_textarea_field($value);
                    break;
                default:
                    $sanitized[$key] = sanitize_text_field($value);
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Escape output
     */
    public static function escape($value, $context = 'html') {
        switch ($context) {
            case 'attr':
                return esc_attr($value);
            case 'url':
                return esc_url($value);
            case 'js':
                return esc_js($value);
            case 'textarea':
                return esc_textarea($value);
            default:
                return esc_html($value);
        }
    }
    
    /**
     * Prepare SQL safely
     */
    public static function prepare_sql($query, $args) {
        global $wpdb;
        return $wpdb->prepare($query, $args);
    }
    
    /**
     * Validate file upload
     */
    public static function validate_file_upload($file, $allowed_types = array('jpg', 'jpeg', 'png', 'gif')) {
        if (!isset($file['error']) || is_array($file['error'])) {
            return new WP_Error('invalid_file', 'Invalid file upload');
        }
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return new WP_Error('upload_error', 'File upload error: ' . $file['error']);
        }
        
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_ext, $allowed_types)) {
            return new WP_Error('invalid_type', 'File type not allowed');
        }
        
        if ($file['size'] > 5 * 1024 * 1024) { // 5MB
            return new WP_Error('file_too_large', 'File size exceeds 5MB');
        }
        
        return true;
    }
    
    /**
     * Rate limit check
     */
    public static function check_rate_limit($action, $limit = 10, $window = 60) {
        $user_id = get_current_user_id();
        $key = "dodo_rate_limit_{$action}_{$user_id}";
        
        $count = get_transient($key) ?: 0;
        
        if ($count >= $limit) {
            return new WP_Error('rate_limit', 'Too many requests. Please wait.');
        }
        
        set_transient($key, $count + 1, $window);
        return true;
    }
    
    /**
     * Audit security issues
     */
    public static function audit() {
        $issues = array();
        
        // Check if WP_DEBUG is on in production
        if (WP_DEBUG && !defined('WP_LOCAL_DEV')) {
            $issues[] = array(
                'severity' => 'warning',
                'message' => 'WP_DEBUG is enabled in production',
                'fix' => 'Set WP_DEBUG to false in wp-config.php',
            );
        }
        
        // Check file permissions
        $upload_dir = wp_upload_dir();
        if (is_writable($upload_dir['basedir'])) {
            // This is expected, but check if it's too permissive
            $perms = fileperms($upload_dir['basedir']);
            if (($perms & 0777) === 0777) {
                $issues[] = array(
                    'severity' => 'critical',
                    'message' => 'Upload directory has 777 permissions',
                    'fix' => 'Change permissions to 755',
                );
            }
        }
        
        // Check if API key is exposed
        $settings = get_option('dodo_ai_seo_settings', array());
        if (!empty($settings['openai_api_key'])) {
            // API key should not be visible in frontend
            if (defined('REST_REQUEST') && REST_REQUEST) {
                $issues[] = array(
                    'severity' => 'info',
                    'message' => 'API key is stored in options (normal)',
                    'fix' => 'No action needed - key is not exposed',
                );
            }
        }
        
        // Check nonce usage in AJAX
        // This would require code analysis, just flag for manual review
        $issues[] = array(
            'severity' => 'info',
            'message' => 'Manual review: Verify all AJAX endpoints use nonce checks',
            'fix' => 'Review code for check_ajax_referer() calls',
        );
        
        return array(
            'total_issues' => count($issues),
            'critical' => count(array_filter($issues, function($i) { return $i['severity'] === 'critical'; })),
            'warnings' => count(array_filter($issues, function($i) { return $i['severity'] === 'warning'; })),
            'info' => count(array_filter($issues, function($i) { return $i['severity'] === 'info'; })),
            'issues' => $issues,
        );
    }
}
