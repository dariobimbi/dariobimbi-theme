# Plan 7: The Commander's Console — Per-Tower Targeting Priority

**Spec date:** 2026-05-08
**Status:** Ready for plan-writing.
**Theme version target:** 0.13.0 (current 0.12.0).
**Branch base:** `main` at commit `c8d4eb2` (Plan 6 merge).

## §1 — Goal & Non-Goals

### Goal

Add a per-tower targeting priority system to Ant Buster so the player can tell each cannon what kind of target to prioritize. Five modes:

- **Closest** — pick the ant with the smallest distance from the cannon (current default behavior).
- **First** — pick the ant with the smallest distance to the cake (closest to the goal).
- **Last** — pick the ant with the largest distance to the cake (closest to the anthill, or returning home with cake).
- **Strongest** — pick the ant with the highest current HP.
- **Weakest** — pick the ant with the lowest current HP.

This turns the game's existing spatial puzzle (Plan 5) and compositional puzzle (Plan 6) into a tactical puzzle: which target each placement should hunt, and how that decision interacts with the build's status effects, range bands, and damage profiles.

### Non-Goals

- **Persistence.** No `localStorage` work. Targeting modes reset to `'closest'` on page refresh, like every other in-session state.
- **Audio.** No click sound for mode buttons. Ant Buster has no audio system today and Plan 7 does not introduce one.
- **Global toggles.** No "set all cannons to mode X" button. Targeting is strictly per-cannon. The strategic value of the feature is per-cannon differentiation; a global toggle would defeat the purpose.
- **Aura cannons.** Cannons whose kind def has an `aura` property (MP-N and similar radial-effect cannons) do not call `findTarget()`. They damage everything in radius and freeze a uniform-random pick. The targeting UI is omitted on those cannons. Cone-DoT cannons (Plasma Torch) DO call `findTarget()` to aim the cone, so they receive the UI like normal projectile cannons.
- **Status-aware modes.** No "Target Un-Slowed" or "Target Frozen first" mode. Status-aware targeting is a real design space but a different conceptual category (filtering by status presence vs ranking by inherent property), and deserves its own design pass. Logged as a Plan 8+ candidate.
- **Tooltip copy or in-game guidance.** No "the King is the strongest target" tutorial text in the UI. The mode buttons are dials; what the player does with them is the tactical decision.
- **New cannon kinds.** Plan 7 modifies the targeting logic of the existing 51 cannons. No new cannons.
- **Sprite work.** No new sprite art. The button row uses single-character text glyphs at 22×22 px, drawn directly to canvas. Eighteen god-tier silhouettes logged as a Plan 8+ candidate, restricted to canvas factories rather than raster.

## §2 — Architecture Overview

### 2.1 — Per-cannon state

Each cannon instance gains one new property:

```js
cannon.targetingMode  // string: 'closest' | 'first' | 'last' | 'strongest' | 'weakest'
```

Default is `'closest'`. This matches the current `findTarget()` behavior, so all existing cannons behave identically until the player explicitly changes a mode. The property lives on the cannon instance, not on the kind def, because targeting is a per-placement decision.

When a cannon is upgraded via `upgradeCannon`, the new instance must inherit the targeting mode from the old instance. Without this, every upgrade silently resets the mode.

### 2.2 — Targeting math

No per-frame ant state is added. The path-progress concept from earlier brainstorming was rejected because Ant Buster's ants do not follow a waypointed track. They walk straight toward the cake using `angleTowards` plus wander offset and cannon-avoidance steering (see `updateAnt`, line 2512). Distance to the cake is the meaningful "progress" measure for this game's geometry.

For First and Last, `findTarget()` reads `game.cake.x` and `game.cake.y` directly from live state and computes the squared distance from each candidate ant to the cake. No pre-compute, no caching, no King-specific code path. The King competes with every other ant on actual distance:

- When the King is near the cake, his distance-to-cake is small and First mode picks him.
- When a Royal Guard slips past him to the cake, the Royal Guard's distance-to-cake is smaller and First mode picks the Royal Guard.

Both behaviors are correct.

### 2.3 — UI integration

The selected-tower panel grows a new "Targeting" row:

- Position: between the cannon name/level header and the upgrade buttons.
- Size: 5 buttons, each 22×22 px, with a 4 px gap between them. Total row width 134 px (5 × 22 + 4 × 4).
- Glyph: single bold character (C, F, L, S, W).
- Active state: 2 px high-contrast white border around the active mode's button.
- Hover state: brief one-shade-lighter background highlight.
- Below the button row: a single-line text label like "Targeting: Strongest" that updates based on the active mode.

