# Ant Buster Plan 6 — Brittle Synergy Design Spec

**Date:** 2026-05-08
**Status:** Design phase complete, ready for implementation plan
**Theme version target:** 0.11.0 → 0.12.0

---

## §1 — Goal & Non-Goals

**Goal:** Add a "Brittle" synergy that rewards Frost + Heavy combinations with a 3× damage execution moment on frozen targets. Specifically: a heavy-family projectile's direct hit on a frozen ant deals `base × 1.5 × 2.0` damage, consumes the freeze, and spawns a cyan shatter flourish. The mechanic stacks multiplicatively on top of the existing `freeze.damageAmp: 1.5` pathway from Plan 3 — Brittle is the Heavy-only second valve, not a replacement.

**Non-goals (Plan 6 does not touch):**

- Title screen / Save State shell — deferred, the right time is after one more mechanical layer lands.
- Per-tower targeting priority UI — the Reaper-spatial-puzzle from Plan 5 is still fresh.
- Hawkeye-vs-Queen rebalance — separate follow-up tagged in Plan 4 status doc §117.
- Distinct sprite silhouettes for the 18 god-tier rows — visual identity work, no mechanics.
- Brittle Resistance on bosses — `armor: 0.5` on the King is the existing universal governor; cranking armor is the §8 tuning hatch if playtest shows King-melt is too fast.
- Splash/AoE Brittle propagation — splash hits get the existing 1.5× damageAmp passive, no 2× spike. Direct hit only. Avoids the "ant-liberator" anti-pattern where a heavy splash sabotages frozen crowd control.
- New status effects, new cannons, new ant kinds. Brittle is a *mechanic*, not new content.

**Why this is one plan and not two:** The trigger logic, math, status consumption, and visual flourish are tightly coupled — each one is a few lines, but they make no sense in isolation. Splitting "mechanic now, visual later" would land a 3× damage spike with no player feedback, which is worse than not shipping at all.

---

## §2 — Architecture Overview

**File footprint:** Single file. `patterns/demo-ant-buster.php` — same monolithic demo. No new patterns, no new templates, no `theme.json` changes.

**Total estimated touch:** ~50 lines added. Comparable to Plan 5.

**Where the logic lands:**

The Plan 3 `freeze.damageAmp` pathway already lives inside `applyDamage` at line 4048-4053 — reading the freeze effect, multiplying damage, before armor mitigation. **Brittle slots in immediately after** that block, before armor:

```
applyDamage(ant, amount, armorPierce, sourceCannon, isDirectHit) {
  dmg = amount × damageModifier        (existing)
  dmg = dmg × freeze.damageAmp          (existing 1.5×)
  dmg = dmg × poison.damageAmp          (existing Biohazard 1.25×)

  // NEW: Brittle predicate
  if (isDirectHit AND sourceCannon AND family === 'heavy' AND ant.effects.freeze) {
    dmg = dmg × 2
    delete ant.effects.freeze            // consume
    spawnShatterParticles(ant.x, ant.y)  // visual hook
  }

  if (!armorPierce) dmg = dmg × (1 - armor)
  ant.hp -= dmg
}
```

**`isDirectHit` is a new 5th parameter on `applyDamage`** (default falsy). This enforces direct-hit-only without polluting splash/beam/aura code paths. Existing callers stay as-is — only the projectile-collision call site passes `true`.

**Why a parameter and not a separate function:** Keeping Brittle inside `applyDamage` lets the math compose atomically with damageAmp in the same function, in the right order, without re-reading `ant.effects.freeze` at the call site after a delete. Splitting it would require the call site to read damageAmp, pre-multiply, then call applyDamage with bypassed amp — gnarly. One param is the cheapest fix.

**New code surfaces (none of these exist today):**

1. `game.shatterParticles = []` — array of active particles, init in game state setup.
2. `spawnShatterParticles(x, y)` — factory pushes 10 cyan particles with random radial velocity onto the array.
3. `updateShatterParticles(dt)` — tick block in the main update loop. Advances age, prunes expired.
4. `drawShatterParticles(ctx)` — draw pass renders each active particle.

Plus the touch to `applyDamage` (signature + 7 lines of predicate body) and one call site update (the projectile-direct-hit collision branch passes `true`).

**What does NOT change:**

- `STATUS_KINDS` schema. No new kind, no new fields. `freeze` stays exactly as defined at line 2267.
- `CANNON_KINDS` schema. The `family` field already exists and is already curated; we just read it.
- `ANT_KINDS` schema. No new ant, no new field on existing ants.
- The Plan 3 `freeze.damageAmp: 1.5` stamp from Cryo and Absolute Zero. Untouched. Brittle stacks on top of it via multiplicative composition.

**Inline-script gotcha note:** The Brittle predicate uses 4 conditions joined logically. Per the CLAUDE.md `&&` HTML-encoding gotcha and the existing convention at the freeze.damageAmp block (lines 4048-4053 use nested ifs), Brittle will be written as nested ifs — verbose but matches the function-local convention and dodges the 4-fold operator-encoding risk.

---

## §3 — The Trigger Predicate

**Decision: binary check, no minimum-duration threshold.**

