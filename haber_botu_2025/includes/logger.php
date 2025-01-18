<?php
if (!defined('ABSPATH')) {
    exit;
}

class CNNTurk_Logger {
    private static $log_file;
    
    public static function init() {
        self::$log_file = WP_CONTENT_DIR . '/cnnturk-bot-logs.txt';
    }
    
    public static function log($message, $type = 'info') {
        if (!self::$log_file) {
            self::init();
        }
        
        $timestamp = current_time('Y-m-d H:i:s');
        $log_entry = sprintf("[%s] [%s]: %s\n", $timestamp, strtoupper($type), $message);
        
        error_log($log_entry, 3, self::$log_file);
    }
    
    public static function clear_logs() {
        if (!self::$log_file) {
            self::init();
        }
        
        if (file_exists(self::$log_file)) {
            unlink(self::$log_file);
        }
    }
} 