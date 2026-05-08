# Ant Buster — Plan 5: Royal Guard & King-Fight Rebalance

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Restore the King fight's intended multi-tasking pressure by introducing a `royal_guard` ant kind that the King's drip-spawn produces, dodging the Reaper's `kind === 'worker'` instakill modifier without nerfing it.

**Architecture:** Plain content addition to `patterns/demo-ant-buster.php`. New ANT_KINDS row, two new sprite factories (living + splat), one nested-if at the splat-draw site, and a one-character swap in the King's `onSpawn` drip-job. ~50 lines of new code, single file, no template/theme.json/inc changes.

**Tech Stack:** PHP 8.2 (single pattern file), vanilla JS IIFE, Canvas 2D rendering, no build system, no test framework. Verification is PHP lint (`php -l`), hygiene check (`curl | grep` for HTML-encoded operators), and Playwright introspection via `window.__ab*` surfaces.

**Spec reference:** [docs/superpowers/specs/2026-05-08-ant-buster-plan-5-design.md](../specs/2026-05-08-ant-buster-plan-5-design.md) (commit `79e23d8`).

**Branch:** `ant-buster-plan-5`. Plan 4 merged to main 2026-05-08 as `8b4048b` (theme version 0.10.0). Plan 5 lands at version 0.11.0.

**Local URL for Playwright verify:** `http://localhost/dariobimbi.com/demos/ant-buster/`

---

## Setup

- [ ] **Step 1: Verify clean working tree on main**

```bash
git status
```

Expected: `nothing to commit, working tree clean` and `On branch main`.

- [ ] **Step 2: Create and check out the implementation branch**

```bash
git checkout -b ant-buster-plan-5
```

Expected: `Switched to a new branch 'ant-buster-plan-5'`.

- [ ] **Step 3: Confirm spec is on disk**

```bash
ls docs/superpowers/specs/2026-05-08-ant-buster-plan-5-design.md
```

Expected: file exists. If not, the brainstorming session didn't actually save the spec — investigate before continuing.

---

## Task 1: ANT_KINDS row

**Goal:** Add the `royal_guard` kind to the ANT_KINDS table, immediately after `worker`. After this task lands, `window.__abAntKinds.royal_guard` exists with the §4 stats from the spec; nothing visible changes in-game yet because no spawn site uses the new kind.

**Files:**
- Modify: `patterns/demo-ant-buster.php` — insert ~16 lines after the `worker` row (around line 1964, the closing `},` of the worker entry).

- [ ] **Step 1: Insert the `royal_guard` row immediately after the `worker` row**

The current `worker` row ends at line 1964 with `},`. Insert this new row directly after that closing brace, before the `carrier:` row begins:

```js
royal_guard: {
  name: 'Royal Guard',
  hp: 20,
  speed: 35,
  carryingSpeedMult: 1.5,
  gold: 4,
  alignment: 0.3,
  jitterRate: 3.0,
  wanderFreqMin: 0.3, wanderFreqMax: 0.7,
  wanderAmpMin: 0.9, wanderAmpMax: 1.5,
  spriteKey: 'royal_guard',
  spriteW: 16, spriteH: 16,
  slowResist: 0.3,
  damageMult: 1,
  pierceMult: 1,
  canPickup: true,
  armor: 0.2
},
```

- [ ] **Step 2: PHP lint + hygiene + Playwright verify**

Run lint:
```
c:/wamp64/bin/php/php8.2.29/php.exe -l patterns/demo-ant-buster.php
```
Expected: `No syntax errors detected`.

Run hygiene:
```
curl -s "http://localhost/dariobimbi.com/demos/ant-buster/" | grep -c -F "&#038;&#038;"
curl -s "http://localhost/dariobimbi.com/demos/ant-buster/" | grep -c -F "&#124;&#124;"
```
Expected: both return `0`.

