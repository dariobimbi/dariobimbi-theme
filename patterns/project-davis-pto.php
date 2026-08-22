<?php
/**
 * Title: Project — Davis Elementary PTO
 * Slug: dariobimbi/project-davis-pto
 * Categories: dariobimbi
 * Description: Full write-up for the Davis Elementary PTO website, a volunteer-maintained Squarespace site for a school PTO in St. Charles, Illinois. Covers the homepage rebuild, the committee illustration set, and the flyer-to-featured-image pipeline.
 *
 * To use: child Page "Davis Elementary PTO" under the Projects parent (slug
 * `davis-pto`), pick the "Project — Davis Elementary PTO" template.
 * Final URL: /projects/davis-pto/.
 *
 * Screenshots live in assets/projects/davis-pto/ and were captured from the
 * live site. The site is linked in the page so visitors can see the current
 * state for themselves, which will drift as the board adds events.
 */
$assets = esc_url( get_stylesheet_directory_uri() . '/assets/projects/davis-pto/' );
?>
<!-- wp:group {"align":"full","className":"content-page project-page project-davis-pto","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull content-page project-page project-davis-pto" style="padding-top:var(--wp--preset--spacing--70);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--70);padding-left:var(--wp--preset--spacing--30)">

    <!-- wp:paragraph {"className":"eyebrow"} -->
    <p class="eyebrow">web · squarespace · volunteer</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"level":1,"className":"has-gradient-text","style":{"spacing":{"margin":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20"}}}} -->
    <h1 class="wp-block-heading has-gradient-text" style="margin-top:var(--wp--preset--spacing--20);margin-bottom:var(--wp--preset--spacing--20)">Davis Elementary PTO</h1>
    <!-- /wp:heading -->

    <!-- wp:paragraph {"fontSize":"large","textColor":"muted","style":{"typography":{"lineHeight":"1.55"},"spacing":{"margin":{"bottom":"var:preset|spacing|40"}}}} -->
    <p class="has-muted-color has-text-color has-large-font-size" style="line-height:1.55;margin-bottom:var(--wp--preset--spacing--40)">The website for the PTO at my kid's elementary school in St. Charles, Illinois. I am the volunteer webmaster and this is my third school year on it. The site runs on Squarespace, which was the board's call and the right one, so most of the work was figuring out how much of a real design I could get inside those limits.</p>
    <!-- /wp:paragraph -->

    <!-- wp:html -->
    <figure class="project-hero">
        <img src="<?php echo $assets; ?>davis-home.png" alt="The Davis Elementary PTO homepage showing the school photo hero and the upcoming events board below it" loading="lazy">
        <figcaption>The homepage: hero, then the upcoming events board.</figcaption>
    </figure>
    <!-- /wp:html -->

    <!-- wp:heading {"level":2} -->
    <h2 class="wp-block-heading">Why Squarespace stays</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph -->
    <p>Squarespace is not the platform I would pick for myself, and this site is the reason that does not matter. A PTO board turns over every year or two, and whoever takes it over after me has to be able to change a date on an event without calling me first. Squarespace gives them that, so the honest job was not to replace it but to see how far I could push the design and the structure while leaving it something a volunteer with ten minutes can edit.</p>
    <!-- /wp:paragraph -->

    <!-- wp:paragraph -->
    <p>That turned into a real constraint on every decision. Anything I built with custom code had to keep working after someone edited the page around it, which ruled out a fair amount of what I would normally reach for. The pieces that survived are the ones that live in the platform's own building blocks and just look better than the defaults do.</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"level":2} -->
    <h2 class="wp-block-heading">The events board</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph -->
    <p>Parents come to a PTO site with one question, which is what is coming up. So the homepage answers that before anything else. The upcoming events board sits directly under the hero, each card carries its date and links to the full event page, and nobody has to open a calendar to find out that the mum sale closes in two weeks. Everything else on the homepage, the welcome copy and the photos and the ways to get involved, sits below that board because it is the second thing people want, not the first.</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"level":2} -->
    <h2 class="wp-block-heading">A card for every committee</h2>
    <!-- /wp:heading -->

    <!-- wp:html -->
    <figure class="project-figure">
        <img src="<?php echo $assets; ?>davis-committees.png" alt="Grid of committee cards on the Davis PTO site, each with a custom flat illustration in the school's blue and gold palette" loading="lazy">
        <figcaption>Part of the committee grid. Every card has its own illustration.</figcaption>
    </figure>
    <!-- /wp:html -->

    <!-- wp:paragraph -->
    <p>There are fifteen committees and every one of them needs a card that tells a parent what they would actually be signing up for. Stock photos would have read as filler, so I generated an illustration set for them instead, working through the whole batch in one sitting against a locked palette and a locked style so the grid reads as one piece rather than fifteen images that happen to sit next to each other. That last part is the hard part, since art generated one prompt at a time drifts a little further with every image and you do not notice until you see them lined up.</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"level":2} -->
    <h2 class="wp-block-heading">The flyer problem</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph -->
    <p>The school sends its flyers out as PDFs, a few a week once the year gets going, and each one has to become an image on an event page. Doing that by hand means opening the PDF, cropping it to whatever size looks right that day, and exporting a JPG, which is how the site ended up carrying nine slightly different copies of the same Trunk or Treat flyer. I replaced it with one repeatable step that takes a PDF and produces a featured image at the standard size, so the board stays consistent no matter who is adding the event or how late at night they are doing it.</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"level":2} -->
    <h2 class="wp-block-heading">What I took away</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph -->
    <p>The thing I keep coming back to is that I am not the only person who touches this site. Everything I built had to survive a volunteer who has never seen the admin before, and that pushed me toward fewer and more obvious patterns than I would have used on something only I maintain. It is a better site for it, honestly, and it made me more careful about the same question on client work.</p>
    <!-- /wp:paragraph -->

    <!-- wp:paragraph -->
    <p>The site is live at <a href="https://www.daviselementarypto.com/" target="_blank" rel="noopener">daviselementarypto.com</a> and the board keeps adding to it through the school year, so what you see there will be ahead of the screenshots on this page.</p>
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
