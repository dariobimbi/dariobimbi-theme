# Plan 7 Implementation Status — Shipped

**Last updated:** 2026-05-08 (end of session)
**Status:** SHIPPED. Branch `ant-buster-plan-7`, 7 commits (6 feature/hygiene + this status doc), ready for `--no-ff` merge to `main`.
**Plan reference:** [`2026-05-08-ant-buster-plan-7.md`](./2026-05-08-ant-buster-plan-7.md)
**Spec reference:** [`../specs/2026-05-08-ant-buster-plan-7-design.md`](../specs/2026-05-08-ant-buster-plan-7-design.md)

## What shipped

Plan 7 — The Commander's Console — adds a per-tower targeting priority system. Five modes (Closest, First, Last, Strongest, Weakest) are selectable from a 5-button row in the selected-tower panel. The picked mode tells the cannon's `findTarget` which ant in range to prefer: spatially nearest, nearest to the cake, farthest from the cake, highest HP, or lowest HP. Stealth (revealed by Spotter) remains the apex priority — the mode picks within the revealed pool when one exists, so a Hawkeye-plus-Reaper combo set to Strongest now hunts the toughest revealed Carrier rather than the first one the loop happens to hit.

The targeting row is hidden on aura cannons (Blizzard and any future `def.aura` kind) because they never call `findTarget` — there is no targeting decision to surface. Cone-DoT cannons (Plasma Torch) and beam cannons (Glacier, Lance, Solar Lance) DO call `findTarget` to aim the cone or beam target, so they receive the row normally.

A faint dashed white line and 4 px white reticle confirm the picked target visually whenever a cannon is selected and has a live target. The overlay is mode-agnostic (always white at alpha 0.4 for the line, 0.7 for the reticle) so it does not compete with hit-flash red or the gold-cost UI.

Branch base: `7b77b12` on `main` (Plan 7 plan-doc commit, sitting on top of `aeee89c` Plan 7 spec fixup, on top of `c8d4eb2` Plan 6 merge). Branch head: this commit.

### Plan 7 commits (chronological)

| # | Commit | Subject |
|---|---|---|
| 1 | `b67e66f` | feat(ant-buster): cannon.targetingMode default closest (Plan 7 Task 1) |
| 2 | `2142844` | feat(ant-buster): findTarget filter-then-pick + 5 modes (Plan 7 Task 2) |
| 3 | `6babd5f` | feat(ant-buster): drawTargetingRow + drawTowerPanel integration (Plan 7 Task 3) |
| 4 | `f1d61bb` | feat(ant-buster): targeting button click handler (Plan 7 Task 4) |
| 5 | `6b241f8` | feat(ant-buster): drawTargetingOverlay - target line + reticle (Plan 7 Task 5) |
| 6 | `7d3eb52` | chore(ant-buster): bump theme version to 0.13.0 (Plan 7 hygiene) |
| 7 | _this commit_ | docs(ant-buster): Plan 7 status — shipped |

## Acceptance criteria walkthrough

QA walk performed via Playwright against `http://localhost/dariobimbi.com/demos/ant-buster/?debug=1` on 2026-05-08. Two-stage subagent review (spec compliance + code quality) for every task.

