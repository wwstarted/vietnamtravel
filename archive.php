<?php
/**
 * VOYA — archive.php (v2 — fixed 2)
 *
 * Fix so với v2-fixed:
 *  1. Sidebar sticky — grid cần align-items: start (fix bằng inline style safe)
 *  2. Tag/Author/Date archive: detect context → thêm đúng args vào WP_Query
 *  3. Sort links dùng đúng URL hiện tại (current_url) thay vì $base_url
 */

get_header();

global $wpdb;

// ── Filter state ─────────────────────────────────────────────
$current_cat = isset($_GET['cat']) ? intval($_GET['cat']) : 0;
$current_sort = isset($_GET['sort']) ? sanitize_key($_GET['sort']) : 'date_desc';
$current_year = isset($_GET['year']) ? intval($_GET['year']) : 0;

// Detect từ WP query object — category
if (!$current_cat && is_category()) {
    $q_obj = get_queried_object();
    $current_cat = $q_obj ? $q_obj->term_id : 0;
}

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

// ── Pagination ────────────────────────────────────────────────
$paged = max(1, get_query_var('paged') ?: (get_query_var('page') ?: 1));
$per_page = 12;

// ── FIX: Build đúng WP_Query args theo context hiện tại ──────
$q_args = [
    'post_type' => 'post',
    'post_status' => 'publish',
    'posts_per_page' => $per_page,
    'paged' => $paged,
    'orderby' => $sort['orderby'],
    'order' => $sort['order'],
    'no_found_rows' => false,
];

// Category filter (từ GET param)
if ($current_cat) {
    $q_args['cat'] = $current_cat;
}

// Year filter
if ($current_year) {
    $q_args['year'] = $current_year;
}

// FIX: Tag archive — is_tag() → thêm tag slug/ID vào query
if (is_tag()) {
    $tag_obj = get_queried_object();
    if ($tag_obj) {
        $q_args['tag_id'] = $tag_obj->term_id;
    }
}

// FIX: Author archive
if (is_author()) {
    $author_obj = get_queried_object();
    if ($author_obj) {
        $q_args['author'] = $author_obj->ID;
    }
}

// FIX: Year/Month/Day archive
if (is_year())
    $q_args['year'] = get_query_var('year');
if (is_month()) {
    $q_args['year'] = get_query_var('year');
    $q_args['monthnum'] = get_query_var('monthnum');
}
if (is_day()) {
    $q_args['year'] = get_query_var('year');
    $q_args['monthnum'] = get_query_var('monthnum');
    $q_args['day'] = get_query_var('day');
}

// FIX: Search archive
if (is_search()) {
    $q_args['s'] = get_search_query();
}

$archive_q = new WP_Query($q_args);
$total_posts = $archive_q->found_posts;
$max_pages = $archive_q->max_num_pages;
$all_posts = $archive_q->posts;
wp_reset_postdata();

// ── Page title ────────────────────────────────────────────────
if ($current_cat && ($filter_cat = get_category($current_cat))) {
    $page_title = $filter_cat->name;
    $page_desc = $filter_cat->description;
} elseif (is_category()) {
    $q_obj = get_queried_object();
    $filter_cat = $q_obj;
    $page_title = single_cat_title('', false);
    $page_desc = category_description();
} elseif (is_tag()) {
    $page_title = single_tag_title('', false);
    $page_desc = '';
} elseif (is_author()) {
    $page_title = get_the_author_meta('display_name', get_queried_object_id());
    $page_desc = '';
} elseif (is_year()) {
    $page_title = get_query_var('year');
    $page_desc = '';
} elseif (is_month()) {
    $page_title = get_the_date('F Y');
    $page_desc = '';
} elseif (is_search()) {
    $page_title = sprintf(__('Tìm kiếm: %s', 'voya'), get_search_query());
    $page_desc = '';
} else {
    $page_title = get_theme_mod('vy_archive_default_title', __('Travel Blog', 'voya'));
    $page_desc = '';
}

// ── Hero banner ───────────────────────────────────────────────
$hero_banner_url = '';
if (isset($filter_cat) && $filter_cat) {
    $hero_banner_url = get_term_meta($filter_cat->term_id, '_vy_archive_banner', true);
}
if (!$hero_banner_url) {
    $default_banner_id = get_theme_mod('vy_archive_default_banner');
    $hero_banner_url = $default_banner_id
        ? wp_get_attachment_image_url($default_banner_id, 'wl-wide')
        ?: wp_get_attachment_image_url($default_banner_id, 'full')
        : '';
}

