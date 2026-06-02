<?php
/**
 * Title: Site Brand
 * Slug: dariobimbi/site-brand
 * Categories: dariobimbi
 * Description: The header brand lockup — the schnauzer mark linked to home, with the live site name beside it.
 *
 * Why a pattern (not the core Site Logo block): the Site Logo block reads the
 * custom_logo theme mod, an admin/DB setting. This theme ships its brand art as
 * a file under assets/, so the mark is referenced directly here. PHP gives us
 * home_url() and the live site name. Invoked from parts/header.html.
 */
$mark = get_stylesheet_directory_uri() . '/assets/logo-mark.png';
$name = get_bloginfo( 'name' );
?>
<!-- wp:html -->
<a class="site-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
    <img class="site-brand-mark" src="<?php echo esc_url( $mark ); ?>" width="40" height="40" alt="" decoding="async">
    <span class="site-brand-name"><?php echo esc_html( $name ); ?></span>
</a>
<!-- /wp:html -->
