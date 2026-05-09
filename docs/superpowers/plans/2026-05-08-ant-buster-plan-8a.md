# Plan 8a — God-Tier Sprites (Vertical Slice)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the Howitzer-fallback sprite with 9 distinct god-tier sprites — three per family (Heavy, Quick, Double), chosen as the most visually distinctive silhouettes from each family — so the family-anchor differentiation is validated across the whole design system before Plan 8b fills in the conventional nine.

**Architecture:** Path A from the spec (procedural canvas factories). Each sprite is a 20×20 RGBA canvas drawn with `ctx.fillStyle` + `ctx.fillRect` calls per the §4 pixel-landmark layout. All 9 factories live in `patterns/demo-ant-buster.php` alongside the existing T1-T3 factories (line 411+). Registrations append to the `game.sprites.turrets` object literal at line 1022-1057. The gold halo (`drawGodTierHalos`, line 1447) is a separate procedural layer untouched by this plan.

**Tech Stack:** Vanilla JS IIFE inside PHP (`patterns/demo-ant-buster.php`), Canvas 2D rendering, no test framework. Verification via PHP lint + hygiene grep against served HTML for HTML-encoded operators (`&#038;&#038;`) + Playwright pixel-sampling probes against the live demo.

**Spec reference:** [`docs/superpowers/specs/2026-05-08-ant-buster-god-tier-sprites-design.md`](../specs/2026-05-08-ant-buster-god-tier-sprites-design.md) at commit `389a9d4`.

**Branch base:** `main` at commit `389a9d4` (Earthshaker color tweak; final commit before Plan 8a work).

**Branch name:** `ant-buster-plan-8a`

**Theme version target:** 0.13.0 (NO bump for 8a — version bump lands at end of 8b).

---

## File Structure

| File | What changes |
|---|---|
| `patterns/demo-ant-buster.php` | 9 new `makeCannonTurret<Name>()` factory functions added after the existing factories (around line 700). 9 new registration lines in the `turrets` object at line 1022-1057. Net diff approximately +250 lines. |

No new files, no other touchpoints. The gold halo in `drawGodTierHalos` is unchanged.

---

## Conventions

These apply to every code step. From CLAUDE.md and the existing codebase, non-negotiable inside the `<script>` block:

- **`var` only.** No `let` or `const`.
- **No `&&` or `||`** in the inline script. Nested `if` instead.
- **`function () { ... }` style.** No arrow functions.
- **Sprite factory template:**

```js
function makeCannonTurret<Name>() {
  var c = makeCanvas(20, 20);
  var x = c.getContext('2d');
  // <ctx.fillStyle + fillRect calls per spec §4 landmarks>
  return c;
}
```

- **Draw order matters within a factory.** The first `fillRect` is drawn first; later calls overlay. Per-sprite, draw order is documented as "background → mid layers → foreground" inline.
- **Coordinates match spec §4 exactly.** No improvising. If a spec landmark seems off, flag it as a question; do NOT silently adjust.
- **Sprite is pre-rotation:** barrel/feature points up at 12 o'clock. The render pipeline rotates by `cannon.heading` at draw time.
- **Halo clearance:** y=15-18 pixels stay tonally dark/neutral per §2.5. Bright colors below y=14 are a spec violation.

---

## Pre-flight

- [ ] **Step 1: Confirm baseline state on `main`**

```bash
git status
git log --oneline -5
```

Expected: working tree clean. Top of log shows `389a9d4 docs(ant-buster): Earthshaker piston color tweak` and `d956238 docs(ant-buster): god-tier sprites spec - feedback pass`.

- [ ] **Step 2: Create the feature branch**

```bash
git checkout -b ant-buster-plan-8a
```

- [ ] **Step 3: Sanity-check the demo loads cleanly**

Navigate Playwright to `http://localhost/dariobimbi.com/demos/ant-buster/?debug=1`. Probe:

```js
window.__ab !== undefined
```

Expected: `true`.

- [ ] **Step 4: Confirm all 9 god-tier kinds currently fall back to howitzer sprite**

```js
(function () {
  var t = window.__ab.sprites.turrets;
  var kinds = ['particle_cannon', 'annihilator', 'earthshaker', 'phoenix', 'reaper', 'hawkeye', 'blizzard', 'tempest', 'biohazard'];
  return kinds.map(function (k) { return { kind: k, hasSprite: !!t[k] }; });
})()
```

Expected: every entry has `hasSprite: false` (or undefined). The render code falls back to `t.howitzer` when the sprite is missing (line 1592).

---

## Task 1: Scaffold — 9 stub factories + registrations

**Files:**
- Modify: `patterns/demo-ant-buster.php` (add 9 stub factories after the existing turret factories near line 700; register all 9 in the turrets object at line 1022-1057)

This task adds infrastructure only. Each stub factory delegates to `makeCannonTurret()` (the howitzer) so the registration works without changing the visual state. Subsequent tasks replace each stub body with the real factory body.

- [ ] **Step 1: Locate the insertion point for new factories**

Read `patterns/demo-ant-buster.php` around line 690-710. Find the closing `}` of the last existing `makeCannonTurret*()` factory (likely Pink Rubber Family). Insert new stubs immediately after.

- [ ] **Step 2: Add 9 stub factories**

Insert this block after the last existing factory:

```js
  // === Plan 8a — God-tier turret factories (vertical slice, 9 of 18) ===
  // Stubs delegate to makeCannonTurret() until each cannon's body is filled
  // in by its own task. All 9 register in the turrets table below; the live
  // demo will show howitzer fallback until each task lands.

  function makeCannonTurretParticleCannon() { return makeCannonTurret(); }
  function makeCannonTurretAnnihilator() { return makeCannonTurret(); }
  function makeCannonTurretEarthshaker() { return makeCannonTurret(); }
  function makeCannonTurretPhoenix() { return makeCannonTurret(); }
  function makeCannonTurretReaper() { return makeCannonTurret(); }
  function makeCannonTurretHawkeye() { return makeCannonTurret(); }
  function makeCannonTurretBlizzard() { return makeCannonTurret(); }
  function makeCannonTurretTempest() { return makeCannonTurret(); }
  function makeCannonTurretBiohazard() { return makeCannonTurret(); }
```

- [ ] **Step 3: Register the 9 in the turrets object**

Locate the `turrets: { ... }` block at line 1022-1057. Find the last entry (`rubber_wrecking_ball: makeCannonTurretRubberWreckingBall()`) and add 9 new entries immediately after it. The closing brace of `turrets` should follow the new entries.

