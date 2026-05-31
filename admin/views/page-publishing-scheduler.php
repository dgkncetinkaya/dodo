<?php
/**
 * Publishing Scheduler Sayfası
 *
 * @package DODO_AI_SEO
 * @since 1.0.4
 */

// Doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

// Settings ve Scheduler instance
$settings = new DODO_Settings();
$current_settings = $settings->get_settings();
$scheduler = new DODO_Scheduled_Publisher();
$schedule_stats = $scheduler->get_schedule_stats();

// Ayarları kaydet
if (isset($_POST['dodo_save_scheduler_settings'])) {
    // Nonce kontrolü
    if (!isset($_POST['dodo_scheduler_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['dodo_scheduler_nonce'])), 'dodo_scheduler_nonce_action')) {
        wp_die(__('Güvenlik kontrolü başarısız.', 'dodo-ai-seo'));
    }
    
    // Yetki kontrolü
    if (!current_user_can('manage_options')) {
        wp_die(__('Bu işlem için yetkiniz yok.', 'dodo-ai-seo'));
    }
    
    // Sadece scheduling ayarlarını güncelle
    $raw_values = array(
        'default_publish_mode' => isset($_POST['default_publish_mode']) ? sanitize_text_field($_POST['default_publish_mode']) : 'draft',
        'daily_publish_limit' => isset($_POST['daily_publish_limit']) ? absint($_POST['daily_publish_limit']) : 1,
        'publish_start_hour' => isset($_POST['publish_start_hour']) ? absint($_POST['publish_start_hour']) : 10,
        'publish_start_minute' => isset($_POST['publish_start_minute']) ? absint($_POST['publish_start_minute']) : 0,
        'publish_on_weekends' => isset($_POST['publish_on_weekends']) ? true : false,
        'publish_time_interval' => isset($_POST['publish_time_interval']) ? absint($_POST['publish_time_interval']) : 4,
    );
    
    // Sanitize
    $new_settings = $settings->sanitize_settings($raw_values);
    
    // Mevcut ayarları al ve sadece scheduling ayarlarını güncelle
    $all_settings = $settings->get_settings();
    $all_settings['default_publish_mode'] = $new_settings['default_publish_mode'];
    $all_settings['daily_publish_limit'] = $new_settings['daily_publish_limit'];
    $all_settings['publish_start_hour'] = $new_settings['publish_start_hour'];
    $all_settings['publish_start_minute'] = $new_settings['publish_start_minute'];
    $all_settings['publish_on_weekends'] = $new_settings['publish_on_weekends'];
    $all_settings['publish_time_interval'] = $new_settings['publish_time_interval'];
    
    if ($settings->update_settings($all_settings)) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Ayarlar başarıyla kaydedildi.', 'dodo-ai-seo') . '</p></div>';
        // Refresh settings
        $current_settings = $settings->get_settings();
        $schedule_stats = $scheduler->get_schedule_stats();
    } else {
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Ayarlar kaydedilirken bir hata oluştu.', 'dodo-ai-seo') . '</p></div>';
    }
}

// Zamanlanmış postları al
$scheduled_posts = $scheduler->get_scheduled_posts(50);

// Queue stats
$opportunities_manager = new DODO_Keyword_Opportunities();
$queue_stats = $opportunities_manager->get_stats();
?>

<div class="wrap dodo-admin-wrap">
    <!-- Premium Page Header -->
    <div class="dodo-page-header">
        <div class="dodo-page-header-content">
            <h1 class="dodo-page-title">
                <span class="dashicons dashicons-calendar-alt"></span>
                <?php echo esc_html__('Yayın Zamanlayıcı', 'dodo-ai-seo'); ?>
            </h1>
            <p class="dodo-page-description">
                <?php echo esc_html__('Zamanlanmış yayınlama sistemini yönetin ve planlanan içerikleri görüntüleyin.', 'dodo-ai-seo'); ?>
            </p>
        </div>
    </div>
    
    <!-- Dashboard İstatistikleri -->
    <div class="dodo-stats-grid" style="margin-bottom: 32px;">
        <div class="dodo-stat-card">
            <div style="font-size: 2.5rem; font-weight: 700; color: var(--dodo-primary); margin-bottom: 8px;">
                <?php echo esc_html($schedule_stats['scheduled']); ?>
            </div>
            <div style="font-size: 0.875rem; color: #64748b; font-weight: 500;">Zamanlanmış Blog</div>
        </div>
        <div class="dodo-stat-card">
            <div style="font-size: 2.5rem; font-weight: 700; color: var(--dodo-success); margin-bottom: 8px;">
                <?php echo esc_html($schedule_stats['published_today']); ?>
            </div>
            <div style="font-size: 0.875rem; color: #64748b; font-weight: 500;">Bugün Yayınlanan</div>
        </div>
        <div class="dodo-stat-card">
            <div style="font-size: 2.5rem; font-weight: 700; color: var(--dodo-warning); margin-bottom: 8px;">
                <?php echo esc_html($queue_stats['queued']); ?>
            </div>
            <div style="font-size: 0.875rem; color: #64748b; font-weight: 500;">Kuyrukta Bekleyen</div>
        </div>
        <div class="dodo-stat-card">
            <div style="font-size: 1.125rem; font-weight: 600; color: var(--dodo-text); margin-bottom: 8px;">
                <?php echo !empty($schedule_stats['next_publish_date']) ? esc_html($schedule_stats['next_publish_date']) : '—'; ?>
            </div>
            <div style="font-size: 0.875rem; color: #64748b; font-weight: 500;">Sıradaki Yayın</div>
        </div>
    </div>
    
    <!-- Missed Schedule Fixer -->
    <?php
    $fixer = new DODO_Missed_Schedule_Fixer();
    $missed_count = $fixer->count_missed_schedules();
    ?>
    <?php if ($missed_count > 0) : ?>
        <div class="notice notice-warning" style="margin-bottom: 20px;">
            <p>
                <strong>⚠️ <?php echo esc_html(sprintf(__('%d kaçırılan zamanlama tespit edildi!', 'dodo-ai-seo'), $missed_count)); ?></strong>
            </p>
            <p>
                <?php echo esc_html__('WordPress cron sistemi çalışmadığı için bazı içerikler zamanında yayınlanmamış. Aşağıdaki butona tıklayarak düzeltebilirsiniz.', 'dodo-ai-seo'); ?>
            </p>
            <p>
                <button type="button" id="dodo-fix-missed-schedules" class="button button-primary">
                    <span class="dashicons dashicons-update" style="margin-top: 4px;"></span>
                    <?php echo esc_html__('Kaçırılan Yayınları Düzelt', 'dodo-ai-seo'); ?>
                </button>
            </p>
        </div>
    <?php endif; ?>
    
    <div class="dodo-layout-container">
        <!-- Ana İçerik -->
        <div class="dodo-main-content">
            <!-- Planlanan İçerikler -->
            <div class="dodo-card">
                <h2 class="dodo-card-title">
                    📅 <?php echo esc_html__('Planlanan İçerikler', 'dodo-ai-seo'); ?>
                </h2>
                
                <?php if (empty($scheduled_posts)) : ?>
                    <div style="text-align: center; padding: 40px 20px; color: #646970;">
                        <span class="dashicons dashicons-calendar-alt" style="font-size: 48px; opacity: 0.3;"></span>
                        <p style="margin-top: 10px; font-size: 14px;">
                            <?php echo esc_html__('Henüz zamanlanmış içerik yok.', 'dodo-ai-seo'); ?>
                        </p>
                        <p style="font-size: 13px;">
                            <?php echo esc_html__('Queue processing ile otomatik zamanlanmış içerik oluşturabilirsiniz.', 'dodo-ai-seo'); ?>
                        </p>
                    </div>
                <?php else : ?>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: separate; border-spacing: 0; background: #fff;">
                            <thead>
                                <tr style="background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%); border-bottom: 2px solid #e2e8f0;">
                                    <th style="width: 32%; padding: 18px 20px; font-weight: 600; font-size: 12px; color: #64748b; text-align: left; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;">
                                        Başlık
                                    </th>
                                    <th style="width: 22%; padding: 18px 20px; font-weight: 600; font-size: 12px; color: #64748b; text-align: left; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;">
                                        Yayın Zamanı
                                    </th>
                                    <th style="width: 14%; padding: 18px 20px; font-weight: 600; font-size: 12px; color: #64748b; text-align: left; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;">
                                        Oluşturulma
                                    </th>
                                    <th style="width: 12%; padding: 18px 20px; font-weight: 600; font-size: 12px; color: #64748b; text-align: left; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;">
                                        Durum
                                    </th>
                                    <th style="width: 20%; padding: 18px 20px; font-weight: 600; font-size: 12px; color: #64748b; text-align: left; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;">
                                        İşlemler
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($scheduled_posts as $post) : ?>
                                    <?php
                                    $post_date = get_the_date('Y-m-d', $post->ID);
                                    $post_time = get_the_date('H:i', $post->ID);
                                    $created_date = get_the_date('d.m.Y', $post->ID);
                                    $created_time = get_the_date('H:i', $post->ID);
                                    $edit_url = get_edit_post_link($post->ID);
                                    $publish_mode = get_post_meta($post->ID, '_dodo_publish_mode', true);
                                    $schedule_source = get_post_meta($post->ID, '_dodo_schedule_source', true);
                                    ?>
                                    <tr data-post-id="<?php echo esc_attr($post->ID); ?>" style="border-bottom: 1px solid #e2e8f0; transition: background 0.15s;" onmouseover="this.style.background='#f8fafc';" onmouseout="this.style.background='#fff';">
                                        <td style="padding: 20px; vertical-align: middle;">
                                            <div style="display: flex; flex-direction: column; gap: 6px;">
                                                <a href="<?php echo esc_url($edit_url); ?>" target="_blank" style="text-decoration: none; color: #0f172a; font-weight: 600; font-size: 14px; line-height: 1.4; display: block; transition: color 0.15s;" onmouseover="this.style.color='#2271b1';" onmouseout="this.style.color='#0f172a';">
                                                    <?php echo esc_html($post->post_title); ?>
                                                </a>
                                                <?php if ($schedule_source) : ?>
                                                    <span style="color: #64748b; font-size: 12px; font-weight: 500;">
                                                        <?php 
                                                        $source_labels = array(
                                                            'queue' => '📋 Kuyruk',
                                                            'manual' => '✍️ Manuel',
                                                            'draft' => '📝 Taslak',
                                                        );
                                                        echo isset($source_labels[$schedule_source]) ? $source_labels[$schedule_source] : $schedule_source;
                                                        ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td style="padding: 20px; vertical-align: middle;">
                                            <div style="display: flex; flex-direction: column; gap: 8px;">
                                                <input 
                                                    type="date" 
                                                    class="dodo-edit-date" 
                                                    value="<?php echo esc_attr($post_date); ?>"
                                                    style="width: 100%; max-width: 160px; height: 36px; padding: 6px 10px; border: 1.5px solid #cbd5e1; border-radius: 6px; font-size: 13px; color: #0f172a; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; transition: all 0.2s;"
                                                    onfocus="this.style.borderColor='#2271b1'; this.style.boxShadow='0 0 0 3px rgba(34,113,177,0.1)';"
                                                    onblur="this.style.borderColor='#cbd5e1'; this.style.boxShadow='none';"
                                                >
                                                <input 
                                                    type="time" 
                                                    class="dodo-edit-time" 
                                                    value="<?php echo esc_attr($post_time); ?>"
                                                    style="width: 100%; max-width: 100px; height: 36px; padding: 6px 10px; border: 1.5px solid #cbd5e1; border-radius: 6px; font-size: 13px; color: #0f172a; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; transition: all 0.2s;"
                                                    onfocus="this.style.borderColor='#2271b1'; this.style.boxShadow='0 0 0 3px rgba(34,113,177,0.1)';"
                                                    onblur="this.style.borderColor='#cbd5e1'; this.style.boxShadow='none';"
                                                >
                                            </div>
                                        </td>
                                        <td style="padding: 20px; vertical-align: middle;">
                                            <div style="display: flex; flex-direction: column; gap: 2px;">
                                                <span style="color: #475569; font-size: 13px; font-weight: 500; line-height: 1.4;">
                                                    <?php echo esc_html($created_date); ?>
                                                </span>
                                                <span style="color: #94a3b8; font-size: 12px;">
                                                    <?php echo esc_html($created_time); ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td style="padding: 20px; vertical-align: middle;">
                                            <span style="display: inline-flex; align-items: center; gap: 5px; min-width: 100px; background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); color: #fff; padding: 5px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; white-space: nowrap; box-shadow: 0 1px 3px rgba(251,191,36,0.3);">
                                                <span style="font-size: 13px; line-height: 1;">⏰</span>
                                                <span>Zamanlandı</span>
                                            </span>
                                        </td>
                                        <td style="padding: 20px; vertical-align: middle;">
                                            <div style="display: flex; flex-direction: column; gap: 6px;">
                                                <button 
                                                    type="button" 
                                                    class="button button-small dodo-save-schedule" 
                                                    data-post-id="<?php echo esc_attr($post->ID); ?>"
                                                    title="Değişiklikleri Kaydet"
                                                    style="display: inline-flex; align-items: center; justify-content: center; gap: 5px; width: 100%; height: 32px; padding: 0 12px; background: #2271b1; border: none; border-radius: 5px; color: #fff; font-size: 12px; font-weight: 600; cursor: pointer; transition: all 0.2s; white-space: nowrap;"
                                                    onmouseover="this.style.background='#135e96'; this.style.transform='translateY(-1px)'; this.style.boxShadow='0 2px 8px rgba(34,113,177,0.3)';"
                                                    onmouseout="this.style.background='#2271b1'; this.style.transform='translateY(0)'; this.style.boxShadow='none';"
                                                >
                                                    <span class="dashicons dashicons-saved" style="font-size: 14px; width: 14px; height: 14px;"></span>
                                                    <span>Kaydet</span>
                                                </button>
                                                
                                                <button 
                                                    type="button" 
                                                    class="button button-small dodo-publish-now" 
                                                    data-post-id="<?php echo esc_attr($post->ID); ?>"
                                                    title="Şimdi Yayınla"
                                                    style="display: inline-flex; align-items: center; justify-content: center; gap: 5px; width: 100%; height: 32px; padding: 0 12px; background: #fff; border: 1.5px solid #cbd5e1; border-radius: 5px; color: #475569; font-size: 12px; font-weight: 600; cursor: pointer; transition: all 0.2s; white-space: nowrap;"
                                                    onmouseover="this.style.borderColor='#10b981'; this.style.color='#10b981'; this.style.background='#f0fdf4';"
                                                    onmouseout="this.style.borderColor='#cbd5e1'; this.style.color='#475569'; this.style.background='#fff';"
                                                >
                                                    <span class="dashicons dashicons-yes" style="font-size: 14px; width: 14px; height: 14px;"></span>
                                                    <span>Yayınla</span>
                                                </button>
                                                
                                                <button 
                                                    type="button" 
                                                    class="button button-small dodo-convert-to-draft" 
                                                    data-post-id="<?php echo esc_attr($post->ID); ?>"
                                                    title="Taslağa Çevir"
                                                    style="display: inline-flex; align-items: center; justify-content: center; gap: 5px; width: 100%; height: 32px; padding: 0 12px; background: #fff; border: 1.5px solid #cbd5e1; border-radius: 5px; color: #475569; font-size: 12px; font-weight: 600; cursor: pointer; transition: all 0.2s; white-space: nowrap;"
                                                    onmouseover="this.style.borderColor='#64748b'; this.style.color='#1e293b'; this.style.background='#f8fafc';"
                                                    onmouseout="this.style.borderColor='#cbd5e1'; this.style.color='#475569'; this.style.background='#fff';"
                                                >
                                                    <span class="dashicons dashicons-edit" style="font-size: 14px; width: 14px; height: 14px;"></span>
                                                    <span>Taslak</span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Premium Sidebar - Ayarlar -->
        <aside class="dodo-sidebar">
            <div class="dodo-sidebar-card" style="padding: 28px;">
                <h3 class="dodo-sidebar-card-title" style="margin: 0 0 24px 0; font-size: 16px; font-weight: 700; color: #1e293b;">
                    ⚙️ <?php echo esc_html__('Zamanlanmış Yayınlama Ayarları', 'dodo-ai-seo'); ?>
                </h3>
                
                <form method="post" action="">
                    <?php wp_nonce_field('dodo_scheduler_nonce_action', 'dodo_scheduler_nonce'); ?>
                    
                    <div class="dodo-form-group" style="margin-bottom: 24px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 10px; font-size: 14px; color: #1e293b;">
                            <?php echo esc_html__('Kuyruk İşleme Varsayılan Modu', 'dodo-ai-seo'); ?>
                        </label>
                        <select name="default_publish_mode" class="dodo-select" style="width: 100%; height: 44px; padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 14px; color: #1e293b; background: #fff; transition: all 0.2s;" onfocus="this.style.borderColor='#2271b1'; this.style.boxShadow='0 0 0 3px rgba(34,113,177,0.1)';" onblur="this.style.borderColor='#cbd5e1'; this.style.boxShadow='none';">
                            <?php 
                            $mode_options = $settings->get_publish_mode_options();
                            $mode_translations = array(
                                'draft' => 'Taslak',
                                'publish' => 'Yayınla',
                                'scheduled' => 'Zamanlandı',
                                'auto' => 'Otomatik',
                            );
                            foreach ($mode_options as $value => $label) : 
                                $translated_label = isset($mode_translations[$value]) ? $mode_translations[$value] : $label;
                            ?>
                                <option value="<?php echo esc_attr($value); ?>" 
                                    <?php selected($current_settings['default_publish_mode'], $value); ?>>
                                    <?php echo esc_html($translated_label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p style="margin: 8px 0 0 0; font-size: 13px; color: #64748b; line-height: 1.5;">
                            <?php echo esc_html__('Kuyruk işleme için varsayılan mod', 'dodo-ai-seo'); ?>
                        </p>
                    </div>
                    
                    <div class="dodo-form-group" style="margin-bottom: 24px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 10px; font-size: 14px; color: #1e293b;">
                            <?php echo esc_html__('Günlük Yayın Limiti', 'dodo-ai-seo'); ?>
                        </label>
                        <input 
                            type="number" 
                            name="daily_publish_limit" 
                            class="dodo-input" 
                            style="width: 100%; height: 44px; padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 14px; color: #1e293b; transition: all 0.2s;"
                            value="<?php echo esc_attr($current_settings['daily_publish_limit']); ?>"
                            min="1"
                            max="20"
                            onfocus="this.style.borderColor='#2271b1'; this.style.boxShadow='0 0 0 3px rgba(34,113,177,0.1)';"
                            onblur="this.style.borderColor='#cbd5e1'; this.style.boxShadow='none';"
                        >
                        <p style="margin: 8px 0 0 0; font-size: 13px; color: #64748b; line-height: 1.5;">
                            <?php echo esc_html__('Bir günde maksimum kaç blog', 'dodo-ai-seo'); ?>
                        </p>
                    </div>
                    
                    <div class="dodo-form-group" style="margin-bottom: 24px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 10px; font-size: 14px; color: #1e293b;">
                            <?php echo esc_html__('Yayın Başlangıç Saati', 'dodo-ai-seo'); ?>
                        </label>
                        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 12px;">
                            <select name="publish_start_hour" class="dodo-select" style="width: 100%; height: 44px; padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 14px; color: #1e293b; background: #fff; transition: all 0.2s;" onfocus="this.style.borderColor='#2271b1'; this.style.boxShadow='0 0 0 3px rgba(34,113,177,0.1)';" onblur="this.style.borderColor='#cbd5e1'; this.style.boxShadow='none';">
                                <?php for ($h = 0; $h < 24; $h++) : ?>
                                    <option value="<?php echo $h; ?>" 
                                        <?php selected($current_settings['publish_start_hour'], $h); ?>>
                                        <?php echo sprintf('%02d:00', $h); ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                            <select name="publish_start_minute" class="dodo-select" style="width: 100%; height: 44px; padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 14px; color: #1e293b; background: #fff; transition: all 0.2s;" onfocus="this.style.borderColor='#2271b1'; this.style.boxShadow='0 0 0 3px rgba(34,113,177,0.1)';" onblur="this.style.borderColor='#cbd5e1'; this.style.boxShadow='none';">
                                <option value="0" <?php selected($current_settings['publish_start_minute'], 0); ?>>00</option>
                                <option value="15" <?php selected($current_settings['publish_start_minute'], 15); ?>>15</option>
                                <option value="30" <?php selected($current_settings['publish_start_minute'], 30); ?>>30</option>
                                <option value="45" <?php selected($current_settings['publish_start_minute'], 45); ?>>45</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="dodo-form-group" style="margin-bottom: 24px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 10px; font-size: 14px; color: #1e293b;">
                            <?php echo esc_html__('Yayın Aralığı (Saat)', 'dodo-ai-seo'); ?>
                        </label>
                        <input 
                            type="number" 
                            name="publish_time_interval" 
                            class="dodo-input" 
                            style="width: 100%; height: 44px; padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 14px; color: #1e293b; transition: all 0.2s;"
                            value="<?php echo esc_attr($current_settings['publish_time_interval']); ?>"
                            min="1"
                            max="12"
                            onfocus="this.style.borderColor='#2271b1'; this.style.boxShadow='0 0 0 3px rgba(34,113,177,0.1)';"
                            onblur="this.style.borderColor='#cbd5e1'; this.style.boxShadow='none';"
                        >
                        <p style="margin: 8px 0 0 0; font-size: 13px; color: #64748b; line-height: 1.5;">
                            <?php echo esc_html__('Aynı gün içinde bloglar arası saat farkı', 'dodo-ai-seo'); ?>
                        </p>
                    </div>
                    
                    <div class="dodo-form-group" style="margin-bottom: 28px;">
                        <label style="display: flex; align-items: center; font-size: 14px; cursor: pointer; padding: 12px; background: #f8fafc; border-radius: 8px; transition: all 0.2s;" onmouseover="this.style.background='#f1f5f9';" onmouseout="this.style.background='#f8fafc';">
                            <input 
                                type="checkbox" 
                                name="publish_on_weekends" 
                                value="1"
                                <?php checked($current_settings['publish_on_weekends'], true); ?>
                                style="margin-right: 10px; width: 18px; height: 18px; cursor: pointer;"
                            >
                            <span style="font-weight: 500; color: #1e293b;">
                                <?php echo esc_html__('Hafta sonları yayınla', 'dodo-ai-seo'); ?>
                            </span>
                        </label>
                        <p style="margin: 8px 0 0 12px; font-size: 13px; color: #64748b; line-height: 1.5;">
                            <?php echo esc_html__('Kapalıysa Cumartesi ve Pazar atlanır', 'dodo-ai-seo'); ?>
                        </p>
                    </div>
                    
                    <button type="submit" name="dodo_save_scheduler_settings" class="dodo-btn-primary" style="width: 100%; height: 48px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; background: linear-gradient(135deg, #2271b1 0%, #135e96 100%); border: none; border-radius: 8px; color: #fff; font-size: 15px; font-weight: 600; cursor: pointer; transition: all 0.2s; box-shadow: 0 2px 8px rgba(34,113,177,0.2);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 16px rgba(34,113,177,0.3)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(34,113,177,0.2)';">
                        <span class="dashicons dashicons-saved" style="font-size: 20px; width: 20px; height: 20px;"></span>
                        <?php echo esc_html__('Ayarları Kaydet', 'dodo-ai-seo'); ?>
                    </button>
                </form>
            </div>
            
            <!-- Bilgi Kartı -->
            <div class="dodo-sidebar-card dodo-info-card" style="padding: 24px;">
                <h4 class="dodo-sidebar-card-title" style="margin: 0 0 16px 0; font-size: 15px; font-weight: 700; color: #1e293b;">
                    ℹ️ <?php echo esc_html__('Nasıl Çalışır?', 'dodo-ai-seo'); ?>
                </h4>
                <ul class="dodo-sidebar-list" style="margin: 0; padding: 0; list-style: none;">
                    <li style="padding: 10px 0; border-bottom: 1px solid #e2e8f0; font-size: 13px; color: #475569; line-height: 1.6;">
                        <?php echo esc_html__('Queue processing ile otomatik zamanlanmış içerik oluşturulur', 'dodo-ai-seo'); ?>
                    </li>
                    <li style="padding: 10px 0; border-bottom: 1px solid #e2e8f0; font-size: 13px; color: #475569; line-height: 1.6;">
                        <?php echo esc_html__('WordPress native scheduling sistemi kullanılır', 'dodo-ai-seo'); ?>
                    </li>
                    <li style="padding: 10px 0; border-bottom: 1px solid #e2e8f0; font-size: 13px; color: #475569; line-height: 1.6;">
                        <?php echo esc_html__('Günlük limit ve saat aralığına göre tarihler hesaplanır', 'dodo-ai-seo'); ?>
                    </li>
                    <li style="padding: 10px 0; font-size: 13px; color: #475569; line-height: 1.6;">
                        <?php echo esc_html__('Hafta sonu kontrolü ile doğal yayın akışı sağlanır', 'dodo-ai-seo'); ?>
                    </li>
                </ul>
            </div>
        </aside>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Kaçırılan zamanlamaları düzelt
    $('#dodo-fix-missed-schedules').on('click', function() {
        var $btn = $(this);
        
        if (!confirm('Kaçırılan zamanlamaları düzeltmek istediğinizden emin misiniz? Bu işlem geçmiş tarihli tüm "future" postları yayınlayacak.')) {
            return;
        }
        
        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update dashicons-spin"></span> İşleniyor...');
        
        $.ajax({
            url: dodoAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'dodo_fix_missed_schedules',
                nonce: dodoAjax.nonce
            },
            timeout: 60000, // 1 dakika
            success: function(response) {
                if (response.success) {
                    var message = response.data.message;
                    if (response.data.fixed > 0) {
                        message += '\n\n✅ Düzeltilen: ' + response.data.fixed;
                    }
                    if (response.data.failed > 0) {
                        message += '\n❌ Başarısız: ' + response.data.failed;
                    }
                    alert(message);
                    location.reload();
                } else {
                    alert('Hata: ' + response.data.message);
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-update"></span> Kaçırılan Yayınları Düzelt');
                }
            },
            error: function() {
                alert('Bir hata oluştu. Lütfen tekrar deneyin.');
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-update"></span> Kaçırılan Yayınları Düzelt');
            }
        });
    });
    
    // Kaydet butonu
    $('.dodo-save-schedule').on('click', function() {
        var $btn = $(this);
        var $row = $btn.closest('tr');
        var postId = $btn.data('post-id');
        var newDate = $row.find('.dodo-edit-date').val();
        var newTime = $row.find('.dodo-edit-time').val();
        
        if (!newDate || !newTime) {
            alert('Lütfen tarih ve saat girin.');
            return;
        }
        
        $btn.prop('disabled', true).text('Kaydediliyor...');
        
        $.ajax({
            url: dodoAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'dodo_update_scheduled_post',
                nonce: dodoAjax.nonce,
                post_id: postId,
                publish_date: newDate,
                publish_time: newTime
            },
            success: function(response) {
                if (response.success) {
                    alert('Yayın tarihi güncellendi!');
                    location.reload();
                } else {
                    alert('Hata: ' + response.data.message);
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-saved"></span> Kaydet');
                }
            },
            error: function() {
                alert('Bir hata oluştu. Lütfen tekrar deneyin.');
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-saved"></span> Kaydet');
            }
        });
    });
    
    // Şimdi Yayınla
    $('.dodo-publish-now').on('click', function() {
        var $btn = $(this);
        var postId = $btn.data('post-id');
        
        if (!confirm('Bu içeriği şimdi yayınlamak istediğinizden emin misiniz?')) {
            return;
        }
        
        $btn.prop('disabled', true).text('Yayınlanıyor...');
        
        $.ajax({
            url: dodoAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'dodo_publish_now',
                nonce: dodoAjax.nonce,
                post_id: postId
            },
            success: function(response) {
                if (response.success) {
                    alert('İçerik başarıyla yayınlandı!');
                    location.reload();
                } else {
                    alert('Hata: ' + response.data.message);
                    $btn.prop('disabled', false).text('Yayınla');
                }
            },
            error: function() {
                alert('Bir hata oluştu. Lütfen tekrar deneyin.');
                $btn.prop('disabled', false).text('Yayınla');
            }
        });
    });
    
    // Taslağa Çevir
    $('.dodo-convert-to-draft').on('click', function() {
        var $btn = $(this);
        var postId = $btn.data('post-id');
        
        if (!confirm('Bu içeriği taslağa çevirmek istediğinizden emin misiniz?')) {
            return;
        }
        
        $btn.prop('disabled', true).text('İşleniyor...');
        
        $.ajax({
            url: dodoAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'dodo_convert_to_draft',
                nonce: dodoAjax.nonce,
                post_id: postId
            },
            success: function(response) {
                if (response.success) {
                    alert('İçerik taslağa çevrildi!');
                    location.reload();
                } else {
                    alert('Hata: ' + response.data.message);
                    $btn.prop('disabled', false).text('Taslak');
                }
            },
            error: function() {
                alert('Bir hata oluştu. Lütfen tekrar deneyin.');
                $btn.prop('disabled', false).text('Taslak');
            }
        });
    });
});
</script>