The check is simply "does `ant.effects.freeze` exist at the moment of impact?" If yes, Brittle fires. If the status has already been cleared by the tick loop (because the timer expired), it doesn't.

**Why no threshold:**

1. **Convention consistency.** The Plan 3 `freeze.damageAmp` read at lines 4048-4053 doesn't check duration either — it just reads the amp tag off the live status. Adding a duration threshold to Brittle would create an inconsistency where two closely-related mechanics use different gates: damageAmp says "yes" to 0.05s remaining, Brittle says "no." That asymmetry is hard to explain in tooltip copy and looks like a bug to the player.
2. **Magic numbers.** A "0.1s minimum" threshold is unmotivated. If 0.1s, why not 0.2s? Why not 0.05s? Any value is arbitrary.
3. **The status loop is already the source of truth.** Status update runs every tick at 60fps. By the time `applyDamage` reads `ant.effects.freeze`, it's the post-tick reality. "Was this ant frozen at moment of impact?" is a binary yes/no the system already answers correctly.
4. **The "frame-perfect shatter" isn't a player exploit.** A heavy projectile hitting a frozen ant 1 frame before the freeze would have ended is just a heavy projectile hitting a frozen ant. The player who fired it didn't manipulate frame timing; they shot a target that was visibly frozen.

**Formal predicate (in nested-if form, per the inline-script convention):**

```js
// Inserted into applyDamage after the existing damageAmp blocks
// (freeze block at lines 4048-4053, poison block at 4059-4064) and
// before the armor mitigation block at line 4065.
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

Variable named `sk2` to avoid colliding with the `sk` alias already declared at line 4038 in the `damageModifier` block.

**Call-site changes — exactly one:** The projectile-direct-hit `applyDamage` call. Splash, beam, aura, and any future broadcast pathway leave `isDirectHit` unset (defaults to falsy). The implementation phase confirms this is line 4357; the existing call signature there gets a 5th argument: `true`.

**Cascade behavior — multiple heavy hits same frame:**

If two heavy projectiles hit the same frozen ant on the same tick:

- First projectile: predicate passes, dmg × 2, freeze deleted, shatter particles spawn.
- Second projectile (and any subsequent): freeze is already gone. Predicate fails. Standard damage, no Brittle, no second shatter visual.

This is the right behavior:

- Mathematically: only one "shatter" makes sense per freeze application.
- Visually: one cyan burst per ice break, not stacked bursts.
- Incidentally: the second projectile *also* loses the 1.5× damageAmp, because it's reading the now-deleted freeze. That's correct — the ice IS shattered, no amp source remains.

**Edge case worth flagging for the plan author:** Heavy projectile and a non-heavy projectile hit on the same frame. Order matters — whichever arrives first in the projectile-collision iteration determines whether the freeze persists. If the non-heavy arrives first, it eats the 1.5× damageAmp; the heavy arrives second, sees the freeze, shatters at 3×. If the heavy arrives first, it shatters at 3×; the non-heavy arrives second, sees no freeze, deals base damage. Both orderings are deterministic given the iteration order and acceptable — neither produces a pathological outcome.

---

## §4 — Damage Math

**The full damage formula (in evaluation order, multiplicative):**

```
finalDmg = base
         × kindMod              [e.g., Reaper instakill — usually 1 for non-Reaper sources]
         × freezeAmp             [1.5 if ant has frozen+damageAmp tag, else 1.0]
         × poisonAmp             [1.25 if Biohazard-poisoned, else 1.0]
         × brittle               [2.0 if isDirectHit AND family='heavy' AND frozen, else 1.0]
         × (armorPierce ? 1.0 : 1 - armor)
