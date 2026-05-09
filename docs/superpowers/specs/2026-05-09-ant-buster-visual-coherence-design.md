# Ant Buster Visual Coherence Pass: Design Spec

**Date:** 2026-05-09
**Branch:** ant-buster-visual-coherence
**Sub-project:** 1 of 2 (the multi-tier upgrade tree is sub-project 2 and is explicitly out of scope here)

## Goal

Replace the current "default placeholder" UI surface of Ant Buster with a coherent picnic-themed visual language across every surface the player sees. The player should immediately read the game as a deliberate piece, not a system mockup.

The existing surfaces are: a flat dark HUD with monospace text, four flat dark blue shop cards, four full-screen overlays that are dimmed black with white text, a wave intro banner, a tutorial hint, and a placed-cannon detail panel. None of them reference the picnic premise visually.

## Scope

In scope:

1. Visual restyle of the HUD strip (top of canvas).
2. Visual restyle of the shop sidebar (right column when no cannon is selected).
3. Visual restyle of the four full-screen overlays (idle/title, pause, win, lose).
4. Visual restyle of the wave intro banner and tutorial hint sticky.
5. Visual restyle of the selected-cannon detail panel (right column when a placed cannon is tapped).
6. New behavior: a "?" hangtag on each shop card opens an info panel that previews the cannon's description and its next-tier upgrades. This is a one-tier preview, not a full tree.
7. One behavior simplification: the "Lost N/8" readout is removed from the HUD. The underlying `game.cake.lost` counter still drives the lose condition; it just stops being on screen.
8. Three new pixel-art PNG sprites: title cake, coin icon, cake-slice icon.

Out of scope (explicitly sub-project 2):

- Multi-tier upgrade tree visualization (showing every cannon a base can become across all tiers in one view).
- Touch-target sizing for the targeting buttons, pause/speed widgets, and Send Next button on iPhone-landscape (sub-project 2 of the touch input rollout).
- Portrait/narrow-viewport reflow (sub-project 3 of the touch input rollout).
- Any sound or music changes.
- Any gameplay-logic balance change.

## Architecture

The game canvas is fixed at 640x360 internal coordinates and CSS-scales as a unit. Every surface in this spec renders into that canvas. No new DOM elements are added; nothing migrates out of the canvas.

The existing render pipeline in `patterns/demo-ant-buster.php` is retained:

- `drawHud()` renders the top strip.
- `drawShop()` renders the right column when `ui.selectedCannon` is null.
- `drawTowerPanel(c)` renders the right column when a cannon is selected.
- `drawIdleOverlay()`, `drawPauseOverlay()`, `drawWinOverlay()`, `drawLoseOverlay()` render the four full-screen overlays.
- `drawTutorialHint()` and `drawBanner()` render the small in-canvas notes.

Each function is rewritten to emit the new visual language. New helper functions are added for the shared chrome (wooden tag, wooden card, wooden button) so the same primitives serve all surfaces.

A new state field `ui.shopMode` joins the `ui` object. Values:

- `'list'`: default, draws the four cannon cards.
- `'info'`: draws the info panel for `ui.shopInfoKind` (a cannon kind string, e.g. `'howitzer'`).

When a cannon is placed, the right column is governed by `ui.selectedCannon` as before; `ui.shopMode` does not apply. The info panel is shop-state-only.

## Color and typography palette

Wood family (new, for chrome):

- `--wood-light` `#8a6a4e`
- `--wood-mid` `#6a4a2e`
- `--wood-dark` `#4a3220`
- `--wood-deep` `#2a1810`
- `--wood-edge` `#5a3a20` (inset highlight on top edge of recessed elements)

Cream / yellow family (text and labels):

- `--cream` `#f5e6c8` (default label color, ~6:1 on wood-mid)
- `--cream-dim` `#c8a868` (subordinate labels, headers)
- `--gold` `#fee761` (primary number color, name color, button text on green)
- `--gold-warm` `#ffd86b` (Cake readout)

