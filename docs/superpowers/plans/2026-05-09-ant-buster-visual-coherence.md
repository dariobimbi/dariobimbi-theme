# Ant Buster Visual Coherence Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the placeholder HUD/shop/splash chrome in `patterns/demo-ant-buster.php` with a coherent picnic-themed visual language: wooden plaque HUD, hero-cake title screen, distinct emotional weight on win/lose splashes, wooden shop cards with family-color strips, info-panel drill-down, restyled detail panel.

**Architecture:** Everything renders into the existing 640x360 canvas using its CSS-as-a-unit scaling. New helper functions (`drawWoodenSign`, `drawWoodenTag`, `drawWoodenButton`, `drawWoodenCard`) are added alongside the existing render pipeline so every surface composes the same primitives. New sprites (`coin`, `cakeSlice`, `titleCake`) are generated programmatically by `make*Sprite()` functions, matching the existing `game.sprites = {...}` pattern (no PNG files added). One new UI state field `ui.shopMode` toggles the right column between the cards list and an info preview panel.

**Tech Stack:** PHP (WordPress block-pattern file), vanilla JS in inline `<script>`, HTML5 Canvas 2D context. No build step. No test runner. Verification is hand-driven against the spec's 17-item checklist.

---

## Critical Inline-Script Gotcha

The pattern PHP runs through WordPress content filters that HTML-encode `&&` and `||` inside inline scripts to `&#038;&#038;` and similar, breaking the JS parser. **Every code block in this plan avoids `&&` and `||`.** Use nested `if`/`else if`, helper variables, or `indexOf`-style checks instead. After landing each task, the implementer verifies by curling the rendered page and grepping for `&#038;&#038;`.

## Visual Verification

There is no automated test runner for this theme. After each task:

1. WAMP must be running (Apache + MySQL via the WAMP tray icon).
2. Navigate to `http://localhost/dariobimbi.com/demos/ant-buster/`.
3. Hard refresh (Ctrl+F5) to bypass any cached JS.
4. Confirm the visual checks called out in that task's verification step.
5. If the page renders empty (header + footer with empty `<main>`), the inline-script HTML-encoding bug has hit. Run `curl -s http://localhost/dariobimbi.com/demos/ant-buster/ | grep '&#038;&#038;'` to find offending operators and rewrite as `if`/`else if`.

## File Structure

All implementation lands in two files:

- **Modify:** `patterns/demo-ant-buster.php` (the entire game lives here per project convention)
- **Modify:** `style.css` (theme version bump on the final task)

No new files are created. The single-file demo convention is preserved.

## Task Map

13 tasks, executed in order. Tasks 1-2 land foundation primitives; tasks 3-12 each restyle one surface; task 13 bumps the theme version.

1. Wooden chrome primitives (helpers used by every later task)
2. New inline sprite functions (coin, cake-slice, title-cake)
3. HUD redesign (remove Lost readout, replace flat strip with wooden plaque)
4. Idle / title splash (cake hero + ant scatter + sign overlap)
5. Pause splash (wooden sign rebuild)
6. Win splash (confetti + candles + warm tint)
7. Lose splash (scattered ants + wine-dark sign + jitter)
8. Wave intro banner and tutorial sticky restyle
9. Shop sidebar restyle (wooden cards + family strips + stats row + info hangtag)
10. Info panel feature (`ui.shopMode`, drill-down, back link)
11. Detail panel restyle (wooden chrome + targeting buttons + sell button)
12. Cannon descriptions content (16 descriptions for 4 base + 12 mid)
13. Theme version bump to 0.15.0

---

### Task 1: Wooden chrome primitives

**Files:**
- Modify: `patterns/demo-ant-buster.php` (insert helper functions immediately above `function drawHud()` at line ~2160)

These four helpers serve every later task. Implement them once, reuse everywhere.

- [ ] **Step 1: Add `drawWoodenPlaque(x, y, w, h)` helper**

Locate `function drawHud()` (search for `function drawHud`). Immediately above it, insert:

```js
  // Visual coherence pass — shared wooden chrome primitives.
  // drawWoodenPlaque: filled wood gradient with edge highlights and a deep border.
  // Used as the HUD strip background and as the body of larger signs.
  function drawWoodenPlaque(x, y, w, h) {
    var grd = ctx.createLinearGradient(x, y, x, y + h);
    grd.addColorStop(0, '#6a4a2e');
    grd.addColorStop(1, '#4a3220');
    ctx.fillStyle = grd;
    ctx.fillRect(x, y, w, h);
    // Top edge highlight (wood-edge)
    ctx.fillStyle = '#5a3a20';
    ctx.fillRect(x, y, w, 1);
    // Bottom edge highlight (wood-light)
    ctx.fillStyle = '#8a6a4e';
    ctx.fillRect(x, y + h - 2, w, 2);
    // Bottom border (wood-deep)
    ctx.fillStyle = '#2a1810';
    ctx.fillRect(x, y + h - 1, w, 1);
  }
```

- [ ] **Step 2: Add `drawWoodenTag(x, y, w, h)` helper**

```js
  // drawWoodenTag: a small recessed wooden box for HUD readouts and stat cells.
  // Body is a darker wood gradient than the plaque so tags sit IN the plaque.
  function drawWoodenTag(x, y, w, h) {
    var grd = ctx.createLinearGradient(x, y, x, y + h);
    grd.addColorStop(0, '#5a3a20');
    grd.addColorStop(1, '#3a2210');
    ctx.fillStyle = grd;
    ctx.fillRect(x, y, w, h);
    ctx.fillStyle = '#2a1810';
    ctx.fillRect(x, y, w, 1);                    // top dark inset
    ctx.fillRect(x, y, 1, h);
    ctx.fillRect(x + w - 1, y, 1, h);
    ctx.fillRect(x, y + h - 1, w, 1);
    ctx.fillStyle = '#8a6a4e';
    ctx.fillRect(x + 1, y + h - 2, w - 2, 1);    // light bottom inset
  }
```

- [ ] **Step 3: Add `drawWoodenSign(x, y, w, h)` helper**

```js
  // drawWoodenSign: full wooden sign with two metal nails at the top corners
  // and a bottom drop shadow. Used by all four full-screen overlays plus the
  // wave intro banner (at smaller scale).
  function drawWoodenSign(x, y, w, h) {
    // Drop shadow first so it sits behind the body
    ctx.fillStyle = 'rgba(0, 0, 0, 0.4)';
    ctx.fillRect(x + 2, y + h, w, 4);
    // Body (lighter than tags so the sign reads as foreground)
    var grd = ctx.createLinearGradient(x, y, x, y + h);
    grd.addColorStop(0, '#6a4a2e');
    grd.addColorStop(1, '#4a3220');
    ctx.fillStyle = grd;
    ctx.fillRect(x, y, w, h);
    // 3 px deep border
    ctx.fillStyle = '#2a1810';
    ctx.fillRect(x, y, w, 3);
    ctx.fillRect(x, y, 3, h);
    ctx.fillRect(x + w - 3, y, 3, h);
    ctx.fillRect(x, y + h - 3, w, 3);
    // Inset edge highlights
    ctx.fillStyle = '#5a3a20';
    ctx.fillRect(x + 3, y + 3, w - 6, 1);
    ctx.fillStyle = '#8a6a4e';
    ctx.fillRect(x + 3, y + h - 4, w - 6, 1);
    // Two metal nails at top corners
    ctx.fillStyle = '#444';
    ctx.fillRect(x + 6, y + 5, 4, 4);
    ctx.fillRect(x + w - 10, y + 5, 4, 4);
    ctx.fillStyle = '#888';
    ctx.fillRect(x + 7, y + 6, 2, 2);
    ctx.fillRect(x + w - 9, y + 6, 2, 2);
  }
```

- [ ] **Step 4: Add `drawWoodenButton(x, y, w, h, variant)` helper**

```js
  // drawWoodenButton: a chunky button with a top light inset and a bottom
  // shadow inset. Three variants:
  //   'green'  PLAY / Send Next / affordable upgrade row
  //   'red'    Sell / Restart Run
  //   'wood'   Targeting buttons and back link (neutral)
  function drawWoodenButton(x, y, w, h, variant) {
    var topColor; var bottomColor; var borderColor; var lightColor; var shadowColor;
    if (variant === 'red') {
      topColor = '#a04040';
      bottomColor = '#6a2020';
      borderColor = '#2a0a0a';
      lightColor = '#c06060';
      shadowColor = '#6a2020';
    } else if (variant === 'wood') {
      topColor = '#5a3a20';
      bottomColor = '#3a2210';
      borderColor = '#2a1810';
      lightColor = '#8a6a4e';
      shadowColor = '#3a2210';
    } else {
      // green default
      topColor = '#9ac870';
      bottomColor = '#6a9840';
      borderColor = '#2a1810';
      lightColor = '#b8e890';
      shadowColor = '#88b860';
    }
    var grd = ctx.createLinearGradient(x, y, x, y + h);
    grd.addColorStop(0, topColor);
    grd.addColorStop(1, bottomColor);
    ctx.fillStyle = grd;
    ctx.fillRect(x, y, w, h);
    // 2 px border
    ctx.fillStyle = borderColor;
    ctx.fillRect(x, y, w, 2);
    ctx.fillRect(x, y, 2, h);
    ctx.fillRect(x + w - 2, y, 2, h);
    ctx.fillRect(x, y + h - 2, w, 2);
    // Top light inset
    ctx.fillStyle = lightColor;
    ctx.fillRect(x + 2, y + 2, w - 4, 2);
    // Bottom shadow inset
    ctx.fillStyle = shadowColor;
    ctx.fillRect(x + 2, y + h - 4, w - 4, 2);
  }
```

- [ ] **Step 5: Add `drawTextWithShadow(text, x, y, color, font, align)` helper**

```js
  // drawTextWithShadow: drops a 1-px wood-deep shadow behind the text so
  // numbers and labels stand off the wood. Used by every readout, label,
  // and title in the visual pass.
  function drawTextWithShadow(text, x, y, color, font, align) {
    ctx.font = font;
    if (align) { ctx.textAlign = align; } else { ctx.textAlign = 'start'; }
    ctx.textBaseline = 'middle';
    ctx.fillStyle = '#2a1810';
    ctx.fillText(text, x + 1, y + 1);
    ctx.fillStyle = color;
    ctx.fillText(text, x, y);
    ctx.textAlign = 'start';
    ctx.textBaseline = 'alphabetic';
  }
```

- [ ] **Step 6: Visual verification**

