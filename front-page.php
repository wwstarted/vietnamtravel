<?php
/**
 * VOYA — front-page.php
 * Homepage — 6 sections
 *
 * Fix/Optimize so với version trước:
 *  - S3 Newsletter: sanitize $_GET['nl'] trước khi dùng
 *  - S1 Hero: dùng esc_url thay vì esc_url cho style inline → giữ nguyên (đúng)
 *  - S5 Destinations: wp_reset_postdata() đặt sau loop (đã đúng, confirm)
 *  - S6 Explore cats: fallback category query bỏ exclude uncategorized (vì ID có thể khác)
 *  - General: thêm wp_reset_postdata() safety ở cuối mỗi query block
 */

get_header();
?>

<main id="vy-main" class="vy-main-content">


    <!-- ============================================================
         S1 — HERO SLIDER
         full-viewport | content bottom-left | controls bottom-right
    ============================================================ -->
    <?php
    $hero_query = wl_get_section_query('hero_slider');
    $slide_count = ($hero_query && $hero_query->have_posts()) ? $hero_query->post_count : 0;
    ?>

    <?php if ($slide_count > 0): ?>

    <section class="vy-hero" data-hero aria-label="<?php esc_attr_e('Bài viết nổi bật', 'voya'); ?>"
        data-total="<?php echo esc_attr($slide_count); ?>">

        <div class="vy-hero__track" role="list">

            <?php
                $si = 0;
                while ($hero_query->have_posts()):
                    $hero_query->the_post();
                    $hero_thumb_id = get_post_thumbnail_id();
                    $excerpt = get_the_excerpt() ?: wp_trim_words(get_the_content(), 22, '…');
                    $cats = get_the_category();
                    $cat = $cats[0] ?? null;
                    $is_first = $si === 0;
                    $tab_idx = $is_first ? '0' : '-1';

                    // wp_get_attachment_image() tự generate srcset + sizes
                    // Browser chọn đúng size → không bị upscale → không blur
                    $hero_img_html = '';
                    if ($hero_thumb_id) {
                        $img_attrs = [
                            'class' => 'vy-hero__img',
                            'alt' => esc_attr(get_the_title()),
                            'loading' => $is_first ? 'eager' : 'lazy',
                            'decoding' => $is_first ? 'sync' : 'async',
                            'fetchpriority' => $is_first ? 'high' : 'auto',
                            'sizes' => '100vw',
                        ];
                        $hero_img_html = wp_get_attachment_image($hero_thumb_id, 'wl-hero', false, $img_attrs)
                            ?: wp_get_attachment_image($hero_thumb_id, 'full', false, $img_attrs);
                    }
                    ?>

            <div class="vy-hero__slide<?php echo $is_first ? ' is-active' : ''; ?>" role="listitem"
                aria-hidden="<?php echo $is_first ? 'false' : 'true'; ?>" data-index="<?php echo esc_attr($si); ?>">

                <?php if ($hero_img_html): ?>
                <?php echo $hero_img_html; ?>
                <?php else: ?>
                <div class="vy-hero__img-placeholder"></div>
                <?php endif; ?>

                <div class="vy-hero__overlay" aria-hidden="true"></div>

                <div class="vy-hero__content-wrap">
                    <div class="vy-container--wide">
                        <div class="vy-hero__content">

                            <?php if ($cat): ?>
                            <a href="<?php echo esc_url(get_category_link($cat->term_id)); ?>" class="vy-hero__cat"
                                tabindex="<?php echo $tab_idx; ?>">
                                <?php echo esc_html($cat->name); ?>
                            </a>
                            <?php endif; ?>

                            <h2 class="vy-hero__title">
                                <a href="<?php the_permalink(); ?>" tabindex="<?php echo $tab_idx; ?>">
                                    <?php the_title(); ?>
                                </a>
                            </h2>

                            <p class="vy-hero__excerpt">
                                <?php echo esc_html(wp_trim_words($excerpt, 22, '…')); ?>
                            </p>

                            <div class="vy-hero__meta">
                                <time datetime="<?php echo esc_attr(get_the_date('Y-m-d')); ?>" class="vy-hero__date">
                                    <?php echo esc_html(get_the_date('j M, Y')); ?>
                                </time>
                                <span class="vy-hero__meta-dot" aria-hidden="true"></span>
                                <a href="<?php echo esc_url(get_author_posts_url(get_the_author_meta('ID'))); ?>"
                                    class="vy-hero__author" tabindex="<?php echo $tab_idx; ?>">
                                    <?php echo esc_html(get_the_author()); ?>
                                </a>
                            </div>

                            <a href="<?php the_permalink(); ?>" class="vy-btn vy-btn--ghost-white vy-hero__cta"
                                tabindex="<?php echo $tab_idx; ?>">
                                <?php esc_html_e('Đọc bài viết', 'voya'); ?>
                                <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                            </a>

                        </div>
                    </div>
                </div>

            </div><!-- .vy-hero__slide -->

            <?php $si++; endwhile;
                wp_reset_postdata(); ?>

        </div><!-- .vy-hero__track -->

        <!-- Controls: counter + arrows -->
        <div class="vy-hero__controls">
            <div class="vy-hero__counter" aria-live="polite" aria-atomic="true">
                <span class="vy-hero__counter-cur">01</span>
                <span class="vy-hero__counter-line" aria-hidden="true"></span>
                <span class="vy-hero__counter-total">
                    <?php echo esc_html(str_pad($slide_count, 2, '0', STR_PAD_LEFT)); ?>
                </span>
            </div>
            <div class="vy-hero__arrows">
                <button class="vy-hero__arrow vy-hero__arrow--prev" type="button"
                    aria-label="<?php esc_attr_e('Slide trước', 'voya'); ?>">
                    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                </button>
                <button class="vy-hero__arrow vy-hero__arrow--next" type="button"
                    aria-label="<?php esc_attr_e('Slide tiếp theo', 'voya'); ?>">
                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </button>
            </div>
        </div>

        <!-- Dots (a11y) -->
        <div class="vy-hero__dots" role="tablist" aria-label="<?php esc_attr_e('Chọn slide', 'voya'); ?>">
            <?php for ($d = 0; $d < $slide_count; $d++): ?>
            <button class="vy-hero__dot <?php echo $d === 0 ? 'is-active' : ''; ?>" role="tab"
                aria-selected="<?php echo $d === 0 ? 'true' : 'false'; ?>"
                aria-label="<?php printf(esc_attr__('Slide %d', 'voya'), $d + 1); ?>"
                data-index="<?php echo esc_attr($d); ?>" type="button"></button>
            <?php endfor; ?>
        </div>

        <!-- Progress bar -->
        <div class="vy-hero__progress" aria-hidden="true">
            <div class="vy-hero__progress-bar"></div>
        </div>

        <!-- Scroll hint -->
        <div class="vy-hero__scroll-hint" aria-hidden="true">
            <span class="vy-hero__scroll-line"></span>
        </div>

    </section><!-- .vy-hero -->

    <?php else: ?>
    <section class="vy-hero vy-hero--empty">
        <?php if (current_user_can('edit_theme_options')): ?>
        <div class="vy-hero__empty-msg">
            <p><?php esc_html_e('Chưa có bài viết cho Hero Slider.', 'voya'); ?>
                <a href="<?php echo esc_url(admin_url('themes.php?page=wl-home-sections')); ?>">
                    <?php esc_html_e('Cài đặt →', 'voya'); ?>
                </a>
            </p>
        </div>
        <?php endif; ?>
    </section>
    <?php endif; ?>


    <!-- ============================================================
     S1b — ABOUT / FEATURES SECTION
     Layout: [Label + Title + Desc] | [3 Feature Cards]