The targeting row is omitted entirely when the selected cannon's kind def has an `aura` property (truthy object check, not boolean). Cone-DoT cannons (`def.coneDot`) and beam cannons (`def.beam`) both call `findTarget` and use targeting modes meaningfully (cone heading and beam target follow the picked ant), so they receive the UI.

### 2.4 — Visual feedback

When `ui.selectedCannon` is non-null, draw a tactical overlay:

- A single dashed white line from the cannon center to its current target, rendered at alpha 0.4.
- A 4 px white square reticle on the target ant.

Both elements are drawn in white regardless of which targeting mode is active. Coloring the line by mode would compete with hit-flash red and gold-cost UI; staying neutral keeps the overlay readable.

If the cannon has no current target (none in range, or the cannon is on cooldown with `cannon.target === null`), the overlay does not render that frame.

## §3 — Targeting Logic (findTarget Refactor)

The current `findTarget(cannon)` at line 3605 is a single-pass loop that picks the nearest ant in range, with a short-circuit override for any `isRevealed` ant. Plan 7 generalizes it into a filter-then-pick pattern.

### 3.1 — Filter phase

Single pass over `game.ants` collects candidates:

1. Skip ants where `dead` is truthy. (The existing `dead` flag is the canonical "ant is in the zombie state, do not target" signal. Using `dead` rather than `hp <= 0` matches existing semantics and avoids a brief mid-frame race window where hp has dropped but the dead flag has not yet been set.)
2. Skip ants where `intangible` is truthy. (Phase ants, etc.)
3. Skip ants whose squared distance to the cannon exceeds `def.range²`.
4. For each candidate kept, also note if `isRevealed` is true.

If the candidate set is empty, `findTarget` returns `null` (same as current behavior).

### 3.2 — Stealth override

If any candidate is `isRevealed`, the candidate set is reduced to revealed ants only. This preserves the existing Hawkeye-vs-stealth dynamic: a revealed Stalker outranks every other consideration. The targeting mode then picks within the revealed subset.

If no candidate is revealed, the full candidate set is used.

This is strictly better than the existing "first revealed in iteration order" short-circuit, because it lets the player apply the targeting mode within the revealed pool. A Hawkeye-plus-Reaper combo set to Strongest will pick the toughest revealed ant, not the first one the loop happens to hit.

### 3.3 — Pick phase

The targeting mode picks one ant from the active candidate set:

| Mode | Picks ant with... |
|------|---|
| `closest` | smallest squared distance to cannon |
| `first` | smallest squared distance to cake (`game.cake.x, game.cake.y`) |
| `last` | largest squared distance to cake |
| `strongest` | highest current `hp` |
| `weakest` | lowest current `hp` |

### 3.4 — Tie-breaking

If two ants tie on the active mode's metric (e.g., two Workers with identical HP in Strongest mode, or two ants equidistant from the cake), the loop falls through to whichever appeared first in `game.ants`. Iteration order is the deterministic tie-breaker. Closest is NOT used as a secondary tie-breaker. That would complicate the loop without observable gameplay benefit, given how rare exact ties are in continuous-value play.

### 3.5 — Performance notes

- One pass over `game.ants`, ~30-50 entries on screen at peak.
- Per-ant cost in the filter pass: 2 multiplications and a compare for the range filter.
- Per-ant cost in the pick pass: one more squared-distance calc and one compare for First/Last; one compare for Strongest/Weakest; reuses the already-computed cannon-distance for Closest.
- Total cost stays well below 1 ms per `findTarget` call. Not a concern.

### 3.6 — Inline-script gotchas

The implementation must follow the codebase conventions documented in CLAUDE.md:

- **`var` only.** No `let` or `const`. WordPress's content filter is comfortable with `var` and the existing IIFE uses it exclusively.
- **No `&&` or `||` in the inline script.** WordPress occasionally HTML-encodes `&&` to `&#038;&#038;` and `||` is suspect by association. Use nested `if` statements instead.
- **`function () { ... }` style.** No arrow functions. Function declarations are hoisted and safe.
- **No `Array.prototype.filter` / `reduce` chains.** They allocate, and the existing `findTarget` style is a single-pass `for` loop.
- **No `Math.pow(x, 2)`.** The convention is `dx * dx + dy * dy`.

