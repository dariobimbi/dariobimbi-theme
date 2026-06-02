<?php
/**
 * Title: Contact
 * Slug: dariobimbi/contact
 * Categories: dariobimbi
 * Description: Closing CTA with neon-glow card, email button, and LinkedIn link.
 */
?>
<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--70);padding-bottom:var(--wp--preset--spacing--70)">

    <!-- wp:group {"className":"has-neon-glow","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","right":"var:preset|spacing|50","bottom":"var:preset|spacing|60","left":"var:preset|spacing|50"},"blockGap":"var:preset|spacing|30"},"border":{"radius":"20px"},"color":{"background":"#1b1712"}},"layout":{"type":"constrained","contentSize":"640px","justifyContent":"center"}} -->
    <div class="wp-block-group has-neon-glow has-background" style="border-radius:20px;background-color:#1b1712;padding:var(--wp--preset--spacing--60) var(--wp--preset--spacing--50)">

        <!-- wp:paragraph {"align":"center","className":"eyebrow"} -->
        <p class="has-text-align-center eyebrow">get in touch</p>
        <!-- /wp:paragraph -->

        <!-- wp:heading {"textAlign":"center","className":"has-gradient-text","style":{"spacing":{"margin":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|30"}}}} -->
        <h2 class="wp-block-heading has-text-align-center has-gradient-text" id="contact" style="margin-top:var(--wp--preset--spacing--20);margin-bottom:var(--wp--preset--spacing--30)">Tell me what you're building.</h2>
        <!-- /wp:heading -->

        <!-- wp:paragraph {"align":"center","fontSize":"large","textColor":"muted","style":{"typography":{"lineHeight":"1.5"}}} -->
        <p class="has-text-align-center has-muted-color has-text-color has-large-font-size" style="line-height:1.5">Working on a WordPress site or a custom build and could use a second pair of hands? Send me a note below and I usually reply within a day.</p>
        <!-- /wp:paragraph -->

        <!-- wp:html -->
        <?php echo dariobimbi_contact_form(); ?>
        <!-- /wp:html -->

        <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"margin":{"top":"var:preset|spacing|30"}}}} -->
        <div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--30)">
            <!-- wp:button {"className":"is-style-outline"} -->
            <div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="https://www.linkedin.com/in/dario-bimbi-7512a36b">LinkedIn</a></div>
            <!-- /wp:button -->
        </div>
        <!-- /wp:buttons -->

    </div>
    <!-- /wp:group -->

</div>
<!-- /wp:group -->