```js
      // Plan 4 — Pink Rubber Family (Bucket B). Linear chain, no T3 god.
      pink_pea: makeCannonTurretPinkPea(),
      bouncy_bubble: makeCannonTurretBouncyBubble(),
      rubber_wrecking_ball: makeCannonTurretRubberWreckingBall(),
      // Plan 8a — God-tier vertical slice (9 of 18). Plan 8b fills in the rest.
      particle_cannon: makeCannonTurretParticleCannon(),
      annihilator: makeCannonTurretAnnihilator(),
      earthshaker: makeCannonTurretEarthshaker(),
      phoenix: makeCannonTurretPhoenix(),
      reaper: makeCannonTurretReaper(),
      hawkeye: makeCannonTurretHawkeye(),
      blizzard: makeCannonTurretBlizzard(),
      tempest: makeCannonTurretTempest(),
      biohazard: makeCannonTurretBiohazard()
    },
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

- [ ] **Step 6: Playwright probe — all 9 keys now exist in turrets table**

Reload the demo. Probe:

```js
(function () {
  var t = window.__ab.sprites.turrets;
  var kinds = ['particle_cannon', 'annihilator', 'earthshaker', 'phoenix', 'reaper', 'hawkeye', 'blizzard', 'tempest', 'biohazard'];
  return kinds.map(function (k) {
    return { kind: k, hasSprite: !!t[k], w: t[k] ? t[k].width : null, h: t[k] ? t[k].height : null };
  });
})()
```

Expected: every entry has `hasSprite: true`, `w: 20`, `h: 20`. Visually, the sprites still look like the howitzer because each is delegating to `makeCannonTurret()`.

- [ ] **Step 7: Commit**

```bash
git add wp-content/themes/dariobimbi/patterns/demo-ant-buster.php
git commit -m "$(cat <<'EOF'
feat(ant-buster): Plan 8a scaffold - 9 god-tier turret stubs

Adds 9 stub factories delegating to makeCannonTurret() and registers
them in game.sprites.turrets. Visual state unchanged (still howitzer
fallback) but infrastructure is in place; subsequent tasks replace
each stub body with the real per-cannon pixel-art factory per the
god-tier sprites design spec §4.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Per-Cannon Task Template

Tasks 2-10 share this shape. Read this section once; each task references the per-cannon §4 description from the spec and provides the literal factory body to paste in.

For each cannon:

1. Locate the stub factory in `patterns/demo-ant-buster.php` (added in Task 1).
2. Replace the entire function body with the real implementation (provided in the task).
3. PHP lint, hygiene grep.
4. Playwright probe: place the cannon at known coords, sample specific pixels, confirm the sprite renders per spec.
5. Visual confirmation via screenshot.
6. Commit.

The Playwright probe pattern for each cannon is the same template:

```js
async () => {
  var game = window.__ab;
  var ui = window.__abUi;
  // Set up: skip prep, wait for ants, pause
  game.state = 'playing';
  game.waveState = 'wave';
  game.prepRemaining = 0;
  // Wait for game.cannons to be empty + a borrowable ant or just inject directly
  game.paused = true;
  game.cannons.length = 0;
  var c = {
    kind: '<KIND>', kindHistory: ['<KIND>'],
    x: 320, y: 200, heading: 0, cooldown: 999,
    target: null, kills: 0,
    disabled: false, disabledUntil: 0,
    targetingMode: 'closest'
  };
  game.cannons.push(c);
  ui.selectedCannon = c;
  // Force one render frame
  await new Promise(function (r) { requestAnimationFrame(function () { requestAnimationFrame(function () { r(); }); }); });
  // Sample pixels at expected locations
  var canvas = document.querySelector('canvas');
  var ctx = canvas.getContext('2d');
  function sample(gx, gy) {
    var px = gx * 2 + c.x * 2 - 20;  // offset relative to cannon center, scaled by 2 for ctx.scale(2,2)
    var py = gy * 2 + c.y * 2 - 20;
    var d = ctx.getImageData(Math.round(px), Math.round(py), 1, 1).data;
    return [d[0], d[1], d[2]];
  }
  // <per-cannon pixel checks>
}
```

The pixel-sample math: cannon is centered at `c.x, c.y` in game coords. Game coords are scaled 2x for canvas (`ctx.scale(2, 2)`). The 20×20 sprite is drawn at `c.x - 10, c.y - 10` in game coords. So sprite pixel `(sx, sy)` maps to canvas pixel `(c.x * 2 + (sx - 10) * 2, c.y * 2 + (sy - 10) * 2)` — but the sprite is rotated by `cannon.heading`. With `heading: 0` (default), no rotation, so the mapping works directly.

---

## Task 2: Particle Cannon (Heavy)

**Spec reference:** §4.1 — long thin silver-white accelerator barrel, three cyan plasma rings, glowing purple core in breech, slate-blue base.

**Files:**
- Modify: `patterns/demo-ant-buster.php` — replace `makeCannonTurretParticleCannon()` body

- [ ] **Step 1: Replace the factory body**

Locate `function makeCannonTurretParticleCannon() { return makeCannonTurret(); }` and replace with:

```js
  function makeCannonTurretParticleCannon() {
    var c = makeCanvas(20, 20);
    var x = c.getContext('2d');
    // Breech base (slate, halo-safe)
    x.fillStyle = '#1a1a30';
    x.fillRect(4, 16, 12, 2);
    // Side rails
    x.fillStyle = '#5a5a6a';
    x.fillRect(4, 14, 1, 4);
    x.fillRect(15, 14, 1, 4);
    // Barrel (silver-white accelerator)
    x.fillStyle = '#d8e0f0';
    x.fillRect(9, 2, 2, 14);
    // Plasma rings (cyan core at y=5, 9, 13)
    x.fillStyle = '#3affff';
    x.fillRect(8, 5, 4, 1);
    x.fillRect(8, 9, 4, 1);
    x.fillRect(8, 13, 4, 1);
    // Ring highlights (lighter cyan, scattered)
    x.fillStyle = '#80ffff';
    x.fillRect(9, 5, 1, 1);
    x.fillRect(10, 9, 1, 1);
    x.fillRect(9, 13, 1, 1);
    // Purple energy core in breech
    x.fillStyle = '#a050ff';
    x.fillRect(9, 15, 2, 1);
    x.fillStyle = '#e0a0ff';
    x.fillRect(10, 15, 1, 1);
    return c;
  }
```

- [ ] **Step 2: PHP lint**

```bash
"c:/wamp64/bin/php/php8.2.29/php.exe" -l wp-content/themes/dariobimbi/patterns/demo-ant-buster.php
```

Expected: `No syntax errors detected`.

- [ ] **Step 3: Hygiene grep**

```bash
curl -s http://localhost/dariobimbi.com/demos/ant-buster/ | grep -c "&#038;&#038;"
```

Expected: `0`.

- [ ] **Step 4: Playwright probe — verify pixel sample**

After loading the demo and placing the cannon at game coords (320, 200) with heading 0, sample the following sprite-local pixels (mapped to canvas coords as `2 * (320 + sx - 10), 2 * (200 + sy - 10)`):

