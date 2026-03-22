<?php
/**
 * VOYA — Single Post Custom Banner
 * File: inc/single-banner-meta.php
 *
 * Thêm meta box "Banner ảnh (Single Page)" vào màn hình edit post.
 * Nếu không set → tự động dùng featured image làm banner.
 * Nếu không có cả 2 → banner màu tối mặc định.
 *
 * Thêm vào functions.php:
 *   require_once get_template_directory() . '/inc/single-banner-meta.php';
 */

if (!defined('ABSPATH'))
    exit;

/* ── Enqueue media uploader trên edit post ── */
add_action('admin_enqueue_scripts', 'vy_single_banner_enqueue');

function vy_single_banner_enqueue($hook)
{
    if (!in_array($hook, ['post.php', 'post-new.php']))
        return;
    wp_enqueue_media();
}

/* ── Register meta box ── */
add_action('add_meta_boxes', 'vy_single_banner_meta_box');

function vy_single_banner_meta_box()
{
    add_meta_box(
        'vy_single_banner',
        __('Banner ảnh (Single Page)', 'voya'),
        'vy_single_banner_render',
        'post',
        'side',
        'high'
    );
}

/* ── Render meta box ── */
function vy_single_banner_render($post)
{
    wp_nonce_field('vy_single_banner_save', 'vy_single_banner_nonce');
    $banner_id = get_post_meta($post->ID, '_vy_single_banner', true);
    $banner_url = $banner_id
        ? wp_get_attachment_image_url($banner_id, 'medium')
        : '';
    ?>
<p style="margin-bottom:8px;font-size:12px;color:#666;">
    <?php esc_html_e('Ảnh banner riêng cho trang single. Để trống → dùng Featured Image.', 'voya'); ?><br>
    <?php esc_html_e('Khuyến nghị: 1900×460px hoặc 1900×700px.', 'voya'); ?>
</p>

<div class="vy-single-banner-preview" style="margin-bottom:8px;">
    <?php if ($banner_url): ?>
    <img id="vy_single_banner_preview" src="<?php echo esc_url($banner_url); ?>" alt=""
        style="width:100%;height:auto;max-height:100px;object-fit:cover;border:1px solid #ddd;border-radius:3px;">
    <?php else: ?>
    <img id="vy_single_banner_preview" src="" alt=""
        style="display:none;width:100%;height:auto;max-height:100px;object-fit:cover;border:1px solid #ddd;border-radius:3px;">
    <?php endif; ?>
</div>

<input type="hidden" id="vy_single_banner_id" name="vy_single_banner_id" value="<?php echo esc_attr($banner_id); ?>">

<div style="display:flex;gap:6px;flex-wrap:wrap;">
    <button type="button" id="vy_single_banner_upload" class="button">
        <span class="dashicons dashicons-upload" style="margin-top:3px;font-size:15px;"></span>
        <?php esc_html_e('Chọn ảnh', 'voya'); ?>
    </button>
    <button type="button" id="vy_single_banner_remove" class="button"
        <?php echo $banner_id ? '' : 'style="display:none"'; ?>>
        <span class="dashicons dashicons-trash" style="margin-top:3px;font-size:15px;color:#a00;"></span>
        <?php esc_html_e('Xoá', 'voya'); ?>
    </button>
</div>

<script>
(function($) {
    var frame;
    var $upload = $('#vy_single_banner_upload');
    var $remove = $('#vy_single_banner_remove');
    var $input = $('#vy_single_banner_id');
    var $preview = $('#vy_single_banner_preview');

    $upload.on('click', function(e) {
        e.preventDefault();
        if (frame) {
            frame.open();
            return;
        }
        frame = wp.media({
            title: '<?php echo esc_js(__('Chọn ảnh Banner', 'voya')); ?>',
            button: {
                text: '<?php echo esc_js(__('Dùng ảnh này', 'voya')); ?>'
            },
            multiple: false,
            library: {
                type: 'image'
            }
        });
        frame.on('select', function() {
            var att = frame.state().get('selection').first().toJSON();
            $input.val(att.id);
            $preview.attr('src', att.sizes && att.sizes.medium ? att.sizes.medium.url : att.url)
                .show();
            $remove.show();
        });
        frame.open();
    });

    $remove.on('click', function(e) {
        e.preventDefault();
        $input.val('');
        $preview.attr('src', '').hide();
        $remove.hide();
    });
})(jQuery);
</script>
<?php
}

/* ── Save ── */
add_action('save_post_post', 'vy_single_banner_save');

function vy_single_banner_save($post_id)
{
    if (
        !isset($_POST['vy_single_banner_nonce'])
        || !wp_verify_nonce($_POST['vy_single_banner_nonce'], 'vy_single_banner_save')
    )
        return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        return;
    if (!current_user_can('edit_post', $post_id))
        return;

    if (isset($_POST['vy_single_banner_id'])) {
        $val = absint($_POST['vy_single_banner_id']);
        if ($val) {
            update_post_meta($post_id, '_vy_single_banner', $val);
        } else {
            delete_post_meta($post_id, '_vy_single_banner');
        }
    }
}