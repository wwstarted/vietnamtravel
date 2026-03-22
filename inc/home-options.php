<?php
/**
 * WANDERLAND — Home Sections Options Page
 * File: inc/home-options.php
 *
 * Trang admin để chọn bài viết cho từng section trên trang Home.
 * Không dùng ACF — thuần WordPress Options API.
 *
 * Options được lưu:
 *   wl_section_{section_key}  →  array of post IDs (theo thứ tự)
 *
 * Cách include vào functions.php:
 *   require_once get_template_directory() . '/inc/home-options.php';
 */

if (!defined('ABSPATH'))
    exit;


// ═══════════════════════════════════════════════════════════════
//  1. DEFINE HOME SECTIONS
//  Thêm section mới ở đây khi làm thêm phần home.
// ═══════════════════════════════════════════════════════════════

function wl_get_home_sections()
{
    return apply_filters('wl_home_sections', array(

        'hero_slider' => array(
            'label' => __('Hero Slider', 'wanderland'),
            'description' => __('Các bài viết hiển thị ở slider banner đầu trang. Khuyến nghị: 3–6 bài, có featured image đẹp.', 'wanderland'),
            'icon' => 'dashicons-images-alt2',
            'max_posts' => 8,
            'min_posts' => 1,
        ),

        'dcl_posts' => array(
            'label' => __('Latest Posts Grid (Home Section 6)', 'wanderland'),
            'description' => __('4 bài viết hiển thị trong lưới 2×2 ở section cuối.', 'wanderland'),
            'icon' => 'dashicons-grid-view',
            'max_posts' => 4,
            'min_posts' => 4,
        ),

        // ── Các section sẽ mở khoá sau ──────────────────────
        'featured_posts' => array(
            'label' => __('Featured Posts', 'wanderland'),
            'description' => __('Bài nổi bật hiển thị trong slider 3 cột.', 'wanderland'),
            'icon' => 'dashicons-star-filled',
            'max_posts' => 12,
            'min_posts' => 3,
        ),

        'travel_essentials' => array(
            'label' => __('Travel Essentials', 'wanderland'),
            'description' => __('Bài tips & tricks hiển thị trong section "Travel Essentials".', 'wanderland'),
            'icon' => 'dashicons-admin-post',
            'max_posts' => 2,
            'min_posts' => 1,
        ),

    ));
}


// ═══════════════════════════════════════════════════════════════
//  2. REGISTER ADMIN MENU
// ═══════════════════════════════════════════════════════════════

add_action('admin_menu', 'wl_register_home_options_page');

function wl_register_home_options_page()
{
    add_theme_page(
        __('Home Sections', 'wanderland'),   // Page title
        __('Home Sections', 'wanderland'),   // Menu label
        'edit_theme_options',                   // Capability
        'wl-home-sections',                     // Menu slug
        'wl_render_home_options_page'           // Callback
    );
}


// ═══════════════════════════════════════════════════════════════
//  3. ENQUEUE ADMIN ASSETS
// ═══════════════════════════════════════════════════════════════

add_action('admin_enqueue_scripts', 'wl_enqueue_home_options_assets');

function wl_enqueue_home_options_assets($hook)
{
    // Chỉ load trên trang này
    if ($hook !== 'appearance_page_wl-home-sections')
        return;

    $uri = get_template_directory_uri();
    $ver = '1.0.0';

    wp_enqueue_style(
        'wl-home-options',
        $uri . '/admin/css/home-options.css',
        array('dashicons'),
        $ver
    );

    wp_enqueue_script(
        'wl-home-options',
        $uri . '/admin/js/home-options.js',
        array('jquery', 'wp-util'),
        $ver,
        true
    );

    // Pass dữ liệu sang JS
    wp_localize_script('wl-home-options', 'WL_HOME_OPTIONS', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'restUrl' => rest_url('wp/v2/posts'),
        'nonce' => wp_create_nonce('wp_rest'),
        'sections' => wl_get_home_sections(),
        'i18n' => array(
            'searchPlaceholder' => __('Tìm kiếm bài viết...', 'wanderland'),
            'noResults' => __('Không tìm thấy bài viết.', 'wanderland'),
            'loading' => __('Đang tìm...', 'wanderland'),
            'removePost' => __('Xoá khỏi section này', 'wanderland'),
            'maxReached' => __('Đã đạt giới hạn số bài cho section này.', 'wanderland'),
            'alreadyAdded' => __('Bài viết này đã có trong danh sách.', 'wanderland'),
            'dragHint' => __('Kéo để thay đổi thứ tự', 'wanderland'),
            'saved' => __('Đã lưu thành công!', 'wanderland'),
            'saving' => __('Đang lưu...', 'wanderland'),
        ),
    ));
}


