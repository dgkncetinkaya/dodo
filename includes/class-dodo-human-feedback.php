<?php
/**
 * Human Feedback Loop
 * 
 * Tracks user actions on AI recommendations
 * Learns which recommendations users actually use
 * Adapts future recommendations based on user behavior
 * 
 * @package DODO_AI_SEO
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Human_Feedback {
    
    /**
     * User action types
     */
    const ACTION_ACCEPTED = 'accepted';
    const ACTION_REJECTED = 'rejected';
    const ACTION_MODIFIED = 'modified';
    const ACTION_IGNORED = 'ignored';
    const ACTION_PARTIAL = 'partial';
    
    /**
     * Recommendation types
     */
    const REC_TITLE_REWRITE = 'title_rewrite';
    const REC_META_REWRITE = 'meta_rewrite';
    const REC_CONTENT_REFRESH = 'content_refresh';
    const REC_FAQ_SUGGESTION = 'faq_suggestion';
    const REC_INTERNAL_LINKS = 'internal_links';
    const REC_SEMANTIC_EXPANSION = 'semantic_expansion';
    const REC_GEO_OPTIMIZATION = 'geo_optimization';
    const REC_HUMANIZATION = 'humanization';
    const REC_CTA_PLACEMENT = 'cta_placement';
    const REC_KEYWORD_OPTIMIZATION = 'keyword_optimization';
    
    /**
     * Minimum samples for learning
     */
    const MIN_SAMPLES = 5;
    
    /**
     * Database table
     */
    private $feedback_table;
    
    /**
     * Feedback engine
     */
    private $feedback_engine;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->feedback_table = $wpdb->prefix . 'dodo_user_feedback';
        
        if (class_exists('DODO_Feedback_Engine')) {
            require_once plugin_dir_path(__FILE__) . 'class-dodo-feedback-engine.php';
            $this->feedback_engine = new DODO_Feedback_Engine();
        }
    }
    
    /**
     * Record user feedback on recommendation
     * 
     * @param string $recommendation_type Recommendation type
     * @param string $user_action User action (accepted, rejected, modified, ignored)
     * @param array $details Feedback details
     * @return int Feedback ID
     */
    public function record_feedback($recommendation_type, $user_action, $details = []) {
        global $wpdb;
        
        error_log(sprintf(
            '[DODO Feedback] Recording - Type: %s, Action: %s',
            $recommendation_type,
            $user_action
        ));
        
        // Validate action
        if (!$this->is_valid_action($user_action)) {
            error_log("[DODO Feedback] Invalid action: {$user_action}");
            return false;
        }
        
        // Enrich details
        $enriched_details = array_merge($details, [
            'timestamp' => current_time('mysql'),
            'user_id' => get_current_user_id(),
            'user_role' => $this->get_user_role(),
            'session_id' => $this->get_session_id(),
        ]);
        
        // Store feedback
        $wpdb->insert(
            $this->feedback_table,
            [
                'recommendation_type' => $recommendation_type,
                'user_action' => $user_action,
                'details' => json_encode($enriched_details),
                'user_id' => get_current_user_id(),
                'recorded_at' => current_time('mysql'),
            ],
            ['%s', '%s', '%s', '%d', '%s']
        );
        
        $feedback_id = $wpdb->insert_id;
        
        // Update learning engine
        if ($this->feedback_engine) {
            $this->feedback_engine->learn_from_user_feedback(
                $recommendation_type,
                $user_action,
                $enriched_details
            );
        }
        
        // Update acceptance rates
        $this->update_acceptance_rates($recommendation_type, $user_action);
        
        // Update user preference profile
        $this->update_user_preferences($recommendation_type, $user_action, $enriched_details);
        
        error_log("[DODO Feedback] Recorded - ID: {$feedback_id}");
        
        return $feedback_id;
    }
    
    /**
     * Record recommendation shown to user
     * 
     * Tracks what recommendations were presented
     * 
     * @param string $recommendation_type Recommendation type
     * @param array $recommendation_data Recommendation data
     * @return int Tracking ID
     */
    public function track_recommendation_shown($recommendation_type, $recommendation_data = []) {
        global $wpdb;
        
        $wpdb->insert(
            $this->feedback_table,
            [
                'recommendation_type' => $recommendation_type,
                'user_action' => 'shown',
                'details' => json_encode([
                    'recommendation' => $recommendation_data,
                    'shown_at' => current_time('mysql'),
                ]),
                'user_id' => get_current_user_id(),
                'recorded_at' => current_time('mysql'),
            ],
            ['%s', '%s', '%s', '%d', '%s']
        );
        
        return $wpdb->insert_id;
    }
    
    /**
     * Get acceptance rate for recommendation type
     * 
     * @param string $recommendation_type Recommendation type
     * @return array Acceptance metrics
     */
    public function get_acceptance_rate($recommendation_type) {
        global $wpdb;
        
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN user_action = 'accepted' THEN 1 ELSE 0 END) as accepted,
                SUM(CASE WHEN user_action = 'rejected' THEN 1 ELSE 0 END) as rejected,
                SUM(CASE WHEN user_action = 'modified' THEN 1 ELSE 0 END) as modified,
                SUM(CASE WHEN user_action = 'ignored' THEN 1 ELSE 0 END) as ignored,
                SUM(CASE WHEN user_action = 'partial' THEN 1 ELSE 0 END) as partial
            FROM {$this->feedback_table}
            WHERE recommendation_type = %s
            AND user_action != 'shown'",
            $recommendation_type
        ), ARRAY_A);
        
        if (!$stats || $stats['total'] == 0) {
            return [
                'acceptance_rate' => 0,
                'rejection_rate' => 0,
                'modification_rate' => 0,
                'ignore_rate' => 0,
                'total_samples' => 0,
                'confidence' => 0,
            ];
        }
        
        $total = $stats['total'];
        
        return [
            'acceptance_rate' => round(($stats['accepted'] / $total) * 100, 1),
            'rejection_rate' => round(($stats['rejected'] / $total) * 100, 1),
            'modification_rate' => round(($stats['modified'] / $total) * 100, 1),
            'ignore_rate' => round(($stats['ignored'] / $total) * 100, 1),
            'partial_rate' => round(($stats['partial'] / $total) * 100, 1),
            'total_samples' => (int) $total,
            'confidence' => $this->calculate_confidence($total),
            'quality_score' => $this->calculate_quality_score($stats, $total),
        ];
    }
    
    /**
     * Get user preference profile
     * 
     * Returns learned preferences for current user
     * 
     * @param int $user_id User ID (default: current user)
     * @return array User preferences
     */
    public function get_user_preferences($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $preferences = get_user_meta($user_id, 'dodo_recommendation_preferences', true);
        
        if (!$preferences) {
            return $this->get_default_preferences();
        }
        
        return $preferences;
    }
    
    /**
     * Get recommendation quality insights
     * 
     * @return array Quality insights for all recommendation types
     */
    public function get_quality_insights() {
        $recommendation_types = [
            self::REC_TITLE_REWRITE,
            self::REC_META_REWRITE,
            self::REC_CONTENT_REFRESH,
            self::REC_FAQ_SUGGESTION,
            self::REC_INTERNAL_LINKS,
            self::REC_SEMANTIC_EXPANSION,
            self::REC_GEO_OPTIMIZATION,
            self::REC_HUMANIZATION,
            self::REC_CTA_PLACEMENT,
            self::REC_KEYWORD_OPTIMIZATION,
        ];
        
        $insights = [
            'by_type' => [],
            'best_performing' => [],
            'worst_performing' => [],
            'needs_improvement' => [],
        ];
        
        foreach ($recommendation_types as $type) {
            $metrics = $this->get_acceptance_rate($type);
            
            if ($metrics['total_samples'] >= self::MIN_SAMPLES) {
                $insights['by_type'][$type] = $metrics;
            }
        }
        
        // Sort by quality score
        uasort($insights['by_type'], function($a, $b) {
            return $b['quality_score'] <=> $a['quality_score'];
        });
        
        // Best performing (top 3)
        $insights['best_performing'] = array_slice($insights['by_type'], 0, 3, true);
        
        // Worst performing (bottom 3)
        $insights['worst_performing'] = array_slice(
            array_reverse($insights['by_type'], true),
            0,
            3,
            true
        );
        
        // Needs improvement (quality score < 50)
        $insights['needs_improvement'] = array_filter($insights['by_type'], function($metrics) {
            return $metrics['quality_score'] < 50;
        });
        
        return $insights;
    }
    
    /**
     * Get modification patterns
     * 
     * Analyzes how users modify recommendations
     * 
     * @param string $recommendation_type Recommendation type
     * @return array Modification patterns
     */
    public function get_modification_patterns($recommendation_type) {
        global $wpdb;
        
        $modifications = $wpdb->get_results($wpdb->prepare(
            "SELECT details 
            FROM {$this->feedback_table}
            WHERE recommendation_type = %s
            AND user_action = 'modified'
            ORDER BY recorded_at DESC
            LIMIT 50",
            $recommendation_type
        ), ARRAY_A);
        
        if (empty($modifications)) {
            return [
                'patterns' => [],
                'common_changes' => [],
                'reasoning' => 'No modification data available',
            ];
        }
        
        $patterns = [
            'shortened' => 0,
            'lengthened' => 0,
            'tone_changed' => 0,
            'keywords_added' => 0,
            'keywords_removed' => 0,
            'structure_changed' => 0,
        ];
        
        $common_changes = [];
        
        foreach ($modifications as $row) {
            $details = json_decode($row['details'], true);
            
            if (!isset($details['original']) || !isset($details['modified'])) {
                continue;
            }
            
            $original = $details['original'];
            $modified = $details['modified'];
            
            // Analyze changes
            $original_length = strlen($original);
            $modified_length = strlen($modified);
            
            if ($modified_length < $original_length * 0.8) {
                $patterns['shortened']++;
            } elseif ($modified_length > $original_length * 1.2) {
                $patterns['lengthened']++;
            }
            
            // Track specific changes
            if (isset($details['change_type'])) {
                $change_type = $details['change_type'];
                
                if (!isset($common_changes[$change_type])) {
                    $common_changes[$change_type] = 0;
                }
                $common_changes[$change_type]++;
            }
        }
        
        // Sort common changes
        arsort($common_changes);
        
        return [
            'patterns' => $patterns,
            'common_changes' => array_slice($common_changes, 0, 5, true),
            'total_modifications' => count($modifications),
            'reasoning' => $this->generate_modification_reasoning($patterns, $common_changes),
        ];
    }
    
    /**
     * Get ignored recommendations
     * 
     * Identifies recommendations users consistently ignore
     * 
     * @return array Ignored recommendation insights
     */
    public function get_ignored_recommendations() {
        global $wpdb;
        
        $ignored = $wpdb->get_results(
            "SELECT 
                recommendation_type,
                COUNT(*) as ignore_count,
                (COUNT(*) * 100.0 / (
                    SELECT COUNT(*) 
                    FROM {$this->feedback_table} f2 
                    WHERE f2.recommendation_type = f1.recommendation_type
                    AND f2.user_action != 'shown'
                )) as ignore_rate
            FROM {$this->feedback_table} f1
            WHERE user_action = 'ignored'
            GROUP BY recommendation_type
            HAVING ignore_count >= " . self::MIN_SAMPLES . "
            ORDER BY ignore_rate DESC",
            ARRAY_A
        );
        
        return [
            'ignored_types' => $ignored,
            'reasoning' => $this->generate_ignore_reasoning($ignored),
        ];
    }
    
    /**
     * Should show recommendation to user?
     * 
     * Decides if recommendation should be shown based on learned preferences
     * 
     * @param string $recommendation_type Recommendation type
     * @param array $context Context
     * @return array Decision with reasoning
     */
    public function should_show_recommendation($recommendation_type, $context = []) {
        $acceptance = $this->get_acceptance_rate($recommendation_type);
        
        // Not enough data - show by default
        if ($acceptance['total_samples'] < self::MIN_SAMPLES) {
            return [
                'show' => true,
                'confidence' => 0,
                'reasoning' => 'Insufficient data - showing to gather feedback',
            ];
        }
        
        // High rejection rate - consider not showing
        if ($acceptance['rejection_rate'] > 70) {
            return [
                'show' => false,
                'confidence' => $acceptance['confidence'],
                'reasoning' => sprintf(
                    'High rejection rate (%.1f%%) - users consistently reject this recommendation',
                    $acceptance['rejection_rate']
                ),
            ];
        }
        
        // High ignore rate - lower priority
        if ($acceptance['ignore_rate'] > 60) {
            return [
                'show' => true,
                'priority' => 'low',
                'confidence' => $acceptance['confidence'],
                'reasoning' => sprintf(
                    'High ignore rate (%.1f%%) - showing with low priority',
                    $acceptance['ignore_rate']
                ),
            ];
        }
        
        // Good acceptance - show with high priority
        if ($acceptance['acceptance_rate'] > 50) {
            return [
                'show' => true,
                'priority' => 'high',
                'confidence' => $acceptance['confidence'],
                'reasoning' => sprintf(
                    'Good acceptance rate (%.1f%%) - users find this valuable',
                    $acceptance['acceptance_rate']
                ),
            ];
        }
        
        // Default - show with medium priority
        return [
            'show' => true,
            'priority' => 'medium',
            'confidence' => $acceptance['confidence'],
            'reasoning' => 'Moderate acceptance - showing with medium priority',
        ];
    }
    
    /**
     * Get personalized recommendations
     * 
     * Returns recommendations tailored to user preferences
     * 
     * @param int $post_id Post ID
     * @param array $available_recommendations Available recommendations
     * @return array Prioritized recommendations
     */
    public function get_personalized_recommendations($post_id, $available_recommendations) {
        $user_preferences = $this->get_user_preferences();
        $prioritized = [];
        
        foreach ($available_recommendations as $rec) {
            $rec_type = $rec['type'] ?? 'unknown';
            
            // Get acceptance rate
            $acceptance = $this->get_acceptance_rate($rec_type);
            
            // Calculate priority score
            $priority_score = 50; // Base score
            
            // Boost for high acceptance
            $priority_score += $acceptance['acceptance_rate'] * 0.5;
            
            // Penalty for high rejection
            $priority_score -= $acceptance['rejection_rate'] * 0.3;
            
            // User preference boost
            if (isset($user_preferences['preferred_types'][$rec_type])) {
                $priority_score += 20;
            }
            
            // User preference penalty
            if (isset($user_preferences['avoided_types'][$rec_type])) {
                $priority_score -= 30;
            }
            
            $rec['priority_score'] = round($priority_score);
            $rec['acceptance_rate'] = $acceptance['acceptance_rate'];
            $rec['confidence'] = $acceptance['confidence'];
            
            $prioritized[] = $rec;
        }
        
        // Sort by priority score
        usort($prioritized, function($a, $b) {
            return $b['priority_score'] <=> $a['priority_score'];
        });
        
        return $prioritized;
    }
    
    /**
     * Update acceptance rates
     */
    private function update_acceptance_rates($recommendation_type, $user_action) {
        $option_key = "dodo_acceptance_rate_{$recommendation_type}";
        $rates = get_option($option_key, [
            'accepted' => 0,
            'rejected' => 0,
            'modified' => 0,
            'ignored' => 0,
            'partial' => 0,
            'total' => 0,
        ]);
        
        if (isset($rates[$user_action])) {
            $rates[$user_action]++;
        }
        $rates['total']++;
        
        update_option($option_key, $rates);
    }
    
    /**
     * Update user preferences
     */
    private function update_user_preferences($recommendation_type, $user_action, $details) {
        $user_id = get_current_user_id();
        $preferences = $this->get_user_preferences($user_id);
        
        // Track preferred types (high acceptance)
        if ($user_action === self::ACTION_ACCEPTED) {
            if (!isset($preferences['preferred_types'][$recommendation_type])) {
                $preferences['preferred_types'][$recommendation_type] = 0;
            }
            $preferences['preferred_types'][$recommendation_type]++;
        }
        
        // Track avoided types (high rejection)
        if ($user_action === self::ACTION_REJECTED) {
            if (!isset($preferences['avoided_types'][$recommendation_type])) {
                $preferences['avoided_types'][$recommendation_type] = 0;
            }
            $preferences['avoided_types'][$recommendation_type]++;
        }
        
        // Track modification patterns
        if ($user_action === self::ACTION_MODIFIED) {
            if (!isset($preferences['modification_patterns'][$recommendation_type])) {
                $preferences['modification_patterns'][$recommendation_type] = [];
            }
            
            if (isset($details['change_type'])) {
                $change_type = $details['change_type'];
                
                if (!isset($preferences['modification_patterns'][$recommendation_type][$change_type])) {
                    $preferences['modification_patterns'][$recommendation_type][$change_type] = 0;
                }
                $preferences['modification_patterns'][$recommendation_type][$change_type]++;
            }
        }
        
        update_user_meta($user_id, 'dodo_recommendation_preferences', $preferences);
    }
    
    /**
     * Calculate confidence based on sample count
     */
    private function calculate_confidence($sample_count) {
        if ($sample_count >= 50) {
            return 90;
        } elseif ($sample_count >= 20) {
            return 75;
        } elseif ($sample_count >= 10) {
            return 60;
        } elseif ($sample_count >= self::MIN_SAMPLES) {
            return 40;
        }
        
        return 20;
    }
    
    /**
     * Calculate quality score
     */
    private function calculate_quality_score($stats, $total) {
        // Quality = (accepted + 0.5 * modified) / total * 100
        $quality = (($stats['accepted'] + ($stats['modified'] * 0.5) + ($stats['partial'] * 0.7)) / $total) * 100;
        
        return round($quality, 1);
    }
    
    /**
     * Get default preferences
     */
    private function get_default_preferences() {
        return [
            'preferred_types' => [],
            'avoided_types' => [],
            'modification_patterns' => [],
        ];
    }
    
    /**
     * Validate user action
     */
    private function is_valid_action($action) {
        return in_array($action, [
            self::ACTION_ACCEPTED,
            self::ACTION_REJECTED,
            self::ACTION_MODIFIED,
            self::ACTION_IGNORED,
            self::ACTION_PARTIAL,
        ]);
    }
    
    /**
     * Get user role
     */
    private function get_user_role() {
        $user = wp_get_current_user();
        return !empty($user->roles) ? $user->roles[0] : 'guest';
    }
    
    /**
     * Get session ID
     */
    private function get_session_id() {
        if (!session_id()) {
            session_start();
        }
        return session_id();
    }
    
    /**
     * Generate modification reasoning
     */
    private function generate_modification_reasoning($patterns, $common_changes) {
        $reasoning = [];
        
        if ($patterns['shortened'] > $patterns['lengthened']) {
            $reasoning[] = 'Users tend to shorten recommendations';
        } elseif ($patterns['lengthened'] > $patterns['shortened']) {
            $reasoning[] = 'Users tend to expand recommendations';
        }
        
        if (!empty($common_changes)) {
            $top_change = array_key_first($common_changes);
            $reasoning[] = "Most common modification: {$top_change}";
        }
        
        return implode('. ', $reasoning);
    }
    
    /**
     * Generate ignore reasoning
     */
    private function generate_ignore_reasoning($ignored) {
        if (empty($ignored)) {
            return 'No consistently ignored recommendations';
        }
        
        $top = $ignored[0];
        
        return sprintf(
            '%s is ignored %.1f%% of the time - consider reducing frequency or improving quality',
            $top['recommendation_type'],
            $top['ignore_rate']
        );
    }
    
    /**
     * Create database table
     */
    public static function create_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dodo_user_feedback';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            recommendation_type varchar(50) NOT NULL,
            user_action varchar(20) NOT NULL,
            details longtext,
            user_id bigint(20) NOT NULL,
            recorded_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY recommendation_type (recommendation_type),
            KEY user_action (user_action),
            KEY user_id (user_id),
            KEY recorded_at (recorded_at)
        ) {$charset_collate};";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        error_log('[DODO Feedback] User feedback table created');
    }
}
