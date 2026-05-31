<?php
/**
 * GEO / AI Visibility Engine
 * 
 * Analyzes content for AI system retrievability and LLM optimization
 * 
 * @package DODO_AI_SEO
 * @since 3.0.0 (Sprint 5 - Phase 1)
 */

if (!defined('ABSPATH')) {
    exit;
}

// Prevent duplicate class declaration
if (class_exists('DODO_GEO_Engine')) {
    return;
}

class DODO_GEO_Engine {
    
    /**
     * Analyze content for GEO score
     * 
     * @param int $post_id Post ID
     * @return array GEO analysis results
     */
    public function analyze_content($post_id) {
        $post = get_post($post_id);
        
        if (!$post) {
            return new WP_Error('invalid_post', 'Geçersiz yazı ID');
        }
        
        $content = $post->post_content;
        $title = $post->post_title;
        
        // Run all analysis dimensions
        $scores = array(
            'answerability' => $this->analyze_answerability($content, $title),
            'citation_potential' => $this->analyze_citation_potential($content),
            'chunk_quality' => $this->analyze_chunk_quality($content),
            'entity_richness' => $this->analyze_entity_richness($content),
            'semantic_clarity' => $this->analyze_semantic_clarity($content),
            'retrieval_friendliness' => $this->analyze_retrieval_friendliness($content),
            'passage_extraction' => $this->analyze_passage_extraction($content),
            'ai_overview_compatibility' => $this->analyze_ai_overview_compatibility($content, $title),
            'conversational_intent' => $this->analyze_conversational_intent($content),
            'featured_snippet' => $this->analyze_featured_snippet($content),
        );
        
        // Calculate overall GEO score
        $geo_score = $this->calculate_geo_score($scores);
        
        // Generate recommendations
        $recommendations = $this->generate_recommendations($scores);
        
        // Identify strengths and weaknesses
        $strengths = $this->identify_strengths($scores);
        $weaknesses = $this->identify_weaknesses($scores);
        
        $result = array(
            'geo_score' => $geo_score,
            'scores' => $scores,
            'strengths' => $strengths,
            'weaknesses' => $weaknesses,
            'recommendations' => $recommendations,
            'analyzed_at' => current_time('mysql'),
        );
        
        // Save to database
        $this->save_analysis($post_id, $result);
        
        return $result;
    }
    
    /**
     * Analyze answerability score
     */
    private function analyze_answerability($content, $title) {
        $score = 50; // Base score
        
        // Check for question in title
        if (preg_match('/\?|how|what|why|when|where|who/i', $title)) {
            $score += 15;
        }
        
        // Check for direct answers
        $paragraphs = explode("\n\n", strip_tags($content));
        $has_short_answer = false;
        
        foreach ($paragraphs as $para) {
            $word_count = str_word_count($para);
            if ($word_count >= 20 && $word_count <= 60) {
                $has_short_answer = true;
                break;
            }
        }
        
        if ($has_short_answer) {
            $score += 20;
        }
        
        // Check for lists (structured answers)
        if (preg_match('/<ul>|<ol>|<li>/i', $content)) {
            $score += 10;
        }
        
        // Check for definition patterns
        if (preg_match('/is defined as|refers to|means that|is a/i', $content)) {
            $score += 5;
        }
        
        return min(100, $score);
    }
    
    /**
     * Analyze citation potential
     */
    private function analyze_citation_potential($content) {
        $score = 40; // Base score
        
        // Check for data and statistics
        if (preg_match('/\d+%|\d+ percent|according to|study shows|research indicates/i', $content)) {
            $score += 20;
        }
        
        // Check for authoritative language
        if (preg_match('/expert|professional|certified|proven|verified/i', $content)) {
            $score += 15;
        }
        
        // Check for sources
        if (preg_match('/source:|via|according to|cited by/i', $content)) {
            $score += 15;
        }
        
        // Check for external links (authority signals)
        $link_count = preg_match_all('/<a[^>]+href=["\']https?:\/\//i', $content);
        if ($link_count > 0) {
            $score += min(10, $link_count * 2);
        }
        
        return min(100, $score);
    }
    
    /**
     * Analyze chunk quality
     */
    private function analyze_chunk_quality($content) {
        $score = 50; // Base score
        
        $paragraphs = explode("\n\n", strip_tags($content));
        $good_chunks = 0;
        $total_chunks = count($paragraphs);
        
        foreach ($paragraphs as $para) {
            $word_count = str_word_count($para);
            
            // Ideal chunk: 50-150 words
            if ($word_count >= 50 && $word_count <= 150) {
                $good_chunks++;
            }
        }
        
        if ($total_chunks > 0) {
            $chunk_ratio = $good_chunks / $total_chunks;
            $score = round(50 + ($chunk_ratio * 50));
        }
        
        return min(100, $score);
    }
    
