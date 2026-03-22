<?php
/**
 * VOYA — functions.php
 * Modern & Minimal WordPress Travel Blog Theme
 * Redesigned from: Wanderland | Same database structure
 * Audience: Vietnamese readers
 */

if (!defined('ABSPATH'))
    exit;


// ═══════════════════════════════════════════════════════════════
// 1. THEME SETUP
// ═══════════════════════════════════════════════════════════════
function vy_theme_setup()
{
    load_theme_textdomain('voya', get_template_directory() . '/languages');

    add_theme_support('automatic-feed-links');
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption']);
    add_theme_support('custom-logo', [
        'height' => 160,
        'width' => 320,
        'flex-height' => true,
        'flex-width' => true,
    ]);
    add_theme_support('customize-selective-refresh-widgets');
    add_theme_support('post-formats', ['video', 'audio', 'gallery', 'quote', 'link']);

    // Image sizes — giữ nguyên key để tương thích database cũ
    add_image_size('wl-hero', 1920, 900, false); // soft crop: không upscale ảnh nhỏ hơn 1920px
    add_image_size('wl-card', 800, 600, true);
    add_image_size('wl-card-sm', 600, 450, true);
    add_image_size('wl-thumb', 400, 300, true);
    add_image_size('wl-wide', 1280, 720, true);
    // Thêm mới cho theme Voya
    add_image_size('vy-square', 600, 600, true);
    add_image_size('vy-portrait', 600, 800, true);

    // Nav menus — đơn giản hoá từ 4 menu → 3 menu
    register_nav_menus([
        'primary' => __('Primary Navigation', 'voya'),   // Desktop nav chính (thay header-left + header-right)
        'mobile' => __('Mobile Menu', 'voya'),
        'footer' => __('Footer Menu', 'voya'),
    ]);
}
add_action('after_setup_theme', 'vy_theme_setup');


// ═══════════════════════════════════════════════════════════════
// 2. CONTENT WIDTH
// ═══════════════════════════════════════════════════════════════
function vy_content_width()
{
    $GLOBALS['content_width'] = apply_filters('vy_content_width', 1280);
}
add_action('after_setup_theme', 'vy_content_width', 0);


// ═══════════════════════════════════════════════════════════════
// 3. ENQUEUE ASSETS
// ═══════════════════════════════════════════════════════════════
function vy_enqueue_assets()
{
    $ver = '1.0.0';
    $uri = get_template_directory_uri();

    // ── Fonts ──
    // Plus Jakarta Sans: body font với hỗ trợ tiếng Việt tốt
    // Cormorant Garamond: heading sang trọng
    wp_enqueue_style(
        'vy-fonts',
        'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;1,300;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&family=Dancing+Script:wght@600&display=swap',
        [],
        null
    );

    // ── Icons: chỉ dùng Font Awesome 6 (bỏ Ionicons cũ) ──
    wp_enqueue_style(
        'font-awesome',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css',
        [],
        '6.5.0'
    );

    // ── Global + Layout ──
    wp_enqueue_style('vy-global', $uri . '/css/style.css', ['font-awesome'], $ver);
    wp_enqueue_style('vy-header', $uri . '/css/header.css', ['vy-global'], $ver);
    wp_enqueue_style('vy-footer', $uri . '/css/footer.css', ['vy-global'], $ver);

    wp_enqueue_script('vy-header', $uri . '/js/header.js', [], $ver, false); // in <head> với defer
    wp_enqueue_script('vy-footer', $uri . '/js/footer.js', [], $ver, true);

    // ── Page-specific ──
    if (is_front_page()) {
        wp_enqueue_style('vy-home', $uri . '/css/home.css', ['vy-global'], $ver);
        wp_enqueue_script('vy-home', $uri . '/js/home.js', [], $ver, true);
    }

    if (is_single()) {
        wp_enqueue_style('vy-single', $uri . '/css/single.css', ['vy-global'], $ver);
        wp_enqueue_script('vy-single', $uri . '/js/single.js', [], $ver, true);
    }

    if (is_archive() || (is_home() && !is_front_page())) {
        wp_enqueue_style('vy-archive', $uri . '/css/archive.css', ['vy-global'], $ver);
        wp_enqueue_script('vy-archive', $uri . '/js/archive.js', [], $ver, true);
    }

    if (is_page() && !is_front_page()) {
        wp_enqueue_style('vy-page', $uri . '/css/page.css', ['vy-global'], $ver);
    }

    if (is_search()) {
        wp_enqueue_style('vy-archive', $uri . '/css/archive.css', ['vy-global'], $ver);
        wp_enqueue_style('vy-search', $uri . '/css/search.css', ['vy-archive'], $ver);
        wp_enqueue_script('vy-search', $uri . '/js/search.js', [], $ver, true);
    }

    if (is_404()) {
        wp_enqueue_style('vy-404', $uri . '/css/404.css', ['vy-global'], $ver);
        wp_enqueue_script('vy-404', $uri . '/js/404.js', [], $ver, true);
    }

    if (is_page_template('page-cms.php')) {
        wp_enqueue_style('vy-cms', $uri . '/css/page-cms.css', ['vy-global'], $ver);
        wp_enqueue_script('vy-cms', $uri . '/js/page-cms.js', [], $ver, true);
    }

    if (is_page_template('page-contact.php')) {
        wp_enqueue_style('vy-cms', $uri . '/css/page-cms.css', ['vy-global'], $ver);
        wp_enqueue_style('vy-contact', $uri . '/css/page-contact.css', ['vy-cms'], $ver);
        wp_enqueue_script('vy-contact', $uri . '/js/page-contact.js', [], $ver, true);
    }
}
add_action('wp_enqueue_scripts', 'vy_enqueue_assets');