| Sprite pixel (sx, sy) | Expected color | What it tests |
|---|---|---|
| (10, 8) | `#d8e0f0` silver-white | Barrel mid-shaft |
| (9, 9) | `#3affff` cyan ring | Center plasma ring |
| (10, 15) | `#e0a0ff` light purple | Core highlight |
| (4, 17) | `#1a1a30` slate | Breech corner (halo zone) |

Probe:

```js
async () => {
  var game = window.__ab;
  var ui = window.__abUi;
  game.state = 'playing';
  game.waveState = 'wave';
  game.prepRemaining = 0;
  game.paused = true;
  game.cannons.length = 0;
  var cn = {
    kind: 'particle_cannon', kindHistory: ['particle_cannon'],
    x: 320, y: 200, heading: 0, cooldown: 999,
    target: null, kills: 0, disabled: false, disabledUntil: 0,
    targetingMode: 'closest'
  };
  game.cannons.push(cn);
  ui.selectedCannon = cn;
  await new Promise(function (r) { requestAnimationFrame(function () { requestAnimationFrame(function () { r(); }); }); });
  var canvas = document.querySelector('canvas');
  var ctx = canvas.getContext('2d');
  function sample(sx, sy) {
    var px = 2 * (320 + sx - 10);
    var py = 2 * (200 + sy - 10);
    var d = ctx.getImageData(Math.round(px), Math.round(py), 1, 1).data;
    return [d[0], d[1], d[2]];
  }
  return {
    barrel: sample(10, 8),       // expect ~ [216, 224, 240]
    ring: sample(9, 9),          // expect ~ [58, 255, 255]
    coreHighlight: sample(10, 15), // expect ~ [224, 160, 255]
    breech: sample(4, 17)        // expect ~ [26, 26, 48]
  };
}
```

Expected: pixel samples match the §4 colors (within ±5 RGB tolerance — anti-aliasing of any rotation transform may introduce noise, but with heading=0 the values should be exact).

- [ ] **Step 5: Visual confirmation**

Take a screenshot of the canvas with the Particle Cannon placed and selected. Confirm visually:
- Long thin silver-white barrel pointing up.
- Three cyan rings visible at evenly-spaced intervals along the barrel.
- Small purple core glow in the breech.
- Slate-blue base, no bright colors at the bottom.

- [ ] **Step 6: Commit**

```bash
git add wp-content/themes/dariobimbi/patterns/demo-ant-buster.php
git commit -m "$(cat <<'EOF'
feat(ant-buster): Particle Cannon god-tier sprite (Plan 8a Task 2)

Replaces the howitzer fallback with a long silver-white accelerator
barrel, three cyan plasma rings, and a glowing purple core in the
slate breech. Heavy family — long/thin silhouette branch.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 3: Annihilator (Heavy)

**Spec reference:** §4.2 — stubby siege mortar, brass reinforcement bands, cast-iron breech with corner bolts, black bore with orange flicker.

- [ ] **Step 1: Replace the factory body**

```js
  function makeCannonTurretAnnihilator() {
    var c = makeCanvas(20, 20);
    var x = c.getContext('2d');
    // Cast-iron breech (extends full width — heaviest base)
    x.fillStyle = '#2a2530';
    x.fillRect(3, 11, 14, 7);
    // Bolt heads (4 corners)
    x.fillStyle = '#806040';
    x.fillRect(3, 11, 2, 2);
    x.fillRect(15, 11, 2, 2);
    x.fillRect(3, 16, 2, 2);
    x.fillRect(15, 16, 2, 2);
    // Stubby barrel (chunkiest)
    x.fillStyle = '#3a3540';
    x.fillRect(7, 3, 7, 8);
    // Brass reinforcement bands
    x.fillStyle = '#aa7822';
    x.fillRect(6, 5, 9, 1);
    x.fillRect(6, 9, 9, 1);
    // Brass band highlights
    x.fillStyle = '#d8a040';
    x.fillRect(7, 5, 1, 1);
    x.fillRect(13, 9, 1, 1);
    // Black bore with orange flicker
    x.fillStyle = '#000000';
    x.fillRect(8, 0, 5, 3);
    x.fillStyle = '#cc4020';
    x.fillRect(10, 1, 1, 1);
    return c;
  }
```

- [ ] **Step 2: PHP lint** (same command as Task 2 Step 2). Expected `No syntax errors detected`.
- [ ] **Step 3: Hygiene grep** (same command as Task 2 Step 3). Expected `0`.
- [ ] **Step 4: Playwright probe** — same template as Task 2 Step 4 with `kind: 'annihilator'` and these pixel checks:

| (sx, sy) | Expected | What it tests |
|---|---|---|
| (10, 7) | `#3a3540` (~[58, 53, 64]) | Barrel mid |
| (10, 5) | `#aa7822` (~[170, 120, 34]) | Brass band |
| (10, 1) | `#cc4020` (~[204, 64, 32]) | Bore flicker |
| (4, 12) | `#806040` (~[128, 96, 64]) | Corner bolt |

- [ ] **Step 5: Visual confirmation** — stubby barrel, two bright brass bands, wide cast-iron base with visible corner bolts, dark muzzle with orange ember.

- [ ] **Step 6: Commit**

```bash
git add wp-content/themes/dariobimbi/patterns/demo-ant-buster.php
git commit -m "$(cat <<'EOF'
feat(ant-buster): Annihilator god-tier sprite (Plan 8a Task 3)

Stubby siege-mortar barrel with two brass reinforcement bands, full-
width cast-iron breech with corner bolts, dark muzzle bore with an
orange flicker pixel. Heavy family — short/wide silhouette branch.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 4: Earthshaker (Heavy)

**Spec reference:** §4.6 — industrial-steel piston (`#3a4055`, lighter than family anchor), hammer-face stud pattern, widest base footprint, orange seep at the very bottom.

- [ ] **Step 1: Replace the factory body**

```js
  function makeCannonTurretEarthshaker() {
    var c = makeCanvas(20, 20);
    var x = c.getContext('2d');
    // Cast-iron breech (widest base — anchored deep)
    x.fillStyle = '#1a1820';
    x.fillRect(2, 11, 16, 7);
    // Corner bolt-heads
    x.fillStyle = '#604035';
    x.fillRect(2, 11, 2, 2);
    x.fillRect(15, 11, 2, 2);
    x.fillRect(2, 16, 2, 2);
    x.fillRect(15, 16, 2, 2);
    // Industrial-steel piston (lighter than family anchor for pop)
    x.fillStyle = '#3a4055';
    x.fillRect(6, 4, 8, 7);
    // Hammer-face dark cap
    x.fillStyle = '#604035';
    x.fillRect(6, 2, 8, 2);
    // Stud dots (+ pattern on hammer-face)
    x.fillStyle = '#806040';
    x.fillRect(10, 1, 1, 1);
    x.fillRect(8, 3, 1, 1);
    x.fillRect(10, 3, 1, 1);
    x.fillRect(12, 3, 1, 1);
    // Orange seep at the very bottom (warm light leaking)
    x.fillStyle = '#cc4020';
    x.fillRect(5, 18, 10, 1);
    return c;
  }
```

