# Ant Buster: mobile / portrait layout

Date: 2026-05-10
Status: design, awaiting review
Scope: layout + render layer of `patterns/demo-ant-buster.php` (game logic untouched)

## Context

The Ant Buster demo at `/demos/ant-buster/` renders a 640 × 360 canvas (480 px playfield + 160 px right shop sidebar + 24 px HUD) inside `.ab-stage`. CSS scales the canvas to viewport width with `aspect-ratio: 16/9`. On a phone in portrait the canvas fills the viewport width and ends up tiny. On a 375 px iPhone the canvas displays at 375 × 211, so a 24-game-pixel cannon renders at ~14 screen pixels — readable but too small to tap reliably, and the 160-wide shop sidebar collapses to ~94 screen pixels with text in the 5–7 screen-pixel range.

Touch input itself works (Pointer Events shipped 2026-05-09). What is missing is a layout that gives the game enough room on a portrait phone without forcing rotation. Sub-projects 1 (touch input) and 2 (visual coherence) shipped; this spec is sub-project 3 of the post-touch-input rollout and the third and final piece of the iOS playability fix series.

## Goals

1. The demo is comfortable to play on an iPhone or Android phone held in portrait.
2. Tap targets land in the 20–25 screen-pixel range on a 375-wide phone (current floor is ~13).
3. The wooden-chrome shop / detail / info panels from the visual coherence pass keep their look — only their layout reflows.
4. Landscape rendering on desktop and tablet is byte-identical to today.
5. Rotation flips the layout in place without a page reload.

## Non-goals

- Changing the gameplay (wave paths, cake position, cannon kinds, ant kinds, all combat math). The playfield stays 480 × 336.
- Adding a "rotate to landscape" prompt. The portrait layout is first-class.
- A new shop-card information architecture. Same four base kinds; same field-guide drill-down; same placed-cannon detail panel. Only their pixel layout inside the strip changes.
- Touch-target overhaul of the HUD pause / speed / Send-Next widgets. They already moved to 22 × 20 in the visual coherence pass; that is acceptable for portrait.

## Design

### 1. Layout switch

Two internal coordinate systems, picked at canvas init and on every `resize` / `orientationchange`:

- **Landscape**: `WIDTH = 640`, `HEIGHT = 360`, `PLAYFIELD_W = 480`, `SHOP_W = 160`, `STRIP_H = 0`. This is today.
- **Portrait**: `WIDTH = 480`, `HEIGHT = 600`, `PLAYFIELD_W = 480`, `SHOP_W = 0`, `STRIP_H = 240`. New.

`HUD_H = 24` is shared. The shop is either a right column (landscape) or a bottom strip (portrait). The right column has width `SHOP_W`; the bottom strip has height `STRIP_H`. Exactly one of those is non-zero per orientation.

Detection: a single `isPortrait()` helper that reads the bounding rect of `.ab-stage` and returns `rect.height > rect.width`. The CSS sets the canvas aspect ratio per orientation via a media query (see Section 8) so the rect comparison is meaningful even before the canvas internal-coords switch happens. JavaScript listens to `window` `resize` and `orientationchange`; on either, if the result of `isPortrait()` differs from the cached value, the engine calls `relayout()`.

`relayout()` does:

