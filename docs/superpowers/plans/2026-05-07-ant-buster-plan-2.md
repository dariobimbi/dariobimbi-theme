# Ant Buster — Plan 2 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ship Ant Buster's full systems layer plus a stub-content roster (9 of 48 cannons, 10 of 10 enemy ant kinds, 5 of 25 waves, full status framework) so Plan 3 can be pure data fill.

**Architecture:** Single-file pattern (`patterns/demo-ant-buster.php`). Four central kind-tables (`CANNON_KINDS`, `ANT_KINDS`, `STATUS_KINDS`, `WAVES`) drive every entity by string key. Adding new content = appending rows. No new files, no new templates.

**Tech Stack:** Vanilla JS (IIFE, `var`, no `&&`/`||` operators in inline scripts), Canvas 2D, fixed-timestep loop, procedural placeholder sprites. PHP 8.2 for `php -l`. Playwright MCP for per-task verification (navigate + console + `window.__ab` state inspection + screenshot).

**Spec:** [`../specs/2026-05-07-ant-buster-plan-2-design.md`](../specs/2026-05-07-ant-buster-plan-2-design.md)
**Plan 1 status:** [`./2026-05-06-ant-buster-plan-1-status.md`](./2026-05-06-ant-buster-plan-1-status.md)

---

## Conventions every task must follow

1. **Inline-script syntax footgun.** Never write `&&` or `||` inside the `<script>` block. Use nested `if`, `indexOf` checks (`['a','b'].indexOf(x) === -1`), or helper booleans. After every commit, verify with:

   ```bash
   curl -s http://localhost/dariobimbi.com/demos/ant-buster/ | grep -F '&#038;&#038;'
   ```

   Expected: empty output. Any hit blocks the commit; rewrite the offending expression.
2. **`var` only.** No `let` or `const`. The IIFE-scoped patterns from Plan 1 stand.
3. **PHP syntax check after every edit:**

   ```bash
   c:/wamp64/bin/php/php8.2.29/php.exe -l c:/wamp64/www/dariobimbi.com/wp-content/themes/dariobimbi/patterns/demo-ant-buster.php
   ```

   Expected: `No syntax errors detected`.
4. **Playwright verification per task:**
   - Navigate to `http://localhost/dariobimbi.com/demos/ant-buster/`.
   - Read console messages (`browser_console_messages`) — any error other than benign 404s for non-existent assets is a blocker.
   - Inspect game state via `browser_evaluate` reading `window.__ab` (the IIFE exposes the entire `game` object as `window.__ab` at line 901 today — keep that line intact).
   - Screenshot for any visual change.
5. **Commit after each task.** Conventional commits style matching Plan 1: `feat(ant-buster): …`, `refactor(ant-buster): …`, `fix(ant-buster): …`.
6. **No theme version bump per task** — bump once at the end (Task 26).
7. **Serve from local WAMP.** The demo is at `/demos/ant-buster/`. If the page returns 404, check that WAMP is running and the page exists; do not re-run `auto-pages` reconciliation as part of a task.

---

## File structure

All work lives in **one** file: `c:/wamp64/www/dariobimbi.com/wp-content/themes/dariobimbi/patterns/demo-ant-buster.php`.

Currently 903 lines, projected to grow to ~1800-2200 lines (still under the 2500-line single-file ceiling called out in spec §3.4). Logical regions inside the IIFE, in order from top:

1. **Constants & state** (lines ~85-120 today): `WIDTH`, `HEIGHT`, `PLAYFIELD_W`, `HUD_H`, `SHOP_W`, `TARGET_FPS`, `FIXED_DT`, then the `game` state object.
2. **Canvas setup & sprite factories** (~120-230): `makeCanvas`, `makeWorkerSprite`, `makeAntSplat`, `makeCannonBase`, `makeCannonTurret`, `makeCake`, `makeAnthill`, `makeProjectile`. Plan 2 adds: `makeScout`, `makeSoldier`, `makeTank`, `makeSplitter`, `makeMiniSplitter`, `makePhase`, `makeBomber`, `makeQueen`, plus 8 cannon turret sprites (per kind), plus `makeFallenSlice`, plus status-effect indicator sprites.
3. **Kind tables (NEW for Plan 2):** Sit immediately above `render()`. `CANNON_KINDS`, `ANT_KINDS`, `STATUS_KINDS`, `WAVES`. The order in the IIFE matters — Plan 1's gotcha note ("`var` data tables are not value-hoisted") means tables must appear textually before any function that runs at IIFE-init time and references them. Defensive: keep tables above all `function`-declarations that are called from `resetGame()` or the IIFE entry point.
4. **Render** (~234-396): `render()`. Plan 2 rewrites this into the 13-layer z-order from spec §3.5.
5. **Ant logic** (~399-600): `spawnAnt`, `angleTowards`, `angleDelta`, `steerAroundCannons`, `updateAnt`, `updateAnts`, `checkPickupsAndDeliveries`, `pruneAnts`. Plan 2 adds status-effect application, fallen-slice pickup, soft-repulsion clamp, intangibility, on-death hooks.
6. **Cannon logic** (~603-755): `CANNON_DEFS` (DELETED, replaced by `CANNON_KINDS`), `cannonAt`, `canPlaceCannon`, `placeCannon`, `findTarget`, `updateCannons`, `fireProjectile`, `updateProjectiles`. Plan 2 adds beam cannons, multi-barrel volleys, spool-up, pierce flag, disabled state.
7. **Wave logic** (~754-792): `wave` (DELETED, replaced by `WAVES` + `spawner` substate). Plan 2 adds 5-wave state machine and spawner.
8. **Reset / UI / input** (~794-900): `resetGame`, `ui`, `canvasToGame`, `shopCardAt`, mousedown/mousemove/mouseup handlers, `update`, `frame`, `window.__ab = game`. Plan 2 adds tower-detail panel, upgrade buttons, sell, pause, speed, keyboard shortcuts, debug `T`, fallen-slice rendering, wave intro banner.

---

## Task 1: Bump native canvas to 640×360, reposition cake / anthill / playfield

**Files:**
- Modify: `patterns/demo-ant-buster.php` lines ~87-92 (constants), ~98-105 (`game.cake`, `game.anthill` initial positions inside `game` object), ~510-515 (playfield-bounds clamp inside `updateAnt`), ~240-243 (blanket coords inside `render`), CSS at top of pattern (the `<style>` block — bump `#ab-canvas { width: ... }` if hard-coded).

- [ ] **Step 1: Read the constants block and update**

  Replace:
  ```js
  var WIDTH = 480;
  var HEIGHT = 270;
  var PLAYFIELD_W = 320;
  var PLAYFIELD_H = 246;
  ```
  with:
  ```js
  var WIDTH = 640;
  var HEIGHT = 360;
  var PLAYFIELD_W = 480;
  var PLAYFIELD_H = 336;
  ```

- [ ] **Step 2: Reposition cake and anthill in the `game` initial-state object**

  Plan 1 has `cake: { x: 264, y: 56, slices: 8, lost: 0 }` and `anthill: { x: 16, y: 135 }`. Update to:
  ```js
  cake: { x: 352, y: 76, slices: 8, lost: 0 },
  anthill: { x: 22, y: 184 },
  ```
  Also update the same values inside `resetGame()` (Plan 1 duplicates the literals there).

- [ ] **Step 3: Update the picnic-blanket draw rect inside `render()`**

  Plan 1's blanket is `blanketX = 32, blanketY = 32, blanketW = 256, blanketH = 200`. Rescale proportionally for the new playfield: `blanketX = 48, blanketY = 48, blanketW = 384, blanketH = 272`. Eyeball-check the cake plate sits inside the blanket; if it overhangs, nudge values by ±8 px.

- [ ] **Step 4: Confirm the backing canvas scales correctly**

  Plan 1 sets backing canvas to `2 * WIDTH × 2 * HEIGHT` and applies `ctx.scale(2, 2)`. Because `WIDTH` / `HEIGHT` are read from the new constants, no edit needed here — just verify no hard-coded `960` or `540` remains in the file.

  ```bash
  grep -nE '\b(960|540|480|270)\b' c:/wamp64/www/dariobimbi.com/wp-content/themes/dariobimbi/patterns/demo-ant-buster.php
  ```
  Expected: no hits for `480` or `270` outside of the new playfield-W constant or comments. (Backing canvas math should reference `WIDTH * 2`, not `960`.)

- [ ] **Step 5: PHP lint + curl ampersand check**

  ```bash
  c:/wamp64/bin/php/php8.2.29/php.exe -l c:/wamp64/www/dariobimbi.com/wp-content/themes/dariobimbi/patterns/demo-ant-buster.php
  curl -s http://localhost/dariobimbi.com/demos/ant-buster/ | grep -F '&#038;&#038;' | head -5
  ```

- [ ] **Step 6: Playwright verification**

  Navigate to `/demos/ant-buster/`, take a screenshot. The canvas element should render at 1280×720 backing (CSS may scale it). Visually: the playfield is wider, cake is in the upper-right, anthill is left-center-low, ants spawn from the anthill area. Click to start, watch one ant walk; it should not get stuck or vanish off-screen.

- [ ] **Step 7: Commit**

  ```bash
  git add patterns/demo-ant-buster.php
  git commit -m "refactor(ant-buster): bump native canvas to 640x360, reposition cake/anthill"
  ```

---

## Task 2: Introduce `CANNON_KINDS` table; refactor existing Howitzer to read from it

**Files:**
- Modify: `patterns/demo-ant-buster.php` line ~603 (`CANNON_DEFS` becomes `CANNON_KINDS`); update every reader (`CANNON_DEFS.howitzer` → `CANNON_KINDS[cannon.kind]`); add `kind` and `kindHistory` to placed cannon objects; touch `placeCannon`, `findTarget`, `updateCannons`, `fireProjectile`, render, and the shop card.

- [ ] **Step 1: Replace `CANNON_DEFS` with the new `CANNON_KINDS` table**

  Above the `render()` function, replace:
  ```js
  var CANNON_DEFS = {
    howitzer: { name: 'Howitzer', cost: 50, damage: 5, fireRate: 0.6, range: 110, projectileSpeed: 240, color: '#3a4466' }
  };
  ```
  with:
  ```js
  var CANNON_KINDS = {
    howitzer: {
      name: 'Howitzer',
      cost: 50,
      damage: 5,
      fireRate: 0.6,
      range: 110,
      projectileSpeed: 240,
      color: '#3a4466',
      family: 'heavy',
      tier: 1,
      upgradesTo: []  // siege added in Task 11
    }
  };
  ```