    /**
     * Analyze entity richness
     */
    private function analyze_entity_richness($content) {
        $score = 30; // Base score
        
        // Check for named entities (capitalized words)
        $capitalized_count = preg_match_all('/\b[A-Z][a-z]+(?:\s+[A-Z][a-z]+)*\b/', strip_tags($content));
        
        if ($capitalized_count > 10) {
            $score += 30;
        } elseif ($capitalized_count > 5) {
            $score += 20;
        } elseif ($capitalized_count > 0) {
            $score += 10;
        }
        
        // Check for technical terms
        if (preg_match('/\b[A-Z]{2,}\b/', $content)) {
            $score += 10;
        }
        
        // Check for numbers and data
        $number_count = preg_match_all('/\b\d+\b/', strip_tags($content));
        if ($number_count > 5) {
            $score += 15;
        }
        
        // Check for dates
        if (preg_match('/\b\d{4}\b|january|february|march|april|may|june|july|august|september|october|november|december/i', $content)) {
            $score += 15;
        }
        
        return min(100, $score);
    }
    
    /**
     * Analyze semantic clarity
     */
    private function analyze_semantic_clarity($content) {
        $score = 60; // Base score
        
        $text = strip_tags($content);
        
        // Check for ambiguous words
        $ambiguous_words = array('thing', 'stuff', 'something', 'various', 'etc', 'and so on');
        $ambiguous_count = 0;
        
        foreach ($ambiguous_words as $word) {
            $ambiguous_count += substr_count(strtolower($text), $word);
        }
        
        if ($ambiguous_count > 5) {
            $score -= 20;
        } elseif ($ambiguous_count > 2) {
            $score -= 10;
        }
        
        // Check for clear definitions
        if (preg_match('/specifically|precisely|exactly|clearly|in other words/i', $content)) {
            $score += 15;
        }
        
        // Check for examples
        if (preg_match('/for example|for instance|such as|like|including/i', $content)) {
            $score += 15;
        }
        
        // Check for headings (structure clarity)
        $heading_count = preg_match_all('/<h[2-6][^>]*>/i', $content);
        if ($heading_count > 3) {
            $score += 10;
        }
        
        return max(0, min(100, $score));
    }
    
    /**
     * Analyze retrieval friendliness
     */
    private function analyze_retrieval_friendliness($content) {
        $score = 50; // Base score
        
        // Check for headings
        $heading_count = preg_match_all('/<h[2-6][^>]*>/i', $content);
        if ($heading_count >= 5) {
            $score += 20;
        } elseif ($heading_count >= 3) {
            $score += 10;
        }
        
        // Check for lists
        $list_count = preg_match_all('/<ul>|<ol>/i', $content);
        if ($list_count > 0) {
            $score += 15;
        }
        
        // Check for bold/strong (key points)
        $bold_count = preg_match_all('/<strong>|<b>/i', $content);
        if ($bold_count > 3) {
            $score += 10;
        }
        
        // Check for short paragraphs (scannable)
        $paragraphs = explode("\n\n", strip_tags($content));
        $short_para_count = 0;
        
        foreach ($paragraphs as $para) {
            if (str_word_count($para) < 100) {
                $short_para_count++;
            }
        }
        
        if ($short_para_count > count($paragraphs) / 2) {
            $score += 5;
        }
        
        return min(100, $score);
    }
    
    /**
     * Analyze passage extraction suitability
     */
    private function analyze_passage_extraction($content) {
        $score = 50; // Base score
        
        $paragraphs = explode("\n\n", strip_tags($content));
        $standalone_count = 0;
        
        foreach ($paragraphs as $para) {
            $word_count = str_word_count($para);
            
            // Good passage: 40-100 words, complete sentences
            if ($word_count >= 40 && $word_count <= 100) {
                // Check if it's a complete thought (ends with period)
                if (preg_match('/\.$/', trim($para))) {
                    $standalone_count++;
                }
            }
        }
        
        if (count($paragraphs) > 0) {
            $ratio = $standalone_count / count($paragraphs);
            $score = round(30 + ($ratio * 70));
        }
        
        return min(100, $score);
    }
    