Note: the spec's "ground-cracks" detail at y=18 is omitted — at 20×20 the scattered crack pixels read as noise against the orange seep. The seep alone carries the "anchored to the ground" reading.

- [ ] **Step 2: PHP lint, Step 3: Hygiene grep** — same as prior tasks.
- [ ] **Step 4: Playwright probe** with `kind: 'earthshaker'`:

| (sx, sy) | Expected | What it tests |
|---|---|---|
| (10, 7) | `#3a4055` (~[58, 64, 85]) | Piston body (lighter than family anchor) |
| (10, 14) | `#1a1820` (~[26, 24, 32]) | Breech (darker than piston — confirms color contrast) |
| (10, 18) | `#cc4020` (~[204, 64, 32]) | Orange seep |
| (10, 1) | `#806040` (~[128, 96, 64]) | Top stud dot |

The piston-vs-breech contrast is the critical visual differentiator established in the spec feedback pass — if these two pixels read as the same color (within ±10 RGB), the piston has lost its pop and the sprite needs adjustment.

- [ ] **Step 5: Visual confirmation** — wide cast-iron base, lighter steel piston that pops against the breech, hammer-face stud + pattern, orange line of light at the bottom.

- [ ] **Step 6: Commit**

```bash
git add wp-content/themes/dariobimbi/patterns/demo-ant-buster.php
git commit -m "$(cat <<'EOF'
feat(ant-buster): Earthshaker god-tier sprite (Plan 8a Task 4)

Industrial-steel piston (#3a4055, lighter than the #262b44 family
anchor for pop) over a #1a1820 cast-iron breech with corner bolts.
Hammer-face stud + pattern caps the piston. Orange seep at y=18 sells
the anchored-to-the-ground reading. Heavy family — wide-footprint
silhouette branch.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 5: Phoenix (Quick)

**Spec reference:** §4.8 — flared cone-emitter horn (no barrel), concentric color bands at the mouth, gold-bronze stem, flame-feather curls at outriggers.

- [ ] **Step 1: Replace the factory body**

```js
  function makeCannonTurretPhoenix() {
    var c = makeCanvas(20, 20);
    var x = c.getContext('2d');
    // Breech (dark with ember glow)
    x.fillStyle = '#3a2520';
    x.fillRect(4, 14, 12, 4);
    x.fillStyle = '#cc4020';
    x.fillRect(6, 18, 8, 1);
    // Gold-bronze stem (narrow neck connecting cone to breech)
    x.fillStyle = '#aa7822';
    x.fillRect(8, 10, 4, 4);
    // Cone walls (slope inward — darker orange band)
    x.fillStyle = '#cc4020';
    x.fillRect(7, 6, 6, 2);
    x.fillRect(6, 8, 8, 1);
    x.fillRect(4, 5, 12, 1);
    // Mouth — concentric color bands (back to front)
    // Outer ring (deep orange)
    x.fillStyle = '#ff6020';
    x.fillRect(3, 0, 14, 4);
    // Mid ring (bright yellow-orange)
    x.fillStyle = '#ffcc20';
    x.fillRect(5, 0, 10, 4);
    // White-hot core
    x.fillStyle = '#fff0b0';
    x.fillRect(7, 0, 6, 4);
    x.fillStyle = '#ffffff';
    x.fillRect(9, 2, 2, 1);
    // Flame-feather curls (left and right outriggers — 3-px each, mirrored)
    x.fillStyle = '#ff9020';
    x.fillRect(2, 5, 1, 1);
    x.fillRect(1, 6, 1, 1);
    x.fillRect(2, 7, 1, 1);
    x.fillRect(17, 5, 1, 1);
    x.fillRect(18, 6, 1, 1);
    x.fillRect(17, 7, 1, 1);
    return c;
  }
```

- [ ] **Step 2: PHP lint, Step 3: Hygiene grep** — same as prior.
- [ ] **Step 4: Playwright probe** with `kind: 'phoenix'`:

| (sx, sy) | Expected | What it tests |
|---|---|---|
| (3, 0) | `#ff6020` (~[255, 96, 32]) | Outer mouth ring |
| (10, 2) | `#fff0b0` (~[255, 240, 176]) | White-hot core (or `#ffffff` if exactly on the highlight pixel) |
| (10, 12) | `#aa7822` (~[170, 120, 34]) | Gold-bronze stem |
| (1, 6) | `#ff9020` (~[255, 144, 32]) | Left flame-feather outrigger |

- [ ] **Step 5: Visual confirmation** — flared cone mouth at the top with orange-yellow gradient, no traditional barrel, gold-bronze stem connecting to dark breech, two small flame curls at the upper sides.

- [ ] **Step 6: Commit**

```bash
git add wp-content/themes/dariobimbi/patterns/demo-ant-buster.php
git commit -m "$(cat <<'EOF'
feat(ant-buster): Phoenix god-tier sprite (Plan 8a Task 5)

Flared cone-emitter horn replacing the traditional barrel — concentric
orange/yellow/white-hot bands at the mouth, gold-bronze stem, two
flame-feather curls at the outriggers, dark breech with ember glow.
Quick family — no-barrel silhouette branch.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 6: Reaper (Quick)

**Spec reference:** §4.9 — vertical bone-white shaft, hooked-scythe muzzle (NOT curved), dark cowl with red eye, purple aura rim.

- [ ] **Step 1: Replace the factory body**

```js
  function makeCannonTurretReaper() {
    var c = makeCanvas(20, 20);
    var x = c.getContext('2d');
    // Breech base (dark slate — halo zone)
    x.fillStyle = '#0a0a14';
    x.fillRect(8, 14, 6, 4);
    // Purple aura rim at base edge
    x.fillStyle = '#602080';
    x.fillRect(8, 18, 6, 1);
    // Hood cowl (dark backdrop behind the hook tip)
    x.fillStyle = '#1a1a20';
    x.fillRect(5, 0, 5, 3);
    // Red eye (single pixel, inside hood)
    x.fillStyle = '#ff2020';
    x.fillRect(6, 1, 1, 1);
    // Eye highlight
    x.fillStyle = '#ff8080';
    x.fillRect(7, 1, 1, 1);
    // Vertical shaft (bone-white)
    x.fillStyle = '#e8e0d0';
    x.fillRect(10, 2, 1, 13);
    // Hook (bone-white, overlays hood at apex)
    x.fillRect(9, 2, 1, 1);  // hook base
    x.fillRect(8, 1, 1, 1);  // hook apex (overlays hood)
    x.fillRect(7, 2, 1, 1);  // hook tip
    // Cutting edge (rusted iron, inner side of hook)
    x.fillStyle = '#604035';
    x.fillRect(8, 2, 1, 1);
    x.fillRect(9, 3, 1, 1);
    return c;
  }
