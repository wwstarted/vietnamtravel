<?php
/**
 * WANDERLAND — Newsletter Section
 * Thêm vào cuối functions.php (sau require home-options.php)
 *
 * 1. Customizer settings cho Section 3
 * 2. Form submit handler (lưu email + gửi notification)
 */

if (!defined('ABSPATH'))
    exit;


// ── Customizer: Newsletter Section ────────────────────────────
add_action('customize_register', 'wl_newsletter_customizer');

function wl_newsletter_customizer($wp_customize)
{

    $wp_customize->add_section('wl_newsletter', array(
        'title' => __('Newsletter Section (Home)', 'wanderland'),
        'priority' => 35,
    ));

    // Decorative image
    $wp_customize->add_setting('wl_newsletter_image', array(
        'default' => '',
        'sanitize_callback' => 'absint',
    ));
    $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize, 'wl_newsletter_image', array(
        'label' => __('Ảnh trang trí (cột trái)', 'wanderland'),
        'section' => 'wl_newsletter',
        'mime_type' => 'image',
    )));

    // Tagline
    $wp_customize->add_setting('wl_newsletter_tagline', array(
        'default' => 'Lorem ipsum dolor',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control('wl_newsletter_tagline', array(
        'label' => __('Tagline', 'wanderland'),
        'section' => 'wl_newsletter',
        'type' => 'text',
    ));

    // Title
    $wp_customize->add_setting('wl_newsletter_title', array(
        'default' => 'Finding the perfect trails to hike is easy with',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control('wl_newsletter_title', array(
        'label' => __('Title (phần không highlight)', 'wanderland'),
        'section' => 'wl_newsletter',
        'type' => 'text',
    ));

    // Highlighted word
    $wp_customize->add_setting('wl_newsletter_highlight', array(
        'default' => 'newsletter',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control('wl_newsletter_highlight', array(
        'label' => __('Từ highlight (màu xanh)', 'wanderland'),
        'section' => 'wl_newsletter',
        'type' => 'text',
    ));

    // Body text
    $wp_customize->add_setting('wl_newsletter_text', array(
        'default' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididu nt ut labore et dolore minim veniam, quism.',
        'sanitize_callback' => 'sanitize_textarea_field',
    ));
    $wp_customize->add_control('wl_newsletter_text', array(
        'label' => __('Body text', 'wanderland'),
        'section' => 'wl_newsletter',
        'type' => 'textarea',
    ));

    // Notification email
    $wp_customize->add_setting('wl_newsletter_notify_email', array(
        'default' => get_option('admin_email'),
        'sanitize_callback' => 'sanitize_email',
    ));
    $wp_customize->add_control('wl_newsletter_notify_email', array(
        'label' => __('Email nhận thông báo subscriber', 'wanderland'),
        'description' => __('Để trống = dùng admin email', 'wanderland'),
        'section' => 'wl_newsletter',
        'type' => 'email',
    ));
}


// ── Form handler ──────────────────────────────────────────────
add_action('admin_post_wl_newsletter_subscribe', 'wl_handle_newsletter');
add_action('admin_post_nopriv_wl_newsletter_subscribe', 'wl_handle_newsletter');

function wl_handle_newsletter()
{

    // Verify nonce
    if (
        !isset($_POST['wl_nl_nonce'])
        || !wp_verify_nonce($_POST['wl_nl_nonce'], 'wl_newsletter_subscribe')
    ) {
        wp_die(__('Security check failed.', 'wanderland'));
    }

    $name = sanitize_text_field($_POST['nl_name'] ?? '');
    $email = sanitize_email($_POST['nl_email'] ?? '');
    $redirect = esc_url_raw($_POST['redirect_to'] ?? home_url('/'));

    if (!is_email($email)) {
        wp_redirect(add_query_arg('nl', 'error', $redirect));
        exit;
    }

    // Save subscriber to options (simple list — no plugin needed)
    $subscribers = get_option('wl_newsletter_subscribers', array());
    $already = false;
    foreach ($subscribers as $s) {
        if ($s['email'] === $email) {
            $already = true;
            break;
        }
    }

    if (!$already) {
        $subscribers[] = array(
            'name' => $name,
            'email' => $email,
            'date' => current_time('mysql'),
        );
        update_option('wl_newsletter_subscribers', $subscribers);

        // Send notification
        $notify_to = get_theme_mod('wl_newsletter_notify_email', get_option('admin_email'));
        $subject = sprintf(__('[%s] New newsletter subscriber', 'wanderland'), get_bloginfo('name'));
        $body = sprintf(
            "Name: %s\nEmail: %s\nDate: %s",
            $name,
            $email,
            current_time('mysql')
        );
        wp_mail($notify_to, $subject, $body);
    }

    wp_redirect(add_query_arg('nl', 'success', $redirect));
    exit;
}


// ── Customizer: Featured Posts count ─────────────────────────
add_action('customize_register', 'wl_featured_posts_customizer');

function wl_featured_posts_customizer($wp_customize)
{

    $wp_customize->add_section('wl_featured_posts', array(
        'title' => __('Featured Posts Slider (Home)', 'wanderland'),
        'priority' => 36,
    ));

    $wp_customize->add_setting('wl_featured_posts_count', array(
        'default' => 6,
        'sanitize_callback' => 'absint',
        'transport' => 'refresh',
    ));

    $wp_customize->add_control('wl_featured_posts_count', array(
        'label' => __('Số bài hiển thị trong slider', 'wanderland'),
        'description' => __('Tối thiểu 3, tối đa 12. Slider hiện 3 bài mỗi lần.', 'wanderland'),
        'section' => 'wl_featured_posts',
        'type' => 'number',
        'input_attrs' => array(
            'min' => 3,
            'max' => 12,
            'step' => 1,
        ),
    ));
}


// ── Customizer: Clients / Partners Logo Grid ─────────────────
add_action('customize_register', 'wl_clients_customizer');

function wl_clients_customizer($wp_customize)
{

    $wp_customize->add_section('wl_clients', array(
        'title' => __('Partners / Clients (Hero bottom)', 'wanderland'),
        'description' => __('5 logo hiển thị ngay dưới hero banner. Mỗi logo có ảnh mặc định + ảnh hover.', 'wanderland'),
        'priority' => 34,
    ));

    for ($i = 1; $i <= 5; $i++) {

        // Image default
        $wp_customize->add_setting('wl_client_' . $i . '_image', array(
            'default' => '',
            'sanitize_callback' => 'absint',
        ));
        $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize, 'wl_client_' . $i . '_image', array(
            'label' => sprintf(__('Logo %d — ảnh mặc định', 'wanderland'), $i),
            'section' => 'wl_clients',
            'mime_type' => 'image',
        )));

        // Image hover
        $wp_customize->add_setting('wl_client_' . $i . '_hover', array(
            'default' => '',
            'sanitize_callback' => 'absint',
        ));
        $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize, 'wl_client_' . $i . '_hover', array(
            'label' => sprintf(__('Logo %d — ảnh hover', 'wanderland'), $i),
            'section' => 'wl_clients',
            'mime_type' => 'image',
        )));

        // URL
        $wp_customize->add_setting('wl_client_' . $i . '_url', array(
            'default' => '#',
            'sanitize_callback' => 'esc_url_raw',
        ));
        $wp_customize->add_control('wl_client_' . $i . '_url', array(
            'label' => sprintf(__('Logo %d — link URL', 'wanderland'), $i),
            'section' => 'wl_clients',
            'type' => 'url',
        ));

        // Alt text
        $wp_customize->add_setting('wl_client_' . $i . '_alt', array(
            'default' => 'Partner ' . $i,
            'sanitize_callback' => 'sanitize_text_field',
        ));
        $wp_customize->add_control('wl_client_' . $i . '_alt', array(
            'label' => sprintf(__('Logo %d — tên / alt text', 'wanderland'), $i),
            'section' => 'wl_clients',
            'type' => 'text',
        ));
    }
}