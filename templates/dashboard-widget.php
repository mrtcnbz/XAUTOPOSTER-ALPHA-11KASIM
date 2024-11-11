<?php
if (!defined('ABSPATH')) {
    exit;
}

$api_verified = get_option('xautoposter_api_verified', false);
$auto_share_enabled = get_option('xautoposter_options')['auto_share'] ?? false;
$last_shared_posts = get_posts([
    'meta_key' => '_xautoposter_shared',
    'meta_value' => '1',
    'orderby' => 'meta_value',
    'order' => 'DESC',
    'posts_per_page' => 5
]);
?>

<div class="xautoposter-dashboard-widget">
    <div class="status-section">
        <h4><?php _e('Durum', 'xautoposter'); ?></h4>
        <p>
            <strong><?php _e('API Durumu:', 'xautoposter'); ?></strong>
            <?php if ($api_verified): ?>
                <span class="status-ok"><?php _e('Bağlı', 'xautoposter'); ?></span>
            <?php else: ?>
                <span class="status-error"><?php _e('Bağlı Değil', 'xautoposter'); ?></span>
            <?php endif; ?>
        </p>
        <p>
            <strong><?php _e('Otomatik Paylaşım:', 'xautoposter'); ?></strong>
            <?php if ($auto_share_enabled): ?>
                <span class="status-ok"><?php _e('Aktif', 'xautoposter'); ?></span>
            <?php else: ?>
                <span class="status-warning"><?php _e('Pasif', 'xautoposter'); ?></span>
            <?php endif; ?>
        </p>
    </div>

    <?php if (!empty($last_shared_posts)): ?>
        <div class="recent-shares">
            <h4><?php _e('Son Paylaşılan Gönderiler', 'xautoposter'); ?></h4>
            <ul>
                <?php foreach ($last_shared_posts as $post): ?>
                    <li>
                        <a href="<?php echo get_edit_post_link($post->ID); ?>">
                            <?php echo get_the_title($post->ID); ?>
                        </a>
                        <span class="share-time">
                            <?php 
                            $share_time = get_post_meta($post->ID, '_xautoposter_share_time', true);
                            echo human_time_diff(strtotime($share_time), current_time('timestamp')) . ' önce';
                            ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="widget-actions">
        <a href="<?php echo admin_url('admin.php?page=xautoposter'); ?>" class="button button-primary">
            <?php _e('Eklenti Ayarları', 'xautoposter'); ?>
        </a>
    </div>
</div>

<style>
.xautoposter-dashboard-widget {
    padding: 12px;
}

.xautoposter-dashboard-widget .status-section {
    margin-bottom: 20px;
}

.xautoposter-dashboard-widget .status-ok {
    color: #46b450;
}

.xautoposter-dashboard-widget .status-error {
    color: #dc3232;
}

.xautoposter-dashboard-widget .status-warning {
    color: #ffb900;
}

.xautoposter-dashboard-widget .recent-shares {
    margin-bottom: 20px;
}

.xautoposter-dashboard-widget .recent-shares ul {
    margin: 0;
}

.xautoposter-dashboard-widget .recent-shares li {
    margin-bottom: 8px;
    padding-bottom: 8px;
    border-bottom: 1px solid #eee;
}

.xautoposter-dashboard-widget .share-time {
    color: #666;
    font-size: 12px;
    float: right;
}

.xautoposter-dashboard-widget .widget-actions {
    margin-top: 15px;
    text-align: right;
}
</style>