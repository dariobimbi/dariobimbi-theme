<?php
/**
 * Title: About
 * Slug: dariobimbi/about
 * Categories: dariobimbi
 * Description: Two-column about section with bio and a quick-facts card. Sits on the elevated surface tone.
 */
?>
<!-- wp:group {"align":"full","className":"section section--surface","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"var:preset|spacing|30","right":"var:preset|spacing|30"},"blockGap":"var:preset|spacing|40"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull section section--surface" style="padding-top:var(--wp--preset--spacing--60);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--30)">

    <!-- wp:paragraph {"className":"eyebrow"} -->
    <p class="eyebrow">about</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"className":"has-gradient-text","style":{"spacing":{"margin":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|40"}}}} -->
    <h2 class="wp-block-heading has-gradient-text" id="about" style="margin-top:var(--wp--preset--spacing--20);margin-bottom:var(--wp--preset--spacing--40)">Ten years on the web.</h2>
    <!-- /wp:heading -->

    <!-- wp:columns {"style":{"spacing":{"blockGap":{"top":"var:preset|spacing|40","left":"var:preset|spacing|50"}}}} -->
    <div class="wp-block-columns">

        <!-- wp:column {"width":"60%"} -->
        <div class="wp-block-column" style="flex-basis:60%">
            <!-- wp:paragraph {"fontSize":"large","style":{"typography":{"lineHeight":"1.55"}}} -->
            <p class="has-large-font-size" style="line-height:1.55">I started in game development at DePaul University, and that is where I picked up the habit of caring how a site feels, not just whether it loads. Day to day at Clicks I build custom WordPress themes, write functions and widgets, run PHPUnit and Jest, review pull requests from my team, and spend a fair amount of time talking with clients about what their site really needs to do. I have also shipped a couple of Flutter mobile apps for Clicks, with a Firestore and Google Cloud backend behind them, and on the database side I am in MySQL most days. That client conversation part is honestly half the work, since most clients need a little help putting the goal into words.</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column {"width":"40%"} -->
        <div class="wp-block-column" style="flex-basis:40%">
            <!-- wp:group {"className":"dario-card","style":{"spacing":{"padding":{"top":"var:preset|spacing|40","right":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40"},"blockGap":"var:preset|spacing|20"}},"layout":{"type":"constrained"}} -->
            <div class="wp-block-group dario-card" style="padding:var(--wp--preset--spacing--40)">

                <!-- wp:paragraph {"className":"eyebrow"} -->
                <p class="eyebrow">quick facts</p>
                <!-- /wp:paragraph -->

                <!-- wp:list {"style":{"typography":{"lineHeight":"1.8"}}} -->
                <ul class="wp-block-list" style="line-height:1.8">
                    <!-- wp:list-item --><li><strong>Role:</strong> Web Developer II, Clicks Web Design Inc.</li><!-- /wp:list-item -->
                    <!-- wp:list-item --><li><strong>Based in:</strong> St. Charles, Illinois</li><!-- /wp:list-item -->
                    <!-- wp:list-item --><li><strong>Education:</strong> B.S. Computer Game Development, DePaul University</li><!-- /wp:list-item -->
                    <!-- wp:list-item --><li><strong>Stack:</strong> WordPress, PHP, JavaScript, Angular, Flutter</li><!-- /wp:list-item -->
                    <!-- wp:list-item --><li><strong>Data:</strong> MySQL, Firestore, Google Cloud</li><!-- /wp:list-item -->
                    <!-- wp:list-item --><li><strong>Tools:</strong> Adobe XD, Photoshop, Premiere, PHPUnit, Jest, GitHub</li><!-- /wp:list-item -->
                </ul>
                <!-- /wp:list -->

            </div>
            <!-- /wp:group -->
        </div>
        <!-- /wp:column -->

    </div>
    <!-- /wp:columns -->

</div>
<!-- /wp:group -->
