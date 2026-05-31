<?php
/**
 * Structured Logging System
 * 
 * @package DODO_AI_SEO
 * @since 2.0.0 (Sprint 4 - Task 7)
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Logger {
    
    const LEVEL_DEBUG = 'debug';
    const LEVEL_INFO = 'info';
    const LEVEL_WARNING = 'warning';
    const LEVEL_ERROR = 'error';
    const LEVEL_CRITICAL = 'critical';
    
    /**
     * Log message
     */
    public static function log($level, $module, $message, $context = array()) {
        // Don't log debug in production
        if ($level === self::LEVEL_DEBUG && !WP_DEBUG) {
            return;
        }
        
        $entry = array(
            'timestamp' => current_time('mysql'),
            'level' => $level,
            'module' => $module,
            'message' => $message,
            'context' => $context,
            'user_id' => get_current_user_id(),
        );
        
        // Store in transient
        $logs = get_transient('dodo_logs') ?: array();
        array_unshift($logs, $entry);
        $logs = array_slice($logs, 0, 500); // Keep last 500
        set_transient('dodo_logs', $logs, WEEK_IN_SECONDS);
        
        // Also log to error_log for critical/error
        if (in_array($level, array(self::LEVEL_ERROR, self::LEVEL_CRITICAL))) {
            error_log(sprintf('[DODO %s] [%s] %s', strtoupper($level), $module, $message));
        }
    }
    
    /**
     * Convenience methods
     */
    public static function debug($module, $message, $context = array()) {
        self::log(self::LEVEL_DEBUG, $module, $message, $context);
    }
    
    public static function info($module, $message, $context = array()) {
        self::log(self::LEVEL_INFO, $module, $message, $context);
    }
    
    public static function warning($module, $message, $context = array()) {
        self::log(self::LEVEL_WARNING, $module, $message, $context);
    }
    
    public static function error($module, $message, $context = array()) {
        self::log(self::LEVEL_ERROR, $module, $message, $context);
    }
    
    public static function critical($module, $message, $context = array()) {
        self::log(self::LEVEL_CRITICAL, $module, $message, $context);
    }
    
    /**
     * Get logs with filters
     */
    public static function get_logs($filters = array()) {
        $logs = get_transient('dodo_logs') ?: array();
        
        // Filter by level
        if (!empty($filters['level'])) {
            $logs = array_filter($logs, function($log) use ($filters) {
                return $log['level'] === $filters['level'];
            });
        }
        
        // Filter by module
        if (!empty($filters['module'])) {
            $logs = array_filter($logs, function($log) use ($filters) {
                return $log['module'] === $filters['module'];
            });
        }
        
        // Filter by date
        if (!empty($filters['since'])) {
            $logs = array_filter($logs, function($log) use ($filters) {
                return strtotime($log['timestamp']) >= strtotime($filters['since']);
            });
        }
        
        // Limit
        if (!empty($filters['limit'])) {
            $logs = array_slice($logs, 0, $filters['limit']);
        }
        
        return array_values($logs);
    }
    
    /**
     * Export logs
     */
    public static function export_logs($format = 'json') {
        $logs = get_transient('dodo_logs') ?: array();
        
        if ($format === 'json') {
            return json_encode($logs, JSON_PRETTY_PRINT);
        }
        
        if ($format === 'csv') {
            $csv = "Timestamp,Level,Module,Message\n";
            foreach ($logs as $log) {
                $csv .= sprintf('"%s","%s","%s","%s"' . "\n",
                    $log['timestamp'],
                    $log['level'],
                    $log['module'],
                    str_replace('"', '""', $log['message'])
                );
            }
            return $csv;
        }
        
        return '';
    }
    
    /**
     * Clear logs
     */
    public static function clear_logs() {
        delete_transient('dodo_logs');
    }
    
    /**
     * Get log stats
     */
    public static function get_stats() {
        $logs = get_transient('dodo_logs') ?: array();
        
        $stats = array(
            'total' => count($logs),
            'by_level' => array(),
            'by_module' => array(),
        );
        
        foreach ($logs as $log) {
            // Count by level
            if (!isset($stats['by_level'][$log['level']])) {
                $stats['by_level'][$log['level']] = 0;
            }
            $stats['by_level'][$log['level']]++;
            
            // Count by module
            if (!isset($stats['by_module'][$log['module']])) {
                $stats['by_module'][$log['module']] = 0;
            }
            $stats['by_module'][$log['module']]++;
        }
        
        return $stats;
    }
}
