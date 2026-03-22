<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
    <?php wp_body_open(); ?>

    <?php
    // Chuẩn bị logo IDs — dùng ở cả desktop + mobile
    $logo_id = get_theme_mod('custom_logo');
    $logo_dark_id = get_theme_mod('vy_logo_dark');
    $logo_light_id = get_theme_mod('vy_logo_light');
    $logo_mobile_id = get_theme_mod('vy_logo_mobile');

    $logo_dark_url = $logo_dark_id ? wp_get_attachment_image_url($logo_dark_id, 'full')
        : ($logo_id ? wp_get_attachment_image_url($logo_id, 'full') : '');
    $logo_light_url = $logo_light_id ? wp_get_attachment_image_url($logo_light_id, 'full') : $logo_dark_url;
    $logo_mobile_url = $logo_mobile_id ? wp_get_attachment_image_url($logo_mobile_id, 'full') : $logo_dark_url;
    $site_name = get_bloginfo('name');
    ?>

    <!-- ============================================================
     MAIN HEADER
     Structure: Logo | Nav Center | Actions (social + search + hamburger)
     ============================================================ -->
    <?php
    // Thêm is-transparent ngay trong PHP — tránh FOUC (flash trắng trước khi JS chạy)
// vy-header-no-transition: tắt animation trong 1 frame đầu, JS sẽ remove sau
    $vy_header_class = 'vy-header vy-header-no-transition';
    if (vy_is_hero_page()) {
        $vy_header_class .= ' is-transparent';
    }
    ?>
    <header class="<?php echo esc_attr($vy_header_class); ?>" id="vy-header" role="banner">
        <div class="vy-header__inner">

            <!-- ── Logo ──────────────────────────────────────────── -->
            <div class="vy-header__logo">
                <a href="<?php echo esc_url(home_url('/')); ?>" itemprop="url"
                    aria-label="<?php echo esc_attr($site_name); ?> — Trang chủ">

                    <?php if ($logo_light_url): ?>
                    <img class="vy-logo vy-logo--light" src="<?php echo esc_url($logo_light_url); ?>"
                        alt="<?php echo esc_attr($site_name); ?>" width="160" height="52" loading="eager">
                    <?php endif; ?>

                    <?php if ($logo_dark_url): ?>
                    <img class="vy-logo vy-logo--dark" src="<?php echo esc_url($logo_dark_url); ?>"
                        alt="<?php echo esc_attr($site_name); ?>" width="160" height="52" loading="eager">
                    <?php else: ?>
                    <!-- Fallback: chữ tên blog khi chưa có logo -->
                    <span class="vy-logo-text"><?php echo esc_html($site_name); ?></span>
                    <?php endif; ?>

                </a>
            </div><!-- .vy-header__logo -->


            <!-- ── Primary Navigation ────────────────────────────── -->
            <nav class="vy-header__nav" aria-label="<?php esc_attr_e('Menu chính', 'voya'); ?>">
                <?php
                wp_nav_menu([
                    'theme_location' => 'primary',
                    'menu_class' => 'vy-nav',
                    'container' => false,
                    'depth' => 3,
                    'walker' => new VY_Nav_Walker(),
                    'fallback_cb' => false,
                ]);
                ?>
            </nav><!-- .vy-header__nav -->


            <!-- ── Header Actions ─────────────────────────────────── -->
            <div class="vy-header__actions">

                <!-- Social icons (hiển thị trên desktop, ẩn trên mobile) -->
                <?php if (get_theme_mod('vy_header_show_social', '1')):
                    $social_html = vy_social_links('vy-header__social-link');
                    if ($social_html): ?>
                <div class="vy-header__social" aria-label="<?php esc_attr_e('Mạng xã hội', 'voya'); ?>">
                    <?php echo $social_html; // Already escaped inside vy_social_links() ?>
                </div>
                <?php endif; endif; ?>

                <!-- Separator -->
                <span class="vy-header__divider" aria-hidden="true"></span>

                <!-- Search toggle -->
                <button class="vy-search-btn" id="vy-search-toggle"
                    aria-label="<?php esc_attr_e('Mở tìm kiếm', 'voya'); ?>" aria-expanded="false"
                    aria-controls="vy-search-bar">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                </button>

                <!-- Optional CTA button (set từ Customizer) -->
                <?php
                $cta_text = get_theme_mod('vy_header_cta_text', '');
                $cta_url = get_theme_mod('vy_header_cta_url', '');
                if ($cta_text && $cta_url): ?>
                <a href="<?php echo esc_url($cta_url); ?>" class="vy-btn vy-btn--primary vy-header__cta">
                    <?php echo esc_html($cta_text); ?>
                </a>
                <?php endif; ?>

                <!-- Hamburger — chỉ hiển thị trên mobile (CSS) -->
                <button class="vy-hamburger" id="vy-hamburger" aria-label="<?php esc_attr_e('Mở menu', 'voya'); ?>"
                    aria-expanded="false" aria-controls="vy-mobile-menu">
                    <span class="vy-hamburger__line vy-hamburger__line--1"></span>
                    <span class="vy-hamburger__line vy-hamburger__line--2"></span>
                    <span class="vy-hamburger__line vy-hamburger__line--3"></span>
                </button>

            </div><!-- .vy-header__actions -->

        </div><!-- .vy-header__inner -->

        <!-- ── Search Bar (expand xuống dưới header) ─────────────── -->
        <div class="vy-search-bar" id="vy-search-bar" role="search"
            aria-label="<?php esc_attr_e('Tìm kiếm', 'voya'); ?>" aria-hidden="true">
            <div class="vy-search-bar__inner">
                <form action="<?php echo esc_url(home_url('/')); ?>" method="get">
                    <label for="vy-search-input" class="sr-only">
                        <?php esc_html_e('Tìm kiếm', 'voya'); ?>
                    </label>
                    <input type="search" name="s" id="vy-search-input" class="vy-search-bar__input"
                        placeholder="<?php esc_attr_e('Tìm điểm đến, hành trình, mẹo du lịch…', 'voya'); ?>"
                        autocomplete="off" required>
                    <button type="submit" class="vy-search-bar__submit"
                        aria-label="<?php esc_attr_e('Tìm kiếm', 'voya'); ?>">
                        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                        <span class="sr-only"><?php esc_html_e('Tìm', 'voya'); ?></span>
                    </button>
                    <button type="button" class="vy-search-bar__close" id="vy-search-close"
                        aria-label="<?php esc_attr_e('Đóng tìm kiếm', 'voya'); ?>">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </form>
            </div>
        </div><!-- .vy-search-bar -->

    </header><!-- .vy-header -->


    <!-- ============================================================
     MOBILE MENU — Slide-in Panel từ phải
     Thay thế dropdown nav cũ bằng full overlay panel
     ============================================================ -->
    <div class="vy-mobile-menu" id="vy-mobile-menu" aria-hidden="true" aria-modal="true" role="dialog"
        aria-label="<?php esc_attr_e('Menu di động', 'voya'); ?>">

        <!-- Backdrop (click để đóng) -->
        <div class="vy-mobile-menu__backdrop" id="vy-mobile-backdrop" aria-hidden="true"></div>

        <!-- Panel -->
        <div class="vy-mobile-menu__panel">

            <!-- Panel Head: Logo + Close -->
            <div class="vy-mobile-menu__head">
                <a href="<?php echo esc_url(home_url('/')); ?>" class="vy-mobile-menu__logo"
                    aria-label="<?php echo esc_attr($site_name); ?> — Trang chủ">
                    <?php if ($logo_mobile_url): ?>
                    <img src="<?php echo esc_url($logo_mobile_url); ?>" alt="<?php echo esc_attr($site_name); ?>"
                        height="32" loading="lazy">
                    <?php else: ?>
                    <span class="vy-logo-text"><?php echo esc_html($site_name); ?></span>
                    <?php endif; ?>
                </a>

                <button class="vy-mobile-menu__close" id="vy-mobile-close"
                    aria-label="<?php esc_attr_e('Đóng menu', 'voya'); ?>">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </div><!-- .vy-mobile-menu__head -->

            <!-- Panel Nav -->
            <div class="vy-mobile-menu__nav">
                <?php
                // Ưu tiên menu 'mobile', fallback về 'primary'
                $mobile_location = has_nav_menu('mobile') ? 'mobile' : 'primary';
                wp_nav_menu([
                    'theme_location' => $mobile_location,
                    'menu_class' => 'vy-mobile-nav',
                    'container' => false,
                    'depth' => 3,
                    'walker' => new VY_Mobile_Walker(),
                    'fallback_cb' => false,
                ]);
                ?>
            </div><!-- .vy-mobile-menu__nav -->

            <!-- Panel Foot: Contact + Social -->
            <div class="vy-mobile-menu__foot">

                <?php
                $phone = get_theme_mod('vy_phone', '');
                $email = get_theme_mod('vy_email', '');
                if ($phone || $email): ?>
                <div class="vy-mobile-menu__contact">
                    <?php if ($phone): ?>
                    <a href="tel:<?php echo esc_attr(preg_replace('/\s+/', '', $phone)); ?>"
                        class="vy-mobile-menu__contact-item">
                        <i class="fa-solid fa-phone" aria-hidden="true"></i>
                        <span><?php echo esc_html($phone); ?></span>
                    </a>
                    <?php endif; ?>
                    <?php if ($email): ?>
                    <a href="mailto:<?php echo esc_attr($email); ?>" class="vy-mobile-menu__contact-item">
                        <i class="fa-regular fa-envelope" aria-hidden="true"></i>
                        <span><?php echo esc_html($email); ?></span>
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Social icons -->
                <?php $social = vy_social_links('vy-mobile-menu__social-link'); ?>
                <?php if ($social): ?>
                <div class="vy-mobile-menu__social" aria-label="<?php esc_attr_e('Mạng xã hội', 'voya'); ?>">
                    <?php echo $social; ?>
                </div>
                <?php endif; ?>

            </div><!-- .vy-mobile-menu__foot -->

        </div><!-- .vy-mobile-menu__panel -->
    </div><!-- .vy-mobile-menu -->