<?php
/**
 * robots.txt — extend WordPress's virtual /robots.txt with our sitemap URL.
 *
 * WP serves a default /robots.txt when no static file is present at the
 * web root: it allows everything except /wp-admin/ (with an explicit allow
 * for admin-ajax.php) and, when wp_sitemaps_enabled is on, advertises
 * /wp-sitemap.xml. We disable WP's built-in sitemap in inc/sitemap.php and
 * publish our own at /sitemap.xml, so we re-add the Sitemap line here.
 *
 * The filter only runs when the site is set to "Discourage search engines"
 * = off (Settings → Reading), so a private install stays private.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter( 'robots_txt', function ( $output, $public ) {
    if ( ! $public ) {
        return $output;
    }
    $sitemap = esc_url_raw( home_url( '/sitemap.xml' ) );
    if ( strpos( $output, $sitemap ) !== false ) {
        return $output;
    }
    return rtrim( $output ) . "\n\nSitemap: " . $sitemap . "\n";
}, 10, 2 );
