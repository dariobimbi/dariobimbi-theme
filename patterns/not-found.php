<?php
/**
 * Title: 404 Not Found
 * Slug: dariobimbi/not-found
 * Categories: dariobimbi
 * Description: Friendly, on-brand 404 page with a fake terminal session, search box, and CTAs back to home and contact.
 */
$home    = esc_url( home_url( '/' ) );
$contact = esc_url( home_url( '/#contact' ) );

/* Reconstruct the URL the visitor actually requested for the curl line.
   Host comes from home_url() (trusted site config) rather than $_SERVER
   ['HTTP_HOST'], which is attacker-controlled. The path comes from
   REQUEST_URI but is escaped before output. */
$site    = wp_parse_url( home_url() );
$req     = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
$current = esc_html(
    ( $site['scheme'] ?? 'https' ) . '://'
    . ( $site['host']   ?? '' )
    . ( ! empty( $site['port'] ) ? ':' . $site['port'] : '' )
    . $req
);
?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|80","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--80);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--80);padding-left:var(--wp--preset--spacing--30)">

    <!-- wp:paragraph {"align":"center","className":"eyebrow"} -->
    <p class="has-text-align-center eyebrow">error 404</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"textAlign":"center","level":1,"className":"has-gradient-text","style":{"typography":{"fontSize":"clamp(3rem, 9vw, 6rem)","lineHeight":"1.05","letterSpacing":"-0.02em"},"spacing":{"margin":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|30"}}}} -->
    <h1 class="wp-block-heading has-text-align-center has-gradient-text" style="margin-top:var(--wp--preset--spacing--20);margin-bottom:var(--wp--preset--spacing--30);font-size:clamp(3rem, 9vw, 6rem);letter-spacing:-0.02em;line-height:1.05">404. Page not found.</h1>
    <!-- /wp:heading -->

    <!-- wp:html -->
    <pre class="not-found-art" aria-hidden="true">  $ <span class="t-func">curl</span> <span class="t-string">"<?php echo $current; ?>"</span>
  <span class="t-comment">&gt; HTTP/1.1 404 Not Found</span>
  <span class="t-comment">&gt; Content-Type: text/sad</span>
  <span class="t-comment">&gt;</span>
  <span class="t-comment">&gt; The page you wanted isn't here. It could be a typo, or the</span>
  <span class="t-comment">&gt; link could just be broken. Either way, the rest of the site is working fine.</span></pre>
    <!-- /wp:html -->

    <!-- wp:paragraph {"align":"center","fontSize":"large","textColor":"muted","style":{"typography":{"lineHeight":"1.55"},"spacing":{"margin":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40"}}}} -->
    <p class="has-text-align-center has-muted-color has-text-color has-large-font-size" style="margin-top:var(--wp--preset--spacing--40);margin-bottom:var(--wp--preset--spacing--40);line-height:1.55">Try the homepage, search the site, or jump straight to a section.</p>
    <!-- /wp:paragraph -->

    <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
    <div class="wp-block-buttons">
        <!-- wp:button -->
        <div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="<?php echo $home; ?>">Take me home →</a></div>
        <!-- /wp:button -->

        <!-- wp:button {"className":"is-style-outline"} -->
        <div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="<?php echo $contact; ?>">Report a broken link</a></div>
        <!-- /wp:button -->
    </div>
    <!-- /wp:buttons -->

    <!-- wp:search {"label":"Search","showLabel":false,"placeholder":"Search the site…","buttonText":"Search","buttonPosition":"button-inside","style":{"spacing":{"margin":{"top":"var:preset|spacing|50"}}}} /-->

</div>
<!-- /wp:group -->
