<?php
/**
 * Title: Project — NicKey Performance
 * Slug: dariobimbi/project-nickey-performance
 * Categories: dariobimbi
 * Description: Full write-up for NicKey Performance, a performance auto shop website I built and maintained from 2014 to 2017. Covers the dual WordPress/Joomla work and the custom HTML pipeline for the eBay vehicle listings.
 *
 * To use: child Page "NicKey Performance" under the Projects parent (slug
 * `nickey-performance`), pick the "Project — NicKey Performance" template.
 * Final URL: /projects/nickey-performance/.
 *
 * Hero screenshot lives in assets/projects/nickey-performance/. The live site is
 * also linked in the page so visitors can see the current state for themselves.
 */
$assets = esc_url( get_stylesheet_directory_uri() . '/assets/projects/nickey-performance/' );
?>
<!-- wp:group {"align":"full","className":"content-page project-page project-nickey","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull content-page project-page project-nickey" style="padding-top:var(--wp--preset--spacing--70);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--70);padding-left:var(--wp--preset--spacing--30)">

    <!-- wp:paragraph {"className":"eyebrow"} -->
    <p class="eyebrow">web · wordpress · joomla</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"level":1,"className":"has-gradient-text","style":{"spacing":{"margin":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20"}}}} -->
    <h1 class="wp-block-heading has-gradient-text" style="margin-top:var(--wp--preset--spacing--20);margin-bottom:var(--wp--preset--spacing--20)">NicKey Performance</h1>
    <!-- /wp:heading -->

    <!-- wp:paragraph {"fontSize":"large","textColor":"muted","style":{"typography":{"lineHeight":"1.55"},"spacing":{"margin":{"bottom":"var:preset|spacing|40"}}}} -->
    <p class="has-muted-color has-text-color has-large-font-size" style="line-height:1.55;margin-bottom:var(--wp--preset--spacing--40)">The website for NicKey Performance, a performance auto shop in Loves Park, Illinois. I built and maintained it across both WordPress and Joomla over about three years, and wrote the custom HTML pipeline they used to list cars for sale on eBay.</p>
    <!-- /wp:paragraph -->

    <!-- wp:html -->
    <figure class="project-hero">
        <img src="<?php echo $assets; ?>nickey-hero.jpeg" alt="Homepage of nickeyperformance.com showing the hero with a row of muscle cars and the Original Super Car Headquarters tagline" loading="lazy">
        <figcaption>The current homepage at nickeyperformance.com.</figcaption>
    </figure>
    <!-- /wp:html -->

    <!-- wp:heading {"level":2} -->
    <h2 class="wp-block-heading">The work</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph -->
    <p>NicKey is a small specialty shop that builds and sells modified vehicles, which means the website has two jobs: tell visitors who the shop is, and list every vehicle currently for sale with enough detail that a serious buyer can decide before driving up. I built and maintained the front-end pages on both Joomla and WordPress (the site moved between platforms during my time there), produced the ads and digital images for the seasonal promos, and managed the CMS content alongside the day-to-day work of a small shop.</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"level":2} -->
    <h2 class="wp-block-heading">The eBay pipeline</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph -->
    <p>The piece I am most proud of from that role is the eBay listing pipeline. NicKey sold most of their inventory through eBay Motors, and each listing needed a custom HTML page with photos, specs, and a styled write-up. Building that page by hand for every vehicle was eating up the staff's time. I wrote a small templating layer that took the vehicle's specs as input and produced a clean, branded eBay-compatible HTML page on the other end. Once it was set up, putting a new vehicle up for sale went from about an hour of copy-paste to a few minutes of filling in fields.</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"level":2} -->
    <h2 class="wp-block-heading">The shop</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph -->
    <p>NicKey Performance is family-owned, run out of a garage in Loves Park, Illinois, and they have been around long enough that anyone serious about modified American muscle in northern Illinois has heard of them. The site is still live at <a href="https://nickeyperformance.com/" target="_blank" rel="noopener">nickeyperformance.com</a>, though some of the work has been updated by other hands since I left in 2017.</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"level":2} -->
    <h2 class="wp-block-heading">What I took away</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph -->
    <p>This was my first real client-facing role, and most of what I know about talking with people who are not developers I learned there. The owner did not care about my favorite frameworks. He cared whether the vehicle pages looked good, whether the email contact form actually delivered to his inbox, and whether the eBay listings went out without him having to copy-paste HTML. That framing has stuck with me, and it is mostly the framing I still use when I sit down with a Clicks client today.</p>
    <!-- /wp:paragraph -->

    <!-- wp:buttons {"layout":{"type":"flex"},"style":{"spacing":{"margin":{"top":"var:preset|spacing|50"}}}} -->
    <div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--50)">
        <!-- wp:button {"className":"is-style-outline"} -->
        <div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/projects/' ) ); ?>">← Back to Projects</a></div>
        <!-- /wp:button -->

        <!-- wp:button -->
        <div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="https://nickeyperformance.com/" target="_blank" rel="noopener">View live site →</a></div>
        <!-- /wp:button -->

        <!-- wp:button {"className":"is-style-outline"} -->
        <div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/#contact' ) ); ?>">Get in touch</a></div>
        <!-- /wp:button -->
    </div>
    <!-- /wp:buttons -->

</div>
<!-- /wp:group -->
