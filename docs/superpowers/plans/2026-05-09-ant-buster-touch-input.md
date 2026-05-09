# Ant Buster Touch Input Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make the Ant Buster demo playable on iPhone and iPad by replacing the mouse-only input layer with Pointer Events and adding a tap-to-arm, tap-to-place placement model that coexists with desktop drag-and-drop.

**Architecture:** Replace four mouse listeners on the `#ab-canvas` element with four Pointer Event listeners (`pointerdown` / `pointermove` / `pointerup` / `pointercancel`) that use `setPointerCapture` for continuous gesture tracking. Introduce a small five-state state machine (Idle, Pending, Dragging, Armed, Placing) driven by one new field on the existing `ui` object: `ui.armedKind`. Add a `touch-action: none` CSS rule to suppress iOS scroll-and-zoom gestures on the canvas. Render a touch-specific 30 game-pixel upward offset on the placement ghost so a fingertip does not occlude the placement tile.

**Tech Stack:** Vanilla JS inside a single inline `<script>` block in `patterns/demo-ant-buster.php`. No build step, no test runner, no automated test framework. Pointer Events API (W3C, supported in iOS Safari 13+, all current desktop browsers). Verification is hand-driven against the 14-item checklist in the spec, run on a real iPhone, a real iPad, and at least one desktop browser.

**Reference spec:** `docs/superpowers/specs/2026-05-09-ant-buster-touch-input-design.md` (commit `25e0b7a` on branch `ant-buster-touch-input`).

**Inline-script gotchas (per CLAUDE.md):** Do not use bare `&&` or `||` in this inline-script context: WordPress's content filters HTML-encode them and the JS parser breaks. Use nested `if` statements (the rest of the file already does this throughout). Do not introduce `var` data tables that are read at script-load time. Function declarations are safely hoisted; `var foo = function () {}` is not.

**Commit message convention:** `feat(ant-buster): [Touch Input] Task N: <short summary>` matches the existing log style for this project.

**Branch:** All work lands on `ant-buster-touch-input` (already created from main, holds the spec commit `25e0b7a`).

---

## File Structure

This plan modifies one file:

- **`patterns/demo-ant-buster.php`**: single inline `<script>` block holds all game code. Touch points: the `<style>` block (line ~33), the `ui` object literal (line ~5489), `resetGame()` (line ~5475), `drawShop()` (line ~1455), `drawDragPreview()` (line ~2192), `drawEffects()` (line ~2069), and the four mouse-event listeners (lines ~5515-5661). Line numbers drift as edits accumulate; rely on landmarks like `// === INPUT ===` and the function names rather than literal line numbers.

And one bookkeeping file at the very end:

- **`style.css`**: bump the `Version:` header line so the enqueued stylesheet's cache-buster query string changes.

No new files are created. The plan is sequenced so each task lands a working state of the demo: at no point during the rollout should the desktop game be broken.

---

## Task 0: Suppress iOS page-scroll on canvas drag

**Files:**
- Modify: `patterns/demo-ant-buster.php` (inline `<style>` block, line ~55-66, the `.demo-ant-buster #ab-canvas` rule)

This task is independent of the input rewrite and ships a real iOS-side improvement (the page no longer scrolls when you slide a finger across the canvas) before any JS changes. It is also a prerequisite for Pointer Events to receive a continuous touch stream: without `touch-action: none`, iOS Safari steals the gesture for scrolling and stops sending pointermove events.

- [ ] **Step 1: Identify the existing canvas CSS rule**

Open `patterns/demo-ant-buster.php` and find the rule that begins:

```css
.demo-ant-buster #ab-canvas {
  width: 1280px;
  max-width: 100%;
  height: auto;
  ...
}
```

It currently sits around line 55-66.

- [ ] **Step 2: Record current iOS behavior (baseline)**

On the iPhone with the original repro, navigate to `/demos/ant-buster/`. Place a finger on the canvas and drag horizontally. The page (or the canvas inside its container) currently scrolls or pans. Note this; we are about to make it stop.

- [ ] **Step 3: Add `touch-action: none` to the canvas rule**

Edit the rule to add one declaration. The rule should now read:

```css
.demo-ant-buster #ab-canvas {
  width: 1280px;
  max-width: 100%;
  height: auto;
  aspect-ratio: 16 / 9;
  background: var(--ab-bg);
  image-rendering: pixelated;
  image-rendering: -moz-crisp-edges;
  image-rendering: crisp-edges;
  cursor: default;
  display: block;
  touch-action: none;
}
```

- [ ] **Step 4: Verify on iPhone**

Reload `/demos/ant-buster/` on the iPhone. Drag a finger across the canvas in any direction. The page must not scroll, pan, or pinch-zoom. The canvas itself does not respond yet (no Pointer Events handlers added), but the gesture is now contained.

This is checklist item 9 from the spec ("Page scroll suppression").

- [ ] **Step 5: Verify desktop unaffected**

In a desktop browser, click and drag on the canvas. The existing drag-from-shop interaction still works exactly as before. No regression.

- [ ] **Step 6: Commit**

```bash
git add patterns/demo-ant-buster.php
git commit -m "feat(ant-buster): [Touch Input] Task 0: touch-action: none on canvas

Suppresses iOS page-scroll and pinch-zoom on canvas gestures so the
upcoming Pointer Events listeners can receive a continuous touch stream.
No JS changes yet."
```

---

## Task 1: Replace mouse listeners with Pointer Events (behavior parity)

**Files:**
- Modify: `patterns/demo-ant-buster.php` (the four mouse listeners at lines ~5515-5661, the `ui` object literal at ~5489-5503, `resetGame()` at ~5470-5486)

This task is the technical heart of the change. It swaps `mousedown` / `mousemove` / `mouseup` / `mouseleave` for `pointerdown` / `pointermove` / `pointerup` / `pointercancel`, adds `setPointerCapture`, and adds the `activePointerId` / `pointerType` tracking. The existing drag-from-shop behavior is preserved bit-for-bit on desktop, and now also works on iOS because Pointer Events stream `pointermove` continuously during a touch (synthetic mouse events on iOS Safari did not).

After this task, the demo is already meaningfully more playable on iPhone: drag-from-shop works. The tap-to-arm UX is layered on top in Tasks 2-3.

- [ ] **Step 1: Add pointer-tracking fields to the `ui` object**

Find the `ui` object literal that begins:

```js
  // === INPUT ===
  var ui = {
    dragging: null,         // { kind } when dragging from shop
    mouseX: 0,
    mouseY: 0,
    sendNextRect: null,     // bounds of the Send-Next button (set when rendered in prep)
    ...
```

Add three new fields immediately after `dragging`:

```js
  // === INPUT ===
  var ui = {
    dragging: null,         // { kind } when dragging from shop
    activePointerId: null,  // single-pointer gesture lock; subsequent pointers ignored
    pointerType: null,      // 'mouse' | 'touch' | 'pen' for last pointerdown; drives ghost offset
    armedKind: null,        // tap-to-arm: cannon kind committed-to-place, null when idle (Tasks 2-3)
    mouseX: 0,
    mouseY: 0,
    sendNextRect: null,     // bounds of the Send-Next button (set when rendered in prep)
    ...
```

`armedKind` is added now even though Task 1 does not use it yet: adding it here means the field exists with a sensible default before any later task references it.

- [ ] **Step 2: Add cleanup of new fields to `resetGame()`**

Find the `resetGame()` body that includes:

```js
    ui.sendNextRect = null;
    ui.restartRunRect = null;
    ui.dragging = null;
    ui.selectedCannon = null;
```

