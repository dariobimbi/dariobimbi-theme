# Ant Buster: cannon sprite refresh (infrastructure pass)

Date: 2026-05-18
Status: design, awaiting review
Scope: sprite-loading + render-fallback plumbing in `patterns/demo-ant-buster.php`, plus an authoring contract and repo hygiene for the new PNG sprite pipeline

## Context

Every cannon in Ant Buster currently renders procedurally. `patterns/demo-ant-buster.php` defines `makeCannonTurret*()` factories that draw small canvases pixel-by-pixel (around line 1070 and again at 1096); `drawCannonTurrets()` at line 2978 rotates each to face its target and `drawImage`s it onto the playfield at offset `(-10, -16)`.

The user has been authoring hand-drawn PNG sprites for the heavy cannon class in Photoshop, with AI iteration for variants. The current state of `assets/sprites/cannons/heavy/`:

- 14 large side-view PNG mockups (Mortar, Railgun, Demolisher, Discus, Patriot, Hellfire, Shockwave, Particle Cannon, Annihilator, Cluster Strike, Saturation, Vortex, Earthshaker, Ricochet) at 5–6 MB each — Photoshop art-direction renders, not game-ready
- Two PSD sources (`Annihilator.psd`, `Vortex.psd`)
- Numbered iteration variants (`Annihilator-2.png`, `Vortex-3.png`, `Siege-3.png`) and one raw export (`Siege copy.raw`)
- Two in-progress small sprite strips (`howitzer.png`, `Siege.png`) — closer to game-ready but not yet matching a documented spec

None of these files are referenced by the runtime. The folder is a workshop, not a production asset path.

Two problems block adoption: (1) the PNGs themselves are not aligned to a consistent pivot, size, or orientation, so the renderer can't load them generically; (2) there is no infrastructure in `demo-ant-buster.php` to load and draw external images for cannons. This spec addresses both — without committing any final sprite art in this pass.

## Goals

1. Land the sprite-loading + render-fallback infrastructure so a future commit can replace any cannon's procedural rendering by dropping a PNG plus a one-line `spriteFile:` field.
2. Establish a single authoring contract (canvas size, pivot, orientation, lighting, naming) that every heavy-class cannon PNG must conform to.
3. Move PSD sources out of the web-served `assets/` tree and into `tools/`.
4. Add `.gitignore` patterns so numbered iteration variants and raw exports stop polluting the repo.
5. Delete the 14 obsolete side-view art-direction mockups (~80 MB) currently sitting in `assets/sprites/cannons/heavy/`.
6. Zero gameplay or visual regression: with no PNGs present, every cannon must render byte-identical to today via the procedural fallback.

## Non-goals

- Authoring any new sprite art in this pass. The first PNG ships in a follow-up commit by the user.
- Touching cannons outside the heavy class. Reaper, Pink Rubber, stealth, gold, and any other classes stay procedural until their own follow-up plans.
- Animation. Each cannon is a single static frame. Existing muzzle-flash and recoil math continue to provide firing feedback.
- An atlas / sprite-sheet packer or any build step. Individual PNGs served over HTTP/2.
- Per-tier variant filenames (`<kind>__t2.png` etc.). If visual upgrades ever ship, that's a future spec.

## Design

### 1. Authoring contract (PSD template + export rules)

Every heavy-class cannon PNG must satisfy:

| Field | Value |
|---|---|
| Canvas size | 32 × 32 px |
| Pivot | Geometric center, i.e. the corner between pixels (15, 15) and (16, 16). The renderer draws the image at `(-w/2, -h/2)` after `ctx.translate` + `ctx.rotate`, so an even-width canvas with art drawn symmetric around the column-15/column-16 boundary rotates wobble-free |
| Orientation | Barrel points straight up (north). At `c.heading = -π/2` the cannon visually aims toward the top of the screen. The renderer applies `ctx.rotate(c.heading + π/2)` so the barrel ends up pointing along `c.heading` |
| Barrel tip y-coordinate | `y = 2`. The existing muzzle-flash code at line 3001 spawns the flash at `tipDist = 14` pixels from cannon center along the heading. For a 32 × 32 sprite with pivot at (16, 16), 14 pixels above the pivot lands at `y = 2`. Authoring the barrel tip elsewhere creates a visible gap or overlap between the visible muzzle and the flash |
| Lighting | Flat / top-down only. No directional shading (no "light from upper-left"), because the sprite rotates 360° around its center and any directional shading rotates with it, breaking the read |
| Background | Fully transparent |
| Edge margin | 3 px transparent margin on LEFT, RIGHT, and BOTTOM. The TOP edge is the barrel zone — the barrel tip is allowed to reach y = 2, and the muzzle silhouette can extend toward y = 0 within reason |
| Format | PNG-8 with alpha, or PNG-24 with alpha. No JPG |
| File path | `assets/sprites/cannons/heavy/<kind>.png` |
| `<kind>` | Matches the `CANNON_KINDS` JavaScript key exactly, case-sensitive (e.g. `howitzer`, `particleCannon`, `clusterStrike` — the exact spelling will be confirmed from the code when the implementation plan is written) |

PSDs follow the same name and live at `tools/sprites/cannons/heavy/<kind>.psd`. There is no machine-checked template file in this spec — the contract is the table above.

### 2. Renderer plumbing

Two additions to `patterns/demo-ant-buster.php`.

**Theme URI constant.** Inject the theme URI as a JS constant near the top of the IIFE so subsequent image loads have an absolute base path that survives WordPress subpath routing:

```js
var THEME_URI = '<?php echo esc_url( get_template_directory_uri() ); ?>';
var SPRITE_VER = '<?php echo esc_js( wp_get_theme()->get('Version') ); ?>';
```

`SPRITE_VER` is appended as `?v=...` to every sprite URL so that bumping `style.css` Version invalidates the browser cache.

**Preload loop.** Where `game.sprites` is populated (around line 1614), add:

```js
game.sprites.cannonTurretPNG = {};
for (var k in CANNON_KINDS) {
  var f = CANNON_KINDS[k].spriteFile;
  if (f) {
    var img = new Image();
    img.src = THEME_URI + '/assets/sprites/cannons/' + f + '?v=' + SPRITE_VER;
    game.sprites.cannonTurretPNG[k] = img;
  }
}
```

This is non-blocking. Cannons with no `spriteFile` field never get an `Image` object; cannons whose image is still loading fall through to procedural; cannons whose image 404'd fall through to procedural (the browser logs a 404 to console but no JS error fires).

**Render branch.** Replace the body of `drawCannonTurrets()` at line 2978's hot path:

```js
var pngSprite = game.sprites.cannonTurretPNG[c.kind];
var pngReady = false;
if (pngSprite) {
  if (pngSprite.complete) {
    if (pngSprite.naturalWidth) { pngReady = true; }
  }
}
var turretSprite, drawX, drawY;
if (pngReady) {
  turretSprite = pngSprite;
  drawX = -pngSprite.naturalWidth / 2;
  drawY = -pngSprite.naturalHeight / 2;
} else {
  turretSprite = game.sprites.turrets[c.kind];
  if (!turretSprite) { turretSprite = game.sprites.turrets.howitzer; }
  drawX = -10; drawY = -16;
}
ctx.drawImage(turretSprite, drawX, drawY);
```

Nested `if`s instead of `&&` chains, per the CLAUDE.md gotcha that WordPress sometimes HTML-encodes `&&` inside inline `<script>` blocks to `&#038;&#038;` and breaks the JS parser. The PNG-side draw offsets are derived from `naturalWidth`/`naturalHeight` instead of hard-coded constants, so a 33 × 33 art mistake still renders centered (just slightly off-grid) instead of jumping by half a pixel.

The procedural-fallback offsets `(-10, -16)` are preserved exactly as today.