```

- [ ] **Step 2: PHP lint, Step 3: Hygiene grep** — same.
- [ ] **Step 4: Playwright probe** with `kind: 'reaper'`:

| (sx, sy) | Expected | What it tests |
|---|---|---|
| (10, 8) | `#e8e0d0` (~[232, 224, 208]) | Vertical shaft (mid) |
| (8, 1) | `#e8e0d0` (~[232, 224, 208]) | Hook apex (bone-white overlay on hood) |
| (6, 1) | `#ff2020` (~[255, 32, 32]) | Red eye |
| (5, 0) | `#1a1a20` (~[26, 26, 32]) | Hood corner |
| (10, 17) | `#0a0a14` (~[10, 10, 20]) | Breech (halo zone) |

- [ ] **Step 5: Visual confirmation** — straight vertical bone-white shaft, sickle-hook tip pointing left at the top, dark cowl with a single red eye visible, dark slate breech with thin purple rim at the very bottom.

- [ ] **Step 6: Commit**

```bash
git add wp-content/themes/dariobimbi/patterns/demo-ant-buster.php
git commit -m "$(cat <<'EOF'
feat(ant-buster): Reaper god-tier sprite (Plan 8a Task 6)

Vertical bone-white shaft (#e8e0d0) with a hooked-scythe muzzle
(NOT a true curve) — the hook turns at (8,1) and tips at (7,2).
Dark hood cowl behind the hook with a single red eye + highlight.
Rusted-iron cutting edge on the hook's inner curve. Purple aura rim
at the breech base. Quick family — asymmetric-tip silhouette branch.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 7: Hawkeye (Quick)

**Spec reference:** §4.11 — rifle-scope silhouette, perpendicular scope cylinder, suppressor bulge, walnut-grain stock.

- [ ] **Step 1: Replace the factory body**

```js
  function makeCannonTurretHawkeye() {
    var c = makeCanvas(20, 20);
    var x = c.getContext('2d');
    // Walnut stock (breech)
    x.fillStyle = '#604030';
    x.fillRect(6, 14, 8, 4);
    // Wood grain lines (broken pattern)
    x.fillStyle = '#805040';
    x.fillRect(7, 15, 2, 1);
    x.fillRect(11, 15, 2, 1);
    x.fillRect(8, 17, 2, 1);
    x.fillRect(11, 17, 1, 1);
    // Scope cylinder (perpendicular to barrel — cross-axis geometry)
    x.fillStyle = '#1a1820';
    x.fillRect(4, 11, 12, 3);
    // Brass edge rings (left and right end caps of scope)
    x.fillStyle = '#aa7822';
    x.fillRect(4, 11, 1, 3);
    x.fillRect(15, 11, 1, 3);
    // Barrel (gunmetal, very thin — only 2 px wide)
    x.fillStyle = '#4a5560';
    x.fillRect(9, 2, 2, 12);
    // Suppressor bulge at muzzle (slightly wider)
    x.fillStyle = '#2a3540';
    x.fillRect(8, 0, 4, 2);
    return c;
  }
```

- [ ] **Step 2-3:** PHP lint + hygiene grep.
- [ ] **Step 4: Playwright probe** with `kind: 'hawkeye'`:

| (sx, sy) | Expected | What it tests |
|---|---|---|
| (10, 8) | `#4a5560` (~[74, 85, 96]) | Barrel mid (thin, 2px wide) |
| (10, 0) | `#2a3540` (~[42, 53, 64]) | Suppressor bulge |
| (10, 12) | `#1a1820` (~[26, 24, 32]) | Scope cylinder body |
| (4, 12) | `#aa7822` (~[170, 120, 34]) | Brass edge ring (left scope cap) |
| (10, 16) | `#604030` (~[96, 64, 48]) | Walnut stock |

The cross-axis check: pixel (4, 12) is OUTSIDE the central barrel column (which is x=9-10) but INSIDE the perpendicular scope. If this pixel is brass-colored, the scope is rendering correctly.

- [ ] **Step 5: Visual confirmation** — thin gunmetal barrel, perpendicular scope cylinder visible across the breech, brass cap rings at scope ends, walnut-grain wooden stock at the bottom.

- [ ] **Step 6: Commit**

```bash
git add wp-content/themes/dariobimbi/patterns/demo-ant-buster.php
git commit -m "$(cat <<'EOF'
feat(ant-buster): Hawkeye god-tier sprite (Plan 8a Task 7)

Rifle-scope silhouette — thin 2-px gunmetal barrel, suppressor bulge
at the muzzle, perpendicular scope cylinder (cross-axis geometry —
unique among god-tier sprites) with brass cap rings, walnut-grain
wooden stock breech. Quick family — cross-axis silhouette branch.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 8: Blizzard (Double)

**Spec reference:** §4.15 — central ice-spire crystal (no barrel), four ice fragments around the base, frosted base with rime, snowflake glyph.

- [ ] **Step 1: Replace the factory body**

```js
  function makeCannonTurretBlizzard() {
    var c = makeCanvas(20, 20);
    var x = c.getContext('2d');
    // Frosted base (halo-friendly slate-blue)
    x.fillStyle = '#3a4055';
    x.fillRect(4, 14, 12, 4);
    // Snowflake glyph (5-pixel asterisk centered in base)
    x.fillStyle = '#ffffff';
    x.fillRect(9, 15, 1, 1);
    x.fillRect(8, 16, 1, 1);
    x.fillRect(9, 16, 1, 1);
    x.fillRect(10, 16, 1, 1);
    x.fillRect(9, 17, 1, 1);
    // Rime crawling up base edges
    x.fillStyle = '#a0d8ff';
    x.fillRect(5, 14, 1, 1);
    x.fillRect(15, 14, 1, 1);
    x.fillRect(4, 16, 1, 1);
    x.fillRect(15, 17, 1, 1);
    // Left ice fragment (3-px triangle)
    x.fillStyle = '#c8e8ff';
    x.fillRect(5, 10, 2, 1);
    x.fillRect(4, 11, 3, 1);
    x.fillRect(5, 12, 2, 1);
    // Right ice fragment (mirrored)
    x.fillRect(13, 10, 2, 1);
    x.fillRect(13, 11, 3, 1);
    x.fillRect(13, 12, 2, 1);
    // Central spire — hexagonal cross-section
    x.fillStyle = '#a0d8ff';
    // Top narrowing (2 px wide)
    x.fillRect(9, 2, 2, 2);
    // Middle widening (4 px)
    x.fillRect(8, 4, 4, 8);
    // Bottom narrowing back
    x.fillRect(9, 12, 2, 2);
    // Right-edge shadow on spire
    x.fillStyle = '#5090c0';
    x.fillRect(11, 4, 1, 8);
    // Sparkle highlights
    x.fillStyle = '#ffffff';
    x.fillRect(9, 4, 1, 1);
    x.fillRect(10, 10, 1, 1);
    return c;
  }
