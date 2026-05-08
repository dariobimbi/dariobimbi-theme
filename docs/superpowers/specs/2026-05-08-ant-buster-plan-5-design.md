# Ant Buster — Plan 5: Royal Guard & King-Fight Rebalance (Design)

**Date:** 2026-05-08
**Status:** DESIGN — pending user review, then transitions to implementation plan
**Predecessor:** Plan 4 (`docs/superpowers/plans/2026-05-08-ant-buster-plan-4-status.md`)
**Scope:** Single subsystem, 5-8 implementation tasks, contained within `patterns/demo-ant-buster.php`.

---

## 1. Goal

Restore the King fight's intended multi-tasking pressure by introducing a new ant kind, `royal_guard`, that the King's drip-spawn produces instead of the standard `worker`. The Royal Guard is mechanically distinct enough to dodge the Reaper's instakill modifier (which only special-cases `worker` and `scout`), to survive a beat of chip damage, and to feel like a "trained escort" rather than a re-skinned civilian.

The fix is surgical. The Reaper-vs-drip-Worker interaction was the single specific tension leak from Plan 4: a god-tier Quick cannon designed as a high-value sniper accidentally became an automated lawnmower for the King's secondary pressure, because its `kind === 'worker'` instakill check made the drip mechanically meaningless. Plan 5 routes around it without touching the Reaper's identity.

---

## 2. Out of scope (explicit)

- Reaper's instakill on Worker/Scout. Plan 4 identity choice. Plan 5 dodges it, does not nerf it.
- Hawkeye's 3× modifier vs Queen. Separate balance concern flagged in `2026-05-08-ant-buster-plan-4-status.md:117`. Affects mid-game Queen stress, not the King fight.
- Threshold-summoned Soldiers (the 4 spawned at 75/50/25% King HP). They aren't broken (no Reaper special-case, hp 40 + slowResist 0.5 already makes them threats). Re-skinning them would require either branching the sprite at draw-time on `source === 'threshold'` or splitting into a `royal_soldier` kind. YAGNI for a cosmetic.
- King's own stats (hp 3000, speed 10, armor 0.5, slowResist 0.5). All preserved. The King-fight rebalance comes entirely from the spawned-unit change, which keeps Plan 5 mechanically isolated.
- King's `canPickup: false`. Preserved. The whole Royal Guard mechanic depends on the King not being a viable cake-grabber.
- Drip-spawn position (10, 320). Preserved. The geography of the bottom-left drip line is part of the spatial-tradeoff design.
- Drip interval (2.5s). Preserved as part of mechanical isolation. May change as a Section 5 last-resort tuning dial.
- Plan 5+ candidates (Brittle status, Rubbery Resonance, per-tower targeting priority UI, distinct sprite silhouettes for the 18 god-tier rows, real PNG sprite art). Intentionally not bundled.

---

## 3. Architecture

The whole feature is plain content addition. Zero new primitives, zero kind-table shape changes, zero new render layers. Four touchpoints, all in `patterns/demo-ant-buster.php`:

1. **New `ANT_KINDS.royal_guard` row.** Same schema fields as `worker` and `soldier` (`hp`, `speed`, `slowResist`, `armor`, `gold`, `canPickup`, etc.). Living-ant rendering finds its sprite via `game.sprites[k.spriteKey]` (the existing path used by all ants, line 1373). No special-casing.

2. **Two new sprite factories.** `makeRoyalGuardSprite()` produces a Worker silhouette with a small gold crown overlay and a royal-blue accent on the body. `makeRoyalGuardSplat()` produces a solid `#2c3a8c` variant of the existing 16×16 splat (mirror of `makeAntSplat`). Both registered in the sprites table at boot (around line 982-983).

3. **King's drip-spawn swap.** The King's `onSpawn` registers a periodic `spawnJobs` entry that calls `spawnAntAt('worker', 10, 320, 'drip', ...)` at line 2196. Swap the kind string from `'worker'` to `'royal_guard'`. One-character behavioral change. `spawnAntAt` (line 2309) takes an arbitrary kind key; works without modification.

4. **Splat draw-site conditional.** The dead-ant render path picks `game.sprites.splat` for any non-delivered dead ant (line 1368). Replace with a nested-if that picks `royalGuardSplat` when `a.kind === 'royal_guard'`. One conditional, one site, no new draw layer.

**Notably absent:** no new `STATUS_KINDS` entries, no projectile-system changes, no Reaper modifier changes. The Reaper's `kind === 'worker'` check naturally falls through for `'royal_guard'` to its default `1×` modifier (or `2×` at full HP). That fall-through is the entire bypass.

**File footprint:** every change is in `patterns/demo-ant-buster.php`. No template, theme.json, or `inc/` changes. Total lines of new code: ~50 (sprite factory ~20, splat factory ~7, ANT_KINDS row ~16, sprite-table entries 2, conditional expansion ~3 net, drip-string swap 1).

