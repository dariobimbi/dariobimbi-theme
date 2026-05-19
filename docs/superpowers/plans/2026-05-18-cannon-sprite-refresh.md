# Cannon Sprite Refresh (Infrastructure Pass) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Land the sprite-loading + render-fallback plumbing in `patterns/demo-ant-buster.php`, the authoring contract for new PNG cannon sprites, and the repo hygiene cleanup — without committing any new sprite art.

**Architecture:** Two PHP-interpolated JS constants (`THEME_URI`, `SPRITE_VER`) carry the theme URL and cache-bust query string into the JS scope. A preload loop runs after `CANNON_KINDS` is declared and creates `Image` objects only for cannons that opt in via a `spriteFile` field. The renderer's per-cannon hot path tries the PNG; if missing, broken, or still loading it falls back to the existing procedural `makeCannonTurret*()` sprite, byte-identical to today.

**Tech Stack:** Plain PHP + JavaScript inside a WordPress block pattern. No build step. No test framework. Manual incognito-browser QA + `curl | grep` verification.

**Reference spec:** [docs/superpowers/specs/2026-05-18-cannon-sprite-refresh-design.md](../specs/2026-05-18-cannon-sprite-refresh-design.md) (commit `f3586a5`).

---

## Files

- Modify: `.gitignore` — add sprite-workshop ignore patterns
- Create: `tools/sprites/cannons/heavy/` — new directory for PSD sources
- Move: `assets/sprites/cannons/heavy/Annihilator.psd` → `tools/sprites/cannons/heavy/Annihilator.psd`
- Move: `assets/sprites/cannons/heavy/Vortex.psd` → `tools/sprites/cannons/heavy/Vortex.psd`
- Delete: 14 obsolete art-direction PNGs in `assets/sprites/cannons/heavy/` (Mortar, Railgun, Demolisher, Discus, Patriot, Hellfire, Shockwave, Particle Cannon, Annihilator, Cluster Strike, Saturation, Vortex, Earthshaker, Ricochet)
- Delete: `assets/sprites/cannons/heavy/howitzer.png` and `Siege.png` (in-progress sprite strips that don't match the new spec)
- Delete: 6 untracked workshop scraps (`Annihilator-2.png`, `Annihilator-3.png`, `Siege-3.png`, `Siege copy.raw`, `Vortex-2.png`, `Vortex-3.png`)
- Create: `assets/sprites/cannons/README.md` — short authoring recipe
- Modify: `patterns/demo-ant-buster.php` — three additions:
  - line ~278 (after `var game = {…};`): `THEME_URI` + `SPRITE_VER` constants
  - line ~5514 (after `CANNON_KINDS` closing `};`): preload loop
  - lines 2985-2987 (inside `drawCannonTurrets()`): replace render branch
- Modify: `style.css` line 7 — bump theme Version `0.16.0` → `0.17.0`

---

## Task 1: Repo hygiene (gitignore + PSD move + cleanup + README)

**Files:**
- Modify: `.gitignore`
- Move: `assets/sprites/cannons/heavy/Annihilator.psd` → `tools/sprites/cannons/heavy/Annihilator.psd`
- Move: `assets/sprites/cannons/heavy/Vortex.psd` → `tools/sprites/cannons/heavy/Vortex.psd`
- Delete: 22 files in `assets/sprites/cannons/heavy/`
- Create: `assets/sprites/cannons/README.md`

- [ ] **Step 1: Verify the working tree starts in the expected state**

Run:
```
git status -s assets/sprites/cannons/heavy/
```

Expected output (order may vary):
```
 M assets/sprites/cannons/heavy/Siege.png
 M assets/sprites/cannons/heavy/howitzer.png
?? assets/sprites/cannons/heavy/Annihilator-2.png
?? assets/sprites/cannons/heavy/Annihilator-3.png
?? assets/sprites/cannons/heavy/Annihilator.psd
?? "assets/sprites/cannons/heavy/Siege copy.raw"
?? assets/sprites/cannons/heavy/Siege-3.png
?? assets/sprites/cannons/heavy/Vortex-2.png
?? assets/sprites/cannons/heavy/Vortex-3.png
?? assets/sprites/cannons/heavy/Vortex.psd
```

If you see additional untracked files in that folder, list them in the task notes — they're probably more iteration scraps to delete.

- [ ] **Step 2: Append sprite-workshop patterns to `.gitignore`**

Open `.gitignore` and append this block at the end of the file (after the existing `assets/projects/lost-probe/*.exe` entry):

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

- [ ] **Step 3: Create the new PSD source directory**

Run:
```
mkdir -p tools/sprites/cannons/heavy
```

Verify:
```
ls -la tools/sprites/cannons/heavy/
```

Expected: empty directory exists.

- [ ] **Step 4: Move the two PSD files into the new directory**

Run:
```
mv "assets/sprites/cannons/heavy/Annihilator.psd" "tools/sprites/cannons/heavy/Annihilator.psd"
mv "assets/sprites/cannons/heavy/Vortex.psd"      "tools/sprites/cannons/heavy/Vortex.psd"
```

Verify:
```
ls tools/sprites/cannons/heavy/
```

Expected: `Annihilator.psd  Vortex.psd`

- [ ] **Step 5: Delete the 14 obsolete art-direction PNGs (tracked)**

These were committed in earlier work. Use `git rm` so they leave the index:

```
git rm "assets/sprites/cannons/heavy/Annihilator.png"
git rm "assets/sprites/cannons/heavy/Cluster Strike.png"
git rm "assets/sprites/cannons/heavy/Demolisher.png"
git rm "assets/sprites/cannons/heavy/Discus.png"
git rm "assets/sprites/cannons/heavy/Earthshaker.png"
git rm "assets/sprites/cannons/heavy/Hellfire.png"
git rm "assets/sprites/cannons/heavy/Mortar.png"
git rm "assets/sprites/cannons/heavy/Particle Cannon.png"
git rm "assets/sprites/cannons/heavy/Patriot.png"
git rm "assets/sprites/cannons/heavy/Railgun.png"
git rm "assets/sprites/cannons/heavy/Ricochet.png"
git rm "assets/sprites/cannons/heavy/Saturation.png"
git rm "assets/sprites/cannons/heavy/Shockwave.png"
git rm "assets/sprites/cannons/heavy/Vortex.png"
```

If any `git rm` errors with "did not match any files," that file isn't tracked — fall back to `rm` for it and move on.

- [ ] **Step 6: Delete the two in-progress sprite strips (tracked, modified)**

```
git rm -f "assets/sprites/cannons/heavy/howitzer.png"
git rm -f "assets/sprites/cannons/heavy/Siege.png"
```

The `-f` is required because both currently show as modified in `git status`. The new spec requires single-frame 32×32 PNGs; these are 8-frame strips. They get re-authored to spec in a future commit.

- [ ] **Step 7: Delete the 6 untracked workshop scraps**

These are untracked, so `git rm` won't apply. Use plain `rm`:

```
rm "assets/sprites/cannons/heavy/Annihilator-2.png"
rm "assets/sprites/cannons/heavy/Annihilator-3.png"
rm "assets/sprites/cannons/heavy/Siege copy.raw"
rm "assets/sprites/cannons/heavy/Siege-3.png"
rm "assets/sprites/cannons/heavy/Vortex-2.png"
rm "assets/sprites/cannons/heavy/Vortex-3.png"
```

- [ ] **Step 8: Verify the heavy folder is now empty**

Run:
```
ls assets/sprites/cannons/heavy/
```

Expected: empty (no output).

If anything remains, it's an unexpected scrap — list it in the task notes and delete it manually if it matches the workshop-scrap pattern.

- [ ] **Step 9: Create the authoring README**

Create `assets/sprites/cannons/README.md` with this exact content:

```markdown
# Cannon Sprite Authoring

These are the production PNG assets loaded by the Ant Buster demo
([patterns/demo-ant-buster.php](../../../patterns/demo-ant-buster.php)). The full
contract is in [docs/superpowers/specs/2026-05-18-cannon-sprite-refresh-design.md](../../../docs/superpowers/specs/2026-05-18-cannon-sprite-refresh-design.md).

## Recipe for adding a new cannon sprite

1. Open or create the PSD at `tools/sprites/cannons/<class>/<kind>.psd`.
2. Author at **32 × 32 px**, transparent background, barrel pointing **up**,
   barrel tip at **y = 2** (14 px above the pivot — matches the existing
   muzzle-flash spawn distance), flat top-down lighting (no directional
   shading), 3 px transparent margin on LEFT, RIGHT, and BOTTOM.
3. Export PNG to `assets/sprites/cannons/<class>/<kind>.png`. The filename
   must match the `CANNON_KINDS` JavaScript key exactly (case-sensitive).
4. In `patterns/demo-ant-buster.php`, set `spriteFile: '<class>/<kind>.png'`
   on the `CANNON_KINDS[<kind>]` entry.
5. Bump `style.css` Version (cache-busts the sprite via the `?v=` query string).
6. Hard-reload `/demos/ant-buster/` in an incognito window. Place the cannon
   on the playfield. Confirm it renders as PNG, rotates smoothly to face
   ants, and the muzzle flash sits at the visible barrel tip.
7. Commit.

## What does NOT belong in this folder

- Numbered iteration variants (`howitzer-2.png`, `howitzer-3.png`, …) —
  `.gitignore`'d. Pick a winner, export it as `howitzer.png`, delete the rest.
- PSD or RAW sources — `.gitignore`'d. They live under `tools/sprites/`.
- Sprite strips with multiple frames per cannon. Each cannon is one frame.
  Existing muzzle-flash and recoil math provide firing feedback.
```

- [ ] **Step 10: Verify final working tree**

Run:
```
git status -s
```

Expected (order may vary; only sprite-related lines shown):
```
 M .gitignore
 D assets/sprites/cannons/heavy/Annihilator.png
 D assets/sprites/cannons/heavy/Cluster Strike.png
 D assets/sprites/cannons/heavy/Demolisher.png
 D assets/sprites/cannons/heavy/Discus.png
 D assets/sprites/cannons/heavy/Earthshaker.png
 D assets/sprites/cannons/heavy/Hellfire.png
 D assets/sprites/cannons/heavy/Mortar.png
 D assets/sprites/cannons/heavy/Particle Cannon.png
 D assets/sprites/cannons/heavy/Patriot.png
 D assets/sprites/cannons/heavy/Railgun.png
 D assets/sprites/cannons/heavy/Ricochet.png
 D assets/sprites/cannons/heavy/Saturation.png
 D assets/sprites/cannons/heavy/Shockwave.png
 D assets/sprites/cannons/heavy/Siege.png
 D assets/sprites/cannons/heavy/Vortex.png
 D assets/sprites/cannons/heavy/howitzer.png
?? assets/sprites/cannons/README.md
?? tools/sprites/cannons/heavy/
```

No `Annihilator.psd`, `Vortex.psd`, `Annihilator-2.png`, etc. should appear. The 6 untracked scraps are gone; the 2 tracked PSDs are gone from `assets/` and re-appear under `tools/`.

- [ ] **Step 11: Commit**

```
git add .gitignore assets/sprites/cannons/README.md tools/sprites/cannons/heavy/Annihilator.psd tools/sprites/cannons/heavy/Vortex.psd
git add -A assets/sprites/cannons/heavy/
git commit -m "$(cat <<'EOF'
chore(ant-buster): sprite-folder hygiene for cannon refresh

Move PSD sources to tools/sprites/cannons/heavy/, delete the obsolete
side-view art-direction mockups and in-progress sprite strips from
assets/sprites/cannons/heavy/, add the authoring README, and gitignore
numbered iteration variants and raw/PSD files inside assets/sprites/cannons/.

Per docs/superpowers/specs/2026-05-18-cannon-sprite-refresh-design.md.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

- [ ] **Step 12: Verify commit landed cleanly**

Run:
```
git status -s
git log --oneline -1
```

Expected: empty `git status` (or only unrelated working-tree changes). The latest commit subject is the chore message from Step 11.

---

## Task 2: Inject `THEME_URI` and `SPRITE_VER` JS constants

**Files:**
- Modify: `patterns/demo-ant-buster.php:277-278`

- [ ] **Step 1: Confirm the injection target**

Run:
```
sed -n '275,285p' patterns/demo-ant-buster.php
```

Expected output:
```
    godTierUnlocked: false,
    godTierUnlockPulseStart: 0
  };

  // Module-level rect helper used by HUD hit-tests. Avoids inline and-operator chains
  // (which the WP content filter occasionally HTML-encodes) and centralizes the
  // pattern that future tasks will reuse for upgrade buttons, selected-tower UI, etc.
  function inRect(p, r) {
    if (p.x < r.x) { return false; }
    if (p.x > r.x + r.w) { return false; }
```

The constants will go between line 277 (the closing `};` of `var game = {…};`) and line 279 (the `inRect` helper comment).

- [ ] **Step 2: Insert the constants**

Edit `patterns/demo-ant-buster.php`. Find the block (around line 277):

```
    godTierUnlocked: false,
    godTierUnlockPulseStart: 0
  };

  // Module-level rect helper used by HUD hit-tests.