============================================================ -->
    <?php
    // Lấy data từ Customizer — defaults có sẵn nếu chưa set
    $about_label = get_theme_mod('vy_about_label', __('Tại sao chọn chúng tôi', 'voya'));
    $about_title = get_theme_mod('vy_about_title', __('Lựa chọn tốt nhất cho hành trình của bạn', 'voya'));
    $about_desc = get_theme_mod('vy_about_desc', __('Đến đúng nơi để lên kế hoạch cho những trải nghiệm du lịch tuyệt vời — lưu trú tiện nghi, hướng dẫn viên địa phương thân thiện và dịch vụ hoàn hảo.', 'voya'));
    $about_cta_text = get_theme_mod('vy_about_cta_text', '');
    $about_cta_url = get_theme_mod('vy_about_cta_url', '');

    // 3 Feature cards
    $cards = [];
    for ($i = 1; $i <= 3; $i++) {
        $icon_id = get_theme_mod('vy_about_card_' . $i . '_icon');
        $cards[] = [
            'icon_url' => $icon_id ? wp_get_attachment_image_url($icon_id, 'thumbnail') : '',
            'icon_fa' => get_theme_mod('vy_about_card_' . $i . '_icon_fa', 'fa-compass'),
            'title' => get_theme_mod('vy_about_card_' . $i . '_title', ''),
            'desc' => get_theme_mod('vy_about_card_' . $i . '_desc', ''),
        ];
    }

    // Defaults cho từng card nếu chưa có data
    $card_defaults = [
        1 => [
            'icon_fa' => 'fa-shield-halved',
            'title' => __('Dịch vụ đáng tin cậy', 'voya'),
            'desc' => __('99% khách hàng hài lòng. Đội ngũ của chúng tôi chăm sóc từng dịch vụ trong suốt hành trình.', 'voya'),
        ],
        2 => [
            'icon_fa' => 'fa-tag',
            'title' => __('Giá trị tốt nhất', 'voya'),
            'desc' => __('Là công ty lữ hành địa phương với lượng khách lớn, chúng tôi có mức giá tốt nhất từ nhà cung cấp.', 'voya'),
        ],
        3 => [
            'icon_fa' => 'fa-map',
            'title' => __('Tour thiết kế riêng', 'voya'),
            'desc' => __('Chuyên gia du lịch địa phương có thể tùy chỉnh mọi yêu cầu của bạn một cách linh hoạt nhất.', 'voya'),
        ],
    ];

    foreach ($cards as $ci => $card) {
        $n = $ci + 1;
        if (empty($card['title']))
            $cards[$ci]['title'] = $card_defaults[$n]['title'];
        if (empty($card['desc']))
            $cards[$ci]['desc'] = $card_defaults[$n]['desc'];
        if (empty($card['icon_url']))
            $cards[$ci]['icon_fa'] = $card_defaults[$n]['icon_fa'];
    }
    ?>

    <section class="vy-about" aria-label="<?php esc_attr_e('Về chúng tôi', 'voya'); ?>">
        <div class="vy-container">
            <div class="vy-about__inner">

                <!-- ── LEFT: Label + Title + Desc + optional CTA ── -->
                <div class="vy-about__left">

                    <?php if ($about_label): ?>
                    <span class="vy-about__label"><?php echo esc_html($about_label); ?></span>
                    <?php endif; ?>

                    <h2 class="vy-about__title">
                        <?php echo wp_kses_post($about_title); ?>
                    </h2>

                    <?php if ($about_desc): ?>
                    <p class="vy-about__desc"><?php echo esc_html($about_desc); ?></p>
                    <?php endif; ?>

                    <?php if ($about_cta_text && $about_cta_url): ?>
                    <a href="<?php echo esc_url($about_cta_url); ?>" class="vy-about__cta">
                        <?php echo esc_html($about_cta_text); ?>
                        <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                    </a>
                    <?php endif; ?>

                </div><!-- .vy-about__left -->


                <!-- ── RIGHT: 3 Feature Cards ── -->
                <div class="vy-about__right">
                    <div class="vy-about__cards">

                        <?php foreach ($cards as $card): ?>
                        <div class="vy-about__card">

                            <!-- Icon: ưu tiên ảnh upload, fallback FA icon -->
                            <div class="vy-about__card-icon" aria-hidden="true">
                                <?php if ($card['icon_url']): ?>
                                <img src="<?php echo esc_url($card['icon_url']); ?>" alt="" width="32" height="32"
                                    loading="lazy">
                                <?php else: ?>
                                <i class="fa-solid <?php echo esc_attr($card['icon_fa']); ?>"></i>
                                <?php endif; ?>
                            </div>

                            <?php if ($card['title']): ?>
                            <h3 class="vy-about__card-title">
                                <?php echo esc_html($card['title']); ?>
                            </h3>
                            <?php endif; ?>

                            <?php if ($card['desc']): ?>
                            <p class="vy-about__card-desc">
                                <?php echo esc_html($card['desc']); ?>
                            </p>
                            <?php endif; ?>

                        </div><!-- .vy-about__card -->
                        <?php endforeach; ?>

                    </div><!-- .vy-about__cards -->
                </div><!-- .vy-about__right -->

            </div><!-- .vy-about__inner -->
        </div><!-- .vy-container -->
    </section><!-- .vy-about -->


    <!-- ============================================================
     S2 — FEATURED TOURS SLIDER
     Portrait cards | 4 desktop | 3 tablet | 2 small-tablet | 1 mobile
