<?php
/**
 * Template Name: Contact Page
 * Template Post Type: page
 *
 * VOYA — page-contact.php
 * Redesigned từ Wanderland:
 *  - Bỏ brush PNG, hero nhất quán với page-cms.php
 *  - Ionicons → FA6
 *  - Prefix wl_ → vy_
 *  - Form: tiếng Việt
 *  - Social: dùng vy_social_links() helper từ functions.php
 */

get_header();

$post_id = get_the_ID();

// ── Hero ──────────────────────────────────────────────────────
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

$tagline = get_post_meta($post_id, '_vy_cms_tagline', true);

// ── Contact info ──────────────────────────────────────────────
$address = get_post_meta($post_id, '_vy_contact_address', true) ?: '';
$phone = get_post_meta($post_id, '_vy_contact_phone', true)
    ?: get_theme_mod('vy_phone', '');
$email = get_post_meta($post_id, '_vy_contact_email', true)
    ?: get_theme_mod('vy_email', '');
$hours = get_post_meta($post_id, '_vy_contact_hours', true) ?: '';
$map_url = get_post_meta($post_id, '_vy_contact_map_embed', true) ?: '';

// ── Form submission ───────────────────────────────────────────
$form_sent = false;
$form_error = '';

if (
    isset($_POST['vy_contact_submit'])
    && wp_verify_nonce($_POST['_vy_nonce'] ?? '', 'vy_contact_form')
) {

    $cf_name = sanitize_text_field($_POST['cf_name'] ?? '');
    $cf_email = sanitize_email($_POST['cf_email'] ?? '');
    $cf_subject = sanitize_text_field($_POST['cf_subject'] ?? '');
    $cf_message = sanitize_textarea_field($_POST['cf_message'] ?? '');

    if (empty($cf_name) || empty($cf_email) || empty($cf_message)) {
        $form_error = __('Vui lòng điền đầy đủ các trường bắt buộc.', 'voya');
    } elseif (!is_email($cf_email)) {
        $form_error = __('Địa chỉ email không hợp lệ.', 'voya');
    } else {
        $to = $email ?: get_option('admin_email');
        $subject = $cf_subject ?: sprintf(__('Liên hệ từ %s', 'voya'), $cf_name);
        $body = sprintf("Họ tên: %s\nEmail: %s\n\n%s", $cf_name, $cf_email, $cf_message);
        $headers = ['Content-Type: text/plain; charset=UTF-8', 'Reply-To: ' . $cf_email];

        $sent = wp_mail($to, $subject, $body, $headers);
        $form_sent = $sent;
        if (!$sent) {
            $form_error = __('Không thể gửi tin nhắn. Vui lòng thử lại.', 'voya');
        }
    }
}

// Helper: giữ lại giá trị form sau khi lỗi
function vy_cf_val($key, $sanitize = 'text')
{
    if (!isset($_POST[$key]))
        return '';
    return $sanitize === 'email'
        ? esc_attr(sanitize_email($_POST[$key]))
        : esc_attr(sanitize_text_field($_POST[$key]));
}
?>

