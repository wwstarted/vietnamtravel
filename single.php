<?php
/**
 * VOYA — single.php (v2)
 *
 * Layout mới theo mẫu asiakingtravel:
 *  - Banner ngắn (~460px) + breadcrumb center (giống archive)
 *  - 2-col: content LEFT (mcol overlap banner) | sidebar RIGHT (sticky)
 *  - Content: title → date created/updated → excerpt → TOC → content → tags/share → author → comments
 *  - Sidebar: Related posts + Latest posts (100×100) + Categories + Tags
 *
 * Giữ nguyên:
 *  - Toàn bộ PHP data variables
 *  - TOC JS (single.js không đổi)
 *  - Reading progress, lightbox, share popup
 *  - Related posts section (đã dời vào sidebar)
 */

get_header();

while (have_posts()):
    the_post();

    // ── Prepare data ─────────────────────────────────────────────
    $post_id = get_the_ID();
    $thumb_id = get_post_thumbnail_id($post_id);

    // Banner: dùng featured image (wl-wide ~1900×700) hoặc custom field
    $banner_url = '';
    $custom_banner_id = get_post_meta($post_id, '_vy_single_banner', true);
    if ($custom_banner_id) {
        $banner_url = wp_get_attachment_image_url($custom_banner_id, 'wl-wide')
            ?: wp_get_attachment_image_url($custom_banner_id, 'full');
    }
    if (!$banner_url && $thumb_id) {
        $banner_url = wp_get_attachment_image_url($thumb_id, 'wl-wide')
            ?: wp_get_attachment_image_url($thumb_id, 'wl-hero')
            ?: wp_get_attachment_image_url($thumb_id, 'full');
    }

    $categories = get_the_category();
    $cat = $categories[0] ?? null;
    $tags = get_the_tags();

    $author_id = get_the_author_meta('ID');
    $author_name = get_the_author();
    $author_bio = get_the_author_meta('description')
        ?: __('Một người yêu du lịch và kể chuyện, chia sẻ hành trình qua từng điểm đến.', 'voya');
    $author_url = get_author_posts_url($author_id);
    $author_avatar = get_avatar_url($author_id, ['size' => 80]);

    $author_facebook = get_the_author_meta('facebook', $author_id);
    $author_twitter = get_the_author_meta('twitter', $author_id);
    $author_instagram = get_the_author_meta('instagram', $author_id);

    // Created / Updated dates
    $created_date = get_the_date('Y-m-d H:i:s');
    $created_nice = get_the_date('j M, Y');
    $modified_date = get_the_modified_date('Y-m-d H:i:s');
    $modified_nice = get_the_modified_date('j M, Y');
    $modified_author = get_the_modified_author();

    // Share URLs
    $post_url_enc = rawurlencode(get_permalink());
    $post_title_enc = rawurlencode(get_the_title());

    // Prev/Next
    $prev_post = get_previous_post();
    $next_post = get_next_post();

    // Blog URL
    $blog_page = get_option('page_for_posts');
    $blog_url = $blog_page ? get_permalink($blog_page) : home_url('/blog/');

    // Reading time
    $reading_time = max(1, (int) ceil(str_word_count(wp_strip_all_tags(get_the_content())) / 200));

    // Excerpt (short desc)
    $short_desc = has_excerpt()
        ? get_the_excerpt()
        : wp_trim_words(wp_strip_all_tags(get_the_content()), 30, '…');
    ?>

