# Ant Buster Plan 7 — Per-Tower Targeting Priority

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a per-cannon targeting priority system to Ant Buster — five modes (Closest, First, Last, Strongest, Weakest) selectable from a button row in the selected-tower panel — so the player can express tactical decisions like "Reaper picks Strongest, Bombers pick Closest".

**Architecture:** Single new field `cannon.targetingMode` (default `'closest'`) consumed by a refactored `findTarget(cannon)` that filters in-range candidates, applies a stealth filter when any are revealed, then picks one ant by mode. Five new UI rects in `ui.targetingRects` wire into the existing mousedown handler the same way upgrade buttons do. A neutral white-dashed target line and 4 px reticle overlay confirm the picked target visually when a cannon is selected. No path-progress, no sentinel, no King-specific code path: distance-to-cake handles every ant uniformly because Ant Buster's ants do not follow waypoints.

**Tech Stack:** Vanilla JS IIFE inside PHP (`patterns/demo-ant-buster.php`), Canvas 2D rendering, no test framework. Verification is via PHP lint, hygiene grep against served HTML for HTML-encoded operators (`&#038;&#038;`), and Playwright probes against the live demo at `http://localhost/dariobimbi.com/demos/ant-buster/?debug=1`.

**Spec reference:** [`docs/superpowers/specs/2026-05-08-ant-buster-plan-7-design.md`](../specs/2026-05-08-ant-buster-plan-7-design.md) at commit `aeee89c`.

**Branch base:** `main` at commit `aeee89c` (Plan 7 spec + spec fixup; Plan 6 merge `c8d4eb2` is the last code change).

**Branch name:** `ant-buster-plan-7`

**Theme version target:** 0.13.0 (current 0.12.0).

---

## File Structure

Plan 7 modifies two files. No new files.

| File | What changes |
|---|---|
| `wp-content/themes/dariobimbi/patterns/demo-ant-buster.php` | Cannon instance gains `targetingMode`. `findTarget` rewritten to filter-then-pick. New `drawTargetingRow` + `drawTargetingOverlay` functions. `drawTowerPanel` grows by 32 px to accommodate the row. Mousedown handler gains a hit-test for the new buttons. `resetGame` clears `ui.targetingRects`. |
| `wp-content/themes/dariobimbi/style.css` | Theme `Version:` 0.12.0 → 0.13.0. |

The `patterns/demo-ant-buster.php` file is ~5000 lines. Plan 7's net diff is approximately +120 lines. All edits are co-located with their domain (cannon-state edits near `placeCannon`, render edits near `drawTowerPanel`, click-handling edits near the existing mousedown handler).

---

## Conventions

These apply to every code step in this plan. They come from `CLAUDE.md` and the existing codebase, and they are non-negotiable inside `<script>` blocks served as inline JS by WordPress.

- **`var` only.** No `let` or `const`. The existing IIFE uses `var` exclusively.
- **No `&&` and no `||`.** WordPress's content filter occasionally HTML-encodes `&&` to `&#038;&#038;`. `||` is suspect by association. Use nested `if` statements or helper variables. Every existing function in `findTarget`, `applyDamage`, `updateAnt` follows this rule.
- **`function () { ... }` style.** No arrow functions. Function declarations are hoisted and safe.
- **No `Array.prototype.filter` / `.reduce` chains.** Use single-pass `for` loops with index counters. The existing `findTarget` and `updateAnt` use this pattern.
- **No `Math.pow(x, 2)`.** The codebase convention is `dx * dx + dy * dy` for squared distance.
- **King is identified by `ant.kind === 'king'`.** There is no `ant.isKing` flag.
- **Dead-ant filter uses `ant.dead` flag**, not `ant.hp <= 0`. The flag is the canonical signal and avoids a brief mid-frame race.
- **Squared distances throughout.** No `Math.sqrt` calls in `findTarget` (existing convention; sqrt is unnecessary for ranking).

---

## Pre-flight

- [ ] **Step 1: Confirm baseline state on `main`**

```bash
git status
git log --oneline -5
```

Expected:
- Working tree clean.
- Top of log shows `aeee89c docs(ant-buster): Plan 7 spec fixup` and `e75ec3b docs(ant-buster): Plan 7 design spec`.

- [ ] **Step 2: Create the feature branch**

```bash
git checkout -b ant-buster-plan-7
```

Expected: switched to a new branch `ant-buster-plan-7`.

- [ ] **Step 3: Sanity-check the demo loads cleanly**

Navigate Playwright to `http://localhost/dariobimbi.com/demos/ant-buster/?debug=1`. Probe:

```js
window.__ab !== undefined
```

Expected: `true`. Confirms the live demo's debug global is present and the IIFE has run without parse errors.

- [ ] **Step 4: Sanity-check current findTarget behavior**

Probe (still in the live demo):

```js
(function () {
  // Place a worker right under any existing cannon, then check its target.
  // No cannons are placed at game start, so this probe just confirms findTarget
  // is callable. Real per-mode tests come in Task 2.
  return typeof window.__ab.game !== 'undefined';
})()
```

Expected: `true`.

---

## Task 1: Add `targetingMode` to cannon instance

**Files:**
- Modify: `wp-content/themes/dariobimbi/patterns/demo-ant-buster.php` at `placeCannon` (line 3584-3603)

The cannon-instance object literal in `placeCannon` is the single source of truth for what fields a cannon has. Adding `targetingMode: 'closest'` here gives every newly-placed cannon a default mode. `upgradeCannon` (line 1115) does NOT need any change because it mutates the cannon in place (`c.kind = toKind`) rather than constructing a new object — the `targetingMode` field is preserved automatically.

- [ ] **Step 1: Pre-flight probe — confirm placeCannon currently produces no targetingMode field**

In the live demo:

```js
(function () {
  // Drag-place a Pea Shooter at (200, 200) via the existing input pipeline.
  // Spec §10 task 1 expects targetingMode field to NOT exist before the change.
  var ui = window.__ab.ui;
  var game = window.__ab.game;
  // Force a cannon into place by calling placeCannon directly via the IIFE escape.
  // Note: placeCannon is not exposed; we read game.cannons after a normal place.
  // For pre-flight, just snapshot the existing state.
  return {
    cannonCount: game.cannons.length,
    hasTargetingMode: game.cannons.length > 0 ? ('targetingMode' in game.cannons[0]) : null
  };
})()
```

