<?php
/**
 * VOYA — home.php (v2)
 * Blog index — tất cả bài viết, không filter theo category
 *
 * WordPress dùng file này khi:
 *   Settings → Reading → Your homepage displays → A static page
 *   → Posts page: [Blog page]
 *
 * Layout: Hero banner (banner mặc định từ Customizer)
 *         + 2-col sidebar/mcol (giống archive-v2)
 *         + Top blogs (page 1 only)
 *         + Latest articles list 2-col + pagination
 *
 * Sidebar categories: link tới get_category_link() → dẫn tới archive.php
 */

get_header();

global $wpdb;

// ── Sort state ────────────────────────────────────────────────
$current_sort = isset($_GET['sort']) ? sanitize_key($_GET['sort']) : 'date_desc';
$current_year = isset($_GET['year']) ? intval($_GET['year']) : 0;

$sort_map = [
    'date_desc' => ['orderby' => 'date', 'order' => 'DESC'],
    'date_asc' => ['orderby' => 'date', 'order' => 'ASC'],
    'title_asc' => ['orderby' => 'title', 'order' => 'ASC'],
    'popular' => ['orderby' => 'comment_count', 'order' => 'DESC'],
];
$sort = $sort_map[$current_sort] ?? $sort_map['date_desc'];

// ── Sidebar data ──────────────────────────────────────────────
$all_cats = get_categories([
    'hide_empty' => true,
    'orderby' => 'count',
    'order' => 'DESC',
    'number' => 20,
]);

$all_tags = get_tags([
    'hide_empty' => true,
    'orderby' => 'count',
    'order' => 'DESC',
    'number' => 60,
]);

// ── Main query ────────────────────────────────────────────────
$paged = max(1, get_query_var('paged') ?: (get_query_var('page') ?: 1));
$per_page = 12;

$q_args = [
    'post_type' => 'post',
    'post_status' => 'publish',
    'posts_per_page' => $per_page,
    'paged' => $paged,
    'orderby' => $sort['orderby'],
    'order' => $sort['order'],
    'no_found_rows' => false,
];
if ($current_year)
    $q_args['year'] = $current_year;

$blog_q = new WP_Query($q_args);
$total_posts = $blog_q->found_posts;
$max_pages = $blog_q->max_num_pages;
$all_posts = $blog_q->posts;
wp_reset_postdata();

// ── Page title: từ blog page hoặc Customizer ─────────────────
$blog_page_id = get_option('page_for_posts');
$page_title = $blog_page_id
    ? get_the_title($blog_page_id)
    : get_theme_mod('vy_archive_default_title', __('Travel Blog', 'voya'));

// ── Hero banner: dùng banner mặc định từ Customizer ──────────
$default_banner_id = get_theme_mod('vy_archive_default_banner');
$hero_banner_url = $default_banner_id
    ? wp_get_attachment_image_url($default_banner_id, 'wl-wide')
    ?: wp_get_attachment_image_url($default_banner_id, 'full')
    : '';

// ── Base URL của trang blog ───────────────────────────────────
$base_url = $blog_page_id
    ? get_permalink($blog_page_id)
    : home_url('/blog/');

// ── Sort URL helper ───────────────────────────────────────────
function vy_home_sort_url($base, $sort_key, $year = 0)
{
    $params = array_filter([
        'sort' => ($sort_key !== 'date_desc') ? $sort_key : null,
        'year' => $year ?: null,
    ]);
    return $params ? add_query_arg($params, $base) : $base;
}

// ── Top blogs (page 1 only) ───────────────────────────────────
$top_blogs_posts = [];
if ($paged === 1) {
    $top_blogs_query = wl_get_section_query('top_blogs', ['posts_per_page' => 6]);
    if ($top_blogs_query && $top_blogs_query->have_posts()) {
        $top_blogs_posts = $top_blogs_query->posts;
        wp_reset_postdata();
    }
}
?>