Mode-color family (existing, retained):

- Closest `#4a6acc` (was `#3a4a8c`, brightened for cream-on-wood contrast)
- First `#c8a040` (was `#9a7820`)
- Last `#888` (was `#3a3a3a`)
- Strongest `#c84444` (was `#7a2020`)
- Weakest `#c84090` (was `#7a2040`)

Family-strip colors (used on shop cards):

- Heavy `#888`
- Quick `#b0d04a`
- Status `#8a5ac8`
- Rubber `#ff77a8`

Danger / sell / lose family:

- `--danger-red` `#a04040` (sell button base)
- `--danger-deep` `#6a2020` (sell button shadow)
- `--lose-tint` `rgba(80, 10, 10, 0.55)` (lose overlay dim)

Win family:

- `--win-glow` `rgba(254, 231, 97, 0.35)` (radial light on win overlay)

Typography in canvas:

- All text `bold Npx monospace` using `ctx.font`.
- Drop shadow on numbers and titles: `ctx.fillStyle = '#2a1810'; ctx.fillText(s, x+1, y+1); ctx.fillStyle = <main>; ctx.fillText(s, x, y);` for a 1-pixel offset shadow that reads at small sizes.
- Cream labels at 8-9px bold all-caps with letter-spacing achieved via per-character draws.
- Numbers at 13-16px bold; titles at 24-32px bold.

## HUD redesign (top 24 px strip)

Background: a wooden plaque spanning `0..PLAYFIELD_W` on `y..0..HUD_H`. Three horizontal bands:

1. Top 2px: wood-edge highlight `#5a3a20`.
2. Middle: vertical gradient wood-light `#6a4a2e` to wood-dark `#4a3220`.
3. Bottom 2px: light highlight `#8a6a4e`.
4. Bottom 1px: deep border `#2a1810`.

Four readout tags arranged left to right with 4px gaps, each tag a recessed wooden box:

- Coin tag: 12x14 pixel coin sprite + gold value. Example "150" at 13px bold.
- Cake tag: 12x14 pixel cake-slice sprite + gold-warm value. Example "8/8".
- Wave tag: cream "WAVE" small caps label + white value. Example "1/25".
- Prep tag: cream "PREP" small caps label + light-green value. Example "30s".

Tag rendering: 22px tall, vertically centered in the 24px HUD. Inset wooden frame: `#5a3a20` to `#3a2210` gradient body, 1px `#2a1810` border, 1px `#8a6a4e` light line on the bottom inside edge, 1px `#2a1810` shadow line on the top inside edge.

Right side: when `game.waveState === 'prep'`, a chunky green Send Next button. Same wooden-button chrome family as the title PLAY button (see Splashes section). Replaces the current flat `#5a7d4a` rect.

Pause and speed widgets: re-themed to match the wooden-button family. Pause icon stays as the existing two-bar / triangle. Speed widget shows "1x" or "2x" in cream on wood.

Removed: the "Lost N/8" readout. The render no longer emits it, the spacing tightens to four tags. `game.cake.lost` continues to drive the lose condition unchanged.

## Splash overlays

Each full-screen overlay dims the playfield with a tinted layer, then draws decorative props and a wooden sign in the canvas center. The four overlays share the same wooden-sign chrome.

### Wooden sign primitive (`drawWoodenSign`)

A reusable helper that draws:

- Body: wood-mid to wood-dark gradient rectangle, configurable size.
- Border: 3px wood-deep frame.
- Inset highlights: 3px wood-light line on the bottom inside edge, 3px wood-edge line on the top inside edge.
- Drop shadow: 6px offset, `rgba(0,0,0,0.4)`.
- Two metal nails: 5x5px radial-gradient circles at (left+8, top+6) and (right-8, top+6), `#888` to `#444` with a 1px `#2a1810` ring.

Used by all four splashes plus the wave intro banner.

### Idle / title overlay

Dim layer: `rgba(20, 10, 4, 0.55)` over the entire canvas.

