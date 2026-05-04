<?php
/**
 * Title: Project — Space Dwarves
 * Slug: dariobimbi/project-space-dwarves
 * Categories: dariobimbi
 * Description: Full write-up for the Space Dwarves Entertainment project (Lead Level Designer, 2014 to 2015). Covers the role, the two Unity games (Bionic Marine Command Online and Osiris), screenshots, and a short gameplay clip.
 *
 * To use: child Page "Space Dwarves" under the Projects parent (slug
 * `space-dwarves`), pick the "Project — Space Dwarves" template.
 * Final URL: /projects/space-dwarves/.
 */
$assets = esc_url( get_stylesheet_directory_uri() . '/assets/projects/space-dwarves/' );
?>
<!-- wp:group {"align":"full","className":"content-page project-page project-space-dwarves","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull content-page project-page project-space-dwarves" style="padding-top:var(--wp--preset--spacing--70);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--70);padding-left:var(--wp--preset--spacing--30)">

    <!-- wp:paragraph {"className":"eyebrow"} -->
    <p class="eyebrow">game · unity · studio</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"level":1,"className":"has-gradient-text","style":{"spacing":{"margin":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20"}}}} -->
    <h1 class="wp-block-heading has-gradient-text" style="margin-top:var(--wp--preset--spacing--20);margin-bottom:var(--wp--preset--spacing--20)">Space Dwarves Entertainment</h1>
    <!-- /wp:heading -->

    <!-- wp:paragraph {"fontSize":"large","textColor":"muted","style":{"typography":{"lineHeight":"1.55"},"spacing":{"margin":{"bottom":"var:preset|spacing|40"}}}} -->
    <p class="has-muted-color has-text-color has-large-font-size" style="line-height:1.55;margin-bottom:var(--wp--preset--spacing--40)">Lead Level Designer, January 2014 to July 2015. About a year and a half on two Unity games at a small Chicago studio.</p>
    <!-- /wp:paragraph -->

    <!-- wp:html -->
    <figure class="project-hero">
        <img src="<?php echo $assets; ?>bmco-helmet.jpg" alt="Bionic Marine Command Online helmet promotional artwork" loading="lazy">
        <figcaption>Bionic Marine Command Online promotional art.</figcaption>
    </figure>
    <!-- /wp:html -->

    <!-- wp:heading {"level":2} -->
    <h2 class="wp-block-heading">The role</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph -->
    <p>I joined Space Dwarves Entertainment as a Lead Level Designer while finishing my degree at DePaul, and I stayed for about a year and a half. The studio was small, around twelve people on a busy week, working out of a shared office in Chicago. My job was the level layout work, which on a team that size meant owning the geometry, scripting the encounters, scaling the props in Maya, and pushing builds to playtesters before the next milestone.</p>
    <!-- /wp:paragraph -->

    <!-- wp:paragraph -->
    <p>I also led a team of nine other designers through the milestone-and-review cycle, which usually meant pairing them on layouts, running level reviews with the art team, and sitting in on the client conversations that pinned down what each level was supposed to do. Some of the levels we shipped, some of them we cut, and a fair amount of the work was figuring out which was which.</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"level":2} -->
    <h2 class="wp-block-heading">Bionic Marine Command Online</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph -->
    <p>The studio's flagship project. A multiplayer mech-combat game in Unity where players piloted bipedal marines across military bunker maps. I owned a couple of the bunker levels (the concrete entrance, the radar dish, and the vault interior), and I scripted small Unity tools in C# and JavaScript that the rest of the design team used to lay out cover and spawn points without writing code.</p>
    <!-- /wp:paragraph -->

    <!-- wp:html -->
    <figure class="project-figure">
        <img src="<?php echo $assets; ?>bmco-gameplay.jpg" alt="Bionic Marine Command Online gameplay screenshot" loading="lazy">
        <figcaption>In-engine gameplay shot from one of the bunker levels.</figcaption>
    </figure>
    <!-- /wp:html -->

    <!-- wp:html -->
    <figure class="project-figure">
        <video controls preload="none" poster="<?php echo $assets; ?>bmco-gameplay.jpg" width="1280" height="720">
            <source src="<?php echo $assets; ?>hanger-launch.mp4" type="video/mp4">
            Your browser does not support embedded video. <a href="<?php echo $assets; ?>hanger-launch.mp4">Download the clip</a>.
        </video>
        <figcaption>Hanger launch sequence (about 30 seconds, click to play).</figcaption>
    </figure>
    <!-- /wp:html -->

    <!-- wp:heading {"level":2} -->
    <h2 class="wp-block-heading">Osiris</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph -->
    <p>The studio's second project, which we worked on in parallel for the back half of my time there. Osiris was a more open-world, exploration-driven game with a hand-built terrain pipeline (heightmaps in World Machine, then imported into Unity for set-dressing). I owned the layout for a handful of the early grid sections (A1 through B2 in our internal map naming), wrote the design docs for the NPC encounters in those areas, and prototyped the shanty-town layout that ended up in the early playtests.</p>
    <!-- /wp:paragraph -->

    <!-- wp:html -->
    <figure class="project-gallery">
        <img src="<?php echo $assets; ?>osiris-1.jpg" alt="Osiris environment screenshot showing terrain and rocks" loading="lazy">
        <img src="<?php echo $assets; ?>osiris-2.jpg" alt="Osiris environment screenshot showing a base layout" loading="lazy">
        <img src="<?php echo $assets; ?>osiris-3.jpg" alt="Osiris environment screenshot showing the green forest section" loading="lazy">
    </figure>
    <!-- /wp:html -->

    <!-- wp:heading {"level":2} -->
    <h2 class="wp-block-heading">What I took away</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph -->
    <p>The studio went quiet not long after I left, and neither game ended up shipping to a wide audience, but the year and a half taught me more about how a real project moves than any class did. I learned how to read a build that was not mine and figure out what the level was trying to do. I learned how to give and take feedback in front of an art lead on a Friday afternoon, with a milestone due Monday. And I learned that a good level is mostly the result of cutting the bad parts, not adding more good ones.</p>
    <!-- /wp:paragraph -->

    <!-- wp:paragraph -->
    <p>That last lesson is the one I still use almost every day, building websites instead of mech bunkers.</p>
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

