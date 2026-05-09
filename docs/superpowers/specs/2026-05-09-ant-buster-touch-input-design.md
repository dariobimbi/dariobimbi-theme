# Ant Buster: touch input + tap-to-arm placement

Date: 2026-05-09
Status: design, awaiting review
Scope: input layer of `patterns/demo-ant-buster.php`

## Context

The Ant Buster demo at `/demos/ant-buster/` uses a drag-from-shop-to-playfield interaction to place cannons. All input is wired through `mousedown` / `mousemove` / `mouseup` / `mouseleave` listeners on the canvas (lines 5515-5661 in `patterns/demo-ant-buster.php`).

iOS Safari does not stream `mousemove` events during a touch. It synthesizes a `mousedown` / `mouseup` / `click` burst at the end of the touch, with no motion in between. The drag interaction therefore cannot work: `ui.dragging` is set on synthetic `mousedown` and immediately cleared on the `mouseup` that follows on the same coordinates (still inside the shop card region), so `placeCannon` is called outside the playfield and rejected by `canPlaceCannon`. The cannon never lands.

Confirmed on a real iOS device: tapping a shop card "seems to select but can't be moved." Other single-tap interactions (selecting a placed cannon, pause, speed, Send-Next, restart) work because they only need one synthetic mouse pair.

## Goals

1. Make the demo playable end-to-end on iPhone and iPad.
2. Replace the mouse-only input layer with Pointer Events so a single code path handles mouse, finger, and stylus.
3. Add a tap-to-arm, tap-to-place interaction model that does not require streaming motion during a touch, while preserving desktop drag-and-drop muscle memory.
4. Render the placement preview in a position the player can actually see when their finger is on the screen.

## Non-goals (deferred to future sub-projects)

- Sub-project 2: enlarging tap targets (pause, speed, targeting buttons, etc.) so they meet Apple's 44 CSS-px guideline at smaller viewport scales. Will be its own design.
- Sub-project 3: portrait / narrow-viewport layout reflow (or a "rotate to landscape" overlay). Will be its own design.

This spec covers the unblock-iOS work only.

## Design

### 1. Interaction state machine

One new piece of state on the `ui` object: `ui.armedKind` (a cannon-kind key, or `null`). It replaces `ui.dragging` semantically. A second new field, `ui.activePointerId`, tracks the single pointer currently driving the gesture.

States:

- **Idle.** `armedKind === null`, no pointer down. Default.
- **Pending.** Pointer is down on an affordable shop card, motion has not yet exceeded a 6-game-pixel threshold. Outcome (tap vs. drag) is not yet decided.
- **Dragging.** Pointer is down, `armedKind` is set, motion has exceeded the threshold. Ghost preview follows the pointer.
- **Armed.** No pointer is down, `armedKind` is set. The user has tap-armed a card and has not yet tap-placed.
- **Placing.** Substate of Armed: pointer is down on the playfield with `armedKind` set. Functionally identical to Dragging (ghost follows pointer, release decides), just entered from a different starting state.

Transitions:

| From | Trigger | To |
|---|---|---|
| Idle | pointerdown on affordable shop card | Pending |
| Pending | pointermove > 6 game-px from start | Dragging (set `armedKind`) |
| Pending | pointerup on same card without exceeding threshold | Armed (set `armedKind`; clear `activePointerId` since pointer is up) |
| Dragging | pointerup over a valid playfield tile | Idle (call `placeCannon`) |
| Dragging | pointerup anywhere else | Idle (no placement) |
| Armed | pointerdown on the same shop card | Idle (toggle off) |
| Armed | pointerdown on a different affordable shop card | Pending on the new card (old `armedKind` dropped) |
| Armed | pointerdown on a different unaffordable shop card | (no change) |
| Armed | pointerdown on the playfield | Placing |
| Armed | ESC key (desktop) | Idle |
| Armed | `pointercancel` | (Armed persists; only Pending and Dragging are dropped) |
| Placing | pointermove | (ghost follows pointer) |
| Placing | pointerup over valid tile | Idle (call `placeCannon`) |
| Placing | pointerup over invalid tile | Armed (invalid-spot flash, no placement) |
| any | `resetGame()` | Idle |