```

- [ ] **Step 2-3:** lint + grep.
- [ ] **Step 4: Playwright probe** with `kind: 'blizzard'`:

| (sx, sy) | Expected | What it tests |
|---|---|---|
| (10, 8) | `#a0d8ff` (~[160, 216, 255]) | Spire mid (widest part) |
| (11, 8) | `#5090c0` (~[80, 144, 192]) | Spire right-edge shadow |
| (5, 11) | `#c8e8ff` (~[200, 232, 255]) | Left ice fragment |
| (9, 4) | `#ffffff` (~[255, 255, 255]) | Sparkle highlight |
| (9, 16) | `#ffffff` (~[255, 255, 255]) | Snowflake glyph center |

- [ ] **Step 5: Visual confirmation** — central tall ice-blue spire (no barrel), small ice triangles flanking the base, frosted slate-blue base with a white snowflake glyph and rime pixels at the edges.

- [ ] **Step 6: Commit**

```bash
git add wp-content/themes/dariobimbi/patterns/demo-ant-buster.php
git commit -m "$(cat <<'EOF'
feat(ant-buster): Blizzard god-tier sprite (Plan 8a Task 8)

Central ice-spire crystal (#a0d8ff hexagonal cross-section) replacing
the traditional barrel — the only god-tier with vertical symmetry
around a non-barrel center. Four ice fragments at the base, frosted
slate-blue base with rime crawling up and a white snowflake glyph.
Aura cannon — no findTarget call, so the spec hides its targeting row.
Double family — aura-row silhouette branch.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 9: Tempest (Double)

**Spec reference:** §4.18 — Z-bolt zigzag silhouette, two ground shockwave rings, lightning glyph in breech.

NOTE: Tempest is the one god-tier with intentional asymmetry — the Z-bolt rotates with the cannon as it tracks targets. The shockwave rings on the base ARE symmetric, anchoring the rotation.

- [ ] **Step 1: Replace the factory body**

```js
  function makeCannonTurretTempest() {
    var c = makeCanvas(20, 20);
    var x = c.getContext('2d');
    // Stormy purple-grey breech
    x.fillStyle = '#3a2a40';
    x.fillRect(6, 15, 8, 4);
    // Lightning glyph in breech (5-pixel zig)
    x.fillStyle = '#fff080';
    x.fillRect(9, 16, 1, 1);
    x.fillRect(10, 16, 1, 1);
    x.fillRect(8, 17, 1, 1);
    x.fillRect(9, 17, 1, 1);
    x.fillRect(10, 17, 1, 1);
    // Shock-rings on the ground (concentric, symmetric — anchors rotation)
    x.fillStyle = '#80c0ff';
    x.fillRect(4, 15, 12, 1);
    x.fillRect(2, 17, 16, 1);
    // Z-bolt outer-edge halo (drawn first as a backdrop glow)
    x.fillStyle = '#ffd040';
    // Top segment glow
    x.fillRect(8, 2, 3, 4);
    // Middle kink glow
    x.fillRect(9, 5, 5, 5);
    // Bottom segment glow
    x.fillRect(12, 9, 3, 6);
    // Z-bolt core (bright yellow-white, 1-px path)
    x.fillStyle = '#fff080';
    // Top segment (lean left from y=2 to y=5)
    x.fillRect(10, 2, 1, 1);
    x.fillRect(9, 3, 1, 1);
    x.fillRect(9, 4, 1, 1);
    x.fillRect(8, 5, 1, 1);
    // Middle segment (diagonal right from y=6 to y=9)
    x.fillRect(10, 6, 1, 1);
    x.fillRect(11, 7, 1, 1);
    x.fillRect(12, 8, 1, 1);
    x.fillRect(13, 9, 1, 1);
    // Bottom segment (vertical from y=10 to y=14)
    x.fillRect(13, 10, 1, 1);
    x.fillRect(13, 11, 1, 1);
    x.fillRect(14, 12, 1, 1);
    x.fillRect(14, 13, 1, 1);
    x.fillRect(14, 14, 1, 1);
    return c;
  }
```

- [ ] **Step 2-3:** lint + grep.
- [ ] **Step 4: Playwright probe** with `kind: 'tempest'`:

| (sx, sy) | Expected | What it tests |
|---|---|---|
| (10, 2) | `#fff080` (~[255, 240, 128]) | Z-bolt top tip |
| (8, 5) | `#fff080` (~[255, 240, 128]) | Top-segment leftmost lean |
| (13, 9) | `#fff080` (~[255, 240, 128]) | Middle-segment kink endpoint |
| (14, 14) | `#fff080` (~[255, 240, 128]) | Bottom-segment lower tip |
| (8, 15) | `#80c0ff` (~[128, 192, 255]) | Inner shockwave ring |
| (2, 17) | `#80c0ff` (~[128, 192, 255]) | Outer shockwave ring |

The four bolt-path samples confirm the Z-shape traces correctly.

- [ ] **Step 5: Visual confirmation** — bright yellow-white Z-bolt with golden glow halo zigzags from upper-center to lower-right, two concentric blue shockwave rings at the bottom, dark purple-grey breech with a small lightning glyph.

- [ ] **Step 6: Commit**

