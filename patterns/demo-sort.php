<?php
/**
 * Title: Demo — Sort Race
 * Slug: dariobimbi/demo-sort
 * Categories: dariobimbi
 * Description: Four sorting algorithms (bubble, insertion, merge, quick) race the same shuffled array, each in its own canvas. Each algorithm is implemented as a step-generator so the visualization can pause between operations. Comparison + swap counters update live, and the first to finish wins a glow.
 *
 * To use: parent Page "Demos" (slug `demos`), child Page "Sort Race"
 * (slug `sort`, parent = Demos), pick the "Demo — Sort Race" template.
 * Final URL: /demos/sort/.
 */
?>
<!-- wp:group {"align":"full","className":"content-page demo-sort","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull content-page demo-sort" style="padding-top:var(--wp--preset--spacing--70);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--70);padding-left:var(--wp--preset--spacing--30)">

    <!-- wp:paragraph {"className":"eyebrow"} -->
    <p class="eyebrow">algorithms, racing</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"level":1,"className":"has-gradient-text","style":{"spacing":{"margin":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20"}}}} -->
    <h1 class="wp-block-heading has-gradient-text" style="margin-top:var(--wp--preset--spacing--20);margin-bottom:var(--wp--preset--spacing--20)">Sort Race</h1>
    <!-- /wp:heading -->

    <!-- wp:paragraph {"textColor":"muted","style":{"typography":{"lineHeight":"1.55"},"spacing":{"margin":{"bottom":"var:preset|spacing|40"}}}} -->
    <p class="has-muted-color has-text-color" style="line-height:1.55;margin-bottom:var(--wp--preset--spacing--40)">Four sorting algorithms run on the same shuffled array and race each other to finish first. Each one is written as a step-generator, which means the algorithm hands control back to the page after every comparison or swap so the canvas can draw between steps. The slower O(n²) sorts (bubble and insertion) usually finish well after merge and quick. <strong>Bigger arrays make the gap between them more obvious.</strong></p>
    <!-- /wp:paragraph -->

    <!-- wp:html -->
    <div class="sr">
        <div class="sr-controls">
            <div class="sr-knob sr-knob--wide">
                <label for="sr-size">Array size <span data-role="size-out">60</span></label>
                <input id="sr-size" type="range" min="20" max="180" step="2" value="60" data-role="size">
            </div>
            <div class="sr-knob sr-knob--wide">
                <label for="sr-speed">Speed <span data-role="speed-out">8</span> ops/frame</label>
                <input id="sr-speed" type="range" min="1" max="40" step="1" value="8" data-role="speed">
            </div>
            <div class="sr-actions">
                <button type="button" class="sr-btn"            data-action="shuffle">⤴ Shuffle</button>
                <button type="button" class="sr-btn sr-btn--go" data-action="toggle" data-state="idle">
                    <span class="sr-btn-icon" aria-hidden="true">▶</span>
                    <span class="sr-btn-label">Race</span>
                </button>
            </div>
        </div>

        <div class="sr-grid">
            <!-- These four panels are templated rather than hand-rolled in JS so the
                 markup is greppable and easy to restyle. JS just looks them up by id. -->
            <article class="sr-panel" data-algo="bubble">
                <header>
                    <h3>Bubble</h3>
                    <span class="sr-complexity">O(n²)</span>
                </header>
                <canvas data-canvas width="500" height="200" role="img" aria-label="Bubble sort: bars showing the array as it gets sorted."></canvas>
                <footer>
                    <span class="sr-stat">cmp <strong data-stat="cmp">0</strong></span>
                    <span class="sr-stat">swap <strong data-stat="swap">0</strong></span>
                    <span class="sr-status" data-status>idle</span>
                </footer>
            </article>
            <article class="sr-panel" data-algo="insertion">
                <header>
                    <h3>Insertion</h3>
                    <span class="sr-complexity">O(n²)</span>
                </header>
                <canvas data-canvas width="500" height="200" role="img" aria-label="Insertion sort: bars showing the array as it gets sorted."></canvas>
                <footer>
                    <span class="sr-stat">cmp <strong data-stat="cmp">0</strong></span>
                    <span class="sr-stat">swap <strong data-stat="swap">0</strong></span>
                    <span class="sr-status" data-status>idle</span>
                </footer>
            </article>
            <article class="sr-panel" data-algo="merge">
                <header>
                    <h3>Merge</h3>
                    <span class="sr-complexity">O(n log n)</span>
                </header>
                <canvas data-canvas width="500" height="200" role="img" aria-label="Merge sort: bars showing the array as it gets sorted."></canvas>
                <footer>
                    <span class="sr-stat">cmp <strong data-stat="cmp">0</strong></span>
                    <span class="sr-stat">swap <strong data-stat="swap">0</strong></span>
                    <span class="sr-status" data-status>idle</span>
                </footer>
            </article>
            <article class="sr-panel" data-algo="quick">
                <header>
                    <h3>Quick</h3>
                    <span class="sr-complexity">O(n log n) avg</span>
                </header>
                <canvas data-canvas width="500" height="200" role="img" aria-label="Quick sort: bars showing the array as it gets sorted."></canvas>
                <footer>
                    <span class="sr-stat">cmp <strong data-stat="cmp">0</strong></span>
                    <span class="sr-stat">swap <strong data-stat="swap">0</strong></span>
                    <span class="sr-status" data-status>idle</span>
                </footer>
            </article>
        </div>

        <p class="sr-leader" data-role="leader" aria-live="polite">Press <strong>Race</strong> to start. Highlighted bars are the indices the algorithm is currently looking at.</p>
    </div>
    <!-- /wp:html -->

</div>
<!-- /wp:group -->

<!-- wp:html -->
<style>
.demo-sort {
    --sr-bg: #12100c;
    --sr-panel: #14110d;
    --sr-accent: #f43f5e;
    --sr-accent-2: #e11d48;
    --sr-running: #facc15;
}

.demo-sort .sr {
    margin-top: 1rem;
    background: var(--wp--preset--color--surface);
    border: 1px solid var(--wp--preset--color--subtle);
    border-radius: 16px;
    padding: 1.5rem;
}

.demo-sort .sr-controls {
    display: flex;
    gap: 1rem;
    align-items: flex-end;
    flex-wrap: wrap;
    margin-bottom: 1.25rem;
}
.demo-sort .sr-knob {
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
}
.demo-sort .sr-knob--wide { flex: 1 1 220px; }
.demo-sort .sr-knob label {
    font-family: var(--wp--preset--font-family--mono);
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--wp--preset--color--muted);
}
.demo-sort .sr-knob label span { color: var(--wp--preset--color--accent-soft); }
.demo-sort .sr-knob input[type="range"] {
    accent-color: var(--wp--preset--color--accent);
    background: var(--sr-bg);
    border-radius: 8px;
}

.demo-sort .sr-actions {
    display: flex;
    gap: 0.55rem;
    flex-wrap: wrap;
    margin-left: auto;
}
.demo-sort .sr-btn {
    cursor: pointer;
    border: 1px solid var(--wp--preset--color--subtle);
    background: var(--wp--preset--color--surface);
    color: var(--wp--preset--color--foreground);
    border-radius: 999px;
    padding: 0.65rem 1.2rem;
    font-weight: 700;
    font-size: 0.9rem;
    transition: transform .15s ease, box-shadow .25s ease, border-color .15s ease, background .25s ease, color .15s ease;
}
.demo-sort .sr-btn:hover {
    border-color: var(--wp--preset--color--primary-soft);
    color: #fff;
    transform: translateY(-1px);
}
.demo-sort .sr-btn:active { transform: translateY(0); }
.demo-sort .sr-btn:disabled {
    opacity: 0.45;
    cursor: not-allowed;
    transform: none;
}
.demo-sort .sr-btn:disabled:hover {
    border-color: var(--wp--preset--color--subtle);
    color: var(--wp--preset--color--foreground);
    transform: none;
}
.demo-sort .sr-knob input[type="range"]:disabled { opacity: 0.45; cursor: not-allowed; }
.demo-sort .sr-btn:focus-visible {
    outline: 2px solid var(--wp--preset--color--primary-soft);
    outline-offset: 3px;
}

/* Race/Pause/Resume toggle. Same button, different state via data-state. */
.demo-sort .sr-btn--go {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    border-color: transparent;
    color: #fff;
    background: linear-gradient(135deg, var(--sr-accent), var(--sr-accent-2));
    background-size: 200% 200%;
    background-position: 0% 50%;
    box-shadow: 0 8px 22px -10px color-mix(in srgb, var(--sr-accent-2) 55%, transparent);
}
.demo-sort .sr-btn--go:hover {
    background-position: 100% 50%;
    box-shadow: 0 12px 28px -8px color-mix(in srgb, var(--sr-accent-2) 70%, transparent);
    transform: translateY(-1px);
}
.demo-sort .sr-btn--go[data-state="running"] {
    background: linear-gradient(135deg, var(--sr-accent), var(--sr-running));
    background-size: 200% 200%;
    background-position: 0% 50%;
    box-shadow: 0 8px 22px -10px color-mix(in srgb, var(--sr-accent) 55%, transparent);
}
.demo-sort .sr-btn--go[data-state="running"]:hover {
    background-position: 100% 50%;
    box-shadow: 0 12px 28px -8px color-mix(in srgb, var(--sr-accent) 70%, transparent);
}
.demo-sort .sr-btn--go .sr-btn-icon {
    display: inline-block;
    line-height: 1;
    transition: transform .25s ease;
}
.demo-sort .sr-btn--go:hover .sr-btn-icon { transform: scale(1.15); }
/* Subtle pulse on the icon while a race is running. */
.demo-sort .sr-btn--go[data-state="running"] .sr-btn-icon {
    animation: sr-pulse 1.1s ease-in-out infinite;
}
@keyframes sr-pulse {
    0%, 100% { opacity: 1;   transform: scale(1); }
    50%      { opacity: 0.55; transform: scale(0.85); }
}
@media (prefers-reduced-motion: reduce) {
    .demo-sort .sr-btn,
    .demo-sort .sr-btn--go .sr-btn-icon { transition: none; animation: none; }
    .demo-sort .sr-btn:hover { transform: none; }
}

/* --- Race grid --- */
.demo-sort .sr-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1rem;
}
.demo-sort .sr-panel {
    background: var(--sr-panel);
    border: 1px solid var(--wp--preset--color--subtle);
    border-radius: 12px;
    overflow: hidden;
    transition: border-color .25s ease, box-shadow .25s ease;
}
.demo-sort .sr-panel.is-running { border-color: var(--wp--preset--color--primary); }
.demo-sort .sr-panel.is-done    { border-color: var(--wp--preset--color--accent); }
/* Placement glows — gold / silver / bronze / neutral. */
/* brand-fidelity — podium medals, intentional */
.demo-sort .sr-panel.is-place-1 {
    border-color: #facc15;
    box-shadow: 0 0 0 2px rgba(250,204,21,0.35), 0 16px 40px -12px rgba(250,204,21,0.4);
}
.demo-sort .sr-panel.is-place-2 {
    border-color: #cbd5e1;
    box-shadow: 0 0 0 2px rgba(203,213,225,0.28), 0 14px 32px -14px rgba(203,213,225,0.32);
}
.demo-sort .sr-panel.is-place-3 {
    border-color: #d97706;
    box-shadow: 0 0 0 2px rgba(217,119,6,0.28), 0 14px 32px -14px rgba(217,119,6,0.32);
}
.demo-sort .sr-panel.is-place-4 {
    border-color: var(--wp--preset--color--muted);
    box-shadow: 0 8px 22px -14px rgba(148,163,184,0.25);
}

.demo-sort .sr-panel header {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--wp--preset--color--subtle);
}
.demo-sort .sr-panel h3 {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--wp--preset--color--foreground);
}
.demo-sort .sr-complexity {
    font-family: var(--wp--preset--font-family--mono);
    font-size: 0.75rem;
    color: var(--wp--preset--color--muted);
}
.demo-sort .sr-panel canvas {
    display: block;
    width: 100%;
    height: 200px;
    background: var(--sr-panel);
}
.demo-sort .sr-panel footer {
    padding: 0.55rem 1rem;
    display: flex;
    align-items: center;
    gap: 0.85rem;
    border-top: 1px solid var(--wp--preset--color--subtle);
    font-family: var(--wp--preset--font-family--mono);
    font-size: 0.78rem;
    color: var(--wp--preset--color--muted);
}
.demo-sort .sr-panel footer strong {
    color: var(--wp--preset--color--foreground);
    font-weight: 600;
    margin-left: 0.25rem;
}
.demo-sort .sr-status {
    margin-left: auto;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    font-size: 0.72rem;
}
.demo-sort .sr-panel.is-running .sr-status { color: var(--wp--preset--color--primary-soft); }
.demo-sort .sr-panel.is-done    .sr-status { color: var(--wp--preset--color--accent-soft); }
.demo-sort .sr-panel.is-place-1 .sr-status { color: #fde68a; font-weight: 700; }
.demo-sort .sr-panel.is-place-2 .sr-status { color: #e2e8f0; font-weight: 700; }
.demo-sort .sr-panel.is-place-3 .sr-status { color: #fbbf24; font-weight: 700; }
.demo-sort .sr-panel.is-place-4 .sr-status { color: var(--wp--preset--color--muted); font-weight: 700; }

.demo-sort .sr-leader {
    margin: 1.25rem 0 0;
    color: var(--wp--preset--color--muted);
    font-size: 0.92rem;
    text-align: center;
}
.demo-sort .sr-leader strong { color: var(--wp--preset--color--foreground); }
</style>

<script>
(function () {
    var root = document.querySelector('.demo-sort .sr');
    if (!root) return;

    var sizeIn   = root.querySelector('[data-role="size"]');
    var sizeOut  = root.querySelector('[data-role="size-out"]');
    var spdIn    = root.querySelector('[data-role="speed"]');
    var spdOut   = root.querySelector('[data-role="speed-out"]');
    var leader   = root.querySelector('[data-role="leader"]');
    var toggle   = root.querySelector('[data-action="toggle"]');
    var togIcon  = toggle.querySelector('.sr-btn-icon');
    var togLabel = toggle.querySelector('.sr-btn-label');
    var shuffleBtn = root.querySelector('[data-action="shuffle"]');

    function setToggle(state) {
        // state: 'idle' (shows ▶ Race) | 'running' (shows ❚❚ Pause) | 'paused' (shows ▶ Resume)
        toggle.dataset.state  = state;
        togIcon.textContent   = state === 'running' ? '❚❚' : '▶';
        togLabel.textContent  = state === 'running' ? 'Pause' : state === 'paused' ? 'Resume' : 'Race';
        toggle.setAttribute('aria-label', state === 'running' ? 'Pause race' : state === 'paused' ? 'Resume race' : 'Start race');
        // Size and Shuffle reset the arrays, so they only make sense off-track.
        sizeIn.disabled     = state === 'running';
        shuffleBtn.disabled = state === 'running';
    }

    var panels = {};
    root.querySelectorAll('.sr-panel').forEach(function (p) {
        panels[p.dataset.algo] = {
            el:     p,
            canvas: p.querySelector('[data-canvas]'),
            ctx:    p.querySelector('[data-canvas]').getContext('2d'),
            cmp:    p.querySelector('[data-stat="cmp"]'),
            swap:   p.querySelector('[data-stat="swap"]'),
            status: p.querySelector('[data-status]'),
            arr:    [],
            iter:   null,
            done:   false,
            cmpN:   0,
            swapN:  0,
            highlights: []
        };
    });

    /* --- Algorithms as step-generators -----------------------------------
       Each yields after every comparison or swap. The yielded value is a
       small object describing what the algo is currently looking at, so
       the renderer can highlight those bars. */

    function* bubble(a) {
        var n = a.length;
        for (var i = 0; i < n - 1; i++) {
            for (var j = 0; j < n - i - 1; j++) {
                yield { kind: 'cmp', i: j, j: j + 1 };
                if (a[j] > a[j + 1]) {
                    var t = a[j]; a[j] = a[j + 1]; a[j + 1] = t;
                    yield { kind: 'swap', i: j, j: j + 1 };
                }
            }
        }
    }

    function* insertion(a) {
        for (var i = 1; i < a.length; i++) {
            var k = i;
            while (k > 0) {
                yield { kind: 'cmp', i: k - 1, j: k };
                if (a[k - 1] > a[k]) {
                    var t = a[k - 1]; a[k - 1] = a[k]; a[k] = t;
                    yield { kind: 'swap', i: k - 1, j: k };
                    k--;
                } else break;
            }
        }
    }

    /* In-place merge sort variant — yields cmp/write events as it merges.
       Uses an auxiliary buffer per merge. */
    function* mergeSort(a) {
        function* merge(lo, mid, hi) {
            var left  = a.slice(lo, mid);
            var right = a.slice(mid, hi);
            var i = 0, j = 0, k = lo;
            // NOTE: avoid `&&` here — WordPress's content filter encodes
            // consecutive ampersands to `&#038;&#038;` even inside <script>,
            // which is a JS syntax error. Split the condition instead.
            while (i < left.length) {
                if (j >= right.length) break;
                yield { kind: 'cmp', i: lo + i, j: mid + j };
                if (left[i] <= right[j]) { a[k++] = left[i++]; }
                else                      { a[k++] = right[j++]; }
                yield { kind: 'swap', i: k - 1, j: k - 1 };
            }
            while (i < left.length)  { a[k++] = left[i++];  yield { kind: 'swap', i: k - 1, j: k - 1 }; }
            while (j < right.length) { a[k++] = right[j++]; yield { kind: 'swap', i: k - 1, j: k - 1 }; }
        }
        function* sort(lo, hi) {
            if (hi - lo <= 1) return;
            var mid = (lo + hi) >> 1;
            yield* sort(lo, mid);
            yield* sort(mid, hi);
            yield* merge(lo, mid, hi);
        }
        yield* sort(0, a.length);
    }

    function* quickSort(a) {
        function* qs(lo, hi) {
            if (lo >= hi) return;
            var pivot = a[hi];
            var i = lo - 1;
            for (var j = lo; j < hi; j++) {
                yield { kind: 'cmp', i: j, j: hi };
                if (a[j] <= pivot) {
                    i++;
                    if (i !== j) {
                        var t = a[i]; a[i] = a[j]; a[j] = t;
                        yield { kind: 'swap', i: i, j: j };
                    }
                }
            }
            var t2 = a[i + 1]; a[i + 1] = a[hi]; a[hi] = t2;
            yield { kind: 'swap', i: i + 1, j: hi };
            yield* qs(lo, i);
            yield* qs(i + 2, hi);
        }
        yield* qs(0, a.length - 1);
    }

    var algoFns = { bubble: bubble, insertion: insertion, merge: mergeSort, quick: quickSort };

    /* --- Setup / shuffle / render --- */
    function shuffle(n) {
        var a = new Array(n);
        for (var i = 0; i < n; i++) a[i] = i + 1;
        for (var i = n - 1; i > 0; i--) {
            var j = Math.floor(Math.random() * (i + 1));
            var t = a[i]; a[i] = a[j]; a[j] = t;
        }
        return a;
    }

    function fitCanvas(c) {
        var dpr = window.devicePixelRatio || 1;
        var rect = c.getBoundingClientRect();
        c.width  = Math.round(rect.width  * dpr);
        c.height = Math.round(rect.height * dpr);
        c.getContext('2d').setTransform(dpr, 0, 0, dpr, 0, 0);
    }

    function draw(panel) {
        var ctx = panel.ctx;
        var c   = panel.canvas;
        var w = c.clientWidth, h = c.clientHeight;
        var arr = panel.arr;
        var n = arr.length;
        var bw = w / n;
        ctx.clearRect(0, 0, w, h);
        for (var i = 0; i < n; i++) {
            var v = arr[i];
            var bh = (v / n) * (h - 4);
            // hue mapped to value: small=deep pink, mid=orange, large=gold.
            // The compare/write highlight is warm white so it stays distinct from the ramp.
            var hue = (350 + (v / n) * 60) % 360;
            var hl = panel.highlights.indexOf(i) !== -1;
            ctx.fillStyle = panel.done
                ? 'hsl(' + hue + ', 70%, 55%)'
                : hl
                    ? '#efe9df'
                    : 'hsl(' + hue + ', 80%, 58%)';
            ctx.fillRect(i * bw + 0.5, h - bh - 1, Math.max(1, bw - 1), bh);
        }
    }

    /* --- Race orchestration --- */
    var raf = 0, racing = false, paused = false, finishedOrder = [];

    /* Placement labels per finish position. Index = order they finished. */
    var PLACE_LABEL = ['🥇 1st', '🥈 2nd', '🥉 3rd', '4th'];
    var PLACE_CLASS = ['is-place-1', 'is-place-2', 'is-place-3', 'is-place-4'];

    function reset() {
        finishedOrder = [];
        paused = false;
        leader.innerHTML = 'Press <strong>Race</strong> to start. Highlighted bars are the indices the algorithm is currently looking at.';
        var size = parseInt(sizeIn.value, 10);
        var seed = shuffle(size);
        Object.keys(panels).forEach(function (k) {
            var p = panels[k];
            p.arr = seed.slice();
            p.iter = null;
            p.done = false;
            p.cmpN = 0;
            p.swapN = 0;
            p.highlights = [];
            p.cmp.textContent = '0';
            p.swap.textContent = '0';
            p.status.textContent = 'idle';
            p.el.classList.remove('is-running', 'is-done', 'is-winner', 'is-place-1', 'is-place-2', 'is-place-3', 'is-place-4');
            fitCanvas(p.canvas);
            draw(p);
        });
        setToggle('idle');
    }

    function startRace() {
        if (racing) return;
        // A paused race resumes the iterators it already has. Anything else
        // starts from the top, with a fresh shuffle if a previous run finished.
        if (!paused) {
            var anyDone = Object.keys(panels).some(function (k) { return panels[k].done; });
            if (anyDone) reset();
            Object.keys(panels).forEach(function (k) {
                var p = panels[k];
                p.iter = algoFns[k](p.arr);
            });
        }
        paused = false;
        Object.keys(panels).forEach(function (k) {
            var p = panels[k];
            if (p.done) return;
            p.el.classList.add('is-running');
            p.status.textContent = 'running';
        });
        racing = true;
        setToggle('running');
        leader.innerHTML = 'Racing… <strong>highlighted bars</strong> are being compared or written.';
        loop();
    }

    function stopRace() {
        racing = false;
        paused = true;
        cancelAnimationFrame(raf);
        Object.keys(panels).forEach(function (k) {
            var p = panels[k];
            if (!p.done) {
                p.el.classList.remove('is-running');
                p.status.textContent = 'paused';
            }
        });
        setToggle('paused');
        leader.innerHTML = 'Paused. <strong>Resume</strong> picks up where it left off, and Shuffle starts over.';
    }

    function loop() {
        if (!racing) return;
        var stepsPerFrame = parseInt(spdIn.value, 10);

        Object.keys(panels).forEach(function (k) {
            var p = panels[k];
            if (p.done || !p.iter) return;
            p.highlights = [];
            for (var s = 0; s < stepsPerFrame; s++) {
                var r = p.iter.next();
                if (r.done) {
                    p.done = true;
                    p.iter = null;
                    finishedOrder.push(k);
                    var place = finishedOrder.length;            // 1-based
                    var idx   = Math.min(place - 1, PLACE_LABEL.length - 1);
                    p.el.classList.remove('is-running');
                    p.el.classList.add('is-done', PLACE_CLASS[idx]);
                    if (place === 1) p.el.classList.add('is-winner');
                    p.status.textContent = PLACE_LABEL[idx];
                    if (place === 1) {
                        leader.innerHTML = '<strong>' + k.charAt(0).toUpperCase() + k.slice(1) + '</strong> finished first.';
                    }
                    break;
                }
                if (r.value.kind === 'cmp')  p.cmpN++;
                else                          p.swapN++;
                p.highlights = [r.value.i, r.value.j];
            }
            p.cmp.textContent  = p.cmpN;
            p.swap.textContent = p.swapN;
            draw(p);
        });

        // Stop once everyone is done.
        var allDone = Object.keys(panels).every(function (k) { return panels[k].done; });
        if (allDone) {
            racing = false;
            setToggle('idle');
            var ordered = finishedOrder.map(function (k, i) {
                var medal = i === 0 ? '🥇' : i === 1 ? '🥈' : i === 2 ? '🥉' : (i + 1) + '.';
                return medal + ' ' + k;
            }).join(' · ');
            leader.innerHTML = 'Final order: ' + ordered;
            return;
        }
        raf = requestAnimationFrame(loop);
    }

    /* --- Wire up --- */
    sizeIn.addEventListener('input', function () {
        sizeOut.textContent = sizeIn.value;
        if (!racing) reset();
    });
    spdIn.addEventListener('input', function () {
        spdOut.textContent = spdIn.value;
    });
    shuffleBtn.addEventListener('click', function () {
        if (!racing) reset();
    });
    toggle.addEventListener('click', function () {
        if (racing) stopRace();
        else        startRace();
    });

    window.addEventListener('resize', function () {
        Object.keys(panels).forEach(function (k) {
            fitCanvas(panels[k].canvas);
            draw(panels[k]);
        });
    });

    reset();
})();
</script>
<!-- /wp:html -->