// ── Base URL (blog page) ──────────────────────────────────────
$blog_page_id = get_option('page_for_posts');
$base_url = $blog_page_id
    ? get_permalink($blog_page_id)
    : home_url('/blog/');

// ── FIX: current_archive_url — URL của archive hiện tại ──────
// Dùng cho sort links để sort đúng ngữ cảnh (tag, category, author...)
if (is_category() && isset($filter_cat)) {
    $current_archive_url = get_category_link($filter_cat->term_id);
} elseif (is_tag()) {
    $current_archive_url = get_tag_link(get_queried_object_id());
} elseif (is_author()) {
    $current_archive_url = get_author_posts_url(get_queried_object_id());
} elseif ($current_cat) {
    $current_archive_url = get_category_link($current_cat);
} else {
    $current_archive_url = $base_url;
}

function vy_archive_sort_url($archive_url, $sort_key, $year = 0)
{
    $params = array_filter([
        'sort' => ($sort_key !== 'date_desc') ? $sort_key : null,
        'year' => $year ?: null,
    ]);
    return $params ? add_query_arg($params, $archive_url) : $archive_url;
}

function vy_build_sort_url($base, $cat_id, $sort_key, $year = 0)
{
    if ($cat_id) {
        $url = get_category_link($cat_id);
        $params = array_filter([
            'sort' => ($sort_key !== 'date_desc') ? $sort_key : null,
            'year' => $year ?: null,
        ]);
        return $params ? add_query_arg($params, $url) : $url;
    }
    $params = array_filter([
        'sort' => ($sort_key !== 'date_desc') ? $sort_key : null,
        'year' => $year ?: null,
    ]);
    return $params ? add_query_arg($params, $base) : $base;
}

// ── Top blogs (page 1 only) ───────────────────────────────────
$top_blogs_posts = [];
if ($paged === 1 && !is_tag() && !is_author() && !is_search()) {
    $top_blogs_query = wl_get_section_query('top_blogs', ['posts_per_page' => 6]);
    if ($top_blogs_query && $top_blogs_query->have_posts()) {
        $top_blogs_posts = $top_blogs_query->posts;
        wp_reset_postdata();
    }
}
?>

