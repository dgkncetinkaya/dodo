/**
 * DODO AI SEO - Admin JavaScript
 * 
 * @package DODO_AI_SEO
 * @since 1.0.0
 */

(function($) {
    'use strict';
    
    /**
     * Toast notification göster
     */
    function showToast(type, title, message, duration = 5000) {
        // Mevcut toast'ları kaldır
        $('.dodo-toast').remove();
        
        const icon = type === 'success' ? '✓' : '✕';
        
        const $toast = $(`
            <div class="dodo-toast ${type}">
                <div class="dodo-toast-icon">${icon}</div>
                <div class="dodo-toast-content">
                    <div class="dodo-toast-title">${title}</div>
                    <div class="dodo-toast-message">${message}</div>
                </div>
                <button class="dodo-toast-close" aria-label="Kapat">×</button>
            </div>
        `);
        
        $('body').append($toast);
        
        // Kapat butonu
        $toast.find('.dodo-toast-close').on('click', function() {
            hideToast($toast);
        });
        
        // Otomatik kapat
        if (duration > 0) {
            setTimeout(function() {
                hideToast($toast);
            }, duration);
        }
    }
    
    /**
     * Toast'ı gizle
     */
    function hideToast($toast) {
        $toast.addClass('hiding');
        setTimeout(function() {
            $toast.remove();
        }, 300);
    }
    
    $(document).ready(function() {
        
        /**
         * Blog oluşturma formu
         */
        $('#dodo-blog-form').on('submit', function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $submitBtn = $form.find('.dodo-submit-btn');
            const $loading = $('#dodo-loading');
            const $result = $('#dodo-result');
            
            // Form verilerini al
            const formData = {
                action: 'dodo_generate_blog',
                nonce: dodoAjax.nonce,
                focus_keyword: $('#focus_keyword').val().trim(),
                topic: $('#topic').val().trim(),
                ai_assisted_mode: $('#ai_assisted_mode').is(':checked') ? '1' : '0',
                length: $('#length').val() || 'medium',
                tone: $('#tone').val() || 'technical',
                content_type: $('#content_type').val() || 'blog',
                cta_intensity: $('#cta_intensity').val() || 'medium',
                humanization: $('#humanization').val() || 'standard',
                category_id: $('#category_id').val(),
                opportunity_id: $('input[name="opportunity_id"]').val() || 0,
                // Advanced Controls (Task 6.3)
                publish_mode: $('#publish_mode').val() || 'draft',
                scheduled_date: $('#scheduled_date').val() || '',
                scheduled_time: $('#scheduled_time').val() || '',
                content_intent: $('#content_intent').val() || 'informational',
                expertise_depth: $('#expertise_depth').val() || 'intermediate',
                geo_optimization: $('#geo_optimization').val() || 'moderate',
                ai_naturalness: $('#ai_naturalness').val() || 'human',
                semantic_aggressiveness: $('#semantic_aggressiveness').val() || 'moderate',
                readability_target: $('#readability_target').val() || 'easy',
                answer_blocks_enabled: $('#answer_blocks_enabled').is(':checked') ? '1' : '0',
                block_short_answer: $('#block_short_answer').is(':checked') ? '1' : '0',
                block_faq: $('#block_faq').is(':checked') ? '1' : '0',
                humanization_preset: $('#humanization_preset').val() || 'standard'
            };
            
            // Focus keyword'ü konsola logla
            console.log('[DODO][AJAX][Blog] Focus keyword gönderiliyor:', formData.focus_keyword);
            
            // Validasyon - sadece focus_keyword zorunlu
            if (!formData.focus_keyword) {
                showResult('error', 'Lütfen odak anahtar kelime girin.');
                return;
            }
            
            // UI durumunu güncelle
            $submitBtn.prop('disabled', true);
            $loading.fadeIn();
            $result.hide();
            
            // AJAX isteği
            $.ajax({
                url: dodoAjax.ajaxurl,
                type: 'POST',
                data: formData,
                timeout: 300000, // 5 dakika timeout (300 seconds for long blog generation)
                success: function(response) {
                    console.log('[DODO][AJAX][Blog] Response:', response); // Debug için
                    
                    if (response.success) {
                        // Toast notification göster
                        showToast(
                            'success',
                            '✅ Blog Yazısı Oluşturuldu!',
                            'Blog yazınız başarıyla oluşturuldu. Düzenlemek için aşağıdaki butona tıklayın.',
                            8000
                        );
                        
                        showResult('success', response.data.message, {
                            post_id: response.data.post_id,
                            edit_url: response.data.edit_url,
                            view_url: response.data.view_url
                        });
                        
                        // Formu temizle
                        $form[0].reset();
                    } else {
                        // Hata mesajını göster
                        const errorMessage = response.data && response.data.message 
                            ? response.data.message 
                            : dodoAjax.strings.error;
                        
                        console.error('[DODO][AJAX][Blog] Error:', errorMessage); // Debug için
                        
                        // Toast notification göster
                        showToast(
                            'error',
                            '❌ Hata Oluştu',
                            errorMessage,
                            10000
                        );
                        
                        showResult('error', errorMessage);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('[DODO][AJAX][Blog] Error:', {xhr: xhr, status: status, error: error}); // Debug için
                    
                    let errorMessage = dodoAjax.strings.error;
                    
                    if (status === 'timeout') {
                        errorMessage = 'İstek zaman aşımına uğradı. Lütfen tekrar deneyin.';
                    } else if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        errorMessage = xhr.responseJSON.data.message;
                    } else if (xhr.responseText) {
                        console.error('[DODO][AJAX][Blog] Raw Response:', xhr.responseText); // Ham yanıtı logla
                    }
                    
                    showResult('error', errorMessage);
                },
                complete: function() {
                    $submitBtn.prop('disabled', false);
                    $loading.fadeOut();
                }
            });
        });
        
        /**
         * Sonuç mesajını göster
         */
        function showResult(type, message, data) {
            const $result = $('#dodo-result');
            
            let html = '<div class="dodo-result-title">';
            html += type === 'success' ? '✅ Başarılı!' : '❌ Hata!';
            html += '</div>';
            html += '<p>' + message + '</p>';
            
            if (type === 'success' && data) {
                html += '<div class="dodo-result-actions">';
                html += '<a href="' + data.edit_url + '" class="button button-primary">Yazıyı Düzenle</a>';
                html += '<a href="' + data.view_url + '" class="button" target="_blank">Yazıyı Görüntüle</a>';
                html += '</div>';
            }
            
            $result
                .removeClass('success error')
                .addClass(type)
                .html(html)
                .fadeIn();
            
            // Sayfayı yukarı kaydır
            $('html, body').animate({
                scrollTop: $result.offset().top - 100
            }, 500);
        }
        
        /**
         * Karakter sayacı (opsiyonel)
         */
        $('#topic').on('input', function() {
            const length = $(this).val().length;
            const $counter = $(this).siblings('.char-counter');
            
            if ($counter.length === 0) {
                $(this).after('<span class="char-counter dodo-help-text"></span>');
            }
            
            $(this).siblings('.char-counter').text(length + ' karakter');
        });
        
        /**
         * API key göster/gizle
         */
        $(document).on('click', '.dodo-toggle-api-key', function(e) {
            e.preventDefault();
            const $input = $('#openai_api_key');
            const type = $input.attr('type');
            
            if (type === 'password') {
                $input.attr('type', 'text');
                $(this).text('Gizle');
            } else {
                $input.attr('type', 'password');
                $(this).text('Göster');
            }
        });
        
        /**
         * Form değişikliklerini izle (kaydetmeden çıkma uyarısı)
         */
        let formChanged = false;
        
        $('#dodo-blog-form input, #dodo-blog-form textarea, #dodo-blog-form select').on('change', function() {
            formChanged = true;
        });
        
        $('#dodo-blog-form').on('submit', function() {
            formChanged = false;
        });
        
        $(window).on('beforeunload', function() {
            if (formChanged) {
                return 'Yaptığınız değişiklikler kaydedilmedi. Sayfadan çıkmak istediğinizden emin misiniz?';
            }
        });
        
        /**
         * Gelişmiş Ayarlar toggle - DEVRE DIŞI (inline onclick kullanılıyor)
         */
        /*
        $('#dodo-advanced-toggle').on('click', function() {
            const $section = $('#dodo-advanced-section');
            const $toggle = $(this);
            
            if ($section.is(':visible')) {
                $section.slideUp(200);
                $toggle.removeClass('active');
            } else {
                $section.slideDown(200);
                $toggle.addClass('active');
            }
        });
        */
        
    });
    
})(jQuery);
