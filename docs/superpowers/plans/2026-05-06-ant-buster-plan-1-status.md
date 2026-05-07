# Plan 1 Implementation Status

**Last updated:** 2026-05-07 (end of evening session)
**Branch:** `ant-buster-mvp` (13 commits ahead of `main`)
**Plan reference:** [`2026-05-06-ant-buster-plan-1-shell-mvp.md`](./2026-05-06-ant-buster-plan-1-shell-mvp.md)
**Spec reference:** [`../specs/2026-05-06-ant-buster-design.md`](../specs/2026-05-06-ant-buster-design.md)

## Where to resume next session

1. Confirm you're on the `ant-buster-mvp` branch and the working tree is clean.
2. Browser smoke-test what's been built so far. Open `http://localhost/dariobimbi.com/wp-admin/` once to trigger `inc/auto-pages.php` reconciliation (creates the `/demos/ant-buster/` Page from the registered template). Then open `http://localhost/dariobimbi.com/demos/ant-buster/` in incognito.
   - Expected: picnic blanket renders, 5 placeholder workers wander toward the cake, one Howitzer card on the right, drag-to-place works, range circle previews white/red, placed cannons rotate but don't fire yet, "THE ANTS WIN" appears when cake hits 0 slices.
3. Apply the deferred edge-straddle placement fix (see "Open issues" below) as the first edit of Task 10.
4. Continue with Tasks 10 → 15 from the plan.
5. After Task 15, dispatch a final whole-implementation code review (per the subagent-driven-development workflow).
6. When approved, merge `ant-buster-mvp` into `main`. Then plan and execute Plan 2 (full cannon + ant rosters + 25-wave campaign).

## Completed tasks

Each task is its own commit on the branch. Reviews caught real issues on Tasks 1, 2, and 4 (each got a follow-up `fix:` commit). Tasks 3, 5, 6, 7, 8, 9 passed clean.

| # | Task | Implementation SHA | Fix SHA (if any) | Notes |
|---|---|---|---|---|
| 1 | Pattern + template scaffold + theme registration | `e15eb6b` | `87b8c7f` | Class renamed `ant-buster-page` → `demo-ant-buster`; em-dash title; canvas a11y attrs added; theme.json placement fixed |
| 2 | Canvas DOM, full-bleed background, pixelated rendering | `5ff5eec` | `b16031c` | Dropped dead `height:540px` line |
| 3 | Game IIFE skeleton, state object, main loop | `115f2e4` | — | Approved clean |
| 4 | Procedural placeholder sprites | `32a835e` | `35afacd` | Cake frosting Y offset `*8` → `*12` so frosting and body descend in sync |
| 5 | Render the playfield (blanket, grass, cake, anthill) | `708099d` | — | Approved clean |
| 6 | Ant data structure, spawn, render (stationary) | `b94209f` | — | Approved clean |
| 7 | Erratic ant pathing (movement) | `25e1940` | — | Approved clean |
| 8 | Cake collision, life counter, lose state | `fc24341` | — | Approved clean |
| 9 | Cannon shop UI panel + drag-to-place | `160aba8` | — | Approved with one open issue (see below) |

Plan doc was kept in sync with all corrections via commit `429bbba`.

## Remaining tasks

In plan-spec order. Each one is fully specified in the plan file with code blocks; no design work remains.

- **10:** Cannon targeting and projectiles. Add `findTarget` + `updateCannons` to CANNONS section, add new PROJECTILES section, wire both into `update()`, render projectiles in `render()`. **Combine with the edge-straddle fix (see open issues).**
- **11:** Gold drops on kill. One-line change in the projectile-hit block.
- **12:** HUD bar text (gold / cake slices / wave).
- **13:** Replace the bootstrap 5-ant loop with the wave-1 spawn schedule + wave-complete detection.
- **14:** Win/lose overlay layout + click-to-restart.
- **15:** Intro hint + final theme version bump.
- **Final review:** whole-implementation code review of the cumulative diff `main..ant-buster-mvp` against the plan and spec.

## Open issues to fix

### Edge-straddle placement bug (Task 9 review note)

`canPlaceCannon` boundary checks allow a 32×32 cannon sprite to straddle the playfield boundary by half a sprite. The cannon would render half outside the intended area.

**Fix** — in the CANNONS section of `patterns/demo-ant-buster.php`, replace the four bounds checks at the top of `canPlaceCannon`:

```javascript
    if (x < 0) { return false; }
    if (x > PLAYFIELD_W) { return false; }
    if (y < HUD_H) { return false; }
    if (y > HEIGHT) { return false; }
```

with:

```javascript
    if (x < 16) { return false; }
    if (x > PLAYFIELD_W - 16) { return false; }
    if (y < HUD_H + 16) { return false; }
    if (y > HEIGHT - 16) { return false; }
```

`16` is the half-width of the cannon base sprite (32×32). Apply this as the first sub-step of Task 10.

## Workflow notes for the next session

The subagent-driven-development pattern that worked well across Tasks 5–9:

- One implementer subagent per task. Provide the **full task text from the plan** in the prompt — don't make the subagent read the plan file (skill rule: red flag).
- One **combined spec + code-quality reviewer subagent** per task. The skill spec calls for two separate review subagents but a combined one is more efficient when the change is small and isolated. Use it as a default; split if the task is large.
- Capture the base SHA before each implementer dispatch, head SHA after the commit, so the reviewer can `git diff base..head`.
- If the reviewer finds an issue: send a focused fix-prompt to a fresh implementer subagent (cheaper model is fine — fixes are mechanical) rather than amending the original commit.
- Project conventions to enforce in every prompt:
  - No `&&` or `||` in inline scripts (WordPress encoding gotcha).
  - `var` over `let`/`const` (consistent with project demos).
  - Single-file pattern (no extracting to separate JS files).
  - PHP CLI is at `c:/wamp64/bin/php/php8.2.29/php.exe`.

## Branch commit log (for reference)

```
429bbba docs(ant-buster): sync Plan 1 with corrections from review
160aba8 feat(ant-buster): cannon shop, drag-to-place, range preview        (Task 9)
fc24341 feat(ant-buster): cake collision, life counter, lose state         (Task 8)
25e1940 feat(ant-buster): erratic-wander pathing toward the cake           (Task 7)
b94209f feat(ant-buster): add ant data structure, spawn, render            (Task 6)
708099d feat(ant-buster): render the playfield, cake, anthill, blanket     (Task 5)
35afacd fix(ant-buster): align cake frosting Y offset with body            (Task 4 fix)
32a835e feat(ant-buster): generate procedural placeholder sprites          (Task 4)
115f2e4 feat(ant-buster): scaffold IIFE, game state, fixed-timestep loop   (Task 3)
b16031c fix(ant-buster): drop dead height:540px declaration                (Task 2 fix)
5ff5eec feat(ant-buster): style the canvas stage with pixelated rendering  (Task 2)
87b8c7f fix(ant-buster): align scaffold with project demo conventions      (Task 1 fix)
e15eb6b feat(ant-buster): add pattern + template scaffold for demo page    (Task 1)
```
