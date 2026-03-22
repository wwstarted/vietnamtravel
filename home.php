<?php
/**
 * VOYA — home.php
 * Blog index page — tất cả bài viết, không filter
 *
 * WordPress dùng file này khi:
 *   Settings → Reading → Your homepage displays → A static page
 *   → Posts page: [Blog page]
 *
 * Khác archive.php:
 *  - Không có filter bar (no cat/sort/year)
 *  - Không có breadcrumb category
 *  - Header đơn giản hơn
 *  - Load More vẫn dùng admin-ajax.php → action=vy_load_posts
 */

get_header();

global $wpdb;

$paged = max(1, get_query_var('paged') ?: (get_query_var('page') ?: 1));
$per_page = get_option('posts_per_page', 9);

$q = new WP_Query([
    'post_type' => 'post',
    'post_status' => 'publish',
    'posts_per_page' => $per_page,
    'paged' => $paged,
    'orderby' => 'date',
    'order' => 'DESC',
    'no_found_rows' => false,
]);

$total_posts = $q->found_posts;
$max_pages = $q->max_num_pages;
$all_posts = $q->posts;
wp_reset_postdata();

$shown_now = count($all_posts);

// Available years for Load More meta
$years_raw = $wpdb->get_col(
    "SELECT DISTINCT YEAR(post_date) FROM {$wpdb->posts}
     WHERE post_status='publish' AND post_type='post'
     ORDER BY post_date DESC LIMIT 1"
);
?>

