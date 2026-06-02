<?php
/**
 * Favicon / touch-icon link tags.
 *
 * The theme ships its brand mark as files under assets/ rather than going
 * through WP's Site Icon admin setting (which stores an attachment ID in the
 * DB). So WP's own wp_site_icon() emits nothing here — we print the tags
 * ourselves, pointing at the squared schnauzer mark. Sizes are derived from
 * assets/logo-mark.png by tools (see CLAUDE.md "logo" notes).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/*
 * The theme owns the favicon (files under assets/), so suppress WP's own
 * Site Icon output. Without this, a stale site_icon attachment left in the DB
 * would emit a second, conflicting set of icon tags on the front end.
 */
remove_action( 'wp_head', 'wp_site_icon', 99 );

add_action( 'wp_head', function () {
    $assets  = get_stylesheet_directory_uri() . '/assets';
    $version = wp_get_theme()->get( 'Version' );
    $v       = '?ver=' . rawurlencode( $version );

    $links = [
        '<link rel="icon" type="image/png" sizes="32x32" href="%1$s/favicon-32.png%2$s">',
        '<link rel="icon" type="image/png" sizes="512x512" href="%1$s/logo-mark.png%2$s">',
        '<link rel="apple-touch-icon" sizes="180x180" href="%1$s/apple-touch-icon.png%2$s">',
    ];

    foreach ( $links as $link ) {
        printf( "\n" . $link, esc_url( $assets ), esc_attr( $v ) );
    }
    echo "\n";
}, 2 );
