<?php
/**
 * Title: Demo — Microgame
 * Slug: dariobimbi/demo-microgame
 * Categories: dariobimbi
 * Description: A maze game from a DePaul intro class, originally written in Processing, hand-ported to vanilla JavaScript and Canvas. Use WASD to move, do not touch the walls, and reach the orange goal before the ten-second timer runs out.
 *
 * To use: parent Page "Demos" (slug `demos`), child Page "Microgame"
 * (slug `microgame`, parent = Demos), pick the "Demo — Microgame" template.
 * Final URL: /demos/microgame/.
 *
 * Image assets live at /wp-content/themes/dariobimbi/assets/games/microgame/.
 * Wall layout and collision boxes are kept identical to the original .pde.
 */
$assets = esc_url( get_stylesheet_directory_uri() . '/assets/games/microgame/' );
?>
<!-- wp:group {"align":"full","className":"content-page demo-microgame","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull content-page demo-microgame" style="padding-top:var(--wp--preset--spacing--70);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--70);padding-left:var(--wp--preset--spacing--30)">

    <!-- wp:paragraph {"className":"eyebrow"} -->
    <p class="eyebrow">another old school game, ported</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"level":1,"className":"has-gradient-text","style":{"spacing":{"margin":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20"}}}} -->
    <h1 class="wp-block-heading has-gradient-text" style="margin-top:var(--wp--preset--spacing--20);margin-bottom:var(--wp--preset--spacing--20)">Microgame</h1>
    <!-- /wp:heading -->

    <!-- wp:paragraph {"textColor":"muted","style":{"typography":{"lineHeight":"1.55"},"spacing":{"margin":{"bottom":"var:preset|spacing|40"}}}} -->
    <p class="has-muted-color has-text-color" style="line-height:1.55;margin-bottom:var(--wp--preset--spacing--40)">A small maze game I made for an intro class at DePaul, originally written in Processing and hand-ported here to vanilla JavaScript and Canvas. Press <strong>Play</strong>, then <strong>Enter</strong> to start the timer, and use <strong>WASD</strong> to move the red square through the maze and reach the orange goal at the bottom right before the ten-second timer runs out. Touching a wall ends the run.</p>
    <!-- /wp:paragraph -->

    <!-- wp:html -->
    <div class="mg" data-assets-base="<?php echo $assets; ?>" tabindex="0">
        <div class="mg-stage">
            <canvas class="mg-canvas" data-role="canvas" width="450" height="600" aria-label="Microgame maze canvas"></canvas>
        </div>
        <div class="mg-bar" data-role="bar">
            <button type="button" class="mg-btn" data-act="play">Play</button>
            <button type="button" class="mg-btn" data-act="instr">Instructions</button>
            <button type="button" class="mg-btn" data-act="back" hidden>Back</button>
            <button type="button" class="mg-btn" data-act="start" hidden>Start</button>
            <button type="button" class="mg-btn" data-act="restart" hidden>Restart</button>
        </div>
        <div class="mg-pad" data-role="pad" role="group" aria-label="Movement pad">
            <button type="button" class="mg-btn mg-pad-up" data-key="w" aria-label="Move up">&#9650;</button>
            <button type="button" class="mg-btn mg-pad-left" data-key="a" aria-label="Move left">&#9664;</button>
            <button type="button" class="mg-btn mg-pad-right" data-key="d" aria-label="Move right">&#9654;</button>
            <button type="button" class="mg-btn mg-pad-down" data-key="s" aria-label="Move down">&#9660;</button>
        </div>
        <p class="mg-hint">Press <kbd>Play</kbd>, then <kbd>Start</kbd> or <kbd>Enter</kbd>, then move with <kbd>W</kbd> <kbd>A</kbd> <kbd>S</kbd> <kbd>D</kbd>. On a phone, use the arrow pad.</p>
    </div>
    <!-- /wp:html -->

</div>
<!-- /wp:group -->

<!-- wp:html -->
<style>
.demo-microgame .mg {
    margin-top: 1rem;
    background: var(--wp--preset--color--surface);
    border: 1px solid var(--wp--preset--color--subtle);
    border-radius: 16px;
    padding: 1.5rem;
    outline: none;
}
.demo-microgame .mg:focus-visible {
    box-shadow: 0 0 0 3px var(--wp--preset--color--primary-soft);
}
.demo-microgame .mg-stage {
    background: #14110d;
    border: 1px solid var(--wp--preset--color--subtle);
    border-radius: 12px;
    overflow: hidden;
    aspect-ratio: 450 / 600;
    max-width: 450px;
    margin: 0 auto;
}
.demo-microgame .mg-canvas {
    display: block;
    width: 100%;
    height: 100%;
}
.demo-microgame .mg-bar {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 0.75rem;
    margin-top: 1rem;
}
.demo-microgame .mg-bar[hidden],
.demo-microgame .mg-btn[hidden] {
    display: none;
}
.demo-microgame .mg-btn {
    min-width: 44px;
    min-height: 44px;
    padding: 0.5rem 1.25rem;
    font: inherit;
    font-family: var(--wp--preset--font-family--mono);
    color: var(--wp--preset--color--foreground);
    background: var(--wp--preset--color--surface);
    border: 1px solid var(--wp--preset--color--subtle);
    border-radius: 10px;
    cursor: pointer;
}
.demo-microgame .mg-btn:focus-visible {
    outline: 3px solid var(--wp--preset--color--primary);
    outline-offset: 2px;
}
/* D-pad: touch screens only. Held buttons must not scroll the page. */
.demo-microgame .mg-pad {
    display: none;
    grid-template-columns: repeat(3, 56px);
    grid-template-rows: repeat(3, 56px);
    gap: 6px;
    justify-content: center;
    margin-top: 1rem;
    touch-action: none;
    user-select: none;
    -webkit-user-select: none;
    -webkit-touch-callout: none;
}
@media (pointer: coarse) {
    .demo-microgame .mg-pad { display: grid; }
}
.demo-microgame .mg-pad .mg-btn {
    padding: 0;
    touch-action: none;
    font-size: 1.25rem;
    line-height: 1;
}
.demo-microgame .mg-pad-up    { grid-column: 2; grid-row: 1; }
.demo-microgame .mg-pad-left  { grid-column: 1; grid-row: 2; }
.demo-microgame .mg-pad-right { grid-column: 3; grid-row: 2; }
.demo-microgame .mg-pad-down  { grid-column: 2; grid-row: 3; }
.demo-microgame .mg-hint {
    margin: 1rem 0 0;
    text-align: center;
    font-family: var(--wp--preset--font-family--mono);
    font-size: 0.85rem;
    color: var(--wp--preset--color--muted);
    letter-spacing: 0.04em;
}
.demo-microgame .mg-hint kbd {
    background: var(--wp--preset--color--surface);
    border: 1px solid var(--wp--preset--color--subtle);
    border-radius: 4px;
    padding: 0.05rem 0.4rem;
    font-size: 0.8rem;
    color: var(--wp--preset--color--foreground);
}
</style>

<script>
/* Microgame — vanilla JS / Canvas port of the original Processing sketch.
   Wall geometry and collision boxes are kept byte-for-byte the same as the
   original Mircogame.pde so the play feel matches what I shipped in class. */
(function () {
    var root = document.querySelector('.demo-microgame .mg');
    if (!root) return;

    var canvas = root.querySelector('[data-role="canvas"]');
    var ctx    = canvas.getContext('2d');
    var assetsBase = root.dataset.assetsBase || '';
    var bar    = root.querySelector('[data-role="bar"]');
    var pad    = root.querySelector('[data-role="pad"]');
    var reduceMotionQuery = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : null;

    var W = 450, H = 600;

    /* --- Game state --------------------------------------------------- */
    var menue = 0;     // 0 = open, 1 = play, 2 = instructions
    var win   = 0;     // 0 = playing, 1 = won, 2 = lost
    var start = 0;     // 0 = waiting for Enter, 1 = timer running
    var ticks = 0;     // 60 Hz game steps since start, so ticks / 60 is real seconds
    var TIME_LIMIT = 600;  // 10 seconds of ticks
    var x = 57, y = 70; // player top-left

    var mouseX = 0, mouseY = 0;
    var mouseDown = false;

    /* Held movement keys (keyboard and the on-screen pad share this). */
    var held = { w: false, a: false, s: false, d: false };
    var repeatCd = 0;

    /* One rect per canvas button, shared by drawing, hover and click. */
    var BTN = {
        play:    { x: 175, y: 320, w: 90,  h: 40 },
        instr:   { x: 125, y: 420, w: 200, h: 40 },
        back:    { x: 175, y: 525, w: 90,  h: 40 },
        restart: { x: 3,   y: 565, w: 135, h: 32 }
    };

    /* --- Asset loading ------------------------------------------------ */
    var imgs = {};
    var imgFiles = {
        start:        'start.jpg',
        floor:        'floor.jpg',
        winner:       'winner.jpg',
        loser:        'loser.jpg',
        instructions: 'instructions.jpg'
    };
    var loaded = 0, total = 0;
    Object.keys(imgFiles).forEach(function () { total++; });
    Object.keys(imgFiles).forEach(function (k) {
        var img = new Image();
        img.onload  = function () { loaded++; if (loaded === total) requestAnimationFrame(frame); };
        img.onerror = function () { loaded++; };
        img.src = assetsBase + imgFiles[k];
        imgs[k] = img;
    });

    /* --- Actions (canvas buttons and HTML buttons both call these) ----- */
    function doPlay()    { menue = 1; root.focus(); }
    function doInstr()   { menue = 2; }
    function doBack()    { menue = 0; }
    function doStart() {
        if (menue !== 1) return;
        if (win !== 0) return;
        start = 1;
    }

    function reset() {
        menue = 0;
        win = 0;
        x = 57; y = 70;
        start = 0;
        ticks = 0;
        releaseAll();
    }

    var actions = { play: doPlay, instr: doInstr, back: doBack, start: doStart, restart: reset };

    /* --- Input -------------------------------------------------------- */
    function getMousePos(e) {
        var rect = canvas.getBoundingClientRect();
        var sx = canvas.width  / rect.width;
        var sy = canvas.height / rect.height;
        return { x: (e.clientX - rect.left) * sx, y: (e.clientY - rect.top) * sy };
    }

    function inRect(r) {
        if (mouseX > r.x) { if (mouseX < r.x + r.w) { if (mouseY > r.y) { if (mouseY < r.y + r.h) { return true; } } } }
        return false;
    }

    function drawButton(r, label, size, color) {
        drawText(label, r.x, r.y, size, color);
    }

    canvas.addEventListener('mousemove', function (e) {
        var p = getMousePos(e); mouseX = p.x; mouseY = p.y;
    });
    canvas.addEventListener('mousedown', function (e) {
        var p = getMousePos(e); mouseX = p.x; mouseY = p.y;
        mouseDown = true;
        handleClick();
    });
    canvas.addEventListener('mouseup', function () { mouseDown = false; });

    function canMove() {
        if (menue !== 1) return false;
        if (start !== 1) return false;
        if (win !== 0) return false;
        return true;
    }

    function moveOnce(k) {
        if (k === 'w') { if (y > 55)  { y -= 15; } }
        if (k === 's') { if (y < 570) { y += 15; } }
        if (k === 'a') { if (x > 0)   { x -= 15; } }
        if (k === 'd') { if (x < 410) { x += 15; } }
    }

    function press(k) {
        if (!canMove()) return;
        moveOnce(k);
        held[k] = true;
        repeatCd = 8;   // short pause before a held key starts repeating
    }

    function release(k) { held[k] = false; }

    function releaseAll() {
        held.w = false; held.a = false; held.s = false; held.d = false;
    }

    /* Keyboard listens on the wrapper (which is focusable via tabindex). */
    root.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            /* On a focused HTML button, Enter should click that button. */
            if (e.target.tagName === 'BUTTON') return;
            doStart();
            e.preventDefault();
            return;
        }
        var k = (e.key || '').toLowerCase();
        if (['w', 'a', 's', 'd'].indexOf(k) === -1) return;
        if (!canMove()) return;
        e.preventDefault();
        if (!e.repeat) press(k);
    });
    root.addEventListener('keyup', function (e) {
        release((e.key || '').toLowerCase());
    });
    window.addEventListener('blur', releaseAll);

    function handleClick() {
        if (win !== 0) {
            if (inRect(BTN.restart)) { reset(); }
            return;
        }
        if (menue === 0) {
            if (inRect(BTN.play))  { doPlay(); }
            if (inRect(BTN.instr)) { doInstr(); }
        } else if (menue === 2) {
            if (inRect(BTN.back)) { doBack(); }
        }
    }

    /* HTML menu buttons (keyboard reachable). */
    Array.prototype.forEach.call(bar.querySelectorAll('[data-act]'), function (b) {
        b.addEventListener('click', function () {
            var fn = actions[b.dataset.act];
            if (fn) fn();
            if (b.dataset.act === 'start') root.focus();
        });
    });

    /* On-screen D-pad, shown on coarse pointers only (see the CSS). It feeds
       the same held-key state as WASD. */
    Array.prototype.forEach.call(pad.querySelectorAll('[data-key]'), function (b) {
        var k = b.dataset.key;
        b.addEventListener('pointerdown', function (e) {
            e.preventDefault();
            try { b.setPointerCapture(e.pointerId); } catch (err) { /* older browsers */ }
            press(k);
        });
        b.addEventListener('pointerup', function () { release(k); });
        b.addEventListener('pointercancel', function () { release(k); });
        b.addEventListener('lostpointercapture', function () { release(k); });
        b.addEventListener('contextmenu', function (e) { e.preventDefault(); });
    });

    /* Show only the HTML buttons that make sense for the current screen. */
    var ctlKey = '';
    function syncControls() {
        var key = menue + '|' + win + '|' + start;
        if (key === ctlKey) return;
        ctlKey = key;

        var show = { play: false, instr: false, back: false, start: false, restart: false };
        if (win !== 0) {
            show.restart = true;
        } else if (menue === 0) {
            show.play = true; show.instr = true;
        } else if (menue === 2) {
            show.back = true;
        } else if (start === 0) {
            show.start = true;
        }

        var active = document.activeElement;
        var focusWasInBar = bar.contains(active);
        var firstVisible = null;
        var any = false;
        Array.prototype.forEach.call(bar.querySelectorAll('[data-act]'), function (b) {
            var on = show[b.dataset.act];
            b.hidden = !on;
            if (on) { any = true; if (!firstVisible) firstVisible = b; }
        });
        bar.hidden = !any;

        /* A button that just hid was holding keyboard focus: hand it on. */
        if (focusWasInBar) {
            if (active.hidden) {
                if (firstVisible) firstVisible.focus();
                else root.focus();
            }
        }
    }

    /* --- Helpers ------------------------------------------------------ */
    function clearBg() {
        ctx.fillStyle = '#000';
        ctx.fillRect(0, 0, W, H);
    }

    function drawText(text, dx, dy, size, color) {
        ctx.fillStyle = color || '#fff';
        ctx.font = (size || 36) + 'px sans-serif';
        ctx.textBaseline = 'top';
        ctx.fillText(text, dx, dy);
    }

    function prefersReducedMotion() {
        if (!reduceMotionQuery) return false;
        return reduceMotionQuery.matches;
    }

    /* --- Stages ------------------------------------------------------- */
    function openScreen() {
        ticks = 0;
        clearBg();
        if (imgs.start.complete) ctx.drawImage(imgs.start, 0, 0, W, H);
        drawButton(BTN.play,  '[Play]', 36, '#fff');
        drawButton(BTN.instr, '[Instructions]', 36, '#fff');

        var hover = false;
        if (inRect(BTN.play))  hover = true;
        if (inRect(BTN.instr)) hover = true;
        canvas.style.cursor = hover ? 'pointer' : 'default';
    }

    function instructionsScreen() {
        clearBg();
        if (imgs.instructions.complete) ctx.drawImage(imgs.instructions, 0, 0, W, H);
        drawButton(BTN.back, '[Back]', 36, '#fff');

        canvas.style.cursor = inRect(BTN.back) ? 'pointer' : 'default';
    }

    var flickColor = 'rgb(200,0,0)';
    var flickAt = 0;
    var nowMs = 0;

    function gameplay() {
        // Floor and walls
        if (imgs.floor.complete) ctx.drawImage(imgs.floor, 0, 0, W, H);
        canvas.style.cursor = 'default';

        ctx.fillStyle = '#00ff00';
        // Original wall geometry, including the negative-width quirks.
        ctx.fillRect(0, 146, 300, 20);
        ctx.fillRect(0, 50, 20, 100);
        ctx.fillRect(430, 50, 20, 500);
        // rect(430, 226, -355, 20)  →  rect from (75, 226), 355x20
        ctx.fillRect(75, 226, 355, 20);
        ctx.fillRect(74, 226, 20, 200);
        ctx.fillRect(0, 504, 300, 20);
        // rect(166, 504, 20, -200)  →  rect from (166, 304), 20x200
        ctx.fillRect(166, 304, 20, 200);
        ctx.fillRect(296, 226, 20, 200);

        // Goal (orange)
        ctx.fillStyle = '#D68000';
        ctx.fillRect(300, 534, 120, 50);

        // Player
        ctx.fillStyle = '#ff0000';
        ctx.fillRect(x, y, 25, 25);

        // HUD
        var timer = Math.min(ticks, TIME_LIMIT) / 60;
        ctx.fillStyle = '#fff';
        ctx.fillRect(0, 0, 600, 50);
        ctx.font = '25px sans-serif';
        ctx.textBaseline = 'top';
        if (win === 0) {
            if (timer < 6) {
                ctx.fillStyle = '#000';
            } else if (prefersReducedMotion()) {
                ctx.fillStyle = '#c00000';
            } else {
                // Flicker about 6 times a second, not every frame.
                if (nowMs - flickAt > 160) {
                    flickColor = 'rgb(' + (100 + Math.floor(Math.random() * 156)) + ',0,0)';
                    flickAt = nowMs;
                }
                ctx.fillStyle = flickColor;
            }
            ctx.fillText('Timer : ' + timer.toFixed(2), 109, 18);
        }

        if (start === 0) {
            ctx.fillStyle = 'rgba(0,0,0,0.65)';
            ctx.fillRect(0, 200, W, 200);
            drawText('Press Enter to start', 65, 280, 28, '#fff');
        }
    }

    function collide() {
        // Walls (any hit = lose)
        if (x > 0)   { if (x < 20)  { if (y > 50)  { if (y < 165) { win = 2; } } } }
        if (x > 26)  { if (x < 300) { if (y > 129) { if (y < 166) { win = 2; } } } }
        if (x > 416) { if (x < 450) { if (y > 56)  { if (y < 550) { win = 2; } } } }
        if (x > 75)  { if (x < 430) { if (y > 204) { if (y < 245) { win = 2; } } } }
        if (x > 56)  { if (x < 95)  { if (y > 204) { if (y < 425) { win = 2; } } } }
        if (x > 146) { if (x < 185) { if (y > 279) { if (y < 500) { win = 2; } } } }
        if (x > 281) { if (x < 316) { if (y > 225) { if (y < 425) { win = 2; } } } }
        if (x > 0)   { if (x < 300) { if (y > 489) { if (y < 525) { win = 2; } } } }
        // Goal (win)
        if (x > 311) { if (x < 420) { if (y > 519) { if (y < 580) { win = 1; } } } }
        // Time-out (lose)
        if (ticks > TIME_LIMIT) win = 2;
    }

    function winScreen() {
        clearBg();
        if (imgs.winner.complete) ctx.drawImage(imgs.winner, 0, 0, W, H);
        drawButton(BTN.restart, '[Restart]', 28, '#000');
        canvas.style.cursor = inRect(BTN.restart) ? 'pointer' : 'default';
    }

    function loseScreen() {
        clearBg();
        if (imgs.loser.complete) ctx.drawImage(imgs.loser, 0, 0, W, H);
        drawButton(BTN.restart, '[Restart]', 28, '#000');
        canvas.style.cursor = inRect(BTN.restart) ? 'pointer' : 'default';
    }

    /* --- Fixed 60 Hz update ------------------------------------------- */
    /* Timer and movement advance in fixed steps so the ten seconds are real
       seconds on any refresh rate. */
    function step() {
        if (menue !== 1) return;
        if (win !== 0) return;

        if (start === 1) { ticks++; }

        // A held key or pad button repeats every few steps.
        if (held.w || held.a || held.s || held.d) {
            repeatCd--;
            if (repeatCd <= 0) {
                if (held.w) moveOnce('w');
                if (held.a) moveOnce('a');
                if (held.s) moveOnce('s');
                if (held.d) moveOnce('d');
                repeatCd = 3;
            }
        }

        collide();
    }

    /* --- Main loop ---------------------------------------------------- */
    var STEP_MS = 1000 / 60;
    var lastTs = 0;
    var acc = 0;

    function frame(ts) {
        nowMs = ts;
        if (lastTs) {
            var dt = ts - lastTs;
            if (dt > 250) dt = 250;   // tab was hidden, do not fast-forward
            acc += dt;
        }
        lastTs = ts;
        while (acc >= STEP_MS - 0.5) {
            step();
            acc -= STEP_MS;
        }

        if (win === 1) {
            winScreen();
        } else if (win === 2) {
            loseScreen();
        } else if (menue === 0) {
            openScreen();
        } else if (menue === 1) {
            gameplay();
        } else if (menue === 2) {
            instructionsScreen();
        }
        syncControls();
        requestAnimationFrame(frame);
    }
})();
</script>
<!-- /wp:html -->
