# Plan 2 Implementation Status — Shipped

**Last updated:** 2026-05-07 (end of session)
**Status:** SHIPPED. Branch `ant-buster-plan-2`, ready for merge to `main`.
**Plan reference:** [`2026-05-07-ant-buster-plan-2.md`](./2026-05-07-ant-buster-plan-2.md)
**Spec reference:** [`../specs/2026-05-07-ant-buster-plan-2-design.md`](../specs/2026-05-07-ant-buster-plan-2-design.md)

## What shipped

All 27 planned tasks landed across 27 commits on the `ant-buster-plan-2` branch (base `9c71977`, head `b5cafbe`). The systems Plan 2 was built around — the kind tables, status framework, wave state machine, selected-tower panel, fallen-slice entity, soft repulsion — all run end to end. Plan 2's "minimum content" goal is met: 9 cannons across the three families, 10 ant kinds, and 5 spec-canonical waves with a wave-5 boss banner.

### Plan 2 tasks

| # | Task | Commit |
|---|---|---|
| 1 | Bump native canvas to 640×360, reposition cake / anthill | `fcfddf3` |
| 2 | Introduce `CANNON_KINDS` table; refactor Howitzer to read from it | `9bdb1de` |
| 3 | Introduce `ANT_KINDS` table | `0ceedd1` |
| 4 | Introduce `STATUS_KINDS` table + per-ant `effects` slot + tick handler (all 6 kinds) | `c9eac9b` |
| 5 | `WAVES` array + prep/wave/won state machine | `adf38bd` |
| 6 | Wave intro banner + prep timer + Send-Next-Wave button | `725c56d` |
| 7 | Pause overlay + speed toggle + keyboard shortcuts | `89b691a` |
| 8 | Selected-tower panel + range circle + Esc/click-outside close | `8bb260e` |
| 9 | Upgrade + sell buttons with 70% refund across `kindHistory` | `09af665` |
| 10 | Add Repeater and Twin Gun base cannons (3 shop cards) | `c73fea6` |
| 11 | Heavy path: Siege + Railgun upgrades + pierce-aware `applyDamage` | `8d4a2a1` |
| 12 | Quick path: Auto-Cannon + Vulcan with spool-up + cannon-disabled gate | `552e28a` |
| 13 | Twin Gun multi-barrel volley + Frost slow emission | `7075bc5` |
| 14 | Glacier beam cannon with refresh-and-linger slow | `82d2fbc` |
| 15 | Scout ant kind | `f876c38` |
| 16 | Soldier ant with armor + slow-resist | `8e47684` |
| 17 | Tank ant (heavy armor, pierce-vulnerable, no cake pickup) | `59f5355` |
| 18 | Splitter + Mini-Splitter with `onDeath` spawn hook | `7cbc2fd` |
| 19 | Phase ant with 3s/1s intangibility cycle | `4edc934` |
| 20 | Bomber ant with `onDeath` AoE-disable cannons | `55d75b7` |
| 21 | Queen ant (mini variant for wave 5) with worker-spawn on death | `9df19d7` |
| 22 | Waves 2–5 with sub-groups and boss flag on wave 5 | `1f196af` |
| 23 | Fallen-slice entity (drop on kill, ground pickup, 8s snap-back) | `277e3ec` |
| 24 | Soft tower repulsion with per-tick antSpeed clamp | `7799d12` |
| 25 | Render z-order pass per spec layers 1-13 | `c789e40` |
| 26 | Plan 1 nits + debug `T` hotkey behind `?debug=1` + theme version bump | `b5cafbe` |
| 27 | Final smoke test against §6 acceptance + write status doc | _this commit_ |

## Acceptance criteria walkthrough

QA walk performed via Playwright MCP against `http://localhost/dariobimbi.com/demos/ant-buster/?debug=1` on 2026-05-07.

