<?php
/**
 * Auto-provision Pages for theme demos and projects.
 *
 * The header nav (patterns/header-nav.php) and the sitemap pattern query
 * the database for the child Pages of "demos" and "projects". Hand-creating
 * those Pages for every demo on disk is fragile (the production site has
 * shipped with the wrong slug more than once). This walks the
 * customTemplates list in theme.json and ensures a Page exists for every
 * page-demo-* and page-project-* template. The slug is the template name
 * with the page-demo- / page-project- prefix stripped, and the page title
 * is taken from the customTemplates label minus its "Demo — " / "Project — "
 * prefix. Existing Pages keep their title and content untouched; only the
 * assigned template is reconciled.
 *
 * Cached against the theme version: once the run for version X has
 * succeeded, it does not run again until the version bumps. Adding a new
 * demo on disk requires bumping the theme Version anyway (so the pattern
 * cache picks the new file up — see CLAUDE.md), so the new Page lands the
 * first time an admin loads any admin screen after the bump.
 *
 * Note: this never deletes Pages. If a Page exists with a slug that does
 * not match any current customTemplates entry (e.g. a renamed-but-not-
 * cleaned-up demo), it stays in the DB and shows up in the nav until you
 * trash it manually.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'admin_init', function () {
    if ( ! current_user_can( 'edit_pages' ) ) {
        return;
    }

    $theme_version = wp_get_theme()->get( 'Version' );
    $cached        = get_option( 'dariobimbi_auto_pages_version' );
    if ( $cached === $theme_version ) {
        return;
    }

    $theme_json_path = get_stylesheet_directory() . '/theme.json';
    if ( ! file_exists( $theme_json_path ) ) {
        return;
    }
    $theme_json = json_decode( file_get_contents( $theme_json_path ), true );
    $templates  = $theme_json['customTemplates'] ?? [];
    if ( empty( $templates ) ) {
        return;
    }

    /* Make sure the two parent pages exist. WP's template hierarchy will
       auto-pick page-demos.html and page-projects.html from the slug, so no
       explicit template assignment is needed here. */
    $demos_id    = dariobimbi_ensure_page( 'demos',    'Demos',    0, '' );
    $projects_id = dariobimbi_ensure_page( 'projects', 'Projects', 0, '' );

    foreach ( $templates as $tpl ) {
        $name  = $tpl['name']  ?? '';
        $label = $tpl['title'] ?? '';

        if ( preg_match( '/^page-demo-(.+)$/', $name, $m ) ) {
            $parent_id = $demos_id;
            $slug      = $m[1];
            $prefix_re = '/^Demo\s*[\x{2014}\x{2013}\-]\s*/u';
            $is_project = false;
        } elseif ( preg_match( '/^page-project-(.+)$/', $name, $m ) ) {
            $parent_id = $projects_id;
            $slug      = $m[1];
            $prefix_re = '/^Project\s*[\x{2014}\x{2013}\-]\s*/u';
            $is_project = true;
        } else {
            continue;
        }

        $title = preg_replace( $prefix_re, '', $label );
        if ( ! $title || $title === $label ) {
            $title = ucwords( str_replace( '-', ' ', $slug ) );
        }

        $page_id = dariobimbi_ensure_page( $slug, $title, $parent_id, $name );

        /* For project pages, side-load the per-project banner.png into the
           Media Library and set it as the Featured Image. The image goes
           into the SEO/sitemap pipeline automatically (see inc/seo.php
           dariobimbi_seo_og_image and inc/sitemap.php). Skipped if the
           page already has a Featured Image, so a manual override always
           wins. */
        if ( $is_project && $page_id && current_user_can( 'upload_files' ) ) {
            $banner = get_stylesheet_directory() . '/assets/projects/' . $slug . '/banner.png';
            dariobimbi_set_banner_as_featured( $page_id, $banner );
        }
    }

    update_option( 'dariobimbi_auto_pages_version', $theme_version, false );
} );

