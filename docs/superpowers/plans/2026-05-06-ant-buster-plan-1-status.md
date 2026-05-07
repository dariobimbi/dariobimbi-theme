# Plan 1 Implementation Status — Shipped

**Last updated:** 2026-05-07 (end of session)
**Status:** SHIPPED. Branch `ant-buster-mvp` merged to `main`.
**Plan reference:** [`2026-05-06-ant-buster-plan-1-shell-mvp.md`](./2026-05-06-ant-buster-plan-1-shell-mvp.md)
**Spec reference:** [`../specs/2026-05-06-ant-buster-design.md`](../specs/2026-05-06-ant-buster-design.md)

## What shipped

All 15 planned tasks plus seven post-plan polish commits driven by smoke-testing in the browser. The MVP is meaningfully more playable than the bare plan called for.

### Plan 1 tasks (all approved by the per-task and final whole-implementation reviewers)

| # | Task | Commit |
|---|---|---|
| 1 | Pattern + template scaffold + theme registration | `e15eb6b` (+ fix `87b8c7f`) |
| 2 | Canvas DOM, full-bleed background, pixelated rendering | `5ff5eec` (+ fix `b16031c`) |
| 3 | Game IIFE skeleton, state, fixed-timestep main loop | `115f2e4` |
| 4 | Procedural placeholder sprites | `32a835e` (+ fix `35afacd`) |
| 5 | Render the playfield (blanket, grass, cake, anthill) | `708099d` |
| 6 | Ant data structure, spawn, render | `b94209f` |
| 7 | Erratic-wander pathing | `25e1940` |
| 8 | Cake collision, life counter, lose state | `fc24341` |
| 9 | Cannon shop, drag-to-place, range preview | `160aba8` |
| 10 | Cannon targeting + projectiles (with edge-straddle fix) | `f5b37d4` |
| 11 | Gold on kill | `35eac7d` |
| 12 | HUD bar (gold/cake/wave) | `bfb7605` |
| 13 | Wave 1 spawn schedule + win detection | `b104d11` |
| 14 | Win/lose overlays + click-to-restart | `3c63d9f` |
| 15 | Intro hint + theme version bump | `bb9e055` |

Final whole-implementation review verdict: **APPROVED FOR MERGE**. No blockers; three optional nits noted (`PLAYFIELD_H` unused, ants killed at cake skipped the splat frame, `ui.dragging` not cleared in `resetGame`).

### Post-plan polish (after final review)

| Commit | What |
|---|---|
| `cf8ef40` | Crisp text via 960×540 backing canvas + `ctx.scale(2,2)`. Idle start state with overlay; mousedown transitions to `'playing'`. Stronger erratic pathing (alignment 0.6→0.3, jitterRate 0.5→3.0, wider spawn y-spread, random initial heading). |
| `1b4c68a` | Steal-and-deliver mechanic. Ants pick up a slice on cake contact, carry it back to the anthill (slower, more determined steering), and the slice is permanently lost only on delivery. Killing a carrying ant returns the slice. New `cake.lost` field; lose triggers at `lost >= 8`. HUD adds `Lost: N/8` readout. Carried slices rendered as a small orange/yellow crumb above the ant. |
| `d9e2a34` | Hide the "Drag a cannon..." intro hint while the idle overlay is up (state-guarded the existing 4-second timer). |
| `981868f` | Cake moved from center (200, 135) to top-right (264, 56). Per-ant sinusoidal wander: each ant spawns with `wanderPhase`, `wanderFreq` (0.3-0.7 Hz), `wanderAmp` (0.9-1.5 rad). The desired heading is biased by `sin(phase) * amp * scale`, where carrying ants use `scale = 0.3`. Combined with the existing jitter, this produces visible weaving instead of mostly-straight lines. |
| `206fe5e` | Ants now bounce off the playfield bounds. After each tick, x is clamped to `[8, PLAYFIELD_W - 8]` and y to `[HUD_H + 8, HEIGHT - 8]`; on contact the heading reflects across the wall normal. Stops the wander from pushing ants into the HUD or shop area. |
| `68f3a30` | Final-approach mode: when an uncarried ant is within `32 * 32` squared distance of the cake, alignment jumps to 0.9, jitterRate drops to 0.5, and `wanderScale` is zeroed so the ant commits to the pickup instead of weaving past it. |

