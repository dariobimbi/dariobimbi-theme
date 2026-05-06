<?php
/**
 * Title: Hero
 * Slug: dariobimbi/hero
 * Categories: dariobimbi, featured
 * Description: Full-bleed banner image followed by gradient headline, lead, and CTAs. Contains an animated gradient mesh behind the content.
 */
$assets         = get_stylesheet_directory_uri() . '/assets';
$banner_webp_sm = esc_url( $assets . '/banner-hero-800.webp' );
$banner_webp_lg = esc_url( $assets . '/banner-hero.webp' );
$banner_jpg_sm  = esc_url( $assets . '/banner-hero-800.jpg' );
$banner_jpg_lg  = esc_url( $assets . '/banner-hero.jpg' );
?>
<!-- wp:group {"align":"full","className":"section hero-stage","style":{"spacing":{"padding":{"top":"0","bottom":"var:preset|spacing|60"},"blockGap":"var:preset|spacing|40"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull section hero-stage" style="padding-top:0;padding-bottom:var(--wp--preset--spacing--60)">

    <!-- wp:html -->
    <div class="hero-mesh" aria-hidden="true"></div>
    <!-- /wp:html -->

    <!-- wp:html -->
    <figure class="wp-block-image alignfull size-full" style="margin-top:0;margin-bottom:var(--wp--preset--spacing--40)">
        <picture>
            <source type="image/webp" srcset="<?php echo $banner_webp_sm; ?> 800w, <?php echo $banner_webp_lg; ?> 1600w" sizes="100vw">
            <img src="<?php echo $banner_jpg_lg; ?>" srcset="<?php echo $banner_jpg_sm; ?> 800w, <?php echo $banner_jpg_lg; ?> 1600w" sizes="100vw" width="1600" height="639" alt="Dario Bimbi, Web Developer. Build. Design. Solve. Improve." fetchpriority="high" loading="eager" decoding="async">
        </picture>
    </figure>
    <!-- /wp:html -->

    <!-- wp:paragraph {"align":"center","className":"status-pill"} -->
    <p class="has-text-align-center status-pill">Currently at <a href="https://clickswebdesign.com">Clicks Web Design</a></p>
    <!-- /wp:paragraph -->

    <!-- wp:paragraph {"align":"center","className":"eyebrow","style":{"spacing":{"margin":{"top":"var:preset|spacing|30"}}}} -->
    <p class="has-text-align-center eyebrow" style="margin-top:var(--wp--preset--spacing--30)">what i do</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"textAlign":"center","level":1,"className":"has-gradient-text","style":{"spacing":{"margin":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|30"}}}} -->
    <h1 class="wp-block-heading has-text-align-center has-gradient-text" style="margin-top:var(--wp--preset--spacing--20);margin-bottom:var(--wp--preset--spacing--30)">Build. Design.<br>Solve. Improve.</h1>
    <!-- /wp:heading -->

    <!-- wp:paragraph {"align":"center","fontSize":"large","textColor":"muted","style":{"typography":{"lineHeight":"1.5"}}} -->
    <p class="has-text-align-center has-muted-color has-text-color has-large-font-size" style="line-height:1.5">I'm <strong>Dario Bimbi</strong>, a web developer at Clicks Web Design. I build custom WordPress themes, plugins, and web apps, and after about ten years of doing this work I have a pretty clear idea of what makes a site good and what makes a site just look good.</p>
    <!-- /wp:paragraph -->

    <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"margin":{"top":"var:preset|spacing|40"}}}} -->
    <div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--40)">
        <!-- wp:button -->
        <div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#projects">View projects →</a></div>
        <!-- /wp:button -->

        <!-- wp:button {"className":"is-style-outline"} -->
        <div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="#contact">Get in touch</a></div>
        <!-- /wp:button -->
    </div>
    <!-- /wp:buttons -->

</div>
<!-- /wp:group -->