```

**Key compositional insight from the existing code path:**

The `freezeAmp` and `poisonAmp` are independent of source-cannon family. So a Biohazard'd-AND-frozen target hit by an Annihilator gets **both** existing amps stacked with Brittle: `1.5 × 1.25 × 2.0 = 3.75×` before armor. That's not new design — it falls out of the existing multiplicative chain — but it's the maximum-stacking corner case worth flagging.

**Concrete numbers — heavy cannons against the bossish targets:**

| Cannon | Base | AP | fireRate | Frozen Worker (armor 0) | Frozen Royal Guard (0.2) | Frozen Soldier/Tank (0.5) | Frozen Queen (0.3) | Frozen King (0.5) |
|---|---|---|---|---|---|---|---|---|
| Mortar (mid) | 8 | no | 0.5 | 24 | 19.2 | 12 | 16.8 | 12 |
| Discus (mid) | 4 | no | 0.4 | 12 | 9.6 | 6 | 8.4 | 6 |
| Siege (mid) | 12 | no | 0.6 | 36 | 28.8 | 18 | 25.2 | 18 |
| Railgun (final) | 25 | yes | 0.6 | 75 | 75 | 75 | 75 | 75 |
| Patriot (final) | 20 | no | 0.5 | 60 | 48 | 30 | 42 | 30 |
| Demolisher (final) | 60 | no | 0.25 | 180 | 144 | 90 | 126 | 90 |
| Hellfire (final) | 15 | no | 0.4 | 45 | 36 | 22.5 | 31.5 | 22.5 |
| Particle Cannon (god) | 60 | yes | 0.6 | 180 | 180 | 180 | 180 | 180 |
| Annihilator (god) | 120 | yes | 0.3 | 360 | 360 | 360 | 360 | 360 |
| Saturation (god) | 25 | no | 0.5 | 75 | 60 | 37.5 | 52.5 | 37.5 |
| Cluster Strike (god) | 30 | no | 0.5 | 90 | 72 | 45 | 63 | 45 |
| Earthshaker (god) | 80 | no | 0.12 | 240 | 192 | 120 | 168 | 120 |

**Time-to-kill snapshots, Frozen King (3000 HP):**

| Solo cannon + Frost wall | Per-shot dmg | Shots needed | TTK |
|---|---|---|---|
| Annihilator (heavy, AP, fast) | 360 | 8.3 | **~28s** |
| Particle Cannon (heavy, AP, mid-fire) | 180 | 16.7 | **~28s** |
| Earthshaker (heavy, slow, AoE) | 120 | 25 | **~208s** |
| Demolisher (heavy, no AP) | 90 | 33 | **~133s** |
| Railgun (heavy, AP, final-tier) | 75 | 40 | **~67s** |

The 7× spread between Annihilator (28s) and Earthshaker (208s) is the role differentiation. Annihilator/Particle Cannon are surgical boss-killers; Earthshaker is a wave-clear hammer that incidentally chips at bosses.

**Maximum-stack corner case:**

A Biohazard'd, frozen King hit by an Annihilator direct shot:
`120 × 1.5 × 1.25 × 2.0 × 1.0 (armorPierce) = 450 dmg/shot`. ~6.7 shots = **~22s King melt** with the full triple-stack. This is the absolute ceiling. It costs gold to set up (Biohazard 260 + Absolute Zero 280 + Annihilator 280 = 820), surrenders three slots to one lane, and requires the Biohazard infection to land before the freeze-shatter cycle. Acceptable as the apex skill-expression configuration.

**Sanity floor — Brittle on the smallest heavy cannon vs. unarmored worker:**

Discus direct hit on a frozen worker: `4 × 1.5 × 2 = 12 dmg`. Worker has 10 HP. **One-shot kill.** That's a low-tier mid-game synergy that makes early Frost+Discus pairings feel rewarding without becoming exploit-tier. The bonus scales naturally with base damage, so the smallest heavies stay flavorful instead of game-breaking.

**What's NOT in this math:**

- Status durations (covered in §3 — binary check, no minimum).
- Splash damage to bystanders (covered in §1 — direct hit only; bystanders get the existing 1.5× damageAmp passive but no Brittle).
- Phoenix burn-spread interaction. Phoenix is family `'quick'`, not heavy — Brittle doesn't fire from Phoenix at all. Phoenix's burn ticks don't pass `isDirectHit: true`. Confirmed safe.

---

## §5 — Status Consumption & Visual Gating

**Status consumption rule:** `delete ant.effects.freeze` happens **inside the same predicate block** as the 2× multiplier and the visual spawn. All three actions are atomic — they fire together, or none of them fire.

**Visual gating — three states:**

| Scenario | Damage | Visual |
|---|---|---|
| Heavy direct hit on frozen ant | base × 1.5 × 2.0 (Brittle fires) | **Cyan burst** |
| Heavy direct hit on non-frozen ant | base (no amp, no Brittle) | None |
| Heavy *splash* hit on frozen ant (bystander) | base × 1.5 (damageAmp passive only) | None |
| Quick/Double direct hit on frozen ant | base × 1.5 (damageAmp passive only) | None |

**The cyan burst is functional UI.** It tells the player "this exact hit was the shatter." Anything else firing the visual would dilute the signal — a frozen ant getting peppered by a Marksman shouldn't pulse cyan, even though it benefits from damageAmp.

---

**Particle factory: `spawnShatterParticles(x, y)`**

**Decision: fixed count, no damage-scaling.** Reasons:

1. Conceptually the visual represents *the ice breaking*, not the force of the hit. The same chunk of ice shatters whether a Discus or an Annihilator broke it.
2. Performance is predictable — bounded particle count even in worst-case shatter spam.
3. The existing god-tier juice (halo, `flashScale: 1.7` muzzle flash, Earthshaker/Annihilator screen-shake) already scales the "wow" with cannon tier.
4. YAGNI — if a tier-scaling tweak feels needed in playtest, it's a one-line knob in §8.

**Particle specs:**

| Property | Value | Reasoning |
|---|---|---|
| Count per shatter | **10** | Reads as "burst" without overdrawing on a 16×16 ant sprite |
| Color | **#88f0ff** | Cyan, slightly cool. Distinct from frost-blue (#a0e8ff) so it's clearly the *break*, not the freeze |
| Particle size | **2×2 pixels** | Matches the demo's pixel-art aesthetic (cf. Worker sprite, splat factories) |
| Lifetime | **0.18s** (~11 frames at 60fps) | Fast enough to feel snappy, slow enough to read at 60fps |
| Initial position | `(ant.x, ant.y)` | Ant center, not splat-offset |
| Initial velocity | Radial outward, random angle, magnitude **80-140 px/sec** | Random per particle, gives the "shards everywhere" feel |
| Friction per tick | **× 0.92** per tick | Particles slow visibly before they expire — adds physical weight |
| Alpha curve | Linear fade `1.0 → 0.0` over lifetime | Simple, performant, reads well |
| No gravity | The game is top-down — ice shards in plan-view don't fall |

**Data structure (per particle):** `{ x, y, vx, vy, age, lifetime }`. Six floats. Pushed onto `game.shatterParticles[]`.

**Worst-case render budget:** 8 heavy cannons × ~1 shatter/sec × 10 particles × 0.18s lifetime = **~14 active particles per frame** in the most aggressive scenario. Negligible for canvas 2D. No quota or pool needed.

---

## §6 — Implementation Reference Card

All edits in `wp-content/themes/dariobimbi/patterns/demo-ant-buster.php` unless noted. Listed in implementation order.

---

**[6.1] State init.** Append one line where `game.ants`, `game.projectiles`, `game.slices` are initialized.

```js
game.shatterParticles = [];
```

Add a one-line comment: `// game.shatterParticles is ephemeral; do not persist if Save State is added later.`