Expected: `{ cannonCount: 0, hasTargetingMode: null }` on a fresh page (no cannons placed yet). If a cannon is placed via the UI before this probe runs, expected becomes `{ cannonCount: 1, hasTargetingMode: false }`.

- [ ] **Step 2: Edit `placeCannon` to add the targetingMode field**

Locate `placeCannon` at line 3584. Edit the cannon-instance object literal:

```js
// Before (current state):
game.cannons.push({
  kind: kindKey,
  kindHistory: [kindKey],   // base kind included per spec SYS-2
  x: x,
  y: y,
  heading: 0,
  cooldown: 0,
  target: null,
  kills: 0,
  disabled: false,
  disabledUntil: 0
});

// After (Plan 7):
game.cannons.push({
  kind: kindKey,
  kindHistory: [kindKey],   // base kind included per spec SYS-2
  x: x,
  y: y,
  heading: 0,
  cooldown: 0,
  target: null,
  kills: 0,
  disabled: false,
  disabledUntil: 0,
  targetingMode: 'closest'  // Plan 7 — per-cannon targeting dial
});
```

- [ ] **Step 3: PHP lint**

```bash
"c:/wamp64/bin/php/php8.2.29/php.exe" -l wp-content/themes/dariobimbi/patterns/demo-ant-buster.php
```

Expected: `No syntax errors detected in ...demo-ant-buster.php`.

- [ ] **Step 4: Hygiene grep against served HTML**

```bash
curl -s http://localhost/dariobimbi.com/demos/ant-buster/ | grep -c "&#038;&#038;"
```

Expected: `0`. (No HTML-encoded `&&` operators anywhere in the served page.)

- [ ] **Step 5: Playwright probe — verify default and upgrade preservation**

Reload the demo. Drag-place any cannon (e.g., Pea Shooter at canvas coords). Then probe:

```js
(function () {
  var game = window.__ab.game;
  var c = game.cannons[0];
  return {
    hasField: 'targetingMode' in c,
    defaultValue: c.targetingMode
  };
})()
```

Expected: `{ hasField: true, defaultValue: 'closest' }`.

Then upgrade the cannon once via the UI. Probe again:

```js
(function () {
  var game = window.__ab.game;
  var c = game.cannons[0];
  // Mutate the field, simulate an upgrade by calling upgradeCannon-equivalent
  // via the UI is the manual step; here we just verify the field survives
  // any mutation that doesn't explicitly reset it.
  c.targetingMode = 'strongest';  // simulate user's mode change
  return c.targetingMode;
})()
```

Expected: `'strongest'`. Then upgrade through the UI, and confirm:

```js
window.__ab.game.cannons[0].targetingMode === 'strongest'
```

Expected: `true`. (This verifies the §2.1 spec requirement that mode survives upgrade — automatic in this codebase because `upgradeCannon` mutates in place.)

- [ ] **Step 6: Commit**

