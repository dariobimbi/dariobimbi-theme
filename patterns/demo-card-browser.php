<?php
/**
 * Title: Demo — Pokémon TCG Browser
 * Slug: dariobimbi/demo-card-browser
 * Categories: dariobimbi
 * Description: Browse Pokémon cards and sealed products by set, with rarity, price, and language filters. Pulls live pricing from JustTCG via a server-side proxy that keeps the API key off the client.
 */
?>
<!-- wp:group {"align":"full","className":"section section--surface","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull section section--surface" style="padding:var(--wp--preset--spacing--60) var(--wp--preset--spacing--30)">

    <!-- wp:html -->
    <div class="demo-card-browser" data-state="boot">

        <header class="dcb-header">
            <p class="eyebrow">demo</p>
            <h1 class="dcb-title">Pokémon TCG Browser</h1>
            <p class="dcb-subtitle">Live Pokémon prices from JustTCG, sealed products and singles, English and Japanese sets, all proxied through the theme so the API key stays off the client.</p>
        </header>

        <div class="dcb-stale" hidden>
            <strong>Showing cached prices.</strong> The live API hit a rate limit, so this is the most recent successful pull. Prices update again as soon as the rate limit window resets.
        </div>

        <div class="dcb-config-error" hidden>
            <strong>JustTCG API key not configured.</strong> Set <code>JUSTTCG_API_KEY</code> in <code>wp-config.php</code> to enable this demo.
        </div>

        <div class="dcb-layout">

            <div class="dcb-drawer-backdrop" hidden data-action="close-drawer"></div>

            <aside class="dcb-sidebar" id="dcb-filter-sheet" aria-label="Filters">

                <div class="dcb-sheet-head">
                    <span class="dcb-sheet-title">Filters</span>
                    <button type="button" class="dcb-sheet-close" data-action="close-drawer" aria-label="Close filters">×</button>
                </div>

                <details class="dcb-mobile-toggle" open>
                    <summary>Filters</summary>

                    <fieldset class="dcb-fieldset">
                        <legend>Show</legend>
                        <label><input type="radio" name="dcb-mode" value="all" checked> All</label>
                        <label><input type="radio" name="dcb-mode" value="cards"> Cards only</label>
                        <label><input type="radio" name="dcb-mode" value="sealed"> Sealed only</label>
                    </fieldset>

                    <fieldset class="dcb-fieldset">
                        <legend>Language</legend>
                        <label><input type="checkbox" name="dcb-lang" value="pokemon" checked> English</label>
                        <label><input type="checkbox" name="dcb-lang" value="pokemon-japan"> Japanese</label>
                    </fieldset>

                    <fieldset class="dcb-fieldset">
                        <legend>Set</legend>
                        <input type="search" class="dcb-set-search" placeholder="Find a set…" aria-label="Search sets" />
                        <div class="dcb-set-list" role="radiogroup" aria-label="Set picker">
                            <p class="dcb-set-loading">Loading sets…</p>
                        </div>
                    </fieldset>

                    <fieldset class="dcb-fieldset" data-fieldset="rarity" hidden>
                        <legend>Rarity</legend>
                        <div class="dcb-rarity-list"></div>
                    </fieldset>

                    <fieldset class="dcb-fieldset" data-fieldset="condition" hidden>
                        <legend>Condition</legend>
                        <div class="dcb-condition-list"></div>
                    </fieldset>

                    <fieldset class="dcb-fieldset" data-fieldset="printing" hidden>
                        <legend>Printing</legend>
                        <div class="dcb-printing-list"></div>
                    </fieldset>

                    <fieldset class="dcb-fieldset">
                        <legend>Price (USD)</legend>
                        <div class="dcb-price-row">
                            <div class="dcb-price-cell">
                                <span class="dcb-price-cell-label">Min</span>
                                <input type="number" class="dcb-price-min" min="0" step="1" placeholder="0" aria-label="Minimum price" />
                            </div>
                            <div class="dcb-price-cell">
                                <span class="dcb-price-cell-label">Max</span>
                                <input type="number" class="dcb-price-max" min="0" step="1" placeholder="∞" aria-label="Maximum price" />
                            </div>
                        </div>
                    </fieldset>

                    <button type="button" class="dcb-clear">Clear filters</button>
                </details>

                <div class="dcb-sheet-foot">
                    <button type="button" class="dcb-sheet-apply" data-action="close-drawer">Show results</button>
                </div>

            </aside>

            <main class="dcb-main">
                <div class="dcb-toolbar">
                    <input type="search" class="dcb-card-search" placeholder="Search by name…" aria-label="Search cards by name" />
                    <button type="button" class="dcb-filter-trigger" aria-expanded="false" aria-controls="dcb-filter-sheet">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z"/></svg>
                        Filters <span class="dcb-filter-count" hidden>0</span>
                    </button>
                    <select class="dcb-sort" aria-label="Sort">
                        <option value="default">Default order</option>
                        <option value="price-desc">Price · High to low</option>
                        <option value="price-asc">Price · Low to high</option>
                        <option value="trend-desc">Trending · Hot 7d</option>
                        <option value="name-asc">Name · A to Z</option>
                    </select>
                </div>

                <p class="dcb-status" role="status" aria-live="polite">Pick a set to start browsing.</p>

                <div class="dcb-grid"></div>

                <div class="dcb-load-more" hidden>
                    <button type="button" class="dcb-load-more-btn">Load more</button>
                </div>
            </main>

        </div>

        <!-- Modal -->
        <div class="dcb-modal" role="dialog" aria-modal="true" aria-labelledby="dcb-modal-title" hidden>
            <div class="dcb-modal-backdrop" data-action="close-modal"></div>
            <div class="dcb-modal-content">
                <button type="button" class="dcb-modal-close" data-action="close-modal" aria-label="Close">×</button>
                <div class="dcb-modal-image"></div>
                <div class="dcb-modal-details">
                    <h2 id="dcb-modal-title" class="dcb-modal-name"></h2>
                    <p class="dcb-modal-sub"></p>
                    <div class="dcb-modal-variants"></div>
                    <div class="dcb-modal-link"></div>
                </div>
            </div>
        </div>

    </div>
    <!-- /wp:html -->

</div>
<!-- /wp:group -->

<style>
/* === Card Browser demo, scoped under .demo-card-browser === */

/* Pokémon Center-inspired palette. Deliberately breaks from the rest of
   the site (which is dark / gradient-forward) because the user wanted this
   demo to feel like pokemoncenter.com — bright white surfaces, Pokémon
   brand red, Pokémon brand yellow, near-black text. The whole demo is
   scoped under .demo-card-browser, so the override doesn't bleed elsewhere. */
.demo-card-browser {
    --dcb-bg: #ffffff;
    --dcb-surface: #ffffff;
    --dcb-subtle: #e6e7ec;
    --dcb-border-strong: #c7c8cf;
    --dcb-fg: #1d1d1f;
    --dcb-muted: #6e6e73;
    --dcb-primary: #ee1c25;   /* Pokémon brand red */
    --dcb-accent: #ffcb05;    /* Pokémon brand yellow */
    --dcb-hot: #ee1c25;
    --dcb-soft-bg: #f5f5f7;   /* Pokémon Center grey card stripes */
    --dcb-radius: 16px;
    color: var(--dcb-fg);
    font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif;
}

html:has(.demo-card-browser),
body.has-card-browser-page,
body:has(.demo-card-browser) {
    background-color: #ffffff !important;
    color: #1d1d1f;
    /* Pokéball watermark, six filled ball variants in a 600px tile:
       classic Pokéball (red/white), Great Ball (blue with red wedges),
       Ultra Ball (black with yellow H), Master Ball (purple with pink
       dots), Heal Ball (pink with cyan dots), Dive Ball (cyan with
       wave). Each is hand-drawn SVG so the demo stays asset-free and
       avoids hosting Nintendo sprite art. The whole group sits at
       0.16 opacity so the bands and colored top-halves read as
       pokéballs without competing with the grid. The 110s linear
       diagonal drift gives a slow ambient float; prefers-reduced-motion
       silences it below. */
    background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 600 600'><g opacity='0.16'><g transform='translate(95,110)'><circle r='28' fill='%23ffffff'/><path d='M-28 0 A 28 28 0 0 1 28 0 Z' fill='%23ee1c25'/><path d='M-28 0 H 28' stroke='%231d1d1f' stroke-width='3'/><circle r='28' fill='none' stroke='%231d1d1f' stroke-width='2'/><circle r='6.5' fill='%23ffffff' stroke='%231d1d1f' stroke-width='2'/></g><g transform='translate(430,90)'><circle r='28' fill='%23ffffff'/><path d='M-28 0 A 28 28 0 0 1 28 0 Z' fill='%233f51b5'/><path d='M -22 -8 L -8 -22 L -8 -16 L -16 -8 Z' fill='%23e53935'/><path d='M 8 -22 L 22 -8 L 16 -8 L 8 -16 Z' fill='%23e53935'/><path d='M-28 0 H 28' stroke='%231d1d1f' stroke-width='3'/><circle r='28' fill='none' stroke='%231d1d1f' stroke-width='2'/><circle r='6.5' fill='%23ffffff' stroke='%231d1d1f' stroke-width='2'/></g><g transform='translate(280,250)'><circle r='28' fill='%23ffffff'/><path d='M-28 0 A 28 28 0 0 1 28 0 Z' fill='%231d1d1f'/><rect x='-12' y='-22' width='3' height='14' fill='%23ffcb05'/><rect x='9' y='-22' width='3' height='14' fill='%23ffcb05'/><rect x='-12' y='-16' width='24' height='2.5' fill='%23ffcb05'/><path d='M-28 0 H 28' stroke='%231d1d1f' stroke-width='3'/><circle r='28' fill='none' stroke='%231d1d1f' stroke-width='2'/><circle r='6.5' fill='%23ffffff' stroke='%231d1d1f' stroke-width='2'/></g><g transform='translate(520,330)'><circle r='28' fill='%23ffffff'/><path d='M-28 0 A 28 28 0 0 1 28 0 Z' fill='%237c3aed'/><circle cx='-12' cy='-10' r='2.5' fill='%23f9a8d4'/><circle cx='0' cy='-16' r='2.5' fill='%23f9a8d4'/><circle cx='12' cy='-10' r='2.5' fill='%23f9a8d4'/><path d='M-28 0 H 28' stroke='%231d1d1f' stroke-width='3'/><circle r='28' fill='none' stroke='%231d1d1f' stroke-width='2'/><circle r='6.5' fill='%23ffffff' stroke='%231d1d1f' stroke-width='2'/></g><g transform='translate(130,420)'><circle r='28' fill='%23ffffff'/><path d='M-28 0 A 28 28 0 0 1 28 0 Z' fill='%23f48fb1'/><circle cx='-14' cy='-12' r='2.5' fill='%234dd0e1'/><circle cx='14' cy='-12' r='2.5' fill='%234dd0e1'/><path d='M-28 0 H 28' stroke='%231d1d1f' stroke-width='3'/><circle r='28' fill='none' stroke='%231d1d1f' stroke-width='2'/><circle r='6.5' fill='%23ffffff' stroke='%231d1d1f' stroke-width='2'/></g><g transform='translate(380,510)'><circle r='28' fill='%23ffffff'/><path d='M-28 0 A 28 28 0 0 1 28 0 Z' fill='%2306b6d4'/><path d='M -16 -10 Q -8 -15 0 -10 T 16 -10' fill='none' stroke='%230891b2' stroke-width='2'/><path d='M-28 0 H 28' stroke='%231d1d1f' stroke-width='3'/><circle r='28' fill='none' stroke='%231d1d1f' stroke-width='2'/><circle r='6.5' fill='%23ffffff' stroke='%231d1d1f' stroke-width='2'/></g></g></svg>");
    background-repeat: repeat;
    animation: dcb-pokeball-drift 110s linear infinite;
}

@keyframes dcb-pokeball-drift {
    from { background-position: 0 0; }
    to   { background-position: 600px 600px; }
}

/* Honor the OS-level reduced-motion setting: drifting backgrounds are
   exactly the kind of ambient motion users with vestibular sensitivities
   ask sites to turn off. */
@media (prefers-reduced-motion: reduce) {
    body:has(.demo-card-browser) {
        animation: none;
    }
}

/* The pattern's outer .section--surface wrapper paints the dark site
   surface color from style.css. Force it transparent when this demo
   lives inside it so the body's pokéball watermark shows through;
   :has() lets us scope the override without editing global CSS. */
.section--surface:has(.demo-card-browser) {
    background: transparent !important;
}

/* The site's primary nav was tuned for the dark gradient theme, so its
   open-dropdown state uses a translucent blue→violet gradient with
   light-foreground text. Against this demo's white background that
   reads as pale-lavender-on-white — almost invisible. Override the
   open / hover / focus states here so the trigger pills paint as a
   solid Pokémon-red→yellow gradient with white text, matching the
   demo's palette and giving real contrast against the page. Scoped to
   the body class set by this pattern's JS so the rest of the site
   keeps its original styling. */
body.has-card-browser-page .site-nav-dropdown[open] > .site-nav-trigger,
body:has(.demo-card-browser) .site-nav-dropdown[open] > .site-nav-trigger {
    background: linear-gradient(135deg, #ee1c25 0%, #f59e0b 100%);
    border-color: #ee1c25;
    color: #ffffff;
}
body.has-card-browser-page .site-nav-trigger:hover,
body.has-card-browser-page .site-nav-trigger:focus-visible,
body.has-card-browser-page .site-nav-trigger--link:hover,
body.has-card-browser-page .site-nav-trigger--link:focus-visible,
body:has(.demo-card-browser) .site-nav-trigger:hover,
body:has(.demo-card-browser) .site-nav-trigger:focus-visible,
body:has(.demo-card-browser) .site-nav-trigger--link:hover,
body:has(.demo-card-browser) .site-nav-trigger--link:focus-visible {
    border-color: #ee1c25;
    color: #ee1c25;
}

/* Eyebrow on this demo is dark muted text without the "</>" code-comment
   prefix — that prefix reads as portfolio voice, which clashes with
   pokemoncenter.com's plain marketing register. */
.demo-card-browser .eyebrow {
    color: var(--dcb-muted);
    font-family: 'Inter', system-ui, sans-serif;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    font-size: 0.75rem;
}
.demo-card-browser .eyebrow::before {
    display: none;
}

.demo-card-browser .dcb-header {
    text-align: center;
    margin-bottom: var(--wp--preset--spacing--40);
}

.demo-card-browser .dcb-title {
    font-size: clamp(2.5rem, 1.5rem + 4vw, 5rem);
    font-weight: 800;
    color: var(--dcb-primary);
    margin: 0.25rem 0;
    -webkit-text-fill-color: var(--dcb-primary);
    letter-spacing: -0.02em;
}

/* Yellow underline accent under the title — Pokémon Center uses big
   blocks of brand yellow as section markers, so a small bar reads
   as the same family without taking over the layout. */
.demo-card-browser .dcb-title::after {
    content: "";
    display: block;
    width: 80px;
    height: 6px;
    margin: 0.5rem auto 0;
    background: var(--dcb-accent);
    border-radius: 999px;
}

.demo-card-browser .dcb-subtitle {
    color: var(--dcb-muted);
    max-width: 60ch;
    margin: 0.75rem auto 0;
}

.demo-card-browser .dcb-stale,
.demo-card-browser .dcb-config-error {
    margin: 0 auto var(--wp--preset--spacing--30);
    max-width: 80ch;
    padding: 0.85rem 1rem;
    border-radius: var(--dcb-radius);
    background: var(--dcb-surface);
    border: 1px solid var(--dcb-subtle);
    color: var(--dcb-fg);
    font-size: 0.95rem;
}

.demo-card-browser .dcb-config-error {
    border-color: var(--dcb-hot);
}

.demo-card-browser .dcb-config-error code {
    background: rgba(255, 255, 255, 0.08);
    padding: 0.1rem 0.35rem;
    border-radius: 4px;
    font-size: 0.85em;
}

.demo-card-browser .dcb-layout {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: var(--wp--preset--spacing--40);
    align-items: start;
}

@media (max-width: 900px) {
    .demo-card-browser .dcb-layout {
        grid-template-columns: 1fr;
    }
}

.demo-card-browser .dcb-sidebar {
    position: sticky;
    top: 1rem;
}

@media (max-width: 900px) {
    .demo-card-browser .dcb-sidebar {
        position: static;
    }
}

.demo-card-browser .dcb-mobile-toggle {
    background: var(--dcb-surface);
    border: 1px solid var(--dcb-subtle);
    border-radius: var(--dcb-radius);
    padding: 1rem;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
}

.demo-card-browser .dcb-mobile-toggle > summary {
    cursor: pointer;
    font-weight: 700;
    padding: 0.25rem;
    list-style: none;
}

.demo-card-browser .dcb-mobile-toggle > summary::-webkit-details-marker {
    display: none;
}

.demo-card-browser .dcb-fieldset {
    border: 0;
    padding: 0;
    margin: 1rem 0 0;
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
}

.demo-card-browser .dcb-fieldset legend {
    font-weight: 600;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--dcb-muted);
    margin-bottom: 0.4rem;
}

.demo-card-browser .dcb-fieldset label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.95rem;
    cursor: pointer;
    padding: 0.2rem 0;
}

