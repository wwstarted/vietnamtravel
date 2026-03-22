<?php
/**
 * VOYA — Archive Page Customizer + Category Banner Meta
 *
 * Thêm vào cuối inc/destinations-cpt.php (hoặc functions.php)
 *
 * Cung cấp:
 * 1. Customizer: "Archive Page (Blog)" — default banner + default title + top blogs title
 * 2. Category banner upload: thêm field ảnh vào màn hình edit Category
 */


/* ══════════════════════════════════════════════════════════
   1. CUSTOMIZER: Archive Page settings
══════════════════════════════════════════════════════════ */
add_action('customize_register', 'vy_archive_customizer');

function vy_archive_customizer($wp_customize)
{
    $wp_customize->add_section('vy_archive', [
        'title' => __('Archive Page (Blog)', 'voya'),
        'description' => __('Cài đặt cho trang danh sách bài viết (archive).', 'voya'),
        'priority' => 41,
    ]);

    /* ── Default banner image ── */
    $wp_customize->add_setting('vy_archive_default_banner', [
        'default' => '',
        'sanitize_callback' => 'absint',
    ]);
    $wp_customize->add_control(
        new WP_Customize_Media_Control($wp_customize, 'vy_archive_default_banner', [
            'label' => __('Banner mặc định (khi không có banner theo category)', 'voya'),
            'description' => __('Khuyến nghị: 1900×700px, landscape đẹp.', 'voya'),
            'section' => 'vy_archive',
            'mime_type' => 'image',
        ])
    );

    /* ── Default page title ── */
    $wp_customize->add_setting('vy_archive_default_title', [
        'default' => 'Travel Blog',
        'sanitize_callback' => 'sanitize_text_field',
        'transport' => 'postMessage',
    ]);
    $wp_customize->add_control('vy_archive_default_title', [
        'label' => __('Tiêu đề mặc định (trang blog tổng hợp)', 'voya'),
        'description' => __('Hiển thị trên hero banner khi không lọc theo category.', 'voya'),
        'section' => 'vy_archive',
        'type' => 'text',
    ]);

    /* ── Top blogs section title ── */
    $wp_customize->add_setting('vy_archive_topblogs_title', [
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field',
        'transport' => 'postMessage',
    ]);
    $wp_customize->add_control('vy_archive_topblogs_title', [
        'label' => __('Tiêu đề section "Top Blogs" (để trống = tự động)', 'voya'),
        'description' => __('Ví dụ: "Top 6 bài viết phổ biến nhất". Để trống sẽ tự sinh từ số lượng bài.', 'voya'),
        'section' => 'vy_archive',
        'type' => 'text',
    ]);
}


/* ══════════════════════════════════════════════════════════
   2. CATEGORY BANNER — thêm field upload vào edit Category
   Admin: WP Admin → Posts → Categories → Edit category
══════════════════════════════════════════════════════════ */
add_action('admin_enqueue_scripts', 'vy_category_banner_media');

function vy_category_banner_media($hook)
{
    /* Chỉ load trên trang edit-tags.php / term.php của taxonomy category */
    if (!in_array($hook, ['edit-tags.php', 'term.php']))
        return;
    if (!isset($_GET['taxonomy']) || $_GET['taxonomy'] !== 'category')
        return;

    /* Enqueue wp media uploader */
    wp_enqueue_media();

    /* Enqueue jQuery (thường đã có, chắc chắn hơn) */
    wp_enqueue_script('jquery');
}
/* Add form — thêm category mới */
add_action('category_add_form_fields', 'vy_cat_banner_add_field');

function vy_cat_banner_add_field()
{
    wp_nonce_field('vy_cat_banner_save', 'vy_cat_banner_nonce');
    ?>
<div class="form-field">
    <label>
        <?php esc_html_e('Banner ảnh (Archive Hero)', 'voya'); ?>
    </label>
    <?php vy_cat_banner_field(''); ?>
    <p class="description">
        <?php esc_html_e('Ảnh hiển thị trên hero banner khi xem archive của category này. Khuyến nghị: 1900×700px.', 'voya'); ?>
    </p>
</div>
<?php
}