---

## 4. ANT_KINDS row

Placement: immediately after the `worker` row in `ANT_KINDS`. Adjacent-to-counterpart placement makes the stat delta legible at a glance for future tuning.

```js
royal_guard: {
  name: 'Royal Guard',
  hp: 20,
  speed: 35,
  carryingSpeedMult: 1.5,    // matches worker — sprint with the cake
  gold: 4,
  alignment: 0.3,            // +0.1 vs worker — reads as "trained, more on-task"
  jitterRate: 3.0,           // slightly less twitchy than worker (4.0)
  wanderFreqMin: 0.3, wanderFreqMax: 0.7,    // matches worker
  wanderAmpMin: 0.9, wanderAmpMax: 1.5,      // matches worker
  spriteKey: 'royal_guard',
  spriteW: 16, spriteH: 16,
  slowResist: 0.3,
  damageMult: 1,
  pierceMult: 1,
  canPickup: true,
  armor: 0.2
},
```

### Design rationale per field

- **`hp: 20`**: 2× Worker. Survives a Hailstorm pellet salvo. Reaper still one-shots them at full HP (60 base × 2× modifier × (1 - 0.2 armor) = 96 effective damage), which is fine. The value of the bypass is not survival; it is the *fire-rate tax* (see §5 King-Spawn Swap).
- **`speed: 35`**: slightly faster than Worker (30). Visually distinguishable in motion. Not so fast that they slip past mid-game towers.
- **`carryingSpeedMult: 1.5`**: preserved from Worker. They flee-sprint with the cake. The whole point is that they're a viable cake-grabber.
- **`alignment: 0.3` and `jitterRate: 3.0`**: small bumps from Worker's `0.2 / 4.0`. Pathing reads as "more disciplined, less random." Visible over a 2.5s drip cycle as a "marching" feel rather than the "scurrying" of a civilian Worker.
- **Wander shape unchanged from Worker**: keeps the drip-ant path-feel familiar so the player's mental model still applies. Discipline shows in alignment, not in wander.
- **`slowResist: 0.3`**: Frost cannons still slow them, just less. A pure slow-build is no longer the universal answer.
- **`armor: 0.2`**: light fractional mitigation. Plan 3's existing armor system at line 4008-4012: `dmg = dmg * (1 - armor)`. `armorPierce: true` cannons (Railgun line, Sniper line, Particle Cannon, Annihilator) still bypass it entirely. Reflects "guard armor."
- **`canPickup: true`**: the whole point. They are the King's *real* threat, since the King himself can't pick up.
- **`damageMult: 1`, `pierceMult: 1`**: defaults. Schema-uniform pattern from Plan 3.

### Stat hierarchy after this lands

| Kind | HP | Speed | slowResist | armor | gold | canPickup |
|---|---|---|---|---|---|---|
| Worker | 10 | 30 | 0 | 0 | 2 | yes |
| Scout | 6 | 60 | 0 | 0 | 3 | yes |
| **Royal Guard** | **20** | **35** | **0.3** | **0.2** | **4** | **yes** |
| Soldier | 40 | 20 | 0.5 | 0.5 | 5 | yes |

---

## 5. King-spawn swap

### The one-string change

The King's `onSpawn` block at line 2190-2199:

```js
onSpawn: function(king) {
  game.spawnJobs.push({
    interval: 2.5,
    lastFiredAt: game.time,
    owner: king,
    fn: function() {
      spawnAntAt('royal_guard', 10, 320, 'drip', { x: 10, y: 320 });   // was 'worker'
    }
  });
},
```

Diff is one quoted string. `source: 'drip'` plumbing from Plan 4 stays — future-friendly discriminator for any plan that wants a "King-loyal" trait shared across drip-spawned units.

### Why the drip interval stays at 2.5s

**Mechanical isolation.** Plan 5's whole pitch is surgical: one variable (the spawn kind) so any King-fight feel-change is unambiguously attributable to the Royal Guard, not to compounded interval tuning. If we change two things and the encounter feels off, we'd be re-running playtests just to A/B back to the right interval.

**Fire-rate tax already in tune.** Reaper at `fireRate: 0.5` fires one shot every 2 seconds; King drip is every 2.5 seconds. A single Reaper is ~80% consumed by drip-suppression at this interval, leaving ~20% for the King. That clean tradeoff disappears if the interval moves: speed it up to 2.0s and a single Reaper can't keep up at all (drip outpaces fire rate); slow it down to 3.5s and one Reaper has spare cycles for the King anyway.

**Implicit difficulty bump from speed 35 vs 30.** Each Royal Guard reaches the cake ~13% faster than a Worker would (~7.7s vs ~9s) under the same drip interval, plus they survive better under chip damage. More Royal Guards in transit at any given moment than there were Workers. That is an intended difficulty bump from the kind change alone, no need to also crank the interval.

