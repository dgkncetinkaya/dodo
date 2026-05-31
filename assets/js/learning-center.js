/**
 * Learning Center Dashboard JavaScript
 * 
 * Handles AJAX interactions for learning insights
 * 
 * @package DODO_AI_SEO
 * @since 3.0.0
 */

(function($) {
    'use strict';
    
    const LearningCenter = {
        
        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
            this.autoRefresh();
        },
        
        /**
         * Bind events
         */
        bindEvents: function() {
            // Reset learning button
            $(document).on('click', '.reset-learning-btn', this.resetLearning.bind(this));
            
            // Rollback evolution button
            $(document).on('click', '.rollback-evolution-btn', this.rollbackEvolution.bind(this));
            
            // Refresh insights button
            $(document).on('click', '.refresh-insights-btn', this.refreshInsights.bind(this));
        },
        
        /**
         * Reset learning
         */
        resetLearning: function(e) {
            e.preventDefault();
            
            const actionType = $(e.currentTarget).data('action-type') || null;
            const confirmMsg = actionType 
                ? `"${actionType}" için tüm öğrenme verilerini sıfırlamak istediğinizden emin misiniz?`
                : 'TÜM öğrenme verilerini sıfırlamak istediğinizden emin misiniz? Bu işlem geri alınamaz!';
            
            if (!confirm(confirmMsg)) {
                return;
            }
            
            $.ajax({
                url: dodoLearning.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'dodo_reset_learning',
                    nonce: dodoLearning.nonce,
                    action_type: actionType
                },
                beforeSend: function() {
                    $(e.currentTarget).prop('disabled', true).text('Sıfırlanıyor...');
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert('Hata: ' + response.data.message);
                    }
                },
                error: function() {
                    alert('Bir hata oluştu. Lütfen tekrar deneyin.');
                },
                complete: function() {
                    $(e.currentTarget).prop('disabled', false).text('Sıfırla');
                }
            });
        },
        
        /**
         * Rollback evolution
         */
        rollbackEvolution: function(e) {
            e.preventDefault();
            
            const strategyId = $(e.currentTarget).data('strategy-id');
            
            if (!strategyId) {
                alert('Geçersiz strateji ID');
                return;
            }
            
            if (!confirm('Bu strateji evrimini geri almak istediğinizden emin misiniz?')) {
                return;
            }
            
            $.ajax({
                url: dodoLearning.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'dodo_rollback_evolution',
                    nonce: dodoLearning.nonce,
                    strategy_id: strategyId
                },
                beforeSend: function() {
                    $(e.currentTarget).prop('disabled', true).text('Geri alınıyor...');
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert('Hata: ' + response.data.message);
                    }
                },
                error: function() {
                    alert('Bir hata oluştu. Lütfen tekrar deneyin.');
                },
                complete: function() {
                    $(e.currentTarget).prop('disabled', false).text('Geri Al');
                }
            });
        },
        
        /**
         * Refresh insights
         */
        refreshInsights: function(e) {
            e.preventDefault();
            
            $.ajax({
                url: dodoLearning.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'dodo_get_learning_insights',
                    nonce: dodoLearning.nonce
                },
                beforeSend: function() {
                    $(e.currentTarget).prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Yenileniyor...');
                },
                success: function(response) {
                    if (response.success) {
                        // Reload page to show updated data
                        location.reload();
                    } else {
                        alert('Hata: ' + response.data.message);
                    }
                },
                error: function() {
                    alert('Bir hata oluştu. Lütfen tekrar deneyin.');
                },
                complete: function() {
                    $(e.currentTarget).prop('disabled', false).html('<span class="dashicons dashicons-update"></span> Yenile');
                }
            });
        },
        
        /**
         * Auto refresh every 5 minutes
         */
        autoRefresh: function() {
            // Auto refresh every 5 minutes (300000ms)
            setInterval(function() {
                console.log('[Learning Center] Auto-refreshing insights...');
                location.reload();
            }, 300000);
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        LearningCenter.init();
    });
    
})(jQuery);
