<?php
/**
 * Title: Sitemap
 * Slug: dariobimbi/sitemap
 * Categories: dariobimbi
 * Description: Human-readable sitemap. Lists every section of the homepage plus any standalone pages and standard endpoints. (The XML sitemap WordPress auto-generates at /wp-sitemap.xml is for crawlers — this one is for people.)
 *
 * To use: create a Page in admin with slug "sitemap" — WP will auto-pick the
 * page-sitemap.html template, which renders this pattern. The Page itself can
 * be empty.
 *
 * The Pages list is queried at render time so any new Page you publish shows
 * up automatically; nothing here needs editing when you add or rename pages.
 */
$home    = home_url( '/' );
$xml_url = home_url( '/sitemap.xml' );

/* Pull every published Page except the sitemap itself, then bucket into a
   parent => children[] tree so the renderer can show top-level pages with
   their child pages nested underneath. No parent filter on the query — we
   build the hierarchy here so anything new shows up automatically. */
$all_pages = get_pages( [
    'sort_column' => 'menu_order,post_title',
    'exclude'     => [ get_queried_object_id() ],
] );

$children_by_parent = [];
foreach ( $all_pages as $p ) {
    $children_by_parent[ (int) $p->post_parent ][] = $p;
}
$top_level_pages = $children_by_parent[0] ?? [];

$sections = [
    [ 'about',       'About',       'Ten years on the web, a short bio, and the quick facts.' ],
    [ 'experience',  'Experience',  'Where I have worked and what I built there.' ],
    [ 'projects',    'Work',        'Selected projects, with notes on what I built.' ],
    [ 'skills',      'Skills',      'Languages, frameworks, and tools I use day to day.' ],
    [ 'contact',     'Contact',     'Email, LinkedIn, and the usual ways to reach me.' ],
];
?>
<!-- wp:group {"align":"full","className":"content-page","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull content-page" style="padding-top:var(--wp--preset--spacing--70);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--70);padding-left:var(--wp--preset--spacing--30)">

    <!-- wp:paragraph {"className":"eyebrow"} -->
    <p class="eyebrow">find your way around</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"level":1,"className":"has-gradient-text","style":{"spacing":{"margin":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20"}}}} -->
    <h1 class="wp-block-heading has-gradient-text" style="margin-top:var(--wp--preset--spacing--20);margin-bottom:var(--wp--preset--spacing--20)">Sitemap</h1>
    <!-- /wp:heading -->

    <!-- wp:paragraph {"textColor":"muted","style":{"typography":{"lineHeight":"1.55"},"spacing":{"margin":{"bottom":"var:preset|spacing|50"}}}} -->
    <p class="has-muted-color has-text-color" style="line-height:1.55;margin-bottom:var(--wp--preset--spacing--50)">This is a list of everything on the site, in one place. If you would rather have the machine-readable XML version, it lives at <a href="<?php echo esc_url( $xml_url ); ?>"><code>/sitemap.xml</code></a>.</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"level":2,"style":{"spacing":{"margin":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|30"}}}} -->
    <h2 class="wp-block-heading" style="margin-top:var(--wp--preset--spacing--40);margin-bottom:var(--wp--preset--spacing--30)">Homepage sections</h2>
    <!-- /wp:heading -->

    <!-- wp:html -->
    <ul class="sitemap-list">
        <?php foreach ( $sections as $s ) : list( $id, $label, $desc ) = $s; ?>
        <li>
            <a href="<?php echo esc_url( $home . '#' . $id ); ?>"><?php echo esc_html( $label ); ?></a>
            <span class="sitemap-desc"><?php echo esc_html( $desc ); ?></span>
        </li>
        <?php endforeach; ?>
    </ul>
    <!-- /wp:html -->

    <?php if ( ! empty( $top_level_pages ) ) : ?>
    <!-- wp:heading {"level":2,"style":{"spacing":{"margin":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|30"}}}} -->
    <h2 class="wp-block-heading" style="margin-top:var(--wp--preset--spacing--60);margin-bottom:var(--wp--preset--spacing--30)">Pages</h2>
    <!-- /wp:heading -->

    <!-- wp:html -->
    <ul class="sitemap-list">
        <?php foreach ( $top_level_pages as $parent_page ) : ?>
        <li>
            <a href="<?php echo esc_url( get_permalink( $parent_page ) ); ?>"><?php echo esc_html( get_the_title( $parent_page ) ); ?></a>
            <?php $kids = $children_by_parent[ $parent_page->ID ] ?? []; ?>
            <?php if ( ! empty( $kids ) ) : ?>
            <ul class="sitemap-sublist">
                <?php foreach ( $kids as $kid ) : ?>
                <li>
                    <a href="<?php echo esc_url( get_permalink( $kid ) ); ?>"><?php echo esc_html( get_the_title( $kid ) ); ?></a>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </li>
        <?php endforeach; ?>
    </ul>
    <!-- /wp:html -->
    <?php endif; ?>

    <!-- wp:heading {"level":2,"style":{"spacing":{"margin":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|30"}}}} -->
    <h2 class="wp-block-heading" style="margin-top:var(--wp--preset--spacing--60);margin-bottom:var(--wp--preset--spacing--30)">For machines</h2>
    <!-- /wp:heading -->

    <!-- wp:html -->
    <ul class="sitemap-list">
        <li>
            <a href="<?php echo esc_url( $xml_url ); ?>"><code>/sitemap.xml</code></a>
            <span class="sitemap-desc">XML sitemap for search-engine crawlers. Lists every published page on the site.</span>
        </li>
        <li>
            <a href="<?php echo esc_url( home_url( '/feed/' ) ); ?>"><code>/feed/</code></a>
            <span class="sitemap-desc">RSS feed of any blog posts (currently empty, since I have not really gotten into blogging yet).</span>
        </li>
        <li>
            <a href="<?php echo esc_url( home_url( '/robots.txt' ) ); ?>"><code>/robots.txt</code></a>
            <span class="sitemap-desc">Crawler rules.</span>
        </li>
    </ul>
    <!-- /wp:html -->

</div>
<!-- /wp:group -->
