# Ant Buster Plan 6 Implementation Plan — Brittle Synergy

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Heavy-family direct hit on a frozen ant deals 3× damage (composing multiplicatively with Plan 3's `freeze.damageAmp: 1.5×`), consumes the freeze status, and spawns a cyan shatter visual.

**Architecture:** Single-file change in `patterns/demo-ant-buster.php`. Adds a 5th `isDirectHit` parameter to `applyDamage`, slots a Brittle predicate after the existing damageAmp blocks but before armor mitigation, and adds a lightweight cyan particle system tied to **real-time `dt`** (not `simDt`) so the visual stays readable at 8× game speed. No new schemas, no new templates, no `theme.json` changes.

**Tech Stack:** Vanilla JS (IIFE inside a PHP pattern), Canvas 2D, no build step. WAMP-served WordPress theme. Verification via Playwright MCP against `http://localhost/dariobimbi.com/demos/ant-buster/?debug=1`.

**Spec reference:** [`docs/superpowers/specs/2026-05-08-ant-buster-plan-6-design.md`](../specs/2026-05-08-ant-buster-plan-6-design.md) (committed to `main` as `36ca4c5`).

**Theme version target:** 0.11.0 → 0.12.0.

---

## File Structure

| File | Action | Why |
|---|---|---|
| `wp-content/themes/dariobimbi/patterns/demo-ant-buster.php` | Modify | All mechanic + visual code lives here. ~50 lines added across Tasks 1-3. |
| `wp-content/themes/dariobimbi/style.css` | Modify | Theme `Version:` field, bumped 0.11.0 → 0.12.0 (Task 4). |
| `wp-content/themes/dariobimbi/docs/superpowers/plans/2026-05-08-ant-buster-plan-6-status.md` | Create | Status doc with playtest results, acceptance walkthrough, architectural decisions (Task 5). |
| `C:\Users\dario\.claude\projects\c--wamp64-www-dariobimbi-com-wp-content-themes-dariobimbi\memory\ant_buster_plan_6_shipped.md` | Create | Auto-memory record (Task 6). |
| `C:\Users\dario\.claude\projects\c--wamp64-www-dariobimbi-com-wp-content-themes-dariobimbi\memory\MEMORY.md` | Modify | Add index entry for the new memory file (Task 6). |

---

## Pre-flight

- [ ] **Step 0.1: Confirm starting state.** On `main`, working tree clean, last commit is the Plan 6 spec doc.

```bash
git status
git log --oneline -1
```

Expected output:
```
On branch main
nothing to commit, working tree clean
36ca4c5 docs(ant-buster): Plan 6 design spec — Brittle synergy
```

- [ ] **Step 0.2: Create implementation branch from the spec commit.**

```bash
git checkout -b ant-buster-plan-6
```

Expected: `Switched to a new branch 'ant-buster-plan-6'`.

- [ ] **Step 0.3: Confirm WAMP is serving the demo.** Visit `http://localhost/dariobimbi.com/demos/ant-buster/?debug=1` in a browser (or via Playwright) and confirm the page loads with "Click to start" overlay.

---

## Task 1: Particle system (state, factory, update, draw, wire-in)

**Goal:** Stand up the cyan particle system in isolation. Brittle predicate (Task 2) will call `spawnShatterParticles`; this task makes sure the function and the supporting state/loops exist *before* the predicate references them.

**Files:**
- Modify: `wp-content/themes/dariobimbi/patterns/demo-ant-buster.php` (state init line, helper-function block, main update loop, main render loop)

**Spec reference:** §5 (particle specs), §6.1, §6.2, §6.6, §6.7, §7 case 6 (real-time `dt`).

---

- [ ] **Step 1.1: Locate the game-state init block.** Find where `game.ants`, `game.projectiles`, `game.slices` are first initialized as `[]`.

Run:

```bash
grep -n "game.ants = \[\]" patterns/demo-ant-buster.php
grep -n "game.slices = \[\]" patterns/demo-ant-buster.php
```

The output identifies the state-init block. Note the line number for the next edit.

---

- [ ] **Step 1.2: Add `game.shatterParticles = []` to the state init block.** Insert immediately after the `game.slices = []` line (or alongside the other empty-array game-state collections).

Insert this exact line (with the inline comment from the spec):

```js
// game.shatterParticles is ephemeral; do not persist if Save State is added later.
game.shatterParticles = [];
```

---

- [ ] **Step 1.3: Locate the helper-function area.** Find where `spawnAntAt` is defined (it's the conventional home for top-level spawn helpers).

Run:

```bash
grep -n "function spawnAntAt" patterns/demo-ant-buster.php
```

The output gives the line where `spawnAntAt` is defined. The new factory will be inserted just after it (or just before the status-loop logic, whichever is earlier in the file structure).

---

- [ ] **Step 1.4: Insert the `spawnShatterParticles` factory.**

Insert this exact block in the helper area identified in Step 1.3:

```js
// Plan 6: Shatter particle factory. Called by the Brittle predicate inside
// applyDamage when a heavy-family direct hit lands on a frozen ant. Pushes
// 10 cyan particles onto game.shatterParticles with random radial velocity.
// The particles tick on real-time dt (see updateShatterParticles below) so
// the visual stays readable at any game speed.
function spawnShatterParticles(x, y) {
  for (var i = 0; i < 10; i++) {
    var angle = Math.random() * Math.PI * 2;
    var speed = 80 + Math.random() * 60;
    game.shatterParticles.push({
      x: x,
      y: y,
      vx: Math.cos(angle) * speed,
      vy: Math.sin(angle) * speed,
      age: 0,
      lifetime: 0.18
    });
  }
}
```

---

- [ ] **Step 1.5: Insert the `updateShatterParticles` tick function.**

Place it adjacent to `spawnShatterParticles` (so the spawn / update / draw trio reads as one unit).

```js
// Plan 6: Particle tick. Caller passes real-time dt (NOT simDt) so the burst
// always lasts 0.18s wall-time regardless of game speed. Reverse iteration
// so splice(i, 1) doesn't break the index — same convention as
// updateProjectiles and the slices loop elsewhere in this file.
function updateShatterParticles(dt) {
  for (var i = game.shatterParticles.length - 1; i >= 0; i--) {
    var p = game.shatterParticles[i];
    p.x = p.x + p.vx * dt;
    p.y = p.y + p.vy * dt;
    p.vx = p.vx * 0.92;
    p.vy = p.vy * 0.92;
    p.age = p.age + dt;
    if (p.age >= p.lifetime) {
      game.shatterParticles.splice(i, 1);
    }
  }
}
```

---

- [ ] **Step 1.6: Insert the `drawShatterParticles` render function.**

Place it adjacent to the update tick.

```js
// Plan 6: Particle draw. Linear alpha fade over lifetime. Math.round on the
// position to dodge subpixel blur on the 320x180 canvas. fillStyle hex
// literal — no operator-encoding concerns.
function drawShatterParticles(ctx) {
  for (var i = 0; i < game.shatterParticles.length; i++) {
    var p = game.shatterParticles[i];
    var alpha = 1 - (p.age / p.lifetime);
    ctx.save();
    ctx.globalAlpha = alpha;
    ctx.fillStyle = '#88f0ff';
    ctx.fillRect(Math.round(p.x) - 1, Math.round(p.y) - 1, 2, 2);
    ctx.restore();
  }
}
```

---

- [ ] **Step 1.7: Wire `updateShatterParticles(dt)` into the main update loop.**

The screenshake decay at lines 5010-5015 uses real-time `dt` *before* the simDt computation at line 5017. That's the canonical "real-time visual juice" zone. Insert the shatter-particles update immediately after the screenshake-decay block, before `game.time += dt` at line 5016.

Locate the screenshake decay block:

```bash
grep -n "Screenshake decay" patterns/demo-ant-buster.php
```

Expected: line ~5010.

Read lines 5005-5020 to confirm the structure, then insert this single line after the `}` that closes the `if (game.screenshake.magnitude > 0)` block (after line 5015), before `game.time += dt` (line 5016):

```js
    updateShatterParticles(dt);
```

(Indentation matches the surrounding lines: 4 spaces of leading indent.)

---

- [ ] **Step 1.8: Wire `drawShatterParticles(ctx)` into the main render loop.**

Find where `drawAnts()` is called from the render pass:

```bash
grep -n "drawAnts(" patterns/demo-ant-buster.php
```

There will be a call site (typically inside a `render()` or `draw()` function, around line 1900+). Insert this line immediately after the `drawAnts(...)` call:

```js
    drawShatterParticles(ctx);
```

(Indentation matches surrounding lines.)

---

- [ ] **Step 1.9: PHP lint check.** Confirm no syntax errors were introduced.

```bash
c:/wamp64/bin/php/php8.2.29/php.exe -l patterns/demo-ant-buster.php
```

Expected: `No syntax errors detected in patterns/demo-ant-buster.php`.

If the PHP path drifts (WAMP updated PHP), run `ls c:/wamp64/bin/php` to find the current version.

---

- [ ] **Step 1.10: Hygiene check (operator encoding).**

```bash
curl -s "http://localhost/dariobimbi.com/demos/ant-buster/" | grep -c '&#038;&#038;'
curl -s "http://localhost/dariobimbi.com/demos/ant-buster/" | grep -c '&amp;&amp;'
```

Expected: both return `0`.

If either returns >0, the inline-script `&&` encoding gotcha bit. Find the offending block, rewrite using nested ifs (per CLAUDE.md and spec §10.8), re-run.

---

- [ ] **Step 1.11: Playwright probe — particle system exists.**

Navigate to `http://localhost/dariobimbi.com/demos/ant-buster/?debug=1`. Click the canvas to dismiss the "Click to start" overlay. Then evaluate in browser context:

```js
// Probe 1: state init
JSON.stringify({
  shatterParticlesType: Array.isArray(window.__ab.shatterParticles),
  shatterParticlesLength: window.__ab.shatterParticles.length
});
```

Expected: `{"shatterParticlesType":true,"shatterParticlesLength":0}`.

```js
// Probe 2: directly push a fake particle, run a frame, confirm tick runs
window.__ab.shatterParticles.push({
  x: 100, y: 100, vx: 50, vy: 0, age: 0, lifetime: 0.18
});
var lengthBefore = window.__ab.shatterParticles.length;
// wait for next frame via promise
new Promise(function (resolve) {
  requestAnimationFrame(function () {
    requestAnimationFrame(function () {
      var p = window.__ab.shatterParticles[0];
      resolve({
        before: lengthBefore,
        after: window.__ab.shatterParticles.length,
        ageAdvanced: p ? p.age > 0 : false,
        positionAdvanced: p ? p.x > 100 : false
      });
    });
  });
});
```

Expected: `{"before":1,"after":1,"ageAdvanced":true,"positionAdvanced":true}`.

If `ageAdvanced` is `false`, the update tick wasn't wired into the loop — re-check Step 1.7. If `positionAdvanced` is false, the velocity math isn't applying — re-check Step 1.5.

---

- [ ] **Step 1.12: Commit Task 1.**

```bash
git add patterns/demo-ant-buster.php
git commit -m "$(cat <<'EOF'
feat(ant-buster): shatter particle system (Plan 6 Task 1)

Adds game.shatterParticles state init, spawnShatterParticles factory,
updateShatterParticles tick (real-time dt), and drawShatterParticles
render. Wired into the main update loop alongside screenshake decay
and into the render pass after drawAnts. Brittle predicate (Task 2)
will call spawnShatterParticles when a heavy-family direct hit lands
on a frozen ant.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

Verify with `git log --oneline -1` — first line should be the commit subject.

---

## Task 2: `applyDamage` signature change + Brittle predicate

**Goal:** Add the 5th `isDirectHit` parameter to `applyDamage` and insert the Brittle predicate block between the existing damageAmp blocks and the armor mitigation block.

**Files:**
- Modify: `wp-content/themes/dariobimbi/patterns/demo-ant-buster.php` (lines 4034 and 4064-4065)

**Spec reference:** §3 (predicate), §6.3, §6.4.

---

- [ ] **Step 2.1: Modify `applyDamage` signature at line 4034.**

Current line 4034:

```js
  function applyDamage(ant, amount, armorPierce, sourceCannon) {
```

Change to:

```js
  function applyDamage(ant, amount, armorPierce, sourceCannon, isDirectHit) {
```

---

- [ ] **Step 2.2: Locate the insertion point for the Brittle predicate.** It goes immediately after the `poison.damageAmp` block (which ends at line 4064) and before the armor mitigation block (which starts at line 4065).

Run:

```bash
sed -n '4054,4070p' patterns/demo-ant-buster.php
```

Confirm the structure matches:
```
4054:    // Plan 4: Biohazard +25% damage amp via poison.damageAmp...
...
4063:      }
4064:    }
4065:    if (!armorPierce) {
4066:      var armor = k.armor;
```

---

- [ ] **Step 2.3: Insert the Brittle predicate block.**

Insert this exact block between the closing `}` at line 4064 and the `if (!armorPierce)` at line 4065:

```js
    // Plan 6: Brittle synergy. Heavy-family direct hit on a frozen ant deals
    // 2x damage on top of any existing freeze.damageAmp (composes multiplicatively),
    // consumes the freeze status, and spawns a cyan shatter visual. Direct hit
    // only — splash, beam, aura, and chain-link calls leave isDirectHit unset.
    // Nested ifs to dodge the inline-script and-operator encoding gotcha.
    if (isDirectHit) {
      if (sourceCannon) {
        var sk2 = CANNON_KINDS[sourceCannon.kind];
        if (sk2.family === 'heavy') {
          if (ant.effects) {
            if (ant.effects.freeze) {
              dmg = dmg * 2;
              delete ant.effects.freeze;
              spawnShatterParticles(ant.x, ant.y);
            }
          }
        }
      }
    }
```

The variable is `sk2` (not `sk`) to avoid shadowing the `sk` alias declared at line 4038 in the existing `damageModifier` block.

---

- [ ] **Step 2.4: PHP lint check.**

```bash
c:/wamp64/bin/php/php8.2.29/php.exe -l patterns/demo-ant-buster.php
```

Expected: `No syntax errors detected`.

---

- [ ] **Step 2.5: Hygiene check.** This is the most important hygiene check in the entire plan because the Brittle predicate is the most condition-dense block being added (per spec §10.8).

```bash
curl -s "http://localhost/dariobimbi.com/demos/ant-buster/" | grep -c '&#038;&#038;'
curl -s "http://localhost/dariobimbi.com/demos/ant-buster/" | grep -c '&amp;&amp;'
```

Expected: both return `0`.

If either is >0, the predicate must be rewritten with nested ifs (which it already is — but a reviewer or accidental edit could have introduced an `&&`). **Do not attempt to fix via escape characters or string concatenation.** Only nested-if rewrite is reliable.

---

- [ ] **Step 2.6: Playwright probe — predicate fires on direct heavy-on-frozen.**

The call site at line 4357 is still 4-arg until Task 3, so we test the predicate by calling `applyDamage` directly with `isDirectHit: true`.

Navigate to `http://localhost/dariobimbi.com/demos/ant-buster/?debug=1`. Click to start. Evaluate:

```js
// Spawn a worker ant manually for testing. The wave-spawn pathway can be
// bypassed by pushing directly onto game.ants and initializing the required
// fields. We'll use the same shape as ANT_KINDS.worker.
var workerKind = window.__abAntKinds.worker;
var testAnt = {
  kind: 'worker',
  x: 100, y: 100,
  hp: 10, maxHp: 10,
  vx: 0, vy: 0,
  effects: {},
  firedThresholds: {},
  dead: false,
  carrying: false,
  deliveredAt: 0,
  intangible: false,
  phaseT: 0,
  wanderPhase: 0,
  wanderFreq: 0.5,
  wanderAmp: 1.0
};
window.__ab.ants.push(testAnt);

// Apply freeze with damageAmp tag (Plan 3 convention)
window.__abApplyStatus(testAnt, 'freeze', 1, 5, 'test');
testAnt.effects.freeze.damageAmp = 1.5;

// Confirm pre-state
var pre = {
  hp: testAnt.hp,
  hasFreeze: !!testAnt.effects.freeze,
  particles: window.__ab.shatterParticles.length
};

// Build a fake "discus" cannon reference. The predicate reads
// CANNON_KINDS[sourceCannon.kind].family — discus is family 'heavy'.
var fakeCannon = { kind: 'discus' };

// Fire the predicate via direct applyDamage call
window.__abApplyDamage(testAnt, 4, false, fakeCannon, true);

// Confirm post-state
var post = {
  hp: testAnt.hp,
  hasFreeze: !!testAnt.effects.freeze,
  particles: window.__ab.shatterParticles.length
};

JSON.stringify({ pre: pre, post: post });
```

Expected output:
```json
{"pre":{"hp":10,"hasFreeze":true,"particles":0},
 "post":{"hp":-2,"hasFreeze":false,"particles":10}}
```

Math walkthrough:
- Discus base = 4. damageAmp = 1.5. Brittle = 2.0. Worker armor = 0.
- `4 × 1.5 × 2.0 × (1 - 0) = 12`. Worker HP `10 - 12 = -2`. (Negative is fine; ant dies on next prune.)
- Freeze cleared (`hasFreeze: false`).
- Particles +10 (`particles: 10`).

If `hp` is `8.5`, the predicate didn't fire — only the existing damageAmp ran (`4 × 1.5 × (1-0) - 4 × 1.5 = 6 - 0` then `hp = 10 - 6 = 4`, hmm that's 4, not 8.5). Re-check Step 2.3 — the predicate may not be reading family correctly.

If `hp` is `4`, only damageAmp fired. Re-check the call signature in Step 2.1 (`isDirectHit` may not have been added).

If `particles` is `0` but `hp` decreased correctly, re-check that `spawnShatterParticles` is in scope at the predicate's location (Task 1 must be merged in correctly).

---

- [ ] **Step 2.7: Playwright probe — predicate does NOT fire on negative cases.**

Same setup pattern, but verify the three negative cases from spec §5:

```js
// Negative case A: heavy direct hit on NON-frozen ant.
var antA = { /* same shape as testAnt above, but no freeze applied */ };
// ... [reset state, push antA, do NOT call applyStatus]
var preA = { hp: antA.hp, particles: window.__ab.shatterParticles.length };
window.__abApplyDamage(antA, 4, false, { kind: 'discus' }, true);
var postA = { hp: antA.hp, particles: window.__ab.shatterParticles.length };
// Expected: hp 10 - 4 = 6 (no Brittle, no damageAmp), particles unchanged

// Negative case B: quick-family direct hit on frozen ant.
var antB = { /* same shape, freeze applied with damageAmp 1.5 */ };
// ... [reset, push antB, applyStatus + tag damageAmp]
var preB = { hp: antB.hp, hasFreeze: !!antB.effects.freeze, particles: window.__ab.shatterParticles.length };
window.__abApplyDamage(antB, 8, false, { kind: 'autocannon' }, true);  // autocannon is family 'quick'
var postB = { hp: antB.hp, hasFreeze: !!antB.effects.freeze, particles: window.__ab.shatterParticles.length };
// Expected: hp 10 - (8 * 1.5) = -2 (damageAmp only, no Brittle), freeze still on, particles unchanged

// Negative case C: heavy splash hit (isDirectHit FALSE) on frozen ant.
var antC = { /* same shape, freeze applied with damageAmp 1.5 */ };
// ... [reset, push antC, applyStatus + tag damageAmp]
var preC = { hp: antC.hp, hasFreeze: !!antC.effects.freeze, particles: window.__ab.shatterParticles.length };
window.__abApplyDamage(antC, 4, false, { kind: 'discus' }, false);  // isDirectHit = false
var postC = { hp: antC.hp, hasFreeze: !!antC.effects.freeze, particles: window.__ab.shatterParticles.length };
// Expected: hp 10 - (4 * 1.5) = 4 (damageAmp only), freeze still on, particles unchanged

JSON.stringify({ A: { pre: preA, post: postA }, B: { pre: preB, post: postB }, C: { pre: preC, post: postC } });
```

Expected:
- A: hp 10 → 6 (or close after armor — Worker has armor 0 so exactly 6). Particles unchanged.
- B: hp 10 → -2 (damageAmp only on quick). Freeze still on. Particles unchanged.
- C: hp 10 → 4 (damageAmp only on splash). Freeze still on. Particles unchanged.

If any case shows particles increasing or hp dropping more than expected, the predicate is firing in a case it shouldn't — re-check the nested `if` conditions in Step 2.3.

---

- [ ] **Step 2.8: Commit Task 2.**

```bash
git add patterns/demo-ant-buster.php
git commit -m "$(cat <<'EOF'
feat(ant-buster): Brittle predicate in applyDamage (Plan 6 Task 2)

Adds 5th isDirectHit parameter to applyDamage and inserts the Brittle
predicate block between the existing freeze/poison damageAmp blocks
and the armor mitigation. Heavy-family direct hit on a frozen ant
multiplies dmg by 2, deletes ant.effects.freeze, and spawns 10 cyan
particles via spawnShatterParticles. Nested-if structure dodges the
inline-script &&-encoding gotcha (CLAUDE.md note).

Direct verification via window.__abApplyDamage; in-game projectile
collision pathway gets the isDirectHit=true argument in Task 3.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 3: Call-site update at line 4357

**Goal:** Update the projectile-direct-collision `applyDamage` call to pass `true` as the 5th argument. Leave all other call sites at the 4-arg form.

**Files:**
- Modify: `wp-content/themes/dariobimbi/patterns/demo-ant-buster.php` (line 4357 only)

**Spec reference:** §6.5.

---

- [ ] **Step 3.1: Confirm the projectile-collision call site is still at line 4357.** (Tasks 1 and 2 inserted lines above, which may have shifted the absolute line number.)

```bash
grep -n "applyDamage(a, p.damage, p.armorPierce, p.sourceCannon)" patterns/demo-ant-buster.php
```

There should be exactly one matching line. Note the line number — that's the call site to update.

If the grep returns zero matches, the call site has already been edited (perhaps by a partial earlier run). Run:

```bash
grep -n "applyDamage(a, p.damage, p.armorPierce, p.sourceCannon, true)" patterns/demo-ant-buster.php
```

If this returns a match, Step 3.2 is already done — skip to Step 3.3.

---

- [ ] **Step 3.2: Modify the projectile-collision call site.** Use Edit tool to change exactly:

Before:
```js
          applyDamage(a, p.damage, p.armorPierce, p.sourceCannon);
```

After:
```js
          applyDamage(a, p.damage, p.armorPierce, p.sourceCannon, true);
```

---

- [ ] **Step 3.3: Confirm no other `applyDamage` call sites were modified.**

```bash
grep -n "applyDamage(" patterns/demo-ant-buster.php
```

Expected: 8 lines total. Exactly ONE should pass `true` as 5th arg (the projectile-collision site from Step 3.2). The other 7 should remain 4-arg:
- The `coneDot` call (~line 3599)
- The two beam calls (~lines 3656, 3696)
- The aura call (~line 3726)
- The two `teslaArc` calls (~lines 3822, 3841)
- The splash-radius call (~line 4157)

Plus the function declaration line (~line 4034) doesn't count as a call.

---

- [ ] **Step 3.4: PHP lint check.**

```bash
c:/wamp64/bin/php/php8.2.29/php.exe -l patterns/demo-ant-buster.php
```

Expected: `No syntax errors detected`.

---

- [ ] **Step 3.5: Hygiene check.**

```bash
curl -s "http://localhost/dariobimbi.com/demos/ant-buster/" | grep -c '&#038;&#038;'
curl -s "http://localhost/dariobimbi.com/demos/ant-buster/" | grep -c '&amp;&amp;'
```

Expected: both return `0`.

---

- [ ] **Step 3.6: Playwright end-to-end probe — in-game projectile shatters.**

This is the integration test: a real projectile hitting a real frozen ant should now trigger Brittle.

Navigate to `http://localhost/dariobimbi.com/demos/ant-buster/?debug=1`. Click to start. Evaluate:

```js
// Spawn a worker test-ant on the path
var testAnt = {
  kind: 'worker',
  x: 200, y: 90,
  hp: 10, maxHp: 10,
  vx: 0, vy: 0,
  effects: {},
  firedThresholds: {},
  dead: false,
  carrying: false,
  deliveredAt: 0,
  intangible: false,
  phaseT: 0,
  wanderPhase: 0,
  wanderFreq: 0.5,
  wanderAmp: 1.0
};
window.__ab.ants.push(testAnt);

// Freeze it (with damageAmp tag)
window.__abApplyStatus(testAnt, 'freeze', 1, 5, 'test');
testAnt.effects.freeze.damageAmp = 1.5;

// Manually inject a Discus projectile aimed at the ant. Construct a
// projectile that mirrors the in-game spawn shape so the collision
// loop processes it normally.
var fakeCannon = window.__ab.cannons.length > 0
  ? window.__ab.cannons[0]
  : { kind: 'discus', x: 100, y: 90 };

window.__ab.projectiles.push({
  x: 195, y: 90,           // very close to ant — collides on first frame
  vx: 200, vy: 0,
  damage: 4,
  armorPierce: false,
  sourceCannon: fakeCannon,
  // any other fields the projectile-update loop expects (lifetime, etc.)
  // — minimum shape is what the collision check at line ~4357 reads
  lifetime: 5,
  age: 0
});

// Wait two frames for the collision pathway to run
new Promise(function (resolve) {
  requestAnimationFrame(function () {
    requestAnimationFrame(function () {
      resolve({
        antHp: testAnt.hp,
        antHasFreeze: !!testAnt.effects.freeze,
        particles: window.__ab.shatterParticles.length,
        projectilesRemaining: window.__ab.projectiles.length
      });
    });
  });
});
```

Expected: `{"antHp":-2, "antHasFreeze":false, "particles":10, "projectilesRemaining":0}` (or close — depending on exact timing the projectile may have been despawned).

If `antHp` is unchanged (still 10), the projectile didn't collide — increase the projectile speed or initial proximity, or wait more frames.

If `antHp` is `4` (only damageAmp ran, no Brittle), the call-site update from Step 3.2 didn't take — re-verify with grep.

If `particles` is `0` but `antHp` is `-2`, the predicate fired but the visual didn't — re-verify Task 1's wire-in.

---

- [ ] **Step 3.7: Commit Task 3.**

```bash
git add patterns/demo-ant-buster.php
git commit -m "$(cat <<'EOF'
feat(ant-buster): projectile-collision call site (Plan 6 Task 3)

Updates the projectile-direct-hit applyDamage call to pass true as
the 5th argument (isDirectHit). All other applyDamage call sites
(cone, beam, aura, tesla-arc, splash) remain 4-arg, so the Brittle
predicate fires only on direct projectile collisions — preserving
the §1 "direct hit only" design intent.

End-to-end Playwright probe confirms: frozen worker + Discus shot =
12 dmg, freeze cleared, 10 cyan particles spawned.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 4: Theme version bump

**Goal:** Bump the theme `Version:` field from 0.11.0 to 0.12.0.

**Files:**
- Modify: `wp-content/themes/dariobimbi/style.css` (one line)

**Spec reference:** §6.8.

---

- [ ] **Step 4.1: Read the current `style.css` header.**

```bash
head -30 style.css
```

Find the line `Version: 0.11.0`.

---

- [ ] **Step 4.2: Modify the version line.** Use Edit tool:

Before:
```
Version: 0.11.0
```

After:
```
Version: 0.12.0
```

---

- [ ] **Step 4.3: Confirm the served stylesheet uses the new version.** Visit `http://localhost/dariobimbi.com/demos/ant-buster/` and view source. The stylesheet `<link>` tag should include `?ver=0.12.0`.

```bash
curl -s "http://localhost/dariobimbi.com/demos/ant-buster/" | grep "ver=0.12.0" | head -3
```

Expected: at least one match (the theme stylesheet enqueue with the new version query param).

If no match, the WordPress theme cache may need refreshing — visit `wp-admin` once or hit `wp-admin/themes.php` to force a re-read.

---

- [ ] **Step 4.4: Commit Task 4.**

```bash
git add style.css
git commit -m "$(cat <<'EOF'
chore(ant-buster): bump theme version to 0.12.0 (Plan 6 hygiene)

Plan 6 (Brittle synergy) ships at theme version 0.12.0. Bumping the
version invalidates the served stylesheet cache and triggers
inc/auto-pages.php reconciliation on the next admin pageview (no-op
for Plan 6 since no new templates or patterns were added).

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 5: Playtest + status doc

**Goal:** Run the three §10.6 playtest scenarios, record results, and write the status doc with acceptance walkthrough + architectural decisions.

**Files:**
- Create: `wp-content/themes/dariobimbi/docs/superpowers/plans/2026-05-08-ant-buster-plan-6-status.md`

**Spec reference:** §9 (acceptance criteria), §10.6 (playtest scenarios), §10.7 (status doc structure).

---

- [ ] **Step 5.1: Run Scenario A — King boss-melt benchmark.**

Navigate to `http://localhost/dariobimbi.com/demos/ant-buster/?debug=1`. Use the `W` debug hotkey to jump to wave 25 prep. Place 1 Absolute Zero and 1 Annihilator within range of the King's wander zone. Start the wave.

Record:
- King time-to-die in seconds (use `window.__ab.simTime` or wall-clock).
- Royal Guard kills during the fight.
- Number of Royal Guards reaching the cake (`window.__ab.cake.lostSlices` or similar).
- Cake state at King-death (`window.__ab.cake.slices`).

Expected baseline: ~28-35s King melt, 0-2 RGs reaching cake.

---

- [ ] **Step 5.2: Run Scenario B — Wave-clear sanity.**

Jump to wave 18 (Queen wave) or wave 20. Place a Frost cluster + 1 Earthshaker covering the lane.

Observe (visual confirmation, possibly at 0.5× speed):
- Direct-hit ants in the Earthshaker splash radius shatter visibly (cyan burst).
- Splash *bystanders* die from splash damage WITHOUT cyan bursts.

This validates the §5 visual-gating contract.

---

- [ ] **Step 5.3: Run Scenario C — Triple-stack interaction.**

Wave 15+ with a Plague-poisoned cluster. Place Plague + Cryo + Demolisher in range.

Observe:
- Cyan burst (direct hit) + green poison clouds (Plague spread) + standard splat (death) all render on overlapping frames.
- No console errors.

---

- [ ] **Step 5.4: Walk through §9 acceptance criteria.**

For each of the 15 items in spec §9, record PASS/FAIL with a one-line note. Items 1-4 are verified by `grep`; items 5-11 by Playwright probe; items 12-14 by shell command; item 15 by playtest observation (Steps 5.1-5.3).

If any item fails, **stop and fix before continuing**. Do not write the status doc until all acceptance items pass.

---

- [ ] **Step 5.5: Write the status doc.**

Create `wp-content/themes/dariobimbi/docs/superpowers/plans/2026-05-08-ant-buster-plan-6-status.md` with this exact structure (matching Plan 5's status doc):

```markdown
# Plan 6 Implementation Status — Shipped

**Last updated:** 2026-05-08 (end of session)
**Status:** SHIPPED. Branch `ant-buster-plan-6`, [N] commits, ready for `--no-ff` merge to `main`.
**Plan reference:** [`2026-05-08-ant-buster-plan-6.md`](./2026-05-08-ant-buster-plan-6.md)
**Spec reference:** [`../specs/2026-05-08-ant-buster-plan-6-design.md`](../specs/2026-05-08-ant-buster-plan-6-design.md)

## What shipped

Plan 6 adds the Brittle synergy: a heavy-family projectile's direct hit on a frozen ant deals 3× damage (composing multiplicatively with Plan 3's existing 1.5× freeze.damageAmp), consumes the freeze status, and spawns a cyan shatter visual. The mechanic is a thin extension of the existing damageAmp pathway — it adds a 5th `isDirectHit` parameter to `applyDamage` and slots a nested-if predicate between the damageAmp blocks and armor mitigation. Direct hit only; splash/beam/aura/chain calls are intentionally not Brittle triggers (preserving the §1 "ant-liberator" guardrail).

The cyan particle system (10 particles per shatter, 0.18s lifetime, real-time `dt`) lives alongside the screenshake decay logic so the visual stays readable at any game speed.

Branch base: `36ca4c5` on `main` (Plan 6 spec doc commit). Branch head: this commit.

### Plan 6 commits (chronological)

| # | Commit | Subject |
|---|---|---|
| 1 | [SHA] | feat(ant-buster): shatter particle system (Plan 6 Task 1) |
| 2 | [SHA] | feat(ant-buster): Brittle predicate in applyDamage (Plan 6 Task 2) |
| 3 | [SHA] | feat(ant-buster): projectile-collision call site (Plan 6 Task 3) |
| 4 | [SHA] | chore(ant-buster): bump theme version to 0.12.0 (Plan 6 hygiene) |
| 5 | _this commit_ | docs(ant-buster): Plan 6 status — shipped |

## Acceptance criteria walkthrough

[Table with all 15 §9 items, PASS/FAIL, one-line note each.]

## Playtest results

[Three rows from Scenarios A/B/C with measurements and observations.]

## Architectural decisions worth flagging

1. **`isDirectHit` as a 5th parameter, not a separate function.** Keeping Brittle inside `applyDamage` lets the math compose atomically with damageAmp in the right order. Splitting would require pre-multiplying at the call site after re-reading the freeze status — gnarly.

2. **Nested-if predicate, not `&&`.** The 4-condition check is verbose but matches the function-local convention at the existing `freeze.damageAmp` block (lines 4048-4053). Avoids the inline-script HTML-encoding gotcha (CLAUDE.md).

3. **No immunity window after shatter.** The natural fire-rate cadence of the freeze cannons (Cryo 1.67s reload, AZ 1.0s, Blizzard aura 5s per-ant) is the thaw governor. New per-ant timer was unmotivated; if AZ-spam proves problematic in playtest, §8.5 has an escape hatch.

4. **Family-based, not damage-threshold-based, "Heavy" classification.** Reuses the existing `family: 'heavy'` curation. Damage-threshold was rejected because it would silently retag cannons every time their base damage was tweaked.

5. **Direct-hit-only, not splash-propagation.** Splash bystanders get the existing 1.5× damageAmp passive but no Brittle 2×. Avoids the "ant-liberator" anti-pattern where one Earthshaker splash would mass-shatter an entire frozen cluster (sabotaging the player's own crowd control).

## Plan 7+ candidates

From spec §1 non-goals plus prior plan backlogs:

- **Title Screen / Save State shell** — deferred; the right time is now (Plan 6 added the second mechanical layer that justifies a save-friendly architecture).
- **Per-tower targeting priority UI** — Plan 4 §4 candidate.
- **Hawkeye-vs-Queen rebalance** — Plan 4 status doc §117.
- **Distinct sprite silhouettes for the 18 god-tier rows** — visual identity work, no mechanics.

## File deltas

- `wp-content/themes/dariobimbi/patterns/demo-ant-buster.php` — net approximately +50 lines. Particle system (state init, factory, update, draw, wire-ins), `applyDamage` 5th parameter, Brittle predicate block, projectile-collision call-site update.
- `wp-content/themes/dariobimbi/style.css` — `Version: 0.11.0 → 0.12.0`.
- `wp-content/themes/dariobimbi/docs/superpowers/plans/2026-05-08-ant-buster-plan-6-status.md` — this file.
```

Replace `[N]`, `[SHA]`, and the bracketed sections with actual values from the playtest and `git log`.

---

- [ ] **Step 5.6: Commit Task 5 (status doc).**

```bash
git add docs/superpowers/plans/2026-05-08-ant-buster-plan-6-status.md
git commit -m "$(cat <<'EOF'
docs(ant-buster): Plan 6 status — shipped (Plan 6 Task 5)

All 15 acceptance criteria PASS. Programmatic playtest run across the
three §10.6 scenarios: King melt benchmark, wave-clear visual gating,
and triple-stack status interaction. Status doc captures the five
architectural decisions worth flagging for future maintainers and
the carry-forward Plan 7+ candidates.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 6: Auto-memory + merge

**Goal:** Update auto-memory with the Plan 6 shipped record, merge to main, push to origin.

**Files:**
- Create: `C:\Users\dario\.claude\projects\c--wamp64-www-dariobimbi-com-wp-content-themes-dariobimbi\memory\ant_buster_plan_6_shipped.md`
- Modify: `C:\Users\dario\.claude\projects\c--wamp64-www-dariobimbi-com-wp-content-themes-dariobimbi\memory\MEMORY.md`

**Spec reference:** §10.7 (auto-memory update).

---

- [ ] **Step 6.1: Write the auto-memory file.**

Create `C:\Users\dario\.claude\projects\c--wamp64-www-dariobimbi-com-wp-content-themes-dariobimbi\memory\ant_buster_plan_6_shipped.md` with this content (replace `[merge-SHA]` with the actual merge commit SHA from Step 6.4):

```markdown
---
name: Ant Buster Plan 6 shipped
description: Brittle synergy shipped 2026-05-08. Heavy-family direct hit on a frozen ant deals 3× damage (composes with Plan 3's freeze.damageAmp 1.5×), consumes the freeze, spawns a cyan shatter visual. Theme version 0.12.0.
type: project
---
Plan 6 (Brittle synergy) shipped 2026-05-08, merged to `main` as `[merge-SHA]` via `--no-ff` from branch `ant-buster-plan-6` (5 commits). Status doc: `docs/superpowers/plans/2026-05-08-ant-buster-plan-6-status.md`.

**What landed:**
- New 5th parameter `isDirectHit` on `applyDamage` (line 4034). Default falsy. Only line 4357 (projectile-direct-collision) passes `true`; all other call sites (cone, beam, aura, tesla-arc, splash) remain 4-arg.
- Brittle predicate block inserted between the existing damageAmp blocks and armor mitigation. Nested-if structure (4 conditions: isDirectHit, sourceCannon, family heavy, ant.effects.freeze) per the inline-script convention.
- Predicate fires `dmg = dmg × 2`, `delete ant.effects.freeze`, `spawnShatterParticles(ant.x, ant.y)` atomically.
- New cyan particle system: `game.shatterParticles[]` state, `spawnShatterParticles(x, y)` factory (10 particles per shatter, color #88f0ff, 0.18s lifetime, radial 80-140 px/sec velocity, ×0.92 friction per tick), `updateShatterParticles(dt)` tick, `drawShatterParticles(ctx)` render. Tick uses real-time `dt` (not simDt) — sits alongside screenshake decay in the update loop.
- Theme version bumped 0.11.0 → 0.12.0.

**Why:** Plan 5 introduced the Royal Guard subtype to fix Reaper trivializing the King fight. Plan 6 builds the *positive* synergy on top: rewards Frost + Heavy combos with a 3× execution moment. Composes multiplicatively with Plan 3's freeze.damageAmp (1.5 × 2.0 = 3.0×) and stacks with Biohazard's poison.damageAmp (1.5 × 1.25 × 2.0 = 3.75× max corner case).

**How to apply:** When extending Ant Buster:
- The 5th `isDirectHit` parameter on `applyDamage` is the canonical "direct hit signal" for any future mechanic that wants to differentiate direct from splash/beam/aura. Pass `true` only at projectile-collision direct-hit sites.
- The "Heavy" classification is the existing `family === 'heavy'` curation, NOT a damage threshold. Re-classifying a cannon's family has cross-system ripples (it shares with the wave-15 unlock UI logic).
- The cyan shatter visual gates strictly to "shatter actually fired" — heavy direct on non-frozen, quick on frozen, splash on frozen all use damageAmp passively but produce no cyan burst. This keeps the visual signal pure.
- Particle-style real-time `dt` convention (alongside screenshake) is the right pattern for any future visual juice that should look the same at 1× and 8× speed.
- The "ant-liberator" anti-pattern was explicitly rejected in spec §1: splash from a heavy cannon does NOT shatter frozen bystanders. Don't reverse this without re-running the design discussion.

**Tuning hatches (spec §8) for future playtests:**
- King too fragile under AP-shatter combo: King HP 3000 → 4000 (primary), King armor 0.5 → 0.6 (secondary).
- Brittle multiplier feels too strong: 2.0 → 1.75 (last-resort, headline-math nerf).
- AZ-spam permafreeze chains shatters: Cryo duration 0.8 → 0.6, AZ duration 1.5 → 1.2.
- Visual reads wrong: particle count 10 → 8/12, lifetime 0.18 → 0.22, color hex tweak.

**Plan 7+ candidates (logged in spec §1 + Plan 5 carry-forward):**
- Title Screen / Save State shell (now genuinely time to land — two mechanical layers stacked since the deferral).
- Per-tower targeting priority UI (Plan 4 §4 candidate).
- Hawkeye-vs-Queen rebalance (Plan 4 status doc §117).
- Distinct sprite silhouettes for the 18 god-tier rows.
```

---

- [ ] **Step 6.2: Add an index entry to `MEMORY.md`.**

Append this exact line to the existing list (after the Plan 5 entry, before any closing content):

```markdown
- [Ant Buster Plan 6 shipped](ant_buster_plan_6_shipped.md) — Brittle synergy: heavy-family direct hit on frozen ant deals 3× damage, consumes freeze, spawns cyan shatter; merged to main 2026-05-08 as [merge-SHA], theme version 0.12.0
```

(Replace `[merge-SHA]` after Step 6.4.)

---

- [ ] **Step 6.3: Confirm working tree is clean on `ant-buster-plan-6` before merging.**

```bash
git status
git log --oneline main..ant-buster-plan-6
```

Expected: clean tree, 5 commits ahead of main:
1. feat(ant-buster): shatter particle system (Plan 6 Task 1)
2. feat(ant-buster): Brittle predicate in applyDamage (Plan 6 Task 2)
3. feat(ant-buster): projectile-collision call site (Plan 6 Task 3)
4. chore(ant-buster): bump theme version to 0.12.0 (Plan 6 hygiene)
5. docs(ant-buster): Plan 6 status — shipped (Plan 6 Task 5)

---

- [ ] **Step 6.4: Merge to `main` with `--no-ff`.**

```bash
git checkout main
git merge --no-ff ant-buster-plan-6 -m "Merge branch 'ant-buster-plan-6' into main"
git log --oneline -1
```

The output gives the merge SHA. Capture it for the auto-memory file (Step 6.1) and the MEMORY.md index entry (Step 6.2).

If `git merge` reports a conflict, **stop**. The Plan 6 branch should be a clean fast-forward of main since Plan 5 is the only intervening change and Plan 6 was branched from `36ca4c5` (post-Plan-5). Investigate before resolving.

---

- [ ] **Step 6.5: Update auto-memory file and `MEMORY.md` with the actual merge SHA.**

Open both files (`ant_buster_plan_6_shipped.md` and `MEMORY.md`), replace the `[merge-SHA]` placeholder with the value from Step 6.4.

(No git commit for memory files — they live outside the repo at `C:\Users\dario\.claude\projects\...\memory\`.)

---

- [ ] **Step 6.6: Push `main` to origin.**

```bash
git push origin main
```

Expected: `[old-SHA]..[new-SHA] main -> main`.

---

- [ ] **Step 6.7: Final confirmation.**

```bash
git log --oneline -8
git branch --list "ant-buster-plan-6"
```

Expected:
- The merge commit + 5 Plan 6 task commits visible in `main` history.
- The `ant-buster-plan-6` branch still exists locally (matches Plan 5 convention; do not delete it as the user prefers to keep historical branches).

---

## Done

Plan 6 shipped. Theme version 0.12.0 live. Brittle synergy active in production at `https://dariobimbi.com/demos/ant-buster/`.

The first time a player freezes an ant and lands a heavy hit on it: cyan crunch.