Playwright verify (in browser console at the demo URL):
```js
JSON.stringify({
  exists: !!window.__abAntKinds.royal_guard,
  hp: window.__abAntKinds.royal_guard.hp,
  speed: window.__abAntKinds.royal_guard.speed,
  slowResist: window.__abAntKinds.royal_guard.slowResist,
  armor: window.__abAntKinds.royal_guard.armor,
  gold: window.__abAntKinds.royal_guard.gold,
  alignment: window.__abAntKinds.royal_guard.alignment,
  canPickup: window.__abAntKinds.royal_guard.canPickup,
  carryingSpeedMult: window.__abAntKinds.royal_guard.carryingSpeedMult,
  spriteKey: window.__abAntKinds.royal_guard.spriteKey
})
```
Expected: `{"exists":true,"hp":20,"speed":35,"slowResist":0.3,"armor":0.2,"gold":4,"alignment":0.3,"canPickup":true,"carryingSpeedMult":1.5,"spriteKey":"royal_guard"}`.

Reaper modifier sanity check (proves the bypass works without code changes to Reaper):
```js
JSON.stringify({
  fullHp: window.__abCannonKinds.reaper.damageModifier({kind:'royal_guard',hp:20,maxHp:20}),
  damaged: window.__abCannonKinds.reaper.damageModifier({kind:'royal_guard',hp:10,maxHp:20}),
  worker: window.__abCannonKinds.reaper.damageModifier({kind:'worker',hp:10,maxHp:10})
})
```
Expected: `{"fullHp":2,"damaged":1,"worker":999}` — Royal Guard gets the standard 2× full-HP modifier and 1× otherwise; Worker still hits the 999 instakill case.

- [ ] **Step 3: Commit**

```bash
git add patterns/demo-ant-buster.php
git commit -m "feat(ant-buster): royal_guard ANT_KINDS row (Plan 5 Task 1)"
```

---

## Task 2: Sprite factories

**Goal:** Add `makeRoyalGuardSprite()` (living-ant sprite, Worker silhouette + crown + accent) and `makeRoyalGuardSplat()` (royal-blue corpse) and register both in the sprites table. After this task lands, `window.__ab.sprites.royal_guard` and `window.__ab.sprites.royalGuardSplat` exist as 16×16 canvases; the draw paths can find them but no Royal Guard spawns yet so no visible change.

**Files:**
- Modify: `patterns/demo-ant-buster.php` — insert two new factory functions and two sprite-table entries.

- [ ] **Step 1: Insert `makeRoyalGuardSprite()` immediately after `makeWorkerSprite()` (line 202)**

The Worker sprite at line 189-202 is the size baseline. The Royal Guard variant copies that body and adds a crown + accent. Insert this block after line 202 (the closing `}` of `makeWorkerSprite`), before `function makeSoldierSprite()` at line 204:

```js
function makeRoyalGuardSprite() {
  var c = makeCanvas(16, 16);
  var x = c.getContext('2d');
  x.fillStyle = '#3a4466';
  x.beginPath();
  x.ellipse(8, 8, 5, 4, 0, 0, Math.PI * 2);   // body — same as worker
  x.fill();
  x.fillStyle = '#2c3a8c';
  x.fillRect(6, 7, 2, 2);                     // royal-blue accent (left shoulder)
  x.fillRect(9, 7, 2, 2);                     // royal-blue accent (right shoulder)
  x.fillStyle = '#262b44';
  x.fillRect(7, 3, 2, 2);                     // head
  x.fillStyle = '#fee761';
  x.fillRect(6, 1, 4, 1);                     // crown base, forward of head
  x.fillRect(6, 0, 1, 1);                     // crown spike L
  x.fillRect(8, 0, 1, 1);                     // crown spike center
  x.fillRect(9, 0, 1, 1);                     // crown spike R (intentional 3-spike pattern)
  x.fillStyle = '#181425';
  x.fillRect(7, 1, 1, 1);                     // antenna L (shorter, crown takes the upper space)
  x.fillRect(8, 1, 1, 1);                     // antenna R
  return c;
}
```

The crown sits at y=0-1 (forward-offset toward the mandibles per spec §6); the carrying-slice overlay sits on the body center (y=6-10) so it won't bury the crown. The blue shoulder accent at y=7 is on the body itself, where the slice may partially overlap, but that's intended — the crown is the primary identity beat.

