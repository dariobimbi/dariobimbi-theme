<?php
/**
 * Title: Hero
 * Slug: dariobimbi/hero
 * Categories: dariobimbi, featured
 * Description: Asymmetric "title screen" hero — an oversized monospace headline with a hand-drawn squiggle underline on the left, a tilted photo card on a Sticky-Yellow sticker backing on the right, and a dotted Ant Buster trail along the bottom, over a warm mesh + notebook-grid texture. System fonts only; the banner stays the eager LCP image.
 */
$assets         = get_stylesheet_directory_uri() . '/assets';
$banner_webp_sm = esc_url( $assets . '/banner-hero-800.webp' );
$banner_webp_lg = esc_url( $assets . '/banner-hero.webp' );
$banner_jpg_sm  = esc_url( $assets . '/banner-hero-800.jpg' );
$banner_jpg_lg  = esc_url( $assets . '/banner-hero.jpg' );

// Only link to pages that are actually published, so a draft never ships a 404.
$ant_page  = get_page_by_path( 'demos/ant-buster' );
$self_page = get_page_by_path( 'projects/dariobimbi-com' );
$ant_url   = ( $ant_page  && 'publish' === get_post_status( $ant_page ) )  ? get_permalink( $ant_page )  : '';
$self_url  = ( $self_page && 'publish' === get_post_status( $self_page ) ) ? get_permalink( $self_page ) : '';
?>
<!-- wp:group {"align":"full","className":"section hero-stage","style":{"spacing":{"padding":{"top":"0","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull section hero-stage" style="padding-top:0;padding-bottom:var(--wp--preset--spacing--60)">

    <!-- wp:html -->
    <div class="hero-grid-texture" aria-hidden="true" role="presentation"></div>
    <div class="hero-mesh" aria-hidden="true" role="presentation"></div>
    <!-- /wp:html -->

    <!-- wp:html -->
    <p class="eyebrow hero-eyebrow hero-rise" style="--rise-delay:0ms">what i do</p>

    <div class="hero-layout">

        <div class="hero-copy">
            <p class="status-pill hero-rise" style="--rise-delay:90ms">Currently at <a href="https://clickswebdesign.com">Clicks Web Design</a></p>
            <h1 class="hero-headline hero-rise" style="--rise-delay:180ms">I build websites I&rsquo;d want to use <span class="hero-underline">myself<svg class="hero-squiggle" viewBox="0 0 300 16" preserveAspectRatio="none" aria-hidden="true" focusable="false"><path d="M3 11 Q 40 3, 78 9 T 150 9 T 222 9 T 297 8" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" vector-effect="non-scaling-stroke"/></svg></span>.</h1>
            <p class="hero-lead hero-rise" style="--rise-delay:300ms">I&rsquo;m <strong>Dario Bimbi</strong>, a web developer at Clicks Web Design who came up through game design. My focus is custom WordPress themes, plugins, and web apps, and after about ten years of doing this work I have a pretty clear idea of what makes a site good and what makes a site just look good.</p>

            <div class="hero-ctas hero-rise" style="--rise-delay:420ms">
                <?php if ( $ant_url ) : ?>
                    <div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( $ant_url ); ?>">Play Ant Buster</a></div>
                <?php endif; ?>
                <?php if ( $self_url ) : ?>
                    <div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( $self_url ); ?>">See how this site is built</a></div>
                <?php endif; ?>
                <a class="hero-contact-link" href="#contact">Or just say hello</a>
            </div>
        </div>

        <div class="hero-figure">
            <span class="hero-figure-backing" aria-hidden="true"></span>
            <figure class="hero-photo">
                <picture>
                    <source type="image/webp" srcset="<?php echo $banner_webp_sm; ?> 800w, <?php echo $banner_webp_lg; ?> 1600w" sizes="(max-width: 860px) 92vw, 46vw">
                    <img src="<?php echo $banner_jpg_lg; ?>" srcset="<?php echo $banner_jpg_sm; ?> 800w, <?php echo $banner_jpg_lg; ?> 1600w" sizes="(max-width: 860px) 92vw, 46vw" width="1600" height="1067" alt="Dario Bimbi, web developer, illustrated at his desk with his dog." fetchpriority="high" loading="eager" decoding="async">
                </picture>
            </figure>
        </div>

    </div>

    <div class="hero-trail" aria-hidden="true" role="presentation">
        <span class="hero-trail-line"></span>
        <span class="hero-ant"></span>
        <span class="hero-ant"></span>
        <span class="hero-ant"></span>
    </div>
    <!-- /wp:html -->

</div>
<!-- /wp:group -->
