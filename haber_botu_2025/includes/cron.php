<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('cnnturk_haber_botu_fetch_news', 'cnnturk_haber_botu_fetch_news');
function cnnturk_haber_botu_update_cron() {
    $interval = get_option('cnnturk_fetch_interval', 'hourly');

    if (!wp_next_scheduled('cnnturk_haber_botu_fetch_news')) {
        wp_schedule_event(time(), $interval, 'cnnturk_haber_botu_fetch_news');
    } else {
        wp_clear_scheduled_hook('cnnturk_haber_botu_fetch_news');
        wp_schedule_event(time(), $interval, 'cnnturk_haber_botu_fetch_news');
    }
}
add_action('update_option_cnnturk_fetch_interval', 'cnnturk_haber_botu_update_cron');

// Cron işini hemen çalıştırmak için
wp_schedule_single_event(time(), 'cnnturk_haber_botu_fetch_news');