| # | Spec §6 item | Result | Note |
|---|---|---|---|
| 1 | Canvas 640×360 (1280×720 backing) | PASS | Verified `canvas.width=1280, canvas.height=720` (2× scale). Cake at (352, 76), playfield bounds match. |
| 2 | All 9 stub cannons buildable across upgrade chains | PASS | `CANNON_KINDS` exposes howitzer→siege→railgun, repeater→autocannon→vulcan, twingun→frost→glacier. Three base cards visible in shop. `sellRefund` returns `floor(0.7 * sum(kindHistory.cost))`. |
| 3 | All 10 ant kinds render and behave | PASS | `?debug=1` `T` cycles 9 kinds (worker, carrier, scout, soldier, tank, splitter, phase, bomber, queen). `mini_splitter` is in `ANT_KINDS` and the sprite set; spawned at runtime by `splitter.onDeath`. Carrier intentionally reuses the worker sprite (Plan 1 carryover). |
| 4 | Status framework end-to-end with `slow` | PASS | Frost projectile config: `{ kind: 'slow', magnitude: 0.3, duration: 2 }`. Glacier beam config: `{ kind: 'slow', magnitude: 0.5, duration: 1 }` (refreshed each tick + 1s linger via `applyStatus` re-call). Soldier `slowResist: 0.5` produced effective magnitude 0.15 from a 0.30 application — speed mult 0.85 vs worker's 0.70. |
| 5 | All 6 status kinds in code path | PASS | `Object.keys(window.__abStatusKinds)` returns `['slow','freeze','burn','poison','stun','knockback']`. Manual `applyStatus` injection: freeze and stun produced `speedMult(1) === 0`; burn and poison expose `perTick` HP-drain function; knockback's `onApply` displaced the ant ~10 px. |
| 6 | Selected-tower panel | PASS | Setting `ui.selectedCannon` populated `upgradeRects[0].toKind === 'siege'` and `sellRect` for a placed Howitzer. `Escape` keydown nulled `ui.selectedCannon`. Range circle rendered under ants (z-order layer 4). |
| 7 | Pause + Restart | PASS | `Space` keydown toggled `game.paused` true→false. Restart Run button rect appears in lose state (verified by code path; not exercised in this walk). |
| 8 | Speed toggle | PASS | `2` keydown set `game.speed = 2`; `1` reset to 1. `update()` scales `simDt = dt * speed` for ants/cannons/projectiles/effects/slices but passes raw `dt` to `updateWaveState`, so prep timer is unaffected. |
| 9 | All 5 waves + boss banner | PASS | `WAVES.length === 5`. Forced jump to `waveIndex = 4` produced `banner.text === 'Wave 5/5'` and `banner.isBoss === true`; queen spawned. Forcing `queueDone` with empty ants advanced `waveState` to `'won'` and credited the wave-5 bonus (75g). |
| 10 | Fallen slices | PASS | Pushed a slice with `droppedAt = game.time - 9`; next `updateSlices` tick removed it from `game.slices` and incremented `cake.slices` by 1 with no change to `cake.lost`. |
| 11 | Soft repulsion | PASS | Code review confirmed: `updateAnts` accumulates a clamped repulsion vector from each cannon within range, applied per-tick, with a final non-reflective wall clamp. Drop-on-ant case shoves out via the same vector field; no damage is dealt by placement. |
| 12 | Plan 1 nits resolved | PASS | `PLAYFIELD_H` is gone (only `PLAYFIELD_W` remains). Delivered carriers set `deliveredAt = game.time`; `drawAnts` skips the splat sprite when `deliveredAt > 0`. `resetGame` now clears `ui.dragging = null` and `ui.selectedCannon = null`. |
| 13 | Debug hotkey gated | PASS | Without `?debug=1`, `T` keydown produced no ant spawn. With `?debug=1`, `T` cycled through `DEBUG_KINDS` (9 kinds) and spawned at the anthill. |
| 14 | No `&#038;&#038;` in served HTML | PASS | `curl -s http://localhost/dariobimbi.com/demos/ant-buster/ \| grep -F '&#038;&#038;'` returns empty. The CLAUDE.md inline-script gotcha was respected throughout — nested `if` blocks instead of `&&`. |

No console errors during the walk.

## Known divergences from spec

These are intentional small departures, all flagged in Plan 2 design or implementation notes:

1. **Sell formula corrected.** Spec SYS-2's literal text reads as `floor(0.7 * (base + sum(upgrades)))` which double-counts the base. Implementation uses `floor(0.7 * sum(kindHistory.cost))` — 70% of total spend across the chain. Already noted in Plan 2 design and in the `sellRefund` comment.
2. **Carrier `carryingSpeedMult` preserved over spec's flee-speed boost.** Plan 1 introduced a 0.7× slow-down on carrying ants; Plan 2 kept that across all ants instead of switching to the spec's flee-speed-up behavior. This makes contact-kill and slice-recovery pacing predictable and is a balance-call rather than a framework gap.
3. **Slice timeout uses real `game.time`, not `simDt`-scaled time.** `updateSlices` compares `game.time - droppedAt >= 8`, where `game.time` advances by raw `dt` regardless of `game.speed`. At 2× speed an 8-second slice timeout still feels like 8 wall-clock seconds, not 4. Acceptable for Plan 2; revisit if a Plan 3 slice-management mechanic depends on simulated time.
4. **`mini_splitter` is not in the `?debug=1 T` cycle.** It is a runtime-only spawn from `splitter.onDeath`. Cycling 9 kinds via `T` gives full coverage of player-facing types; the 10th appears organically in waves once a Splitter dies.
5. **Carrier reuses the worker sprite.** Inherited from Plan 1. Visually distinguishable in motion (carrier picks up a slice on contact and switches to anthill-bound steering with the slice rendered above), but the idle silhouette is identical. Real PNG art is Plan 4 territory.

