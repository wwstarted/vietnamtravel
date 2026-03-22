<?php
/**
 * VOYA — single.php
 * Single post: Hero image + Header + Content/Sidebar + Related
 *
 * Redesigned từ Wanderland:
 *  - Bỏ blog-hero banner + brush PNG → dùng featured image làm hero
 *  - Fix wp_remote_head() block → dùng get_avatar() trực tiếp
 *  - Fix share URLs chưa esc_url()
 *  - Author social: ẩn link khi không có URL (không dùng href="#")
 *  - Prefix wl- → vy-  |  Ionicons → FA6
 */

get_header();

while ( have_posts() ) :
    the_post();

    // ── Prepare data ─────────────────────────────────────────────
    $post_id        = get_the_ID();
    $thumb_id       = get_post_thumbnail_id( $post_id );
    $hero_url       = $thumb_id
        ? wp_get_attachment_image_url( $thumb_id, 'wl-hero' )
            ?: wp_get_attachment_image_url( $thumb_id, 'full' )
        : '';
    $featured_url   = $thumb_id
        ? wp_get_attachment_image_url( $thumb_id, 'wl-wide' )
            ?: wp_get_attachment_image_url( $thumb_id, 'full' )
        : '';

    $categories     = get_the_category();
    $cat            = $categories[0] ?? null;
    $tags           = get_the_tags();

    $author_id      = get_the_author_meta( 'ID' );
    $author_name    = get_the_author();
    $author_bio     = get_the_author_meta( 'description' ) ?: __( 'Một người yêu du lịch và kể chuyện, chia sẻ hành trình qua từng điểm đến.', 'voya' );
    $author_url     = get_author_posts_url( $author_id );
    // FIX: Bỏ wp_remote_head() — dùng get_avatar() built-in, WP tự handle fallback
    $author_avatar  = get_avatar_url( $author_id, [ 'size' => 120 ] );

    $author_facebook  = get_the_author_meta( 'facebook',  $author_id );
    $author_twitter   = get_the_author_meta( 'twitter',   $author_id );
    $author_instagram = get_the_author_meta( 'instagram', $author_id );

    // FIX: esc_url() đúng chỗ — không dùng trực tiếp trong href
    $post_url_raw   = get_permalink();
    $post_url_enc   = rawurlencode( $post_url_raw );
    $post_title_enc = rawurlencode( get_the_title() );

    $prev_post      = get_previous_post();
    $next_post      = get_next_post();

    $blog_page      = get_option( 'page_for_posts' );
    $blog_url       = $blog_page ? get_permalink( $blog_page ) : home_url( '/blog/' );

    $reading_time   = max( 1, (int) ceil( str_word_count( wp_strip_all_tags( get_the_content() ) ) / 200 ) );
?>