```bash
git add wp-content/themes/dariobimbi/patterns/demo-ant-buster.php
git commit -m "$(cat <<'EOF'
feat(ant-buster): Tempest god-tier sprite (Plan 8a Task 9)

Z-bolt zigzag silhouette replacing the traditional barrel — yellow-
white core with a golden #ffd040 glow halo. Two concentric shockwave
rings on the ground (symmetric — anchors rotation despite the
asymmetric bolt). Stormy purple-grey breech with a small lightning
glyph. Double family — lightning/zigzag silhouette branch. The
intentional asymmetry rotates with the cannon as it tracks targets.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 10: Biohazard (Double)

**Spec reference:** §4.17 — twin chemical reservoirs flanking a thin central nozzle, biohazard trefoil glyph, rust-patina base.

- [ ] **Step 1: Replace the factory body**

```js
  function makeCannonTurretBiohazard() {
    var c = makeCanvas(20, 20);
    var x = c.getContext('2d');
    // Rust-patina base
    x.fillStyle = '#3a2a20';
    x.fillRect(4, 14, 12, 4);
    // Patina speckles
    x.fillStyle = '#5a3a30';
    x.fillRect(6, 15, 1, 1);
    x.fillRect(13, 16, 1, 1);
    x.fillRect(8, 17, 1, 1);
    // Left reservoir (bile-green with shadow)
    x.fillStyle = '#7acc20';
    x.fillRect(2, 8, 6, 10);
    // Left reservoir shadow (left edge)
    x.fillStyle = '#4a8c10';
    x.fillRect(2, 8, 1, 10);
    // Left reservoir liquid line
    x.fillStyle = '#5a8c10';
    x.fillRect(3, 11, 5, 1);
    // Right reservoir (mirrored)
    x.fillStyle = '#7acc20';
    x.fillRect(12, 8, 6, 10);
    x.fillStyle = '#4a8c10';
    x.fillRect(17, 8, 1, 10);
    x.fillStyle = '#5a8c10';
    x.fillRect(12, 11, 5, 1);
    // Central nozzle (dull stainless)
    x.fillStyle = '#8a90a0';
    x.fillRect(9, 2, 2, 8);
    // Venom drip at the muzzle
    x.fillStyle = '#7acc20';
    x.fillRect(9, 0, 1, 1);
    // Biohazard trefoil glyph (3 black 2x2 lobes clustered around x=9-10, y=5-8)
    x.fillStyle = '#0a0a14';
    x.fillRect(7, 5, 2, 2);   // top-left lobe
    x.fillRect(11, 5, 2, 2);  // top-right lobe
    x.fillRect(9, 7, 2, 2);   // bottom lobe
    return c;
  }
```

- [ ] **Step 2-3:** lint + grep.
- [ ] **Step 4: Playwright probe** with `kind: 'biohazard'`:

| (sx, sy) | Expected | What it tests |
|---|---|---|
| (4, 12) | `#7acc20` (~[122, 204, 32]) | Left reservoir body |
| (15, 12) | `#7acc20` (~[122, 204, 32]) | Right reservoir body (mirror) |
| (10, 6) | `#0a0a14` (~[10, 10, 20]) | Biohazard glyph (top-right lobe pixel) |
| (10, 4) | `#8a90a0` (~[138, 144, 160]) | Central stainless nozzle |
| (3, 11) | `#5a8c10` (~[90, 140, 16]) | Liquid line in left reservoir |

- [ ] **Step 5: Visual confirmation** — two bulbous bile-green tanks flanking a thin grey nozzle, biohazard trefoil glyph between the tanks, rust-patina base with subtle speckles, single venom-green pixel at the muzzle.

- [ ] **Step 6: Commit**

