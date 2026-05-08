# Ant Buster — Plan 4 Design Spec

**Status:** Design complete. Pending user review before transitioning to the implementation plan.
**Date opened:** 2026-05-08
**Author:** Dario, with Claude assist
**Parent spec:** [`2026-05-06-ant-buster-design.md`](./2026-05-06-ant-buster-design.md)
**Plan 3 spec:** [`2026-05-07-ant-buster-plan-3-design.md`](./2026-05-07-ant-buster-plan-3-design.md)
**Plan 3 status:** [`../plans/2026-05-07-ant-buster-plan-3-status.md`](../plans/2026-05-07-ant-buster-plan-3-status.md)

---

## 1. Goal of Plan 4

**Plan 4 = dramatic apex.** Take Plan 3's content-fill backbone (21 non-god cannons, 25 waves, the King boss) and pour the apex pass on top: 18 god-tier T3 cannons across all three primary families, the wave-15 unlock gate that gives them dramatic significance, and the deferred Pink Rubber Family from Plan 3 §9. Add the visual juice that makes a T3 commitment feel like a real moment (gold halo, bigger muzzle flash, screen shake on the heavy ground-pounders). Close out Plan 3's hygiene carryover so Plan 4 ships clean.

The split was chosen against bundling shell + content + audio + polish into a single sprawling Plan 4. Plan 5 owns the bookends (title screen, save state, tutorial, win/lose overlays, sound, endless mode, real PNG sprite art). Plan 4 stays focused on "make the run feel like a real apex commitment," with the Option D phase-shield/enrage on the King held in reserve as a documented escape valve if the post-implementation playtest reveals the King fight is too easy with god-tier T3 cannons available.

After Plan 4 ships, the demo plays as a 25-wave campaign with 21 non-god cannons, 18 god-tier apex cannons gated behind a wave-15 unlock, and the Pink Rubber Family as a 4th support family. The visual treatment of *arriving* at the win state is unchanged from Plan 3. Plan 5 owns the celebration overlay.

---

## 2. What ships in Plan 4

### 2.1 Cannon system additions

#### Four new mechanic primitives