| # | Spec §9 item | Result | Note |
|---|---|---|---|
| 1 | Default behavior unchanged (`targetingMode === 'closest'`) | PASS | Probe verified field exists with `'closest'` default. |
| 2 | Mode survives upgrade | PASS | `upgradeCannon` mutates kind in place; field carries through automatically. Confirmed by setting `'strongest'`, mutating `c.kind`, re-reading. |
| 3 | Closest mode picks nearest | PASS | Probe at three known distances (20/50/100): target = worker (dist 20). |
| 4 | First mode picks cake-closest | PASS | Same setup; target = worker = expected first by cake-distance. |
| 5 | Last mode picks anthill-closest | PASS | Target = tank = expected last by cake-distance. |
| 6 | Strongest mode picks max HP | PASS | Worker(10)/Soldier(40)/Tank(150) mix; target = tank, hp 150. |
| 7 | Weakest mode picks min HP | PASS | Same mix; target = worker, hp 5 (took damage during un-pause). |
| 8 | Stealth override still wins | PASS | Spotter+Howitzer scene; revealed Carrier (hp 10) outranked unrevealed Tank (hp 150) under Strongest mode. |
| 9 | Mode picks within revealed pool | PASS | Two revealed Carriers (10HP / 150HP) under Strongest; target = 150HP Carrier. |
| 10 | Targeting row hidden on aura cannons | PASS | Blizzard selected; `ui.targetingRects.length === 0`. |
| 11 | Cone-DoT heading follows targeting mode | PASS | Plasma Torch with 2 ants; closest mode heading -0.0004 rad (aimed at near ant), first mode heading -1.241 rad (aimed at cake-near ant). |
| 12 | Active mode highlighted | PASS | Screenshot confirms 2 px white border + palette color (blue/gold/grey/red/rose) on the active button; pixel-sample probe across all 5 modes confirms inactive buttons stay at panel-dark `#1a1a28`. |
| 13 | Mode label updates | PASS | Screenshot confirms `Targeting: Closest` label below the row. Code path: `drawTargetingRow` reads `c.targetingMode` directly, so the label always matches. |
| 14 | Line draws when target exists | PASS | Pixel sampling around worker sprite at (340, 200) detected 64 alpha-blended white pixels (reticle + line endpoints) when `c.target` is the worker. |
| 15 | Line gone without target | PASS | Same pixel sample after `c.target = null` returned 0 white pixels. |
| 16 | King in Strongest mode is locked on | DEFERRED | Wave 25 scenario; deferred to user manual playtest per Plan 5/6 precedent. The §3 filter-then-pick logic guarantees the King's 3000 HP outranks any other ant's HP in range; the spec compliance review and the smoke tests on items 6–7 cover the underlying mechanism. |
| 17 | Royal Guard outranks King in First mode | DEFERRED | Same precedent. The First mode reads each ant's distance-to-cake without kind-special-casing; when a Royal Guard's distance to cake is smaller than the King's, First picks the Royal Guard. Behavior is emergent from the spec §2.2 distance-to-cake metric. |
| 18 | PHP lint passes | PASS | `php -l patterns/demo-ant-buster.php` returns "No syntax errors detected" at every task verify step. |
| 19 | Hygiene check passes | PASS | `curl ... | grep -c "&#038;&#038;"` returns 0 against the served HTML at every task. |
| 20 | Theme Version bumped to 0.13.0 | PASS | `style.css` shows `Version: 0.13.0`; `?ver=0.13.0` confirmed twice on the served page. |

**18 of 20 items pass programmatically. 2 (items 16, 17) deferred to user manual playtest per the established Plan 5 / Plan 6 precedent for King-fight scenarios** — those plans likewise verified the King-fight via manual playtest because synthesizing wave 25 programmatically takes longer than playing through it.

## Architectural decisions worth flagging

1. **Distance-to-cake replaced pathProgress entirely.** First/Last modes use squared distance from each candidate to `game.cake.x, game.cake.y`. The original spec brainstorm considered pathProgress with a King sentinel value, but Ant Buster's ants do not follow waypoints — they walk straight to the cake using `angleTowards` plus wander/cannon-avoidance. Distance-to-cake is the meaningful "progress" measure. The King competes with every other ant on actual distance, no special case. Royal Guards outrank the King in First mode when they walk past him; the King outranks them when he is closer. Both behaviors are correct emergent results.

2. **`upgradeCannon` mutates in place — `targetingMode` survives automatically.** No explicit copy needed. The cannon instance gets `c.kind = toKind; c.kindHistory.push(toKind)` and other field updates, but the `targetingMode` field is preserved by virtue of being a separate property. Plan 5 used the same in-place mutation pattern for the Royal Guard work; Plan 7 inherits it.