// ═══════════════════════════════════════════════════════════════
//  4. SAVE HANDLER
// ═══════════════════════════════════════════════════════════════
add_action('admin_post_wl_save_home_sections', 'wl_save_home_sections');

function wl_save_home_sections()
{
    // ── Security ────────────────────────────────────────────────
    if (!current_user_can('edit_theme_options')) {
        wp_die(__('Bạn không có quyền thực hiện thao tác này.', 'wanderland'));
    }

    check_admin_referer('wl_home_sections_save', 'wl_home_nonce');

    // ── Xác định section đang active ────────────────────────────
    // FIX: Dùng wl_active_tab từ form thay vì loop tất cả sections.
    // wl_active_tab đã có sẵn trong form (hidden input) nhưng chưa được dùng.
    $active_tab = isset($_POST['wl_active_tab'])
        ? sanitize_key($_POST['wl_active_tab'])
        : '';

    $sections = wl_get_home_sections();

    // Validate: active_tab phải là một section hợp lệ
    if (empty($active_tab) || !array_key_exists($active_tab, $sections)) {
        wp_redirect(add_query_arg(array(
            'page' => 'wl-home-sections',
            'saved' => 'error',
        ), admin_url('themes.php')));
        exit;
    }

    // ── Chỉ save section đang active ────────────────────────────
    // FIX: Không loop tất cả sections nữa — tránh ghi đè array() rỗng
    // lên các sections khác không có trong $_POST.
    $section = $sections[$active_tab];
    $option_key = 'wl_section_' . $active_tab;

    if (isset($_POST[$option_key]) && is_array($_POST[$option_key])) {
        $post_ids = array_map('absint', $_POST[$option_key]);
        $post_ids = array_filter($post_ids);                                        // bỏ ID = 0
        $max = isset($section['max_posts']) ? (int) $section['max_posts'] : 10;
        $post_ids = array_slice(array_values($post_ids), 0, $max);                 // giới hạn + reindex

        update_option($option_key, $post_ids);
    } else {
        // Không có bài nào được chọn → lưu mảng rỗng cho section này
        update_option($option_key, array());
    }

    // ── Redirect về đúng tab sau khi lưu ────────────────────────
    // FIX: Thêm 'tab' vào query arg để sau khi save không nhảy về tab đầu tiên.
    wp_redirect(add_query_arg(array(
        'page' => 'wl-home-sections',
        'tab' => $active_tab,   // ← thêm mới
        'saved' => '1',
    ), admin_url('themes.php')));
    exit;
}

// ═══════════════════════════════════════════════════════════════
//  5. HELPER: GET POSTS FOR A SECTION
//  Dùng trong front-page.php và các template khác
// ═══════════════════════════════════════════════════════════════

/**
 * Lấy WP_Query cho một section cụ thể.
 *
 * @param string $section_key   Key của section (vd: 'hero_slider')
 * @param array  $query_args    WP_Query args bổ sung (override)
 * @return WP_Query|false
 */
function wl_get_section_query($section_key, $query_args = array())
{
    $option_key = 'wl_section_' . $section_key;
    $post_ids = get_option($option_key, array());

    // Nếu chưa có config → fallback về latest posts
    if (empty($post_ids)) {
        $sections = wl_get_home_sections();
        $max = isset($sections[$section_key]['max_posts']) ? $sections[$section_key]['max_posts'] : 5;

        $defaults = array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => $max,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => array(
                array(
                    'key' => '_thumbnail_id',
                    'compare' => 'EXISTS',
                ),
            ),
        );
        return new WP_Query(array_merge($defaults, $query_args));
    }

    // Có config → query theo post IDs, giữ đúng thứ tự đã set
    $defaults = array(
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => count($post_ids),
        'post__in' => $post_ids,
        'orderby' => 'post__in', // Giữ thứ tự đã kéo thả
        'ignore_sticky_posts' => true,
    );

    return new WP_Query(array_merge($defaults, $query_args));
}