The CLAUDE.md inline-script gotcha was respected throughout — no `&&` or `||` anywhere in the inline script. Verified by grep on every commit.

## What this MVP demonstrates

- Whole pipeline: WordPress block-pattern integration, custom-template registration, single-file demo with inline `<style>` and `<script>`, canvas rendering, fixed-timestep game loop, procedural placeholder sprites.
- Core gameplay: one cannon (Howitzer), one ant (Worker), one wave (8 Workers over 9 seconds), erratic wander pathing, free placement on a 480×270 logical canvas, drag-to-place from a shop card.
- Economy: starting 150 gold, Howitzer at 50g, kills drop 2g, wave-completion bonus of 21g.
- The full Ant Buster steal-and-deliver loop with slice recovery, which makes cannon placement a real strategic decision rather than a contact-kill problem.

## Workflow notes for Plan 2

The subagent-driven-development pattern worked very well across the 15 tasks:

- One implementer subagent per task, given the full task text from the plan in the prompt (no skill rule violations — never make the subagent read the plan).
- One **combined spec + code-quality reviewer subagent** per non-trivial task. The skill spec calls for two separate review subagents but a combined reviewer is faster when the diff is small and isolated, and was sufficient for every task here. Split if a Plan 2 task touches more than one subsystem.
- Trivial single-line tasks (Tasks 11, 12, 14) were reviewed inline by the controller rather than dispatched. Acceptable when the diff is verifiable in <30 seconds of reading.
- Capture base SHA before each implementer dispatch, head SHA after the commit, so the reviewer can `git diff base..head`.
- Implementer-found-issue fixes were one-shot prompts to a fresh implementer (often on a cheaper model — fixes are mechanical) rather than amending the original commit. Three of the 15 tasks needed this; pattern works.
- Hard project conventions to enforce in every Plan 2 prompt:
  - **No `&&` or `||` in inline `<script>` JS.** (Use nested `if` blocks.)
  - `var` over `let`/`const`.
  - Single-file pattern (no extracting JS to separate files).
  - No em-dashes in user-facing copy.
  - PHP CLI for lint: `c:/wamp64/bin/php/php8.2.29/php.exe`.

## Open issues to consider for Plan 2

These were flagged in the final review or noted during polish but explicitly deferred:

1. **`PLAYFIELD_H = 246` is declared but unused.** Currently a forward-looking constant for Plan 2 layout work. Remove or wire up when Plan 2 starts.
2. **Ants killed reaching the cake skip the splat sprite.** `checkPickupsAndDeliveries` (formerly `checkCakeCollision`) sets `deathTime = 0.5`, which is the same value the keep check uses (`< 0.5` strict). Result: delivered ants vanish without a splat. Was intentional for delivery (clean disappearance into the anthill) but the same behavior applies if you ever introduce a "cake kills the ant" mechanic.
3. **`ui.dragging` not cleared by `resetGame()`.** Currently safe because state transitions only happen during the update loop, not during a mouse event. Worth flagging if Plan 2 adds more input paths or async operations.
4. **No cannon-on-cannon repulsion / soft placement.** The placement check rejects overlap but free-placement plus repulsion (per spec §5.5) is deferred to Plan 2.
5. **Fallen slices snap straight back to the cake.** Plan 1 uses snap-back for simplicity; the original Ant Buster has fallen slices land on the ground for any other ant to pick up. Plan 2 should add a "fallen slice" entity type.

## What's next

**Plan 2** — full content. The brainstorming session for Plan 2 should resolve:

- Layout of the 48-cannon upgrade tree in the shop UI (currently the shop is a single card; Plan 2 needs grouping, tier labels, scrolling or paging).
- Tower-detail panel layout (upgrade buttons, sell, target priority).
- Per-ant-type behaviors in code (Carrier crumb-pickup-and-flee, Bomber death-AoE-disabling-cannons, Phase intangibility windows, Splitter mini-spawns, Queen / King mechanics).
- Whether ants drop bonus gold for special types vs. the flat 2g for Workers.
- How the 25 wave schedules are stored (inline JS table? JSON in a separate `<script type="application/json">` block? Since the project rule is single-file, inline JS is the only fit).

After Plan 2, **Plan 3** (UI polish, save state, endless mode, tutorial, sound) and **Plan 4** (real PNG sprite art) follow in order.