/* Edit form — sửa category */
add_action('category_edit_form_fields', 'vy_cat_banner_edit_field');

function vy_cat_banner_edit_field($term)
{
    wp_nonce_field('vy_cat_banner_save', 'vy_cat_banner_nonce');
    $banner = get_term_meta($term->term_id, '_vy_archive_banner', true);
    ?>
<tr class="form-field">
    <th><label>
            <?php esc_html_e('Banner ảnh (Archive Hero)', 'voya'); ?>
        </label></th>
    <td>
        <?php vy_cat_banner_field($banner); ?>
        <p class="description">
            <?php esc_html_e('Ảnh hero banner khi xem archive category này. Khuyến nghị: 1900×700px.', 'voya'); ?>
        </p>
    </td>
</tr>
<?php
}

/**
 * Render upload field cho category banner
 */
function vy_cat_banner_field($current_url = '')
{
    ?>
<div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-top:4px;">
    <input type="hidden" id="vy_cat_banner_url" name="vy_cat_banner_url" value="<?php echo esc_attr($current_url); ?>">

    <?php if ($current_url): ?>
    <img id="vy_cat_banner_preview" src="<?php echo esc_url($current_url); ?>" alt=""
        style="max-height:80px;max-width:200px;border:1px solid #ddd;border-radius:4px;object-fit:cover;">
    <?php else: ?>
    <img id="vy_cat_banner_preview" src="" alt=""
        style="max-height:80px;max-width:200px;border:1px solid #ddd;border-radius:4px;object-fit:cover;display:none;">
    <?php endif; ?>

    <button type="button" class="button vy-cat-banner-upload">
        <span class="dashicons dashicons-upload" style="margin-top:3px;font-size:16px;"></span>
        <?php esc_html_e('Upload ảnh banner', 'voya'); ?>
    </button>

    <button type="button" class="button vy-cat-banner-remove" <?php echo $current_url ? '' : 'style="display:none"'; ?>>
        <span class="dashicons dashicons-trash" style="margin-top:3px;font-size:16px;color:#a00;"></span>
        <?php esc_html_e('Xoá', 'voya'); ?>
    </button>
</div>
<?php
}

/* Save banner meta khi tạo/sửa category */
add_action('created_category', 'vy_save_cat_banner');
add_action('edited_category', 'vy_save_cat_banner');

function vy_save_cat_banner($term_id)
{
    if (
        !isset($_POST['vy_cat_banner_nonce'])
        || !wp_verify_nonce($_POST['vy_cat_banner_nonce'], 'vy_cat_banner_save')
    )
        return;

    if (isset($_POST['vy_cat_banner_url'])) {
        update_term_meta(
            $term_id,
            '_vy_archive_banner',
            esc_url_raw($_POST['vy_cat_banner_url'])
        );
    }
}

add_action('admin_footer', 'vy_category_banner_script');

function vy_category_banner_script()
{
    /* Chỉ output trên đúng trang */
    $screen = get_current_screen();
    if (!$screen)
        return;
    if ($screen->base !== 'edit-tags' && $screen->base !== 'term')
        return;
    if ($screen->taxonomy !== 'category')
        return;
    ?>
<script>
(function($) {
    'use strict';

    /* Upload button */
    $(document).on('click', '.vy-cat-banner-upload', function(e) {
        e.preventDefault();

        var frame = wp.media({
            title: 'Chọn ảnh Banner',
            button: {
                text: 'Dùng ảnh này'
            },
            multiple: false,
            library: {
                type: 'image'
            }
        });

        frame.on('select', function() {
            var att = frame.state().get('selection').first().toJSON();
            $('#vy_cat_banner_url').val(att.url);
            $('#vy_cat_banner_preview').attr('src', att.url).show();
            $('.vy-cat-banner-remove').show();
        });

        frame.open();
    });

    /* Remove button */
    $(document).on('click', '.vy-cat-banner-remove', function(e) {
        e.preventDefault();
        $('#vy_cat_banner_url').val('');
        $('#vy_cat_banner_preview').hide();
        $(this).hide();
    });

})(jQuery);
</script>
<?php
}