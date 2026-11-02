<?php
/**
 * Title: Header Navigation
 * Slug: dariobimbi/header-nav
 * Categories: dariobimbi
 * Description: The site's primary navigation. Two dropdowns ("Projects" and "Demos") that auto-list every child page under their respective parents. No manual menu management. Privacy and Sitemap intentionally live in the footer only.
 *
 * Why a pattern (not a template part): template parts are HTML-only, but this
 * needs PHP to query each parent's children at render time. Patterns are
 * PHP-evaluated, so this lives here and is invoked from parts/header.html.
 *
 * Built on <details>/<summary>: zero JS for keyboard + screen-reader open/close,
 * plus a tiny outside-click handler at the bottom for the expected mouse UX.
 */

/* --- Projects parent + children -------------------------------------- */
$projects          = get_page_by_path( 'projects' );
$projects_url      = $projects ? get_permalink( $projects ) : home_url( '/projects/' );
$project_children  = $projects
    ? get_pages( [
        'parent'      => $projects->ID,
        'sort_column' => 'menu_order,post_title',
    ] )
    : [];

/* --- Demos parent + children ----------------------------------------- */
$demos          = get_page_by_path( 'demos' );
$demos_url      = $demos ? get_permalink( $demos ) : home_url( '/demos/' );
$demo_children  = dariobimbi_demo_children(); // meta order first, see inc/demos.php

/* Bucket demo children by shared category (web / game / ported) so the
   dropdown groupings always match the /demos/ hub. The categorization and the
   section titles both live in inc/demos.php — see dariobimbi_demo_category(). */
$nav_sections = dariobimbi_demo_nav_sections();
$nav_buckets  = array_fill_keys( array_keys( $nav_sections ), [] );
foreach ( $demo_children as $child ) {
    $cat = dariobimbi_demo_category( $child->post_name );
    if ( ! isset( $nav_buckets[ $cat ] ) ) {
        $nav_buckets[ $cat ] = [];
    }
    $nav_buckets[ $cat ][] = $child;
}
?>
<!-- wp:html -->
<nav class="site-nav" aria-label="Primary">

    <?php /* Projects: dropdown when there are child pages, otherwise a plain link to the hub. */ ?>
    <?php if ( ! empty( $project_children ) ) : ?>
        <details class="site-nav-dropdown">
            <summary class="site-nav-trigger">
                <span>Projects</span>
                <span class="site-nav-chevron" aria-hidden="true">▾</span>
            </summary>
            <ul class="site-nav-menu">
                <li>
                    <a href="<?php echo esc_url( $projects_url ); ?>" class="site-nav-menu-overview">All projects →</a>
                </li>
                <?php foreach ( $project_children as $child ) : ?>
                    <li>
                        <a href="<?php echo esc_url( get_permalink( $child ) ); ?>"><?php echo esc_html( get_the_title( $child ) ); ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </details>
    <?php else : ?>
        <a class="site-nav-trigger site-nav-trigger--link" href="<?php echo esc_url( $projects_url ); ?>">Projects</a>
    <?php endif; ?>

    <?php /* Demos: same shape as Projects, plus a Web/Old-games sub-grouping inside. */ ?>
    <?php if ( ! empty( $demo_children ) ) : ?>
        <details class="site-nav-dropdown">
            <summary class="site-nav-trigger">
                <span>Demos</span>
                <span class="site-nav-chevron" aria-hidden="true">▾</span>
            </summary>
            <ul class="site-nav-menu">
                <li>
                    <a href="<?php echo esc_url( $demos_url ); ?>" class="site-nav-menu-overview">All demos →</a>
                </li>
                <?php foreach ( $nav_sections as $cat => $section_title ) : ?>
                    <?php if ( empty( $nav_buckets[ $cat ] ) ) continue; ?>
                    <li class="site-nav-menu-heading"><?php echo esc_html( $section_title ); ?></li>
                    <?php foreach ( $nav_buckets[ $cat ] as $child ) : ?>
                        <li>
                            <a href="<?php echo esc_url( get_permalink( $child ) ); ?>"><?php echo esc_html( get_the_title( $child ) ); ?></a>
                        </li>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </ul>
        </details>
    <?php else : ?>
        <a class="site-nav-trigger site-nav-trigger--link" href="<?php echo esc_url( $demos_url ); ?>">Demos</a>
    <?php endif; ?>

</nav>
<script>
/* Close any open <details.site-nav-dropdown> when the user clicks outside it.
   Keyboard users get free Esc/blur handling from <details> itself. */
(function () {
    document.addEventListener('click', function (e) {
        document.querySelectorAll('details.site-nav-dropdown[open]').forEach(function (d) {
            if (!d.contains(e.target)) d.removeAttribute('open');
        });
    });
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        document.querySelectorAll('details.site-nav-dropdown[open]').forEach(function (d) {
            d.removeAttribute('open');
            d.querySelector('summary').focus();
        });
    });
})();
</script>
<!-- /wp:html -->
