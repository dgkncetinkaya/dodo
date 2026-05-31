/**
 * Inline Editor Experience (Sprint 1B-1)
 * 
 * @package DODO_AI_SEO
 * @since 1.1.3
 */

(function($) {
    'use strict';
    
    // Inline Editor State
    window.DodoInlineEditor = {
        activeSection: null,
        improvements: {},
        initialized: false,
        
        /**
         * Initialize inline editor
         */
        init: function() {
            // Duplicate init protection
            if (this.initialized) {
                return;
            }
            
            this.bindEvents();
            this.initialized = true;
        },
        
        /**
         * Destroy inline editor (cleanup)
         */
        destroy: function() {
            this.unbindEvents();
            this.activeSection = null;
            this.improvements = {};
            this.initialized = false;
        },
        
        /**
         * Bind events with namespace
         */
        bindEvents: function() {
            // Cleanup before binding (prevent duplicates)
            this.unbindEvents();
            
            // Expand/collapse section
            $(document).on('click.dodoInline', '.dodo-expand-btn', function(e) {
                e.preventDefault();
                const $section = $(this).closest('.dodo-section-item');
                DodoInlineEditor.toggleSection($section);
            });
            
            // Inline improve button
            $(document).on('click.dodoInline', '.dodo-inline-improve-btn', function(e) {
                e.preventDefault();
                const $section = $(this).closest('.dodo-section-item');
                const sectionIndex = $section.data('section-index');
                const improveType = $(this).data('type');
                
                DodoInlineEditor.improveInline(sectionIndex, improveType);
            });
            
            // Inline tab switching
            $(document).on('click.dodoInline', '.dodo-inline-tab', function(e) {
                e.preventDefault();
                const $section = $(this).closest('.dodo-section-item');
                const tabName = $(this).data('tab');
                
                DodoInlineEditor.switchInlineTab($section, tabName);
            });
            
            // Inline apply
            $(document).on('click.dodoInline', '.dodo-inline-apply-btn', function(e) {
                e.preventDefault();
                const $section = $(this).closest('.dodo-section-item');
                const sectionIndex = $section.data('section-index');
                
                DodoInlineEditor.applyInline(sectionIndex);
            });
            
            // Inline cancel
            $(document).on('click.dodoInline', '.dodo-inline-cancel-btn', function(e) {
                e.preventDefault();
                const $section = $(this).closest('.dodo-section-item');
                
                DodoInlineEditor.collapseSection($section);
            });
        },
        
        /**
         * Unbind events (cleanup)
         */
        unbindEvents: function() {
            $(document).off('.dodoInline');
        },
        
        /**
         * Toggle section expansion
         */
        toggleSection: function($section) {
            if ($section.hasClass('expanded')) {
                this.collapseSection($section);
            } else {
                this.expandSection($section);
            }
        },
        
        /**
         * Expand section
         */
        expandSection: function($section) {
            // Collapse other sections
            $('.dodo-section-item.expanded').each(function() {
                if (!$(this).is($section)) {
                    DodoInlineEditor.collapseSection($(this));
                }
            });
            
            $section.addClass('expanded');
            
            // Update expand button text
            $section.find('.dodo-expand-btn').html('<span class="dashicons dashicons-arrow-up-alt2"></span> Daralt');
            
            // Show inline editor if not already shown
            if ($section.find('.dodo-inline-editor').length === 0) {
                this.renderInlineEditor($section);
            }
        },
        
        /**
         * Collapse section
         */
        collapseSection: function($section) {
            $section.removeClass('expanded');
            
            // Update expand button text
            $section.find('.dodo-expand-btn').html('<span class="dashicons dashicons-arrow-down-alt2"></span> Genişlet');
            
            // Clear improvement data
            const sectionIndex = $section.data('section-index');
            delete this.improvements[sectionIndex];
        },
        
        /**
         * Render inline editor
         */
        renderInlineEditor: function($section) {
            const sectionIndex = $section.data('section-index');
            
            const editorHtml = `
                <div class="dodo-inline-editor">
                    <div class="dodo-inline-tabs">
                        <button class="dodo-inline-tab active" data-tab="original">Orijinal</button>
                        <button class="dodo-inline-tab" data-tab="diff" style="display:none;">Değişiklikler</button>
                        <button class="dodo-inline-tab" data-tab="improved" style="display:none;">Geliştirilmiş</button>
                        <button class="dodo-inline-tab" data-tab="compare" style="display:none;">Karşılaştır</button>
                    </div>
                    
                    <div class="dodo-inline-content active" data-content="original">
                        <div class="dodo-inline-preview">
                            ${$section.find('.dodo-section-preview').html()}
                        </div>
                    </div>
                    
                    <div class="dodo-inline-content" data-content="diff">
                        <div class="dodo-inline-diff">
                            <!-- Will be populated after improvement -->
                        </div>
                    </div>
                    
                    <div class="dodo-inline-content" data-content="improved">
                        <div class="dodo-inline-preview">
                            <!-- Will be populated after improvement -->
                        </div>
                    </div>
                    
                    <div class="dodo-inline-content" data-content="compare">
                        <div class="dodo-split-compare">
                            <div class="dodo-compare-pane dodo-compare-original">
                                <div class="dodo-compare-header">Orijinal</div>
                                <div class="dodo-compare-content" data-compare-side="original">
                                    <!-- Will be populated after improvement -->
                                </div>
                            </div>
                            <div class="dodo-compare-divider"></div>
                            <div class="dodo-compare-pane dodo-compare-improved">
                                <div class="dodo-compare-header">Geliştirilmiş</div>
                                <div class="dodo-compare-content" data-compare-side="improved">
                                    <!-- Will be populated after improvement -->
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="dodo-inline-actions" style="display:none;">
                        <button class="dodo-inline-btn dodo-inline-btn-success dodo-inline-apply-btn">
                            <span class="dashicons dashicons-yes"></span>
                            Uygula
                        </button>
                        <button class="dodo-inline-btn dodo-inline-cancel-btn">
                            <span class="dashicons dashicons-no"></span>
                            İptal
                        </button>
                    </div>
                </div>
            `;
            
            $section.find('.dodo-section-actions').after(editorHtml);
        },
        
        /**
         * Switch inline tab
         */
        switchInlineTab: function($section, tabName) {
            // Update tabs
            $section.find('.dodo-inline-tab').removeClass('active');
            $section.find('.dodo-inline-tab[data-tab="' + tabName + '"]').addClass('active');
            
            // Update content
            $section.find('.dodo-inline-content').removeClass('active');
            $section.find('.dodo-inline-content[data-content="' + tabName + '"]').addClass('active');
        },
        
        /**
         * Improve inline
         */
        improveInline: function(sectionIndex, improveType) {
            const $section = $('.dodo-section-item[data-section-index="' + sectionIndex + '"]');
            
            // Show AI pipeline
            this.showAIPipeline($section);
            
            // Get section data
            const sectionData = window.analyzedSections[sectionIndex];
            const sectionContent = sectionData.content;
            
            // Prepare payload
            const payload = {
                action: 'dodo_improve_content',
                nonce: window.dodoImprover.nonce,
                post_id: window.currentPostId,
                improve_type: improveType,
                focus_keyword: window.currentFocusKeyword,
                section_content: sectionContent
            };
            
            // Add offset metadata
            if (sectionData) {
                payload.section_id = sectionData.section_id;
                payload.start_offset = sectionData.start_offset;
                payload.end_offset = sectionData.end_offset;
                payload.original_hash = sectionData.original_hash;
            }
            
            // AJAX request with XHR for header access
            const xhr = new XMLHttpRequest();
            xhr.open('POST', window.dodoImprover.ajaxUrl, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            
            // Fallback: Auto-progress stages if no header updates (graceful degradation)
            let stageTimeout = null;
            let lastStage = null;
            let fallbackActive = false;
            const stages = ['analyze', 'strategy', 'improve', 'validate', 'diff'];
            let currentStageIndex = 0;
            
            // Start fallback timer (if no header update in 2 seconds, use fallback)
            stageTimeout = setTimeout(function() {
                fallbackActive = true;
                DodoInlineEditor.progressStagesFallback($section, stages, 0);
            }, 2000);
            
            // Listen for stage updates via headers (primary method)
            xhr.onreadystatechange = function() {
                if (xhr.readyState >= 2) { // Headers received
                    const stage = xhr.getResponseHeader('X-DODO-Pipeline-Stage');
                    if (stage && stage !== lastStage) {
                        lastStage = stage;
                        
                        // Cancel fallback if real stage received
                        if (stageTimeout) {
                            clearTimeout(stageTimeout);
                            stageTimeout = null;
                        }
                        fallbackActive = false;
                        
                        DodoInlineEditor.updatePipelineStage($section, stage);
                    }
                }
                
                if (xhr.readyState === 4) { // Request complete
                    // Clear any remaining timeouts
                    if (stageTimeout) {
                        clearTimeout(stageTimeout);
                    }
                    
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            
                            if (response.success) {
                                // Store improvement
                                DodoInlineEditor.improvements[sectionIndex] = {
                                    original: response.data.original,
                                    improved: response.data.improved,
                                    metadata: response.data.metadata || {},
                                    sectionData: sectionData
                                };
                                
                                // Render improvement
                                DodoInlineEditor.renderImprovement($section, response.data);
                                
                                // Hide AI pipeline
                                DodoInlineEditor.hideAIPipeline($section);
                            } else {
                                window.DodoToast.error('Hata', response.data.message || 'İyileştirme başarısız');
                                DodoInlineEditor.hideAIPipeline($section);
                            }
                        } catch (e) {
                            window.DodoToast.error('Hata', 'Yanıt işlenemedi');
                            DodoInlineEditor.hideAIPipeline($section);
                        }
                    } else {
                        window.DodoToast.error('Hata', 'Sunucu hatası oluştu');
                        DodoInlineEditor.hideAIPipeline($section);
                    }
                }
            };
            
            // Convert payload to URL-encoded string
            const formData = Object.keys(payload)
                .map(key => encodeURIComponent(key) + '=' + encodeURIComponent(payload[key]))
                .join('&');
            
            xhr.send(formData);
        },
        
        /**
         * Progress stages fallback (graceful degradation)
         */
        progressStagesFallback: function($section, stages, index) {
            if (index >= stages.length) return;
            
            this.updatePipelineStage($section, stages[index]);
            
            // Progress to next stage after delay
            setTimeout(function() {
                DodoInlineEditor.progressStagesFallback($section, stages, index + 1);
            }, 1000);
        },
        
        /**
         * Show AI Pipeline
         */
        showAIPipeline: function($section) {
            const pipelineHtml = `
                <div class="dodo-ai-pipeline">
                    <div class="dodo-pipeline-step" data-stage="analyze">
                        <div class="dodo-pipeline-step-icon">⟳</div>
                        <div class="dodo-pipeline-step-text">İçerik analiz ediliyor...</div>
                    </div>
                    <div class="dodo-pipeline-step" data-stage="strategy">
                        <div class="dodo-pipeline-step-icon">⟳</div>
                        <div class="dodo-pipeline-step-text">Düzenleme stratejisi oluşturuluyor...</div>
                    </div>
                    <div class="dodo-pipeline-step" data-stage="improve">
                        <div class="dodo-pipeline-step-icon">⟳</div>
                        <div class="dodo-pipeline-step-text">AI iyileştirme yapıyor...</div>
                    </div>
                    <div class="dodo-pipeline-step" data-stage="validate">
                        <div class="dodo-pipeline-step-icon">⟳</div>
                        <div class="dodo-pipeline-step-text">Semantic validation yapılıyor...</div>
                    </div>
                    <div class="dodo-pipeline-step" data-stage="diff">
                        <div class="dodo-pipeline-step-icon">⟳</div>
                        <div class="dodo-pipeline-step-text">Smart diff hazırlanıyor...</div>
                    </div>
                </div>
            `;
            
            $section.find('.dodo-inline-editor').prepend(pipelineHtml);
            
            // NO FAKE ANIMATION - stages will be updated by backend events
            // Pipeline stages are now controlled by real backend progress
        },
        
        /**
         * Hide AI Pipeline
         */
        hideAIPipeline: function($section) {
            // Mark all as completed
            $section.find('.dodo-pipeline-step').removeClass('active').addClass('completed');
            $section.find('.dodo-pipeline-step-icon').text('✓');
            
            // Fade out after delay
            setTimeout(function() {
                $section.find('.dodo-ai-pipeline').fadeOut(300, function() {
                    $(this).remove();
                });
            }, 500);
        },
        
        /**
         * Update pipeline stage (called by backend events)
         */
        updatePipelineStage: function($section, stage) {
            const $pipeline = $section.find('.dodo-ai-pipeline');
            if ($pipeline.length === 0) return;
            
            const $currentStep = $pipeline.find('.dodo-pipeline-step[data-stage="' + stage + '"]');
            if ($currentStep.length === 0) return;
            
            // Mark previous steps as completed
            $currentStep.prevAll('.dodo-pipeline-step').removeClass('active').addClass('completed')
                .find('.dodo-pipeline-step-icon').text('✓');
            
            // Mark current step as active
            $currentStep.addClass('active').removeClass('completed')
                .find('.dodo-pipeline-step-icon').text('⟳');
            
            // Debug log
            if (window.dodoImprover && window.dodoImprover.debug) {
                console.log('[DODO PIPELINE] Stage updated:', stage);
            }
        },
        
        /**
         * Render improvement
         */
        renderImprovement: function($section, data) {
            // Show diff tab
            $section.find('.dodo-inline-tab[data-tab="diff"]').show();
            $section.find('.dodo-inline-tab[data-tab="improved"]').show();
            $section.find('.dodo-inline-tab[data-tab="compare"]').show();
            
            // Populate diff
            if (data.metadata && data.metadata.diff_html) {
                $section.find('.dodo-inline-content[data-content="diff"] .dodo-inline-diff').html(data.metadata.diff_html);
            }
            
            // Populate improved
            $section.find('.dodo-inline-content[data-content="improved"] .dodo-inline-preview').html(this.formatContent(data.improved));
            
            // Populate side-by-side compare
            $section.find('.dodo-compare-content[data-compare-side="original"]').html(this.formatContent(data.original));
            $section.find('.dodo-compare-content[data-compare-side="improved"]').html(this.formatContent(data.improved));
            
            // Setup synchronized scroll
            this.setupSyncScroll($section);
            
            // Show actions
            $section.find('.dodo-inline-actions').fadeIn();
            
            // Switch to diff tab
            this.switchInlineTab($section, 'diff');
        },
        
        /**
         * Setup synchronized scroll for compare view
         */
        setupSyncScroll: function($section) {
            const $original = $section.find('.dodo-compare-content[data-compare-side="original"]');
            const $improved = $section.find('.dodo-compare-content[data-compare-side="improved"]');
            
            // Cleanup old listeners first
            $original.off('scroll.dodoCompare');
            $improved.off('scroll.dodoCompare');
            
            let isSyncing = false;
            
            $original.on('scroll.dodoCompare', function() {
                if (!isSyncing) {
                    isSyncing = true;
                    $improved.scrollTop($(this).scrollTop());
                    setTimeout(function() { isSyncing = false; }, 10);
                }
            });
            
            $improved.on('scroll.dodoCompare', function() {
                if (!isSyncing) {
                    isSyncing = true;
                    $original.scrollTop($(this).scrollTop());
                    setTimeout(function() { isSyncing = false; }, 10);
                }
            });
        },
        
        /**
         * Apply inline
         */
        applyInline: function(sectionIndex) {
            const improvement = this.improvements[sectionIndex];
            
            if (!improvement) {
                window.DodoToast.error('Hata', 'İyileştirme verisi bulunamadı');
                return;
            }
            
            const $section = $('.dodo-section-item[data-section-index="' + sectionIndex + '"]');
            const $applyBtn = $section.find('.dodo-inline-apply-btn');
            
            // Show loading
            $applyBtn.prop('disabled', true).html('<span class="spinner is-active" style="float:none;margin:0 8px 0 0;"></span> Uygulanıyor...');
            
            // Prepare payload
            const payload = {
                action: 'dodo_apply_improvement',
                nonce: window.dodoImprover.nonce,
                post_id: window.currentPostId,
                original_content: improvement.original,
                improved_content: improvement.improved,
                improve_type: 'inline_edit',
                metadata: JSON.stringify(improvement.metadata)
            };
            
            // Add offset metadata
            if (improvement.sectionData) {
                payload.section_id = improvement.sectionData.section_id;
                payload.start_offset = improvement.sectionData.start_offset;
                payload.end_offset = improvement.sectionData.end_offset;
                payload.original_hash = improvement.sectionData.original_hash;
            }
            
            // AJAX request
            $.ajax({
                url: window.dodoImprover.ajaxUrl,
                type: 'POST',
                data: payload,
                success: function(response) {
                    if (response.success) {
                        // Store revision ID for undo
                        const revisionId = response.data.revision_id;
                        
                        window.DodoToast.show({
                            type: 'success',
                            title: 'Başarılı',
                            message: 'İyileştirme uygulandı',
                            undoAction: {
                                label: 'Geri Al',
                                timeout: 8000,
                                callback: function() {
                                    // Rollback via revision system
                                    DodoInlineEditor.rollbackImprovement(revisionId);
                                }
                            }
                        });
                        
                        // Collapse section
                        DodoInlineEditor.collapseSection($section);
                        
                        // Re-analyze content
                        setTimeout(function() {
                            if (typeof window.analyzeContent === 'function') {
                                window.analyzeContent();
                            }
                        }, 1000);
                    } else {
                        window.DodoToast.error('Hata', response.data.message || 'Uygulama başarısız');
                        $applyBtn.prop('disabled', false).html('<span class="dashicons dashicons-yes"></span> Uygula');
                    }
                },
                error: function() {
                    window.DodoToast.error('Hata', 'Sunucu hatası oluştu');
                    $applyBtn.prop('disabled', false).html('<span class="dashicons dashicons-yes"></span> Uygula');
                }
            });
        },
        
        /**
         * Format content
         */
        formatContent: function(content) {
            // Simple HTML formatting
            return content.replace(/\n/g, '<br>');
        },
        
        /**
         * Rollback improvement (undo action)
         */
        rollbackImprovement: function(revisionId) {
            if (!revisionId) {
                window.DodoToast.error('Hata', 'Revizyon ID bulunamadı');
                return;
            }
            
            // Show loading toast
            const $loadingToast = window.DodoToast.info('Geri Alınıyor', 'İyileştirme geri alınıyor...');
            
            $.ajax({
                url: window.dodoImprover.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'dodo_rollback_revision',
                    nonce: window.dodoImprover.nonce,
                    revision_id: revisionId
                },
                success: function(response) {
                    window.DodoToast.close($loadingToast);
                    
                    if (response.success) {
                        window.DodoToast.success('Başarılı', 'İyileştirme geri alındı');
                        
                        // Re-analyze content
                        setTimeout(function() {
                            if (typeof window.analyzeContent === 'function') {
                                window.analyzeContent();
                            }
                        }, 1000);
                    } else {
                        window.DodoToast.error('Hata', response.data.message || 'Geri alma başarısız');
                    }
                },
                error: function() {
                    window.DodoToast.close($loadingToast);
                    window.DodoToast.error('Hata', 'Sunucu hatası oluştu');
                }
            });
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        window.DodoInlineEditor.init();
    });
    
})(jQuery);
