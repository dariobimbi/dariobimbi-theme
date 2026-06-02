<?php
/**
 * Title: Demos Hub
 * Slug: dariobimbi/demos-hub
 * Categories: dariobimbi
 * Description: Landing page for /demos/. Auto-lists every child of the Demos page as a styled card. Per-slug metadata (accent, tag, tech, preview) lives in $meta below — when you publish a new child page, give it an entry here for the bespoke flair, otherwise it falls back to a tasteful default.
 *
 * The page itself (slug "demos") stays empty in admin — content lives here.
 * Template: page-demos.html (auto-picked from slug, also registered in
 * theme.json customTemplates so it shows in the dropdown).
 */

$demos_page = get_page_by_path( 'demos' );
$children   = $demos_page
    ? get_pages( [
        'parent'      => $demos_page->ID,
        'sort_column' => 'menu_order,post_title',
    ] )
    : [];

/**
 * Per-demo metadata, keyed by child page slug. Anything missing falls back to
 * sensible defaults (preview "default", neutral tag/tech/blurb). Add a new
 * entry whenever you publish a new demo to give it bespoke styling.
 */
$meta = [
    'contrast' => [
        'category' => 'web',
        'tag'      => 'Accessibility',
        'preview'  => 'contrast',
        'blurb'    => 'Pick any two colors and watch the WCAG ratio update live. Pass/fail tiles call out AA, AAA, and UI-component thresholds, and the URL updates so you can share a palette.',
        'tech'     => [ 'Vanilla JS', 'WCAG 2.1', 'Zero deps' ],
        'badge'    => '',
    ],
    'weather' => [
        'category' => 'web',
        'tag'      => 'Live API',
        'preview'  => 'weather',
        'blurb'    => 'Two-step fetch chain against the Open-Meteo API: geocode the city, then pull a 7-day forecast. Pretty-printed JSON pane underneath shows the actual response. No key, no library, no proxy.',
        'tech'     => [ 'fetch()', 'async/await', 'Open-Meteo' ],
        'badge'    => 'New',
    ],
    'audio' => [
        'category' => 'web',
        'tag'      => 'Web Audio',
        'preview'  => 'audio',
        'blurb'    => 'AudioContext → AnalyserNode → Canvas. Pick an oscillator or pipe in your microphone, then switch between bars, waveform, and circular spectrum visualizations. Headphones recommended.',
        'tech'     => [ 'Web Audio API', 'Canvas 2D', 'requestAnimationFrame' ],
        'badge'    => 'New',
    ],
    'sort' => [
        'category' => 'web',
        'tag'      => 'Algorithms',
        'preview'  => 'sort',
        'blurb'    => 'Four sorts (bubble, insertion, merge, quick) race the same shuffled array. Each is a step-generator yielding control between operations so the canvas can render. Watch O(n²) lose, badly.',
        'tech'     => [ 'Generators', 'Canvas 2D', 'Visualization' ],
        'badge'    => 'New',
    ],
    'life' => [
        'category' => 'web',
        'tag'      => 'Cellular Automata',
        'preview'  => 'life',
        'blurb'    => "Conway's Game of Life on a toroidal grid. Click or drag to toggle cells, drop in classic patterns (glider, pulsar, Gosper's gun), and watch a few rules turn into a surprising amount of structure.",
        'tech'     => [ 'Typed Arrays', 'Canvas 2D', 'Pointer events' ],
        'badge'    => 'New',
    ],
    'live-editor' => [
        'category' => 'web',
        'tag'      => 'Front-end',
        'preview'  => 'live-editor',
        'blurb'    => 'A two-pane mini code editor that re-renders a sandboxed CSS preview on every keystroke. The preview lives inside a Shadow DOM, the syntax highlighter is hand-rolled, and the whole thing runs without a build step.',
        'tech'     => [ 'Shadow DOM', 'Tokenizer', 'Vanilla JS' ],
        'badge'    => '',
    ],
    'pug-attack' => [
        'category' => 'ported',
        'tag'      => 'Old game, ported',
        'preview'  => 'pug-attack',
        'blurb'    => 'A small Processing sketch from a DePaul level design class, hand-ported to vanilla JavaScript and Canvas so it plays in the browser. The pug paces along the top, the cannon tracks the mouse, and clicking fires a shot at the cat scrolling along the bottom.',
        'tech'     => [ 'Vanilla JS', 'Canvas 2D', 'Original Processing' ],
        'badge'    => 'New',
    ],
    'microgame' => [
        'category' => 'ported',
        'tag'      => 'Old game, ported',
        'preview'  => 'microgame',
        'blurb'    => 'A maze game from a DePaul intro class, ported from Processing to vanilla JavaScript and Canvas. Press Play, then Enter, and move with WASD to reach the orange goal at the bottom right before the ten-second timer runs out.',
        'tech'     => [ 'Vanilla JS', 'Canvas 2D', 'Original Processing' ],
        'badge'    => 'New',
    ],
    'pokedex' => [
        'category' => 'web',
        'tag'      => 'Two APIs',
        'preview'  => 'pokedex',
        'blurb'    => 'Browse all 1025 Pokémon by generation, then pick one and see every Trading Card Game card it has appeared on. Sort by release date or rarity, filter by set, and open any card for full details and current pricing from TCGPlayer and Cardmarket.',
        'tech'     => [ 'PokéAPI', 'pokemontcg.io', 'Vanilla JS' ],
        'badge'    => 'New',
    ],
    'card-browser' => [
        'category' => 'web',
        'tag'      => 'Live API + Proxy',
        'preview'  => 'card-browser',
        'blurb'    => 'Browse Pokémon TCG cards and sealed products by set, in English or Japanese, with filters for rarity, condition, printing, and price. Live prices come from JustTCG through a WordPress REST proxy that keeps the API key server-side and caches against the free-tier rate limit.',
        'tech'     => [ 'JustTCG', 'WP REST API', 'Transient cache' ],
        'badge'    => 'New',
    ],
    'ant-buster' => [
        'category' => 'game',
        'tag'      => 'Tower defense',
        'preview'  => 'ant-buster',
        'blurb'    => 'A full tower-defense game about keeping ants off the picnic. Place and upgrade your cannons, survive the waves, and try not to let the ants carry the food away. It is the biggest thing I have built just for the fun of it.',
        'tech'     => [ 'Vanilla JS', 'Canvas 2D', 'Zero deps' ],
        'badge'    => 'Playable',
    ],
    'prompt-studio' => [
        'category' => 'web',
        'tag'      => 'AI · Tooling',
        'preview'  => 'prompt-studio',
        'blurb'    => 'A little studio for writing and scoring AI prompts. Pick a task, draft a prompt, and it grades how the result came out so you can see what is actually working.',
        'tech'     => [ 'Vanilla JS', 'Single file', 'Zero deps' ],
        'badge'    => 'New',
    ],
];