| ID | Primitive | Used by |
|---|---|---|
| MP-L | Bouncing retarget (on impact, search nearest non-hit ant within `bounceRadius`, redirect velocity, decrement `bouncesRemaining`, multiply damage by `damageDecay`; reuses Plan 3's `_sawAnts` array) | Pink Pea, Bouncy Bubble, Rubber Wrecking Ball |
| MP-M | Mid-flight projectile split (at `splitFraction` of original travel distance, parent missile despawns and spawns 3 child missiles, each with the standard MP-C homing primitive) | Cluster Strike |
| MP-N | Persistent radial aura (cannon registers as an aura source with no fire-rate cooldown and no target acquisition; per-tick AoE damage and slow re-application within `auraRadius`, plus uniform-random freeze pick every 5s) | Blizzard |
| MP-O | Beam line-pierce (extends Plan 3's MP-G beam-direct: every tick, beam damages up to N ants whose centers fall within a thin cylinder along the tower→target line) | Solar Lance |

MP-L was specced in Plan 3 §9 but deferred from implementation. Plan 4 lands it as part of Bucket B alongside the Pink Rubber cannons.

#### Two framework hooks added to STATUS_KINDS

- **`burn.onExpire(deadAnt)`** — fires when a burn duration runs out (not when the target dies). Parallel of Plan 3's `poison.onTargetDeath`. Phoenix stamps `spreads: true` on its burn applications; the hook propagates burn to ants within 30px on expiry. Toxin / Plasma Torch leave it unset, so their burns expire silently.
- **`poison.damageAmp` (number, default 1).** Read by `applyDamage`, multiplies incoming damage on poisoned targets. Same shape as Plan 3's `freeze.damageAmp`. Biohazard sets `damageAmp: 1.25` on its poison applications. Source-independent: any cannon firing on a Biohazard-infected target deals +25%.

#### One splash extension

- **`splashStatus: true` on CANNON_KINDS.** When set, the existing splash AoE function applies the cannon's `statusOnHit` array to every ant caught in radius, not just the direct-hit ant. Backward compatible (existing splash cannons leave it unset). Used by Earthshaker (stun ring), Tempest (knockback + stun ring), Absolute Zero (freeze ring on impact).

#### CANNON_KINDS field additions

- `tier` (string, enum `'base' | 'mid' | 'final' | 'god'`). Existing 30 rows backfilled with their natural tier. New 18 god-tier rows set `'god'`. The unlock-gate logic and visual treatment branch on this field.
- `flashScale` (number, default 1.0). God-tier rows set 1.7. Read by `drawMuzzleFlash` to scale the radial-gradient flash arc.
- `splashStatus` (boolean, default false). See above.
- `visualScale` (number, default 1.0). Already in Bucket G §9 design. Field added in Plan 4. Pink Pea 1.0, Bouncy Bubble 1.5, Wrecking Ball 2.5.
- `statusOnFirstHit` (object, optional). Already in Bucket G §9 design. Field added in Plan 4. Wrecking Ball uses it for first-hit-only knockback.
- `screenShakeMag` (number, default 0). Earthshaker 8, Annihilator 5, Shockwave 4 (already in Plan 3).

#### Eighteen new CANNON_KINDS rows (the god tier)

All numbers locked in parent design doc §3.1–3.3. Implementation notes added per cannon.

**Heavy family — Ordnance — 6 god-tier**

| ID | Name | Damage | Fire | Range | Implementation | Cost |
|---|---|---|---|---|---|---|
| 1.111 | Particle Cannon | 60 | 0.6/s | 140 | `linePierce: 999, armorPierce: true, falloff: 0`. Extends Railgun. | +250 |
| 1.121 | Annihilator | 120 + 30 splash | 0.3/s | 130 | `armorPierce: true, splash: { radius: 60, damage: 30 }, screenShakeMag: 5`. Extends Demolisher. | +280 |
| 1.211 | Saturation | 25 + 20 splash | 0.5/s | 200 | `barrels: 3` (arcing), `splash: { radius: 80, damage: 20 }`. Extends Hellfire / Triple Threat hybrid. | +260 |
| 1.221 | Cluster Strike | 30 + 12 splash | 0.5/s | 220 | `homing: true, splitFraction: 0.5, splitCount: 3, splash: { radius: 30, damage: 12 }`. Uses MP-M. | +280 |
| 1.311 | Vortex | 12 | 0.4/s | 200 | `barrels: 2, returning: true, linePierce: 2`. Extends Ricochet. Hits 4× per disc (out + 2 pierces + return). | +250 |
| 1.321 | Earthshaker | 80 + 50 splash | 0.12/s | 140 | `splash: { radius: 120, damage: 50 }, splashStatus: [stun 0.6s], screenShakeMag: 8`. | +300 |

**Quick family — Precision — 6 god-tier**

| ID | Name | Damage | Fire | Range | Implementation | Cost |
|---|---|---|---|---|---|---|
| 2.111 | Hailstorm | 4 | 14/s | 160 | `aimJitter: 0`. Extends Vulcan. | +250 |
| 2.121 | Phoenix | 2/tick | 12/s | 80 (cone 70°) | Cone DoT, burn `+3/s for 3s, spreads: true` (uses new `burn.onExpire`). | +260 |
| 2.211 | Reaper | 60 | 0.5/s | 380 | `damageModifier: (t) => (t.kind === 'worker' \|\| t.kind === 'scout') ? 999 : (t.hp === t.maxHp ? 2 : 1)`. | +280 |
| 2.221 | Solar Lance | 8/tick | continuous | 280 | `beam: true, beamPierce: 2`. Uses MP-O. | +290 |
| 2.311 | Hawkeye | 80 | 0.3/s | 600 | `minRange: 0, damageModifier: (t) => t.kind === 'queen' ? 3 : 1`. Drops parent spec's "shoot through obstacles" clause (no obstacles exist). | +300 |
| 2.321 | Stormcaller | 18 | 1/s | 240 | `teslaArc: { jumps: 6, range: 80, falloff: 1 }`. Extends Tesla. | +280 |

**Double family — Swarm Control — 6 god-tier**

| ID | Name | Damage | Fire | Range | Implementation | Cost |
|---|---|---|---|---|---|---|
| 3.111 | Scattergun | 3 (×8 pellets) | 0.7/s | 100 | `pellets: 8, spread: 70°, linePierce: 1` (per pellet). Extends Buckshot. | +260 |
| 3.121 | Phalanx | 2 | 18/s | 140 | `barrels: 3, aimJitter: 0`. Extends Minigun. Tracking lead skipped per Section 4 brainstorm decision. | +280 |
| 3.211 | Blizzard | 1/tick AoE | continuous radial | 150 | MP-N: `aura: { radius: 150, damagePerTick: 1, slow: 0.5, slowExpiry: 0.15, freezeRandomEvery: 5, freezeDuration: 2 }`. | +260 |
| 3.221 | Absolute Zero | 6 | 1/s | 130 | `freezeDuration: 1.5, splashStatus: [freeze 0.5s], splashRadius: 40`. Extends Cryo. | +280 |
| 3.311 | Biohazard | 4 | 1/s | 130 | `poison: { dmg: 30, duration: 8, spreads: true, spreadRadius: 50, damageAmp: 1.25 }`. | +260 |
| 3.321 | Tempest | 10 | 0.7/s | 110 | `splashRadius: 50, splashStatus: [knockback 50, stun 0.8s]`. | +260 |

**Wiring notes:**
- Each god-tier row sets `tier: 'god'`.
- Each T2 final's `upgradesTo` field gains its god-tier ID. Single-element array per Plan 3's selected-tower-panel pattern (the 1-button case from Bucket G now also covers all 18 T2 finals).
- Build palette stays at 4 cards. God tier never appears in the build palette, only as upgrade options on T2 finals.

### 2.2 Wave-15 god-tier unlock mechanic

**State.** Two new fields on `game`:
- `godTierUnlocked: false` — boolean gate. Read by selected-tower-panel rendering and click handlers.
- `godTierUnlockPulseStart: 0` — number, the `game.time` at which unlock fired. Used for the 0.5s halo flash decay.

Both reset to default on game-over → restart, alongside the existing reset of `gold`, `cake`, `waveIndex`, etc.

**Trigger.** Inside `updateWaveState`, in the same branch that pays the wave-completion bonus:

```js
if (wv.number === 15 && transitionedToClear) {
  game.godTierUnlocked = true;
  game.godTierUnlockPulseStart = game.time;
}
```

Fires once when wave 15's `alive === 0` flips true, the same instant as the 35g bonus payout.

**Selected-tower-panel rendering.** When a T2 final cannon is selected (its `tier === 'final'` and `upgradesTo[0]` exists):
- **Pre-unlock state** (`!game.godTierUnlocked`): button at 50% alpha, label `"<Name> +<cost>g"`, hover shows tooltip `"Unlocks at wave 15"` anchored above. Click is a no-op.
- **Post-unlock state**: button at 100% alpha, click commits the upgrade in place, preserving position and current target via Plan 2's SYS-2 in-place upgrade.
- **Unlock pulse window** (`game.time - godTierUnlockPulseStart < 0.5`): an additive golden halo behind the button at alpha `1 - (timeSince / 0.5)`, decaying linearly to 0. The pulse plays even if the player has no T2 final selected at the moment of unlock (they see the un-greyed button when they next select one, no pulse).

**Click-handler gate.** The selected-tower panel's `upgradeTower(udef)` gains a guard at the top:

```js
if (udef.tier === 'god' && !game.godTierUnlocked) return;
```

Defense in depth alongside the visual greying.

**Debug hotkeys.**
- `?debug=1 G` toggles `game.godTierUnlocked`. Pressing G while toggling true also stamps `godTierUnlockPulseStart = game.time` so the visual pulse fires for testing.
- `?debug=1 W` clears `game.ants`, sets `waveIndex = 15` (i.e., next wave to start = wave 16), `phase = 'prep'`, `prepTimer = 20`, `godTierUnlocked = true`. One-shot, repeatable. Intended for the post-implementation Option C playtest.

Both hotkeys gated by the `?debug=1` URL query string. The keydown handler doesn't attach without `debug=1`, so neither key has any effect in production.

**What this is NOT.** No localStorage, no save state, no persistent unlock across runs, no tutorial popover, no god-tier-specific overlay banner. The unlock is conveyed by the un-greying + the 0.5s halo pulse. Quiet by design.

### 2.3 Bucket G — Pink Rubber Family (carryover from Plan 3 §9)

Three new CANNON_KINDS (data + light wiring on top of MP-L), one new mechanic primitive (MP-L), two new CANNON_KINDS fields (`visualScale`, `statusOnFirstHit`), build-palette extension from 3 to 4 cards.

| ID | Name | Damage | Fire | Range | Mechanism | Cost |
|---|---|---|---|---|---|---|
| 4.1 | Pink Pea | 5 | 0.8/s | 130 | bounces 1, radius 50, decay 0.8 | 50g (build) |
| 4.2 | Bouncy Bubble | 12 | 0.6/s | 160 | bounces 2, radius 60, decay 0.8, projectile 1.5× scale | +80g |
| 4.3 | Rubber Wrecking Ball | 30 | 0.4/s | 180 | bounces 4, radius 70, decay 0.8, projectile 2.5× scale, first-hit 30px knockback | +120g |

Linear chain. No fork at the mid tier. No T3 god-tier (preserves the family's "casual / approachable" identity; absence of god slot is intentional).

Procedural placeholder art per Plan 3 §9: pink turret + ball sprites in hex range `#ff69b4` → `#ff1493`. Real PNG art is Plan 5.

The selected-tower panel's 1-button upgrade case (Pink Pea / Bouncy Bubble) reuses the existing generic upgrade-button loop introduced in Plan 3. Plan 4's god-tier wiring also exercises this path (every T2 final → 1 god-tier upgrade button), so the 1-button render gets exercised by both Bucket G and god tier.

Acceptance criteria 28–32 from Plan 3 §9 carry over into §6 below.

### 2.4 Visual treatment

#### Universal god-tier markers

1. **Pulsing halo under T3 turrets.** New sub-layer between the range circle and the turret sprite. Color `#fee761` (gold, parent design doc §6.5 god-tier glow palette). Sin-based alpha pulse, period ~2s, alpha range 0.3–0.6. Diameter ~1.4× turret base. Drawn for every cannon with `tier === 'god'`.
2. **1.7× muzzle flash on T3 shots.** `drawMuzzleFlash` reads the new `flashScale` field. Existing radial-gradient flash arc scales by the field's value.
3. **Screen shake on heavy ground-pounders.** Earthshaker (`screenShakeMag: 8`), Annihilator (`screenShakeMag: 5`). Reuses Plan 3's `game.screenShake = { mag, until }` pattern. Data-only.
4. **Wave-15 unlock pulse on T2 finals' upgrade buttons.** Detailed in §2.2 above.

#### Per-mechanic visuals

5. **Blizzard aura ring (MP-N).** Translucent circle at the cannon position, radius 150px, fill `rgba(150, 200, 255, 0.15)`, 1px stroke at alpha 0.4. New sub-layer between the range circle and the turret sprite, sitting under the god-tier halo. Renders every frame Blizzard is alive.
6. **Biohazard infected outline.** Bright cyan (`#00ffff`) outline pulse around ants whose `effects.poison` carries `damageAmp: 1.25`. Sin-based alpha pulse, period ~0.6s, alpha range 0.4–0.9. ~2px wide, on top of the existing ant sprite. Distinct from poison's existing green tint.
7. **Cluster Strike split moment.** White-flash burst (~12px radius, 0.1s lifetime) at the split point as the 3 child projectiles spawn. Reuses the existing splash-flash effect entry shape.
8. **Phoenix burn-spread cue.** Orange shockwave ring (radius 0 → 30px over 0.2s, decaying alpha) at the dying-burn ant's position when `burn.onExpire` propagates.
9. **Tempest knockback ring.** Brief ring expansion at the impact point (radius 0 → 50px over 0.15s, white stroke decaying alpha). Same primitive as Phoenix's spread.
10. **Stormcaller chain extension.** No new visual code. Plan 3's Tesla draws a polyline lightning bolt for ~0.15s; Stormcaller's 6 jumps means a 7-segment polyline. Same render path, longer array.
11. **Vortex two discs / Saturation 3-shell volley.** No new visuals. Two parallel returning projectiles for Vortex (with ±5px perpendicular spawn offset) and three arcing shells for Saturation (with 0.05s time-stagger). Reuses existing render paths.

#### Render layer impact

Fits as sub-layers of Plan 3's existing 13 slots. No new top-level layer needed. The god-tier halo + Blizzard aura sit between the range circle and turret sprite. Biohazard outline sits on top of the ant sprite. Cluster Strike split / Phoenix spread / Tempest ring all share Plan 3's existing transient-effect entry shape (splash-flash).

### 2.5 Hygiene + carryover

**`||` operators sweep.** Plan 2 and 3 status docs both flagged the six remaining `||` instances in inline JS. CLAUDE.md classifies `||` as "probably suspect" alongside `&&` because WP's content filter HTML-encodes them inconsistently to `&#124;&#124;`. Plan 4 sweeps all six. Each replaced with the verbose `var x = a; if (!x) { x = b; }` pattern. Verify with `curl -s http://localhost/dariobimbi.com/demos/ant-buster/ | grep -c '&#124;&#124;'` returning 0.

**Slice timeout simDt scaling.** Plan 3 status doc §5: slice timeouts read `game.time` instead of the simDt-scaled accumulator. None of Plan 4's mechanics specifically depend on slice age, but the technical debt is small to clear. Switch the slice's `timeout` field to read from the simDt accumulator. Verify at 0.5× and 2× game speeds (Plan 2's debug speed control).

---

## 3. Architectural changes from Plan 3

### 3.1 New CANNON_KINDS fields

Six fields added (`tier`, `flashScale`, `splashStatus`, `visualScale`, `statusOnFirstHit`, `screenShakeMag`). All default to backward-compatible values, so existing 30 rows continue to work without migration.

### 3.2 New STATUS_KINDS hooks

Two additions: `burn.onExpire(deadAnt)` (parallel of `poison.onTargetDeath`) and `poison.damageAmp` (parallel of `freeze.damageAmp`).

### 3.3 New game state fields

`game.godTierUnlocked` (boolean, default false) and `game.godTierUnlockPulseStart` (number, default 0). Both reset on game-over → restart.

### 3.4 Render layer additions

No new top-level layers. Sub-layers added on existing slots:
- Sub-layer 4a: god-tier halo (between range circle and turret sprite)
- Sub-layer 4b: Blizzard aura ring (under the god-tier halo, above range circle)
- Sub-layer 7c: Biohazard infected outline (on top of ant sprite, before status icon overlays)
- Sub-layer 9d: Cluster Strike split flash, Phoenix burn-spread ring, Tempest knockback ring (share the existing splash-flash effect entry)

### 3.5 File size projection

Plan 3 ended at ~3300 lines in `patterns/demo-ant-buster.php`. Plan 4 adds:
- ~18 god-tier cannon rows: ~150 lines of CANNON_KINDS data
- 4 new mechanic primitives: ~250–350 lines (MP-N is the largest at ~80–100 lines)
- 2 framework hooks: ~30 lines
- Splash extension: ~10 lines
- 3 Pink Rubber rows + MP-L: ~80 lines
- Wave-15 unlock state + UI rendering: ~80 lines
- Visual treatment (halo, flash scale, screen shake, Blizzard aura, Biohazard outline, Cluster split, Phoenix spread, Tempest ring): ~150 lines

Estimated end-of-Plan-4 file size: **~3950–4150 lines**. Crossing the threshold where extracting the cannon table to a separate `data/` PHP file would be reasonable. Plan 5 should revisit the split.

---

## 4. Out of scope (deferred to Plan 5+)

### 4.1 Plan 5 — Polish + production-ready

- Title screen (Start Campaign / How to Play, with high-score badges once save state lands)
- Save state via localStorage (key candidates: campaign-cleared flag, fastest clear time, endless high-water mark, tutorial-seen flag, sound on/off)
- Tutorial popover (first-run drag-from-shop hint, selected-tower-panel hint)
- Victory overlay per parent spec §7.6 (dimmed canvas, run stats, Continue-into-endless / Return-to-title buttons)
- Game-over overlay enhancements (run stats, Retry / Return-to-title)
- SFX library + per-event triggers
- Optional music
- Endless mode (wave 26+, procedural generation, +5%/+3% scaling per wave)
- Real PNG sprite art (replace procedural placeholders)
- Bomber AoE-disable cue (greyscale + sparks + countdown ring)
- Phase intangibility cue (alpha pulse + outline shimmer)
- Selected-tower panel kill count + DPS readout (parent spec UI-2)

### 4.2 Future synergy candidates (logged from Plan 4 brainstorming)

These came up during brainstorming as good design instincts but stretched scope. Capturing here so the design intent isn't lost.

- **Brittle.** "If a frozen ant is hit by a Heavy family projectile, the freeze ends early but deals a flat 10% of the ant's max HP as bonus damage." Cross-family synergy reward. Concern: 10% of King max HP = 300 bonus damage per shatter, which materially shifts the King fight before the Plan 4 playtest can establish a baseline. Re-evaluate after Plan 4 ships.
- **Rubbery Resonance.** "Once a god-tier unit is unlocked, Pink Rubber projectiles gain 10% chance to stick + slow 5% per remaining bounce." Global synergy that keeps the Pink Rubber family relevant in the late game. Re-evaluate after Plan 4 ships.
- **Per-tower targeting priorities.** Closest / strongest / first / last toggle per cannon. Standard TD UX. Real value, but its own UI surface and per-cannon state. Plan 5+ feature, not Plan 4 scope creep.
- **Pink Rubber god-tier (4th-family T3).** Add only if the family feels under-finalled next to Plan 4 god-tier from the original three families. The "no god-tier" identity is part of the family's design intent (casual / approachable / always available); adding a T3 dilutes that. Re-evaluate after Plan 4 ships.

### 4.3 King-fight tuning deferred to post-implementation playtest

The "Option C" decision from brainstorming: leave the King at 3000 HP and the Plan 3-tuned threshold-summon timings, do a single-session playthrough with one god-tier path maxed, decide based on feel. **No tuning task in Plan 4.**

If the playtest reveals balance issues, the documented escape valve is **Option D — phase-based scaling**: instead of a raw HP bump, give the King a "shield" or enrage state at a HP threshold (e.g., 50%) that interacts specifically with god-tier mechanics. Examples sketched during brainstorming:
- Visual state change at 50% HP (King turns red, gains 15% movement speed)
- Sustained-DPS-only damage window (shield breaks under continuous beam pressure but resists burst)

These are entirely playtest-driven and not pre-baked into Plan 4's task list.

The numbers cited during brainstorming as drivers for "bump to 6000 HP" (Hawkeye solo-deleting the King, $1000 god-tier costs) were factually inaccurate. Hawkeye's +200% modifier is vs Queen specifically; it does base 80 dmg / 0.3 fire rate = 24 DPS solo against the King (~125 seconds for 3000 HP). God-tier upgrades cost +250 to +300 gold, not $1000. The decision to defer remains correct on the actual numbers.

### 4.4 Real PNG sprite art

Plan 5+. Plan 4 stays procedural for everything (god-tier halos, Blizzard aura, Biohazard outlines, etc.).

---

## 5. Project conventions to enforce

Carry forward from Plan 2 / Plan 3:

- **`var` over `let`/`const`** in inline scripts (CLAUDE.md inline-script gotcha).
- **No `&&` or `||` in inline JS.** Use the verbose `if` / `var x = a; if (!x) { x = b; }` form. Plan 4 sweeps the remaining six `||` instances. Curl-grep the served HTML after each commit that touches inline JS.
- **Function declarations, not function expressions** for shared helpers.
- **CANNON_KINDS / ANT_KINDS / STATUS_KINDS / WAVES tables stay at the top of the IIFE.** With Plan 4 adding 18 god-tier rows (bringing CANNON_KINDS to 51 entries), file size approaches the threshold for extracting to a separate data file. Hold off on the split for Plan 4; revisit in Plan 5.
- **Bump the theme `Version:` in `style.css`** at the end of Plan 4.
- **Expose Plan 2 / 3 `window.__ab*` debug surfaces.** Plan 4 adds `__abGodTierUnlocked` (read/write via debug bypass) and ensures every existing global stays exposed.
- **Voice rule does not apply** to design docs and PHP/JS comments. Em-dashes, technical jargon, CV-style phrasing fine inside the spec and admin-only code comments. The voice rule applies to user-visible UI text.

---

## 6. Acceptance criteria (Plan 4 done = all of these)

A QA walk against `http://localhost/dariobimbi.com/demos/ant-buster/?debug=1` should verify each.

**Cannon system — god tier (§2.1):**

1. All 18 god-tier cannons buildable from their parent T2 finals after wave 15 clears. Selected-tower panel renders 1 god-tier upgrade button per T2 final.
2. All 4 new mechanic primitives functional: MP-L bouncing retarget (Pink Rubber chain), MP-M mid-flight split (Cluster Strike), MP-N persistent radial aura (Blizzard), MP-O beam line-pierce (Solar Lance).
3. Reaper instakills Workers (10 HP) and Scouts (6 HP) on every hit; against full-HP non-Worker/Scout targets deals 2× base; otherwise 1×.
4. Hawkeye's `damageModifier` returns 3 for `kind === 'queen'` and 1 for everything else (including King). Verified by `__abCannonKinds.hawkeye.damageModifier({kind:'king'}) === 1`.
5. Particle Cannon line-pierces all ants in path with no falloff.
6. Annihilator armor-pierces (full damage vs Tank / Soldier) and applies splash radius 60.
7. Saturation fires 3 arcing shells per volley with 0.05s time-stagger.
8. Cluster Strike's parent missile splits into 3 homing children at 50% of original travel distance to target. White-flash burst renders at split point.
9. Vortex fires 2 simultaneous discs with ±5px perpendicular spawn offset; each disc hits 4 times (out + 2 pierces + return).
10. Earthshaker's splash applies 0.6s stun via `splashStatus` to all ants in radius 120.
11. Phoenix's burn-on-expire propagates to ants within 30px when burn duration runs out (not on death). Orange shockwave ring renders at the dying-burn ant's position.
12. Solar Lance's beam damages primary + 2 line-aligned ants per tick.
13. Stormcaller's Tesla arc jumps 6 ants within 80px each, falloff = 1.0 (no damage decay).
14. Scattergun fires 8 pellets in 70° cone, each with `linePierce: 1`.
15. Phalanx fires 3 alternating barrels at 18/s with `aimJitter: 0`.
16. Blizzard's MP-N aura damages all ants in 150px every tick at 1 dmg, applies 50% slow with 0.15s expiry, picks uniform-random ant for 2s freeze every 5s. Translucent radial aura renders at the cannon position.
17. Absolute Zero applies 1.5s freeze to direct hit + 0.5s freeze to all ants in 40px radius via `splashStatus`.
18. Biohazard's poison applies `damageAmp: 1.25` to infected ants. Any cannon firing on a Biohazard-infected target deals +25% damage. Verified by inspecting `applyDamage` reads `target.effects.poison?.damageAmp ?? 1` before mitigation.
19. Tempest's `splashStatus` applies knockback 50 + stun 0.8s to all ants in 50px ring on impact.

**Wave-15 unlock (§2.2):**

20. `game.godTierUnlocked === false` on game start and after game-over → restart.
21. Wave 15's `alive === 0` transition sets `godTierUnlocked = true` and stamps `godTierUnlockPulseStart = game.time`.
22. Locked god-tier upgrade button renders at 50% alpha with tooltip `"Unlocks at wave 15"` on hover.
23. Click on locked god-tier button is no-op (defense-in-depth via `upgradeTower` guard).
24. Post-unlock, T2 finals' god-tier upgrade button renders at 100% alpha; click commits the upgrade in place via Plan 2 SYS-2 (preserving position and current target).
25. `?debug=1 G` hotkey toggles `godTierUnlocked` and stamps the pulse start when toggling true.
26. `?debug=1 W` hotkey clears `game.ants`, sets `waveIndex = 15`, `phase = 'prep'`, `prepTimer = 20`, and `godTierUnlocked = true`.
27. Wave-15 unlock pulse renders golden halo behind T2 finals' god-tier button for 0.5s decay window.

**Bucket G (§2.3, carryover from Plan 3 §9):**

28. Pink Pea buildable from build palette starting wave 1 at 50g cost. Shop column shows 4 cards (Howitzer / Repeater / Twin Gun / Pink Pea).
29. Pink Pea projectile bounces exactly once on impact, redirecting to nearest non-original ant within 50px. Decayed damage = `5 × 0.8 = 4` on the second hit.
30. Bouncy Bubble bounces up to 2 times (radius 60). Projectile renders at 1.5× scale via `visualScale`.
31. Rubber Wrecking Ball bounces up to 4 times (radius 70). Projectile renders at 2.5× scale. First impact applies 30px knockback via `statusOnFirstHit`; subsequent bounces deal damage but no knockback.
32. Selected-tower panel shows 1 upgrade button on Pink Pea (→ Bouncy Bubble) and Bouncy Bubble (→ Rubber Wrecking Ball). Wrecking Ball shows 0.

**Visual treatment (§2.4):**

33. T3 turrets render with pulsing gold halo (color `#fee761`, sin pulse period ~2s, alpha range 0.3–0.6) under the turret sprite.
34. T3 muzzle flash renders at 1.7× scale via `flashScale: 1.7` field. Verified visually on Particle Cannon, Hailstorm, Scattergun.
35. Earthshaker triggers screen shake (`screenShakeMag: 8`) on fire; Annihilator triggers screen shake (`screenShakeMag: 5`).
36. Wave-15 unlock pulse renders golden halo on T2 finals' god-tier button for 0.5s decay (covered in item 27).
37. Blizzard renders translucent radial aura (light blue, 15% opacity, 1px stroke at alpha 0.4) at the cannon position.
38. Biohazard-infected ants render bright cyan (`#00ffff`) outline pulse on top of ant sprite, distinct from poison's existing green tint.

**Hygiene (§2.5):**

39. `curl -s http://localhost/dariobimbi.com/demos/ant-buster/ | grep -c '&#124;&#124;'` returns 0.
40. Slice timeout reads simDt accumulator; verified slices time out correctly at 0.5× and 2× game speeds.
41. Theme `Version:` in `wp-content/themes/dariobimbi/style.css` bumped, pattern cache invalidates on next admin pageview.
42. No console errors during a wave 1–25 playthrough at `?debug=1`, locked-state and unlocked-state both walked.

---

## 7. Decision log

Captured during the brainstorming session, in the order they were locked.

- **Plan 4 = content-heavy (Option A).** 18 god-tier cannons + wave-15 unlock + Bucket G + hygiene. Plan 5 owns title screen / save state / audio / sprite art.
- **King-fight tuning deferred to post-implementation playtest (Option C).** The "feel" indicators that drive the call: clarity factor (screen readability with new visuals), power-spike window (does the King's HP bar visibly chunk down when god-tier fires), T3 gap (does a T3-only player still have a fighting chance). Option D phase shield / enrage held in reserve as documented escape valve.
- **Visual juice = light decorative pass (Option B).** Mechanic-essential visuals + 1.7× muzzle flash + screen shake on heavy ground-pounders + pulsing halo under T3 turrets + Biohazard target outline. No real PNG sprite art, no per-cannon trail effects.
- **Biohazard amp visual lives on the target, not on the turret.** Bright cyan outline pulse on infected ants. The buff visual is in the locus of effect (the ant being amped), not on the cannon firing five seconds ago.
- **Wave-15 unlock = in-session only, no localStorage.** Reset on game-over → restart. New run = re-clear wave 15.
- **Live UI un-grey on next frame after wave 15 clears.** No popup, no overlay banner. Quiet by design.
- **One-shot 0.5s halo pulse on T2 finals' god-tier button when wave 15 clears.** Marks the milestone moment without being intrusive.
- **`?debug=1 G` hotkey toggles unlock state. `?debug=1 W` jumps to wave 16 prep with unlock = true.** Both gated behind the `?debug=1` URL query, no effect in production.
- **Phalanx tracking lead skipped (Option B).** Phalanx ships as "Minigun with 3 alternating barrels and `aimJitter: 0`." Already differentiated from Minigun by the barrel pattern + higher fire rate.
- **Blizzard MP-N = continuous radial damage zone (Option A).** The cannon *is* the AoE. No projectile, no fire-rate cooldown, no target acquisition. Three independent timers (per-tick damage, per-tick slow re-application with 0.15s expiry, 5s random-freeze pick).
- **Pink Rubber Family stays linear, no T3 god-tier.** "Casual / approachable" identity. The 4th family without a god-tier endpoint is intentional.
- **MP-L bouncing-retarget reuses Plan 3's `_sawAnts` array.** Structural prevention of double-hits on the same projectile, so MP-L can't "shotgun" against a large hit-box (no 0.05s cooldown needed).
- **Sell-back stays at 70% (Plan 2 baseline, parent design doc §3).** Plan 4 doesn't change the economy.
- **God-tier upgrade costs +250 to +300 gold.** Realistic gold pool across waves 16–25 supports 2–3 god-tier commits, not the whole 18. Tight on purpose, matches parent design doc §3.4.6.
- **Reaper instakill via existing `damageModifier` hook.** Not a new primitive. `damageModifier(target) => target.kind === 'worker' || target.kind === 'scout' ? 999 : ...`. Returns multiplier large enough to overkill (60 × 999 = 59940 vs 10/6 HP).
- **Hawkeye drops parent design doc's "shoot through obstacles" clause.** No obstacles exist in the playfield.
- **Brittle, Rubbery Resonance, per-tower targeting priorities, Pink Rubber god-tier — all Plan 5+.** Logged in §4.2.
- **No "Restart Wave" feature exists; no guard needed.** Game-over → restart is the only restart flow, and it intentionally resets the unlock.
- **Estimated Plan 4 size = ~31 implementation tasks.** In line with Plan 3's 30 commits + status doc.

---

## 8. Implementation tasks (rough sketch — to be expanded by writing-plans)

Eight buckets, ~31 tasks. Architecture refactors first (unblock everything), then Bucket G (small, already designed, exercises the 1-button selected-tower-panel path that god tier also uses), then new primitives + their visuals (interleaved so each primitive ships with the cannon that uses it), then visual juice, then god-tier configs by family, then unlock UI, then hygiene, then QA + status doc.

**Bucket A — Architecture refactors (5 tasks):**

A1. Add `tier` field to CANNON_KINDS. Backfill existing 30 rows. Add `flashScale` field (default 1.0).
A2. Add `splashStatus` field on CANNON_KINDS + extend the splash function to apply `statusOnHit` to all caught ants when set.
A3. Add `STATUS_KINDS.poison.damageAmp` field (default 1), read by `applyDamage`.
A4. Add `STATUS_KINDS.burn.onExpire(deadAnt)` framework hook + `spreads: true` flag pattern.
A5. Add `game.godTierUnlocked` + `game.godTierUnlockPulseStart` state fields, the wave-15 unlock trigger inside `updateWaveState`, and the click-handler gate inside `upgradeTower`.

**Bucket B — Pink Rubber Family / Bucket G carryover (4 tasks):**

B1. MP-L bouncing-retarget primitive in `fireProjectile` / `updateProjectiles`. Reuse `_sawAnts` array.
B2. `visualScale` and `statusOnFirstHit` fields on CANNON_KINDS.
B3. `pink_pea`, `bouncy_bubble`, `rubber_wrecking_ball` CANNON_KINDS rows + procedural pink turret + ball sprites.
B4. Build palette extension from 3 cards to 4. Verify selected-tower-panel 1-button render via Pink Pea / Bouncy Bubble.

**Bucket C — New mechanic primitives + their visuals (7 tasks):**

C1. MP-M mid-flight projectile split (Cluster Strike's missile split branch).
C2. MP-N persistent radial aura (Blizzard's no-target update branch + 0.15s slow buffer + 5s random-freeze timer).
C3. MP-O beam line-pierce (Solar Lance's per-tick line-cylinder damage).
C4. Cluster Strike split white-flash visual.
C5. Phoenix burn-spread shockwave ring + Tempest knockback ring (share the transient-effect entry shape).
C6. Biohazard cyan outline pulse on infected ants.
C7. Blizzard aura ring render (translucent circle).

**Bucket D — Universal god-tier visual markers (4 tasks):**

D1. Pulsing halo under T3 turrets (`tier === 'god'` branch in turret render).
D2. Muzzle-flash scaling reads `flashScale` field.
D3. Screen-shake hooks on Earthshaker (`screenShakeMag: 8`) and Annihilator (`screenShakeMag: 5`).
D4. Wave-15 unlock pulse render on T2 finals' god-tier upgrade buttons.

**Bucket E — God-tier cannon configs by family (3 tasks):**

E1. Heavy family — six god-tier rows (Particle Cannon, Annihilator, Saturation, Cluster Strike, Vortex, Earthshaker). Wire `upgradesTo` on each T2 final to the matching god-tier ID.
E2. Quick family — six god-tier rows (Hailstorm, Phoenix, Reaper, Solar Lance, Hawkeye, Stormcaller).
E3. Double family — six god-tier rows (Scattergun, Phalanx, Blizzard, Absolute Zero, Biohazard, Tempest).

**Bucket F — Unlock UI (3 tasks):**

F1. Selected-tower-panel rendering for the locked god-tier upgrade button (50% alpha + `"Unlocks at wave 15"` tooltip).
F2. `?debug=1 G` hotkey toggles `godTierUnlocked` and stamps pulse start.
F3. `?debug=1 W` hotkey jumps to wave 16 prep with `godTierUnlocked = true`.

**Bucket G — Hygiene (2 tasks):**

G1. Sweep the 6 remaining `||` operators in inline JS. Verify curl-grep returns 0.
G2. Slice timeout simDt scaling. Verify behavior at 0.5× and 2× game speeds.

**Bucket H — Final QA + status doc (3 tasks):**

H1. Theme `Version:` bump in `style.css`.
H2. Playwright walk against §6 acceptance criteria. Both locked and unlocked god-tier state walked.
H3. Plan 4 status doc.

The writing-plans pass might consolidate or split (e.g., Bucket E's three family-tasks could split into six per-tier tasks, or Bucket D's halo + flash + shake could merge into a single "T3 visual treatment" task). Realistic landed range: **28–35 commits**.

---

## 9. Post-implementation playtest checklist

Once Plan 4 ships, run a single-session playthrough to validate the King-fight balance call (Option C). The "feel" indicators to watch:

1. **Clarity factor.** Does the screen remain readable during the King fight with the new visuals? Specifically: Blizzard's aura ring + Solar Lance's beam pierce + Biohazard's cyan outlines firing simultaneously. If visual noise drowns out the King's HP bar, that's a problem worth fixing in Plan 5.
2. **Power-spike window.** Does the King's HP bar visibly chunk down when a god-tier cannon fires, or is it a slow grinding drain? A god-tier commitment should produce a noticeable spike, not just a marginal DPS bump.
3. **T3 gap.** Does a player without god-tier cannons (the "spent gold poorly" or "lost wave 15 + restarted" run) still feel like they have a fighting chance with a perfect T3 setup? If wave 25 becomes "wait-to-win" without god tier, that's a balance flag.

Stress-test scenarios using `?debug=1 W`:

1. **Minimalist run.** Use `W` to land at wave 16 with one god-tier upgrade pre-built. Push to wave 25 with minimal additional towers. Can a single god-tier line solo the King fight? If yes, the wave 25 King is too soft. If it takes 4–5 minutes, the King is appropriately tuned.
2. **Pivot rescue.** Build a full T2 board, sell half at wave 24, see if a fresh god-tier upgrade can rescue a failing run. Tests whether the +70% sell-back gives enough flexibility for a panic-pivot.

If both stress tests pass and the live walk shows good clarity / power-spike / T3 gap, the King fight stands at 3000 HP. Otherwise, the Option D phase shield / enrage state is the documented next step. Plan 4 itself does not commit to Option D — that's a Plan 4-status doc decision once the playtest evidence is in hand.

---

End of Plan 4 design spec. Pending user review before transitioning to writing-plans.
