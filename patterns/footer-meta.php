<?php
/**
 * Title: Footer Meta Links
 * Slug: dariobimbi/footer-meta
 * Categories: dariobimbi
 * Description: Right-hand footer link strip — LinkedIn, email, privacy, sitemap. Lives in a pattern (not the footer template part) because URLs need home_url(), and template parts are HTML-only.
 */
$privacy = esc_url( home_url( '/privacy-policy/' ) );
$map     = esc_url( home_url( '/sitemap/' ) );
?>
<!-- wp:paragraph {"fontSize":"small"} -->
<p class="has-small-font-size"><a href="https://www.linkedin.com/in/dario-bimbi-7512a36b">LinkedIn</a> · <a href="mailto:dariobimbi@gmail.com">Email</a> · <a href="<?php echo $privacy; ?>">Privacy</a> · <a href="<?php echo $map; ?>">Sitemap</a></p>
<!-- /wp:paragraph -->