<main id="vy-contact-main" class="vy-cms-main vy-contact-main" data-hero>

    <!-- ============================================================
         HERO — tái dụng .vy-cms-hero từ page-cms
    ============================================================ -->
    <section class="vy-cms-hero vy-contact-hero">

        <?php if ($hero_bg): ?>
        <div class="vy-cms-hero__img" style="background-image:url('<?php echo esc_url($hero_bg); ?>')" role="img"
            aria-label="<?php the_title_attribute(); ?>">
        </div>
        <?php else: ?>
        <div class="vy-cms-hero__img vy-cms-hero__img--empty"></div>
        <?php endif; ?>

        <div class="vy-cms-hero__overlay" aria-hidden="true"></div>

        <div class="vy-cms-hero__content-wrap">
            <div class="vy-container">
                <div class="vy-cms-hero__content">
                    <?php if ($tagline): ?>
                    <span class="vy-cms-hero__tagline"><?php echo esc_html($tagline); ?></span>
                    <?php endif; ?>
                    <h1 class="vy-cms-hero__title"><?php the_title(); ?></h1>
                    <div class="vy-cms-hero__line" aria-hidden="true"></div>
                </div>
            </div>
        </div>

        <div class="vy-cms-hero__scroll" aria-hidden="true">
            <span class="vy-cms-hero__scroll-line"></span>
        </div>

    </section><!-- .vy-cms-hero -->


    <!-- ============================================================
         CONTACT BODY
    ============================================================ -->
    <div class="vy-contact-body">
        <div class="vy-contact-container">

            <!-- ── LEFT: Info + Map ── -->
            <aside class="vy-contact-info" id="vy-contact-info">

                <span class="vy-label"><?php esc_html_e('Thông tin liên hệ', 'voya'); ?></span>
                <h2 class="vy-contact-info__title">
                    <?php esc_html_e('Liên hệ ', 'voya'); ?>
                    <em><?php esc_html_e('với chúng tôi', 'voya'); ?></em>
                </h2>

                <?php if (has_excerpt()): ?>
                <p class="vy-contact-info__desc"><?php the_excerpt(); ?></p>
                <?php endif; ?>

                <!-- Info cards -->
                <div class="vy-contact-cards">

                    <?php if ($address): ?>
                    <div class="vy-contact-card">
                        <div class="vy-contact-card__icon" aria-hidden="true">
                            <i class="fa-solid fa-location-dot"></i>
                        </div>
                        <div class="vy-contact-card__body">
                            <span class="vy-contact-card__label"><?php esc_html_e('Địa chỉ', 'voya'); ?></span>
                            <span class="vy-contact-card__value">
                                <?php echo nl2br(esc_html($address)); ?>
                            </span>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($phone): ?>
                    <div class="vy-contact-card">
                        <div class="vy-contact-card__icon" aria-hidden="true">
                            <i class="fa-solid fa-phone"></i>
                        </div>
                        <div class="vy-contact-card__body">
                            <span class="vy-contact-card__label"><?php esc_html_e('Điện thoại', 'voya'); ?></span>
                            <a class="vy-contact-card__value vy-contact-card__link"
                                href="tel:<?php echo esc_attr(preg_replace('/\s+/', '', $phone)); ?>">
                                <?php echo esc_html($phone); ?>
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($email): ?>
                    <div class="vy-contact-card">
                        <div class="vy-contact-card__icon" aria-hidden="true">
                            <i class="fa-regular fa-envelope"></i>
                        </div>
                        <div class="vy-contact-card__body">
                            <span class="vy-contact-card__label"><?php esc_html_e('Email', 'voya'); ?></span>
                            <a class="vy-contact-card__value vy-contact-card__link"
                                href="mailto:<?php echo esc_attr($email); ?>">
                                <?php echo esc_html($email); ?>
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($hours): ?>
                    <div class="vy-contact-card">
                        <div class="vy-contact-card__icon" aria-hidden="true">
                            <i class="fa-regular fa-clock"></i>
                        </div>
                        <div class="vy-contact-card__body">
                            <span class="vy-contact-card__label"><?php esc_html_e('Giờ làm việc', 'voya'); ?></span>
                            <span class="vy-contact-card__value">
                                <?php echo nl2br(esc_html($hours)); ?>
                            </span>
                        </div>
                    </div>
                    <?php endif; ?>

                </div><!-- .vy-contact-cards -->

                <!-- Social links -->
                <?php $social_html = vy_social_links('vy-contact-social__link');
                if ($social_html): ?>
                <div class="vy-contact-social" aria-label="<?php esc_attr_e('Mạng xã hội', 'voya'); ?>">
                    <span class="vy-contact-social__label"><?php esc_html_e('Theo dõi', 'voya'); ?></span>
                    <?php echo $social_html; ?>
                </div>
                <?php endif; ?>

                <!-- Google Maps embed -->
                <?php if ($map_url): ?>
                <div class="vy-contact-map">
                    <iframe src="<?php echo esc_url($map_url); ?>" width="100%" height="240" style="border:0;"
                        allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"
                        title="<?php esc_attr_e('Bản đồ địa điểm', 'voya'); ?>">
                    </iframe>
                </div>
                <?php endif; ?>

            </aside><!-- .vy-contact-info -->


            <!-- ── RIGHT: Contact Form ── -->
            <div class="vy-contact-form-wrap">

                <span class="vy-label"><?php esc_html_e('Gửi tin nhắn', 'voya'); ?></span>
                <h2 class="vy-contact-form__title">
                    <?php esc_html_e('Để lại ', 'voya'); ?>
                    <em><?php esc_html_e('lời nhắn', 'voya'); ?></em>
                </h2>

                <?php if ($form_sent): ?>
                <!-- Success -->
                <div class="vy-form-notice vy-form-notice--success" role="alert">
                    <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                    <p><?php esc_html_e('Tin nhắn đã được gửi thành công! Chúng tôi sẽ phản hồi sớm nhất có thể.', 'voya'); ?>
                    </p>
                </div>

                <?php else: ?>

                <?php if ($form_error): ?>
                <div class="vy-form-notice vy-form-notice--error" role="alert">
                    <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                    <p><?php echo esc_html($form_error); ?></p>
                </div>
                <?php endif; ?>

                <form class="vy-contact-form" id="vy-contact-form" method="post" action="#vy-contact-form" novalidate>
                    <?php wp_nonce_field('vy_contact_form', '_vy_nonce'); ?>
                    <input type="hidden" name="vy_contact_submit" value="1">

                    <!-- Row 1: Họ tên + Email -->
                    <div class="vy-cf-row vy-cf-row--2">
                        <div class="vy-cf-field">
                            <label class="vy-cf-label" for="cf_name">
                                <?php esc_html_e('Họ và tên', 'voya'); ?>
                                <span class="vy-cf-required" aria-hidden="true">*</span>
                            </label>
                            <input type="text" id="cf_name" name="cf_name" class="vy-cf-input"
                                placeholder="<?php esc_attr_e('Nguyễn Văn A', 'voya'); ?>"
                                value="<?php echo vy_cf_val('cf_name'); ?>" required autocomplete="name">
                        </div>
                        <div class="vy-cf-field">
                            <label class="vy-cf-label" for="cf_email">
                                <?php esc_html_e('Địa chỉ email', 'voya'); ?>
                                <span class="vy-cf-required" aria-hidden="true">*</span>
                            </label>
                            <input type="email" id="cf_email" name="cf_email" class="vy-cf-input"
                                placeholder="<?php esc_attr_e('email@example.com', 'voya'); ?>"
                                value="<?php echo vy_cf_val('cf_email', 'email'); ?>" required autocomplete="email">
                        </div>
                    </div>

                    <!-- Row 2: Chủ đề -->
                    <div class="vy-cf-row">
                        <div class="vy-cf-field">
                            <label class="vy-cf-label" for="cf_subject">
                                <?php esc_html_e('Chủ đề', 'voya'); ?>
                            </label>
                            <input type="text" id="cf_subject" name="cf_subject" class="vy-cf-input"
                                placeholder="<?php esc_attr_e('Chúng tôi có thể giúp gì cho bạn?', 'voya'); ?>"
                                value="<?php echo vy_cf_val('cf_subject'); ?>">
                        </div>
                    </div>

                    <!-- Row 3: Nội dung -->
                    <div class="vy-cf-row">
                        <div class="vy-cf-field">
                            <label class="vy-cf-label" for="cf_message">
                                <?php esc_html_e('Nội dung', 'voya'); ?>
                                <span class="vy-cf-required" aria-hidden="true">*</span>
                            </label>
                            <textarea id="cf_message" name="cf_message" class="vy-cf-textarea"
                                placeholder="<?php esc_attr_e('Viết nội dung tin nhắn của bạn tại đây…', 'voya'); ?>"
                                required rows="6"><?php
                                    if (isset($_POST['cf_message'])) {
                                        echo esc_textarea(sanitize_textarea_field($_POST['cf_message']));
                                    }
                                    ?></textarea>
                        </div>
                    </div>

                    <!-- Submit -->
                    <div class="vy-cf-row vy-cf-row--submit">
                        <button type="submit" class="vy-cf-submit" id="vy-cf-submit">
                            <span class="vy-cf-submit__text">
                                <?php esc_html_e('Gửi tin nhắn', 'voya'); ?>
                            </span>
                            <span class="vy-cf-submit__icon" aria-hidden="true">
                                <i class="fa-solid fa-arrow-right vy-cf-submit__arrow"></i>
                                <i class="fa-solid fa-circle-notch fa-spin vy-cf-submit__spinner"></i>
                            </span>
                        </button>
                        <p class="vy-cf-required-note">
                            <span class="vy-cf-required">*</span>
                            <?php esc_html_e('Trường bắt buộc', 'voya'); ?>
                        </p>
                    </div>

                </form>

                <?php endif; ?>

            </div><!-- .vy-contact-form-wrap -->

        </div><!-- .vy-contact-container -->
    </div><!-- .vy-contact-body -->

</main>

<?php get_footer(); ?>