============================================================ -->
    <?php
    // Dùng lại key 'travel_essentials' — đã config lại max_posts = 12 trong home-options.php
    $tours_count = absint(get_theme_mod('vy_tours_count', 8));
    $tours_count = max(4, min(12, $tours_count));
    $tours_query = wl_get_section_query('travel_essentials', ['posts_per_page' => $tours_count]);

    // Section header text (Customizer)
    $tours_label = get_theme_mod('vy_tours_label', __('Khám phá ngay', 'voya'));
    $tours_title = get_theme_mod('vy_tours_title', __('Tour nổi bật', 'voya'));
    $tours_title_em = get_theme_mod('vy_tours_title_em', __('của chúng tôi', 'voya'));
    ?>

    <section class="vy-tours vy-section">
        <div class="vy-container">

            <!-- Header: label + title LEFT | arrows RIGHT -->
            <div class="vy-tours__header">
                <div class="vy-tours__header-left">
                    <span class="vy-label"><?php echo esc_html($tours_label); ?></span>
                    <h2 class="vy-section-title">
                        <?php echo esc_html($tours_title); ?>
                        <?php if ($tours_title_em): ?>
                        <em><?php echo esc_html($tours_title_em); ?></em>
                        <?php endif; ?>
                    </h2>
                </div>
                <div class="vy-tours__header-arrows">
                    <button class="vy-tours__arrow vy-tours__arrow--prev" type="button"
                        aria-label="<?php esc_attr_e('Tour trước', 'voya'); ?>">
                        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                    </button>
                    <button class="vy-tours__arrow vy-tours__arrow--next" type="button"
                        aria-label="<?php esc_attr_e('Tour tiếp theo', 'voya'); ?>">
                        <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <?php if ($tours_query && $tours_query->have_posts()):
                $tour_posts = $tours_query->posts;
                wp_reset_postdata(); ?>

            <!-- Slider -->
            <div class="vy-tours__slider-wrap" data-total="<?php echo count($tour_posts); ?>">
                <div class="vy-tours__viewport">
                    <div class="vy-tours__track">

                        <?php foreach ($tour_posts as $tp):
                                setup_postdata($tp);

                                // Thumbnail
                                $tp_tid = get_post_thumbnail_id($tp->ID);
                                $tp_img = $tp_tid
                                    ? wp_get_attachment_image_url($tp_tid, 'wl-card-sm')
                                    ?: wp_get_attachment_image_url($tp_tid, 'wl-card')
                                    ?: wp_get_attachment_image_url($tp_tid, 'full')
                                    : '';

                                // Badge: category (location) + duration meta
                                $cats = get_the_category($tp->ID);
                                $cat = $cats[0] ?? null;
                                $duration = get_post_meta($tp->ID, '_vy_tour_duration', true);
                                ?>

                        <article class="vy-tours__card" itemscope itemtype="https://schema.org/BlogPosting">

                            <!-- Image -->
                            <div class="vy-tours__card-img">
                                <a href="<?php echo esc_url(get_permalink($tp->ID)); ?>" class="vy-tours__img-link">
                                    <?php if ($tp_img): ?>
                                    <img src="<?php echo esc_url($tp_img); ?>"
                                        alt="<?php echo esc_attr($tp->post_title); ?>" loading="lazy" itemprop="image">
                                    <?php else: ?>
                                    <div class="vy-tours__img-placeholder"></div>
                                    <?php endif; ?>
                                </a>

                                <!-- Badge overlay -->
                                <?php if ($cat || $duration): ?>
                                <div class="vy-tours__badge">
                                    <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                                    <?php if ($cat): ?>
                                    <span><?php echo esc_html($cat->name); ?></span>
                                    <?php endif; ?>
                                    <?php if ($duration): ?>
                                    <span>| <?php echo esc_html($duration); ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </div><!-- .vy-tours__card-img -->

                            <!-- Title -->
                            <div class="vy-tours__card-body">
                                <h3 class="vy-tours__card-title" itemprop="headline">
                                    <a href="<?php echo esc_url(get_permalink($tp->ID)); ?>">
                                        <?php echo esc_html($tp->post_title); ?>
                                    </a>
                                </h3>
                            </div>

                        </article><!-- .vy-tours__card -->

                        <?php endforeach;
                            wp_reset_postdata(); ?>

                    </div><!-- .vy-tours__track -->
                </div><!-- .vy-tours__viewport -->
            </div><!-- .vy-tours__slider-wrap -->

            <?php else: ?>
            <?php if (current_user_can('edit_theme_options')): ?>
            <p class="vy-empty-notice">
                <?php esc_html_e('Chưa có bài viết. ', 'voya'); ?>
                <a href="<?php echo esc_url(admin_url('themes.php?page=wl-home-sections&tab=travel_essentials')); ?>">
                    <?php esc_html_e('Cài đặt →', 'voya'); ?>
                </a>
            </p>
            <?php endif; ?>
            <?php endif; ?>

        </div><!-- .vy-container -->
    </section><!-- .vy-tours -->

    <!-- ============================================================
         S3 — NEWSLETTER (dark band)
    ============================================================ -->
    <?php
    // FIX: sanitize $_GET['nl'] — tránh XSS nếu ai inject giá trị lạ
    $nl_status = isset($_GET['nl']) ? sanitize_key($_GET['nl']) : '';
    ?>

    <section class="vy-newsletter">
        <div class="vy-container--narrow">
            <div class="vy-newsletter__inner">

                <span class="vy-label vy-label--light">
                    <?php echo esc_html(get_theme_mod('wl_newsletter_tagline', 'Newsletter')); ?>
                </span>

                <h2 class="vy-newsletter__title">
                    <?php echo esc_html(get_theme_mod('wl_newsletter_title', 'Hành trình mới mỗi tuần, gửi thẳng đến hộp thư')); ?>
                    <?php $hl = get_theme_mod('wl_newsletter_highlight', '');
                    if ($hl): ?>
                    <em class="vy-newsletter__hl"><?php echo esc_html($hl); ?></em>
                    <?php endif; ?>
                </h2>

                <p class="vy-newsletter__desc">
                    <?php echo esc_html(get_theme_mod('wl_newsletter_text', 'Đăng ký để nhận bài viết mới nhất, mẹo du lịch và những địa điểm ẩn chưa ai biết.')); ?>
                </p>

                <form class="vy-newsletter__form" method="post"
                    action="<?php echo esc_url(admin_url('admin-post.php')); ?>" novalidate>
                    <?php wp_nonce_field('wl_newsletter_subscribe', 'wl_nl_nonce'); ?>
                    <input type="hidden" name="action" value="wl_newsletter_subscribe">
                    <input type="hidden" name="redirect_to" value="<?php echo esc_url(home_url('/?nl=success')); ?>">

                    <div class="vy-newsletter__row">
                        <input type="text" name="nl_name" class="vy-newsletter__input"
                            placeholder="<?php esc_attr_e('Tên của bạn', 'voya'); ?>" autocomplete="name" required>
                        <input type="email" name="nl_email" class="vy-newsletter__input"
                            placeholder="<?php esc_attr_e('Địa chỉ email', 'voya'); ?>" autocomplete="email" required>
                        <button type="submit" class="vy-newsletter__submit">
                            <?php esc_html_e('Đăng ký', 'voya'); ?>
                            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </button>
                    </div>

                    <?php if ($nl_status === 'success'): ?>
                    <p class="vy-newsletter__success">
                        <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                        <?php esc_html_e('Đăng ký thành công! Cảm ơn bạn.', 'voya'); ?>
                    </p>
                    <?php elseif ($nl_status === 'error'): ?>
                    <p class="vy-newsletter__error">
                        <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                        <?php esc_html_e('Email không hợp lệ. Vui lòng thử lại.', 'voya'); ?>
                    </p>
                    <?php endif; ?>
                </form>

            </div>
        </div>
    </section>


    <!-- ============================================================
     S4 — FEATURED POSTS SLIDER
     Tour card: image + badge + title + short desc + CTA
     3 desktop | 2 tablet | 1 mobile
