<?php
/**
 * Usage Logger Debug Page
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

echo '<div class="wrap dodo-admin-wrap">';
echo '<h1>🔍 Usage Logger Debug</h1>';
echo '<div style="background: var(--dodo-bg-card); padding: 20px; margin-top: 20px; border: 1px solid var(--dodo-border-default); font-family: monospace; white-space: pre-wrap;">';

// 1. Tablo varlık kontrolü
$table_name = $wpdb->prefix . 'dodo_ai_usage_logs';
echo "=== TABLO VARLIK KONTROLÜ ===\n\n";
echo "Table Name: {$table_name}\n";

$table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
echo "Table Exists: " . ($table_exists ? "✅ YES" : "❌ NO") . "\n\n";

if ($table_exists) {
    // 2. Tablo yapısı
    echo "=== TABLO YAPISI ===\n\n";
    $columns = $wpdb->get_results("DESCRIBE {$table_name}");
    foreach ($columns as $col) {
        echo "- {$col->Field} ({$col->Type}) " . ($col->Null === 'NO' ? 'NOT NULL' : 'NULL') . " {$col->Key}\n";
    }
    echo "\n";
    
    // 3. Kayıt sayısı
    echo "=== KAYIT SAYISI ===\n\n";
    $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
    echo "Total Records: {$count}\n\n";
    
    // 4. Son 5 kayıt
    if ($count > 0) {
        echo "=== SON 5 KAYIT ===\n\n";
        $records = $wpdb->get_results("SELECT * FROM {$table_name} ORDER BY created_at DESC LIMIT 5");
        foreach ($records as $record) {
            echo "ID: {$record->id}\n";
            echo "Feature: {$record->feature_name}\n";
            echo "Model: {$record->model}\n";
            echo "Tokens: {$record->tokens_input} + {$record->tokens_output} = {$record->tokens_total}\n";
            echo "Cost: $" . number_format($record->estimated_cost, 6) . "\n";
            echo "Cache Hit: " . ($record->cache_hit ? 'YES' : 'NO') . "\n";
            echo "Created: {$record->created_at}\n";
            echo "---\n";
        }
    }
} else {
    echo "❌ TABLO BULUNAMADI!\n\n";
    echo "Tablo oluşturuluyor...\n";
    
    require_once DODO_PLUGIN_DIR . 'includes/class-dodo-usage-logger.php';
    $logger = new DODO_Usage_Logger();
    $logger->create_table();
    
    $table_exists_after = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
    echo "Tablo oluşturuldu: " . ($table_exists_after ? "✅ YES" : "❌ NO") . "\n";
}

echo "\n=== CLASS KONTROLÜ ===\n\n";
echo "DODO_Usage_Logger class exists: " . (class_exists('DODO_Usage_Logger') ? "✅ YES" : "❌ NO") . "\n";

if (class_exists('DODO_Usage_Logger')) {
    $logger = new DODO_Usage_Logger();
    echo "Logger instance created: ✅ YES\n\n";
    
    // Test insert
    echo "=== TEST INSERT ===\n\n";
    $test_id = $logger->log_usage(
        'debug_test',
        'gpt-4o',
        10,
        5,
        100,
        false
    );
    
    if ($test_id) {
        echo "✅ Test insert başarılı! ID: {$test_id}\n";
        
        // Test kaydını sil
        $wpdb->delete($table_name, array('id' => $test_id), array('%d'));
        echo "Test kaydı silindi.\n";
        
        // Verify deletion
        $verify_count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
        echo "Kayıt sayısı (silme sonrası): {$verify_count}\n";
    } else {
        echo "❌ Test insert başarısız!\n";
        echo "Last Error: " . $wpdb->last_error . "\n";
        echo "Last Query: " . $wpdb->last_query . "\n";
        
        // Detailed error info
        echo "\nDetaylı Hata Bilgisi:\n";
        echo "- Table Name: {$table_name}\n";
        echo "- WPDB Prefix: {$wpdb->prefix}\n";
        echo "- DB Name: " . DB_NAME . "\n";
        
        // Check table permissions
        $test_select = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
        echo "- SELECT Permission: " . ($test_select !== null ? "✅ OK" : "❌ FAILED") . "\n";
    }
    
    // Test get_usage_stats
    echo "\n=== TEST get_usage_stats() ===\n\n";
    $stats = $logger->get_usage_stats('all');
    echo "Stats returned:\n";
    echo "- Total Requests: {$stats['total_requests']}\n";
    echo "- Total Tokens: {$stats['total_tokens']}\n";
    echo "- Total Cost: $" . number_format($stats['total_cost'], 6) . "\n";
    echo "- Cache Hits: {$stats['cache_hits']}\n";
    echo "- Cache Misses: {$stats['cache_misses']}\n";
}

echo "\n=== WPDB DURUMU ===\n\n";
echo "Last Error: " . ($wpdb->last_error ? $wpdb->last_error : "YOK") . "\n";
echo "Last Query: " . ($wpdb->last_query ? substr($wpdb->last_query, 0, 200) : "YOK") . "\n";

echo '</div>';
echo '</div>';