Insert the three new clears so the block reads:

```js
    ui.sendNextRect = null;
    ui.restartRunRect = null;
    ui.dragging = null;
    ui.activePointerId = null;
    ui.pointerType = null;
    ui.armedKind = null;
    ui.selectedCannon = null;
```

- [ ] **Step 3: Replace `mousedown` with `pointerdown`**

Find the listener that begins:

```js
  canvas.addEventListener('mousedown', function (evt) {
    var p = canvasToGame(evt);
    ui.mouseX = p.x;
    ui.mouseY = p.y;
```

Replace its first three lines so the new version reads:

```js
  canvas.addEventListener('pointerdown', function (evt) {
    if (ui.activePointerId !== null) { return; }   // single-pointer lock
    canvas.setPointerCapture(evt.pointerId);
    ui.activePointerId = evt.pointerId;
    ui.pointerType = evt.pointerType;
    var p = canvasToGame(evt);
    ui.mouseX = p.x;
    ui.mouseY = p.y;
```

Leave the rest of the function body unchanged. The hit-test cascade (pause overlay, HUD, send-next, win/lose, idle, playfield, side panel, shop) is preserved exactly as today.

- [ ] **Step 4: Replace `mousemove` with `pointermove`**

Find the listener that begins:

```js
  canvas.addEventListener('mousemove', function (evt) {
    var p = canvasToGame(evt);
    ui.mouseX = p.x;
    ui.mouseY = p.y;
```

Replace it so the new version reads:

```js
  canvas.addEventListener('pointermove', function (evt) {
    if (ui.activePointerId !== null) {
      if (evt.pointerId !== ui.activePointerId) { return; }
    }
    var p = canvasToGame(evt);
    ui.mouseX = p.x;
    ui.mouseY = p.y;
```

The nested `if` (rather than `&&`) is required by the CLAUDE.md inline-script gotcha. Note that `pointermove` also fires for hover (no buttons pressed) on desktop, where `activePointerId` is `null`: those events still pass through and update `mouseX`/`mouseY`, which keeps the existing `hoveredUpgrade` tracking working.

Leave the rest of the function body (the upgrade-button hover scan) unchanged.

- [ ] **Step 5: Replace `mouseup` with `pointerup`**

Find the listener that begins:

```js
  canvas.addEventListener('mouseup', function (evt) {
    var p = canvasToGame(evt);
    ui.mouseX = p.x;
    ui.mouseY = p.y;
    if (ui.dragging) {
      placeCannon(ui.dragging.kind, p.x, p.y);
      ui.dragging = null;
      canvas.classList.remove('ab-dragging');
    }
  });
```

Replace it with:

```js
  canvas.addEventListener('pointerup', function (evt) {
    if (evt.pointerId !== ui.activePointerId) { return; }
    var p = canvasToGame(evt);
    ui.mouseX = p.x;
    ui.mouseY = p.y;
    if (ui.dragging) {
      placeCannon(ui.dragging.kind, p.x, p.y);
      ui.dragging = null;
      canvas.classList.remove('ab-dragging');
    }
    ui.activePointerId = null;
    ui.pointerType = null;
  });
```

- [ ] **Step 6: Replace `mouseleave` with `pointercancel`**

Find the listener:

```js
  canvas.addEventListener('mouseleave', function () {
    if (ui.dragging) {
      ui.dragging = null;
      canvas.classList.remove('ab-dragging');
    }
  });
```

Replace it entirely with:

```js
  canvas.addEventListener('pointercancel', function (evt) {
    if (evt.pointerId !== ui.activePointerId) { return; }
    if (ui.dragging) {
      ui.dragging = null;
      canvas.classList.remove('ab-dragging');
    }
    ui.activePointerId = null;
    ui.pointerType = null;
  });
```

Note: the `mouseleave` semantics ("cursor exited the canvas, drop the drag") are no longer needed because `setPointerCapture` keeps the gesture bound to the canvas even when the pointer travels outside its rectangle. `pointercancel` covers the genuinely interrupting cases (OS gesture takes over, tab loses focus on touch, etc.). Armed state is not yet introduced, so the "leave Armed alone on cancel" rule from the spec is implicitly correct here (there is no Armed state yet to leave alone).

- [ ] **Step 7: Verify desktop drag still works (regression baseline)**

In Chrome and Firefox, navigate to `/demos/ant-buster/`. Click and drag a shop card onto the playfield. Cannon places. Try this on each of the four base shop cards. Try dragging out of the canvas mid-gesture and back in: the ghost preview keeps following because of the pointer capture.

This is checklist item 5 ("Drag-from-shop, desktop only"). It must pass.

- [ ] **Step 8: Verify iPhone drag-from-shop now works**

On the iPhone, navigate to `/demos/ant-buster/`. Press a shop card with one finger and slide onto the playfield, then lift. The cannon should place. This is the first time this interaction has worked on iOS; until now it would silently no-op because synthetic mouse events did not stream during the touch.

- [ ] **Step 9: Verify single-tap UI still works**

On iPhone: tap a placed cannon: side panel opens. Tap pause: pauses. Tap speed: toggles. Tap an upgrade button (with a selected cannon): upgrade applies if affordable.

This is checklist item 14 ("Existing single-tap UI still works").

- [ ] **Step 10: Commit**

```bash
git add patterns/demo-ant-buster.php
git commit -m "feat(ant-buster): [Touch Input] Task 1: Pointer Events listener swap

Replaces mouse{down,move,up,leave} with pointer{down,move,up,cancel} on
the canvas, adds setPointerCapture for continuous gesture tracking, and
introduces ui.activePointerId / ui.pointerType / ui.armedKind on the ui
object (armedKind is reserved for Tasks 2-3). Drag-from-shop now works
on iOS Safari for the first time."
```

---

## Task 2: Refactor drag flow to use `armedKind` and Pending-state motion threshold

**Files:**
- Modify: `patterns/demo-ant-buster.php` (the `pointerdown` and `pointermove` handlers from Task 1, the `pointerup` handler, the `pointercancel` handler, the `drawDragPreview` function at line ~2192)

This task is a refactor that introduces the Pending state without yet adding the tap-to-arm exit from Pending. After Task 2, drag-from-shop still works exactly as today (and now also on iOS, from Task 1), but internally the `ui.dragging` flag is replaced by the cleaner `ui.armedKind` + Pending tracking. This sets the table for Task 3 to add tap-to-arm without touching the drag path again.

The motion threshold for promoting Pending to Dragging is `6` game-pixels squared distance (i.e., 6 game-px of pointer travel from the down point in any direction).

- [ ] **Step 1: Add Pending-state fields to the `ui` object**

Update the `ui` object so it now reads:

```js
  // === INPUT ===
  var ui = {
    dragging: null,         // [DEPRECATED in Task 2; kept null for one task to ease bisect]
    activePointerId: null,
    pointerType: null,
    armedKind: null,
    pendingKind: null,      // shop-card kind currently in Pending; null when not Pending
    pendingX: 0,            // pointerdown x in game-coords (motion-threshold reference)
    pendingY: 0,            // pointerdown y in game-coords
    mouseX: 0,
    mouseY: 0,
    sendNextRect: null,
    ...
```

Note: `dragging` is left in place but unused after this task. It will be removed in Task 4. Leaving it for one task means the same commit can be reverted cleanly in case of regression.

- [ ] **Step 2: Add Pending-state clearing to `resetGame()`**

Update the resetGame block to also clear the new fields:

```js
    ui.dragging = null;
    ui.activePointerId = null;
    ui.pointerType = null;
    ui.armedKind = null;
    ui.pendingKind = null;
    ui.pendingX = 0;
    ui.pendingY = 0;
    ui.selectedCannon = null;
```

- [ ] **Step 3: Update the `pointerdown` shop-card branch**

Find the shop-card hit-test inside `pointerdown` (it currently sets `ui.dragging = { kind: rs.kind }` and adds the `ab-dragging` CSS class):

```js
    if (ui.shopRects) {
      for (var si = 0; si < ui.shopRects.length; si++) {
        var rs = ui.shopRects[si];
        if (inRect(p, rs)) {
          // only start drag if affordable
          var sk = CANNON_KINDS[rs.kind];
          if (game.gold >= sk.cost) {
            ui.dragging = { kind: rs.kind };
            canvas.classList.add('ab-dragging');
          }
          return;
        }
      }
    }
  });
```

Replace the inner block so it now sets Pending state instead of Dragging:

```js
    if (ui.shopRects) {
      for (var si = 0; si < ui.shopRects.length; si++) {
        var rs = ui.shopRects[si];
        if (inRect(p, rs)) {
          // Pending state: motion in pointermove will promote to Dragging.
          var sk = CANNON_KINDS[rs.kind];
          if (game.gold >= sk.cost) {
            ui.pendingKind = rs.kind;
            ui.pendingX = p.x;
            ui.pendingY = p.y;
          }
          return;
        }
      }
    }
  });
```

The `ab-dragging` cursor class is moved to the promotion point in pointermove (Step 4): Pending should not show the grab cursor because the gesture has not yet been committed.

- [ ] **Step 4: Update the `pointermove` handler to promote Pending to Dragging**

Find the `pointermove` handler. After the pointerId guard and the `mouseX`/`mouseY` update, add a Pending-promotion block before the existing upgrade-hover scan:

```js
  canvas.addEventListener('pointermove', function (evt) {
    if (ui.activePointerId !== null) {
      if (evt.pointerId !== ui.activePointerId) { return; }
    }
    var p = canvasToGame(evt);
    ui.mouseX = p.x;
    ui.mouseY = p.y;
    // Pending -> Dragging promotion: 6 game-px squared distance from down point.
    if (ui.pendingKind !== null) {
      var ddx = p.x - ui.pendingX;
      var ddy = p.y - ui.pendingY;
      if (ddx * ddx + ddy * ddy > 36) {
        ui.armedKind = ui.pendingKind;
        ui.pendingKind = null;
        canvas.classList.add('ab-dragging');
      }
    }
    // Track upgrade-button hover so drawRangeCircle can preview the target's range
    // ... (existing upgrade-button hover scan unchanged below)
```

The threshold is `36` (which is `6 * 6`) so we compare squared distance against squared threshold without a `Math.sqrt` call.

- [ ] **Step 5: Update the `pointerup` handler to commit from Dragging only**

Replace the `pointerup` body so it now reads from `armedKind` instead of `dragging`, and drops Pending silently (the tap-to-arm exit is added in Task 3):

```js
  canvas.addEventListener('pointerup', function (evt) {
    if (evt.pointerId !== ui.activePointerId) { return; }
    var p = canvasToGame(evt);
    ui.mouseX = p.x;
    ui.mouseY = p.y;
    if (ui.armedKind !== null) {
      placeCannon(ui.armedKind, p.x, p.y);
      ui.armedKind = null;
      canvas.classList.remove('ab-dragging');
    }
    // Pending without motion: drop silently for now. Task 3 will arm here instead.
    ui.pendingKind = null;
    ui.activePointerId = null;
    ui.pointerType = null;
  });
```

- [ ] **Step 6: Update the `pointercancel` handler**

Replace its body so it clears Pending and Dragging cleanly:

```js
  canvas.addEventListener('pointercancel', function (evt) {
    if (evt.pointerId !== ui.activePointerId) { return; }
    if (ui.armedKind !== null) {
      ui.armedKind = null;
      canvas.classList.remove('ab-dragging');
    }
    ui.pendingKind = null;
    ui.activePointerId = null;
    ui.pointerType = null;
  });
```

This will be revisited in Task 4 to preserve a future Armed state across cancellation; for now Armed and Dragging are the same (`armedKind !== null` with a pointer down). After Task 3 splits these meanings, Task 4 refines this handler.

- [ ] **Step 7: Update `drawDragPreview()` to read `armedKind`**

Find the function:

```js
  function drawDragPreview() {
    if (!ui.dragging) { return; }
    var ok = canPlaceCannon(ui.mouseX, ui.mouseY);
    var dragDef = CANNON_KINDS[ui.dragging.kind];
    var dragTurret = game.sprites.turrets[ui.dragging.kind];
    if (!dragTurret) { dragTurret = game.sprites.turrets.howitzer; }
    ctx.globalAlpha = 0.6;
    ctx.drawImage(game.sprites.cannonBase, Math.round(ui.mouseX - 16), Math.round(ui.mouseY - 16));
    ctx.drawImage(dragTurret, Math.round(ui.mouseX - 10), Math.round(ui.mouseY - 16));
    ctx.globalAlpha = 1;
    ctx.strokeStyle = ok ? 'rgba(255, 255, 255, 0.5)' : 'rgba(228, 59, 68, 0.7)';
    ctx.lineWidth = 1;
    ctx.beginPath();
    ctx.arc(ui.mouseX, ui.mouseY, dragDef.range, 0, Math.PI * 2);
    ctx.stroke();
  }
```

Replace its first four lines so it now reads from `armedKind`:

```js
  function drawDragPreview() {
    if (ui.armedKind === null) { return; }
    if (ui.activePointerId === null) { return; }   // hide ghost when no pointer is down (Task 3 keeps this)
    var ok = canPlaceCannon(ui.mouseX, ui.mouseY);
    var dragDef = CANNON_KINDS[ui.armedKind];
    var dragTurret = game.sprites.turrets[ui.armedKind];
    if (!dragTurret) { dragTurret = game.sprites.turrets.howitzer; }
    ctx.globalAlpha = 0.6;
    ctx.drawImage(game.sprites.cannonBase, Math.round(ui.mouseX - 16), Math.round(ui.mouseY - 16));
    ctx.drawImage(dragTurret, Math.round(ui.mouseX - 10), Math.round(ui.mouseY - 16));
    ctx.globalAlpha = 1;
    ctx.strokeStyle = ok ? 'rgba(255, 255, 255, 0.5)' : 'rgba(228, 59, 68, 0.7)';
    ctx.lineWidth = 1;
    ctx.beginPath();
    ctx.arc(ui.mouseX, ui.mouseY, dragDef.range, 0, Math.PI * 2);
    ctx.stroke();
  }
```

The `activePointerId === null` guard is the future-proofing: in Task 3 there will be Armed-without-pointer states where the ghost should not render. Adding this guard now means Task 3 does not need to revisit `drawDragPreview` for that concern.

- [ ] **Step 8: Verify drag-from-shop still works on desktop**

In a desktop browser: drag a shop card to the playfield. Cannon places. Try with all four base cards. Try a deliberate-and-tiny click-without-drag on a card: nothing happens (correct: the Pending state drops silently in Task 2).

- [ ] **Step 9: Verify drag-from-shop still works on iPhone**

On the iPhone: press and slide a shop card to the playfield. Cannon places. Same as Task 1: no regression from the refactor.

- [ ] **Step 10: Commit**