============================================================ -->
    <?php
    $featured_count = absint(get_theme_mod('wl_featured_posts_count', 6));
    $featured_count = max(3, min(12, $featured_count));
    $featured_query = wl_get_section_query('featured_posts', ['posts_per_page' => $featured_count]);
    ?>

    <section class="vy-featured vy-section">

        <div class="vy-container">

            <!-- Header -->
            <div class="vy-featured__header">
                <div class="vy-featured__header-left">
                    <span class="vy-label"><?php esc_html_e('Khám phá thêm', 'voya'); ?></span>
                    <h2 class="vy-section-title">
                        <?php esc_html_e('Bài viết ', 'voya'); ?>
                        <em><?php esc_html_e('nổi bật', 'voya'); ?></em>
                    </h2>
                </div>
                <div class="vy-featured__header-arrows">
                    <button class="vy-featured__arrow vy-featured__arrow--prev" type="button"
                        aria-label="<?php esc_attr_e('Bài trước', 'voya'); ?>">
                        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                    </button>
                    <button class="vy-featured__arrow vy-featured__arrow--next" type="button"
                        aria-label="<?php esc_attr_e('Bài tiếp theo', 'voya'); ?>">
                        <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <?php if ($featured_query && $featured_query->have_posts()):
                $all_posts = $featured_query->posts;
                wp_reset_postdata(); ?>

            <!-- Slider — nằm trong .vy-container để có padding 2 bên -->
            <div class="vy-featured__slider-wrap" data-total="<?php echo count($all_posts); ?>">
                <div class="vy-featured__viewport">
                    <div class="vy-featured__track">

                        <?php foreach ($all_posts as $fp):
                                setup_postdata($fp);

                                /* Thumbnail */
                                $fp_tid = get_post_thumbnail_id($fp->ID);
                                $fp_img = $fp_tid
                                    ? wp_get_attachment_image_url($fp_tid, 'wl-card')
                                    ?: wp_get_attachment_image_url($fp_tid, 'large')
                                    ?: wp_get_attachment_image_url($fp_tid, 'full')
                                    : '';

                                /* Badge: category + duration */
                                $cats = get_the_category($fp->ID);
                                $cat = $cats[0] ?? null;
                                $duration = get_post_meta($fp->ID, '_vy_tour_duration', true);

                                /* Short description: excerpt → fallback trim content */
                                $short_desc = get_post_field('post_excerpt', $fp->ID);
                                if (empty($short_desc)) {
                                    $short_desc = wp_trim_words(
                                        get_post_field('post_content', $fp->ID),
                                        20,
                                        '…'
                                    );
                                } else {
                                    $short_desc = wp_trim_words($short_desc, 20, '…');
                                }
                                ?>

                        <article class="vy-featured__card" itemscope itemtype="https://schema.org/BlogPosting">

                            <!-- Image + Badge -->
                            <div class="vy-featured__card-img">
                                <a href="<?php echo esc_url(get_permalink($fp->ID)); ?>" class="vy-featured__img-link">
                                    <?php if ($fp_img): ?>
                                    <img src="<?php echo esc_url($fp_img); ?>"
                                        alt="<?php echo esc_attr($fp->post_title); ?>" loading="lazy" itemprop="image">
                                    <?php else: ?>
                                    <div class="vy-featured__img-placeholder"></div>
                                    <?php endif; ?>
                                </a>

                                <?php if ($cat || $duration): ?>
                                <div class="vy-featured__badge">
                                    <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                                    <?php if ($cat): ?>
                                    <span class="vy-featured__badge-country">
                                        <?php echo esc_html($cat->name); ?>
                                    </span>
                                    <?php endif; ?>
                                    <?php if ($duration): ?>
                                    <span class="vy-featured__badge-sep" aria-hidden="true">|</span>
                                    <span class="vy-featured__badge-dur">
                                        <?php echo esc_html($duration); ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </div><!-- .vy-featured__card-img -->

                            <!-- Card Body -->
                            <div class="vy-featured__card-body">

                                <h3 class="vy-featured__card-title" itemprop="headline">
                                    <a href="<?php echo esc_url(get_permalink($fp->ID)); ?>">
                                        <?php echo esc_html($fp->post_title); ?>
                                    </a>
                                </h3>

                                <?php if ($short_desc): ?>
                                <p class="vy-featured__short-desc" itemprop="description">
                                    <?php echo esc_html($short_desc); ?>
                                </p>
                                <?php endif; ?>

                                <div class="vy-featured__card-btm">
                                    <a href="<?php echo esc_url(get_permalink($fp->ID)); ?>"
                                        class="vy-featured__learn-more">
                                        <?php esc_html_e('Xem chi tiết', 'voya'); ?>
                                    </a>
                                </div>

                            </div><!-- .vy-featured__card-body -->

                        </article><!-- .vy-featured__card -->

                        <?php endforeach;
                            wp_reset_postdata(); ?>

                    </div><!-- .vy-featured__track -->
                </div><!-- .vy-featured__viewport -->
            </div><!-- .vy-featured__slider-wrap -->

            <?php else: ?>
            <?php if (current_user_can('edit_theme_options')): ?>
            <p class="vy-empty-notice">
                <?php esc_html_e('Chưa có bài viết. ', 'voya'); ?>
                <a href="<?php echo esc_url(admin_url('themes.php?page=wl-home-sections&tab=featured_posts')); ?>">
                    <?php esc_html_e('Cài đặt →', 'voya'); ?>
                </a>
            </p>
            <?php endif; ?>
            <?php endif; ?>

        </div><!-- .vy-container -->

    </section><!-- .vy-featured -->

    <!-- ============================================================
     S5 — DESTINATIONS PORTRAIT SLIDER
     Data: WP posts (chọn trong Home Sections admin)
     Portrait cards (255:345) | 4 desktop | 3 tablet | 1 mobile
