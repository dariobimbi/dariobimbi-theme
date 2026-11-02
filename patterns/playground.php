<?php
/**
 * Title: Playground
 * Slug: dariobimbi/playground
 * Categories: dariobimbi
 * Description: Homepage showcase of a few hand-picked live demos, reusing the .demo-card component from the demos hub. Each card links to a playable demo page; cards for unpublished pages are skipped so a draft never ships a 404.
 */

/*
 * Curated lineup. `path` is resolved against the published page tree below,
 * and anything not published is silently dropped. Tag, blurb, tech, badge and
 * the `preview` key for the CSS-only motion sketch come from
 * dariobimbi_demo_meta() in inc/demos.php (shared with the demos hub); only the
 * title, accent and call to action are specific to this showcase.
 * Accents stick to the warm palette (pink / yellow); no cool blue/purple here.
 */
$picks = [
    [ 'path' => 'demos/ant-buster',   'title' => 'Ant Buster',    'accent' => 'yellow', 'cta' => 'Play it' ],
    [ 'path' => 'demos/pokedex',      'title' => 'Pokédex',       'accent' => 'pink',   'cta' => 'Open it' ],
    [ 'path' => 'demos/prompt-studio', 'title' => 'Prompt Studio', 'accent' => 'yellow', 'cta' => 'Try it' ],
    [ 'path' => 'demos/card-browser', 'title' => 'Card Browser',  'accent' => 'pink',   'cta' => 'Open it' ],
];

/* Resolve to published pages only; drop the rest. */
$cards = [];
foreach ( $picks as $p ) {
    $page = get_page_by_path( $p['path'] );
    if ( $page && 'publish' === get_post_status( $page ) ) {
        $p        = array_merge( dariobimbi_demo_meta_for( $page->post_name ), $p );
        $p['url'] = get_permalink( $page );
        $cards[]  = $p;
    }
}

/* Nothing published yet → render nothing rather than an empty shell. */
if ( empty( $cards ) ) {
    return;
}

$demos_hub = get_page_by_path( 'demos' );
$demos_url = ( $demos_hub && 'publish' === get_post_status( $demos_hub ) ) ? get_permalink( $demos_hub ) : '';
?>
<!-- wp:group {"align":"full","className":"section section--surface","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"var:preset|spacing|30","right":"var:preset|spacing|30"},"blockGap":"var:preset|spacing|40"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull section section--surface" style="padding-top:var(--wp--preset--spacing--60);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--30)">

    <!-- wp:paragraph {"className":"eyebrow"} -->
    <p class="eyebrow">playground</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"className":"has-gradient-text","style":{"spacing":{"margin":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|30"}}}} -->
    <h2 class="wp-block-heading has-gradient-text" id="playground" style="margin-top:var(--wp--preset--spacing--20);margin-bottom:var(--wp--preset--spacing--30)">Things you can play with.</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph {"fontSize":"large","textColor":"muted","style":{"typography":{"lineHeight":"1.55"},"spacing":{"margin":{"bottom":"var:preset|spacing|40"}}}} -->
    <p class="has-muted-color has-text-color has-large-font-size" style="margin-bottom:var(--wp--preset--spacing--40);line-height:1.55">Small things I built for fun, usually to try out an idea or work through something I was curious about. They run right here in the browser, and each one is a single file you can read from top to bottom. Have a poke around.</p>
    <!-- /wp:paragraph -->

    <!-- wp:html -->
    <ul class="demos-grid alignwide">
        <?php foreach ( $cards as $c ) : ?>
        <li class="demo-card demo-card--<?php echo esc_attr( $c['accent'] ); ?>">
            <a class="demo-card-link" href="<?php echo esc_url( $c['url'] ); ?>" aria-labelledby="play-<?php echo esc_attr( $c['preview'] ); ?>-title">
                <div class="demo-card-preview demo-card-preview--<?php echo esc_attr( $c['preview'] ); ?>" aria-hidden="true">
                    <?php dariobimbi_demo_preview( $c['preview'] ); ?>
                </div>

                <div class="demo-card-body">
                    <div class="demo-card-meta">
                        <span class="demo-card-tag"><?php echo esc_html( $c['tag'] ); ?></span>
                        <?php if ( ! empty( $c['badge'] ) ) : ?>
                            <span class="demo-card-badge"><?php echo esc_html( $c['badge'] ); ?></span>
                        <?php endif; ?>
                    </div>

                    <h3 class="demo-card-title" id="play-<?php echo esc_attr( $c['preview'] ); ?>-title"><?php echo esc_html( $c['title'] ); ?></h3>

                    <p class="demo-card-blurb"><?php echo esc_html( $c['blurb'] ); ?></p>

                    <?php if ( ! empty( $c['tech'] ) ) : ?>
                        <ul class="demo-card-tech">
                            <?php foreach ( $c['tech'] as $t ) : ?>
                                <li><?php echo esc_html( $t ); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <span class="demo-card-cta"><?php echo esc_html( $c['cta'] ); ?> <span class="demo-card-arrow" aria-hidden="true">&rarr;</span></span>
                </div>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
    <!-- /wp:html -->

    <?php if ( $demos_url ) : ?>
    <!-- wp:buttons {"className":"demos-see-all-row","style":{"spacing":{"margin":{"top":"var:preset|spacing|40"}}}} -->
    <div class="wp-block-buttons demos-see-all-row" style="margin-top:var(--wp--preset--spacing--40)">
        <!-- wp:button {"className":"is-style-outline demos-see-all"} -->
        <div class="wp-block-button is-style-outline demos-see-all"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( $demos_url ); ?>">See all the demos</a></div>
        <!-- /wp:button -->
    </div>
    <!-- /wp:buttons -->
    <?php endif; ?>

</div>
<!-- /wp:group -->
