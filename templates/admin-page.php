<?php
if (!defined('ABSPATH')) {
    exit;
}

// Get current tab
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'settings';

// Nonce oluştur
$nonce = wp_create_nonce('xautoposter_admin');
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <nav class="nav-tab-wrapper">
        <a href="?page=xautoposter&tab=settings" 
           class="nav-tab <?php echo $current_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
            <?php _e('API Ayarları', 'xautoposter'); ?>
        </a>
        <a href="?page=xautoposter&tab=auto-share" 
           class="nav-tab <?php echo $current_tab === 'auto-share' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Otomatik Paylaşım', 'xautoposter'); ?>
        </a>
        <a href="?page=xautoposter&tab=manual-share" 
           class="nav-tab <?php echo $current_tab === 'manual-share' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Manuel Paylaşım', 'xautoposter'); ?>
        </a>
        <a href="?page=xautoposter&tab=metrics" 
           class="nav-tab <?php echo $current_tab === 'metrics' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Metrikler', 'xautoposter'); ?>
        </a>
    </nav>
    
    <div class="tab-content">
        <?php if ($current_tab === 'settings'): ?>
            <div class="card">
                <?php 
                $api_verified = get_option('xautoposter_api_verified', false);
                if ($api_verified): ?>
                    <div class="api-status-bar">
                        <span class="status-text success">
                            <?php _e('API bağlantısı aktif', 'xautoposter'); ?>
                        </span>
                        <button type="button" id="unlock-api-settings" class="button">
                            <?php _e('API Ayarlarını Düzenle', 'xautoposter'); ?>
                        </button>
                    </div>
                <?php endif; ?>
                
                <form method="post" action="options.php">
                    <?php
                        settings_fields('xautoposter_options');
                        wp_nonce_field('xautoposter_admin', '_wpnonce');
                        do_settings_sections('xautoposter-settings');
                        submit_button();
                    ?>
                </form>
            </div>
            
        <?php elseif ($current_tab === 'auto-share'): ?>
            <div class="card">
                <form method="post" action="options.php">
                    <?php
                        settings_fields('xautoposter_auto_share_options');
                        wp_nonce_field('xautoposter_admin');
                        do_settings_sections('xautoposter-auto-share');
                        submit_button();
                    ?>
                </form>
            </div>
            
        <?php elseif ($current_tab === 'manual-share'): ?>
            <div class="card">
                <h2><?php _e('Manuel Paylaşım', 'xautoposter'); ?></h2>
                
                <?php
                // Sayfalama parametreleri
                $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
                $posts_per_page = 10;
                
                // Kategori filtresi
                $categories = get_categories(['hide_empty' => false]);
                $selected_category = isset($_GET['category']) ? intval($_GET['category']) : 0;
                
                // Sıralama
                $sort_order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'desc';
                
                // WP_Query parametreleri
                $args = [
                    'post_type' => 'post',
                    'post_status' => 'publish',
                    'posts_per_page' => $posts_per_page,
                    'paged' => $paged,
                    'orderby' => 'date',
                    'order' => strtoupper($sort_order)
                ];
                
                if ($selected_category) {
                    $args['cat'] = $selected_category;
                }
                
                $query = new WP_Query($args);
                ?>
                
                <div class="tablenav top">
                    <div class="alignleft actions">
                        <form method="get" class="filter-form">
                            <input type="hidden" name="page" value="xautoposter">
                            <input type="hidden" name="tab" value="manual-share">
                            
                            <select name="category" id="category-filter">
                                <option value=""><?php _e('Tüm Kategoriler', 'xautoposter'); ?></option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo esc_attr($category->term_id); ?>" 
                                            <?php selected($selected_category, $category->term_id); ?>>
                                        <?php echo esc_html($category->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            
                            <select name="order" id="date-sort">
                                <option value="desc" <?php selected($sort_order, 'desc'); ?>><?php _e('En Yeni', 'xautoposter'); ?></option>
                                <option value="asc" <?php selected($sort_order, 'asc'); ?>><?php _e('En Eski', 'xautoposter'); ?></option>
                            </select>
                            
                            <input type="submit" class="button" value="<?php esc_attr_e('Filtrele', 'xautoposter'); ?>">
                        </form>
                    </div>
                </div>
                
                <table class="wp-list-table widefat fixed striped posts-table">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="select-all-posts"></th>
                            <th><?php _e('Görsel', 'xautoposter'); ?></th>
                            <th><?php _e('Başlık', 'xautoposter'); ?></th>
                            <th><?php _e('Kategori', 'xautoposter'); ?></th>
                            <th><?php _e('Tarih', 'xautoposter'); ?></th>
                            <th><?php _e('Durum', 'xautoposter'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($query->have_posts()): while ($query->have_posts()): $query->the_post();
                            $post_id = get_the_ID();
                            $is_shared = get_post_meta($post_id, '_xautoposter_shared', true);
                            $share_time = get_post_meta($post_id, '_xautoposter_share_time', true);
                            $thumbnail = get_the_post_thumbnail_url($post_id, 'thumbnail');
                            $categories = get_the_category();
                        ?>
                            <tr class="post-row">
                                <td>
                                    <input type="checkbox" name="posts[]" value="<?php echo $post_id; ?>" 
                                           <?php echo $is_shared ? 'disabled' : ''; ?>>
                                </td>
                                <td>
                                    <?php if ($thumbnail): ?>
                                        <img src="<?php echo esc_url($thumbnail); ?>" 
                                             alt="<?php echo esc_attr(get_the_title()); ?>"
                                             style="width: 50px; height: 50px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="no-image"><?php _e('Görsel Yok', 'xautoposter'); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?php the_title(); ?></td>
                                <td>
                                    <?php
                                    $cat_names = [];
                                    foreach ($categories as $category) {
                                        $cat_names[] = esc_html($category->name);
                                    }
                                    echo implode(', ', $cat_names);
                                    ?>
                                </td>
                                <td><?php echo get_the_date(); ?></td>
                                <td>
                                    <?php if ($is_shared): ?>
                                        <span class="dashicons dashicons-yes-alt"></span> 
                                        <?php echo sprintf(__('Paylaşıldı: %s', 'xautoposter'), 
                                              wp_date(get_option('date_format') . ' ' . get_option('time_format'), 
                                              strtotime($share_time))); ?>
                                    <?php else: ?>
                                        <span class="dashicons dashicons-minus"></span>
                                        <?php _e('Paylaşılmadı', 'xautoposter'); ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; else: ?>
                            <tr>
                                <td colspan="6"><?php _e('Gönderi bulunamadı.', 'xautoposter'); ?></td>
                            </tr>
                        <?php endif; wp_reset_postdata(); ?>
                    </tbody>
                </table>
                
                <div class="tablenav bottom">
                    <div class="alignleft actions">
                        <button type="button" id="share-selected" class="button button-primary" disabled>
                            <?php _e('Seçili Gönderileri Paylaş', 'xautoposter'); ?>
                        </button>
                    </div>
                    
                    <div class="tablenav-pages">
                        <?php
                        $total_pages = $query->max_num_pages;
                        
                        if ($total_pages > 1) {
                            $page_links = paginate_links([
                                'base' => add_query_arg('paged', '%#%'),
                                'format' => '',
                                'prev_text' => __('&laquo;'),
                                'next_text' => __('&raquo;'),
                                'total' => $total_pages,
                                'current' => $paged,
                                'type' => 'array'
                            ]);
                            
                            if ($page_links) {
                                echo '<div class="pagination">';
                                foreach ($page_links as $link) {
                                    echo $link;
                                }
                                echo '</div>';
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
            
        <?php elseif ($current_tab === 'metrics'): ?>
            <?php include(XAUTOPOSTER_PATH . 'templates/metrics-page.php'); ?>
        <?php endif; ?>
    </div>
</div>

<style>
.api-status-bar {
    background: #f8f9fa;
    padding: 15px;
    margin-bottom: 20px;
    border: 1px solid #ddd;
    border-radius: 4px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.status-text {
    font-weight: 500;
}

.status-text.success {
    color: #28a745;
}

.card {
    max-width: none;
    margin-top: 20px;
    padding: 20px;
}

.filter-form {
    display: flex;
    gap: 10px;
    align-items: center;
}

.posts-table {
    margin-top: 15px;
}

.no-image {
    width: 50px;
    height: 50px;
    background: #f0f0f0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    color: #666;
}

.pagination {
    display: flex;
    gap: 5px;
    align-items: center;
}

.pagination .page-numbers {
    padding: 5px 10px;
    border: 1px solid #ddd;
    text-decoration: none;
    border-radius: 3px;
}

.pagination .current {
    background: #0073aa;
    color: white;
    border-color: #0073aa;
}

.dashicons-yes-alt {
    color: #28a745;
}

.dashicons-minus {
    color: #dc3545;
}
</style>
