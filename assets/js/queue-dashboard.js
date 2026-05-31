/**
 * Queue Dashboard JavaScript
 * 
 * @package DODO_AI_SEO
 * @since 2.0.0
 */

(function($) {
    'use strict';

    const QueueDashboard = {
        currentFilter: {
            status: 'all',
            type: 'all'
        },
        
        refreshInterval: null,

        init: function() {
            this.bindEvents();
            this.loadQueue();
            this.startAutoRefresh();
        },

        bindEvents: function() {
            $('#queue-status-filter').on('change', this.handleFilterChange.bind(this));
            $('#queue-type-filter').on('change', this.handleFilterChange.bind(this));
            $('#refresh-queue').on('click', this.loadQueue.bind(this));
            $('#clear-completed').on('click', this.clearCompleted.bind(this));
            
            // Modal close
            $(document).on('click', '.dodo-modal-close, .dodo-modal-overlay', this.closeModal.bind(this));
            
            // Job actions
            $(document).on('click', '.retry-job', this.retryJob.bind(this));
            $(document).on('click', '.cancel-job', this.cancelJob.bind(this));
            $(document).on('click', '.view-job', this.viewJob.bind(this));
        },

        handleFilterChange: function() {
            this.currentFilter.status = $('#queue-status-filter').val();
            this.currentFilter.type = $('#queue-type-filter').val();
            this.loadQueue();
        },

        loadQueue: function() {
            const $tbody = $('#queue-table-body');
            
            $.ajax({
                url: dodoQueue.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'dodo_get_queue_jobs',
                    nonce: dodoQueue.nonce,
                    status: this.currentFilter.status,
                    type: this.currentFilter.type
                },
                success: (response) => {
                    if (response.success) {
                        this.renderQueue(response.data.jobs);
                        this.updateStats(response.data.stats);
                    } else {
                        this.showError($tbody, response.data.message || 'Kuyruk yüklenemedi');
                    }
                },
                error: () => {
                    this.showError($tbody, 'Bağlantı hatası');
                }
            });
        },

        renderQueue: function(jobs) {
            const $tbody = $('#queue-table-body');
            
            if (jobs.length === 0) {
                $tbody.html('<tr class="empty-row"><td colspan="8">Kuyrukta iş bulunmuyor</td></tr>');
                return;
            }
            
            let html = '';
            jobs.forEach(job => {
                const statusClass = this.getStatusClass(job.status);
                const typeLabel = this.getTypeLabel(job.job_type);
                const progress = job.progress || 0;
                
                html += `
                    <tr class="job-row" data-job-id="${job.id}">
                        <td><span style="font-variant-numeric:tabular-nums;font-weight:500;color:var(--dodo-text-tertiary)">#${job.id}</span></td>
                        <td><span class="job-type-badge">${typeLabel}</span></td>
                        <td><span class="status-badge ${statusClass}">${this.getStatusLabel(job.status)}</span></td>
                        <td>
                            <div class="progress-bar-wrapper">
                                <div class="progress-bar" style="width: ${progress}%"></div>
                            </div>
                            <span class="progress-text">${progress}%</span>
                        </td>
                        <td class="col-priority"><span class="priority-badge priority-${job.priority}">${job.priority}</span></td>
                        <td class="col-retries" style="color:var(--dodo-text-tertiary);font-variant-numeric:tabular-nums">${job.retry_count}/3</td>
                        <td class="col-created" style="color:var(--dodo-text-tertiary);white-space:nowrap">${this.formatDate(job.created_at)}</td>
                        <td class="actions-cell">
                            ${this.renderActions(job)}
                        </td>
                    </tr>
                `;
            });
            
            $tbody.html(html);
        },

        renderActions: function(job) {
            // SVG icons — no emojis
            const iconEye = '<svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
            const iconRetry = '<svg viewBox="0 0 24 24"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4.95"/></svg>';
            const iconCancel = '<svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>';

            let actions = '<div class="job-actions">';

            actions += `<button type="button" class="action-btn view-job" data-job-id="${job.id}" title="Detayları Görüntüle" aria-label="Detayları Görüntüle">${iconEye}</button>`;

            if (job.status === 'failed') {
                actions += `<button type="button" class="action-btn retry-job" data-job-id="${job.id}" title="Tekrar Dene" aria-label="Tekrar Dene">${iconRetry}</button>`;
            }

            if (job.status === 'pending' || job.status === 'processing') {
                actions += `<button type="button" class="action-btn cancel-job" data-job-id="${job.id}" title="İptal Et" aria-label="İptal Et">${iconCancel}</button>`;
            }

            actions += '</div>';
            return actions;
        },

        updateStats: function(stats) {
            const $cards = $('.dodo-stat-card');
            $cards.eq(0).find('.dodo-stat-value').text(stats.pending || 0);
            $cards.eq(1).find('.dodo-stat-value').text(stats.processing || 0);
            $cards.eq(2).find('.dodo-stat-value').text(stats.completed || 0);
            $cards.eq(3).find('.dodo-stat-value').text(stats.failed || 0);
        },

        retryJob: function(e) {
            const jobId = $(e.currentTarget).data('job-id');
            
            if (!confirm('Bu işi tekrar denemek istediğinizden emin misiniz?')) {
                return;
            }
            
            $.ajax({
                url: dodoQueue.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'dodo_retry_queue_job',
                    nonce: dodoQueue.nonce,
                    job_id: jobId
                },
                success: (response) => {
                    if (response.success) {
                        this.showToast('İş kuyruğa eklendi', 'success');
                        this.loadQueue();
                    } else {
                        this.showToast(response.data.message || 'İşlem başarısız', 'error');
                    }
                }
            });
        },

        cancelJob: function(e) {
            const jobId = $(e.currentTarget).data('job-id');
            
            if (!confirm('Bu işi iptal etmek istediğinizden emin misiniz?')) {
                return;
            }
            
            $.ajax({
                url: dodoQueue.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'dodo_cancel_queue_job',
                    nonce: dodoQueue.nonce,
                    job_id: jobId
                },
                success: (response) => {
                    if (response.success) {
                        this.showToast('İş iptal edildi', 'success');
                        this.loadQueue();
                    } else {
                        this.showToast(response.data.message || 'İşlem başarısız', 'error');
                    }
                }
            });
        },

        viewJob: function(e) {
            const jobId = $(e.currentTarget).data('job-id');
            
            $.ajax({
                url: dodoQueue.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'dodo_get_queue_job_detail',
                    nonce: dodoQueue.nonce,
                    job_id: jobId
                },
                success: (response) => {
                    if (response.success) {
                        this.showJobDetail(response.data.job);
                    } else {
                        this.showToast(response.data.message || 'Detaylar yüklenemedi', 'error');
                    }
                }
            });
        },

        showJobDetail: function(job) {
            const $content = $('#job-detail-content');
            
            let html = `
                <div class="job-detail-grid">
                    <div class="detail-item">
                        <label>ID:</label>
                        <span>#${job.id}</span>
                    </div>
                    <div class="detail-item">
                        <label>Tip:</label>
                        <span>${this.getTypeLabel(job.job_type)}</span>
                    </div>
                    <div class="detail-item">
                        <label>Durum:</label>
                        <span class="status-badge ${this.getStatusClass(job.status)}">${this.getStatusLabel(job.status)}</span>
                    </div>
                    <div class="detail-item">
                        <label>İlerleme:</label>
                        <span>${job.progress || 0}%</span>
                    </div>
                    <div class="detail-item">
                        <label>Öncelik:</label>
                        <span>${job.priority}</span>
                    </div>
                    <div class="detail-item">
                        <label>Deneme Sayısı:</label>
                        <span>${job.retry_count}/3</span>
                    </div>
                    <div class="detail-item">
                        <label>Oluşturulma:</label>
                        <span>${this.formatDate(job.created_at)}</span>
                    </div>
                    <div class="detail-item">
                        <label>Güncelleme:</label>
                        <span>${this.formatDate(job.updated_at)}</span>
                    </div>
                </div>
            `;
            
            if (job.job_data) {
                html += '<div class="detail-section"><h3>İş Verisi</h3><pre>' + JSON.stringify(JSON.parse(job.job_data), null, 2) + '</pre></div>';
            }
            
            if (job.result) {
                html += '<div class="detail-section"><h3>Sonuç</h3><pre>' + JSON.stringify(JSON.parse(job.result), null, 2) + '</pre></div>';
            }
            
            if (job.error_message) {
                html += '<div class="detail-section error"><h3>Hata Mesajı</h3><p>' + job.error_message + '</p></div>';
            }
            
            $content.html(html);
            $('#job-detail-modal').fadeIn(200);
        },

        closeModal: function() {
            $('#job-detail-modal').fadeOut(200);
        },

        clearCompleted: function() {
            if (!confirm('Tamamlanan tüm işleri temizlemek istediğinizden emin misiniz?')) {
                return;
            }
            
            $.ajax({
                url: dodoQueue.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'dodo_clear_completed_jobs',
                    nonce: dodoQueue.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showToast(response.data.message, 'success');
                        this.loadQueue();
                    } else {
                        this.showToast(response.data.message || 'İşlem başarısız', 'error');
                    }
                }
            });
        },

        startAutoRefresh: function() {
            this.refreshInterval = setInterval(() => {
                this.loadQueue();
            }, 10000); // 10 seconds
        },

        getStatusClass: function(status) {
            const classes = {
                'pending': 'status-pending',
                'processing': 'status-processing',
                'completed': 'status-completed',
                'failed': 'status-failed',
                'cancelled': 'status-cancelled'
            };
            return classes[status] || '';
        },

        getStatusLabel: function(status) {
            const labels = {
                'pending': 'Bekliyor',
                'processing': 'İşleniyor',
                'completed': 'Tamamlandı',
                'failed': 'Başarısız',
                'cancelled': 'İptal Edildi'
            };
            return labels[status] || status;
        },

        getTypeLabel: function(type) {
            const labels = {
                'blog_generation': 'Blog Üretimi',
                'analytics_snapshot': 'Analytics',
                'cluster_analysis': 'Cluster Analizi',
                'content_audit': 'İçerik Denetimi',
                'bulk_improvement': 'Toplu İyileştirme'
            };
            return labels[type] || type;
        },

        formatDate: function(dateString) {
            const date = new Date(dateString);
            return date.toLocaleString('tr-TR');
        },

        showError: function($el, message) {
            $el.html(`<tr class="error-row"><td colspan="8">${message}</td></tr>`);
        },

        showToast: function(message, type) {
            const $toast = $(`<div class="dodo-queue-toast ${type}">${message}</div>`);
            $('body').append($toast);
            setTimeout(() => {
                $toast.fadeOut(200, function() {
                    $(this).remove();
                });
            }, 3000);
        }
    };

    $(document).ready(function() {
        if ($('.dodo-queue-dashboard').length) {
            QueueDashboard.init();
        }
    });

})(jQuery);