.demo-card-browser .dcb-fieldset input[type="radio"],
.demo-card-browser .dcb-fieldset input[type="checkbox"] {
    accent-color: var(--dcb-primary);
}

.demo-card-browser .dcb-set-search,
.demo-card-browser .dcb-card-search,
.demo-card-browser .dcb-fieldset input[type="number"] {
    background: var(--dcb-bg);
    color: var(--dcb-fg);
    border: 1px solid var(--dcb-subtle);
    border-radius: 8px;
    padding: 0.5rem 0.75rem;
    font: inherit;
    width: 100%;
}

.demo-card-browser .dcb-set-search:focus,
.demo-card-browser .dcb-card-search:focus,
.demo-card-browser .dcb-fieldset input[type="number"]:focus,
.demo-card-browser .dcb-sort:focus {
    outline: 2px solid var(--dcb-primary);
    outline-offset: 2px;
}

.demo-card-browser .dcb-set-list {
    max-height: 260px;
    overflow-y: auto;
    border: 1px solid var(--dcb-subtle);
    border-radius: 8px;
    padding: 0.5rem;
    background: var(--dcb-bg);
}

.demo-card-browser .dcb-set-list label {
    font-size: 0.9rem;
    padding: 0.25rem;
    border-radius: 6px;
}

.demo-card-browser .dcb-set-list label:hover {
    background: var(--dcb-surface);
}

