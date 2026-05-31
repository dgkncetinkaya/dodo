<?php
/**
 * Humanization Engine
 * 
 * Transforms robotic AI content into natural, human-like writing
 * 
 * @package DODO_AI_SEO
 * @since 3.0.0 (Sprint 5 - Phase 4)
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Humanizer {
    
    /**
     * OpenAI instance
     */
    private $openai;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->openai = new DODO_OpenAI();
    }
    
    /**
     * Analyze content for humanization
     * 
     * @param int $post_id Post ID
     * @return array Humanization analysis
     */
    public function analyze_content($post_id) {
        $post = get_post($post_id);
        
        if (!$post) {
            return new WP_Error('invalid_post', 'Geçersiz yazı ID');
        }
        
        $content = strip_tags($post->post_content);
        
        // Run all analysis dimensions
        $scores = array(
            'robotic_score' => $this->detect_robotic_patterns($content),
            'burstiness_score' => $this->analyze_burstiness($content),
            'transition_score' => $this->analyze_transitions($content),
            'conversational_score' => $this->analyze_conversational_balance($content),
            'rhythm_score' => $this->analyze_paragraph_rhythm($content),
        );
        
        // Calculate overall humanization score (inverse of robotic score)
        $humanization_score = $this->calculate_humanization_score($scores);
        
        // Generate recommendations
        $recommendations = $this->generate_recommendations($scores);
        
        // Identify issues
        $issues = $this->identify_issues($scores, $content);
        
        $result = array(
            'humanization_score' => $humanization_score,
            'scores' => $scores,
            'issues' => $issues,
            'recommendations' => $recommendations,
            'analyzed_at' => current_time('mysql'),
        );
        
        // Save to database
        $this->save_analysis($post_id, $result);
        
        return $result;
    }
    
    /**
     * Detect robotic patterns
     */
    private function detect_robotic_patterns($content) {
        $score = 100; // Start with perfect score
        
        // Common AI phrases
        $ai_phrases = array(
            'it is important to note',
            'it\'s worth noting',
            'in conclusion',
            'in summary',
            'furthermore',
            'moreover',
            'additionally',
            'consequently',
            'therefore',
            'thus',
            'hence',
            'delve into',
            'dive into',
            'explore the intricacies',
            'it\'s crucial to understand',
            'plays a pivotal role',
            'in today\'s digital landscape',
            'in the ever-evolving',
            'revolutionize',
            'game-changer',
            'cutting-edge',
        );
        
        $content_lower = strtolower($content);
        $phrase_count = 0;
        
        foreach ($ai_phrases as $phrase) {
            $phrase_count += substr_count($content_lower, $phrase);
        }
        
        // Penalize for AI phrases (each occurrence -5 points)
        $score -= min(50, $phrase_count * 5);
        
        // Check for repetitive sentence starters
        $sentences = preg_split('/[.!?]+/', $content);
        $starters = array();
        
        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);
            if (empty($sentence)) continue;
            
            $words = explode(' ', $sentence);
            if (count($words) > 0) {
                $starter = strtolower($words[0]);
                if (!isset($starters[$starter])) {
                    $starters[$starter] = 0;
                }
                $starters[$starter]++;
            }
        }
        
        // Penalize for repetitive starters
        foreach ($starters as $count) {
            if ($count > 3) {
                $score -= ($count - 3) * 3;
            }
        }
        
        return max(0, min(100, $score));
    }
    
    /**
     * Analyze burstiness (sentence length variation)
     */
    private function analyze_burstiness($content) {
        $sentences = preg_split('/[.!?]+/', $content);
        $lengths = array();
        
        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);
            if (empty($sentence)) continue;
            
            $word_count = str_word_count($sentence);
            if ($word_count > 0) {
                $lengths[] = $word_count;
            }
        }
        
        if (count($lengths) < 3) {
            return 50; // Not enough data
        }
        
        // Calculate standard deviation
        $mean = array_sum($lengths) / count($lengths);
        $variance = 0;
        
        foreach ($lengths as $length) {
            $variance += pow($length - $mean, 2);
        }
        
        $variance = $variance / count($lengths);
        $std_dev = sqrt($variance);
        
        // Good burstiness: std_dev between 5-15
        if ($std_dev >= 5 && $std_dev <= 15) {
            $score = 100;
        } elseif ($std_dev < 5) {
            // Too uniform (robotic)
            $score = 50 + ($std_dev * 10);
        } else {
            // Too varied (chaotic)
            $score = 100 - (($std_dev - 15) * 3);
        }
        
        return max(0, min(100, round($score)));
    }
    
    /**
     * Analyze transitions
     */
    private function analyze_transitions($content) {
        $score = 60; // Base score
        
        // Good transition words
        $good_transitions = array(
            'however', 'but', 'yet', 'still', 'although',
            'meanwhile', 'similarly', 'likewise',
            'for example', 'for instance', 'such as',
            'in fact', 'actually', 'indeed',
            'on the other hand', 'in contrast',
        );
        
        // Overused formal transitions (robotic)
        $formal_transitions = array(
            'furthermore', 'moreover', 'additionally',
            'consequently', 'therefore', 'thus', 'hence',
        );
        
        $content_lower = strtolower($content);
        
        $good_count = 0;
        foreach ($good_transitions as $transition) {
            $good_count += substr_count($content_lower, $transition);
        }
        
        $formal_count = 0;
        foreach ($formal_transitions as $transition) {
            $formal_count += substr_count($content_lower, $transition);
        }
        
        // Reward good transitions
        $score += min(20, $good_count * 2);
        
        // Penalize formal transitions
        $score -= min(30, $formal_count * 5);
        
        return max(0, min(100, $score));
    }
    
    /**
     * Analyze conversational balance
     */
    private function analyze_conversational_balance($content) {
        $score = 50; // Base score
        
        $content_lower = strtolower($content);
        $word_count = str_word_count($content);
        
        if ($word_count === 0) {
            return 0;
        }
        
        // Conversational indicators
        $you_count = substr_count($content_lower, ' you ') + substr_count($content_lower, ' your ');
        $we_count = substr_count($content_lower, ' we ') + substr_count($content_lower, ' our ');
        $question_count = substr_count($content, '?');
        $contraction_count = substr_count($content, "'");
        
        // Calculate ratios
        $you_ratio = ($you_count / $word_count) * 1000;
        $we_ratio = ($we_count / $word_count) * 1000;
        $question_ratio = ($question_count / $word_count) * 1000;
        
        // Reward conversational elements
        if ($you_ratio > 2) $score += 15;
        if ($we_ratio > 1) $score += 10;
        if ($question_ratio > 0.5) $score += 15;
        if ($contraction_count > 3) $score += 10;
        
        return min(100, $score);
    }
    
    /**
     * Analyze paragraph rhythm
     */
    private function analyze_paragraph_rhythm($content) {
        $paragraphs = explode("\n\n", $content);
        $lengths = array();
        
        foreach ($paragraphs as $para) {
            $para = trim($para);
            if (empty($para)) continue;
            
            $word_count = str_word_count($para);
            if ($word_count > 0) {
                $lengths[] = $word_count;
            }
        }
        
        if (count($lengths) < 3) {
            return 50;
        }
        
        // Calculate variation
        $mean = array_sum($lengths) / count($lengths);
        $variance = 0;
        
        foreach ($lengths as $length) {
            $variance += pow($length - $mean, 2);
        }
        
        $variance = $variance / count($lengths);
        $std_dev = sqrt($variance);
        
        // Good rhythm: varied paragraph lengths
        if ($std_dev >= 20 && $std_dev <= 60) {
            $score = 100;
        } elseif ($std_dev < 20) {
            $score = 50 + ($std_dev * 2.5);
        } else {
            $score = 100 - (($std_dev - 60) * 1);
        }
        
        return max(0, min(100, round($score)));
    }
    
    /**
     * Calculate overall humanization score
     */
    private function calculate_humanization_score($scores) {
        // Robotic score is inverse (100 - robotic = human)
        $human_score = 100 - $scores['robotic_score'];
        
        $weights = array(
            'human' => 0.30,
            'burstiness' => 0.25,
            'transition' => 0.15,
            'conversational' => 0.20,
            'rhythm' => 0.10,
        );
        
        $total = ($human_score * $weights['human']) +
                 ($scores['burstiness_score'] * $weights['burstiness']) +
                 ($scores['transition_score'] * $weights['transition']) +
                 ($scores['conversational_score'] * $weights['conversational']) +
                 ($scores['rhythm_score'] * $weights['rhythm']);
        
        return round($total);
    }
    
    /**
     * Identify issues
     */
    private function identify_issues($scores, $content) {
        $issues = array();
        
        if ($scores['robotic_score'] > 40) {
            $issues[] = array(
                'type' => 'robotic_phrases',
                'severity' => 'high',
                'description' => 'İçerik çok fazla AI kalıbı içeriyor',
            );
        }
        
        if ($scores['burstiness_score'] < 60) {
            $issues[] = array(
                'type' => 'monotonous_sentences',
                'severity' => 'medium',
                'description' => 'Cümle uzunlukları çok tekdüze',
            );
        }
        
        if ($scores['transition_score'] < 50) {
            $issues[] = array(
                'type' => 'poor_transitions',
                'severity' => 'medium',
                'description' => 'Geçişler çok formal veya eksik',
            );
        }
        
        if ($scores['conversational_score'] < 50) {
            $issues[] = array(
                'type' => 'not_conversational',
                'severity' => 'low',
                'description' => 'İçerik yeterince konuşma dilinde değil',
            );
        }
        
        if ($scores['rhythm_score'] < 60) {
            $issues[] = array(
                'type' => 'poor_rhythm',
                'severity' => 'low',
                'description' => 'Paragraf uzunlukları monoton',
            );
        }
        
        return $issues;
    }
    
    /**
     * Generate recommendations
     */
    private function generate_recommendations($scores) {
        $recommendations = array();
        
        if ($scores['robotic_score'] > 40) {
            $recommendations[] = array(
                'priority' => 'high',
                'action' => 'AI kalıplarını kaldır ve daha doğal ifadeler kullan',
                'examples' => array(
                    'Kötü: "It is important to note that..."',
                    'İyi: "Keep in mind..."',
                ),
            );
        }
        
        if ($scores['burstiness_score'] < 60) {
            $recommendations[] = array(
                'priority' => 'high',
                'action' => 'Cümle uzunluklarını çeşitlendir (kısa, orta, uzun)',
                'examples' => array(
                    'Kısa cümle. Orta uzunlukta bir cümle ekle. Sonra daha uzun, detaylı bir cümle kullan.',
                ),
            );
        }
        
        if ($scores['transition_score'] < 50) {
            $recommendations[] = array(
                'priority' => 'medium',
                'action' => 'Doğal geçiş kelimeleri kullan (however, but, yet)',
                'examples' => array(
                    'Formal: "Furthermore, moreover"',
                    'Doğal: "But, however, yet"',
                ),
            );
        }
        
        if ($scores['conversational_score'] < 50) {
            $recommendations[] = array(
                'priority' => 'medium',
                'action' => 'Daha fazla "you", "we" ve soru kullan',
                'examples' => array(
                    'Formal: "One should consider..."',
                    'Konuşma: "You should consider..."',
                ),
            );
        }
        
        return $recommendations;
    }
    
    /**
     * Humanize content with preset
     * 
     * @param string $content Content to humanize
     * @param string $preset Humanization preset
     * @return string Humanized content
     */
    public function humanize_content($content, $preset = 'human-like') {
        $preset_prompts = $this->get_preset_prompts();
        
        if (!isset($preset_prompts[$preset])) {
            $preset = 'human-like';
        }
        
        $prompt = $preset_prompts[$preset];
        
        $full_prompt = "{$prompt}

Original content:
{$content}

Humanized version:";

        $response = $this->openai->generate_text($full_prompt, array(
            'max_tokens' => 2000,
            'temperature' => 0.7,
        ));
        
        if (is_wp_error($response)) {
            return $content; // Return original on error
        }
        
        return trim($response);
    }
    
    /**
     * Get humanization preset prompts
     */
    private function get_preset_prompts() {
        return array(
            'human-like' => 'Rewrite this content to sound more natural and human-like. Remove AI phrases, vary sentence length, use conversational tone, and add personality. Keep the core message and SEO keywords.',
            
            'editorial' => 'Rewrite this content in professional editorial style. Use clear, authoritative language. Maintain journalistic standards. Remove AI clichés. Keep it factual and well-structured.',
            
            'technical' => 'Rewrite this content in technical style. Use precise terminology. Be accurate and detailed. Remove fluff and AI phrases. Maintain technical depth while being readable.',
            
            'founder-voice' => 'Rewrite this content in authentic founder voice. Be personal, opinionated, and story-driven. Share insights and experiences. Remove corporate AI language. Sound like a real person.',
            
            'conversion-focused' => 'Rewrite this content for conversion. Be persuasive and action-oriented. Focus on benefits. Use power words. Remove AI fluff. Drive the reader to take action.',
        );
    }
    
    /**
     * Get preset labels
     */
    public function get_preset_labels() {
        return array(
            'human-like' => 'İnsan Gibi',
            'editorial' => 'Editöryel',
            'technical' => 'Teknik',
            'founder-voice' => 'Kurucu Sesi',
            'conversion-focused' => 'Dönüşüm Odaklı',
        );
    }
    
    /**
     * Save analysis to database
     */
    private function save_analysis($post_id, $result) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dodo_humanization_analysis';
        
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
                'humanization_score' => $result['humanization_score'],
                'robotic_score' => $result['scores']['robotic_score'],
                'burstiness_score' => $result['scores']['burstiness_score'],
                'transition_score' => $result['scores']['transition_score'],
                'conversational_score' => $result['scores']['conversational_score'],
                'rhythm_score' => $result['scores']['rhythm_score'],
                'analysis_data' => json_encode($result),
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ),
            array('%d', '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%s', '%s')
        );
        
        return $wpdb->insert_id;
    }
    
    /**
     * Get saved analysis
     */
    public function get_analysis($post_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dodo_humanization_analysis';
        
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
        
        $table_name = $wpdb->prefix . 'dodo_humanization_analysis';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            humanization_score int(11) NOT NULL,
            robotic_score int(11) NOT NULL,
            burstiness_score int(11) NOT NULL,
            transition_score int(11) NOT NULL,
            conversational_score int(11) NOT NULL,
            rhythm_score int(11) NOT NULL,
            analysis_data longtext,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY post_id (post_id),
            KEY humanization_score (humanization_score),
            KEY updated_at (updated_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
