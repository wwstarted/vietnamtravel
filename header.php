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
    // ── Chuẩn bị dữ liệu ─────────────────────────────────────
    $logo_id = get_theme_mod('custom_logo');
    $logo_dark_id = get_theme_mod('vy_logo_dark');
    $logo_mobile_id = get_theme_mod('vy_logo_mobile');

    $logo_dark_url = $logo_dark_id
        ? wp_get_attachment_image_url($logo_dark_id, 'full')
        : ($logo_id ? wp_get_attachment_image_url($logo_id, 'full') : '');
    $logo_mobile_url = $logo_mobile_id
        ? wp_get_attachment_image_url($logo_mobile_id, 'full')
        : $logo_dark_url;

    $site_name = get_bloginfo('name');

    // Contact: dùng Customizer settings có sẵn (vy_phone, vy_email từ functions.php)
    $phone = get_theme_mod('vy_phone', '');
    $email = get_theme_mod('vy_email', '');
    ?>


    <!-- ============================================================
         TOP BAR — dark info strip (email + phone, centered)
         position: relative → in-flow, scrolls away naturally.
         Sticky header snaps to top automatically after this leaves viewport.
         ============================================================ -->
    <?php if ($email || $phone): ?>
    <div class="vy-topbar" id="vy-topbar">
        <div class="vy-topbar__inner">

            <!-- Nhóm trái: tagline + email + phone -->
            <div class="vy-topbar__contacts">

                <span class="vy-topbar__tagline">
                    <?php esc_html_e('S&#7861;n s&#224;ng cho m&#7897;t tr&#7843;i nghi&#7879;m &#273;&#7897;c &#273;&#225;o?', 'voya'); ?>
                </span>

                <?php if ($email): ?>
                <a href="mailto:<?php echo esc_attr($email); ?>" class="vy-topbar__contact">
                    <i class="fa-regular fa-envelope" aria-hidden="true"></i>
                    <span><?php echo esc_html($email); ?></span>
                </a>
                <?php endif; ?>

                <?php if ($phone): ?>
                <a href="tel:<?php echo esc_attr(preg_replace('/\s+/', '', $phone)); ?>" class="vy-topbar__contact">
                    <i class="fa-solid fa-phone" aria-hidden="true"></i>
                    <span><?php echo esc_html($phone); ?></span>
                </a>
                <?php endif; ?>

            </div><!-- .vy-topbar__contacts -->

        </div><!-- .vy-topbar__inner -->
    </div><!-- .vy-topbar -->
    <?php endif; ?>


    <!-- ============================================================
         MAIN HEADER — sticky white bar
         Layout: [Logo LEFT] ──────────────── [Nav · Search · CTA · Hamburger RIGHT]
         ============================================================ -->
    <header class="vy-header vy-header-no-transition" id="vy-header" role="banner">
        <div class="vy-header__inner">

            <!-- ── Logo ──────────────────────────────────────── -->
            <div class="vy-header__logo">
                <a href="<?php echo esc_url(home_url('/')); ?>" itemprop="url"
                    aria-label="<?php echo esc_attr($site_name); ?> — Trang chủ">

                    <?php if ($logo_dark_url): ?>
                    <img class="vy-logo" src="<?php echo esc_url($logo_dark_url); ?>"
                        alt="<?php echo esc_attr($site_name); ?>" width="160" height="52" loading="eager">
                    <?php else: ?>
                    <span class="vy-logo-text"><?php echo esc_html($site_name); ?></span>
                    <?php endif; ?>

                </a>
            </div><!-- .vy-header__logo -->


            <!-- ── Primary Navigation (pushed right via margin-left:auto in CSS) ── -->
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


            <!-- ── Actions: search · CTA · hamburger ─────────── -->
            <div class="vy-header__actions">

                <button class="vy-search-btn" id="vy-search-toggle"
                    aria-label="<?php esc_attr_e('Mở tìm kiếm', 'voya'); ?>" aria-expanded="false"
                    aria-controls="vy-search-modal">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                </button>

                <?php
                $cta_text = get_theme_mod('vy_header_cta_text', '');
                $cta_url = get_theme_mod('vy_header_cta_url', '');
                if ($cta_text && $cta_url): ?>
                <a href="<?php echo esc_url($cta_url); ?>" class="vy-btn vy-btn--primary vy-header__cta">
                    <?php echo esc_html($cta_text); ?>
                </a>
                <?php endif; ?>

                <button class="vy-hamburger" id="vy-hamburger" aria-label="<?php esc_attr_e('Mở menu', 'voya'); ?>"
                    aria-expanded="false" aria-controls="vy-mobile-menu">
                    <span class="vy-hamburger__line vy-hamburger__line--1"></span>
                    <span class="vy-hamburger__line vy-hamburger__line--2"></span>
                    <span class="vy-hamburger__line vy-hamburger__line--3"></span>
                </button>

            </div><!-- .vy-header__actions -->

        </div><!-- .vy-header__inner -->
    </header><!-- .vy-header -->


    <!-- ============================================================
         SEARCH MODAL — full-screen overlay, dead-center
         ============================================================ -->
    <div class="vy-search-modal" id="vy-search-modal" role="dialog"
        aria-label="<?php esc_attr_e('Tìm kiếm', 'voya'); ?>" aria-hidden="true">

        <div class="vy-search-modal__backdrop" id="vy-search-backdrop" aria-hidden="true"></div>

        <div class="vy-search-modal__wrap">
            <form action="<?php echo esc_url(home_url('/')); ?>" method="get" class="vy-search-modal__form">

                <label for="vy-search-input" class="sr-only">
                    <?php esc_html_e('Tìm kiếm', 'voya'); ?>
                </label>

                <div class="vy-search-modal__box">
                    <i class="fa-solid fa-magnifying-glass vy-search-modal__icon" aria-hidden="true"></i>

                    <input type="search" name="s" id="vy-search-input" class="vy-search-modal__input"
                        placeholder="<?php esc_attr_e('Tìm điểm đến, hành trình, mẹo du lịch…', 'voya'); ?>"
                        autocomplete="off">

                    <button type="button" class="vy-search-modal__close" id="vy-search-close"
                        aria-label="<?php esc_attr_e('Đóng tìm kiếm', 'voya'); ?>">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>

            </form>
        </div><!-- .vy-search-modal__wrap -->

    </div><!-- .vy-search-modal -->


    <!-- ============================================================
         MOBILE MENU — slide-in panel from right
         ============================================================ -->
    <div class="vy-mobile-menu" id="vy-mobile-menu" aria-hidden="true" aria-modal="true" role="dialog"
        aria-label="<?php esc_attr_e('Menu di động', 'voya'); ?>">

        <div class="vy-mobile-menu__backdrop" id="vy-mobile-backdrop" aria-hidden="true"></div>

        <div class="vy-mobile-menu__panel">

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
            </div>

            <div class="vy-mobile-menu__nav">
                <?php
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
            </div>

            <div class="vy-mobile-menu__foot">

                <?php if ($phone || $email): ?>
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

                <?php $social = vy_social_links('vy-mobile-menu__social-link'); ?>
                <?php if ($social): ?>
                <div class="vy-mobile-menu__social" aria-label="<?php esc_attr_e('Mạng xã hội', 'voya'); ?>">
                    <?php echo $social; ?>
                </div>
                <?php endif; ?>

            </div><!-- .vy-mobile-menu__foot -->

        </div><!-- .vy-mobile-menu__panel -->
    </div><!-- .vy-mobile-menu -->