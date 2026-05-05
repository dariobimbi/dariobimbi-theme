<?php
/**
 * Render the per-project Featured Image as a hero banner at the top of
 * project pages.
 *
 * The Featured Image is auto-side-loaded by inc/auto-pages.php from the
 * banners under assets/projects/<slug>/banner.png. That puts the image
 * into the OG and image-sitemap pipelines, but a Featured Image is not
 * rendered in the page body unless something asks for it.
 *
 * The project templates (templates/page-project-*.html) invoke the project
 * pattern directly via <!-- wp:pattern --> — they never call the_content,
 * so a the_content filter would never fire here. We hook the pattern block
 * render instead: any time a `dariobimbi/project-*` pattern is rendered on
 * a singular page, we prepend the page's Featured Image to the output.
 *
 * Wrapped in a <figure class="project-banner"> so the existing site CSS
 * can target it directly without leaning on inline styles.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter( 'render_block_core/pattern', function ( $content, $block ) {
    if ( ! is_singular() ) {
        return $content;
    }

    $slug = $block['attrs']['slug'] ?? '';
    if ( strpos( $slug, 'dariobimbi/project-' ) !== 0 ) {
        return $content;
    }

    $project_slug = substr( $slug, strlen( 'dariobimbi/project-' ) );
    $page_id      = (int) get_the_ID();

    /* Prefer the wide hero variant (banner-hero.png) for in-page display.
       It's served straight from the theme directory so it doesn't have to
       round-trip through the Media Library, and the OG-card banner.png
       attachment stays untouched for social sharing. */
    $hero_path = get_stylesheet_directory()     . '/assets/projects/' . $project_slug . '/banner-hero.png';
    $hero_url  = get_stylesheet_directory_uri() . '/assets/projects/' . $project_slug . '/banner-hero.png';

    if ( file_exists( $hero_path ) ) {
        $size  = @getimagesize( $hero_path );
        $dims  = ( $size && $size[0] && $size[1] ) ? sprintf( ' width="%d" height="%d"', $size[0], $size[1] ) : '';
        $alt   = $page_id ? get_the_title( $page_id ) : '';
        $img   = sprintf(
            '<img src="%s" alt="%s" class="project-banner__img" loading="eager" fetchpriority="high" decoding="async"%s>',
            esc_url( $hero_url ),
            esc_attr( $alt ),
            $dims
        );
    } elseif ( $page_id && has_post_thumbnail( $page_id ) ) {
        /* Fallback: use the side-loaded OG banner from the Media Library if
           the project doesn't have a dedicated hero variant on disk yet. */
        $img = get_the_post_thumbnail(
            $page_id,
            'full',
            [
                'class'         => 'project-banner__img',
                'loading'       => 'eager',
                'fetchpriority' => 'high',
                'decoding'      => 'async',
            ]
        );
    } else {
        return $content;
    }

    if ( ! $img ) {
        return $content;
    }

    return '<figure class="project-banner alignfull">' . $img . '</figure>' . $content;
}, 10, 2 );
