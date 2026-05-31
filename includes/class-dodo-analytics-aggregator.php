<?php
/**
 * Analytics Aggregator
 * 
 * Aggregates metrics from all Sprint 5 engines for analytics dashboard
 * 
 * @package DODO_AI_SEO
 * @since 3.0.0 (Sprint 5 - Phase 6)
 */

if (!defined('ABSPATH')) {
    exit;
}

class DODO_Analytics_Aggregator {
    
    /**
     * Get comprehensive site metrics
     * 
     * @return array All metrics
     */
    public function get_site_metrics() {
        return array(
            'geo_metrics' => $this->get_geo_metrics(),
            'humanization_metrics' => $this->get_humanization_metrics(),
            'intelligence_metrics' => $this->get_intelligence_metrics(),
            'publishing_metrics' => $this->get_publishing_metrics(),
            'trends' => $this->get_trends(),
        );
    }
    
    /**
     * Get GEO metrics
     */
    private function get_geo_metrics() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dodo_geo_scores';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") !== $table_name) {
            return $this->get_empty_geo_metrics();
        }
        
        $stats = $wpdb->get_row("
            SELECT 
                COUNT(*) as total_analyzed,
                AVG(geo_score) as avg_geo_score,
                AVG(answerability_score) as avg_answerability,
                AVG(citation_potential) as avg_citation,
                AVG(retrieval_friendliness) as avg_retrieval,
                AVG(ai_overview_compatibility) as avg_ai_overview
            FROM {$table_name}
        ", ARRAY_A);
        
        return array(
            'total_analyzed' => intval($stats['total_analyzed'] ?? 0),
            'avg_geo_score' => round(floatval($stats['avg_geo_score'] ?? 0)),
            'avg_answerability' => round(floatval($stats['avg_answerability'] ?? 0)),
            'avg_citation' => round(floatval($stats['avg_citation'] ?? 0)),
            'avg_retrieval' => round(floatval($stats['avg_retrieval'] ?? 0)),
            'avg_ai_overview' => round(floatval($stats['avg_ai_overview'] ?? 0)),
        );
    }
    
    /**
     * Get humanization metrics
     */
    private function get_humanization_metrics() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dodo_humanization_analysis';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") !== $table_name) {
            return $this->get_empty_humanization_metrics();
        }
        
        $stats = $wpdb->get_row("
            SELECT 
                COUNT(*) as total_analyzed,
                AVG(humanization_score) as avg_humanization,
                AVG(robotic_score) as avg_robotic,
                AVG(burstiness_score) as avg_burstiness,
                AVG(conversational_score) as avg_conversational
            FROM {$table_name}
        ", ARRAY_A);
        
        return array(
            'total_analyzed' => intval($stats['total_analyzed'] ?? 0),
            'avg_humanization' => round(floatval($stats['avg_humanization'] ?? 0)),
            'avg_robotic' => round(floatval($stats['avg_robotic'] ?? 0)),
            'avg_burstiness' => round(floatval($stats['avg_burstiness'] ?? 0)),
            'avg_conversational' => round(floatval($stats['avg_conversational'] ?? 0)),
        );
    }
    
    /**
     * Get intelligence metrics
     */
    private function get_intelligence_metrics() {
        $intelligence = new DODO_Content_Intelligence();
        $latest = $intelligence->get_latest_analysis();
        
        if (!$latest || empty($latest['analysis_data'])) {
            return $this->get_empty_intelligence_metrics();
        }
        
        $data = $latest['analysis_data'];
        
        return array(
            'pillar_count' => count($data['topic_map']['pillar_topics'] ?? array()),
            'total_posts' => $data['topic_map']['total_posts'] ?? 0,
            'cannibalization_issues' => $data['cannibalization']['total_issues'] ?? 0,
            'orphan_posts' => $data['internal_links']['orphan_count'] ?? 0,
            'overall_authority' => $data['topical_authority']['overall_authority'] ?? 0,
            'analyzed_at' => $latest['created_at'] ?? '',
        );
    }
    
    /**
     * Get publishing metrics
     */
    private function get_publishing_metrics() {
        $pipeline = new DODO_Publishing_Pipeline();
        $stats = $pipeline->get_stats();
        
        return array(
            'total_in_pipeline' => intval($stats['total'] ?? 0),
            'scheduled' => intval($stats['scheduled'] ?? 0),
            'published' => intval($stats['published'] ?? 0),
            'pending_review' => intval($stats['pending_review'] ?? 0),
            'approved' => intval($stats['approved'] ?? 0),
        );
    }
    
    /**
     * Get trends (last 30 days)
     */
    private function get_trends() {
        global $wpdb;
        
        $geo_table = $wpdb->prefix . 'dodo_geo_scores';
        $humanization_table = $wpdb->prefix . 'dodo_humanization_analysis';
        
        $thirty_days_ago = date('Y-m-d', strtotime('-30 days'));
        
        // GEO trends
        $geo_trends = array();
        if ($wpdb->get_var("SHOW TABLES LIKE '{$geo_table}'") === $geo_table) {
            $geo_trends = $wpdb->get_results($wpdb->prepare("
                SELECT 
                    DATE(created_at) as date,
                    AVG(geo_score) as avg_score
                FROM {$geo_table}
                WHERE created_at >= %s
                GROUP BY DATE(created_at)
                ORDER BY date ASC
            ", $thirty_days_ago), ARRAY_A);
        }
        
        // Humanization trends
        $humanization_trends = array();
        if ($wpdb->get_var("SHOW TABLES LIKE '{$humanization_table}'") === $humanization_table) {
            $humanization_trends = $wpdb->get_results($wpdb->prepare("
                SELECT 
                    DATE(created_at) as date,
                    AVG(humanization_score) as avg_score
                FROM {$humanization_table}
                WHERE created_at >= %s
                GROUP BY DATE(created_at)
                ORDER BY date ASC
            ", $thirty_days_ago), ARRAY_A);
        }
        
        return array(
            'geo' => $geo_trends,
            'humanization' => $humanization_trends,
        );
    }
    
    /**
     * Empty metrics fallbacks
     */
    private function get_empty_geo_metrics() {
        return array(
            'total_analyzed' => 0,
            'avg_geo_score' => 0,
            'avg_answerability' => 0,
            'avg_citation' => 0,
            'avg_retrieval' => 0,
            'avg_ai_overview' => 0,
        );
    }
    
    private function get_empty_humanization_metrics() {
        return array(
            'total_analyzed' => 0,
            'avg_humanization' => 0,
            'avg_robotic' => 0,
            'avg_burstiness' => 0,
            'avg_conversational' => 0,
        );
    }
    
    private function get_empty_intelligence_metrics() {
        return array(
            'pillar_count' => 0,
            'total_posts' => 0,
            'cannibalization_issues' => 0,
            'orphan_posts' => 0,
            'overall_authority' => 0,
            'analyzed_at' => '',
        );
    }
}
