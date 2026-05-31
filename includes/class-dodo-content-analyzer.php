<?php
/**
 * Content Analyzer Sınıfı
 * 
 * İçerik benzerlik analizi yapar
 *
 * @package DODO_AI_SEO
 * @since 1.0.0
 */

// Doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

class DODO_Content_Analyzer {
    
    /**
     * İki metin arasındaki benzerlik skorunu hesapla
     * 
     * @param string $text1
     * @param string $text2
     * @return float 0-100 arası skor
     */
    public function calculate_similarity($text1, $text2) {
        $text1 = $this->normalize_text($text1);
        $text2 = $this->normalize_text($text2);
        
        // Boş kontrol
        if (empty($text1) || empty($text2)) {
            return 0;
        }
        
        // similar_text kullan
        similar_text($text1, $text2, $percent);
        
        return round($percent, 2);
    }
    
    /**
     * Başlık benzerliği hesapla
     * 
     * @param string $title1
     * @param string $title2
     * @return float 0-100 arası skor
     */
    public function calculate_title_similarity($title1, $title2) {
        $title1 = $this->normalize_text($title1);
        $title2 = $this->normalize_text($title2);
        
        // Kelime bazlı karşılaştırma
        $words1 = explode(' ', $title1);
        $words2 = explode(' ', $title2);
        
        // Ortak kelime sayısı
        $common_words = array_intersect($words1, $words2);
        $common_count = count($common_words);
        
        // Toplam benzersiz kelime sayısı
        $total_words = count(array_unique(array_merge($words1, $words2)));
        
        if ($total_words === 0) {
            return 0;
        }
        
        // Jaccard benzerliği
        $jaccard_score = ($common_count / $total_words) * 100;
        
        // similar_text skoru
        similar_text($title1, $title2, $similar_percent);
        
        // İki skoru birleştir (ağırlıklı ortalama)
        $final_score = ($jaccard_score * 0.6) + ($similar_percent * 0.4);
        
        return round($final_score, 2);
    }
    
    /**
     * Anahtar kelime eşleşmesi hesapla
     * 
     * @param string $keyword Odak anahtar kelime
     * @param string $text Karşılaştırılacak metin
     * @return float 0-100 arası skor
     */
    public function calculate_keyword_match($keyword, $text) {
        $keyword = $this->normalize_text($keyword);
        $text = $this->normalize_text($text);
        
        if (empty($keyword) || empty($text)) {
            return 0;
        }
        
        // Tam eşleşme
        if (strpos($text, $keyword) !== false) {
            return 100;
        }
        
        // Kelime bazlı eşleşme
        $keyword_words = explode(' ', $keyword);
        $text_words = explode(' ', $text);
        
        $match_count = 0;
        foreach ($keyword_words as $word) {
            if (in_array($word, $text_words)) {
                $match_count++;
            }
        }
        
        $keyword_word_count = count($keyword_words);
        if ($keyword_word_count === 0) {
            return 0;
        }
        
        $score = ($match_count / $keyword_word_count) * 100;
        
        return round($score, 2);
    }
    
    /**
     * Kategori eşleşmesi kontrol et
     * 
     * @param array $categories1
     * @param array $categories2
     * @return float 0-100 arası skor
     */
    public function calculate_category_match($categories1, $categories2) {
        if (empty($categories1) || empty($categories2)) {
            return 0;
        }
        
        // Kategori ID'lerini al
        $cat_ids1 = is_array($categories1) ? $categories1 : array($categories1);
        $cat_ids2 = is_array($categories2) ? $categories2 : array($categories2);
        
        // Ortak kategoriler
        $common = array_intersect($cat_ids1, $cat_ids2);
        
        if (empty($common)) {
            return 0;
        }
        
        // Tam eşleşme varsa 100
        if (count($common) === count($cat_ids1) && count($common) === count($cat_ids2)) {
            return 100;
        }
        
        // Kısmi eşleşme
        $total_unique = count(array_unique(array_merge($cat_ids1, $cat_ids2)));
        $score = (count($common) / $total_unique) * 100;
        
        return round($score, 2);
    }
    