## §4 — Visual Feedback

### 4.1 — Target line and reticle

Drawn during the per-frame render pass, after `drawAnts()` and before any HUD overlay. Gated on `ui.selectedCannon` being non-null and the cannon having a current target (`cannon.target` non-null and not dead).

```
Pseudocode (final form lives in §6.6):

if (ui.selectedCannon) {
  var c = ui.selectedCannon;
  if (c.target) {
    if (!c.target.dead) {
      // dashed white line at alpha 0.4, from cannon center to target center
      // 4px white square reticle on target
    }
  }
}
```

The dead-check guards against drawing to a target the cannon already killed (the dead ant is briefly still in `game.ants` while the splat plays out).

### 4.2 — Style

- Line: stroke style `rgba(255, 255, 255, 0.4)`, `lineWidth: 1`, dash pattern `[3, 3]`.
- Reticle: fill style `rgba(255, 255, 255, 0.7)`, 4×4 px square centered on the target ant's position.

Both elements share the same neutral-white treatment. The visual is identical regardless of which targeting mode is active. The mode is communicated by the highlighted button in the sidebar, not by the line color.

### 4.3 — When no target

If the cannon has no in-range target this frame (e.g., between waves with no ants on screen, or all ants are out of range), the line and reticle simply do not render. No "ghost line" pointing at empty space.

## §5 — UI Aesthetics & Glyphs

### 5.1 — Button row

Five buttons, 22×22 px each, 4 px gap. Drawn in the selected-tower panel between the cannon name header and the first upgrade button.

| Glyph | Mode | Active background | Inactive background |
|---|---|---|---|
| C | Closest | `#3a4a8c` (neutral blue) | `#1a1a28` (panel-dark) |
| F | First | `#9a7820` (gold) | `#1a1a28` |
| L | Last | `#3a3a3a` (dark grey) | `#1a1a28` |
| S | Strongest | `#7a2020` (deep red) | `#1a1a28` |
| W | Weakest | `#7a2040` (pink/dark-rose, kept in the red family to avoid the "Green = healthy" semantic mismatch) | `#1a1a28` |

The active button gets a 2 px white border drawn around it. The inactive buttons render with the panel-dark background and white text.

The glyph itself is a single bold character drawn at the button's center. Existing canvas text-rendering conventions (font, baseline) carry over from the cannon-name header.

### 5.2 — Mode label below the row

A single-line text reading `Targeting: <ModeName>` below the button row, where `<ModeName>` is the human-readable form of the active mode ("Closest", "First", "Last", "Strongest", "Weakest"). Updates immediately on click.

This redundantly confirms the active mode in plain text. C/F/L/S/W glyphs require a learning curve, and players who have not memorized the mapping should still be able to read what is selected.

### 5.3 — Hover behavior

Hovering over a mode button shows a brief background highlight (one shade lighter than the inactive background). The mode label below does NOT update on hover, only on click. Hover-preview-of-mode would be over-engineered for a 5-mode dial.

## §6 — Implementation Reference Card

Copy-pasteable blocks the implementer can adapt. Style follows the inline-script conventions from §3.6.

### 6.1 — Cannon initialization

When a cannon is created (during placement, around the `game.cannons.push(...)` site), set `targetingMode`:

```js
var newCannon = {
  // ... existing fields ...
  targetingMode: 'closest'
};
```

The default value matches existing behavior, so cannons placed before any mode is chosen behave identically to today.

### 6.2 — Cannon upgrade preserves mode

In `upgradeCannon`, copy the targeting mode from the old instance to the new instance:

```js
function upgradeCannon(cannon, targetKind) {
  // ... existing upgrade logic that produces newCannon ...
  newCannon.targetingMode = cannon.targetingMode;
  // ... rest ...
}
```

Without this line, every upgrade silently resets the mode to `'closest'`.

### 6.3 — findTarget refactor

Replaces the existing function at line 3605:

```js
function findTarget(cannon) {
  var def = CANNON_KINDS[cannon.kind];
  var rangeSq = def.range * def.range;
  var mode = cannon.targetingMode;
  if (!mode) { mode = 'closest'; }

  // Single pass: gather candidates with cached distSq, flag if any revealed.
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

  // Stealth filter: if any revealed, keep only revealed.
  var pool = candidates;
  if (anyRevealed) {
    pool = [];
    for (var j = 0; j < candidates.length; j++) {
      if (candidates[j].ant.isRevealed) { pool.push(candidates[j]); }
    }
  }

  // Pick by mode.
  var best = pool[0];
  for (var k = 1; k < pool.length; k++) {
    var c = pool[k];
    if (mode === 'closest') {
      if (c.distSq < best.distSq) { best = c; }
    } else if (mode === 'strongest') {
      if (c.ant.hp > best.ant.hp) { best = c; }
    } else if (mode === 'weakest') {
      if (c.ant.hp < best.ant.hp) { best = c; }
    } else {
      // first or last: compare distance-to-cake.
      var ccdx = c.ant.x - game.cake.x;
      var ccdy = c.ant.y - game.cake.y;
      var bcdx = best.ant.x - game.cake.x;
      var bcdy = best.ant.y - game.cake.y;
      var cToCake = ccdx * ccdx + ccdy * ccdy;
      var bToCake = bcdx * bcdx + bcdy * bcdy;
      if (mode === 'first') {
        if (cToCake < bToCake) { best = c; }
      } else if (mode === 'last') {
        if (cToCake > bToCake) { best = c; }
      }
    }
  }
  return best.ant;
}
```

### 6.4 — Selected-tower panel: targeting row

In whichever function renders the selected-tower panel (the implementer should grep for the upgrade-button rendering site, since the targeting row sits adjacent to it), insert the targeting row gated on cannon kind:

```js
var def = CANNON_KINDS[cannon.kind];
// Skip the targeting row for aura cannons (e.g., MP-N). They never call
// findTarget so the UI would be a no-op. Cone-DoT cannons (Plasma Torch)
// and beam cannons (Glacier, Solar Lance) DO call findTarget and use
// targeting modes meaningfully, so they receive the row.
if (def.aura) {
  // skip rendering the targeting row
} else {
  drawTargetingRow(cannon, panelX, panelY);
}
```

`drawTargetingRow` renders the 5 buttons plus the mode label, hit-tests for clicks (storing rects in `ui.targetingRects` similar to existing `ui.upgradeRects`), and is responsible for the active-state highlight.

### 6.5 — Sidebar click handler

The mousedown handler at the selected-tower panel needs an additional hit-test for the targeting row. Pattern follows the existing `inRect(p, ui.upgradeRects[i])` check style:

```js
// Inside the mousedown handler, after the existing upgrade/sell hit tests,
// before falling through to "click somewhere blank deselects":
if (ui.targetingRects) {
  var modes = ['closest', 'first', 'last', 'strongest', 'weakest'];
  for (var ti = 0; ti < ui.targetingRects.length; ti++) {
    if (ui.targetingRects[ti]) {
      if (inRect(p, ui.targetingRects[ti])) {
        ui.selectedCannon.targetingMode = modes[ti];
        return;
      }
    }
  }
}
```

The mode strings array order must match the button-rendering order. Both must match the mode-name array used for §5.2's label.

### 6.6 — Target line and reticle draw

In the per-frame render pass, after `drawAnts()` and before HUD overlay rendering:

```js
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

Wired into the main render loop after `drawAnts()`, around line 1942 (where `drawShatterParticles()` sits today, so the targeting overlay sits adjacent for code-locality).

### 6.7 — Reset / restart

`resetGame` clears `game.cannons = []`, which means `targetingMode` is implicitly cleared with the cannon. No additional reset code needed for the cannon state itself. `ui.targetingRects = []` should be added to `resetGame` to mirror the existing pattern for `ui.upgradeRects`.

### 6.8 — Theme version bump

`wp-content/themes/dariobimbi/style.css`: `Version: 0.12.0` → `0.13.0`.

## §7 — Edge Cases

### 7.1 — Carrying ants

An ant that has grabbed cake and is fleeing toward the anthill has growing distance-to-cake. In First mode it ranks "last" (de-prioritized). In Last mode it ranks "first" (prioritized). Both are intentional:

- First mode players want to stop the next slice from being grabbed, not chase the lost one.
- Last mode players want exactly the opposite. Finish off the slice-thief before it returns home.

This emerges naturally from the distance-to-cake metric and requires no special handling.

### 7.2 — No candidates in range

If `game.ants` is empty or every ant in range fails the dead/intangible filter, `findTarget` returns `null`. Existing call sites already handle null targets (cannons skip the firing logic). Plan 7 changes nothing here.

### 7.3 — Selected cannon deselected mid-frame

`ui.targetingRects` is populated only when the selected-tower panel renders. If `ui.selectedCannon` becomes null mid-frame (the player clicks elsewhere), the click handler must check `ui.targetingRects` for non-null before iterating, similar to the existing `ui.upgradeRects[i]` null-checks.

### 7.4 — Mode buttons on aura cannons

The targeting row is omitted entirely on aura cannons (MP-N and similar `def.aura` kinds). The selected-tower panel still renders normally with name, upgrade buttons, sell, range preview, just without the targeting row. The vertical space the row would have occupied collapses naturally (the upgrade buttons sit higher). This makes "this cannon doesn't have a targeting decision" obvious by absence rather than by a disabled-looking control.

Cone-DoT and beam cannons receive the row normally. For cone-DoT, the targeting mode determines which ant the cone is aimed at, which then determines which other ants get caught in the cone's spread. For beam, the targeting mode determines the beam's primary target (and, for pierce-beam cannons like Solar Lance, the line along which bystanders take damage).

### 7.5 — Tie cases

Per §3.4, ties resolve to iteration order. This is rarely observable in practice because:

- Ant HP is deterministic per kind, but per-frame damage application desynchronizes individual HP values quickly.
- Distance-to-cake is a continuous value; exact ties require pixel-perfect symmetry that emergent ant movement breaks within milliseconds.
- The visible player-side effect is stable behavior under continuous play, occasional jitter when two ants are temporarily indistinguishable on the active metric.

If this becomes an observable issue (e.g., two cannons fighting over which Worker to kill), the §8 tuning hatch is to add closest-as-secondary-tie-breaker. Not worth doing preemptively.

### 7.6 — King in Strongest mode

The King's 3000 HP makes him an unconditional Strongest-mode target whenever in range, regardless of HP percentage. If the player wants their Reaper to kill Workers while a Hawkeye keeps the King in line, they would set the Reaper to Closest or First, not Strongest. This is correct system behavior. The mode buttons give the player the control to express it.

### 7.7 — Royal Guards (drip-spawned during King fight)

Royal Guards spawn at (10, 320) and walk toward the cake using the standard ant movement. They have:

- Distance-to-cake: large at spawn, decreases as they walk.
- HP: 20 (between Worker 10 and Soldier 40).
- isRevealed: false by default.

In First mode, the Royal Guard becomes "first" once it walks closer to the cake than any other ant on the field. In Strongest mode, the King still outranks them. In Weakest mode, the Royal Guard ranks above the Worker (HP 10) and below most Soldier-tier and above. Mode behavior is intuitive.

### 7.8 — Mid-shot mode change

The targeting mode is read at the moment `findTarget` is called, which happens once per cannon firing cycle (per shot, not per frame). If the player flips the mode mid-shot-cycle, the existing in-flight projectile finishes its journey to its original target. The new mode takes effect on the next call to `findTarget`. This is correct and matches player expectation. Projectiles do not retarget mid-flight based on cannon state.

## §8 — Tuning Hatches

Likelihood-to-fire order. Each hatch is a knob that can be turned in a future plan if real play reveals a mismatch.

1. **Add closest-as-secondary-tie-breaker** if exact ties become a visible problem (§7.5). Two-line code change in §6.3's pick loop.
2. **Mode-colored target line** if the neutral-white treatment turns out not to give enough feedback. Replace `rgba(255, 255, 255, 0.4)` with a mode-dependent color in §6.6's `drawTargetingOverlay`.
3. **Mode-button glyph swap** from text (C, F, L, S, W) to small canvas-drawn icons if the letter glyphs prove ambiguous in playtests. Changes only §5 button-render style. Logic unaffected.
4. **Targeting row position** can move from above the upgrade buttons to below them if visual hierarchy testing prefers it. One-line change in §6.4's render order.
5. **Targeting on aura cannons.** If a future plan introduces a single-target aura cannon (none exist today), the §2.3 omission rule needs revisiting. The `def.aura` check would change to a more specific marker.

## §9 — Acceptance Criteria

QA walk via Playwright against `http://localhost/dariobimbi.com/demos/ant-buster/?debug=1`. Each item is a binary pass/fail.