Composition (all in canvas-internal pixel coords):

- 15 ant sprites scattered randomly across the canvas at varied rotations and three sizes (small 10x5, medium 14x7, big 18x9). Static positions, no movement. Z-order: above dim, below cake.
- Hero cake sprite: a new PNG `assets/sprites/title-cake.png` at 192x144 source resolution, rendered at ~280x180 in canvas (44% of canvas width). Top-edge at y=14 in canvas. Three tiers (yellow-yellow-red, yellow-yellow-pink, yellow-yellow-green frosting bands), three candles on top with flickering pixel flames. Z-order: above ants.
- Title sign: ~380x140 in canvas (60% wide), bottom edge at y=320. Overlaps the lower 60% of the cake. Z-order: above cake.
- Sign content, top to bottom:
  - Eyebrow "DEFEND THE PICNIC" in cream-dim small caps with letter-spacing.
  - Title "ANT BUSTER" in gold at 32px bold, with each character drawn at a slight per-character rotation (-3deg, 2deg, -1deg, 3deg, -2deg, 0deg, 2deg, -2deg, 3deg, -1deg) for a hand-painted feel. No animation.
  - PLAY button in green wooden-button chrome at 18px bold gold text, no animation.

The only animation on the idle overlay is the candle flame flicker (a tiny y-scale and alpha oscillation at ~0.5 Hz).

### Pause overlay (unchanged from earlier locked design)

Dim layer: `rgba(0, 0, 0, 0.5)`.
Wooden sign centered with eyebrow "HOLD UP", title "PAUSED" at 24px bold gold, sub "Space to resume" in cream, and a smaller red-wooden "Restart Run" sub-button below.

### Win overlay

Dim layer: a radial gradient with `--win-glow` in the center fading to `rgba(20, 10, 4, 0.5)` at the edges. Overall warmer than the idle dim.

Composition:

- 8 confetti pixel rects falling from random x positions in colors gold, hot-pink, light-green. Slow vertical drift with a small rotation, constant cycle.
- Wooden sign centered. Three small birthday candles on top of the sign (flickering pixel flames), gap-spaced.
- Sign content:
  - Eyebrow "ALL 25 WAVES CLEARED" in gold cream small caps.
  - Title "VICTORY" at 40px bold cream-yellow with a subtle gold glow.
  - Sub "Click to restart" in cream.

### Lose overlay

Dim layer: `--lose-tint` (saturated dark red).

Composition:

- 6 ant sprites scattered, larger than idle (medium 14x7 and big 18x9 only), positioned around the sign.
- Wooden sign centered, with the wood gradient swapped to wine-dark (wood-mid biased toward `#5a2a20` to `#3a1a10`) and the border deepened to `#5a1a1a`. Reads as a different chrome state, signals harshness.
- Sign content:
  - Eyebrow "8 SLICES LOST" in light-red cream small caps.
  - Title "THE ANTS WIN" at 32px bold light-red `#ff5050` with a subtle red glow, plus a tiny horizontal jitter (1px shake at ~3 Hz) so the title reads as agitated. Single sin/cos pair, cheap.
  - Sub "Click to try again" in light-red cream.

### Wave intro banner

Existing `drawBanner()`. Same wooden-sign primitive, scaled smaller (96x18 in canvas as currently sized). For boss waves the wood gradient swaps to the wine-dark family, label flips to "BOSS WAVE". Otherwise "WAVE N".

### Tutorial hint sticky

Existing `drawTutorialHint()`. Replaced visually with a paper-card primitive: cream `#fff8e8` to `#f0dfb8` body, 2px wood-deep border, 2px offset dark drop shadow, content tilted -1.5 degrees. Reads as a sticky note pinned to the playfield. Copy unchanged: "Drag a cannon from the shop / onto the picnic blanket to start".

## Shop sidebar

Background: a wood-grain panel filling the 160px right column. Painted with vertical 1.2cqw stripes alternating `#4a3220` and `#5a3a26`, then a vertical gradient `#5a3a26` to `#3a2010` overlaid at 50% opacity. Reads as planks.