<main id="vy-single-main" class="vy-single-main" data-hero>

    <!-- ============================================================
         HERO — Featured image full-viewport (bỏ blog-banner tĩnh)
         Transparent header float trên (data-hero → header.js)
    ============================================================ -->
    <div class="vy-single-hero">

        <?php if ( $hero_url ) : ?>
        <div class="vy-single-hero__img" style="background-image:url('<?php echo esc_url( $hero_url ); ?>');" role="img"
            aria-label="<?php the_title_attribute(); ?>">
        </div>
        <?php else : ?>
        <div class="vy-single-hero__img vy-single-hero__img--empty"></div>
        <?php endif; ?>

        <div class="vy-single-hero__overlay" aria-hidden="true"></div>

        <!-- Post info overlay — bottom left -->
        <div class="vy-single-hero__content-wrap">
            <div class="vy-container">
                <div class="vy-single-hero__content">

                    <!-- Breadcrumb -->
                    <nav class="vy-single-breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'voya' ); ?>">
                        <a
                            href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Trang chủ', 'voya' ); ?></a>
                        <span aria-hidden="true">/</span>
                        <a href="<?php echo esc_url( $blog_url ); ?>"><?php esc_html_e( 'Blog', 'voya' ); ?></a>
                        <?php if ( $cat ) : ?>
                        <span aria-hidden="true">/</span>
                        <a href="<?php echo esc_url( get_category_link( $cat->term_id ) ); ?>">
                            <?php echo esc_html( $cat->name ); ?>
                        </a>
                        <?php endif; ?>
                    </nav>

                    <!-- Category tag -->
                    <?php if ( $cat ) : ?>
                    <a href="<?php echo esc_url( get_category_link( $cat->term_id ) ); ?>" class="vy-single-hero__cat">
                        <?php echo esc_html( $cat->name ); ?>
                    </a>
                    <?php endif; ?>

                    <!-- Title -->
                    <h1 class="vy-single-hero__title"><?php the_title(); ?></h1>

                    <!-- Meta: author · date · reading time -->
                    <div class="vy-single-hero__meta">
                        <img src="<?php echo esc_url( $author_avatar ); ?>"
                            alt="<?php echo esc_attr( $author_name ); ?>" class="vy-single-hero__avatar" width="32"
                            height="32" loading="eager">
                        <a href="<?php echo esc_url( $author_url ); ?>" class="vy-single-hero__author">
                            <?php echo esc_html( $author_name ); ?>
                        </a>
                        <span class="vy-single-hero__dot" aria-hidden="true"></span>
                        <time datetime="<?php echo esc_attr( get_the_date( 'Y-m-d' ) ); ?>">
                            <?php echo esc_html( get_the_date( 'j M, Y' ) ); ?>
                        </time>
                        <span class="vy-single-hero__dot" aria-hidden="true"></span>
                        <span class="vy-single-hero__read-time">
                            <?php printf( esc_html__( '%d phút đọc', 'voya' ), $reading_time ); ?>
                        </span>
                    </div>

                </div>
            </div>
        </div>

        <!-- Scroll hint -->
        <div class="vy-single-hero__scroll" aria-hidden="true">
            <span class="vy-single-hero__scroll-line"></span>
        </div>

    </div><!-- .vy-single-hero -->


    <!-- ============================================================
         POST BODY: Content + Sidebar
    ============================================================ -->
    <div class="vy-single-body">
        <div class="vy-container">
            <div class="vy-single-layout">

                <!-- ARTICLE ─────────────────────────────────────── -->
                <article class="vy-single-article" id="vy-article" itemscope itemtype="https://schema.org/BlogPosting">

                    <meta itemprop="headline" content="<?php the_title_attribute(); ?>">
                    <meta itemprop="datePublished" content="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
                    <meta itemprop="author" content="<?php echo esc_attr( $author_name ); ?>">
                    <?php if ( $hero_url ) : ?>
                    <meta itemprop="image" content="<?php echo esc_url( $hero_url ); ?>">
                    <?php endif; ?>

                    <!-- TOC placeholder — JS inject sau p đầu tiên -->
                    <aside class="vy-toc" id="vy-toc" aria-label="<?php esc_attr_e( 'Mục lục', 'voya' ); ?>"
                        style="display:none;" hidden>
                        <div class="vy-toc__head" role="button" tabindex="0" aria-expanded="true"
                            aria-controls="vy-toc-list">
                            <span class="vy-toc__title">
                                <i class="fa-solid fa-list-ul" aria-hidden="true"></i>
                                <?php esc_html_e( 'Mục lục', 'voya' ); ?>
                            </span>
                            <button class="vy-toc__toggle" type="button"
                                aria-label="<?php esc_attr_e( 'Thu gọn mục lục', 'voya' ); ?>">
                                <i class="fa-solid fa-chevron-up vy-toc__arrow" aria-hidden="true"></i>
                            </button>
                        </div>
                        <nav class="vy-toc__body" id="vy-toc-list">
                            <ol class="vy-toc__list"></ol>
                        </nav>
                    </aside>

                    <!-- Post content -->
                    <div class="vy-single-entry" itemprop="articleBody">
                        <?php the_content(); ?>
                    </div>

                    <!-- Pagination (multi-page posts) -->
                    <?php
                    wp_link_pages( [
                        'before'      => '<div class="vy-single-pages"><span class="vy-single-pages__label">' . esc_html__( 'Trang:', 'voya' ) . '</span>',
                        'after'       => '</div>',
                        'link_before' => '<span>',
                        'link_after'  => '</span>',
                    ] );
                    ?>

                    <!-- ── Footer: Tags + Share ── -->
                    <footer class="vy-single-footer">

                        <!-- Tags -->
                        <?php if ( $tags ) : ?>
                        <div class="vy-single-tags" aria-label="<?php esc_attr_e( 'Tags', 'voya' ); ?>">
                            <?php foreach ( $tags as $tag ) : ?>
                            <a href="<?php echo esc_url( get_tag_link( $tag->term_id ) ); ?>" class="vy-tag">
                                <?php echo esc_html( $tag->name ); ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <!-- Share -->
                        <div class="vy-single-share" role="group"
                            aria-label="<?php esc_attr_e( 'Chia sẻ bài viết', 'voya' ); ?>">
                            <span class="vy-single-share__label"><?php esc_html_e( 'Chia sẻ', 'voya' ); ?></span>
                            <div class="vy-single-share__links">
                                <a href="https://www.facebook.com/sharer.php?u=<?php echo $post_url_enc; ?>"
                                    class="vy-share-btn" target="_blank" rel="noopener noreferrer"
                                    aria-label="<?php esc_attr_e( 'Chia sẻ lên Facebook', 'voya' ); ?>">
                                    <i class="fa-brands fa-facebook-f" aria-hidden="true"></i>
                                </a>
                                <a href="https://twitter.com/intent/tweet?url=<?php echo $post_url_enc; ?>&text=<?php echo $post_title_enc; ?>"
                                    class="vy-share-btn" target="_blank" rel="noopener noreferrer"
                                    aria-label="<?php esc_attr_e( 'Chia sẻ lên Twitter', 'voya' ); ?>">
                                    <i class="fa-brands fa-x-twitter" aria-hidden="true"></i>
                                </a>
                                <a href="https://pinterest.com/pin/create/button/?url=<?php echo $post_url_enc; ?>"
                                    class="vy-share-btn" target="_blank" rel="noopener noreferrer"
                                    aria-label="<?php esc_attr_e( 'Chia sẻ lên Pinterest', 'voya' ); ?>">
                                    <i class="fa-brands fa-pinterest-p" aria-hidden="true"></i>
                                </a>
                                <a href="https://t.me/share/url?url=<?php echo $post_url_enc; ?>" class="vy-share-btn"
                                    target="_blank" rel="noopener noreferrer"
                                    aria-label="<?php esc_attr_e( 'Chia sẻ lên Telegram', 'voya' ); ?>">
                                    <i class="fa-brands fa-telegram" aria-hidden="true"></i>
                                </a>
                            </div>
                        </div>

                    </footer><!-- .vy-single-footer -->


                    <!-- ── Prev / Next Navigation ── -->
                    <?php if ( $prev_post || $next_post ) : ?>
                    <nav class="vy-single-nav" aria-label="<?php esc_attr_e( 'Điều hướng bài viết', 'voya' ); ?>">
                        <div class="vy-single-nav__inner">

                            <?php if ( $prev_post ) :
                                    $prev_thumb = get_the_post_thumbnail_url( $prev_post->ID, [ 76, 50 ] );
                                ?>
                            <a class="vy-nav-item vy-nav-item--prev"
                                href="<?php echo esc_url( get_permalink( $prev_post->ID ) ); ?>" rel="prev">
                                <i class="fa-solid fa-arrow-left vy-nav-item__arrow" aria-hidden="true"></i>
                                <?php if ( $prev_thumb ) : ?>
                                <div class="vy-nav-item__thumb">
                                    <img src="<?php echo esc_url( $prev_thumb ); ?>"
                                        alt="<?php echo esc_attr( $prev_post->post_title ); ?>" width="76" height="50"
                                        loading="lazy">
                                </div>
                                <?php endif; ?>
                                <div class="vy-nav-item__text">
                                    <span class="vy-nav-item__label"><?php esc_html_e( 'Bài trước', 'voya' ); ?></span>
                                    <span
                                        class="vy-nav-item__title"><?php echo esc_html( wp_trim_words( $prev_post->post_title, 8, '…' ) ); ?></span>
                                </div>
                            </a>
                            <?php else : ?>
                            <div class="vy-nav-item vy-nav-item--empty"></div>
                            <?php endif; ?>

                            <span class="vy-single-nav__divider" aria-hidden="true"></span>

                            <?php if ( $next_post ) :
                                    $next_thumb = get_the_post_thumbnail_url( $next_post->ID, [ 76, 50 ] );
                                ?>
                            <a class="vy-nav-item vy-nav-item--next"
                                href="<?php echo esc_url( get_permalink( $next_post->ID ) ); ?>" rel="next">
                                <div class="vy-nav-item__text">
                                    <span
                                        class="vy-nav-item__label"><?php esc_html_e( 'Bài tiếp theo', 'voya' ); ?></span>
                                    <span
                                        class="vy-nav-item__title"><?php echo esc_html( wp_trim_words( $next_post->post_title, 8, '…' ) ); ?></span>
                                </div>
                                <?php if ( $next_thumb ) : ?>
                                <div class="vy-nav-item__thumb">
                                    <img src="<?php echo esc_url( $next_thumb ); ?>"
                                        alt="<?php echo esc_attr( $next_post->post_title ); ?>" width="76" height="50"
                                        loading="lazy">
                                </div>
                                <?php endif; ?>
                                <i class="fa-solid fa-arrow-right vy-nav-item__arrow" aria-hidden="true"></i>
                            </a>
                            <?php else : ?>
                            <div class="vy-nav-item vy-nav-item--empty"></div>
                            <?php endif; ?>

                        </div>
                    </nav>
                    <?php endif; ?>


                    <!-- ── Author Box ── -->
                    <div class="vy-author-box">
                        <div class="vy-author-box__avatar">
                            <a href="<?php echo esc_url( $author_url ); ?>">
                                <img src="<?php echo esc_url( $author_avatar ); ?>"
                                    alt="<?php echo esc_attr( $author_name ); ?>" width="100" height="100"
                                    loading="lazy">
                            </a>
                        </div>

                        <div class="vy-author-box__body">

                            <div class="vy-author-box__top">
                                <h5 class="vy-author-box__name">
                                    <a href="<?php echo esc_url( $author_url ); ?>">
                                        <?php echo esc_html( $author_name ); ?>
                                    </a>
                                </h5>
                                <!-- FIX: chỉ hiện social link khi có URL thực -->
                                <div class="vy-author-box__social">
                                    <?php if ( $author_facebook ) : ?>
                                    <a href="<?php echo esc_url( $author_facebook ); ?>" target="_blank"
                                        rel="noopener noreferrer" aria-label="Facebook">
                                        <i class="fa-brands fa-facebook-f" aria-hidden="true"></i>
                                    </a>
                                    <?php endif; ?>
                                    <?php if ( $author_twitter ) : ?>
                                    <a href="<?php echo esc_url( $author_twitter ); ?>" target="_blank"
                                        rel="noopener noreferrer" aria-label="Twitter/X">
                                        <i class="fa-brands fa-x-twitter" aria-hidden="true"></i>
                                    </a>
                                    <?php endif; ?>
                                    <?php if ( $author_instagram ) : ?>
                                    <a href="<?php echo esc_url( $author_instagram ); ?>" target="_blank"
                                        rel="noopener noreferrer" aria-label="Instagram">
                                        <i class="fa-brands fa-instagram" aria-hidden="true"></i>
                                    </a>
                                    <?php endif; ?>
                                    <?php
                                    // Fallback: nếu không có social nào → hiện link archive
                                    if ( ! $author_facebook && ! $author_twitter && ! $author_instagram ) : ?>
                                    <a href="<?php echo esc_url( $author_url ); ?>" class="vy-author-box__all-posts">
                                        <?php esc_html_e( 'Xem tất cả bài viết', 'voya' ); ?>
                                        <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <p class="vy-author-box__bio"><?php echo esc_html( $author_bio ); ?></p>

                        </div>
                    </div><!-- .vy-author-box -->


                    <!-- ── Comments ── -->
                    <?php if ( comments_open() || get_comments_number() ) : ?>
                    <div class="vy-comments-wrap">
                        <?php comments_template(); ?>
                    </div>
                    <?php endif; ?>

                </article><!-- .vy-single-article -->


                <!-- SIDEBAR ──────────────────────────────────────── -->
                <aside class="vy-single-sidebar" id="vy-sidebar" role="complementary"
                    aria-label="<?php esc_attr_e( 'Sidebar', 'voya' ); ?>">

                    <?php if ( is_active_sidebar( 'single-sidebar' ) ) : ?>
                    <?php dynamic_sidebar( 'single-sidebar' ); ?>
                    <?php endif; ?>

                    <!-- Recent Posts -->
                    <div class="vy-sidebar-widget vy-sidebar-recent">
                        <h5 class="vy-widget-title"><?php esc_html_e( 'Bài viết gần đây', 'voya' ); ?></h5>
                        <?php
                        $recent_q = new WP_Query( [
                            'posts_per_page'      => 5,
                            'post__not_in'        => [ $post_id ],
                            'post_status'         => 'publish',
                            'orderby'             => 'date',
                            'order'               => 'DESC',
                            'no_found_rows'       => true,
                            'ignore_sticky_posts' => true,
                        ] );
                        if ( $recent_q->have_posts() ) : ?>
                        <ul class="vy-recent-list">
                            <?php while ( $recent_q->have_posts() ) :
                                    $recent_q->the_post();
                                    $rec_img = get_the_post_thumbnail_url( get_the_ID(), 'wl-thumb' )
                                        ?: get_the_post_thumbnail_url( get_the_ID(), 'full' );
                                ?>
                            <li class="vy-recent-item">
                                <a class="vy-recent-link" href="<?php the_permalink(); ?>">
                                    <div class="vy-recent-thumb">
                                        <?php if ( $rec_img ) : ?>
                                        <img src="<?php echo esc_url( $rec_img ); ?>"
                                            alt="<?php the_title_attribute(); ?>" loading="lazy">
                                        <?php else : ?>
                                        <div class="vy-recent-thumb__placeholder"></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="vy-recent-text">
                                        <span class="vy-recent-title"><?php the_title(); ?></span>
                                        <time class="vy-recent-date"
                                            datetime="<?php echo esc_attr( get_the_date( 'Y-m-d' ) ); ?>">
                                            <?php echo esc_html( get_the_date( 'M j, Y' ) ); ?>
                                        </time>
                                    </div>
                                </a>
                            </li>
                            <?php endwhile;
                                wp_reset_postdata(); ?>
                        </ul>
                        <?php endif; ?>
                    </div><!-- .vy-sidebar-recent -->

                    <!-- Categories -->
                    <div class="vy-sidebar-widget">
                        <h5 class="vy-widget-title"><?php esc_html_e( 'Danh mục', 'voya' ); ?></h5>
                        <ul class="vy-sidebar-cats">
                            <?php wp_list_categories( [
                                'show_count' => true,
                                'title_li'   => '',
                                'orderby'    => 'count',
                                'order'      => 'DESC',
                                'number'     => 10,
                                'hide_empty' => true,
                            ] ); ?>
                        </ul>
                    </div>

                    <!-- Tags -->
                    <div class="vy-sidebar-widget">
                        <h5 class="vy-widget-title"><?php esc_html_e( 'Tags', 'voya' ); ?></h5>
                        <div class="vy-sidebar-tagcloud">
                            <?php wp_tag_cloud( [
                                'smallest' => 11,
                                'largest'  => 11,
                                'unit'     => 'px',
                                'format'   => 'flat',
                                'number'   => 20,
                            ] ); ?>
                        </div>
                    </div>

                </aside><!-- .vy-single-sidebar -->

            </div><!-- .vy-single-layout -->
        </div><!-- .vy-container -->
    </div><!-- .vy-single-body -->


    <!-- ============================================================
         RELATED POSTS — 3 bài cùng category
    ============================================================ -->
    <?php
    $related_args = $cat
        ? [ 'category__in' => [ $cat->term_id ], 'post__not_in' => [ $post_id ], 'posts_per_page' => 3, 'orderby' => 'rand', 'post_status' => 'publish', 'no_found_rows' => true ]
        : [ 'post__not_in' => [ $post_id ], 'posts_per_page' => 3, 'orderby' => 'date', 'post_status' => 'publish', 'no_found_rows' => true ];
    $related_q = new WP_Query( $related_args );
    if ( $related_q->have_posts() ) :
    ?>
    <section class="vy-related" aria-label="<?php esc_attr_e( 'Bài viết liên quan', 'voya' ); ?>">
        <div class="vy-container">

            <header class="vy-related__header">
                <div class="vy-related__header-left">
                    <span class="vy-label"><?php esc_html_e( 'Có thể bạn thích', 'voya' ); ?></span>
                    <h2 class="vy-section-title">
                        <?php esc_html_e( 'Bài viết ', 'voya' ); ?>
                        <em><?php esc_html_e( 'liên quan', 'voya' ); ?></em>
                    </h2>
                </div>
            </header>

            <div class="vy-related__grid">
                <?php while ( $related_q->have_posts() ) :
                    $related_q->the_post();
                    $rel_img  = get_the_post_thumbnail_url( get_the_ID(), 'wl-card' )
                        ?: get_the_post_thumbnail_url( get_the_ID(), 'full' );
                    $rel_cats = get_the_category();
                    $rel_cat  = $rel_cats[0] ?? null;
                    $rel_exc  = get_the_excerpt() ?: wp_trim_words( get_the_content(), 18, '…' );
                ?>

                <article class="vy-related-card" itemscope itemtype="https://schema.org/BlogPosting">

                    <a class="vy-related-card__img-link" href="<?php the_permalink(); ?>">
                        <?php if ( $rel_img ) : ?>
                        <img src="<?php echo esc_url( $rel_img ); ?>" alt="<?php the_title_attribute(); ?>"
                            loading="lazy" itemprop="image" class="vy-related-card__img">
                        <?php else : ?>
                        <div class="vy-related-card__img-placeholder"></div>
                        <?php endif; ?>
                        <?php if ( $rel_cat ) : ?>
                        <span class="vy-related-card__cat">
                            <?php echo esc_html( $rel_cat->name ); ?>
                        </span>
                        <?php endif; ?>
                    </a>

                    <div class="vy-related-card__body">
                        <div class="vy-related-card__meta">
                            <a href="<?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); ?>"
                                class="vy-related-card__author">
                                <?php echo esc_html( get_the_author() ); ?>
                            </a>
                            <span aria-hidden="true">·</span>
                            <time datetime="<?php echo esc_attr( get_the_date( 'Y-m-d' ) ); ?>"
                                itemprop="datePublished">
                                <?php echo esc_html( get_the_date( 'j M, Y' ) ); ?>
                            </time>
                        </div>

                        <h3 class="vy-related-card__title" itemprop="headline">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h3>

                        <p class="vy-related-card__excerpt">
                            <?php echo esc_html( wp_trim_words( $rel_exc, 18, '…' ) ); ?>
                        </p>

                        <a href="<?php the_permalink(); ?>" class="vy-related-card__more">
                            <?php esc_html_e( 'Đọc thêm', 'voya' ); ?>
                            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </a>
                    </div>

                </article>

                <?php endwhile;
                wp_reset_postdata(); ?>
            </div><!-- .vy-related__grid -->

        </div>
    </section>
    <?php endif; ?>

</main>

<?php endwhile; ?>
<?php get_footer(); ?>