```

Replace it with:

```
    godTierUnlocked: false,
    godTierUnlockPulseStart: 0
  };

  // Theme URI and cache-bust version, injected from PHP. Used by the PNG
  // cannon-sprite loader registered after CANNON_KINDS is declared.
  var THEME_URI = '<?php echo esc_url( get_template_directory_uri() ); ?>';
  var SPRITE_VER = '<?php echo esc_js( wp_get_theme()->get('Version') ); ?>';

  // Module-level rect helper used by HUD hit-tests.
```

(Only the two `var` lines and their two-line comment are new. The surrounding context is preserved.)

- [ ] **Step 3: Confirm PHP rendered correctly**

Run:
```
curl -s "http://localhost/dariobimbi.com/demos/ant-buster/" | grep -E "var THEME_URI|var SPRITE_VER"
```

Expected output (something like):
```
  var THEME_URI = 'http://localhost/dariobimbi.com/wp-content/themes/dariobimbi';
  var SPRITE_VER = '0.16.0';
```

The exact URI may vary by local setup. What matters: both lines appear, both have non-empty quoted strings, and no `<?php` tags leak through.

If the curl returns nothing or shows literal `<?php` tags, the pattern file's `<?php` block isn't being evaluated — check the WordPress page is using the correct template and the pattern is referenced by `<!-- wp:pattern {"slug":"dariobimbi/demo-ant-buster"} /-->`.

- [ ] **Step 4: Confirm WordPress didn't HTML-encode anything**

Run:
```
curl -s "http://localhost/dariobimbi.com/demos/ant-buster/" | grep -E "THEME_URI|SPRITE_VER" | grep -E "&#038;|&amp;|&lt;|&gt;"
```

Expected output: empty (no matches).

If anything appears, WordPress encoded a character it shouldn't have. Stop and report — the design assumes raw URLs and version strings reach the browser.

- [ ] **Step 5: Commit**

```
git add patterns/demo-ant-buster.php
git commit -m "$(cat <<'EOF'
feat(ant-buster): inject THEME_URI and SPRITE_VER JS constants

