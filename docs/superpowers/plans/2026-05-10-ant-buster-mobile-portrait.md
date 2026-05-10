# Ant Buster Mobile / Portrait Layout Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a portrait layout (480 × 600 internal canvas coords) to the Ant Buster demo so it is comfortable to play on a phone in portrait, while keeping the existing landscape layout (640 × 360) byte-identical for desktop and tablet.

**Architecture:** Two internal coordinate systems, picked at canvas init and on every `resize` / `orientationchange`. Layout constants (`WIDTH`, `HEIGHT`, `PLAYFIELD_W`, `SHOP_W`, `STRIP_H`, plus a new `PLAYFIELD_H`) are mutated by a `relayout()` helper. Per-mode draw functions (`drawShop`, `drawTowerPanel`, `drawShopInfoPanel`, `drawShopBackground`, splash overlays) branch on `isPortrait()` to render either the landscape right-column layout or the portrait bottom-strip layout. Game logic (cannons, ants, waves, combat) does not change.

**Tech Stack:** Plain JavaScript inline in `patterns/demo-ant-buster.php` (no build step, no JSX). HTML5 Canvas 2D API. Pointer Events for input (already shipped). CSS media queries for the canvas aspect ratio breakpoint.

---

## File Structure

All implementation work lands in two files:

- **`patterns/demo-ant-buster.php`** — the entire demo (game logic, render layer, input handlers, inline CSS, inline `<script>`). Modified throughout. Roughly 7000 lines today.
- **`style.css`** — theme version bump on the final task (0.15.0 → 0.16.0) so WordPress invalidates its pattern cache and the `style.css?ver=` query string updates.

No new files. The portrait constants, the `relayout()` helper, the `isPortrait()` helper, the `inPlayfield()` helper, and the per-mode portrait branches all live inside the existing inline `<script>` IIFE in `patterns/demo-ant-buster.php`.

## Inline-script gotcha (read first, applies to every task)

Per the top of `CLAUDE.md`, WordPress's content filter HTML-encodes `&&` to `&#038;&#038;` inside inline `<script>` blocks. Browsers don't decode HTML entities inside `<script>`, so the JS parser sees a literal `&#038;&#038;` and throws `SyntaxError`. Default to writing without `&&` or `||`. Use nested `if` statements, helper variables that capture truthiness once, and `Array.indexOf` for membership checks.

After every code-changing step, verify with:

```bash
curl -s "http://localhost/dariobimbi.com/demos/ant-buster/" | grep -c '&#038;&#038;'
```

Expected output: `0`.

If the count is non-zero, find the `&&` or `||` in the new code and rewrite as nested `if`. Don't move on until the count is zero.

---

## Task 1: Layout constants + `relayout()` + `isPortrait()` + CSS media query

**Files:**
- Modify: `patterns/demo-ant-buster.php` (constants block at lines 88-92; new helpers after the constants block; new CSS rule in the `<style>` block at lines 33-78)

This task lays the foundation. After it lands, the canvas can switch between landscape and portrait coords on rotation, but every drawing function still reads `WIDTH`, `HEIGHT`, `PLAYFIELD_W`, `SHOP_W` literally — so the visual result in portrait will be a stretched landscape render. Subsequent tasks fix each surface.

- [ ] **Step 1: Convert layout constants from `var` (immutable feel) to `var` mutables and add `STRIP_H` + `PLAYFIELD_H`**

In `patterns/demo-ant-buster.php`, find lines 88-92:

```js
  var WIDTH = 640;
  var HEIGHT = 360;
  var PLAYFIELD_W = 480;
  var HUD_H = 24;
  var SHOP_W = 160;
```

Replace with:

```js
  // Layout constants. Mutated at boot and on rotation by relayout(). The
  // landscape values (640x360, right sidebar) are the desktop default; the
  // portrait values (480x600, bottom strip) kick in when isPortrait() is
  // true. PLAYFIELD_H is new and equals HEIGHT - HUD_H in landscape and
  // HEIGHT - HUD_H - STRIP_H in portrait. STRIP_H is the height of the
  // bottom strip in portrait, 0 in landscape.
  var WIDTH = 640;
  var HEIGHT = 360;
  var PLAYFIELD_W = 480;
  var PLAYFIELD_H = 336;
  var HUD_H = 24;
  var SHOP_W = 160;
  var STRIP_H = 0;
```

- [ ] **Step 2: Add `isPortrait()` and `relayout()` helpers**

Find the line `var SHOP_W = 160;` (now `var STRIP_H = 0;` after Step 1). Immediately AFTER the constants block (a blank line follows it), insert:

```js
  // Returns true when the canvas's host element is taller than wide. Reads
  // the bounding rect of the .ab-stage element; falls back to window
  // innerWidth/innerHeight if the stage isn't measurable yet (boot race).
  function isPortrait() {
    var stage = canvas.parentElement;
    if (stage) {
      var r = stage.getBoundingClientRect();
      if (r.width > 0) {
        if (r.height >= r.width) { return true; }
        return false;
      }
    }
    if (window.innerHeight >= window.innerWidth) { return true; }
    return false;
  }

  // Mutates the layout constants and resizes the backing canvas. Called once
  // at boot and again on resize/orientationchange when isPortrait() flips.
  // Game state (cannons, gold, wave, paused, selectedCannon, armedKind, etc.)
  // is preserved — only render-layer constants change.
  function relayout() {
    if (isPortrait()) {
      WIDTH = 480;
      HEIGHT = 600;
      PLAYFIELD_W = 480;
      PLAYFIELD_H = 336;
      SHOP_W = 0;
      STRIP_H = 240;
    } else {
      WIDTH = 640;
      HEIGHT = 360;
      PLAYFIELD_W = 480;
      PLAYFIELD_H = 336;
      SHOP_W = 160;
      STRIP_H = 0;
    }
    // 2x retina backing buffer.
    canvas.width = WIDTH * 2;
    canvas.height = HEIGHT * 2;
    ctx.setTransform(2, 0, 0, 2, 0, 0);
  }
```

The helpers reference `canvas` and `ctx` which are defined later in the file. Function declarations are hoisted, so the references are fine, but the calls to `relayout()` will not happen until after `canvas` and `ctx` exist (see Step 4).

- [ ] **Step 3: Wire `relayout()` into boot and resize**

Find the existing `canvas.width = WIDTH * 2; canvas.height = HEIGHT * 2;` lines (search the file for `canvas.width = WIDTH * 2`). They sit shortly after `var ctx = canvas.getContext('2d');`.

Replace those two lines (and the existing `ctx.setTransform(2, 0, 0, 2, 0, 0);` line that follows) with a single call to `relayout()`:

```js
  relayout();
```

Then, AFTER the `relayout();` line, add a resize listener:

```js
  // On rotate / window resize, re-pick the layout if portrait/landscape flips.
  // Cache the last result so we only run relayout() on actual changes — not
  // every pixel-by-pixel resize event during a slow drag.
  var lastIsPortrait = isPortrait();
  window.addEventListener('resize', function () {
    var nowPortrait = isPortrait();
    if (nowPortrait !== lastIsPortrait) {
      lastIsPortrait = nowPortrait;
      relayout();
    }
  });
  window.addEventListener('orientationchange', function () {
    var nowPortrait = isPortrait();
    if (nowPortrait !== lastIsPortrait) {
      lastIsPortrait = nowPortrait;
      relayout();
    }
  });
```

- [ ] **Step 4: Add the portrait CSS media query**

Find the inline `<style>` block at lines 33-78. Find the existing `@media (max-width: 1000px)` block:

```css
@media (max-width: 1000px) {
  .demo-ant-buster #ab-canvas {
    width: 100%;
  }
}
```

AFTER it, add:

```css
@media (max-aspect-ratio: 1/1) {
  .demo-ant-buster #ab-canvas {
    aspect-ratio: 480 / 600;
    width: 480px;
  }
}
```