Header at the top of the panel: "CANNONS" in cream-dim small caps with letter-spacing.

Four shop cards stacked, 8px vertical gap. Each card is a wooden-card primitive:

- Body: wood-mid to wood-dark gradient.
- Border: 2px wood-deep.
- Inset highlights: 2px wood-light bottom, 2px wood-edge top.
- Family-color strip: 6px wide column on the left edge, full card height, in the family color (heavy gray, quick green, status purple, rubber pink). Acts as a visual hint for what family the cannon belongs to.

Card layout, top zone:

- Sprite tile: 38x38 dark-inset frame (`#2a1810` body, 1px `#1a0a00` border, 2px `#4a3220` inner glow). The existing turret sprite is drawn centered inside.
- Right column:
  - Name in gold bold.
  - Cost row: 10x10 coin sprite vertically centered with the gold cost number.

Card layout, bottom zone (full card width, separated from top zone by a 1px subtle wood-deep divider with a dark-gradient overlay):

- Stats row: three stats space-between, each "LABEL value" with cream-dim small caps label and bright cream bold value. Plain text, no icons. Example "DMG 5   RATE 0.6   RANGE 110".

Card layout, decoration:

- Wooden hangtag at top-right corner: 14x14 wooden chip with a small dark string anchor at the top, gold "?" carved in the center. Reads as a label tied to the cannon. Tap-target for opening info panel.

Card states:

- Normal: as described.
- Cant-afford: cost number and coin desaturate to gray; everything else stays readable.
- Armed (player has tapped this card to place): outer yellow glow ring (3px `#fee761` plus 8px soft `rgba(254, 231, 97, 0.5)`), and a small cream "Tap a spot" pill hangs off the bottom of the card.

## Info panel (the "?" drill-down)

Triggered by tapping the "?" hangtag on a shop card. Sets `ui.shopMode = 'info'` and `ui.shopInfoKind = <cannon-kind>`. The shop sidebar render branches on `ui.shopMode` and renders the info panel instead of the four cards.

Panel layout, top to bottom:

- Back link: cream-dim small caps "< BACK TO CANNONS". Tap-target for `ui.shopMode = 'list'`.
- Cannon name in 22px bold gold with 1px wood-deep drop shadow.
- Family label in cream-dim small caps "<family> family".
- Stats row: four stat columns in a recessed wooden frame, each "LABEL above value". Stats are DMG, RATE, RANGE. (KILLS is omitted here, since the info panel is for *unplaced* cannons. KILLS belongs to the detail panel.)
- Description card: dark-recessed `#3a2210` to `#2a1810` body, 2px wood-deep border, 2px wood-edge top inset, cream text at 12px bold. One sentence per cannon, drawn from a new `CANNON_KINDS[kind].description` field. If the field is absent, the description card falls back to "<Family> <tier>" text (e.g. "Heavy base", "Quick mid") so the panel still renders cleanly during incremental content rollout.
- "UPGRADES TO" header in cream-dim small caps.
- Upgrade rows (1-3 rows depending on the cannon), each a recessed wooden card:
  - Top line: "→" arrow in gold, name in gold bold, cost with coin icon right-aligned.
  - Stats line: "DMG N   RATE N   RANGE N" in cream-dim labels and cream values.
  - Description line: one-sentence preview in cream at slightly dimmer tone than the body description.
  - Tap-target for arming that upgrade, but only when the player is in the placed-cannon detail panel, not the shop info panel. In the shop info panel the rows are read-only previews.

Behavior summary:

- Tapping "?" on a shop card flips the column to info mode.
- Tapping anywhere outside the panel, including the playfield, the back link, or another shop card, returns to list mode.
- Pressing Escape (when keyboard available) returns to list mode.
- The info panel is non-blocking: gameplay continues in the playfield. The wave timer keeps ticking; ants keep moving. This matches the existing detail panel behavior.

