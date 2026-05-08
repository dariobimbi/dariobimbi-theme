# Ant Buster — Plan 4 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Land Plan 3's deferred Pink Rubber Family + 18 god-tier T3 cannons gated behind a wave-15 unlock + four new mechanic primitives (MP-L bouncing retarget, MP-M mid-flight split, MP-N persistent radial aura, MP-O beam line-pierce) + light decorative visual juice (gold halo, 1.7× muzzle flash, screen shake on heavy ground-pounders, Biohazard cyan outline) + Plan 3 hygiene closeout (six remaining `||` operators, slice timeout simDt scaling).

**Architecture:** Single-file pattern (`patterns/demo-ant-buster.php`). Plan 2's four kind-tables (`CANNON_KINDS`, `ANT_KINDS`, `STATUS_KINDS`, `WAVES`) drive everything by string key. Plan 4 = appending 18 god-tier rows + 3 Pink Rubber rows + 4 new mechanic primitive functions + 2 new STATUS_KINDS hooks + 1 splash-AoE extension + light visual additions. No new files, no new templates.

**Tech Stack:** Vanilla JS (IIFE, `var` only, no `&&`/`||` operators in inline scripts), Canvas 2D, fixed-timestep loop, procedural placeholder sprites. PHP 8.2 for `php -l`. Playwright MCP for per-task verification (navigate + console + `window.__ab*` introspection + screenshot).

**Spec:** [`../specs/2026-05-08-ant-buster-plan-4-design.md`](../specs/2026-05-08-ant-buster-plan-4-design.md)
**Plan 3 status:** [`./2026-05-07-ant-buster-plan-3-status.md`](./2026-05-07-ant-buster-plan-3-status.md)

---

## Conventions every task must follow

1. **Inline-script syntax footgun.** Never write `&&` or `||` inside the `<script>` block. Use nested `if`, helper booleans (`var ok = a; if (!ok) { ok = b; }`), or `indexOf` checks. After every commit, verify:

   ```bash
   curl -s http://localhost/dariobimbi.com/demos/ant-buster/ | grep -F '&#038;&#038;'
   curl -s http://localhost/dariobimbi.com/demos/ant-buster/ | grep -F '&#124;&#124;'
   ```

   Expected: empty output for both. Any hit blocks the commit; rewrite the offending expression.