The `(max-aspect-ratio: 1/1)` query fires on any viewport whose height is greater than or equal to width — phones in portrait, narrow desktop windows, etc. The `width: 480px` line keeps the canvas pixel-perfect at desktop sizes (where it's 480 px wide centered in the column) and the `max-width: 100%` from the previous rule shrinks it on narrower viewports.

- [ ] **Step 5: Verify**

Refresh `http://localhost/dariobimbi.com/demos/ant-buster/` in a desktop browser. Game should look identical to today (landscape, 640x360 canvas).

Then narrow the browser window to less than 1:1 aspect ratio (taller than wide). The canvas should:

- Re-shape to portrait (480x600 internal, taller than wide)
- Show stretched / mis-positioned content (this is expected — Tasks 2-7 fix each surface)
- Not error in the JS console (open DevTools to confirm no `Uncaught` errors)

Run the inline-script gotcha check:

```bash
curl -s "http://localhost/dariobimbi.com/demos/ant-buster/" | grep -c '&#038;&#038;'
```

Expected: `0`.

- [ ] **Step 6: Commit**

```bash
git add patterns/demo-ant-buster.php
git commit -m "feat(ant-buster): Plan mobile-portrait Task 1 — layout constants + relayout()"
```

---

## Task 2: HUD anchor formula

**Files:**
- Modify: `patterns/demo-ant-buster.php`, `drawHud()` function (search for `function drawHud()`, near line 2882)

The HUD already uses `PLAYFIELD_W` for the right-edge anchor of the pause/speed/Send-Next widgets. In portrait there is no shop sidebar, so the HUD spans the full canvas width. The right widgets should anchor to `WIDTH` instead. The four left readouts (gold / cake / wave / prep) already left-align starting at `x=4` and don't need to change.

- [ ] **Step 1: Update the right-widget anchor base**

Find the lines that set `ui.pauseRect.x` and `ui.speedRect.x`. They look like:

```js
    ui.pauseRect.x = PLAYFIELD_W - 122;
    ...
    ui.speedRect.x = PLAYFIELD_W - 96;
```

There's also a `var btnX = PLAYFIELD_W - btnW - 4;` for the Send-Next button.

Replace each `PLAYFIELD_W` with `WIDTH` in those three assignments:

```js
    ui.pauseRect.x = WIDTH - 122;
    ...
    ui.speedRect.x = WIDTH - 96;
```

And:

```js
      var btnX = WIDTH - btnW - 4;
```

The `drawWoodenPlaque(0, 0, PLAYFIELD_W, HUD_H);` call at the top of `drawHud()` should stay as `PLAYFIELD_W` in landscape but become `WIDTH` in portrait. Replace it with:

```js
    drawWoodenPlaque(0, 0, WIDTH, HUD_H);
```

In landscape `WIDTH === 640`, but the HUD plaque only paints up to `PLAYFIELD_W` (640's the canvas width but the right column is the shop). Wait — the shop is actually at `x = PLAYFIELD_W` in landscape, and the HUD plaque doesn't paint over the shop column. Actually re-reading the existing code: `drawWoodenPlaque(0, 0, PLAYFIELD_W, HUD_H)` paints only the HUD-strip portion of the canvas (over the playfield, not the shop). The shop has its own `drawShopBackground()`.

Keep the existing semantics: the HUD plaque covers the playfield-width strip in landscape, but the full canvas-width strip in portrait (where the shop is below, not to the right). So the `drawWoodenPlaque` call needs a conditional:

```js
    if (isPortrait()) {
      drawWoodenPlaque(0, 0, WIDTH, HUD_H);
    } else {
      drawWoodenPlaque(0, 0, PLAYFIELD_W, HUD_H);
    }
```

- [ ] **Step 2: Verify**

Refresh `http://localhost/dariobimbi.com/demos/ant-buster/` in landscape — HUD should look identical to today.

Resize to portrait. The HUD wooden plaque should now span the full width (480 internal px). The pause / speed / Send-Next widgets should sit at the right edge of the canvas (not at `WIDTH - 160 - 122` which would be off-screen).

Run the gotcha check (`curl ... | grep -c '&#038;&#038;'` returns `0`).

- [ ] **Step 3: Commit**

```bash
git add patterns/demo-ant-buster.php
git commit -m "feat(ant-buster): Plan mobile-portrait Task 2 — HUD anchor"
```

---

## Task 3: `drawShopBackground()` — paint right column or bottom strip

**Files:**
- Modify: `patterns/demo-ant-buster.php`, `drawShopBackground()` function (search for `function drawShopBackground()`, near line 1570)

`drawShopBackground()` paints the wooden plaque + cream-paper backdrop for the shop area. Today it paints the right column. In portrait it should paint the bottom strip instead.

- [ ] **Step 1: Branch the function on orientation**

Read the existing `drawShopBackground()` to understand its current implementation. It paints rectangles at `(PLAYFIELD_W, 0, SHOP_W, HEIGHT)` and similar — anchored to the right column.

Replace the function body with:

```js
  function drawShopBackground() {
    var sx; var sy; var sw; var sh;
    if (isPortrait()) {
      sx = 0;
      sy = HEIGHT - STRIP_H;
      sw = WIDTH;
      sh = STRIP_H;
    } else {
      sx = PLAYFIELD_W;
      sy = 0;
      sw = SHOP_W;
      sh = HEIGHT;
    }
    // Wood plaque base
    var grd = ctx.createLinearGradient(sx, sy, sx, sy + sh);
    grd.addColorStop(0, '#5a3a20');
    grd.addColorStop(1, '#3a2210');
    ctx.fillStyle = grd;
    ctx.fillRect(sx, sy, sw, sh);
    // Vertical wood-grain stripes for landscape; horizontal for portrait
    ctx.fillStyle = 'rgba(0, 0, 0, 0.08)';
    if (isPortrait()) {
      for (var gy = sy + 4; gy < sy + sh; gy = gy + 8) {
        ctx.fillRect(sx + 2, gy, sw - 4, 1);
      }
    } else {
      for (var gx = sx + 4; gx < sx + sw; gx = gx + 8) {
        ctx.fillRect(gx, sy + 2, 1, sh - 4);
      }
    }
    // Inner border edges
    ctx.fillStyle = '#2a1810';
    if (isPortrait()) {
      // Top edge dark border
      ctx.fillRect(sx, sy, sw, 2);
    } else {
      // Left edge dark border
      ctx.fillRect(sx, sy, 2, sh);
    }
  }
```

The function preserves the existing visual recipe (gradient + grain stripes + dark inner-edge border). The grain direction flips so the wood reads correctly in either orientation.

- [ ] **Step 2: Verify**

Landscape: identical to today.

Portrait: the bottom 240 px of the canvas should now have the wooden plaque background. The right column in portrait is empty (just the canvas background color). Other surfaces (shop cards, detail panel, info panel) still render at their landscape positions and are off the visible portrait area — that's expected. Tasks 4-6 fix each.

Gotcha check: `curl ... | grep -c '&#038;&#038;'` returns `0`.

- [ ] **Step 3: Commit**

```bash
git add patterns/demo-ant-buster.php
git commit -m "feat(ant-buster): Plan mobile-portrait Task 3 — shop background paints strip in portrait"
```

---

## Task 4: Shop list strip — 4-card row in portrait

**Files:**
- Modify: `patterns/demo-ant-buster.php`, `drawShop()` function (search for `function drawShop()`, near line 1865)

`drawShop()` is responsible for both the four shop cards and the "?" hangtag rects. In portrait the cards lay out left-to-right in the bottom strip with sprite + name + cost + a small DMG/RATE line.

- [ ] **Step 1: Branch the card-rendering loop on orientation**

Find `drawShop()` near line 1865. The function starts with a header label, an early-return for `shopMode === 'info'`, and then a `for (var i = 0; i < keys.length; i++)` loop that renders the cards.

Replace the keys + cardX/cardW/cardH/startY block (right after the `if (ui.shopMode === 'info') { drawShopInfoPanel(); return; }` early return) with:

```js
    var keys = ['howitzer', 'repeater', 'twingun', 'pink_pea'];
    ui.shopRects = [];
    ui.shopInfoRects = [];

    var portrait = isPortrait();
    var cardX; var cardW; var cardH; var startY; var startX; var dx; var dy;
    if (portrait) {
      // 4 cards in a row, 5 px outer + 4 px inner gaps. Card 115w x 230h.
      var stripY = HEIGHT - STRIP_H;
      var GAP = 4;
      cardW = (WIDTH - 5 * GAP) / 4;
      cardH = STRIP_H - 2 * GAP - 2;
      startX = GAP;
      startY = stripY + GAP;
      dx = cardW + GAP;
      dy = 0;
      cardX = startX;
    } else {
      cardX = PLAYFIELD_W + 6;
      cardW = SHOP_W - 12;
      cardH = 78;
      startX = cardX;
      startY = 26;
      dx = 0;
      dy = cardH + 4;
    }

    // "CANNONS" header — landscape only. Portrait uses the strip's own area
    // and skips the standalone header to save vertical space.
    if (!portrait) {
      drawTextWithShadow('CANNONS', PLAYFIELD_W + SHOP_W / 2, 14, '#c8a868', 'bold 9px monospace', 'center');
    }
```

Then update the per-card inner block. Find the `for (var i = 0; i < keys.length; i++) {` loop and replace its body. The full replacement loop body:

```js
    for (var i = 0; i < keys.length; i++) {
      var k = keys[i];
      var sk = CANNON_KINDS[k];
      var cx = startX + i * dx;
      var cy = startY + i * dy;
      var canAfford = (game.gold >= sk.cost);

      // Card body
      drawWoodenButton(cx, cy, cardW, cardH, 'wood');

      // Family color strip
      var stripColor = FAMILY_STRIP_COLOR[sk.family];
      if (!stripColor) { stripColor = '#888'; }
      ctx.fillStyle = stripColor;
      ctx.fillRect(cx + 2, cy + 2, 4, cardH - 4);
      ctx.fillStyle = '#2a1810';
      ctx.fillRect(cx + 6, cy + 2, 1, cardH - 4);

      if (portrait) {
        // === PORTRAIT CARD ===
        // Sprite tile 40x40, centered horizontally near the top.
        var spX = cx + cardW / 2 - 20;
        var spY = cy + 16;
        ctx.fillStyle = '#2a1810';
        ctx.fillRect(spX, spY, 40, 40);
        ctx.fillStyle = '#1a0a00';
        ctx.fillRect(spX, spY, 40, 1);
        ctx.fillRect(spX, spY, 1, 40);
        ctx.fillRect(spX + 39, spY, 1, 40);
        ctx.fillRect(spX, spY + 39, 40, 1);
        // Render the cannon (base + turret) in the centered tile. Sprites are
        // 32x32 so center inside the 40x40 inset with a 4 px margin.
        ctx.drawImage(game.sprites.cannonBase, spX + 4, spY + 4);
        var turret = game.sprites.turrets[k];
        if (turret) { ctx.drawImage(turret, spX + 10, spY + 4); }

        // Name centered under sprite. Truncate to fit card width minus 8 px.
        ctx.font = 'bold 11px monospace';
        var fittedName = truncateToWidth(sk.name, cardW - 8);
        drawTextWithShadow(fittedName, cx + cardW / 2, cy + 70, '#fee761', 'bold 11px monospace', 'center');

        // Cost row: coin + value, centered.
        ctx.font = 'bold 11px monospace';
        var costStr = '' + sk.cost;
        var costTextW = ctx.measureText(costStr).width;
        var rowW = 12 + 4 + costTextW;
        var rowStartX = cx + (cardW - rowW) / 2;
        ctx.drawImage(game.sprites.coin, rowStartX, cy + 90 - 6);
        var costColor;
        if (canAfford) { costColor = '#fee761'; } else { costColor = '#888'; }
        drawTextWithShadow(costStr, rowStartX + 16, cy + 90, costColor, 'bold 11px monospace', 'start');

        // DMG / RATE inline (RNG omitted — too tight for 3 columns at 115 wide).
        drawTextWithShadow('DMG ' + sk.damage + '   RATE ' + sk.fireRate, cx + cardW / 2, cy + 110, '#c8a868', 'bold 8px monospace', 'center');

        // Stats card-internal row at the bottom — recessed to match desktop visual.
        var statsY = cy + cardH - 32;
        ctx.fillStyle = 'rgba(0, 0, 0, 0.18)';
        ctx.fillRect(cx + 12, statsY, cardW - 24, 18);
        ctx.fillStyle = '#2a1810';
        ctx.fillRect(cx + 12, statsY, cardW - 24, 1);
        drawTextWithShadow('RNG', cx + cardW / 2 - 18, statsY + 9, '#c8a868', 'bold 8px monospace', 'start');
        drawTextWithShadow('' + sk.range, cx + cardW / 2 + 8, statsY + 9, '#f5e6c8', 'bold 10px monospace', 'start');

        // ? hangtag at top-right. Larger than landscape (16 vs 18) for tappability.
        var tagW = 18;
        var tagH = 18;
        var tagX = cx + cardW - tagW - 4;
        var tagY = cy + 4;
        ctx.fillStyle = '#fff8e8';
        ctx.fillRect(tagX, tagY, tagW, tagH);
        ctx.fillStyle = '#f0dfb8';
        ctx.fillRect(tagX + 1, tagY + tagH - 3, tagW - 2, 2);
        ctx.fillStyle = '#2a1810';
        ctx.fillRect(tagX, tagY, tagW, 1);
        ctx.fillRect(tagX, tagY, 1, tagH);
        ctx.fillRect(tagX + tagW - 1, tagY, 1, tagH);
        ctx.fillRect(tagX, tagY + tagH - 1, tagW, 1);
        ctx.fillStyle = '#2a1810';
        ctx.fillRect(tagX + tagW / 2 - 1, tagY - 3, 2, 4);
        ctx.font = 'bold 13px monospace';
        ctx.fillStyle = '#2a1810';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText('?', tagX + tagW / 2, tagY + tagH / 2 + 1);
        ctx.textAlign = 'start';
        ctx.textBaseline = 'alphabetic';
        ui.shopInfoRects.push({ x: tagX, y: tagY, w: tagW, h: tagH, kind: k });

        // Armed-card highlight + Tap a spot pill
        if (ui.armedKind === k) {
          ctx.strokeStyle = '#fee761';
          ctx.lineWidth = 3;
          ctx.strokeRect(cx + 1, cy + 1, cardW - 2, cardH - 2);
          ctx.lineWidth = 1;
          var pillW = cardW - 16;
          var pillH = 14;
          var pillX = cx + 8;
          var pillY = cy + cardH - 10;
          ctx.fillStyle = '#fee761';
          ctx.fillRect(pillX, pillY, pillW, pillH);
          ctx.fillStyle = '#2a1810';
          ctx.fillRect(pillX, pillY, pillW, 1);
          ctx.fillRect(pillX, pillY + pillH - 1, pillW, 1);
          ctx.fillRect(pillX, pillY, 1, pillH);
          ctx.fillRect(pillX + pillW - 1, pillY, 1, pillH);
          drawTextWithShadow('TAP A SPOT', pillX + pillW / 2, pillY + pillH / 2, '#2a1810', 'bold 8px monospace', 'center');
        }
      } else {
        // === LANDSCAPE CARD === (existing visual coherence layout, unchanged)
        // Sprite tile (32x32 dark inset)
        var spXL = cx + 10;
        var spYL = cy + 8;
        ctx.fillStyle = '#2a1810';
        ctx.fillRect(spXL, spYL, 32, 32);
        ctx.fillStyle = '#1a0a00';
        ctx.fillRect(spXL, spYL, 32, 1);
        ctx.fillRect(spXL, spYL, 1, 32);
        ctx.fillRect(spXL + 31, spYL, 1, 32);
        ctx.fillRect(spXL, spYL + 31, 32, 1);
        ctx.drawImage(game.sprites.cannonBase, spXL, spYL);
        var turretL = game.sprites.turrets[k];
        if (turretL) { ctx.drawImage(turretL, spXL + 6, spYL); }

        // Name
        ctx.font = 'bold 11px monospace';
        var fittedShopName = truncateToWidth(sk.name, cardW - 72);
        drawTextWithShadow(fittedShopName, spXL + 36, cy + 14, '#fee761', 'bold 11px monospace', 'start');

        // Cost row
        var costYL = cy + 30;
        ctx.drawImage(game.sprites.coin, spXL + 36, costYL - 5);
        var costColorL;
        if (canAfford) { costColorL = '#fee761'; } else { costColorL = '#888'; }
        drawTextWithShadow('' + sk.cost, spXL + 52, costYL + 1, costColorL, 'bold 11px monospace', 'start');

        // Stats row
        var statsYL = cy + 56;
        ctx.fillStyle = 'rgba(0, 0, 0, 0.18)';
        ctx.fillRect(cx + 12, statsYL - 4, cardW - 20, 18);
        ctx.fillStyle = '#2a1810';
        ctx.fillRect(cx + 12, statsYL - 4, cardW - 20, 1);
        var statColW = (cardW - 20) / 3;
        var col1X = cx + 12;
        var col2X = cx + 12 + statColW;
        var col3X = cx + 12 + 2 * statColW;
        drawTextWithShadow('DMG', col1X, statsYL + 4, '#c8a868', 'bold 8px monospace', 'start');
        drawTextWithShadow('' + sk.damage, col1X + 22, statsYL + 4, '#f5e6c8', 'bold 10px monospace', 'start');
        drawTextWithShadow('RATE', col2X, statsYL + 4, '#c8a868', 'bold 8px monospace', 'start');
        drawTextWithShadow('' + sk.fireRate, col2X + 26, statsYL + 4, '#f5e6c8', 'bold 10px monospace', 'start');
        drawTextWithShadow('RNG', col3X, statsYL + 4, '#c8a868', 'bold 8px monospace', 'start');
        drawTextWithShadow('' + sk.range, col3X + 22, statsYL + 4, '#f5e6c8', 'bold 10px monospace', 'start');

        // ? hangtag (landscape: 18x18, top-right)
        var tagWL = 18;
        var tagHL = 18;
        var tagXL = cx + cardW - tagWL - 4;
        var tagYL = cy + 4;
        ctx.fillStyle = '#fff8e8';
        ctx.fillRect(tagXL, tagYL, tagWL, tagHL);
        ctx.fillStyle = '#f0dfb8';
        ctx.fillRect(tagXL + 1, tagYL + tagHL - 3, tagWL - 2, 2);
        ctx.fillStyle = '#2a1810';
        ctx.fillRect(tagXL, tagYL, tagWL, 1);
        ctx.fillRect(tagXL, tagYL, 1, tagHL);
        ctx.fillRect(tagXL + tagWL - 1, tagYL, 1, tagHL);
        ctx.fillRect(tagXL, tagYL + tagHL - 1, tagWL, 1);
        ctx.fillStyle = '#2a1810';
        ctx.fillRect(tagXL + tagWL / 2 - 1, tagYL - 3, 2, 4);
        ctx.font = 'bold 13px monospace';
        ctx.fillStyle = '#2a1810';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText('?', tagXL + tagWL / 2, tagYL + tagHL / 2 + 1);
        ctx.textAlign = 'start';
        ctx.textBaseline = 'alphabetic';
        ui.shopInfoRects.push({ x: tagXL, y: tagYL, w: tagWL, h: tagHL, kind: k });

        // Armed-card highlight + Tap a spot pill
        if (ui.armedKind === k) {
          ctx.strokeStyle = '#fee761';
          ctx.lineWidth = 3;
          ctx.strokeRect(cx + 1, cy + 1, cardW - 2, cardH - 2);
          ctx.lineWidth = 1;
          var pillWL = 60;
          var pillHL = 12;
          var pillXL = cx + cardW / 2 - pillWL / 2;
          var pillYL = cy + cardH - 6;
          ctx.fillStyle = '#fee761';
          ctx.fillRect(pillXL, pillYL, pillWL, pillHL);
          ctx.fillStyle = '#2a1810';
          ctx.fillRect(pillXL, pillYL, pillWL, 1);
          ctx.fillRect(pillXL, pillYL + pillHL - 1, pillWL, 1);
          ctx.fillRect(pillXL, pillYL, 1, pillHL);
          ctx.fillRect(pillXL + pillWL - 1, pillYL, 1, pillHL);
          drawTextWithShadow('TAP A SPOT', pillXL + pillWL / 2, pillYL + pillHL / 2, '#2a1810', 'bold 8px monospace', 'center');
        }
      }

      ui.shopRects.push({ x: cx, y: cy, w: cardW, h: cardH, kind: k });
    }
```

The `for` loop body now branches on `portrait` to render either layout. Hit-test rects (`shopRects`, `shopInfoRects`) are populated identically in both branches. The landscape branch is the same code that exists today, lifted verbatim into the `else`.

- [ ] **Step 2: Verify**

Landscape: shop sidebar should look identical to today (same 4 cards, same hangtags, same TAP A SPOT pill).

Portrait: refresh in a portrait viewport. Bottom strip should now show 4 cards in a horizontal row, each ~115 px wide × 230 px tall. Each card has sprite (centered, larger), name, cost row, DMG/RATE inline, RNG-only stats card at the bottom, and the "?" hangtag.

Tap a card: should arm with the yellow border + "TAP A SPOT" pill.

Tap the "?": opens the info panel (will be mis-laid-out until Task 6).

Tap a placed cannon on the playfield: opens the detail panel (will be mis-laid-out until Task 5).

Gotcha check: `curl ... | grep -c '&#038;&#038;'` returns `0`.

- [ ] **Step 3: Commit**

```bash
git add patterns/demo-ant-buster.php
git commit -m "feat(ant-buster): Plan mobile-portrait Task 4 — shop list 4-card row strip"
```

---

## Task 5: Detail panel — two-column horizontal in portrait

**Files:**
- Modify: `patterns/demo-ant-buster.php`, `drawTowerPanel()` function (search for `function drawTowerPanel(c)`, near line 2090) and `drawTargetingRow()` (search for `function drawTargetingRow`, near line 2047)

The detail panel renders when a placed cannon is selected. In landscape it's a vertical stack in the right sidebar. In portrait it's a horizontal two-column layout in the bottom strip: left column has cannon stats + targeting row, right column has upgrade rows + sell button.

- [ ] **Step 1: Branch `drawTowerPanel(c)` on orientation**

Find `drawTowerPanel(c)` near line 2090. The function starts with `var sk = CANNON_KINDS[c.kind];`, then sets `var px = PLAYFIELD_W + 6; var pw = SHOP_W - 12; var py = 26;`. These coordinates assume the right sidebar.

Replace the function body's first ~10 lines (header, panel-coord setup, drawShopBackground call, back button) with:

```js
  function drawTowerPanel(c) {
    var sk = CANNON_KINDS[c.kind];
    drawShopBackground();

    var portrait = isPortrait();
    if (portrait) {
      drawTowerPanelPortrait(c, sk);
    } else {
      drawTowerPanelLandscape(c, sk);
    }
  }

  // Landscape: vertical stack in right sidebar (existing layout). Lifted from
  // the original drawTowerPanel body — no logic change.
  function drawTowerPanelLandscape(c, sk) {
    var px = PLAYFIELD_W + 6;
    var pw = SHOP_W - 12;
    var py = 26;

    // Back button (wooden chrome)
    var dbX = px + 16;
    var dbY = py + 2;
    var dbW = pw - 32;
    var dbH = 16;
    drawWoodenButton(dbX, dbY, dbW, dbH, 'wood');
    drawTextWithShadow('< BACK TO CANNONS', dbX + dbW / 2, dbY + dbH / 2, '#fee761', 'bold 9px monospace', 'center');
    ui.detailBackRect = { x: dbX, y: dbY, w: dbW, h: dbH };

    // Name (with shrink-then-truncate)
    var detailNameFit = fitFont(sk.name, pw - 16, 'bold __PX__px monospace', 14, 9);
    drawTextWithShadow(detailNameFit.text, px + 8, py + 32, '#fee761', detailNameFit.font, 'start');
    drawTextWithShadow(sk.family + ' family', px + 8, py + 48, '#c8a868', 'bold 8px monospace', 'start');

    // 4-stat row (DMG/RATE/RNG/KILLS)
    var sy = py + 58;
    var sh = 30;
    drawWoodenTag(px + 2, sy, pw - 4, sh);
    var col = (pw - 4) / 4;
    var killCount;
    if (c.kills) { killCount = c.kills; } else { killCount = 0; }
    var labels = ['DMG', 'RATE', 'RNG', 'KILLS'];
    var values = ['' + sk.damage, '' + sk.fireRate, '' + sk.range, '' + killCount];
    var valColors = ['#f5e6c8', '#f5e6c8', '#f5e6c8', '#fee761'];
    for (var i = 0; i < 4; i++) {
      var cx = px + 2 + col * i + col / 2;
      drawTextWithShadow(labels[i], cx, sy + 9, '#c8a868', 'bold 7px monospace', 'center');
      drawTextWithShadow(values[i], cx, sy + 22, valColors[i], 'bold 11px monospace', 'center');
    }

    // Targeting + upgrade rows + sell — lifted from the original body
    var afterTargetingY = drawTargetingRow(c, px + 8, py + 110);
    drawTowerPanelUpgrades(c, sk, px, pw, afterTargetingY + 6, /*upgRowH=*/ 42);
    drawTowerPanelSell(c, px, pw, HEIGHT - 30, /*sellH=*/ 24, /*variant=*/ 'red');
  }

  // Portrait: two-column horizontal layout in the bottom strip.
  function drawTowerPanelPortrait(c, sk) {
    var stripY = HEIGHT - STRIP_H;

    // Back button — full strip-width wooden chrome.
    var bbX = 16;
    var bbY = stripY + 4;
    var bbW = WIDTH - 32;
    var bbH = 16;
    drawWoodenButton(bbX, bbY, bbW, bbH, 'wood');
    drawTextWithShadow('< BACK TO CANNONS', bbX + bbW / 2, bbY + bbH / 2, '#fee761', 'bold 9px monospace', 'center');
    ui.detailBackRect = { x: bbX, y: bbY, w: bbW, h: bbH };

    // Two columns: left (stats + targeting), right (upgrades + sell).
    var colTop = stripY + 22;
    var colH = STRIP_H - 26;
    var leftX = 4;
    var leftW = 276;
    var rightX = 284;
    var rightW = WIDTH - rightX - 4;

    // === LEFT COLUMN: name, family, stats, targeting ===
    // Recessed background
    ctx.fillStyle = 'rgba(0, 0, 0, 0.18)';
    ctx.fillRect(leftX, colTop, leftW, colH);
    ctx.fillStyle = '#2a1810';
    ctx.fillRect(leftX, colTop, leftW, 1);

    // Name
    var detailNameFitP = fitFont(sk.name, leftW - 16, 'bold __PX__px monospace', 14, 9);
    drawTextWithShadow(detailNameFitP.text, leftX + 8, colTop + 14, '#fee761', detailNameFitP.font, 'start');
    drawTextWithShadow(sk.family + ' family', leftX + 8, colTop + 30, '#c8a868', 'bold 8px monospace', 'start');

    // 4-stat row in 4 even columns
    var statsY = colTop + 50;
    var statsH = 30;
    drawWoodenTag(leftX + 4, statsY, leftW - 8, statsH);
    var colW = (leftW - 8) / 4;
    var killCountP;
    if (c.kills) { killCountP = c.kills; } else { killCountP = 0; }
    var labelsP = ['DMG', 'RATE', 'RNG', 'KILLS'];
    var valuesP = ['' + sk.damage, '' + sk.fireRate, '' + sk.range, '' + killCountP];
    var valColorsP = ['#f5e6c8', '#f5e6c8', '#f5e6c8', '#fee761'];
    for (var iP = 0; iP < 4; iP++) {
      var cxP = leftX + 4 + colW * iP + colW / 2;
      drawTextWithShadow(labelsP[iP], cxP, statsY + 9, '#c8a868', 'bold 7px monospace', 'center');
      drawTextWithShadow(valuesP[iP], cxP, statsY + 22, valColorsP[iP], 'bold 11px monospace', 'center');
    }

    // Targeting row — start below the stats tag
    drawTargetingRow(c, leftX + 8, statsY + statsH + 14);

    // === RIGHT COLUMN: upgrade rows + sell ===
    var sellH = 22;
    var upgTop = colTop;
    var upgBottom = colTop + colH - sellH - 4;
    var upgArea = upgBottom - upgTop;
    drawTowerPanelUpgrades(c, sk, rightX, rightW, upgTop, /*upgRowH=*/ Math.floor((upgArea - 8) / 3));

    // Sell button at the bottom of the right column
    drawTowerPanelSell(c, rightX, rightW, upgBottom + 4, sellH, /*variant=*/ 'red');
  }
```

Now extract the upgrade-rows rendering and sell-button rendering from the original `drawTowerPanel` body into helper functions. Find the existing upgrade-rows loop (the big `for (var i2 = 0; i2 < upgrades.length; i2++)` block that renders each upgrade row with cost, stats, "?" hangtag, affordability dim, etc.) and move it into a new function:

```js
  function drawTowerPanelUpgrades(c, sk, px, pw, baseY, upgRowH) {
    var upgrades = sk.upgradesTo;
    if (!upgrades) { upgrades = []; }
    var hoveredLockedRect = null;
    ui.upgradeRects = [null, null, null];
    ui.detailUpgradeInfoRects = [null, null, null];
    for (var i2 = 0; i2 < upgrades.length; i2++) {
      var uk = upgrades[i2];
      var udef = CANNON_KINDS[uk];
      if (!udef) { continue; }
      var rh = upgRowH;
      var by = baseY + i2 * (rh + 5);
      var canAfford = (game.gold >= udef.cost);
      var isGod = (udef.tier === 'god');
      var isLocked = false;
      if (isGod) {
        if (!game.godTierUnlocked) { isLocked = true; }
      }

      // Plan 4 unlock pulse
      if (isGod) {
        var pulseAge = game.time - game.godTierUnlockPulseStart;
        if (pulseAge >= 0) {
          if (pulseAge < 0.5) {
            var pulseAlpha = 1 - (pulseAge / 0.5);
            ctx.save();
            ctx.globalAlpha = pulseAlpha;
            ctx.fillStyle = '#fee761';
            ctx.fillRect(px - 2, by - 4, pw + 4, rh + 8);
            ctx.restore();
          }
        }
      }

      ctx.save();
      if (isLocked) { ctx.globalAlpha = 0.5; }
      else if (!canAfford) { ctx.globalAlpha = 0.55; }

      if (isGod) {
        var grd = ctx.createLinearGradient(px + 2, by, px + 2, by + rh);
        grd.addColorStop(0, '#5a4a10');
        grd.addColorStop(1, '#3a2c00');
        ctx.fillStyle = grd;
        ctx.fillRect(px + 2, by, pw - 4, rh);
        ctx.fillStyle = '#2a1810';
        ctx.fillRect(px + 2, by, pw - 4, 2);
        ctx.fillRect(px + 2, by, 2, rh);
        ctx.fillRect(px + pw - 4, by, 2, rh);
        ctx.fillRect(px + 2, by + rh - 2, pw - 4, 2);
        ctx.fillStyle = '#c0a040';
        ctx.fillRect(px + 4, by + rh - 4, pw - 8, 2);
      } else {
        drawWoodenButton(px + 2, by, pw - 4, rh, 'wood');
      }

      // Name (truncated to fit before the coin/cost area)
      ctx.font = 'bold 10px monospace';
      var fittedUpgName = truncateToWidth(udef.name, pw - 70);
      drawTextWithShadow(fittedUpgName, px + 10, by + 11, '#fee761', 'bold 10px monospace', 'start');
      ctx.drawImage(game.sprites.coin, px + pw - 56, by + 6);
      var costColor;
      if (isLocked) { costColor = '#888'; }
      else if (canAfford) { costColor = '#fee761'; }
      else { costColor = '#ff7070'; }
      drawTextWithShadow('' + udef.cost, px + pw - 26, by + 11, costColor, 'bold 10px monospace', 'end');
      drawTextWithShadow('DMG ' + udef.damage + '  RATE ' + udef.fireRate + '  RNG ' + udef.range, px + 10, by + 28, '#f5e6c8', 'bold 8px monospace', 'start');

      // ? hangtag
      var ihtW = 16;
      var ihtH = 16;
      var ihtX = px + pw - ihtW - 6;
      var ihtY = by + 4;
      ctx.fillStyle = '#fff8e8';
      ctx.fillRect(ihtX, ihtY, ihtW, ihtH);
      ctx.fillStyle = '#f0dfb8';
      ctx.fillRect(ihtX + 1, ihtY + ihtH - 3, ihtW - 2, 2);
      ctx.fillStyle = '#2a1810';
      ctx.fillRect(ihtX, ihtY, ihtW, 1);
      ctx.fillRect(ihtX, ihtY, 1, ihtH);
      ctx.fillRect(ihtX + ihtW - 1, ihtY, 1, ihtH);
      ctx.fillRect(ihtX, ihtY + ihtH - 1, ihtW, 1);
      ctx.fillStyle = '#2a1810';
      ctx.fillRect(ihtX + ihtW / 2 - 1, ihtY - 3, 2, 4);
      ctx.font = 'bold 11px monospace';
      ctx.fillStyle = '#2a1810';
      ctx.textAlign = 'center';
      ctx.textBaseline = 'middle';
      ctx.fillText('?', ihtX + ihtW / 2, ihtY + ihtH / 2 + 1);
      ctx.textAlign = 'start';
      ctx.textBaseline = 'alphabetic';

      ctx.restore();

      ui.upgradeRects[i2] = { x: px + 2, y: by, w: pw - 4, h: rh, toKind: uk, locked: isLocked };
      ui.detailUpgradeInfoRects[i2] = { x: ihtX, y: ihtY, w: ihtW, h: ihtH, kind: uk };

      if (isLocked) {
        if (ui.mouseX >= px + 2) {
          if (ui.mouseX <= px + pw - 2) {
            if (ui.mouseY >= by) {
              if (ui.mouseY <= by + rh) {
                hoveredLockedRect = { x: px + 2, y: by, w: pw - 4, h: rh };
              }
            }
          }
        }
      }
    }

    // Locked god-tier tooltip
    if (hoveredLockedRect) {
      var tipX = hoveredLockedRect.x;
      var tipY = hoveredLockedRect.y - 18;
      drawWoodenTag(tipX, tipY, hoveredLockedRect.w, 16);
      drawTextWithShadow('Unlocks at wave 15', tipX + hoveredLockedRect.w / 2, tipY + 8, '#fee761', 'bold 8px monospace', 'center');
    }
  }
```

And the sell button:

```js
  function drawTowerPanelSell(c, px, pw, sbY, sbH, variant) {
    var refund = sellRefund(c);
    var sbW = pw - 4;
    var sbX = px + 2;
    drawWoodenButton(sbX, sbY, sbW, sbH, variant);
    drawTextWithShadow('Sell · +' + refund, sbX + sbW / 2, sbY + sbH / 2, '#fee761', 'bold 10px monospace', 'center');
    ui.sellRect = { x: sbX, y: sbY, w: sbW, h: sbH };
  }
```

Both helpers are placed AFTER `drawTowerPanelPortrait` (before the existing `drawTargetingRow` is fine). They're called from both `drawTowerPanelLandscape` and `drawTowerPanelPortrait`. The original loop body is no longer needed inline — delete the existing upgrade-rows loop and sell-button code from the body of `drawTowerPanel` (the landscape and portrait sub-functions now own that work).

- [ ] **Step 2: Verify**

Landscape: detail panel should look identical to today. Tap a placed cannon, see the same vertical stack with name, family, 4-stat row, targeting C/F/L/S/W, up to 3 upgrade rows, red sell button at bottom.

Portrait: tap a placed cannon. Strip should now show the back button at the top, then a two-column horizontal layout: left side has stats + targeting, right side has upgrade rows stacked + red sell at the bottom.

Try targeting modes (C/F/L/S/W tap) — should change the cannon's target priority and show the colored underline.

Try buying an upgrade — should debit gold and update the cannon kind.

Try selling — should refund gold and clear the panel.

Tap "< BACK TO CANNONS" — should return to the shop list.

Gotcha check: `curl ... | grep -c '&#038;&#038;'` returns `0`.

- [ ] **Step 3: Commit**

```bash
git add patterns/demo-ant-buster.php
git commit -m "feat(ant-buster): Plan mobile-portrait Task 5 — detail panel two-column"
```

---

## Task 6: Info panel — two-column horizontal cream in portrait

**Files:**
- Modify: `patterns/demo-ant-buster.php`, `drawShopInfoPanel()` function (search for `function drawShopInfoPanel()`, near line 1595)

Same structural split as Task 5: branch on `isPortrait()`, the landscape branch is the existing function body lifted verbatim, the portrait branch is a new two-column horizontal layout (cream-paper aesthetic, with the FIELD GUIDE eyebrow and contextual back button preserved).

- [ ] **Step 1: Branch the function**

Replace `drawShopInfoPanel()` with:

```js
  function drawShopInfoPanel() {
    var k = ui.shopInfoKind;
    if (!k) { ui.shopMode = 'list'; return; }
    var sk = CANNON_KINDS[k];
    if (!sk) { ui.shopMode = 'list'; return; }

    if (isPortrait()) {
      drawShopInfoPanelPortrait(sk);
    } else {
      drawShopInfoPanelLandscape(sk);
    }
  }

  function drawShopInfoPanelLandscape(sk) {
    var px = PLAYFIELD_W + 6;
    var pw = SHOP_W - 12;
    var py = 26;

    // FIELD GUIDE eyebrow + back button
    drawTextWithShadow('FIELD GUIDE', px + pw / 2, py, '#c8a868', 'bold 8px monospace', 'center');
    var bbX = px + 16;
    var bbY = py + 6;
    var bbW = pw - 32;
    var bbH = 16;
    drawWoodenButton(bbX, bbY, bbW, bbH, 'wood');
    ctx.font = 'bold 9px monospace';
    var backLabel;
    if (ui.shopInfoFrom === 'detail') {
      if (ui.selectedCannon) {
        var srcName = CANNON_KINDS[ui.selectedCannon.kind].name.toUpperCase();
        var prefixW = ctx.measureText('< BACK TO ').width;
        var nameMaxW = (bbW - 8) - prefixW;
        backLabel = '< BACK TO ' + truncateToWidth(srcName, nameMaxW);
      } else {
        backLabel = '< BACK TO CANNONS';
      }
    } else {
      backLabel = '< BACK TO CANNONS';
    }
    drawTextWithShadow(backLabel, bbX + bbW / 2, bbY + bbH / 2, '#fee761', 'bold 9px monospace', 'center');
    ui.shopInfoBackRect = { x: bbX, y: bbY, w: bbW, h: bbH };

    // Name
    var infoNameFit = fitFont(sk.name, pw - 16, 'bold __PX__px monospace', 14, 9);
    drawTextWithShadow(infoNameFit.text, px + 8, py + 38, '#fee761', infoNameFit.font, 'start');
    drawTextWithShadow(sk.family + ' family', px + 8, py + 54, '#c8a868', 'bold 8px monospace', 'start');

    // 3-stat row
    var sy = py + 64;
    var sh = 30;
    drawWoodenTag(px + 2, sy, pw - 4, sh);
    var col = (pw - 4) / 3;
    var col1 = px + 2 + col * 0 + col / 2;
    var col2 = px + 2 + col * 1 + col / 2;
    var col3 = px + 2 + col * 2 + col / 2;
    drawTextWithShadow('DMG', col1, sy + 9, '#c8a868', 'bold 7px monospace', 'center');
    drawTextWithShadow('' + sk.damage, col1, sy + 22, '#f5e6c8', 'bold 11px monospace', 'center');
    drawTextWithShadow('RATE', col2, sy + 9, '#c8a868', 'bold 7px monospace', 'center');
    drawTextWithShadow('' + sk.fireRate, col2, sy + 22, '#f5e6c8', 'bold 11px monospace', 'center');
    drawTextWithShadow('RNG', col3, sy + 9, '#c8a868', 'bold 7px monospace', 'center');
    drawTextWithShadow('' + sk.range, col3, sy + 22, '#f5e6c8', 'bold 11px monospace', 'center');

    // Description
    var desc = sk.description;
    if (!desc) {
      var tierLabel = sk.tier;
      if (!tierLabel) { tierLabel = 'base'; }
      desc = sk.family + ' ' + tierLabel;
    }
    var dy = py + 102;
    var dh = 56;
    var dgrd = ctx.createLinearGradient(px + 2, dy, px + 2, dy + dh);
    dgrd.addColorStop(0, '#fff8e8');
    dgrd.addColorStop(1, '#f0dfb8');
    ctx.fillStyle = dgrd;
    ctx.fillRect(px + 2, dy, pw - 4, dh);
    ctx.fillStyle = '#2a1810';
    ctx.fillRect(px + 2, dy, pw - 4, 2);
    ctx.fillRect(px + 2, dy, 2, dh);
    ctx.fillRect(px + pw - 4, dy, 2, dh);
    ctx.fillRect(px + 2, dy + dh - 2, pw - 4, 2);
    drawWrappedTextDark(desc, px + 12, dy + 10, pw - 24, '#3a2210', 'bold 9px monospace', 14);

    // EVOLVES TO
    drawTextWithShadow('EVOLVES TO', px + 8, py + 168, '#c8a868', 'bold 8px monospace', 'start');
    drawShopInfoEvolveCards(sk, px + 2, pw - 4, py + 180, /*rh=*/ 46);
  }

  function drawShopInfoPanelPortrait(sk) {
    var stripY = HEIGHT - STRIP_H;

    // Back button (full strip-width wooden chrome) + FIELD GUIDE eyebrow
    var bbX = 16;
    var bbY = stripY + 4;
    var bbW = WIDTH - 32;
    var bbH = 16;
    drawWoodenButton(bbX, bbY, bbW, bbH, 'wood');
    ctx.font = 'bold 9px monospace';
    var backLabel;
    if (ui.shopInfoFrom === 'detail') {
      if (ui.selectedCannon) {
        var srcName = CANNON_KINDS[ui.selectedCannon.kind].name.toUpperCase();
        var prefixW = ctx.measureText('< BACK TO ').width;
        var nameMaxW = (bbW - 8) - prefixW;
        backLabel = '< BACK TO ' + truncateToWidth(srcName, nameMaxW);
      } else {
        backLabel = '< BACK TO CANNONS';
      }
    } else {
      backLabel = '< BACK TO CANNONS';
    }
    drawTextWithShadow(backLabel, bbX + bbW / 2, bbY + bbH / 2, '#fee761', 'bold 9px monospace', 'center');
    ui.shopInfoBackRect = { x: bbX, y: bbY, w: bbW, h: bbH };

    drawTextWithShadow('FIELD GUIDE', WIDTH / 2, stripY + 28, '#c8a868', 'bold 8px monospace', 'center');

    // Two columns
    var colTop = stripY + 38;
    var colH = STRIP_H - 42;
    var leftX = 4;
    var leftW = 276;
    var rightX = 284;
    var rightW = WIDTH - rightX - 4;

    // === LEFT: cream paper with name + family + stats + description ===
    var lgrd = ctx.createLinearGradient(leftX, colTop, leftX, colTop + colH);
    lgrd.addColorStop(0, '#fff8e8');
    lgrd.addColorStop(1, '#f0dfb8');
    ctx.fillStyle = lgrd;
    ctx.fillRect(leftX, colTop, leftW, colH);
    ctx.fillStyle = '#2a1810';
    ctx.fillRect(leftX, colTop, leftW, 2);
    ctx.fillRect(leftX, colTop, 2, colH);
    ctx.fillRect(leftX + leftW - 2, colTop, 2, colH);
    ctx.fillRect(leftX, colTop + colH - 2, leftW, 2);

    // Name (dark on cream)
    ctx.font = 'bold 14px monospace';
    var fittedInfoName = truncateToWidth(sk.name, leftW - 16);
    ctx.fillStyle = '#3a2210';
    ctx.textAlign = 'start';
    ctx.textBaseline = 'middle';
    ctx.fillText(fittedInfoName, leftX + 8, colTop + 14);
    ctx.font = 'bold 8px monospace';
    ctx.fillStyle = '#8a5a30';
    ctx.fillText(sk.family + ' family', leftX + 8, colTop + 28);

    // 3-stat row inside the cream area
    var statsY = colTop + 42;
    var colW = (leftW - 16) / 3;
    var labels3 = ['DMG', 'RATE', 'RNG'];
    var values3 = ['' + sk.damage, '' + sk.fireRate, '' + sk.range];
    for (var sI = 0; sI < 3; sI++) {
      var sCx = leftX + 8 + colW * sI + colW / 2;
      ctx.font = 'bold 7px monospace';
      ctx.fillStyle = '#8a5a30';
      ctx.textAlign = 'center';
      ctx.fillText(labels3[sI], sCx, statsY);
      ctx.font = 'bold 11px monospace';
      ctx.fillStyle = '#3a2210';
      ctx.fillText(values3[sI], sCx, statsY + 14);
    }
    ctx.textAlign = 'start';
    ctx.textBaseline = 'alphabetic';

    // Description
    var descP = sk.description;
    if (!descP) {
      var tierLabelP = sk.tier;
      if (!tierLabelP) { tierLabelP = 'base'; }
      descP = sk.family + ' ' + tierLabelP;
    }
    drawWrappedTextDark(descP, leftX + 8, colTop + 70, leftW - 16, '#3a2210', 'bold 9px monospace', 13);

    // === RIGHT: EVOLVES TO header + cream evolution cards ===
    drawTextWithShadow('EVOLVES TO', rightX + rightW / 2, colTop + 6, '#fee761', 'bold 8px monospace', 'center');
    drawShopInfoEvolveCards(sk, rightX, rightW, colTop + 16, /*rh=*/ Math.floor((colH - 22) / 3) - 4);
  }
```

The evolution-cards rendering is shared. Extract it into a helper:

```js
  function drawShopInfoEvolveCards(sk, px, pw, baseY, rh) {
    var upgrades = sk.upgradesTo;
    if (!upgrades) { upgrades = []; }
    var rowY = baseY;
    for (var i = 0; i < upgrades.length; i++) {
      var uk = upgrades[i];
      var udef = CANNON_KINDS[uk];
      if (!udef) { continue; }

      // Cream paper card body
      var rgrd = ctx.createLinearGradient(px + 2, rowY, px + 2, rowY + rh);
      rgrd.addColorStop(0, '#fff8e8');
      rgrd.addColorStop(1, '#f0dfb8');
      ctx.fillStyle = rgrd;
      ctx.fillRect(px + 2, rowY, pw - 4, rh);
      ctx.fillStyle = '#2a1810';
      ctx.fillRect(px + 2, rowY, pw - 4, 2);
      ctx.fillRect(px + 2, rowY, 2, rh);
      ctx.fillRect(px + pw - 4, rowY, 2, rh);
      ctx.fillRect(px + 2, rowY + rh - 2, pw - 4, 2);

      // Top line: name + tier badge
      ctx.font = 'bold 10px monospace';
      var fittedInfoUpgName = truncateToWidth(udef.name, pw - 42);
      ctx.fillStyle = '#3a2210';
      ctx.textAlign = 'start';
      ctx.textBaseline = 'middle';
      ctx.fillText(fittedInfoUpgName, px + 12, rowY + 13);

      var tierLabel2 = udef.tier;
      if (!tierLabel2) { tierLabel2 = 'mid'; }
      ctx.font = 'bold 7px monospace';
      ctx.fillStyle = '#8a5a30';
      ctx.textAlign = 'end';
      ctx.fillText(tierLabel2.toUpperCase(), px + pw - 8, rowY + 13);

      // Stats line
      ctx.font = 'bold 8px monospace';
      ctx.fillStyle = '#5a3a20';
      ctx.textAlign = 'start';
      ctx.fillText('DMG ' + udef.damage + '  RATE ' + udef.fireRate + '  RNG ' + udef.range, px + 10, rowY + 27);

      // Description line
      var udesc = udef.description;
      if (!udesc) {
        var ut = udef.tier;
        if (!ut) { ut = 'mid'; }
        udesc = udef.family + ' ' + ut;
      }
      ctx.font = 'bold 7px monospace';
      ctx.fillStyle = '#7a5a40';
      ctx.fillText(truncate(udesc, 32), px + 10, rowY + 38);

      ctx.textAlign = 'start';
      ctx.textBaseline = 'alphabetic';

      rowY = rowY + rh + 5;
    }
  }
```

- [ ] **Step 2: Verify**

Landscape: info panel should look identical to today.

Portrait: tap a "?" hangtag on a shop card. Strip should show: back button at top, FIELD GUIDE eyebrow below it, then a two-column split. Left column = cream paper with name, family, 3-stat row, description. Right column = EVOLVES TO header with up to 3 evolution cards.

Tap a placed cannon → "?" on an upgrade row. Same info panel; back button now reads `< BACK TO HOWITZER` (or whatever the source kind is). Tapping it returns to the cannon's detail panel.

Gotcha check: `curl ... | grep -c '&#038;&#038;'` returns `0`.

- [ ] **Step 3: Commit**

```bash
git add patterns/demo-ant-buster.php
git commit -m "feat(ant-buster): Plan mobile-portrait Task 6 — info panel two-column cream"
```

---

## Task 7: `IDLE_ANT_LAYOUT_PORTRAIT` + title-cake position table

**Files:**
- Modify: `patterns/demo-ant-buster.php`, `IDLE_ANT_LAYOUT` constant (near lines 99-115) and `drawIdleOverlay()` function (search for `function drawIdleOverlay()`, near line 3077)

The idle splash today reads from `IDLE_ANT_LAYOUT` — 15 ants positioned for a 640×360 canvas. In portrait we need a separate 480×600 layout. The title cake also moves.

- [ ] **Step 1: Add `IDLE_ANT_LAYOUT_PORTRAIT`**

Find `var IDLE_ANT_LAYOUT = [` (near line 99). After the closing `];` of that array, add:

```js
  // Portrait variant: 15 ants distributed across a 480x600 canvas. Roughly
  // 4 in the HUD area, 7 across the playfield, 4 in the strip area.
  var IDLE_ANT_LAYOUT_PORTRAIT = [
    { x:  30, y:  10, size: 2, rot:  0.6 },
    { x: 360, y:  14, size: 1, rot: -0.35 },
    { x: 110, y:  60, size: 0, rot:  1.92 },
    { x: 420, y:  90, size: 1, rot:  1.31 },
    { x:  50, y: 130, size: 1, rot: -0.87 },
    { x: 430, y: 180, size: 2, rot: -1.92 },
    { x:  40, y: 230, size: 0, rot:  0.35 },
    { x: 380, y: 260, size: 1, rot:  2.79 },
    { x: 100, y: 310, size: 1, rot: -1.22 },
    { x: 270, y: 340, size: 2, rot:  0.7 },
    { x:  60, y: 410, size: 0, rot: -0.5 },
    { x: 410, y: 430, size: 1, rot:  1.0 },
    { x: 200, y: 490, size: 0, rot:  2.1 },
    { x: 100, y: 530, size: 1, rot: -1.7 },
    { x: 380, y: 560, size: 2, rot:  0.3 }
  ];
```

- [ ] **Step 2: Branch `drawIdleOverlay()` to use the portrait layout**

Find `drawIdleOverlay()` (near line 3077). Find where it loops over `IDLE_ANT_LAYOUT`. Replace each direct reference to `IDLE_ANT_LAYOUT` with a layout-picker variable:

```js
  function drawIdleOverlay() {
    // ... existing dim / wash code ...

    var layoutAnts;
    if (isPortrait()) { layoutAnts = IDLE_ANT_LAYOUT_PORTRAIT; }
    else { layoutAnts = IDLE_ANT_LAYOUT; }

    for (var i = 0; i < layoutAnts.length; i++) {
      var a = layoutAnts[i];
      // ... existing per-ant render code ...
    }

    // ... existing title-cake + button code ...
  }
```

Then find the title-cake position. In landscape it's roughly canvas-centered. Find the line that sets the cake's draw position (search for `game.sprites.titleCake` inside `drawIdleOverlay`). Replace its x/y assignment with a portrait branch:

```js
    var cakeX; var cakeY;
    if (isPortrait()) {
      cakeX = WIDTH / 2 - 96;   // centered (cake sprite is 192 wide)
      cakeY = HUD_H + 24;        // near the top of the playfield
    } else {
      cakeX = WIDTH / 2 - 96;
      cakeY = HEIGHT / 2 - 80;
    }
    ctx.drawImage(game.sprites.titleCake, cakeX, cakeY);
```

The "Tap to play" button position also needs to follow the cake. Find the button's `var btnY` assignment in `drawIdleOverlay` and update it:

```js
    var btnY;
    if (isPortrait()) { btnY = HUD_H + 220; }  // below the cake
    else { btnY = HEIGHT / 2 + 70; }
```

If `drawIdleOverlay` renders the title text overlapping the cake bottom, also adjust the title's y position the same way. Read the existing code for the exact variable names — they may differ from `btnY`.

- [ ] **Step 3: Verify**

Landscape: idle splash should look identical to today (cake centered, ants scattered across the 640x360 canvas).

Portrait: load the demo at portrait viewport while in idle state (refresh while not playing). Cake should appear near the top of the playfield, "Tap to play" button below it, 15 ants scattered across the full canvas (HUD + playfield + strip area).

Gotcha check: `curl ... | grep -c '&#038;&#038;'` returns `0`.

- [ ] **Step 4: Commit**

```bash
git add patterns/demo-ant-buster.php
git commit -m "feat(ant-buster): Plan mobile-portrait Task 7 — idle splash portrait variant"
```

---

## Task 8: Splash overlays anchored to playfield rect

**Files:**
- Modify: `patterns/demo-ant-buster.php`, `drawPauseOverlay()` (near line 3178), `drawLoseOverlay()` (near line 3227), `drawWinOverlay()` (near line 3259), `drawBanner()` (near line 3031), `drawTutorialHint()` (near line 3142)

The splash overlays paint a dimmed rectangle. In landscape they cover the playfield and leave the right sidebar live. In portrait they should cover the playfield and leave the strip live. Both reduce to "the overlay rect is `(0, HUD_H, PLAYFIELD_W, PLAYFIELD_H)`" — already the case in landscape, just needs to use `PLAYFIELD_H` instead of `HEIGHT - HUD_H`.

- [ ] **Step 1: Update each overlay's full-rect fill**

For `drawPauseOverlay`, `drawLoseOverlay`, `drawWinOverlay`: find the dimming `ctx.fillRect(0, 0, WIDTH, HEIGHT)` (or similar full-canvas rect). Replace with:

```js
    ctx.fillRect(0, HUD_H, PLAYFIELD_W, PLAYFIELD_H);
```

If the existing line uses `HEIGHT - HUD_H` instead of `PLAYFIELD_H`, it produced the same result in landscape (336) but would over-fill in portrait (576). Replacing with `PLAYFIELD_H` makes both orientations correct.

Each overlay also has internal centering math (e.g., button positions). Find each `WIDTH / 2` reference inside the overlay function and replace with `PLAYFIELD_W / 2`. Find each `HEIGHT / 2` reference and replace with `HUD_H + PLAYFIELD_H / 2`. The overlay text and buttons should center on the playfield, not on the full canvas.

- [ ] **Step 2: Update `drawBanner()` and `drawTutorialHint()` similarly**

`drawBanner()` (the wave-start banner) and `drawTutorialHint()` (the "Drag a cannon" sticky note) both render at top-of-playfield positions. Find their y-coordinates and `WIDTH / 2` references. Replace with playfield-centered positions:

- Banner anchor: `(PLAYFIELD_W / 2, HUD_H + 24)` instead of `(WIDTH / 2, 48)`
- Tutorial hint anchor: similar — find the existing position and rebase off `PLAYFIELD_W` instead of `WIDTH`

- [ ] **Step 3: Verify**

Landscape: pause/win/lose overlays should look identical to today. Banner appears centered in the playfield at wave start. Tutorial hint appears at the top.

Portrait: pause the game. The dimming should cover only the 480x336 playfield area, leaving the strip (with shop cards) clearly visible underneath. The "PAUSED" text and Restart Run button should be centered in the playfield, not in the full canvas.

Same check for win and lose: only the playfield dims; the strip stays bright.

Banner at wave start should appear centered in the playfield in both orientations.

Tutorial hint should sit at the top of the playfield in both orientations.

Gotcha check: `curl ... | grep -c '&#038;&#038;'` returns `0`.

- [ ] **Step 4: Commit**

```bash
git add patterns/demo-ant-buster.php
git commit -m "feat(ant-buster): Plan mobile-portrait Task 8 — overlays bound to playfield rect"
```

---

## Task 9: `inPlayfield()` helper + pointerdown call sites

**Files:**
- Modify: `patterns/demo-ant-buster.php`, helper at the top of the input handlers, and the four call sites (lines 6471, 6521, 6551, 6646 — search for `p.x < PLAYFIELD_W` and `p.x >= PLAYFIELD_W`)

Today the input handlers use `p.x < PLAYFIELD_W` to mean "is the tap in the playfield?". In landscape that's enough — the right side is the shop. In portrait the playfield occupies `x < PLAYFIELD_W AND y < HUD_H + PLAYFIELD_H`, with the strip below.

- [ ] **Step 1: Add `inPlayfield(p)` helper**

Find the `canvasToGame(evt)` helper (near line 6419). Immediately AFTER it, add:

```js
  // Returns true when the point is inside the playfield rect (between the HUD
  // and the bottom of the playable area, and within the playfield width).
  // Centralizes the orientation-aware check so the pointer handler doesn't
  // need to special-case landscape vs portrait.
  function inPlayfield(p) {
    if (p.x < 0) { return false; }
    if (p.x >= PLAYFIELD_W) { return false; }
    if (p.y < HUD_H) { return false; }
    if (p.y >= HUD_H + PLAYFIELD_H) { return false; }
    return true;
  }
```

- [ ] **Step 2: Replace each `p.x < PLAYFIELD_W` call site**

There are 2 sites in `pointerdown` and a few elsewhere. For each occurrence of `p.x < PLAYFIELD_W` inside the playfield-tap handlers, replace with `inPlayfield(p)`.

Site 1 (search for the first `if (p.x < PLAYFIELD_W) {` in `pointerdown` — lines around 6471, in the armed-state block):

```js
      if (p.x < PLAYFIELD_W) {
        if (p.y >= HUD_H) {
          if (!game.paused) {
            // Note: do not call placeCannon here. ...
            canvas.classList.add('ab-dragging');
            return;
          }
        }
      }
```

becomes:

```js
      if (inPlayfield(p)) {
        if (!game.paused) {
          canvas.classList.add('ab-dragging');
          return;
        }
      }
```

The nested `if (p.y >= HUD_H)` is now subsumed by `inPlayfield`.

Site 2 (the second occurrence, around 6521, in the cannon-select block):

```js
      if (p.x < PLAYFIELD_W) {
        // If info panel is open, close it on any playfield tap.
        if (ui.shopMode === 'info') {
          ui.shopMode = 'list';
          ui.shopInfoKind = null;
          ui.shopInfoFrom = null;
        }
        var hit = cannonAt(p.x, p.y);
        if (hit) {
          ui.selectedCannon = hit;
          return;
        }
        if (ui.selectedCannon) {
          ui.selectedCannon = null;
          return;
        }
      }
```

becomes:

```js
      if (inPlayfield(p)) {
        if (ui.shopMode === 'info') {
          ui.shopMode = 'list';
          ui.shopInfoKind = null;
          ui.shopInfoFrom = null;
        }
        var hit = cannonAt(p.x, p.y);
        if (hit) {
          ui.selectedCannon = hit;
          return;
        }
        if (ui.selectedCannon) {
          ui.selectedCannon = null;
          return;
        }
      }
```

Sites 3 and 4 use `p.x >= PLAYFIELD_W` to mean "is the tap in the right column?". In portrait the equivalent is "is the tap in the strip area?". Replace those two with a new helper or inline check:

```js
      if (ui.selectedCannon) {
       if (ui.shopMode === 'list') {
        if (p.x >= PLAYFIELD_W) {
          // ...
        }
       }
      }
```

becomes:

```js
      if (ui.selectedCannon) {
       if (ui.shopMode === 'list') {
        if (inSidePanel(p)) {
          // ...
        }
       }
      }
```

Add `inSidePanel(p)` next to `inPlayfield(p)`:

```js
  // Returns true when the point is inside the shop sidebar (landscape) or the
  // bottom strip (portrait). The complement of inPlayfield, basically — but
  // tested separately so HUD-area taps don't match.
  function inSidePanel(p) {
    if (p.y < HUD_H) { return false; }
    if (isPortrait()) {
      // Portrait: strip lives BELOW the playfield.
      if (p.y < HUD_H + PLAYFIELD_H) { return false; }
      if (p.y >= HEIGHT) { return false; }
      return true;
    }
    // Landscape: sidebar lives to the RIGHT of the playfield.
    if (p.x < PLAYFIELD_W) { return false; }
    if (p.x >= WIDTH) { return false; }
    return true;
  }
```

Find both occurrences of `p.x >= PLAYFIELD_W` inside the side-panel hit-test cascades and replace with `inSidePanel(p)`.

- [ ] **Step 3: Verify**

Landscape: existing tap behavior unchanged. Test:
- Tap on a placed cannon → selects (detail panel opens)
- Tap on empty playfield (with no selection) → no-op
- Tap on shop card → arms
- Tap "?" → info panel
- Tap detail-panel back button → returns to shop list
- Tap detail-panel sell → sells
- Tap targeting buttons → switch mode
- Tap upgrade rows → buy

Portrait: same matrix:
- Tap on a placed cannon (which is in y < 360 region) → selects, detail panel opens in strip
- Tap on empty playfield → no-op
- Tap on shop card (in strip, y >= 360) → arms
- Tap "?" → info panel in strip
- Tap detail-panel back button (in strip) → returns to shop list
- Tap targeting / upgrade / sell → all work
- Tap in strip area (which is `y >= HUD_H + PLAYFIELD_H`) doesn't trigger playfield logic

Edge case: tap in HUD strip (y < HUD_H) → only triggers HUD widgets (pause/speed/Send-Next). Already handled by the HUD hit-test at the top of pointerdown.

Gotcha check: `curl ... | grep -c '&#038;&#038;'` returns `0`.

- [ ] **Step 4: Commit**

```bash
git add patterns/demo-ant-buster.php
git commit -m "feat(ant-buster): Plan mobile-portrait Task 9 — inPlayfield/inSidePanel helpers"
```

---

## Task 10: Theme version bump + final verification sweep

**Files:**
- Modify: `style.css` (Version line in the file header)
- No-modify: `patterns/demo-ant-buster.php` (just verify)

The theme version bump invalidates WordPress's pattern cache so the updated demo loads. Final sweep verifies the full 18-item portrait checklist plus the 17-item visual coherence checklist.

- [ ] **Step 1: Bump theme version**

Open `style.css`. The header looks like:

```css
/*
Theme Name: Dario Bimbi
...
Version: 0.15.0
...
*/
```

Change `Version: 0.15.0` to `Version: 0.16.0`.

- [ ] **Step 2: Run the portrait verification checklist**

Refresh `http://localhost/dariobimbi.com/demos/ant-buster/` in a portrait viewport (DevTools device toolbar, iPhone 14 Pro 393x852, or actual phone).

Walk through all 18 items in the spec's "Verification checklist" section. Each item must pass; mark each `- [x]` as you confirm:

- [ ] 1. 480-wide × 600-tall canvas in portrait. HUD top, playfield middle with cake and idle ants, 4-card shop strip at bottom. No empty bands.
- [ ] 2. 4 shop cards visible, ~90 screen-px wide on a 375-wide phone. Sprite, name, cost, DMG/RATE line readable.
- [ ] 3. Tapping a shop card arms it (yellow border + TAP A SPOT pill).
- [ ] 4. Tapping playfield with armed card places the cannon. Ghost preview under finger; commits on lift.
- [ ] 5. Tapping a placed cannon opens the detail panel inside the strip.
- [ ] 6. Detail panel left column shows name, family, 4-stat row (DMG/RATE/RNG/KILLS), targeting row (5 buttons). Targeting toggle works; active button shows colored underline.
- [ ] 7. Detail panel right column shows up to 3 upgrade rows with cost + DMG/RATE/RNG. Affordable full opacity, unaffordable dimmed with red cost. God-tier rows dimmed before wave 15.
- [ ] 8. Sell · +N red wooden button refunds correctly.
- [ ] 9. Tapping "< BACK TO CANNONS" at the top of the detail panel returns to the 4-card list.
- [ ] 10. Tapping "?" hangtag on a shop card opens the info panel.
- [ ] 11. Info panel left column shows name, family, 3-stat row, wrapped description on cream paper.
- [ ] 12. Info panel right column shows EVOLVES TO stack of cream cards (up to 3).
- [ ] 13. Tapping "?" on a detail-panel upgrade row opens info for that upgrade kind. Back button reads "< BACK TO ‹parent kind›" and returns to parent's detail panel.
- [ ] 14. HUD reads correctly: gold/cake/wave/prep tags left; pause/speed/Send-Next right; widgets tappable.
- [ ] 15. Pause/win/lose splash overlays paint across playfield only, strip visible underneath.
- [ ] 16. Idle splash shows title cake at playfield top with title overlapping its bottom, plus 15 ants scattered.
- [ ] 17. Rotating phone from portrait→landscape (or vice versa) flips layout in place. Game state preserved.
- [ ] 18. Same operations on desktop landscape (1200-wide window) render byte-identical to today.

- [ ] **Step 3: Run the visual coherence checklist (regression check on landscape)**

In a landscape browser at 1200px wide, run the 17-item checklist from `docs/superpowers/specs/2026-05-09-ant-buster-visual-coherence-design.md`. Every item must still pass.

- [ ] **Step 4: Run inline-script gotcha sweep one final time**

```bash
curl -s "http://localhost/dariobimbi.com/demos/ant-buster/" | grep -c '&#038;&#038;'
```

Expected: `0`.

- [ ] **Step 5: Commit**

```bash
git add style.css
git commit -m "chore(ant-buster): bump theme to 0.16.0 for mobile portrait pass"
```

---

## Out-of-scope reminders

- No new tap-target overhaul on HUD widgets (pause/speed/Send-Next stay 22×20)
- No iOS in-game vibration / haptics
- No "rotate for best experience" hint
- No two-finger gestures
- No gameplay changes (cake position, wave paths, ant kinds, combat math all unchanged)
- No new sprites
