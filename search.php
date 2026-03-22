<?php
/**
 * VOYA — search.php (v2)
 * Layout giống archive-v2: banner + sidebar LEFT + mcol RIGHT (overlap banner)
 *
 * Giữ nguyên: toàn bộ data/query logic từ search.php cũ
 * Thay đổi: layout, CSS class → dùng chung archive.css
 */

get_header();

$search_query = get_search_query(); // sanitized by WP
$paged = max(1, get_query_var('paged') ?: (get_query_var('page') ?: 1));
$per_page = 12;

$search_args = [
    'post_type' => 'post',
    'post_status' => 'publish',
    's' => $search_query,
    'posts_per_page' => $per_page,
    'paged' => $paged,
    'no_found_rows' => false,
];

$search_q = new WP_Query($search_args);
$total_posts = $search_q->found_posts;
$max_pages = $search_q->max_num_pages;
$all_posts = $search_q->posts;
wp_reset_postdata();

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

// ── Blog base URL ─────────────────────────────────────────────
$blog_page_id = get_option('page_for_posts');
$base_url = $blog_page_id
    ? get_permalink($blog_page_id)
    : home_url('/blog/');

// ── Default banner ────────────────────────────────────────────
$default_banner_id = get_theme_mod('vy_archive_default_banner');
$hero_banner_url = $default_banner_id
    ? wp_get_attachment_image_url($default_banner_id, 'wl-wide')
    ?: wp_get_attachment_image_url($default_banner_id, 'full')
    : '';
?>