Rationale for the deferred decision in Pending: a touch lift-after-press on the same card with no useful motion in between is the only signal we get for "tap, not drag" on iOS. Letting the gesture decide its own meaning at release time is the cleanest way to support both interaction models from one event stream.

Rationale for the Placing substate: on touch, the finger occludes the placement tile. Committing on pointerdown means the player never gets to see the ghost. Committing on pointerup, with the ghost following the finger between down and up, lets the player slide-to-adjust before committing, and unifies behavior with desktop drag-and-drop.

Rationale for Dragging-on-invalid (returns to Idle) vs. Placing-on-invalid (returns to Armed): the two paths are entered with different signals about player intent. Dragging is started by a deliberate motion gesture from the shop card, so a release on a non-tile reads as "abandon this attempt", matching today's drag-cancel behavior on desktop. Placing is started by a tap on the playfield in tap-to-place mode, so a missed tap on an invalid spot reads as "I missed", and keeping Armed lets the player retry without re-tapping the shop. The divergence is intentional and matches the mental model each entry path establishes.

### 2. Pointer Events wiring

Four listeners on `canvas`, all replacing the existing mouse listeners. The existing `canvasToGame(evt)` helper is unchanged; Pointer Events expose `clientX` / `clientY` the same as mouse events.

- `pointerdown`: call `canvas.setPointerCapture(evt.pointerId)`. Record `ui.activePointerId = evt.pointerId` and `ui.pointerType = evt.pointerType`. Run the unified hit-test in current order: pause-overlay restart, HUD pause/speed, win/lose/idle taps, Send-Next, playfield (cannon-select OR Placing entry if Armed), side panel (targeting / upgrade / sell), shop. Set up Pending when a shop-card hit succeeds and the kind is affordable.
- `pointermove`: drop the event unless `evt.pointerId === ui.activePointerId`. Update `ui.mouseX` / `ui.mouseY`. In Pending, promote to Dragging if motion exceeds 6 game-px from the recorded down point. In Dragging, Placing, or mouse-hover, refresh the ghost preview position. Mouse-hover behavior (the `hoveredUpgrade` tracking) is preserved.
- `pointerup`: drop the event unless it matches the active pointer. Run the state-machine transitions, then clear `ui.activePointerId` and `ui.pointerType`.
- `pointercancel`: drop Pending and Dragging silently. Leave Armed untouched. Clear `ui.activePointerId`.

CSS: add `touch-action: none` to `.demo-ant-buster #ab-canvas`. Without it, iOS treats finger drags as page scroll or pinch-zoom and our event stream goes silent.

Multitouch: only the first pointer per gesture is honored, via `ui.activePointerId`. Subsequent `pointerdown` events from a second finger are ignored. When the active pointer ends, the slot is released and a fresh `pointerdown` can take it.

`mouseleave` removal: the existing `mouseleave` handler (lines 5656-5661) is deleted. With `setPointerCapture` the captured pointer keeps streaming events to us regardless of cursor location, and `pointerup` outside the canvas still fires through the captured target.

`preventDefault` policy: not called on pointer events. `touch-action: none` is the right tool to suppress browser gestures, and preventing default on pointer events would suppress focus we may want later.

Inline-script gotcha: per CLAUDE.md, this pattern is a single inline `<script>` and bare `&&` in JS gets HTML-encoded by WordPress filters. The new input code follows the existing nested-`if` style already used throughout the file.

### 3. Visual feedback

