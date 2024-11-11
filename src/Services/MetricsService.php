<?php
namespace XAutoPoster\Services;

class MetricsService {
    private $twitter;
    
    public function __construct($twitter) {
        $this->twitter = $twitter;
    }
    
    public function updateMetrics($postId) {
        try {
            $tweetId = get_post_meta($postId, '_xautoposter_tweet_id', true);
            if (!$tweetId) {
                return false;
            }
            
            $metrics = $this->twitter->getTweetMetrics($tweetId);
            if ($metrics) {
                update_post_meta($postId, '_xautoposter_tweet_metrics', $metrics);
                return true;
            }
            
            return false;
        } catch (\Exception $e) {
            error_log('XAutoPoster Metrics Error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function updateAllMetrics() {
        $posts = get_posts([
            'meta_key' => '_xautoposter_shared',
            'meta_value' => '1',
            'posts_per_page' => -1
        ]);
        
        $updated = 0;
        foreach ($posts as $post) {
            if ($this->updateMetrics($post->ID)) {
                $updated++;
            }
        }
        
        return $updated;
    }
    
    public function scheduleMetricsUpdate() {
        if (!wp_next_scheduled('xautoposter_update_metrics')) {
            wp_schedule_event(time(), 'hourly', 'xautoposter_update_metrics');
        }
    }
    
    public function unscheduleMetricsUpdate() {
        wp_clear_scheduled_hook('xautoposter_update_metrics');
    }
}