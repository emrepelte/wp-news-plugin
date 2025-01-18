<?php
if (!defined('ABSPATH')) {
    exit;
}

class CNNTurk_RateLimiter {
    private static $limit_key = 'cnnturk_rate_limit';
    private static $max_requests = 60; // Saatte maksimum istek
    private static $time_window = 3600; // 1 saat
    
    public static function can_make_request() {
        $current_count = get_transient(self::$limit_key) ?: 0;
        
        if ($current_count >= self::$max_requests) {
            CNNTurk_Logger::log('Rate limit aşıldı', 'warning');
            return false;
        }
        
        set_transient(self::$limit_key, $current_count + 1, self::$time_window);
        return true;
    }
    
    public static function reset_counter() {
        delete_transient(self::$limit_key);
    }
} 