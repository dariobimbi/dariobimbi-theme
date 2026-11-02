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

/**
 * Per-demo presentation data, keyed by child page slug.
 *
 * Shared by the Demos hub (patterns/demos-hub.php) and the homepage playground
 * (patterns/playground.php) so the blurbs, tags and tech lists live in one
 * place. `order` sets the position within a section: lower comes first, and a
 * demo with no entry here sorts last. Web demos are ranked by strength.
 *
 * @return array<string,array<string,mixed>>
 */
function dariobimbi_demo_meta() {
    return [
        'contrast' => [
            'order'   => 50,
            'tag'     => 'Accessibility',
            'preview' => 'contrast',
            'blurb'   => 'Pick any two colors and watch the WCAG ratio update live. Pass/fail tiles call out AA, AAA, and UI-component thresholds, and the URL updates so you can share a palette.',
            'tech'    => [ 'Vanilla JS', 'WCAG 2.1', 'Zero deps' ],
            'badge'   => '',
        ],
        'weather' => [
            'order'   => 40,
            'tag'     => 'Live API',
            'preview' => 'weather',
            'blurb'   => 'Two-step fetch chain against the Open-Meteo API: geocode the city, then pull a 7-day forecast. Pretty-printed JSON pane underneath shows the actual response. No key, no library, no proxy.',
            'tech'    => [ 'fetch()', 'async/await', 'Open-Meteo' ],
            'badge'   => '',
        ],
        'audio' => [
            'order'   => 70,
            'tag'     => 'Web Audio',
            'preview' => 'audio',
            'blurb'   => 'AudioContext → AnalyserNode → Canvas. Pick an oscillator or pipe in your microphone, then switch between bars, waveform, and circular spectrum visualizations. Headphones recommended.',
            'tech'    => [ 'Web Audio API', 'Canvas 2D', 'requestAnimationFrame' ],
            'badge'   => '',
        ],
        'sort' => [
            'order'   => 60,
            'tag'     => 'Algorithms',
            'preview' => 'sort',
            'blurb'   => 'Four sorts (bubble, insertion, merge, quick) race the same shuffled array. Each is a step-generator yielding control between operations so the canvas can render. Watch O(n²) lose, badly.',
            'tech'    => [ 'Generators', 'Canvas 2D', 'Visualization' ],
            'badge'   => '',
        ],
        'life' => [
            'order'   => 80,
            'tag'     => 'Cellular Automata',
            'preview' => 'life',
            'blurb'   => "Conway's Game of Life on a toroidal grid. Click or drag to toggle cells, drop in classic patterns (glider, pulsar, Gosper's gun), and watch a few rules turn into a surprising amount of structure.",
            'tech'    => [ 'Typed Arrays', 'Canvas 2D', 'Pointer events' ],
            'badge'   => '',
        ],
        'live-editor' => [
            'order'   => 90,
            'tag'     => 'Front-end',
            'preview' => 'live-editor',
            'blurb'   => 'A two-pane mini code editor that re-renders a sandboxed CSS preview on every keystroke. The preview lives inside a Shadow DOM, the syntax highlighter is hand-rolled, and the whole thing runs without a build step.',
            'tech'    => [ 'Shadow DOM', 'Tokenizer', 'Vanilla JS' ],
            'badge'   => '',
        ],
        'pug-attack' => [
            'order'   => 20,
            'tag'     => 'Old game, ported',
            'preview' => 'pug-attack',
            'blurb'   => 'A small Processing sketch from a DePaul level design class, hand-ported to vanilla JavaScript and Canvas so it plays in the browser. The pug paces along the top, the cannon tracks the mouse, and clicking fires a shot at the cat scrolling along the bottom.',
            'tech'    => [ 'Vanilla JS', 'Canvas 2D', 'Original Processing' ],
            'badge'   => '',
        ],
        'microgame' => [
            'order'   => 10,
            'tag'     => 'Old game, ported',
            'preview' => 'microgame',
            'blurb'   => 'A maze game from a DePaul intro class, ported from Processing to vanilla JavaScript and Canvas. Press Play, then Enter, and move with WASD to reach the orange goal at the bottom right before the ten-second timer runs out.',
            'tech'    => [ 'Vanilla JS', 'Canvas 2D', 'Original Processing' ],
            'badge'   => '',
        ],
        'pokedex' => [
            'order'   => 10,
            'tag'     => 'Two APIs',
            'preview' => 'pokedex',
            'blurb'   => 'Browse all 1025 Pokémon by generation, then pick one and see every Trading Card Game card it has appeared on. Sort by release date or rarity, filter by set, and open any card for full details and current pricing from TCGPlayer and Cardmarket.',
            'tech'    => [ 'PokéAPI', 'pokemontcg.io', 'Vanilla JS' ],
            'badge'   => '',
        ],
        'card-browser' => [
            'order'   => 20,
            'tag'     => 'Live API + Proxy',
            'preview' => 'card-browser',
            'blurb'   => 'Browse Pokémon TCG cards and sealed products by set, in English or Japanese, with filters for rarity, condition, printing, and price. Live prices come from JustTCG through a WordPress REST proxy that keeps the API key server-side and caches against the free-tier rate limit.',
            'tech'    => [ 'JustTCG', 'WP REST API', 'Transient cache' ],
            'badge'   => '',
        ],
        'ant-buster' => [
            'order'   => 10,
            'tag'     => 'Tower defense',
            'preview' => 'ant-buster',
            'blurb'   => 'A full tower-defense game about keeping ants off the picnic. Place and upgrade your cannons, survive the waves, and try not to let the ants carry the food away. It is the biggest thing I have built just for the fun of it.',
            'tech'    => [ 'Vanilla JS', 'Canvas 2D', 'Zero deps' ],
            'badge'   => 'Playable',
        ],
        'prompt-studio' => [
            'order'   => 30,
            'tag'     => 'AI · Tooling',
            'preview' => 'prompt-studio',
            'blurb'   => 'A little studio for writing and scoring AI prompts. Pick a task, draft a prompt, and it grades how the result came out so you can see what is actually working.',
            'tech'    => [ 'Vanilla JS', 'Single file', 'Zero deps' ],
            'badge'   => '',
        ],
    ];
}