    /**
     * Genel içerik skoru hesapla
     * 
     * @param array $params Karşılaştırma parametreleri
     * @return float 0-100 arası toplam skor
     */
    public function calculate_content_score($params) {
        $scores = array();
        
        // Başlık benzerliği (ağırlık: 0.4)
        if (isset($params['title1']) && isset($params['title2'])) {
            $scores['title'] = $this->calculate_title_similarity($params['title1'], $params['title2']) * 0.4;
        }
        
        // Kategori eşleşmesi (ağırlık: 0.3)
        if (isset($params['categories1']) && isset($params['categories2'])) {
            $scores['category'] = $this->calculate_category_match($params['categories1'], $params['categories2']) * 0.3;
        }
        
        // Anahtar kelime eşleşmesi (ağırlık: 0.3)
        if (isset($params['keyword']) && isset($params['text'])) {
            $scores['keyword'] = $this->calculate_keyword_match($params['keyword'], $params['text']) * 0.3;
        }
        
        // Toplam skor
        $total_score = array_sum($scores);
        
        return round($total_score, 2);
    }
    
    /**
     * Metni normalize et (Türkçe karakter desteği)
     * 
     * @param string $text
     * @return string
     */
    private function normalize_text($text) {
        // Küçük harfe çevir
        $text = mb_strtolower($text, 'UTF-8');
        
        // HTML etiketlerini temizle
        $text = wp_strip_all_tags($text);
        
        // Fazla boşlukları temizle
        $text = preg_replace('/\s+/', ' ', $text);
        
        // Özel karakterleri temizle (Türkçe karakterler hariç)
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', '', $text);
        
        return trim($text);
    }
    
    /**
     * Metinden anahtar kelimeleri çıkar
     * 
     * @param string $text
     * @param int $limit
     * @return array
     */
    public function extract_keywords($text, $limit = 10) {
        $text = $this->normalize_text($text);
        
        // Stop words (Türkçe)
        $stop_words = array(
            've', 'veya', 'ile', 'için', 'bir', 'bu', 'şu', 'o', 'da', 'de',
            'mi', 'mu', 'mı', 'mü', 'gibi', 'kadar', 'daha', 'en', 'çok',
            'var', 'yok', 'olan', 'olarak', 'ise', 'eğer', 'ancak', 'fakat',
            'ama', 'lakin', 'ne', 'nasıl', 'neden', 'niçin', 'nerede', 'kim'
        );
        
        // Kelimelere ayır
        $words = explode(' ', $text);
        
        // Stop words'leri filtrele ve kısa kelimeleri çıkar
        $words = array_filter($words, function($word) use ($stop_words) {
            return strlen($word) > 3 && !in_array($word, $stop_words);
        });
        
        // Kelime frekanslarını hesapla
        $word_freq = array_count_values($words);
        
        // Frekansa göre sırala
        arsort($word_freq);
        
        // Limit kadar al
        return array_slice(array_keys($word_freq), 0, $limit);
    }
    
    /**
     * İki içerik arasındaki semantik benzerliği hesapla
     * 
     * @param string $content1
     * @param string $content2
     * @return float 0-100 arası skor
     */
    public function calculate_semantic_similarity($content1, $content2) {
        // Her iki içerikten anahtar kelimeler çıkar
        $keywords1 = $this->extract_keywords($content1, 20);
        $keywords2 = $this->extract_keywords($content2, 20);
        
        // Ortak anahtar kelimeler
        $common_keywords = array_intersect($keywords1, $keywords2);
        
        // Toplam benzersiz anahtar kelime
        $total_keywords = count(array_unique(array_merge($keywords1, $keywords2)));
        
        if ($total_keywords === 0) {
            return 0;
        }
        
        // Jaccard benzerliği
        $score = (count($common_keywords) / $total_keywords) * 100;
        
        return round($score, 2);
    }
}
