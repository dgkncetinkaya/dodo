<?php
/**
 * Text Utilities - Centralized text processing
 * 
 * PHASE 7 - Architecture Cleanup
 * Consolidates duplicate text processing logic
 * 
 * @package DODO_AI_SEO
 * @since 4.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (class_exists('DODO_Text_Utils')) {
    return;
}

class DODO_Text_Utils {
    
    /**
     * Turkish stop words
     */
    private static $turkish_stop_words = array(
        'bir', 'bu', 've', 'için', 'ile', 'mi', 'mı', 'mu', 'mü',
        'da', 'de', 'ta', 'te', 'ki', 'ne', 'nasıl', 'neden',
        'gibi', 'kadar', 'daha', 'en', 'çok', 'az', 'var', 'yok',
        'olan', 'olarak', 'ise', 'ancak', 'fakat', 'veya', 'ya',
        'nedir', 'ne demek', 'nerede', 'ne zaman', 'kim', 'hangi',
        '2024', '2025', '2026',
    );
    
    /**
     * English stop words
     */
    private static $english_stop_words = array(
        'the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at',
        'to', 'for', 'of', 'with', 'by', 'from', 'as', 'is', 'was',
        'are', 'were', 'been', 'be', 'have', 'has', 'had', 'do',
        'does', 'did', 'will', 'would', 'should', 'could', 'may',
        'might', 'must', 'can', 'this', 'that', 'these', 'those',
    );
    
    /**
     * Extract paragraphs from HTML content
     * 
     * @param string $content HTML content
     * @return array Paragraphs
     */
    public static function extract_paragraphs($content) {
        preg_match_all('/<p[^>]*>(.*?)<\/p>/is', $content, $matches);
        return array_filter($matches[1], function($p) {
            return strlen(strip_tags($p)) > 20;
        });
    }
    
    /**
     * Extract sentences from text
     * 
     * @param string $text Plain text
     * @return array Sentences
     */
    public static function extract_sentences($text) {
        $text = strip_tags($text);
        $sentences = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        return array_map('trim', array_filter($sentences));
    }
    
    /**
     * Extract keywords from text
     * 
     * @param string $text Text to analyze
     * @param int $limit Max keywords to return
     * @return array Keywords with frequency
     */
    public static function extract_keywords($text, $limit = 10) {
        $text = mb_strtolower(strip_tags($text), 'UTF-8');
        
        // Extract words
        preg_match_all('/\b[\p{L}]{3,}\b/u', $text, $matches);
        $words = $matches[0];
        
        // Filter stop words
        $stop_words = array_merge(self::$turkish_stop_words, self::$english_stop_words);
        $keywords = array();
        
        foreach ($words as $word) {
            if (!in_array($word, $stop_words)) {
                if (!isset($keywords[$word])) {
                    $keywords[$word] = 0;
                }
                $keywords[$word]++;
            }
        }
        
        // Sort by frequency
        arsort($keywords);
        
        return array_slice($keywords, 0, $limit, true);
    }
    
    /**
     * Extract main topic from keyword
     * 
     * @param string $keyword Keyword phrase
     * @return string Main topic
     */
    public static function extract_main_topic($keyword) {
        $keyword = mb_strtolower($keyword, 'UTF-8');
        
        // Remove common modifiers
        $modifiers = array(
            'nasıl', 'nedir', 'ne demek', 'neden', 'ne zaman', 'nerede',
            'en iyi', 'fiyat', 'fiyatları', 'satın al', 'tavsiye',
            'karşılaştırma', 'vs', 'hakkında', 'için', 'ile',
            'how to', 'what is', 'why', 'when', 'where',
            'best', 'price', 'buy', 'compare', 'vs', 'about',
            '2024', '2025', '2026',
        );
        
        $topic = $keyword;
        foreach ($modifiers as $modifier) {
            $topic = preg_replace('/\b' . preg_quote($modifier, '/') . '\b/i', '', $topic);
        }
        
        // Clean up whitespace
        $topic = preg_replace('/\s+/', ' ', trim($topic));
        
        // Get first 2-3 words as main topic
        $words = explode(' ', $topic);
        return implode(' ', array_slice($words, 0, min(3, count($words))));
    }
    
    /**
     * Extract entities from text (simple pattern matching)
     * 
     * @param string $text Text to analyze
     * @return array Entities
     */
    public static function extract_entities($text) {
        $entities = array();
        
        // Proper nouns (capitalized words)
        preg_match_all('/\b[A-ZÇĞIÖŞÜ][a-zçğıöşü]+\b/u', $text, $matches);
        if (!empty($matches[0])) {
            $entities['proper_nouns'] = array_unique($matches[0]);
        }
        
        // Numbers and measurements
        preg_match_all('/\d+[\.,]?\d*\s*(?:kg|gr|lt|ml|cm|m|km|TL|₺|\$|€)?/u', $text, $matches);
        if (!empty($matches[0])) {
            $entities['measurements'] = array_unique($matches[0]);
        }
        
        // Dates
        preg_match_all('/\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{2,4}/', $text, $matches);
        if (!empty($matches[0])) {
            $entities['dates'] = array_unique($matches[0]);
        }
        
        return $entities;
    }
    
    /**
     * Calculate text similarity (simple Jaccard)
     * 
     * @param string $text1 First text
     * @param string $text2 Second text
     * @return float Similarity score 0-1
     */
    public static function calculate_similarity($text1, $text2) {
        $words1 = self::extract_keywords($text1, 50);
        $words2 = self::extract_keywords($text2, 50);
        
        $keys1 = array_keys($words1);
        $keys2 = array_keys($words2);
        
        $intersection = count(array_intersect($keys1, $keys2));
        $union = count(array_unique(array_merge($keys1, $keys2)));
        
        return $union > 0 ? $intersection / $union : 0;
    }
    
    /**
     * Sanitize keyword
     * 
     * @param string $keyword Keyword to sanitize
     * @return string Sanitized keyword
     */
    public static function sanitize_keyword($keyword) {
        $keyword = sanitize_text_field($keyword);
        $keyword = mb_strtolower($keyword, 'UTF-8');
        $keyword = preg_replace('/\s+/', ' ', trim($keyword));
        return $keyword;
    }
    
    /**
     * Get word count
     * 
     * @param string $text Text to count
     * @return int Word count
     */
    public static function word_count($text) {
        $text = strip_tags($text);
        return str_word_count($text);
    }
    
    /**
     * Truncate text to word limit
     * 
     * @param string $text Text to truncate
     * @param int $limit Word limit
     * @param string $suffix Suffix to add
     * @return string Truncated text
     */
    public static function truncate($text, $limit = 50, $suffix = '...') {
        return wp_trim_words($text, $limit, $suffix);
    }
}