- [ ] **Step 2: Update `placeCannon` to set `kind` and `kindHistory`**

  Existing signature: `placeCannon(defKey, x, y)`. Rename param to `kindKey` and write:
  ```js
  function placeCannon(kindKey, x, y) {
    var def = CANNON_KINDS[kindKey];
    var c = {
      kind: kindKey,
      kindHistory: [kindKey],   // base kind included per spec SYS-2
      x: x, y: y,
      heading: 0,
      cooldown: 0,
      kills: 0,
      disabled: false,
      disabledUntil: 0
    };
    game.cannons.push(c);
    game.gold = game.gold - def.cost;
  }
  ```
  Drop any field Plan 1 had on the cannon that's now read from `CANNON_KINDS` (e.g. cached `damage`, `fireRate`).

- [ ] **Step 3: Replace every `CANNON_DEFS[c.defKey]` and `c.defKey` lookup**

  Search-and-replace within the file:
  - `CANNON_DEFS` → `CANNON_KINDS`
  - `c.defKey` → `c.kind` (and any other variable names — `cannon.defKey` → `cannon.kind`)
  - `def.<field>` reads stay the same since `def` is now `CANNON_KINDS[c.kind]`.

- [ ] **Step 4: Shop card uses `CANNON_KINDS.howitzer`**

  In `render()`, the shop card displays `CANNON_DEFS.howitzer.cost` and `CANNON_DEFS.howitzer.name`. Update to `CANNON_KINDS.howitzer.…`.

- [ ] **Step 5: Verify in browser**

  Lint, curl-grep ampersands. Open in Playwright. Place a Howitzer (drag from shop). Check `window.__ab.cannons[0]` via `browser_evaluate`:
  ```js
  return JSON.stringify({
    kind: window.__ab.cannons[0].kind,
    kindHistory: window.__ab.cannons[0].kindHistory,
    hasDefKey: 'defKey' in window.__ab.cannons[0]
  });
  ```
  Expected: `{"kind":"howitzer","kindHistory":["howitzer"],"hasDefKey":false}`. Cannon fires at ants; ants take damage; gameplay unchanged.

- [ ] **Step 6: Commit**

  ```bash
  git commit -am "refactor(ant-buster): introduce CANNON_KINDS table with kind+kindHistory entity fields"
  ```

---

## Task 3: Introduce `ANT_KINDS` table; refactor Worker + Carrier to read from it

**Files:**
- Modify: `patterns/demo-ant-buster.php`. Add `ANT_KINDS` table above `spawnAnt` (~line 399). Refactor `spawnAnt`, `updateAnt`, `checkPickupsAndDeliveries`, `pruneAnts`, render layer for ants.

Plan 1 stores Carrier vs Worker behavior on a single `Worker` ant via the `carrying` flag flipping its speed/alignment. Plan 2 splits this into proper `kind` rows: `'worker'` (no special), `'carrier'` (steal-and-deliver). For Plan 2 they share most numbers, but the table cleanup unblocks the other 8 kinds.

- [ ] **Step 1: Add `ANT_KINDS` above `spawnAnt`**

  ```js
  var ANT_KINDS = {
    worker: {
      name: 'Worker',
      hp: 10,
      speed: 30,
      gold: 2,
      alignment: 0.2,
      jitterRate: 4.0,
      wanderFreqMin: 0.3, wanderFreqMax: 0.7,
      wanderAmpMin: 0.9, wanderAmpMax: 1.5,
      spriteKey: 'worker',
      spriteW: 16, spriteH: 16,
      slowResist: 0,
      damageMult: 1,
      pierceMult: 1,
      canPickup: true,
      onDeath: null,
      armor: 0
    },
    carrier: {
      name: 'Carrier',
      hp: 15,
      speed: 30, fleeSpeed: 45,
      gold: 4, fleeGold: 8,
      alignment: 0.2,
      jitterRate: 4.0,
      wanderFreqMin: 0.3, wanderFreqMax: 0.7,
      wanderAmpMin: 0.9, wanderAmpMax: 1.5,
      spriteKey: 'worker',  // shares Worker sprite for now; visual tell is the carried crumb
      spriteW: 16, spriteH: 16,
      slowResist: 0,
      damageMult: 1,
      pierceMult: 1,
      canPickup: true,
      onDeath: null,
      armor: 0
    }
  };
  ```

  Plan 2's spec calls out ten total entries; the rest land in Tasks 15-21.

- [ ] **Step 2: Rewrite `spawnAnt` to take `kindKey`**

  ```js
  function spawnAnt(kindKey) {
    var k = ANT_KINDS[kindKey];
    var ant = {
      kind: kindKey,
      x: game.anthill.x + (Math.random() * 16 - 8),
      y: game.anthill.y + (Math.random() * 32 - 16),
      hp: k.hp,
      speed: k.speed,
      heading: Math.random() * Math.PI * 2,
      wanderPhase: Math.random() * Math.PI * 2,
      wanderFreq: k.wanderFreqMin + Math.random() * (k.wanderFreqMax - k.wanderFreqMin),
      wanderAmp: k.wanderAmpMin + Math.random() * (k.wanderAmpMax - k.wanderAmpMin),
      jitterT: 0,
      carrying: false,
      dead: false,
      deathTime: 0,
      deliveredAt: 0,    // SYS-7 nit: separate field from deathTime
      effects: {}        // SYS-1: status framework slot
    };
    game.ants.push(ant);
  }
  ```

- [ ] **Step 3: `updateAnt` reads kind for behavior numbers**

  Replace literal constants in `updateAnt`:
  ```js
  var k = ANT_KINDS[ant.kind];
  var alignment = k.alignment;
  var jitterRate = k.jitterRate;
  var speed = ant.carrying ? (k.fleeSpeed || k.speed) : k.speed;
  ```
  Final-approach mode and carrying-mode overrides stay as Plan 1 had them, but read base values from `k` not literals.

- [ ] **Step 4: Replace existing wave spawn site**

  Plan 1's wave schedule has `{type: 'worker', ...}` entries. The spawner already calls `spawnAnt('worker')` — confirm it still does after the refactor. (No change needed if Plan 1 already passed the type string.)

- [ ] **Step 5: Verify**

  Lint + curl-grep. In Playwright: start game, watch wave 1 unfold, check `window.__ab.ants[0]`:
  ```js
  return JSON.stringify({ kind: window.__ab.ants[0].kind, hp: window.__ab.ants[0].hp, hasEffects: !!window.__ab.ants[0].effects });
  ```
  Expected: `{"kind":"worker","hp":10,"hasEffects":true}`. Steal-and-deliver still works (kill or spare a Carrier, observe behavior).

- [ ] **Step 6: Commit**

  ```bash
  git commit -am "refactor(ant-buster): introduce ANT_KINDS table with effects slot"
  ```

---

## Task 4: `STATUS_KINDS` framework + per-ant `effects` slot + tick handler

**Files:**
- Modify: same file. Add `STATUS_KINDS` table above `updateAnts`. Add `applyStatus(ant, kind, magnitude, duration, source)`. Modify `updateAnt` to read effective speed from status, decrement timers, drop expired effects.

- [ ] **Step 1: Add `STATUS_KINDS` table**

  ```js
  var STATUS_KINDS = {
    slow:      { name: 'Slow',      onApply: null, perTick: null, speedMult: function(m) { return 1 - m; } },
    freeze:    { name: 'Freeze',    onApply: null, perTick: null, speedMult: function(m) { return 0; } },
    burn:      { name: 'Burn',      onApply: null, perTick: function(ant, eff, dt) { ant.hp = ant.hp - eff.magnitude * dt; }, speedMult: null },
    poison:    { name: 'Poison',    onApply: null, perTick: function(ant, eff, dt) { ant.hp = ant.hp - eff.magnitude * dt; }, speedMult: null },
    stun:      { name: 'Stun',      onApply: null, perTick: null, speedMult: function(m) { return 0; } },
    knockback: { name: 'Knockback', onApply: function(ant, m) { var k = ANT_KINDS[ant.kind]; ant.x = ant.x + Math.cos(ant.heading + Math.PI) * m; ant.y = ant.y + Math.sin(ant.heading + Math.PI) * m; }, perTick: null, speedMult: null }
  };
  ```

  Comments: `slow` and `freeze` and `stun` all stop or scale movement; `burn` and `poison` apply tick damage; `knockback` is one-shot impulse on apply.

- [ ] **Step 2: Add `applyStatus` helper**

  ```js
  function applyStatus(ant, kindKey, magnitude, duration, source) {
    // Soldier slow-resist (SYS-3 — see Task 16 to land the field)
    var k = ANT_KINDS[ant.kind];
    if (kindKey === 'slow') {
      magnitude = magnitude * (1 - (k.slowResist || 0));
    }
    var existing = ant.effects[kindKey];
    var newExpiry = (game.gameTime || 0) + duration;
    if (existing) {
      if (existing.magnitude > magnitude) { magnitude = existing.magnitude; }
      if (existing.expiresAt > newExpiry) { newExpiry = existing.expiresAt; }
    }
    ant.effects[kindKey] = { magnitude: magnitude, expiresAt: newExpiry, source: source };
    var def = STATUS_KINDS[kindKey];
    if (def.onApply) { def.onApply(ant, magnitude); }
  }
  ```

- [ ] **Step 3: Add `game.gameTime` (seconds since game start)**

  In `game` initial state add `gameTime: 0,`. In `update(dt)`, before any other system runs while in the `'playing'` state, do `game.gameTime = game.gameTime + dt;`. Reset to 0 in `resetGame()`.

- [ ] **Step 4: Wire effects into `updateAnt` movement and damage**

  Inside `updateAnt`, after computing base `speed`:
  ```js
  // status-effect speed scaling
  var ek;
  for (ek in ant.effects) {
    var def = STATUS_KINDS[ek];
    if (def.speedMult) {
      var mag = ant.effects[ek].magnitude;
      speed = speed * def.speedMult(mag);
    }
  }
  // status-effect tick damage
  for (ek in ant.effects) {
    var defT = STATUS_KINDS[ek];
    if (defT.perTick) { defT.perTick(ant, ant.effects[ek], dt); }
  }
  // expire
  var keys = [];
  for (ek in ant.effects) { keys.push(ek); }
  for (var ki = 0; ki < keys.length; ki++) {
    if (ant.effects[keys[ki]].expiresAt <= game.gameTime) {
      delete ant.effects[keys[ki]];
    }
  }
  ```

  Note: no `&&` operator anywhere — all conditionals are nested `if` or single-test.

- [ ] **Step 5: Verify framework end-to-end via debug injection**

  In Playwright, after starting the game:
  ```js
  // inject a slow on the first ant
  var a = window.__ab.ants[0];
  a.effects.slow = { magnitude: 0.5, expiresAt: window.__ab.gameTime + 3, source: 'test' };
  return { speedFromKind: 30, effectiveSpeed: a.speed };
  ```
  Wait one frame, check that the ant visibly moves slower. After 3 seconds, the effect drops and the ant resumes full speed.

  Also check `Object.keys(window.__ab.ants[0].effects)` is `[]` initially — meaning the slot exists but is empty.

  ```js
  return Object.keys(window.__ab).indexOf('gameTime') >= 0 ? 'has gameTime' : 'missing';
  ```