============================================================ -->
    <?php
    $dest_count = absint(get_theme_mod('wl_destinations_count', 8));
    $dest_count = max(4, min(20, $dest_count));
    $dest_s_query = wl_get_section_query('destination_slider', ['posts_per_page' => $dest_count]);
    ?>

    <section class="vy-dest-slider vy-section">
        <div class="vy-container">

            <!-- Header: label + title LEFT | arrows RIGHT -->
            <div class="vy-dest-slider__header">
                <div class="vy-dest-slider__header-left">
                    <span class="vy-label">
                        <?php echo esc_html(get_theme_mod('vy_dest_slider_label', __('Bản đồ hành trình', 'voya'))); ?>
                    </span>
                    <h2 class="vy-section-title">
                        <?php echo esc_html(get_theme_mod('vy_dest_slider_title', __('Điểm đến ', 'voya'))); ?>
                        <?php $dest_em = get_theme_mod('vy_dest_slider_title_em', __('Đông Nam Á', 'voya'));
                        if ($dest_em): ?>
                        <em><?php echo esc_html($dest_em); ?></em>
                        <?php endif; ?>
                    </h2>
                </div>
                <div class="vy-dest-slider__arrows">
                    <button class="vy-dest-slider__arrow vy-dest-slider__arrow--prev" type="button"
                        aria-label="<?php esc_attr_e('Điểm đến trước', 'voya'); ?>">
                        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                    </button>
                    <button class="vy-dest-slider__arrow vy-dest-slider__arrow--next" type="button"
                        aria-label="<?php esc_attr_e('Điểm đến tiếp theo', 'voya'); ?>">
                        <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <?php if ($dest_s_query && $dest_s_query->have_posts()):
                $dest_s_posts = $dest_s_query->posts;
                wp_reset_postdata(); ?>

            <div class="vy-dest-slider__wrap" data-total="<?php echo count($dest_s_posts); ?>">
                <div class="vy-dest-slider__viewport">
                    <div class="vy-dest-slider__track">

                        <?php foreach ($dest_s_posts as $dp):
                                /* Thumbnail — ưu tiên portrait size */
                                $dp_tid = get_post_thumbnail_id($dp->ID);
                                $dp_img = $dp_tid
                                    ? wp_get_attachment_image_url($dp_tid, 'vy-portrait')
                                    ?: wp_get_attachment_image_url($dp_tid, 'wl-card-sm')
                                    ?: wp_get_attachment_image_url($dp_tid, 'medium')
                                    ?: wp_get_attachment_image_url($dp_tid, 'full')
                                    : '';
                                ?>

                        <div class="vy-dest-slider__card">
                            <a href="<?php echo esc_url(get_permalink($dp->ID)); ?>" class="vy-dest-slider__card-link"
                                title="<?php echo esc_attr($dp->post_title); ?>">

                                <div class="vy-dest-slider__img-wrap">

                                    <?php if ($dp_img): ?>
                                    <img src="<?php echo esc_url($dp_img); ?>"
                                        alt="<?php echo esc_attr($dp->post_title); ?>" loading="lazy"
                                        class="vy-dest-slider__img">
                                    <?php else: ?>
                                    <div class="vy-dest-slider__img-placeholder"></div>
                                    <?php endif; ?>

                                    <div class="vy-dest-slider__overlay" aria-hidden="true"></div>

                                    <span class="vy-dest-slider__name">
                                        <?php echo esc_html($dp->post_title); ?>
                                    </span>

                                </div><!-- .vy-dest-slider__img-wrap -->

                            </a>
                        </div><!-- .vy-dest-slider__card -->

                        <?php endforeach; ?>

                    </div><!-- .vy-dest-slider__track -->
                </div><!-- .vy-dest-slider__viewport -->
            </div><!-- .vy-dest-slider__wrap -->

            <?php else: ?>
            <?php if (current_user_can('edit_theme_options')): ?>
            <p class="vy-empty-notice">
                <?php esc_html_e('Chưa có bài viết nào. ', 'voya'); ?>
                <a href="<?php echo esc_url(admin_url('themes.php?page=wl-home-sections&tab=destination_slider')); ?>">
                    <?php esc_html_e('Cài đặt →', 'voya'); ?>
                </a>
            </p>
            <?php endif; ?>
            <?php endif; ?>

        </div><!-- .vy-container -->
    </section><!-- .vy-dest-slider -->

    <!-- ============================================================
         S7 — LATEST NEWS GRID
     4 bài | asymmetric 3-col grid | text overlay bottom
     col1: large | col2: tall portrait | col3: 2 small stacked
