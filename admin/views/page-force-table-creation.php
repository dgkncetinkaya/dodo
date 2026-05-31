<?php
/**
 * Force Table Creation Page
 * 
 * Manuel olarak usage logger tablosunu oluşturur
 */

// Doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

// Force create işlemi
$force_create = isset($_POST['force_create_table']) && check_admin_referer('dodo_force_create', 'dodo_force_create_nonce');

?>

<div class="wrap dodo-admin-wrap">
    <h1>🔧 Force Table Creation</h1>
    
    <?php if (!$force_create): ?>
        
        <div class="notice notice-info">
            <p><strong>Bu sayfa şunları yapar:</strong></p>
            <ul style="list-style: disc; margin-left: 20px;">
                <li>wp_dodo_ai_usage_logs tablosunu kontrol eder</li>
                <li>Tablo yoksa oluşturur</li>
                <li>Tablo varsa yapısını günceller</li>
                <li>Test insert yapar</li>
            </ul>
        </div>
        
        <form method="post" style="margin-top: 20px;">
            <?php wp_nonce_field('dodo_force_create', 'dodo_force_create_nonce'); ?>
            <input type="hidden" name="force_create_table" value="1">
            <button type="submit" class="button button-primary button-hero">
                🔨 Tabloyu Oluştur/Güncelle
            </button>
        </form>
        
    <?php else: ?>
        
        <?php
        global $wpdb;
        
        echo '<div style="background: #fff; padding: 20px; margin-top: 20px; border: 1px solid #ccc; font-family: monospace; white-space: pre-wrap;">';
        
        echo "=================================================\n";
        echo "FORCE TABLE CREATION\n";
        echo "=================================================\n\n";
        
        $table_name = $wpdb->prefix . 'dodo_ai_usage_logs';
        echo "Table Name: {$table_name}\n\n";
        
        // Mevcut durumu kontrol et
        echo "=== MEVCUT DURUM ===\n\n";
        $table_exists_before = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
        echo "Table Exists (Before): " . ($table_exists_before ? "✅ YES" : "❌ NO") . "\n\n";
        
        if ($table_exists_before) {
            $count_before = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
            echo "Record Count (Before): {$count_before}\n\n";
        }
        
        // Tabloyu oluştur/güncelle
        echo "=== TABLO OLUŞTURMA ===\n\n";
        
        require_once DODO_PLUGIN_DIR . 'includes/class-dodo-usage-logger.php';
        $logger = new DODO_Usage_Logger();
        
        echo "Logger instance created: ✅\n";
        echo "Calling create_table()...\n\n";
        
        $logger->create_table();
        
        // Sonucu kontrol et
        echo "=== SONUÇ ===\n\n";
        $table_exists_after = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
        echo "Table Exists (After): " . ($table_exists_after ? "✅ YES" : "❌ NO") . "\n\n";
        
        if ($table_exists_after) {
            // Tablo yapısını göster
            echo "=== TABLO YAPISI ===\n\n";
            $columns = $wpdb->get_results("DESCRIBE {$table_name}");
            foreach ($columns as $col) {
                echo "- {$col->Field} ({$col->Type}) " . ($col->Null === 'NO' ? 'NOT NULL' : 'NULL') . " {$col->Key}\n";
            }
            echo "\n";
            
            // Kayıt sayısı
            $count_after = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
            echo "Record Count (After): {$count_after}\n\n";
            
            // Test insert
            echo "=== TEST INSERT ===\n\n";
            $test_id = $logger->log_usage(
                'force_create_test',
                'gpt-4o',
                100,
                50,
                500,
                false
            );
            
            if ($test_id) {
                echo "✅ Test insert başarılı! ID: {$test_id}\n\n";
                
                // Test kaydını oku
                $test_record = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$table_name} WHERE id = %d",
                    $test_id
                ));
                
                if ($test_record) {
                    echo "Test Record:\n";
                    echo "- ID: {$test_record->id}\n";
                    echo "- Feature: {$test_record->feature_name}\n";
                    echo "- Model: {$test_record->model}\n";
                    echo "- Tokens: {$test_record->tokens_input} + {$test_record->tokens_output} = {$test_record->tokens_total}\n";
                    echo "- Cost: $" . number_format($test_record->estimated_cost, 6) . "\n";
                    echo "- Cache Hit: " . ($test_record->cache_hit ? 'YES' : 'NO') . "\n";
                    echo "- Created: {$test_record->created_at}\n\n";
                }
                
                // Test kaydını sil
                $wpdb->delete($table_name, array('id' => $test_id), array('%d'));
                echo "Test kaydı silindi.\n\n";
                
                echo "=================================================\n";
                echo "✅ BAŞARILI! Tablo hazır ve çalışıyor.\n";
                echo "=================================================\n";
            } else {
                echo "❌ Test insert başarısız!\n";
                echo "WPDB Error: " . $wpdb->last_error . "\n";
                echo "WPDB Query: " . $wpdb->last_query . "\n\n";
                
                echo "=================================================\n";
                echo "❌ HATA! Tablo oluşturuldu ama insert çalışmıyor.\n";
                echo "=================================================\n";
            }
        } else {
            echo "=================================================\n";
            echo "❌ HATA! Tablo oluşturulamadı.\n";
            echo "=================================================\n";
            
            echo "\nWPDB Error: " . $wpdb->last_error . "\n";
            echo "DB Name: " . DB_NAME . "\n";
            echo "DB User: " . DB_USER . "\n";
            echo "Table Prefix: " . $wpdb->prefix . "\n";
        }
        
        echo '</div>';
        
        echo '<p style="margin-top: 20px;">';
        echo '<a href="' . admin_url('admin.php?page=dodo-force-table-creation') . '" class="button">🔄 Tekrar Çalıştır</a> ';
        echo '<a href="' . admin_url('admin.php?page=dodo-usage-logger-debug') . '" class="button">🔍 Logger Debug</a> ';
        echo '<a href="' . admin_url('admin.php?page=dodo-cache-test') . '" class="button">🧪 Cache Test</a>';
        echo '</p>';
        ?>
        
    <?php endif; ?>
    
</div>
