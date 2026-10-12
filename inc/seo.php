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

/* Employer + alma mater + locality used by the JSON-LD Person/Organization
   blocks. These are public-facing facts already on the site copy; the
   address is the metro the bio mentions, not a street. */
const DARIOBIMBI_EMPLOYER_NAME = 'Clicks Web Design Inc.';
const DARIOBIMBI_EMPLOYER_URL  = 'https://clickswebdesign.com';
const DARIOBIMBI_ALMA_NAME     = 'DePaul University';
const DARIOBIMBI_ALMA_URL      = 'https://www.depaul.edu/';
const DARIOBIMBI_LOCALITY      = 'Chicago';
const DARIOBIMBI_REGION        = 'IL';
const DARIOBIMBI_COUNTRY       = 'US';

/* Identify a "game" project page so the JSON-LD can emit VideoGame instead
   of generic CreativeWork. Templates that aren't in this list fall through
   to CreativeWork. Add slugs here as new game projects ship. */
const DARIOBIMBI_GAME_PROJECTS = [ 'space-dwarves', 'lost-probe', 'one-robot-army' ];

/* Certifications. Feeds both the card row in patterns/skills.php and the
   Person node's hasCredential. Badges live in assets/certs/ at 192px. */
const DARIOBIMBI_CERTS = [
    [ 'name' => 'Google Analytics Certification', 'issuer' => 'Skillshop', 'issued' => '2026-10-10', 'expires' => '2027-10-10', 'badge' => 'google-analytics.png', 'url' => 'https://www.credential.net/e90b19b7-1b64-484a-9ce8-9bcee6edd24c' ],
];

/* ------------------------------------------------------------------
 * Helpers
 * ------------------------------------------------------------------ */

/** Certs still in date. A lapsed one drops off rather than sitting there looking current. */
function dariobimbi_certifications(): array {
    return array_values( array_filter( DARIOBIMBI_CERTS, fn( $c ) => empty( $c['expires'] ) || strtotime( $c['expires'] ) > time() ) );
}

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

/**
 * Absolute URL to the OG image plus its real pixel dimensions.
 *
 * Singular pages with a featured image use that image, so a project or demo
 * shared on LinkedIn shows its own screenshot instead of the generic site
 * banner. Everything else falls back to assets/banner.png.
 */