### The spatial tradeoff (emergent design)

Targeting at line 3492-3517 is **nearest-in-range, with revealed-target preference**. No kind-priority, no HP-priority, no carrier-priority. Whichever ant is closest to the cannon gets the shot.

That changes the framing of the fire-rate tax. It is not an automatic dynamic. It is **positional**:

- **Reaper near (10, 320)** preferentially shoots each Royal Guard as it appears. King-DPS suffers; drip is suppressed.
- **Reaper near the King's wander zone** preferentially shoots the King. Royal Guards walk past unimpeded.
- **Reaper between the two** depends on which is closer at any tick. Awkward; players will learn this is the wrong spot.

The King has a different path from the Royal Guards. King wanders slowly from his spawn (mid-canvas, with `alignment: 0.4`); Royal Guards file from (10, 320) with `alignment: 0.3` toward the cake. Two distinct paths. The player has to make a *spatial* decision: cover the drip line, stack on the King, or split. That is a richer tradeoff than auto-suppression, and it falls out of the kind-string change with no extra code.

---

## 6. Splat logic

### Why 16×16, not scaled to HP

Splat size doesn't currently track anything in this codebase. All ant kinds use the same 16×16 splat today: Worker (16×16 sprite), Scout (12×12 sprite), Soldier (16×16), Tank (presumably larger sprite, still 16×16 splat). Introducing HP-based scaling would teach the player "splat size = HP" against a corpus where it explicitly does not, and the Tank's small standard splat would suddenly feel inconsistent.

The visual rhyme is "same silhouette, different color," matching the living-sprite distinction (Worker silhouette + crown + blue accent = Royal Guard sprite; Worker splat + blue tint = Royal Guard splat). Identical structure, single varied attribute.

No `splatScale` or `splatColor` field. One consumer doesn't earn a config field.

### The three code changes

**1. New factory** — exact mirror of `makeAntSplat()` at line 353-361 with the fill color swapped. Goes immediately after `makeAntSplat` to keep the splat factories adjacent:

```js
function makeRoyalGuardSplat() {
  var c = makeCanvas(16, 16);
  var x = c.getContext('2d');
  x.fillStyle = '#2c3a8c';
  x.fillRect(4, 7, 8, 3);
  x.fillRect(5, 6, 6, 1);
  x.fillRect(5, 10, 6, 1);
  return c;
}
```