```bash
git add wp-content/themes/dariobimbi/patterns/demo-ant-buster.php
git commit -m "$(cat <<'EOF'
feat(ant-buster): cannon.targetingMode default closest (Plan 7 Task 1)

Adds targetingMode field to placeCannon's cannon-instance literal,
defaulting to 'closest' which matches existing findTarget behavior.
upgradeCannon needs no change because it mutates the cannon in place,
preserving the field automatically. Wires the player-facing dial that
Tasks 2-6 will consume.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 2: Refactor `findTarget` with five modes

**Files:**
- Modify: `wp-content/themes/dariobimbi/patterns/demo-ant-buster.php` at `findTarget` (line 3605-3631)

Replace the existing simple "first revealed or nearest" loop with a filter-then-pick pattern. Stealth becomes a primary filter (any revealed → revealed-only pool), then the targeting mode picks within the active pool. Distance-to-cake replaces the originally-considered pathProgress because Ant Buster's ants do not follow waypoints.

- [ ] **Step 1: Pre-flight probe — confirm current findTarget picks nearest**

Drag-place a cannon and a Worker into range. Probe:

```js
(function () {
  var game = window.__ab.game;
  if (game.cannons.length === 0) { return 'no cannons'; }
  if (game.ants.length === 0) { return 'no ants'; }
  var c = game.cannons[0];
  // Force-call findTarget by reading c.target after one update tick.
  // The existing function is internal; we observe its result via c.target.
  return {
    cannonAt: { x: c.x, y: c.y },
    targetAt: c.target ? { x: c.target.x, y: c.target.y, kind: c.target.kind } : null
  };
})()
```

Expected: `c.target` points at whichever Worker is spatially nearest (current `findTarget` behavior).

- [ ] **Step 2: Replace the `findTarget` function body**

Locate `findTarget(cannon)` at line 3605. Replace the entire function with the new implementation. The new version is single-pass (filter), single-pass (stealth), single-pass (pick), and follows every convention in the Conventions section.

```js
function findTarget(cannon) {
  var def = CANNON_KINDS[cannon.kind];
  var rangeSq = def.range * def.range;
  var mode = cannon.targetingMode;
  if (!mode) { mode = 'closest'; }

  // Filter pass: gather candidates with cached distSq, flag if any revealed.
  // Avoids and-operator chains by using nested ifs (CLAUDE.md inline-script gotcha).
  var candidates = [];
  var anyRevealed = false;
  for (var i = 0; i < game.ants.length; i++) {
    var a = game.ants[i];
    if (a.dead) { continue; }
    if (a.intangible) { continue; }
    var dx = a.x - cannon.x;
    var dy = a.y - cannon.y;
    var distSq = dx * dx + dy * dy;
    if (distSq > rangeSq) { continue; }
    candidates.push({ ant: a, distSq: distSq });
    if (a.isRevealed) { anyRevealed = true; }
  }
  if (candidates.length === 0) { return null; }

  // Stealth filter: if any revealed, keep only revealed ants. Preserves the
  // existing Hawkeye apex-priority while letting the mode pick within that pool.
  var pool = candidates;
  if (anyRevealed) {
    pool = [];
    for (var j = 0; j < candidates.length; j++) {
      if (candidates[j].ant.isRevealed) { pool.push(candidates[j]); }
    }
  }

  // Pick pass: apply the targeting mode to the active pool.
  var best = pool[0];
  for (var k = 1; k < pool.length; k++) {
    var cur = pool[k];
    if (mode === 'closest') {
      if (cur.distSq < best.distSq) { best = cur; }
    } else if (mode === 'strongest') {
      if (cur.ant.hp > best.ant.hp) { best = cur; }
    } else if (mode === 'weakest') {
      if (cur.ant.hp < best.ant.hp) { best = cur; }
    } else {
      // first or last: compare squared distance to cake.
      var ccdx = cur.ant.x - game.cake.x;
      var ccdy = cur.ant.y - game.cake.y;
      var bcdx = best.ant.x - game.cake.x;
      var bcdy = best.ant.y - game.cake.y;
      var cToCake = ccdx * ccdx + ccdy * ccdy;
      var bToCake = bcdx * bcdx + bcdy * bcdy;
      if (mode === 'first') {
        if (cToCake < bToCake) { best = cur; }
      } else if (mode === 'last') {
        if (cToCake > bToCake) { best = cur; }
      }
    }
  }
  return best.ant;
}
```

- [ ] **Step 3: PHP lint**

```bash
"c:/wamp64/bin/php/php8.2.29/php.exe" -l wp-content/themes/dariobimbi/patterns/demo-ant-buster.php
```

Expected: `No syntax errors detected`.

- [ ] **Step 4: Hygiene grep**

```bash
curl -s http://localhost/dariobimbi.com/demos/ant-buster/ | grep -c "&#038;&#038;"
```

Expected: `0`.

- [ ] **Step 5: Playwright probes — verify each mode**

Reload the demo. The probes below place ants and cannons programmatically by manipulating `game.ants` and `game.cannons` directly (the IIFE has no public placement API for tests, but `window.__ab.game` is mutable in debug mode).

**Probe 5a — closest mode picks nearest cannon-distance:**

```js
(function () {
  var game = window.__ab.game;
  // Place cannon at (200, 200), Workers at (210, 200), (220, 200), (300, 200).
  // Find a Pea Shooter kind and place via internal function if available; if
  // not, the implementer should drag-place via UI and read positions.
  // The tester probe assumes one cannon at game.cannons[0] and three ants.
  if (game.cannons.length < 1) { return 'place a cannon first'; }
  if (game.ants.length < 3) { return 'spawn at least 3 ants in range'; }
  var c = game.cannons[0];
  c.targetingMode = 'closest';
  c.cooldown = 0;
  // Force one update tick to rerun findTarget.
  // In live game, the next animation frame will refresh c.target.
  // Wait one frame:
  return new Promise(function (resolve) {
    requestAnimationFrame(function () {
      requestAnimationFrame(function () {
        var t = c.target;
        if (!t) { resolve(null); return; }
        var dx = t.x - c.x;
        var dy = t.y - c.y;
        resolve({ targetDistSq: dx * dx + dy * dy, targetKind: t.kind });
      });
    });
  });
})()
```

Expected: `targetDistSq` is the smallest squared distance among all in-range ants.

**Probe 5b — first mode picks cake-closest ant:**

```js
(function () {
  var game = window.__ab.game;
  if (game.cannons.length < 1) { return 'place a cannon first'; }
  var c = game.cannons[0];
  c.targetingMode = 'first';
  c.cooldown = 0;
  return new Promise(function (resolve) {
    requestAnimationFrame(function () {
      requestAnimationFrame(function () {
        var t = c.target;
        if (!t) { resolve(null); return; }
        var dx = t.x - game.cake.x;
        var dy = t.y - game.cake.y;
        resolve({ targetCakeDistSq: dx * dx + dy * dy });
      });
    });
  });
})()
```

Expected: `targetCakeDistSq` is the smallest squared distance from any in-range ant to `game.cake.x, game.cake.y`.

**Probe 5c — strongest mode picks highest HP:**

```js
(function () {
  var game = window.__ab.game;
  var c = game.cannons[0];
  c.targetingMode = 'strongest';
  c.cooldown = 0;
  return new Promise(function (resolve) {
    requestAnimationFrame(function () {
      requestAnimationFrame(function () {
        resolve(c.target ? { hp: c.target.hp, kind: c.target.kind } : null);
      });
    });
  });
})()
```

Expected: `hp` is the maximum HP among all in-range ants.

**Probe 5d — weakest mode picks lowest HP:**

Same as 5c but `c.targetingMode = 'weakest'`. Expected: `hp` is the minimum HP among in-range ants.

**Probe 5e — last mode picks anthill-closest (i.e., furthest from cake):**

Same as 5b but `c.targetingMode = 'last'`. Expected: `targetCakeDistSq` is the maximum among in-range ants.

**Probe 5f — stealth override still applies:**

Spawn a Stalker (kind `'stalker'` if it exists, else `'carrier'`) and a non-revealed ant with higher HP. Place a Hawkeye in range to reveal the Stalker. Set the cannon to `'strongest'`. Expected: `c.target.kind === 'stalker'` (or whichever ant is revealed), even though it's not the highest-HP ant in range.

- [ ] **Step 6: Commit**

```bash
git add wp-content/themes/dariobimbi/patterns/demo-ant-buster.php
git commit -m "$(cat <<'EOF'
feat(ant-buster): findTarget filter-then-pick + 5 modes (Plan 7 Task 2)

Replaces the existing nearest-with-revealed-override loop with a three-pass
pattern: filter in-range alive non-intangible candidates, apply stealth
filter when any are revealed, then pick by targetingMode (closest, first,
last, strongest, weakest). First/Last use squared distance to game.cake;
strongest/weakest read ant.hp directly. Tie-breaker is iteration order.

No path-progress, no King sentinel: distance-to-cake handles every ant
uniformly because Ant Buster's ants do not follow waypoints. The King
becomes a Strongest magnet (3000 HP) and a First contender when near
the cake, both correct emergent behavior.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 3: drawTargetingRow + integration into drawTowerPanel

