<?php
/**
 * VOYA — inc/contact-page-meta.php
 * Meta boxes cho Contact Page template:
 *   - Hero image + tagline (dùng chung key với cms-page-meta)
 *   - Address, Phone, Email, Hours
 *   - Google Maps embed URL
 *
 * Redesigned từ Wanderland:
 *   - Prefix wl_ → vy_
 *   - Meta keys: _vy_cms_* và _vy_contact_*
 */

if ( ! defined( 'ABSPATH' ) ) exit;


// ── Register meta box ─────────────────────────────────────────
function vy_contact_meta_box() {
    add_meta_box(
        'vy_contact_settings',
        __( 'Contact Page Settings', 'voya' ),
        'vy_contact_meta_render',
        'page',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'vy_contact_meta_box' );


// ── Render callback ───────────────────────────────────────────
function vy_contact_meta_render( $post ) {
    // Chỉ hiện khi đang dùng template page-contact.php
    if ( get_page_template_slug( $post->ID ) !== 'page-contact.php' ) return;

    wp_nonce_field( 'vy_contact_meta_save', 'vy_contact_nonce' );

    $hero_img_id = get_post_meta( $post->ID, '_vy_cms_hero_image',    true );
    $tagline     = get_post_meta( $post->ID, '_vy_cms_tagline',       true );
    $address     = get_post_meta( $post->ID, '_vy_contact_address',   true );
    $phone       = get_post_meta( $post->ID, '_vy_contact_phone',     true );
    $email       = get_post_meta( $post->ID, '_vy_contact_email',     true );
    $hours       = get_post_meta( $post->ID, '_vy_contact_hours',     true );
    $map_url     = get_post_meta( $post->ID, '_vy_contact_map_embed', true );

    $hero_src = $hero_img_id
        ? wp_get_attachment_image_url( intval( $hero_img_id ), 'thumbnail' )
        : '';
    ?>
<style>
.vy-cm-section {
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
}

.vy-cm-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.vy-cm-section>.vy-cm-section-title {
    font-weight: 700;
    font-size: 13px;
    margin: 0 0 14px;
    color: #1d2327;
}

.vy-cm-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 0;
}

.vy-cm-row.full {
    grid-template-columns: 1fr;
}

.vy-cm-field {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.vy-cm-field label {
    font-weight: 600;
    font-size: 12px;
    letter-spacing: .03em;
    color: #1d2327;
}

.vy-cm-field input,
.vy-cm-field textarea {
    width: 100%;
    box-sizing: border-box;
}

.vy-cm-hint {
    font-size: 11px;
    color: #888;
    margin: 0;
    line-height: 1.4;
}

.vy-media-row {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-top: 6px;
}

.vy-media-row img {
    width: 72px;
    height: 48px;
    object-fit: cover;
    border: 1px solid #ddd;
    border-radius: 3px;
}
</style>

<!-- ── Hero Settings ── -->
<div class="vy-cm-section">
    <p class="vy-cm-section-title"><?php esc_html_e( 'Hero Banner', 'voya' ); ?></p>
    <div class="vy-cm-row">

        <div class="vy-cm-field">
            <label for="vy_cms_tagline"><?php esc_html_e( 'Tagline (trên tiêu đề)', 'voya' ); ?></label>
            <input type="text" id="vy_cms_tagline" name="vy_cms_tagline" value="<?php echo esc_attr( $tagline ); ?>"
                placeholder="<?php esc_attr_e( 'Ví dụ: Liên hệ với chúng tôi', 'voya' ); ?>">
        </div>

        <div class="vy-cm-field">
            <label><?php esc_html_e( 'Ảnh nền Hero', 'voya' ); ?></label>
            <p class="vy-cm-hint">
                <?php esc_html_e( 'Nếu trống → dùng Featured Image. Khuyến nghị: 1920×900px.', 'voya' ); ?></p>
            <input type="hidden" id="vy_cms_hero_image" name="vy_cms_hero_image"
                value="<?php echo esc_attr( $hero_img_id ); ?>">
            <div class="vy-media-row">
                <img id="vy_hero_prev" src="<?php echo esc_url( $hero_src ); ?>" alt=""
                    style="<?php echo $hero_src ? '' : 'display:none'; ?>">
                <button type="button" class="button" id="vy_hero_pick">
                    <?php esc_html_e( 'Chọn ảnh', 'voya' ); ?>
                </button>
                <button type="button" class="button" id="vy_hero_clear"
                    style="<?php echo $hero_img_id ? '' : 'display:none'; ?>">
                    <?php esc_html_e( 'Xoá', 'voya' ); ?>
                </button>
            </div>
        </div>

    </div>
</div>

<!-- ── Contact Information ── -->
<div class="vy-cm-section">
    <p class="vy-cm-section-title"><?php esc_html_e( 'Thông tin liên hệ', 'voya' ); ?></p>
    <div class="vy-cm-row" style="margin-bottom:16px">

        <div class="vy-cm-field">
            <label for="vy_contact_address"><?php esc_html_e( 'Địa chỉ', 'voya' ); ?></label>
            <textarea id="vy_contact_address" name="vy_contact_address"
                rows="3"><?php echo esc_textarea( $address ); ?></textarea>
            <p class="vy-cm-hint">
                <?php esc_html_e( 'Hiển thị trong thẻ địa chỉ. Dùng Enter để xuống dòng.', 'voya' ); ?></p>
        </div>

        <div class="vy-cm-field">
            <label for="vy_contact_hours"><?php esc_html_e( 'Giờ làm việc', 'voya' ); ?></label>
            <textarea id="vy_contact_hours" name="vy_contact_hours"
                rows="3"><?php echo esc_textarea( $hours ); ?></textarea>
            <p class="vy-cm-hint"><?php esc_html_e( 'Ví dụ: Thứ 2–6: 8h–17h', 'voya' ); ?></p>
        </div>

    </div>

    <div class="vy-cm-row">

        <div class="vy-cm-field">
            <label for="vy_contact_phone"><?php esc_html_e( 'Số điện thoại', 'voya' ); ?></label>
            <input type="text" id="vy_contact_phone" name="vy_contact_phone" value="<?php echo esc_attr( $phone ); ?>"
                placeholder="<?php echo esc_attr( get_theme_mod( 'vy_phone', '' ) ); ?>">
            <p class="vy-cm-hint"><?php esc_html_e( 'Để trống → dùng số từ Customizer.', 'voya' ); ?></p>
        </div>

        <div class="vy-cm-field">
            <label for="vy_contact_email"><?php esc_html_e( 'Email nhận form', 'voya' ); ?></label>
            <input type="email" id="vy_contact_email" name="vy_contact_email" value="<?php echo esc_attr( $email ); ?>"
                placeholder="<?php echo esc_attr( get_theme_mod( 'vy_email', get_option( 'admin_email' ) ) ); ?>">
            <p class="vy-cm-hint">
                <?php esc_html_e( 'Form liên hệ gửi đến email này. Để trống → dùng email từ Customizer.', 'voya' ); ?>
            </p>
        </div>

    </div>
</div>

<!-- ── Google Maps ── -->
<div class="vy-cm-section">
    <p class="vy-cm-section-title"><?php esc_html_e( 'Bản đồ Google Maps', 'voya' ); ?></p>
    <div class="vy-cm-row full">
        <div class="vy-cm-field">
            <label for="vy_contact_map_embed"><?php esc_html_e( 'Google Maps Embed URL', 'voya' ); ?></label>
            <input type="url" id="vy_contact_map_embed" name="vy_contact_map_embed"
                value="<?php echo esc_url( $map_url ); ?>" placeholder="https://maps.google.com/maps?...&output=embed">
            <p class="vy-cm-hint">
                <?php esc_html_e( 'Google Maps → Chia sẻ → Nhúng bản đồ → copy URL trong thuộc tính src. Để trống để ẩn bản đồ.', 'voya' ); ?>
            </p>
        </div>
    </div>
</div>

<script>
jQuery(function($) {
    var frame;
    $('#vy_hero_pick').on('click', function() {
        frame = frame || wp.media({
            title: 'Hero Image',
            multiple: false,
            button: {
                text: 'Chọn ảnh này'
            }
        });
        frame.on('select', function() {
            var a = frame.state().get('selection').first().toJSON();
            var src = (a.sizes || {}).thumbnail ? a.sizes.thumbnail.url : a.url;
            $('#vy_cms_hero_image').val(a.id);
            $('#vy_hero_prev').attr('src', src).show();
            $('#vy_hero_clear').show();
        });
        frame.open();
    });

    $('#vy_hero_clear').on('click', function() {
        $('#vy_cms_hero_image').val('');
        $('#vy_hero_prev').hide();
        $(this).hide();
    });
});
</script>
<?php
}


// ── Save handler ──────────────────────────────────────────────
function vy_contact_meta_save( $post_id ) {
    if ( ! isset( $_POST['vy_contact_nonce'] ) ) return;
    if ( ! wp_verify_nonce( $_POST['vy_contact_nonce'], 'vy_contact_meta_save' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;
    if ( get_page_template_slug( $post_id ) !== 'page-contact.php' ) return;

    // Text fields
    $text_fields = [
        'vy_cms_tagline'      => '_vy_cms_tagline',
        'vy_contact_address'  => '_vy_contact_address',
        'vy_contact_phone'    => '_vy_contact_phone',
        'vy_contact_hours'    => '_vy_contact_hours',
    ];

    foreach ( $text_fields as $post_key => $meta_key ) {
        $val = isset( $_POST[ $post_key ] ) ? sanitize_text_field( $_POST[ $post_key ] ) : '';
        if ( $val ) {
            update_post_meta( $post_id, $meta_key, $val );
        } else {
            delete_post_meta( $post_id, $meta_key );
        }
    }

    // Email
    $em = isset( $_POST['vy_contact_email'] ) ? sanitize_email( $_POST['vy_contact_email'] ) : '';
    $em ? update_post_meta( $post_id, '_vy_contact_email', $em )
        : delete_post_meta( $post_id, '_vy_contact_email' );

    // Hero image
    $hero = intval( $_POST['vy_cms_hero_image'] ?? 0 );
    $hero ? update_post_meta( $post_id, '_vy_cms_hero_image', $hero )
          : delete_post_meta( $post_id, '_vy_cms_hero_image' );

    // Map embed URL
    $map = isset( $_POST['vy_contact_map_embed'] ) ? esc_url_raw( $_POST['vy_contact_map_embed'] ) : '';
    $map ? update_post_meta( $post_id, '_vy_contact_map_embed', $map )
         : delete_post_meta( $post_id, '_vy_contact_map_embed' );
}
add_action( 'save_post', 'vy_contact_meta_save' );