1. Update the eight layout constants (`WIDTH`, `HEIGHT`, `PLAYFIELD_W`, `SHOP_W`, `STRIP_H`, plus the strip / sidebar origin x/y).
2. Set `canvas.width` and `canvas.height` to `WIDTH * 2` and `HEIGHT * 2` (the existing 2× retina factor).
3. Regenerate sprite caches that depend on canvas dimensions (none today actually do — sprites are pre-rendered to fixed-size offscreen canvases, so this is a no-op for now, but the call is wired so a future sprite that reads `WIDTH` won't silently break).
4. Recompute static positions: `IDLE_ANT_LAYOUT_PORTRAIT` (a separate frozen array of 15 ants distributed across the portrait playfield), the title-cake position, the splash button positions, the HUD pause/speed/sendNext anchor offsets.
5. Reset the next-frame `dirty` flag so `drawFrame` fully repaints.

Game state is preserved across `relayout()`: cannons, ants, gold, wave index, paused state, selected cannon, armed kind, shop mode, scrolling — all of it. Rotation does not reset the run.

### 2. Canvas dimensions and zone breakdown

Portrait canvas is 480 × 600 internal:

```
┌──────────────────────────────────────┐
│ HUD (480 × 24)                       │  y =   0..23
├──────────────────────────────────────┤
│                                      │
│                                      │
│ Playfield (480 × 336)                │  y =  24..359
│                                      │
│                                      │
├──────────────────────────────────────┤
│                                      │
│ Strip (480 × 240) — list/detail/info │  y = 360..599
│                                      │
└──────────────────────────────────────┘
```

The playfield rect is identical to landscape. All gameplay coords (cake position, wave path nodes, cannon snap grid, IDLE_ANT_LAYOUT entries that are inside the playfield) are usable as-is.

### 3. HUD (portrait)

Same seven-widget layout as landscape: coin tag, cake tag, wave tag, prep tag (during prep), Send-Next button (during prep), pause widget, speed widget. The four readout tags left-align starting at x=4. The pause / speed / Send-Next widgets right-align starting at `WIDTH - 4`. In landscape the right widgets sit at `PLAYFIELD_W - 122` etc., relative to the playfield right edge; in portrait they sit at `WIDTH - 122` etc., relative to the canvas right edge. Same anchor formula, just different reference width.

Long gold values (4–5 digits) keep working because the visual coherence pass already measures each tag with `ctx.measureText` against `bold 11px monospace`.

### 4. Strip — list mode (default)

`STRIP_Y = HEIGHT - STRIP_H = 360`. Strip body is 480 × 240, painted with the same wooden plaque as the right sidebar today.

Four cards arrayed left-to-right:

- Card width: `(480 - 5 * GAP) / 4` where `GAP = 4` ⇒ 115 px each
- Card height: 230 (full strip height minus 5 px top + 5 px bottom padding)
- Each card contains, top to bottom: family-color strip (left edge, 4 px wide), sprite tile (40 × 40 inset, centered), name (centered, bold 11px), cost row (coin sprite + value, centered), small DMG/RATE line (centered, bold 8px), "?" cream-paper hangtag (top-right corner, 14 × 14)
- "TAP A SPOT" yellow pill highlight when the card is the currently armed kind (same as landscape, just sized to the new card width)
- Hit rect: full card bounds, written into `ui.shopRects[i]` with `kind` field
- "?" hangtag hit rect: written into `ui.shopInfoRects[i]` with `kind` field

The DMG/RATE line is new for portrait — landscape cards already have an inline three-stat row (DMG/RATE/RNG), but its column widths assume a 148-wide card. Portrait cards are 115 wide, which doesn't fit DMG/RATE/RNG in three columns. Showing only the two most-decisive stats (DMG and RATE) keeps the inline glance value without the cramming. The "?" field guide still shows all three.

### 5. Strip — detail mode (placed cannon tapped)

Two-column horizontal layout inside the 480 × 240 strip:

```
┌─────────────────────────────┬──────────────────┐
│ ‹ BACK TO CANNONS  (button) │                  │  18 tall
├─────────────────────────────┴──────────────────┤
│ Left column                  Right column      │
│ (276 × 218)                  (200 × 218)       │
│                                                │
│  Repeater                     [ Twin Gun  ?  ] │
│  artillery family             [ DMG 4 · 100 ]  │
│                                                │
│  [DMG][RATE][RNG][KILLS]      [ Sniper    ?  ] │
│   2    1.6   90   3           [ DMG 8 · 180 ]  │
│                                                │
│  [C] [F] [L] [S] [W]          [ Sell · +35  ]  │
│                                  red wooden    │
└────────────────────────────────────────────────┘
```

- Back button: full-width wooden button at the top of the strip (same chrome as the visual coherence pass), `bbX = 16`, `bbY = STRIP_Y + 4`, `bbW = 480 - 32 = 448`, `bbH = 16`. Label is the static "‹ BACK TO CANNONS" — in portrait there is no nesting (info from detail goes back to detail, see Section 6), so the contextual relabeling logic stays for info but the detail back link is always "BACK TO CANNONS".
- Left column (`x = 4`, `y = STRIP_Y + 22`, `w = 276`, `h = 218`): wooden recessed tag containing name, family label, four-column stats grid (DMG/RATE/RNG/KILLS — all four fit because the column is 276 wide vs. 148 in landscape), then a horizontal targeting row of 5 buttons (C/F/L/S/W, 22 × 22 each, 4 px gap). Mode-color underline on the active button as today.
- Right column (`x = 284`, `y = STRIP_Y + 22`, `w = 192`, `h = 218`): vertical stack of up to 3 upgrade rows (each ~62 tall), with a red sell button pinned to the bottom (`h = 22`). Upgrade rows have name (10 px bold), cost-with-coin (right-aligned), one-line stats (DMG/RATE/RNG, 8 px bold), and the cream-paper "?" hangtag (top-right, 14 × 14). Affordability dim and god-tier-locked dim work the same as landscape.
- The two-column split width (276 vs. 192) is calibrated so the four-column stats grid in the left column has even ~64-px columns, and the upgrade rows in the right column can fit "Pink Pea Gatling" at 9-px font.

### 6. Strip — info mode ("?" tapped from list or detail)

Same two-column split as detail, swapped fill:

```
┌────────────────────────────────────────────────┐
│ ‹ BACK TO ‹where I came from›  (button)        │  18 tall
├────────────────────────────────────────────────┤
│ FIELD GUIDE                                    │  10 tall
├─────────────────────────────┬──────────────────┤
│ Cream paper                  EVOLVES TO        │
│ (276 × 200)                  Cream cards       │
│                              (192 × 200)       │
│                                                │
│  Howitzer                    [ Sniper       ]  │
│  artillery family            [ DMG 8 · 0.5  ]  │
│                                                │
│  [DMG] [RATE] [RNG]          [ Mortar       ]  │
│   5     0.6    110           [ DMG 12 · 0.3 ]  │
│                                                │
│  Slow but heavy. Good        [ Reaper · GOD ]  │
│  against grouped ants.       [              ]  │
└────────────────────────────────────────────────┘
```

- Back button (top of strip, full width, wooden, contextual label) and "FIELD GUIDE" eyebrow (centered, dim gold) sit above the two-column split. Same chrome as landscape, just rotated 90° relative to the right sidebar's stack.
- Left column: cream-paper rectangle holding name, family label, three-column stats (DMG/RATE/RNG — KILLS is absent in info, since you might be inspecting a cannon you don't own), and the wrapped description.
- Right column: vertical stack of "EVOLVES TO" cream cards. Up to 3 evolution targets (the longest evolution list in the data). No cost shown, no affordability dim. Tier badge in the top-right corner. Tapping a card pushes onto the nav stack and re-renders info for the tapped kind, exactly as on desktop.
- The contextual back-label logic from the visual coherence pass is preserved: when info was opened from a placed cannon's detail panel, the back button reads "‹ BACK TO REPEATER" (or whichever cannon kind), and tapping it returns to that cannon's detail panel. Otherwise it reads "‹ BACK TO CANNONS".

### 7. IDLE_ANT_LAYOUT — portrait variant

A new frozen array `IDLE_ANT_LAYOUT_PORTRAIT` parallel to the existing `IDLE_ANT_LAYOUT`, with 15 ants distributed across the 480 × 600 portrait canvas. Concentration is roughly: 4 ants in the HUD strip area (top), 7 ants spread across the playfield (middle), 4 ants in the strip area (bottom). Same shape vocabulary (size 0/1/2 + rotation), just new positions chosen by hand to look balanced at 480 × 600.

The title-cake on the idle splash also moves: in landscape it sits at roughly the playfield center; in portrait it sits at roughly playfield-top with the title overlapping its bottom (matching the 2026-05-09 user request "Make the title a bit larger and move it to overlap the bottom of the cake"). Coordinates picked at relayout time from a constant table.

The pause / win / lose splash overlays are anchored to the playfield rect (not the canvas rect). They paint a dimmed overlay across the playfield only, leaving the strip visible. This matches today's landscape behavior, which paints over the playfield only and leaves the right sidebar live.

### 8. CSS

Add a portrait media query that overrides the canvas aspect ratio:

```css
.demo-ant-buster #ab-canvas {
  width: 1280px;
  max-width: 100%;
  height: auto;
  aspect-ratio: 16 / 9;
  /* … existing properties … */
}

@media (max-aspect-ratio: 1/1) {
  .demo-ant-buster #ab-canvas {
    aspect-ratio: 480 / 600;
    width: 480px;   /* internal coord pixel-perfect at desktop, scales on phone */
  }
}
```

The `(max-aspect-ratio: 1/1)` query fires on any viewport that's taller than wide, including narrow desktop windows. That is intentional — if a desktop user shrinks the browser to portrait the canvas reflows just like a phone.

Existing breakpoint at `(max-width: 1000px)` stays. Both queries can match; the order keeps the portrait override winning.

`.ab-stage` keeps its current padding and centering. `display: flex; justify-content: center` already does the right thing for either canvas shape.

### 9. Hit-test rectangle adaptation

All hit-test rects in the pointerdown / pointermove handlers either:

- Use canvas-relative coords that don't depend on `WIDTH` / `SHOP_W` (`pauseRect` at `(WIDTH - 122, 2, 22, 20)`). These work after relayout because `WIDTH` is updated.
- Are written into `ui.*Rect` fields by the per-mode draw functions (`drawShop`, `drawTowerPanel`, `drawShopInfoPanel`). These get rewritten every frame, so they pick up the new positions automatically.
- Reference the playfield bounds (`p.x < PLAYFIELD_W` for "is the tap in the playfield?"). In landscape playfield is `0..480`; in portrait playfield is `0..480` AND `y < HUD_H + PLAYFIELD_H = 360`. The single x-comparison in landscape becomes an x AND y comparison in portrait. Add a `inPlayfield(p)` helper that does the right check per orientation.

Net effect on the pointer-event code: about 8 sites that say `p.x < PLAYFIELD_W` become calls to `inPlayfield(p)`. No other input-handler change.

### 10. Tap-target verification

On a 375-screen-px phone, the 480-px-wide portrait canvas displays at 375 / 480 = 0.78 × scale. Tap targets in screen-px:

- Cannon snap target (24 game-px) ⇒ 18.75 screen-px
- Shop card (115 × 230 game-px) ⇒ 90 × 180 screen-px
- "?" hangtag (14 × 14 game-px) ⇒ 11 × 11 screen-px (still small, but lifted from 9 × 9)
- Targeting button (22 × 22 game-px) ⇒ 17 × 17 screen-px
- Pause / speed widget (22 × 20 game-px) ⇒ 17 × 16 screen-px

The cannon and shop card targets are the most important — those are the gestures players make every wave. They jump from 14 to 19 and from 28 wide to 90 wide respectively. The "?" hangtag and HUD widgets stay marginal but don't get worse than landscape on a small viewport, and the visual coherence pass already accepted those sizes for landscape.

## Migration and risks

- **Portrait detection edge case.** Square viewports (`width === height`) hit the boundary. The `(max-aspect-ratio: 1/1)` query is inclusive at 1:1, so a 600 × 600 viewport renders portrait. JS uses `>=` for the same boundary so CSS and JS agree.
- **Rotation mid-gesture.** If the player rotates while the pointer is down, `pointercancel` fires per the Pointer Events spec; the existing handler clears Pending and Dragging silently. Armed survives. After cancel, `relayout()` runs and the strip re-renders in the new orientation. Acceptable.
- **Game-world effects rendered with screen-shake.** `Math.random` jitter in screenshake reads from the canvas context's transform; relayout doesn't change that. Splash overlays paint over the playfield rect, which is correct in either orientation.
- **Sprite caches.** All 30+ procedural sprites are generated once at boot and cached. None of them read `WIDTH` or `HEIGHT` directly — they're all sized to fixed pixel dimensions like 12 × 12 or 32 × 32. No regeneration required on relayout. The hook is added regardless so a future dimension-aware sprite doesn't silently break.
- **`ctx.measureText` and font availability.** Both orientations use the same monospace fonts. No cache invalidation needed.
- **WordPress inline-script gotcha.** Per `CLAUDE.md`, `&&` and `||` get HTML-encoded inside inline `<script>` tags. The new code uses nested `if` / `else if` exclusively. Verified post-edit with `curl ... | grep '&#038;&#038;'` returning 0.

## Verification checklist

The implementation lands when all 18 items pass on a real iPhone in portrait, plus the existing 17-item visual coherence checklist still passes on desktop landscape.

Portrait checklist:

1. Loading the demo at `/demos/ant-buster/` in portrait orientation shows a 480-wide × 600-tall canvas (proportional). HUD on top, playfield in the middle with the cake and idle ants visible, four-card shop strip at the bottom. No empty bands and no horizontal scroll.
2. The four shop cards are visible, each ~90 screen-px wide on a 375-wide phone. Card sprite, name, cost, and a two-stat (DMG / RATE) line are all readable.
3. Tapping a shop card arms it (yellow border + "TAP A SPOT" pill at the bottom of the card).
4. Tapping the playfield with an armed card places the cannon. The ghost preview appears under the finger, the placement commits on lift.
5. Tapping a placed cannon opens the detail panel inside the strip area (no playfield content moves).
6. The detail panel's left column shows the cannon name, family, four-stat row (DMG/RATE/RNG/KILLS), and the targeting row (5 buttons C/F/L/S/W). Tapping each targeting button works; the active one shows the colored underline.
7. The detail panel's right column shows up to 3 upgrade rows with cost + DMG/RATE/RNG. Affordable upgrades are full opacity, unaffordable are dimmed with red cost. God-tier rows are dimmed gold-tinted before wave 15.
8. The "Sell · +N" red wooden button at the bottom of the right column refunds correctly.
9. Tapping the "‹ BACK TO CANNONS" button at the top of the detail panel returns to the four-card list.
10. Tapping a "?" hangtag on a shop card opens the info panel.
11. The info panel's left column shows name, family, three-stat row (DMG/RATE/RNG), and a wrapped description on cream paper.
12. The info panel's right column shows the "EVOLVES TO" stack of cream cards (up to 3).
13. Tapping a "?" hangtag on a detail-panel upgrade row opens the info panel for that upgrade kind. The back button reads "‹ BACK TO ‹parent kind name›" and returns to the parent cannon's detail panel.
14. The HUD reads correctly in portrait: gold, cake, wave, prep tags on the left; pause / speed / Send-Next on the right; pause / speed widgets are tappable.
15. The pause / win / lose splash overlays paint across the playfield only, leaving the strip visible underneath.
16. The idle splash shows the title cake at playfield top with the title overlapping its bottom, plus 15 ants scattered around.
17. Rotating the phone from portrait to landscape (or vice versa) flips the layout in place. Game state (gold, wave, placed cannons, paused state) is preserved.
18. The same operations on desktop landscape (a 1200-wide browser window) render byte-identically to today. Any visual diff is a regression.

## Out of scope

- iOS in-game vibration feedback on tap
- Landscape-on-phone optimization (current 16:9 already roughly fits — not addressed here)
- A "rotate for best experience" hint
- Two-finger gestures (zoom, two-finger pan)

These are intentionally deferred. If portrait support feels good after this lands, vibration and landscape-phone polish can be a follow-up sub-project.
