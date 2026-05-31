<?php
/**
 * Learning Control Center
 * 
 * Admin page for controlling learning system
 * 
 * @package DODO_AI_SEO
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$controller = new DODO_Learning_Controller();
$status = $controller->get_status();

?>

<div class="wrap dodo-learning-control">
    <h1>
        <span class="dashicons dashicons-admin-settings"></span>
        Öğrenme Kontrol Merkezi
    </h1>
    
    <p class="description">
        Öğrenme sistemi davranışı, modları ve güvenlik ayarlarını kontrol edin
    </p>
    
    <!-- Emergency Freeze -->
    <?php if ($status['frozen']): ?>
        <div class="notice notice-error">
            <p>
                <strong>⚠️ ACİL DONDURMA AKTİF</strong><br>
                Tüm öğrenme adaptasyonları engellendi. Devam etmek için "Dondur" butonuna tıklayın.
            </p>
            <p>
                <button type="button" class="button button-primary" id="unfreeze-btn">
                    Öğrenme Sistemini Çöz
                </button>
            </p>
        </div>
    <?php endif; ?>
    
    <!-- Status Cards -->
    <div class="dodo-status-grid">
        <div class="status-card <?php echo $status['enabled'] ? 'status-active' : 'status-inactive'; ?>">
            <div class="status-icon">
                <?php echo $status['enabled'] ? '✓' : '✗'; ?>
            </div>
            <div class="status-content">
                <div class="status-label">Öğrenme Durumu</div>
                <div class="status-value"><?php echo $status['enabled'] ? 'AKTİF' : 'KAPALI'; ?></div>
            </div>
        </div>
        
        <div class="status-card">
            <div class="status-icon">🎯</div>
            <div class="status-content">
                <div class="status-label">Aktif Mod</div>
                <div class="status-value"><?php echo strtoupper($status['mode']); ?></div>
            </div>
        </div>
        
        <div class="status-card">
            <div class="status-icon">📊</div>
            <div class="status-content">
                <div class="status-label">Güven Eşiği</div>
                <div class="status-value"><?php echo $status['confidence_threshold']; ?>%</div>
            </div>
        </div>
        
        <div class="status-card">
            <div class="status-icon">🌍</div>
            <div class="status-content">
                <div class="status-label">Ortam</div>
                <div class="status-value"><?php echo strtoupper($status['environment']); ?></div>
            </div>
        </div>
    </div>
    
    <!-- Control Panels -->
    <div class="dodo-control-panels">
        
        <!-- Learning Mode -->
        <div class="control-panel">
            <h2>Öğrenme Modu</h2>
            
            <div class="mode-options">
                <label class="mode-option">
                    <input type="radio" name="learning_mode" value="off" <?php checked($status['mode'], 'off'); ?>>
                    <div class="mode-card">
                        <div class="mode-title">🔴 KAPALI</div>
                        <div class="mode-desc">Öğrenme devre dışı - takip veya adaptasyon yok</div>
                    </div>
                </label>
                
                <label class="mode-option">
                    <input type="radio" name="learning_mode" value="observe" <?php checked($status['mode'], 'observe'); ?>>
                    <div class="mode-card">
                        <div class="mode-title">👁️ GÖZLEM</div>
                        <div class="mode-desc">Öğren fakat değişiklikleri uygulama</div>
                    </div>
                </label>
                
                <label class="mode-option">
                    <input type="radio" name="learning_mode" value="apply" <?php checked($status['mode'], 'apply'); ?>>
                    <div class="mode-card">
                        <div class="mode-title">✅ UYGULA</div>
                        <div class="mode-desc">Doğrulanan tüm değişiklikleri uygula</div>
                    </div>
                </label>
                
                <label class="mode-option">
                    <input type="radio" name="learning_mode" value="safe_rollout" <?php checked($status['mode'], 'safe_rollout'); ?>>
                    <div class="mode-card">
                        <div class="mode-title">🛡️ GÜVENLİ YAYIN</div>
                        <div class="mode-desc">Sadece düşük riskli adaptasyonları uygula (&lt;15% değişim)</div>
                    </div>
                </label>
            </div>
            
            <button type="button" class="button button-primary" id="save-mode-btn">
                Öğrenme Modunu Kaydet
            </button>
        </div>
        
        <!-- Environment -->
        <div class="control-panel">
            <h2>Yayın Ortamı</h2>
            
            <select id="environment-select">
                <option value="development" <?php selected($status['environment'], 'development'); ?>>
                    Geliştirme (50% güven eşiği)
                </option>
                <option value="staging" <?php selected($status['environment'], 'staging'); ?>>
                    Test (60% güven eşiği)
                </option>
                <option value="production" <?php selected($status['environment'], 'production'); ?>>
                    Canlı (70% güven eşiği)
                </option>
            </select>
            
            <button type="button" class="button button-primary" id="save-env-btn">
                Ortamı Kaydet
            </button>
        </div>
        
        <!-- Confidence Threshold -->
        <div class="control-panel">
            <h2>Güven Eşiği</h2>
            
            <div class="threshold-control">
                <input type="range" id="confidence-slider" min="0" max="100" value="<?php echo $status['confidence_threshold']; ?>" step="5">
                <div class="threshold-value">
                    <span id="confidence-value"><?php echo $status['confidence_threshold']; ?></span>%
                </div>
            </div>
            
            <p class="description">
                Öğrenme adaptasyonu için gereken minimum güven (yüksek = daha muhafazakar)
            </p>
            
            <button type="button" class="button button-primary" id="save-threshold-btn">
                Eşiği Kaydet
            </button>
        </div>
        
        <!-- Safety Controls -->
        <div class="control-panel">
            <h2>Güvenlik Kontrolleri</h2>
            
            <div class="safety-options">
                <label class="safety-option">
                    <input type="checkbox" id="adaptive-strategy" <?php checked($status['adaptive_strategy']); ?>>
                    <span>Adaptif Stratejiyi Etkinleştir</span>
                    <p class="description">Öğrenilen desenlere dayalı strateji evrimine izin ver</p>
                </label>
                
                <label class="safety-option">
                    <input type="checkbox" id="noisy-filter" <?php checked($status['noisy_filter']); ?>>
                    <span>Gürültülü Veri Filtresini Etkinleştir</span>
                    <p class="description">İstatistiksel aykırı değerleri ve tutarsız verileri filtrele</p>
                </label>
            </div>
            
            <button type="button" class="button button-primary" id="save-safety-btn">
                Güvenlik Ayarlarını Kaydet
            </button>
        </div>
        
        <!-- Emergency Controls -->
        <div class="control-panel emergency-panel">
            <h2>Acil Durum Kontrolleri</h2>
            
            <?php if (!$status['frozen']): ?>
                <button type="button" class="button button-danger" id="emergency-freeze-btn">
                    🚨 Acil Dondurma
                </button>
                <p class="description">
                    Tüm öğrenme adaptasyonlarını anında durdur (sorun durumunda kullan)
                </p>
            <?php else: ?>
                <button type="button" class="button button-primary" id="unfreeze-btn-2">
                    ✅ Sistemi Çöz
                </button>
                <p class="description">
                    Normal öğrenme operasyonlarına devam et
                </p>
            <?php endif; ?>
        </div>
        
    </div>
</div>

<style>
.dodo-learning-control {
    max-width: 1200px;
}

.dodo-status-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.status-card {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.status-card.status-active {
    border-color: #46b450;
    background: #f0f9f1;
}

.status-card.status-inactive {
    border-color: #dc3232;
    background: #fef7f7;
}

.status-icon {
    font-size: 32px;
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f0f0f0;
    border-radius: 50%;
}

.status-label {
    font-size: 12px;
    color: #666;
    text-transform: uppercase;
}

.status-value {
    font-size: 20px;
    font-weight: bold;
    color: #2271b1;
}

.dodo-control-panels {
    display: grid;
    gap: 20px;
    margin-top: 30px;
}

.control-panel {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
}

.control-panel h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 2px solid #2271b1;
}

.mode-options {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin: 20px 0;
}

.mode-option {
    cursor: pointer;
}

.mode-option input[type="radio"] {
    display: none;
}

.mode-card {
    border: 2px solid #ddd;
    border-radius: 8px;
    padding: 15px;
    text-align: center;
    transition: all 0.3s;
}

.mode-option input[type="radio"]:checked + .mode-card {
    border-color: #2271b1;
    background: #f0f6fc;
}

.mode-title {
    font-size: 18px;
    font-weight: bold;
    margin-bottom: 10px;
}

.mode-desc {
    font-size: 13px;
    color: #666;
}

.threshold-control {
    display: flex;
    align-items: center;
    gap: 20px;
    margin: 20px 0;
}

#confidence-slider {
    flex: 1;
}

.threshold-value {
    font-size: 24px;
    font-weight: bold;
    color: #2271b1;
    min-width: 80px;
    text-align: center;
}

.safety-options {
    display: flex;
    flex-direction: column;
    gap: 15px;
    margin: 20px 0;
}

.safety-option {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.safety-option input[type="checkbox"] {
    margin-right: 10px;
}

.emergency-panel {
    border-color: #dc3232;
}

.button-danger {
    background: #dc3232;
    border-color: #dc3232;
    color: white;
}

.button-danger:hover {
    background: #a00;
    border-color: #a00;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Update confidence value display
    $('#confidence-slider').on('input', function() {
        $('#confidence-value').text($(this).val());
    });
    
    // Save learning mode
    $('#save-mode-btn').on('click', function() {
        const mode = $('input[name="learning_mode"]:checked').val();
        
        $.post(ajaxurl, {
            action: 'dodo_set_learning_mode',
            nonce: '<?php echo wp_create_nonce('dodo_learning_control'); ?>',
            mode: mode
        }, function(response) {
            if (response.success) {
                alert('Öğrenme modu kaydedildi: ' + mode);
                location.reload();
            } else {
                alert('Hata: ' + response.data.message);
            }
        });
    });
    
    // Save environment
    $('#save-env-btn').on('click', function() {
        const env = $('#environment-select').val();
        
        $.post(ajaxurl, {
            action: 'dodo_set_environment',
            nonce: '<?php echo wp_create_nonce('dodo_learning_control'); ?>',
            environment: env
        }, function(response) {
            if (response.success) {
                alert('Ortam kaydedildi: ' + env);
                location.reload();
            } else {
                alert('Hata: ' + response.data.message);
            }
        });
    });
    
    // Save threshold
    $('#save-threshold-btn').on('click', function() {
        const threshold = $('#confidence-slider').val();
        
        $.post(ajaxurl, {
            action: 'dodo_set_confidence_threshold',
            nonce: '<?php echo wp_create_nonce('dodo_learning_control'); ?>',
            threshold: threshold
        }, function(response) {
            if (response.success) {
                alert('Güven eşiği kaydedildi: ' + threshold + '%');
            } else {
                alert('Hata: ' + response.data.message);
            }
        });
    });
    
    // Save safety settings
    $('#save-safety-btn').on('click', function() {
        const adaptive = $('#adaptive-strategy').is(':checked');
        const noisy = $('#noisy-filter').is(':checked');
        
        $.post(ajaxurl, {
            action: 'dodo_set_safety_settings',
            nonce: '<?php echo wp_create_nonce('dodo_learning_control'); ?>',
            adaptive_strategy: adaptive,
            noisy_filter: noisy
        }, function(response) {
            if (response.success) {
                alert('Güvenlik ayarları kaydedildi');
            } else {
                alert('Hata: ' + response.data.message);
            }
        });
    });
    
    // Emergency freeze
    $('#emergency-freeze-btn, #unfreeze-btn, #unfreeze-btn-2').on('click', function() {
        const action = $(this).attr('id').includes('freeze') ? 'freeze' : 'unfreeze';
        const confirm_msg = action === 'freeze' 
            ? 'Tüm öğrenme adaptasyonlarını DONDURMAK istediğinizden emin misiniz?' 
            : 'Öğrenme sistemini ÇÖZMEK istediğinizden emin misiniz?';
        
        if (!confirm(confirm_msg)) {
            return;
        }
        
        $.post(ajaxurl, {
            action: 'dodo_emergency_' + action,
            nonce: '<?php echo wp_create_nonce('dodo_learning_control'); ?>'
        }, function(response) {
            if (response.success) {
                alert(action === 'freeze' ? 'Sistem DONDURULDU' : 'Sistem ÇÖZÜLDÜ');
                location.reload();
            } else {
                alert('Hata: ' + response.data.message);
            }
        });
    });
});
</script>
