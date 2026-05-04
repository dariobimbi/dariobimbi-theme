<?php
/**
 * Title: Projects
 * Slug: dariobimbi/projects
 * Categories: dariobimbi
 * Description: Five-card grid for highlighted projects, on the elevated surface tone. Two rows: a 3-card row of game work on top, a 2-card row of web work below.
 */
?>
<!-- wp:group {"align":"full","className":"section section--surface","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"var:preset|spacing|30","right":"var:preset|spacing|30"},"blockGap":"var:preset|spacing|40"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull section section--surface" style="padding-top:var(--wp--preset--spacing--60);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--30)">

    <!-- wp:paragraph {"className":"eyebrow"} -->
    <p class="eyebrow">selected work</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"className":"has-gradient-text","style":{"spacing":{"margin":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|40"}}}} -->
    <h2 class="wp-block-heading has-gradient-text" id="projects" style="margin-top:var(--wp--preset--spacing--20);margin-bottom:var(--wp--preset--spacing--40)">Stuff I'm proud of.</h2>
    <!-- /wp:heading -->

    <!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"top":"var:preset|spacing|30","left":"var:preset|spacing|30"}}}} -->
    <div class="wp-block-columns alignwide">

        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:group {"className":"dario-card","style":{"spacing":{"padding":{"top":"var:preset|spacing|40","right":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40"},"blockGap":"var:preset|spacing|20"}},"layout":{"type":"constrained"}} -->
            <div class="wp-block-group dario-card" style="padding:var(--wp--preset--spacing--40)">
                <!-- wp:paragraph {"fontSize":"small","textColor":"hot","style":{"typography":{"fontFamily":"var:preset|font-family|mono","letterSpacing":"0.06em","textTransform":"uppercase"}}} -->
                <p class="has-hot-color has-text-color has-small-font-size" style="font-family:var(--wp--preset--font-family--mono);letter-spacing:0.06em;text-transform:uppercase">Game · Unity</p>
                <!-- /wp:paragraph -->
                <!-- wp:heading {"level":3} -->
                <h3 class="wp-block-heading"><a href="<?php echo esc_url( home_url( '/projects/lost-probe/' ) ); ?>">The Lost Probe</a></h3>
                <!-- /wp:heading -->
                <!-- wp:paragraph {"textColor":"muted"} -->
                <p class="has-muted-color has-text-color">An adventure-puzzle game where the player is a robot who wakes up after a crash and has to wander a strange world looking for the rest of his body. I built the levels and designed the puzzles for each zone.</p>
                <!-- /wp:paragraph -->
            </div>
            <!-- /wp:group -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:group {"className":"dario-card","style":{"spacing":{"padding":{"top":"var:preset|spacing|40","right":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40"},"blockGap":"var:preset|spacing|20"}},"layout":{"type":"constrained"}} -->
            <div class="wp-block-group dario-card" style="padding:var(--wp--preset--spacing--40)">
                <!-- wp:paragraph {"fontSize":"small","textColor":"hot","style":{"typography":{"fontFamily":"var:preset|font-family|mono","letterSpacing":"0.06em","textTransform":"uppercase"}}} -->
                <p class="has-hot-color has-text-color has-small-font-size" style="font-family:var(--wp--preset--font-family--mono);letter-spacing:0.06em;text-transform:uppercase">Game · Unity</p>
                <!-- /wp:paragraph -->
                <!-- wp:heading {"level":3} -->
                <h3 class="wp-block-heading"><a href="<?php echo esc_url( home_url( '/projects/one-robot-army/' ) ); ?>">One Robot Army</a></h3>
                <!-- /wp:heading -->
                <!-- wp:paragraph {"textColor":"muted"} -->
                <p class="has-muted-color has-text-color">A small Unity action game I worked on with a team of four at DePaul (GAM 245). The story is set in a post-nuclear future where robots have taken over, and the player is a cyborg fighting through three levels to rescue his wife after the government captures her. I shared the level design and gameplay scripting with the rest of the team.</p>
                <!-- /wp:paragraph -->
            </div>
            <!-- /wp:group -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:group {"className":"dario-card","style":{"spacing":{"padding":{"top":"var:preset|spacing|40","right":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40"},"blockGap":"var:preset|spacing|20"}},"layout":{"type":"constrained"}} -->
            <div class="wp-block-group dario-card" style="padding:var(--wp--preset--spacing--40)">
                <!-- wp:paragraph {"fontSize":"small","textColor":"hot","style":{"typography":{"fontFamily":"var:preset|font-family|mono","letterSpacing":"0.06em","textTransform":"uppercase"}}} -->
                <p class="has-hot-color has-text-color has-small-font-size" style="font-family:var(--wp--preset--font-family--mono);letter-spacing:0.06em;text-transform:uppercase">Game · Unity · Studio</p>
                <!-- /wp:paragraph -->
                <!-- wp:heading {"level":3} -->
                <h3 class="wp-block-heading"><a href="<?php echo esc_url( home_url( '/projects/space-dwarves/' ) ); ?>">Space Dwarves Entertainment</a></h3>
                <!-- /wp:heading -->
                <!-- wp:paragraph {"textColor":"muted"} -->
                <p class="has-muted-color has-text-color">Lead Level Designer at Space Dwarves Entertainment, where I spent about a year and a half on two Unity games, Bionic Marine Command Online and Osiris. I owned the level layout work, scripted small Unity tools in C# and JavaScript, and led a team of nine other designers through milestones, art reviews, and the long final polish.</p>
                <!-- /wp:paragraph -->
            </div>
            <!-- /wp:group -->
        </div>
        <!-- /wp:column -->

    </div>
    <!-- /wp:columns -->

    <!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"top":"var:preset|spacing|30","left":"var:preset|spacing|30"}}}} -->
    <div class="wp-block-columns alignwide">

        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:group {"className":"dario-card","style":{"spacing":{"padding":{"top":"var:preset|spacing|40","right":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40"},"blockGap":"var:preset|spacing|20"}},"layout":{"type":"constrained"}} -->
            <div class="wp-block-group dario-card" style="padding:var(--wp--preset--spacing--40)">
                <!-- wp:paragraph {"fontSize":"small","textColor":"primary-soft","style":{"typography":{"fontFamily":"var:preset|font-family|mono","letterSpacing":"0.06em","textTransform":"uppercase"}}} -->
                <p class="has-primary-soft-color has-text-color has-small-font-size" style="font-family:var(--wp--preset--font-family--mono);letter-spacing:0.06em;text-transform:uppercase">Web · WordPress</p>
                <!-- /wp:paragraph -->
                <!-- wp:heading {"level":3} -->
                <h3 class="wp-block-heading"><a href="<?php echo esc_url( home_url( '/projects/nickey-performance/' ) ); ?>">NicKey Performance</a></h3>
                <!-- /wp:heading -->
                <!-- wp:paragraph {"textColor":"muted"} -->
                <p class="has-muted-color has-text-color">The website for a performance auto shop in Loves Park, Illinois. I built and maintained it across both WordPress and Joomla, and wrote the custom HTML pipeline they used to list vehicles for sale on eBay. Live at <a href="https://nickeyperformance.com/" target="_blank" rel="noopener">nickeyperformance.com</a>.</p>
                <!-- /wp:paragraph -->
            </div>
            <!-- /wp:group -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:group {"className":"dario-card","style":{"spacing":{"padding":{"top":"var:preset|spacing|40","right":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40"},"blockGap":"var:preset|spacing|20"}},"layout":{"type":"constrained"}} -->
            <div class="wp-block-group dario-card" style="padding:var(--wp--preset--spacing--40)">
                <!-- wp:paragraph {"fontSize":"small","textColor":"primary-soft","style":{"typography":{"fontFamily":"var:preset|font-family|mono","letterSpacing":"0.06em","textTransform":"uppercase"}}} -->
                <p class="has-primary-soft-color has-text-color has-small-font-size" style="font-family:var(--wp--preset--font-family--mono);letter-spacing:0.06em;text-transform:uppercase">Web · WordPress</p>
                <!-- /wp:paragraph -->
                <!-- wp:heading {"level":3} -->
                <h3 class="wp-block-heading"><a href="<?php echo esc_url( home_url( '/projects/dariobimbi-com/' ) ); ?>">dariobimbi.com</a></h3>
                <!-- /wp:heading -->
                <!-- wp:paragraph {"textColor":"muted"} -->
                <p class="has-muted-color has-text-color">This site. A custom WordPress block theme I wrote from scratch, with no page builder, no external fonts, and full editing inside the WordPress editor. The source lives in <code>wp-content/themes/dariobimbi</code>, and I tried to keep it clean enough that someone could read through it without much trouble.</p>
                <!-- /wp:paragraph -->
            </div>
            <!-- /wp:group -->
        </div>
        <!-- /wp:column -->

    </div>
    <!-- /wp:columns -->

</div>
<!-- /wp:group -->
