# Ant Buster — Plan 3 Design Spec

**Status:** Design complete. Pending user review before transitioning to the implementation plan.
**Date opened:** 2026-05-07
**Author:** Dario, with Claude assist
**Parent spec:** [`2026-05-06-ant-buster-design.md`](./2026-05-06-ant-buster-design.md)
**Plan 2 spec:** [`2026-05-07-ant-buster-plan-2-design.md`](./2026-05-07-ant-buster-plan-2-design.md)
**Plan 2 status:** [`../plans/2026-05-07-ant-buster-plan-2-status.md`](../plans/2026-05-07-ant-buster-plan-2-status.md)

---

## 1. Goal of Plan 3

**Plan 3 = content fill.** Take Plan 2's systems backbone (kind tables for cannons / ants / statuses, wave state machine, status framework, soft repulsion, fallen slices, projectile pipeline) and pour content into it: the full non-god cannon roster, all 25 spec-canonical waves, and the King boss fight that wave 25 has been waiting for.

The split was chosen against bundling shell + content + audio + polish into a single sprawling Plan 3. Each of those is its own coherent slice. Plan 3 stays focused on "make the run longer and harder," with the explicit understanding that Plan 4 owns the bookends (title screen, save state, tutorial, win/lose overlays, sound, endless mode, god tier). See §4 for the deferral list.

After Plan 3 ships, the demo plays as a 25-wave campaign with 21 cannons of upgrade depth and a real final boss. The visual treatment of *arriving* at the win state is unchanged from Plan 2 — Plan 4 owns the celebration.

---

## 2. What ships in Plan 3

### 2.1 Cannon system additions

**Eleven new mechanic primitives** added to the projectile / cannon update pipeline. Plan 2 covered direct-hit, line-pierce, spool-up firing, multi-barrel volley, status-emitting projectile, and continuous beam with status emission. Plan 3 adds:

| ID | Primitive | Used by |
|---|---|---|
| MP-A | Splash AoE on impact (radius + falloff damage) | Mortar, Hellfire, Patriot, Shockwave |
| MP-B | Arcing ballistic projectile (parabolic flight, fixed land time) | Mortar, Hellfire |
| MP-C | Homing projectile (per-tick re-target, turn-rate cap) | Patriot |
| MP-D | Returning projectile (out → hit → reverse → second hit) | Discus, Ricochet |
| MP-E | Multi-pellet shotgun cone (N pellets per fire, spread arc) | Buckshot |
| MP-F | Single-barrel hose with aim jitter (Vulcan minus spool-up + spray) | Minigun |
| MP-G | Continuous beam, direct-damage variant (Glacier minus status, plus damage) | Lance |
| MP-H | Cone DoT area (continuous cone of per-tick damage + status) | Plasma Torch |
| MP-I | Bonus-damage modifier hook (per-cannon damage formula: vs full-HP, vs Queen, etc.) | Marksman, Sharpshooter, Phantom |
| MP-J | Tesla arc-jump (chain to N nearest, per-jump falloff, instant-resolve with polyline render) | Tesla |
| MP-K | Carrier reveal + global target priority (`ant.isRevealed` flag set by any Spotter in range) | Spotter |

The status framework already shipped in Plan 2 covers `freeze`, `burn`, `poison`, `knockback`, and `stun`. Plan 3 adds cannons that emit them but does not extend the framework itself, except for one small addition: a `STATUS_KINDS.poison.onTargetDeath` hook used by Plague to spread poison to ants within 30 px when a poisoned target dies.

**Refactor:** Plan 2's single `pierce` flag couples line-pierce (passes through N ants) with armor-pierce (full damage vs armored targets like Tank and Soldier). Plan 3 splits this into two flags:

- `linePierce: N` — projectile passes through up to N ants before despawning
- `armorPierce: true` — projectile deals full damage against armored ants (bypasses the 50% mitigation)

Railgun (1.11) keeps both. Demolisher (1.12) gets `armorPierce` only — single-target armor-bypass, no line pass-through.

**Twenty-one new CANNON_KINDS rows.** All numbers from parent spec §3.1–3.3, locked.

#### Heavy family (Ordnance) — 7 new cannons

| ID | Name | Tier | Damage | Fire rate | Range | Special | Upgrade cost |
|---|---|---|---|---|---|---|---|
| 1.2 | Mortar | mid | 8 | 0.5/s | 130 | splash radius 40, splash 4 | +60 |
| 1.3 | Discus | mid | 4 | 0.4/s | 150 | hits twice (out + return) | +60 |
| 1.12 | Demolisher | final | 60 | 0.25/s | 110 | armor-pierce | +110 |
| 1.21 | Hellfire | final | 15 | 0.4/s | 180 | arcing shell, splash radius 60, splash 10 | +90 |
| 1.22 | Patriot | final | 20 | 0.5/s | 200 | homing, splash radius 30, splash 8 | +110 |
| 1.31 | Ricochet | final | 8 | 0.4/s | 180 | hits 3 times (out, pierce, return) | +90 |
| 1.32 | Shockwave | final | 50 | 0.15/s | 130 | splash radius 80, splash 30, brief screenshake | +110 |

Branch shape after Plan 3: Howitzer → {Siege, Mortar, Discus} → {Railgun ∨ Demolisher, Hellfire ∨ Patriot, Ricochet ∨ Shockwave}.

#### Quick family (Precision) — 7 new cannons

| ID | Name | Tier | Damage | Fire rate | Range | Special | Upgrade cost |
|---|---|---|---|---|---|---|---|
| 2.2 | Marksman | mid | 8 | 0.6/s | 250 | bonus damage vs full-HP | +70 |
| 2.3 | Spotter | mid | 3 | 2/s | 200 | reveals Carriers (sets global `ant.isRevealed`) | +60 |
| 2.12 | Plasma Torch | final | 1/tick | 10/s | 70 (cone 60°) | continuous, ignites for +1/s for 2s | +90 |
| 2.21 | Sharpshooter | final | 25 | 0.5/s | 350 | +50% dmg vs full-HP, ignores 50% slow penalty | +100 |
| 2.22 | Lance | final | 4/tick | continuous beam | 250 | no projectile travel, never misses | +110 |
| 2.31 | Phantom | final | 40 | 0.3/s | 450 | bonus damage vs Queen +100% | +110 |
| 2.32 | Tesla | final | 10 | 1/s | 200 | arcs to 3 nearest ants within 60 px each, dmg falls 50% per jump | +100 |

