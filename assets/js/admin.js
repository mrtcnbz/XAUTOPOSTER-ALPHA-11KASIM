jQuery(document).ready(function($) {
    // API Ayarları Yönetimi
    const ApiSettings = {
        init: function() {
            this.$unlockButton = $('#unlock-api-settings');
            if (!this.$unlockButton.length) return;
            
            this.bindEvents();
        },

        bindEvents: function() {
            this.$unlockButton.on('click', (e) => {
                e.preventDefault();
                this.handleUnlock();
            });
        },

        handleUnlock: function() {
            if (!confirm(xautoposter.strings.confirm_unlock)) return;

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'xautoposter_reset_api_verification',
                    nonce: $('#_wpnonce').val()
                },
                beforeSend: () => {
                    this.$unlockButton.prop('disabled', true);
                },
                success: (response) => {
                    if (response.success) {
                        $('input[name^="xautoposter_options"]').prop('readonly', false);
                        $('.api-status-bar').slideUp();
                        
                        $('<div class="notice notice-success is-dismissible"><p>' + 
                          response.data.message + '</p></div>')
                            .hide()
                            .insertAfter('.wrap > h1')
                            .slideDown();
                            
                        $('input[type="submit"]').prop('disabled', false);
                    } else {
                        alert(response.data.message || xautoposter.strings.error);
                    }
                },
                error: (xhr, status, error) => {
                    console.error('AJAX Error:', error);
                    alert(xautoposter.strings.error);
                },
                complete: () => {
                    this.$unlockButton.prop('disabled', false);
                }
            });
        }
    };

    // Manuel Paylaşım İşlemleri
    const ManualShare = {
        init: function() {
            const $postsTable = $('.posts-table');
            if (!$postsTable.length) return;

            this.$table = $postsTable;
            this.$shareButton = $('#share-selected');
            this.bindEvents();
            this.updateShareButtonState();
        },

        bindEvents: function() {
            // Tüm Seç/Kaldır
            $('#select-all-posts').on('change', (e) => {
                $('input[name="posts[]"]:not(:disabled)').prop('checked', $(e.target).prop('checked'));
                this.updateShareButtonState();
            });

            // Tekil checkbox değişimlerini izle
            this.$table.on('change', 'input[name="posts[]"]', () => {
                this.updateShareButtonState();
            });

            // Paylaşım
            this.$shareButton.on('click', (e) => {
                e.preventDefault();
                this.handleShare();
            });

            // Kategori Filtresi ve Sıralama
            $('#category-filter, #date-sort').on('change', function() {
                $(this).closest('form').submit();
            });
        },

        updateShareButtonState: function() {
            const checkedPosts = $('input[name="posts[]"]:checked').length;
            this.$shareButton.prop('disabled', checkedPosts === 0);
        },

        handleShare: function() {
            const posts = $('input[name="posts[]"]:checked').map(function() {
                return $(this).val();
            }).get();

            if (posts.length === 0) {
                alert(xautoposter.strings.no_posts_selected);
                return;
            }

            this.$shareButton.prop('disabled', true).text(xautoposter.strings.sharing);

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'xautoposter_share_posts',
                    posts: posts,
                    nonce: $('#_wpnonce').val()
                },
                success: (response) => {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert(response.data.message || xautoposter.strings.error);
                    }
                },
                error: (xhr, status, error) => {
                    console.error('AJAX Error:', error);
                    alert(xautoposter.strings.error);
                },
                complete: () => {
                    this.$shareButton.prop('disabled', false)
                        .text(xautoposter.strings.share_selected);
                }
            });
        }
    };

    // Başlat
    ApiSettings.init();
    ManualShare.init();
});