// Thêm defer cho header.js — đặt NGOÀI vy_enqueue_assets() để tránh add_filter bị gọi nhiều lần
add_filter('script_loader_tag', function ($tag, $handle) {
    if ($handle === 'vy-header') {
        return str_replace(' src=', ' defer src=', $tag);
    }
    return $tag;
}, 10, 2);


// ═══════════════════════════════════════════════════════════════
// 4. INCLUDES — Giữ nguyên inc/ để dùng chung database cũ
// ═══════════════════════════════════════════════════════════════
require_once get_template_directory() . '/inc/home-options.php';
require_once get_template_directory() . '/inc/functions-newsletter.php';
require_once get_template_directory() . '/inc/destinations-cpt.php';
require_once get_template_directory() . '/inc/cms-page-meta.php';
require_once get_template_directory() . '/inc/contact-page-meta.php';


// ═══════════════════════════════════════════════════════════════
// 5. CUSTOMIZER SETTINGS
// ═══════════════════════════════════════════════════════════════
function vy_customizer_settings($wp_customize)
{

    // ── Logo variants ──
    $logo_keys = ['vy_logo_dark', 'vy_logo_light', 'vy_logo_mobile', 'vy_logo_footer'];
    foreach ($logo_keys as $key) {
        $wp_customize->add_setting($key, [
            'default' => '',
            'sanitize_callback' => 'absint',
            'transport' => 'postMessage',
        ]);
    }
    $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize, 'vy_logo_dark', [
        'label' => __('Logo — Tối (sticky header / trang thường)', 'voya'),
        'section' => 'title_tagline',
        'mime_type' => 'image',
    ]));
    $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize, 'vy_logo_light', [
        'label' => __('Logo — Sáng (transparent header, trên hero)', 'voya'),
        'section' => 'title_tagline',
        'mime_type' => 'image',
    ]));
    $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize, 'vy_logo_mobile', [
        'label' => __('Logo — Mobile', 'voya'),
        'section' => 'title_tagline',
        'mime_type' => 'image',
    ]));

    // ── Contact info ──
    $wp_customize->add_section('vy_contact', [
        'title' => __('Thông tin liên hệ', 'voya'),
        'priority' => 30,
    ]);
    $wp_customize->add_setting('vy_phone', ['default' => '', 'sanitize_callback' => 'sanitize_text_field']);
    $wp_customize->add_setting('vy_email', ['default' => '', 'sanitize_callback' => 'sanitize_email']);
    $wp_customize->add_control('vy_phone', [
        'label' => __('Số điện thoại', 'voya'),
        'section' => 'vy_contact',
        'type' => 'text',
    ]);
    $wp_customize->add_control('vy_email', [
        'label' => __('Email', 'voya'),
        'section' => 'vy_contact',
        'type' => 'email',
    ]);

    // ── Social Links ──
    $wp_customize->add_section('vy_social', [
        'title' => __('Mạng xã hội', 'voya'),
        'priority' => 31,
    ]);
    // Thêm TikTok vào list (phổ biến với người Việt)
    foreach (['instagram', 'facebook', 'youtube', 'tiktok', 'twitter'] as $net) {
        $wp_customize->add_setting('vy_social_' . $net, [
            'default' => '',
            'sanitize_callback' => 'esc_url_raw',
        ]);
        $wp_customize->add_control('vy_social_' . $net, [
            'label' => ucfirst($net) . ' URL',
            'section' => 'vy_social',
            'type' => 'url',
        ]);
    }
    // Toggle hiện social trên header
    $wp_customize->add_setting('vy_header_show_social', [
        'default' => '1',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    $wp_customize->add_control('vy_header_show_social', [
        'label' => __('Hiện social icons trên header', 'voya'),
        'section' => 'vy_social',
        'type' => 'checkbox',
    ]);

    // ── Header Settings ──
    $wp_customize->add_section('vy_header_settings', [
        'title' => __('Header Settings', 'voya'),
        'priority' => 29,
    ]);
    $wp_customize->add_setting('vy_header_cta_text', [
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    $wp_customize->add_setting('vy_header_cta_url', [
        'default' => '',
        'sanitize_callback' => 'esc_url_raw',
    ]);
    $wp_customize->add_control('vy_header_cta_text', [
        'label' => __('CTA Button Text (để trống để ẩn)', 'voya'),
        'description' => __('Ví dụ: "Liên hệ", "Đặt tour"', 'voya'),
        'section' => 'vy_header_settings',
        'type' => 'text',
    ]);
    $wp_customize->add_control('vy_header_cta_url', [
        'label' => __('CTA Button URL', 'voya'),
        'section' => 'vy_header_settings',
        'type' => 'url',
    ]);

    // ── Footer Settings ──
    $wp_customize->add_section('vy_footer', [
        'title' => __('Footer', 'voya'),
        'priority' => 32,
    ]);
    $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize, 'vy_logo_footer', [
        'label' => __('Footer Logo', 'voya'),
        'section' => 'vy_footer',
        'mime_type' => 'image',
    ]));
    $wp_customize->add_setting('vy_footer_bio', [
        'default' => '',
        'sanitize_callback' => 'wp_kses_post',
    ]);
    $wp_customize->add_control('vy_footer_bio', [
        'label' => __('Giới thiệu ngắn (Footer)', 'voya'),
        'section' => 'vy_footer',
        'type' => 'textarea',
    ]);
    $wp_customize->add_setting('vy_copyright', [
        'default' => '© ' . gmdate('Y') . ' Voya. All rights reserved.',
        'sanitize_callback' => 'wp_kses_post',
    ]);
    $wp_customize->add_control('vy_copyright', [
        'label' => __('Copyright text', 'voya'),
        'section' => 'vy_footer',
        'type' => 'text',
    ]);
}
add_action('customize_register', 'vy_customizer_settings');


// ═══════════════════════════════════════════════════════════════
// 6. DESKTOP NAV WALKER
//    Bỏ hoàn toàn brush-stroke SVG → clean BEM markup
// ═══════════════════════════════════════════════════════════════
class VY_Nav_Walker extends Walker_Nav_Menu
{

    public function start_el(&$output, $item, $depth = 0, $args = null, $id = 0)
    {
        $classes = empty($item->classes) ? [] : (array) $item->classes;
        $has_children = $args->walker->has_children;
        $is_current = in_array('current-menu-item', $classes)
            || in_array('current-menu-ancestor', $classes);

        if ($has_children)
            $classes[] = 'has-dropdown';

        $class_str = join(' ', apply_filters('nav_menu_css_class', array_filter($classes), $item, $args));
        $output .= '<li class="' . esc_attr($class_str) . '">';

        $href = !empty($item->url) ? $item->url : '#';

        if ($depth === 0) {
            // Top-level link — brush stroke hover/active (port từ Wanderland)
            $output .= sprintf(
                '<a href="%s" class="vy-nav__link%s">',
                esc_url($href),
                $is_current ? ' is-active' : ''
            );
            // Brush stroke: 3 phần — left SVG cap + middle fill + right SVG cap
            // Reveal bằng clip-path animation từ trái sang phải
            $output .= '<span class="vy-nav__brush" aria-hidden="true">';
            $output .= '<svg class="vy-brush__left" viewBox="0 0 16 30" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M16 1C12 0 5 0 2 4C0 7 0 11 0 15C0 19 0 23 2 26C5 30 12 30 16 29Z"/></svg>';
            $output .= '<span class="vy-brush__mid"></span>';
            $output .= '<svg class="vy-brush__right" viewBox="0 0 16 30" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M0 1L0 29C4 30 11 30 14 26C16 23 16 19 16 15C16 11 16 7 14 4C11 0 4 0 0 1Z"/></svg>';
            $output .= '</span>';
            // Text + arrow nằm trên brush (z-index cao hơn)
            $output .= '<span class="vy-nav__inner">';
            $output .= '<span class="vy-nav__text">' . apply_filters('the_title', $item->title, $item->ID) . '</span>';
            if ($has_children) {
                $output .= '<i class="fa-solid fa-chevron-down vy-nav__arrow" aria-hidden="true"></i>';
            }
            $output .= '</span>';
            $output .= '</a>';
        } else {
            // Dropdown item
            $output .= sprintf(
                '<a href="%s" class="vy-dropdown__link%s">',
                esc_url($href),
                $is_current ? ' is-active' : ''
            );
            $output .= '<span>' . apply_filters('the_title', $item->title, $item->ID) . '</span>';
            $output .= '</a>';
        }
    }

    public function start_lvl(&$output, $depth = 0, $args = null)
    {
        $output .= '<div class="vy-dropdown" role="region"><ul class="vy-dropdown__list">';
    }

    public function end_lvl(&$output, $depth = 0, $args = null)
    {
        $output .= '</ul></div>';
    }
}


// ═══════════════════════════════════════════════════════════════
// 7. MOBILE NAV WALKER
//    Slide-in panel: dùng button toggle thay vì span arrow
// ═══════════════════════════════════════════════════════════════
class VY_Mobile_Walker extends Walker_Nav_Menu
{

    public function start_el(&$output, $item, $depth = 0, $args = null, $id = 0)
    {
        $classes = empty($item->classes) ? [] : (array) $item->classes;
        $has_children = in_array('menu-item-has-children', $classes);

        if ($has_children)
            $classes[] = 'has-sub';

        $class_str = join(' ', apply_filters('nav_menu_css_class', array_filter($classes), $item, $args, $depth));
        $output .= '<li class="' . esc_attr($class_str) . ' vy-mobile-item">';

        $href = !empty($item->url) ? $item->url : '#';
        $output .= '<a href="' . esc_url($href) . '" class="vy-mobile-link">';
        $output .= '<span>' . apply_filters('the_title', $item->title, $item->ID) . '</span>';
        $output .= '</a>';

        if ($has_children) {
            $output .= '<button class="vy-mobile-toggle" aria-label="' . esc_attr__('Mở submenu', 'voya') . '" aria-expanded="false">';
            $output .= '<i class="fa-solid fa-plus" aria-hidden="true"></i>';
            $output .= '</button>';
        }
    }

    public function start_lvl(&$output, $depth = 0, $args = null)
    {
        $output .= '<ul class="vy-mobile-sub">';
    }

    public function end_lvl(&$output, $depth = 0, $args = null)
    {
        $output .= '</ul>';
    }
}


// ═══════════════════════════════════════════════════════════════
// 8. MISC
// ═══════════════════════════════════════════════════════════════
add_filter('show_admin_bar', '__return_false');

// Sidebar
register_sidebar([
    'name' => __('Single Post Sidebar', 'voya'),
    'id' => 'single-sidebar',
    'before_widget' => '<div class="vy-sidebar-widget %2$s">',
    'after_widget' => '</div>',
    'before_title' => '<h5 class="vy-widget-title">',
    'after_title' => '</h5>',
]);

function vy_register_footer_widgets()
{
    $cols = [
        ['id' => 'footer-col-1', 'name' => 'Footer — Col 1 (About)'],
        ['id' => 'footer-col-2', 'name' => 'Footer — Col 2 (Newsletter)'],
        ['id' => 'footer-col-3', 'name' => 'Footer — Col 3 (Recent Posts)'],
        ['id' => 'footer-col-4', 'name' => 'Footer — Col 4 (Categories)'],
    ];
    foreach ($cols as $col) {
        register_sidebar([
            'name' => __($col['name'], 'voya'),
            'id' => $col['id'],
            'before_widget' => '<div class="vy-footer-widget %2$s">',
            'after_widget' => '</div>',
            'before_title' => '<h6 class="vy-footer-widget-title">',
            'after_title' => '</h6>',
        ]);
    }
}
add_action('widgets_init', 'vy_register_footer_widgets');


// ═══════════════════════════════════════════════════════════════
// 9. HELPER FUNCTIONS
// ═══════════════════════════════════════════════════════════════

/**
 * Render social links — dùng ở header, mobile menu, footer
 *
 * @param string $class  CSS class prefix cho từng <a>
 */
function vy_social_links($class = 'vy-social-link')
{
    $networks = [
        'instagram' => ['icon' => 'fa-instagram', 'label' => 'Instagram'],
        'facebook' => ['icon' => 'fa-facebook-f', 'label' => 'Facebook'],
        'youtube' => ['icon' => 'fa-youtube', 'label' => 'YouTube'],
        'tiktok' => ['icon' => 'fa-tiktok', 'label' => 'TikTok'],
        'twitter' => ['icon' => 'fa-x-twitter', 'label' => 'Twitter/X'],
    ];
    $output = '';
    foreach ($networks as $net => $data) {
        $url = get_theme_mod('vy_social_' . $net);
        if (!$url)
            continue;
        $output .= sprintf(
            '<a href="%s" class="%s" target="_blank" rel="noopener noreferrer" aria-label="%s"><i class="fa-brands %s"></i></a>',
            esc_url($url),
            esc_attr($class),
            esc_attr($data['label']),
            esc_attr($data['icon'])
        );
    }
    return $output;
}

/**
 * Kiểm tra trang có hero banner full-screen không
 * (header sẽ transparent trên những trang này)
 */
function vy_is_hero_page()
{
    return is_front_page()
        || is_single()
        || is_page_template('page-cms.php')
        || is_page_template('page-contact.php')
        || is_404();
}


// ═══════════════════════════════════════════════════════════════
// 10. AJAX — Load More Posts (dùng admin-ajax.php, chuẩn WP)
// ═══════════════════════════════════════════════════════════════

add_action('wp_ajax_vy_load_posts', 'vy_ajax_load_posts');
add_action('wp_ajax_nopriv_vy_load_posts', 'vy_ajax_load_posts');

function vy_ajax_load_posts()
{
    // Verify nonce
    check_ajax_referer('vy_load_posts_nonce', 'nonce');

    $paged = max(1, intval($_POST['paged'] ?? 1));
    $cat = intval($_POST['cat'] ?? 0);
    $year = intval($_POST['year'] ?? 0);
    $per_page = max(1, intval($_POST['per_page'] ?? 9));

    $sort_map = [
        'date_desc' => ['orderby' => 'date', 'order' => 'DESC'],
        'date_asc' => ['orderby' => 'date', 'order' => 'ASC'],
        'title_asc' => ['orderby' => 'title', 'order' => 'ASC'],
        'popular' => ['orderby' => 'comment_count', 'order' => 'DESC'],
    ];
    $sort_key = sanitize_key($_POST['sort'] ?? 'date_desc');
    $sort = $sort_map[$sort_key] ?? $sort_map['date_desc'];

    $args = [
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => $per_page,
        'paged' => $paged,
        'orderby' => $sort['orderby'],
        'order' => $sort['order'],
        'no_found_rows' => true,
    ];
    if ($cat)
        $args['cat'] = $cat;
    if ($year)
        $args['year'] = $year;

    $q = new WP_Query($args);

    if (!$q->have_posts()) {
        wp_send_json_success(['html' => '', 'has_more' => false]);
    }

    ob_start();
    foreach ($q->posts as $p) {
        vy_render_archive_card($p);
    }
    $html = ob_get_clean();
    wp_reset_postdata();

    wp_send_json_success([
        'html' => $html,
        'has_more' => false, // per-page đơn giản, JS tự track
    ]);
}

/**
 * Render 1 archive card — shared bởi archive.php và ajax handler
 * Phải accessible ở cả 2 context nên khai báo ở functions.php
 */
if (!function_exists('vy_render_archive_card')) {
    function vy_render_archive_card($post)
    {
        $img = get_the_post_thumbnail_url($post->ID, 'wl-card')
            ?: get_the_post_thumbnail_url($post->ID, 'full');
        $cats = get_the_category($post->ID);
        $cat = $cats[0] ?? null;
        $exc = get_post_field('post_excerpt', $post->ID)
            ?: wp_trim_words(get_post_field('post_content', $post->ID), 18, '…');
        $author = get_the_author_meta('display_name', $post->post_author);
        $date = get_the_date('j M, Y', $post->ID);
        $datetime = get_the_date('Y-m-d', $post->ID);
        $comments_n = (int) get_comments_number($post->ID);
        ?>
<article class="vy-archive-card" itemscope itemtype="https://schema.org/BlogPosting">
    <a class="vy-archive-card__img-link" href="<?php echo esc_url(get_permalink($post->ID)); ?>">
        <?php if ($img): ?>
        <img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($post->post_title); ?>" loading="lazy"
            class="vy-archive-card__img" itemprop="image">
        <?php else: ?>
        <div class="vy-archive-card__img-placeholder"></div>
        <?php endif; ?>
        <?php if ($cat): ?>
        <span class="vy-archive-card__cat"><?php echo esc_html($cat->name); ?></span>
        <?php endif; ?>
    </a>
    <div class="vy-archive-card__body">
        <div class="vy-archive-card__meta">
            <a href="<?php echo esc_url(get_author_posts_url($post->post_author)); ?>" class="vy-archive-card__author">
                <?php echo esc_html($author); ?>
            </a>
            <span aria-hidden="true">·</span>
            <time datetime="<?php echo esc_attr($datetime); ?>" itemprop="datePublished">
                <?php echo esc_html($date); ?>
            </time>
            <?php if ($comments_n > 0): ?>
            <span aria-hidden="true">·</span>
            <span class="vy-archive-card__comments">
                <i class="fa-regular fa-comment" aria-hidden="true"></i>
                <?php echo $comments_n; ?>
            </span>
            <?php endif; ?>
        </div>
        <h2 class="vy-archive-card__title" itemprop="headline">
            <a href="<?php echo esc_url(get_permalink($post->ID)); ?>">
                <?php echo esc_html($post->post_title); ?>
            </a>
        </h2>
        <p class="vy-archive-card__excerpt" itemprop="description">
            <?php echo esc_html(wp_trim_words($exc, 18, '…')); ?>
        </p>
        <a class="vy-archive-card__more" href="<?php echo esc_url(get_permalink($post->ID)); ?>">
            <?php esc_html_e('Đọc thêm', 'voya'); ?>
            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
        </a>
    </div>
</article>
<?php
    }
}


// ── Localize archive script với ajax_url + nonce ──────────────
function vy_localize_archive_script()
{
    // Áp dụng cho cả archive và home.php (blog index)
    if (!(is_archive() || (is_home() && !is_front_page())))
        return;
    wp_localize_script('vy-archive', 'VY_ARCHIVE', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('vy_load_posts_nonce'),
    ]);
}
add_action('wp_enqueue_scripts', 'vy_localize_archive_script', 20);