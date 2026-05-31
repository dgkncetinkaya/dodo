<?php
/**
 * Editorial Style Detector Class
 * 
 * Detects editorial tone and style of content
 * Identifies tone drift after AI improvements
 *
 * @package DODO_AI_SEO
 * @since 1.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Style_Detector {
    
    /**
     * Tone types
     */
    const TONE_CORPORATE = 'corporate';
    const TONE_TECHNICAL = 'technical';
    const TONE_EDUCATIONAL = 'educational';
    const TONE_SALES = 'sales';
    const TONE_PROFESSIONAL = 'professional';
    const TONE_CONVERSATIONAL = 'conversational';
    
    /**
     * Detect editorial style
     * 
     * @param string $content Post content
     * @return array Style analysis data
     */
    public function detect_style($content) {
        $text = strip_tags($content);
        
        // Detect primary tone
        $tone_scores = $this->calculate_tone_scores($text);
        $primary_tone = $this->get_primary_tone($tone_scores);
        $tone_confidence = $this->calculate_tone_confidence($tone_scores);
        
        // Detect tone characteristics
        $formality_score = $this->calculate_formality($text);
        $technical_density = $this->calculate_technical_density($text);
        $persuasion_level = $this->calculate_persuasion_level($text);
        $engagement_style = $this->calculate_engagement_style($text);
        
        return [
            'primary_tone' => $primary_tone,
            'tone_confidence' => $tone_confidence,
            'tone_scores' => $tone_scores,
            'formality_score' => $formality_score,
            'technical_density' => $technical_density,
            'persuasion_level' => $persuasion_level,
            'engagement_style' => $engagement_style,
            'tone_label' => $this->get_tone_label($primary_tone),
        ];
    }
    
    /**
     * Detect tone drift between two contents
     * 
     * @param string $original_content Original content
     * @param string $improved_content Improved content
     * @return array Tone drift analysis
     */
    public function detect_tone_drift($original_content, $improved_content) {
        $original_style = $this->detect_style($original_content);
        $improved_style = $this->detect_style($improved_content);
        
        $drift_detected = false;
        $drift_severity = 'none';
        $drift_details = [];
        
        // Check if primary tone changed
        if ($original_style['primary_tone'] !== $improved_style['primary_tone']) {
            $drift_detected = true;
            $drift_severity = 'high';
            $drift_details[] = sprintf(
                __('Tone changed from %s to %s', 'dodo-ai-seo'),
                $original_style['tone_label'],
                $improved_style['tone_label']
            );
        }
        
        // Check formality shift
        $formality_diff = abs($original_style['formality_score'] - $improved_style['formality_score']);
        if ($formality_diff > 25) {
            $drift_detected = true;
            $drift_severity = $drift_severity === 'high' ? 'high' : 'medium';
            $drift_details[] = __('Formality level changed significantly', 'dodo-ai-seo');
        }
        
        // Check technical density shift
        $technical_diff = abs($original_style['technical_density'] - $improved_style['technical_density']);
        if ($technical_diff > 20) {
            $drift_detected = true;
            $drift_severity = $drift_severity === 'none' ? 'medium' : $drift_severity;
            $drift_details[] = __('Technical language density changed', 'dodo-ai-seo');
        }
        
        // Check persuasion shift
        $persuasion_diff = abs($original_style['persuasion_level'] - $improved_style['persuasion_level']);
        if ($persuasion_diff > 20) {
            $drift_detected = true;
            $drift_severity = $drift_severity === 'none' ? 'low' : $drift_severity;
            $drift_details[] = __('Persuasion style changed', 'dodo-ai-seo');
        }
        
        return [
            'drift_detected' => $drift_detected,
            'drift_severity' => $drift_severity,
            'drift_details' => $drift_details,
            'original_tone' => $original_style['tone_label'],
            'improved_tone' => $improved_style['tone_label'],
            'formality_change' => $improved_style['formality_score'] - $original_style['formality_score'],
            'technical_change' => $improved_style['technical_density'] - $original_style['technical_density'],
        ];
    }
    
    /**
     * Calculate tone scores for all tone types
     * 
     * @param string $text Content text
     * @return array Tone scores
     */
    private function calculate_tone_scores($text) {
        return [
            self::TONE_CORPORATE => $this->calculate_corporate_score($text),
            self::TONE_TECHNICAL => $this->calculate_technical_score($text),
            self::TONE_EDUCATIONAL => $this->calculate_educational_score($text),
            self::TONE_SALES => $this->calculate_sales_score($text),
            self::TONE_PROFESSIONAL => $this->calculate_professional_score($text),
            self::TONE_CONVERSATIONAL => $this->calculate_conversational_score($text),
        ];
    }
    
    /**
     * Calculate corporate tone score
     */
    private function calculate_corporate_score($text) {
        $score = 0;
        $text_lower = mb_strtolower($text);
        
        $corporate_indicators = [
            'şirket', 'kurum', 'organizasyon', 'ekip', 'misyon', 'vizyon',
            'değer', 'strateji', 'hedef', 'performans', 'verimlilik',
            'iş birliği', 'ortaklık', 'müşteri', 'hizmet', 'kalite',
        ];
        
        foreach ($corporate_indicators as $indicator) {
            $score += substr_count($text_lower, $indicator) * 5;
        }
        
        // Formal language patterns
        if (preg_match_all('/\b(gerçekleştir|sağla|oluştur|geliştir|sunmak)\b/u', $text_lower)) {
            $score += 10;
        }
        
        return min(100, $score);
    }
    
    /**
     * Calculate technical tone score
     */
    private function calculate_technical_score($text) {
        $score = 0;
        $text_lower = mb_strtolower($text);
        
        $technical_indicators = [
            'sistem', 'algoritma', 'veri', 'analiz', 'optimize',
            'performans', 'entegrasyon', 'konfigürasyon', 'parametre',
            'fonksiyon', 'metod', 'protokol', 'interface', 'api',
        ];
        
        foreach ($technical_indicators as $indicator) {
            $score += substr_count($text_lower, $indicator) * 6;
        }
        
        // Technical jargon density
        $word_count = str_word_count($text);
        if ($word_count > 0) {
            $technical_word_count = 0;
            foreach ($technical_indicators as $indicator) {
                $technical_word_count += substr_count($text_lower, $indicator);
            }
            $density = ($technical_word_count / $word_count) * 100;
            $score += $density * 2;
        }
        
        return min(100, $score);
    }
    
    /**
     * Calculate educational tone score
     */
    private function calculate_educational_score($text) {
        $score = 0;
        $text_lower = mb_strtolower($text);
        
        $educational_indicators = [
            'öğren', 'anla', 'keşfet', 'incele', 'araştır',
            'örnek', 'açıklama', 'tanım', 'kavram', 'temel',
            'adım', 'rehber', 'kılavuz', 'ipucu', 'bilgi',
        ];
        
        foreach ($educational_indicators as $indicator) {
            $score += substr_count($text_lower, $indicator) * 5;
        }
        
        // Question patterns (educational content asks questions)
        $question_count = substr_count($text, '?');
        $score += min(30, $question_count * 5);
        
        // List patterns (educational content uses lists)
        $list_count = preg_match_all('/<li[^>]*>/i', $text);
        $score += min(20, $list_count * 2);
        
        return min(100, $score);
    }
    
    /**
     * Calculate sales tone score
     */
    private function calculate_sales_score($text) {
        $score = 0;
        $text_lower = mb_strtolower($text);
        
        $sales_indicators = [
            'hemen', 'şimdi', 'bugün', 'sınırlı', 'özel',
            'fırsat', 'indirim', 'kampanya', 'avantaj', 'kazanç',
            'ücretsiz', 'garanti', 'risk yok', 'dene', 'satın al',
        ];
        
        foreach ($sales_indicators as $indicator) {
            $score += substr_count($text_lower, $indicator) * 7;
        }
        
        // CTA patterns
        $cta_patterns = [
            'tıkla', 'başla', 'kayıt ol', 'üye ol', 'iletişime geç',
            'hemen al', 'şimdi dene', 'kaçırma',
        ];
        
        foreach ($cta_patterns as $pattern) {
            $score += substr_count($text_lower, $pattern) * 10;
        }
        
        // Urgency language
        if (preg_match('/\b(acele|hızlı|son|kaçırma|sınırlı)\b/u', $text_lower)) {
            $score += 15;
        }
        
        return min(100, $score);
    }
    
    /**
     * Calculate professional tone score
     */
    private function calculate_professional_score($text) {
        $score = 0;
        $text_lower = mb_strtolower($text);
        
        // Formal vocabulary
        $professional_words = [
            'profesyonel', 'uzman', 'deneyim', 'yetkinlik', 'uzmanlık',
            'çözüm', 'yaklaşım', 'metodoloji', 'süreç', 'standart',
        ];
        
        foreach ($professional_words as $word) {
            $score += substr_count($text_lower, $word) * 6;
        }
        
        // Sentence complexity (professional writing tends to be more complex)
        $sentences = preg_split('/[.!?]+/', $text);
        $avg_sentence_length = 0;
        foreach ($sentences as $sentence) {
            $avg_sentence_length += str_word_count($sentence);
        }
        if (count($sentences) > 0) {
            $avg_sentence_length /= count($sentences);
            if ($avg_sentence_length > 20 && $avg_sentence_length < 30) {
                $score += 20;
            }
        }
        
        return min(100, $score);
    }
    
    /**
     * Calculate conversational tone score
     */
    private function calculate_conversational_score($text) {
        $score = 0;
        $text_lower = mb_strtolower($text);
        
        // Conversational markers
        $conversational_indicators = [
            'sen', 'siz', 'biz', 'birlikte', 'hadi',
            'değil mi', 'öyle değil mi', 'biliyorsun',
        ];
        
        foreach ($conversational_indicators as $indicator) {
            $score += substr_count($text_lower, $indicator) * 5;
        }
        
        // Questions (conversational content engages with questions)
        $question_count = substr_count($text, '?');
        $score += min(25, $question_count * 4);
        
        // Short sentences (conversational writing is punchier)
        $sentences = preg_split('/[.!?]+/', $text);
        $short_sentence_count = 0;
        foreach ($sentences as $sentence) {
            if (str_word_count($sentence) < 15) {
                $short_sentence_count++;
            }
        }
        if (count($sentences) > 0) {
            $short_ratio = $short_sentence_count / count($sentences);
            $score += $short_ratio * 30;
        }
        
        // Exclamation marks (enthusiasm)
        $exclamation_count = substr_count($text, '!');
        $score += min(15, $exclamation_count * 3);
        
        return min(100, $score);
    }
    
    /**
     * Get primary tone (highest score)
     */
    private function get_primary_tone($tone_scores) {
        arsort($tone_scores);
        return key($tone_scores);
    }
    
    /**
     * Calculate tone confidence
     */
    private function calculate_tone_confidence($tone_scores) {
        $values = array_values($tone_scores);
        rsort($values);
        
        if (count($values) < 2) {
            return 50;
        }
        
        $highest = $values[0];
        $second_highest = $values[1];
        
        if ($highest == 0) {
            return 0;
        }
        
        // Confidence based on gap between top two scores
        $gap = $highest - $second_highest;
        $confidence = min(100, ($gap / $highest) * 100);
        
        return round($confidence);
    }
    
    /**
     * Calculate formality score
     */
    private function calculate_formality($text) {
        $score = 50; // Start neutral
        
        // Formal indicators increase score
        $formal_patterns = [
            '/\b(gerçekleştir|sağla|oluştur|geliştir|sunmak)\b/u',
            '/\b(dolayısıyla|bu nedenle|sonuç olarak)\b/u',
        ];
        
        foreach ($formal_patterns as $pattern) {
            $matches = preg_match_all($pattern, mb_strtolower($text));
            $score += min(15, $matches * 3);
        }
        
        // Informal indicators decrease score
        $informal_words = ['hadi', 'tamam', 'işte', 'yani', 'falan'];
        foreach ($informal_words as $word) {
            $count = substr_count(mb_strtolower($text), $word);
            $score -= min(15, $count * 3);
        }
        
        return max(0, min(100, $score));
    }
    
    /**
     * Calculate technical density
     */
    private function calculate_technical_density($text) {
        $technical_words = [
            'sistem', 'algoritma', 'veri', 'analiz', 'optimize',
            'performans', 'entegrasyon', 'api', 'protokol',
        ];
        
        $word_count = str_word_count($text);
        if ($word_count == 0) {
            return 0;
        }
        
        $technical_count = 0;
        foreach ($technical_words as $word) {
            $technical_count += substr_count(mb_strtolower($text), $word);
        }
        
        return min(100, ($technical_count / $word_count) * 100 * 10);
    }
    
    /**
     * Calculate persuasion level
     */
    private function calculate_persuasion_level($text) {
        $persuasive_words = [
            'hemen', 'şimdi', 'garanti', 'kanıtlanmış', 'güvenilir',
            'en iyi', 'lider', 'başarılı', 'etkili', 'güçlü',
        ];
        
        $score = 0;
        foreach ($persuasive_words as $word) {
            $score += substr_count(mb_strtolower($text), $word) * 5;
        }
        
        return min(100, $score);
    }
    
    /**
     * Calculate engagement style
     */
    private function calculate_engagement_style($text) {
        $score = 0;
        
        // Questions engage readers
        $score += min(30, substr_count($text, '?') * 5);
        
        // Direct address (sen/siz)
        $score += min(25, substr_count(mb_strtolower($text), 'sen') * 3);
        $score += min(25, substr_count(mb_strtolower($text), 'siz') * 3);
        
        // Exclamations show enthusiasm
        $score += min(20, substr_count($text, '!') * 4);
        
        return min(100, $score);
    }
    
    /**
     * Get tone label
     */
    private function get_tone_label($tone) {
        $labels = [
            self::TONE_CORPORATE => __('Kurumsal', 'dodo-ai-seo'),
            self::TONE_TECHNICAL => __('Teknik', 'dodo-ai-seo'),
            self::TONE_EDUCATIONAL => __('Eğitici', 'dodo-ai-seo'),
            self::TONE_SALES => __('Satış Odaklı', 'dodo-ai-seo'),
            self::TONE_PROFESSIONAL => __('Profesyonel', 'dodo-ai-seo'),
            self::TONE_CONVERSATIONAL => __('Konuşma Dili', 'dodo-ai-seo'),
        ];
        
        return $labels[$tone] ?? $tone;
    }
}
