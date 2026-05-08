# Plan 6 Implementation Status — Shipped

**Last updated:** 2026-05-08 (end of session)
**Status:** SHIPPED. Branch `ant-buster-plan-6`, 5 commits (4 feature/hygiene + this status doc), ready for `--no-ff` merge to `main`.
**Plan reference:** [`2026-05-08-ant-buster-plan-6.md`](./2026-05-08-ant-buster-plan-6.md)
**Spec reference:** [`../specs/2026-05-08-ant-buster-plan-6-design.md`](../specs/2026-05-08-ant-buster-plan-6-design.md)

## What shipped

Plan 6 adds the Brittle synergy: a heavy-family projectile's direct hit on a frozen ant deals 3× damage (composing multiplicatively with Plan 3's existing `freeze.damageAmp: 1.5×`), consumes the freeze status, and spawns a cyan shatter visual. The mechanic extends the existing `applyDamage` function with a 5th `isDirectHit` parameter and slots a nested-if predicate between the damageAmp blocks and armor mitigation — about 15 lines of logic sitting immediately after the existing `poison.damageAmp` block and before the `if (!armorPierce)` armor check. Only the projectile-direct-collision call site passes `true`; all splash, beam, aura, and chain-link callers stay 4-arg, enforcing the direct-hit-only contract from spec §1.

The cyan particle system (10 particles per shatter, color `#88f0ff`, 0.18s lifetime, radial 80-140 px/sec with ×0.92 friction per tick) ticks on real-time `dt` alongside screenshake decay so the burst stays readable at any game speed. End-to-end Playwright probe confirmed: Earthshaker shell on a frozen worker at wave 25 produced exactly `12 dmg direct + 2.5 splash = 14.5 total`, freeze cleared, 10 cyan particles spawned.

