# Plan 5 Implementation Status — Shipped

**Last updated:** 2026-05-08 (end of session)
**Status:** SHIPPED. Branch `ant-buster-plan-5`, 6 commits (5 feature/hygiene + this status doc), ready for `--no-ff` merge to `main`.
**Plan reference:** [`2026-05-08-ant-buster-plan-5.md`](./2026-05-08-ant-buster-plan-5.md)
**Spec reference:** [`../specs/2026-05-08-ant-buster-plan-5-design.md`](../specs/2026-05-08-ant-buster-plan-5-design.md)

## What shipped

Plan 5 is a targeted rebalance of the King fight introduced by Plan 4's Reaper cannon. Reaper's `kind === 'worker'` instakill trivializes the King's drip-spawn pressure — one Reaper near the spawn corner shuts it down at near-zero fire-rate cost. Plan 5 routes around this by upgrading the drip-spawn to a new `royal_guard` ant kind, which falls through the Reaper's `'worker'` check and pays only the standard 2×/1× HP-conditional multiplier. No Reaper code was changed; the fix is entirely in the ant data and the King's spawn logic.

The Royal Guard slots between Worker (hp 10) and Soldier (hp 40) at hp 20, with `armor 0.2`, `slowResist 0.3`, `canPickup true`, and a distinct royal-blue sprite with gold crown overlay. The spatial decision Reaper players now face — park near the drip-spawn corner for drip suppression at a fire-rate tax, or cover the King's cake-approach lane for more King DPS — is the design intent of spec §3.