2. **`var` only.** No `let` or `const`. Function declarations only — no `var foo = function () {}` for shared helpers (Plan 1's hoisting trap).
3. **PHP syntax check after every edit:**

   ```bash
   c:/wamp64/bin/php/php8.2.29/php.exe -l c:/wamp64/www/dariobimbi.com/wp-content/themes/dariobimbi/patterns/demo-ant-buster.php
   ```

   Expected: `No syntax errors detected`.
4. **Playwright verification per task.**
   - Navigate to `http://localhost/dariobimbi.com/demos/ant-buster/?debug=1`.
   - Read console messages — any error other than benign 404s is a blocker.
   - Inspect game state via `browser_evaluate` reading `window.__ab` (whole `game` object), `window.__abUi`, `window.__abCannonKinds`, `window.__abAntKinds`, `window.__abStatusKinds`, `window.__abApplyStatus`, `window.__abApplyDamage`, `window.__abWaves`.
   - Screenshot for any visual change.
5. **Commit per task** in conventional-commits style: `feat(ant-buster): …`, `refactor(ant-buster): …`, `fix(ant-buster): …`. Subagent-driven workers commit; the reviewer verifies the diff before approving.
6. **No theme `Version:` bump per task.** Bumped once at the end (Task 30).
7. **Serve from local WAMP.** Demo at `/demos/ant-buster/`.
8. **Tier convention.** `CANNON_KINDS[k].tier` is a **string** in Plan 4: `'base'`, `'mid'`, `'final'`, or `'god'`. Plan 3 used numeric tiers (1/2/3). Task 1 migrates the existing 30 rows to strings as a one-shot find-and-replace. Throughout this plan, `tier === 'god'` checks resolve via the string convention.
9. **Order matters.** Tasks 1–5 (Bucket A architecture refactors) must run first. Bucket B (Pink Rubber Family) depends only on Task 1. Bucket C/D god-tier work depends on the full Bucket A. Hygiene (Bucket G) can interleave with anything.

---

## File structure

All work lives in **two** files:
- `c:/wamp64/www/dariobimbi.com/wp-content/themes/dariobimbi/patterns/demo-ant-buster.php` (currently 3940 lines after Plan 3, projected end-of-Plan-4: ~4000–4200 lines)
- `c:/wamp64/www/dariobimbi.com/wp-content/themes/dariobimbi/style.css` (one-line `Version:` bump at the end)

Logical regions inside the IIFE in `demo-ant-buster.php` (line numbers approximate, will drift):

1. **Constants & state** (~85–130): canvas dims, `DEBUG_KINDS`, `game` state object, banner state. Plan 4 adds `game.godTierUnlocked` and `game.godTierUnlockPulseStart` here.
2. **Sprite factories** (~140–1050): per-kind procedural sprites + UI sprites. Plan 4 adds Pink Pea / Bouncy Bubble / Wrecking Ball turret sprites. No new ant sprites.
3. **Render** (~1052–1078): `render()` walks 13 z-order layers. Plan 4 adds god-tier halo, Blizzard aura ring, Biohazard cyan outline, transient effects (Cluster split flash, Phoenix burn-spread ring, Tempest knockback ring) into existing layers.
4. **Tower-detail panel** (~1028–1170): `drawTowerPanel`. Plan 4 extends to render greyed-out god-tier upgrade button when locked + golden pulse halo when wave-15 unlock fires.
5. **Ant render** (~1167–1245): `drawAnts`, `drawAntHpBar`. Plan 4 adds Biohazard cyan outline pulse to `drawAnt` (line ~1167).
6. **ANT_KINDS table** (~1644–1900): per-kind data rows. Plan 4 does **not** modify ANT_KINDS — no new ant kinds.
7. **STATUS_KINDS table** (~1907–1940): six status kinds. Plan 4 adds `burn.onExpire` hook field + `poison.damageAmp` field (default 1).
8. **Status helpers** (~1942–1965): `applyStatus`. Plan 4 adds the burn-on-expire firing path inside the per-tick status update loop (separate location near `updateAnts`).
9. **Spawn / ant logic** (~1965–2293): `spawnAntAt`, `spawnAnt`, `updateAnt`, `updateAnts`. Plan 4's only touch here is the burn-on-expire firing path.
10. **CANNON_KINDS table** (~2294–2750): cannon data rows. Plan 4 appends 18 god-tier rows + 3 Pink Rubber rows. Migrates existing 30 rows from numeric tier to string tier.
11. **Cannon logic** (~2751–3395): `findTarget`, `updateCannons`, `fireProjectile`, `applyDamage`, `applySplash`, `updateProjectiles`. Plan 4 extends `applySplash` for `splashStatus`, `applyDamage` for `poison.damageAmp`, `fireProjectile` and `updateProjectiles` for new mechanic primitives (MP-L, MP-M, MP-N, MP-O).
12. **Wave logic** (~3395–3895): `WAVES` array + `updateWaveState`. Plan 4 adds the wave-15 unlock trigger inside `updateWaveState`.
13. **Reset / UI / input** (~3895–3940): `resetGame`, `ui` state, mouse + key handlers, `window.__ab*` exposure. Plan 4 adds `?debug=1 G` and `?debug=1 W` hotkeys + `__abGodTierUnlocked` setter.

---

## Bucket A — Architecture refactors (Tasks 1–5)

These five tasks land first. Everything else depends on them.

### Task 1: Migrate `tier` to string enum + add `flashScale` field

**Files:**
- Modify: `patterns/demo-ant-buster.php` — `CANNON_KINDS` table (~lines 2294–2750)

Existing 30 rows use `tier: 1 | 2 | 3` (number). Plan 4 spec calls for `tier: 'base' | 'mid' | 'final' | 'god'` (string). This task migrates the existing 30 rows. Also adds `flashScale` field default to all rows so god-tier rows can opt-in to the bigger flash.

- [ ] **Step 1: Find-and-replace `tier: 1` → `tier: 'base'`**

  Use Grep to find all `tier: 1` occurrences in `CANNON_KINDS`. Should match the 4 base cannons: `howitzer`, `repeater`, `twingun`, plus `spotter` (which is currently `tier: 2` — verify; Plan 3 shipped Spotter as a `mid`).

  Then `tier: 2` → `tier: 'mid'`, `tier: 3` → `tier: 'final'`. Verify counts:

  - `tier: 'base'`: 3 cannons (Howitzer, Repeater, Twin Gun)
  - `tier: 'mid'`: 9 cannons (Siege, Mortar, Discus, Auto-Cannon, Marksman, Spotter, Triple Threat, Frost, Toxin)
  - `tier: 'final'`: 18 cannons (Railgun, Demolisher, Hellfire, Patriot, Ricochet, Shockwave, Vulcan, Plasma Torch, Sharpshooter, Lance, Phantom, Tesla, Buckshot, Minigun, Glacier, Cryo, Plague, Sonic Pulse)

- [ ] **Step 2: PHP lint**

  ```bash
  c:/wamp64/bin/php/php8.2.29/php.exe -l c:/wamp64/www/dariobimbi.com/wp-content/themes/dariobimbi/patterns/demo-ant-buster.php
  ```

  Expected: `No syntax errors detected`.

- [ ] **Step 3: Playwright verify tier counts**

  Navigate to `?debug=1`. Run via `browser_evaluate`:

  ```js
  return JSON.stringify({
    base: Object.values(window.__abCannonKinds).filter(c => c.tier === 'base').length,
    mid: Object.values(window.__abCannonKinds).filter(c => c.tier === 'mid').length,
    final: Object.values(window.__abCannonKinds).filter(c => c.tier === 'final').length,
    numericTier: Object.values(window.__abCannonKinds).filter(c => typeof c.tier === 'number').length
  });
  ```

  Expected: `{"base":3,"mid":9,"final":18,"numericTier":0}`.

- [ ] **Step 4: Hygiene check**

  ```bash
  curl -s http://localhost/dariobimbi.com/demos/ant-buster/ | grep -F '&#124;&#124;'
  curl -s http://localhost/dariobimbi.com/demos/ant-buster/ | grep -F '&#038;&#038;'
  ```

  Expected: both empty.

- [ ] **Step 5: Commit**

  ```bash
  git add patterns/demo-ant-buster.php
  git commit -m "refactor(ant-buster): migrate CANNON_KINDS tier to string enum (Plan 4 Task 1)"
  ```

---

### Task 2: Add `splashStatus` field + extend `applySplash` to apply statusOnHit to all caught ants

**Files:**
- Modify: `patterns/demo-ant-buster.php` — `applySplash` function (~lines 3172–3187)

Plan 3's `applySplash(x, y, radius, damage, falloff, sourceCannon)` only damages ants in radius. Earthshaker, Tempest, and Absolute Zero need to apply their `statusOnHit` to every ant caught in the splash. Extension: when the source cannon has `splashStatus: true`, iterate the ants caught in radius and apply each entry of the cannon's `statusOnHit` array.

- [ ] **Step 1: Replace `applySplash` body**

  Find the function at line ~3172. Replace its body with:

  ```js
  function applySplash(x, y, radius, damage, falloff, sourceCannon) {
    var def = null;
    if (sourceCannon) { def = CANNON_KINDS[sourceCannon.kind]; }
    var applyStatusToAll = false;
    if (def) {
      if (def.splashStatus) { applyStatusToAll = true; }
    }
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
      if (applyStatusToAll) {
        if (def.statusOnHit) {
          var slist = def.statusOnHit;
          if (!Array.isArray(slist)) { slist = [slist]; }
          for (var si = 0; si < slist.length; si++) {
            var s = slist[si];
            applyStatus(a, s.kind, s.magnitude, s.duration, 'splash');
            if (s.damageAmp) {
              if (a.effects[s.kind]) { a.effects[s.kind].damageAmp = s.damageAmp; }
            }
            if (s.spreads) {
              if (a.effects[s.kind]) { a.effects[s.kind].spreads = true; }
            }
          }
        }
      }
      if (a.hp <= 0) { killAnt(a, sourceCannon); }
    }
  }
  ```

- [ ] **Step 2: Push Tempest knockback ring transient effect**

  Inside `applySplash`, after the `applyStatusToAll` block, also push a white ring transient when the splash status array contains a `knockback` entry. This produces Tempest's visual cue (spec §2.4 item 9). Add at the bottom of `applySplash`, after the iteration:

  ```js
  // Push a transient white knockback ring when applicable. Same shape as
  // Phoenix's burn-spread ring (Task 4) so the render path is shared via
  // game.transientEffects (Task 13). Tempest is the primary user; any
  // future cannon that emits knockback via splashStatus also gets the ring.
  if (applyStatusToAll) {
    if (def && def.statusOnHit) {
      var hasKnockback = false;
      var slist2 = def.statusOnHit;
      if (!Array.isArray(slist2)) { slist2 = [slist2]; }
      for (var sj = 0; sj < slist2.length; sj++) {
        if (slist2[sj].kind === 'knockback') { hasKnockback = true; }
      }
      if (hasKnockback) {
        if (game.transientEffects) {
          game.transientEffects.push({
            kind: 'knockbackRing',
            x: x, y: y,
            startTime: game.time,
            duration: 0.15,
            color: '#ffffff',
            maxRadius: radius
          });
        }
      }
    }
  }
  ```

- [ ] **Step 3: PHP lint + hygiene check**

  Same pair of curl-grep checks as Task 1. Expected empty.

- [ ] **Step 4: Playwright smoke**

  Navigate to `?debug=1`. Force a Shockwave splash (existing splash cannon, no `splashStatus`) — verify ants caught in splash take damage but no status applied (regression check). Read console: no errors.

- [ ] **Step 5: Commit**

  ```bash
  git add patterns/demo-ant-buster.php
  git commit -m "refactor(ant-buster): splashStatus field on CANNON_KINDS + applySplash extension + knockback ring transient (Plan 4 Task 2)"
  ```

---

### Task 3: Add `poison.damageAmp` field + read in `applyDamage`

**Files:**
- Modify: `patterns/demo-ant-buster.php` — `applyDamage` function (~line 3082) and `STATUS_KINDS` table (~line 1907)

Mirror of Plan 3's `freeze.damageAmp` for poison. When a Biohazard-applied poison effect carries `damageAmp: 1.25`, every damage tick on the infected target gets +25%. Source-independent.

- [ ] **Step 1: Read existing freeze.damageAmp pattern in applyDamage**

  Look at lines 3096–3101 — that's the existing freeze.damageAmp read. Pattern to mirror.

- [ ] **Step 2: Add poison.damageAmp read in applyDamage**

  After the freeze.damageAmp block (~line 3101), add:

  ```js
  if (ant.effects) {
    if (ant.effects.poison) {
      var pamp = ant.effects.poison.damageAmp;
      if (pamp) { dmg = dmg * pamp; }
    }
  }
  ```

- [ ] **Step 3: Verify STATUS_KINDS.poison default is compatible**

  Check `STATUS_KINDS.poison` row (~line 1907). Confirm it does NOT initialize `damageAmp` on apply — `damageAmp` is set only by tag-after-apply on the projectile-collision pathway (existing pattern in updateProjectiles ~line 3265). No change needed in STATUS_KINDS itself.

- [ ] **Step 4: Verify backward compatibility**

  Plan 3's Toxin and Plague apply `poison` without `damageAmp`. With this change, those kills still work (the `if (pamp)` gate skips the multiplication when `damageAmp` is undefined). Run Playwright through wave 6 (Toxin / Plague / poison interactions) and confirm no console errors.

- [ ] **Step 5: PHP lint + hygiene**

  Standard pair of checks.

- [ ] **Step 6: Commit**

  ```bash
  git add patterns/demo-ant-buster.php
  git commit -m "feat(ant-buster): poison.damageAmp read in applyDamage (Plan 4 Task 3)"
  ```

---

### Task 4: Add `burn.onExpire(deadAnt)` framework hook + tag-after-apply for `burn.spreads`

**Files:**
- Modify: `patterns/demo-ant-buster.php` — `STATUS_KINDS.burn` (~line 1907 area), `updateAnts` per-tick status update loop, `updateProjectiles` collision tag-after-apply (~3265)

Mirror of Plan 3's `poison.onTargetDeath`. Phoenix needs to spread burn to ants within 30px when the burn duration runs out (not on death).

- [ ] **Step 1: Find existing status-tick loop**

  Search for the location where status durations are decremented per tick. Plan 3 should have a per-status-update loop in `updateAnts`. Look for `effects.burn`, `effects.poison`, `effects.freeze` decrements.

- [ ] **Step 2: Add `onExpire` hook to STATUS_KINDS.burn**

  In the `STATUS_KINDS.burn` row, add the hook field:

  ```js
  burn: {
    onExpire: function (ant) {
      // Phoenix spread: if the burn was tagged as spreading, propagate to
      // ants within 30 px on burn-end. Toxin / Plasma Torch leave .spreads
      // unset, so this fires only for Phoenix.
      if (!ant.effects) { return; }
      if (!ant.effects.burn) { return; }
      if (!ant.effects.burn.spreads) { return; }
      for (var i = 0; i < game.ants.length; i++) {
        var a = game.ants[i];
        if (a === ant) { continue; }
        if (a.dead) { continue; }
        var dx = a.x - ant.x;
        var dy = a.y - ant.y;
        if (dx * dx + dy * dy > 30 * 30) { continue; }
        applyStatus(a, 'burn', 1, 2, 'burn-spread');
        // Stamp spreads so chained spreads continue (capped by burn natural decay).
        if (a.effects.burn) { a.effects.burn.spreads = true; }
      }
      // Push a transient visual effect (orange shockwave ring) for the spread cue.
      // This shape lives alongside Plan 3's splash-flash. Implementation in Task 11.
      if (game.transientEffects) {
        game.transientEffects.push({
          kind: 'spreadRing',
          x: ant.x, y: ant.y,
          startTime: game.time,
          duration: 0.2,
          color: '#ff7733',
          maxRadius: 30
        });
      }
    },
    // Existing fields stay (tickDamage, tickInterval, etc.)
  }
  ```

- [ ] **Step 3: Wire `onExpire` firing into the per-tick status update loop**

  Inside `updateAnts` (or wherever burn duration is decremented), when a burn duration drops to ≤ 0, call `STATUS_KINDS.burn.onExpire(ant)` *before* deleting the effect entry. Pseudocode shape:

  ```js
  if (ant.effects.burn) {
    ant.effects.burn.duration -= dt;
    if (ant.effects.burn.duration <= 0) {
      STATUS_KINDS.burn.onExpire(ant);
      delete ant.effects.burn;
    }
  }
  ```

  Adapt to actual loop structure.

- [ ] **Step 4: Initialize `game.transientEffects`**

  In `resetGame` (around line ~3595, where `game.spawnJobs = []` is set), add:

  ```js
  game.transientEffects = [];
  ```

  (The transient-effect render path lives in Task 11 / 12; the array just needs to exist.)

- [ ] **Step 5: PHP lint + hygiene + Playwright smoke**

  Standard checks. No console errors.

- [ ] **Step 6: Commit**

  ```bash
  git add patterns/demo-ant-buster.php
  git commit -m "feat(ant-buster): burn.onExpire framework hook + spreads tag (Plan 4 Task 4)"
  ```

---

### Task 5: Add `game.godTierUnlocked` state + wave-15 unlock trigger + click-handler gate

**Files:**
- Modify: `patterns/demo-ant-buster.php` — `game` state object (~line 90–130), `updateWaveState` (~line 3523), `upgradeTower` or equivalent (search for it), `resetGame` (~line 3595)

State plumbing for the wave-15 unlock. UI rendering lands in Bucket F; this task is the data layer.

- [ ] **Step 1: Add fields to `game` state object**

  In the `game` state initializer, add:

  ```js
  godTierUnlocked: false,
  godTierUnlockPulseStart: 0,
  ```

- [ ] **Step 2: Reset both fields in `resetGame`**

  In `resetGame`, alongside `game.gold = ...`, add:

  ```js
  game.godTierUnlocked = false;
  game.godTierUnlockPulseStart = 0;
  ```

- [ ] **Step 3: Wave-15 unlock trigger in `updateWaveState`**

  Find the branch in `updateWaveState` that pays the wave-completion bonus (Plan 3 added the `20 + waveNum` bonus). After the bonus payout, add:

  ```js
  if (wv.number === 15) {
    if (!game.godTierUnlocked) {
      game.godTierUnlocked = true;
      game.godTierUnlockPulseStart = game.time;
    }
  }
  ```

  Nested ifs because `&&` is forbidden in inline JS.

- [ ] **Step 4: Find or create the upgrade-commit function**

  Search for the function that commits a tower upgrade — likely `upgradeTower` or inline within the panel click handler. Should be reachable from `ui.upgradeRects` click logic.

- [ ] **Step 5: Add click-handler gate**

  At the top of the upgrade-commit function (or branch within the click handler), add the guard:

  ```js
  // Block god-tier upgrades when locked. Defense-in-depth alongside the
  // visual greying in drawTowerPanel (Bucket F).
  if (udef.tier === 'god') {
    if (!game.godTierUnlocked) { return; }
  }
  ```

- [ ] **Step 6: Expose to debug surface**

  At the bottom of the IIFE (~line 3938 alongside other `window.__ab*`), add:

  ```js
  window.__abGodTierUnlocked = function (v) {
    if (typeof v === 'boolean') {
      game.godTierUnlocked = v;
      if (v) { game.godTierUnlockPulseStart = game.time; }
    }
    return game.godTierUnlocked;
  };
  ```

  This gives Playwright tests a way to set the flag without grinding 15 waves.

- [ ] **Step 7: PHP lint + hygiene + Playwright verify**

  ```js
  return JSON.stringify({
    initial: window.__abGodTierUnlocked(),
    afterSet: window.__abGodTierUnlocked(true),
    pulseStart: window.__ab.godTierUnlockPulseStart > 0
  });
  ```

  Expected: `{"initial":false,"afterSet":true,"pulseStart":true}`.

- [ ] **Step 8: Commit**

  ```bash
  git add patterns/demo-ant-buster.php
  git commit -m "feat(ant-buster): godTierUnlocked state + wave-15 trigger + upgrade gate (Plan 4 Task 5)"
  ```

---

## Bucket B — Pink Rubber Family (Tasks 6–9)

Plan 3 §9 designed Bucket G but deferred implementation. Plan 4 lands it here. MP-L is shared with no other family, so it ships in this bucket.

### Task 6: MP-L bouncing-retarget primitive

**Files:**
- Modify: `patterns/demo-ant-buster.php` — `fireProjectile` (~line 2919), `updateProjectiles` (~line 3189) collision body

When a projectile with `bouncesRemaining > 0` hits an ant, find the nearest non-`_sawAnts` live ant within `bounceRadius`, redirect velocity, decrement counter, multiply damage by `damageDecay`, do not despawn. If no eligible target found, despawn normally.

- [ ] **Step 1: Wire bounce fields into projectile literal in `fireProjectile`**

  In `fireProjectile`, find the `game.projectiles.push({...})` block. After existing fields (`damage`, `linePierce`, etc.), add:

  ```js
  bouncesRemaining: def.bouncesRemaining ? def.bouncesRemaining : 0,
  bounceRadius: def.bounceRadius ? def.bounceRadius : 0,
  damageDecay: def.damageDecay ? def.damageDecay : 1,
  initialHit: true,
  ```

  `initialHit` flips to false after first impact and is used by Wrecking Ball's `statusOnFirstHit` (Task 8).

- [ ] **Step 2: Bounce-retarget branch in updateProjectiles collision body**

  In `updateProjectiles`, after the `applyDamage(a, p.damage, ...)` call but before the `linePierce` branch, add:

  ```js
  // MP-L bouncing retarget. After the hit lands, search for the nearest
  // non-already-hit live ant within bounceRadius. If found: redirect
  // velocity, decrement bouncesRemaining, multiply damage by damageDecay,
  // do not despawn. If not found: despawn normally.
  if (p.bouncesRemaining > 0) {
    if (!p._sawAnts) { p._sawAnts = []; }
    p._sawAnts.push(a);
    var bestBounce = null;
    var bestBounceDist = Infinity;
    for (var bk = 0; bk < game.ants.length; bk++) {
      var ba = game.ants[bk];
      if (ba.dead) { continue; }
      if (ba.intangible) { continue; }
      if (p._sawAnts.indexOf(ba) !== -1) { continue; }
      var bdx = ba.x - p.x;
      var bdy = ba.y - p.y;
      var bdSq = bdx * bdx + bdy * bdy;
      if (bdSq > p.bounceRadius * p.bounceRadius) { continue; }
      if (bdSq < bestBounceDist) { bestBounceDist = bdSq; bestBounce = ba; }
    }
    if (bestBounce) {
      var bAngle = Math.atan2(bestBounce.y - p.y, bestBounce.x - p.x);
      var bSpeed = Math.sqrt(p.vx * p.vx + p.vy * p.vy);
      p.vx = Math.cos(bAngle) * bSpeed;
      p.vy = Math.sin(bAngle) * bSpeed;
      p.bouncesRemaining = p.bouncesRemaining - 1;
      p.damage = p.damage * p.damageDecay;
      p.initialHit = false;
      // Skip the despawn — projectile keeps flying. Continue outer loop.
      continue;
    }
    // No eligible bounce target → fall through to normal despawn.
  }
  ```

  Place this branch so it runs AFTER the existing damage/status application but BEFORE the linePierce check. The `continue` jumps back to the outer projectile loop.

- [ ] **Step 3: Gate `statusOnHit` application on `initialHit`**

  When the cannon has `statusOnFirstHit` (a separate field, see Task 7), check `p.initialHit` before applying. The general statusOnHit array continues to fire on every bounce. (Task 8 wires this when Wrecking Ball lands.)

- [ ] **Step 4: PHP lint + hygiene check**

  Standard.

- [ ] **Step 5: Commit**

  ```bash
  git add patterns/demo-ant-buster.php
  git commit -m "feat(ant-buster): MP-L bouncing-retarget primitive (Plan 4 Task 6)"
  ```

---

### Task 7: `visualScale` and `statusOnFirstHit` fields on CANNON_KINDS + projectile sprite scaling

**Files:**
- Modify: `patterns/demo-ant-buster.php` — `fireProjectile` (~2919), `drawProjectiles` (~1274)

Plan 3 §9 designed both fields. Plan 4 wires them into the render path.

- [ ] **Step 1: Wire `visualScale` into projectile literal in `fireProjectile`**

  Add to the projectile push:

  ```js
  visualScale: def.visualScale ? def.visualScale : 1.0,
  ```

- [ ] **Step 2: Wire `statusOnFirstHit` into projectile literal**

  Add:

  ```js
  statusOnFirstHit: def.statusOnFirstHit ? def.statusOnFirstHit : null,
  ```

- [ ] **Step 3: Apply `statusOnFirstHit` in collision branch**

  In `updateProjectiles` collision body, after the existing `statusOnHit` application, add:

  ```js
  if (p.statusOnFirstHit) {
    if (p.initialHit) {
      var sf = p.statusOnFirstHit;
      var sflist = sf;
      if (!Array.isArray(sflist)) { sflist = [sf]; }
      for (var sfi = 0; sfi < sflist.length; sfi++) {
        var sfe = sflist[sfi];
        applyStatus(a, sfe.kind, sfe.magnitude, sfe.duration, 'first-hit');
      }
    }
  }
  ```

- [ ] **Step 4: Scale projectile rendering by `visualScale`**

  In `drawProjectiles` (~line 1274), find where each projectile renders. Scale the radius / sprite size:

  ```js
  var pScale = p.visualScale ? p.visualScale : 1.0;
  // existing render call multiplies size by pScale
  ```

  Specific edit depends on existing rendering shape — multiply existing fixed radii by `pScale`.

- [ ] **Step 5: PHP lint + hygiene + Playwright smoke**

  No new cannons use these fields yet (Pink Rubber lands in Task 8), so this is a regression check on existing cannons. Confirm no console errors.

- [ ] **Step 6: Commit**

  ```bash
  git add patterns/demo-ant-buster.php
  git commit -m "feat(ant-buster): visualScale + statusOnFirstHit fields on CANNON_KINDS (Plan 4 Task 7)"
  ```

---

### Task 8: Pink Rubber Family CANNON_KINDS rows + procedural pink art

**Files:**
- Modify: `patterns/demo-ant-buster.php` — `CANNON_KINDS` table (append three rows ~line 2740), sprite factories (~line 200, find existing turret factories)

- [ ] **Step 1: Append three CANNON_KINDS rows**

  At the end of the `CANNON_KINDS` table, just before the closing `}`:

  ```js
  pink_pea: {
    name: 'Pink Pea',
    cost: 50,
    damage: 5,
    fireRate: 0.8,
    range: 130,
    projectileSpeed: 220,
    family: 'rubber',
    tier: 'base',
    bouncesRemaining: 1,
    bounceRadius: 50,
    damageDecay: 0.8,
    visualScale: 1.0,
    upgradesTo: ['bouncy_bubble']
  },
  bouncy_bubble: {
    name: 'Bouncy Bubble',
    cost: 80,
    damage: 12,
    fireRate: 0.6,
    range: 160,
    projectileSpeed: 240,
    family: 'rubber',
    tier: 'mid',
    bouncesRemaining: 2,
    bounceRadius: 60,
    damageDecay: 0.8,
    visualScale: 1.5,
    upgradesTo: ['rubber_wrecking_ball']
  },
  rubber_wrecking_ball: {
    name: 'Rubber Wrecking Ball',
    cost: 120,
    damage: 30,
    fireRate: 0.4,
    range: 180,
    projectileSpeed: 200,
    family: 'rubber',
    tier: 'final',
    bouncesRemaining: 4,
    bounceRadius: 70,
    damageDecay: 0.8,
    visualScale: 2.5,
    statusOnFirstHit: { kind: 'knockback', magnitude: 30, duration: 0 },
    upgradesTo: []
  }
  ```

- [ ] **Step 2: Add procedural pink turret + ball sprites**

  Find existing turret sprite factories (e.g., `makeHowitzer`, `makeRepeater`). Mirror the pattern with three new functions:

  ```js
  function makePinkPea() {
    // simple pink turret sprite, hex #ff69b4 base
    // (canvas drawing primitives — circle base, short barrel)
  }
  function makeBouncyBubble() {
    // medium pink with #ff5fa0 accent
  }
  function makeRubberWreckingBall() {
    // dark pink with #ff1493 accent + heavy chain detail
  }
  ```

  Wire into the turret-sprite map (search for `howitzer:` in the sprite map and add three matching entries).

- [ ] **Step 3: PHP lint + hygiene**

  Standard.

- [ ] **Step 4: Playwright verify**

  ```js
  return JSON.stringify({
    pinkPea: !!window.__abCannonKinds.pink_pea,
    bouncyBubble: !!window.__abCannonKinds.bouncy_bubble,
    wreckingBall: !!window.__abCannonKinds.rubber_wrecking_ball,
    wreckingBallStatus: window.__abCannonKinds.rubber_wrecking_ball.statusOnFirstHit
  });
  ```

  Expected: all three true; statusOnFirstHit shape matches.

- [ ] **Step 5: Commit**

  ```bash
  git add patterns/demo-ant-buster.php
  git commit -m "feat(ant-buster): Pink Rubber Family — Pink Pea / Bouncy Bubble / Wrecking Ball (Plan 4 Task 8)"
  ```

---

### Task 9: Build palette extension from 3 to 4 cards

**Files:**
- Modify: `patterns/demo-ant-buster.php` — `ui` shop state (search for `ui.shop` or build palette config), `drawShop` rendering

Find where the build palette renders the 3 base cards (Howitzer / Repeater / Twin Gun). Add Pink Pea as the 4th card.

- [ ] **Step 1: Find build palette config**

  Search for `'howitzer', 'repeater', 'twingun'` (or similar) in the file. Should be in `ui` state or a `SHOP_KEYS` constant.

- [ ] **Step 2: Append `'pink_pea'`**

  Update the palette array:

  ```js
  ['howitzer', 'repeater', 'twingun', 'pink_pea']
  ```

- [ ] **Step 3: Verify shop layout fits**

  Check `SHOP_W` constant or shop card dimensions. Plan 3 §9 verified 4 × 70px cards + spacing fits the existing shop column. Re-verify by navigating to the demo and visually confirming the 4th card renders cleanly without overlap.

- [ ] **Step 4: PHP lint + hygiene**

  Standard.

- [ ] **Step 5: Playwright verify**

  Take screenshot of the shop column. Build a Pink Pea, place it on the field. Verify it can fire and bounce (manual test against a cluster of workers).

- [ ] **Step 6: Commit**

  ```bash
  git add patterns/demo-ant-buster.php
  git commit -m "feat(ant-buster): build palette extension — Pink Pea as 4th card (Plan 4 Task 9)"
  ```

---

## Bucket C — New mechanic primitives + their visuals (Tasks 10–14)

### Task 10: MP-M mid-flight projectile split (Cluster Strike branch in updateProjectiles)

**Files:**
- Modify: `patterns/demo-ant-buster.php` — `fireProjectile` (~2919) and `updateProjectiles` (~3189)

When a projectile with `splitFraction` and `splitCount` reaches that fraction of its original travel distance, it despawns and spawns N child projectiles. Each child uses the standard MP-C homing primitive.

- [ ] **Step 1: Wire MP-M fields into projectile literal in `fireProjectile`**

  ```js
  splitFraction: def.splitFraction ? def.splitFraction : 0,
  splitCount: def.splitCount ? def.splitCount : 0,
  splitOriginX: cannon.x,    // store fire position for distance comparison
  splitOriginY: cannon.y,
  splitTotalDist: 0,
  ```

  When the cannon has `splitFraction`, also set `homing: true` on the parent projectile so it tracks the target.

  Compute `splitTotalDist` at fire time:

  ```js
  if (def.splitFraction) {
    var sdx = target.x - cannon.x;
    var sdy = target.y - cannon.y;
    var sd = Math.sqrt(sdx * sdx + sdy * sdy);
    p.splitTotalDist = sd * def.splitFraction;
  }
  ```

  (Inserted in the literal after computing target.)

- [ ] **Step 2: Split branch in `updateProjectiles`**

  At the top of the per-projectile loop, after position update (`p.x += p.vx * dt`), add:

  ```js
  // MP-M mid-flight split (Cluster Strike). When the parent missile has
  // traveled splitTotalDist from origin, despawn and spawn splitCount
  // homing children at the parent's current position with the parent's
  // damage and splash but with fresh homing-target acquisition.
  if (p.splitFraction > 0) {
    if (!p._splitFired) {
      var ddx = p.x - p.splitOriginX;
      var ddy = p.y - p.splitOriginY;
      var travDist = Math.sqrt(ddx * ddx + ddy * ddy);
      if (travDist >= p.splitTotalDist) {
        p._splitFired = true;
        // Spawn children. Each child is a homing projectile that re-acquires
        // a target on first tick. Visual: white-flash burst at split point
        // (Task 12 visual pass — for now we just push the children).
        for (var sc = 0; sc < p.splitCount; sc++) {
          var childAngle = Math.random() * Math.PI * 2;
          var childSpeed = Math.sqrt(p.vx * p.vx + p.vy * p.vy);
          game.projectiles.push({
            x: p.x, y: p.y,
            vx: Math.cos(childAngle) * childSpeed,
            vy: Math.sin(childAngle) * childSpeed,
            damage: p.damage,
            linePierce: 0,
            armorPierce: !!p.armorPierce,
            pierceLeft: 0,
            statusOnHit: p.statusOnHit,
            sourceCannon: p.sourceCannon,
            life: 1.5,
            homing: true,
            homingTurnRate: 6.0,
            target: null,    // will re-acquire on first tick
            splash: p.splash,
            visualScale: p.visualScale ? p.visualScale * 0.7 : 0.7,    // children are smaller
            _sawAnts: []
          });
        }
        // Push transient white-flash visual for the split moment.
        if (game.transientEffects) {
          game.transientEffects.push({
            kind: 'splitFlash',
            x: p.x, y: p.y,
            startTime: game.time,
            duration: 0.1,
            color: '#ffffff',
            maxRadius: 12
          });
        }
        // Despawn parent — skip the rest of this iteration without keeping it.
        p.life = -1;    // mark for sweep; continue to per-tick logic that culls life <= 0
        continue;
      }
    }
  }
  ```

- [ ] **Step 3: Verify children inherit splash**

  Cluster Strike has `splash: { radius: 30, damage: 12 }`. Each child must trigger splash on its hit. Check that the existing splash-trigger branch in `updateProjectiles` (~line 3277) reads `p.splash` from the projectile literal — adapt the child push to include `splash: p.splash` if it doesn't already.

- [ ] **Step 4: PHP lint + hygiene + Playwright smoke**

  Standard. No Cluster Strike yet, so this is a no-op until Task 16. Test that existing projectiles (Patriot, Mortar, etc.) don't regress.

- [ ] **Step 5: Commit**

  ```bash
  git add patterns/demo-ant-buster.php
  git commit -m "feat(ant-buster): MP-M mid-flight projectile split primitive (Plan 4 Task 10)"
  ```

---

### Task 11: MP-N persistent radial aura (Blizzard's no-target update branch)

**Files:**
- Modify: `patterns/demo-ant-buster.php` — `updateCannons` (search for the per-cannon update branch ~2820), `applyStatus` invocation pattern

The cannon *is* the AoE. No projectile, no fire-rate cooldown, no target acquisition.

- [ ] **Step 1: Find updateCannons branch**

  Search for the per-cannon update logic (between findTarget and fireProjectile). Plan 3 has cannons with various code paths (beam-direct, cone-DoT, normal projectile). Add a new MP-N branch.

- [ ] **Step 2: Add MP-N branch in updateCannons**

  At the top of the per-cannon update (before `findTarget`), check for `def.aura`:

  ```js
  // MP-N persistent radial aura (Blizzard). The cannon does not fire projectiles,
  // does not acquire a single target, and has no fire-rate cooldown. Every tick:
  // damage all ants in radius, re-apply slow with a 0.15s expiry, and pick a
  // uniform-random ant for freeze every aura.freezeRandomEvery seconds.
  if (def.aura) {
    var au = def.aura;
    // Per-tick damage + slow re-application
    for (var aai = 0; aai < game.ants.length; aai++) {
      var aaa = game.ants[aai];
      if (aaa.dead) { continue; }
      if (aaa.intangible) { continue; }
      var adx = aaa.x - c.x;
      var ady = aaa.y - c.y;
      if (adx * adx + ady * ady > au.radius * au.radius) { continue; }
      applyDamage(aaa, au.damagePerTick * dt, false, c);
      applyStatus(aaa, 'slow', au.slow, au.slowExpiry, 'aura');
      if (aaa.hp <= 0) { killAnt(aaa, c); }
    }
    // Random freeze every freezeRandomEvery seconds
    if (!c.lastFreezeAt) { c.lastFreezeAt = 0; }
    if (game.time - c.lastFreezeAt >= au.freezeRandomEvery) {
      var inRadius = [];
      for (var fai = 0; fai < game.ants.length; fai++) {
        var fa = game.ants[fai];
        if (fa.dead) { continue; }
        if (fa.intangible) { continue; }
        var fdx = fa.x - c.x;
        var fdy = fa.y - c.y;
        if (fdx * fdx + fdy * fdy > au.radius * au.radius) { continue; }
        inRadius.push(fa);
      }
      if (inRadius.length > 0) {
        var pick = inRadius[Math.floor(Math.random() * inRadius.length)];
        applyStatus(pick, 'freeze', 1, au.freezeDuration, 'aura-freeze');
      }
      c.lastFreezeAt = game.time;
    }
    continue;    // skip the rest of normal cannon update
  }
  ```

  This branch must come *before* `findTarget(c)` and the fire-rate cooldown logic.

- [ ] **Step 3: Verify slow status exists in STATUS_KINDS**

  Plan 3 should have `STATUS_KINDS.slow` from Frost / Glacier. Confirm it accepts magnitude (0.5 = 50% slow) and duration. If the existing status accepts `magnitude` as a fraction, no change. If it's an absolute speed multiplier, adjust the aura's `slow: 0.5` interpretation accordingly.

- [ ] **Step 4: PHP lint + hygiene + Playwright smoke**

  No Blizzard yet (lands in Task 25), so no behavior change. Confirm no console errors.

- [ ] **Step 5: Commit**

  ```bash
  git add patterns/demo-ant-buster.php
  git commit -m "feat(ant-buster): MP-N persistent radial aura primitive (Plan 4 Task 11)"
  ```

---

### Task 12: MP-O beam line-pierce (Solar Lance extension to MP-G)

**Files:**
- Modify: `patterns/demo-ant-buster.php` — beam tick logic in `updateCannons` (find Glacier / Lance pattern)

Solar Lance extends Plan 3's Lance (MP-G beam-direct). Every tick, the beam damages the primary target plus up to N ants whose centers fall within a thin cylinder along the tower→target line.

- [ ] **Step 1: Find existing beam-direct tick logic**

  Search for `Lance` or `def.beam` in `updateCannons`. Should be a per-tick damage application against the primary target.

- [ ] **Step 2: Add MP-O branch alongside the primary-target damage**

  After the primary-target `applyDamage` call inside the beam tick, add:

  ```js
  // MP-O beam line-pierce (Solar Lance). After the primary hit, damage up to
  // beamPierce additional ants whose centers fall within a thin cylinder
  // (cylinderHalfWidth ≈ 6px) along the tower→target line.
  if (def.beamPierce) {
    var pierceCount = def.beamPierce;
    var pierceHalfWidth = 6;
    var bdx = target.x - c.x;
    var bdy = target.y - c.y;
    var bdLen = Math.sqrt(bdx * bdx + bdy * bdy);
    if (bdLen > 0) {
      var bnx = bdx / bdLen;
      var bny = bdy / bdLen;
      var hits = 0;
      for (var bi = 0; bi < game.ants.length; bi++) {
        var ba = game.ants[bi];
        if (ba === target) { continue; }
        if (ba.dead) { continue; }
        if (ba.intangible) { continue; }
        var bax = ba.x - c.x;
        var bay = ba.y - c.y;
        // project onto beam line
        var proj = bax * bnx + bay * bny;
        if (proj < 0) { continue; }
        if (proj > bdLen) { continue; }
        // perpendicular distance
        var perp = bax * (-bny) + bay * bnx;
        if (Math.abs(perp) > pierceHalfWidth) { continue; }
        applyDamage(ba, def.beamTickDamage * dt, false, c);
        if (ba.hp <= 0) { killAnt(ba, c); }
        hits = hits + 1;
        if (hits >= pierceCount) { break; }
      }
    }
  }
  ```

  (Adapt `def.beamTickDamage` to whatever the existing beam reads — possibly `def.damage` or `def.tickDamage`.)

- [ ] **Step 3: PHP lint + hygiene + Playwright smoke**

  Standard. Solar Lance lands in Task 22.

- [ ] **Step 4: Commit**

  ```bash
  git add patterns/demo-ant-buster.php
  git commit -m "feat(ant-buster): MP-O beam line-pierce primitive (Plan 4 Task 12)"
  ```

---

### Task 13: Transient effects render path (split flash, spread ring, knockback ring)

**Files:**
- Modify: `patterns/demo-ant-buster.php` — `render` (~line 1052), add `drawTransientEffects` function

Three new transient visuals (`splitFlash`, `spreadRing`, `knockbackRing`) share an effect-entry shape pushed by their respective gameplay paths. Render path is shared.

- [ ] **Step 1: Add `drawTransientEffects` function**

  After the existing `drawLightningBolts` function (~line 1338), add:

  ```js
  function drawTransientEffects() {
    if (!game.transientEffects) { return; }
    var kept = [];
    for (var i = 0; i < game.transientEffects.length; i++) {
      var fx = game.transientEffects[i];
      var t = (game.time - fx.startTime) / fx.duration;
      if (t > 1) { continue; }    // expired, drop
      var alpha = 1 - t;
      var radius = fx.maxRadius * t;    // grows from 0 to maxRadius
      ctx.save();
      ctx.globalAlpha = alpha;
      if (fx.kind === 'splitFlash') {
        // White burst, radius shrinks from maxRadius to 0 (different shape)
        var srad = fx.maxRadius * (1 - t);
        ctx.fillStyle = fx.color;
        ctx.beginPath();
        ctx.arc(fx.x, fx.y, srad, 0, Math.PI * 2);
        ctx.fill();
      } else {
        // Ring expansion (spreadRing, knockbackRing) — stroked circle
        ctx.strokeStyle = fx.color;
        ctx.lineWidth = 2;
        ctx.beginPath();
        ctx.arc(fx.x, fx.y, radius, 0, Math.PI * 2);
        ctx.stroke();
      }
      ctx.restore();
      kept.push(fx);
    }
    game.transientEffects = kept;
  }
  ```

- [ ] **Step 2: Wire into `render()`**

  In the `render()` function, find the appropriate z-order layer (after projectiles, before UI). Add:

  ```js
  drawTransientEffects();
  ```

- [ ] **Step 3: PHP lint + hygiene + Playwright smoke**

  Standard. No transient effects fire yet (split flash from Task 10's Cluster Strike, spread ring from Task 4's burn.onExpire), so this is preparatory.

- [ ] **Step 4: Commit**

  ```bash
  git add patterns/demo-ant-buster.php
  git commit -m "feat(ant-buster): transient effects render path (Plan 4 Task 13)"
  ```

---

### Task 14: Biohazard cyan outline pulse on infected ants

**Files:**
- Modify: `patterns/demo-ant-buster.php` — `drawAnts` (~line 1167)

Bright cyan (`#00ffff`) outline around ants whose `effects.poison.damageAmp >= 1.25` (Biohazard signature). Sin-based alpha pulse, period ~0.6s.

- [ ] **Step 1: Locate per-ant draw**

  In `drawAnts`, find the per-ant render block. After the existing ant sprite + status icon overlays, before the closing of the loop, add:

  ```js
  // Biohazard infected outline (Plan 4). Bright cyan pulse identifying
  // ants currently amped by Biohazard's +25% damage taken.
  if (a.effects) {
    if (a.effects.poison) {
      if (a.effects.poison.damageAmp) {
        if (a.effects.poison.damageAmp >= 1.25) {
          var biAlpha = 0.65 + 0.25 * Math.sin(game.time * 10);    // period ~0.6s
          ctx.save();
          ctx.globalAlpha = biAlpha;
          ctx.strokeStyle = '#00ffff';
          ctx.lineWidth = 2;
          ctx.beginPath();
          ctx.arc(a.x, a.y, 10, 0, Math.PI * 2);
          ctx.stroke();
          ctx.restore();
        }
      }
    }
  }
  ```

- [ ] **Step 2: PHP lint + hygiene + Playwright smoke**

  Standard. No Biohazard yet (Task 28).

- [ ] **Step 3: Commit**

  ```bash
  git add patterns/demo-ant-buster.php
  git commit -m "feat(ant-buster): Biohazard cyan outline pulse on infected ants (Plan 4 Task 14)"
  ```

---

### Task 15: Blizzard aura ring render (translucent radial circle)

**Files:**
- Modify: `patterns/demo-ant-buster.php` — `drawCannonBases` (~line 1158) or `drawCannonTurrets` (~1251), add new `drawCannonAuras`

- [ ] **Step 1: Add `drawCannonAuras` function**

  After `drawCannonBases`, add:

  ```js
  function drawCannonAuras() {
    for (var i = 0; i < game.cannons.length; i++) {
      var c = game.cannons[i];
      var def = CANNON_KINDS[c.kind];
      if (!def.aura) { continue; }
      var au = def.aura;
      ctx.save();
      ctx.fillStyle = 'rgba(150, 200, 255, 0.15)';
      ctx.strokeStyle = 'rgba(150, 200, 255, 0.4)';
      ctx.lineWidth = 1;
      ctx.beginPath();
      ctx.arc(c.x, c.y, au.radius, 0, Math.PI * 2);
      ctx.fill();
      ctx.stroke();
      ctx.restore();
    }
  }
  ```

- [ ] **Step 2: Wire into render()**

  In `render()`, after the range circle layer (Plan 3 layer 4 area), but BEFORE the cannon turret sprite, add:

  ```js
  drawCannonAuras();
  ```

- [ ] **Step 3: PHP lint + hygiene + Playwright smoke**

  Standard. No Blizzard yet.

- [ ] **Step 4: Commit**

  ```bash
  git add patterns/demo-ant-buster.php
  git commit -m "feat(ant-buster): Blizzard aura ring render (Plan 4 Task 15)"
  ```

---

## Bucket D — Universal god-tier visual markers (Tasks 16–18)

### Task 16: Pulsing gold halo under T3 god-tier turrets

**Files:**
- Modify: `patterns/demo-ant-buster.php` — `drawCannonBases` (~line 1158) or new `drawGodTierHalos`

- [ ] **Step 1: Add `drawGodTierHalos` function**

  After `drawCannonAuras` (Task 15), add:

  ```js
  function drawGodTierHalos() {
    for (var i = 0; i < game.cannons.length; i++) {
      var c = game.cannons[i];
      var def = CANNON_KINDS[c.kind];
      if (def.tier !== 'god') { continue; }
      var alpha = 0.45 + 0.15 * Math.sin(game.time * Math.PI);    // period ~2s, range 0.30–0.60
      ctx.save();
      ctx.globalAlpha = alpha;
      ctx.fillStyle = '#fee761';
      ctx.beginPath();
      ctx.arc(c.x, c.y, 22, 0, Math.PI * 2);    // ~1.4× turret base
      ctx.fill();
      ctx.restore();
    }
  }
  ```

- [ ] **Step 2: Wire into render()**

  After `drawCannonAuras`, before the turret sprite layer:

  ```js
  drawGodTierHalos();
  ```

- [ ] **Step 3: Verify halo only fires for tier === 'god'**

  Add a god-tier cannon manually via console for testing (will be replaced in Bucket E):

  ```js
  return {
    nonGodCannons: game.cannons.filter(c => CANNON_KINDS[c.kind].tier !== 'god').length
  };
  ```

  Visually confirm: existing T2 finals (Railgun, Demolisher, etc.) have NO halo.

- [ ] **Step 4: Commit**

  ```bash
  git add patterns/demo-ant-buster.php
  git commit -m "feat(ant-buster): pulsing gold halo under T3 god-tier turrets (Plan 4 Task 16)"
  ```

---

### Task 17: `flashScale` field reading in `drawMuzzleFlash`

**Files:**
- Modify: `patterns/demo-ant-buster.php` — find `drawMuzzleFlash` or muzzle-flash render path

- [ ] **Step 1: Find existing muzzle flash render**

  Search for `muzzleFlash`, `flash`, or the radial-gradient call that renders cannon firing flash. Plan 3 should have an existing `drawMuzzleFlash(c)` or inline branch.

- [ ] **Step 2: Read `flashScale` from CANNON_KINDS**

  Wherever the flash arc radius is computed (e.g. `var flashRadius = 8`), multiply by:

  ```js
  var fScale = def.flashScale ? def.flashScale : 1.0;
  flashRadius = flashRadius * fScale;
  ```

  And similarly for any scaling of the gradient.

- [ ] **Step 3: PHP lint + hygiene**

  Standard.

- [ ] **Step 4: Commit**

  ```bash
  git add patterns/demo-ant-buster.php
  git commit -m "feat(ant-buster): drawMuzzleFlash reads flashScale field (Plan 4 Task 17)"
  ```

---

### Task 18: Screen shake hooks on Earthshaker / Annihilator + read `screenShakeMag`

**Files:**
- Modify: `patterns/demo-ant-buster.php` — fire-on-cooldown branch in `updateCannons` (find existing `screenshake` or `game.screenShake` references)

Plan 3's Shockwave already triggers screen shake. Earthshaker (mag 8) and Annihilator (mag 5) need the same hook. Reuses existing `game.screenShake` mechanism.

- [ ] **Step 1: Locate existing Shockwave screen-shake trigger**

  Search for `screenshake` or `screenShake`. Plan 3 has Shockwave's screenshake set in CANNON_KINDS. The fire pathway reads it and writes to `game.screenShake`.

- [ ] **Step 2: Generalize via `screenShakeMag` field**

  Update the fire-pathway branch to read either the new `screenShakeMag` field (number) or fall back to the existing nested `screenshake` object. Earthshaker and Annihilator's god-tier rows (Bucket E) will set `screenShakeMag: 8` and `5` respectively.

  (If Plan 3 used a nested object only, prefer keeping that for backward compat and have Earthshaker/Annihilator set both. Or unify cleanly.)

- [ ] **Step 3: PHP lint + hygiene**

  Standard.

- [ ] **Step 4: Commit**

  ```bash
  git add patterns/demo-ant-buster.php
  git commit -m "feat(ant-buster): screenShakeMag field on CANNON_KINDS (Plan 4 Task 18)"
  ```

---

## Bucket E — God-tier cannon configs (Tasks 19–21)

Three tasks, one per family. Each task ships six god-tier rows + their `upgradesTo` wiring on the parent T2 finals.

### Task 19: Heavy family — 6 god-tier rows

**Files:**
- Modify: `patterns/demo-ant-buster.php` — `CANNON_KINDS` (append rows + update parent T2 finals' `upgradesTo`)

- [ ] **Step 1: Append six rows**

  Append to CANNON_KINDS (after Plan 4's Bucket B Pink Rubber rows, before the closing `}`):

  ```js
  particle_cannon: {
    name: 'Particle Cannon',
    cost: 250,
    damage: 60,
    fireRate: 0.6,
    range: 140,
    projectileSpeed: 360,
    family: 'heavy',
    tier: 'god',
    linePierce: 999,
    armorPierce: true,
    flashScale: 1.7,
    upgradesTo: []
  },
  annihilator: {
    name: 'Annihilator',
    cost: 280,
    damage: 120,
    fireRate: 0.3,
    range: 130,
    projectileSpeed: 260,
    family: 'heavy',
    tier: 'god',
    armorPierce: true,
    splash: { radius: 60, falloff: 0.4, splashMult: 0.25 },    // base 120 dmg; splashMult yields 30 splash damage
    flashScale: 1.7,
    screenShakeMag: 5,
    upgradesTo: []
  },
  saturation: {
    name: 'Saturation',
    cost: 260,
    damage: 25,
    fireRate: 0.5,
    range: 200,
    projectileSpeed: 220,
    family: 'heavy',
    tier: 'god',
    barrels: 3,
    arc: { height: 32, travelTime: 0.7 },
    splash: { radius: 80, falloff: 0.4, splashMult: 0.8 },
    flashScale: 1.7,
    upgradesTo: []
  },
  cluster_strike: {
    name: 'Cluster Strike',
    cost: 280,
    damage: 30,
    fireRate: 0.5,
    range: 220,
    projectileSpeed: 240,
    family: 'heavy',
    tier: 'god',
    homing: { turnRate: 5.0 },
    splitFraction: 0.5,
    splitCount: 3,
    splash: { radius: 30, falloff: 0.4, splashMult: 0.4 },
    flashScale: 1.7,
    upgradesTo: []
  },
  vortex: {
    name: 'Vortex',
    cost: 250,
    damage: 12,
    fireRate: 0.4,
    range: 200,
    projectileSpeed: 240,
    family: 'heavy',
    tier: 'god',
    barrels: 2,
    returning: true,
    returnPierce: 2,
    flashScale: 1.7,
    upgradesTo: []
  },
  earthshaker: {
    name: 'Earthshaker',
    cost: 300,
    damage: 80,
    fireRate: 0.12,
    range: 140,
    projectileSpeed: 180,
    family: 'heavy',
    tier: 'god',
    splash: { radius: 120, falloff: 0.4, splashMult: 0.625 },
    splashStatus: true,
    statusOnHit: { kind: 'stun', magnitude: 1, duration: 0.6 },
    flashScale: 1.7,
    screenShakeMag: 8,
    upgradesTo: []
  },
  ```

- [ ] **Step 2: Update parent T2 finals' `upgradesTo`**

  Find each Heavy T2 final and add its god-tier ID:

  - `railgun.upgradesTo = ['particle_cannon']`
  - `demolisher.upgradesTo = ['annihilator']`
  - `hellfire.upgradesTo = ['saturation']` _(Note: Heavy Family branch has Hellfire under Mortar mid-tier; verify the upgrade tree)_
  - `patriot.upgradesTo = ['cluster_strike']`
  - `ricochet.upgradesTo = ['vortex']`
  - `shockwave.upgradesTo = ['earthshaker']`

  Verify each by reading the parent design doc §3.1 (which T2 final maps to which god).

- [ ] **Step 3: Verify barrel pattern for Saturation**

  Saturation needs 3-shell volley with 0.05s time-stagger. Plan 3 has multi-barrel in Triple Threat / Twin Gun. Confirm the existing `barrels: N` field handles staggered fire, or add a `barrelStagger: 0.05` field if needed.

- [ ] **Step 4: PHP lint + hygiene + Playwright verify**

  ```js
  return JSON.stringify({
    heavyGods: ['particle_cannon', 'annihilator', 'saturation', 'cluster_strike', 'vortex', 'earthshaker']
      .map(k => ({ k, exists: !!window.__abCannonKinds[k], tier: window.__abCannonKinds[k]?.tier }))
  });
  ```

  Expected: 6 rows, all `tier === 'god'`.

- [ ] **Step 5: Commit**

  ```bash
  git add patterns/demo-ant-buster.php
  git commit -m "feat(ant-buster): Heavy family god-tier (Plan 4 Task 19)"
  ```

---

### Task 20: Quick family — 6 god-tier rows

**Files:**
- Modify: `patterns/demo-ant-buster.php` — append rows + parent `upgradesTo`

- [ ] **Step 1: Append six rows**

  ```js
  hailstorm: {
    name: 'Hailstorm',
    cost: 250,
    damage: 4,
    fireRate: 14,
    range: 160,
    projectileSpeed: 360,
    family: 'quick',
    tier: 'god',
    spoolUp: 0.5,
    aimJitter: 0,
    flashScale: 1.7,
    upgradesTo: []
  },
  phoenix: {
    name: 'Phoenix',
    cost: 260,
    damage: 0,
    fireRate: 0,
    range: 80,
    projectileSpeed: 0,
    family: 'quick',
    tier: 'god',
    coneDot: {
      range: 80,
      angleDeg: 70,
      tickDamage: 12,
      statusOnTick: { kind: 'burn', magnitude: 3, duration: 3, spreads: true }
    },
    flashScale: 1.7,
    upgradesTo: []
  },
  reaper: {
    name: 'Reaper',
    cost: 280,
    damage: 60,
    fireRate: 0.5,
    range: 380,
    projectileSpeed: 360,
    family: 'quick',
    tier: 'god',
    damageModifier: function (target, sourceCannon) {
      // Instakill Workers and Scouts. +100% vs full-HP everything else.
      // Nested ifs to avoid the inline-script and-operator.
      if (target.kind === 'worker') { return 999; }
      if (target.kind === 'scout') { return 999; }
      if (target.hp >= target.maxHp - 0.001) { return 2; }
      return 1;
    },
    flashScale: 1.7,
    upgradesTo: []
  },
  solar_lance: {
    name: 'Solar Lance',
    cost: 290,
    damage: 0,
    fireRate: 0,
    range: 280,
    projectileSpeed: 0,
    family: 'quick',
    tier: 'god',
    beam: true,
    beamTickDamage: 8,
    beamPierce: 2,
    flashScale: 1.7,
    upgradesTo: []
  },
  hawkeye: {
    name: 'Hawkeye',
    cost: 300,
    damage: 80,
    fireRate: 0.3,
    range: 600,
    projectileSpeed: 400,
    family: 'quick',
    tier: 'god',
    minRange: 0,
    damageModifier: function (target, sourceCannon) {
      if (target.kind === 'queen') { return 3; }
      return 1;
    },
    flashScale: 1.7,
    upgradesTo: []
  },
  stormcaller: {
    name: 'Stormcaller',
    cost: 280,
    damage: 18,
    fireRate: 1,
    range: 240,
    projectileSpeed: 360,
    family: 'quick',
    tier: 'god',
    teslaArc: { jumps: 6, range: 80, falloff: 1 },
    flashScale: 1.7,
    upgradesTo: []
  },
  ```

- [ ] **Step 2: Update parent T2 finals' `upgradesTo`**

  - `vulcan.upgradesTo = ['hailstorm']`
  - `plasma_torch.upgradesTo = ['phoenix']`
  - `sharpshooter.upgradesTo = ['reaper']`
  - `lance.upgradesTo = ['solar_lance']`
  - `phantom.upgradesTo = ['hawkeye']`
  - `tesla.upgradesTo = ['stormcaller']`

- [ ] **Step 3: PHP lint + hygiene + Playwright verify**

  ```js
  return JSON.stringify({
    reaperOnWorker: window.__abCannonKinds.reaper.damageModifier({kind:'worker',hp:10,maxHp:10}),
    reaperOnKing: window.__abCannonKinds.reaper.damageModifier({kind:'king',hp:3000,maxHp:3000}),
    hawkeyeOnQueen: window.__abCannonKinds.hawkeye.damageModifier({kind:'queen',hp:800,maxHp:800}),
    hawkeyeOnKing: window.__abCannonKinds.hawkeye.damageModifier({kind:'king',hp:3000,maxHp:3000})
  });
  ```

  Expected: `reaperOnWorker = 999`, `reaperOnKing = 2`, `hawkeyeOnQueen = 3`, `hawkeyeOnKing = 1`.

- [ ] **Step 4: Commit**

  ```bash
  git add patterns/demo-ant-buster.php
  git commit -m "feat(ant-buster): Quick family god-tier (Plan 4 Task 20)"
  ```

---

### Task 21: Double family — 6 god-tier rows

**Files:**
- Modify: `patterns/demo-ant-buster.php` — append rows + parent `upgradesTo`

- [ ] **Step 1: Append six rows**

  ```js
  scattergun: {
    name: 'Scattergun',
    cost: 260,
    damage: 3,
    fireRate: 0.7,
    range: 100,
    projectileSpeed: 320,
    family: 'double',
    tier: 'god',
    pellets: 8,
    spreadDeg: 70,
    linePierce: 1,
    flashScale: 1.7,
    upgradesTo: []
  },
  phalanx: {
    name: 'Phalanx',
    cost: 280,
    damage: 2,
    fireRate: 18,
    range: 140,
    projectileSpeed: 380,
    family: 'double',
    tier: 'god',
    barrels: 3,
    aimJitter: 0,
    flashScale: 1.7,
    upgradesTo: []
  },
  blizzard: {
    name: 'Blizzard',
    cost: 260,
    damage: 0,
    fireRate: 0,
    range: 150,
    projectileSpeed: 0,
    family: 'double',
    tier: 'god',
    aura: {
      radius: 150,
      damagePerTick: 60,    // 1 dmg per render-tick at 60fps; scaled by dt in updateCannons
      slow: 0.5,
      slowExpiry: 0.15,
      freezeRandomEvery: 5,
      freezeDuration: 2
    },
    flashScale: 1.7,
    upgradesTo: []
  },
  absolute_zero: {
    name: 'Absolute Zero',
    cost: 280,
    damage: 6,
    fireRate: 1,
    range: 130,
    projectileSpeed: 280,
    family: 'double',
    tier: 'god',
    statusOnHit: { kind: 'freeze', magnitude: 1, duration: 1.5, damageAmp: 1.5 },
    splash: { radius: 40, falloff: 0, splashMult: 0 },    // splash for splashStatus only, no damage
    splashStatus: true,
    flashScale: 1.7,
    upgradesTo: []
  },
  biohazard: {
    name: 'Biohazard',
    cost: 260,
    damage: 4,
    fireRate: 1,
    range: 130,
    projectileSpeed: 260,
    family: 'double',
    tier: 'god',
    statusOnHit: { kind: 'poison', magnitude: 30 / 8, duration: 8, spreads: true, spreadRadius: 50, damageAmp: 1.25 },
    flashScale: 1.7,
    upgradesTo: []
  },
  tempest: {
    name: 'Tempest',
    cost: 260,
    damage: 10,
    fireRate: 0.7,
    range: 110,
    projectileSpeed: 280,
    family: 'double',
    tier: 'god',
    splash: { radius: 50, falloff: 0, splashMult: 0 },    // splash for splashStatus only
    splashStatus: true,
    statusOnHit: [
      { kind: 'knockback', magnitude: 50, duration: 0 },
      { kind: 'stun', magnitude: 1, duration: 0.8 }
    ],
    flashScale: 1.7,
    upgradesTo: []
  },
  ```

- [ ] **Step 2: Update parent T2 finals' `upgradesTo`**

  - `buckshot.upgradesTo = ['scattergun']`
  - `minigun.upgradesTo = ['phalanx']`
  - `glacier.upgradesTo = ['blizzard']`
  - `cryo.upgradesTo = ['absolute_zero']`
  - `plague.upgradesTo = ['biohazard']`
  - `sonic_pulse.upgradesTo = ['tempest']`

- [ ] **Step 3: Verify Plague's `spreadRadius` honored by spread**

  Plan 3's `STATUS_KINDS.poison.onTargetDeath` uses a hardcoded 30px radius. Biohazard wants 50px. Update the hook to read `ant.effects.poison.spreadRadius` if set, else default to 30:

  ```js
  // Inside STATUS_KINDS.poison.onTargetDeath:
  var rad = 30;
  if (ant.effects.poison.spreadRadius) { rad = ant.effects.poison.spreadRadius; }
  ```

- [ ] **Step 4: PHP lint + hygiene + Playwright verify**

  ```js
  return JSON.stringify({
    blizzardAura: window.__abCannonKinds.blizzard.aura,
    biohazardPoison: window.__abCannonKinds.biohazard.statusOnHit,
    tempestStatuses: window.__abCannonKinds.tempest.statusOnHit
  });
  ```

  Expected: blizzard.aura present, biohazard's `damageAmp: 1.25` present, tempest array of 2.

- [ ] **Step 5: Commit**

  ```bash
  git add patterns/demo-ant-buster.php
  git commit -m "feat(ant-buster): Double family god-tier (Plan 4 Task 21)"
  ```

---

## Bucket F — Unlock UI (Tasks 22–24)

### Task 22: Selected-tower-panel god-tier upgrade button (locked + unlocked + pulse)

**Files:**
- Modify: `patterns/demo-ant-buster.php` — `drawTowerPanel` (~line 1028)

The panel already supports the generic 1-button case from Plan 3 §9 (Pink Pea / Bouncy Bubble linear chain). Plan 4 needs three additions:
1. When the upgrade target's `tier === 'god'` and `!game.godTierUnlocked`, render at 50% alpha + `"Unlocks at wave 15"` tooltip.
2. When unlocked, render at 100% alpha (existing path).
3. During `game.time - game.godTierUnlockPulseStart < 0.5`, render an additive golden halo behind the button.

- [ ] **Step 1: Find existing upgrade-button render**

  In `drawTowerPanel`, locate the per-upgrade-button render block (the 1/2/3-button generic loop from Plan 3).

- [ ] **Step 2: Add tier check + alpha + tooltip**

  Inside the per-button block, before the existing render:

  ```js
  var isGod = (udef.tier === 'god');
  var isLocked = (isGod && !game.godTierUnlocked);
  ctx.save();
  if (isLocked) { ctx.globalAlpha = 0.5; }
  // (existing button render — fill, stroke, label)
  ctx.restore();
  ```

- [ ] **Step 3: Add unlock pulse halo**

  Wrap the button rendering in a halo-pre-pass when applicable:

  ```js
  var pulseAge = game.time - game.godTierUnlockPulseStart;
  if (isGod && pulseAge < 0.5 && pulseAge >= 0) {
    var pulseAlpha = 1 - (pulseAge / 0.5);
    ctx.save();
    ctx.globalAlpha = pulseAlpha;
    ctx.fillStyle = '#fee761';
    var bx = btnRect.x;
    var by = btnRect.y;
    var bw = btnRect.w;
    var bh = btnRect.h;
    ctx.fillRect(bx - 4, by - 4, bw + 8, bh + 8);
    ctx.restore();
  }
  ```

  (Adapt `btnRect` to the actual variable name in the existing render block.)

- [ ] **Step 4: Add tooltip for locked god-tier on hover**

  Search for the tooltip / hover-feedback path. Plan 3 may have a generic `hoverTooltip` helper. If a button is locked and hovered, render `"Unlocks at wave 15"` text above it:

  ```js
  if (isLocked) {
    if (ui.hover) {
      // Compare ui.hover with btnRect bounds; render tooltip if hovering.
      ctx.save();
      ctx.fillStyle = '#222';
      ctx.fillRect(btnRect.x, btnRect.y - 24, 140, 18);
      ctx.fillStyle = '#fff';
      ctx.font = '11px sans-serif';
      ctx.fillText('Unlocks at wave 15', btnRect.x + 8, btnRect.y - 11);
      ctx.restore();
    }
  }
  ```

  Adapt to the existing tooltip system if one already exists in Plan 3.

- [ ] **Step 5: PHP lint + hygiene + Playwright verify**

  Verify locked state rendering:

  ```js
  // Force a Sharpshooter to be selected
  // Check that the panel shows a Reaper button at 50% alpha
  // (visual verification via screenshot)
  ```

  Then via `__abGodTierUnlocked(true)`, verify the button un-greys.

- [ ] **Step 6: Commit**

  ```bash
  git add patterns/demo-ant-buster.php
  git commit -m "feat(ant-buster): god-tier upgrade button locked/unlocked/pulse render (Plan 4 Task 22)"
  ```

---

### Task 23: `?debug=1 G` hotkey — toggle godTierUnlocked

**Files:**
- Modify: `patterns/demo-ant-buster.php` — keydown handler (~line 3895)

- [ ] **Step 1: Find keydown handler**

  Line 3895: `window.addEventListener('keydown', function (e) {`. Read surrounding context to see existing `?debug=1 T` handler shape.

- [ ] **Step 2: Add `G` branch**

  Inside the keydown handler, after the existing `T` branch:

  ```js
  if (e.key === 'g' || e.key === 'G') {
    // ?debug=1 G: toggle god-tier unlock state. When toggling true, also
    // stamp pulseStart so the visual pulse fires for testing.
    if (game.debug) {
      game.godTierUnlocked = !game.godTierUnlocked;
      if (game.godTierUnlocked) {
        game.godTierUnlockPulseStart = game.time;
      }
    }
  }
  ```

  (`game.debug` is the existing flag set when `?debug=1` is in the URL.)

- [ ] **Step 3: Inline-JS gotcha — replace `||` in the `if (e.key === 'g' || e.key === 'G')` line**

  Replace with:

  ```js
  var keyG = false;
  if (e.key === 'g') { keyG = true; }
  if (e.key === 'G') { keyG = true; }
  if (keyG) {
    // ... toggle logic
  }
  ```

- [ ] **Step 4: PHP lint + hygiene + Playwright verify**

  Navigate to `?debug=1`, press `G`, verify state via `window.__ab.godTierUnlocked` flips.

- [ ] **Step 5: Commit**

  ```bash
  git add patterns/demo-ant-buster.php
  git commit -m "feat(ant-buster): ?debug=1 G hotkey toggles godTierUnlocked (Plan 4 Task 23)"
  ```

---

### Task 24: `?debug=1 W` hotkey — jump to wave 16 prep with unlock

**Files:**
- Modify: `patterns/demo-ant-buster.php` — keydown handler (~line 3895)

- [ ] **Step 1: Add `W` branch**

  ```js
  var keyW = false;
  if (e.key === 'w') { keyW = true; }
  if (e.key === 'W') { keyW = true; }
  if (keyW) {
    if (game.debug) {
      // Soft-reset to wave 16 prep with god-tier unlocked. Intended for
      // post-implementation playtest where iterating on King-fight feel
      // without grinding 15 waves matters.
      game.ants = [];
      game.projectiles = [];
      game.lightningBolts = [];
      game.transientEffects = [];
      game.spawnJobs = [];
      game.waveIndex = 15;
      game.phase = 'prep';
      game.prepTimer = 20;
      game.godTierUnlocked = true;
      game.godTierUnlockPulseStart = game.time;
    }
  }
  ```

- [ ] **Step 2: Verify field names match resetGame**

  Look at `resetGame` to see what fields exist (`game.phase`, `game.prepTimer`, `game.waveIndex`). Adjust the W-handler to match exactly.

- [ ] **Step 3: PHP lint + hygiene + Playwright verify**

  Navigate to `?debug=1`. Press `W`. Verify:

  ```js
  return JSON.stringify({
    waveIndex: game.waveIndex,
    phase: game.phase,
    unlocked: game.godTierUnlocked,
    antsCleared: game.ants.length === 0
  });
  ```

  Expected: `waveIndex: 15`, `phase: 'prep'`, `unlocked: true`, `antsCleared: true`.

- [ ] **Step 4: Commit**

  ```bash
  git add patterns/demo-ant-buster.php
  git commit -m "feat(ant-buster): ?debug=1 W hotkey jump to wave 16 + unlock (Plan 4 Task 24)"
  ```

---

## Bucket G — Hygiene (Tasks 25–26)

### Task 25: Sweep remaining `||` operators in inline JS

**Files:**
- Modify: `patterns/demo-ant-buster.php` — wherever `||` instances live in inline JS

- [ ] **Step 1: Find all remaining `||` in served HTML**

  ```bash
  curl -s http://localhost/dariobimbi.com/demos/ant-buster/ | grep -F '&#124;&#124;'
  ```

  Expected (before sweep): some output — the six remaining instances.

- [ ] **Step 2: Locate source occurrences**

  Use Grep with pattern `\|\|` against `patterns/demo-ant-buster.php`. Each match is a candidate for replacement. Distinguish:
  - `||` inside `<script>` block: must be replaced with verbose form.
  - `||` inside PHP comments or HTML attributes: leave alone.

- [ ] **Step 3: Replace each `a || b` with verbose form**

  Pattern:

  ```js
  // before:
  var x = a || b;
  // after:
  var x = a;
  if (!x) { x = b; }
  ```

  For each occurrence, choose the form that reads cleanly given context.

- [ ] **Step 4: PHP lint + hygiene check**

  ```bash
  c:/wamp64/bin/php/php8.2.29/php.exe -l c:/wamp64/www/dariobimbi.com/wp-content/themes/dariobimbi/patterns/demo-ant-buster.php
  curl -s http://localhost/dariobimbi.com/demos/ant-buster/ | grep -c '&#124;&#124;'
  ```

  Expected: `0`.

- [ ] **Step 5: Playwright smoke**

  Navigate, walk through wave 1, no console errors.

- [ ] **Step 6: Commit**

  ```bash
  git add patterns/demo-ant-buster.php
  git commit -m "fix(ant-buster): sweep remaining || operators in inline JS (Plan 4 Task 25)"
  ```

---

### Task 26: Slice timeout simDt scaling

**Files:**
- Modify: `patterns/demo-ant-buster.php` — `updateSlices` or wherever slice timeout is computed (search for `droppedAt` or `slice.timeout`)

Plan 3 status doc §5: slice timeouts read `game.time` instead of the simDt-scaled accumulator. None of Plan 4's mechanics depend on slice age, but the technical debt is small.

- [ ] **Step 1: Find slice timeout logic**

  Grep for `droppedAt` and `8 * 1000` or `slice.timeout`. Locate the per-tick check.

- [ ] **Step 2: Identify the simDt accumulator**

  Search for `simTime` or `simDt` accumulator. Plan 3 should have a `game.simTime` separate from `game.time` (real-time vs simulated-time). If not, the change is to add a new `game.simTime` accumulator that increments by `dt * speedMult` each tick, where `speedMult` is the existing 0.5×/1×/2× game speed multiplier.

- [ ] **Step 3: Switch slice's `droppedAt` reference to `simTime`**

  In `updateSlices`, replace:

  ```js
  if (game.time - slice.droppedAt > 8) { ... }
  ```

  with:

  ```js
  if (game.simTime - slice.droppedAt > 8) { ... }
  ```

  And in `killAnt` where slices are pushed, set `droppedAt: game.simTime`.

- [ ] **Step 4: Initialize `game.simTime`**

  In `resetGame`, alongside `game.time = 0`:

  ```js
  game.simTime = 0;
  ```

  And in the main update loop, increment `game.simTime += dt * (current speed multiplier)` each tick.

- [ ] **Step 5: Playwright verify at 0.5× and 2× speed**

  Spawn a slice (kill a carrying carrier in front of the cake). At 0.5× speed, slice should expire after 16 wall-clock seconds (8 sim-seconds). At 2× speed, slice should expire after 4 wall-clock seconds.

- [ ] **Step 6: Commit**

  ```bash
  git add patterns/demo-ant-buster.php
  git commit -m "fix(ant-buster): slice timeout reads simDt accumulator (Plan 4 Task 26)"
  ```

---

## Bucket H — Final QA + status doc (Tasks 27–29)

### Task 27: Theme `Version:` bump

**Files:**
- Modify: `wp-content/themes/dariobimbi/style.css` (line 7)

- [ ] **Step 1: Read current version**

  Existing `Version: 0.9.2`. Bump to `0.9.3` (Plan 3) — wait, Plan 3 ended at `0.9.2` per Plan 3 status doc. Bump to `0.10.0` for Plan 4 (minor version reflecting 18 god-tier cannons).

  Actually, follow the established pattern: Plan 3 was `0.9.0 → 0.9.2` (two small bumps within Plan 3). Plan 4 = `0.10.0`.

- [ ] **Step 2: Edit style.css**

  Replace `Version: 0.9.2` with `Version: 0.10.0`.

- [ ] **Step 3: Verify pattern cache invalidates**

  Navigate to `/wp-admin/`. Confirm no fatal errors. Plan 3 status notes `inc/auto-pages.php` and `inc/sitemap.php` reconciliation triggers on version change — those should re-fire on next admin pageview.

- [ ] **Step 4: Commit**

  ```bash
  git add style.css
  git commit -m "chore(theme): bump version to 0.10.0 for Plan 4 (Plan 4 Task 27)"
  ```

---

### Task 28: Playwright acceptance walk against §6 of the spec

**Files:**
- Read-only verification + small ad-hoc edits if any acceptance criterion fails

- [ ] **Step 1: Navigate to `?debug=1` (locked state)**

  Verify items 1–9 (god-tier cannons) by inspecting `__abCannonKinds`. All 18 god-tier rows present with `tier: 'god'`. Reaper / Hawkeye damageModifier shapes correct.

- [ ] **Step 2: Verify Bucket G items 28–32**

  Build a Pink Pea, place it. Verify projectile bounces. Upgrade chain to Wrecking Ball. Knock back ants on first hit but not on bounces.

- [ ] **Step 3: Verify wave-15 unlock items 20–27**

  Press `?debug=1 W` to jump to wave 16 with unlock. Verify panel renders the god-tier button at 100% alpha. Force-reset (full restart), build a Sharpshooter, verify panel shows greyed Reaper button with tooltip.

- [ ] **Step 4: Verify visual treatment items 33–38**

  Visual screenshot of placed Particle Cannon (gold halo). Force-build a Blizzard via debug (replace a placed Glacier), verify aura ring renders. Verify Biohazard cyan outline on infected ants by chip-poisoning a Worker.

- [ ] **Step 5: Verify hygiene items 39–42**

  ```bash
  curl -s http://localhost/dariobimbi.com/demos/ant-buster/ | grep -c '&#124;&#124;'
  curl -s http://localhost/dariobimbi.com/demos/ant-buster/ | grep -c '&#038;&#038;'
  ```

  Expected: both `0`. Console errors during a wave 1–25 walk: 0.

- [ ] **Step 6: Run full wave 1–25 playthrough at `?debug=1`**

  Watch for console errors. Confirm transitions, banners, wave-15 unlock fires correctly.

- [ ] **Step 7: Fix any failures inline**

  If any acceptance item fails, fix the specific code path in a small follow-up commit (`fix(ant-buster): … (Plan 4 acceptance)`).

- [ ] **Step 8: Commit (if any fixes were made)**

  Otherwise no commit needed — this task is verification.

---

### Task 29: Plan 4 status doc

**Files:**
- Create: `docs/superpowers/plans/2026-05-08-ant-buster-plan-4-status.md`

Mirror the shape of Plan 3's status doc.

- [ ] **Step 1: Create status doc**

  Use Plan 3 status doc as template. Sections:
  - What shipped (commits chronological)
  - Acceptance criteria walkthrough (table mapping each §6 item to PASS/FAIL with notes)
  - Known divergences from spec
  - Open issues for Plan 5
  - Workflow notes for Plan 5
  - What this Plan 4 release demonstrates

- [ ] **Step 2: Verify all 42 acceptance criteria addressed**

  Each must have a PASS row with the verification method (Playwright script, curl-grep, visual inspection).

- [ ] **Step 3: Document any divergences from spec**

  E.g., the `tier` string-vs-number migration, any de-scoped god-tier visuals, any post-implementation playtest findings on the King fight.

- [ ] **Step 4: Document the Option C playtest results**

  After the manual playthrough (Task 28 step 6), document whether King at 3000 HP held up (Option C) or whether Option D phase shield needs to ship in Plan 5.

- [ ] **Step 5: Commit**

  ```bash
  git add docs/superpowers/plans/2026-05-08-ant-buster-plan-4-status.md
  git commit -m "docs(ant-buster): Plan 4 status — shipped (Task 29)"
  ```

---

## Summary — task count + commit shape

29 tasks total across 8 buckets:
- **Bucket A** (Architecture): 5 tasks (Tasks 1–5)
- **Bucket B** (Pink Rubber Family): 4 tasks (Tasks 6–9)
- **Bucket C** (New mechanic primitives + visuals): 6 tasks (Tasks 10–15)
- **Bucket D** (Universal god-tier visual markers): 3 tasks (Tasks 16–18)
- **Bucket E** (God-tier cannon configs by family): 3 tasks (Tasks 19–21)
- **Bucket F** (Unlock UI): 3 tasks (Tasks 22–24)
- **Bucket G** (Hygiene): 2 tasks (Tasks 25–26)
- **Bucket H** (QA + status doc): 3 tasks (Tasks 27–29)

29 commits projected (some tasks may bundle when small fixes follow). Spec estimate was ~31 tasks; this plan lands at 29 by consolidating Bucket E into per-family commits and Bucket H's QA into a verification-only task. Realistic landed range: **27–32 commits**, in line with Plan 3's 30.

The writing-plans pass might further consolidate (e.g. roll Task 17 `flashScale` and Task 18 `screenShakeMag` into a single "T3 visual data fields" commit) or split (e.g. Task 22's panel render is dense — could split into "locked state" and "pulse halo" sub-tasks). Subagent-driven execution should make those calls per task.

End of Plan 4 implementation plan.