function dariobimbi_seo_og_image(): array {
    if ( is_singular() && has_post_thumbnail() ) {
        $thumb_id = get_post_thumbnail_id();
        $src      = $thumb_id ? wp_get_attachment_image_src( $thumb_id, 'full' ) : false;
        if ( $src && ! empty( $src[0] ) ) {
            return [
                'url'    => (string) $src[0],
                'width'  => (int) ( $src[1] ?? 0 ),
                'height' => (int) ( $src[2] ?? 0 ),
            ];
        }
    }

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

/**
 * If the current request is a project page, return its slug + Schema.org
 * type. Returns null on non-project pages so callers can short-circuit.
 *
 * Detection is by custom-template name: pages assigned a `page-project-*`
 * template are projects, the slug is whatever follows the prefix, and
 * known game slugs map to VideoGame instead of generic CreativeWork.
 */
function dariobimbi_seo_project_meta(): ?array {
    if ( ! is_singular() ) {
        return null;
    }
    $template = get_page_template_slug();
    if ( ! $template || strpos( $template, 'page-project-' ) !== 0 ) {
        return null;
    }
    $slug = substr( $template, strlen( 'page-project-' ) );
    $type = in_array( $slug, DARIOBIMBI_GAME_PROJECTS, true ) ? 'VideoGame' : 'CreativeWork';
    return [ 'slug' => $slug, 'type' => $type ];
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
    /* og:image:type lets crawlers skip a HEAD probe for the MIME type. */
    $og_ext  = strtolower( (string) pathinfo( wp_parse_url( $og['url'], PHP_URL_PATH ) ?: '', PATHINFO_EXTENSION ) );
    $og_mime = [ 'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'webp' => 'image/webp', 'gif' => 'image/gif' ][ $og_ext ] ?? '';
    if ( $og_mime ) {
        printf( '<meta property="og:image:type" content="%s">' . "\n", esc_attr( $og_mime ) );
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

    /* IndieWeb / Mastodon identity verification: rel="me" links the site to
       the off-site profile, and any rel=me on that profile pointing back
       confirms ownership. Free and standards-based. */
    printf( '<link rel="me" href="%s">' . "\n", esc_url( DARIOBIMBI_LINKEDIN ) );

    echo "<!-- /SEO -->\n";
}, 5 );

/* ------------------------------------------------------------------
 * JSON-LD structured data
 * ------------------------------------------------------------------ */

add_action( 'wp_head', function () {
    $home     = home_url( '/' );
    $logoUrl  = get_stylesheet_directory_uri() . DARIOBIMBI_LOGO;
    $flags    = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT;
    $org_id   = DARIOBIMBI_EMPLOYER_URL . '#org';
    $person_id = $home . '#dario';
    $site_id   = $home . '#website';

    /* Person + employer Organization are emitted on every page so the
       knowledge graph stays consistent across the site, not just the home
       page. Inner pages reference them by @id. */
    $organization = [
        '@context' => 'https://schema.org',
        '@type'    => 'Organization',
        '@id'      => $org_id,
        'name'     => DARIOBIMBI_EMPLOYER_NAME,
        'url'      => DARIOBIMBI_EMPLOYER_URL,
    ];

    $person = [
        '@context'   => 'https://schema.org',
        '@type'      => 'Person',
        '@id'        => $person_id,
        'name'       => 'Dario Bimbi',
        'url'        => $home,
        'image'      => $logoUrl,
        'jobTitle'   => 'Web Developer II',
        'description'=> DARIOBIMBI_BIO,
        'worksFor'   => [ '@id' => $org_id ],
        'alumniOf'   => [
            '@type' => 'CollegeOrUniversity',
            'name'  => DARIOBIMBI_ALMA_NAME,
            'url'   => DARIOBIMBI_ALMA_URL,
        ],
        'address'    => [
            '@type'           => 'PostalAddress',
            'addressLocality' => DARIOBIMBI_LOCALITY,
            'addressRegion'   => DARIOBIMBI_REGION,
            'addressCountry'  => DARIOBIMBI_COUNTRY,
        ],
        'contactPoint' => [
            '@type'       => 'ContactPoint',
            'contactType' => 'Inquiries',
            'url'         => $home . '#contact',
        ],
        'sameAs'     => [ DARIOBIMBI_LINKEDIN ],
        'knowsAbout' => [
            'WordPress', 'PHP', 'JavaScript', 'CSS', 'HTML',
            'Web accessibility', 'Block themes', 'Front-end development',
            'WooCommerce', 'MySQL',
        ],
    ];
    $certs = dariobimbi_certifications();
    if ( $certs ) {
        $person['hasCredential'] = array_map( fn( $c ) => [
            '@type'              => 'EducationalOccupationalCredential',
            'name'               => $c['name'],
            'credentialCategory' => 'certificate',
            'url'                => $c['url'],
            'dateCreated'        => $c['issued'],
            'expires'            => $c['expires'],
            'recognizedBy'       => [ '@type' => 'Organization', 'name' => $c['issuer'] ],
        ], $certs );
    }

    $website = [
        '@context'        => 'https://schema.org',
        '@type'           => 'WebSite',
        '@id'             => $site_id,
        'name'            => get_bloginfo( 'name' ),
        'alternateName'   => 'Dario Bimbi Portfolio',
        'url'             => $home,
        'description'     => DARIOBIMBI_BIO,
        'inLanguage'      => str_replace( '_', '-', get_locale() ),
        'publisher'       => [ '@id' => $person_id ],
        'potentialAction' => [
            '@type'       => 'SearchAction',
            'target'      => [
                '@type'       => 'EntryPoint',
                'urlTemplate' => $home . '?s={search_term_string}',
            ],
            'query-input' => 'required name=search_term_string',
        ],
    ];

    if ( is_front_page() ) {
        /* ProfilePage is the canonical "this page is about a person" type;
           Google has dedicated rich-result handling for it. mainEntity links
           it to the Person node defined above. */
        $profile = [
            '@context'   => 'https://schema.org',
            '@type'      => 'ProfilePage',
            '@id'        => $home . '#profile',
            'url'        => $home,
            'name'       => wp_get_document_title(),
            'description'=> DARIOBIMBI_BIO,
            'inLanguage' => str_replace( '_', '-', get_locale() ),
            'isPartOf'   => [ '@id' => $site_id ],
            'mainEntity' => [ '@id' => $person_id ],
        ];

        echo "<script type=\"application/ld+json\">" . wp_json_encode( $profile,      $flags ) . "</script>\n";
        echo "<script type=\"application/ld+json\">" . wp_json_encode( $person,       $flags ) . "</script>\n";
        echo "<script type=\"application/ld+json\">" . wp_json_encode( $organization, $flags ) . "</script>\n";
        echo "<script type=\"application/ld+json\">" . wp_json_encode( $website,      $flags ) . "</script>\n";
        return;
    }

    if ( is_singular() ) {
        $page_url   = dariobimbi_seo_canonical();
        $page_title = wp_get_document_title();
        $page_desc  = dariobimbi_seo_description();

        $page = [
            '@context'    => 'https://schema.org',
            '@type'       => 'WebPage',
            'name'        => $page_title,
            'description' => $page_desc,
            'url'         => $page_url,
            'inLanguage'  => str_replace( '_', '-', get_locale() ),
            'isPartOf'    => [ '@id' => $site_id ],
            'about'       => [ '@id' => $person_id ],
        ];

        $crumbs_items = [
            [ '@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => $home ],
        ];

        /* If the page sits under a parent (Demos / Projects), insert that
           parent as the second crumb so the breadcrumb trail matches the
           actual URL nesting. */
        $parent_id = (int) wp_get_post_parent_id( get_the_ID() );
        if ( $parent_id ) {
            $crumbs_items[] = [
                '@type'    => 'ListItem',
                'position' => count( $crumbs_items ) + 1,
                'name'     => get_the_title( $parent_id ),
                'item'     => get_permalink( $parent_id ),
            ];
        }
        $crumbs_items[] = [
            '@type'    => 'ListItem',
            'position' => count( $crumbs_items ) + 1,
            'name'     => get_the_title(),
            'item'     => get_permalink(),
        ];

        $crumbs = [
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $crumbs_items,
        ];

        echo "<script type=\"application/ld+json\">" . wp_json_encode( $page,   $flags ) . "</script>\n";
        echo "<script type=\"application/ld+json\">" . wp_json_encode( $crumbs, $flags ) . "</script>\n";

        /* Project pages get a CreativeWork (or VideoGame) node so each piece
           of work is its own indexable entity authored by Dario, rather
           than a generic WebPage. The featured image, when present, doubles
           as the work's representative image. */
        $project = dariobimbi_seo_project_meta();
        if ( $project ) {
            $og = dariobimbi_seo_og_image();
            $work = [
                '@context'    => 'https://schema.org',
                '@type'       => $project['type'],
                '@id'         => $page_url . '#work',
                'name'        => get_the_title(),
                'headline'    => get_the_title(),
                'description' => $page_desc,
                'url'         => $page_url,
                'inLanguage'  => str_replace( '_', '-', get_locale() ),
                'author'      => [ '@id' => $person_id ],
                'creator'     => [ '@id' => $person_id ],
                'mainEntityOfPage' => $page_url,
            ];
            if ( ! empty( $og['url'] ) ) {
                $work['image'] = $og['url'];
            }
            echo "<script type=\"application/ld+json\">" . wp_json_encode( $work, $flags ) . "</script>\n";
        }

        /* Person + Organization on inner pages too: gives every page a full
           identity graph, not just the homepage. Cheap to emit, and it's
           what Google's structured-data tooling expects. */
        echo "<script type=\"application/ld+json\">" . wp_json_encode( $person,       $flags ) . "</script>\n";
        echo "<script type=\"application/ld+json\">" . wp_json_encode( $organization, $flags ) . "</script>\n";
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