.demo-card-browser .dcb-set-list .dcb-set-meta {
    color: var(--dcb-muted);
    font-size: 0.8em;
    margin-left: auto;
}

.demo-card-browser .dcb-set-loading {
    color: var(--dcb-muted);
    margin: 0;
    text-align: center;
    padding: 1rem;
}

.demo-card-browser .dcb-price-row {
    display: flex;
    gap: 0.75rem;
}

/* Each price cell is a column-flex with the field label stacked above
   the number input. flex: 1 1 0 + min-width: 0 stops a long placeholder
   or input value from blowing out the cell. box-sizing: border-box on
   the input is the load-bearing fix — without it the input's padding
   plus border pushed past width:100% and the Max input visually
   overlapped the Min input on narrower sidebars. */
.demo-card-browser .dcb-price-cell {
    flex: 1 1 0;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
}

.demo-card-browser .dcb-price-cell-label {
    font-size: 0.78rem;
    color: var(--dcb-muted);
    font-weight: 600;
    letter-spacing: 0.04em;
}

.demo-card-browser .dcb-price-cell input[type="number"] {
    box-sizing: border-box;
    width: 100%;
}

.demo-card-browser .dcb-clear {
    margin-top: 1rem;
    padding: 0.5rem 1rem;
    background: transparent;
    color: var(--dcb-fg);
    border: 1px solid var(--dcb-subtle);
    border-radius: 8px;
    cursor: pointer;
    font: inherit;
}

.demo-card-browser .dcb-clear:hover {
    background: var(--dcb-surface);
}

.demo-card-browser .dcb-toolbar {
    display: flex;
    gap: 0.75rem;
    margin-bottom: 1rem;
    flex-wrap: wrap;
}

.demo-card-browser .dcb-toolbar .dcb-card-search {
    flex: 1;
    min-width: 220px;
}

.demo-card-browser .dcb-sort {
    background: var(--dcb-surface);
    color: var(--dcb-fg);
    border: 1px solid var(--dcb-subtle);
    border-radius: 8px;
    padding: 0.5rem 0.75rem;
    font: inherit;
    cursor: pointer;
}

.demo-card-browser .dcb-status {
    color: var(--dcb-muted);
    margin: 0 0 1rem;
    font-size: 0.9rem;
}

.demo-card-browser .dcb-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 1rem;
}

.demo-card-browser .dcb-tile {
    position: relative;
    display: flex;
    flex-direction: column;
    background: var(--dcb-surface);
    border: 1px solid var(--dcb-subtle);
    border-radius: var(--dcb-radius);
    padding: 0.85rem;
    cursor: pointer;
    transition: transform 200ms, box-shadow 200ms, border-color 200ms;
    text-align: left;
    color: inherit;
    font: inherit;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
}

.demo-card-browser .dcb-tile:hover {
    transform: translateY(-4px);
    box-shadow: 0 16px 30px rgba(238, 28, 37, 0.14);
    border-color: var(--dcb-primary);
}

.demo-card-browser .dcb-tile:focus-visible {
    outline: 2px solid var(--dcb-primary);
    outline-offset: 2px;
}

.demo-card-browser .dcb-tile-image {
    width: 100%;
    aspect-ratio: 1 / 1;
    object-fit: contain;
    background: var(--dcb-soft-bg);
    border-radius: 10px;
    margin-bottom: 0.6rem;
}

.demo-card-browser .dcb-tile-name {
    font-weight: 700;
    line-height: 1.3;
    margin: 0 0 0.25rem;
    font-size: 0.95rem;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

.demo-card-browser .dcb-tile-set {
    font-size: 0.8rem;
    color: var(--dcb-muted);
    margin: 0 0 0.25rem;
}

.demo-card-browser .dcb-tile-rarity {
    font-size: 0.75rem;
    color: var(--dcb-muted);
    margin: 0 0 0.5rem;
}

.demo-card-browser .dcb-tile-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: auto;
    padding-top: 0.4rem;
}

.demo-card-browser .dcb-tile-price {
    font-weight: 700;
    color: var(--dcb-fg);
    font-size: 1.05rem;
}

.demo-card-browser .dcb-tile-trend {
    font-size: 0.75rem;
    padding: 0.15rem 0.5rem;
    border-radius: 999px;
    background: var(--dcb-bg);
}