---

**[6.2] Factory function.** Insert near other top-level draw/spawn helpers (after `spawnAntAt`, before status logic blocks).

```js
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

**[6.3] `applyDamage` signature change.** Modify line 4034.

Before:

```js
function applyDamage(ant, amount, armorPierce, sourceCannon) {
```

After:

```js
function applyDamage(ant, amount, armorPierce, sourceCannon, isDirectHit) {
```

---

**[6.4] Brittle predicate insertion.** Insert immediately after the `poison.damageAmp` block (currently ending at line 4064), before the armor mitigation block at line 4065.

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

---

**[6.5] Call-site update.** Modify line 4357 (projectile-direct-collision call).

Before:

```js
applyDamage(a, p.damage, p.armorPierce, p.sourceCannon);
```

After:

```js
applyDamage(a, p.damage, p.armorPierce, p.sourceCannon, true);
```

**Do NOT modify** any other `applyDamage` call site (3599, 3656, 3696, 3726, 3822, 3841, 4157). Those are splash/beam/aura/chain — they should leave `isDirectHit` unset (defaults falsy). Specifically: line 3822 (Tesla arc primary) is a candidate future hook if a heavy-family Tesla cannon is ever added, but is intentionally left untouched today since Stormcaller is family `'quick'` and the predicate would no-op anyway.

---

**[6.6] Update tick.** Insert in the main update loop, after `updateStatuses(dt)` and before `updateProjectiles(dt)`. The caller must pass **real-time `dt`** (the same variable used by screenshake — see §7 case 6 and §10.4), not sim-time.

```js
function updateShatterParticles(dt) {
  for (var i = game.shatterParticles.length - 1; i >= 0; i--) {
    var p = game.shatterParticles[i];
    p.x = p.x + p.vx * dt;
    p.y = p.y + p.vy * dt;
    p.vx = p.vx * 0.92;
    p.vy = p.vy * 0.92;
    p.age = p.age + dt;
    if (p.age >= p.lifetime) {
      // Reverse iteration so splice doesn't break the index. Same convention
      // as updateProjectiles and updateStatuses elsewhere in this file.
      game.shatterParticles.splice(i, 1);
    }
  }
}
```

Wire the call into the main update loop:

```js
updateShatterParticles(realDt); // see §10.4 — match the screenshake variable
```

---

**[6.7] Draw pass.** Insert in main render loop, between `drawAnts()` and the HUD draw pass.

```js
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

Wire the call into the main render loop:

```js
drawShatterParticles(ctx);
```

---

**[6.8] Theme version bump.** Edit `wp-content/themes/dariobimbi/style.css`.

Before: `Version: 0.11.0`
After: `Version: 0.12.0`

---

**[6.9] Hygiene checks (per CLAUDE.md inline-script gotchas).** After all edits, confirm via curl-and-grep that no operator encoding crept in:

```bash
curl -s "http://localhost/dariobimbi.com/demos/ant-buster/" | grep -c '&#038;&#038;'   # expect 0
curl -s "http://localhost/dariobimbi.com/demos/ant-buster/" | grep -c '&amp;&amp;'     # expect 0
php -l patterns/demo-ant-buster.php                                                     # expect "No syntax errors"
```

(PHP path on this WAMP install: `c:/wamp64/bin/php/php8.2.29/php.exe`.)

---

**Estimated total touch:** ~50 lines added, 2 lines modified. Single file change (plus the one-line `style.css` version bump).

---

## §7 — Edge Cases

### (1) Phase ant during the intangible window

Phase ants have `phaseInterval: 3, phaseDuration: 1` — 3 seconds tangible, 1 second intangible, looping. The projectile-collision loop at line 3558 already short-circuits with `continue` if `ant.intangible === true`, so `applyDamage` never gets called.

Result: a frozen Phase ant during its intangible window cannot be shattered. The heavy projectile passes through. The freeze status persists (nothing damaged the ant, so nothing modified `ant.effects`). Next heavy shot that lands during the tangible window shatters normally.

**This is correct behavior, not a bug.** It rewards players for timing their heavy shots to the tangible window — the same skill expression the Phase ant was designed to demand.

### (2) Plague spread on a shattered ant that dies from the shatter

Plague stamps `poison` onto the target via `statusOnHit: { kind: 'poison', magnitude: 2.5, duration: 6, spreads: true }`. The death-spread cascade fires from `killAnt` via the existing `onTargetDeath` / poison `onDeath` hook (Plan 4 wired this).

Brittle deletes ONLY `ant.effects.freeze`. The poison slot is untouched. So:

- Shatter spike kills ant → standard death pathway runs.
- `ant.effects.poison.spreads === true` → poison propagates to neighbors via the existing radius logic.
- Cyan shatter visual fires alongside the standard splat draw and any death side-effects (Bomber boom, Splitter spawn, Queen worker-spawn).

**No interference.** All side-effects compose because they read independent state slots.

### (3) Phoenix burn behavior around shatter

Phoenix is family `'quick'`, not heavy. **Brittle never fires from a Phoenix hit** — the family check fails immediately. So "Phoenix shatters" doesn't exist as a scenario.

The relevant question: if a *different* cannon (e.g., Annihilator) shatters a Phoenix-burned ant, what happens to the burn? Burn lives on `ant.effects.burn`. Brittle deletes ONLY freeze. Burn stays alive, keeps ticking, expires at duration end, fires the `burn.onExpire` Phoenix-spread hook normally.

If the shatter spike kills the burning ant outright, the death pathway interrupts the remaining burn ticks (no further DPS), but the spread-on-expire hook is tied to expiry, not death — so it doesn't fire. That's a Plan 4 design choice (burn-spread is on `onExpire`, not `onDeath`), unchanged by Brittle.

### (4) Multi-threshold King summons in a single shatter

A 360-damage Annihilator shatter spike on the King at 80% HP would drop him to roughly 68% — crossing the 75% threshold once. A 1500+ damage spike (would require multiple stacked amps + exploit math) crossing 75% AND 50% simultaneously is the corner case.

The existing `onHpThresholds` loop at lines 4075-4087 iterates all thresholds in array order and fires each one whose `at` is crossed by the current HP. **Multi-threshold crossings already work correctly** — no Brittle-specific handling needed.

### (5) Frozen carrier ant gets shattered while holding a cake slice

The death pathway in `killAnt` (line 4093) handles `ant.carrying` — drops the slice on the ground at the death position. Brittle doesn't touch carrier state. Slice drops normally.

Since Royal Guards (Plan 5) have `canPickup: true` and may be carrying when shattered, this scenario WILL come up in real play. Visual: cyan burst + standard splat + dropped slice on the floor at the same position. All three layers compose without overlap or interference.

### (6) `dt` source for `updateShatterParticles` — real-time vs sim-time

Per CLAUDE.md note ("real-time `game.time` retained for screenshake/visual juice") and Plan 4 status doc convention, **shatter particles must tick with real-time `dt`**, not sim-time. Otherwise at 8× game speed, the 0.18s lifetime collapses to ~22ms (1-2 frames at 60fps) and the visual flickers and disappears.

When wiring `updateShatterParticles(dt)` into the main update loop, pass the real-time delta variable used by screenshake — see §10.4 for the discovery procedure.

### (7) Pause and speed-control behavior

Pausing freezes the update loop; particles freeze in place. Speed control affects the real-time dt only if the game multiplies it (it shouldn't, per the convention). At 1× speed, particles look the same as at 8× speed — the visual is decoupled from game pacing. This is the intended behavior for visual juice.

### (8) Save State (future, not in scope)

`game.shatterParticles` is purely ephemeral visual state. If Save State is added in a future plan, the serializer should skip this array entirely. The §6.1 init line carries an inline comment to remind future-implementers.

### (9) Memory bound on `shatterParticles`

Reverse-iteration splice (in [6.6]) cleans expired particles every frame. Worst case from §5 analysis was ~14 active particles. No quota or pool needed.

---

## §8 — Balance & Tuning Hatches

**Pre-emptive King HP bump? No.** Ship 3000 HP, treat the ~28s Annihilator-shatter melt as a valid power-fantasy moment, and crank the dial only if playtest shows the Royal Guard pressure becomes irrelevant before the King dies.

Tuning dials are listed in **likelihood-to-fire order**.

### Tier 1 — Most likely to fire after first-week playtest

**(8.1) King HP 3000 → 4000.** *Symptom:* King dies before the Royal Guard drip pressures the cake meaningfully (i.e., 0-1 RGs reaching the cake during the entire boss phase).

| Setup | TTK before | TTK after |
|---|---|---|
| Annihilator + Frost wall | ~28s | ~37s |
| Particle Cannon + Frost wall | ~28s | ~37s |
| Earthshaker + Frost wall | ~208s | ~278s |
| Biohazard'd Annihilator (max stack) | ~22s | ~30s |

Edit: `ANT_KINDS.king.hp` at line 2226.

**(8.2) King armor 0.5 → 0.6.** *Symptom:* Non-armorPierce heavy cannons feel too punchy on the King, but the AP cannons (Annihilator, Particle Cannon, Railgun) feel fine.

This is the precision dial — it nerfs Earthshaker / Demolisher / Saturation / Cluster Strike against the King without affecting AP combos. Doesn't touch wave-clear performance (Worker has armor 0). Edit: `ANT_KINDS.king.armor` at line 2240.

### Tier 2 — Secondary, only if Tier 1 doesn't resolve the symptom

**(8.3) Brittle multiplier 2.0 → 1.75.** *Symptom:* Even after King HP+armor bumps, the AP-shatter combo still trivializes the boss.

| Setup | TTK at 2.0× | TTK at 1.75× |
|---|---|---|
| Annihilator vs King 4000 HP, armor 0.5 | ~37s | ~42s |
| Discus vs frozen Worker (10 HP) | 1-shot (12 dmg) | 1-shot (10.5 dmg) |

The 2.0 → 1.75 bump preserves the low-tier teaching moment. Edit: literal `2` in [6.4] becomes `1.75`.

**(8.4) Cryo/AZ freeze duration tightening.** *Symptom:* AZ-spam permafreeze + Annihilator chain-shatter feels exploit-tier even after King HP bump. Cryo: `duration: 0.8` → `0.6`. AZ: `duration: 1.5` → `1.2`. Tightens the re-freeze window so the natural thaw between shatters is more visible.

**(8.5) Add a post-shatter immunity window (last-resort).** We explicitly designed Brittle without an immunity window in §3, but if AZ-spam still chains shatters at problematic speed, a 0.5s post-shatter freeze immunity would force a real thaw window. Implementation cost: one new field on the ant (`shatterImmuneUntil: simTime + 0.5`), checked in `applyStatus` for `freeze` kind. ~5 lines. Treat as a structural escape hatch, not a tuning preference.

### Tier 3 — Visual polish dials (no balance impact)

- **(8.6)** Particle count 10 → 8 or 12.
- **(8.7)** Particle lifetime 0.18 → 0.22.
- **(8.8)** Particle color `#88f0ff` → `#aaf0ff` (brighter), `#66d8e8` (more saturated), or `#b0e8ff` (paler/icier).
- **(8.9)** Particle radial speed 80-140 → range tweak.

### Decisions explicitly NOT on the dial list

| Looks like | Don't touch | Reason |
|---|---|---|
| `freeze.damageAmp 1.5 → x` | Plan 3 value, shared with Cryo | Affects non-Brittle synergies. Brittle multiplier (8.3) is the right global Brittle dial. |
| Removing a cannon from `family: 'heavy'` | Family membership | Family is shared with the wave-15 unlock UI logic. Re-classification has cross-system ripples. |
| Adding `freezeResist` to bosses | New schema | Universal `armor` already covers boss survival math. New schema = new bug surface. |
| Splash-shatter propagation | Spec §1 non-goal | The "ant-liberator" anti-pattern. Don't reverse this. |

### Queen note (cross-plan)

The frozen-Queen one-shot scenario (Annihilator on a frozen Queen — 360 dmg armor-pierced vs 400 HP = effective one-shot) **is intended.** A frozen Queen + Annihilator = an executed Queen.

If playtest shows this trivializes the wave-18-22 Queen pressure, the fix is **Queen rebalance** (Plan 4 status doc §117 already flagged this) — not a Brittle nerf. That dial lives in the Queen rebalance plan, not Plan 6.

### Tuning playbook for the implementer

If §10 playtest reveals problems, work the dials in this order:

1. King-too-fragile? → 8.1 (HP bump). Re-test.
2. Still fragile? → 8.2 (armor bump). Re-test.
3. Still fragile, AND the issue is specifically AP cannons? → 8.3 (Brittle 1.75). Re-test.
4. Re-freeze cycles feel exploitative? → 8.4 (freeze duration tighten). Re-test.
5. Still exploitative? → 8.5 (immunity window). Last resort.
6. Visual reads wrong? → 8.6/8.7/8.8/8.9 in any order, doesn't affect math.

**Ship 0.12.0 with all dials at default.** Tuning happens in playtest, not pre-emptively.

---

## §9 — Acceptance Criteria

| # | Item | Verification path |
|---|---|---|
| 1 | `applyDamage` signature has 5 params: `(ant, amount, armorPierce, sourceCannon, isDirectHit)` | `grep` confirms signature at line 4034 |
| 2 | Brittle predicate block exists inside `applyDamage`, between the `poison.damageAmp` block and the armor mitigation block; uses nested ifs (no `&&`); checks `isDirectHit` AND `sourceCannon` AND `CANNON_KINDS[sourceCannon.kind].family === 'heavy'` AND `ant.effects.freeze` | `grep` confirms predicate structure |
| 3 | Only line 4357 (projectile-collision call) passes `true` as 5th arg; all other `applyDamage` call sites (3599, 3656, 3696, 3726, 3822, 3841, 4157) remain 4-arg | `grep applyDamage\(` confirms |
| 4 | `spawnShatterParticles(x, y)` factory exists, `game.shatterParticles` initialized as empty array, `updateShatterParticles(dt)` and `drawShatterParticles(ctx)` wired into main loops | `grep` + Playwright reads `window.__ab.shatterParticles` is `[]` at game start (per the `window.__ab = game` introspection alias at line ~5155) |
| 5 | **Brittle math, baseline:** Discus direct hit on a frozen unarmored worker (10 HP, armor 0) deals exactly 12 dmg → one-shot kill. `4 × 1.5 × 2.0 = 12` | Playwright probe: spawn frozen worker, fire Discus, assert worker dies in one hit |
| 6 | **Brittle math, max stack:** Annihilator direct hit on a frozen, Biohazard'd King (3000 HP, armor 0.5, armorPierce active) deals exactly 450 dmg. `120 × 1.5 × 1.25 × 2.0 × 1.0 = 450` | Playwright probe: stack freeze + poison.damageAmp + Brittle, fire Annihilator, assert HP delta == 450 |
| 7 | Freeze status is removed atomically with the shatter: after a heavy direct hit on a frozen ant, `ant.effects.freeze === undefined` and `game.shatterParticles.length` increased by exactly 10 | Playwright probe |
| 8 | **Visual gating correct.** Three negative cases must show no shatter visual and no Brittle multiplier: (a) heavy direct hit on non-frozen ant, (b) quick-family direct hit on frozen ant, (c) heavy splash hit on a frozen bystander (not the direct-hit target) | Playwright probes for each, confirm `shatterParticles.length` unchanged and damage matches the no-Brittle math |
| 9 | Phase ant during its intangible window (`ant.intangible === true`) blocks the projectile at line 3558 short-circuit; `applyDamage` not called; freeze status persists; no shatter visual | Playwright probe: freeze a Phase ant, fire heavy during intangible window, assert no shatter and freeze still on ant |
| 10 | Plague-poisoned ant shattered to death: `poison.spreads === true` propagation fires from `killAnt`'s `onTargetDeath` cascade. Neighbors within spread radius receive poison; cyan shatter visual + standard splat + poison cloud all render on the death frame | Playwright probe: cluster of ants, central one Plague'd + frozen, Annihilator shatter, assert neighbors get poison |
| 11 | Particles tick in real-time, not sim-time. At 8× game speed, the cyan burst still lasts ~0.18s wall-time (visible for ~11 frames at 60fps) | Playwright probe at speed 8: trigger shatter, count frames where `shatterParticles.length > 0`, expect ~11 |
| 12 | PHP lint passes. `c:/wamp64/bin/php/php8.2.29/php.exe -l patterns/demo-ant-buster.php` returns "No syntax errors" | Command output |
| 13 | Hygiene check passes. `curl -s "http://localhost/dariobimbi.com/demos/ant-buster/" \| grep -c '&#038;&#038;'` returns 0; same for `'&amp;&amp;'` | Command output |
| 14 | Theme `Version:` bumped to 0.12.0 in `style.css`; served stylesheet URL has `?ver=0.12.0` | curl + grep on served homepage |
| 15 | Wave 22+ manual playthrough records: King time-to-die under Annihilator+Frost combo, AZ-spam re-freeze cadence (qualitative — does the thaw window feel readable?), shatter visual readability at 1× and 8× game speeds | Status doc playtest section |

**Acceptance summary:** 15 items. Items 1-4 verify code structure (grep). Items 5-11 verify mechanic + visual correctness (Playwright probes). Items 12-14 verify hygiene + version. Item 15 captures the human-eye playtest read.

---

## §10 — Notes for the Plan Author

### (10.1) Suggested task decomposition

Plan 6 should split into **6 tasks**, matching Plan 5's cadence. Each task ends with a bundled verify (PHP lint + hygiene grep + Playwright probe) before commit.

| Task | Focus | Files | Estimated touch |
|---|---|---|---|
| 1 | State init + factory + update + draw (the particle system in isolation) | `patterns/demo-ant-buster.php` | ~40 lines added |
| 2 | `applyDamage` signature change (5th param) + Brittle predicate insertion | `patterns/demo-ant-buster.php` | ~10 lines added, 1 line modified |
| 3 | Call-site update at line 4357 (single-line edit, pass `true`) | `patterns/demo-ant-buster.php` | 1 line modified |
| 4 | Hygiene + theme version bump 0.11.0 → 0.12.0 | `style.css` | 1 line modified |
| 5 | Playtest run + status doc draft (analogous to Plan 5 Task 6) | `docs/superpowers/plans/2026-05-08-ant-buster-plan-6-status.md` | new file |
| 6 | Auto-memory update + final commit | memory + status doc | small touches |

Tasks 1-4 are mechanical and dispatch to **haiku** subagents cleanly. Task 5 (playtest interpretation + architectural decisions write-up) is judgment-heavy and should go to **sonnet**. Task 6 is housekeeping.

### (10.2) Order-of-operations constraints

1. **Task 1 before Task 2.** The Brittle predicate in Task 2 calls `spawnShatterParticles`. Calling a function that doesn't exist yet → ReferenceError on first shatter.
2. **Task 2 before Task 3.** The call-site update in Task 3 passes a 5th arg to `applyDamage`. If the signature still has 4 params, the 5th arg is silently swallowed — predicate never fires, no error, but tests fail.
3. **Task 4 (version bump) any time after the code is in.** Should land before Task 5's playtest so the served page has the new code.

### (10.3) Architectural reference point

Before writing the Brittle predicate in Task 2, the implementer should **read the existing `freeze.damageAmp` block at lines 4048-4053**. It is the structural sibling — same kind of in-applyDamage status read, same nested-if convention, same multiplicative composition. Brittle should match its style.

### (10.4) Real-time `dt` — find it via screenshake

Per §7 case 6, `updateShatterParticles(dt)` must receive **real-time** dt, not sim-time. The implementer should grep for the screenshake update call (introduced in Plan 4 — Earthshaker mag 8, Annihilator mag 5) and use the same dt variable.

```bash
# Suggested grep to find the right variable
grep -n "screenshake\|shake" patterns/demo-ant-buster.php | head -20
```

Match whatever variable name the screenshake update uses. Don't invent a new one.

### (10.5) Refactoring temptations to resist

| Suggestion | Resist because |
|---|---|
| "5 params on `applyDamage` is too many — refactor to options object" | Out of scope. Signature change works as-is. Future plan can refactor. |
| "Particles should use a pool/quota for performance" | Worst case is ~14 active particles per frame (§5 budget). Negligible. YAGNI. |
| "Default `isDirectHit` to `true` so I don't have to update other call sites" | Direct-hit-only is the §1 design intent. Defaulting to true would silently turn splash/beam/aura into Brittle triggers. |
| "Splash should also shatter, just at half-strength" | §1 explicit non-goal. The "ant-liberator" anti-pattern was already worked through. |
| "Add a new `STATUS_KINDS.brittle` for symmetry with the other statuses" | Brittle is a transient predicate, not a status. It has no duration, no per-tick, no apply/expire. A status entry would be ceremonial overhead. |
| "Add an `isHeavyFamily()` helper for cleaner reads" | Spec uses inline `CANNON_KINDS[k].family === 'heavy'`. Helper is a one-liner that adds an indirection layer. Match the existing inline-read convention from `damageModifier` blocks. |

### (10.6) Suggested playtest scenarios

Run these in Task 5. Each is analogous to Plan 5's Scenario 2 / Scenario 4 setup.

**Scenario A — King boss-melt benchmark.**

- Wave 25 (King fight, post-Plan-5).
- Setup: 1 Absolute Zero + 1 Annihilator, both within range of the King's wander zone.
- Measure: King time-to-die in seconds. Royal Guard kills during the fight. Cake state at King-death.
- Expected: ~28-35s King melt, 0-2 RGs reaching cake. If RGs reach cake more than 3 times before King dies, flag as needing §8.1 dial.

**Scenario B — Wave-clear sanity (no boss).**

- Wave 18 (Queen wave) or Wave 20 (heavy mixed wave).
- Setup: Frost cluster + 1 Earthshaker covering the lane.
- Measure: Whether the Earthshaker shatters frozen ants in the cluster as expected, AND whether splash bystanders correctly DON'T shatter (visual gating §5).
- Expected: Direct-hit ants in the splash radius shatter visibly; bystanders die from splash damage without cyan bursts. Confirm via slow-motion video capture or 0.5× speed playthrough.

**Scenario C — Triple-stack interaction.**

- Wave 15+ on a Plague-poisoned cluster.
- Setup: Plague + Cryo + Demolisher all in range.
- Measure: Whether the cascading status interactions fire correctly. Does the Demolisher's splash poison trigger Plague spread on the bystander deaths? Does the direct-hit-frozen ant shatter visibly? Does the poison cloud propagate from the shattered death?
- Expected: All cascades fire cleanly. No console errors. Visual: cyan burst (direct hit) + green poison clouds (Plague spread) + standard splat (death) all render on overlapping frames.

### (10.7) Status doc + branch naming

**Branch naming:** `ant-buster-plan-6` (matches Plan 5 convention).

**Status doc path:** `docs/superpowers/plans/2026-05-08-ant-buster-plan-6-status.md`. Structure should mirror Plan 5's status doc:

1. What shipped (one-paragraph summary).
2. Branch base + Plan 6 commit table.
3. Acceptance criteria walkthrough (table with PASS/FAIL for each §9 item).
4. Playtest results (the three scenarios in 10.6).
5. Architectural decisions worth flagging (likely 3-5: the multiplicative composition, the `isDirectHit` param choice, the no-immunity-window decision, the family-not-threshold decision, and the direct-hit-only-not-splash decision).
6. Plan 7+ candidates (carryover from Plan 5's Plan 6+ list, minus Brittle which we just shipped).

**Auto-memory update:** add `ant_buster_plan_6_shipped.md` and update `MEMORY.md` index. Reference the merge SHA, theme version 0.12.0, and the §6 reference card for future maintainers.

### (10.8) The single most likely failure mode

If exactly one thing goes wrong during implementation, it'll be **the inline-script `&&` encoding gotcha**. The Brittle predicate is the most condition-dense block we've added since Plan 4. The nested-if structure in [6.4] is correct, but a reviewer might suggest "this is verbose, use `&&`" — and the `&&` will pass local PHP lint, then HTML-encode on serve, then break the JS parser.

**Mitigation:** Hygiene check (item §9.13) is mandatory after Task 2. If the curl-and-grep returns anything other than 0, the predicate must be rewritten with nested ifs before the task commits. **Do not attempt to "fix" via escape characters or string concatenation** — only the nested-if rewrite is reliable per CLAUDE.md.

---

## End of Spec

Plan 6 design is locked. Next step: invoke `superpowers:writing-plans` to produce the implementation plan at `docs/superpowers/plans/2026-05-08-ant-buster-plan-6.md`.
