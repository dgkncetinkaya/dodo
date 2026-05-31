<?php
/**
 * Publishing Workflow System
 * 
 * @package DODO_AI_SEO
 * @since 2.0.0 (Sprint 3 - Task 9)
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Workflow_Engine {
    
    const WORKFLOW_STAGES = array(
        'draft' => 'Draft',
        'ai_reviewed' => 'AI Reviewed',
        'seo_approved' => 'SEO Approved',
        'geo_approved' => 'GEO Approved',
        'ready_to_publish' => 'Ready to Publish',
        'scheduled' => 'Scheduled',
        'published' => 'Published',
    );
    
    public function get_post_workflow_status($post_id) {
        $status = get_post_meta($post_id, '_dodo_workflow_status', true);
        return $status ?: 'draft';
    }
    
    public function update_workflow_status($post_id, $status) {
        if (!array_key_exists($status, self::WORKFLOW_STAGES)) {
            return false;
        }
        
        update_post_meta($post_id, '_dodo_workflow_status', $status);
        update_post_meta($post_id, '_dodo_workflow_updated', current_time('mysql'));
        
        return true;
    }
    
    public function calculate_publish_readiness($post_id) {
        $post = get_post($post_id);
        $score = 0;
        $blocking_warnings = array();
        
        // Has content
        if (str_word_count(strip_tags($post->post_content)) >= 500) {
            $score += 20;
        } else {
            $blocking_warnings[] = 'İçerik çok kısa (500+ kelime gerekli)';
        }
        
        // Has title
        if (!empty($post->post_title)) {
            $score += 15;
        } else {
            $blocking_warnings[] = 'Başlık eksik';
        }
        
        // Has focus keyword
        $focus_keyword = get_post_meta($post_id, 'rank_math_focus_keyword', true);
        if (!empty($focus_keyword)) {
            $score += 15;
        }
        
        // Has category
        $categories = wp_get_post_categories($post_id);
        if (!empty($categories)) {
            $score += 10;
        }
        
        // Has meta description
        $meta_desc = get_post_meta($post_id, 'rank_math_description', true);
        if (!empty($meta_desc)) {
            $score += 15;
        }
        
        // Has featured image
        if (has_post_thumbnail($post_id)) {
            $score += 10;
        }
        
        // Has internal links
        if (substr_count($post->post_content, home_url()) >= 2) {
            $score += 15;
        }
        
        return array(
            'readiness_score' => $score,
            'is_ready' => $score >= 70 && empty($blocking_warnings),
            'blocking_warnings' => $blocking_warnings,
        );
    }
    
    public function get_workflow_badge($status) {
        $badges = array(
            'draft' => array('label' => 'Taslak', 'color' => '#6b7280'),
            'ai_reviewed' => array('label' => 'AI İncelendi', 'color' => '#3b82f6'),
            'seo_approved' => array('label' => 'SEO Onaylandı', 'color' => '#10b981'),
            'geo_approved' => array('label' => 'GEO Onaylandı', 'color' => '#8b5cf6'),
            'ready_to_publish' => array('label' => 'Yayına Hazır', 'color' => '#059669'),
            'scheduled' => array('label' => 'Zamanlandı', 'color' => '#f59e0b'),
            'published' => array('label' => 'Yayında', 'color' => '#10b981'),
        );
        
        return $badges[$status] ?? $badges['draft'];
    }
}
