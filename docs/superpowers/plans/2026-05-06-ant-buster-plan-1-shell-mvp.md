# Ant Buster — Plan 1: Game Shell + MVP Loop

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a trivially-playable Ant Buster MVP that proves the whole pipeline (pattern integration, canvas rendering, asset generation, game loop, input, gameplay loop). One cannon type, one ant type, one hardcoded wave, win/lose states. No real art, no full roster — just enough to verify the architecture before scaling content in Plan 2.

**Architecture:** Single-file pattern PHP (`patterns/demo-ant-buster.php`) holding markup + inline `<style>` + inline `<script>`. Thin template `templates/page-demo-ant-buster.html` invokes the pattern. Custom template registered in `theme.json` so the WordPress page editor offers it. The game runs as a single IIFE that owns a `game` state object, a fixed-timestep update loop, and a render pass per frame. Placeholder sprites generated procedurally at load time.

**Tech Stack:** Vanilla JavaScript, HTML5 Canvas 2D, WordPress block patterns. No build step, no npm, no jQuery.

**Reference spec:** [`docs/superpowers/specs/2026-05-06-ant-buster-design.md`](../specs/2026-05-06-ant-buster-design.md)

**Pre-flight checklist (before Task 1):**
- WAMP is running (Apache + MySQL).
- Working tree is clean on `main`.
- You can reach `http://localhost/dariobimbi.com/wp-admin/` and log in.
- The `demos` parent Page already exists in WP admin (it does — used by other demos).

**Working directory:** All file paths are relative to the theme root: `c:\wamp64\www\dariobimbi.com\wp-content\themes\dariobimbi\`.

---

## Conventions used in this plan

- **Inline JS rule:** never write `&&` in inline-script code. WordPress's content filter encodes it to `&#038;&#038;` inconsistently, which becomes a runtime `SyntaxError`. Use nested `if` statements, helper booleans, or `Array.indexOf` instead. Same caution applies to `||`, `<`, `>` when they could be parsed as HTML.
- **Section markers:** the JavaScript inside `demo-ant-buster.php` is organized into clearly labeled sections. Later tasks add code to the section that owns the responsibility.
- **Manual verification:** each task ends with a "Verify" step describing exactly what to check in the browser. Open `http://localhost/dariobimbi.com/demos/ant-buster/` in a fresh tab (cache disabled in DevTools) for each verify.
- **Commit cadence:** every task ends with a single commit. Frequent commits make it easy to bisect if something breaks.

---

## Task 1: Pattern + template scaffold + theme registration

**Files:**
- Create: `patterns/demo-ant-buster.php`
- Create: `templates/page-demo-ant-buster.html`
- Modify: `theme.json` (add to `customTemplates`)
- Modify: `style.css` (bump `Version:`)

- [ ] **Step 1: Read the existing demo pattern to match conventions**

Open `patterns/demo-pokedex.php` and skim the top 50 lines and the section structure. Note the pattern header comment format and the page-pattern shell convention.

- [ ] **Step 2: Create the pattern file with the page-pattern shell**

Create `patterns/demo-ant-buster.php`:

```php
<?php
/**
 * Title: Ant Buster Demo
 * Slug: dariobimbi/demo-ant-buster
 * Categories: dariobimbi
 * Inserter: no
 */
?>
<!-- wp:group {"align":"full","className":"content-page demo-ant-buster","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull content-page demo-ant-buster" style="padding-top:var(--wp--preset--spacing--70);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--70);padding-left:var(--wp--preset--spacing--30)">

  <!-- wp:paragraph {"className":"eyebrow"} -->
  <p class="eyebrow">demo</p>
  <!-- /wp:paragraph -->

  <!-- wp:heading {"level":1,"className":"has-gradient-text"} -->
  <h1 class="wp-block-heading has-gradient-text">Ant Buster</h1>
  <!-- /wp:heading -->

  <!-- wp:paragraph -->
  <p>Defend the cake from the ants. Drag a cannon onto the picnic blanket and try to keep the swarm from reaching the cake.</p>
  <!-- /wp:paragraph -->

  <!-- wp:html -->
  <div class="ab-stage">
    <canvas id="ab-canvas" data-role="canvas" aria-label="Ant Buster game canvas" width="480" height="270"></canvas>
  </div>
  <!-- /wp:html -->

</div>
<!-- /wp:group -->

<style>
/* Inline styles added in Task 2 */
</style>

<script>
/* Inline script added in Task 3 */
</script>
```

- [ ] **Step 3: Create the thin template that invokes the pattern**

Create `templates/page-demo-ant-buster.html`:

```html
<!-- wp:template-part {"slug":"header","tagName":"header"} /-->

<!-- wp:group {"tagName":"main","layout":{"type":"constrained"}} -->
<main class="wp-block-group">
  <!-- wp:pattern {"slug":"dariobimbi/demo-ant-buster"} /-->
</main>
<!-- /wp:group -->

<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->
```

- [ ] **Step 4: Register the template in `theme.json`**

Open `theme.json` and find the `customTemplates` array. The existing entries are organized in two groups: `page-demo-*` (demos) followed by `page-projects` and `page-project-*` (projects). Add this entry to the demos group, immediately after the existing `page-demo-card-browser` entry and before the first `page-projects` entry:

```json
{
  "name": "page-demo-ant-buster",
  "title": "Demo — Ant Buster",
  "postTypes": ["page"]
}
```

The `—` is an em-dash (U+2014). Every other demo entry in this file uses this exact `Demo — <Name>` format. Pay attention to commas: the surrounding entries need correct trailing commas after your insert.

- [ ] **Step 5: Bump the theme version in `style.css`**

Open `style.css`. The header has a `Version:` line near the top. Bump the patch number (e.g. `1.4.7` → `1.4.8`). This invalidates the WordPress pattern cache so `demo-ant-buster.php` becomes available, and re-fires the auto-pages reconciliation in `inc/auto-pages.php` so a Page is auto-created for the new template.

- [ ] **Step 6: Trigger auto-pages reconciliation by visiting wp-admin**

Open `http://localhost/dariobimbi.com/wp-admin/` in your browser. The reconciliation in `inc/auto-pages.php` runs on admin page load when the cached version doesn't match the current theme version. It walks `theme.json` `customTemplates` and creates a Page for each demo template that doesn't yet have one.

- [ ] **Step 7: Verify the page exists and resolves**