3. **Aura is the only auto-exclusion.** The spec considered `def.aura || def.coneDot || def.beam` as the exclusion list, but a verification pass found `findTarget(c)` is called inside the cone-DoT and beam paths (line 3811 onward) to aim the cone heading and beam target. Excluding them would break the targeting feature on those cannons. Only `def.aura` cannons (Blizzard and any future radial-effect kind) skip `findTarget` entirely, so they alone get the row hidden.

4. **Stealth filter is the apex priority, not a bypass.** The Plan 4 implementation short-circuited on the first revealed ant in iteration order, which meant Hawkeye+Reaper would pick "whichever revealed Carrier the loop hit first," not "the strongest revealed Carrier." Plan 7's filter-then-pick reduces the candidate pool to revealed-only when any are revealed, then applies the targeting mode within that pool. This is strictly better than the previous behavior — a Hawkeye+Reaper combo set to Strongest now hunts the toughest revealed Carrier.

5. **Neutral-white target overlay.** The line and reticle are always `rgba(255, 255, 255, 0.4)` and `rgba(255, 255, 255, 0.7)` regardless of mode. Coloring by mode would compete with hit-flash red and gold-cost UI; the active button highlight in the panel is the primary mode signal. Tuning hatch §8 lists this as the first place to look if real-game feedback turns out to be insufficient.

6. **Mode glyphs are single bold characters (C, F, L, S, W) at 22×22 px, drawn directly to canvas.** No sprite work. Tuning hatch §8 lists glyph-to-icon swap as a deferred polish item if play testing flags ambiguity.

## Plan 8+ candidates

Carried forward from Plan 4-6 backlogs plus spec §2 out-of-scope log:

- **Status-aware targeting modes** ("Target Frozen first", "Target Un-Slowed only"). Spec §1 explicitly logged this as a Plan 8+ candidate — distinct conceptually from rank-by-property modes (filter by status presence vs rank by inherent value).
- **Title Screen / Save State shell** — deferred from Plan 5 brainstorm.
- **Per-mode target line color** — Plan 7 §8 tuning hatch #2, in case the neutral-white treatment proves insufficient feedback.
- **Mode-glyph-to-icon swap** — Plan 7 §8 tuning hatch #3, in case the C/F/L/S/W letters prove ambiguous.
- **Distinct sprite silhouettes for the 18 god-tier rows** — currently fall back to howitzer sprite; visual identity comes from halo / flash / shake.
- **Hawkeye-vs-Queen rebalance** — flagged in Plan 4 status doc §117.
- **Brittle status combo extensions** — Plan 6 shipped the heavy-family + frozen + 3× damage path; further extensions (e.g., Ricochet on Brittle = double-shatter) are open design space.

## File deltas

- `wp-content/themes/dariobimbi/patterns/demo-ant-buster.php` — net approximately +120 lines:
  - `placeCannon` cannon-instance literal grows by one field (`targetingMode: 'closest'`).
  - `findTarget` rewritten to filter-then-pick (filter pass + stealth pass + pick pass).
  - New `drawTargetingRow(c, px, py)` helper rendering the 5-button row + label, returns the y-coordinate after the label so the upgrade buttons can stack below.
  - `drawTowerPanel` panel height grew 200 → 232 to accommodate the row; upgrade buttons base-Y now follows the row's returned bottom-Y; sell button shifted 200-26 → 232-26.
  - Mousedown handler gained a hit-test loop for `ui.targetingRects` before the upgrade-rects loop.
  - New `drawTargetingOverlay` function (target line + reticle), wired into the render loop after `drawShatterParticles`.
  - `resetGame` clears `ui.targetingRects = []`.
  - `ui` object literal gains `targetingRects: []`.
- `wp-content/themes/dariobimbi/style.css` — `Version: 0.12.0 → 0.13.0`.
- `wp-content/themes/dariobimbi/docs/superpowers/plans/2026-05-08-ant-buster-plan-7-status.md` — this file.