**Files:**
- Modify: `wp-content/themes/dariobimbi/patterns/demo-ant-buster.php` at `drawTowerPanel` (line 1138-1259)
- Modify: `wp-content/themes/dariobimbi/patterns/demo-ant-buster.php` at `ui` object literal (line 4888-4901), add `targetingRects: []`

The targeting row sits between the cannon stats (KILLS at py + 66) and the upgrade buttons (originally py + 90). The buttons are 22×22 with 4 px gaps. The row label is below the buttons. To accommodate, the upgrade buttons shift down by 32 px (from py + 90 to py + 122) and the panel height grows from 200 to 232.

- [ ] **Step 1: Pre-flight probe — confirm panel currently has no targeting row**

Place any cannon, click to select it. Probe:

```js
(function () {
  var ui = window.__ab.ui;
  return {
    hasTargetingRects: 'targetingRects' in ui,
    targetingRectsValue: ui.targetingRects
  };
})()
```

Expected: `{ hasTargetingRects: false, targetingRectsValue: undefined }`.

- [ ] **Step 2: Add `targetingRects: []` to the `ui` object literal**

Locate the `ui` object at line 4888-4901. Add the field:

```js
// Before:
var ui = {
  dragging: null,
  mouseX: 0,
  mouseY: 0,
  sendNextRect: null,
  pauseRect: { x: 4, y: 4, w: 16, h: 16 },
  speedRect: { x: 24, y: 4, w: 24, h: 16 },
  restartRunRect: null,
  selectedCannon: null,
  upgradeRects: [null, null, null],
  hoveredUpgrade: null,
  sellRect: null,
  shopRects: []
};

// After (add targetingRects between sellRect and shopRects):
var ui = {
  dragging: null,
  mouseX: 0,
  mouseY: 0,
  sendNextRect: null,
  pauseRect: { x: 4, y: 4, w: 16, h: 16 },
  speedRect: { x: 24, y: 4, w: 24, h: 16 },
  restartRunRect: null,
  selectedCannon: null,
  upgradeRects: [null, null, null],
  hoveredUpgrade: null,
  sellRect: null,
  targetingRects: [],   // Plan 7 — five mode-button rects (or empty for aura cannons)
  shopRects: []
};
```

- [ ] **Step 3: Add `drawTargetingRow` helper function**

Insert this function definition immediately before `drawTowerPanel` at line 1138 (so it is hoisted before any caller):

```js
// Plan 7 — Targeting row: 5 buttons (C, F, L, S, W) + label below.
// Returns the y-coordinate immediately after the row, so drawTowerPanel
// can push the upgrade buttons down by the row's vertical extent.
// Sets ui.targetingRects to a 5-array of hit-test rects in mode order:
//   [closest, first, last, strongest, weakest].
function drawTargetingRow(c, px, py) {
  var def = CANNON_KINDS[c.kind];
  // Aura cannons (MP-N etc.) don't call findTarget, so the row is omitted.
  // Cone-DoT (Plasma Torch) and beam (Glacier, Solar Lance) DO call findTarget
  // and use targeting modes meaningfully, so they receive the row.
  if (def.aura) {
    ui.targetingRects = [];
    return py;
  }

  var modes = ['closest', 'first', 'last', 'strongest', 'weakest'];
  var glyphs = ['C', 'F', 'L', 'S', 'W'];
  var modeNames = ['Closest', 'First', 'Last', 'Strongest', 'Weakest'];
  var activeColors = ['#3a4a8c', '#9a7820', '#3a3a3a', '#7a2020', '#7a2040'];
  var inactiveBg = '#1a1a28';
  var btnW = 22;
  var btnH = 22;
  var gap = 4;
  var rowY = py;

  // Active mode for highlight selection. Falls back to 'closest' if unset.
  var active = c.targetingMode;
  if (!active) { active = 'closest'; }

  ui.targetingRects = [];
  for (var i = 0; i < 5; i++) {
    var bx = px + i * (btnW + gap);
    var by = rowY;
    var isActive = (modes[i] === active);

    // Background fill.
    if (isActive) {
      ctx.fillStyle = activeColors[i];
    } else {
      ctx.fillStyle = inactiveBg;
    }
    ctx.fillRect(bx, by, btnW, btnH);

    // Active-mode border (2 px white).
    if (isActive) {
      ctx.strokeStyle = '#ffffff';
      ctx.lineWidth = 2;
      ctx.strokeRect(bx + 1, by + 1, btnW - 2, btnH - 2);
      ctx.lineWidth = 1;
    }

    // Glyph (single bold character, centered).
    ctx.fillStyle = '#ffffff';
    ctx.font = 'bold 12px monospace';
    ctx.textAlign = 'center';
    ctx.fillText(glyphs[i], bx + btnW / 2, by + btnH / 2 + 4);
    ctx.textAlign = 'left';

    ui.targetingRects.push({ x: bx, y: by, w: btnW, h: btnH });
  }

  // Mode label below the row.
  var labelY = rowY + btnH + 12;
  var activeIdx = 0;
  for (var li = 0; li < modes.length; li++) {
    if (modes[li] === active) { activeIdx = li; break; }
  }
  ctx.fillStyle = '#cccccc';
  ctx.font = '10px monospace';
  ctx.fillText('Targeting: ' + modeNames[activeIdx], px, labelY);

  // Return y immediately past the label so the caller can place upgrade
  // buttons below it. 6 px breathing room.
  return labelY + 6;
}
```

- [ ] **Step 4: Modify `drawTowerPanel` to call `drawTargetingRow` and shift upgrades down**

Locate `drawTowerPanel` at line 1138. Two edits:

**Edit 4a — bump panel height from 200 to 232 (line 1144):**

```js
// Before:
ctx.fillRect(px, py, pw, 200);

// After:
ctx.fillRect(px, py, pw, 232);  // Plan 7 — +32 to fit targeting row
```

**Edit 4b — insert targeting row call between the stats block (line 1155) and the upgrade-buttons loop (line 1162). The new code computes a `targetingBottomY` that drives the upgrade-button Y offsets:**

