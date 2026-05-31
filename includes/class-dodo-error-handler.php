<?php
/**
 * Global Error Handler & Recovery System
 * 
 * @package DODO_AI_SEO
 * @since 2.0.0 (Sprint 4 - Task 6)
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Error_Handler {
    
    /**
     * Mask sensitive data in strings (API keys, tokens, etc.)
     */
    public static function mask_secrets($data) {
        if (is_string($data)) {
            // Mask OpenAI API keys (sk-...)
            $data = preg_replace('/sk-[a-zA-Z0-9]{48}/', 'sk-***MASKED***', $data);
            
            // Mask bearer tokens
            $data = preg_replace('/Bearer\s+[a-zA-Z0-9\-_\.]+/i', 'Bearer ***MASKED***', $data);
            
            // Mask authorization headers
            $data = preg_replace('/(authorization["\']?\s*[:=]\s*["\']?)[^"\'}\s]+/i', '$1***MASKED***', $data);
            
            // Mask api_key parameters
            $data = preg_replace('/(api_key["\']?\s*[:=]\s*["\']?)[^"\'}\s]+/i', '$1***MASKED***', $data);
            
            return $data;
        }
        
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = self::mask_secrets($value);
            }
            return $data;
        }
        
        if (is_object($data)) {
            foreach ($data as $key => $value) {
                $data->$key = self::mask_secrets($value);
            }
            return $data;
        }
        
        return $data;
    }
    
    /**
     * Safe error_log with secret masking
     */
    public static function safe_log($message, $context = array()) {
        // Mask secrets in message
        $safe_message = self::mask_secrets($message);
        
        // Mask secrets in context
        $safe_context = self::mask_secrets($context);
        
        // Log with context if provided
        if (!empty($safe_context)) {
            error_log($safe_message . ' | Context: ' . json_encode($safe_context));
        } else {
            error_log($safe_message);
        }
    }
    
    /**
     * Normalize error for user display
     */
    public static function normalize_error($error) {
        if (is_wp_error($error)) {
            return array(
                'type' => 'wp_error',
                'code' => $error->get_error_code(),
                'message' => $error->get_error_message(),
                'user_message' => self::get_user_friendly_message($error->get_error_code()),
                'recovery_action' => self::get_recovery_action($error->get_error_code()),
            );
        }
        
        if ($error instanceof Exception) {
            return array(
                'type' => 'exception',
                'code' => $error->getCode(),
                'message' => $error->getMessage(),
                'user_message' => self::get_user_friendly_message('exception'),
                'recovery_action' => self::get_recovery_action('exception'),
            );
        }
        
        return array(
            'type' => 'unknown',
            'message' => is_string($error) ? $error : 'Bilinmeyen hata',
            'user_message' => 'Bir hata oluştu. Lütfen tekrar deneyin.',
            'recovery_action' => 'retry',
        );
    }
    
    /**
     * Get user-friendly error message
     */
    private static function get_user_friendly_message($error_code) {
        $messages = array(
            'api_key_missing' => 'OpenAI API anahtarı tanımlanmamış. Lütfen ayarlardan API anahtarınızı girin.',
            'api_error' => 'OpenAI API ile bağlantı kurulamadı. Lütfen API anahtarınızı kontrol edin.',
            'rate_limit' => 'API kullanım limiti aşıldı. Lütfen birkaç dakika bekleyin.',
            'insufficient_quota' => 'OpenAI hesabınızda yeterli kredi yok. Lütfen hesabınıza kredi ekleyin.',
            'content_too_long' => 'İçerik çok uzun. Lütfen daha kısa bir içerik deneyin.',
            'invalid_request' => 'Geçersiz istek. Lütfen girdiğiniz bilgileri kontrol edin.',
            'network_error' => 'Ağ bağlantısı hatası. İnternet bağlantınızı kontrol edin.',
            'timeout' => 'İşlem zaman aşımına uğradı. Lütfen tekrar deneyin.',
            'database_error' => 'Veritabanı hatası. Lütfen site yöneticinize başvurun.',
            'permission_denied' => 'Bu işlem için yetkiniz yok.',
            'table_missing' => 'Veritabanı tablosu eksik. Otomatik onarım başlatılıyor...',
        );
        
        return $messages[$error_code] ?? 'Bir hata oluştu. Lütfen tekrar deneyin.';
    }
    
    /**
     * Get recovery action
     */
    private static function get_recovery_action($error_code) {
        $actions = array(
            'api_key_missing' => 'configure_api',
            'api_error' => 'check_api_key',
            'rate_limit' => 'wait_and_retry',
            'insufficient_quota' => 'add_credits',
            'content_too_long' => 'reduce_content',
            'invalid_request' => 'check_input',
            'network_error' => 'check_connection',
            'timeout' => 'retry',
            'database_error' => 'contact_admin',
            'permission_denied' => 'contact_admin',
            'table_missing' => 'auto_repair',
        );
        
        return $actions[$error_code] ?? 'retry';
    }
    
    /**
     * Auto-heal common issues
     */
    public static function auto_heal($error_code) {
        switch ($error_code) {
            case 'table_missing':
                require_once DODO_PLUGIN_DIR . 'includes/class-dodo-database.php';
                $result = DODO_Database::auto_repair();
                return $result['success'];
                
            case 'cache_corrupted':
                wp_cache_flush();
                return true;
                
            case 'transient_expired':
                // Transients auto-expire, no action needed
                return true;
                
            default:
                return false;
        }
    }
    
    /**
     * Log error
     */
    public static function log_error($error, $context = array()) {
        $normalized = self::normalize_error($error);
        
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'type' => $normalized['type'],
            'code' => $normalized['code'] ?? 'unknown',
            'message' => $normalized['message'],
            'context' => $context,
            'user_id' => get_current_user_id(),
            'url' => $_SERVER['REQUEST_URI'] ?? '',
        );
        
        error_log('[DODO ERROR] ' . json_encode($log_entry));
        
        // Store in transient for recent errors display
        $recent_errors = get_transient('dodo_recent_errors') ?: array();
        array_unshift($recent_errors, $log_entry);
        $recent_errors = array_slice($recent_errors, 0, 50); // Keep last 50
        set_transient('dodo_recent_errors', $recent_errors, DAY_IN_SECONDS);
    }
    
    /**
     * Get recent errors
     */
    public static function get_recent_errors($limit = 10) {
        $errors = get_transient('dodo_recent_errors') ?: array();
        return array_slice($errors, 0, $limit);
    }
    
    /**
     * Clear error log
     */
    public static function clear_errors() {
        delete_transient('dodo_recent_errors');
    }
    
    /**
     * Safe AJAX error response
     * 
     * @param string $message User-friendly message
     * @param int $code HTTP status code
     * @param mixed $debug Debug data (only shown if WP_DEBUG)
     */
    public static function safe_ajax_error($message, $code = 400, $debug = null) {
        $response = array(
            'message' => $message
        );
        
        // Add debug info if WP_DEBUG is enabled
        if (defined('WP_DEBUG') && WP_DEBUG && $debug !== null) {
            $response['debug'] = self::mask_secrets($debug);
        }
        
        // Log the error
        self::safe_log('[DODO AJAX ERROR] ' . $message, array(
            'code' => $code,
            'debug' => $debug
        ));
        
        wp_send_json_error($response, $code);
    }
    
    /**
     * Safe exception response
     * 
     * @param Throwable $e Exception
     * @param string $context Context description
     */
    public static function safe_exception_response(Throwable $e, $context = 'Unknown') {
        // Log with full details
        self::safe_log(
            '[DODO EXCEPTION] ' . $context . ': ' . $e->getMessage(),
            array(
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            )
        );
        
        // User-friendly message
        $user_message = __('İşlem sırasında bir hata oluştu. Lütfen tekrar deneyin.', 'dodo-ai-seo');
        
        // Debug info for developers
        $debug = null;
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $debug = array(
                'message' => $e->getMessage(),
                'file' => basename($e->getFile()),
                'line' => $e->getLine()
            );
        }
        
        wp_send_json_error(array(
            'message' => $user_message,
            'debug' => $debug
        ), 500);
    }
    
    /**
     * Mask sensitive data (alias for mask_secrets)
     * 
     * @param mixed $data Data to mask
     * @return mixed Masked data
     */
    public static function mask_sensitive_data($data) {
        return self::mask_secrets($data);
    }
    
    /**
     * Log error with context
     * 
     * @param string $context Context (e.g., 'AJAX', 'OpenAI', 'Queue')
     * @param string $message Error message
     * @param array $data Additional data
     */
    public static function log_error_with_context($context, $message, $data = array()) {
        $safe_message = self::mask_secrets($message);
        $safe_data = self::mask_secrets($data);
        
        $log_message = sprintf('[DODO][%s] %s', strtoupper($context), $safe_message);
        
        if (!empty($safe_data)) {
            $log_message .= ' | Data: ' . json_encode($safe_data);
        }
        
        error_log($log_message);
        
        // Also use the existing log_error for transient storage
        self::log_error($message, array_merge(array('context' => $context), $data));
    }
}
