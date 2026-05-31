<?php
/**
 * Internal Links Sınıfı
 * 
 * Akıllı iç link seçimi yapar
 *
 * @package DODO_AI_SEO
 * @since 1.0.0
 */

// Doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

class DODO_Internal_Links {
    
    /**
     * Content Analyzer instance
     */
    private $analyzer;
    
    /**
     * Settings instance
     */
    private $settings;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->analyzer = new DODO_Content_Analyzer();
        $this->settings = new DODO_Settings();
    }
    
    /**
     * Alakalı iç linkleri bul
     * 
     * @param array $params Arama parametreleri
     * @return array İç link önerileri
     */
    public function find_relevant_links($params) {
        $focus_keyword = $params['focus_keyword'] ?? '';
        $topic = $params['topic'] ?? '';
        $category_id = $params['category_id'] ?? 0;
        
        $links = array(
            'products' => array(),
            'posts' => array(),
            'categories' => array(),
        );
        
        // WooCommerce ürünlerini al
        if (class_exists('WooCommerce')) {
            $links['products'] = $this->get_relevant_products($focus_keyword, $topic, $category_id);
        }
        
        // Blog yazılarını al
        $links['posts'] = $this->get_relevant_posts($focus_keyword, $topic, $category_id);
        
        // Kategorileri al
        $links['categories'] = $this->get_relevant_categories($focus_keyword, $category_id);
        
        return $links;
    }
    
    /**
     * Alakalı ürünleri bul
     * 
     * @param string $keyword
     * @param string $topic
     * @param int $category_id
     * @return array
     */
    private function get_relevant_products($keyword, $topic, $category_id) {
        // WooCommerce kontrolü
        if (!class_exists('WooCommerce')) {
            return array();
        }
        
        $args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => 20, // Daha fazla al, sonra skorlayıp filtrele
            'orderby' => 'date',
            'order' => 'DESC',
        );
        
        $products = get_posts($args);
        $scored_products = array();
        
        foreach ($products as $product) {
            // Ürün kategorilerini al
            $product_categories = wp_get_post_terms($product->ID, 'product_cat', array('fields' => 'ids'));
            
            // Skor hesapla
            $score = $this->analyzer->calculate_content_score(array(
                'title1' => $keyword,
                'title2' => $product->post_title,
                'keyword' => $keyword,
                'text' => $product->post_title . ' ' . $product->post_excerpt,
                'categories1' => $category_id ? array($category_id) : array(),
                'categories2' => $product_categories,
            ));
            
            // Minimum skor kontrolü (30 ve üzeri)
            if ($score >= 30) {
                $scored_products[] = array(
                    'id' => $product->ID,
                    'title' => $product->post_title,
                    'url' => get_permalink($product->ID),
                    'excerpt' => wp_trim_words($product->post_excerpt, 20),
                    'score' => $score,
                    'type' => 'product',
                );
            }
        }
        
        // Skora göre sırala
        usort($scored_products, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        // En iyi 5 ürünü al
        return array_slice($scored_products, 0, 5);
    }
    
    /**
     * Alakalı blog yazılarını bul
     * 
     * @param string $keyword
     * @param string $topic
     * @param int $category_id
     * @return array
     */
    private function get_relevant_posts($keyword, $topic, $category_id) {
        $args = array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => 20,
            'orderby' => 'date',
            'order' => 'DESC',
        );
        
        // Kategori filtresi
        if ($category_id > 0) {
            $args['cat'] = $category_id;
        }
        
        $posts = get_posts($args);
        $scored_posts = array();
        
        foreach ($posts as $post) {
            // Post kategorilerini al
            $post_categories = wp_get_post_categories($post->ID);
            
            // Skor hesapla
            $score = $this->analyzer->calculate_content_score(array(
                'title1' => $keyword,
                'title2' => $post->post_title,
                'keyword' => $keyword,
                'text' => $post->post_title . ' ' . $post->post_excerpt,
                'categories1' => $category_id ? array($category_id) : array(),
                'categories2' => $post_categories,
            ));
            
            // Minimum skor kontrolü (25 ve üzeri)
            if ($score >= 25) {
                $scored_posts[] = array(
                    'id' => $post->ID,
                    'title' => $post->post_title,
                    'url' => get_permalink($post->ID),
                    'excerpt' => wp_trim_words($post->post_excerpt, 20),
                    'score' => $score,
                    'type' => 'post',
                );
            }
        }
        
        // Skora göre sırala
        usort($scored_posts, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        // En iyi 5 yazıyı al
        return array_slice($scored_posts, 0, 5);
    }
    
    /**
     * Alakalı kategorileri bul
     * 
     * @param string $keyword
     * @param int $current_category_id
     * @return array
     */
    private function get_relevant_categories($keyword, $current_category_id) {
        $categories = get_categories(array(
            'hide_empty' => true,
            'number' => 10,
        ));
        
        $scored_categories = array();
        
        foreach ($categories as $category) {
            // Mevcut kategoriyi atla
            if ($category->term_id === $current_category_id) {
                continue;
            }
            
            // Skor hesapla
            $score = $this->analyzer->calculate_keyword_match(
                $keyword,
                $category->name . ' ' . $category->description
            );
            
            // Minimum skor kontrolü (20 ve üzeri)
            if ($score >= 20) {
                $scored_categories[] = array(
                    'id' => $category->term_id,
                    'title' => $category->name,
                    'url' => get_category_link($category->term_id),
                    'description' => $category->description,
                    'score' => $score,
                    'type' => 'category',
                );
            }
        }
        
        // Skora göre sırala
        usort($scored_categories, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        // En iyi 3 kategoriyi al
        return array_slice($scored_categories, 0, 3);
    }
    
    /**
     * İç linkleri formatlı string olarak döndür (AI için)
     * 
     * @param array $links
     * @return string
     */
    public function format_links_for_ai($links) {
        $formatted = "## Kullanılabilir İç Linkler\n\n";
        $formatted .= "Aşağıdaki linkleri yazı içinde DOĞAL bir şekilde kullan. Her linki SADECE BİR KEZ kullan.\n\n";
        
        // Ürünler
        if (!empty($links['products'])) {
            $formatted .= "### Ürünler (Satın alma niyeti olan paragraflarda kullan):\n";
            $formatted .= "Bu linkleri ürün önerisi, karşılaştırma veya satın alma yönlendirmesi yapılan bölümlerde kullan.\n\n";
            foreach ($links['products'] as $product) {
                $formatted .= sprintf(
                    "- [%s](%s) - Alakalılık: %.0f%%\n",
                    $product['title'],
                    $product['url'],
                    $product['score']
                );
            }
            $formatted .= "\n";
        }
        
        // Blog yazıları
        if (!empty($links['posts'])) {
            $formatted .= "### İlgili Blog Yazıları (Bilgi verici paragraflarda kullan):\n";
            $formatted .= "Bu linkleri detaylı bilgi, rehber veya ek kaynak sunduğun bölümlerde kullan.\n\n";
            foreach ($links['posts'] as $post) {
                $formatted .= sprintf(
                    "- [%s](%s) - Alakalılık: %.0f%%\n",
                    $post['title'],
                    $post['url'],
                    $post['score']
                );
            }
            $formatted .= "\n";
        }
        
        // Kategoriler
        if (!empty($links['categories'])) {
            $formatted .= "### İlgili Kategoriler (Genel konu geçişlerinde kullan):\n";
            $formatted .= "Bu linkleri geniş konu başlıklarına geçiş yaparken veya kategori tanıtımında kullan.\n\n";
            foreach ($links['categories'] as $category) {
                $formatted .= sprintf(
                    "- [%s](%s) - Alakalılık: %.0f%%\n",
                    $category['title'],
                    $category['url'],
                    $category['score']
                );
            }
            $formatted .= "\n";
        }
        
        $formatted .= "**KURALLAR:**\n";
        $formatted .= "- Her URL'yi yazıda SADECE BİR KEZ kullan\n";
        $formatted .= "- Anchor text'ler doğal ve konuyla alakalı olmalı\n";
        $formatted .= "- Alakasız yerlere link ekleme\n";
        $formatted .= "- Link spam yapma\n";
        
        return $formatted;
    }
    
    /**
     * Toplam link sayısını al
     * 
     * @param array $links
     * @return int
     */
    public function get_total_link_count($links) {
        $count = 0;
        
        if (isset($links['products'])) {
            $count += count($links['products']);
        }
        
        if (isset($links['posts'])) {
            $count += count($links['posts']);
        }
        
        if (isset($links['categories'])) {
            $count += count($links['categories']);
        }
        
        return $count;
    }
    
    /**
     * Link çeşitliliğini kontrol et
     * 
     * @param array $links
     * @return bool
     */
    public function has_link_diversity($links) {
        $types = 0;
        
        if (!empty($links['products'])) {
            $types++;
        }
        
        if (!empty($links['posts'])) {
            $types++;
        }
        
        if (!empty($links['categories'])) {
            $types++;
        }
        
        // En az 2 farklı tip link olmalı
        return $types >= 2;
    }
}
