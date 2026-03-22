<?php
/**
 * VOYA — archive.php
 * Blog archive: editorial hero + grid + AJAX load more
 *
 * Load More dùng admin-ajax.php + wp_ajax_vy_load_posts (chuẩn WP)
 * vy_render_archive_card() và AJAX handler khai báo trong functions.php
 */

get_header();

global $wpdb;

// ── Filter state ─────────────────────────────────────────────
$current_cat = isset($_GET['cat']) ? intval($_GET['cat']) : 0;
$current_sort = isset($_GET['sort']) ? sanitize_key($_GET['sort']) : 'date_desc';
$current_year = isset($_GET['year']) ? intval($_GET['year']) : 0;

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

$all_cats = get_categories([
    'hide_empty' => true,
    'orderby' => 'count',
    'order' => 'DESC',
    'number' => 12,
]);

$years = $wpdb->get_col(
    "SELECT DISTINCT YEAR(post_date) FROM {$wpdb->posts}
     WHERE post_status='publish' AND post_type='post'
     ORDER BY post_date DESC"
);

$paged = max(1, get_query_var('paged') ?: (get_query_var('page') ?: 1));
$per_page = 9;

$q_args = [
    'post_type' => 'post',
    'post_status' => 'publish',
    'posts_per_page' => $per_page,
    'paged' => $paged,
    'orderby' => $sort['orderby'],
    'order' => $sort['order'],
    'no_found_rows' => false,
];
if ($current_cat)
    $q_args['cat'] = $current_cat;
if ($current_year)
    $q_args['year'] = $current_year;

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
    $page_title = single_cat_title('', false);
    $page_desc = category_description();
} elseif (is_tag()) {
    $page_title = single_tag_title('', false);
    $page_desc = '';
} elseif (is_author()) {
    $page_title = get_the_author();
    $page_desc = '';
} elseif (is_year()) {
    $page_title = get_the_date('Y');
    $page_desc = '';
} elseif (is_month()) {
    $page_title = get_the_date('F Y');
    $page_desc = '';
} else {
    $page_title = __('Blog', 'voya');
    $page_desc = '';
}

// ── Base URL ──────────────────────────────────────────────────
if (is_category() && !isset($_GET['cat'])) {
    $blog_page = get_option('page_for_posts');
    $base_url = $blog_page ? get_permalink($blog_page) : home_url('/blog/');
} else {
    $base_url = strtok($_SERVER['REQUEST_URI'], '?');
}

function vy_filter_url($base, $cat = 0, $sort = 'date_desc', $year = 0)
{
    $params = array_filter([
        'cat' => $cat ?: null,
        'sort' => ($sort !== 'date_desc') ? $sort : null,
        'year' => $year ?: null,
    ]);
    return add_query_arg($params, $base);
}
?>

