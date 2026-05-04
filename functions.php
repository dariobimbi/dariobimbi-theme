<?php
/**
 * Dario Bimbi theme — setup, asset enqueue, and pattern category registration.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/inc/seo.php';

add_action( 'after_setup_theme', function () {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'responsive-embeds' );
    add_theme_support( 'editor-styles' );
    add_theme_support( 'html5', [ 'search-form', 'comment-form', 'gallery', 'caption', 'style', 'script' ] );
} );

add_action( 'init', function () {
    register_block_pattern_category( 'dariobimbi', [
        'label'       => __( 'Dario Bimbi', 'dariobimbi' ),
        'description' => __( 'Sections for the Dario Bimbi portfolio site.', 'dariobimbi' ),
    ] );
} );

/**
 * Easter egg #1: a love letter to anyone who hits "View Source".
 * Printed early in <head> so it's the first thing they see.
 */
add_action( 'wp_head', function () {
    echo "\n<!--\n"
       . "    Hey, source-viewer. Welcome to the basement.\n"
       . "\n"
       . "    This site is hand-built — custom block theme, no plugins, no\n"
       . "    page builder, no jQuery, no build step. The CSS playground\n"
       . "    further down is a real editor: textarea + hand-rolled tokenizer\n"
       . "    + Shadow-DOM preview, all in ~250 lines of vanilla JS.\n"
       . "\n"
       . "    Open the console for a hello. Try the Konami code while you're\n"
       . "    there. If you're hiring → dariobimbi@gmail.com\n"
       . "\n"
       . "                                              </> Dario\n"
       . "-->\n";
}, 1 );

add_action( 'wp_enqueue_scripts', function () {
    $version = wp_get_theme()->get( 'Version' );

    wp_enqueue_style(
        'dariobimbi-style',
        get_stylesheet_uri(),
        [],
        $version
    );

    wp_enqueue_script(
        'dariobimbi-script',
        get_stylesheet_directory_uri() . '/assets/dariobimbi.js',
        [],
        $version,
        true // load in footer
    );
} );
