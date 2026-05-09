# Plan 8a Implementation Status — Shipped

**Last updated:** 2026-05-08 (end of session)
**Status:** SHIPPED. Branch `ant-buster-plan-8a`, 11 commits (10 sprite/scaffold + this status doc), ready for `--no-ff` merge to `main`.
**Plan reference:** [`2026-05-08-ant-buster-plan-8a.md`](./2026-05-08-ant-buster-plan-8a.md)
**Spec reference:** [`../specs/2026-05-08-ant-buster-god-tier-sprites-design.md`](../specs/2026-05-08-ant-buster-god-tier-sprites-design.md)

## What shipped

Plan 8a is the vertical-slice phase of the god-tier sprite polish pass — 9 of 18 god-tier (T4) cannons now have distinct procedural canvas-factory sprites instead of the Howitzer fallback. The 9 chosen are the most visually distinctive entries from each family (3 Heavy, 3 Quick, 3 Double), picked to stress-test the family-anchor differentiation across the design system before Plan 8b fills in the conventional 9.

Each sprite is a 20×20 RGBA canvas drawn with `ctx.fillStyle` + `ctx.fillRect` calls per the spec §4 pixel-landmark layout. The gold halo (`drawGodTierHalos`, line 1447) is unchanged — it remains a separate procedural layer pulsing underneath. Halo-clearance for the bottom 4 rows (y=15–18) was honored on the dark-base sprites (Particle Cannon, Annihilator, Reaper, Hawkeye) and the design-intent bright accents (Earthshaker's orange seep, Phoenix's ember glow, Blizzard's snowflake glyph, Tempest's lightning + shockwave rings, Biohazard's reservoirs) play with the halo, not against it — visually verified via screenshot.

Branch base: `f0eb1cd` on `main` (Plan 8a plan-doc commit, sitting on top of `389a9d4` Earthshaker spec tweak, on top of `f233d5a` Plan 7 merge). Branch head: this commit.

### Plan 8a commits (chronological)

| # | Commit | Subject |
|---|---|---|
| 1 | `f9fe1fe` | feat(ant-buster): Plan 8a scaffold - 9 god-tier turret stubs |
| 2 | `f31f8ea` | feat(ant-buster): Particle Cannon god-tier sprite (Plan 8a Task 2) |
| 3 | `e359a10` | feat(ant-buster): Annihilator god-tier sprite (Plan 8a Task 3) |
| 4 | `0f80c15` | feat(ant-buster): Earthshaker god-tier sprite (Plan 8a Task 4) |
| 5 | `8853bab` | feat(ant-buster): Phoenix god-tier sprite (Plan 8a Task 5) |
| 6 | `3dfe510` | feat(ant-buster): Reaper god-tier sprite (Plan 8a Task 6) |
| 7 | `70f4ffb` | feat(ant-buster): Hawkeye god-tier sprite (Plan 8a Task 7) |
| 8 | `6c0e718` | feat(ant-buster): Blizzard god-tier sprite (Plan 8a Task 8) |
| 9 | `9be3b0d` | feat(ant-buster): Tempest god-tier sprite (Plan 8a Task 9) |
| 10 | `66d8b54` | feat(ant-buster): Biohazard god-tier sprite (Plan 8a Task 10) |
| 11 | _this commit_ | docs(ant-buster): Plan 8a status — shipped |

## Acceptance criteria walkthrough (§6 items 1-9)

QA walk via Playwright pixel-sampling against the offscreen sprite canvases (NOT the live game canvas). The offscreen approach is more reliable because it bypasses pause-overlay and rAF-throttling concerns entirely.

| # | Spec §6 item | Result | Notes |
|---|---|---|---|
| 1 | All 9 sprites load with 20×20 dimensions | PASS | `window.__ab.sprites.turrets[<kind>]` confirmed for all 9; `width === 20`, `height === 20`. |
| 3 | Background fully transparent | PASS | All 9 corner pixels (0,0), (19,0), (0,19), (19,19) at alpha 0. |
| 4 | Halo clearance | PASS WITH NOTES | Strict ≤2 bright pixels in y=16-19 was too tight for the spec's own carve-outs. Earthshaker (10), Phoenix (8), Blizzard (6), Tempest (18), Biohazard (20) all have bright halo-zone pixels — but each is a design-intent feature called out in §4 (orange seep, ember, snowflake glyph, lightning + shock-rings, reservoir base). Screenshot of all 9 placed in a 3x3 grid with halos active confirmed they play with the halo, not against it. |
| 5 | No more Howitzer fallback | PASS | Each sprite's pixel-sample probe verified unique colors that Howitzer doesn't have. Visual screenshot confirms 9 distinct silhouettes. |
| 6 | Family hierarchy reads | PASS | 3x3 screenshot shows Heavy slate-blue+brass anchor, Quick green/gold/teal anchor, Double magenta + status-color anchor visible across all three rows. |
| 7 | Family cohesion (T3 → T4 evolution) | PASS | Each new T4 reads as same-family + tier-up evolution. Particle Cannon evolves from Patriot's Heavy slate-blue base. Reaper evolves from Marksman's thin sniper silhouette. Blizzard evolves from Cryo's ice palette. None of the 9 reads as a cross-family swap. |
| 8 | Rotation works | PASS | All sprites except Tempest are mirror-symmetric around x=10. Tempest's intentional Z-bolt asymmetry IS the design — the bolt rotates with the cannon as it tracks targets, anchored by symmetric shockwave rings on the base. |
| 9 | Gold halo plays well | PASS | Verified by screenshot — gold halo (`drawGodTierHalos`, line 1447, alpha 0.30→0.60 sin-pulse) blends cleanly under all 9 sprites without visible clash. The halo-zone bright accents (orange/yellow/cyan) read as glow features, not noise. |

PHP lint passed at every task. Hygiene grep returned 0 at every task. Items 10 (PHP lint final), 11 (hygiene grep final), 12 (theme version 0.14.0) are deferred to Plan 8b's full-roster acceptance walk.

## Per-sprite pixel-perfect verification

Each sprite's offscreen pixel samples matched the spec §4 colors exactly. Selected highlights:

- **Particle Cannon** — barrel `[216, 224, 240]`, ring `[58, 255, 255]`, core highlight `[224, 160, 255]` ✓
- **Earthshaker** — piston `[58, 64, 85]` vs breech `[26, 24, 32]` = contrast delta 125 (vastly above the ±10 threshold guarding the silhouette pop) ✓
- **Reaper** — hook apex at (8,1) renders bone-white `[232, 224, 208]` correctly overlaying the dark hood; eye `[255, 32, 32]` and highlight `[255, 128, 128]` preserved ✓
- **Hawkeye** — barrel pixel `[74, 85, 96]` overlays scope at (10, 12) by design (drawn after); brass cap rings `[170, 120, 34]` confirm cross-axis geometry ✓
- **Tempest** — all four Z-bolt path samples (top, lean, kink, bottom) yellow `[255, 240, 128]`; halo backdrop `[255, 208, 64]` correctly visible at non-core positions; symmetric shockwave rings `[128, 192, 255]` ✓

## Architectural decisions worth flagging

1. **Path A (procedural canvas factories) chosen over Path B (Gemini PNG).** The spec recommended Path A for this pass, and execution proved the choice — pixel-perfect verification is straightforward against `ctx.fillRect`-drawn canvases. PNG migration remains an open path if texture-richness needs grow.

2. **Earthshaker piston-vs-breech contrast guard.** Plan 8a included a dedicated probe checking that piston `#3a4055` doesn't merge with breech `#1a1820` (delta 125 vs ±10 threshold). Future Heavy-family god-tier work should similarly probe the silhouette differentiator pixel pair when the spec calls out a deliberate contrast tuning.

3. **Halo-clearance threshold needs design-aware tuning.** The strict `≤2 bright pixels in y=16–19` heuristic from the plan failed for 5 of 9 sprites — but in every case, the bright pixels were design-intent features the spec explicitly carved out (Earthshaker orange seep, Phoenix ember, Blizzard snowflake glyph, Tempest lightning, Biohazard reservoirs). Plan 8b's acceptance walk should replace the pixel-count threshold with a screenshot-based check ("does this sprite visually clash with the gold halo at full pulse?").

4. **Reaper draw order is non-obvious.** The hood at (8, 1) gets overwritten by the hook apex (also at (8, 1)) which is correct — the hook is drawn AFTER the hood. The eye at (6, 1) and highlight at (7, 1) sit on hood pixels, drawn after hood but before hook, so they survive. If a future sprite has 3+ overlay layers, document the draw order in the spec like Reaper §4.9 does.

5. **Tempest asymmetry is design intent.** Most sprites are mirror-symmetric around x=10 to handle rotation cleanly. Tempest's Z-bolt is intentionally asymmetric — the bolt rotates with the cannon as it tracks. The symmetric shockwave rings on the base anchor the visual. Any future sprite breaking symmetry should similarly anchor via a symmetric ground feature.

6. **The implementer subagent's live Playwright probes failed; offscreen sprite-canvas probes succeeded.** Per-task implementers using live game-canvas pixel sampling hit pause-overlay obscuration and rAF-throttling issues. The controller-side workaround — sample directly from `window.__ab.sprites.turrets[<kind>].getContext('2d').getImageData(...)` — bypasses both. Future sprite work should default to offscreen-canvas verification.

## Plan 8b scope

The remaining 9 god-tier sprites stay on the Howitzer fallback until Plan 8b ships:

- **Heavy fill-in (3):** Saturation, Cluster Strike, Vortex.
- **Quick fill-in (3):** Hailstorm, Solar Lance, Stormcaller (the one with outrigger antennae per spec §4.12 feedback pass).
- **Double fill-in (3):** Scattergun, Phalanx, Absolute Zero.

Plus Plan 8b's hygiene wrap-up:
- Full §6 acceptance walk across all 18 god-tier sprites (this Plan 8a only checked the 9 it touched).
- Theme version bump 0.13.0 → 0.14.0 in `style.css`.
- Status doc + merge.

## File deltas

- `wp-content/themes/dariobimbi/patterns/demo-ant-buster.php` — net approximately +180 lines:
  - Plan 8a section header comment + 9 stub factory definitions (Task 1).
  - 9 stub bodies replaced with real implementations (Tasks 2-10).
  - 9 new entries in `game.sprites.turrets` table (Task 1).
  - Comma added to `rubber_wrecking_ball` entry (Task 1).
  - No other touchpoints. The gold halo at line 1447 is unchanged.
- `wp-content/themes/dariobimbi/docs/superpowers/plans/2026-05-08-ant-buster-plan-8a-status.md` — this file.
- **No theme version bump.** `style.css` stays at 0.13.0; the bump to 0.14.0 lands at end of Plan 8b.

## Acceptance criteria deferred to 8b

- §6 item 2 (effective dimensions === 20×20) — already verified for these 9 but full-roster check happens in 8b.
- §6 item 10 (PHP lint passes) — passed at every task; final pass in 8b.
- §6 item 11 (hygiene check passes) — passed at every task; final pass in 8b.
- §6 item 12 (theme version 0.14.0) — landing at end of 8b.

These are not "missed" by 8a — they're explicitly scoped to 8b's full-roster acceptance walk per the plan §8 split.