Branch shape after Plan 3: Repeater → {Auto-Cannon, Marksman, Spotter} → {Vulcan ∨ Plasma Torch, Sharpshooter ∨ Lance, Phantom ∨ Tesla}.

#### Double family (Swarm Control) — 7 new cannons

| ID | Name | Tier | Damage | Fire rate | Range | Special | Upgrade cost |
|---|---|---|---|---|---|---|---|
| 3.1 | Triple Threat | mid | 2 (×3 barrels) | 1/s | 110 | fires 3 shots per volley, slight spread | +70 |
| 3.3 | Toxin | mid | 1 | 1/s | 100 | applies 5 dmg over 5s (DoT, refreshes, doesn't stack) | +75 |
| 3.11 | Buckshot | final | 2 (×5 pellets) | 0.8/s | 90 | shotgun cone 50°, all pellets land in arc | +90 |
| 3.12 | Minigun | final | 1 | 14/s | 120 | single barrel, hose of fire, slight spray inaccuracy | +110 |
| 3.22 | Cryo | final | 3 | 1/s | 110 | freezes target solid for 0.8s (0 movement, takes +50% damage) | +110 |
| 3.31 | Plague | final | 2 | 1/s | 110 | 15 dmg over 6s, spreads to ants within 30 px on target's death | +90 |
| 3.32 | Sonic Pulse | final | 5 | 0.8/s | 100 | knocks ants back 30 px, briefly stuns 0.4s | +90 |

Branch shape after Plan 3: Twin Gun → {Triple Threat, Frost, Toxin} → {Buckshot ∨ Minigun, Glacier ∨ Cryo, Plague ∨ Sonic Pulse}.

**Tesla rendering note.** Implemented as instant-resolve: target acquisition + damage application all happen in one tick. Visual is a polyline lightning bolt connecting tower → primary → jump-1 → jump-2 → jump-3, drawn for ~0.15s before fading. The "hop" feel comes from the simultaneous flash of all targets, not from a moving particle. CPU cost is one Tesla volley = one tick of work, not three.

**Returning-projectile state for Discus / Ricochet.** Each projectile carries `originX, originY` set at fire time (the tower's coordinates). The return phase steers toward `(originX, originY)`, not the original target. If the target dies mid-flight on the out-leg, the projectile still completes the return arc and can hit anyone in its path — Ricochet's mid-leg pierce stays valuable even when the original target is gone.

### 2.2 Selected-tower panel — branching upgrade UI

Plan 2's panel shows a single upgrade button + sell button. Plan 3 generalizes:

| Cannon tier | Upgrade buttons rendered |
|---|---|
| Base (Howitzer / Repeater / Twin Gun) | 3 (the three mid options for that family) |
| Mid (e.g. Mortar) | 2 (the two final options on that mid's path) |
| Final (e.g. Hellfire) | 0 (god tier deferred to Plan 4) |

Layout: vertical stack of upgrade buttons inside the existing 160-wide panel, each labeled `<Name>  +<cost>g`, then the sell button beneath. Greyed out if `gold < cost`. Click commits the upgrade in place (preserving position, target, kindHistory per Plan 2's SYS-2). No confirmation prompt, consistent with Plan 2.

**Range-circle preview honesty.** Hovering an upgrade button shows the *upgrade target's* range circle, not the current cannon's. This applies to growth (Hellfire 130 → 180) and to shrinks (Plasma Torch 130 → 70). The Plasma Torch case matters: a player upgrading from Auto-Cannon expecting "more of the same" will see the cone-range collapse before they click, not after.

### 2.3 Wave structure additions (waves 6–25)

Twenty new entries appended to the `WAVES` array. Compositions taken verbatim from parent spec §4.2:

| Wave | Composition (in spawn order) | Notes |
|---|---|---|
| 6 | 12 Workers, 6 Scouts | |
| 7 | 8 Scouts, 6 Workers, 4 Scouts | Scout-heavy |
| 8 | 12 Workers, 2 Soldiers | Soldier intro |
| 9 | 6 Soldiers, 8 Workers | Armor focus |
| 10 | **Queen #2 (HP 600)** + 6 Soldiers + 6 Workers | Boss |
| 11 | 8 Workers, 4 Carriers, 6 Workers | Carrier intro (45 px/s flee, see §2.5A) |
| 12 | 4 Soldiers, 6 Carriers, 4 Scouts | Carrier swarm |
| 13 | 6 Splitters, 8 Workers | Splitter intro |
| 14 | 4 Splitters, 4 Soldiers, 6 Carriers, 8 Scouts | Mixed escalation |
| 15 | **Queen #3 (HP 800)** + 8 Splitters + 6 Carriers | Boss (no god-tier unlock — Plan 4) |
| 16 | 2 Tanks, 8 Workers | Tank intro |
| 17 | 6 Phase ants, 8 Workers, 4 Soldiers | Phase intro |
| 18 | 3 Tanks, 4 Phase, 6 Splitters | Late-game cocktail |
| 19 | 4 Bombers, 8 Workers, 4 Carriers | Bomber intro |
| 20 | **Queen #4 (HP 1200)** + 4 Bombers + 6 Tanks | Boss |
| 21 | 6 Tanks, 6 Phase, 8 Carriers | Endurance |
| 22 | 8 Bombers, 8 Splitters, 4 Soldiers | AoE pressure |
| 23 | 4 Tanks, 6 Phase, 6 Bombers, 8 Splitters, 8 Carriers, 12 Workers | "Everything wave" |
| 24 | 8 Tanks, 12 Carriers, 8 Bombers | Pre-boss attrition |
| 25 | **King (HP 3000)** + threshold-summons + secondary worker drip | Final boss (full mechanics in §2.4) |

**Pacing.** Each wave config gets a `prepTime` field (default 20s, boss waves explicitly 30s). Wave 1 keeps its existing 30s prep from Plan 2. Active spawning length stays emergent from spawn-interval × ant-count — no enforced cap.

**Wave-completion bonus** normalized to spec formula: `20 + waveNum`. Wave 5 pays 25g (replacing whatever Plan 2 was paying), wave 25 pays 45g. Plan 2's higher value was a development cushion; with the 21-cannon roster offering real choice, the tighter economy keeps upgrade decisions meaningful. **This is a balance change from Plan 2** — flag it explicitly in the task that ships waves 6–25.

**Send-Next-Wave-Early bonus** unchanged: `floor(remainingPrepSeconds / 2)` gold. Already in Plan 2.

**Boss banner copy:**

- Waves 10, 15, 20: `WAVE [N]: QUEEN` (uses Plan 2's existing boss-banner treatment)
- Wave 25: `FINAL WAVE: THE KING` (larger font, deeper-toned visual cue per parent spec §7.6)

### 2.4 King ant + wave 25 boss mechanics

**King ant** is the single new entry in `ANT_KINDS` for Plan 3. Per parent spec §4.1:

| Field | Value |
|---|---|
| `kind` | `'king'` |
| `hp` | 3000 |
| `speed` | 10 px/s |
| `gold` | 200 |
| `armor` | true (50% damage from non-armor-pierce) |
| `slowResist` | 0.5 |
| `canCarrySlice` | false |
| `barStyle` | `{ width: 60, height: 4, color: '#d04020', borderColor: '#fff', persistent: true }` |
| `sprite` | new procedural large regal silhouette (placeholder until Plan 5) |

The `barStyle` field is new and applies to all ANT_KINDS — default is the existing 16×2 red bar shown only after damage. Queen entries can opt into an intermediate style (e.g. `width: 32, color: '#a040c0'`) to escalate the visual progression. `persistent: true` makes the bar render from spawn instead of waiting for first damage; King is the only ant in Plan 3 with this set.

The King walks straight at the cake along the standard path. No erratic wandering — at 10 px/s and HP 3000, the wandering visual would feel wrong at his scale.

**Threshold-summon hook.** New `onHpThresholds` field on `ANT_KINDS`:

```js
king: {
  // ...
  onHpThresholds: [
    { at: 0.75, action: spawnSoldiersAtKing(4) },
    { at: 0.50, action: spawnSoldiersAtKing(4) },
    { at: 0.25, action: spawnSoldiersAtKing(4) },
  ],
}
```

Per-instance state: `firedThresholds: Set<number>`. The check runs inside `applyDamage`: if `hp/maxHp` crosses below a threshold not yet in `firedThresholds`, fire the action and add the threshold. Each fires exactly once per King, even if HP dips and recovers (no recovery mechanic exists today, but the once-only contract holds).

Soldiers spawn **at the King's current position**, not at the anthill. `spawnAnt('soldier', king.x, king.y, 'threshold')`. Each is a standard Soldier per Plan 2's ANT_KINDS — 40 HP, 50% slow resist, armored, 5g drop. 4 × 3 thresholds = 12 Soldier adds total over the fight.

**Continuous worker drip from secondary spawn.** New fixed map location at `(10, 320)` — the bottom-left corner, mirroring the main anthill's left-edge position vertically. Forces tower coverage to extend downward instead of stacking on the main lane.

The King's `onSpawn` hook registers a per-tick spawn job that fires every 2.5s while King is alive: `spawnAnt('worker', 10, 320, 'drip')`. Drip Workers are otherwise standard Workers (10 HP, 30 px/s, can pick up slices).

The King's `onDeath` hook clears the per-tick spawn job. Drip stops the moment King falls.

**Drip Workers flee to secondary spawn, not main anthill.** The flee-target logic reads `ant.spawnOrigin` (set on spawn) instead of a global home coordinate. `'wave'`, `'split'`, `'queen'`, `'threshold'` source ants get `spawnOrigin = (anthillX, anthillY)`. `'drip'` source ants get `spawnOrigin = (10, 320)`. See §2.5C.

**Wave 25 spawn config.**

```js
{
  index: 24,           // 0-indexed, wave 25
  isBoss: true,
  bannerText: 'FINAL WAVE: THE KING',
  prepTime: 30,
  entries: [
    { kind: 'king', count: 1, delay: 0 },
  ],
  // No initial worker queue — King's drip handles ongoing pressure.
}
```

**Wave-completion logic.** Plan 2's check `spawnComplete && activeAnts.length === 0` works unchanged for wave 25:

- `spawnComplete` flips true right after the King spawns (wave entry queue is empty)
- `activeAnts.length === 0` won't be true until King + threshold-Soldiers + drip-Workers are all cleared
- Drip Workers and threshold Soldiers naturally enter `game.ants`, so `activeAnts.length` accounts for them
- Drip stops on King death, so `activeAnts.length` drains naturally after the King falls

No special-casing for King in the wave-completion checker. **The King fight cannot be "leaked" by killing him while drip Workers are en route, and cannot be "won early" by clearing Soldiers while the King still walks.**

When wave 25 transitions to `'won'`, the run is complete. The wave bonus (45g) is paid. The visual treatment of arrival at the `'won'` state is unchanged from Plan 2.

**Balance.** Reaching wave 25 with maxed T3 finals across three chains gives the player roughly 30–60 sustained DPS. King at 3000 HP takes ~50–100s of focused fire — workable. The threshold-Soldiers are the real test, breaking focus fire and forcing the player to deal with armored adds while the King keeps walking. The 2.5s drip rate and 4-Soldier threshold count are starting points — playtest after Plan 3 ships and tune from there. **No god tier is required to clear wave 25 in Plan 3** (the spec's "god-tier required" line was aspirational; Plan 3 tunes the King to be hard-but-beatable with T3 finals).

### 2.5 Carryover items

#### 2.5A — Carrier flee-speed

Update `ANT_KINDS.carrier.carryingSpeedMult` from `0.7` (Plan 2 inherited from Plan 1) to `1.5`. Result: carriers move at 30 px/s out-bound, 45 px/s while carrying — matching parent spec §4.1.

The same multiplier applies to any ant that ends up in carrying state, not just spawn-Carrier ants. A Worker that grabs a slice and flips to carrying gets the 1.5× boost too. Consistent semantics: "carrying = fleeing at 1.5× base speed."

This is a meaningful gameplay shift — carriers transform from "slow vulnerable target" to "sprinter who must be killed before they reach the anthill." Counter-cannons are in Plan 3 scope (Spotter / Phantom for reveal + range, Sonic Pulse for knockback, Cryo for freeze).

#### 2.5B — Debug-cycle additions

Plan 2's `?debug=1 T` hotkey cycles 9 ant kinds. Plan 3 expands to 11:

```js
const DEBUG_KINDS = ['worker', 'carrier', 'scout', 'soldier', 'tank',
                     'splitter', 'mini_splitter', 'phase', 'bomber',
                     'queen', 'king'];
```

Adding `'mini_splitter'` (currently spawned only at runtime by Splitter onDeath) and `'king'` gives full type-coverage for visual + mechanic checks without forcing a 25-wave playthrough during development.

#### 2.5C — `spawnAnt` source parameter

Extend Plan 2's `spawnAnt(kind, x, y)` to:

```js
function spawnAnt(kind, x, y, source = 'wave') {
  // ...
  ant.spawnSource = source;
  ant.spawnOrigin = (source === 'drip')
    ? { x: 10, y: 320 }
    : { x: anthillX, y: anthillY };
  // ...
}
```

`source` values:

- `'wave'` — standard wave-queue spawn (default)
- `'split'` — Splitter onDeath mini-spawn
- `'queen'` — Queen onDeath worker spawn
- `'threshold'` — King HP-threshold Soldier summon
- `'drip'` — King continuous Worker drip from secondary spawn

Plan 3 reads `source` for two behaviors: (1) drip Workers route their flee target to secondary spawn via `ant.spawnOrigin`, and (2) the source value is recorded for telemetry / debug.

Plan 4 will hook spawn SFX off `source` (different sound for `'drip'` vs `'wave'`) — the parameter is forward-compatible without re-plumbing.

#### 2.5D — `ant.spawnOrigin` per-instance field

New field on every ant instance, set on spawn (see §2.5C). Flee-target logic reads `ant.spawnOrigin` instead of a global home coordinate. Plan 2's "flee back to anthill" logic generalizes to "flee back to spawn origin."

For all current ants except `'drip'` Workers, `spawnOrigin` is the main anthill — same behavior as Plan 2. The change is non-breaking.

### 2.6 Win/lose state

**Win state.** When wave 25 transitions to `'won'`, Plan 3 reuses Plan 2's existing `'won'` treatment unchanged. Plan 4 owns the parent spec §7.6 victory overlay (dimmed canvas, run stats, Continue-into-endless / Return-to-title buttons). Plan 3 ships **no new win-state UI**.

**Lose state.** Identical reuse — Plan 2's "cake hits 0 → Restart Run button" treatment carries forward. Plan 4 owns the richer game-over screen (run stats, Retry / Title buttons).

The honest framing: Plan 3 makes the run longer and harder. The visual moment of arrival at win or loss is identical to Plan 2. The payoff is the journey.

---

## 3. Architectural changes from Plan 2

### 3.1 Mechanic primitive expansion (no new framework)

The 11 new mechanic primitives (§2.1, MP-A through MP-K) plug into Plan 2's existing projectile / cannon update loop. Each is a behavior flag or function pointer on the CANNON_KINDS row, not a new system.

- Splash (MP-A): on-impact AoE function, takes `(x, y, radius, damage)`, walks `game.ants` and applies damage with optional falloff.
- Arcing (MP-B): projectile y-position is computed as `arcHeight * sin(t * π / travelTime)` over a fixed travel time. Visual only — collision still resolves on (x, target.y).
- Homing (MP-C): per-tick target lookup; if current target alive, steer toward; otherwise re-acquire nearest. Turn rate cap prevents 180° snaps.
- Returning (MP-D): two-phase projectile state, `phase: 'out' | 'return'`. On-hit-or-end-of-out flips to `'return'`, target becomes `(originX, originY)`. Collision on return phase damages anyone in path.
- Multi-pellet cone (MP-E): single fire spawns N projectiles with angle = baseAngle + random(-spread/2, +spread/2).
- Hose (MP-F): high fire rate + small per-shot angle jitter; reuses Plan 2's straight projectile pipeline.
- Beam-direct (MP-G): refactor of Plan 2's beam to support a damage-per-tick output alongside (or instead of) status emission. Glacier keeps emit-only behavior; Lance gets damage-only; Plasma Torch gets both (cone variant, see MP-H).
- Cone DoT (MP-H): every tick, find ants within cone (range + angle), apply per-tick damage and burn status. Render cone as a translucent triangle.
- Bonus damage (MP-I): per-cannon `damageModifier(target) → multiplier` function; default is `() => 1`. Marksman, Sharpshooter, Phantom override.
- Tesla arc-jump (MP-J): on-fire, find primary target, then chain to up to N nearest within R of last target, apply falloff per jump. All resolved in one tick. Render polyline for ~0.15s.
- Spotter reveal (MP-K): every tick a Spotter is in range of a Carrier, set `carrier.isRevealed = true`. The flag is global — all towers' `findTarget` checks `isRevealed` before proximity. Plague-spread also benefits (revealed Carriers stay flagged through Phase intangibility, since reveal is set per-tick by any nearby Spotter independent of intangibility state).

**Two small framework-side additions** to `STATUS_KINDS`:

- `poison.onTargetDeath(deadAnt)` — fires when a poisoned ant dies, applies poison to ants within 30 px. Used by Plague (3.31). Toxin (3.3) does not set this hook (no spread).
- `freeze.damageAmp` (number, default 1) — multiplier read by `applyDamage` against frozen targets. Cryo (3.22) sets `damageAmp: 1.5` on the freeze status it emits, so any damage landing on a Cryo-frozen target deals +50%. The freeze-and-damage interaction is implemented as `applyDamage` reading `target.effects.freeze?.damageAmp ?? 1` before applying mitigation.

### 3.2 Pierce flag split

Plan 2's `pierce` flag conflated line-pierce and armor-pierce. Plan 3 splits into:

- `linePierce: N` (number) — how many ants the projectile passes through
- `armorPierce: bool` — whether the projectile bypasses armored mitigation

Migration: Railgun's existing `pierce: true` becomes `{ linePierce: 999, armorPierce: true }` (effectively unchanged). Demolisher uses `{ armorPierce: true }` only — single-target, full damage vs Tank/Soldier.

### 3.3 `spawnAnt` source plumbing + `ant.spawnOrigin`

Already detailed in §2.5C and §2.5D. The change is additive — Plan 2 callers continue to work because `source` defaults to `'wave'`.

### 3.4 Selected-tower panel — variable upgrade button count

Plan 2's panel logic assumes one upgrade button. Plan 3 generalizes to render 0, 2, or 3 buttons based on the cannon's tier in the upgrade tree. The button-rect computation, hover detection, and click handling all extend uniformly — same primitive (a button rect) repeated.

### 3.5 File size projection

Plan 2 ended at 2368 lines in `patterns/demo-ant-buster.php`. Plan 3 adds:

- ~21 cannon kind data entries: ~150 lines of CANNON_KINDS rows
- ~11 mechanic primitives: ~250–400 lines (most are 20–40 lines each; Tesla arc-jump and homing are ~50)
- ~20 wave config rows: ~80 lines
- King ant config + onSpawn / onDeath / onHpThresholds hooks: ~80 lines
- `spawnAnt` source plumbing + `ant.spawnOrigin` field: ~30 lines
- Pierce flag split refactor: ~20 lines (mostly mechanical edits to existing applyDamage)
- Selected-tower panel multi-button render: ~50 lines

Estimated end-of-Plan-3 file size: **~3000–3300 lines**. Approaching the threshold where extracting the cannon table to a separate `data/` PHP file would be reasonable, but Plan 3 keeps everything in the single file to honor the "single-file demo" convention. Plan 4 can revisit the split.

### 3.6 Render z-order

Plan 2 established 13 render layers (per its task 25 refactor). Plan 3 adds three new visual primitives that slot into existing layers — no new layer slots needed:

- **Tesla polyline lightning bolt** — sits between the projectiles layer and the ants layer so the bolt visually connects targets without obscuring them. ~0.15s lifetime.
- **Plasma Torch cone area** — sits in the same layer as other persistent effect overlays (range circles, freeze auras). Translucent triangle from tower toward facing direction. Rendered every tick while firing.
- **King's persistent HP bar** — sits in the same layer as the existing damage-triggered HP bars but always renders, even at full HP. Other ants' bars stay damage-triggered.

The exact layer numbers are an implementation detail to verify against the Plan 2 task-25 refactor; the contract here is the relative ordering of the three additions.

---

## 4. Out of scope (deferred to Plan 4 and beyond)

### 4.1 Plan 4 — Game shell + audio + god tier

**Game shell:**

- Title screen (Start Campaign / Endless Mode / How to Play, with high-score badges)
- Save state via localStorage (key `dariobimbi_antbuster_v1`: campaign-cleared flag, fastest clear time, endless high-water mark, tutorial-seen flag, sound on/off, music on/off)
- Tutorial popover (first-run "drag a tower from the right" + "click a tower to upgrade" panels)
- Victory overlay per parent spec §7.6 (dimmed canvas, run stats, Continue-into-endless / Return-to-title buttons)
- Game-over overlay enhancements (run stats, Retry / Return-to-title)

**Audio:**

- SFX library + per-event triggers (cannon fire by family, ant death, slice drop, slice pickup, slice delivered, wave start / end, boss intro, status applications, threshold-summon, drip spawn)
- Optional music
- Sound on/off persistence

**God tier (18 cannons + unlock mechanic):**

- 18 god-tier cannon configs (`*.111`, `*.121`, … `*.321`)
- Wave-15 god-tier-unlock mechanic (greyed-out upgrade button on T2 finals with "Unlocks at wave 15" tooltip until wave 15 is cleared)
- God-tier-specific visual treatment (bigger muzzle flash, screen shake on Earthshaker / Annihilator, continuous aura ring on Blizzard)

**Endless mode (wave 26+):**

- Procedural wave generation with weighted random ant pool
- +5% HP / +3% speed scaling per wave above 25
- Endless high-score persistence (saved on game-over)

**Visualization polish:**

- Bomber AoE-disable cue (greyscale + sparks + countdown ring on disabled cannons)
- Phase ant intangibility cue (alpha pulse + outline shimmer)
- Kill count and DPS readout in selected-tower panel
- Slice-timeout simDt scaling (currently 8 wall-clock seconds regardless of game speed)

### 4.2 Plan 5 — Real PNG sprite art

Replace procedural placeholder sprites with hand-drawn PNG art for ants, cannons, projectiles, effects, and UI elements. Out of scope for Plan 3.

### 4.3 Tuning deferred to post-Plan-3 playtest

- King drip rate (2.5s starting point)
- King threshold-Soldier count (4 starting point)
- Per-wave prep-time exceptions (e.g., wave 23 "everything wave" breather)
- Cannon stat balancing if specific upgrade paths feel under- or overpowered after a full 25-wave playthrough

---

## 5. Project conventions to enforce

Carry forward from Plan 2:

- **`var` over `let`/`const`** in inline scripts (CLAUDE.md inline-script gotcha — avoid hoisting traps).
- **No `&&` or `||` in inline JS** — use the verbose `if` / `var x = a; if (!x) { x = b; }` form. WP's content filter HTML-encodes them inconsistently to `&#038;&#038;` / `&#124;&#124;`. Curl-grep the served HTML after each commit that touches inline JS.
- **Function declarations, not function expressions** for shared helpers in inline scripts (declarations are fully hoisted; `var foo = function () {}` is not).
- **CANNON_KINDS / ANT_KINDS / STATUS_KINDS / WAVES tables stay at the top of the IIFE** so all data tables are in one scannable region.
- **Bump the theme `Version:` in `style.css`** at the end of Plan 3 so the pattern cache invalidates and the new demo loads on next admin pageview.
- **Expose Plan 2's `window.__ab*` debug surfaces** (and add `window.__abSpawn = spawnAnt` per Plan 2 status workflow notes) for Playwright introspection.
- **Voice rule does not apply** — design docs and PHP/JS comments are not user-facing copy. Em-dashes, technical jargon, and CV-style phrasing are fine inside the spec and inside admin-only code comments. The rule applies to `.php` template content that visitors actually read.

---

## 6. Acceptance criteria (Plan 3 done = all of these)

A QA walk against `http://localhost/dariobimbi.com/demos/ant-buster/?debug=1` should verify each:

**Cannon system (§2.1, §2.2):**

1. All 21 new cannons buildable through upgrade chains. Selected-tower panel renders 3 upgrade buttons on bases (Howitzer / Repeater / Twin Gun), 2 on mids, 0 on finals.
2. All 11 new mechanic primitives functional in playtesting: splash, arc, homing, return, multi-pellet cone, hose, beam-direct, cone-DoT, bonus-damage modifier, Tesla arc-jump, Spotter reveal.
3. `linePierce` and `armorPierce` flags are separate. Demolisher (`armorPierce` only) deals full damage to Tank but does not pass through ants. Railgun keeps both.
4. Range-circle preview reflects upgrade target's range, including Plasma Torch shrink (130 → 70 visible on hover).
5. Spotter's `isRevealed` flag is global — all towers' `findTarget` biases toward revealed Carriers regardless of which tower revealed them.
6. Plague's poison status spreads to ants within 30 px on a poisoned target's death.
7. Cryo freeze fully stops ants for 0.8s; ants take +50% damage during the freeze.
8. Sonic Pulse displaces ants 30 px and applies 0.4s stun.
9. Tesla resolves all chain hits in one tick; visual lightning polyline persists ~0.15s before fading.

**Wave system (§2.3):**

10. Waves 6–25 run end-to-end with the spec compositions in §2.3.
11. Prep time = 20s for non-boss waves, 30s for boss waves (5 / 10 / 15 / 20 / 25). Wave 1 keeps 30s.
12. Wave-completion bonus = `20 + waveNum`. Wave 5 pays 25g, wave 25 pays 45g. Plan 2's prior value is replaced.
13. Boss banners: `WAVE 10: QUEEN`, `WAVE 15: QUEEN`, `WAVE 20: QUEEN`, `FINAL WAVE: THE KING`.

**King ant + wave 25 (§2.4):**

14. King: HP 3000, speed 10 px/s, gold 200, can't carry slices, `barStyle: { width: 60, height: 4, color: '#d04020', persistent: true }` renders from spawn.
15. Threshold-summons fire exactly once each when King's HP/maxHp crosses below 75%, 50%, 25%. 4 Soldiers per threshold. Soldiers spawn at King's current position with `source: 'threshold'`.
16. Drip Workers spawn from `(10, 320)` every 2.5s while King alive, with `source: 'drip'`. Drip stops on King death.
17. Drip Workers flee to `(10, 320)` when carrying a slice (via `ant.spawnOrigin`), not to main anthill.
18. Wave 25 advances to `'won'` state when King + all drip Workers + all threshold Soldiers are cleared. 45g bonus paid.
19. The King fight cannot be leaked by killing the King while drip Workers are en route, and cannot be ended early by clearing the threshold Soldiers while the King still walks.
20. **Spawn-source telemetry verifiable at runtime.** Every ant in `game.ants` carries a `spawnSource` field set at spawn time. During a wave 25 playthrough, `game.ants.filter(a => a.spawnSource === 'drip')` returns only drip Workers from `(10, 320)`, and `=== 'threshold'` returns only the King's threshold-Soldiers. Inspectable via `window.__ab` for Playwright / devtools introspection. This contract is what Plan 4 audio hooks read; verifying it now prevents plumbing surprises later.

**Carryover (§2.5):**

21. Carrier flee-speed = 45 px/s (`carryingSpeedMult = 1.5`). Worker-turned-carrier ants also use 1.5×.
22. `?debug=1 T` cycle includes `mini_splitter` and `king` (11 kinds total).
23. `spawnAnt(kind, x, y, source)` accepts and stores 5 source values; default `'wave'` preserves Plan 2 behavior.
24. `ant.spawnOrigin` is set on spawn and read by flee-target logic.

**Hygiene:**

25. No `&#038;&#038;` or `&#124;&#124;` in the served HTML (verify with `curl -s … | grep -F`).
26. Theme `Version:` in `wp-content/themes/dariobimbi/style.css` bumped, pattern cache invalidates.
27. No console errors during a wave 1–25 playthrough at `?debug=1`.

---

## 7. Decision log

Captured during the brainstorming session, in the order they were locked.

- **Plan 3 = content-first.** Content fill (waves + cannons + King) over shell-first or mixed-slice approaches. Plan 4 owns shell, audio, polish, and god tier.
- **Cannon roster scope = tree-complete, no god.** 21 missing non-god cannons fill out the 3-mid × 2-final structure for all three families. The 18 god-tier cannons and the wave-15-god-unlock mechanic are Plan 4. Wave 15 in Plan 3 is just a hard fight.
- **Carrier flee-speed = 45 px/s (matches parent spec §4.1).** Plan 2's 0.7× slow-down replaced. Counter-cannons (Spotter, Phantom, Sonic Pulse, Cryo) all in Plan 3 scope.
- **Polish items defer to Plan 4** — bomber AoE-disable cue, phase intangibility cue, kill count / DPS readout, slice-timeout simDt scaling. Exception: the trivial `mini_splitter` (and `king`) addition to the `?debug=1 T` cycle ships in Plan 3.
- **Wave 15 ships no god-tier UI.** No greyed-out unlock button, no "Unlocks at wave 15" tooltip. Plan 4 ships the unlock mechanic together with the 18 god-tier cannons.
- **Endless mode (wave 26+) is Plan 4** — depends on save-state for high-score persistence, which Plan 4 owns.
- **Tesla arc-jump = instant-resolve with polyline render.** All 3 hits land in one tick; visual juice from the simultaneous flash, not from a moving particle.
- **Spotter reveal is a global ant flag**, not local targeting bias. All towers' `findTarget` reads `ant.isRevealed` before proximity. Reveal persists through Phase intangibility because it's set per-tick by any nearby Spotter.
- **Returning projectiles (Discus / Ricochet) carry `originX, originY`** at fire time. Return phase steers to those coords. Mid-flight target loss does not break the return arc.
- **Pierce flag split into `linePierce` and `armorPierce`.** Plan 2's coupled flag becomes two orthogonal flags, forward-compatible with Plan 4 god-tier (Particle Cannon needs both).
- **Wave-completion bonus = `20 + waveNum`** (parent spec formula). Plan 2's higher development-cushion value is replaced. Tightens late-game economy to make 21-cannon choices meaningful.
- **Boss banner copy** = `WAVE [N]: QUEEN` for 10/15/20, `FINAL WAVE: THE KING` for 25.
- **King secondary spawn at `(10, 320)`** — bottom-left corner, mirrors main anthill's left-edge position vertically. Drip rate 2.5s while King alive.
- **King threshold-summons spawn at King's position**, not at the anthill. Soldiers emerge from the King.
- **`spawnAnt` source parameter** added now, even though Plan 3 uses only two of the five source values for behavior change. Plan 4 audio hooks read `source` without re-plumbing.
- **`ant.spawnOrigin` per-instance field** generalizes flee-target from "always main anthill" to "wherever you came from." Forward-compatible with future multi-spawn waves.
- **Win/lose state UI unchanged from Plan 2.** Plan 3 reaches the same `'won'` and game-over states; Plan 4 owns the spec §7.6 overlays.
- **King HP bar persistent from spawn** (60×4, dark red, white border). Reads as "different threat tier" before first damage. Other ants stay on damage-triggered bars.
- **Estimated Plan 3 size = 30–35 implementation tasks.** Slightly bigger than Plan 2's 27. The cannon roster triples and each new mechanic primitive gets its own task; wave configs and ant kind are mostly data.

---

## 8. Implementation tasks (rough sketch — to be expanded by writing-plans)

The full task breakdown lives in the implementation plan. This section sketches the rough buckets so the plan can be drafted with a clear shape.

**Bucket A — Architecture refactors (do first, unblocks everything else):**

1. Pierce flag split (`linePierce` + `armorPierce`) in CANNON_KINDS + applyDamage.
2. `spawnAnt` source parameter + `ant.spawnOrigin` field.
3. Selected-tower panel: variable upgrade button count (0 / 2 / 3) with range-preview honesty.
4. Bonus-damage modifier hook on CANNON_KINDS.

**Bucket B — Mechanic primitives (each its own task, mostly independent):**

5. Splash AoE on impact.
6. Arcing ballistic projectile.
7. Homing projectile.
8. Returning projectile (out + return) for Discus.
9. Returning + line-pierce on return for Ricochet.
10. Multi-pellet shotgun cone for Buckshot.
11. Single-barrel hose with aim jitter for Minigun.
12. Continuous beam direct-damage variant (Lance).
13. Cone DoT area (Plasma Torch — burn status emit).
14. Tesla arc-jump with polyline render.
15. Spotter reveal (global `ant.isRevealed` flag) + targeting bias.
16. Plague poison-spread-on-death (extension to STATUS_KINDS.poison).

**Bucket C — Cannon configs (one per cannon, mostly data + light wiring):**

17–37. The 21 CANNON_KINDS rows (Heavy ×7, Quick ×7, Double ×7).

**Bucket D — Wave content:**

38. Add `prepTime` field to wave configs; default 20s, boss waves 30s.
39. Append waves 6–25 to WAVES with spec compositions.
40. Wave-completion bonus formula update (`20 + waveNum`).
41. Boss banner copy update (`WAVE [N]: QUEEN` / `FINAL WAVE: THE KING`).

**Bucket E — King + wave 25:**

42. King ANT_KINDS row with `barStyle`, `onHpThresholds`, `onSpawn`, `onDeath`.
43. `barStyle` field on ANT_KINDS — default + persistent variants — and Queen escalation styles.
44. Threshold-summon hook (`onHpThresholds` checked in applyDamage).
45. King continuous worker drip from `(10, 320)`.
46. Drip-source flee logic (uses `ant.spawnOrigin`).
47. Wave 25 spawn config + `FINAL WAVE: THE KING` banner.
48. King smoke test against §6 acceptance items 14–19.

**Bucket F — Carryover + final smoke test:**

49. Carrier flee-speed = 45 px/s.
50. `mini_splitter` and `king` in `?debug=1 T` cycle.
51. Theme `Version:` bump in `style.css`.
52. Final QA walk against §6 acceptance criteria + curl-grep hygiene check + write Plan 3 status doc.

This is **~52 tasks** as drafted, more than the 30–35 estimate from §7's decision log. The writing-plans pass should consolidate adjacent cannon-config tasks (e.g. all 7 Heavy cannons that share the Mortar mechanic primitive can land in one or two commits). Realistic landed task count: **30–40**.

---

## 9. Addendum (2026-05-07): Pink Rubber Family expansion

Added mid-marathon during Plan 3 execution (before Task 16). Folds a new **fourth cannon family** into Plan 3's content fill rather than deferring to a follow-up plan. The family is a single linear upgrade chain (no mid-tier branch), introduces one new mechanic primitive (MP-L: bouncing retarget), and reuses the existing knockback status from Plan 2 for its final-tier finisher.

The motivating fiction: a daughter-inspired addition. The mechanical fit: a "generalist" base that contrasts the existing Howitzer (single-target heavy), Repeater (single-target sustained), and Twin Gun (multi-target light) by giving the player a rubber ball that hops between adjacent ants. Doubles as a soft counter to the King's wave-25 worker drip without overlapping with Sonic Pulse / Cryo / Spotter design space.

### 9.1 New mechanic primitive — MP-L (bouncing retarget)

| Field | Value |
|---|---|
| ID | MP-L |
| Name | Bouncing retarget |
| Used by | Pink Pea, Bouncy Bubble, Rubber Wrecking Ball |
| Logic | On impact, search `game.ants` for the nearest ant within `bounceRadius` excluding ants the projectile has already hit. If found: redirect velocity vector toward that ant, decrement `bouncesRemaining`, multiply `damage` by `damageDecay`, do not despawn. If not found or `bouncesRemaining === 0`: despawn as normal. |
| Decay | `projectile.damage = projectile.damage * damageDecay` per bounce. Default 0.8 across all three Rubber cannons. |
| Visual | `visualScale` field on CANNON_KINDS scales the projectile sprite at render time (default 1.0). Pink Pea = 1.0, Bouncy Bubble = 1.5, Wrecking Ball = 2.5. |
| First-hit-only effects | `statusOnFirstHit: { kind, magnitude, duration }` field on CANNON_KINDS applies a status only to the projectile's *initial* target. Bounce targets take damage but no status. Used by Wrecking Ball for "heavy knockback only on the first hit," preventing pinball chaos. |

**Distinct from MP-D (Returning).** MP-D's projectile carries `originX, originY` and steers back to the tower. MP-L seeks the nearest non-hit ant. Same lifecycle hook (`onImpact`), different branch — both can share the existing `_sawAnts` array (introduced in Task 1's pierce flag split) for "don't double-hit the same ant."

**Append to §2.1's primitives table** as the 12th row.

### 9.2 Cannon configs — the Pink Rubber Family

New base cannon added to the build palette, reachable from wave 1 (no gating).

| ID | Name | Tier | Damage | Fire rate | Range | Special | Cost |
|---|---|---|---|---|---|---|---|
| 4.1 | Pink Pea | base | 5 | 0.8/s | 130 | bounces 1, radius 50 px, decay 0.8 | 50g (place from build palette) |
| 4.2 | Bouncy Bubble | mid | 12 | 0.6/s | 160 | bounces 2, radius 60 px, decay 0.8, projectile 1.5× scale | +80g upgrade |
| 4.3 | Rubber Wrecking Ball | final | 30 | 0.4/s | 180 | bounces 4, radius 70 px, decay 0.8, projectile 2.5× scale, **first-hit-only knockback 30 px** | +120g upgrade |

Branch shape after Plan 3 expansion: **Pink Pea → Bouncy Bubble → Rubber Wrecking Ball.** Linear chain. No fork at the mid tier.

**Build palette** extends from `['howitzer', 'repeater', 'twingun']` to `['howitzer', 'repeater', 'twingun', 'pink_pea']`. A 4th vertical card slot rendering the Pink Pea sprite + cost + family hint. Visual budget check: `SHOP_W` accommodates 4 × 70 px cards (280 px) + spacing + the wave panel beneath, with the existing 8 px gap between cards.

**Color palette.** Hex range `#ff69b4` (Pink Pea) → `#ff1493` (Wrecking Ball), applied to both the turret sprite barrel and the projectile fill. Procedural placeholder art consistent with the rest of Plan 3's sprite quality (real PNG art is Plan 5 scope).

**Knockback semantics on Wrecking Ball.** Reuses Plan 2's existing `knockback` status. Magnitude 30 px (no stun, distinct from Sonic Pulse which combines knockback + 0.4s stun). Applied via `statusOnFirstHit` only — bounce targets don't get knocked back. This keeps the rubber ball feeling "weighty on impact, decaying as it bounces" rather than "ant pinball machine."

### 9.3 Selected-tower panel — 1-button case

Plan 3 §2.2 already specified the panel renders 0/2/3 upgrade buttons (final / mid / base). The Pink Rubber Family adds the **1-button case** for Pink Pea (linear → Bouncy Bubble) and Bouncy Bubble (linear → Wrecking Ball). The existing generic loop in Task 4 (`for (var i = 0; i < upgrades.length; i++)`) already handles arbitrary lengths — no logic change needed, only verification that 1-button renders correctly with the existing layout (sell button stays at fixed `py + 174`, single upgrade button sits at `py + 90` with a clean gap below).

The panel-button-count table in §2.2 extends to:

| Cannon tier | Upgrade buttons rendered |
|---|---|
| Base (Howitzer / Repeater / Twin Gun) | 3 |
| Base (Pink Pea) | **1 (linear chain)** |
| Mid (e.g. Mortar) | 2 |
| Mid (e.g. Bouncy Bubble) | **1 (linear chain)** |
| Final (e.g. Hellfire / Wrecking Ball) | 0 |

### 9.4 Acceptance criteria additions

Append to §6:

28. Pink Pea is buildable from the build palette starting wave 1 at 50g cost. The shop column shows 4 cards (Howitzer / Repeater / Twin Gun / Pink Pea).
29. Pink Pea projectile bounces exactly once on impact, redirecting to the nearest non-original ant within 50 px. Decayed damage = `5 × 0.8 = 4` on the second hit.
30. Bouncy Bubble bounces up to 2 times (radius 60 px). Projectile sprite renders at 1.5× scale.
31. Rubber Wrecking Ball bounces up to 4 times (radius 70 px). Projectile sprite renders at 2.5× scale. First impact applies 30 px knockback; subsequent bounces deal damage but no knockback.
32. Selected-tower panel shows 1 upgrade button for Pink Pea (→ Bouncy Bubble) and Bouncy Bubble (→ Wrecking Ball). 0 buttons for Wrecking Ball.

### 9.5 Decision log additions

Append to §7:

- **Pink Rubber Family added mid-marathon as Bucket G (Tasks 37–40).** 4th cannon family, linear chain, ships in Plan 3 rather than deferring to a follow-up. Available from wave 1, competes with the 3 existing base cannons in the build palette.
- **MP-L (bouncing retarget) is a new primitive distinct from MP-D (returning).** Reuses `_sawAnts` from Task 1's pierce split for "don't double-hit." Decay multiplier 0.8 uniform across all three Rubber cannons.
- **First-hit-only knockback on Wrecking Ball.** New `statusOnFirstHit` field on CANNON_KINDS. Bounce targets take damage but no status. Prevents pinball chaos.
- **`visualScale` field on CANNON_KINDS** scales projectile sprite at render. Pink Pea 1.0, Bouncy Bubble 1.5, Wrecking Ball 2.5. Default 1.0 for all existing cannons (no migration needed).
- **No gating on Pink Pea.** Available from wave 1. Functions as a "generalist" 4th base cannon with multi-target potential via the bounce.

### 9.6 Implementation tasks (Bucket G)

Appended to the plan as Tasks 37–40:

- **Task 37 — MP-L bouncing primitive:** Add `bouncesRemaining`, `bounceRadius`, `damageDecay`, `initialHit`, `statusOnFirstHit` to projectile literal in `fireProjectile`. Add bounce-retarget branch in `updateProjectiles`. Gate `statusOnHit` application on `p.initialHit`.
- **Task 38 — Build palette + linear-chain panel verification:** Extend the shop's `keys` array from 3 to 4 to include `pink_pea`. Verify the selected-tower panel's existing generic upgrade-button loop renders the 1-button case correctly.
- **Task 39 — The Rubber Roster:** Add `pink_pea`, `bouncy_bubble`, `rubber_wrecking_ball` to CANNON_KINDS with stats per §9.2. Wire the linear chain via `upgradesTo`.
- **Task 40 — Visuals & polish:** Add `visualScale` to projectile rendering. Procedural pink turret + ball sprites for the three Rubber cannons. Final QA against §9.4 acceptance items.

**Plan 3 final task count after Bucket G: 40 tasks.**