- [ ] **Step 2: Insert `makeRoyalGuardSplat()` immediately after `makeAntSplat()` (line 361)**

The current splat at line 353-361 is the structural template. Insert this directly after its closing `}`, before `function makeCannonBase()` at line 363:

```js
function makeRoyalGuardSplat() {
  var c = makeCanvas(16, 16);
  var x = c.getContext('2d');
  x.fillStyle = '#2c3a8c';
  x.fillRect(4, 7, 8, 3);
  x.fillRect(5, 6, 6, 1);
  x.fillRect(5, 10, 6, 1);
  return c;
}
```

- [ ] **Step 3: Register both sprites in the sprites table**

The sprites table starts at line 972 with `game.sprites = {`. Find these two existing lines:

```js
worker: makeWorkerSprite(),
```
(around line 973)

```js
splat: makeAntSplat(),
```
(line 983)

Add a new line after each:

```js
worker: makeWorkerSprite(),
royal_guard: makeRoyalGuardSprite(),
```

```js
splat: makeAntSplat(),
royalGuardSplat: makeRoyalGuardSplat(),
```

- [ ] **Step 4: PHP lint + hygiene + Playwright verify**

Lint:
```
c:/wamp64/bin/php/php8.2.29/php.exe -l patterns/demo-ant-buster.php
```
Expected: `No syntax errors detected`.

Hygiene:
```
curl -s "http://localhost/dariobimbi.com/demos/ant-buster/" | grep -c -F "&#038;&#038;"
curl -s "http://localhost/dariobimbi.com/demos/ant-buster/" | grep -c -F "&#124;&#124;"
```
Expected: both `0`.

Playwright verify:
```js
JSON.stringify({
  spriteExists: !!window.__ab.sprites.royal_guard,
  spriteW: window.__ab.sprites.royal_guard.width,
  spriteH: window.__ab.sprites.royal_guard.height,
  splatExists: !!window.__ab.sprites.royalGuardSplat,
  splatW: window.__ab.sprites.royalGuardSplat.width,
  splatH: window.__ab.sprites.royalGuardSplat.height
})
```
Expected: `{"spriteExists":true,"spriteW":16,"spriteH":16,"splatExists":true,"splatW":16,"splatH":16}`.

- [ ] **Step 5: Commit**

```bash
git add patterns/demo-ant-buster.php
git commit -m "feat(ant-buster): royal_guard sprite + splat factories (Plan 5 Task 2)"
```

---

## Task 3: Splat draw-site conditional

**Goal:** Update the dead-ant render path to pick `royalGuardSplat` for `royal_guard` corpses. After this task lands, the conditional exists in code but no Royal Guard spawns yet, so it never fires in practice. The change is a defensive prerequisite for Task 5.

**Files:**
- Modify: `patterns/demo-ant-buster.php` — expand the nested-if at line 1364-1371.

- [ ] **Step 1: Replace the splat selection with a kind-aware nested if**

Find this block at line 1364-1371 in `drawAnts()`:

```js
if (a.dead) {
  // Delivered ants vanish silently. Only killed ants splat. Nested ifs avoid
  // the AND operator (gets HTML-encoded inside inline scripts in this theme).
  if (a.deliveredAt === 0) {
    sprite = game.sprites.splat;
  } else {
    continue;
  }
} else {
```

Replace the `if (a.deliveredAt === 0) { ... }` block with:

```js
if (a.dead) {
  // Delivered ants vanish silently. Only killed ants splat. Nested ifs avoid
  // the AND operator (gets HTML-encoded inside inline scripts in this theme).
  if (a.deliveredAt === 0) {
    if (a.kind === 'royal_guard') {
      sprite = game.sprites.royalGuardSplat;
    } else {
      sprite = game.sprites.splat;
    }
  } else {
    continue;
  }
} else {
```

Nested ifs match the function-local convention (see the comment at lines 1365-1366 explaining why this codebase avoids `&&` inside inline scripts). Do not refactor to a ternary.

