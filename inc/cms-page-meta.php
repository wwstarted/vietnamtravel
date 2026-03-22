<?php
/**
 * VOYA — inc/cms-page-meta.php
 * Meta boxes cho CMS Page template:
 *   - Tagline (hiển thị trên hero title)
 *   - Hero background image (override featured image)
 *   - Slider images (tối đa 8, drag-to-reorder)
 *
 * Redesigned từ Wanderland cms-page-meta.php:
 *   - Prefix wl_ → vy_ cho meta keys
 *   - Logic giữ nguyên 100%
 */

if (!defined('ABSPATH'))
    exit;


// ── Register meta box ─────────────────────────────────────────
function vy_cms_meta_boxes()
{
    add_meta_box(
        'vy_cms_settings',
        __('CMS Page Settings — Hero & Slider', 'voya'),
        'vy_cms_meta_box_render',
        'page',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'vy_cms_meta_boxes');


// ── Render callback ───────────────────────────────────────────
function vy_cms_meta_box_render($post)
{
    wp_nonce_field('vy_cms_meta_save', 'vy_cms_meta_nonce');

    $hero_img_id = get_post_meta($post->ID, '_vy_cms_hero_image', true);
    $tagline = get_post_meta($post->ID, '_vy_cms_tagline', true);
    $slider_ids_raw = get_post_meta($post->ID, '_vy_cms_slider_images', true);
    $slider_ids = $slider_ids_raw
        ? array_filter(array_map('intval', explode(',', $slider_ids_raw)))
        : [];

    $hero_src = $hero_img_id
        ? wp_get_attachment_image_url(intval($hero_img_id), 'thumbnail')
        : '';
    ?>
<style>
.vy-meta-section {
    margin-bottom: 22px;
    padding-bottom: 22px;
    border-bottom: 1px solid #eee;
}

.vy-meta-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.vy-meta-section>label {
    display: block;
    font-weight: 600;
    margin-bottom: 6px;
    font-size: 13px;
}

.vy-meta-hint {
    font-size: 11px;
    color: #888;
    margin: 4px 0 0;
}

.vy-media-row {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-top: 8px;
}

.vy-media-row img {
    width: 80px;
    height: 54px;
    object-fit: cover;
    border: 1px solid #ddd;
    border-radius: 3px;
}

.vy-slider-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 10px;
    min-height: 90px;
    align-items: flex-start;
}

.vy-slider-item {
    position: relative;
    width: 82px;
    height: 82px;
    border: 1px solid #ddd;
    border-radius: 3px;
    cursor: grab;
    background: #f5f5f5;
}

.vy-slider-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    border-radius: 3px;
    pointer-events: none;
}

.vy-slider-remove {
    position: absolute;
    top: 3px;
    right: 3px;
    width: 18px;
    height: 18px;
    background: rgba(180, 0, 0, .85);
    color: #fff;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    font-size: 12px;
    line-height: 18px;
    text-align: center;
    padding: 0;
    display: none;
}

.vy-slider-item:hover .vy-slider-remove {
    display: block;
}

.vy-slider-add {
    width: 82px;
    height: 82px;
    border: 2px dashed #ccc;
    background: #fafafa;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 30px;
    color: #ccc;
    border-radius: 3px;
    transition: all .18s;
}

.vy-slider-add:hover {
    border-color: #b89d6e;
    color: #b89d6e;
}
</style>

<!-- Tagline -->
<div class="vy-meta-section">
    <label for="vy_cms_tagline"><?php esc_html_e('Hero Tagline', 'voya'); ?></label>
    <input type="text" id="vy_cms_tagline" name="vy_cms_tagline" value="<?php echo esc_attr($tagline); ?>"
        class="widefat"
        placeholder="<?php esc_attr_e('Ví dụ: Câu chuyện của chúng tôi — hiển thị nhỏ trên hero title', 'voya'); ?>">
    <p class="vy-meta-hint">
        <?php esc_html_e('Tuỳ chọn. Dòng text nhỏ màu vàng hiện phía trên tiêu đề trang trong hero.', 'voya'); ?></p>
</div>

<!-- Hero image -->
<div class="vy-meta-section">
    <label><?php esc_html_e('Hero Background Image', 'voya'); ?></label>
    <p class="vy-meta-hint">
        <?php esc_html_e('Nếu không chọn, sẽ dùng Featured Image của trang. Khuyến nghị: 1920×900px.', 'voya'); ?>
    </p>
    <input type="hidden" id="vy_cms_hero_image" name="vy_cms_hero_image" value="<?php echo esc_attr($hero_img_id); ?>">
    <div class="vy-media-row">
        <img id="vy_hero_preview" src="<?php echo esc_url($hero_src); ?>" alt=""
            style="<?php echo $hero_src ? '' : 'display:none'; ?>">
        <button type="button" class="button" id="vy_hero_choose">
            <?php esc_html_e('Chọn ảnh', 'voya'); ?>
        </button>
        <button type="button" class="button" id="vy_hero_remove"
            style="<?php echo $hero_img_id ? '' : 'display:none'; ?>">
            <?php esc_html_e('Xoá', 'voya'); ?>
        </button>
    </div>
</div>

