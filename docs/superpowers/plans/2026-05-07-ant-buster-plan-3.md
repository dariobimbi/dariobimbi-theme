# Ant Buster — Plan 3 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Fill out Plan 2's systems backbone with content: 21 non-god cannons (full 3-mid × 2-final tree), 20 new waves (6–25), King ant + wave 25 boss mechanics, carrier flee-speed match. Game shell, audio, god tier, endless, and visualization polish all explicitly Plan 4.

**Architecture:** Single-file pattern (`patterns/demo-ant-buster.php`). Plan 2's four kind-tables (`CANNON_KINDS`, `ANT_KINDS`, `STATUS_KINDS`, `WAVES`) drive everything by string key. Plan 3 = appending rows + ~11 new mechanic primitives in the projectile / cannon update pipeline + small refactors (pierce-flag split, source param on `spawnAnt`, branching upgrade UI). No new files, no new templates.

**Tech Stack:** Vanilla JS (IIFE, `var` only, no `&&`/`||` operators in inline scripts), Canvas 2D, fixed-timestep loop, procedural placeholder sprites. PHP 8.2 for `php -l`. Playwright MCP for per-task verification (navigate + console + `window.__ab` introspection + screenshot).

**Spec:** [`../specs/2026-05-07-ant-buster-plan-3-design.md`](../specs/2026-05-07-ant-buster-plan-3-design.md)
**Plan 2 status:** [`./2026-05-07-ant-buster-plan-2-status.md`](./2026-05-07-ant-buster-plan-2-status.md)

---

## Conventions every task must follow

1. **Inline-script syntax footgun.** Never write `&&` or `||` inside the `<script>` block. Use nested `if`, helper booleans (`var ok = a; if (!ok) { ok = b; }`), or `indexOf` checks. After every commit, verify:

   ```bash
   curl -s http://localhost/dariobimbi.com/demos/ant-buster/ | grep -F '&#038;&#038;'
   curl -s http://localhost/dariobimbi.com/demos/ant-buster/ | grep -F '&#124;&#124;'
   ```

   Expected: empty output for both. Any hit blocks the commit; rewrite the offending expression.