- [ ] **Step 2: PHP lint + hygiene**

```
c:/wamp64/bin/php/php8.2.29/php.exe -l patterns/demo-ant-buster.php
curl -s "http://localhost/dariobimbi.com/demos/ant-buster/" | grep -c -F "&#038;&#038;"
curl -s "http://localhost/dariobimbi.com/demos/ant-buster/" | grep -c -F "&#124;&#124;"
```
Expected: lint passes, both grep counts `0`.

No Playwright assertion this task — the conditional doesn't fire until Task 5 spawns a Royal Guard. Skipping the assertion is intentional rather than a placeholder. (If you really want to exercise it, you can manually add `window.__abApplyDamage(window.__ab.ants[0], 999, false, null)` after spawning a test royal_guard, but that's premature given Task 5 lands the spawn naturally.)

- [ ] **Step 3: Commit**

```bash
git add patterns/demo-ant-buster.php
git commit -m "feat(ant-buster): kind-aware splat draw-site (Plan 5 Task 3)"
```

---

## Task 4: King drip-spawn swap

**Goal:** Change the King's `onSpawn` drip-job from spawning `'worker'` to spawning `'royal_guard'`. After this task lands, the feature is fully wired: wave 25's King fight produces Royal Guards on the 2.5s drip cycle, the Reaper bypass is exercised, and the spatial tradeoff dynamic from spec §3 emerges.

**Files:**
- Modify: `patterns/demo-ant-buster.php` — one quoted-string change at line 2196.

- [ ] **Step 1: Change the kind string in the King's drip-spawn**