```js
// Before (lines 1153-1167 region):
var killCount;
if (c.kills) { killCount = c.kills; } else { killCount = 0; }
ctx.fillText('KILLS ' + killCount, px + 6, py + 66);

// Upgrade buttons (up to 3) — only present when sk.upgradesTo is non-empty.
// Base cannons offer 3 mid options, mid cannons offer 2 finals, finals
// offer 1 god-tier (Plan 4). God-tier buttons render at 50% alpha with a
// tooltip when locked, full alpha when unlocked, and an additive gold
// halo for the first 0.5s after unlock fires (game.godTierUnlockPulseStart).
var upgrades = sk.upgradesTo;
if (!upgrades) { upgrades = []; }
var hoveredLockedRect = null;
for (var i = 0; i < upgrades.length; i++) {
  var uk = upgrades[i];
  var udef = CANNON_KINDS[uk];
  var by = py + 90 + i * 22;
```

```js
// After:
var killCount;
if (c.kills) { killCount = c.kills; } else { killCount = 0; }
ctx.fillText('KILLS ' + killCount, px + 6, py + 66);

// Plan 7 — Targeting row sits between stats and upgrades. Returns the
// y-coordinate where the next element can start. For aura cannons the row
// is omitted and the function returns the y it received, so upgrades sit
// where they did before Plan 7.
var afterTargetingY = drawTargetingRow(c, px + 6, py + 78);

// Upgrade buttons (up to 3) — only present when sk.upgradesTo is non-empty.
// Base cannons offer 3 mid options, mid cannons offer 2 finals, finals
// offer 1 god-tier (Plan 4). God-tier buttons render at 50% alpha with a
// tooltip when locked, full alpha when unlocked, and an additive gold
// halo for the first 0.5s after unlock fires (game.godTierUnlockPulseStart).
var upgrades = sk.upgradesTo;
if (!upgrades) { upgrades = []; }
var hoveredLockedRect = null;
// Plan 7 — upgradeBaseY shifts to accommodate the targeting row. For non-aura
// cannons, afterTargetingY is roughly py + 122. For aura cannons (no row),
// it's py + 78, which is below the original py + 90 — close enough that the
// upgrade buttons just sit slightly lower; the panel is now 232 tall, plenty
// of room. To preserve the original look on aura, we clamp to py + 90 minimum.
var upgradeBaseY = afterTargetingY;
if (upgradeBaseY < py + 90) { upgradeBaseY = py + 90; }
for (var i = 0; i < upgrades.length; i++) {
  var uk = upgrades[i];
  var udef = CANNON_KINDS[uk];
  var by = upgradeBaseY + i * 22;
```

**Edit 4c — also shift the sell button. Locate line 1251:**

```js
// Before:
var sy = py + 200 - 26;

// After:
var sy = py + 232 - 26;  // Plan 7 — panel height bumped to 232
```

- [ ] **Step 5: PHP lint**

```bash
"c:/wamp64/bin/php/php8.2.29/php.exe" -l wp-content/themes/dariobimbi/patterns/demo-ant-buster.php
```

Expected: `No syntax errors detected`.

- [ ] **Step 6: Hygiene grep**

```bash
curl -s http://localhost/dariobimbi.com/demos/ant-buster/ | grep -c "&#038;&#038;"
```

Expected: `0`.

- [ ] **Step 7: Playwright probes — verify panel rendering**

**Probe 7a — confirm targetingRects populated for normal cannon:**

Place a Pea Shooter, click to select. Probe:

```js
(function () {
  var ui = window.__ab.ui;
  return {
    rectCount: ui.targetingRects.length,
    firstRect: ui.targetingRects[0]
  };
})()
```

Expected: `rectCount: 5`, `firstRect` has `{ x, y, w: 22, h: 22 }`.

**Probe 7b — confirm targetingRects empty for MP-N (aura cannon):**

Place an MP-N (need to upgrade or place via debug if available). Click to select. Probe:

```js
window.__ab.ui.targetingRects.length
```

Expected: `0`.

**Probe 7c — confirm panel height grew:**

Visual: take a screenshot of the panel with a Pea Shooter selected. Confirm:
- The "C F L S W" button row is visible between KILLS and the first upgrade button.
- The "Targeting: Closest" label is visible below the buttons.
- The upgrade buttons are still readable, sell button is still at the bottom.
- No clipping at the panel's bottom edge.

- [ ] **Step 8: Commit**

