<?php
/**
 * WANDERLAND — Destination Custom Post Type
 * File: inc/destinations-cpt.php
 *
 * Include trong functions.php:
 *   require_once get_template_directory() . '/inc/destinations-cpt.php';
 *
 * Admin: WP Admin → Destinations → Add New
 * Fields: Title, Featured Image, Excerpt (short desc), Custom: coordinates (optional)
 */

if (!defined('ABSPATH'))
    exit;


// ── Register CPT ──────────────────────────────────────────────
add_action('init', 'wl_register_destination_cpt');

function wl_register_destination_cpt()
{
    $labels = array(
        'name' => __('Destinations', 'wanderland'),
        'singular_name' => __('Destination', 'wanderland'),
        'add_new' => __('Add New', 'wanderland'),
        'add_new_item' => __('Add New Destination', 'wanderland'),
        'edit_item' => __('Edit Destination', 'wanderland'),
        'new_item' => __('New Destination', 'wanderland'),
        'view_item' => __('View Destination', 'wanderland'),
        'search_items' => __('Search Destinations', 'wanderland'),
        'not_found' => __('No destinations found', 'wanderland'),
        'not_found_in_trash' => __('No destinations found in Trash', 'wanderland'),
        'menu_name' => __('Destinations', 'wanderland'),
    );

    register_post_type('wl_destination', array(
        'labels' => $labels,
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'destination'),
        'capability_type' => 'post',
        'has_archive' => false,
        'hierarchical' => false,
        'menu_position' => 20,
        'menu_icon' => 'dashicons-location-alt',
        'supports' => array(
            'title',
            'editor',        // full description
            'excerpt',       // short description for timeline
            'thumbnail',     // featured image (flag / photo)
            'page-attributes', // menu_order → control display order
        ),
        'show_in_rest' => true, // Gutenberg + REST API
    ));
}


// ── Meta box: Coordinates ─────────────────────────────────────
add_action('add_meta_boxes', 'wl_destination_meta_boxes');

function wl_destination_meta_boxes()
{
    add_meta_box(
        'wl_destination_coords',
        __('Coordinates & Link', 'wanderland'),
        'wl_destination_coords_cb',
        'wl_destination',
        'side',
        'default'
    );
}

function wl_destination_coords_cb($post)
{
    wp_nonce_field('wl_destination_meta', 'wl_dest_nonce');

    $lat = get_post_meta($post->ID, '_wl_dest_lat', true);
    $lng = get_post_meta($post->ID, '_wl_dest_lng', true);
    $url = get_post_meta($post->ID, '_wl_dest_url', true);
    ?>
<p>
    <label for="wl_dest_lat"><strong><?php esc_html_e('Latitude', 'wanderland'); ?></strong></label><br>
    <input type="text" id="wl_dest_lat" name="wl_dest_lat" value="<?php echo esc_attr($lat); ?>" style="width:100%"
        placeholder="e.g. 59.3293">
</p>
<p>
    <label for="wl_dest_lng"><strong><?php esc_html_e('Longitude', 'wanderland'); ?></strong></label><br>
    <input type="text" id="wl_dest_lng" name="wl_dest_lng" value="<?php echo esc_attr($lng); ?>" style="width:100%"
        placeholder="e.g. 18.0686">
</p>
<p>
    <label
        for="wl_dest_url"><strong><?php esc_html_e('Destination URL (optional)', 'wanderland'); ?></strong></label><br>
    <input type="url" id="wl_dest_url" name="wl_dest_url" value="<?php echo esc_attr($url); ?>" style="width:100%"
        placeholder="https://...">
    <small style="color:#888"><?php esc_html_e('Leave blank to use post permalink', 'wanderland'); ?></small>
</p>
<?php
}

// Save meta
add_action('save_post_wl_destination', 'wl_save_destination_meta');

function wl_save_destination_meta($post_id)
{
    if (
        !isset($_POST['wl_dest_nonce'])
        || !wp_verify_nonce($_POST['wl_dest_nonce'], 'wl_destination_meta')
    )
        return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        return;
    if (!current_user_can('edit_post', $post_id))
        return;

    foreach (array('wl_dest_lat', 'wl_dest_lng', 'wl_dest_url') as $key) {
        if (isset($_POST[$key])) {
            update_post_meta($post_id, '_' . $key, sanitize_text_field($_POST[$key]));
        }
    }
}


// ── Helper: get destinations ──────────────────────────────────
/**
 * Query destinations ordered by menu_order (drag in admin to reorder).
 * @param int $limit Max number of destinations
 * @return WP_Query
 */
