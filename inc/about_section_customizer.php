<?php
/* ============================================================
   VOYA — About Section Customizer Settings
   File: paste vào cuối functions.php
   (hoặc require_once 1 file riêng trong inc/)

   Các setting có thể chỉnh trong:
   WP Admin → Appearance → Customize → "About Section (Home)"
   ============================================================ */

add_action('customize_register', 'vy_about_section_customizer');

function vy_about_section_customizer($wp_customize)
{

    /* ── Section ── */
    $wp_customize->add_section('vy_about_section', [
        'title' => __('About Section (Home)', 'voya'),
        'description' => __('Phần giới thiệu ngắn + 3 tính năng nổi bật, hiển thị ngay dưới Hero.', 'voya'),
        'priority' => 33,
    ]);


    /* ── LEFT COL: Label ── */
    $wp_customize->add_setting('vy_about_label', [
        'default' => 'Tại sao chọn chúng tôi',
        'sanitize_callback' => 'sanitize_text_field',
        'transport' => 'postMessage',
    ]);
    $wp_customize->add_control('vy_about_label', [
        'label' => __('Label nhỏ phía trên tiêu đề', 'voya'),
        'section' => 'vy_about_section',
        'type' => 'text',
    ]);


    /* ── LEFT COL: Title ── */
    $wp_customize->add_setting('vy_about_title', [
        'default' => 'Lựa chọn tốt nhất <em>cho hành trình của bạn</em>',
        'sanitize_callback' => 'wp_kses_post',   // cho phép <em> italic
        'transport' => 'postMessage',
    ]);
    $wp_customize->add_control('vy_about_title', [
        'label' => __('Tiêu đề chính (cho phép <em> để in nghiêng)', 'voya'),
        'section' => 'vy_about_section',
        'type' => 'text',
    ]);


    /* ── LEFT COL: Description ── */
    $wp_customize->add_setting('vy_about_desc', [
        'default' => 'Đến đúng nơi để lên kế hoạch cho những trải nghiệm du lịch tuyệt vời — lưu trú tiện nghi, hướng dẫn viên địa phương thân thiện và dịch vụ hoàn hảo.',
        'sanitize_callback' => 'sanitize_textarea_field',
        'transport' => 'postMessage',
    ]);
    $wp_customize->add_control('vy_about_desc', [
        'label' => __('Mô tả ngắn', 'voya'),
        'section' => 'vy_about_section',
        'type' => 'textarea',
    ]);


    /* ── LEFT COL: Optional CTA ── */
    $wp_customize->add_setting('vy_about_cta_text', [
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    $wp_customize->add_control('vy_about_cta_text', [
        'label' => __('CTA Button Text (để trống để ẩn)', 'voya'),
        'description' => __('Ví dụ: "Tìm hiểu thêm", "Về chúng tôi"', 'voya'),
        'section' => 'vy_about_section',
        'type' => 'text',
    ]);

    $wp_customize->add_setting('vy_about_cta_url', [
        'default' => '',
        'sanitize_callback' => 'esc_url_raw',
    ]);
    $wp_customize->add_control('vy_about_cta_url', [
        'label' => __('CTA Button URL', 'voya'),
        'section' => 'vy_about_section',
        'type' => 'url',
    ]);


    /* ── RIGHT COL: 3 Feature Cards ── */
    $card_defaults = [
        1 => [
            'title' => 'Dịch vụ đáng tin cậy!',
            'desc' => '99% khách hàng hài lòng. Đội ngũ chăm sóc từng dịch vụ trong suốt hành trình.',
            'fa' => 'fa-shield-halved',
        ],
        2 => [
            'title' => 'Giá trị tốt nhất!',
            'desc' => 'Là công ty lữ hành địa phương, chúng tôi có mức giá ưu đãi nhất từ nhà cung cấp.',
            'fa' => 'fa-tag',
        ],
        3 => [
            'title' => 'Tour thiết kế riêng!',
            'desc' => 'Chuyên gia du lịch địa phương có thể tùy chỉnh mọi yêu cầu của bạn.',
            'fa' => 'fa-map',
        ],
    ];

    for ($i = 1; $i <= 3; $i++) {
        $prefix = 'vy_about_card_' . $i;

        /* Card icon (ảnh upload) */
        $wp_customize->add_setting($prefix . '_icon', [
            'default' => '',
            'sanitize_callback' => 'absint',
        ]);
        $wp_customize->add_control(
            new WP_Customize_Media_Control($wp_customize, $prefix . '_icon', [
                'label' => sprintf(__('Card %d — Icon (ảnh upload, ưu tiên)', 'voya'), $i),
                'section' => 'vy_about_section',
                'mime_type' => 'image',
            ])
        );

        /* Card icon fallback FA */
        $wp_customize->add_setting($prefix . '_icon_fa', [
            'default' => $card_defaults[$i]['fa'],
            'sanitize_callback' => 'sanitize_text_field',
        ]);
        $wp_customize->add_control($prefix . '_icon_fa', [
            'label' => sprintf(__('Card %d — FA Icon class (fallback khi chưa upload ảnh)', 'voya'), $i),
            'description' => __('Ví dụ: fa-compass, fa-shield-halved, fa-map, fa-star', 'voya'),
            'section' => 'vy_about_section',
            'type' => 'text',
        ]);

        /* Card title */
        $wp_customize->add_setting($prefix . '_title', [
            'default' => $card_defaults[$i]['title'],
            'sanitize_callback' => 'sanitize_text_field',
            'transport' => 'postMessage',
        ]);
        $wp_customize->add_control($prefix . '_title', [
            'label' => sprintf(__('Card %d — Tiêu đề', 'voya'), $i),
            'section' => 'vy_about_section',
            'type' => 'text',
        ]);

        /* Card description */
        $wp_customize->add_setting($prefix . '_desc', [
            'default' => $card_defaults[$i]['desc'],
            'sanitize_callback' => 'sanitize_textarea_field',
            'transport' => 'postMessage',
        ]);
        $wp_customize->add_control($prefix . '_desc', [
            'label' => sprintf(__('Card %d — Mô tả', 'voya'), $i),
            'section' => 'vy_about_section',
            'type' => 'textarea',
        ]);
    }
}