## Selected-cannon detail panel

Renders when `ui.selectedCannon` is non-null. Same wood column body as the shop sidebar. Layout extends the info panel:

- Back link: "< BACK TO CANNONS" (`ui.selectedCannon = null`).
- Cannon name and family (same as info panel).
- Stats row: four columns now (DMG, RATE, RANGE, KILLS). KILLS column has a gold value color (different from the cream of the static stats) so the live tally reads as the dynamic field.
- Targeting row: 5 wooden buttons (C, F, L, S, W) flex-distributed across the panel width. Each button is a recessed wooden chip. The active mode shows a 4px colored bar inset along its bottom edge in the mode color (Closest blue, First gold, Last gray, Strongest red, Weakest pink). The active button's letter is gold; inactive letters are cream.
- Targeting label below the row: "Targeting <Mode>" in cream-dim small caps with the mode name in gold.
- "UPGRADES TO" header.
- Upgrade rows: same format as the info panel, but tappable to commit the upgrade. Affordability gating remains: rows below `game.gold` render with the cost in dim gray and ignore taps. God-tier rows: dark-gold gradient instead of brown wood, 50% alpha when locked, full alpha + halo pulse when unlocked. The existing `game.godTierUnlockPulseStart` halo logic is retained, just rendered around the new wooden-frame upgrade row instead of the old green rect.
- Sell button: pinned at the bottom of the panel. Red wooden chrome: `#a04040` to `#6a2020` body, 2px `#2a0a0a` border, 2px `#c06060` light inset, 2px `#6a2020` shadow inset. Cream text "Sell · +Ng" with a middot, no em-dash, no en-dash (voice-guide-clean).

Locked god-tier tooltip ("Unlocks at wave 15") retains its existing position and copy. Visually re-themed to a tiny wooden chip (wood-deep body, gold text) instead of the current dark-blue rect.

## Voice and copy

All copy in this pass passes the voice guide:

- No em-dashes, no en-dashes anywhere user-facing.
- No three-item rhetorical lists.
- No "not just X, but Y" cadence.
- No hype words.
- Plain caps for emphasis where the design calls for it.

Copy table:

- Title splash: eyebrow "DEFEND THE PICNIC", title "ANT BUSTER", button "PLAY".
- Win splash: eyebrow "ALL 25 WAVES CLEARED", title "VICTORY", sub "Click to restart".
- Lose splash: eyebrow "8 SLICES LOST", title "THE ANTS WIN", sub "Click to try again".
- Pause splash: eyebrow "HOLD UP", title "PAUSED", sub "Space to resume", red sub-button "Restart Run".
- Wave banner: "WAVE N" or "BOSS WAVE".
- Tutorial sticky: "Drag a cannon from the shop / onto the picnic blanket to start".
- Shop header: "CANNONS".
- Card stats: "DMG", "RATE", "RANGE".
- Info panel: "BACK TO CANNONS", "<family> family", "UPGRADES TO".
- Detail panel: same as info panel plus "KILLS", "Targeting <Mode>", "Sell · +Ng".

## New asset specifications

Three new pixel-art PNGs to commit at `assets/sprites/`:

1. `title-cake.png`: 192x144 source, three-tier cake with three lit candles, drawn so the bottom 30% can be visually obscured by the title sign without losing the silhouette. Drawn in the existing pixel style (dithered shading, hard outlines). Loaded via the existing sprite-loader path.
2. `coin.png`: 12x12 source, gold coin with dark outline. Used in the HUD coin tag and in every cost readout (shop card, info panel, upgrade rows).
3. `cake-slice.png`: 12x12 source, single slice with a candle, used in the HUD cake tag.

Existing sprites retained: the four base turret sprites (already loaded), the in-canvas cake-on-pedestal, the ant sprite (already used for projectile-target reasoning, will be reused for splash decoration).

If creating dedicated pixel art is too much for one pass, the cake and coin can be temporary CSS-canvas-drawn approximations using the wooden-tag and rect-stack primitives. The PNG path is preferred and the spec assumes PNG; the plan can downgrade if the implementer flags it.

