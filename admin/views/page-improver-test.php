<?php
/**
 * Content Improver Test Page
 * 
 * Core engine test sayfası (sadece WP_DEBUG modda)
 */

// Doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

// WP_DEBUG kontrolü - production'da bu sayfa görünmemeli
if (!defined('WP_DEBUG') || !WP_DEBUG) {
    wp_die(
        __('Bu sayfa sadece development modda erişilebilir.', 'dodo-ai-seo'),
        __('Erişim Engellendi', 'dodo-ai-seo'),
        array('back_link' => true)
    );
}

// Test çalıştır
$run_test = isset($_POST['run_improver_test']) && check_admin_referer('dodo_improver_test', 'dodo_improver_test_nonce');

?>

<div class="wrap dodo-admin-wrap">
    <h1>🔧 Content Improver Test</h1>
    
    <div class="notice notice-warning">
        <p><strong>⚠️ Development Mode Only</strong></p>
        <p>Bu sayfa sadece core engine test için. UI ve production features henüz eklenmedi.</p>
    </div>
    
    <?php if (!$run_test): ?>
        
        <div class="notice notice-info">
            <p><strong>Test Senaryosu:</strong></p>
            <ul style="list-style: disc; margin-left: 20px;">
                <li>Sample FAQ content ile test</li>
                <li>Improve type: faq_improve</li>
                <li>Focus keyword: serigrafi</li>
                <li>Cache ve Usage Logger entegrasyonu</li>
                <li>Rollback safety kontrolü</li>
            </ul>
        </div>
        
        <form method="post" style="margin-top: 20px;">
            <?php wp_nonce_field('dodo_improver_test', 'dodo_improver_test_nonce'); ?>
            <input type="hidden" name="run_improver_test" value="1">
            <button type="submit" class="button button-primary button-hero">
                🚀 Test Improver
            </button>
        </form>
        
    <?php else: ?>
        
        <div class="notice notice-warning">
            <p><strong>⏳ Test çalışıyor... Lütfen bekleyin.</strong></p>
        </div>
        
        <?php
        echo '<div style="background: #fff; padding: 20px; margin-top: 20px; border: 1px solid #ccc; font-family: monospace; white-space: pre-wrap;">';
        
        echo "=================================================\n";
        echo "CONTENT IMPROVER CORE ENGINE TEST\n";
        echo "=================================================\n\n";
        
        // Improver instance
        $improver = new DODO_Content_Improver();
        
        // Run test
        $test_results = $improver->test_improver();
        
        echo "Test Status: " . strtoupper($test_results['status']) . "\n\n";
        
        if ($test_results['status'] === 'error') {
            echo "❌ ERROR: " . $test_results['error'] . "\n\n";
        } else {
            echo "✅ SUCCESS\n\n";
            
            echo "=== ORIGINAL CONTENT ===\n\n";
            echo $test_results['original'] . "\n\n";
            
            echo "=== IMPROVED CONTENT ===\n\n";
            echo $test_results['improved'] . "\n\n";
            
            echo "=== STATISTICS ===\n\n";
            echo "Original Length: " . $test_results['original_length'] . " chars\n";
            echo "Improved Length: " . $test_results['improved_length'] . " chars\n";
            echo "Length Diff: " . ($test_results['length_diff'] > 0 ? '+' : '') . $test_results['length_diff'] . " chars\n\n";
        }
        
        // Supported improve types
        echo "=== SUPPORTED IMPROVE TYPES ===\n\n";
        $improve_types = $improver->get_improve_types();
        foreach ($improve_types as $type) {
            $token_limit = $improver->get_token_limit($type);
            echo "- {$type}: max {$token_limit} tokens\n";
        }
        echo "\n";
        
        // Usage Logger check
        echo "=== USAGE LOGGER CHECK ===\n\n";
        $logger = new DODO_Usage_Logger();
        $recent_logs = $logger->get_recent_logs(5);
        
        $improver_logs = array_filter($recent_logs, function($log) {
            return strpos($log['feature_name'], '_improve') !== false;
        });
        
        if (!empty($improver_logs)) {
            echo "Recent Improver Logs:\n";
            foreach ($improver_logs as $log) {
                echo "- [{$log['created_at']}] {$log['feature_name']}: {$log['tokens_total']} tokens, $" . number_format($log['estimated_cost'], 6) . "\n";
            }
        } else {
            echo "No improver logs found yet.\n";
        }
        echo "\n";
        
        // Cache check
        echo "=== CACHE CHECK ===\n\n";
        $cache = new DODO_AI_Cache();
        $cache_stats = $cache->get_cache_stats();
        echo "Total Requests: {$cache_stats['total_requests']}\n";
        echo "Cache Hits: {$cache_stats['cache_hits']}\n";
        echo "Cache Misses: {$cache_stats['cache_misses']}\n";
        echo "Hit Rate: {$cache_stats['hit_rate']}%\n\n";
        
        echo "=================================================\n";
        echo "TEST COMPLETED\n";
        echo "=================================================\n";
        
        echo '</div>';
        
        echo '<p style="margin-top: 20px;"><a href="' . admin_url('admin.php?page=dodo-improver-test') . '" class="button">🔄 Run Test Again</a></p>';
        ?>
        
    <?php endif; ?>
    
</div>