2. **`var` only.** No `let` or `const`. Function declarations (`function foo()`) only — no `var foo = function () {}` for shared helpers (Plan 1's hoisting trap).
3. **PHP syntax check after every edit:**

   ```bash
   c:/wamp64/bin/php/php8.2.29/php.exe -l c:/wamp64/www/dariobimbi.com/wp-content/themes/dariobimbi/patterns/demo-ant-buster.php
   ```

   Expected: `No syntax errors detected`.
4. **Playwright verification per task.**
   - Navigate to `http://localhost/dariobimbi.com/demos/ant-buster/?debug=1`.
   - Read console messages — any error other than benign 404s is a blocker.
   - Inspect game state via `browser_evaluate` reading `window.__ab` (whole `game` object), `window.__abUi` (UI state), `window.__abStatusKinds` (status kinds table), `window.__abApplyStatus` (apply-status helper).
   - Screenshot for any visual change.
5. **Commit per task** in conventional-commits style: `feat(ant-buster): …`, `refactor(ant-buster): …`, `fix(ant-buster): …`. Subagent-driven workers commit; the reviewer verifies the diff before approving.
6. **No theme `Version:` bump per task.** Bumped once at the end (Task 35).
7. **Serve from local WAMP.** Demo at `/demos/ant-buster/`. If the page returns 404, check WAMP is running and the page exists; do not re-run `auto-pages` reconciliation as part of a task.
8. **Order matters.** Tasks 1–5 are architectural refactors; subsequent tasks depend on them. Do not skip ahead.

---

## File structure

All work lives in **one** file: `c:/wamp64/www/dariobimbi.com/wp-content/themes/dariobimbi/patterns/demo-ant-buster.php`.

Currently 2368 lines (after Plan 2 + post-Plan-2 `||` sweep at `3ad666c`). Projected end-of-Plan-3: ~3000–3300 lines. Logical regions inside the IIFE (line numbers approximate, will drift):

1. **Constants & state** (lines ~85–130): canvas dims, `DEBUG_KINDS`, `game` state object, banner state.
2. **Sprite factories** (~140–1050): per-kind procedural sprites + UI sprites. Plan 3 adds `makeKing`, plus visual additions for new mechanics (Tesla polyline, Plasma Torch cone overlay, persistent HP bar render variant).
3. **Render** (~1052–1078): `render()` walks 13 z-order layers (Plan 2 task 25 refactor). Plan 3 adds Tesla bolt and Plasma cone in existing layers.
4. **Tower-detail panel** (~600–670): `drawTowerPanel`. Plan 3 generalizes upgrade-button rendering from 2 to 3 slots.
5. **ANT_KINDS table** (~1081–1300): per-kind data rows. Plan 3 adds `king`. Adds `barStyle`, `onHpThresholds` schema fields.
6. **STATUS_KINDS table** (~1304–1314): six status kinds. Plan 3 adds `freeze.damageAmp` field, `poison.onTargetDeath` hook field.
7. **Status helpers** (~1315–1337): `applyStatus`. Plan 3 leaves the helper alone but the tick-handler in `updateAnts` reads new fields.
8. **Spawn / ant logic** (~1338–1620): `spawnAntAt`, `spawnAnt`, `updateAnt`, `updateAnts`. Plan 3 extends `spawnAnt` with `source` param + `spawnOrigin` field; flee logic in `updateAnt` reads `ant.spawnOrigin`.
9. **CANNON_KINDS table** (~1625–1755): cannon data rows. Plan 3 appends 21 rows; replaces `pierce` with `linePierce` + `armorPierce`; adds `damageModifier` hook field.
10. **Cannon logic** (~1760–1985): `placeCannon`, `findTarget`, `updateCannons`, `fireProjectile`, `applyDamage`, `updateProjectiles`, `killAnt`. Plan 3 extends fireProjectile and updateProjectiles for new mechanic primitives; applyDamage gains armorPierce + damageModifier reads.
11. **Wave logic** (~1989–2080): `WAVES` array + `updateWaveState`. Plan 3 appends waves 6–25; adds `prepTime` per-wave override.
12. **Reset / UI / input** (~2080–2363): `resetGame`, `ui` state, mousedown / mousemove / mouseup, keyboard. Plan 3 extends `ui.upgradeRects` from 2 to 3 slots and updates the click handler.
13. **Debug exposure** (~2363–2367): `window.__ab`, `window.__abUi`, etc. Plan 3 adds `window.__abSpawn = spawnAnt` and `window.__abPlaceCannon = placeCannon` for Playwright introspection.

Region 9 (CANNON_KINDS) is where most Plan 3 LOC will land — 21 new rows + the 11 mechanic-primitive functions sitting near `fireProjectile` / `updateProjectiles`. The single-file convention holds; Plan 4 can revisit splitting.

---

## Task 1: Pierce flag split — `linePierce` + `armorPierce`

**Files:**
- Modify: `patterns/demo-ant-buster.php` — `CANNON_KINDS.railgun` (~line 1700ish), `fireProjectile` (~1869), `applyDamage` (~1901), `updateProjectiles` (~1939).

Plan 2's `pierce: true` flag couples line-pierce (passes through ants) with armor-pierce (full damage vs armored). Plan 3 splits these into orthogonal flags so future cannons can opt into one without the other. Demolisher (Task 9) needs `armorPierce` only.

- [ ] **Step 1: Update Railgun's CANNON_KINDS row**

  Find Railgun's row in `CANNON_KINDS` (it has `pierce: true`). Replace:

  ```js
  pierce: true,
  ```

  with:

  ```js
  linePierce: 999,    // unlimited — pre-Plan-3 Railgun semantics
  armorPierce: true,
  ```

- [ ] **Step 2: `fireProjectile` writes both flags onto the projectile**

  In `fireProjectile` (around line 1885–1894), the projectile literal currently has `pierce: !!def.pierce`. Replace with:

  ```js
  game.projectiles.push({
    x: cannon.x + Math.cos(h) * 12,
    y: cannon.y + Math.sin(h) * 12,
    vx: Math.cos(h) * def.projectileSpeed,
    vy: Math.sin(h) * def.projectileSpeed,
    damage: def.damage,
    linePierce: def.linePierce ? def.linePierce : 0,
    armorPierce: !!def.armorPierce,
    pierceLeft: def.linePierce ? def.linePierce : 0,    // counter for line-pierce hits
    statusOnHit: statusOnHit,
    sourceCannon: cannon,    // for damageModifier reads — Task 5
    life: 1.5
  });
  ```

  Drop the `pierce: !!def.pierce` line.

- [ ] **Step 3: `applyDamage` reads `armorPierce` instead of `pierce`**

  Replace the function signature at line 1901:

  ```js
  function applyDamage(ant, amount, armorPierce) {
    var k = ANT_KINDS[ant.kind];
    var dmg = amount;
    if (!armorPierce) {
      var armor = k.armor;
      if (!armor) { armor = 0; }
      dmg = dmg * (1 - armor);
    }
    ant.hp = ant.hp - dmg;
  }
  ```

- [ ] **Step 4: `updateProjectiles` honors `linePierce` instead of `break`**

  In `updateProjectiles` around line 1947–1965, the inner ant-collision loop currently has `hit = true; break;` after first contact. Replace the collision body with:

  ```js
  if (dx * dx + dy * dy < 6 * 6) {
    applyDamage(a, p.damage, p.armorPierce);
    if (p.statusOnHit) {
      var s = p.statusOnHit;
      applyStatus(a, s.kind, s.magnitude, s.duration, 'projectile');
    }
    if (a.hp <= 0) {
      killAnt(a, p.sourceCannon ? p.sourceCannon : null);
    }
    if (p.pierceLeft > 0) {
      p.pierceLeft = p.pierceLeft - 1;
      // Don't set hit; the projectile keeps flying. Skip re-hitting this ant.
      a._lastPiercedTick = game.tick;
    } else {
      hit = true;
      break;
    }
  }
  ```

  And add a guard above the bounding-box check to skip ants the same projectile already passed through this frame:

  ```js
  if (a._lastPiercedTick === game.tick && p._sawAnts && p._sawAnts.indexOf(a) !== -1) { continue; }
  ```

  Plus initialize `p._sawAnts = []` when the projectile is created in fireProjectile (Step 2 — append `_sawAnts: []` to the projectile literal). And inside the collision body, when `pierceLeft > 0`, push the ant: `if (!p._sawAnts) { p._sawAnts = []; } p._sawAnts.push(a);`.

  Simpler alternative if the above is too fiddly: track `p._sawAnts` as a flat array on the projectile and check `if (p._sawAnts.indexOf(a) !== -1) { continue; }` before the distance check. Initialize as `[]` at fire time.

- [ ] **Step 5: Add `game.tick` if it doesn't exist**

  Search for `game.tick` in the file. If it doesn't exist (Plan 2 may not have it), the `_sawAnts` path is fine on its own — the array tracks ants this projectile has already pierced. Drop the `_lastPiercedTick` references and rely solely on `p._sawAnts`.

- [ ] **Step 6: PHP lint + curl ampersand check**

  ```bash
  c:/wamp64/bin/php/php8.2.29/php.exe -l c:/wamp64/www/dariobimbi.com/wp-content/themes/dariobimbi/patterns/demo-ant-buster.php
  curl -s http://localhost/dariobimbi.com/demos/ant-buster/ | grep -F '&#038;&#038;'
  curl -s http://localhost/dariobimbi.com/demos/ant-buster/ | grep -F '&#124;&#124;'
  ```

  Expected: lint clean, both grep outputs empty.

- [ ] **Step 7: Playwright verification**

  Navigate to `?debug=1`. Build a Howitzer → Siege → Railgun. Spawn a Tank via `T` (cycle to tank). Confirm Railgun deals full damage to Tank (i.e. Tank HP drops normally, not at half). Confirm Railgun still passes through multiple ants on a single shot when ants are in a line. Read `window.__ab.cannons[0]` and verify the Railgun row in `CANNON_KINDS` shows both `linePierce: 999` and `armorPierce: true` via:

  ```js
  return JSON.stringify({
    railgun: {
      linePierce: window.__abCannonKinds.railgun.linePierce,
      armorPierce: window.__abCannonKinds.railgun.armorPierce,
      hasOldPierce: 'pierce' in window.__abCannonKinds.railgun
    }
  });
  ```

  Note: this requires exposing `CANNON_KINDS` as `window.__abCannonKinds`. If Plan 2 didn't already, add `window.__abCannonKinds = CANNON_KINDS;` at the bottom of the IIFE alongside `window.__ab = game;`.

- [ ] **Step 8: Commit**

  ```bash
  git add patterns/demo-ant-buster.php
  git commit -m "refactor(ant-buster): split pierce flag into linePierce + armorPierce"
  ```

---

## Task 2: `spawnAnt` source parameter + `ant.spawnOrigin` field

**Files:**
- Modify: `patterns/demo-ant-buster.php` — `spawnAnt` (~1363), `spawnAntAt` (~1338), flee-target logic in `updateAnt` (search for `game.anthill.x` to find current flee-target reads).

- [ ] **Step 1: Extend `spawnAnt` signature**

  Replace the function header at line 1363:

  ```js
  function spawnAnt(kindKey, source) {
    if (!source) { source = 'wave'; }
    var k = ANT_KINDS[kindKey];
    var ant = {
      kind: kindKey,
      x: game.anthill.x + (Math.random() * 16 - 8),
      y: game.anthill.y + (Math.random() * 32 - 16),
      hp: k.hp,
      maxHp: k.hp,                        // for HP-bar + threshold-summon math
      speed: k.speed,
      heading: Math.random() * Math.PI * 2,
      wanderPhase: Math.random() * Math.PI * 2,
      wanderFreq: k.wanderFreqMin + Math.random() * (k.wanderFreqMax - k.wanderFreqMin),
      wanderAmp: k.wanderAmpMin + Math.random() * (k.wanderAmpMax - k.wanderAmpMin),
      jitterT: 0,
      carrying: false,
      dead: false,
      deathTime: 0,
      deliveredAt: 0,
      effects: {},
      spawnSource: source,
      spawnOrigin: { x: game.anthill.x, y: game.anthill.y },
      firedThresholds: {}                 // for King — empty for everyone else
    };
    game.ants.push(ant);
    return ant;
  }
  ```

- [ ] **Step 2: Extend `spawnAntAt` to forward `source` + custom origin**

  At line 1338, `spawnAntAt(kindKey, x, y)` should become:

  ```js
  function spawnAntAt(kindKey, x, y, source, origin) {
    if (!source) { source = 'wave'; }
    var ant = spawnAnt(kindKey, source);
    ant.x = x;
    ant.y = y;
    if (origin) { ant.spawnOrigin = { x: origin.x, y: origin.y }; }
    return ant;
  }
  ```

  This keeps `spawnAntAt` callable with 3 args (existing call sites unchanged; `source` defaults to `'wave'`) and lets King's drip pass `'drip'` + `(10, 320)` as origin.

- [ ] **Step 3: Flee-target reads `ant.spawnOrigin` instead of `game.anthill`**

  Find every `game.anthill.x` and `game.anthill.y` reference inside `updateAnt` (the flee/return-home logic — likely around the `if (ant.carrying)` branch) and replace with `ant.spawnOrigin.x` / `ant.spawnOrigin.y`. Use grep to find them all:

  ```bash
  grep -n 'game\.anthill\.' patterns/demo-ant-buster.php
  ```

  References inside the spawn helper (steps 1 above) can stay as `game.anthill.x` since those set the *initial* spawnOrigin. Only the flee-target reads change.

- [ ] **Step 4: Update call sites that pass an existing source**

  Search for `spawnAntAt(` and `spawnAnt(` and check call sites:

  ```bash
  grep -n 'spawnAnt[^a-zA-Z]' patterns/demo-ant-buster.php
  ```

  Plan 2 has Splitter `onDeath` calling `spawnAntAt('mini_splitter', ...)`. Update to pass `'split'` as source: `spawnAntAt('mini_splitter', x, y, 'split')`. Queen onDeath calls `spawnAnt('worker')` for each spawned worker — update to `spawnAnt('worker', 'queen')`. Wave-spawner code stays as `spawnAnt(type)` (defaults to `'wave'`).

- [ ] **Step 5: Lint + curl-grep**

  ```bash
  c:/wamp64/bin/php/php8.2.29/php.exe -l c:/wamp64/www/dariobimbi.com/wp-content/themes/dariobimbi/patterns/demo-ant-buster.php
  curl -s http://localhost/dariobimbi.com/demos/ant-buster/ | grep -F '&#038;&#038;'
  curl -s http://localhost/dariobimbi.com/demos/ant-buster/ | grep -F '&#124;&#124;'
  ```

- [ ] **Step 6: Playwright verification**

  Navigate to `?debug=1`. Spawn a worker via `T`. Read:

  ```js
  return JSON.stringify({
    sample: {
      spawnSource: window.__ab.ants[0].spawnSource,
      spawnOrigin: window.__ab.ants[0].spawnOrigin,
      maxHp: window.__ab.ants[0].maxHp,
      hasFiredThresholds: !!window.__ab.ants[0].firedThresholds
    }
  });
  ```

  Expected: `spawnSource: 'wave'`, `spawnOrigin: { x: ~22, y: ~184 }` (the anthill coords), `maxHp` matching kind HP, `hasFiredThresholds: true` (empty object).

  Trigger a Splitter death (cycle to splitter, kill it). Confirm the spawned mini-splitters have `spawnSource: 'split'`.

- [ ] **Step 7: Commit**

  ```bash
  git commit -am "refactor(ant-buster): spawnAnt source param + ant.spawnOrigin per-instance field"
  ```

---

## Task 3: `barStyle` field on ANT_KINDS + render-side dispatcher

**Files:**
- Modify: `patterns/demo-ant-buster.php` — every row in `ANT_KINDS` (~1081–1300), the ant-bar rendering function (search for the existing `ant.hp` / red bar render).

- [ ] **Step 1: Find the existing HP-bar render**

  ```bash
  grep -n 'ant\.hp\b\|fillRect.*hp' patterns/demo-ant-buster.php | head -20
  ```

  Locate the helper that draws the red HP bar above each ant. Likely inside `drawAnts` or a `drawAntHpBar` helper. Note its current signature and existing show-only-when-damaged guard.

- [ ] **Step 2: Add `barStyle` field with sensible defaults**

  At the top of the `ANT_KINDS` definition (line ~1081), add a comment:

  ```js
  // barStyle (optional): { width, height, color, borderColor, persistent }.
  // Default = damage-triggered 16x2 red bar. Override per-kind for boss escalation.
  ```

  No edits to existing rows yet — they'll fall back to default. Defaults read by the renderer if `k.barStyle` is undefined.

- [ ] **Step 3: Refactor the bar render**

  Replace the existing HP-bar drawing logic with:

  ```js
  function drawAntHpBar(ant) {
    var k = ANT_KINDS[ant.kind];
    var style = k.barStyle;
    var persistent = style && style.persistent;
    var damaged = ant.hp < ant.maxHp;
    if (!persistent && !damaged) { return; }

    var bw = (style && style.width) ? style.width : 16;
    var bh = (style && style.height) ? style.height : 2;
    var color = (style && style.color) ? style.color : '#e43b44';
    var border = (style && style.borderColor) ? style.borderColor : null;

    var bx = ant.x - bw / 2;
    var by = ant.y - 14;
    var pct = ant.hp / ant.maxHp;
    if (pct < 0) { pct = 0; }

    if (border) {
      ctx.fillStyle = border;
      ctx.fillRect(bx - 1, by - 1, bw + 2, bh + 2);
    }
    ctx.fillStyle = '#262b44';
    ctx.fillRect(bx, by, bw, bh);
    ctx.fillStyle = color;
    ctx.fillRect(bx, by, bw * pct, bh);
  }
  ```

  Make sure to read `ant.maxHp`, which Task 2 added on every ant.

- [ ] **Step 4: Wire `drawAntHpBar` into the ant render loop**

  Find the existing inline HP-bar drawing inside `drawAnts` (or wherever — see Step 1) and replace with `drawAntHpBar(ant);`.

- [ ] **Step 5: Lint + curl-grep + Playwright**

  Verify a fresh wave 1 plays: workers walk normally, HP bars appear when they take damage from a Howitzer, vanish when they die. Confirm visual is unchanged for default ants.

- [ ] **Step 6: Commit**

  ```bash
  git commit -am "refactor(ant-buster): barStyle field on ANT_KINDS + persistent HP bar support"
  ```

---

## Task 4: Selected-tower panel — variable upgrade button count (0 / 2 / 3)

**Files:**
- Modify: `patterns/demo-ant-buster.php` — `drawTowerPanel` (~600–670), `ui.upgradeRects` initialization (~2125), reset sites for upgradeRects (~1069–1071, ~2109–2111), click handler that iterates upgradeRects (~2197–2204).

Plan 2 caps `upgradeRects` at 2. Plan 3 needs 3 for base cannons (Howitzer / Repeater / Twin Gun → 3 mid options).

- [ ] **Step 1: Extend `ui.upgradeRects` to 3 slots**

  At line ~2125 (in `ui` initialization):

  ```js
  upgradeRects: [null, null, null], // up-to-3 upgrade buttons (3 for base, 2 for mid, 0 for final)
  ```

- [ ] **Step 2: Update every reset site**

  Search for `ui.upgradeRects[0]`, `ui.upgradeRects[1]`:

  ```bash
  grep -n 'ui\.upgradeRects' patterns/demo-ant-buster.php
  ```

  Wherever the existing pair `ui.upgradeRects[0] = null; ui.upgradeRects[1] = null;` appears, replace with:

  ```js
  ui.upgradeRects[0] = null;
  ui.upgradeRects[1] = null;
  ui.upgradeRects[2] = null;
  ```

  Sites: inside `render()` when no panel is open (lines 1069–1071 in Plan 2), inside `resetGame` (lines 2109–2111).

- [ ] **Step 3: Generalize the upgrade-button render loop**

  In `drawTowerPanel` around line 642–655, the existing loop is:

  ```js
  for (var i = 0; i < upgrades.length; i++) {
    var uk = upgrades[i];
    var udef = CANNON_KINDS[uk];
    var by = py + 90 + i * 22;
    var canAfford = (game.gold >= udef.cost);
    ctx.fillStyle = canAfford ? '#3a8c3a' : '#444';
    ctx.fillRect(px + 6, by, pw - 12, 18);
    ctx.fillStyle = canAfford ? '#fee761' : '#888';
    ctx.fillText('-> ' + udef.name + ' (' + udef.cost + 'g)', px + 10, by + 12);
    ui.upgradeRects[i] = { x: px + 6, y: by, w: pw - 12, h: 18, toKind: uk };
  }
  for (var k = upgrades.length; k < 2; k++) { ui.upgradeRects[k] = null; }
  ```

  Replace with:

  ```js
  for (var i = 0; i < upgrades.length; i++) {
    var uk = upgrades[i];
    var udef = CANNON_KINDS[uk];
    var by = py + 90 + i * 22;
    var canAfford = (game.gold >= udef.cost);
    ctx.fillStyle = canAfford ? '#3a8c3a' : '#444';
    ctx.fillRect(px + 6, by, pw - 12, 18);
    ctx.fillStyle = canAfford ? '#fee761' : '#888';
    ctx.fillText('-> ' + udef.name + ' (' + udef.cost + 'g)', px + 10, by + 12);
    ui.upgradeRects[i] = { x: px + 6, y: by, w: pw - 12, h: 18, toKind: uk };
  }
  for (var k = upgrades.length; k < 3; k++) { ui.upgradeRects[k] = null; }
  ```

  The only change is `< 2` → `< 3` in the trailing zero-fill loop.

- [ ] **Step 4: Update the click handler to iterate 3 slots**

  Around line ~2197, the click handler iterates over `upgradeRects`:

  ```js
  for (var ri = 0; ri < ui.upgradeRects.length; ri++) {
    var ur = ui.upgradeRects[ri];
    // ...
  }
  ```

  This already iterates over `.length`, so no change needed. Confirm by reading the loop bound — if it's a hard-coded `< 2`, change to `< 3` or `< ui.upgradeRects.length`.

- [ ] **Step 5: Sell-button position**

  Sell button currently at `py + 200 - 26 = py + 174` (line ~659). With 3 buttons at `+90, +112, +134` (top-of-button), the bottom of the third button is `+134 + 18 = +152`. Sell at `+174` leaves 22px gap — fine. If panel feels cramped after Task 12 lands all the upgrade options, revisit.

- [ ] **Step 6: Range-circle preview honesty**

  Plan 2's range circle for the *current* cannon is drawn at line 1057 (`drawRangeCircle(ui.selectedCannon)`). The hover-preview for upgrades doesn't currently exist. Add:

  - In `ui` state, add `hoveredUpgrade: null` (line ~2125).
  - In the `mousemove` handler, after hit-testing the panel, set `ui.hoveredUpgrade` to the `toKind` of any hovered upgradeRect, or null.
  - In `drawRangeCircle`, when an upgrade is hovered, draw a *secondary* circle in dashed stroke at the upgrade target's range:

  ```js
  function drawRangeCircle(cannon) {
    var def = CANNON_KINDS[cannon.kind];
    ctx.strokeStyle = '#fee761';
    ctx.beginPath();
    ctx.arc(cannon.x, cannon.y, def.range, 0, Math.PI * 2);
    ctx.stroke();

    if (ui.hoveredUpgrade) {
      var udef = CANNON_KINDS[ui.hoveredUpgrade];
      if (udef) {
        ctx.strokeStyle = '#a040c0';
        ctx.setLineDash([4, 4]);
        ctx.beginPath();
        ctx.arc(cannon.x, cannon.y, udef.range, 0, Math.PI * 2);
        ctx.stroke();
        ctx.setLineDash([]);
      }
    }
  }
  ```

  This is what makes the Plasma Torch range-shrink (130 → 70) visible.

- [ ] **Step 7: Lint + curl-grep + Playwright**

  Place a Howitzer. Click it. Hover the upgrade button — the preview circle should match Siege's range (still 110 — same as Howitzer). Now manually edit `CANNON_KINDS.howitzer.upgradesTo` to test 3 buttons: temporarily set `upgradesTo: ['siege', 'siege', 'siege']`. Confirm 3 buttons render. Revert before committing.

- [ ] **Step 8: Commit**

  ```bash
  git commit -am "refactor(ant-buster): selected-tower panel supports 0/2/3 upgrade buttons + range-shrink preview"
  ```

---

## Task 5: Bonus-damage modifier hook on CANNON_KINDS

**Files:**
- Modify: `patterns/demo-ant-buster.php` — `CANNON_KINDS` schema doc (~1625), `applyDamage` (~1901).

Used by Marksman / Sharpshooter (vs full-HP) and Phantom (vs Queen) in later tasks. Schema-only Task — no cannon uses it yet.

- [ ] **Step 1: Schema doc comment in CANNON_KINDS**

  Above the `CANNON_KINDS` literal (line ~1623), append to the existing comment block:

  ```js
  // Optional fields (Plan 3):
  //   damageModifier: function(target, projectile) → multiplier
  //     Per-cannon damage formula hook. Returns 1 by default. Marksman returns
  //     1.5 vs full-HP targets; Phantom returns 2 vs Queens; etc.
  //   linePierce: number — projectile passes through up to N ants (Plan 3).
  //   armorPierce: bool — bypass armored mitigation (Plan 3).
  //   splash: { radius, falloff: number 0..1 } — AoE damage on impact.
  //   homing: bool — projectile re-targets each tick (Patriot).
  //   arc: { height, travelTime } — ballistic arcing visual + fixed land time.
  //   returning: { phases } — projectile reverses path after first hit.
  //   pellets: number — multi-pellet cone (Buckshot).
  //   coneDeg: degrees — spread cone for pellets or aim jitter (Minigun).
  //   beam: { tickDamage, tickInterval, statusOnTick } — continuous beam (Lance).
  //   coneDot: { range, angleDeg, tickDamage, statusOnTick } — Plasma Torch.
  //   teslaArc: { jumps, range, falloff } — chain-jump targeting (Tesla).
  //   reveal: bool — sets target.isRevealed = true each tick (Spotter).
  ```

  No code changes here — just a documentation block. The fields are added to specific rows in later tasks.

- [ ] **Step 2: `applyDamage` reads `damageModifier`**

  Replace the function body (line 1901–1910) with:

  ```js
  function applyDamage(ant, amount, armorPierce, sourceCannon) {
    var k = ANT_KINDS[ant.kind];
    var dmg = amount;
    if (sourceCannon) {
      var sk = CANNON_KINDS[sourceCannon.kind];
      if (sk.damageModifier) {
        dmg = dmg * sk.damageModifier(ant, sourceCannon);
      }
    }
    if (!armorPierce) {
      var armor = k.armor;
      if (!armor) { armor = 0; }
      dmg = dmg * (1 - armor);
    }
    ant.hp = ant.hp - dmg;
  }
  ```

- [ ] **Step 3: Update every `applyDamage` call site**

  ```bash
  grep -n 'applyDamage(' patterns/demo-ant-buster.php
  ```

  Each call site — projectile collision in `updateProjectiles`, beam tick in `updateCannons` (Glacier), cone DoT (Plan 3) — must pass `sourceCannon` as the 4th argument. For projectile collision, that's `p.sourceCannon` (set by Task 1 Step 2). For Glacier beam, the surrounding loop knows the cannon; pass it through. Update each call:

  ```js
  applyDamage(a, p.damage, p.armorPierce, p.sourceCannon);
  ```

  Glacier beam call:

  ```js
  applyDamage(targetAnt, beamDef.beam.tickDamage * dt, beamDef.armorPierce, beamCannon);
  ```

- [ ] **Step 4: Lint + curl-grep + Playwright**

  Place Howitzer + worker. Confirm damage still applies normally (modifier returns 1 since no cannons use it yet — falls through to existing armor calc). No regressions.

- [ ] **Step 5: Commit**

  ```bash
  git commit -am "feat(ant-buster): damageModifier hook on CANNON_KINDS, applyDamage reads sourceCannon"
  ```

---

## Task 6: Splash AoE primitive + arc primitive + Mortar (1.2)

**Files:**
- Modify: `patterns/demo-ant-buster.php` — append to `CANNON_KINDS` near related Heavy entries; new helpers `applySplash` and arc-position math; add `'mortar'` to `CANNON_KINDS.howitzer.upgradesTo`; sprite factory `makeMortarTurret`.

- [ ] **Step 1: Add `applySplash` helper**

  Place above `updateProjectiles` (~line 1939):

  ```js
  function applySplash(x, y, radius, damage, falloff, sourceCannon) {
    for (var i = 0; i < game.ants.length; i++) {
      var a = game.ants[i];
      if (a.dead) { continue; }
      if (a.intangible) { continue; }
      var dx = a.x - x;
      var dy = a.y - y;
      var dist = Math.sqrt(dx * dx + dy * dy);
      if (dist > radius) { continue; }
      var pct;
      if (falloff > 0) { pct = 1 - (dist / radius) * falloff; } else { pct = 1; }
      if (pct < 0) { pct = 0; }
      applyDamage(a, damage * pct, false, sourceCannon);
      if (a.hp <= 0) { killAnt(a, sourceCannon); }
    }
  }
  ```

- [ ] **Step 2: Splash trigger in `updateProjectiles`**

  Inside the collision body (right after `applyDamage` — see Task 1 Step 4), add:

  ```js
  var sk = p.sourceCannon ? CANNON_KINDS[p.sourceCannon.kind] : null;
  if (sk && sk.splash) {
    applySplash(p.x, p.y, sk.splash.radius, p.damage * sk.splash.splashMult, sk.splash.falloff, p.sourceCannon);
  }
  ```

  Where `splash.splashMult` is the splash-damage portion (e.g. Mortar's `splash 4` against `damage 8` = `splashMult 0.5`).

- [ ] **Step 3: Arc visual primitive**

  Plan 2's projectiles fly in a straight line. Mortar shells arc. Add an `arcStartedAt` field on the projectile when fired, and during render compute a y-offset:

  In `fireProjectile` (Task 1 Step 2 already touched this), append to the projectile literal:

  ```js
  arcing: !!def.arc,
  arcStartedAt: def.arc ? game.time : 0,
  arcDuration: def.arc ? def.arc.travelTime : 0,
  arcHeight: def.arc ? def.arc.height : 0,
  ```

  In the projectile-render path (search for `drawProjectile` or where projectiles draw), if `p.arcing`:

  ```js
  var t = (game.time - p.arcStartedAt) / p.arcDuration;
  if (t > 1) { t = 1; }
  var arcOffset = -Math.sin(t * Math.PI) * p.arcHeight;
  ctx.fillRect(p.x - 1, p.y - 1 + arcOffset, 3, 3);  // visual offset only
  ```

  (Collision still resolves on `(p.x, p.y)` — arc is purely cosmetic.)

- [ ] **Step 4: Add Mortar (1.2) to CANNON_KINDS**

  Inside `CANNON_KINDS`, near the Heavy family entries:

  ```js
  mortar: {
    name: 'Mortar', cost: 60, damage: 8, fireRate: 0.5, range: 130,
    projectileSpeed: 200, color: '#5a3a1a',
    family: 'heavy', tier: 2,
    splash: { radius: 40, falloff: 0.5, splashMult: 0.5 },
    arc: { height: 24, travelTime: 0.6 },
    upgradesTo: ['hellfire', 'patriot']    // Tasks 10, 8
  },
  ```

- [ ] **Step 5: Wire into Howitzer's upgradesTo**

  Find Howitzer's row and update:

  ```js
  upgradesTo: ['siege', 'mortar', 'discus']    // discus added in Task 7
  ```

  (Add `'discus'` here even though the kind doesn't exist yet — the panel-render code reads `CANNON_KINDS[uk]` and will crash if `discus` isn't defined yet. Workaround: only add `'mortar'` here in Task 6, then `'discus'` in Task 7. So:)

  ```js
  upgradesTo: ['siege', 'mortar']
  ```

  Task 7 adds `'discus'` to the array.

- [ ] **Step 6: Sprite — `makeMortarTurret`**

  Plan 2's cannon turret factory pattern: a small canvas sprite. Add (near other `makeXTurret` factories):

  ```js
  function makeMortarTurret() {
    var s = document.createElement('canvas');
    s.width = 24; s.height = 24;
    var sc = s.getContext('2d');
    sc.fillStyle = '#5a3a1a';
    sc.fillRect(8, 4, 8, 14);  // short stubby barrel pointing up
    sc.fillStyle = '#3a2a0a';
    sc.fillRect(6, 10, 12, 10);  // base
    return s;
  }
  ```

  Wire into the cannon-sprite lookup (look for the existing pattern that maps `cannon.kind` → sprite). Add a `'mortar'` entry.

- [ ] **Step 7: Lint + curl-grep + Playwright**

  Build Howitzer → Mortar (via Hover-upgrade button: select Howitzer, click Mortar 60g). Spawn a worker swarm via `T`. Confirm Mortar fires arcing shells that splash on impact (multiple ants take damage from one shot if grouped). Range circle 130. Damage 8 direct + 4 splash with falloff.

  ```js
  return JSON.stringify({
    mortar: {
      def: window.__abCannonKinds.mortar,
      placed: window.__ab.cannons.find(function(c){ return c.kind === 'mortar'; })
    }
  });
  ```

- [ ] **Step 8: Commit**

  ```bash
  git commit -am "feat(ant-buster): splash + arc primitives + Mortar (1.2)"
  ```

---

## Task 7: Returning projectile primitive + Discus (1.3)

**Files:**
- Modify: `patterns/demo-ant-buster.php` — `updateProjectiles` (handle return phase), `fireProjectile` (init `phase: 'out'`), CANNON_KINDS Discus row, Howitzer.upgradesTo.

- [ ] **Step 1: Projectile fields for return phase**

  In `fireProjectile` (Task 1 Step 2), append to the projectile literal:

  ```js
  returning: !!def.returning,
  phase: 'out',
  originX: cannon.x,
  originY: cannon.y,
  ```

- [ ] **Step 2: Update `updateProjectiles` to handle return phase**

  Inside the projectile loop, after the collision body decides `hit = true; break;`, intercept with:

  ```js
  if (p.returning && p.phase === 'out') {
    // Flip to return phase: keep flying back to origin instead of expiring
    p.phase = 'return';
    var rdx = p.originX - p.x;
    var rdy = p.originY - p.y;
    var rmag = Math.sqrt(rdx * rdx + rdy * rdy);
    var speed = Math.sqrt(p.vx * p.vx + p.vy * p.vy);
    p.vx = (rdx / rmag) * speed;
    p.vy = (rdy / rmag) * speed;
    hit = false;
    p.life = 1.5;  // reset life for the return leg
    // Don't break — let the projectile keep flying. But this hit is done.
    p._sawAnts.push(a);
    continue;  // skip the inner ant loop, but stay in the outer projectile loop
  }
  ```

  And on the return phase, also check for `dist < 4` from origin to despawn the projectile:

  Inside the per-projectile bounds check (around line 1966, where life and bounds are validated), add:

  ```js
  if (p.phase === 'return') {
    var ddx = p.originX - p.x;
    var ddy = p.originY - p.y;
    if (ddx * ddx + ddy * ddy < 4 * 4) { /* drop, don't push to kept */ continue; }
  }
  ```

- [ ] **Step 3: Add Discus (1.3) to CANNON_KINDS**

  ```js
  discus: {
    name: 'Discus', cost: 60, damage: 4, fireRate: 0.4, range: 150,
    projectileSpeed: 220, color: '#9b5a2a',
    family: 'heavy', tier: 2,
    returning: true,
    upgradesTo: ['ricochet', 'shockwave']    // Tasks 11, 12
  },
  ```

- [ ] **Step 4: Wire Discus into Howitzer.upgradesTo**

  ```js
  upgradesTo: ['siege', 'mortar', 'discus']
  ```

- [ ] **Step 5: Sprite — `makeDiscusTurret`**

  Add a small disc-launcher sprite — flat disc on top of base:

  ```js
  function makeDiscusTurret() {
    var s = document.createElement('canvas');
    s.width = 24; s.height = 24;
    var sc = s.getContext('2d');
    sc.fillStyle = '#9b5a2a';
    sc.fillRect(4, 12, 16, 4);  // wide horizontal launcher
    sc.fillStyle = '#3a2a0a';
    sc.fillRect(8, 8, 8, 8);    // base
    return s;
  }
  ```

  Wire into the cannon-sprite lookup.

- [ ] **Step 6: Lint + curl-grep + Playwright**

  Build Howitzer → Discus. Confirm: panel shows 3 upgrade buttons (Siege, Mortar, Discus) when Howitzer is selected. Discus fires; projectile travels to first hit, then reverses path back to the cannon. Discus deals 4 damage on first hit, 4 on return = 8 total per shot if the path crosses ants twice.

- [ ] **Step 7: Commit**

  ```bash
  git commit -am "feat(ant-buster): returning projectile primitive + Discus (1.3)"
  ```

---

## Task 8: Homing primitive + Patriot (1.22)

**Files:**
- Modify: `patterns/demo-ant-buster.php` — `updateProjectiles` adds homing steer per tick, CANNON_KINDS Patriot row, Mortar.upgradesTo.

- [ ] **Step 1: Add `homing` field on projectile, steer per tick**

  In `fireProjectile`, append to the projectile literal:

  ```js
  homing: !!def.homing,
  homingTurnRate: def.homing ? def.homing.turnRate : 0,
  target: target,    // current homing target
  ```

  (`target` was already fed in to the function — pass it through to the projectile.)

  In `updateProjectiles`, before the position update, add:

  ```js
  if (p.homing) {
    var t = p.target;
    if (!t || t.dead) {
      // re-acquire nearest ant
      var nearest = null;
      var bestDist = Infinity;
      for (var k = 0; k < game.ants.length; k++) {
        var ka = game.ants[k];
        if (ka.dead) { continue; }
        if (ka.intangible) { continue; }
        var kdx = ka.x - p.x;
        var kdy = ka.y - p.y;
        var kd = kdx * kdx + kdy * kdy;
        if (kd < bestDist) { bestDist = kd; nearest = ka; }
      }
      p.target = nearest;
      t = nearest;
    }
    if (t) {
      var desiredAngle = Math.atan2(t.y - p.y, t.x - p.x);
      var currentAngle = Math.atan2(p.vy, p.vx);
      var delta = desiredAngle - currentAngle;
      while (delta > Math.PI) { delta = delta - Math.PI * 2; }
      while (delta < -Math.PI) { delta = delta + Math.PI * 2; }
      var maxTurn = p.homingTurnRate * dt;
      if (delta > maxTurn) { delta = maxTurn; }
      if (delta < -maxTurn) { delta = -maxTurn; }
      var newAngle = currentAngle + delta;
      var speed = Math.sqrt(p.vx * p.vx + p.vy * p.vy);
      p.vx = Math.cos(newAngle) * speed;
      p.vy = Math.sin(newAngle) * speed;
    }
  }
  ```

- [ ] **Step 2: Add Patriot (1.22) to CANNON_KINDS**

  ```js
  patriot: {
    name: 'Patriot', cost: 110, damage: 20, fireRate: 0.5, range: 200,
    projectileSpeed: 260, color: '#3a4d8a',
    family: 'heavy', tier: 3,
    homing: { turnRate: 4.0 },                  // rad/s
    splash: { radius: 30, falloff: 0.4, splashMult: 0.4 },
    upgradesTo: []
  },
  ```

- [ ] **Step 3: Wire Patriot into Mortar.upgradesTo**

  Mortar's `upgradesTo` already has `['hellfire', 'patriot']` from Task 6 — no change needed.

- [ ] **Step 4: Sprite — `makePatriotTurret`**

  ```js
  function makePatriotTurret() {
    var s = document.createElement('canvas');
    s.width = 28; s.height = 28;
    var sc = s.getContext('2d');
    sc.fillStyle = '#3a4d8a';
    sc.fillRect(10, 4, 8, 20);  // tall vertical launcher
    sc.fillStyle = '#1a2d4a';
    sc.fillRect(6, 18, 16, 8);  // base
    sc.fillStyle = '#fee761';
    sc.fillRect(13, 6, 2, 4);   // missile tip
    return s;
  }
  ```

- [ ] **Step 5: Lint + curl-grep + Playwright**

  Build Howitzer → Mortar → Patriot. Spawn a fast-moving Scout. Patriot's missile should bend to track the Scout instead of flying straight. On impact, splash radius 30 hits adjacent ants. Damage 20 + 8 splash on radius hits.

- [ ] **Step 6: Commit**

  ```bash
  git commit -am "feat(ant-buster): homing projectile primitive + Patriot (1.22)"
  ```

---

## Task 9: Demolisher (1.12) — armorPierce-only

**Files:**
- Modify: `patterns/demo-ant-buster.php` — append Demolisher to CANNON_KINDS, add to Siege.upgradesTo.

Pure data + sprite. Reuses Task 1's `armorPierce` flag.

- [ ] **Step 1: Add Demolisher to CANNON_KINDS**

  ```js
  demolisher: {
    name: 'Demolisher', cost: 110, damage: 60, fireRate: 0.25, range: 110,
    projectileSpeed: 260, color: '#7a3a1a',
    family: 'heavy', tier: 3,
    armorPierce: true,    // single-target armor-bypass; no line-pierce
    upgradesTo: []
  },
  ```

- [ ] **Step 2: Wire into Siege.upgradesTo**

  Find Siege's row. Update its `upgradesTo` from `['railgun']` to:

  ```js
  upgradesTo: ['railgun', 'demolisher']
  ```

- [ ] **Step 3: Sprite — `makeDemolisherTurret`**

  ```js
  function makeDemolisherTurret() {
    var s = document.createElement('canvas');
    s.width = 28; s.height = 28;
    var sc = s.getContext('2d');
    sc.fillStyle = '#7a3a1a';
    sc.fillRect(4, 11, 22, 6);  // very thick barrel
    sc.fillStyle = '#3a1a0a';
    sc.fillRect(8, 8, 12, 12);
    return s;
  }
  ```

- [ ] **Step 4: Playwright verification**

  Build Howitzer → Siege → Demolisher. Spawn a Tank (`T` cycle). Confirm Demolisher deals 60 dmg per shot (full damage, no halving from Tank's armor). Confirm projectile does NOT pass through to a second ant (it has `armorPierce` only, not `linePierce`).

- [ ] **Step 5: Lint + curl-grep + commit**

  ```bash
  git commit -am "feat(ant-buster): Demolisher (1.12) — armorPierce-only Heavy final"
  ```

---

## Task 10: Hellfire (1.21) — arcing splash

**Files:**
- Modify: `patterns/demo-ant-buster.php` — append Hellfire to CANNON_KINDS.

Reuses splash + arc primitives from Task 6.

- [ ] **Step 1: Add Hellfire to CANNON_KINDS**

  ```js
  hellfire: {
    name: 'Hellfire', cost: 90, damage: 15, fireRate: 0.4, range: 180,
    projectileSpeed: 200, color: '#d04020',
    family: 'heavy', tier: 3,
    splash: { radius: 60, falloff: 0.4, splashMult: 0.67 },
    arc: { height: 32, travelTime: 0.7 },
    upgradesTo: []
  },
  ```

- [ ] **Step 2: Sprite — `makeHellfireTurret`**

  ```js
  function makeHellfireTurret() {
    var s = document.createElement('canvas');
    s.width = 28; s.height = 28;
    var sc = s.getContext('2d');
    sc.fillStyle = '#d04020';
    sc.fillRect(7, 4, 14, 12);  // wide flared barrel
    sc.fillStyle = '#5a1a0a';
    sc.fillRect(6, 14, 16, 10);
    return s;
  }
  ```

- [ ] **Step 3: Playwright verify**

  Build Howitzer → Mortar → Hellfire. Confirm arc visual + 60-radius splash on a worker swarm.

- [ ] **Step 4: Lint + curl-grep + commit**

  ```bash
  git commit -am "feat(ant-buster): Hellfire (1.21) — arcing splash"
  ```

---

## Task 11: Ricochet (1.31) — return + line-pierce on return

**Files:**
- Modify: `patterns/demo-ant-buster.php` — extend returning projectile to support pierce on the return leg, append Ricochet to CANNON_KINDS.

Per spec: "hits 3 times (out, pierce, return)." Out-leg single hit → reverses → pierce-1 on return path → hits origin. So 3 contact opportunities: out hit, mid-return pierce hit, return-end (which doesn't usually hit anyone since the cannon despawns it). Practical implementation: out-leg + return-leg with `linePierce: 1` on the return leg.

- [ ] **Step 1: Extend returning-projectile primitive**

  In `updateProjectiles`, when transitioning from `'out'` to `'return'` (Task 7 Step 2), if the cannon's def has `returnPierce`, set the projectile's pierce counter:

  In the phase-transition block:

  ```js
  if (p.returning && p.phase === 'out') {
    // ... existing flip-to-return ...
    p.phase = 'return';
    if (sk && sk.returnPierce) {
      p.pierceLeft = sk.returnPierce;
    }
    // ...
  }
  ```

  This re-arms the line-pierce counter on the return leg only.

- [ ] **Step 2: Add Ricochet to CANNON_KINDS**

  ```js
  ricochet: {
    name: 'Ricochet', cost: 90, damage: 8, fireRate: 0.4, range: 180,
    projectileSpeed: 240, color: '#cc6a4a',
    family: 'heavy', tier: 3,
    returning: true,
    returnPierce: 1,
    upgradesTo: []
  },
  ```

- [ ] **Step 3: Wire into Discus.upgradesTo** (already has `['ricochet', 'shockwave']` from Task 7 — confirm.)

- [ ] **Step 4: Sprite — `makeRicochetTurret`**

  ```js
  function makeRicochetTurret() {
    var s = document.createElement('canvas');
    s.width = 28; s.height = 28;
    var sc = s.getContext('2d');
    sc.fillStyle = '#cc6a4a';
    sc.fillRect(2, 12, 24, 4);  // wide horizontal disc-launcher
    sc.fillStyle = '#9b3a1a';
    sc.fillRect(10, 8, 8, 12);
    return s;
  }
  ```

- [ ] **Step 5: Playwright verify**

  Build Howitzer → Discus → Ricochet. Confirm: 3 hit opportunities per shot (out hit, return-pierce mid-hit, return-end). Disc returns to cannon and despawns.

- [ ] **Step 6: Lint + curl-grep + commit**

  ```bash
  git commit -am "feat(ant-buster): Ricochet (1.31) — returning + return-leg pierce"
  ```

---

## Task 12: Shockwave (1.32) — massive splash + brief screenshake

**Files:**
- Modify: `patterns/demo-ant-buster.php` — Shockwave row, add `game.screenshake` state, render-side shake offset.

- [ ] **Step 1: Add screenshake state**

  In the `game` initial state object (around line 100):

  ```js
  screenshake: { magnitude: 0, decay: 0 },  // { magnitude: px, decay: 1/s }
  ```

  Reset in `resetGame` similarly.

- [ ] **Step 2: Decay screenshake each tick**

  In `update(dt)` near the top:

  ```js
  if (game.screenshake.magnitude > 0) {
    game.screenshake.magnitude = game.screenshake.magnitude - game.screenshake.decay * dt;
    if (game.screenshake.magnitude < 0) { game.screenshake.magnitude = 0; }
  }
  ```

- [ ] **Step 3: Apply offset in render**

  At the top of `render()`, before any drawing:

  ```js
  var sx = 0, sy = 0;
  if (game.screenshake.magnitude > 0) {
    sx = (Math.random() * 2 - 1) * game.screenshake.magnitude;
    sy = (Math.random() * 2 - 1) * game.screenshake.magnitude;
    ctx.save();
    ctx.translate(sx, sy);
  }
  // ... existing render ...
  if (game.screenshake.magnitude > 0) { ctx.restore(); }
  ```

- [ ] **Step 4: Trigger screenshake from Shockwave splash**

  Extend the splash trigger in `updateProjectiles` (Task 6 Step 2):

  ```js
  if (sk && sk.splash) {
    applySplash(p.x, p.y, sk.splash.radius, p.damage * sk.splash.splashMult, sk.splash.falloff, p.sourceCannon);
    if (sk.screenshake) {
      game.screenshake.magnitude = sk.screenshake.magnitude;
      game.screenshake.decay = sk.screenshake.decay;
    }
  }
  ```

- [ ] **Step 5: Add Shockwave to CANNON_KINDS**

  ```js
  shockwave: {
    name: 'Shockwave', cost: 110, damage: 50, fireRate: 0.15, range: 130,
    projectileSpeed: 180, color: '#a040c0',
    family: 'heavy', tier: 3,
    splash: { radius: 80, falloff: 0.3, splashMult: 0.6 },
    screenshake: { magnitude: 3, decay: 12 },    // 3 px shake, decays in 0.25s
    upgradesTo: []
  },
  ```

  Wire into Discus.upgradesTo (already has `'shockwave'` from Task 7).

- [ ] **Step 6: Sprite — `makeShockwaveTurret`**

  ```js
  function makeShockwaveTurret() {
    var s = document.createElement('canvas');
    s.width = 28; s.height = 28;
    var sc = s.getContext('2d');
    sc.fillStyle = '#a040c0';
    sc.fillRect(10, 6, 8, 14);
    sc.fillStyle = '#5a1a5a';
    sc.fillRect(4, 16, 20, 8);
    sc.fillStyle = '#fee761';
    sc.fillRect(11, 8, 6, 2);
    return s;
  }
  ```

- [ ] **Step 7: Lint + curl-grep + Playwright**

  Build Howitzer → Discus → Shockwave. Fire on a worker swarm. Confirm: 80-radius splash takes down many ants per shot, brief visible screenshake on impact.

- [ ] **Step 8: Commit**

  ```bash
  git commit -am "feat(ant-buster): Shockwave (1.32) — massive splash + brief screenshake"
  ```

---

## Task 13: Marksman (2.2) — bonus vs full-HP

**Files:**
- Modify: `patterns/demo-ant-buster.php` — append Marksman to CANNON_KINDS, wire into Repeater.upgradesTo.

Uses the `damageModifier` hook from Task 5.

- [ ] **Step 1: Add Marksman to CANNON_KINDS**

  ```js
  marksman: {
    name: 'Marksman', cost: 70, damage: 8, fireRate: 0.6, range: 250,
    projectileSpeed: 320, color: '#6a8a3a',
    family: 'quick', tier: 2,
    damageModifier: function(target) {
      if (target.hp >= target.maxHp - 0.001) { return 1.5; }
      return 1;
    },
    upgradesTo: ['sharpshooter', 'lance']    // Tasks 16, 17
  },
  ```

- [ ] **Step 2: Wire into Repeater.upgradesTo**

  Find Repeater's row. Update from `['autocannon']` to:

  ```js
  upgradesTo: ['autocannon', 'marksman']
  ```

  (Task 14 will append `'spotter'`.)

- [ ] **Step 3: Sprite — `makeMarksmanTurret`**

  ```js
  function makeMarksmanTurret() {
    var s = document.createElement('canvas');
    s.width = 28; s.height = 28;
    var sc = s.getContext('2d');
    sc.fillStyle = '#6a8a3a';
    sc.fillRect(2, 12, 24, 3);  // long thin barrel
    sc.fillStyle = '#3a5a1a';
    sc.fillRect(10, 9, 8, 9);   // base
    sc.fillStyle = '#fee761';
    sc.fillRect(20, 11, 2, 5);  // scope
    return s;
  }
  ```

- [ ] **Step 4: Playwright verify**

  Build Repeater → Marksman. Spawn full-HP workers. First shot deals 12 (8 × 1.5). After damage, subsequent shots deal 8.

- [ ] **Step 5: Lint + curl-grep + commit**

  ```bash
  git commit -am "feat(ant-buster): Marksman (2.2) — +50% vs full-HP via damageModifier"
  ```

---

## Task 14: Spotter primitive + Spotter (2.3)

**Files:**
- Modify: `patterns/demo-ant-buster.php` — `findTarget` (~1781) reads `ant.isRevealed`, `updateCannons` per-tick reveal sweep, append Spotter row, Repeater.upgradesTo.

- [ ] **Step 1: Add reveal flag init in `spawnAnt`**

  In Task 2's spawnAnt body, add `isRevealed: false` to the ant literal.

- [ ] **Step 2: Per-tick reveal sweep in `updateCannons`**

  Inside the cannon update loop, before firing logic:

  ```js
  if (def.reveal) {
    for (var ri = 0; ri < game.ants.length; ri++) {
      var ra = game.ants[ri];
      if (ra.dead) { continue; }
      if (!ra.carrying) { continue; }    // Spotter only reveals Carriers
      var rdx = ra.x - cannon.x;
      var rdy = ra.y - cannon.y;
      if (rdx * rdx + rdy * rdy <= def.range * def.range) {
        ra.isRevealed = true;
      }
    }
  }
  ```

  Note: reveal-sweep runs every tick a Spotter is in range; Carriers stay flagged frame-to-frame because they may be revealed by another Spotter, or this same one continuously. To clear when no Spotter sees them, reset all `isRevealed` to false at the *start* of each tick before any Spotter sweep. Simpler: reset all `isRevealed = false` in a pre-pass at the top of `updateCannons`, then each Spotter sets the flag for ants in range.

- [ ] **Step 3: `findTarget` priority for revealed**

  In `findTarget` (line ~1781):

  ```js
  function findTarget(cannon) {
    var def = CANNON_KINDS[cannon.kind];
    var revealedTarget = null;
    var nearestTarget = null;
    var bestDist = Infinity;
    for (var i = 0; i < game.ants.length; i++) {
      var a = game.ants[i];
      if (a.dead) { continue; }
      if (a.intangible) { continue; }
      var dx = a.x - cannon.x;
      var dy = a.y - cannon.y;
      var dist2 = dx * dx + dy * dy;
      if (dist2 > def.range * def.range) { continue; }
      if (a.isRevealed && !revealedTarget) { revealedTarget = a; }
      if (dist2 < bestDist) { bestDist = dist2; nearestTarget = a; }
    }
    if (revealedTarget) { return revealedTarget; }
    return nearestTarget;
  }
  ```

  All cannons benefit, not just Spotters. That's the intended global-flag behavior.

- [ ] **Step 4: Add Spotter to CANNON_KINDS**

  ```js
  spotter: {
    name: 'Spotter', cost: 60, damage: 3, fireRate: 2, range: 200,
    projectileSpeed: 280, color: '#aa8a3a',
    family: 'quick', tier: 2,
    reveal: true,
    upgradesTo: ['phantom', 'tesla']    // Tasks 18, 19
  },
  ```

- [ ] **Step 5: Wire into Repeater.upgradesTo**

  ```js
  upgradesTo: ['autocannon', 'marksman', 'spotter']
  ```

- [ ] **Step 6: Sprite + verification + commit**

  Add `makeSpotterTurret` (cyan/yellow scope-style sprite). Build Repeater → Spotter. Set up a Carrier scenario (let a Worker grab a slice and become a carrier). Confirm `window.__ab.ants.find(a => a.carrying).isRevealed === true` once the Spotter is in range. Confirm other towers in scene start prioritizing the carrier.

  ```bash
  git commit -am "feat(ant-buster): Spotter (2.3) + global isRevealed targeting bias"
  ```

---

## Task 15: Cone-DoT primitive + Plasma Torch (2.12)

**Files:**
- Modify: `patterns/demo-ant-buster.php` — `updateCannons` adds cone-DoT update path, render adds cone overlay, append Plasma Torch.

- [ ] **Step 1: Cone-DoT helper**

  Add above `updateCannons`:

  ```js
  function applyConeDoT(cannon, def, dt) {
    var range = def.coneDot.range;
    var halfAngle = (def.coneDot.angleDeg * Math.PI / 180) / 2;
    for (var i = 0; i < game.ants.length; i++) {
      var a = game.ants[i];
      if (a.dead) { continue; }
      if (a.intangible) { continue; }
      var dx = a.x - cannon.x;
      var dy = a.y - cannon.y;
      var dist = Math.sqrt(dx * dx + dy * dy);
      if (dist > range) { continue; }
      var angleToAnt = Math.atan2(dy, dx);
      var delta = angleToAnt - cannon.heading;
      while (delta > Math.PI) { delta = delta - Math.PI * 2; }
      while (delta < -Math.PI) { delta = delta + Math.PI * 2; }
      if (Math.abs(delta) > halfAngle) { continue; }
      applyDamage(a, def.coneDot.tickDamage * dt, !!def.armorPierce, cannon);
      if (def.coneDot.statusOnTick) {
        var s = def.coneDot.statusOnTick;
        applyStatus(a, s.kind, s.magnitude, s.duration, 'cone');
      }
      if (a.hp <= 0) { killAnt(a, cannon); }
    }
  }
  ```

- [ ] **Step 2: Hook into `updateCannons`**

  Inside the cannon-update loop, after fire-cooldown logic:

  ```js
  if (def.coneDot) {
    var t = findTarget(cannon);
    if (t) {
      cannon.heading = Math.atan2(t.y - cannon.y, t.x - cannon.x);
      applyConeDoT(cannon, def, dt);
      cannon.firingCone = true;
    } else {
      cannon.firingCone = false;
    }
  }
  ```

- [ ] **Step 3: Render the cone overlay**

  In the effects render layer, add:

  ```js
  for (var ci = 0; ci < game.cannons.length; ci++) {
    var cc = game.cannons[ci];
    var cdef = CANNON_KINDS[cc.kind];
    if (cdef.coneDot && cc.firingCone) {
      var halfA = (cdef.coneDot.angleDeg * Math.PI / 180) / 2;
      ctx.fillStyle = 'rgba(255, 120, 30, 0.18)';
      ctx.beginPath();
      ctx.moveTo(cc.x, cc.y);
      ctx.arc(cc.x, cc.y, cdef.coneDot.range, cc.heading - halfA, cc.heading + halfA);
      ctx.closePath();
      ctx.fill();
    }
  }
  ```

- [ ] **Step 4: Add Plasma Torch to CANNON_KINDS**

  ```js
  plasma_torch: {
    name: 'Plasma Torch', cost: 90, damage: 0, fireRate: 0, range: 70,
    projectileSpeed: 0, color: '#ff7a30',
    family: 'quick', tier: 3,
    coneDot: {
      range: 70,
      angleDeg: 60,
      tickDamage: 10,    // 1/tick at 10/s = 10 dps
      statusOnTick: { kind: 'burn', magnitude: 1, duration: 2 }
    },
    upgradesTo: []
  },
  ```

- [ ] **Step 5: Wire into Auto-Cannon.upgradesTo**

  Find Auto-Cannon. Update from `['vulcan']` to:

  ```js
  upgradesTo: ['vulcan', 'plasma_torch']
  ```

- [ ] **Step 6: Sprite + verify + commit**

  Add `makePlasmaTorchTurret` (orange flame-shaped). Build Repeater → Auto-Cannon → Plasma Torch. Confirm cone visual + ants taking continuous damage + burn status applied (`window.__ab.ants[0].effects.burn` populated for ants in cone).

  ```bash
  git commit -am "feat(ant-buster): cone-DoT primitive + Plasma Torch (2.12)"
  ```

---

## Task 16: Sharpshooter (2.21) — bonus vs full-HP, ignores slow penalty

**Files:**
- Modify: `patterns/demo-ant-buster.php` — append Sharpshooter, small slow-resist tweak.

- [ ] **Step 1: Add Sharpshooter to CANNON_KINDS**

  ```js
  sharpshooter: {
    name: 'Sharpshooter', cost: 100, damage: 25, fireRate: 0.5, range: 350,
    projectileSpeed: 360, color: '#3a8a8a',
    family: 'quick', tier: 3,
    damageModifier: function(target) {
      var mult = 1;
      if (target.hp >= target.maxHp - 0.001) { mult = 1.5; }
      // ignores 50% slow penalty: this is a slow-applied damage debuff
      // that other towers respect; Sharpshooter shoots through it.
      // Implemented as: if target has a slow effect, ignore its damage-reduction modifier.
      // Plan 2's slow doesn't have a damage-reduction modifier (it's pure speed),
      // so this clause is a no-op today. Left as documentation.
      return mult;
    },
    upgradesTo: []
  },
  ```

  Note: spec says "ignores 50% slow penalty" — Plan 2's slow only affects movement, not damage. The "slow penalty" on damage is a Plan 4 wrinkle if it ever materializes. For Plan 3, this field documents intent without changing behavior. Leave the comment in place.

- [ ] **Step 2: Wire into Marksman.upgradesTo**

  Marksman already has `['sharpshooter', 'lance']` (Task 13).

- [ ] **Step 3: Sprite + verify + commit**

  Add `makeSharpshooterTurret`. Build Repeater → Marksman → Sharpshooter. Confirm 25 dmg + 1.5× vs full-HP = 37.5 first shot.

  ```bash
  git commit -am "feat(ant-buster): Sharpshooter (2.21) — bonus vs full-HP"
  ```

---

## Task 17: Beam-direct primitive + Lance (2.22)

**Files:**
- Modify: `patterns/demo-ant-buster.php` — extend Plan 2's Glacier beam logic to support direct damage variant, append Lance.

Plan 2 has Glacier as a beam that emits status. Lance is a beam that deals direct damage. The framework supports both via the same path.

- [ ] **Step 1: Find existing Glacier beam logic**

  ```bash
  grep -n 'glacier\|beam' patterns/demo-ant-buster.php | head -20
  ```

  Locate the Glacier-specific beam tick (likely inside `updateCannons`). Generalize to read `def.beam`:

  ```js
  if (def.beam) {
    var t = findTarget(cannon);
    if (t) {
      cannon.heading = Math.atan2(t.y - cannon.y, t.x - cannon.x);
      cannon.beamTarget = t;
      if (def.beam.tickDamage > 0) {
        applyDamage(t, def.beam.tickDamage * dt, !!def.armorPierce, cannon);
        if (t.hp <= 0) { killAnt(t, cannon); }
      }
      if (def.beam.statusOnTick) {
        var s = def.beam.statusOnTick;
        applyStatus(t, s.kind, s.magnitude, s.duration, 'beam');
      }
    } else {
      cannon.beamTarget = null;
    }
  }
  ```

  Migrate Glacier's existing config into the new schema:

  ```js
  glacier: {
    // ... existing fields ...
    beam: {
      tickDamage: 1,    // 1/tick continuous (existing)
      statusOnTick: { kind: 'slow', magnitude: 0.5, duration: 1 }
    }
  }
  ```

- [ ] **Step 2: Add Lance to CANNON_KINDS**

  ```js
  lance: {
    name: 'Lance', cost: 110, damage: 0, fireRate: 0, range: 250,
    projectileSpeed: 0, color: '#3acccc',
    family: 'quick', tier: 3,
    beam: {
      tickDamage: 4,    // 4/tick = 4 dps if dt=1s; with fixed dt this scales correctly
      statusOnTick: null
    },
    upgradesTo: []
  },
  ```

  Note: spec says "4/tick" — the implementation uses `tickDamage * dt` so at 60fps fixed-step (dt=1/60), it deals `4/60 ≈ 0.067` per tick. To get the spec's "4/tick" feel as 4 dps would mean `tickDamage = 4` with `* dt` integration, giving 4 damage per second. Spec's "4/tick" is meant as 4 dmg per second of continuous beam, not 4 per fixed tick. Keep `tickDamage: 4`.

- [ ] **Step 3: Wire into Marksman.upgradesTo** (already has `['sharpshooter', 'lance']`).

- [ ] **Step 4: Sprite + render the beam**

  Reuse Glacier's beam-render. Lance gets a tighter cyan beam. Add `makeLanceTurret`.

- [ ] **Step 5: Verify + commit**

  Build Repeater → Marksman → Lance. Confirm continuous beam, 4 dps direct damage, no status. Confirm Glacier still applies slow + 1 dps as before.

  ```bash
  git commit -am "feat(ant-buster): beam-direct primitive + Lance (2.22)"
  ```

---

## Task 18: Phantom (2.31) — bonus vs Queen

**Files:**
- Modify: `patterns/demo-ant-buster.php` — append Phantom.

Pure data + sprite. Uses `damageModifier` hook.

- [ ] **Step 1: Add Phantom to CANNON_KINDS**

  ```js
  phantom: {
    name: 'Phantom', cost: 110, damage: 40, fireRate: 0.3, range: 450,
    projectileSpeed: 380, color: '#9a6acc',
    family: 'quick', tier: 3,
    damageModifier: function(target) {
      if (target.kind === 'queen') { return 2; }
      return 1;
    },
    upgradesTo: []
  },
  ```

- [ ] **Step 2: Wire into Spotter.upgradesTo** (already has `['phantom', 'tesla']`).

- [ ] **Step 3: Sprite + verify + commit**

  Add `makePhantomTurret`. Build Repeater → Spotter → Phantom. Spawn a Queen (`T`). Confirm Phantom deals 80 vs Queen, 40 vs others.

  ```bash
  git commit -am "feat(ant-buster): Phantom (2.31) — +100% vs Queen"
  ```

---

## Task 19: Tesla arc-jump primitive + Tesla (2.32) + polyline render

**Files:**
- Modify: `patterns/demo-ant-buster.php` — `fireProjectile` add Tesla path (instant resolve), new `game.lightningBolts[]` array for polyline render, render layer, append Tesla.

- [ ] **Step 1: Add `lightningBolts` to game state**

  In the `game` initial state object:

  ```js
  lightningBolts: [],   // each: { points: [{x,y}], expiresAt: number }
  ```

- [ ] **Step 2: Tesla branch in `fireProjectile`**

  At the top of `fireProjectile`, before the volley loop:

  ```js
  if (def.teslaArc) {
    var primary = target;
    var hits = [primary];
    var current = primary;
    var jumps = def.teslaArc.jumps;
    var jumpRange = def.teslaArc.range;
    var falloff = def.teslaArc.falloff;
    var dmg = def.damage;

    applyDamage(primary, dmg, !!def.armorPierce, cannon);
    if (primary.hp <= 0) { killAnt(primary, cannon); }

    for (var j = 0; j < jumps; j++) {
      var nearest = null;
      var bestDist = Infinity;
      for (var ai = 0; ai < game.ants.length; ai++) {
        var aa = game.ants[ai];
        if (aa.dead) { continue; }
        if (aa.intangible) { continue; }
        if (hits.indexOf(aa) !== -1) { continue; }
        var jdx = aa.x - current.x;
        var jdy = aa.y - current.y;
        var jd2 = jdx * jdx + jdy * jdy;
        if (jd2 > jumpRange * jumpRange) { continue; }
        if (jd2 < bestDist) { bestDist = jd2; nearest = aa; }
      }
      if (!nearest) { break; }
      dmg = dmg * (1 - falloff);
      applyDamage(nearest, dmg, !!def.armorPierce, cannon);
      if (nearest.hp <= 0) { killAnt(nearest, cannon); }
      hits.push(nearest);
      current = nearest;
    }

    var bolt = { points: [{ x: cannon.x, y: cannon.y }], expiresAt: game.time + 0.15 };
    for (var hi = 0; hi < hits.length; hi++) {
      bolt.points.push({ x: hits[hi].x, y: hits[hi].y });
    }
    game.lightningBolts.push(bolt);
    return;    // skip the projectile-spawn loop below
  }
  ```

- [ ] **Step 3: Decay lightning bolts each tick**

  In `update(dt)`:

  ```js
  var keptBolts = [];
  for (var bi = 0; bi < game.lightningBolts.length; bi++) {
    if (game.lightningBolts[bi].expiresAt > game.time) {
      keptBolts.push(game.lightningBolts[bi]);
    }
  }
  game.lightningBolts = keptBolts;
  ```

- [ ] **Step 4: Render lightning polyline**

  Between projectiles and ants in the render layer order (per spec §3.6 — under ants, over projectiles):

  ```js
  for (var bi = 0; bi < game.lightningBolts.length; bi++) {
    var b = game.lightningBolts[bi];
    var lifeRemaining = b.expiresAt - game.time;
    var alpha = lifeRemaining / 0.15;
    if (alpha < 0) { alpha = 0; }
    if (alpha > 1) { alpha = 1; }
    ctx.strokeStyle = 'rgba(180, 220, 255, ' + alpha + ')';
    ctx.lineWidth = 2;
    ctx.beginPath();
    ctx.moveTo(b.points[0].x, b.points[0].y);
    for (var pi = 1; pi < b.points.length; pi++) {
      // small zig-zag for visual juice
      var jx = b.points[pi].x + (Math.random() * 6 - 3);
      var jy = b.points[pi].y + (Math.random() * 6 - 3);
      ctx.lineTo(jx, jy);
    }
    ctx.stroke();
    ctx.lineWidth = 1;
  }
  ```

- [ ] **Step 5: Add Tesla to CANNON_KINDS**

  ```js
  tesla: {
    name: 'Tesla', cost: 100, damage: 10, fireRate: 1, range: 200,
    projectileSpeed: 0, color: '#b0d8ff',
    family: 'quick', tier: 3,
    teslaArc: { jumps: 3, range: 60, falloff: 0.5 },
    upgradesTo: []
  },
  ```

- [ ] **Step 6: Wire into Spotter.upgradesTo** (already has `['phantom', 'tesla']`).

- [ ] **Step 7: Sprite + verify + commit**

  Add `makeTeslaTurret` (small coil sprite, blue/cyan). Build Repeater → Spotter → Tesla. Spawn a worker swarm. Confirm: instant damage (10 → 5 → 2.5 across 3 jumps), polyline visual flashes for ~0.15s.

  ```bash
  git commit -am "feat(ant-buster): Tesla arc-jump primitive + polyline render + Tesla (2.32)"
  ```

---

## Task 20: Triple Threat (3.1) — 3-barrel volley with spread

**Files:**
- Modify: `patterns/demo-ant-buster.php` — Plan 2's Twin Gun multi-barrel logic in `fireProjectile` already handles `def.barrels`. Triple Threat just sets `barrels: 3`.

- [ ] **Step 1: Add Triple Threat to CANNON_KINDS**

  ```js
  triple_threat: {
    name: 'Triple Threat', cost: 70, damage: 2, fireRate: 1, range: 110,
    projectileSpeed: 240, color: '#aa3aaa',
    family: 'double', tier: 2,
    barrels: 3,
    spread: 0.10,    // ~5.7° — bigger than Twin Gun's 0.06
    upgradesTo: ['buckshot', 'minigun']    // Tasks 21, 22
  },
  ```

- [ ] **Step 2: Verify `fireProjectile` reads `def.spread`**

  Plan 2's `fireProjectile` hard-codes `var spread = 0.06;` at line ~1876. Update to:

  ```js
  var spread = def.spread ? def.spread : 0.06;
  ```

- [ ] **Step 3: Wire into Twin Gun.upgradesTo**

  Find Twin Gun. Update from `['frost']` to:

  ```js
  upgradesTo: ['triple_threat', 'frost', 'toxin']    // toxin added Task 23
  ```

  Or just `['triple_threat', 'frost']` here, append `'toxin'` in Task 23.

- [ ] **Step 4: Sprite + verify + commit**

  Add `makeTripleThreatTurret` (3 barrels side-by-side). Build Twin Gun → Triple Threat. Confirm 3 shots per volley with visible spread.

  ```bash
  git commit -am "feat(ant-buster): Triple Threat (3.1) — 3-barrel volley"
  ```

---

## Task 21: Multi-pellet cone primitive + Buckshot (3.11)

**Files:**
- Modify: `patterns/demo-ant-buster.php` — `fireProjectile` adds pellet variant (cone-spread N projectiles), append Buckshot.

Buckshot fires 5 pellets in a 50° cone — different from Triple Threat's 3-barrel volley because the spread is much wider and pellets are independent projectiles.

- [ ] **Step 1: Pellet branch in `fireProjectile`**

  At the top of `fireProjectile` (before the existing volley loop):

  ```js
  if (def.pellets) {
    var pellets = def.pellets;
    var coneRad = (def.coneDeg * Math.PI / 180);
    for (var pi = 0; pi < pellets; pi++) {
      var t = (pellets === 1) ? 0 : (pi / (pellets - 1) - 0.5);    // -0.5..0.5
      var pAngle = heading + t * coneRad;
      game.projectiles.push({
        x: cannon.x + Math.cos(pAngle) * 12,
        y: cannon.y + Math.sin(pAngle) * 12,
        vx: Math.cos(pAngle) * def.projectileSpeed,
        vy: Math.sin(pAngle) * def.projectileSpeed,
        damage: def.damage,
        linePierce: def.linePierce ? def.linePierce : 0,
        armorPierce: !!def.armorPierce,
        pierceLeft: def.linePierce ? def.linePierce : 0,
        statusOnHit: statusOnHit,
        sourceCannon: cannon,
        _sawAnts: [],
        life: 1.0
      });
    }
    return;
  }
  ```

  This is a return-early branch — pellet cannons skip the regular barrels loop.

- [ ] **Step 2: Add Buckshot to CANNON_KINDS**

  ```js
  buckshot: {
    name: 'Buckshot', cost: 90, damage: 2, fireRate: 0.8, range: 90,
    projectileSpeed: 220, color: '#cc8a3a',
    family: 'double', tier: 3,
    pellets: 5,
    coneDeg: 50,
    upgradesTo: []
  },
  ```

- [ ] **Step 3: Wire into Triple Threat.upgradesTo** (already has `['buckshot', 'minigun']`).

- [ ] **Step 4: Sprite + verify + commit**

  Add `makeBuckshotTurret` (wide flared barrel). Build Twin Gun → Triple Threat → Buckshot. Confirm 5 pellets per shot in a 50° fan, 90 range.

  ```bash
  git commit -am "feat(ant-buster): multi-pellet cone primitive + Buckshot (3.11)"
  ```

---

## Task 22: Hose primitive + Minigun (3.12)

**Files:**
- Modify: `patterns/demo-ant-buster.php` — `fireProjectile` adds aim-jitter for hose cannons, append Minigun.

- [ ] **Step 1: Aim-jitter in `fireProjectile`**

  In the regular barrels loop (the one that runs for non-pellet, non-tesla cannons), add jitter when `def.aimJitter` is set:

  ```js
  if (def.aimJitter) {
    h = h + (Math.random() * 2 - 1) * def.aimJitter;
  }
  ```

  Place this after the per-barrel `off` calculation, before the projectile push.

- [ ] **Step 2: Add Minigun to CANNON_KINDS**

  ```js
  minigun: {
    name: 'Minigun', cost: 110, damage: 1, fireRate: 14, range: 120,
    projectileSpeed: 320, color: '#3a3a3a',
    family: 'double', tier: 3,
    aimJitter: 0.05,
    upgradesTo: []
  },
  ```

- [ ] **Step 3: Wire into Triple Threat.upgradesTo** (already done in Task 20).

- [ ] **Step 4: Sprite + verify + commit**

  Add `makeMinigunTurret` (single barrel, sleek). Build Twin Gun → Triple Threat → Minigun. Confirm 14/s fire rate, slight visible spray pattern.

  ```bash
  git commit -am "feat(ant-buster): hose primitive + Minigun (3.12)"
  ```

---

## Task 23: Toxin (3.3) — applies poison status

**Files:**
- Modify: `patterns/demo-ant-buster.php` — append Toxin, update Twin Gun.upgradesTo.

Pure data + sprite. Uses Plan 2's existing poison status framework.

- [ ] **Step 1: Add Toxin to CANNON_KINDS**

  ```js
  toxin: {
    name: 'Toxin', cost: 75, damage: 1, fireRate: 1, range: 100,
    projectileSpeed: 200, color: '#3aaa3a',
    family: 'double', tier: 2,
    statusOnHit: { kind: 'poison', magnitude: 1, duration: 5 },    // 1 dps × 5s = 5 dmg
    upgradesTo: ['plague', 'sonic_pulse']    // Tasks 25, 26
  },
  ```

- [ ] **Step 2: Wire into Twin Gun.upgradesTo**

  Update from `['triple_threat', 'frost']` to:

  ```js
  upgradesTo: ['triple_threat', 'frost', 'toxin']
  ```

- [ ] **Step 3: Sprite + verify + commit**

  Add `makeToxinTurret` (green, dripping). Build Twin Gun → Toxin. Hit a Tank. Confirm poison ticks via `window.__ab.ants.find(a=>a.kind==='tank').effects.poison`.

  ```bash
  git commit -am "feat(ant-buster): Toxin (3.3) — applies poison status"
  ```

---

## Task 24: Freeze damageAmp hook + Cryo (3.22)

**Files:**
- Modify: `patterns/demo-ant-buster.php` — `STATUS_KINDS.freeze` gains `damageAmp` field, `applyDamage` reads `target.effects.freeze?.damageAmp`, append Cryo.

- [ ] **Step 1: Update applyDamage to read freeze damageAmp**

  After Task 5's body, add another modifier path:

  ```js
  function applyDamage(ant, amount, armorPierce, sourceCannon) {
    var k = ANT_KINDS[ant.kind];
    var dmg = amount;
    if (sourceCannon) {
      var sk = CANNON_KINDS[sourceCannon.kind];
      if (sk.damageModifier) {
        dmg = dmg * sk.damageModifier(ant, sourceCannon);
      }
    }
    if (ant.effects && ant.effects.freeze) {
      var amp = ant.effects.freeze.damageAmp;
      if (amp) { dmg = dmg * amp; }
    }
    if (!armorPierce) {
      var armor = k.armor;
      if (!armor) { armor = 0; }
      dmg = dmg * (1 - armor);
    }
    ant.hp = ant.hp - dmg;
  }
  ```

- [ ] **Step 2: Tag `damageAmp` on the freeze effect after `applyStatus` returns**

  Don't extend `applyStatus`'s signature. Instead, in the projectile-collision pathway in `updateProjectiles` where `applyStatus` is called, immediately after the call, check the cannon's `statusOnHit` for a `damageAmp` field and stamp it onto the just-applied effect:

  ```js
  if (p.statusOnHit) {
    var slist = p.statusOnHit;
    if (!Array.isArray(slist)) { slist = [slist]; }
    for (var si = 0; si < slist.length; si++) {
      var s = slist[si];
      applyStatus(a, s.kind, s.magnitude, s.duration, 'projectile');
      if (s.damageAmp && a.effects[s.kind]) {
        a.effects[s.kind].damageAmp = s.damageAmp;
      }
    }
  }
  ```

  This pattern keeps `applyStatus` clean — extra fields like `damageAmp` are stamped onto the effect from the *cannon's* statusOnHit config. Cryo's status config (Step 3 below) carries `damageAmp: 1.5`. Future emitters can carry different amps without code changes.

- [ ] **Step 3: Add Cryo to CANNON_KINDS**

  ```js
  cryo: {
    name: 'Cryo', cost: 110, damage: 3, fireRate: 1, range: 110,
    projectileSpeed: 220, color: '#80c0ff',
    family: 'double', tier: 3,
    statusOnHit: { kind: 'freeze', magnitude: 1, duration: 0.8, damageAmp: 1.5 },
    upgradesTo: []
  },
  ```

  The `damageAmp: 1.5` carries through Step 2's tag-after-applyStatus pattern: when Cryo hits, `a.effects.freeze.damageAmp` becomes 1.5. `applyDamage` (Step 1) reads that field and amplifies subsequent damage from any cannon while the freeze is active.

- [ ] **Step 4: Wire into Frost.upgradesTo**

  Frost currently has `['glacier']`. Update to:

  ```js
  upgradesTo: ['glacier', 'cryo']
  ```

- [ ] **Step 5: Sprite + verify + commit**

  Add `makeCryoTurret`. Build Twin Gun → Frost → Cryo. Hit a worker. Confirm: ant fully stops for 0.8s; during freeze, a follow-up hit from any cannon deals +50%.

  ```bash
  git commit -am "feat(ant-buster): freeze damageAmp + Cryo (3.22)"
  ```

---

## Task 25: Poison-spread-on-death hook + Plague (3.31)

**Files:**
- Modify: `patterns/demo-ant-buster.php` — extend `STATUS_KINDS.poison` schema, hook into `killAnt`, append Plague.

- [ ] **Step 1: Add `onTargetDeath` field to STATUS_KINDS.poison**

  In `STATUS_KINDS` (line ~1304), the existing poison entry doesn't have `onTargetDeath`. Add the schema:

  ```js
  poison: {
    name: 'Poison',
    onApply: null,
    perTick: function(ant, eff, dt) { ant.hp = ant.hp - eff.magnitude * dt; },
    speedMult: null,
    onTargetDeath: function(deadAnt) {
      var radius = 30;
      for (var i = 0; i < game.ants.length; i++) {
        var a = game.ants[i];
        if (a.dead) { continue; }
        if (a === deadAnt) { continue; }
        var dx = a.x - deadAnt.x;
        var dy = a.y - deadAnt.y;
        if (dx * dx + dy * dy > radius * radius) { continue; }
        // Re-apply the same poison the dead ant had — magnitude and duration carry over
        var orig = deadAnt.effects.poison;
        if (orig) {
          applyStatus(a, 'poison', orig.magnitude, orig.expiresAt - game.time, 'plague-spread');
        }
      }
    }
  },
  ```

- [ ] **Step 2: Tag `spreads` on the poison effect via the same after-applyStatus pattern**

  Don't extend `applyStatus`'s signature. Same pattern as Cryo (Task 24): the cannon's `statusOnHit` carries a `spreads: true` field, and the projectile-collision pathway stamps it onto the effect after `applyStatus` returns. Task 24 Step 2 already established the loop:

  ```js
  if (p.statusOnHit) {
    var slist = p.statusOnHit;
    if (!Array.isArray(slist)) { slist = [slist]; }
    for (var si = 0; si < slist.length; si++) {
      var s = slist[si];
      applyStatus(a, s.kind, s.magnitude, s.duration, 'projectile');
      if (s.damageAmp && a.effects[s.kind]) {
        a.effects[s.kind].damageAmp = s.damageAmp;
      }
      if (s.spreads && a.effects[s.kind]) {
        a.effects[s.kind].spreads = true;
      }
    }
  }
  ```

  (This adds the `s.spreads` line to the existing block.)

- [ ] **Step 2.5: Trigger spread from `killAnt`**

  In `killAnt` (line ~1915), after the existing `onDeath` hook fires:

  ```js
  if (ant.effects && ant.effects.poison && ant.effects.poison.spreads) {
    STATUS_KINDS.poison.onTargetDeath(ant);
  }
  ```

  Toxin's `statusOnHit` does NOT carry `spreads` (omitted = default false). Plague's `statusOnHit` carries `spreads: true`. So Toxin-poisoned ants die without spreading; Plague-poisoned ants spread on death.

- [ ] **Step 3: Add Plague to CANNON_KINDS**

  ```js
  plague: {
    name: 'Plague', cost: 90, damage: 2, fireRate: 1, range: 110,
    projectileSpeed: 200, color: '#5acc5a',
    family: 'double', tier: 3,
    statusOnHit: { kind: 'poison', magnitude: 2.5, duration: 6, spreads: true },    // 15 dmg over 6s
    upgradesTo: []
  },
  ```

- [ ] **Step 4: Wire into Toxin.upgradesTo** (already done in Task 23).

- [ ] **Step 5: Sprite + verify + commit**

  Add `makePlagueTurret`. Build Twin Gun → Toxin → Plague. Hit one ant in a clustered swarm. Kill it. Confirm: nearby ants within 30px gain poison effect (`a.effects.poison.spreads === true`).

  ```bash
  git commit -am "feat(ant-buster): poison spread-on-death hook + Plague (3.31)"
  ```

---

## Task 26: Sonic Pulse (3.32) — knockback + stun

**Files:**
- Modify: `patterns/demo-ant-buster.php` — append Sonic Pulse.

Pure data + sprite. Uses Plan 2's knockback and stun status framework.

- [ ] **Step 1: Add Sonic Pulse to CANNON_KINDS**

  Plan 2's `applyStatus` may only accept one statusOnHit. To apply both knockback AND stun, either (a) extend statusOnHit to accept an array, or (b) have Sonic Pulse only apply stun via the existing path and trigger knockback inline at the projectile-collision site by reading a separate `statusOnHitExtra` field, or (c) add a new "compound" status.

  Simplest: extend the projectile-collision body to apply multiple statuses if `statusOnHit` is an array:

  ```js
  if (p.statusOnHit) {
    var slist = p.statusOnHit;
    if (!Array.isArray(slist)) { slist = [slist]; }
    for (var si = 0; si < slist.length; si++) {
      var s = slist[si];
      applyStatus(a, s.kind, s.magnitude, s.duration, 'projectile');
    }
  }
  ```

  Then Sonic Pulse:

  ```js
  sonic_pulse: {
    name: 'Sonic Pulse', cost: 90, damage: 5, fireRate: 0.8, range: 100,
    projectileSpeed: 240, color: '#cccc3a',
    family: 'double', tier: 3,
    statusOnHit: [
      { kind: 'knockback', magnitude: 30, duration: 0 },
      { kind: 'stun', magnitude: 1, duration: 0.4 }
    ],
    upgradesTo: []
  },
  ```

- [ ] **Step 2: Wire into Toxin.upgradesTo** (already done in Task 23 — has `['plague', 'sonic_pulse']`).

- [ ] **Step 3: Sprite + verify + commit**

  Add `makeSonicPulseTurret`. Build Twin Gun → Toxin → Sonic Pulse. Hit a worker. Confirm 30px backward displacement + 0.4s stun (no movement). Confirm beam cannons (Lance) don't hit a stunned ant any differently — stun zeroes movement, that's it.

  ```bash
  git commit -am "feat(ant-buster): Sonic Pulse (3.32) — knockback + stun"
  ```

---

## Task 27: Wave config `prepTime` field + boss-prep differentiation

**Files:**
- Modify: `patterns/demo-ant-buster.php` — `WAVES` schema, `updateWaveState` reads `prepTime`.

- [ ] **Step 1: Add `prepTime` to WAVES rows**

  Current WAVES entries have `prep: 30` (Plan 2 hard-coded for wave 1). Generalize: add a default at the top of the file constants:

  ```js
  var DEFAULT_PREP_REGULAR = 20;
  var DEFAULT_PREP_BOSS = 30;
  ```

  Update `WAVES[0]` (wave 1):

  ```js
  {
    number: 1,
    prepTime: 30,         // explicit — wave 1 keeps spec's 30s
    bonus: 21,
    spawns: [
      { type: 'worker', count: 8, interval: 1.0, delay: 0 }
    ]
  }
  ```

  Update existing WAVES[1..4] to use `prepTime` instead of `prep`. For wave 5 (boss): `prepTime: 30`.

- [ ] **Step 2: `updateWaveState` reads `prepTime` (or default)**

  Find the wave-state machine's prep-timer init. Wherever it reads `wave.prep`, replace with:

  ```js
  var prepDuration = wave.prepTime;
  if (!prepDuration) {
    prepDuration = wave.isBoss ? DEFAULT_PREP_BOSS : DEFAULT_PREP_REGULAR;
  }
  ```

- [ ] **Step 3: Update bonus formula too — same step (Task 28 covers waves 6-25 entries)**

  In `updateWaveState`, when transitioning out of a wave, replace the bonus payout. Plan 2 might use `wave.bonus` literally; Plan 3 normalizes to `20 + wave.number`. Replace:

  ```js
  game.gold = game.gold + wave.bonus;
  ```

  with:

  ```js
  var paid = 20 + wave.number;
  game.gold = game.gold + paid;
  ```

  Alternatively, recompute `wave.bonus = 20 + wave.number` at WAVES-array construction time and keep the existing read. Either works. Pick one.

- [ ] **Step 4: Verify wave 5 still pays 25g (not 75g)**

  Build to wave 5, complete it, watch console for the bonus payout. If Plan 2's QA showed 75g for wave 5, the formula change brings it down to 25. This is the *intentional* rebalance per spec §6.3 — flag in commit message.

- [ ] **Step 5: Lint + curl-grep + commit**

  ```bash
  git commit -am "refactor(ant-buster): prepTime field + spec wave-bonus formula (20+wave)"
  ```

---

## Task 28: Append waves 6–25 + boss banners

**Files:**
- Modify: `patterns/demo-ant-buster.php` — append 20 entries to WAVES, banner copy in `showBanner` callers.

- [ ] **Step 1: Append waves 6-25 to WAVES**

  After the existing wave-5 entry, append:

  ```js
  ,{ number: 6, prepTime: 20, isBoss: false, bannerText: 'WAVE 6/25', spawns: [
    { type: 'worker', count: 12, interval: 0.7, delay: 0 },
    { type: 'scout',  count: 6,  interval: 0.5, delay: 1 }
  ]},
  { number: 7, prepTime: 20, isBoss: false, bannerText: 'WAVE 7/25', spawns: [
    { type: 'scout',  count: 8, interval: 0.4, delay: 0 },
    { type: 'worker', count: 6, interval: 0.7, delay: 1.5 },
    { type: 'scout',  count: 4, interval: 0.4, delay: 1 }
  ]},
  { number: 8, prepTime: 20, isBoss: false, bannerText: 'WAVE 8/25', spawns: [
    { type: 'worker',  count: 12, interval: 0.7, delay: 0 },
    { type: 'soldier', count: 2,  interval: 1.5, delay: 2 }
  ]},
  { number: 9, prepTime: 20, isBoss: false, bannerText: 'WAVE 9/25', spawns: [
    { type: 'soldier', count: 6, interval: 1.5, delay: 0 },
    { type: 'worker',  count: 8, interval: 0.7, delay: 1 }
  ]},
  { number: 10, prepTime: 30, isBoss: true, bannerText: 'WAVE 10: QUEEN', spawns: [
    { type: 'queen', count: 1, interval: 0, delay: 0, hp: 600 },
    { type: 'soldier', count: 6, interval: 1.5, delay: 1 },
    { type: 'worker',  count: 6, interval: 0.7, delay: 1 }
  ]},
  { number: 11, prepTime: 20, isBoss: false, bannerText: 'WAVE 11/25', spawns: [
    { type: 'worker',  count: 8, interval: 0.7, delay: 0 },
    { type: 'carrier', count: 4, interval: 1.0, delay: 1 },
    { type: 'worker',  count: 6, interval: 0.7, delay: 1 }
  ]},
  { number: 12, prepTime: 20, isBoss: false, bannerText: 'WAVE 12/25', spawns: [
    { type: 'soldier', count: 4, interval: 1.5, delay: 0 },
    { type: 'carrier', count: 6, interval: 0.8, delay: 1.5 },
    { type: 'scout',   count: 4, interval: 0.4, delay: 1 }
  ]},
  { number: 13, prepTime: 20, isBoss: false, bannerText: 'WAVE 13/25', spawns: [
    { type: 'splitter', count: 6, interval: 1.2, delay: 0 },
    { type: 'worker',   count: 8, interval: 0.7, delay: 1.5 }
  ]},
  { number: 14, prepTime: 20, isBoss: false, bannerText: 'WAVE 14/25', spawns: [
    { type: 'splitter', count: 4, interval: 1.2, delay: 0 },
    { type: 'soldier',  count: 4, interval: 1.5, delay: 1 },
    { type: 'carrier',  count: 6, interval: 0.8, delay: 1 },
    { type: 'scout',    count: 8, interval: 0.4, delay: 1 }
  ]},
  { number: 15, prepTime: 30, isBoss: true, bannerText: 'WAVE 15: QUEEN', spawns: [
    { type: 'queen',    count: 1, interval: 0, delay: 0, hp: 800 },
    { type: 'splitter', count: 8, interval: 1.0, delay: 1 },
    { type: 'carrier',  count: 6, interval: 0.8, delay: 1 }
  ]},
  { number: 16, prepTime: 20, isBoss: false, bannerText: 'WAVE 16/25', spawns: [
    { type: 'tank',   count: 2, interval: 2.5, delay: 0 },
    { type: 'worker', count: 8, interval: 0.7, delay: 2 }
  ]},
  { number: 17, prepTime: 20, isBoss: false, bannerText: 'WAVE 17/25', spawns: [
    { type: 'phase',   count: 6, interval: 1.0, delay: 0 },
    { type: 'worker',  count: 8, interval: 0.7, delay: 1 },
    { type: 'soldier', count: 4, interval: 1.5, delay: 1 }
  ]},
  { number: 18, prepTime: 20, isBoss: false, bannerText: 'WAVE 18/25', spawns: [
    { type: 'tank',     count: 3, interval: 2.5, delay: 0 },
    { type: 'phase',    count: 4, interval: 1.0, delay: 1 },
    { type: 'splitter', count: 6, interval: 1.2, delay: 1 }
  ]},
  { number: 19, prepTime: 20, isBoss: false, bannerText: 'WAVE 19/25', spawns: [
    { type: 'bomber',  count: 4, interval: 1.5, delay: 0 },
    { type: 'worker',  count: 8, interval: 0.7, delay: 1 },
    { type: 'carrier', count: 4, interval: 1.0, delay: 1 }
  ]},
  { number: 20, prepTime: 30, isBoss: true, bannerText: 'WAVE 20: QUEEN', spawns: [
    { type: 'queen',  count: 1, interval: 0, delay: 0, hp: 1200 },
    { type: 'bomber', count: 4, interval: 1.5, delay: 1 },
    { type: 'tank',   count: 6, interval: 2.0, delay: 1 }
  ]},
  { number: 21, prepTime: 20, isBoss: false, bannerText: 'WAVE 21/25', spawns: [
    { type: 'tank',    count: 6, interval: 2.0, delay: 0 },
    { type: 'phase',   count: 6, interval: 1.0, delay: 1 },
    { type: 'carrier', count: 8, interval: 0.8, delay: 1 }
  ]},
  { number: 22, prepTime: 20, isBoss: false, bannerText: 'WAVE 22/25', spawns: [
    { type: 'bomber',   count: 8, interval: 1.2, delay: 0 },
    { type: 'splitter', count: 8, interval: 1.0, delay: 1 },
    { type: 'soldier',  count: 4, interval: 1.5, delay: 1 }
  ]},
  { number: 23, prepTime: 20, isBoss: false, bannerText: 'WAVE 23/25', spawns: [
    { type: 'tank',     count: 4,  interval: 2.0, delay: 0 },
    { type: 'phase',    count: 6,  interval: 1.0, delay: 1 },
    { type: 'bomber',   count: 6,  interval: 1.2, delay: 1 },
    { type: 'splitter', count: 8,  interval: 1.0, delay: 1 },
    { type: 'carrier',  count: 8,  interval: 0.8, delay: 1 },
    { type: 'worker',   count: 12, interval: 0.5, delay: 1 }
  ]},
  { number: 24, prepTime: 20, isBoss: false, bannerText: 'WAVE 24/25', spawns: [
    { type: 'tank',    count: 8,  interval: 1.8, delay: 0 },
    { type: 'carrier', count: 12, interval: 0.7, delay: 1 },
    { type: 'bomber',  count: 8,  interval: 1.2, delay: 1 }
  ]}
  // wave 25 added in Task 32
  ```

- [ ] **Step 2: Boss banner uses `wave.bannerText`**

  Find the wave-start banner trigger. Plan 2's existing `showBanner(text, isBoss)` call may hard-code text. Update to:

  ```js
  showBanner(wave.bannerText, !!wave.isBoss);
  ```

- [ ] **Step 3: Spawner reads per-spawn `hp` override (for Queens)**

  In the spawner code (where it calls `spawnAnt(group.type)`), add:

  ```js
  var spawned = spawnAnt(group.type);
  if (group.hp) {
    spawned.hp = group.hp;
    spawned.maxHp = group.hp;
  }
  ```

  This lets wave 10/15/20 Queens have their per-wave HP override (600 / 800 / 1200) without needing 3 separate ANT_KINDS rows.

- [ ] **Step 4: Lint + curl-grep + Playwright**

  Set `?debug=1` and use `window.__abAdvanceWave()` (or skip via cheat) to test wave 10. Confirm Queen has 600 HP. Test wave 15 — Queen at 800. Test wave 20 — Queen at 1200. Test wave 11 — carriers spawn. Test wave 16 — tanks spawn.

  Helper: a debug surface for wave-skipping might be missing. If so, briefly expose `window.__abSkipToWave = function(n) { ... }` for the QA pass; remove or keep for Plan 4.

- [ ] **Step 5: Commit**

  ```bash
  git commit -am "feat(ant-buster): waves 6-24 with spec compositions + boss banners"
  ```

---

## Task 29: King ANT_KINDS row + persistent HP bar

**Files:**
- Modify: `patterns/demo-ant-buster.php` — append `king` to ANT_KINDS, add `makeKingSprite`.

- [ ] **Step 1: Add King to ANT_KINDS**

  ```js
  king: {
    name: 'King',
    hp: 3000,
    speed: 10,
    gold: 200,
    alignment: 0.4,
    jitterRate: 1.0,
    wanderFreqMin: 0.1, wanderFreqMax: 0.2,
    wanderAmpMin: 0.2, wanderAmpMax: 0.4,
    spriteKey: 'king',
    spriteW: 32, spriteH: 32,
    slowResist: 0.5,
    damageMult: 1,
    pierceMult: 1,
    canPickup: false,         // King doesn't carry slices
    armor: 0.5,               // 50% from non-armorPierce
    onDeath: null,            // wave-completion handles end-of-run
    barStyle: { width: 60, height: 4, color: '#d04020', borderColor: '#fff', persistent: true },
    onHpThresholds: null      // populated in Task 30
  }
  ```

- [ ] **Step 2: Sprite — `makeKingSprite`**

  ```js
  function makeKingSprite() {
    var s = document.createElement('canvas');
    s.width = 32; s.height = 32;
    var sc = s.getContext('2d');
    sc.fillStyle = '#262b44';
    sc.fillRect(8, 8, 16, 22);  // body
    sc.fillStyle = '#7a3a1a';
    sc.fillRect(6, 12, 4, 4); sc.fillRect(22, 12, 4, 4);  // legs
    sc.fillRect(6, 20, 4, 4); sc.fillRect(22, 20, 4, 4);
    sc.fillStyle = '#fee761';
    sc.fillRect(11, 4, 2, 4); sc.fillRect(15, 2, 2, 6); sc.fillRect(19, 4, 2, 4);  // crown spikes
    sc.fillStyle = '#3a2a0a';
    sc.fillRect(11, 8, 10, 2);  // crown band
    sc.fillStyle = '#e43b44';
    sc.fillRect(13, 16, 6, 6);  // gem on chest
    return s;
  }
  ```

  Wire into the ant-sprite lookup (the dispatcher that maps `ant.kind` → sprite).

- [ ] **Step 3: Lint + curl-grep + Playwright**

  Set `?debug=1`, add `'king'` to DEBUG_KINDS temporarily (this lands in Task 34 properly; for now add inline). Spawn King via `T`. Confirm: large regal sprite, 60×4 dark-red HP bar persistently visible from spawn, slow 10 px/s walk toward cake.

- [ ] **Step 4: Commit**

  ```bash
  git commit -am "feat(ant-buster): King ANT_KINDS row + persistent HP bar"
  ```

---

## Task 30: Threshold-summon hook (`onHpThresholds`)

**Files:**
- Modify: `patterns/demo-ant-buster.php` — King's `onHpThresholds`, `applyDamage` checks crossings.

- [ ] **Step 1: Define threshold helper**

  Above ANT_KINDS:

  ```js
  function spawnSoldiersAtKing(count) {
    return function(king) {
      for (var i = 0; i < count; i++) {
        var ang = Math.random() * Math.PI * 2;
        var dx = Math.cos(ang) * 10;
        var dy = Math.sin(ang) * 10;
        spawnAntAt('soldier', king.x + dx, king.y + dy, 'threshold');
      }
    };
  }
  ```

- [ ] **Step 2: Set `onHpThresholds` on King**

  Replace `onHpThresholds: null` from Task 29 with:

  ```js
  onHpThresholds: [
    { at: 0.75, action: spawnSoldiersAtKing(4) },
    { at: 0.50, action: spawnSoldiersAtKing(4) },
    { at: 0.25, action: spawnSoldiersAtKing(4) }
  ]
  ```

- [ ] **Step 3: `applyDamage` checks crossings**

  Inside `applyDamage`, after `ant.hp = ant.hp - dmg;` add:

  ```js
  var k2 = ANT_KINDS[ant.kind];
  if (k2.onHpThresholds) {
    var pct = ant.hp / ant.maxHp;
    for (var ti = 0; ti < k2.onHpThresholds.length; ti++) {
      var thr = k2.onHpThresholds[ti];
      var key = String(thr.at);
      if (!ant.firedThresholds[key] && pct < thr.at) {
        ant.firedThresholds[key] = true;
        thr.action(ant);
      }
    }
  }
  ```

  `ant.firedThresholds` was added in Task 2's `spawnAnt`.

- [ ] **Step 4: Verify**

  Set `?debug=1`. Spawn King. Bring HP to 75% via repeated direct damage:

  ```js
  window.__ab.ants.find(a => a.kind === 'king').hp = 2249;
  ```

  Then chip HP via a placed Howitzer's next shot — confirm 4 Soldiers spawn at King's position. Repeat at 50% (1499) and 25% (749).

- [ ] **Step 5: Commit**

  ```bash
  git commit -am "feat(ant-buster): King onHpThresholds — threshold-summon Soldiers at 75/50/25% HP"
  ```

---

## Task 31: Continuous worker drip from secondary spawn

**Files:**
- Modify: `patterns/demo-ant-buster.php` — King's `onSpawn`, `onDeath` hooks; add per-tick spawn-job framework.

- [ ] **Step 1: Add per-tick spawn jobs to game state**

  In `game` initial state:

  ```js
  spawnJobs: [],   // each: { interval, lastFiredAt, fn, owner }
  ```

  Reset in `resetGame`.

- [ ] **Step 2: Process spawn jobs in `update`**

  At the top of `update(dt)`:

  ```js
  for (var ji = 0; ji < game.spawnJobs.length; ji++) {
    var job = game.spawnJobs[ji];
    if (job.owner && job.owner.dead) { continue; }
    if (game.time - job.lastFiredAt >= job.interval) {
      job.lastFiredAt = game.time;
      job.fn();
    }
  }
  // prune jobs with dead owners
  var keptJobs = [];
  for (var jj = 0; jj < game.spawnJobs.length; jj++) {
    if (!game.spawnJobs[jj].owner || !game.spawnJobs[jj].owner.dead) {
      keptJobs.push(game.spawnJobs[jj]);
    }
  }
  game.spawnJobs = keptJobs;
  ```

- [ ] **Step 3: Add `onSpawn` to King**

  ```js
  king: {
    // ... existing fields ...
    onSpawn: function(king) {
      game.spawnJobs.push({
        interval: 2.5,
        lastFiredAt: game.time,
        owner: king,
        fn: function() {
          spawnAntAt('worker', 10, 320, 'drip', { x: 10, y: 320 });
        }
      });
    },
    onDeath: function(king) {
      // spawnJobs auto-prune via the dead-owner check; nothing else needed
    }
  }
  ```

- [ ] **Step 4: Wire `onSpawn` into `spawnAnt`**

  In `spawnAnt` (Task 2), after pushing the ant:

  ```js
  if (k.onSpawn) { k.onSpawn(ant); }
  ```

- [ ] **Step 5: Verify**

  Spawn a King. Watch for workers appearing at (10, 320) every 2.5s. Confirm those workers walk to the cake and, when grabbing a slice, flee back toward (10, 320) instead of the main anthill (this requires Task 2's spawnOrigin work to be correct).

- [ ] **Step 6: Commit**

  ```bash
  git commit -am "feat(ant-buster): King secondary spawn drip (workers from (10,320) at 2.5s)"
  ```

---

## Task 32: Wave 25 spawn config + FINAL WAVE banner

**Files:**
- Modify: `patterns/demo-ant-buster.php` — append wave 25 to WAVES, banner copy + visual.

- [ ] **Step 1: Append wave 25 to WAVES**

  After the trailing wave-24 entry from Task 28, add (mind the comma):

  ```js
  ,{ number: 25, prepTime: 30, isBoss: true, bannerText: 'FINAL WAVE: THE KING', spawns: [
    { type: 'king', count: 1, interval: 0, delay: 0 }
  ]}
  ```

- [ ] **Step 2: Final-wave banner visual treatment**

  Find `showBanner` (line ~144). Add a new third arg or detect 'KING' in the text:

  ```js
  function showBanner(text, isBoss, isFinal) {
    game.banner.active = true;
    game.banner.text = text;
    game.banner.startedAt = game.time;
    game.banner.duration = isFinal ? 4 : (isBoss ? 3 : 2.5);
    game.banner.isBoss = isBoss;
    game.banner.isFinal = !!isFinal;
  }
  ```

  Update banner-render to use a deeper-toned color when `isFinal`:

  ```js
  ctx.fillStyle = game.banner.isFinal ? '#7a1a1a' : (game.banner.isBoss ? '#a44' : '#3a4466');
  ```

  Update wave-start trigger to pass isFinal:

  ```js
  showBanner(wave.bannerText, !!wave.isBoss, wave.number === 25);
  ```

- [ ] **Step 3: Verify wave-25 completion path**

  Skip to wave 25 via debug. Confirm: King spawns. Drip workers spawn from (10,320) every 2.5s. Threshold soldiers fire at 75/50/25% HP. After King dies and all drip-workers + soldiers are cleared, `waveState` advances to `'won'` and the wave-completion bonus (45g) is paid.

- [ ] **Step 4: Lint + curl-grep + commit**

  ```bash
  git commit -am "feat(ant-buster): wave 25 — FINAL WAVE: THE KING boss fight"
  ```

---

## Task 33: Carrier flee-speed = 45 px/s

**Files:**
- Modify: `patterns/demo-ant-buster.php` — `ANT_KINDS.carrier.carryingSpeedMult` (or equivalent).

- [ ] **Step 1: Find current value**

  ```bash
  grep -n 'carryingSpeedMult\|fleeSpeed' patterns/demo-ant-buster.php
  ```

  Plan 2 likely has `carryingSpeedMult: 0.7` on the carrier kind, or a `fleeSpeed: 21` field.

- [ ] **Step 2: Update to 1.5 multiplier**

  Replace:

  ```js
  carryingSpeedMult: 0.7,
  ```

  with:

  ```js
  carryingSpeedMult: 1.5,
  ```

  Also check that worker-turned-carrier ants read the `carrier` row's `carryingSpeedMult` (i.e. the speed-while-carrying logic should branch on `ant.carrying`, not on `ant.kind === 'carrier'`). If Plan 2 reads from `ant.kind`, generalize:

  ```js
  var speed = ant.carrying ? (ANT_KINDS.carrier.fleeSpeed || k.speed * (k.carryingSpeedMult || 1)) : k.speed;
  ```

  Or simpler: store `carryingSpeedMult` on every ant kind (default 1) so both `worker` and `carrier` can have it. Most kinds default to 1, carrier overrides to 1.5.

- [ ] **Step 3: Verify**

  Wave 11 carriers — confirm visibly faster while fleeing. Worker-turned-carrier (worker grabs cake slice) — also visibly faster.

- [ ] **Step 4: Commit**

  ```bash
  git commit -am "feat(ant-buster): carrier flee-speed = 45 px/s (carryingSpeedMult 1.5)"
  ```

---

## Task 34: Debug cycle — add `mini_splitter` and `king`

**Files:**
- Modify: `patterns/demo-ant-buster.php` — `DEBUG_KINDS` (line 101).

- [ ] **Step 1: Update DEBUG_KINDS**

  Replace:

  ```js
  var DEBUG_KINDS = ['worker', 'carrier', 'scout', 'soldier', 'tank', 'splitter', 'phase', 'bomber', 'queen'];
  ```

  with:

  ```js
  var DEBUG_KINDS = ['worker', 'carrier', 'scout', 'soldier', 'tank',
                     'splitter', 'mini_splitter', 'phase', 'bomber',
                     'queen', 'king'];
  ```

- [ ] **Step 2: Verify**

  Cycle through `T` 11 times. Confirm each kind spawns once. Confirm King spawns at the anthill (Task 29's sprite renders correctly mid-game). Confirm threshold-summons fire when King takes damage. Confirm drip from King's `onSpawn` runs.

- [ ] **Step 3: Commit**

  ```bash
  git commit -am "feat(ant-buster): mini_splitter + king in ?debug=1 T cycle"
  ```

---

## Task 35: Theme `Version:` bump

**Files:**
- Modify: `wp-content/themes/dariobimbi/style.css` — Version header.

- [ ] **Step 1: Read current version**

  ```bash
  grep -i 'Version:' c:/wamp64/www/dariobimbi.com/wp-content/themes/dariobimbi/style.css
  ```

- [ ] **Step 2: Bump (semver-style, e.g. 0.X.0 → 0.(X+1).0)**

  Use the next semver bump for "feature release" — likely `0.X.0` → `0.(X+1).0`. The exact value depends on what Plan 2 ended on; bump by one minor version.

- [ ] **Step 3: Verify pattern cache invalidates**

  Open admin page. Pattern list should re-scan. Visit `/demos/ant-buster/` — page should still render (pattern is unchanged by the version bump itself; bump just busts the cache so any indirect pattern-list changes propagate).

- [ ] **Step 4: Commit**

  ```bash
  git add wp-content/themes/dariobimbi/style.css
  git commit -m "chore(ant-buster): bump theme Version for Plan 3 cache invalidation"
  ```

---

## Task 36: Final QA walk against §6 acceptance + write Plan 3 status doc

**Files:**
- Modify: none directly (verification + docs).
- Create: `docs/superpowers/plans/2026-05-07-ant-buster-plan-3-status.md`

- [ ] **Step 1: Run the full §6 acceptance walk**

  Open `?debug=1`. For each of the 27 acceptance criteria in `docs/superpowers/specs/2026-05-07-ant-buster-plan-3-design.md` §6, demonstrate via:
  - Building cannon chains (acceptance items 1, 3)
  - Hovering upgrade buttons (item 4 — Plasma Torch range shrink)
  - Spawning Carriers + Spotter (item 5)
  - Killing a Plague-poisoned ant in a swarm (item 6)
  - Cryo + secondary cannon hit (item 7)
  - Sonic Pulse on a worker (item 8)
  - Tesla on grouped enemies (item 9)
  - Skipping to specific waves and watching compositions (items 10, 11)
  - Wave 5 / 25 bonus payout values (item 12)
  - Boss banner copy (item 13)
  - King fight end-to-end (items 14-19)
  - `window.__ab.ants.filter(a => a.spawnSource === 'drip')` and `'threshold'` (item 20)
  - Carrier flee speed (item 21)
  - DEBUG_KINDS length (item 22)
  - `spawnAnt('worker', 'drip')` returns ant with correct origin (item 23)
  - `ant.spawnOrigin` populated on every ant (item 24)
  - curl-grep clean (item 25)
  - `style.css` Version line current (item 26)
  - No console errors during full play (item 27)

- [ ] **Step 2: Write Plan 3 status doc**

  Mirror Plan 2's status doc format at `docs/superpowers/plans/2026-05-07-ant-buster-plan-2-status.md`:

  - Header: status, date, plan reference, spec reference
  - "What shipped" with task-by-task commit table
  - Acceptance walk results table (every §6 item with PASS / FAIL + note)
  - Known divergences (anything that landed differently from spec)
  - Open issues for Plan 4 (this is where the deferred polish + shell + audio + god-tier list lives — copy-paste from spec §4.1)
  - Workflow notes (what worked / what to refine for Plan 4)

- [ ] **Step 3: Lint + curl-grep + Playwright + commit**

  Final QA pass. If anything fails, fix and re-test before committing the status doc.

  ```bash
  git add docs/superpowers/plans/2026-05-07-ant-buster-plan-3-status.md
  git commit -m "docs(ant-buster): Plan 3 status — shipped"
  ```

---

## Bucket G: Pink Rubber Family expansion (Tasks 37-40)

> Added mid-marathon 2026-05-07 per spec §9 addendum. Fourth cannon family with linear upgrade chain (Pink Pea -> Bouncy Bubble -> Rubber Wrecking Ball). Introduces MP-L (bouncing retarget) primitive. Tasks land after the existing 36-task body so the King fight (wave 25) and the carryover/QA work ship before this expansion. Each task self-contained.

---

## Task 37: MP-L bouncing primitive (bounce-retarget on impact)

**Files:**
- Modify: `patterns/demo-ant-buster.php` — `fireProjectile` (projectile literal), `updateProjectiles` (collision branch), the existing status-on-hit application code.

The bouncing primitive is structurally similar to MP-D (Returning, Task 7) but seeks the nearest non-hit ant within `bounceRadius` instead of returning to origin. Reuses the `_sawAnts` array introduced by Task 1's pierce split so the projectile never re-hits the same ant.

- [ ] **Step 1: Add bounce fields to the projectile literal**

  In `fireProjectile`, append to the projectile object:

  ```js
  bouncesRemaining: def.bounces ? def.bounces : 0,
  bounceRadius: def.bounceRadius ? def.bounceRadius : 0,
  damageDecay: def.bounceDamageDecay ? def.bounceDamageDecay : 1,
  initialHit: true,
  damage: def.damage,  // mutable per-projectile damage so decay applies cleanly
  ```

  `_sawAnts` already exists from Task 1. Reuse it.

  Also confirm `def.bounces` reads cleanly when undefined: the `def.bounces ? ... : 0` form avoids the inline-script `||` hazard and gives 0 for any cannon without the field (existing cannons unchanged).

- [ ] **Step 2: Add the bounce-retarget branch in `updateProjectiles`**

  Inside the projectile loop, **after** damage is applied to the hit ant `a` and **before** the projectile would despawn, insert:

  ```js
  // MP-L: Bouncing retarget. If the projectile has bounces left, find the
  // nearest live, non-intangible ant within bounceRadius that this projectile
  // has not already hit. Redirect velocity, decay damage, mark non-initial.
  if (p.bouncesRemaining > 0) {
    var bestAnt = null;
    var bestDist = p.bounceRadius;
    for (var bi = 0; bi < game.ants.length; bi++) {
      var ba = game.ants[bi];
      if (ba.dead) { continue; }
      if (ba === a) { continue; }
      if (ba.intangible) { continue; }
      var seenIdx = p._sawAnts.indexOf(ba);
      if (seenIdx !== -1) { continue; }
      var bdx = ba.x - p.x;
      var bdy = ba.y - p.y;
      var bd = Math.sqrt(bdx * bdx + bdy * bdy);
      if (bd < bestDist) {
        bestDist = bd;
        bestAnt = ba;
      }
    }
    if (bestAnt) {
      p._sawAnts.push(a);
      p.bouncesRemaining = p.bouncesRemaining - 1;
      p.damage = p.damage * p.damageDecay;
      p.initialHit = false;
      var ndx = bestAnt.x - p.x;
      var ndy = bestAnt.y - p.y;
      var nmag = Math.sqrt(ndx * ndx + ndy * ndy);
      var pspeed = Math.sqrt(p.vx * p.vx + p.vy * p.vy);
      if (nmag > 0) {
        p.vx = (ndx / nmag) * pspeed;
        p.vy = (ndy / nmag) * pspeed;
      }
      p.targetAnt = bestAnt;
      hit = false;
      continue;
    }
  }
  ```

  Note the **no `&&`** style: nested `if`s instead of `if (p.bouncesRemaining > 0 && bestAnt)`. The `continue` skips the despawn path — projectile stays alive for the next bounce. If no `bestAnt` is found, the projectile falls through to the existing despawn path.

- [ ] **Step 3: Use `p.damage` (not `def.damage`) in the damage call**

  The damage application currently reads `def.damage` or similar. For bounce projectiles, the damage decays per bounce, so `applyDamage` must read the projectile's mutable `damage`:

  ```js
  applyDamage(a, p.damage, !!def.armorPierce, sourceCannon);
  ```

  This is a one-line edit but critical — without it, every bounce deals full `def.damage` regardless of decay.

  Existing cannons that don't bounce: `p.damage` is initialized to `def.damage` in Step 1, so behavior is unchanged for them.

- [ ] **Step 4: Gate `statusOnHit` on `p.initialHit`**

  Find the existing status application code (Task 6 or thereabouts; status is applied in the projectile-impact block):

  ```js
  if (def.statusOnHit) {
    var s = def.statusOnHit;
    applyStatus(a, s.kind, s.magnitude, s.duration, 'cannon');
  }
  ```

  Replace with:

  ```js
  if (def.statusOnHit) {
    var s = def.statusOnHit;
    applyStatus(a, s.kind, s.magnitude, s.duration, 'cannon');
  }
  if (def.statusOnFirstHit) {
    if (p.initialHit) {
      var sf = def.statusOnFirstHit;
      applyStatus(a, sf.kind, sf.magnitude, sf.duration, 'cannon');
    }
  }
  ```

  Wrecking Ball uses `statusOnFirstHit` for its 30 px knockback; bounce targets don't get knocked back.

- [ ] **Step 5: PHP lint + curl-grep**

  ```bash
  c:/wamp64/bin/php/php8.2.29/php.exe -l patterns/demo-ant-buster.php
  curl -s 'http://localhost/dariobimbi.com/demos/ant-buster/?debug=1' | grep -E '&#038;&#038;|&#124;&#124;'
  ```

  Lint must pass. Grep must return zero hits.

- [ ] **Step 6: Smoke test (no Rubber cannons exist yet)**

  Existing cannons must still fire correctly. Place Howitzer, Repeater, Twin Gun. Spawn ants. Confirm no console errors and projectiles still kill ants on impact. Bounce branch is dormant until `def.bounces` is set on a cannon (Task 39).

- [ ] **Step 7: Commit**

  ```bash
  git commit -am "feat(ant-buster): MP-L bouncing primitive + statusOnFirstHit gate"
  ```

---

## Task 38: Build palette extension + 1-button selected-tower panel verification

**Files:**
- Modify: `patterns/demo-ant-buster.php` — `drawShop` (`keys` array around line 700).

Plan 2 hardcoded the build palette to 3 cannons. Plan 3's existing 36 tasks did not touch this. Bucket G adds Pink Pea as a 4th base cannon. The selected-tower panel's existing generic loop (Task 4 Step 3) already handles arbitrary `upgrades.length` including 1, so no panel logic change is needed — just verification.

- [ ] **Step 1: Extend the shop `keys` array**

  In `drawShop`, find:

  ```js
  var keys = ['howitzer', 'repeater', 'twingun'];
  ```

  Replace with:

  ```js
  var keys = ['howitzer', 'repeater', 'twingun', 'pink_pea'];
  ```

  The loop, card layout, click hit-testing (`ui.shopRects`), and `placeCannon` flow all already iterate over `keys.length` — no other edits in this step.

- [ ] **Step 2: Confirm the shop column has visual room**

  Card height = 70 px, gap 8 px, startY 32. With 4 cards: bottom of last card = `32 + 4*70 + 3*8 = 32 + 280 + 24 = 336 px`. Playfield height is 480 px. Wave panel sits beneath the shop cards. Confirm the 4-card layout fits without overlapping the wave panel — if it doesn't, reduce `cardH` from 70 to 60 or `cy` gap from 8 to 6. Most likely fits as-is; verify visually.

  This step is a Playwright check, not a code edit. If it fails, do the smallest layout tweak that restores fit.

- [ ] **Step 3: Verify selected-tower panel renders 1-button case**

  The Task 4 generic loop:

  ```js
  for (var i = 0; i < upgrades.length; i++) { /* render upgrade button i */ }
  for (var k = upgrades.length; k < 3; k++) { ui.upgradeRects[k] = null; }
  ```

  When `upgrades.length === 1`, this renders 1 button at slot 0 (`py + 90`) and nulls slots 1 and 2. The sell button stays at fixed `py + 174`. Result: a single upgrade button followed by a 64 px gap to the sell button. Visually OK.

  No code change in this step — verify only by manually testing in Step 5 once Pink Pea exists (Task 39 + 40).

- [ ] **Step 4: PHP lint + curl-grep**

  ```bash
  c:/wamp64/bin/php/php8.2.29/php.exe -l patterns/demo-ant-buster.php
  curl -s 'http://localhost/dariobimbi.com/demos/ant-buster/?debug=1' | grep -E '&#038;&#038;|&#124;&#124;'
  ```

- [ ] **Step 5: Smoke test (Pink Pea slot empty until Task 39)**

  Reload the demo. The shop column should show 4 card slots. The 4th slot will render with no name / cost / sprite because `CANNON_KINDS.pink_pea` doesn't exist yet — that is expected. Confirm: 3 existing cards still render correctly, 4th slot is visible (placeholder), no console errors. The 4th slot is hit-testable (clicking it tries to place `pink_pea` and silently no-ops because the kind is undefined). Task 39 fills the slot.

- [ ] **Step 6: Commit**

  ```bash
  git commit -am "refactor(ant-buster): extend build palette to 4 slots for Pink Rubber Family"
  ```

---

## Task 39: The Rubber Roster (Pink Pea, Bouncy Bubble, Rubber Wrecking Ball)

**Files:**
- Modify: `patterns/demo-ant-buster.php` — append three rows to `CANNON_KINDS`.

All three cannons use the bouncing primitive from Task 37. Pink Pea is the only one reachable from the build palette (Task 38); the other two are upgrade targets. No sprite work in this task — Task 40 owns visuals.

- [ ] **Step 1: Add `pink_pea` to CANNON_KINDS**

  ```js
  pink_pea: {
    name: 'Pink Pea', cost: 50, damage: 5, fireRate: 0.8, range: 130,
    projectileSpeed: 240,
    family: 'rubber', tier: 1,
    bounces: 1, bounceRadius: 50, bounceDamageDecay: 0.8,
    visualScale: 1.0,
    upgradesTo: ['bouncy_bubble']
  },
  ```

  No `color` field (consistent with the marathon's "skip dead field" convention from Task 6).

- [ ] **Step 2: Add `bouncy_bubble` to CANNON_KINDS**

  ```js
  bouncy_bubble: {
    name: 'Bouncy Bubble', cost: 80, damage: 12, fireRate: 0.6, range: 160,
    projectileSpeed: 240,
    family: 'rubber', tier: 2,
    bounces: 2, bounceRadius: 60, bounceDamageDecay: 0.8,
    visualScale: 1.5,
    upgradesTo: ['rubber_wrecking_ball']
  },
  ```

- [ ] **Step 3: Add `rubber_wrecking_ball` to CANNON_KINDS**

  ```js
  rubber_wrecking_ball: {
    name: 'Rubber Wrecking Ball', cost: 120, damage: 30, fireRate: 0.4, range: 180,
    projectileSpeed: 220,
    family: 'rubber', tier: 3,
    bounces: 4, bounceRadius: 70, bounceDamageDecay: 0.8,
    visualScale: 2.5,
    statusOnFirstHit: { kind: 'knockback', magnitude: 30, duration: 0 },
    upgradesTo: []
  },
  ```

  `statusOnFirstHit` reuses Plan 2's `knockback` status (already implemented). Magnitude 30 px displacement. Duration 0 because knockback is an instantaneous displacement, not a sustained effect.

- [ ] **Step 4: PHP lint + curl-grep**

  ```bash
  c:/wamp64/bin/php/php8.2.29/php.exe -l patterns/demo-ant-buster.php
  curl -s 'http://localhost/dariobimbi.com/demos/ant-buster/?debug=1' | grep -E '&#038;&#038;|&#124;&#124;'
  ```

- [ ] **Step 5: Playwright verify**

  Reload demo. The 4th shop slot now renders "Pink Pea" + "50g" + "rubber" hint (sprite is still default until Task 40). Place a Pink Pea. Spawn 3 workers in a tight cluster. Fire — projectile should hit the first ant for 5 dmg, then bounce to a second ant for 4 dmg (5 × 0.8). No 3rd hit (bounces=1).

  Click the Pink Pea. Selected-tower panel shows **1 upgrade button**: "Bouncy Bubble (80g)". Click it (with enough gold). Cannon upgrades to Bouncy Bubble. Selected-tower panel now shows **1 upgrade button**: "Rubber Wrecking Ball (120g)". Upgrade again. Now the panel shows **0 upgrade buttons** — final tier.

  Spawn ants. Wrecking Ball fires; 1st ant takes 30 dmg + knockback (30 px displacement visible). Subsequent bounce targets take damage with no knockback.

- [ ] **Step 6: Commit**

  ```bash
  git commit -am "feat(ant-buster): Pink Rubber Family roster (4.1, 4.2, 4.3)"
  ```

---

## Task 40: Pink Rubber visuals - turret sprites + visualScale projectile rendering + final QA

**Files:**
- Modify: `patterns/demo-ant-buster.php` — `drawProjectiles` (~line 1006), sprite factories (turret region), `game.sprites.turrets` table.

- [ ] **Step 1: Add `visualScale` to projectile rendering**

  Find `drawProjectiles` (~line 1006). Current body:

  ```js
  ctx.drawImage(game.sprites.projectile, Math.round(p.x - 2), Math.round(p.y - 2 + arcOffset));
  ```

  Replace with:

  ```js
  var pdef = CANNON_KINDS[p.cannonKind];
  var pscale = 1;
  if (pdef) {
    if (pdef.visualScale) { pscale = pdef.visualScale; }
  }
  if (pscale === 1) {
    ctx.drawImage(game.sprites.projectile, Math.round(p.x - 2), Math.round(p.y - 2 + arcOffset));
  } else {
    var sw = 4 * pscale;
    var sh = 4 * pscale;
    ctx.drawImage(
      game.sprites.projectile,
      Math.round(p.x - sw / 2),
      Math.round(p.y - sh / 2 + arcOffset),
      sw, sh
    );
  }
  ```

  `p.cannonKind` is the firing cannon's kind, set in `fireProjectile` (already exists from earlier tasks; if not, add `cannonKind: cannon.kind` to the projectile literal in `fireProjectile` first). The 5-arg `ctx.drawImage` scales the sprite to `(sw, sh)`. The base sprite is 4×4 px.

  No `&&`/`||` — nested `if`s and explicit checks per CLAUDE.md.

- [ ] **Step 2: Pink Pea turret sprite**

  Add the factory near the other `makeCannonTurret*` functions:

  ```js
  function makeCannonTurretPinkPea() {
    var s = makeCanvas(28, 28);
    var sc = s.getContext('2d');
    sc.fillStyle = '#ff69b4';      // pink barrel
    sc.fillRect(10, 8, 8, 12);
    sc.fillStyle = '#c2185b';      // darker pink base
    sc.fillRect(8, 16, 12, 8);
    sc.fillStyle = '#ffc1d8';      // light pink highlight on barrel
    sc.fillRect(12, 9, 4, 2);
    return s;
  }
  ```

- [ ] **Step 3: Bouncy Bubble turret sprite**

  ```js
  function makeCannonTurretBouncyBubble() {
    var s = makeCanvas(28, 28);
    var sc = s.getContext('2d');
    sc.fillStyle = '#ff5aa4';      // mid pink, slightly hotter
    sc.fillRect(9, 7, 10, 13);
    sc.fillStyle = '#a8124a';      // base
    sc.fillRect(7, 16, 14, 8);
    sc.fillStyle = '#ffd5e8';      // bubble highlight (round-ish)
    sc.fillRect(11, 8, 6, 3);
    sc.fillRect(12, 11, 4, 2);
    return s;
  }
  ```

- [ ] **Step 4: Rubber Wrecking Ball turret sprite**

  ```js
  function makeCannonTurretRubberWreckingBall() {
    var s = makeCanvas(28, 28);
    var sc = s.getContext('2d');
    sc.fillStyle = '#ff1493';      // hot pink
    sc.fillRect(8, 6, 12, 14);
    sc.fillStyle = '#7a0a3e';      // dark base
    sc.fillRect(6, 16, 16, 9);
    sc.fillStyle = '#ffa7c8';      // chunky highlight
    sc.fillRect(11, 8, 6, 3);
    sc.fillStyle = '#fee761';      // tiny gold rivet
    sc.fillRect(13, 18, 2, 2);
    return s;
  }
  ```

- [ ] **Step 5: Wire turret sprites into the lookup**

  Find `game.sprites.turrets = { ... }` and add three entries:

  ```js
  pink_pea: makeCannonTurretPinkPea(),
  bouncy_bubble: makeCannonTurretBouncyBubble(),
  rubber_wrecking_ball: makeCannonTurretRubberWreckingBall(),
  ```

- [ ] **Step 6: Replace projectile sprite tint at draw time (optional polish)**

  The shared `game.sprites.projectile` is a small white-ish ball. For pink projectile color without making three new sprites, add a `ctx.globalCompositeOperation = 'multiply'` overlay or a pink fill rect on top of the scaled image. Cleanest minimal version: draw a filled circle on top of the sprite for Rubber projectiles:

  ```js
  if (pdef && pdef.family === 'rubber') {
    ctx.fillStyle = pdef.tier === 3 ? '#ff1493' : (pdef.tier === 2 ? '#ff5aa4' : '#ff69b4');
    ctx.beginPath();
    ctx.arc(p.x, p.y + arcOffset, 2 * pscale, 0, Math.PI * 2);
    ctx.fill();
  }
  ```

  Place this *after* the `ctx.drawImage` call from Step 1. Note the ternary expression is allowed — `?:` is not on the encoded-operator hazard list. If the ternary feels risky, expand to nested `if`s for the color pick.

- [ ] **Step 7: PHP lint + curl-grep**

  ```bash
  c:/wamp64/bin/php/php8.2.29/php.exe -l patterns/demo-ant-buster.php
  curl -s 'http://localhost/dariobimbi.com/demos/ant-buster/?debug=1' | grep -E '&#038;&#038;|&#124;&#124;'
  ```

  Both must return clean.

- [ ] **Step 8: Final QA against §9.4 acceptance criteria**

  Walk the demo at `?debug=1`:

  1. Build palette shows 4 cards: Howitzer, Repeater, Twin Gun, **Pink Pea (50g, rubber family)** — sprite renders pink.
  2. Place Pink Pea. Spawn 4 workers in a cluster. Pink Pea fires a small pink ball; first hit deals 5 dmg, second hit (bounce) deals 4 dmg (5 × 0.8). No 3rd hit.
  3. Selected-tower panel for Pink Pea shows **1 upgrade button**: "Bouncy Bubble (80g)". Sell button below it.
  4. Upgrade to Bouncy Bubble. Projectile is now visibly larger (1.5× scale). Bounces up to 2 times within 60 px.
  5. Upgrade to Rubber Wrecking Ball. Projectile is 2.5× scale (clearly heavier). First impact deals 30 dmg + visible 30 px knockback. Subsequent bounces (up to 4 times within 70 px) deal damage but **no knockback** — the ant just takes damage in place.
  6. Selected-tower panel for Rubber Wrecking Ball shows **0 upgrade buttons** (final tier).
  7. No console errors during the walk.

- [ ] **Step 9: Commit**

  ```bash
  git commit -am "feat(ant-buster): Pink Rubber visuals - turret sprites + visualScale rendering"
  ```

---

## End-of-plan notes

**Branch strategy.** Plan 2 used a feature branch (`ant-buster-plan-2`) merged to `main` after status doc. Plan 3 should follow the same pattern: create `ant-buster-plan-3` from `main`, work tasks 1-40 there, merge after status doc. (Task 36's status doc walk includes Bucket G's acceptance items §9.4.)

**Mid-stream playtest checkpoints.** Recommended natural pause points:
- After Task 5 (architecture refactors complete) — confirm no regressions on Plan 2 functionality
- After Task 12 (Heavy family complete) — playtest waves 1-5 with all 7 Heavy cannons
- After Task 19 (Quick family complete) — same with all 14 cannons available
- After Task 26 (Double family complete) — full 30-cannon roster (9 from Plan 2 + 21 from Plan 3)
- After Task 32 (King fight playable) — full wave-25 playthrough
- Task 36 is the original final acceptance walk
- Tasks 37-40 (Bucket G) execute after Task 36 lands. Task 40 Step 8 is the Bucket G acceptance walk.

**Subagent-driven development pattern.** Plan 2's status doc called out that the per-task implementer + combined spec/code-quality reviewer split scaled to 27 tasks. Plan 3 at 40 tasks (36 original + 4 Bucket G) is in the same ballpark; same pattern applies. Trivial single-line tasks (33, 34, 35) can bundle into a single commit with inline review if desired.

**`window.__ab*` debug surfaces.** Plan 2 status workflow notes recommended adding `window.__abSpawn = spawnAnt` and `window.__abPlaceCannon = placeCannon`. Bake these into Task 1 (or any early task that's already touching the debug-export region) so subsequent tasks can use them in Playwright introspection without manual mouse events.

**File-size monitor.** Plan 3 projects to ~3000–3300 lines (per spec §3.5). If the file crosses 3500 during Plan 3, pause and consider whether to extract `CANNON_KINDS` to a separate `data/` PHP file. Plan 4 will revisit regardless.

**Voice rule does not apply.** The implementation file's PHP comments and JS comments are admin-only and not visitor-facing copy. Em-dashes and technical jargon are fine. The voice rule applies to template HTML and pattern PHP that visitors see — not to inline JS comments inside `<script>` blocks.
