<?php
/**
 * Security Manager
 * 
 * Enterprise-grade security layer for DODO AI SEO
 * Handles AJAX security, input validation, prompt injection prevention
 *
 * @package DODO_AI_SEO
 * @since 2.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Security_Manager {
    
    /**
     * Rate limiting configuration
     */
    const RATE_LIMIT_WINDOW = 3600; // 1 hour
    const MAX_REQUESTS_PER_HOUR = 50;
    const MAX_GENERATION_PER_HOUR = 10;
    
    /**
     * Prompt injection patterns
     */
    private $injection_patterns = [
        '/ignore\s+(previous|above|all)\s+instructions?/i',
        '/system\s*:\s*you\s+are/i',
        '/forget\s+(everything|all|previous)/i',
        '/new\s+instructions?/i',
        '/disregard\s+(previous|above)/i',
        '/override\s+system/i',
        '/jailbreak/i',
        '/prompt\s*:\s*/i',
        '/<\s*script/i',
        '/eval\s*\(/i',
    ];
    
    /**
     * Verify AJAX nonce
     *
     * @param string $nonce Nonce value
     * @param string $action Nonce action
     * @return bool|WP_Error Valid or error
     */
    public function verify_ajax_nonce($nonce, $action) {
        if (!wp_verify_nonce($nonce, $action)) {
            error_log(sprintf(
                '[DODO Security] Nonce verification failed for action: %s',
                $action
            ));
            
            return new WP_Error(
                'invalid_nonce',
                'Security verification failed. Please refresh the page.'
            );
        }
        
        return true;
    }
    
    /**
     * Verify user capability
     *
     * @param string $capability Required capability
     * @return bool|WP_Error Has capability or error
     */
    public function verify_capability($capability = 'manage_options') {
        if (!current_user_can($capability)) {
            error_log(sprintf(
                '[DODO Security] Capability check failed. Required: %s, User ID: %d',
                $capability,
                get_current_user_id()
            ));
            
            return new WP_Error(
                'insufficient_permissions',
                'You do not have permission to perform this action.'
            );
        }
        
        return true;
    }
    
    /**
     * Sanitize generation parameters
     *
     * @param array $params Raw parameters
     * @return array Sanitized parameters
     */
    public function sanitize_generation_params($params) {
        $sanitized = [];
        
        // Focus keyword - strict sanitization
        $sanitized['focus_keyword'] = isset($params['focus_keyword']) 
            ? $this->sanitize_keyword($params['focus_keyword']) 
            : '';
        
        // Topic - allow more content but sanitize
        $sanitized['topic'] = isset($params['topic']) 
            ? sanitize_textarea_field($params['topic']) 
            : '';
        
        // Length - whitelist
        $allowed_lengths = ['short', 'medium', 'long', 'very_long'];
        $sanitized['length'] = isset($params['length']) && in_array($params['length'], $allowed_lengths)
            ? $params['length']
            : 'medium';
        
        // Tone - whitelist
        $allowed_tones = ['technical', 'conversational', 'persuasive'];
        $sanitized['tone'] = isset($params['tone']) && in_array($params['tone'], $allowed_tones)
            ? $params['tone']
            : 'conversational';
        
        // Content intent - whitelist
        $allowed_intents = ['informational', 'commercial', 'transactional'];
        $sanitized['content_intent'] = isset($params['content_intent']) && in_array($params['content_intent'], $allowed_intents)
            ? $params['content_intent']
            : 'informational';
        
        // Numeric values
        $sanitized['category_id'] = isset($params['category_id']) ? absint($params['category_id']) : 0;
        
        // Boolean values
        $sanitized['override_cannibalization'] = isset($params['override_cannibalization']) 
            ? (bool) $params['override_cannibalization'] 
            : false;
        
        error_log('[DODO Security] Generation parameters sanitized');
        
        return $sanitized;
    }
    
    /**
     * Sanitize keyword with prompt injection check
     *
     * @param string $keyword Raw keyword
     * @return string Sanitized keyword
     */
    private function sanitize_keyword($keyword) {
        // Basic sanitization
        $keyword = sanitize_text_field($keyword);
        
        // Length limit
        if (strlen($keyword) > 200) {
            $keyword = substr($keyword, 0, 200);
            error_log('[DODO Security] Keyword truncated to 200 characters');
        }
        
        // Check for prompt injection
        if ($this->detect_prompt_injection($keyword)) {
            error_log(sprintf(
                '[DODO Security] PROMPT INJECTION DETECTED in keyword: %s',
                $keyword
            ));
            
            // Sanitize aggressively - remove injection patterns
            $keyword = preg_replace('/\b(ignore|disregard|forget|override|system|prompt|instructions?|admin|drop|delete|insert|update|select|union|script|eval)\b/i', '', $keyword);
            $keyword = preg_replace('/[^a-zA-Z0-9\s\-_\p{L}]/u', '', $keyword);
            $keyword = trim($keyword);
            
            error_log('[DODO Security] Keyword sanitized after injection detection');
        }
        
        // Additional SQL injection protection
        $sql_patterns = [
            '/(\bDROP\b|\bDELETE\b|\bINSERT\b|\bUPDATE\b|\bUNION\b|\bSELECT\b)/i',
            '/--/',
            '/;/',
            '/\/\*/',
            '/\*\//',
        ];
        
        foreach ($sql_patterns as $pattern) {
            if (preg_match($pattern, $keyword)) {
                error_log('[DODO Security] SQL INJECTION PATTERN DETECTED in keyword');
                $keyword = preg_replace($pattern, '', $keyword);
            }
        }
        
        return trim($keyword);
    }
    
    /**
     * Detect prompt injection attempts
     *
     * @param string $input User input
     * @return bool Is injection attempt
     */
    public function detect_prompt_injection($input) {
        $input_lower = strtolower($input);
        
        foreach ($this->injection_patterns as $pattern) {
            if (preg_match($pattern, $input_lower)) {
                return true;
            }
        }
        
        // Check for suspicious character sequences
        if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $input)) {
            return true;
        }
        
        // Check for excessive special characters
        $special_char_count = preg_match_all('/[^\w\s\-]/u', $input);
        $total_length = mb_strlen($input);
        
        if ($total_length > 0 && ($special_char_count / $total_length) > 0.3) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Rate limit check
     *
     * @param string $action Action identifier
     * @param int $user_id User ID
     * @return bool|WP_Error Within limit or error
     */
    public function check_rate_limit($action, $user_id = null) {
        // Check if rate limiting is enabled
        $rate_limiting_enabled = get_option('dodo_rate_limiting_enabled', true);
        
        if (!$rate_limiting_enabled) {
            return true;
        }
        
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }
        
        // Admins bypass rate limiting
        if (user_can($user_id, 'manage_options')) {
            return true;
        }
        
        $transient_key = sprintf('dodo_rate_limit_%s_%d', $action, $user_id);
        $requests = get_transient($transient_key);
        
        if ($requests === false) {
            // First request in window
            set_transient($transient_key, 1, self::RATE_LIMIT_WINDOW);
            
            error_log(sprintf(
                '[DODO Security] Rate limit tracking started for user %d, action: %s',
                $user_id,
                $action
            ));
            
            return true;
        }
        
        // Determine limit based on action
        $limit = $action === 'generation' 
            ? self::MAX_GENERATION_PER_HOUR 
            : self::MAX_REQUESTS_PER_HOUR;
        
        if ($requests >= $limit) {
            error_log(sprintf(
                '[DODO Security] Rate limit exceeded for user %d, action: %s (%d/%d)',
                $user_id,
                $action,
                $requests,
                $limit
            ));
            
            // Log security event
            $this->log_security_event(
                'rate_limit_exceeded',
                sprintf('User exceeded rate limit for action: %s', $action),
                ['action' => $action, 'requests' => $requests, 'limit' => $limit]
            );
            
            return new WP_Error(
                'rate_limit_exceeded',
                sprintf('Rate limit exceeded. Maximum %d requests per hour. Try again later.', $limit)
            );
        }
        
        // Increment counter
        set_transient($transient_key, $requests + 1, self::RATE_LIMIT_WINDOW);
        
        return true;
    }
    
    /**
     * Validate file upload
     *
     * @param array $file $_FILES array element
     * @param array $allowed_types Allowed MIME types
     * @return bool|WP_Error Valid or error
     */
    public function validate_file_upload($file, $allowed_types = []) {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return new WP_Error('upload_error', 'File upload failed');
        }
        
        // Check file size (10MB max)
        if ($file['size'] > 10 * 1024 * 1024) {
            return new WP_Error('file_too_large', 'File size exceeds 10MB limit');
        }
        
        // Verify MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!empty($allowed_types) && !in_array($mime_type, $allowed_types)) {
            return new WP_Error('invalid_file_type', 'File type not allowed');
        }
        
        // Check for path traversal in filename
        $filename = basename($file['name']);
        if ($filename !== $file['name']) {
            return new WP_Error('invalid_filename', 'Invalid filename');
        }
        
        return true;
    }
    
    /**
     * Sanitize output for display
     *
     * @param string $content Content to sanitize
     * @param string $context Display context
     * @return string Sanitized content
     */
    public function sanitize_output($content, $context = 'html') {
        switch ($context) {
            case 'html':
                return wp_kses_post($content);
                
            case 'text':
                return esc_html($content);
                
            case 'url':
                return esc_url($content);
                
            case 'attr':
                return esc_attr($content);
                
            case 'js':
                return esc_js($content);
                
            default:
                return esc_html($content);
        }
    }
    
    /**
     * Verify cron request authenticity
     *
     * @return bool|WP_Error Valid or error
     */
    public function verify_cron_request() {
        // Check if request is from WP-Cron
        if (!defined('DOING_CRON') || !DOING_CRON) {
            // Check for cron key
            $cron_key = get_option('dodo_cron_key');
            $request_key = isset($_GET['dodo_cron_key']) ? $_GET['dodo_cron_key'] : '';
            
            if ($cron_key !== $request_key) {
                error_log('[DODO Security] Unauthorized cron request attempt');
                
                return new WP_Error(
                    'unauthorized_cron',
                    'Unauthorized cron request'
                );
            }
        }
        
        return true;
    }
    
    /**
     * Generate secure cron key
     *
     * @return string Cron key
     */
    public function generate_cron_key() {
        $key = wp_generate_password(32, false);
        update_option('dodo_cron_key', $key);
        
        error_log('[DODO Security] New cron key generated');
        
        return $key;
    }
    
    /**
     * Log security event
     *
     * @param string $event_type Event type
     * @param string $description Event description
     * @param array $context Additional context
     */
    public function log_security_event($event_type, $description, $context = []) {
        $log_entry = [
            'timestamp' => current_time('mysql'),
            'event_type' => $event_type,
            'description' => $description,
            'user_id' => get_current_user_id(),
            'ip_address' => $this->get_client_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
            'context' => $context,
        ];
        
        error_log(sprintf(
            '[DODO Security Event] %s: %s (User: %d, IP: %s)',
            $event_type,
            $description,
            $log_entry['user_id'],
            $log_entry['ip_address']
        ));
        
        // Store in database for audit trail
        $this->store_security_log($log_entry);
    }
    
    /**
     * Get client IP address
     *
     * @return string IP address
     */
    private function get_client_ip() {
        $ip_keys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($ip_keys as $key) {
            if (isset($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                
                // Handle multiple IPs (proxy)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return 'unknown';
    }
    
    /**
     * Store security log in database
     *
     * @param array $log_entry Log entry data
     */
    private function store_security_log($log_entry) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dodo_security_logs';
        
        $wpdb->insert(
            $table_name,
            [
                'event_type' => $log_entry['event_type'],
                'description' => $log_entry['description'],
                'user_id' => $log_entry['user_id'],
                'ip_address' => $log_entry['ip_address'],
                'user_agent' => substr($log_entry['user_agent'], 0, 255),
                'context' => json_encode($log_entry['context']),
                'created_at' => $log_entry['timestamp'],
            ],
            ['%s', '%s', '%d', '%s', '%s', '%s', '%s']
        );
    }
    
    /**
     * Create security logs table
     */
    public static function create_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dodo_security_logs';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            event_type varchar(50) NOT NULL,
            description text NOT NULL,
            user_id bigint(20) NOT NULL,
            ip_address varchar(45) NOT NULL,
            user_agent varchar(255) DEFAULT '',
            context text,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY event_type (event_type),
            KEY user_id (user_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        error_log('[DODO Security] Security logs table created');
    }
}