/**
 * Presentation data for one demo slug, with defaults filled in for any demo
 * that has no entry in dariobimbi_demo_meta().
 *
 * @param string $slug The demo page's post_name.
 * @return array<string,mixed>
 */
function dariobimbi_demo_meta_for( $slug ) {
    $defaults = [
        'order'   => PHP_INT_MAX,
        'tag'     => 'Demo',
        'preview' => 'default',
        'blurb'   => '',
        'tech'    => [ 'Vanilla JS' ],
        'badge'   => '',
    ];
    $all = dariobimbi_demo_meta();
    return isset( $all[ $slug ] ) ? array_merge( $defaults, $all[ $slug ] ) : $defaults;
}

/**
 * The Demos page's children in display order: the `order` from
 * dariobimbi_demo_meta() first, then menu_order and title as the tiebreak.
 * Demos with no meta entry sort last. The hub and the header nav both call
 * this so their ordering can't drift.
 *
 * @return WP_Post[]
 */
function dariobimbi_demo_children() {
    $demos_page = get_page_by_path( 'demos' );
    if ( ! $demos_page ) {
        return [];
    }
    $children = get_pages( [
        'parent'      => $demos_page->ID,
        'sort_column' => 'menu_order,post_title',
    ] );
    // usort is stable in PHP 8, so equal orders keep the menu_order/title sort.
    usort( $children, static function ( $a, $b ) {
        $oa = dariobimbi_demo_meta_for( $a->post_name )['order'];
        $ob = dariobimbi_demo_meta_for( $b->post_name )['order'];
        return $oa <=> $ob;
    } );
    return $children;
}

/**
 * Echo the CSS-only motion sketch for a demo card's preview strip.
 *
 * Shared by the hub and the homepage playground. Unknown keys get the
 * three-bar default. Sprites for the Pokédex preview are served from
 * assets/demos/pokedex/ (grayscale Red/Blue sprites from PokéAPI's public
 * sprite repo); mix-blend-mode: multiply paints them as dark-green pixels on
 * the lighter green Game Boy screen.
 *
 * @param string $preview Preview key (the `preview` value from the meta).
 */
