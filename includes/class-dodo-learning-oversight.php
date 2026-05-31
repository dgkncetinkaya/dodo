<?php
/**
 * Learning Oversight System
 * 
 * Human oversight for major adaptations
 * 
 * @package DODO_AI_SEO
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Learning_Oversight {
    
    /**
     * Check if adaptation requires human approval
     */
    public function requires_approval($learned_strategy, $current_strategy) {
        $changes = $this->calculate_changes($learned_strategy, $current_strategy);
        
        // Major changes that require approval
        $major_changes = [
            'geo_optimization' => 30,  // 30% change in GEO
            'cta_density' => 25,       // 25% change in CTA
            'target_words' => 40,      // 40% change in word count
            'semantic_aggressiveness' => 2, // 2 level change
        ];
        
        foreach ($major_changes as $key => $threshold) {
            if (isset($changes[$key])) {
                $change = $changes[$key];
                
                // Numeric threshold check
                if (is_numeric($threshold) && isset($change['change_pct'])) {
                    if (abs($change['change_pct']) >= $threshold) {
                        return [
                            'requires_approval' => true,
                            'reason' => "Major {$key} change: {$change['change_pct']}%",
                            'change' => $change,
                        ];
                    }
                }
                
                // Categorical threshold check
                if (!is_numeric($threshold)) {
                    $levels = ['low', 'moderate', 'high', 'aggressive'];
                    $from_idx = array_search($change['from'], $levels);
                    $to_idx = array_search($change['to'], $levels);
                    
                    if ($from_idx !== false && $to_idx !== false) {
                        if (abs($to_idx - $from_idx) >= $threshold) {
                            return [
                                'requires_approval' => true,
                                'reason' => "Major {$key} change: {$change['from']} → {$change['to']}",
                                'change' => $change,
                            ];
                        }
                    }
                }
            }
        }
        
        return [
            'requires_approval' => false,
            'reason' => 'Changes within acceptable range',
        ];
    }
    
    /**
     * Calculate changes between strategies
     */
    private function calculate_changes($learned_strategy, $current_strategy) {
        $changes = [];
        
        foreach ($learned_strategy as $key => $new_value) {
            $old_value = $current_strategy[$key] ?? null;
            
            if ($old_value !== $new_value && $old_value !== null) {
                $changes[$key] = [
                    'from' => $old_value,
                    'to' => $new_value,
                ];
                
                // Calculate percentage change for numeric values
                if (is_numeric($old_value) && is_numeric($new_value) && $old_value > 0) {
                    $changes[$key]['change_pct'] = round((($new_value - $old_value) / $old_value) * 100, 1);
                }
            }
        }
        
        return $changes;
    }
    
    /**
     * Create approval request
     */
    public function create_approval_request($learned_strategy, $current_strategy, $reason) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'dodo_approval_requests';
        
        $request_id = wp_generate_uuid4();
        
        $wpdb->insert(
            $table,
            [
                'request_id' => $request_id,
                'learned_strategy' => json_encode($learned_strategy),
                'current_strategy' => json_encode($current_strategy),
                'reason' => $reason,
                'status' => 'pending',
                'created_at' => current_time('mysql'),
            ],
            ['%s', '%s', '%s', '%s', '%s', '%s']
        );
        
        error_log("[DODO Oversight] Approval request created: {$request_id}");
        
        // Send notification to admin
        $this->notify_admin($request_id, $reason);
        
        return $request_id;
    }
    
    /**
     * Approve request
     */
    public function approve_request($request_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'dodo_approval_requests';
        
        $wpdb->update(
            $table,
            [
                'status' => 'approved',
                'approved_at' => current_time('mysql'),
                'approved_by' => get_current_user_id(),
            ],
            ['request_id' => $request_id],
            ['%s', '%s', '%d'],
            ['%s']
        );
        
        error_log("[DODO Oversight] Request approved: {$request_id}");
        
        return true;
    }
    
    /**
     * Reject request
     */
    public function reject_request($request_id, $rejection_reason = '') {
        global $wpdb;
        
        $table = $wpdb->prefix . 'dodo_approval_requests';
        
        $wpdb->update(
            $table,
            [
                'status' => 'rejected',
                'rejected_at' => current_time('mysql'),
                'rejected_by' => get_current_user_id(),
                'rejection_reason' => $rejection_reason,
            ],
            ['request_id' => $request_id],
            ['%s', '%s', '%d', '%s'],
            ['%s']
        );
        
        error_log("[DODO Oversight] Request rejected: {$request_id}");
        
        return true;
    }
    
    /**
     * Get pending requests
     */
    public function get_pending_requests() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'dodo_approval_requests';
        
        $requests = $wpdb->get_results("
            SELECT * FROM {$table}
            WHERE status = 'pending'
            ORDER BY created_at DESC
        ");
        
        $result = [];
        
        foreach ($requests as $request) {
            $result[] = [
                'request_id' => $request->request_id,
                'learned_strategy' => json_decode($request->learned_strategy, true),
                'current_strategy' => json_decode($request->current_strategy, true),
                'reason' => $request->reason,
                'status' => $request->status,
                'created_at' => $request->created_at,
            ];
        }
        
        return $result;
    }
    
    /**
     * Notify admin
     */
    private function notify_admin($request_id, $reason) {
        // Get admin email
        $admin_email = get_option('admin_email');
        
        // Email subject
        $subject = '[DODO AI SEO] Learning Adaptation Requires Approval';
        
        // Email body
        $message = "A major learning adaptation requires your approval.\n\n";
        $message .= "Request ID: {$request_id}\n";
        $message .= "Reason: {$reason}\n\n";
        $message .= "Please review in Learning Control Center:\n";
        $message .= admin_url('admin.php?page=dodo-ai-seo-learning-control');
        
        // Send email
        wp_mail($admin_email, $subject, $message);
        
        error_log("[DODO Oversight] Notification sent to: {$admin_email}");
    }
    
    /**
     * Create approval requests table
     */
    public static function create_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dodo_approval_requests';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            request_id varchar(36) NOT NULL,
            learned_strategy longtext NOT NULL,
            current_strategy longtext NOT NULL,
            reason text NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            created_at datetime NOT NULL,
            approved_at datetime DEFAULT NULL,
            approved_by bigint(20) DEFAULT NULL,
            rejected_at datetime DEFAULT NULL,
            rejected_by bigint(20) DEFAULT NULL,
            rejection_reason text DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY request_id (request_id),
            KEY status (status),
            KEY created_at (created_at)
        ) {$charset_collate};";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        error_log('[DODO Oversight] Approval requests table created');
    }
}

