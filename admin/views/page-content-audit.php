<?php
/**
 * Content Audit Sayfası
 *
 * @package DODO_AI_SEO
 * @since 1.1.0
 */

// Doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

$audit_manager = new DODO_Content_Audit();
$posts_with_audit = $audit_manager->get_recent_posts();

// Status labels
$status_labels = array(
    'excellent' => '✅ Mükemmel',
    'good' => '👍 İyi',
    'warning' => '⚠️ Uyarı',
    'critical' => '❌ Kritik',
    'pending' => '⏳ Bekliyor',
);

// Status colors
$status_colors = array(
    'excellent' => '#00a32a',
    'good' => '#2271b1',
    'warning' => '#f0b849',
    'critical' => '#d63638',
    'pending' => '#646970',
);
?>

<div class="wrap dodo-admin-wrap">
    <!-- Premium Page Header -->
    <div class="dodo-page-header">
        <div class="dodo-page-header-content">
            <h1 class="dodo-page-title">
                <span class="dashicons dashicons-analytics"></span>
                <?php echo esc_html__('Content Audit', 'dodo-ai-seo'); ?>
            </h1>
            <p class="dodo-page-description">
                <?php echo esc_html__('Mevcut blog yazılarınızı SEO ve içerik kalitesi açısından analiz edin.', 'dodo-ai-seo'); ?>
            </p>
        </div>
    </div>
    
    <!-- İstatistikler -->
    <div class="dodo-stats-grid">
        <?php
        $total_audited = 0;
        $excellent_count = 0;
        $good_count = 0;
        $warning_count = 0;
        $critical_count = 0;
        
        foreach ($posts_with_audit as $item) {
            if ($item['audit']) {
                $total_audited++;
                switch ($item['audit']['audit_status']) {
                    case 'excellent':
                        $excellent_count++;
                        break;
                    case 'good':
                        $good_count++;
                        break;
                    case 'warning':
                        $warning_count++;
                        break;
                    case 'critical':
                        $critical_count++;
                        break;
                }
            }
        }
        ?>
        
        <div class="dodo-stat-card">
            <div><?php echo esc_html($total_audited); ?></div>
            <div>Audit Edildi</div>
        </div>
        <div class="dodo-stat-card">
            <div style="color: var(--dodo-success);"><?php echo esc_html($excellent_count); ?></div>
            <div>Mükemmel</div>
        </div>
        <div class="dodo-stat-card">
            <div style="color: var(--dodo-warning);"><?php echo esc_html($warning_count); ?></div>
            <div>Uyarı</div>
        </div>
        <div class="dodo-stat-card">
            <div style="color: var(--dodo-error);"><?php echo esc_html($critical_count); ?></div>
            <div>Kritik</div>
        </div>
    </div>
    
    <!-- Loading State -->
    <div id="dodo-audit-loading" class="dodo-loading" style="display: none;">
        <div class="dodo-spinner"></div>
        <p class="dodo-loading-text">
            <?php echo esc_html__('İçerik analiz ediliyor...', 'dodo-ai-seo'); ?>
        </p>
    </div>
    
    <!-- Result Message -->
    <div id="dodo-audit-result" class="dodo-result" style="display: none;"></div>
    
    <!-- Blog Listesi -->
    <div class="dodo-card">
        <h2 style="margin-top: 0;">Son 20 Blog Yazısı</h2>
        
        <table class="wp-list-table widefat fixed striped" id="dodo-audit-table">
            <thead>
                <tr>
                    <th style="width: 35%;">Başlık</th>
                    <th style="width: 10%;">Durum</th>
                    <th style="width: 10%;">Kelime</th>
                    <th style="width: 10%;">SEO</th>
                    <th style="width: 10%;">İçerik</th>
                    <th style="width: 10%;">Link</th>
                    <th style="width: 10%;">Overall</th>
                    <th style="width: 15%;">Aksiyon</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($posts_with_audit as $item) : 
                    $post = $item['post'];
                    $audit = $item['audit'];
                    $word_count = $item['word_count'];
                ?>
                    <tr data-post-id="<?php echo esc_attr($post->ID); ?>">
                        <td>
                            <strong>
                                <a href="<?php echo esc_url(get_edit_post_link($post->ID)); ?>" target="_blank">
                                    <?php echo esc_html($post->post_title); ?>
                                </a>
                            </strong>
                            <br>
                            <span class="description">
                                <?php echo esc_html(get_the_date('d.m.Y', $post)); ?>
                            </span>
                        </td>
                        <td class="dodo-audit-status">
                            <?php if ($audit) : 
                                $status = $audit['audit_status'];
                                $color = $status_colors[$status] ?? '#646970';
                            ?>
                                <span style="display: inline-block; background: <?php echo esc_attr($color); ?>; color: #fff; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                                    <?php echo esc_html($status_labels[$status] ?? $status); ?>
                                </span>
                            <?php else : ?>
                                <span style="color: #646970;">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="dodo-audit-word-count">
                            <?php if ($audit) : ?>
                                <?php echo esc_html(number_format($audit['word_count'])); ?>
                            <?php else : ?>
                                <?php echo esc_html(number_format($word_count)); ?>
                            <?php endif; ?>
                        </td>
                        <td class="dodo-audit-seo-score">
                            <?php if ($audit) : 
                                $seo_score = intval($audit['seo_score']);
                                $seo_color = $seo_score >= 90 ? '#00a32a' : ($seo_score >= 75 ? '#2271b1' : ($seo_score >= 60 ? '#f0b849' : '#d63638'));
                            ?>
                                <span style="display: inline-block; background: <?php echo esc_attr($seo_color); ?>; color: #fff; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">
                                    <?php echo esc_html($seo_score); ?>
                                </span>
                            <?php else : ?>
                                <span style="color: #646970;">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="dodo-audit-content-score">
                            <?php if ($audit) : 
                                $content_score = intval($audit['content_score']);
                                $content_color = $content_score >= 90 ? '#00a32a' : ($content_score >= 75 ? '#2271b1' : ($content_score >= 60 ? '#f0b849' : '#d63638'));
                            ?>
                                <span style="display: inline-block; background: <?php echo esc_attr($content_color); ?>; color: #fff; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">
                                    <?php echo esc_html($content_score); ?>
                                </span>
                            <?php else : ?>
                                <span style="color: #646970;">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="dodo-audit-link-score">
                            <?php if ($audit) : 
                                $link_score = intval($audit['link_score']);
                                $link_color = $link_score >= 90 ? '#00a32a' : ($link_score >= 75 ? '#2271b1' : ($link_score >= 60 ? '#f0b849' : '#d63638'));
                            ?>
                                <span style="display: inline-block; background: <?php echo esc_attr($link_color); ?>; color: #fff; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">
                                    <?php echo esc_html($link_score); ?>
                                </span>
                            <?php else : ?>
                                <span style="color: #646970;">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="dodo-audit-overall-score">
                            <?php if ($audit) : 
                                $overall_score = intval($audit['overall_score']);
                                $overall_color = $overall_score >= 90 ? '#00a32a' : ($overall_score >= 75 ? '#2271b1' : ($overall_score >= 60 ? '#f0b849' : '#d63638'));
                            ?>
                                <span style="display: inline-block; background: <?php echo esc_attr($overall_color); ?>; color: #fff; padding: 6px 12px; border-radius: 4px; font-size: 14px; font-weight: 600;">
                                    <?php echo esc_html($overall_score); ?>
                                </span>
                            <?php else : ?>
                                <span style="color: #646970;">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button type="button" class="button button-primary button-small dodo-run-audit" data-post-id="<?php echo esc_attr($post->ID); ?>">
                                <?php if ($audit) : ?>
                                    🔄 Tekrar Audit
                                <?php else : ?>
                                    📊 Audit Yap
                                <?php endif; ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    
    // Status labels
    const statusLabels = {
        'excellent': '✅ Mükemmel',
        'good': '👍 İyi',
        'warning': '⚠️ Uyarı',
        'critical': '❌ Kritik',
        'pending': '⏳ Bekliyor'
    };
    
    // Status colors
    const statusColors = {
        'excellent': '#00a32a',
        'good': '#2271b1',
        'warning': '#f0b849',
        'critical': '#d63638',
        'pending': '#646970'
    };
    
    // Score color helper (Recalibrated)
    function getScoreColor(score) {
        if (score >= 90) return '#00a32a'; // Excellent - Green
        if (score >= 75) return '#2271b1'; // Good - Blue
        if (score >= 60) return '#f0b849'; // Warning - Yellow
        return '#d63638'; // Critical - Red
    }
    
    // Audit yap butonu
    $('.dodo-run-audit').on('click', function() {
        const $btn = $(this);
        const postId = $btn.data('post-id');
        const $row = $btn.closest('tr');
        const $loading = $('#dodo-audit-loading');
        const $result = $('#dodo-audit-result');
        
        if (!confirm('Bu blog yazısını audit etmek istediğinizden emin misiniz?')) {
            return;
        }
        
        console.log('[DODO AUDIT] Starting audit for post #' + postId);
        
        $btn.prop('disabled', true).text('İşleniyor...');
        $loading.fadeIn();
        $result.hide();
        
        $.ajax({
            url: dodoAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'dodo_run_content_audit',
                nonce: dodoAjax.nonce,
                post_id: postId
            },
            timeout: 60000, // 1 dakika
            success: function(response) {
                console.log('[DODO AUDIT] AJAX response:', response);
                
                if (response.success) {
                    const data = response.data;
                    
                    console.log('[DODO AUDIT] Audit successful:', data);
                    console.log('[DODO AUDIT] Response data:', {
                        seo_score: data.seo_score,
                        content_score: data.content_score,
                        link_score: data.link_score,
                        overall_score: data.overall_score,
                        audit_status: data.audit_status,
                        word_count: data.word_count
                    });
                    
                    // Update UI - Word Count
                    $row.find('.dodo-audit-word-count').text(
                        parseInt(data.word_count).toLocaleString()
                    );
                    
                    // Update UI - Status column
                    const statusColor = statusColors[data.audit_status] || '#646970';
                    const statusLabel = statusLabels[data.audit_status] || data.audit_status;
                    $row.find('.dodo-audit-status').html(
                        '<span style="display: inline-block; background: ' + statusColor + '; color: #fff; padding: 4px 8px; border-radius: 4px; font-size: 12px;">' +
                        statusLabel +
                        '</span>'
                    );
                    
                    // Update UI - SEO Score
                    const seoColor = getScoreColor(data.seo_score);
                    $row.find('.dodo-audit-seo-score').html(
                        '<span style="display: inline-block; background: ' + seoColor + '; color: #fff; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">' +
                        data.seo_score +
                        '</span>'
                    );
                    
                    // Update UI - Content Score
                    const contentColor = getScoreColor(data.content_score);
                    $row.find('.dodo-audit-content-score').html(
                        '<span style="display: inline-block; background: ' + contentColor + '; color: #fff; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">' +
                        data.content_score +
                        '</span>'
                    );
                    
                    // Update UI - Link Score
                    const linkColor = getScoreColor(data.link_score);
                    $row.find('.dodo-audit-link-score').html(
                        '<span style="display: inline-block; background: ' + linkColor + '; color: #fff; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">' +
                        data.link_score +
                        '</span>'
                    );
                    
                    // Update UI - Overall Score
                    const overallColor = getScoreColor(data.overall_score);
                    $row.find('.dodo-audit-overall-score').html(
                        '<span style="display: inline-block; background: ' + overallColor + '; color: #fff; padding: 6px 12px; border-radius: 4px; font-size: 14px; font-weight: 600;">' +
                        data.overall_score +
                        '</span>'
                    );
                    
                    // Update button text
                    $btn.text('🔄 Tekrar Audit');
                    
                    // Show success message
                    $result
                        .removeClass('error')
                        .addClass('success')
                        .html('<div class="dodo-result-title">✅ Audit Tamamlandı!</div>' +
                              '<p>Overall Score: <strong>' + data.overall_score + '</strong> - ' + statusLabel + '</p>')
                        .fadeIn();
                    
                    // Hide success message after 5 seconds
                    setTimeout(function() {
                        $result.fadeOut();
                    }, 5000);
                    
                    console.log('[DODO AUDIT] UI updated successfully');
                } else {
                    console.error('[DODO AUDIT] Audit failed:', response.data);
                    
                    let errorMsg = response.data.message || 'Bir hata oluştu';
                    
                    // Detaylı hata bilgisi varsa console'a yaz
                    if (response.data.db_error) {
                        console.error('[DODO AUDIT] DB Error:', response.data.db_error);
                        errorMsg += '<br><small>DB Error: ' + response.data.db_error + '</small>';
                    }
                    if (response.data.last_query) {
                        console.error('[DODO AUDIT] Last Query:', response.data.last_query);
                    }
                    
                    $result
                        .removeClass('success')
                        .addClass('error')
                        .html('<div class="dodo-result-title">❌ Hata!</div><p>' + errorMsg + '</p>')
                        .fadeIn();
                    
                    $btn.text('📊 Audit Yap');
                }
                
                $btn.prop('disabled', false);
            },
            error: function(xhr, status, error) {
                console.error('[DODO AUDIT] AJAX error:', status, error);
                console.error('[DODO AUDIT] XHR:', xhr);
                
                $result
                    .removeClass('success')
                    .addClass('error')
                    .html('<div class="dodo-result-title">❌ Hata!</div><p>Bir hata oluştu. Lütfen tekrar deneyin.</p>')
                    .fadeIn();
                
                $btn.prop('disabled', false).text('📊 Audit Yap');
            },
            complete: function() {
                $loading.fadeOut();
            }
        });
    });
});
</script>
