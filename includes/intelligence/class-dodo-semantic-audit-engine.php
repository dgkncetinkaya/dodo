<?php
/**
 * Semantic Coverage Audit Engine
 * 
 * Detects missing entities, subtopics, and semantic gaps
 * Sprint E - Feedback Loop & Self-Learning SEO System
 * 
 * @package DODO_AI_SEO
 * @since 3.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Semantic_Audit_Engine {
    
    /**
     * Audit semantic coverage of content
     * 
     * @param int $post_id
     * @return array Audit results
     */
    public function audit_semantic_coverage($post_id) {
        $audit = array(
            'post_id' => $post_id,
            'semantic_depth' => 0,
            'coverage_score' => 0,
            'missing_entities' => array(),
            'missing_subtopics' => array(),
            'faq_gaps' => array(),
            'incomplete_answers' => array(),
            'recommendations' => array(),
        );
        
        try {
            $post = get_post($post_id);
            
            if (!$post) {
                return $audit;
            }
            
            // 1. Detect missing entities
            $audit['missing_entities'] = $this->detect_missing_entities($post);
            
            // 2. Detect missing subtopics
            $audit['missing_subtopics'] = $this->detect_missing_subtopics($post);
            
            // 3. Detect FAQ gaps
            $audit['faq_gaps'] = $this->detect_faq_gaps($post);
            
            // 4. Detect incomplete answers
            $audit['incomplete_answers'] = $this->detect_incomplete_answers($post);
            
            // 5. Calculate semantic depth
            $audit['semantic_depth'] = $this->calculate_semantic_depth($post);
            
            // 6. Calculate coverage score
            $audit['coverage_score'] = $this->calculate_coverage_score($audit);
            
            // 7. Generate recommendations
            $audit['recommendations'] = $this->generate_recommendations($audit);
            
            // Save audit results
            $this->save_audit_results($post_id, $audit);
            
        } catch (Throwable $e) {
            error_log('[DODO][Semantic Audit] Error auditing coverage: ' . $e->getMessage());
        }
        
        return $audit;
    }
    
    /**
     * Detect missing entities
     * 
     * @param WP_Post $post
     * @return array Missing entities
     */
    public function detect_missing_entities($post) {
        $missing = array();
        
        try {
            $content = $post->post_content;
            $title = $post->post_title;
            
            // Extract main topic from title
            $main_topic = $this->extract_main_topic($title);
            
            if (empty($main_topic)) {
                return $missing;
            }
            
            // Expected entities based on topic
            $expected_entities = $this->get_expected_entities($main_topic);
            
            // Check which entities are missing
            foreach ($expected_entities as $entity) {
                if (stripos($content, $entity) === false) {
                    $missing[] = array(
                        'entity' => $entity,
                        'importance' => 'high',
                        'reason' => 'Temel entity eksik',
                    );
                }
            }
            
            // Check for numbers/statistics
            $has_numbers = preg_match('/\d+%|\d+\s*(milyon|bin|yüzde)/i', $content);
            
            if (!$has_numbers) {
                $missing[] = array(
                    'entity' => 'İstatistikler/Sayılar',
                    'importance' => 'medium',
                    'reason' => 'Veri ve istatistik eksik',
                );
            }
            
            // Check for examples
            $has_examples = preg_match('/(örneğin|mesela|örnek|for example)/i', $content);
            
            if (!$has_examples) {
                $missing[] = array(
                    'entity' => 'Örnekler',
                    'importance' => 'medium',
                    'reason' => 'Pratik örnek eksik',
                );
            }
            
        } catch (Throwable $e) {
            error_log('[DODO][Semantic Audit] Error detecting entities: ' . $e->getMessage());
        }
        
        return $missing;
    }
    
    /**
     * Extract main topic from title
     * 
     * @param string $title
     * @return string Main topic
     */
    private function extract_main_topic($title) {
        // Remove common words
        $stop_words = array('nasıl', 'nedir', 'ne', 'en iyi', 'için', 'ile', 've', 'bir', 'bu', '2024', '2025', '2026');
        
        $topic = $title;
        
        foreach ($stop_words as $word) {
            $topic = str_ireplace($word, '', $topic);
        }
        
        $topic = trim($topic);
        
        return $topic;
    }
    
    /**
     * Get expected entities for topic
     * 
     * @param string $topic
     * @return array Expected entities
     */
    private function get_expected_entities($topic) {
        $entities = array();
        
        // Generic entities that should appear in most content
        $generic_entities = array(
            'avantaj',
            'dezavantaj',
            'fayda',
            'kullanım',
            'özellik',
        );
        
        // Topic-specific entities (simplified)
        $topic_lower = mb_strtolower($topic, 'UTF-8');
        
        if (strpos($topic_lower, 'seo') !== false) {
            $entities = array_merge($entities, array('anahtar kelime', 'backlink', 'içerik', 'google'));
        }
        
        if (strpos($topic_lower, 'wordpress') !== false) {
            $entities = array_merge($entities, array('plugin', 'tema', 'güvenlik', 'performans'));
        }
        
        if (strpos($topic_lower, 'pazarlama') !== false || strpos($topic_lower, 'marketing') !== false) {
            $entities = array_merge($entities, array('strateji', 'hedef kitle', 'dönüşüm', 'analiz'));
        }
        
        // Add generic entities
        $entities = array_merge($entities, $generic_entities);
        
        return array_unique($entities);
    }
    
    /**
     * Detect missing subtopics
     * 
     * @param WP_Post $post
     * @return array Missing subtopics
     */
    public function detect_missing_subtopics($post) {
        $missing = array();
        
        try {
            $content = $post->post_content;
            $title = $post->post_title;
            
            // Extract headings
            preg_match_all('/<h[2-3][^>]*>(.*?)<\/h[2-3]>/i', $content, $matches);
            $existing_headings = $matches[1] ?? array();
            
            // Expected subtopics based on title
            $expected_subtopics = $this->get_expected_subtopics($title);
            
            // Check which subtopics are missing
            foreach ($expected_subtopics as $subtopic) {
                $found = false;
                
                foreach ($existing_headings as $heading) {
                    $heading_clean = strip_tags($heading);
                    
                    if (stripos($heading_clean, $subtopic) !== false) {
                        $found = true;
                        break;
                    }
                }
                
                if (!$found) {
                    $missing[] = array(
                        'subtopic' => $subtopic,
                        'importance' => 'high',
                        'reason' => 'Beklenen alt konu eksik',
                    );
                }
            }
            
        } catch (Throwable $e) {
            error_log('[DODO][Semantic Audit] Error detecting subtopics: ' . $e->getMessage());
        }
        
        return $missing;
    }
    
    /**
     * Get expected subtopics for title
     * 
     * @param string $title
     * @return array Expected subtopics
     */
    private function get_expected_subtopics($title) {
        $subtopics = array();
        
        $title_lower = mb_strtolower($title, 'UTF-8');
        
        // "Nasıl" questions should have steps
        if (strpos($title_lower, 'nasıl') !== false) {
            $subtopics[] = 'adım';
            $subtopics[] = 'yöntem';
        }
        
        // "En iyi" lists should have comparison
        if (strpos($title_lower, 'en iyi') !== false) {
            $subtopics[] = 'karşılaştırma';
            $subtopics[] = 'özellik';
            $subtopics[] = 'fiyat';
        }
        
        // Generic subtopics
        $subtopics[] = 'nedir';
        $subtopics[] = 'avantaj';
        $subtopics[] = 'kullanım';
        
        return $subtopics;
    }
    
    /**
     * Detect FAQ gaps
     * 
     * @param WP_Post $post
     * @return array FAQ gaps
     */
    public function detect_faq_gaps($post) {
        $gaps = array();
        
        try {
            $content = $post->post_content;
            $title = $post->post_title;
            
            // Count existing FAQs
            $faq_count = substr_count($content, 'wp:rank-math/faq-block');
            
            if ($faq_count === 0) {
                $gaps[] = array(
                    'gap' => 'FAQ bloğu yok',
                    'severity' => 'critical',
                    'recommendation' => 'En az 3-5 FAQ sorusu ekleyin',
                );
            } elseif ($faq_count < 3) {
                $gaps[] = array(
                    'gap' => 'Yetersiz FAQ sayısı',
                    'severity' => 'high',
                    'recommendation' => 'FAQ sayısını 5\'e çıkarın',
                );
            }
            
            // Check for common question patterns
            $common_questions = $this->get_common_questions($title);
            
            foreach ($common_questions as $question) {
                if (stripos($content, $question) === false) {
                    $gaps[] = array(
                        'gap' => 'Eksik soru: ' . $question,
                        'severity' => 'medium',
                        'recommendation' => 'Bu soruyu FAQ\'ye ekleyin',
                    );
                }
            }
            
        } catch (Throwable $e) {
            error_log('[DODO][Semantic Audit] Error detecting FAQ gaps: ' . $e->getMessage());
        }
        
        return $gaps;
    }
    
    /**
     * Get common questions for topic
     * 
     * @param string $title
     * @return array Common questions
     */
    private function get_common_questions($title) {
        $questions = array();
        
        $topic = $this->extract_main_topic($title);
        
        // Generic questions
        $questions[] = $topic . ' nedir?';
        $questions[] = $topic . ' nasıl kullanılır?';
        $questions[] = $topic . ' avantajları nelerdir?';
        
        return $questions;
    }
    
    /**
     * Detect incomplete answers
     * 
     * @param WP_Post $post
     * @return array Incomplete answers
     */
    public function detect_incomplete_answers($post) {
        $incomplete = array();
        
        try {
            $content = $post->post_content;
            
            // Extract sections (H2 + content until next H2)
            preg_match_all('/<h2[^>]*>(.*?)<\/h2>(.*?)(?=<h2|$)/is', $content, $matches, PREG_SET_ORDER);
            
            foreach ($matches as $match) {
                $heading = strip_tags($match[1]);
                $section_content = strip_tags($match[2]);
                $word_count = str_word_count($section_content);
                
                // Section too short
                if ($word_count < 100) {
                    $incomplete[] = array(
                        'section' => $heading,
                        'issue' => 'Bölüm çok kısa (' . $word_count . ' kelime)',
                        'severity' => 'high',
                        'recommendation' => 'En az 150 kelime olmalı',
                    );
                }
                
                // No examples
                if (!preg_match('/(örneğin|mesela|örnek)/i', $section_content)) {
                    $incomplete[] = array(
                        'section' => $heading,
                        'issue' => 'Örnek yok',
                        'severity' => 'medium',
                        'recommendation' => 'Pratik örnek ekleyin',
                    );
                }
            }
            
        } catch (Throwable $e) {
            error_log('[DODO][Semantic Audit] Error detecting incomplete answers: ' . $e->getMessage());
        }
        
        return $incomplete;
    }
    
    /**
     * Calculate semantic depth (0-100)
     * 
     * @param WP_Post $post
     * @return int Depth score
     */
    public function calculate_semantic_depth($post) {
        $depth = 0;
        
        try {
            $content = $post->post_content;
            
            // Word count (30 points)
            $word_count = str_word_count(strip_tags($content));
            
            if ($word_count >= 2000) {
                $depth += 30;
            } elseif ($word_count >= 1500) {
                $depth += 25;
            } elseif ($word_count >= 1000) {
                $depth += 20;
            } elseif ($word_count >= 500) {
                $depth += 10;
            }
            
            // Heading structure (20 points)
            $h2_count = substr_count($content, '<h2');
            $h3_count = substr_count($content, '<h3');
            
            if ($h2_count >= 5 && $h3_count >= 10) {
                $depth += 20;
            } elseif ($h2_count >= 3 && $h3_count >= 5) {
                $depth += 15;
            } elseif ($h2_count >= 2) {
                $depth += 10;
            }
            
            // FAQ presence (15 points)
            $faq_count = substr_count($content, 'wp:rank-math/faq-block');
            
            if ($faq_count >= 5) {
                $depth += 15;
            } elseif ($faq_count >= 3) {
                $depth += 10;
            } elseif ($faq_count >= 1) {
                $depth += 5;
            }
            
            // Lists (10 points)
            $list_count = substr_count($content, '<ul') + substr_count($content, '<ol');
            
            if ($list_count >= 5) {
                $depth += 10;
            } elseif ($list_count >= 3) {
                $depth += 7;
            } elseif ($list_count >= 1) {
                $depth += 5;
            }
            
            // Tables (10 points)
            $table_count = substr_count($content, '<table');
            
            if ($table_count >= 2) {
                $depth += 10;
            } elseif ($table_count >= 1) {
                $depth += 7;
            }
            
            // Internal links (10 points)
            $internal_link_count = substr_count($content, get_site_url());
            
            if ($internal_link_count >= 5) {
                $depth += 10;
            } elseif ($internal_link_count >= 3) {
                $depth += 7;
            } elseif ($internal_link_count >= 1) {
                $depth += 5;
            }
            
            // Images (5 points)
            $image_count = substr_count($content, '<img');
            
            if ($image_count >= 5) {
                $depth += 5;
            } elseif ($image_count >= 3) {
                $depth += 3;
            }
            
        } catch (Throwable $e) {
            error_log('[DODO][Semantic Audit] Error calculating depth: ' . $e->getMessage());
        }
        
        return min(100, $depth);
    }
    
    /**
     * Calculate coverage score (0-100)
     * 
     * @param array $audit
     * @return int Coverage score
     */
    private function calculate_coverage_score($audit) {
        $score = 100;
        
        // Deduct for missing entities
        $score -= count($audit['missing_entities']) * 5;
        
        // Deduct for missing subtopics
        $score -= count($audit['missing_subtopics']) * 7;
        
        // Deduct for FAQ gaps
        $score -= count($audit['faq_gaps']) * 10;
        
        // Deduct for incomplete answers
        $score -= count($audit['incomplete_answers']) * 3;
        
        // Bonus for high semantic depth
        if ($audit['semantic_depth'] >= 80) {
            $score += 10;
        }
        
        return max(0, min(100, $score));
    }
    
    /**
     * Generate recommendations
     * 
     * @param array $audit
     * @return array Recommendations
     */
    private function generate_recommendations($audit) {
        $recommendations = array();
        
        // Missing entities
        if (!empty($audit['missing_entities'])) {
            $top_entities = array_slice($audit['missing_entities'], 0, 3);
            
            foreach ($top_entities as $entity) {
                $recommendations[] = array(
                    'type' => 'entity',
                    'priority' => $entity['importance'],
                    'action' => 'Ekle: ' . $entity['entity'],
                    'reason' => $entity['reason'],
                );
            }
        }
        
        // Missing subtopics
        if (!empty($audit['missing_subtopics'])) {
            $top_subtopics = array_slice($audit['missing_subtopics'], 0, 3);
            
            foreach ($top_subtopics as $subtopic) {
                $recommendations[] = array(
                    'type' => 'subtopic',
                    'priority' => $subtopic['importance'],
                    'action' => 'Bölüm ekle: ' . $subtopic['subtopic'],
                    'reason' => $subtopic['reason'],
                );
            }
        }
        
        // FAQ gaps
        if (!empty($audit['faq_gaps'])) {
            $critical_gaps = array_filter($audit['faq_gaps'], function($gap) {
                return $gap['severity'] === 'critical' || $gap['severity'] === 'high';
            });
            
            foreach ($critical_gaps as $gap) {
                $recommendations[] = array(
                    'type' => 'faq',
                    'priority' => 'high',
                    'action' => $gap['recommendation'],
                    'reason' => $gap['gap'],
                );
            }
        }
        
        // Semantic depth
        if ($audit['semantic_depth'] < 60) {
            $recommendations[] = array(
                'type' => 'depth',
                'priority' => 'high',
                'action' => 'İçerik derinliğini artır',
                'reason' => 'Semantic depth düşük (' . $audit['semantic_depth'] . '/100)',
            );
        }
        
        return $recommendations;
    }
    
    /**
     * Save audit results
     * 
     * @param int $post_id
     * @param array $audit
     */
    private function save_audit_results($post_id, $audit) {
        update_post_meta($post_id, '_dodo_semantic_depth', $audit['semantic_depth']);
        update_post_meta($post_id, '_dodo_coverage_score', $audit['coverage_score']);
        update_post_meta($post_id, '_dodo_semantic_audit', $audit);
        update_post_meta($post_id, '_dodo_last_audited', current_time('mysql'));
    }
    
    /**
     * Get audit statistics
     * 
     * @return array Statistics
     */
    public function get_statistics() {
        $stats = array(
            'total_audited' => 0,
            'excellent_coverage' => 0, // >= 80
            'good_coverage' => 0, // 60-79
            'fair_coverage' => 0, // 40-59
            'poor_coverage' => 0, // < 40
            'avg_semantic_depth' => 0,
            'avg_coverage_score' => 0,
        );
        
        try {
            global $wpdb;
            
            // Get all audited posts
            $results = $wpdb->get_results(
                "SELECT post_id, meta_value as score 
                FROM {$wpdb->postmeta} 
                WHERE meta_key = '_dodo_coverage_score'"
            );
            
            $scores = array();
            $depths = array();
            
            foreach ($results as $row) {
                $score = intval($row->meta_value);
                $scores[] = $score;
                
                $stats['total_audited']++;
                
                if ($score >= 80) {
                    $stats['excellent_coverage']++;
                } elseif ($score >= 60) {
                    $stats['good_coverage']++;
                } elseif ($score >= 40) {
                    $stats['fair_coverage']++;
                } else {
                    $stats['poor_coverage']++;
                }
                
                $depth = get_post_meta($row->post_id, '_dodo_semantic_depth', true);
                if ($depth) {
                    $depths[] = intval($depth);
                }
            }
            
            if (!empty($scores)) {
                $stats['avg_coverage_score'] = round(array_sum($scores) / count($scores));
            }
            
            if (!empty($depths)) {
                $stats['avg_semantic_depth'] = round(array_sum($depths) / count($depths));
            }
            
        } catch (Throwable $e) {
            error_log('[DODO][Semantic Audit] Error getting statistics: ' . $e->getMessage());
        }
        
        return $stats;
    }
}
