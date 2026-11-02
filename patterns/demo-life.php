<?php
/**
 * Title: Demo — Game of Life
 * Slug: dariobimbi/demo-life
 * Categories: dariobimbi
 * Description: Conway's Game of Life on a toroidal grid (edges wrap). Click or drag to toggle cells, drop in classic patterns (glider, pulsar, Gosper's gun, R-pentomino), and watch the population evolve. Cells get warmer the older they live, so stable structures glow.
 *
 * To use: parent Page "Demos" (slug `demos`), child Page "Game of Life"
 * (slug `life`, parent = Demos), pick the "Demo — Game of Life" template.
 * Final URL: /demos/life/.
 */
?>
<!-- wp:group {"align":"full","className":"content-page demo-life","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull content-page demo-life" style="padding-top:var(--wp--preset--spacing--70);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--70);padding-left:var(--wp--preset--spacing--30)">

    <!-- wp:paragraph {"className":"eyebrow"} -->
    <p class="eyebrow">cellular automata, click to seed the grid</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"level":1,"className":"has-gradient-text","style":{"spacing":{"margin":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20"}}}} -->
    <h1 class="wp-block-heading has-gradient-text" style="margin-top:var(--wp--preset--spacing--20);margin-bottom:var(--wp--preset--spacing--20)">Game of Life</h1>
    <!-- /wp:heading -->

    <!-- wp:paragraph {"textColor":"muted","style":{"typography":{"lineHeight":"1.55"},"spacing":{"margin":{"bottom":"var:preset|spacing|40"}}}} -->
    <p class="has-muted-color has-text-color" style="line-height:1.55;margin-bottom:var(--wp--preset--spacing--40)">Conway's three rules (under-population kills a cell, over-population kills a cell, and exactly three live neighbors births a new one) produce a surprising amount of structure out of almost nothing. The edges of the grid wrap around, so it is really a torus, which means gliders that fly off the right side come back in on the left. Click or drag on the grid to toggle cells, or drop in a classic pattern to start with.</p>
    <!-- /wp:paragraph -->

    <!-- wp:html -->
    <div class="gl">
        <div class="gl-stage">
            <canvas class="gl-canvas" data-role="canvas" width="900" height="500" role="img" aria-label="Game of Life grid. Live cells are drawn in warm colors that shift from pink toward yellow as they age. Click or drag to toggle cells."></canvas>
        </div>

        <div class="gl-controls">
            <div class="gl-row">
                <button type="button" class="gl-btn gl-btn--primary" data-action="toggle" aria-pressed="false">▶ Play</button>
                <button type="button" class="gl-btn" data-action="step">⏭ Step</button>
                <button type="button" class="gl-btn" data-action="random">🎲 Random</button>
                <button type="button" class="gl-btn" data-action="clear">⌫ Clear</button>
            </div>
            <div class="gl-row">
                <div class="gl-knob gl-knob--wide">
                    <label for="gl-speed">Speed <span data-role="speed-out">12</span> gen/s</label>
                    <input id="gl-speed" type="range" min="1" max="60" step="1" value="12" data-role="speed">
                </div>
                <div class="gl-knob gl-knob--wide">
                    <label for="gl-cell">Cell size <span data-role="cell-out">10</span> px</label>
                    <input id="gl-cell" type="range" min="4" max="22" step="1" value="10" data-role="cell">
                </div>
            </div>

            <div class="gl-patterns">
                <span class="gl-patterns-label">drop a pattern:</span>
                <button type="button" class="gl-pat" data-pattern="glider">Glider</button>
                <button type="button" class="gl-pat" data-pattern="lwss">Lightweight ship</button>
                <button type="button" class="gl-pat" data-pattern="pulsar">Pulsar</button>
                <button type="button" class="gl-pat" data-pattern="rpentomino">R-pentomino</button>
                <button type="button" class="gl-pat" data-pattern="gosper">Gosper's glider gun</button>
            </div>

            <div class="gl-stats">
                <span>Generation <strong data-role="gen">0</strong></span>
                <span>Population <strong data-role="pop">0</strong></span>
                <span>Grid <strong data-role="dims">0 × 0</strong></span>
            </div>
        </div>
    </div>
    <!-- /wp:html -->

</div>
<!-- /wp:group -->

<!-- wp:html -->
<style>
.demo-life {
    --gl-stage: #14110d;
    --gl-accent: #f43f5e;
    --gl-accent-2: #e11d48;
}

.demo-life .gl {
    margin-top: 1rem;
    background: var(--wp--preset--color--surface);
    border: 1px solid var(--wp--preset--color--subtle);
    border-radius: 16px;
    padding: 1.5rem;
}

.demo-life .gl-stage {
    background: var(--gl-stage);
    border: 1px solid var(--wp--preset--color--subtle);
    border-radius: 12px;
    overflow: hidden;
    aspect-ratio: 9 / 5;
}
.demo-life .gl-canvas {
    display: block;
    width: 100%;
    height: 100%;
    cursor: crosshair;
    touch-action: none;
}

.demo-life .gl-controls {
    margin-top: 1.25rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}
.demo-life .gl-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
}
.demo-life .gl-btn {
    cursor: pointer;
    border: 1px solid var(--wp--preset--color--subtle);
    background: var(--wp--preset--color--surface);
    color: var(--wp--preset--color--foreground);
    border-radius: 999px;
    padding: 0.65rem 1.2rem;
    font-weight: 700;
    font-size: 0.9rem;
    transition: filter .15s ease, border-color .15s ease;
}
.demo-life .gl-btn--primary {
    background: linear-gradient(135deg, var(--gl-accent), var(--gl-accent-2));
    border-color: transparent;
    color: #fff;
    min-width: 120px;
}
.demo-life .gl-btn:hover { filter: brightness(1.1); }

.demo-life .gl-knob {
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
}
.demo-life .gl-knob--wide { flex: 1 1 220px; }
.demo-life .gl-knob label {
    font-family: var(--wp--preset--font-family--mono);
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--wp--preset--color--muted);
}
.demo-life .gl-knob label span { color: var(--wp--preset--color--accent-soft); }
.demo-life .gl-knob input[type="range"] { accent-color: var(--wp--preset--color--accent); }

.demo-life .gl-patterns {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
    align-items: center;
}
.demo-life .gl-patterns-label {
    font-family: var(--wp--preset--font-family--mono);
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--wp--preset--color--muted);
    margin-right: 0.25rem;
}
.demo-life .gl-pat {
    cursor: pointer;
    background: transparent;
    border: 1px solid var(--wp--preset--color--subtle);
    color: var(--wp--preset--color--muted);
    padding: 0.35rem 0.75rem;
    border-radius: 999px;
    font-size: 0.82rem;
    transition: border-color .15s ease, color .15s ease, background-color .15s ease;
}
.demo-life .gl-pat:hover {
    border-color: var(--wp--preset--color--accent);
    color: var(--wp--preset--color--accent-soft);
}