PHP-evaluated theme URL and Version reach JavaScript scope via esc_url() and
esc_js(). Used by the PNG cannon-sprite loader added in the next commit to
build absolute, cache-busted sprite URLs that survive WordPress subpath routing.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 3: Add the PNG preload loop after `CANNON_KINDS`

**Files:**
- Modify: `patterns/demo-ant-buster.php:5513-5515`

- [ ] **Step 1: Confirm the injection target**

Run:
```
sed -n '5510,5520p' patterns/demo-ant-buster.php
```

Expected:
```
      flashScale: 1.7,
      upgradesTo: []
    }
  };

  function cannonAt(x, y) {
    for (var i = 0; i < game.cannons.length; i++) {
      var c = game.cannons[i];
```

Line 5513 is the closing `};` of `var CANNON_KINDS = {…};`. The preload goes between that and `function cannonAt`.

- [ ] **Step 2: Insert the preload block**

Edit `patterns/demo-ant-buster.php`. Find:

```
      flashScale: 1.7,
      upgradesTo: []
    }
  };

  function cannonAt(x, y) {
```

Replace with:

```
      flashScale: 1.7,
      upgradesTo: []
    }
  };

  // PNG cannon-turret preloader. Walks CANNON_KINDS and creates an Image
  // object for every cannon kind that opts in via `spriteFile:`. Loads run
  // asynchronously and never block the game; cannons without a spriteFile
  // entry are skipped, and any cannon whose image is still loading or
  // failed (404) falls through to the procedural sprite in drawCannonTurrets.
  game.sprites.cannonTurretPNG = {};
  for (var __spritePreloadKey in CANNON_KINDS) {
    var __spritePreloadFile = CANNON_KINDS[__spritePreloadKey].spriteFile;
    if (__spritePreloadFile) {
      var __spritePreloadImg = new Image();
      __spritePreloadImg.src = THEME_URI + '/assets/sprites/cannons/' + __spritePreloadFile + '?v=' + SPRITE_VER;
      game.sprites.cannonTurretPNG[__spritePreloadKey] = __spritePreloadImg;
    }
  }

  function cannonAt(x, y) {
```