**Armed shop card.** When `ui.armedKind === card.kind`, `drawShop` draws the card with a 2-game-px border in the existing `#fee761` accent color (the same gold tint already used in `drawShop` for affordable cost text) plus a small label inside the card reading "tap a spot" in 8px monospace, color `#aaa`, positioned below the family hint. This is the only persistent on-screen tell that the player is in placement intent. If implementation finds a per-kind family-color token already wired into `CANNON_KINDS`, that may be used instead of `#fee761` for the border, but the label color stays neutral.

**Ghost preview.** Renders during any active gesture where `armedKind` is set AND a pointer is down: Dragging and Placing both use the existing `drawDragPreview` code. When the pointer is up in Armed state (between the arm-tap and the place-tap), the ghost is hidden; the armed-card highlight is the only indicator. This avoids a stale ghost lingering at the last touched location.

**Touch occlusion offset.** While rendering the ghost:

- `ui.pointerType === 'mouse'`: ghost centered on `(ui.mouseX, ui.mouseY)`. Identical to today.
- `ui.pointerType === 'touch'` or `'pen'`: ghost rendered at `(ui.mouseX, ui.mouseY - 30)`. The range ring stays centered on the real placement point. The placement coordinate passed to `placeCannon` is unchanged: the offset is rendering-only.

The 30 game-pixel offset is one tunable constant. At iPhone-landscape canvas scale (~1.3×) it lifts the ghost ~40 CSS-px above the fingertip, comfortably clear of an average fingertip footprint.

**Validity readout.** During an active gesture, when `canPlaceCannon(x, y)` returns false, tint the cannon ghost with a red-shifted overlay alpha so the player sees the rejection without having to read the range ring. One extra branch in `drawDragPreview`.

**Invalid-spot flash.** When the user releases on an invalid spot in Placing state, push a transient effect onto `game.fx` (the same array used by Bomber AoE flashes): a quick red pulse at the touch location, ~150ms life. The `game.fx` system already exists and is the right home.

### 4. Edge cases and cancellation

| Trigger | Result |
|---|---|
| Pointerdown on the same shop card while Armed | Idle (toggle off) |
| Pointerdown on a different affordable shop card while Armed | Pending on the new card; old `armedKind` dropped |
| Pointerdown on a different unaffordable shop card while Armed | (no change) |
| ESC key (desktop) while Armed | Idle |
| `pointercancel` mid-Pending or mid-Dragging | Drop the gesture; if Armed was the parent state (Placing), return to Armed |
| `resetGame()` | Idle (`armedKind`, `activePointerId`, `pointerType` all nulled in the existing reset block) |
| HUD pause / speed / Send-Next while Armed | Toggle as today; Armed persists |
| Tap on placed cannon while Armed | Ignored. Placement intent wins. To inspect a cannon, unarm first |
| Tap upgrade / sell / targeting button while Armed | Operates on `ui.selectedCannon` as today; Armed persists |
| Affordability lost mid-Armed (gold spent on an upgrade) | No auto-cancel. Shop card greys out via existing affordability render. Placement attempt fails through existing `placeCannon` gold check, shown as invalid-spot flash |
| Pointerdown on playfield while paused | Falls through to existing pause-overlay hit-tests (restart button). Armed persists |
| Pre-game `idle` state | Shop is not drawn; `armedKind` cannot be set |
| Two fingers on canvas | Only the first `pointerId` is honored; second is ignored |
| Pointer leaves canvas mid-drag | `setPointerCapture` keeps events flowing; `pointerup` outside the canvas falls through Section 1 rules |

### 5. Testing

There is no automated test suite in this theme (per CLAUDE.md). Verification is hand-driven on real devices.

**Device matrix.**

- iPhone (the original repro device), Safari, current iOS. Acceptance device. If this fails, the work is not done.
- iPad, Safari. Confirms tap-to-arm and the larger-screen drag-from-shop both work.
- Desktop Chrome and Firefox. Confirms drag-from-shop still places, click-without-drag now arms (new path), ESC unarms, pause/speed/sell/upgrade still wired.
- Desktop browser DevTools touch emulation is a smoke test only, not a substitute for the real iOS device. Synthetic touch in DevTools does not reproduce iOS Safari's gesture-interruption semantics or `touch-action` enforcement quirks.