<main id="vy-archive-main" class="vy-archive-main">

    <!-- HERO BANNER -->
    <div class="vy-archive-hero-banner" <?php if ($hero_banner_url): ?>
        style="background-image: url('<?php echo esc_url($hero_banner_url); ?>')" <?php endif; ?>>
        <div class="vy-archive-hero-banner__overlay" aria-hidden="true"></div>
        <div class="vy-archive-hero-banner__content">
            <h1 class="vy-archive-hero-banner__title">
                <?php echo esc_html($page_title); ?>
            </h1>
            <nav class="vy-archive-breadcrumb" aria-label="<?php esc_attr_e('Breadcrumb', 'voya'); ?>">
                <a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Trang chủ', 'voya'); ?></a>
                <span aria-hidden="true">/</span>
                <?php if (isset($filter_cat) && $filter_cat || is_tag() || is_author()): ?>
                <a href="<?php echo esc_url($base_url); ?>"><?php esc_html_e('Blog', 'voya'); ?></a>
                <span aria-hidden="true">/</span>
                <span><?php echo esc_html($page_title); ?></span>
                <?php else: ?>
                <span><?php esc_html_e('Blog', 'voya'); ?></span>
                <?php endif; ?>
            </nav>
        </div>
    </div>

    <!-- BODY -->
    <div class="vy-archive-body">
        <div class="vy-container">
            <!-- FIX STICKY: thêm class vy-archive-layout--sticky-sidebar -->
            <div class="vy-archive-layout vy-archive-layout--sticky-sidebar">

                <!-- SIDEBAR -->
                <aside class="vy-archive-sidebar" aria-label="<?php esc_attr_e('Bộ lọc', 'voya'); ?>">
                    <div class="vy-archive-sidebar__sticky">

                        <div class="vy-archive-widget">
                            <div class="vy-archive-widget__title">
                                <?php esc_html_e('Lọc theo', 'voya'); ?>
                            </div>
                            <div class="vy-archive-widget__body">

                                <div class="vy-archive-widget__subtitle">
                                    <?php esc_html_e('Danh mục', 'voya'); ?>
                                </div>
                                <div class="vy-filter-cat-list">

                                    <?php foreach ($all_cats as $ci):
                                        $cat_url = get_category_link($ci->term_id);
                                        if ($current_sort !== 'date_desc' || $current_year) {
                                            $cat_url = add_query_arg(array_filter([
                                                'sort' => ($current_sort !== 'date_desc') ? $current_sort : null,
                                                'year' => $current_year ?: null,
                                            ]), $cat_url);
                                        }
                                        /* Active nếu đang ở category này */
                                        $is_active = $current_cat === $ci->term_id
                                            || (is_category() && get_queried_object_id() === $ci->term_id);
                                        ?>
                                    <div class="vy-filter-cat-item">
                                        <a href="<?php echo esc_url($cat_url); ?>"
                                            class="vy-filter-cat-link <?php echo $is_active ? 'is-active' : ''; ?>">
                                            <span class="vy-filter-cat-radio" aria-hidden="true">
                                                <?php if ($is_active): ?>
                                                <i class="fa-solid fa-circle-dot"></i>
                                                <?php else: ?>
                                                <i class="fa-regular fa-circle"></i>
                                                <?php endif; ?>
                                            </span>
                                            <?php echo esc_html($ci->name); ?>
                                        </a>
                                    </div>
                                    <?php endforeach; ?>

                                    <!-- Tất cả -->
                                    <div class="vy-filter-cat-item">
                                        <a href="<?php echo esc_url($base_url); ?>"
                                            class="vy-filter-cat-link <?php echo (!$current_cat && !is_category() && !is_tag() && !is_author()) ? 'is-active' : ''; ?>">
                                            <span class="vy-filter-cat-radio" aria-hidden="true">
                                                <?php if (!$current_cat && !is_category() && !is_tag() && !is_author()): ?>
                                                <i class="fa-solid fa-circle-dot"></i>
                                                <?php else: ?>
                                                <i class="fa-regular fa-circle"></i>
                                                <?php endif; ?>
                                            </span>
                                            <?php esc_html_e('Tất cả danh mục', 'voya'); ?>
                                        </a>
                                    </div>
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
                                        /* FIX: sort dựa trên current_archive_url */
                                        $sort_url = vy_archive_sort_url($current_archive_url, $sk, $current_year);
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
                        </div>

                        <?php if (!empty($all_tags)): ?>
                        <div class="vy-archive-widget">
                            <div class="vy-archive-widget__title">
                                <?php esc_html_e('Tags', 'voya'); ?>
                            </div>
                            <div class="vy-archive-widget__body">
                                <div class="vy-archive-tags">
                                    <?php foreach ($all_tags as $tag): ?>
                                    <a href="<?php echo esc_url(get_tag_link($tag->term_id)); ?>"
                                        class="vy-archive-tag <?php echo (is_tag() && get_queried_object_id() === $tag->term_id) ? 'is-active' : ''; ?>"
                                        title="<?php echo esc_attr($tag->name); ?>">
                                        <?php echo esc_html($tag->name); ?>
                                    </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                    </div>
                </aside>

                <!-- MCOL -->
                <div class="vy-archive-mcol">

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
                                        class="vy-archive-topblogs__img-link">
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
                        </div>

                        <?php if ($max_pages > 1):
                                /* Pagination base: dùng current archive URL */
                                $paged_base = add_query_arg('paged', '%#%', $current_archive_url);
                                /* Giữ sort/year nếu có */
                                if ($current_sort !== 'date_desc') {
                                    $paged_base = add_query_arg('sort', $current_sort, $paged_base);
                                }
                                if ($current_year) {
                                    $paged_base = add_query_arg('year', $current_year, $paged_base);
                                }
                                $pagination_args = [
                                    'base' => $paged_base,
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
                                <?php esc_html_e('Thử thay đổi bộ lọc hoặc xem tất cả bài viết.', 'voya'); ?>
                            </p>
                            <a href="<?php echo esc_url($base_url); ?>" class="vy-btn vy-btn--primary">
                                <?php esc_html_e('Xem tất cả bài viết', 'voya'); ?>
                            </a>
                        </div>
                        <?php endif; ?>

                    </section>

                </div>

            </div>
        </div>
    </div>

</main>

<?php get_footer(); ?>