1. **Default behavior unchanged.** A freshly-placed cannon has `targetingMode === 'closest'` and `findTarget` returns the same ant it would have returned before Plan 7.
2. **Mode persists through upgrade.** Set a cannon to `'strongest'`, upgrade it once, confirm the upgraded cannon's `targetingMode === 'strongest'`.
3. **Closest mode picks the closest ant.** Spawn three ants at varying distances from a cannon, set the cannon to `'closest'`, confirm `cannon.target` is the spatially nearest one.
4. **First mode picks the cake-closest ant.** Same setup, swap mode to `'first'`, confirm the cannon's target is the ant nearest `game.cake.x, game.cake.y`.
5. **Last mode picks the anthill-closest ant.** Mode `'last'`, confirm target is the ant farthest from cake.
6. **Strongest mode picks highest-HP ant.** Mix a Worker (10 HP), Soldier (40 HP), and Tank (200 HP) in range; mode `'strongest'`; confirm target is Tank.
7. **Weakest mode picks lowest-HP ant.** Same mix; mode `'weakest'`; confirm target is Worker.
8. **Stealth override still wins.** Spawn a Stalker (or any stealth ant) plus a tougher non-stealth ant, reveal the Stalker via Hawkeye, set the cannon to `'strongest'`. Confirm the Stalker is the target despite having lower HP, because it is the only revealed ant in range.
9. **Stealth + mode picking within revealed pool.** Spawn two revealed Stalkers in range, one with HP 10 and one with HP 40 (after partial damage). Mode `'strongest'`. Confirm the higher-HP one is the target.
10. **Targeting row hidden on aura cannons.** Place an MP-N (or any aura cannon), select it, confirm the targeting row does not render in the panel.
11. **Cone-DoT heading follows targeting mode.** Place a Plasma Torch with one ant near the cannon and one ant near the cake. Set mode `'closest'` and confirm the cannon's `heading` aims at the cannon-near ant. Switch to `'first'` and confirm the heading shifts to aim at the cake-near ant. (This proves the targeting mode is wired into the cone-DoT path, not just the projectile path.)
12. **Active mode highlighted.** Click each mode button in turn, confirm the 2 px border appears on the clicked button and on no other.
13. **Mode label updates.** After each click, confirm the "Targeting: \<Name\>" label below the button row matches.
14. **Target line draws when target exists.** Select a cannon with an in-range ant. Confirm a faint dashed white line is drawn from cannon to ant, and a 4 px square reticle on the ant.
15. **Target line does not draw without target.** Sell all in-range ants (or wait until the cannon's target is dead), confirm the overlay disappears that frame.
16. **King in Strongest mode is locked on.** Spawn King, place a Reaper in range, set Reaper to `'strongest'`. Confirm Reaper's target is consistently the King across multiple shot cycles.
17. **Royal Guard outranks King in First mode.** Spawn King and a Royal Guard close to the cake, place a Reaper covering both. Set `'first'`. Confirm target is Royal Guard when Royal Guard's distance-to-cake is smaller than King's.
18. **PHP lint passes.** `php -l patterns/demo-ant-buster.php` returns 0.
19. **Hygiene check passes.** Operator-encoding grep (`&#038;&#038;`, etc.) returns 0 against the served HTML.
20. **Theme version is 0.13.0.** Confirmed in `style.css` and on the served page (`?ver=0.13.0`).

All 20 items must pass before merge.

## §10 — Notes for Plan Author

The implementation plan should bite-size as follows:

- **Task 1: cannon.targetingMode initialization + upgradeCannon preservation.** Smallest possible commit. Verifies §6.1 and §6.2. Acceptance items 1, 2.
- **Task 2: findTarget refactor.** §6.3. Verifies acceptance items 3 through 9. Single function replacement; high-confidence in-place edit.
- **Task 3: Selected-tower panel adds targeting row (gated on aura/cone).** §6.4. Verifies acceptance items 10, 11, 12, 13.
- **Task 4: Sidebar click handler wires the buttons to mode changes.** §6.5. Re-verifies items 12, 13 end-to-end (button click changes the active mode).
- **Task 5: Target line and reticle.** §6.6. Verifies acceptance items 14, 15.
- **Task 6: Hygiene.** §6.7 (reset sequence) and §6.8 (version bump). Plus run the full §9 acceptance suite, including items 16, 17, 18, 19, 20.

Each task should ship with a Playwright probe demonstrating the relevant acceptance items pass before commit. Tasks 2 and 3 are the meat. Tasks 1, 4, 5, 6 are smaller and faster.

The plan-doc lives at `docs/superpowers/plans/2026-05-08-ant-buster-plan-7.md`.
