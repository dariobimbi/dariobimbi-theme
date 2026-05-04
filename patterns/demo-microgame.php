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
        <p class="mg-hint">Click the canvas first, then press <kbd>Play</kbd>, then <kbd>Enter</kbd>, then move with <kbd>W</kbd> <kbd>A</kbd> <kbd>S</kbd> <kbd>D</kbd>.</p>
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
    background: #050813;
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

    var W = 450, H = 600;

    /* --- Game state --------------------------------------------------- */
    var menue = 0;     // 0 = open, 1 = play, 2 = instructions
    var win   = 0;     // 0 = playing, 1 = won, 2 = lost
    var start = 0;     // 0 = waiting for Enter, 1 = timer running
    var timer = 0;
    var x = 57, y = 70; // player top-left

    var mouseX = 0, mouseY = 0;
    var mouseDown = false;

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

    /* --- Input -------------------------------------------------------- */
    function getMousePos(e) {
        var rect = canvas.getBoundingClientRect();
        var sx = canvas.width  / rect.width;
        var sy = canvas.height / rect.height;
        return { x: (e.clientX - rect.left) * sx, y: (e.clientY - rect.top) * sy };
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

    /* Keyboard listens on the wrapper (which is focusable via tabindex). */
    root.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            start = 1;
            e.preventDefault();
            return;
        }
        if (menue !== 1) return;
        if (start !== 1) return;
        if (win !== 0) return;
        var k = (e.key || '').toLowerCase();
        if (k === 'w') { if (y > 55)  { y -= 15; e.preventDefault(); } }
        if (k === 's') { if (y < 570) { y += 15; e.preventDefault(); } }
        if (k === 'a') { if (x > 0)   { x -= 15; e.preventDefault(); } }
        if (k === 'd') { if (x < 410) { x += 15; e.preventDefault(); } }
    });

    function handleClick() {
        if (menue === 0) {
            // Play button: 175 < x < 265, 320 < y < 360
            if (mouseX > 175) { if (mouseX < 265) { if (mouseY > 320) { if (mouseY < 360) { menue = 1; root.focus(); } } } }
            // Instructions button: 125 < x < 325, 420 < y < 460
            if (mouseX > 125) { if (mouseX < 325) { if (mouseY > 420) { if (mouseY < 460) { menue = 2; } } } }
        } else if (menue === 2) {
            // Back button: 175 < x < 265, 490 < y < 540
            if (mouseX > 175) { if (mouseX < 265) { if (mouseY > 490) { if (mouseY < 540) { menue = 0; } } } }
        } else if (win === 1 || win === 2) {
            // Restart button: 3 < x < 135, 546 < y < 577
            if (mouseX > 3) { if (mouseX < 135) { if (mouseY > 546) { if (mouseY < 577) { reset(); } } } }
        }
    }

    function reset() {
        menue = 0;
        win = 0;
        x = 57; y = 70;
        start = 0;
        timer = 0;
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

    /* --- Stages ------------------------------------------------------- */
    function openScreen() {
        timer = 0;
        clearBg();
        if (imgs.start.complete) ctx.drawImage(imgs.start, 0, 0, W, H);
        drawText('[Play]', 175, 320, 36, '#fff');
        drawText('[Instructions]', 125, 420, 36, '#fff');

        var hover = false;
        if (mouseX > 175) { if (mouseX < 265) { if (mouseY > 320) { if (mouseY < 360) { hover = true; } } } }
        if (mouseX > 125) { if (mouseX < 325) { if (mouseY > 420) { if (mouseY < 460) { hover = true; } } } }
        canvas.style.cursor = hover ? 'pointer' : 'default';
    }

    function instructionsScreen() {
        clearBg();
        if (imgs.instructions.complete) ctx.drawImage(imgs.instructions, 0, 0, W, H);
        drawText('[Back]', 175, 525, 36, '#fff');

        var hover = false;
        if (mouseX > 175) { if (mouseX < 265) { if (mouseY > 525) { if (mouseY < 565) { hover = true; } } } }
        canvas.style.cursor = hover ? 'pointer' : 'default';
    }

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
        if (start === 1) {
            if (timer < 10) timer += 0.013;
        }
        ctx.fillStyle = '#fff';
        ctx.fillRect(0, 0, 600, 50);
        ctx.font = '25px sans-serif';
        ctx.textBaseline = 'top';
        if (win === 0) {
            if (timer < 6) {
                ctx.fillStyle = '#000';
                ctx.fillText('Timer : ' + timer.toFixed(2), 109, 18);
            } else {
                var r = 100 + Math.floor(Math.random() * 156);
                ctx.fillStyle = 'rgb(' + r + ',0,0)';
                ctx.fillText('Timer : ' + timer.toFixed(2), 109, 18);
            }
        }

        if (start === 0) {
            ctx.fillStyle = 'rgba(0,0,0,0.65)';
            ctx.fillRect(0, 200, W, 200);
            drawText('Press Enter to start', 65, 280, 28, '#fff');
        }

        // Collision
        collide();
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
        if (timer > 10) win = 2;
    }

    function winScreen() {
        clearBg();
        if (imgs.winner.complete) ctx.drawImage(imgs.winner, 0, 0, W, H);
        drawText('[Restart]', 0, 575, 28, '#000');

        var hover = false;
        if (mouseX > 3) { if (mouseX < 135) { if (mouseY > 546) { if (mouseY < 577) { hover = true; } } } }
        canvas.style.cursor = hover ? 'pointer' : 'default';
    }

    function loseScreen() {
        clearBg();
        if (imgs.loser.complete) ctx.drawImage(imgs.loser, 0, 0, W, H);
        drawText('[Restart]', 0, 575, 28, '#000');

        var hover = false;
        if (mouseX > 3) { if (mouseX < 135) { if (mouseY > 546) { if (mouseY < 577) { hover = true; } } } }
        canvas.style.cursor = hover ? 'pointer' : 'default';
    }

    /* --- Main loop ---------------------------------------------------- */
    function frame() {
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
        requestAnimationFrame(frame);
    }
})();
</script>
<!-- /wp:html -->
