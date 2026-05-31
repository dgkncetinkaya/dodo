<?php
/**
 * Cache System Test Page
 * 
 * Admin sayfası olarak cache sistemini test eder
 */

// Doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

// Test çalıştırılacak mı kontrol et
$run_test = isset($_POST['run_cache_test']) && check_admin_referer('dodo_cache_test', 'dodo_cache_test_nonce');

// Test kayıtlarını temizle
$clean_test_logs = isset($_POST['clean_test_logs']) && check_admin_referer('dodo_clean_test_logs', 'dodo_clean_test_logs_nonce');

if ($clean_test_logs) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'dodo_ai_usage_logs';
    
    // Sadece cache_test kayıtlarını sil
    $deleted = $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$table_name} WHERE feature_name = %s",
            'cache_test'
        )
    );
    
    echo '<div class="wrap dodo-admin-wrap">';
    echo '<h1>🧪 DODO AI Cache System Test</h1>';
    echo '<div class="notice notice-success is-dismissible">';
    echo '<p><strong>✅ Test kayıtları temizlendi!</strong></p>';
    echo '<p>' . $deleted . ' adet cache_test kaydı silindi.</p>';
    echo '</div>';
    echo '<p><a href="' . admin_url('admin.php?page=dodo-cache-test') . '" class="button">← Geri Dön</a></p>';
    echo '</div>';
    return;
}

?>

