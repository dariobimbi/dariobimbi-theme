<?php
/**
 * Custom XML sitemap at /sitemap.xml.
 *
 * WP's built-in sitemap lives at /wp-sitemap.xml (an index) and splits the
 * URL list off to /wp-sitemap-posts-page-1.xml, which is an awkward URL to
 * advertise from the human sitemap page. This module replaces it with a
 * single clean /sitemap.xml that lists the homepage and every published
 * Page, ordered the same way the human sitemap is.
 *
 * The XML is generated on every request from get_pages(), so a new Page
 * shows up the moment it's published. Nothing is written to disk.
 *
 * WP's default /wp-sitemap.xml is turned off so crawlers don't see two
 * competing sitemaps.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter( 'wp_sitemaps_enabled', '__return_false' );

add_action( 'init', function () {
    add_rewrite_rule( '^sitemap\.xml$', 'index.php?dariobimbi_sitemap=1', 'top' );
} );

add_filter( 'query_vars', function ( $vars ) {
    $vars[] = 'dariobimbi_sitemap';
    return $vars;
} );

add_action( 'template_redirect', function () {
    if ( ! get_query_var( 'dariobimbi_sitemap' ) ) {
        return;
    }
    dariobimbi_render_sitemap_xml();
    exit;
} );

function dariobimbi_render_sitemap_xml(): void {
    $pages = get_pages( [
        'sort_column' => 'menu_order,post_title',
        'post_status' => 'publish',
    ] );

    /* Homepage gets the latest post_modified across all pages, since changing
       any section of any page is effectively a change to the front door. */
    $latest_gmt = '';
    foreach ( $pages as $p ) {
        if ( $p->post_modified_gmt > $latest_gmt ) {
            $latest_gmt = $p->post_modified_gmt;
        }
    }

    /* Each entry: loc, lastmod, and an optional images list. The homepage
       reuses the OG banner so image search has a representative thumbnail
       for the front door. Inner pages get their featured image when set. */
    $banner = get_stylesheet_directory_uri() . '/assets/banner.png';

    $urls = [
        [
            'loc'     => home_url( '/' ),
            'lastmod' => $latest_gmt ? mysql2date( 'c', $latest_gmt, false ) : '',
            'images'  => [ $banner ],
        ],
    ];
    foreach ( $pages as $p ) {
        $images = [];
        $thumb_id = (int) get_post_thumbnail_id( $p->ID );
        if ( $thumb_id ) {
            $src = wp_get_attachment_image_src( $thumb_id, 'full' );
            if ( $src && ! empty( $src[0] ) ) {
                $images[] = (string) $src[0];
            }
        }
        $urls[] = [
            'loc'     => get_permalink( $p ),
            'lastmod' => mysql2date( 'c', $p->post_modified_gmt, false ),
            'images'  => $images,
        ];
    }

    header( 'Content-Type: application/xml; charset=UTF-8' );
    header( 'X-Robots-Tag: noindex, follow', true );
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n"
       . '        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";
    foreach ( $urls as $u ) {
        echo "  <url>\n";
        echo '    <loc>' . esc_url( $u['loc'] ) . "</loc>\n";
        if ( ! empty( $u['lastmod'] ) ) {
            echo '    <lastmod>' . esc_html( $u['lastmod'] ) . "</lastmod>\n";
        }
        if ( ! empty( $u['images'] ) ) {
            foreach ( $u['images'] as $img_url ) {
                echo "    <image:image>\n";
                echo '      <image:loc>' . esc_url( $img_url ) . "</image:loc>\n";
                echo "    </image:image>\n";
            }
        }
        echo "  </url>\n";
    }
    echo "</urlset>\n";
}

/**
 * Flush rewrite rules once per theme version. Without this, /sitemap.xml
 * 404s until an admin manually visits Settings → Permalinks. Same pattern
 * as inc/auto-pages.php — bumping the theme Version triggers a single
 * flush on the next admin page load.
 */
add_action( 'admin_init', function () {
    $theme_version = wp_get_theme()->get( 'Version' );
    if ( get_option( 'dariobimbi_sitemap_rules_version' ) === $theme_version ) {
        return;
    }
    flush_rewrite_rules( false );
    update_option( 'dariobimbi_sitemap_rules_version', $theme_version, false );
} );