<main id="vy-single-main" class="vy-single-main" data-hero>

    <!-- ══════════════════════════════════════════════════
         BANNER — full width, shorter (~460px)
         Breadcrumb centered, NO title here
    ══════════════════════════════════════════════════ -->
    <div class="vy-single-banner" <?php if ($banner_url): ?>
        style="background-image: url('<?php echo esc_url($banner_url); ?>')" <?php endif; ?>>
        <div class="vy-single-banner__overlay" aria-hidden="true"></div>
        <div class="vy-single-banner__content">
            <nav class="vy-single-breadcrumb" aria-label="<?php esc_attr_e('Breadcrumb', 'voya'); ?>">
                <a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Trang chủ', 'voya'); ?></a>
                <span aria-hidden="true">/</span>
                <a href="<?php echo esc_url($blog_url); ?>"><?php esc_html_e('Blog', 'voya'); ?></a>
                <?php if ($cat): ?>
                <span aria-hidden="true">/</span>
                <a href="<?php echo esc_url(get_category_link($cat->term_id)); ?>">
                    <?php echo esc_html($cat->name); ?>
                </a>
                <?php endif; ?>
            </nav>
        </div>
    </div><!-- .vy-single-banner -->


    <!-- ══════════════════════════════════════════════════
         BODY: mcol (content LEFT, overlap banner) | sidebar RIGHT
    ══════════════════════════════════════════════════ -->
    <div class="vy-single-body">
        <div class="vy-container">
            <div class="vy-single-layout">

                <!-- ── CONTENT CARD (mcol, overlaps banner) ── -->
                <div class="vy-single-mcol">

                    <article class="vy-single-article" id="vy-article" itemscope
                        itemtype="https://schema.org/BlogPosting">

                        <meta itemprop="headline" content="<?php the_title_attribute(); ?>">
                        <meta itemprop="datePublished" content="<?php echo esc_attr(get_the_date('c')); ?>">
                        <meta itemprop="author" content="<?php echo esc_attr($author_name); ?>">
                        <?php if ($banner_url): ?>
                        <meta itemprop="image" content="<?php echo esc_url($banner_url); ?>">
                        <?php endif; ?>

                        <!-- Title -->
                        <h1 class="vy-single-title" itemprop="headline">
                            <?php the_title(); ?>
                        </h1>

                        <!-- Date meta: Created / Updated -->
                        <div class="vy-single-dateline">
                            <i class="fa-regular fa-clock" aria-hidden="true"></i>
                            <span>
                                <?php printf(
                                        esc_html__('Tạo bởi %1$s lúc %2$s', 'voya'),
                                        '<strong>' . esc_html($author_name) . '</strong>',
                                        '<time datetime="' . esc_attr($created_date) . '">' . esc_html($created_nice) . '</time>'
                                    ); ?>
                            </span>
                            <?php if ($modified_date !== $created_date && $modified_author): ?>
                            <span class="vy-single-dateline__sep" aria-hidden="true">,</span>
                            <span>
                                <?php printf(
                                            esc_html__('Cập nhật bởi %1$s lúc %2$s', 'voya'),
                                            '<strong>' . esc_html($modified_author) . '</strong>',
                                            '<time datetime="' . esc_attr($modified_date) . '">' . esc_html($modified_nice) . '</time>'
                                        ); ?>
                            </span>
                            <?php endif; ?>
                            <span class="vy-single-dateline__read">
                                · <?php printf(esc_html__('%d phút đọc', 'voya'), $reading_time); ?>
                            </span>
                        </div>

                        <!-- Short description -->
                        <?php if ($short_desc): ?>
                        <p class="vy-single-excerpt" itemprop="description">
                            <?php echo esc_html($short_desc); ?>
                        </p>
                        <?php endif; ?>

                        <!-- TOC placeholder — JS inject từ single.js -->
                        <aside class="vy-toc" id="vy-toc" aria-label="<?php esc_attr_e('Mục lục', 'voya'); ?>"
                            style="display:none;" hidden>
                            <div class="vy-toc__head" role="button" tabindex="0" aria-expanded="true"
                                aria-controls="vy-toc-list">
                                <span class="vy-toc__title">
                                    <i class="fa-solid fa-list-ul" aria-hidden="true"></i>
                                    <?php esc_html_e('Mục lục', 'voya'); ?>
                                </span>
                                <button class="vy-toc__toggle" type="button"
                                    aria-label="<?php esc_attr_e('Thu gọn mục lục', 'voya'); ?>">
                                    <i class="fa-solid fa-chevron-up vy-toc__arrow" aria-hidden="true"></i>
                                </button>
                            </div>
                            <nav class="vy-toc__body" id="vy-toc-list">
                                <ol class="vy-toc__list"></ol>
                            </nav>
                        </aside>

                        <!-- Main content -->
                        <div class="vy-single-entry" itemprop="articleBody">
                            <?php the_content(); ?>
                        </div>

                        <!-- Multi-page -->
                        <?php wp_link_pages([
                                'before' => '<div class="vy-single-pages"><span class="vy-single-pages__label">' . esc_html__('Trang:', 'voya') . '</span>',
                                'after' => '</div>',
                                'link_before' => '<span>',
                                'link_after' => '</span>',
                            ]); ?>

                        <!-- ── Footer: Tags + Share ── -->
                        <footer class="vy-single-footer">

                            <?php if ($tags): ?>
                            <div class="vy-single-tags">
                                <?php foreach ($tags as $tag): ?>
                                <a href="<?php echo esc_url(get_tag_link($tag->term_id)); ?>" class="vy-tag">
                                    <?php echo esc_html($tag->name); ?>
                                </a>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>

                            <div class="vy-single-share" role="group"
                                aria-label="<?php esc_attr_e('Chia sẻ bài viết', 'voya'); ?>">
                                <span class="vy-single-share__label"><?php esc_html_e('Chia sẻ', 'voya'); ?></span>
                                <div class="vy-single-share__links">
                                    <a href="https://www.facebook.com/sharer.php?u=<?php echo $post_url_enc; ?>"
                                        class="vy-share-btn" target="_blank" rel="noopener noreferrer"
                                        aria-label="<?php esc_attr_e('Chia sẻ lên Facebook', 'voya'); ?>">
                                        <i class="fa-brands fa-facebook-f" aria-hidden="true"></i>
                                    </a>
                                    <a href="https://twitter.com/intent/tweet?url=<?php echo $post_url_enc; ?>&text=<?php echo $post_title_enc; ?>"
                                        class="vy-share-btn" target="_blank" rel="noopener noreferrer"
                                        aria-label="<?php esc_attr_e('Chia sẻ lên Twitter', 'voya'); ?>">
                                        <i class="fa-brands fa-x-twitter" aria-hidden="true"></i>
                                    </a>
                                    <a href="https://pinterest.com/pin/create/button/?url=<?php echo $post_url_enc; ?>"
                                        class="vy-share-btn" target="_blank" rel="noopener noreferrer"
                                        aria-label="<?php esc_attr_e('Chia sẻ lên Pinterest', 'voya'); ?>">
                                        <i class="fa-brands fa-pinterest-p" aria-hidden="true"></i>
                                    </a>
                                    <a href="https://t.me/share/url?url=<?php echo $post_url_enc; ?>"
                                        class="vy-share-btn" target="_blank" rel="noopener noreferrer"
                                        aria-label="<?php esc_attr_e('Chia sẻ lên Telegram', 'voya'); ?>">
                                        <i class="fa-brands fa-telegram" aria-hidden="true"></i>
                                    </a>
                                </div>
                            </div>

                        </footer>

                        <!-- ── Prev / Next ── -->
                        <?php if ($prev_post || $next_post): ?>
                        <nav class="vy-single-nav" aria-label="<?php esc_attr_e('Điều hướng bài viết', 'voya'); ?>">
                            <div class="vy-single-nav__inner">

                                <?php if ($prev_post):
                                            $prev_thumb = get_the_post_thumbnail_url($prev_post->ID, [76, 50]); ?>
                                <a class="vy-nav-item vy-nav-item--prev"
                                    href="<?php echo esc_url(get_permalink($prev_post->ID)); ?>" rel="prev">
                                    <i class="fa-solid fa-arrow-left vy-nav-item__arrow" aria-hidden="true"></i>
                                    <?php if ($prev_thumb): ?>
                                    <div class="vy-nav-item__thumb">
                                        <img src="<?php echo esc_url($prev_thumb); ?>"
                                            alt="<?php echo esc_attr($prev_post->post_title); ?>" width="76" height="50"
                                            loading="lazy">
                                    </div>
                                    <?php endif; ?>
                                    <div class="vy-nav-item__text">
                                        <span
                                            class="vy-nav-item__label"><?php esc_html_e('Bài trước', 'voya'); ?></span>
                                        <span
                                            class="vy-nav-item__title"><?php echo esc_html(wp_trim_words($prev_post->post_title, 8, '…')); ?></span>
                                    </div>
                                </a>
                                <?php else: ?>
                                <div class="vy-nav-item vy-nav-item--empty"></div>
                                <?php endif; ?>

                                <span class="vy-single-nav__divider" aria-hidden="true"></span>

                                <?php if ($next_post):
                                            $next_thumb = get_the_post_thumbnail_url($next_post->ID, [76, 50]); ?>
                                <a class="vy-nav-item vy-nav-item--next"
                                    href="<?php echo esc_url(get_permalink($next_post->ID)); ?>" rel="next">
                                    <div class="vy-nav-item__text">
                                        <span
                                            class="vy-nav-item__label"><?php esc_html_e('Bài tiếp theo', 'voya'); ?></span>
                                        <span
                                            class="vy-nav-item__title"><?php echo esc_html(wp_trim_words($next_post->post_title, 8, '…')); ?></span>
                                    </div>
                                    <?php if ($next_thumb): ?>
                                    <div class="vy-nav-item__thumb">
                                        <img src="<?php echo esc_url($next_thumb); ?>"
                                            alt="<?php echo esc_attr($next_post->post_title); ?>" width="76" height="50"
                                            loading="lazy">
                                    </div>
                                    <?php endif; ?>
                                    <i class="fa-solid fa-arrow-right vy-nav-item__arrow" aria-hidden="true"></i>
                                </a>
                                <?php else: ?>
                                <div class="vy-nav-item vy-nav-item--empty"></div>
                                <?php endif; ?>

                            </div>
                        </nav>
                        <?php endif; ?>

                        <!-- ── Author Box ── -->
                        <div class="vy-author-box">
                            <div class="vy-author-box__avatar">
                                <a href="<?php echo esc_url($author_url); ?>">
                                    <img src="<?php echo esc_url($author_avatar); ?>"
                                        alt="<?php echo esc_attr($author_name); ?>" width="80" height="80"
                                        loading="lazy">
                                </a>
                            </div>
                            <div class="vy-author-box__body">
                                <div class="vy-author-box__top">
                                    <h5 class="vy-author-box__name">
                                        <a href="<?php echo esc_url($author_url); ?>">
                                            <?php echo esc_html($author_name); ?>
                                        </a>
                                    </h5>
                                    <div class="vy-author-box__social">
                                        <?php if ($author_facebook): ?>
                                        <a href="<?php echo esc_url($author_facebook); ?>" target="_blank"
                                            rel="noopener noreferrer" aria-label="Facebook">
                                            <i class="fa-brands fa-facebook-f" aria-hidden="true"></i>
                                        </a>
                                        <?php endif; ?>
                                        <?php if ($author_twitter): ?>
                                        <a href="<?php echo esc_url($author_twitter); ?>" target="_blank"
                                            rel="noopener noreferrer" aria-label="Twitter/X">
                                            <i class="fa-brands fa-x-twitter" aria-hidden="true"></i>
                                        </a>
                                        <?php endif; ?>
                                        <?php if ($author_instagram): ?>
                                        <a href="<?php echo esc_url($author_instagram); ?>" target="_blank"
                                            rel="noopener noreferrer" aria-label="Instagram">
                                            <i class="fa-brands fa-instagram" aria-hidden="true"></i>
                                        </a>
                                        <?php endif; ?>
                                        <?php if (!$author_facebook && !$author_twitter && !$author_instagram): ?>
                                        <a href="<?php echo esc_url($author_url); ?>" class="vy-author-box__all-posts">
                                            <?php esc_html_e('Xem tất cả bài viết', 'voya'); ?>
                                            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <p class="vy-author-box__bio"><?php echo esc_html($author_bio); ?></p>
                            </div>
                        </div>

                        <!-- ── Comments ── -->
                        <?php if (comments_open() || get_comments_number()): ?>
                        <div class="vy-comments-wrap">
                            <?php comments_template(); ?>
                        </div>
                        <?php endif; ?>

                    </article>

                </div><!-- .vy-single-mcol -->


                <!-- ── SIDEBAR (sticky, bên phải) ── -->
                <aside class="vy-single-sidebar" id="vy-sidebar" role="complementary"
                    aria-label="<?php esc_attr_e('Sidebar', 'voya'); ?>">
                    <div class="vy-single-sidebar__sticky">

                        <!-- Related posts -->
                        <?php
                            $related_args = $cat
                                ? ['category__in' => [$cat->term_id], 'post__not_in' => [$post_id], 'posts_per_page' => 6, 'orderby' => 'rand', 'post_status' => 'publish', 'no_found_rows' => true]
                                : ['post__not_in' => [$post_id], 'posts_per_page' => 6, 'orderby' => 'date', 'post_status' => 'publish', 'no_found_rows' => true];
                            $related_q = new WP_Query($related_args);
                            if ($related_q->have_posts()):
                                ?>
                        <div class="vy-sidebar-widget">
                            <h5 class="vy-widget-title"><?php esc_html_e('Bài viết liên quan', 'voya'); ?></h5>
                            <div class="vy-sidebar-post-list">
                                <?php while ($related_q->have_posts()):
                                            $related_q->the_post();
                                            $rel_img = get_the_post_thumbnail_url(get_the_ID(), 'thumbnail')
                                                ?: get_the_post_thumbnail_url(get_the_ID(), 'full');
                                            ?>
                                <div class="vy-sidebar-post-item">
                                    <a href="<?php the_permalink(); ?>" class="vy-sidebar-post-link">
                                        <div class="vy-sidebar-post-thumb">
                                            <?php if ($rel_img): ?>
                                            <img src="<?php echo esc_url($rel_img); ?>"
                                                alt="<?php the_title_attribute(); ?>" loading="lazy" width="100"
                                                height="100">
                                            <?php else: ?>
                                            <div class="vy-sidebar-post-thumb__placeholder"></div>
                                            <?php endif; ?>
                                        </div>
                                        <span class="vy-sidebar-post-title"><?php the_title(); ?></span>
                                    </a>
                                </div>
                                <?php endwhile;
                                        wp_reset_postdata(); ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Latest posts -->
                        <?php
                            $latest_q = new WP_Query([
                                'posts_per_page' => 6,
                                'post__not_in' => [$post_id],
                                'post_status' => 'publish',
                                'orderby' => 'date',
                                'order' => 'DESC',
                                'no_found_rows' => true,
                                'ignore_sticky_posts' => true,
                            ]);
                            if ($latest_q->have_posts()):
                                ?>
                        <div class="vy-sidebar-widget">
                            <h5 class="vy-widget-title"><?php esc_html_e('Bài viết mới nhất', 'voya'); ?></h5>
                            <div class="vy-sidebar-post-list">
                                <?php while ($latest_q->have_posts()):
                                            $latest_q->the_post();
                                            $lat_img = get_the_post_thumbnail_url(get_the_ID(), 'thumbnail')
                                                ?: get_the_post_thumbnail_url(get_the_ID(), 'full');
                                            ?>
                                <div class="vy-sidebar-post-item">
                                    <a href="<?php the_permalink(); ?>" class="vy-sidebar-post-link">
                                        <div class="vy-sidebar-post-thumb">
                                            <?php if ($lat_img): ?>
                                            <img src="<?php echo esc_url($lat_img); ?>"
                                                alt="<?php the_title_attribute(); ?>" loading="lazy" width="100"
                                                height="100">
                                            <?php else: ?>
                                            <div class="vy-sidebar-post-thumb__placeholder"></div>
                                            <?php endif; ?>
                                        </div>
                                        <span class="vy-sidebar-post-title"><?php the_title(); ?></span>
                                    </a>
                                </div>
                                <?php endwhile;
                                        wp_reset_postdata(); ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Categories -->
                        <div class="vy-sidebar-widget">
                            <h5 class="vy-widget-title"><?php esc_html_e('Danh mục', 'voya'); ?></h5>
                            <ul class="vy-sidebar-cats">
                                <?php wp_list_categories([
                                        'show_count' => true,
                                        'title_li' => '',
                                        'orderby' => 'count',
                                        'order' => 'DESC',
                                        'number' => 10,
                                        'hide_empty' => true,
                                    ]); ?>
                            </ul>
                        </div>

                        <!-- Tags -->
                        <?php if ($tags): ?>
                        <div class="vy-sidebar-widget">
                            <h5 class="vy-widget-title"><?php esc_html_e('Tags', 'voya'); ?></h5>
                            <div class="vy-sidebar-tagcloud">
                                <?php foreach ($tags as $tag): ?>
                                <a href="<?php echo esc_url(get_tag_link($tag->term_id)); ?>" class="vy-archive-tag">
                                    <?php echo esc_html($tag->name); ?>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Dynamic sidebar (optional widgets) -->
                        <?php if (is_active_sidebar('single-sidebar')):
                                dynamic_sidebar('single-sidebar');
                            endif; ?>

                    </div><!-- .vy-single-sidebar__sticky -->
                </aside><!-- .vy-single-sidebar -->

            </div><!-- .vy-single-layout -->
        </div><!-- .vy-container -->
    </div><!-- .vy-single-body -->

</main>

<?php endwhile; ?>
<?php get_footer(); ?>