/**
 * Lấy array post IDs của một section.
 *
 * @param string $section_key
 * @return array
 */
function wl_get_section_post_ids($section_key)
{
    return (array) get_option('wl_section_' . $section_key, array());
}


// ═══════════════════════════════════════════════════════════════
//  6. RENDER ADMIN PAGE
// ═══════════════════════════════════════════════════════════════

function wl_render_home_options_page()
{
    if (!current_user_can('edit_theme_options')) {
        wp_die(__('Bạn không có quyền truy cập trang này.', 'wanderland'));
    }

    $sections = wl_get_home_sections();
    $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'hero_slider';
    $saved_notice = isset($_GET['saved']) && $_GET['saved'] === '1';

    // Validate active tab
    if (!array_key_exists($active_tab, $sections)) {
        $active_tab = array_key_first($sections);
    }
    ?>
<div class="wl-options-wrap">

    <!-- ── Page Header ── -->
    <div class="wl-options-header">
        <div class="wl-options-header-inner">
            <div class="wl-options-brand">
                <span class="wl-options-brand-icon dashicons dashicons-location-alt"></span>
                <div>
                    <h1 class="wl-options-title">
                        <?php esc_html_e('Home Sections', 'wanderland'); ?>
                    </h1>
                    <p class="wl-options-subtitle">
                        <?php esc_html_e('Chọn và sắp xếp bài viết cho từng section trên trang Home', 'wanderland'); ?>
                    </p>
                </div>
            </div>
            <a href="<?php echo esc_url(home_url('/')); ?>" target="_blank" class="wl-btn-preview">
                <span class="dashicons dashicons-external"></span>
                <?php esc_html_e('Xem trang Home', 'wanderland'); ?>
            </a>
        </div>
    </div>

    <!-- ── Saved Notice ── -->
    <?php if ($saved_notice): ?>
    <div class="wl-notice wl-notice-success" id="wl-saved-notice">
        <span class="dashicons dashicons-yes-alt"></span>
        <?php esc_html_e('Cài đặt đã được lưu thành công!', 'wanderland'); ?>
    </div>
    <?php endif; ?>

    <!-- ── Tab Nav ── -->
    <nav class="wl-tab-nav" role="tablist">
        <?php foreach ($sections as $key => $section):
                $is_active = $key === $active_tab;
                $is_coming_soon = !empty($section['coming_soon']);
                $saved_ids = wl_get_section_post_ids($key);
                $post_count = count($saved_ids);
                $tab_url = add_query_arg(array(
                    'page' => 'wl-home-sections',
                    'tab' => $key,
                ), admin_url('themes.php'));
                ?>
        <a class="wl-tab-item <?php echo $is_active ? 'is-active' : ''; ?> <?php echo $is_coming_soon ? 'is-locked' : ''; ?>"
            href="<?php echo $is_coming_soon ? '#' : esc_url($tab_url); ?>" role="tab"
            aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>" <?php if ($is_coming_soon)
                               echo 'aria-disabled="true"'; ?>>
            <span class="dashicons <?php echo esc_attr($section['icon']); ?>"></span>
            <span class="wl-tab-label"><?php echo esc_html($section['label']); ?></span>
            <?php if ($post_count > 0 && !$is_coming_soon): ?>
            <span class="wl-tab-count"><?php echo esc_html($post_count); ?></span>
            <?php endif; ?>
            <?php if ($is_coming_soon): ?>
            <span class="wl-tab-lock"><?php esc_html_e('Sắp có', 'wanderland'); ?></span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </nav>

    <!-- ── Section Content ── -->
    <?php
        $current_section = $sections[$active_tab];
        $option_key = 'wl_section_' . $active_tab;
        $saved_ids = wl_get_section_post_ids($active_tab);
        $max_posts = isset($current_section['max_posts']) ? (int) $current_section['max_posts'] : 8;

        // Load saved post objects
        $saved_posts = array();
        if (!empty($saved_ids)) {
            $saved_query = new WP_Query(array(
                'post_type' => 'post',
                'post_status' => 'publish',
                'post__in' => $saved_ids,
                'orderby' => 'post__in',
                'posts_per_page' => count($saved_ids),
                'ignore_sticky_posts' => true,
            ));
            $saved_posts = $saved_query->posts;
            wp_reset_postdata();
        }
        ?>

    <div class="wl-section-content">
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="wl-home-form"
            data-section="<?php echo esc_attr($active_tab); ?>" data-max="<?php echo esc_attr($max_posts); ?>">

            <input type="hidden" name="action" value="wl_save_home_sections">
            <?php wp_nonce_field('wl_home_sections_save', 'wl_home_nonce'); ?>
            <input type="hidden" name="wl_active_tab" value="<?php echo esc_attr($active_tab); ?>">

            <div class="wl-section-layout">

                <!-- LEFT: Search + Add -->
                <div class="wl-search-panel">
                    <div class="wl-panel-header">
                        <h2><?php esc_html_e('Thêm bài viết', 'wanderland'); ?></h2>
                        <p class="wl-panel-desc">
                            <?php printf(
                                    /* translators: %d: max posts number */
                                    esc_html__('Tìm và thêm bài viết. Tối đa %d bài.', 'wanderland'),
                                    $max_posts
                                ); ?>
                        </p>
                    </div>

                    <!-- Search Input -->
                    <div class="wl-search-box">
                        <span class="dashicons dashicons-search wl-search-icon"></span>
                        <input type="text" id="wl-post-search" class="wl-search-input"
                            placeholder="<?php esc_attr_e('Tìm kiếm bài viết...', 'wanderland'); ?>" autocomplete="off"
                            aria-label="<?php esc_attr_e('Tìm kiếm bài viết để thêm', 'wanderland'); ?>">
                        <span class="wl-search-spinner" id="wl-search-spinner" aria-hidden="true"></span>
                    </div>

                    <!-- Search Results Dropdown -->
                    <div class="wl-search-results" id="wl-search-results" role="listbox"
                        aria-label="<?php esc_attr_e('Kết quả tìm kiếm', 'wanderland'); ?>">
                        <div class="wl-search-placeholder">
                            <span class="dashicons dashicons-search"></span>
                            <p><?php esc_html_e('Nhập từ khoá để tìm bài viết', 'wanderland'); ?></p>
                        </div>
                    </div>

                    <!-- Section info -->
                    <div class="wl-section-info-card">
                        <span class="dashicons <?php echo esc_attr($current_section['icon']); ?>"></span>
                        <div>
                            <strong><?php echo esc_html($current_section['label']); ?></strong>
                            <p><?php echo esc_html($current_section['description']); ?></p>
                        </div>
                    </div>
                </div>

                <!-- RIGHT: Selected Posts List -->
                <div class="wl-selected-panel">
                    <div class="wl-panel-header">
                        <h2>
                            <?php esc_html_e('Bài viết đã chọn', 'wanderland'); ?>
                        </h2>
                        <div class="wl-counter-wrap">
                            <span id="wl-post-count"><?php echo count($saved_posts); ?></span>
                            <span class="wl-counter-sep">/</span>
                            <span><?php echo esc_html($max_posts); ?></span>
                        </div>
                    </div>

                    <!-- Sortable list -->
                    <div class="wl-sortable-list <?php echo empty($saved_posts) ? 'is-empty' : ''; ?>"
                        id="wl-sortable-list" role="list"
                        aria-label="<?php esc_attr_e('Danh sách bài viết, kéo để sắp xếp', 'wanderland'); ?>">

                        <!-- Empty state -->
                        <div class="wl-empty-state" id="wl-empty-state"
                            <?php echo !empty($saved_posts) ? 'style="display:none"' : ''; ?>>
                            <span class="dashicons dashicons-plus-alt2 wl-empty-icon"></span>
                            <p><?php esc_html_e('Chưa có bài viết nào.', 'wanderland'); ?></p>
                            <p class="wl-empty-hint">
                                <?php esc_html_e('Dùng ô tìm kiếm bên trái để thêm.', 'wanderland'); ?>
                            </p>
                        </div>

                        <!-- Saved posts -->
                        <?php foreach ($saved_posts as $post):
                                $thumbnail_url = get_the_post_thumbnail_url($post->ID, 'thumbnail');
                                $category = get_the_category($post->ID);
                                $cat_name = !empty($category) ? $category[0]->name : '';
                                $post_date = get_the_date('d/m/Y', $post->ID);
                                ?>
                        <div class="wl-post-item" role="listitem" data-post-id="<?php echo esc_attr($post->ID); ?>"
                            draggable="true">

                            <input type="hidden" name="<?php echo esc_attr($option_key); ?>[]"
                                value="<?php echo esc_attr($post->ID); ?>">

                            <span class="wl-drag-handle" aria-hidden="true"
                                title="<?php esc_attr_e('Kéo để sắp xếp', 'wanderland'); ?>">
                                <svg width="10" height="16" viewBox="0 0 10 16" fill="none">
                                    <circle cx="3" cy="2" r="1.5" fill="currentColor" />
                                    <circle cx="7" cy="2" r="1.5" fill="currentColor" />
                                    <circle cx="3" cy="6" r="1.5" fill="currentColor" />
                                    <circle cx="7" cy="6" r="1.5" fill="currentColor" />
                                    <circle cx="3" cy="10" r="1.5" fill="currentColor" />
                                    <circle cx="7" cy="10" r="1.5" fill="currentColor" />
                                    <circle cx="3" cy="14" r="1.5" fill="currentColor" />
                                    <circle cx="7" cy="14" r="1.5" fill="currentColor" />
                                </svg>
                            </span>

                            <?php if ($thumbnail_url): ?>
                            <div class="wl-post-thumb">
                                <img src="<?php echo esc_url($thumbnail_url); ?>"
                                    alt="<?php echo esc_attr($post->post_title); ?>" loading="lazy">
                            </div>
                            <?php else: ?>
                            <div class="wl-post-thumb wl-post-thumb--no-image">
                                <span class="dashicons dashicons-format-image"></span>
                            </div>
                            <?php endif; ?>

                            <div class="wl-post-info">
                                <span class="wl-post-title"><?php echo esc_html($post->post_title); ?></span>
                                <div class="wl-post-meta">
                                    <?php if ($cat_name): ?>
                                    <span class="wl-post-cat"><?php echo esc_html($cat_name); ?></span>
                                    <span class="wl-meta-dot" aria-hidden="true">·</span>
                                    <?php endif; ?>
                                    <span class="wl-post-date"><?php echo esc_html($post_date); ?></span>
                                </div>
                            </div>

                            <span class="wl-post-order" aria-hidden="true">
                                <?php /* Order number — updated by JS */ ?>
                            </span>

                            <button type="button" class="wl-remove-btn"
                                aria-label="<?php esc_attr_e('Xoá bài viết này khỏi section', 'wanderland'); ?>"
                                data-post-id="<?php echo esc_attr($post->ID); ?>">
                                <span class="dashicons dashicons-no-alt"></span>
                            </button>

                        </div><!-- .wl-post-item -->
                        <?php endforeach; ?>

                    </div><!-- .wl-sortable-list -->

                    <!-- Save Button -->
                    <div class="wl-save-row">
                        <button type="submit" class="wl-save-btn" id="wl-save-btn">
                            <span class="dashicons dashicons-saved"></span>
                            <?php esc_html_e('Lưu cài đặt', 'wanderland'); ?>
                        </button>
                        <span class="wl-save-hint">
                            <?php esc_html_e('Thứ tự kéo thả sẽ là thứ tự hiển thị trên trang home.', 'wanderland'); ?>
                        </span>
                    </div>

                </div><!-- .wl-selected-panel -->

            </div><!-- .wl-section-layout -->
        </form>
    </div><!-- .wl-section-content -->

</div><!-- .wl-options-wrap -->
<?php
}