```bash
git add wp-content/themes/dariobimbi/patterns/demo-ant-buster.php
git commit -m "$(cat <<'EOF'
feat(ant-buster): drawTargetingRow + drawTowerPanel integration (Plan 7 Task 3)

Adds drawTargetingRow helper that paints 5 buttons (C/F/L/S/W) with the
active mode highlighted in palette color (blue/gold/grey/red/pink) and a
2px white border. Single-line "Targeting: <ModeName>" label below.

Wired into drawTowerPanel after the KILLS stat. Panel height grew from
200 to 232 to accommodate. Upgrade button base-Y now follows the row's
returned bottom-Y, with a clamp to py+90 so aura cannons (no row) keep
their original layout. Sell button shifted from py+200-26 to py+232-26.

Aura cannons (def.aura) skip the row. Cone-DoT and beam cannons receive
it because they call findTarget meaningfully.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 4: Click handler hit-test for targeting buttons

**Files:**
- Modify: `wp-content/themes/dariobimbi/patterns/demo-ant-buster.php` at the mousedown handler (line 4968-4986)

The targeting buttons need to react to clicks. The handler pattern follows the existing upgrade-rects loop (line 4970-4978). Insert the targeting hit-test BEFORE the upgrade hit-test, so a misclick that lands in both ranges (impossible by design but defensive) prefers the targeting buttons.

- [ ] **Step 1: Pre-flight probe — confirm clicks on targeting buttons currently do nothing**

Place a Pea Shooter, click to select. Click on the C/F/L/S/W button area in the panel. Probe:

```js
window.__ab.game.cannons[0].targetingMode
```

Expected: `'closest'` (unchanged, because the click handler doesn't yet test the targeting rects).

- [ ] **Step 2: Insert targeting hit-test in the mousedown handler**

Locate the mousedown handler at line 4968. The current code reads:

```js
// Click inside the right-column panel while a cannon is selected — test
// the upgrade buttons (up to 3) then the sell button.
if (ui.selectedCannon) {
  if (p.x >= PLAYFIELD_W) {
    for (var ri = 0; ri < ui.upgradeRects.length; ri++) {
      var ur = ui.upgradeRects[ri];
      if (ur) {
        if (inRect(p, ur)) {
          upgradeCannon(ui.selectedCannon, ur.toKind);
          return;
        }
      }
    }
    if (ui.sellRect) {
      if (inRect(p, ui.sellRect)) {
        sellCannon(ui.selectedCannon);
        return;
      }
    }
  }
}
```

Insert the targeting hit-test loop after the `if (p.x >= PLAYFIELD_W) {` line and before the upgrade-rects loop:

```js
// Click inside the right-column panel while a cannon is selected — test
// the targeting buttons first (Plan 7), then upgrade buttons (up to 3),
// then the sell button.
if (ui.selectedCannon) {
  if (p.x >= PLAYFIELD_W) {
    // Plan 7 — Targeting row hit-test. Mode strings are positional and
    // must match the order in drawTargetingRow's `modes` array.
    if (ui.targetingRects) {
      var tModes = ['closest', 'first', 'last', 'strongest', 'weakest'];
      for (var ti = 0; ti < ui.targetingRects.length; ti++) {
        var trec = ui.targetingRects[ti];
        if (trec) {
          if (inRect(p, trec)) {
            ui.selectedCannon.targetingMode = tModes[ti];
            return;
          }
        }
      }
    }
    for (var ri = 0; ri < ui.upgradeRects.length; ri++) {
      var ur = ui.upgradeRects[ri];
      if (ur) {
        if (inRect(p, ur)) {
          upgradeCannon(ui.selectedCannon, ur.toKind);
          return;
        }
      }
    }
    if (ui.sellRect) {
      if (inRect(p, ui.sellRect)) {
        sellCannon(ui.selectedCannon);
        return;
      }
    }
  }
}
```

- [ ] **Step 3: PHP lint**

```bash
"c:/wamp64/bin/php/php8.2.29/php.exe" -l wp-content/themes/dariobimbi/patterns/demo-ant-buster.php
```

Expected: `No syntax errors detected`.

- [ ] **Step 4: Hygiene grep**

```bash
curl -s http://localhost/dariobimbi.com/demos/ant-buster/ | grep -c "&#038;&#038;"
```

Expected: `0`.

- [ ] **Step 5: Playwright probe — click each mode and verify**

Place a Pea Shooter, click to select. Read the targetingRects to find click coordinates:

```js
(function () {
  var rects = window.__ab.ui.targetingRects;
  return rects.map(function (r) { return { cx: r.x + r.w / 2, cy: r.y + r.h / 2 }; });
})()
```

Then for each (cx, cy) returned, click that pixel and verify the mode changed:

```js
// After clicking the second button (index 1, mode 'first'):
window.__ab.game.cannons[0].targetingMode
```

Expected: `'first'`. Cycle through all five buttons to confirm: `closest`, `first`, `last`, `strongest`, `weakest`.

**Probe 5b — visual highlight follows click:**

After clicking the "S" button, take a screenshot and confirm:
- The S button has the deep-red `#7a2020` background.
- The S button has a 2 px white border.
- The other four buttons have the panel-dark `#1a1a28` background.
- The label below reads `Targeting: Strongest`.

- [ ] **Step 6: Commit**

```bash
git add wp-content/themes/dariobimbi/patterns/demo-ant-buster.php
git commit -m "$(cat <<'EOF'
feat(ant-buster): targeting button click handler (Plan 7 Task 4)

Adds a hit-test loop in the mousedown handler that maps clicks on
ui.targetingRects to ui.selectedCannon.targetingMode = modes[index].
Inserted before the upgrade-rects loop so targeting always wins on
overlap (defensive; rects don't actually overlap by design).

The mode string array order matches drawTargetingRow's array exactly.
Both arrays are positional and must stay in lockstep.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 5: drawTargetingOverlay (target line + reticle)

**Files:**
- Modify: `wp-content/themes/dariobimbi/patterns/demo-ant-buster.php` at `drawShatterParticles` neighborhood (function definition near line 2406, render-loop call near line 1942)

Add a new render function that draws a faint dashed white line from the selected cannon to its current target, plus a 4 px reticle on the target. Wired into the render loop right after `drawShatterParticles()` so the overlay sits on top of ants and particles but under HUD elements.

- [ ] **Step 1: Pre-flight probe — confirm no overlay renders today**

Place a cannon, click to select. Visual check: no line is drawn from the cannon to its target. Probe:

```js
typeof window.__ab.drawTargetingOverlay
```

Expected: `'undefined'` (function does not exist yet — we're checking that we're not about to clobber an existing helper).

- [ ] **Step 2: Add `drawTargetingOverlay` function**

Insert this function definition immediately after `drawShatterParticles` (which ends around line 2418 — implementer should grep for the closing `}` of `drawShatterParticles` to find the exact line):

```js
// Plan 7 — Targeting overlay. When a cannon is selected, draws a faint
// dashed white line from the cannon to its current target and a 4 px
// reticle on the target. Gated on selectedCannon non-null and the cannon
// having a live target.
function drawTargetingOverlay() {
  if (!ui.selectedCannon) { return; }
  var c = ui.selectedCannon;
  if (!c.target) { return; }
  if (c.target.dead) { return; }

  ctx.save();
  ctx.strokeStyle = 'rgba(255, 255, 255, 0.4)';
  ctx.lineWidth = 1;
  ctx.setLineDash([3, 3]);
  ctx.beginPath();
  ctx.moveTo(c.x, c.y);
  ctx.lineTo(c.target.x, c.target.y);
  ctx.stroke();
  ctx.setLineDash([]);

  ctx.fillStyle = 'rgba(255, 255, 255, 0.7)';
  ctx.fillRect(Math.round(c.target.x) - 2, Math.round(c.target.y) - 2, 4, 4);
  ctx.restore();
}
```

- [ ] **Step 3: Wire `drawTargetingOverlay` into the render loop**

Locate the render-loop block at line 1941-1942:

```js
// Before:
drawAnts();                // 7. ants, sorted top-down by y
drawShatterParticles();    // 7b. cyan shatter particles (Plan 6 Brittle)
```

Add the overlay call immediately after `drawShatterParticles`:

```js
// After:
drawAnts();                // 7. ants, sorted top-down by y
drawShatterParticles();    // 7b. cyan shatter particles (Plan 6 Brittle)
drawTargetingOverlay();    // 7c. selected-cannon target line + reticle (Plan 7)
```

- [ ] **Step 4: PHP lint**

```bash
"c:/wamp64/bin/php/php8.2.29/php.exe" -l wp-content/themes/dariobimbi/patterns/demo-ant-buster.php
```

Expected: `No syntax errors detected`.

- [ ] **Step 5: Hygiene grep**

```bash
curl -s http://localhost/dariobimbi.com/demos/ant-buster/ | grep -c "&#038;&#038;"
```

Expected: `0`.

- [ ] **Step 6: Playwright probes — verify overlay**

**Probe 6a — overlay appears when cannon is selected with target:**

Place a cannon, spawn ants in range, click cannon to select. Take a screenshot and confirm:
- A faint white dashed line is drawn from the cannon to the current target.
- A 4 px white square sits on the target ant.
- Both elements are alpha-blended (not solid white).

**Probe 6b — overlay disappears when cannon is deselected:**

Click anywhere outside the cannon to deselect. Take a screenshot and confirm:
- No line drawn anywhere.
- No reticle on any ant.

**Probe 6c — overlay disappears when target dies:**

With a cannon selected and firing, wait for it to kill its target. Confirm the line and reticle disappear in the frame the target's `dead` flag flips.

**Probe 6d — overlay color is independent of mode:**

Switch the cannon's mode to `'strongest'`. Confirm the line stays white-dashed (no red tint). Switch to `'first'`. Confirm the line stays white (no gold tint).

- [ ] **Step 7: Commit**

```bash
git add wp-content/themes/dariobimbi/patterns/demo-ant-buster.php
git commit -m "$(cat <<'EOF'
feat(ant-buster): drawTargetingOverlay - target line + reticle (Plan 7 Task 5)