## Content: cannon descriptions

The info panel and the upgrade-row previews each render a one-sentence description per cannon. The game has 51 cannons (4 base, 12 mid, 17 final, 18 god per Plan 4 inventory).

In scope for this pass:

- Add a `description` string field to every entry in `CANNON_KINDS`.
- Write descriptions for the 4 base cannons and the 12 mid-tier cannons (16 total). These are the cannons most visible to a new player and the upgrade rows that surface from the base shop info panel.
- Mid-tier and apex descriptions can land in batches; the fallback text described above keeps the panel valid until the field is filled.
- Voice: plain, one sentence, declarative. Examples: "Slow, hard-hitting single shot. The foundation of the heavy line." / "Bouncing disc, hits the same ant up to three times." / "Lobs a shell that splashes on impact."

Out of scope: the 17 final-tier and 18 god-tier descriptions. They fall back to "<Family> <tier>" text until a content pass adds them. The plan can choose to include them all if there is appetite; the spec does not require it for this sub-project to ship.

## Behavior changes summary

This pass is mostly visual, but two behaviors change:

1. The "Lost N/8" readout is removed from the HUD. `game.cake.lost` is unchanged; it still triggers game-over at 8.
2. A new info-panel state lives in the shop sidebar, accessible via the "?" hangtag on each card. The panel is read-only preview, not a new gameplay surface. It coexists with armed-state.

## What sub-project 2 will add

The recursive drill-down: tapping "→ Siege" inside an info panel flips the same column to Siege's info panel, showing Siege's stats, description, and *its* next-tier upgrades. Repeat to the apex tier. This makes the entire upgrade tree explorable without buying anything.

This pass intentionally stops at one tier so the chrome and primitives can ship and bake before the recursion is added.

## Manual test checklist

The repo has no automated test runner; verification is hand-driven. Each item below is checked after implementation, before merge:

1. Title screen: cake, sign, ants render correctly. PLAY button hover state shows pointer cursor on desktop. Tap anywhere on the sign starts the game.
2. HUD: all four readouts render and update during play. Send Next button appears during prep, disappears during wave. Pause and speed widgets work.
3. HUD scaling: at 320px-wide viewport and at 1280px-wide viewport, every readout reads cleanly. No overflow.
4. Shop sidebar: four cards render. Family color strip is correct per cannon. Tapping a card arms it. Tapping a placed cannon spot places the cannon.
5. Cant-afford state: when gold is below cost, cost and coin go gray; the rest of the card stays normal.
6. Info hangtag: tapping "?" flips the column to info mode. Back link returns to list. Tapping outside the panel also returns to list. Tapping the same "?" again does not toggle (always opens info; close with back).
7. Info panel content: name, family, stats, description, three upgrade rows render with correct values.
8. Detail panel: tapping a placed cannon shows DMG/RATE/RANGE/KILLS, the targeting row, upgrade rows, and sell button. KILLS counter increments live during the wave.
9. Targeting buttons: tapping any of the 5 changes the active mode and updates the bottom-edge color bar plus the label.
10. Upgrade buttons: tapping an affordable upgrade commits, deducts gold, swaps the cannon kind. Locked god-tier shows 50% alpha and tooltip on hover.
11. Sell button: tapping returns 70% of cumulative spend, removes the cannon, returns column to shop list.
12. Win and lose overlays: trigger correctly, render the right composition, restart on click.
13. Pause overlay: triggers on space, looks correct, "Space to resume" works, "Restart Run" sub-button works.
14. Wave intro banner and tutorial sticky render at the right times with the new visuals.
15. Inline-script gotcha: no `&&` or `||` in any new inline script code; verify by curling the rendered page and grepping for `&#038;&#038;`.
16. Voice/copy: no em-dashes or en-dashes anywhere user-facing.
17. Theme version bumped in `style.css` so the pattern cache invalidates.
