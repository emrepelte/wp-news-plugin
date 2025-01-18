<?php
if (!defined('ABSPATH')) {
    exit;
}

class CNNTurk_Filter {
    public static function filter_content($content) {
        // Yasaklı kelimeleri al
        $banned_words = get_option('cnnturk_banned_words', []);
        
        // HTML etiketlerini temizle
        $content = wp_strip_all_tags($content);
        
        // Yasaklı kelimeleri kontrol et
        foreach ($banned_words as $word) {
            if (stripos($content, $word) !== false) {
                return false;
            }
        }
        
        // XSS koruması
        $content = wp_kses_post($content);
        
        return $content;
    }
    
    public static function should_fetch_category($category) {
        $allowed_categories = get_option('cnnturk_allowed_categories', []);
        return empty($allowed_categories) || in_array($category, $allowed_categories);
    }
} 