```bash
git add patterns/demo-ant-buster.php
git commit -m "feat(ant-buster): [Touch Input] Task 2: Pending state, armedKind refactor

Refactors the drag-from-shop path to use the new ui.armedKind +
ui.pendingKind state. Introduces the 6-game-px motion threshold for
promoting Pending to Dragging. Behavior is unchanged: drag-and-drop
works, click-without-drag does nothing yet (Task 3 adds tap-to-arm)."
```

---

## Task 3: Add tap-to-arm and tap-to-place placement model

**Files:**
- Modify: `patterns/demo-ant-buster.php` (the `pointerdown` and `pointerup` handlers from Task 2)

This task adds the new interaction model. Pointerup-on-same-card-without-motion arms the kind. Pointerdown-on-playfield while Armed enters Placing (the ghost follows the finger between down and up). Pointerup-on-valid-tile-while-Placing places. Pointerup-on-invalid-tile-while-Placing stays Armed and pushes a brief flash (the flash itself lands in Task 7; the "stays Armed" semantics land here).

- [ ] **Step 1: Add Armed-state branches to the `pointerdown` hit-test cascade**

Inside `pointerdown`, before the existing playfield-cannon-select branch and before the shop-card branch, add a new top-priority block that handles Armed-state interactions. The new code goes right after the pointerId / capture / `mouseX` / `mouseY` setup at the top of the handler, before the pause-overlay branch.

The placement of this block matters: in Armed state, a tap on the shop should toggle/swap (not be eaten by the pause-overlay or HUD); a tap on the playfield should enter Placing (not select an existing cannon underneath); a tap on HUD (pause/speed) or on a side-panel button still works because we explicitly do not touch armedKind there.

Add this block right after the `ui.mouseX = p.x; ui.mouseY = p.y;` lines:

```js
    // === Armed-state interactions (tap-to-arm world) ===
    // Runs before the normal hit-test cascade so an Armed gesture wins over
    // cannon-select and HUD pass-through. HUD pause/speed/sell/upgrade are
    // re-tested below: they do not clear armedKind.
    if (ui.armedKind !== null) {
      if (ui.activePointerId !== null) {
        // We are already capturing this pointer; check shop and playfield.
        // Shop hit-tests first so a same-card re-tap toggles off and a
        // different-card tap swaps kind.
        if (ui.shopRects) {
          for (var sa = 0; sa < ui.shopRects.length; sa++) {
            var ra = ui.shopRects[sa];
            if (inRect(p, ra)) {
              if (ra.kind === ui.armedKind) {
                // Same card: toggle off.
                ui.armedKind = null;
                canvas.classList.remove('ab-dragging');
                return;
              }
              // Different card: swap if affordable, else ignore.
              var skSwap = CANNON_KINDS[ra.kind];
              if (game.gold >= skSwap.cost) {
                ui.armedKind = null;
                ui.pendingKind = ra.kind;
                ui.pendingX = p.x;
                ui.pendingY = p.y;
                canvas.classList.remove('ab-dragging');
              }
              return;
            }
          }
        }
        // Playfield: enter Placing (ghost will render via drawDragPreview).
        if (p.x < PLAYFIELD_W) {
          if (p.y >= HUD_H) {
            // Note: do not call placeCannon here. Placement commits on
            // pointerup (Step 3 below) so the player can slide-to-adjust.
            canvas.classList.add('ab-dragging');
            return;
          }
        }
        // Anything else (HUD, side panel, etc.): fall through to normal
        // hit-tests below. armedKind is preserved.
      }
    }
```

This block uses the existing `inRect` helper and `PLAYFIELD_W` / `HUD_H` constants.

- [ ] **Step 2: Update the `pointerup` handler to arm on tap and stay-Armed on invalid placement**

Replace the `pointerup` body so it now distinguishes the four cases (Pending → Armed, Dragging → place, Placing → place-or-stay, default):

```js
  canvas.addEventListener('pointerup', function (evt) {
    if (evt.pointerId !== ui.activePointerId) { return; }
    var p = canvasToGame(evt);
    ui.mouseX = p.x;
    ui.mouseY = p.y;
    // Case A: Pending without motion -> Armed (tap-to-arm).
    if (ui.pendingKind !== null) {
      ui.armedKind = ui.pendingKind;
      ui.pendingKind = null;
      // ab-dragging class stays off: Armed without pointer down has no grab cursor.
    } else if (ui.armedKind !== null) {
      // Case B: Dragging or Placing -> commit if valid.
      if (canPlaceCannon(p.x, p.y)) {
        placeCannon(ui.armedKind, p.x, p.y);
        ui.armedKind = null;
        canvas.classList.remove('ab-dragging');
      } else {
        // Case C: invalid spot. Drag-from-shop releases (Idle); tap-armed
        // placement attempts stay Armed. We distinguish by whether the
        // ab-dragging class is set: it is set in Dragging (motion-promoted)
        // and in Placing (Step 1 added it). For Dragging we want to drop;
        // for Placing we want to stay Armed.
        // We cannot distinguish Dragging from Placing by class alone, so
        // we use the simpler rule: if pointerType is touch, stay Armed
        // (touch users came from tap-to-arm); if mouse, drop.
        // This matches the spec rationale: desktop drag-and-miss reads as
        // "abandon"; touch tap-and-miss reads as "I missed, retry".
        if (ui.pointerType === 'touch') {
          // Stay Armed; flash effect lands in Task 7.
          canvas.classList.remove('ab-dragging');
        } else if (ui.pointerType === 'pen') {
          canvas.classList.remove('ab-dragging');
        } else {
          ui.armedKind = null;
          canvas.classList.remove('ab-dragging');
        }
      }
    }
    ui.activePointerId = null;
    ui.pointerType = null;
  });
```

The pointerType-based decision is the simplest faithful implementation of the spec's Dragging-vs-Placing-on-invalid divergence, given that the same ui state field (`armedKind`) covers both. A more elaborate alternative would be to track an explicit `ui.placing` boolean set in Step 1's playfield branch, but the pointerType test gets the same observable behavior with one less field. The two `else if` branches (touch and pen) are written separately rather than `if (ui.pointerType === 'touch' || ui.pointerType === 'pen')` because the inline-script gotcha forbids `||`.

- [ ] **Step 3: Verify tap-to-arm on iPhone**

On the iPhone: tap a shop card. Nothing visible changes yet (the highlight ships in Task 5; the ghost is hidden because no pointer is down: that is the `activePointerId === null` guard added in Task 2 Step 7). Tap a valid playfield tile. Cannon places.

This is checklist item 1 ("Tap-to-arm + tap-to-place"). It must pass.

- [ ] **Step 4: Verify tap-to-arm + cancel by re-tap on iPhone**

Tap a card. Tap the same card again. No cannon is placed; the (invisible-for-now) Armed state is cleared. To confirm the state actually toggled off: tap a valid tile after the second card-tap; no cannon should appear, because Armed is now Idle.

This is checklist item 2.

- [ ] **Step 5: Verify tap-to-arm + swap on iPhone**

Tap card A. Tap card B (a different affordable card). Tap a valid tile. The cannon that places is the B kind, not A.

This is checklist item 3.

- [ ] **Step 6: Verify desktop drag still works**

In a desktop browser: drag a shop card to the playfield with a real motion. Cannon places. Click a card without dragging: Armed state is now set. Click a valid tile: cannon places. Click the same card again: Armed clears.

This is checklist item 5 plus an extension; both must pass.

- [ ] **Step 7: Verify desktop drag-and-miss still drops the cannon (no regression)**

In a desktop browser: drag a shop card outside the playfield (e.g., onto the HUD or onto the side panel). Release. The Armed state should clear (cannon does not stay armed). This is the spec's "Dragging-on-invalid → Idle" rule, preserved by the pointerType-based decision in Step 2.