<div class="wrap dodo-admin-wrap">
    <h1>🧪 DODO AI Cache System Test</h1>
    
    <?php if (!$run_test): ?>
        
        <div class="notice notice-info">
            <p><strong>Bu test şunları kontrol eder:</strong></p>
            <ul style="list-style: disc; margin-left: 20px;">
                <li>Cache MISS (ilk çağrı - API kullanımı)</li>
                <li>Cache HIT (ikinci çağrı - cache kullanımı)</li>
                <li>Token tasarrufu</li>
                <li>Execution time farkı</li>
                <li>Usage logger kayıtları</li>
                <li>Transient oluşturma</li>
            </ul>
        </div>
        
        <form method="post" style="margin-top: 20px;">
            <?php wp_nonce_field('dodo_cache_test', 'dodo_cache_test_nonce'); ?>
            <input type="hidden" name="run_cache_test" value="1">
            <button type="submit" class="button button-primary button-hero">
                🚀 Cache Testini Başlat
            </button>
        </form>
        
        <hr style="margin: 30px 0;">
        
        <h2>🗑️ Test Kayıtlarını Temizle</h2>
        <div class="notice notice-warning">
            <p><strong>Dikkat:</strong> Bu işlem sadece <code>feature_name = cache_test</code> olan kayıtları siler.</p>
            <p>Diğer production kayıtlarına dokunmaz.</p>
        </div>
        
        <form method="post" style="margin-top: 20px;" onsubmit="return confirm('cache_test kayıtlarını silmek istediğinize emin misiniz?');">
            <?php wp_nonce_field('dodo_clean_test_logs', 'dodo_clean_test_logs_nonce'); ?>
            <input type="hidden" name="clean_test_logs" value="1">
            <button type="submit" class="button button-secondary">
                🗑️ Test Kayıtlarını Temizle
            </button>
        </form>
        
    <?php else: ?>
        
        <div class="notice notice-warning">
            <p><strong>⏳ Test çalışıyor... Lütfen bekleyin.</strong></p>
        </div>
        
        <?php
        // Test başlat
        echo '<div style="background: #fff; padding: 20px; margin-top: 20px; border: 1px solid #ccc; font-family: monospace; white-space: pre-wrap;">';
        
        // Test parametreleri
        $system_prompt = "You are a helpful assistant.";
        $user_prompt = "Say 'Hello World' in exactly 2 words.";
        $feature_name = "cache_test";
        
        echo "=================================================\n";
        echo "DODO AI CACHE SYSTEM TEST\n";
        echo "=================================================\n\n";
        
        echo "Test Parametreleri:\n";
        echo "- System Prompt: {$system_prompt}\n";
        echo "- User Prompt: {$user_prompt}\n";
        echo "- Feature Name: {$feature_name}\n\n";
        
        // CACHE TEMİZLEME - Sadece cache_test için
        echo "=================================================\n";
        echo "CACHE TEMİZLEME (cache_test için)\n";
        echo "=================================================\n\n";
        
        $cache = new DODO_AI_Cache();
        $settings = new DODO_Settings();
        $model = $settings->get_setting('openai_model', 'gpt-4o');
        $temperature = $settings->get_setting('openai_temperature', 0.7);
        $max_tokens = 16000;
        
        // Cache key oluştur
        $cache_key = $cache->generate_cache_key(
            $model,
            $system_prompt,
            $user_prompt,
            $temperature,
            $max_tokens,
            $feature_name
        );
        
        echo "Cache Key: " . substr($cache_key, 0, 60) . "...\n";
        
        // Mevcut cache'i kontrol et
        $existing_cache = get_transient($cache_key);
        if ($existing_cache !== false) {
            echo "Mevcut Cache: ✅ BULUNDU (silinecek)\n";
            delete_transient($cache_key);
            echo "Cache Silindi: ✅ BAŞARILI\n";
        } else {
            echo "Mevcut Cache: ❌ YOK (temiz başlangıç)\n";
        }
        echo "\n";
        
        // OpenAI instance
        $openai = new DODO_OpenAI();
        $logger = new DODO_Usage_Logger();
        
        // TEST 1: İlk çağrı (Cache MISS)
        echo "=================================================\n";
        echo "TEST 1: İLK ÇAĞRI (Cache MISS Bekleniyor)\n";
        echo "=================================================\n\n";
        
        $start_time_1 = microtime(true);
        $response_1 = $openai->generate_content($system_prompt, $user_prompt, $feature_name);
        $end_time_1 = microtime(true);
        $execution_time_1 = round(($end_time_1 - $start_time_1) * 1000, 2);
        
        if (is_wp_error($response_1)) {
            echo "❌ HATA: " . $response_1->get_error_message() . "\n";
        } else {
            echo "✅ İlk Çağrı Başarılı\n";
            echo "Response: {$response_1}\n";
            echo "Execution Time: {$execution_time_1} ms\n\n";
        }
        
        // Kısa bekleme
        sleep(1);
        
        // TEST 2: İkinci çağrı (Cache HIT)
        echo "=================================================\n";
        echo "TEST 2: İKİNCİ ÇAĞRI (Cache HIT Bekleniyor)\n";
        echo "=================================================\n\n";
        
        $start_time_2 = microtime(true);
        $response_2 = $openai->generate_content($system_prompt, $user_prompt, $feature_name);
        $end_time_2 = microtime(true);
        $execution_time_2 = round(($end_time_2 - $start_time_2) * 1000, 2);
        
        if (is_wp_error($response_2)) {
            echo "❌ HATA: " . $response_2->get_error_message() . "\n";
        } else {
            echo "✅ İkinci Çağrı Başarılı\n";
            echo "Response: {$response_2}\n";
            echo "Execution Time: {$execution_time_2} ms\n\n";
        }
        
        // Response eşleşmesi
        $responses_match = ($response_1 === $response_2);
        echo "Response Eşleşmesi: " . ($responses_match ? "✅ EVET" : "❌ HAYIR") . "\n\n";
        
        // Cache stats
        echo "=================================================\n";
        echo "CACHE İSTATİSTİKLERİ\n";
        echo "=================================================\n\n";
        
        $cache_stats = $cache->get_cache_stats();
        echo "Cache Stats:\n";
        echo "- Total Requests: {$cache_stats['total_requests']}\n";
        echo "- Cache Hits: {$cache_stats['cache_hits']}\n";
        echo "- Cache Misses: {$cache_stats['cache_misses']}\n";
        echo "- Hit Rate: {$cache_stats['hit_rate']}%\n";
        echo "- Saved Requests: {$cache_stats['saved_requests']}\n\n";
        
        $cache_size = $cache->get_cache_size();
        echo "Cache Size:\n";
        echo "- Entry Count: {$cache_size['count']}\n";
        echo "- Size (KB): {$cache_size['size_kb']}\n";
        echo "- Size (MB): {$cache_size['size_mb']}\n\n";
        
        // Usage stats
        echo "=================================================\n";
        echo "USAGE LOGGER İSTATİSTİKLERİ\n";
        echo "=================================================\n\n";
        
        // Logger instance kontrolü
        echo "Logger Instance Check:\n";
        $test_logger = new DODO_Usage_Logger();
        echo "- Logger created: " . (is_object($test_logger) ? "✅ YES" : "❌ NO") . "\n";
        
        global $wpdb;
        $logger_table = $wpdb->prefix . 'dodo_ai_usage_logs';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$logger_table}'") === $logger_table;
        echo "- Table exists: " . ($table_exists ? "✅ YES" : "❌ NO") . " ({$logger_table})\n";
        
        if ($table_exists) {
            $record_count = $wpdb->get_var("SELECT COUNT(*) FROM {$logger_table}");
            echo "- Record count: {$record_count}\n";
        }
        echo "\n";
        
        $usage_stats = $logger->get_usage_stats('all');
        echo "Usage Stats (All Time):\n";
        echo "- Total Requests: {$usage_stats['total_requests']}\n";
        echo "- Total Tokens: {$usage_stats['total_tokens']}\n";
        echo "- Total Cost: $" . number_format($usage_stats['total_cost'], 6) . "\n";
        echo "- Cache Hits: {$usage_stats['cache_hits']}\n";
        echo "- Cache Misses: {$usage_stats['cache_misses']}\n";
        echo "- Cache Hit Rate: {$usage_stats['cache_hit_rate']}%\n";
        echo "- Avg Execution Time: {$usage_stats['avg_execution_time']} ms\n\n";
        
        // Feature breakdown
        $usage_by_feature = $logger->get_usage_by_feature('all');
        echo "Usage by Feature:\n";
        foreach ($usage_by_feature as $feature) {
            echo "- {$feature['feature_name']}: {$feature['requests']} requests, {$feature['total_tokens']} tokens, $" . number_format($feature['total_cost'], 6) . "\n";
        }
        echo "\n";
        
        // Recent logs
        $recent_logs = $logger->get_recent_logs(10);
        echo "Recent Logs (Last 10):\n";
        
        // cache_test için MISS ve HIT'leri ayır
        $cache_test_logs = array();
        $miss_log = null;
        $hit_log = null;
        
        foreach ($recent_logs as $log) {
            if ($log['feature_name'] === $feature_name) {
                $cache_test_logs[] = $log;
                if (!$log['cache_hit'] && $miss_log === null) {
                    $miss_log = $log;
                }
                if ($log['cache_hit'] && $hit_log === null) {
                    $hit_log = $log;
                }
            }
        }
        
        if (!empty($cache_test_logs)) {
            echo "\n📊 Bu Test İçin Kayıtlar (cache_test):\n\n";
            
            if ($miss_log) {
                echo "❌ CACHE MISS (İlk Çağrı - API Call):\n";
                echo "   - ID: {$miss_log['id']}\n";
                echo "   - Model: {$miss_log['model']}\n";
                echo "   - Tokens Input: {$miss_log['tokens_input']}\n";
                echo "   - Tokens Output: {$miss_log['tokens_output']}\n";
                echo "   - Total Tokens: {$miss_log['tokens_total']}\n";
                echo "   - Cost: $" . number_format($miss_log['estimated_cost'], 6) . "\n";
                echo "   - Execution Time: {$miss_log['execution_time']} ms\n";
                echo "   - Created: {$miss_log['created_at']}\n\n";
            }
            
            if ($hit_log) {
                echo "✅ CACHE HIT (İkinci Çağrı - Cache):\n";
                echo "   - ID: {$hit_log['id']}\n";
                echo "   - Model: {$hit_log['model']}\n";
                echo "   - Tokens Input: {$hit_log['tokens_input']} (cache hit, API çağrısı yok, maliyet yok)\n";
                echo "   - Tokens Output: {$hit_log['tokens_output']}\n";
                echo "   - Total Tokens: {$hit_log['tokens_total']}\n";
                echo "   - Cost: $" . number_format($hit_log['estimated_cost'], 6) . " (cache hit, maliyet yok)\n";
                echo "   - Execution Time: {$hit_log['execution_time']} ms\n";
                echo "   - Created: {$hit_log['created_at']}\n\n";
            }
            
            echo "Tüm cache_test Kayıtları:\n";
            foreach ($cache_test_logs as $log) {
                $cache_hit_label = $log['cache_hit'] ? '✅ HIT' : '❌ MISS';
                echo "- [{$log['created_at']}] {$cache_hit_label} - Tokens: {$log['tokens_total']} - Cost: $" . number_format($log['estimated_cost'], 6) . " - Time: {$log['execution_time']}ms\n";
            }
        } else {
            echo "⚠️  cache_test için kayıt bulunamadı!\n";
        }
        
        echo "\n\nTüm Recent Logs:\n";
        foreach ($recent_logs as $log) {
            $cache_hit_label = $log['cache_hit'] ? '✅ HIT' : '❌ MISS';
            echo "- [{$log['created_at']}] {$log['feature_name']} - {$cache_hit_label} - Tokens: {$log['tokens_total']} - Cost: $" . number_format($log['estimated_cost'], 6) . "\n";
        }
        echo "\n";
        
        // Performans analizi
        echo "=================================================\n";
        echo "PERFORMANS ANALİZİ\n";
        echo "=================================================\n\n";
        
        $time_saved = $execution_time_1 - $execution_time_2;
        $time_saved_percent = round(($time_saved / $execution_time_1) * 100, 2);
        
        echo "Execution Time Karşılaştırması:\n";
        echo "- İlk Çağrı (API): {$execution_time_1} ms\n";
        echo "- İkinci Çağrı (Cache): {$execution_time_2} ms\n";
        echo "- Zaman Tasarrufu: {$time_saved} ms ({$time_saved_percent}%)\n\n";
        
        // Token tasarrufu
        $token_savings = 0;
        $saved_tokens_from_cache = 0;
        
        foreach ($recent_logs as $log) {
            if (!$log['cache_hit'] && $log['feature_name'] === $feature_name) {
                $token_savings = $log['tokens_total'];
                break;
            }
        }
        
        // Cache'den saved tokens bilgisini al
        $cached_data_for_savings = get_transient($cache_key);
        if ($cached_data_for_savings !== false && isset($cached_data_for_savings['metadata'])) {
            $saved_tokens_from_cache = (int) ($cached_data_for_savings['metadata']['tokens_input'] ?? 0) + 
                                       (int) ($cached_data_for_savings['metadata']['tokens_output'] ?? 0);
        }
        
        echo "Token Tasarrufu:\n";
        echo "- İlk Çağrı Token Kullanımı (API): " . ($token_savings > 0 ? $token_savings : "N/A") . "\n";
        echo "- İkinci Çağrı Token Kullanımı (Cache): 0 (API çağrısı yok)\n";
        echo "- Tasarruf Edilen Tokens: " . ($saved_tokens_from_cache > 0 ? $saved_tokens_from_cache : $token_savings) . " tokens\n";
        
        if ($saved_tokens_from_cache > 0) {
            // Maliyet hesapla
            $model = $settings->get_setting('openai_model', 'gpt-4o');
            $logger = new DODO_Usage_Logger();
            
            // Metadata'dan input/output ayrımı
            $saved_input = (int) ($cached_data_for_savings['metadata']['tokens_input'] ?? 0);
            $saved_output = (int) ($cached_data_for_savings['metadata']['tokens_output'] ?? 0);
            $saved_cost = $logger->calculate_cost($model, $saved_input, $saved_output);
            
            echo "- Tasarruf Edilen Maliyet: $" . number_format($saved_cost, 6) . "\n";
        }
        echo "\n";
        
        // Transient kontrolü
        echo "=================================================\n";
        echo "TRANSIENT KONTROLÜ (Test Sonrası)\n";
        echo "=================================================\n\n";
        
        echo "Cache Key: " . substr($cache_key, 0, 60) . "...\n\n";
        
        $cached_data_after = get_transient($cache_key);
        if ($cached_data_after !== false) {
            echo "✅ Transient Bulundu (İkinci çağrıdan sonra oluşturuldu)\n";
            echo "- Feature: {$cached_data_after['feature']}\n";
            echo "- Cached At: {$cached_data_after['cached_at']}\n";
            echo "- Expires At: {$cached_data_after['expires_at']}\n";
            echo "- Response Length: " . strlen($cached_data_after['response']) . " chars\n";
            if (isset($cached_data_after['metadata'])) {
                echo "- Metadata:\n";
                foreach ($cached_data_after['metadata'] as $key => $value) {
                    echo "  - {$key}: {$value}\n";
                }
            }
        } else {
            echo "❌ Transient Bulunamadı (Beklenmeyen - ikinci çağrıdan sonra olmalı)\n";
        }
        echo "\n";
        
        // Test sonucu
        echo "=================================================\n";
        echo "TEST SONUCU\n";
        echo "=================================================\n\n";
        
        $test_passed = true;
        $issues = array();
        
        // cache_test için MISS ve HIT kontrolü
        $has_miss = false;
        $has_hit = false;
        
        foreach ($cache_test_logs as $log) {
            if (!$log['cache_hit']) {
                $has_miss = true;
            }
            if ($log['cache_hit']) {
                $has_hit = true;
            }
        }
        
        if (!$has_miss) {
            $test_passed = false;
            $issues[] = "Cache MISS kaydı bulunamadı (ilk çağrı)";
        }
        
        if (!$has_hit) {
            $test_passed = false;
            $issues[] = "Cache HIT kaydı bulunamadı (ikinci çağrı)";
        }
        
        // Response eşleşmesi
        if (!$responses_match) {
            $test_passed = false;
            $issues[] = "Response'lar eşleşmiyor";
        }
        
        // Transient kontrolü (test sonrası)
        $cached_data_after = get_transient($cache_key);
        if ($cached_data_after === false) {
            $test_passed = false;
            $issues[] = "Cache transient bulunamadı (ikinci çağrıdan sonra olmalı)";
        }
        
        // Performans kontrolü
        if ($execution_time_2 >= $execution_time_1) {
            $issues[] = "⚠️  Cache'li çağrı daha yavaş (beklenmeyen)";
        }
        
        // Usage logger kontrolü
        if (count($cache_test_logs) < 2) {
            $test_passed = false;
            $issues[] = "Usage logger'da 2 kayıt bekleniyor, " . count($cache_test_logs) . " bulundu";
        }
        
        if ($test_passed && empty($issues)) {
            echo "✅ TÜM TESTLER BAŞARILI!\n\n";
            echo "Cache Sistemi Özeti:\n";
            echo "- ✅ Cache temizleme çalıştı (test başında)\n";
            echo "- ✅ Cache MISS çalışıyor (ilk çağrı)\n";
            echo "- ✅ Cache HIT çalışıyor (ikinci çağrı)\n";
            echo "- ✅ Response'lar tutarlı\n";
            echo "- ✅ Transient doğru oluşturulmuş\n";
            echo "- ✅ Usage logger çalışıyor\n";
            echo "- ✅ MISS ve HIT ayrı kaydedildi\n";
            echo "- ✅ Token tasarrufu sağlanıyor\n";
            echo "- ✅ Performans artışı var ({$time_saved_percent}%)\n\n";
            
            if ($miss_log && $hit_log) {
                echo "Token Tasarrufu Detayı:\n";
                echo "- İlk Çağrı (MISS): {$miss_log['tokens_total']} tokens, $" . number_format($miss_log['estimated_cost'], 6) . " (gerçek API maliyeti)\n";
                echo "- İkinci Çağrı (HIT): {$hit_log['tokens_total']} tokens, $" . number_format($hit_log['estimated_cost'], 6) . " (cache, maliyet yok)\n";
                
                // Saved tokens metadata'dan al
                $cached_data_for_final = get_transient($cache_key);
                if ($cached_data_for_final !== false && isset($cached_data_for_final['metadata'])) {
                    $final_saved_input = (int) ($cached_data_for_final['metadata']['tokens_input'] ?? 0);
                    $final_saved_output = (int) ($cached_data_for_final['metadata']['tokens_output'] ?? 0);
                    $final_saved_total = $final_saved_input + $final_saved_output;
                    $final_saved_cost = $logger->calculate_cost($model, $final_saved_input, $final_saved_output);
                    
                    echo "- Gerçek Tasarruf: {$final_saved_total} tokens, $" . number_format($final_saved_cost, 6) . "\n";
                } else {
                    echo "- Tasarruf: {$miss_log['tokens_total']} tokens, $" . number_format($miss_log['estimated_cost'], 6) . "\n";
                }
            }
        } else {
            echo "❌ TEST BAŞARISIZ\n\n";
            echo "Sorunlar:\n";
            foreach ($issues as $issue) {
                echo "- {$issue}\n";
            }
        }
        
        echo "\n=================================================\n";
        echo "TEST TAMAMLANDI\n";
        echo "=================================================\n";
        
        echo '</div>';
        
        echo '<p style="margin-top: 20px;"><a href="' . admin_url('admin.php?page=dodo-cache-test') . '" class="button">🔄 Testi Tekrar Çalıştır</a></p>';
        ?>
        
    <?php endif; ?>
    
</div>
