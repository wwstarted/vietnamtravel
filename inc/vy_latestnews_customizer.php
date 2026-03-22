<?php
/**
 * VOYA — Latest News Section Customizer
 * Thêm vào cuối inc/home-options.php (hoặc paste vào functions.php)
 *
 * Controls:
 *  - Label nhỏ
 *  - Tiêu đề chính + phần in nghiêng
 *
 * Data (4 bài): Admin → Home Sections → Latest Posts Grid (tab dcl_posts)
 */

add_action('customize_register', 'vy_latestnews_customizer');

function vy_latestnews_customizer($wp_customize)
{
    $wp_customize->add_section('vy_latestnews', [
        'title' => __('Latest News Grid (Home S7)', 'voya'),
        'description' => __('Grid tin tức mới nhất cuối trang. Chọn 4 bài tại Admin → Home Sections → Latest Posts Grid.', 'voya'),
        'priority' => 39,
    ]);

    $wp_customize->add_setting('vy_latestnews_label', [
        'default' => 'Cập nhật mới nhất',
        'sanitize_callback' => 'sanitize_text_field',
        'transport' => 'postMessage',
    ]);
    $wp_customize->add_control('vy_latestnews_label', [
        'label' => __('Label nhỏ phía trên tiêu đề', 'voya'),
        'section' => 'vy_latestnews',
        'type' => 'text',
    ]);

    $wp_customize->add_setting('vy_latestnews_title', [
        'default' => 'Tin tức ',
        'sanitize_callback' => 'sanitize_text_field',
        'transport' => 'postMessage',
    ]);
    $wp_customize->add_control('vy_latestnews_title', [
        'label' => __('Tiêu đề (phần thường)', 'voya'),
        'section' => 'vy_latestnews',
        'type' => 'text',
    ]);

    $wp_customize->add_setting('vy_latestnews_title_em', [
        'default' => 'nổi bật',
        'sanitize_callback' => 'sanitize_text_field',
        'transport' => 'postMessage',
    ]);
    $wp_customize->add_control('vy_latestnews_title_em', [
        'label' => __('Tiêu đề (phần in nghiêng màu accent)', 'voya'),
        'description' => __('Để trống nếu không cần.', 'voya'),
        'section' => 'vy_latestnews',
        'type' => 'text',
    ]);
}