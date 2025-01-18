<?php
if (!defined('ABSPATH')) {
    exit;
}

// Test sayfası menüsü ekleme
function cnnturk_haber_botu_add_test_page() {
    add_submenu_page(
        'cnnturk_haber_botu',
        'Test Sayfası',
        'Test Et',
        'manage_options',
        'cnnturk_haber_botu_test',
        'cnnturk_haber_botu_test_page'
    );
}
add_action('admin_menu', 'cnnturk_haber_botu_add_test_page');

// Test sayfası içeriği
function cnnturk_haber_botu_test_page() {
    if (isset($_POST['test_bot'])) {
        // Çalışma durumunu kontrol et
        $is_running = get_transient('cnnturk_bot_running');
        if (!$is_running) {
            // Çalışma durumunu işaretle (5 dakika için)
            set_transient('cnnturk_bot_running', true, 5 * MINUTE_IN_SECONDS);
            do_action('cnnturk_haber_botu_fetch_news');
            echo '<div class="notice notice-success"><p>Haber çekme işlemi başlatıldı! Yazılar sayfasını kontrol edin.</p></div>';
        } else {
            echo '<div class="notice notice-warning"><p>Bot zaten çalışıyor! Lütfen bekleyin.</p></div>';
        }
    }

    if (isset($_POST['clear_logs'])) {
        CNNTurk_Logger::clear_logs();
        echo '<div class="notice notice-success"><p>Log kayıtları başarıyla temizlendi!</p></div>';
    }
    ?>
    <div class="wrap">
        <h1>CNN Türk Haber Botu Test</h1>
        <form method="post" style="margin-bottom: 20px;">
            <p>Haberleri çekmek için butona tıklayın:</p>
            <input type="submit" name="test_bot" class="button button-primary" value="Haberleri Çek">
            <input type="submit" name="clear_logs" class="button button-secondary" value="Log Kayıtlarını Temizle" style="margin-left: 10px;">
        </form>
        
        <h2 style="margin-top: 20px;">Log Kayıtları</h2>
        <div style="background: #fff; padding: 15px; border: 1px solid #ccc; max-height: 400px; overflow-y: auto;">
            <?php
            $log_file = WP_CONTENT_DIR . '/cnnturk-bot-logs.txt';
            if (file_exists($log_file)) {
                echo '<pre>' . esc_html(file_get_contents($log_file)) . '</pre>';
            } else {
                echo 'Henüz log kaydı bulunmuyor.';
            }
            ?>
        </div>
    </div>
    <?php
} 