<main id="vy-search-main" class="vy-archive-main">

    <!-- ══════════════════════════════════════════════════
         BANNER — dùng chung archive hero banner style
         Title = từ khoá tìm kiếm
    ══════════════════════════════════════════════════ -->
    <div class="vy-archive-hero-banner vy-search-banner" <?php if ($hero_banner_url): ?>
        style="background-image: url('<?php echo esc_url($hero_banner_url); ?>')" <?php endif; ?>>
        <div class="vy-archive-hero-banner__overlay" aria-hidden="true"></div>
        <div class="vy-archive-hero-banner__content">

            <?php if ($search_query): ?>
            <p class="vy-search-banner__label">
                <?php esc_html_e('Kết quả tìm kiếm cho', 'voya'); ?>
            </p>
            <h1 class="vy-archive-hero-banner__title vy-search-banner__title">
                "<?php echo esc_html($search_query); ?>"
            </h1>
            <?php else: ?>
            <h1 class="vy-archive-hero-banner__title">
                <?php esc_html_e('Tìm kiếm', 'voya'); ?>
            </h1>
            <?php endif; ?>

            <!-- Breadcrumb -->
            <nav class="vy-archive-breadcrumb" aria-label="<?php esc_attr_e('Breadcrumb', 'voya'); ?>">
                <a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Trang chủ', 'voya'); ?></a>
                <span aria-hidden="true">/</span>
                <span><?php esc_html_e('Tìm kiếm', 'voya'); ?></span>
                <?php if ($search_query): ?>
                <span aria-hidden="true">/</span>
                <span><?php echo esc_html($search_query); ?></span>
                <?php endif; ?>
            </nav>

        </div>
    </div><!-- .vy-archive-hero-banner -->


    <!-- ══════════════════════════════════════════════════
         BODY — sidebar + mcol (giống archive-v2)
    ══════════════════════════════════════════════════ -->
    <div class="vy-archive-body">
        <div class="vy-container">
            <div class="vy-archive-layout">

                <!-- ── SIDEBAR ── -->
                <aside class="vy-archive-sidebar" aria-label="<?php esc_attr_e('Bộ lọc', 'voya'); ?>">
                    <div class="vy-archive-sidebar__sticky">

                        <!-- Search form lại nhanh -->
                        <div class="vy-archive-widget vy-search-widget-form">
                            <div class="vy-archive-widget__title">
                                <?php esc_html_e('Tìm kiếm', 'voya'); ?>
                            </div>
                            <div class="vy-archive-widget__body">
                                <form class="vy-search-sidebar-form" action="<?php echo esc_url(home_url('/')); ?>"
                                    method="get" role="search">
                                    <label for="vy-search-sidebar-input" class="sr-only">
                                        <?php esc_html_e('Từ khoá', 'voya'); ?>
                                    </label>
                                    <div class="vy-search-sidebar-form__inner">
                                        <input type="search" id="vy-search-sidebar-input" name="s"
                                            class="vy-search-sidebar-input"
                                            value="<?php echo esc_attr($search_query); ?>"
                                            placeholder="<?php esc_attr_e('Tìm bài viết…', 'voya'); ?>"
                                            autocomplete="off">
                                        <button type="submit" class="vy-search-sidebar-btn"
                                            aria-label="<?php esc_attr_e('Tìm kiếm', 'voya'); ?>">
                                            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Categories -->
                        <?php if (!empty($all_cats)): ?>
                        <div class="vy-archive-widget">
                            <div class="vy-archive-widget__title">
                                <?php esc_html_e('Danh mục', 'voya'); ?>
                            </div>
                            <div class="vy-archive-widget__body">
                                <div class="vy-filter-cat-list">
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
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Tags -->
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


                <!-- ── MCOL (overlap banner) ── -->
                <div class="vy-archive-mcol">

                    <?php if (!$search_query): ?>
                    <!-- Chưa nhập keyword -->
                    <div class="vy-archive-list-section">
                        <div class="vy-search-empty">
                            <div class="vy-search-empty__icon" aria-hidden="true">
                                <i class="fa-solid fa-magnifying-glass"></i>
                            </div>
                            <h2 class="vy-search-empty__title">
                                <?php esc_html_e('Nhập từ khoá để tìm kiếm', 'voya'); ?>
                            </h2>
                            <p class="vy-search-empty__text">
                                <?php esc_html_e('Hãy nhập tên địa điểm, chủ đề hoặc từ khoá vào ô tìm kiếm bên trái.', 'voya'); ?>
                            </p>
                        </div>
                    </div>

                    <?php elseif (empty($all_posts)): ?>
                    <!-- Không có kết quả -->
                    <div class="vy-archive-list-section">
                        <div class="vy-search-empty">
                            <div class="vy-search-empty__icon" aria-hidden="true">
                                <i class="fa-regular fa-face-sad-tear"></i>
                            </div>
                            <h2 class="vy-search-empty__title">
                                <?php printf(
                                        esc_html__('Không có kết quả cho "%s"', 'voya'),
                                        esc_html($search_query)
                                    ); ?>
                            </h2>
                            <p class="vy-search-empty__text">
                                <?php esc_html_e('Hãy thử từ khoá khác, hoặc kiểm tra lại chính tả.', 'voya'); ?>
                            </p>

                            <!-- Suggestions -->
                            <div class="vy-search-suggestions">
                                <p class="vy-search-suggestions__label">
                                    <?php esc_html_e('Gợi ý:', 'voya'); ?>
                                </p>
                                <ul class="vy-search-suggestions__list">
                                    <li><?php esc_html_e('Kiểm tra lại chính tả', 'voya'); ?></li>
                                    <li><?php esc_html_e('Thử từ khoá ngắn hơn hoặc chung hơn', 'voya'); ?></li>
                                    <li><?php esc_html_e('Tìm theo tên địa điểm hoặc loại hình du lịch', 'voya'); ?>
                                    </li>
                                </ul>
                            </div>

                            <!-- Popular categories -->
                            <?php
                                $pop_cats = get_categories([
                                    'hide_empty' => true,
                                    'orderby' => 'count',
                                    'order' => 'DESC',
                                    'number' => 6,
                                ]);
                                if (!empty($pop_cats)): ?>
                            <div class="vy-search-cats">
                                <p class="vy-search-cats__label">
                                    <?php esc_html_e('Khám phá theo danh mục', 'voya'); ?>
                                </p>
                                <div class="vy-search-cats__list">
                                    <?php foreach ($pop_cats as $pc): ?>
                                    <a href="<?php echo esc_url(get_category_link($pc->term_id)); ?>"
                                        class="vy-search-cat-pill">
                                        <?php echo esc_html($pc->name); ?>
                                        <span class="vy-search-cat-pill__count">
                                            <?php echo intval($pc->count); ?>
                                        </span>
                                    </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <a href="<?php echo esc_url($base_url); ?>" class="vy-btn vy-btn--primary">
                                <?php esc_html_e('Xem tất cả bài viết', 'voya'); ?>
                            </a>
                        </div>
                    </div>

                    <?php else: ?>
                    <!-- Có kết quả -->
                    <section class="vy-archive-list-section">

                        <h2 class="vy-archive-list-section__title">
                            <?php printf(
                                    esc_html__('Tìm thấy %s kết quả', 'voya'),
                                    '<span class="vy-archive-list-section__count">(' . number_format_i18n($total_posts) . ')</span>'
                                ); ?>
                        </h2>

                        <!-- 2-col list — reuse archive list classes -->
                        <div class="vy-archive-list" id="vy-search-grid">
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

                        <!-- Pagination — reuse archive style -->
                        <?php if ($max_pages > 1):
                                $pagination_args = [
                                    'base' => add_query_arg('paged', '%#%'),
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
                        <nav class="vy-archive-pagination"
                            aria-label="<?php esc_attr_e('Điều hướng kết quả', 'voya'); ?>">
                            <ul class="vy-archive-pagination__list">
                                <?php foreach ($pages as $pg): ?>
                                <li class="vy-archive-pagination__item"><?php echo $pg; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </nav>
                        <?php endif; endif; ?>

                    </section>
                    <?php endif; ?>

                </div><!-- .vy-archive-mcol -->

            </div><!-- .vy-archive-layout -->
        </div><!-- .vy-container -->
    </div><!-- .vy-archive-body -->

</main>

<?php get_footer(); ?>