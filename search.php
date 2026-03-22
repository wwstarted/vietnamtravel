<?php
/**
 * VOYA — search.php
 * Search results page
 *
 * Logic:
 *  Header search bar (header.php) → GET /?s=keyword → trang này
 *  Layout: search header + results grid + pagination
 *  Tái dụng: vy-archive-card, vy_render_archive_card() từ functions.php
 */

get_header();

$search_query = get_search_query(); // đã sanitize bởi WP
$paged = max(1, get_query_var('paged') ?: (get_query_var('page') ?: 1));
$per_page = 9;

// WP đã chạy query mặc định, nhưng ta custom để control per_page + paged
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
?>

<main id="vy-search-main" class="vy-search-main">

    <!-- ============================================================
         SEARCH HEADER
    ============================================================ -->
    <div class="vy-search-header">
        <div class="vy-container">

            <!-- Breadcrumb -->
            <nav class="vy-search-breadcrumb" aria-label="<?php esc_attr_e('Breadcrumb', 'voya'); ?>">
                <a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Trang chủ', 'voya'); ?></a>
                <span aria-hidden="true">/</span>
                <span><?php esc_html_e('Tìm kiếm', 'voya'); ?></span>
            </nav>

            <div class="vy-search-header__inner">
                <div class="vy-search-header__left">

                    <span class="vy-label"><?php esc_html_e('Kết quả tìm kiếm', 'voya'); ?></span>

                    <h1 class="vy-search-header__title">
                        <?php if ($search_query): ?>
                        <span class="vy-search-header__query">
                            "<?php echo esc_html($search_query); ?>"
                        </span>
                        <?php else: ?>
                        <?php esc_html_e('Tìm kiếm', 'voya'); ?>
                        <?php endif; ?>
                    </h1>

                    <?php if ($search_query && $total_posts > 0): ?>
                    <p class="vy-search-header__meta">
                        <?php printf(
                                    esc_html(_n('Tìm thấy %s kết quả', 'Tìm thấy %s kết quả', $total_posts, 'voya')),
                                    '<strong>' . number_format_i18n($total_posts) . '</strong>'
                                ); ?>
                    </p>
                    <?php endif; ?>

                </div>

                <!-- Search form inline — cho phép đổi keyword nhanh -->
                <div class="vy-search-header__form-wrap">
                    <form class="vy-search-inline-form" action="<?php echo esc_url(home_url('/')); ?>" method="get"
                        role="search">
                        <label for="vy-search-inline-input" class="sr-only">
                            <?php esc_html_e('Từ khoá tìm kiếm', 'voya'); ?>
                        </label>
                        <input type="search" id="vy-search-inline-input" name="s" class="vy-search-inline-input"
                            value="<?php echo esc_attr($search_query); ?>"
                            placeholder="<?php esc_attr_e('Tìm kiếm bài viết…', 'voya'); ?>" autocomplete="off"
                            autofocus>
                        <button type="submit" class="vy-search-inline-btn"
                            aria-label="<?php esc_attr_e('Tìm kiếm', 'voya'); ?>">
                            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                        </button>
                    </form>
                </div>

            </div>

        </div>
    </div><!-- .vy-search-header -->


    <!-- ============================================================
         SEARCH RESULTS
    ============================================================ -->
    <div class="vy-search-body">
        <div class="vy-container">

            <?php if (!$search_query): ?>
            <!-- No keyword entered -->
            <div class="vy-search-empty">
                <div class="vy-search-empty__icon" aria-hidden="true">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>
                <h2 class="vy-search-empty__title">
                    <?php esc_html_e('Nhập từ khoá để tìm kiếm', 'voya'); ?>
                </h2>
                <p class="vy-search-empty__text">
                    <?php esc_html_e('Hãy nhập tên địa điểm, chủ đề hoặc từ khoá vào ô tìm kiếm ở trên.', 'voya'); ?>
                </p>
            </div>

            <?php elseif (empty($all_posts)): ?>
            <!-- No results -->
            <div class="vy-search-empty">
                <div class="vy-search-empty__icon" aria-hidden="true">
                    <i class="fa-regular fa-face-sad-tear"></i>
                </div>
                <h2 class="vy-search-empty__title">
                    <?php printf(
                                esc_html__('Không tìm thấy kết quả cho "%s"', 'voya'),
                                esc_html($search_query)
                            ); ?>
                </h2>
                <p class="vy-search-empty__text">
                    <?php esc_html_e('Hãy thử từ khoá khác, hoặc kiểm tra chính tả nhé.', 'voya'); ?>
                </p>

                <!-- Suggestions -->
                <div class="vy-search-suggestions">
                    <p class="vy-search-suggestions__label">
                        <?php esc_html_e('Gợi ý:', 'voya'); ?>
                    </p>
                    <ul class="vy-search-suggestions__list">
                        <li><?php esc_html_e('Kiểm tra lại chính tả từ khoá', 'voya'); ?></li>
                        <li><?php esc_html_e('Thử từ khoá ngắn hơn hoặc chung hơn', 'voya'); ?></li>
                        <li><?php esc_html_e('Tìm theo tên địa điểm hoặc loại hình du lịch', 'voya'); ?></li>
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
                        <a href="<?php echo esc_url(get_category_link($pc->term_id)); ?>" class="vy-search-cat-pill">
                            <?php echo esc_html($pc->name); ?>
                            <span class="vy-search-cat-pill__count">
                                <?php echo intval($pc->count); ?>
                            </span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <a href="<?php echo esc_url(home_url('/')); ?>" class="vy-btn vy-btn--primary">
                    <?php esc_html_e('Về trang chủ', 'voya'); ?>
                </a>
            </div>

            <?php else: ?>
            <!-- Has results -->

            <!-- Results grid — tái dụng vy-archive-card -->
            <div class="vy-archive-grid vy-search-grid" id="vy-search-grid">
                <?php foreach ($all_posts as $p):
                            vy_render_archive_card($p);
                        endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($max_pages > 1): ?>
            <nav class="vy-search-pagination" aria-label="<?php esc_attr_e('Điều hướng kết quả', 'voya'); ?>">

                <?php
                                // Build pagination với WP paginate_links()
                                $pagination = paginate_links([
                                    'base' => add_query_arg('paged', '%#%'),
                                    'format' => '',
                                    'current' => $paged,
                                    'total' => $max_pages,
                                    'prev_text' => '<i class="fa-solid fa-arrow-left" aria-hidden="true"></i>',
                                    'next_text' => '<i class="fa-solid fa-arrow-right" aria-hidden="true"></i>',
                                    'type' => 'array',
                                    'end_size' => 1,
                                    'mid_size' => 2,
                                ]);

                                if ($pagination): ?>
                <div class="vy-search-pagination__inner">
                    <?php foreach ($pagination as $page_link):
                                                // Thêm class vy- vào từng link
                                                $page_link = str_replace(
                                                    ['class="page-numbers current"', 'class="page-numbers"', 'class="prev page-numbers"', 'class="next page-numbers"'],
                                                    ['class="vy-page-num is-current" aria-current="page"', 'class="vy-page-num"', 'class="vy-page-btn vy-page-prev"', 'class="vy-page-btn vy-page-next"'],
                                                    $page_link
                                                );
                                                echo $page_link;
                                            endforeach; ?>
                </div>

                <p class="vy-search-pagination__info">
                    <?php printf(
                                                esc_html__('Trang %1$s / %2$s', 'voya'),
                                                '<strong>' . $paged . '</strong>',
                                                '<strong>' . $max_pages . '</strong>'
                                            ); ?>
                </p>
                <?php endif; ?>

            </nav>
            <?php endif; ?>

            <?php endif; ?>

        </div>
    </div><!-- .vy-search-body -->

</main>

<?php get_footer(); ?>