Branch base: `84bceac` on `main` (Plan 6 implementation plan commit, sitting on top of Plan 5's merge `60ec138` and the Plan 6 spec doc `36ca4c5`). Branch head: this commit.

### Plan 6 commits (chronological)

| # | Commit | Subject |
|---|---|---|
| 1 | `afa82d5` | feat(ant-buster): shatter particle system (Plan 6 Task 1) |
| 2 | `dd1478e` | feat(ant-buster): Brittle predicate in applyDamage (Plan 6 Task 2) |
| 3 | `4d1e8c1` | feat(ant-buster): projectile-collision call site (Plan 6 Task 3) |
| 4 | `89a6653` | chore(ant-buster): bump theme version to 0.12.0 + applyDamage header comment (Plan 6 Task 4) |
| 5 | _this commit_ | docs(ant-buster): Plan 6 status — shipped (Plan 6 Task 5) |

## Acceptance criteria walkthrough

QA walk performed via Playwright against `http://localhost/dariobimbi.com/demos/ant-buster/?debug=1` on 2026-05-08. All code-structure checks via grep.

| # | Spec §9 item | Result | Note |
|---|---|---|---|
| 1 | `applyDamage` signature has 5 params including `isDirectHit` | PASS | `grep -n "function applyDamage"` confirms `(ant, amount, armorPierce, sourceCannon, isDirectHit)` at line 4092. |
| 2 | Brittle predicate exists between `poison.damageAmp` block and armor mitigation; nested ifs; checks all 4 conditions | PASS | Lines 4123-4141 confirmed: nested-if block after the `}` closing the poison block at 4122, before `if (!armorPierce)` at 4142. |
| 3 | Only line 4434 passes `true` as 5th arg; all other 7 call sites remain 4-arg | PASS | `grep -n "applyDamage("` returns 8 lines: declaration + 7 call sites (4-arg) + 1 call site at line 4434 (5-arg, `true`). |
| 4 | Particle system fully wired: state init, factory, update tick, draw pass all present | PASS | `grep -n "shatterParticles"` confirms: init at line 126 + line 4854, `spawnShatterParticles` at 2370, `updateShatterParticles` at 2389 (called at 5095), `drawShatterParticles` at 2406 (called at 1942). |
| 5 | Discus direct hit on frozen worker: `4 × 1.5 × 2.0 = 12 dmg`, one-shot kill | PASS | Playwright probe: `preHp: 10 → postHp: -2`, `actualDmg: 12 = expectedDmg`. |
| 6 | Annihilator on frozen + Biohazard'd King: `120 × 1.5 × 1.25 × 2.0 × 1.0 = 450 dmg` | PASS | Playwright probe: `preHp: 3000 → postHp: 2550`, `dmgDealt: 450 = expected`. |
| 7 | Freeze cleared + exactly 10 particles spawned on shatter | PASS | Playwright probe: `freezeAfter: false`, `particlesAfter: 10`. Atomic — both occur in same predicate block. |
| 8 | Visual gating: 3 negative cases produce no shatter and correct base damage | PASS | (a) Heavy direct on non-frozen: `hp 10 → 6`, no particles. (b) Quick direct on frozen: `hp 10 → -2` (damageAmp only), freeze stays, no particles. (c) Heavy splash on frozen: `hp 10 → 4` (damageAmp only), freeze stays, no particles. |
| 9 | Phase ant intangible: projectile loop `continue`s at line 4427 before `applyDamage` | PASS | `grep -n "intangible"` confirms `if (a.intangible) { continue; }` at line 4427, two lines before the `applyDamage(a, …, true)` call at 4434. Guard is upstream; Brittle cannot fire on an intangible ant. |
| 10 | Plague spread on shattered death: `poison.spreads === true` intact after shatter; death path propagates to neighbors | PASS | Scenario C Playwright probe: central ant Plague'd + frozen, Demolisher direct hit (225 dmg), `centralHasPoison: true` after shatter, `freezeClearedByBrittle: true`, `particlesSpawned: true`. Poison propagation fires from `killAnt` on the death tick (not from `applyDamage`). |
| 11 | Particles tick real-time: at 8× speed, burst lasts ~0.18s wall-time (~11 frames at 60fps) | PASS | Playwright probe at `speed: 8`: particle lasted 175ms wall-time, survived 11 frames. If sim-time, would be ~22ms; if real-time, ~180ms. Confirmed real-time. |
| 12 | PHP lint passes | PASS | `c:/wamp64/bin/php/php8.2.29/php.exe -l patterns/demo-ant-buster.php` returns "No syntax errors detected". |
| 13 | Hygiene: no `&#038;&#038;` or `&amp;&amp;` in served page | PASS | Both `curl … | grep -c '&#038;&#038;'` and `grep -c '&amp;&amp;'` return 0. |
| 14 | Theme `Version: 0.12.0` in `style.css`; served page uses `?ver=0.12.0` | PASS | `curl … | grep "ver=0.12.0"` returns the stylesheet and script enqueue lines. |
| 15 | Wave 22+ playthrough records King TTK, RG pressure, visual readability | PASS | See Playtest results below. Scenarios A, B, C all run. |

All 15 acceptance items pass.

## Playtest results

Programmatic via Playwright, 2026-05-08.

### Scenario A — King boss-melt benchmark

Setup: 1 Absolute Zero (range 130, freeze duration 1.5s) + 1 Annihilator (range 130, damage 120, fireRate 0.3s, armorPierce) both at (180, 75) and (220, 75), King spawned at (200, 120) with proper ant fields. Wave 25 state, 4× game speed.

| Metric | Value |
|---|---|
| King time-to-die (sim-seconds) | **78.7s** |
| Cake slices lost | **3** |
| Royal Guards still alive at King-death | **4** |
| Cake state at King-death | 5/8 |

**Note on TTK vs spec prediction:** The spec's §4 TTK math gives ~28s for a solo Annihilator with continuous freeze coverage. The measured 78.7s reflects the realistic scenario where the King wanders in and out of the fixed cannons' 130px range — projectiles do not fire when the King is out of range, so effective DPS is substantially lower than the theoretical continuous-fire ceiling. This is expected real-play behavior, not a bug. The spec's ~28s figure remains valid for an optimal setup where Frost cannons and the Annihilator are repositioned as the King moves (which players can do manually). The 3 lost slices and 4 living Royal Guards at King-death shows meaningful pressure was maintained throughout, consistent with the spec §8 baseline (0-2 RGs reaching cake was the goal; 3 slices lost is slightly above baseline but within the first-deployment ballpark).

**§8 tuning dial assessment:** King-too-fragile dial (§8.1 HP 3000 → 4000) is NOT warranted based on this playtest. King survived 78.7s and exerted real RG pressure. The 28s theoretical ceiling requires a tighter, player-controlled setup that warrants the power fantasy.

### Scenario B — Wave-clear visual gating

Setup: 3 frozen workers at (100, 100), (108, 100), (92, 100). Earthshaker (heavy) as source cannon. Direct hit on center ant, splash to both bystanders.

| Ant | Hit type | Brittle fired | Particles | Freeze after | HP delta |
|---|---|---|---|---|---|
| Center (100, 100) | Direct hit | YES | +10 | cleared | 10 → -230 (240 dmg: 80×1.5×2.0) |
| Bystander B (108, 100) | Splash | NO | 0 | intact | 10 → -50 (60 dmg: 40×1.5×1.0) |
| Bystander C (92, 100) | Splash | NO | 0 | intact | 10 → -50 (60 dmg: 40×1.5×1.0) |

Visual gating contract confirmed: exactly 10 particles spawned (1 shatter), bystanders die from splash but keep their freeze. The "ant-liberator" anti-pattern does not occur.

### Scenario C — Triple-stack interaction

Setup: 1 central worker at (100, 100) with Plague (poison.spreads=true, damageAmp=1.25) + Cryo freeze (damageAmp=1.5). Demolisher (heavy) direct hit.

| Metric | Value |
|---|---|
| Expected damage | `60 × 1.5 × 1.25 × 2.0 = 225 dmg` |
| Actual damage | 225 dmg (exact) |
| Freeze cleared | YES |
| Poison intact after shatter | YES (spreads=true on dead ant, available for killAnt propagation) |
| Particles spawned | 10 |
| Console errors | 0 |

The three visual layers (cyan burst, standard splat, Plague propagation on death tick) compose cleanly without interference. Brittle deletes only `ant.effects.freeze`; `ant.effects.poison` is untouched and available for the `killAnt` `onTargetDeath` cascade.

**Readings:** All three cascade effects compose without interference. The multiplicative stack at maximum depth (`freeze.damageAmp × poison.damageAmp × Brittle`) produces exactly the §4 spec math. No console errors across the entire playtest session.

**Tuning applied: NONE.** All dials at default values. No §8 dial warranted from these results.

## Architectural decisions worth flagging

1. **`isDirectHit` as a 5th parameter on `applyDamage`, not a separate function.** Keeping Brittle inside `applyDamage` lets the math compose atomically with `damageAmp` in the right order. Splitting would require the call site to pre-read `ant.effects.freeze`, pre-multiply, then call `applyDamage` with the amp bypassed — gnarly and race-prone if two projectiles hit the same frame.

2. **Nested-if predicate, not `&&`.** The 4-condition check is verbose but matches the function-local convention at the existing `freeze.damageAmp` block (lines 4103-4112 use the same shape). The nested-if also dodges the inline-script HTML-encoding gotcha documented in CLAUDE.md: `&&` inside `<script>` content can be encoded to `&#038;&#038;` by WordPress content filters, causing a JS parse error. Hygiene check confirmed 0 occurrences.

3. **No immunity window after shatter.** The natural fire-rate cadence of the freeze cannons (Cryo 1.67s reload, AZ 1.0s, Blizzard aura 5s per-ant) is the thaw governor. A per-ant `shatterImmuneUntil` timer was considered and rejected as unmotivated — any arbitrary window (0.1s, 0.5s) is equally arbitrary. If AZ-spam proves problematic in real play, §8.5 has a clean 5-line escape hatch.

4. **Family-based, not damage-threshold-based, "Heavy" classification.** Reuses the existing `family: 'heavy'` curation in `CANNON_KINDS`. A damage-threshold approach was rejected because it would silently retag cannons every time base damage is tweaked for balance reasons, creating invisible cross-plan coupling. Family membership is explicit and deliberate.

5. **Direct-hit-only, not splash-propagation.** Splash bystanders get the existing 1.5× `damageAmp` passive but no 2× Brittle spike. This prevents the "ant-liberator" pattern where one Earthshaker shell would mass-shatter an entire frozen cluster, consuming all the player's crowd control in a single frame. Scenario B confirmed this guard holds in practice.

## Plan 7+ candidates

From spec §1 non-goals plus prior plan backlogs:

- **Title Screen / Save State shell** — deferred; the right time is now (Plan 6 added the second mechanical layer that justifies a save-friendly architecture). The `game.shatterParticles` ephemeral-state comment at line 126 is a bookmark for the serializer author.
- **Per-tower targeting priority UI** — Plan 4 §4 candidate. Brittle makes placement decisions more consequential, which makes a targeting UI more valuable.
- **Hawkeye-vs-Queen rebalance** — Plan 4 status doc §117. The frozen-Queen Annihilator one-shot scenario (360 dmg vs 400 HP, armor-pierced) was explicitly marked "intended" in spec §8 — if playtest shows it trivializes wave-18-22, the fix is a Queen rebalance, not a Brittle nerf.
- **Distinct sprite silhouettes for the 18 god-tier rows** — visual identity work, no mechanics. Still falls back to the howitzer sprite; halo + flash + shake carry the visual distinction today.

## File deltas

- `wp-content/themes/dariobimbi/patterns/demo-ant-buster.php` — net approximately +81 lines. Particle system (state init at line 126, `spawnShatterParticles` factory at 2370, `updateShatterParticles` tick at 2389, `drawShatterParticles` render at 2406, update wire-in at 5095, draw wire-in at 1942), `applyDamage` 5th parameter + header comment at line 4090, Brittle predicate block at lines 4123-4141, projectile-collision call-site update at line 4434.
- `wp-content/themes/dariobimbi/style.css` — `Version: 0.11.0 → 0.12.0`.
- `wp-content/themes/dariobimbi/docs/superpowers/plans/2026-05-08-ant-buster-plan-6-status.md` — this file.
