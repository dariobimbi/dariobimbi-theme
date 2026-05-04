<?php
/**
 * Title: Project — dariobimbi.com
 * Slug: dariobimbi/project-dariobimbi-com
 * Categories: dariobimbi
 * Description: Full write-up for dariobimbi.com itself. A custom WordPress block theme written from scratch with no plugins, no page builder, no external fonts, and no JavaScript framework. Hand-rolled SEO module, FSE-editable templates, scoped per-demo styles.
 *
 * To use: child Page "dariobimbi.com" under the Projects parent (slug
 * `dariobimbi-com`), pick the "Project — dariobimbi.com" template.
 * Final URL: /projects/dariobimbi-com/.
 */
$assets = esc_url( get_stylesheet_directory_uri() . '/assets/projects/dariobimbi-com/' );
?>
<!-- wp:group {"align":"full","className":"content-page project-page project-dariobimbi","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull content-page project-page project-dariobimbi" style="padding-top:var(--wp--preset--spacing--70);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--70);padding-left:var(--wp--preset--spacing--30)">

    <!-- wp:paragraph {"className":"eyebrow"} -->
    <p class="eyebrow">web · wordpress · this site</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"level":1,"className":"has-gradient-text","style":{"spacing":{"margin":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20"}}}} -->
    <h1 class="wp-block-heading has-gradient-text" style="margin-top:var(--wp--preset--spacing--20);margin-bottom:var(--wp--preset--spacing--20)">dariobimbi.com</h1>
    <!-- /wp:heading -->

    <!-- wp:paragraph {"fontSize":"large","textColor":"muted","style":{"typography":{"lineHeight":"1.55"},"spacing":{"margin":{"bottom":"var:preset|spacing|40"}}}} -->
    <p class="has-muted-color has-text-color has-large-font-size" style="line-height:1.55;margin-bottom:var(--wp--preset--spacing--40)">This site. A custom WordPress block theme written from scratch, with no page builder, no external fonts, no JavaScript framework, and no plugins.</p>
    <!-- /wp:paragraph -->

    <!-- wp:html -->
    <figure class="project-hero">
        <img src="<?php echo $assets; ?>home.jpeg" alt="The dariobimbi.com homepage hero, showing an illustrated portrait of Dario at his desk with the tagline Build · Design · Solve · Improve" loading="lazy">
        <figcaption>The homepage of this site, rendered by the same theme.</figcaption>
    </figure>
    <!-- /wp:html -->

    <!-- wp:heading {"level":2} -->
    <h2 class="wp-block-heading">The pitch</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph -->
    <p>The pitch on this site is partly the site itself. I work with WordPress every day at my day job, and I wanted a portfolio that showed I could build a clean WordPress experience without leaning on the usual ecosystem of plugins and page builders. Everything you see here, from the SEO meta tags in the head to the contact CTA at the bottom, is hand-written PHP, HTML, and CSS in the theme directory.</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"level":2} -->
    <h2 class="wp-block-heading">How it works</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph -->
    <p>The theme is a Full Site Editing block theme, which means the templates are HTML files (not PHP) and the layout is editable from the WordPress block editor. Anywhere a real query or a piece of dynamic data is needed, I move that block out to a PHP pattern and have the template invoke the pattern. That keeps the templates simple and makes the dynamic bits easy to find.</p>
    <!-- /wp:paragraph -->

    <!-- wp:paragraph -->
    <p>There is also a hand-rolled SEO module in <code>inc/seo.php</code> that handles titles, meta descriptions, Open Graph and Twitter card tags, and JSON-LD structured data. No Yoast, no Rank Math, no plugin at all. The SEO defaults are set as constants at the top of that file, and any per-page tuning lives next to the rendering logic so it is easy to read.</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"level":2} -->
    <h2 class="wp-block-heading">What is on it</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph -->
    <p>The homepage has a hero, an about section, an experience timeline, a projects grid, a skills list, and a contact CTA. There is a separate <a href="<?php echo esc_url( home_url( '/demos/' ) ); ?>">/demos/</a> section with small interactive things I built (a contrast checker, a weather playground, a Conway's Game of Life canvas, two old games hand-ported from Processing), and a <a href="<?php echo esc_url( home_url( '/projects/' ) ); ?>">/projects/</a> section like the one you are reading right now.</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"level":2} -->
    <h2 class="wp-block-heading">What I took away</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph -->
    <p>Building your own portfolio is a slightly humbling exercise. You think you have your aesthetic figured out, and then you try to write the homepage paragraph and realize you do not. Most of the work on this site was less about code than about deciding what to say. The voice you are reading was rewritten about three times before it landed in the place I was happy with.</p>
    <!-- /wp:paragraph -->

    <!-- wp:buttons {"layout":{"type":"flex"},"style":{"spacing":{"margin":{"top":"var:preset|spacing|50"}}}} -->
    <div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--50)">
        <!-- wp:button {"className":"is-style-outline"} -->
        <div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/projects/' ) ); ?>">← Back to Projects</a></div>
        <!-- /wp:button -->

        <!-- wp:button -->
        <div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/#contact' ) ); ?>">Get in touch</a></div>
        <!-- /wp:button -->
    </div>
    <!-- /wp:buttons -->

</div>
<!-- /wp:group -->
