<?php
/**
 * SEO module — Dario Bimbi theme.
 *
 * Adds the things the WP core <head> doesn't, and removes the things it
 * adds that nobody needs. Aiming for "well-indexed personal site," not
 * "industrial SEO farm."
 *
 *   - Per-view <title> and <meta description> (homepage gets a hand-written
 *     pitch; inner pages derive from excerpt/content; 404 + search are
 *     marked noindex,follow)
 *   - <link rel="canonical">, theme-color, color-scheme, sitemap link
 *   - Open Graph + Twitter Card meta (OG image = assets/banner.png with
 *     real width/height pulled from the file at runtime)
 *   - JSON-LD: Person + WebSite + SearchAction on the homepage; WebPage +
 *     BreadcrumbList on inner pages
 *   - Defers the theme JS via script_loader_tag (no longer render-blocking)
 *   - Strips legacy head clutter (RSD, WLW, generator, shortlink) — none of
 *     it helps SEO and it just bloats the markup
 *
 * Why a constants block at the top: these are the bits a future-me will
 * actually want to edit. Everything else is plumbing.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

const DARIOBIMBI_TAGLINE  = 'Web Developer · WordPress, PHP, JavaScript';
const DARIOBIMBI_BIO      = 'Portfolio of Dario Bimbi, web developer at Clicks Web Design Inc. Custom WordPress block themes, PHP back-ends, vanilla-JS front-end, and a real eye for accessibility. Based near Chicago.';
const DARIOBIMBI_LINKEDIN = 'https://www.linkedin.com/in/dario-bimbi-7512a36b';
const DARIOBIMBI_OG_IMG   = '/assets/banner.png';
const DARIOBIMBI_LOGO     = '/assets/logo.png';

/* ------------------------------------------------------------------
 * Helpers
 * ------------------------------------------------------------------ */

/** Best-effort description for the current view, capped near the SERP cutoff. */
function dariobimbi_seo_description(): string {
    if ( is_front_page() || is_home() ) {
        return DARIOBIMBI_BIO;
    }
    if ( is_404() ) {
        return 'Page not found. Try the homepage or search the site.';
    }
    if ( is_search() ) {
        return sprintf( 'Search results for "%s" on dariobimbi.com.', esc_html( get_search_query() ) );
    }
    if ( is_singular() ) {
        $excerpt = wp_strip_all_tags( get_the_excerpt() );
        if ( $excerpt !== '' ) {
            return $excerpt;
        }
        $content = wp_strip_all_tags( strip_shortcodes( get_post_field( 'post_content', get_the_ID() ) ) );
        $content = preg_replace( '/\s+/', ' ', trim( $content ) );
        return mb_strimwidth( $content, 0, 158, '…' );
    }
    return get_bloginfo( 'description' ) ?: DARIOBIMBI_BIO;
}

/** Canonical URL for the current view, fully qualified. */
function dariobimbi_seo_canonical(): string {
    if ( is_singular() ) {
        return (string) get_permalink();
    }
    if ( is_front_page() || is_home() ) {
        return home_url( '/' );
    }
    // Strip query strings to avoid splitting page rank across ?utm_=… variants.
    return home_url( strtok( $_SERVER['REQUEST_URI'] ?? '/', '?' ) );
}

/** Absolute URL to the OG image plus its real pixel dimensions. */
function dariobimbi_seo_og_image(): array {
    $rel  = DARIOBIMBI_OG_IMG;
    $url  = get_stylesheet_directory_uri() . $rel;
    $path = get_stylesheet_directory()      . $rel;

    $width = $height = 0;
    if ( file_exists( $path ) ) {
        $size = @getimagesize( $path );
        if ( $size ) {
            $width  = (int) $size[0];
            $height = (int) $size[1];
        }
    }
    return [ 'url' => $url, 'width' => $width, 'height' => $height ];
}

/* ------------------------------------------------------------------
 * <title> tuning
 * ------------------------------------------------------------------ */

add_filter( 'document_title_separator', fn() => '·' );

add_filter( 'document_title_parts', function ( $parts ) {
    if ( is_front_page() ) {
        // Homepage gets a keyword-rich title, not just the site name.
        $parts['title']   = 'Dario Bimbi, ' . DARIOBIMBI_TAGLINE;
        $parts['tagline'] = null;
        unset( $parts['tagline'] );
    } elseif ( is_404() ) {
        $parts['title'] = 'Page not found';
    }
    return $parts;
} );

/* ------------------------------------------------------------------
 * Meta tags  (priority 5 = run before any later wp_head action)
 * ------------------------------------------------------------------ */