function wl_get_destinations($limit = 9)
{
    return new WP_Query(array(
        'post_type' => 'wl_destination',
        'post_status' => 'publish',
        'posts_per_page' => $limit,
        'orderby' => 'menu_order',
        'order' => 'ASC',
    ));
}


// ── Customizer: số lượng destinations hiển thị ───────────────
add_action('customize_register', 'wl_destinations_customizer');

function wl_destinations_customizer($wp_customize)
{

    $wp_customize->add_section('wl_destinations', array(
        'title' => __('Destinations Timeline (Home)', 'wanderland'),
        'priority' => 37,
    ));

    // Số lượng hiển thị
    $wp_customize->add_setting('wl_destinations_count', array(
        'default' => 9,
        'sanitize_callback' => 'absint',
        'transport' => 'refresh',
    ));
    $wp_customize->add_control('wl_destinations_count', array(
        'label' => __('Số địa danh hiển thị', 'wanderland'),
        'description' => __('Tối đa bao nhiêu địa danh xuất hiện trên timeline.', 'wanderland'),
        'section' => 'wl_destinations',
        'type' => 'number',
        'input_attrs' => array('min' => 3, 'max' => 20, 'step' => 1),
    ));
}


// ── Register Destination Category Taxonomy ───────────────────
add_action('init', 'wl_register_destination_taxonomy');

function wl_register_destination_taxonomy()
{
    register_taxonomy('wl_destination_cat', 'wl_destination', array(
        'labels' => array(
            'name' => __('Destination Categories', 'wanderland'),
            'singular_name' => __('Destination Category', 'wanderland'),
            'add_new_item' => __('Add New Category', 'wanderland'),
            'edit_item' => __('Edit Category', 'wanderland'),
            'menu_name' => __('Categories', 'wanderland'),
        ),
        'public' => true,
        'hierarchical' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_rest' => true,
        'rewrite' => array('slug' => 'destination-category'),
    ));
}


// ── Term meta: category image (normal + hover) ───────────────
add_action('wl_destination_cat_add_form_fields', 'wl_dest_cat_add_fields');
add_action('wl_destination_cat_edit_form_fields', 'wl_dest_cat_edit_fields');
add_action('created_wl_destination_cat', 'wl_save_dest_cat_meta');
add_action('edited_wl_destination_cat', 'wl_save_dest_cat_meta');
add_action('admin_enqueue_scripts', 'wl_dest_cat_enqueue_media');