/**
 * Side-load a banner PNG from the theme's assets directory into the Media
 * Library and set it as the Featured Image on the given page. No-op if the
 * page already has a Featured Image, the file is missing, or the user
 * cannot upload files. Returns the attachment ID, or 0 on no-op / failure.
 *
 * The attachment is tagged with post meta `_dariobimbi_banner_for` =
 * <page_id> so a future re-run can find the existing attachment instead of
 * creating duplicates if the banner ever needs to be refreshed.
 */
function dariobimbi_set_banner_as_featured( int $page_id, string $source_path ): int {
    if ( ! file_exists( $source_path ) ) {
        return 0;
    }
    if ( has_post_thumbnail( $page_id ) ) {
        return 0;
    }

    /* Look for a previously side-loaded attachment for this page. If we
       already imported the banner once and the user later cleared the
       Featured Image, re-attach the existing attachment rather than
       making a duplicate copy in the Media Library. */
    $existing = get_posts( [
        'post_type'      => 'attachment',
        'post_status'    => 'inherit',
        'posts_per_page' => 1,
        'meta_key'       => '_dariobimbi_banner_for',
        'meta_value'     => (string) $page_id,
        'fields'         => 'ids',
    ] );
    if ( ! empty( $existing ) ) {
        set_post_thumbnail( $page_id, (int) $existing[0] );
        return (int) $existing[0];
    }

    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';

    $upload   = wp_upload_dir();
    if ( ! empty( $upload['error'] ) || empty( $upload['path'] ) ) {
        return 0;
    }
    if ( ! wp_mkdir_p( $upload['path'] ) ) {
        return 0;
    }

    $filename = wp_unique_filename( $upload['path'], basename( $source_path ) );
    $dest     = trailingslashit( $upload['path'] ) . $filename;

    if ( ! @copy( $source_path, $dest ) ) {
        return 0;
    }

    $filetype = wp_check_filetype( $filename, null );
    $att_id   = wp_insert_attachment(
        [
            'guid'           => trailingslashit( $upload['url'] ) . $filename,
            'post_mime_type' => $filetype['type'] ?: 'image/png',
            'post_title'     => sanitize_text_field( pathinfo( $filename, PATHINFO_FILENAME ) ),
            'post_content'   => '',
            'post_status'    => 'inherit',
        ],
        $dest,
        $page_id
    );

    if ( is_wp_error( $att_id ) || ! $att_id ) {
        @unlink( $dest );
        return 0;
    }

    $metadata = wp_generate_attachment_metadata( $att_id, $dest );
    wp_update_attachment_metadata( $att_id, $metadata );

    update_post_meta( $att_id, '_dariobimbi_banner_for', (string) $page_id );
    update_post_meta( $att_id, '_wp_attachment_image_alt', sprintf( '%s — project banner', get_the_title( $page_id ) ) );

    set_post_thumbnail( $page_id, (int) $att_id );

    return (int) $att_id;
}

/**
 * Ensure a Page exists with the given slug under the given parent. Only the
 * assigned template is reconciled when the Page already exists, so any
 * admin edits to title or content survive. Returns the Page ID, or 0 on
 * failure.
 */
function dariobimbi_ensure_page( $slug, $title, $parent_id, $template ) {
    $path = $parent_id
        ? trim( (string) get_post_field( 'post_name', $parent_id ), '/' ) . '/' . $slug
        : $slug;

    $existing = get_page_by_path( $path );

    if ( $existing && $existing->post_type === 'page' ) {
        if ( $template ) {
            $current_tpl = get_page_template_slug( $existing->ID );
            if ( $current_tpl !== $template ) {
                update_post_meta( $existing->ID, '_wp_page_template', $template );
            }
        }
        return (int) $existing->ID;
    }

    $id = wp_insert_post( [
        'post_type'    => 'page',
        'post_status'  => 'publish',
        'post_title'   => $title,
        'post_name'    => $slug,
        'post_parent'  => (int) $parent_id,
        'post_content' => '',
    ], true );

    if ( is_wp_error( $id ) || ! $id ) {
        return 0;
    }

    if ( $template ) {
        update_post_meta( $id, '_wp_page_template', $template );
    }

    return (int) $id;
}