<main id="vy-archive-main" class="vy-archive-main">

    <!-- ══════════════════════════════════════════════════
         HERO BANNER — dùng banner mặc định từ Customizer
    ══════════════════════════════════════════════════ -->
    <div class="vy-archive-hero-banner" <?php if ($hero_banner_url): ?>
        style="background-image: url('<?php echo esc_url($hero_banner_url); ?>')" <?php endif; ?>>
        <div class="vy-archive-hero-banner__overlay" aria-hidden="true"></div>
        <div class="vy-archive-hero-banner__content">
            <h1 class="vy-archive-hero-banner__title">
                <?php echo esc_html($page_title); ?>
            </h1>
            <nav class="vy-archive-breadcrumb" aria-label="<?php esc_attr_e('Breadcrumb', 'voya'); ?>">
                <a href="<?php echo esc_url(home_url('/')); ?>">
                    <?php esc_html_e('Trang chủ', 'voya'); ?>
                </a>
                <span aria-hidden="true">/</span>
                <span><?php esc_html_e('Blog', 'voya'); ?></span>
            </nav>
        </div>
    </div><!-- .vy-archive-hero-banner -->


    <!-- ══════════════════════════════════════════════════
         MAIN BODY — sidebar + mcol (mcol overlap hero)
    ══════════════════════════════════════════════════ -->
    <div class="vy-archive-body">
        <div class="vy-container">
            <div class="vy-archive-layout">

                <!-- ── SIDEBAR ── -->
                <aside class="vy-archive-sidebar" aria-label="<?php esc_attr_e('Bộ lọc', 'voya'); ?>">
                    <div class="vy-archive-sidebar__sticky">

                        <!-- Widget: Danh mục -->
                        <div class="vy-archive-widget">
                            <div class="vy-archive-widget__title">
                                <?php esc_html_e('Danh mục', 'voya'); ?>
                            </div>
                            <div class="vy-archive-widget__body">
                                <div class="vy-filter-cat-list">

                                    <!-- Tất cả — active vì đang ở trang blog tổng -->
                                    <div class="vy-filter-cat-item">
                                        <a href="<?php echo esc_url($base_url); ?>" class="vy-filter-cat-link is-active"
                                            aria-current="page">
                                            <span class="vy-filter-cat-radio" aria-hidden="true">
                                                <i class="fa-solid fa-circle-dot"></i>
                                            </span>
                                            <?php esc_html_e('Tất cả', 'voya'); ?>
                                        </a>
                                    </div>

                                    <!-- Các category → dẫn tới archive.php của từng cat -->
                                    <?php foreach ($all_cats as $ci): ?>
                                    <div class="vy-filter-cat-item">
                                        <a href="<?php echo esc_url(get_category_link($ci->term_id)); ?>"
                                            class="vy-filter-cat-link">
                                            <span class="vy-filter-cat-radio" aria-hidden="true">
                                                <i class="fa-regular fa-circle"></i>
                                            </span>
                                            <?php echo esc_html($ci->name); ?>
                                        </a>
                                    </div>
                                    <?php endforeach; ?>

                                </div>

                                <!-- Sort -->
                                <div class="vy-archive-widget__divider"></div>
                                <div class="vy-archive-widget__subtitle">
                                    <?php esc_html_e('Sắp xếp', 'voya'); ?>
                                </div>
                                <div class="vy-filter-cat-list">
                                    <?php
                                    $sort_options = [
                                        'date_desc' => __('Mới nhất', 'voya'),
                                        'date_asc' => __('Cũ nhất', 'voya'),
                                        'title_asc' => __('A → Z', 'voya'),
                                        'popular' => __('Phổ biến', 'voya'),
                                    ];
                                    foreach ($sort_options as $sk => $sl):
                                        $sort_url = vy_home_sort_url($base_url, $sk, $current_year);
                                        $is_active = $current_sort === $sk;
                                        ?>
                                    <div class="vy-filter-cat-item">
                                        <a href="<?php echo esc_url($sort_url); ?>"
                                            class="vy-filter-cat-link <?php echo $is_active ? 'is-active' : ''; ?>">
                                            <span class="vy-filter-cat-radio" aria-hidden="true">
                                                <?php if ($is_active): ?>
                                                <i class="fa-solid fa-circle-dot"></i>
                                                <?php else: ?>
                                                <i class="fa-regular fa-circle"></i>
                                                <?php endif; ?>
                                            </span>
                                            <?php echo esc_html($sl); ?>
                                        </a>
                                    </div>
                                    <?php endforeach; ?>
                                </div>

                            </div>
                        </div><!-- .vy-archive-widget -->

                        <!-- Widget: Tags -->
                        <?php if (!empty($all_tags)): ?>
                        <div class="vy-archive-widget">
                            <div class="vy-archive-widget__title">
                                <?php esc_html_e('Tags', 'voya'); ?>
                            </div>
                            <div class="vy-archive-widget__body">
                                <div class="vy-archive-tags">
                                    <?php foreach ($all_tags as $tag): ?>
                                    <a href="<?php echo esc_url(get_tag_link($tag->term_id)); ?>" class="vy-archive-tag"
                                        title="<?php echo esc_attr($tag->name); ?>">
                                        <?php echo esc_html($tag->name); ?>
                                    </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                    </div><!-- .vy-archive-sidebar__sticky -->
                </aside><!-- .vy-archive-sidebar -->


                <!-- ── MCOL — overlap hero ── -->
                <div class="vy-archive-mcol">

                    <!-- Top Blogs (page 1 only) -->
                    <?php if ($paged === 1 && !empty($top_blogs_posts)): ?>
                    <section class="vy-archive-topblogs">
                        <h2 class="vy-archive-topblogs__title">
                            <?php
                                $tb_title = get_theme_mod('vy_archive_topblogs_title', '');
                                echo esc_html($tb_title ?: sprintf(
                                    __('Top %d bài viết phổ biến nhất', 'voya'),
                                    count($top_blogs_posts)
                                ));
                                ?>
                        </h2>
                        <div class="vy-archive-topblogs__grid">
                            <?php foreach ($top_blogs_posts as $tb):
                                    $tb_img = get_the_post_thumbnail_url($tb->ID, 'wl-card')
                                        ?: get_the_post_thumbnail_url($tb->ID, 'full');
                                    ?>
                            <div class="vy-archive-topblogs__item">
                                <div class="vy-archive-topblogs__inner">
                                    <a href="<?php echo esc_url(get_permalink($tb->ID)); ?>"
                                        class="vy-archive-topblogs__img-link"
                                        title="<?php echo esc_attr($tb->post_title); ?>">
                                        <div class="vy-archive-topblogs__img-wrap">
                                            <?php if ($tb_img): ?>
                                            <img src="<?php echo esc_url($tb_img); ?>"
                                                alt="<?php echo esc_attr($tb->post_title); ?>" loading="lazy"
                                                class="vy-archive-topblogs__img">
                                            <?php else: ?>
                                            <div class="vy-archive-topblogs__img-placeholder"></div>
                                            <?php endif; ?>
                                        </div>
                                    </a>
                                    <div class="vy-archive-topblogs__ct">
                                        <h4 class="vy-archive-topblogs__item-title">
                                            <a href="<?php echo esc_url(get_permalink($tb->ID)); ?>">
                                                <?php echo esc_html($tb->post_title); ?>
                                            </a>
                                        </h4>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                    <?php endif; ?>


                    <!-- Latest Articles -->
                    <section class="vy-archive-list-section">

                        <h2 class="vy-archive-list-section__title">
                            <?php esc_html_e('Bài viết mới nhất', 'voya'); ?>
                            <?php if ($total_posts > 0): ?>
                            <span class="vy-archive-list-section__count">
                                (<?php echo number_format_i18n($total_posts); ?>)
                            </span>
                            <?php endif; ?>
                        </h2>

                        <?php if (!empty($all_posts)): ?>

                        <div class="vy-archive-list" id="vy-archive-grid">
                            <?php foreach ($all_posts as $p):
                                    $p_img = get_the_post_thumbnail_url($p->ID, 'wl-card')
                                        ?: get_the_post_thumbnail_url($p->ID, 'full');
                                    $p_exc = get_post_field('post_excerpt', $p->ID)
                                        ?: wp_trim_words(get_post_field('post_content', $p->ID), 20, '…');
                                    $p_author = get_the_author_meta('display_name', $p->post_author);
                                    ?>
                            <article class="vy-archive-list-item" itemscope itemtype="https://schema.org/BlogPosting">

                                <a href="<?php echo esc_url(get_permalink($p->ID)); ?>"
                                    class="vy-archive-list-item__img-link">
                                    <div class="vy-archive-list-item__img-wrap">
                                        <?php if ($p_img): ?>
                                        <img src="<?php echo esc_url($p_img); ?>"
                                            alt="<?php echo esc_attr($p->post_title); ?>" loading="lazy"
                                            class="vy-archive-list-item__img" itemprop="image">
                                        <?php else: ?>
                                        <div class="vy-archive-list-item__img-placeholder"></div>
                                        <?php endif; ?>
                                    </div>
                                </a>

                                <div class="vy-archive-list-item__ct">
                                    <h4 class="vy-archive-list-item__title" itemprop="headline">
                                        <a href="<?php echo esc_url(get_permalink($p->ID)); ?>">
                                            <?php echo esc_html($p->post_title); ?>
                                        </a>
                                    </h4>
                                    <div class="vy-archive-list-item__btm">
                                        <div class="vy-archive-list-item__params">
                                            <span class="vy-archive-list-item__date">
                                                <i class="fa-regular fa-clock" aria-hidden="true"></i>
                                                <time datetime="<?php echo esc_attr(get_the_date('Y-m-d', $p->ID)); ?>"
                                                    itemprop="datePublished">
                                                    <?php echo esc_html(get_the_date('d F, Y', $p->ID)); ?>
                                                </time>
                                            </span>
                                            <?php if ($p_author): ?>
                                            <span class="vy-archive-list-item__author">
                                                <i class="fa-regular fa-user" aria-hidden="true"></i>
                                                <a href="<?php echo esc_url(get_author_posts_url($p->post_author)); ?>"
                                                    itemprop="author">
                                                    <?php echo esc_html($p_author); ?>
                                                </a>
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="vy-archive-list-item__desc" itemprop="description">
                                            <?php echo esc_html(wp_trim_words($p_exc, 20, '…')); ?>
                                        </p>
                                    </div>
                                </div>

                            </article>
                            <?php endforeach; ?>
                        </div><!-- .vy-archive-list -->

                        <!-- Pagination -->
                        <?php if ($max_pages > 1):
                                $pagination_args = [
                                    'base' => add_query_arg('paged', '%#%', $base_url),
                                    'format' => '',
                                    'current' => $paged,
                                    'total' => $max_pages,
                                    'prev_text' => '<i class="fa-solid fa-chevron-left" aria-hidden="true"></i>',
                                    'next_text' => '<i class="fa-solid fa-chevron-right" aria-hidden="true"></i>',
                                    'type' => 'array',
                                    'end_size' => 2,
                                    'mid_size' => 2,
                                ];
                                $pages = paginate_links($pagination_args);
                                if ($pages): ?>
                        <nav class="vy-archive-pagination" aria-label="<?php esc_attr_e('Phân trang', 'voya'); ?>">
                            <ul class="vy-archive-pagination__list">
                                <?php foreach ($pages as $pg): ?>
                                <li class="vy-archive-pagination__item"><?php echo $pg; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </nav>
                        <?php endif; endif; ?>

                        <?php else: ?>
                        <div class="vy-archive-empty">
                            <div class="vy-archive-empty__icon" aria-hidden="true">
                                <i class="fa-regular fa-newspaper"></i>
                            </div>
                            <h3 class="vy-archive-empty__title">
                                <?php esc_html_e('Chưa có bài viết nào', 'voya'); ?>
                            </h3>
                            <p class="vy-archive-empty__text">
                                <?php esc_html_e('Những hành trình tuyệt vời đang được chuẩn bị. Hãy quay lại sớm nhé!', 'voya'); ?>
                            </p>
                            <a href="<?php echo esc_url(home_url('/')); ?>" class="vy-btn vy-btn--primary">
                                <?php esc_html_e('Về trang chủ', 'voya'); ?>
                            </a>
                        </div>
                        <?php endif; ?>

                    </section>

                </div><!-- .vy-archive-mcol -->

            </div><!-- .vy-archive-layout -->
        </div><!-- .vy-container -->
    </div><!-- .vy-archive-body -->

</main>

<?php get_footer(); ?>