<?php
if (!defined('ABSPATH')) {
    exit;
}

// Admin menü ekleme
function cnnturk_haber_botu_add_admin_menu() {
    add_menu_page(
        'Haber Botu',
        'Haber Botu',
        'manage_options',
        'haber_botu',
        'haber_botu_genel_ayarlar',
        'dashicons-rss',
        20
    );

    add_submenu_page(
        'haber_botu',
        'Genel Ayarlar',
        'Genel Ayarlar',
        'manage_options',
        'haber_botu',
        'haber_botu_genel_ayarlar'
    );

    add_submenu_page(
        'haber_botu',
        'Test Et',
        'Test Et',
        'manage_options',
        'haber_botu_test',
        'haber_botu_test_page'
    );

    add_submenu_page(
        'haber_botu',
        'Profil',
        'Profil',
        'manage_options',
        'haber_botu_profil',
        'haber_botu_profil_page'
    );

    add_submenu_page(
        'haber_botu',
        'Lisans',
        'Lisans',
        'manage_options',
        'haber_botu_lisans',
        'haber_botu_lisans_page'
    );
}
add_action('admin_menu', 'cnnturk_haber_botu_add_admin_menu');

// Admin panel stil dosyasını ekle
function haber_botu_admin_styles() {
    wp_enqueue_style('haber-botu-admin', plugins_url('assets/css/admin.css', dirname(__FILE__)));
}
add_action('admin_enqueue_scripts', 'haber_botu_admin_styles');

// Genel Ayarlar sayfası
function haber_botu_genel_ayarlar() {
    ?>
    <div class="wrap haber-botu-admin">
        <div class="haber-botu-header">
            <h1>Haber Botu Ayarları</h1>
            <div class="haber-botu-header-right">
                <a href="?page=haber_botu_test" class="button button-primary">Test Et</a>
                <a href="https://example.com/docs" target="_blank" class="button">Yardım</a>
            </div>
        </div>

        <div class="notice notice-info">
            <p>Haberleri otomatik olarak çeken ve sitenizde yayınlayan bot ayarları.</p>
        </div>

        <div class="haber-botu-content">
            <div class="haber-botu-card">
                <h2>Genel Ayarlar</h2>
                <form method="post" action="options.php">
                    <?php
                    settings_fields('haber_botu_settings');
                    ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Çekme Sıklığı</th>
                            <td>
                                <select name="haber_bot_interval">
                                    <option value="hourly" <?php selected(get_option('haber_bot_interval'), 'hourly'); ?>>Saatlik</option>
                                    <option value="twicedaily" <?php selected(get_option('haber_bot_interval'), 'twicedaily'); ?>>Günde İki Kez</option>
                                    <option value="daily" <?php selected(get_option('haber_bot_interval'), 'daily'); ?>>Günlük</option>
                                </select>
                                <p class="description">Haberlerin hangi sıklıkla çekileceğini belirleyin.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Haber Kategorisi</th>
                            <td>
                                <?php
                                wp_dropdown_categories(array(
                                    'name' => 'haber_bot_category',
                                    'selected' => get_option('haber_bot_category'),
                                    'hide_empty' => 0,
                                    'show_option_none' => 'Kategori Seçin'
                                ));
                                ?>
                                <p class="description">Haberlerin hangi kategoride yayınlanacağını seçin.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Yayın Durumu</th>
                            <td>
                                <select name="haber_bot_status">
                                    <option value="publish" <?php selected(get_option('haber_bot_status'), 'publish'); ?>>Hemen Yayınla</option>
                                    <option value="draft" <?php selected(get_option('haber_bot_status'), 'draft'); ?>>Taslak Olarak Kaydet</option>
                                </select>
                                <p class="description">Çekilen haberlerin nasıl yayınlanacağını belirleyin.</p>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button('Ayarları Kaydet'); ?>
                </form>
            </div>

            <div class="haber-botu-card">
                <h2>Bot Durumu</h2>
                <table class="widefat">
                    <tr>
                        <td><strong>Son Çalışma:</strong></td>
                        <td><?php echo get_option('haber_bot_last_run', 'Henüz çalışmadı'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Toplam Haber:</strong></td>
                        <td><?php echo wp_count_posts()->publish; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Durum:</strong></td>
                        <td>
                            <?php
                            $is_running = get_transient('haber_bot_running');
                            echo $is_running ? '<span class="status-active">Çalışıyor</span>' : '<span class="status-inactive">Beklemede</span>';
                            ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    <?php
}

// Test sayfası
function haber_botu_test_page() {
    include plugin_dir_path(__FILE__) . 'test-page.php';
}

// Profil sayfası
function haber_botu_profil_page() {
    ?>
    <div class="wrap haber-botu-admin">
        <h1>Profil Ayarları</h1>
        <div class="haber-botu-card">
            <form method="post" action="options.php">
                <?php
                settings_fields('haber_botu_profile_settings');
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">API Anahtarı</th>
                        <td>
                            <input type="text" name="haber_bot_api_key" value="<?php echo esc_attr(get_option('haber_bot_api_key')); ?>" class="regular-text">
                            <p class="description">Bot API anahtarınız.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">E-posta Bildirimleri</th>
                        <td>
                            <label>
                                <input type="checkbox" name="haber_bot_notifications" value="1" <?php checked(get_option('haber_bot_notifications'), 1); ?>>
                                Hata durumunda e-posta bildirimi al
                            </label>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Profil Ayarlarını Kaydet'); ?>
            </form>
        </div>
    </div>
    <?php
}

// Lisans sayfası
function haber_botu_lisans_page() {
    ?>
    <div class="wrap haber-botu-admin">
        <h1>Lisans Ayarları</h1>
        <div class="haber-botu-card">
            <form method="post" action="options.php">
                <?php
                settings_fields('haber_botu_license_settings');
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Lisans Anahtarı</th>
                        <td>
                            <input type="text" name="haber_bot_license_key" value="<?php echo esc_attr(get_option('haber_bot_license_key')); ?>" class="regular-text">
                            <p class="description">Premium özellikleri aktifleştirmek için lisans anahtarınızı girin.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Durum</th>
                        <td>
                            <?php
                            $license_status = get_option('haber_bot_license_status');
                            if ($license_status == 'active') {
                                echo '<span class="status-active">Aktif</span>';
                            } else {
                                echo '<span class="status-inactive">Pasif</span>';
                            }
                            ?>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Lisans Anahtarını Doğrula'); ?>
            </form>
        </div>
    </div>
    <?php
}
