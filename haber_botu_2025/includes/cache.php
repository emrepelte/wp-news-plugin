<?php
if (!defined('ABSPATH')) {
    exit;
}

class CNNTurk_Cache {
    private static $cache_time = 3600; // 1 saat
    
    public static function set($key, $data) {
        set_transient('cnnturk_bot_' . $key, $data, self::$cache_time);
    }
    
    public static function get($key) {
        return get_transient('cnnturk_bot_' . $key);
    }
    
    public static function delete($key) {
        delete_transient('cnnturk_bot_' . $key);
    }
} 