<main id="vy-home-blog-main" class="vy-archive-main">

    <!-- ============================================================
         BLOG HEADER — minimal, no breadcrumb
    ============================================================ -->
    <div class="vy-archive-header vy-blog-header">
        <div class="vy-container">
            <div class="vy-archive-header__inner">

                <div class="vy-archive-header__left">
                    <span class="vy-label"><?php esc_html_e('Tất cả bài viết', 'voya'); ?></span>
                    <h1 class="vy-archive-header__title">
                        <?php esc_html_e('Blog', 'voya'); ?>
                        <em><?php esc_html_e('du lịch', 'voya'); ?></em>
                    </h1>
                    <p class="vy-archive-header__desc">
                        <?php esc_html_e('Khám phá những hành trình, trải nghiệm và bí kíp du lịch từ khắp nơi trên thế giới.', 'voya'); ?>
                    </p>
                </div>

                <div class="vy-archive-header__right">
                    <div class="vy-archive-header__count">
                        <span class="vy-archive-header__count-num">
                            <?php echo number_format_i18n($total_posts); ?>
                        </span>
                        <span class="vy-archive-header__count-label">
                            <?php esc_html_e('bài viết', 'voya'); ?>
                        </span>
                    </div>
                </div>

            </div>
        </div>
    </div><!-- .vy-blog-header -->


    <!-- ============================================================
         BLOG BODY
    ============================================================ -->
    <div class="vy-archive-body">
        <div class="vy-container">

            <?php if (!empty($all_posts)):

                $hero_post = $all_posts[0];
                $remaining = array_slice($all_posts, 1);

                $hero_img = get_the_post_thumbnail_url($hero_post->ID, 'wl-hero')
                    ?: get_the_post_thumbnail_url($hero_post->ID, 'full');
                $hero_cats = get_the_category($hero_post->ID);
                $hero_cat = $hero_cats[0] ?? null;
                $hero_exc = get_post_field('post_excerpt', $hero_post->ID)
                    ?: wp_trim_words(get_post_field('post_content', $hero_post->ID), 22, '…');
                ?>

            <!-- ── Editorial Hero (bài đầu tiên) ── -->
            <div class="vy-archive-hero">
                <a class="vy-archive-hero__img-link" href="<?php echo esc_url(get_permalink($hero_post->ID)); ?>"
                    tabindex="-1" aria-hidden="true">
                    <?php if ($hero_img): ?>
                    <img src="<?php echo esc_url($hero_img); ?>" alt="<?php echo esc_attr($hero_post->post_title); ?>"
                        class="vy-archive-hero__img" loading="eager">
                    <?php else: ?>
                    <div class="vy-archive-hero__img-placeholder"></div>
                    <?php endif; ?>
                    <div class="vy-archive-hero__overlay" aria-hidden="true"></div>
                </a>

                <div class="vy-archive-hero__content">

                    <?php if ($hero_cat): ?>
                    <a href="<?php echo esc_url(get_category_link($hero_cat->term_id)); ?>"
                        class="vy-archive-hero__cat">
                        <?php echo esc_html($hero_cat->name); ?>
                    </a>
                    <?php endif; ?>

                    <h2 class="vy-archive-hero__title">
                        <a href="<?php echo esc_url(get_permalink($hero_post->ID)); ?>">
                            <?php echo esc_html($hero_post->post_title); ?>
                        </a>
                    </h2>

                    <p class="vy-archive-hero__excerpt">
                        <?php echo esc_html(wp_trim_words($hero_exc, 22, '…')); ?>
                    </p>

                    <div class="vy-archive-hero__meta">
                        <span><?php echo esc_html(get_the_author_meta('display_name', $hero_post->post_author)); ?></span>
                        <span class="vy-archive-hero__dot" aria-hidden="true"></span>
                        <time datetime="<?php echo esc_attr(get_the_date('Y-m-d', $hero_post->ID)); ?>">
                            <?php echo esc_html(get_the_date('j M, Y', $hero_post->ID)); ?>
                        </time>
                    </div>

                    <a href="<?php echo esc_url(get_permalink($hero_post->ID)); ?>"
                        class="vy-btn vy-btn--ghost-white vy-archive-hero__cta">
                        <?php esc_html_e('Đọc bài viết', 'voya'); ?>
                        <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                    </a>

                </div>
            </div><!-- .vy-archive-hero -->


            <!-- ── Grid: phần còn lại ── -->
            <div class="vy-archive-grid" id="vy-archive-grid">
                <?php foreach ($remaining as $p):
                            vy_render_archive_card($p);
                        endforeach; ?>
            </div>


            <!-- ── Load More ── -->
            <div class="vy-load-more-wrap" id="vy-load-more-wrap"
                <?php echo $max_pages <= 1 ? 'style="display:none"' : ''; ?>>

                <div class="vy-load-more-progress" aria-hidden="true">
                    <div class="vy-load-more-progress__bar" style="width:<?php echo $total_posts > 0
                                     ? round($shown_now / $total_posts * 100)
                                     : 100; ?>%">
                    </div>
                </div>

                <p class="vy-load-more-count">
                    <?php printf(
                                esc_html__('Đang hiện %1$s / %2$s bài viết', 'voya'),
                                '<span id="vy-shown-count">' . esc_html($shown_now) . '</span>',
                                '<strong>' . number_format_i18n($total_posts) . '</strong>'
                            ); ?>
                </p>

                <button class="vy-load-more-btn" id="vy-load-more-btn" type="button" data-page="1"
                    data-max-pages="<?php echo esc_attr($max_pages); ?>"
                    data-per-page="<?php echo esc_attr($per_page); ?>"
                    data-total="<?php echo esc_attr($total_posts); ?>" data-cat="0" data-sort="date_desc" data-year="0">
                    <span class="vy-load-more-btn__text">
                        <?php esc_html_e('Xem thêm bài viết', 'voya'); ?>
                    </span>
                    <span class="vy-load-more-btn__spinner" aria-hidden="true">
                        <i class="fa-solid fa-circle-notch fa-spin"></i>
                    </span>
                </button>

            </div><!-- .vy-load-more-wrap -->


            <?php else: ?>

            <!-- Empty state -->
            <div class="vy-archive-empty">
                <div class="vy-archive-empty__icon" aria-hidden="true">
                    <i class="fa-regular fa-newspaper"></i>
                </div>
                <h2 class="vy-archive-empty__title">
                    <?php esc_html_e('Chưa có bài viết nào', 'voya'); ?>
                </h2>
                <p class="vy-archive-empty__text">
                    <?php esc_html_e('Những hành trình tuyệt vời đang được chuẩn bị. Hãy quay lại sớm nhé!', 'voya'); ?>
                </p>
                <a href="<?php echo esc_url(home_url('/')); ?>" class="vy-btn vy-btn--primary">
                    <?php esc_html_e('Về trang chủ', 'voya'); ?>
                </a>
            </div>

            <?php endif; ?>

        </div>
    </div><!-- .vy-archive-body -->

</main>

<?php get_footer(); ?>