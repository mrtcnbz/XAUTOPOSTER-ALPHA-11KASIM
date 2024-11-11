<?php
namespace XAutoPoster\Admin;

class Settings {
    public function registerSettings() {
        // API Ayarları
        register_setting(
            'xautoposter_options',
            'xautoposter_options',
            [$this, 'validateOptions']
        );

        add_settings_section(
            'xautoposter_api_settings',
            __('Twitter API Ayarları', 'xautoposter'),
            [$this, 'renderSettingsHeader'],
            'xautoposter-settings'
        );

        // API Key
        add_settings_field(
            'api_key',
            __('API Key', 'xautoposter'),
            [$this, 'renderTextField'],
            'xautoposter-settings',
            'xautoposter_api_settings',
            ['name' => 'api_key']
        );

        // API Secret
        add_settings_field(
            'api_secret',
            __('API Secret', 'xautoposter'),
            [$this, 'renderTextField'],
            'xautoposter-settings',
            'xautoposter_api_settings',
            ['name' => 'api_secret']
        );

        // Access Token
        add_settings_field(
            'access_token',
            __('Access Token', 'xautoposter'),
            [$this, 'renderTextField'],
            'xautoposter-settings',
            'xautoposter_api_settings',
            ['name' => 'access_token']
        );

        // Access Token Secret
        add_settings_field(
            'access_token_secret',
            __('Access Token Secret', 'xautoposter'),
            [$this, 'renderTextField'],
            'xautoposter-settings',
            'xautoposter_api_settings',
            ['name' => 'access_token_secret']
        );

        // Otomatik Paylaşım Ayarları
        register_setting(
            'xautoposter_auto_share_options',
            'xautoposter_auto_share_options',
            [$this, 'validateAutoShareOptions']
        );

        add_settings_section(
            'xautoposter_auto_share_settings',
            __('Otomatik Paylaşım Ayarları', 'xautoposter'),
            null,
            'xautoposter-auto-share'
        );

        // Paylaşım Aralığı
        add_settings_field(
            'interval',
            __('Paylaşım Aralığı', 'xautoposter'),
            [$this, 'renderIntervalField'],
            'xautoposter-auto-share',
            'xautoposter_auto_share_settings'
        );

        // Kategoriler
        add_settings_field(
            'categories',
            __('Paylaşılacak Kategoriler', 'xautoposter'),
            [$this, 'renderCategoriesField'],
            'xautoposter-auto-share',
            'xautoposter_auto_share_settings'
        );

        // Otomatik Paylaşım
        add_settings_field(
            'auto_share',
            __('Otomatik Paylaşım', 'xautoposter'),
            [$this, 'renderCheckboxField'],
            'xautoposter-auto-share',
            'xautoposter_auto_share_settings',
            [
                'name' => 'auto_share',
                'label' => __('Yeni gönderiler otomatik olarak paylaşılsın', 'xautoposter')
            ]
        );

        // Tweet Şablonu
        add_settings_field(
            'post_template',
            __('Tweet Şablonu', 'xautoposter'),
            [$this, 'renderTemplateField'],
            'xautoposter-auto-share',
            'xautoposter_auto_share_settings'
        );
    }

    public function renderSettingsHeader() {
        $api_verified = get_option('xautoposter_api_verified', false);
        $api_error = get_option('xautoposter_api_error', '');
        
        if ($api_verified) {
            echo '<div class="notice notice-success inline"><p>' . 
                 esc_html__('Twitter API bağlantısı başarılı.', 'xautoposter') . 
                 '</p></div>';
        } elseif ($api_error) {
            echo '<div class="notice notice-error inline"><p>' . 
                 esc_html__('Twitter API Hatası: ', 'xautoposter') . 
                 esc_html($api_error) . 
                 '</p></div>';
        }
    }

