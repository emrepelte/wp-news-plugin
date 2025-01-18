/**
 * Plugin Name: CNN Türk Haber Botu
 * Plugin URI: https://github.com/emrepelte/wp-news-plugin
 * Description: CNN Türk'ten haberleri çekerek WordPress sitenizde otomatik olarak yayınlayan bot.
 * Version: 1.0.1
 * Author: Emre Pelte
 * Author URI: https://github.com/emrepelte
 * License: GPL2
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

// Eklenti sürüm numarası
define('CNNTURK_BOT_VERSION', '1.0.1');
define('CNNTURK_BOT_PLUGIN_FILE', __FILE__);
define('CNNTURK_BOT_PLUGIN_DIR', plugin_dir_path(__FILE__));

// Gerekli dosyaları dahil et
require_once CNNTURK_BOT_PLUGIN_DIR . 'includes/updater.php';
require_once CNNTURK_BOT_PLUGIN_DIR . 'includes/logger.php';
require_once CNNTURK_BOT_PLUGIN_DIR . 'includes/scraper.php';
require_once CNNTURK_BOT_PLUGIN_DIR . 'admin/settings.php';

// Güncelleme kontrolünü başlat
add_action('init', 'cnnturk_haber_botu_init');
function cnnturk_haber_botu_init() {
    // Güncelleme sınıfını başlat
    if (class_exists('CNNTurk_Updater')) {
        $updater_config = array(
            'slug' => 'wp-cnnturk-haber-botu',
            'proper_folder_name' => 'haber_botu_2025',
            'api_url' => 'https://api.github.com/repos/emrepelte/wp-news-plugin/releases',
            'raw_url' => 'https://raw.githubusercontent.com/emrepelte/wp-news-plugin/main',
            'github_url' => 'https://github.com/emrepelte/wp-news-plugin',
            'zip_url' => 'https://github.com/emrepelte/wp-news-plugin/releases/download/v1.0.1/haber_botu_2025.zip',
            'requires' => '5.0',
            'tested' => '6.4',
            'readme' => 'README.md'
        );
        new CNNTurk_Updater(CNNTURK_BOT_PLUGIN_FILE, $updater_config);
    }
}

// Aktivasyon ve deaktivasyon kancaları
register_activation_hook(CNNTURK_BOT_PLUGIN_FILE, 'cnnturk_haber_botu_activate');
register_deactivation_hook(CNNTURK_BOT_PLUGIN_FILE, 'cnnturk_haber_botu_deactivate');

// ... existing code ... 