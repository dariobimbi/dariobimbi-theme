<?php
/**
 * Title: Demo — Pokédex
 * Slug: dariobimbi/demo-pokedex
 * Categories: dariobimbi
 * Description: Browse all 1025 Pokémon by generation, see every TCG card for the selected Pokémon, with sort/filter/search and per-card detail with pricing.
 */
?>
<!-- wp:group {"align":"full","className":"section section--surface","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull section section--surface pokemon-full-width" style="padding:var(--wp--preset--spacing--60) var(--wp--preset--spacing--30)">

    <!-- wp:html -->
    <div class="demo-pokedex" data-view="dex" data-gen="1">

        <!-- Layer 1: ambient floating icons (filled in Task 17) -->
        <div class="pdex-ambient" aria-hidden="true">
            <span class="pdex-icon" style="--x:5%;  --d:60s; --delay: -2s;">⚪</span>
            <span class="pdex-icon" style="--x:15%; --d:75s; --delay: -8s;">🔥</span>
            <span class="pdex-icon" style="--x:25%; --d:50s; --delay: -16s;">💧</span>
            <span class="pdex-icon" style="--x:35%; --d:65s; --delay: -28s;">🍃</span>
            <span class="pdex-icon" style="--x:45%; --d:70s; --delay: -32s;">⚡</span>
            <span class="pdex-icon" style="--x:55%; --d:55s; --delay: -42s;">⚪</span>
            <span class="pdex-icon" style="--x:65%; --d:80s; --delay: -10s;">🌙</span>
            <span class="pdex-icon" style="--x:75%; --d:60s; --delay: -22s;">⭐</span>
            <span class="pdex-icon" style="--x:85%; --d:72s; --delay: -38s;">🔥</span>
            <span class="pdex-icon" style="--x:95%; --d:48s; --delay: -50s;">💧</span>
            <span class="pdex-icon" style="--x:10%; --d:90s; --delay: -60s;">🍃</span>
            <span class="pdex-icon" style="--x:50%; --d:78s; --delay: -70s;">⚡</span>
        </div>

        <!-- Page header -->
        <header class="pdex-header">
            <p class="eyebrow">demo</p>
            <h1 class="pdex-title">Pokédex</h1>
            <p class="pdex-subtitle">1025 Pokémon · 9 generations · every card, sortable</p>
        </header>

        <!-- View 1: dex grid -->
        <section class="pdex-view pdex-view--dex" data-view-id="dex" aria-label="Pokédex grid">
            <div class="pdex-controls">
                <div class="pdex-gen-tabs" role="tablist" aria-label="Generation"></div>
                <input type="search" class="pdex-search" placeholder="Search all 1025 Pokémon by name…" aria-label="Search Pokémon by name" />
            </div>
            <div class="pdex-status" role="status" aria-live="polite"></div>
            <div class="pdex-grid pdex-grid--dex" role="tabpanel"></div>
        </section>

        <!-- View 2: selected Pokémon's cards -->
        <section class="pdex-view pdex-view--cards" data-view-id="cards" aria-label="Selected Pokémon cards" hidden>
            <div class="pdex-topbar">
                <a href="#" class="pdex-back" data-action="back">← Pokédex</a>
                <span class="pdex-topbar-title"></span>
            </div>
            <div class="pdex-hero"></div>
            <div class="pdex-card-controls">
                <label class="pdex-control">
                    <span>Sort</span>
                    <select data-control="sort">
                        <option value="release-desc">Release date · Newest first</option>
                        <option value="release-asc">Release date · Oldest first</option>
                        <option value="trending">Trending · Hot right now</option>
                        <option value="price-desc">Price · High to low</option>
                        <option value="price-asc">Price · Low to high</option>
                        <option value="rarity-desc">Rarity · High to low</option>
                        <option value="set-number">Set + card number</option>
                    </select>
                </label>
                <label class="pdex-control">
                    <span>Set</span>
                    <select data-control="set">
                        <option value="">All sets</option>
                    </select>
                </label>
                <label class="pdex-control">
                    <span>Rarity</span>
                    <select data-control="rarity">
                        <option value="">All rarities</option>
                    </select>
                </label>
                <input type="search" class="pdex-card-search" data-control="card-search" placeholder="Filter by card name…" aria-label="Filter cards by name" />
            </div>
            <div class="pdex-card-status" role="status" aria-live="polite"></div>
            <div class="pdex-grid pdex-grid--cards"></div>
        </section>

        <!-- View 3: card detail modal -->
        <div class="pdex-modal" role="dialog" aria-modal="true" aria-labelledby="pdex-modal-title" hidden>
            <div class="pdex-modal-backdrop" data-action="close-modal"></div>
            <div class="pdex-modal-content">
                <button type="button" class="pdex-modal-close" data-action="close-modal" aria-label="Close">×</button>
                <button type="button" class="pdex-modal-prev" data-action="prev-card" aria-label="Previous card">‹</button>
                <button type="button" class="pdex-modal-next" data-action="next-card" aria-label="Next card">›</button>
                <div class="pdex-modal-image"></div>
                <div class="pdex-modal-details">
                    <h2 id="pdex-modal-title" class="pdex-modal-name"></h2>
                    <div class="pdex-modal-pricing"></div>
                    <div class="pdex-modal-body"></div>
                </div>
            </div>
        </div>

    </div>
    <!-- /wp:html -->

</div>
<!-- /wp:group -->

<style>
/* === Pokédex demo, scoped under .demo-pokedex === */

.demo-pokedex {
    --pdex-gap: 1rem;
    --pdex-radius: 14px;
    --pdex-card-bg: var(--wp--preset--color--surface);
    --pdex-border: var(--wp--preset--color--subtle);
    --pdex-fg: var(--wp--preset--color--foreground);
    --pdex-muted: var(--wp--preset--color--muted);
    --pdex-accent: var(--wp--preset--color--accent);
    --pdex-primary: var(--wp--preset--color--primary);
    --pdex-hot: var(--wp--preset--color--hot);
    position: relative;
    isolation: isolate;
}

/* Paint html and body with the surface color so no navy bleeds through at
   the viewport edges. The .has-pokedex-page class is applied by the
   demo's script so this works even on browsers without :has(), and the
   !important wins over the body bg WordPress emits from
   theme.json styles.color.background and the .is-layout-constrained
   margin: auto !important rule. */
html:has(.demo-pokedex),
body.has-pokedex-page,
body:has(.demo-pokedex) {
    background-color: var(--wp--preset--color--surface) !important;
}

.demo-pokedex .pdex-header {
    text-align: center;
    margin-bottom: var(--wp--preset--spacing--40);
}

.demo-pokedex .pdex-title {
    font-size: clamp(2.5rem, 1.5rem + 4vw, 5rem);
    font-weight: 800;
    background: linear-gradient(135deg, var(--pdex-primary) 0%, var(--pdex-accent) 100%);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    margin: 0.25rem 0;
}

.demo-pokedex .pdex-subtitle {
    color: var(--pdex-muted);
    margin: 0;
}

.demo-pokedex .pdex-controls {
    display: flex;
    flex-direction: column;
    gap: var(--pdex-gap);
    margin-bottom: var(--wp--preset--spacing--30);
}

.demo-pokedex .pdex-gen-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
}

.demo-pokedex .pdex-search,
.demo-pokedex .pdex-card-search {
    width: 100%;
    padding: 0.7rem 1rem;
    background: var(--pdex-card-bg);
    color: var(--pdex-fg);
    border: 1px solid var(--pdex-border);
    border-radius: 999px;
    font: inherit;
}

.demo-pokedex .pdex-search:focus,
.demo-pokedex .pdex-card-search:focus {
    outline: 2px solid var(--pdex-primary);
    outline-offset: 2px;
}

.demo-pokedex .pdex-grid--dex {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: var(--pdex-gap);
}

.demo-pokedex .pdex-grid--cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: var(--pdex-gap);
}

@media (max-width: 480px) {
    .demo-pokedex .pdex-grid--dex { grid-template-columns: repeat(2, 1fr); }
    .demo-pokedex .pdex-grid--cards { grid-template-columns: repeat(2, 1fr); }
}

.demo-pokedex .pdex-view[hidden] { display: none; }

.demo-pokedex .pdex-modal[hidden] { display: none; }

