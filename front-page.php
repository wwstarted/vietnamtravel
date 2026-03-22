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
         S1b — PARTNERS STRIP
    ============================================================ -->
    <?php
    $fallback_imgs = [
        ['img' => 'https://wanderland.qodeinteractive.com/wp-content/uploads/2019/10/h1-clients-img-05.png', 'hover' => 'https://wanderland.qodeinteractive.com/wp-content/uploads/2019/12/h1-clients-img-05-hover.png', 'alt' => 'Partner 1', 'url' => '#'],
        ['img' => 'https://wanderland.qodeinteractive.com/wp-content/uploads/2019/10/h1-clients-img-01.png', 'hover' => 'https://wanderland.qodeinteractive.com/wp-content/uploads/2019/12/h1-clients-img-01-hover.png', 'alt' => 'Partner 2', 'url' => '#'],
        ['img' => 'https://wanderland.qodeinteractive.com/wp-content/uploads/2019/10/h1-clients-img-02.png', 'hover' => 'https://wanderland.qodeinteractive.com/wp-content/uploads/2019/12/h1-clients-img-02-hover.png', 'alt' => 'Partner 3', 'url' => '#'],
        ['img' => 'https://wanderland.qodeinteractive.com/wp-content/uploads/2019/10/h1-clients-img-03.png', 'hover' => 'https://wanderland.qodeinteractive.com/wp-content/uploads/2019/12/h1-clients-img-03-hover.png', 'alt' => 'Partner 4', 'url' => '#'],
        ['img' => 'https://wanderland.qodeinteractive.com/wp-content/uploads/2019/10/h1-clients-img-04.png', 'hover' => 'https://wanderland.qodeinteractive.com/wp-content/uploads/2019/12/h1-clients-img-04-hover.png', 'alt' => 'Partner 5', 'url' => '#'],
    ];

    $clients = [];
    for ($ci = 1; $ci <= 5; $ci++) {
        $img_id = get_theme_mod('wl_client_' . $ci . '_image');
        $img_h_id = get_theme_mod('wl_client_' . $ci . '_hover');
        $clients[] = [
            'img' => $img_id ? wp_get_attachment_image_url($img_id, 'full') : $fallback_imgs[$ci - 1]['img'],
            'hover' => $img_h_id ? wp_get_attachment_image_url($img_h_id, 'full') : $fallback_imgs[$ci - 1]['hover'],
            'url' => get_theme_mod('wl_client_' . $ci . '_url', '#') ?: '#',
            'alt' => get_theme_mod('wl_client_' . $ci . '_alt', 'Partner ' . $ci),
        ];
    }
    ?>

    <section class="vy-partners" aria-label="<?php esc_attr_e('Đối tác & Thương hiệu', 'voya'); ?>">
        <div class="vy-container--wide">
            <div class="vy-partners__inner">
                <p class="vy-partners__label"><?php esc_html_e('Được tin dùng bởi', 'voya'); ?></p>
                <div class="vy-partners__list">
                    <?php foreach ($clients as $client): ?>
                    <a href="<?php echo esc_url($client['url']); ?>" class="vy-partners__item"
                        aria-label="<?php echo esc_attr($client['alt']); ?>">
                        <img class="vy-partners__logo vy-partners__logo--default"
                            src="<?php echo esc_url($client['img']); ?>" alt="<?php echo esc_attr($client['alt']); ?>"
                            loading="lazy">
                        <img class="vy-partners__logo vy-partners__logo--hover"
                            src="<?php echo esc_url($client['hover']); ?>" alt="" loading="lazy" aria-hidden="true">
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>


    <!-- ============================================================
         S2 — TRAVEL ESSENTIALS
         numbered editorial split — image | content (alternating)
    ============================================================ -->
    <?php
    $essentials_query = wl_get_section_query('travel_essentials', ['posts_per_page' => 2]);
    ?>

    <section class="vy-essentials vy-section">
        <div class="vy-container">

            <header class="vy-essentials__header">
                <span class="vy-label"><?php esc_html_e('Cẩm nang du lịch', 'voya'); ?></span>
                <h2 class="vy-section-title">
                    <?php esc_html_e('Kiến thức cần thiết ', 'voya'); ?>
                    <em><?php esc_html_e('cho mỗi chuyến đi', 'voya'); ?></em>
                </h2>
            </header>

            <?php if ($essentials_query && $essentials_query->have_posts()): ?>
            <div class="vy-essentials__list">
                <?php
                        $ess_i = 0;
                        while ($essentials_query->have_posts()):
                            $essentials_query->the_post();
                            $ess_tid = get_post_thumbnail_id();
                            $ess_img = $ess_tid
                                ? wp_get_attachment_image_url($ess_tid, 'wl-card')
                                ?: wp_get_attachment_image_url($ess_tid, 'large')
                                ?: wp_get_attachment_image_url($ess_tid, 'full')
                                : '';
                            $cats = get_the_category();
                            $cat = $cats[0] ?? null;
                            $excerpt = get_the_excerpt() ?: wp_trim_words(get_the_content(), 30, '…');
                            $reversed = $ess_i % 2 !== 0;
                            ?>

                <article class="vy-essentials__item<?php echo $reversed ? ' is-reversed' : ''; ?>" itemscope
                    itemtype="https://schema.org/BlogPosting">

                    <span class="vy-essentials__index" aria-hidden="true">
                        <?php echo esc_html(str_pad($ess_i + 1, 2, '0', STR_PAD_LEFT)); ?>
                    </span>

                    <div class="vy-essentials__img-col">
                        <a href="<?php the_permalink(); ?>" class="vy-essentials__img-link">
                            <?php if ($ess_img): ?>
                            <img src="<?php echo esc_url($ess_img); ?>" alt="<?php the_title_attribute(); ?>"
                                loading="lazy" itemprop="image">
                            <?php else: ?>
                            <div class="vy-essentials__img-placeholder"></div>
                            <?php endif; ?>
                        </a>
                        <?php if ($cat): ?>
                        <a href="<?php echo esc_url(get_category_link($cat->term_id)); ?>" class="vy-essentials__cat">
                            <?php echo esc_html($cat->name); ?>
                        </a>
                        <?php endif; ?>
                    </div>

                    <div class="vy-essentials__content-col">
                        <div class="vy-essentials__meta">
                            <time datetime="<?php echo esc_attr(get_the_date('Y-m-d')); ?>" itemprop="datePublished">
                                <?php echo esc_html(get_the_date('j M, Y')); ?>
                            </time>
                            <span aria-hidden="true">·</span>
                            <a href="<?php echo esc_url(get_author_posts_url(get_the_author_meta('ID'))); ?>"
                                itemprop="author">
                                <?php echo esc_html(get_the_author()); ?>
                            </a>
                        </div>

                        <h3 class="vy-essentials__post-title" itemprop="headline">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h3>

                        <p class="vy-essentials__excerpt" itemprop="description">
                            <?php echo esc_html(wp_trim_words($excerpt, 30, '…')); ?>
                        </p>

                        <a href="<?php the_permalink(); ?>" class="vy-btn vy-btn--text">
                            <?php esc_html_e('Đọc thêm', 'voya'); ?>
                            <i class="fa-solid fa-arrow-up-right" aria-hidden="true"></i>
                        </a>
                    </div>

                </article>

                <?php $ess_i++; endwhile;
                        wp_reset_postdata(); ?>
            </div>

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

        </div>
    </section>


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
         header arrows | landscape cards | 3→2→1 responsive
    ============================================================ -->
    <?php
    $featured_count = absint(get_theme_mod('wl_featured_posts_count', 6));
    $featured_count = max(3, min(12, $featured_count)); // clamp 3–12
    $featured_query = wl_get_section_query('featured_posts', ['posts_per_page' => $featured_count]);
    ?>

    <section class="vy-featured vy-section">

        <div class="vy-container">
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
        </div>

        <?php if ($featured_query && $featured_query->have_posts()):
            $all_posts = $featured_query->posts;
            wp_reset_postdata(); ?>

        <div class="vy-featured__slider-wrap" data-total="<?php echo count($all_posts); ?>">
            <div class="vy-featured__viewport">
                <div class="vy-featured__track">

                    <?php foreach ($all_posts as $fp):
                                setup_postdata($fp);
                                $fp_tid = get_post_thumbnail_id($fp->ID);
                                $fp_img = $fp_tid
                                    ? wp_get_attachment_image_url($fp_tid, 'wl-card')
                                    ?: wp_get_attachment_image_url($fp_tid, 'large')
                                    ?: wp_get_attachment_image_url($fp_tid, 'full')
                                    : '';
                                $cats = get_the_category($fp->ID);
                                $cat = $cats[0] ?? null;
                                ?>

                    <article class="vy-featured__card" itemscope itemtype="https://schema.org/BlogPosting">

                        <div class="vy-featured__card-img">
                            <a href="<?php echo esc_url(get_permalink($fp->ID)); ?>" class="vy-featured__img-link">
                                <?php if ($fp_img): ?>
                                <img src="<?php echo esc_url($fp_img); ?>"
                                    alt="<?php echo esc_attr($fp->post_title); ?>" loading="lazy" itemprop="image">
                                <?php else: ?>
                                <div class="vy-featured__img-placeholder"></div>
                                <?php endif; ?>
                            </a>
                            <?php if ($cat): ?>
                            <a href="<?php echo esc_url(get_category_link($cat->term_id)); ?>" class="vy-featured__cat">
                                <?php echo esc_html($cat->name); ?>
                            </a>
                            <?php endif; ?>
                        </div>

                        <div class="vy-featured__card-body">
                            <time datetime="<?php echo esc_attr(get_the_date('Y-m-d', $fp->ID)); ?>"
                                class="vy-featured__date" itemprop="datePublished">
                                <?php echo esc_html(get_the_date('j M Y', $fp->ID)); ?>
                            </time>
                            <h3 class="vy-featured__card-title" itemprop="headline">
                                <a href="<?php echo esc_url(get_permalink($fp->ID)); ?>">
                                    <?php echo esc_html($fp->post_title); ?>
                                </a>
                            </h3>
                        </div>

                    </article>

                    <?php endforeach;
                            wp_reset_postdata(); ?>

                </div>
            </div>
        </div>

        <?php else: ?>
        <?php if (current_user_can('edit_theme_options')): ?>
        <div class="vy-container">
            <p class="vy-empty-notice">
                <?php esc_html_e('Chưa có bài viết. ', 'voya'); ?>
                <a href="<?php echo esc_url(admin_url('themes.php?page=wl-home-sections&tab=featured_posts')); ?>">
                    <?php esc_html_e('Cài đặt →', 'voya'); ?>
                </a>
            </p>
        </div>
        <?php endif; ?>
        <?php endif; ?>

    </section>


    <!-- ============================================================
         S5 — DESTINATIONS CARD GRID
         3-col grid | responsive 3→2→1
    ============================================================ -->
    <?php
    $dest_count = absint(get_theme_mod('wl_destinations_count', 9));
    $dest_query = wl_get_destinations($dest_count);
    ?>

    <section class="vy-destinations vy-section">
        <div class="vy-container">

            <header class="vy-destinations__header">
                <div class="vy-destinations__header-text">
                    <span class="vy-label"><?php esc_html_e('Bản đồ hành trình', 'voya'); ?></span>
                    <h2 class="vy-section-title">
                        <?php esc_html_e('Điểm đến ', 'voya'); ?>
                        <em><?php esc_html_e('đáng nhớ', 'voya'); ?></em>
                    </h2>
                </div>
                <span class="vy-destinations__watermark" aria-hidden="true">EXPLORE</span>
            </header>

            <?php if ($dest_query->have_posts()):
                $dest_posts = $dest_query->posts;
                wp_reset_postdata(); ?>

            <div class="vy-destinations__grid">
                <?php foreach ($dest_posts as $di => $dp):
                            $dp_tid = get_post_thumbnail_id($dp->ID);
                            $dp_img = $dp_tid
                                ? wp_get_attachment_image_url($dp_tid, 'wl-thumb')
                                ?: wp_get_attachment_image_url($dp_tid, 'medium')
                                ?: wp_get_attachment_image_url($dp_tid, 'full')
                                : '';
                            $ext_url = get_post_meta($dp->ID, '_wl_dest_url', true);
                            $link = $ext_url ?: get_permalink($dp->ID);
                            // Coordinates — chỉ hiện nếu có ít nhất 1 giá trị
                            $lat = get_post_meta($dp->ID, '_wl_dest_lat', true);
                            $lng = get_post_meta($dp->ID, '_wl_dest_lng', true);
                            $coords = array_filter([$lat, $lng]);
                            ?>

                <a class="vy-dest-card" href="<?php echo esc_url($link); ?>">

                    <div class="vy-dest-card__img">
                        <?php if ($dp_img): ?>
                        <img src="<?php echo esc_url($dp_img); ?>" alt="<?php echo esc_attr($dp->post_title); ?>"
                            loading="lazy">
                        <?php else: ?>
                        <div class="vy-dest-card__img-placeholder"></div>
                        <?php endif; ?>
                    </div>

                    <div class="vy-dest-card__body">
                        <span class="vy-dest-card__num" aria-hidden="true">
                            <?php echo esc_html(str_pad($di + 1, 2, '0', STR_PAD_LEFT)); ?>
                        </span>
                        <div class="vy-dest-card__info">
                            <h6 class="vy-dest-card__title">
                                <?php echo esc_html($dp->post_title); ?>
                            </h6>
                            <?php if (!empty($coords)): ?>
                            <p class="vy-dest-card__coords">
                                <?php echo esc_html(implode(' · ', $coords)); ?>
                            </p>
                            <?php endif; ?>
                        </div>
                        <i class="fa-solid fa-arrow-up-right vy-dest-card__arrow" aria-hidden="true"></i>
                    </div>

                </a>

                <?php endforeach; ?>
            </div>

            <?php else: ?>
            <?php if (current_user_can('edit_posts')): ?>
            <p class="vy-empty-notice">
                <?php esc_html_e('Chưa có địa danh nào. ', 'voya'); ?>
                <a href="<?php echo esc_url(admin_url('post-new.php?post_type=wl_destination')); ?>">
                    <?php esc_html_e('Thêm ngay →', 'voya'); ?>
                </a>
            </p>
            <?php endif; ?>
            <?php endif; ?>

        </div>
    </section>


    <!-- ============================================================
         S6 — EXPLORE: CATEGORIES + POSTS + CTA
    ============================================================ -->
    <section class="vy-explore vy-section">
        <div class="vy-container">

            <!-- Category Pills -->
            <?php
            // FIX: Bỏ exclude uncategorized (ID có thể khác trên mỗi site)
            // Thay bằng: thử wl_destination_cat trước, fallback về WP category
            $dest_cats = get_terms([
                'taxonomy' => 'wl_destination_cat',
                'hide_empty' => false,
                'number' => 6,
                'orderby' => 'name',
            ]);

            if (is_wp_error($dest_cats) || empty($dest_cats)) {
                $dest_cats = get_terms([
                    'taxonomy' => 'category',
                    'hide_empty' => true,   // FIX: true — chỉ show cat có bài
                    'number' => 6,
                    'orderby' => 'count',
                    'order' => 'DESC',
                ]);
            }
            ?>

            <?php if (!empty($dest_cats) && !is_wp_error($dest_cats)): ?>
            <nav class="vy-explore__cats" aria-label="<?php esc_attr_e('Danh mục', 'voya'); ?>">

                <header class="vy-explore__cats-header">
                    <span class="vy-label"><?php esc_html_e('Danh mục', 'voya'); ?></span>
                </header>

                <div class="vy-explore__cats-list">
                    <?php foreach ($dest_cats as $xcat):
                                $xcat_url = get_term_link($xcat);
                                $xcat_img = get_term_meta($xcat->term_id, '_wl_cat_image', true);
                                $xcat_imh = get_term_meta($xcat->term_id, '_wl_cat_image_hover', true);
                                ?>
                    <a class="vy-explore__cat-pill"
                        href="<?php echo esc_url(is_wp_error($xcat_url) ? '#' : $xcat_url); ?>">

                        <?php if ($xcat_img): ?>
                        <span class="vy-explore__cat-icon">
                            <img class="vy-explore__cat-img vy-explore__cat-img--default"
                                src="<?php echo esc_url($xcat_img); ?>" alt="" loading="lazy">
                            <?php if ($xcat_imh): ?>
                            <img class="vy-explore__cat-img vy-explore__cat-img--hover"
                                src="<?php echo esc_url($xcat_imh); ?>" alt="" loading="lazy" aria-hidden="true">
                            <?php endif; ?>
                        </span>
                        <?php else: ?>
                        <span class="vy-explore__cat-icon vy-explore__cat-icon--fa">
                            <i class="fa-solid fa-compass" aria-hidden="true"></i>
                        </span>
                        <?php endif; ?>

                        <span class="vy-explore__cat-name"><?php echo esc_html($xcat->name); ?></span>
                        <span class="vy-explore__cat-count"><?php echo esc_html($xcat->count); ?></span>

                    </a>
                    <?php endforeach; ?>
                </div>

            </nav>
            <?php endif; ?>

            <hr class="vy-explore__divider">

            <!-- Posts Grid + CTA -->
            <div class="vy-explore__bottom">

                <div class="vy-explore__posts-col">
                    <?php $dcl_query = wl_get_section_query('dcl_posts', ['posts_per_page' => 4]); ?>

                    <?php if ($dcl_query && $dcl_query->have_posts()): ?>
                    <div class="vy-explore__posts-grid">

                        <?php while ($dcl_query->have_posts()):
                                    $dcl_query->the_post();
                                    $ex_tid = get_post_thumbnail_id();
                                    $ex_img = $ex_tid
                                        ? wp_get_attachment_image_url($ex_tid, 'wl-card')
                                        ?: wp_get_attachment_image_url($ex_tid, 'large')
                                        ?: wp_get_attachment_image_url($ex_tid, 'full')
                                        : '';
                                    ?>

                        <article class="vy-explore__post-card" itemscope itemtype="https://schema.org/BlogPosting">

                            <a class="vy-explore__post-img-link" href="<?php the_permalink(); ?>">
                                <?php if ($ex_img): ?>
                                <img src="<?php echo esc_url($ex_img); ?>" alt="<?php the_title_attribute(); ?>"
                                    loading="lazy" itemprop="image">
                                <?php else: ?>
                                <div class="vy-explore__post-img-placeholder"></div>
                                <?php endif; ?>
                            </a>

                            <div class="vy-explore__post-body">
                                <div class="vy-explore__post-meta">
                                    <time datetime="<?php echo esc_attr(get_the_date('Y-m-d')); ?>">
                                        <?php echo esc_html(get_the_date('j M Y')); ?>
                                    </time>
                                    <span aria-hidden="true">·</span>
                                    <a href="<?php echo esc_url(get_author_posts_url(get_the_author_meta('ID'))); ?>">
                                        <?php echo esc_html(get_the_author()); ?>
                                    </a>
                                </div>
                                <h5 class="vy-explore__post-title" itemprop="headline">
                                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                </h5>
                            </div>

                        </article>

                        <?php endwhile;
                                wp_reset_postdata(); ?>

                    </div>

                    <?php else: ?>
                    <?php if (current_user_can('edit_theme_options')): ?>
                    <p class="vy-empty-notice">
                        <?php esc_html_e('Chưa có bài viết. ', 'voya'); ?>
                        <a href="<?php echo esc_url(admin_url('themes.php?page=wl-home-sections')); ?>">
                            <?php esc_html_e('Cài đặt →', 'voya'); ?>
                        </a>
                    </p>
                    <?php endif; ?>
                    <?php endif; ?>
                </div><!-- .vy-explore__posts-col -->

                <!-- CTA column -->
                <aside class="vy-explore__cta-col">
                    <div class="vy-explore__cta">

                        <span class="vy-label">
                            <?php echo esc_html(get_theme_mod('wl_dcl_tagline', 'Câu chuyện du lịch')); ?>
                        </span>

                        <h3 class="vy-explore__cta-title">
                            <?php echo esc_html(get_theme_mod('wl_dcl_title', 'Khám phá điểm đến phù hợp với bạn')); ?>
                        </h3>

                        <p class="vy-explore__cta-body">
                            <?php echo esc_html(get_theme_mod('wl_dcl_body', 'Tìm kiếm hành trình tiếp theo, khám phá những địa điểm ẩn chưa ai biết và chia sẻ câu chuyện của bạn.')); ?>
                        </p>

                        <!-- Social share -->
                        <div class="vy-explore__social">
                            <span class="vy-explore__social-label"><?php esc_html_e('Chia sẻ', 'voya'); ?></span>
                            <div class="vy-explore__social-links">
                                <?php
                                $share_url = urlencode(home_url('/'));
                                $share_links = [
                                    ['url' => 'https://www.facebook.com/sharer.php?u=' . $share_url, 'icon' => 'fa-facebook-f', 'label' => 'Facebook'],
                                    ['url' => 'https://twitter.com/intent/tweet?url=' . $share_url, 'icon' => 'fa-x-twitter', 'label' => 'Twitter/X'],
                                    ['url' => 'https://pinterest.com/pin/create/button/?url=' . $share_url, 'icon' => 'fa-pinterest-p', 'label' => 'Pinterest'],
                                ];
                                foreach ($share_links as $sl): ?>
                                <a href="#" class="vy-explore__social-link"
                                    data-share="<?php echo esc_url($sl['url']); ?>"
                                    aria-label="<?php echo esc_attr('Chia sẻ lên ' . $sl['label']); ?>">
                                    <i class="fa-brands <?php echo esc_attr($sl['icon']); ?>" aria-hidden="true"></i>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>

                    </div>
                </aside>

            </div><!-- .vy-explore__bottom -->

        </div>
    </section>

</main>

<?php get_footer(); ?>