**Critical-path checklist.** Run on each real device.

1. Tap-to-arm + tap-to-place. Tap a shop card; card highlights. Tap a valid playfield tile; cannon placed; card unarms.
2. Tap-to-arm + cancel by re-tap. Tap a card. Tap the same card. Card unarms; no cannon placed.
3. Tap-to-arm + swap. Tap card A. Tap card B. B armed; A no longer highlighted. Tap valid spot. B-kind cannon placed.
4. Tap-to-arm + tap invalid spot. Tap a card. Tap an invalid spot. Red flash; card stays armed. Tap a valid spot. Placed.
5. Drag-from-shop (desktop only). Press on card, drag to valid spot, release. Cannon placed. Confirms the legacy desktop muscle-memory path is intact.
6. HUD pause persistence. Arm a card. Tap pause. Pause overlay appears. Tap pause again. Card still armed. Place.
7. HUD speed persistence. Arm a card. Tap speed (1× → 2×). Speed changes. Card still armed.
8. Send-Next during prep. Arm a card. Tap Send-Next. Wave starts; card still armed; place mid-wave.
9. Page scroll suppression. On iPhone: drag a finger across the canvas. Page must not scroll or pinch-zoom. Confirms `touch-action: none`.
10. System-gesture interruption. Start a drag-from-shop on iPhone, swipe up from the bottom (Home gesture) mid-drag, return to Safari. App returns to a sane state: Pending and Dragging cleared, no orphaned state, ghost not stuck on screen.
11. Multitouch. Place two fingers on the canvas at once. Only the first touch drives input; second finger ignored. No double-place.
12. Affordability mid-Armed. Spend down to just enough gold to arm. Place. Re-arm with last gold. Try to place again; fails silently with invalid flash.
13. Reset clears Armed. Lose a wave or hit Restart Run. New game starts with `ui.armedKind === null`.
14. Existing single-tap UI still works. Tap an existing cannon; side panel opens. Tap upgrade / targeting / sell buttons; still work. Tap canvas in idle state; game starts.

**Regression baseline.** Before merging: confirm drag-preview ghost (desktop drag), the dashed-secondary range circle when hovering an upgrade button (desktop hover), and the existing armed-card visual treatment on shop cards all still render correctly.

**Acceptance.** All 14 checklist items pass on the real iPhone and iPad. Desktop regression baseline holds. Per CLAUDE.md's `verification-before-completion` rule, no claiming the fix works until the iPhone path has been run end-to-end.

## File-touch inventory

- `patterns/demo-ant-buster.php`: replace lines 5515-5661 (input listeners) with Pointer Events. Add `ui.armedKind`, `ui.activePointerId`, `ui.pointerType` to the `ui` object near line 5489. Add `armedKind` and `activePointerId` clearing to `resetGame` near line 5475. Update `drawShop` (line 1455) for the armed-card highlight. Update `drawDragPreview` (line 2311) for the touch offset and red-shifted invalid tint. Update CSS in the `<style>` block to add `touch-action: none`. Add an ESC-key listener for desktop unarm.
- `style.css`: bump the theme `Version:` per CLAUDE.md's pattern-cache rule.

## References

- Existing input code: `patterns/demo-ant-buster.php` lines 5488-5661.
- Drag preview render: `patterns/demo-ant-buster.php` lines 2308-2360.
- Shop render: `patterns/demo-ant-buster.php` lines 1455-1492.
- `placeCannon` validity check: `patterns/demo-ant-buster.php` lines 4140-4162.
- `game.fx` transient effects: `patterns/demo-ant-buster.php` (search `game.fx.push`).
- Pointer Events spec: https://www.w3.org/TR/pointerevents3/.
- `touch-action` CSS spec: https://www.w3.org/TR/pointerevents3/#the-touch-action-css-property.