Adds a new render function that paints a single dashed white line at
alpha 0.4 from the selected cannon to its current target, plus a 4 px
white reticle (alpha 0.7) on the target itself. Wired into the render
loop after drawShatterParticles, so the overlay sits above ants and
particles but below HUD.

Gated on ui.selectedCannon non-null and target alive. Color is fixed
white regardless of mode to avoid competing with hit-flash red and
gold-cost UI.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 6: Hygiene — resetGame + version bump + final acceptance

**Files:**
- Modify: `wp-content/themes/dariobimbi/patterns/demo-ant-buster.php` at `resetGame` (line 4841-4885)
- Modify: `wp-content/themes/dariobimbi/style.css` (`Version:` field, near top of file)

Adds the `ui.targetingRects = []` line to `resetGame` so a restart clears the previous game's rects (matching the existing `ui.upgradeRects` reset pattern). Bumps the theme version from 0.12.0 to 0.13.0, which is the cache-buster for the served `style.css?ver=0.13.0` and the trigger for any `inc/auto-pages.php` re-runs that key off the version.

- [ ] **Step 1: Add `ui.targetingRects = []` to `resetGame`**

Locate `resetGame` at line 4841. Find the block that resets `ui.upgradeRects` (line 4879-4881):

```js
// Before:
ui.upgradeRects[0] = null;
ui.upgradeRects[1] = null;
ui.upgradeRects[2] = null;
ui.hoveredUpgrade = null;
ui.sellRect = null;
ui.shopRects = [];
```

Add `ui.targetingRects = []` after `ui.sellRect = null`:

```js
// After:
ui.upgradeRects[0] = null;
ui.upgradeRects[1] = null;
ui.upgradeRects[2] = null;
ui.hoveredUpgrade = null;
ui.sellRect = null;
ui.targetingRects = [];   // Plan 7 — clear targeting hit-test rects on restart
ui.shopRects = [];
```

- [ ] **Step 2: Bump theme version in `style.css`**

Open `wp-content/themes/dariobimbi/style.css`. Find the `Version:` line in the theme header (typically within the first 20 lines). Edit:

```css
/* Before: */
Version: 0.12.0

/* After: */
Version: 0.13.0
```

- [ ] **Step 3: PHP lint**

```bash
"c:/wamp64/bin/php/php8.2.29/php.exe" -l wp-content/themes/dariobimbi/patterns/demo-ant-buster.php
```

Expected: `No syntax errors detected`.

- [ ] **Step 4: Hygiene grep**

```bash
curl -s http://localhost/dariobimbi.com/demos/ant-buster/ | grep -c "&#038;&#038;"
```

Expected: `0`.

- [ ] **Step 5: Confirm version is served**

```bash
curl -s http://localhost/dariobimbi.com/demos/ant-buster/ | grep -c "ver=0.13.0"
```

Expected: at least `1` (the `style.css` enqueue should append `?ver=0.13.0`).

- [ ] **Step 6: Run the full §9 acceptance suite**

Walk through every item in spec §9 (20 items). For each, run the Playwright probe described and confirm pass. Items 16, 17, and the King-fight scenarios require waiting for / fast-forwarding to wave 25, which has the King.

| # | Item | Verification |
|---|---|---|
| 1 | Default unchanged | Place cannon, check `targetingMode === 'closest'` |
| 2 | Mode survives upgrade | Set 'strongest', upgrade, check still 'strongest' |
| 3 | Closest mode picks nearest | Spawn 3 ants, set closest, check c.target |
| 4 | First mode picks cake-closest | Same setup, set first, check c.target |
| 5 | Last mode picks anthill-closest | Same setup, set last, check c.target |
| 6 | Strongest picks max HP | Mix Worker/Soldier/Tank, set strongest |
| 7 | Weakest picks min HP | Same mix, set weakest |
| 8 | Stealth override wins | Reveal Stalker, set strongest, target=Stalker |
| 9 | Mode picks within revealed pool | Two revealed Stalkers diff HP, strongest |
| 10 | Row hidden on aura | Place MP-N, select, ui.targetingRects empty |
| 11 | Cone-DoT heading follows mode | Plasma Torch with two ants, switch closest→first |
| 12 | Active button highlighted | Click each, screenshot border |
| 13 | Mode label updates | Click each, read label text |
| 14 | Line draws when target exists | Select cannon w/ ant in range, screenshot |
| 15 | Line gone without target | Sell ants, screenshot |
| 16 | King in Strongest is locked on | Wave 25, Reaper at strongest |
| 17 | Royal Guard outranks King in First | Wave 25, place Reaper covering both |
| 18 | PHP lint passes | `php -l ...demo-ant-buster.php` |
| 19 | Hygiene check passes | `grep -c &#038;&#038;` |
| 20 | Theme version 0.13.0 | `grep -c ver=0.13.0` |