In the browser, visit `http://localhost/dariobimbi.com/demos/ant-buster/`. You should see:
- The site header.
- An "demo" eyebrow paragraph.
- An "Ant Buster" h1 with gradient text styling.
- The intro paragraph.
- A blank canvas area (no styling yet, so it'll be a small unstyled box).
- The site footer.

If the page returns 404: the auto-pages reconciliation didn't fire. Re-check that the version was bumped in `style.css` and that the `customTemplates` entry is well-formed JSON. Then revisit `wp-admin`.

If the page shows but the canvas isn't there: the pattern slug is mismatched between the pattern file's header (`Slug: dariobimbi/demo-ant-buster`) and the template's `wp:pattern` invocation. They must match exactly.

- [ ] **Step 8: Commit**

```bash
git add patterns/demo-ant-buster.php templates/page-demo-ant-buster.html theme.json style.css
git commit -m "feat(ant-buster): add pattern + template scaffold for demo page"
```

---

## Task 2: Canvas DOM, full-bleed background, pixelated rendering

**Files:**
- Modify: `patterns/demo-ant-buster.php` (the `<style>` block)

- [ ] **Step 1: Replace the placeholder `<style>` block with the demo's scoped styles**

Open `patterns/demo-ant-buster.php` and replace the `<style>` block with:

```php
<style>
.demo-ant-buster {
  --ab-bg: #1a1c2c;
  --ab-frame: #2a2f3e;
}

body.has-ant-buster-page {
  background-color: var(--wp--preset--color--background) !important;
}

.demo-ant-buster .ab-stage {
  display: flex;
  justify-content: center;
  align-items: center;
  margin: var(--wp--preset--spacing--40) auto 0;
  padding: var(--wp--preset--spacing--20);
  background: var(--ab-frame);
  border-radius: 12px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
  max-width: 100%;
}

.demo-ant-buster #ab-canvas {
  width: 960px;
  max-width: 100%;
  height: auto;
  aspect-ratio: 16 / 9;
  background: var(--ab-bg);
  image-rendering: pixelated;
  image-rendering: -moz-crisp-edges;
  image-rendering: crisp-edges;
  cursor: default;
  display: block;
}

.demo-ant-buster #ab-canvas.ab-dragging {
  cursor: grabbing;
}

@media (max-width: 1000px) {
  .demo-ant-buster #ab-canvas {
    width: 100%;
  }
}
</style>
```

- [ ] **Step 2: Verify the canvas renders at the right size**

Reload `/demos/ant-buster/`. You should see:
- A dark frame around a 960×540 (on desktop) dark canvas.
- On a viewport narrower than 1000px, the canvas fills available width while keeping its 16:9 ratio.
- Inspecting the canvas in DevTools: `width="480"` and `height="270"` HTML attributes (native pixel size), but CSS-rendered larger (scaled).

- [ ] **Step 3: Commit**

```bash
git add patterns/demo-ant-buster.php
git commit -m "feat(ant-buster): style the canvas stage with pixelated rendering"
```

---

## Task 3: Game IIFE skeleton, state object, and main loop

**Files:**
- Modify: `patterns/demo-ant-buster.php` (the `<script>` block)

- [ ] **Step 1: Replace the placeholder `<script>` block with the IIFE skeleton**

Replace the `<script>` block in `patterns/demo-ant-buster.php` with:

```php
<script>
(function () {
  'use strict';

  // === BODY CLASS (full-bleed paint workaround) ===
  document.body.classList.add('has-ant-buster-page');

  // === CONSTANTS ===
  var WIDTH = 480;
  var HEIGHT = 270;
  var PLAYFIELD_W = 320;
  var PLAYFIELD_H = 246;
  var HUD_H = 24;
  var SHOP_W = 160;
  var TARGET_FPS = 60;
  var FIXED_DT = 1 / TARGET_FPS;

  // === GAME STATE ===
  var game = {
    state: 'playing',         // 'playing' | 'won' | 'lost'
    time: 0,                  // seconds since game start
    accumulator: 0,           // for fixed-timestep
    cake: { x: 200, y: 135, slices: 8 },
    anthill: { x: 16, y: 135 },
    ants: [],
    cannons: [],
    projectiles: [],
    sprites: {},              // populated in Task 4
    gold: 150,
    wave: 1
  };

  // === CANVAS SETUP ===
  var canvas = document.getElementById('ab-canvas');
  if (!canvas) { return; }
  var ctx = canvas.getContext('2d');
  ctx.imageSmoothingEnabled = false;

  // === SPRITES === (Task 4)

  // === RENDERING === (Task 5)
  function render() {
    ctx.fillStyle = '#1a1c2c';
    ctx.fillRect(0, 0, WIDTH, HEIGHT);
  }

  // === UPDATE === (Task 6+)
  function update(dt) {
    game.time += dt;
  }

  // === MAIN LOOP ===
  var lastTime = performance.now();
  function frame(now) {
    var elapsed = (now - lastTime) / 1000;
    lastTime = now;
    if (elapsed > 0.25) { elapsed = 0.25; }   // clamp to avoid spiral-of-death
    game.accumulator += elapsed;
    while (game.accumulator >= FIXED_DT) {
      update(FIXED_DT);
      game.accumulator -= FIXED_DT;
    }
    render();
    requestAnimationFrame(frame);
  }
  requestAnimationFrame(frame);

  // Expose game for debugging from devtools console
  window.__ab = game;
})();
</script>
```

- [ ] **Step 2: Verify the loop runs**

Reload `/demos/ant-buster/`. You should see a flat dark canvas (no errors in console). Open DevTools console and type `__ab.time` — it should be a growing number (seconds since load).

- [ ] **Step 3: Verify the body class is applied**

In DevTools, inspect `<body>`. It should have `has-ant-buster-page` in its class list. Inspect the page background — it should not show any thin strips of the default page background around the page-pattern's full-width sections.

- [ ] **Step 4: Commit**

```bash
git add patterns/demo-ant-buster.php
git commit -m "feat(ant-buster): scaffold IIFE, game state, fixed-timestep main loop"
```

---

## Task 4: Procedural placeholder sprites

**Files:**
- Modify: `patterns/demo-ant-buster.php` (the `=== SPRITES ===` section)

- [ ] **Step 1: Add placeholder sprite generators**

Find the `// === SPRITES === (Task 4)` marker and replace it with:

```javascript
  // === SPRITES ===
  // All sprites are generated procedurally as offscreen canvases.
  // Plan 4 will swap these out for real PNGs loaded from disk.
  function makeCanvas(w, h) {
    var c = document.createElement('canvas');
    c.width = w;
    c.height = h;
    return c;
  }

  function makeWorkerSprite() {
    var c = makeCanvas(16, 16);
    var x = c.getContext('2d');
    x.fillStyle = '#3a4466';
    x.beginPath();
    x.ellipse(8, 8, 5, 4, 0, 0, Math.PI * 2);   // body
    x.fill();
    x.fillStyle = '#262b44';
    x.fillRect(7, 3, 2, 2);                     // head
    x.fillStyle = '#181425';
    x.fillRect(7, 1, 1, 2);                     // antenna L
    x.fillRect(8, 1, 1, 2);                     // antenna R
    return c;
  }

  function makeAntSplat() {
    var c = makeCanvas(16, 16);
    var x = c.getContext('2d');
    x.fillStyle = '#262b44';
    x.fillRect(4, 7, 8, 3);
    x.fillRect(5, 6, 6, 1);
    x.fillRect(5, 10, 6, 1);
    return c;
  }

  function makeCannonBase() {
    var c = makeCanvas(32, 32);
    var x = c.getContext('2d');
    x.fillStyle = '#5a6988';
    x.beginPath();
    x.arc(16, 16, 12, 0, Math.PI * 2);
    x.fill();
    x.fillStyle = '#8b9bb4';
    x.beginPath();
    x.arc(16, 16, 10, 0, Math.PI * 2);
    x.fill();
    return c;
  }

  function makeCannonTurret() {
    var c = makeCanvas(20, 20);
    var x = c.getContext('2d');
    x.fillStyle = '#262b44';
    x.fillRect(8, 4, 4, 12);                    // barrel pointing up (rotated at draw time)
    x.fillStyle = '#181425';
    x.fillRect(7, 2, 6, 3);                     // barrel tip
    return c;
  }

  function makeCake(slices) {
    var c = makeCanvas(32, 32);
    var x = c.getContext('2d');
    var maxSlices = 8;
    var fillRatio = slices / maxSlices;
    if (fillRatio > 0) {
      x.fillStyle = '#feae34';
      x.fillRect(8, 12 + (1 - fillRatio) * 12, 16, fillRatio * 12);
      x.fillStyle = '#fee761';
      x.fillRect(10, 8 + (1 - fillRatio) * 12, 12, fillRatio * 6);
      x.fillStyle = '#e43b44';
      x.fillRect(15, 4, 2, 6);                  // candle
      x.fillStyle = '#fee761';
      x.fillRect(14, 2, 4, 3);                  // flame
    }
    x.fillStyle = '#ffffff';
    x.fillRect(6, 24, 20, 3);                   // plate
    return c;
  }

  function makeAnthill() {
    var c = makeCanvas(32, 32);
    var x = c.getContext('2d');
    x.fillStyle = '#5a432f';
    x.beginPath();
    x.ellipse(16, 24, 14, 6, 0, 0, Math.PI * 2);
    x.fill();
    x.fillStyle = '#7a5a3a';
    x.beginPath();
    x.ellipse(16, 22, 11, 5, 0, 0, Math.PI * 2);
    x.fill();
    x.fillStyle = '#181425';
    x.beginPath();
    x.ellipse(16, 22, 4, 2, 0, 0, Math.PI * 2);
    x.fill();
    return c;
  }

  function makeProjectile() {
    var c = makeCanvas(4, 4);
    var x = c.getContext('2d');
    x.fillStyle = '#181425';
    x.fillRect(0, 0, 4, 4);
    return c;
  }

  game.sprites = {
    worker: makeWorkerSprite(),
    splat: makeAntSplat(),
    cannonBase: makeCannonBase(),
    cannonTurret: makeCannonTurret(),
    anthill: makeAnthill(),
    projectile: makeProjectile()
  };
  // Note: cake sprite is regenerated in render() per frame because it depends on slice count.
```

- [ ] **Step 2: Verify the sprites generate without errors**

Reload `/demos/ant-buster/`. Console should be clean. In DevTools, type `__ab.sprites` and inspect — you should see an object with `worker`, `splat`, `cannonBase`, etc., each one a `<canvas>` element. Click any of them in the inspector to see the rendered sprite.

- [ ] **Step 3: Commit**

```bash
git add patterns/demo-ant-buster.php
git commit -m "feat(ant-buster): generate procedural placeholder sprites"
```

---

## Task 5: Render the playfield (blanket, grass, cake, anthill)

**Files:**
- Modify: `patterns/demo-ant-buster.php` (the `render()` function)

- [ ] **Step 1: Replace the minimal `render()` with the playfield render**

Find the `function render() { ... }` body inside `// === RENDERING ===` and replace it with:

```javascript
  function render() {
    // Background
    ctx.fillStyle = '#193c3e';
    ctx.fillRect(0, 0, WIDTH, HEIGHT);

    // Grass border (whole canvas is grass-colored already; blanket sits on top)
    var blanketX = 32;
    var blanketY = 32;
    var blanketW = 256;
    var blanketH = 200;

    // Picnic blanket — alternating red/white check
    var checkSize = 16;
    for (var by = 0; by < blanketH; by += checkSize) {
      for (var bx = 0; bx < blanketW; bx += checkSize) {
        var isRed = ((bx / checkSize) + (by / checkSize)) % 2 === 0;
        ctx.fillStyle = isRed ? '#e43b44' : '#feeed8';
        ctx.fillRect(blanketX + bx, blanketY + by, checkSize, checkSize);
      }
    }

    // Anthill
    ctx.drawImage(game.sprites.anthill, game.anthill.x - 16, game.anthill.y - 16);

    // Cake (regenerated each frame since it depends on slice count)
    var cakeSprite = makeCake(game.cake.slices);
    ctx.drawImage(cakeSprite, game.cake.x - 16, game.cake.y - 16);

    // HUD bar (top, dimmed)
    ctx.fillStyle = 'rgba(0, 0, 0, 0.4)';
    ctx.fillRect(0, 0, PLAYFIELD_W, HUD_H);

    // Shop panel (right)
    ctx.fillStyle = '#1a1c2c';
    ctx.fillRect(PLAYFIELD_W, 0, SHOP_W, HEIGHT);
  }
```

- [ ] **Step 2: Verify the playfield renders**

Reload. You should see:
- A green grass background.
- A red-and-white-checkered blanket (256×200) inset from the canvas edges.
- A brown anthill on the left edge of the blanket area.
- An orange/yellow cake with a candle in the middle.
- A semi-transparent dark strip at the top (HUD area).
- A solid dark area on the right (shop area).

- [ ] **Step 3: Commit**

```bash
git add patterns/demo-ant-buster.php
git commit -m "feat(ant-buster): render the playfield, cake, anthill, blanket"
```

---

## Task 6: Ant data structure, spawn, and rendering

**Files:**
- Modify: `patterns/demo-ant-buster.php` (add ant section, update `update()` and `render()`)

- [ ] **Step 1: Add the ant constructor and helper functions**

Find the `// === UPDATE === (Task 6+)` marker. Above it, add a new `// === ANTS ===` section:

```javascript
  // === ANTS ===
  function spawnAnt(type) {
    var ant = {
      type: type,
      x: game.anthill.x,
      y: game.anthill.y + (Math.random() * 6 - 3),
      hp: 10,
      maxHp: 10,
      speed: 30,
      heading: 0,         // radians; 0 = east
      dead: false,
      deathTime: 0
    };
    game.ants.push(ant);
  }
```

- [ ] **Step 2: Bootstrap 5 workers at game start (temporary, removed in Task 13)**

Inside the `// === ANTS ===` section, *after* the `spawnAnt` function declaration, append:

```javascript
  // Bootstrap ants for early-task verification (removed in Task 13)
  for (var ai = 0; ai < 5; ai++) {
    spawnAnt('worker');
  }
```

This runs at IIFE evaluation time, *after* `game.sprites` has been populated and `game.anthill` exists, so spawn position is valid.

- [ ] **Step 3: Render the ants**

Find the `render()` function. Just before the closing `}`, add:

```javascript
    // Ants
    for (var i = 0; i < game.ants.length; i++) {
      var a = game.ants[i];
      var sprite = a.dead ? game.sprites.splat : game.sprites.worker;
      ctx.drawImage(sprite, Math.round(a.x - 8), Math.round(a.y - 8));
    }
```

- [ ] **Step 4: Verify five ants render**

Reload. You should see 5 placeholder worker ants stacked near the anthill on the left edge. They don't move yet — that's Task 7.

- [ ] **Step 5: Commit**

```bash
git add patterns/demo-ant-buster.php
git commit -m "feat(ant-buster): add ant data structure, spawn, render (stationary)"
```

---

## Task 7: Erratic ant pathing (movement)

**Files:**
- Modify: `patterns/demo-ant-buster.php` (`update()` function and ANTS section)

- [ ] **Step 1: Add the steering math to the ANTS section**

Inside `// === ANTS ===`, after `spawnAnt` but before the bootstrap loop, add:

```javascript
  function angleTowards(fromX, fromY, toX, toY) {
    return Math.atan2(toY - fromY, toX - fromX);
  }

  function angleDelta(from, to) {
    var d = to - from;
    while (d > Math.PI) { d -= Math.PI * 2; }
    while (d < -Math.PI) { d += Math.PI * 2; }
    return d;
  }

  function updateAnt(ant, dt) {
    if (ant.dead) {
      ant.deathTime += dt;
      return;
    }
    var targetX = game.cake.x;
    var targetY = game.cake.y;
    var desiredHeading = angleTowards(ant.x, ant.y, targetX, targetY);
    var alignment = 0.6;       // Worker default; per-type table comes in Plan 2
    var jitterRate = 0.5;      // radians/sec of low-freq noise
    ant.heading += angleDelta(ant.heading, desiredHeading) * alignment * dt;
    ant.heading += (Math.random() * 2 - 1) * jitterRate * dt;
    ant.x += Math.cos(ant.heading) * ant.speed * dt;
    ant.y += Math.sin(ant.heading) * ant.speed * dt;
  }

  function updateAnts(dt) {
    for (var i = 0; i < game.ants.length; i++) {
      updateAnt(game.ants[i], dt);
    }
  }
```

- [ ] **Step 2: Wire `updateAnts` into the main update**

Replace the `update(dt)` body with:

```javascript
  function update(dt) {
    game.time += dt;
    if (game.state !== 'playing') { return; }
    updateAnts(dt);
  }
```

- [ ] **Step 3: Verify the ants wander toward the cake**

Reload. The 5 placeholder workers should drift across the blanket toward the cake at the center, with visible jitter (paths are not straight lines). They'll cluster on the cake and stop there (no collision yet — Task 8 handles that).

- [ ] **Step 4: Commit**

```bash
git add patterns/demo-ant-buster.php
git commit -m "feat(ant-buster): erratic-wander pathing toward the cake"
```

---

## Task 8: Cake collision, life counter, lose state

**Files:**
- Modify: `patterns/demo-ant-buster.php` (ANTS section, render() for lose overlay)

- [ ] **Step 1: Add collision and lose-state logic**

Inside `// === ANTS ===`, after `updateAnts`, add:

```javascript
  function checkCakeCollision() {
    var cakeR = 14;
    for (var i = 0; i < game.ants.length; i++) {
      var a = game.ants[i];
      if (a.dead) { continue; }
      var dx = a.x - game.cake.x;
      var dy = a.y - game.cake.y;
      if (dx * dx + dy * dy < cakeR * cakeR) {
        game.cake.slices -= 1;
        a.dead = true;
        a.deathTime = 0.5;       // mark for cleanup
        if (game.cake.slices <= 0) {
          game.cake.slices = 0;
          game.state = 'lost';
        }
      }
    }
  }

  function pruneAnts() {
    var kept = [];
    for (var i = 0; i < game.ants.length; i++) {
      var a = game.ants[i];
      if (!a.dead) {
        kept.push(a);
      } else if (a.deathTime < 0.5) {
        kept.push(a);
      }
    }
    game.ants = kept;
  }
```

- [ ] **Step 2: Wire collision and prune into update**

Replace the `update(dt)` body again with:

```javascript
  function update(dt) {
    game.time += dt;
    if (game.state !== 'playing') { return; }
    updateAnts(dt);
    checkCakeCollision();
    pruneAnts();
  }
```

- [ ] **Step 3: Add a "GAME OVER" overlay to render()**

At the end of `render()`, before the closing `}`, add:

```javascript
    if (game.state === 'lost') {
      ctx.fillStyle = 'rgba(0, 0, 0, 0.6)';
      ctx.fillRect(0, 0, WIDTH, HEIGHT);
      ctx.fillStyle = '#e43b44';
      ctx.font = '16px monospace';
      ctx.textAlign = 'center';
      ctx.fillText('THE ANTS WIN', WIDTH / 2, HEIGHT / 2);
      ctx.textAlign = 'start';
    }
  }
```

(Note: the closing `}` is already there — make sure you add the overlay block *inside* the function and the existing `}` stays.)

- [ ] **Step 4: Verify the lose state**

Reload. The 5 ants will reach the cake and consume it slice by slice. After 5 ants reach the cake (cake has 8 slices, but only 5 ants exist in the bootstrap), the cake should look smaller. Console: `__ab.cake.slices` should be 3 after the bootstrap ants finish.

To force the lose state for testing, in console: `for (var i = 0; i < 10; i++) __ab.ants.push({ type: 'worker', x: 16, y: 135, hp: 10, maxHp: 10, speed: 30, heading: 0, dead: false, deathTime: 0 });`. Wait. Cake should empty, "THE ANTS WIN" overlay should appear, and ants should stop moving (game.state = 'lost').

- [ ] **Step 5: Commit**

```bash
git add patterns/demo-ant-buster.php
git commit -m "feat(ant-buster): cake collision, life counter, lose state"
```

---

## Task 9: Cannon shop UI panel

**Files:**
- Modify: `patterns/demo-ant-buster.php` (add SHOP and INPUT sections, update render())

- [ ] **Step 1: Add a CANNONS section and shop card definition**

Find `// === ANTS ===` (or wherever the ants section ends) and *after* the closing of the ants section, add:

```javascript
  // === CANNONS ===
  var CANNON_DEFS = {
    howitzer: {
      name: 'Howitzer',
      cost: 50,
      damage: 5,
      fireRate: 0.6,         // shots per second
      range: 110,
      projectileSpeed: 200
    }
  };

  function cannonAt(x, y) {
    for (var i = 0; i < game.cannons.length; i++) {
      var c = game.cannons[i];
      var dx = c.x - x;
      var dy = c.y - y;
      if (dx * dx + dy * dy < 16 * 16) {
        return c;
      }
    }
    return null;
  }

  function canPlaceCannon(x, y) {
    if (x < 0) { return false; }
    if (x > PLAYFIELD_W) { return false; }
    if (y < HUD_H) { return false; }
    if (y > HEIGHT) { return false; }
    var dxCake = x - game.cake.x;
    var dyCake = y - game.cake.y;
    if (dxCake * dxCake + dyCake * dyCake < 24 * 24) { return false; }
    var dxAnt = x - game.anthill.x;
    var dyAnt = y - game.anthill.y;
    if (dxAnt * dxAnt + dyAnt * dyAnt < 24 * 24) { return false; }
    if (cannonAt(x, y)) { return false; }
    return true;
  }

  function placeCannon(defKey, x, y) {
    var def = CANNON_DEFS[defKey];
    if (!def) { return false; }
    if (game.gold < def.cost) { return false; }
    if (!canPlaceCannon(x, y)) { return false; }
    game.gold -= def.cost;
    game.cannons.push({
      defKey: defKey,
      x: x,
      y: y,
      heading: 0,
      cooldown: 0,
      target: null
    });
    return true;
  }
```

- [ ] **Step 2: Add the input layer and drag-to-place**

After the CANNONS section, add:

```javascript
  // === INPUT ===
  var ui = {
    dragging: null,         // { defKey, mouseX, mouseY } when dragging from shop
    mouseX: 0,
    mouseY: 0
  };

  function canvasToGame(evt) {
    var rect = canvas.getBoundingClientRect();
    var sx = WIDTH / rect.width;
    var sy = HEIGHT / rect.height;
    return {
      x: (evt.clientX - rect.left) * sx,
      y: (evt.clientY - rect.top) * sy
    };
  }

  function shopCardAt(x, y) {
    // One card for now; positioned in the shop panel.
    if (x < PLAYFIELD_W + 8) { return null; }
    if (x > PLAYFIELD_W + 8 + 56) { return null; }
    if (y < 32) { return null; }
    if (y > 32 + 64) { return null; }
    return 'howitzer';
  }

  canvas.addEventListener('mousedown', function (evt) {
    var p = canvasToGame(evt);
    ui.mouseX = p.x;
    ui.mouseY = p.y;
    var card = shopCardAt(p.x, p.y);
    if (card) {
      ui.dragging = { defKey: card };
      canvas.classList.add('ab-dragging');
    }
  });

  canvas.addEventListener('mousemove', function (evt) {
    var p = canvasToGame(evt);
    ui.mouseX = p.x;
    ui.mouseY = p.y;
  });

  canvas.addEventListener('mouseup', function (evt) {
    var p = canvasToGame(evt);
    ui.mouseX = p.x;
    ui.mouseY = p.y;
    if (ui.dragging) {
      placeCannon(ui.dragging.defKey, p.x, p.y);
      ui.dragging = null;
      canvas.classList.remove('ab-dragging');
    }
  });

  canvas.addEventListener('mouseleave', function () {
    if (ui.dragging) {
      ui.dragging = null;
      canvas.classList.remove('ab-dragging');
    }
  });
```

- [ ] **Step 3: Render the shop card and the drag preview**

Find `render()`. Just before the lose-state overlay, add:

```javascript
    // Shop card for Howitzer
    var cardX = PLAYFIELD_W + 8;
    var cardY = 32;
    ctx.fillStyle = '#2a2f3e';
    ctx.fillRect(cardX, cardY, 56, 64);
    ctx.drawImage(game.sprites.cannonBase, cardX + 12, cardY + 4);
    ctx.drawImage(game.sprites.cannonTurret, cardX + 18, cardY + 10);
    ctx.fillStyle = '#ffffff';
    ctx.font = '8px monospace';
    ctx.textAlign = 'center';
    ctx.fillText('Howitzer', cardX + 28, cardY + 50);
    ctx.fillStyle = (game.gold >= CANNON_DEFS.howitzer.cost) ? '#fee761' : '#888';
    ctx.fillText('50g', cardX + 28, cardY + 60);
    ctx.textAlign = 'start';

    // Placed cannons
    for (var ci = 0; ci < game.cannons.length; ci++) {
      var c = game.cannons[ci];
      ctx.drawImage(game.sprites.cannonBase, Math.round(c.x - 16), Math.round(c.y - 16));
      ctx.save();
      ctx.translate(Math.round(c.x), Math.round(c.y));
      ctx.rotate(c.heading + Math.PI / 2);   // turret art points up; heading 0 = east
      ctx.drawImage(game.sprites.cannonTurret, -10, -16);
      ctx.restore();
    }

    // Drag preview
    if (ui.dragging) {
      var ok = canPlaceCannon(ui.mouseX, ui.mouseY);
      ctx.globalAlpha = 0.6;
      ctx.drawImage(game.sprites.cannonBase, Math.round(ui.mouseX - 16), Math.round(ui.mouseY - 16));
      ctx.drawImage(game.sprites.cannonTurret, Math.round(ui.mouseX - 10), Math.round(ui.mouseY - 16));
      ctx.globalAlpha = 1;
      // Range circle
      ctx.strokeStyle = ok ? 'rgba(255, 255, 255, 0.5)' : 'rgba(228, 59, 68, 0.7)';
      ctx.lineWidth = 1;
      ctx.beginPath();
      ctx.arc(ui.mouseX, ui.mouseY, CANNON_DEFS.howitzer.range, 0, Math.PI * 2);
      ctx.stroke();
    }
```

- [ ] **Step 4: Verify shop and placement**

Reload. You should see:
- A "Howitzer / 50g" card in the shop panel (right side).
- Click and hold on the card; a translucent cannon ghost follows the cursor with a range circle.
- Drop it on the blanket; a real cannon stays where you placed it.
- Try dropping on the cake — the range circle turns red and placement is rejected.
- Console: `__ab.cannons.length` reflects placed cannons.
- Console: `__ab.gold` decreases by 50 per placement; placement fails when gold runs out.

- [ ] **Step 5: Commit**

```bash
git add patterns/demo-ant-buster.php
git commit -m "feat(ant-buster): cannon shop, drag-to-place, range preview"
```

---

## Task 10: Cannon targeting and projectiles

**Files:**
- Modify: `patterns/demo-ant-buster.php` (CANNONS and PROJECTILES sections, update())

- [ ] **Step 1: Add cannon targeting and firing**

In the CANNONS section, after `placeCannon`, add:

```javascript
  function findTarget(cannon) {
    var def = CANNON_DEFS[cannon.defKey];
    var rangeSq = def.range * def.range;
    var nearest = null;
    var nearestDistSq = rangeSq;
    for (var i = 0; i < game.ants.length; i++) {
      var a = game.ants[i];
      if (a.dead) { continue; }
      var dx = a.x - cannon.x;
      var dy = a.y - cannon.y;
      var distSq = dx * dx + dy * dy;
      if (distSq < nearestDistSq) {
        nearest = a;
        nearestDistSq = distSq;
      }
    }
    return nearest;
  }

  function updateCannons(dt) {
    for (var i = 0; i < game.cannons.length; i++) {
      var c = game.cannons[i];
      var def = CANNON_DEFS[c.defKey];
      c.cooldown -= dt;
      var target = findTarget(c);
      c.target = target;
      if (target) {
        c.heading = angleTowards(c.x, c.y, target.x, target.y);
        if (c.cooldown <= 0) {
          fireProjectile(c, target);
          c.cooldown = 1 / def.fireRate;
        }
      }
    }
  }
```

- [ ] **Step 2: Add the PROJECTILES section**

After `// === CANNONS ===`, before `// === INPUT ===`, add:

```javascript
  // === PROJECTILES ===
  function fireProjectile(cannon, target) {
    var def = CANNON_DEFS[cannon.defKey];
    var heading = angleTowards(cannon.x, cannon.y, target.x, target.y);
    game.projectiles.push({
      x: cannon.x,
      y: cannon.y,
      vx: Math.cos(heading) * def.projectileSpeed,
      vy: Math.sin(heading) * def.projectileSpeed,
      damage: def.damage,
      life: 1.5     // seconds
    });
  }

  function updateProjectiles(dt) {
    var kept = [];
    for (var i = 0; i < game.projectiles.length; i++) {
      var p = game.projectiles[i];
      p.x += p.vx * dt;
      p.y += p.vy * dt;
      p.life -= dt;
      var hit = false;
      for (var j = 0; j < game.ants.length; j++) {
        var a = game.ants[j];
        if (a.dead) { continue; }
        var dx = a.x - p.x;
        var dy = a.y - p.y;
        if (dx * dx + dy * dy < 6 * 6) {
          a.hp -= p.damage;
          if (a.hp <= 0) {
            a.dead = true;
            a.deathTime = 0;
          }
          hit = true;
          break;
        }
      }
      if (!hit) {
        if (p.life > 0) {
          if (p.x >= 0) {
            if (p.x <= PLAYFIELD_W) {
              if (p.y >= 0) {
                if (p.y <= HEIGHT) {
                  kept.push(p);
                }
              }
            }
          }
        }
      }
    }
    game.projectiles = kept;
  }
```

(Note the deeply nested `if` blocks: those replace `if (!hit && p.life > 0 && p.x >= 0 && ...)` because the inline-script `&&` encoding gotcha would mangle the chain. Nesting is uglier but reliable.)

- [ ] **Step 3: Wire updates into the main `update()`**

Replace `update()` again with:

```javascript
  function update(dt) {
    game.time += dt;
    if (game.state !== 'playing') { return; }
    updateAnts(dt);
    updateCannons(dt);
    updateProjectiles(dt);
    checkCakeCollision();
    pruneAnts();
  }
```

- [ ] **Step 4: Render projectiles**

In `render()`, after the placed-cannons loop, before the drag-preview block, add:

```javascript
    // Projectiles
    for (var pi = 0; pi < game.projectiles.length; pi++) {
      var p = game.projectiles[pi];
      ctx.drawImage(game.sprites.projectile, Math.round(p.x - 2), Math.round(p.y - 2));
    }
```

- [ ] **Step 5: Verify cannons fire and projectiles hit**

Reload. The bootstrap 5 ants spawn and start crossing the blanket. Drag a Howitzer onto the blanket near the path. The cannon should rotate to track ants entering its range, fire small projectiles, and the projectiles should land on ants. After ~2 hits, an ant should "die" (placed splat sprite, then disappear after 0.5s).

- [ ] **Step 6: Commit**

```bash
git add patterns/demo-ant-buster.php
git commit -m "feat(ant-buster): cannon targeting + projectile system"
```

---

## Task 11: Gold drops and economy

**Files:**
- Modify: `patterns/demo-ant-buster.php` (collision and projectile hit handlers)

- [ ] **Step 1: Award gold when an ant dies from a projectile**

Inside `updateProjectiles`, where `a.hp -= p.damage;` is followed by `if (a.hp <= 0) { ... }`, change the death block to:

```javascript
          if (a.hp <= 0) {
            a.dead = true;
            a.deathTime = 0;
            game.gold += 2;       // Worker drops 2 gold; per-type table comes in Plan 2
          }
```

- [ ] **Step 2: Verify gold counter**

Reload. Place a Howitzer (gold should drop 150 → 100). Wait for it to kill ants. After each kill, `__ab.gold` in console should increase by 2. With the bootstrap 5 ants, the cannon should kill them all and gold should end at 110.

- [ ] **Step 3: Commit**

```bash
git add patterns/demo-ant-buster.php
git commit -m "feat(ant-buster): award gold on ant kill"
```

---

## Task 12: HUD bar (gold, lives, wave, time)

**Files:**
- Modify: `patterns/demo-ant-buster.php` (`render()`)

- [ ] **Step 1: Render the HUD bar contents**

Find the line in `render()` that draws the dim HUD bar (`ctx.fillStyle = 'rgba(0, 0, 0, 0.4)'; ctx.fillRect(0, 0, PLAYFIELD_W, HUD_H);`). Replace it with:

```javascript
    // HUD bar
    ctx.fillStyle = 'rgba(0, 0, 0, 0.55)';
    ctx.fillRect(0, 0, PLAYFIELD_W, HUD_H);
    ctx.font = '10px monospace';
    ctx.textBaseline = 'middle';
    ctx.fillStyle = '#fee761';
    ctx.fillText('Gold: ' + game.gold, 6, 12);
    ctx.fillStyle = '#e43b44';
    ctx.fillText('Cake: ' + game.cake.slices + '/8', 80, 12);
    ctx.fillStyle = '#ffffff';
    ctx.fillText('Wave: ' + game.wave, 160, 12);
    ctx.textBaseline = 'alphabetic';
```

- [ ] **Step 2: Verify HUD shows values**

Reload. The top of the playfield shows `Gold: 150  Cake: 8/8  Wave: 1`. As you place a cannon and ants die / reach the cake, the values update in real time.

- [ ] **Step 3: Commit**

```bash
git add patterns/demo-ant-buster.php
git commit -m "feat(ant-buster): HUD bar showing gold, cake slices, wave"
```

---

## Task 13: Hardcoded wave 1 (replace bootstrap ants)

**Files:**
- Modify: `patterns/demo-ant-buster.php` (replace the bootstrap loop with a wave system)

- [ ] **Step 1: Remove the bootstrap loop**

In the ANTS section, find:

```javascript
  // Bootstrap ants for early-task verification (removed in Task 15)
  for (var ai = 0; ai < 5; ai++) {
    spawnAnt('worker');
  }
```

Delete this block.

- [ ] **Step 2: Add a WAVE section**

After `// === PROJECTILES ===` (or wherever projectiles end), add:

```javascript
  // === WAVE ===
  var wave = {
    active: true,
    timeIntoWave: 0,
    spawnIndex: 0,
    schedule: [
      // [timeSec, type] pairs — wave 1: 8 Workers spread over 10s
      [0,  'worker'],
      [1.2, 'worker'],
      [2.4, 'worker'],
      [3.6, 'worker'],
      [5.0, 'worker'],
      [6.2, 'worker'],
      [7.4, 'worker'],
      [8.6, 'worker']
    ]
  };

  function updateWave(dt) {
    if (!wave.active) { return; }
    wave.timeIntoWave += dt;
    while (wave.spawnIndex < wave.schedule.length) {
      var entry = wave.schedule[wave.spawnIndex];
      if (entry[0] > wave.timeIntoWave) { break; }
      spawnAnt(entry[1]);
      wave.spawnIndex += 1;
    }
    // Wave complete = all spawns issued AND no live ants left
    if (wave.spawnIndex >= wave.schedule.length) {
      var liveAnts = 0;
      for (var i = 0; i < game.ants.length; i++) {
        if (!game.ants[i].dead) { liveAnts += 1; }
      }
      if (liveAnts === 0) {
        wave.active = false;
        game.state = 'won';
        game.gold += 21;        // wave-completion bonus: 20 + wave number (wave 1)
      }
    }
  }
```

- [ ] **Step 3: Wire `updateWave` into `update()`**

Replace `update()` with:

```javascript
  function update(dt) {
    game.time += dt;
    if (game.state !== 'playing') { return; }
    updateWave(dt);
    updateAnts(dt);
    updateCannons(dt);
    updateProjectiles(dt);
    checkCakeCollision();
    pruneAnts();
  }
```

- [ ] **Step 4: Verify wave plays out**

Reload. The canvas should be empty of ants at start. Over the next ~9 seconds, 8 Workers spawn one at a time and march toward the cake. Place a Howitzer to defend. If you defend successfully (kill all 8 before any reach the cake), the game state flips to `'won'`. If you don't (cake hits 0), state flips to `'lost'`.

Console: `__ab.state` should be `'playing'` during the wave, then `'won'` or `'lost'` at the end.

- [ ] **Step 5: Commit**

```bash
git add patterns/demo-ant-buster.php
git commit -m "feat(ant-buster): wave 1 spawn schedule, wave-complete detection"
```

---

## Task 14: Win / lose overlays + restart

**Files:**
- Modify: `patterns/demo-ant-buster.php` (`render()` and add restart logic to INPUT)

- [ ] **Step 1: Add a "VICTORY" overlay alongside the existing lose overlay**

In `render()`, find the lose-state overlay and replace it with:

```javascript
    if (game.state === 'lost') {
      ctx.fillStyle = 'rgba(0, 0, 0, 0.6)';
      ctx.fillRect(0, 0, WIDTH, HEIGHT);
      ctx.fillStyle = '#e43b44';
      ctx.font = '16px monospace';
      ctx.textAlign = 'center';
      ctx.fillText('THE ANTS WIN', WIDTH / 2, HEIGHT / 2 - 12);
      ctx.fillStyle = '#ffffff';
      ctx.font = '10px monospace';
      ctx.fillText('Click to restart', WIDTH / 2, HEIGHT / 2 + 8);
      ctx.textAlign = 'start';
    }

    if (game.state === 'won') {
      ctx.fillStyle = 'rgba(0, 0, 0, 0.6)';
      ctx.fillRect(0, 0, WIDTH, HEIGHT);
      ctx.fillStyle = '#fee761';
      ctx.font = '16px monospace';
      ctx.textAlign = 'center';
      ctx.fillText('CAKE SAVED', WIDTH / 2, HEIGHT / 2 - 12);
      ctx.fillStyle = '#ffffff';
      ctx.font = '10px monospace';
      ctx.fillText('Click to restart', WIDTH / 2, HEIGHT / 2 + 8);
      ctx.textAlign = 'start';
    }
```

- [ ] **Step 2: Add a `resetGame()` function**

In the WAVE section (or wherever clean), after `updateWave`, add:

```javascript
  function resetGame() {
    game.state = 'playing';
    game.time = 0;
    game.cake.slices = 8;
    game.ants = [];
    game.cannons = [];
    game.projectiles = [];
    game.gold = 150;
    game.wave = 1;
    wave.active = true;
    wave.timeIntoWave = 0;
    wave.spawnIndex = 0;
  }
```

- [ ] **Step 3: Wire restart into the mousedown handler**

In the INPUT section, find the `canvas.addEventListener('mousedown', function (evt) { ... })` handler. Replace its body with:

```javascript
    var p = canvasToGame(evt);
    ui.mouseX = p.x;
    ui.mouseY = p.y;
    if (game.state === 'won') { resetGame(); return; }
    if (game.state === 'lost') { resetGame(); return; }
    var card = shopCardAt(p.x, p.y);
    if (card) {
      ui.dragging = { defKey: card };
      canvas.classList.add('ab-dragging');
    }
```

- [ ] **Step 4: Verify win and restart flow**

Reload. Play through. Successfully kill all 8 ants → "CAKE SAVED" overlay appears → click anywhere → game resets to wave 1 with full cake and 150 gold.

Then play and *let* an ant reach the cake (don't place enough cannons). After 8 cake hits → "THE ANTS WIN" overlay → click to restart → fresh wave 1.

- [ ] **Step 5: Commit**

```bash
git add patterns/demo-ant-buster.php
git commit -m "feat(ant-buster): win/lose overlays + click-to-restart"
```

---

## Task 15: Final polish and bump theme version

**Files:**
- Modify: `style.css` (theme version bump)
- Modify: `patterns/demo-ant-buster.php` (small polish: HUD wave text, intro tooltip)

- [ ] **Step 1: Add a one-time intro hint**

In `render()`, just before the win/lose overlays, add:

```javascript
    if (game.time < 4) {
      if (game.cannons.length === 0) {
        ctx.fillStyle = 'rgba(0, 0, 0, 0.5)';
        ctx.fillRect(60, 100, 200, 70);
        ctx.fillStyle = '#fee761';
        ctx.font = '10px monospace';
        ctx.textAlign = 'center';
        ctx.fillText('Drag a cannon from the shop', 160, 130);
        ctx.fillText('onto the picnic blanket to start!', 160, 145);
        ctx.textAlign = 'start';
      }
    }
```

- [ ] **Step 2: Bump the theme version (final time for Plan 1)**

In `style.css`, bump the `Version:` patch number again. This ensures the inline `<script>` cache-buster picks up all the changes from Tasks 2–14 in any environment that aggressively caches the pattern output.

- [ ] **Step 3: Verify the full Plan 1 experience**

Reload `/demos/ant-buster/` in an incognito window (so caches don't lie). Verify, top to bottom:

- Heading, eyebrow, intro paragraph render.
- Canvas appears at full size with the dark frame.
- Tutorial hint shows for the first ~4 seconds.
- Picnic blanket, cake, anthill render.
- HUD shows `Gold: 150  Cake: 8/8  Wave: 1`.
- Howitzer shop card visible on right with "50g" text in yellow.
- Drag-to-place works, including the red preview when invalid.
- Wave 1 spawns 8 Workers over ~9 seconds.
- Workers wander erratically toward the cake (visible jitter, not straight lines).
- Placed Howitzer rotates to track ants and fires projectiles.
- Hits damage ants, kills give 2 gold, dead ants show splat then disappear.
- Cake takes damage from ants that reach it.
- Win state ("CAKE SAVED") if all ants are killed.
- Lose state ("THE ANTS WIN") if cake reaches 0.
- Click anywhere on the win/lose overlay restarts wave 1 with fresh state.

- [ ] **Step 4: Commit**

```bash
git add style.css patterns/demo-ant-buster.php
git commit -m "feat(ant-buster): intro hint + bump theme version for Plan 1 completion"
```

---

## Plan 1 done. What's next.

After Task 15 commits cleanly, Plan 1 is complete. The MVP plays end-to-end: a single cannon type defends against one ant type across one hardcoded wave, with full game-state transitions.

**Plan 2 will add:**

- Full ant roster (Scout, Soldier, Carrier, Tank, Splitter, Phase, Bomber, Queen, King) with per-type stats and behaviors.
- Full 48-cannon tree with the upgrade UI.
- All 25 hand-tuned waves from §4.2 of the spec.
- Tower-detail panel with upgrade and sell buttons.
- Per-ant-type behaviors (Carrier crumb-pickup-and-flee, Bomber death-AoE-disabling-cannons, Phase intangibility, Splitter mini-spawns).

**Plan 2 will not add:**

- Title screen, save state, endless mode, tutorial overlay, sound, keyboard shortcuts. Those are Plan 3.
- Real PNG sprite art. That's Plan 4.

---

## Self-review notes

This plan was self-reviewed for:

1. **Spec coverage:** Plan 1 implements §1, §2 (single-file pattern + canvas integration), §5.1 (map dimensions), §5.2 (erratic pathing — Worker only), §5.4 (free placement), §5.5 (no soft repulsion yet — deferred to Plan 2 since with one ant type and one cannon it doesn't matter), §6.1 (gold from kills + wave bonus, simplified), §6.3 (win/lose), §7.3 (HUD bar partial), §7.4 (shop panel partial), §8 (procedural placeholder sprites instead of PNG art — explicitly scoped this way), §9 (Endesga-32 colors used in placeholder generation). All deferrals are intentional and called out.

2. **Placeholder scan:** No "TBD", no "implement later", no "similar to". Every step has actual code or actual commands.

3. **Type consistency:** `game.gold`, `game.ants`, `game.cannons`, `game.projectiles`, `game.cake.slices`, `game.state` are used identically across all tasks. Function names (`spawnAnt`, `updateAnts`, `updateCannons`, `placeCannon`, `findTarget`, `fireProjectile`, `updateProjectiles`, `pruneAnts`, `resetGame`, `updateWave`) are consistent across their definitions and call sites. The `wave` object is declared in Task 13 and consumed by `resetGame` in Task 14 — both match.

4. **Inline-script gotcha:** No `&&` is written anywhere in the JavaScript. Multi-condition checks are nested `if` blocks throughout (verified in Task 10 projectile pruning). `||` is also avoided. `<` and `>` only appear in clearly numeric comparison contexts where WordPress's content filter does not interfere.