.demo-pokedex .pdex-modal {
    position: fixed;
    inset: 0;
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.demo-pokedex .pdex-modal-backdrop {
    position: absolute;
    inset: 0;
    background: rgba(10, 14, 26, 0.85);
    backdrop-filter: blur(4px);
}

.demo-pokedex .pdex-modal-content {
    position: relative;
    background: var(--pdex-card-bg);
    color: var(--pdex-fg);
    border: 1px solid var(--pdex-border);
    border-radius: var(--pdex-radius);
    width: min(960px, 95vw);
    max-height: 90vh;
    overflow: hidden;
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    gap: 1.5rem;
    padding: 1.5rem;
    box-sizing: border-box;
}

.demo-pokedex .pdex-modal-image {
    min-height: 0;
    display: flex;
    align-items: flex-start;
    justify-content: center;
}

.demo-pokedex .pdex-modal-details {
    min-height: 0;
    overflow-y: auto;
    padding-right: 0.25rem;
    scrollbar-width: thin;
}

@media (max-width: 720px) {
    .demo-pokedex .pdex-modal-content { grid-template-columns: 1fr; }
}

.demo-pokedex .pdex-modal-close,
.demo-pokedex .pdex-modal-prev,
.demo-pokedex .pdex-modal-next {
    position: absolute;
    background: transparent;
    color: var(--pdex-fg);
    border: 1px solid var(--pdex-border);
    border-radius: 999px;
    width: 40px;
    height: 40px;
    cursor: pointer;
    z-index: 1;
}

.demo-pokedex .pdex-modal-close { top: 0.75rem; right: 0.75rem; }
.demo-pokedex .pdex-modal-prev  { top: 50%; left: -50px; transform: translateY(-50%); }
.demo-pokedex .pdex-modal-next  { top: 50%; right: -50px; transform: translateY(-50%); }

@media (max-width: 1080px) {
    .demo-pokedex .pdex-modal-prev { left: 0.5rem; }
    .demo-pokedex .pdex-modal-next { right: 0.5rem; }
}

.demo-pokedex .pdex-gen-tab {
    padding: 0.5rem 1rem;
    background: var(--pdex-card-bg);
    color: var(--pdex-fg);
    border: 1px solid var(--pdex-border);
    border-radius: 999px;
    font: inherit;
    cursor: pointer;
    transition: background 200ms, border-color 200ms;
}

.demo-pokedex .pdex-gen-tab[aria-selected="true"] {
    background: linear-gradient(135deg, var(--pdex-primary) 0%, var(--pdex-accent) 100%);
    color: #fff;
    border-color: transparent;
}

.demo-pokedex .pdex-gen-tab:focus-visible {
    outline: 2px solid var(--pdex-primary);
    outline-offset: 2px;
}

.demo-pokedex .pdex-tile {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 0.75rem 0.5rem;
    background: var(--pdex-card-bg);
    color: var(--pdex-fg);
    border: 1px solid var(--pdex-border);
    border-radius: var(--pdex-radius);
    text-decoration: none;
    transition: transform 200ms, border-color 200ms, box-shadow 200ms;
}

.demo-pokedex .pdex-tile:hover {
    transform: translateY(-3px);
    border-color: var(--pdex-primary);
    box-shadow: 0 8px 24px rgba(59, 130, 246, 0.15);
}

.demo-pokedex .pdex-tile-sprite {
    width: 96px;
    height: 96px;
    object-fit: contain;
    image-rendering: auto;
}

.demo-pokedex .pdex-tile-num {
    font-family: var(--wp--preset--font-family--mono);
    font-size: 0.75rem;
    color: var(--pdex-muted);
}

.demo-pokedex .pdex-tile-name {
    font-weight: 700;
    text-transform: capitalize;
    margin: 0.25rem 0 0;
}

.demo-pokedex .pdex-topbar {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: var(--wp--preset--spacing--30);
}

.demo-pokedex .pdex-back {
    color: var(--pdex-fg);
    text-decoration: none;
    border: 1px solid var(--pdex-border);
    padding: 0.45rem 1rem;
    border-radius: 999px;
    transition: background 200ms;
}

.demo-pokedex .pdex-back:hover { background: var(--pdex-card-bg); }

.demo-pokedex .pdex-topbar-title {
    font-family: var(--wp--preset--font-family--mono);
    color: var(--pdex-muted);
}

.demo-pokedex .pdex-hero {
    display: grid;
    grid-template-columns: 220px 1fr;
    gap: 1.5rem;
    background: var(--pdex-card-bg);
    border: 1px solid var(--pdex-border);
    border-radius: var(--pdex-radius);
    padding: 1.25rem;
    margin-bottom: var(--wp--preset--spacing--30);
    position: relative;
    overflow: hidden;
}

@media (max-width: 640px) {
    .demo-pokedex .pdex-hero { grid-template-columns: 1fr; text-align: center; }
}

.demo-pokedex .pdex-hero-image {
    width: 100%;
    aspect-ratio: 1;
    object-fit: contain;
}

.demo-pokedex .pdex-hero-name {
    font-size: clamp(2rem, 1.5rem + 2vw, 3rem);
    margin: 0;
}

.demo-pokedex .pdex-hero-num {
    font-family: var(--wp--preset--font-family--mono);
    color: var(--pdex-muted);
}

.demo-pokedex .pdex-type-badges {
    display: flex;
    gap: 0.4rem;
    margin: 0.5rem 0 1rem;
    flex-wrap: wrap;
}

.demo-pokedex .pdex-type-badge {
    padding: 0.25rem 0.7rem;
    border-radius: 999px;
    font-size: 0.85rem;
    font-weight: 700;
    text-transform: capitalize;
    color: #0a0e1a;
    background: #888;
}

.demo-pokedex .pdex-type-badge[data-type="normal"]   { background: #a8a878; color: #fff; }
.demo-pokedex .pdex-type-badge[data-type="fire"]     { background: #f08030; color: #fff; }
.demo-pokedex .pdex-type-badge[data-type="water"]    { background: #6890f0; color: #fff; }
.demo-pokedex .pdex-type-badge[data-type="electric"] { background: #f8d030; color: #000; }
.demo-pokedex .pdex-type-badge[data-type="grass"]    { background: #78c850; color: #fff; }
.demo-pokedex .pdex-type-badge[data-type="ice"]      { background: #98d8d8; color: #000; }
.demo-pokedex .pdex-type-badge[data-type="fighting"] { background: #c03028; color: #fff; }
.demo-pokedex .pdex-type-badge[data-type="poison"]   { background: #a040a0; color: #fff; }
.demo-pokedex .pdex-type-badge[data-type="ground"]   { background: #e0c068; color: #000; }
.demo-pokedex .pdex-type-badge[data-type="flying"]   { background: #a890f0; color: #fff; }
.demo-pokedex .pdex-type-badge[data-type="psychic"]  { background: #f85888; color: #fff; }
.demo-pokedex .pdex-type-badge[data-type="bug"]      { background: #a8b820; color: #fff; }
.demo-pokedex .pdex-type-badge[data-type="rock"]     { background: #b8a038; color: #fff; }
.demo-pokedex .pdex-type-badge[data-type="ghost"]    { background: #705898; color: #fff; }
.demo-pokedex .pdex-type-badge[data-type="dragon"]   { background: #7038f8; color: #fff; }
.demo-pokedex .pdex-type-badge[data-type="dark"]     { background: #705848; color: #fff; }
.demo-pokedex .pdex-type-badge[data-type="steel"]    { background: #b8b8d0; color: #000; }
.demo-pokedex .pdex-type-badge[data-type="fairy"]    { background: #ee99ac; color: #000; }

.demo-pokedex .pdex-hero-stats {
    display: flex;
    gap: 1.5rem;
    color: var(--pdex-muted);
    font-size: 0.9rem;
}

.demo-pokedex .pdex-card-controls {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    align-items: end;
    margin-bottom: var(--wp--preset--spacing--30);
}

.demo-pokedex .pdex-control {
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
    font-size: 0.85rem;
    color: var(--pdex-muted);
}

.demo-pokedex .pdex-control select {
    background: var(--pdex-card-bg);
    color: var(--pdex-fg);
    border: 1px solid var(--pdex-border);
    border-radius: 8px;
    padding: 0.5rem 0.75rem;
    font: inherit;
}

.demo-pokedex .pdex-card-search { flex: 1; min-width: 220px; }

.demo-pokedex .pdex-card {
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
    background: var(--pdex-card-bg);
    border: 1px solid var(--pdex-border);
    border-radius: var(--pdex-radius);
    padding: 0.5rem;
    cursor: pointer;
    transition: transform 200ms, box-shadow 200ms;
}

.demo-pokedex .pdex-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 32px rgba(168, 85, 247, 0.18);
}

.demo-pokedex .pdex-card-image {
    width: 100%;
    aspect-ratio: 245 / 342;
    object-fit: contain;
    border-radius: 8px;
}

.demo-pokedex .pdex-card-meta {
    font-size: 0.8rem;
    color: var(--pdex-muted);
}

.demo-pokedex .pdex-card-meta strong {
    color: var(--pdex-fg);
    font-weight: 600;
}

.demo-pokedex .pdex-card-footer {
    margin-top: auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.4rem;
    padding-top: 0.4rem;
    border-top: 1px solid var(--pdex-border);
}

.demo-pokedex .pdex-card-price {
    font-weight: 700;
    font-size: 0.95rem;
    color: var(--pdex-fg);
}

.demo-pokedex .pdex-card-link {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.7rem;
    font-weight: 600;
    letter-spacing: 0.03em;
    text-decoration: none;
    color: var(--pdex-muted);
    border: 1px solid var(--pdex-border);
    border-radius: 999px;
    padding: 0.2rem 0.55rem;
    transition: color 150ms, border-color 150ms, background 150ms, transform 150ms;
}
.demo-pokedex .pdex-card-link:hover,
.demo-pokedex .pdex-card-link:focus-visible {
    color: var(--pdex-fg);
    border-color: var(--pdex-primary);
    background: rgba(255, 255, 255, 0.04);
    transform: translateY(-1px);
}
.demo-pokedex .pdex-card-link:focus-visible {
    outline: 2px solid var(--pdex-primary);
    outline-offset: 2px;
}

.demo-pokedex .pdex-modal-image img {
    max-width: 100%;
    max-height: calc(90vh - 3rem);
    width: auto;
    height: auto;
    object-fit: contain;
    border-radius: 8px;
}

.demo-pokedex .pdex-modal-name {
    font-size: 1.6rem;
    margin: 0 2rem 0.5rem 0;
}

.demo-pokedex .pdex-modal-body dl {
    display: grid;
    grid-template-columns: 110px 1fr;
    gap: 0.4rem 0.75rem;
    margin: 0.5rem 0;
}

.demo-pokedex .pdex-modal-body dt {
    color: var(--pdex-muted);
    font-size: 0.85rem;
}

.demo-pokedex .pdex-modal-body dd {
    margin: 0;
    font-size: 0.95rem;
}

.demo-pokedex .pdex-attack {
    border-top: 1px solid var(--pdex-border);
    padding: 0.6rem 0;
}

.demo-pokedex .pdex-attack:first-child { border-top: 0; padding-top: 0; }

.demo-pokedex .pdex-attack-head {
    display: flex;
    justify-content: space-between;
    font-weight: 700;
}

.demo-pokedex .pdex-attack-cost {
    font-family: var(--wp--preset--font-family--mono);
    font-size: 0.8rem;
    color: var(--pdex-muted);
}

.demo-pokedex .pdex-attack-text {
    font-size: 0.85rem;
    color: var(--pdex-muted);
    margin: 0.2rem 0 0;
}

/* Accordion — collapsible sections for Details / Abilities / Attacks. */
.demo-pokedex .pdex-acc {
    border-top: 1px solid var(--pdex-border);
}
.demo-pokedex .pdex-acc:last-of-type {
    border-bottom: 1px solid var(--pdex-border);
}

.demo-pokedex .pdex-acc summary {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    padding: 0.7rem 0.25rem;
    cursor: pointer;
    list-style: none;
    font-weight: 600;
    color: var(--pdex-fg);
    transition: color 150ms ease, background 150ms ease;
}
.demo-pokedex .pdex-acc summary::-webkit-details-marker { display: none; }
.demo-pokedex .pdex-acc summary:hover { color: var(--pdex-primary); }
.demo-pokedex .pdex-acc summary:focus-visible {
    outline: 2px solid var(--pdex-primary);
    outline-offset: 2px;
    border-radius: 4px;
}

.demo-pokedex .pdex-acc-label {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.demo-pokedex .pdex-acc-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 1.4rem;
    height: 1.4rem;
    padding: 0 0.4rem;
    border-radius: 999px;
    background: var(--pdex-border);
    color: var(--pdex-muted);
    font-size: 0.75rem;
    font-weight: 600;
}

.demo-pokedex .pdex-acc-chevron {
    display: inline-block;
    font-size: 1.1rem;
    color: var(--pdex-muted);
    transition: transform 200ms ease;
}
.demo-pokedex .pdex-acc[open] > summary > .pdex-acc-chevron {
    transform: rotate(90deg);
}

.demo-pokedex .pdex-acc-body {
    padding: 0.25rem 0.25rem 0.85rem;
    animation: pdex-acc-slide 200ms ease;
}

@keyframes pdex-acc-slide {
    from { opacity: 0; transform: translateY(-4px); }
    to   { opacity: 1; transform: translateY(0); }
}

@media (prefers-reduced-motion: reduce) {
    .demo-pokedex .pdex-acc-body { animation: none; }
    .demo-pokedex .pdex-acc-chevron { transition: none; }
}

/* Pricing block — promoted to right under the title, treated as the headline. */
.demo-pokedex .pdex-modal-pricing {
    margin: 0 0 1rem;
    padding: 0.9rem 1rem;
    background: linear-gradient(135deg, rgba(255,255,255,0.03), rgba(255,255,255,0));
    border: 1px solid var(--pdex-border);
    border-left: 3px solid var(--type-accent, var(--pdex-primary));
    border-radius: var(--pdex-radius);
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    transition: border-left-color 400ms ease;
}

@media (max-width: 540px) {
    .demo-pokedex .pdex-modal-pricing { grid-template-columns: 1fr; }
}

.demo-pokedex .pdex-price-block h4 {
    font-size: 0.85rem;
    color: var(--pdex-muted);
    margin: 0 0 0.25rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.demo-pokedex .pdex-price-headline {
    font-size: 1.9rem;
    font-weight: 800;
    margin: 0;
    line-height: 1.1;
    background: linear-gradient(135deg, var(--pdex-fg) 0%, var(--type-accent, var(--pdex-primary)) 120%);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    letter-spacing: -0.01em;
}

.demo-pokedex .pdex-price-variant {
    font-size: 0.8rem;
    color: var(--pdex-muted);
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.demo-pokedex .pdex-variant-count {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    padding: 0.15rem 0.5rem;
    border-radius: 999px;
    background: color-mix(in srgb, var(--type-accent, var(--pdex-primary)) 15%, transparent);
    color: var(--type-accent, var(--pdex-primary));
    border: 1px solid color-mix(in srgb, var(--type-accent, var(--pdex-primary)) 35%, transparent);
    font-weight: 600;
}

.demo-pokedex .pdex-variant-list {
    list-style: none;
    margin: 0.7rem 0 0;
    padding: 0;
    display: grid;
    gap: 0.35rem;
}

.demo-pokedex .pdex-variant-row {
    display: grid;
    grid-template-columns: 1fr auto;
    grid-template-areas:
        "name price"
        "meta meta";
    align-items: baseline;
    gap: 0.15rem 0.75rem;
    padding: 0.55rem 0.7rem;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid var(--pdex-border);
    border-radius: 8px;
}

.demo-pokedex .pdex-variant-name {
    grid-area: name;
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--pdex-fg);
}

.demo-pokedex .pdex-variant-price {
    grid-area: price;
    font-variant-numeric: tabular-nums;
    font-weight: 700;
    color: var(--type-accent, var(--pdex-primary));
}

.demo-pokedex .pdex-variant-meta {
    grid-area: meta;
    font-size: 0.75rem;
    color: var(--pdex-muted);
    font-variant-numeric: tabular-nums;
}

.demo-pokedex .pdex-card-variants {
    display: inline-block;
    margin-left: 0.4rem;
    font-size: 0.7rem;
    font-weight: 600;
    padding: 0.05rem 0.4rem;
    border-radius: 999px;
    background: color-mix(in srgb, var(--type-accent, var(--pdex-primary)) 18%, transparent);
    color: var(--type-accent, var(--pdex-primary));
    border: 1px solid color-mix(in srgb, var(--type-accent, var(--pdex-primary)) 35%, transparent);
    vertical-align: middle;
}

.demo-pokedex .pdex-price-range {
    font-size: 0.85rem;
    color: var(--pdex-muted);
    margin-top: 0.25rem;
}

.demo-pokedex .pdex-sparkline {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 0.5rem;
}

.demo-pokedex .pdex-sparkline svg {
    width: 90px;
    height: 30px;
}

.demo-pokedex .pdex-sparkline-arrow.up   { color: #10b981; }
.demo-pokedex .pdex-sparkline-arrow.down { color: #ef4444; }
.demo-pokedex .pdex-sparkline-arrow.flat { color: var(--pdex-muted); }

.demo-pokedex .pdex-modal-tcg-link {
    grid-column: 1 / -1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    margin-top: 0.5rem;
    padding: 0.55rem 1rem;
    border-radius: 999px;
    background: var(--type-accent, var(--pdex-primary));
    color: #0a0e1a;
    font-weight: 700;
    font-size: 0.85rem;
    letter-spacing: 0.02em;
    text-decoration: none;
    transition: filter 150ms, transform 150ms;
}
.demo-pokedex .pdex-modal-tcg-link:hover,
.demo-pokedex .pdex-modal-tcg-link:focus-visible {
    filter: brightness(1.1);
    transform: translateY(-1px);
}
.demo-pokedex .pdex-modal-tcg-link:focus-visible {
    outline: 2px solid var(--pdex-fg);
    outline-offset: 2px;
}

.demo-pokedex .pdex-no-pricing {
    grid-column: 1 / -1;
    color: var(--pdex-muted);
    font-size: 0.9rem;
    text-align: center;
}

.demo-pokedex .pdex-ambient {
    position: absolute;
    inset: 0;
    overflow: hidden;
    pointer-events: none;
    z-index: 0;
}

.demo-pokedex .pdex-ambient .pdex-icon {
    position: absolute;
    left: var(--x, 50%);
    bottom: -10%;
    font-size: 2rem;
    opacity: 0.08;
    will-change: transform;
    animation: pdex-drift var(--d, 60s) linear infinite;
    animation-delay: var(--delay, 0s);
}

@keyframes pdex-drift {
    from { transform: translate3d(0, 0, 0); }
    to   { transform: translate3d(20px, -120vh, 0); }
}

.demo-pokedex .pdex-header,
.demo-pokedex .pdex-view { position: relative; z-index: 1; }
.demo-pokedex .pdex-modal { z-index: 1000; }

/* Tint paints on the alignfull parent so it spans the viewport. The
   per-gen and per-type CSS variables live on the parent and inherit down
   to .demo-pokedex, so .pdex-hero::before still reads --type-glow. */
.pokemon-full-width:has(.demo-pokedex) {
    isolation: isolate;
    overflow: hidden;
}

.pokemon-full-width:has(.demo-pokedex)::before {
    content: '';
    position: absolute;
    inset: 0;
    background: var(--gen-tint, transparent);
    opacity: 0.5;
    z-index: 0;
    pointer-events: none;
    transition: background 600ms ease;
}

.pokemon-full-width:has(.demo-pokedex[data-gen="1"]) { --gen-tint: radial-gradient(circle at 30% 0%, rgba(220, 40, 50, 0.18), transparent 60%); }
.pokemon-full-width:has(.demo-pokedex[data-gen="2"]) { --gen-tint: radial-gradient(circle at 70% 0%, rgba(218, 165, 32, 0.18), transparent 60%); }
.pokemon-full-width:has(.demo-pokedex[data-gen="3"]) { --gen-tint: linear-gradient(120deg, rgba(220, 60, 60, 0.14), rgba(60, 120, 200, 0.14)); }
.pokemon-full-width:has(.demo-pokedex[data-gen="4"]) { --gen-tint: linear-gradient(120deg, rgba(80, 200, 220, 0.14), rgba(240, 130, 200, 0.14)); }
.pokemon-full-width:has(.demo-pokedex[data-gen="5"]) { --gen-tint: radial-gradient(circle at 50% 0%, rgba(120, 130, 150, 0.14), transparent 60%); }
.pokemon-full-width:has(.demo-pokedex[data-gen="6"]) { --gen-tint: linear-gradient(120deg, rgba(40, 120, 220, 0.14), rgba(220, 50, 50, 0.14)); }
.pokemon-full-width:has(.demo-pokedex[data-gen="7"]) { --gen-tint: linear-gradient(120deg, rgba(240, 150, 30, 0.14), rgba(140, 80, 200, 0.14)); }
.pokemon-full-width:has(.demo-pokedex[data-gen="8"]) { --gen-tint: linear-gradient(120deg, rgba(60, 180, 200, 0.14), rgba(220, 60, 180, 0.14)); }
.pokemon-full-width:has(.demo-pokedex[data-gen="9"]) { --gen-tint: linear-gradient(120deg, rgba(220, 50, 30, 0.14), rgba(140, 30, 220, 0.14)); }

.pokemon-full-width:has(.demo-pokedex.type-fire)     { --type-glow: rgba(240, 128, 48, 0.25); }
.pokemon-full-width:has(.demo-pokedex.type-water)    { --type-glow: rgba(104, 144, 240, 0.25); }
.pokemon-full-width:has(.demo-pokedex.type-electric) { --type-glow: rgba(248, 208, 48, 0.25); }
.pokemon-full-width:has(.demo-pokedex.type-grass)    { --type-glow: rgba(120, 200, 80, 0.25); }
.pokemon-full-width:has(.demo-pokedex.type-ice)      { --type-glow: rgba(152, 216, 216, 0.25); }
.pokemon-full-width:has(.demo-pokedex.type-fighting) { --type-glow: rgba(192, 48, 40, 0.25); }
.pokemon-full-width:has(.demo-pokedex.type-poison)   { --type-glow: rgba(160, 64, 160, 0.25); }
.pokemon-full-width:has(.demo-pokedex.type-ground)   { --type-glow: rgba(224, 192, 104, 0.25); }
.pokemon-full-width:has(.demo-pokedex.type-flying)   { --type-glow: rgba(168, 144, 240, 0.25); }
.pokemon-full-width:has(.demo-pokedex.type-psychic)  { --type-glow: rgba(248, 88, 136, 0.25); }
.pokemon-full-width:has(.demo-pokedex.type-bug)      { --type-glow: rgba(168, 184, 32, 0.25); }
.pokemon-full-width:has(.demo-pokedex.type-rock)     { --type-glow: rgba(184, 160, 56, 0.25); }
.pokemon-full-width:has(.demo-pokedex.type-ghost)    { --type-glow: rgba(112, 88, 152, 0.30); }
.pokemon-full-width:has(.demo-pokedex.type-dragon)   { --type-glow: rgba(112, 56, 248, 0.25); }
.pokemon-full-width:has(.demo-pokedex.type-dark)     { --type-glow: rgba(112, 88, 72, 0.30); }
.pokemon-full-width:has(.demo-pokedex.type-steel)    { --type-glow: rgba(184, 184, 208, 0.25); }
.pokemon-full-width:has(.demo-pokedex.type-fairy)    { --type-glow: rgba(238, 153, 172, 0.25); }
.pokemon-full-width:has(.demo-pokedex.type-normal)   { --type-glow: rgba(168, 168, 120, 0.25); }

.demo-pokedex .pdex-hero::before {
    content: '';
    position: absolute;
    inset: -40%;
    background: radial-gradient(circle, var(--type-glow, transparent) 0%, transparent 65%);
    z-index: -1;
    transition: background 400ms ease;
}

.demo-pokedex .pdex-hero { z-index: 1; }
.demo-pokedex .pdex-hero > * { position: relative; z-index: 1; }

/* Saturated type accent (used for borders, focus, highlights in cards view) */
.demo-pokedex.type-fire     { --type-accent: #f08030; }
.demo-pokedex.type-water    { --type-accent: #6890f0; }
.demo-pokedex.type-electric { --type-accent: #f8d030; }
.demo-pokedex.type-grass    { --type-accent: #78c850; }
.demo-pokedex.type-ice      { --type-accent: #98d8d8; }
.demo-pokedex.type-fighting { --type-accent: #c03028; }
.demo-pokedex.type-poison   { --type-accent: #a040a0; }
.demo-pokedex.type-ground   { --type-accent: #e0c068; }
.demo-pokedex.type-flying   { --type-accent: #a890f0; }
.demo-pokedex.type-psychic  { --type-accent: #f85888; }
.demo-pokedex.type-bug      { --type-accent: #a8b820; }
.demo-pokedex.type-rock     { --type-accent: #b8a038; }
.demo-pokedex.type-ghost    { --type-accent: #8b6cb8; }
.demo-pokedex.type-dragon   { --type-accent: #7038f8; }
.demo-pokedex.type-dark     { --type-accent: #8a6f5e; }
.demo-pokedex.type-steel    { --type-accent: #b8b8d0; }
.demo-pokedex.type-fairy    { --type-accent: #ee99ac; }
.demo-pokedex.type-normal   { --type-accent: #a8a878; }

/* When viewing one Pokémon's cards, layer a type-tinted glow over the gen tint */
.pokemon-full-width:has(.demo-pokedex[data-view="cards"])::before {
    background:
        radial-gradient(ellipse at 50% 0%, var(--type-glow, transparent) 0%, transparent 55%),
        var(--gen-tint, transparent);
    opacity: 0.9;
}

/* Cards view: drive accents from the Pokémon's primary type */
.demo-pokedex[data-view="cards"] .pdex-hero {
    border-color: var(--type-accent, var(--pdex-border));
    box-shadow: 0 0 0 1px var(--type-accent, transparent),
                0 18px 48px var(--type-glow, transparent);
}

.demo-pokedex[data-view="cards"] .pdex-hero-name {
    background: linear-gradient(135deg, var(--type-accent, var(--pdex-primary)) 0%, var(--pdex-fg) 95%);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
}

.demo-pokedex[data-view="cards"] .pdex-card:hover {
    border-color: var(--type-accent, var(--pdex-border));
    box-shadow: 0 12px 32px var(--type-glow, rgba(168, 85, 247, 0.18));
}

.demo-pokedex[data-view="cards"] .pdex-control select:focus,
.demo-pokedex[data-view="cards"] .pdex-card-search:focus {
    outline-color: var(--type-accent, var(--pdex-primary));
}

.demo-pokedex[data-view="cards"] .pdex-back:hover {
    border-color: var(--type-accent, var(--pdex-border));
}

.demo-pokedex[data-view="cards"] .pdex-gen-tab[aria-selected="true"],
.demo-pokedex[data-view="cards"] .pdex-back:focus-visible {
    outline-color: var(--type-accent, var(--pdex-primary));
}

/* Skeleton loading states */
.demo-pokedex .pdex-skeleton {
    background: linear-gradient(90deg, var(--pdex-card-bg) 0%, var(--pdex-border) 50%, var(--pdex-card-bg) 100%);
    background-size: 200% 100%;
    animation: pdex-shimmer 1.4s infinite;
    border-radius: var(--pdex-radius);
}

.demo-pokedex .pdex-skeleton-tile {
    aspect-ratio: 1;
    border: 1px solid var(--pdex-border);
}

.demo-pokedex .pdex-skeleton-card {
    aspect-ratio: 245 / 342;
    border: 1px solid var(--pdex-border);
}

.demo-pokedex .pdex-skeleton-hero {
    grid-column: 1 / -1;
    min-height: 220px;
}

@keyframes pdex-shimmer {
    0%   { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}
</style>

<script>
(function () {
    'use strict';

    var ROOT = document.querySelector('.demo-pokedex');
    if (!ROOT) return;

    // Tag <body> so the section's surface color paints the entire viewport,
    // covering any gap left by alignfull / is-layout-constrained margin rules.
    document.body.classList.add('has-pokedex-page');

    /* ===== Shared state ===== */

    var state = {
        pokemonList: [],     // Filled by Task 4 result
        activeGen: 1,
        searchTerm: '',
        selectedPokemon: null,
        cardData: null,       // Set in Task 10
        cardFilters: { sort: 'release-desc', set: '', rarity: '', search: '' },
        cardRetries: 0
    };

    function applyParamsToFilters(params, current) {
        var allowedSorts = ['release-desc', 'release-asc', 'rarity-desc', 'set-number', 'price-desc', 'price-asc', 'trending'];
        return {
            sort: allowedSorts.indexOf(params.sort) !== -1 ? params.sort : 'release-desc',
            set: typeof params.set === 'string' ? params.set : '',
            rarity: typeof params.rarity === 'string' ? params.rarity : '',
            search: typeof params.search === 'string' ? params.search : ''
        };
    }
    function cardNumKey(num) {
        var m = String(num || '').match(/^([A-Za-z]*)(\d+)/);
        if (!m) return ['', 0];
        return [m[1], parseInt(m[2], 10)];
    }
    function cardMarketPrice(c) {
        var t = c.tcgplayer && pickBestTcgVariant(c.tcgplayer.prices);
        return t ? t.price : null;
    }
    // Momentum score from Cardmarket: how much the 1-day avg price moved
    // versus the 30-day avg, expressed as a fraction. Positive = rising,
    // negative = falling. Returns null when Cardmarket has no usable history.
    function cardMomentum(c) {
        var p = c.cardmarket && c.cardmarket.prices;
        if (!p) return null;
        var recent = typeof p.avg1 === 'number' ? p.avg1 : (typeof p.trendPrice === 'number' ? p.trendPrice : null);
        var baseline = typeof p.avg30 === 'number' ? p.avg30 : null;
        if (recent === null || baseline === null || baseline === 0) return null;
        return (recent - baseline) / baseline;
    }
    function getVisibleCards() {
        if (!state.cardData) return [];
        var f = state.cardFilters;
        var list = state.cardData.slice();

        if (f.set) list = list.filter(function (c) { return c.set && c.set.id === f.set; });
        if (f.rarity) list = list.filter(function (c) { return (c.rarity || '') === f.rarity; });
        if (f.search) {
            var term = f.search.toLowerCase();
            list = list.filter(function (c) { return (c.name || '').toLowerCase().indexOf(term) !== -1; });
        }

        list.sort(function (a, b) {
            switch (f.sort) {
                case 'release-asc':
                    return (a.set.releaseDate || '').localeCompare(b.set.releaseDate || '');
                case 'rarity-desc':
                    return rarityRank(b.rarity) - rarityRank(a.rarity);
                case 'set-number':
                    if (a.set.id !== b.set.id) return (a.set.releaseDate || '').localeCompare(b.set.releaseDate || '');
                    var ka = cardNumKey(a.number), kb = cardNumKey(b.number);
                    if (ka[0] !== kb[0]) return ka[0].localeCompare(kb[0]);
                    return ka[1] - kb[1];
                case 'price-desc':
                case 'price-asc':
                    // Cards with no TCG pricing always sort to the bottom so
                    // the user sees priced cards first regardless of direction.
                    var pa = cardMarketPrice(a), pb = cardMarketPrice(b);
                    if (pa === null && pb === null) return 0;
                    if (pa === null) return 1;
                    if (pb === null) return -1;
                    return f.sort === 'price-desc' ? pb - pa : pa - pb;
                case 'trending':
                    // Hottest momentum first. Cards without Cardmarket history
                    // drop to the bottom rather than being treated as flat.
                    var ma = cardMomentum(a), mb = cardMomentum(b);
                    if (ma === null && mb === null) return 0;
                    if (ma === null) return 1;
                    if (mb === null) return -1;
                    return mb - ma;
                case 'release-desc':
                default:
                    return (b.set.releaseDate || '').localeCompare(a.set.releaseDate || '');
            }
        });
        return list;
    }

    function rarityRank(rarity) {
        var ranks = ['Common', 'Uncommon', 'Promo', 'Rare', 'Rare Holo', 'Rare Holo EX', 'Rare Holo GX', 'Rare Holo V', 'Rare Holo VMAX', 'Rare Holo VSTAR', 'Rare BREAK', 'Rare Prime', 'Rare ACE', 'Rare Shiny', 'Rare Ultra', 'Double Rare', 'Ultra Rare', 'ACE SPEC Rare', 'Trainer Gallery Rare Holo', 'Amazing Rare', 'Radiant Rare', 'LEGEND', 'Illustration Rare', 'Rare Rainbow', 'Rare Secret', 'Special Illustration Rare', 'Hyper Rare'];
        var idx = ranks.indexOf(rarity);
        // Unknown rarities cluster at top of rarity-desc since modern sets often emit
        // rarity strings not in this list, and "unknown" is more often "rare new tier" than "common".
        return idx === -1 ? 999 : idx + 1;
    }

    function populateSetFilter() {
        var select = ROOT.querySelector('select[data-control="set"]');
        var sets = {};
        (state.cardData || []).forEach(function (c) {
            if (c.set && c.set.id) {
                sets[c.set.id] = {
                    name: c.set.name || '',
                    releaseDate: c.set.releaseDate || ''
                };
            }
        });
        var entries = Object.keys(sets).map(function (id) {
            var s = sets[id];
            return {
                id: id,
                name: s.name,
                releaseDate: s.releaseDate,
                label: s.name + (s.releaseDate ? ' (' + s.releaseDate.slice(0, 4) + ')' : '')
            };
        });
        entries.sort(function (a, b) {
            // Newest set first; sets with no release date sink to the bottom.
            if (a.releaseDate && b.releaseDate) {
                var cmp = b.releaseDate.localeCompare(a.releaseDate);
                if (cmp !== 0) return cmp;
            } else if (a.releaseDate) {
                return -1;
            } else if (b.releaseDate) {
                return 1;
            }
            return a.name.localeCompare(b.name);
        });
        select.innerHTML = '<option value="">All sets</option>' + entries.map(function (e) {
            return '<option value="' + escapeHtml(e.id) + '">' + escapeHtml(e.label) + '</option>';
        }).join('');
        select.value = state.cardFilters.set;
    }

    function populateRarityFilter() {
        var select = ROOT.querySelector('select[data-control="rarity"]');
        var seen = {};
        (state.cardData || []).forEach(function (c) {
            if (c.rarity) seen[c.rarity] = true;
        });
        var rarities = Object.keys(seen);
        // Highest tier first so the rare stuff is at the top of the dropdown.
        // rarityRank returns 999 for unknown rarities; those slot above the
        // known tiers (treated as "newer / probably higher").
        rarities.sort(function (a, b) { return rarityRank(b) - rarityRank(a); });
        select.innerHTML = '<option value="">All rarities</option>' + rarities.map(function (r) {
            return '<option value="' + escapeHtml(r) + '">' + escapeHtml(r) + '</option>';
        }).join('');
        // Drop the saved value if the new card list doesn't include that rarity.
        if (state.cardFilters.rarity && !seen[state.cardFilters.rarity]) {
            state.cardFilters.rarity = '';
        }
        select.value = state.cardFilters.rarity;
    }

    function buildMetaRows(card) {
        var rows = [];
        if (card.set) rows.push(['Set', (card.set.name || '?') + ' · ' + (card.number || '?')]);
        if (card.rarity) rows.push(['Rarity', card.rarity]);
        if (card.hp) rows.push(['HP', card.hp]);
        if (card.types && card.types.length) rows.push(['Type', card.types.join(', ')]);
        if (card.weaknesses && card.weaknesses.length) rows.push(['Weakness', card.weaknesses.map(function (w) { return w.type + ' ' + w.value; }).join(', ')]);
        if (card.resistances && card.resistances.length) rows.push(['Resistance', card.resistances.map(function (r) { return r.type + ' ' + r.value; }).join(', ')]);
        if (card.retreatCost) rows.push(['Retreat', card.retreatCost.length + ' energy']);
        if (card.artist) rows.push(['Artist', card.artist]);
        if (card.set && card.set.releaseDate) rows.push(['Released', card.set.releaseDate]);
        return rows;
    }

    function renderCardGrid() {
        var grid = ROOT.querySelector('.pdex-grid--cards');
        var cards = getVisibleCards();
        if (!cards.length) {
            var hasFilters = state.cardFilters.set || state.cardFilters.rarity || state.cardFilters.search;
            var msg = hasFilters
                ? 'No cards match the current filters. Try clearing the set, rarity, or search filter.'
                : 'No TCG cards found for ' + getDisplayName(state.selectedPokemon || '') + ' yet.';
            grid.innerHTML = '<p style="grid-column:1/-1;color:var(--pdex-muted);text-align:center;padding:3rem 1rem;">' + escapeHtml(msg) + '</p>';
            return;
        }
        grid.innerHTML = cards.map(function (c, idx) {
            var img = (c.images && c.images.small) || '';
            var setLine = (c.set && c.set.name ? c.set.name : '?') + ' · ' + (c.number || '?');
            var variants = c.tcgplayer ? getAllTcgVariants(c.tcgplayer.prices) : [];
            var tcg = variants.length ? variants[0] : null;
            var tcgUrl = c.tcgplayer && c.tcgplayer.url;
            var priceText = tcg ? '$' + tcg.price.toFixed(2) : '—';
            var variantBadge = variants.length > 1
                ? '<span class="pdex-card-variants" title="This card has ' + variants.length + ' priced printings">+' + (variants.length - 1) + '</span>'
                : '';
            var linkHtml = tcgUrl
                ? '<a class="pdex-card-link" href="' + escapeHtml(tcgUrl) + '" target="_blank" rel="noopener noreferrer" aria-label="View ' + escapeHtml(c.name || 'card') + ' on TCGplayer">TCG <span aria-hidden="true">↗</span></a>'
                : '';
            return ''
                + '<div class="pdex-card" data-card-index="' + idx + '" tabindex="0" role="button">'
                +     '<img class="pdex-card-image" src="' + escapeHtml(img) + '" alt="' + escapeHtml((c.name || 'card') + ', ' + setLine) + '" loading="lazy" />'
                +     '<span class="pdex-card-meta"><strong>' + escapeHtml(c.name || '') + '</strong></span>'
                +     '<span class="pdex-card-meta">' + escapeHtml(setLine) + '</span>'
                +     '<span class="pdex-card-meta">' + escapeHtml((c.rarity || '·') + (c.hp ? ' · HP ' + c.hp : '')) + '</span>'
                +     '<div class="pdex-card-footer">'
                +         '<span class="pdex-card-price">' + escapeHtml(priceText) + variantBadge + '</span>'
                +         linkHtml
                +     '</div>'
                + '</div>';
        }).join('');
    }

    function spriteUrl(id) {
        return 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/other/official-artwork/' + id + '.png';
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function renderDexGrid() {
        var grid = ROOT.querySelector('.pdex-grid--dex');
        var status = ROOT.querySelector('.pdex-status');

        if (!state.pokemonList.length) {
            grid.innerHTML = Array.from({ length: 24 }).map(function () {
                return '<div class="pdex-skeleton pdex-skeleton-tile"></div>';
            }).join('');
            status.textContent = 'Loading Pokédex…';
            return;
        }

        var visible;
        if (state.searchTerm) {
            var term = state.searchTerm.toLowerCase();
            visible = state.pokemonList.filter(function (p) { return p.name.indexOf(term) !== -1; });
            status.textContent = visible.length + ' result' + (visible.length === 1 ? '' : 's') + ' across all generations';
        } else {
            visible = state.pokemonList.filter(function (p) { return genForId(p.id) === state.activeGen; });
            status.textContent = visible.length + ' Pokémon in generation ' + state.activeGen;
        }

        grid.innerHTML = visible.map(function (p) {
            var num = String(p.id).padStart(4, '0');
            var name = getDisplayName(p.name);
            return ''
                + '<a class="pdex-tile" href="#' + escapeHtml(p.name) + '" data-pokemon="' + escapeHtml(p.name) + '">'
                +     '<img class="pdex-tile-sprite" src="' + spriteUrl(p.id) + '" alt="' + escapeHtml(name) + ' official artwork" loading="lazy" decoding="async" onerror="this.style.visibility=\'hidden\'" />'
                +     '<span class="pdex-tile-num">#' + num + '</span>'
                +     '<span class="pdex-tile-name">' + name + '</span>'
                + '</a>';
        }).join('');
    }

    function fetchPokemonDetail(idOrName) {
        return fetchJSON(API.POKEAPI_DETAIL(idOrName));
    }

    function fetchAllCardsForPokemon(rawName) {
        var queryName = getTcgQueryName(rawName);
        var allCards = [];

        function fetchPage(page) {
            return fetchJSON(API.TCG_CARDS(queryName, page), { tcgKey: true })
                .then(function (resp) {
                    allCards = allCards.concat(resp.data || []);
                    var fetched = (resp.page * resp.pageSize);
                    if (fetched < resp.totalCount) return fetchPage(page + 1);
                    return allCards;
                });
        }

        return fetchPage(1).then(function (cards) {
            if (cards.length === 0) {
                console.warn('No cards returned for', rawName, 'using TCG query name', queryName, '— check NAME_MAP for missing entry.');
            }
            return cards;
        });
    }

    function renderHero(pokemon) {
        var hero = ROOT.querySelector('.pdex-hero');
        var num = String(pokemon.id).padStart(4, '0');
        var name = getDisplayName(pokemon.name);
        var types = (pokemon.types || []).map(function (t) { return t.type.name; });
        var heightM = (pokemon.height || 0) / 10;
        var weightKg = (pokemon.weight || 0) / 10;
        var bst = (pokemon.stats || []).reduce(function (sum, s) { return sum + s.base_stat; }, 0);

        hero.innerHTML = ''
            + '<img class="pdex-hero-image" src="' + spriteUrl(pokemon.id) + '" alt="' + escapeHtml(name) + ' official artwork" />'
            + '<div class="pdex-hero-info">'
            +     '<span class="pdex-hero-num">#' + num + '</span>'
            +     '<h2 class="pdex-hero-name">' + name + '</h2>'
            +     '<div class="pdex-type-badges">'
            +         types.map(function (t) { return '<span class="pdex-type-badge" data-type="' + escapeHtml(t) + '">' + t + '</span>'; }).join('')
            +     '</div>'
            +     '<div class="pdex-hero-stats">'
            +         '<span>Height: ' + heightM.toFixed(1) + ' m</span>'
            +         '<span>Weight: ' + weightKg.toFixed(1) + ' kg</span>'
            +         '<span>Base stat total: ' + bst + '</span>'
            +     '</div>'
            + '</div>';

        // Set the type-glow class on root for Layer 3 (Task 18 reads this).
        ROOT.className = ROOT.className.replace(/\btype-\w+\b/g, '').trim();
        if (types[0]) ROOT.classList.add('type-' + types[0]);
    }

    /* ===== API helpers with sessionStorage caching ===== */

    var API = {
        POKEAPI_LIST: 'https://pokeapi.co/api/v2/pokemon?limit=1025',
        POKEAPI_DETAIL: function (id) { return 'https://pokeapi.co/api/v2/pokemon/' + id; },
        TCG_KEY: '', // Optional: paste your pokemontcg.io API key here for higher rate limits.
        TCG_CARDS: function (name, page) {
            var pageSize = 250;
            var query = 'name:"' + name.replace(/"/g, '\\"') + '" supertype:Pokémon';
            var qs = new URLSearchParams({
                q: query,
                pageSize: String(pageSize),
                page: String(page || 1),
                orderBy: '-set.releaseDate'
            });
            return 'https://api.pokemontcg.io/v2/cards?' + qs.toString();
        }
    };

    function cacheGet(key) {
        try {
            var raw = sessionStorage.getItem(key);
            return raw === null ? null : JSON.parse(raw);
        } catch (e) { return null; }
    }

    function cacheSet(key, value) {
        try { sessionStorage.setItem(key, JSON.stringify(value)); } catch (e) { /* quota or disabled */ }
    }

    function fetchJSON(url, options) {
        options = options || {};
        var cached = options.cache !== false ? cacheGet(url) : null;
        if (cached !== null) return Promise.resolve(cached);

        var headers = {};
        if (options.tcgKey && API.TCG_KEY) headers['X-Api-Key'] = API.TCG_KEY;

        return fetch(url, { headers: headers })
            .then(function (res) {
                if (!res.ok) {
                    var err = new Error(res.status === 429 ? 'Rate limited' : 'HTTP ' + res.status);
                    err.code = res.status;
                    throw err;
                }
                return res.json();
            })
            .then(function (data) {
                if (options.cache !== false) cacheSet(url, data);
                return data;
            });
    }

    function fetchPokemonList() {
        return fetchJSON(API.POKEAPI_LIST).then(function (data) {
            // Each entry: { name: 'bulbasaur', url: 'https://pokeapi.co/api/v2/pokemon/1/' }
            // Extract the ID from the URL rather than relying on array order.
            return data.results.map(function (entry) {
                var m = entry.url.match(/\/pokemon\/(\d+)\/?$/);
                return { id: m ? Number(m[1]) : null, name: entry.name };
            }).filter(function (e) { return e.id !== null; });
        });
    }

    /* ===== Hash routing ===== */

    var router = {
        parse: function () {
            var raw = (location.hash || '').replace(/^#/, '');
            if (!raw) return { view: 'dex', name: null, params: {} };
            var queryIdx = raw.indexOf('?');
            var name = queryIdx === -1 ? raw : raw.slice(0, queryIdx);
            var queryString = queryIdx === -1 ? '' : raw.slice(queryIdx + 1);
            var params = {};
            if (queryString) {
                var sp = new URLSearchParams(queryString);
                sp.forEach(function (v, k) { params[k] = v; });
            }
            return { view: name ? 'cards' : 'dex', name: name || null, params: params };
        },
        build: function (route) {
            if (!route.name) return '';
            var hash = '#' + route.name;
            var keys = Object.keys(route.params || {});
            if (keys.length) {
                var sp = new URLSearchParams();
                keys.forEach(function (k) {
                    if (route.params[k] !== '' && route.params[k] != null) sp.set(k, route.params[k]);
                });
                var s = sp.toString();
                if (s) hash += '?' + s;
            }
            return hash;
        },
        // push() fires applyRouteFromHash so callers don't have to. replace() does NOT,
        // because it's used for in-view URL updates (filter state) that shouldn't re-render.
        push: function (route) {
            var newHash = router.build(route);
            if (newHash !== location.hash) {
                history.pushState(route, '', newHash || location.pathname + location.search);
            }
            applyRouteFromHash();
        },
        replace: function (route) {
            var newHash = router.build(route);
            history.replaceState(route, '', newHash || location.pathname + location.search);
        }
    };

    var views = {
        dex: ROOT.querySelector('.pdex-view--dex'),
        cards: ROOT.querySelector('.pdex-view--cards')
    };

    function showView(viewId) {
        Object.keys(views).forEach(function (key) {
            views[key].hidden = (key !== viewId);
        });
        ROOT.dataset.view = viewId;
    }

    function applyRouteFromHash() {
        var s = router.parse();
        showView(s.view);

        if (s.view === 'cards' && s.name) {
            state.selectedPokemon = s.name;
            // Note: cardRetries reset only on a NEW selection, not on retry re-entries
            // (the retry path calls applyRouteFromHash() which would otherwise zero the counter).
            // Track the last selection separately so we know.
            if (state._lastCardFetchFor !== s.name) {
                state.cardRetries = 0;
                state._lastCardFetchFor = s.name;
                // Clear the previous Pokémon's hero and type tint so the
                // stale data does not flash before the new fetch resolves.
                ROOT.querySelector('.pdex-hero').innerHTML = '<div class="pdex-skeleton pdex-skeleton-hero"></div>';
                ROOT.className = ROOT.className.replace(/\btype-\w+\b/g, '').trim();
                // Bring the top of the demo into view so the user is not
                // left scrolled to where they clicked the dex tile.
                ROOT.scrollIntoView({ block: 'start', behavior: 'smooth' });
            }
            ROOT.querySelector('.pdex-topbar-title').textContent = getDisplayName(s.name);

            // Find the dex entry for this name (so we have the ID for the detail fetch).
            var entry = state.pokemonList.find(function (p) { return p.name === s.name; });
            if (!entry) {
                // Pokémon list may not have loaded yet, or hash is invalid.
                // If list is loaded but name isn't found, fall back to dex.
                if (state.pokemonList.length) {
                    router.replace({ view: 'dex', name: null, params: {} });
                    applyRouteFromHash();
                    return;
                }
                // Otherwise, defer; bootstrap will reapply.
                return;
            }

            fetchPokemonDetail(entry.id)
                .then(function (detail) {
                    if (state.selectedPokemon !== s.name) return;
                    renderHero(detail);
                })
                .catch(function (err) {
                    if (state.selectedPokemon !== s.name) return;
                    ROOT.querySelector('.pdex-hero').innerHTML = '<p>Couldn\'t load this Pokémon\'s details.</p>';
                    console.error(err);
                });

            var cardGrid = ROOT.querySelector('.pdex-grid--cards');
            cardGrid.innerHTML = Array.from({ length: 8 }).map(function () {
                return '<div class="pdex-skeleton pdex-skeleton-card"></div>';
            }).join('');

            var cardStatus = ROOT.querySelector('.pdex-card-status');
            cardStatus.textContent = 'Loading cards…';

            fetchAllCardsForPokemon(s.name)
                .then(function (cards) {
                    if (state.selectedPokemon !== s.name) return;
                    state.cardRetries = 0;
                    state.cardData = cards;
                    state.cardFilters = applyParamsToFilters(s.params, state.cardFilters);
                    populateSetFilter();
                    populateRarityFilter();
                    ROOT.querySelector('select[data-control="sort"]').value = state.cardFilters.sort;
                    ROOT.querySelector('input[data-control="card-search"]').value = state.cardFilters.search;
                    renderCardGrid();
                    cardStatus.textContent = cards.length + ' cards found';
                })
                .catch(function (err) {
                    if (state.selectedPokemon !== s.name) return;
                    if (err && err.code === 429) {
                        if (state.cardRetries < 3) {
                            state.cardRetries++;
                            cardStatus.textContent = 'Rate-limited by the TCG API. Retrying in 5 seconds…';
                            setTimeout(function () { applyRouteFromHash(); }, 5000);
                            return;
                        }
                        cardStatus.textContent = 'Still rate-limited by the TCG API. Refresh to try again.';
                        return;
                    }
                    cardStatus.textContent = 'Couldn\'t reach the Pokémon TCG API.';
                    console.error(err);
                });
        } else {
            ROOT.className = ROOT.className.replace(/\btype-\w+\b/g, '').trim();
            state.selectedPokemon = null;
        }
    }

    window.addEventListener('popstate', applyRouteFromHash);

    /* ===== Modal ===== */

    var modal = {
        el: ROOT.querySelector('.pdex-modal'),
        name: ROOT.querySelector('.pdex-modal-name'),
        body: ROOT.querySelector('.pdex-modal-body'),
        image: ROOT.querySelector('.pdex-modal-image'),
        pricing: ROOT.querySelector('.pdex-modal-pricing'),
        currentIndex: -1,
        previousFocus: null,

        open: function (index) {
            var cards = getVisibleCards();
            if (index < 0 || index >= cards.length) return;
            modal.currentIndex = index;
            modal.previousFocus = document.activeElement;
            modal.render(cards[index]);
            modal.el.hidden = false;
            document.body.style.overflow = 'hidden';
            modal.el.querySelector('.pdex-modal-close').focus();
        },

        close: function () {
            modal.el.hidden = true;
            document.body.style.overflow = '';
            if (modal.previousFocus && typeof modal.previousFocus.focus === 'function') {
                modal.previousFocus.focus();
            }
        },

        render: function (card) {
            modal.name.textContent = card.name || '';
            var imgs = card.images || {};
            modal.image.innerHTML = '<img src="'
                + escapeHtml(imgs.large || imgs.small || '')
                + '" alt="' + escapeHtml(card.name || 'card') + '" />';

            var rows = buildMetaRows(card);
            var abilities = card.abilities || [];
            var attacks = card.attacks || [];

            var attacksHtml = attacks.map(function (a) {
                return ''
                    + '<div class="pdex-attack">'
                    +     '<div class="pdex-attack-head"><span>' + escapeHtml(a.name || '') + '</span><span>' + escapeHtml(a.damage || '') + '</span></div>'
                    +     '<div class="pdex-attack-cost">Cost: ' + escapeHtml((a.cost || []).join(', ') || '·') + '</div>'
                    +     (a.text ? '<p class="pdex-attack-text">' + escapeHtml(a.text) + '</p>' : '')
                    + '</div>';
            }).join('');

            var abilitiesHtml = abilities.map(function (ab) {
                return ''
                    + '<div class="pdex-attack">'
                    +     '<div class="pdex-attack-head"><span>' + escapeHtml(ab.name || '') + '</span><span>' + escapeHtml(ab.type || 'Ability') + '</span></div>'
                    +     (ab.text ? '<p class="pdex-attack-text">' + escapeHtml(ab.text) + '</p>' : '')
                    + '</div>';
            }).join('');

            function accordion(label, count, contentHtml) {
                var badge = (typeof count === 'number')
                    ? '<span class="pdex-acc-count">' + count + '</span>'
                    : '';
                return ''
                    + '<details class="pdex-acc">'
                    +     '<summary><span class="pdex-acc-label">' + escapeHtml(label) + badge + '</span><span class="pdex-acc-chevron" aria-hidden="true">›</span></summary>'
                    +     '<div class="pdex-acc-body">' + contentHtml + '</div>'
                    + '</details>';
            }

            var detailsRows = '<dl>'
                + rows.map(function (r) { return '<dt>' + escapeHtml(r[0]) + '</dt><dd>' + escapeHtml(r[1]) + '</dd>'; }).join('')
                + '</dl>';

            modal.body.innerHTML = ''
                + accordion('Details', null, detailsRows)
                + (abilitiesHtml ? accordion('Abilities', abilities.length, abilitiesHtml) : '')
                + (attacksHtml  ? accordion('Attacks',  attacks.length,  attacksHtml)  : '');

            modal.pricing.innerHTML = '';
            renderPricing(card);
        },

        next: function () {
            var cards = getVisibleCards();
            modal.open((modal.currentIndex + 1) % cards.length);
        },

        prev: function () {
            var cards = getVisibleCards();
            modal.open((modal.currentIndex - 1 + cards.length) % cards.length);
        }
    };

    function pickBestTcgVariant(prices) {
        if (!prices) return null;
        var best = null;
        Object.keys(prices).forEach(function (variant) {
            var p = prices[variant];
            if (p && typeof p.market === 'number') {
                if (!best || p.market > best.price) best = { variant: variant, price: p.market, low: p.low, mid: p.mid, high: p.high };
            }
        });
        return best;
    }

    // Returns every TCGPlayer variant on a card with usable market pricing,
    // sorted from priciest to cheapest. Used by the modal so a card with both
    // Holofoil and Reverse Holofoil shows both rows instead of just the headline.
    function getAllTcgVariants(prices) {
        if (!prices) return [];
        var out = [];
        Object.keys(prices).forEach(function (variant) {
            var p = prices[variant];
            if (p && typeof p.market === 'number') {
                out.push({ variant: variant, price: p.market, low: p.low, mid: p.mid, high: p.high });
            }
        });
        out.sort(function (a, b) { return b.price - a.price; });
        return out;
    }

    function variantLabel(name) {
        return name.replace(/([A-Z])/g, ' $1').replace(/^./, function (c) { return c.toUpperCase(); }).trim();
    }

    function buildSparkline(points) {
        var valid = points.filter(function (p) { return typeof p === 'number' && isFinite(p); });
        if (valid.length < 2) return null;
        var min = Math.min.apply(null, valid), max = Math.max.apply(null, valid);
        var range = max - min || 1;
        var width = 90, height = 30, pad = 2;
        var step = (width - pad * 2) / (points.length - 1);
        var coords = [];
        points.forEach(function (p, i) {
            if (typeof p !== 'number' || !isFinite(p)) return;
            var x = pad + i * step;
            var y = height - pad - ((p - min) / range) * (height - pad * 2);
            coords.push([x, y]);
        });
        if (coords.length < 2) return null;
        var d = 'M' + coords.map(function (c) { return c[0].toFixed(1) + ',' + c[1].toFixed(1); }).join(' L');
        return '<svg viewBox="0 0 ' + width + ' ' + height + '" aria-hidden="true">'
            + '<path d="' + d + '" fill="none" stroke="currentColor" stroke-width="1.5" />'
            + '</svg>';
    }

    function trendArrow(start, end) {
        if (typeof start !== 'number' || typeof end !== 'number') return { cls: 'flat', glyph: '·' };
        var diff = end - start;
        if (Math.abs(diff) / (start || 1) < 0.02) return { cls: 'flat', glyph: '→' };
        return diff > 0 ? { cls: 'up', glyph: '↑' } : { cls: 'down', glyph: '↓' };
    }

    function renderPricing(card) {
        var variants = card.tcgplayer ? getAllTcgVariants(card.tcgplayer.prices) : [];
        var tcg = variants.length ? variants[0] : null;
        var cm = card.cardmarket && card.cardmarket.prices;
        var tcgUrl = card.tcgplayer && card.tcgplayer.url;

        var html = '';

        if (tcg) {
            html += ''
                + '<div class="pdex-price-block">'
                +     '<h4>TCGPlayer (USD)</h4>'
                +     '<p class="pdex-price-headline">$' + tcg.price.toFixed(2) + '</p>'
                +     '<p class="pdex-price-variant">' + escapeHtml(variantLabel(tcg.variant))
                +         (variants.length > 1 ? ' <span class="pdex-variant-count">' + variants.length + ' variants</span>' : '')
                +     '</p>';

            if (variants.length > 1) {
                // Show every variant in its own row when the card has more than
                // one printing (Holofoil / Reverse / Normal / 1st Edition / etc.).
                html += '<ul class="pdex-variant-list">';
                variants.forEach(function (v) {
                    var range = (typeof v.low === 'number' || typeof v.high === 'number')
                        ? 'Low $' + (v.low || 0).toFixed(2) + ' · High $' + (v.high || 0).toFixed(2)
                        : '';
                    html += ''
                        + '<li class="pdex-variant-row">'
                        +     '<span class="pdex-variant-name">' + escapeHtml(variantLabel(v.variant)) + '</span>'
                        +     (range ? '<span class="pdex-variant-meta">' + range + '</span>' : '')
                        +     '<span class="pdex-variant-price">$' + v.price.toFixed(2) + '</span>'
                        + '</li>';
                });
                html += '</ul>';
            } else if (typeof tcg.low === 'number') {
                html += '<p class="pdex-price-range">Low $' + tcg.low.toFixed(2) + ' · Mid $' + (tcg.mid || 0).toFixed(2) + ' · High $' + (tcg.high || 0).toFixed(2) + '</p>';
            }

            html += '</div>';
        }

        if (cm && (cm.trendPrice || cm.avg30 || cm.avg7 || cm.avg1)) {
            var points = [cm.avg30, cm.avg7, cm.avg1, cm.trendPrice];
            var sparkline = buildSparkline(points);
            var firstValid = points.find(function (p) { return typeof p === 'number'; });
            var lastValid = [].concat(points).reverse().find(function (p) { return typeof p === 'number'; });
            var arrow = trendArrow(firstValid, lastValid);

            html += ''
                + '<div class="pdex-price-block">'
                +     '<h4>Cardmarket (EUR)</h4>'
                +     '<p class="pdex-price-headline">€' + (typeof cm.trendPrice === 'number' ? cm.trendPrice.toFixed(2) : '·') + '</p>'
                +     '<p class="pdex-price-variant">Trend price</p>'
                +     (sparkline
                        ? '<div class="pdex-sparkline" title="Cardmarket 30-day trend">' + sparkline + '<span class="pdex-sparkline-arrow ' + arrow.cls + '">' + arrow.glyph + '</span></div>'
                        : '<p class="pdex-price-range" title="Cardmarket has no recent trade history for this card">No recent trade history</p>')
                + '</div>';
        }

        if (!html) {
            html = '<p class="pdex-no-pricing">No pricing data available for this card.</p>';
        }

        if (tcgUrl) {
            html += ''
                + '<a class="pdex-modal-tcg-link" href="' + escapeHtml(tcgUrl) + '" target="_blank" rel="noopener noreferrer">'
                +     'View on TCGplayer <span aria-hidden="true">↗</span>'
                + '</a>';
        }

        modal.pricing.innerHTML = html;
    }

    document.addEventListener('keydown', function (event) {
        if (modal.el.hidden) return;
        if (event.key === 'Escape') { event.preventDefault(); modal.close(); }
        else if (event.key === 'ArrowRight') { event.preventDefault(); modal.next(); }
        else if (event.key === 'ArrowLeft') { event.preventDefault(); modal.prev(); }
        else if (event.key === 'Tab') {
            // Focus trap
            var focusables = modal.el.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
            if (!focusables.length) return;
            var first = focusables[0], last = focusables[focusables.length - 1];
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
            else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
        }
    });

    ROOT.addEventListener('click', function (event) {
        // External links (e.g. the TCGplayer card link) open in a new tab via
        // the anchor's own default behavior — don't treat the click as a card
        // click that would also open the modal.
        if (event.target.closest('a[href][target="_blank"]')) {
            return;
        }
        var backLink = event.target.closest('[data-action="back"]');
        if (backLink) {
            event.preventDefault();
            router.push({ view: 'dex', name: null, params: {} });
            return;
        }
        var tile = event.target.closest('[data-pokemon]');
        if (tile) {
            event.preventDefault();
            var name = tile.dataset.pokemon;
            router.push({ view: 'cards', name: name, params: {} });
            return;
        }
        var card = event.target.closest('[data-card-index]');
        if (card) {
            modal.open(parseInt(card.dataset.cardIndex, 10));
            return;
        }
        var closeBtn = event.target.closest('[data-action="close-modal"]');
        if (closeBtn) { modal.close(); return; }
        var prevBtn = event.target.closest('[data-action="prev-card"]');
        if (prevBtn) { modal.prev(); return; }
        var nextBtn = event.target.closest('[data-action="next-card"]');
        if (nextBtn) { modal.next(); return; }
    });

    ROOT.addEventListener('keydown', function (event) {
        if (event.key !== 'Enter' && event.key !== ' ') return;
        var card = event.target.closest('[data-card-index]');
        if (card) { event.preventDefault(); modal.open(parseInt(card.dataset.cardIndex, 10)); }
    });

    applyRouteFromHash();

    /* ===== Generations ===== */

    var GENERATIONS = [
        { id: 1, label: 'Gen 1', range: [1, 151] },
        { id: 2, label: 'Gen 2', range: [152, 251] },
        { id: 3, label: 'Gen 3', range: [252, 386] },
        { id: 4, label: 'Gen 4', range: [387, 493] },
        { id: 5, label: 'Gen 5', range: [494, 649] },
        { id: 6, label: 'Gen 6', range: [650, 721] },
        { id: 7, label: 'Gen 7', range: [722, 809] },
        { id: 8, label: 'Gen 8', range: [810, 905] },
        { id: 9, label: 'Gen 9', range: [906, 1025] }
    ];

    function genForId(pokemonId) {
        for (var i = 0; i < GENERATIONS.length; i++) {
            var r = GENERATIONS[i].range;
            if (pokemonId >= r[0] && pokemonId <= r[1]) return GENERATIONS[i].id;
        }
        return null;
    }

    function renderGenTabs() {
        var tablist = ROOT.querySelector('.pdex-gen-tabs');
        tablist.innerHTML = '';
        GENERATIONS.forEach(function (g) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'pdex-gen-tab';
            btn.setAttribute('role', 'tab');
            btn.setAttribute('aria-selected', String(g.id === state.activeGen));
            btn.dataset.gen = String(g.id);
            btn.textContent = g.label;
            tablist.appendChild(btn);
        });
    }

    function setActiveGen(gen) {
        state.activeGen = gen;
        ROOT.dataset.gen = String(gen);
        renderGenTabs();
        renderDexGrid();
    }

    (function wireGenTabEvents() {
        var tablistEl = ROOT.querySelector('.pdex-gen-tabs');
        tablistEl.addEventListener('click', function (event) {
            var tab = event.target.closest('.pdex-gen-tab');
            if (!tab) return;
            var gen = parseInt(tab.dataset.gen, 10);
            setActiveGen(gen);
        });
        tablistEl.addEventListener('keydown', function (event) {
            if (['ArrowLeft','ArrowRight'].indexOf(event.key) === -1) return;
            var current = parseInt((event.target.dataset || {}).gen, 10);
            if (!current) return;
            var dir = event.key === 'ArrowLeft' ? -1 : 1;
            var next = ((current - 1 + dir + GENERATIONS.length) % GENERATIONS.length) + 1;
            setActiveGen(next);
            var nextBtn = tablistEl.querySelector('[data-gen="' + next + '"]');
            if (nextBtn) nextBtn.focus();
        });
    })();

    /* ===== Pokémon name normalization ===== */

    // PokéAPI hyphenated form → display name and pokemontcg.io query name.
    // If a name is not in this map, it falls through to default capitalization.
    var NAME_MAP = {
        'mr-mime':    { display: 'Mr. Mime',    tcg: 'Mr. Mime' },
        'mime-jr':    { display: 'Mime Jr.',    tcg: 'Mime Jr.' },
        'mr-rime':    { display: 'Mr. Rime',    tcg: 'Mr. Rime' },
        'farfetchd':  { display: "Farfetch'd",  tcg: "Farfetch'd" },
        'sirfetchd':  { display: "Sirfetch'd",  tcg: "Sirfetch'd" },
        'type-null':  { display: 'Type: Null',  tcg: 'Type: Null' },
        'nidoran-f':  { display: 'Nidoran ♀',   tcg: 'Nidoran ♀' },
        'nidoran-m':  { display: 'Nidoran ♂',   tcg: 'Nidoran ♂' },
        'flabebe':    { display: 'Flabébé',     tcg: 'Flabébé' },
        'ho-oh':      { display: 'Ho-Oh',       tcg: 'Ho-Oh' },
        'porygon-z':  { display: 'Porygon-Z',   tcg: 'Porygon-Z' },
        'jangmo-o':   { display: 'Jangmo-o',    tcg: 'Jangmo-o' },
        'hakamo-o':   { display: 'Hakamo-o',    tcg: 'Hakamo-o' },
        'kommo-o':    { display: 'Kommo-o',     tcg: 'Kommo-o' },
        'wo-chien':   { display: 'Wo-Chien',    tcg: 'Wo-Chien' },
        'chien-pao':  { display: 'Chien-Pao',   tcg: 'Chien-Pao' },
        'ting-lu':    { display: 'Ting-Lu',     tcg: 'Ting-Lu' },
        'chi-yu':     { display: 'Chi-Yu',      tcg: 'Chi-Yu' },
        'tapu-koko':  { display: 'Tapu Koko',   tcg: 'Tapu Koko' },
        'tapu-lele':  { display: 'Tapu Lele',   tcg: 'Tapu Lele' },
        'tapu-bulu':  { display: 'Tapu Bulu',   tcg: 'Tapu Bulu' },
        'tapu-fini':  { display: 'Tapu Fini',   tcg: 'Tapu Fini' }
    };

    function getDisplayName(rawName) {
        // NAME_MAP is `var`-declared lower in the file; on the very first
        // applyRouteFromHash() call it is still `undefined`. Guard against
        // the TypeError so a fresh page load with #pokemon-name boots cleanly.
        // Avoid && in the condition because WordPress sometimes HTML-encodes
        // it inside inline scripts, breaking the parser.
        if (typeof NAME_MAP !== 'undefined') {
            if (NAME_MAP[rawName]) return NAME_MAP[rawName].display;
        }
        return rawName.split('-').map(function (part) {
            return part.charAt(0).toUpperCase() + part.slice(1);
        }).join(' ');
    }

    function getTcgQueryName(rawName) {
        if (NAME_MAP[rawName]) return NAME_MAP[rawName].tcg;
        // Default: capitalize and join with spaces (works for the 95%+ case).
        return getDisplayName(rawName);
    }

    /* ===== Search ===== */

    function setSearchTerm(term) {
        state.searchTerm = term.trim();
        var tablist = ROOT.querySelector('.pdex-gen-tabs');
        tablist.style.opacity = state.searchTerm ? '0.4' : '';
        tablist.style.pointerEvents = state.searchTerm ? 'none' : '';
        renderDexGrid();
    }

    function debounce(fn, ms) {
        var t;
        return function () {
            var args = arguments, ctx = this;
            clearTimeout(t);
            t = setTimeout(function () { fn.apply(ctx, args); }, ms);
        };
    }

    ROOT.querySelector('.pdex-search').addEventListener('input', debounce(function (e) {
        setSearchTerm(e.target.value);
    }, 150));

    /* ===== Card filters ===== */

    function updateCardFilter(key, value) {
        state.cardFilters[key] = value;
        var s = router.parse();
        var params = {};
        if (state.cardFilters.sort) {
            if (state.cardFilters.sort !== 'release-desc') params.sort = state.cardFilters.sort;
        }
        if (state.cardFilters.set) params.set = state.cardFilters.set;
        if (state.cardFilters.rarity) params.rarity = state.cardFilters.rarity;
        if (state.cardFilters.search) params.search = state.cardFilters.search;
        router.replace({ view: 'cards', name: s.name, params: params });
        renderCardGrid();
    }

    ROOT.querySelector('select[data-control="sort"]').addEventListener('change', function (e) {
        updateCardFilter('sort', e.target.value);
    });
    ROOT.querySelector('select[data-control="set"]').addEventListener('change', function (e) {
        updateCardFilter('set', e.target.value);
    });
    ROOT.querySelector('select[data-control="rarity"]').addEventListener('change', function (e) {
        updateCardFilter('rarity', e.target.value);
    });
    ROOT.querySelector('input[data-control="card-search"]').addEventListener('input', debounce(function (e) {
        updateCardFilter('search', e.target.value.trim());
    }, 150));

    /* ===== Bootstrap ===== */

    function bootstrap() {
        renderGenTabs(); // paint tabs immediately; they don't depend on the fetch
        fetchPokemonList()
            .then(function (list) {
                state.pokemonList = list;
                renderDexGrid();
                applyRouteFromHash(); // reapply now that the list is available
            })
            .catch(function (err) {
                ROOT.querySelector('.pdex-status').textContent = 'Couldn\'t reach PokéAPI. Check your connection and refresh.';
                console.error(err);
            });
    }

    bootstrap();

    /* ===== Inline self-tests (run with ?test=1 in the URL) ===== */

    if (new URLSearchParams(location.search).get('test') === '1') {
        console.group('demo-pokedex self-tests');
        console.assert(getDisplayName('bulbasaur') === 'Bulbasaur', 'getDisplayName: bulbasaur');
        console.assert(getDisplayName('mr-mime') === 'Mr. Mime', 'getDisplayName: mr-mime');
        console.assert(getDisplayName('farfetchd') === "Farfetch'd", 'getDisplayName: farfetchd');
        console.assert(getDisplayName('nidoran-f') === 'Nidoran ♀', 'getDisplayName: nidoran-f');
        console.assert(getDisplayName('flabebe') === 'Flabébé', 'getDisplayName: flabebe');
        console.assert(getDisplayName('tapu-koko') === 'Tapu Koko', 'getDisplayName: tapu-koko');
        console.assert(getDisplayName('porygon-z') === 'Porygon-Z', 'getDisplayName: porygon-z');
        console.assert(getDisplayName('ho-oh') === 'Ho-Oh', 'getDisplayName: ho-oh');
        console.assert(getTcgQueryName('mr-mime') === 'Mr. Mime', 'getTcgQueryName: mr-mime');
        console.assert(getTcgQueryName('bulbasaur') === 'Bulbasaur', 'getTcgQueryName fallthrough');
        console.assert(genForId(1) === 1, 'genForId Gen 1 start');
        console.assert(genForId(151) === 1, 'genForId Gen 1 end');
        console.assert(genForId(152) === 2, 'genForId Gen 2 start');
        console.assert(genForId(1025) === 9, 'genForId Gen 9 end');
        console.log('demo-pokedex self-tests complete');
        console.groupEnd();
    }
})();
</script>