<!-- Slider images -->
<div class="vy-meta-section">
    <label><?php esc_html_e('Slider Images', 'voya'); ?></label>
    <p class="vy-meta-hint">
        <?php esc_html_e('Tối đa 8 ảnh. Kéo để sắp xếp thứ tự. Tỷ lệ portrait (ví dụ 500×600px) đẹp nhất.', 'voya'); ?>
    </p>
    <input type="hidden" id="vy_cms_slider_images" name="vy_cms_slider_images"
        value="<?php echo esc_attr($slider_ids_raw); ?>">
    <div class="vy-slider-grid" id="vy_slider_grid">
        <?php foreach ($slider_ids as $sid):
                $thumb = wp_get_attachment_image_url($sid, 'thumbnail');
                if (!$thumb)
                    continue;
                ?>
        <div class="vy-slider-item" data-id="<?php echo intval($sid); ?>" draggable="true">
            <img src="<?php echo esc_url($thumb); ?>" alt="">
            <button type="button" class="vy-slider-remove"
                aria-label="<?php esc_attr_e('Xoá ảnh', 'voya'); ?>">&times;</button>
        </div>
        <?php endforeach; ?>
        <button type="button" class="vy-slider-add" id="vy_slider_add"
            aria-label="<?php esc_attr_e('Thêm ảnh', 'voya'); ?>">+</button>
    </div>
</div>

<script>
jQuery(function($) {

    // ── Hero image ─────────────────────────────────────────
    var heroFrame;

    $('#vy_hero_choose').click(function() {
        heroFrame = heroFrame || wp.media({
            title: 'Hero Image',
            multiple: false,
            button: {
                text: 'Chọn ảnh này'
            }
        });
        heroFrame.on('select', function() {
            var a = heroFrame.state().get('selection').first().toJSON();
            var src = (a.sizes || {}).thumbnail ? a.sizes.thumbnail.url : a.url;
            $('#vy_cms_hero_image').val(a.id);
            $('#vy_hero_preview').attr('src', src).show();
            $('#vy_hero_remove').show();
        });
        heroFrame.open();
    });

    $('#vy_hero_remove').click(function() {
        $('#vy_cms_hero_image').val('');
        $('#vy_hero_preview').hide();
        $(this).hide();
    });

    // ── Slider images ──────────────────────────────────────
    function updateIds() {
        var ids = [];
        $('#vy_slider_grid .vy-slider-item').each(function() {
            ids.push($(this).data('id'));
        });
        $('#vy_cms_slider_images').val(ids.join(','));
    }

    $('#vy_slider_add').click(function() {
        if ($('#vy_slider_grid .vy-slider-item').length >= 8) {
            alert('Tối đa 8 ảnh.');
            return;
        }
        var frame = wp.media({
            title: 'Slider Images',
            multiple: true,
            button: {
                text: 'Thêm vào slider'
            }
        });
        frame.on('select', function() {
            frame.state().get('selection').each(function(a) {
                if ($('#vy_slider_grid .vy-slider-item').length >= 8) return;
                var d = a.toJSON();
                var src = (d.sizes || {}).thumbnail ? d.sizes.thumbnail.url : d.url;
                var $el = $('<div class="vy-slider-item" data-id="' + d.id +
                    '" draggable="true">' +
                    '<img src="' + src + '" alt="">' +
                    '<button type="button" class="vy-slider-remove" aria-label="Xoá ảnh">&times;</button>' +
                    '</div>');
                $('#vy_slider_add').before($el);
            });
            updateIds();
        });
        frame.open();
    });

    // Remove
    $('#vy_slider_grid').on('click', '.vy-slider-remove', function(e) {
        e.stopPropagation();
        $(this).closest('.vy-slider-item').remove();
        updateIds();
    });

    // Drag-to-reorder (HTML5 native DnD)
    var dragged = null;

    $('#vy_slider_grid')
        .on('dragstart', '.vy-slider-item', function() {
            dragged = this;
            $(this).css('opacity', .4);
        })
        .on('dragend', '.vy-slider-item', function() {
            $(this).css('opacity', 1);
            dragged = null;
            updateIds();
        })
        .on('dragover', '.vy-slider-item', function(e) {
            e.preventDefault();
            if (!dragged || dragged === this) return;
            var mid = this.getBoundingClientRect().left + this.getBoundingClientRect().width / 2;
            $(dragged).detach();
            e.clientX < mid ? $(this).before(dragged) : $(this).after(dragged);
        });

});
</script>
<?php
}


// ── Save handler ──────────────────────────────────────────────
function vy_cms_meta_save($post_id)
{
    if (!isset($_POST['vy_cms_meta_nonce']))
        return;
    if (!wp_verify_nonce($_POST['vy_cms_meta_nonce'], 'vy_cms_meta_save'))
        return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        return;
    if (!current_user_can('edit_post', $post_id))
        return;

    // Tagline
    update_post_meta(
        $post_id,
        '_vy_cms_tagline',
        isset($_POST['vy_cms_tagline']) ? sanitize_text_field($_POST['vy_cms_tagline']) : ''
    );

    // Hero image
    $hero = intval($_POST['vy_cms_hero_image'] ?? 0);
    if ($hero) {
        update_post_meta($post_id, '_vy_cms_hero_image', $hero);
    } else {
        delete_post_meta($post_id, '_vy_cms_hero_image');
    }

    // Slider images (tối đa 8)
    $raw = sanitize_text_field($_POST['vy_cms_slider_images'] ?? '');
    $ids = array_slice(array_filter(array_map('intval', explode(',', $raw))), 0, 8);
    if ($ids) {
        update_post_meta($post_id, '_vy_cms_slider_images', implode(',', $ids));
    } else {
        delete_post_meta($post_id, '_vy_cms_slider_images');
    }
}
add_action('save_post', 'vy_cms_meta_save');