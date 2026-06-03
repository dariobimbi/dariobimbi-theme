<?php
/**
 * Demos — shared categorization.
 *
 * The Demos hub (patterns/demos-hub.php) and the header nav dropdown
 * (patterns/header-nav.php) both group demos into web / game / ported. They
 * used to keep their own slug lists and drifted: Ant Buster was a "game" on
 * the hub but fell through to "web" in the nav. This is the single source now —
 * change a demo's grouping here and both follow.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Category for a demo child-page slug.
 *
 * Returns one of 'web', 'game', or 'ported'. Everything is a web demo by
 * default; only the exceptions are listed, so a new demo needs an entry here
 * only if it is a game or an old ported sketch.
 *
 * @param string $slug The demo page's post_name.
 * @return string
 */
function dariobimbi_demo_category( $slug ) {
    $exceptions = [
        'ant-buster' => 'game',   // built from scratch, just for fun
        'pug-attack' => 'ported', // old DePaul Processing sketch, hand-ported
        'microgame'  => 'ported', // ditto
    ];
    return isset( $exceptions[ $slug ] ) ? $exceptions[ $slug ] : 'web';
}

/**
 * Canonical category order plus the short titles the header nav shows for each.
 *
 * The hub renders these same three categories in this order (with its own
 * richer eyebrow + lead copy in patterns/demos-hub.php). A new category should
 * be added here first so the nav and hub stay in the same order.
 *
 * @return array<string,string> category slug => nav section title
 */
function dariobimbi_demo_nav_sections() {
    return [
        'web'    => 'Web demos',
        'game'   => 'Games',
        'ported' => 'Old games',
    ];
}