Branch base: `b881386` on `main` (Plan 5 plan-doc commit, sitting on top of Plan 4's merge `8b4048b`). Branch head: this commit.

### Plan 5 commits (chronological)

| # | Commit | Subject |
|---|---|---|
| 1 | `8988c37` | feat(ant-buster): royal_guard ANT_KINDS row (Plan 5 Task 1) |
| 2 | `fee9005` | feat(ant-buster): royal_guard sprite + splat factories (Plan 5 Task 2) |
| 3 | `e254b85` | feat(ant-buster): kind-aware splat draw-site (Plan 5 Task 3) |
| 4 | `934bb96` | feat(ant-buster): King drips Royal Guards (Plan 5 Task 4) |
| 5 | `0576afe` | chore(ant-buster): bump theme version to 0.11.0 (Plan 5 hygiene) |
| 6 | _this commit_ | docs(ant-buster): Plan 5 status — shipped |

## Acceptance criteria walkthrough

QA walk performed via Playwright against `http://localhost/dariobimbi.com/demos/ant-buster/?debug=1` on 2026-05-08.

| # | Spec §8 item | Result | Note |
|---|---|---|---|
| 1 | `ANT_KINDS.royal_guard` exists with §4 stats (hp 20, speed 35, slowResist 0.3, armor 0.2, gold 4, alignment 0.3, jitterRate 3.0, canPickup true, plus standard required fields) | PASS | All 11 fields confirmed via Playwright assertion at Task 1 verify. |
| 2 | King's `onSpawn` drip-job spawns `royal_guard`, not `worker` | PASS | Task 4 Playwright assertion: 1 King + 3 Royal Guards + 0 Workers from drip after 12 sim seconds. |
| 3 | `Reaper.damageModifier({kind:'royal_guard'})` returns 2 at full HP, 1 otherwise; no special case in modifier | PASS | Task 1 Playwright probe: `{fullHp:2, damaged:1, worker:999}`. Fall-through works without touching Reaper code. |
| 4 | `royalGuardSplat` exists; dead Royal Guards render in royal blue | PASS | Task 2 sprite-table probe confirmed key present. Visual confirmation deferred to user playtest (approved). |
| 5 | `royal_guard` sprite exists; living Royal Guards render with crown overlay | PASS | Task 2 sprite-table probe confirmed key present. Visual confirmation by user at Task 4 checkpoint (approved). |
| 6 | Royal Guards pick up cake (`canPickup true`) and flee-sprint at 1.5× | PASS | Data confirmed at Task 1. Scenario 2 playtest: 1 Royal Guard observed carrying cake slice with `carryingSpeedMult` applied. |
| 7 | PHP lint passes | PASS | Confirmed at every task verify step. |
| 8 | Hygiene check passes (all four operator-encoding checks return 0) | PASS | Confirmed at every task verify step. |
| 9 | Theme Version bumped to 0.11.0 | PASS | Task 5; `ver=0.11.0` confirmed on served page. |
| 10 | Wave 25 manual playthrough records failure modes | PASS | See Playtest section below. |

All 10 acceptance items pass.

## Playtest results

Programmatic via Playwright; 21.7 real-game seconds per scenario.

| Scenario | Setup | King HP at end | Reaper kills | Royal Guards reaching cake | Cake state |
|---|---|---|---|---|---|
| 2 | 1 Reaper at (90, 280) near drip-spawn corner | 2850/3000 | 6 | 1 (carrying slice) | 7/8 in transit, 0 lost |
| 4 | 2 Reapers split: (90, 280) + (280, 150) covering King's cake-approach | 2760/3000 | 7 (6+1) | 0 | 8/8 untouched |

**Readings:**

- The fire-rate tax is real and substantial. Reaper at full DPS would clear 3000 HP King in roughly 100 sim seconds. With Royal Guards in the loop, both tested configs barely scratch him in the window tested.
- The spatial tradeoff (spec §3) manifests as designed. Two Reapers split gives 100% drip suppression AND roughly 60% more King DPS than a single Reaper. The winning config is meaningfully better, not a wash.
- Royal Guard HP 20 holds. Royal Guards get one-shot by Reaper as designed. The value of the bypass is the fire-rate tax, not survival. No HP-25 bump warranted.

**Tuning applied: NONE.** Starting values held across both tested scenarios. No §7 dial fired.

### Scenarios deferred

Scenarios 1, 3, and 5 were not run programmatically:

- **Scenario 1** (no Reaper, generic god-tier roster) — would test Royal Guard HP vs Hailstorm / Stormcaller / Phoenix chain-damage. The Stormcaller chain-clear concern from §7 was not directly exercised.
- **Scenario 3** (Reaper near King wander zone only) — would test the converse of the spatial tradeoff from Scenario 4.
- **Scenario 5** (Frost-heavy build) — would test `slowResist 0.3`.

If any of these surface failure modes in real play, the §7 tuning dials map cleanly: HP 20 → 25 (Stormcaller safety), `slowResist 0.3` → 0.2 (Frost trivialization), drip interval 2.5s → 3.0s (last resort).

## Architectural decisions worth flagging

1. **Reaper's `kind === 'worker'` instakill is intentionally preserved.** Plan 5 dodges it via fall-through, not by modifying the Reaper. If a future plan changes Reaper's targeting or modifier, preserve this fall-through behavior.

2. **`source: 'drip'` parameter on `spawnAntAt` is the future-friendly discriminator.** Currently used only for source-tagging. If a future plan wants a "King-loyal" trait shared across all drip-spawned units, this is the hook.

3. **`armor: 0.2` slots into the existing fractional-armor system from Plan 3** (`dmg = dmg * (1 - armor)`). No new mitigation variable. `armorPierce: true` cannons (Railgun line, Sniper line, Particle Cannon, Annihilator) bypass it cleanly.

4. **Sprite-table key naming is mixed by intent.** Snake_case for the living-ant sprite (`royal_guard`, must match `spriteKey` in `ANT_KINDS`), camelCase for the splat (`royalGuardSplat`, matches the existing splat convention). Not a mistake.

5. **The dead-ant render path uses a kind-aware nested-if for splat selection** (see [`demo-ant-buster.php` around line 1402](../../../patterns/demo-ant-buster.php)). If a future plan adds a third ant kind with a distinct splat, follow the same nested-if pattern, or generalize to a per-kind `splatColor` field if the third variant arrives.

## Plan 6+ candidates

From spec §2 out-of-scope log plus prior plan backlogs:

- **Title Screen / Save State shell** — deferred from Plan 5 scope discussion.
- **Brittle status combo** (Frozen + Heavy = shatter, 2× damage) — Plan 4 §4 candidate.
- **Rubbery Resonance** (Pink Rubber bounces apply 0.3s slow) — Plan 4 §4 candidate.
- **Per-tower targeting priority UI** — Plan 4 §4 candidate.
- **Hawkeye-vs-Queen rebalance** — flagged in Plan 4 status doc §117.
- **Distinct sprite silhouettes for the 18 god-tier rows** — currently fall back to howitzer sprite; visual identity comes from halo / flash / shake.

## File deltas

- `wp-content/themes/dariobimbi/patterns/demo-ant-buster.php` — net approximately +40 lines. New `royal_guard` row in `ANT_KINDS`, `makeRoyalGuardSprite` and `makeRoyalGuardSplat` factories, sprite-table registrations, kind-aware nested-if in `drawAnts`, drip-spawn kind change in King's `onSpawn`.
- `wp-content/themes/dariobimbi/style.css` — `Version: 0.10.0 → 0.11.0`.
- `wp-content/themes/dariobimbi/docs/superpowers/plans/2026-05-08-ant-buster-plan-5-status.md` — this file.