- [ ] **Step 6: Commit**

  ```bash
  git commit -am "feat(ant-buster): status-effect framework with all 6 kinds (slow/freeze/burn/poison/stun/knockback)"
  ```

---

## Task 5: `WAVES` array + spawner state machine (idle → prep → wave → prep → won)

**Files:**
- Modify: same file. Replace `var wave = {...}` (~line 754) with `WAVES` array + `spawner` substate on `game`. Rewrite `updateWave` as `updateWaveState`. Add prep timer + transitions. Update win condition.

- [ ] **Step 1: Add `WAVES` array (wave 1 only for now; 2-5 land in Task 22)**

  ```js
  var WAVES = [
    {
      number: 1,
      prep: 30,
      bonus: 21,
      spawns: [
        { type: 'worker', count: 8, interval: 1.0, delay: 0 }
      ]
    }
  ];
  ```

- [ ] **Step 2: Replace `game.wave` initial state**

  ```js
  // in `game` initial state
  waveIndex: 0,           // index into WAVES
  waveState: 'prep',      // 'prep' | 'wave' | 'won' | 'lost' | 'idle'
  prepRemaining: 30,
  spawner: { groupIndex: 0, spawnedInGroup: 0, nextSpawnAt: 0, queueDone: false }
  ```

  In `resetGame()`, mirror these.

- [ ] **Step 3: Rewrite `updateWave` to `updateWaveState(dt)`**

  ```js
  function updateWaveState(dt) {
    if (game.waveState === 'prep') {
      game.prepRemaining = game.prepRemaining - dt;
      if (game.prepRemaining <= 0) {
        game.waveState = 'wave';
        game.spawner.groupIndex = 0;
        game.spawner.spawnedInGroup = 0;
        game.spawner.nextSpawnAt = game.gameTime + WAVES[game.waveIndex].spawns[0].delay;
        game.spawner.queueDone = false;
      }
      return;
    }
    if (game.waveState === 'wave') {
      var wv = WAVES[game.waveIndex];
      // spawn from current group
      if (!game.spawner.queueDone) {
        var grp = wv.spawns[game.spawner.groupIndex];
        if (game.gameTime >= game.spawner.nextSpawnAt) {
          spawnAnt(grp.type);
          game.spawner.spawnedInGroup = game.spawner.spawnedInGroup + 1;
          if (game.spawner.spawnedInGroup >= grp.count) {
            game.spawner.groupIndex = game.spawner.groupIndex + 1;
            game.spawner.spawnedInGroup = 0;
            if (game.spawner.groupIndex >= wv.spawns.length) {
              game.spawner.queueDone = true;
            } else {
              game.spawner.nextSpawnAt = game.gameTime + wv.spawns[game.spawner.groupIndex].delay;
            }
          } else {
            game.spawner.nextSpawnAt = game.gameTime + grp.interval;
          }
        }
      }
      // wave complete: queue done AND no live ants
      if (game.spawner.queueDone) {
        var alive = 0;
        for (var i = 0; i < game.ants.length; i++) {
          if (!game.ants[i].dead) { alive = alive + 1; }
        }
        if (alive === 0) {
          game.gold = game.gold + wv.bonus;
          if (game.waveIndex + 1 >= WAVES.length) {
            game.waveState = 'won';
          } else {
            game.waveIndex = game.waveIndex + 1;
            game.waveState = 'prep';
            game.prepRemaining = WAVES[game.waveIndex].prep;
          }
        }
      }
    }
  }
  ```

- [ ] **Step 4: Update `update(dt)` to call `updateWaveState`**

  Replace the `updateWave(dt)` call with `updateWaveState(dt)`.

- [ ] **Step 5: Update render to read new state**

  HUD wave display: `'Wave ' + (game.waveIndex + 1) + '/' + WAVES.length`. Win overlay triggers on `game.waveState === 'won'`. Lose overlay still triggers on `cake.lost >= 8`.

- [ ] **Step 6: Verify**

  Lint, curl-grep. In Playwright: start game, observe prep timer at 30s, watch wave 1 spawn, complete the wave (or use cheats — for now, the wave naturally completes), verify `game.waveState` transitions to `'won'` and the win overlay appears. Restart works.

  ```js
  return JSON.stringify({ wi: window.__ab.waveIndex, ws: window.__ab.waveState, prep: window.__ab.prepRemaining });
  ```

- [ ] **Step 7: Commit**

  ```bash
  git commit -am "refactor(ant-buster): WAVES array + prep/wave/won state machine"
  ```

---

## Task 6: Wave intro banner + prep timer + Send-Next-Wave button + Enter

**Files:**
- Modify: same file. Add banner state + animation, add Send-Next-Wave button hit-test, render prep countdown in HUD, add `Enter` keyboard handler.

- [ ] **Step 1: Add banner state**

  In `game` initial state:
  ```js
  banner: { active: false, text: '', startedAt: 0, duration: 2.5, isBoss: false }
  ```
  Helper function:
  ```js
  function showBanner(text, isBoss) {
    game.banner.active = true;
    game.banner.text = text;
    game.banner.startedAt = game.gameTime;
    game.banner.duration = isBoss ? 3 : 2.5;
    game.banner.isBoss = isBoss;
  }
  ```

- [ ] **Step 2: Trigger banner on prep→wave transition**

  Inside `updateWaveState`, on the `prep` branch when `prepRemaining <= 0`, after setting `waveState = 'wave'`:
  ```js
  var wv = WAVES[game.waveIndex];
  showBanner('Wave ' + wv.number + '/' + WAVES.length, !!wv.boss);
  ```

- [ ] **Step 3: Render banner in `render()` — last before HUD overlay**

  Drop into the overlay layer (spec §3.5 layer 13). Slide-in: x position eased from `-200` to `WIDTH/2 - 48` over first 0.4s, dwell, fade-out alpha over last 0.5s.

  ```js
  if (game.banner.active) {
    var t = game.gameTime - game.banner.startedAt;
    if (t > game.banner.duration) {
      game.banner.active = false;
    } else {
      var alpha = 1;
      if (t < 0.4) { alpha = t / 0.4; }
      if (t > game.banner.duration - 0.5) { alpha = (game.banner.duration - t) / 0.5; }
      ctx.save();
      ctx.globalAlpha = alpha;
      ctx.fillStyle = game.banner.isBoss ? '#a44' : '#3a4466';
      ctx.fillRect(WIDTH / 2 - 48, 36, 96, 16);
      ctx.fillStyle = '#fee761';
      ctx.font = '10px monospace';
      ctx.textAlign = 'center';
      ctx.textBaseline = 'middle';
      ctx.fillText(game.banner.text, WIDTH / 2, 44);
      ctx.restore();
    }
  }
  ```

- [ ] **Step 4: HUD shows prep countdown + Send-Next button during prep**

  In the HUD render block, when `waveState === 'prep'`:
  - Append `'Prep: ' + Math.ceil(game.prepRemaining) + 's'` to the existing readout line.
  - Draw a clickable rect labeled "Send Next ⏎" near the right edge of the HUD (e.g. at `x = PLAYFIELD_W - 80, y = 4, w = 76, h = 16`). Store its bounds on a `ui.sendNextRect` for hit-test.

- [ ] **Step 5: Hit-test Send-Next on mousedown**

  In the existing mousedown handler, before the shop-card hit test, when `game.waveState === 'prep'` and the click falls inside `ui.sendNextRect`:
  ```js
  var bonus = Math.floor(game.prepRemaining / 2);
  game.gold = game.gold + bonus;
  game.prepRemaining = 0;   // updateWaveState picks it up next tick
  return;
  ```

- [ ] **Step 6: Enter key triggers Send-Next**

  Add `keydown` listener at end of IIFE:
  ```js
  window.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
      if (game.waveState === 'prep') {
        var bonus = Math.floor(game.prepRemaining / 2);
        game.gold = game.gold + bonus;
        game.prepRemaining = 0;
      }
    }
  });
  ```

- [ ] **Step 7: Verify**

  Playwright: start game (idle → click → prep). HUD shows "Prep: 30s" countdown. Click Send-Next or press Enter — gold jumps by ~15, wave 1 spawns immediately, banner slides in saying "Wave 1/5". Banner fades after ~2.5s.

- [ ] **Step 8: Commit**

  ```bash
  git commit -am "feat(ant-buster): wave intro banner, prep timer HUD, Send-Next-Wave button (Enter)"
  ```

---

## Task 7: Pause overlay + speed toggle + keyboard shortcuts (Space / 1 / 2)

**Files:**
- Modify: same file. Add `paused` state, `speed` multiplier, pause overlay render, HUD pause icon + speed toggle, Space/1/2 handlers, gate update loop.

- [ ] **Step 1: Add state**

  In `game`:
  ```js
  paused: false,
  speed: 1   // 1 or 2
  ```
  Reset both in `resetGame()`.

- [ ] **Step 2: Apply speed multiplier in `update(dt)`**

  Plan 2 spec §UI-5: speed affects ants and projectiles only; UI animations and prep countdown unaffected. Easiest implementation: pass two `dt` values down.
  ```js
  function update(dt) {
    if (game.paused) { return; }
    if (game.state !== 'playing') { /* existing transitions */ return; }
    game.gameTime = game.gameTime + dt;
    var simDt = dt * game.speed;
    updateWaveState(dt);                  // prep timer uses real dt
    updateAnts(simDt);
    updateProjectiles(simDt);
    updateCannons(simDt);
    checkPickupsAndDeliveries();
    pruneAnts();
  }
  ```

- [ ] **Step 3: HUD widgets (pause icon + speed toggle)**

  Render two small clickable rects in the HUD:
  - Pause/Resume at `x = 4, y = 4, w = 16, h = 16`. Glyph: two vertical bars when running, single triangle when paused.
  - Speed at `x = 24, y = 4, w = 24, h = 16`. Glyph: "1×" or "2×" depending on `game.speed`.

  Track their rects on `ui.pauseRect` and `ui.speedRect`.

- [ ] **Step 4: Mousedown handlers for new HUD widgets**

  Before any other hit-test (since they're in the HUD strip and shouldn't conflict with playfield clicks), test pause + speed rects:
  ```js
  if (pointInRect(p.x, p.y, ui.pauseRect)) { game.paused = !game.paused; return; }
  if (pointInRect(p.x, p.y, ui.speedRect)) {
    if (game.speed === 1) { game.speed = 2; } else { game.speed = 1; }
    return;
  }
  ```
  Add `pointInRect` helper if not present.

- [ ] **Step 5: Keybinds (Space / 1 / 2)**

  Extend the `keydown` listener:
  ```js
  if (e.key === ' ') { e.preventDefault(); game.paused = !game.paused; return; }
  if (e.key === '1') { game.speed = 1; return; }
  if (e.key === '2') { game.speed = 2; return; }
  ```
  No `&&` (each keybind is its own `if`).

