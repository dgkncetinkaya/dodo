<?php
/**
 * Learning Controller
 * 
 * Controls learning system behavior and modes
 * 
 * @package DODO_AI_SEO
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Learning_Controller {
    
    /**
     * Learning modes
     */
    const MODE_OFF = 'off';
    const MODE_OBSERVE = 'observe';
    const MODE_APPLY = 'apply';
    const MODE_SAFE_ROLLOUT = 'safe_rollout';
    
    /**
     * Deployment environments
     */
    const ENV_DEVELOPMENT = 'development';
    const ENV_STAGING = 'staging';
    const ENV_PRODUCTION = 'production';
    
    /**
     * Get learning mode
     */
    public function get_mode() {
        return get_option('dodo_learning_mode', self::MODE_OBSERVE);
    }
    
    /**
     * Set learning mode
     */
    public function set_mode($mode) {
        $allowed = [self::MODE_OFF, self::MODE_OBSERVE, self::MODE_APPLY, self::MODE_SAFE_ROLLOUT];
        
        if (!in_array($mode, $allowed)) {
            return false;
        }
        
        update_option('dodo_learning_mode', $mode);
        
        error_log("[DODO Controller] Learning mode changed to: {$mode}");
        
        return true;
    }
    
    /**
     * Check if learning is enabled
     */
    public function is_enabled() {
        return $this->get_mode() !== self::MODE_OFF;
    }
    
    /**
     * Check if learning should apply changes
     */
    public function should_apply() {
        $mode = $this->get_mode();
        return in_array($mode, [self::MODE_APPLY, self::MODE_SAFE_ROLLOUT]);
    }
    
    /**
     * Check if in observe mode
     */
    public function is_observe_mode() {
        return $this->get_mode() === self::MODE_OBSERVE;
    }
    
    /**
     * Get confidence threshold
     */
    public function get_confidence_threshold() {
        $env = $this->get_environment();
        
        $defaults = [
            self::ENV_DEVELOPMENT => 50,
            self::ENV_STAGING => 60,
            self::ENV_PRODUCTION => 70,
        ];
        
        $default = $defaults[$env] ?? 60;
        
        return (int) get_option('dodo_learning_confidence_threshold', $default);
    }
    
    /**
     * Set confidence threshold
     */
    public function set_confidence_threshold($threshold) {
        $threshold = max(0, min(100, (int) $threshold));
        update_option('dodo_learning_confidence_threshold', $threshold);
        
        error_log("[DODO Controller] Confidence threshold set to: {$threshold}%");
        
        return true;
    }
    
    /**
     * Get environment
     */
    public function get_environment() {
        return get_option('dodo_learning_environment', self::ENV_PRODUCTION);
    }
    
    /**
     * Set environment
     */
    public function set_environment($env) {
        $allowed = [self::ENV_DEVELOPMENT, self::ENV_STAGING, self::ENV_PRODUCTION];
        
        if (!in_array($env, $allowed)) {
            return false;
        }
        
        update_option('dodo_learning_environment', $env);
        
        // Auto-adjust confidence threshold
        $thresholds = [
            self::ENV_DEVELOPMENT => 50,
            self::ENV_STAGING => 60,
            self::ENV_PRODUCTION => 70,
        ];
        
        $this->set_confidence_threshold($thresholds[$env]);
        
        error_log("[DODO Controller] Environment changed to: {$env}");
        
        return true;
    }
    
    /**
     * Emergency freeze
     */
    public function emergency_freeze() {
        update_option('dodo_learning_emergency_freeze', true);
        update_option('dodo_learning_freeze_timestamp', time());
        
        error_log('[DODO Controller] EMERGENCY FREEZE ACTIVATED');
        
        return true;
    }
    
    /**
     * Unfreeze
     */
    public function unfreeze() {
        delete_option('dodo_learning_emergency_freeze');
        delete_option('dodo_learning_freeze_timestamp');
        
        error_log('[DODO Controller] Emergency freeze DEACTIVATED');
        
        return true;
    }
    
    /**
     * Check if frozen
     */
    public function is_frozen() {
        return (bool) get_option('dodo_learning_emergency_freeze', false);
    }
    
    /**
     * Get noisy learning filter status
     */
    public function is_noisy_filter_enabled() {
        return (bool) get_option('dodo_learning_noisy_filter', true);
    }
    
    /**
     * Set noisy learning filter
     */
    public function set_noisy_filter($enabled) {
        update_option('dodo_learning_noisy_filter', (bool) $enabled);
        
        error_log('[DODO Controller] Noisy filter: ' . ($enabled ? 'ENABLED' : 'DISABLED'));
        
        return true;
    }
    
    /**
     * Get adaptive strategy status
     */
    public function is_adaptive_strategy_enabled() {
        return (bool) get_option('dodo_learning_adaptive_strategy', true);
    }
    
    /**
     * Set adaptive strategy
     */
    public function set_adaptive_strategy($enabled) {
        update_option('dodo_learning_adaptive_strategy', (bool) $enabled);
        
        error_log('[DODO Controller] Adaptive strategy: ' . ($enabled ? 'ENABLED' : 'DISABLED'));
        
        return true;
    }
    
    /**
     * Get controller status
     */
    public function get_status() {
        return [
            'mode' => $this->get_mode(),
            'enabled' => $this->is_enabled(),
            'should_apply' => $this->should_apply(),
            'observe_mode' => $this->is_observe_mode(),
            'confidence_threshold' => $this->get_confidence_threshold(),
            'environment' => $this->get_environment(),
            'frozen' => $this->is_frozen(),
            'noisy_filter' => $this->is_noisy_filter_enabled(),
            'adaptive_strategy' => $this->is_adaptive_strategy_enabled(),
        ];
    }
    
    /**
     * Validate learning application
     */
    public function validate_application($learned_strategy) {
        // Check if frozen
        if ($this->is_frozen()) {
            return [
                'allowed' => false,
                'reason' => 'Emergency freeze active',
            ];
        }
        
        // Check if mode allows application
        if (!$this->should_apply()) {
            return [
                'allowed' => false,
                'reason' => 'Learning mode does not allow application',
            ];
        }
        
        // Check confidence threshold
        $confidence = $learned_strategy['confidence'] ?? 0;
        $threshold = $this->get_confidence_threshold();
        
        if ($confidence < $threshold) {
            return [
                'allowed' => false,
                'reason' => sprintf('Confidence %d%% below threshold %d%%', $confidence, $threshold),
            ];
        }
        
        // Safe rollout mode - only low-risk adaptations
        if ($this->get_mode() === self::MODE_SAFE_ROLLOUT) {
            if (!$this->is_low_risk_adaptation($learned_strategy)) {
                return [
                    'allowed' => false,
                    'reason' => 'Safe rollout mode - only low-risk adaptations allowed',
                ];
            }
        }
        
        return [
            'allowed' => true,
            'reason' => 'All validation checks passed',
        ];
    }
    
    /**
     * Check if adaptation is low-risk
     */
    private function is_low_risk_adaptation($learned_strategy) {
        // Low-risk: changes < 15%
        $changes = $learned_strategy['changes'] ?? [];
        
        foreach ($changes as $change) {
            if (isset($change['change_pct']) && $change['change_pct'] > 15) {
                return false;
            }
        }
        
        return true;
    }
}
