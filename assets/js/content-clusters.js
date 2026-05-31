/**
 * Content Clusters UI
 * 
 * @package DODO_AI_SEO
 * @since 2.0.0
 */

jQuery(document).ready(function($) {
    
    // Load clusters on page load
    loadClusters();
    
    // Refresh button
    $('#dodo-refresh-clusters').on('click', function() {
        loadClusters();
    });
    
    function loadClusters() {
        $('#dodo-clusters-loading').show();
        $('#dodo-clusters-overview').hide();
        $('#dodo-pillar-pages').hide();
        $('#dodo-topic-clusters').hide();
        $('#dodo-orphan-content').hide();
        $('#dodo-cannibalization').hide();
        
        $.ajax({
            url: dodoClusters.ajaxUrl,
            type: 'POST',
            data: {
                action: 'dodo_analyze_clusters',
                nonce: dodoClusters.nonce
            },
            success: function(response) {
                if (response.success) {
                    renderClusters(response.data);
                } else {
                    alert(response.data.message || 'Kümeler yüklenirken hata oluştu');
                }
            },
            error: function() {
                alert('AJAX hatası');
            },
            complete: function() {
                $('#dodo-clusters-loading').hide();
            }
        });
    }
    
    function renderClusters(data) {
        // Overview stats
        $('#cluster-count').text(data.total_clusters || 0);
        $('#pillar-count').text(data.pillar_pages.length || 0);
        $('#orphan-count').text(data.orphan_content.length || 0);
        $('#cannibalization-count').text(data.cannibalization_risks.length || 0);
        $('#dodo-clusters-overview').show();
        
        // Pillar pages
        if (data.pillar_pages.length > 0) {
            renderPillarPages(data.pillar_pages);
            $('#dodo-pillar-pages').show();
        }
        
        // Topic clusters
        if (Object.keys(data.clusters).length > 0) {
            renderTopicClusters(data.clusters);
            $('#dodo-topic-clusters').show();
        }
        
        // Orphan content
        if (data.orphan_content.length > 0) {
            renderOrphanContent(data.orphan_content);
            $('#dodo-orphan-content').show();
        }
        
        // Cannibalization
        if (data.cannibalization_risks.length > 0) {
            renderCannibalization(data.cannibalization_risks);
            $('#dodo-cannibalization').show();
        }
    }
    
    function renderPillarPages(pillars) {
        let html = '';
        
        pillars.forEach(function(pillar) {
            html += '<div class="dodo-pillar-card">';
            html += '<div class="dodo-pillar-header">';
            html += '<h3><a href="' + pillar.post_url + '" target="_blank">' + pillar.post_title + '</a></h3>';
            html += '<div class="dodo-pillar-score">';
            html += '<span class="dodo-score-value">' + pillar.pillar_score + '</span>';
            html += '<span class="dodo-score-label">Otorite</span>';
            html += '</div>';
            html += '</div>';
            html += '<div class="dodo-pillar-meta">';
            html += '<span class="dodo-meta-item"><span class="dashicons dashicons-media-text"></span> ' + pillar.word_count + ' kelime</span>';
            html += '</div>';
            html += '<div class="dodo-pillar-reasons">';
            pillar.reasons.forEach(function(reason) {
                html += '<span class="dodo-reason-badge">' + reason + '</span>';
            });
            html += '</div>';
            html += '</div>';
        });
        
        $('#pillar-pages-list').html(html);
    }
    
    function renderTopicClusters(clusters) {
        let html = '';
        
        Object.values(clusters).forEach(function(cluster) {
            let authorityClass = cluster.authority_score >= 70 ? 'high' : (cluster.authority_score >= 40 ? 'medium' : 'low');
            
            html += '<div class="dodo-cluster-card">';
            html += '<div class="dodo-cluster-header">';
            html += '<h3>' + cluster.topic + '</h3>';
            html += '<div class="dodo-cluster-authority dodo-authority-' + authorityClass + '">';
            html += '<span class="dodo-authority-value">' + cluster.authority_score + '</span>';
            html += '<span class="dodo-authority-label">Otorite</span>';
            html += '</div>';
            html += '</div>';
            html += '<div class="dodo-cluster-stats">';
            html += '<span class="dodo-stat-item"><span class="dashicons dashicons-admin-page"></span> ' + cluster.post_count + ' içerik</span>';
            html += '<span class="dodo-stat-item"><span class="dashicons dashicons-media-text"></span> ' + cluster.total_words + ' kelime</span>';
            html += '</div>';
            html += '<div class="dodo-cluster-posts">';
            cluster.posts.slice(0, 5).forEach(function(post) {
                html += '<div class="dodo-cluster-post">';
                html += '<a href="' + post.post_url + '" target="_blank">' + post.post_title + '</a>';
                html += '<span class="dodo-post-words">' + post.word_count + ' kelime</span>';
                html += '</div>';
            });
            if (cluster.posts.length > 5) {
                html += '<div class="dodo-cluster-more">' + (cluster.posts.length - 5) + ' içerik daha</div>';
            }
            html += '</div>';
            html += '</div>';
        });
        
        $('#topic-clusters-list').html(html);
    }
    
    function renderOrphanContent(orphans) {
        let html = '';
        
        orphans.forEach(function(orphan) {
            html += '<div class="dodo-orphan-card">';
            html += '<div class="dodo-orphan-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></div>';
            html += '<div class="dodo-orphan-content">';
            html += '<h4><a href="' + orphan.post_url + '" target="_blank">' + orphan.post_title + '</a></h4>';
            html += '<p>Bu içerik güçlü bir konu kümesinin parçası değil. İlgili içeriklerle bağlantılandırın veya konuyu genişletin.</p>';
            html += '</div>';
            html += '</div>';
        });
        
        $('#orphan-content-list').html(html);
    }
    
    function renderCannibalization(risks) {
        let html = '';
        
        risks.forEach(function(risk) {
            html += '<div class="dodo-cannibalization-card">';
            html += '<div class="dodo-cannibalization-header">';
            html += '<h3><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px;display:inline-block;vertical-align:middle;margin-right:8px;color:#ef4444;"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>' + risk.topic + '</h3>';
            html += '<span class="dodo-severity-badge dodo-severity-' + risk.severity + '">' + risk.severity + '</span>';
            html += '</div>';
            html += '<p>' + risk.post_count + ' içerik benzer anahtar kelimeleri hedefliyor - arama sonuçlarında rekabet edebilir</p>';
            html += '<div class="dodo-cannibalization-posts">';
            risk.posts.forEach(function(post) {
                html += '<div class="dodo-cannibalization-post">';
                html += '<a href="' + post.post_url + '" target="_blank">' + post.post_title + '</a>';
                if (post.focus_keyword) {
                    html += '<span class="dodo-keyword-badge">' + post.focus_keyword + '</span>';
                }
                html += '</div>';
            });
            html += '</div>';
            html += '</div>';
        });
        
        $('#cannibalization-list').html(html);
    }
});
