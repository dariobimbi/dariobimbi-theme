# Ant Buster — Plan 2 Design Spec

**Status:** Design complete. Pending user review before transitioning to the implementation plan.
**Date opened:** 2026-05-07
**Author:** Dario, with Claude assist
**Parent spec:** [`2026-05-06-ant-buster-design.md`](./2026-05-06-ant-buster-design.md)
**Plan 1 status:** [`../plans/2026-05-06-ant-buster-plan-1-status.md`](../plans/2026-05-06-ant-buster-plan-1-status.md)

---

## 1. Goal of Plan 2

**Plan 2 = systems + minimum content.** Ship the architectural framework that lets Plan 3 be pure data fill (cannon table rows, wave table rows, sprite swaps). Every code path that Plan 3 will need must be exercised by at least one running stub in Plan 2.

The split was chosen over a one-shot "Plan 2 = full content" because the cannon table in the parent spec §3 is just numbers, while the work that proves the design is the systems (upgrade tree UI, selected-tower panel, ant behaviors, status effects). Once those work, filling 48 rows is mechanical.

---

## 2. What ships in Plan 2

### 2.1 UI surface

| # | Feature | Notes |
|---|---|---|
| UI-1 | Selected-tower panel | Click placed cannon → shop side panel is replaced by a 160-wide tower-detail panel showing name, tier, current stats, 0–2 upgrade buttons, sell button. Range circle drawn on playfield while panel is open. Click outside the cannon (or `Esc`) closes it. |
| UI-2 | Upgrade buttons | Each non-final cannon shows its available upgrade(s). Disabled (greyed) if gold < cost. Click → deduct cost → swap cannon `kind` in place. No confirmation prompt. |
| UI-3 | Sell button | Refunds `floor(0.7 * sum(cost of base + each upgrade))`. Tower removed, panel returns to shop view. |
| UI-4 | Pause overlay | Triggered by Pause button or `Space`. Dims canvas, freezes update loop. Buttons: Resume, Restart Run. (No Quit-to-Title — title screen is Plan 3.) |
| UI-5 | Speed toggle | 1× / 2× toggle in HUD. Multiplies `dt` for ants and projectiles; UI animations and prep countdown unaffected. Keyboard `1` / `2`. |
| UI-6 | Wave intro banner | At wave start, a 96×16 banner slides in from top showing "Wave X/5", dwells 2s, fades. Boss waves get a flash + deeper-toned text (no sound; Plan 3). |
| UI-7 | Send-Next-Wave button | Visible only during prep time. Skips remaining prep, grants `floor(remaining / 2)` gold. Keyboard `Enter`. |
| UI-8 | HUD additions | Pause icon, speed toggle, prep timer countdown, Send-Next button alongside existing gold / cake / wave readout. |

### 2.2 Game systems

