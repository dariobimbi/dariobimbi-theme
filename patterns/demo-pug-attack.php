<?php
/**
 * Title: Demo — Pug Attack
 * Slug: dariobimbi/demo-pug-attack
 * Categories: dariobimbi
 * Description: A small Processing sketch from a DePaul level design class, hand-ported to vanilla JavaScript and Canvas so it plays in the browser. Mouse to aim, click to fire, the goal is to hit the cat scrolling across the bottom.
 *
 * To use: parent Page "Demos" (slug `demos`), child Page "Pug Attack"
 * (slug `pug-attack`, parent = Demos), pick the "Demo — Pug Attack" template.
 * Final URL: /demos/pug-attack/.
 *
 * Image assets live at /wp-content/themes/dariobimbi/assets/games/pug-attack/.
 * The original .pde was a few hundred lines; this is roughly the same shape.
 */
$assets = esc_url( get_stylesheet_directory_uri() . '/assets/games/pug-attack/' );
?>
<!-- wp:group {"align":"full","className":"content-page demo-pug-attack","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull content-page demo-pug-attack" style="padding-top:var(--wp--preset--spacing--70);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--70);padding-left:var(--wp--preset--spacing--30)">

    <!-- wp:paragraph {"className":"eyebrow"} -->
    <p class="eyebrow">an old school game, ported</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"level":1,"className":"has-gradient-text","style":{"spacing":{"margin":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20"}}}} -->
    <h1 class="wp-block-heading has-gradient-text" style="margin-top:var(--wp--preset--spacing--20);margin-bottom:var(--wp--preset--spacing--20)">Pug Attack</h1>
    <!-- /wp:heading -->

    <!-- wp:paragraph {"textColor":"muted","style":{"typography":{"lineHeight":"1.55"},"spacing":{"margin":{"bottom":"var:preset|spacing|40"}}}} -->
    <p class="has-muted-color has-text-color" style="line-height:1.55;margin-bottom:var(--wp--preset--spacing--40)">A small Processing sketch I made for a level design class at DePaul, hand-ported to vanilla JavaScript and Canvas so it runs in the browser. The pug bounces along the top, you aim with the mouse, and a click fires a shot at the cat scrolling across the bottom. Same shape as the original .pde, just rewritten so it does not need a plugin to run.</p>
    <!-- /wp:paragraph -->

    <!-- wp:html -->
    <div class="pa" data-assets-base="<?php echo $assets; ?>" tabindex="-1">
        <div class="pa-stage">
            <canvas class="pa-canvas" data-role="canvas" width="800" height="550" aria-label="Pug Attack game canvas"></canvas>
        </div>
        <div class="pa-bar">
            <button type="button" class="pa-btn" data-role="menu" hidden>Menu</button>
        </div>
        <p class="pa-hint">Click to fire. Hit the cat.</p>
    </div>
    <!-- /wp:html -->

</div>
<!-- /wp:group -->

<!-- wp:html -->
<style>
.demo-pug-attack .pa {
    margin-top: 1rem;
    background: var(--wp--preset--color--surface);
    border: 1px solid var(--wp--preset--color--subtle);
    border-radius: 16px;
    padding: 1.5rem;
}
.demo-pug-attack .pa-stage {
    background: #14110d;
    border: 1px solid var(--wp--preset--color--subtle);
    border-radius: 12px;
    overflow: hidden;
    aspect-ratio: 800 / 550;
    max-width: 800px;
    margin: 0 auto;
}
.demo-pug-attack .pa-canvas {
    display: block;
    width: 100%;
    height: 100%;
    cursor: crosshair;
}
.demo-pug-attack .pa:focus {
    outline: none;
}
.demo-pug-attack .pa-bar {
    display: flex;
    justify-content: center;
    margin-top: 1rem;
}
.demo-pug-attack .pa-btn[hidden] {
    display: none;
}
.demo-pug-attack .pa-btn {
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
.demo-pug-attack .pa-btn:focus-visible {
    outline: 3px solid var(--wp--preset--color--primary);
    outline-offset: 2px;
}
.demo-pug-attack .pa-hint {
    margin: 1rem 0 0;
    text-align: center;
    font-family: var(--wp--preset--font-family--mono);
    font-size: 0.85rem;
    color: var(--wp--preset--color--muted);
    letter-spacing: 0.05em;
}
</style>

<script>
/* Pug Attack — vanilla JS / Canvas port of the original Processing sketch.
   Original: pug_attack.pde (~150 lines). The cannon math, bullet pool, and
   stage state machine are kept intentionally close to the original. */
(function () {
    var root = document.querySelector('.demo-pug-attack .pa');
    if (!root) return;

    var canvas = root.querySelector('[data-role="canvas"]');
    var ctx    = canvas.getContext('2d');
    var assetsBase = root.dataset.assetsBase || '';
    var menuBtn = root.querySelector('[data-role="menu"]');

    var W = 800, H = 550;

    /* --- Game state ---------------------------------------------------- */
    var stage = 0;        // 0 = open, 1 = instructions, 2 = play
    var pugX = 0, pugY = 0;
    var direction = 0;    // 0 = right, 1 = left
    var catX = 820, catY = 450;
    var rot = 0;
    var score = 0;

    /* The cannon sits at a fixed spot on the pug sprite; shots leave its tip. */
    var CANNON_DX = 90, CANNON_DY = 160, CANNON_LEN = 40;
    var BULLET_SPEED = 10;   // px per 60 Hz step

    /* Bullet pool: spawn at the cannon tip, move, deactivate off screen. */
    var bullCount = 100;
    var bulls    = [];
    var fire     = true;
    var timer    = 0;

    for (var i = 0; i < bullCount; i++) {
        bulls.push({ x: 0, y: 0, vx: 0, vy: 0, active: false });
    }

    /* One rect per canvas button, shared by drawing, hover and click. */
    var BTN = {
        play:  { x: 460, y: 320, w: 110, h: 40 },
        instr: { x: 125, y: 420, w: 200, h: 40 },
        back:  { x: 175, y: 525, w: 90,  h: 40 }
    };

    /* --- Mouse tracking (canvas-coord aware, scales with displayed size) */
    var mouseX = 0, mouseY = 0;
    var mouseDown = false;

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

    canvas.addEventListener('mousemove', function (e) {
        var p = getMousePos(e);
        mouseX = p.x; mouseY = p.y;
    });
    canvas.addEventListener('mousedown', function (e) {
        var p = getMousePos(e);
        mouseX = p.x; mouseY = p.y;
        mouseDown = true;
        handleClick();
    });
    canvas.addEventListener('mouseup', function () {
        mouseDown = false;
        fire = true;
    });
    canvas.addEventListener('mouseleave', function () {
        mouseDown = false;
    });
    /* Touch fallback so mobile can play. */
    canvas.addEventListener('touchstart', function (e) {
        if (e.touches.length === 0) return;
        var t = e.touches[0];
        var p = getMousePos({ clientX: t.clientX, clientY: t.clientY });
        mouseX = p.x; mouseY = p.y;
        mouseDown = true;
        handleClick();
        e.preventDefault();
    }, { passive: false });
    canvas.addEventListener('touchend', function () {
        mouseDown = false;
        fire = true;
    });

    function handleClick() {
        if (stage === 0) {
            if (inRect(BTN.play))  { stage = 2; mouseDown = false; }   // the Play click is not a shot
            else if (inRect(BTN.instr)) { stage = 1; }
        } else if (stage === 1) {
            if (inRect(BTN.back)) { stage = 0; }
        }
    }

    /* Back to the menu with a clean slate. */
    function backToMenu() {
        stage = 0;
        score = 0;
        pugX = 0;
        direction = 0;
        catX = 820;
        fire = true;
        timer = 0;
        mouseDown = false;
        for (var s = 0; s < bullCount; s++) { bulls[s].active = false; }
        root.focus({ preventScroll: true });
    }

    menuBtn.addEventListener('click', backToMenu);

    /* --- Asset loading ------------------------------------------------- */
    var imgs = {};
    var imgFiles = {
        pugLeft:  'pug-left.png',
        pugRight: 'pug-right.png',
        castle:   'castle.jpg',
        cat:      'cat.gif',
        start:    'start.jpg'
    };
    var loaded = 0, total = 0;
    Object.keys(imgFiles).forEach(function (k) { total++; });
    Object.keys(imgFiles).forEach(function (k) {
        var img = new Image();
        img.onload = function () {
            loaded++;
            if (loaded === total) requestAnimationFrame(frame);
        };
        img.onerror = function () { loaded++; };
        img.src = assetsBase + imgFiles[k];
        imgs[k] = img;
    });

    /* --- Helpers ------------------------------------------------------- */
    function clearBg() {
        ctx.fillStyle = '#000';
        ctx.fillRect(0, 0, W, H);
    }

    function drawText(text, x, y, size, color) {
        ctx.fillStyle = color || '#fff';
        ctx.font = (size || 36) + 'px sans-serif';
        ctx.textBaseline = 'top';
        ctx.fillText(text, x, y);
    }

    function drawButton(r, label, size, color) {
        drawText(label, r.x, r.y, size, color);
    }

    /* --- Stages -------------------------------------------------------- */
    function openScreen() {
        timer = 0;
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
        drawText('How to play', 50, 60, 32, '#fff');
        drawText('The pug paces back and forth along the top.', 50, 130, 22, '#cbd5e1');
        drawText('Move the mouse to aim the cannon at the cat.', 50, 165, 22, '#cbd5e1');
        drawText('Click and hold to fire shots from the pug.', 50, 200, 22, '#cbd5e1');
        drawText('Hit the cat scrolling across the bottom.', 50, 235, 22, '#cbd5e1');
        drawButton(BTN.back, '[Back]', 36, '#fff');

        canvas.style.cursor = inRect(BTN.back) ? 'pointer' : 'default';
    }

    function playScreen() {
        if (imgs.castle.complete) ctx.drawImage(imgs.castle, 0, 0, W, H);

        drawBullets();
        drawPug();
        drawCat();

        // Score readout
        ctx.fillStyle = '#fff';
        ctx.font = '20px ui-monospace, Menlo, Consolas, monospace';
        ctx.textBaseline = 'top';
        ctx.fillText('Hits: ' + score, 700, 14);

        canvas.style.cursor = 'crosshair';
    }

    function drawPug() {
        var img = (direction === 0) ? imgs.pugRight : imgs.pugLeft;
        if (img.complete) ctx.drawImage(img, pugX, pugY);

        // Cannon: rectangle + ellipse, rotated to point at mouse
        ctx.save();
        ctx.translate(pugX + CANNON_DX, pugY + CANNON_DY);
        ctx.rotate(rot + Math.PI / 2);
        ctx.fillStyle = '#fff';
        ctx.fillRect(-10, -CANNON_LEN, 20, CANNON_LEN);
        ctx.beginPath();
        ctx.arc(0, 0, 10, 0, Math.PI * 2);
        ctx.fill();
        ctx.restore();
    }

    function drawBullets() {
        ctx.fillStyle = '#fff';
        for (var s = 0; s < bullCount; s++) {
            if (bulls[s].active) {
                ctx.beginPath();
                ctx.arc(bulls[s].x, bulls[s].y, 10, 0, Math.PI * 2);
                ctx.fill();
            }
        }
    }

    function drawCat() {
        if (imgs.cat.complete) ctx.drawImage(imgs.cat, catX, catY);
    }

    /* --- Fixed 60 Hz update -------------------------------------------- */
    /* Pug, cat, bullets and the fire cooldown all advance in fixed steps, so
       the game plays the same on a 60 Hz and a 144 Hz screen. */
    function spawnBullet() {
        for (var s = 0; s < bullCount; s++) {
            if (!bulls[s].active) {
                bulls[s].active = true;
                bulls[s].x  = pugX + CANNON_DX + Math.cos(rot) * CANNON_LEN;
                bulls[s].y  = pugY + CANNON_DY + Math.sin(rot) * CANNON_LEN;
                bulls[s].vx = BULLET_SPEED * Math.cos(rot);
                bulls[s].vy = BULLET_SPEED * Math.sin(rot);
                return;
            }
        }
    }

    function updateBullets() {
        for (var s = 0; s < bullCount; s++) {
            var b = bulls[s];
            if (!b.active) continue;

            b.x += b.vx;
            b.y += b.vy;

            // Deactivate offscreen
            var off = false;
            if (b.x > W + 10) off = true;
            if (b.x < -10)    off = true;
            if (b.y < -10)    off = true;
            if (b.y > H + 10) off = true;
            if (off) {
                b.active = false;
                continue;
            }

            // Collide with cat (nested ifs avoid the WP wptexturize encoding hazard, see CLAUDE.md)
            if (b.x > catX) {
                if (b.x < catX + 100) {
                    if (b.y > 475) {
                        if (b.y < 550) {
                            catX = 875;
                            b.active = false;
                            score++;
                        }
                    }
                }
            }
        }
    }

    function step() {
        // Cannon angle from the cannon pivot toward the mouse
        rot = Math.atan2(mouseY - (pugY + CANNON_DY), mouseX - (pugX + CANNON_DX));

        // Fire cooldown (15 steps)
        if (!fire) timer++;
        else timer = 0;
        if (timer >= 15) {
            fire = true;
            timer = 0;
        }

        // Spawn a new bullet on click + cooldown ready
        if (mouseDown) {
            if (fire) {
                spawnBullet();
                fire = false;
            }
        }

        updateBullets();

        // Pug paces along the top
        if (direction === 0) {
            pugX += 1;
            if (pugX > 600) direction = 1;
        } else {
            pugX -= 1;
            if (pugX < 0) direction = 0;
        }

        // Cat scrolls across the bottom
        catX -= 2;
        if (catX < -100) catX = 850;
    }

    /* --- Main loop ----------------------------------------------------- */
    var STEP_MS = 1000 / 60;
    var lastTs = 0;
    var acc = 0;
    var lastStage = -1;

    function frame(ts) {
        if (lastTs) {
            var dt = ts - lastTs;
            if (dt > 250) dt = 250;   // tab was hidden, do not fast-forward
            acc += dt;
        }
        lastTs = ts;
        while (acc >= STEP_MS - 0.5) {
            if (stage === 2) step();
            acc -= STEP_MS;
        }

        if (stage === 0) openScreen();
        else if (stage === 1) instructionsScreen();
        else if (stage === 2) playScreen();

        if (stage !== lastStage) {
            lastStage = stage;
            menuBtn.hidden = (stage !== 2);
        }
        requestAnimationFrame(frame);
    }
})();
</script>
<!-- /wp:html -->