============================================================ -->
    <?php
    $lnews_label = get_theme_mod('vy_latestnews_label', __('Cập nhật mới nhất', 'voya'));
    $lnews_title = get_theme_mod('vy_latestnews_title', __('Tin tức ', 'voya'));
    $lnews_title_em = get_theme_mod('vy_latestnews_title_em', __('nổi bật', 'voya'));

    $dcl_query = wl_get_section_query('dcl_posts', ['posts_per_page' => 4]);
    ?>

    <section class="vy-latestnews vy-section">
        <div class="vy-container">

            <!-- Header — center -->
            <div class="vy-latestnews__header">
                <span class="vy-label"><?php echo esc_html($lnews_label); ?></span>
                <h2 class="vy-section-title">
                    <?php echo esc_html($lnews_title); ?>
                    <?php if ($lnews_title_em): ?>
                    <em><?php echo esc_html($lnews_title_em); ?></em>
                    <?php endif; ?>
                </h2>
            </div>

            <?php if ($dcl_query && $dcl_query->have_posts()):
                $news_posts = $dcl_query->posts;
                wp_reset_postdata();

                /* Gán item index để áp đúng CSS class bố cục */
                $item_classes = ['vy-latestnews__item--1', 'vy-latestnews__item--2', 'vy-latestnews__item--3', 'vy-latestnews__item--4'];
                ?>

            <div class="vy-latestnews__grid">

                <?php foreach ($news_posts as $ni => $np):
                        $ni_tid = get_post_thumbnail_id($np->ID);

                        /* Size hint theo vị trí:
                           item1 → landscape lớn (wl-card)
                           item2 → portrait tall (vy-portrait hoặc wl-card)
                           item3,4 → landscape nhỏ (wl-card-sm) */
                        $img_size = ($ni === 1) ? 'vy-portrait' : (($ni === 0) ? 'wl-card' : 'wl-card-sm');

                        $ni_img = $ni_tid
                            ? wp_get_attachment_image_url($ni_tid, $img_size)
                            ?: wp_get_attachment_image_url($ni_tid, 'wl-card')
                            ?: wp_get_attachment_image_url($ni_tid, 'large')
                            ?: wp_get_attachment_image_url($ni_tid, 'full')
                            : '';

                        $cats = get_the_category($np->ID);
                        $cat = $cats[0] ?? null;
                        $date_str = get_the_date('M j, Y', $np->ID);
                        $datetime = get_the_date('Y-m-d', $np->ID);
                        $item_cls = $item_classes[$ni] ?? '';
                        ?>

                <article class="vy-latestnews__item <?php echo esc_attr($item_cls); ?>" itemscope
                    itemtype="https://schema.org/BlogPosting">

                    <!-- Image link — fills entire card -->
                    <a href="<?php echo esc_url(get_permalink($np->ID)); ?>" class="vy-latestnews__img-link"
                        tabindex="0">

                        <?php if ($ni_img): ?>
                        <img src="<?php echo esc_url($ni_img); ?>" alt="<?php echo esc_attr($np->post_title); ?>"
                            loading="<?php echo $ni === 0 ? 'eager' : 'lazy'; ?>" class="vy-latestnews__img"
                            itemprop="image">
                        <?php else: ?>
                        <div class="vy-latestnews__img-placeholder"></div>
                        <?php endif; ?>

                        <!-- Gradient overlay -->
                        <div class="vy-latestnews__overlay" aria-hidden="true"></div>

                        <!-- Text content — bottom overlay -->
                        <div class="vy-latestnews__ct">
                            <div class="vy-latestnews__meta">
                                <?php if ($cat): ?>
                                <span class="vy-latestnews__cat">
                                    <i class="fa-solid fa-folder" aria-hidden="true"></i>
                                    <?php echo esc_html($cat->name); ?>
                                </span>
                                <?php endif; ?>
                                <time datetime="<?php echo esc_attr($datetime); ?>" class="vy-latestnews__date"
                                    itemprop="datePublished">
                                    <i class="fa-regular fa-clock" aria-hidden="true"></i>
                                    <?php echo esc_html($date_str); ?>
                                </time>
                            </div>
                            <h3 class="vy-latestnews__title" itemprop="headline">
                                <?php echo esc_html($np->post_title); ?>
                            </h3>
                        </div><!-- .vy-latestnews__ct -->

                    </a><!-- .vy-latestnews__img-link -->

                </article><!-- .vy-latestnews__item -->

                <?php endforeach; ?>

            </div><!-- .vy-latestnews__grid -->

            <?php else: ?>
            <?php if (current_user_can('edit_theme_options')): ?>
            <p class="vy-empty-notice">
                <?php esc_html_e('Chưa có bài viết. ', 'voya'); ?>
                <a href="<?php echo esc_url(admin_url('themes.php?page=wl-home-sections&tab=dcl_posts')); ?>">
                    <?php esc_html_e('Cài đặt →', 'voya'); ?>
                </a>
            </p>
            <?php endif; ?>
            <?php endif; ?>

        </div><!-- .vy-container -->
    </section><!-- .vy-latestnews -->


    <!-- ============================================================
     S8 — TOP POPULAR BLOGS
     6 portrait cards | title below image | no slider
     Data: WP posts (chọn trong Home Sections admin)
