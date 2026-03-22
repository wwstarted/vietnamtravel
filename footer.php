<footer class="vy-footer" role="contentinfo">

    <!-- ============================================================
         FOOTER TOP: Logo + Bio
         ============================================================ -->
    <div class="vy-footer__top">
        <div class="vy-container--wide">
            <div class="vy-footer__top-inner">

                <!-- Logo -->
                <div class="vy-footer__brand">
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="vy-footer__logo-link">
                        <?php
                        $footer_logo_id = get_theme_mod('vy_logo_footer');
                        if ($footer_logo_id): ?>
                            <img src="<?php echo esc_url(wp_get_attachment_image_url($footer_logo_id, 'full')); ?>"
                                alt="<?php bloginfo('name'); ?>" class="vy-footer__logo" loading="lazy">
                        <?php else: ?>
                            <span class="vy-footer__logo-text"><?php bloginfo('name'); ?></span>
                        <?php endif; ?>
                    </a>
                </div>

                <!-- Bio text -->
                <div class="vy-footer__bio">
                    <p class="vy-footer__bio-text">
                        <?php echo wp_kses_post(get_theme_mod(
                            'vy_footer_bio',
                            'Blog du lịch chia sẻ những hành trình, trải nghiệm và bí kíp khám phá thế giới.'
                        )); ?>
                    </p>
                </div>

            </div>
        </div>
    </div><!-- .vy-footer__top -->


    <!-- ============================================================
         FOOTER MIDDLE: 4 columns
         ============================================================ -->
    <div class="vy-footer__middle">
        <div class="vy-container--wide">
            <div class="vy-footer__grid">

                <!-- Col 1: About -->
                <div class="vy-footer__col">
                    <?php if (is_active_sidebar('footer-col-1')): ?>
                        <?php dynamic_sidebar('footer-col-1'); ?>
                    <?php else: ?>
                        <div class="vy-footer-widget">
                            <h6 class="vy-footer-widget__title"><?php esc_html_e('Về Blog', 'voya'); ?></h6>
                            <p class="vy-footer-widget__text">
                                <?php echo wp_kses_post(get_theme_mod(
                                    'vy_footer_about',
                                    'Nơi ghi lại những chuyến đi, cảm xúc và ký ức đẹp từ khắp mọi miền đất nước.'
                                )); ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Col 2: Newsletter -->
                <div class="vy-footer__col">
                    <?php if (is_active_sidebar('footer-col-2')): ?>
                        <?php dynamic_sidebar('footer-col-2'); ?>
                    <?php else: ?>
                        <div class="vy-footer-widget vy-footer-newsletter">
                            <h6 class="vy-footer-widget__title"><?php esc_html_e('Nhận bài viết mới', 'voya'); ?></h6>
                            <p class="vy-footer-widget__sub">
                                <?php esc_html_e('Đăng ký để không bỏ lỡ những hành trình mới nhất.', 'voya'); ?>
                            </p>
                            <form class="vy-footer-newsletter__form" method="post"
                                action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                <input type="hidden" name="action" value="wl_newsletter_subscribe">
                                <?php wp_nonce_field('wl_newsletter_nonce', 'wl_newsletter_nonce_field'); ?>
                                <div class="vy-footer-newsletter__fields">
                                    <div class="vy-footer-newsletter__field">
                                        <input type="text" name="your-name" class="vy-footer-newsletter__input"
                                            placeholder="<?php esc_attr_e('Tên của bạn', 'voya'); ?>" required>
                                    </div>
                                    <div class="vy-footer-newsletter__field">
                                        <input type="email" name="your-email" class="vy-footer-newsletter__input"
                                            placeholder="<?php esc_attr_e('Địa chỉ email', 'voya'); ?>" required>
                                    </div>
                                </div>
                                <button type="submit" class="vy-footer-newsletter__btn">
                                    <span><?php esc_html_e('Đăng ký', 'voya'); ?></span>
                                    <i class="fa-solid fa-arrow-up-right" aria-hidden="true"></i>
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Col 3: Recent Posts -->
                <div class="vy-footer__col">
                    <?php if (is_active_sidebar('footer-col-3')): ?>
                        <?php dynamic_sidebar('footer-col-3'); ?>
                    <?php else: ?>
                        <div class="vy-footer-widget">
                            <h6 class="vy-footer-widget__title"><?php esc_html_e('Bài viết mới nhất', 'voya'); ?></h6>
                            <div class="vy-footer-posts">
                                <?php
                                $recent = new WP_Query([
                                    'posts_per_page' => 3,
                                    'orderby' => 'date',
                                    'order' => 'DESC',
                                    'no_found_rows' => true,
                                ]);
                                while ($recent->have_posts()):
                                    $recent->the_post(); ?>
                                    <article class="vy-footer-post">
                                        <time class="vy-footer-post__date"
                                            datetime="<?php echo esc_attr(get_the_date('Y-m-d')); ?>">
                                            <i class="fa-regular fa-calendar" aria-hidden="true"></i>
                                            <a
                                                href="<?php echo esc_url(get_month_link(get_the_time('Y'), get_the_time('m'))); ?>">
                                                <?php echo esc_html(get_the_date()); ?>
                                            </a>
                                        </time>
                                        <h6 class="vy-footer-post__title">
                                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                        </h6>
                                    </article>
                                <?php endwhile;
                                wp_reset_postdata(); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Col 4: Categories -->
                <div class="vy-footer__col">
                    <?php if (is_active_sidebar('footer-col-4')): ?>
                        <?php dynamic_sidebar('footer-col-4'); ?>
                    <?php else: ?>
                        <div class="vy-footer-widget">
                            <h6 class="vy-footer-widget__title"><?php esc_html_e('Danh mục', 'voya'); ?></h6>
                            <ul class="vy-footer-cats">
                                <?php wp_list_categories([
                                    'title_li' => '',
                                    'hide_empty' => true,
                                    'orderby' => 'name',
                                    'order' => 'ASC',
                                ]); ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div><!-- .vy-footer__middle -->


    <!-- ============================================================
         FOOTER BOTTOM: Social + Copyright
         ============================================================ -->
    <div class="vy-footer__bottom">
        <div class="vy-container--wide">
            <div class="vy-footer__bottom-inner">

                <!-- Social icons -->
                <?php $social_html = vy_social_links('vy-footer__social-link');
                if ($social_html): ?>
                    <div class="vy-footer__socials" aria-label="<?php esc_attr_e('Mạng xã hội', 'voya'); ?>">
                        <span class="vy-footer__socials-label"><?php esc_html_e('Theo dõi', 'voya'); ?></span>
                        <?php echo $social_html; ?>
                    </div>
                <?php endif; ?>

                <!-- Copyright -->
                <div class="vy-footer__copyright">
                    <?php echo wp_kses_post(get_theme_mod(
                        'vy_copyright',
                        '&copy; ' . gmdate('Y') . ' <a href="' . esc_url(home_url('/')) . '">' . get_bloginfo('name') . '</a>. All rights reserved.'
                    )); ?>
                </div>

            </div>
        </div>
    </div><!-- .vy-footer__bottom -->

</footer><!-- .vy-footer -->


<!-- ============================================================
     BACK TO TOP BUTTON
     ============================================================ -->
<a id="vy-back-to-top" href="#" aria-label="<?php esc_attr_e('Lên đầu trang', 'voya'); ?>">
    <span class="vy-btt__stack" aria-hidden="true">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 22 22" class="vy-btt__arrow vy-btt__arrow--primary">
            <line x1="11" y1="20" x2="11" y2="3" />
            <line x1="11" y1="3" x2="2" y2="12" />
            <line x1="11" y1="3" x2="20" y2="12" />
        </svg>
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 22 22" class="vy-btt__arrow vy-btt__arrow--hover">
            <line x1="11" y1="20" x2="11" y2="3" />
            <line x1="11" y1="3" x2="2" y2="12" />
            <line x1="11" y1="3" x2="20" y2="12" />
        </svg>
    </span>
</a>

<?php wp_footer(); ?>
</body>

</html>