<?php
/**
 * VOYA — 404.php
 * Full-screen 404: header floats above, no footer, no scroll
 *
 * Redesigned từ Wanderland:
 *  - Bỏ --topbar-height reference
 *  - Ionicons → FA6
 *  - Prefix wl → vy
 *  - data-hero → header.js tự detect transparent
 *  - Không gọi get_footer(), chỉ wp_footer() + đóng tag
 */

get_header();
?>

<main id="vy-404-main" class="vy-404-main" role="main" data-hero>

    <div class="vy-404-scene">

        <!-- Background image (CSS) -->
        <div class="vy-404-bg" aria-hidden="true"></div>

        <!-- Overlay -->
        <div class="vy-404-overlay" aria-hidden="true"></div>

        <!-- Parallax particles -->
        <div class="vy-404-particles" aria-hidden="true">
            <span class="vy-404-particle"></span>
            <span class="vy-404-particle"></span>
            <span class="vy-404-particle"></span>
            <span class="vy-404-particle"></span>
            <span class="vy-404-particle"></span>
        </div>

        <!-- Content -->
        <div class="vy-404-content">

            <!-- 404 number + compass -->
            <div class="vy-404-number" aria-label="404">
                <span class="vy-404-digit" aria-hidden="true">4</span>

                <!-- Compass — replacing the 0 -->
                <span class="vy-404-compass" aria-hidden="true">
                    <svg viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <!-- Outer rings -->
                        <circle cx="60" cy="60" r="54" stroke="currentColor" stroke-width="1.2" opacity="0.25" />
                        <circle cx="60" cy="60" r="44" stroke="currentColor" stroke-width="0.8" opacity="0.12"
                            stroke-dasharray="3 6" />
                        <!-- Cardinal ticks -->
                        <line x1="60" y1="6" x2="60" y2="20" stroke="currentColor" stroke-width="2.5"
                            stroke-linecap="round" />
                        <line x1="60" y1="100" x2="60" y2="114" stroke="currentColor" stroke-width="2.5"
                            stroke-linecap="round" />
                        <line x1="6" y1="60" x2="20" y2="60" stroke="currentColor" stroke-width="2.5"
                            stroke-linecap="round" />
                        <line x1="100" y1="60" x2="114" y2="60" stroke="currentColor" stroke-width="2.5"
                            stroke-linecap="round" />
                        <!-- Diagonal minor ticks -->
                        <line x1="22" y1="22" x2="30" y2="30" stroke="currentColor" stroke-width="1"
                            stroke-linecap="round" opacity="0.3" />
                        <line x1="98" y1="22" x2="90" y2="30" stroke="currentColor" stroke-width="1"
                            stroke-linecap="round" opacity="0.3" />
                        <line x1="22" y1="98" x2="30" y2="90" stroke="currentColor" stroke-width="1"
                            stroke-linecap="round" opacity="0.3" />
                        <line x1="98" y1="98" x2="90" y2="90" stroke="currentColor" stroke-width="1"
                            stroke-linecap="round" opacity="0.3" />
                        <!-- Cardinal labels -->
                        <text x="60" y="31" text-anchor="middle" font-size="10"
                            font-family="Plus Jakarta Sans,sans-serif" font-weight="700" fill="currentColor"
                            letter-spacing="1">N</text>
                        <text x="60" y="98" text-anchor="middle" font-size="10"
                            font-family="Plus Jakarta Sans,sans-serif" font-weight="700" fill="currentColor"
                            opacity="0.4">S</text>
                        <text x="97" y="64" text-anchor="middle" font-size="10"
                            font-family="Plus Jakarta Sans,sans-serif" font-weight="700" fill="currentColor"
                            opacity="0.4">E</text>
                        <text x="23" y="64" text-anchor="middle" font-size="10"
                            font-family="Plus Jakarta Sans,sans-serif" font-weight="700" fill="currentColor"
                            opacity="0.4">W</text>
                        <!-- Needle north — accent gold -->
                        <polygon class="vy-404-needle-n" points="60,20 56,60 60,65 64,60"
                            fill="var(--color-accent,#b89d6e)" />
                        <!-- Needle south — faded white -->
                        <polygon class="vy-404-needle-s" points="60,100 56,60 60,65 64,60"
                            fill="rgba(255,255,255,0.22)" />
                        <!-- Center pin -->
                        <circle cx="60" cy="62" r="5.5" fill="white" opacity="0.9" />
                        <circle cx="60" cy="62" r="2.5" fill="var(--color-primary,#2c2c2c)" />
                    </svg>
                </span>

                <span class="vy-404-digit" aria-hidden="true">4</span>
            </div>

            <!-- Text -->
            <span class="vy-404-tagline">
                <?php esc_html_e('Ồ! Bạn đang đi lạc trên bản đồ.', 'voya'); ?>
            </span>

            <h1 class="vy-404-title">
                <?php esc_html_e('Không tìm thấy trang', 'voya'); ?>
            </h1>

            <!-- Search -->
            <form class="vy-404-search" action="<?php echo esc_url(home_url('/')); ?>" method="get" role="search">
                <div class="vy-404-search__inner">
                    <label for="vy-404-search-input" class="sr-only">
                        <?php esc_html_e('Tìm kiếm', 'voya'); ?>
                    </label>
                    <input type="search" id="vy-404-search-input" name="s" class="vy-404-search__input"
                        placeholder="<?php esc_attr_e('Tìm kiếm điều bạn cần…', 'voya'); ?>" autocomplete="off"
                        aria-label="<?php esc_attr_e('Tìm kiếm', 'voya'); ?>">
                    <button type="submit" class="vy-404-search__btn"
                        aria-label="<?php esc_attr_e('Tìm kiếm', 'voya'); ?>">
                        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    </button>
                </div>
            </form>

            <!-- Action buttons -->
            <div class="vy-404-btns">
                <a class="vy-404-btn vy-404-btn--primary" href="<?php echo esc_url(home_url('/')); ?>">
                    <i class="fa-solid fa-house" aria-hidden="true"></i>
                    <?php esc_html_e('Về trang chủ', 'voya'); ?>
                </a>

                <?php
                $blog_page = get_option('page_for_posts');
                $blog_url = $blog_page ? get_permalink($blog_page) : home_url('/blog/');
                ?>
                <a class="vy-404-btn vy-404-btn--ghost" href="<?php echo esc_url($blog_url); ?>">
                    <i class="fa-regular fa-newspaper" aria-hidden="true"></i>
                    <?php esc_html_e('Xem tất cả bài viết', 'voya'); ?>
                </a>
            </div>

        </div><!-- .vy-404-content -->

    </div><!-- .vy-404-scene -->

</main>

<?php
// Không gọi get_footer() — 404 full-screen, chỉ cần wp_footer()
wp_footer();
?>
</body>

</html>