function wl_dest_cat_enqueue_media($hook)
{
    if (
        in_array($hook, array('edit-tags.php', 'term.php'))
        && isset($_GET['taxonomy']) && $_GET['taxonomy'] === 'wl_destination_cat'
    ) {
        wp_enqueue_media();
        wp_add_inline_script('media-upload', '
(function($){
    $(document).on("click", ".wl-cat-upload-btn", function(e){
        e.preventDefault();
        var btn    = $(this);
        var target = btn.data("target");
        var frame  = wp.media({
            title: "Select Icon Image",
            button: { text: "Use this image" },
            multiple: false,
            library: { type: "image" }
        });
        frame.on("select", function(){
            var att = frame.state().get("selection").first().toJSON();
            $("#" + target + "_url").val(att.url);
            $("#" + target + "_preview").attr("src", att.url).show();
            $("#" + target + "_remove").show();
        });
        frame.open();
    });
    $(document).on("click", ".wl-cat-remove-btn", function(e){
        e.preventDefault();
        var btn    = $(this);
        var target = btn.data("target");
        $("#" + target + "_url").val("");
        $("#" + target + "_preview").hide();
        btn.hide();
    });
})(jQuery);
        ');
    }
}

function wl_dest_cat_add_fields()
{
    wp_nonce_field('wl_dest_cat_meta', 'wl_dest_cat_nonce'); ?>
<div class="form-field">
    <label><?php esc_html_e('Icon (default)', 'wanderland'); ?></label>
    <?php wl_dest_cat_image_field('wl_cat_image', ''); ?>
    <p class="description">
        <?php esc_html_e('Upload icon mặc định (~92×74px). Dạng PNG có nền trong suốt.', 'wanderland'); ?></p>
</div>
<div class="form-field">
    <label><?php esc_html_e('Icon (hover)', 'wanderland'); ?></label>
    <?php wl_dest_cat_image_field('wl_cat_image_hover', ''); ?>
    <p class="description"><?php esc_html_e('Upload icon khi hover (thường có màu khác).', 'wanderland'); ?></p>
</div>
<?php
}

function wl_dest_cat_edit_fields($term)
{
    wp_nonce_field('wl_dest_cat_meta', 'wl_dest_cat_nonce');
    $img = get_term_meta($term->term_id, '_wl_cat_image', true);
    $img_h = get_term_meta($term->term_id, '_wl_cat_image_hover', true); ?>
<tr class="form-field">
    <th><label><?php esc_html_e('Icon (default)', 'wanderland'); ?></label></th>
    <td>
        <?php wl_dest_cat_image_field('wl_cat_image', $img); ?>
        <p class="description"><?php esc_html_e('Upload icon mặc định (~92×74px). PNG nền trong.', 'wanderland'); ?>
        </p>
    </td>
</tr>
<tr class="form-field">
    <th><label><?php esc_html_e('Icon (hover)', 'wanderland'); ?></label></th>
    <td>
        <?php wl_dest_cat_image_field('wl_cat_image_hover', $img_h); ?>
        <p class="description"><?php esc_html_e('Upload icon khi hover.', 'wanderland'); ?></p>
    </td>
</tr>
<?php
}

/**
 * Render upload field: hidden URL input + preview + buttons
 */
function wl_dest_cat_image_field($key, $current_url)
{
    $id = esc_attr($key); ?>
<div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-top:4px">
    <input type="hidden" id="<?php echo $id; ?>_url" name="<?php echo esc_attr($key); ?>"
        value="<?php echo esc_attr($current_url); ?>">

    <?php if ($current_url): ?>
    <img id="<?php echo $id; ?>_preview" src="<?php echo esc_url($current_url); ?>" alt=""
        style="max-height:60px;max-width:100px;border:1px solid #ddd;border-radius:3px;object-fit:contain;background:#f9f9f9;padding:4px">
    <?php else: ?>
    <img id="<?php echo $id; ?>_preview" src="" alt=""
        style="max-height:60px;max-width:100px;border:1px solid #ddd;border-radius:3px;object-fit:contain;background:#f9f9f9;padding:4px;display:none">
    <?php endif; ?>

    <button type="button" class="button wl-cat-upload-btn" data-target="<?php echo $id; ?>">
        <span class="dashicons dashicons-upload" style="margin-top:3px;font-size:16px"></span>
        <?php esc_html_e('Upload ảnh', 'wanderland'); ?>
    </button>

    <button type="button" class="button wl-cat-remove-btn" data-target="<?php echo $id; ?>"
        <?php echo $current_url ? '' : 'style="display:none"'; ?>>
        <span class="dashicons dashicons-trash" style="margin-top:3px;font-size:16px;color:#a00"></span>
        <?php esc_html_e('Xoá', 'wanderland'); ?>
    </button>
</div>
<?php
}

function wl_save_dest_cat_meta($term_id)
{
    if (
        !isset($_POST['wl_dest_cat_nonce'])
        || !wp_verify_nonce($_POST['wl_dest_cat_nonce'], 'wl_dest_cat_meta')
    )
        return;
    foreach (array('wl_cat_image', 'wl_cat_image_hover') as $key) {
        if (isset($_POST[$key])) {
            update_term_meta($term_id, '_' . $key, esc_url_raw($_POST[$key]));
        }
    }
}


// ── Customizer: Section 6 CTA text ───────────────────────────
add_action('customize_register', 'wl_dcl_customizer');

function wl_dcl_customizer($wp_customize)
{
    $wp_customize->add_section('wl_dcl', array(
        'title' => __('Section 6 — CTA Text (Home)', 'wanderland'),
        'priority' => 38,
    ));

    foreach (array(
        'wl_dcl_tagline' => array('Lorem ipsum dolor sit amet', __('Tagline', 'wanderland'), 'text'),
        'wl_dcl_title' => array('HOW TO FIND YOUR DIGITAL RESORT', __('Title', 'wanderland'), 'text'),
        'wl_dcl_body' => array('Lorem ipsum dolor sit amet, conse ctetur nus adipisic ing elit, sed do eiusmod tempor incididu nt ut labore et dolore magna aliqua.', __('Body text', 'wanderland'), 'textarea'),
    ) as $key => $args) {
        $wp_customize->add_setting($key, array(
            'default' => $args[0],
            'sanitize_callback' => $args[2] === 'textarea' ? 'sanitize_textarea_field' : 'sanitize_text_field',
        ));
        $wp_customize->add_control($key, array(
            'label' => $args[1],
            'section' => 'wl_dcl',
            'type' => $args[2],
        ));
    }
}