<?php
/**
 * Title: Demo — Contrast Checker
 * Slug: dariobimbi/demo-contrast
 * Categories: dariobimbi
 * Description: Live WCAG 2.x contrast-ratio checker. Two color pickers, a live preview at multiple sizes, a big ratio number, and pass/fail tiles for AA/AAA at normal and large sizes plus UI components. All vanilla JS, math implements the actual WCAG relative-luminance formula.
 *
 * To use: create a "Demos" Page (slug `demos`, can be empty), then a child Page
 * "Contrast Checker" (slug `contrast`, parent = Demos). On the child Page pick
 * the "Demo — Contrast Checker" template. Final URL: /demos/contrast/.
 *
 * Single-file by design — styles and script live alongside the markup so this
 * pattern is self-contained and easy to copy as a starting point for the next
 * demo.
 */
?>
<!-- wp:group {"align":"full","className":"content-page demo-contrast","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull content-page demo-contrast" style="padding-top:var(--wp--preset--spacing--70);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--70);padding-left:var(--wp--preset--spacing--30)">

    <!-- wp:paragraph {"className":"eyebrow"} -->
    <p class="eyebrow">a small accessibility tool</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"level":1,"className":"has-gradient-text","style":{"spacing":{"margin":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20"}}}} -->
    <h1 class="wp-block-heading has-gradient-text" style="margin-top:var(--wp--preset--spacing--20);margin-bottom:var(--wp--preset--spacing--20)">Contrast Checker</h1>
    <!-- /wp:heading -->

    <!-- wp:paragraph {"textColor":"muted","style":{"typography":{"lineHeight":"1.55"},"spacing":{"margin":{"bottom":"var:preset|spacing|50"}}}} -->
    <p class="has-muted-color has-text-color" style="line-height:1.55;margin-bottom:var(--wp--preset--spacing--50)">Pick a foreground and a background color, and the page will tell you whether the combination meets the <a href="https://www.w3.org/WAI/WCAG22/Understanding/contrast-minimum" target="_blank" rel="noopener">WCAG 2.2</a> contrast thresholds. The math follows the spec exactly, using the same relative-luminance formula that Lighthouse and axe use.</p>
    <!-- /wp:paragraph -->

    <!-- wp:html -->
    <div class="cc" data-default-fg="#60a5fa" data-default-bg="#0a0e1a">
        <div class="cc-controls">
            <label class="cc-field">
                <span class="cc-field-label">Foreground</span>
                <span class="cc-field-row">
                    <input type="color" class="cc-color" data-role="fg-color" aria-label="Foreground color picker">
                    <input type="text" class="cc-hex" data-role="fg-hex" aria-label="Foreground hex value" maxlength="7" autocomplete="off" spellcheck="false">
                </span>
            </label>

            <label class="cc-field">
                <span class="cc-field-label">Background</span>
                <span class="cc-field-row">
                    <input type="color" class="cc-color" data-role="bg-color" aria-label="Background color picker">
                    <input type="text" class="cc-hex" data-role="bg-hex" aria-label="Background hex value" maxlength="7" autocomplete="off" spellcheck="false">
                </span>
            </label>

            <div class="cc-actions">
                <button type="button" class="cc-btn" data-action="swap" aria-label="Swap foreground and background">↕ swap</button>
                <button type="button" class="cc-btn" data-action="random" aria-label="Pick a random color pair">⚄ random</button>
                <button type="button" class="cc-btn" data-action="copy" aria-label="Copy a shareable link to this color pair">⎘ copy link</button>
            </div>
        </div>

        <div class="cc-preview" data-role="preview" aria-label="Live contrast preview">
            <p class="cc-prev-large">The quick brown fox jumps over the lazy dog.</p>
            <p class="cc-prev-large-bold"><strong>The quick brown fox jumps over the lazy dog.</strong></p>
            <p class="cc-prev-normal">The quick brown fox jumps over the lazy dog.</p>
            <p class="cc-prev-small">Small print also has to read at this contrast level.</p>
            <span class="cc-prev-button" role="button" aria-disabled="true">Sample button</span>
        </div>

        <div class="cc-readout" aria-live="polite">
            <div class="cc-ratio">
                <span class="cc-ratio-num" data-role="ratio">·</span>
                <span class="cc-ratio-suffix">: 1</span>
            </div>
            <div class="cc-grade" data-role="grade">·</div>
        </div>

        <ul class="cc-tiles" data-role="tiles">
            <li class="cc-tile" data-tile="aa-normal">
                <span class="cc-tile-mark">·</span>
                <span class="cc-tile-name">AA</span>
                <span class="cc-tile-detail">Normal text · 4.5</span>
            </li>
            <li class="cc-tile" data-tile="aa-large">
                <span class="cc-tile-mark">·</span>
                <span class="cc-tile-name">AA</span>
                <span class="cc-tile-detail">Large text · 3.0</span>
            </li>
            <li class="cc-tile" data-tile="aaa-normal">
                <span class="cc-tile-mark">·</span>
                <span class="cc-tile-name">AAA</span>
                <span class="cc-tile-detail">Normal text · 7.0</span>
            </li>
            <li class="cc-tile" data-tile="aaa-large">
                <span class="cc-tile-mark">·</span>
                <span class="cc-tile-name">AAA</span>
                <span class="cc-tile-detail">Large text · 4.5</span>
            </li>
            <li class="cc-tile" data-tile="ui">
                <span class="cc-tile-mark">·</span>
                <span class="cc-tile-name">UI</span>
                <span class="cc-tile-detail">Components · 3.0</span>
            </li>
        </ul>

        <p class="cc-footnote">
            "Large" means 18pt regular (≈24px) or 14pt bold (≈18.66px). The UI tile covers icons, form borders, and chart elements per WCAG 2.2 SC 1.4.11.
        </p>
    </div>

    <style>
    /* ---- Contrast checker ---------------------------------------------- */
    .demo-contrast .cc {
        display: grid;
        gap: var(--wp--preset--spacing--40);
        margin-top: var(--wp--preset--spacing--40);
    }
    .demo-contrast .cc-controls {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: var(--wp--preset--spacing--30);
        align-items: end;
    }
    .demo-contrast .cc-actions {
        grid-column: 1 / -1;
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    @media (max-width: 540px) {
        .demo-contrast .cc-controls { grid-template-columns: 1fr; }
    }
    .demo-contrast .cc-field {
        display: flex;
        flex-direction: column;
        gap: 0.45rem;
    }
    .demo-contrast .cc-field-label {
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: var(--wp--preset--color--muted);
    }
    .demo-contrast .cc-field-row {
        display: flex;
        gap: 0.5rem;
        align-items: stretch;
    }
    .demo-contrast .cc-color {
        width: 3.25rem;
        height: 2.6rem;
        padding: 0;
        border: 1px solid var(--wp--preset--color--subtle);
        border-radius: 0.5rem;
        background: var(--wp--preset--color--surface);
        cursor: pointer;
        flex: 0 0 auto;
    }
    .demo-contrast .cc-color::-webkit-color-swatch-wrapper { padding: 2px; }
    .demo-contrast .cc-color::-webkit-color-swatch { border-radius: 0.35rem; border: none; }
    .demo-contrast .cc-color::-moz-color-swatch { border-radius: 0.35rem; border: none; }

    .demo-contrast .cc-hex {
        flex: 1 1 auto;
        min-width: 0;
        font-family: var(--wp--preset--font-family--mono);
        font-size: 1rem;
        padding: 0 0.75rem;
        background: var(--wp--preset--color--surface);
        color: var(--wp--preset--color--foreground);
        border: 1px solid var(--wp--preset--color--subtle);
        border-radius: 0.5rem;
        text-transform: lowercase;
    }
    .demo-contrast .cc-hex:focus {
        outline: 2px solid var(--wp--preset--color--primary-soft);
        outline-offset: 2px;
    }
    .demo-contrast .cc-hex.is-invalid {
        border-color: var(--wp--preset--color--hot);
    }

    .demo-contrast .cc-btn {
        font: inherit;
        font-size: 0.9rem;
        padding: 0.55rem 0.95rem;
        background: var(--wp--preset--color--surface);
        color: var(--wp--preset--color--foreground);
        border: 1px solid var(--wp--preset--color--subtle);
        border-radius: 999px;
        cursor: pointer;
        transition: border-color .15s, transform .1s;
    }
    .demo-contrast .cc-btn:hover { border-color: var(--wp--preset--color--accent); }
    .demo-contrast .cc-btn:active { transform: translateY(1px); }
    .demo-contrast .cc-btn.is-copied { border-color: var(--wp--preset--color--accent-soft); }

    .demo-contrast .cc-preview {
        padding: var(--wp--preset--spacing--40);
        border-radius: 1rem;
        border: 1px solid var(--wp--preset--color--subtle);
        display: grid;
        gap: 0.75rem;
        transition: background-color .15s, color .15s;
    }
    .demo-contrast .cc-prev-large       { font-size: 1.5rem;    line-height: 1.3; margin: 0; font-weight: 400; }
    .demo-contrast .cc-prev-large-bold  { font-size: 1.17rem;   line-height: 1.4; margin: 0; }
    .demo-contrast .cc-prev-normal      { font-size: 1rem;      line-height: 1.5; margin: 0; }
    .demo-contrast .cc-prev-small       { font-size: 0.85rem;   line-height: 1.5; margin: 0; opacity: 0.85; }
    .demo-contrast .cc-prev-button {
        align-self: start;
        margin-top: 0.5rem;
        padding: 0.55rem 1.2rem;
        border-radius: 999px;
        border: 1px solid currentColor;
        font-weight: 600;
        font-size: 0.95rem;
    }

    .demo-contrast .cc-readout {
        display: flex;
        flex-wrap: wrap;
        align-items: baseline;
        gap: 1.5rem;
        padding: var(--wp--preset--spacing--30) var(--wp--preset--spacing--40);
        background: var(--wp--preset--color--surface);
        border: 1px solid var(--wp--preset--color--subtle);
        border-radius: 1rem;
    }
    .demo-contrast .cc-ratio {
        font-family: var(--wp--preset--font-family--mono);
        line-height: 1;
        display: flex;
        align-items: baseline;
        gap: 0.35rem;
    }
    .demo-contrast .cc-ratio-num {
        font-size: clamp(2.25rem, 1.5rem + 3vw, 3.5rem);
        font-weight: 700;
        background: var(--wp--preset--gradient--brand);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
    }
    .demo-contrast .cc-ratio-suffix {
        font-size: 1.25rem;
        color: var(--wp--preset--color--muted);
    }
    .demo-contrast .cc-grade {
        font-family: var(--wp--preset--font-family--mono);
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        padding: 0.4rem 0.85rem;
        border-radius: 999px;
        border: 1px solid var(--wp--preset--color--subtle);
        color: var(--wp--preset--color--muted);
    }
    .demo-contrast .cc-grade.is-aaa { color: #4ade80; border-color: #4ade8055; }
    .demo-contrast .cc-grade.is-aa  { color: var(--wp--preset--color--highlight); border-color: #facc1555; }
    .demo-contrast .cc-grade.is-fail{ color: var(--wp--preset--color--hot); border-color: #f43f5e55; }

    .demo-contrast .cc-tiles {
        list-style: none;
        margin: 0;
        padding: 0;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 0.6rem;
    }
    .demo-contrast .cc-tile {
        padding: 0.85rem 1rem;
        background: var(--wp--preset--color--surface);
        border: 1px solid var(--wp--preset--color--subtle);
        border-radius: 0.75rem;
        display: grid;
        grid-template-columns: auto 1fr;
        grid-template-rows: auto auto;
        gap: 0.05rem 0.75rem;
        align-items: center;
        transition: border-color .15s, background-color .15s;
    }
    .demo-contrast .cc-tile-mark {
        grid-row: 1 / 3;
        font-size: 1.5rem;
        font-weight: 700;
        line-height: 1;
        width: 1.75rem;
        height: 1.75rem;
        display: inline-grid;
        place-items: center;
        border-radius: 999px;
        background: rgba(148, 163, 184, 0.15);
        color: var(--wp--preset--color--muted);
    }
    .demo-contrast .cc-tile-name {
        font-weight: 700;
        font-size: 0.95rem;
    }
    .demo-contrast .cc-tile-detail {
        font-family: var(--wp--preset--font-family--mono);
        font-size: 0.78rem;
        color: var(--wp--preset--color--muted);
    }
    .demo-contrast .cc-tile.is-pass {
        border-color: rgba(74, 222, 128, 0.35);
        background: rgba(74, 222, 128, 0.06);
    }
    .demo-contrast .cc-tile.is-pass .cc-tile-mark {
        background: rgba(74, 222, 128, 0.18);
        color: #4ade80;
    }
    .demo-contrast .cc-tile.is-fail {
        border-color: rgba(244, 63, 94, 0.3);
    }
    .demo-contrast .cc-tile.is-fail .cc-tile-mark {
        background: rgba(244, 63, 94, 0.15);
        color: var(--wp--preset--color--hot);
    }

    .demo-contrast .cc-footnote {
        font-size: 0.85rem;
        color: var(--wp--preset--color--muted);
        line-height: 1.55;
        margin: 0;
    }
    </style>

    <script>
    /* WCAG 2.x contrast checker — vanilla, ~80 lines of logic.
       Math: w3.org/TR/WCAG22/#dfn-contrast-ratio
       URL hash carries ?fg=…&bg=… for shareable permalinks. */
    (function () {
        const root = document.querySelector('.demo-contrast .cc');
        if (!root) return;

        const $ = sel => root.querySelector(sel);
        const fgColor = $('[data-role="fg-color"]');
        const fgHex   = $('[data-role="fg-hex"]');
        const bgColor = $('[data-role="bg-color"]');
        const bgHex   = $('[data-role="bg-hex"]');
        const preview = $('[data-role="preview"]');
        const ratioEl = $('[data-role="ratio"]');
        const gradeEl = $('[data-role="grade"]');
        const tilesEl = $('[data-role="tiles"]');

        const THRESHOLDS = {
            'aa-normal':  4.5,
            'aa-large':   3.0,
            'aaa-normal': 7.0,
            'aaa-large':  4.5,
            'ui':         3.0,
        };

        function normalizeHex(input) {
            let h = String(input || '').trim().toLowerCase().replace(/^#/, '');
            if (/^[0-9a-f]{3}$/.test(h)) h = h.split('').map(c => c + c).join('');
            return /^[0-9a-f]{6}$/.test(h) ? '#' + h : null;
        }

        function hexToRgb(hex) {
            const n = parseInt(hex.slice(1), 16);
            return [(n >> 16) & 255, (n >> 8) & 255, n & 255];
        }

        function luminance([r, g, b]) {
            const ch = c => {
                c = c / 255;
                return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
            };
            return 0.2126 * ch(r) + 0.7152 * ch(g) + 0.0722 * ch(b);
        }

        function contrast(hexA, hexB) {
            const lA = luminance(hexToRgb(hexA));
            const lB = luminance(hexToRgb(hexB));
            return (Math.max(lA, lB) + 0.05) / (Math.min(lA, lB) + 0.05);
        }

        function gradeFor(ratio) {
            if (ratio >= 7)   return { label: 'AAA', cls: 'is-aaa' };
            if (ratio >= 4.5) return { label: 'AA',  cls: 'is-aa'  };
            return { label: 'Fail', cls: 'is-fail' };
        }

        function render(fg, bg) {
            const ratio = contrast(fg, bg);
            const rounded = Math.round(ratio * 100) / 100;

            preview.style.background = bg;
            preview.style.color = fg;
            ratioEl.textContent = rounded.toFixed(2);

            const g = gradeFor(ratio);
            gradeEl.textContent = g.label;
            gradeEl.className = 'cc-grade ' + g.cls;

            tilesEl.querySelectorAll('.cc-tile').forEach(tile => {
                const min = THRESHOLDS[tile.dataset.tile];
                const passes = ratio >= min;
                tile.classList.toggle('is-pass', passes);
                tile.classList.toggle('is-fail', !passes);
                tile.querySelector('.cc-tile-mark').textContent = passes ? '✓' : '✗';
            });

            // Reflect to URL — replaceState so it doesn't pollute history.
            const url = new URL(location.href);
            url.searchParams.set('fg', fg.slice(1));
            url.searchParams.set('bg', bg.slice(1));
            history.replaceState(null, '', url);
        }

        // Pair of inputs (color picker + hex text) stays in sync, then re-renders.
        function bindPair(colorInput, hexInput, otherColorInput) {
            colorInput.addEventListener('input', () => {
                hexInput.value = colorInput.value;
                hexInput.classList.remove('is-invalid');
                render(fgColor.value, bgColor.value);
            });
            hexInput.addEventListener('input', () => {
                const norm = normalizeHex(hexInput.value);
                if (norm) {
                    hexInput.classList.remove('is-invalid');
                    colorInput.value = norm;
                    render(fgColor.value, bgColor.value);
                } else {
                    hexInput.classList.add('is-invalid');
                }
            });
            hexInput.addEventListener('blur', () => {
                // Snap text input back to the canonical 7-char form.
                hexInput.value = colorInput.value;
                hexInput.classList.remove('is-invalid');
            });
        }

        function setPair(fg, bg) {
            fgColor.value = fg; fgHex.value = fg;
            bgColor.value = bg; bgHex.value = bg;
            render(fg, bg);
        }

        function randomHex() {
            return '#' + Math.floor(Math.random() * 0xffffff).toString(16).padStart(6, '0');
        }

        // Initial values: URL params win, otherwise the brand defaults.
        const params = new URLSearchParams(location.search);
        const initFg = normalizeHex(params.get('fg')) || root.dataset.defaultFg;
        const initBg = normalizeHex(params.get('bg')) || root.dataset.defaultBg;
        setPair(initFg, initBg);

        bindPair(fgColor, fgHex, bgColor);
        bindPair(bgColor, bgHex, fgColor);

        root.querySelector('[data-action="swap"]').addEventListener('click', () => {
            setPair(bgColor.value, fgColor.value);
        });
        root.querySelector('[data-action="random"]').addEventListener('click', () => {
            setPair(randomHex(), randomHex());
        });
        root.querySelector('[data-action="copy"]').addEventListener('click', async (e) => {
            const btn = e.currentTarget;
            try {
                await navigator.clipboard.writeText(location.href);
                const original = btn.textContent;
                btn.textContent = '✓ copied';
                btn.classList.add('is-copied');
                setTimeout(() => { btn.textContent = original; btn.classList.remove('is-copied'); }, 1400);
            } catch (_) {
                // Older browsers / insecure contexts: just select the URL bar mentally.
                btn.textContent = 'copy not supported';
            }
        });
    })();
    </script>
    <!-- /wp:html -->

</div>
<!-- /wp:group -->
