<?php
/**
 * Title: Project — One Robot Army
 * Slug: dariobimbi/project-one-robot-army
 * Categories: dariobimbi
 * Description: Full write-up for One Robot Army, a small Unity action game made with a four-person team for DePaul GAM 245. The player is a cyborg fighting through three levels in a post-nuclear future to rescue his wife.
 *
 * To use: child Page "One Robot Army" under the Projects parent (slug
 * `one-robot-army`), pick the "Project — One Robot Army" template.
 * Final URL: /projects/one-robot-army/.
 */
$assets = esc_url( get_stylesheet_directory_uri() . '/assets/projects/one-robot-army/' );
?>
<!-- wp:group {"align":"full","className":"content-page project-page project-one-robot-army","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull content-page project-page project-one-robot-army" style="padding-top:var(--wp--preset--spacing--70);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--70);padding-left:var(--wp--preset--spacing--30)">

    <!-- wp:paragraph {"className":"eyebrow"} -->
    <p class="eyebrow">game · unity · school</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"level":1,"className":"has-gradient-text","style":{"spacing":{"margin":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20"}}}} -->
    <h1 class="wp-block-heading has-gradient-text" style="margin-top:var(--wp--preset--spacing--20);margin-bottom:var(--wp--preset--spacing--20)">One Robot Army</h1>
    <!-- /wp:heading -->

    <!-- wp:paragraph {"fontSize":"large","textColor":"muted","style":{"typography":{"lineHeight":"1.55"},"spacing":{"margin":{"bottom":"var:preset|spacing|40"}}}} -->
    <p class="has-muted-color has-text-color has-large-font-size" style="line-height:1.55;margin-bottom:var(--wp--preset--spacing--40)">A small Unity action game I worked on with a team of four at DePaul (GAM 245). Set in a post-nuclear future where the robots have taken over and a cyborg has to fight through three levels to rescue his wife.</p>
    <!-- /wp:paragraph -->

    <!-- wp:html -->
    <figure class="project-hero">
        <img src="<?php echo $assets; ?>ora-cover.png" alt="One Robot Army title artwork" loading="lazy">
        <figcaption>Title artwork from the One Robot Army design doc.</figcaption>
    </figure>
    <!-- /wp:html -->

    <!-- wp:heading {"level":2} -->
    <h2 class="wp-block-heading">The story</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph -->
    <p>After a nuclear war, the robots have taken over the world and started building cities of their own. There are very few humans left, but a handful of cyborgs are still around the edges. The robot government has decided that even cyborgs cannot stay, and the player's character finds his wife taken to the city for execution. The game is the rescue.</p>
    <!-- /wp:paragraph -->

    <!-- wp:html -->
    <figure class="project-figure">
        <img src="<?php echo $assets; ?>ora-art.png" alt="One Robot Army character art" loading="lazy">
        <figcaption>Character artwork from the design doc.</figcaption>
    </figure>
    <!-- /wp:html -->

    <!-- wp:heading {"level":2} -->
    <h2 class="wp-block-heading">What I did</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph -->
    <p>I shared the level design and gameplay scripting with the rest of the team. The game has three levels, and each one was supposed to feel different from the last: the scrapyard outside the city where the player starts, the streets and rooftops of the city itself, and the government building at the end. Most of my time on the project went into wiring up the encounters in each level and making sure the player always had a clear next step in front of them.</p>
    <!-- /wp:paragraph -->

    <!-- wp:html -->
    <div class="project-gallery">
        <img src="<?php echo $assets; ?>ora-thumb-clr.jpg" alt="One Robot Army color portfolio thumbnail" loading="lazy">
        <img src="<?php echo $assets; ?>ora-thumb-bw.jpg" alt="One Robot Army black and white portfolio thumbnail" loading="lazy">
    </div>
    <!-- /wp:html -->

    <!-- wp:heading {"level":2} -->
    <h2 class="wp-block-heading">The team</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph -->
    <p>Four people for GAM 245 at DePaul: Anas Azmi, Tyler Murray, Jack Guffey, and me. The pitch was a quick three-week project, so a fair amount of the work was scoping down what we wanted to build into something the four of us could actually finish on time.</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"level":2} -->
    <h2 class="wp-block-heading">What I took away</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph -->
    <p>The project was small enough that we shipped a build with an installer, which felt like a real milestone at the time. It was also the first time I worked on a Unity project where someone else had set the architecture before I touched it, and I learned how to read code that was not mine and figure out where to plug in without breaking what was already there.</p>
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
