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
    <div class="pa" data-assets-base="<?php echo $assets; ?>">
        <div class="pa-stage">
            <canvas class="pa-canvas" data-role="canvas" width="800" height="550" aria-label="Pug Attack game canvas"></canvas>
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
    background: #050813;
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

    var W = 800, H = 550;

    /* --- Game state ---------------------------------------------------- */
    var stage = 0;        // 0 = open, 1 = instructions, 2 = play
    var pugX = 0, pugY = 0;
    var direction = 0;    // 0 = right, 1 = left
    var catX = 820, catY = 450;
    var rot = 0;
    var score = 0;

    var bullCount = 100;
    var bulls    = [];
    var speedx   = [];
    var speedy   = [];
    var fire     = true;
    var timer    = 0;
    var currshot = 1;
    var Pos      = { x: 250, y: 600 };

    for (var i = 0; i < bullCount; i++) {
        bulls.push({ x: 0, y: 600 });
        speedx.push(10 * Math.cos(rot));
        speedy.push(10 * Math.sin(rot));
    }

    /* --- Mouse tracking (canvas-coord aware, scales with displayed size) */
    var mouseX = 0, mouseY = 0;
    var mouseDown = false;

    function getMousePos(e) {
        var rect = canvas.getBoundingClientRect();
        var sx = canvas.width  / rect.width;
        var sy = canvas.height / rect.height;
        return { x: (e.clientX - rect.left) * sx, y: (e.clientY - rect.top) * sy };
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
            // Play button hitbox
            if (mouseX > 470) { if (mouseX < 530) { if (mouseY > 320) { if (mouseY < 350) { stage = 2; } } } }
            // Instructions button hitbox
            if (mouseX > 125) { if (mouseX < 325) { if (mouseY > 420) { if (mouseY < 450) { stage = 1; } } } }
        } else if (stage === 1) {
            // Back button hitbox
            if (mouseX > 175) { if (mouseX < 265) { if (mouseY > 490) { if (mouseY < 540) { stage = 0; } } } }
        }
    }

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
    function dist(x1, y1, x2, y2) {
        var dx = x2 - x1, dy = y2 - y1;
        return Math.sqrt(dx * dx + dy * dy);
    }

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

    /* --- Stages -------------------------------------------------------- */
    function openScreen() {
        timer = 0;
        clearBg();
        if (imgs.start.complete) ctx.drawImage(imgs.start, 0, 0, W, H);
        drawText('[Play]', 460, 320, 36, '#fff');
        drawText('[Instructions]', 125, 420, 36, '#fff');

        var hover = false;
        if (mouseX > 460) { if (mouseX < 570) { if (mouseY > 320) { if (mouseY < 360) { hover = true; } } } }
        if (mouseX > 125) { if (mouseX < 325) { if (mouseY > 420) { if (mouseY < 460) { hover = true; } } } }
        canvas.style.cursor = hover ? 'pointer' : 'default';
    }

    function instructionsScreen() {
        clearBg();
        drawText('How to play', 50, 60, 32, '#fff');
        drawText('The pug paces back and forth along the top.', 50, 130, 22, '#cbd5e1');
        drawText('Move the mouse to aim the cannon at the cat.', 50, 165, 22, '#cbd5e1');
        drawText('Click and hold to fire shots from the pug.', 50, 200, 22, '#cbd5e1');
        drawText('Hit the cat scrolling across the bottom.', 50, 235, 22, '#cbd5e1');
        drawText('[Back]', 175, 525, 36, '#fff');

        var hover = false;
        if (mouseX > 175) { if (mouseX < 265) { if (mouseY > 525) { if (mouseY < 560) { hover = true; } } } }
        canvas.style.cursor = hover ? 'pointer' : 'default';
    }

    function playScreen() {
        if (imgs.castle.complete) ctx.drawImage(imgs.castle, 0, 0, W, H);

        // Cannon angle from pug toward mouse
        rot = Math.atan2(-(pugY - mouseY), -(pugX - mouseX));

        // Fire cooldown
        if (!fire) timer++;
        else timer = 0;
        if (timer >= 15) {
            fire = true;
            timer = 0;
        }

        // Bullets (draw + update + collide)
        updateBullets();

        // Pug + cannon
        drawPug();

        // Cat
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

        if (direction === 0) {
            pugX += 1;
            if (pugX > 600) direction = 1;
        } else {
            pugX -= 1;
            if (pugX < 0) direction = 0;
        }

        // Cannon: rectangle + ellipse, rotated to point at mouse
        ctx.save();
        ctx.translate(pugX + 90, pugY + 160);
        ctx.rotate(rot + Math.PI / 2);
        ctx.fillStyle = '#fff';
        ctx.fillRect(-10, -40, 20, 40);
        ctx.beginPath();
        ctx.arc(0, 0, 10, 0, Math.PI * 2);
        ctx.fill();
        ctx.restore();
    }

    function updateBullets() {
        // Spawn a new bullet on click + cooldown ready
        if (mouseDown) {
            if (fire) {
                bulls[currshot].x += speedx[currshot] + pugX - 160;
                bulls[currshot].y += speedy[currshot] - 450;
                fire = false;
                currshot += 1;
            }
        }

        for (var s = 0; s < bullCount; s++) {
            if (currshot >= bullCount) currshot = 1;

            var d = dist(bulls[s].x, bulls[s].y, Pos.x, Pos.y);
            if (d === 0) {
                speedx[s] = 10 * Math.cos(rot);
                speedy[s] = 10 * Math.sin(rot);
            }
            if (d > 0) {
                bulls[s].x += speedx[s];
                bulls[s].y += speedy[s];
            }

            // Reset offscreen
            var off = false;
            if (bulls[s].x > 800)  off = true;
            if (bulls[s].x < -800) off = true;
            if (bulls[s].y < -550) off = true;
            if (off) {
                bulls[s].x = 800;
                bulls[s].y = -800;
                speedx[s] = 0;
                speedy[s] = 0;
            }

            // Draw
            ctx.fillStyle = '#fff';
            ctx.beginPath();
            ctx.arc(bulls[s].x, bulls[s].y, 10, 0, Math.PI * 2);
            ctx.fill();

            // Collide with cat (nested ifs avoid the WP wptexturize encoding hazard, see CLAUDE.md)
            if (bulls[s].x > catX) {
                if (bulls[s].x < catX + 100) {
                    if (bulls[s].y > 475) {
                        if (bulls[s].y < 550) {
                            catX = 875;
                            bulls[s].x = 800;
                            bulls[s].y = -800;
                            speedx[s] = 0;
                            speedy[s] = 0;
                            score++;
                        }
                    }
                }
            }
        }
    }

    function drawCat() {
        if (imgs.cat.complete) ctx.drawImage(imgs.cat, catX, catY);
        catX -= 2;
        if (catX < -100) catX = 850;
    }

    /* --- Main loop ----------------------------------------------------- */
    function frame() {
        if (stage === 0) openScreen();
        else if (stage === 1) instructionsScreen();
        else if (stage === 2) playScreen();
        requestAnimationFrame(frame);
    }
})();
</script>
<!-- /wp:html -->
