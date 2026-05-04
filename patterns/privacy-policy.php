<?php
/**
 * Title: Privacy Policy
 * Slug: dariobimbi/privacy-policy
 * Categories: dariobimbi
 * Description: Plain-language privacy policy for the portfolio site. No analytics theatre — just an honest summary of what the site does and doesn't collect.
 *
 * To use: create a Page in admin with slug "privacy-policy" (WP will auto-pick
 * the page-privacy-policy.html template, which renders this pattern). The Page
 * itself can be empty.
 */
$updated = 'April 30, 2026';
$email   = 'dariobimbi@gmail.com';
?>
<!-- wp:group {"align":"full","className":"content-page","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull content-page" style="padding-top:var(--wp--preset--spacing--70);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--70);padding-left:var(--wp--preset--spacing--30)">

    <!-- wp:paragraph {"className":"eyebrow"} -->
    <p class="eyebrow">the legal bit</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"level":1,"className":"has-gradient-text","style":{"spacing":{"margin":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20"}}}} -->
    <h1 class="wp-block-heading has-gradient-text" style="margin-top:var(--wp--preset--spacing--20);margin-bottom:var(--wp--preset--spacing--20)">Privacy Policy</h1>
    <!-- /wp:heading -->

    <!-- wp:paragraph {"fontSize":"small","textColor":"muted"} -->
    <p class="has-muted-color has-text-color has-small-font-size">Last updated: <?php echo esc_html( $updated ); ?></p>
    <!-- /wp:paragraph -->

    <!-- wp:paragraph {"style":{"typography":{"lineHeight":"1.65"},"spacing":{"margin":{"top":"var:preset|spacing|40"}}}} -->
    <p style="margin-top:var(--wp--preset--spacing--40);line-height:1.65">This is a personal portfolio site, and the whole reason it exists is so people can learn what I do, look at my work, and reach out if they want to talk. The short version of this privacy policy is that I do not collect much. What I do collect is the standard server-log information that every website on the internet collects.</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"level":2,"style":{"spacing":{"margin":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|20"}}}} -->
    <h2 class="wp-block-heading" style="margin-top:var(--wp--preset--spacing--50);margin-bottom:var(--wp--preset--spacing--20)">What this site collects</h2>
    <!-- /wp:heading -->

    <!-- wp:list {"style":{"typography":{"lineHeight":"1.65"}}} -->
    <ul style="line-height:1.65">
        <!-- wp:list-item --><li><strong>Server logs.</strong> The web server records IP address, user agent, requested URL, timestamp, and referrer for every request. These are kept for a short window for diagnostics and security, and aren't shared.</li><!-- /wp:list-item -->
        <!-- wp:list-item --><li><strong>Cookies set by WordPress.</strong> If (and only if) you log in or leave a comment, WordPress sets session and preference cookies. As a regular visitor browsing the site, no cookies are set.</li><!-- /wp:list-item -->
        <!-- wp:list-item --><li><strong>Email you send me.</strong> If you click an email link and write to me, I read what you wrote and reply. I don't add you to any list.</li><!-- /wp:list-item -->
    </ul>
    <!-- /wp:list -->

    <!-- wp:heading {"level":2,"style":{"spacing":{"margin":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|20"}}}} -->
    <h2 class="wp-block-heading" style="margin-top:var(--wp--preset--spacing--50);margin-bottom:var(--wp--preset--spacing--20)">What this site does <em>not</em> do</h2>
    <!-- /wp:heading -->

    <!-- wp:list {"style":{"typography":{"lineHeight":"1.65"}}} -->
    <ul style="line-height:1.65">
        <!-- wp:list-item --><li>No analytics scripts (no Google Analytics, no Plausible, no Fathom, nothing).</li><!-- /wp:list-item -->
        <!-- wp:list-item --><li>No third-party trackers, ad networks, or social-media pixels.</li><!-- /wp:list-item -->
        <!-- wp:list-item --><li>No fonts loaded from a CDN. Everything is system fonts and self-hosted assets.</li><!-- /wp:list-item -->
        <!-- wp:list-item --><li>No newsletter, no mailing list, no remarketing.</li><!-- /wp:list-item -->
        <!-- wp:list-item --><li>No selling, sharing, or renting of any data, ever.</li><!-- /wp:list-item -->
    </ul>
    <!-- /wp:list -->

    <!-- wp:heading {"level":2,"style":{"spacing":{"margin":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|20"}}}} -->
    <h2 class="wp-block-heading" style="margin-top:var(--wp--preset--spacing--50);margin-bottom:var(--wp--preset--spacing--20)">Outbound links</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph {"style":{"typography":{"lineHeight":"1.65"}}} -->
    <p style="line-height:1.65">The site links out to LinkedIn, GitHub, and other external sites. Those sites have their own privacy policies, and once you click an outbound link, the policy on that site is what applies, not this one.</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"level":2,"style":{"spacing":{"margin":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|20"}}}} -->
    <h2 class="wp-block-heading" style="margin-top:var(--wp--preset--spacing--50);margin-bottom:var(--wp--preset--spacing--20)">Your rights</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph {"style":{"typography":{"lineHeight":"1.65"}}} -->
    <p style="line-height:1.65">If you live somewhere with privacy laws like the GDPR or CCPA, you have the right to know what data is held about you, to ask for it to be corrected or deleted, and to file a complaint with a regulator. Because this site does not really hold anything tied to you specifically, those requests are usually a quick reply from me confirming there is nothing to delete. You are still welcome to ask if you want to be sure.</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"level":2,"style":{"spacing":{"margin":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|20"}}}} -->
    <h2 class="wp-block-heading" style="margin-top:var(--wp--preset--spacing--50);margin-bottom:var(--wp--preset--spacing--20)">Changes</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph {"style":{"typography":{"lineHeight":"1.65"}}} -->
    <p style="line-height:1.65">If anything material changes, the date at the top of this page will be updated to match. There is no version history beyond that, since this is just a personal portfolio site.</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"level":2,"style":{"spacing":{"margin":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|20"}}}} -->
    <h2 class="wp-block-heading" style="margin-top:var(--wp--preset--spacing--50);margin-bottom:var(--wp--preset--spacing--20)">Contact</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph {"style":{"typography":{"lineHeight":"1.65"}}} -->
    <p style="line-height:1.65">Privacy questions, deletion requests, or anything else: <a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a>.</p>
    <!-- /wp:paragraph -->

</div>
<!-- /wp:group -->