Refresh `http://localhost/dariobimbi.com/demos/ant-buster/`. The page should still load and render exactly as before (these helpers are not yet called). Verify by:

```bash
curl -s http://localhost/dariobimbi.com/demos/ant-buster/ | grep -c '&#038;&#038;'
```

Expected: `0`

If the count is non-zero, locate the offending `&&` or `||` in the new helpers and rewrite as nested `if`.

- [ ] **Step 7: Commit**

```bash
git add patterns/demo-ant-buster.php
git commit -m "$(cat <<'EOF'
feat(ant-buster): visual coherence Task 1 — wooden chrome primitives

Add five reusable helpers (drawWoodenPlaque, drawWoodenTag, drawWoodenSign,
drawWoodenButton, drawTextWithShadow) that every later task in the visual
coherence pass composes. No surface uses them yet.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

### Task 2: New inline sprite functions

**Files:**
- Modify: `patterns/demo-ant-buster.php` (insert sprite functions just before the `game.sprites = {...}` block at line ~1328; extend `game.sprites` to include `coin`, `cakeSlice`, `titleCake`)

The existing project generates every sprite as an offscreen canvas via `make*Sprite()`. We follow the same pattern instead of loading PNG files.

- [ ] **Step 1: Add `makeCoinSprite()`**

Locate `game.sprites = {` (around line 1328). Immediately above the `game.sprites =` assignment, insert:

```js
  // 12x12 gold coin used in the HUD coin tag and every cost readout.
  function makeCoinSprite() {
    var c = document.createElement('canvas');
    c.width = 12; c.height = 12;
    var x = c.getContext('2d');
    // Outer dark ring
    x.fillStyle = '#2a1810';
    x.fillRect(2, 0, 8, 12);
    x.fillRect(0, 2, 12, 8);
    x.fillRect(1, 1, 10, 10);
    // Coin body
    x.fillStyle = '#c8a000';
    x.fillRect(2, 2, 8, 8);
    x.fillRect(1, 3, 10, 6);
    x.fillRect(3, 1, 6, 10);
    // Highlight
    x.fillStyle = '#fee761';
    x.fillRect(3, 3, 6, 6);
    x.fillRect(2, 4, 8, 4);
    // Center mark
    x.fillStyle = '#c8a000';
    x.fillRect(5, 5, 2, 2);
    return c;
  }
```

- [ ] **Step 2: Add `makeCakeSliceSprite()`**

Below `makeCoinSprite`:

```js
  // 12x12 cake-slice with candle. Used in the HUD cake tag.
  function makeCakeSliceSprite() {
    var c = document.createElement('canvas');
    c.width = 12; c.height = 12;
    var x = c.getContext('2d');
    // Frosting top (cream-yellow)
    x.fillStyle = '#fee761';
    x.fillRect(1, 4, 10, 2);
    // Cake body
    x.fillStyle = '#f5d000';
    x.fillRect(1, 6, 10, 2);
    // Bottom red layer
    x.fillStyle = '#c84244';
    x.fillRect(1, 8, 10, 3);
    // Outline
    x.fillStyle = '#2a1810';
    x.fillRect(0, 4, 1, 7);
    x.fillRect(11, 4, 1, 7);
    x.fillRect(1, 11, 10, 1);
    x.fillRect(1, 3, 10, 1);
    // Candle
    x.fillStyle = '#f5e6c8';
    x.fillRect(5, 1, 2, 3);
    // Flame
    x.fillStyle = '#ff9c44';
    x.fillRect(5, 0, 2, 1);
    return c;
  }
```

- [ ] **Step 3: Add `makeTitleCakeSprite()`**

Below `makeCakeSliceSprite`:

```js
  // 96x72 hero cake for the idle/title splash. Three tiers, three lit candles.
  // Drawn at 2x output scale (96x72) so that, when canvas is rendered at the
  // typical 2x CSS scale on desktop, it stays crisp.
  function makeTitleCakeSprite() {
    var c = document.createElement('canvas');
    c.width = 96; c.height = 72;
    var x = c.getContext('2d');
    var ink = '#2a1810';
    // Tier sizes (centered)
    // base: full-width bottom tier
    var b = { x: 4,  y: 50, w: 88, h: 18 };
    var m = { x: 14, y: 32, w: 68, h: 18 };
    var t = { x: 24, y: 18, w: 48, h: 14 };
    function tier(rect, frostingColor) {
      // Outline
      x.fillStyle = ink;
      x.fillRect(rect.x, rect.y, rect.w, rect.h);
      // Frosting strip on top
      x.fillStyle = '#fee761';
      x.fillRect(rect.x + 2, rect.y + 2, rect.w - 4, 4);
      // Body band
      x.fillStyle = '#f5d000';
      x.fillRect(rect.x + 2, rect.y + 6, rect.w - 4, 4);
      // Bottom frosting/colour band
      x.fillStyle = frostingColor;
      x.fillRect(rect.x + 2, rect.y + 10, rect.w - 4, rect.h - 12);
      // Bottom darken
      x.fillStyle = 'rgba(0, 0, 0, 0.25)';
      x.fillRect(rect.x + 2, rect.y + rect.h - 4, rect.w - 4, 2);
    }
    tier(b, '#c84244');     // bottom: red frosting band
    tier(m, '#ff77a8');     // middle: pink
    tier(t, '#c8f5a8');     // top: green
    // Three candles on top tier
    var cy = 6;
    for (var i = 0; i < 3; i++) {
      var cx = 36 + i * 12;
      x.fillStyle = ink;
      x.fillRect(cx, cy, 4, 12);
      x.fillStyle = '#f5e6c8';
      x.fillRect(cx + 1, cy + 1, 2, 10);
      // Flame
      x.fillStyle = ink;
      x.fillRect(cx + 1, cy - 5, 2, 5);
      x.fillStyle = '#ff9c44';
      x.fillRect(cx + 1, cy - 4, 2, 4);
      x.fillStyle = '#fee761';
      x.fillRect(cx + 1, cy - 3, 2, 2);
    }
    return c;
  }
```

- [ ] **Step 4: Wire the new sprites into `game.sprites`**

Locate the existing `game.sprites = {` block. Add three new keys at the top level (alongside `worker`, `cannonBase`, etc., NOT inside `turrets`). Edit the block by inserting the three lines after `projectile: makeProjectile()`:

```js
    anthill: makeAnthill(),
    projectile: makeProjectile(),
    coin: makeCoinSprite(),
    cakeSlice: makeCakeSliceSprite(),
    titleCake: makeTitleCakeSprite()
  };
```

(Watch the trailing comma rule: the line above `coin:` needs a comma added if it didn't have one; the line for `titleCake:` must NOT have a trailing comma since it's the last property.)

- [ ] **Step 5: Visual verification**

Refresh `http://localhost/dariobimbi.com/demos/ant-buster/`. The page should still load and render exactly as before (the new sprites are loaded but not yet drawn anywhere). In the browser console, type:

```js
game.sprites.coin
game.sprites.cakeSlice
game.sprites.titleCake
```

Each should return an `<canvas>` element. Confirm none are `undefined`.

Then verify no encoding bug:

```bash
curl -s http://localhost/dariobimbi.com/demos/ant-buster/ | grep -c '&#038;&#038;'
```

Expected: `0`.

- [ ] **Step 6: Commit**

```bash
git add patterns/demo-ant-buster.php
git commit -m "$(cat <<'EOF'
feat(ant-buster): visual coherence Task 2 — coin / cake-slice / title-cake sprites

Three new programmatically generated offscreen-canvas sprites attached to
game.sprites. Match the existing sprite-as-canvas pattern (no PNG files).
Sprites are loaded but not drawn yet.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

### Task 3: HUD redesign

**Files:**
- Modify: `patterns/demo-ant-buster.php` (replace body of `function drawHud()` at line ~2160)

Replaces the flat `rgba(0, 0, 0, 0.55)` strip with the wooden plaque, four wooden tags (Coin / Cake / Wave / Prep), and re-themed Send Next, pause, and speed widgets. Lost readout removed.

- [ ] **Step 1: Replace the `drawHud()` body**

Find `function drawHud()` and replace its full body with:

```js
  function drawHud() {
    drawWoodenPlaque(0, 0, PLAYFIELD_W, HUD_H);

    // Four readout tags, left-aligned.
    // Tag layout: 22 px tall, 1 px from top of HUD, content vertically centered.
    var tagY = 1;
    var tagH = HUD_H - 2;
    var tagX = 4;

    // 1. Coin tag — 12x12 sprite + gold value.
    var goldStr = '' + game.gold;
    var coinTagW = 14 + ctx.measureText(goldStr).width + 14;
    drawWoodenTag(tagX, tagY, coinTagW, tagH);
    ctx.drawImage(game.sprites.coin, tagX + 4, tagY + 5);
    drawTextWithShadow(goldStr, tagX + 20, tagY + tagH / 2, '#fee761', 'bold 11px monospace', 'start');
    tagX = tagX + coinTagW + 4;

    // 2. Cake tag — 12x12 cake-slice + warm gold value.
    var cakeStr = game.cake.slices + '/8';
    var cakeTagW = 14 + ctx.measureText(cakeStr).width + 14;
    drawWoodenTag(tagX, tagY, cakeTagW, tagH);
    ctx.drawImage(game.sprites.cakeSlice, tagX + 4, tagY + 5);
    drawTextWithShadow(cakeStr, tagX + 20, tagY + tagH / 2, '#ffd86b', 'bold 11px monospace', 'start');
    tagX = tagX + cakeTagW + 4;

    // 3. Wave tag — small caps "WAVE" + white value.
    var waveStr = (game.waveIndex + 1) + '/' + WAVES.length;
    var waveTagW = 28 + ctx.measureText(waveStr).width + 8;
    drawWoodenTag(tagX, tagY, waveTagW, tagH);
    drawTextWithShadow('WAVE', tagX + 5, tagY + tagH / 2, '#f5e6c8', 'bold 8px monospace', 'start');
    drawTextWithShadow(waveStr, tagX + 28, tagY + tagH / 2, '#ffffff', 'bold 11px monospace', 'start');
    tagX = tagX + waveTagW + 4;

    // 4. Prep tag — small caps "PREP" + light-green value, only during prep.
    if (game.waveState === 'prep') {
      var prepStr = Math.ceil(game.prepRemaining) + 's';
      var prepTagW = 26 + ctx.measureText(prepStr).width + 8;
      drawWoodenTag(tagX, tagY, prepTagW, tagH);
      drawTextWithShadow('PREP', tagX + 5, tagY + tagH / 2, '#f5e6c8', 'bold 8px monospace', 'start');
      drawTextWithShadow(prepStr, tagX + 28, tagY + tagH / 2, '#c8f5a8', 'bold 11px monospace', 'start');

      // Send Next button — green wooden, right-edge.
      var btnW = 64;
      var btnH = HUD_H - 4;
      var btnX = PLAYFIELD_W - btnW - 4;
      var btnY = 2;
      ui.sendNextRect = { x: btnX, y: btnY, w: btnW, h: btnH };
      drawWoodenButton(btnX, btnY, btnW, btnH, 'green');
      drawTextWithShadow('Send Next', btnX + btnW / 2, btnY + btnH / 2, '#1a1a0a', 'bold 9px monospace', 'center');
    } else {
      ui.sendNextRect = null;
    }

    // Pause widget (re-themed, same hit rect).
    drawWoodenButton(ui.pauseRect.x, ui.pauseRect.y, ui.pauseRect.w, ui.pauseRect.h, 'wood');
    ctx.fillStyle = '#fee761';
    if (game.paused) {
      ctx.beginPath();
      ctx.moveTo(ui.pauseRect.x + 5, ui.pauseRect.y + 4);
      ctx.lineTo(ui.pauseRect.x + 12, ui.pauseRect.y + 8);
      ctx.lineTo(ui.pauseRect.x + 5, ui.pauseRect.y + 12);
      ctx.closePath();
      ctx.fill();
    } else {
      ctx.fillRect(ui.pauseRect.x + 5, ui.pauseRect.y + 4, 2, 8);
      ctx.fillRect(ui.pauseRect.x + 9, ui.pauseRect.y + 4, 2, 8);
    }

    // Speed widget (re-themed, same hit rect).
    drawWoodenButton(ui.speedRect.x, ui.speedRect.y, ui.speedRect.w, ui.speedRect.h, 'wood');
    var speedLabel;
    if (game.speed === 2) { speedLabel = '2x'; } else { speedLabel = '1x'; }
    drawTextWithShadow(speedLabel, ui.speedRect.x + ui.speedRect.w / 2, ui.speedRect.y + ui.speedRect.h / 2, '#fee761', 'bold 9px monospace', 'center');
  }
```

- [ ] **Step 2: Visual verification**

Refresh and confirm:

- HUD strip is wooden brown, not flat black.
- Coin sprite + 150 visible at the left.
- Cake-slice sprite + 8/8 next to it.
- "WAVE 1/25" tag next.
- "PREP 30s" tag next (during prep).
- Lost readout is gone.
- Send Next button on the right is green wooden during prep.
- Pause and speed widgets are wooden.
- Numbers have a 1-px dark drop shadow.

Encoding check:

```bash
curl -s http://localhost/dariobimbi.com/demos/ant-buster/ | grep -c '&#038;&#038;'
```

Expected: `0`.

- [ ] **Step 3: Commit**

```bash
git add patterns/demo-ant-buster.php
git commit -m "$(cat <<'EOF'
feat(ant-buster): visual coherence Task 3 — HUD wooden plaque

Replace the flat dark HUD strip with a wooden plaque carrying four wooden-tag
readouts (Coin / Cake / Wave / Prep) and re-themed Send Next, pause, and speed
widgets. Lost readout removed from HUD; game.cake.lost still drives the lose
condition.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

### Task 4: Idle / title splash

**Files:**
- Modify: `patterns/demo-ant-buster.php` (replace body of `function drawIdleOverlay()` at line ~2282; add a module-scoped `IDLE_ANT_LAYOUT` array near the top of the IIFE)

Cake hero (192x144 source rendered at 280x180) sits behind a large wooden title sign. Sign overlaps the cake's lower 60%. Fifteen ants scatter across the canvas at three sizes and varied rotations.

- [ ] **Step 1: Add the static ant layout near the top of the IIFE**

Find the `var WIDTH = 640;` line (around line 88). Immediately after the canvas dimension constants, insert:

```js
  // Static layout for the 15 idle-splash ants. Frozen so they don't reshuffle
  // every frame. Each: { x, y, size, rot } in canvas coords. size: 0=small,
  // 1=medium, 2=big. rot in radians.
  var IDLE_ANT_LAYOUT = [
    { x:  40, y:  18, size: 2, rot:  0.6 },
    { x: 540, y:  30, size: 1, rot: -0.35 },
    { x: 110, y:  60, size: 0, rot:  1.92 },
    { x: 590, y:  70, size: 1, rot:  1.31 },
    { x:  20, y: 110, size: 1, rot: -0.87 },
    { x: 560, y: 140, size: 2, rot: -1.92 },
    { x:  50, y: 215, size: 0, rot:  0.35 },
    { x: 530, y: 250, size: 1, rot:  2.79 },
    { x:  90, y: 275, size: 1, rot: -1.22 },
    { x: 380, y: 310, size: 2, rot:  0.7 },
    { x:  40, y: 330, size: 0, rot: -0.44 },
    { x: 320, y:  14, size: 1, rot:  1.05 },
    { x: 410, y:  56, size: 0, rot: -1.48 },
    { x: 240, y: 100, size: 1, rot: -0.52 },
    { x: 320, y: 320, size: 1, rot:  2.09 }
  ];
```

- [ ] **Step 2: Add the `drawSplashAnt(x, y, size, rot)` helper**

Insert immediately above `function drawIdleOverlay()`:

```js
  // drawSplashAnt: renders the existing worker-ant sprite at one of three
  // scaled sizes with rotation. Used by idle and lose splashes.
  function drawSplashAnt(x, y, size, rot) {
    var s = game.sprites.worker;
    var scale;
    if (size === 0) { scale = 0.5; }
    else if (size === 1) { scale = 0.75; }
    else { scale = 1.0; }
    var w = s.width * scale;
    var h = s.height * scale;
    ctx.save();
    ctx.translate(x, y);
    ctx.rotate(rot);
    ctx.drawImage(s, -w / 2, -h / 2, w, h);
    ctx.restore();
  }
```

- [ ] **Step 3: Replace the body of `drawIdleOverlay()`**

Find `function drawIdleOverlay()` and replace its body with:

```js
  function drawIdleOverlay() {
    // Dim layer
    ctx.fillStyle = 'rgba(20, 10, 4, 0.55)';
    ctx.fillRect(0, 0, WIDTH, HEIGHT);

    // 15 scattered ants, behind the cake and sign.
    for (var i = 0; i < IDLE_ANT_LAYOUT.length; i++) {
      var a = IDLE_ANT_LAYOUT[i];
      drawSplashAnt(a.x, a.y, a.size, a.rot);
    }

    // Hero cake — 96x72 sprite scaled 2.92x to render at ~280x210, top-edge y=14.
    var cakeW = 280;
    var cakeH = 210;
    var cakeX = (WIDTH - cakeW) / 2;
    var cakeY = 14;
    ctx.imageSmoothingEnabled = false;
    ctx.drawImage(game.sprites.titleCake, cakeX, cakeY, cakeW, cakeH);

    // Title sign — 380x140, bottom edge y=320, overlaps cake's lower portion.
    var signW = 380;
    var signH = 140;
    var signX = (WIDTH - signW) / 2;
    var signY = HEIGHT - 40 - signH;
    drawWoodenSign(signX, signY, signW, signH);

    // Eyebrow
    drawTextWithShadow('DEFEND THE PICNIC', WIDTH / 2, signY + 22, '#c8a868', 'bold 10px monospace', 'center');

    // Title — per-letter rotation for hand-painted feel, no animation.
    var letters = ['A', 'N', 'T', ' ', 'B', 'U', 'S', 'T', 'E', 'R'];
    var rots = [-0.05, 0.035, -0.018, 0, 0.052, -0.035, 0, 0.035, -0.035, -0.018];
    ctx.font = 'bold 32px monospace';
    var totalW = 0;
    for (var l = 0; l < letters.length; l++) {
      totalW = totalW + ctx.measureText(letters[l]).width;
    }
    var lx = WIDTH / 2 - totalW / 2;
    var ly = signY + 70;
    for (var k = 0; k < letters.length; k++) {
      var ch = letters[k];
      var lw = ctx.measureText(ch).width;
      ctx.save();
      ctx.translate(lx + lw / 2, ly);
      ctx.rotate(rots[k]);
      ctx.fillStyle = '#2a1810';
      ctx.fillText(ch, -lw / 2 + 2, 2);
      ctx.fillStyle = '#fee761';
      ctx.fillText(ch, -lw / 2, 0);
      ctx.restore();
      lx = lx + lw;
    }

    // PLAY button — green wooden.
    var btnW = 110;
    var btnH = 30;
    var btnX = WIDTH / 2 - btnW / 2;
    var btnY = signY + signH - 50;
    drawWoodenButton(btnX, btnY, btnW, btnH, 'green');
    drawTextWithShadow('PLAY', btnX + btnW / 2, btnY + btnH / 2, '#1a1a0a', 'bold 16px monospace', 'center');

    // Track the click target for tap-to-start.
    ui.idlePlayRect = { x: btnX, y: btnY, w: btnW, h: btnH };
  }
```

- [ ] **Step 4: Visual verification**

Reload the page (which begins in idle state). Confirm:

- Dimmed playfield with 15 small ant silhouettes scattered across the canvas at varied angles.
- Three-tier cake centered, candles visible at top, base of cake disappears under the sign.
- Wooden sign with two metal nails at the top corners.
- "DEFEND THE PICNIC" eyebrow above title.
- "ANT BUSTER" in chunky gold with subtle per-letter tilt.
- Big green PLAY button.
- Clicking anywhere on the canvas (or the PLAY button) starts the game (existing behavior).

Encoding:

```bash
curl -s http://localhost/dariobimbi.com/demos/ant-buster/ | grep -c '&#038;&#038;'
```

Expected: `0`.

- [ ] **Step 5: Commit**

```bash
git add patterns/demo-ant-buster.php
git commit -m "$(cat <<'EOF'
feat(ant-buster): visual coherence Task 4 — idle title splash

Replace the bare ANT BUSTER text overlay with a hero composition: dim layer,
15 scattered ants, three-tier cake hero, wooden title sign overlapping the
cake's lower portion, hand-painted per-letter title rotation, chunky green
PLAY button. ui.idlePlayRect tracks the button hit area.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

### Task 5: Pause splash

**Files:**
- Modify: `patterns/demo-ant-buster.php` (replace body of `function drawPauseOverlay()` at line ~2310)

Wooden sign with eyebrow + title + sub + red Restart Run sub-button.

- [ ] **Step 1: Replace the `drawPauseOverlay()` body**

```js
  function drawPauseOverlay() {
    ctx.fillStyle = 'rgba(0, 0, 0, 0.55)';
    ctx.fillRect(0, 0, WIDTH, HEIGHT);

    var signW = 220;
    var signH = 120;
    var signX = (WIDTH - signW) / 2;
    var signY = (HEIGHT - signH) / 2 - 10;
    drawWoodenSign(signX, signY, signW, signH);

    drawTextWithShadow('HOLD UP', WIDTH / 2, signY + 22, '#c8a868', 'bold 10px monospace', 'center');
    drawTextWithShadow('PAUSED', WIDTH / 2, signY + 52, '#fee761', 'bold 24px monospace', 'center');
    drawTextWithShadow('Space to resume', WIDTH / 2, signY + 78, '#f5e6c8', 'bold 10px monospace', 'center');

    var rrW = 100;
    var rrH = 22;
    var rrX = WIDTH / 2 - rrW / 2;
    var rrY = signY + signH - 36;
    drawWoodenButton(rrX, rrY, rrW, rrH, 'red');
    drawTextWithShadow('Restart Run', rrX + rrW / 2, rrY + rrH / 2, '#fee761', 'bold 10px monospace', 'center');
    ui.restartRunRect = { x: rrX, y: rrY, w: rrW, h: rrH };
  }
```

- [ ] **Step 2: Visual verification**

Start the game, press Space to pause. Confirm:

- Dimmed canvas.
- Centered wooden sign with two nails.
- "HOLD UP" eyebrow, "PAUSED" title, "Space to resume" sub.
- Red wooden "Restart Run" sub-button works (clicking restarts).
- Pressing Space resumes.

Encoding check:

```bash
curl -s http://localhost/dariobimbi.com/demos/ant-buster/ | grep -c '&#038;&#038;'
```

Expected: `0`.

- [ ] **Step 3: Commit**

```bash
git add patterns/demo-ant-buster.php
git commit -m "$(cat <<'EOF'
feat(ant-buster): visual coherence Task 5 — pause splash

Wooden-sign pause overlay with eyebrow / title / sub / red Restart Run button.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

### Task 6: Win splash

**Files:**
- Modify: `patterns/demo-ant-buster.php` (replace body of `function drawWinOverlay()` at line ~2342; add `WIN_CONFETTI_LAYOUT` near the existing `IDLE_ANT_LAYOUT`)

Warm radial-tint dim, three little candles on top of the sign, eight confetti drift particles, "VICTORY" title with a gold glow.

- [ ] **Step 1: Add `WIN_CONFETTI_LAYOUT`**

Immediately below `IDLE_ANT_LAYOUT`:

```js
  // Static layout for win-splash confetti. Each: { x, color }. y is animated.
  var WIN_CONFETTI_LAYOUT = [
    { x:  60, color: '#fee761' },
    { x: 130, color: '#ff77a8' },
    { x: 220, color: '#c8f5a8' },
    { x: 300, color: '#fee761' },
    { x: 380, color: '#ff77a8' },
    { x: 450, color: '#c8f5a8' },
    { x: 530, color: '#fee761' },
    { x: 590, color: '#ff77a8' }
  ];
```

- [ ] **Step 2: Replace `drawWinOverlay()`**

```js
  function drawWinOverlay() {
    // Warm radial dim — golden glow at the center, deeper dim at the edges.
    var grd = ctx.createRadialGradient(WIDTH / 2, HEIGHT / 2, 40, WIDTH / 2, HEIGHT / 2, 320);
    grd.addColorStop(0, 'rgba(254, 231, 97, 0.35)');
    grd.addColorStop(1, 'rgba(20, 10, 4, 0.55)');
    ctx.fillStyle = grd;
    ctx.fillRect(0, 0, WIDTH, HEIGHT);

    // Confetti — eight pieces drifting down, looping every 4 s.
    var t = (game.time % 4) / 4;
    for (var i = 0; i < WIN_CONFETTI_LAYOUT.length; i++) {
      var p = WIN_CONFETTI_LAYOUT[i];
      var phase = (t + i / 8) % 1;
      var py = phase * (HEIGHT + 20) - 10;
      ctx.save();
      ctx.translate(p.x, py);
      ctx.rotate(phase * Math.PI * 4);
      ctx.fillStyle = p.color;
      ctx.fillRect(-3, -4, 6, 8);
      ctx.restore();
    }

    // Sign
    var signW = 280;
    var signH = 130;
    var signX = (WIDTH - signW) / 2;
    var signY = (HEIGHT - signH) / 2 - 6;
    drawWoodenSign(signX, signY, signW, signH);

    // Three candles on top of the sign.
    var candleY = signY - 14;
    for (var c = 0; c < 3; c++) {
      var cx = signX + signW / 2 - 18 + c * 18;
      ctx.fillStyle = '#2a1810';
      ctx.fillRect(cx, candleY, 4, 14);
      ctx.fillStyle = '#f5e6c8';
      ctx.fillRect(cx + 1, candleY + 1, 2, 12);
      // Flame flicker — uses time so all three flicker together. Cheap.
      var flick = (game.time * 4 + c) % 1;
      var flameH;
      if (flick < 0.5) { flameH = 5; } else { flameH = 6; }
      ctx.fillStyle = '#ff9c44';
      ctx.fillRect(cx + 1, candleY - flameH, 2, flameH);
      ctx.fillStyle = '#fee761';
      ctx.fillRect(cx + 1, candleY - flameH + 2, 2, flameH - 2);
    }

    // Title — VICTORY at 40 px with a gold glow.
    drawTextWithShadow('ALL 25 WAVES CLEARED', WIDTH / 2, signY + 24, '#fee761', 'bold 10px monospace', 'center');
    ctx.save();
    ctx.shadowBlur = 18;
    ctx.shadowColor = 'rgba(254, 231, 97, 0.6)';
    drawTextWithShadow('VICTORY', WIDTH / 2, signY + 70, '#fff8a0', 'bold 40px monospace', 'center');
    ctx.restore();
    drawTextWithShadow('Click to restart', WIDTH / 2, signY + signH - 22, '#f5e6c8', 'bold 10px monospace', 'center');
  }
```

- [ ] **Step 3: Visual verification**

Force a win (the easiest path: edit `WAVES.length` temporarily, or play through normally). Confirm:

- Warm radial gold tint over the canvas.
- Confetti drifts vertically.
- Wooden sign centered with three flickering candles on top.
- "ALL 25 WAVES CLEARED" eyebrow.
- "VICTORY" title in cream-yellow with a soft glow.
- "Click to restart" sub.
- Click anywhere to restart.

Encoding:

```bash
curl -s http://localhost/dariobimbi.com/demos/ant-buster/ | grep -c '&#038;&#038;'
```

Expected: `0`.

- [ ] **Step 4: Commit**

```bash
git add patterns/demo-ant-buster.php
git commit -m "$(cat <<'EOF'
feat(ant-buster): visual coherence Task 6 — win splash

Warm radial dim, three candles flickering on top of the sign, eight drifting
confetti pieces, VICTORY title with gold glow. ALL 25 WAVES CLEARED eyebrow
carries the brag.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

### Task 7: Lose splash

**Files:**
- Modify: `patterns/demo-ant-buster.php` (replace body of `function drawLoseOverlay()` at line ~2329; add `LOSE_ANT_LAYOUT` near the existing layouts)

Saturated red dim, six scattered ants (medium and big sizes only), wine-dark wooden sign, jittering title.

- [ ] **Step 1: Add `LOSE_ANT_LAYOUT`**

Below `WIN_CONFETTI_LAYOUT`:

```js
  // Static layout for lose-splash ants. Six items, all medium or big sizes,
  // positioned around (not directly behind) the sign.
  var LOSE_ANT_LAYOUT = [
    { x:  60, y:  60, size: 2, rot:  0.7 },
    { x: 580, y:  80, size: 1, rot: -0.5 },
    { x:  40, y: 280, size: 1, rot:  2.6 },
    { x: 600, y: 290, size: 2, rot: -1.8 },
    { x: 320, y:  20, size: 1, rot:  1.2 },
    { x: 340, y: 330, size: 2, rot: -0.3 }
  ];
```

- [ ] **Step 2: Add `drawWoodenSignWine(x, y, w, h)` helper**

The lose sign uses a darker, wine-tinted wood. Add this helper above `drawLoseOverlay()`:

```js
  // Wine-dark variant of drawWoodenSign for the lose splash.
  function drawWoodenSignWine(x, y, w, h) {
    ctx.fillStyle = 'rgba(0, 0, 0, 0.4)';
    ctx.fillRect(x + 2, y + h, w, 4);
    var grd = ctx.createLinearGradient(x, y, x, y + h);
    grd.addColorStop(0, '#5a2a20');
    grd.addColorStop(1, '#3a1a10');
    ctx.fillStyle = grd;
    ctx.fillRect(x, y, w, h);
    ctx.fillStyle = '#5a1a1a';
    ctx.fillRect(x, y, w, 3);
    ctx.fillRect(x, y, 3, h);
    ctx.fillRect(x + w - 3, y, 3, h);
    ctx.fillRect(x, y + h - 3, w, 3);
    ctx.fillStyle = '#3a1010';
    ctx.fillRect(x + 3, y + 3, w - 6, 1);
    ctx.fillStyle = '#7a3a30';
    ctx.fillRect(x + 3, y + h - 4, w - 6, 1);
    ctx.fillStyle = '#444';
    ctx.fillRect(x + 6, y + 5, 4, 4);
    ctx.fillRect(x + w - 10, y + 5, 4, 4);
    ctx.fillStyle = '#888';
    ctx.fillRect(x + 7, y + 6, 2, 2);
    ctx.fillRect(x + w - 9, y + 6, 2, 2);
  }
```

- [ ] **Step 3: Replace `drawLoseOverlay()`**

```js
  function drawLoseOverlay() {
    // Saturated red dim
    ctx.fillStyle = 'rgba(80, 10, 10, 0.55)';
    ctx.fillRect(0, 0, WIDTH, HEIGHT);

    // Scattered ants
    for (var i = 0; i < LOSE_ANT_LAYOUT.length; i++) {
      var a = LOSE_ANT_LAYOUT[i];
      drawSplashAnt(a.x, a.y, a.size, a.rot);
    }

    var signW = 280;
    var signH = 130;
    var signX = (WIDTH - signW) / 2;
    var signY = (HEIGHT - signH) / 2 - 6;
    drawWoodenSignWine(signX, signY, signW, signH);

    drawTextWithShadow('8 SLICES LOST', WIDTH / 2, signY + 24, '#ff8a8a', 'bold 10px monospace', 'center');

    // Title with subtle horizontal jitter (1 px @ ~3 Hz).
    var jx;
    var p = (game.time * 6) % 2;
    if (p < 1) { jx = 1; } else { jx = -1; }
    ctx.save();
    ctx.shadowBlur = 14;
    ctx.shadowColor = 'rgba(255, 80, 80, 0.5)';
    drawTextWithShadow('THE ANTS WIN', WIDTH / 2 + jx, signY + 70, '#ff5050', 'bold 32px monospace', 'center');
    ctx.restore();

    drawTextWithShadow('Click to try again', WIDTH / 2, signY + signH - 22, '#ffaaaa', 'bold 10px monospace', 'center');
  }
```

- [ ] **Step 4: Visual verification**

Force a loss (let 8 cake slices through). Confirm:

- Saturated red dim instead of neutral black.
- Six scattered ants (medium/big sizes).
- Wine-dark wooden sign with red-toned border.
- "8 SLICES LOST" eyebrow in light red.
- "THE ANTS WIN" title in red, jittering subtly.
- "Click to try again" sub.
- Click anywhere restarts.

Encoding:

```bash
curl -s http://localhost/dariobimbi.com/demos/ant-buster/ | grep -c '&#038;&#038;'
```

Expected: `0`.

- [ ] **Step 5: Commit**

```bash
git add patterns/demo-ant-buster.php
git commit -m "$(cat <<'EOF'
feat(ant-buster): visual coherence Task 7 — lose splash

Saturated red dim, six scattered ants, wine-dark wooden sign with red border,
THE ANTS WIN title with horizontal jitter and red glow. 8 SLICES LOST eyebrow
names the damage; Click to try again sub.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

### Task 8: Wave intro banner and tutorial sticky

**Files:**
- Modify: `patterns/demo-ant-buster.php` (replace bodies of `function drawBanner()` at line ~2256 and `function drawTutorialHint()` at line ~2296)

Wave banner becomes a small wooden sign at the top of the playfield. Boss wave uses the wine-dark variant. Tutorial sticky becomes a paper note tilted -1.5 degrees.

- [ ] **Step 1: Replace `drawBanner()`**

```js
  function drawBanner() {
    if (!game.banner.active) { return; }
    var bt = game.time - game.banner.startedAt;
    if (bt > game.banner.duration) {
      game.banner.active = false;
      return;
    }
    var alpha = 1;
    if (bt < 0.4) { alpha = bt / 0.4; }
    if (bt > game.banner.duration - 0.5) { alpha = (game.banner.duration - bt) / 0.5; }
    ctx.save();
    ctx.globalAlpha = alpha;
    var w = 140;
    var h = 26;
    var x = WIDTH / 2 - w / 2;
    var y = 32;
    if (game.banner.isBoss) {
      drawWoodenSignWine(x, y, w, h);
      drawTextWithShadow(game.banner.text, x + w / 2, y + h / 2, '#ffaaaa', 'bold 12px monospace', 'center');
    } else if (game.banner.isFinal) {
      drawWoodenSignWine(x, y, w, h);
      drawTextWithShadow(game.banner.text, x + w / 2, y + h / 2, '#fee761', 'bold 12px monospace', 'center');
    } else {
      drawWoodenSign(x, y, w, h);
      drawTextWithShadow(game.banner.text, x + w / 2, y + h / 2, '#fee761', 'bold 12px monospace', 'center');
    }
    ctx.restore();
  }
```

- [ ] **Step 2: Replace `drawTutorialHint()`**

```js
  function drawTutorialHint() {
    if (game.state !== 'playing') { return; }
    if (game.time >= 4) { return; }
    if (game.cannons.length !== 0) { return; }
    var w = 220;
    var h = 64;
    var x = 60;
    var y = 110;
    ctx.save();
    ctx.translate(x + w / 2, y + h / 2);
    ctx.rotate(-0.026); // -1.5 deg
    // Paper card body
    var grd = ctx.createLinearGradient(0, -h / 2, 0, h / 2);
    grd.addColorStop(0, '#fff8e8');
    grd.addColorStop(1, '#f0dfb8');
    ctx.fillStyle = grd;
    ctx.fillRect(-w / 2, -h / 2, w, h);
    // Drop shadow under the rotated card
    ctx.fillStyle = 'rgba(0, 0, 0, 0.35)';
    ctx.fillRect(-w / 2 + 3, h / 2, w, 3);
    // Border
    ctx.fillStyle = '#2a1810';
    ctx.fillRect(-w / 2, -h / 2, w, 2);
    ctx.fillRect(-w / 2, -h / 2, 2, h);
    ctx.fillRect(w / 2 - 2, -h / 2, 2, h);
    ctx.fillRect(-w / 2, h / 2 - 2, w, 2);
    // Text
    ctx.font = 'bold 10px monospace';
    ctx.fillStyle = '#4a3220';
    ctx.textAlign = 'center';
    ctx.fillText('Drag a cannon from the shop', 0, -6);
    ctx.fillText('onto the picnic blanket to start', 0, 10);
    ctx.restore();
    ctx.textAlign = 'start';
  }
```

- [ ] **Step 3: Visual verification**

Start the game. Within the first 4 seconds (and before placing a cannon), confirm:

- Tutorial sticky is a paper card tilted slightly counter-clockwise.
- Text reads cleanly on cream paper.
- Disappears after 4 s or after a cannon is placed.

Place a cannon and trigger the next wave intro. Confirm the banner is now a small wooden sign instead of the flat colored rect. Boss waves should use the wine-dark variant.

Encoding:

```bash
curl -s http://localhost/dariobimbi.com/demos/ant-buster/ | grep -c '&#038;&#038;'
```

Expected: `0`.

- [ ] **Step 4: Commit**

```bash
git add patterns/demo-ant-buster.php
git commit -m "$(cat <<'EOF'
feat(ant-buster): visual coherence Task 8 — wave banner + tutorial sticky

Wave intro banner becomes a small wooden sign (wine-dark for boss waves).
Tutorial hint becomes a tilted paper card with cream body and dark border,
reading like a sticky note on the playfield.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

### Task 9: Shop sidebar

**Files:**
- Modify: `patterns/demo-ant-buster.php` (replace body of `function drawShop()` at line ~1396; add `drawShopBackground()` helper used by both shop and info panel)

Wood-grain panel background, "CANNONS" header, four wooden cards each with a family-color strip, sprite tile, name, cost (with coin icon), full-width stats row, and "?" hangtag.

- [ ] **Step 1: Add the family-color and stats lookups near the top of the IIFE**

Find the `var WIDTH = 640;` constant block. Below the constants and any existing layout arrays, add:

```js
  // Family-color strip lookup for shop cards.
  var FAMILY_STRIP_COLOR = {
    heavy:  '#888',
    quick:  '#b0d04a',
    status: '#8a5ac8',
    rubber: '#ff77a8'
  };
```

- [ ] **Step 2: Add `drawShopBackground()` helper**

Insert immediately above `function drawShop()`:

```js
  // Wood-grain panel background filling the right shop column.
  // Vertical 4-px stripes alternating two wood tones plus a top-to-bottom
  // gradient overlay to read as planks.
  function drawShopBackground() {
    var sx = PLAYFIELD_W;
    var sy = 0;
    var sw = SHOP_W;
    var sh = HEIGHT;
    // Stripes
    for (var i = 0; i < sw; i = i + 4) {
      if ((i / 4) % 2 === 0) {
        ctx.fillStyle = '#4a3220';
      } else {
        ctx.fillStyle = '#5a3a26';
      }
      ctx.fillRect(sx + i, sy, 4, sh);
    }
    // Vertical darken gradient
    var grd = ctx.createLinearGradient(sx, sy, sx, sy + sh);
    grd.addColorStop(0, 'rgba(90, 58, 38, 0.0)');
    grd.addColorStop(1, 'rgba(58, 32, 16, 0.6)');
    ctx.fillStyle = grd;
    ctx.fillRect(sx, sy, sw, sh);
    // Left edge dark border
    ctx.fillStyle = '#2a1810';
    ctx.fillRect(sx, sy, 2, sh);
  }
```

- [ ] **Step 3: Replace `drawShop()`**

```js
  function drawShop() {
    drawShopBackground();

    // "CANNONS" header
    drawTextWithShadow('CANNONS', PLAYFIELD_W + SHOP_W / 2, 14, '#c8a868', 'bold 9px monospace', 'center');

    // If shopMode is 'info', delegate to the info panel render and return.
    // (Task 10 wires the info panel; on Task 9 ui.shopMode is always 'list'
    // so this branch is harmless until Task 10 lands.)
    if (ui.shopMode === 'info') {
      drawShopInfoPanel();
      return;
    }

    var keys = ['howitzer', 'repeater', 'twingun', 'pink_pea'];
    ui.shopRects = [];
    ui.shopInfoRects = [];
    var cardX = PLAYFIELD_W + 6;
    var cardW = SHOP_W - 12;
    var cardH = 78;
    var startY = 26;

    for (var i = 0; i < keys.length; i++) {
      var k = keys[i];
      var sk = CANNON_KINDS[k];
      var cy = startY + i * (cardH + 4);
      var canAfford = (game.gold >= sk.cost);

      // Card body
      drawWoodenButton(cardX, cy, cardW, cardH, 'wood');

      // Family color strip
      var stripColor = FAMILY_STRIP_COLOR[sk.family];
      if (!stripColor) { stripColor = '#888'; }
      ctx.fillStyle = stripColor;
      ctx.fillRect(cardX + 2, cy + 2, 4, cardH - 4);
      ctx.fillStyle = '#2a1810';
      ctx.fillRect(cardX + 6, cy + 2, 1, cardH - 4);

      // Sprite tile (32x32 dark inset)
      var spX = cardX + 10;
      var spY = cy + 8;
      ctx.fillStyle = '#2a1810';
      ctx.fillRect(spX, spY, 32, 32);
      ctx.fillStyle = '#1a0a00';
      ctx.fillRect(spX, spY, 32, 1);
      ctx.fillRect(spX, spY, 1, 32);
      ctx.fillRect(spX + 31, spY, 1, 32);
      ctx.fillRect(spX, spY + 31, 32, 1);
      var turret = game.sprites.turrets[k];
      if (turret) { ctx.drawImage(turret, spX, spY); }

      // Name
      drawTextWithShadow(sk.name, spX + 36, cy + 14, '#fee761', 'bold 11px monospace', 'start');

      // Cost row: coin sprite + value, vertically centered with the coin.
      var costY = cy + 30;
      ctx.drawImage(game.sprites.coin, spX + 36, costY - 5);
      var costColor;
      if (canAfford) { costColor = '#fee761'; } else { costColor = '#888'; }
      drawTextWithShadow('' + sk.cost, spX + 52, costY + 1, costColor, 'bold 11px monospace', 'start');

      // Stats row — full card width, below the sprite/name/cost zone.
      var statsY = cy + 56;
      ctx.fillStyle = 'rgba(0, 0, 0, 0.18)';
      ctx.fillRect(cardX + 8, statsY - 4, cardW - 16, 18);
      ctx.fillStyle = '#2a1810';
      ctx.fillRect(cardX + 8, statsY - 4, cardW - 16, 1);

      // Three stats space-between
      var statX0 = cardX + 12;
      var statX2 = cardX + cardW - 12;
      var statXMid = (statX0 + statX2) / 2;
      drawTextWithShadow('DMG', statX0, statsY + 4, '#c8a868', 'bold 8px monospace', 'start');
      drawTextWithShadow('' + sk.damage, statX0 + 22, statsY + 4, '#f5e6c8', 'bold 10px monospace', 'start');
      drawTextWithShadow('RATE', statXMid - 18, statsY + 4, '#c8a868', 'bold 8px monospace', 'start');
      drawTextWithShadow('' + sk.fireRate, statXMid + 12, statsY + 4, '#f5e6c8', 'bold 10px monospace', 'start');
      drawTextWithShadow('RNG', statX2 - 28, statsY + 4, '#c8a868', 'bold 8px monospace', 'start');
      drawTextWithShadow('' + sk.range, statX2 - 4, statsY + 4, '#f5e6c8', 'bold 10px monospace', 'end');

      // "?" wooden hangtag at the top-right
      var tagW = 16;
      var tagH = 16;
      var tagX = cardX + cardW - tagW - 4;
      var tagY = cy + 2;
      drawWoodenTag(tagX, tagY, tagW, tagH);
      // Dark string anchor
      ctx.fillStyle = '#2a1810';
      ctx.fillRect(tagX + tagW / 2 - 1, tagY - 3, 2, 4);
      drawTextWithShadow('?', tagX + tagW / 2, tagY + tagH / 2, '#fee761', 'bold 12px monospace', 'center');
      ui.shopInfoRects.push({ x: tagX, y: tagY, w: tagW, h: tagH, kind: k });

      // Armed-card highlight + Tap a spot pill
      if (ui.armedKind === k) {
        ctx.strokeStyle = '#fee761';
        ctx.lineWidth = 3;
        ctx.strokeRect(cardX + 1, cy + 1, cardW - 2, cardH - 2);
        ctx.lineWidth = 1;
        var pillW = 60;
        var pillH = 12;
        var pillX = cardX + cardW / 2 - pillW / 2;
        var pillY = cy + cardH - 6;
        ctx.fillStyle = '#fee761';
        ctx.fillRect(pillX, pillY, pillW, pillH);
        ctx.fillStyle = '#2a1810';
        ctx.fillRect(pillX, pillY, pillW, 1);
        ctx.fillRect(pillX, pillY + pillH - 1, pillW, 1);
        ctx.fillRect(pillX, pillY, 1, pillH);
        ctx.fillRect(pillX + pillW - 1, pillY, 1, pillH);
        drawTextWithShadow('TAP A SPOT', pillX + pillW / 2, pillY + pillH / 2, '#2a1810', 'bold 8px monospace', 'center');
      }

      ui.shopRects.push({ x: cardX, y: cy, w: cardW, h: cardH, kind: k });
    }
  }
```

- [ ] **Step 4: Add a stub `drawShopInfoPanel()`**

The shop branch references `drawShopInfoPanel`. Until Task 10 implements it, add a placeholder so the page does not throw:

```js
  // Stub. Task 10 replaces this with the full info panel.
  function drawShopInfoPanel() {
    drawTextWithShadow('Info panel coming in Task 10', PLAYFIELD_W + SHOP_W / 2, HEIGHT / 2, '#c8a868', 'bold 10px monospace', 'center');
  }
```

Insert above `drawShop()`.

Also add `ui.shopMode = 'list'` initialization. Find where `ui = { ... }` is defined (search for `var ui = {` or where `ui.armedKind` is initialized; likely around line 5489 per memory). Add to the `ui` object literal:

```js
    shopMode: 'list',
    shopInfoKind: null,
    shopInfoRects: [],
    idlePlayRect: null,
```

(idlePlayRect was added in Task 4 but only set, not declared in `ui`; declare it here for clarity.)

- [ ] **Step 5: Visual verification**

Refresh. Confirm:

- Right column has wood-grain background.
- "CANNONS" header at top.
- Four cards: Howitzer, Repeater, Twin Gun, Pink Pea.
- Each has the correct family-color strip on the left edge.
- Sprite tile is dark with the existing turret sprite drawn inside.
- Name in gold bold.
- Coin sprite + cost vertically aligned.
- Stats row at the bottom of each card spans full width with DMG / RATE / RNG.
- "?" hangtag on each card top-right.
- Armed state (after tapping a card) shows the yellow border and "TAP A SPOT" pill.
- Tapping "?" still does nothing visible (Task 10 will wire it).

Encoding:

```bash
curl -s http://localhost/dariobimbi.com/demos/ant-buster/ | grep -c '&#038;&#038;'
```

Expected: `0`.

- [ ] **Step 6: Commit**

```bash
git add patterns/demo-ant-buster.php
git commit -m "$(cat <<'EOF'
feat(ant-buster): visual coherence Task 9 — shop sidebar

Wood-grain shop column with CANNONS header, four wooden cards each carrying
family-color strip, sprite tile, name, cost-with-coin, and a full-width stats
row. ? hangtag rendered on every card; click handler lands in Task 10. Adds
ui.shopMode/shopInfoKind/shopInfoRects state fields (default list mode).

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

### Task 10: Info panel feature

**Files:**
- Modify: `patterns/demo-ant-buster.php` (replace stub `drawShopInfoPanel()` from Task 9; add hangtag click handler in the existing pointerdown handler)

The "?" hangtag flips the right column to an info panel showing description and 1-tier upgrade preview.

- [ ] **Step 1: Replace the `drawShopInfoPanel()` stub**

```js
  function drawShopInfoPanel() {
    var k = ui.shopInfoKind;
    if (!k) { ui.shopMode = 'list'; return; }
    var sk = CANNON_KINDS[k];
    if (!sk) { ui.shopMode = 'list'; return; }

    var px = PLAYFIELD_W + 6;
    var pw = SHOP_W - 12;
    var py = 26;

    // Back link
    drawTextWithShadow('< BACK TO CANNONS', px + pw / 2, py + 6, '#c8a868', 'bold 8px monospace', 'center');
    ui.shopInfoBackRect = { x: px, y: py, w: pw, h: 14 };

    // Name
    drawTextWithShadow(sk.name, px + 4, py + 28, '#fee761', 'bold 14px monospace', 'start');
    drawTextWithShadow(sk.family + ' family', px + 4, py + 42, '#c8a868', 'bold 8px monospace', 'start');

    // Stats row in a single recessed tag
    var sy = py + 50;
    drawWoodenTag(px + 2, sy, pw - 4, 24);
    var sx0 = px + 8;
    var sx2 = px + pw - 8;
    var sxMid = (sx0 + sx2) / 2;
    drawTextWithShadow('DMG', sx0, sy + 8, '#c8a868', 'bold 7px monospace', 'start');
    drawTextWithShadow('' + sk.damage, sx0, sy + 18, '#f5e6c8', 'bold 11px monospace', 'start');
    drawTextWithShadow('RATE', sxMid - 12, sy + 8, '#c8a868', 'bold 7px monospace', 'start');
    drawTextWithShadow('' + sk.fireRate, sxMid - 12, sy + 18, '#f5e6c8', 'bold 11px monospace', 'start');
    drawTextWithShadow('RNG', sx2 - 24, sy + 8, '#c8a868', 'bold 7px monospace', 'start');
    drawTextWithShadow('' + sk.range, sx2, sy + 18, '#f5e6c8', 'bold 11px monospace', 'end');

    // Description card. Falls back to "<family> <tier>" if description missing.
    var desc = sk.description;
    if (!desc) {
      var tierLabel = sk.tier;
      if (!tierLabel) { tierLabel = 'base'; }
      desc = sk.family + ' ' + tierLabel;
    }
    var dy = py + 80;
    var dh = 36;
    ctx.fillStyle = '#2a1810';
    ctx.fillRect(px + 2, dy, pw - 4, dh);
    ctx.fillStyle = '#3a2210';
    ctx.fillRect(px + 4, dy + 2, pw - 8, dh - 4);
    drawWrappedText(desc, px + 7, dy + 6, pw - 14, '#f5e6c8', 'bold 9px monospace', 11);

    // Upgrades to header
    drawTextWithShadow('UPGRADES TO', px + 4, py + 124, '#c8a868', 'bold 8px monospace', 'start');

    // Upgrade rows
    var upgrades = sk.upgradesTo;
    if (!upgrades) { upgrades = []; }
    var rowY = py + 134;
    for (var i = 0; i < upgrades.length; i++) {
      var uk = upgrades[i];
      var udef = CANNON_KINDS[uk];
      if (!udef) { continue; }
      var rh = 36;
      drawWoodenButton(px + 2, rowY, pw - 4, rh, 'wood');
      // Top line: arrow + name + cost-with-coin (right aligned)
      drawTextWithShadow('->', px + 6, rowY + 8, '#fee761', 'bold 10px monospace', 'start');
      drawTextWithShadow(udef.name, px + 18, rowY + 8, '#fee761', 'bold 10px monospace', 'start');
      ctx.drawImage(game.sprites.coin, px + pw - 26, rowY + 3);
      drawTextWithShadow('' + udef.cost, px + pw - 6, rowY + 8, '#fee761', 'bold 10px monospace', 'end');
      // Stats line
      drawTextWithShadow('DMG ' + udef.damage + '  RATE ' + udef.fireRate + '  RNG ' + udef.range, px + 6, rowY + 20, '#f5e6c8', 'bold 8px monospace', 'start');
      // Description line (falls back to family + tier)
      var udesc = udef.description;
      if (!udesc) {
        var ut = udef.tier;
        if (!ut) { ut = 'mid'; }
        udesc = udef.family + ' ' + ut;
      }
      drawTextWithShadow(truncate(udesc, 30), px + 6, rowY + 30, '#d8c8a0', 'bold 7px monospace', 'start');
      rowY = rowY + rh + 4;
    }
  }

  // Word-wrap helper for the description card. Naive but enough for short
  // sentences at small widths.
  function drawWrappedText(text, x, y, maxW, color, font, lineH) {
    ctx.font = font;
    var words = text.split(' ');
    var line = '';
    var ly = y + lineH / 2;
    for (var i = 0; i < words.length; i++) {
      var test = line;
      if (test.length > 0) { test = test + ' '; }
      test = test + words[i];
      if (ctx.measureText(test).width > maxW) {
        if (line.length > 0) {
          drawTextWithShadow(line, x, ly, color, font, 'start');
          ly = ly + lineH;
          line = words[i];
        } else {
          drawTextWithShadow(test, x, ly, color, font, 'start');
          ly = ly + lineH;
          line = '';
        }
      } else {
        line = test;
      }
    }
    if (line.length > 0) {
      drawTextWithShadow(line, x, ly, color, font, 'start');
    }
  }

  function truncate(s, n) {
    if (s.length <= n) { return s; }
    return s.substring(0, n - 1) + '...';
  }
```

- [ ] **Step 2: Wire the hangtag click handler**

Find the existing pointerdown handler in the canvas event-listener block (search for `canvas.addEventListener('pointerdown'` or similar). Inside that handler, locate the section that decides between playfield and shop (likely guarded by `if (p.x >= PLAYFIELD_W)` or similar shop branch). At the start of the shop branch, before the existing card-arming logic, insert:

```js
        // Visual coherence: hangtag click flips to info panel.
        if (ui.shopMode === 'info') {
          // Back link?
          if (ui.shopInfoBackRect) {
            var br = ui.shopInfoBackRect;
            if (p.x >= br.x) {
              if (p.x <= br.x + br.w) {
                if (p.y >= br.y) {
                  if (p.y <= br.y + br.h) {
                    ui.shopMode = 'list';
                    ui.shopInfoKind = null;
                    canvas.releasePointerCapture(evt.pointerId);
                    ui.activePointerId = null;
                    ui.pointerType = null;
                    return;
                  }
                }
              }
            }
          }
          // Any other tap inside the info panel: close.
          if (p.x >= PLAYFIELD_W) {
            ui.shopMode = 'list';
            ui.shopInfoKind = null;
            canvas.releasePointerCapture(evt.pointerId);
            ui.activePointerId = null;
            ui.pointerType = null;
            return;
          }
        }
        // Hangtag rects always checked first when in list mode.
        if (ui.shopMode === 'list') {
          for (var hi = 0; hi < ui.shopInfoRects.length; hi++) {
            var hr = ui.shopInfoRects[hi];
            if (p.x >= hr.x) {
              if (p.x <= hr.x + hr.w) {
                if (p.y >= hr.y) {
                  if (p.y <= hr.y + hr.h) {
                    ui.shopMode = 'info';
                    ui.shopInfoKind = hr.kind;
                    canvas.releasePointerCapture(evt.pointerId);
                    ui.activePointerId = null;
                    ui.pointerType = null;
                    return;
                  }
                }
              }
            }
          }
        }
```

Note: the `evt.pointerId` references and `canvas.releasePointerCapture` need to match the existing handler's parameter naming. If the existing handler uses a different pointer-event variable name, adapt accordingly. The Task 1 touch-input commit `2e17601` established the Pointer Events handlers; this code lives in that block.

- [ ] **Step 3: Wire close-on-playfield-tap**

In the same pointerdown handler, in the playfield branch (the existing code that handles taps inside the playfield), at the very top add:

```js
      // If info panel is open, close it on any playfield tap.
      if (ui.shopMode === 'info') {
        ui.shopMode = 'list';
        ui.shopInfoKind = null;
      }
```

- [ ] **Step 4: Wire Escape key to close info panel**

Find the existing keydown handler (search for `window.addEventListener('keydown'` or `addEventListener('keydown'`). Inside, add an Escape handler:

```js
    if (e.key === 'Escape') {
      if (ui.shopMode === 'info') {
        ui.shopMode = 'list';
        ui.shopInfoKind = null;
        e.preventDefault();
        return;
      }
    }
```

- [ ] **Step 5: Visual verification**

Refresh. Confirm:

- Tapping "?" on a Howitzer card flips the right column to an info panel showing Howitzer name, family, stats, fallback description, and 3 upgrade rows (Siege, Mortar, Discus) with stats and fallback description.
- "BACK TO CANNONS" link at the top closes the panel.
- Tapping anywhere outside the panel (inside playfield) also closes it.
- Pressing Escape closes it.
- Repeating with Repeater, Twin Gun, Pink Pea each shows the right info panel.

Encoding:

```bash
curl -s http://localhost/dariobimbi.com/demos/ant-buster/ | grep -c '&#038;&#038;'
```

Expected: `0`.

- [ ] **Step 6: Commit**

```bash
git add patterns/demo-ant-buster.php
git commit -m "$(cat <<'EOF'
feat(ant-buster): visual coherence Task 10 — info panel drill-down

? hangtag opens an info panel for the tapped cannon. Panel shows name, family,
stats, description with family/tier fallback, and three upgrade rows each
carrying stats and a one-line description. Back link, playfield-tap, and
Escape all close. Word-wrap helper drawWrappedText supports the description
card.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

### Task 11: Detail panel restyle

**Files:**
- Modify: `patterns/demo-ant-buster.php` (replace bodies of `function drawTowerPanel(c)` at line ~1548 and `function drawTargetingRow(c, px, py)` at line ~1484)

Detail panel reuses the info-panel format and adds the targeting row, kills stat, and red sell button.

- [ ] **Step 1: Replace `drawTargetingRow()`**

```js
  function drawTargetingRow(c, px, py) {
    var def = CANNON_KINDS[c.kind];
    if (def.aura) {
      ui.targetingRects = [];
      return py;
    }
    var modes = ['closest', 'first', 'last', 'strongest', 'weakest'];
    var glyphs = ['C', 'F', 'L', 'S', 'W'];
    var modeNames = ['Closest', 'First', 'Last', 'Strongest', 'Weakest'];
    var modeColors = ['#4a6acc', '#c8a040', '#888', '#c84444', '#c84090'];
    var btnW = 22;
    var btnH = 22;
    var gap = 4;

    var active = c.targetingMode;
    if (!active) { active = 'closest'; }

    ui.targetingRects = [];
    for (var i = 0; i < 5; i++) {
      var bx = px + i * (btnW + gap);
      var by = py;
      var isActive = (modes[i] === active);
      drawWoodenButton(bx, by, btnW, btnH, 'wood');
      if (isActive) {
        // Mode-color bar along the bottom edge
        ctx.fillStyle = modeColors[i];
        ctx.fillRect(bx + 2, by + btnH - 6, btnW - 4, 4);
      }
      var glyphColor;
      if (isActive) { glyphColor = '#fee761'; } else { glyphColor = '#f5e6c8'; }
      drawTextWithShadow(glyphs[i], bx + btnW / 2, by + btnH / 2, glyphColor, 'bold 11px monospace', 'center');
      ui.targetingRects.push({ x: bx, y: by, w: btnW, h: btnH });
    }

    var labelY = py + btnH + 14;
    var activeIdx = 0;
    for (var li = 0; li < modes.length; li++) {
      if (modes[li] === active) { activeIdx = li; break; }
    }
    drawTextWithShadow('TARGETING ' + modeNames[activeIdx].toUpperCase(), px, labelY, '#c8a868', 'bold 8px monospace', 'start');
    return labelY + 8;
  }
```

- [ ] **Step 2: Replace `drawTowerPanel(c)`**

```js
  function drawTowerPanel(c) {
    var sk = CANNON_KINDS[c.kind];
    var px = PLAYFIELD_W + 6;
    var pw = SHOP_W - 12;
    var py = 26;

    drawShopBackground();

    // Back link
    drawTextWithShadow('< BACK TO CANNONS', px + pw / 2, py + 6, '#c8a868', 'bold 8px monospace', 'center');

    // Name
    drawTextWithShadow(sk.name, px + 4, py + 28, '#fee761', 'bold 14px monospace', 'start');
    drawTextWithShadow(sk.family + ' family', px + 4, py + 42, '#c8a868', 'bold 8px monospace', 'start');

    // Stats row — 4 columns including KILLS.
    var sy = py + 50;
    drawWoodenTag(px + 2, sy, pw - 4, 26);
    var col = (pw - 4) / 4;
    var killCount;
    if (c.kills) { killCount = c.kills; } else { killCount = 0; }
    var labels = ['DMG', 'RATE', 'RNG', 'KILLS'];
    var values = ['' + sk.damage, '' + sk.fireRate, '' + sk.range, '' + killCount];
    var valColors = ['#f5e6c8', '#f5e6c8', '#f5e6c8', '#fee761'];
    for (var i = 0; i < 4; i++) {
      var cx = px + 2 + col * i + col / 2;
      drawTextWithShadow(labels[i], cx, sy + 8, '#c8a868', 'bold 7px monospace', 'center');
      drawTextWithShadow(values[i], cx, sy + 19, valColors[i], 'bold 10px monospace', 'center');
    }

    // Targeting row + label
    var afterTargetingY = drawTargetingRow(c, px + 4, py + 86);

    // Upgrade rows (same format as info panel) — but tappable.
    var upgrades = sk.upgradesTo;
    if (!upgrades) { upgrades = []; }
    var hoveredLockedRect = null;
    var upgradeBaseY = afterTargetingY + 4;
    ui.upgradeRects = [null, null, null];
    for (var i2 = 0; i2 < upgrades.length; i2++) {
      var uk = upgrades[i2];
      var udef = CANNON_KINDS[uk];
      if (!udef) { continue; }
      var rh = 32;
      var by = upgradeBaseY + i2 * (rh + 3);
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

      // Wooden frame, gold tint for god-tier
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

      // Top line
      drawTextWithShadow('->', px + 6, by + 8, '#fee761', 'bold 10px monospace', 'start');
      drawTextWithShadow(udef.name, px + 18, by + 8, '#fee761', 'bold 10px monospace', 'start');
      ctx.drawImage(game.sprites.coin, px + pw - 26, by + 3);
      var costColor;
      if (canAfford) { costColor = '#fee761'; } else { costColor = '#888'; }
      drawTextWithShadow('' + udef.cost, px + pw - 6, by + 8, costColor, 'bold 10px monospace', 'end');
      // Stats line
      drawTextWithShadow('DMG ' + udef.damage + '  RATE ' + udef.fireRate + '  RNG ' + udef.range, px + 6, by + 22, '#f5e6c8', 'bold 8px monospace', 'start');

      ctx.restore();

      ui.upgradeRects[i2] = { x: px + 2, y: by, w: pw - 4, h: rh, toKind: uk, locked: isLocked };

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

    // Sell button — pinned near the bottom
    var refund = sellRefund(c);
    var sbW = pw - 4;
    var sbH = 22;
    var sbX = px + 2;
    var sbY = HEIGHT - sbH - 6;
    drawWoodenButton(sbX, sbY, sbW, sbH, 'red');
    drawTextWithShadow('Sell · +' + refund, sbX + sbW / 2, sbY + sbH / 2, '#fee761', 'bold 10px monospace', 'center');
    ui.sellRect = { x: sbX, y: sbY, w: sbW, h: sbH };
  }
```

Note: the existing `ui.sellRect` may already exist; if so, the assignment above just updates its values, which is fine. If not, declare `sellRect: null` in the `ui` object literal added in Task 9.

- [ ] **Step 3: Visual verification**

Place a Howitzer, then tap it to select. Confirm:

- Detail panel shows name, family, 4-stat row including KILLS in gold.
- Targeting row: 5 wooden buttons C/F/L/S/W. Active button has a colored bar at its bottom edge.
- "TARGETING CLOSEST" label below the row.
- Three upgrade rows (Siege, Mortar, Discus) with name, cost-with-coin, stats line.
- Tapping each targeting button updates the active mode and color bar.
- Tapping an affordable upgrade commits and swaps the cannon kind.
- Locked god-tier upgrade (after upgrading enough to reach a final-tier cannon) shows 50% alpha + "Unlocks at wave 15" tooltip on hover.
- Red sell button at the bottom shows "Sell · +N" with the correct refund.
- Tapping sell removes the cannon and returns gold.

Encoding:

```bash
curl -s http://localhost/dariobimbi.com/demos/ant-buster/ | grep -c '&#038;&#038;'
```

Expected: `0`.

- [ ] **Step 4: Commit**

```bash
git add patterns/demo-ant-buster.php
git commit -m "$(cat <<'EOF'
feat(ant-buster): visual coherence Task 11 — detail panel

drawTowerPanel rewritten using the wooden-chrome primitives. Adds KILLS to a
4-column stats row, re-themes the targeting buttons as wooden chips with a
mode-color bar on the active one, and styles the upgrade rows like info-panel
rows (name + cost-with-coin + stats line). God-tier rows render in dark gold
with the existing unlock-pulse halo retained. Sell button becomes a red
wooden button reading "Sell · +Ng".

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

### Task 12: Cannon descriptions content

**Files:**
- Modify: `patterns/demo-ant-buster.php` (add `description` field to 16 entries in `CANNON_KINDS`)

Adds one-sentence descriptions for the 4 base and 12 mid-tier cannons. Final and god-tier descriptions remain absent and the panel falls back to "<family> <tier>" text.

- [ ] **Step 1: Add base descriptions**

Find the `CANNON_KINDS` block. For each of the 4 base cannons, add a `description:` line. The exact line numbers depend on your file but the entries are identifiable by their `name`.

For Howitzer (likely first entry, family 'heavy'):

```js
      description: 'Slow, hard-hitting single shot. The foundation of the heavy line.',
```

For Repeater (family 'quick'):

```js
      description: 'Light, fast pellets. Trades damage per shot for fire rate.',
```

For Twin Gun (family 'status'):

```js
      description: 'Two barrels firing alternately. Each hit can apply a status effect.',
```

For Pink Pea (family 'rubber'):

```js
      description: 'Bouncy rubber pellet. Casual cousin of the heavy line, no gating.',
```

- [ ] **Step 2: Add mid-tier heavy descriptions**

For Siege:

```js
      description: 'Harder hit, longer reach. Same slow rhythm as the Howitzer.',
```

For Mortar:

```js
      description: 'Lobs a shell that splashes on impact. Hits clusters.',
```

For Discus:

```js
      description: 'Bouncing disc. Hits the same ant up to three times.',
```

- [ ] **Step 3: Add mid-tier quick descriptions**

For Autocannon:

```js
      description: 'Higher fire rate than the Repeater. Sustained pressure.',
```

For Vulcan:

```js
      description: 'Spins up to a punishing fire rate. Pays off on long lanes.',
```

For Plasma Torch:

```js
      description: 'Cone of plasma that burns over time. Soft on armor.',
```

For Sharpshooter:

```js
      description: 'Long-range precision shot. Picks off targets at the back.',
```

- [ ] **Step 4: Add mid-tier status descriptions**

For Triple Threat:

```js
      description: 'Three barrels, three status hits per volley.',
```

For Toxin:

```js
      description: 'Inflicts poison. Damage continues after the shot lands.',
```

For Frost:

```js
      description: 'Slows ants on hit. Pairs with heavy cannons for shatter combos.',
```

- [ ] **Step 5: Add mid-tier rubber descriptions**

For Bouncy Bubble:

```js
      description: 'Round bouncing pellet. Wider knockback than Pink Pea.',
```

For Rubber Wrecking Ball:

```js
      description: 'Heavy bouncing payload. Big knockback, slow recovery.',
```

(Verify these exact mid-tier names exist in your CANNON_KINDS by searching for them. If a different mid name appears in the actual data, use the actual name and write a description that fits the cannon's behavior. The 12 mid-tier cannons are the ones with `tier: 'mid'`.)

- [ ] **Step 6: Visual verification**

Refresh. For each of the four base cannons, tap the "?" hangtag and confirm:

- Howitzer info panel description reads "Slow, hard-hitting single shot. The foundation of the heavy line."
- Other base cannons show their respective descriptions.
- Each upgrade row shows its mid-tier description in the third line.
- Cannons that did NOT get descriptions (final, god) still render the panel correctly with the family/tier fallback text.

Encoding:

```bash
curl -s http://localhost/dariobimbi.com/demos/ant-buster/ | grep -c '&#038;&#038;'
```

Expected: `0`.

- [ ] **Step 7: Commit**

```bash
git add patterns/demo-ant-buster.php
git commit -m "$(cat <<'EOF'
feat(ant-buster): visual coherence Task 12 — cannon descriptions

Add description field to the 4 base and 12 mid-tier cannons. Final and god
tiers fall back to "<family> <tier>" via the existing fallback in the info
panel and detail panel renderers.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

### Task 13: Theme version bump

**Files:**
- Modify: `style.css` (header version line)

Bumps the theme version from 0.14.0 to 0.15.0 so the pattern cache invalidates and the new chrome lands on the live page.

- [ ] **Step 1: Bump the version**

Open `style.css`. Locate the `Version:` header line near the top. Change:

```css
Version: 0.14.0
```

to:

```css
Version: 0.15.0
```

- [ ] **Step 2: Visual verification**

Refresh. Confirm:

- The page renders. The version bump is what makes the new pattern code take effect on the live page (per the CLAUDE.md note about pattern caching).
- All 17 items in the spec's manual test checklist (lines 274-292 of the spec) pass.

Encoding:

```bash
curl -s http://localhost/dariobimbi.com/demos/ant-buster/ | grep -c '&#038;&#038;'
```

Expected: `0`.

- [ ] **Step 3: Commit**

```bash
git add style.css
git commit -m "$(cat <<'EOF'
chore(ant-buster): bump theme to 0.15.0 for visual coherence pass

Invalidates the WordPress pattern cache so the rewritten HUD/shop/splash/info
panel chrome renders on live page loads. Closes the visual coherence pass.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

- [ ] **Step 4: Final verification against the 17-item checklist**

Walk every item in the spec's "Manual test checklist" section (lines 274-292 of the spec doc). For each:

1. Title screen renders correctly (cake, sign, ants).
2. HUD readouts update during play.
3. HUD scales cleanly at 320px and 1280px wide.
4. Shop sidebar renders and arming works.
5. Cant-afford state desaturates cost only.
6. Info hangtag opens info panel; back/playfield/Escape all close.
7. Info panel content correct.
8. Detail panel correct on selected cannon.
9. Targeting buttons respond.
10. Upgrade buttons commit affordable upgrades.
11. Sell button works.
12. Win and lose overlays trigger correctly.
13. Pause overlay works.
14. Wave intro and tutorial sticky render at the right times.
15. Inline-script: no `&#038;&#038;` in served HTML.
16. Voice/copy: no em-dashes anywhere user-facing.
17. Theme version bumped.

Document any failures here, then fix and re-test before considering the branch done.

---

## Self-Review Notes

After writing this plan, the spec was checked against it section by section:

**Spec coverage:**

- HUD redesign (spec section "HUD redesign"): Task 3.
- Splash overlays (spec section "Splash overlays"): Tasks 4, 5, 6, 7.
- Wave intro and tutorial sticky (spec section "Wave intro banner" + "Tutorial hint sticky"): Task 8.
- Shop sidebar (spec section "Shop sidebar"): Task 9.
- Info panel feature (spec section "Info panel"): Task 10.
- Detail panel (spec section "Selected-cannon detail panel"): Task 11.
- Cannon descriptions content (spec section "Content: cannon descriptions"): Task 12.
- New asset specifications (spec section "New asset specifications"): Task 2 (downgraded from PNG to inline sprite functions per spec's allowance).
- Color and typography palette (spec section "Color and typography palette"): exercised across Tasks 1-11.
- Voice and copy (spec section "Voice and copy"): all copy in Tasks 3-12 matches the spec's copy table.
- Behavior changes summary (spec section "Behavior changes summary"): Lost removal in Task 3, info panel state in Tasks 9-10.
- Theme version bump: Task 13.

**Type consistency:** every helper signature is consistent across tasks. `drawWoodenSign(x, y, w, h)`, `drawWoodenButton(x, y, w, h, variant)`, `drawWoodenTag(x, y, w, h)`, `drawTextWithShadow(text, x, y, color, font, align)`, `drawSplashAnt(x, y, size, rot)` — all match in every task that calls them.

**Sprite consistency:** `game.sprites.coin`, `game.sprites.cakeSlice`, `game.sprites.titleCake` introduced in Task 2; used in Tasks 3 (HUD coin/cake), 4 (titleCake), 9-11 (coin in cost rows).

**State field consistency:** `ui.shopMode` ('list' | 'info'), `ui.shopInfoKind`, `ui.shopInfoRects`, `ui.shopInfoBackRect`, `ui.idlePlayRect`, `ui.sellRect` — all introduced in Task 9 or 10 and consumed in Task 11 and the click handlers.