add_action( 'wp_head', function () {
    $description = dariobimbi_seo_description();
    $canonical   = dariobimbi_seo_canonical();
    $title       = wp_get_document_title();
    $site_name   = get_bloginfo( 'name' );
    $og          = dariobimbi_seo_og_image();
    $type        = ( is_singular() && ! is_front_page() ) ? 'article' : 'website';
    $robots      = ( is_404() || is_search() )
        ? 'noindex,follow'
        : 'index,follow,max-image-preview:large,max-snippet:-1';

    echo "\n<!-- SEO -->\n";
    printf( '<meta name="description" content="%s">' . "\n",  esc_attr( $description ) );
    printf( '<meta name="author" content="%s">' . "\n",       esc_attr( 'Dario Bimbi' ) );
    printf( '<meta name="robots" content="%s">' . "\n",       esc_attr( $robots ) );
    printf( '<link rel="canonical" href="%s">' . "\n",        esc_url( $canonical ) );

    // Open Graph
    printf( '<meta property="og:type" content="%s">' . "\n",          esc_attr( $type ) );
    printf( '<meta property="og:title" content="%s">' . "\n",         esc_attr( $title ) );
    printf( '<meta property="og:description" content="%s">' . "\n",   esc_attr( $description ) );
    printf( '<meta property="og:url" content="%s">' . "\n",           esc_url( $canonical ) );
    printf( '<meta property="og:site_name" content="%s">' . "\n",     esc_attr( $site_name ) );
    printf( '<meta property="og:locale" content="%s">' . "\n",        esc_attr( get_locale() ) );
    printf( '<meta property="og:image" content="%s">' . "\n",         esc_url( $og['url'] ) );
    if ( $og['width'] && $og['height'] ) {
        printf( '<meta property="og:image:width" content="%d">' . "\n",  $og['width'] );
        printf( '<meta property="og:image:height" content="%d">' . "\n", $og['height'] );
    }
    printf( '<meta property="og:image:alt" content="%s">' . "\n",     esc_attr( 'Dario Bimbi, Web Developer' ) );

    // Twitter Card (no handle — Dario isn't on Twitter as a creator)
    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
    printf( '<meta name="twitter:title" content="%s">' . "\n",        esc_attr( $title ) );
    printf( '<meta name="twitter:description" content="%s">' . "\n",  esc_attr( $description ) );
    printf( '<meta name="twitter:image" content="%s">' . "\n",        esc_url( $og['url'] ) );
    printf( '<meta name="twitter:image:alt" content="%s">' . "\n",    esc_attr( 'Dario Bimbi, Web Developer' ) );

    // Browser-chrome hints
    echo '<meta name="theme-color" content="#0a0e1a">' . "\n";
    echo '<meta name="color-scheme" content="dark light">' . "\n";

    // Sitemap discovery (XML one for crawlers; the human one is /sitemap/)
    printf( '<link rel="sitemap" type="application/xml" title="Sitemap" href="%s">' . "\n",
        esc_url( home_url( '/sitemap.xml' ) )
    );

    echo "<!-- /SEO -->\n";
}, 5 );

/* ------------------------------------------------------------------
 * JSON-LD structured data
 * ------------------------------------------------------------------ */

add_action( 'wp_head', function () {
    $home    = home_url( '/' );
    $logoUrl = get_stylesheet_directory_uri() . DARIOBIMBI_LOGO;
    $flags   = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT;

    if ( is_front_page() ) {
        $person = [
            '@context'   => 'https://schema.org',
            '@type'      => 'Person',
            '@id'        => $home . '#dario',
            'name'       => 'Dario Bimbi',
            'url'        => $home,
            'image'      => $logoUrl,
            'jobTitle'   => 'Web Developer II',
            'worksFor'   => [
                '@type' => 'Organization',
                'name'  => 'Clicks Web Design Inc.',
                'url'   => 'https://clickswebdesign.com',
            ],
            'sameAs'     => [ DARIOBIMBI_LINKEDIN ],
            'knowsAbout' => [
                'WordPress', 'PHP', 'JavaScript', 'CSS', 'HTML',
                'Web accessibility', 'Block themes', 'Front-end development',
                'WooCommerce', 'MySQL',
            ],
        ];

        $website = [
            '@context'        => 'https://schema.org',
            '@type'           => 'WebSite',
            '@id'             => $home . '#website',
            'name'            => get_bloginfo( 'name' ),
            'alternateName'   => 'Dario Bimbi Portfolio',
            'url'             => $home,
            'description'     => DARIOBIMBI_BIO,
            'inLanguage'      => str_replace( '_', '-', get_locale() ),
            'publisher'       => [ '@id' => $home . '#dario' ],
            'potentialAction' => [
                '@type'       => 'SearchAction',
                'target'      => [
                    '@type'       => 'EntryPoint',
                    'urlTemplate' => $home . '?s={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];

        echo "<script type=\"application/ld+json\">" . wp_json_encode( $person,  $flags ) . "</script>\n";
        echo "<script type=\"application/ld+json\">" . wp_json_encode( $website, $flags ) . "</script>\n";
        return;
    }

    if ( is_singular() ) {
        $page = [
            '@context'    => 'https://schema.org',
            '@type'       => 'WebPage',
            'name'        => wp_get_document_title(),
            'description' => dariobimbi_seo_description(),
            'url'         => dariobimbi_seo_canonical(),
            'inLanguage'  => str_replace( '_', '-', get_locale() ),
            'isPartOf'    => [ '@id' => $home . '#website' ],
            'about'       => [ '@id' => $home . '#dario' ],
        ];

        $crumbs = [
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => [
                [ '@type' => 'ListItem', 'position' => 1, 'name' => 'Home',           'item' => $home ],
                [ '@type' => 'ListItem', 'position' => 2, 'name' => get_the_title(),  'item' => get_permalink() ],
            ],
        ];

        echo "<script type=\"application/ld+json\">" . wp_json_encode( $page,   $flags ) . "</script>\n";
        echo "<script type=\"application/ld+json\">" . wp_json_encode( $crumbs, $flags ) . "</script>\n";
    }
}, 6 );

/* ------------------------------------------------------------------
 * Defer the theme JS so it doesn't block first paint
 * ------------------------------------------------------------------ */

add_filter( 'script_loader_tag', function ( $tag, $handle ) {
    if ( $handle === 'dariobimbi-script' ) {
        return str_replace( ' src=', ' defer src=', $tag );
    }
    return $tag;
}, 10, 2 );

/* ------------------------------------------------------------------
 * Strip legacy <head> noise
 *  - rsd_link / wlwmanifest_link: pre-2010 blog-client autodiscovery
 *  - wp_generator: leaks the WP version string
 *  - wp_shortlink_wp_head: confuses crawlers when canonical is set
 * ------------------------------------------------------------------ */

remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'wp_generator' );
remove_action( 'wp_head', 'wp_shortlink_wp_head' );
