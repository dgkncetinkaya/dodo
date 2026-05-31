/**
 * Content Improver Admin Page JavaScript
 * 
 * @package DODO_AI_SEO
 * @since 1.5.0
 */

(function($) {
    'use strict';
    
    // Global state - accessible from inline-editor.js
    window.currentPostId = null;
    window.currentFocusKeyword = '';
    window.analyzedSections = []; // Store section metadata with offsets
    
    let currentImprovement = {
        original: '',
        improved: '',
        type: '',
        sectionIndex: null,
        sectionData: null // Store full section metadata
    };
    
    /**
     * Toast Notification System
     */
    window.DodoToast = {
        container: null,
        undoCallbacks: {}, // Store undo callbacks
        undoTimers: {}, // Store undo timers
        
        init: function() {
            if (!this.container) {
                this.container = $('<div class="dodo-toast-container"></div>');
                $('body').append(this.container);
            }
        },
        
        show: function(options) {
            this.init();
            
            const defaults = {
                type: 'success', // success, error, warning, info
                title: '',
                message: '',
                meta: null, // {label: value} object
                duration: 4000,
                closable: true,
                undoAction: null, // {label: 'Geri Al', callback: function, timeout: 8000}
            };
            
            const opts = $.extend({}, defaults, options);
            const toastId = 'toast-' + Date.now();
            
            // Icon mapping
            const icons = {
                success: '✓',
                error: '✕',
                warning: '⚠',
                info: 'ℹ'
            };
            
            // Build toast HTML
            let html = '<div class="dodo-toast dodo-toast-' + opts.type + '" data-toast-id="' + toastId + '">';
            html += '<div class="dodo-toast-header">';
            html += '<span class="dodo-toast-icon">' + icons[opts.type] + '</span>';
            html += '<span class="dodo-toast-title">' + opts.title + '</span>';
            if (opts.closable) {
                html += '<button class="dodo-toast-close" type="button" aria-label="Close">×</button>';
            }
            html += '</div>';
            
            if (opts.message) {
                html += '<div class="dodo-toast-body">' + opts.message + '</div>';
            }
            
            if (opts.meta) {
                html += '<div class="dodo-toast-meta">';
                for (let key in opts.meta) {
                    html += '<div class="dodo-toast-meta-item">';
                    html += '<span class="dodo-toast-meta-label">' + key + ':</span>';
                    html += '<span>' + opts.meta[key] + '</span>';
                    html += '</div>';
                }
                html += '</div>';
            }
            
            // Add undo action if provided
            if (opts.undoAction) {
                const undoTimeout = opts.undoAction.timeout || 8000;
                const undoLabel = opts.undoAction.label || 'Geri Al';
                
                html += '<div class="dodo-toast-undo">';
                html += '<button class="dodo-toast-undo-btn" type="button" data-toast-id="' + toastId + '">';
                html += '↩ ' + undoLabel + ' (<span class="dodo-undo-countdown">' + Math.ceil(undoTimeout / 1000) + '</span>s)';
                html += '</button>';
                html += '</div>';
                
                // Store undo callback
                this.undoCallbacks[toastId] = opts.undoAction.callback;
                
                // Start countdown
                this.startUndoCountdown(toastId, undoTimeout);
            }
            
            html += '</div>';
            
            const $toast = $(html);
            this.container.append($toast);
            
            // Close button
            $toast.find('.dodo-toast-close').on('click', function() {
                DodoToast.close($toast);
            });
            
            // Undo button
            $toast.find('.dodo-toast-undo-btn').on('click', function() {
                const toastId = $(this).data('toast-id');
                DodoToast.executeUndo(toastId);
            });
            
            // Auto close
            if (opts.duration > 0 && !opts.undoAction) {
                setTimeout(function() {
                    DodoToast.close($toast);
                }, opts.duration);
            }
            
            return $toast;
        },
        
        startUndoCountdown: function(toastId, timeout) {
            const $toast = $('[data-toast-id="' + toastId + '"]');
            const $countdown = $toast.find('.dodo-undo-countdown');
            let remaining = Math.ceil(timeout / 1000);
            
            const timer = setInterval(function() {
                remaining--;
                $countdown.text(remaining);
                
                if (remaining <= 0) {
                    clearInterval(timer);
                    delete DodoToast.undoCallbacks[toastId];
                    delete DodoToast.undoTimers[toastId];
                    DodoToast.close($toast);
                }
            }, 1000);
            
            this.undoTimers[toastId] = timer;
        },
        
        executeUndo: function(toastId) {
            const callback = this.undoCallbacks[toastId];
            
            if (callback && typeof callback === 'function') {
                // Clear timer
                if (this.undoTimers[toastId]) {
                    clearInterval(this.undoTimers[toastId]);
                    delete this.undoTimers[toastId];
                }
                
                // Execute callback
                callback();
                
                // Close toast
                const $toast = $('[data-toast-id="' + toastId + '"]');
                this.close($toast);
                
                // Clean up
                delete this.undoCallbacks[toastId];
            }
        },
        
        close: function($toast) {
            const toastId = $toast.data('toast-id');
            
            // Clean up timers and callbacks (defensive)
            if (this.undoTimers[toastId]) {
                clearInterval(this.undoTimers[toastId]);
                delete this.undoTimers[toastId];
            }
            if (this.undoCallbacks[toastId]) {
                delete this.undoCallbacks[toastId];
            }
            
            $toast.addClass('closing');
            setTimeout(function() {
                $toast.remove();
            }, 300);
        },
        
        // Cleanup all timers (defensive)
        cleanup: function() {
            for (let toastId in this.undoTimers) {
                clearInterval(this.undoTimers[toastId]);
            }
            this.undoTimers = {};
            this.undoCallbacks = {};
        },
        
        success: function(title, message, meta) {
            return this.show({type: 'success', title: title, message: message, meta: meta});
        },
        
        error: function(title, message, meta) {
            return this.show({type: 'error', title: title, message: message, meta: meta, duration: 6000});
        },
        
        warning: function(title, message, meta) {
            return this.show({type: 'warning', title: title, message: message, meta: meta, duration: 5000});
        },
        
        info: function(title, message, meta) {
            return this.show({type: 'info', title: title, message: message, meta: meta});
        }
    };
    
    $(document).ready(function() {
        
        // Show empty state on page load
        showEmptyState();
        
        // Cleanup previous bindings (prevent duplicates)
        $(document).off('.dodoImprover');
        $('#dodo-post-select').off('.dodoImprover');
        $('#dodo-analyze-content').off('.dodoImprover');
        $('.dodo-modal-close, .dodo-modal-cancel').off('.dodoImprover');
        $('.dodo-modal-overlay').off('.dodoImprover');
        $('.dodo-modal-apply').off('.dodoImprover');
        
        // Post selection change
        $('#dodo-post-select').on('change.dodoImprover', function() {
            window.currentPostId = $(this).val();
            
            if (window.currentPostId) {
                $('#dodo-analyze-content').prop('disabled', false);
            } else {
                $('#dodo-analyze-content').prop('disabled', true);
                $('.dodo-analysis-results').hide();
                showEmptyState();
            }
        });
        
        // Analyze content button
        $('#dodo-analyze-content').on('click.dodoImprover', function() {
            window.currentFocusKeyword = $('#dodo-focus-keyword').val().trim();
            
            if (!window.currentFocusKeyword) {
                alert(dodoImprover.strings.noKeyword);
                $('#dodo-focus-keyword').focus();
                return;
            }
            
            analyzeContent();
        });
        
        // Improve button click (delegated with namespace)
        $(document).on('click.dodoImprover', '.dodo-improve-btn', function() {
            const improveType = $(this).data('type');
            const sectionIndex = $(this).data('section');
            const sectionContent = $(this).data('content');
            
            // Get section metadata from stored sections
            const sectionData = window.analyzedSections[sectionIndex];
            
            improveSection(improveType, sectionIndex, sectionContent, sectionData);
        });
        
        // Modal close
        $('.dodo-modal-close, .dodo-modal-cancel').on('click.dodoImprover', function() {
            hideModal();
        });
        
        // Modal overlay click
        $('.dodo-modal-overlay').on('click.dodoImprover', function() {
            hideModal();
        });
        
        // Apply improvement
        $('.dodo-modal-apply').on('click.dodoImprover', function() {
            applyImprovement();
        });
        
        // Tab switching (with namespace)
        $(document).on('click.dodoImprover', '.dodo-tab-btn', function() {
            const tabName = $(this).data('tab');
            switchTab(tabName);
        });
        
    });
    
    /**
     * Show empty state
     */
    function showEmptyState() {
        $('#dodo-empty-state').fadeIn();
        $('.dodo-analysis-results').hide();
    }
    
    /**
     * Hide empty state
     */
    function hideEmptyState() {
        $('#dodo-empty-state').fadeOut();
    }
    
    /**
     * Analyze content
     */
    window.analyzeContent = function() {
        showLoading();
        hideEmptyState();
        
        // Show staged loading experience (Sprint 2B - Task 5)
        showStagedLoading();
        
        // AJAX with XHR for header access
        const xhr = new XMLHttpRequest();
        xhr.open('POST', dodoImprover.ajaxUrl, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        
        // Listen for stage updates via headers
        let lastStage = null;
        xhr.onreadystatechange = function() {
            if (xhr.readyState >= 2) { // Headers received
                const stage = xhr.getResponseHeader('X-DODO-Pipeline-Stage');
                if (stage && stage !== lastStage) {
                    lastStage = stage;
                    updateAnalysisStage(stage);
                }
            }
            
            if (xhr.readyState === 4) { // Request complete
                hideLoading();
                
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        
                        if (response.success) {
                            // Staged reveal of intelligence data
                            revealIntelligenceStaged(response.data);
                            
                            displaySections(response.data.sections);
                            
                            // Handle intelligence data
                            if (response.data.intelligence) {
                                displayIntelligence(response.data.intelligence);
                            }
                            
                            $('.dodo-analysis-results').fadeIn();
                        } else {
                            alert(response.data.message || dodoImprover.strings.error);
                        }
                    } catch (e) {
                        alert(dodoImprover.strings.error);
                    }
                } else {
                    alert(dodoImprover.strings.error);
                }
            }
        };
        
        // Prepare payload
        const payload = {
            action: 'dodo_analyze_content',
            nonce: dodoImprover.nonce,
            post_id: window.currentPostId,
            focus_keyword: window.currentFocusKeyword,
            include_intelligence: true
        };
        
        // Convert to URL-encoded string
        const formData = Object.keys(payload)
            .map(key => encodeURIComponent(key) + '=' + encodeURIComponent(payload[key]))
            .join('&');
        
        xhr.send(formData);
    }
    
    /**
     * Show staged loading experience (Sprint 2B - Task 5)
     * NO FAKE DELAYS - Real backend events control stage progression
     */
    function showStagedLoading() {
        const stages = [
            { name: 'content_loading', icon: '📄', text: 'İçerik yükleniyor...' },
            { name: 'health_analysis', icon: '🔍', text: 'SEO analiz ediliyor...' },
            { name: 'semantic_analysis', icon: '🧠', text: 'Semantik yapı inceleniyor...' },
            { name: 'ai_detection', icon: '🤖', text: 'AI risk analizi yapılıyor...' },
            { name: 'recommendation_engine', icon: '💡', text: 'Öneriler oluşturuluyor...' },
            { name: 'risk_analysis', icon: '⚠️', text: 'Risk değerlendirmesi yapılıyor...' },
            { name: 'summary_generation', icon: '📊', text: 'Özet hazırlanıyor...' },
            { name: 'insights_render', icon: '✨', text: 'İçgörüler render ediliyor...' }
        ];
        
        const $loading = $('#dodo-improver-loading');
        const $content = $loading.find('.dodo-loading-content');
        
        // Clear existing content
        $content.find('.dodo-loading-stages').remove();
        
        // Add stages container
        $content.append('<div class="dodo-loading-stages"></div>');
        const $stages = $content.find('.dodo-loading-stages');
        
        // Add all stages at once
        stages.forEach(function(stage) {
            const stageHtml = `
                <div class="dodo-loading-stage" data-stage-name="${stage.name}">
                    <span class="dodo-stage-icon">${stage.icon}</span>
                    <span class="dodo-stage-text">${stage.text}</span>
                    <span class="dodo-stage-check">✓</span>
                </div>
            `;
            $stages.append(stageHtml);
        });
        
        // Show first stage
        $stages.find('.dodo-loading-stage').first().addClass('active');
        
        // Stages will be updated by real backend events via updateAnalysisStage()
    }
    
    /**
     * Update analysis stage (called by backend events)
     */
    function updateAnalysisStage(stageName) {
        const $stages = $('.dodo-loading-stages');
        const $targetStage = $stages.find('.dodo-loading-stage[data-stage-name="' + stageName + '"]');
        
        if ($targetStage.length === 0) return;
        
        // Mark previous stages as complete
        $targetStage.prevAll('.dodo-loading-stage').removeClass('active').addClass('complete');
        
        // Mark current stage as active
        $targetStage.addClass('active').removeClass('complete');
        
        // Debug log
        if (typeof dodoImprover.debug !== 'undefined' && dodoImprover.debug) {
            console.log('[DODO ANALYSIS] Stage:', stageName);
        }
    }
    
    /**
     * Reveal intelligence staged (Sprint 2B - Task 5)
     */
    function revealIntelligenceStaged(data) {
        // Mark all stages as complete
        $('.dodo-loading-stage').removeClass('active').addClass('complete');
    }
    
    /**
     * Display intelligence data (Sprint 2A - UI Integration)
     */
    function displayIntelligence(intelligence) {
        // Only log in debug mode
        if (typeof dodoImprover.debug !== 'undefined' && dodoImprover.debug) {
            console.log('=== CONTENT INTELLIGENCE ===');
            console.log('Health Score:', intelligence.health_score, '(' + intelligence.score_label + ')');
            console.log('Recommendations:', intelligence.recommendations);
        }
        
        // Show intelligence panel
        $('#dodo-intelligence-panel').fadeIn();
        $('#dodo-quick-start-card').hide();
        
        // Update Health Score Circle
        updateHealthScore(intelligence.health_score, intelligence.score_class, intelligence.score_label);
        
        // Update Dimension Scores
        updateMetricBar('seo', intelligence.seo_score);
        updateMetricBar('quality', intelligence.quality_score);
        updateMetricBar('readability', intelligence.readability_score);
        updateMetricBar('semantic', intelligence.semantic_score);
        updateMetricBar('ai-risk', intelligence.ai_risk_score);
        
        // Update Confidence & Risk
        updateConfidence(intelligence.confidence_score, intelligence.confidence_label);
        updateRisk(intelligence.risk_level, intelligence.risk_label);
        
        // Update Quick Stats
        if (intelligence.quick_stats) {
            $('#dodo-word-count').text(intelligence.quick_stats.word_count || '--');
            $('#dodo-reading-time').text(intelligence.quick_stats.reading_time || '--');
            $('#dodo-keyword-density').text(intelligence.quick_stats.keyword_density 
                ? intelligence.quick_stats.keyword_density.toFixed(1) 
                : '--');
        }
        
        // Update Recommendations
        displayRecommendations(intelligence.recommendations || []);
        
        // Update Advanced Intelligence (Sprint 2B)
        if (intelligence.semantic_analysis) {
            updateSemanticAnalysis(intelligence.semantic_analysis);
        }
        if (intelligence.style_analysis) {
            updateStyleAnalysis(intelligence.style_analysis);
        }
        if (intelligence.depth_analysis) {
            updateDepthAnalysis(intelligence.depth_analysis);
        }
        if (intelligence.ai_detection) {
            updateAIDetection(intelligence.ai_detection);
        }
        if (intelligence.quality_summary) {
            updateQualitySummary(intelligence.quality_summary);
        }
        if (intelligence.risk_assessment) {
            updateRiskAssessment(intelligence.risk_assessment);
        }
        
        // Update GEO Analysis (Sprint 3 - Task 3)
        if (intelligence.geo_analysis) {
            updateGEOAnalysis(intelligence.geo_analysis);
        }
        
        // Update Entity Enrichment (Sprint 3 - Task 2)
        if (intelligence.entity_enrichment) {
            updateEntityEnrichment(intelligence.entity_enrichment);
        }
        
        // Update Workflow Status (Sprint 3 - Task 3)
        if (intelligence.workflow) {
            updateWorkflowStatus(intelligence.workflow);
        }
        
        // Store intelligence globally
        window.currentIntelligence = intelligence;
        
        // Trigger custom event
        $(document).trigger('dodo:intelligence:updated', [intelligence]);
    }
    
    /**
     * Update Semantic Analysis (Sprint 2B - Task 1)
     */
    function updateSemanticAnalysis(semantic) {
        $('#dodo-semantic-health').text(Math.round(semantic.semantic_health));
        $('#dodo-semantic-health-bar').css('width', semantic.semantic_health + '%');
        
        // Show warnings if any
        if (semantic.warnings && semantic.warnings.length > 0) {
            $('#dodo-semantic-warnings').fadeIn();
            displaySemanticWarnings(semantic.warnings);
        } else {
            $('#dodo-semantic-warnings').hide();
        }
    }
    
    /**
     * Display Semantic Warnings
     */
    function displaySemanticWarnings(warnings) {
        const $list = $('#dodo-warnings-list');
        $list.empty();
        
        warnings.forEach(function(warning) {
            const severityClass = 'severity-' + warning.severity;
            const severityIcon = warning.severity === 'high' ? '🔴' : '🟡';
            
            const html = `
                <div class="dodo-warning-item ${severityClass}">
                    <span class="dodo-warning-icon">${severityIcon}</span>
                    <span class="dodo-warning-message">${warning.message}</span>
                </div>
            `;
            
            $list.append(html);
        });
    }
    
    /**
     * Update Style Analysis (Sprint 2B - Task 2)
     */
    function updateStyleAnalysis(style) {
        const $badge = $('#dodo-editorial-tone');
        $badge.text(style.tone_label);
        
        // Add tone class for styling
        $badge.removeClass('tone-corporate tone-technical tone-educational tone-sales tone-professional tone-conversational');
        $badge.addClass('tone-' + style.primary_tone);
    }
    
    /**
     * Update Depth Analysis (Sprint 2B - Task 3)
     */
    function updateDepthAnalysis(depth) {
        const $badge = $('#dodo-content-depth');
        const $bar = $('#dodo-content-depth-bar');
        
        $badge.text(depth.depth_label);
        $bar.css('width', depth.depth_score + '%');
        
        // Add depth class for styling
        $badge.removeClass('depth-deep depth-medium depth-surface');
        $badge.addClass('depth-' + depth.depth_level);
    }
    
    /**
     * Update AI Detection (Sprint 2B - Task 4)
     */
    function updateAIDetection(ai) {
        const $badge = $('#dodo-ai-similarity-risk');
        const $bar = $('#dodo-ai-similarity-bar');
        
        $badge.text(ai.risk_label);
        $bar.css('width', ai.ai_similarity_score + '%');
        
        // Add risk class for styling
        $badge.removeClass('ai-risk-low ai-risk-medium ai-risk-high');
        $badge.addClass('ai-risk-' + ai.risk_level);
    }
    
    /**
     * Update Risk Assessment (Sprint 2B - Task 6)
     */
    function updateRiskAssessment(riskAssessment) {
        if (!riskAssessment || !riskAssessment.risks || riskAssessment.risks.length === 0) {
            $('#dodo-risk-assessment').hide();
            return;
        }
        
        // Show risk assessment section
        $('#dodo-risk-assessment').fadeIn();
        
        // Update risk count
        $('#dodo-risk-count').text(riskAssessment.risk_count);
        
        // Populate risk list
        const $list = $('#dodo-risk-list');
        $list.empty();
        
        riskAssessment.risks.forEach(function(risk) {
            const severityClass = 'severity-' + risk.severity;
            
            const html = `
                <div class="dodo-risk-item ${severityClass}">
                    <div class="dodo-risk-item-header">
                        <span class="dodo-risk-item-icon">${risk.icon}</span>
                        <span class="dodo-risk-item-message">${risk.message}</span>
                    </div>
                    <div class="dodo-risk-item-meta">
                        <span class="dodo-risk-severity-badge ${severityClass}">${risk.severity}</span>
                        <span class="dodo-risk-score">${Math.round(risk.score)}/100</span>
                    </div>
                </div>
            `;
            
            $list.append(html);
        });
    }
    
    /**
     * Update Quality Summary (Sprint 2B - Task 12)
     */
    function updateQualitySummary(summary) {
        // Overall status
        const status = summary.overall_status;
        const statusHtml = `
            <div class="dodo-status-badge status-${status.class}">
                <span class="dodo-status-icon">${status.icon}</span>
                <span class="dodo-status-label">${status.label}</span>
            </div>
        `;
        $('#dodo-overall-status').html(statusHtml);
        
        // Strengths
        const $strengthsList = $('#dodo-strengths-list');
        $strengthsList.empty();
        
        if (summary.strengths && summary.strengths.length > 0) {
            summary.strengths.forEach(function(item) {
                $strengthsList.append(`
                    <div class="dodo-summary-item strength">
                        <span class="dodo-summary-icon">${item.icon}</span>
                        <span class="dodo-summary-text">${item.label}</span>
                    </div>
                `);
            });
        } else {
            $strengthsList.html('<div class="dodo-summary-empty">Güçlü yön bulunamadı</div>');
        }
        
        // Weaknesses
        const $weaknessesList = $('#dodo-weaknesses-list');
        $weaknessesList.empty();
        
        if (summary.weaknesses && summary.weaknesses.length > 0) {
            summary.weaknesses.forEach(function(item) {
                $weaknessesList.append(`
                    <div class="dodo-summary-item weakness severity-${item.severity}">
                        <span class="dodo-summary-icon">${item.icon}</span>
                        <span class="dodo-summary-text">${item.label}</span>
                    </div>
                `);
            });
        } else {
            $weaknessesList.html('<div class="dodo-summary-empty">Zayıf yön bulunamadı</div>');
        }
        
        // Critical warnings
        if (summary.critical_warnings && summary.critical_warnings.length > 0) {
            $('#dodo-critical-section').show();
            const $criticalList = $('#dodo-critical-list');
            $criticalList.empty();
            
            summary.critical_warnings.forEach(function(item) {
                $criticalList.append(`
                    <div class="dodo-summary-item critical">
                        <span class="dodo-summary-icon">${item.icon}</span>
                        <span class="dodo-summary-text">${item.message}</span>
                    </div>
                `);
            });
        } else {
            $('#dodo-critical-section').hide();
        }
        
        // Next actions
        const $actionsList = $('#dodo-actions-list');
        $actionsList.empty();
        
        if (summary.next_actions && summary.next_actions.length > 0) {
            summary.next_actions.forEach(function(item) {
                $actionsList.append(`
                    <div class="dodo-summary-item action">
                        <span class="dodo-summary-icon">${item.icon}</span>
                        <span class="dodo-summary-text">${item.action}</span>
                    </div>
                `);
            });
        } else {
            $actionsList.html('<div class="dodo-summary-empty">Tüm metrikler iyi durumda</div>');
        }
    }
    
    /**
     * Update Health Score Circle
     */
    function updateHealthScore(score, scoreClass, label) {
        const $circle = $('#dodo-health-progress');
        const $scoreEl = $('#dodo-health-score');
        const $labelEl = $('#dodo-health-label');
        
        // Update score text
        $scoreEl.text(Math.round(score));
        $labelEl.text(label);
        
        // Calculate circle progress (circumference = 2 * π * r = 339.292)
        const circumference = 339.292;
        const offset = circumference - (score / 100) * circumference;
        
        // Update circle
        $circle.css('stroke-dashoffset', offset);
        $circle.removeClass('excellent good fair poor').addClass(scoreClass);
    }
    
    /**
     * Update Metric Bar
     */
    function updateMetricBar(metric, score) {
        const $value = $('#dodo-' + metric + '-score');
        const $bar = $('#dodo-' + metric + '-bar');
        
        $value.text(Math.round(score));
        $bar.css('width', score + '%');
    }
    
    /**
     * Update Confidence
     */
    function updateConfidence(score, label) {
        const $score = $('#dodo-confidence-score');
        const $badge = $('#dodo-confidence-badge');
        
        $score.text(Math.round(score));
        $badge.text(label);
        
        // Remove all classes and add appropriate one
        $badge.removeClass('very-high high medium low very-low');
        
        const labelClass = label.toLowerCase().replace(' ', '-');
        $badge.addClass(labelClass);
    }
    
    /**
     * Update Risk
     */
    function updateRisk(level, label) {
        const $badge = $('#dodo-risk-badge');
        
        $badge.text(label);
        $badge.removeClass('low medium high').addClass(level);
    }
    
    /**
     * Display Recommendations
     */
    function displayRecommendations(recommendations) {
        const $list = $('#dodo-recommendations-list');
        $list.empty();
        
        if (!recommendations || recommendations.length === 0) {
            $list.html(`
                <div class="dodo-recommendations-empty">
                    <div class="dodo-recommendations-empty-icon">✓</div>
                    <div>Bu içerik için kritik öneri bulunamadı.</div>
                </div>
            `);
            return;
        }
        
        recommendations.forEach(function(rec) {
            const typeClass = rec.type; // critical, important, suggested
            const categoryIcon = getCategoryIcon(rec.category);
            const effortLabel = getEffortLabel(rec.effort);
            
            // Build explanation HTML if available (Sprint 2B - Task 8)
            let explanationHtml = '';
            if (rec.explanation) {
                explanationHtml = `<div class="dodo-recommendation-explanation">${rec.explanation}</div>`;
            }
            
            // Build priority factors if available (Sprint 2B - Task 11)
            let factorsHtml = '';
            if (rec.priority_factors && rec.priority_factors.length > 0) {
                factorsHtml = '<div class="dodo-recommendation-factors">';
                rec.priority_factors.forEach(function(factor) {
                    factorsHtml += `<span class="dodo-factor-badge">${factor}</span>`;
                });
                factorsHtml += '</div>';
            }
            
            const html = `
                <div class="dodo-recommendation-item ${typeClass}">
                    <div class="dodo-recommendation-header">
                        <div class="dodo-recommendation-title">
                            ${categoryIcon} ${rec.title}
                        </div>
                        <div class="dodo-recommendation-priority">${rec.priority_score || rec.priority}</div>
                    </div>
                    <div class="dodo-recommendation-description">
                        ${rec.description}
                    </div>
                    ${explanationHtml}
                    ${factorsHtml}
                    <div class="dodo-recommendation-meta">
                        <div class="dodo-recommendation-meta-item">
                            <span class="dodo-recommendation-meta-label">Etki:</span>
                            <span>${rec.impact}/10</span>
                        </div>
                        <div class="dodo-recommendation-meta-item">
                            <span class="dodo-recommendation-meta-label">Efor:</span>
                            <span>${effortLabel}</span>
                        </div>
                    </div>
                </div>
            `;
            
            $list.append(html);
        });
    }
    
    /**
     * Get category icon
     */
    function getCategoryIcon(category) {
        const icons = {
            'seo': '🔍',
            'content': '📝',
            'readability': '📖',
            'ai-risk': '⚠️'
        };
        return icons[category] || '•';
    }
    
    /**
     * Get effort label
     */
    function getEffortLabel(effort) {
        const labels = {
            'low': 'Düşük',
            'medium': 'Orta',
            'high': 'Yüksek'
        };
        return labels[effort] || effort;
    }
    
    /**
     * Show score changes (Sprint 2B - Task 7)
     */
    function showScoreChanges(before, after) {
        const changes = [];
        
        const metrics = [
            { key: 'health', label: 'Sağlık' },
            { key: 'seo', label: 'SEO' },
            { key: 'quality', label: 'Kalite' },
            { key: 'readability', label: 'Okunabilirlik' },
            { key: 'semantic', label: 'Semantik' },
            { key: 'ai_risk', label: 'AI Risk' },
            { key: 'confidence', label: 'Güven' }
        ];
        
        metrics.forEach(function(metric) {
            const beforeVal = before[metric.key];
            const afterVal = after[metric.key];
            const change = afterVal - beforeVal;
            
            if (Math.abs(change) >= 2) { // Only show significant changes
                changes.push({
                    label: metric.label,
                    before: Math.round(beforeVal),
                    after: Math.round(afterVal),
                    change: change,
                    direction: change > 0 ? 'up' : 'down'
                });
            }
        });
        
        if (changes.length > 0) {
            let changesHtml = '<div class="dodo-score-changes-title">Skor Değişimleri</div>';
            
            changes.forEach(function(item) {
                const arrow = item.direction === 'up' ? '↗' : '↘';
                const colorClass = item.direction === 'up' ? 'positive' : 'negative';
                const changeText = (item.change > 0 ? '+' : '') + Math.round(item.change);
                
                changesHtml += `
                    <div class="dodo-score-change-item ${colorClass}">
                        <span class="dodo-change-label">${item.label}</span>
                        <span class="dodo-change-values">
                            ${item.before} ${arrow} ${item.after}
                        </span>
                        <span class="dodo-change-delta">${changeText}</span>
                    </div>
                `;
            });
            
            DodoToast.show({
                type: 'info',
                title: 'İyileştirme Sonuçları',
                message: changesHtml,
                duration: 8000,
                closable: true
            });
        }
    }
    
    /**
     * Save score history to database (Sprint 2B - Task 7)
     */
    function saveScoreHistory(postId, beforeScores, afterScores, revisionId) {
        $.ajax({
            url: dodoImprover.ajaxUrl,
            type: 'POST',
            data: {
                action: 'dodo_save_score_history',
                nonce: dodoImprover.nonce,
                post_id: postId,
                revision_id: revisionId || null,
                before_scores: JSON.stringify(beforeScores),
                after_scores: JSON.stringify(afterScores),
                focus_keyword: window.currentFocusKeyword
            },
            success: function(response) {
                if (typeof dodoImprover.debug !== 'undefined' && dodoImprover.debug) {
                    console.log('Score history saved:', response);
                }
            },
            error: function() {
                // Silent fail - don't interrupt user experience
                if (typeof dodoImprover.debug !== 'undefined' && dodoImprover.debug) {
                    console.error('Failed to save score history');
                }
            }
        });
    }

    
    /**
     * Display detected sections
     */
    function displaySections(sections) {
        const container = $('#dodo-detected-sections');
        container.empty();
        
        // Store sections globally for later use
        window.analyzedSections = sections;
        
        if (!sections || sections.length === 0) {
            container.html('<p>' + dodoImprover.strings.noSections + '</p>');
            return;
        }
        
        let displayedCount = 0;
        
        sections.forEach(function(section, index) {
            // Skip sections with empty or very short content
            if (!section.content || section.content.trim().length < 20) {
                return; // Skip this section
            }
            
            const wordCount = section.word_count || countWords(section.content);
            const charCount = section.content.length;
            
            // Check if section is too large
            const isTooLarge = section.too_large || false;
            const offsetUnavailable = section.offset_unavailable || false;
            const sizeWarning = isTooLarge ? '<span class="dodo-size-warning">⚠️ Bölüm çok büyük</span>' : '';
            const offsetWarning = offsetUnavailable ? '<span class="dodo-offset-warning">⚠️ Konum bilgisi yok</span>' : '';
            
            const sectionHtml = `
                <div class="dodo-section-item ${offsetUnavailable ? 'offset-unavailable' : ''}" data-section-index="${index}">
                    <div class="dodo-section-header">
                        <h4>${section.title}</h4>
                        <div class="dodo-section-badges">
                            <span class="dodo-section-type">${section.type}</span>
                            ${sizeWarning}
                            ${offsetWarning}
                            <button class="dodo-expand-btn">
                                <span class="dashicons dashicons-arrow-down-alt2"></span>
                                Genişlet
                            </button>
                        </div>
                    </div>
                    <div class="dodo-section-meta">
                        <span class="dodo-meta-item">📝 ${wordCount} kelime</span>
                        <span class="dodo-meta-item">📊 ${charCount} karakter</span>
                    </div>
                    <div class="dodo-section-preview">
                        ${truncateText(section.content, 200)}
                    </div>
                    <div class="dodo-section-actions">
                        ${getActionButtons(section.type, index, section.content)}
                    </div>
                </div>
            `;
            container.append(sectionHtml);
            displayedCount++;
        });
        
        if (displayedCount === 0) {
            container.html('<p>' + dodoImprover.strings.noSections + '</p>');
        }
    }
    
    /**
     * Get action buttons based on section type
     */
    function getActionButtons(type, index, content) {
        let buttons = '';
        
        // Inline improve button (NEW - Sprint 1B-1)
        buttons += `<button type="button" class="button dodo-inline-improve-btn" data-type="${type}_improve" data-section="${index}">
            <span>✨</span> İyileştir
        </button>`;
        
        if (type === 'intro') {
            buttons += `<button type="button" class="button dodo-improve-btn" data-type="intro_improve" data-section="${index}" data-content="${escapeHtml(content)}">
                <span>✨</span> Intro Geliştir
            </button>`;
        } else if (type === 'faq') {
            buttons += `<button type="button" class="button dodo-improve-btn" data-type="faq_improve" data-section="${index}" data-content="${escapeHtml(content)}">
                <span>💬</span> FAQ Geliştir
            </button>`;
        } else if (type === 'cta') {
            buttons += `<button type="button" class="button dodo-improve-btn" data-type="cta_improve" data-section="${index}" data-content="${escapeHtml(content)}">
                <span>🎯</span> CTA Geliştir
            </button>`;
        } else {
            // Generic section
            buttons += `<button type="button" class="button dodo-improve-btn" data-type="section_expand" data-section="${index}" data-content="${escapeHtml(content)}">
                <span>📝</span> Genişlet
            </button>`;
            buttons += `<button type="button" class="button dodo-improve-btn" data-type="semantic_improve" data-section="${index}" data-content="${escapeHtml(content)}">
                <span>🔍</span> Anlamsal
            </button>`;
            buttons += `<button type="button" class="button dodo-improve-btn" data-type="readability_improve" data-section="${index}" data-content="${escapeHtml(content)}">
                <span>📖</span> Okunabilirlik
            </button>`;
        }
        
        return buttons;
    }
    
    /**
     * Improve section
     */
    function improveSection(improveType, sectionIndex, sectionContent, sectionData) {
        if (!window.currentFocusKeyword) {
            alert(dodoImprover.strings.noKeyword);
            return;
        }
        
        // Check if section is too large
        if (sectionData && sectionData.too_large) {
            DodoToast.show({
                type: 'error',
                title: 'Bölüm Çok Büyük',
                message: sectionData.type === 'faq' 
                    ? 'FAQ bölümü 1500 kelimeden büyük. Daha küçük bir bölüm seçin.'
                    : 'Bu bölüm 3000 kelimeden büyük. Daha küçük bir bölüm seçin.',
                duration: 6000
            });
            return;
        }
        
        // Check if offset is unavailable
        if (sectionData && sectionData.offset_unavailable) {
            DodoToast.show({
                type: 'error',
                title: 'Konum Bilgisi Yok',
                message: 'Bu bölüm güvenli şekilde eşleştirilemedi. Lütfen içeriği tekrar analiz edin veya daha küçük bir bölüm seçin.',
                duration: 6000
            });
            return;
        }
        
        showLoading();
        
        // Prepare payload with section metadata
        const payload = {
            action: 'dodo_improve_content',
            nonce: dodoImprover.nonce,
            post_id: window.currentPostId,
            improve_type: improveType,
            focus_keyword: window.currentFocusKeyword,
            section_content: sectionContent
        };
        
        // Add offset metadata if available
        if (sectionData) {
            payload.section_id = sectionData.section_id;
            payload.start_offset = sectionData.start_offset;
            payload.end_offset = sectionData.end_offset;
            payload.original_hash = sectionData.original_hash;
        }
        
        $.ajax({
            url: dodoImprover.ajaxUrl,
            type: 'POST',
            data: payload,
            success: function(response) {
                hideLoading();
                
                if (response.success) {
                    // Store ALL improvement data for apply
                    currentImprovement = {
                        original: response.data.original,
                        improved: response.data.improved,
                        type: improveType,
                        sectionIndex: sectionIndex,
                        sectionData: sectionData, // Store full section metadata
                        metadata: response.data.metadata || {},
                        validation_failed: response.data.validation_failed || false,
                        apply_allowed: response.data.apply_allowed !== false
                    };
                    
                    // Debug log
                    if (typeof dodoImprover.debug !== 'undefined' && dodoImprover.debug) {
                        console.log('[DODO IMPROVE] === IMPROVEMENT RESPONSE ===');
                        console.log('[DODO IMPROVE] original length:', currentImprovement.original.length);
                        console.log('[DODO IMPROVE] improved length:', currentImprovement.improved.length);
                        console.log('[DODO IMPROVE] type:', currentImprovement.type);
                        console.log('[DODO IMPROVE] validation_failed:', currentImprovement.validation_failed);
                        console.log('[DODO IMPROVE] apply_allowed:', currentImprovement.apply_allowed);
                        console.log('[DODO IMPROVE] section_id:', sectionData ? sectionData.section_id : 'N/A');
                        console.log('[DODO IMPROVE] metadata keys:', Object.keys(currentImprovement.metadata));
                    }
                    
                    showModal(
                        response.data.original, 
                        response.data.improved, 
                        response.data.metadata,
                        response.data.validation_failed || false,
                        response.data.apply_allowed !== false
                    );
                } else {
                    alert(response.data.message || dodoImprover.strings.error);
                }
            },
            error: function() {
                hideLoading();
                alert(dodoImprover.strings.error);
            }
        });
    }
    
    /**
     * Apply improvement
     */
    function applyImprovement() {
        // Validate current state
        if (!window.currentPostId) {
            DodoToast.error('Hata', 'Post ID bulunamadı. Lütfen sayfayı yenileyin.');
            return;
        }
        
        if (!currentImprovement.original || !currentImprovement.improved) {
            DodoToast.error('Hata', 'İyileştirme verisi eksik. Lütfen önizlemeyi tekrar oluşturun.');
            return;
        }
        
        if (!currentImprovement.type) {
            DodoToast.error('Hata', 'İyileştirme türü belirtilmemiş.');
            return;
        }
        
        // CRITICAL: Check if apply is allowed (validation_failed protection)
        if (currentImprovement.apply_allowed === false) {
            DodoToast.error(
                'Uygulama Engellendi',
                'Doğrulama başarısız içerik uygulanamaz. Lütfen önce içeriği düzeltin.',
                {
                    'Durum': 'Validation Failed',
                    'Strateji': currentImprovement.metadata?.strategy || 'N/A'
                }
            );
            return;
        }
        
        // Double-check validation_failed flag
        if (currentImprovement.validation_failed === true) {
            DodoToast.error(
                'Uygulama Engellendi',
                'Bu içerik doğrulama testlerinden geçemedi ve güvenlik nedeniyle uygulanamaz.',
                {
                    'Validation': 'Failed',
                    'Hata': currentImprovement.metadata?.error_message || 'Bilinmeyen hata'
                }
            );
            return;
        }
        
        // Store current scores for history (Sprint 2B - Task 7)
        const beforeScores = window.currentIntelligence ? {
            health: window.currentIntelligence.health_score,
            seo: window.currentIntelligence.seo_score,
            quality: window.currentIntelligence.quality_score,
            readability: window.currentIntelligence.readability_score,
            semantic: window.currentIntelligence.semantic_score,
            ai_risk: window.currentIntelligence.ai_risk_score,
            confidence: window.currentIntelligence.confidence_score
        } : null;
        
        // Prepare payload
        const payload = {
            action: 'dodo_apply_improvement',
            nonce: dodoImprover.nonce,
            post_id: window.currentPostId,
            original_content: currentImprovement.original,
            improved_content: currentImprovement.improved,
            improve_type: currentImprovement.type,
            metadata: JSON.stringify(currentImprovement.metadata || {})
        };
        
        // Add offset metadata if available
        if (currentImprovement.sectionData) {
            payload.section_id = currentImprovement.sectionData.section_id;
            payload.start_offset = currentImprovement.sectionData.start_offset;
            payload.end_offset = currentImprovement.sectionData.end_offset;
            payload.original_hash = currentImprovement.sectionData.original_hash;
        }
        
        // Debug log
        if (typeof dodoImprover.debug !== 'undefined' && dodoImprover.debug) {
            console.log('[DODO APPLY] === APPLY IMPROVEMENT PAYLOAD ===');
            console.log('[DODO APPLY] post_id:', payload.post_id);
            console.log('[DODO APPLY] improve_type:', payload.improve_type);
            console.log('[DODO APPLY] original_content length:', payload.original_content.length);
            console.log('[DODO APPLY] improved_content length:', payload.improved_content.length);
            console.log('[DODO APPLY] section_id:', payload.section_id || 'N/A');
            console.log('[DODO APPLY] start_offset:', payload.start_offset || 'N/A');
            console.log('[DODO APPLY] end_offset:', payload.end_offset || 'N/A');
            console.log('[DODO APPLY] original_hash:', payload.original_hash || 'N/A');
            console.log('[DODO APPLY] metadata:', currentImprovement.metadata);
        }
        
        // Show loading state
        const $applyBtn = $('.dodo-modal-apply');
        $applyBtn.addClass('loading').prop('disabled', true);
        const originalText = $applyBtn.text();
        $applyBtn.text('Uygulanıyor...');
        
        showLoading();
        
        $.ajax({
            url: dodoImprover.ajaxUrl,
            type: 'POST',
            data: payload,
            success: function(response) {
                hideLoading();
                $applyBtn.removeClass('loading').prop('disabled', false).text(originalText);
                
                if (response.success) {
                    // Debug log success
                    if (typeof dodoImprover.debug !== 'undefined' && dodoImprover.debug) {
                        console.log('[DODO APPLY] === APPLY SUCCESS ===');
                        console.log('[DODO APPLY] - verified:', response.data.verified);
                        console.log('[DODO APPLY] - content_hash:', response.data.content_hash);
                        console.log('[DODO APPLY] - revision_id:', response.data.revision_id);
                    }
                    
                    // Close modal with smooth animation
                    hideModal();
                    
                    // Build meta info
                    const meta = {};
                    
                    // Improve type label
                    const typeLabels = {
                        'intro_improve': 'Giriş',
                        'faq_improve': 'SSS',
                        'cta_improve': 'CTA',
                        'section_expand': 'Genişletme',
                        'semantic_improve': 'Anlamsal',
                        'readability_improve': 'Okunabilirlik'
                    };
                    meta['Bölüm'] = typeLabels[currentImprovement.type] || currentImprovement.type;
                    
                    // Matching strategy
                    if (response.data.match_strategy) {
                        const strategyLabels = {
                            'exact': 'Tam eşleşme',
                            'normalized': 'Normalize',
                            'fuzzy': 'Fuzzy'
                        };
                        let strategyText = strategyLabels[response.data.match_strategy] || response.data.match_strategy;
                        if (response.data.confidence) {
                            strategyText += ' (%' + response.data.confidence + ' güven)';
                        }
                        meta['Eşleşme'] = strategyText;
                    }
                    
                    // Revision status
                    if (response.data.revision_id) {
                        meta['Revizyon'] = 'Kaydedildi (#' + response.data.revision_id + ')';
                    } else {
                        // Revision failed but content saved
                        DodoToast.warning(
                            'İçerik kaydedildi',
                            'Revizyon geçmişi oluşturulamadı ancak içerik başarıyla güncellendi.',
                            meta
                        );
                        
                        // Re-analyze after delay
                        setTimeout(function() {
                            if (typeof dodoImprover.debug !== 'undefined' && dodoImprover.debug) {
                                console.log('[DODO APPLY] Re-analyzing content...');
                            }
                            analyzeContent();
                        }, 1500);
                        
                        return;
                    }
                    
                    // Show success toast
                    DodoToast.success(
                        'İyileştirme başarıyla uygulandı',
                        response.data.message || 'İçerik başarıyla güncellendi.',
                        meta
                    );
                    
                    // Re-analyze to show updated content
                    setTimeout(function() {
                        if (typeof dodoImprover.debug !== 'undefined' && dodoImprover.debug) {
                            console.log('[DODO APPLY] Re-analyzing content to verify changes...');
                        }
                        
                        // Re-analyze and show score changes (Sprint 2B - Task 7)
                        $.ajax({
                            url: dodoImprover.ajaxUrl,
                            type: 'POST',
                            data: {
                                action: 'dodo_analyze_content',
                                nonce: dodoImprover.nonce,
                                post_id: window.currentPostId,
                                focus_keyword: window.currentFocusKeyword,
                                include_intelligence: true,
                                force_refresh: true
                            },
                            success: function(reanalysis) {
                                if (reanalysis.success && reanalysis.data.intelligence) {
                                    const afterScores = {
                                        health: reanalysis.data.intelligence.health_score,
                                        seo: reanalysis.data.intelligence.seo_score,
                                        quality: reanalysis.data.intelligence.quality_score,
                                        readability: reanalysis.data.intelligence.readability_score,
                                        semantic: reanalysis.data.intelligence.semantic_score,
                                        ai_risk: reanalysis.data.intelligence.ai_risk_score,
                                        confidence: reanalysis.data.intelligence.confidence_score
                                    };
                                    
                                    // Show score changes if we have before scores
                                    if (beforeScores) {
                                        showScoreChanges(beforeScores, afterScores);
                                        
                                        // Save score history to database (Sprint 2B - Task 7)
                                        saveScoreHistory(window.currentPostId, beforeScores, afterScores, response.data.revision_id);
                                    }
                                    
                                    // Update UI with new intelligence
                                    displaySections(reanalysis.data.sections);
                                    displayIntelligence(reanalysis.data.intelligence);
                                    $('.dodo-analysis-results').fadeIn();
                                }
                            }
                        });
                    }, 1500);
                    
                } else {
                    // === ERROR HANDLING ===
                    // Get base error message
                    let errorMsg = response.data && response.data.message 
                        ? response.data.message 
                        : (response.message || 'Bir hata oluştu.');
                    
                    let errorDetails = [];
                    
                    // Missing params
                    if (response.data && response.data.missing_params) {
                        errorDetails.push('Eksik: ' + response.data.missing_params.join(', '));
                    }
                    
                    // Validation failed
                    if (response.data && response.data.validation_failed) {
                        if (response.data.error_details) {
                            errorDetails.push('Detay: ' + response.data.error_details);
                        }
                    }
                    
                    // Content mismatch
                    if (response.data && response.data.content_mismatch) {
                        errorDetails.push('Sayfa yenilenecek...');
                        
                        DodoToast.error('İçerik Uyuşmazlığı', errorMsg, {
                            'Durum': 'Sayfa 2 saniye içinde yenilenecek'
                        });
                        
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                        return;
                    }
                    
                    // Build meta
                    const errorMeta = {};
                    if (errorDetails.length > 0) {
                        errorMeta['Detaylar'] = errorDetails.join(', ');
                    }
                    
                    DodoToast.error('İşlem Başarısız', errorMsg, errorMeta);
                    
                    // Debug log
                    console.error('[DODO APPLY] === APPLY FAILED ===');
                    console.error('[DODO APPLY] Error:', errorMsg);
                    if (typeof dodoImprover.debug !== 'undefined' && dodoImprover.debug) {
                        console.error('[DODO APPLY] Full response:', response);
                    }
                }
            },
            error: function(xhr, status, error) {
                hideLoading();
                $applyBtn.removeClass('loading').prop('disabled', false).text(originalText);
                
                let errorTitle = 'Sunucu Hatası';
                let errorMsg = '';
                let errorMeta = {};
                
                // Try to parse JSON response
                try {
                    const response = JSON.parse(xhr.responseText);
                    
                    if (response.data && response.data.message) {
                        errorMsg = response.data.message;
                    } else if (response.message) {
                        errorMsg = response.message;
                    } else if (response.error) {
                        errorMsg = response.error;
                    } else {
                        errorMsg = 'Bilinmeyen sunucu hatası';
                        errorMeta['HTTP Status'] = xhr.status || 'N/A';
                    }
                } catch (e) {
                    // Not JSON - server error
                    if (xhr.status === 500) {
                        errorMsg = 'Lütfen debug.log kontrol edin.';
                        errorMeta['HTTP'] = '500 Internal Server Error';
                    } else if (xhr.status === 502) {
                        errorMsg = 'Lütfen tekrar deneyin.';
                        errorMeta['HTTP'] = '502 Bad Gateway';
                    } else if (xhr.status === 503) {
                        errorMsg = 'Sunucu geçici olarak kullanılamıyor.';
                        errorMeta['HTTP'] = '503 Service Unavailable';
                    } else if (xhr.status === 0) {
                        errorTitle = 'Bağlantı Hatası';
                        errorMsg = 'İnternet bağlantınızı kontrol edin.';
                    } else {
                        errorMsg = error || 'Bilinmeyen hata';
                        errorMeta['HTTP'] = xhr.status;
                    }
                }
                
                // Show error toast
                DodoToast.error(errorTitle, errorMsg, errorMeta);
                
                // Debug log (always log AJAX errors)
                console.error('[DODO APPLY] === AJAX ERROR ===');
                console.error('[DODO APPLY] Status:', xhr.status, status);
                console.error('[DODO APPLY] Error:', error);
                
                if (typeof dodoImprover.debug !== 'undefined' && dodoImprover.debug) {
                    console.error('[DODO APPLY] Response Text:', xhr.responseText);
                    console.error('[DODO APPLY] Full XHR:', xhr);
                }
            }
        });
    }
    
    /**
     * Show modal
     */
    function showModal(original, improved, metadata, validationFailed, applyAllowed) {
        // Handle validation failed state (NEW - Task 7)
        if (validationFailed === true) {
            // Show validation warning panel
            $('.dodo-validation-warning').show();
            
            // Display error message if available
            if (metadata && metadata.error_message) {
                $('.dodo-validation-details').html('<strong>Sebep:</strong> ' + escapeHtml(metadata.error_message));
            }
            
            // Display validation errors if available
            if (metadata && metadata.validation_errors && metadata.validation_errors.length > 0) {
                let errorList = '<ul>';
                metadata.validation_errors.forEach(function(error) {
                    errorList += '<li>' + escapeHtml(error) + '</li>';
                });
                errorList += '</ul>';
                $('.dodo-validation-details').append(errorList);
            }
            
            // Hide Diff tab (no diff for validation failures)
            $('.dodo-tab-btn[data-tab="diff"]').hide();
            
            // Disable Apply button
            $('.dodo-modal-apply').prop('disabled', true).addClass('disabled');
            
            // Add red validation badge to strategy
            if (metadata && metadata.strategy) {
                metadata.strategy = 'validation_failed';
            }
        } else {
            // Validation passed - normal flow
            $('.dodo-validation-warning').hide();
            $('.dodo-tab-btn[data-tab="diff"]').show();
            
            // Enable/disable Apply button based on apply_allowed flag
            if (applyAllowed === false) {
                $('.dodo-modal-apply').prop('disabled', true).addClass('disabled');
            } else {
                $('.dodo-modal-apply').prop('disabled', false).removeClass('disabled');
            }
        }
        
        $('.dodo-preview-original').html(formatContent(original));
        $('.dodo-preview-improved').html(formatContent(improved));
        
        // Display diff view (only if validation passed)
        if (!validationFailed && metadata && metadata.diff_html) {
            $('.dodo-preview-diff').html(metadata.diff_html);
            displayDiffStatistics(metadata);
            $('.dodo-diff-stats').show();
        } else {
            $('.dodo-preview-diff').html('<p>Fark görünümü mevcut değil</p>');
            $('.dodo-diff-stats').hide();
        }
        
        // Display metadata if available
        if (metadata && Object.keys(metadata).length > 0) {
            displayMetadata(metadata);
            $('.dodo-strategy-info').show();
        } else {
            $('.dodo-strategy-info').hide();
        }
        
        // Reset to original tab
        switchTab('original');
        
        $('#dodo-improver-preview-modal').fadeIn(300);
    }
    
    /**
     * Switch tab (NEW)
     */
    function switchTab(tabName) {
        // Update tab buttons
        $('.dodo-tab-btn').removeClass('active');
        $('.dodo-tab-btn[data-tab="' + tabName + '"]').addClass('active');
        
        // Update tab content
        $('.dodo-tab-content').removeClass('active');
        $('.dodo-tab-content[data-tab-content="' + tabName + '"]').addClass('active');
    }
    
    /**
     * Display diff statistics (NEW)
     */
    function displayDiffStatistics(metadata) {
        const container = $('.dodo-diff-stats-grid');
        container.empty();
        
        // Added words
        if (metadata.added_words_count !== undefined) {
            const addedCount = metadata.added_words_count;
            container.append(
                '<div class="dodo-diff-stat-item added">' +
                '<span class="dodo-diff-stat-icon">+</span>' +
                '<span class="dodo-diff-stat-value">' + addedCount + '</span>' +
                '<span class="dodo-diff-stat-label">Eklenen Kelimeler</span>' +
                '</div>'
            );
        }
        
        // Removed words
        if (metadata.removed_words_count !== undefined) {
            const removedCount = metadata.removed_words_count;
            container.append(
                '<div class="dodo-diff-stat-item removed">' +
                '<span class="dodo-diff-stat-icon">-</span>' +
                '<span class="dodo-diff-stat-value">' + removedCount + '</span>' +
                '<span class="dodo-diff-stat-label">Silinen Kelimeler</span>' +
                '</div>'
            );
        }
        
        // Modified words
        if (metadata.modified_words_count !== undefined) {
            const modifiedCount = metadata.modified_words_count;
            container.append(
                '<div class="dodo-diff-stat-item modified">' +
                '<span class="dodo-diff-stat-icon">~</span>' +
                '<span class="dodo-diff-stat-value">' + modifiedCount + '</span>' +
                '<span class="dodo-diff-stat-label">Değiştirilen Kelimeler</span>' +
                '</div>'
            );
        }
        
        // Unchanged words
        if (metadata.unchanged_words_count !== undefined) {
            const unchangedCount = metadata.unchanged_words_count;
            container.append(
                '<div class="dodo-diff-stat-item unchanged">' +
                '<span class="dodo-diff-stat-icon">✓</span>' +
                '<span class="dodo-diff-stat-value">' + unchangedCount + '</span>' +
                '<span class="dodo-diff-stat-label">Değişmeyen Kelimeler</span>' +
                '</div>'
            );
        }
        
        // Change percentage
        if (metadata.diff_change_percentage !== undefined) {
            const changePercent = metadata.diff_change_percentage;
            let changeClass = 'low';
            if (changePercent > 30) {
                changeClass = 'high';
            } else if (changePercent > 15) {
                changeClass = 'medium';
            }
            
            container.append(
                '<div class="dodo-diff-stat-item total ' + changeClass + '">' +
                '<span class="dodo-diff-stat-icon">%</span>' +
                '<span class="dodo-diff-stat-value">' + changePercent + '%</span>' +
                '<span class="dodo-diff-stat-label">Toplam Değişiklik</span>' +
                '</div>'
            );
        }
        
        // Visual quality warning for LOW rewrite mode
        if (metadata.rewrite_necessity === 'low' && metadata.diff_change_percentage > 20) {
            container.append(
                '<div class="dodo-diff-warning">' +
                '⚠️ Uyarı: DÜŞÜK yeniden yazım modu için yüksek değişiklik yüzdesi' +
                '</div>'
            );
        }
    }
    
    /**
     * Display improvement metadata
     */
    function displayMetadata(metadata) {
        const container = $('.dodo-metadata-grid');
        container.empty();
        
        // Strategy with validation failed badge (NEW - Task 7)
        if (metadata.strategy) {
            const strategyLabel = metadata.strategy.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
            let strategyBadge = '';
            
            if (metadata.strategy === 'validation_failed') {
                strategyBadge = '<span class="badge-validation-failed">❌ Doğrulama Başarısız</span>';
            } else {
                strategyBadge = '<span class="value">' + escapeHtml(strategyLabel) + '</span>';
            }
            
            container.append(
                '<div class="dodo-metadata-item">' +
                '<span class="label">Strateji:</span>' +
                strategyBadge +
                '</div>'
            );
        }
        
        // Main problem
        if (metadata.main_problem) {
            container.append(
                '<div class="dodo-metadata-item">' +
                '<span class="label">Tespit Edilen Sorun:</span>' +
                '<span class="value">' + escapeHtml(metadata.main_problem) + '</span>' +
                '</div>'
            );
        }
        
        // Rewrite necessity
        if (metadata.rewrite_necessity) {
            const badgeClass = 'badge-' + metadata.rewrite_necessity;
            const necessityLabel = metadata.rewrite_necessity.charAt(0).toUpperCase() + metadata.rewrite_necessity.slice(1);
            container.append(
                '<div class="dodo-metadata-item">' +
                '<span class="label">Yeniden Yazım Gerekliliği:</span>' +
                '<span class="value ' + badgeClass + '">' + necessityLabel + '</span>' +
                '</div>'
            );
        }
        
        // Sentence statistics (NEW)
        if (metadata.total_sentences_count) {
            const preservedCount = metadata.preserved_sentences_count || 0;
            const changedCount = metadata.changed_sentences_count || 0;
            const totalCount = metadata.total_sentences_count;
            const preservedPercent = Math.round((preservedCount / totalCount) * 100);
            
            container.append(
                '<div class="dodo-metadata-item">' +
                '<span class="label">Cümle Değişiklikleri:</span>' +
                '<span class="value">' + 
                preservedCount + ' korundu, ' + changedCount + ' değişti (%' + preservedPercent + ' korundu)' +
                '</span>' +
                '</div>'
            );
        }
        
        // Semantic preservation score (NEW)
        if (metadata.semantic_preservation_score !== undefined) {
            const semanticScore = metadata.semantic_preservation_score;
            let semanticClass = 'badge-high';
            if (semanticScore >= 80) {
                semanticClass = 'badge-low'; // Green for good preservation
            } else if (semanticScore >= 60) {
                semanticClass = 'badge-medium'; // Yellow for moderate
            }
            
            container.append(
                '<div class="dodo-metadata-item">' +
                '<span class="label">Anlam Koruma:</span>' +
                '<span class="value ' + semanticClass + '">%' + semanticScore + '</span>' +
                '</div>'
            );
        }
        
        // Synonym replacements (NEW)
        if (metadata.synonym_replacement_count !== undefined) {
            const synonymCount = metadata.synonym_replacement_count;
            let synonymWarning = '';
            if (synonymCount > 10) {
                synonymWarning = ' ⚠️ (çok fazla)';
            }
            
            container.append(
                '<div class="dodo-metadata-item">' +
                '<span class="label">Eş Anlamlı Değiştirmeler:</span>' +
                '<span class="value">' + synonymCount + synonymWarning + '</span>' +
                '</div>'
            );
        }
        
        // Stylistic changes (NEW)
        if (metadata.stylistic_changes_detected !== undefined) {
            const stylisticCount = metadata.stylistic_changes_detected;
            let stylisticWarning = '';
            if (stylisticCount > 3) {
                stylisticWarning = ' ⚠️ (çok fazla)';
            }
            
            container.append(
                '<div class="dodo-metadata-item">' +
                '<span class="label">Stilistik Değişiklikler:</span>' +
                '<span class="value">' + stylisticCount + stylisticWarning + '</span>' +
                '</div>'
            );
        }
        
        // Token change ratio (NEW)
        if (metadata.token_change_ratio !== undefined) {
            const tokenRatio = metadata.token_change_ratio;
            let tokenClass = 'badge-low'; // Green for low changes
            let tokenWarning = '';
            
            if (tokenRatio > 20) {
                tokenClass = 'badge-high'; // Red for excessive changes
                tokenWarning = ' ⚠️ (çok yüksek)';
            } else if (tokenRatio > 10) {
                tokenClass = 'badge-medium'; // Yellow for moderate
            }
            
            container.append(
                '<div class="dodo-metadata-item">' +
                '<span class="label">Token Değişim Oranı:</span>' +
                '<span class="value ' + tokenClass + '">%' + tokenRatio + tokenWarning + '</span>' +
                '</div>'
            );
        }
        
        // Surgical edit score (NEW)
        if (metadata.surgical_edit_score !== undefined) {
            const surgicalScore = metadata.surgical_edit_score;
            let surgicalClass = 'badge-high'; // Red for low score
            
            if (surgicalScore >= 80) {
                surgicalClass = 'badge-low'; // Green for high score (good)
            } else if (surgicalScore >= 60) {
                surgicalClass = 'badge-medium'; // Yellow for moderate
            }
            
            container.append(
                '<div class="dodo-metadata-item">' +
                '<span class="label">Cerrahi Düzenleme Skoru:</span>' +
                '<span class="value ' + surgicalClass + '">%' + surgicalScore + '</span>' +
                '</div>'
            );
        }
        
        // Reconstructed sentences (NEW)
        if (metadata.reconstructed_sentences_count !== undefined) {
            const reconstructedCount = metadata.reconstructed_sentences_count;
            let reconstructedWarning = '';
            
            if (reconstructedCount > 0) {
                reconstructedWarning = ' ⚠️ (cümle yeniden yapılandırması algılandı)';
            }
            
            container.append(
                '<div class="dodo-metadata-item">' +
                '<span class="label">Yeniden Yapılandırılan Cümleler:</span>' +
                '<span class="value">' + reconstructedCount + reconstructedWarning + '</span>' +
                '</div>'
            );
        }
        
        // CHANGE INTENT ANALYSIS (NEW - Task 9)
        if (metadata.change_classification) {
            const classification = metadata.change_classification;
            let classificationBadge = '';
            
            if (classification === 'editorial_safe') {
                classificationBadge = '<span class="badge-editorial-safe">🟢 Editöryel Güvenli</span>';
            } else if (classification === 'moderate_rewrite') {
                classificationBadge = '<span class="badge-moderate-rewrite">🟡 Orta Seviye Yeniden Yazım</span>';
            } else if (classification === 'semantic_rewrite') {
                classificationBadge = '<span class="badge-semantic-rewrite">🔴 Anlamsal Yeniden Yazım Algılandı</span>';
            }
            
            container.append(
                '<div class="dodo-metadata-item dodo-metadata-full-width">' +
                '<span class="label">Değişiklik Sınıflandırması:</span>' +
                classificationBadge +
                '</div>'
            );
        }
        
        // Change Intent Details (NEW - Task 9)
        if (metadata.change_intent) {
            container.append(
                '<div class="dodo-metadata-section-header dodo-metadata-full-width">' +
                '<strong>Değişiklik Amacı Analizi</strong>' +
                '</div>'
            );
            
            // Grammar fixes
            if (metadata.grammar_fixes !== undefined) {
                container.append(
                    '<div class="dodo-metadata-item">' +
                    '<span class="label">Dilbilgisi Düzeltmeleri:</span>' +
                    '<span class="value">' + metadata.grammar_fixes + '</span>' +
                    '</div>'
                );
            }
            
            // Punctuation fixes
            if (metadata.punctuation_fixes !== undefined) {
                container.append(
                    '<div class="dodo-metadata-item">' +
                    '<span class="label">Noktalama Düzeltmeleri:</span>' +
                    '<span class="value">' + metadata.punctuation_fixes + '</span>' +
                    '</div>'
                );
            }
            
            // Readability fixes
            if (metadata.readability_fixes !== undefined) {
                container.append(
                    '<div class="dodo-metadata-item">' +
                    '<span class="label">Okunabilirlik Düzeltmeleri:</span>' +
                    '<span class="value">' + metadata.readability_fixes + '</span>' +
                    '</div>'
                );
            }
            
            // Semantic reframings (CRITICAL)
            if (metadata.semantic_reframings !== undefined) {
                const reframingCount = metadata.semantic_reframings;
                let reframingClass = '';
                let reframingWarning = '';
                
                if (reframingCount > 0) {
                    reframingClass = 'dodo-metadata-warning';
                    reframingWarning = ' ⚠️ (DÜŞÜK modda YASAK)';
                }
                
                container.append(
                    '<div class="dodo-metadata-item ' + reframingClass + '">' +
                    '<span class="label">Anlamsal Yeniden Çerçevelemeler:</span>' +
                    '<span class="value">' + reframingCount + reframingWarning + '</span>' +
                    '</div>'
                );
            }
            
            // Tone shifts
            if (metadata.tone_shifts !== undefined) {
                const toneCount = metadata.tone_shifts;
                let toneClass = '';
                let toneWarning = '';
                
                if (toneCount > 0) {
                    toneClass = 'dodo-metadata-warning';
                    toneWarning = ' ⚠️ (DÜŞÜK modda YASAK)';
                }
                
                container.append(
                    '<div class="dodo-metadata-item ' + toneClass + '">' +
                    '<span class="label">Ton Değişimleri:</span>' +
                    '<span class="value">' + toneCount + toneWarning + '</span>' +
                    '</div>'
                );
            }
            
            // Metaphor changes
            if (metadata.metaphor_changes !== undefined) {
                const metaphorCount = metadata.metaphor_changes;
                let metaphorClass = '';
                let metaphorWarning = '';
                
                if (metaphorCount > 0) {
                    metaphorClass = 'dodo-metadata-warning';
                    metaphorWarning = ' ⚠️ (DÜŞÜK modda YASAK)';
                }
                
                container.append(
                    '<div class="dodo-metadata-item ' + metaphorClass + '">' +
                    '<span class="label">Metafor Değişimleri:</span>' +
                    '<span class="value">' + metaphorCount + metaphorWarning + '</span>' +
                    '</div>'
                );
            }
            
            // Sentence reconstructions
            if (metadata.sentence_reconstructions !== undefined) {
                const reconstructionCount = metadata.sentence_reconstructions;
                let reconstructionClass = '';
                let reconstructionWarning = '';
                
                if (reconstructionCount > 1) {
                    reconstructionClass = 'dodo-metadata-warning';
                    reconstructionWarning = ' ⚠️ (DÜŞÜK modda SINIRLI)';
                }
                
                container.append(
                    '<div class="dodo-metadata-item ' + reconstructionClass + '">' +
                    '<span class="label">Cümle Yeniden Yapılandırmaları:</span>' +
                    '<span class="value">' + reconstructionCount + reconstructionWarning + '</span>' +
                    '</div>'
                );
            }
        }
        
        // What preserved
        if (metadata.what_preserved && metadata.what_preserved.length > 0) {
            container.append(
                '<div class="dodo-metadata-item">' +
                '<span class="label">Korunan:</span>' +
                '<span class="value">' + escapeHtml(metadata.what_preserved.join(', ')) + '</span>' +
                '</div>'
            );
        }
        
        // What changed
        if (metadata.what_changed && metadata.what_changed.length > 0) {
            container.append(
                '<div class="dodo-metadata-item">' +
                '<span class="label">Değiştirilen:</span>' +
                '<span class="value">' + escapeHtml(metadata.what_changed.join(', ')) + '</span>' +
                '</div>'
            );
        }
        
        // Validation warnings
        if (metadata.validation_warnings && metadata.validation_warnings.length > 0) {
            container.append(
                '<div class="dodo-metadata-item warning">' +
                '<span class="label">Uyarılar:</span>' +
                '<span class="value">' + escapeHtml(metadata.validation_warnings.join(', ')) + '</span>' +
                '</div>'
            );
        }
        
        // Validation errors (if any)
        if (metadata.validation_errors && metadata.validation_errors.length > 0) {
            container.append(
                '<div class="dodo-metadata-item error">' +
                '<span class="label">Doğrulama Hataları:</span>' +
                '<span class="value">' + escapeHtml(metadata.validation_errors.join(', ')) + '</span>' +
                '</div>'
            );
        }
    }
    
    /**
     * Hide modal
     */
    function hideModal() {
        $('#dodo-improver-preview-modal').fadeOut(300);
        currentImprovement = {
            original: '',
            improved: '',
            type: '',
            sectionIndex: null
        };
    }
    
    /**
     * Show loading
     */
    function showLoading() {
        $('#dodo-improver-loading').fadeIn(200);
    }
    
    /**
     * Hide loading
     */
    function hideLoading() {
        $('#dodo-improver-loading').fadeOut(200);
    }
    
    /**
     * Show notice
     */
    function showNotice(type, message) {
        const noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
        const notice = $('<div>')
            .addClass('notice ' + noticeClass + ' is-dismissible')
            .html('<p>' + message + '</p>');
        
        $('.wrap').prepend(notice);
        
        setTimeout(function() {
            notice.fadeOut(300, function() {
                $(this).remove();
            });
        }, 3000);
    }
    
    /**
     * Format content for preview
     */
    function formatContent(content) {
        if (!content) return '';
        
        // Remove markdown code block wrapper if present
        content = content.replace(/^```markdown\s*/i, '');
        content = content.replace(/^```\s*/m, '');
        content = content.replace(/\s*```$/m, '');
        
        // Trim whitespace
        content = content.trim();
        
        // Store original for debugging
        if (window.console && window.console.log) {
            console.log('Formatting content:', content.substring(0, 100));
        }
        
        // Convert markdown headings to HTML (must be done before paragraph processing)
        content = content.replace(/^#### (.+)$/gm, '<h5>$1</h5>');
        content = content.replace(/^### (.+)$/gm, '<h4>$1</h4>');
        content = content.replace(/^## (.+)$/gm, '<h3>$1</h3>');
        content = content.replace(/^# (.+)$/gm, '<h2>$1</h2>');
        
        // Convert markdown bold (must be done before italic)
        content = content.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
        
        // Convert markdown italic (single asterisk, not part of bold)
        content = content.replace(/\*([^*\n]+?)\*/g, '<em>$1</em>');
        
        // Convert markdown unordered lists
        content = content.replace(/^[\-\*] (.+)$/gm, '<li>$1</li>');
        
        // Wrap consecutive list items in <ul>
        content = content.replace(/(<li>.*?<\/li>\n?)+/g, function(match) {
            return '<ul>' + match + '</ul>';
        });
        
        // Convert markdown ordered lists
        content = content.replace(/^\d+\. (.+)$/gm, '<li>$1</li>');
        
        // Wrap consecutive numbered list items in <ol>
        content = content.replace(/(<li>.*?<\/li>\n?)+/g, function(match) {
            // Check if already wrapped in ul
            if (match.indexOf('<ul>') === -1) {
                return '<ol>' + match + '</ol>';
            }
            return match;
        });
        
        // Split by double newlines to get paragraphs
        var lines = content.split(/\n\n+/);
        
        // Process each block
        var formatted = lines.map(function(block) {
            block = block.trim();
            if (!block) return '';
            
            // If already has block-level HTML tags, return as is
            if (block.match(/^<(h[2-5]|ul|ol|div|blockquote)/i)) {
                return block;
            }
            
            // If it's a list item without wrapper, return as is (will be wrapped above)
            if (block.match(/^<li>/i)) {
                return block;
            }
            
            // Otherwise wrap in paragraph
            return '<p>' + block.replace(/\n/g, '<br>') + '</p>';
        });
        
        // Filter empty blocks and join
        formatted = formatted.filter(function(block) {
            return block.length > 0;
        }).join('\n');
        
        return formatted;
    }
    
    /**
     * Truncate text
     */
    function truncateText(text, maxLength) {
        if (text.length <= maxLength) return text;
        return text.substring(0, maxLength) + '...';
    }
    
    /**
     * Escape HTML for data attributes
     */
    function escapeHtml(text) {
        return text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
    
    /**
     * Count words in text
     */
    function countWords(text) {
        if (!text || typeof text !== 'string') return 0;
        
        // Remove HTML tags
        text = text.replace(/<[^>]*>/g, ' ');
        
        // Remove extra whitespace
        text = text.trim().replace(/\s+/g, ' ');
        
        // Count words
        if (text.length === 0) return 0;
        return text.split(' ').length;
    }
    
})(jQuery);

    /**
     * Update GEO Analysis (Sprint 3 - Task 3)
     */
    function updateGEOAnalysis(geo) {
        // Update GEO Score Circle
        const $geoCircle = $('#dodo-geo-progress');
        const $geoScore = $('#dodo-geo-score');
        
        $geoScore.text(Math.round(geo.geo_score));
        
        // Calculate circle progress (circumference = 2 * π * r = 339.292)
        const circumference = 339.292;
        const offset = circumference - (geo.geo_score / 100) * circumference;
        $geoCircle.css('stroke-dashoffset', offset);
        
        // Update sub-scores
        updateGEOMetric('answerability', geo.ai_answerability);
        updateGEOMetric('citation', geo.citation_potential);
        updateGEOMetric('chunk', geo.chunk_quality);
        updateGEOMetric('authority', geo.semantic_authority);
        updateGEOMetric('retrieval', geo.retrieval_friendliness);
        updateGEOMetric('llm-readability', geo.llm_readability);
        updateGEOMetric('extraction', geo.answer_extraction);
        updateGEOMetric('conversational', geo.conversational_coverage);
        
        // Display suggestions
        displayGEOSuggestions(geo.suggestions || []);
    }
    
    /**
     * Update GEO Metric
     */
    function updateGEOMetric(metric, score) {
        const $value = $('#dodo-geo-' + metric);
        const $bar = $('#dodo-geo-' + metric + '-bar');
        
        $value.text(Math.round(score));
        $bar.css('width', score + '%');
    }
    
    /**
     * Display GEO Suggestions
     */
    function displayGEOSuggestions(suggestions) {
        const $list = $('#dodo-geo-suggestions-list');
        $list.empty();
        
        if (!suggestions || suggestions.length === 0) {
            $list.html('<div class="dodo-geo-suggestion-item">Tüm GEO metrikleri iyi durumda</div>');
            return;
        }
        
        suggestions.forEach(function(suggestion) {
            $list.append(`<div class="dodo-geo-suggestion-item">${suggestion}</div>`);
        });
    }

    /**
     * Load Smart Internal Links (Sprint 3 - Task 4)
     */
    function loadSmartLinks() {
        if (!window.currentPostId) {
            return;
        }
        
        $.ajax({
            url: dodoImprover.ajaxUrl,
            type: 'POST',
            data: {
                action: 'dodo_get_link_suggestions',
                nonce: dodoImprover.nonce,
                post_id: window.currentPostId,
                focus_keyword: window.currentFocusKeyword
            },
            success: function(response) {
                if (response.success) {
                    displayLinkSuggestions(response.data.suggestions);
                    displayAuthorityFlow(response.data.authority_flow);
                }
            },
            error: function() {
                console.error('Link suggestions yüklenemedi');
            }
        });
    }
    
    /**
     * Display Link Suggestions
     */
    function displayLinkSuggestions(suggestions) {
        const $list = $('#dodo-link-suggestions-list');
        const $count = $('#dodo-link-count');
        
        $list.empty();
        $count.text(suggestions.length);
        
        if (!suggestions || suggestions.length === 0) {
            $list.html('<div class="dodo-link-suggestions-empty">İlgili içerik bulunamadı</div>');
            return;
        }
        
        suggestions.forEach(function(link) {
            const html = `
                <div class="dodo-link-suggestion-item" data-url="${link.post_url}">
                    <div class="dodo-link-suggestion-header">
                        <div class="dodo-link-suggestion-title">${link.post_title}</div>
                        <span class="dodo-link-confidence">${link.confidence_score}%</span>
                    </div>
                    <div class="dodo-link-explanation">${link.explanation}</div>
                    <div class="dodo-link-badges">
                        <span class="dodo-link-badge topical">${link.topical_relation}</span>
                        <span class="dodo-link-badge anchor">Anchor: ${link.anchor_quality}</span>
                        <span class="dodo-link-badge type">${getLinkTypeLabel(link.link_type)}</span>
                    </div>
                    ${renderAnchorSuggestions(link.anchor_suggestions)}
                </div>
            `;
            
            $list.append(html);
        });
        
        // Click handler to copy link
        $('.dodo-link-suggestion-item').on('click', function() {
            const url = $(this).data('url');
            copyToClipboard(url);
            DodoToast.success('Link Kopyalandı', url);
        });
    }
    
    /**
     * Render Anchor Suggestions
     */
    function renderAnchorSuggestions(anchors) {
        if (!anchors || anchors.length === 0) {
            return '';
        }
        
        let html = '<div class="dodo-link-anchors">';
        html += '<div class="dodo-link-anchors-title">Önerilen Anchor Metinler</div>';
        
        anchors.forEach(function(anchor) {
            html += `<span class="dodo-anchor-suggestion" data-text="${anchor.text}">${anchor.text}</span>`;
        });
        
        html += '</div>';
        return html;
    }
    
    /**
     * Get Link Type Label
     */
    function getLinkTypeLabel(type) {
        const labels = {
            'pillar': 'Pillar İçerik',
            'supporting': 'Destekleyici',
            'related': 'İlgili İçerik'
        };
        return labels[type] || type;
    }
    
    /**
     * Display Authority Flow
     */
    function displayAuthorityFlow(flow) {
        $('#dodo-flow-score').text(flow.flow_score);
        $('#dodo-internal-links').text(flow.internal_links);
        $('#dodo-inbound-links').text(flow.inbound_links);
        
        // Show orphan warning if no inbound links
        if (flow.inbound_links === 0) {
            $('#dodo-orphan-warning').fadeIn();
        } else {
            $('#dodo-orphan-warning').hide();
        }
    }
    
    /**
     * Copy to Clipboard
     */
    function copyToClipboard(text) {
        const $temp = $('<textarea>');
        $('body').append($temp);
        $temp.val(text).select();
        document.execCommand('copy');
        $temp.remove();
    }
    
    /**
     * Refresh Links Button
     */
    $(document).on('click', '#dodo-refresh-links', function() {
        $(this).addClass('rotating');
        loadSmartLinks();
        setTimeout(() => {
            $(this).removeClass('rotating');
        }, 1000);
    });
    
    /**
     * Load smart links after intelligence is displayed
     */
    $(document).on('dodo:intelligence:updated', function() {
        loadSmartLinks();
    });

    
    /**
     * Update Entity Enrichment Panel (Sprint 3 - Task 2)
     */
    function updateEntityEnrichment(data) {
        // Semantic Coverage
        const semanticCoverage = data.semantic_coverage || 0;
        $('#dodo-semantic-coverage').text(semanticCoverage.toFixed(0));
        $('#dodo-semantic-coverage-bar').css('width', semanticCoverage + '%');
        
        // Topical Completeness
        const topicalCompleteness = data.topical_completeness || 0;
        $('#dodo-topical-completeness').text(topicalCompleteness.toFixed(0));
        $('#dodo-topical-completeness-bar').css('width', topicalCompleteness + '%');
        
        // Detected Entities
        const entities = data.entities || [];
        let entitiesHTML = '';
        
        if (entities.length > 0) {
            entities.forEach(function(entity) {
                entitiesHTML += '<div class="dodo-entity-badge">';
                entitiesHTML += '<span class="dodo-entity-name">' + entity.name + '</span>';
                entitiesHTML += '<span class="dodo-entity-count">' + entity.frequency + 'x</span>';
                entitiesHTML += '</div>';
            });
        } else {
            entitiesHTML = '<div class="dodo-empty-state">No entities detected</div>';
        }
        
        $('#dodo-entities-items').html(entitiesHTML);
        
        // Enrichment Suggestions
        const suggestions = data.enrichment_suggestions || [];
        let suggestionsHTML = '';
        
        if (suggestions.length > 0) {
            suggestions.forEach(function(suggestion) {
                suggestionsHTML += '<div class="dodo-enrichment-item">';
                suggestionsHTML += '<span class="dodo-enrichment-icon">💡</span>';
                suggestionsHTML += '<span class="dodo-enrichment-text">' + suggestion + '</span>';
                suggestionsHTML += '</div>';
            });
        } else {
            suggestionsHTML = '<div class="dodo-empty-state">Content is well-enriched</div>';
        }
        
        $('#dodo-enrichment-list').html(suggestionsHTML);
    }

    /**
     * Update Workflow Status Panel (Sprint 3 - Task 3)
     */
    function updateWorkflowStatus(data) {
        const status = data.status || 'draft';
        const readinessScore = data.readiness_score || 0;
        const isReady = data.is_ready || false;
        const blockingWarnings = data.blocking_warnings || [];
        
        // Workflow badge colors
        const badgeColors = {
            'draft': '#6b7280',
            'ai_reviewed': '#3b82f6',
            'seo_approved': '#10b981',
            'geo_approved': '#8b5cf6',
            'ready_to_publish': '#059669',
            'scheduled': '#f59e0b',
            'published': '#10b981'
        };
        
        // Workflow badge labels
        const badgeLabels = {
            'draft': 'Taslak',
            'ai_reviewed': 'AI İncelendi',
            'seo_approved': 'SEO Onaylandı',
            'geo_approved': 'GEO Onaylandı',
            'ready_to_publish': 'Yayına Hazır',
            'scheduled': 'Zamanlandı',
            'published': 'Yayında'
        };
        
        // Update badge
        const badgeColor = badgeColors[status] || badgeColors['draft'];
        const badgeLabel = badgeLabels[status] || badgeLabels['draft'];
        
        $('#dodo-workflow-badge')
            .css('background-color', badgeColor)
            .find('.dodo-workflow-badge-label')
            .text(badgeLabel);
        
        // Update readiness score
        $('#dodo-readiness-score').text(readinessScore + '%');
        $('#dodo-readiness-bar').css('width', readinessScore + '%');
        
        // Update readiness bar color
        let barColor = '#ef4444'; // red
        if (readinessScore >= 70) {
            barColor = '#10b981'; // green
        } else if (readinessScore >= 50) {
            barColor = '#f59e0b'; // orange
        }
        $('#dodo-readiness-bar').css('background-color', barColor);
        
        // Update blocking warnings
        if (blockingWarnings.length > 0) {
            let warningsHTML = '<div class="dodo-workflow-warnings-title">⚠️ Engeller:</div>';
            blockingWarnings.forEach(function(warning) {
                warningsHTML += '<div class="dodo-workflow-warning-item">' + warning + '</div>';
            });
            $('#dodo-workflow-warnings').html(warningsHTML).show();
        } else {
            $('#dodo-workflow-warnings').hide();
        }
    }

    // ============================================
    // SPRINT 5: GEO & HUMANIZATION FUNCTIONS
    // ============================================
    
    /**
     * Show Sprint 5 panels after content analysis
     */
    function showSprint5Panels() {
        $('#dodo-geo-panel').slideDown();
        $('#dodo-humanization-panel').slideDown();
    }
    
    /**
     * Run GEO Analysis
     */
    $('#dodo-run-geo-analysis').on('click', function() {
        const postId = $('#dodo-post-select').val();
        const focusKeyword = $('#dodo-focus-keyword').val();
        
        if (!postId) {
            alert('Lütfen önce bir içerik seçin');
            return;
        }
        
        const $btn = $(this);
        $btn.prop('disabled', true).html('<span class="spinner is-active"></span> Analiz ediliyor...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'dodo_run_geo_analysis',
                post_id: postId,
                focus_keyword: focusKeyword,
                nonce: dodoImproverData.nonce
            },
            success: function(response) {
                if (response.success) {
                    displayGeoResults(response.data);
                } else {
                    alert('Hata: ' + (response.data.message || 'Bilinmeyen hata'));
                }
            },
            error: function() {
                alert('AJAX hatası oluştu');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> GEO Analizi Yap');
            }
        });
    });
    
    /**
     * Display GEO analysis results
     */
    function displayGeoResults(data) {
        // Update scores
        $('#dodo-geo-score').text(data.geo_score || '--');
        $('#dodo-geo-answerability').text(data.answerability_score || '--');
        $('#dodo-geo-citation').text(data.citation_potential || '--');
        $('#dodo-geo-retrieval').text(data.retrieval_friendliness || '--');
        $('#dodo-geo-passage').text(data.passage_extraction || '--');
        $('#dodo-geo-ai-overview').text(data.ai_overview_compatibility || '--');
        
        // Update bars
        updateMetricBar('#dodo-geo-score-bar', data.geo_score);
        updateMetricBar('#dodo-geo-answerability-bar', data.answerability_score);
        updateMetricBar('#dodo-geo-citation-bar', data.citation_potential);
        updateMetricBar('#dodo-geo-retrieval-bar', data.retrieval_friendliness);
        updateMetricBar('#dodo-geo-passage-bar', data.passage_extraction);
        updateMetricBar('#dodo-geo-ai-overview-bar', data.ai_overview_compatibility);
        
        // Display recommendations
        if (data.recommendations && data.recommendations.length > 0) {
            let html = '<div class="dodo-geo-recommendations-title">📋 Öneriler:</div>';
            data.recommendations.forEach(function(rec) {
                html += '<div class="dodo-geo-recommendation-item">' + rec + '</div>';
            });
            $('#dodo-geo-recommendations').html(html).show();
        }
    }
    
    /**
     * Run Humanization Analysis
     */
    $('#dodo-run-humanization-analysis').on('click', function() {
        const postId = $('#dodo-post-select').val();
        
        if (!postId) {
            alert('Lütfen önce bir içerik seçin');
            return;
        }
        
        const $btn = $(this);
        $btn.prop('disabled', true).html('<span class="spinner is-active"></span> Analiz ediliyor...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'dodo_run_humanization_analysis',
                post_id: postId,
                nonce: dodoImproverData.nonce
            },
            success: function(response) {
                if (response.success) {
                    displayHumanizationResults(response.data);
                    $('#dodo-apply-humanization').show();
                } else {
                    alert('Hata: ' + (response.data.message || 'Bilinmeyen hata'));
                }
            },
            error: function() {
                alert('AJAX hatası oluştu');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg> İnsanileştirme Analizi Yap');
            }
        });
    });
    
    /**
     * Display Humanization analysis results
     */
    function displayHumanizationResults(data) {
        // Update scores
        $('#dodo-humanization-score').text(data.humanization_score || '--');
        $('#dodo-humanization-robotic').text(data.robotic_score || '--');
        $('#dodo-humanization-rhythm').text(data.sentence_rhythm || '--');
        $('#dodo-humanization-burstiness').text(data.burstiness_score || '--');
        $('#dodo-humanization-transition').text(data.transition_quality || '--');
        
        // Update bars
        updateMetricBar('#dodo-humanization-score-bar', data.humanization_score);
        updateMetricBar('#dodo-humanization-robotic-bar', data.robotic_score);
        updateMetricBar('#dodo-humanization-rhythm-bar', data.sentence_rhythm);
        updateMetricBar('#dodo-humanization-burstiness-bar', data.burstiness_score);
        updateMetricBar('#dodo-humanization-transition-bar', data.transition_quality);
        
        // Display suggestions
        if (data.suggestions && data.suggestions.length > 0) {
            let html = '<div class="dodo-humanization-suggestions-title">💡 Öneriler:</div>';
            data.suggestions.forEach(function(suggestion) {
                html += '<div class="dodo-humanization-suggestion-item">' + suggestion + '</div>';
            });
            $('#dodo-humanization-suggestions').html(html).show();
        }
    }
    
    /**
     * Apply Humanization
     */
    $('#dodo-apply-humanization').on('click', function() {
        const postId = $('#dodo-post-select').val();
        const preset = $('#dodo-humanization-preset').val();
        
        if (!postId) {
            alert('Lütfen önce bir içerik seçin');
            return;
        }
        
        if (!confirm('İçeriğe kontrollü insanileştirme uygulanacak. Devam edilsin mi?')) {
            return;
        }
        
        const $btn = $(this);
        $btn.prop('disabled', true).html('<span class="spinner is-active"></span> Uygulanıyor...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'dodo_apply_humanization',
                post_id: postId,
                preset: preset,
                nonce: dodoImproverData.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('✅ İnsanileştirme başarıyla uygulandı!');
                    // Refresh analysis
                    $('#dodo-analyze-content').click();
                } else {
                    alert('Hata: ' + (response.data.message || 'Bilinmeyen hata'));
                }
            },
            error: function() {
                alert('AJAX hatası oluştu');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg> Kontrollü İnsanileştir');
            }
        });
    });
    
    /**
     * Helper: Update metric bar
     */
    function updateMetricBar(selector, value) {
        const $bar = $(selector);
        $bar.css('width', value + '%');
        
        // Color based on value
        let color = '#ef4444'; // red
        if (value >= 70) {
            color = '#10b981'; // green
        } else if (value >= 50) {
            color = '#f59e0b'; // orange
        }
        $bar.css('background-color', color);
    }
    
    // Show Sprint 5 panels after content analysis
    $(document).on('dodo:content-analyzed', function() {
        showSprint5Panels();
    });