```bash
git add wp-content/themes/dariobimbi/patterns/demo-ant-buster.php
git commit -m "$(cat <<'EOF'
feat(ant-buster): Biohazard god-tier sprite (Plan 8a Task 10)

Twin bile-green chemical reservoirs (#7acc20) flanking a thin
stainless nozzle, biohazard trefoil glyph (3 black 2x2 lobes) between
the tanks, rust-patina base with subtle speckles. Single venom-drip
pixel at the muzzle. Double family — multi-element silhouette branch.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 11: Plan 8a hygiene + family-cohesion check

**Files:** none modified — verification only.

This task runs the §6 acceptance items 1-9 against the 9 new sprites, plus the family-cohesion check. NO version bump (that lands at end of 8b). NO acceptance items 10-12 (those are full-roster checks for 8b).

- [ ] **Step 1: Item 1 — All 9 sprites load without console errors**

Reload the demo. Probe:

```js
(function () {
  var t = window.__ab.sprites.turrets;
  var kinds = ['particle_cannon', 'annihilator', 'earthshaker', 'phoenix', 'reaper', 'hawkeye', 'blizzard', 'tempest', 'biohazard'];
  return kinds.every(function (k) { return t[k] && t[k].width === 20 && t[k].height === 20; });
})()
```

Expected: `true`.

- [ ] **Step 2: Item 3 — Background fully transparent**

Probe alpha at corners of each sprite:

```js
(function () {
  var t = window.__ab.sprites.turrets;
  var kinds = ['particle_cannon', 'annihilator', 'earthshaker', 'phoenix', 'reaper', 'hawkeye', 'blizzard', 'tempest', 'biohazard'];
  return kinds.map(function (k) {
    var sprite = t[k];
    var sctx = sprite.getContext('2d');
    var corner = sctx.getImageData(0, 0, 1, 1).data;
    return { kind: k, cornerAlpha: corner[3] };
  });
})()
```

Expected: every entry has `cornerAlpha: 0`.

- [ ] **Step 3: Item 4 — Halo clearance**

For each sprite, sample 4 pixels at y=16, 17, 18, 19. Verify none are at full saturation in a non-anchor color.

```js
(function () {
  var t = window.__ab.sprites.turrets;
  var kinds = ['particle_cannon', 'annihilator', 'earthshaker', 'phoenix', 'reaper', 'hawkeye', 'blizzard', 'tempest', 'biohazard'];
  return kinds.map(function (k) {
    var sprite = t[k];
    var sctx = sprite.getContext('2d');
    var rows = [16, 17, 18, 19];
    var brightCount = 0;
    for (var ri = 0; ri < rows.length; ri++) {
      for (var sx = 0; sx < 20; sx++) {
        var d = sctx.getImageData(sx, rows[ri], 1, 1).data;
        // Bright = max channel > 200 AND alpha > 0
        if (d[3] > 0) {
          if (Math.max(d[0], d[1], d[2]) > 200) {
            brightCount++;
          }
        }
      }
    }
    return { kind: k, brightPixelsInHaloZone: brightCount };
  });
})()
```

Expected: every entry has `brightPixelsInHaloZone <= 2` (a few bright pixels are OK — e.g., Tempest's lightning glyph has 5 yellow pixels at y=16-17, which IS expected. Adjust the threshold per cannon if needed).

For Tempest specifically, the lightning glyph and shockwave rings are halo-friendly accents per spec §2.5. Document any "expected" bright pixels and confirm they match the spec's design intent.

- [ ] **Step 4: Item 5 — No more Howitzer fallback for the 9**

Place each of the 9 god-tier cannons in the live demo. For each, confirm the rendered sprite is visibly different from Howitzer.

For each kind, run the Task 2-style probe and confirm at least one pixel-sample matches the spec's expected color (i.e., it's not just blitting the howitzer barrel).

- [ ] **Step 5: Item 6 — Family hierarchy reads**

Place the entire Heavy family side-by-side: Howitzer (T1) → Siege/Mortar/Discus (T2) → Patriot/Demolisher/Hellfire (T3) → Particle Cannon/Annihilator/Earthshaker (T4 from Plan 8a — Saturation/Cluster Strike/Vortex still on Howitzer fallback).

Take a screenshot. Visual check: slate-blue + brass anchor visible across all tiers; the three new T4s read as Heavy, not Quick or Double.

Repeat for Quick (with Phoenix/Reaper/Hawkeye on T4) and Double (with Blizzard/Tempest/Biohazard on T4).

- [ ] **Step 6: Item 7 — Family cohesion (T3 → T4 evolution)**

For each of the 9 new sprites, place it next to its T3 ancestor:

| T4 (new) | T3 ancestor | Cohesion check |
|---|---|---|
| Particle Cannon | Patriot | Slate-blue + brass anchor visible? Long-thin silhouette evolved? |
| Annihilator | Demolisher | Stubby silhouette + brass bands evolved? |
| Earthshaker | Hellfire | Wide-base anchored silhouette evolved? |
| Phoenix | Plasma Torch | Cone silhouette evolved with more elaborate flame mouth? |
| Reaper | Marksman | Sniper-thin silhouette evolved with hooked-scythe muzzle? |
| Hawkeye | Sharpshooter | Sniper-thin silhouette evolved with perpendicular scope? |
| Blizzard | Cryo | Ice/frost color palette evolved into tower-like aura silhouette? |
| Tempest | Sonic Pulse | Stun-coded silhouette evolved with lightning bolt? |
| Biohazard | Plague | Bile-green poison silhouette evolved with twin reservoirs? |

For each pair, confirm the T4 reads as a same-family + tier-up evolution, not a cross-family swap.

- [ ] **Step 7: Item 8 — Rotation works**

For each of the 9, set `cannon.heading` to several values (0, π/4, π/2, 3π/4, π, 5π/4, 3π/2, 7π/4) and confirm the sprite rotates cleanly without artifacts.

```js
async () => {
  var game = window.__ab;
  var ui = window.__abUi;
  var c = game.cannons[0];
  var headings = [0, Math.PI / 4, Math.PI / 2, 3 * Math.PI / 4, Math.PI];
  game.paused = true;
  for (var i = 0; i < headings.length; i++) {
    c.heading = headings[i];
    await new Promise(function (r) { requestAnimationFrame(function () { r(); }); });
    // Visual check via screenshot at each heading
  }
}
```

Expected: clean rotation. Tempest's intentional asymmetry rotates with the bolt, which is the design intent.

- [ ] **Step 8: Item 9 — Gold halo plays well**

Watch each god-tier cannon for ~3 seconds at full game speed. Confirm the sin-pulsed gold halo blends cleanly with the sprite's bottom rows. No "muddy gold + bright color" clashes.

- [ ] **Step 9: PHP lint + hygiene grep (final pass for 8a)**

```bash
"c:/wamp64/bin/php/php8.2.29/php.exe" -l wp-content/themes/dariobimbi/patterns/demo-ant-buster.php
curl -s http://localhost/dariobimbi.com/demos/ant-buster/ | grep -c "&#038;&#038;"
```

Expected: lint passes, hygiene grep returns `0`.

If any acceptance item fails, fix in a follow-up commit BEFORE moving to Task 12.

---

## Task 12: Status doc + merge to main

**Files:**
- Create: `docs/superpowers/plans/2026-05-08-ant-buster-plan-8a-status.md`

Following Plan 5/6/7 precedent. Plan 8b will pick up after this merges.

- [ ] **Step 1: Write the status doc**

Create the status doc with sections:

- Header: shipped status, branch, commits, base/head SHAs.
- "What shipped" — paragraph summarizing the 9 new sprites and the vertical-slice approach.
- Commit table — 11 commits (Tasks 1-10 + this status doc).
- Acceptance items 1-9 walkthrough (table form).
- Family-cohesion check results.
- Architectural decisions worth flagging:
  1. Earthshaker piston bumped lighter (#3a4055) for contrast.
  2. Reaper hooked-scythe instead of curved barrel.
  3. Stormcaller (deferred to 8b) gets outrigger antennae.
  4. Tempest accepts intentional asymmetry; rotation is by design.
  5. Ground-cracks on Earthshaker omitted as noise at 20×20 — orange seep alone carries the reading.
- Plan 8b candidates: Saturation, Cluster Strike, Vortex, Hailstorm, Solar Lance, Stormcaller, Scattergun, Phalanx, Absolute Zero. Plus the version bump (0.13.0 → 0.14.0).
- File deltas.

- [ ] **Step 2: Commit the status doc**

```bash
git add wp-content/themes/dariobimbi/docs/superpowers/plans/2026-05-08-ant-buster-plan-8a-status.md
git commit -m "$(cat <<'EOF'
docs(ant-buster): Plan 8a status - shipped

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

- [ ] **Step 3: Merge to main with `--no-ff`**

```bash
git checkout main
git merge --no-ff ant-buster-plan-8a -m "$(cat <<'EOF'
Merge branch 'ant-buster-plan-8a'

Plan 8a — God-tier sprites vertical slice. Replaces the howitzer
fallback for 9 of 18 god-tier cannons (3 per family, most distinctive
silhouettes): Particle Cannon, Annihilator, Earthshaker, Phoenix,
Reaper, Hawkeye, Blizzard, Tempest, Biohazard. Validates family-anchor
differentiation across the design system. Plan 8b fills in the
remaining 9 plus version bump.

No theme version bump (lands at end of 8b).

EOF
)"
```

- [ ] **Step 4: Push + cleanup**

After user authorization, push and delete the branch:

```bash
git push origin main
git branch -D ant-buster-plan-8a
git push origin --delete ant-buster-plan-8a
```

- [ ] **Step 5: Save Plan 8a memory entry**

Update `~/.claude/projects/c--wamp64-www-dariobimbi-com-wp-content-themes-dariobimbi/memory/MEMORY.md` with a new entry pointing to a `ant_buster_plan_8a_shipped.md` file capturing what shipped.

---

## Self-Review Checklist (run before handoff)

After all tasks complete, the following must hold:

1. **Spec coverage:** All 9 vertical-slice cannons from spec §8 have a Task that implements them.
2. **No placeholders:** Every code step contains the actual factory body.
3. **Type / name consistency:** Function names (`makeCannonTurretParticleCannon` etc.) match across the factory definition and the registration line.
4. **Pixel-landmark fidelity:** Each factory's `fillRect` calls match the spec §4 landmarks. No improvised coordinates.
5. **Halo clearance:** Spot-check that each factory's y=14-18 region is dark/neutral.
6. **CLAUDE.md gotchas:** No `&&`, no `||`, no `let`/`const`, no arrow functions in any factory.
7. **Branch hygiene:** Pre-flight creates `ant-buster-plan-8a` from `main` at `389a9d4`. Task 12 merges with `--no-ff` and pushes.
8. **No version bump:** Theme stays at 0.13.0 throughout 8a. Version 0.14.0 is Plan 8b's responsibility.