============================================================ -->
    <?php
    $topblogs_count = absint(get_theme_mod('vy_topblogs_count', 6));
    $topblogs_count = max(3, min(12, $topblogs_count));
    $topblogs_query = wl_get_section_query('top_blogs', ['posts_per_page' => $topblogs_count]);

    $topblogs_label = get_theme_mod('vy_topblogs_label', __('Được đọc nhiều nhất', 'voya'));
    $topblogs_title = get_theme_mod('vy_topblogs_title', __('Top bài viết ', 'voya'));
    $topblogs_title_em = get_theme_mod('vy_topblogs_title_em', __('nổi bật', 'voya'));
    $topblogs_desc = get_theme_mod('vy_topblogs_desc', __('Những bài viết được yêu thích nhất từ độc giả của chúng tôi.', 'voya'));
    ?>

    <section class="vy-topblogs vy-section">
        <div class="vy-container">

            <!-- Section Header — center -->
            <div class="vy-topblogs__header">
                <span class="vy-label">
                    <?php echo esc_html($topblogs_label); ?>
                </span>
                <h2 class="vy-section-title">
                    <?php echo esc_html($topblogs_title); ?>
                    <?php if ($topblogs_title_em): ?>
                    <em>
                        <?php echo esc_html($topblogs_title_em); ?>
                    </em>
                    <?php endif; ?>
                </h2>
                <?php if ($topblogs_desc): ?>
                <p class="vy-topblogs__desc">
                    <?php echo esc_html($topblogs_desc); ?>
                </p>
                <?php endif; ?>
            </div>

            <?php if ($topblogs_query && $topblogs_query->have_posts()):
                $top_posts = $topblogs_query->posts;
                wp_reset_postdata(); ?>

            <div class="vy-topblogs__grid">

                <?php foreach ($top_posts as $tp):
                        $tp_tid = get_post_thumbnail_id($tp->ID);
                        $tp_img = $tp_tid
                            ? wp_get_attachment_image_url($tp_tid, 'vy-portrait')
                            ?: wp_get_attachment_image_url($tp_tid, 'wl-card-sm')
                            ?: wp_get_attachment_image_url($tp_tid, 'medium')
                            ?: wp_get_attachment_image_url($tp_tid, 'full')
                            : '';
                        ?>

                <div class="vy-topblog-card">
                    <div class="vy-topblog-card__inner">

                        <!-- Portrait image -->
                        <a href="<?php echo esc_url(get_permalink($tp->ID)); ?>" class="vy-topblog-card__img-link"
                            title="<?php echo esc_attr($tp->post_title); ?>">
                            <div class="vy-topblog-card__img-wrap">
                                <?php if ($tp_img): ?>
                                <img src="<?php echo esc_url($tp_img); ?>"
                                    alt="<?php echo esc_attr($tp->post_title); ?>" loading="lazy"
                                    class="vy-topblog-card__img">
                                <?php else: ?>
                                <div class="vy-topblog-card__img-placeholder"></div>
                                <?php endif; ?>
                            </div>
                        </a>

                        <!-- Title — bên dưới ảnh, NGOÀI img-wrap -->
                        <div class="vy-topblog-card__ct">
                            <h4 class="vy-topblog-card__title">
                                <a href="<?php echo esc_url(get_permalink($tp->ID)); ?>">
                                    <?php echo esc_html($tp->post_title); ?>
                                </a>
                            </h4>
                        </div>

                    </div><!-- .vy-topblog-card__inner -->
                </div><!-- .vy-topblog-card -->

                <?php endforeach; ?>

            </div><!-- .vy-topblogs__grid -->

            <?php else: ?>
            <?php if (current_user_can('edit_theme_options')): ?>
            <p class="vy-empty-notice">
                <?php esc_html_e('Chưa có bài viết. ', 'voya'); ?>
                <a href="<?php echo esc_url(admin_url('themes.php?page=wl-home-sections&tab=top_blogs')); ?>">
                    <?php esc_html_e('Cài đặt →', 'voya'); ?>
                </a>
            </p>
            <?php endif; ?>
            <?php endif; ?>

        </div><!-- .vy-container -->
    </section><!-- .vy-topblogs -->

</main>

<?php get_footer(); ?>