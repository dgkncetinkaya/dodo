/**
 * Bulk Operations JavaScript
 * 
 * @package DODO_AI_SEO
 * @since 2.0.0
 */

(function($) {
    'use strict';

    const BulkOperations = {
        currentOperation: null,
        totalItems: 0,
        processedItems: 0,
        failedItems: 0,
        cancelled: false,

        init: function() {
            this.loadPosts();
            this.bindEvents();
        },

        bindEvents: function() {
            $('#start-bulk-analyze').on('click', this.startBulkAnalyze.bind(this));
            $('#start-bulk-improve').on('click', this.startBulkImprove.bind(this));
            $('#start-bulk-geo').on('click', this.startBulkGeo.bind(this));
            $('#start-bulk-links').on('click', this.startBulkLinks.bind(this));
            $('#cancel-bulk-operation').on('click', this.cancelOperation.bind(this));
        },

        loadPosts: function() {
            $.ajax({
                url: dodoBulk.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'dodo_get_posts_for_bulk',
                    nonce: dodoBulk.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.populatePostSelects(response.data.posts);
                    }
                }
            });
        },

        populatePostSelects: function(posts) {
            const selects = [
                '#bulk-analyze-posts',
                '#bulk-improve-posts',
                '#bulk-geo-posts',
                '#bulk-links-posts'
            ];
            
            let options = '';
            posts.forEach(post => {
                options += `<option value="${post.ID}">${post.post_title} (ID: ${post.ID})</option>`;
            });
            
            selects.forEach(selector => {
                $(selector).html(options);
            });
        },

        startBulkAnalyze: function() {
            const selectedPosts = $('#bulk-analyze-posts').val();
            
            if (!selectedPosts || selectedPosts.length === 0) {
                alert('Lütfen en az bir içerik seçin');
                return;
            }
            
            this.startOperation('analyze', {
                post_ids: selectedPosts
            });
        },

        startBulkImprove: function() {
            const selectedPosts = $('#bulk-improve-posts').val();
            const improvementType = $('#improvement-type').val();
            
            if (!selectedPosts || selectedPosts.length === 0) {
                alert('Lütfen en az bir içerik seçin');
                return;
            }
            
            this.startOperation('improve', {
                post_ids: selectedPosts,
                improvement_type: improvementType
            });
        },

        startBulkGeo: function() {
            const selectedPosts = $('#bulk-geo-posts').val();
            const targetCountry = $('#target-country').val();
            
            if (!selectedPosts || selectedPosts.length === 0) {
                alert('Lütfen en az bir içerik seçin');
                return;
            }
            
            this.startOperation('geo', {
                post_ids: selectedPosts,
                target_country: targetCountry
            });
        },

        startBulkLinks: function() {
            const selectedPosts = $('#bulk-links-posts').val();
            
            if (!selectedPosts || selectedPosts.length === 0) {
                alert('Lütfen en az bir içerik seçin');
                return;
            }
            
            this.startOperation('links', {
                post_ids: selectedPosts
            });
        },

        startOperation: function(type, data) {
            this.currentOperation = type;
            this.totalItems = data.post_ids.length;
            this.processedItems = 0;
            this.failedItems = 0;
            this.cancelled = false;
            
            // Show modal
            $('#bulk-progress-modal').fadeIn(200);
            $('#total-items').text(this.totalItems);
            $('#processed-items').text(0);
            $('#failed-items').text(0);
            $('#bulk-progress-bar').css('width', '0%');
            $('#bulk-progress-text').text('0%');
            $('#progress-log').html('');
            
            // Estimate cost and time
            this.estimateCostAndTime(type, this.totalItems);
            
            // Start processing
            this.processNextBatch(type, data.post_ids, data);
        },

        processNextBatch: function(type, postIds, extraData) {
            if (this.cancelled) {
                this.logMessage('İşlem iptal edildi', 'warning');
                return;
            }
            
            if (postIds.length === 0) {
                this.completeOperation();
                return;
            }
            
            // Process in batches of 5
            const batchSize = 5;
            const batch = postIds.slice(0, batchSize);
            const remaining = postIds.slice(batchSize);
            
            $.ajax({
                url: dodoBulk.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'dodo_process_bulk_batch',
                    nonce: dodoBulk.nonce,
                    operation_type: type,
                    post_ids: batch,
                    extra_data: extraData
                },
                success: (response) => {
                    if (response.success) {
                        this.processedItems += response.data.processed;
                        this.failedItems += response.data.failed;
                        
                        // Update progress
                        this.updateProgress();
                        
                        // Log results
                        response.data.results.forEach(result => {
                            if (result.success) {
                                this.logMessage(`✓ ${result.post_title} - Başarılı`, 'success');
                            } else {
                                this.logMessage(`✗ ${result.post_title} - ${result.error}`, 'error');
                            }
                        });
                        
                        // Process next batch
                        setTimeout(() => {
                            this.processNextBatch(type, remaining, extraData);
                        }, 1000);
                    } else {
                        this.logMessage('Batch işleme hatası: ' + response.data.message, 'error');
                        this.failedItems += batch.length;
                        this.updateProgress();
                        
                        // Continue with next batch
                        setTimeout(() => {
                            this.processNextBatch(type, remaining, extraData);
                        }, 1000);
                    }
                },
                error: () => {
                    this.logMessage('Bağlantı hatası', 'error');
                    this.failedItems += batch.length;
                    this.updateProgress();
                    
                    // Continue with next batch
                    setTimeout(() => {
                        this.processNextBatch(type, remaining, extraData);
                    }, 1000);
                }
            });
        },

        updateProgress: function() {
            const progress = Math.round((this.processedItems / this.totalItems) * 100);
            
            $('#processed-items').text(this.processedItems);
            $('#failed-items').text(this.failedItems);
            $('#bulk-progress-bar').css('width', progress + '%');
            $('#bulk-progress-text').text(progress + '%');
        },

        completeOperation: function() {
            this.logMessage('Toplu işlem tamamlandı!', 'success');
            
            setTimeout(() => {
                $('#bulk-progress-modal').fadeOut(200);
                this.currentOperation = null;
            }, 2000);
        },

        cancelOperation: function() {
            if (confirm('İşlemi iptal etmek istediğinizden emin misiniz?')) {
                this.cancelled = true;
                this.logMessage('İşlem iptal ediliyor...', 'warning');
            }
        },

        estimateCostAndTime: function(type, count) {
            // Rough estimates
            const timePerItem = {
                'analyze': 5,
                'improve': 15,
                'geo': 10,
                'links': 8
            };
            
            const costPerItem = {
                'analyze': 0.02,
                'improve': 0.10,
                'geo': 0.05,
                'links': 0.03
            };
            
            const estimatedSeconds = (timePerItem[type] || 10) * count;
            const estimatedCost = (costPerItem[type] || 0.05) * count;
            
            const minutes = Math.floor(estimatedSeconds / 60);
            const seconds = estimatedSeconds % 60;
            
            $('#estimated-time').text(`${minutes}dk ${seconds}sn`);
            $('#estimated-cost').text(`$${estimatedCost.toFixed(2)}`);
        },

        logMessage: function(message, type) {
            const $log = $('#progress-log');
            const timestamp = new Date().toLocaleTimeString('tr-TR');
            const typeClass = type || 'info';
            
            $log.append(`<div class="log-entry ${typeClass}">[${timestamp}] ${message}</div>`);
            $log.scrollTop($log[0].scrollHeight);
        }
    };

    $(document).ready(function() {
        if ($('.dodo-bulk-operations').length) {
            BulkOperations.init();
        }
    });

})(jQuery);
