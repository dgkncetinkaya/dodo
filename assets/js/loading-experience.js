/**
 * Loading Experience V2 (Sprint 1B-2)
 * 
 * @package DODO_AI_SEO
 * @since 1.1.3
 */

(function($) {
    'use strict';
    
    window.DodoLoading = {
        
        /**
         * Show skeleton loading
         */
        showSkeleton: function(container, count = 3) {
            const $container = $(container);
            $container.empty();
            
            for (let i = 0; i < count; i++) {
                const skeletonHtml = `
                    <div class="dodo-skeleton-section">
                        <div class="dodo-skeleton-header">
                            <div class="dodo-skeleton dodo-skeleton-title"></div>
                            <div class="dodo-skeleton dodo-skeleton-badge"></div>
                        </div>
                        <div class="dodo-skeleton-meta">
                            <div class="dodo-skeleton dodo-skeleton-meta-item"></div>
                            <div class="dodo-skeleton dodo-skeleton-meta-item"></div>
                        </div>
                        <div class="dodo-skeleton dodo-skeleton-preview"></div>
                        <div class="dodo-skeleton-actions">
                            <div class="dodo-skeleton dodo-skeleton-button"></div>
                            <div class="dodo-skeleton dodo-skeleton-button"></div>
                        </div>
                    </div>
                `;
                $container.append(skeletonHtml);
            }
        },
        
        /**
         * Hide skeleton loading
         */
        hideSkeleton: function(container) {
            $(container).find('.dodo-skeleton-section').fadeOut(300, function() {
                $(this).remove();
            });
        },
        
        /**
         * Show staged loading
         */
        showStaged: function(container, stages) {
            const $container = $(container);
            $container.empty();
            
            // Create stage elements
            stages.forEach(function(stage, index) {
                const stageHtml = `
                    <div class="dodo-loading-stage" data-stage="${index}" data-stage-name="${stage.name || ''}">
                        <div class="dodo-loading-stage-icon">${stage.icon}</div>
                        <div class="dodo-loading-stage-title">${stage.title}</div>
                        <div class="dodo-loading-stage-text">${stage.text}</div>
                    </div>
                `;
                $container.append(stageHtml);
            });
            
            // Show first stage
            $container.find('.dodo-loading-stage[data-stage="0"]').addClass('active');
            
            // NO FAKE AUTO-PROGRESS - stages are now controlled by real backend events
            // Use updateStage() method to progress stages based on actual backend progress
        },
        
        /**
         * Update stage (called by backend events)
         */
        updateStage: function(container, stageName) {
            const $container = $(container);
            const $targetStage = $container.find('.dodo-loading-stage[data-stage-name="' + stageName + '"]');
            
            if ($targetStage.length === 0) return;
            
            // Hide all stages
            $container.find('.dodo-loading-stage').removeClass('active');
            
            // Show target stage
            $targetStage.addClass('active');
            
            // Mark previous stages as completed
            $targetStage.prevAll('.dodo-loading-stage').addClass('completed');
        },
        
        /**
         * Hide staged loading
         */
        hideStaged: function(container) {
            const $container = $(container);
            
            // Fade out
            $container.find('.dodo-loading-stage').fadeOut(300, function() {
                $(this).remove();
            });
        },
        
        /**
         * Show premium loader
         */
        showPremium: function(container, text = 'Yükleniyor...') {
            const $container = $(container);
            
            const loaderHtml = `
                <div class="dodo-premium-loader">
                    <div class="dodo-loader-dots">
                        <div class="dodo-loader-dot"></div>
                        <div class="dodo-loader-dot"></div>
                        <div class="dodo-loader-dot"></div>
                    </div>
                    <div class="dodo-loader-text">${text}</div>
                </div>
            `;
            
            $container.html(loaderHtml);
        },
        
        /**
         * Hide premium loader
         */
        hidePremium: function(container) {
            $(container).find('.dodo-premium-loader').fadeOut(300, function() {
                $(this).remove();
            });
        },
        
        /**
         * Show analyze loading with stages
         */
        showAnalyzeLoading: function() {
            const stages = [
                {
                    icon: '🔍',
                    title: 'İçerik Taranıyor',
                    text: 'Yazınız analiz ediliyor ve bölümlere ayrılıyor...'
                },
                {
                    icon: '🧠',
                    title: 'AI Analiz Yapıyor',
                    text: 'Her bölüm için iyileştirme fırsatları tespit ediliyor...'
                },
                {
                    icon: '✨',
                    title: 'Sonuçlar Hazırlanıyor',
                    text: 'İyileştirme önerileri oluşturuluyor...'
                }
            ];
            
            this.showStaged('#dodo-detected-sections', stages);
            $('.dodo-analysis-results').fadeIn();
        },
        
        /**
         * Show improve loading with stages
         */
        showImproveLoading: function() {
            const stages = [
                {
                    name: 'analyze',
                    icon: '📝',
                    title: 'İçerik Analiz Ediliyor',
                    text: 'Mevcut içerik yapısı inceleniyor...'
                },
                {
                    name: 'strategy',
                    icon: '🎯',
                    title: 'Strateji Oluşturuluyor',
                    text: 'En uygun düzenleme stratejisi belirleniyor...'
                },
                {
                    name: 'improve',
                    icon: '✍️',
                    title: 'AI İyileştirme Yapıyor',
                    text: 'İçerik SEO ve okunabilirlik için optimize ediliyor...'
                },
                {
                    name: 'validate',
                    icon: '🔒',
                    title: 'Doğrulama Yapılıyor',
                    text: 'Semantic validation ve güvenlik kontrolleri...'
                }
            ];
            
            return stages;
        }
    };
    
})(jQuery);