### 3. Per-cannon migration workflow (post-plan, not in this commit)

Each cannon ships in its own future commit. The recipe:

1. Open `tools/sprites/cannons/heavy/<kind>.psd` (or create a new one from the 32 × 32 contract above).
2. Author the sprite. Confirm against the table in Section 1.
3. Export PNG → `assets/sprites/cannons/heavy/<kind>.png`. Filename must match the `CANNON_KINDS` key.
4. In `patterns/demo-ant-buster.php`, set `spriteFile: 'heavy/<kind>.png'` on the `CANNON_KINDS[<kind>]` entry.
5. Bump `style.css` Version (cache-busts the sprite and triggers admin reconciliation if needed).
6. Manual QA: place that cannon in the demo, confirm it renders as PNG, confirm it rotates correctly, confirm a different cannon next to it still renders procedurally, confirm the muzzle flash sits at the barrel tip.
7. Commit.

A short version of this recipe lives at `assets/sprites/cannons/README.md` (added by this plan) so the author has it next to the assets.

### 4. Repo hygiene

**Move:**

```
assets/sprites/cannons/heavy/Annihilator.psd  →  tools/sprites/cannons/heavy/Annihilator.psd
assets/sprites/cannons/heavy/Vortex.psd       →  tools/sprites/cannons/heavy/Vortex.psd
```

**Delete (uncommitted scraps from the workshop):**

```
assets/sprites/cannons/heavy/Annihilator-2.png
assets/sprites/cannons/heavy/Annihilator-3.png
assets/sprites/cannons/heavy/Siege copy.raw
assets/sprites/cannons/heavy/Siege-3.png
assets/sprites/cannons/heavy/Vortex-2.png
assets/sprites/cannons/heavy/Vortex-3.png
```

**Delete (the 14 obsolete side-view art-direction mockups, ~80 MB):**

```
assets/sprites/cannons/heavy/Annihilator.png
assets/sprites/cannons/heavy/Cluster Strike.png
assets/sprites/cannons/heavy/Demolisher.png
assets/sprites/cannons/heavy/Discus.png
assets/sprites/cannons/heavy/Earthshaker.png
assets/sprites/cannons/heavy/Hellfire.png
assets/sprites/cannons/heavy/Mortar.png
assets/sprites/cannons/heavy/Particle Cannon.png
assets/sprites/cannons/heavy/Patriot.png
assets/sprites/cannons/heavy/Railgun.png
assets/sprites/cannons/heavy/Ricochet.png
assets/sprites/cannons/heavy/Saturation.png
assets/sprites/cannons/heavy/Shockwave.png
assets/sprites/cannons/heavy/Vortex.png
```

These are the original side-view Photoshop renders that don't match the new 32 × 32 top-down spec. They're not loaded by the runtime today and won't be loaded under the new infrastructure either. Annihilator and Vortex have PSD sources moving to `tools/`; the other 12 can be regenerated from AI prompts if reference is ever needed.