- [ ] **Step 6: Pause overlay render**

  After all gameplay layers, before win/lose overlay:
  ```js
  if (game.paused) {
    ctx.fillStyle = 'rgba(0,0,0,0.5)';
    ctx.fillRect(0, 0, WIDTH, HEIGHT);
    ctx.fillStyle = '#fee761';
    ctx.font = '20px monospace';
    ctx.textAlign = 'center';
    ctx.fillText('PAUSED', WIDTH / 2, HEIGHT / 2 - 12);
    ctx.font = '10px monospace';
    ctx.fillText('Space to resume', WIDTH / 2, HEIGHT / 2 + 8);
  }
  ```
  (Spec §UI-4 calls for Resume + Restart Run buttons. Plan 2 ships keyboard-driven and the icon click in HUD; Restart Run can be a button under the PAUSED text. Add a hit-rect labeled "Restart Run" at `WIDTH/2 - 36, HEIGHT/2 + 22, 72, 14`. On click in mousedown, if `game.paused`, call `resetGame()`.)

- [ ] **Step 7: Verify**

  Playwright: pause via Space, observe overlay. Resume. Place a cannon, switch to 2×, watch ants visibly faster. Switch to 1×.
  ```js
  return JSON.stringify({ paused: window.__ab.paused, speed: window.__ab.speed });
  ```

- [ ] **Step 8: Commit**

  ```bash
  git commit -am "feat(ant-buster): pause overlay + 1x/2x speed toggle (Space/1/2 keybinds)"
  ```

---

## Task 8: Click-cannon-to-select + selected-tower panel + Esc/click-outside close + range circle

**Files:**
- Modify: same file. Add `ui.selectedCannon` (reference to cannon object), render tower-detail panel in place of shop card when set, render range circle, click handlers for cannon select / close.

- [ ] **Step 1: Add `ui.selectedCannon` state**

  ```js
  // in `ui`
  selectedCannon: null
  ```
  Reset to `null` in `resetGame()`.

- [ ] **Step 2: Click-cannon-to-select in mousedown handler**

  In the playfield-click branch of mousedown (after Send-Next / pause / speed checks):
  ```js
  // tower select takes priority over drag-to-place
  if (game.state === 'playing') {
    var hit = cannonAt(p.x, p.y);
    if (hit) {
      ui.selectedCannon = hit;
      return;
    }
  }
  // click outside any cannon: deselect
  if (ui.selectedCannon) {
    // only if click landed in playfield, not on a panel button (panel buttons handled below)
    if (p.x < PLAYFIELD_W) {
      ui.selectedCannon = null;
    }
  }
  // existing drag-to-place + shop-card logic continues
  ```

- [ ] **Step 3: Esc closes selection**

  Add `if (e.key === 'Escape') { ui.selectedCannon = null; return; }` to the keydown listener.

- [ ] **Step 4: Render range circle when selected (z-order layer 5)**

  In `render()`, after fallen-slice layer (which lands in Task 23) and before cannon bases:
  ```js
  if (ui.selectedCannon) {
    var sk = CANNON_KINDS[ui.selectedCannon.kind];
    ctx.strokeStyle = 'rgba(255, 231, 97, 0.5)';
    ctx.lineWidth = 1;
    ctx.beginPath();
    ctx.arc(ui.selectedCannon.x, ui.selectedCannon.y, sk.range, 0, Math.PI * 2);
    ctx.stroke();
  }
  ```

- [ ] **Step 5: Render tower-detail panel in place of shop card**

  In the right-column render block (currently the shop card area), branch:
  ```js
  if (ui.selectedCannon) {
    drawTowerPanel(ui.selectedCannon);
  } else {
    drawShop();
  }
  ```
  Extract Plan 1's shop-card draw into `drawShop()`. Implement `drawTowerPanel(c)`:
  ```js
  function drawTowerPanel(c) {
    var sk = CANNON_KINDS[c.kind];
    var px = PLAYFIELD_W + 8;
    var py = 32;
    var pw = SHOP_W - 16;
    ctx.fillStyle = '#262b44';
    ctx.fillRect(px, py, pw, 200);
    ctx.fillStyle = '#fee761';
    ctx.font = '10px monospace';
    ctx.textAlign = 'left';
    ctx.fillText(sk.name, px + 6, py + 14);
    ctx.fillStyle = '#cccccc';
    ctx.fillText('DMG ' + sk.damage, px + 6, py + 30);
    ctx.fillText('RATE ' + sk.fireRate + '/s', px + 6, py + 42);
    ctx.fillText('RANGE ' + sk.range, px + 6, py + 54);
    ctx.fillText('KILLS ' + c.kills, px + 6, py + 66);
    // Upgrade + sell buttons land in Task 9
  }
  ```

- [ ] **Step 6: Verify**

  Playwright: place a Howitzer, click it. Right panel shows Howitzer stats. Range circle drawn around the cannon. Click outside (in playfield) — panel reverts to shop. Esc also closes.
  ```js
  return JSON.stringify({ hasSelected: !!window.__ab.ui ? !!window.__ab.ui.selectedCannon : 'no ui' });
  ```
  (`window.__ab` is `game`; UI lives in a separate closure variable. To inspect, expose `window.__abUi = ui;` near the existing `window.__ab` line.)

  - [ ] Add `window.__abUi = ui;` next to `window.__ab = game;` so Playwright can read selection state.

- [ ] **Step 7: Commit**

  ```bash
  git commit -am "feat(ant-buster): selected-tower panel + range circle + Esc/click-outside close"
  ```

---

## Task 9: Upgrade buttons + sell button (kindHistory math + 70% refund)

**Files:**
- Modify: same file. Extend `drawTowerPanel` with up-to-2 upgrade button rects + sell rect. Hit-test in mousedown. Implement `upgradeCannon(c, toKind)` and `sellCannon(c)`.

- [ ] **Step 1: Extend `drawTowerPanel` with buttons**

  ```js
  function drawTowerPanel(c) {
    // ... existing stats render ...
    var sk = CANNON_KINDS[c.kind];
    // upgrade buttons (0-2)
    var upgrades = sk.upgradesTo || [];
    for (var i = 0; i < upgrades.length; i++) {
      var uk = upgrades[i];
      var udef = CANNON_KINDS[uk];
      var by = py + 90 + i * 22;
      var canAfford = game.gold >= udef.cost;
      ctx.fillStyle = canAfford ? '#3a8c3a' : '#444';
      ctx.fillRect(px + 6, by, pw - 12, 18);
      ctx.fillStyle = canAfford ? '#fee761' : '#888';
      ctx.fillText('→ ' + udef.name + ' (' + udef.cost + 'g)', px + 10, by + 12);
      ui.upgradeRects[i] = { x: px + 6, y: by, w: pw - 12, h: 18, toKind: uk };
    }
    for (var k = upgrades.length; k < 2; k++) { ui.upgradeRects[k] = null; }
    // sell button — refund 70% of historical spend
    var refund = sellRefund(c);
    var sy = py + 200 - 26;
    ctx.fillStyle = '#a44';
    ctx.fillRect(px + 6, sy, pw - 12, 18);
    ctx.fillStyle = '#fee761';
    ctx.fillText('Sell (+' + refund + 'g)', px + 10, sy + 12);
    ui.sellRect = { x: px + 6, y: sy, w: pw - 12, h: 18 };
  }
  ```

- [ ] **Step 2: Add `sellRefund` and `upgradeCannon` and `sellCannon`**

  ```js
  function sellRefund(c) {
    var total = 0;
    for (var i = 0; i < c.kindHistory.length; i++) {
      total = total + CANNON_KINDS[c.kindHistory[i]].cost;
    }
    // current kind's cost is already in kindHistory because we push *current* before swapping
    return Math.floor(0.7 * total);
  }
  function upgradeCannon(c, toKind) {
    var udef = CANNON_KINDS[toKind];
    if (game.gold < udef.cost) { return; }
    game.gold = game.gold - udef.cost;
    c.kind = toKind;
    c.kindHistory.push(toKind);
  }
  function sellCannon(c) {
    var refund = sellRefund(c);
    game.gold = game.gold + refund;
    var idx = game.cannons.indexOf(c);
    if (idx >= 0) { game.cannons.splice(idx, 1); }
    ui.selectedCannon = null;
  }
  ```

  *Note on `kindHistory` semantics — divergence from spec formula:* spec SYS-2 has two clauses that conflict if read together literally. It says (a) `kindHistory` includes the base kind at construction, and (b) sell math is `floor(0.7 * (sum(CANNON_KINDS[k].cost for k in kindHistory) + CANNON_KINDS[currentKind].cost))`. Together those double-count the base (a fresh Howitzer would refund 70g for a 50g purchase). The intended behavior is clear from context: refund 70% of total spend. Plan 2 implements the intent: `kindHistory` is the canonical "every kind owned, in order" list (Task 2 sets `kindHistory: [kindKey]` at construction; Task 9 pushes the new kind on upgrade), and `sellRefund` sums history only — no separate current term. Resulting refunds: fresh Howitzer = 35g, Howitzer→Siege = 70g, Howitzer→Siege→Railgun = 133g, all match the 70% rule.

- [ ] **Step 3: Hit-test in mousedown**

  Inside the playfield-click block, when `ui.selectedCannon` is set and click is in the right-column panel area (`p.x >= PLAYFIELD_W`):
  ```js
  if (ui.selectedCannon) {
    if (p.x >= PLAYFIELD_W) {
      for (var i = 0; i < 2; i++) {
        var r = ui.upgradeRects[i];
        if (r) {
          if (pointInRect(p.x, p.y, r)) {
            upgradeCannon(ui.selectedCannon, r.toKind);
            return;
          }
        }
      }
      if (ui.sellRect) {
        if (pointInRect(p.x, p.y, ui.sellRect)) {
          sellCannon(ui.selectedCannon);
          return;
        }
      }
    }
  }
  ```

- [ ] **Step 4: Initialize new `ui` fields**

  In the `ui` object: `upgradeRects: [null, null], sellRect: null`. Reset in `resetGame()`.

