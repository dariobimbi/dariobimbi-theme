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
        } elseif ( preg_match( '/^page-project-(.+)$/', $name, $m ) ) {
            $parent_id = $projects_id;
            $slug      = $m[1];
            $prefix_re = '/^Project\s*[\x{2014}\x{2013}\-]\s*/u';
        } else {
            continue;
        }

        $title = preg_replace( $prefix_re, '', $label );
        if ( ! $title || $title === $label ) {
            $title = ucwords( str_replace( '-', ' ', $slug ) );
        }

        dariobimbi_ensure_page( $slug, $title, $parent_id, $name );
    }

    update_option( 'dariobimbi_auto_pages_version', $theme_version, false );
} );

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