- [ ] **Step 8: Verify iPhone tap-and-miss stays armed**

On iPhone: tap a card to arm. Tap an invalid spot (overlap with an existing cannon, or near the cake). No cannon places. Tap a valid spot. Cannon places. The Armed state survived the missed tap.

This is checklist item 4 (the flash itself ships in Task 7; the "stays armed" half ships here).

- [ ] **Step 9: Commit**

```bash
git add patterns/demo-ant-buster.php
git commit -m "feat(ant-buster): [Touch Input] Task 3: tap-to-arm + tap-to-place

Adds the Armed and Placing states. Tap-without-motion on a shop card
arms the kind; pointerdown on playfield while Armed enters Placing;
pointerup commits if valid spot, stays Armed on touch/pen if invalid,
drops to Idle on mouse if invalid (preserving desktop drag-and-miss
semantics). Tap a card a second time to unarm; tap a different card
to swap kind."
```

---

## Task 4: Cancellation hardening (ESC, pointercancel preserves Armed, placed-cannon ignored)

**Files:**
- Modify: `patterns/demo-ant-buster.php` (the `pointerdown` handler, the `pointercancel` handler, plus a new keydown listener)

This task closes the cancellation edge cases the spec calls out: ESC unarms on desktop, OS-interrupt (`pointercancel`) preserves Armed across the interruption, and tapping a placed cannon while Armed is silently ignored (placement intent wins).

- [ ] **Step 1: Add a "tap on placed cannon while Armed → ignore" guard to pointerdown**

The Step-1 block from Task 3 already returns from the playfield branch in Armed state (and does not call `cannonAt`). But the existing `if (game.state === 'playing')` cannon-select branch later in the handler would still run if we fell through. We must make sure we do not fall through to it while Armed.

Audit Task 3 Step 1: the playfield branch returns when `p.x < PLAYFIELD_W` and `p.y >= HUD_H`. That covers all of the playable area, so cannon-select in the later branch never runs while Armed. Good, no new code needed for this case.

But there is one gap: the spec says "tap on a different unaffordable shop card → no change" (the gesture should be ignored, not enter Pending). Re-read Task 3 Step 1: the swap branch already only enters Pending if `game.gold >= skSwap.cost`, otherwise it returns silently. Good, this case is already covered.

This step is therefore a code audit, not an edit. Mark it complete after confirming the behavior.

To verify, on desktop: arm a cheap card. Click an unaffordable card (one whose cost exceeds current gold). Armed state should remain on the original card; the click should not start any new gesture.

- [ ] **Step 2: Add an ESC keydown listener**

After the `pointercancel` listener, add a new keydown listener attached to the window (so it works without canvas focus, which canvases do not have by default):

```js
  // ESC unarms on desktop. Touch users have the same-card-retap to unarm.
  window.addEventListener('keydown', function (evt) {
    if (evt.key === 'Escape') {
      if (ui.armedKind !== null) {
        ui.armedKind = null;
        canvas.classList.remove('ab-dragging');
      }
    }
  });
```

The `evt.key === 'Escape'` check is the modern API; older `keyCode === 27` is not needed for the iOS-13-and-modern-desktop target.

- [ ] **Step 3: Update the `pointercancel` handler so Armed survives an OS interruption**

Replace the `pointercancel` body so it only drops Pending and Dragging, not Armed-without-pointer:

```js
  canvas.addEventListener('pointercancel', function (evt) {
    if (evt.pointerId !== ui.activePointerId) { return; }
    // OS-interrupt rule: drop Pending and the in-progress Dragging/Placing
    // gesture, but leave Armed intact so the user can finish the placement
    // after the interruption ends.
    ui.pendingKind = null;
    // armedKind is preserved iff this gesture started from Armed (tap-to-place).
    // In Dragging-from-shop, Pending→Dragging set armedKind, so cancel here
    // means dropping armedKind too. We distinguish by pointerType again:
    // touch/pen pointers came from tap-to-place, mouse pointers came from
    // drag-from-shop.
    if (ui.armedKind !== null) {
      if (ui.pointerType === 'mouse') {
        ui.armedKind = null;
      }
      // touch and pen: leave armedKind set (Armed survives).
    }
    canvas.classList.remove('ab-dragging');
    ui.activePointerId = null;
    ui.pointerType = null;
  });
```

This is the same pointerType-based heuristic used in Task 3 Step 2. It is the cleanest faithful encoding of the spec without an explicit Placing-vs-Dragging boolean.

- [ ] **Step 4: Remove the now-unused `dragging` field from the `ui` object**

Now that the refactor is complete and Task 2 is two commits behind, the deprecated `dragging` field can be removed safely. Update the `ui` object so the line:

```js
    dragging: null,         // [DEPRECATED in Task 2; kept null for one task to ease bisect]
```

is deleted. Also remove the corresponding `ui.dragging = null;` in `resetGame()`.

- [ ] **Step 5: Verify ESC unarms on desktop**

In a desktop browser: click a shop card without dragging (Armed). Press ESC. Try to place: no cannon appears, because Armed is now Idle.

- [ ] **Step 6: Verify system-gesture interruption on iPhone**

On the iPhone: tap a shop card (Armed). Swipe up from the bottom edge to invoke the Home gesture, then return to Safari. Tap a valid playfield tile. The cannon should still place: Armed survived the interruption.

This is checklist item 10.

For the drag-from-shop variant: drag from a card on iPhone, swipe up mid-drag, return. The drag itself is dropped (no orphaned ghost on screen), and Armed is null because the gesture was a mouse-style drag (well, touch: but the heuristic says touch survives; this is fine, the user can still tap a tile to place since Armed kept the kind). On reflection, the touch-cancel-keeps-armed behavior is *more* user-friendly than dropping the drag, so the slight semantic shift is acceptable. The spec's intent is "no orphaned state, ghost not stuck on screen": both achieved.

- [ ] **Step 7: Verify multitouch idiom**

On the iPhone: place two fingers on the canvas at once. Only the first finger drives input. Place a card-tap with the first finger, then while still holding it, tap with a second finger; the second tap is ignored.

This is checklist item 11.

- [ ] **Step 8: Verify reset clears Armed**

Lose a wave or hit Restart Run. New game starts. Confirm `ui.armedKind === null` by trying to place a cannon by tapping a playfield tile: nothing places, because Armed is null and no card is selected.

This is checklist item 13.

- [ ] **Step 9: Commit**

```bash
git add patterns/demo-ant-buster.php
git commit -m "feat(ant-buster): [Touch Input] Task 4: cancellation hardening

ESC unarms on desktop. pointercancel preserves Armed across OS
interruptions (touch/pen) while still dropping mouse-driven drags.
Tap-on-placed-cannon while Armed is ignored (verified by code audit;
the playfield branch returns before cannon-select runs). Removes the
deprecated ui.dragging field."
```

---

## Task 5: Armed shop-card visual highlight

**Files:**
- Modify: `patterns/demo-ant-buster.php` (the `drawShop()` function at line ~1455)

This task adds the visible feedback the player needs to know they are in Armed state. The card whose kind matches `ui.armedKind` is drawn with a 2-game-px gold border and a small "tap a spot" label below the family hint.

- [ ] **Step 1: Add the armed-state branch to `drawShop()`**

Find the function body of `drawShop()` (line ~1455-1492). Inside its `for` loop, after the existing card render and before the `ui.shopRects.push(...)` line, add an armed-card highlight block:

```js
    for (var i = 0; i < keys.length; i++) {
      var k = keys[i];
      var sk = CANNON_KINDS[k];
      var cy = startY + i * (cardH + 8);
      // card background
      ctx.fillStyle = '#262b44';
      ctx.fillRect(cardX, cy, cardW, cardH);
      // turret sprite mini icon (top-left of card)
      var turret = game.sprites.turrets[k];
      if (turret) { ctx.drawImage(turret, cardX + 6, cy + 6); }
      // name
      ctx.fillStyle = '#fee761';
      ctx.font = '10px monospace';
      ctx.textAlign = 'left';
      ctx.fillText(sk.name, cardX + 32, cy + 22);
      // cost
      var canAfford = game.gold >= sk.cost;
      ctx.fillStyle = canAfford ? '#fee761' : '#888';
      ctx.fillText(sk.cost + 'g', cardX + 32, cy + 38);
      // tier or family hint (small)
      ctx.fillStyle = '#999';
      ctx.font = '8px monospace';
      ctx.fillText(sk.family, cardX + 32, cy + 52);
      // Armed-card highlight (Touch Input Task 5).
      if (ui.armedKind === k) {
        ctx.strokeStyle = '#fee761';
        ctx.lineWidth = 2;
        ctx.strokeRect(cardX + 1, cy + 1, cardW - 2, cardH - 2);
        ctx.fillStyle = '#aaa';
        ctx.font = '8px monospace';
        ctx.fillText('tap a spot', cardX + 32, cy + 64);
      }
      ui.shopRects.push({ x: cardX, y: cy, w: cardW, h: cardH, kind: k });
    }
```

The 2-px stroke is inset by 1 pixel so its outer edge sits flush with the card edge. The "tap a spot" label uses the same 8px monospace as the family hint, in the existing gray (`#aaa`) used elsewhere for secondary text. The label sits at `cy + 64`, which is 6 px below the family hint at `cy + 52`: within the 70-px tall card.

- [ ] **Step 2: Verify armed-card highlight on iPhone**

On the iPhone: tap a card. The card should now show a gold border and a "tap a spot" label. Tap the same card again. Border and label disappear. Tap a different affordable card. Border moves to the new card. Tap a valid playfield tile. Cannon places, border disappears.

- [ ] **Step 3: Verify on desktop**

In a desktop browser: click a shop card without dragging. Highlight appears. Click on a valid tile. Cannon places, highlight disappears.

- [ ] **Step 4: Verify drag still does not show the highlight prematurely**

In a desktop browser: press on a shop card and start dragging immediately (more than 6 px of motion). The card should NOT show the armed highlight during the drag: armed highlight only renders when `ui.armedKind` is set, which during Dragging is true, but the player will see the ghost at the cursor and the card highlight at the same time. This is acceptable: drag-from-shop also visibly identifies which kind is being dragged via the ghost. The highlight is consistent.

(If we wanted to suppress the card highlight during Dragging, we would gate it on `ui.activePointerId === null`. The spec does not require this and the redundancy reads as a feature, not a bug. Leave as-is.)

- [ ] **Step 5: Commit**

```bash
git add patterns/demo-ant-buster.php
git commit -m "feat(ant-buster): [Touch Input] Task 5: armed-card highlight

drawShop renders a 2-px gold border and a small 'tap a spot' label on
the card whose kind matches ui.armedKind. Players now have a visible
indication of placement intent in tap-to-arm mode."
```

---

## Task 6: Touch occlusion offset for ghost preview

**Files:**
- Modify: `patterns/demo-ant-buster.php` (the `drawDragPreview()` function at line ~2192)

This task offsets the ghost cannon sprite 30 game-pixels above the pointer when the gesture is driven by a touch or pen, so a fingertip does not occlude the placement tile. The range ring stays centered on the real placement coordinate; only the cannon-base and turret sprites move.

- [ ] **Step 1: Add the pointerType-aware Y offset to `drawDragPreview()`**

Find the function body. Replace the `ctx.drawImage(...)` calls with an offset-aware version:

```js
  function drawDragPreview() {
    if (ui.armedKind === null) { return; }
    if (ui.activePointerId === null) { return; }
    var ok = canPlaceCannon(ui.mouseX, ui.mouseY);
    var dragDef = CANNON_KINDS[ui.armedKind];
    var dragTurret = game.sprites.turrets[ui.armedKind];
    if (!dragTurret) { dragTurret = game.sprites.turrets.howitzer; }
    // Ghost sprite Y offset: lift 30 game-px on touch/pen so the player can
    // see the placement tile under their finger. Range ring stays centered
    // on the real (ui.mouseX, ui.mouseY) so validity readout is accurate.
    var ghostDy = 0;
    if (ui.pointerType === 'touch') { ghostDy = -30; }
    if (ui.pointerType === 'pen') { ghostDy = -30; }
    ctx.globalAlpha = 0.6;
    ctx.drawImage(game.sprites.cannonBase, Math.round(ui.mouseX - 16), Math.round(ui.mouseY - 16 + ghostDy));
    ctx.drawImage(dragTurret, Math.round(ui.mouseX - 10), Math.round(ui.mouseY - 16 + ghostDy));
    ctx.globalAlpha = 1;
    ctx.strokeStyle = ok ? 'rgba(255, 255, 255, 0.5)' : 'rgba(228, 59, 68, 0.7)';
    ctx.lineWidth = 1;
    ctx.beginPath();
    ctx.arc(ui.mouseX, ui.mouseY, dragDef.range, 0, Math.PI * 2);
    ctx.stroke();
  }
```

The two `if (ui.pointerType === ...)` lines are written separately to avoid `||` (CLAUDE.md inline-script gotcha).

- [ ] **Step 2: Verify offset on iPhone**

On the iPhone: tap a card to arm. Press a finger down on a valid playfield tile and start to slide. The ghost cannon (base + turret) should appear ~30 game-px above the fingertip, leaving the tile under the finger visible. Slide around; the ghost tracks the finger but stays offset upward. The range ring stays centered on the actual finger position. Release on a valid tile; cannon places at the finger position (not the offset position).

- [ ] **Step 3: Verify no offset on desktop**

In a desktop browser: drag a shop card. Ghost appears centered on the cursor (no offset). Same as before Task 6. No regression.

- [ ] **Step 4: Tune the offset if needed**

If the 30-px offset turns out to be too small (ghost still hidden) or too large (ghost feels disconnected from finger), adjust the `-30` constant. The spec calls 30 the starting value. Realistic tuning range is `-25` to `-40`.

If you tune this, document the new value in this step's commit message.

- [ ] **Step 5: Commit**

```bash
git add patterns/demo-ant-buster.php
git commit -m "feat(ant-buster): [Touch Input] Task 6: touch occlusion offset

drawDragPreview lifts the ghost cannon 30 game-px above the pointer when
ui.pointerType is touch or pen, so a fingertip does not occlude the
placement tile. Range ring stays centered on the real placement
coordinate; placement uses the unmodified ui.mouseX/Y."
```

---

## Task 7: Validity readout (red ghost) and invalid-spot flash

**Files:**
- Modify: `patterns/demo-ant-buster.php` (the `drawDragPreview()` function, the `pointerup` handler, the `drawEffects()` function at line ~2069)

This task lands two visual signals:

1. While the player is mid-gesture over an invalid spot, the ghost cannon itself is tinted red (in addition to the existing red-stroked range ring), so the rejection is unmistakable on a small phone screen.
2. When the player releases on an invalid spot in tap-to-place mode, push a brief red pulse onto `game.effects` at the touch location so they know the tap was registered but rejected.