**Also delete (in-progress small sprite strips that don't match the new single-frame 32 × 32 spec):**

```
assets/sprites/cannons/heavy/howitzer.png
assets/sprites/cannons/heavy/Siege.png
```

Both currently show as modified in `git status`. They're sprite strips with 8+ frames, which conflicts with the "1 PNG = 1 cannon, single frame" rule. The user re-exports them at 32 × 32 as part of the post-plan authoring workflow.

**`.gitignore` additions:**

```
# Sprite authoring scraps. Final exports go in assets/sprites/cannons/<class>/<kind>.png;
# PSD sources live in tools/sprites/cannons/<class>/<kind>.psd. Anything else
# (numbered iteration variants, "copy" suffixes, raw exports, stray PSDs in the
# web-served tree) is workshop noise.
assets/sprites/cannons/**/*-[0-9].png
assets/sprites/cannons/**/*-[0-9][0-9].png
assets/sprites/cannons/**/* copy*
assets/sprites/cannons/**/*.raw
assets/sprites/cannons/**/*.psd
```

After this plan lands, `assets/sprites/cannons/heavy/` contains: the new `README.md`, and nothing else until a future commit ships the first authored sprite.

### 5. Theme version bump

`style.css` Version goes from `0.16.0` to `0.17.0`. The bump:

- Cache-busts the enqueued stylesheet (standard reason).
- Forces `inc/auto-pages.php` and `inc/sitemap.php` to re-run their reconciliation, since both are version-keyed. Neither cares about sprites, but the bump is the codebase's documented signal that the theme changed, and CLAUDE.md prescribes it for "any change touching paths."

## Testing / QA

This is a WordPress theme with no automated test suite. QA is manual visual + console.

**Infrastructure-only pass (this plan's commit, no art yet):**

1. Open `http://localhost/dariobimbi.com/demos/ant-buster/` in an **incognito** window. Admin bar and Site Kit are stripped, matching production-like measurement.
2. Open DevTools → Network. Filter to `Img`. No requests under `assets/sprites/cannons/`. Confirms the preload loop correctly skips cannons without `spriteFile`.
3. Open DevTools → Console. No errors, no warnings about missing images.
4. Place each of the 14 heavy cannons on the playfield. All render via the procedural fallback. Visual identical to the pre-plan main branch.
5. Play through wave 5. No rendering regressions. Muzzle flashes still sit at the barrel tip of every procedural cannon (this is unchanged code, but worth confirming).
6. `curl` the rendered page and grep the inline script for `&#038;`. Zero matches expected — confirms WordPress didn't HTML-encode any operator in the new code.

**Post-plan smoke test (manual, after the user authors the first sprite):**

1. Drop a 32 × 32 `howitzer.png` (or any conforming cannon) at `assets/sprites/cannons/heavy/howitzer.png`.
2. Set `spriteFile: 'heavy/howitzer.png'` on `CANNON_KINDS.howitzer`.
3. Bump `style.css` Version.
4. Hard-reload the demo page.
5. Place a howitzer. It renders as PNG.
6. Place a mortar (no `spriteFile`). It renders procedurally. Both on screen at once: no glitch, no flicker.
7. Watch the howitzer track an ant through a curved path. Turret rotates smoothly to face the ant. Muzzle flash spawns at the visible barrel tip, not 2 px behind it.
8. Rename the PNG temporarily to break the path. Reload. Howitzer falls back to procedural. Console logs a 404 (expected). No JS error.

## Risks and open questions

- **Filename normalization.** The current `assets/sprites/cannons/heavy/` folder has files with spaces and capitals (`Particle Cannon.png`, `Cluster Strike.png`). After the Section 4 deletions those files are gone. New PNGs use `<kind>.png` matching the JS keys exactly. The implementation plan will list the exact `CANNON_KINDS` key spellings (e.g. confirm `particleCannon` vs `particle_cannon`) when the JS file is read carefully.
- **Inline-script HTML-encoding.** The new render branch deliberately avoids `&&`, `||`, and `>` operators. The `curl + grep` step in QA verifies post-edit. If WordPress encodes some other operator, the JS parser will throw and the demo will visibly break — fast-failure, not silent.
- **Procedural code stays forever.** The fallback isn't a temporary scaffold. Cannons outside the heavy class will be added in future plans, and any class that never gets PNG art continues to render procedurally indefinitely. This is intentional, not technical debt.
- **No image preloader UX.** The first time a page loads, the user may briefly see procedural renders before the PNG swap in once the image decodes. With 14 sprites at ~5 KB each, on broadband this is sub-100 ms and acceptable. If it becomes a visible glitch in QA, the implementation plan can add a per-image `onload` callback that defers the first render until images settle. Not in scope unless QA flags it.
- **Mobile portrait layout (theme version 0.16.0).** Sprite rendering is the same regardless of orientation — the playfield rect is `480 × 336` in both. No additional handling needed.
