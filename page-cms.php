<?php
/**
 * Template Name: CMS Page (About / Policy / Terms)
 * Template Post Type: page
 *
 * VOYA — page-cms.php
 * Redesigned từ Wanderland:
 *  - Bỏ brush PNG dependency
 *  - Hero: featured image → custom meta → solid dark fallback
 *  - Slider: clean minimal arrows (FA6)
 *  - Content: Cormorant headings, nhất quán với theme
 */

get_header();

$post_id = get_the_ID();

// ── Hero image: ưu tiên custom meta → featured image → solid bg ──
$hero_img_id = get_post_meta($post_id, '_vy_cms_hero_image', true);
$hero_fallback = get_post_thumbnail_id($post_id);

if ($hero_img_id) {
    $hero_bg = wp_get_attachment_image_url(intval($hero_img_id), 'wl-hero')
        ?: wp_get_attachment_image_url(intval($hero_img_id), 'full');
} elseif ($hero_fallback) {
    $hero_bg = wp_get_attachment_image_url($hero_fallback, 'wl-hero')
        ?: wp_get_attachment_image_url($hero_fallback, 'full');
} else {
    $hero_bg = '';
}

// ── Slider images ──────────────────────────────────────────────
$slider_ids_raw = get_post_meta($post_id, '_vy_cms_slider_images', true);
$slider_ids = $slider_ids_raw
    ? array_filter(array_map('intval', explode(',', $slider_ids_raw)))
    : [];

// ── Page tagline ───────────────────────────────────────────────
$tagline = get_post_meta($post_id, '_vy_cms_tagline', true);
?>

<main id="vy-cms-main" class="vy-cms-main" data-hero>

    <!-- ============================================================
         1. HERO BANNER
         Nhất quán với single.php: featured image full-viewport
    ============================================================ -->
    <section class="vy-cms-hero">

        <?php if ($hero_bg): ?>
        <div class="vy-cms-hero__img" style="background-image:url('<?php echo esc_url($hero_bg); ?>')" role="img"
            aria-label="<?php the_title_attribute(); ?>">
        </div>
        <?php else: ?>
        <div class="vy-cms-hero__img vy-cms-hero__img--empty"></div>
        <?php endif; ?>

        <div class="vy-cms-hero__overlay" aria-hidden="true"></div>

        <!-- Content — bottom-left -->
        <div class="vy-cms-hero__content-wrap">
            <div class="vy-container">
                <div class="vy-cms-hero__content">

                    <?php if ($tagline): ?>
                    <span class="vy-cms-hero__tagline">
                        <?php echo esc_html($tagline); ?>
                    </span>
                    <?php endif; ?>

                    <h1 class="vy-cms-hero__title"><?php the_title(); ?></h1>

                    <!-- Decorative line -->
                    <div class="vy-cms-hero__line" aria-hidden="true"></div>

                </div>
            </div>
        </div>

        <!-- Scroll hint -->
        <div class="vy-cms-hero__scroll" aria-hidden="true">
            <span class="vy-cms-hero__scroll-line"></span>
        </div>

    </section><!-- .vy-cms-hero -->


    <!-- ============================================================
         2. IMAGE SLIDER (hiển thị nếu có ảnh)
    ============================================================ -->
    <?php if (!empty($slider_ids)): ?>

    <section class="vy-cms-slider-section" aria-label="<?php esc_attr_e('Thư viện ảnh', 'voya'); ?>">
        <div class="vy-container">
            <div class="vy-cms-slider-wrap">

                <!-- Slider viewport -->
                <div class="vy-cms-slider" id="vy-cms-slider" tabindex="0" aria-roledescription="carousel">
                    <div class="vy-cms-slider__track" id="vy-cms-slider-track">
                        <?php foreach ($slider_ids as $img_id):
                                $img_url = wp_get_attachment_image_url($img_id, 'wl-card')
                                    ?: wp_get_attachment_image_url($img_id, 'full');
                                $img_alt = get_post_meta($img_id, '_wp_attachment_image_alt', true);
                                if (!$img_url)
                                    continue;
                                ?>
                        <div class="vy-cms-slide" role="group" aria-roledescription="slide">
                            <div class="vy-cms-slide__inner">
                                <img src="<?php echo esc_url($img_url); ?>" alt="<?php echo esc_attr($img_alt); ?>"
                                    loading="lazy">
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Arrow buttons -->
                <button class="vy-cms-arrow vy-cms-arrow--prev" id="vy-cms-prev" type="button"
                    aria-label="<?php esc_attr_e('Ảnh trước', 'voya'); ?>">
                    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                </button>

                <button class="vy-cms-arrow vy-cms-arrow--next" id="vy-cms-next" type="button"
                    aria-label="<?php esc_attr_e('Ảnh tiếp theo', 'voya'); ?>">
                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </button>

                <!-- Dots indicator -->
                <div class="vy-cms-slider__dots" id="vy-cms-dots" role="tablist"
                    aria-label="<?php esc_attr_e('Chọn ảnh', 'voya'); ?>">
                </div>

            </div>
        </div>
    </section>

    <?php endif; ?>


    <!-- ============================================================
         3. PAGE CONTENT
    ============================================================ -->
    <div class="vy-cms-content-wrap">
        <div class="vy-cms-container">
            <div class="vy-cms-content">
                <?php
                while (have_posts()):
                    the_post();
                    the_content();
                endwhile;
                ?>
            </div>
        </div>
    </div>

</main><!-- .vy-cms-main -->

<?php get_footer(); ?>