    /**
     * Analyze AI Overview compatibility
     */
    private function analyze_ai_overview_compatibility($content, $title) {
        $score = 40; // Base score
        
        // Check for summary at start
        $paragraphs = explode("\n\n", strip_tags($content));
        if (count($paragraphs) > 0) {
            $first_para_words = str_word_count($paragraphs[0]);
            if ($first_para_words >= 30 && $first_para_words <= 80) {
                $score += 20;
            }
        }
        
        // Check for hierarchical structure
        $h2_count = preg_match_all('/<h2[^>]*>/i', $content);
        $h3_count = preg_match_all('/<h3[^>]*>/i', $content);
        
        if ($h2_count >= 3 && $h3_count >= 2) {
            $score += 20;
        }
        
        // Check for key points (lists)
        if (preg_match('/<ul>|<ol>/i', $content)) {
            $score += 10;
        }
        
        // Check for tables (structured data)
        if (preg_match('/<table>/i', $content)) {
            $score += 10;
        }
        
        return min(100, $score);
    }
    
    /**
     * Analyze conversational intent
     */
    private function analyze_conversational_intent($content) {
        $score = 50; // Base score
        
        $text = strip_tags($content);
        
        // Check for question patterns
        $question_count = preg_match_all('/\?/', $text);
        if ($question_count > 2) {
            $score += 15;
        }
        
        // Check for conversational phrases
        $conversational = array('you', 'your', 'let\'s', 'we\'ll', 'here\'s', 'that\'s');
        $conversational_count = 0;
        
        foreach ($conversational as $phrase) {
            $conversational_count += substr_count(strtolower($text), $phrase);
        }
        
        if ($conversational_count > 10) {
            $score += 20;
        } elseif ($conversational_count > 5) {
            $score += 10;
        }
        
        // Check for FAQ section
        if (preg_match('/frequently asked|common questions|faq/i', $content)) {
            $score += 15;
        }
        
        return min(100, $score);
    }
    
    /**
     * Analyze featured snippet suitability
     */
    private function analyze_featured_snippet($content) {
        $score = 40; // Base score
        
        // Check for definition
        if (preg_match('/is defined as|refers to|means|is a type of/i', $content)) {
            $score += 20;
        }
        
        // Check for lists
        $list_count = preg_match_all('/<ul>|<ol>/i', $content);
        if ($list_count > 0) {
            $score += 20;
        }
        
        // Check for tables
        if (preg_match('/<table>/i', $content)) {
            $score += 20;
        }
        
        // Check for step-by-step
        if (preg_match('/step \d+|first|second|third|finally/i', $content)) {
            $score += 10;
        }
        
        return min(100, $score);
    }
    
    /**
     * Calculate overall GEO score
     */
    private function calculate_geo_score($scores) {
        $weights = array(
            'answerability' => 0.15,
            'citation_potential' => 0.10,
            'chunk_quality' => 0.10,
            'entity_richness' => 0.10,
            'semantic_clarity' => 0.10,
            'retrieval_friendliness' => 0.15,
            'passage_extraction' => 0.10,
            'ai_overview_compatibility' => 0.10,
            'conversational_intent' => 0.05,
            'featured_snippet' => 0.05,
        );
        
        $total = 0;
        foreach ($scores as $key => $score) {
            $total += $score * $weights[$key];
        }
        
        return round($total);
    }
    
    /**
     * Identify strengths
     */
    private function identify_strengths($scores) {
        $strengths = array();
        
        foreach ($scores as $key => $score) {
            if ($score >= 75) {
                $strengths[] = $this->get_dimension_label($key);
            }
        }
        
        return $strengths;
    }
    
    /**
     * Identify weaknesses
     */
    private function identify_weaknesses($scores) {
        $weaknesses = array();
        
        foreach ($scores as $key => $score) {
            if ($score < 60) {
                $weaknesses[] = $this->get_dimension_label($key);
            }
        }
        
        return $weaknesses;
    }
    