- [ ] **Step 5: Verify**

  Playwright: place a Howitzer (50g spent, 100 left). Click it. Panel shows. Sell button reads "Sell (+35g)" (70% of 50). Click sell — cannon vanishes, gold goes to 135. Place another Howitzer. (Real upgrade test waits for Task 11's Siege.)

- [ ] **Step 6: Commit**

  ```bash
  git commit -am "feat(ant-buster): upgrade + sell buttons with 70% refund across kindHistory"
  ```

---

## Task 10: Add Repeater + Twin Gun base cannons (3 shop cards visible)

**Files:**
- Modify: same file. Add 2 entries to `CANNON_KINDS`. Add 2 turret sprite makers. Extend `drawShop` to render 3 cards. Update placement / drag flow to track which kind is being dragged.

- [ ] **Step 1: Add Repeater + Twin Gun kind rows**

  ```js
  CANNON_KINDS.repeater = {
    name: 'Repeater', cost: 50, damage: 1, fireRate: 4, range: 130, projectileSpeed: 280,
    color: '#5a7d4a', family: 'quick', tier: 1, upgradesTo: []  // 'autocannon' added in Task 12
  };
  CANNON_KINDS.twingun = {
    name: 'Twin Gun', cost: 75, damage: 2, fireRate: 1, range: 100, projectileSpeed: 240,
    color: '#7d4a7d', family: 'status', tier: 1, barrels: 2, upgradesTo: []  // 'frost' added in Task 13
  };
  ```

- [ ] **Step 2: Sprite makers for the new turrets**

  Add `makeCannonTurretRepeater()` and `makeCannonTurretTwinGun()` mirroring `makeCannonTurret()`'s shape but with the kind-specific `color`. For Twin Gun, draw two short barrels offset on the y-axis.

  Cache them on `game.sprites.turrets = { howitzer: …, repeater: …, twingun: … }`. Cannon render reads `game.sprites.turrets[c.kind]`.

- [ ] **Step 3: `drawShop` renders 3 cards stacked**

  Replace the single-card render with a loop over `['howitzer', 'repeater', 'twingun']`:
  ```js
  function drawShop() {
    var cardX = PLAYFIELD_W + 8;
    var startY = 32;
    var cardH = 70;
    var keys = ['howitzer', 'repeater', 'twingun'];
    ui.shopRects = [];
    for (var i = 0; i < keys.length; i++) {
      var k = keys[i];
      var sk = CANNON_KINDS[k];
      var cy = startY + i * (cardH + 8);
      // … draw card background, name, cost, mini turret icon …
      ui.shopRects.push({ x: cardX, y: cy, w: SHOP_W - 16, h: cardH, kind: k });
    }
  }
  ```

- [ ] **Step 4: Update drag-to-place to use selected kind**

  In `ui`: `dragging: null` (was a boolean — now stores the kind string). Mousedown on a shop card sets `ui.dragging = card.kind`. Existing drag preview uses `CANNON_KINDS[ui.dragging]` for the range circle. Drop calls `placeCannon(ui.dragging, x, y)` if `canPlaceCannon`.

  `canPlaceCannon` becomes `canPlaceCannon(x, y, kindKey)` — uses `CANNON_KINDS[kindKey].cost` for the gold check.

- [ ] **Step 5: Verify**

  Playwright: shop shows three cards. Drag each into the playfield, place. `window.__ab.cannons` has three entries with distinct `kind` values.

- [ ] **Step 6: Commit**

  ```bash
  git commit -am "feat(ant-buster): add Repeater and Twin Gun base cannons (3 shop cards)"
  ```

---

## Task 11: Add Siege + Railgun (Heavy upgrade path)

**Files:**
- Modify: same file. 2 new `CANNON_KINDS` rows. Wire `howitzer.upgradesTo = ['siege']`, `siege.upgradesTo = ['railgun']`. Sprite makers.

- [ ] **Step 1: Add Siege and Railgun**

  ```js
  CANNON_KINDS.siege = { name: 'Siege', cost: 50, damage: 12, fireRate: 0.6, range: 110, projectileSpeed: 240, color: '#5a3a44', family: 'heavy', tier: 2, upgradesTo: ['railgun'] };
  CANNON_KINDS.railgun = { name: 'Railgun', cost: 90, damage: 25, fireRate: 0.6, range: 120, projectileSpeed: 360, color: '#6f3a44', family: 'heavy', tier: 3, pierce: true, upgradesTo: [] };
  ```

  Set `CANNON_KINDS.howitzer.upgradesTo = ['siege'];` and `CANNON_KINDS.siege.upgradesTo = ['railgun'];`.

- [ ] **Step 2: Sprite makers + register in `game.sprites.turrets`**

- [ ] **Step 3: Pierce flag honored in `updateProjectiles` damage check**

  Tank's pierce-bypass land in Task 17. For Plan 2, projectile.pierce is set from `kind.pierce` at `fireProjectile` time and passed into damage application. Add the flag now so Task 17 doesn't have to revisit Railgun.

  In `fireProjectile`:
  ```js
  var def = CANNON_KINDS[cannon.kind];
  // …
  game.projectiles.push({ x: x0, y: y0, heading: heading, speed: def.projectileSpeed, damage: def.damage, pierce: !!def.pierce, alive: true });
  ```

- [ ] **Step 4: Verify**

  Playwright: place Howitzer, sell some gold, upgrade to Siege (panel shows "→ Siege (50g)"), then Railgun. Stats panel reflects new numbers. Projectiles fire faster / harder.

- [ ] **Step 5: Commit**

  ```bash
  git commit -am "feat(ant-buster): Heavy path Siege+Railgun upgrades"
  ```

---

## Task 12: Add Auto-Cannon + Vulcan (Quick upgrade path with spool-up)

**Files:**
- Modify: same file. 2 new rows. Spool-up logic on Vulcan: tracks `c.spoolT` that ramps from 0 to 1 over 0.5s while firing, multiplies effective fire rate.

- [ ] **Step 1: Add Auto-Cannon and Vulcan**

  ```js
  CANNON_KINDS.autocannon = { name: 'Auto-Cannon', cost: 50, damage: 2, fireRate: 6, range: 130, projectileSpeed: 320, color: '#4a7d6a', family: 'quick', tier: 2, upgradesTo: ['vulcan'] };
  CANNON_KINDS.vulcan = { name: 'Vulcan', cost: 80, damage: 3, fireRate: 9, range: 140, projectileSpeed: 360, color: '#3a8c8c', family: 'quick', tier: 3, spoolUp: 0.5, upgradesTo: [] };
  ```

  Wire `repeater.upgradesTo = ['autocannon']`, `autocannon.upgradesTo = ['vulcan']`.

- [ ] **Step 2: Spool-up logic in `updateCannons`**

  ```js
  function updateCannons(dt) {
    for (var i = 0; i < game.cannons.length; i++) {
      var c = game.cannons[i];
      if (c.disabled) {
        if (game.gameTime >= c.disabledUntil) { c.disabled = false; }
      }
      if (c.disabled) { continue; }
      var def = CANNON_KINDS[c.kind];
      var target = findTarget(c);
      if (!target) {
        // spool-down for vulcan: drop to 0 over same window
        if (def.spoolUp) {
          c.spoolT = (c.spoolT || 0) - dt / def.spoolUp;
          if (c.spoolT < 0) { c.spoolT = 0; }
        }
        continue;
      }
      var effRate = def.fireRate;
      if (def.spoolUp) {
        c.spoolT = (c.spoolT || 0) + dt / def.spoolUp;
        if (c.spoolT > 1) { c.spoolT = 1; }
        // ramp from 30% rate to 100% rate
        effRate = def.fireRate * (0.3 + 0.7 * c.spoolT);
      }
      c.cooldown = c.cooldown - dt;
      if (c.cooldown <= 0) {
        fireProjectile(c, target);
        c.cooldown = c.cooldown + 1 / effRate;
      }
    }
  }
  ```

- [ ] **Step 3: Verify**

  Playwright: build Repeater→Auto-Cannon→Vulcan. Watch Vulcan ramp visibly (slower first half-second of firing, then full clip).

- [ ] **Step 4: Commit**

  ```bash
  git commit -am "feat(ant-buster): Quick path Auto-Cannon + Vulcan with spool-up"
  ```

---

## Task 13: Twin Gun multi-barrel volley + Frost slow emission (first status emitter)

**Files:**
- Modify: same file. Twin Gun fires `barrels` projectiles per shot with small angular spread. Frost projectile applies `slow` on hit.

- [ ] **Step 1: Add Frost row**

  ```js
  CANNON_KINDS.frost = {
    name: 'Frost', cost: 75, damage: 1, fireRate: 1.5, range: 100, projectileSpeed: 220,
    color: '#7da4f7', family: 'status', tier: 2,
    statusOnHit: { kind: 'slow', magnitude: 0.3, duration: 2 },
    upgradesTo: ['glacier']  // glacier added in Task 14
  };
  ```
  Wire `twingun.upgradesTo = ['frost'];`.

- [ ] **Step 2: Twin Gun `barrels: 2` honored in `fireProjectile`**

  ```js
  function fireProjectile(cannon, target) {
    var def = CANNON_KINDS[cannon.kind];
    var heading = angleTowards(cannon.x, cannon.y, target.x, target.y);
    var n = def.barrels || 1;
    var spread = 0.06;  // ~3.4°
    for (var i = 0; i < n; i++) {
      var off = (n === 1) ? 0 : (i - (n - 1) / 2) * spread;
      var h = heading + off;
      game.projectiles.push({
        x: cannon.x + Math.cos(h) * 12,
        y: cannon.y + Math.sin(h) * 12,
        heading: h,
        speed: def.projectileSpeed,
        damage: def.damage,
        pierce: !!def.pierce,
        statusOnHit: def.statusOnHit || null,
        alive: true
      });
    }
  }
  ```

- [ ] **Step 3: `updateProjectiles` applies status on hit**

  When a projectile collides with an ant, after applying damage:
  ```js
  if (p.statusOnHit) {
    var s = p.statusOnHit;
    applyStatus(a, s.kind, s.magnitude, s.duration, 'projectile');
  }
  ```

- [ ] **Step 4: Verify**

  Playwright: place Twin Gun, watch it fire two pellets per shot. Upgrade to Frost. Watch ants slow visibly when hit. `window.__ab.ants[i].effects.slow` populated:
  ```js
  var hasSlow = false;
  for (var i = 0; i < window.__ab.ants.length; i++) {
    if (window.__ab.ants[i].effects.slow) { hasSlow = true; break; }
  }
  return hasSlow;
  ```

- [ ] **Step 5: Commit**

  ```bash
  git commit -am "feat(ant-buster): Twin Gun multi-barrel + Frost slow emission"
  ```

---

## Task 14: Add Glacier (continuous beam, slow refresh, 1s linger)

**Files:**
- Modify: same file. Glacier fires no projectiles — instead, while it has a target each tick, it applies fresh slow to that target and renders a beam line.

- [ ] **Step 1: Add Glacier row**

  ```js
  CANNON_KINDS.glacier = {
    name: 'Glacier', cost: 90, damage: 1, fireRate: 0, range: 130,
    color: '#a4d4ff', family: 'status', tier: 3,
    beam: true,
    beamDamagePerSec: 1,    // applied per-tick as damage*dt
    statusOnBeam: { kind: 'slow', magnitude: 0.5, duration: 1 },  // refresh; outlasts beam by 1s when disconnected
    upgradesTo: []
  };
  ```
  Wire `frost.upgradesTo = ['glacier'];`.

- [ ] **Step 2: Beam handler in `updateCannons`**

  Branch on `def.beam`:
  ```js
  if (def.beam) {
    var target = findTarget(c);
    if (!target) {
      c.beamTargetId = null;
      continue;
    }
    // damage tick
    target.hp = target.hp - def.beamDamagePerSec * dt;
    if (target.hp <= 0) {
      target.dead = true;
      target.deathTime = 0.5;
      game.gold = game.gold + ANT_KINDS[target.kind].gold;
      c.kills = c.kills + 1;
    }
    // status refresh — duration 1s, beam re-applies every tick so it never expires while held
    if (def.statusOnBeam) {
      var s = def.statusOnBeam;
      applyStatus(target, s.kind, s.magnitude, s.duration, 'beam');
    }
    c.beamTargetId = target;  // store reference for render
    continue;
  }
  ```

  *Refresh semantics:* `applyStatus`'s longer-duration rule means each tick pushes `expiresAt` forward by ~`gameTime + 1s`. The moment the beam disconnects, the slow naturally lingers ~1s. Spec §2.3 met.

- [ ] **Step 3: Render beam (z-order layer 9)**

  ```js
  for (var ci = 0; ci < game.cannons.length; ci++) {
    var c = game.cannons[ci];
    if (CANNON_KINDS[c.kind].beam) {
      if (c.beamTargetId && !c.beamTargetId.dead) {
        ctx.strokeStyle = '#a4d4ff';
        ctx.lineWidth = 2;
        ctx.beginPath();
        ctx.moveTo(c.x, c.y);
        ctx.lineTo(c.beamTargetId.x, c.beamTargetId.y);
        ctx.stroke();
      }
    }
  }
  ```

- [ ] **Step 4: Verify**

  Playwright: build Twin Gun → Frost → Glacier. Watch the beam line connect to nearest ant. Ant moves at 50% speed. When beam disconnects (target dies or moves out of range), check that `effects.slow` lingers ~1 second then drops:
  ```js
  // sample at t=0
  var idx = window.__ab.ants.findIndex(function(a){return a.effects.slow;});
  return idx >= 0 ? window.__ab.ants[idx].effects.slow : null;
  ```

- [ ] **Step 5: Commit**

  ```bash
  git commit -am "feat(ant-buster): Glacier beam cannon with refresh-and-linger slow"
  ```

---

## Task 15: Add Scout ant

**Files:**
- Modify: same file. New `ANT_KINDS.scout`. New `makeScout` sprite. Wave references it (real use lands in Task 22).

- [ ] **Step 1: Add Scout**

  ```js
  ANT_KINDS.scout = {
    name: 'Scout', hp: 6, speed: 60, gold: 3,
    alignment: 0.4, jitterRate: 5.0,
    wanderFreqMin: 0.4, wanderFreqMax: 0.9,
    wanderAmpMin: 1.0, wanderAmpMax: 1.7,
    spriteKey: 'scout', spriteW: 12, spriteH: 12,
    slowResist: 0, damageMult: 1, pierceMult: 1, canPickup: true, onDeath: null, armor: 0
  };
  ```

- [ ] **Step 2: `makeScout` sprite**

  Smaller (12×12), brighter color (e.g. `#fee761`), thinner body to read as fast.

  Update sprite resolution in render to read `ANT_KINDS[a.kind].spriteKey` and look up `game.sprites[key]`.

- [ ] **Step 3: Verify**

  In Playwright with `?debug=1` (Task 26 lands the hotkey, but for now we can use `browser_evaluate` to call `spawnAnt('scout')`). Scout visibly faster + jitterier than Worker.

- [ ] **Step 4: Commit**

  ```bash
  git commit -am "feat(ant-buster): Scout ant kind"
  ```

---

## Task 16: Add Soldier (slow-resist + non-pierce damage modifier)

**Files:**
- Modify: same file. Add Soldier row with `slowResist: 0.5` and `armor: 0.5`. Wire damage pipeline to multiply non-pierce damage by `(1 - armor)`.

- [ ] **Step 1: Add Soldier**

  ```js
  ANT_KINDS.soldier = {
    name: 'Soldier', hp: 40, speed: 20, gold: 5,
    alignment: 0.2, jitterRate: 3.0,
    wanderFreqMin: 0.2, wanderFreqMax: 0.5,
    wanderAmpMin: 0.6, wanderAmpMax: 1.0,
    spriteKey: 'soldier', spriteW: 16, spriteH: 16,
    slowResist: 0.5,    // applyStatus halves slow magnitude
    damageMult: 1, pierceMult: 1, canPickup: true, onDeath: null,
    armor: 0.5          // non-pierce damage halved
  };
  ```

- [ ] **Step 2: Apply armor in damage application**

  In `updateProjectiles` (and Glacier beam path):
  ```js
  function applyDamage(ant, amount, pierce) {
    var k = ANT_KINDS[ant.kind];
    var dmg = amount;
    if (!pierce) {
      dmg = dmg * (1 - (k.armor || 0));
    }
    ant.hp = ant.hp - dmg;
  }
  ```
  Replace direct `a.hp = a.hp - p.damage` with `applyDamage(a, p.damage, p.pierce)`. Glacier beam: `applyDamage(target, def.beamDamagePerSec * dt, false)` (Glacier is not pierce).

- [ ] **Step 3: Soldier sprite**

  `makeSoldier` — chunkier silhouette, darker color (e.g. `#3a4466`), maybe a small helmet pixel.

- [ ] **Step 4: Verify**

  Playwright spawn Soldier, hit with Howitzer (5 dmg). HP drops by 2.5 not 5. Hit with Railgun (pierce). HP drops by 25 (full damage). Apply slow via Frost: `effects.slow.magnitude` is 0.15, not 0.30.

- [ ] **Step 5: Commit**

  ```bash
  git commit -am "feat(ant-buster): Soldier ant with armor + slow-resist"
  ```

---

## Task 17: Add Tank (pierce-bypass via ant flag)

**Files:**
- Modify: same file. Tank row with `armor: 0.5` and `spriteW/H: 24`. Pierce bypass already works via Task 16's `applyDamage`.

- [ ] **Step 1: Add Tank**

  ```js
  ANT_KINDS.tank = {
    name: 'Tank', hp: 150, speed: 12, gold: 10,
    alignment: 0.15, jitterRate: 2.0,
    wanderFreqMin: 0.15, wanderFreqMax: 0.35,
    wanderAmpMin: 0.4, wanderAmpMax: 0.7,
    spriteKey: 'tank', spriteW: 24, spriteH: 24,
    slowResist: 0, damageMult: 1, pierceMult: 1, canPickup: false, onDeath: null,
    armor: 0.5
  };
  ```
  `canPickup: false` — Tanks don't steal cake (heavy bruiser archetype).

- [ ] **Step 2: `makeTank` sprite (24×24)**

- [ ] **Step 3: `checkPickupsAndDeliveries` honors `canPickup`**

  Skip the pickup branch for ants whose kind has `canPickup === false`.

- [ ] **Step 4: Verify**

  Playwright spawn Tank, watch it walk past cake without picking up. Hit with Howitzer (5 dmg) — HP drops 2.5. Railgun pierce — HP drops 25.

- [ ] **Step 5: Commit**

  ```bash
  git commit -am "feat(ant-buster): Tank ant (heavy armor, pierce-vulnerable, no cake pickup)"
  ```

---

## Task 18: Add Splitter + Mini-Splitter (`onDeath` spawn hook)

**Files:**
- Modify: same file. 2 ant rows. Generic `onDeath` hook resolved in `pruneAnts` before splice.

- [ ] **Step 1: Add Splitter and Mini-Splitter**

  ```js
  ANT_KINDS.splitter = {
    name: 'Splitter', hp: 25, speed: 25, gold: 4,
    alignment: 0.25, jitterRate: 4.0,
    wanderFreqMin: 0.3, wanderFreqMax: 0.6, wanderAmpMin: 0.8, wanderAmpMax: 1.3,
    spriteKey: 'splitter', spriteW: 16, spriteH: 16,
    slowResist: 0, damageMult: 1, pierceMult: 1, canPickup: true,
    onDeath: function(ant) {
      for (var i = 0; i < 2; i++) {
        var ang = Math.random() * Math.PI * 2;
        spawnAntAt('mini_splitter', ant.x + Math.cos(ang) * 6, ant.y + Math.sin(ang) * 6);
      }
    },
    armor: 0
  };
  ANT_KINDS.mini_splitter = {
    name: 'Mini-Splitter', hp: 8, speed: 35, gold: 1,
    alignment: 0.35, jitterRate: 5.0,
    wanderFreqMin: 0.4, wanderFreqMax: 0.8, wanderAmpMin: 1.0, wanderAmpMax: 1.5,
    spriteKey: 'mini_splitter', spriteW: 8, spriteH: 8,
    slowResist: 0, damageMult: 1, pierceMult: 1, canPickup: true, onDeath: null, armor: 0
  };
  ```

- [ ] **Step 2: Add `spawnAntAt(kindKey, x, y)`**

  Same as `spawnAnt` but uses the supplied `x, y` instead of the anthill location. `spawnAnt` becomes a wrapper that calls `spawnAntAt(kindKey, anthillX + jitter, anthillY + jitter)`.

- [ ] **Step 3: Fire `onDeath` in `pruneAnts`**

  Before splicing dead ants out:
  ```js
  function pruneAnts() {
    var kept = [];
    for (var i = 0; i < game.ants.length; i++) {
      var a = game.ants[i];
      // dead-and-faded: drop. Plan 1 logic kept; deathTime ticks down.
      if (a.dead) {
        if (a.deathTime > 0) {
          a.deathTime = a.deathTime - FIXED_DT;
        }
        if (a.deathTime <= 0) {
          // fire onDeath only if killed (not delivered)
          if (a.deliveredAt === 0) {
            var k = ANT_KINDS[a.kind];
            if (k.onDeath) { k.onDeath(a); }
          }
          continue;  // drop the ant
        }
      }
      kept.push(a);
    }
    game.ants = kept;
  }
  ```
  Plan 1 had `pruneAnts` differently — adjust the gating to whatever Plan 1 already does, the contract is "fire onDeath once, only when the ant was killed (not delivered)".

- [ ] **Step 4: Sprites**

  `makeSplitter` (16×16, fissure-line down the middle), `makeMiniSplitter` (8×8, crumb-sized).

- [ ] **Step 5: Verify**

  Playwright spawn Splitter, kill it, watch 2 mini-Splitters spawn. Mini-Splitters deal damage to cake on contact (canPickup true).

- [ ] **Step 6: Commit**

  ```bash
  git commit -am "feat(ant-buster): Splitter + Mini-Splitter with onDeath spawn hook"
  ```

---

## Task 19: Add Phase ant (intangibility window)

**Files:**
- Modify: same file. Phase ant cycles intangibility every 3s for 1s. `findTarget` skips intangible ants. Projectile collisions skip intangible ants. Render at 30% alpha during the window.

- [ ] **Step 1: Add Phase**

  ```js
  ANT_KINDS.phase = {
    name: 'Phase', hp: 20, speed: 35, gold: 6,
    alignment: 0.3, jitterRate: 4.0,
    wanderFreqMin: 0.3, wanderFreqMax: 0.7, wanderAmpMin: 0.9, wanderAmpMax: 1.4,
    spriteKey: 'phase', spriteW: 16, spriteH: 16,
    slowResist: 0, damageMult: 1, pierceMult: 1, canPickup: true, onDeath: null,
    armor: 0,
    phaseInterval: 3, phaseDuration: 1
  };
  ```

- [ ] **Step 2: Track intangibility on the ant**

  `spawnAntAt` sets `a.intangible = false; a.phaseT = 0;`.

  In `updateAnt`, if the ant's kind has `phaseInterval`:
  ```js
  if (k.phaseInterval) {
    a.phaseT = (a.phaseT || 0) + dt;
    var cycle = k.phaseInterval + k.phaseDuration;
    var t = a.phaseT % cycle;
    a.intangible = (t >= k.phaseInterval);
  }
  ```

- [ ] **Step 3: `findTarget` skips intangible**

  ```js
  if (a.dead) { continue; }
  if (a.intangible) { continue; }
  ```

- [ ] **Step 4: Projectile collisions skip intangible**

  In `updateProjectiles` collision loop, also `if (a.intangible) { continue; }`.

- [ ] **Step 5: Render at 30% alpha**

  In ant render loop:
  ```js
  if (a.intangible) {
    ctx.save();
    ctx.globalAlpha = 0.3;
    // draw sprite
    ctx.restore();
  } else {
    // normal draw
  }
  ```

- [ ] **Step 6: Verify**

  Playwright spawn Phase, watch it flicker translucent every 3 seconds. Cannons skip it during the window.
  ```js
  return window.__ab.ants[0].intangible;
  ```

- [ ] **Step 7: Commit**

  ```bash
  git commit -am "feat(ant-buster): Phase ant with 3s/1s intangibility cycle"
  ```

---

## Task 20: Add Bomber (`onDeath` AoE-disable cannons)

**Files:**
- Modify: same file. Bomber row with onDeath that disables nearby cannons. Disabled-cannon render adds a sparks overlay.

- [ ] **Step 1: Add Bomber**

  ```js
  ANT_KINDS.bomber = {
    name: 'Bomber', hp: 30, speed: 40, gold: 7,
    alignment: 0.3, jitterRate: 4.5,
    wanderFreqMin: 0.4, wanderFreqMax: 0.8, wanderAmpMin: 1.0, wanderAmpMax: 1.5,
    spriteKey: 'bomber', spriteW: 16, spriteH: 16,
    slowResist: 0, damageMult: 1, pierceMult: 1, canPickup: true, armor: 0,
    onDeath: function(ant) {
      var radius = 60;
      for (var i = 0; i < game.cannons.length; i++) {
        var c = game.cannons[i];
        var dx = c.x - ant.x;
        var dy = c.y - ant.y;
        if (dx * dx + dy * dy <= radius * radius) {
          c.disabled = true;
          c.disabledUntil = game.gameTime + 5;
        }
      }
      game.effects.push({ kind: 'bomber_aoe', x: ant.x, y: ant.y, t: 0, life: 0.6 });
    }
  };
  ```

- [ ] **Step 2: Add `game.effects` array (if not yet present from earlier tasks)**

  In `game` initial state and `resetGame()`: `effects: []`. Tick effects in `update`:
  ```js
  function updateEffects(dt) {
    var kept = [];
    for (var i = 0; i < game.effects.length; i++) {
      game.effects[i].t = game.effects[i].t + dt;
      if (game.effects[i].t < game.effects[i].life) { kept.push(game.effects[i]); }
    }
    game.effects = kept;
  }
  ```
  Call from `update`.

- [ ] **Step 3: Render bomber AoE flash + cannon sparks overlay**

  Effects layer (z-order layer 10):
  ```js
  for (var i = 0; i < game.effects.length; i++) {
    var ef = game.effects[i];
    if (ef.kind === 'bomber_aoe') {
      var p = ef.t / ef.life;
      ctx.strokeStyle = 'rgba(255,80,40,' + (1 - p) + ')';
      ctx.lineWidth = 2;
      ctx.beginPath();
      ctx.arc(ef.x, ef.y, 60 * p, 0, Math.PI * 2);
      ctx.stroke();
    }
  }
  ```
  Cannon render — when `c.disabled`:
  ```js
  if (c.disabled) {
    ctx.fillStyle = '#ff5040';
    ctx.fillRect(c.x - 1, c.y - 12, 2, 2);  // single spark for now
  }
  ```

- [ ] **Step 4: `updateCannons` skips disabled cannons (already added in Task 12)**

- [ ] **Step 5: Verify**

  Playwright spawn Bomber near a placed cannon, kill the Bomber, watch AoE flash. Cannon stops firing for 5s. After 5s `c.disabled === false` and firing resumes.

- [ ] **Step 6: Commit**

  ```bash
  git commit -am "feat(ant-buster): Bomber ant with onDeath AoE-disable cannons"
  ```

---

## Task 21: Add Queen (mini at HP 400 for wave 5; onDeath spawn 6 Workers)

**Files:**
- Modify: same file. Queen row.

- [ ] **Step 1: Add Queen**

  ```js
  ANT_KINDS.queen = {
    name: 'Queen', hp: 400, speed: 15, gold: 50,    // wave-5 mini variant; full Queen lands in Plan 3
    alignment: 0.15, jitterRate: 1.5,
    wanderFreqMin: 0.1, wanderFreqMax: 0.3, wanderAmpMin: 0.3, wanderAmpMax: 0.5,
    spriteKey: 'queen', spriteW: 24, spriteH: 24,
    slowResist: 0.25, damageMult: 1, pierceMult: 1, canPickup: true, armor: 0.3,
    onDeath: function(ant) {
      for (var i = 0; i < 6; i++) {
        var ang = (Math.PI * 2 / 6) * i;
        spawnAntAt('worker', ant.x + Math.cos(ang) * 12, ant.y + Math.sin(ang) * 12);
      }
    }
  };
  ```

- [ ] **Step 2: `makeQueen` sprite (24×24, crown silhouette)**

- [ ] **Step 3: Verify**

  Playwright: spawn Queen via debug, kill it, watch 6 Workers spawn around the death point.

- [ ] **Step 4: Commit**

  ```bash
  git commit -am "feat(ant-buster): Queen ant (mini variant for wave 5) with worker-spawn on death"
  ```

---

## Task 22: Waves 2-5 data

**Files:**
- Modify: same file. Append to `WAVES`.

- [ ] **Step 1: Append wave rows**

  ```js
  WAVES.push({
    number: 2, prep: 20, bonus: 30,
    spawns: [{ type: 'worker', count: 12, interval: 0.8, delay: 0 }]
  });
  WAVES.push({
    number: 3, prep: 20, bonus: 35,
    spawns: [
      { type: 'worker', count: 6, interval: 0.7, delay: 0 },
      { type: 'worker', count: 6, interval: 0.7, delay: 4 }
    ]
  });
  WAVES.push({
    number: 4, prep: 20, bonus: 45,
    spawns: [
      { type: 'worker', count: 10, interval: 0.7, delay: 0 },
      { type: 'scout', count: 4, interval: 1.0, delay: 1.5 }
    ]
  });
  WAVES.push({
    number: 5, prep: 30, bonus: 75, boss: true,
    spawns: [
      { type: 'queen', count: 1, interval: 1, delay: 0 },
      { type: 'worker', count: 8, interval: 0.8, delay: 2 }
    ]
  });
  ```

  *Note on wave 3 sub-group spawning:* each entry's `delay` is "seconds after the previous group started spawning". The state machine in Task 5 reads `delay` once, when transitioning to that group. Wave 3 = 6 workers, then 4-second gap, then 6 workers — set `delay: 4` on the second group. Confirm against Task 5's spawner: `nextSpawnAt = game.gameTime + grp.delay` is set on group transition only.

- [ ] **Step 2: Verify**

  Playwright: speed-run all 5 waves at 2× with three Howitzers placed. Win screen on wave 5 completion. Boss banner on wave 5.

- [ ] **Step 3: Commit**

  ```bash
  git commit -am "feat(ant-buster): waves 2-5 with sub-groups + boss flag on wave 5"
  ```

---

## Task 23: Fallen-slice entity + Carrier-drop-on-kill rewrite + ground pickup

**Files:**
- Modify: same file. New `slices: []` array on `game`. Modify projectile/beam kill path on Carriers carrying. Add `updateSlices(dt)`. Add ground-pickup branch in `checkPickupsAndDeliveries`. Render slice sprite. 8s timeout returns slice to cake without life deduction.

- [ ] **Step 1: Add `game.slices`**

  In `game` initial state and `resetGame()`: `slices: []`.

- [ ] **Step 2: When a carrying ant dies, drop a slice**

  In the kill path (projectile collision and beam damage), when an ant's `hp <= 0`:
  ```js
  if (a.carrying) {
    game.slices.push({ x: a.x, y: a.y, droppedAt: game.gameTime, claimedBy: null });
    a.carrying = false;
  }
  // existing dead-flag handling continues
  ```
  Plan 1's behavior was "killed Carrier returns the slice to cake instantly" — replace that with the drop.

- [ ] **Step 3: `updateSlices(dt)` — timeout returns to cake**

  ```js
  function updateSlices(dt) {
    var kept = [];
    for (var i = 0; i < game.slices.length; i++) {
      var s = game.slices[i];
      var age = game.gameTime - s.droppedAt;
      if (age >= 8 && !s.claimedBy) {
        // return to cake — no life deduction
        if (game.cake.lost > 0) {
          game.cake.slices = game.cake.slices + 1;  // refund into the slice pool
        } else {
          game.cake.slices = game.cake.slices + 1;
        }
        // Actually: the slice was already removed from the cake when the Carrier picked it up.
        // We just refund cake.slices, leaving cake.lost unchanged.
        continue;
      }
      kept.push(s);
    }
    game.slices = kept;
  }
  ```

  *Subtle accounting note:* in Plan 1, when a Carrier picks up cake, `cake.slices` decrements by 1 (so the cake sprite reads correctly). On delivery, `cake.lost` increments. On return-to-cake (timeout), restore `cake.slices` and leave `cake.lost`. This matches spec SYS-4 ("returns to the cake without deducting a life").

- [ ] **Step 4: Ground pickup in `checkPickupsAndDeliveries`**

  Before the cake-pickup branch, for every ant whose kind has `canPickup: true` and `!a.carrying` and `!a.dead`:
  ```js
  for (var si = 0; si < game.slices.length; si++) {
    var sl = game.slices[si];
    if (sl.claimedBy) { continue; }
    var dxs = a.x - sl.x;
    var dys = a.y - sl.y;
    if (dxs * dxs + dys * dys <= 100) {  // 10 px radius squared
      a.carrying = true;
      sl.claimedBy = a;
      // remove the slice (claimed slices don't sit on the ground anymore)
      game.slices.splice(si, 1);
      break;
    }
  }
  ```

- [ ] **Step 5: Render slice sprite (z-order layer 4)**

  ```js
  for (var i = 0; i < game.slices.length; i++) {
    var s = game.slices[i];
    ctx.fillStyle = 'rgba(0,0,0,0.3)';
    ctx.fillRect(s.x - 3, s.y + 1, 6, 2);     // shadow
    ctx.fillStyle = '#ff9c40';
    ctx.fillRect(s.x - 3, s.y - 2, 6, 4);
    ctx.fillStyle = '#fee761';
    ctx.fillRect(s.x - 2, s.y - 1, 2, 1);     // highlight
  }
  ```

- [ ] **Step 6: Verify**

  Playwright: place a Frost (so cannon hits but doesn't kill instantly), let a Carrier pick up a slice, kill Carrier mid-flee. Watch slice land on ground. Wait — another Worker walks over and picks it up.

- [ ] **Step 7: Commit**

  ```bash
  git commit -am "feat(ant-buster): fallen-slice entity (drop on kill, ground pickup, 8s snap-back)"
  ```

---

## Task 24: Soft tower repulsion with per-tick clamp

**Files:**
- Modify: same file. Replace Plan 1's hard push-out with soft-repulsion accumulator + `1.5 * antSpeed * dt` clamp.

Plan 1's `steerAroundCannons` does a steering bias (good — keep). Plan 1's `updateAnt` end-of-tick has a `minD` push-out (lines ~528-540). Replace that hard push-out with a soft accumulator.

- [ ] **Step 1: Compute repulsion vector**

  In `updateAnt`, after steering and position-integration, before bounds-clamp:
  ```js
  var rx = 0;
  var ry = 0;
  var towerRadius = 12;
  var influence = towerRadius + 4;
  for (var ci = 0; ci < game.cannons.length; ci++) {
    var cn = game.cannons[ci];
    var dxN = ant.x - cn.x;
    var dyN = ant.y - cn.y;
    var dN = Math.sqrt(dxN * dxN + dyN * dyN);
    if (dN < influence) {
      if (dN < 0.001) { dN = 0.001; }
      var force = (influence - dN) / influence;
      rx = rx + (dxN / dN) * force;
      ry = ry + (dyN / dN) * force;
    }
  }
  // clamp magnitude to 1.5 * antSpeed * dt
  var rmag = Math.sqrt(rx * rx + ry * ry);
  var clamp = 1.5 * speed * dt;
  if (rmag > clamp) {
    rx = rx * (clamp / rmag);
    ry = ry * (clamp / rmag);
  }
  ant.x = ant.x + rx;
  ant.y = ant.y + ry;
  ```
  Then the existing playfield-bounds clamp + reflective bounce continues — no change to that block.

- [ ] **Step 2: Remove the old hard push-out block**

  The `minD = 18` block in Plan 1's `updateAnt` becomes redundant once soft repulsion runs every tick.

- [ ] **Step 3: Verify**

  Playwright: place 4 Howitzers in a tight cluster, watch ants flow around them. Drop a Howitzer onto a moving ant — the ant gets shoved out without taking damage and without exiting the playfield.

- [ ] **Step 4: Commit**

  ```bash
  git commit -am "feat(ant-buster): soft tower repulsion with per-tick antSpeed clamp"
  ```

---

## Task 25: Render z-order pass

**Files:**
- Modify: `render()`. Reorder draw calls per spec §3.5.

- [ ] **Step 1: Restructure `render()`**

  Inline-comment each layer:
  ```js
  function render() {
    // backing-canvas scale already set up at module init
    // 1. background
    drawBackground();
    // 2. cake
    drawCake();
    // 3. anthill
    drawAnthill();
    // 4. fallen slices
    drawSlices();
    // 5. range circle (only if a tower is selected)
    if (ui.selectedCannon) { drawRangeCircle(ui.selectedCannon); }
    // 6. cannon bases
    drawCannonBases();
    // 7. ants (sorted by y) + crumb sub-step for carrying ants
    drawAnts();
    // 8. cannon turrets + muzzle flashes
    drawCannonTurrets();
    // 9. projectiles + beams
    drawProjectiles();
    drawBeams();
    // 10. effects
    drawEffects();
    // 11. HUD
    drawHud();
    // 12. shop / tower-detail
    if (ui.selectedCannon) { drawTowerPanel(ui.selectedCannon); } else { drawShop(); }
    // 13. overlays
    drawBanner();
    if (game.paused) { drawPauseOverlay(); }
    if (game.state === 'idle') { drawIdleOverlay(); }
    if (game.waveState === 'won') { drawWinOverlay(); }
    if (game.waveState === 'lost') { drawLoseOverlay(); }   // or game.state === 'lost' depending on what's used
  }
  ```

- [ ] **Step 2: Sort ants by y for top-down feel**

  ```js
  function drawAnts() {
    var sorted = game.ants.slice().sort(function(a, b) { return a.y - b.y; });
    for (var i = 0; i < sorted.length; i++) { /* draw a sorted[i] */ }
  }
  ```

- [ ] **Step 3: Verify**

  Playwright: visual diff via screenshot. Range circle no longer obscures ants. Pause overlay still draws on top of everything.

- [ ] **Step 4: Commit**

  ```bash
  git commit -am "refactor(ant-buster): render z-order pass per spec layers 1-13"
  ```

---

## Task 26: Plan 1 nits + debug `T` hotkey behind `?debug=1` + theme version bump

**Files:**
- Modify: same file. Drop unused `PLAYFIELD_H` if it's no longer referenced (Task 1 may have rescaled it; if so it's now used). Fix splat-on-deliver. Clear `ui.dragging` in `resetGame`. Add debug hotkey. Bump theme `Version:` in `style.css`.

- [ ] **Step 1: Plan 1 nit — `PLAYFIELD_H`**

  After Tasks 1 + 24, `PLAYFIELD_H` should now be used by the bounds-clamp. Confirm with `grep -n PLAYFIELD_H` — if it appears in `updateAnt`, leave it. If still unused, remove the constant.

- [ ] **Step 2: Plan 1 nit — splat-on-deliver**

  In Plan 1, both kill and deliver set `deathTime = 0.5`, so delivered Carriers visually splat. Task 3 added `deliveredAt`. In `checkPickupsAndDeliveries`, when an ant delivers to anthill: set `a.deliveredAt = game.gameTime` and `a.dead = true` and `a.deathTime = 0` so it vanishes immediately, no splat. Killing keeps `a.deliveredAt = 0` and `a.deathTime = 0.5` so the splat still shows.

  In ant render, draw splat sprite only when `a.dead && a.deathTime > 0 && a.deliveredAt === 0`.

- [ ] **Step 3: Plan 1 nit — clear `ui.dragging` in `resetGame`**

  Add `ui.dragging = null; ui.selectedCannon = null;` to `resetGame`. (Task 8 should already do the second; ensure both run.)

- [ ] **Step 4: Debug `T` hotkey**

  Near the top of the IIFE:
  ```js
  var DEBUG = /[?&]debug=1/.test(location.search);
  var DEBUG_KINDS = ['worker','carrier','scout','soldier','tank','splitter','phase','bomber','queen'];
  var debugCycleIdx = 0;
  ```
  Extend keydown:
  ```js
  if (DEBUG) {
    if (e.key === 't' || e.key === 'T') {
      var k = DEBUG_KINDS[debugCycleIdx % DEBUG_KINDS.length];
      spawnAntAt(k, game.anthill.x, game.anthill.y);
      debugCycleIdx = debugCycleIdx + 1;
      return;
    }
  }
  ```

- [ ] **Step 5: Bump theme `Version:` in `style.css`**

  Open `wp-content/themes/dariobimbi/style.css`, find the `Version:` line, increment patch (or bump minor — match Plan 1's last bump pattern).

- [ ] **Step 6: Verify**

  Playwright: visit `/demos/ant-buster/?debug=1`, press `T` nine times — each press spawns one of the nine listed kinds at the anthill. Visit without `?debug=1`, press `T` — nothing happens.

  Kill a Carrier mid-deliver: splat shows. Carrier reaches anthill: vanishes silently.

- [ ] **Step 7: Commit**

  ```bash
  git commit -am "fix(ant-buster): Plan 1 nits + debug T hotkey behind ?debug=1 + theme version bump"
  ```

---

## Task 27: Final smoke test against §6 acceptance criteria

This task runs the full acceptance walk from spec §6 and produces a status doc. No code edits unless something fails — in which case file a fix as a follow-up commit, then continue.

- [ ] **Step 1: Run all 14 acceptance items in Playwright**

  Walk each item in spec §6 in order. For each, document pass/fail with a short note. Items 1-5, 9, 11, 12, 14 are the deepest:
  - **Canvas size.** Native 640×360 confirmed via `window.__ab` constants exposure or rendered screenshot pixel count.
  - **9 stub cannons.** Place each, upgrade through full path, verify 70% sell at every tier.
  - **9 ant types render.** Use `?debug=1` + cycle T. Each visually distinguishable.
  - **Status framework with `slow`.** Frost slows; Glacier 50% slow + 1s linger after disconnect; Soldier resists.
  - **Status framework all 6 kinds.** `Object.keys(STATUS_KINDS)` returns the 6 names. Manual injection of each via `applyStatus` produces correct behavior.
  - **All 5 waves complete.** Speed-run with three placed cannons.
  - **Soft repulsion.** Visual smoke check.
  - **Plan 1 nits.** Carrier-deliver vs Carrier-kill behavior differ.
  - **No `&#038;&#038;`.** `curl -s ... | grep -F '&#038;&#038;'` returns nothing.

- [ ] **Step 2: Acknowledge any unresolved nits**

  If any item fails, file a follow-up commit. If a fix would meaningfully change scope (e.g. requires a new ant kind), pause and surface to the user — don't expand silently.

- [ ] **Step 3: Write status doc**

  Create `docs/superpowers/plans/2026-05-07-ant-buster-plan-2-status.md` mirroring Plan 1's status doc structure: list shipped tasks with their commit SHAs, post-plan polish (if any), open issues for Plan 3.

- [ ] **Step 4: Commit status doc**

  ```bash
  git add docs/superpowers/plans/2026-05-07-ant-buster-plan-2-status.md
  git commit -m "docs(ant-buster): Plan 2 status — shipped"
  ```

---

## End-of-plan notes

- **Single commit per task** unless a Plan 1-style implementer-found-issue requires a fix. Use a fresh implementer for fixes; don't amend.
- **Capture base SHA before each implementer dispatch and head SHA after the commit** so the reviewer can `git diff base..head`.
- **For Plan 2 the dispatch pattern mirrors Plan 1**: combined spec + code-quality reviewer per non-trivial task. Trivial tasks reviewed inline.
- Tasks 4, 5, 8, 9, 14, 18, 23, 24, 25 are the highest-stakes — each touches multiple existing systems and benefits from a separate code-quality reviewer pass even on top of the combined reviewer.
- Tasks 15, 16, 17, 19, 20, 21 are mechanical content — a single combined reviewer is enough.