Find this block at line 2190-2199 (the King's `onSpawn`):

```js
onSpawn: function(king) {
  game.spawnJobs.push({
    interval: 2.5,
    lastFiredAt: game.time,
    owner: king,
    fn: function() {
      spawnAntAt('worker', 10, 320, 'drip', { x: 10, y: 320 });
    }
  });
},
```

Change `'worker'` to `'royal_guard'`:

```js
onSpawn: function(king) {
  game.spawnJobs.push({
    interval: 2.5,
    lastFiredAt: game.time,
    owner: king,
    fn: function() {
      spawnAntAt('royal_guard', 10, 320, 'drip', { x: 10, y: 320 });
    }
  });
},
```

Do not change the interval, the spawn position, the source string, or anything else in this block. Mechanical isolation is the whole point per spec §5.

- [ ] **Step 2: PHP lint + hygiene + Playwright verify (full pipeline test)**

Lint:
```
c:/wamp64/bin/php/php8.2.29/php.exe -l patterns/demo-ant-buster.php
```
Expected: `No syntax errors detected`.

Hygiene:
```
curl -s "http://localhost/dariobimbi.com/demos/ant-buster/" | grep -c -F "&#038;&#038;"
curl -s "http://localhost/dariobimbi.com/demos/ant-buster/" | grep -c -F "&#124;&#124;"
```
Expected: both `0`.

Playwright verify — jump to wave 25 prep, then start it, wait for the King to spawn and the first drip to fire (about 2.5 simulated seconds at 1× speed):

```js
// 1. Set state to wave 25 prep with god-tier unlock fired
window.__abGodTierUnlocked(true);
window.__ab.waveIndex = 24;     // wave 25 is index 24 (zero-indexed)
window.__ab.waveState = 'prep';
window.__ab.prepRemaining = 0;  // skip prep
window.__ab.gold = 9999;        // not relevant but avoids any HUD weirdness

// 2. Bump game speed so the drip fires fast
window.__ab.speed = 4;

// 3. Wait ~3 real seconds (12 sim seconds) — first King spawns, first Royal Guard drips
await new Promise(function(r) { setTimeout(r, 3000); });

// 4. Inspect ant population
JSON.stringify({
  kingsAlive: window.__ab.ants.filter(function(a) { return a.kind === 'king'; }).length,
  royalGuardsSpawned: window.__ab.ants.filter(function(a) { return a.kind === 'royal_guard'; }).length,
  workersDripped: window.__ab.ants.filter(function(a) {
    if (a.kind !== 'worker') { return false; }
    if (!a.spawnOrigin) { return false; }
    return a.spawnOrigin.x === 10;
  }).length
})
```
Expected: `kingsAlive: 1`, `royalGuardsSpawned: >= 1` (at least one drip fired), `workersDripped: 0` (no Workers from the drip — only Royal Guards now).

If `royalGuardsSpawned: 0` after 3 real seconds at speed 4, increase the wait to 5 seconds and retry — the King's drip uses real game time accumulation and may need a moment.

- [ ] **Step 3: Visual sanity check (manual)**

Reload the demo page (so the speed/state hacks above don't carry into the visual test). Use `?debug=1` and the `W` hotkey to jump to wave 25 prep with god-tier unlocked, then let the wave start. Watch the bottom-left corner around (10, 320). You should see:

1. The King spawns and starts its slow wander (1 second after wave start, per existing King spawn timing).
2. Around 2.5s after the King spawns, the first Royal Guard appears at (10, 320) and starts marching toward the cake.
3. The Royal Guard sprite is visibly distinct from a Worker — small gold crown on top, royal-blue accent dots on the body.
4. If the Royal Guard dies (build a few quick cannons in the drip line first), the splat is royal blue, not the standard dark-navy color.

Take a screenshot of step 3 (Royal Guard alive, marching) and step 4 (blue splat) — these will be referenced in the Task 6 status doc.

- [ ] **Step 4: Commit**

```bash
git add patterns/demo-ant-buster.php
git commit -m "feat(ant-buster): King drips Royal Guards (Plan 5 Task 4)"
```

---

## Task 5: Hygiene closeout + version bump

**Goal:** Sweep the entire pattern file with PHP lint and hygiene checks, bump the theme version to 0.11.0 (cache-bust + memory anchor), and confirm no console errors load on the demo page.

**Files:**
- Modify: `style.css` — bump `Version:` from `0.10.0` to `0.11.0`.

- [ ] **Step 1: Bump theme version**

Open `wp-content/themes/dariobimbi/style.css`. Find the line:

```
Version: 0.10.0
```

Change to:

```
Version: 0.11.0
```

(Plan 4 landed at 0.10.0 per the auto-memory; Plan 5 increments the minor.)

- [ ] **Step 2: Full PHP lint + hygiene sweep**

Lint:
```
c:/wamp64/bin/php/php8.2.29/php.exe -l patterns/demo-ant-buster.php
```
Expected: `No syntax errors detected`.

Hygiene (the only `&&`/`||` shows up if WP's content filters mangled inline-script operators):
```
curl -s "http://localhost/dariobimbi.com/demos/ant-buster/" | grep -c -F "&#038;&#038;"
curl -s "http://localhost/dariobimbi.com/demos/ant-buster/" | grep -c -F "&#124;&#124;"
curl -s "http://localhost/dariobimbi.com/demos/ant-buster/" | grep -c -F "&lt;&lt;"
curl -s "http://localhost/dariobimbi.com/demos/ant-buster/" | grep -c -F "&gt;&gt;"
```
Expected: all `0`.

Console-error sweep via Playwright:

```js
// Reload the page first to clear any state from Task 4's verification.
location.reload();
```

Then after reload, in DevTools Console, look at the console messages list. Expected: no errors (red entries). Warnings about deprecated APIs are fine; outright errors block ship.

- [ ] **Step 3: Verify version bump shipped**

```bash
curl -sI "http://localhost/dariobimbi.com/demos/ant-buster/" | grep -i etag
curl -s "http://localhost/dariobimbi.com/wp-content/themes/dariobimbi/style.css?ver=0.11.0" | head -10
```

Expected: the `style.css` head shows `Version: 0.11.0`. The `?ver=` query string on the served stylesheet URL should reflect 0.11.0 once WP refreshes its theme metadata cache (visit the homepage once if it's stale).

- [ ] **Step 4: Commit**

```bash
git add style.css
git commit -m "chore(ant-buster): bump theme version to 0.11.0 (Plan 5 hygiene)"
```

---

## Task 6: Wave-25 playthrough + status doc + auto-memory

**Goal:** Playtest the King fight per the §7 scenarios, log results, write the Plan 5 status doc, and update auto-memory for the next session. This is the "ship" task — after it lands, the branch is ready to merge to main.

**Files:**
- Create: `docs/superpowers/plans/2026-05-08-ant-buster-plan-5-status.md` — the ship-status document.
- Update: `C:\Users\dario\.claude\projects\c--wamp64-www-dariobimbi-com-wp-content-themes-dariobimbi\memory\MEMORY.md` — add Plan 5 entry.
- Create: `C:\Users\dario\.claude\projects\c--wamp64-www-dariobimbi-com-wp-content-themes-dariobimbi\memory\ant_buster_plan_5_shipped.md` — Plan 5 auto-memory file.

- [ ] **Step 1: Run the five §7 playtest scenarios**

Use `?debug=1` URL parameter and the `W` hotkey to jump to wave 16 prep with god-tier unlocked. Then build the configuration for each scenario, manually progress through waves 16-24 with whatever towers you want, and let wave 25 play out naturally.

For each scenario, log: did the King die? did any Royal Guards reach the cake? how did the difficulty feel?

| # | Configuration | Did King die? | Royal Guards through? | Difficulty feel |
|---|---|---|---|---|
| 1 | No Reaper, generic god-tier roster | | | |
| 2 | One Reaper near (10, 320) | | | |
| 3 | One Reaper near King wander zone | | | |
| 4 | Two Reapers, split | | | |
| 5 | Frost-heavy build | | | |

(Fill in the table during the playtest. This is a real test, not a placeholder — leave the cells blank if any scenario can't be reached due to gold/time, and note that in the status doc.)

- [ ] **Step 2: Determine if any §7 tuning dials need to fire**

Cross-reference scenarios against §7's failure-mode table. If Scenario 1 showed the Royal Guards dying too fast to Stormcaller, the HP 20 → 25 dial fires now. If Scenario 5 showed Frost trivializing them, slowResist 0.3 → 0.2 fires. Apply only the dials that scenarios surface; do not pre-emptively tune.

If no tuning dial is needed, log "starting values held" in the status doc.

If a tuning dial fires:
1. Make the single-number edit to `patterns/demo-ant-buster.php`.
2. Run PHP lint (`php -l`) and hygiene check (the curl/grep pair).
3. Re-run only the affected scenario to verify the dial fixed it.
4. Commit as a separate dial-application commit:
   ```
   git commit -m "fix(ant-buster): bump royal_guard hp 20 -> 25 (Plan 5 Task 6 dial #1)"
   ```

- [ ] **Step 3: Write the Plan 5 status doc**

Create `docs/superpowers/plans/2026-05-08-ant-buster-plan-5-status.md` with the structure (mirror of Plan 4's status doc):

```markdown
# Ant Buster — Plan 5 Status (2026-05-08)

**Status:** SHIPPED. Branch `ant-buster-plan-5`, [N] commits, ready for merge to `main`.

## What landed

- New `royal_guard` ant kind (hp 20, speed 35, slowResist 0.3, armor 0.2, gold 4) — between Worker and Soldier in the stat hierarchy.
- King's drip-spawn swapped from `worker` to `royal_guard`. Reaper's `kind === 'worker'` instakill bypassed by fall-through.
- Two new sprite factories: `makeRoyalGuardSprite()` (Worker silhouette + crown + royal-blue shoulders) and `makeRoyalGuardSplat()` (royal blue corpse).
- Kind-aware nested-if at the splat draw-site to pick the blue splat for Royal Guards.
- Theme version bumped to 0.11.0.

## Why

[Reference spec §1 goal — the Reaper-vs-drip-Worker tension leak from Plan 4.]

## Commits

| Commit | Title |
|---|---|
| [SHA] | feat(ant-buster): royal_guard ANT_KINDS row (Plan 5 Task 1) |
| [SHA] | feat(ant-buster): royal_guard sprite + splat factories (Plan 5 Task 2) |
| [SHA] | feat(ant-buster): kind-aware splat draw-site (Plan 5 Task 3) |
| [SHA] | feat(ant-buster): King drips Royal Guards (Plan 5 Task 4) |
| [SHA] | chore(ant-buster): bump theme version to 0.11.0 (Plan 5 hygiene) |
| [SHA] | docs(ant-buster): Plan 5 status doc + auto-memory (Plan 5 Task 6) |
| [optional dial SHAs] | ... |

## Acceptance walkthrough (spec §8)

[Walk through all 10 acceptance criteria. Each gets PASS or FAIL with verification reference.]

## Playtest results

[Reproduce the §7 scenarios table from Step 1, filled in.]

## Tuning applied

[Either "Starting values held — no dial fired" or list of dials applied with commit SHAs.]

## Plan 6+ candidates

- Title Screen / Save State shell (deferred from Plan 5 scope discussion).
- Brittle status combo (Plan 4 §4 candidate).
- Rubbery Resonance (Plan 4 §4 candidate).
- Per-tower targeting priority UI (Plan 4 §4 candidate).
- Hawkeye-vs-Queen rebalance (Plan 4 status doc §117).
- Distinct sprite silhouettes for the 18 god-tier rows (Plan 4 deferred).
```

- [ ] **Step 4: Update auto-memory**

Create `C:\Users\dario\.claude\projects\c--wamp64-www-dariobimbi-com-wp-content-themes-dariobimbi\memory\ant_buster_plan_5_shipped.md` with this content (use the actual merge SHA after the merge step):

```markdown
---
name: Ant Buster Plan 5 shipped
description: Royal Guard subtype + King-fight rebalance shipped 2026-05-08. New ant kind dodges Reaper instakill, restores King-fight tension via spatial Reaper-placement tradeoff. Theme version 0.11.0.
type: project
---
Plan 5 (Royal Guard & King-fight rebalance) shipped 2026-05-08, merged to `main` as `[MERGE-SHA]` via `--no-ff` from branch `ant-buster-plan-5` ([N] commits). Status doc: `docs/superpowers/plans/2026-05-08-ant-buster-plan-5-status.md`.

**What landed:**
- New `royal_guard` ant kind (hp 20, speed 35, slowResist 0.3, armor 0.2, gold 4, canPickup true). Stat-hierarchy slot between Worker (hp 10) and Soldier (hp 40).
- King's drip-spawn at (10, 320) now produces `royal_guard` instead of `worker` every 2.5s. Reaper's `kind === 'worker'` instakill modifier falls through to standard 1×/2× damage on Royal Guards — no Reaper code change needed.
- Two new sprite factories: `makeRoyalGuardSprite()` (Worker silhouette + 4-pixel gold crown forward-offset toward the mandibles + 2-pixel royal-blue shoulder accents) and `makeRoyalGuardSplat()` (#2c3a8c variant of the standard splat). Both registered in the sprites table.
- Kind-aware nested-if at the splat draw-site (`drawAnts()` line 1364) — picks `royalGuardSplat` for `royal_guard` corpses.
- Theme version bumped 0.10.0 → 0.11.0.

**Why:** Plan 4 shipped 18 god-tier cannons including Reaper, whose `kind === 'worker'` instakill modifier accidentally trivialized the King's drip-spawn pressure. Plan 5 routes around it without nerfing the Reaper's identity against standard waves, restoring the King fight's intended multi-tasking pressure.

**How to apply:** When extending Ant Buster:
- The §3 spatial-tradeoff insight is the key takeaway — Reaper placement near (10, 320) drip-suppresses; placement near the King wander zone melts him; split placement is the "winning" config. Targeting is nearest-in-range with revealed-target preference (line 3492-3517) — there is no kind-priority field today.
- The `source: 'drip'` parameter on `spawnAntAt` ([demo-ant-buster.php:2309](wp-content/themes/dariobimbi/patterns/demo-ant-buster.php#L2309)) is the future-friendly discriminator for "King-loyal" traits if any future plan wants them.
- New ant kinds slot between `worker` and `carrier` in `ANT_KINDS` for stat-delta legibility against the Worker baseline.
- `armor` is a fractional mitigation field (Plan 3 — `dmg = dmg * (1 - armor)` at line 4008-4012); not a boolean. Cannons with `armorPierce: true` bypass it.

**Plan 6+ candidates** (logged in spec §2 out-of-scope): Title Screen / Save State shell, Brittle status combo, Rubbery Resonance, per-tower targeting priority UI, Hawkeye-vs-Queen rebalance, distinct god-tier sprite silhouettes.
```

Then update `MEMORY.md` to add the Plan 5 index entry. Find this existing line:

```
- [Ant Buster Plan 4 shipped](ant_buster_plan_4_shipped.md) — apex pass: 51 cannons (18 god-tier), 4 new primitives, Pink Rubber Family, wave-15 unlock; merged to main 2026-05-08 as 8b4048b, theme version 0.10.0
```

Add immediately after it:

```
- [Ant Buster Plan 5 shipped](ant_buster_plan_5_shipped.md) — Royal Guard subtype + King-fight rebalance: new ant kind dodges Reaper instakill, restores spatial-placement tradeoff at wave 25; merged to main 2026-05-08 as [MERGE-SHA], theme version 0.11.0
```

- [ ] **Step 5: Commit status doc + auto-memory**

```bash
git add docs/superpowers/plans/2026-05-08-ant-buster-plan-5-status.md
git commit -m "docs(ant-buster): Plan 5 status — shipped (Plan 5 Task 6)"
```

(The auto-memory files outside the theme repo do not get committed to git.)

- [ ] **Step 6: Merge to main**

After all tasks pass and the status doc is committed:

```bash
git checkout main
git merge --no-ff ant-buster-plan-5 -m "Merge branch 'ant-buster-plan-5' into main

Plan 5 (Royal Guard & King-fight rebalance) — restores King-fight
multi-tasking tension by introducing a royal_guard ant kind that the
King's drip-spawn produces instead of worker. Bypasses Reaper's
kind === 'worker' instakill without nerfing the Reaper's identity.
Theme version 0.11.0."
git status
```

Capture the merge SHA from `git log -1 --format=%H` and back-fill it into the auto-memory file and the status doc's commit table.

Do not push to the remote unless the user explicitly asks.

---

## Self-review notes

**Spec coverage check:** every spec §8 acceptance criterion maps to a task:
- AC #1 (royal_guard row exists with §4 stats) → Task 1
- AC #2 (King onSpawn spawns royal_guard) → Task 4
- AC #3 (Reaper damageModifier returns 2/1 for royal_guard, no special case) → Task 1 verify step
- AC #4 (royalGuardSplat exists, blue corpse renders) → Tasks 2 + 3 + 4 visual sanity check
- AC #5 (royal_guard sprite exists, crown overlay renders) → Tasks 2 + 4 visual sanity check
- AC #6 (canPickup true, flee-sprint at 1.5×) → Task 1 (data) + Task 4 (in-game observation if a Royal Guard reaches the cake)
- AC #7 (PHP lint passes) → every task verify step + Task 5 sweep
- AC #8 (hygiene check passes) → every task verify step + Task 5 sweep
- AC #9 (theme version bumped) → Task 5
- AC #10 (wave 25 playthrough records failure modes) → Task 6 step 1

**Type/name consistency:** `royal_guard` (snake_case ant kind), `royalGuardSplat` and `royal_guard` (camelCase sprite-table keys to match existing convention — `splat`/`royalGuardSplat` and `worker`/`royal_guard`). Both factories named with the JS factory convention (`make*`). Spec § references match the design doc's section numbering.

**No placeholders:** every step has exact code, exact paths, exact commands, exact expected output. The Task 6 playtest table is empty by design — the "fill in during the test" instruction is a deliberate live-data hand-off, not a placeholder.