| # | System | Notes |
|---|---|---|
| SYS-1 | Status effect framework | Six kinds: `slow`, `freeze`, `burn`, `poison`, `stun`, `knockback`. Each ant has an `effects` slot (object keyed by kind, value `{magnitude, expiresAt, source}`). Each tick: decrement timers, drop expired. Speed scaling reads `slow.magnitude` and treats `freeze` as `slow=1`. Damage pipeline reads `burn`/`poison` for tick damage. `stun` zeros movement. `knockback` applies one-frame impulse on apply. **Refresh rule:** re-applying same kind takes the *longer* remaining duration and *higher* magnitude. **Effects are binary state, not gradual decay** — slow magnitude is constant for the duration, then drops to 0 (no ramp). Plan 2 emits only `slow`, but the framework handles all six. |
| SYS-2 | Upgrade tree | Single `CANNON_KINDS` data table at top of IIFE. Cannon entity stores `kind` and `kindHistory`. **`kindHistory` includes the base kind from construction time** — a fresh Howitzer is created with `kindHistory: ['howitzer']`, not `[]`. Upgrade click: deduct, push current onto history, set new `kind`. Position / target / cooldown / kill count preserved across upgrade. Sell math reads every entry in `kindHistory` plus current `kind`'s cost: `floor(0.7 * (sum(CANNON_KINDS[k].cost for k in kindHistory) + CANNON_KINDS[currentKind].cost))`. |
| SYS-3 | Nine enemy ant types coded | All 10 enemy roster types from parent spec §4.1 except King (deferred to Plan 3). Plus Mini-Splitter as a separate kind (Splitter's death spawn). Total: 10 entries in `ANT_KINDS` table. See §3.2 for per-type behaviors. |
| SYS-4 | Fallen-slice entity | New entity type: `slices[]`, each `{x, y, droppedAt}`. When a Carrier is killed mid-flee, the carried crumb becomes a fallen slice at the death position. **First-come pickup:** the first uncarrying ant within 10 px claims it; once claimed, the slice is removed from `slices[]` and bound to that ant. No contention / fighting — the radius check resolves on first match. Slices time out after 8 seconds and **return to the cake without deducting a life** (cake.lost is unchanged; the slice simply re-attaches to cake state). Renders as small orange-yellow crumb with faint shadow. Replaces Plan 1's snap-back-on-kill behavior. |
| SYS-5 | Soft tower repulsion | Each cannon contributes `force = (towerRadius + 4 - dist) / (towerRadius + 4)` along the away-vector, applied within `towerRadius + 4`. Added after wander/jitter steering. **Per-tick total repulsion is clamped** to magnitude ≤ `1.5 * antSpeed * dt` (units: px) to prevent two adjacent towers from catapulting an ant through walls or into a jitter loop in narrow gaps. After repulsion, ant position is still passed through Plan 1's playfield-bounds clamp + reflective bounce, so the ant cannot exit the playfield even if the clamp ceiling is hit. Ants flow around towers without pathfinding. |
| SYS-6 | Wave system rewrite | Data-driven `WAVES` array. Spawner advances each tick, reads next entry from current wave's spawn list, instantiates ant. Wave-completion check: `aliveAnts === 0 && spawner.queue.length === 0`. Triggers wave bonus + transitions to prep state. |
| SYS-7 | Plan 1 nits cleared | Remove unused `PLAYFIELD_H` (replaced by new constants from §3.1). Splat-on-deliver: separate `deliveredAt` from `deathTime` so delivered ants vanish cleanly while killed ants always splat. Clear `ui.dragging` in `resetGame()`. |
| SYS-8 | Debug spawn hotkey | Press `T` during play → cycles through ant types and spawns one at the anthill on each press. **Gated behind `?debug=1` query param.** Read once on page load: `var DEBUG = /[?&]debug=1/.test(location.search);`. |

### 2.3 Stub content

**9-cannon roster** (numbers from parent spec §3.1–3.3, locked):

| Path | Base | Mid | Final |
|---|---|---|---|
| Heavy | Howitzer (5 dmg, 0.6/s, 110 r, 50g) — *shipped in Plan 1* | Siege (12, 0.6/s, 110 r, +50g) | Railgun (25, 0.6/s, 120 r, +90g) |
| Quick | Repeater (1, 4/s, 130 r, 50g) | Auto-Cannon (2, 6/s, 130 r, +50g) | Vulcan (3, 9/s, 140 r, +80g, gatling spool-up after 0.5s of firing) |
| Status | Twin Gun (2 dmg × 2 barrels, 1/s, 100 r, 75g) | Frost (1, 1.5/s, 100 r, +75g, applies 30% slow for 2s) | Glacier (1/tick, continuous beam, 130 r, +90g, 50% slow while beam holds + 1s linger after disconnect — see note) |

**Glacier slow semantics:** binary 50% state, beam-driven duration. While Glacier's beam is hitting an ant each tick, the slow effect is *refreshed* (expiresAt pushed forward) so it never times out mid-beam. When the beam disconnects (ant moves out of range, dies, or is hidden by Phase intangibility), the slow continues at full 50% for 1 more second, then drops to 0. No gradual ramp on application, no decay on release — clean state-swap, consistent with Frost's binary 30%.

**5-wave campaign** (canonical from parent spec §4.2):

| # | Composition | Prep | Notes |
|---|---|---|---|
| 1 | 8 Workers | 30s | Existing wave from Plan 1 |
| 2 | 12 Workers | 20s | First wave-bonus payout |
| 3 | 6 Workers, gap 4s, 6 Workers | 20s | Introduces sub-group spawning |
| 4 | 10 Workers + 4 Scouts (interleaved) | 20s | Scout intro |
| 5 | Mini-Queen (HP 400) + 8 Workers | 30s (boss) | Boss + summon-on-death |

Plan 2's "win" state triggers after wave 5 completes (placeholder; Plan 3 extends the schedule to wave 25).

---

## 3. Sized changes from Plan 1

### 3.1 Native canvas size — 480×270 → 640×360

Plan 1 native is 480×270, with playfield 320×246. Smoke-testing showed it feels cramped. Plan 2 bumps the native canvas to **640×360** (round 16:9 numbers, "qHD", scales cleanly to 1280×720 on screen at 2× via existing `ctx.scale(2,2)` setup).

| Region | Plan 1 | Plan 2 |
|---|---|---|
| Native canvas | 480×270 | 640×360 |
| Backing canvas | 960×540 | 1280×720 |
| HUD bar | top 24 px | top 24 px (unchanged) |
| Shop panel | right 160 px wide | right 160 px wide (unchanged) |
| Playfield | 320×246 | 480×336 |
| Cake position | (264, 56) | (352, 76) — proportional scale, fine-tune during impl if visually off |
| Anthill position | (16, 135) | (22, 184) — proportional scale, fine-tune during impl if visually off |

**Cannon ranges and ant speeds stay flat** — they are NOT scaled up proportionally. Same numbers (110–200 px range, 12–60 px/s speed) on a bigger playfield means range covers a smaller fraction of the field, ants take longer to cross, and placement becomes more strategic. This is the intended fix for the cramped feeling — not a bigger canvas of identical gameplay.

Sprites stay the same physical size (16×16 ants, 32×32 cannons). They occupy a smaller portion of the screen, which reads correctly for the top-down picnic-blanket framing.

### 3.2 New ant behaviors (9 of 10 types)

Carrier and Worker already exist from Plan 1. Plan 2 adds 7 new types plus the mini-Splitter spawn:

| Ant | HP | Speed | Gold | New behavior summary |
|---|---|---|---|---|
| Worker | 10 | 30 | 2 | *unchanged from Plan 1* |
| Scout | 6 | 60 | 3 | Fast, low HP, alignment 0.4 (very erratic) |
| Soldier | 40 | 20 | 5 | Takes 50% damage from non-pierce sources; 50% slow resistance (multiplies incoming `slow.magnitude` by 0.5) |
| Carrier | 15 | 30 / 45 fleeing | 4 (8 mid-flee) | *unchanged from Plan 1, but joins the new ANT_KINDS table* |
| Tank | 150 | 12 | 10 | Bigger sprite (24×24), 50% damage reduction unless projectile has `pierce: true` flag |
| Splitter | 25 | 25 | 4 | `onDeath`: spawns 2 mini-Splitters at death position with small offsets |
| Mini-Splitter | 8 | 35 | 1 | 8×8 sprite, no further split, dropped by Splitter death only |
| Phase | 20 | 35 | 6 | Every 3s becomes intangible for 1s: untargetable by cannons, projectiles pass through, sprite renders at 30% alpha. Phase shimmer FX on transition. |
| Bomber | 30 | 40 | 7 | `onDeath`: AoE-disable. Cannons within 60 px get `disabledUntil = gameTime + 5` (5 seconds of game time). Disabled cannons render with sparks overlay and skip targeting / firing. |
| Queen | 800 (mini at 400 for wave 5) | 15 | 50 | `onDeath`: spawns 6 Workers in a small radius around death position. |

**King is explicitly deferred to Plan 3.** Reasoning: King's threshold-summon mechanic only stress-tests if it actually runs, and King doesn't appear before wave 25. Coding it in Plan 2 without a wave to deploy it on means the threshold-summon code is untested until Plan 3 anyway.

Each ant type gets a procedural placeholder sprite — distinct silhouette + color, not pretty, just identifiable. Pixel-art PNGs come in Plan 4. Roughly 10–15 lines of canvas drawing per type.

### 3.3 Architecture — kind-table pattern

All four lookup tables live as object/array literals at the top of the IIFE:

- `CANNON_KINDS` — keyed by kind string (`'howitzer'`, `'siege'`, ...), value has `name, cost, damage, fireRate, range, special, upgradesTo, baseFamily`.
- `ANT_KINDS` — keyed by kind string, value has `hp, speed, gold, alignmentRate, sprite, behaviors`.
- `STATUS_KINDS` — keyed by effect kind, value has hooks: `applySpeed(ant, magnitude)`, `tickDamage(ant, dt)`, `onApply(ant, magnitude)`.
- `WAVES` — array of `{number, prep, spawns: [{type, count, interval, delay}, ...], boss?: true}`.

**Per-entity `kind` is the single source of truth** — render, update, and balance numbers all read from the kind table at draw/tick time. Means upgrading a cannon is literally `cannon.kind = 'siege'` and the next frame is correct. Position, current target, fire cooldown, and kill count are preserved across an upgrade.

This pattern is what makes Plan 3 a content fill: adding the 39 remaining cannons is 39 new rows in `CANNON_KINDS` plus their procedural sprites; adding waves 6–25 is 20 new entries in `WAVES`.

### 3.4 File size projection

Pattern stays single-file (`patterns/demo-ant-buster.php`). Currently 903 lines; expected to grow to **roughly 1800–2200 lines** in Plan 2 — comparable to the 1600-line Pokédex demo, and within the established single-file pattern. If the file approaches 2500 lines, that's a signal to revisit whether helper modules under `patterns/components/` (or similar) are worth introducing for Plan 3, but no extraction is planned for Plan 2.

### 3.5 Render z-order

Single canonical pass per frame, back-to-front. Each item is its own clearly-named draw call:

1. Background (grass + blanket tiles + cake plate)
2. Cake (current damage state)
3. Anthill
4. Fallen slices (so they read as on the ground, behind the action)
5. Range circle (only when a tower is selected; drawn under everything moving so it doesn't obscure ants/projectiles passing through)
6. Cannon bases (static under the rotating turret)
7. Ants (sorted by y for top-down depth feel; carrying ants render their crumb sprite as a sub-step at this layer)
8. Cannon turrets + muzzle flashes
9. Projectiles + beams (Glacier beam line is drawn here, not as an effect)
10. Effects (hit sparks, splat fades, Bomber AoE flash, Phase shimmer, status-effect indicators)
11. HUD bar (24 px top strip)
12. Shop / tower-detail panel (right column)
13. Overlays (wave intro banner, pause overlay, win/lose screen) — always last

The range circle on layer 5 (under ants) is the deliberate call from the peer review — it stays legible without obscuring the moving entities the player is reading.

---

## 4. Out of scope (deferred to Plan 3)

Explicit non-goals for Plan 2. Each of these has a defensible reason for not landing now:

| Item | Why deferred |
|---|---|
| King ant + threshold-summon mechanic | Only stress-tests if it runs; doesn't appear before wave 25 |
| Waves 6–25 | Plan 3 = content fill |
| Endless-mode procedural generator | Has no entry point until title screen + save state exist |
| 39 remaining cannons (god tier + other paths) | Plan 3 = content fill; the system can already render them |
| Title screen | All buttons (Endless, How-to-Play, badges) point at Plan 3 features |
| Save state (localStorage) | Nothing meaningful to save until campaign completion + endless wave records exist |
| Sound prefs / sound toggle | No sound in Plan 2 (no SFX, no music) |
| Tutorial popover | Not a system; trivial to add when the upgrade tree lands |
| God-tier wave-15 unlock UI | No god cannons to unlock; button is hidden in Plan 2 |
| Real PNG sprite art | Plan 4 |
| Wave-intro / victory / lose sound cues | No audio system |
| Quit-to-Title in pause overlay | No title screen yet |

---

## 5. Project conventions to enforce

Carrying forward from Plan 1's "Workflow notes for Plan 2" (status doc §Workflow notes):

- **No `&&` or `||` in inline `<script>` JS.** WordPress's content filter encodes `&&` to `&#038;&#038;` inconsistently. Use nested `if` blocks, helper booleans, or `indexOf` checks. Verify after each commit by `curl`-ing the served page and grepping the script tags.
- `var` over `let`/`const`.
- Single-file pattern (no extracting JS to separate files).
- No em-dashes in user-facing copy.
- PHP CLI for syntax checks: `c:/wamp64/bin/php/php8.2.29/php.exe -l <file>`.
- Bump theme `Version:` in `style.css` whenever a new pattern or template is added or renamed (none expected in Plan 2 — same pattern file, no new templates).

---

## 6. Acceptance criteria (Plan 2 done = all of these)

A test plan to verify Plan 2 ships correctly:

1. **Canvas sized correctly.** Demo loads at native 640×360 (1280×720 backing). HUD, playfield, and shop visually proportional.
2. **All 9 stub cannons buildable.** Each appears in shop (3 cards visible at base level). Each base cannon can be placed and upgraded through the full path to its final tier. Upgrade button greys out at correct gold thresholds. Sell refunds 70% correctly across upgrade history.
3. **All 9 ant types render and behave.** Worker, Scout, Soldier, Carrier (with steal-and-deliver), Tank, Splitter (splits on death), Mini-Splitter, Phase (intangibility window), Bomber (AoE-disables cannons), Queen (spawns 6 Workers on death). Each is visually distinguishable from the others.
4. **Status framework works end-to-end with `slow`.** Frost applies 30% slow on hit, lingers 2s. Glacier holds 50% slow while beam fires + 1s linger. Effect ends and ant returns to full speed. Soldier resists slow (sees 15% effective slow from a 30% application). Refresh rule respected when both Frost and Glacier hit the same ant.
5. **Status framework respects all 6 kinds in code path.** Inspecting `STATUS_KINDS` shows all 6 have their hook functions defined; manual injection (via debug or test) of `freeze`, `burn`, `poison`, `stun`, `knockback` produces the documented behavior. (No cannons trigger them in Plan 2.)
6. **Selected-tower panel.** Click any cannon → panel replaces shop with stats + upgrade options. Range circle visible. `Esc` or click-outside closes.
7. **Pause works.** `Space` or pause button freezes the game. Resume continues. Restart Run resets to wave 1 from prep.
8. **Speed toggle works.** `2` or speed icon doubles ant + projectile speed. Prep timer and UI animations unaffected.
9. **All 5 waves complete.** Run from wave 1 → wave 5, win screen shows on wave 5 completion. Send-Next-Wave gives correct gold bonus. Boss banner flashes on wave 5.
10. **Fallen slices.** Killing a Carrier mid-flee drops a slice on the ground. Another ant (any kind) within 10 px picks it up. Slice times out and snaps back to cake at 8s if untouched.
11. **Soft repulsion.** Ants steering toward cake visibly flow around placed cannons without getting stuck. Dropping a cannon on a moving ant shoves the ant out, no damage.
12. **Plan 1 nits resolved.** No unused constants. Carriers delivering to anthill vanish without a splat. Killed ants always splat (including those killed adjacent to anthill or cake).
13. **Debug hotkey gated.** `T` does nothing when accessed without `?debug=1`. With `?debug=1` in URL, `T` cycles through ant types and spawns one at the anthill.
14. **No `&#038;&#038;` in served HTML.** `curl` of the page shows clean `&&`-free script content (or the equivalent `if`-block transformations).

---

## 7. Decision log

Quick-reference table of every Plan 2 decision and the question it resolves.

| Decision | Choice |
|---|---|
| Plan 2 scope | Systems + minimum content (vs. one-shot full content) |
| Stub cannon path through Family 3 | Twin Gun → Frost → Glacier (status-effect path, exercises slow framework) |
| Tutorial | Defer to Plan 3 |
| Status framework breadth | Full 6-effect framework now, only `slow` actively emitted |
| Title screen + save state + sound toggle | All deferred to Plan 3 |
| God-tier wave-15 unlock UI | Hidden in Plan 2 (no god cannons exist yet) |
| Wave coverage strategy | Spec-canonical waves 1–5 (option C) + debug `T` hotkey for type-coverage testing |
| King in Plan 2 | Deferred to Plan 3 (ships with wave 25) |
| Native canvas size | 640×360 (vs Plan 1's 480×270) |
| Range / speed scaling | Stays flat — bigger playfield, same numbers, more strategic placement |
| Shop panel width | Stays at 160 px |
| Debug `T` hotkey | Gated behind `?debug=1` query param |

---

## 8. Implementation tasks (rough sketch — to be expanded by writing-plans)

This is a working sketch for the implementation plan, not the plan itself. Final ordering and grouping handled by the writing-plans skill.

1. Bump native canvas to 640×360, reposition cake / anthill / playfield bounds
2. Introduce `CANNON_KINDS` table; refactor existing Howitzer to read from it
3. Introduce `ANT_KINDS` table; refactor existing Worker + Carrier to read from it
4. Introduce `STATUS_KINDS` table + per-ant `effects` slot + tick handler (all 6 kinds)
5. Introduce `WAVES` array; refactor existing wave 1 to be `WAVES[0]`
6. Add waves 2–5 to schedule
7. Wave intro banner + prep timer + Send-Next-Wave button
8. Pause overlay + speed toggle + keyboard shortcuts
9. Selected-tower panel UI + click-cannon-to-select + click-outside-to-close
10. Upgrade buttons + sell button (per UI-2, UI-3)
11. Add 8 new cannon kinds (Siege, Railgun, Auto-Cannon, Vulcan, Twin Gun, Frost, Glacier — Howitzer already exists; Twin Gun is a new base, Vulcan adds spool-up, Glacier is the first beam cannon)
12. Add Scout ant (extends existing pattern)
13. Add Soldier (armor + slow resistance — exercises status framework)
14. Add Tank (pierce-or-50% reduction — exercises damage pipeline)
15. Add Splitter + Mini-Splitter (`onDeath` spawn hook)
16. Add Phase (intangibility window — exercises targeting + projectile collision)
17. Add Bomber (AoE-disable cannons — exercises cannon disable state)
18. Add Queen (mini at HP 400 for wave 5; `onDeath` spawn hook reused from Splitter)
19. Fallen-slice entity + Carrier-drop-on-kill rewrite + ground pickup
20. Soft tower repulsion in ant steering math
21. Plan 1 nits: drop unused `PLAYFIELD_H`, fix splat-on-deliver, clear `ui.dragging` in `resetGame`
22. Debug `T` hotkey behind `?debug=1`
23. Final smoke test against §6 acceptance criteria + grep for `&#038;&#038;`

Subagent-driven-development pattern from Plan 1 carries forward: one implementer per task, combined spec + code-quality reviewer for non-trivial tasks. Trivial single-line tasks reviewed inline.

---

**End of Plan 2 design spec.**
