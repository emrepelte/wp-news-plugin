<?php
if (!defined('ABSPATH')) {
    exit;
}

function cnnturk_haber_botu_is_duplicate($title) {
    global $wpdb;
    $query = $wpdb->prepare("SELECT COUNT(*) FROM $wpdb->posts WHERE post_title = %s", $title);
    $count = $wpdb->get_var($query);
    return $count > 0;
}

function cnnturk_haber_botu_insert_news($title, $content, $image, $source) {
    $category_id = get_option('cnnturk_category_id', 1);
    $post_status = get_option('cnnturk_post_status', 'publish');
    
    $post_data = [
        'post_title'    => $title,
        'post_content'  => $content . "<br><br><a href='{$source}' target='_blank'>Kaynak: CNN Türk</a>",
        'post_status'   => $post_status,
        'post_author'   => 1,
        'post_category' => [$category_id],
    ];

    $post_id = wp_insert_post($post_data);

    if ($post_id && $image) {
        cnnturk_haber_botu_set_featured_image($post_id, $image);
    }
}

function cnnturk_haber_botu_set_featured_image($post_id, $image_url) {
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $tmp = download_url($image_url);
    $file_array = [
        'name'     => basename($image_url),
        'tmp_name' => $tmp,
    ];

    $id = media_handle_sideload($file_array, $post_id);

    if (!is_wp_error($id)) {
        set_post_thumbnail($post_id, $id);
    }
}