/* Default category for any child page without a $meta entry. */
$default_category = 'web';

/* Rotating accent palette — each card picks one based on its position so a
   row alternates a warm pink / yellow rhythm without any per-card config.
   (The cool blue/purple accents were retired with the warm repalette.) */
$accents = [ 'pink', 'yellow' ];

/* Default fallback for any child page without an entry in $meta. */
$default_meta = [
    'category' => $default_category,
    'tag'      => 'Demo',
    'preview'  => 'default',
    'blurb'    => '',
    'tech'     => [ 'Vanilla JS' ],
    'badge'    => '',
];

/* Bucket children by category so we can render the sections in order
   (web demos, games I built, old games ported). */
$buckets = [ 'web' => [], 'game' => [], 'ported' => [] ];
foreach ( $children as $child ) {
    $cslug = $child->post_name;
    $cat   = isset( $meta[ $cslug ]['category'] ) ? $meta[ $cslug ]['category'] : $default_category;
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
    <p class="has-text-align-center has-muted-color has-text-color has-large-font-size" style="line-height:1.55">Small, self-contained things I built to try out an idea or work through a problem I was curious about. No frameworks, no build step, and no analytics. Each one is a single PHP file you can open up and read from top to bottom.</p>
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
                $m    = isset( $meta[ $slug ] ) ? array_merge( $default_meta, $meta[ $slug ] ) : $default_meta;
                $accent = $accents[ $i % count( $accents ) ];
                $blurb  = $m['blurb'] !== ''
                    ? $m['blurb']
                    : ( $child->post_excerpt !== '' ? $child->post_excerpt : 'Open it up. The description lives in the demo itself.' );
            ?>
            <li class="demo-card demo-card--<?php echo esc_attr( $accent ); ?>">
            <a class="demo-card-link" href="<?php echo esc_url( get_permalink( $child ) ); ?>" aria-labelledby="demo-<?php echo esc_attr( $slug ); ?>-title">
                <div class="demo-card-preview demo-card-preview--<?php echo esc_attr( $m['preview'] ); ?>" aria-hidden="true">
                    <?php // Per-preview markup — each is a tiny CSS-only motion sketch. ?>
                    <?php if ( $m['preview'] === 'contrast' ) : ?>
                        <span class="cp-contrast-fg">Aa</span>
                        <span class="cp-contrast-bg">Aa</span>
                    <?php elseif ( $m['preview'] === 'weather' ) : ?>
                        <span class="cp-wx-sun"></span>
                        <span class="cp-wx-cloud"></span>
                        <span class="cp-wx-drop"></span>
                        <span class="cp-wx-drop"></span>
                        <span class="cp-wx-drop"></span>
                    <?php elseif ( $m['preview'] === 'audio' ) : ?>
                        <span class="cp-eq"></span>
                        <span class="cp-eq"></span>
                        <span class="cp-eq"></span>
                        <span class="cp-eq"></span>
                        <span class="cp-eq"></span>
                        <span class="cp-eq"></span>
                        <span class="cp-eq"></span>
                        <span class="cp-eq"></span>
                        <span class="cp-eq"></span>
                        <span class="cp-eq"></span>
                    <?php elseif ( $m['preview'] === 'sort' ) : ?>
                        <span class="cp-sort"></span>
                        <span class="cp-sort"></span>
                        <span class="cp-sort"></span>
                        <span class="cp-sort"></span>
                        <span class="cp-sort"></span>
                        <span class="cp-sort"></span>
                        <span class="cp-sort"></span>
                        <span class="cp-sort"></span>
                    <?php elseif ( $m['preview'] === 'life' ) : ?>
                        <?php // Real Conway glider: 4 stacked phase frames cross-fade
                             // through the glider's period-4 rotation, while the whole
                             // glider container steps diagonally across a faint 6x6 grid.
                            $cp_life_phases = [
                                [ '.X.', '..X', 'XXX' ],
                                [ 'X.X', '.XX', '.X.' ],
                                [ '..X', 'X.X', '.XX' ],
                                [ 'X..', '.XX', 'XX.' ],
                            ];
                        ?>
                        <span class="cp-life-glider">
                            <?php foreach ( $cp_life_phases as $idx => $rows ) : ?>
                                <span class="cp-life-frame cp-life-frame-<?php echo $idx; ?>">
                                    <?php foreach ( $rows as $row ) : ?>
                                        <?php foreach ( str_split( $row ) as $ch ) : ?>
                                            <span class="cp-life-cell<?php echo $ch === 'X' ? ' is-on' : ''; ?>"></span>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                </span>
                            <?php endforeach; ?>
                        </span>
                    <?php elseif ( $m['preview'] === 'live-editor' ) : ?>
                        <span class="cp-le-pane">
                            <span class="cp-le-line"><span class="cp-le-sel">.btn</span> <span class="cp-le-brace">{</span></span>
                            <span class="cp-le-line">  <span class="cp-le-prop">radius</span><span class="cp-le-punct">:</span> <span class="cp-le-num">18px</span><span class="cp-le-punct">;</span></span>
                            <span class="cp-le-line">  <span class="cp-le-prop">color</span><span class="cp-le-punct">:</span> <span class="cp-le-hex">#a855f7</span><span class="cp-le-punct">;</span></span>
                            <span class="cp-le-line"><span class="cp-le-brace">}</span><span class="cp-le-caret"></span></span>
                        </span>
                        <span class="cp-le-render"><span class="cp-le-card"></span></span>
                    <?php elseif ( $m['preview'] === 'microgame' ) : ?>
                        <span class="cp-mg-wall cp-mg-wall-1"></span>
                        <span class="cp-mg-wall cp-mg-wall-2"></span>
                        <span class="cp-mg-wall cp-mg-wall-3"></span>
                        <span class="cp-mg-wall cp-mg-wall-4"></span>
                        <span class="cp-mg-wall cp-mg-wall-5"></span>
                        <span class="cp-mg-wall cp-mg-wall-6"></span>
                        <span class="cp-mg-goal"></span>
                        <span class="cp-mg-player"></span>
                    <?php elseif ( $m['preview'] === 'pug-attack' ) : ?>
                        <span class="cp-pa-ground"></span>
                        <span class="cp-pa-pug"></span>
                        <span class="cp-pa-bullet"></span>
                        <span class="cp-pa-cat"></span>
                    <?php elseif ( $m['preview'] === 'pokedex' ) : ?>
                        <?php // Cycling original Red/Blue sprites on a Game Boy green LCD.
                             // Sprites are grayscale-on-transparent PNGs from PokéAPI's
                             // public sprite repo; mix-blend-mode: multiply paints them
                             // as authentic dark-green pixels on the lighter green screen. ?>
                        <span class="cp-poke-shell">
                            <span class="cp-poke-led"></span>
                            <span class="cp-poke-screen">
                                <?php
                                $cp_poke_ids = [ 1, 4, 7, 25, 150 ]; // Bulbasaur, Charmander, Squirtle, Pikachu, Mewtwo
                                foreach ( $cp_poke_ids as $idx => $pid ) :
                                    $cp_poke_url = 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/versions/generation-i/red-blue/transparent/' . $pid . '.png';
                                ?>
                                    <img class="cp-poke-sprite cp-poke-sprite-<?php echo $idx; ?>" src="<?php echo esc_url( $cp_poke_url ); ?>" alt="" loading="lazy" decoding="async" width="56" height="56">
                                <?php endforeach; ?>
                            </span>
                        </span>
                    <?php elseif ( $m['preview'] === 'card-browser' ) : ?>
                        <?php // Three Pokémon-style mini cards fanned out and gently
                             // bobbing. Top stripe + circle "art" placeholder reads as
                             // "Pokémon TCG card" without leaning on Game Freak art. ?>
                        <span class="cp-tcg-card cp-tcg-card-1"></span>
                        <span class="cp-tcg-card cp-tcg-card-2"></span>
                        <span class="cp-tcg-card cp-tcg-card-3"></span>
                    <?php elseif ( $m['preview'] === 'ant-buster' ) : ?>
                        <span class="cp-ab-cannon"></span>
                        <span class="cp-ab-shot"></span>
                        <span class="cp-ab-crumb"></span>
                        <span class="cp-ab-ant"></span>
                        <span class="cp-ab-ant"></span>
                        <span class="cp-ab-ant"></span>
                    <?php elseif ( $m['preview'] === 'prompt-studio' ) : ?>
                        <span class="cp-ps-panel">
                            <span class="cp-ps-line cp-ps-line-1"></span>
                            <span class="cp-ps-line cp-ps-line-2"></span>
                            <span class="cp-ps-caret"></span>
                        </span>
                        <span class="cp-ps-spark cp-ps-spark-1"></span>
                        <span class="cp-ps-spark cp-ps-spark-2"></span>
                        <span class="cp-ps-spark cp-ps-spark-3"></span>
                    <?php else : ?>
                        <span class="cp-default"></span>
                        <span class="cp-default"></span>
                        <span class="cp-default"></span>
                    <?php endif; ?>
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
            <li class="demo-card demo-card--lab" aria-hidden="true">
                <div class="demo-card-link demo-card-link--static">
                    <div class="demo-card-preview demo-card-preview--lab">
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
