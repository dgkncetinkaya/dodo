<?php
/**
 * Content Clusters Page
 * 
 * @package DODO_AI_SEO
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap dodo-admin-wrap">
    <div class="dodo-page-header">
        <div class="dodo-page-header-content">
            <h1 class="dodo-page-title">
                <svg class="dodo-page-title-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="2"/><path d="M16.24 7.76a6 6 0 0 1 0 8.49m-8.48-.01a6 6 0 0 1 0-8.49m11.31-2.82a10 10 0 0 1 0 14.14m-14.14 0a10 10 0 0 1 0-14.14"/></svg>
                <?php echo esc_html__('İçerik Kümeleri', 'dodo-ai-seo'); ?>
            </h1>
            <p class="dodo-page-description">
                <?php echo esc_html__('Konu otoritesi haritalaması ve semantik küme analizi', 'dodo-ai-seo'); ?>
            </p>
        </div>
    </div>

    <!-- Content Clusters Layout -->
    <div class="dodo-clusters-layout">
        <!-- Main Content -->
        <div class="dodo-clusters-main">
                <!-- Loading State -->
                <div id="dodo-clusters-loading" class="dodo-loading">
                    <div class="dodo-spinner"></div>
                    <p class="dodo-loading-text"><?php echo esc_html__('İçerik kümeleri analiz ediliyor...', 'dodo-ai-seo'); ?></p>
                </div>

                <!-- Clusters Overview Stats -->
                <div id="dodo-clusters-overview" class="dodo-clusters-overview" style="display:none;">
                    <div class="dodo-stats-grid">
                        <div class="dodo-stat-card">
                            <div class="dodo-stat-label"><?php echo esc_html__('Konu Kümesi', 'dodo-ai-seo'); ?></div>
                            <div class="dodo-stat-value" id="cluster-count">0</div>
                        </div>
                        <div class="dodo-stat-card">
                            <div class="dodo-stat-label"><?php echo esc_html__('Ana İçerik', 'dodo-ai-seo'); ?></div>
                            <div class="dodo-stat-value" id="pillar-count">0</div>
                        </div>
                        <div class="dodo-stat-card">
                            <div class="dodo-stat-label"><?php echo esc_html__('Bağlantısız', 'dodo-ai-seo'); ?></div>
                            <div class="dodo-stat-value" id="orphan-count">0</div>
                        </div>
                        <div class="dodo-stat-card">
                            <div class="dodo-stat-label"><?php echo esc_html__('Çakışma', 'dodo-ai-seo'); ?></div>
                            <div class="dodo-stat-value" id="cannibalization-count">0</div>
                        </div>
                    </div>
                </div>

                <!-- Topic Clusters -->
                <div id="dodo-topic-clusters" class="dodo-section" style="display:none;">
                    <div class="dodo-section-header">
                        <h2 class="dodo-section-title"><?php echo esc_html__('Konu Kümeleri', 'dodo-ai-seo'); ?></h2>
                    </div>
                    <div id="topic-clusters-list" class="dodo-clusters-list"></div>
                </div>

                <!-- Orphan Content -->
                <div id="dodo-orphan-content" class="dodo-section" style="display:none;">
                    <div class="dodo-section-header">
                        <h2 class="dodo-section-title"><?php echo esc_html__('Bağlantısız İçerikler', 'dodo-ai-seo'); ?></h2>
                    </div>
                    <div id="orphan-content-list" class="dodo-orphan-list"></div>
                </div>

                <!-- Cannibalization Warnings -->
                <div id="dodo-cannibalization" class="dodo-section" style="display:none;">
                    <div class="dodo-section-header">
                        <h2 class="dodo-section-title"><?php echo esc_html__('Anahtar Kelime Çakışmaları', 'dodo-ai-seo'); ?></h2>
                    </div>
                    <div id="cannibalization-list" class="dodo-cannibalization-list"></div>
                </div>
            </div>

            <!-- Right Sidebar -->
            <div class="dodo-clusters-sidebar">
                <!-- Quick Actions -->
                <div class="dodo-sidebar-card">
                    <h3 class="dodo-sidebar-card-title"><?php echo esc_html__('Hızlı İşlemler', 'dodo-ai-seo'); ?></h3>
                    <div class="dodo-sidebar-actions">
                        <button id="dodo-refresh-clusters" class="dodo-btn-secondary dodo-btn-sm" style="width: 100%;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2v6h-6"/><path d="M3 12a9 9 0 0 1 15-6.7L21 8"/><path d="M3 22v-6h6"/><path d="M21 12a9 9 0 0 1-15 6.7L3 16"/></svg>
                            <?php echo esc_html__('Analizi Yenile', 'dodo-ai-seo'); ?>
                        </button>
                    </div>
                </div>

                <!-- Pillar Pages -->
                <div id="dodo-pillar-pages" class="dodo-sidebar-card" style="display:none;">
                    <h3 class="dodo-sidebar-card-title"><?php echo esc_html__('Ana İçerikler', 'dodo-ai-seo'); ?></h3>
                    <div id="pillar-pages-list" class="dodo-pillar-list"></div>
                </div>

                <!-- Insights -->
                <div class="dodo-sidebar-card">
                    <h3 class="dodo-sidebar-card-title"><?php echo esc_html__('İçgörüler', 'dodo-ai-seo'); ?></h3>
                    <ul class="dodo-sidebar-list">
                        <li><?php echo esc_html__('Küme analizi SEO otoritesi için kritik', 'dodo-ai-seo'); ?></li>
                        <li><?php echo esc_html__('Ana içerikler yüksek kaliteli olmalı', 'dodo-ai-seo'); ?></li>
                        <li><?php echo esc_html__('Bağlantısız içerikleri kümelere bağlayın', 'dodo-ai-seo'); ?></li>
                        <li><?php echo esc_html__('Anahtar kelime çakışmalarını çözün', 'dodo-ai-seo'); ?></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
