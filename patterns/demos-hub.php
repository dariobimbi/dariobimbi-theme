<?php
/**
 * Title: Demos Hub
 * Slug: dariobimbi/demos-hub
 * Categories: dariobimbi
 * Description: Landing page for /demos/. Auto-lists every child of the Demos page as a styled card. Per-slug metadata (tag, tech, preview, order) lives in dariobimbi_demo_meta() in inc/demos.php. When you publish a new child page, give it an entry there for the bespoke flair, otherwise it falls back to a tasteful default.
 *
 * The page itself (slug "demos") stays empty in admin — content lives here.
 * Template: page-demos.html (auto-picked from slug, also registered in
 * theme.json customTemplates so it shows in the dropdown).
 */

$children = dariobimbi_demo_children();

/* Rotating accent palette — each card picks one based on its position so a
   row alternates a warm pink / yellow rhythm without any per-card config.
   (The cool blue/purple accents were retired with the warm repalette.) */
$accents = [ 'pink', 'yellow' ];

/* Bucket children by category so we can render the sections in order
   (web demos, games I built, old games ported). Category comes from the
   shared dariobimbi_demo_category() so the nav dropdown matches exactly. */
$buckets = [ 'web' => [], 'game' => [], 'ported' => [] ];
foreach ( $children as $child ) {
    $cat = dariobimbi_demo_category( $child->post_name );
    if ( ! isset( $buckets[ $cat ] ) ) $buckets[ $cat ] = [];
    $buckets[ $cat ][] = $child;
}

/* Section labels + intro lines, used when rendering. Only sections with at
   least one child get rendered. */