.demo-card-browser .dcb-tile-trend {
    background: var(--dcb-soft-bg);
}
.demo-card-browser .dcb-tile-trend.up    { color: #15803d; background: #dcfce7; }
.demo-card-browser .dcb-tile-trend.down  { color: #b91c1c; background: #fee2e2; }
.demo-card-browser .dcb-tile-trend.flat  { color: var(--dcb-muted); }

.demo-card-browser .dcb-tile-badge {
    position: absolute;
    top: 0.65rem;
    left: 0.65rem;
    background: var(--dcb-primary);
    color: #ffffff;
    font-size: 0.7rem;
    font-weight: 800;
    padding: 0.25rem 0.6rem;
    border-radius: 999px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    box-shadow: 0 2px 6px rgba(238, 28, 37, 0.3);
}

.demo-card-browser .dcb-tile-badge[data-kind="sealed"] {
    background: var(--dcb-hot);
}

.demo-card-browser .dcb-load-more {
    margin: 1.5rem 0;
    text-align: center;
}

.demo-card-browser .dcb-load-more-btn {
    background: var(--dcb-surface);
    color: var(--dcb-fg);
    border: 1px solid var(--dcb-subtle);
    border-radius: 999px;
    padding: 0.6rem 1.5rem;
    cursor: pointer;
    font: inherit;
}

.demo-card-browser .dcb-load-more-btn:hover {
    border-color: var(--dcb-primary);
}

.demo-card-browser .dcb-modal {
    position: fixed;
    inset: 0;
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}

.demo-card-browser .dcb-modal[hidden] {
    display: none;
}

.demo-card-browser .dcb-modal-backdrop {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(4px);
}

.demo-card-browser .dcb-modal-content {
    position: relative;
    background: var(--dcb-surface);
    border: 1px solid var(--dcb-subtle);
    border-radius: var(--dcb-radius);
    max-width: 720px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    padding: 1.5rem;
    display: grid;
    grid-template-columns: 220px 1fr;
    gap: 1.5rem;
}

@media (max-width: 600px) {
    .demo-card-browser .dcb-modal-content {
        grid-template-columns: 1fr;
    }
}

.demo-card-browser .dcb-modal-close {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    background: transparent;
    border: 0;
    color: var(--dcb-fg);
    font-size: 1.5rem;
    cursor: pointer;
    padding: 0.25rem 0.5rem;
}

.demo-card-browser .dcb-modal-image img {
    width: 100%;
    border-radius: 8px;
    background: var(--dcb-bg);
}

.demo-card-browser .dcb-modal-name {
    margin: 0 0 0.25rem;
    font-size: 1.4rem;
    line-height: 1.2;
}

.demo-card-browser .dcb-modal-sub {
    color: var(--dcb-muted);
    margin: 0 0 1rem;
    font-size: 0.9rem;
}

.demo-card-browser .dcb-variant {
    border-top: 1px solid var(--dcb-subtle);
    padding: 0.6rem 0;
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 0.5rem;
    align-items: center;
}

.demo-card-browser .dcb-variant-label {
    font-size: 0.9rem;
}

.demo-card-browser .dcb-variant-meta {
    color: var(--dcb-muted);
    font-size: 0.8rem;
}

.demo-card-browser .dcb-variant-price {
    font-weight: 700;
    font-size: 1.05rem;
    text-align: right;
}

.demo-card-browser .dcb-variant-spark {
    grid-column: 1 / -1;
    color: var(--dcb-muted);
}

.demo-card-browser .dcb-variant-spark svg {
    width: 100%;
    max-width: 220px;
    height: 32px;
    color: var(--dcb-primary);
}

.demo-card-browser .dcb-modal-link {
    margin-top: 1rem;
}

.demo-card-browser .dcb-modal-link a {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.5rem 1rem;
    background: var(--dcb-bg);
    border: 1px solid var(--dcb-subtle);
    border-radius: 999px;
    color: var(--dcb-fg);
    text-decoration: none;
    font-size: 0.9rem;
}

.demo-card-browser .dcb-modal-link a:hover {
    border-color: var(--dcb-primary);
}

.demo-card-browser .dcb-empty {
    grid-column: 1 / -1;
    text-align: center;
    padding: 3rem 1rem;
    color: var(--dcb-muted);
}

.demo-card-browser .dcb-skeleton {
    background: linear-gradient(90deg, var(--dcb-surface) 0%, var(--dcb-subtle) 50%, var(--dcb-surface) 100%);
    background-size: 200% 100%;
    animation: dcb-shimmer 1.5s linear infinite;
    border-radius: var(--dcb-radius);
    aspect-ratio: 3 / 4;
}

@keyframes dcb-shimmer {
    0%   { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

/* === Mobile filter drawer (Direction B) =========================
   On phones the sidebar's filter stack used to ship open and push the
   card grid almost two screens down the page. Below 900px the sidebar
   is re-skinned into a slide-up bottom sheet, opened from a sticky
   toolbar button, so visitors land on the cards and pull the filters
   up on demand. Desktop (>900px) keeps the original sticky 280px
   sidebar untouched — every rule here is mobile-scoped. */

/* Trigger pill + sheet chrome are mobile-only; hidden on desktop. */
.demo-card-browser .dcb-filter-trigger,
.demo-card-browser .dcb-sheet-head,
.demo-card-browser .dcb-sheet-foot {
    display: none;
}

.demo-card-browser .dcb-drawer-backdrop {
    position: fixed;
    inset: 0;
    z-index: 40;
    background: rgba(0, 0, 0, 0.5);
}
.demo-card-browser .dcb-drawer-backdrop[hidden] {
    display: none;
}

.demo-card-browser .dcb-filter-trigger {
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    background: var(--dcb-surface);
    color: var(--dcb-fg);
    border: 1px solid var(--dcb-subtle);
    border-radius: 8px;
    padding: 0.5rem 0.9rem;
    font: inherit;
    font-weight: 600;
    cursor: pointer;
}
.demo-card-browser .dcb-filter-trigger:hover {
    border-color: var(--dcb-primary);
}
.demo-card-browser .dcb-filter-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 1.4em;
    height: 1.4em;
    padding: 0 0.35em;
    border-radius: 999px;
    background: var(--dcb-primary);
    color: #ffffff;
    font-size: 0.78rem;
    font-weight: 800;
}
.demo-card-browser .dcb-filter-count[hidden] {
    display: none;
}

@media (max-width: 900px) {
    /* Sticky discovery bar: search on its own row, Filters + Sort sharing
       the row below, all pinned so they stay reachable while the grid
       scrolls under them. The site nav scrolls away (not fixed), so
       top:0 is the correct anchor. */
    .demo-card-browser .dcb-toolbar {
        position: sticky;
        top: 0;
        z-index: 30;
        background: var(--dcb-bg);
        padding-top: 0.6rem;
        padding-bottom: 0.6rem;
        box-shadow: 0 6px 10px -6px rgba(0, 0, 0, 0.18);
    }
    .demo-card-browser .dcb-toolbar .dcb-card-search {
        flex: 1 1 100%;
        min-width: 0;
    }
    .demo-card-browser .dcb-filter-trigger {
        display: inline-flex;
        flex: 1 1 0;
        min-height: 44px;
    }
    .demo-card-browser .dcb-sort {
        flex: 1 1 0;
        min-height: 44px;
    }

    /* Sidebar becomes the slide-up bottom sheet. dvh sits after the vh
       fallback so iOS Safari's shrinking toolbar doesn't clip it. */
    .demo-card-browser .dcb-sidebar {
        position: fixed;
        left: 0;
        right: 0;
        bottom: 0;
        top: auto;
        z-index: 50;
        max-height: 85vh;
        max-height: 85dvh;
        display: flex;
        flex-direction: column;
        background: var(--dcb-bg);
        border-top-left-radius: 18px;
        border-top-right-radius: 18px;
        box-shadow: 0 -8px 30px rgba(0, 0, 0, 0.25);
        transform: translateY(100%);
        transition: transform 280ms ease;
        will-change: transform;
    }
    .demo-card-browser .dcb-sidebar.is-open {
        transform: translateY(0);
    }

    /* The filter stack is the single scroll area inside the sheet;
       overscroll-behavior keeps swipes from chaining to the page behind
       it — the reliable half of the iOS scroll-lock, paired with the JS
       body lock. */
    .demo-card-browser .dcb-mobile-toggle {
        flex: 1 1 auto;
        overflow-y: auto;
        overscroll-behavior: contain;
        -webkit-overflow-scrolling: touch;
        background: transparent;
        border: 0;
        border-radius: 0;
        box-shadow: none;
        padding: 0 1rem 1rem;
        margin: 0;
    }
    .demo-card-browser .dcb-mobile-toggle > summary {
        display: none;
    }
    /* Drop the set-list's own scroll so the sheet body is the only one. */
    .demo-card-browser .dcb-set-list {
        max-height: none;
    }

    .demo-card-browser .dcb-sheet-head {
        display: flex;
        position: relative;
        align-items: center;
        justify-content: space-between;
        flex: 0 0 auto;
        padding: 0.9rem 1rem 0.6rem;
    }
    /* Grab-handle affordance centered above the title. */
    .demo-card-browser .dcb-sheet-head::before {
        content: "";
        position: absolute;
        top: 0.5rem;
        left: 50%;
        transform: translateX(-50%);
        width: 36px;
        height: 4px;
        border-radius: 999px;
        background: var(--dcb-border-strong);
    }
    .demo-card-browser .dcb-sheet-title {
        font-weight: 800;
        font-size: 1.1rem;
    }
    .demo-card-browser .dcb-sheet-close {
        background: transparent;
        border: 0;
        color: var(--dcb-fg);
        font-size: 1.6rem;
        line-height: 1;
        cursor: pointer;
        padding: 0 0.5rem;
        min-height: 44px;
        min-width: 44px;
    }

    .demo-card-browser .dcb-sheet-foot {
        display: block;
        flex: 0 0 auto;
        padding: 0.75rem 1rem calc(0.75rem + env(safe-area-inset-bottom));
        border-top: 1px solid var(--dcb-subtle);
        background: var(--dcb-bg);
    }
    .demo-card-browser .dcb-sheet-apply {
        width: 100%;
        background: var(--dcb-primary);
        color: #ffffff;
        border: 0;
        border-radius: 999px;
        padding: 0.85rem 1rem;
        font: inherit;
        font-weight: 700;
        cursor: pointer;
        min-height: 48px;
    }
    .demo-card-browser .dcb-sheet-apply:hover {
        background: #d3171f;
    }

    /* 44px touch targets for filter rows and set rows. */
    .demo-card-browser .dcb-fieldset label,
    .demo-card-browser .dcb-set-list label {
        min-height: 44px;
        padding-top: 0.4rem;
        padding-bottom: 0.4rem;
    }
}

/* Phones: guarantee a 2-up grid instead of one giant card per row, with
   tighter gaps and padding so the card art carries the tile. */
@media (max-width: 560px) {
    .demo-card-browser .dcb-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 0.6rem;
    }
    .demo-card-browser .dcb-tile {
        padding: 0.55rem;
    }
    .demo-card-browser .dcb-tile-name {
        font-size: 0.85rem;
    }
}

@media (prefers-reduced-motion: reduce) {
    .demo-card-browser .dcb-sidebar {
        transition: none;
    }
}
</style>

<script>
(function () {
    'use strict';

    var ROOT = document.querySelector('.demo-card-browser');
    if (!ROOT) return;

    document.body.classList.add('has-card-browser-page');

    var PROXY_BASE = '<?php echo esc_url_raw( rest_url( 'dariobimbi/v1/jtcg' ) ); ?>';

    var state = {
        sets: { pokemon: [], 'pokemon-japan': [] },
        selectedSetId: null,
        cards: [],
        offset: 0,
        total: 0,
        hasMore: false,
        loading: false,
        mode: 'all',
        languages: { pokemon: true, 'pokemon-japan': false },
        rarityFilter: {},
        conditionFilter: {},
        printingFilter: {},
        priceMin: null,
        priceMax: null,
        searchTerm: '',
        sort: 'default',
        setSearch: ''
    };

    /* ===== Helpers ===== */

    function escapeHtml(str) {
        return String(str == null ? '' : str)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    // Inline SVG placeholder used when (a) the card has no tcgplayerId
    // (CardMarket-only listings, JP promos, weird records) or (b) the
    // TCGplayer CDN returns 404 for a real ID. Inline so the demo stays
    // a single self-contained file with no asset dependencies.
    var FALLBACK_IMAGE = 'data:image/svg+xml;utf8,' + encodeURIComponent(
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 240 240">'
        +   '<defs>'
        +     '<linearGradient id="g" x1="0" y1="0" x2="1" y2="1">'
        +       '<stop offset="0%" stop-color="#1f1936"/>'
        +       '<stop offset="100%" stop-color="#0f0a23"/>'
        +     '</linearGradient>'
        +   '</defs>'
        +   '<rect width="240" height="240" rx="14" fill="url(#g)"/>'
        +   '<circle cx="120" cy="104" r="42" fill="none" stroke="#a855f7" stroke-width="3" opacity="0.85"/>'
        +   '<path d="M78 104 H162" stroke="#a855f7" stroke-width="3" opacity="0.85"/>'
        +   '<circle cx="120" cy="104" r="11" fill="#0f0a23" stroke="#a855f7" stroke-width="3"/>'
        +   '<text x="120" y="190" text-anchor="middle" fill="#9aa0b4" font-family="system-ui, -apple-system, Segoe UI, sans-serif" font-size="16" font-weight="600" letter-spacing="0.5">No image</text>'
        + '</svg>'
    );

    function tcgImage(tcgplayerId, size) {
        if (!tcgplayerId) return FALLBACK_IMAGE;
        var dim = size || 400;
        return 'https://tcgplayer-cdn.tcgplayer.com/product/' + encodeURIComponent(tcgplayerId) + '_in_' + dim + 'x' + dim + '.jpg';
    }

    function debounce(fn, ms) {
        var t;
        return function () {
            var args = arguments, ctx = this;
            clearTimeout(t);
            t = setTimeout(function () { fn.apply(ctx, args); }, ms);
        };
    }

    function isSealedCard(card) {
        var variants = card.variants || [];
        for (var i = 0; i < variants.length; i++) {
            if (variants[i].condition === 'Sealed') return true;
        }
        return false;
    }

    function lowestPrice(card) {
        var variants = card.variants || [];
        var min = null;
        for (var i = 0; i < variants.length; i++) {
            var p = variants[i].price;
            if (typeof p === 'number') {
                if (min === null || p < min) min = p;
            }
        }
        return min;
    }

    function bestVariant(card) {
        // The "headline" variant per tile: the cheapest priced one for cards
        // (matches TCGplayer's "from $X" framing) or the only Sealed variant
        // for sealed products.
        var variants = card.variants || [];
        var best = null;
        for (var i = 0; i < variants.length; i++) {
            var v = variants[i];
            if (typeof v.price !== 'number') continue;
            if (!best || v.price < best.price) best = v;
        }
        return best;
    }

    function trendOf(card) {
        var variant = bestVariant(card);
        if (!variant) return null;
        return typeof variant.priceChange7d === 'number' ? variant.priceChange7d : null;
    }

    /* ===== API ===== */

    function api(path, params) {
        var qs = new URLSearchParams();
        Object.keys(params || {}).forEach(function (k) {
            // Avoid the && operator inside inline scripts: WordPress's content
            // filters HTML-encode it to &#038;&#038;, which the JS parser
            // chokes on. Use nested ifs instead. See CLAUDE.md "Inline
            // <script> gotchas inside pattern PHP" for the full story.
            if (params[k] !== '') {
                if (params[k] != null) qs.set(k, params[k]);
            }
        });
        var url = PROXY_BASE + path + (qs.toString() ? '?' + qs.toString() : '');
        return fetch(url, { credentials: 'same-origin' })
            .then(function (r) {
                return r.json().then(function (json) {
                    return { status: r.status, body: json };
                });
            });
    }

    function loadSets() {
        var games = Object.keys(state.languages).filter(function (g) { return state.languages[g]; });
        if (!games.length) {
            state.sets = { pokemon: [], 'pokemon-japan': [] };
            renderSetList();
            return Promise.resolve();
        }
        var promises = games.map(function (g) {
            return api('/sets', { game: g }).then(function (resp) {
                var body = resp.body || {};
                var errMsg = typeof body.error === 'string' ? body.error : '';
                if (resp.status === 503) {
                    if (errMsg.indexOf('JUSTTCG_API_KEY') !== -1) {
                        ROOT.querySelector('.dcb-config-error').hidden = false;
                        return [];
                    }
                }
                if (body._dariobimbi_stale) {
                    ROOT.querySelector('.dcb-stale').hidden = false;
                }
                return body.data || [];
            });
        });
        return Promise.all(promises).then(function (results) {
            games.forEach(function (g, i) {
                state.sets[g] = results[i];
            });
            renderSetList();
        });
    }

    function loadCards(reset) {
        if (!state.selectedSetId) {
            state.cards = [];
            state.hasMore = false;
            renderGrid();
            return Promise.resolve();
        }
        if (reset) {
            state.offset = 0;
            state.cards = [];
        }
        var setEntry = findSetById(state.selectedSetId);
        if (!setEntry) return Promise.resolve();

        state.loading = true;
        renderStatus();

        if (reset) {
            renderSkeletons();
        }

        return api('/cards', {
            game: setEntry.game_id,
            set: setEntry.id,
            offset: state.offset,
            limit: 20
        }).then(function (resp) {
            state.loading = false;
            var body = resp.body || {};
            if (body._dariobimbi_stale) {
                ROOT.querySelector('.dcb-stale').hidden = false;
            }
            var data = body.data || [];
            var meta = body.meta || {};
            state.cards = state.cards.concat(data);
            state.offset = (meta.offset || 0) + (meta.limit || data.length);
            state.total = meta.total || state.cards.length;
            state.hasMore = !!meta.hasMore;
            buildRarityFilter();
            buildConditionFilter();
            buildPrintingFilter();
            renderGrid();
            renderStatus();
        }).catch(function (err) {
            state.loading = false;
            var errMsg = err ? err.message : '';
            renderStatus('Could not load cards. ' + (errMsg || ''));
        });
    }

    function findSetById(id) {
        var games = Object.keys(state.sets);
        for (var i = 0; i < games.length; i++) {
            var arr = state.sets[games[i]];
            for (var j = 0; j < arr.length; j++) {
                if (arr[j].id === id) return arr[j];
            }
        }
        return null;
    }

    /* ===== Rendering ===== */

    function renderSetList() {
        var listEl = ROOT.querySelector('.dcb-set-list');
        var enabledGames = Object.keys(state.languages).filter(function (g) { return state.languages[g]; });
        var combined = [];
        enabledGames.forEach(function (g) {
            (state.sets[g] || []).forEach(function (s) { combined.push(s); });
        });

        if (!combined.length) {
            listEl.innerHTML = '<p class="dcb-set-loading">No sets available for the selected languages.</p>';
            return;
        }

        // Newest set first; null release dates sink to the bottom.
        combined.sort(function (a, b) {
            if (a.release_date) {
                if (b.release_date) {
                    return b.release_date.localeCompare(a.release_date);
                }
                return -1;
            }
            if (b.release_date) return 1;
            return a.name.localeCompare(b.name);
        });

        var term = state.setSearch.toLowerCase();
        var visible = combined;
        if (term) {
            visible = combined.filter(function (s) {
                return s.name.toLowerCase().indexOf(term) !== -1;
            });
        }

        // Auto-pick a default set so the grid (and the dynamic Rarity /
        // Condition / Printing filters that derive their values from the
        // loaded cards) have something to populate from on first paint.
        // Prefer the newest English set with at least one sealed product
        // (matches the Collectr aesthetic this demo riffs on); fall back
        // to plain newest if none of the visible sets have sealed.
        if (!state.selectedSetId) {
            if (combined.length) {
                var defaultSet = combined.find(function (s) {
                    if (s.game_id !== 'pokemon') return false;
                    return s.sealed_count > 0;
                }) || combined[0];
                state.selectedSetId = defaultSet.id;
            }
        }

        listEl.innerHTML = visible.map(function (s) {
            var year = s.release_date ? s.release_date.slice(0, 4) : '·';
            var langTag = s.game_id === 'pokemon-japan' ? '<span class="dcb-set-meta">JP</span>' : '';
            return ''
                + '<label>'
                +     '<input type="radio" name="dcb-set" value="' + escapeHtml(s.id) + '"' + (s.id === state.selectedSetId ? ' checked' : '') + '>'
                +     '<span>' + escapeHtml(s.name) + '</span>'
                +     '<span class="dcb-set-meta">' + escapeHtml(year) + '</span>'
                +     langTag
                + '</label>';
        }).join('');
    }

    function renderSkeletons() {
        var grid = ROOT.querySelector('.dcb-grid');
        var skeletons = '';
        for (var i = 0; i < 8; i++) skeletons += '<div class="dcb-skeleton"></div>';
        grid.innerHTML = skeletons;
    }

    function buildRarityFilter() {
        var seen = {};
        state.cards.forEach(function (c) {
            if (c.rarity) seen[c.rarity] = (seen[c.rarity] || 0) + 1;
        });
        var rarities = Object.keys(seen).sort();
        var fieldset = ROOT.querySelector('[data-fieldset="rarity"]');
        var listEl = ROOT.querySelector('.dcb-rarity-list');

        if (!rarities.length) {
            fieldset.hidden = true;
            return;
        }
        fieldset.hidden = false;

        var current = state.rarityFilter;
        listEl.innerHTML = rarities.map(function (r) {
            var checked = current[r] ? ' checked' : '';
            return '<label><input type="checkbox" name="dcb-rarity" value="' + escapeHtml(r) + '"' + checked + '> '
                + escapeHtml(r) + ' <span class="dcb-set-meta">(' + seen[r] + ')</span></label>';
        }).join('');
    }

    // Condition / Printing filters: same pattern as rarity, but the values
    // live one level deeper (on each variant rather than on the card). Count
    // distinct *cards* per value, not distinct variants — otherwise a card
    // with both Near Mint and Lightly Played versions would inflate both
    // counts and the totals stop adding up to the visible grid.
    function buildVariantFilter(field, fieldsetSel, listSel, checkboxName, currentMap) {
        var seen = {};
        state.cards.forEach(function (c) {
            var values = {};
            (c.variants || []).forEach(function (v) {
                if (v[field]) values[v[field]] = true;
            });
            Object.keys(values).forEach(function (val) {
                seen[val] = (seen[val] || 0) + 1;
            });
        });

        var keys = Object.keys(seen);
        var fieldset = ROOT.querySelector(fieldsetSel);
        var listEl = ROOT.querySelector(listSel);

        if (!keys.length) {
            fieldset.hidden = true;
            return;
        }
        fieldset.hidden = false;

        // Sort: known order first (matches TCGplayer's ordering for both
        // condition and printing), then anything unknown alphabetically.
        var preferredOrder = field === 'condition'
            ? ['Sealed', 'Near Mint', 'Lightly Played', 'Moderately Played', 'Heavily Played', 'Damaged']
            : ['Normal', 'Foil', 'Holofoil', 'Reverse Holofoil', '1st Edition', '1st Edition Holofoil', 'Unlimited', 'Unlimited Holofoil', 'Shadowless'];
        keys.sort(function (a, b) {
            var ia = preferredOrder.indexOf(a);
            var ib = preferredOrder.indexOf(b);
            if (ia !== -1) {
                if (ib !== -1) return ia - ib;
                return -1;
            }
            if (ib !== -1) return 1;
            return a.localeCompare(b);
        });

        listEl.innerHTML = keys.map(function (val) {
            var checked = currentMap[val] ? ' checked' : '';
            return '<label><input type="checkbox" name="' + checkboxName + '" value="' + escapeHtml(val) + '"' + checked + '> '
                + escapeHtml(val) + ' <span class="dcb-set-meta">(' + seen[val] + ')</span></label>';
        }).join('');
    }

    function buildConditionFilter() {
        buildVariantFilter('condition', '[data-fieldset="condition"]', '.dcb-condition-list', 'dcb-condition', state.conditionFilter);
    }

    function buildPrintingFilter() {
        buildVariantFilter('printing', '[data-fieldset="printing"]', '.dcb-printing-list', 'dcb-printing', state.printingFilter);
    }

    function getVisibleCards() {
        var list = state.cards.slice();

        if (state.mode === 'sealed') {
            list = list.filter(isSealedCard);
        } else if (state.mode === 'cards') {
            list = list.filter(function (c) { return !isSealedCard(c); });
        }

        var rarityKeys = Object.keys(state.rarityFilter).filter(function (k) { return state.rarityFilter[k]; });
        if (rarityKeys.length) {
            list = list.filter(function (c) { return rarityKeys.indexOf(c.rarity || '') !== -1; });
        }

        // Condition and Printing both filter at the variant level: a card
        // passes if any of its variants matches any checked value. This
        // matches TCGplayer's behavior — checking "Near Mint" doesn't hide
        // a card that has both NM and LP variants, it just keeps the card.
        var conditionKeys = Object.keys(state.conditionFilter).filter(function (k) { return state.conditionFilter[k]; });
        if (conditionKeys.length) {
            list = list.filter(function (c) {
                var variants = c.variants || [];
                for (var i = 0; i < variants.length; i++) {
                    if (conditionKeys.indexOf(variants[i].condition || '') !== -1) return true;
                }
                return false;
            });
        }

        var printingKeys = Object.keys(state.printingFilter).filter(function (k) { return state.printingFilter[k]; });
        if (printingKeys.length) {
            list = list.filter(function (c) {
                var variants = c.variants || [];
                for (var i = 0; i < variants.length; i++) {
                    if (printingKeys.indexOf(variants[i].printing || '') !== -1) return true;
                }
                return false;
            });
        }

        if (state.priceMin !== null || state.priceMax !== null) {
            list = list.filter(function (c) {
                var p = lowestPrice(c);
                if (p === null) return false;
                if (state.priceMin !== null) {
                    if (p < state.priceMin) return false;
                }
                if (state.priceMax !== null) {
                    if (p > state.priceMax) return false;
                }
                return true;
            });
        }

        if (state.searchTerm) {
            var term = state.searchTerm.toLowerCase();
            list = list.filter(function (c) { return (c.name || '').toLowerCase().indexOf(term) !== -1; });
        }

        list.sort(function (a, b) {
            switch (state.sort) {
                case 'price-desc': {
                    var pa = lowestPrice(a), pb = lowestPrice(b);
                    if (pa === null) {
                        if (pb === null) return 0;
                        return 1;
                    }
                    if (pb === null) return -1;
                    return pb - pa;
                }
                case 'price-asc': {
                    var qa = lowestPrice(a), qb = lowestPrice(b);
                    if (qa === null) {
                        if (qb === null) return 0;
                        return 1;
                    }
                    if (qb === null) return -1;
                    return qa - qb;
                }
                case 'trend-desc': {
                    var ta = trendOf(a), tb = trendOf(b);
                    if (ta === null) {
                        if (tb === null) return 0;
                        return 1;
                    }
                    if (tb === null) return -1;
                    return tb - ta;
                }
                case 'name-asc':
                    return (a.name || '').localeCompare(b.name || '');
                default:
                    return 0;
            }
        });

        return list;
    }

    function renderGrid() {
        var grid = ROOT.querySelector('.dcb-grid');
        var cards = getVisibleCards();

        // Keep the mobile Filters badge and the sheet's "Show N results"
        // button in sync on every grid render (covers filter, search,
        // sort, clear, and set-change paths since they all land here).
        updateFilterBadge();
        updateApplyCount(cards.length);

        if (!cards.length) {
            if (state.loading) return;
            grid.innerHTML = '<p class="dcb-empty">No items match the current filters. Try clearing one of the filters or picking a different set.</p>';
            ROOT.querySelector('.dcb-load-more').hidden = true;
            return;
        }

        grid.innerHTML = cards.map(function (c, idx) {
            var sealed = isSealedCard(c);
            var variant = bestVariant(c);
            var price = variant ? '$' + variant.price.toFixed(2) : '—';
            var trend = trendOf(c);
            var trendCls = 'flat', trendText = '';
            if (typeof trend === 'number') {
                if (trend > 1) { trendCls = 'up';   trendText = '↑ ' + trend.toFixed(1) + '%'; }
                else if (trend < -1) { trendCls = 'down'; trendText = '↓ ' + Math.abs(trend).toFixed(1) + '%'; }
                else { trendCls = 'flat'; trendText = '·'; }
            }
            var imageUrl = tcgImage(c.tcgplayerId, 400);
            // If TCGplayer's CDN 404s on a real ID, swap to the inline SVG
            // fallback rather than hiding the slot, so the grid stays
            // visually rectangular instead of collapsing.
            var imageHtml = '<img class="dcb-tile-image" src="' + escapeHtml(imageUrl)
                + '" alt="' + escapeHtml(c.name || 'card')
                + '" loading="lazy" data-fallback="' + escapeHtml(FALLBACK_IMAGE)
                + '" onerror="if(this.src!==this.dataset.fallback){this.src=this.dataset.fallback;}" />';
            var badge = sealed ? '<span class="dcb-tile-badge" data-kind="sealed">Sealed</span>' : '';
            var rarity = c.rarity || (sealed ? 'Sealed product' : '·');
            var hasNumber = c.number ? c.number !== 'N/A' : false;
            var setLine = (c.set_name || '?') + (hasNumber ? ' · #' + c.number : '');
            return ''
                + '<button type="button" class="dcb-tile" data-card-index="' + idx + '">'
                +     badge
                +     imageHtml
                +     '<p class="dcb-tile-name">' + escapeHtml(c.name || '') + '</p>'
                +     '<p class="dcb-tile-set">' + escapeHtml(setLine) + '</p>'
                +     '<p class="dcb-tile-rarity">' + escapeHtml(rarity) + '</p>'
                +     '<div class="dcb-tile-footer">'
                +         '<span class="dcb-tile-price">' + price + '</span>'
                +         '<span class="dcb-tile-trend ' + trendCls + '">' + escapeHtml(trendText) + '</span>'
                +     '</div>'
                + '</button>';
        }).join('');

        ROOT.querySelector('.dcb-load-more').hidden = !state.hasMore;
    }

    function renderStatus(message) {
        var status = ROOT.querySelector('.dcb-status');
        if (message) { status.textContent = message; return; }
        if (state.loading) { status.textContent = 'Loading…'; return; }
        if (!state.selectedSetId) { status.textContent = 'Pick a set to start browsing.'; return; }
        var setEntry = findSetById(state.selectedSetId);
        var visible = getVisibleCards().length;
        var setName = setEntry ? setEntry.name : '';
        status.textContent = visible + ' of ' + state.cards.length + ' shown'
            + (state.cards.length < state.total ? ' (' + state.total + ' total in set)' : '')
            + (setName ? ' · ' + setName : '');
    }

    /* ===== Filter drawer (mobile) ===== */

    function activeKeys(map) {
        return Object.keys(map).filter(function (k) { return map[k]; });
    }

    // Count of selections that narrow the grid, shown as the (n) badge on
    // the mobile Filters trigger. Language is left out on purpose — it
    // swaps the *source* of sets rather than filtering the current grid.
    function countActiveFilters() {
        var n = 0;
        if (state.mode !== 'all') n += 1;
        n += activeKeys(state.rarityFilter).length;
        n += activeKeys(state.conditionFilter).length;
        n += activeKeys(state.printingFilter).length;
        if (state.priceMin !== null) n += 1;
        if (state.priceMax !== null) n += 1;
        return n;
    }

    function updateFilterBadge() {
        var badge = ROOT.querySelector('.dcb-filter-count');
        if (!badge) return;
        var n = countActiveFilters();
        if (n > 0) {
            badge.textContent = n;
            badge.hidden = false;
        } else {
            badge.hidden = true;
        }
    }

    function updateApplyCount(count) {
        var btn = ROOT.querySelector('.dcb-sheet-apply');
        if (!btn) return;
        var n = typeof count === 'number' ? count : getVisibleCards().length;
        btn.textContent = n === 1 ? 'Show 1 result' : 'Show ' + n + ' results';
    }

    /* ===== Modal ===== */

    var modal = {
        el: ROOT.querySelector('.dcb-modal'),
        previousFocus: null,

        open: function (idx) {
            var cards = getVisibleCards();
            var card = cards[idx];
            if (!card) return;
            modal.previousFocus = document.activeElement;
            modal.render(card);
            modal.el.hidden = false;
            document.body.style.overflow = 'hidden';
            modal.el.querySelector('.dcb-modal-close').focus();
        },

        close: function () {
            modal.el.hidden = true;
            document.body.style.overflow = '';
            if (modal.previousFocus) {
                if (typeof modal.previousFocus.focus === 'function') {
                    modal.previousFocus.focus();
                }
            }
        },

        render: function (card) {
            ROOT.querySelector('.dcb-modal-name').textContent = card.name || '';
            var hasNum = card.number ? card.number !== 'N/A' : false;
            ROOT.querySelector('.dcb-modal-sub').textContent =
                (card.set_name || '?') + (card.rarity ? ' · ' + card.rarity : '') + (hasNum ? ' · #' + card.number : '');

            var imageUrl = tcgImage(card.tcgplayerId, 1000);
            ROOT.querySelector('.dcb-modal-image').innerHTML =
                '<img src="' + escapeHtml(imageUrl)
                + '" alt="' + escapeHtml(card.name || 'card')
                + '" data-fallback="' + escapeHtml(FALLBACK_IMAGE)
                + '" onerror="if(this.src!==this.dataset.fallback){this.src=this.dataset.fallback;}" />';

            var variants = (card.variants || []).slice().sort(function (a, b) {
                if (typeof a.price !== 'number') return 1;
                if (typeof b.price !== 'number') return -1;
                return b.price - a.price;
            });

            var variantsHtml = variants.map(function (v) {
                var price = typeof v.price === 'number' ? '$' + v.price.toFixed(2) : '—';
                var spark = buildSparkline(v.priceHistory || []);
                var change = typeof v.priceChange7d === 'number'
                    ? (v.priceChange7d > 0 ? '+' : '') + v.priceChange7d.toFixed(1) + '% 7d'
                    : '';
                return ''
                    + '<div class="dcb-variant">'
                    +     '<div>'
                    +         '<div class="dcb-variant-label">' + escapeHtml(v.condition || '') + ' · ' + escapeHtml(v.printing || '') + '</div>'
                    +         '<div class="dcb-variant-meta">' + escapeHtml(v.language || '') + (change ? ' · ' + escapeHtml(change) : '') + '</div>'
                    +     '</div>'
                    +     '<div class="dcb-variant-price">' + price + '</div>'
                    +     (spark ? '<div class="dcb-variant-spark">' + spark + '</div>' : '')
                    + '</div>';
            }).join('');

            ROOT.querySelector('.dcb-modal-variants').innerHTML = variantsHtml || '<p class="dcb-variant-meta">No variants with pricing.</p>';

            var linkEl = ROOT.querySelector('.dcb-modal-link');
            if (card.tcgplayerId) {
                var tcgUrl = 'https://www.tcgplayer.com/product/' + encodeURIComponent(card.tcgplayerId);
                linkEl.innerHTML = '<a href="' + escapeHtml(tcgUrl) + '" target="_blank" rel="noopener noreferrer">View on TCGplayer <span aria-hidden="true">↗</span></a>';
            } else {
                linkEl.innerHTML = '';
            }
        }
    };

    /* Bottom-sheet filter drawer. Reuses the sidebar markup wholesale —
       only its skin (CSS) and open/close state are new. Focus + Esc
       handling mirror the card modal's level (focus the close control on
       open, return focus to the trigger on close), kept consistent on
       purpose rather than building a heavier trap on one surface only. */
    var drawer = {
        sidebar: ROOT.querySelector('.dcb-sidebar'),
        backdrop: ROOT.querySelector('.dcb-drawer-backdrop'),
        trigger: ROOT.querySelector('.dcb-filter-trigger'),
        isOpen: false,

        open: function () {
            if (drawer.isOpen) return;
            drawer.isOpen = true;
            drawer.backdrop.hidden = false;
            drawer.sidebar.classList.add('is-open');
            if (drawer.trigger) drawer.trigger.setAttribute('aria-expanded', 'true');
            document.body.style.overflow = 'hidden';
            var closeBtn = ROOT.querySelector('.dcb-sheet-close');
            if (closeBtn) closeBtn.focus();
        },

        close: function () {
            if (!drawer.isOpen) return;
            drawer.isOpen = false;
            drawer.sidebar.classList.remove('is-open');
            drawer.backdrop.hidden = true;
            document.body.style.overflow = '';
            if (drawer.trigger) {
                drawer.trigger.setAttribute('aria-expanded', 'false');
                if (typeof drawer.trigger.focus === 'function') drawer.trigger.focus();
            }
        }
    };

    function buildSparkline(history) {
        if (!history || history.length < 2) return null;
        var points = history.map(function (h) { return h.p; }).filter(function (p) { return typeof p === 'number'; });
        if (points.length < 2) return null;
        var min = Math.min.apply(null, points);
        var max = Math.max.apply(null, points);
        var range = max - min || 1;
        var w = 220, h = 32, pad = 2;
        var step = (w - pad * 2) / (points.length - 1);
        var d = points.map(function (p, i) {
            var x = pad + i * step;
            var y = h - pad - ((p - min) / range) * (h - pad * 2);
            return (i === 0 ? 'M' : 'L') + x.toFixed(1) + ',' + y.toFixed(1);
        }).join(' ');
        return '<svg viewBox="0 0 ' + w + ' ' + h + '" aria-hidden="true">'
            +    '<path d="' + d + '" fill="none" stroke="currentColor" stroke-width="1.5" />'
            + '</svg>';
    }

    /* ===== Wiring ===== */

    ROOT.addEventListener('change', function (e) {
        var target = e.target;
        if (!target) return;
        if (target.name === 'dcb-mode') {
            state.mode = target.value;
            renderGrid();
            renderStatus();
        } else if (target.name === 'dcb-lang') {
            state.languages[target.value] = target.checked;
            state.selectedSetId = null;
            loadSets().then(function () { return loadCards(true); });
        } else if (target.name === 'dcb-set') {
            state.selectedSetId = target.value;
            // Reset variant-level filters when switching sets — the new set's
            // available conditions/printings/rarities will rarely overlap
            // exactly with the previous set, and stale filter state would
            // hide everything.
            state.rarityFilter = {};
            state.conditionFilter = {};
            state.printingFilter = {};
            loadCards(true);
        } else if (target.name === 'dcb-rarity') {
            state.rarityFilter[target.value] = target.checked;
            renderGrid();
            renderStatus();
        } else if (target.name === 'dcb-condition') {
            state.conditionFilter[target.value] = target.checked;
            renderGrid();
            renderStatus();
        } else if (target.name === 'dcb-printing') {
            state.printingFilter[target.value] = target.checked;
            renderGrid();
            renderStatus();
        }
    });

    ROOT.querySelector('.dcb-set-search').addEventListener('input', debounce(function (e) {
        state.setSearch = e.target.value.trim();
        renderSetList();
    }, 150));

    ROOT.querySelector('.dcb-card-search').addEventListener('input', debounce(function (e) {
        state.searchTerm = e.target.value.trim();
        renderGrid();
        renderStatus();
    }, 150));

    ROOT.querySelector('.dcb-sort').addEventListener('change', function (e) {
        state.sort = e.target.value;
        renderGrid();
    });

    ROOT.querySelector('.dcb-price-min').addEventListener('input', debounce(function (e) {
        var v = parseFloat(e.target.value);
        state.priceMin = isFinite(v) ? v : null;
        renderGrid();
        renderStatus();
    }, 200));

    ROOT.querySelector('.dcb-price-max').addEventListener('input', debounce(function (e) {
        var v = parseFloat(e.target.value);
        state.priceMax = isFinite(v) ? v : null;
        renderGrid();
        renderStatus();
    }, 200));

    ROOT.querySelector('.dcb-clear').addEventListener('click', function () {
        state.mode = 'all';
        state.rarityFilter = {};
        state.conditionFilter = {};
        state.printingFilter = {};
        state.priceMin = null;
        state.priceMax = null;
        state.searchTerm = '';
        state.sort = 'default';
        ROOT.querySelector('input[name="dcb-mode"][value="all"]').checked = true;
        ROOT.querySelector('.dcb-card-search').value = '';
        ROOT.querySelector('.dcb-sort').value = 'default';
        ROOT.querySelector('.dcb-price-min').value = '';
        ROOT.querySelector('.dcb-price-max').value = '';
        var unchecks = ROOT.querySelectorAll('input[name="dcb-rarity"], input[name="dcb-condition"], input[name="dcb-printing"]');
        for (var i = 0; i < unchecks.length; i++) unchecks[i].checked = false;
        renderGrid();
        renderStatus();
    });

    ROOT.querySelector('.dcb-load-more-btn').addEventListener('click', function () {
        if (!state.loading) loadCards(false);
    });

    ROOT.addEventListener('click', function (e) {
        var tile = e.target.closest('[data-card-index]');
        if (tile) {
            modal.open(parseInt(tile.dataset.cardIndex, 10));
            return;
        }
        if (e.target.closest('.dcb-filter-trigger')) {
            drawer.open();
            return;
        }
        if (e.target.closest('[data-action="close-drawer"]')) {
            drawer.close();
            return;
        }
        if (e.target.closest('[data-action="close-modal"]')) {
            modal.close();
        }
    });

    // Esc closes whichever overlay is open — modal takes priority since it
    // can be opened from within nothing else, then the filter sheet.
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        if (!modal.el.hidden) {
            e.preventDefault();
            modal.close();
            return;
        }
        if (drawer.isOpen) {
            e.preventDefault();
            drawer.close();
        }
    });

    // If the viewport grows past the mobile breakpoint while the sheet is
    // open (rotate / desktop resize), close it so the body scroll lock and
    // the off-canvas transform don't linger on the now-static sidebar.
    window.addEventListener('resize', function () {
        if (window.innerWidth > 900) {
            if (drawer.isOpen) drawer.close();
        }
    });

    /* ===== Bootstrap ===== */

    loadSets().then(function () {
        if (state.selectedSetId) return loadCards(true);
    });

})();
</script>