    /**
     * Generate recommendations
     */
    private function generate_recommendations($scores) {
        $recommendations = array();
        
        // Answerability
        if ($scores['answerability'] < 70) {
            $recommendations[] = array(
                'priority' => 'high',
                'category' => 'answerability',
                'action' => 'Başlangıca kısa, net bir cevap paragrafı ekle (40-60 kelime)',
                'impact' => '+' . (70 - $scores['answerability']) . ' puan',
            );
        }
        
        // Citation potential
        if ($scores['citation_potential'] < 70) {
            $recommendations[] = array(
                'priority' => 'high',
                'category' => 'citation',
                'action' => 'İstatistikler, veriler ve otoriter kaynaklar ekle',
                'impact' => '+' . (70 - $scores['citation_potential']) . ' puan',
            );
        }
        
        // Chunk quality
        if ($scores['chunk_quality'] < 70) {
            $recommendations[] = array(
                'priority' => 'medium',
                'category' => 'structure',
                'action' => 'Uzun paragrafları 50-150 kelimelik parçalara böl',
                'impact' => '+' . (70 - $scores['chunk_quality']) . ' puan',
            );
        }
        
        // Entity richness
        if ($scores['entity_richness'] < 70) {
            $recommendations[] = array(
                'priority' => 'medium',
                'category' => 'entities',
                'action' => 'Daha fazla isim, tarih, sayı ve teknik terim ekle',
                'impact' => '+' . (70 - $scores['entity_richness']) . ' puan',
            );
        }
        
        // Retrieval friendliness
        if ($scores['retrieval_friendliness'] < 70) {
            $recommendations[] = array(
                'priority' => 'high',
                'category' => 'structure',
                'action' => 'Daha fazla başlık, liste ve vurgulu metin ekle',
                'impact' => '+' . (70 - $scores['retrieval_friendliness']) . ' puan',
            );
        }
        
        // Sort by priority
        usort($recommendations, function($a, $b) {
            $priority_order = array('high' => 1, 'medium' => 2, 'low' => 3);
            return $priority_order[$a['priority']] - $priority_order[$b['priority']];
        });
        
        return $recommendations;
    }
    
    /**
     * Get dimension label
     */
    private function get_dimension_label($key) {
        $labels = array(
            'answerability' => 'Cevaplanabilirlik',
            'citation_potential' => 'Alıntı Potansiyeli',
            'chunk_quality' => 'Parça Kalitesi',
            'entity_richness' => 'Varlık Zenginliği',
            'semantic_clarity' => 'Anlamsal Netlik',
            'retrieval_friendliness' => 'Erişim Kolaylığı',
            'passage_extraction' => 'Pasaj Çıkarımı',
            'ai_overview_compatibility' => 'AI Overview Uyumu',
            'conversational_intent' => 'Konuşma Niyeti',
            'featured_snippet' => 'Öne Çıkan Snippet',
        );
        
        return $labels[$key] ?? $key;
    }
    
    /**
     * Save analysis to database
     */
    private function save_analysis($post_id, $result) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dodo_geo_scores';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") !== $table_name) {
            return false;
        }
        
        // Delete old analysis
        $wpdb->delete($table_name, array('post_id' => $post_id), array('%d'));
        
        // Insert new analysis
        $wpdb->insert(
            $table_name,
            array(
                'post_id' => $post_id,
                'geo_score' => $result['geo_score'],
                'answerability_score' => $result['scores']['answerability'],
                'citation_potential' => $result['scores']['citation_potential'],
                'chunk_quality' => $result['scores']['chunk_quality'],
                'entity_richness' => $result['scores']['entity_richness'],
                'semantic_clarity' => $result['scores']['semantic_clarity'],
                'retrieval_friendliness' => $result['scores']['retrieval_friendliness'],
                'passage_extraction' => $result['scores']['passage_extraction'],
                'ai_overview_compatibility' => $result['scores']['ai_overview_compatibility'],
                'conversational_intent' => $result['scores']['conversational_intent'],
                'featured_snippet' => $result['scores']['featured_snippet'],
                'analysis_data' => json_encode($result),
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ),
            array('%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%s', '%s')
        );
        
        return $wpdb->insert_id;
    }
    
    /**
     * Get saved analysis
     */
    public function get_analysis($post_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dodo_geo_scores';
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE post_id = %d ORDER BY created_at DESC LIMIT 1",
            $post_id
        ), ARRAY_A);
        
        if ($result) {
            $result['analysis_data'] = json_decode($result['analysis_data'], true);
        }
        
        return $result;
    }
    
    /**
     * Create database table
     */
    public static function create_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dodo_geo_scores';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            geo_score int(11) NOT NULL,
            answerability_score int(11) NOT NULL,
            citation_potential int(11) NOT NULL,
            chunk_quality int(11) NOT NULL,
            entity_richness int(11) NOT NULL,
            semantic_clarity int(11) NOT NULL,
            retrieval_friendliness int(11) NOT NULL,
            passage_extraction int(11) NOT NULL,
            ai_overview_compatibility int(11) NOT NULL,
            conversational_intent int(11) NOT NULL,
            featured_snippet int(11) NOT NULL,
            analysis_data longtext,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY post_id (post_id),
            KEY geo_score (geo_score),
            KEY updated_at (updated_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