Notes for the engineer:
- The `__spritePreload*` variable names are intentionally namespaced to avoid colliding with other loop variables in the same function scope (the IIFE).
- No `&&` or `||` operators are used — the `if (__spritePreloadFile)` is a single-value truthy check, dodging the WordPress HTML-encoding gotcha documented in [CLAUDE.md].

- [ ] **Step 3: Confirm WordPress preserved the script unchanged**

Run:
```
curl -s "http://localhost/dariobimbi.com/demos/ant-buster/" | grep -E "cannonTurretPNG|__spritePreload"
```

Expected: three or four lines including:
```
  game.sprites.cannonTurretPNG = {};
  for (var __spritePreloadKey in CANNON_KINDS) {
    var __spritePreloadFile = CANNON_KINDS[__spritePreloadKey].spriteFile;
…
```

Then run:
```
curl -s "http://localhost/dariobimbi.com/demos/ant-buster/" | grep -E "__spritePreload" | grep -E "&#038;|&amp;|&lt;|&gt;"
```

Expected: empty (no encoded entities in the new block).

- [ ] **Step 4: Reload the page in the browser and check Console + Network**

1. Open `http://localhost/dariobimbi.com/demos/ant-buster/` in an **incognito** window.
2. Open DevTools.
3. **Console tab:** no errors. No "Cannot read properties of undefined" or similar.
4. **Network tab → filter Img:** no requests under `assets/sprites/cannons/` (because no cannon yet has a `spriteFile:` field).
5. Verify the game still loads to its idle splash.