$section_labels = [
    'web'  => [
        'eyebrow' => 'web dev demos',
        'title'   => 'Web demos',
        'lead'    => 'Small interactive things I built to try out a web idea or work through a problem I was curious about. Each one is a single PHP file you can open up and read from top to bottom.',
        'lab'     => 'A focus-trap explainer, a tiny SQL-style query builder, and a few other ideas. I have sketched them out, but they are not quite ready to ship yet. Check back soon, or <a href="' . esc_url( home_url( '/#contact' ) ) . '">tell me which one you would want first</a>.',
    ],
    'game' => [
        'eyebrow' => 'games',
        'title'   => 'Games',
        'lead'    => 'Games I built from scratch and put right in the browser, using nothing but JavaScript and a canvas.',
        'lab'     => 'I have a few more game ideas sitting in a notebook that I would like to build out next. They take a while, but I will add them here as they come together.',
    ],
    'ported' => [
        'eyebrow' => 'old games, ported',
        'title'   => 'Old games',
        'lead'    => 'Old Processing sketches I made in school, hand-ported to vanilla JavaScript and Canvas so they run in a browser without any plugins.',
        'lab'     => 'There are a few more sketches in an old DePaul folder I would like to port. They need a careful rewrite before they are playable in the browser, but I will add them as they come together.',
    ],
];
?>
<!-- wp:group {"align":"full","className":"section section--ambient demos-hub-hero","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|50","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull section section--ambient demos-hub-hero" style="padding-top:var(--wp--preset--spacing--70);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--30)">

    <!-- wp:paragraph {"align":"center","className":"eyebrow"} -->
    <p class="has-text-align-center eyebrow">things you can poke at</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"textAlign":"center","level":1,"className":"has-gradient-text","style":{"spacing":{"margin":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|30"}}}} -->
    <h1 class="wp-block-heading has-text-align-center has-gradient-text" style="margin-top:var(--wp--preset--spacing--20);margin-bottom:var(--wp--preset--spacing--30)">Demos.</h1>
    <!-- /wp:heading -->

    <!-- wp:paragraph {"align":"center","fontSize":"large","textColor":"muted","style":{"typography":{"lineHeight":"1.55"}}} -->
    <p class="has-text-align-center has-muted-color has-text-color has-large-font-size" style="line-height:1.55">Small, self-contained things I built to try out an idea or work through a problem I was curious about. There are no frameworks or build steps here, and none of the demos add any tracking. Each one is a single PHP file you can open up and read from top to bottom.</p>
    <!-- /wp:paragraph -->

</div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","className":"section section--surface","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|70","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull section section--surface" style="padding-top:var(--wp--preset--spacing--60);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--70);padding-left:var(--wp--preset--spacing--30)">

<!-- wp:html -->
<?php if ( empty( $children ) ) : ?>
    <p class="demos-hub-empty">No demos published yet. The Demos parent page has no children. Add a child page (and a matching <code>patterns/demo-&lt;slug&gt;.php</code>) and it will show up here.</p>
<?php else : ?>
    <?php foreach ( $section_labels as $cat => $sl ) :
        if ( empty( $buckets[ $cat ] ) ) continue;
    ?>
    <div class="demos-section demos-section--<?php echo esc_attr( $cat ); ?> alignwide">
        <p class="eyebrow"><?php echo esc_html( $sl['eyebrow'] ); ?></p>
        <h2 class="demos-section-title"><?php echo esc_html( $sl['title'] ); ?></h2>
        <p class="demos-section-lead"><?php echo esc_html( $sl['lead'] ); ?></p>
        <ul class="demos-grid">
            <?php foreach ( $buckets[ $cat ] as $i => $child ) :
                $slug = $child->post_name;
                $m    = dariobimbi_demo_meta_for( $slug );
                $accent = $accents[ $i % count( $accents ) ];
                $blurb  = $m['blurb'] !== ''
                    ? $m['blurb']
                    : ( $child->post_excerpt !== '' ? $child->post_excerpt : 'Open it up. The description lives in the demo itself.' );
            ?>
            <li class="demo-card demo-card--<?php echo esc_attr( $accent ); ?>">
            <a class="demo-card-link" href="<?php echo esc_url( get_permalink( $child ) ); ?>" aria-labelledby="demo-<?php echo esc_attr( $slug ); ?>-title">
                <div class="demo-card-preview demo-card-preview--<?php echo esc_attr( $m['preview'] ); ?>" aria-hidden="true">
                    <?php dariobimbi_demo_preview( $m['preview'] ); ?>
                </div>

                <div class="demo-card-body">
                    <div class="demo-card-meta">
                        <span class="demo-card-tag"><?php echo esc_html( $m['tag'] ); ?></span>
                        <?php if ( ! empty( $m['badge'] ) ) : ?>
                            <span class="demo-card-badge"><?php echo esc_html( $m['badge'] ); ?></span>
                        <?php endif; ?>
                    </div>

                    <h3 class="demo-card-title" id="demo-<?php echo esc_attr( $slug ); ?>-title"><?php echo esc_html( get_the_title( $child ) ); ?></h3>

                    <p class="demo-card-blurb"><?php echo esc_html( $blurb ); ?></p>

                    <?php if ( ! empty( $m['tech'] ) ) : ?>
                        <ul class="demo-card-tech">
                            <?php foreach ( $m['tech'] as $t ) : ?>
                                <li><?php echo esc_html( $t ); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <span class="demo-card-cta">Open demo <span class="demo-card-arrow" aria-hidden="true">→</span></span>
                </div>
            </a>
        </li>
            <?php endforeach; ?>

            <?php // "In the lab" tail card — section-specific copy, sits at the end of each grid. ?>
            <li class="demo-card demo-card--lab">
                <div class="demo-card-link demo-card-link--static">
                    <div class="demo-card-preview demo-card-preview--lab" aria-hidden="true">
                        <span class="cp-lab"></span>
                        <span class="cp-lab"></span>
                        <span class="cp-lab"></span>
                    </div>
                    <div class="demo-card-body">
                        <div class="demo-card-meta">
                            <span class="demo-card-tag">In the lab</span>
                        </div>
                        <h3 class="demo-card-title">More on the way</h3>
                        <p class="demo-card-blurb"><?php echo wp_kses( $sl['lab'], [ 'a' => [ 'href' => true ] ] ); ?></p>
                        <span class="demo-card-cta demo-card-cta--quiet">Cooking <span class="demo-card-arrow demo-card-arrow--bounce" aria-hidden="true">…</span></span>
                    </div>
                </div>
            </li>
        </ul>
    </div>
    <?php endforeach; ?>
<?php endif; ?>
<!-- /wp:html -->

</div>
<!-- /wp:group -->
