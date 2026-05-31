/**
 * Onboarding Wizard JavaScript
 * 
 * @package DODO_AI_SEO
 * @since 2.0.0
 */

(function($) {
    'use strict';

    const DodoOnboarding = {
        init: function() {
            this.bindEvents();
            this.runSystemHealthCheck();
        },

        bindEvents: function() {
            // API Key Test
            $('#test-api-key').on('click', this.testApiKey.bind(this));
            
            // API Setup Form
            $('#dodo-api-setup-form').on('submit', this.saveApiSettings.bind(this));
            
            // Brand Setup Form
            $('#dodo-brand-setup-form').on('submit', this.saveBrandSettings.bind(this));
            
            // GEO Setup Form
            $('#dodo-geo-setup-form').on('submit', this.saveGeoSettings.bind(this));
            
            // Strategy Setup Form
            $('#dodo-strategy-setup-form').on('submit', this.saveStrategySettings.bind(this));
            
            // Complete Onboarding
            $('#complete-onboarding').on('click', this.completeOnboarding.bind(this));
        },

        testApiKey: function(e) {
            e.preventDefault();
            
            const apiKey = $('#openai_api_key').val();
            const $button = $('#test-api-key');
            const $result = $('#api-test-result');
            
            if (!apiKey) {
                this.showError($result, 'Lütfen API anahtarınızı girin.');
                return;
            }
            
            $button.prop('disabled', true).text('Test ediliyor...');
            $result.hide();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'dodo_test_api_key',
                    nonce: dodoOnboarding.nonce,
                    api_key: apiKey
                },
                success: (response) => {
                    if (response.success) {
                        this.showSuccess($result, 'API anahtarı geçerli! ✓');
                    } else {
                        this.showError($result, response.data.message || 'API anahtarı geçersiz.');
                    }
                },
                error: () => {
                    this.showError($result, 'Bağlantı hatası. Lütfen tekrar deneyin.');
                },
                complete: () => {
                    $button.prop('disabled', false).text('API Anahtarını Test Et');
                }
            });
        },

        saveApiSettings: function(e) {
            e.preventDefault();
            
            const apiKey = $('#openai_api_key').val();
            
            if (!apiKey) {
                alert('Lütfen API anahtarınızı girin.');
                return;
            }
            
            this.saveSettings({
                openai_api_key: apiKey
            }, 3);
        },

        saveBrandSettings: function(e) {
            e.preventDefault();
            
            this.saveSettings({
                default_tone: $('#default_tone').val(),
                default_length: $('#default_length').val(),
                default_status: $('#default_status').val()
            }, 5);
        },

        saveGeoSettings: function(e) {
            e.preventDefault();
            
            this.saveSettings({
                target_country: $('#target_country').val(),
                target_language: $('#target_language').val(),
                enable_geo_scoring: $('input[name="enable_geo_scoring"]').is(':checked') ? 1 : 0
            }, 6);
        },

        saveStrategySettings: function(e) {
            e.preventDefault();
            
            this.saveSettings({
                min_internal_links: $('#min_internal_links').val(),
                max_internal_links: $('#max_internal_links').val(),
                ai_model_preference: $('#ai_model_preference').val(),
                daily_token_budget: $('#daily_token_budget').val()
            }, 7);
        },

        saveSettings: function(settings, nextStep) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'dodo_save_onboarding_settings',
                    nonce: dodoOnboarding.nonce,
                    settings: settings
                },
                success: (response) => {
                    if (response.success) {
                        window.location.href = dodoOnboarding.adminUrl + 'admin.php?page=dodo-ai-seo-onboarding&step=' + nextStep;
                    } else {
                        alert(response.data.message || 'Ayarlar kaydedilemedi.');
                    }
                },
                error: () => {
                    alert('Bağlantı hatası. Lütfen tekrar deneyin.');
                }
            });
        },

        runSystemHealthCheck: function() {
            if ($('[data-step="3"]').length === 0) {
                return;
            }
            
            const $results = $('#system-health-results');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'dodo_get_system_health',
                    nonce: dodoOnboarding.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.displayHealthResults(response.data);
                    } else {
                        $results.html('<div class="error-state">Sistem kontrolü başarısız oldu.</div>');
                    }
                },
                error: () => {
                    $results.html('<div class="error-state">Bağlantı hatası.</div>');
                }
            });
        },

        displayHealthResults: function(health) {
            const $results = $('#system-health-results');
            let html = '<div class="health-checks">';
            
            const checks = [
                { key: 'api_connection', label: 'OpenAI API Bağlantısı', status: health.api_connection },
                { key: 'database', label: 'Veritabanı Tabloları', status: health.database },
                { key: 'cron', label: 'WP Cron', status: health.cron },
                { key: 'memory', label: 'PHP Memory', status: health.memory },
                { key: 'php_version', label: 'PHP Versiyonu', status: health.php_version },
                { key: 'extensions', label: 'PHP Extensions', status: health.extensions }
            ];
            
            checks.forEach(check => {
                const icon = check.status === 'healthy' ? '✓' : (check.status === 'warning' ? '⚠' : '✗');
                const statusClass = check.status === 'healthy' ? 'success' : (check.status === 'warning' ? 'warning' : 'error');
                
                html += `
                    <div class="health-check-item ${statusClass}">
                        <span class="check-icon">${icon}</span>
                        <span class="check-label">${check.label}</span>
                        <span class="check-status">${this.getStatusText(check.status)}</span>
                    </div>
                `;
            });
            
            html += '</div>';
            
            // Overall status
            const overallStatus = health.overall_status || 'healthy';
            const statusText = overallStatus === 'healthy' ? 'Sistem Hazır' : (overallStatus === 'warning' ? 'Uyarılar Var' : 'Kritik Sorunlar Var');
            const statusClass = overallStatus === 'healthy' ? 'success' : (overallStatus === 'warning' ? 'warning' : 'error');
            
            html += `<div class="overall-health ${statusClass}"><strong>${statusText}</strong></div>`;
            
            $results.html(html);
        },

        getStatusText: function(status) {
            const texts = {
                'healthy': 'Hazır',
                'warning': 'Uyarı',
                'critical': 'Hata'
            };
            return texts[status] || status;
        },

        completeOnboarding: function() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'dodo_complete_onboarding',
                    nonce: dodoOnboarding.nonce
                },
                success: (response) => {
                    if (response.success) {
                        window.location.href = dodoOnboarding.adminUrl + 'admin.php?page=dodo-ai-seo';
                    }
                }
            });
        },

        showSuccess: function($el, message) {
            $el.removeClass('error').addClass('success').html(message).fadeIn();
        },

        showError: function($el, message) {
            $el.removeClass('success').addClass('error').html(message).fadeIn();
        }
    };

    $(document).ready(function() {
        DodoOnboarding.init();
    });

})(jQuery);
