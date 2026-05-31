<?php
/**
 * Money Page Detector
 * 
 * Detects high-value commercial pages (products, categories, conversion pages)
 * Sprint D - Revenue & Topical Domination Engine
 * 
 * @package DODO_AI_SEO
 * @since 3.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Money_Page_Detector {
    
    /**
     * Detect money pages
     * 
     * @return array Money pages
     */
    public function detect_money_pages() {
        $money_pages = array();
        
        try {
            // 1. WooCommerce products
            if (class_exists('WooCommerce')) {
                $products = $this->detect_product_pages();
                $money_pages = array_merge($money_pages, $products);
            }
            
            // 2. Category pages
            $categories = $this->detect_category_pages();
            $money_pages = array_merge($money_pages, $categories);
            
            // 3. High traffic commercial pages
            $commercial_pages = $this->detect_high_value_urls();
            $money_pages = array_merge($money_pages, $commercial_pages);
            
            // 4. Conversion pages
            $conversion_pages = $this->detect_conversion_pages();
            $money_pages = array_merge($money_pages, $conversion_pages);
            
        } catch (Throwable $e) {
            error_log('[DODO][Money Page] Detection error: ' . $e->getMessage());
        }
        
        return $money_pages;
    }
    
    /**
     * Detect product pages
     */
    private function detect_product_pages() {
        $products = array();
        
        if (!class_exists('WooCommerce')) {
            return $products;
        }
        
        $product_posts = get_posts(array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'numberposts' => 50,
        ));
        
        foreach ($product_posts as $post) {
            $product = wc_get_product($post->ID);
            
            if (!$product) {
                continue;
            }
            
            $products[] = array(
                'id' => $post->ID,
                'title' => $post->post_title,
                'url' => get_permalink($post->ID),
                'type' => 'product',
                'price' => $product->get_price(),
                'value_score' => $this->calculate_product_value($product),
            );
        }
        
        return $products;
    }
    
    /**
     * Calculate product value score
     */
    private function calculate_product_value($product) {
        $score = 50;
        
        // Price factor
        $price = floatval($product->get_price());
        if ($price > 1000) {
            $score += 30;
        } elseif ($price > 500) {
            $score += 20;
        } elseif ($price > 100) {
            $score += 10;
        }
        
        // Sales count
        $sales = $product->get_total_sales();
        if ($sales > 50) {
            $score += 20;
        } elseif ($sales > 10) {
            $score += 10;
        }
        
        return min(100, $score);
    }
    
    /**
     * Detect category pages
     */
    private function detect_category_pages() {
        $categories = array();
        
        $terms = get_terms(array(
            'taxonomy' => array('category', 'product_cat'),
            'hide_empty' => true,
            'number' => 30,
        ));
        
        foreach ($terms as $term) {
            $categories[] = array(
                'id' => $term->term_id,
                'title' => $term->name,
                'url' => get_term_link($term),
                'type' => 'category',
                'post_count' => $term->count,
                'value_score' => $this->calculate_category_value($term),
            );
        }
        
        return $categories;
    }
    
    /**
     * Calculate category value score
     */
    private function calculate_category_value($term) {
        $score = 50;
        
        // Post count
        if ($term->count > 20) {
            $score += 30;
        } elseif ($term->count > 10) {
            $score += 20;
        } elseif ($term->count > 5) {
            $score += 10;
        }
        
        // Commercial keywords in name
        $name_lower = mb_strtolower($term->name, 'UTF-8');
        if (preg_match('/(ürün|product|satış|sale|fiyat|price)/i', $name_lower)) {
            $score += 20;
        }
        
        return min(100, $score);
    }
    
    /**
     * Detect high value URLs
     */
    private function detect_high_value_urls() {
        $high_value = array();
        
        // Get posts with high engagement
        $posts = get_posts(array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'numberposts' => 20,
            'orderby' => 'comment_count',
            'order' => 'DESC',
        ));
        
        foreach ($posts as $post) {
            // Check if commercial content
            $content_lower = mb_strtolower($post->post_content, 'UTF-8');
            $is_commercial = preg_match('/(satın\s+al|fiyat|ürün|product|buy|price)/i', $content_lower);
            
            if ($is_commercial) {
                $high_value[] = array(
                    'id' => $post->ID,
                    'title' => $post->post_title,
                    'url' => get_permalink($post->ID),
                    'type' => 'commercial_post',
                    'comments' => $post->comment_count,
                    'value_score' => $this->calculate_post_value($post),
                );
            }
        }
        
        return $high_value;
    }
    
    /**
     * Calculate post value score
     */
    private function calculate_post_value($post) {
        $score = 50;
        
        // Comment count
        if ($post->comment_count > 20) {
            $score += 20;
        } elseif ($post->comment_count > 10) {
            $score += 10;
        }
        
        // Word count
        $word_count = str_word_count(strip_tags($post->post_content));
        if ($word_count > 2000) {
            $score += 15;
        } elseif ($word_count > 1000) {
            $score += 10;
        }
        
        // Recency
        $post_age_days = (time() - strtotime($post->post_date)) / DAY_IN_SECONDS;
        if ($post_age_days < 90) {
            $score += 15;
        } elseif ($post_age_days < 180) {
            $score += 10;
        }
        
        return min(100, $score);
    }
    
    /**
     * Detect conversion pages
     */
    private function detect_conversion_pages() {
        $conversion_pages = array();
        
        // Common conversion page patterns
        $patterns = array(
            'iletişim',
            'contact',
            'teklif',
            'quote',
            'demo',
            'deneme',
            'kayıt',
            'register',
        );
        
        foreach ($patterns as $pattern) {
            $pages = get_posts(array(
                'post_type' => 'page',
                'post_status' => 'publish',
                's' => $pattern,
                'numberposts' => 5,
            ));
            
            foreach ($pages as $page) {
                $conversion_pages[] = array(
                    'id' => $page->ID,
                    'title' => $page->post_title,
                    'url' => get_permalink($page->ID),
                    'type' => 'conversion_page',
                    'value_score' => 80, // Conversion pages are high value
                );
            }
        }
        
        return $conversion_pages;
    }
    
    /**
     * Get money page statistics
     */
    public function get_statistics() {
        $money_pages = $this->detect_money_pages();
        
        $stats = array(
            'total' => count($money_pages),
            'products' => 0,
            'categories' => 0,
            'commercial_posts' => 0,
            'conversion_pages' => 0,
            'avg_value_score' => 0,
        );
        
        $total_value = 0;
        
        foreach ($money_pages as $page) {
            $type = $page['type'] ?? '';
            
            if ($type === 'product') {
                $stats['products']++;
            } elseif ($type === 'category') {
                $stats['categories']++;
            } elseif ($type === 'commercial_post') {
                $stats['commercial_posts']++;
            } elseif ($type === 'conversion_page') {
                $stats['conversion_pages']++;
            }
            
            $total_value += $page['value_score'] ?? 0;
        }
        
        if ($stats['total'] > 0) {
            $stats['avg_value_score'] = round($total_value / $stats['total']);
        }
        
        return $stats;
    }
}