**2. Living-ant sprite factory** — `makeRoyalGuardSprite()`. Worker silhouette + small gold crown + royal-blue accent on the body. Goes adjacent to `makeWorkerSprite()` for proximity. Crown is **forward-offset toward the mandibles** so the carrying-slice overlay (which sits on the ant's back/center when the Royal Guard hauls cake) doesn't completely bury the crown.

Approximate shape (final pixel work belongs in implementation):

- Base: copy of Worker sprite (existing pixel pattern).
- Crown: 4-5 px gold pattern (`#fee761` matching the god-tier halo color from Plan 4) at the front of the head, offset toward the mandibles.
- Accent: replace 1-2 body pixels with royal blue (`#2c3a8c`) — a "sash" or "shoulder pauldron" beat. Subtle. The crown does the heavy lifting.

**3. Sprite-table registration** at line 982-983 — append two lines:

```js
splat: makeAntSplat(),
royalGuardSplat: makeRoyalGuardSplat(),    // new
// ... and elsewhere in the same table:
worker: makeWorkerSprite(),
royal_guard: makeRoyalGuardSprite(),       // new
```

**4. Draw-site conditional** at line 1364-1371. Currently:

```js
if (a.dead) {
  if (a.deliveredAt === 0) {
    sprite = game.sprites.splat;
  } else {
    continue;
  }
}
```

After:

```js
if (a.dead) {
  if (a.deliveredAt === 0) {
    if (a.kind === 'royal_guard') {
      sprite = game.sprites.royalGuardSplat;
    } else {
      sprite = game.sprites.splat;
    }
  } else {
    continue;
  }
}
```

Nested ifs, not a ternary. The immediately surrounding code (the comment at line 1365-1366) uses nested ifs as the local convention to dodge the `&&` HTML-encoding gotcha called out in `CLAUDE.md`. Match the function-local pattern.

**Total footprint:** ~50 lines added (sprite factory ~20, splat factory ~7, ANT_KINDS row ~16, table entries 2, conditional expansion ~3 net, drip-string swap 1). `makeWorkerSprite()` at line 189 is the size baseline for the new sprite factory: 14 lines of pixel ops in 16×16. The Royal Guard variant adds a crown overlay (~3 lines) and an accent pixel pair (~1-2 lines) on top of that base.

---

## 7. Balance & tuning

### Starting values (locked from §4-§6)

| Field | Value | Section |
|---|---|---|
| Royal Guard HP | 20 | §4 |
| Royal Guard speed | 35 | §4 |
| Royal Guard slowResist | 0.3 | §4 |
| Royal Guard armor | 0.2 | §4 |
| Royal Guard gold | 4 | §4 |
| Royal Guard alignment | 0.3 | §4 |
| King drip interval | 2.5s (unchanged) | §5 |
| King HP | 3000 (unchanged) | §5 |
| Threshold-Soldier count | 4 per threshold (unchanged) | §5 |
| Splat color | `#2c3a8c` | §6 |
| Crown color | `#fee761` | §6 |

### Tuning dial hierarchy

Each failure mode lists dials in **preference order** — try the first one before reaching for the second.

| Failure mode | Primary dial | Secondary dial |
|---|---|---|
| Royal Guards die too fast (Stormcaller / Hailstorm chain-deletes them) | HP 20 → 25 | armor 0.2 → 0.3 |
| Royal Guards survive too well (chip damage doesn't dent them) | HP 20 → 15 | armor 0.2 → 0.1 |
| Royal Guards reach the cake too often | speed 35 → 32 | drip interval 2.5s → 3.0s |
| King dies too fast despite distraction tax | King HP 3000 → 3500 | King armor 0.5 → 0.6 |
| King fight feels impossible | drip interval 2.5s → 3.0s | threshold-Soldier count 4 → 3 |
| Slow-builds (Frost line) trivialize Royal Guards | slowResist 0.3 → 0.2 | slowResist 0.2 → 0.1 |

### Playtest scenarios

Five quick wave-25 runs. The `?debug=1 W` hotkey jumps directly to wave-16 prep with the god-tier unlock fired.

1. **No Reaper, generic god-tier roster.** Tests Royal Guard HP vs Hailstorm / Stormcaller / Phoenix.
2. **One Reaper, placed near (10, 320).** Tests the drip-suppression dynamic. Reaper should feel ~80% consumed by the drip line.
3. **One Reaper, placed near the King's wander zone.** Tests the converse. Royal Guards should slip past, King should melt.
4. **Two Reapers, split across both zones.** Tests the spatial tradeoff. Should feel like the "winning" configuration without being trivial.
5. **Frost-heavy build** (Cryo / Blizzard / Absolute Zero). Tests `slowResist 0.3`.

For each scenario, log: did the King die? did any Royal Guards reach the cake? how did the player feel about the difficulty?

---

## 8. Acceptance criteria

A condensed §6-style checklist for the implementation plan to verify against:

1. `ANT_KINDS.royal_guard` exists with the §4 stats.
2. King's `onSpawn` drip-job spawns `'royal_guard'`, not `'worker'`.
3. Reaper's `damageModifier({kind:'royal_guard'})` returns `2` at full HP, `1` otherwise — no special case in the modifier function itself.
4. `game.sprites.royalGuardSplat` exists; dead Royal Guards render in royal blue.
5. `game.sprites.royal_guard` exists; living Royal Guards render with the crown overlay sprite and blue accent.
6. Royal Guards pick up the cake (`canPickup: true`) and flee-sprint at 1.5×.
7. PHP lint passes (`c:/wamp64/bin/php/php8.2.29/php.exe -l patterns/demo-ant-buster.php`).
8. Hygiene check passes (no `&#038;&#038;` or `&#124;&#124;` in served HTML — `curl -s URL | grep -F '&#038;&#038;'`).
9. Theme `Version:` bumped (cache-bust + memory anchor for the next session).
10. Wave 25 manual playthrough records which failure modes (if any) need a tuning-dial pass per §7.

---

## 9. Notes for implementation-plan author

- Branch off `main` as `ant-buster-plan-5`. Plan 4 merged 2026-05-08 as `8b4048b`, theme version 0.10.0. Plan 5 should land at version 0.11.0.
- Estimated 5-8 tasks with TDD-ish cadence (write, lint, hygiene, Playwright verify, commit per task).
- The `spawnAntAt` function at line 2309 takes an arbitrary kind key. No spawn-side changes needed beyond the one-string swap.
- Reaper's `damageModifier` at line 3259-3266 is **already correct** for Royal Guards (the `kind === 'worker'` check naturally falls through). Touch it only if the implementation plan needs to add a `royal_guard` test assertion to the existing Plan 4 console probes.
- The Plan 4 `?debug=1` hotkey ecosystem (`G` for god-tier-unlock toggle, `W` for jump-to-wave-16-prep) is already in place. Plan 5 doesn't need new hotkeys.
- Bump `style.css` `Version:` to `0.11.0`.
- After the merge, update `MEMORY.md` and add `ant_buster_plan_5_shipped.md` summarizing what landed.