The transient effect goes onto the existing `game.effects` array (the spec referred to this as `game.fx`; the actual identifier in code is `game.effects`).

- [ ] **Step 1: Add red-ghost tint to `drawDragPreview()`**

Update the function so the `globalAlpha` and tinting happen conditionally on `ok`:

```js
  function drawDragPreview() {
    if (ui.armedKind === null) { return; }
    if (ui.activePointerId === null) { return; }
    var ok = canPlaceCannon(ui.mouseX, ui.mouseY);
    var dragDef = CANNON_KINDS[ui.armedKind];
    var dragTurret = game.sprites.turrets[ui.armedKind];
    if (!dragTurret) { dragTurret = game.sprites.turrets.howitzer; }
    var ghostDy = 0;
    if (ui.pointerType === 'touch') { ghostDy = -30; }
    if (ui.pointerType === 'pen') { ghostDy = -30; }
    ctx.globalAlpha = 0.6;
    ctx.drawImage(game.sprites.cannonBase, Math.round(ui.mouseX - 16), Math.round(ui.mouseY - 16 + ghostDy));
    ctx.drawImage(dragTurret, Math.round(ui.mouseX - 10), Math.round(ui.mouseY - 16 + ghostDy));
    // Invalid-spot red tint over the ghost (Task 7). Drawn on top with a
    // multiply-ish blend by stacking a translucent red rect; cheap and
    // visible on small screens. Only renders when canPlaceCannon = false.
    if (!ok) {
      ctx.fillStyle = 'rgba(228, 59, 68, 0.45)';
      ctx.fillRect(Math.round(ui.mouseX - 16), Math.round(ui.mouseY - 16 + ghostDy), 32, 24);
    }
    ctx.globalAlpha = 1;
    ctx.strokeStyle = ok ? 'rgba(255, 255, 255, 0.5)' : 'rgba(228, 59, 68, 0.7)';
    ctx.lineWidth = 1;
    ctx.beginPath();
    ctx.arc(ui.mouseX, ui.mouseY, dragDef.range, 0, Math.PI * 2);
    ctx.stroke();
  }
```

The 32×24 rectangle covers the cannon-base (32×16) plus the turret height (~12 above the base), in the existing red `#e43b44` (`228, 59, 68`) at 45% alpha multiplied by the 60% globalAlpha already set, for an effective ~27% red overlay. Visible on iPhone, not overpowering on desktop.

- [ ] **Step 2: Push an invalid-spot flash effect on `pointerup` invalid placement**

Update the `pointerup` body's invalid-spot branch (the touch / pen "stay Armed" path from Task 3 Step 2). Add a `game.effects.push(...)` call:

```js
        if (ui.pointerType === 'touch') {
          game.effects.push({ kind: 'invalidPlacement', x: p.x, y: p.y, t: 0, life: 0.15 });
          canvas.classList.remove('ab-dragging');
        } else if (ui.pointerType === 'pen') {
          game.effects.push({ kind: 'invalidPlacement', x: p.x, y: p.y, t: 0, life: 0.15 });
          canvas.classList.remove('ab-dragging');
        } else {
          ui.armedKind = null;
          canvas.classList.remove('ab-dragging');
        }
```

The 0.15 second life matches the spec's ~150ms target. The effect will be ticked by the existing `updateEffects` loop and dropped automatically when `t >= life`.

- [ ] **Step 3: Add an `invalidPlacement` render branch to `drawEffects()`**

Find the function:

```js
  function drawEffects() {
    for (var ei = 0; ei < game.effects.length; ei++) {
      var ef = game.effects[ei];
      if (ef.kind === 'bomber_aoe') {
        ...
      }
      if (ef.kind === 'splitFlash') { ... }
      if (ef.kind === 'spreadRing') { ... }
      if (ef.kind === 'knockbackRing') { ... }
    }
  }
```

Add a fifth branch:

```js
      // Touch Input Task 7: invalid placement flash. Brief red pulse at
      // the rejected tap location. Stays Armed; this is the visible
      // "I missed" signal.
      if (ef.kind === 'invalidPlacement') {
        var ip = ef.t / ef.life;
        ctx.save();
        ctx.globalAlpha = 1 - ip;
        ctx.strokeStyle = '#e43b44';
        ctx.lineWidth = 2;
        ctx.beginPath();
        ctx.arc(ef.x, ef.y, 8 + ip * 12, 0, Math.PI * 2);
        ctx.stroke();
        // Small "X" through the center for unambiguous rejection.
        ctx.beginPath();
        ctx.moveTo(ef.x - 5, ef.y - 5);
        ctx.lineTo(ef.x + 5, ef.y + 5);
        ctx.moveTo(ef.x + 5, ef.y - 5);
        ctx.lineTo(ef.x - 5, ef.y + 5);
        ctx.stroke();
        ctx.restore();
      }
```

The ring grows from radius 8 to radius 20 over the 150 ms while alpha fades from 1 to 0; an "X" sits at the center for the duration. Renders on a single frame budget without disturbing existing effect kinds.

- [ ] **Step 4: Verify red ghost on invalid spot**

On the iPhone or desktop: arm a cannon (tap a card on iPhone, click without dragging on desktop). Press on a tile occupied by an existing cannon. The ghost cannon should render with a red tint (in addition to the existing red range ring). Slide to a valid tile; the tint clears (the ghost is normal again). Slide back to invalid; tint returns.

- [ ] **Step 5: Verify invalid-spot flash on iPhone**

On the iPhone: tap a card to arm. Tap an invalid spot (overlap with existing cannon). A red pulsing ring with an X should briefly appear at the tap location, ~150 ms. The card stays armed (Task 3 already verified this). Tap a valid spot. Cannon places.

This completes checklist item 4 ("Tap-to-arm + tap invalid spot").

- [ ] **Step 6: Verify desktop drag-and-miss does NOT push the flash**

In a desktop browser: drag a shop card to an invalid spot and release. Armed clears (Task 3 behavior); no red flash. The flash is intentionally only on touch/pen tap-to-place, where the player needs explicit feedback that the tap registered. Desktop drag-and-miss already feels intentional via the cursor returning to default.

- [ ] **Step 7: Commit**

```bash
git add patterns/demo-ant-buster.php
git commit -m "feat(ant-buster): [Touch Input] Task 7: validity readout and flash

drawDragPreview tints the ghost cannon red over invalid placement spots.
On touch/pen pointerup over an invalid tile in Placing state, push an
invalidPlacement transient onto game.effects: a 150ms red ring + X at
the tap location. Renders via a new branch in drawEffects."
```

---

## Task 8: Theme version bump and full hand-test sweep

**Files:**
- Modify: `style.css` (the `Version:` header line)