<main id="vy-archive-main" class="vy-archive-main">

    <!-- ARCHIVE HEADER -->
    <div class="vy-archive-header">
        <div class="vy-container">
            <div class="vy-archive-header__inner">
                <div class="vy-archive-header__left">
                    <nav class="vy-archive-breadcrumb" aria-label="<?php esc_attr_e('Breadcrumb', 'voya'); ?>">
                        <a
                            href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Trang chủ', 'voya'); ?></a>
                        <span aria-hidden="true">/</span>
                        <?php if ($current_cat && isset($filter_cat)): ?>
                            <a href="<?php echo esc_url($base_url); ?>"><?php esc_html_e('Blog', 'voya'); ?></a>
                            <span aria-hidden="true">/</span>
                            <span><?php echo esc_html($filter_cat->name); ?></span>
                        <?php else: ?>
                            <span><?php esc_html_e('Blog', 'voya'); ?></span>
                        <?php endif; ?>
                    </nav>
                    <h1 class="vy-archive-header__title"><?php echo esc_html($page_title); ?></h1>
                    <?php if ($page_desc): ?>
                        <p class="vy-archive-header__desc"><?php echo esc_html($page_desc); ?></p>
                    <?php endif; ?>
                </div>
                <div class="vy-archive-header__right">
                    <div class="vy-archive-header__count">
                        <span
                            class="vy-archive-header__count-num"><?php echo number_format_i18n($total_posts); ?></span>
                        <span class="vy-archive-header__count-label"><?php esc_html_e('bài viết', 'voya'); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FILTER BAR — sticky -->
    <div class="vy-filter-bar" id="vy-filter-bar">
        <div class="vy-container">
            <div class="vy-filter-bar__inner">

                <nav class="vy-filter-pills" aria-label="<?php esc_attr_e('Lọc theo danh mục', 'voya'); ?>">
                    <a class="vy-filter-pill <?php echo !$current_cat ? 'is-active' : ''; ?>"
                        href="<?php echo esc_url(vy_filter_url($base_url, 0, $current_sort, $current_year)); ?>">
                        <?php esc_html_e('Tất cả', 'voya'); ?>
                    </a>
                    <?php foreach ($all_cats as $ci): ?>
                        <a class="vy-filter-pill <?php echo $current_cat === $ci->term_id ? 'is-active' : ''; ?>"
                            href="<?php echo esc_url(vy_filter_url($base_url, $ci->term_id, $current_sort, $current_year)); ?>">
                            <?php echo esc_html($ci->name); ?>
                            <span class="vy-filter-pill__count"><?php echo intval($ci->count); ?></span>
                        </a>
                    <?php endforeach; ?>
                </nav>

                <div class="vy-filter-bar__controls">
                    <?php if (!empty($years)): ?>
                        <div class="vy-filter-select-wrap">
                            <select class="vy-filter-select" data-filter-key="year"
                                aria-label="<?php esc_attr_e('Lọc theo năm', 'voya'); ?>">
                                <option value=""><?php esc_html_e('Tất cả năm', 'voya'); ?></option>
                                <?php foreach ($years as $y): ?>
                                    <option value="<?php echo esc_attr($y); ?>" <?php selected($current_year, intval($y)); ?>>
                                        <?php echo esc_html($y); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <i class="fa-solid fa-chevron-down vy-filter-select__arrow" aria-hidden="true"></i>
                        </div>
                    <?php endif; ?>

                    <div class="vy-filter-select-wrap">
                        <select class="vy-filter-select" data-filter-key="sort"
                            aria-label="<?php esc_attr_e('Sắp xếp', 'voya'); ?>">
                            <option value="date_desc" <?php selected($current_sort, 'date_desc'); ?>>
                                <?php esc_html_e('Mới nhất', 'voya'); ?>
                            </option>
                            <option value="date_asc" <?php selected($current_sort, 'date_asc'); ?>>
                                <?php esc_html_e('Cũ nhất', 'voya'); ?>
                            </option>
                            <option value="title_asc" <?php selected($current_sort, 'title_asc'); ?>>
                                <?php esc_html_e('A → Z', 'voya'); ?>
                            </option>
                            <option value="popular" <?php selected($current_sort, 'popular'); ?>>
                                <?php esc_html_e('Phổ biến', 'voya'); ?>
                            </option>
                        </select>
                        <i class="fa-solid fa-chevron-down vy-filter-select__arrow" aria-hidden="true"></i>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- ARCHIVE BODY -->
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
                    ?: wp_trim_words(get_post_field('post_content', $hero_post->ID), 20, '…');
                $shown_now = count($all_posts);
                ?>

                <!-- Editorial Hero -->
                <div class="vy-archive-hero">
                    <a class="vy-archive-hero__img-link" href="<?php echo esc_url(get_permalink($hero_post->ID)); ?>"
                        tabindex="-1" aria-hidden="true">
                        <?php if ($hero_img): ?>
                            <img src="<?php echo esc_url($hero_img); ?>"
                                alt="<?php echo esc_attr($hero_post->post_title); ?>" class="vy-archive-hero__img"
                                loading="eager">
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
                            <?php echo esc_html(wp_trim_words($hero_exc, 20, '…')); ?>
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

                <!-- Grid -->
                <div class="vy-archive-grid" id="vy-archive-grid">
                    <?php foreach ($remaining as $p):
                        vy_render_archive_card($p); endforeach; ?>
                </div>

                <!-- Load More Button -->
                <div class="vy-load-more-wrap" id="vy-load-more-wrap" <?php echo $max_pages <= 1 ? 'style="display:none"' : ''; ?>>
                    <div class="vy-load-more-progress" aria-hidden="true">
                        <div class="vy-load-more-progress__bar"
                            style="width:<?php echo $total_posts > 0 ? round($shown_now / $total_posts * 100) : 100; ?>%">
                        </div>
                    </div>
                    <p class="vy-load-more-count">
                        <?php printf(
                            esc_html__('Đang hiện %1$s / %2$s bài viết', 'voya'),
                            '<span id="vy-shown-count">' . esc_html($shown_now) . '</span>',
                            '<strong>' . number_format_i18n($total_posts) . '</strong>'
                        ); ?>
                    </p>
                    <!--
                        data-* truyền filter params xuống JS
                        JS sẽ gọi admin-ajax.php với action=vy_load_posts
                    -->
                    <button class="vy-load-more-btn" id="vy-load-more-btn" type="button" data-page="1"
                        data-max-pages="<?php echo esc_attr($max_pages); ?>"
                        data-per-page="<?php echo esc_attr($per_page); ?>"
                        data-total="<?php echo esc_attr($total_posts); ?>"
                        data-cat="<?php echo esc_attr($current_cat); ?>"
                        data-sort="<?php echo esc_attr($current_sort); ?>"
                        data-year="<?php echo esc_attr($current_year); ?>">
                        <span class="vy-load-more-btn__text">
                            <?php esc_html_e('Xem thêm bài viết', 'voya'); ?>
                        </span>
                        <span class="vy-load-more-btn__spinner" aria-hidden="true">
                            <i class="fa-solid fa-circle-notch fa-spin"></i>
                        </span>
                    </button>
                </div>

            <?php else: ?>

                <div class="vy-archive-empty">
                    <div class="vy-archive-empty__icon" aria-hidden="true">
                        <i class="fa-regular fa-newspaper"></i>
                    </div>
                    <h3 class="vy-archive-empty__title"><?php esc_html_e('Chưa có bài viết nào', 'voya'); ?></h3>
                    <p class="vy-archive-empty__text">
                        <?php esc_html_e('Thử thay đổi bộ lọc hoặc xem tất cả bài viết.', 'voya'); ?>
                    </p>
                    <a href="<?php echo esc_url($base_url); ?>" class="vy-btn vy-btn--primary">
                        <?php esc_html_e('Xem tất cả bài viết', 'voya'); ?>
                    </a>
                </div>

            <?php endif; ?>

        </div>
    </div>

</main>

<?php get_footer(); ?>