.demo-life .gl-stats {
    display: flex;
    gap: 1.4rem;
    font-family: var(--wp--preset--font-family--mono);
    font-size: 0.85rem;
    color: var(--wp--preset--color--muted);
    flex-wrap: wrap;
    padding-top: 0.5rem;
    border-top: 1px dashed var(--wp--preset--color--subtle);
}
.demo-life .gl-stats strong {
    color: var(--wp--preset--color--foreground);
    font-weight: 600;
    margin-left: 0.35rem;
}
</style>

<script>
(function () {
    var root = document.querySelector('.demo-life .gl');
    if (!root) return;

    var canvas = root.querySelector('[data-role="canvas"]');
    var ctx    = canvas.getContext('2d');
    var btnTog = root.querySelector('[data-action="toggle"]');
    var spdIn  = root.querySelector('[data-role="speed"]');
    var spdOut = root.querySelector('[data-role="speed-out"]');
    var cellIn = root.querySelector('[data-role="cell"]');
    var cellOut= root.querySelector('[data-role="cell-out"]');
    var genOut = root.querySelector('[data-role="gen"]');
    var popOut = root.querySelector('[data-role="pop"]');
    var dimsOut= root.querySelector('[data-role="dims"]');

    /* --- Grid state ---
       grid: Uint8Array — 0 = dead, 1+ = alive (the value is "age" in
       generations, used for the warm-cell coloring). Width and height in
       cells. We use plain typed arrays for speed; this can comfortably hit
       40k cells at 60fps. */
    var W = 0, H = 0, CELL = 10;
    var grid, next;
    var generation = 0;
    var population = 0;
    var running = false;
    var stageColor = '#14110d';

    function fitGrid() {
        var dpr = window.devicePixelRatio || 1;
        var rect = canvas.getBoundingClientRect();
        var pw = Math.round(rect.width  * dpr);
        var ph = Math.round(rect.height * dpr);
        // Assigning width/height clears the bitmap, so only do it on a real change.
        if (canvas.width !== pw) canvas.width = pw;
        if (canvas.height !== ph) canvas.height = ph;
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        stageColor = getComputedStyle(root).getPropertyValue('--gl-stage').trim() || stageColor;
        CELL = parseInt(cellIn.value, 10);
        var nw = Math.max(8, Math.floor(rect.width  / CELL));
        var nh = Math.max(8, Math.floor(rect.height / CELL));
        // Same cell dimensions (the mobile URL bar collapsing fires resize): keep the grid.
        if (grid) {
            if (nw === W) {
                if (nh === H) { draw(); return; }
            }
        }
        var newGrid = new Uint8Array(nw * nh);
        var newNext = new Uint8Array(nw * nh);
        // Copy the overlapping region so a resize keeps what the user drew.
        if (grid) {
            var cw = Math.min(W, nw);
            var ch = Math.min(H, nh);
            for (var y = 0; y < ch; y++) {
                newGrid.set(grid.subarray(y * W, y * W + cw), y * nw);
            }
        }
        W = nw;
        H = nh;
        grid = newGrid;
        next = newNext;
        dimsOut.textContent = W + ' × ' + H;
        draw();
    }

    function idx(x, y) { return y * W + x; }

    function step() {
        // Toroidal wrap — left/right and top/bottom edges connect.
        for (var y = 0; y < H; y++) {
            var ym = (y - 1 + H) % H;
            var yp = (y + 1) % H;
            for (var x = 0; x < W; x++) {
                var xm = (x - 1 + W) % W;
                var xp = (x + 1) % W;
                var n = (grid[ym * W + xm] ? 1 : 0)
                      + (grid[ym * W + x ] ? 1 : 0)
                      + (grid[ym * W + xp] ? 1 : 0)
                      + (grid[y  * W + xm] ? 1 : 0)
                      + (grid[y  * W + xp] ? 1 : 0)
                      + (grid[yp * W + xm] ? 1 : 0)
                      + (grid[yp * W + x ] ? 1 : 0)
                      + (grid[yp * W + xp] ? 1 : 0);
                var alive = grid[y * W + x];
                // Standard B3/S23 — birth on 3, survive on 2 or 3.
                if (alive) {
                    // Cap age at 255 to fit in Uint8.
                    next[y * W + x] = (n === 2 || n === 3) ? Math.min(255, alive + 1) : 0;
                } else {
                    next[y * W + x] = (n === 3) ? 1 : 0;
                }
            }
        }
        var t = grid; grid = next; next = t;
        generation++;
    }

    function draw() {
        var w = canvas.clientWidth, h = canvas.clientHeight;
        ctx.fillStyle = stageColor;
        ctx.fillRect(0, 0, w, h);
        // Subtle grid lines when cells are big enough to see them.
        if (CELL >= 8) {
            ctx.strokeStyle = 'rgba(255,255,255,0.04)';
            ctx.lineWidth = 1;
            ctx.beginPath();
            for (var gx = 0; gx <= W; gx++) {
                ctx.moveTo(gx * CELL + 0.5, 0);
                ctx.lineTo(gx * CELL + 0.5, H * CELL);
            }
            for (var gy = 0; gy <= H; gy++) {
                ctx.moveTo(0,        gy * CELL + 0.5);
                ctx.lineTo(W * CELL, gy * CELL + 0.5);
            }
            ctx.stroke();
        }
        population = 0;
        for (var y = 0; y < H; y++) {
            for (var x = 0; x < W; x++) {
                var v = grid[y * W + x];
                if (!v) continue;
                population++;
                // Color by age: young = Dragon Pink, mature = orange, old = gold.
                // Cap perceived age around 30 generations so glow saturates.
                var t = Math.min(1, v / 30);
                var hue = (345 + t * 65) % 360; // 345 (pink) → 50 (gold)
                var light = 55 + t * 10;
                ctx.fillStyle = 'hsl(' + hue + ', 80%, ' + light + '%)';
                ctx.fillRect(x * CELL + 1, y * CELL + 1, CELL - 2, CELL - 2);
            }
        }
        genOut.textContent = generation;
        popOut.textContent = population;
    }

    /* --- Pattern library, expressed as relative [x,y] offsets. They're
       dropped centered on the grid (or where the user last clicked, if
       we wanted to extend that). */
    var patterns = {
        glider: [
            [1,0],[2,1],[0,2],[1,2],[2,2]
        ],
        lwss: [ // Lightweight spaceship
            [1,0],[4,0],[0,1],[0,2],[4,2],[0,3],[1,3],[2,3],[3,3]
        ],
        pulsar: (function () {
            // Pulsar (period 3 oscillator) — 13×13 hand-built coords.
            var raw = [
                [2,0],[3,0],[4,0],[8,0],[9,0],[10,0],
                [0,2],[5,2],[7,2],[12,2],
                [0,3],[5,3],[7,3],[12,3],
                [0,4],[5,4],[7,4],[12,4],
                [2,5],[3,5],[4,5],[8,5],[9,5],[10,5],
                [2,7],[3,7],[4,7],[8,7],[9,7],[10,7],
                [0,8],[5,8],[7,8],[12,8],
                [0,9],[5,9],[7,9],[12,9],
                [0,10],[5,10],[7,10],[12,10],
                [2,12],[3,12],[4,12],[8,12],[9,12],[10,12]
            ];
            return raw;
        })(),
        rpentomino: [
            [1,0],[2,0],[0,1],[1,1],[1,2]
        ],
        gosper: [
            [0,4],[0,5],[1,4],[1,5],
            [10,4],[10,5],[10,6],[11,3],[11,7],[12,2],[12,8],[13,2],[13,8],
            [14,5],[15,3],[15,7],[16,4],[16,5],[16,6],[17,5],
            [20,2],[20,3],[20,4],[21,2],[21,3],[21,4],[22,1],[22,5],
            [24,0],[24,1],[24,5],[24,6],
            [34,2],[34,3],[35,2],[35,3]
        ]
    };

    function dropPattern(name) {
        var p = patterns[name];
        if (!p) return;
        // Find pattern bounding box, then place it centered.
        var maxX = 0, maxY = 0;
        for (var i = 0; i < p.length; i++) {
            if (p[i][0] > maxX) maxX = p[i][0];
            if (p[i][1] > maxY) maxY = p[i][1];
        }
        var ox = Math.max(0, Math.floor((W - maxX) / 2));
        var oy = Math.max(0, Math.floor((H - maxY) / 2));
        // Don't clear — let the user combine patterns if they want.
        for (var i = 0; i < p.length; i++) {
            var x = (ox + p[i][0]) % W;
            var y = (oy + p[i][1]) % H;
            grid[y * W + x] = 1;
        }
        draw();
    }

    /* Does the pattern fit the grid with a cell of room around it? The Gosper
       gun is 36 wide and would wrap onto itself on a phone-sized grid. */
    function patternFits(name) {
        var p = patterns[name];
        var maxX = 0, maxY = 0;
        for (var i = 0; i < p.length; i++) {
            if (p[i][0] > maxX) maxX = p[i][0];
            if (p[i][1] > maxY) maxY = p[i][1];
        }
        return maxX + 3 <= W ? maxY + 3 <= H : false;
    }

    /* --- Main loop, throttled to "speed" generations per second ---
       `running` is what the user asked for. `onScreen` and `looping` only
       decide whether the rAF loop is actually spinning right now. */
    var lastTick = 0;
    var onScreen = true;
    var looping = false;
    function loop(now) {
        if (!running) { looping = false; return; }
        if (!onScreen) { looping = false; return; }
        var interval = 1000 / parseInt(spdIn.value, 10);
        if (now - lastTick >= interval) {
            step();
            draw();
            lastTick = now;
        }
        requestAnimationFrame(loop);
    }
    function kick() {
        if (looping) return;
        if (!running) return;
        if (!onScreen) return;
        looping = true;
        lastTick = 0;
        requestAnimationFrame(loop);
    }

    function play() {
        running = true;
        btnTog.textContent = '⏸ Pause';
        btnTog.setAttribute('aria-pressed', 'true');
        kick();
    }
    function pause() {
        running = false;
        btnTog.textContent = '▶ Play';
        btnTog.setAttribute('aria-pressed', 'false');
    }

    /* Stop burning CPU while the canvas is scrolled out of view. */
    if ('IntersectionObserver' in window) {
        new IntersectionObserver(function (entries) {
            onScreen = entries[entries.length - 1].isIntersecting;
            if (onScreen) kick();
        }).observe(canvas);
    }

    /* --- Click + drag to toggle cells --- */
    var painting = false, paintMode = 1;
    function cellAt(e) {
        var rect = canvas.getBoundingClientRect();
        var x = Math.floor((e.clientX - rect.left) / CELL);
        var y = Math.floor((e.clientY - rect.top)  / CELL);
        if (x < 0 || y < 0 || x >= W || y >= H) return null;
        return [x, y];
    }
    canvas.addEventListener('pointerdown', function (e) {
        var c = cellAt(e); if (!c) return;
        // First click decides whether we're painting or erasing — drag follows suit.
        paintMode = grid[c[1] * W + c[0]] ? 0 : 1;
        grid[c[1] * W + c[0]] = paintMode;
        painting = true;
        canvas.setPointerCapture(e.pointerId);
        draw();
    });
    canvas.addEventListener('pointermove', function (e) {
        if (!painting) return;
        var c = cellAt(e); if (!c) return;
        grid[c[1] * W + c[0]] = paintMode;
        draw();
    });
    canvas.addEventListener('pointerup',     function () { painting = false; });
    canvas.addEventListener('pointercancel', function () { painting = false; });

    /* --- Wire up controls --- */
    btnTog.addEventListener('click', function () { running ? pause() : play(); });
    root.querySelector('[data-action="step"]').addEventListener('click', function () {
        if (running) pause();
        step(); draw();
    });
    root.querySelector('[data-action="random"]').addEventListener('click', function () {
        for (var i = 0; i < grid.length; i++) grid[i] = Math.random() < 0.28 ? 1 : 0;
        generation = 0;
        draw();
    });
    root.querySelector('[data-action="clear"]').addEventListener('click', function () {
        grid.fill(0); generation = 0; draw();
    });
    root.querySelectorAll('[data-pattern]').forEach(function (b) {
        b.addEventListener('click', function () { dropPattern(b.dataset.pattern); });
    });
    spdIn.addEventListener('input', function () { spdOut.textContent = spdIn.value; });
    cellIn.addEventListener('input', function () {
        cellOut.textContent = cellIn.value;
        fitGrid();
    });

    // Debounced: the mobile URL bar collapsing and window drags fire resize in bursts.
    var resizeTimer = 0;
    window.addEventListener('resize', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(fitGrid, 150);
    });

    /* Boot: a glider gun is the most recognizable Life pattern, and it
       keeps producing new gliders forever, which gives the page life on landing.
       On a grid too narrow for it, an R-pentomino keeps busy on its own. */
    fitGrid();
    dropPattern(patternFits('gosper') ? 'gosper' : 'rpentomino');
    // NOTE: no double-ampersand operators in inline scripts, WordPress encodes them.
    var reduceMotion = false;
    if (window.matchMedia) reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (!reduceMotion) play();
})();
</script>
<!-- /wp:html -->