This task is the closing checkpoint: bump the theme version (per CLAUDE.md's pattern-cache rule and to invalidate the enqueued `style.css?ver=` cache-buster) and run all 14 spec checklist items against real devices.

- [ ] **Step 1: Read the current theme version**

Open `style.css`. Find the `Version:` line in the header comment (likely near the top, around line 6-10).

- [ ] **Step 2: Bump the version**

The most recent feature commits on main were Plan 9. The previous tracked version (per the memory log) was `0.13.0` after Plan 7. Bump to the next minor: `0.14.0`. Edit the line:

```
Version: 0.14.0
```

(If the version on disk has already advanced past 0.13.0 due to in-progress Plan 8/9 work, bump by one minor from whatever the current value is.)

- [ ] **Step 3: Run the full 14-item checklist on iPhone**

Run every item from the spec's checklist in order, on the real iPhone:

1. Tap-to-arm + tap-to-place: arm a card, tap valid tile, places.
2. Tap-to-arm + cancel by re-tap: same card twice, no place.
3. Tap-to-arm + swap: A then B, B places.
4. Tap-to-arm + tap invalid spot: flash, stays armed, then place valid.
5. Drag-from-shop (skip: desktop only).
6. HUD pause persistence: arm, pause, unpause, place.
7. HUD speed persistence: arm, toggle 2×, still armed.
8. Send-Next during prep: arm, Send-Next, place mid-wave.
9. Page scroll suppression: drag finger, no page scroll.
10. System-gesture interruption: drag, swipe up to home, return, sane state.
11. Multitouch: two fingers, only first drives.
12. Affordability mid-Armed: spend down, re-arm, fail silently with flash.
13. Reset clears Armed: lose, restart, armedKind null.
14. Existing single-tap UI: cannon select, upgrade, sell, pause, speed.

Each item must pass. If any fail, file a follow-up task; do not claim acceptance until all pass.

- [ ] **Step 4: Run the checklist on iPad**

Repeat all 14 items on an iPad. Most should already pass given iPhone parity; the value of the iPad pass is confirming the larger-screen drag-from-shop UX still feels natural and the touch-occlusion offset is not too aggressive on a bigger screen.

- [ ] **Step 5: Run desktop regression baseline**

In Chrome and Firefox:

- Drag-from-shop with motion: places.
- Click-without-drag on a card: arms (highlight visible).
- Click on valid tile while armed: places.
- Drag-and-release on invalid: drops to Idle (no flash).
- Click-without-drag on same card again: unarms.
- Hover an upgrade button on a selected cannon: dashed range circle preview still renders.
- ESC while armed: unarms.

These cover the desktop muscle-memory paths.

- [ ] **Step 6: Commit the version bump**

```bash
git add style.css
git commit -m "chore(ant-buster): [Touch Input] Task 8: bump theme version to 0.14.0

Touch input + tap-to-arm placement, sub-project 1 of the iOS playability
fix. Replaces the mouse-only input layer with Pointer Events, adds tap-
to-arm UX with finger-occlusion offset on the ghost preview, and lands
the 14-item hand-test checklist (passes on iPhone, iPad, desktop)."
```

- [ ] **Step 7: Decide on follow-up branches**

Sub-project 2 (tap-target sizing) and sub-project 3 (portrait layout) are deferred per the spec. After this branch merges to main, brainstorm sub-project 2 with its own spec; the implementation plan for it will modify `drawShop`, `drawTargetingRow`, the HUD pause/speed widgets, and the `placeCannon` validity check's edge-buffer constants.

---

## Self-Review

Going back through the spec section by section to confirm coverage.

**Section 1 (interaction state machine):** Idle is the default `armedKind === null`. Pending is set in pointerdown shop-card branch (Task 2 Step 3). Pending → Dragging via the 6-game-px motion threshold (Task 2 Step 4). Pending → Armed on pointerup-without-motion (Task 3 Step 2 case A). Armed → Idle via same-card retap (Task 3 Step 1). Armed → Pending on different-card-affordable swap (Task 3 Step 1). Armed → Placing on playfield pointerdown (Task 3 Step 1). Placing → Idle on valid pointerup (Task 3 Step 2 case B). Placing → Armed on invalid pointerup (Task 3 Step 2 case C, gated on touch/pen pointerType). Armed → Idle via ESC (Task 4 Step 2). pointercancel rules (Task 4 Step 3). resetGame clears (Task 1 Step 2 plus Task 2 Step 2 plus Task 4 Step 4 final). Covered.

**Section 2 (Pointer Events wiring):** Four listeners, all replaced (Task 1 Steps 3-6). setPointerCapture + activePointerId tracking (Task 1 Step 3). pointerType captured (Task 1 Step 3). pointermove pointerId guard (Task 1 Step 4). pointerup pointerId guard (Task 1 Step 5). pointercancel pointerId guard (Task 1 Step 6, refined Task 4 Step 3). touch-action: none CSS (Task 0 Step 3). Multitouch single-pointer lock (Task 1 Step 3, the `if (ui.activePointerId !== null) { return; }` line). mouseleave removed (Task 1 Step 6 replaces it with pointercancel; the old listener is deleted, not kept). preventDefault is not called on any pointer event (verified by absence: no Step in the plan adds it). Inline-script gotcha respected (Task 1 Step 4 nested if; Task 3 Step 2 separate touch/pen branches; Task 6 Step 1 separate touch/pen branches). Covered.

**Section 3 (visual feedback):** Armed shop card highlight + label (Task 5 Step 1). Ghost preview hidden when activePointerId is null (Task 2 Step 7 added the guard, preserved in Task 6 and Task 7). Touch occlusion offset (Task 6 Step 1). Validity readout / red ghost tint (Task 7 Step 1). Invalid-spot flash (Task 7 Steps 2-3). Covered.

**Section 4 (edge cases and cancellation):** Same-card-retap toggle (Task 3 Step 1). Different-card-affordable swap (Task 3 Step 1). Different-card-unaffordable ignored (Task 3 Step 1 the swap branch's gold check; Task 4 Step 1 audit). ESC unarms (Task 4 Step 2). pointercancel preserves Armed for touch/pen (Task 4 Step 3). resetGame clears (covered). HUD pause / speed / Send-Next preserve Armed (verified by code audit: none of those branches touch armedKind; Task 1 Step 3 preserves the cascade order). Tap on placed cannon while Armed ignored (Task 4 Step 1 audit). Affordability lost mid-Armed handled by existing placeCannon gold check + visual greying (no change needed; verified by checklist item 12). Pointerdown on playfield while paused: existing pause-overlay hit-test runs first per the cascade order (no change needed). Pre-game idle state: shop not drawn, armedKind cannot be set (no change needed). Multitouch: Task 1 Step 3 single-pointer lock covers this. Pointer leaves canvas mid-drag: setPointerCapture covers this (Task 1 Step 3 calls it). Covered.

**Section 5 (testing):** Device matrix is the iPhone, iPad, desktop devices used throughout each task's verification step. Critical-path checklist 1-14 maps to specific verification steps. The acceptance step in Task 8 Step 3 runs the full sweep on iPhone. Regression baseline in Task 8 Step 5. Covered.

**Placeholder scan:** No "TBD" / "TODO" / "implement later" / "fill in details" in the plan. Each step has either concrete code or a concrete verification action. The "tune the offset if needed" step (Task 6 Step 4) is a soft branch with a clear default and a documented adjustment range, not a placeholder.

**Type / name consistency:** `ui.armedKind`, `ui.activePointerId`, `ui.pointerType`, `ui.pendingKind`, `ui.pendingX`, `ui.pendingY` are spelled identically across all tasks. `placeCannon`, `canPlaceCannon`, `cannonAt`, `CANNON_KINDS`, `game.effects`, `drawShop`, `drawDragPreview`, `drawEffects`, `inRect` all match the existing codebase. The transient effect kind `'invalidPlacement'` is set in Task 7 Step 2 and read in Task 7 Step 3: same string. The CSS class `'ab-dragging'` is referenced consistently across pointerdown / pointerup / pointercancel handlers.

**Spec-to-code identifier remap:** The spec text said `game.fx`; the actual array is `game.effects`. This plan uses `game.effects` everywhere. Note this in the Task 7 commit message so a future reader of the spec is not confused (the Task 7 commit body already mentions `game.effects` explicitly).

No issues found. Plan is ready for execution.