function dariobimbi_demo_preview( $preview ) {
    switch ( $preview ) {
        case 'contrast':
            echo '<span class="cp-contrast-fg">Aa</span><span class="cp-contrast-bg">Aa</span>';
            break;
        case 'weather':
            echo '<span class="cp-wx-sun"></span><span class="cp-wx-cloud"></span>'
                . str_repeat( '<span class="cp-wx-drop"></span>', 3 );
            break;
        case 'audio':
            echo str_repeat( '<span class="cp-eq"></span>', 10 );
            break;
        case 'sort':
            echo str_repeat( '<span class="cp-sort"></span>', 8 );
            break;
        case 'life':
            // Real Conway glider: 4 stacked phase frames cross-fade through the
            // glider's period-4 rotation, while the whole glider container steps
            // diagonally across a faint 6x6 grid.
            $phases = [
                [ '.X.', '..X', 'XXX' ],
                [ 'X.X', '.XX', '.X.' ],
                [ '..X', 'X.X', '.XX' ],
                [ 'X..', '.XX', 'XX.' ],
            ];
            echo '<span class="cp-life-glider">';
            foreach ( $phases as $idx => $rows ) {
                echo '<span class="cp-life-frame cp-life-frame-' . (int) $idx . '">';
                foreach ( $rows as $row ) {
                    foreach ( str_split( $row ) as $ch ) {
                        echo '<span class="cp-life-cell' . ( 'X' === $ch ? ' is-on' : '' ) . '"></span>';
                    }
                }
                echo '</span>';
            }
            echo '</span>';
            break;
        case 'live-editor':
            echo '<span class="cp-le-pane">'
                . '<span class="cp-le-line"><span class="cp-le-sel">.btn</span> <span class="cp-le-brace">{</span></span>'
                . '<span class="cp-le-line">  <span class="cp-le-prop">radius</span><span class="cp-le-punct">:</span> <span class="cp-le-num">18px</span><span class="cp-le-punct">;</span></span>'
                . '<span class="cp-le-line">  <span class="cp-le-prop">color</span><span class="cp-le-punct">:</span> <span class="cp-le-hex">#a855f7</span><span class="cp-le-punct">;</span></span>'
                . '<span class="cp-le-line"><span class="cp-le-brace">}</span><span class="cp-le-caret"></span></span>'
                . '</span>'
                . '<span class="cp-le-render"><span class="cp-le-card"></span></span>';
            break;
        case 'microgame':
            for ( $i = 1; $i <= 6; $i++ ) {
                echo '<span class="cp-mg-wall cp-mg-wall-' . $i . '"></span>';
            }
            echo '<span class="cp-mg-goal"></span><span class="cp-mg-player"></span>';
            break;
        case 'pug-attack':
            echo '<span class="cp-pa-ground"></span><span class="cp-pa-pug"></span>'
                . '<span class="cp-pa-bullet"></span><span class="cp-pa-cat"></span>';
            break;
        case 'pokedex':
            $sprite_base = get_stylesheet_directory_uri() . '/assets/demos/pokedex/';
            $poke_ids    = [ 1, 4, 7, 25, 150 ]; // Bulbasaur, Charmander, Squirtle, Pikachu, Mewtwo
            echo '<span class="cp-poke-shell"><span class="cp-poke-led"></span><span class="cp-poke-screen">';
            foreach ( $poke_ids as $idx => $pid ) {
                echo '<img class="cp-poke-sprite cp-poke-sprite-' . (int) $idx . '" src="' . esc_url( $sprite_base . $pid . '.png' ) . '" alt="" loading="lazy" decoding="async" width="56" height="56">';
            }
            echo '</span></span>';
            break;
        case 'card-browser':
            // Three Pokémon-style mini cards fanned out and gently bobbing. Top
            // stripe + circle "art" placeholder reads as "Pokémon TCG card"
            // without leaning on Game Freak art.
            echo '<span class="cp-tcg-card cp-tcg-card-1"></span><span class="cp-tcg-card cp-tcg-card-2"></span><span class="cp-tcg-card cp-tcg-card-3"></span>';
            break;
        case 'ant-buster':
            echo '<span class="cp-ab-cannon"></span><span class="cp-ab-shot"></span><span class="cp-ab-crumb"></span>'
                . str_repeat( '<span class="cp-ab-ant"></span>', 3 );
            break;
        case 'prompt-studio':
            echo '<span class="cp-ps-panel"><span class="cp-ps-line cp-ps-line-1"></span><span class="cp-ps-line cp-ps-line-2"></span><span class="cp-ps-caret"></span></span>'
                . '<span class="cp-ps-spark cp-ps-spark-1"></span><span class="cp-ps-spark cp-ps-spark-2"></span><span class="cp-ps-spark cp-ps-spark-3"></span>';
            break;
        default:
            echo str_repeat( '<span class="cp-default"></span>', 3 );
    }
}