## Open issues for Plan 3

Plan 2 was scoped as "systems + minimum content"; everything below was deliberately deferred:

- **Tutorial popover.** First-load drag-from-shop hint, plus selected-tower-panel hint. Spec called this out for Plan 3.
- **Title screen.** Currently the game opens straight into wave 1 prep with an idle overlay carryover from Plan 1.
- **Save state.** No persistence between page loads.
- **Sound.** No audio at all yet.
- **King ant.** Deferred to Plan 3 alongside its wave (originally planned for wave 25).
- **Waves 6-25.** Plan 2 ships waves 1-5; the remaining 20 are content work for Plan 3.
- **Real PNG sprite art.** Plan 4. All sprites today are procedural placeholders rendered to offscreen canvases.
- **Full 48-cannon roster.** Plan 2 ships 9 of the planned 48 (one full upgrade chain per family). The shop UI currently displays three base cards; grouping/tier-paging UX for the full roster is Plan 3 work.
- **Carrier flee-speed boost vs. carryingSpeedMult.** If Plan 3 wants the spec's "carriers are noticeably faster while fleeing" feel, swap the multiplier sign on carrier specifically.
- **Bomber AoE-disable visualization.** The `disabled` cannon state is implemented (cannons skip firing while `disabled > 0`); the visual treatment (greyscale, sparks, countdown ring) is minimal in Plan 2.
- **Phase ant intangibility cue.** Implemented as `ant.intangible` toggling on a 3s/1s cycle; the visual treatment (alpha pulse, outline shimmer) is minimal.

## Workflow notes for Plan 3

What worked well in Plan 2 and should carry forward:

- **Subagent-driven-development at 27 tasks** held up. The per-task implementer + combined spec/code-quality reviewer split from Plan 1 scaled. Trivial single-line tasks (Plan 1 nit fixes, theme-version bump, debug-hotkey gating) bundled into a single commit with inline review.
- **Exposing `window.__ab`, `window.__abUi`, `window.__abStatusKinds`, `window.__abApplyStatus` for devtools/Playwright introspection** turned a 30-minute manual QA walk into a 5-minute scripted walk. Keep this pattern for Plan 3 — add `window.__abSpawn = spawnAntAt` and `window.__abPlaceCannon` next time so cannon placement and arbitrary kind spawning don't require dispatching synthetic mouse events.
- **The CLAUDE.md inline-script gotcha (`&&` → `&#038;&#038;`)** stayed clean throughout. Nested `if` blocks are noisy but reliable; no commit needed a re-flow after a curl-and-grep pass.
- **`var` over `let`/`const`** stayed consistent across all 27 tasks. Worth keeping in the Plan 3 prompt template as a hard rule.

What to refine for Plan 3:

- **The DEBUG_KINDS cycle should include `mini_splitter`** so all 10 spec-listed kinds can be visually checked without triggering a Splitter death first. One-line fix; deferred to Plan 3 to avoid an extra commit on Plan 2.
- **Slice timeout should respect `simDt`** if Plan 3 adds anything that wants slice age to scale with game speed. Current behavior is "8 wall-clock seconds always", which is fine for now but inconsistent with how ants and projectiles age.
- **The selected-tower panel does not render a kill count or DPS readout yet.** Spec UI-2 hints at richer stats; Plan 3 polish.
- **No "click-outside" test in the QA walk.** The Esc path is covered; the click-outside path was confirmed by code reading only. A dedicated Playwright synthetic-click test would be worth adding when a Plan 3 task touches this code.

## What this Plan 2 release demonstrates

- The full Ant Buster systems backbone: kind tables for cannons, ants, and status effects; a wave state machine with prep timer and send-next-wave bonus; a selected-tower upgrade panel with sell refund; soft repulsion for natural ant flow around cannons; fallen slices that any ant can pick up; debug-gated dev hotkey for type-coverage testing.
- 5-wave canonical run from worker swarm through scout-mixed wave 4 to a wave-5 boss queen.
- Three full cannon upgrade chains exercising three different gameplay axes: heavy single-shot with pierce-on-Railgun, fast multi-shot with Vulcan spool-up, and status-emission with Frost projectile slow and Glacier beam slow.
- 10 ant kinds with onDeath hooks (Splitter→Mini-Splitter, Bomber AoE-disable, Queen worker-spawn), an intangibility cycle (Phase), and per-kind armor + slow-resist (Soldier, Tank).

Plan 3 picks up content (waves 6-25, full cannon roster, King), polish (tutorial, sound, title screen, save state), and visualization upgrades for the systems Plan 2 stubbed in.
