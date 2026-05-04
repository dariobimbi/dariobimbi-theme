<?php
/**
 * Title: Project — The Lost Probe
 * Slug: dariobimbi/project-lost-probe
 * Categories: dariobimbi
 * Description: Full write-up for The Lost Probe, a four-person capstone game made at DePaul (GAM 394, winter 2014). Originally pitched as Nuts & Bolts, the game is about a robot trying to put himself back together after his ship crashes on an unknown planet.
 *
 * To use: child Page "The Lost Probe" under the Projects parent (slug
 * `lost-probe`), pick the "Project — The Lost Probe" template.
 * Final URL: /projects/lost-probe/.
 */
$assets = esc_url( get_stylesheet_directory_uri() . '/assets/projects/lost-probe/' );
?>
<!-- wp:group {"align":"full","className":"content-page project-page project-lost-probe","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull content-page project-page project-lost-probe" style="padding-top:var(--wp--preset--spacing--70);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--70);padding-left:var(--wp--preset--spacing--30)">

    <!-- wp:paragraph {"className":"eyebrow"} -->
    <p class="eyebrow">game · unity · school capstone</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"level":1,"className":"has-gradient-text","style":{"spacing":{"margin":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20"}}}} -->
    <h1 class="wp-block-heading has-gradient-text" style="margin-top:var(--wp--preset--spacing--20);margin-bottom:var(--wp--preset--spacing--20)">The Lost Probe</h1>
    <!-- /wp:heading -->

    <!-- wp:paragraph {"fontSize":"large","textColor":"muted","style":{"typography":{"lineHeight":"1.55"},"spacing":{"margin":{"bottom":"var:preset|spacing|40"}}}} -->
    <p class="has-muted-color has-text-color has-large-font-size" style="line-height:1.55;margin-bottom:var(--wp--preset--spacing--40)">A four-person capstone project at DePaul, originally pitched as Nuts &amp; Bolts. The premise is that a space exploration robot loses his ship to an electromagnetic field on planetary entry and has to wander a strange new world looking for the rest of his body.</p>
    <!-- /wp:paragraph -->

    <!-- wp:html -->
    <figure class="project-hero">
        <img src="<?php echo $assets; ?>lp-cover.jpg" alt="The Lost Probe cover artwork showing the robot character" loading="lazy">
        <figcaption>Promotional cover artwork for The Lost Probe.</figcaption>
    </figure>
    <!-- /wp:html -->

    <!-- wp:heading {"level":2} -->
    <h2 class="wp-block-heading">The story</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph -->
    <p>The robot is named Scrapes. Humanity sent him out to find new habitable planets after Earth started to run out of room, and his ship hit an unexpected electromagnetic field on entry that exploded everything but his head. The game is about working through that strange new world to find the rest of him, with the catch that the planet's atmosphere keeps changing him in small ways he did not expect.</p>
    <!-- /wp:paragraph -->

    <!-- wp:html -->
    <figure class="project-figure">
        <img src="<?php echo $assets; ?>lp-art.jpg" alt="Concept art for The Lost Probe" loading="lazy">
        <figcaption>Early concept art from the team's design doc.</figcaption>
    </figure>
    <!-- /wp:html -->

    <!-- wp:heading {"level":2} -->
    <h2 class="wp-block-heading">What I did</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph -->
    <p>I owned the level layout and the puzzle design across each zone. The core mechanic is that as Scrapes finds his body parts, the shape he can fold into changes. Just a head he is a circle (small spaces, fast leaps). With his body added he becomes a square (heavier, presses buttons, breaks weak objects). Arms turn him into a triangle (levers, glide). Legs let him take a hexagon shape, the strongest one, which can smash through stone walls.</p>
    <!-- /wp:paragraph -->

    <!-- wp:paragraph -->
    <p>Each zone of the world was tied to one of those abilities, and the puzzles in that zone were built to teach the new shape without saying so out loud. A lot of the work was sitting at the opening of each zone and asking whether someone playing for the first time would understand what they could do, or whether they would get stuck and stop having fun.</p>
    <!-- /wp:paragraph -->

    <!-- wp:html -->
    <div class="project-gallery">
        <img src="<?php echo $assets; ?>lp-level1a.jpg" alt="Level 1 layout from The Lost Probe" loading="lazy">
        <img src="<?php echo $assets; ?>lp-level2.jpg" alt="Level 2 layout from The Lost Probe" loading="lazy">
    </div>
    <!-- /wp:html -->

    <!-- wp:heading {"level":2} -->
    <h2 class="wp-block-heading">The team</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph -->
    <p>Four people, GAM 394 capstone class at DePaul during the winter 2014 quarter, run by Professor Turner. I led the level and puzzle work, and the other three on the team handled the modeling, animation, and core scripting. The pitch shifted directions a few times before we settled on the body-piece game we ended up shipping.</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"level":2} -->
    <h2 class="wp-block-heading">What I took away</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph -->
    <p>This was the project that taught me to design for the player's confusion, not the developer's. The first time we put level 1 in front of a stranger, they did things we never expected and got stuck on things we thought were obvious. We rebuilt the opening three times before it landed, and the version we shipped was the one where the player figured out how to move on their own.</p>
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