    public function renderTextField($args) {
        $options = get_option('xautoposter_options', []);
        $name = $args['name'];
        $value = isset($options[$name]) ? $options[$name] : '';
        $api_verified = get_option('xautoposter_api_verified', false);
        
        printf(
            '<input type="text" id="%s" name="xautoposter_options[%s]" value="%s" class="regular-text" %s>',
            esc_attr($name),
            esc_attr($name),
            esc_attr($value),
            $api_verified ? 'readonly="readonly"' : ''
        );
    }

    public function renderIntervalField() {
        $options = get_option('xautoposter_auto_share_options', []);
        $interval = isset($options['interval']) ? $options['interval'] : '30min';
        
        $intervals = [
            '5min' => __('5 Dakika', 'xautoposter'),
            '15min' => __('15 Dakika', 'xautoposter'),
            '30min' => __('30 Dakika', 'xautoposter'),
            '60min' => __('60 Dakika', 'xautoposter')
        ];
        
        echo '<select id="interval" name="xautoposter_auto_share_options[interval]">';
        foreach ($intervals as $value => $label) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr($value),
                selected($interval, $value, false),
                esc_html($label)
            );
        }
        echo '</select>';
        
        echo '<p class="description">' . 
             __('Otomatik paylaşımlar için zaman aralığını seçin.', 'xautoposter') . 
             '</p>';
    }

    public function renderCategoriesField() {
        $options = get_option('xautoposter_auto_share_options', []);
        $selected = isset($options['categories']) ? (array)$options['categories'] : [];
        
        $categories = get_categories(['hide_empty' => false]);
        
        echo '<div class="categories-list">';
        foreach ($categories as $category) {
            printf(
                '<label><input type="checkbox" name="xautoposter_auto_share_options[categories][]" value="%d" %s> %s</label><br>',
                $category->term_id,
                checked(in_array($category->term_id, $selected), true, false),
                esc_html($category->name)
            );
        }
        echo '</div>';
        
        echo '<p class="description">' . 
             __('Paylaşılacak kategorileri seçin. Hiçbiri seçilmezse tüm kategoriler paylaşılır.', 'xautoposter') . 
             '</p>';
    }

    public function renderCheckboxField($args) {
        $options = get_option('xautoposter_auto_share_options', []);
        $name = $args['name'];
        $checked = isset($options[$name]) && $options[$name] === '1';
        
        printf(
            '<label><input type="checkbox" name="xautoposter_auto_share_options[%s]" value="1" %s> %s</label>',
            esc_attr($name),
            checked($checked, true, false),
            esc_html($args['label'])
        );
    }

    public function renderTemplateField() {
        $options = get_option('xautoposter_auto_share_options', []);
        $template = isset($options['post_template']) ? $options['post_template'] : '%title% %link% %hashtags%';
        
        printf(
            '<textarea name="xautoposter_auto_share_options[post_template]" rows="3" class="large-text">%s</textarea>',
            esc_textarea($template)
        );
        
        echo '<p class="description">' . 
             __('Kullanılabilir değişkenler: %title% (başlık), %link% (bağlantı), %hashtags% (etiketler)', 'xautoposter') . 
             '</p>';
    }

    public function validateOptions($input) {
        $output = [];
        
        $fields = ['api_key', 'api_secret', 'access_token', 'access_token_secret'];
        foreach ($fields as $field) {
            if (isset($input[$field])) {
                $output[$field] = sanitize_text_field($input[$field]);
            }
        }
        
        return $output;
    }

    public function validateAutoShareOptions($input) {
        $output = [];
        
        // Interval
        if (isset($input['interval'])) {
            $output['interval'] = sanitize_text_field($input['interval']);
        }
        
        // Categories
        if (isset($input['categories']) && is_array($input['categories'])) {
            $output['categories'] = array_map('intval', $input['categories']);
        }
        
        // Auto Share
        $output['auto_share'] = isset($input['auto_share']) ? '1' : '0';
        
        // Template
        if (isset($input['post_template'])) {
            $output['post_template'] = sanitize_textarea_field($input['post_template']);
        }
        
        return $output;
    }
}