All 20 items should pass. If any fail, fix and re-run.

- [ ] **Step 7: Commit**

```bash
git add wp-content/themes/dariobimbi/patterns/demo-ant-buster.php wp-content/themes/dariobimbi/style.css
git commit -m "$(cat <<'EOF'
chore(ant-buster): bump theme version to 0.13.0 (Plan 7 hygiene)

Adds ui.targetingRects = [] to resetGame so restarts clear the previous
game's mode-button hit-test rects. Mirrors the existing pattern for
ui.upgradeRects, ui.sellRect, ui.shopRects.

Bumps Version: 0.12.0 → 0.13.0 in style.css. This is the cache-buster
that ships Plan 7 (per-cannon targeting) to the live site, matching
the spec target version.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 7: Status doc + merge to main

> Note: Spec §10 outlined six tasks. Task 7 is added as a hygiene wrapper, following Plan 5 and Plan 6 precedent (both shipped with a final task that wrote the status doc and merged the branch). The status doc is small and the merge is mechanical, but separating them out keeps the per-task commit history clean and gives the implementer one final checkpoint before main.



**Files:**
- Create: `wp-content/themes/dariobimbi/docs/superpowers/plans/2026-05-08-ant-buster-plan-7-status.md`

Following the Plan 5 and Plan 6 conventions, write a short status doc summarizing what shipped, the commit list, the §9 acceptance walk results, any architectural decisions worth flagging, and Plan 8+ candidates. Then merge the branch into main with `--no-ff`.

- [ ] **Step 1: Write the status doc**

Create `wp-content/themes/dariobimbi/docs/superpowers/plans/2026-05-08-ant-buster-plan-7-status.md` with sections:

- Header: status, branch, commit count, base/head SHAs
- "What shipped" — 1-2 paragraph summary
- Commit table with 7 commits (Tasks 1-6 plus this status doc)
- Acceptance criteria walkthrough (table of 20 items, all PASS)
- Architectural decisions worth flagging (3-5 items: distance-to-cake over pathProgress, in-place upgradeCannon mutation preserves field, aura-only exclusion not aura-plus-cone, neutral-white target line, etc.)
- Plan 8+ candidates from spec §8 + spec out-of-scope log
- File deltas

- [ ] **Step 2: Commit the status doc**

```bash
git add wp-content/themes/dariobimbi/docs/superpowers/plans/2026-05-08-ant-buster-plan-7-status.md
git commit -m "$(cat <<'EOF'
docs(ant-buster): Plan 7 status - shipped

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

- [ ] **Step 3: Merge to main with `--no-ff`**

```bash
git checkout main
git merge --no-ff ant-buster-plan-7 -m "$(cat <<'EOF'
Merge branch 'ant-buster-plan-7'

Plan 7 — The Commander's Console: per-tower targeting priority. Five modes
(Closest, First, Last, Strongest, Weakest) selectable from a button row in
the selected-tower panel. findTarget refactored to filter-then-pick with
stealth as the apex filter. Distance-to-cake replaces the originally-
considered pathProgress; King handled by emergent ranking, no sentinel.
Aura cannons (MP-N) excluded from the UI; cone-DoT and beam cannons
receive it because they call findTarget meaningfully.

Theme version 0.12.0 → 0.13.0.

EOF
)"
```

- [ ] **Step 4: Push to origin**

```bash
git push origin main
```

Expected: clean push, `c8d4eb2..<merge-commit>  main -> main` or similar.

- [ ] **Step 5: Confirm clean**

```bash
git status
git log --oneline -10
```

Expected: working tree clean. Top of log shows the merge commit, the 7 Plan 7 commits underneath, then `aeee89c` (Plan 7 spec fixup), `e75ec3b` (Plan 7 spec), `c8d4eb2` (Plan 6 merge).

---

## Self-Review Checklist (run before handoff)

After all tasks complete, the following must hold:

1. **Spec coverage:** Every §1-§7 spec section has at least one task implementing it. ✓ verified during plan write.
2. **Acceptance items:** All 20 §9 items have a Task that produces them, and Task 6 Step 6 walks all 20.
3. **No placeholders:** No "TBD", "TODO", "implement later", "similar to Task N" without the code shown. Searched and confirmed.
4. **Type / name consistency:** `targetingMode` (string) used identically across Tasks 1, 2, 3, 4. `ui.targetingRects` (array of `{x,y,w,h}`) used identically across Tasks 3, 4, 6. `drawTargetingRow` (function) signature `(c, px, py)` returns y-coordinate, used in Task 3. `drawTargetingOverlay` (function) no parameters, used in Task 5.
5. **Mode-string array order:** The five-element array `['closest', 'first', 'last', 'strongest', 'weakest']` appears in Task 3 (drawTargetingRow's `modes`) and Task 4 (handler's `tModes`). Both must be identical and positional.
6. **Glyph order:** `['C', 'F', 'L', 'S', 'W']` in Task 3 matches mode-string order. `['Closest', 'First', 'Last', 'Strongest', 'Weakest']` in Task 3 matches mode-string order.
7. **CLAUDE.md inline-script gotchas:** Code samples in Tasks 1-6 contain no `&&`, no `||`, no `let`, no `const`, no arrow functions, no `Array.prototype.filter/.reduce`, no `Math.pow`. Reviewed each code block; clean.
8. **Branch hygiene:** Pre-flight creates `ant-buster-plan-7` from `main` at `aeee89c`. Task 7 merges with `--no-ff` and pushes.