If the Console shows a TypeError on `CANNON_KINDS`, the preload block is running before `CANNON_KINDS` is assigned — re-check that the block is placed AFTER the closing `};` of `var CANNON_KINDS = {…};` at line 5513.

- [ ] **Step 5: Commit**

```
git add patterns/demo-ant-buster.php
git commit -m "$(cat <<'EOF'
feat(ant-buster): preload optional PNG turrets per CANNON_KINDS entry

Walks CANNON_KINDS after declaration and creates an Image object for any
cannon with a spriteFile field. Non-blocking; cannons without spriteFile are
skipped. Sets up the runtime map (game.sprites.cannonTurretPNG) the renderer
will consult in the next commit.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 4: Modify `drawCannonTurrets()` to prefer the PNG with procedural fallback

**Files:**
- Modify: `patterns/demo-ant-buster.php:2985-2987`

- [ ] **Step 1: Confirm the target lines**

Run:
```
sed -n '2978,2990p' patterns/demo-ant-buster.php
```

Expected:
```
  function drawCannonTurrets() {
    for (var ci = 0; ci < game.cannons.length; ci++) {
      var c = game.cannons[ci];
      var def = CANNON_KINDS[c.kind];
      ctx.save();
      ctx.translate(Math.round(c.x), Math.round(c.y));
      ctx.rotate(c.heading + Math.PI / 2);
      var turretSprite = game.sprites.turrets[c.kind];
      if (!turretSprite) { turretSprite = game.sprites.turrets.howitzer; }
      ctx.drawImage(turretSprite, -10, -16);
      ctx.restore();
      // Plan 4 — muzzle flash. Renders for FLASH_DUR seconds after the cannon
```

Lines 2985-2987 are what we're replacing.

- [ ] **Step 2: Replace lines 2985-2987 with the PNG-or-procedural branch**

Find this exact block in `patterns/demo-ant-buster.php`:

```
      ctx.rotate(c.heading + Math.PI / 2);
      var turretSprite = game.sprites.turrets[c.kind];
      if (!turretSprite) { turretSprite = game.sprites.turrets.howitzer; }
      ctx.drawImage(turretSprite, -10, -16);
      ctx.restore();
```

Replace with:

```
      ctx.rotate(c.heading + Math.PI / 2);
      // Try the PNG sprite first; fall back to procedural if missing, broken,
      // or still loading. Nested ifs (no && chains) per the CLAUDE.md note about
      // WordPress occasionally HTML-encoding && inside inline scripts.
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
      ctx.restore();
```

Notes:
- The PNG branch derives `drawX`/`drawY` from `naturalWidth`/`naturalHeight`, so a 33×33 art mistake renders centered (slightly off-grid) instead of jumping by half a pixel.
- The fallback branch keeps the existing `(-10, -16)` offsets exactly so procedural rendering is byte-identical to today.
- The `if (pngSprite.naturalWidth)` check covers the 404 case: when an image fails to load, browsers mark `complete = true` but leave `naturalWidth = 0`, which is falsy and correctly routes back to procedural.

- [ ] **Step 3: Confirm WordPress preserved the script unchanged**

Run:
```
curl -s "http://localhost/dariobimbi.com/demos/ant-buster/" | grep -E "pngReady|pngSprite\."
```

Expected: about half a dozen lines including `var pngReady = false;`, the three nested `if`s, the assignment branch, etc.

Then run:
```
curl -s "http://localhost/dariobimbi.com/demos/ant-buster/" | grep -E "pngReady|pngSprite" | grep -E "&#038;|&amp;|&lt;|&gt;"
```

Expected: empty.

If any encoded entity appears, the WordPress content filter has mangled an operator. The most likely culprits in the new code: `<` inside `pngSprite.naturalWidth > 0` (not used — we wrote it as truthy), `&&` (avoided — nested ifs instead), `||` (avoided — `if (!turretSprite) {...}` style). If you see encoding, re-check the substitution copied the exact text shown in Step 2.

- [ ] **Step 4: Reload and confirm cannons still render**

1. Hard-reload `http://localhost/dariobimbi.com/demos/ant-buster/` in **incognito** (Ctrl+Shift+R to bypass cache).
2. Click through to start a game.
3. Drag a Howitzer from the shop onto the playfield. It renders. Drag a Siege next to it. Both render.
4. Open the shop sidebar's Heavy class and place one of each cannon kind that's available in prep mode at wave 1 (likely just Howitzer at start — that's enough to confirm the fallback branch works).
5. **Console tab:** no errors.

- [ ] **Step 5: Commit**

```
git add patterns/demo-ant-buster.php
git commit -m "$(cat <<'EOF'
feat(ant-buster): swap cannon turret to PNG with procedural fallback

drawCannonTurrets() now prefers a per-cannon PNG (from
game.sprites.cannonTurretPNG, populated only for cannons with a spriteFile
field) and falls back to the existing procedural makeCannonTurret*() sprite
when the PNG is missing, still loading, or failed to load. PNG draw offsets
come from naturalWidth/naturalHeight so a misexported asset still renders
centered.

Procedural-side rendering is byte-identical to before this change because no
cannon has a spriteFile yet. Art will land in follow-up commits per cannon.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 5: Bump theme Version and run the infrastructure-only QA pass

**Files:**
- Modify: `style.css:7`

- [ ] **Step 1: Bump the theme Version**

Open `style.css`. Find line 7:

```
Version: 0.16.0
```

Change to:

```
Version: 0.17.0
```

- [ ] **Step 2: Confirm `SPRITE_VER` now reflects the new version**

Run:
```
curl -s "http://localhost/dariobimbi.com/demos/ant-buster/" | grep "SPRITE_VER"
```

Expected:
```
  var SPRITE_VER = '0.17.0';
```

- [ ] **Step 3: Run the spec's infrastructure-only QA pass**

The full checklist from [the spec](../specs/2026-05-18-cannon-sprite-refresh-design.md#testing--qa):

1. Open `http://localhost/dariobimbi.com/demos/ant-buster/` in an **incognito** window. Admin bar and Site Kit are stripped, matching production-like measurement.
2. DevTools → Network → filter `Img`: no requests under `assets/sprites/cannons/`. (Preload loop correctly skipped every cannon — none have `spriteFile`.)
3. DevTools → Console: no errors, no warnings about missing images.
4. Place every heavy cannon you can afford in prep mode (Howitzer at minimum; Mortar / Discus / Siege become affordable after the first wave). All render via procedural fallback.
5. Play through to wave 5. No rendering regressions. Muzzle flashes still sit at the barrel tip of every procedural cannon.
6. Run a final encoding sanity check:
   ```
   curl -s "http://localhost/dariobimbi.com/demos/ant-buster/" | grep -E "THEME_URI|SPRITE_VER|cannonTurretPNG|pngReady" | grep -E "&#038;|&amp;|&lt;|&gt;"
   ```
   Expected: empty.

If any step fails, do NOT proceed to Step 4 (commit). Stop and report what went wrong.

- [ ] **Step 4: Commit the version bump**

```
git add style.css
git commit -m "$(cat <<'EOF'
chore(ant-buster): bump theme to 0.17.0 for cannon sprite refresh

Cache-busts the enqueued style.css and the new ?v= sprite cache-bust string.
Standard housekeeping after touching paths and forcing inc/auto-pages.php +
inc/sitemap.php to re-run their version-keyed reconciliation.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

- [ ] **Step 5: Verify the branch is clean**

Run:
```
git status -s
git log --oneline -5
```

Expected: empty `git status`. The last five commits include (in order, newest first):
1. `chore(ant-buster): bump theme to 0.17.0 for cannon sprite refresh`
2. `feat(ant-buster): swap cannon turret to PNG with procedural fallback`
3. `feat(ant-buster): preload optional PNG turrets per CANNON_KINDS entry`
4. `feat(ant-buster): inject THEME_URI and SPRITE_VER JS constants`
5. `chore(ant-buster): sprite-folder hygiene for cannon refresh`

---

## Task 6: Post-plan smoke test (optional, for confidence that the future authoring flow works)

This task does NOT modify the codebase. It walks through the future authoring flow with a throwaway sprite to confirm the pipeline really works end-to-end, then reverts.

**Files:**
- Create (temporarily): `assets/sprites/cannons/heavy/howitzer.png` (a 32×32 PNG of any kind — a colored square is fine)
- Modify (temporarily): `patterns/demo-ant-buster.php` — add `spriteFile: 'heavy/howitzer.png'` to the howitzer entry

Skip this task if you trust the static QA from Task 5. Run it if you want to see the PNG branch actually fire.

- [ ] **Step 1: Drop a placeholder PNG**

Create any 32×32 PNG with transparency. Quickest option in a shell:

```
c:/wamp64/bin/php/php8.2.29/php.exe -r "
\$im = imagecreatetruecolor(32, 32);
imagesavealpha(\$im, true);
imagefill(\$im, 0, 0, imagecolorallocatealpha(\$im, 0, 0, 0, 127));
imagefilledrectangle(\$im, 4, 16, 28, 28, imagecolorallocate(\$im, 255, 50, 50));
imagefilledrectangle(\$im, 14, 2, 18, 16, imagecolorallocate(\$im, 200, 30, 30));
imagepng(\$im, 'assets/sprites/cannons/heavy/howitzer.png');
"
```

(Confirms the PHP path. The exact binary path may drift when WAMP updates PHP; check `ls c:/wamp64/bin/php/` if `php8.2.29` no longer exists.)

This produces a red bar with a vertical barrel. Not pretty — just a clear visual signal that the PNG branch is firing.

- [ ] **Step 2: Add `spriteFile` to the howitzer entry**

Edit `patterns/demo-ant-buster.php`. Find the howitzer entry (around line 4699):

```
    howitzer: {
      name: 'Howitzer',
      description: 'Slow, hard-hitting single shot. The foundation of the heavy line.',
      cost: 50,
      damage: 5,
      fireRate: 0.6,         // shots per second
      range: 110,
      projectileSpeed: 200,
      family: 'heavy',
      tier: 'base',
      upgradesTo: ['siege', 'mortar', 'discus']
    },
```

Add the `spriteFile` field on its own line, e.g. right after `family: 'heavy',`:

```
    howitzer: {
      name: 'Howitzer',
      description: 'Slow, hard-hitting single shot. The foundation of the heavy line.',
      cost: 50,
      damage: 5,
      fireRate: 0.6,         // shots per second
      range: 110,
      projectileSpeed: 200,
      family: 'heavy',
      spriteFile: 'heavy/howitzer.png',
      tier: 'base',
      upgradesTo: ['siege', 'mortar', 'discus']
    },
```

- [ ] **Step 3: Reload and confirm the PNG renders**

1. Hard-reload `http://localhost/dariobimbi.com/demos/ant-buster/` in incognito.
2. DevTools → Network → filter `Img`: one new request to `…/wp-content/themes/dariobimbi/assets/sprites/cannons/heavy/howitzer.png?v=0.17.0` returning 200 OK.
3. Place a Howitzer on the playfield. It renders as the red bar (PNG branch firing).
4. Place a Siege or Mortar next to it. Those render procedurally (no `spriteFile` field).
5. Wait for an ant to walk past the howitzer. Confirm the turret rotates to track it. Muzzle flash spawns at the barrel tip.
6. **Failure mode test:** rename the PNG to `howitzer_broken.png`, hard-reload, confirm the howitzer falls back to the procedural sprite with only a 404 in the Network tab (no JS error in Console).
7. Rename it back to `howitzer.png` once verified.

- [ ] **Step 4: Revert the smoke test**

Roll back both changes — this is a throwaway test, not a commit:

```
git checkout -- patterns/demo-ant-buster.php
rm assets/sprites/cannons/heavy/howitzer.png
```

Verify:
```
git status -s
```

Expected: empty (no working-tree changes; the `assets/sprites/cannons/heavy/howitzer.png` deletion is invisible because the file was never tracked thanks to Task 1 wiping it).

No commit. The real howitzer sprite ships when the user authors one to spec, in a separate post-plan commit.

---

## Self-review checklist

Run through these before declaring done:

- [ ] `git log --oneline -5` shows the five expected commit subjects from Task 5 Step 5.
- [ ] `git status -s` is empty.
- [ ] `ls assets/sprites/cannons/heavy/` is empty.
- [ ] `ls tools/sprites/cannons/heavy/` shows `Annihilator.psd` and `Vortex.psd`.
- [ ] `cat assets/sprites/cannons/README.md` matches Task 1 Step 9 content.
- [ ] `grep "Version:" style.css` reports `Version: 0.17.0`.
- [ ] `curl -s "http://localhost/dariobimbi.com/demos/ant-buster/" | grep -c "cannonTurretPNG"` reports 4 or more matches (one in the preload loop, one in the renderer).
- [ ] No console errors in the incognito-loaded demo. All 14 heavy cannons (those you can afford to place) render via procedural fallback, visually identical to the pre-plan main branch.
